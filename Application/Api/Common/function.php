<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

/**
 * 获取 IP  地理位置
 * 淘宝IP接口
 * @Return: array
 */
function get_client_city_byIP($ip = ''){
    if($ip == ''){
        $url = "http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json";
        $ip=json_decode(file_get_contents($url),true);
        $data = $ip;
    }else{
        $url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
        $ip=json_decode(file_get_contents($url));
        if((string)$ip->code=='1'){
            return false;
        }
        $data = (array)$ip->data;
    }

    return $data;
}

/**
 * 格式化缓存key
 * @param array|string $key
 * @return string
 * @author liukw
 */
function format_key($key){
	return C('CACHE_PREFIX').':'.$key;
}

/**
 * redis通用方法（如果缓存 标识不存在或者已经过期，则返回false）
 * @param string $key 缓存主键名
 * @param object $value 缓存值
 * @param int $expire 过期时间（0 表示永不过期）
 * @author liukw
 */
function memcache_cache($key, $value=false, $expire=0){
    $res = false; // 返回值（不存在或者访问失败）
    /* Windows处理方式  */
    if(IS_WIN){
        $mem = new \Memcache;
        if(!$mem->connect(C('MEMECHE_HOST'), C('MEMECHE_PORT'))){
            return false;
        }
        $mem->add($key, $value, MEMCACHE_COMPRESSED, 60);

        if($value !== false){
            // 删除缓存
            if($value == null){
                $res = $mem->delete($key);
            }else{
                if($expire > 0){
                    $res = $mem->set($key, $value, MEMCACHE_COMPRESSED, $expire);
                }else{
                    $res = $mem->set($key, $value, MEMCACHE_COMPRESSED);
                }
            }
        }else{ // 缓存读取
            $res = $mem->get($key);
        }
        $mem->close();
    }else{ /* Linux处理方式  */
        $memcache = new Memcached;  //声明一个新的memcached链接
        $memcache->setOption(Memcached::OPT_COMPRESSION, false); //关闭压缩功能
        $memcache->setOption(Memcached::OPT_BINARY_PROTOCOL, true); //使用binary二进制协议
        $memcache->setOption(Memcached::OPT_TCP_NODELAY, true); //重要，php memcached有个bug，当get的值不存在，有固定40ms延迟，开启这个参数，可以避免这个bug
        $memcache->addServer(C('MEMECHE_HOST'), C('MEMECHE_PORT')); //添加OCS实例地址及端口号

        // 缓存修改
        if ($value !== false) {
            // 删除缓存
            if ($value == null) {
                $res = $memcache->delete($key);
            } else {
                if ($expire > 0) {
                    $res = $memcache->set($key, $value, 0, $expire);
                }else{
                    $res = $memcache->set($key, $value);
                }
            }
        } else { // 缓存读取
            $res = $memcache->get($key);
        }
    }

    return $res;
}

function load_redis($option, $key, $value='', $field='', $time=''){
	$redis = new Redis();
	$redis->connect(C('REDIS_HOST'), C('REDIS_PORT'));

    /*-----------线上redis服务器需要密码-------------*/
//      if ($redis->auth(C("REDIS_PWD")) == false){
//          json_error(10204);// Redis服务器密码错误
//      }
    $redis->select(0);
	switch($option){
		case 'exists':
			$return = $redis->exists($key);
			break;
		case 'hset':
			$return = $redis->hset($key, $field, $value);
			break;
		case 'hget':
			$return = $redis->hget($key, $field);
			break;
		case 'lpush':
			$return = $redis->lPush($key, $value);
			break;
		case 'lget':
			$return = $redis->lget($key);
			break;
		case 'rpush':
			$return = $redis->rPush($key, $value);
			break;
		case 'rget':
			$return = $redis->rget($key);
			break;
		case 'lsize':
			$return = $redis->lsize($key);
			break;
		case 'lrange_all':
			$return = $redis->lrange($key, 0, -1);
			break;
		case 'delete':
			//删除指定key
			$return = $redis->delete($key);
			break;
        case 'keys' :
            $return = $redis->keys($key.'*');
	}
	$redis->close();	
	return $return;
}

/**
 * 提取中英文首字母
 * @param $str
 * @return string
 */
function get_first_letter($str)
{
    $str= iconv("UTF-8","gb2312", $str);//如果程序是gbk的，此行就要注释掉
        $fchar=ord($str{0});
        if($fchar>=ord("A") and $fchar<=ord("z") )return strtoupper($str{0});
        $a = $str;
        $val=ord($a{0})*256+ord($a{1})-65536;
        if($val>=-20319 and $val<=-20284)return "A";
        if($val>=-20283 and $val<=-19776)return "B";
        if($val>=-19775 and $val<=-19219)return "C";
        if($val>=-19218 and $val<=-18711)return "D";
        if($val>=-18710 and $val<=-18527)return "E";
        if($val>=-18526 and $val<=-18240)return "F";
        if($val>=-18239 and $val<=-17923)return "G";
        if($val>=-17922 and $val<=-17418)return "H";
        if($val>=-17417 and $val<=-16475)return "J";
        if($val>=-16474 and $val<=-16213)return "K";
        if($val>=-16212 and $val<=-15641)return "L";
        if($val>=-15640 and $val<=-15166)return "M";
        if($val>=-15165 and $val<=-14923)return "N";
        if($val>=-14922 and $val<=-14915)return "O";
        if($val>=-14914 and $val<=-14631)return "P";
        if($val>=-14630 and $val<=-14150)return "Q";
        if($val>=-14149 and $val<=-14091)return "R";
        if($val>=-14090 and $val<=-13319)return "S";
        if($val>=-13318 and $val<=-12839)return "T";
        if($val>=-12838 and $val<=-12557)return "W";
        if($val>=-12556 and $val<=-11848)return "X";
        if($val>=-11847 and $val<=-11056)return "Y";
        if($val>=-11055 and $val<=-10247)return "Z";

}

//$arr->传入数组   $key->判断的key值
 function array_unset_tt($arr,$key){
     //建立一个目标数组
     $res = array();
     foreach ($arr as $value) {
         //查看有没有重复项
         if(isset($res[$value[$key]])){
             //有：销毁
             unset($value[$key]);
         }else{
             $res[$value[$key]] = $value;
         }
     }
     return $res;
 }
 
 /**
  * 生成订单号
  * 订单号生成规则：年月日时分秒 + 业务缩写 + 4位大写随机数
  */
 //1取快递|2送快递|3取餐|4衣物送洗|5送洗衣物代取，以后拓展往后顺接
 function create_order_num($type) {
     if (empty($type)) {
         return false;
     }
     
     $order_num = date('YmdHis');
     switch ($type){
         case 1:
             $order_num = 'QKD'.$order_num; // 取快递
             break;
         case 2:
             $order_num = 'SKD'.$order_num; // 送快递
             break;
         case 3:
             $order_num = 'QC'.$order_num; // 取餐
             break;
         case 4:
             $order_num = 'YWSX'.$order_num; // 衣物送洗
             break;
         case 5:
             $order_num = 'SXYWDQ'.$order_num; // 送洗衣物代取
             break;
         default:
             $order_num = 'QT'.$order_num;
             break;
     }
     $order_num.=randcode(4, 5);
     return $order_num;
 }