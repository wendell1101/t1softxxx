<?php
/**
 * Api_common core module: roulette
 * Separated 10/17/2021
 *
 * This trait has following member methods:
 * public function getRouletteSpinTimes()
 * public function getRouletteWinningList()
 * public function applyRoulette()
 * public function fetchBetAndDepositAmount()
 *
 * @see		api_common.php
 */
trait comapi_core_roulette {
    /**
     * @param int playerId
     *
     * @uses    string  POST:api_key        api key given by system
     * @uses    string  POST:username       Player username
     * @uses    string  POST:token          Effective token for player
     * @uses    string  POST:roulette_name  roulette type for class name
     * @uses    int     POST:promo_cms_id   promo cms id
     * @return array ('success' => boolean, 'code' => int, 'message' => lang(), 'result' => array())
     */

    public function getRouletteSpinTimes() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        try {
            // Read arguments
            $token          = trim($this->input->post('token', true));
            $username       = trim($this->input->post('username', true));
            $roulette_name  = $this->input->post('roulette_name', true);
            $promo_cms_id   = (int) $this->input->post('promo_cms_id', true);

            $request = [
                'api_key' => $api_key,
                'username' => $username,
                'token' => $token,
                'roulette_name' => $roulette_name,
                'promo_cms_id' => $promo_cms_id,
            ];

            $this->comapi_log(__METHOD__, 'request', $request);

            // Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Player username invalid', Api_common::CODE_COMMON_INVALID_USERNAME);
            }

            // Check player token
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), Api_common::CODE_COMMON_INVALID_TOKEN);
            }

            $api_name = 'roulette_api_' . $roulette_name;
            $classExists = file_exists(strtolower(APPPATH.'libraries/roulette/'.$api_name.".php"));

            if (!$classExists) {
                throw new Exception(lang('class cannot find ' . $classExists), Api_common::CLASS_NOT_EXISTS);
            }

            $this->load->library('roulette/'.$api_name);
            $roulette_api = $this->$api_name;

            if (!$roulette_api) {
                throw new Exception(lang('Cannot find ' . $api_name . ' api'), Api_common::CANNOT_FIND_ROULETTE_API);
            }

            $moduleType = $roulette_api->getModuleType();
            if ($this->utils->getConfig('enabled_roulette_transactions') && $moduleType === Abstract_roulette_api::MODULE_TYPE_TRANS ) {
               if (empty($promo_cms_id)) {
                    $ret = $this->getRouletteSpinTimesByTransactions($player_id, $roulette_name, $roulette_api);
                    return $ret;
                }
            }

            #Check prmom cms id
            if (empty($promo_cms_id)) {
                $promo_cms_id = $roulette_api->getCmsIdByRouletteName($roulette_name);
                if(empty($promo_cms_id)){

                    throw new Exception(lang('Invalid promo_cms_id'), Api_common::CODE_PMO_PROMO_CMS_ID_INVALID);
                }
            }

            #Check prmom cms id Whitelist
            if (!$this->checkPromoCmsIdWhitelist($promo_cms_id)) {
                throw new Exception(lang('Invalid promo_cms_id not in Whitelist'), Api_common::CODE_PMO_PROMO_CMS_ID_INVALID);
            }

            #Check Available promo
            $promo_res = $this->utils->getPlayerAvailablePromoList($player_id, $promo_cms_id);

            $this->comapi_log(__METHOD__, 'promo_res', $promo_res);

            if (count($promo_res['promo_list']) == 0) {
                throw new Exception(lang('Promo disabled for player'), Api_common::CODE_DISABLED_PROMOTION);
            }

            $roulette_res = $roulette_api->generateRouletteSpinTimes($player_id, $promo_cms_id);
            
            if ($roulette_res['success'] == false) {
                throw new Exception($roulette_res['mesg'], $roulette_res['code']);
            }

            $this->comapi_log(__METHOD__, 'roulette_res', $roulette_res);

            $spin_times_data = $this->utils->safeGetArray($roulette_res, 'spin_times_data', []);
            $rt_res = [
                'total_times' => $this->utils->safeGetArray($spin_times_data, 'total_times'),
                'used_times' => $this->utils->safeGetArray($spin_times_data, 'used_times'),
                'remain_times' => $this->utils->safeGetArray($spin_times_data, 'remain_times'),
                'type' => $roulette_res['type'],
                '_remain_times' => $this->utils->safeGetArray($spin_times_data, 'getRetention'),
                'valid_date' => $this->utils->safeGetArray($spin_times_data, 'valid_date'),
                'available_list' => $this->utils->safeGetArray($spin_times_data, 'available_list',[]),
            ];
		    $rt_res['base'] = $this->utils->safeGetArray($spin_times_data, 'base', 0);

            $ret = [
                'success'   => true,
                'code'      => 0,
                'mesg'      => $roulette_res['mesg'],
                'result'    => isset($rt_res) ? $rt_res : null
            ];
            $this->comapi_log(__METHOD__, 'Successful response', $ret);
        }
        catch (Exception $ex) {
            $ex_log = [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ];
            $this->comapi_log(__METHOD__, 'Exception', $ex_log);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
            
        }
        finally {
            $this->returnApiResponseByArray($ret);
        }

    }// End function getRouletteSpinTimes()

    /**
     * @uses    string  POST:api_key        api key given by system
     * @uses    string  POST:username       Player username
     * @uses    string  POST:token          Effective token for player
     * @uses    string  POST:roulette_name  roulette type for class name
     * @uses    int     POST:promo_cms_id   promo cms id
     * @return array ('success' => boolean, 'code' => int, 'message' => lang(), 'result' => array())
     */
    public function applyRoulette() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        try {
            $this->load->model([ 'player_model' , 'player_promo']);
            // Read arguments
            $token          = trim($this->input->post('token', true));
            $username       = trim($this->input->post('username', true));
            $roulette_name  = $this->input->post('roulette_name', true);
            $promo_cms_id   = (int) $this->input->post('promo_cms_id', true);
            $player_token   = trim($this->input->post('player_token', true));

            $request = [
                'api_key' => $api_key,
                'username' => $username,
                'token' => $token,
                'roulette_name' => $roulette_name,
                'promo_cms_id' => $promo_cms_id,
                'player_token' => $player_token
            ];

            $this->comapi_log(__METHOD__, 'request', $request);

            $roulette_dataset = $request;

            // Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Player username invalid', Api_common::CODE_COMMON_INVALID_USERNAME);
            }

            // Check player token
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), Api_common::CODE_COMMON_INVALID_TOKEN);
            }

            //Check class name
            $api_name = 'roulette_api_' . $roulette_name;
            $classExists = file_exists(strtolower(APPPATH.'libraries/roulette/'.$api_name.".php"));

            if (!$classExists) {
                throw new Exception(lang('class cannot find ' . $classExists), Api_common::CLASS_NOT_EXISTS);
            }

            $this->load->library('roulette/'.$api_name);
            $roulette_api = $this->$api_name;

            if (!$roulette_api) {
                throw new Exception(lang('Cannot find ' . $api_name . ' api'), Api_common::CANNOT_FIND_ROULETTE_API);
            }

            $moduleType = $roulette_api->getModuleType();
            if ($this->utils->getConfig('enabled_roulette_transactions') && $moduleType === Abstract_roulette_api::MODULE_TYPE_TRANS ) {
                if (empty($promo_cms_id)) {
                    $ret = $this->applyRouletteTransactions($player_id, $roulette_name, $roulette_api);
                    return $ret;
                }
            }

            if (empty($promo_cms_id)) {
                $promo_cms_id = $roulette_api->getCmsIdByRouletteName($roulette_name);
                if(empty($promo_cms_id)){

                    throw new Exception(lang('Invalid promo_cms_id'), Api_common::CODE_PMO_PROMO_CMS_ID_INVALID);
                }
            }

            #Check prmom cms id Whitelist
            if (!$this->checkPromoCmsIdWhitelist($promo_cms_id)) {
                throw new Exception(lang('Invalid promo_cms_id not in Whitelist'), Api_common::CODE_PMO_PROMO_CMS_ID_INVALID);
            }

            //Check player Spin Times
            $verify_res = $roulette_api->verifyRouletteSpinTimes($player_id, $promo_cms_id);
            $this->comapi_log(__METHOD__, 'verifyRouletteSpinTimes verify_res', $verify_res);
            if ($verify_res['remain_times'] == 0) {
                throw new Exception(lang('promo_rule.common.error'), Api_common::CODE_REQUEST_PROMOTION_FAIL);
            }

            //get bonus amount from api
            $chance_res = $roulette_api->playerRouletteRewardOdds();
            if ($chance_res['success'] != true) {
                throw new Exception($chance_res['mesg'], $chance_res['code']);
            }
            $bonus = $chance_res['chance_res']['bonus'];

            $extra_info = ['order_generated_by' => Player_promo::ORDER_GENERATED_BY_ROULETTE, 'player_request_ip' => $this->utils->getIP(), 'is_roulette_api' => true, 'bonus_amount' => $bonus, 'player_token' => $player_token];

            if (isset($verify_res['available_list'])) {
                $extra_info['verify_res'] = $verify_res;
            }

            $this->comapi_log(__METHOD__, "roulette apply promo params {$player_id}", ['extra_info' => $extra_info, 'bonus' => $bonus, 'player_token' => $player_token]);

            $msg='';
            $error_code = 0;
            $controller = $this;
            $apply_res = null;
            $roulette_res = null;
            $succ = $this->player_model->lockAndTransForApplyRoulette($player_id, function()
                    use($controller, $player_id, &$extra_info, $promo_cms_id, $verify_res, &$msg, &$error_code, $roulette_api, &$apply_res, &$roulette_res, $bonus, $roulette_name, $chance_res){

                        $apply_res = $controller->request_promo($promo_cms_id, 0, null, false, 'ret_to_api', $player_id, $extra_info);

                        if ($apply_res['success'] != true) {
                            $msg = $apply_res['message'];
                            $error_code = $apply_res['code'];
                            return false;
                            // throw new Exception($apply_res['message'], $apply_res['code']);
                        }
            
                        $after_spin_times = $roulette_api->verifyRouletteSpinTimes($player_id, $promo_cms_id);
            
                        $rt_data = [
                            'player_id' => $player_id,
                            'player_promo_id' => $apply_res['player_promo_request_id'],
                            'promo_cms_id' => $promo_cms_id,
                            'bonus_amount' => $bonus,
                            'created_at' => $this->utils->getNowForMysql(),
                            'type' => $roulette_api->getRouletteType($roulette_name),
                            'notes' => lang('by comapi '. $roulette_name .' applyRoulette'),
                            'product_id' => isset($chance_res['chance_res']['product_id']) ? $chance_res['chance_res']['product_id'] : null,
                            'prize' => json_encode($chance_res['chance_res']),
                            'deposit_amount' => isset($verify_res['deposit']) ? $verify_res['deposit'] : null,
                            'total_times' => $after_spin_times['total_times'],
                            'used_times' => $after_spin_times['used_times'],
                            'valid_date' => $after_spin_times['valid_date'],
                        ];
            
                        $roulette_res = $roulette_api->createRoulette($player_id, $rt_data);
            
                        if ($roulette_res['success'] == false) {
                            $msg = $roulette_res['mesg'];
                            $error_code = $roulette_res['code'];
                            return false;
                            // throw new Exception($roulette_res['mesg'], $roulette_res['code']);
                        }
                        $apply_res['bonus_res'] = $chance_res['chance_res'];
                        unset($apply_res['success']);
                        unset($apply_res['code']);
                        unset($apply_res['player_promo_request_id']);
                        return true;
            });

            if(!$succ) {
                throw new Exception($msg, $error_code);
            }

            $this->comapi_log(__METHOD__, 'roulette_res', $roulette_res);

            $ret = [
                'success'   => true,
                'code'      => 0,
                'mesg'      => $roulette_res['mesg'],
                'result'    => isset($apply_res) ? $apply_res : null
            ];
            $this->comapi_log(__METHOD__, 'Successful response', $ret);
		    $promosetting = $this->promorules->getPromoCmsDetails($promo_cms_id);

            $this->promoTracking($player_id, "TRACKINGEVENT_SOURCE_TYPE_PROMO_APPROVED", $promosetting[0]['promoName'], "Approved");
        }
        catch (Exception $ex) {
            $ex_log = [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ];
            $this->comapi_log(__METHOD__, 'Exception', $ex_log);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
        }
        finally {
            $roulette_api->processAfteraApply($player_id, $promo_cms_id, $roulette_name);
            $this->returnApiResponseByArray($ret);
        }
    }

    public function promoTracking($playerId, $source_type, $title, $status){
		$this->utils->debug_log("======source_type======", $source_type);
		$this->utils->playerTrackingEvent($playerId, $source_type, array(
			'PromoTitle' => $title,
			'Status'	  => $status
		));
	}

    /**
     * @uses    string  POST:api_key        api key given by system
     * @uses    string  POST:username       Player username
     * @uses    string  POST:token          Effective token for player
     * @uses    string  POST:roulette_name  roulette type for class name
     * @uses    int     POST:limit
     * @uses    int     POST:offset
     * @uses    string  POST:start_time
     * @uses    string  POST:end_time
     * @return array ('success' => boolean, 'code' => int, 'message' => lang(), 'result' => array())
     */
    public function listRoulette() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }
        $res_mesg = '';

        try {
            $this->load->model([ 'player_model' ]);

            // Read arguments
            $roulette_name  = $this->input->post('roulette_name', true);
            $username       = trim($this->input->post('username', true));
            $token          = trim($this->input->post('token', true));
            $limit          = (int) $this->input->post('limit', true);
            $offset         = (int) $this->input->post('offset', true);
            $start_time     = trim($this->input->post('start_time', true));
            $end_time       = trim($this->input->post('end_time', true));

            $std_creds      = [ 'api_key' => $api_key, 'limit' => $limit, 'offset' => $offset, 'start_time' => $start_time, 'end_time' => $end_time , 'username' => $username, 'token' => $token, 'roulette_name' => $roulette_name];
            $this->comapi_log(__METHOD__, 'request', $std_creds);

            // Set limit/offset start_time/end_time to null if not specified
            if (empty($limit))  { $limit = null; }
            if (empty($offset)) { $offset = null; }
            if (empty($start_time))  { $limit = null; }
            if (empty($end_time)) { $offset = null; }

            // Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Player username invalid', Api_common::CODE_COMMON_INVALID_USERNAME);
            }

            // Check player token
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), Api_common::CODE_COMMON_INVALID_TOKEN);
            }

            $api_name = 'roulette_api_' . $roulette_name;
            $this->load->library('roulette/'.$api_name);
            $roulette_api = $this->$api_name;

            if (!$roulette_api) {
                throw new Exception(lang('Cannot find ' . $api_name . ' api'), Api_common::CODE_MDN_DEPOSIT_TIME_INVALID);
            }

            // $promo_res = $this->comapi_lib->get_promos_bare($offset, $limit, Comapi_lib::PROMO_SIMPLE, $is_deposit);
            $roulette_res = $roulette_api->getRouletteWinningList($start_time, $end_time, $offset, $limit, $player_id);
            $mesg = 'Roulette(s) for player are retrieved successfully';

            $this->comapi_log(__METHOD__, 'roulette_res', $roulette_res);

            if (count($roulette_res) == 0) {
                $mesg = 'Players have not yet applied for roulette promo';
            }

            // If everything goes alright
            $ret = [
                'success'   => true ,
                'code'      => Api_common::CODE_SUCCESS ,
                'mesg'      => $mesg,
                'result'    => $roulette_res
            ];
        }
        catch (Exception $ex) {
            $this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
        }
        finally {
            $this->returnApiResponseByArray($ret);
        }
    }

    /**
     * @uses    string  POST:api_key        api key given by system
     * @uses    string  POST:username       Player username
     * @uses    string  POST:token          Effective token for player
     * @uses    string  POST:roulette_name  roulette type for class name
     * @uses    string  POST:start_time
     * @uses    string  POST:end_time
     * @return array ('success' => boolean, 'code' => int, 'message' => lang(), 'result' => array())
     */

    public function fetchBetAndDepositAmount() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }
        $res_mesg = '';

        try {
            $this->load->model([ 'player_model' ]);

            // Read arguments
            $token          = trim($this->input->post('token', true));
            $roulette_name  = $this->input->post('roulette_name', true);
            $username       = trim($this->input->post('username', true));
            $start_time     = trim($this->input->post('start_time', true));
            $end_time       = trim($this->input->post('end_time', true));
            $std_creds      = [ 'api_key' => $api_key, 'roulette_name' => $roulette_name, 'username' => $username, 'start_time' => $start_time, 'end_time' => $end_time ];
            $this->comapi_log(__METHOD__, 'request', $std_creds);

            // Set start_time/end_time to null if not specified
            if (empty($start_time))  { $limit = null; }
            if (empty($end_time)) { $offset = null; }

            // Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Player username invalid', Api_common::CODE_COMMON_INVALID_USERNAME);
            }

            // Check player token
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), Api_common::CODE_COMMON_INVALID_TOKEN);
            }

            $api_name = 'roulette_api_' . $roulette_name;
            $this->load->library('roulette/'.$api_name);
            $roulette_api = $this->$api_name;

            if (!$roulette_api) {
                throw new Exception(lang('Cannot find ' . $api_name . ' api'), Api_common::CODE_MDN_DEPOSIT_TIME_INVALID);
            }

            list($bet_amount, $deposit_amount) = $roulette_api->getPlayerBetAndDepositAmount($player_id, $start_time, $end_time);
            $mesg = 'Get player daily bet and deposit amount successfully';

            $amount_res = ['bet_amount' => $bet_amount, 'deposit_amount' => $deposit_amount];
            $this->comapi_log(__METHOD__, 'amount_res', $amount_res);

            // If everything goes alright
            $ret = [
                'success'   => true ,
                'code'      => Api_common::CODE_SUCCESS ,
                'mesg'      => $mesg,
                'result'    => $amount_res
            ];
        }
        catch (Exception $ex) {
            $this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
        }
        finally {
            $this->returnApiResponseByArray($ret);
        }
    }

    public function checkPromoCmsIdWhitelist($promoCmsId){
        $whitelist = $this->utils->getConfig('comapi_roulette_cmsid_whitelist');
        return !empty($whitelist) && in_array($promoCmsId, $whitelist) ;
    }

    /**
     * getRouletteSpinTimesByTransactions
     */
    public function getRouletteSpinTimesByTransactions($player_id, $roulette_name, $roulette_api) {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        try {
            $this->comapi_log(__METHOD__, "{$player_id} roulette_name", $roulette_name);

            $roulette_res = $roulette_api->generateRouletteSpinTimes($player_id, $roulette_name);

            if ($roulette_res['success'] == false) {
                throw new Exception($roulette_res['mesg'], $roulette_res['code']);
            }

            $this->comapi_log(__METHOD__, 'roulette_res', $roulette_res);

            // $rt_res = [
            //     'total_times' => $roulette_res['spin_times_data']['total_times'],
            //     'used_times' => $roulette_res['spin_times_data']['used_times'],
            //     'remain_times' => $roulette_res['spin_times_data']['remain_times'],
            //     'type' => $roulette_res['type'],
            // ];
            $rt_res = $roulette_res['spin_times_data'];
            $rt_res['type'] = $roulette_res['type'];

            $ret = [
                'success'   => true,
                'code'      => 0,
                'mesg'      => $roulette_res['mesg'],
                'result'    => isset($rt_res) ? $rt_res : null
            ];
            $this->comapi_log(__METHOD__, 'Successful response', $ret);
        }
        catch (Exception $ex) {
            $ex_log = [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ];
            $this->comapi_log(__METHOD__, 'Exception', $ex_log);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
        }
        finally {
            return $ret;
        }
    }// End function getRouletteSpinTimesByTransactions()

    /**
     * apply Roulette by Transactions
     * @return array ('success' => boolean, 'code' => int, 'message' => lang(), 'result' => array())
     */
    public function applyRouletteTransactions($player_id, $roulette_name, $roulette_api) {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return array('success' => false); }

        try {
            $this->comapi_log(__METHOD__, "apply Roulette Transactions {$player_id} roulette_name", $roulette_name);
            $this->load->model([ 'player_model']);

            $player = $this->CI->player_model->getPlayerById($player_id);
            $roulette_desc = $roulette_api->rouletteDescription($roulette_name);
            if($player->disabled_promotion && $this->utils->safeGetArray($roulette_desc, 'check_player_disabled_promotion', false)){
                throw new Exception(lang('promo_rule.common.error'), Api_common::CODE_REQUEST_PROMOTION_FAIL);
            }

            //Check player Spin Times
            $verify_res = $roulette_api->verifyRouletteSpinTimes($player_id, $roulette_name);
            $this->comapi_log(__METHOD__, 'verifyRouletteSpinTimes verify_res', $verify_res);
            if ($verify_res['remain_times'] == 0) {
                throw new Exception(lang('You are not suited for this roulette yet'), Api_common::CODE_REQUEST_ROULETTE_FAIL);
            }

            //get bonus amount from api
            $chance_res = $roulette_api->playerRouletteRewardOdds();
            if ($chance_res['success'] != true) {
                throw new Exception($chance_res['mesg'], $chance_res['code']);
            }
            $bonus = $chance_res['chance_res']['bonus'];

            $this->comapi_log(__METHOD__, "roulette apply transactions params {$player_id}", ['bonus' => $bonus, 'verify_res' => $verify_res, 'chance_res' => $chance_res]);

            $msg='';
            $error_code = 0;
            $controller = $this;
            $roulette_res = null;
            $promo_cms_id = 0;//db cannot be null so set to 0
            $admin_user_id = $this->authentication->getUserId() ? $this->authentication->getUserId() : Users::SUPER_ADMIN_ID;
            $bonus_trans_id = null;
            $skip_transaction = false;
            if($bonus == 0 && $this->utils->safeGetArray($chance_res['chance_res'], 'skip_tran', false)) {
                unset($chance_res['chance_res']['skip_tran']);
                $skip_transaction = true;
            }

            $trans_succ = $this->player_model->lockAndTransForPlayerBalance($player_id, function()
                use($controller, $player_id, $promo_cms_id, $verify_res, &$msg, &$error_code, $roulette_api, $bonus, $roulette_name, $chance_res, $admin_user_id, &$bonus_trans_id, $skip_transaction){

                    if($skip_transaction) {
                        return true;
                    }
                    $this->load->model(['transactions', 'wallet_model', 'withdraw_condition']);
                    $this->load->library('authentication');

                    $before_balance = $controller->wallet_model->getMainWalletBalance($player_id);
                    $note = 'admin user: ' . $admin_user_id . ', added roulette bonus amount of: ' . $bonus . ' to ' . $player_id;
                    $controller->comapi_log('roulette transactions note', $note);
                    $bonus_trans_id = $controller->transactions->createRouletteBonusTransaction($admin_user_id, $player_id, $bonus, $before_balance, $note);

                    $controller->comapi_log('roulette bonus_trans_id', $bonus_trans_id);
                    if(empty($bonus_trans_id)){
                        $msg = lang('created roulette transactions failed');
                        $error_code = Api_common::ROULETTE_TRANSACTIONS_FAILED;
                        return false;
                    }

                    #create roulette withdrawal condition
                    $description = $roulette_api->rouletteDescription($roulette_name);
                    $bet_times = isset($description['withdrawal_condition']) ? $description['withdrawal_condition'] : 1;
                    $withdrawal_condition = $bet_times * $bonus;
                    $wc_res = $controller->withdraw_condition->createWithdrawConditionForRouletteBonus($player_id, $bonus_trans_id, $withdrawal_condition, $bonus, $bet_times);

                    $controller->comapi_log('roulette withdrawal condition result', $description, $bet_times, $withdrawal_condition, $wc_res);

                    return true;
            });

            if(!$trans_succ) {
                throw new Exception($msg, $error_code);
            }
            $message = $err_code = '';
            $succ = $this->player_model->lockAndTransForApplyRoulette($player_id, function()
                use($controller, $player_id, $promo_cms_id, $verify_res, &$message, &$err_code, $roulette_api, &$roulette_res, $bonus, $roulette_name, $chance_res, $bonus_trans_id){

                    $after_spin_times = $roulette_api->verifyRouletteSpinTimes($player_id, $roulette_name);
                    $this->comapi_log(__METHOD__, "after_spin_times {$player_id}", ['verify_res' => $verify_res, 'after_spin_times' => $after_spin_times]);

                    $rt_data = [
                        'player_id' => $player_id,
                        // 'player_promo_id' => $apply_res['player_promo_request_id'],
                        'promo_cms_id' => $promo_cms_id,
                        'bonus_amount' => $bonus,
                        'created_at' => $this->utils->getNowForMysql(),
                        'type' => $roulette_api->getRouletteType($roulette_name),
                        'notes' => lang('by comapi '. $roulette_name .' applyRoulette'),
                        'product_id' => isset($chance_res['chance_res']['product_id']) ? $chance_res['chance_res']['product_id'] : null,
                        'prize' => json_encode($chance_res['chance_res']),
                        'deposit_amount' => isset($verify_res['deposit']) ? $verify_res['deposit'] : null,
                        'total_times' => $after_spin_times['total_times'],
                        'used_times' => $after_spin_times['used_times']+1,
                        'valid_date' => $after_spin_times['valid_date'],
                        'transaction_id' => $bonus_trans_id
                    ];

                    $roulette_res = $roulette_api->createRoulette($player_id, $rt_data);

                    if ($roulette_res['success'] == false) {
                        $message = $roulette_res['mesg'];
                        $err_code = $roulette_res['code'];
                        return false;
                    }
                    return true;
            });

            if(!$succ) {
                throw new Exception($message, $err_code);
            }

            $this->comapi_log(__METHOD__, 'roulette_res', $roulette_res);

            $ret = [
                'success'   => true,
                'code'      => 0,
                'mesg'      => $roulette_res['mesg'],
                'result'    => $chance_res['chance_res']
            ];
            $this->comapi_log(__METHOD__, 'Successful response', $ret);
        }
        catch (Exception $ex) {
            $ex_log = [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ];
            $this->comapi_log(__METHOD__, 'Exception', $ex_log);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
        }
        finally {
            return $ret;
        }
    }// End of trait applyRouletteTransactions
} // End of trait comapi_core_roulette
