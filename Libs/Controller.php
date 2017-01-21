<?php
namespace WebWorker\Libs;

class Controller{
   
    public $request = false;
    public $response = false; 

    private $redis = false;
    private $db = false;

    function __construct($request,$response) {
        global $config;
        $this->request = $request;
	$this->response = $response;
        if ( $config["redis"]["load"] ) {
            $this->LoadRedis();
        }
	if ( $config["db"]["load"] ){
	    $this->LoadDb();
	}
    }

    private function LoadRedis(){
        global $config;
	if ( $config["redis"]["coroutine"] ){
            if (count($config["redis"]["coroutine_pool"]) == 0) {
                $redis =  \WebWorker\Libs\Mredis::getInstance($config['redis']);
                $config["redis"]["coroutine_pool"]->push($redis);
                $config["redis"]["coroutine_count"]++;
            }
            $this->redis =  $config["redis"]["coroutine_pool"]->pop();
	}else{
	    $this->redis = \WebWorker\Libs\Mredis::getInstance($config['redis']); 
	}
    }

    private function LoadDb(){
	global $config;
	if ( $config["db"]["coroutine"] ){
            if (count($config["db"]["coroutine_pool"]) == 0) {
                $db =  \WebWorker\Libs\Mmysqli::getInstance($config["db"]);
                $config["db"]["coroutine_pool"]->push($db);
                $config["db"]["coroutine_count"]++;
            }
            $this->db =  $config["db"]["coroutine_pool"]->pop();
        }else{
            $this->db = \WebWorker\Libs\Mmysqli::getInstance($config['db']); 
        }        
    }

    public function  ServerJson($data){
        $this->response->header('Content-Type', 'application/json');
        $this->response->end(json_encode($data));
    } 


    public function  ServerHtml($data){
        $this->response->header("Content-Type", "text/html; charset=utf-8");
        $this->response->end($data);
    }
    
    public function __destruct() {
        global $config;
        if ( $this->redis && isset($config["redis"]["coroutine_pool"]) ){
	    if ( is_a($config["redis"]["coroutine_pool"],'SplQueue') ){    
                $config["redis"]["coroutine_pool"]->push($this->redis);
	    }
        }
	global $config;
        if ( $this->db && isset($config["db"]["coroutine_pool"]) ){
            if ( is_a($config["db"]["coroutine_pool"],'SplQueue') ){
                $config["db"]["coroutine_pool"]->push($this->db);
            }
        }
    }


}
