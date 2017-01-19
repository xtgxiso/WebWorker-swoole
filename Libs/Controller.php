<?php
namespace WebWorker\Libs;

class Controller{
   
    public $request = false;
    public $response = false; 

    private $redis = false;

    function __construct($request,$response) {
        global $config;
        $this->request = $request;
	$this->response = $response;
        if ( $config["redis"]["load"] ) {
            $this->LoadRedis();
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
        if ( $this->redis && $config["redis"]["coroutine_pool"] ){
            $config["redis"]["coroutine_pool"]->push($this->redis);
        }
    }


}
