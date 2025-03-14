<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-25340

condition:
{
    "class": "promo_rule_smash_newbet_bonus",
    "bonus_turnover" : 0.07,
    "bonus_settings":{
        "R1"  : {"rate": 50},
        "R2"  : {"rate": 18.75},
        "R3"  : {"rate": 11.25},
        "R4"  : {"rate": 5.63},
        "R5"  : {"rate": 4.38},
        "R6"  : {"rate": 3.75},
        "R7"  : {"rate": 2.5},
        "R8"  : {"rate": 1.88},
        "R9"  : {"rate": 1.25},
        "R10" : {"rate": 0.63},
    },
    "release_settings": {
        "count" : "-1day",
        "time" : "01:00"
    }
}

 *
 *
 */
class Promo_rule_smash_newbet_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_smash_newbet_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
    protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$success = false;
		$errorMessageLang = null;
		// $allowed_date = $description['allowed_date'];
		$bonus_settings = $description['bonus_settings'];
		$fromDate = !empty($extra_info['sync_date']) ? $extra_info['sync_date'] : $this->get_date_type(self::DATE_YESTERDAY_START);
		$toDate = !empty($extra_info['sync_date']) ? $extra_info['sync_date'] : $this->get_date_type(self::DATE_YESTERDAY_END);
		$today = $this->utils->getTodayForMysql();

		if($this->process_mock('today', $today)){
			//use mock data
			$this->appendToDebugLog('use mock today', ['today'=>$today]);
		}

		$this->appendToDebugLog('runBonusConditionChecker check date', ['fromDate'=>$fromDate, 'toDate'=>$toDate, 'today'=>$today]);


		$result = $this->checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

		if(array_key_exists('bonus_amount',$result)){
			unset($result['bonus_amount']);
		}

		if(array_key_exists('deposit_amount',$result)){
			unset($result['deposit_amount']);
		}

		return $result;
	}

	/**
	 * generate withdrawal condition
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
	 */
	protected function generateWithdrawalCondition($description, &$extra_info, $dry_run){

		return $this->returnUnimplemented();
	}

    /**
     * generate transfer condition
     * @param  array $description original description in rule
     * @param  array $extra_info exchange data
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message_lang'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
     */
    protected function generateTransferCondition($description, &$extra_info, $dry_run){
        return $this->returnUnimplemented();
    }

	/**
	 * release bonus
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'bonus_amount'=> bonus amount]
	 */
    protected function releaseBonus($description, &$extra_info, $dry_run){
		$success = false;
		$errorMessageLang = null;
		// $allowed_date = $description['allowed_date'];
		$bonus_settings = $description['bonus_settings'];
		$fromDate = !empty($extra_info['sync_date']) ? $extra_info['sync_date'] : $this->get_date_type(self::DATE_YESTERDAY_START);
		$toDate = !empty($extra_info['sync_date']) ? $extra_info['sync_date'] : $this->get_date_type(self::DATE_YESTERDAY_END);
		$today = $this->utils->getTodayForMysql();

		if($this->process_mock('today', $today)){
			//use mock data
			$this->appendToDebugLog('use mock today', ['today'=>$today]);
		}

		$this->appendToDebugLog('runBonusConditionChecker check date', ['fromDate'=>$fromDate, 'toDate'=>$toDate, 'today'=>$today]);


		$request = $this->checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, $extra_info, $description, $errorMessageLang);

        return $request;
	}

    private function checkCustomizeBonusCondition($bonus_settings, $fromDate, $toDate, &$extra_info, $description, &$errorMessageLang, $dry_run = false)
    {
        $success = true;
	    $bonus_amount = 0;
        $rank_name = 'newbet';
        $this->load->model(['player_score_model']);
	    // $errorMessageLang =  lang('notify.79');

        // get jackpot bonus
        $syncDate = $fromDate;
        if (is_object($syncDate) && $syncDate instanceof DateTime) {
            $syncDate = $syncDate->format('Y-m-d');
        }

        //for syncPlayerRankWithScore
        if(is_string($syncDate)){
            $syncDate=$this->utils->formatDateForMysql(new DateTime($syncDate));
        }
        $rank_key = $rank_name.'_'.$syncDate;
        $total_score = $this->player_score_model->getPlayerTotalScore(false, $syncDate, 'newbet', null, player_score_model::ID_FOR_TOTAL_SCORE);
        $newbet_bonus = (!empty($total_score[0]) && isset($total_score[0]['game_score'])) ? (float)$total_score[0]['game_score'] : 0;
        $newbet_bonus_turnover = $newbet_bonus * ($description['bonus_turnover'] / 100);

        $bonus_rate_settings = $description['bonus_settings'];

        // check rank < 10;
        $_rankCurrentPlayer = $this->player_score_model->getPlayerNewbetRanklist($this->playerId, 1, 0, $syncDate);
        if (empty($_rankCurrentPlayer[0])) {
            $success = false;

            $this->appendToDebugLog('checkCustomizeBonusCondition: Player not found in rank.', ['player_id' => $this->playerId, '_rankCurrentPlayer' => $_rankCurrentPlayer]);
            $errorMessageLang = lang('Player not found in rank.');
            return $result=['success' => $success, 'message' => $errorMessageLang];
        }
        $rankCurrentPlayer = $_rankCurrentPlayer[0];
        $player_rank = $rankCurrentPlayer['rank'] ?: false;
        if($player_rank > 10) {
            $success = false;
            $this->appendToDebugLog('checkCustomizeBonusCondition: Player rank not reached.', ['player_id' => $this->playerId, 'rankCurrentPlayer' => $rankCurrentPlayer]);

            $errorMessageLang = lang('Player rank not reached.');
            return $result=['success' => $success, 'message' => $errorMessageLang];
        }
        if(!empty($rankCurrentPlayer['playerpromoId'])){
            $success = false;
            $this->appendToDebugLog('checkCustomizeBonusCondition: Player promo had released.', ['player_id' => $this->playerId, 'rankCurrentPlayer' => $rankCurrentPlayer]);
            $errorMessageLang = lang('Player promo had released.');
            return $result=['success' => $success, 'message' => $errorMessageLang];
        }

        // check released

        // count bonus
        $bonus_rate_index = "R$player_rank";
        if(!empty($bonus_rate_settings[$bonus_rate_index])){
            $this->appendToDebugLog('checkCustomizeBonusCondition: bonus amount.', [
                'player_id' => $this->playerId, 
                'rankCurrentPlayer' => $rankCurrentPlayer, 
                "bonus_rate_settings" => $bonus_rate_settings, 
                "bonus_rate" => $bonus_rate_settings[$bonus_rate_index]
                ]
            );
            $rate = $bonus_rate_settings[$bonus_rate_index]['rate'];
            $rank_current_bonus = $newbet_bonus_turnover * ($rate / 100);
            $bonus_amount = round($rank_current_bonus, 2);

            $extra_info['newbet_total_bonus'] = round($newbet_bonus_turnover, 2);
            $extra_info['player_rate'] = round($rate, 2);

        } else {
            $success = false;
            $this->appendToDebugLog('checkCustomizeBonusCondition: Rank bonus not set up.', ['player_id' => $this->playerId, 'rankCurrentPlayer' => $rankCurrentPlayer, "bonus_rate_settings" => $bonus_rate_settings ]);
            $errorMessageLang = lang('Rank bonus not set up.');
            return $result=['success' => $success, 'message' => $errorMessageLang];
        }

        return $result=['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        
    }
}
