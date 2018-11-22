<?php
return array(
    //'配置项'=>'配置值'
    'MODULE_ALLOW_LIST' => array('Admin', 'Api'),
    'DEFAULT_MODULE' => 'Admin',  // 默认模块
    'URL_CASE_INSENSITIVE' => true, //屏蔽大写
    'URL_MODEL' => '2', //URL 模式
    'URL_HTML_SUFFIX' => '', //伪静态后缀
    
    'LOAD_EXT_CONFIG' => 'conf_local', // 引入外部配置
);