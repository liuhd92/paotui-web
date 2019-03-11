<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<!--[if lt IE 7]> <html class="lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]> <html class="lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]> <html class="lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="en"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <title>Login Form</title>
  <link rel="stylesheet" href="/Public/css/login.css">
  <!--[if lt IE 9]><script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
</head>
<body>

  <section class="container">
    <div class="login">
      <h1>用户登录</h1>
      <form method="post" action="<?php echo U('index/index');?>">
        <p><input type="text" name="login" value="" placeholder="邮箱"></p>
        <p><input type="password" name="password" value="" placeholder="密码"></p>
        <p class="submit"><input type="submit" name="commit" value="登录"></p>
      </form>
    </div>
  </section>

</body>
</html>