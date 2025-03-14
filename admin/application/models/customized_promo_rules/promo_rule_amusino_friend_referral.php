<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * OGP-33784 邀请好友返佣 （三级分销制度）
 * 好友推薦獎金
 * 
 * 举例：
 * 一级代理邀请二级获得二级0.35%的有效流水返佣，
 * 二级邀请三级，则二级获得三级的0.35%有效流水返佣，
 * 一级获得三级0.15%的有效流水返佣，
 * 
 * 三级邀请四级，则三级获得四级0.35%有效流水返佣，
 * 二级获得四级0.15%有效流水返佣，无限循环
 * 
 * 
 * 1级、2级所得投注返水
 * 真人返水佣金=有效投注*0.35%*65%
 * 电子返水佣金=有效投注*0.35%*65%
 * 体育返水佣金=有效投注*0.35%*100%
 * 其他返水佣金=有效投注*0.35%*30%
 * 
 *  1级所得3级的投注返水
 * 真人返水佣金=有效投注*0.15%*65%
 * 电子返水佣金=有效投注*0.15%*65%
 * 体育返水佣金=有效投注*0.15%*100%
 * 其他返水佣金=有效投注*0.15%*30%
 * 
 * 抓前一日的 00:00:00 ~ 23:59:59的流水來算
 * 系統自動發放 ,每日凌晨4點統一發放
 * 1倍流水
 * 反佣適用全部遊戲 除了真人、电子、体育(game tag), 剩餘的game tag都當作其他
 * 
 * 真人 live_dealer => game tag : 4
 * 电子 e_sports => game tag : 10
 * 体育 sports => game tag : 15
 * 其他  otehrt => game tag :
 * 
 * (被推薦人)有效好友 = 驗證手機(不支援FB綁定, 因此不需驗證FB) + 1筆成功上分紀錄
 * 邀請人僅需要驗證手機, 才能獲得有效好友的返佣
 *
condition:
{
  "class": "promo_rule_amusino_friend_referral",
  "bet_allowed_date": {
    "start": "2023-02-16",
    "end": "2023-02-25"
  },
  "verified_phone": true,
  "verified_invited_deposit_cnt": true,
  "bonus_settings": {
    "1": [
      {"game_tag": [1], "game_tag_code": ["live_dealer"], "bonus_percentage": 35, "bet_percentage": 65},
      {"game_tag": [2], "game_tag_code": ["e_sports"], "bonus_percentage": 35, "bet_percentage": 65},
      {"game_tag": [3], "game_tag_code": ["sports"], "bonus_percentage": 35, "bet_percentage": 100},
      {"game_tag": [4, 5, 6], "game_tag_code": ["custom_others"], "bonus_percentage": 35, "bet_percentage": 30}
    ],
    "2": [
      {"game_tag": [1], "game_tag_code": ["live_dealer"], "bonus_percentage": 15, "bet_percentage": 65},
      {"game_tag": [2], "game_tag_code": ["e_sports"], "bonus_percentage": 15, "bet_percentage": 65},
      {"game_tag": [3], "game_tag_code": ["sports"], "bonus_percentage": 15, "bet_percentage": 100},
      {"game_tag": [4, 5, 6], "game_tag_code": ["custom_others"], "bonus_percentage": 15, "bet_percentage": 30}
    ]
  }
}
 *
 *
 */
class Promo_rule_amusino_friend_referral extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_amusino_friend_referral';
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

		return $result;
		// return ['success' => false, 'message' => 'testing'];
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

    protected function isVerifiedPhone($description, $playerId = null){
        $verified_phone = true;

		$player_id = $this->playerId;
		if(!empty($playerId)){
			$player_id = $playerId;
		}

        if(!empty($description['verified_phone']) && $description['verified_phone']){
            $verified_phone = $this->player_model->isVerifiedPhone($player_id);
        }

        if(!$verified_phone){
            $this->appendToDebugLog('not verified phone',['player_id'=>$player_id]);
        }

        return $verified_phone;
    }

	public function isVerifiedDepositCnt($description, $invitedUserId, $deposit_start = null, $deposit_end = null){
		$this->load->model(['transactions']);
		$verified_invited_deposit_cnt = true;
		$cnt = 0;
		if(!empty($description['verified_invited_deposit_cnt']) && $description['verified_invited_deposit_cnt']){
            $cnt = $this->transactions->countDepositByPlayerId($invitedUserId, $deposit_start, $deposit_end);
        }

		$verified_invited_deposit_cnt = !empty($cnt) ? true : false;

		if(!$verified_invited_deposit_cnt){
			$this->appendToDebugLog('invited deposit cnt not verified',['invited user id'=>$invitedUserId]);
		}

		return $verified_invited_deposit_cnt;
	}

	public function get_referred_list_by_player_id(){
		$this->load->model(['player_friend_referral']);
		$invite_list_level_1 = [];
		$invite_list_level_2 = [];

		$invited_list = $this->player_friend_referral->getPlayerReferralList($this->playerId);
		if(!empty($invited_list)){
			$invite_list_level_1 = $this->callHelper('array_pluck', [$invited_list, 'invitedUserId']);
		}

		if(!empty($invite_list_level_1)){
			foreach($invite_list_level_1 as $level_1_invitedUserId){
				$invited_list_level_2 = $this->player_friend_referral->getPlayerReferralList($level_1_invitedUserId);
				if(!empty($invited_list_level_2)){
					$level_2_invitedUserId_arr[] = $this->callHelper('array_pluck', [$invited_list_level_2, 'invitedUserId']);
				}
			}

			if(!empty($level_2_invitedUserId_arr)){
				foreach($level_2_invitedUserId_arr as $level_2_invitedUserId){
					foreach($level_2_invitedUserId as $invitedUserId){
						$invite_list_level_2[] = $invitedUserId;
					}
				}
			}
		}

		return [$invite_list_level_1, $invite_list_level_2];
	}

	public function getLevelSettings($description){
		$bonus_settings = !empty($description['bonus_settings'])? $description['bonus_settings'] : null;
		$level_1_settings =  !empty($bonus_settings[1]) ? $bonus_settings[1] : null;
		$level_2_settings =  !empty($bonus_settings[2]) ? $bonus_settings[2] : null;
		return [$level_1_settings, $level_2_settings];
	}

	public function calculatePlayerBonus($description, $fromDate, $toDate, $level_settings, $invited_list){
		$accumulate_bonus = 0;
		$bonus = 0;
		$invalid_user_id = [];
		$bonus_info = [];

		if(empty($invited_list)){
			$this->utils->debug_log(__METHOD__ . ' empty invited list');
			return $accumulate_bonus;
		}

		// check last 30 days login_ip between referrer and invited, if found same ip, then ignore invited player bet
		$check_ip_from = $check_ip_to = null;
		$dont_allow_request_promo_from_same_ips = $this->promorule['dont_allow_request_promo_from_same_ips'];
		if($dont_allow_request_promo_from_same_ips){
			$ip_date_range = 30;
			$check_from = $this->utils->getMinusDaysForMysql($ip_date_range, 'Y-m-d');
			$check_to = $this->utils->getYesterdayForMysql();
			if(!empty($description['check_ip_from']) && !empty($description['check_ip_to'])){
				$check_from = $description['check_ip_from'];
				$check_to = $description['check_ip_to'];
			}
			$check_ip_from = $check_from.' '.Utils::FIRST_TIME;
			$check_ip_to = $check_to.' '.Utils::LAST_TIME;
			$referrerIp = $this->callHelper('getPlayerLoginIpByDate', [$this->playerId, $check_ip_from, $check_ip_to]);
		}

		foreach($invited_list as $invite_user_id){
			if(!empty($check_ip_from) && !empty($check_ip_to)){
				$invitedIp = $this->callHelper('getPlayerLoginIpByDate', [$invite_user_id, $check_ip_from, $check_ip_to]);
				$this->appendToDebugLog("check referrer [$this->playerId] and invited [$invite_user_id] both recent ip", ['referrerIp' => $referrerIp, 'invitedIp' => $invitedIp, 'check_ip_from' => $check_ip_from, 'check_ip_to' => $check_ip_to]);
				$commonIp = array_intersect($referrerIp, $invitedIp);
				if(!empty($commonIp)){
					$invalid_user_id[$invite_user_id] = "referrer and invited have same ip between {$check_ip_from} to {$check_ip_to}";
					continue;
				}
			}

			$verified_phone = $this->isVerifiedPhone($description, $invite_user_id);
			if(!$verified_phone){
				$invalid_user_id[$invite_user_id] = 'player do not verify phone number';
				continue;
			}

			$verified_deposit_cnt = $this->isVerifiedDepositCnt($description, $invite_user_id, null, null);
			if(!$verified_deposit_cnt){
				$invalid_user_id[$invite_user_id] = 'player do not have deposit record';
				continue;
			}

			$records = $this->getPlayersBetByDate($invite_user_id, $fromDate, $toDate);
			if(empty($records)){
				$invalid_user_id[$invite_user_id] = 'player do not bet';
				continue;
			}

			$this->utils->debug_log(__METHOD__ . ' process invited player', $invite_user_id);

			foreach ($records as $record) {
				foreach ($level_settings as $settings) {
					if(in_array($record['id'], $settings['game_tag'])){
						$record_tag_id = $record['id'];
						$settings_tag_id = $settings['game_tag'];
						$bonus_percentage = $settings['bonus_percentage'] / 100;
						$bet_percentage = $settings['bet_percentage'] / 100;
						$total_bet = $record['total_bet'];
						$bonus = $total_bet * $bonus_percentage * $bet_percentage;
						$accumulate_bonus += $bonus;

						$bonus_info[$invite_user_id][] = [
							'record_tag_id' => $record_tag_id,
							'bonus_percentage' => $bonus_percentage,
							'bet_percentage' => $bet_percentage,
							'total_bet' => $total_bet,
							'bonus' => $bonus
						];
					}
				}
			}
		}

		$this->appendToDebugLog(__METHOD__ . ' result bonus', ['accumulate_bonus' => $accumulate_bonus, 'bonus_info' => $bonus_info]);

		if(!empty($invalid_user_id)){
			$this->appendToDebugLog(__METHOD__ . ' invalid players', $invalid_user_id);
		}

		return $accumulate_bonus;
	}

	public function getPlayersBetByDate($invitedPlayerId, $fromDate, $toDate){
		$result = $this->callHelper('getPlayerBetGroupByGameTagByDate', [$invitedPlayerId, $fromDate, $toDate]);
		return $result;
	}

	private function checkCustomizeBounsCondition($description, &$extra_info, &$errorMessageLang){
		$success = false;
        $errorMessageLang = null;
        $bonus_amount = 0;

		$verified_phone = $this->isVerifiedPhone($description);
		if(!$verified_phone){
			$errorMessageLang = 'promo.rule_is_player_verified_mobile';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		list($invited_list_level_1, $invited_list_level_2) = $this->get_referred_list_by_player_id();
		$this->appendToDebugLog('Invited list', ['invited_list_level_1' => $invited_list_level_1, 'invited_list_level_2' => $invited_list_level_2]);

		if(empty($invited_list_level_1) && empty($invited_list_level_2)){
			$errorMessageLang =  'Referred friend is empty';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		list($level_1_settings, $level_2_settings) = $this->getLevelSettings($description);
		$this->utils->debug_log('Level Settings', ['level_1_settings' => $level_1_settings, 'level_2_settings' => $level_2_settings]);

		if(empty($level_1_settings) || empty($level_2_settings)){
			$errorMessageLang =  'Bonus condition does not set bonus_settings';
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		$yesterday = $this->utils->getYesterdayForMysql();
		$fromDate = $toDate = $yesterday;
		if(!empty($description['bet_allowed_date']['start']) && !empty($description['bet_allowed_date']['end'])){
			$fromDate = $description['bet_allowed_date']['start'];
			$toDate = $description['bet_allowed_date']['end'];
		}

		$this->appendToDebugLog('Bet allow date', ['fromDate'=>$fromDate, 'toDate'=>$toDate]);

		$bonus_1 = $this->calculatePlayerBonus($description, $fromDate, $toDate, $level_1_settings, $invited_list_level_1);
		$bonus_2 = $this->calculatePlayerBonus($description, $fromDate, $toDate, $level_2_settings, $invited_list_level_2);
		$bonus_amount = $bonus_1 + $bonus_2;
        $this->appendToDebugLog('total bonus', ['bonus_1'=>$bonus_1, 'bonus_2'=>$bonus_2, 'bonus_amount'=>$bonus_amount]);

		if(empty($bonus_amount)){
			$errorMessageLang = 'promo_rule.common.error';
			$this->appendToDebugLog('invited players did not meet condition, then bonus amount is 0');
			return ['success' => $success, 'message' => $errorMessageLang];
		}

		$success = true;

        return ['success' => $success, 'message' => $errorMessageLang, 'bonus_amount' => $bonus_amount];
	}
}
