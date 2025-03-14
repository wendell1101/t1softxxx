<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * Ole777 VIP Birthday Bonus
 * OGP-18415
 *
 */
class Promo_rule_ole777_birthday_bonus extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'Promo_rule_ole777_birthday_bonus';
	}

	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){

		$result = [
			'success'	=> false ,
			'message'	=> null ,
			'continue_process_after_script' => false
		];

		try {

			$this->appendToDebugLog('vip_dataset', [
				'group' => $this->vipGroupName,
				'level' => $this->levelName,
				'groupid' => $this->vipGroupId,
				'levelid' => $this->levelId ,
				'level_value' => $this->vipLevel
			]);

			// Check if current month == player's birth month
			$player_birthdate = $this->callHelper('getPlayerBirthdate', []);
			if (empty($player_birthdate)) {
				throw new Exception('promo_custom.birthdate_not_set_yet', 11);
			}

			$current_month		= date('m');
			$player_birth_month	= date('m', strtotime($player_birthdate));

			$this->appendToDebugLog('bday month check', [ 'player_bday' => $player_birthdate, 'player_bmonth' => $player_birth_month, 'current_month' => $current_month ]);

			if ($player_birth_month != $current_month) {
				throw new Exception('promo_custom.not_in_the_month_of_birthdate', 12);
			}

			// Check VIP level
			// $allowed_group = $description['allowed_vip_group'];
			$bonus_settings = $description['bonus_settings'];

			$this->appendToDebugLog('mapped_bonus', [
				'setting_row'	=> $bonus_settings[$this->vipLevel] ,
				'bonus_amount'	=> $bonus_settings[$this->vipLevel]['bonus_amount']
			]);

			if (!isset($bonus_settings[$this->vipLevel]) || $bonus_settings[$this->vipLevel]['bonus_amount'] <= 0) {
				throw new Exception('promo_custom.not_in_allowed_vip_level', 13);
			}

			// Check deposit amount > 1000 for 3 consecutive months just before application
			$months = [];
			$qualify_conseq_deposit = true;
			for ($i = -3; $i <= -1; ++$i) {
				$month_dt		= strtotime("{$i} month");
				$month_start	= date('Y-m-01 00:00:00', $month_dt);
				$month_end		= date('Y-m-t 23:59:59', $month_dt);
				$month_deposit	= $this->callHelper('sum_deposit_amount_by_sale_order', [ $month_start, $month_end, 0 ]);
				if ($month_deposit < 1000.0) {
					$qualify_conseq_deposit = false;
				}
				$months[] = [
					'month_id'	=> $i ,
					'start'		=> $month_start ,
					'end'		=> $month_end ,
					'deposit'	=> $month_deposit
				];
			}

			$this->appendToDebugLog('deposit amount for 3 consecutive months', $months);
			$this->appendToDebugLog('Check result of deposit amount > 1000 for 3 conseutive months', $qualify_conseq_deposit);

			if (!$qualify_conseq_deposit) {
				throw new Exception('promo_custom.failed_deposit_gt_1000_for_conseq_3_months_before_application', 14);
			}

			// Point of success
			$result = [
				'success'	=> true ,
				'message'	=> null ,
				'continue_process_after_script' => false
			];
		}
		catch (Exception $ex) {
			$this->appendToDebugLog('runBonusConditionChecker exception', [ 'code' => $ex->getCode(), 'mesg' => $ex->getMessage() ]);
			$result = [
				'success'	=> false ,
				'message'	=> $ex->getMessage() ,
				'continue_process_after_script' => false
			];
		}
		finally {
			return $result;
		}

	} // End function runBonusConditionChecker()

	/**
	 * generate withdrawal condition
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
	 */
	protected function generateWithdrawalCondition($description, &$extra_info, $dry_run){
		return $this->returnUnimplemented();
	} // End function generateWithdrawalCondition()

    /**
     * (NO TRANSFER CONDITION FOR THIS ONE)
     * generate transfer condition
     * @param  array $description original description in rule
     * @param  array $extra_info exchange data
     * @param  boolean $dry_run
     * @return  array ['success'=> success, 'message_lang'=> errorMessageLang, 'withdrawal_condition_amount'=> withdrawal condition amount]
     */
    protected function generateTransferCondition($description, &$extra_info, $dry_run){
		return $this->returnUnimplemented();
    } // EOF generateTransferCondition

	/**
	 * release bonus
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'bonus_amount'=> bonus amount]
	 */
	protected function releaseBonus($description, &$extra_info, $dry_run){
		try {
			$player_birthdate = $this->callHelper('getPlayerBirthdate', []);
			if (empty($player_birthdate)) {
				throw new Exception('promo_custom.birthdate_not_set_yet', 21);
			}

			$current_month		= date('m');
			$player_birth_month	= date('m', strtotime($player_birthdate));

			$this->appendToDebugLog('bday month check', [ 'player_bday' => $player_birthdate, 'player_bmonth' => $player_birth_month, 'current_month' => $current_month ]);

			if ($player_birth_month != $current_month) {
				throw new Exception('promo_custom.not_in_the_month_of_birthdate', 22);
			}

			// Check VIP level
			// $allowed_group = $description['allowed_vip_group'];
			$bonus_settings = $description['bonus_settings'];

			$this->appendToDebugLog('mapped_bonus', [
				'setting_row'	=> $bonus_settings[$this->vipLevel] ,
				'bonus_amount'	=> $bonus_settings[$this->vipLevel]['bonus_amount']
			]);

			if (!isset($bonus_settings[$this->vipLevel]) || $bonus_settings[$this->vipLevel]['bonus_amount'] <= 0) {
				throw new Exception('promo_custom.not_in_allowed_vip_level', 23);
			}

			// Check deposit amount > 1000 for 3 consecutive months just before application
			$months = [];
			$qualify_conseq_deposit = true;
			for ($i = -3; $i <= -1; ++$i) {
				$month_dt		= strtotime("{$i} month");
				$month_start	= date('Y-m-01 00:00:00', $month_dt);
				$month_end		= date('Y-m-t 23:59:59', $month_dt);
				$month_deposit	= $this->callHelper('sum_deposit_amount_by_sale_order', [ $month_start, $month_end, 0 ]);
				if ($month_deposit < 1000.0) {
					$qualify_conseq_deposit = false;
				}
				$months[] = [
					'month_id'	=> $i ,
					'start'		=> $month_start ,
					'end'		=> $month_end ,
					'deposit'	=> $month_deposit
				];
			}

			$this->appendToDebugLog('deposit amount for 3 consecutive months', $months);
			$this->appendToDebugLog('Check result of deposit amount > 1000 for 3 conseutive months', $qualify_conseq_deposit);

			if (!$qualify_conseq_deposit) {
				throw new Exception('promo_custom.failed_deposit_gt_1000_for_conseq_3_months_before_application', 24);
			}

			// Calc bonus amount
			$bonus_amount = $bonus_settings[$this->vipLevel]['bonus_amount'];

			$result = [
				'success'		=> true ,
				'message'		=> null ,
				'bonus_amount'	=> $bonus_amount
			];
		}
		catch (Exception $ex) {
			$this->appendToDebugLog('releaseBonus exception', [ 'code' => $ex->getCode(), 'mesg' => $ex->getMessage() ]);
			$result = [
				'success'		=> false ,
				'message'		=> $ex->getMessage() ,
				'bonus_amount'	=> 0
			];
		}
		finally {
			return $result;
		}


	} // End function releaseBonus()
}
