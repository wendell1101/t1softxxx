<?php

// if(file_exists(dirname(__FILE__). '/application/libraries/vendor/autoload.php')){
//     require_once(dirname(__FILE__). '/application/libraries/vendor/autoload.php');
// }else{
require_once(dirname(__FILE__). '/vendor/autoload.php');
// }

class Cli {

	private $climate;
	private $config;

	const WHITE_FUNCTION_LIST=[
		'switch_google_k8s',
		'run_migrate',
		'create_links',
		'temp_deploy_core',
		'temp_deploy_payment',
		'temp_deploy_game',
		'fetch_fpm_status',
		'validate_php_opening_tag',
		'record_log',
		'get_top_game_launcher_logs',
		'search_uri_log_timeouts'
	];

    private function readConfig() {
        if (!empty($this->config)) {
            return $this->config;
        }
        // Is the config file in the environment folder?
        $file_path = dirname(__FILE__) . '/config/config.php';
        $config=[];
        // Fetch the config file
        if (file_exists($file_path)) {
	        require $file_path;
        }
        // Does the $config array exist in the file?
        if (!isset($config) OR !is_array($config)) {
            $this->error_log('Your config file does not appear to be formatted correctly.');
        }
		$this->config = $config;
		return $this->config;
	}

	public function __construct() {

		error_reporting(E_ALL);

		$this->climate = new League\CLImate\CLImate;
		// parent::__construct();
		$this->readConfig();
		$this->debug_log('print config', $this->config);

		$cmd_desc='available command: '.implode(self::WHITE_FUNCTION_LIST, ',');

		$params=[
			'command'=>[
				'description'=>$cmd_desc,
				'required'=>true,
			],
			'debug'=>[
				'description'=>'debug mode to print more log',
				'prefix'=>'D',
				'longPrefix'=>'debug',
				'noValue' => true,
			],
			'branch'=>[
				'description'=>'branch name',
				'prefix'=>'b',
				'longPrefix'=>'branch',
			],
			'google_account'=>[
				'description'=>'google account',
				'prefix'=>'a',
				'longPrefix'=>'google_account',
			],
			'google_project_name'=>[
				'description'=>'google project name: externalgateway-167009 or ibetg-164502',
				'prefix'=>'p',
				'longPrefix'=>'google_project_name',
			],
			'google_zone'=>[
				'description'=>'google zone, for example: asia-east1-a',
				'prefix'=>'z',
				'longPrefix'=>'google_zone',
			],
			'google_k8s_name'=>[
				'description'=>'google k8s name , for example: t1tog',
				'prefix'=>'k',
				'longPrefix'=>'google_k8s_name',
			],
			'commit_sha_id'=>[
				'description'=>'commit sha id',
				'prefix'=>'s',
				'longPrefix'=>'commit_sha_id',
			],
			'client_name'=>[
				'description'=>'client name',
				'prefix'=>'c',
				'longPrefix'=>'client_name',
			],
			'php_socket_path'=>[
				'description'=>'socket of php path',
				'prefix'=>'P',
				'longPrefix'=>'php_socket_path',
			],
			'redis_host'=>[
				'description'=>'redis host',
				'prefix'=>'H',
				'longPrefix'=>'redis_host',
			],
			'redis_port'=>[
				'description'=>'redis port',
				'prefix'=>'O',
				'longPrefix'=>'redis_port',
			],
			'redis_retry_timeout'=>[
				'description'=>'redis retry timeout',
				'prefix'=>'R',
				'longPrefix'=>'redis_retry_timeout',
			],
			'log_by_request_id'=>[
				'description'=>'use request_id as log dir',
				'prefix'=>'L',
				'longPrefix'=>'log_by_request_id',
				'noValue' => true,
			],
			'keep_old_logfile'=>[
				'description'=>'keep old log json files',
				'prefix'=>'K',
				'longPrefix'=>'keep_old_logfile',
				'noValue' => true,
			],
			'game_launcher_top_log_count'=>[
				'description'=>'game launcher top count eg. top 50',
				'prefix'=>'t',
				'longPrefix'=>'game_launcher_top_log_count',
				'defaultValue' => 50,
			],
			'target_logfile'=>[
				'description'=>'target logfile',
				'prefix'=>'f',
				'longPrefix'=>'target_logfile',
			],
			'target_uri'=>[
				'description'=>'target uri eg. gamegateway/transfer_player_fund ',
				'prefix'=>'u',
				'longPrefix'=>'target_uri',
			],
			'elapsed_seconds_time'=>[
				'description'=>'time elapsed in seconds',
				'prefix'=>'e',
				'longPrefix'=>'elapsed_seconds_time',
				'defaultValue' => 20,
			],
			'show_data'=>[
				'description'=>'show data or json data when searching timeouts',
				'prefix'=>'d',
				'longPrefix'=>'show_data',
				'noValue' => true,
			],
			'last_hour'=>[
				'description'=>'last hour to search ex. 1,2,3',
				'prefix'=>'h',
				'longPrefix'=>'last_hour',
			],
			'last_minute'=>[
				'description'=>'last minute to search ex. 1,2,3',
				'prefix'=>'m',
				'longPrefix'=>'last_minute',

			],
			'send_to_mm'=>[
				'description'=>'sends to mattermost',
				'prefix'=>'mm',
				'longPrefix'=>'send_to_mm',
				'noValue' => true,
			],
			'mm_channel'=>[
				'description'=>'mattermost channel',
				'prefix'=>'mc',
				'longPrefix'=>'mm_channel',
			],
			'target_monitor_logfile'=>[
				'description'=>'target monitor logfile',
				'prefix'=>'tml',
				'longPrefix'=>'target_monitor_logfile',
			],
			'write_to_log'=>[
				'description'=>'write to log',
				'prefix'=>'w',
				'longPrefix'=>'write_to_log',
				'noValue' => true,
			],
			'limit'=>[
				'description'=>'limit ex. 50',
				'prefix'=>'l',
				'longPrefix'=>'limit',
				'defaultValue' => 50,
			]
		];
		$this->init_params=['google_project_name', 'client_name', 'branch', 'commit_sha_id',
			'google_account', 'google_zone', 'google_k8s_name',
			'php_socket_path', 'redis_host', 'redis_port', 'redis_retry_timeout', 'log_by_request_id',
			'keep_old_logfile','game_launcher_top_log_count','target_logfile','target_uri',
			'elapsed_seconds_time','show_data','last_hour','last_minute','send_to_mm','mm_channel',
			'target_monitor_logfile','write_to_log','limit'];

		$this->climate->arguments->add($params);

		//init project path
		$this->project_home=realpath(dirname(__FILE__));

		$this->climate->red('running on '.$this->project_home);

	}

	public function run(){
		$success=false;

		try{
			$this->climate->arguments->parse();
		}catch(Exception $e){
			if($e->getMessage()!='The following arguments are required: [command].'){
				$this->error_log('parse arguments error', $e);
			}
			return $this->climate->usage();
		}

		$command=$this->climate->arguments->get('command');

		if(empty($command)){
			return;
		}

		$this->is_debug=$this->climate->arguments->defined('debug');

		$this->setClassVarFromArg($this->init_params);

		// $this->climate->draw('bender');
		// $this->climate->animation('bender')->enterFrom('top');

		$this->climate->border('=');

		//check white list
		if(in_array($command, self::WHITE_FUNCTION_LIST)){

			$t1=new DateTime();
			$this->climate->flank('start '.date('Y-m-d H:i:s'));
			$success=false;
			try{
				$success=$this->$command();
			}catch(Exception $e){
				$success=false;
				$this->error_log('run command failed', $e);
			}
			$this->climate->flank('end '.date('Y-m-d H:i:s').' cost: '.$t1->diff(new DateTime())->format('%H:%I:%S'));

			if(!$success){
				$this->error_log('run command:'.$command.' failed');
			}
		}else{

			$this->climate->backgroundRed('wrong command name');

			$this->climate->usage();

		}

		$this->climate->border('=');

		return $success;

	}

	private function switchToBranch($path){

		$this->branch=$this->climate->arguments->get('branch');

		$success=false;
		if(!empty($this->branch)){

			$script=<<<EOD
git -C {$path} checkout {$this->branch}
git -C {$path} branch
EOD;
			//try run
			$success=$this->passthruBool($script);

		}else{
			$this->showWarning('no branch name, do nothing');
		}

		return $success;
	}

	const MAIN_BRANCH_LIST=['live_stable_prod', 'live_stable_rc', 'live_stable'];

	public function switch_google_k8s(){
		$this->info_log('switch_google_project');
		$this->climate->border();

		if(empty($this->google_account)){
			$this->error_log('google_account is empty');
			$this->climate->usage();
			return false;
		}

		if(empty($this->google_project_name)){
			$this->error_log('google_project_name is empty');
			$this->climate->usage();
			return false;
		}

		if(empty($this->google_zone)){
			$this->error_log('google_zone is empty');
			$this->climate->usage();
			return false;
		}

		if(empty($this->google_k8s_name)){
			$this->error_log('google_k8s_name is empty');
			$this->climate->usage();
			return false;
		}

		$script=<<<EOD
gcloud config set account {$this->google_account}
gcloud container clusters get-credentials {$this->google_k8s_name} --zone {$this->google_zone} --project {$this->google_project_name}
EOD;
		$success=$this->passthruBool($script);

		$this->climate->border();

		return $success;
	}

	public function run_migrate(){
		//kubectl get pods | grep -v "\-sync\-" | grep $CLIENT_NAME-og- | tail -n 1 | awk '{print "kubectl exec -it "$1" -- su - vagrant -c \"cd /home/vagrant/Code/og; ./migrate.sh \""}'  | sh

		if(empty($this->client_name)){
			$this->error_log('client_name is empty');
			$this->climate->usage();
			return false;
		}

		$success=$this->switch_google_k8s();
		$found=false;
		$clientPodName=null;
		if($success){

			$clientPodName=$this->searchPod($this->client_name.'-og-');
			$found=!empty($clientPodName);

			if($found){
				//run migrate
				$this->debug_log('run migrate');
				// $script='kubectl exec -it '.$clientPodName.' -- su - vagrant -c "cd /home/vagrant/Code/og; ./migrate.sh "';
				$success=$this->runMigrateOnPod($clientPodName);

				if(!$success){
					$this->error_log('run migrate failed');
				}
				// $success=true;
			}
		}


		return $success;

	}

    public function create_links(){
		//kubectl get pods | grep -v "\-sync\-" | grep $CLIENT_NAME-og- | tail -n 1 | awk '{print "kubectl exec -it "$1" -- su - vagrant -c \"cd /home/vagrant/Code/og; ./migrate.sh \""}'  | sh

		if(empty($this->client_name)){
			$this->error_log('client_name is empty');
			$this->climate->usage();
			return false;
		}

		$success=$this->switch_google_k8s();
		$found=false;
		$clientPodName=null;
		if($success){

            $podList=$this->searchPod($this->client_name.'-og-', true);

            if(!empty($podList)){

                foreach ($podList as $podName) {				//run migrate
                    $this->debug_log('run create_links');
                    $script='kubectl exec -it '.$podName.' -- su - vagrant -c "cd /home/vagrant/Code/og; ./create_links.sh "';
                    $succ=$this->passthruBool($script);

                    if(!$succ){
                        $this->error_log('run create_links failed');
                    }
                }
			}
		}


		return $success;

	}

	/**
	 * temp_deploy_core
	 * only temp, because it will be overwritten
	 * @return bool
	 */
	public function temp_deploy_core(){

		if(empty($this->client_name)){
			$this->error_log('client_name is empty');
			$this->climate->usage();
			return false;
		}

		$success=$this->switch_google_k8s();
		if(!$success){
			$this->error_log('switch_google_k8s failed');
			return $success;
		}

		$this->climate->border();

		$sourceDir=$this->project_home;
		$lastDirName='core-lib';
		$targetDir="/home/vagrant/Code/og/submodules/core-lib/";
		$preprocessScript=" rm -rf ./$lastDirName/.git* ";

		$success=$this->tempDeployDir($sourceDir, $lastDirName, $targetDir, $preprocessScript);

		$this->climate->border();

		return $success;
	}

    /**
	 * temp_deploy_payment
	 * only temp, because it will be overwritten
	 * @return bool
	 */
	public function temp_deploy_payment(){

		if(empty($this->client_name)){
			$this->error_log('client_name is empty');
			$this->climate->usage();
			return false;
		}

		$success=$this->switch_google_k8s();
		if(!$success){
			$this->error_log('switch_google_k8s failed');
			return $success;
		}

		$this->climate->border();

		$sourceDir=$this->project_home;
		$lastDirName='payment-lib';
		$targetDir="/home/vagrant/Code/og/submodules/payment-lib/";
		$preprocessScript=" rm -rf ./$lastDirName/.git* && rm -f ./$lastDirName/vendor ";

		$success=$this->tempDeployDir($sourceDir, $lastDirName, $targetDir, $preprocessScript);

		$this->climate->border();

		return $success;
    }

	public function temp_deploy_game(){

		if(empty($this->client_name)){
			$this->error_log('client_name is empty');
			$this->climate->usage();
			return false;
		}

		$success=$this->switch_google_k8s();
		if(!$success){
			$this->error_log('switch_google_k8s failed');
			return $success;
		}

		$this->climate->border();
		$sourceDir=$this->project_home;
		$lastDirName='game-lib';
		$targetDir="/home/vagrant/Code/og/submodules/game-lib/";
		$preprocessScript=" rm -rf ./$lastDirName/.git* && rm -f ./$lastDirName/vendor ";

		$success=$this->tempDeployDir($sourceDir, $lastDirName, $targetDir, $preprocessScript);

		$this->climate->border();

		return $success;
	}

	public function fetch_fpm_status(){
		$success=false;

		if(empty($this->php_socket_path)){
			$this->error_log('php_socket_path is empty');
			$this->climate->usage();
			return false;
		}

		$this->info_log('try access php_socket_path', $this->php_socket_path);

		try{
			$fastcgi = new \Hoa\Fastcgi\Responder(
	    		new \Hoa\Socket\Client($this->php_socket_path)
			);
	        $response = $fastcgi->send([
	            'REQUEST_METHOD'  => 'GET',
	            'SCRIPT_NAME' => '/fpm-status',
	            'SCRIPT_FILENAME' => '/fpm-status',
	            'QUERY_STRING' => 'json',
	        ]);

			$headers = $fastcgi->getResponseHeaders();

	        $success=!empty($response);
	        $this->debug_log('get response', $response, $headers, $success);
	        if($success){
	        	$fpmStatusFields=[];
	        	$statusArr=json_decode($response, true);
	        	$keepFields=[
	        		'accepted conn'=>'accepted_conn', 'listen queue'=>'listen_queue',
	        		'max listen queue'=>'max_listen_queue', 'idle processes'=>'idle_processes',
	        		'active processes'=>'active_processes', 'total processes'=>'total_processes',
	        		'max active processes'=>'max_active_processes', 'max children reached'=>'max_children_reached',
	        		'slow requests'=>'slow_requests', 'start time'=>'start_time',
	        		'start since'=>'start_since', 'process manager'=>'process_manager',
	        		'pool'=>'pool'];

	        	foreach ($statusArr as $key => $value) {
	        		if(array_key_exists($key, $keepFields)){
	        			$fpmStatusFields[$keepFields[$key]]=$value;
	        		}
	        	}
	        	$this->info_log('get php status', $statusArr, $fpmStatusFields);
	        }

		}catch(Exception $e){
			$this->error_log('send failed', $e);
		}

		return $success;
	}

	public function validate_php_opening_tag(){

    	//$str = shell_exec('find -name "*.php" -type f | egrep -v "/vendor/" ');
		$str=$this->execCmd('find -name "*.php" -type f | egrep -v "/vendor/" ',false, true, true);
		$php_files = explode(PHP_EOL, $str[0]);
		$error_files = [];
		$success = false;
		$scanned_files_cnt = count($php_files);
		$error_files_cnt = 0;
		$existed_files_cnt = 0;

		foreach ($php_files as $file) {

			if(file_exists($file)){
				$existed_files_cnt++;
    			//$output = shell_exec('head -n 5 '. $file);
				$output=$this->execCmd('head -n 5 '. $file,false, true, true);
				$lines = explode(PHP_EOL, $output[0]);
				$line_cnt = 0;
				$regex  = "/<\?php/";
				$is_php_tag_found = false;

				foreach ($lines as $line) {
					if(preg_match($regex, $line, $match)){
						$is_php_tag_found = true;
						if($line_cnt > 0 || stripos($line, "<?php") > 0 ){
							array_push($error_files, $file);
						}
					}
				$line_cnt++;
				}
				if($is_php_tag_found === false){
					array_push($error_files, $file);
				}
			}
		}
		if(!empty($error_files)){
			$error_files_cnt = count($error_files);
			$this->error_log('php opening tag error found on the following files',$error_files);
		}else{
			$success = true;
		}

		$this->info_log('result:','scanned_files_cnt: '.$scanned_files_cnt. ' existed_files_cnt: '.$existed_files_cnt. ' error_files_cnt: '.$error_files_cnt);

		return $success;

	}

	public function record_log(){

		// require 'log_config.php';
		// if (!isset($config) OR !is_array($config)) {
		// 	exit('Your config file does not appear to be formatted correctly.');
		// }

		if(empty($this->redis_host)){
			$this->error_log('empty redis host');
			return false;
		}
		if(empty($this->redis_port)){
			$this->error_log('empty redis port');
			return false;
		}
		// $this->debug_log('config file');

		$host=$this->redis_host;
		$port=$this->redis_port;
		$timeout=0; //$config['redis-server']['timeout'];
		$retry_timeout=empty($this->redis_retry_timeout) ? 5 : $this->redis_retry_timeout;

		$redisClient=new Redis();
		$rlt=$redisClient->connect($host, $port, $timeout, null, $retry_timeout);

		//$redisClient=new Predis\Client(['scheme' => 'tcp',
		//'host'   => $host,
		//'port'   => $port,
		//]);

		//$rlt=$redisClient->connect($host, $port, $timeout, null, $retry_timeout);

		if($rlt){

			$this->debug_log("start", $redisClient->ping());

			$patterns=["*_og", "*_og_sync", "*_og_staging", "*_og_shadow", "*_og_livebak", "*_sbe"];
			$log_dir=dirname(__FILE__).'/../logs/';

			$this->debug_log('write to log dir', $log_dir);
			if(!file_exists($log_dir)){
				@mkdir($log_dir, 0777, true);
			}
			while(true){
				try{
					$redisClient->pSubscribe($patterns, function($redis, $pattern, $channel, $message)
						use($log_dir){
						//raw_debug_log($pattern, $channel, $message);
						if(!empty($message)){
							$json=json_decode($message, true);
							if(!empty($json)){
								if($this->keep_old_logfile){
									$oldLogFile=$log_dir.$channel.'-json.log';
									$this->write_to_logfile($oldLogFile, $message);
								}
								//write down to different dir
								//get request id
								if(isset($json['extra']['tags']['request_id'])){
									$requestId=$json['extra']['tags']['request_id'];
									if($this->log_by_request_id){
										$reqLogDir=$log_dir.$channel.'/'.date("Ymd").'/'.date("Hi");
										if(!is_dir($reqLogDir)){
											mkdir($reqLogDir, 0777, true);
										}
										$logFile=$reqLogDir.'/'.$requestId.'.json';
										$this->write_to_logfile($logFile, $message);
									}
									if($json['level']==400){
										//still keep error file in log dir
										$errorlogFile=$log_dir.$channel.'-json-error.log';
										$this->write_to_logfile($errorlogFile, $message);
									}

								}else{
									//write wrong format json, no request_id
									$logFile=$log_dir.$channel.'-json-error.log';
									$this->write_to_logfile($logFile, $message);
								}
							}else{
								//write wrong format json
								$logFile=$log_dir.$channel.'-json-error.log';
								$this->write_to_logfile($logFile, $message);
							}
							unset($json);
							// $this->debug_log('write to log file', $logFile);
						}
					});
				}catch(Exception $e){
					if($e instanceof RedisException && $e->getMessage()=='read error on connection'){
						// $this->debug_log('ignore redis exception');
					}else{
						$this->error_log('get exception', $e);
					}
				}
				sleep(1);
			}
			$redisClient->close();

			$this->debug_log("end");

		}else{
			$this->error_log('connect redis failed');
		}
	}

	public function get_top_game_launcher_logs(){

		$success=true;
		$log_dir=dirname(__FILE__).'/../logs/';
		$logFile=null;

		if(empty($this->target_logfile)){
			$this->climate->usage();
			$success = false;
			return $success;
		}

		if(file_exists($log_dir.$this->target_logfile)){
			$logFile=$log_dir.$this->target_logfile;
		}else{
			$this->error_log('logFile not found');
			$success = false;
			return $success;
		}

	$cmd=<<<EOD

grep -ioh '{"message":"gamegateway/query_game_launcher.*' {$logFile} | uniq

EOD;
		$this->debug_log('command: '.$cmd);
		$this->debug_log('searching...');
		//$str = shell_exec($cmd);
		$str = $this->execCmd($cmd);
        //$json_str = explode(PHP_EOL, $str);
		$json_str = null;
		if(!isset($str[0])){
			$this->info_log('no results found');
			return $success;
		}else{
			$json_str = explode(PHP_EOL, $str[0]);
		}

		$output = [];
		$datetime_keys = [];
		$result_cnt = 0;

		foreach($json_str as $json){

			if(empty($json)){
				continue;
			}
			$json_arr = json_decode($json, true);

			$row =[];
			$row['datetime'] = null;
			$row['elapsed_time'] = isset($json_arr['context']['elapsed'])  ? $json_arr['context']['elapsed'] : '';
			$row['request_id'] = isset($json_arr['extra']['tags']['request_id'])  ? $json_arr['extra']['tags']['request_id'] : '';

			if(isset($json_arr['datetime'])){
				$regEx = '/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/';
				preg_match($regEx, $json_arr['datetime'], $time_parts);
				$row['datetime'] = $time_parts[0];
			}

			if(empty($row['datetime'])){
				continue;
			}
            //map
			$output[$row['elapsed_time'].'-'.$row['datetime']] = $row;
			array_push($datetime_keys, $row['elapsed_time'].'-'.$row['datetime']);

			$result_cnt++;
		}

        //sort datetime keys desc
		asort($datetime_keys);

		$index = 1;

		$this->info_log('TOP '.$this->game_launcher_top_log_count.' results');
		//for climate table
		$final_display = [];

		foreach ($datetime_keys as $key) {

			$row = $output[$key];
			$row = array_merge(array('index' => $index), $row);
			if($index > $this->game_launcher_top_log_count){
				break;
			}
			array_push($final_display, $row);
			$index++;
		}
		$this->climate->table($final_display);
		$this->info_log('TOP '.$this->game_launcher_top_log_count.' results');
		return $success;

	}

	public function search_uri_log_timeouts(){

		$success=false;
		$log_dir=dirname(__FILE__).'/../logs/';
		$logFile=null;
		$from=null;
		$to=null;
		$last_hour = null;
		$last_minute =null;

		if(empty($this->target_logfile)){
			$this->climate->usage();
			return $success;
		}
		if(empty($this->target_uri)){
			$this->climate->usage();
			return $success;
		}
		if(empty($this->mm_channel)){
			$this->climate->usage();
			return $success;
		}
		if(empty($this->target_monitor_logfile)){
			$this->climate->usage();
			return $success;
		}
		if(empty($this->elapsed_seconds_time)){
			$this->climate->usage();
			return $success;
		}
		if(file_exists($log_dir.$this->target_logfile)){
			$logFile=$log_dir.$this->target_logfile;
		}else{
			$this->error_log('logFile not found');
			return $success;
		}

		$uri=$this->target_uri;

		if(!empty($this->last_hour)){
			$last_hour = $this->last_hour;
			$from = date('Y-m-d H:', strtotime('-'.$last_hour.' hours')).'00:00';
			$to = date('Y-m-d H:').'00:00';
		}

		if(!empty($this->last_minute)){
			$last_minute = $this->last_minute;
			$from = date('Y-m-d H:i:', strtotime('-'.$last_minute.' minutes')).'00';
			$to = date('Y-m-d H:i:').'00';
		}

    	$this->debug_log('from: '.$from.' to: '.$to);

  $cmd=<<<EOD

grep -ioh '{"message":"{$uri}.*' {$logFile} | uniq

EOD;
		$this->debug_log('command: '.$cmd);
		$this->debug_log('searching...');
		//$str = shell_exec($cmd);
		$str = $this->execCmd($cmd);
        //$json_str = explode(PHP_EOL, $str);
		$json_str = null;
		if(!isset($str[0])){
			$this->info_log('no results found');
			return $success;
		}else{
			$json_str = explode(PHP_EOL, $str[0]);
		}

		$output = [];
		$datetime_keys = [];
		$result_cnt = 0;

		foreach($json_str as $json){

			if(empty($json)){
				continue;
			}
			$json_arr = json_decode($json, true);

			$row =[];
			$row['datetime'] = null;
			$row['elapsed_time'] = isset($json_arr['context']['elapsed'])  ? $json_arr['context']['elapsed'] : '';
			$row['request_id'] = isset($json_arr['extra']['tags']['request_id'])  ? $json_arr['extra']['tags']['request_id'] : '';

			if(isset($json_arr['datetime'])){
				$regEx = '/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/';
				preg_match($regEx, $json_arr['datetime'], $time_parts);
				$row['datetime'] = $time_parts[0];
			}

			if(empty($row['datetime'])){
				continue;
			}
			if($row['elapsed_time'] <= $this->elapsed_seconds_time){
				continue;
			}

			if(!empty($last_hour) || !empty($last_minute)){
				if($row['datetime'] < $from){
					continue;
				}
			}

			if($this->show_data){
				$row['json']=$json;
			}

            //map
			$output[$row['elapsed_time'].'-'.$row['datetime']] = $row;
			array_push($datetime_keys, $row['elapsed_time'].'-'.$row['datetime']);

			$result_cnt++;
		}

		//sort datetime keys desc
		asort($datetime_keys);

		$index = 1;

		//for climate table
		$final_display = [];

		foreach ($datetime_keys as $key) {

			$row = $output[$key];
			$row = array_merge(array('index' => $index), $row);
			if($index > $this->limit){
				break;
			}
			array_push($final_display, $row);
			$index++;
		}

		$total_found_cnt=0;

		if(!empty($final_display)){
			$this->climate->table($final_display);
			$total_found_cnt = count($final_display);
			foreach ($final_display as $row) {
				if($this->write_to_log){
					$monitor_target_uri_file_path=$log_dir.$this->target_monitor_logfile;
					$this->write_to_logfile($monitor_target_uri_file_path, $row['json']);
				}
			}
		}else{
			$this->info_log('no results found');
			$success = true;
		}

		if($this->send_to_mm && $total_found_cnt > 0){

			$texts_and_tags = [
				"#".__FUNCTION__.'_'.date('Y_m_d'),
				"#".gethostname()."\n",
			];

			$elapsed_seconds_time = $this->elapsed_seconds_time ;
			$target_logfile = $this->target_logfile;
			$target_monitor_logfile = $this->target_monitor_logfile;
			$time_covered = (!empty($last_hour) || !empty($last_minute)) ? 'Time covered: ['.$from.' to '.$to.']' : null;

  $msg=<<<EOD
{$time_covered}
Total Rows found: {$total_found_cnt}
Timeout search setting: {$elapsed_seconds_time} seconds
Logfile: {$target_logfile}
URI: {$uri}
Details appended on {$target_monitor_logfile} file
EOD;

			$channel = $this->mm_channel;
			$this->debug_log('mm channel: '.$channel);
			$this->sendNotificationToMattermost(__FUNCTION__, $channel,[['text'=>$msg,'type'=>'warning']],$texts_and_tags);

		}

		$success = true;
		$this->info_log('DEFAULT LIMIT RESULTS '.$this->limit);

		return $success;
	}


	private function sendNotificationToMattermost($user,$channel,$messages,$texts_and_tags=null) {


		$mm_channels = $this->getConfig('mattermost_channels');
		$channel_url = $mm_channels[$channel];

		$color = "";
		$color_map = ['info' => "#3498DB",'success' => "#58D68D",'warning' => "#F4D03F",
		'danger' => "#EC7063", 'default' =>"#3498DB"];

		$attachments = [];

		foreach ($messages as $message) {
			$default = array(
				'text' => "Please say something!",
				'color' => $color_map['info']
			);
			$message['color'] = $color_map[$message['type']];
			$attachment = array_merge($default,$message);
			array_push($attachments, $attachment);

		}

		if ( ! empty($channel_url)) {

			$payload = array( 'username'=> $user, 'attachments' => $attachments);

			if(!empty($texts_and_tags)){
				if(is_array($texts_and_tags)){
					$payload['text'] = implode(" ", $texts_and_tags);
				}else{
					$payload['text'] = $texts_and_tags;
				}
			}
			$data = array('payload' => json_encode($payload));

			$ch = curl_init($channel_url);

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$result = curl_exec($ch);

            //get error
			$errCode = curl_errno($ch);
			$error = curl_error($ch);
			$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if($errCode!=0 || $statusCode>=400){
				$this->error_log('error code', $errCode, $error, $statusCode, $result);
				$success=false;
			}else{
				$this->debug_log('return result', $errCode, $error, $statusCode, $result);
				$success=true;
			}

			curl_close($ch);
		}

		return $success;
	}



	private function write_to_logfile($log_file, $message){
		return error_log($message, 3, $log_file);
	}

	//===private function=================================================================
	private function tempDeployDir($sourceDir, $lastDirName, $targetDir, $preprocessScript=null){

		$this->info_log('search all pod');

		$success=false;

		$podList=$this->searchPod($this->client_name.'-og-', true);

		$this->debug_log('pod list', $podList);

		// $coreDir=$this->project_home;
		// $lastDirName='core-lib';
		// $coreTargetDir="/home/vagrant/Code/og/submodules/core-lib/";
		if(!empty($podList)){

			$finishMigration=false;
			foreach ($podList as $podName) {

				$this->debug_log('copy from '.$sourceDir.' to '.$targetDir.' of '.$podName);
                //try copy files to pod
				$success=$this->copyDirToPod($sourceDir, $podName, $targetDir, $lastDirName, $preprocessScript);
				if(!$success){
					$this->error_log('copy core file failed on ', $podName);
					break;
				}else{
					$this->debug_log('copy core file success');
                }

                //run create_links
				// $script='kubectl exec -it '.$podName.' -- su - vagrant -c "cd /home/vagrant/Code/og; ./create_links.sh "';
				$success=$this->runCreateLinksOnPod($podName);
                if(!$success){
                    $this->error_log('create_links failed ', $podName);
                    break;
                }else{
					$this->debug_log('create_links success');

					if(!$finishMigration){
						$success=$this->runMigrateOnPod($podName);
						$finishMigration=$success;
						if(!$success){
		                    $this->error_log('run migrate failed ', $podName);
							break;
						}
					}

                }
			}
		}

		$this->info_log('it will be overwritten when next deploy or restart');

		return $success;
	}

	private function execOnPod($cmd, $podName){
		$script='kubectl exec '.$podName.' '.$cmd;
		$success=$this->passthruBool($script);

		return $success;
	}

	private function runCreateLinksOnPod($podName){
		//'kubectl exec -it '.$podName.' -- su - vagrant -c "cd /home/vagrant/Code/og; ./create_links.sh "'
		return $this->execOnPod('-- su - vagrant -c "cd /home/vagrant/Code/og; ./create_links.sh "', $podName);
	}

	private function runMigrateOnPod($podName){
		return $this->execOnPod('-- su - vagrant -c "cd /home/vagrant/Code/og; ./migrate.sh "', $podName);
	}

	/**
	 *
	 * copyDirToPod
	 *
	 * @param string $dir     source
	 * @param string $podName target pod
	 * @param string $targetDir target dir
	 * @return bool
	 */
	private function copyDirToPod($dir, $podName, $targetDir, $lastDirName=null, $preprocessScript=''){
		$success=false;
		if(is_dir($dir) && !empty($podName)){

            //kubectl exec jwstaging-og-7c96db959c-tlq65 -- bash -c "mkdir -p /tmp/234234"
            //kubectl cp /Users/magicgod/magicgod/Code/core-lib jwstaging-og-7c96db959c-tlq65:/tmp/234234/core-lib
            //kubectl exec jwstaging-og-7c96db959c-tlq65 -- bash -c "cd /tmp/234234 && chown -R vagrant:vagrant core-lib && rm -rf ./core-lib/.git* && cp -R core-lib/* /home/vagrant/Code/og/submodules/core-lib/"

			if(empty($lastDirName)){
				$arr=explode('/', $dir);
				$lastDirName=$arr[count($arr)-1];
			}

            // $localTarFile='/tmp/'.$podName.'-'.$dir.'.tar.gz';
            // //tar local
            // $tarGz=new PharData($localTarFile);
            $tmpName=rand(100000,999999);
			//make temp dir
            // $script='kubectl exec '.$podName.' -- bash -c "mkdir -p /tmp/'.$tmpName.'"';
			// $success=$this->passthruBool($script);
			$success=$this->execOnPod('-- bash -c "mkdir -p /tmp/'.$tmpName.'"', $podName);
			//copy files to temp dir
            $script='kubectl cp '.$dir.' '.$podName.':/tmp/'.$tmpName.'/'.$lastDirName;
			$success=$this->passthruBool($script);
			//copy temp file to target dir and change permission
            // $script='kubectl exec '.$podName.' -- bash -c "cd /tmp/'.$tmpName.' && chmod -R 777 '.$lastDirName.' && rm -rf ./'.$lastDirName.'/.git* && cp -R '.$lastDirName.'/* '.$targetDir.'"';
			// $success=$this->passthruBool($script);
			$cmd='-- bash -c "cd /tmp/'.$tmpName.' && chmod -R 777 '.$lastDirName;
			if(!empty($preprocessScript)){
				$cmd.=' && '.$preprocessScript.' ';
			}
			$cmd.=' && cp -R '.$lastDirName.'/* '.$targetDir.'"';
			$success=$this->execOnPod($cmd, $podName);

			//delete all temp files
            // $script='kubectl exec '.$podName.' -- bash -c "cd /tmp && rm -rf ./'.$tmpName.'"';
			// $success=$this->passthruBool($script);

			// $script='kubectl cp '.$dir.' '.$podName.':'.$targetDir;
			// $success=$this->passthruBool($script);
		}else{
            $this->error_log('wrong dir or pod name', $dir, $podName);
        }

		return $success;
	}

	/**
	 *
	 * search pod
	 * @param  string  $podNameMatch search pod name
	 * @param  boolean $returnAll    return all matched or only first one
	 * @return mixin one name ($returnAll==false) or name list ($returnAll==true)
	 *
	 */
	private function searchPod($podNameMatch, $returnAll=false){

		$first_pod_name=null;
		$pod_list=[];

		$script="kubectl get pods";
		$rlt=$this->execCmd($script);
		if(!empty($rlt) && !empty($rlt[0])){
			$arr=explode("\n", $rlt[0]);
			if(!empty($arr)){
				foreach ($arr as $line) {
					if(!empty($line)){
						$fldArr=explode(' ', $line);
						if(!empty($fldArr) && !empty($fldArr[0])){
							$pod_name=$fldArr[0];
							$this->debug_log('get pod: '.$pod_name, $podNameMatch);
							//start with
							if(strpos($pod_name, $podNameMatch)===0){

								$this->debug_log('found client pod: '.$this->client_name);

								// $found=true;
								if(!$returnAll){
									$first_pod_name=$pod_name;
									break;
								}else{
									$pod_list[]=$pod_name;
								}
							}
						}
					}
				}
			}
		}

		if($returnAll){
			return $pod_list;
		}else{
			return $first_pod_name;
		}

	}

	private function setClassVarFromArg($argNames){
		if(!empty($argNames)){
			foreach ($argNames as $argName) {
				$this->$argName=$this->climate->arguments->get($argName);
			}
		}
	}

	private function startsWith($str, $prefix){
		return substr($str, 0, strlen($prefix))==$prefix;
	}

	private function showWarning($msg){
		$this->climate->lightRed($msg);
	}

	private function processScriptToArray($script){
		$script=str_replace("\r\n","\r", $script);
		return explode("\r", $script);
	}

	private function passthruBool($script){
		return $this->passthru($script)==0;
	}

	private function passthru($script){

		if($this->is_debug){
			$this->debug_log($script);
		}
		$rlt_var=0;
		passthru($script, $rlt_var);
		return $rlt_var;
	}

	private function execCmd($script, $print=false, $return=true, $force_array=true){

		if(is_string($script) && $force_array){
			$script=$this->processScriptToArray($script);
		}

		if(!is_array($script)){
			$script=[$script];
		}

		$str=[];
		foreach ($script as $s) {
			if(empty(trim($s)) || substr($s, 0, 1)=='#'){
				continue;
			}

			// $rlt=shell_exec($s);
			exec($s, $rlt, $rlt_var);

			if($rlt_var!=0){
				$this->error_log('run command error', $s, $rlt_var, $rlt);
				return false;
			}

			if($print){
				if($this->is_debug){
					$this->debug_log($s);
				}
				$this->debug_log($rlt);
			}

			if($return && !empty($rlt)){
				$str[]=implode("\n", $rlt);
			}else{
				unset($rlt);
			}

		}

		if($return){
			return $str;
		}
	}

	public function info_log() {
		$args = func_get_args();

		if (count($args) <= 0) {
			return '';
		}

		$msg = $this->buildDebugMessage($args, 'INFO');

		$this->climate->lightYellow($msg);

		return $msg;
	}

	public function error_log() {
		$args = func_get_args();

		if (count($args) <= 0) {
			return '';
		}

		$msg = $this->buildDebugMessage($args, 'ERROR');

		$this->climate->red($msg);

		return $msg;
	}

	public function debug_log() {

		$args = func_get_args();

		if (count($args) <= 0) {
			return '';
		}

		$msg = $this->buildDebugMessage($args, 'DEBUG');

		$this->climate->darkGray($msg);

		return $msg;
	}

	public function getConfig($key){
		if (!isset($this->config[$key])) {
			return FALSE;
		}

		return $this->config[$key];
	}

	public function setConfig($key, $value){
		$this->config[$key] = $value;
	}

	private function buildDebugMessage($args, $title = 'CLI', $addHeader = true) {

		$msg = '';

		if ($addHeader) {

			$msg .= "[" . $title . "] [";
			if (!empty($subtitle)) {
				$msg = $msg . $subtitle . '] [';
			}
		}
		foreach ($args as $key => $value) {
			$str = $this->formatDebugMessage($value);

			if(is_numeric($key)){
				$msg .= "'" . $str . "' ";
			}else{
				$msg .= $key . ": " . $str . ", ";
			}
		}

		if ($addHeader) {
			$msg .= ' ]';
		}

		return $msg;
	}

	private function formatDebugMessage($value) {

		if (is_object($value)) {
			if ($value instanceof \DateTime) {
				//print date time
				$str = $value->format(\DateTime::ATOM);
			} else if ($value instanceof \SimpleXMLElement) {
				$str = $value->asXML();
			} else if (method_exists($value, '__toString')) {
				$str = $value->__toString();
			} else if (method_exists($value, 'toString')) {
				$str = $value->toString();
			} else {
				$str = json_encode((array) $value, JSON_PRETTY_PRINT);
			}
		} else if (is_array($value)) {
			$str = json_encode($value, JSON_PRETTY_PRINT);
		} else if (is_null($value)) {
			$str = '(NULL)';
		} else if (is_bool($value)) {
			$str = $value ? 'true' : 'false';
		} else {
			$str = $value;
		}

		return $str;
	}

}

$cli=new Cli();

if(!$cli->run()){
	exit(1);
}else{
	exit(0);
}
