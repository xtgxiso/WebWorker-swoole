<?php
namespace WebWorker\Libs;

class Mredis{

    /**
     * 静态成品变量 保存全局实例
     */
    private static  $_instance = array();

    /**
     * 静态工厂方法，返还此类的唯一实例
     */
    public static function getInstance($config=array()) {
        $host = isset($config["host"]) ? $config["host"] : "127.0.0.1";
        $port = isset($config["port"]) ? $config["port"] : 6379;
        $password = isset($config["password"]) ? $config["password"] : "";
        $db = isset($config["db"]) ? $config["db"] : 0;
	if ( $config['coroutine'] ){
	    $redis = new Swoole\Coroutine\Redis();
 	    $redis->connect($host,$port);
	    if ( $password ){
                $redis->auth($password);
		$redis->select($db);
		return $redis;
            } 
	}else{
            $key = md5(implode(":",$config));
            if (!isset(self::$_instance[$key])) {
                self::$_instance[$key] = new \Redis();
                self::$_instance[$key]->connect($host,$port);
                if ( $password ){
                    self::$_instance[$key]->auth($password);
                }
                self::$_instance[$key]->select($db);
            }
            return self::$_instance[$key];
	}
    }



}
