<?php

/**
 * 数据排序、删除杂项及空值
 */
function para_filter_sort($para) {
	$para_filter = array();
	ksort($para);

	foreach ($para as $key=>$val) {
		//将key全部小写
		$key = strtolower($key);
		if ($key === "sign" || $key === "action" || $key === "_request" || $val === "" || is_null($val)) {
			continue;
		} else {
			if (is_bool($val)) {
				$para_filter[$key] = (int)$val;
			} else {
				$para_filter[$key] = is_array($val) ? para_filter_sort($val) : $val;
			}
		}
	}

	return $para_filter;
}

/**
 * 生成拼接串
 * @param string $para
 * @return string
 */
function create_link_string($para){
	$arg = '';
	foreach($para as $key => $val){
		if(is_array($val)){
			$arg .= $key . '=(' . (is_array($val) ? create_link_string($val) : $val) . ')&';
		}else{
			$arg .= $key . '=' . $val . '&';
		}
	}

	if(count($arg) > 0){
		//去掉最后一个&字符
		$arg = substr($arg, 0, count($arg) - 2);
	}

	return $arg;
}

/**
 * 生成校验字符串
 * @param string $data
 * @return string
 */
function create_sign($data){
	if($data){
		// 按照key对数组进行排
		$data = para_filter_sort($data);
		// 生成拼接串
		$prestr = create_link_string($data);

		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()){
			$arg = stripslashes($arg);
		}
		
		//echo '$prestr = '.$prestr;
		if(empty($prestr)){
			return '';
		}else{
		    // 拼接appkey
			$prestr .= C('API_KEY');
			return md5($prestr);
		}
	}else{
		return '';
	}
}

/**
 * 错误结果格式化
 */
function json_error($code, $data='', $sign=false, $isexit=true){
	if(empty($data)){
		$data = array('msg'=>C('ERR_CODE.'.$code));
	}
	
	$return = array();
	$return['code'] = $code;
	$return['result'] = 'error';
	$return['time'] = time();
	if (is_array($data) && count($data) == 0){
		$return['data'] = null;
	} else {
		$return['data'] = $data;
	}	

	if($sign && is_array($data)){
		$return['sign'] = create_sign($data);
	}

	if($isexit){
		header('Content-Type:application/json; charset='.C('DEFAULT_CHARSET'));
		echo json_encode($return);
		exit;
	}else{
		return $return;
	}
}

/**
 * 正确结果格式化
 */
function json_success($data, $sign=false, $isexit=true){
	$return = array();
	$return['code'] = 0;
	$return['result'] = 'ok';
	$return['time'] = time();
	if (is_array($data) && count($data) == 0) {
		$return['data'] = null;
	}else{
		$return['data'] = $data;
	}
	
	if($sign && is_array($data)){
		$return['sign'] = create_sign($data);
	}	

	if($isexit){
		header('Content-Type:application/json; charset='.C('DEFAULT_CHARSET'));
		echo json_encode($return);
		exit;
	}else{
		return $return;
	}
}

/**
 * 获取来源id
 */
function get_source_id($os){
	$source_id = 2;//默认安卓
	
	if($os){		
		if(strtolower($os) == 'android'){
			$source_id = 2;
		}		
		if(strtolower($os) == 'ios'){
			$source_id = 3;
		}
		if(strtolower($os) == 'weixin'){
			$source_id = 4;
		}
	}

	return $source_id;
}

/**
 * url跳转（原生态）
 */
function jump($url){
	header('Location: ' . $url);
	exit;
}

/**
 * 检测手机号
 */
function is_mobile($mobile){
	if (preg_match("/^1[0-9]{10}$/", $mobile)) {
		return true;
	}else{
		return false;
	}
}

function guid(){
	$uuid = '';
	if(function_exists('com_create_guid')){
		$uuid = com_create_guid();
	}else{
		mt_srand((double)microtime()*10000); // optional for php 4.2.0 and up.
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = chr(45);// "-"
		$uuid = chr(123)// "{"
		.substr($charid, 0, 8).$hyphen
		.substr($charid, 8, 4).$hyphen
		.substr($charid,12, 4).$hyphen
		.substr($charid,16, 4).$hyphen
		.substr($charid,20,12)
		.chr(125);// "}"
	}

	$uuid = str_replace('-','',$uuid);
	$uuid = str_replace('{','',$uuid);
	$uuid = str_replace('}','',$uuid);
	return $uuid;
}

/**
 * 网络请求
 * @param string $url 请求地址
 * @param string $param 请求参数  xxx=xxx&xxx=xxx
 * @param string $request_method 请求方法
 * @return string|boolean
 */
function http_curl($url, $param='', $request_method='GET'){
	$ch = curl_init();//初始化curl
	curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
	curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上

	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , false);

	if($request_method == 'POST'){
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $param);
	}else if($request_method == 'GET'){
		if(is_array($param)){
			$str_p = '';
			foreach($param as $k => $v){
				if(empty($str_p)){
					$str_p .= "?{$k}={$v}";
				}else{
					$str_p .= "&{$k}={$v}";
				}
			}
			curl_setopt($ch, CURLOPT_URL, $url.$str_p);//抓取指定网页
		}
	}else{
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
	}
	$data = curl_exec($ch);//运行curl

	if(curl_errno($ch)){
		return false;
	}
	
	curl_close($ch);
	return $data;
}

/**
 * 获取随机数
 * @param 长度 int $len
 * @param 类型 int $mode
 * @return string
 * @author liukw
 */
function randcode($len, $mode=2){
	$rcode = '';

	switch($mode){
		case 1: //去除0、o、O、l、1等易混淆字符
			$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghijkmnpqrstuvwxyz';
			break;
		case 2: //纯数字
			$chars = '0123456789';
			break;
		case 3: //全数字+大小写字母
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
			break;
		case 4: //全数字+大小写字母+一些特殊字符
		    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz~!@#$%^&*()';
		    break;
		case 5: //大写字母
		    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		    break;
	}

	$count = strlen($chars) - 1;
	mt_srand((double)microtime() * 1000000);
	for($i = 0; $i < $len; $i++){
		$rcode .= $chars[mt_rand(0, $count)];
	}

	return $rcode;
}

/**
 * 传递数据以易于阅读的样式格式化后输出
 * @param array $data
 */
function P($data){
    // 定义样式
    $str = '<pre style="display: block;padding: 9.5px;margin: 44px 0 0 0;font-size: 13px;line-height: 1.42857;color: #333;word-break: break-all;word-wrap: break-word;background-color: #F5F5F5;border: 1px solid #CCC;border-radius: 4px;">';
    // 如果是boolean或者null直接显示文字；否则print
    if (is_bool($data)) {
        $show_data = $data ? 'true' : 'false';
    } elseif (is_null($data)) {
        $show_data = 'null';
    } else {
        $show_data = print_r($data, true);
    }
    $str .= $show_data;
    $str .= '</pre>';
    echo $str;
}

/**
 * 比较是否相等
 * @param str|array $arr1
 * @param str|array $arr2
 * @param boolean $is_upper 是否大写
 * @param boolean $is_trim  是否去空格
 * @return boolean
 * @author liuhd
 */
function compare($arr1, $arr2, $is_upper=false, $is_trim=false) {

    if((is_array($arr1) && is_array($arr2))){
        foreach ($arr1 as $k1 =>$v1){
            foreach ($arr2 as $k2 => $v2){
                if ($k1==$k2 && $v1 != $v2) {
                    return false;
                }
            }
        }
        return true;
    } else if (is_string($arr1) && is_string($arr2)){
        if ($is_upper) {
            $arr1 = strtoupper($arr1);
            $arr2 = strtoupper($arr2);
        }
        if ($is_trim) {
            $find = array(" ","\t","\n","\r");
            $replace = array("","","","");
            $arr1 = str_replace($find,$replace,$arr1);
            $arr2 = str_replace($find,$replace,$arr2);
        }
        if ($arr1 === $arr2) {
            return true;
        }
    }
    return false;
}


/**
 * 根据assertion获取token
 *
 * @param 加密字段 $assertion
 * @return string
 */
function get_assertion_token($assertion)
{
    if ($assertion) {
        return substr($assertion, 0, 32);
    } else {
        return '';
    }
}

/**
 * 根据assertion获取uid
 *
 * @param 加密字段 $assertion
 * @return string
 */
function get_assertion_uid($assertion)
{
    if ($assertion) {
        $len = substr($assertion, 32, 1);
        return substr($assertion, 33, $len);
    } else {
        return '';
    }
}

/**
 * 根据assertion获取cid
 *
 * @param 加密字段 $assertion
 * @return string
 */
function get_assertion_cid($assertion)
{
    if ($assertion) {
        $uidlen = substr($assertion, 32, 1);
        return substr($assertion, 33 + $uidlen);
    } else {
        return '';
    }
}

/**
 * 获取指定年月日的开始时间戳和结束时间戳(本地时间戳非GMT时间戳)
 * [1] 指定年：获取指定年份第一天第一秒的时间戳和下一年第一天第一秒的时间戳
 * [2] 指定年月：获取指定年月第一天第一秒的时间戳和下一月第一天第一秒时间戳
 * [3] 指定年月日：获取指定年月日第一天第一秒的时间戳
 * @param  integer $year     [年份]
 * @param  integer $month    [月份]
 * @param  integer $day      [日期]
 * @return array('start' => '', 'end' => '')
 */
function getStartAndEndUnixTimestamp($year = 0, $month = 0, $day = 0)
{
    if(empty($year))
    {
        $year = date("Y");
    }

    $start_year = $year;
    $start_year_formated = str_pad(intval($start_year), 4, "0", STR_PAD_RIGHT);
    $end_year = $start_year + 1;
    $end_year_formated = str_pad(intval($end_year), 4, "0", STR_PAD_RIGHT);

    if(empty($month))
    {
        //只设置了年份
        $start_month_formated = '01';
        $end_month_formated = '01';
        $start_day_formated = '01';
        $end_day_formated = '01';
    }
    else
    {

        $month > 12 || $month < 1 ? $month = 1 : $month = $month;
        $start_month = $month;
        $start_month_formated = sprintf("%02d", intval($start_month));

        if(empty($day))
        {
            //只设置了年份和月份
            $end_month = $start_month + 1;

            if($end_month > 12)
            {
                $end_month = 1;
            }
            else
            {
                $end_year_formated = $start_year_formated;
            }
            $end_month_formated = sprintf("%02d", intval($end_month));
            $start_day_formated = '01';
            $end_day_formated = '01';
        }
        else
        {
            //设置了年份月份和日期
            $startTimestamp = strtotime($start_year_formated.'-'.$start_month_formated.'-'.sprintf("%02d", intval($day))." 00:00:00");
            $endTimestamp = $startTimestamp + 24 * 3600 - 1;
            return array('start' => $startTimestamp, 'end' => $endTimestamp);
        }
    }

    $startTimestamp = strtotime($start_year_formated.'-'.$start_month_formated.'-'.$start_day_formated." 00:00:00");
    $endTimestamp = strtotime($end_year_formated.'-'.$end_month_formated.'-'.$end_day_formated." 00:00:00") - 1;
    return array('start' => $startTimestamp, 'end' => $endTimestamp);
}

// object转json
function objecttoarray($array) {
    if(is_object($array)) {
        $array = (array)$array;
    } if(is_array($array)) {
        foreach($array as $key=>$value) {
            $array[$key] = objecttoarray($value);
        }
    }
    return $array;
}

