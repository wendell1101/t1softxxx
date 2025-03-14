<?php

/**
 * Api_common core module: Non token-secured methods
 * Separated 9/25/2019
 * @see		api_common.php
 */
trait comapi_core_non_token_secured_methods {

	public function isIpBlocked() {
		$api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        $res = null;

        try {
        	$req_ip		= $this->input->post('req_ip', true);
        	$request = [ 'api_key' => $api_key, 'req_ip' => $req_ip ];
        	$this->comapi_log(__METHOD__, 'request', $request);

        	if (empty(filter_var($req_ip, FILTER_VALIDATE_IP))) {
        		throw new Exception('IP malformed', self::CODE_IPBLK_MALFORMED_IP);
        	}

        	$this->load->model([ 'country_rules' ]);

        	list($req_city, $req_country) = $this->utils->getIpCityAndCountry($req_ip);

        	$flag_is_ip_blocked = $this->country_rules->getBlockedStatus($req_ip);

        	$res_isblock = [
        		'is_blocked'	=> $flag_is_ip_blocked ,
        		'city'			=> $req_city ,
        		'country'		=> $req_country
        	];

            $ret = [
                'success'   => true ,
                'code'      => self::CODE_SUCCESS ,
                'mesg'      => lang('IP blocked status returned successfully') ,
                'result'    => $res_isblock
            ];

            $this->comapi_log(__METHOD__, 'Successful return', $ret);
        }
        catch (Exception $ex) {
            $this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => $res
            ];
        }
        finally {
            $this->returnApiResponseByArray($ret);
        }
	}

    /**
     * Gets all or partial system features
     *
     * @uses    string  POST: api_key       api key given by system
     * @uses    string  POST: feature_names string or comma separated string (xxx,yyy,...)
     * @see     Comapi_lib::gsf_get_sys_features()
     *
     * @return  JSON    Standard JSON return structure
     */
    public function getSysFeatures() {
        $api_key = $this->input->post('api_key');

        if (!$this->__checkKey($api_key)) { return; }

        $res = null;

        try {
            $feature_names = trim($this->input->post('feature_names', true));
            if (!empty($feature_names)) {
                $feature_names = explode(',', $feature_names);
            }

            $creds = [ 'api_key' => $api_key, 'feature_names' => $feature_names ];
            $this->utils->debug_log(__METHOD__, 'request', $creds);

            // Get all or selected features
            list($features_selected, $features_log, $features_mode) = $this->comapi_lib->gsf_get_sys_features($feature_names);

            $res = [ 'features' => $features_selected ];
            $log_res = [ 'features' => $features_log ];

            $ret = [
                'success'   => true,
                'code'      => self::CODE_SUCCESS,
                'mesg'      => "{$features_mode} Feature(s) retrieved successfully",
                'result'    => $res
            ];

            $this->utils->debug_log(__METHOD__, 'response', $log_res, $creds);
        }
        catch (Exception $ex) {
            $this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $creds);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => $res
            ];
        }
        finally {
            $this->returnApiResponseByArray($ret);
        }
    } // End function getSysFeature()

    /**
     * Gets all registration settings from config, mostly obsolete
     * @deprecated
     *
     * @uses    string  POST: api_key       api key given by system
     * @see     Comapi_lib::gsf_get_reg_settings()
     *
     * @return  JSON    Standard JSON return structure
     */
    public function getReg0Settings() {
        $api_key = $this->input->post('api_key');

        if (!$this->__checkKey($api_key)) { return; }

        $res = null;

        try {

            $creds = [ 'api_key' => $api_key ];
            $this->utils->debug_log(__METHOD__, 'request', $creds);

            // Read all register settings
            $reg_settings = $this->comapi_lib->gsf_get_config_reg_settings();

            $res = $reg_settings;

            $ret = [
                'success'   => true,
                'code'      => self::CODE_SUCCESS,
                'mesg'      => "Registration settings retrieved successfully",
                'result'    => $res
            ];

            $this->utils->debug_log(__METHOD__, 'response', $res, $creds);
        }
        catch (Exception $ex) {
            $this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $creds);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => $res
            ];
        }
        finally {
            $this->returnApiResponseByArray($ret);
        }
    } // End function getReg0Settings()

    /**
     * Gets all player registration settings from SBE
     *
     * @uses    string  POST: api_key       api key given by system
     * @see     Comapi_lib::gsf_get_reg_settings()
     *
     * @return  JSON    Standard JSON return structure
     */
    public function getRegSettings() {
        $api_key = $this->input->post('api_key');

        if (!$this->__checkKey($api_key)) { return; }

        $res = null;

        try {

            $creds = [ 'api_key' => $api_key ];
            $this->utils->debug_log(__METHOD__, 'request', $creds);

            // Read all register settings
            $reg_settings = $this->comapi_lib->gsf_get_sbe_reg_settings();

            $res = $reg_settings;

            $ret = [
                'success'   => true,
                'code'      => self::CODE_SUCCESS,
                'mesg'      => "Registration settings retrieved successfully",
                'result'    => $res
            ];

            $this->utils->debug_log(__METHOD__, 'response', $res, $creds);
        }
        catch (Exception $ex) {
            $this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $creds);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => $res
            ];
        }
        finally {
            $this->returnApiResponseByArray($ret);
        }
    } // End function getRegSettings()

    /**
     * Returns all announcement items which are not out-of-date from table cmsnews, OGP-13201
     * Not out-of-date:
     *     - is_daterange == 0 , or
     *     - is_daterange == 1 and today within [ start_date, end_date ]
     * @uses    string  POST:api_key        api key given by system
     * @return  JSON    Standard JSON return structure, array of announcement objects in result
     */
    public function announcements() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        try {
            $this->load->model([ 'cms_model' ]);

            $ann = $this->cms_model->getAllNews(null, null, 'date desc');

            $ann = $this->utils->array_select_fields($ann, [ 'newsId', 'title', 'content', 'start_date', 'end_date', 'name' ]);

            foreach ($ann as & $aitem) {
                $aitem['category'] = $aitem['name'];
                unset($aitem['name']);
            }

            $cats = $this->cms_model->getAllNewsCategoriesSimple();

            $ret = [
                'success'   => true,
                'code'      => 0,
                'mesg'      => lang('Announcements retrieved successfully'),
                'result'    => [ 'cats' => $cats, 'list' => $ann ]
            ];
            $this->comapi_log(__FUNCTION__, 'Successful response', $ret);
        }
        catch (Exception $ex) {
            $ex_log = [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ];
            $this->comapi_log(__FUNCTION__, 'Exception', $ex_log);

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

    } // End function announcements()

    /**
     * Returns all active banners
     * @uses    string  POST:api_key        api key given by system
     * @return  JSON
     */
    public function listBanners() {
        $api_key = $this->input->post('api_key');

        if (!$this->__checkKey($api_key)) { return; }

        $res = null;

        try {

            $request = [ 'api_key' => $api_key ];
            $this->utils->debug_log(__METHOD__, 'request', $request);

            $this->load->library([ 'cmsbanner_library' ]);

            $banners = $this->cmsbanner_library->comapiGetActiveCMSBanners();
            $count_banners = count($banners);

            $ret = [
                'success'   => true,
                'code'      => self::CODE_SUCCESS,
                'mesg'      => "Banners retrieved successfully",
                'result'    => [
                    'count_banners'         => $count_banners ,
                    'banners' => $banners
                ]
            ];

        }
        catch (Exception $ex) {
            $this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $creds);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => $res
            ];
        }
        finally {
            $this->returnApiResponseByArray($ret);
        }

    } // End function listBanners()

    /**
     * Returns game maintenance schedule
     * @uses    string  POST:api_key        api key given by system
     * @uses    string  POST:system_name    == external_system.system_name
     * @uses    date    POST:start_date     start date of maintenance job
     * @uses    int     POST:hide_past      1 to hide past maintenance jobs, 0 to show (default 0)
     *
     * @return  JSON    standard json return structure
     */
    public function gameMaintenanceTime() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        try {
            $this->load->model([ 'comapi_reports' ]);

            $system_name    = trim($this->input->post('system_name', true));
            $start_date     = trim($this->input->post('start_date', true));
            $hide_past      = !empty($this->input->post('hide_past'));

            $request = [ 'system_name' => $system_name, 'start_date' => $start_date, 'hide_past' => $hide_past ];
            $this->utils->debug_log(__METHOD__, 'request', $request);

            $start_date_clean = null;
            if (!empty($start_date)) {
                $start_date_clean = date('Y-m-d', strtotime($start_date));
            }

            $gm_res = $this->comapi_reports->gm_game_maintenance_time($system_name, $start_date_clean, $hide_past);

            if ($gm_res['code'] != 0) {
                throw new Exception($gm_res['mesg'], $gm_res['code']);
            }

            $result = [
                'row_count' => count($gm_res['result']) ,
                'rows'      => $gm_res['result']
            ];

            $ret =  [
                'success'   => true ,
                'code'      => 0 ,
                'mesg'      => 'Game maintenance schedule returned' ,
                'result'    => $result
            ];
        }
        catch (Exception $ex) {

            $this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
        }
        finally {
            $this->returnApiResponseByArray($ret, 'return_empty_array');
        }
    }

} // End of trait comapi_core_non_token_secured_methods
