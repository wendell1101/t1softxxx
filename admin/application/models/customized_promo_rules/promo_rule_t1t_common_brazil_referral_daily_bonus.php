<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-30610
 * Hagebet - Referral Program rule
 * 
condition:
{
    "class": "promo_rule_t1t_common_brazil_referral_daily_bonus",
	"referral_date": {
        "start": "2023-06-26",
        "end": "2023-06-30"
    },
    "betting_date": {
		"start": "",
        "end": ""
    },
	"pay_hour": 12,
	"game_platform_id": [1,2,3],
	"game_type_id": [1,2,3],
	"bonus_settings": {
		"base_percentage": 1,
		"power_percentage": 30
	}
}
 *
 *
 */
class Promo_rule_t1t_common_brazil_referral_daily_bonus extends Abstract_promo_rule{

    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_t1t_common_brazil_referral_daily_bonus';
	}

	protected function getPlayerCashbackInfoByDryRun($description, &$extra_info, &$errorMessageLang){
		$success = false;
		$isPlayerActive = $this->callHelper('isPlayerActive', []);
		if(!$isPlayerActive){
			$errorMessageLang = 'player.blocked';
			return ['success' => $success, 'message' => $errorMessageLang];
		}
		$this->load->model(['player_friend_referral']);
		
		$game_platform_id = !empty($description['game_platform_id']) ? $description['game_platform_id'] : null;
		$game_type_id = !empty($description['game_type_id']) ? $description['game_type_id'] : null;
		$referral_start = !empty($description['referral_date']['start']) ? $description['referral_date']['start'] : null;
		$referral_end = !empty($description['referral_date']['end']) ? $description['referral_date']['end'] : null;
		
		$current_date = $this->utils->getTodayForMysql();
		$yesterday = $this->utils->getLastDay($current_date);
		$betting_start = $betting_end = $yesterday;

		if(!empty($description['betting_date']['start']) && !empty($description['betting_date']['end'])){
			$betting_start = $description['betting_date']['start'];
			$betting_end = $description['betting_date']['end'];
		}
		
		$this->appendToDebugLog('date range settings', [
			'referral start' => $referral_start, 'referral end' => $referral_end,
			'bet start' => $betting_start, 'bet end' => $betting_end
		]);

		$player_cashback_info = [];
		$referrer_cashback_info = $this->player_friend_referral->getPlayerReferralLevelList($referral_start, $referral_end, $betting_start, $betting_end, $game_platform_id, $game_type_id);

		$cashback_info = !empty($referrer_cashback_info[$this->playerId]) ? $referrer_cashback_info[$this->playerId] : null;
		if(empty($cashback_info)){
			$errorMessageLang = 'promo_rule.common.error';
			$this->appendToDebugLog('Player cashback info not found');
			return ['success' => $success, 'message' => $errorMessageLang];
		}


		$success = true;
		$extra_info['referrer_cashback'] = $cashback_info;
		$this->appendToDebugLog('referrer cashback info', $cashback_info);

		return ['success' => $success, 'message' => $errorMessageLang];
	}

	protected function getLevelFormula($base_percentage, $power_percentage, $level){
		$base = $base_percentage / 100;
		$power_base = $power_percentage / 100;
		$level_formula = $base * pow($power_base, ($level - 1));
		$this->appendToDebugLog('referrer cashback level formula', [
			'base_percentage' => $base_percentage, 'base' => $base,
			'power_percentage' => $power_percentage, 'power_base' => $power_base, 
			'level_formula' => $level_formula
		]);
		return $level_formula;
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

		$triggerCronjobEvent = !empty($extra_info['triggerCronjobEvent']) ? true : false;

		// run by dryrun
		if(!$triggerCronjobEvent){
			$this->appendToDebugLog('use dryrun to process runBonusConditionChecker');
			$retult = $this->getPlayerCashbackInfoByDryRun($description, $extra_info, $errorMessageLang);
			return $retult;
		}

		// run by cronjob
		$this->appendToDebugLog('use cronjob to process runBonusConditionChecker');
		$cashback_info = !empty($extra_info['referrer_cashback']) ? $extra_info['referrer_cashback'] : null;
		if(empty($cashback_info)){
			$errorMessageLang = 'promo_rule.common.error';
			$this->appendToDebugLog('Player cashback info not found');
			return ['success' => $success, 'messeage' => $errorMessageLang];
		}

		$success = true;
		$result = ['success' => $success, 'messeage' => $errorMessageLang];
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
		$amount = 0;
        $bonus_amount = 0;
        $errorMessageLang=null;

		$bonus_settings = $description['bonus_settings'];
		$min_bonus_amount = !empty($description['min_bonus_amount']) ? $description['min_bonus_amount'] : 0;
		$base_percentage = !empty($bonus_settings['base_percentage']) ? $bonus_settings['base_percentage'] : 1;
		$power_percentage = !empty($bonus_settings['power_percentage']) ? $bonus_settings['power_percentage'] : 30;
		
		$cashback_info = $extra_info['referrer_cashback'];

		$triggerCronjobEvent = !empty($extra_info['triggerCronjobEvent']) ? true : false;
		
		if($triggerCronjobEvent){
			// run by cronjob
			// calculate one cashback as bonus_amount each time
			$level = $cashback_info['level'];
			$formula = $this->getLevelFormula($base_percentage, $power_percentage, $level);
			$last_invited_player_bet = $cashback_info['last_invited_player_bet'];
			$bonus_amount = $last_invited_player_bet * $formula;
			$extra_info['referral_id'] = $cashback_info['last_referral_id'];
		}else{
			// run by dryrun, only for display referrer total bonus amount
			// calculate total cashback as bonus_amount
			foreach ($cashback_info as $row) {
				$level = $row['level'];
				$formula = $this->getLevelFormula($base_percentage, $power_percentage, $level);
				$last_invited_player_bet = $row['last_invited_player_bet'];
				$amount = $last_invited_player_bet * $formula;
				$bonus_amount += $amount;
			}
		}
		

		$this->appendToDebugLog('referrer cashback amount source', [
			'cashback_info' => $cashback_info,
			'bonus_amount' => $bonus_amount
		]);
		
		if($bonus_amount <= $min_bonus_amount){
			$errorMessageLang = 'promo_rule.common.error';
			$this->appendToDebugLog('Bonus amount is less than min bonus amount', ['bonus_amount' => $bonus_amount]);
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		$success = true;
		$result = ['success' => $success, 'bonus_amount' => $bonus_amount, 'message' => $errorMessageLang];
		return $result;
	}
}