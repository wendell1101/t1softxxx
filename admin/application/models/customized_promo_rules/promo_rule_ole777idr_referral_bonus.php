<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';
/**
 * OGP-29492
 * 好友推薦獎金
 *
 * OGP-33202
 * 原本Release Bonus是Auto, 這張單需要改成Manual
 * 所以要調整update referralId 流程
 *
condition:
{
    "class": "promo_rule_ole777idr_referral_bonus",
    "allowed_date": {
        "start": "2023-06-01",
        "end": "2024-06-25"
    },
    "withdrawal_condition":{
        "referrer": 8,
        "invited": 8
    },
    "bonus_amount": {
        "invited": 77,
        "referrer": 100
    },
    "invited_bonus_settings": {
        "min_deposit": 500,
        "min_betting": 500
    },
    "invited_condition_within_days": {
        "deposit" : 30,
        "betting" : 30
    },
    "referrer_bonus_settings": {
        "min_deposit": 100,
        "min_betting": 0
    },
    "referrer_condition_within_days": {
        "deposit" : null,
        "betting" : null
    }
}
 *
 *
 */
class Promo_rule_ole777idr_referral_bonus extends Abstract_promo_rule{

    const REFERRAL_TYPE_INVITED = 'invited'; // new player who registered with referral code
    const REFERRAL_TYPE_REFERRER = 'referrer'; // old player who have referral code

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
		$this->load->model(array('player_friend_referral','total_player_game_day'));
	}

	public function getClassName(){
		return 'Promo_rule_ole777idr_referral_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){
		$errorMessageLang=null;
        $result = $this->checkCustomizeBounsCondition($description, $extra_info, $errorMessageLang);

        if(array_key_exists('bonus_amount',$result)){
            unset($result['bonus_amount']);
        }

        if(array_key_exists('referral_id',$extra_info)){
            unset($extra_info['referral_id']);
        }

        if(array_key_exists('referred_on',$extra_info)){
            unset($extra_info['referred_on']);
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
	protected function generateWithdrawalCondition($description, &$extra_info, $dry_run)
    {
        $success = false;
        $errorMessageLang = null;
        $withdrawal_condition_for_referral = 0;
		$withdrawal_condition_for_invited = 0;
		$withdrawal_condition_amount = 0;
		$withdrawal_condition = !empty($description['withdrawal_condition'])? $description['withdrawal_condition'] : null;
		if(!empty($withdrawal_condition['referrer']) && !empty($withdrawal_condition['invited'])){
			$withdrawal_condition_for_referral = $withdrawal_condition['referrer'];
			$withdrawal_condition_for_invited = $withdrawal_condition['invited'];
        }

        $result = $this->releaseBonus($description, $extra_info, $dry_run);
        $bonus_amount = $result['bonus_amount'];

        $referral_id = null;
        if (!empty($extra_info['referral_id'])){
            //referral_id only exist while main player is applying promo
            $referral_id = $extra_info['referral_id'];
        }
        if (!empty($extra_info['sync_claim_referral_id'])){
            //sync_claim_referral_id only exist while sync_claim_player is applying promo
            $referral_id = $extra_info['sync_claim_referral_id'];
        }

        if(empty($referral_id)){
            // add fool proof
            $errorMessageLang = 'Lost referral id while generating withdrawal condition';
            return ['success'=>$success, 'message'=>$errorMessageLang, 'withdrawal_condition_amount'=>round($withdrawal_condition_amount, 2)];
        }

        $identity = $this->getIdentityByReferralId($referral_id);
        switch ($identity) {
            case self::REFERRAL_TYPE_REFERRER:
                $withdrawal_condition_amount = $bonus_amount * $withdrawal_condition_for_referral;
                break;
            case self::REFERRAL_TYPE_INVITED:
                $withdrawal_condition_amount = $bonus_amount * $withdrawal_condition_for_invited;
                break;
        }

        if(!empty($withdrawal_condition_amount)){
            $success = true;
        }

        return ['success'=>$success, 'message'=>$errorMessageLang, 'withdrawal_condition_amount'=>round($withdrawal_condition_amount, 2)];
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

    public function syncPlayerApplyPromo($referral_id, $playerId, $referred_on, $description, &$errorMessageLang){
        $success = false;
        $referral_status = false;
        $bonus_amount = 0;
        $invited_bonus_amount = $description['bonus_amount']['invited'];
        $referrer_bonus_amount = $description['bonus_amount']['referrer'];

        $identity = $this->getIdentityByReferralId($referral_id);

        if(!empty($identity)){
            switch ($identity){
                case self::REFERRAL_TYPE_REFERRER:
                    $referral_status = $this->checkReferrerCondition($playerId ,$description, $errorMessageLang, $referred_on);
                    $bonus_amount = $referrer_bonus_amount;
                    break;
                case self::REFERRAL_TYPE_INVITED:
                    $referral_status = $this->checkInvitedCondition($playerId ,$description, $errorMessageLang, $referred_on);
                    $bonus_amount = $invited_bonus_amount;
                    break;
            }
        }

        if($referral_status){
            $success = true;
        }

        $this->appendToDebugLog('====== sync corresponding player apply promo =======', [
            'success' => $referral_status,
            'bonus_amount' => $bonus_amount,
            'identity' => $identity,
            'referral_id'=> $referral_id,
            'sync_claim_player'=> $playerId,
            'referred_on' => $referred_on
        ]);

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
    }

	private function checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){
		$success = false;
		$bonus_amount = 0;

		$allowed_date = isset($description['allowed_date'])? $description['allowed_date'] : null;
		if(!empty($allowed_date['start']) && !empty($allowed_date['end'])){
			$today = $this->utils->getTodayForMysql();
            $minDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['start']));
            $maxDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['end']));
            $date = new DateTime($today);
        	$currentDate = $date->format('Y-m-d');
            if( $currentDate < $minDate && $currentDate > $maxDate ){
            	$errorMessageLang = 'Not right date';
            	return ['success' => $success, 'message' => $errorMessageLang];
            }
        }

        /*
         PART 4
            only when main player(manual apply in player center) applied this promo already,
              1. after main player [referrer] apply, then also assist corresponding player [referred] to apply same promo
              2. after main player [referred] apply, then also assist corresponding player [referrer] to apply same promo
              (p.s. $this->playerId will become sync_claim_player)
         */
        if(!empty($extra_info['sync_claim_referral_id']) && !empty($extra_info['sync_claim_referred_on'])){
            return $this->syncPlayerApplyPromo($extra_info['sync_claim_referral_id'], $this->playerId, $extra_info['sync_claim_referred_on'], $description, $errorMessageLang);
        }

        /*
         PART 3
            only check main player (manual apply in player center),
            after check referral related players(referrer and invited) conditions in self::runBonusConditionChecker
            start to run self::releaseBonus AND self::generateWithdrawalCondition
            with main player conditions (ignore sync_claim_player condition)
            will enter this part
        */
        if(!empty($extra_info['referral_id']) && !empty($extra_info['referred_on'])){
            return $this->syncPlayerApplyPromo($extra_info['referral_id'], $this->playerId, $extra_info['referred_on'], $description, $errorMessageLang);
        }

        $referral_players = $this->getReferralListByInvited($this->playerId, $description);
        $invited_players = $this->getInvitedListByReferral($this->playerId, $description);

        // player who do not ever send referral code and not registered by referral code
        if(empty($referral_players) && empty($invited_players)){
            $errorMessageLang = 'promo_rule.common.error';
            return ['success' => $success, 'message' => $errorMessageLang];
        }

        /*
         PART 1 START
            assume player as a referred player (apply for invited bonus)
            step 1-1. try to get friend referral with invitedPlayerId,
                      if player do not have invited record, then check PART 2
            step 1-2. check new player (who registered with referral code) conditions
        */
        if(!empty($referral_players)){
            // every referred player only have one record
            $invited_bonus_amount = $description['bonus_amount']['invited'];
            foreach ($referral_players as $referral_id => $referral_data) {
                $referrer_player = $referral_data['player_id'];
                $referredOn = $referral_data['referred_on'];

                $this->appendToDebugLog('====== referral_players =======', [
                    'referral_id' => $referral_id,
                    'referrer_player_id' => $referrer_player,
                    'referred_on' => $referredOn
                ]);

                // check referral player and invited player meet condition
                $invited_status = $this->checkInvitedCondition($this->playerId, $description, $errorMessageLang, $referredOn);
                $referral_status = $this->checkReferrerCondition($referrer_player ,$description, $errorMessageLang, $referredOn);
                if($invited_status && $referral_status){
                    $success = true;
                    $bonus_amount = $invited_bonus_amount;
                    $extra_info['referral_id'] = $referral_id;
                    $extra_info['referred_on'] = $referredOn;
                    $extra_info['sync_claim_player'] = $referrer_player;
                    $extra_info['reason'] = "Referral id: $referral_id";
                    $this->appendToDebugLog('====== success to cliam referral promo =======', [
                        'referral_id' => $referral_id,
                        'referral_players'=> $referrer_player,
                        'invited_player'=> $this->playerId
                    ]);
                }
            }
            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }
        /* PART 1 END */

        /*
         PART 2 START
            assume player as a referrer player (apply for referrer bonus)
            step 2-1. try to get friend referral by playerId
            step 2-2. check old player (who send referral code to new player) conditions
        */
        if(!empty($invited_players)){
            // every referrer player can have multiple records
            $referrer_bonus_amount = $description['bonus_amount']['referrer'];
            $total_invited_players = count($invited_players);
            $success_info = [];
            $failed_info = [];
            $this->appendToDebugLog($this->playerId . ' ====== total referral ======', [
                'total invited count' => $total_invited_players,
                'invite info' => $invited_players
            ]);

            foreach ($invited_players as $referral_id => $invited_data) {
                $invited_player = $invited_data['player_id'];
                $referredOn = $invited_data['referred_on'];

                $invited_status = $this->checkInvitedCondition($invited_player , $description, $errorMessageLang, $referredOn);
                $referral_status = $this->checkReferrerCondition($this->playerId ,$description, $errorMessageLang, $referredOn);

                if($invited_status && $referral_status){
                    $success_info[] = ['referral_id' => $referral_id, 'referred_on' => $referredOn, 'sync_claim_player' => $invited_player];
                }else{
                    $failed_info[] = ['referral_id' => $referral_id, 'referred_on' => $referredOn, 'sync_claim_player' => $invited_player, 'error' => $errorMessageLang];
                }
            }

            if(!empty($success_info)){
                /* due to player have multiple records, release promo by referral_id ASC */
                $first_record = $success_info[0];
                $extra_info['referral_success_count'] = count($success_info);
                $extra_info['referral_id'] = $first_record['referral_id'];
                $extra_info['referred_on'] = $first_record['referred_on'];
                $extra_info['sync_claim_player'] = $first_record['sync_claim_player'];
                $extra_info['reason'] = "Referral id: $referral_id";
                $success = true;
                $errorMessageLang = null; //reset other failed_info error message
                $bonus_amount = $referrer_bonus_amount;
                $this->appendToDebugLog('====== success to cliam referral promo ======', [
                    'referral_id' => $first_record['referral_id'],
                    'referrer_player' => $this->playerId,
                    'invited_player'=> $first_record['sync_claim_player'],
                ]);
            }

            $this->appendToDebugLog($this->playerId . ' ====== all referrer result ======', [
                'success_details' => $success_info,
                'failed_details'=> $failed_info
            ]);

            return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
        }
        /* PART 2 END */
	}

	public function checkInvitedCondition($invitedPlayerId, $description, &$errorMessageLang, $referredOn){
		$success = false;
        $deposit_end_date = $betting_end_date = null;
		$check_betting_status = $check_deposit_status = false;
        $invited_bonus_settings = $description['invited_bonus_settings'];
        $required_min_betting = !empty($invited_bonus_settings['min_betting']) ? $invited_bonus_settings['min_betting'] : 0;
        $required_min_deposit = !empty($invited_bonus_settings['min_deposit']) ? $invited_bonus_settings['min_deposit'] : 0;
        $invited_condition_within_days = !empty($description['invited_condition_within_days']) ? $description['invited_condition_within_days'] : null;

        if(!empty($invited_condition_within_days['deposit'])){
            $limited = $invited_condition_within_days['deposit'];
            $deposit_end_date = date('Y-m-d H:i:s', strtotime($referredOn. "+ $limited days" ));
        }
        if(!empty($invited_condition_within_days['betting'])){
            $limited = $invited_condition_within_days['betting'];
            $betting_end_date = date('Y-m-d H:i:s', strtotime($referredOn. "+ $limited days" ));
        }

        $invited_total_betting = $this->getPlayersBetByPlayerId($invitedPlayerId, $description, $referredOn, $betting_end_date);
        $invited_total_deposit = $this->totalDepositByPlayerAndDateTime($invitedPlayerId, $referredOn, $deposit_end_date);
        $invited_total_deposit = !empty($invited_total_deposit) ? $invited_total_deposit : 0;

        if($invited_total_betting >= $required_min_betting){
            $check_betting_status = true;
        }

        if($invited_total_deposit >= $required_min_deposit){
            $check_deposit_status = true;
        }

        if(!$check_betting_status || !$check_deposit_status){
            $errorMessageLang = 'promo_rule.common.error';
        }

        if($check_betting_status && $check_deposit_status){
            $success = true;
        }

        $this->appendToDebugLog($invitedPlayerId . ' ====== checkInvitedCondition ======', [
            'start date' => $referredOn,
            'bet' => ['end_date' => $betting_end_date, 'required' => $required_min_betting, 'total' => $invited_total_betting, 'meet condition' => $check_betting_status],
            'deposit' => ['end_date' => $deposit_end_date, 'required' => $required_min_deposit, 'total' => $invited_total_deposit, 'meet condition' => $check_deposit_status]
        ]);

        return $success;
	}

	public function checkReferrerCondition($referralPlayerId, $description, &$errorMessageLang, $referredOn){
        $success = false;
        $deposit_strat_date = $betting_start_date = $deposit_end_date = $betting_end_date = null;
        $check_betting_status = $check_deposit_status = false;
        $referrer_bonus_settings = $description['referrer_bonus_settings'];
        $required_min_betting = !empty($referrer_bonus_settings['min_betting']) ? $referrer_bonus_settings['min_betting'] : 0;
        $required_min_deposit = !empty($referrer_bonus_settings['min_deposit']) ? $referrer_bonus_settings['min_deposit'] : 0;
        $referrer_condition_within_days = !empty($description['referrer_condition_within_days']) ? $description['referrer_condition_within_days'] : null;

        if(!empty($referrer_condition_within_days['deposit'])){
            $limited = $referrer_condition_within_days['deposit'];
            $deposit_end_date = date('Y-m-d H:i:s', strtotime($referredOn. "+ $limited days" ));
        }
        if(!empty($referrer_condition_within_days['betting'])){
            $limited = $referrer_condition_within_days['betting'];
            $betting_end_date = date('Y-m-d H:i:s', strtotime($referredOn. "+ $limited days" ));
        }

        $referral_total_deposit = $this->totalDepositByPlayerAndDateTime($referralPlayerId, $deposit_strat_date, $deposit_end_date);
        $referral_total_deposit = !empty($referral_total_deposit) ? $referral_total_deposit : 0;
        $referral_total_betting = $this->getPlayersBetByPlayerId($referralPlayerId, $description, $betting_start_date, $betting_end_date);

        if($referral_total_betting >= $required_min_betting){
            $check_betting_status = true;
        }

        if($referral_total_deposit >= $required_min_deposit){
            $check_deposit_status = true;
        }

        if(!$check_betting_status || !$check_deposit_status){
            $errorMessageLang = 'promo_rule.common.error';
        }

        if($check_betting_status && $check_deposit_status){
            $success = true;
        }

        $this->appendToDebugLog($referralPlayerId . ' ====== checkReferrerCondition ======', [
            'start date' => $referredOn,
            'bet' => ['end_date' => $betting_end_date, 'required' => $required_min_betting, 'total' => $referral_total_betting, 'meet condition' => $check_betting_status],
            'deposit' => ['end_date' => $deposit_end_date, 'required' => $required_min_deposit, 'total' => $referral_total_deposit, 'meet condition' => $check_deposit_status]
        ]);

        return $success;
    }

	public function getPlayerFriendReferral($referral_player_id=null, $invited_player_id=null, $description){
		$invited_from = null;
        $invited_to = null;
		$invited_date = isset($description['invited_date'])? $description['invited_date'] : null;
		if(!empty($invited_date)){
        	$invited_from = !empty($invited_date['start']) ? $invited_date['start'] : null;
    		$invited_to = !empty($invited_date['end']) ? $invited_date['end'] : null;
        }
		return $this->player_friend_referral->getPlayerReferralList($referral_player_id, self::STATUS_NORMAL, $invited_from, $invited_to, $invited_player_id);
	}

	public function getReferralListByInvited($playerId, $description){
        $referral_list = [];
		$player_friend_referral = $this->getPlayerFriendReferral(null, $playerId, $description);

		if(!empty($player_friend_referral)){
            // each referred player will only have one record
			foreach ($player_friend_referral as $value) {
                $status4invited = $value->status4invited;
                if(!empty($status4invited)){
                    // invited player already claimed bonus, cause referrer was not able to claim bonus before
                    $this->appendToDebugLog('====== Invited Player Already Claimed Bonus ======', [
                        'referral_id' => $value->referralId,
                        'invited_player' => $value->invitedUserId,
                        'status4invited' => $status4invited
                    ]);
                    continue;
                }

                $referral_list[$value->referralId] = [
                    'player_id' => $value->playerId,
                    'referred_on' => $value->registerTime
                ];
                break;
			}
		}
		return $referral_list;
	}

	public function getInvitedListByReferral($playerId, $description){
        $invited_list = [];
		$player_friend_referral = $this->getPlayerFriendReferral($playerId, null, $description);

		if(!empty($player_friend_referral)){
			foreach ($player_friend_referral as $value) {
                $invited_list[$value->referralId] = [
                    'player_id' => $value->invitedUserId,
                    'referred_on' => $value->registerTime
                ];
			}
		}
		return $invited_list;
	}

	public function getPlayersBetByPlayerId($playerId, $description, $betting_start_date, $betting_end_date){
		$game_type = isset($description['game_type'])? $description['game_type'] : null;
		$game_platform = isset($description['game_platform'])? $description['game_platform'] : null;
	    return $this->total_player_game_day->getPlayerTotalBettingAmountByPlayer($playerId, $betting_start_date, $betting_end_date, $game_platform, $game_type);
	}

	public function getIdentityByReferralId($referral_id){
		$referral_list = $this->player_friend_referral->getReferralByReferralId($referral_id);
        $allow_to_paid = null;
		$identity = null;
		if(!empty($referral_list)){
			switch ($this->playerId) {
				case $referral_list['playerId']:
					$identity = self::REFERRAL_TYPE_REFERRER;
                    $allow_to_paid = $referral_list['status'] == self::STATUS_NORMAL;
					break;
				case $referral_list['invitedPlayerId']:
					$identity = self::REFERRAL_TYPE_INVITED;
                    $allow_to_paid = empty($referral_list['status4invited']);
					break;
			}
		}

        if($allow_to_paid){
            return $identity;
        }

        return null;
	}
}
