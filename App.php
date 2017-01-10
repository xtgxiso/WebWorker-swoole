<?php
namespace WebWorker;

if ( extension_loaded("swoole") ){
    if ( version_compare(SWOOLE_VERSION,'2.0.5') ){
        exit("swoole扩展版本必须大于等于2.0.5\n");
    }
}else{
    exit("必须安装swoole扩展\n");
}

function autoload_dir($dir_arr){
    extract($GLOBALS);
    foreach($dir_arr as $dir ){
        foreach(glob($dir.'*.php') as $start_file)
        {
            require_once $start_file;
        }
    }
}

class App
{

    const VERSION = '0.1.0';

    private $map = array();

    public  $autoload = array();
    public  $on404 ="";
    public  $name = "none";    
    public  $count = 0;

    private $_startFile= '';
    private $pidFile = '';
    private $logFile = '';
    private $set = array('daemonize'=>false);
    private $http_server = false;
    
    private $request = false;
    private $response = false;

    public function __construct($ip='0.0.0.0', $port=1215){
        $this->init();
        $this->parseCommand();
        $this->http_server = new \swoole_http_server($ip,$port);
	$this->set['log_file'] = $this->logFile;
        $this->http_server->set($this->set);
        $this->http_server->on('start', array($this, 'onMasterStart'));
	$this->http_server->on('shutdown', array($this, 'onShutdown'));
        $this->http_server->on('workerstart', array($this, 'onWorkerStart'));
        $this->http_server->on('request', array($this, 'onClientMessage'));
    }

    protected function init()
    {
        // Start file.
        $backtrace        = debug_backtrace();
        $this->_startFile = $backtrace[count($backtrace) - 1]['file'];
        // Pid file.
        if (empty($this->pidFile)) {
            $this->pidFile = getcwd() . "/" . str_replace('/', '_', $this->_startFile) . ".pid";
        }
        // Log file.
        if (empty($this->logFile)) {
            $this->logFile = getcwd() . '/WebWorker.log';
        }
        $log_file = (string)$this->logFile;
        touch($log_file);
        chmod($log_file, 0622);
    }

    protected function parseCommand(){
        global $argv;
        $start_file = $argv[0];
        if (!isset($argv[1])) {
            exit("Usage: php yourfile.php {start|stop|restart|reload}\n");
        }
        $command1  = trim($argv[1]);
        $command2 = isset($argv[2]) ? $argv[2] : '';
        $mode = '';
        if ( $command1 === 'start' ) {
            if ( $command2 === '-d' ) {
                $mode = 'in DAEMON mode';
            } else {
                $mode = 'in DEBUG mode';
            }
        }
        // Get master process PID.
        $master_pid      = @file_get_contents($this->pidFile);
        $master_is_alive = $master_pid && @posix_kill($master_pid, 0);
        if ($master_is_alive) {
            if ( $command1 === 'start' ) {
                $this->log("WebWorker [$start_file] already running");
                exit;
            }
        } elseif ($command1 !== 'start' && $command1 !== 'restart') {
            $this->log("WebWorker [$start_file] not run");
            exit;
        }
        switch ( $command1 ) {
            case 'start':
                if ($command2 === '-d') {
                    $this->set['daemonize'] = true;
                }
		$this->log("WebWorker [$start_file] $command1 $mode");
                break;
            case 'restart':
            case 'stop':
                $this->log("WebWorker [$start_file] is stoping ...");
                $master_pid && posix_kill($master_pid,SIGTERM);
                $timeout    = 5;
                $start_time = time();
                // Check master process is still alive?
                while (1) {
                    $master_is_alive = $master_pid && posix_kill($master_pid, 0);
                    if ($master_is_alive) {
                        // Timeout?
                        if (time() - $start_time >= $timeout) {
                            $this->log("WebWorker [$start_file] stop fail");
                            exit;
                        }
                        // Waiting amoment.
                        usleep(10000);
                        continue;
                    }
                    // Stop success.
                    $this->log("WebWorker [$start_file] stop success");
                    if ($command1 === 'stop') {
                        exit(0);
                    }
                    $this->set['daemonize'] = true;
                    break;
                }
                break;
            case 'reload':
                posix_kill($master_pid, SIGUSR1);
                self::log("WebWorker [$start_file] reload");
                exit;
            default :
                exit("Usage: php yourfile.php {start|stop|restart|reload|status}\n");
        }
    }

    public function onMasterStart($serv){
        if (false === @file_put_contents($this->pidFile,$serv->master_pid )) {
            throw new Exception('can not save pid to ' . $this->pidFile);
        }
	swoole_set_process_name("Webworker: master process ".$this->name. " start_file=".$this->_startFile);	
    }

    public function onShutdown($serv){
	unlink($this->pidFile);
    }

    public function onWorkerStart($serv){
        autoload_dir($this->autoload);
    }

    protected function log($msg){
        $msg = $msg . "\n";
	if ( isset($this->set['daemonize']) && !$this->set['daemonize'] ){
        	echo($msg);
	}
    }

    public function HandleFunc($url,callable $callback){
        if ( $url != "/" ){
            $url = strtolower(trim($url,"/"));
        }
        if ( is_callable($callback) ){
            if ( $callback instanceof \Closure ){
                $callback = \Closure::bind($callback, $this, get_class());
            }
        }else{
            throw new \Exception('can not HandleFunc');
        }
        $this->map[] = array($url,$callback,1);
    }

    public function AddFunc($url,callable $callback){
        if ( $url != "/" ){
            $url = strtolower(trim($url,"/"));
        }
        if ( is_callable($callback) ){
            if ( $callback instanceof \Closure ){
                $callback = \Closure::bind($callback, $this, get_class());
            }
        }else{
            throw new \Exception('can not HandleFunc');
        }
        $this->map[] = array($url,$callback,2);
    }

    private function show_404(){
        if ( $this->on404 ){
            call_user_func($this->on404);
        }else{
            $this->response->status(404);
            $html = '<html>
                <head><title>404 Not Found</title></head>
                <body bgcolor="white">
                <center><h1>404 Not Found</h1></center>
                <hr><center>App</center>
                </body>
                </html>';
            $this->response->end($html);
        }
    }

    public function onClientMessage($request, $response){
        if ( empty($this->map) ){
            $str = <<<'EOD'
<div style="margin: 200px auto;width:600px;height:800px;text-align:left;">基于<a href="http://www.swoole.com/" target="_blank">Swoole</a>实现的自带http server的web开发框架.没有添加路由，请添加路由!
<pre>$app->HandleFunc("/",function($conn,$data) use($app){
    $conn->send("默认页");
});</pre>
</div>
EOD;
            $this->ServerHtml($str);
            return;
        }
        $this->request = $request;
        $this->response = $response;
        $url= $request->server["request_uri"];
        $pos = stripos($url,"?");
        if ($pos != false) {
            $url = substr($url,0,$pos);
        }
        if ( $url != "/"){
            $url = strtolower(trim($url,"/"));
        }
        $url_arr = explode("/",$url);
        $class = empty($url_arr[0]) ? "_default" : $url_arr[0];
        $method = empty($url_arr[1]) ? "_default" : $url_arr[1];
        $success = false;
        foreach($this->map as $route){
            if ( $route[2] == 1){//正常路由
                if ( $route[0] == $url ){
                    $callback[] = $route[1];
                }
            }else if ( $route[2] == 2 ){//中间件
                if ( $route[0] == "/" ){
                    $callback[] = $route[1];
                }else if ( stripos($url,$route[0]) === 0 ){
                    $callback[] = $route[1];
                }
            }
        }
        if ( isset($callback) ){
            try {
                foreach($callback as $cl){
                    if ( call_user_func($cl) === true){
                        break;
                    }
                }
            }catch (\Exception $e) {
                // Jump_exit?
                if ($e->getMessage() != 'jump_exit') {
                    echo $e;
                }
                $code = $e->getCode() ? $e->getCode() : 500;
            }
        }else{
            $this->show_404();
            $code = 404;
            $msg = "class $class not found";
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

    public function run(){
        $this->http_server->start();
    }

}
