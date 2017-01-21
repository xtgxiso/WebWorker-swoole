<?php
namespace WebWorker\Libs;

class CoroutineMysql{

    private $db = false;
    private $config = false;

    public $affected_rows = 0;
    public $insert_id = 0;

    public function __construct($config=array()){
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
        $this->db = new \Swoole\Coroutine\MySQL();
        $this->db->connect(array("host"=>$host,"user"=>$user,"password"=>$password,"database"=>$database,"port"=>$port,"timeout"=>3,"c
harset"=>$charset));
        if ( $this->db->connect_error ){
            trigger_error('Connect Error: ' . $this->db->connect_error,E_USER_ERROR);
        }
    }

    public function query($sql){
	$res = $this->db->query($sql);
	if ( $res ){
	    $sql_info = explode(" ",ltrim($sql),2);
	    $type = strtolower($sql_info[0]);
	    if ( $type == "select" ){
	        //$res = $res->fetch_all(MYSQLI_ASSOC);
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
	    if ( !$this->db->connected ){
	        $this->connect();
		return $this->query($sql); 
	    }
	    return false;
	}
    }

    
}
