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
		$total_fee = I('post.total_fee');
		$type = I('post.type');
		
		/* 获取订单编号 */
		$Order = D('Order');
		$order_info = $Order->getInfoById($order_id);
		if ($order_info == null) {
		    json_error(10314); // 暂无当前订单
		} else if($order_info === false) {
		    json_error(10107); // 数据库操作shibai
		}
		switch ($type){
		    case 1:		        
		        $url_notify = 'https://'.$_SERVER['HTTP_HOST'].'/Api/Wxpay/notify'; // 支付订单
		        break;
	        case 2:
	            $order_info['order_number'] = $order_info['order_number'].'GIVE';
	            $url_notify = 'https://'.$_SERVER['HTTP_HOST'].'/Api/Wxpay/notify_rider'; // 打赏骑手
	            break;	            
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
			'notify_url'	=> $url_notify,
			'trade_type'	=> 'JSAPI',
			'openid'		=> $openid
		);
		Log::write('---------------'.$type);
		Log::write(var_export($order_info, true));
// 		Log::write(var_export($unifiedorder, true));
		$unifiedorder['sign'] = self::makeSign($unifiedorder);
		Log::write('---------------');
		Log::write(var_export($unifiedorder, true));
		//请求数据
		$xmldata = self::array2xml($unifiedorder);
		Log::write(var_export($xmldata, true));
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
        
        $content['type'] = $type;
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
		Log::write('进入支付回调了');
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
			$total_fee = $data['total_fee'] / 100;	  //付款金额
			$transaction_id = $data['transaction_id']; 	//微信支付流水号
			
			//更新数据库
			Log::write('$data======>'.json_encode($data));
			
			// 获取订单基础信息
			$Order = D('Order');
			$base_data = $Order->getInfoByNum($order_sn);
			 
			// 获取订单详细信息
			$OrderDetail = D('OrderDetail');
			$detail_data = $OrderDetail->getInfoByOId($base_data['id']);
			
			if(empty($base_data) || empty($detail_data)){
			    exit();
			}
			
			//如果支付宝重复发效验，并且订单已经完成，直接返回成功即可
			if(base_data['status'] == '5') {
				 exit();
			}

	        // 更新订单基础信息
	        $res_base = $Order->edit(
	            array('order_number' => $order_sn), 
	            array(
                    'pay_price' => $total_fee, 
	                'is_pay' => 1, 
	                'update_time' => time(), 
	                'order_status' => 2,
	                'pay_time' => time(),
	            )
	        );
	        Log::write('--------------------');
	        Log::write(var_export($res_base, true));
	        if ($res_base === false || $res_base == 0) $result = false;
	        
	        $OrderPay = D('OrderPay');
	        $res_pay = $OrderPay->add(
	            array(
	                'openid' => $openid,
                    'order_id' => $base_data['id'], 
	                'order_number' => $order_sn, 
	                'cash_sn' => $transaction_id, 
	                'total_fee' => $total_fee,
	                'pay_time' => time(),
	                'trade_type' => $data['trade_type']
	            )
	        );
	        if ($res_pay === false) $result = false;
	        		
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
	
	//微信支付回调验证
	public function notify_rider(){
	    $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
	    Log::write('进入打赏回调了');
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
	        $total_fee = $data['total_fee'] / 100;	  //付款金额
	        $transaction_id = $data['transaction_id']; 	//微信支付流水号
	        	
	        //更新数据库
	        Log::write('$data======>'.json_encode($data));
	        
	        // 获取订单基础信息
	        $Order = D('Order');
	        $base_data = $Order->getInfoByNum(substr($order_sn, 0, strlen($order_sn)-4));
	        Log::write('base_Data');
	        Log::write(var_export($base_data, true));
	        
	        // 获取订单详细信息
	        $OrderDetail = D('OrderDetail');
	        Log::write('detail_Data1');
	        $detail_data = $OrderDetail->getInfoByOId($base_data['id']);
	        Log::write('detail_Data2');
	        Log::write(var_export($detail_data, true));
	        
	        if(empty($base_data) || empty($detail_data)){
	            exit();
	        }
	        	
	        //如果支付宝重复发效验，并且订单已经完成，直接返回成功即可
	        if($detail_data['is_give'] == 1) {
	            exit();
	        }

	        // 添加付款记录
            $give = array();
            $give['oid'] = $base_data['id'];
            $give['uid'] = $base_data['uid'];
            $give['rid'] = $detail_data['rid'];
            $give['total_price'] = $total_fee;
            $give['cash_sn'] = $transaction_id;
            $give['order_number'] = substr($order_sn, 0, strlen($order_sn)-4);
            $give['give_number'] = $order_sn;
            $give['paid_time'] = time();
            Log::write('give_Data');
            Log::write(var_export($give, true));
            
            $RiderGive = D('RiderGive');
            $res_give = $RiderGive->add($give);
            Log::write('add_ersult : '.$res_give);
            
            // 更新基础信息 ： 是否打赏骑手
            $order_result = $OrderDetail->edit(array('order_id' => $base_data['id']), array('is_give' => 1));
            Log::write('order_result');
            Log::write(var_export($order_result, true));
	        	
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