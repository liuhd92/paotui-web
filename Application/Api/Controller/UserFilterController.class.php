<?php
// +----------------------------------------------------------------------
// | http://tiandaoedu.com/ 天道教育
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://tiandaoedu.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: liukw<kaiwei.liu@tiandaoedu.com>
// +----------------------------------------------------------------------

namespace Api\Controller;
use Think\Log;
use Think\Controller\RestController;

/**
 * 对外接口数据校验控制器（对外接口需要继承此类）
 */
class UserFilterController extends RestController{
    /*必填字段 */
    protected $accept_encoding = ''; // 固定字符串:gzip
    protected $client_version = ''; // 客户端版本号码，当前全部使用0
    protected $lang = ''; // 客户端语言环境，取值为：CN或EN
    protected $os = ''; // 操作系统名称：取值为android或ios
    /*非必填字段*/
    protected $token = ''; // 上次登录后者修改密码后返回的数据。
    protected $uid = ''; // 未登录用户传递为空，否则返回上次登录操作返回的编号。
    
    /**
     * 控制器初始化
     */
    public function _initialize(){
        $this->checkHeader();
        $this->checkIP();
        $this->checkVersion();
        $this->checkSign();
    }
    
    /**
     * 获取表头信息
     * @return array
     */
    private function getHeaders(){
        $headers = '';
        foreach($_SERVER as $name => $value){
            if(substr($name, 0, 5) == 'HTTP_'){
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        
        return $headers;
    }
    
    /**
     * 校验报头信息
     */
    private function checkHeader(){
        //获取当前URL
        $url = 'http://'.$_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        //获取报头信息
        $header = self::getHeaders();
        
        //获取传入accept_encoding
        if(empty($header['Accept-Encoding'])){
            json_error(10301);
        }else{
            $this->accept_encoding = $header['Accept-Encoding'];
        }
        
        //获取传入的版本号
        if(empty($header['Client-Version'])){
            json_error(10302);
        }else{
            $this->client_version = $header['Client-Version'];
        }
        
        //获取传入的客户端语言环境
        if(empty($header['Lang'])){
            json_error(10303);
        }else{
            $this->lang = $header['Lang'];
        }
        
        //获取传入的操作系统名称
        if(empty($header['Os'])){
            json_error(10304);
        }else{
            $this->os = $header['Os'];
        }
        
        //获取传入的token
        $this->token = isset($header['Token']) ? $header['Token'] : '';
        
        //获取传入的uid
        $this->uid = isset($header['Uid']) ? (int)$header['Uid'] : '';
    }
    
    /**
     * 校验IP(暂未开放)
     */
    private function checkIP(){
        return true;
    }
    
    /**
     * 版本检查（强制更新）
     */
    private function checkVersion(){
        $version_number = $this->client_version; //版本号信息
        $iosLocalVersion = C('IOS_LOCAL_VERSION');// ios接口版本
        $androidLocalVersion = C('ANDROID_LOCAL_VERSION'); // android接口版本
        
        $version = explode(".", $version_number);
        $ios = explode(".", $iosLocalVersion);
        $android = explode(".", $androidLocalVersion);
        
        if($this->os == 'android'){
            if ($version[0]<$android[0]){
                json_error(10104);
            }
        }else if($this->os == 'iOS'){
            if($version[0]<$ios[0]){
                json_error(10104);
            }
        }
        return true;
    }
    
    /**
     * 校验Sign
     */
    private function checkSign(){
        // 获取传入sign
        $sign_veryfy = I('post.sign','');
        $_sign = create_sign($_POST);
        Log::write('sign : '.$_sign);
        if($_sign){
            // 对比sign字段
            if($sign_veryfy != $_sign){
                //sign验证失败，非法请求
                json_error(10305);
            }
        }
    }
    
    /**
     * 校验用户登陆状态
     */
    protected function checkLogin(){
        $User = D('User');
        $data = $User->getToken($this->uid);
        
        if($data){
            if($data['token'] == $this->token){
                return true;
            }else{
                //设备在其它设备上登陆
                json_error(10307, array('msg' => str_replace("%", $data['equipment'], C('ERR_CODE.10307'))));
            }
        }
        //token校验失败
        json_error(10306);
    }
}