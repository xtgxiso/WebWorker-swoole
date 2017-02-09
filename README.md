WebWorker-swoole
========

基于swoole实现的自带http server的web开发框架，用于开发高性能的api应用，例如app接口服务端等,WebWorker项目的swoole版本,项目示例可参见 https://github.com/xtgxiso/WebWorker-swoole-example 
特性
========
* 天生继承swoole所拥有的特性
* 只实现了简单路由功能的小巧框架,便于开发者使用和扩展,非常具有灵活性
* 相比php-fpm或mod_php的方式性能有几十倍左右的提升
* 可设置自动加载目录加载目录下的所有php文件(仅一级不支持递归)
* 自定义404响应
* 支持中间件
* redis支持原生同步和协程版本，只需要一个配置参数即可
* mysql支持原生同步和协程版本，只需要一个配置参数即可

框架由来
========
大家经常说php性能差，其实主要是在php-fpm或mod_php方式下的差，而php语言本身是不错的，尤其在未来加入JIT之后，性能会越来越好的。面对新兴的语言和开发方式，个人认为php应该抛弃php-fpm或mod_php的开发方式了，以主流的守护进程的方式来开发，这样的方式性能会比php-fpm或mod_php有几十倍左右的提升.

安装方式1(用composer)--推荐方式
========

```
composer require xtgxiso/webworker-swoole
```

安装方式2(直接使用)
========

```
git clone git@github.com:xtgxiso/WebWorker-swoole.git

//需要将代码包含require_once 'vendor/autoload.php'替换成如下内容
require_once 'WebWorker-swoole/App.php';
require_once 'WebWorker-swoole/Libs/Controller.php';
require_once 'WebWorker-swoole/Libs/CoroutineMysql.php';
require_once 'WebWorker-swoole/Libs/Mmysqli.php';
require_once 'WebWorker-swoole/Libs/Mredis.php';

```



快速开始
======
demo.php
```php
<?php

require_once 'vendor/autoload.php';

$app = new WebWorker\App("0.0.0.0",1215);

//进程数
$app->count = 4;

//自动加载目录--会加载目录下的所有php文件
$app->autoload = array();

//应用级中间件--对所有访问启用ip限制访问
$app->AddFunc("/",function() {
    if ( $this->request->server['remote_addr'] != '127.0.0.1' ) {
        $this->ServerHtml("禁止访问");
        return true;//返回ture,中断执行后面的路由或中间件，直接返回给浏览器
    }   
});

//注册路由hello
$app->HandleFunc("/hello",function() {
    $this->ServerHtml("Hello World");
});

//注册路由json
$app->HandleFunc("/json",function() {
     //以json格式响应
     $this->ServerJson(array("name"=>"WebWorker"));
});

//注册路由input
$app->HandleFunc("/input",function() {
    //获取body
     $body = $this->request->rawContent();
     $this->ServerHtml($body);
});

$config = array();
$config["redis"]["host"] = "127.0.0.1";
$config["redis"]["port"] = 6379;
$config["redis"]["password"] = "123456";
$config["redis"]["db"] = 1;
//是否自动初始化连接
$config["redis"]["load"] = 1;
//是否启用协程redis库
$config["redis"]["coroutine"] = 1;
//记录redis连接池数量
$config["redis"]["coroutine_count"] = 0;
//存放所有的redis连接
$config["redis"]["coroutine_pool"] = new SplQueue();

//注册路由redis
$app->HandleFunc("/redis",function() {
    $this->redis->set("xtgxiso",time());
    $str = $this->redis->get("xtgxiso");
    $this->ServerHtml($str);
});

$config['db']['host'] = "127.0.0.1";
$config['db']['user'] = "root";
$config['db']['password'] = "123456";
$config['db']['database'] = "test";
$config['db']['port'] = 3306;
$config['db']['charset'] = "utf8";
$config["db"]["load"] = 1;
$config["db"]["coroutine"] = 1;
$config["db"]["coroutine_count"] = 0;
$config["db"]["coroutine_pool"] = new SplQueue();

//注册路由mysql
$app->HandleFunc("/mysql",function() {
    $res = $this->db->query("select * from test where id =2");
    $this->ServerJson($res);
});

$app->on404  = function() {
    $this->ServerHtml("我的404");
};

$app->run();
```

命令
========

```
php demo.php start 
php demo.php start -d
php demo.php reload
php demo.php restart
```

技术交流QQ群
========
517297682
