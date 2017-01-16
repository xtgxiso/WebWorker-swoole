<?php
namespace WebWorker\Libs;

class Controller{
   
    public $request = false;
    public $response = false; 

    function __construct($request,$response) {
        $this->request = $request;
	$this->response = $response;
    }

    public function  ServerJson($data){
        $this->response->header('Content-Type', 'application/json');
        $this->response->end(json_encode($data));
    } 


    public function  ServerHtml($data){
        $this->response->header("Content-Type", "text/html; charset=utf-8");
        $this->response->end($data);
    }

}
