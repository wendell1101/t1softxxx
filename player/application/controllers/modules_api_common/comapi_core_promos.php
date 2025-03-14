<?php

/**
 * Api_common core module: promos
 * Separated 10/14/2019
 * @see		api_common.php
 */
trait comapi_core_promos {

    /**
     * Lists promos for promo manager
     *
     * @uses    string  POST:api_key    api key given by system
     * @uses	string	POST:username	Player username
     * @uses	string	POST:token		Effective token for player
     * @uses	int		POST:limit		Limit (page length), for paging
     * @uses	int		POST:offset		Offset, for paging
     * @uses    int     POST:is_deposit return deposit promos if true.  Default: false
     *
     * @return	JSON	Standard JSON return structure [ success, code, mesg, result ]
     *                       With result = [ promos (array), categories (array) ] when successful
     */
	public function listPromos() {
		$api_key = $this->input->post('api_key');
    	if (!$this->__checkKey($api_key)) { return; }
    	$res_mesg = '';

		try {
			$this->load->model([ 'player_model' ]);

    		// Read arguments
    		$token		= $this->input->post('token', true);
    		$username	= $this->input->post('username', true);
    		$limit		= (int) $this->input->post('limit', true);
    		$offset		= (int) $this->input->post('offset', true);
            $is_deposit = !empty($this->input->post('is_deposit', true));
    		$std_creds 		= [ 'api_key' => $api_key, 'username' => $username, 'token' => $token, 'limit' => $limit, 'offset' => $offset ];
    		$this->comapi_log(__METHOD__, 'request', $std_creds);

    		// Check player username
    		$player_id	= $this->player_model->getPlayerIdByUsername($username);
    		if (empty($player_id)) {
    			throw new Exception('Player username invalid', self::CODE_INVALID_USER);
    		}

    		// Check player token
    		$logcheck = $this->_isPlayerLoggedIn($player_id, $token);
    		if ($logcheck['code'] != 0) {
    			throw new Exception($logcheck['mesg'], $logcheck['code']);
    		}

            // check player verified phone
            if ($this->utils->getConfig('enable_sms_verified_phone_in_promotion')) {
                $player_verified_phone = $this->player_model->isVerifiedPhone($player_id);
                if (!$player_verified_phone) {
                    throw new Exception(lang('promo.msg3'), self::CODE_SMSVAL_PLAYER_PHONE_NOT_VERIFIED);
                }
            }

    		// Set limit/offset to null if not specified, so they fall back to Comapi_lib::get_promos_enabled_for_promo_manager() default
    		if (empty($limit))	{ $limit = null; }
    		if (empty($offset))	{ $offset = null; }

    		$promo_res = $this->comapi_lib->get_promos_enabled_for_promo_manager($player_id, $offset, $limit, Comapi_lib::PROMO_SIMPLE, $is_deposit);

    		$mesg = 'Promo(s) for player are retrieved successfully';

    		if (count($promo_res) == 0) {
    			$player = $this->player_model->getPlayerArrayById($player_id);
    			if ($player['disabled_promotion'] == 1) {
    				throw new Exception(lang('Promo disabled for player'), self::CODE_DISABLED_PROMOTION);
    			}
    			$mesg = 'No applicable promo for player at this time';
    		}

            // If everything goes alright
            $ret = [
            	'success'	=> true ,
            	'code'		=> self::CODE_SUCCESS ,
            	'mesg'		=> $mesg,
            	'result'	=> $promo_res
            ];
	    }
	    catch (Exception $ex) {
	    	$this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);

	    	$ret = [
	    		'success'	=> false,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> $ex->getMessage(),
	    		'result'	=> null
	    	];
	    }
	    finally {
	    	$this->returnApiResponseByArray($ret);
	    }
	} // End function listPromos()

    /**
     * Lists all promos, requires no login, without any pre-checks
     *
     * @uses    string  POST:api_key    api key given by system
     * @uses    int     POST:limit      Limit (page length), for paging
     * @uses    int     POST:offset     Offset, for paging
     * @uses    int     POST:is_deposit return deposit promos if true.  Default: false
     *
     * @return  JSON    Standard JSON return structure [ success, code, mesg, result ]
     *                       With result = [ promos (array), categories (array) ] when successful
     */
    public function listPromos2() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }
        $res_mesg = '';

        try {
            $this->load->model([ 'player_model' ]);

            // Read arguments
            $limit      = (int) $this->input->post('limit', true);
            $offset     = (int) $this->input->post('offset', true);
            $is_deposit = !empty($this->input->post('is_deposit', true));
            $std_creds      = [ 'api_key' => $api_key, 'limit' => $limit, 'offset' => $offset ];
            $this->comapi_log(__METHOD__, 'request', $std_creds);

            // Set limit/offset to null if not specified
            if (empty($limit))  { $limit = null; }
            if (empty($offset)) { $offset = null; }

            $promo_res = $this->comapi_lib->get_promos_bare($offset, $limit, Comapi_lib::PROMO_SIMPLE, $is_deposit);

            $mesg = 'Promo(s) for player are retrieved successfully';

            if (count($promo_res) == 0) {
                $mesg = 'No applicable promo for player at this time';
            }

            // If everything goes alright
            $ret = [
                'success'   => true ,
                'code'      => self::CODE_SUCCESS ,
                'mesg'      => $mesg,
                'result'    => $promo_res
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
    } // End function listPromos2()

	/**
	 * Apply some promo available in promo manager
     *
     * @param   bool    $skip_vip_level_check   Skip player VIP level check if true
     *
     * @uses    string  POST:api_key        api key given by system
	 * @uses	string	POST:username		Player username
     * @uses	string	POST:token			Effective token for player
     * @uses	string	POST:promo_cms_id	promo_cms_id of target promo
     *
	 * @return	JSON	Standard JSON return structure [ success, code, mesg, result ]
	 */
	public function applyPromo($skip_vip_level_check = false) {
		$api_key = $this->input->post('api_key');
    	if (!$this->__checkKey($api_key)) { return; }
    	$res_mesg = '';

		try {
			$this->load->model([ 'player_model' , 'player_promo']);

    		// Read arguments
    		$token			= $this->input->post('token', true);
    		$username		= $this->input->post('username', true);
    		$promo_cms_id	= (int) $this->input->post('promo_cms_id', true);

    		$std_creds 		= [ 'api_key' => $api_key, 'username' => $username, 'token' => $token, 'promo_cms_id' => $promo_cms_id ];
    		$this->utils->debug_log(__FUNCTION__, 'request', $std_creds);

    		// Check player username
    		$player_id	= $this->player_model->getPlayerIdByUsername($username);
    		if (empty($player_id)) {
    			throw new Exception('Player username invalid', self::CODE_INVALID_USER);
    		}

    		// Check player token
    		$logcheck = $this->_isPlayerLoggedIn($player_id, $token);
    		if ($logcheck['code'] != 0) {
    			throw new Exception($logcheck['mesg'], $logcheck['code']);
    		}

            if (!$skip_vip_level_check) {
        		// Check promo_cms_id
        		$promo_res = $this->comapi_lib->get_promos_enabled_for_promo_manager($player_id, null, null, Comapi_lib::PROMO_SIMPLE, false, 'skip_precheck');
                // $promo_res = $this->comapi_lib->get_promos_enabled_for_promo_manager($player_id);
        		$pcid_legal = false;
        		if (!empty($promo_cms_id) && !empty($promo_res['promos'])) {
    	    		foreach ($promo_res['promos'] as $p) {
    	    			if ($p['promo_cms_id'] == $promo_cms_id) {
    	    				$pcid_legal = true;
    	    				break;
    	    			}
    	    		}
    	    	}

        		if (!$pcid_legal) {
        			throw new Exception(lang('Invalid promo_cms_id'), self::CODE_PMO_PROMO_CMS_ID_INVALID);
        		}
            }

            $extra_info = ['order_generated_by' => Player_promo::ORDER_GENERATED_BY_PLAYER_CENTER_API, 'player_request_ip' => $this->utils->getIP()];
    		$apply_res = $this->request_promo($promo_cms_id, 0, null, false, 'ret_to_api', $player_id, $extra_info);

    		if ($apply_res['success'] != true) {
    			throw new Exception($apply_res['message'], $apply_res['code']);
            }

            //registering promotion to iovation
            $this->load->library(['iovation_lib']);
            /*$isIovationEnabled = $this->utils->isOperatorSettingItemEnabled('iovation_api_list', 'enabled_iovation_in_promotion') && $this->CI->iovation_lib->isReady;
            if($isIovationEnabled){
                $ioBlackBox = $this->input->post('ioBlackBox', 1);
                if(!empty($ioBlackBox)){
                    $iovation_params = [
                        'player_id' => $player_id,
                        'ip'        => $this->utils->getIP(),
                        'blackbox'  => $ioBlackBox,
                        'promo_cms_setting_id'=>$promo_cms_id,
                    ];
                    $this->comapi_log(__METHOD__, 'Iovation params', $iovation_params);
                    $iovation_resp = $this->iovation_lib->registerPromotionToIovation($iovation_params);
                    $this->comapi_log(__METHOD__, 'Iovation response', $iovation_resp);
                }
            }*/

            $custom_promo_sucess_msg = $this->utils->getConfig('custom_promo_sucess_msg');

            if ($custom_promo_sucess_msg) {
                foreach ($custom_promo_sucess_msg as $cmsId => $lang) {
                    if ($cmsId == $promo_cms_id) {
                        $this->utils->debug_log('--------- lang',lang($custom_promo_sucess_msg[$promo_cms_id]));
                        $apply_res['message'] = lang($custom_promo_sucess_msg[$promo_cms_id]);
                    }
                }
            }

            // If everything goes alright
            $ret = [
            	'success'	=> true ,
            	'code'		=> self::CODE_SUCCESS ,
            	'mesg'		=> lang('Promo request successful'),
            	'result'	=> [ 'message' => $apply_res['message'] ]
            ];
	    }
	    catch (Exception $ex) {
	    	$this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);

            $enable_custom_promo_error_msg_all = $this->utils->getConfig('enable_custom_promo_error_msg_all');

	    	$ret = [
	    		'success'	=> false,
	    		'code'		=> $ex->getCode(),
	    		'mesg'		=> lang('Promo request failed') ,
	    		'result'	=> [ 'message' => $enable_custom_promo_error_msg_all ? lang('custom_promo_error_msg_all') : $ex->getMessage() ]
	    	];
	    }
	    finally {
	    	$this->returnApiResponseByArray($ret);
	    }
	} // End function applyPromo()

    /**
     * Applies given promo available in promo manager
     * Use in pair with listPromo2
     * @uses    string  POST:api_key        api key given by system
     * @uses    string  POST:username       Player username
     * @uses    string  POST:token          Effective token for player
     * @uses    string  POST:promo_cms_id   promo_cms_id of target promo
     *
     * @return  JSON    Standard JSON return structure [ success, code, mesg, result ]
     */
    public function applyPromo2() {
        $this->applyPromo('skip_vip_level_check');
    }

    /**
     * listPromos3: lists promos for promo manager, requires  login, but skips pre-checks for performance issue
     * Comparison of listPromos* series:
     *                  needs   Source of                   Promorules::
     *                  login   promos                      checkOnlyPromotion()
     *                  ======  ==========================  ====================
     *     listPromos   yes     Utils::getPlayerPromo()     yes
     *     listPromos2  no      Promorules::getAllPromo()   no
     *     listPromos3  yes     Utils::getPlayerPromo()     no
     *
     * @uses    string  POST:api_key    api key given by system
     * @uses    string  POST:username   Player username
     * @uses    string  POST:token      Effective token for player
     * @uses    int     POST:limit      Limit (page length), for paging
     * @uses    int     POST:offset     Offset, for paging
     * @uses    int     POST:is_deposit return deposit promos if true.  Default: false
     *
     * @return  JSON    Standard JSON return structure [ success, code, mesg, result ]
     *                       With result = [ promos (array), categories (array) ] when successful
     */
    public function listPromos3() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }
        $res_mesg = '';

        try {
            $this->load->model([ 'player_model' ]);

            // Read arguments
            $token      = $this->input->post('token', true);
            $username   = $this->input->post('username', true);
            $limit      = (int) $this->input->post('limit', true);
            $offset     = (int) $this->input->post('offset', true);
            $is_deposit = !empty($this->input->post('is_deposit', true));
            $promo_category = (int) $this->input->post('promo_category', true);
            $std_creds      = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token, 'limit' => $limit, 'offset' => $offset, 'promo_category' => $promo_category , 'is_deposit' => $is_deposit];
            $this->comapi_log(__METHOD__, 'request', $std_creds);

            // Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Player username invalid', self::CODE_INVALID_USER);
            }

            // Check player token
            $logcheck = $this->_isPlayerLoggedIn($player_id, $token);
            if ($logcheck['code'] != 0) {
                throw new Exception($logcheck['mesg'], $logcheck['code']);
            }

            // check player verified phone
            if ($this->utils->getConfig('enable_sms_verified_phone_in_promotion')) {
                $player_verified_phone = $this->player_model->isVerifiedPhone($player_id);
                if (!$player_verified_phone) {
                    throw new Exception(lang('promo.msg3'), self::CODE_SMSVAL_PLAYER_PHONE_NOT_VERIFIED);
                }
            }

            // Set limit/offset to null if not specified, so they fall back to Comapi_lib::get_promos_enabled_for_promo_manager() default
            if (empty($limit))  { $limit = null; }
            if (empty($offset)) { $offset = null; }

            $promo_res = $this->comapi_lib->get_promos_enabled_for_promo_manager($player_id, $offset, $limit, Comapi_lib::PROMO_SIMPLE, $is_deposit, 'skip_precheck', $promo_category);

            $mesg = 'Promo(s) for player are retrieved successfully';

            // Use proper message if no promo available
            if (count($promo_res) == 0) {
                $player = $this->player_model->getPlayerArrayById($player_id);
                if ($player['disabled_promotion'] == 1) {
                    throw new Exception(lang('Promo disabled for player'), self::CODE_DISABLED_PROMOTION);
                }
                $mesg = 'No applicable promo for player at this time';
            }

            // If everything goes alright
            $ret = [
                'success'   => true ,
                'code'      => self::CODE_SUCCESS ,
                'mesg'      => $mesg,
                'result'    => $promo_res
            ];
        }
        catch (Exception $ex) {
            $this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);

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
    } // End function listPromos3()

    /**
     * Applies given promo available in promo manager
     * Use in pair with listPromo3
     * @uses    string  POST:api_key        api key given by system
     * @uses    string  POST:username       Player username
     * @uses    string  POST:token          Effective token for player
     * @uses    string  POST:promo_cms_id   promo_cms_id of target promo
     *
     * @return  JSON    Standard JSON return structure [ success, code, mesg, result ]
     */
    public function applyPromo3() {
        $this->applyPromo();
    }

     /**
     * lists all promos Categories, requires  login
     * @uses    string  POST:api_key    api key given by system
     *
     * @return  JSON    Standard JSON return structure [ success, code, mesg, result ]
     *                       With result = [ categories (array) ] when successful
     */
    public function listPromoCategories() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }
        $res_mesg = '';

        try {
            $this->load->model([ 'player_model' ]);

            $promo_categories = $this->comapi_lib->get_promo_categories();

            $mesg = 'Promo(s) categories for player are retrieved successfully';

            // Use proper message if no promo available
            if (count($promo_categories) == 0) {
                $mesg = 'No applicable promo categories at this time';
                throw new Exception(lang($mesg), self::CODE_DISABLED_PROMOTION);
            }

            // If everything goes alright
            $ret = [
                'success'   => true ,
                'code'      => self::CODE_SUCCESS ,
                'mesg'      => $mesg,
                'result'    => $promo_categories
            ];
        }
        catch (Exception $ex) {
            $this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);

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
    } // End function listPromoCategories()

    /**
     * listDailySignInPromoStatus: lists custom daily signin promo status, requires  login
     *
     * @uses    string  POST:api_key    api key given by system
     * @uses    string  POST:username   Player username
     * @uses    string  POST:token      Effective token for player
     * @uses    string  POST:promo_cms_id   promo_cms_id of target promo
     *
     * @return  JSON    Standard JSON return structure [ success, code, mesg, result ]
     *                       With result = [ date => status ](array) when successful
     *          date:  date of the month, format: Y-m-d
     *          status: 1. Player able to claim
     *                  2. Player not yet able to claim
     *                  3. Player already claim
     *                  4. Player did not claim
     */
    public function listDailySignInPromoStatus(){
        $api_key = $this->input->post('api_key');
    	if (!$this->__checkKey($api_key)) { return; }

        try {
            $this->load->model([ 'player_model', 'promorules']);

            // Read arguments
            $token			= $this->input->post('token', true);
            $username		= $this->input->post('username', true);
            $promo_cms_id	= (int) $this->input->post('promo_cms_id', true);
            $year	        = (int) $this->input->post('year', true);
            $month	        = (int) $this->input->post('month', true);

            $std_creds      = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token, 'promo_cms_id' => $promo_cms_id, 'year' => $year, 'month' => $month ];
            $this->utils->debug_log(__FUNCTION__, 'request', $std_creds);

            // Check player username
            $player_id	= $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Player username invalid', self::CODE_INVALID_USER);
            }

            // Check player token
            $logcheck = $this->_isPlayerLoggedIn($player_id, $token);
            if ($logcheck['code'] != 0) {
                throw new Exception($logcheck['mesg'], $logcheck['code']);
            }

            $player = $this->player_model->getPlayerArrayById($player_id);
            if ($player['disabled_promotion'] == 1) {
                throw new Exception(lang('Promo disabled for player'), self::CODE_DISABLED_PROMOTION);
            }

            // Check promo_cms_id is valid
            $custom_api_promocmsid = (int)$this->utils->getConfig('comapi_core_promos_signin_api_promocmsid')['daily'];
            if($custom_api_promocmsid !== $promo_cms_id){
                throw new Exception('Promo cms id invalid', self::CODE_PMO_PROMO_CMS_ID_INVALID);
            }

            $result = $this->promorules->getCustomDailySignInPromoStatus($player_id, $promo_cms_id, $year, $month);
                        
            // If everything goes alright
            $ret = [
                'success'	=> true ,
                'code'		=> self::CODE_SUCCESS ,
                'mesg'		=> lang('List daily sign in promo status successful'),
                'result'	=> $result 
            ];

        }
        catch (Exception $ex){
            $this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => lang('List daily sign in promo status failed') ,
                'result'    => [ 'message' => $ex->getMessage() ]
            ];
        }
        finally {
	    	$this->returnApiResponseByArray($ret);
	    }
    } // End function listDailySignInPromoStatus

    /**
     * listMonthlySignInPromoStatus: lists custom monthly signin promo status, requires  login, will pre-checks promo
     *
     * @uses    string  POST:api_key    api key given by system
     * @uses    string  POST:username   Player username
     * @uses    string  POST:token      Effective token for player
     * @uses    string  POST:promo_cms_id   promo_cms_id of target promo
     *
     * @return  JSON    Standard JSON return structure [ success, code, mesg, result ]
     *                       With result = [ month => status ](array) when successful
     *          month, format: Y-m
     *          status: 1. Player able to claim
     *                  2. Player not yet able to claim
     *                  3. Player already claim
     *                  4. Player did not claim
     */
    public function listMonthlySignInPromoStatus(){
        $api_key = $this->input->post('api_key');
    	if (!$this->__checkKey($api_key)) { return; }

        try {
            $this->load->model([ 'player_model', 'promorules']);

            // Read arguments
            $token			= $this->input->post('token', true);
            $username		= $this->input->post('username', true);
            $promo_cms_id	= (int) $this->input->post('promo_cms_id', true);
            $year	= (int) $this->input->post('year', true);
            $month	= (int) $this->input->post('month', true);

            $std_creds = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token, 'promo_cms_id' => $promo_cms_id ];
            $this->utils->debug_log(__FUNCTION__, 'request', $std_creds);

            // Check player username
            $player_id	= $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Player username invalid', self::CODE_INVALID_USER);
            }

            // Check player token
            $logcheck = $this->_isPlayerLoggedIn($player_id, $token);
            if ($logcheck['code'] != 0) {
                throw new Exception($logcheck['mesg'], $logcheck['code']);
            }

            $player = $this->player_model->getPlayerArrayById($player_id);
            if ($player['disabled_promotion'] == 1) {
                throw new Exception(lang('Promo disabled for player'), self::CODE_DISABLED_PROMOTION);
            }
            
            // Check promo_cms_id is valid
            $custom_api_promocmsid = (int)$this->utils->getConfig('comapi_core_promos_signin_api_promocmsid')['monthly'];
            if($custom_api_promocmsid !== $promo_cms_id){
                throw new Exception('Promo cms id invalid', self::CODE_PMO_PROMO_CMS_ID_INVALID);
            }

            $result = $this->promorules->getCustomMonthlySignInPromoStatus($player_id, $promo_cms_id, $year, $month);

            // If everything goes alright
            $ret = [
                'success'	=> true ,
                'code'		=> self::CODE_SUCCESS ,
                'mesg'		=> lang('List monthly sign in promo status successful'),
                'result'	=> $result
            ];

        }
        catch (Exception $ex){
            $this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => lang('List monthly sign in promo status Failed') ,
                'result'    => [ 'message' => $ex->getMessage() ]
            ];
        }
        finally {
	    	$this->returnApiResponseByArray($ret);
	    }
    } // End function listMonthlySignInPromoStatus

} // End of trait comapi_core_promos