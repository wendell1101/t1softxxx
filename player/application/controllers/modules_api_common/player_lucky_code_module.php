<?php

/**
 * uri: /wallets
 *
 * @property playerapi_lib $playerapi_lib
 * @property Playerapi_model $playerapi_model
 * @property lucky_code $lucky_code
 */
trait player_lucky_code_module {

    public function lucky_code($action, $additional=null){
        if(!$this->initApi()){
            return;
        }
        $this->load->library(['playerapi_lib', 'payment_library', 'player_library']);
		$this->load->model(['comapi_reports', 'playerapi_model', 'player_model', 'lucky_code']);
		$request_method = $this->input->server('REQUEST_METHOD');
        switch($action){
            case 'list':
                if($request_method == 'GET'){
                    return $this->get_lucky_code_list($additional);
                }
                break;
        }

        return $this->returnErrorWithCode(self::CODE_SERVER_ERROR);
    }

    private function get_lucky_code_list($additional){
        $result = ['code' => Playerapi::CODE_OK];
        try {
            $lucky_code_config = $this->utils->getConfig('fallback_currency_for_lucky_code');
            if(empty($lucky_code_config)){
                $_error_message = lang('not_enable');
                $this->utils->debug_log($_error_message);
                throw new APIException($_error_message, Playerapi::CODE_GET_LUCKY_CODE_ERROR);
            }
            
            $playerId = $this->player_id;
            $currency = $lucky_code_config['currency'];
            $request_body = $this->playerapi_lib->getRequestPramas();
            $this->utils->debug_log("get_lucky_code_list-$playerId request_body", $request_body);

            $output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($playerId, $currency, $request_body){
                $limit = isset($request_body['limit'])? $request_body['limit'] : 20;
                $page = isset($request_body['page'])? $request_body['page'] : 1;
                $period_id = $this->utils->safeGetArray($request_body,'period', '');

                $cache_key="getLuckyCode-$currency-$playerId-$period_id-$limit-$page";
                $cachedResult = $this->utils->getJsonFromCache($cache_key);
                if(!empty($cachedResult)) {
                    $this->utils->debug_log(__METHOD__, 'get records from cache', [ 'cachedResult' => $cachedResult]);
                    // $cachedResult['isCache'] = true;
                    return $cachedResult;
                }

                $current_period = $this->get_lucky_code_period($period_id);
                if(empty($current_period['id'])){
                    $_error_message = lang('period not found');
                    $this->utils->debug_log($_error_message);
                    throw new APIException($_error_message, Playerapi::CODE_GET_LUCKY_CODE_ERROR);
                }
                // $current_period_name = $this->utils->safeGetArray($current_period,'period_name', '');
                // $player_info = $this->playerapi_model->getPlayerInfoByPlayerId($playerId);
                // $player_info['username_on_register'] = $this->player_functions->get_username_on_register($playerId);
                $period_id = $current_period['id'];
                $result = $this->lucky_code->get_player_code_list_pagination($playerId, $period_id, $limit, $page);
                $result['period'] = [
                        "id" => $current_period['id'],
                        "name" => $current_period['period_name'],
                        "start" => $current_period['start_date'],//"2023-01-01 00:00:00",
                        "end"=> $current_period['end_date'],
                        // "_period" => $current_period
                    ];

                $ttl = 10 * 60; //10 minutes
                $this->utils->saveJsonToCache($cache_key, $result, $ttl);
                return $result;

            });
        } catch (\Throwable $th) {
            $_error_message = $th->getMessage();
			$_error_code = $th->getCode();
			$result['code'] = $_error_code;
			$result['errormessage'] = $_error_message;
			$this->returnErrorWithResult($result);
            return;
        }
        $result['data'] = $output;
        return $this->returnSuccessWithResult($result);
    }

    private function get_lucky_code_period($period_id = null){
		$this->load->model('lucky_code');
        $_period = false;
        $current_time = $this->utils->getNowForMysql();
        if($period_id != null && is_numeric($period_id)){
            $_period = $this->lucky_code->getLuckyCodePeriodByPeriodName($period_id);
        } else {
            $_period = $this->lucky_code->getLuckyCodePeriodByCurrentTime($current_time);
            if(empty($_period)){
                $_period = $this->lucky_code->getLastPeriodByCurrentTime($current_time);
                $this->utils->debug_log('getLuckyCodePeriodByCurrentTime is empty, try getLastPeriodByCurrentTime');
            }
        }
        $this->utils->debug_log('current_time: '. $current_time .' period_id:'. $period_id, array('period' => $_period));

        if(empty($_period)){
            return false;
        }
        
        return  $_period;
    }

}
