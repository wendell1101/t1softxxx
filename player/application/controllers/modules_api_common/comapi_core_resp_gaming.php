<?php

/**
 * Api_common core module: responsible gaming
 * Separated 9/03/2021
 *
 * This trait has following member methods:
 *     public function respGamingStatus()
 *
 * @see		api_common.php
 */
trait comapi_core_resp_gaming {

    /**
     * Returns responsible gaming status for player, OGP-23302
     * @uses    string  POST: api_key       api key given by system
     * @uses    string  POST: username      Player username
     * @uses    string  POST: token         Player's login token
     * @return  JSON    General JSON return object [ success, code, message, result ]
     */
    public function respGamingStatus() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        try {
            $token          = $this->input->post('token', true);
            $username       = $this->input->post('username', true);

            $request        = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token ];
            $this->comapi_log(__METHOD__, 'request', $request);

            // 0a: Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception(lang('Player username invalid'), self::CODE_COMMON_INVALID_USERNAME);
            }

            // 0b: Check player token
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
            }

            $rg_stat = $this->comapi_lib->rg_resp_gaming_status($player_id);

            // Point of success --------------------------------------------------------
            $ret = [
                'success'   => true,
                'code'      => 0,
                'mesg'      => lang('Responsible gaming status returned'),
                'result'    => $rg_stat
            ];
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
            $this->comapi_log(__METHOD__, 'Response', $ret);
            $this->returnApiResponseByArray($ret, 'allow_empty');
        }
    } // End function respGamingStatus()

    /**
     * Returns responsible gaming status for player without login (undocumented), OGP-23302
     * @uses    string  POST: api_key       api key given by system
     * @uses    string  POST: username      Player username
     * @return  JSON    General JSON return object [ success, code, message, result ]
     */
    public function respGamingStatus2() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        try {
            $username       = $this->input->post('username', true);

            $request        = [ 'api_key' => $api_key, 'username' => $username ];
            $this->comapi_log(__METHOD__, 'request', $request);

            // 0a: Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception(lang('Player username invalid'), self::CODE_COMMON_INVALID_USERNAME);
            }

            $rg_stat = $this->comapi_lib->rg_resp_gaming_status($player_id, 'not_logged_in');

            // Point of success --------------------------------------------------------
            $ret = [
                'success'   => true,
                'code'      => 0,
                'mesg'      => lang('Responsible gaming status returned'),
                'result'    => $rg_stat
            ];
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
            $this->comapi_log(__METHOD__, 'Response', $ret);
            $this->returnApiResponseByArray($ret, 'allow_empty');
        }
    } // function respGamingStatus2()

    /**
     * Returns options for responsible gaming requests, OGP-23302
     * @uses    string  POST: api_key       api key given by system
     * @uses    string  POST: username      Player username
     * @uses    string  POST: token         Player's login token
     * @return  JSON    General JSON return object [ success, code, message, result ]
     */
    public function respGamingForm() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        try {
            $token          = $this->input->post('token', true);
            $username       = $this->input->post('username', true);

            $request        = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token ];
            $this->comapi_log(__METHOD__, 'request', $request);

            // 0a: Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception(lang('Player username invalid'), self::CODE_COMMON_INVALID_USERNAME);
            }

            // 0b: Check player token
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
            }

            $rg_form = $this->comapi_lib->rg_resp_gaming_form($player_id);

            // Point of success --------------------------------------------------------
            $ret = [
                'success'   => true,
                'code'      => 0,
                'mesg'      => lang('Responsible gaming status returned'),
                'result'    => $rg_form
            ];
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
            $this->comapi_log(__METHOD__, 'Response', $ret);
            $this->returnApiResponseByArray($ret, 'allow_empty');
        }
    } // End function respGamingForm()

    /**
     * Sends responsible gaming request for player, OGP-23302
     * @uses    string  POST: api_key       api key given by system
     * @uses    string  POST: username      Player username
     * @uses    string  POST: token         Player's login token
     * @uses    string  POST: rg_type       type of resp gaming request, either of 'time_out', 'self_exclusion', 'deposit_limits'
     * @uses    int     POST: excl_type     type of self exclusion, 1 for temporary, 2 for permanent
     * @uses    decimal POST: amount        amount for deposit limits requests
     * @return  JSON    General JSON return object [ success, code, message, result ]
     */
    public function respGamingRequest() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        $this->load->model([ 'common_token' ]);

        $ex_extra = [];
        try {
            $token          = $this->input->post('token', true);
            $username       = $this->input->post('username', true);
            $rg_type        = trim($this->input->post('rg_type', true));
            $len_option     = intval($this->input->post('len_option', true));
            $excl_type      = intval($this->input->post('excl_type', true));
            $amount         = floatval($this->input->post('amount', true));

            $request        = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token ];
            $this->comapi_log(__METHOD__, 'request', $request);

            // 0a: Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception(lang('Player username invalid'), self::CODE_COMMON_INVALID_USERNAME);
            }

            // 0b: Check player token
            if (!$this->__isLoggedIn($player_id, $token)) {
                throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
            }

            $rg_result = null;
            switch ($rg_type) {
                case 'time_out' :
                    if (empty($len_option)) {
                        $ex_extra['args_missing'] = 'len_option';
                        throw new Exception(lang('Required argument(s) missing'), self::CODE_COMMON_REQUIRED_ARG_MISSING);
                    }

                    $rg_res = $this->comapi_lib->rg_resp_gaming_request(comapi_lib::RG_REQ_TIME_OUT, $player_id, $len_option);

                    if ($rg_res['code'] != 0) {
                        throw new Exception($rg_res['mesg'], $rg_res['code']);
                    }

                    $rg_result = $rg_res['result'];

                    if ($rg_result['days_before_start'] <= 0) {
                        $this->common_token->disablePlayerAvailableToken($player_id);
                    }

                    break;

                case 'self_exclusion' :
                    if (empty($excl_type)) {
                        $ex_extra['args_missing'] = 'excl_type';
                        throw new Exception(lang('Required argument(s) missing'), self::CODE_COMMON_REQUIRED_ARG_MISSING);
                    }
                    if (!in_array($excl_type, [ comapi_lib::RG_SELF_EXCL_TYPE_TEMP, comapi_lib::RG_SELF_EXCL_TYPE_PERM ])) {
                        $ex_extra['args_illegal'] = 'excl_type';
                        throw new Exception(lang('Invalid value for argument'), self::CODE_COMMON_INVALID_VALUE_FOR_ARG);
                    }
                    if ($excl_type == comapi_lib::RG_SELF_EXCL_TYPE_TEMP && empty($len_option)) {
                        $ex_extra['args_missing'] = 'len_option';
                        throw new Exception(lang('Required argument(s) missing'), self::CODE_COMMON_REQUIRED_ARG_MISSING);
                    }

                    // $rg_res = $this->comapi_lib->rg_request_self_excl($excl_type, $len_option);
                    $rg_res = $this->comapi_lib->rg_resp_gaming_request(comapi_lib::RG_REQ_SELF_EXCLUSION, $player_id, $len_option, [ 'excl_type' => $excl_type ]);

                    if ($rg_res['code'] != 0) {
                        throw new Exception($rg_res['mesg'], $rg_res['code']);
                    }

                    $rg_result = $rg_res['result'];

                    if ($rg_result['days_before_start'] <= 0) {
                        $this->common_token->disablePlayerAvailableToken($player_id);
                    }

                    break;

                case 'deposit_limits' :
                    if (empty($len_option)) {
                        $ex_extra['args_missing'] = 'len_option';
                        throw new Exception(lang('Required argument(s) missing'), self::CODE_COMMON_REQUIRED_ARG_MISSING);
                    }

                    if (empty($amount) || $amount <= 0) {
                        $ex_extra['args_illegal'] = 'amount';
                        throw new Exception(lang('Invalid value for argument'), self::CODE_COMMON_INVALID_VALUE_FOR_ARG);
                    }

                    // $rg_res = $this->comapi_lib->rg_request_dep_limits($amount, $len_option);
                    $rg_res = $this->comapi_lib->rg_resp_gaming_request(comapi_lib::RG_REQ_DEPOSIT_LIMITS, $player_id, $len_option, [ 'amount' => $amount ]);

                    if ($rg_res['code'] != 0) {
                        throw new Exception($rg_res['mesg'], $rg_res['code']);
                    }

                    break;

                default :
                    throw new Exception(lang('Resp gaming type illegal'), self::CODE_RPG_RESP_GAMING_TYPE_ILLEGAL);
                    break;
            }

            // Point of success --------------------------------------------------------
            $ret = [
                'success'   => true,
                'code'      => 0,
                'mesg'      => lang('Responsible gaming request sent'),
                'result'    => $rg_result
            ];
        }
        catch (Exception $ex) {
            $ex_log = [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ];
            $this->comapi_log(__METHOD__, 'Exception', $ex_log);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => $ex_extra
            ];
        }
        finally {
            $this->comapi_log(__METHOD__, 'Response', $ret);
            $this->returnApiResponseByArray($ret);
        }
    } // End function respGamingRequest()


} // End trait comapi_core_withdraw