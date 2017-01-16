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

框架由来
========
大家经常说php性能差，其实主要是在php-fpm或mod_php方式下的差，而php语言本身是不错的，尤其在未来加入JIT之后，性能会越来越好的。面对新兴的语言和开发方式，个人认为php应该抛弃php-fpm或mod_php的开发方式了，以主流的守护进程的方式来开发，这样的方式性能会比php-fpm或mod_php有几十倍左右的提升.

安装
========

```
composer require xtgxiso/webworker-swoole
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

$count = 0;
$pool = new SplQueue();

//注册路由redis
$app->HandleFunc("/redis",function() use($count,$pool) {
    $config = array();
    $config["redis"]["host"] = "127.0.0.1";
    $config["redis"]["port"] = 6379;
    $config["redis"]["password"] = "123456";
    $config["redis"]["db"] = 1; 
    //是否启用协程库来操作redis
    $config["redis"]["coroutine"] = $this->request->get['coroutine'];
    if ( $config["redis"]["coroutine"] ){
        if (count($pool) == 0) {
            $redis =  WebWorker\Libs\Mredis::getInstance($config['redis']);
            $pool->push($redis);
            $count++;
        }
        $redis = $pool->pop();
    }else{
        $redis =  WebWorker\Libs\Mredis::getInstance($config['redis']); 
    }
    $redis->set("xtgxiso",time()."-".$config["redis"]["coroutine"]);
    $str = $redis->get("xtgxiso");
    $this->ServerHtml($str);
    if ( $config["redis"]["coroutine"] ){
        $pool->push($redis);
    }
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
