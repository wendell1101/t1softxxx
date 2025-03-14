<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) .'/abstract_promo_rule.php';

/**
 * Link / Implement Bonus Game API to Promo Manager - for HABA
 * OGP-18973
 *
 * Rules:
 * 	1. pass the settings,"insvr.CreateAndApplyBonusMulti" for send free spins API.
 *
 * Bonus config:
 * {
 *     "class": "Promo_rule_sexycasino_free_spins_coupon",
 *         "insvr.CreateAndApplyBonusMulti": {
 *             "DtStartUTC": "20200724120000",
 *             "DtEndUTC": "20201231120000",
 *             "ExpireAfterDays": 4,
 *             "MaxRedemptionsPerPlayer": 14,
 *             "MaxRedemptionsForBrand": 1004,
 *             "MaxRedemptionIntervalId": 0,
 *             "WagerMultiplierRequirement": 0,
 *             "MaxConversionToRealMultiplier": 0,
 *             "NumberOfFreeSpins": 14,
 *             "_GameKeyNames": [
 *                 "14322",
 *                 "14196"
 *             ],
 *             "couponCurrencyData": [
 *                 {
 *                     "CurrencyCode": "THB",
 *                     "CoinPosition": 0
 *             }
 *         ]
 *     },
 *     "bonus_amount": 0,
 *     "errorMessageLang": null
 * }
 */
class promo_rule_sexycasino_free_spins_coupon extends Abstract_promo_rule{

	public function init($playerId, $promorule, $playerBonusAmount = null, $depositAmount=null){
		parent::init($playerId, $promorule, $playerBonusAmount, $depositAmount);
	}

	public function getClassName(){
		return 'promo_rule_sexycasino_free_spins_coupon';
	}





	/**
	 * run bonus condition checker
	 * @param  array $description original description in rule
	 * @param  array $extra_info
	 * @param  boolean $dry_run
	 * @return  array ['success'=> success, 'message'=> errorMessageLang, 'continue_process_after_script' => FALSE]
	 */
	protected function runBonusConditionChecker($description, &$extra_info, $dry_run){

		$result = [
			'success'	=> false ,
			'message'	=> null ,
			'continue_process_after_script' => false
		];

		try {
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

		$result = [
			'success'		=> false ,
			'message'		=> null ,
			'bonus_amount'	=> 0
		];

		try {
            $total_bonus = 0;
            if( ! empty($description['bonus_amount']) ){
                $total_bonus = $description['bonus_amount'];
            }

			$result = [
				'success'	=> true ,
				'message'	=> null ,
				'bonus_amount' => $total_bonus
			];
		}
		catch (Exception $ex) {
			$this->appendToDebugLog('releaseBonus exception', [ 'code' => $ex->getCode(), 'mesg' => $ex->getMessage() ]);
			$result = [
				'success'	=> false ,
				'message'	=> $ex->getMessage() ,
				'bonus_amount' => 0
			];
		}
		finally {
			return $result;
		}
	}
}
