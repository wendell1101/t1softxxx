<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-32269
 * 1. Deposit >=3 times
 * 2. Effective bet>=100
 * 當被推薦人的 1 or 2 擇一成立, 即成為推薦人的有效用戶
 * 推薦人也可獲得獎金 10
 * 無取款條件
 * 

condition:
{
	"class": "promo_rule_king_referral_daily_bonus",
	"min_deposit_cnt": 3,
	"min_bet": 100,
	"bonus": 10
}
*
*
*
{
	"class": "promo_rule_king_referral_daily_bonus",
	"check_player_active": false,
	"allowed_date": {
		"start": "Y-m-d",
		"end": "Y-m-d"
	},
	"referral_date": {
		"start": "Y-m-d 00:00:00",
		"end": "Y-m-d 23:59:59"
	},
	"deposit_date": {
		"start": "Y-m-d 00:00:00",
		"end": "Y-m-d 23:59:59"
	},
	"betting_date": {
		"start": "Y-m-d 00:00:00",
		"end": "Y-m-d 23:59:59"
	},
	"game_type": [],
	"game_platform": [],
	"min_deposit_cnt": 3,
	"min_bet": 100,
	"bonus": 10
}

 *
 *
 */
class Promo_rule_king_referral_daily_bonus extends Abstract_promo_rule{

    public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_king_referral_daily_bonus';
	}

	protected function getPlayerCashbackInfoByDryRun($description, &$extra_info, &$errorMessageLang){
		$success = false;

		// optional check player active
		if(!empty($description['check_player_active']) ){
			$isPlayerActive = $this->callHelper('isPlayerActive', []);
			if(!$isPlayerActive){
				$errorMessageLang = 'player.blocked';
				return ['success' => $success, 'message' => $errorMessageLang];
			}
		}

		$allowed_date = isset($description['allowed_date'])? $description['allowed_date'] : null;
		if(!empty($allowed_date['start']) && !empty($allowed_date['end'])){
        	$currentDate = $this->utils->getTodayForMysql();
			// currentDate & minDate & maxDate Y-m-d
            $minDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['start']));
            $maxDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['end']));
			$this->appendToDebugLog('allowed_date', ['currentDate'=>$currentDate, 'minDate'=>$minDate, 'maxDate'=>$maxDate]);
            if( $currentDate < $minDate || $currentDate > $maxDate ){
            	$errorMessageLang = 'Not right date';
            	return ['success' => $success, 'message' => $errorMessageLang];
            }
        }

		$referral_start = !empty($description['referral_date']['start']) ? $description['referral_date']['start'] : null;
		$referral_end = !empty($description['referral_date']['end']) ? $description['referral_date']['end'] : null;

		$deposit_start = !empty($description['deposit_date']['start']) ? $description['deposit_date']['start'] : null; 
		$deposit_end = !empty($description['deposit_date']['end']) ? $description['deposit_date']['end'] : null;

		$betting_start = !empty($description['betting_date']['start']) ? $description['betting_date']['start'] : null; 
		$betting_end = !empty($description['betting_date']['end']) ? $description['betting_date']['end'] : null;
		
		$gameTypeId = !empty($description['game_type']) ? $description['game_type'] : null;
		$gamePlatformId = !empty($description['game_platform']) ? $description['game_platform'] : null;

		$min_deposit_cnt = !empty($description['min_deposit_cnt']) ? $description['min_deposit_cnt'] : 3;
		$min_bet = !empty($description['min_bet']) ? $description['min_bet'] : 100;

		$this->appendToDebugLog('custom settings', [
			'referral start' => $referral_start, 'referral end' => $referral_end,
			'deposit start' => $deposit_start, 'deposit end' => $deposit_end,
			'bet start' => $betting_start, 'bet end' => $betting_end,
			'gameTypeId' => $gameTypeId, 'gamePlatformId' => $gamePlatformId,
			'min_deposit_cnt' => $min_deposit_cnt, 'min_bet' => $min_bet
		]);

		$referred_list = $this->get_referred_list_by_referred_on($referral_start, $referral_end);
		$this->appendToDebugLog($this->playerId . ' referred list:', ['count'=>count($referred_list), 'referred_list'=>$referred_list]);

		if(empty($referred_list)){
			$errorMessageLang = 'promo_rule.common.error';
			$this->appendToDebugLog('no referred player found');
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		$success_list = [];
		$fail_list = [];

		foreach($referred_list as $referred_row){
			$invitedUserId = $referred_row->invitedUserId;
			$referralId = $referred_row->referralId;
			$playerId = $referred_row->playerId;
			
			if($this->callHelper('getPlayerPromoByReferralId', [$referralId])){
				$this->appendToDebugLog('referrer already get referral bonus', ['referralId'=>$referralId]);
				$fail_list[] = ['referralId'=>$referralId, 'invitedUserId'=>$invitedUserId];
				continue;
			}

			$met_deposit_condition = false;
			$met_bet_condition = false;

			$deposit_cnt = $this->getPlayeDepositCountByDate($invitedUserId, $deposit_start, $deposit_end);
			$met_deposit_condition = $deposit_cnt >= $min_deposit_cnt;

			$total_bet = $this->getPlayerBetByDate($invitedUserId, $betting_start, $betting_end, $gamePlatformId, $gameTypeId);
			$met_bet_condition = $total_bet >= $min_bet;
			
			$this->appendToDebugLog('validate invitedUser condition by dryrun', [
				'referrer'=>$playerId, 'invitedUserId'=>$invitedUserId, 'referralId'=>$referralId,
				'deposit_cnt'=>$deposit_cnt, 'total bet'=>$total_bet, 
				'met_deposit_condition'=>$met_deposit_condition, 'met_bet_condition'=>$met_bet_condition
			]);

			if($met_deposit_condition || $met_bet_condition){
				$success_list[] = ['referralId'=>$referralId, 'invitedUserId'=>$invitedUserId];
			}
		}

		if(empty($success_list)){
			$errorMessageLang = 'promo_rule.common.error';
			$this->appendToDebugLog('no referred player met the condition', ['fail_list'=>$fail_list]);
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		$success = true;
		$extra_info['referrer_cashback'] = $success_list;
		$this->appendToDebugLog('referrer cashback info', ['count'=>count($success_list), 'result'=>$success_list]);

		return ['success' => $success, 'message' => $errorMessageLang];
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
        $errorMessageLang=null;
        $result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

		return $result;
	}

    public function get_referred_list_by_referred_on($start=null, $end=null){
		$this->load->model(array('player_friend_referral'));
		$rows = $this->player_friend_referral->getPlayerReferralList($this->playerId, null, $start, $end);
		return $rows;
	}

	public function getPlayerBetByDate($invitedUserId, $betting_start, $betting_end, $gamePlatformId = null, $gameTypeId = null){
	    $this->load->model(['total_player_game_day']);
	    return $this->total_player_game_day->getPlayerTotalBettingAmountByPlayer($invitedUserId, $betting_start, $betting_end, $gamePlatformId, $gameTypeId);
	}

	public function getPlayeDepositCountByDate($invitedUserId, $deposit_start, $deposit_end){
		$this->load->model(['transactions']);
		return $this->transactions->countDepositByPlayerId($invitedUserId, $deposit_start, $deposit_end);
	}

	private function checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){
		$success = false;
        $bonus_amount = 0;

		$cashback_info = !empty($extra_info['referrer_cashback']) ? $extra_info['referrer_cashback'] : null;
		if(empty($cashback_info)){
			$errorMessageLang = 'promo_rule.common.error';
			$this->appendToDebugLog('Player cashback info not found');
			return ['success' => $success, 'messeage' => $errorMessageLang];
		}

		$triggerCronjobEvent = !empty($extra_info['triggerCronjobEvent']) ? true : false;
		$bonus = !empty($description['bonus']) ? $description['bonus'] : 10;

		if($triggerCronjobEvent){
			// run by cronjob
			// calculate one cashback as bonus_amount each time
			$row = $cashback_info;
			$extra_info['referral_id'] = $row['referralId']; // in order to append referralId to playerpromo
			$bonus_amount = $bonus;
			$extra_info['reason'] = "Referred Player {$row['invitedUserId']} met condition";
			$this->appendToDebugLog('referrer get bonus info', ['referralId'=>$row['referralId'], 'invitedUserId'=>$row['invitedUserId'], 'bonus'=>$bonus]);
		}else{
			// run by dryrun, only for display referrer total bonus amount
			// calculate total cashback as bonus_amount
			foreach ($cashback_info as $row){
				$bonus_amount += $bonus;
				$this->appendToDebugLog('referrer get bonus info', ['referralId'=>$row['referralId'], 'invitedUserId'=>$row['invitedUserId'], 'bonus'=>$bonus]);
			}
		}

		$this->appendToDebugLog('referrer cashback amount source', [
			'cashback_info' => $cashback_info,
			'bonus_amount' => $bonus_amount
		]);

		if(empty($bonus_amount)){
			$errorMessageLang = 'promo_rule.common.error';
			$this->appendToDebugLog('Bonus amount is less than min bonus amount', ['bonus_amount' => $bonus_amount]);
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		$success = true;
        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}