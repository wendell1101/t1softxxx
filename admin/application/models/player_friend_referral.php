<?php
require_once dirname(__FILE__) . '/base_model.php';

/**
 *
 * playerId = referee
 *
 */
class Player_friend_referral extends BaseModel {

	const TABLE_NAME = 'playerfriendreferral';

	private $tableName = self::TABLE_NAME;

	const STATUS_PAID = 3;
	const STATUS_CANCELLED = 4;
	const STATUS_EXCEED_LIMIT = 5;
	const STATUS_EXCEED_MONTHLY_LIMIT = 6;
	const STATUS_SIGNUP_DATE_NOT_IN_RANGE = 7;

	public function __construct() {
		parent::__construct();
	}

	public function getReferredByPlayerId($playerId) {
		$query = $this->db->get(self::TABLE_NAME, array(
			'playerId' => $playerId,
		));
		return $query->result();
	}
	/**
	 * Get invitedPlayerId of playerfriendreferral
	 *
	 * @param integer $playerId The referr playerId
	 * @param string $start_date The start date of during dates, ex:"2020-03-11".
	 * @param string $end_date The end date of during dates, ex:"2020-03-11".
	 * @return array|null The rows of records.
	 */
	public function getReferredByPlayerIdWithDateRange($playerId, $start_date, $end_date) {

		$start_date = $start_date. ' 00:00:00';
		$end_date = $end_date. ' 23:59:59';
		$this->db->from(self::TABLE_NAME);
		$this->db->where('playerId', $playerId);
		$this->db->where('referredOn >=', $start_date);
		$this->db->where('referredOn <=', $end_date);

		return $this->runMultipleRowArray();
	}

	public function getUnprocessedReferrals() {
		$this->db->from(self::TABLE_NAME);
		$this->db->where('transactionId IS NULL', null, false)->where('status', self::STATUS_NORMAL);
		$this->db->order_by('playerId');
		$this->db->order_by('referredOn');

		return $this->runMultipleRow();
	}

	public function getUnprocessedReferralsByDateTime($from_date, $to_date) {
		$this->db->from(self::TABLE_NAME);
		$this->db->where('transactionId IS NULL', null, false)->where('status', self::STATUS_NORMAL);
		$this->db->where('referredOn >=', $from_date);
		$this->db->where('referredOn <=', $to_date);
		$this->db->order_by('playerId');
		$this->db->order_by('referredOn');

		return $this->runMultipleRow();
	}

	public function paidPlayerFriendReferral($referralId, $transactionId) {
		$this->db->set('transactionId', $transactionId)
			->set('status', self::STATUS_PAID)
			->where('referralId', $referralId);

		return $this->runAnyUpdate($this->tableName);
	}

	/**
	 * update the field, status4invited to paid
	 * In Normal case,
	 * B player invited by C player.
	 *
	 * Possible case but not yet occur,
	 * B player invited by C,D,E,... player(s), then done the referral in one, D player of them.
	 *
	 *
	 * @param integer $invitedPlayerId The referred player Id.
	 * @param integer $transactionId The transaction P.K.
	 * @param integer $referralId The referr player Id. If its not specified, that means update all request of the referred.
	 * @return void
	 */
	public function paidPlayerFriendReferral2invited($invitedPlayerId, $transactionId, $referralId = null) {

		if( ! empty($referralId) ){
			$this->db->where('playerId', $referralId);
		}
		$this->db->set('transactionId4invited', $transactionId)
			->set('status4invited', self::STATUS_PAID)
			->where('invitedPlayerId', $invitedPlayerId);
		$rlt = $this->runAnyUpdate($this->tableName);

		return $rlt;
	}

	public function updatePlayerFriendReferral($referralId, $data) {
		$this->db->update(self::TABLE_NAME, $data, array(
			'referralId' => $referralId,
		));
	}

	public function checkAllReferrals() {
		$success = true;
		//get player
		$this->db->from('player')->where('ifnull(refereePlayerId,0)!=0', null, false);
		$rows = $this->runMultipleRow();
		if (!empty($rows)) {
			foreach ($rows as $row) {
				$this->syncReferral($row->refereePlayerId, $row->playerId);
			}
		}

		return $success;
	}

	public function syncReferral($refereePlayerId, $invitedPlayerId) {
		$this->db->from($this->tableName)->where('playerId', $refereePlayerId)
			->where('invitedPlayerId', $invitedPlayerId);
		if (!$this->runExistsResult()) {
			//add it
			$this->utils->debug_log('add player id', $refereePlayerId, 'invitedPlayerId', $invitedPlayerId);
			return $this->insertReferral($refereePlayerId, $invitedPlayerId);
		} else {
			$this->utils->debug_log('ignore player id', $refereePlayerId, 'invitedPlayerId', $invitedPlayerId);
		}
		return null;
	}

	public function insertReferral($refereePlayerId, $invitedPlayerId) {
		$data = array(
			'playerId' => $refereePlayerId,
			'invitedPlayerId' => $invitedPlayerId,
			'referredOn' => $this->getNowForMysql(),
			'status' => $this->getReferralStatus($refereePlayerId),
		);
		return $this->insertData($this->tableName, $data);

	}

	public function getReferralStatus($playerId){
		//check if there is a limit
		$this->load->model(array('friend_referral_settings'));
		$friend_referral_settings = $this->CI->friend_referral_settings->getFriendReferralSettings();
		if($friend_referral_settings['enabled_referral_limit']==1){
			//get total paid referral
			$referrals = $this->getPlayerReferral($playerId, self::STATUS_PAID);
			if(count($referrals)>$friend_referral_settings['max_referral_released']){
				return self::STATUS_EXCEED_LIMIT;
			}
		}

        if($friend_referral_settings['enabled_referral_limit_monthly']==1){
            //get this month paid referral
            list($from, $to) = $this->utils->getThisMonthRange();
            $referrals = $this->getPlayerReferral($playerId, self::STATUS_PAID, $from, $to);
            if(count($referrals)>$friend_referral_settings['max_referral_released']){
                return self::STATUS_EXCEED_MONTHLY_LIMIT;
            }
        }

		return self::STATUS_NORMAL;
	}

	public function isUnprocessed($referral_id) {
		$this->db->from(self::TABLE_NAME);
		$this->db->where('referralId', $referral_id)->where('status', self::STATUS_NORMAL);

		return $this->runExistsResult();
	}

	/**
	 * overview:
	 * 		check login_ip between referrer and invited within 30 days,
	 * 		if found same ip, then ignore referrer's bonus
	 * @param integer $referrer_id
	 * @param integer $referred_id
	 * @return boolean
	 */
	public function existSameIp($referrer_id, $referred_id){
		$result = false;
		$check_from = $this->utils->getMinusDaysForMysql('29', 'Y-m-d');
		$check_to = $this->utils->getTodayForMysql();
		list($existCommonIp, $commonIpList) = $this->player_login_report->existCommonIpBetweenDate($referrer_id, $referred_id, $check_from, $check_to);
		$existCommonRegistrationIP = $this->player_model->existCommonRegistrationIP($referrer_id, $referred_id);

		if($existCommonIp || $existCommonRegistrationIP){
			$result = true;
		}

		return $result;
	}

	public function checkReferral() {

		$this->load->model(array('friend_referral_settings', 'total_player_game_hour', 'promorules',
			'player_model', 'withdraw_condition', 'transactions', 'player_promo', 'users', 'player_login_report'));

		$now = $this->utils->getNowForMysql();
		$settings = $this->friend_referral_settings->getFriendReferralSettings();
		$this->utils->debug_log('referral settings', $settings);
		$required_referred_deposit = isset($settings['ruleInDeposit']) ? $settings['ruleInDeposit'] : 0;
		$required_referred_bet = isset($settings['ruleInBet']) ? $settings['ruleInBet'] : 0;
		$required_referrer_bet = isset($settings['referrerBet']) ? $settings['referrerBet'] : 0;
		$required_referrer_deposit = isset($settings['referrerDeposit']) ? $settings['referrerDeposit'] : 0;
		$required_referrer_deposit_count = isset($settings['referrerDepositCount']) ? $settings['referrerDepositCount'] : 0;
		$required_referred_deposit_count = isset($settings['referredDepositCount']) ? $settings['referredDepositCount'] : 0;
		$required_referrer_bet_times = isset($settings['withdrawalCondition']) ? $settings['withdrawalCondition'] : 0;
		$referred_bonus = isset($settings['bonusAmount']) ? $settings['bonusAmount'] : 0;
		$referred_bonus_in_referred = isset($settings['bonusAmountInReferred']) ? $settings['bonusAmountInReferred'] : 0;
		$referrer_bonus_rate = isset($settings['bonusRateInReferrer']) ? $settings['bonusRateInReferrer'] : 0;


		$limit_enabled = isset($settings['enabled_referral_limit']) && $settings['enabled_referral_limit']==1 ? true : false;
        $monthly_limit_enabled = isset($settings['enabled_referral_limit_monthly']) && $settings['enabled_referral_limit_monthly']==1 ? true : false;
		$max_referral_released = isset($settings['max_referral_released']) ? (int)$settings['max_referral_released'] : 0;
		$enabled_referred_single_choice = isset($settings['enabled_referred_single_choice']) ? (int)$settings['enabled_referred_single_choice'] : 0;
		$disabled_same_ips_with_inviter = isset($settings['disabled_same_ips_with_inviter']) ? (int)$settings['disabled_same_ips_with_inviter'] : 0;

		$success = 0;
		$qualified = 0;
		$paid_array = array();

		$is_empty_referred_bonus = false;
		$is_empty_referred_bonus_in_referred = false;
		$is_empty_referrer_bonus_rate = false;
		if($referred_bonus <= 0){
			$is_empty_referred_bonus = true;
		}
		if($referrer_bonus_rate <= 0){
			$is_empty_referrer_bonus_rate = true;
		}
		if($referred_bonus_in_referred <= 0){
			$is_empty_referred_bonus_in_referred = true;
		}

		if ($is_empty_referred_bonus && $is_empty_referrer_bonus_rate && $is_empty_referred_bonus_in_referred) {
			if($is_empty_referred_bonus){
				$this->utils->debug_log('ignore referral because bonus <=0');
				if($this->utils->getConfig('ignore_ceated_transaction_and_set_status_to_paid') && $this->utils->getConfig('enabled_quest')){
					list($success, $qualified, $paid_array) = $this->processIgnoreReferralBecauseBonusIsZero($now, $limit_enabled, $monthly_limit_enabled, $max_referral_released, $enabled_referred_single_choice, $required_referred_deposit, $required_referred_bet, $required_referred_deposit_count, $disabled_same_ips_with_inviter);
				}
			}
			if($is_empty_referrer_bonus_rate){
				$this->utils->debug_log('ignore referral because bonus rate <=0');
			}
			if($is_empty_referred_bonus_in_referred){
				$this->utils->debug_log('ignore referral because Referred bonus <=0');
			}
			return array($success, $qualified, $paid_array);
		}

		$referral_list = $this->getUnprocessedReferrals();

		if (!empty($referral_list)) {
			$model = $this;
			$adminUserId = $this->users->getSuperAdminId();
			$promorulesId = $this->promorules->getSystemManualPromoRuleId(); // manual promo rules
			$promoCmsSettingId = $this->promorules->getSystemManualPromoCMSId(); // manual cms setting
			//$friend_referral_settings = $this->friend_referral_settings->getFriendReferralSettings();
			if(	$settings['promo_id'] != 0
				&& false // for When the condition comes from the"Referrer Setting" and the Promo Name show be: EN: Friend Referral / CN: 好友推荐
			) { // if bind a cms on friend referral
				$this->load->model(array('promorules'));
				$promoCmsSettingId = $settings['promo_id'];
				$promorulesId = $this->promorules->getPromorulesIdByPromoCmsId($promoCmsSettingId);
			}
			foreach ($referral_list as $referral) {

				$this->utils->debug_log('check referral ', $referral->playerId, 'referralId', $referral->referralId);

				$referrer_id = $referral->playerId;

                $disabledPromotionPlayer = $this->player_model->isDisabledPromotion($referrer_id);
                if($disabledPromotionPlayer){
                    $this->utils->debug_log('disabled promotion on player id:' . $referrer_id);
                    continue;
                }

				$referrerStatus = $this->utils->getPlayerStatus($referrer_id);
				if($referrerStatus != 0){
					$this->utils->debug_log('ignore, cause referrer player status not active', $referrerStatus, $referrer_id);
					continue;
				}

				// check if exceeded to limit
				if($limit_enabled){
                    $this->utils->debug_log('checkReferral lifetime limit');
					$total_paid_referrals = $this->getPlayerReferral($referrer_id, self::STATUS_PAID);

					if(count($total_paid_referrals)>=$max_referral_released){
						$update_data['status']=self::STATUS_EXCEED_LIMIT;
						$model->updatePlayerFriendReferral($referral->referralId, $update_data);
						$this->utils->debug_log('checkReferral tagged as exceed', $referral, $total_paid_referrals, $max_referral_released, $referrer_id);
						continue;
					}
				}

				if($monthly_limit_enabled){
                    $this->utils->debug_log('checkReferral monthly limit');
                    //get this month paid referral
                    list($from, $to) = $this->utils->getThisMonthRange();
                    $total_paid_referrals = $this->getPlayerReferral($referrer_id, self::STATUS_PAID, $from, $to);

                    if(count($total_paid_referrals)>=$max_referral_released){
                        $update_data['status']=self::STATUS_EXCEED_MONTHLY_LIMIT;
                        $model->updatePlayerFriendReferral($referral->referralId, $update_data);
                        $this->utils->debug_log('checkReferral monthly tagged as exceed', $referral, $total_paid_referrals, $max_referral_released, $referrer_id);
                        continue;
                    }
                }

                if($this->utils->getConfig('enable_registration_date_on_friend_referraL_setting')){
                	$registered_from_setting = isset($settings['registered_from']) ? $settings['registered_from'] : null;
                	$registered_to_setting = isset($settings['registered_to']) ? $settings['registered_to'] : null;
                	$is_signup_date_accept = true;

 					$refereeInfo = (array) $this->player_model->getPlayerInfoById($referral->invitedPlayerId);
 					$refereeSignUpdate = date("Y-m-d", strtotime($refereeInfo['playerCreatedOn']));

 					if( !empty($registered_from_setting) && ( $refereeSignUpdate < $registered_from_setting ) ){
 						$is_signup_date_accept = false;
 					}

 					if( !empty($registered_to_setting) && ( $refereeSignUpdate > $registered_to_setting ) ){
 						$is_signup_date_accept = false;
 					}

 					$this->utils->debug_log('checkReferral signup date setting', $registered_from_setting, $registered_to_setting);
                	$this->utils->debug_log('checkReferral referee signup', $refereeSignUpdate);

                	if(!$is_signup_date_accept){
                		$update_data['status']=self::STATUS_SIGNUP_DATE_NOT_IN_RANGE;
                        $model->updatePlayerFriendReferral($referral->referralId, $update_data);
                        $this->utils->debug_log('checkReferral is_signup_date_accept', $is_signup_date_accept);
                		continue;
                	}
                }

				$is_referr_condition_meet = false;

				$successTrans = $this->lockAndTransForPlayerBalance($referrer_id, function ()
					 use ($model, $referral, $adminUserId, $promorulesId, $promoCmsSettingId, $now, $referred_bonus, $referrer_bonus_rate,
						    $required_referred_bet, $required_referrer_bet_times, $required_referred_deposit, &$qualified,
                            $required_referrer_bet, $required_referrer_deposit, $required_referrer_deposit_count, &$is_referr_condition_meet,
							$required_referred_deposit_count, $enabled_referred_single_choice, $disabled_same_ips_with_inviter) {

						$referral_id = $referral->referralId;
						$referrer_id = $referral->playerId;
						$referred_id = $referral->invitedPlayerId;
                        $referrer_date = $referral_date = $referral->referredOn;
						if($this->utils->getConfig('calculate_referrer_deposit_bet_by_signup_date')){
							//get referrer registration to get its signup date $referrer_id
							$refererInfo = (array) $this->player_model->getPlayerInfoById($referrer_id);
							if(!$refererInfo){
								return false;
							}
							$referrer_date = date("Y-m-d H:i:s", strtotime($refererInfo['playerCreatedOn']));
                        }

                        $add_manual = $this->utils->getConfig('accumulaten_manual_bonus_in_check_referral');

						list($totalDeposit, $totalWithdrawal, $totalBonus) = $model->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers(array($referred_id), $referral_date, $now, $add_manual);
						list($totalBet, $totalWin, $totalLoss) = $model->total_player_game_hour->getPlayerTotalBetsWinsLossByDatetime($referred_id, $referral_date, $now);

						$totalDepositCount = 0;
						if($this->utils->getConfig('enable_friend_referral_referred_deposit_count')){
							$totalDepositCount = $model->transactions->getPlayerTotalDepositCount($referred_id, $referrer_date, $now);
							$this->utils->debug_log('totalDepositCount', $totalDepositCount, 'required_referred_deposit_count', $required_referred_deposit_count);
						}

                        $referrerTotalDeposit = 0;
                        $referrerTotalBet = 0;
						if($this->utils->getConfig('enable_friend_referral_referrer_deposit')){
                            list($referrerTotalDeposit, $referrerTotalWithdrawal, $referrerTotalBonus) = $model->transactions->getPlayerTotalDepositWithdrawalBonusByDatetime($referrer_id, $referrer_date, $now);
                            $this->utils->debug_log('referrerTotalDepositAmount', $referrerTotalDeposit, 'required_referrer_deposit', $required_referrer_deposit);
                        }

						if($this->utils->getConfig('enable_friend_referral_referrer_bet')){
                            list($referrerTotalBet, $referrerTotalWin, $referrerTotalLoss) = $model->total_player_game_hour->getPlayerTotalBetsWinsLossByDatetime($referrer_id, $referrer_date, $now);
                            $this->utils->debug_log('referrerTotalBettingAmount', $referrerTotalBet, 'required_referrer_bet', $required_referrer_bet);
                        }

						$referrerTotalDepositCount = 0;
                        if($this->utils->getConfig('enable_friend_referral_referrer_deposit_count')){
                            $referrerTotalDepositCount = $model->transactions->getPlayerTotalDepositCount($referrer_id, $referrer_date, $now);
                            $this->utils->debug_log('referrerTotalDepositCount', $referrerTotalDepositCount, 'required_referrer_deposit_count', $required_referrer_deposit_count);
                        }

						$model->utils->debug_log('referrer_id', $referrer_id, 'referred_id', $referred_id, 'referral_date', $referral_date, 'referrer_date', $referrer_date, 'required_referrer_bet_times', $required_referrer_bet_times,
							'totalDepositAmount', $totalDeposit, 'required_referred_deposit', $required_referred_deposit, 'totalBettingAmount', $totalBet, 'required_referred_bet', $required_referred_bet, 'disabled_same_ips_with_inviter', $disabled_same_ips_with_inviter);

						$referredFirstDepAmt = 0;
						if($this->utils->getConfig('enabled_referrer_bonus_rate') && !empty($referrer_bonus_rate)){
							$referredFirstDepAmt = $model->transactions->getFirstDepositAmount($referred_id, $referral_date);
							if(empty($referredFirstDepAmt)){
								$model->utils->debug_log('ignore, cause referred player missing first deposit');
								return false;
							}

							$original_referred_bonus = $referred_bonus;
							$bonus_with_rate = ($referrer_bonus_rate/100) * $referredFirstDepAmt;
							$referred_bonus = $bonus_with_rate;
							$model->utils->debug_log('original bonus amount', $original_referred_bonus, 'first Deposit Amount', $referredFirstDepAmt, 'bonus with rate', $bonus_with_rate);
						}

						$referred_req_operator = '&&';
						if($enabled_referred_single_choice){
							$referred_req_operator = '||';
							$this->utils->debug_log('referred_req_operator', $referred_req_operator);
						}

						switch ($referred_req_operator) {
							case '||':
								$referred_meet_requirements = (
									$model->utils->compareResultCurrency($totalDeposit, '>=', $required_referred_deposit)
									|| $model->utils->compareResultCurrency($totalBet, '>=', $required_referred_bet)
									|| $model->utils->compareResultCurrency($totalDepositCount, '>=', $required_referred_deposit_count)
								);
								break;
							case '&&':
							default:
								$referred_meet_requirements = (
									$model->utils->compareResultCurrency($totalDeposit, '>=', $required_referred_deposit)
									&& $model->utils->compareResultCurrency($totalBet, '>=', $required_referred_bet)
									&& $model->utils->compareResultCurrency($totalDepositCount, '>=', $required_referred_deposit_count)
								);
								break;
						}

						$referrer_meet_requirements = (
							$model->utils->compareResultCurrency($referrerTotalDeposit, '>=', $required_referrer_deposit)
							&& $model->utils->compareResultCurrency($referrerTotalBet, '>=', $required_referrer_bet)
							&& $model->utils->compareResultCurrency($referrerTotalDepositCount, '>=', $required_referrer_deposit_count)
						);

						if ($referred_meet_requirements && $referrer_meet_requirements && $model->isUnprocessed($referral_id)) {

							$is_referr_condition_meet = true;

							if($this->utils->getConfig('disabled_same_ips_with_inviter') && $disabled_same_ips_with_inviter){
								$existCommonIp = $this->existSameIp($referrer_id, $referred_id);
								if($existCommonIp){
									$qualified++;
									$update_data = ['status' => self::STATUS_PAID, 'same_ip_with_referrer' => 1];
									$this->updatePlayerFriendReferral($referral_id, $update_data);
									$this->utils->debug_log('only update status and ignore paid bonus to referrer cause same ip');
									return true;
								}
							}

							$paid_array[] = array('player id:' . $referrer_id . ',invitedPlayerId:' . $referred_id .
								',totalDeposit:' . $totalDeposit . ',required_referred_deposit:' . $required_referred_deposit .
								',totalBet:' . $totalBet . ',required_referred_bet:' . $required_referred_bet);
							$qualified++;

							// $this->startTrans();

							# UPDATE MAIN WALLET AND SAVE TO TRANSACTION
							$transaction = $model->player_model->updateMainWalletBalance(array(
								'amount' => $referred_bonus,
								'transaction_type' => Transactions::PLAYER_REFER_BONUS,
								'from_id' => Transactions::SYSTEM_ID,
								'from_type' => Transactions::ADMIN,
								'to_id' => $referrer_id,
								'to_type' => Transactions::PLAYER,
								'note' => sprintf('%s referral bonus for referring %s', $model->utils->formatCurrencyNoSym($referred_bonus), $referred_id),
								'status' => Transactions::APPROVED,
								'flag' => Transactions::PROGRAM,
							));

							if ($transaction) {
                                $bet_amount_withdraw_condition = $required_referrer_bet_times * $referred_bonus;
								//update player promo
								$playerBonusAmount = $referred_bonus;
                                $extra_info = ['order_generated_by' => Player_promo::ORDER_GENERATED_BY_CHECK_REFERRAL];
								$player_promo_id = $model->player_promo->approvePromoToPlayer($referrer_id,	$promorulesId, $playerBonusAmount,
                                    $promoCmsSettingId, $adminUserId, null, $bet_amount_withdraw_condition, $extra_info);

                                //add requestAdmin
								$model->player_promo->addPlayerPromoRequestBy($player_promo_id, $adminUserId, null);

								$model->transactions->updatePlayerPromoId($transaction['id'], $player_promo_id);

								# GET FRIEND REFERRAL PROMOID, SET BY ADMIN - NEW FEATURE
								$bindPromoIdToFriendReferral = null;
								// if ($this->utils->isEnabledFeature('bind_promorules_to_friend_referral')) {
								$bindPromoIdToFriendReferral = $promorulesId;
								// }

								# CREATE WITHDRAWAL CONDITION
								$model->withdraw_condition->createWithdrawConditionForFriendReferral($referrer_id, $transaction['id'],
									$now, $bet_amount_withdraw_condition, $referred_bonus, $required_referrer_bet_times, $bindPromoIdToFriendReferral, $player_promo_id);

								# SET REFERRAL TO PROCESSED
								$model->paidPlayerFriendReferral($referral_id, $transaction['id']);

							}// EOF if ($transaction) {...

							# COMMIT CHANGES
							// if ($this->endTransWithSucc()) {
							// }
							return true;

						}else{
							$this->utils->debug_log('ignore referral player', $referral_id);
						}

						return true;

					});// EOF $successTrans = $this->lockAndTransForPlayerBalanc($referrer_id, function ()...


$this->utils->debug_log('OGP-26883.408.is_referr_condition_meet', $is_referr_condition_meet
, 'is_empty_referred_bonus_in_referred:', $is_empty_referred_bonus_in_referred
, 'promorulesId:', $promorulesId // 0
, 'promoCmsSettingId:', $promoCmsSettingId // 0
, 'getConfig.enabled_referred_bonus:', $this->utils->getConfig('enabled_referred_bonus')
);

				if( $is_referr_condition_meet
					&& ! $is_empty_referred_bonus_in_referred
					&& $this->utils->getConfig('enabled_referred_bonus')
				){
					// handle referred_bonus_in_referred
					$referred_id = $referral->invitedPlayerId;

					$disabledPromotionReferredPlayer = $this->player_model->isDisabledPromotion($referred_id);
					if($disabledPromotionReferredPlayer){
						$this->utils->debug_log('disabled promotion on referred player id:' . $referred_id);
						continue;
					}

					$referredStatus = $this->utils->getPlayerStatus($referred_id);
					if($referredStatus != 0){
						$this->utils->debug_log('ignore, cause referred player status not active', $referredStatus, $referred_id);
						continue;
					}

					$successTrans4referredBonus = $this->lockAndTransForPlayerBalance( $referred_id
					, function () use ($model, $referral, $referred_bonus_in_referred, $required_referrer_bet_times, $promorulesId, $adminUserId, $promoCmsSettingId, $now ) {

						$referred_id = $referral->invitedPlayerId;
						$referrer_id = $referral->playerId;
						# UPDATE MAIN WALLET AND SAVE TO TRANSACTION
						$transaction = $model->player_model->updateMainWalletBalance(array(
							'amount' => $referred_bonus_in_referred,
							'transaction_type' => Transactions::PLAYER_REFERRED_BONUS,
							'from_id' => Transactions::SYSTEM_ID,
							'from_type' => Transactions::ADMIN,
							'to_id' => $referred_id,
							'to_type' => Transactions::PLAYER,
							'note' => sprintf('%s referred bonus from referr %s', $model->utils->formatCurrencyNoSym($referred_bonus_in_referred), $referrer_id),
							'status' => Transactions::APPROVED,
							'flag' => Transactions::PROGRAM,
						));
$this->utils->debug_log('OGP-26883.433.transaction', $transaction
, 'referral:', $referral
);
						if ($transaction) {

							/// @todo WITHDRAWAL CONDITION
							$bet_amount_withdraw_condition = $required_referrer_bet_times * $referred_bonus_in_referred;
							//update player promo
							$playerBonusAmount = $referred_bonus_in_referred;
							$extra_info = ['order_generated_by' => Player_promo::ORDER_GENERATED_BY_CHECK_REFERRAL];
							$player_promo_id = $model->player_promo->approvePromoToPlayer($referred_id,	$promorulesId, $playerBonusAmount,
								$promoCmsSettingId, $adminUserId, null, $bet_amount_withdraw_condition, $extra_info);
$this->utils->debug_log('OGP-26883.445.referred_id', $referred_id // 31815
, 'referrer_id:', $referrer_id
, 'promorulesId:', $promorulesId // 0
, 'playerBonusAmount:', $playerBonusAmount // 99
, 'promoCmsSettingId:', $promoCmsSettingId // 0
, 'adminUserId:' , $adminUserId, null // 1
, 'bet_amount_withdraw_condition:', $bet_amount_withdraw_condition // 1683
, 'extra_info:', $extra_info // order_generated_by:11
);
							//add requestAdmin
							$model->player_promo->addPlayerPromoRequestBy($player_promo_id, $adminUserId, null);

							$model->transactions->updatePlayerPromoId($transaction['id'], $player_promo_id);

							# GET FRIEND REFERRAL PROMOID, SET BY ADMIN - NEW FEATURE
							$bindPromoIdToFriendReferral = null;
							// if ($this->utils->isEnabledFeature('bind_promorules_to_friend_referral')) {
							$bindPromoIdToFriendReferral = $promorulesId;
							// }

							# CREATE WITHDRAWAL CONDITION
							$model->withdraw_condition->createWithdrawConditionForFriendReferral($referred_id, $transaction['id'],
								$now, $bet_amount_withdraw_condition, $referred_bonus_in_referred, $required_referrer_bet_times, $bindPromoIdToFriendReferral, $player_promo_id);


							# SET REFERRAL TO PROCESSED : paidPlayerFriendReferral2invited()
							$model->paidPlayerFriendReferral2invited($referred_id, $transaction['id'], $referrer_id);
$this->utils->debug_log('OGP-26883.475.referred_id', $referred_id // 31815
, 'referrer_id:', $referrer_id
, 'transaction:', $transaction
);
						}
						return true;
					}); // EOF $successTrans = $this->lockAndTransForPlayerBalance($referrer_id

				} // EOF if( $is_referr_condition_meet && ! $is_empty_referred_bonus_in_referred && $this->utils->getConfig('enabled_referred_bonus') ){...


				if ( $successTrans || $successTrans4referredBonus ) {
					$success++;
				}
			} // EOF foreach ($referral_list as $referral) {...
		}

		return array($success, $qualified, $paid_array);
	}

	public function processIgnoreReferralBecauseBonusIsZero($now, $limit_enabled, $monthly_limit_enabled, $max_referral_released, $enabled_referred_single_choice, $required_referred_deposit, $required_referred_bet, $required_referred_deposit_count, $disabled_same_ips_with_inviter) {
		try{
			$this->startTrans();
			$startTime = microtime(true);
			$startEnd = $this->utils->getConfig('getUnprocessedReferralsByDateTimeStartEnd');
			$fromDate = isset($startEnd['from']) ? $startEnd['from'] : '';
			$toDate = isset($startEnd['to']) ? $startEnd['to'] : $now;
			$referralList = $this->getUnprocessedReferralsByDateTime($fromDate, $toDate);

			$this->utils->printLastSQL();

			$success = 0;
			$qualified = 0;
			$paidArray = [];
			$ignoreList = [];

			if(!empty($referralList)){
				foreach ($referralList as $referral) {
					$referrer_id = $referral->playerId;
					$referral_id = $referral->referralId;
					$referred_id = $referral->invitedPlayerId;
					$referral_date = $referral->referredOn;

					$disabledPromotionPlayer = $this->player_model->isDisabledPromotion($referrer_id);
					if($disabledPromotionPlayer){
						$this->utils->debug_log('disabled promotion on player id:' . $referrer_id);
						continue;
					}

					$referrerStatus = $this->utils->getPlayerStatus($referrer_id);
					if($referrerStatus != 0){
						$this->utils->debug_log('ignore, cause referrer player status not active', $referrerStatus, $referrer_id);
						continue;
					}

					// check if exceeded to limit
					if($limit_enabled){
						$this->utils->debug_log('checkReferral lifetime limit');
						$total_paid_referrals = $this->getPlayerReferral($referrer_id, self::STATUS_PAID);

						if(count($total_paid_referrals)>=$max_referral_released){
							$update_data['status']=self::STATUS_EXCEED_LIMIT;
							$this->updatePlayerFriendReferral($referral->referralId, $update_data);
							$this->utils->debug_log('checkReferral tagged as exceed', $referral, $total_paid_referrals, $max_referral_released, $referrer_id);
							continue;
						}
					}

					if($monthly_limit_enabled){
						$this->utils->debug_log('checkReferral monthly limit');
						//get this month paid referral
						list($from, $to) = $this->utils->getThisMonthRange();
						$total_paid_referrals = $this->getPlayerReferral($referrer_id, self::STATUS_PAID, $from, $to);

						if(count($total_paid_referrals)>=$max_referral_released){
							$update_data['status']=self::STATUS_EXCEED_MONTHLY_LIMIT;
							$this->updatePlayerFriendReferral($referral->referralId, $update_data);
							$this->utils->debug_log('checkReferral monthly tagged as exceed', $referral, $total_paid_referrals, $max_referral_released, $referrer_id);
							continue;
						}
					}

					$is_condition_meet_and_success = false;
					$totalDepositCount = 0;

					$add_manual = $this->utils->getConfig('accumulaten_manual_bonus_in_check_referral');

					list($totalDeposit, $totalWithdrawal, $totalBonus) = $this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers(array($referred_id), $referral_date, $now, $add_manual);
					list($totalBet, $totalWin, $totalLoss) = $this->total_player_game_hour->getPlayerTotalBetsWinsLossByDatetime($referred_id, $referral_date, $now);

					$referred_req_operator = '&&';
					if($enabled_referred_single_choice){
						$referred_req_operator = '||';
						$this->utils->debug_log('referred_req_operator', $referred_req_operator);
					}

					switch ($referred_req_operator) {
						case '||':
							$referred_meet_requirements = (
								$this->utils->compareResultCurrency($totalDeposit, '>=', $required_referred_deposit)
								|| $this->utils->compareResultCurrency($totalBet, '>=', $required_referred_bet)
								|| $this->utils->compareResultCurrency($totalDepositCount, '>=', $required_referred_deposit_count)
							);
							break;
						case '&&':
						default:
							$referred_meet_requirements = (
								$this->utils->compareResultCurrency($totalDeposit, '>=', $required_referred_deposit)
								&& $this->utils->compareResultCurrency($totalBet, '>=', $required_referred_bet)
								&& $this->utils->compareResultCurrency($totalDepositCount, '>=', $required_referred_deposit_count)
							);
							break;
					}

					if ($referred_meet_requirements && $this->isUnprocessed($referral_id)) {
						$is_condition_meet_and_success = true;

						if($this->utils->getConfig('disabled_same_ips_with_inviter') && $disabled_same_ips_with_inviter){
							$existCommonIp = $this->existSameIp($referrer_id, $referred_id);
							if($existCommonIp){
								$update_data['same_ip_with_referrer'] = 1;
							}
						}

						$paidArray[] = array('player id:' . $referrer_id . ',invitedPlayerId:' . $referred_id .
							',totalDeposit:' . $totalDeposit . ',required_referred_deposit:' . $required_referred_deposit .
							',totalBet:' . $totalBet . ',required_referred_bet:' . $required_referred_bet . ',totalDepositCount:' . $totalDepositCount . ',required_referred_deposit_count:' . $required_referred_deposit_count);
						$qualified++;
					}else{
						$ignoreList[] = ['referral_id' => $referral_id, 'referred_meet_requirements' => $referred_meet_requirements];
					}

					if($is_condition_meet_and_success){
						$update_data['status']=self::STATUS_PAID;

						if(!$this->utils->getConfig('dry_run_updatePlayerFriendReferral')){
							$this->updatePlayerFriendReferral($referral_id, $update_data);
						}

						$success++;
					}
				}
			}

			$endTime = microtime(true);
			$processTime = $endTime - $startTime;
			$result = ($success > 0) && $this->endTransWithSucc();

			$this->utils->info_log('ignore referral because bonus <=0, set status to paid', $success, $qualified, $paidArray, 'ignore list',$ignoreList, 'process execution time', $processTime, 'result', $result);

			if(!$result){
				throw new Exception("result false");
			}
		}catch(Exception $e){
			$this->utils->debug_log('processIgnoreReferralBecauseBonusIsZero', $e->getMessage());
			$this->rollbackTrans();
		}finally{
			return array($success, $qualified, $paidArray);
		}
	}

	public function countPlayerInvitations($from = null, $to = null){
		$this->db->select('playerId, COUNT(*) as totalValidInvites');
        $this->db->from('playerfriendreferral');
        $this->db->where('status', self::STATUS_PAID);

		if(!empty($from) && !empty($to)){
			$this->db->where('referredOn >=', $from);
			$this->db->where('referredOn <=', $to);
		}

        $this->db->group_by('playerId');
		return $this->runMultipleRow();
	}

	public function countReferralByPlayerId($playerId = null, $status = null, $from = null, $to = null){
		$totalReferrals = 0;

		if(empty($playerId)){
			return $totalReferrals;
		}

		$this->db->select('count(referralId) as count');
		$this->db->from($this->tableName);
		$this->db->where('playerId', $playerId);

		if(!empty($status)){
			$this->db->where('status', $status);
		}

		if(!empty($from) && !empty($to)){
			$this->db->where('referredOn >=', $from);
			$this->db->where('referredOn <=', $to);
		}

		$count = $this->runOneRowOneField('count');
		if(!empty($count)){
			$totalReferrals = $count;
		}

		return $totalReferrals;
	}

	public function getPlayerInvitations($playerId, $questCategoryId, $from=null, $to=null){
		$this->db->from('player_quest_invitations');
		$this->db->where('playerId', $playerId);
		$this->db->where('questCategoryId', $questCategoryId);

		if(!empty($from) && !empty($to)){
            $this->db->where('lastSyncAt >=', $from);
            $this->db->where('lastSyncAt <=', $to);
        }

		return $this->runOneRow();
	}

	public function insertPlayerInvitations($data){
		$this->db->insert('player_quest_invitations', $data);
		return $this->db->affected_rows();
	}

	public function updatePlayerInvitations($playerId, $categoryId, $data){
		$this->db->set($data)->where('playerId', $playerId)->where('questCategoryId', $categoryId)->update('player_quest_invitations');
		return $this->db->affected_rows();
	}

	public function getReferralQuestBonusByPlayerId($playerId){
		$this->load->model(['quest_manager']);
		$referralQuestBonus = 0;
		$conditions['questManagerType'] = 3; //invite friends

		$referralQuestManager = $this->quest_manager->getAllQuestManager(_COMMAND_LINE_NULL, _COMMAND_LINE_NULL, $conditions);
		if(empty($referralQuestManager)){
			return $referralQuestBonus;
		}

		$activeQuestRuleId = []; // for single quest
		$validQuestJobId = []; // for multiple quest job
		$questProgress = [];
		foreach($referralQuestManager as $questManager){
			$questManagerId = $questManager['questManagerId'];
			$levelType = $questManager['levelType'];
			$isHierarchy = $levelType == Quest_manager::QUEST_LEVEL_TYPE_HIERARCHY;

			if($isHierarchy){
				$questJobs = $this->quest_manager->getQuestJobByQuestManagerId($questManagerId);
				if(!empty($questJobs)){
					foreach($questJobs as $job){
						// Mission task condition settings: 5 (invite friends)
						if($job['questConditionType'] != 5){
							continue;
						}
						$validQuestJobId[$questManagerId][$job['questJobId']] = $job['questJobId'];
					}
				}
			}else{
				$questRuleId = $questManager['questRuleId'];
				$activeQuestRuleId = $this->quest_manager->getQuestRuleDetails($questRuleId);

				// only calculate active quest rule's bonus
				if(empty($activeQuestRuleId)){
					continue;
				}
			}

			$conditions = ['rewardStatus' => [Quest_manager::QUEST_REWARD_STATUS_RECEIVED]];
			$questProgress = $this->quest_manager->getQuestProgressByPlayer($playerId, $questManagerId, null, null, $isHierarchy, null, $conditions);

			if(empty($questProgress)){
				continue;
			}

			foreach($questProgress as $state){
				if(empty($state['transactionId']) || empty($state['bonusAmount'])){
					continue;
				}

				if($isHierarchy && !empty($validQuestJobId[$questManagerId][$state['questJobId']])){ // multiple quest job
					$referralQuestBonus += $state['bonusAmount'];
				}else if(empty($state['questJobId'])){ // single quest don't have questJobId
					$referralQuestBonus += $state['bonusAmount'];
				}
			}
		}
		return $referralQuestBonus;
	}

	public function getPlayerFriendRefferedCountAndTotalBets($player_id, $startdate, $enddate) {
		$today_referred_count = 0;
		$today_total_bets = 0;
		$referred_sql = "SELECT * FROM playerfriendreferral WHERE playerId = '{$player_id}'";
		$referred_query = $this->db->query($referred_sql);
		$referreds = $referred_query->result_array();
		$today_referred_sql = $referred_sql . " AND referredOn >= '$startdate' AND referredOn <= '$enddate'";
		$today_referred_query = $this->db->query($today_referred_sql);
		if ($today_referred_query->num_rows() > 0)
			$today_referred_count = $today_referred_query->num_rows();
		foreach ($referreds as $referred) {
			$today_total_bet_sql = "SELECT sum(bet_amount) bet_amount FROM game_logs WHERE player_id = '{$referred['invitedPlayerId']}' AND end_at >= '$startdate' AND end_at <= '$enddate'";
			$total_bets_query = $this->db->query($today_total_bet_sql);
			if ($total_bets_query->num_rows() > 0)
			 	$today_total_bets += (float)$this->getOneRowOneField($total_bets_query, 'bet_amount');
		}
		return Array($today_referred_count, $today_total_bets);
	}

    public function getPlayerTotalFriendRefferalCountByDatetimeAndStatus($fromDatetime, $toDatetime, $player_id = null, $status = self::STATUS_PAID){
        $this->db->select('pf.playerId as player_id, count(pf.referralId) as total_referral')
            ->from('playerfriendreferral as pf')
            ->join('transactions as t', 't.id = pf.transactionId', 'left')
            // Filter referral datetime
            ->where('pf.referredOn >=', $fromDatetime)
            ->where('pf.referredOn <=', $toDatetime)
            // Filter transaction datetime
            ->where('t.created_at >=', $fromDatetime)
            ->where('t.created_at <=', $toDatetime)
            ->where('pf.transactionId IS NOT NULL', null, false)
            ->where('pf.status', $status);

        if(!empty($player_id)){
            $this->db->where('playerId', $player_id);
        }
        $this->db->group_by('player_id');

        return $this->runMultipleRowArray();
	}

	public function getReferrerByInvitedPlayerId($invitedPlayerId) {
		$this->db->select('playerId');
		$this->db->from(self::TABLE_NAME);
		$this->db->where('invitedPlayerId', $invitedPlayerId);
		return $this->runOneRowOneField('playerId');
	}

	public function getTotalReferralBonusByPlayerId($player_id) {
		$this->db->select_sum('transactions.amount')
		    ->from(self::TABLE_NAME)
		    ->join('transactions', 'transactions.id = playerfriendreferral.transactionId')
			->where('playerfriendreferral.playerId', $player_id);

		$amount = $this->runOneRowOneField('amount');
		return $amount !== NULL ? $amount : 0;
	}

	public function getPlayerReferral($playerId=null, $status=null, $from=null, $to=null, $distinct=false) {
		if($distinct){
			$this->db->distinct();
		}
		$this->db->select('playerId');
		$this->db->from(self::TABLE_NAME);
		if($playerId){
			$this->db->where('playerId', $playerId);
		}
		if($status){
			$this->db->where('status', $status);
		}
        if(!empty($from) && !empty($to)){
            $this->db->where('referredOn >=', $from);
            $this->db->where('referredOn <=', $to);
        }
		return $this->runMultipleRow();
	}

	public function getPlayerReferralList($playerId=null, $status=null, $from=null, $to=null, $invitedPlayerId=null) {
        $this->db->select('playerId, invitedPlayerId AS invitedUserId, referredOn AS invitedTime, referredOn AS registerTime, status, status4invited, referralId');
        $this->db->from(self::TABLE_NAME);
        if($playerId){
        	$this->db->where('playerId', $playerId);
        }
        if($status){
            $this->db->where('status', $status);
        }
        if($invitedPlayerId){
        	$this->db->where('invitedPlayerId', $invitedPlayerId);
        }
        if(!empty($from) && !empty($to)){
            $this->db->where('referredOn >=', $from);
            $this->db->where('referredOn <=', $to);
        }
        return $this->runMultipleRow();
    }

    public function getReferralByReferralId($referralId) {
		$query = $this->db->get_where(self::TABLE_NAME, array('referralId' => $referralId));
		return $query->row_array();
	}

    public function updatePlayerFriendReferralByType($referralId, $transId, $playerId){
        $result = false;
        $referral = $this->getReferralByReferralId($referralId);
        if(!empty($referral)){
            switch ($playerId){
                case $referral['invitedPlayerId']:
                    $result = $this->paidPlayerFriendReferral2invited($playerId, $transId);
                    break;
                case $referral['playerId']:
                    $result = $this->paidPlayerFriendReferral($referralId, $transId);
                    break;
            }
        }
        return $result;
    }

	public function updateOtherInfoByCustomPromo($playerId, $promorulesId, $playerPromoId, $referral_id, $bonusTransId = null, &$extra_info){
		$this->load->model(['promorules']);
		$result = false;
		$PromoDetail = $this->promorules->getPromoDetailsWithFormulas($promorulesId);
		$bonus_condition = !empty($PromoDetail['formula']['bonus_condition']) ? $PromoDetail['formula']['bonus_condition'] : null;
		$promo_class = !empty($bonus_condition['class']) ? $bonus_condition['class'] : null;

		if(empty($promo_class)){
			return $result;
		}

		switch ($promo_class) {
			case 'promo_rule_t1t_common_brazil_referral_daily_bonus':
				if(!empty($extra_info['referrer_cashback']) && !empty($playerPromoId)){
					$release_date = !empty($extra_info['release_date']) ? $extra_info['release_date'] : $this->utils->getTodayForMysql();
					$cashback_info = $extra_info['referrer_cashback'];
					$result = $this->createFriendReferralLevel($cashback_info, $playerPromoId, $release_date);
				}
				break;
			case 'promo_rule_ole777idr_referral_bonus':
				$result = $this->updatePlayerFriendReferralByType($referral_id, $bonusTransId, $playerId);
				break;
		}
		return $result;
	}

	public function getPlayerReferralDepositors($playerId){
		$customTable = 'player_friend_referral_custom_details';
		$this->db->select('referred_depositors_count, referred_actual_depositors_count')
				 ->from($customTable)
				 ->where('playerId', $playerId);
		return $this->runOneRowArray();
	}

	public function syncPlayerFriendReferralCustomDetails($playerId, $records){
		$customTable = 'player_friend_referral_custom_details';
		$referred_depositors_count = !empty($records['referred_depositors_count'])?$records['referred_depositors_count']:0;
		$referred_actual_depositors_count = !empty($records['referred_actual_depositors_count'])?$records['referred_actual_depositors_count']:0;

		$data = [
			'referred_depositors_count' => $referred_depositors_count,
			'referred_actual_depositors_count' => $referred_actual_depositors_count
		];

		$this->db->from($customTable);
		$this->db->where('playerId', $playerId);

		if (!$this->runExistsResult()) {
			$data['playerId'] = $playerId;
			return $this->insertData($customTable, $data);
		}else{
			$data['updated_at'] = $this->utils->getNowForMysql();
			return $this->updateData('playerId', $playerId, $customTable, $data);
		}
	}

	public function updateReferredDepositCount($force_today, $dry_run){
		$start = '2000-01-01 00:00:00';
		$end = $this->utils->getYesterdayForMysql().' '.Utils::LAST_TIME;
		if($force_today){
			$end = $this->utils->getNowForMysql();
		}

		$list = $this->getPlayerReferralList(null, null, $start, $end);

		$success_cnt = 0;
		$success_list = [];
		if(empty($list)){
			return [$success_cnt, $success_list];
		}

		$info = [];
		$customTable = 'player_friend_referral_custom_details';
		$this->load->model('transactions');
		$this->utils->debug_log('getPlayerReferralList list', $list);

		foreach ($list as $row) {		
			$playerId = $row->playerId;
			$invitedUserId = $row->invitedUserId;
			$invitedTime = $row->invitedTime;
			$referralId = $row->referralId;

			$invitedDepCnt = $this->transactions->countDepositByPlayerId($invitedUserId, $invitedTime, $end);
			$invitedDepCnt = empty($invitedDepCnt)?0:$invitedDepCnt;
			$invitedTotalDep = $this->transactions->sumDepositAmount($invitedUserId, $invitedTime, $end, 0);
			$invitedTotalDep = empty($invitedTotalDep)?0:$invitedTotalDep;
			$this->utils->debug_log('invited conditions', ['referralId'=> $referralId, 'playerId'=>$playerId, 'invitedUserId'=>$invitedUserId, 'invitedDepCnt'=>$invitedDepCnt, 'invitedTotalDep'=>$invitedTotalDep]);
			
			if(!empty($info[$playerId])){
				if(!empty($invitedDepCnt)){
					$info[$playerId]['referred_depositors_count'] += 1;
				}
				if($invitedTotalDep >= 20){
					$info[$playerId]['referred_actual_depositors_count'] += 1;
				}
				continue;
			}

			$info[$playerId]['referred_depositors_count'] = 0;
			$info[$playerId]['referred_actual_depositors_count'] = 0;

			if(!empty($invitedDepCnt)){
				$info[$playerId]['referred_depositors_count'] += 1;
			}
			if($invitedTotalDep >= 20){
				$info[$playerId]['referred_actual_depositors_count'] += 1;
			}
		}

		$this->utils->debug_log('updateReferredDepositCount info', $info);

		if($dry_run){
			return [$success_cnt, $success_list];
		}

		if(!empty($info)){
			foreach($info as $playerId => $records){
				$this->syncPlayerFriendReferralCustomDetails($playerId, $records);
				$success_cnt++;
				$success_list[] = $playerId;
			}
		}
		return [$success_cnt, $success_list];
	}

	public function getKingReferralDailyBonusList($player_id, $settings){
		$this->load->model(['player_promo', 'transactions', 'total_player_game_day']);
		$promorulesId = $settings['promorulesId'];
		$referral_start = $settings['referral_start'];
		$referral_end = $settings['referral_end'];
		$deposit_start = $settings['deposit_start'];
		$deposit_end = $settings['deposit_end'];
		$betting_start = $settings['betting_start'];
		$betting_end = $settings['betting_end'];
		$gameTypeId = $settings['gameTypeId'];
		$gamePlatformId = $settings['gamePlatformId'];
		$min_deposit_cnt = $settings['min_deposit_cnt'];
		$min_bet = $settings['min_bet'];

		$raw_list = $this->getPlayerReferralList($player_id, null, $referral_start, $referral_end);
		if(empty($raw_list)){
			return [];
		}

		$referral_list = [];
		if(!empty($raw_list)){
			foreach ($raw_list as $list) {
				$playerId = $list->playerId;
				if(!empty($referral_list[$list->playerId])){
					$referral_list[$list->playerId][] = $list;
					continue;
				}
				$referral_list[$list->playerId][] = $list;
			}

			$applied_list = [];
			$qualified_list = [];
			foreach ($referral_list as $referrerPlayerId => $invited_list){
				foreach ($invited_list as $list){
					$invitedUserId = $list->invitedUserId;
					$referralId = $list->referralId;

					$existPromo = $this->player_promo->getPlayerPromoByReferralId($promorulesId, $referralId);
					if($existPromo){
						$applied_list[$referrerPlayerId][] = ['referralId'=>$referralId, 'invitedUserId'=>$invitedUserId];
						$this->utils->debug_log('ignore referral, cause promo bonus already release', $referralId);
						continue;
					}

					$deposit_cnt = $this->transactions->countDepositByPlayerId($invitedUserId, $deposit_start, $deposit_end);
					$met_deposit_condition = $deposit_cnt >= $min_deposit_cnt;

					$total_bet = $this->total_player_game_day->getPlayerTotalBettingAmountByPlayer($invitedUserId, $betting_start, $betting_end, $gamePlatformId, $gameTypeId);
					$met_bet_condition = $total_bet >= $min_bet;

					$this->utils->debug_log('validate invitedUser condition by cronjob', [
						'referrer'=>$referrerPlayerId, 'invitedUserId'=>$invitedUserId, 'referralId'=>$referralId,
						'deposit_cnt'=>$deposit_cnt, 'total bet'=>$total_bet,
						'met_deposit_condition'=>$met_deposit_condition, 'met_bet_condition'=>$met_bet_condition
					]);

					if($met_deposit_condition || $met_bet_condition){
						$qualified_list[$referrerPlayerId][] = ['referralId'=>$referralId, 'invitedUserId'=>$invitedUserId];
					}
				}
			}
			if(!empty($qualified_list)){
				return $qualified_list;
			}
		}
		return [];
	}

	public function getReferralListByCustomPromo($promo_class, $player_id = null, $settings = []){
		$list = [];
		if(empty($settings)){
			return $list;
		}

		switch($promo_class){
			case 'promo_rule_king_referral_daily_bonus':
				$list = $this->getKingReferralDailyBonusList($player_id, $settings);
				break;
			default:
				break;
		}

		return $list;
	}
	
	public function getPlayerReferralLevelList($referral_start = null, $referral_end = null, $betting_start, $betting_end, $game_platform_id = null, $game_type_id = null, $referrer_player_id = null, $request_from_api = false){
		$this->load->model(['total_player_game_hour', 'player_model']);
		$referrals = $this->getPlayerReferralList(null, null, $referral_start, $referral_end);
		if(!empty($referrals) ){
			foreach ($referrals as &$list) {
				$list = (array) $list;
				if($request_from_api){
					// in order to get all invited map, force invited_bet = 1
					$list['invited_bet'] = 1;
				}else{
					// get invited player betting amount for calculate cashback bonus
					// $list['invited_bet'] = $this->total_player_game_day->getPlayerTotalBettingAmountByPlayer($list['invitedUserId'], $betting_start, $betting_end, $game_platform_id, $game_type_id);
					list($totalBet, $totalWin, $totalLoss) = $this->total_player_game_hour->getTotalBetsWinsLossByPlayers($list['invitedUserId'], $betting_start, $betting_end, $game_platform_id);
					$list['invited_bet'] = $totalBet;
				}
			}
		}
		
		$records = $referrals;
		$invited_list = [];	// only for check invited player record
		$invited_cashback_map = [];

		if(!empty($records)){
			// 1. create invited list
			foreach ($records as $record) {
				$invited_list[$record['invitedUserId']] = $record;
			}
	
			// 2. use invited list to create invited cashback map
			foreach ($records as $record) {
				if(empty($record['invited_bet'])){
					continue;
				}
			
				$level = 1;
				$referrer = $record['playerId'];
				$invited = $record['invitedUserId'];
				$last_referral_id = $record['referralId'];
				$last_invited_player = $record['invitedUserId'];
				$last_invited_bet = $record['invited_bet'];
				
				if(!$request_from_api){
					$invitedPlayerStatus = $this->utils->getPlayerStatus($invited);
					if($invitedPlayerStatus != 0){
						$this->utils->debug_log('ignore in model, cause invited player status not active', $invitedPlayerStatus, $invited);
						continue;
					}
				}
				
				/*
					3. use invited cashback map to integrate all referrer's cashbaack info
					[	
						invited-Player => [ 
							[referrer-A => [[cashback info], [cashback info], ...]],
							[referrer-B => [[cashback info], [cashback info], ...]],
							[referrer-C => [[cashback info], [cashback info], ...]]
						] 
					]
				*/

				$invited_cashback_map[$invited][] = [
					//cashback info
					'referral_id' => $record['referralId'],
					'referrer' => $referrer,
					'invited' => $invited,
					'level' => $level++,
					'last_invited_player' => $last_invited_player,
					'last_invited_player_bet' => $last_invited_bet,
					'last_referral_id' => $last_referral_id,
				];
			
				// 把[推薦人]當作[被推薦人]
				$temp_referrer_as_invited = $referrer;
				// 如果[被推薦人]還有[上级推薦人]，繼續往上找，一直找到没有上级推薦人為止
				while(!empty($invited_list[$temp_referrer_as_invited])){
					$referrer_invited_record = $invited_list[$temp_referrer_as_invited];
					$last_referrer = $referrer_invited_record['playerId'];
					$last_invited = $referrer_invited_record['invitedUserId'];
					$invited_cashback_map[$invited][] = [
						//cashback info
						'referral_id' => $referrer_invited_record['referralId'],
						'referrer' => $last_referrer,
						'invited' => $last_invited,
						'level' => $level++,
						'last_invited_player' => $last_invited_player,
						'last_invited_player_bet' => $last_invited_bet,
						'last_referral_id' => $last_referral_id,
					];
					$temp_referrer_as_invited = $last_referrer;
					// 結束後 就可以找出所有被推薦人的依序推薦的array
				}
			}
		}
		
		
		/*
			4. convert invited cashback map into referrer cashback map 
			[ 
				[referrer-A => [[cashback info], [cashback info], ...]],
				[referrer-B => [[cashback info], [cashback info], ...]],
			]
		*/
		$apply_promo_arr = [];
		if(!empty($invited_cashback_map)){
			foreach ($invited_cashback_map as $invited_player => $referral_list) {	
				foreach ($referral_list as $apply_list){
					// 整理出每個推薦人的所有推薦紀錄
					$apply_promo_arr[$apply_list['referrer']][] = $apply_list;
				}
			}
		}

		if($request_from_api){
			if(!empty($apply_promo_arr[$referrer_player_id])){
				$apply_promo_arr = $apply_promo_arr[$referrer_player_id];
			}else{
				$apply_promo_arr = [];
			}
			$this->utils->debug_log('request from api, player invitation map', $apply_promo_arr);

			return $apply_promo_arr;
		}

		return $apply_promo_arr;
	}

	public function getFriendReferralLevelIdByDate($release_date){
		$this->db->from('player_friend_referral_level');
		$this->db->where('release_date', $release_date);
		$row = $this->runMultipleRowArray();

		$unique_rows = [];
		if(!empty($row)){
			foreach ($row as $record) {
				$uniqueId = sprintf("%s_%s_%s", $record['release_date'], $record['referral_id'], $record['last_referral_id']);
				$unique_rows[$uniqueId] = $record;
			}
		}
		return $unique_rows;
	}

	public function updateFriendReferralLevel($cashback_info, $playerPromoId, $release_date){
		$this->db->set('interval_level', $cashback_info['referral_id'])
				 ->set('last_invited_total_bet', $cashback_info['last_invited_player_bet'])
				 ->set('player_promo_id', $playerPromoId)
				 ->where('release_date', $release_date)
				 ->where('referral_id', $cashback_info['referral_id'])
				 ->where('last_referral_id', $cashback_info['last_referral_id']);

		$rlt = $this->runAnyUpdate('player_friend_referral_level');
		// $this->utils->debug_log('update friend referral level result', $success);
		return $rlt;
	}

	public function createFriendReferralLevel($cashback_info, $playerPromoId, $release_date){
		$success = false;
		if(!empty($cashback_info)){
			$data = [
				'referral_id' => $cashback_info['referral_id'],
				'interval_level' => $cashback_info['level'],
				'last_invited_player' => $cashback_info['last_invited_player'],
				'last_invited_total_bet' => $cashback_info['last_invited_player_bet'],
				'last_referral_id' => $cashback_info['last_referral_id'],
				'player_promo_id' => $playerPromoId,
				'release_date' => $release_date
			];
			$success = $this->insertData('player_friend_referral_level', $data);
		}
		$this->utils->debug_log('create friend referral level result', $success);
		return $success;
	}

    public function getMismatchInvitedPlayer($limit = 500, $offset = 0){
        $this->db->from('playerfriendreferral');
        $this->db->join('player', 'playerfriendreferral.invitedPlayerId = player.playerId', 'left');

        $this->db->select('playerfriendreferral.playerId as parentPlayerId');
        $this->db->select('playerfriendreferral.invitedPlayerId as childPlayerId');
        $this->db->select('player.username');
        $this->db->select('player.playerId AS targetPlayerId');
        $this->db->select('player.refereePlayerId');
        //having
        $this->db->having('refereePlayerId IS NULL');
        $this->db->limit($limit, $offset);
        return $this->runMultipleRowArray();
    }

	/**
	 * getPlayerReferralPagination
	 *
	 * @param int $playerId
	 * @param int $status
	 * @param string $from
	 * @param string $to
	 * @param int $limit
	 * @param int $page
	 * @return array
	 */
    public function getPlayerReferralPagination($playerId, $status=null, $from=null, $to=null, $limit = null, $page = null)
    {
        $this->CI->load->model(array('transactions'));
        /** @var Transactions $transactions */
        $transactions = $this->CI->{"transactions"};

        $result = $this->getDataWithAPIPagination(self::TABLE_NAME.' as pf', function() use($playerId, $status, $from, $to) {
            $this->db->select('pf.playerId, pf.invitedPlayerId AS invitedUserId, p.username AS invitedUsername, pf.referredOn as invitedOn, pf.transactionId, pf.status');
			$this->db->join('player as p', 'p.playerId = pf.invitedPlayerId', 'left');
            $this->db->where('pf.playerId', $playerId);

            if ($status) {
                $this->db->where('pf.status', $status);
            }

            if (!empty($from) && !empty($to)) {
                $this->db->where('pf.referredOn BETWEEN ' . $this->db->escape($from) . ' AND ' . $this->db->escape($to) . '');
            }

            $this->db->order_by('pf.referredOn', 'desc');
        }, $limit, $page);

        foreach($result['list'] as &$entry) {
            $entry['releasedBonus'] = 0;
            if($entry['status'] == static::STATUS_PAID) {
                $transaction = $transactions->getTransactionInfoById($entry['transactionId']);
                $entry['releasedBonus'] = (!empty($transaction)) ? $transaction['amount'] : $entry['releasedBonus'];
            }
        }
        return $result;
    }
}

///END OF FILE
