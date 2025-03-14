<?php
require_once dirname(__FILE__) . '/APIBaseController.php';

/**
 * Allows customer to perform various tests to validate their configuration.
 *
 * * SMS Gateway Testing
 */
class Test_page extends APIBaseController {

	function __construct() {
		parent::__construct();
		$this->ci = &get_instance();
		$this->load->library(array('permissions', 'template', 'utils'));
	}


	private function loadTemplate($title) {
		$this->template->add_css('resources/css/general/style.css');

		$this->template->write('title', $title);
		$this->template->write('username', $this->authentication->getUsername());
		$this->template->write('userId', $this->authentication->getUserId());
	}

	# This function works as a sample of how to write a test page
	public function sample() {
		#sleep(2);	# If necessary, impose a delay to avoid misuse
		# The test msg to be displayed on the page. Will be wrap in <pre> tags.
		$data['msg'] = "This is a test msg. \nNo HTML needed.";
		$this->loadTemplate('Test Page');
		$this->template->write_view('main_content', 'view_test_page', $data);
		$this->template->render();
	}

	public function sms_gateway() {
		sleep(2);

		$this->load->library('sms/sms_sender');
		$useSmsApi = $this->sms_sender->getSmsApiName();
		$toNumber  = $this->input->get('to');
		$content   = $this->utils->createSmsContent("000000", $useSmsApi);

		$msg = "Your SMS API: [$useSmsApi]\n";
		$msg .= "Test SMS sending to: [$toNumber]\n";

		# Note: Sending template directly, as we don't know how many vars to replace
		$msg .= "Test SMS content (before signature): [$content]\n";
		$msg .= "SMS sender (used in signature): [".$this->config->item('sms_from')."]\n";
		$sendResult = $this->sms_sender->send($toNumber, $content);
		$msg .= "SMS send success? [".($sendResult?'Yes':'No')."]\n";
		$msg .= "Error/Msg: [".$this->sms_sender->getLastError()."]";

		$data['msg'] = $msg;
		$this->loadTemplate('Test Page');
		$this->template->write_view('main_content', 'view_test_page', $data);
		$this->template->render();
	}

	public function date() {
		$now = new DateTime();
		$now->modify('first day of this month 00:00:00');
		echo $now->format('Y-m-d H:i:s');
	}

	public function test_cache(){

		$result=[];

		$key='testkey';
		$value=random_string('sha1');
		$ttl=600;

		$cache_server = $this->config->item('memcached');

//		if ($this->config->load('memcached', TRUE, TRUE))
//		{

//			if (is_array($this->config->item('memcached')))
//			{

//				foreach ($this->config->config['memcached'] as $name => $conf)
//				{
//					$cache_server[$name] = $conf;
//				}
//			}
//		}

//		$this->utils->debug_log(array_keys($this->config->config));

//		$cache_server=[
//			'hostname' => 'kgvipenstaging-memcached',
//			'port'        => 11211,
//			'weight'    => 1
//		];

		$this->_memcached = new Memcached();
		$this->_memcached->addServer(
			$cache_server['hostname'], $cache_server['port'], $cache_server['weight']
		);

		$result[]=$this->utils->debug_log('init server', $cache_server);

		$rlt=$this->_memcached->set($key, array($value, time(), $ttl), $ttl);

		$return_val = $this->_memcached->get($key);

		$result[]=$this->utils->debug_log('set cache', $key, $value, $ttl, 'result', $rlt);
		$result[]=$this->utils->debug_log('return cache', $key, $return_val);

		$return_from_utils=$this->utils->getTextFromCache($key);
		$result[]=$this->utils->debug_log('return cache from utils', $key, $return_from_utils);

		$value=random_string('sha1');

		$rlt=$this->utils->saveTextToCache($key, $value, 600);
		$result[]=$this->utils->debug_log('set cache by utils', $key, $value, $ttl, 'result', $rlt);
		$return_from_utils=$this->utils->getTextFromCache($key);

		$result[]=$this->utils->debug_log('return cache from utils', $key, $return_from_utils);

		$this->returnJsonResult($result);
	}

	public function test_ip(){
		$result=['ip'=>$this->utils->getIP(),
			'tryGetRealIPWithoutWhiteIP'=>$this->utils->tryGetRealIPWithoutWhiteIP(),
			'server'=>$_SERVER];

		$this->returnJsonResult($result);
	}

	public function nginx_stats($pass){
		if($pass!=$this->utils->getConfig('password_nginx_stats')) {
			return show_error('no permission');
		}

		$result=['stats'=>file_get_contents('http://127.0.0.1/nginx_stats')];

		$this->returnJsonResult($result);
	}

	public function get_reqest_header($pass){
		if($pass!=$this->utils->getConfig('password_nginx_stats')) {
			return show_error('no permission');
		}

		$result=['$_SERVER'=>$_SERVER];

		$this->returnJsonResult($result);
	}

	public function test_db500(){

		$result=['success'=>true];
		$walletAccountId=null;
		// $qry=$this->db->query('select * from wrongdb');
		$lock_withdrawal=$this->lockAndTransForWithdrawLock($walletAccountId, function()
			use ( &$result) {

			$qry=$this->db->query('update game set game_wrong="testPT" where gameId=1');

			$result['success']=$qry!==false;

		});

		$result['lock_withdrawal']=$lock_withdrawal;

		$this->returnJsonResult($result);

	}

	public function batch_login_game(){

		$this->load->library(['authentication']);
		$this->load->model(['users']);
		if(!$this->users->isT1User($this->authentication->getUsername())){
			return show_error('No permission', 403);
		}

		$this->load->view('batch_login_game');
	}

	protected function createPlayerOnGamePlatform($game_platform_id, $playerId, $api, $extra= null) {

		# LOAD MODEL AND LIBRARIES
		$this->load->model('player_model');
		$this->load->library('salt');

		# GET PLAYER
		$player = $this->player_model->getPlayer(array('playerId' => $playerId));
		# DECRYPT PASSWORD
		$decryptedPwd = $this->salt->decrypt($player['password'], $this->getDeskeyOG());
		# CREATE PLAYER
		$rlt = $api->createPlayer($player['username'], $playerId, $decryptedPwd, NULL, $extra);

		// $this->utils->debug_log('CREATEPLAYERONGAMEPLATFORM PLAYER: ',$player);

		if ($rlt['success']) {
			$api->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		// } else {
		// 	$api->updateRegisterFlag($playerId, Abstract_game_api::FLAG_FALSE);
		}

		return $rlt;
	}

	public function launch_ag($username){

		$this->load->library(['authentication']);
		$this->load->model(['users']);
		if(!$this->users->isT1User($this->authentication->getUsername())){
			return show_error('No permission', 403);
		}

		$game_platform_id=T1AGIN_API;
		$api=$this->utils->loadExternalSystemLibObject($game_platform_id);

		$extra=[];
		$player = $api->isPlayerExist($username,$extra);

		# IF NOT CREATE PLAYER
		if (isset($player['exists']) && !$player['exists'] && $player['success']==true) {
			if(!is_null($player['exists'])){
				$player_id=$this->player_model->getPlayerIdByUsername($username);
				$this->createPlayerOnGamePlatform($game_platform_id, $player_id, $api,$extra);
			}
		}
		$rlt=$api->queryForwardGame($username, ['game_type'=>0, 'game_code'=>null, 'mode'=>'real', 'language'=>'zh-cn', 'platform'=>null]);

		redirect($rlt['url']);

	}

	public function test_log(){
		$this->utils->error_log('it is error');
		$this->utils->info_log('it is info');
		$this->utils->debug_log('it is debug');

		// throw new Exception('test exception');

		$this->returnText('done');
	}

	public function test_load_login_api(){
		$api=$this->utils->loadExternalLoginApi();

		if(!empty($api)){
			$this->returnText('done');
		}else{
			$this->returnText('failed');
		}

	}

	public function test_500(){
		// $any_obj->wrong_function();
		show_error('Test', 500);
	}

	public function test_redirect(){
		redirect('/');
	}

	public function test_ip_limit($ip){
        $ipLimit=$this->player_model->readIpLimitBy($ip);
		$this->returnJsonResult($ipLimit);
	}

	public function test_db_error($type, $throw_error='true'){
		$throw_error=$throw_error=='true';
		switch ($type) {
			case 'rollback':
				$this->db->trans_start();
				//try insert random data
				$this->db->insert('game', ['game'=>random_string()]);

				if($throw_error){
					//error
					$this->db->from('xxxxx')->get();
				}

				$this->db->trans_commit();
				break;
			case 'rollback_inside':
				$succ=$this->dbtransOnly(function()use($throw_error){
					//try insert random data
					$this->db->insert('game', ['game'=>random_string()]);

					if($throw_error){
						//error
						$this->db->from('xxxxx')->get();
					}
					return true;
				});
				if(!$succ){
					echo 'db error';
					return;
				}
				break;
			case 'rollback_lock':
				$playerId=1;
				$succ=$this->lockAndTransForPlayerBalance($playerId, function()use($throw_error){
					//try insert random data
					$this->db->insert('game', ['game'=>random_string()]);

					if($throw_error){
						//error
						$this->db->from('xxxxx')->get();
					}
					return true;
				});
				if(!$succ){
					echo 'db error';
					return;
				}
				break;
			default:
				if($throw_error){
					$this->db->from('xxxxx')->get();
				}
				break;
		}
		echo 'no db error';
	}

	public function test_400(){
		// $any_obj->wrong_function();
		show_error('Test', 400);
	}

	public function test_sleep($time){
		// $any_obj->wrong_function();
		sleep($time);
		echo 'sleep '.$time;
	}

	public function test_acl_ip(){
        if($this->_isBlockedPlayer()){
            return show_error('No permission', 403);
        }

        if (self::API_ACL_RESULT_SUCCESS !== $this->_check_api_acl(__FUNCTION__, 'iframe_login')) {
            $this->utils->debug_log('block login on api login', $this->utils->tryGetRealIPWithoutWhiteIP());
            return show_error('No permission', 403);
        }

        return $this->returnJsonResult(['passed'=>true]);
	}

	public function show_player_aff_source($username = null, $secure = null) {
		$MAGIC = 'trio21917';
		$username = trim($username);
		$secure = trim($secure);
		try {
			$secure_expected = md5($MAGIC . $username);
			if ($secure != $secure_expected) {
				throw new Exception('Secure mismatch', 0x100);
			}

			$this->load->model([ 'player_model' ]);

			$player = $this->player_model->getPlayerArrayByUsername($username);

			if (empty($player)) {
				throw new Exception('Player not found', 0x01);
			}

			$result = $this->utils->array_select_fields($player,
				[ 'playerId', 'username', 'tracking_source_code', 'tracking_code', 'createdOn' ]);

			$ret = [ 'success' => true, 'code' => 0, 'mesg' => null, 'result' => $result ];
		}
		catch (Exception $ex) {
			$this->utils->debug_log(__METHOD__, 'Exception', $ex->getMessage());
			$code = $ex->getCode();
			if ($code >= 0x100) {
				return show_error('No permission', 403);
			}
			$ret = [ 'success' => false, 'code' => $ex->getCode(), 'mesg' => $ex->getMessage() ];
		}
		finally {
			echo json_encode($ret);
		}
	}

	public function test_outofmemory(){
		$bigArray=[];
		while (true) {
			$bigArray[]=["test1"=>str_repeat("d", 1000)];
		}
	}

	public function check_ip(){
		if(!$this->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            $error_response['error'] = "ACCESS_DENIED({$ip})";
            return $this->returnJsonResult($error_response);
        }
        return $this->returnJsonResult(["success" => true]);
	}

	/**
     * for white ip
     * @return boolean $success
     */
    protected function validateWhiteIP(){
        $success=false;

        $this->backend_api_white_ip_list=$this->utils->getConfig('backend_api_white_ip_list');

        //init white ip info
        $this->load->model(['ip']);

        $success=$this->ip->checkWhiteIpListForAdmin(function ($ip, &$payload){
            $this->utils->debug_log('search ip', $ip);
            if($this->ip->isDefaultWhiteIP($ip)){
                $this->utils->debug_log('it is default white ip', $ip);
                return true;
            }
            foreach ($this->backend_api_white_ip_list as $whiteIp) {
                if($this->utils->compareIP($ip, $whiteIp)){
                    $this->utils->debug_log('found white ip', $whiteIp, $ip);
                    //found
                    return true;
                }
            }
            //not found
            return false;
        }, $payload);

        $this->utils->debug_log('get key info', $success);
        return $success;
    }

}