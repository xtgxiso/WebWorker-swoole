<?php
namespace WebWorker\Libs;

class Mmysqli{

    private $db = false;
    private $config = false;

    public $affected_rows = 0;
    public $insert_id = 0;

    /**
     * 静态成品变量 保存全局实例
     */
    private static  $_instance = array();

    /**
     * 静态工厂方法，返还此类的唯一实例
     */
    public static function getInstance($config=array()) {
        if ( $config['coroutine'] ){
	    return new \WebWorker\Libs\CoroutineMysql($config);
        }else{
	    unset($config["coroutine_pool"]);
            $key = md5(implode(":",$config));
	    if (!isset(self::$_instance[$key])) {
		$c = __CLASS__;
                self::$_instance[$key] = new $c($config);
            }
            return self::$_instance[$key];
	}    
    }

    private function __construct($config=array()){
	$this->config = $config;
	$this->connect();	
    }

    public function connect(){
	$config = $this->config;
	$host = isset($config["host"]) ? $config["host"] : "127.0.0.1";
        $user = isset($config["user"]) ? $config["user"] : "root";
        $password = isset($config["password"]) ? $config["password"] : "123456";
        $database = isset($config["database"]) ? $config["database"] : "test";
        $port = isset($config["port"]) ? $config["port"] : "3306";
        $charset = isset($config["charset"]) ? $config["charset"] : "utf8";
        $this->db = new \mysqli($host,$user,$password,$database,$port);
        if ( $this->db->connect_error ){
            trigger_error('Connect Error: ' . $this->db->connect_error,E_USER_ERROR);
        }else{
            if (!$this->db->set_charset($charset)) {
                trigger_error("Error loading character set: $charset",E_USER_ERROR);
            }
        }
    }

    public function query($sql){
	$res = $this->db->query($sql);
	if ( $res ){
	    $sql_info = explode(" ",ltrim($sql),2);
	    $type = strtolower($sql_info[0]);
	    if ( $type == "select" ){
	        $res = $res->fetch_all(MYSQLI_ASSOC);
	    }else if ( $type == "insert" ){
		$this->insert_id = $this->db->insert_id;
		$this->affected_rows = $this->db->affected_rows;
	    }else if ( $type == "update" ){
		$this->affected_rows = $this->db->affected_rows;
	    }else if ( $type == "delete" ){
		$this->affected_rows = $this->db->affected_rows;
	    }
	    return $res;
	}else{
	    if ( $this->db->ping() ){
	        return false;
	    }else{
		$this->connect();
		return $this->query($sql);
            }
	}
    }

    
}
