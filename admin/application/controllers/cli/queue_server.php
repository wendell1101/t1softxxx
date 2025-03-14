<?php
require_once dirname(__FILE__) . "/base_cli.php";

require_once dirname(__FILE__).'/../modules/remote_queue_server_module.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class Queue_server
 *
 * General behaviors include :
 *
 * * process queue job
 *
 * @property Queue_result $queue_result
 * @property Utils $utils
 *
 * @category Command Line
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Queue_server extends Base_cli {

	public $ogHome = null;

	private $redisClient;
	private $redis_host;
	private $redis_port;
	private $redis_timeout;
	private $redis_retry_timeout;
	private $redis_password;
	private $redis_database=null;

	private $redis_channel_key;

	private $dispatcher;
	private $subscriberList=[];

	private $rabbitmq_host;
	private $rabbitmq_port;
	private $rabbitmq_username;
	private $rabbitmq_password;
	private $rabbitmq_queue_key;
	private $rabbitmq_event_queue_key;
	private $rabbitmq_auto_queue_key;
	private $rabbitmq_direct_queue_key;
	private $rabbitmq_channel;
	private $rabbitmq_connection;


	use remote_queue_server_module;

	const CHANNEL_SUFFIX='-remote-queue';
	const EVENT_CHANNEL_SUFFIX='-remote-event-queue';
	const AUTO_CHANNEL_SUFFIX='-remote-auto-queue';

	const DIRECT_CHANNEL_SUFFIX='-remote-direct-queue';

	const MAX_RETRY_TIME=20;
	const FUNC_REMOTE_TRIGGER_EVENT='remote_trigger_event';

	const VALID_JOBS=[
		'remote_sms',
		'remote_sync_game_logs',
		'remote_email',
		'remote_debug_queue',
		'remote_import_players',
		'remote_rebuild_games_total',
		'remote_calculate_current_monthly_earnings',
		'remote_calculate_selected_aff_monthly_earnings',
		'remote_update_player_benefit_fee',
		'remote_batch_benefit_fee_adjustment',
		'remote_batch_addon_platform_fee_adjustment',
		'remote_pay_cashback_daily',
		'remote_pay_tournament_event',
		'remote_sync_t1_gamegateway',
		'remote_export_csv',
		'remote_stop_queue',
		'remote_manually_batch_add_cashback_bonus',
		'remote_batch_sync_balance_by',
		'remote_send_player_private_ip_mm_alert',
		'remote_regenerate_all_report',
		'remote_broadcast_message',
		'remote_batch_add_bonus',
		'remote_batch_subtract_bonus',
		'remote_send2Insvr4CreateAndApplyBonusMulti',
		'remote_processPreChecker' , // for withdrawal
		'remote_calcReportDailyBalance' ,
        'remote_bulk_import_playertag',
		'remote_bulk_import_affiliatetag',
        'remote_check_mgquickfire_data',
        'remote_sync_game_after_balance',
		'remote_batch_remove_playertag',
        'remote_send_data_to_fast_track',
        'remote_batch_remove_playertag_ids',
        'remote_batch_remove_iovation_evidence',
		'remote_t1lottery_settle_round',
        'remote_generate_recalculate_cashback_report',
        'remote_generate_recalculate_wcdp_report',
        'remote_rebuild_points_transaction_report_hour',
        'remote_generate_redemption_code',
        'remote_generate_redemption_code_with_internal_message',
        'remote_kick_player_by_game_platform_id',
		'remote_flowgaming_process_pushfeed',
		'remote_rebuild_seamless_balance_history',
		'remote_player_lock_balance',
		'remote_generate_static_redemption_code',
		'remote_generate_static_redemption_code_with_internal_message',
        'remote_call_sync_tags_to_3rd_api_with_player_id_list',
		'remote_batch_update_player_sales_agent',
		'remote_lock_table',
		'remote_do_manual_sync_gamelist_from_gamegateway',
		'remote_sync_game_tag_from_one_to_other_mdb',
		'remote_batch_refund',
		'remote_batch_export_player_id',
		'remote_update_player_quest_reward_status',
		'remote_sync_games_report_timezones',
        'remote_sync_summary_game_total_bet_daily',
        'remote_sync_latest_game_records',
		'remote_check_seamless_round_status',
        'remote_cancel_game_round',
		'remote_refresh_all_player_balance_in_specific_game_provider',
		'remote_transfer_all_players_subwallet_to_main_wallet',
        'remote_process_queue_approve_to_approved'
    ];

    private $online=false;
    // private $tried=false;

	/**
	 * overview : queue server constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->load->model('queue_result');

		$this->config->set_item('print_log_to_console', true);

		$this->ogHome = realpath(dirname(__FILE__) . "/../../../");

		// $this->redisClient=new Redis();

		//never stop
		set_time_limit(0);

        // $this->redisClient=new Redis();

        // $this->readRedisConfig();

        // $this->redis_channel_key=$this->_app_prefix.self::CHANNEL_SUFFIX;

		$this->readRabbitMQConfig();

		$this->initEvent();

		// register_shutdown_function(array($this, 'clean_shutdown'));
	}

	public function readRabbitMQConfig(){
        $rabbitmq_server_config=$this->utils->getConfig('rabbitmq_server');
        $this->rabbitmq_host=isset($rabbitmq_server_config['host']) ? $rabbitmq_server_config['host'] : null;
        $this->rabbitmq_port=isset($rabbitmq_server_config['port']) ? $rabbitmq_server_config['port'] : null;
        $this->rabbitmq_username=isset($rabbitmq_server_config['username']) ? $rabbitmq_server_config['username'] : null;
        $this->rabbitmq_password=isset($rabbitmq_server_config['password']) ? $rabbitmq_server_config['password'] : null;
        $this->rabbitmq_queue_key=$this->_app_prefix.self::CHANNEL_SUFFIX;
		$this->rabbitmq_event_queue_key=$this->_app_prefix.self::EVENT_CHANNEL_SUFFIX;
		$this->rabbitmq_auto_queue_key=$this->_app_prefix.self::AUTO_CHANNEL_SUFFIX;
		$this->rabbitmq_direct_queue_key=$this->_app_prefix.self::DIRECT_CHANNEL_SUFFIX;
	}

	public function activeRabbitMQ(){
        // $this->tried=true;
        if(!$this->online && !empty($this->rabbitmq_host)){
            try{

				$this->rabbitmq_connection = new AMQPStreamConnection($this->rabbitmq_host, $this->rabbitmq_port, $this->rabbitmq_username, $this->rabbitmq_password);
				$this->rabbitmq_channel = $this->rabbitmq_connection->channel();

                $this->online=!empty($this->rabbitmq_connection) && !empty($this->rabbitmq_channel);

            }catch(Exception $e){
                $this->CI->utils->error_log('active rabbit mq failed', $e);
                $this->online=false;
                $this->rabbitmq_connection=null;
	        	$this->rabbitmq_channel=null;
            }
        }

	}

	public function closeRabbitMQ(){
        if($this->online && !empty($this->rabbitmq_connection)){
        	try{
	        	$this->online=false;
	        	if(!empty($this->rabbitmq_channel)){
		        	$this->rabbitmq_channel->close();
		        	$this->rabbitmq_channel=null;
	        	}
	        	$this->rabbitmq_connection->close();
	        	$this->rabbitmq_connection=null;
	        }catch(Exception $e){
	        	$this->CI->utils->error_log('closeRabbitMQ failed', $this->CI->utils->getConfig('rabbitmq_server'), $e);
	        }
        }
	}

	/**
	 * overview : sync service start
	 * @param string $queueMode remote|event|auto|all
	 */
	public function start($queueMode='all') {

		$cnt=0;
		// $connected=false;
		if($queueMode=='remote'){
			$this->utils->info_log('start ', $this->rabbitmq_queue_key);
		}else if($queueMode=='event'){
			$this->utils->info_log('start ', $this->rabbitmq_event_queue_key);
		}else if($queueMode=='auto'){
			$this->utils->info_log('start ', $this->rabbitmq_auto_queue_key);
		}else if($queueMode=='direct'){
			$this->utils->info_log('start ', $this->rabbitmq_direct_queue_key);
		}else if($queueMode=='all'){
			$this->utils->info_log('start ', $this->rabbitmq_queue_key, $this->rabbitmq_event_queue_key, $this->rabbitmq_auto_queue_key);
		}else{
			$this->utils->error_log('wrong queue mode', $queueMode);
			return;
		}

		while(true){

			try{

				if(!$this->online){

					$this->readRabbitMQConfig();
					$this->activeRabbitMQ();

					if($queueMode=='remote'){
						$this->utils->info_log('try connect rabbitmq queue server, success:', $this->online ,
			        	$this->utils->getConfig('rabbitmq_server'), $this->rabbitmq_queue_key);
					}else if($queueMode=='event'){
						$this->utils->info_log('try connect rabbitmq queue server, success:', $this->online ,
			        	$this->utils->getConfig('rabbitmq_server'), $this->rabbitmq_event_queue_key);
					}else if($queueMode=='auto'){
						$this->utils->info_log('try connect rabbitmq queue server, success:', $this->online ,
			        	$this->utils->getConfig('rabbitmq_server'), $this->rabbitmq_auto_queue_key);
					}else if($queueMode=='direct'){
						$this->utils->info_log('try connect rabbitmq queue server, success:', $this->online ,
			        	$this->utils->getConfig('rabbitmq_server'), $this->rabbitmq_direct_queue_key);
					}else if($queueMode=='all'){
						$this->utils->info_log('try connect rabbitmq queue server, success:', $this->online ,
			        	$this->utils->getConfig('rabbitmq_server'), $this->rabbitmq_queue_key, $this->rabbitmq_event_queue_key, $this->rabbitmq_auto_queue_key);
					}

				}

				if($this->online && !empty($this->rabbitmq_channel)){
					$cnt=0;

					// $patterns=[$this->redis_channel_key];

					// $this->utils->debug_log("start", $this->redisClient->ping(), "brPop", $this->redis_channel_key);

					$callback=function($msg) use($queueMode){

						// if(!empty($listContent)){
						// 	$channel=$listContent[0];
						// 	$message=$listContent[1];
						//reset db
						$this->db->_reset_select();
						$this->db->reconnect();
						$this->db->initialize();

						// $channel=$this->rabbitmq_queue_key;
						$message=$msg->body;
						//$this->db = substr($channel, 0, strlen($channel)-strlen(self::CHANNEL_SUFFIX)); // str_replace(self::CHANNEL_SUFFIX, '', $channel);
						//$db = $this->db;

						$token=null;
						$data=null;
						if($queueMode=='direct'){
							// direct mode, message is json data, so save it to db
							$data=json_decode($message, true);
							$data['params']=$data['full_params'];
							$this->utils->info_log('direct start: ', $data);
							$token=$this->convertToQueueResult($data,$data['params']);
							$this->utils->info_log('convert to queue result: '.$data['func_name'], $token);
							if(empty($token)){
								$this->utils->error_log('convert to queue result failed', $data);
								return;
							}
						}else{
							$token=$message;
						}
						$data = $this->initJobData($token);

						$this->utils->debug_log('process token:'.$token, $data);

						if (!empty($data)) {
							$func_name=$data['func_name'];

							if($func_name==self::FUNC_REMOTE_TRIGGER_EVENT){
								$params = json_decode($data['full_params'], true);
								$data['params']=$params;
								$this->utils->info_log('event start: '.$data['func_name'], $token, $params);
								$this->triggerEvent($data, $params);
								$this->utils->info_log('event end: '.$data['func_name'], $token);
							}else if(in_array($func_name, self::VALID_JOBS)){
								$params = json_decode($data['full_params'], true);
								$data['params']=$params;
								$this->utils->info_log('job start: '.$data['func_name'], $token, $params);
								$this->$func_name($data, $params);
								$this->utils->info_log('job end: '.$data['func_name'], $token);
							}else{
								$this->utils->error_log('Not in white list, Invalid function name:'.$func_name, $data);
							}

						} else {
							$this->utils->error_log('Got wrong message', $token);
						}

					// }
					};
					// $listContent=$this->redisClient->brPop([$this->redis_channel_key], $this->queue_redis_pop_timeout);
					if($queueMode=='remote' || $queueMode=='all'){
						$this->rabbitmq_channel->queue_declare($this->rabbitmq_queue_key, false, false, false, false);
						$this->rabbitmq_channel->basic_consume($this->rabbitmq_queue_key, '', false, true, false, false, $callback);
					}
					if($queueMode=='event' || $queueMode=='all'){
						// event queue
						$this->rabbitmq_channel->queue_declare($this->rabbitmq_event_queue_key, false, false, false, false);
						$this->rabbitmq_channel->basic_consume($this->rabbitmq_event_queue_key, '', false, true, false, false, $callback);
					}
					if($queueMode=='auto' || $queueMode=='all'){
						// auto queue
						$this->rabbitmq_channel->queue_declare($this->rabbitmq_auto_queue_key, false, false, false, false);
						$this->rabbitmq_channel->basic_consume($this->rabbitmq_auto_queue_key, '', false, true, false, false, $callback);
					}

					if($queueMode=='direct'){
						// api queue, don't include in all
						$this->rabbitmq_channel->queue_declare($this->rabbitmq_direct_queue_key, false, false, false, false);
						$this->rabbitmq_channel->basic_consume($this->rabbitmq_direct_queue_key, '', false, true, false, false, $callback);
					}

					while (count($this->rabbitmq_channel->callbacks)) {
					    $this->rabbitmq_channel->wait();
					}

					$this->closeRabbitMQ();

				}else{
					$this->utils->error_log('connect rabbitmq failed', $cnt, $this->utils->getConfig('rabbitmq_server'), $this->rabbitmq_queue_key);
					// $this->utils->error_log('connect redis failed', $cnt, $this->redis_host, $this->redis_port);
					$cnt++;
					if($cnt>=self::MAX_RETRY_TIME){
						$this->utils->error_log('quit...');
						break;
					}
					// $redisConnect=false;
					$this->closeRabbitMQ();
				}

				sleep(1);

			} catch (Exception $e) {
				$this->utils->error_log($e);

		        $this->db->_reset_select();
		        //reconnect db
		        $this->db->reconnect();

				//try close
				$this->closeRabbitMQ();

			}
		}
	}

	public function runCmd($cmd){

		// $str = exec($cmd, $output, $return_var);

		// $this->utils->debug_log($cmd, $return_var, $output);
		// unset($output);
		// unset($str);

        $return_var=pclose(popen($cmd, 'r'));
		return $return_var;
	}

	private function initJobData($token) {
		$row = $this->queue_result->getResult($token);
		return $row;
	}

    public function initEvent(){

        $this->dispatcher = new EventDispatcher();
        //register all subscribers
        //load files
        $path=dirname(__FILE__) . "/../../models/subscribers/*.php";
        $this->utils->info_log('init event start', $path);
        $list=glob($path);
        if(!empty($list)){
        	foreach ($list as $filename) {
        		$this->utils->debug_log('try load subscriber: '.$filename);
        		$className=pathinfo($filename, PATHINFO_FILENAME);
        		//ignore abstract subscriber
        		if($className=='AbstractSubscriber' || $className=='AutoFinishSubscriber'){
        			continue;
        		}
        		// require_once $filename;
        		$this->load->model('subscribers/'.$className, $className, false, true);
        		// $subObj=new $className();
        		$subObj=$this->$className;
        		$this->utils->debug_log('class is', get_class($subObj));
        		$this->subscriberList[]=$subObj;
        		$this->dispatcher->addSubscriber($subObj);
        	}
            //last one is AutoFinishSubscriber
            $this->load->model('subscribers/AutoFinishSubscriber', 'AutoFinishSubscriber', false, true);
            $subObj=$this->AutoFinishSubscriber;
            $this->subscriberList[]=$subObj;
            $this->dispatcher->addSubscriber($subObj);
        }
        $this->utils->info_log('init event end');
    }

    public function triggerEvent($data, $params){
        $token = $data['token'];
        $eventInfo=$params['event'];
        $eventClass=$eventInfo['class'];
        $file=dirname(__FILE__) . "/../../models/events/".$eventClass.".php";
        if(file_exists($file)){
            require_once $file;
            $event=new $eventClass($token, $eventInfo['name'], $eventInfo['data']);
            $this->dispatcher->dispatch($event->getEventName(), $event);
        }
    }

	public function convertToQueueResult($data, $params){

		// create a job by params
		$funcName = 'remote_'.$data['func_name'];
		$token=$data['token'];
		if(empty($token)){
			// failed
			return $token;
		}
		$this->queue_result->newResult($data['system_id'],
			$funcName, $params, $data['caller_type'],
			$data['caller'], $data['state'], $data['lang'], $token);
		return $token;
	}

}

/// END OF FILE//////////////
