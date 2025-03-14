<?php
/**
 * 
 * @property Tournament_model $tournament_model
 */
trait player_tournament_utils_module {
    protected function separateCode($code, $separate = '_') {
        $codeArr = explode($separate, $code);
        $currency = $codeArr[0];
        $id = $codeArr[1];
        return [$currency, $id];
    }
    
    public function _getDistributionTime($distributionTime, $presetFrom, $timeShift = 10) {
        if(!empty($distributionTime)) {
            $_distributionTime = new \DateTime($distributionTime);
        } else {
            $_contestEndedAt = new \DateTime($presetFrom);
            $_distributionTime = $this->utils->getNextTime($_contestEndedAt, '+ '.$timeShift.' days');
        }
        return $this->playerapi_lib->formatDateTime($this->utils->formatDateTimeForMysql($_distributionTime));
    }
    public function applyTournamentEvent($event_id, $player_id){

        try{
            $event = $this->tournament_lib->getEventDetails($event_id);
            if(empty($event)){
                throw new APIException(lang('Tournament event not found.'), Playerapi::CODE_TOURNAMENT_EVENT_NOT_FOUND);
            }
            $verify_function_list = [
                ['name' => 'checkEventStatus', 'params' => [$event]],
                ['name' => 'checkEventTime', 'params' => [$event]],
                ['name' => 'checkEventPlayer', 'params' => [$event_id, $player_id]],
                ['name' => 'checkEventRequirements', 'params' => [$event, $player_id]],
            ];
            foreach ($verify_function_list as $method) {
                $this->utils->debug_log('============processDelete verify_function', $method);
                $verify_result = call_user_func_array([$this, $method['name']], $method['params']);
                $exec_continue = $this->playerapi_lib->checkIfExecContinueAfterVerify($verify_result);
    
                if(!$exec_continue) {
                    throw new APIException($verify_result['error_message'], Playerapi::CODE_TOURNAMENT_APPLY_FAILED);
                }
            }
            $success = $this->tournament_model->applyEvent($event['tournamentId'], $event_id, $player_id);
            if(!$success) {
                throw new APIException(lang('Tournament event apply failed.'), Playerapi::CODE_TOURNAMENT_APPLY_FAILED);
            } 
        }catch(\APIException $e){
            return ['success' => false, 'code' => $e->getCode(), 'message' => $e->getMessage()];
        }
        return ['success' => true];
    }

    public function checkEventStatus($event){
        $verify_result = ['passed' => true, 'error_message' => ''];
		try {
            $eventStatus = $event['eventStatus'];
            $tournamentStatus = $event['tournamentStatus'];
            if($tournamentStatus != tournament_model::STATUS_ACTIVE  && $eventStatus != tournament_model::STATUS_ACTIVE){
                $verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, lang('Tournament event not start yet.'));
            }
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
		}
        return $verify_result;
    }
    public function checkEventTime($event){
        $verify_result = ['passed' => true, 'error_message' => ''];
        try {
            $now = $this->CI->utils->getNowForMysql();
            if($event['tournamentStartedAt'] == null || $event['tournamentEndedAt'] == null){
                $verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, lang('Tournament not start yet.'));
            }
            if($event['tournamentStartedAt'] > $now){
                $verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, lang('Tournament not start yet.'));
            }
            if($event['tournamentEndedAt'] < $now){
                $verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, lang('Tournament is over.'));
            }
            if($event['applyStartedAt'] == null || $event['applyEndedAt'] == null){
                $verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, lang('Tournament apply not start yet.'));
            }
            if($event['applyStartedAt'] > $now){
                $verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, lang('Tournament apply not start yet.'));
            }
            if($event['applyEndedAt'] < $now){
                $verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, lang('Tournament apply is over.'));
            }
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
		}
        return $verify_result;
    }

    public function checkEventPlayer($event_id, $player_id, $reverse = false){
        $verify_result = ['passed' => true, 'error_message' => ''];
		try {
            // $event_id = $event['eventId'];
            $player = $this->tournament_model->checkEventPlayer($event_id, $player_id);
            if(!empty($player) && !$reverse){
                // throw new \APIException(lang('You have already applied for this event.'), Playerapi::CODE_TOURNAMENT_APPLY_FAILED);
                $verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, lang('You have already applied for this event.'));
            }
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, 'Unknown Error during '.__METHOD__);
		}
        return $verify_result;
    }

    public function checkEventRequirements($event, $player_id){
        $verify_result = ['passed' => true, 'error_message' => ''];
		try {
            $this->load->model(['player_model', 'tournament_model']);
            //check deposit amount
            $eventRequirements = $event['eventRequirements'];
            $applyConditionDepositAmount = $eventRequirements['applyConditionDepositAmount'];
            $applyConditionCountPeriod = $eventRequirements['applyConditionCountPeriod'];
            $applyConditionCountPeriodStartAt = $eventRequirements['applyConditionCountPeriodStartAt'];
            $applyConditionCountPeriodEndAt = $eventRequirements['applyConditionCountPeriodEndAt'];
    
            if(floatval($applyConditionDepositAmount) > 0) {
                $start_date = $end_date = null;
                if($applyConditionCountPeriod == tournament_model::EVENT_APPLY_CONDITION_FIXED_DATE){
                    $start_date = $applyConditionCountPeriodStartAt;
                    $end_date = $applyConditionCountPeriodEndAt;
                }
                $result = $this->player_model->getPlayersTotalDeposit([$player_id], $start_date, $end_date);
                if( $result < $applyConditionDepositAmount) {
                    throw new \APIException(lang('tournament.event.deposit.notenough'), Playerapi::CODE_TOURNAMENT_APPLY_FAILED);
                    // $verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, lang('Requirements not meet.'));
                }
            }
            
            //todo
            //check vip
            //check current player
            //check under affiliate
            //check under agent
		} catch (Exception $ex) {
			$this->utils->debug_log('============'. __METHOD__ .' Exception', $ex->getMessage());
            $verify_result['passed'] = false;
            $error_message = $ex->getMessage();
			$verify_result = $this->playerapi_lib->setVerifyResultErrorMsg($verify_result, $error_message);
		}
        return $verify_result;
    }
}