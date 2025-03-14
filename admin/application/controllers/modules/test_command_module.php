<?php

trait test_command_module{

    public function test_cache(){

    	$key=$this->utils->getAppPrefix().'-test_key';
    	$text='random '.random_string();
    	$ttl=180;

		$this->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));

		$this->utils->debug_log('-----get cache key', $key, $text, $ttl);

		$succ=$this->cache->save($key, $text, $ttl);
		$this->utils->info_log('save cache', $succ);

		$data = $this->cache->get($key);

		$this->utils->info_log('get data', $data);

    }

    public function test_log(){
		$this->utils->error_log('it is error');
		$this->utils->info_log('it is info');
		$this->utils->debug_log('it is debug');

		$this->utils->undefined_func();
    }

    public function test_mdb($target_db=null){

    	//example: _OG_TARGET_DB=cny bash ./admin/shell/test.sh test_mdb
    	$env_target_db=getenv('_OG_TARGET_DB');

    	$this->utils->info_log('target_db', $target_db, 'env_target_db', $env_target_db);

    	if(empty($target_db) && !empty($env_target_db)){
    		//overwrite
    		$target_db=$env_target_db;
    	}

    	$this->load->model(['player_model']);
    	$readonly=false;
    	$sql='select * from migrations';
    	$params=null;
    	$result=$this->player_model->runRawSelectSQLArrayOnMDB($sql, $params, $readonly);

    	$this->utils->debug_log($result);

    	$this->player_model->foreachMultipleDBToCurrentDB(function(){

    		$this->utils->debug_log('load db: '.$this->db->database);

    	});

    }

    public function test_sync_role_super_to_mdb($roleId){

        $this->load->model(['multiple_db_model', 'roles']);
        $rlt=$this->multiple_db_model->syncRoleFromSuperToOtherMDB($roleId);

        $this->utils->debug_log('syncRoleFromSuperToOtherMDB', $rlt);
    }

    public function test_sync_user_super_to_mdb($username){
        $this->load->model(['multiple_db_model', 'users']);
        $userId=$this->users->getIdByUsername($username);
        $rlt=$this->multiple_db_model->syncUserFromSuperToOtherMDB($userId);

        $this->utils->debug_log('syncUserFromSuperToOtherMDB', $rlt);
    }

    public function test_sync_player_super_to_mdb($username){
        $this->load->model(['multiple_db_model', 'player_model']);
        $playerId=$this->player_model->getPlayerIdByUsername($username);
        $rlt=$this->multiple_db_model->syncPlayerFromSuperToOtherMDB($playerId);

        $this->utils->debug_log('syncPlayerFromSuperToOtherMDB', $rlt);
    }

    public function test_sync_agency_super_to_mdb($username){
        $this->test_sync_agency_to_mdb(Multiple_db::SUPER_TARGET_DB, $username);
    }

    public function test_sync_agency_to_mdb($dbKey, $username){
        $this->load->model(['multiple_db_model', 'agency_model']);
        $agentId=$this->agency_model->get_agent_id_by_agent_name($username);
        $rlt=$this->multiple_db_model->syncAgencyFromOneToOtherMDB($dbKey, $agentId);

        $this->utils->debug_log('syncAgencyFromOneToOtherMDB:'.$dbKey, $rlt);
    }

    public function test_sync_aff_super_to_mdb($username){
        $this->test_sync_aff_to_mdb(Multiple_db::SUPER_TARGET_DB, $username);
    }

    public function test_sync_aff_to_mdb($dbKey, $username){
        $this->load->model(['multiple_db_model', 'affiliatemodel']);
        $affId=$this->affiliatemodel->getAffiliateIdByUsername($username);
        $rlt=$this->multiple_db_model->syncAffFromOneToOtherMDB($dbKey, $affId);

        $this->utils->debug_log('syncAffFromOneToOtherMDB:'.$dbKey, $rlt);
    }

    public function test_show_adminuser_on_mdb($username){
        $this->load->model('multiple_db_model');
        $sql=<<<EOD
select adminusers.*, roles.roleName from adminusers
join userroles on userroles.userId=adminusers.userId
join roles on userroles.roleId=roles.roleId
where username=?
EOD;
        $params=[$username];
        $rlt=$this->multiple_db_model->showResultOnMDB($sql, $params);
        $this->utils->info_log('test_show_adminuser_on_mdb', $rlt);
    }

    public function test_show_role_on_mdb($roleId){
        $this->load->model('multiple_db_model');
        $sql=<<<EOD
select * from roles
where roleId=?
EOD;
        $params=[$roleId];
        $rlt[]=$this->multiple_db_model->showResultOnMDB($sql, $params);

        $sql=<<<EOD
select * from rolefunctions
where roleId=?
EOD;
        $params=[$roleId];
        $rlt[]=$this->multiple_db_model->showResultOnMDB($sql, $params);

        $sql=<<<EOD
select * from rolefunctions_giving
where roleId=?
EOD;
        $params=[$roleId];
        $rlt[]=$this->multiple_db_model->showResultOnMDB($sql, $params);

        $this->utils->info_log('test_show_role_on_mdb', $rlt);
    }

    public function test_show_player_on_mdb($username){
        $this->load->model('multiple_db_model');
        $playerId=$this->multiple_db_model->runAnyOnSuperDB(function($db)
            use($username){
            $db->select('playerId')->from('player')->where('username', $username);
            return $this->multiple_db_model->runOneRowOneField('playerId', $db);
        });

        $sql=<<<EOD
select player.username, player.password, playerdetails.language, player.currency from player
join playerdetails on player.playerId=playerdetails.playerId
where player.playerId=?
EOD;
        $params=[$playerId];
        $rlt[]=$this->multiple_db_model->showResultOnMDB($sql, $params);

        $sql=<<<EOD
select * from playerlevel
where playerlevel.playerId=?
EOD;
        $params=[$playerId];

        $rlt[]=$this->multiple_db_model->showResultOnMDB($sql, $params);
        $this->utils->info_log('test_show_player_on_mdb', $rlt);
    }

    public function test_show_agency_on_mdb($username){

        $this->load->model('multiple_db_model');
        $agent_id=$this->multiple_db_model->runAnyOnSuperDB(function($db)
            use($username){
            $db->select('agent_id')->from('agency_agents')->where('agent_name', $username);
            return $this->multiple_db_model->runOneRowOneField('agent_id', $db);
        });

        $sql=<<<EOD
select agency_agents.agent_id, agency_agents.agent_name, agency_agents.password, agency_agents.language, agency_agents.currency
from agency_agents
where agency_agents.agent_id=?
EOD;
        $params=[$agent_id];

        $rlt[]=$this->multiple_db_model->showResultOnMDB($sql, $params);
        $this->utils->info_log('test_show_agency_on_mdb agency_agents');

        $sql=<<<EOD
select * from agency_agent_game_platforms
where agent_id=?
EOD;
        $params=[$agent_id];
        $rlt[]=$this->multiple_db_model->showResultOnMDB($sql, $params);
        $this->utils->info_log('test_show_agency_on_mdb agency_agent_game_platforms');

        $sql=<<<EOD
select * from agency_agent_game_types
where agent_id=?
EOD;
        $params=[$agent_id];
        $rlt[]=$this->multiple_db_model->showResultOnMDB($sql, $params);
        $this->utils->info_log('test_show_agency_on_mdb agency_agent_game_types');

        $sql=<<<EOD
select * from agency_flattening
where agent_id=?
EOD;
        $params=[$agent_id];
        $rlt[]=$this->multiple_db_model->showResultOnMDB($sql, $params);
        $this->utils->info_log('test_show_agency_on_mdb agency_flattening');

        $sql=<<<EOD
select * from agency_flattening_options
where agent_id=?
EOD;
        $params=[$agent_id];
        $rlt[]=$this->multiple_db_model->showResultOnMDB($sql, $params);
        $this->utils->info_log('test_show_agency_on_mdb agency_flattening_options');

        $sql=<<<EOD
select * from agency_tracking_domain
where agent_id=?
EOD;
        $params=[$agent_id];
        $rlt[]=$this->multiple_db_model->showResultOnMDB($sql, $params);
        $this->utils->info_log('test_show_agency_on_mdb agency_tracking_domain');

        $this->utils->info_log('test_show_agency_on_mdb', $rlt);

    }

    public function test_show_aff_on_mdb($username){
        $this->load->model('multiple_db_model');
        $affiliateId=$this->multiple_db_model->runAnyOnSuperDB(function($db)
            use($username){
            $db->select('affiliateId')->from('affiliates')->where('username', $username);
            return $this->multiple_db_model->runOneRowOneField('affiliateId', $db);
        });

        $sql=<<<EOD
select affiliates.affiliateId, affiliates.username, affiliates.password, affiliates.language, affiliates.currency
from affiliates
where affiliates.affiliateId=?
EOD;
        $params=[$affiliateId];
        $rlt[]=$this->multiple_db_model->showResultOnMDB($sql, $params);

        $sql=<<<EOD
select * from affiliate_terms
where affiliateId=?
EOD;
        $params=[$affiliateId];
        $rlt[]=$this->multiple_db_model->showResultOnMDB($sql, $params);

        $sql=<<<EOD
select * from aff_tracking_link
where aff_id=?
EOD;
        $params=[$affiliateId];
        $rlt[]=$this->multiple_db_model->showResultOnMDB($sql, $params);

        $sql=<<<EOD
select * from affiliate_read_only_account
where affiliate_id=?
EOD;
        $params=[$affiliateId];
        $rlt[]=$this->multiple_db_model->showResultOnMDB($sql, $params);

        $this->utils->info_log('test_show_aff_on_mdb', $rlt);
    }

    public function test_global_lock($username){

        $success=$this->utils->globalLockPlayerRegistration($username, function(){
            $success=true;

            sleep(20);
            $this->utils->info_log('locked');

            return $success;
        });

        $this->utils->info_log('global lock', $success);
    }

    public function test_parallel_global_lock($username, $max=4){

        for ($i=0; $i <$max ; $i++) {
            $cmd=$this->utils->generateCommandLine('test_global_lock', [$username], false);
            $this->utils->runCmd($cmd);
        }

    }

    public function test_refresh_wallet($username){
        $this->load->model(['player_model', 'wallet_model']);
        $playerId=$this->player_model->getPlayerIdByUsername($username);

        $success=$this->lockAndTransForPlayerBalance($playerId, function() use($playerId){
            $db=$this->player_model->getSuperDBFromMDB();
            $success=$this->wallet_model->refreshBigWalletOnDB($playerId, $db);
            return $success;
        });

        $this->utils->debug_log('refreshBigWalletOnDB', $success);
    }

    public function test_db_result($type='number'){
        $db=$this->db;
        $readonly=false;
        $cacheOnMysql=false;
        $sql='select * from logs';

        $this->utils->debug_log('start');
        $_multiple_db=Multiple_db::getSingletonInstance();
        $conn=$_multiple_db->rawConnectDB($readonly, $db);
        try{

            $qry = mysqli_query($conn, $sql, $cacheOnMysql ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT);

            $this->utils->info_log('before fetch');
            $rows = mysqli_fetch_all($qry, $type=='number' ? MYSQLI_NUM : MYSQLI_ASSOC);
            $this->utils->info_log('after fetch');

            $fields=mysqli_fetch_fields($qry);

            mysqli_free_result($qry);
            unset($qry);

        }finally{
            if(!empty($conn)){
                mysqli_close($conn);
            }
        }
        unset($conn);

        $this->utils->debug_log(count($rows));

        $this->utils->debug_log('done');

    }

    public function test_summary2_report(){
        $this->load->model(['multiple_db_model']);
        $conditions=[
            'searchBy'=>[
                'dateFrom'=>'2018-09-01',
                'dateTo'=>'2018-09-08',
                'monthOnly'=>false,
            ],
            'limitBy'=>[
                'pageSize'=>10,
                'currentPage'=>1,
            ],
        ];
        $rlt=$this->multiple_db_model->querySummary2Report($conditions, Multiple_db_model::QUERY_REPORT_TYPE_ONE_PAGE);

        $this->utils->info_log('querySummary2Report', $rlt);
    }

    public function test_send_alert(){
        $level='warning';
        $title='test warning '.random_string();
        $message='message text content warning '.random_string();
        $this->utils->sendAlertBack($level, $title, $message);
        $level='error';
        $title='test error '.random_string();
        $message='message text content error '.random_string();
        $this->utils->sendAlertBack($level, $title, $message);
    }

    public function test_fix_collation(){
        $this->load->model(['player_model']);
        $this->player_model->fixCollationOnTable('mg_game_logs', ['account_number', 'display_name',
            'display_game_category', 'external_uniqueid', 'module_id', 'client_id', 'uniqueid',
            'user_name', 'external_game_id', 'game_platform']);
    }

    public function test_google_auth($secret=null, $app_code=null){
        $this->load->library('third_party/lib_google_authenticator', null, 'lib_google_authenticator');
        if(empty($secret)){
            $secret=$this->lib_google_authenticator->createSecret();
        }
        $currentTimeSlice = floor(time() / 30);
        $discrepancy=10;
        for ($i = -$discrepancy; $i <= $discrepancy; ++$i) {
            $calculatedCode = $this->lib_google_authenticator->getCode($secret, $currentTimeSlice + $i);
            if($app_code==$calculatedCode){
                $this->utils->info_log('found code', $app_code, $secret, $currentTimeSlice);
            }
            $this->utils->debug_log('calculatedCode', $calculatedCode, $secret, $currentTimeSlice);
        }
        $code=$this->lib_google_authenticator->getCode($secret);
        $rlt=$this->lib_google_authenticator->verifyCode($secret, $code);
        $this->utils->debug_log('verify code', $secret, $code, $rlt);
    }

    public function test_lock_redis($playerId=9999999){

        $success=$this->lockAndTransForPlayerBalance($playerId, function(){
            $success=true;

            sleep(20);
            $this->utils->info_log('locked');

            return $success;
        });

        $this->utils->info_log('lock redis', $success);
    }

    public function test_parallel_lock_redis($playerId=9999999, $max=2){

        for ($i=0; $i <$max ; $i++) {
            $cmd=$this->utils->generateCommandLine('test_lock_redis', [$playerId], false);
            $this->utils->runCmd($cmd);
        }

    }

    public function test_forward_incomplete($username){
        $this->utils->debug_log('test_forward_incomplete: '.$username);
        $launcher_settings=['language'=>'en-us', 'mode'=>'real'];
        $gameManager=$this->utils->loadGameManager();
        $incompleteRlt=$gameManager->forwardToIncompleteGameLink($username, $launcher_settings);
        $this->utils->info_log('forwardToIncompleteGameLink ', $incompleteRlt, $username, $launcher_settings);
    }

    public function test_china_ip($ip){
        $this->utils->debug_log('test_china_ip', $this->utils->getIpCityAndCountry($ip));
        $this->utils->debug_log('test_china_ip only', $this->utils->getChinaCityCountryIp($ip));
    }

    public function test_calc_original_md5_for_api($apiId, $externalUniqueId, $filename){
        //$row[$md5Field]=$this->generateMD5SumOneRow($row, $keys, $floatFields);
        $api=$this->utils->loadExternalSystemLibObject($apiId);
        if(!empty($api)){
            $this->load->model(['original_game_logs_model']);
            $rlt=$this->original_game_logs_model->calcOriginalMD5ByApi($api->getOriginalTable(), $api, $externalUniqueId);
            $this->utils->info_log('test_calc_original_md5_for_api on db', $rlt);
            $jsonStr=file_get_contents('/home/vagrant/Code/'.$filename);
            $jsonArr=json_decode($jsonStr, true);
            $resultText=$jsonArr['resultText'];
            unset($jsonArr);unset($jsonStr);
            $rlt=$api->testMD5Fields($resultText, $externalUniqueId);
            $this->utils->info_log('test_calc_original_md5_for_api on result text', $rlt);
        }else{
            $this->utils->error_log('not found api', $apiId);
        }
    }

    public function test_transfer_subwallet_to_main_wallet($playerName, $apiId, $balance) {
        $this->load->model(['player_model','wallet_model']);
        $api = $this->utils->loadExternalSystemLibObject($apiId);
        if ($api) {
            $isPlayerExist = $api->isPlayerExist($playerName);
            if ($isPlayerExist) {
                $playerId=$this->player_model->getPlayerIdByUsername($playerName);
                $result = $this->utils->transferWallet($playerId, $playerName, $apiId, Wallet_model::MAIN_WALLET_ID, $balance);
                $this->utils->debug_log('return transferWallet', $result);
                if (isset($result['success']) && $result['success']) {
                    $this->utils->debug_log('transfer '.$playerName.' from '.$api->getPlatformCode().' balance:'.$balance.' success');
                } else {
                    $this->utils->error_log('transfer '.$playerName.' from '.$api->getPlatformCode().' balance:'.$balance.' failed');
                }
            }else{
                $this->utils->error_log('player does not exist');
            }
        }else{
            $this->utils->error_log('wrong api');
        }

    }

    public function test_unique_index_field($tableName, $fieldName){
        $this->load->model(['player_model']);
        $exists=$this->player_model->existsUniqueIndex($tableName, $fieldName);
        $this->utils->info_log('table:'.$tableName.', field:'.$fieldName.', unique index '.($exists ? 'exists' : 'does not exist'));
    }

    public function test_system_feature(){
        $this->load->model(['system_feature']);
        $enabled=$this->system_feature->isEnabledFeatureWithoutCache('notexist_feature');
        $this->utils->info_log('notexist_feature', $enabled);

        $enabled=$this->system_feature->isEnabledFeature('add_security_on_deposit_transaction');
        $this->utils->info_log('load from cache add_security_on_deposit_transaction', $enabled);

    }

    public function test_encrypt_decrypt($text){

        $error=null;
        $encryptedText=$this->utils->encryptPassword($text, $error);
        $this->utils->info_log('encrypt text', $text, 'to', $encryptedText, $error);

        $decryptedText=$this->utils->decryptPassword($encryptedText, $error);
        $this->utils->info_log('decrypt text', $encryptedText, 'to', $decryptedText, $error);

    }

    public function test_explain_sql(){
        $sql=<<<EOD
select game_logs.id as uniqueid,
game_logs.external_uniqueid as game_external_uniqueid,
game_provider_auth.login_name as username,
'idnsportidr' as merchant_code,
game_logs.game_platform_id as game_platform_id,
game_description.external_game_id as game_code,
game_description.game_name as game_name,
game_logs.end_at game_finish_time,
game_logs.note game_details,
game_logs.bet_at as bet_time,
game_logs.end_at as payout_time,
game_logs.`table` as round_number,
ROUND(ifnull(game_logs.trans_amount, game_logs.bet_amount), 2) as real_bet_amount,
ROUND(game_logs.bet_amount, 2) as effective_bet_amount,
ROUND(game_logs.result_amount, 2) as result_amount,
ROUND(game_logs.result_amount+ifnull(game_logs.trans_amount,game_logs.bet_amount), 2) as payout_amount,
ROUND(game_logs.after_balance, 2) as after_balance,
game_logs.bet_details as bet_details,
game_logs.md5_sum,
game_logs.ip_address,
game_logs.bet_type,
game_logs.odds_type,
game_logs.odds,
ROUND(game_logs.rent, 2) as rent,
game_logs.response_result_id,
game_logs.external_log_id as update_version,
'normal' as game_status,
1 as detail_status,
game_logs.updated_at as updated_at
from game_logs as game_logs use index(idx_updated_at)
join game_provider_auth on game_logs.player_id=game_provider_auth.player_id and game_logs.game_platform_id=game_provider_auth.game_provider_id
join game_description on game_description.id=game_logs.game_description_id
where
game_logs.flag=?
 and game_logs.updated_at >= ?
and game_provider_auth.agent_id=?

 and game_logs.game_platform_id=2090
order by game_logs.updated_at
 limit 2000

EOD;

        $params=[1, '2019-09-05 06:04:13', 13];
        $this->load->model(['player_model']);
        $rows=$this->player_model->queryExplainRows($sql, $params, 'game_logs');

        $this->utils->debug_log('explain sql', $rows);
    }

    public function test_redis_cache(){
        $key='test-'.date('YmdHis').'-'.rand();
        $originalVal='test001';

        // $redis=try_load_redis($this);
        // $redis->select(0);
        // $saveSucc=$redis->set($key, $originalVal);
        // $val=$redis->get($key);

        // $this->utils->info_log('try redis', $key, $originalVal, 'saveSucc', $saveSucc, 'get', $val);

        $key='test-'.date('YmdHis').'-'.rand();
        // $this->utils->setConfig('cache_driver_type', 'redis');
        $this->utils->setConfig('disable_cache', false);
        $saveSucc=$this->utils->saveTextToCache($key, $originalVal);
        $val = $this->utils->getTextFromCache($key);

        $this->utils->info_log('try utils cache', $key, $originalVal, 'saveSucc', $saveSucc,
            'get', $val);

        $delSucc = $this->utils->deleteCache($key);
        $this->utils->info_log('try utils delete key cache', $key, 'delSucc', $delSucc);
        // $delSucc = $this->utils->deleteCache();
        // $this->utils->info_log('try utils clear cache', 'delSucc', $delSucc);
        // sleep(30);
        // $redis=try_load_redis($this);
        // $redis->select(0);
        // $flushSucc=$redis->flushDb();
        // $this->utils->info_log('try utils flush cache', 'flushSucc', $flushSucc);

    }

    public function test_count_player_session(){

        $rlt=$this->player_model->countPlayerSession(new DateTime('-1 hour'));
        $this->utils->info_log('test countPlayerSession last hour', $rlt);
    }

    public function test_is_player_session_timeout($sessionId){
        $rlt=$this->player_model->isPlayerSessionTimeout($sessionId);
        $this->utils->info_log('test isPlayerSessionTimeout', $rlt);
    }

    public function test_exists_online_session($playerId){
        $rlt=$this->player_model->existsOnlineSession($playerId);
        $this->utils->info_log('test existsOnlineSession', $rlt);
    }

    public function test_search_player_session($playerId){
        $rlt=$this->player_model->searchPlayerSession($playerId);
        $this->utils->info_log('test searchPlayerSession', $rlt);
    }

    public function test_search_all_player_before_last_activity(){
        $this->load->library('lib_session_of_player');
        $_config4session_of_player = $this->lib_session_of_player->_extractConfigFromParams( $this->utils->getConfig('session_of_player') );

        $time = time();
        $timeout_seconds = $this->utils->getConfig('player_session_timeout_seconds');
        $time_range = $time - $timeout_seconds;
        $ids=[];
		if($_config4session_of_player['sess_use_database']){
	        $this->player_model->db->select('player_id')
	            ->from('ci_player_sessions')
	            ->where('last_activity >', $time_range)
	            ->where('player_id is NOT NULL', NULL, FALSE)
	            ->distinct();
	        // $query = $this->db->get();
	        $data = $this->player_model->runMultipleRowArray();// $query->result_array();

	        $ids =  array_column($data, 'player_id');
	    }else if($_config4session_of_player['sess_use_redis']){
	    	//get from redis
			$specialSessionTable='ci_player_sessions';
	    	$ids=$this->player_model->searchAllObjectIdOnRedis($specialSessionTable, $time_range);
        }else if($_config4session_of_player['sess_use_file']){
            $ids=$this->lib_session_of_player->searchAllObjectIdOnFile($time_range);
        }else{
            $this->utils->error_log('wrong settings, no db, no redis and no file');
	    }
        $this->utils->info_log('test searchAllObjectId', $ids);
    }

    public function test_delete_player_session($player_id){
        $this->load->library('lib_session_of_player');

        $rlt=$this->lib_session_of_player->deleteSessionsByObjectIdOnFile($player_id);
        $this->utils->info_log('test deleteSessionsByObjectIdOnFile', $rlt);
    }

    /// Its Not recommended to used, because performance issue
    public function test_update_all_online_player_status(){
        $rlt=$this->player_model->updateAllOnlinePlayerStatus();
        $this->utils->info_log('test updateAllOnlinePlayerStatus', $rlt);
    }

    /**
     * Generate the session file list
     *
     * Command,
     * sudo /bin/bash ./admin/shell/command.sh test_gen_file_list '/var/tmp' '/var/tmp/file_list.txt'
     *
     * @param string $source_filepath The patch of the session files folder.
     * @param string $output_filename Output to the filename, contains the path.
     * @return void
     */
    public function test_gen_file_list($source_filepath = '/var/tmp/sess_player0718', $output_filename = '/var/tmp/file_list.txt'){
        $this->load->model(['player_session_files_relay']);
        if( substr($source_filepath, -1) != DIRECTORY_SEPARATOR){
            $source_filepath .= DIRECTORY_SEPARATOR;
        }
        $this->player_session_files_relay->gen_file_list_into_file($source_filepath, $output_filename);
    }


    /**
	 * For Simulate Update data with lockAndTransForPlayerBalance()
     *
     * <code>
     * vagrant@default_og_livestablemdb-PHP7:~/Code/og$ sudo /bin/bash ./admin/shell/command.sh test_updateFieldUpdatedAtInWithdrawalCondition 4883488 20 1 > ./logs/testUpdateWithdrawConditionInLocking.log 2>&1 &
     * <code>
     *
	 * @param string $wc_id The field, "withdraw_conditions.id". If its needed, please use "_" connect more id.
	 * @param integer $idleSec For delay commit, the idle time in lockAndTransForPlayerBalance().
	 * @param boolean $ignoreLockResource For ignore Lock Resource String in lockAndTransForPlayerBalance().
	 * To test startTrans()and endTransWithSucc() directly.
	 * @return void
	 */
    public function test_updateFieldUpdatedAtInWithdrawalCondition($wc_id = '4884194', $idleSec = 0, $ignoreLockResource = false){
        $this->load->model(['withdraw_condition']);

        $player_id = 0; // default
		// $wc_id = '4884194'; // player_id=352262
		// $rows = $this->withdraw_condition->getWithdrawConditionByIds([$wc_id]);
        $wc_id_list = explode('_', $wc_id);
        $rows = $this->_getWithdrawConditionByIds($wc_id_list);
		if( ! empty($rows) ){
            $_this = $this;
            foreach( $rows as $index_number => $row){
                $player_id = $row['player_id'];
                if($ignoreLockResource){
                    // Test for Not the same Key protected.
                    $player_id .= '';
                    $player_id .= $this->utils->generateRandomCode();
                }
                $_wc_id = $row['id'];
                $success = $this->lockAndTransForPlayerBalance($player_id, function() use( $_wc_id,  $_this, $idleSec ){
                    $_this->utils->debug_log('OGP-27272.5692.will.testUpdateFieldUpdatedAtInWithdrawalCondition:', $_wc_id );
                    // $rlt = $_this->withdraw_condition->testUpdateFieldUpdatedAtInWithdrawalCondition([$_wc_id]);
                    $rlt = $_this->_testUpdateFieldUpdatedAtInWithdrawalCondition([$_wc_id]);
                    $_this->utils->debug_log('OGP-27272.5692.after.testUpdateFieldUpdatedAtInWithdrawalCondition.rlt:', $rlt );
                    if( ! empty($idleSec) ){
                        $_this->utils->debug_log('OGP-27272.5688.will idleSec',$idleSec);
                        $_this->utils->idleSec($idleSec);
                    }
                    return true;
                });
                $this->utils->debug_log('OGP-27272.5695.after.lockAndTransForPlayerBalance.wc_id:', $_wc_id, 'success', $success );
            } // EOF foreach(){...
		} // EOF if( ! empty($rows) ){...
    } // EOF test_updateFieldUpdatedAtInWithdrawalCondition

    /**
     * For Reproduce Issue Or Check the patch solution,
     * To stay lock(trans-commit) for a long time.
     *
     * Command,
     * sudo /bin/bash ./admin/shell/command.sh test_create_test_player_with_idleSec 20 test0123 > ./logs/test_create_test_player_with_idleSec.log 2>&1 &
     *
     * @param integer $idleSec
     * @param string $username player.username
     * @param string $usepasswordrname
     * @param integer $levelId
     * @param string $mm_key
     * @return void
     */
    public function test_create_test_player_with_idleSec( $idleSec // #1
                                                        , $username // #2
                                                        , $usepasswordrname = 'laifuwifi999' // #3
                                                        , $levelId = 1 // #4
                                                        , $mm_key = _COMMAND_LINE_NULL // #5
    ){
        // $username = 'aris1007';
        // $usepasswordrname = 'aris1001';
        $pass_length = _COMMAND_LINE_NULL; // '_null'
        // $levelId = '9';
        $registered_by = _COMMAND_LINE_NULL; // '_null'
        // $mm_key = 'test_mattermost_notif';
        $tag = 'local';

        $anyid = random_string('numeric', 5);
        $controller = $this;
        $success = $this->player_model->lockAndTrans( Utils::GLOBAL_LOCK_ACTION_SYSTEM_FEATURE
                                                    , $anyid
                                                    , function () use ( $controller, $idleSec, $username, $usepasswordrname, $pass_length, $levelId, $registered_by, $mm_key, $tag ) {
            $controller->create_test_player( $username // #1
                                    , $usepasswordrname // #2
                                    , $pass_length  // #3
                                    , $levelId  // #4
                                    , $registered_by  // #5
                                    , $mm_key  // #6
                                    , $tag  // #7
                                );
            $controller->utils->idleSec($idleSec);
            return true;
        }); // EOF $this->player_model->lockAndTrans(...

    } // EOF test_create_test_player_with_idleSec

    public function test_muti_run_redemption(){
        //sh admin/shell/command_mdb_noroot.sh cny test_muti_run_redemption
        // foreach ([101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120] as $i) {
        foreach ([101,102,103,104,105] as $i) {
        // foreach ([101] as $i) {
            $cmd=$this->utils->generateCommandLine('test_apply_redemption_code', ['A52ec8IARM8x', $i], false);
            $this->utils->runCmd($cmd);
        }
    }

    public function test_apply_redemption_code(
        $redemption_code = _COMMAND_LINE_NULL,
        $player_id = _COMMAND_LINE_NULL,
        $retry = 0,
        $extra_info = []
    ){
        // $sss= random_int(0,3);
        // sleep($sss);
        $this->utils->info_log('test_apply_redemption_code extra_info ', [
            'redemption_code' => $redemption_code,
            'player_id' => $player_id,
            'retry' => $retry,
            'extra_info' => $extra_info
        ]);
        $extra_info['retry'] = $retry;

        // $extra_info = [];
        $cacheKey = 'redemption_code-'.$redemption_code.'-'.$player_id;
        $this->load->model(['static_redemption_code_model']);

        if($player_id == _COMMAND_LINE_NULL){
            $this->utils->info_log('test_apply_redemption_code player_id is null');
            return;
        }

        if($redemption_code == _COMMAND_LINE_NULL){
            $this->utils->info_log('test_apply_redemption_code redemption_code is null');
            return;
        }

        $extra_info[$retry]['redemption_code'] = $redemption_code;
        $this->utils->info_log('test_apply_redemption_code extra_info ',
        var_export([
            "player_id" => $player_id,
            "extra_info" => $extra_info
        ]));
        if(true || method_exists($this, 'request_promo')){
            $this->utils->info_log('test_apply_redemption_code trigger method request_promo');

            if($this->utils->notEmptyTextFromCache($cacheKey) && 1 > $retry){

                $this->utils->debug_log('test_apply_redemption_code redemption_code is using');
                $extra_info[$retry]['error'] = 'notEmptyTextFromCache';
                $this->utils->saveJsonToCache($cacheKey, $extra_info, 30 * 60);
                return;
            } else {
                $this->utils->saveJsonToCache($cacheKey, $extra_info, 30 * 60);
                try {
                    // $this->static_redemption_code_model->startTrans();
                    $extra_info[$retry]['tran_start'] = $this->utils->getNowForMysql();


                    $foundCodeCacheKey = 'check-'.$redemption_code;
                    if($this->utils->notEmptyTextFromCache($foundCodeCacheKey)){
                        $foundCodeCacheValue = $this->utils->getTextFromCache($foundCodeCacheKey);
                        if($foundCodeCacheValue === '8888'){
                            throw new Exception("redemption_code not exist", 1);
                        }
                    } else {
                        $foundCode = $this->static_redemption_code_model->checkRedemptionCodeExist($redemption_code);
                        if($foundCode < 1){
                            $this->utils->saveTextToCache($foundCodeCacheKey, '8888', 30 * 60);
                            throw new Exception("redemption_code not exist", 1);
                        }
                        $this->utils->saveTextToCache($foundCodeCacheKey, 'static', 30 * 60);
                    }

                    // $sss= random_int(0,3);
                    // sleep($sss);
                    $code_detail = $this->static_redemption_code_model->getDetailsByCode($redemption_code);
                    if(empty($code_detail)){
                        throw new Exception("redemption_code out stock", 1);
                    }

                    $code_id = $code_detail['id'];
                    $code_lock_key = 'redemption_code-'.$redemption_code.'-lock-'.$code_id;
                    $extra_info[$retry]['code_detail'] = $code_detail;
                    if($this->utils->notEmptyTextFromCache($code_lock_key)){
                        throw new Exception("current code in used", 9999);
                    }
                    sleep(8);
                    $do_retry = false;
                    $controller = $this;
                    $is_assigned = false;
                    $this->lockAndTransForStaticRedemptionCode($code_lock_key, function () use ($controller, $code_id, $player_id, $code_lock_key, &$extra_info, &$is_assigned , $retry, &$do_retry) {

                        if($this->utils->notEmptyTextFromCache($code_lock_key)){
                            $do_retry = true;
                            return false;
                        }
                        // $code_status = $controller->static_redemption_code_model->getItemField($code_id, 'status');
                        // // sleep(5);
                        // if($code_status != Static_redemption_code_model::CODE_STATUS_UNUSED){
                        //     $do_retry = true;
                        // } else {
                            $controller->utils->saveJsonToCache($code_lock_key, ["player" => $player_id, "time" => $controller->utils->getNowForMysql()], 30 * 60);
                            $controller->utils->saveJsonToCache($code_lock_key.'_update_'.$player_id, $controller->utils->getNowForMysql(), 30 * 60);

                            $is_assigned = $controller->static_redemption_code_model->setAssignedCode($code_id, $player_id);
                            if($is_assigned){
                                $extra_info[$retry]['updateItem'] = $is_assigned;
                                $extra_info[$retry]['updateOn'] = $controller->utils->getNowForMysql();
                            }
                        // }

                        return !empty($is_assigned);
                    });
    				$this->utils->deleteCache($code_lock_key);
                    $extra_info[$retry]['is_assigned'] = $is_assigned;
                    if($do_retry){
                        throw new Exception("current code in used, do retry", 9999);
                    }
                    if(!$is_assigned){
                        throw new Exception("updateItem fail", 9999);
                    }

                    $process = true;
                    $extra_info[$retry]['process'] = $process;

                } catch (\Throwable $th) {
                    $errorCode = $th->getCode();
                    $errorMessage = $th->getMessage();
                    $extra_info[$retry]['errorCode'] = $errorCode;
                    $extra_info[$retry]['errorMessage'] = $errorMessage;
                    if( $errorCode == 9999){
                        //do retry
                        $retry++;
                        return $this->test_apply_redemption_code(
                            $redemption_code,
                            $player_id,
                            $retry,
                            $extra_info
                        );
                    }
                    $this->utils->error_log('test_apply_redemption_code error'. $errorMessage);

                } finally {
                    // $this->static_redemption_code_model->endTransWithSucc();
                    $extra_info[$retry]['tran_end'] = $this->utils->getNowForMysql();
                }
            }

            if(!empty($process)){

                $result = random_int(0, 10);

                if($result < 5){
                    $this->utils->debug_log('test_apply_redemption_code success');
                    $extra_info[$retry]['message_f'] = 'test_apply_redemption_code success';

                } else {
                    $this->utils->debug_log('test_apply_redemption_code failed');
                    $extra_info[$retry]['message_f'] = 'test_apply_redemption_code failed';

                    if(!empty($code_lock_key)){
                        $cacheCodeData = $this->utils->getTextFromCache($code_lock_key);
                        $this->utils->debug_log('test_apply_redemption_code code cache', $cacheCodeData);
                        $this->lockAndTransForStaticRedemptionCode($code_lock_key, function () use ($controller, $code_id, $player_id, $code_lock_key, &$extra_info, &$dddd , $retry) {
                            $controller->utils->saveJsonToCache($code_lock_key.'_release_'.$player_id, $controller->utils->getNowForMysql(), 30 * 60);
                                $dddd = $controller->static_redemption_code_model->releaseAssignedCode($code_id, $player_id);
                                $extra_info[$retry]['releaseItem'] = $dddd;
                                $extra_info[$retry]['releaseOn'] = $controller->utils->getNowForMysql();
                            return true;
                        });
                        $delSucc = $this->utils->deleteCache($code_lock_key);
                    }
                }
            }
            $this->utils->saveJsonToCache($cacheKey, $extra_info, 30 * 60);


            // $ret = $this->request_promo(
            //     9 // #1
            //     , 0 // #2
            //     , null // #3
            //     , false  // #4
            //     , 'ret_to_api'  // #5
            //     , $player_id // #6
            //     , $extra_info// #6.1
            // );
            // $this->utils->info_log('test_apply_redemption_code', $ret);

            if(!empty($cacheKey)){
                $cacheData = $this->utils->getTextFromCache($cacheKey);
                $this->utils->debug_log('test_apply_redemption_code player cache', $cacheData);
            }

            if(!empty($code_lock_key)){
                $cacheCodeData = $this->utils->getTextFromCache($code_lock_key);
                $this->utils->debug_log('test_apply_redemption_code code cache', $cacheCodeData);
            }

        } else {
            $this->utils->error_log('test_apply_redemption_code method not exists');
        }
        $this->utils->info_log('end test_apply_redemption_code');

    }

    public function test_mdbRoulettePrize(){
        $this->load->model(['roulette_api_record']);
        $data['conditions'] = array(
			'by_date_from' => $this->utils->getTodayForMysql(). ' 00:00:00',
			'by_date_to' => $this->utils->getTodayForMysql(). ' 23:59:59',
			'by_prize_from' => $this->utils->getTodayForMysql(). ' 00:00:00',
			'by_prize_to' => $this->utils->getTodayForMysql(). ' 23:59:59',
			'by_username' => '',
			'promoCmsSettingId' => '',
			'by_roulette_name' => '',
			'by_product_id' => '',
			'by_affiliate' => ''
		);

        echo '--------------------------------------'.PHP_EOL;
        echo '--------------------------------------'.PHP_EOL;

        $r_settings = $this->CI->utils->getConfig('roulette_reward_odds_settings');
		$roulette_name_types = roulette_api_record::ROULETTE_NAME_TYPES;

        $active_currency = $this->utils->getActiveTargetDB();
        $fallback_currency_for_roulette_type = $this->utils->getConfig('fallback_currency_for_roulette_type');

		$get_rname = array_keys($r_settings);
		$rname_data = [];
		$rsettings = [];
		foreach ($get_rname as $index => $rname) {
			if (array_key_exists($rname, $roulette_name_types)) {
				//map roulette name array
				$rname_data[$rname] = $roulette_name_types[$rname];
				//map roulette settings array
                $setting_key = $rname_data[$rname];
				$rsettings[$setting_key] = $r_settings[$rname];

                if($active_currency != 'default' && !empty($fallback_currency_for_roulette_type)){
                    $this->utils->info_log('test_mdbRoulettePrize', $rname, $r_settings[$rname]);
                    array_walk($fallback_currency_for_roulette_type, function (&$item, $key) use (&$rsettings, $setting_key, $rname, $fallback_currency_for_roulette_type, $active_currency) {


                        echo "------------------array_walk---------------$key----".PHP_EOL;

                        $item_currency = $item['currency'];
                        $roultte_name =  $item['roultte_name'];
                        if(strtoupper($active_currency) == strtoupper($item_currency) && $rname == $roultte_name && !empty($item['roulette_reward_odds_settings'])){
                            echo "------------------found active_currency $active_currency-$rname-------------------".PHP_EOL;
                            $this->utils->debug_log('test_mdbRoulettePrize', $item, $key, $rsettings, $rname, $fallback_currency_for_roulette_type, $active_currency);
                            $this->utils->debug_log('replace', $rsettings[$setting_key]);
                            $rsettings[$setting_key] =  $item["roulette_reward_odds_settings"];
                            $this->utils->debug_log('update to ', $item["roulette_reward_odds_settings"]);
                        }
                    });
                }
			}
		}
        echo " ".PHP_EOL;
        echo " ".PHP_EOL;
        echo " ".PHP_EOL;
		$data['r_name'] = $rname_data;
		$data['r_settings'] = $rsettings;
		$data['all_prize'] = [];

        echo "----r_name---".PHP_EOL;
        echo json_encode($data['r_name']).PHP_EOL;
        echo "----r_name---".PHP_EOL;
        echo " ".PHP_EOL;
        echo " ".PHP_EOL;

        echo "----r_settings---".PHP_EOL;
        echo json_encode($data['r_settings']).PHP_EOL;
        echo "----r_settings---".PHP_EOL;
        echo " ".PHP_EOL;
        echo " ".PHP_EOL;

		if ($data['conditions']['by_roulette_name'] != '') {
			$rsettings = $rsettings[$data['conditions']['by_roulette_name']] == '' ? false : $rsettings[$data['conditions']['by_roulette_name']];

			if ($rsettings) {
				$ro_data = [];
				foreach ($rsettings as $key => $value) {
					if (isset($value['product_id'])) {
						$ro_data[$value['product_id']] = lang($value['prize']);
					}
				}
				$data['all_prize'] = $ro_data;
			}
		}

        echo "----all_prize---".PHP_EOL;
        echo json_encode($data['all_prize']).PHP_EOL;
        echo "----all_prize---".PHP_EOL;

    }

    public function test_onlyCheckHasUnfinishedWithdrawalCondictionRecords($currency, $player_id, $skipCheck=false) {
        $this->load->model(['withdraw_condition']);
        $this->load->library('playerapi_lib');
        $wc_unfinished = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($player_id, $skipCheck) {
            if(!$skipCheck){
                $hasUnfinishedRecords = $this->withdraw_condition->onlyCheckHasUnfinishedWithdrawalCondictionRecords($player_id);
                if(!$hasUnfinishedRecords){
                    return false;
                }
            }
			return $this->withdraw_condition->existUnfinishWithdrawConditions($player_id);
		});

		$result['data']['allCompleted'] = ($wc_unfinished === false);
        $this->utils->info_log('test_onlyCheckHasUnfinishedWithdrawalCondictionRecords', $result);
    }

    /**
	 * Update the field,"updated_at" only, for Test.
     *
	 * @param null|array $withdrawConditionIds The field, "withdraw_conditions.id" list.
	 * @return boolean Success update or not.
	 */
	public function _testUpdateFieldUpdatedAtInWithdrawalCondition( $withdrawConditionIds=null) {
		$this->load->model(['withdraw_condition']);
		$_tablename = 'withdraw_conditions'; // $this->withdraw_condition->tableName;
		// $this->withdraw_conditions->tableName

        if(empty($withdrawConditionIds)){
            //empty withdraw condition
            return TRUE;
        }

        $data = [];
        $this->withdraw_condition->db->where_in('id', $withdrawConditionIds);
        $data['updated_at'] = $this->utils->getNowForMysql();
		$this->withdraw_condition->db->set($data);

		$row_count = $this->withdraw_condition->runAnyUpdateWithResult($_tablename);
        return !empty($row_count);
    } // EOF _testUpdateFieldUpdatedAtInWithdrawalCondition
    /**
	 * Get the data by id in withdraw_conditions
	 *
	 * @param array $Id_list The array combined by withdraw_conditions.id
	 * @return array The rows array.
	 */
	public function _getWithdrawConditionByIds($Id_list){
		$this->load->model(['withdraw_condition']);
		$_tablename = 'withdraw_conditions'; // $this->withdraw_condition->tableName;
		$_id_list = implode(', ', $Id_list);
		$rows = [];

		$sql = <<<EOF
SELECT *
FROM $_tablename
WHERE id IN ( {$_id_list} )
EOF;
		if ( ! empty($Id_list) ){
			$rows = $this->withdraw_condition->runRawSelectSQLArray($sql, []);
		}

		return $rows;
        // $this->withdraw_condition->db->select('*')
        //     ->from($this->withdraw_condition->tableName)
        //     ->where_in('id', $Id_list);
        // return $this->withdraw_condition->runMultipleRowArray($this->withdraw_condition->db);
    } // EOF _getWithdrawConditionByIds


    // Command,
    // sudo /bin/bash ./admin/shell/command.sh testLockAndTransForPlayerBalanceInLib 3360119 1 > ./logs/testLockAndTransForPlayerBalanceInLib.lock.log 2>&1 &
    // sudo /bin/bash ./admin/shell/command.sh testLockAndTransForPlayerBalanceInLib 3360120 0 > ./logs/testLockAndTransForPlayerBalanceInLib.nonlock.log 2>&1 &
    //
    // ISSUE:
    // 不同玩家，同金流，兩筆。 一筆有加 LOCK 延遲 60秒；一筆沒有加 LOCK。
    // {"message":"Query error: Lock wait timeout exceeded; try restarting transaction Code:1205 sql: UPDATE `player` SET `declined_deposit_count` = (SELECT COUNT(sale_orders.id) FROM sale_orders WHERE sale_orders.player_id = '85513' AND sale_orders.status = '8') WHERE `playerId` =  85513","context":[],"level":400,"level_name":"ERROR","channel":"default-og","datetime":"2023-12-18 16:42:45 208473","extra":{"tags":{"request_id":"6b03ac027320830a5ba61a2bf73b281e","env":"live.og_local","version":"6.214.01.001","hostname":"default-og"},"file":"/home/vagrant/Code/og/submodules/core-lib/system/database/DB_driver.php","line":321,"class":"CI_DB_driver","function":"query","process_id":40520,"memory_peak_usage":"40.25 MB","memory_usage":"38.25 MB"}}
    ///
    // sudo /bin/bash ./admin/shell/command.sh testLockAndTransForPlayerBalanceInLib 3360128 1 > ./logs/testLockAndTransForPlayerBalanceInLib.lock.log 2>&1 &
    // sudo /bin/bash ./admin/shell/command.sh testLockAndTransForPlayerBalanceInLib 3360129 0 > ./logs/testLockAndTransForPlayerBalanceInLib.nonlock.log 2>&1 &
    //
    public function testLockAndTransForPlayerBalanceInLib($saleOrderId = 3360110, $doLock = 0, $idleSec = 60,$doDefinitelyLock = 0){
        $this->load->library(['payment_library']);
        $this->load->model(['sale_order', 'transactions', 'users']);

        $result = null; // for collect
        $_this = $this;
        $saleOrder = $this->sale_order->getSaleOrderById($saleOrderId);
        $player_id = $saleOrder->player_id;

        $actionlogNotes = 'batch set deposit decliend by T1';
        $loggedAdminUserId = Users::SUPER_ADMIN_ID;

        if($doLock){
            $this->payment_library->_lockAndTransForPlayerBalance( $player_id, function()
            use (&$_this, $saleOrderId, $actionlogNotes, $loggedAdminUserId, $idleSec, &$result) {
                // main script

                $saleOrder = $this->sale_order->getSaleOrderById($saleOrderId);
                //
                $_this->sale_order->declineSaleOrder($saleOrderId, $actionlogNotes, null);
                $_this->transactions->createDeclinedDepositTransaction($saleOrder, $loggedAdminUserId, Transactions::MANUAL);
                //
                $_this->sale_order->userUnlockDeposit($saleOrderId);

                $result = 'tested result!';

                $_this->utils->idleSec( $idleSec );
                return true;
            }, $doDefinitelyLock);
        }else{
            $saleOrder = $this->sale_order->getSaleOrderById($saleOrderId);
            //
            $_this->sale_order->declineSaleOrder($saleOrderId, $actionlogNotes, null);
            $_this->transactions->createDeclinedDepositTransaction($saleOrder, $loggedAdminUserId, Transactions::MANUAL);
            //
            $_this->sale_order->userUnlockDeposit($saleOrderId);
        }

        $this->utils->debug_log('testLockAndTransForPlayerBalanceInLib.result:', $result);
    }
    //
    // sudo /bin/bash ./admin/shell/command.sh testUpdatePlayersApprovedDepositCount > ./logs/testUpdatePlayersApprovedDepositCount.log 2>&1 &
    // sudo /bin/bash ./admin/shell/command.sh testUpdatePlayersApprovedDepositCount 0 > ./logs/testUpdatePlayersApprovedDepositCount.log 2>&1 &
    public function testUpdatePlayersApprovedDepositCount($playerIds = '16972_353109_353103_353110'){
        $this->updatePlayersApprovedDepositCount($playerIds);

    }
    // sudo /bin/bash ./admin/shell/command.sh testUpdatePlayersDeclinedDepositCount > ./logs/testUpdatePlayersDeclinedDepositCount.log 2>&1 &
    // sudo /bin/bash ./admin/shell/command.sh testUpdatePlayersDeclinedDepositCount 0 > ./logs/testUpdatePlayersDeclinedDepositCount.log 2>&1 &
    public function testUpdatePlayersDeclinedDepositCount($playerIds = '16972_353109_353103_353110'){
        $this->updatePlayersDeclinedDepositCount($playerIds);
    }
    // sudo /bin/bash ./admin/shell/command.sh testAddPlayerId2refreshPlayersDepositCountWithStatus > ./logs/testAddPlayerId2refreshPlayersDepositCountWithStatus.log 2>&1 &
    public function testAddPlayerId2refreshPlayersDepositCountWithStatus(){
        $this->load->library(['payment_library']);
        $this->load->model(['sale_order']);
        $status = Sale_order::STATUS_SETTLED;
        // $status = Sale_order::STATUS_DECLINED;

        $player_id = '1111'. mt_rand(1000, 9999);
        $this->payment_library->addPlayerId2refreshPlayersDepositCountWithStatus($player_id, $status);
        $player_id = '1111'. mt_rand(1000, 9999);
        $this->payment_library->addPlayerId2refreshPlayersDepositCountWithStatus($player_id, $status);

        $player_id = '1222'. mt_rand(1000, 9999);
        $this->payment_library->addPlayerId2refreshPlayersDepositCountWithStatus($player_id, $status);
        $_cachekey = $this->payment_library->getCachekeyWithStatusOfSaleOrder($status);
        $_playerIdList = $this->payment_library->cronGetData2OperatorSettings($_cachekey);
        $this->utils->debug_log('testAddPlayerId2refreshPlayersDepositCountWithStatus.1104._cachekey:', $_cachekey, '_playerIdList:', $_playerIdList);
        $this->payment_library->removePlayerId2refreshPlayersDepositCountWithStatus($player_id, $status);
    }

    // /bin/bash admin/shell/command_mdb_noroot.sh brl test_getPlayerTotalBetWinLossBySchedule >> logs/command_mdb_noroot-test_getPlayerTotalBetWinLossBySchedule.brl.log 2>&1
    // /bin/bash admin/shell/command_mdb_noroot.sh php test_getPlayerTotalBetWinLossBySchedule >> logs/command_mdb_noroot-test_getPlayerTotalBetWinLossBySchedule.php.log 2>&1
    public function test_getPlayerTotalBetWinLossBySchedule( $playerId = 85513
                                                            , $fromDatetime = '2022-10-02 00:00:00'
                                                            , $toDatetime = '2022-10-02 23:59:59'
                                                            , $enable_multi_currencies_totals = true
    ){

        $schedule = [];
        $schedule['daily'] = 1;
        $rlt = $this->utils->getPlayerTotalBetWinLossBySchedule($playerId, $fromDatetime, $toDatetime, $schedule, $enable_multi_currencies_totals);
        $this->utils->debug_log('OGP-28577.759.rlt:', $rlt);
    } // EOF test_getPlayerTotalBetWinLossBySchedule

    // /bin/bash admin/shell/command_mdb_noroot.sh brl test_groupTotalBetsWinsLossGroupByPlayers >> logs/command_mdb_noroot-test_groupTotalBetsWinsLossGroupByPlayers.brl.log 2>&1
    // /bin/bash admin/shell/command_mdb_noroot.sh php test_groupTotalBetsWinsLossGroupByPlayers >> logs/command_mdb_noroot-test_groupTotalBetsWinsLossGroupByPlayers.php.log 2>&1
    // /bin/bash admin/shell/command_mdb_noroot.sh php test_groupTotalBetsWinsLossGroupByPlayers '2022-10-02 00:00:00' '2022-10-02 23:59:59' 0 >> logs/command_mdb_noroot-test_groupTotalBetsWinsLossGroupByPlayers.php.log 2>&1
    public function test_groupTotalBetsWinsLossGroupByPlayers( $fromDatetime = '2022-10-02 00:00:00'
        , $toDatetime = '2022-10-02 23:59:59'
        , $enable_multi_currencies_totals = true
    ){
        $this->load->library(['group_level_lib']);
        $this->load->model(['total_player_game_day']);
        if($enable_multi_currencies_totals){
            $rlt = $this->group_level_lib->groupTotalBetsWinsLossGroupByPlayersWithForeachMultipleDBWithoutSuper($fromDatetime, $toDatetime);
        }else{
            $rlt = $this->total_player_game_day->groupTotalBetsWinsLossGroupByPlayers($fromDatetime, $toDatetime);
        }
        $this->utils->debug_log('OGP-28577.783.rlt:', $rlt);
    } // EOF test_groupTotalBetsWinsLossGroupByPlayers

    // /bin/bash admin/shell/command_mdb_noroot.sh brl test_getTotalDepositWithdrawalBonusCashbackByPlayers >> logs/command_mdb_noroot-test_getTotalDepositWithdrawalBonusCashbackByPlayers.brl.log 2>&1
    // /bin/bash admin/shell/command_mdb_noroot.sh php test_getTotalDepositWithdrawalBonusCashbackByPlayers >> logs/command_mdb_noroot-test_getTotalDepositWithdrawalBonusCashbackByPlayers.php.log 2>&1
    public function test_getTotalDepositWithdrawalBonusCashbackByPlayers( $playerIds = 132053
                                                                        , $from = '2022-10-02 00:00:00'
                                                                        , $to = '2022-10-02 23:59:59'
                                                                        , $add_manual=false
                                                                        , $enable_multi_currencies_totals = true
    ){
        $this->load->model(['transactions']);
        // getTotalDepositWithdrawalBonusCashbackByPlayers
        $totals = $this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers($playerIds, $from, $to, $add_manual, $enable_multi_currencies_totals);
        // $totals = $this->transactions->getPlayerTotalsByPlayers($playerIds, $from, $to);
        $this->utils->debug_log('OGP-28577.768.totals:', $totals);
    }
    // /bin/bash admin/shell/command_mdb_noroot.sh brl test_getTotalBalDepositWithdrawalBonusCashbackByPlayers >> logs/command_mdb_noroot-test_getTotalBalDepositWithdrawalBonusCashbackByPlayers.brl.log 2>&1
    // /bin/bash admin/shell/command_mdb_noroot.sh php test_getTotalBalDepositWithdrawalBonusCashbackByPlayers >> logs/command_mdb_noroot-test_getTotalBalDepositWithdrawalBonusCashbackByPlayers.php.log 2>&1
    public function test_getTotalBalDepositWithdrawalBonusCashbackByPlayers( $playerIds = 132053
                                                                            , $from = '2022-10-02 00:00:00'
                                                                            , $to = '2022-10-02 23:59:59'
                                                                            , $add_manual=false
                                                                            , $enable_multi_currencies_totals = true
    ){
        $this->load->model(['transactions']);
        $totals = $this->transactions->getTotalBalDepositWithdrawalBonusCashbackByPlayers($playerIds, $from, $to, $enable_multi_currencies_totals);
        $this->utils->debug_log('OGP-28577.773.totals:', $totals);
    }
    // /bin/bash admin/shell/command_mdb_noroot.sh brl test_getPlayerTotalsByPlayersWith getTotalDepositBonusAndBirthdayByPlayers >> logs/command_mdb_noroot-test_getPlayerTotalsByPlayersWith.1.brl.log 2>&1
    // /bin/bash admin/shell/command_mdb_noroot.sh php test_getPlayerTotalsByPlayersWith getTotalDepositBonusAndBirthdayByPlayers >> logs/command_mdb_noroot-test_getPlayerTotalsByPlayersWith.1.php.log 2>&1
    //
    // /bin/bash admin/shell/command_mdb_noroot.sh brl test_getPlayerTotalsByPlayersWith getPlayerTotalDepositWithdrawalBonusByPlayers >> logs/command_mdb_noroot-test_getPlayerTotalsByPlayersWith.2.brl.log 2>&1
    // /bin/bash admin/shell/command_mdb_noroot.sh php test_getPlayerTotalsByPlayersWith getPlayerTotalDepositWithdrawalBonusByPlayers >> logs/command_mdb_noroot-test_getPlayerTotalsByPlayersWith.2.php.log 2>&1
    //
    // /bin/bash admin/shell/command_mdb_noroot.sh brl test_getPlayerTotalsByPlayersWith getPlayerTotalSummaryReport >> logs/command_mdb_noroot-test_getPlayerTotalsByPlayersWith.3.brl.log 2>&1
    // /bin/bash admin/shell/command_mdb_noroot.sh php test_getPlayerTotalsByPlayersWith getPlayerTotalSummaryReport >> logs/command_mdb_noroot-test_getPlayerTotalsByPlayersWith.3.php.log 2>&1
    public function test_getPlayerTotalsByPlayersWith( $methodStr = 'getTotalDepositBonusAndBirthdayByPlayers'
                                                        , $playerIds = 132053
                                                        , $from = '2022-10-02 00:00:00'
                                                        , $to = '2022-10-02 23:59:59'
                                                        , $enable_multi_currencies_totals = false
    ){
        $this->load->model(['transactions']);
        switch($methodStr){
            case 'getTotalDepositBonusAndBirthdayByPlayers':// aka. transactions->getTotalDepositBonusAndBirthdayByPlayers()
            case 'getPlayerTotalDepositWithdrawalBonusByPlayers':// aka. transactions->getPlayerTotalDepositWithdrawalBonusByPlayers()
            case 'getPlayerTotalSummaryReport': // aka. transactions->getPlayerTotalSummaryReport()
                $totals = call_user_func_array([$this->transactions, $methodStr], [$playerIds, $from, $to, $enable_multi_currencies_totals]);
            break;
            default:
                $totals = null;
            break;
        }
        // $totals = $this->transactions->getTotalBalDepositWithdrawalBonusCashbackByPlayers($playerIds, $from, $to);
        $this->utils->debug_log('OGP-28577.795.methodStr:', $methodStr, 'totals:', $totals);
    }

    // /bin/bash admin/shell/command_mdb_noroot.sh brl test_syncVIPGroupFromCurrentToOtherMDB >> logs/command_mdb_noroot-test_syncVIPGroupFromCurrentToOtherMDB.brl.log 2>&1
    // /bin/bash admin/shell/command_mdb_noroot.sh php test_syncVIPGroupFromCurrentToOtherMDB >> logs/command_mdb_noroot-test_syncVIPGroupFromCurrentToOtherMDB.php.log 2>&1
    public function test_syncVIPGroupFromCurrentToOtherMDB($featureKey = 16, $insertOnly=false){
        $this->load->model(['multiple_db_model']);
        $rlt=$this->multiple_db_model->syncVIPGroupFromCurrentToOtherMDB($featureKey, $insertOnly);

        $this->utils->info_log('test_syncVIPGroupFromCurrentToOtherMDB', $rlt);
    }

    // /bin/bash admin/shell/command_mdb_noroot.sh php test_adjustPlayerLevelWithLogs >> logs/command_mdb_noroot-test_adjustPlayerLevelWithLogs.php.log 2>&1
    public function test_adjustPlayerLevelWithLogs( $playerId = 18654
                                                    , $newPlayerLevel = 83
                                                    , $processed_by = Users::SUPER_ADMIN_ID
                                                    , $action_management_title ='Test Command Module' // self::ACTION_MANAGEMENT_TITLE
    ){
        $this->load->library(['group_level_lib']);
        $logsExtraInfo = [];
        $_rlt_list = $this->group_level_lib->adjustPlayerLevelWithLogsWithForeachMultipleDBWithoutSourceDB( $playerId // #1
                                                                                                , $newPlayerLevel // #2
                                                                                                , $processed_by // #3
                                                                                                , $action_management_title // #4
                                                                                                , $logsExtraInfo // #5
                                                                                            );
        $this->utils->debug_log('OGP-28577.860._rlt_list:', $_rlt_list);
        return;

        // $this->load->library(['group_level_lib']);
        // $this->load->model(['multiple_db_model']);
        //
        // $sourceDB = $this->utils->getActiveTargetDB();
        // $readonly = false;
        // $_this = $this;
        //
        // // multiple_db_model->foreachMultipleDBWithoutSuper
        // $_rlt_list = $this->multiple_db_model->foreachMultipleDBWithoutSourceDB( $sourceDB, function($db, &$rlt)
        // use ( $_this, $playerId, $newPlayerLevel, $processed_by, $action_management_title ){ // callback
        //     $logsExtraInfo = [];
        //     $rlt = $_this->group_level_lib->adjustPlayerLevelWithLogs( $playerId
        //         , $newPlayerLevel
        //         , $processed_by
        //         , $action_management_title
        //         , $logsExtraInfo // #5
        //         , $db
        //     );
        //     return $rlt['success']; // success
        //
        // }, $readonly); // EOF $_rlt_list = $this->ci->multiple_db_model->foreachMultipleDBWithoutSuper(...
        //
        // $this->utils->debug_log('OGP-28577.873._rlt_list:', $_rlt_list);

    } // EOF test_adjustPlayerLevelWithLogs

    // Test player_level_upgrade_by_playerId
    // Test player_level_downgrade_by_playerId

    // /bin/bash admin/shell/command_mdb_noroot.sh brl test_syncVIPGroupFromOneToOtherMDBWithFixPKid > logs/command_mdb_noroot-test_syncVIPGroupFromOneToOtherMDBWithFixPKid.php.log 2>&1
    // /bin/bash admin/shell/command_mdb_noroot.sh php test_syncVIPGroupFromOneToOtherMDBWithFixPKid > logs/command_mdb_noroot-test_syncVIPGroupFromOneToOtherMDBWithFixPKid.php.log 2>&1
    public function test_syncVIPGroupFromOneToOtherMDBWithFixPKid( $featureKey = 18){
        $this->load->model(['multiple_db_model']);

        $dryRun = Multiple_db_model::DRY_RUN_MODE_IN_DISABLED;
        $insertOnly=false;
        $sourceDB=$this->utils->getActiveTargetDB();
        $rlt=$this->multiple_db_model->syncVIPGroupFromOneToOtherMDBWithFixPKid($sourceDB, $featureKey, $insertOnly, $dryRun );

        $this->utils->debug_log('OGP-28577.901.rlt:', $rlt);
    }

    /// /bin/bash admin/shell/command_mdb_noroot.sh brl test_syncVIPGroupFromOneToOtherMDBWithFixPKidVer2 > logs/command_mdb_noroot-test_syncVIPGroupFromOneToOtherMDBWithFixPKidVer2.php.log 2>&1
    public function test_syncVIPGroupFromOneToOtherMDBWithFixPKidVer2( $featureKey = 'groupLevelCount_2'){
        $this->load->model(['multiple_db_model']);

        $dryRun = Multiple_db_model::DRY_RUN_MODE_IN_ADD_GROUP;
        $insertOnly=false;
        $sourceDB=$this->utils->getActiveTargetDB();
        $rlt=$this->multiple_db_model->syncVIPGroupFromOneToOtherMDBWithFixPKidVer2($sourceDB, $featureKey, $insertOnly, $dryRun );

        $this->utils->debug_log('OGP-28577.916.rlt:', $rlt);
    }

/* Test Cases:
                          d1                 d2
                     c1   +------------------+    c2
                     +----+------------------+----+
               a   b |    |                  |    | e   f
               |   | |    |                  |    | |   |
     ----------+-+-+-+----+------------------+----+-+-+-+------->   Time
                 |                                    |

               Begin                                 End

CaseA: 2022-11-16 19:44:00                  2022-11-16 19:54:00      difference in minutes

CaseB: 2022-11-16 19:44:00                  2022-11-16 20:56:59      difference in hours
CaseB1:2022-11-16 19:44:00                  2022-11-16 20:02:59      difference within 1 hour
CaseB2:2022-11-16 19:44:00                  2022-11-16 23:02:59      difference over 1 hours more

CaseC: 2022-11-16 19:44:00                  2022-11-17 20:03:59      difference in days
CaseC1:2022-11-16 19:44:00                  2022-11-17 03:03:59      difference within 1 day
CaseC2:2022-11-16 19:44:00                  2022-11-19 20:03:59      difference over 1 days more

(Ingroe by this is not in requirements)
CaseD: 2022-10-16 19:44:00                  2022-11-18 20:03:59      difference in months
CaseD1:2022-10-16 19:44:00                  2022-11-05 20:03:59      difference within 1 month
CaseD2:2022-10-16 19:44:00                  2022-12-18 20:03:59      difference over 2 months more

(Ingroe by this is not in requirements)
CaseE: 2022-11-16 19:44:00                  2023-11-19 20:03:59      difference in years
CaseE1:2022-11-16 19:44:00                  2023-11-10 20:03:59      difference within 1 year
CaseE2:2022-11-16 19:44:00                  2024-11-19 20:03:59      difference over 1 years more
 */
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss > ./logs/test_getPlayerTotalBetWinLoss.log 2>&1 &
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2022-11-16 19:44:00' '2022-11-16 19:54:00' > ./logs/test_getPlayerTotalBetWinLoss.CaseA.log 2>&1 &
    //
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2022-11-16 19:44:00' '2022-11-16 20:56:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseB.log 2>&1 &
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2022-11-16 19:44:00' '2022-11-16 20:02:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseB1.log 2>&1 &
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2022-11-16 19:44:00' '2022-11-16 23:02:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseB2.log 2>&1 &
    //
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2022-11-16 19:44:00' '2022-11-17 20:03:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseC.log 2>&1 &
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2022-11-16 19:44:00' '2022-11-17 03:03:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseC1.log 2>&1 &
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2022-11-16 19:44:00' '2022-11-19 20:03:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseC2.log 2>&1 &
    //
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2022-10-16 19:44:00' '2022-11-18 20:03:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseD.log 2>&1 &
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2022-10-16 19:44:00' '2022-11-05 20:03:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseD1.log 2>&1 &
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2022-10-16 19:44:00' '2022-12-18 20:03:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseD2.log 2>&1 &
    //
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2022-11-16 19:44:00' '2023-11-19 20:03:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseE.log 2>&1 &
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2022-11-16 19:44:00' '2023-11-10 20:03:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseE1.log 2>&1 &
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2022-11-16 19:44:00' '2024-11-19 20:03:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseE2.log 2>&1 &

    // 2024-03-13 08:11:34 to 2024-04-14 08:59:59
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2024-03-13 08:11:34' '2024-04-14 08:59:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseF.log 2>&1 &
    // 2024-03-13 09:00:00 to 2024-04-10 15:59:59
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2024-03-13 09:00:00' ' 2024-04-10 15:59:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseF1.log 2>&1 &
    // 2024-03-13 09:00:00 to 2024-05-15 23:59:59
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2024-03-13 09:00:00' ' 2024-05-15 23:59:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseF1x.log 2>&1 &
    // 2024-01-13 09:00:00 to 2024-04-10 15:59:59
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2024-01-13 09:00:00' ' 2024-04-10 15:59:59' > ./logs/test_getPlayerTotalBetWinLoss.CaseF1y.log 2>&1 &
    // 2023-11-13 16:00:00 to 2024-01-10 16:05:52
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2023-11-13 16:00:00' '2024-01-10 16:05:52' > ./logs/test_getPlayerTotalBetWinLoss.CaseF2.log 2>&1 &
    // 2024-04-24 00:00:00 to 2024-04-25 00:00:00
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2024-04-24 00:00:00' '2024-04-25 00:00:00' > ./logs/test_getPlayerTotalBetWinLoss.CaseF2x.log 2>&1 &

    // issue, 2024-04-24 01:00:00 to 2024-04-24 02:00:00
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2024-04-24 01:00:00' '2024-04-24 02:00:00' > ./logs/test_getPlayerTotalBetWinLoss.CaseT.log 2>&1 &


    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss 16970 '2022-11-16 19:44:00' '2022-11-18 20:03:59' > ./logs/test_getPlayerTotalBetWinLoss.log 2>&1 &
    // sudo /bin/bash ./admin/shell/command.sh test_getPlayerTotalBetWinLoss > ./logs/test_getPlayerTotalBetWinLoss.log 2>&1 &
    public function test_getPlayerTotalBetWinLoss($player_id = '_null', $dateTimeFrom = '_null', $dateTimeTo = '_null'){

        $isNullInPlayer = false;
        if($player_id == '_null'){
            $isNullInPlayer = true;
        }
        $isNullInDateTimeFrom = false;
        if($dateTimeFrom == '_null'){
            $isNullInDateTimeFrom = true;
        }
        $isNullInDateTimeTo = false;
        if($dateTimeTo == '_null'){
            $isNullInDateTimeTo = true;
        }
        if($isNullInPlayer){
            $player_id = 16970;
        }
        if($isNullInDateTimeFrom){
            $dateTimeFrom = '2022-11-16 19:44:00';
        }
        if($isNullInDateTimeTo){
            $dateTimeTo = '2022-11-18 20:03:59';
        }

        if($isNullInDateTimeTo && $isNullInDateTimeTo && $isNullInDateTimeTo){
            // run test script,

            // InDiffDegree : hour
            // $this->_getPlayerTotalBetWinLoss(16970, '2022-11-16 15:44:00', '2022-11-16 18:03:59');
            // $this->_getPlayerTotalBetWinLoss(16970, '2022-11-16 15:44:00', '2022-11-16 16:03:59');
            // $this->_getPlayerTotalBetWinLoss(16970, '2022-11-16 15:44:00', '2022-11-16 16:50:59');

            // // InDiffDegree : day
            // $this->_getPlayerTotalBetWinLoss(16970, '2022-11-16 19:44:00', '2022-11-18 20:03:59');
            // $this->_getPlayerTotalBetWinLoss(16970, '2022-11-17 19:44:00', '2022-11-18 20:03:59');
            // $this->_getPlayerTotalBetWinLoss(16970, '2022-11-17 19:44:00', '2022-11-18 18:03:59');
            //
            // $this->_getPlayerTotalBetWinLoss(16970, '2022-11-16 19:44:00', '2022-11-18 20:03:59');
            // $this->_getPlayerTotalBetWinLoss(16970, '2024-04-24 01:00:00', '2024-04-24 02:00:00');
            // $this->_getPlayerTotalBetWinLoss(16970, '2024-04-24 00:00:00', '2024-04-25 00:00:00');

            // InDiffDegree : year
            $this->_getPlayerTotalBetWinLoss(16970, '2021-11-16 15:44:00', '2022-10-16 18:03:59');
            $this->_getPlayerTotalBetWinLoss(16970, '2021-11-16 15:44:00', '2022-12-16 16:03:59');
            $this->_getPlayerTotalBetWinLoss(16970, '2019-11-16 15:44:00', '2022-11-16 16:50:59');

        }else{
            // run a test
            $this->_getPlayerTotalBetWinLoss($player_id, $dateTimeFrom, $dateTimeTo);
        }

    }
    private function _getPlayerTotalBetWinLoss($player_id = 16970, $dateTimeFrom = '2022-11-16 19:44:00', $dateTimeTo = '2022-11-17 20:02:59', $usePartitionTables = true){
        $this->load->model(['total_player_game_day']);

        $total_player_game_table='total_player_game_day';
        $where_date_field = 'date';
        $where_game_platform_id = null;
        $where_game_type_id = null;
        $db = null;
        $rlt = $this->total_player_game_day->getPlayerTotalBetWinLoss( $player_id // #1
                                                                    , $dateTimeFrom // #2
                                                                    , $dateTimeTo // #3
                                                                    , $total_player_game_table // #4
                                                                    , $where_date_field  // #5
                                                                    , $where_game_platform_id // #6
                                                                    , $where_game_type_id // #7
                                                                    , $db // #8
                                                                    , $usePartitionTables // #9
                                                                );

        // $rlt['total_bet']
        // $rlt['total_loss']
        // $rlt['total_win']

        // game_logs.bet_amount
        // game_logs.win_amount
        // game_logs.loss_amount

        $_rlt4gameLogs = $this->_getBetsFromGameLogsWith($player_id , $dateTimeFrom, $dateTimeTo);

        $isMet2totalBet = false;
        if( $_rlt4gameLogs['total_bet'] == $rlt['total_bet']){
            $isMet2totalBet = true;
        }
        $isMet2totalLoss = false;
        if( $_rlt4gameLogs['total_loss'] == $rlt['total_loss']){
            $isMet2totalLoss = true;
        }
        $isMet2totalWin = false;
        if( $_rlt4gameLogs['total_win'] == $rlt['total_win']){
            $isMet2totalWin = true;
        }

        //
        $results = [];
        $results['bool'] = false;
        $results['args'] = [];
        $results['args']['player_id'] = $player_id;
        $results['args']['dateTimeFrom'] = $dateTimeFrom;
        $results['args']['dateTimeTo'] = $dateTimeTo;


        $results['isMet2totalBet'] = $isMet2totalBet;
        $results['isMet2totalLoss'] = $isMet2totalLoss;
        $results['isMet2totalWin'] = $isMet2totalWin;

        if($isMet2totalBet && $isMet2totalLoss && $isMet2totalWin){
            $results['bool'] = true;
        }
        $results['partition'] = [];
        $results['gameLogs'] = [];

        if(!$isMet2totalBet){
            $results['total_bet_comp'] = [];
            $results['total_bet_comp']['gameLogs'] = $_rlt4gameLogs['total_bet'];
            $results['total_bet_comp']['partition'] = $rlt['total_bet'];
        }else{
            $results['partition']['total_bet'] =  $rlt['total_bet'];
            $results['gameLogs']['total_bet'] =  $_rlt4gameLogs['total_bet'];
        }
        if(!$isMet2totalLoss){
            $results['total_loss_comp'] = [];
            $results['total_loss_comp']['gameLogs'] = $_rlt4gameLogs['total_loss'];
            $results['total_loss_comp']['partition'] = $rlt['total_loss'];
        }else{
            $results['partition']['total_loss'] =  $rlt['total_loss'];
            $results['gameLogs']['total_loss'] =  $_rlt4gameLogs['total_loss'];
        }
        if(!$isMet2totalWin){
            $results['total_win_comp'] = [];
            $results['total_win_comp']['gameLogs'] = $_rlt4gameLogs['total_win'];
            $results['total_win_comp']['partition'] = $rlt['total_win'];
        }else{
            $results['partition']['total_win'] =  $rlt['total_win'];
            $results['gameLogs']['total_win'] =  $_rlt4gameLogs['total_win'];
        }

        $this->utils->debug_log('OGP-33165.1432.results:', $results);
    }

    private function _getBetsFromGameLogsWith($player_id, $dateTimeFrom = '2018-03-12 23:10:00', $dateTimeTo =  '2019-02-11 12:07:42'){
        $this->load->model(['total_player_game_day']);
        $rlt = [];
        // $col4date = 'start_at';
        $col4date = 'end_at';
        // $col4date = 'bet_at';
        // $col4date = 'updated_at';
        $sql =<<<EOF
SELECT player_id
, SUM(bet_amount) as total_bet
, SUM(win_amount) as total_win
, SUM(loss_amount) as total_loss
FROM game_logs
WHERE player_id = ?
AND $col4date  >= ? /* -- begin */
AND $col4date  <= ? /* -- end */
LIMIT 1;
EOF;
		$params = [];
		$params[] = $player_id;
        $params[] = $dateTimeFrom;
        $params[] = $dateTimeTo;
		$rows = $this->total_player_game_day->runRawArraySelectSQL($sql, $params);

        $this->utils->debug_log('OGP-33165.1511.last_query:', $this->total_player_game_day->db->last_query());
		if( ! empty($rows) ){
            foreach ($rows as $row) {
                $rlt = $row;
                break;
            }
        }
        return $rlt;
    }

    public function test_getPlayerTotals($affiliateId) {
        $this->load->model('affiliatemodel');
        $totals = $this->affiliatemodel->getPlayerTotals($affiliateId);
        $this->utils->info_log('Player Totals', $totals);
    }
    public function test_syncAllPlayersWithdrawAndDepositRelatedFields($usingSourceTable = 'OFF', $days=3){


		$this->utils->debug_log('START temporarilySaveAllPlayerSummary');

		$this->load->model(['transactions', 'wallet_model', 'sale_order']);

		// $usingSourceTable = $this->utils->getConfig('sync_player_deposit_withdraw_from_order_table');
		if($usingSourceTable === 'ON'){
			    $this->utils->debug_log('usingSourceTable');
				//region  usingSourceTable
				$totalBettingAmount_qry = '(SELECT ifnull(sum(betting_amount),0) FROM total_player_game_day where total_player_game_day.player_id = player.playerId)';

				$approvedWithdrawAmount_qry = '(SELECT ifnull(sum(transactions.amount),0) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = \''.Transactions::PLAYER.'\' AND transactions.transaction_type = \''.Transactions::WITHDRAWAL.'\' AND transactions.status = \''.Transactions::APPROVED.'\')';

				$approvedWithdrawAmount_qry = '(SELECT ifnull(sum(walletaccount.amount),0) from walletaccount where walletaccount.playerId = player.playerId AND dwStatus = \''.Wallet_model::PAID_STATUS.'\')';

				$totalDepositAmount_qry = '(SELECT ifnull(sum(sale_orders.amount),0) FROM sale_orders WHERE sale_orders.player_id = player.playerId AND sale_orders.status = \''.sale_order::STATUS_SETTLED.'\')';

				$approvedWithdrawCount_qry = '(SELECT COUNT(walletaccount.walletAccountId) FROM walletaccount WHERE walletaccount.playerId = player.playerId AND dwStatus = \''.Wallet_model::PAID_STATUS.'\')';

				$total_deposit_count_qry = '(SELECT COUNT(sale_orders.id) FROM sale_orders WHERE sale_orders.player_id = player.playerId AND sale_orders.status = \''.sale_order::STATUS_SETTLED.'\')';

				$first_deposit_qry = '(SELECT ifnull(sale_orders.amount,0)  FROM sale_orders where sale_orders.player_id = player.playerId AND sale_orders.status = \''.sale_order::STATUS_SETTLED.'\' ORDER BY sale_orders.process_time ASC LIMIT 0,1)';

				$second_deposit_qry = '(SELECT ifnull(sale_orders.amount,0)  from sale_orders where sale_orders.player_id = player.playerId and sale_orders.status = \''.sale_order::STATUS_SETTLED.'\' ORDER BY sale_orders.process_time ASC LIMIT 1,1)';
				//endregion usingSourceTable
		} else {

				// -- START Sub-queries
				$totalBettingAmount_qry = '(SELECT ifnull(sum(betting_amount),0) FROM total_player_game_day where total_player_game_day.player_id = player.playerId)';

				$approvedWithdrawAmount_qry = '(SELECT ifnull(sum(transactions.amount),0) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = \''.Transactions::PLAYER.'\' AND transactions.transaction_type = \''.Transactions::WITHDRAWAL.'\' AND transactions.status = \''.Transactions::APPROVED.'\')';

				$totalDepositAmount_qry = '(select ifnull(sum(amount),0) from transactions where to_type=' . Transactions::PLAYER . ' and transaction_type=' . Transactions::DEPOSIT . ' and status=' . Transactions::APPROVED . ' and player.playerId = transactions.to_id)';

				$approvedWithdrawCount_qry = '(SELECT COUNT(transactions.id) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = \''.Transactions::PLAYER.'\' AND transactions.transaction_type = \''.Transactions::WITHDRAWAL.'\' AND transactions.status = \''.Transactions::APPROVED.'\')';

				$total_deposit_count_qry = '(SELECT COUNT(transactions.id) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = \''.Transactions::PLAYER.'\' AND transactions.transaction_type = \''.Transactions::DEPOSIT.'\' AND transactions.status = \''.Transactions::APPROVED.'\')';

				$first_deposit_qry = '(SELECT ifnull(transactions.amount,0) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = '.Transactions::PLAYER.' AND transactions.transaction_type = '.Transactions::DEPOSIT.' AND transactions.status = '.Transactions::APPROVED.' ORDER BY created_at ASC LIMIT 0,1)';

				$second_deposit_qry = '(SELECT ifnull(transactions.amount,0) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = '.Transactions::PLAYER.' AND transactions.transaction_type = '.Transactions::DEPOSIT.' AND transactions.status = '.Transactions::APPROVED.' ORDER BY created_at ASC LIMIT 1,1)';
				// -- END Sub-queries
		}

		// -- Select query for setting values to be inserted/updated
		$values_select_query = 'SELECT player.playerId, '.$totalBettingAmount_qry.' as totalBettingAmount, '.$approvedWithdrawAmount_qry.' as approvedWithdrawAmount, '.$totalDepositAmount_qry.' as totalDepositAmount, '.$approvedWithdrawCount_qry.' as approvedWithdrawCount, '.$total_deposit_count_qry.' as total_deposit_count, '.$first_deposit_qry.' as first_deposit, '.$second_deposit_qry.' as second_deposit, "'.$this->utils->getNowForMysql().'" as created_at FROM player_runtime as player';
		// $days=$this->utils->getConfig('sync_player_deposit_withdraw_only_for_days');
		if(empty($days)){
			$days=3;
		}
		// query only last 7 days logged player
		// $values_select_query.= "\njoin player_runtime on (player_runtime.playerId=player.playerId) WHERE player_runtime.lastLoginTime >= DATE_SUB(NOW(), INTERVAL ".$days." DAY)";
		$values_select_query.= "\n WHERE player.lastLoginTime >= DATE_SUB(NOW(), INTERVAL ".$days." DAY)";


		// -- Prepare whole insert query

		// -- trucate the table first before inserting new records
		$this->utils->debug_log('Truncating player_summary_tmp table...');
		// $this->db->truncate('player_summary_tmp');
		// $main_query = 'CREATE TEMPORARY TABLE `player_summary_tmp` ' . $values_select_query;
		$main_query = $values_select_query;
		$this->utils->debug_log('Successfully truncated player_summary_tmp table');


		// -- execute insert query
		$this->db->query($main_query);

		$total_affected_rows = $this->db->affected_rows();

		$this->utils->debug_log('TOTAL NUMBER OF INSERTED RECORDS: '.$total_affected_rows);

		$this->utils->debug_log('END temporarilySaveAllPlayerSummary');
    }

    public function test_white_ip_checker(){
        $this->load->model(['White_ip_checker']);
        $xForward=['104.28.198.171'];
        $exists=$this->White_ip_checker->checkWhiteIpForAdmin('172.71.218.160', $xForward);
        $this->utils->info_log("check white ip:", $exists, $xForward);
        $xForward=['119.9.106.90'];
        $exists=$this->White_ip_checker->checkWhiteIpForAdmin('172.71.218.160', $xForward);
        $this->utils->info_log("check white ip:", $exists, $xForward);
    }

}
