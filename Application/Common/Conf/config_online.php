<?php
return array(
    //'配置项'=>'配置值'
   /* 数据库配置项 */
    'DB_TYPE'   => 'mysqli', // 数据库类型
    'DB_HOST'   => 'api.mysql.tdedu.org', // 服务器地址
    'DB_NAME'   => 'paotui', // 数据库名
    'DB_USER'   => 'root', // 用户名
    'DB_PWD'    => 'liguopeng163.com', // 密码
    'DB_PORT'   => '3306', // 端口
    'DB_PREFIX' => 'pt_', // 数据库表前缀
    'DB_CHARSET'=> 'utf8', // 字符集
    'DB_DEBUG'  =>  TRUE, // 数据库调试模式 开启后可以记录SQL日志 3.2.3新增
    
    // redis缓存服务配置
    'REDIS_HOST' => 'r-2zed402562df3e14.redis.rds.aliyuncs.com', // 127.0.0.1     192.168.10.159
    'REDIS_PORT' => 6379,
    'REDIS_USER' => 'test_username',
    'REDIS_PWD' => 'LGP163com',
    'REDIS_PREFIX' => 'API', // 每个项目单独配置
    
    'CACHE_TOKEN_TIMER' => 3000, //token过期时间
    // memeched缓存服务配置
    'MEMECHE_HOST' => 'm-2zed2894b6b0c4c4.memcache.rds.aliyuncs.com',
    'MEMECHE_PORT' => 11211,
);