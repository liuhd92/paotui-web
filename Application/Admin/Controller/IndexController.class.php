<?php
namespace Admin\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index(){
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379); //连接Redis
        $redis->select(2);//选择数据库2
        $redis->set( "testKey" , "Hello Redis"); //设置测试key
        echo $redis->get("testKey");//输出value
        exit;
        $this->display();
        
    }
}