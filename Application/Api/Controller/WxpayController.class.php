<?php
// +----------------------------------------------------------------------
// | http://tiandaoedu.com/ 天道教育
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://tiandaoedu.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: zhaobo<bo.zhao@tiandaoedu.com>
// +----------------------------------------------------------------------

namespace Api\Controller;

use Think\Controller;
use Think\Log;
/**
 * 微信支付业务层
 * @author liuhd
 */
class WxpayController extends Controller{
    
	protected function _initialize(){
		
		if($_SERVER['REQUEST_METHOD'] != 'POST'){
			self::return_err('error request method');
		}
		
		//微信支付参数配置(appid,商户号,支付秘钥)
		$config = array(
			'appid'		 => 'wx3ada1e3ffa9ff8a7',
			'pay_mchid'	 => '1521562281',
			'pay_apikey' => '88isgnogijekoulgnawuxnaituohzxu8'
		                     
		);
		$this->config = $config;
	}
	
	public function index(){
	    json_success('12345');
	}
	
	/**
     * 预支付请求接口(POST)
     * @param string $openid 	openid
     * @param string $body 		商品简单描述
     * @param string $order_sn  订单编号
     * @param string $total_fee 金额
     * @return  json的数据
     */
	public function prepay(){
		$config = $this->config;
        $openid = I('post.openid');
		$order_id = (int)I('post.oid', 0);
		$body = I('post.body');
		$order_sn = I('post.order_sn');
		$total_fee = I('post.total_fee');
		
		/* 获取订单编号 */
		$Order = D('Order');
		$order_info = $Order->getInfoById($order_id);
		if ($order_info == null) {
		    json_error(10314); // 暂无当前订单
		} else if($order_info === false) {
		    json_error(10107); // 数据库操作shibai
		}
		
		//统一下单参数构造
		$unifiedorder = array(
			'appid'			=> $config['appid'],
			'mch_id'		=> $config['pay_mchid'],
			'nonce_str'		=> self::getNonceStr(),
			'body'			=> $body,
			'out_trade_no'	=> $order_info['order_number'],
			'total_fee'		=> $total_fee * 100,
			'spbill_create_ip'	=> get_client_ip(),
			'notify_url'	=> 'https://'.$_SERVER['HTTP_HOST'].'/Api/Wxpay/notify',
			'trade_type'	=> 'JSAPI',
			'openid'		=> $openid
		);
		$unifiedorder['sign'] = self::makeSign($unifiedorder);
		//请求数据
		$xmldata = self::array2xml($unifiedorder);
		$url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $res = self::curl_post_ssl($url, $xmldata);
        if(!$res){
			self::return_err("Can't connect the server");
        }
		// 这句file_put_contents是用来查看服务器返回的结果 测试完可以删除了
		//file_put_contents(APP_ROOT.'/Statics/log1.txt',$res,FILE_APPEND);
		
		$content = self::xml2array($res);
		if(strval($content['result_code']) == 'FAIL'){
			self::return_err(strval($content['err_code_des']));
        }
		if(strval($content['return_code']) == 'FAIL'){
			self::return_err(strval($content['return_msg']));
        }
        self::return_data(array('data'=>$content));
		//$this->ajaxReturn($content);
	}
	
	
	/**
     * 进行支付接口(POST)
     * @param string $prepay_id 预支付ID(调用prepay()方法之后的返回数据中获取)
     * @return  json的数据
     */
	public function pay(){
		$config = $this->config;
		$prepay_id = I('post.prepay_id');
		
		$data = array(
			'appId'		=> $config['appid'],
			'timeStamp'	=> time(),
			'nonceStr'	=> self::getNonceStr(),
			'package'	=> 'prepay_id='.$prepay_id,
			'signType'	=> 'MD5'
		);
		
		$data['paySign'] = self::makeSign($data);
		
		$this->ajaxReturn($data);
	}
	
	//微信支付回调验证
	public function notify(){
		$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
		Log::write('进入回调了');
		// 这句file_put_contents是用来查看服务器返回的XML数据 测试完可以删除了
		//file_put_contents(APP_ROOT.'/Statics/log2.txt',$res,FILE_APPEND);
		
		//将服务器返回的XML数据转化为数组
		$data = self::xml2array($xml);
		// 保存微信服务器返回的签名sign
		$data_sign = $data['sign'];
		// sign不参与签名算法
		unset($data['sign']);
		$sign = self::makeSign($data);
		
		// 判断签名是否正确  判断支付状态
		if ( ($sign===$data_sign) && ($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS') ) {
			$result = $data;
			//获取服务器返回的数据
			$order_sn = $data['out_trade_no'];			//订单单号
			$openid = $data['openid'];					//付款人openID
			$total_fee = $data['total_fee'] / 100;			//付款金额
			$transaction_id = $data['transaction_id']; 	//微信支付流水号
			
			//更新数据库
			Log::write('$data======>'.json_encode($data));
			$Order = D('Order');
			$OrderDetail = D('OrderDetail');
			$MyClass = D('MyClass');
			$OrderLog = D('OrderLog');
			$Class = D('Class');
			$ClassMember = D('ClassMember');
			$Course = D('Course');
			$order_data = $Order->getInfoByOrderNo($order_sn);
			if(empty($order_data)){
				exit();
			}
			
			//如果支付宝重复发效验，并且订单已经完成，直接返回成功即可
			if($order_data['status'] == '2') {
				 exit();
			}
			
			$now = time();
			if($order_data['amount'] == $total_fee){
				
				$edit_where = array();
				$edit_where['id'] = $order_data['id'];
				$edit_data = array();
				$edit_data['status'] = 2;
				$edit_data['payment'] = 2;
				$edit_data['paid_time'] = $now;
				$edit_data['cash_sn'] = $transaction_id;
				$edit_data['updated_time'] = $now;
			
				$order_result = $Order->edit($edit_where, $edit_data); //更新订单状态
				$course_id = 0;
				if($order_result){
					//新增我的课程数据
					$detail_list = $OrderDetail->getOrderDetailListByOrderId($order_data['id']);
					if($detail_list){
						foreach($detail_list as $val){
							$course_id = $val['course_id']; //获取大班课ID
							
							$myclass_data = array();
							$myclass_data['user_id'] = $val['user_id'];
							$myclass_data['course_id'] = $val['course_id'];
							$myclass_data['class_id'] = $val['class_id'];
							$myclass_data['type'] = $val['type'];
							$myclass_data['expiry_day'] = 0;
							$myclass_data['created_time'] = $now;
							if($val['play_type'] == 1){//直播
								if($val['expiry_day'] > 0 && $val['start_time']){
									if($now > $val['start_time']){//开课前买的 从开课时间算
										$myclass_data['expiry_day'] = $now + $val['expiry_day'] * 86400;
									}else{//开课时间以后买的 以当前时间算
										$myclass_data['expiry_day'] = $val['start_time'] + $val['expiry_day'] * 86400;
									}
								}
							}else{
								if($val['expiry_day'] > 0){
									$myclass_data['expiry_day'] = $now + $val['expiry_day'] * 86400;
								}
			
							}
							 
							 
							//班课学生记录
							$classmember = array();
							$classmember['class_id'] = $val['class_id'];
							$classmember['course_id'] = $val['course_id'];
							$classmember['user_id'] = $val['user_id'];
							$classmember['order_id'] = $order_data['id'];
							$classmember['note_num'] = 0;
							$classmember['is_learned'] = 0;
							$classmember['created_time'] = $now;
							$classmember['updated_time'] = 0;
							$classmember['remark'] = '';
			
							$class_member_result = $ClassMember->add($classmember);
							if($class_member_result){
								//更新班课的学生数
								$myclass_addresult = $MyClass->add($myclass_data);
								if($myclass_addresult){
									$class_where = array();
									$class_data = array();
									$class_where['id'] = $val['class_id'];
									$class_data['student_num'] = array('exp', 'student_num+1');
									$Class->edit($class_where,$class_data);
								}
							}
						}
					}

					//更新大班课的总销售收入
					if($course_id){
						$Course->edit(array('id'=>$course_id),array('income'=>array('exp', 'income+'.$order_data['amount'])));
					}	
					
					//订单支付日志
					$order_log = array();
					 
					$order_log['order_id'] = $order_data['id'];
					$order_log['user_id'] = $order_data['user_id'];
					$order_log['type'] = 2;
					$order_log['message'] = '支付成功';
					$order_log['ip'] = get_client_ip();
					$order_log['created_time'] = $now;
					$order_log_json = json_encode(array('sn'=>$order_data['id'],'status'=>'success','amount'=>$order_data['amount'],'paid_time'=>$now));
					$order_log['data'] = $order_log_json;
					 
					$OrderLog->add($order_log);
					 
				}
			
			}
			
		}else{
			$result = false;
		}
		// 返回状态给微信服务器
		if ($result) {
			$str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
		}else{
			$str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
		}
		echo $str;
		return $result;
	}
	
//---------------------------------------------------------------用到的函数------------------------------------------------------------
	/**
     * 错误返回提示
     * @param string $errMsg 错误信息
     * @param string $status 错误码
     * @return  json的数据
     */
	protected function return_err($errMsg='error',$status=0){
		exit(json_encode(array('status'=>$status,'result'=>'fail','errmsg'=>$errMsg)));
	}
	
	
	/**
     * 正确返回
     * @param 	array $data 要返回的数组
     * @return  json的数据
     */
	protected function return_data($data=array()){
		exit(json_encode(array('status'=>1,'result'=>'success','data'=>$data)));
	}
  
	/**
     * 将一个数组转换为 XML 结构的字符串
     * @param array $arr 要转换的数组
     * @param int $level 节点层级, 1 为 Root.
     * @return string XML 结构的字符串
     */
    protected function array2xml($arr, $level = 1) {
        $s = $level == 1 ? "<xml>" : '';
        foreach($arr as $tagname => $value) {
            if (is_numeric($tagname)) {
                $tagname = $value['TagName'];
                unset($value['TagName']);
            }
            if(!is_array($value)) {
                $s .= "<{$tagname}>".(!is_numeric($value) ? '<![CDATA[' : '').$value.(!is_numeric($value) ? ']]>' : '')."</{$tagname}>";
            } else {
                $s .= "<{$tagname}>" . $this->array2xml($value, $level + 1)."</{$tagname}>";
            }
        }
        $s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
        return $level == 1 ? $s."</xml>" : $s;
    }
	
	/**
	 * 将xml转为array
	 * @param  string 	$xml xml字符串
	 * @return array    转换得到的数组
	 */
	protected function xml2array($xml){   
		//禁止引用外部xml实体
		libxml_disable_entity_loader(true);
		$result= json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);        
		return $result;
	}
	
	/**
	 * 
	 * 产生随机字符串，不长于32位
	 * @param int $length
	 * @return 产生的随机字符串
	 */
	protected function getNonceStr($length = 32) {
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		} 
		return $str;
	}
	
	/**
	* 生成签名
	* @return 签名
	*/
	protected function makeSign($data){
		//获取微信支付秘钥
		$key = $this->config['pay_apikey'];
		// 去空
		$data=array_filter($data);
		//签名步骤一：按字典序排序参数
		ksort($data);
		$string_a=http_build_query($data);
		$string_a=urldecode($string_a);
		//签名步骤二：在string后加入KEY
		//$config=$this->config;
		$string_sign_temp=$string_a."&key=".$key;
		//签名步骤三：MD5加密
		$sign = md5($string_sign_temp);
		// 签名步骤四：所有字符转为大写
		$result=strtoupper($sign);
		return $result;
	}
	
	/**
	 * 微信支付发起请求
	 */
	protected function curl_post_ssl($url, $xmldata, $second=30,$aHeader=array()){
		$ch = curl_init();
		//超时时间
		curl_setopt($ch,CURLOPT_TIMEOUT,$second);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		//这里设置代理，如果有的话
		//curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
		//curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
		
	 
		if( count($aHeader) >= 1 ){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
		}
	 
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$xmldata);
		$data = curl_exec($ch);
		if($data){
			curl_close($ch);
			return $data;
		}
		else { 
			$error = curl_errno($ch);
			echo "call faild, errorCode:$error\n"; 
			curl_close($ch);
			return false;
		}
	}
}