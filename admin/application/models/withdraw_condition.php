<?php
require_once dirname(__FILE__) . '/base_model.php';

/**
 * Class Withdraw_condition
 *
 * General behaviors include :
 *
 * * Cancelling withdrawal conditions
 * * Disable/create withdrawal condition for player, deposit, promo
 * * Get available withdraw condition
 * * Check and clean withdraw condition
 * * Player cash back wallet balance
 * * Get withdraw transaction details
 * * Auto check withdrawal condition
 * * Clear withdraw condition settings
 *
 * @category Marketing Management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

require_once dirname(__FILE__) . '/modules/customized_definition_module.php';

class Withdraw_condition extends BaseModel {

	use customized_definition_module;

	protected $tableName = 'withdraw_conditions';

	protected $idField = 'id';

	const SOURCE_DEPOSIT = 1;
	// const SOURCE_DEPOSIT_WITH_PROMO = 2;
	// const SOURCE_PROMOTION = 3;
	const SOURCE_MANUAL = 4;
	const SOURCE_FRIEND_REFERRAL = 5;
	const SOURCE_RANDOM_BONUS = 6;
	const SOURCE_NON_DEPOSIT = 7;
	const SOURCE_CASHBACK = 8;
	const SOURCE_BONUS = 9;

	const DEPOSIT_CONDITION_TYPE_FIXED = 0;
	const DEPOSIT_CONDITION_TYPE_NONFIXED = 1;

	const BONUS_RELEASE_FIXED_BONUSAMOUNT = 0;
	const BONUS_RELEASE_BY_PERCENTAGE = 1;

	const WITHDRAW_REQUIREMENT_RULE_BYBETTING = 0;
	const WITHDRAW_REQUIREMENT_RULE_NONBETTING = 1;

	// detail status flag
	const DETAIL_STATUS_ACTIVE = 1;
	const DETAIL_STATUS_FINISHED_BETTING_AMOUNT_WHEN_DEPOSIT = 2;
	const DETAIL_STATUS_CANCELLED_MANUALLY = 3;
	const DETAIL_STATUS_CANCELLED_DUE_TO_SMALL_BALANCE = 4;  // if player balance is less than condition amount
	const DETAIL_STATUS_FINISHED_BETTING_AMOUNT_WHEN_WITHDRAW = 5;
	const DETAIL_STATUS_FINISHED_BY_DELETING_PROMO_MANAGER = 6;
	const DETAIL_STATUS_CANCELED_BY_DELETING_PROMO_MANAGER = 7;

	const WITHDRAW_CONDITION_TYPE_BETTING = 1; //default value
	const WITHDRAW_CONDITION_TYPE_DEPOSIT = 2;

	const UN_FINISHED_WITHDRAW_CONDITIONS_FLAG = 0; //default value
    const FINISHED_WITHDRAW_CONDITIONS_FLAG = 1;

    const UN_FINISHED_WITHDRAW_CONDITIONS_TYPE_DEPOSIT_FLAG = 0; //default value
    const FINISHED_WITHDRAW_CONDITIONS_TYPE_DEPOSIT_FLAG = 1;

    const NOT_DEDUCTED_FROM_CALC_CASHBACK = 0; //default value
    const IS_DEDUCTED_FROM_CALC_CASHBACK = 1;
    const ONLY_DEDUCT_BET_BEFORE_CANCELLED_WC_FROM_CALC_CASHBACK = 2; //only deduct available bet before cancelled wc when calculate cb amount
    const TEMP_DEDUCT_FROM_CALC_CASHBACK = 3;
	const SETTLED_DEDUCT_FROM_CALC_CASHBACK = 4; // Settled, like as IS_DEDUCTED_FROM_CALC_CASHBACK and paid Cashback offline.
    const IS_ACCUMULATING_DEDUCTION_OF_WC_FROM_CALCULATE_CASHBACK = 5;

    const AUTO_CHECK_WITHDRAW_CONDITION_AND_MOVE_BIG_WALLET_FROM_ACCESS_USERINFORMATION = 1;
    const AUTO_CHECK_WITHDRAW_CONDITION_AND_MOVE_BIG_WALLET_FROM_SCHEDULER = 2;

	// afbw = auto_finished_by_withdraw
	const REASON_AFBW = 'auto finished by withdraw';
	/**
	 * overview : Withdraw_condition constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * overview : cancel withdrawal condition
	 *
	 * @param int	$playerpromoId
	 * @param bool|false $updated
	 * @return bool
	 */
	public function cancelWithdrawalConditionByPlayerPromoId($playerpromoId, &$updated = false) {
		if (!empty($playerpromoId)) {
			$this->db->from('withdraw_conditions')->where('player_promo_id', $playerpromoId)->limit(1);

			$row = $this->runOneRowArray();
			if (!empty($row)) {

				$this->utils->debug_log('cancel withdraw condition', $playerpromoId, 'withdraw condition id', $row['id']);

				$data['status'] = parent::STATUS_DISABLED;
				$data['updated_at'] = $this->utils->getNowForMysql();
				$data['stopped_at'] = $this->utils->getNowForMysql();
				$data['admin_user_id'] = $this->authentication->getUserId();

				$this->db->where_in('id', [$row['id']]);
				$this->db->set($data);
				// $update = array('status' => self::STATUS_DISABLED);
				// $this->db->update($this->tableName, $update);
				$updated = !!$this->runAnyUpdateWithResult('withdraw_conditions');
			}
		}
		return true;
	}

	/**
	 * overview : cancel withdrawal condition by trans id
	 *
	 * @param int	$transId
	 * @param int	$playerId
	 * @param bool|false $updated
	 * @return bool
	 */
	public function cancelWithdrawalConditionByTransId($transId, $playerId, &$updated = false) {
		if (!empty($transId) && !empty($playerId)) {
			$this->db->from('withdraw_conditions')->where('source_id', $transId)
				->where('player_id', $playerId)->limit(1);

			$row = $this->runOneRowArray();
			if (!empty($row)) {

				$this->utils->debug_log('cancel withdraw condition', $playerId, $transId, 'withdraw condition id', $row['id']);

				$data['status'] = parent::STATUS_DISABLED;
				$data['updated_at'] = $this->utils->getNowForMysql();
				$data['stopped_at'] = $this->utils->getNowForMysql();
				$data['admin_user_id'] = $this->authentication->getUserId();

				$this->db->where_in('id', [$row['id']]);
				$this->db->set($data);
				// $update = array('status' => self::STATUS_DISABLED);
				// $this->db->update($this->tableName, $update);
				$updated = !!$this->runAnyUpdateWithResult('withdraw_conditions');
			}
		}
		return true;
	}

	/**
	 * Check the Accumulatived Bet Amount and Withdraw Condition Amount.
	 * The accumulatived bet amount snice the beginning of the Withdraw Condition created time.
	 *
	 * @param integer $withdrawConditionId The field, withdraw_conditions.id
	 * @return array The returnArray formats,
	 * - $returnArray['bool'] null|boolean If true, the bet amount Greater Than Withdraw Condition Amount.
	 *	If null, it is means not found the AVAILABLE withdrawal condition.
	 * - $returnArray['betAmount'] float The accumulatived bet amount
	 * - $returnArray['conditionAmount'] float The withdraw condition amount.
	 */
	function isAccumulativeBetAmountGreaterThanWithdrawConditionAmount($withdrawConditionId){
		$this->load->model(['game_logs']);

		$returnArray = [];
		$returnArray['bool'] = null;

		$this->db->_reset_select();
		$this->db->from($this->tableName);
		$this->db->where('status', self::STATUS_NORMAL);
        $this->db->where('id', $withdrawConditionId);
		$row = $this->runOneRowArray();
		if( ! empty($row) ){
			$condition_amount = $row['condition_amount'];
			$firstDateTime = $row['started_at'];
			$wallet_type = $row['wallet_type'];
			$playerId = $row['player_id'];
			$promoruleId = $row['promotion_id'];

			if( ! empty($wallet_type) ){
				// limit in a Game Platform
				$gamePlatformId = $wallet_type;
				$betAmount = $this->game_logs->getPlayerCurrentBetByGamePlatformId($playerId, $gamePlatformId, $firstDateTime);
			}else{
				// for main wallet
				$betAmount = $this->game_logs->getPlayerCurrentBet($playerId, $firstDateTime, null, $promoruleId);
			}

			if( empty($betAmount) ){
				// for int type
				$betAmount = 0;
			}
			$returnArray['bool'] = $betAmount >= $condition_amount;
			$returnArray['betAmount'] = $betAmount;
			$returnArray['conditionAmount'] = $condition_amount;
			/// for trace
			$returnArray['started_at'] = $firstDateTime;
			$returnArray['withdrawConditionId'] = $withdrawConditionId;
			$returnArray['wallet_type'] = $wallet_type;
		}else{
			$returnArray['msg'] = 'No found available withdrawal conditions.';
		}
		return $returnArray;
	} // EOF isAccumulativeBetAmountGreaterThanWithdrawConditionAmount

	/**
	 * overview : disable player withdrawal condition
	 *
	 * @param int	$playerId
	 * @return bool
	 */
	function disablePlayerWithdrawalCondition($playerId, $reason=null, $detailStatus=self::DETAIL_STATUS_ACTIVE, $withdrawConditionIds=null) {
	    if(empty($withdrawConditionIds)){
	        //empty withdraw condition
	        return TRUE;
        }

        $this->load->library(['authentication']);
        $admin_user_id = $this->authentication->getUserId();
        $admin_user_id = !empty($admin_user_id) ? $admin_user_id : Users::SUPER_ADMIN_ID;

		$data['status'] = parent::STATUS_DISABLED;
		$data['updated_at'] = $this->utils->getNowForMysql();
		$data['stopped_at'] = $this->utils->getNowForMysql();
		$data['admin_user_id'] = $admin_user_id;
		$data['detail_status'] = $detailStatus;
        $data['note'] = $reason;

		$this->db->where('player_id', $playerId);
		$this->db->where('status', self::STATUS_NORMAL);   # only clear active, to avoid update all
        $this->db->where_in('id', $withdrawConditionIds);
		$success =  $this->runUpdate($data);

		if ($success) {
			$this->load->model(['player_promo']);

			$this->db->select('player_id, player_promo_id')->from('withdraw_conditions')
                    ->where_in('id', $withdrawConditionIds)
					->where('player_promo_id is not null', null, false);

			$rows = $this->runMultipleRowArray();

			if (!empty($rows)) {
				$playerPromoArr = [];
				foreach ($rows as $row) {
					$playerPromoArr[] = $row['player_promo_id'];
				}
				if (!empty($playerPromoArr)) {
					$this->player_promo->finishPlayerPromos($playerPromoArr, $reason);
				}
			}
		}

		return $success;
	}

	/**
	 * overview : create withdrawal condition
	 *
	 * @param $isDeposit
	 * @param $player_id
	 * @param $bonusTransId
	 * @param $withdrawBetAmtCondition
	 * @param $deposit_amount
	 * @param $bonus_amount
	 * @param $bet_times
	 * @param $promorule
	 * @param $depositTransId
	 * @param null $playerpromoId
	 * @param null $startDate
	 * @param null $note
	 * @return mixed
	 */
	function createWithdrawConditionForPromoruleBonus($isDeposit // #1
		, $player_id // #2
		, $bonusTransId // #3
		, $withdrawBetAmtCondition // #4
		, $deposit_amount // #5
		, $bonus_amount // #6
		, $bet_times // #7
		, $promorule // #8
		, $depositTransId // #9
		, $playerpromoId = null // #10
		, $startDate = null // #11
		, $note = null // #12
		, &$extra_info = null // #13
	) {

		$this->load->model(['promorules']);

		if (empty($startDate)) {
			$startDate = $this->utils->getNowForMysql();
		}

		$promorulesId = $promorule['promorulesId'];
		if (empty($withdrawBetAmtCondition)) {
			$withdrawBetAmtCondition = 0;
		}

		if (empty($deposit_amount)) {
			$deposit_amount = 0;
		}

		if (empty($bonus_amount)) {
			$bonus_amount = 0;
		}

		$subWalletId = null;
		if (isset($extra_info['subWalletId'])) {
			$subWalletId = $extra_info['subWalletId'];
		} elseif (isset($promorule['releaseToSubWallet'])) {
			$subWalletId = $promorule['releaseToSubWallet'];
		}

		$cond = $this->getWithdrawConditionByPlayerPromoId($playerpromoId);
		if (empty($cond)) {

			$data = array(
				'source_id' => $bonusTransId,
				'source_type' => $isDeposit ? self::SOURCE_DEPOSIT : self::SOURCE_BONUS,//self::SOURCE_NON_DEPOSIT,
				'started_at' => $startDate,
				'condition_amount' => $withdrawBetAmtCondition,
				'status' => self::STATUS_NORMAL,
				'player_id' => $player_id,
				'deposit_amount' => $deposit_amount,
				'bonus_amount' => $bonus_amount,
				'bet_times' => $bet_times,
				'promotion_id' => $promorulesId,
				'promorules_json' => @$promorule['json_info'],
				'wallet_type' => $subWalletId,
				'player_promo_id' => $playerpromoId,
				'note' => $note,
				'trigger_amount' => $bonus_amount,
                'withdraw_condition_type' => self::WITHDRAW_CONDITION_TYPE_BETTING,
                'is_deducted_from_calc_cashback' => self::NOT_DEDUCTED_FROM_CALC_CASHBACK,
			);
			$rlt = $this->insertRow($data);
		} else {
			$data = array(
				'source_id' => $bonusTransId,
				'source_type' => $isDeposit ? self::SOURCE_DEPOSIT : self::SOURCE_BONUS,//self::SOURCE_NON_DEPOSIT,
				'started_at' => $startDate,
				'condition_amount' => $withdrawBetAmtCondition,
				'status' => self::STATUS_NORMAL,
				'player_id' => $player_id,
				'deposit_amount' => $deposit_amount,
				'bonus_amount' => $bonus_amount,
				'bet_times' => $bet_times,
				'promotion_id' => $promorulesId,
				'promorules_json' => @$promorule['json_info'],
				'wallet_type' => $subWalletId,
				'player_promo_id' => $playerpromoId,
				'note' => $note,
				'trigger_amount' => $bonus_amount,
                'withdraw_condition_type' => self::WITHDRAW_CONDITION_TYPE_BETTING,
                'is_deducted_from_calc_cashback' => self::NOT_DEDUCTED_FROM_CALC_CASHBACK,
			);

			$this->db->where('id', $cond['id'])->update('withdraw_conditions', $data);
			$rlt = $cond['id'];
		}

		return $rlt;
	}

    /**
     * overview : create deposit condition
     *
     * @param $isDeposit
     * @param $player_id
     * @param $bonusTransId
     * @param $withdrawBetAmtCondition
     * @param $deposit_amount
     * @param $bonus_amount
     * @param $bet_times
     * @param $promorule
     * @param $depositTransId
     * @param null $playerpromoId
     * @param null $startDate
     * @param null $note
     * @return mixed
     */
	function createDepositConditionForPromoruleBonus($isDeposit // #1
				, $player_id // #2
				, $bonusTransId // #3
				, $withdrawDepAmtCondition // #4
				, $deposit_amount // #5
				, $bonus_amount // #6
				, $bet_times // #7
				, $promorule // #8
				, $depositTransId // #9
				, $playerpromoId = null // #10
				, $startDate = null // #11
				, $note = null // #12
				, &$extra_info = null // #13
	) {

        $this->load->model(['promorules']);

        if (empty($startDate)) {
            $startDate = $this->utils->getNowForMysql();
        }

        $promorulesId = $promorule['promorulesId'];
        if (empty($withdrawDepAmtCondition)) {
            $withdrawDepAmtCondition = 0;
        }

        if (empty($deposit_amount)) {
            $deposit_amount = 0;
        }

        if (empty($bonus_amount)) {
            $bonus_amount = 0;
        }

        $subWalletId = null;
        if (isset($extra_info['subWalletId'])) {
            $subWalletId = $extra_info['subWalletId'];
        } elseif (isset($promorule['releaseToSubWallet'])) {
            $subWalletId = $promorule['releaseToSubWallet'];
        }

        $cond = $this->getDepositConditionByPlayerPromoId($playerpromoId);
        if (empty($cond)) {

            $data = array(
                'source_id' => $bonusTransId,
                'source_type' => $isDeposit ? self::SOURCE_DEPOSIT : self::SOURCE_BONUS,//self::SOURCE_NON_DEPOSIT,
                'started_at' => $startDate,
                'condition_amount' => $withdrawDepAmtCondition,
                'status' => self::STATUS_NORMAL,
                'player_id' => $player_id,
                'deposit_amount' => $deposit_amount,
                'bonus_amount' => $bonus_amount,
                'bet_times' => $bet_times,
                'promotion_id' => $promorulesId,
                'promorules_json' => @$promorule['json_info'],
                'wallet_type' => $subWalletId,
                'player_promo_id' => $playerpromoId,
                'note' => $note,
                'trigger_amount' => $bonus_amount,
                'withdraw_condition_type' => self::WITHDRAW_CONDITION_TYPE_DEPOSIT,
                'is_deducted_from_calc_cashback' => self::NOT_DEDUCTED_FROM_CALC_CASHBACK,
            );
            $rlt = $this->insertRow($data);
        } else {
            $data = array(
                'source_id' => $bonusTransId,
                'source_type' => $isDeposit ? self::SOURCE_DEPOSIT : self::SOURCE_BONUS,//self::SOURCE_NON_DEPOSIT,
                'started_at' => $startDate,
                'condition_amount' => $withdrawDepAmtCondition,
                'status' => self::STATUS_NORMAL,
                'player_id' => $player_id,
                'deposit_amount' => $deposit_amount,
                'bonus_amount' => $bonus_amount,
                'bet_times' => $bet_times,
                'promotion_id' => $promorulesId,
                'promorules_json' => @$promorule['json_info'],
                'wallet_type' => $subWalletId,
                'player_promo_id' => $playerpromoId,
                'note' => $note,
                'trigger_amount' => $bonus_amount,
                'withdraw_condition_type' => self::WITHDRAW_CONDITION_TYPE_DEPOSIT,
                'is_deducted_from_calc_cashback' => self::NOT_DEDUCTED_FROM_CALC_CASHBACK,
            );

            $this->db->where('id', $cond['id'])->update('withdraw_conditions', $data);
            $rlt = $cond['id'];
        }

        return $rlt;
    }

	/**
	 * overview : get withdrawal condition
	 *
	 * @param  int	$playerpromoId
	 * @return array
	 */
	function getWithdrawConditionByPlayerPromoId($playerpromoId) {
		$this->db->from('withdraw_conditions')->where('player_promo_id', $playerpromoId)->limit(1);
		return $this->runOneRowArray();
	}

	function getWithdrawConditionByPromoCmsSettingId($promocmsId){
        $deleted_promocms_filter = [self::DETAIL_STATUS_FINISHED_BY_DELETING_PROMO_MANAGER,self::DETAIL_STATUS_CANCELED_BY_DELETING_PROMO_MANAGER];
	    $this->db->select('withdraw_conditions.*')
                 ->from($this->tableName)
                 ->join('playerpromo','withdraw_conditions.player_promo_id = playerpromo.playerpromoId', 'left')
                 ->where('playerpromo.promoCmsSettingId', $promocmsId)
                 ->where('withdraw_conditions.status', parent::STATUS_NORMAL)
                 ->where('withdraw_conditions.detail_status not in (' . implode(',', $deleted_promocms_filter) . ')');

        return $this->runMultipleRowArray();
    }

    function updateWithdrawConditionByPromoCmsSettingId($withdraw_conditions){
	    $success = FALSE;
        $playerPromoArr = [];
        $reason = null;
        if(!empty($withdraw_conditions)){
            foreach($withdraw_conditions as $wc){

                $data['detail_status'] = self::DETAIL_STATUS_CANCELED_BY_DELETING_PROMO_MANAGER;   //unfinished wc
                $reason = 'Canceled withdraw condition by deleting promo manager';

                if($wc['is_finished'] == self::FINISHED_WITHDRAW_CONDITIONS_FLAG){
                    $data['detail_status'] = self::DETAIL_STATUS_FINISHED_BY_DELETING_PROMO_MANAGER;   //finished or canceled wc
                    $reason = 'Finished withdraw condition by deleting promo manager';
                }

                $data['status'] = parent::STATUS_DISABLED;
                $data['updated_at'] = $this->utils->getNowForMysql();
                $data['stopped_at'] = $this->utils->getNowForMysql();
                $data['admin_user_id'] = $this->authentication->getUserId();

                $success = $this->updateRow($wc['id'], $data);
                $this->utils->debug_log('-----------------------> updateWithdrawConditionByPromoCmsSettingId withdraw condition id ', $wc['id']);
                if($success){
                    $playerPromoArr[] = $wc['player_promo_id'];
                }
            }

            if(!empty($playerPromoArr)){
                $this->load->model(['player_promo']);
                $this->player_promo->finishPlayerPromos($playerPromoArr, $reason);
            }
        }

        return $success;
    }

    /**
     * overview : get deposit condition
     *
     * @param  int	$playerpromoId
     * @return array
     */
    function getDepositConditionByPlayerPromoId($playerpromoId) {
        $this->db->from('withdraw_conditions')
                 ->where('player_promo_id', $playerpromoId)
                 ->where('withdraw_condition_type', self::WITHDRAW_CONDITION_TYPE_DEPOSIT)
                 ->limit(1);
        return $this->runOneRowArray();
    }

	/**
	 * overview : create withdrawal condition for deposit only
	 * @param $saleOrder
	 * @param $depositTransId
	 * @param $withdrawBetAmtCondition
	 * @param $nonPromoWithdrawSettingVal
	 * @return array
	 */
	function createWithdrawConditionForDepositOnly($saleOrder // #1
					, $depositTransId // #2
					, $withdrawBetAmtCondition // #3
					, $nonPromoWithdrawSettingVal // #4
	) {
		$data = array(
			'source_id' => $depositTransId,
			'source_type' => self::SOURCE_DEPOSIT,
			'started_at' => $this->utils->getNowForMysql(),
			'condition_amount' => $withdrawBetAmtCondition,
			'status' => self::STATUS_NORMAL,
			'player_id' => $saleOrder->player_id,
			'deposit_amount' => $saleOrder->amount,
			'bet_times' => $nonPromoWithdrawSettingVal,
            'is_deducted_from_calc_cashback' => self::NOT_DEDUCTED_FROM_CALC_CASHBACK,
		);

		return $this->insertRow($data);
	}

	/**
	 * overview : create withdrawal condition for cash back
	 *
	 * @param int		$transId
	 * @param double	$withdrawBetAmtCondition
	 * @param int		$withdraw_condition_bet_times
	 * @param int $player_id
	 * @param double $deposit_amount
	 * @return array
	 */
	function createWithdrawConditionForCashback($transId, $withdrawBetAmtCondition,
		$withdraw_condition_bet_times, $player_id, $trigger_amount, $reason='') {

		$data = array(
			'source_id' => $transId,
			'source_type' => self::SOURCE_CASHBACK,//self::SOURCE_CASHBACK,
			'started_at' => $this->utils->getNowForMysql(),
			'condition_amount' => $withdrawBetAmtCondition,
			'status' => self::STATUS_NORMAL,
			'player_id' => $player_id,
			// 'deposit_amount' => $deposit_amount,
			'bet_times' => $withdraw_condition_bet_times,
			'trigger_amount' => $trigger_amount,
			'note' => $reason,
            'is_deducted_from_calc_cashback' => self::NOT_DEDUCTED_FROM_CALC_CASHBACK,
		);

		return $this->insertRow($data);
	}

	function createWithdrawCondForDepositOnly($transId, $withdrawBetAmtCondition,
		$withdraw_condition_bet_times, $player_id, $deposit_amount, $reason='') {

		$data = array(
			'source_id' => $transId,
			'source_type' => self::SOURCE_DEPOSIT,//self::SOURCE_CASHBACK,
			'started_at' => $this->utils->getNowForMysql(),
			'condition_amount' => $withdrawBetAmtCondition,
			'status' => self::STATUS_NORMAL,
			'player_id' => $player_id,
			'deposit_amount' => $deposit_amount,
			'bet_times' => $withdraw_condition_bet_times,
			'note' => $reason,
            'is_deducted_from_calc_cashback' => self::NOT_DEDUCTED_FROM_CALC_CASHBACK,
			// 'trigger_amount' => $trigger_amount
		);

		return $this->insertRow($data);
	}

	/**
	 * overview : create withdrawal condition for member group deposit bonus
	 * @param int		$player_id
	 * @param int		$bonusTransId
	 * @param double	$withdrawBetAmtCondition
	 * @param double	$deposit_amount
	 * @param double	$bonus_amount
	 * @param int		$bet_times
	 * @return array
	 */
	function createWithdrawConditionForMemberGroupDepositBonus($player_id, $bonusTransId, $withdrawBetAmtCondition,
		$deposit_amount, $bonus_amount, $bet_times) {
		$data = array(
			'source_id' => $bonusTransId,
			'source_type' => self::SOURCE_DEPOSIT,
			'started_at' => $this->utils->getNowForMysql(),
			'condition_amount' => $withdrawBetAmtCondition,
			'status' => self::STATUS_NORMAL,
			'player_id' => $player_id,
			'deposit_amount' => $deposit_amount,
			'bonus_amount' => $bonus_amount,
			'bet_times' => $bet_times,
			'trigger_amount' => $bonus_amount,
            'is_deducted_from_calc_cashback' => self::NOT_DEDUCTED_FROM_CALC_CASHBACK,
		);

		return $this->insertRow($data);
	}

	/**
	 * overview : create withdrawal condition for random bonus
	 *
	 * @param int		$player_id
	 * @param int		$bonusTransactionId
	 * @param double	$withdrawBetAmtCondition
	 * @param double	$bonus_amount
	 * @param int	$bet_times
	 * @return array
	 */
	function createWithdrawConditionForRandomBonus($player_id, $bonusTransactionId, $withdrawBetAmtCondition,
		$bonus_amount, $bet_times) {

		$data = array(
			'source_id' => $bonusTransactionId,
			'source_type' => self::SOURCE_BONUS,//self::SOURCE_NON_DEPOSIT,
			'started_at' => $this->utils->getNowForMysql(),
			'condition_amount' => $withdrawBetAmtCondition,
			'status' => self::STATUS_NORMAL,
			'player_id' => $player_id,
			'bonus_amount' => $bonus_amount,
			'bet_times' => $bet_times,
			'trigger_amount' => $bonus_amount,
            'is_deducted_from_calc_cashback' => self::NOT_DEDUCTED_FROM_CALC_CASHBACK,
		);

		return $this->insertRow($data);
	}

	/**
	 * overview : create withdrawal condition for roulette bonus
	 *
	 * @param int		$playerId
	 * @param int		$bonusTransactionId
	 * @param double	$withdrawBetAmtCondition
	 * @param double	$bonusAmount
	 * @param int	$betTimes
	 * @return array
	 */
	function createWithdrawConditionForRouletteBonus($playerId, $bonusTransactionId, $withdrawBetAmtCondition, $bonusAmount, $betTimes) {

		$data = array(
			'source_id' => $bonusTransactionId,
			'source_type' => self::SOURCE_BONUS,
			'started_at' => $this->utils->getNowForMysql(),
			'condition_amount' => $withdrawBetAmtCondition,
			'status' => self::STATUS_NORMAL,
			'player_id' => $playerId,
			'bonus_amount' => $bonusAmount,
			'bet_times' => $betTimes,
			'trigger_amount' => $bonusAmount,
            'is_deducted_from_calc_cashback' => self::NOT_DEDUCTED_FROM_CALC_CASHBACK,
		);

		return $this->insertRow($data);
	}

	/**
	 * overview : create withdrawal condition for quest bonus
	 *
	 * @param int	 $playerId
	 * @param int	 $bonusTransactionId
	 * @param double $withdrawBetAmtCondition
	 * @param double $bonusAmount
	 * @param int	 $betTimes
	 * @return array
	 */
	function createWithdrawConditionForQuestBonus($playerId, $bonusTransactionId, $withdrawBetAmtCondition, $bonusAmount, $betTimes) {

		$data = array(
			'source_id' => $bonusTransactionId,
			'source_type' => self::SOURCE_BONUS,
			'started_at' => $this->utils->getNowForMysql(),
			'condition_amount' => $withdrawBetAmtCondition,
			'status' => self::STATUS_NORMAL,
			'player_id' => $playerId,
			'bonus_amount' => $bonusAmount,
			'bet_times' => $betTimes,
			'trigger_amount' => $bonusAmount,
            'is_deducted_from_calc_cashback' => self::NOT_DEDUCTED_FROM_CALC_CASHBACK,
		);

		return $this->insertRow($data);
	}

	/**
	 * overview : create withdrawal condition for friend referral
	 *
	 * @param int		$playerId
	 * @param int		$bonusTransId
	 * @param date		$referralDate
	 * @param double	$conditionAmount
	 * @param double	$bonus_amount
	 * @param int		$bet_times
	 * @return array
	 */
	function createWithdrawConditionForFriendReferral($playerId, $bonusTransId, $referralDate, $conditionAmount,
		$bonus_amount, $bet_times, $promotion_id = null, $player_promo_id = null) {

		$data = array(
			'source_id' => $bonusTransId,
			'source_type' => self::SOURCE_BONUS,
			'started_at' => $referralDate,
			'condition_amount' => $conditionAmount,
			'status' => self::STATUS_NORMAL,
			'player_id' => $playerId,
			'bonus_amount' => $bonus_amount,
			'bet_times' => $bet_times,
			'promotion_id' => $promotion_id,
			'trigger_amount' => $bonus_amount,
            'player_promo_id' => $player_promo_id,
            'is_deducted_from_calc_cashback' => self::NOT_DEDUCTED_FROM_CALC_CASHBACK,
		);

		return $this->insertRow($data);
	}

	/**
	 * overview : create withdrawal condition for birthday bonus
	 *
	 * @param int		$transId
	 * @param double	$withdrawBetAmtCondition
	 * @param int		$withdraw_condition_bet_times
	 * @param int $player_id
	 * @return array
	 */
	function createWithdrawConditionForBirthdayBonus($transId, $withdrawBetAmtCondition, $bonus_amount,
		$withdraw_condition_bet_times, $player_id) {

		$data = array(
			'source_id' => $transId,
			'source_type' => self::SOURCE_BONUS,//self::SOURCE_NON_DEPOSIT,
			'started_at' => $this->utils->getNowForMysql(),
			'condition_amount' => $withdrawBetAmtCondition,
			'status' => self::STATUS_NORMAL,
			'player_id' => $player_id,
			'bonus_amount' => $bonus_amount,
			'bet_times' => $withdraw_condition_bet_times,
			'note' => lang("Birthday Bonus"),
			'trigger_amount' => $bonus_amount,
            'is_deducted_from_calc_cashback' => self::NOT_DEDUCTED_FROM_CALC_CASHBACK,
		);
		return $this->insertRow($data);
	}

	//===create withdraw condition=======================================

	/**
	 * overview : get first available withdraw condition
	 *
	 * @param $playerId
	 * @return null
	 */
	public function getFirstAvailableWithdrawCondition($playerId) {
		$this->db->select('min(started_at) as firstDateTime', false)
			->from('withdraw_conditions')->where('player_id', $playerId)
			->where('status', self::STATUS_NORMAL);

		return $this->runOneRowOneField('firstDateTime');
	}

	/**
	 * overview : get available condition amount on withdraw condition
	 *
	 * @param int	$playerId
	 * @return double
	 */
	public function getAvailableAmountOnWithdrawCondition($playerId) {
		$this->db->select('sum(condition_amount) as amount', false)
			->from('withdraw_conditions')->where('player_id', $playerId)
			->where('status', self::STATUS_NORMAL);

		return $this->runOneRowOneField('amount');
	}
	/**
	 * overview : get available source amount on withdraw condition
	 *
	 * @param int	$playerId
	 * @return double
	 */
	public function getAvailableSourceAmountOnWithdrawCondition($playerId, $walletType=null) {
	    $this->load->model(['transactions']);
	    $this->db->select('sum(transactions.amount) as sourceAmount')
                ->from($this->tableName)
                ->join("transactions", "transactions.id = withdraw_conditions.source_id", "left")
                ->where("withdraw_conditions.status", self::STATUS_NORMAL)
                ->where('withdraw_conditions.player_id', $playerId)
                ->where('transactions.to_type', Transactions::PLAYER)
                ->where('transactions.status', Transactions::APPROVED);

        if(!empty($walletType)) {
            $this->db->where('withdraw_conditions.wallet_type', $walletType);
        }

		return $this->runOneRowOneField('sourceAmount');
	}

    public function getAvailableWithdrawConditionIds($playerId, $generated_by_promotion = FALSE, $applyWithdrawDateTime = null){
        $this->db->select('id')
                ->from('withdraw_conditions')
                ->where('player_id', $playerId)
                ->where('status', self::STATUS_NORMAL);

        if($this->utils->getConfig('get_available_wc_which_release_to_main_wallet')){
            $this->db->where('(wallet_type IS NULL OR wallet_type = 0)');
        }

        if($generated_by_promotion){
            $this->db->where('promotion_id is not null', null, false);
            $this->db->where('is_finished', self::UN_FINISHED_WITHDRAW_CONDITIONS_FLAG);
        }

        if(!empty($applyWithdrawDateTime)){
            $this->db->where('started_at <', $applyWithdrawDateTime);
        }

        return $this->runMultipleRowOneFieldArray('id');
	}

    public function getAvailableWithdrawConditionIdsByWalletType($playerId, $walletType){
        $this->db->select('id')
            ->from('withdraw_conditions')
            ->where('player_id', $playerId)
            ->where('wallet_type', $walletType)
            ->where('status', self::STATUS_NORMAL);
        return $this->runMultipleRowOneFieldArray('id');
    }

	public function checkGameLogsAndPreventSmallBalance(
		$playerId,
		$balToCheck = array('bal'=> 0, 'balLimit' => 0),
		$firstDateTime,
		&$message,
		$walletType = null
		) {

		$this->load->model(array('game_logs'));
		$hasRecords = false;
		$preventClearWithdrawCondition = false;
		$enable_prevent = $this->utils->getConfig('prevent_unsettle_game_auto_clear_withdrawal_conditions');
		$date_range = $this->utils->getConfig('check_unsettle_game_date_range')?:'-7 days';

		if($enable_prevent) {
			//params
			// $status = array(GAME_LOGS::STATUS_CANCELLED, GAME_LOGS::STATUS_REFUND);
			$status = GAME_LOGS::SETTLED_STATUS_BUT_INVALID_BET;
			// $dateTimeFrom = $this->utils->get7DaysAgoForMysql();
			$dateTimeFrom = $this->utils->formatDateTimeForMysql(new \DateTime($date_range));
			$dateTimeTo = $this->utils->getNowForMysql();
			$hasRecords = $this->game_logs->getUnsettledBetsRecord($status, $dateTimeFrom, $dateTimeTo, $playerId);
			$this->utils->debug_log(' ===========checkGameLogsAndPreventSmallBalance=============', [
				'status'=>$status,
				'walletType' => $walletType,
				'dateTimeFrom'=>$dateTimeFrom,
				'dateTimeTo' =>$dateTimeTo,
				'hasRecords' =>$hasRecords
			]);

			$hasRecords = !!($hasRecords > 0);
			if(!$hasRecords) {
				$balLimit = $this->checkAndGetPassingParams('balLimit', $balToCheck) ?: 0;
				$currentBal = $this->checkAndGetPassingParams('bal', $balToCheck) ?: 0;
				$sourceAmount = $this->getAvailableSourceAmountOnWithdrawCondition($playerId, $walletType) ?: 0;
				list($totalBet, $totalWin, $totalLoss) = $this->game_logs->getTotalBetsWinsLossByPlayers($playerId, $firstDateTime, $dateTimeTo, $walletType);
				$totalBet= $totalBet?:0;
				$totalWin= $totalWin?:0;
				$totalLoss= $totalLoss?:0;

				if( empty($totalBet) || ( ($sourceAmount - ($totalBet - $totalWin)) > $balLimit ) ) {
					$message .= 'Prevent small balance due to game logs not match'. "\n";
					$preventClearWithdrawCondition = true;
					$this->utils->debug_log(' ===========checkGameLogsAndPreventSmallBalance============= betamount not met', [
						'firstDateTime'=>$firstDateTime,
						'sourceAmount'=>$sourceAmount,
						'balLimit'=>$balLimit,
						'currentBal'=>$currentBal,
						'totalBet'=>$totalBet,
						'totalWin'=>$totalWin,
						'totalLoss' =>$totalLoss,
						'hasRecords' =>$hasRecords
					]);
				}

			} else {
				$message .= 'Prevent small balance due to unsettled game logs'. "\n";
				$preventClearWithdrawCondition = true;
			}
		}
		return $preventClearWithdrawCondition;
	}

	private function checkTotalBetByFirstDateTime($playerId, $firstDateTime, &$clear, &$message, &$detailStatus, $walletType = null, $sub_label = null, $sourceFrom = ''){
        if(!empty($walletType) && !empty($sub_label)){
            // sub wallet
            if (!empty($firstDateTime)) {
                $betAmount = $this->game_logs->getPlayerCurrentBetByGamePlatformId($playerId, $walletType, $firstDateTime);
                $conditionAmount = $this->getAvailableAmountOnWithdrawConditionByWalletType($playerId, $walletType);
                $clear = $betAmount >= $conditionAmount;

                $this->utils->debug_log("{$sourceFrom}check withdraw condition", "playerId", $playerId, "walletType", $walletType,
                    "firstDateTime", $firstDateTime, "betAmount", $betAmount, "conditionAmount", $conditionAmount, "clear", $clear);
                if ($clear) {
                    $message .= 'finish withdraw condition on ' . $sub_label . ', because total bet amount (' . $betAmount . ' from ' . $firstDateTime . ') of player ' . $playerId . ' >= ' . $conditionAmount . "\n";
                    $detailStatus = self::DETAIL_STATUS_FINISHED_BETTING_AMOUNT_WHEN_DEPOSIT;
                }
            } else {
                $this->utils->debug_log("{$sourceFrom}donot find first date time of withdraw", $playerId, "wallet", $walletType);
            }
        }else{
            // main wallet
            if (!empty($firstDateTime)) {
                $betAmount = $this->game_logs->getPlayerCurrentBet($playerId, $firstDateTime);
                $conditionAmount = $this->getAvailableAmountOnWithdrawCondition($playerId);
                $clear = $betAmount >= $conditionAmount;

                $this->utils->debug_log("{$sourceFrom}check withdraw condition", "playerId", $playerId, "main wallet",
                    "firstDateTime", $firstDateTime, "betAmount", $betAmount, "conditionAmount", $conditionAmount, "clear", $clear);

                if ($clear) {
                    $message .= 'clean withdraw condition, because total bet amount (' . $betAmount . ' from ' . $firstDateTime . ') of player ' . $playerId . ' >= ' . $conditionAmount . "\n";
                    $detailStatus = self::DETAIL_STATUS_FINISHED_BETTING_AMOUNT_WHEN_DEPOSIT;
                }
            } else {
                $this->utils->debug_log("{$sourceFrom}donot find first date time of withdraw", $playerId, "main wallet");
            }
        }
    }

	private function checkAndGetPassingParams($key, $params){
		if (is_array($params) && array_key_exists($key, $params)) {
			return $params[$key];
		}
		 return false;
	}

	/**
	 * overview : check and clean withdraw condition
	 *
	 * @param int	$playerId
	 * @return bool
	 */
	public function checkAndCleanWithdrawCondition($playerId) {
		$this->load->model(array('operatorglobalsettings', 'wallet_model', 'player_promo', 'game_logs'));
		$balLimit = $this->operatorglobalsettings->getSettingValue('previous_balance_set_amount');
		$bal = $this->wallet_model->getTotalBalance($playerId);

		$this->utils->debug_log('playerId', $playerId, 'balLimit', $balLimit, 'bal', $bal);
		$clear = false;
		$description = null;
		$firstDateTime = $this->getFirstAvailableWithdrawCondition($playerId);

		if ($bal <= $balLimit) {
			//inactive all
			$preventTriggerSmallBalance = $this->checkGameLogsAndPreventSmallBalance($playerId, array('bal'=> $bal, 'balLimit' => $balLimit), $firstDateTime, $description);
			$this->utils->debug_log('checkAndCleanWithdrawCondition', ['player_id'=>$playerId, "preventTriggerSmallBalance" => $preventTriggerSmallBalance]);
			if($preventTriggerSmallBalance){
				$this->utils->debug_log('checkAndCleanWithdrawCondition skip due to Found Unsettled GameLogs');
			} else {
				$clear = true;
				$description = 'clean withdraw condition, because last balance (' . $bal . ') of player ' . $playerId . ' <= ' . $balLimit;
			}
		} else {
			//check bet amount >= withdraw condition
			//get first date
			// $firstDateTime = $this->getFirstAvailableWithdrawCondition($playerId);

			if (!empty($firstDateTime)) {

				$betAmount = $this->game_logs->getPlayerCurrentBet($playerId, $firstDateTime);
				$conditionAmount = $this->getAvailableAmountOnWithdrawCondition($playerId);
				$this->utils->debug_log('betAmount', $betAmount, 'conditionAmount', $conditionAmount);
				$clear = $betAmount >= $conditionAmount;

				$description = 'clean withdraw condition, because total bet amount (' . $betAmount . ' from ' . $firstDateTime . ') of player ' . $playerId . ' >= ' . $conditionAmount;
			}
		}

		if ($clear) {
			$rlt = $this->disablePlayerWithdrawalCondition($playerId);
			$this->utils->debug_log('disablePlayerWithdrawalCondition', $rlt, 'playerId', $playerId);
		}

		return true;
	}

	public function getAllPlayersAvailableAmountOnWithdrawConditionByDeductFlag($player_id=null, $from=null, $to=null, $is_deducted_from_calc_cashback_list_in_where = null) {
		if( empty($is_deducted_from_calc_cashback_list_in_where) ){
			$is_deducted_from_calc_cashback_list_in_where = [self::NOT_DEDUCTED_FROM_CALC_CASHBACK, self::TEMP_DEDUCT_FROM_CALC_CASHBACK];
		}

        if($this->utils->getConfig('use_accumulate_deduction_when_calculate_cashback')){
            array_push($is_deducted_from_calc_cashback_list_in_where, self::IS_ACCUMULATING_DEDUCTION_OF_WC_FROM_CALCULATE_CASHBACK);
        }

        $this->db->select("id, player_id, condition_amount as amount")
                 ->from($this->tableName)
                 ->join("promorules", "withdraw_conditions.promotion_id = promorules.promorulesId", "left")
                 ->where("promorules.disable_cashback_if_not_finish_withdraw_condition", self::TRUE)
                 ->where("withdraw_conditions.withdraw_condition_type", self::WITHDRAW_CONDITION_TYPE_BETTING)
                 ->where("withdraw_conditions.is_deducted_from_calc_cashback IS NOT NULL", null, false) //exclude old wc
                 ->where_in("withdraw_conditions.is_deducted_from_calc_cashback", $is_deducted_from_calc_cashback_list_in_where);

        $enabled_exclude_wc_available_bet_after_cancelled_wc = $this->utils->getConfig('exclude_wc_available_bet_after_cancelled_wc');
        if($enabled_exclude_wc_available_bet_after_cancelled_wc){
            $this->db->where('withdraw_conditions.is_finished', self::FINISHED_WITHDRAW_CONDITIONS_FLAG);
        }

        if(!empty($player_id)){
            $this->db->where("player_id", $player_id);
        }

        // $start_period = $this->utils->getConfig('datetime_of_exclude_withdraw_condition_when_calculate_cashback');
        // //need to format $start_period into mysql time
        // if(!empty($start_period)){
        //     $this->db->where("withdraw_conditions.started_at >= ", $start_period);
        // }

		// withdraw_conditions.started_at >= ? AND withdraw_conditions.started_at<= ?
		if( ! empty($from) ){  // =null, $to=null
            $this->db->where("withdraw_conditions.started_at >= ", $from);
        }
		if( ! empty($to) ){  // =null, $to=null
			$this->db->where("withdraw_conditions.started_at <= ", $to);

        }

        $this->db->order_by("withdraw_conditions.id", "ASC");
        $rows = $this->runMultipleRow();
$last_query = $this->db->last_query();
$this->utils->debug_log('816.getAllPlayersAvailableAmountOnWithdrawConditionByDeductFlag.last_query', $last_query);
        $wc_amount_map = array();
        if(!empty($rows)){
            foreach ($rows as $row) {
                $wc_amount_map[$row->player_id][] = [ "wc_id" => $row->id, "amount" => $row->amount, "is_deducted" => false];
            }
        }

        return $wc_amount_map;
    }

	public function getAllPlayersAvailableAmountOnWithdrawConditionByCashbackSettings($date, $startHour, $endHour, $start_date=null, $end_date=null, $player_id=null, $recalculate_cashback = false, $recalculate_deducted_process_table = null) {
        $wc_amount_map = [];
		$withdraw_condition_type = self::WITHDRAW_CONDITION_TYPE_BETTING;
		// params, $player_sql
		$sql_format = <<<EOF
		SELECT player_id, sum(condition_amount) as amount
		FROM  withdraw_conditions
		JOIN promorules on (withdraw_conditions.promotion_id=promorules.promorulesId)
		WHERE withdraw_conditions.started_at >= ? AND withdraw_conditions.started_at<= ?
		AND promorules.disable_cashback_if_not_finish_withdraw_condition=?
		AND withdraw_conditions.withdraw_condition_type = "$withdraw_condition_type"
		%s
		GROUP BY player_id
EOF;
		list($from, $to) = $this->_getFromTo4WithdrawCondition($date, $startHour, $endHour, $start_date, $end_date);
		$data = [$from, $to, '1'];

	    if($this->utils->isEnabledFeature('enabled_use_decuct_flag_to_filter_withdraw_condition_when_calc_cackback') || $this->utils->getConfig('use_accumulate_deduction_when_calculate_cashback')){
			// WUDFIWC = while_used_decuct_flag_in_withdraw_condition
			$use_calc_cackback_start_WUDFIWC = $this->utils->getConfig('use_calc_cackback_start_while_used_decuct_flag_in_withdraw_condition');
			$use_calc_cackback_end_WUDFIWC = $this->utils->getConfig('use_calc_cackback_end_while_used_decuct_flag_in_withdraw_condition');
			if( empty($use_calc_cackback_start_WUDFIWC) ){
				$from = null;
			}
			if( empty($use_calc_cackback_end_WUDFIWC) ){
				$to = null;
			}
			$start_period = $this->utils->getConfig('datetime_of_exclude_withdraw_condition_when_calculate_cashback');

			// override the $from for get the latest time.
			if( ! empty($start_period) && ! empty($from) ){
				$start_period_DT = new DateTime($start_period);
				$from_DT = new DateTime($from);
				if( $start_period_DT->getTimestamp() > $from_DT->getTimestamp() ){
					$from = $start_period;
				}
			}else if( ! empty($start_period) ){ //need to format $start_period into mysql time
				$from = $start_period;
			}

			if(!$recalculate_cashback){
                $this->utils->debug_log('868.getAllPlayersAvailableAmountOnWithdrawConditionByDeductFlag:', $from, $to);
                $wc_amount_map =  $this->getAllPlayersAvailableAmountOnWithdrawConditionByDeductFlag($player_id, $from, $to);
                $this->utils->debug_log('get all players available amount on withdraw condition by deduct flag', $wc_amount_map);
            }

            if($this->utils->getConfig('use_accumulate_deduction_when_calculate_cashback')){
                $this->load->model(['withdraw_condition_deducted_process']);
                if(!$recalculate_cashback){
                    $wc_amount_map = $this->withdraw_condition_deducted_process->getAllPlayersDeductedList($wc_amount_map, $date);
                }else{
                    $wc_amount_map = $this->withdraw_condition_deducted_process->getAllPlayersDeductedListByRecalculateTable($wc_amount_map, $date, $recalculate_deducted_process_table);
                }

                $this->utils->debug_log('after use accumulate deduction when calculate cashback', $wc_amount_map);
            }

            return $wc_amount_map;
        }

        //only for promotion
		$player_sql='';
		if(!empty($player_id)){
			$player_sql=' and player_id=? ';
            $data[]=$player_id;
		}

        $player_sql .=' and withdraw_conditions.status=? ';
        $data[]=self::STATUS_NORMAL;

		$sql = sprintf($sql_format, $player_sql);

		$query = $this->db->query($sql, $data);

		$rows = $this->getMultipleRow($query);
		//echo $this->db->last_query();

		$wc_amount_map = array();
		if(!empty($rows)){
			foreach ($rows as $row) {
				$wc_amount_map[$row->player_id] = $row->amount;
			}
		}

		return $wc_amount_map;
	}

	/**
	 * Get $from $to for Withdraw Condition
	 *
	 * @param string $date A date/time string. Valid formats are explained in Date and Time Formats.
	 * @param string $startHour 00, 01~23 For hour of begin
	 * @param string $endHour 00, 01~23 For hour of end
	 * @param string $start_date The date string of begin, ex: 2021-07-21
	 * @param string $end_date The date string of end, ex: 2021-07-21
	 * @return array The array contains $form and $to.
	 */
	public function _getFromTo4WithdrawCondition($date, $startHour, $endHour, $start_date=null, $end_date=null){
		$lastDate = $this->utils->getLastDay($date);

		if (intval($endHour) == 23) {
			//all yesterday
			$date = $lastDate;
		}

		$from = $lastDate . ' ' . $startHour . ':00:00';
		$to = $date . ' ' . $endHour . ':59:59';

        // weekly cashback
        if(!empty($start_date) && !empty($end_date)){
            $from = $start_date . ' ' . $startHour . ':00:00';
            $to = $end_date . ' ' . $endHour . ':59:59';
        }
		return [$from, $to];
	} // EOF _getFromToDatetime4WithdrawCondition


	/**
	 * overview : cancel withdrawal condition
	 *
	 * @param  array	$ids
	 * @return bool
	 */
	public function cancelWithdrawalCondition($ids, $cancelManualStatus=self::DETAIL_STATUS_ACTIVE) {
		$data['status'] = parent::STATUS_DISABLED;
		$data['updated_at'] = $this->utils->getNowForMysql();
		$data['stopped_at'] = $this->utils->getNowForMysql();
		$data['admin_user_id'] = $this->authentication->getUserId();
        $data['detail_status'] = $cancelManualStatus;

		$this->db->where_in('id', $ids);
		$this->db->set($data);
		$success = $this->runAnyUpdate('withdraw_conditions');

		// $this->utils->printLastSQL();

		if ($success) {
			$this->load->model(['player_promo']);

			$this->db->select('player_id, player_promo_id')->from('withdraw_conditions')
				->where_in('id', $ids)
				->where('player_promo_id is not null', null, false);

			$rows = $this->runMultipleRowArray();

			$this->utils->printLastSQL();
			$this->utils->debug_log('rows', count($rows));
			if (!empty($rows)) {
				$playerPromoArr = [];
				foreach ($rows as $row) {
					$playerPromoArr[] = $row['player_promo_id'];
				}
				if (!empty($playerPromoArr)) {
					$this->player_promo->finishPlayerPromos($playerPromoArr, 'auto finished by cancel withdraw condition');
				}
			}

		}

		return $success;
	}

	public function existUnfinishedWithdrawalConditionByPromorulesId($playerId, $promorulesId, $start = null, $end = null){
		$result = false;
		$unfinished_rows = [];
		if(!empty($promorulesId)){
			$this->db->select('id, is_finished')
					 ->from($this->tableName)
					 ->where('promotion_id', $promorulesId)
					 ->where('player_id', $playerId)
					 ->where('status', self::STATUS_NORMAL)
					 ->where('withdraw_condition_type', self::WITHDRAW_CONDITION_TYPE_BETTING);

			if(!empty($start) && !empty($end)){
				$this->db->where('started_at >=', $start);
				$this->db->where('started_at <=', $end);
			}
			$rows = $this->runMultipleRowArray();
			if(!empty($rows)){
				foreach($rows as $row){
					if($row['is_finished'] == self::UN_FINISHED_WITHDRAW_CONDITIONS_FLAG){						
						$unfinished_rows[] = $row;
					}
				}
			}

			if(!empty($unfinished_rows)){
				$result = true;
				$this->utils->debug_log('unfinished_rows', $unfinished_rows);
			}
		}
		return $result;
	}

	/**
	 * overview : get player withdrawal condition
	 *
	 * @param int	$playerId
	 * @return array|bool
	 */
	public function getPlayerWithdrawalCondition($playerId, $where = null, $order_by = null) {
        if($this->utils->isEnabledFeature('disabled_withdraw_condition_share_betting_amount')){
            return $this->getPlayerWithdrawalConditionWithoutSharingBettingAmount($playerId, $where, $order_by);
        }else{
            $this->load->model(array('transactions', 'promorules'));

			if( empty($order_by) ){
				$order_by["withdraw_conditions.started_at"] = 'desc';
			}

			if( empty($where) ){
				// $where["withdraw_conditions.started_at"] = 'desc';
				$where['withdraw_conditions.player_id'] = $playerId;
				$where['withdraw_conditions.status'] = self::STATUS_NORMAL;
			}


            // git issue #1371: added promocmssetting.deleted_flag
            $this->db->distinct()->select('promorules.promorulesId,
						   promorules.promoName,
						   promocmssetting.promoCmsSettingId,
						   promocmssetting.promo_code,
						   promocmssetting.deleted_flag AS promocms_del_flag,
						   promorules.promoType,
						   promorules.nonDepositPromoType,
						   promorules.deleted_flag AS promorules_del_flag,
						   promorules.withdrawRequirementDepositConditionType,
						   promorules.disable_cashback_if_not_finish_withdraw_condition,
						   player.username,
						   player.playerId,
						   playerpromo.playerpromoId,
						   playerpromo.withdrawalStatus,
						   playerpromo.promoStatus,
						   playerpromo.note as pp_note,
						   withdraw_conditions.bonus_amount bonusAmount,
						   withdraw_conditions.id as withdrawConditionId,
						   withdraw_conditions.condition_amount as conditionAmount,
						   withdraw_conditions.started_at,
						   withdraw_conditions.source_type,
						   withdraw_conditions.source_id,
						   withdraw_conditions.wallet_type,
						   withdraw_conditions.note,
						   withdraw_conditions.trigger_amount,
						   withdraw_conditions.withdraw_condition_type,
						   withdraw_conditions.is_deducted_from_calc_cashback as cashback_deduted_flag,
						   withdraw_conditions.promotion_id,
						   promotype.promoTypeName,
						   promotype.promoTypeDesc,
						   promorules.promoCategory,
						   withdraw_conditions.deposit_amount as walletDepositAmount')
                ->from('withdraw_conditions')
                ->join('playerpromo', 'playerpromo.playerpromoId = withdraw_conditions.player_promo_id', 'left')
                ->join('promorules', 'promorules.promorulesId = playerpromo.promorulesId OR promorules.promorulesId = withdraw_conditions.promotion_id', 'left')
                ->join('promocmssetting', 'promocmssetting.promoId = promorules.promorulesId', 'left')
                ->join('promotype', 'promotype.promotypeId = promorules.promoCategory', 'left')
                ->join('player', 'player.playerId = withdraw_conditions.player_id', 'left');

			foreach($where as $where_field => $where_value){
				$this->db->where($where_field, $where_value);
			}
            // $this->db->where('withdraw_conditions.player_id', $playerId);
			// $this->db->where('withdraw_conditions.status', self::STATUS_NORMAL);
            $this->db->where('promocmssetting.deleted_flag IS NULL', null, false);

			foreach($order_by as $order_by_field => $order_by_value){
				$this->db->order_by($order_by_field, $order_by_value);
			}

            $query = $this->db->get();

            $this->utils->printLastSQL();

            if ($query->num_rows() > 0) {
                $this->load->model(array('total_player_game_hour', 'group_level', 'game_logs'));
                $data = array();

                $gameDescIdArrFromLevel=$this->group_level->getAllowedGameIdArr($playerId);

                $wd_ident_ar = [];
                $totalRequiredBet = 0;
                $totalPlayerBet = 0;

                foreach ($query->result_array() as $row) {
                    // git issue #1371, hide probably one-to-many promorules-promocmsSettings matches
                    $wd_ident = "{$row['withdrawConditionId']}-{$row['promorulesId']}-{$row['playerpromoId']}-{$row['source_id']}";
                    if (array_key_exists($wd_ident, $wd_ident_ar)) {
                        continue;
                    }
                    else {
                        $wd_ident_ar[$wd_ident] = 1;
                    }
                    $gameDescIdArr=$gameDescIdArrFromLevel;

                    if($this->utils->getConfig('hide_zero_wc_for_deposit_only')){
                        if(is_null($row['promotion_id']) && empty($row['conditionAmount'])){
                            continue;
                        }
                    }

                    //check if deposit promo and non deposit promo (email,registration,mobile)
                    if ($row['promoType'] == Promorules::PROMO_TYPE_NON_DEPOSIT ||
                        $row['promoType'] == Promorules::PROMO_TYPE_DEPOSIT) {
							if($row['promoName'] != Promorules::SYSTEM_MANUAL_PROMO_RULE_NAME){
								if ($row['promorulesId']) {
									$this->load->model(array('promorules'));
									$gameDescIdArr = $this->promorules->getPlayerGames($row['promorulesId']);
									$this->utils->debug_log('gameDescIdArr from promo rules:'.$row['promorulesId']);
								}
							}
                    }

					$row['currentBet'] = $this->game_logs->totalPlayerBettingAmountWithLimitByVIP($row['playerId'], $row['started_at'], null, $gameDescIdArr);

                    $game_bet_with_min_odds = $this->utils->getConfig('filter_game_bet_with_min_odds');
                    $promo_cms_id = $row['promoCmsSettingId'];
                    if(!empty($game_bet_with_min_odds[$promo_cms_id]) && !empty($game_bet_with_min_odds[$promo_cms_id]['min_odds'])){
                        $min_odds = $game_bet_with_min_odds[$promo_cms_id]['min_odds'];
                        $row['currentBet'] = $this->game_logs->totalPlayerBettingAmountWithLimitByOdds($row['playerId'], $row['started_at'], null, $gameDescIdArr, $min_odds);
                        $this->utils->debug_log('use game log calculute total player betting amount with limit odds', $row['currentBet']);
                    }

                    switch ($row['withdraw_condition_type']){
                        case self::WITHDRAW_CONDITION_TYPE_DEPOSIT:
                            $row['currentDeposit'] = 0;
                            $row['conditionDepositAmount'] = 0;

                            //if promorule has deposit condition
                            $row['conditionDepositAmount'] = $row['conditionAmount'];

                            if($row['withdrawRequirementDepositConditionType'] == Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT){
                                $row['currentDeposit'] = $this->transactions->getPlayerTotalDeposits($playerId,$row['started_at'],$this->utils->getNowForMysql());
                            }
                            if($row['withdrawRequirementDepositConditionType'] == Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT_SINCE_REGISTRATION){
                                $row['currentDeposit'] = $this->transactions->getPlayerTotalDeposits($playerId);
                            }

                            $row['unfinished_deposit'] = $row['conditionDepositAmount'] - $row['currentDeposit'];
                            $row['is_finished_deposit'] = ($row['unfinished_deposit'] > 0) ? self::UN_FINISHED_WITHDRAW_CONDITIONS_TYPE_DEPOSIT_FLAG : self::FINISHED_WITHDRAW_CONDITIONS_TYPE_DEPOSIT_FLAG ;
                            break;
                        case self::WITHDRAW_CONDITION_TYPE_BETTING:
						default:
                            $totalRequiredBet += $row['conditionAmount'];
                            $totalPlayerBet = $row['currentBet']; // will use the first wc's total bet amount

							if($this->utils->getConfig('update_withdraw_condition_bet_amount')){
								$this->updateWithdrawalConditionBetAmount($row['withdrawConditionId'], $row['currentBet']);
	                        }

                            $row['unfinished'] = $row['conditionAmount'] - $row['currentBet'];
                            $row['is_finished'] = ($row['unfinished'] > 0) ? self::UN_FINISHED_WITHDRAW_CONDITIONS_FLAG : self::FINISHED_WITHDRAW_CONDITIONS_FLAG ;
                            $this->updateWithdrawalConditionFinishFlag($row['withdrawConditionId'], $row['is_finished']);
                            break;
                    }

					$removeHyperlink = false;
					if ( $row['promoName'] == Promorules::SYSTEM_MANUAL_PROMO_RULE_NAME) {
						$row['promoName']  = lang('promo.'. Promorules::SYSTEM_MANUAL_PROMO_RULE_NAME);
						$removeHyperlink = true;
					}

                    $row['promoBtn'] = $this->utils->createPromoDetailButton($row['promorulesId'], $row['promoName'], $removeHyperlink);

					$row['promoName'] == null ? $row['promoName'] = '' : $row['promoName'];

                    $row['promoCode'] = empty($row['promo_code']) ? '' : $row['promo_code'];

					$shouldChangeSourceType = $this->utils->getConfig('change_source_type_to_cashback');
					if ($shouldChangeSourceType) {
						$transaction = $this->transactions->getTransactionInfoById($row['source_id']);
						$isAutoAddCashback = $transaction && $transaction['transaction_type'] == transactions::AUTO_ADD_CASHBACK_TO_BALANCE;
						if ($isAutoAddCashback) {
							$row['source_type'] = Withdraw_condition::SOURCE_CASHBACK;
							$this->utils->debug_log('change source type to cashback', $row['source_id'], $row['source_type']);
						}
					}

                    $data[] = $row;
                }
                $data['totalRequiredBet'] = $totalRequiredBet;
                $data['totalPlayerBet'] = $totalPlayerBet;
                return $data;
            }

            return false;
        }
	}

    public function getPlayerWithdrawalConditionWithoutSharingBettingAmount($playerId, $where = null, $order_by = null){
        $this->load->model(array('transactions', 'promorules', 'quest_manager'));

		if( empty($order_by) ){
			$order_by["withdraw_conditions.started_at"] = 'ASC';
		}

		if( empty($where) ){
			$where['withdraw_conditions.player_id'] = $playerId;
			$where['withdraw_conditions.status'] = self::STATUS_NORMAL;
		}
        $this->db->distinct()->select('promorules.promorulesId,
						   promorules.promoName,
						   promocmssetting.promoCmsSettingId,
						   promocmssetting.promo_code,
						   promocmssetting.deleted_flag AS promocms_del_flag,
						   promorules.promoType,
						   promorules.nonDepositPromoType,
						   promorules.deleted_flag AS promorules_del_flag,
						   promorules.withdrawRequirementDepositConditionType,
						   promorules.disable_cashback_if_not_finish_withdraw_condition,
						   player.username,
						   player.playerId,
						   playerpromo.playerpromoId,
						   playerpromo.withdrawalStatus,
						   playerpromo.promoStatus,
						   playerpromo.note as pp_note,
						   withdraw_conditions.bonus_amount bonusAmount,
						   withdraw_conditions.id as withdrawConditionId,
						   withdraw_conditions.condition_amount as conditionAmount,
						   withdraw_conditions.started_at,
						   withdraw_conditions.source_type,
						   withdraw_conditions.source_id,
						   withdraw_conditions.wallet_type,
						   withdraw_conditions.note,
						   withdraw_conditions.trigger_amount,
						   withdraw_conditions.withdraw_condition_type,
						   withdraw_conditions.is_deducted_from_calc_cashback as cashback_deduted_flag,
						   withdraw_conditions.promotion_id,
						   promotype.promoTypeName,
						   promotype.promoTypeDesc,
						   promorules.promoCategory,
						   withdraw_conditions.deposit_amount as walletDepositAmount,
						   player_quest_job_state.questManagerId as questManagerId')
            ->from('withdraw_conditions')
            ->join('playerpromo', 'playerpromo.playerpromoId = withdraw_conditions.player_promo_id', 'left')
            ->join('promorules', 'promorules.promorulesId = playerpromo.promorulesId OR promorules.promorulesId = withdraw_conditions.promotion_id', 'left')
            ->join('promocmssetting', 'promocmssetting.promoId = promorules.promorulesId', 'left')
            ->join('promotype', 'promotype.promotypeId = promorules.promoCategory', 'left')
            ->join('player', 'player.playerId = withdraw_conditions.player_id', 'left')
			->join('player_quest_job_state', 'player_quest_job_state.withdrawConditionId = withdraw_conditions.id', 'left');

		foreach($where as $where_field => $where_value){
			$this->db->where($where_field, $where_value);
		}
        // $this->db->where('withdraw_conditions.player_id', $playerId);
        // $this->db->where('withdraw_conditions.status', self::STATUS_NORMAL);
        $this->db->where('promocmssetting.deleted_flag IS NULL', null, false);

		foreach($order_by as $order_by_field => $order_by_value){
			$this->db->order_by($order_by_field, $order_by_value);
		}
		// $this->db->order_by("withdraw_conditions.started_at", "ASC");

        $query = $this->db->get();

		$this->utils->printLastSQL();

        if ($query->num_rows() > 0) {
            $this->load->model(['group_level', 'game_logs']);
            $data = [];
            $rows = $query->result_array();

            $gameDescIdArrFromLevel=$this->group_level->getAllowedGameIdKV($playerId);
            $game_logs = $this->game_logs->getPlayerTotalGameLogsByDate($rows['0']['playerId'], $rows['0']['started_at']);

            $totalRequiredBet = 0;
            $totalPlayerBet = 0;
            $promo_game_list = [];
			$quest_game_list = [];
            $wd_ident_ar = [];

            $realTotalBet=0;
            foreach($game_logs as $key => $log_row){
                $realTotalBet+=$log_row['betting_amount'];
            }
            $this->utils->debug_log('first $realTotalBet', $realTotalBet, count($game_logs));

            foreach ($rows as $row) {

                // git issue #1371, hide probably one-to-many promorules-promocmsSettings matches
                $wd_ident = "{$row['withdrawConditionId']}-{$row['promorulesId']}-{$row['playerpromoId']}-{$row['source_id']}";
                if (array_key_exists($wd_ident, $wd_ident_ar)) {
                    continue;
                }
                else {
                    $wd_ident_ar[$wd_ident] = 1;
                }

                if($this->utils->getConfig('hide_zero_wc_for_deposit_only')){
                    if(is_null($row['promotion_id']) && empty($row['conditionAmount'])){
                        continue;
                    }
                }

                $gameDescIdArr=$gameDescIdArrFromLevel;

                if($row['promoType'] == Promorules::PROMO_TYPE_NON_DEPOSIT || $row['promoType'] == Promorules::PROMO_TYPE_DEPOSIT){
					if($row['promoName'] != Promorules::SYSTEM_MANUAL_PROMO_RULE_NAME){
						if(isset($row['promorulesId'])){
	
							if(isset($promo_game_list[$row['promorulesId']])){
								$gameDescIdArr = $promo_game_list[$row['promorulesId']];
							}else{
								$gameDescIdArr = $this->promorules->getPlayerGamesKV($row['promorulesId']);
								$promo_game_list[$row['promorulesId']] = $gameDescIdArr;
								$this->utils->debug_log('gameDescIdArr from promo rules:'.$row['promorulesId']);
							}
						}
					}
				}

				if(isset($row['questManagerId'])){
					if(isset($quest_game_list[$row['questManagerId']])){
						$gameDescIdArr = $quest_game_list[$row['questManagerId']];
					}else{
						$gameDescIdArr = $this->quest_manager->getPlayerGamesKV($row['questManagerId']);
						$quest_game_list[$row['questManagerId']] = $gameDescIdArr;
						$this->utils->debug_log('gameDescIdArr from quest manager:'.$row['questManagerId'], $quest_game_list[$row['questManagerId']]);
					}
				}

				$removeHyperlink = false;
				if ( $row['promoName'] == Promorules::SYSTEM_MANUAL_PROMO_RULE_NAME) {
					$row['promoName']  = lang('promo.'. Promorules::SYSTEM_MANUAL_PROMO_RULE_NAME);
					$removeHyperlink = true;
				}

                $row['promoBtn'] = $this->utils->createPromoDetailButton($row['promorulesId'], $row['promoName'], $removeHyperlink);
                $row['promoName'] == null ? $row['promoName'] = '' : $row['promoName'];
                $row['promoCode'] = empty($row['promo_code']) ? '' : $row['promo_code'];

                switch ($row['withdraw_condition_type']){
                    case self::WITHDRAW_CONDITION_TYPE_DEPOSIT:
                        $row['currentDeposit'] = 0;
                        $row['conditionDepositAmount'] = 0;

                        //if promorule has deposit condition
                        $row['conditionDepositAmount'] = $row['conditionAmount'];

                        if($row['withdrawRequirementDepositConditionType'] == Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT){
                            $row['currentDeposit'] = $this->transactions->getPlayerTotalDeposits($playerId,$row['started_at'],$this->utils->getNowForMysql());
                        }
                        if($row['withdrawRequirementDepositConditionType'] == Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT_SINCE_REGISTRATION){
                            $row['currentDeposit'] = $this->transactions->getPlayerTotalDeposits($playerId);
                        }

                        $row['unfinished_deposit'] = $row['conditionDepositAmount'] - $row['currentDeposit'];
                        $row['is_finished_deposit'] = ($row['unfinished_deposit'] > 0) ? self::UN_FINISHED_WITHDRAW_CONDITIONS_TYPE_DEPOSIT_FLAG : self::FINISHED_WITHDRAW_CONDITIONS_TYPE_DEPOSIT_FLAG ;
                        break;
                    case self::WITHDRAW_CONDITION_TYPE_BETTING:
                    default:
						$this->totalPlayerCurrentBetWithoutSharingBettingAmount($row, $game_logs, $gameDescIdArr, $totalRequiredBet, $totalPlayerBet);

						$this->utils->debug_log("Will patch for OGP-14387, currentBet:", $row['currentBet']);
						/// Patch for OGP-14387 player can't use APP withdraw
						// $row['currentBet'] is double, result will be scientific notation.
						// Avoid $row['unfinished'] turned into scientific notation
						// BTW $row['currentBet'] generated from $game_logs and game need float format.
						$f = "%f";
						$row['unfinished'] = $row['conditionAmount'] - sprintf($f, $row['currentBet']);
						$this->utils->debug_log("After patch for OGP-14387, unfinished:", $row['unfinished']);
						
						if($this->utils->getConfig('enable_keep_two_decimal_without_rounding_in_wc')){
							$row['unfinished'] = (float) substr(sprintf("%.3f", $row['unfinished']), 0, -1);
							$this->utils->debug_log("After keep two decimal places without rounding, unfinished:", $row['unfinished']);
						}

                        if($row['unfinished'] > 0){
                            $unfinished_record = "withdraw_condition_id:[". $row['withdrawConditionId'] ."], unfinished:[" . $row['unfinished'] . "], conditionAmount:[" . $row['conditionAmount'] . "], currentBet:[" . $row['currentBet'] . "]";
                            $this->utils->debug_log("--------------------> getPlayerWithdrawalConditionWithoutSharingBettingAmount", $unfinished_record);
                        }

                        if($this->utils->getConfig('update_withdraw_condition_bet_amount')){
							$this->updateWithdrawalConditionBetAmount($row['withdrawConditionId'], $row['currentBet']);
                        }

                        $row['is_finished'] = ($row['unfinished'] > 0) ? self::UN_FINISHED_WITHDRAW_CONDITIONS_FLAG : self::FINISHED_WITHDRAW_CONDITIONS_FLAG ;
                        $this->updateWithdrawalConditionFinishFlag($row['withdrawConditionId'], $row['is_finished']);
                        break;
                }

                $data[] = $row;
            }
            $data['totalRequiredBet'] = $totalRequiredBet;
            $data['totalPlayerBet'] = $totalPlayerBet;
            return $data;
        }

        return false;
    }

    public function totalPlayerCurrentBetWithoutSharingBettingAmount(&$withdraw_condition, &$game_logs, $gameDescIdArr, &$totalRequiredBet, &$totalPlayerBet){
		$withdraw_condition['currentBet'] = 0;
        $totalRequiredBet += $withdraw_condition['conditionAmount'];

        if(empty($game_logs)){
            return;
        }

        $withdraw_condition['requireBetAmount'] = 0;
        $withdraw_condition['requireBetAmount'] = $withdraw_condition['conditionAmount'];
        foreach($game_logs as $key => &$log_row){

            if(!isset($gameDescIdArr[$log_row['game_description_id']])){
                continue;
            }

            if($withdraw_condition['requireBetAmount'] <= 0){
                break;
            }

            if($log_row['used']){
                continue;
            }

            if(strtotime($log_row['date_minute']) - strtotime($withdraw_condition['started_at']) < 0){
                continue;
			}

			/// About OGP-14387,
			// $withdraw_condition['requireBetAmount'] type will from string convert to double.
            $withdraw_condition['requireBetAmount'] -= $log_row['betting_amount'];

            if($withdraw_condition['requireBetAmount'] >= 0){
                $log_row['betting_amount'] = 0;
                $log_row['used'] = true;
                unset($game_logs[$key]);
                continue;
            }

            $log_row['betting_amount'] = abs($withdraw_condition['requireBetAmount']);
        }

        if($withdraw_condition['requireBetAmount'] <= 0){
			$withdraw_condition['currentBet'] = $withdraw_condition['conditionAmount'];
        }else{
			$withdraw_condition['currentBet'] = $withdraw_condition['conditionAmount'] - $withdraw_condition['requireBetAmount'];
        }

        $this->utils->debug_log('withdraw condition', $withdraw_condition['currentBet'], $withdraw_condition['requireBetAmount'], $withdraw_condition['conditionAmount']);
        unset($withdraw_condition['requireBetAmount']);
        $totalPlayerBet += $withdraw_condition['currentBet'];

        $this->utils->debug_log('current $totalPlayerBet', $totalPlayerBet, $totalRequiredBet);
    }

    public function updateWithdrawalConditionFinishFlag($id, $is_finished = self::UN_FINISHED_WITHDRAW_CONDITIONS_FLAG) {
        $this->db->set('is_finished', $is_finished)->where('id', $id);
        $this->runAnyUpdate($this->tableName);
        $this->utils->debug_log('update withdraw condition finished flag ['. $is_finished .']',$id);
    }

    public function updateWithdrawalConditionBetAmount($id, $currentBet = 0) {
        $this->db->set('bet_amount', $currentBet)->where('id', $id);
        $this->runAnyUpdate($this->tableName);
        $this->utils->debug_log('update withdraw condition bet amount ['. $currentBet .']',$id);
    }

    /**
     * overview : get player deposit condition in withdrawal conditions
     *
     * @param int	$playerId
     * @return array|bool
     */
    public function getPlayerDepositConditionInWithdrawalCondition($playerId) {
        $this->load->model(array('transactions'));
        $this->db->distinct()->select('promorules.withdrawRequirementDepositConditionType,
                       withdraw_conditions.condition_amount as conditionAmount,
                       withdraw_conditions.id as withdrawConditionId,
                       withdraw_conditions.started_at')
            ->from('withdraw_conditions')
            ->join('promorules', 'promorules.promorulesId = withdraw_conditions.promotion_id', 'left')
            ->where('withdraw_conditions.player_id', $playerId)
            ->where('withdraw_conditions.status', self::STATUS_NORMAL)
            ->where('withdraw_conditions.withdraw_condition_type', self::WITHDRAW_CONDITION_TYPE_DEPOSIT)
            ->order_by("withdraw_conditions.started_at", "asc");

        $query = $this->db->get();

        $this->utils->printLastSQL();

        if ($query->num_rows() > 0) {
            $data = array();
            foreach ($query->result_array() as $row) {
                $row['conditionDepositAmount'] = $row['conditionAmount'];
                switch ($row['withdrawRequirementDepositConditionType']){
                    case Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT:
                        $row['currentDeposit'] = $this->transactions->getPlayerTotalDeposits($playerId,$row['started_at'],$this->utils->getNowForMysql());
                        break;
                    case Promorules::DEPOSIT_CONDITION_TYPE_MIN_LIMIT_SINCE_REGISTRATION:
                        $row['currentDeposit'] = $this->transactions->getPlayerTotalDeposits($playerId);
                        break;
                    case Promorules::DEPOSIT_CONDITION_TYPE_NOTHING:
                    default :
                        break;
                }
                $data[] = $row;
            }

            return $data;
        }

        return false;
    }

	/**
	 * Get the array of Zero Condition Amount Result to Compare Condition.
	 *
	 * @param bool $isEnable The feature switch status.
	 * @return array The format like return from self::processCompareCondition().
	 */
	public function getZeroConditionAmountResult4CompareCondition($isEnable){
		$resultCompareCondition = [];
		$resultCompareCondition['formula'] = 'The Condition Amount is Zero, ignore and met the condition. ';
		$resultCompareCondition['result'] = true; // ignore
		$resultCompareCondition['isEnable'] = $isEnable;
		return $resultCompareCondition;
	} // EOF getZeroConditionAmountResult4CompareCondition

	/**
	 * Compute Player Withdrawal Conditions per every Withdrawal Conditions
	 *
	 * @param integer $playerId The player.playerId.
	 * @param string $currectWithdrawlDatetime The currect Date Time of Withdrawl Request.
	 * * @param string $currectWithdrawlDatetime The currect Date Time.
	 * @param bool $isEnable Reference to "betAndWithdrawalRate_isEnable".
	 * @param string $fieldKey Usually be "betAndWithdrawalRate".
	 * @param array $contdtionDetail The dispatch_withdrawal_conditions rows.
	 * @param array $resultsDetail for collect results detail.
	 * @param array $gameDescriptionIdList The allow games,"game_description.id"
	 * @return array $resultCompareCondition The query and calc result list.
	 */
	public function computePlayerWithdrawalConditionsV2( $playerId // # 1
														, $currectWithdrawlDatetime // # 2
														, $lastWithdrawlDatetime // # 3
														, $isEnable // # 4
														, $fieldKey // # 5
														, &$contdtionDetail // # 6
														, &$resultsDetail // # 7
														, $gameDescriptionIdList = [] // # 8
	){
		$this->load->model(['total_player_game_hour','group_level']);
		$resultCompareCondition = [];
		$limit = $contdtionDetail['betAndWithdrawalRate_rate']; // betAndWithdrawalRate_rate

		// Get the Withdrawal Condition data
		$where['withdraw_conditions.player_id'] = $playerId;
		// $where['withdraw_conditions.status'] = self::STATUS_NORMAL; // get all status data.
		$where['withdraw_conditions.started_at >= '] = $lastWithdrawlDatetime;
		$where['withdraw_conditions.started_at <= '] = $currectWithdrawlDatetime;
		$order_by["withdraw_conditions.started_at"] = 'asc'; // sort by the earliest to the latest
		$withdrawalConditionRows = $this->getPlayerWithdrawalCondition($playerId, $where, $order_by);

		$wcCalc =[]; // Collect query and result at the moment when each Withdrawal Condition occurs.
		$wcCalc['result'] = []; // defaults
		$wcCalc['query'] = []; // defaults
		if( ! empty($withdrawalConditionRows) ){

			$totalRequiredBet = 0; // $totalRequiredBet will be records of withdraw_conditions during lastWithdrawlDatetime to currectWithdrawlDatetime.
			foreach($withdrawalConditionRows as $indexNumber => $wcRow ){
				$totalRequiredBet += $wcRow['conditionAmount'];
			}

			/// $totalPlayerBet be the betting amount of all game by the player.
			$fromDateMinuteStr = $this->utils->formatDateMinuteForMysql(new DateTime($lastWithdrawlDatetime)); //
			$toDateMinuteStr = $this->utils->formatDateMinuteForMysql(new DateTime($currectWithdrawlDatetime)); //
			$total_player_game_table='total_player_game_minute' ;
			$where_date_field = 'date_minute';
			$gamePlatformId=null;
			$gameDescriptionId = $gameDescriptionIdList;
			$calcAvailableBetOnly = false;
			list($totalBet, $totalResult, $totalWin, $totalLoss) = $this->total_player_game_hour
								->getTotalAmountFromHourlyReportByPlayerAndDateTime(  $playerId // # 1
																					, $fromDateMinuteStr // , $from_datetime // # 2
																					, $toDateMinuteStr // , $to_datetime // # 3
																					, $gamePlatformId // # 4
																					, $gameDescriptionId // # 5
																					, $calcAvailableBetOnly // # 6
																					, $total_player_game_table // # 7
																					, $where_date_field // # 8
																				);
			$gameLogData['total_bet'] = $totalBet;
			$totalPlayerBet = $gameLogData['total_bet'];
			if( ! empty($totalRequiredBet) ){
				$count = $totalPlayerBet / $totalRequiredBet;
				$symbol = $this->symbolIntToMathSymbol($contdtionDetail['betAndWithdrawalRate_symbol']);
				$resultCompareCondition = $this->processCompareCondition($count, $symbol, $limit, $isEnable);
				// $resultCompareCondition['count']
				// $resultCompareCondition['formula']
				// $resultCompareCondition['result']
			}else{
				$resultCompareCondition = $this->getZeroConditionAmountResult4CompareCondition($isEnable);
			}


			$resultCompareCondition['totalPlayerBet'] = $totalPlayerBet; // for collect into resultDetail
			$resultCompareCondition['totalRequiredBet'] = $totalRequiredBet; // for collect into resultDetail
			$resultCompareCondition['lastWithdrawlDatetime'] = $lastWithdrawlDatetime;
			$resultCompareCondition['currectWithdrawlDatetime'] = $currectWithdrawlDatetime;

			// build the query by Withdrawal Condition
			foreach($withdrawalConditionRows as $indexNumber => $wcRow ){
				if( ! is_numeric($indexNumber) ){
					continue; // skip this round, ignore for the $withdrawalConditionRows has the element of string type key.
				}
				$dateTimeFrom = $wcRow['started_at'];
				$dateTimeTo = $currectWithdrawlDatetime; // keep to currect Withdrawl Datetime

				$theQuery = [];
				/// for apply to the params of getTotalAmountFromHourlyReportByPlayerAndDateTime()
				$theQuery['dateTimeFrom'] = $dateTimeFrom;
				$theQuery['dateTimeTo'] = $dateTimeTo;
				$theQuery['playerId'] = $playerId;
				$theQuery['where_game_platform_id'] = null;
				$theQuery['where_game_description_id'] = $gameDescriptionIdList;

				$theQuery['conditionAmount'] = $wcRow['conditionAmount'];
				$wcCalc['query'][$indexNumber] = $theQuery;

				// $wcCalc['withdrawalConditionRows'][$indexNumber] = $wcRow; // disable for ignore too much unused fields.
				$resultsDetail[$fieldKey]['resultDetail'][$indexNumber]['query'] = $theQuery;
			} // EOF foreach($withdrawalConditionRows as $indexNumber => $wcRow ){...

			if( ! empty($wcCalc['query']) ){
				$this->load->model(['total_player_game_hour','group_level']);
				foreach($wcCalc['query'] as $indexNumber => $theQuery ){
					if( ! empty($theQuery['conditionAmount']) ){ // handle for promo given bonus will be Zero condition amount in Withdrawal Condition.

						$fromDateMinuteStr = $this->utils->formatDateMinuteForMysql(new DateTime($theQuery['dateTimeFrom'])); //
						$toDateMinuteStr = $this->utils->formatDateMinuteForMysql(new DateTime($theQuery['dateTimeTo'])); //
						$total_player_game_table='total_player_game_minute' ;
						$where_date_field = 'date_minute';
						$_gamePlatformId = $theQuery['where_game_platform_id'];
						$_gameDescriptionId = $theQuery['where_game_description_id'];
						$calcAvailableBetOnly = false;
						list($totalBet, $totalResult, $totalWin, $totalLoss) = $this->total_player_game_hour
											->getTotalAmountFromHourlyReportByPlayerAndDateTime(  $theQuery['playerId'] // # 1
																								, $fromDateMinuteStr // , $from_datetime // # 2
																								, $toDateMinuteStr // , $to_datetime // # 3
																								, $_gamePlatformId // # 4
																								, $_gameDescriptionId // # 5
																								, $calcAvailableBetOnly // # 6
																								, $total_player_game_table // # 7
																								, $where_date_field // # 8
																							);
						$theGameLogData = [];
						$theGameLogData['total_bet'] = $totalBet;
						$theGameLogData['total_result'] = $totalResult;
						$theGameLogData['total_win'] = $totalWin;
						$theGameLogData['total_loss'] = $totalLoss;
						$wcCalc['result'][$indexNumber]['gameLogData'] = $theGameLogData;
						$count = 0;
						/// 倍數 = 玩家投注額 / 需要投注總額
						// rate = Betted Amount / Condition Amount
						$count = $theGameLogData['total_bet'] / $theQuery['conditionAmount'];
						$symbol = $this->symbolIntToMathSymbol($contdtionDetail['betAndWithdrawalRate_symbol']);
						$_resultCompareCondition = $this->processCompareCondition($count, $symbol, $limit, $isEnable);
					}else{
						$_resultCompareCondition = $this->getZeroConditionAmountResult4CompareCondition($isEnable);
					}// EOF if( ! empty($theQuery['conditionAmount']) ){...

					$_resultCompareCondition['total_bet'] = $theGameLogData['total_bet'];
					$_resultCompareCondition['conditionAmount'] = $theQuery['conditionAmount']; // $wcCalc['withdrawalConditionRows'][$indexNumber]['conditionAmount'];
					$wcCalc['result'][$indexNumber]['resultCompareCondition'] = $_resultCompareCondition;

					$resultsDetail[$fieldKey]['resultDetail'][$indexNumber]['compareResult'] = $_resultCompareCondition; // for collect the result per withdrawal condition.
				} // EOF foreach($wcCalc['query'] as $indexNumber => $theQuery ){...
			} // EOF if( ! empty($wcCalc['query']) ){...
			// $this->utils->debug_log('OGP-20026.574.wcCalc', $wcCalc);

		}else{
			$resultCompareCondition['formula'] = 'totalRequiredBet is zero, met the condition.';
			$resultCompareCondition['result'] = true; // ignore
		} // EOF if( ! empty($withdrawalConditionRows) ){...

		$resultsDetail[$fieldKey]['resultDetail']['total'] = $resultCompareCondition; // for collect the result of total.

		$result = $resultCompareCondition['result']; // initial result from total
		$resultCompareCondition['formula'] = [];
		if( ! empty($wcCalc['result'][0]) ){
			foreach( $wcCalc['result'] as $indexNumber => $wcCalcResult ){
				$currResultCompareCondition = $wcCalcResult['resultCompareCondition'];
				$result = $result && $currResultCompareCondition['result'];
				$resultCompareCondition['formula'][$indexNumber] =  $currResultCompareCondition['formula'];
			}
		}
		$resultCompareCondition['result'] = $result;

		return $resultCompareCondition;
	} // EOF computePlayerWithdrawalConditionsV2()

    public function computePlayerWithdrawalConditionsWithDepositCondition($playerId)
    {
        $withdraw_conditions = $this->getPlayerWithdrawalCondition($playerId);

        $data = array(
            'totalRequiredBet' => 0,
            'totalPlayerBet' => 0,
            'unfinished_bet' => 0,
            'totalRequiredDeposit' => 0,
            'totalPlayerDeposit' => 0,
            'unfinished_deposit' => 0,
            'unfinished' => 0,
        );

        if(!$withdraw_conditions) return $data;

        $tmp_required_bet = $tmp_current_bet = $unfinished_dc_foreach = 0;

        if($this->utils->isEnabledFeature('disabled_withdraw_condition_share_betting_amount')){
            if(isset($withdraw_conditions['totalRequiredBet'])){
                $tmp_required_bet = $withdraw_conditions['totalRequiredBet'];
            }
            if(isset($withdraw_conditions['totalPlayerBet'])){
                $tmp_current_bet = $withdraw_conditions['totalPlayerBet'];
            }
        }else{
            foreach($withdraw_conditions as $withdraw_condition){
                if($withdraw_condition['withdraw_condition_type'] == self::WITHDRAW_CONDITION_TYPE_BETTING){
                    // -- Compute for current total bet
                    if($withdraw_condition['currentBet'] > $tmp_current_bet){
                        $tmp_current_bet +=  $withdraw_condition['currentBet'];
                    }
                    // -- Compute for total required bet amount
                    $tmp_required_bet +=  $withdraw_condition['conditionAmount'];
                }
            }
        }

        // -- Compute for total unfinished bet
        $tmp_unfinished_bet = $tmp_required_bet - $tmp_current_bet;
        $tmp_unfinished_bet = ($tmp_unfinished_bet <= 0) ? 0 : $tmp_unfinished_bet;

        // -- Compute for total unfinished deposit
        if($this->utils->isEnabledFeature('check_deposit_conditions_foreach_in_withdrawal_conditions')){
            if(FALSE !== $un_finished_deposit = $this->getPlayerUnfinishedDepositConditionForeach($playerId)){
                $unfinished_dc_foreach = $un_finished_deposit;
            }
        }

        $data['unfinished_bet'] = $tmp_unfinished_bet;
        $data['unfinished_deposit'] = $unfinished_dc_foreach;

        $this->utils->debug_log('=======================computePlayerWithdrawalConditionsWithDepositCondition',
            'playerId => ', $playerId,
            '|totalRequiredBet => ', $tmp_required_bet,
            '|totalPlayerBet => ', $tmp_current_bet,
            '|unfinished_bet => ', $tmp_unfinished_bet,
            '|unfinished_deposit => ', $unfinished_dc_foreach,
            '|tmp_unfinished_bet > 0 => ', ($tmp_unfinished_bet > 0),
            '|tmp_unfinished_deposit > 0 => ', ($unfinished_dc_foreach > 0));

        return $data;
    }

    public function onlyCheckHasUnfinishedWithdrawalCondictionRecords ($playerId) {
        $this->db->from('withdraw_conditions');
        $this->db->where('withdraw_conditions.status', self::STATUS_NORMAL);
        $this->db->where('withdraw_conditions.is_finished', self::UN_FINISHED_WITHDRAW_CONDITIONS_FLAG);
        $this->db->where('withdraw_conditions.player_id', $playerId);
        $query = $this->db->get();

        $this->utils->printLastSQL();

        if ($query->num_rows() > 0) {
            return true;
        }
    }
    public function existUnfinishWithdrawConditions($playerId){
        if($this->utils->isEnabledFeature('check_withdrawal_conditions')){
            $unfinished_wc = $this->getPlayerUnfinishedWithdrawCondition($playerId);
            if(FALSE !== $unfinished_wc){
                return TRUE;
            }
        }
        if($this->utils->isEnabledFeature('check_withdrawal_conditions_foreach')){
            $unfinished_wc_foreach = $this->getPlayerUnfinishedWithdrawConditionForeach($playerId);
            if( FALSE !== $unfinished_wc_foreach){
                return TRUE;
            }
        }
        if($this->utils->isEnabledFeature('check_deposit_conditions_foreach_in_withdrawal_conditions')){
            $unfinished_dc_foreach = $this->getPlayerUnfinishedDepositConditionForeach($playerId);
            if(FALSE !== $unfinished_dc_foreach){
                return TRUE;
            }
        }
        return FALSE;
    }
	/**
	 * Computes for total required bet, current total bet, and unfinished bet amount pf player
	 * based on his withdrawal conditions.
	 *
	 * @param  string $playerId Player ID
	 * @return Array           Computed / summarized withdrawal conditions
	 * @author Cholo Miguel Antonio
	 */
	public function computePlayerWithdrawalConditions($playerId)
	{
		$withdraw_conditions = $this->getPlayerWithdrawalCondition($playerId);

		$data = array(
			'totalRequiredBet' => 0,
			'totalPlayerBet' => 0,
			'unfinished' => 0,
		);

		if(!$withdraw_conditions) return $data;

		$tmp_required_bet = 0;
		$tmp_current_bet = 0;
		$tmp_unfinished_bet = 0;

		foreach($withdraw_conditions as $withdraw_condition){
            if($withdraw_condition['withdraw_condition_type'] != self::WITHDRAW_CONDITION_TYPE_BETTING){
                continue;
            }

		    // -- Compute for current total bet
			if($withdraw_condition['currentBet'] > $tmp_current_bet)
				$tmp_current_bet =  $withdraw_condition['currentBet'];

			// -- Compute for total required bet amount
			$tmp_required_bet +=  $withdraw_condition['conditionAmount'];
		}

		// -- Compute for total unfinished bet
		$tmp_unfinished_bet = $tmp_required_bet - $tmp_current_bet;


		$data['totalRequiredBet'] = $tmp_required_bet;
		$data['totalPlayerBet'] = $tmp_current_bet;
		$data['unfinished'] = $tmp_unfinished_bet;

		return $data;
	}

	/**
	 * overview : get clear withdraw condition settings
	 *
	 * @return array
	 */
	public function getClearWithdrawConditionSettings() {
		$this->load->model(['operatorglobalsettings']);
		$previous_balance_set_amount = $this->operatorglobalsettings->getSettingValue('previous_balance_set_amount');
		$clear_withdraw_cond_by_subwallet = $this->operatorglobalsettings->getSettingJson('clear_withdraw_cond_by_subwallet');
		if (empty($clear_withdraw_cond_by_subwallet)) {
			$clear_withdraw_cond_by_subwallet = [];
		}
		$apis = $this->utils->getActiveGameSystemList();
		foreach ($apis as $api) {
			$value = isset($clear_withdraw_cond_by_subwallet[$api['id']]['value']) ? $clear_withdraw_cond_by_subwallet[$api['id']]['value'] : $previous_balance_set_amount;
			$clear_withdraw_cond_by_subwallet[$api['id']] = [
				"id" => $api['id'],
				"label" => $api['system_code'],
				"value" => $value,
			];
		}
		return [
			'total' => $previous_balance_set_amount,
			'subwallets' => $clear_withdraw_cond_by_subwallet,
		];
	}

	/**
	 * overview : auto check withdraw condition
	 *
	 * @param int	$playerId
	 * @param string $message
	 * @return bool true=all finished
	 */
	public function autoCheckWithdrawConditionAndMoveBigWallet($playerId, &$message = null,
		$targetWalletType = null, $unfinished_is_false = false, $clean_condition = false, $secureId = null, $extra_info = null) {
		//first check subwallets
		$this->load->model(array('operatorglobalsettings', 'wallet_model', 'player_promo', 'game_logs'));
		$settings = $this->getClearWithdrawConditionSettings();
		$message = '';
		$promotion_rules = $this->utils->getConfig('promotion_rules');
		$success = true;
		$changed = false;
		$model = $this;

		$bigWallet = $this->wallet_model->getBigWalletByPlayerId($playerId);
		$subwallets = $settings['subwallets'];

		// EACWC = enabled_auto_clear_withdraw_condition
		// check_each_bet_amount_in_EACWC = check_each_bet_amount_in_enabled_auto_clear_withdraw_condition
		$check_each_bet_amount_in_EACWC = $this->utils->getConfig('check_each_bet_amount_in_enabled_auto_clear_withdraw_condition');

		foreach ($subwallets as $sub) {

			$detailStatus = self::DETAIL_STATUS_ACTIVE;
			$clear = false;

			$walletType = $sub['id']; // sub wallet

			if ($targetWalletType !== null && $targetWalletType != $sub['id']) {
				//ignore other sub wallet
				$this->utils->debug_log('ignore other sub wallet', $sub['id'], 'because', $targetWalletType);
				continue;
			}

			if (!$this->existsAvailWithdrawalConditionByWalletType($playerId, $walletType)) {
				$message .= 'does not exist withdraw condition on ' . $sub['label'] . "\n";
				continue;
			}

			$this->utils->debug_log('process wallet: ' . $targetWalletType . ' , playerId: ' . $playerId);

			$conditionAmount = 0;
			$firstDateTime = null;
			$balLimit = doubleval($sub['value']);
			$bal = $bigWallet['sub'][$walletType]['total'];
            $withdrawConditionIds = $this->getAvailableWithdrawConditionIdsByWalletType($playerId, $walletType);
			$firstDateTime = $this->getFirstAvailableWithdrawConditionByWalletType($playerId, $walletType);
			$preventTriggerSmallBalance = true;
			if ($bal <= $balLimit) {
				//FIXME
				//inactive all
				$preventTriggerSmallBalance = $this->checkGameLogsAndPreventSmallBalance($playerId, array('bal'=> $bal, 'balLimit' => $balLimit), $firstDateTime, $message, $walletType);
				$this->utils->debug_log('autoCheckWithdrawConditionAndMoveBigWallet subwallet', ['player_id'=>$playerId, "preventTriggerSmallBalance" => $preventTriggerSmallBalance]);
				if($preventTriggerSmallBalance){
					$this->utils->debug_log('autoCheckWithdrawConditionAndMoveBigWallet skip small balance');
				} else {
					$clear = true;
					$message .= 'finish withdraw condition on ' . $sub['label'] . ', because last balance (' . $bal . ') of player ' . $playerId . ' <= ' . $balLimit . "\n";
					$detailStatus = self::DETAIL_STATUS_CANCELLED_DUE_TO_SMALL_BALANCE;
				}

				if($preventTriggerSmallBalance && !$clear){
                    $sourceFrom = 'prevent trigger small balance, ';
                    $this->checkTotalBetByFirstDateTime($playerId, $firstDateTime, $clear, $message, $detailStatus, $walletType, $sub['label'], $sourceFrom);
                }

			} else {
                $this->checkTotalBetByFirstDateTime($playerId, $firstDateTime, $clear, $message, $detailStatus, $walletType, $sub['label']);
			}

			if ($clear) {
				if ($this->utils->isEnabledFeature('enabled_auto_clear_withdraw_condition') && $clean_condition) {
                    $reason = 'auto finished withdraw condition by ';
                    if(!empty($extra_info['auto_check_wc_from'])){
                        switch ($extra_info['auto_check_wc_from']){
                            case self::AUTO_CHECK_WITHDRAW_CONDITION_AND_MOVE_BIG_WALLET_FROM_ACCESS_USERINFORMATION:
                                $reason .= 'access userinformation';
                                break;
                            case self::AUTO_CHECK_WITHDRAW_CONDITION_AND_MOVE_BIG_WALLET_FROM_SCHEDULER:
                                $reason .= 'daily auto scheduler';
                                break;
                        }
                    }else{
                        $reason = 'deposit to subwallet ' . $walletType;
                        if(isset($secureId) && !empty($secureId)){
                            $reason .= ',Order ID : ' . $secureId;
                        }
                    }

					$disablePlayerWithdrawalConditionResultList = null;
					if($check_each_bet_amount_in_EACWC && $preventTriggerSmallBalance){
						$disablePlayerWithdrawalConditionResultList = [];
						if( ! empty($withdrawConditionIds) ){
							foreach($withdrawConditionIds as $indexNumber => $withdrawConditionId){
								$isGreaterThan = $this->isAccumulativeBetAmountGreaterThanWithdrawConditionAmount($withdrawConditionId);
								$disablePlayerWithdrawalConditionResultList[$withdrawConditionId]['isGreaterThan'] = $isGreaterThan;
								if( ! empty($isGreaterThan['bool']) ){
									$rlt = $this->disablePlayerWithdrawalConditionByWalletType($playerId, $walletType, $reason, $detailStatus, [$withdrawConditionId]);
									$disablePlayerWithdrawalConditionResultList[$withdrawConditionId]['disabledResult'] = $rlt;
								}
							}
							$this->utils->debug_log('OGP23330.1848.disablePlayerWithdrawalConditionResultList', $disablePlayerWithdrawalConditionResultList);
						}
					}else{
						$success = $success && $this->disablePlayerWithdrawalConditionByWalletType($playerId, $walletType, $reason, $detailStatus, $withdrawConditionIds);
					}

					if (!$success) {
						return $success;
					}
					if (empty($withdrawConditionIds)){
                        $message .= 'withdrawCondition is empty';
                        $this->utils->debug_log('withdrawConditionIds is empty', $withdrawConditionIds);
                    }
					$this->utils->debug_log('disablePlayerWithdrawalConditionByWalletType', $success, 'playerId', $playerId, 'walletType', $walletType, 'withdrawConditionIds', $withdrawConditionIds);

					//FIXME change status of player promo

				}
				$success = $this->wallet_model->moveSubWalletToReal($bigWallet, $walletType);
				if (!$success) {
					$message .= "move subwallet failed\n";
					return $success;
				}

				$changed = true;
			} else {
				$message .= 'still keep withdraw condition on ' . $sub['label'] . ', from ' . $firstDateTime . ', amount: ' . $conditionAmount . "\n";
				$this->utils->debug_log('unfinished_is_false', $unfinished_is_false, 'targetWalletType', $targetWalletType);
				if ($unfinished_is_false && $targetWalletType == $sub['id']) {
					$this->utils->debug_log('unfinished is false on', $targetWalletType);
					$success = false;
					break;
				}
			}
		} // EOF foreach ($subwallets as $sub)
		if (!$success) {
			return $success;
		}

		//main
		$balLimit = $settings['total'];
		$walletType = 0; // main wallet

		$bal = $this->wallet_model->getTotalBalance($playerId);
		$this->utils->debug_log('playerId', $playerId, 'balLimit', $balLimit, 'bal', $bal);
		$clear = false;
		$description = null;
		$conditionAmount = 0;
		$firstDateTime = null;
		$detailStatus = self::DETAIL_STATUS_ACTIVE;
		$withdrawConditionIds = $this->getAvailableWithdrawConditionIds($playerId);
		$firstDateTime = $this->getFirstAvailableWithdrawCondition($playerId);
		$preventTriggerSmallBalance = true;

		if ($bal <= $balLimit) {
			//inactive all
			$preventTriggerSmallBalance = $this->checkGameLogsAndPreventSmallBalance($playerId, array('bal'=> $bal, 'balLimit' => $balLimit), $firstDateTime, $message);
			$this->utils->debug_log('autoCheckWithdrawConditionAndMoveBigWallet mainwallet', ['player_id'=>$playerId, "preventTriggerSmallBalance" => $preventTriggerSmallBalance]);
			if($preventTriggerSmallBalance){
				$this->utils->debug_log('autoCheckWithdrawConditionAndMoveBigWallet skip small balance');
			} else {
				$clear = true;
				$message .= 'finish withdraw condition, because last balance (' . $bal . ') of player ' . $playerId . ' <= ' . $balLimit . "\n";
				$detailStatus =  self::DETAIL_STATUS_CANCELLED_DUE_TO_SMALL_BALANCE;
			}

			if($preventTriggerSmallBalance && !$clear){
                $sourceFrom = 'prevent trigger small balance, ';
                $this->checkTotalBetByFirstDateTime($playerId, $firstDateTime, $clear, $message, $detailStatus, null, null, $sourceFrom);
            }

		} else {
            $this->checkTotalBetByFirstDateTime($playerId, $firstDateTime, $clear, $message, $detailStatus);
		}

		if ($clear) {

			if ($this->utils->isEnabledFeature('enabled_auto_clear_withdraw_condition') && $clean_condition) {
			    $reason = 'auto finished withdraw condition by ';
			    if(!empty($extra_info['auto_check_wc_from'])){
                    switch ($extra_info['auto_check_wc_from']){
                        case self::AUTO_CHECK_WITHDRAW_CONDITION_AND_MOVE_BIG_WALLET_FROM_ACCESS_USERINFORMATION:
                            $reason .= 'access userinformation';
                            break;
                        case self::AUTO_CHECK_WITHDRAW_CONDITION_AND_MOVE_BIG_WALLET_FROM_SCHEDULER:
                            $reason .= 'daily auto scheduler';
                            break;
                    }
                }else{
                    $reason .= 'deposit to main wallet';
                    if(isset($secureId) && !empty($secureId)){
                        $reason .= ',Order ID : ' . $secureId;
                    }
                }

				$disablePlayerWithdrawalConditionResultList = null;
				if($check_each_bet_amount_in_EACWC && $preventTriggerSmallBalance){
					$disablePlayerWithdrawalConditionResultList = [];
					if( ! empty($withdrawConditionIds) ){
						foreach($withdrawConditionIds as $indexNumber => $withdrawConditionId){
							$isGreaterThan = $this->isAccumulativeBetAmountGreaterThanWithdrawConditionAmount($withdrawConditionId);
							$disablePlayerWithdrawalConditionResultList[$withdrawConditionId]['isGreaterThan'] = $isGreaterThan;
							if( ! empty($isGreaterThan['bool']) ){
								$rlt = $this->disablePlayerWithdrawalCondition($playerId, $reason, $detailStatus, [$withdrawConditionId]);
								$disablePlayerWithdrawalConditionResultList[$withdrawConditionId]['disabledResult'] = $rlt;
							}
						}
						$this->utils->debug_log('OGP23330.1937.disablePlayerWithdrawalConditionResultList', $disablePlayerWithdrawalConditionResultList);
					}
				}else{
					$success = $success && $this->disablePlayerWithdrawalCondition($playerId, $reason, $detailStatus, $withdrawConditionIds);
				}

				$this->utils->debug_log('disablePlayerWithdrawalCondition', $success, 'playerId', $playerId, 'withdrawConditionIds', $withdrawConditionIds, 'disablePlayerWithdrawalConditionResultList:',$disablePlayerWithdrawalConditionResultList);
				if (!$success) {
					return $success;
				}
				if (empty($withdrawConditionIds)){
                    $message .= 'withdraCondition is empty';
                    $this->utils->debug_log('withdrawConditionIds is empty', $withdrawConditionIds);
                }
				$this->utils->debug_log('disablePlayerWithdrawalCondition for main wallet', $success, 'playerId', $playerId);

				//change status of player promo
			} // EOF if ($this->utils->isEnabledFeature('enabled_auto_clear_withdraw_condition') && $clean_condition) {...

			$success = $this->wallet_model->moveMainWalletToReal($bigWallet);
			if (!$success) {
				$message .= "move main wallet failed\n";
				return $success;
			}
			$changed = true;
		} elseif ($conditionAmount > 0) {
			$message .= 'still keep withdraw condition on main wallet, from ' . $firstDateTime . ', amount: ' . $conditionAmount . "\n";
		}

		if ($changed && $success) {

			$this->wallet_model->totalBigWallet($bigWallet);

			$success = $this->wallet_model->updateBigWalletByPlayerId($playerId, $bigWallet);
			if (!$success) {
				$message .= 'update big wallet failed' . "\n";
			}
		}

		return $success;
	} // EOF autoCheckWithdrawConditionAndMoveBigWallet

	/**
	 * overview : get first available withdraw condition
	 *
	 * @param int	$playerId
	 * @param int	$walletType
	 * @return string
	 */
	public function getFirstAvailableWithdrawConditionByWalletType($playerId, $walletType) {
		$this->db->select('min(started_at) as firstDateTime', false)
			->from('withdraw_conditions')->where('player_id', $playerId)
			->where('wallet_type', $walletType)
			->where('status', self::STATUS_NORMAL);

		return $this->runOneRowOneField('firstDateTime');
	}

	/**
	 * overview : get available amount on withdraw condition
	 *
	 * @param $playerId
	 * @param $walletType
	 * @return null
	 */
	public function getAvailableAmountOnWithdrawConditionByWalletType($playerId, $walletType) {
		$this->db->select('sum(condition_amount) as amount', false)
			->from('withdraw_conditions')->where('player_id', $playerId)
			->where('wallet_type', $walletType)
			->where('status', self::STATUS_NORMAL);

		return $this->runOneRowOneField('amount');
	}

	/**
	 * overview : disable player withdrawal condition
	 *
	 * @param $playerId
	 * @param $walletType
	 * @return bool
	 */
	function disablePlayerWithdrawalConditionByWalletType($playerId, $walletType, $reason=null, $detailStatus=self::DETAIL_STATUS_ACTIVE, $withdrawConditionIds = null) {
        if(empty($withdrawConditionIds)){
            //empty withdraw condition
            return TRUE;
        }

        $this->load->library(['authentication']);
        $admin_user_id = $this->authentication->getUserId();
        $admin_user_id = !empty($admin_user_id) ? $admin_user_id : Users::SUPER_ADMIN_ID;

		$data['status'] = parent::STATUS_DISABLED;
		$data['updated_at'] = $this->utils->getNowForMysql();
		$data['stopped_at'] = $this->utils->getNowForMysql();
		$data['admin_user_id'] = $admin_user_id;
		$data['detail_status'] = $detailStatus;
        $data['note'] = $reason;

		$this->db->where('player_id', $playerId)->where('wallet_type', $walletType);

		# only clear active, to avoid reupdate all. should not affect cancelled/finished wc
		$this->db->where('status', self::STATUS_NORMAL);

		$this->db->where_in('id', $withdrawConditionIds);
		$this->db->set($data);
		// $this->db->update($this->tableName, $data);
		$success =  $this->runAnyUpdate('withdraw_conditions');

		if ($success) {
			$this->load->model(['player_promo']);

			$this->db->select('player_id, player_promo_id')->from('withdraw_conditions')
                    ->where_in('id', $withdrawConditionIds)
					->where('player_promo_id is not null', null, false);

			$rows = $this->runMultipleRowArray();

			if (!empty($rows)) {
				$playerPromoArr = [];
				foreach ($rows as $row) {
					$playerPromoArr[] = $row['player_promo_id'];
				}
				if (!empty($playerPromoArr)) {
					$this->player_promo->finishPlayerPromos($playerPromoArr, $reason);
				}
			}
		}

		return $success;
	}

	/**
	 * overview : check if available withdrawal condition exist
	 *
	 * @param $playerId
	 * @param $walletType
	 * @return bool
	 */
	public function existsAvailWithdrawalConditionByWalletType($playerId, $walletType) {
		$this->db->select('id')
			->from('withdraw_conditions')->where('player_id', $playerId)
			->where('wallet_type', $walletType)
			->where('status', self::STATUS_NORMAL);

		return $this->runExistsResult();

	}

	/**
	 * overvie : create withdrawal condition for manual
	 *
	 * @param int		$player_id
	 * @param int		$bonusTransId
	 * @param double	$withdrawBetAmtCondition
	 * @param double	$deposit_amount
	 * @param double	$bonus_amount
	 * @param int		$bet_times
	 * @param int		$promorule
	 * @return array
	 */
	function createWithdrawConditionForManual($player_id, $bonusTransId,
		$withdrawBetAmtCondition, $deposit_amount, $bonus_amount, $bet_times, $promorule, $reason=null, $player_promo_id = null) {

		$this->load->model(['promorules']);
		$promorulesId = null;
		$isDeposit = false;
        if(is_array($promorule)){
        	$promorulesId = $promorule['promorulesId'];
	    	$isDeposit = $promorule['promoType'] == Promorules::PROMO_TYPE_DEPOSIT;
        }


		$data = array(
			'source_id' => $bonusTransId,
			'source_type' => $isDeposit ? self::SOURCE_DEPOSIT : self::SOURCE_BONUS,//self::SOURCE_NON_DEPOSIT,
			'started_at' => $this->utils->getNowForMysql(),
			'condition_amount' => $withdrawBetAmtCondition,
			'status' => self::STATUS_NORMAL,
			'player_id' => $player_id,
			'deposit_amount' => $deposit_amount,
			'bonus_amount' => $bonus_amount,
			'bet_times' => $bet_times,
			'promotion_id' => $promorulesId,
			'promorules_json' => @$promorule['json_info'],
			'wallet_type' => @$promorule['releaseToSubWallet'],
			'trigger_amount' => $bonus_amount ,
			'note' => $reason,
            'is_deducted_from_calc_cashback' => self::NOT_DEDUCTED_FROM_CALC_CASHBACK,
		);

		if (!empty($player_promo_id)) {
			$data['player_promo_id'] = $player_promo_id;
		}

		return $this->insertRow($data);
	}

	/**
	 * overview : get withdrawl transaction detail
	 *
	 * @param $walletAccountId
	 * @return array|bool
	 */
	public function getWithdrawalTransactionDetail($walletAccountId) {

		$this->load->model(['walletaccount_additional', 'vipsetting']);
		$this->db->select('walletaccount.*,
						   player.email,
						   player.createdOn,
						   player.username AS playerName,
						   playerdetails.*,
						   playeraccount.totalBalanceAmount AS currentBalAmount,
						   playeraccount.currency AS currentBalCurrency,
						   vipsettingcashbackrule.vipLevel,
						   vipsettingcashbackrule.vipLevelName,
						   vipsetting.groupName,
						   banktype.bankTypeId,
						   banktype.bankName,
						   banktype.bank_code AS bankCode,
						   playerbankdetails.bankAccountFullName,
						   playerbankdetails.bankAccountNumber,
						   playerbankdetails.city,
						   playerbankdetails.province,
						   playerbankdetails.branch,
						   playerbankdetails.phone as bankPhone,
						   playerbankdetails.bankAddress,
						   adminusers.username as processedByAdmin,
						   playerbankdetails.customBankName as customBankName,
						   playerbankdetails.playerBankDetailsId as playerBankDetailsId,
						   crypto_withdrawal_order.transfered_crypto
						   ')
			->from('walletaccount')
			->join('playeraccount', 'playeraccount.playerAccountId = walletaccount.playerAccountId')
			->join('player', 'player.playerId = playeraccount.playerId')
			->join('playerdetails', 'playerdetails.playerId = player.playerId', 'left')
			->join('playerlevel', 'playerlevel.playerId = player.playerId', 'left')
			->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = playerlevel.playerGroupId', 'left')
			->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId', 'left')
			->join('localbankwithdrawaldetails', 'localbankwithdrawaldetails.walletAccountId = walletaccount.walletAccountId', 'left')
			->join('playerbankdetails', 'playerbankdetails.playerBankDetailsId = localbankwithdrawaldetails.playerBankDetailsId', 'left')
			->join('adminusers', 'adminusers.userId = walletaccount.processedBy', 'left')
			->join('banktype', 'banktype.bankTypeId = playerbankdetails.bankTypeId', 'left')
			->join('crypto_withdrawal_order', 'crypto_withdrawal_order.wallet_account_id = walletaccount.walletAccountId', 'left');

		$this->db->where('walletaccount.walletAccountId', $walletAccountId);
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			$data = null;
			foreach ($query->result_array() as $row) {
				//format number
				$row['totalBalance'] = $row['currentBalAmount'];
				$row['currentBalAmount'] = $this->utils->formatCurrency($row['currentBalAmount']);
				$row['bankName'] = lang($row['bankName']);
				$row['firstName'] = $row['firstName'] ? ucwords($row['firstName']) : '';
				$row['lastName'] = $row['lastName'] ? ucwords($row['lastName']) : '';
				$row['dwDateTime'] = $row['dwDateTime'];
				$row['createdOn'] = $row['createdOn'];
				$row['processDatetime'] = $row['processDatetime'];
				$row['playerName'] = ucwords($row['playerName']);
				$row['subwalletBalanceAmount'] = $this->getSubWalletBalance($row['playerId']);
				if (!empty($row['subwalletBalanceAmount'])) {
					foreach ($row['subwalletBalanceAmount'] as &$subwallet) {
						$row['totalBalance'] += $subwallet['totalBalanceAmount'];
						$subwallet['totalBalanceAmount'] = $this->utils->formatCurrency($subwallet['totalBalanceAmount']);
					}
				}
				$row['totalBalance'] = $this->utils->formatCurrency($row['totalBalance']);
				$row['cashbackwalletBalanceAmount'] = 0;
				$row['customBankName'] = $row['customBankName'];
				$row['playerBankDetailsId'] = $row['playerBankDetailsId'];
				$row['currentBalCurrency'] = $this->utils->getCurrentCurrency()['currency_code'];

				$row['isCrypto'] = 0;
                $banktype = $this->banktype->getBankTypeById($row['bankTypeId']);
                if($this->utils->isCryptoCurrency($banktype)){
					$row['isCrypto'] = 1;
				}
				$row['transfered_crypto'] = $row['transfered_crypto'];

				// for walletaccount_additional
				$playerId = $row['playerId'];
				$the_walletaccount_additional = $this->walletaccount_additional->getDetailByWalletAccountId($walletAccountId);
				if( ! empty($the_walletaccount_additional) ){ // "&& false" for test
					$assoc = true;
					$row['walletaccount_vip_level_info'] = $this->utils->json_decode_handleErr($the_walletaccount_additional['vip_level_info'], $assoc) ;
				}else{
					$row['walletaccount_vip_level_info'] = $this->vipsetting->getVipGroupLevelInfoByPlayerId($playerId);
				}
				$data[] = $row;
			}
			return $data;
		}
		return null;
	}

	/**
	 * overview : overview :get sub wallet balance
	 *
	 * @param int	$player_id
	 * @return bool|array
	 */
	public function getSubWalletBalance($player_id) {
		$qry = "SELECT totalBalanceAmount AS totalBalanceAmount,typeId FROM playeraccount WHERE TYPE IN ('subwallet') AND playerId = '" . $player_id . "'";
		$query = $this->db->query("$qry");

		if (!$query->result_array()) {
			return false;
		} else {
			return $query->result_array();
		}
	}

	/**
	 * overview : get player cash back wallet balance
	 *
	 * @param  int	$playerId
	 * @return array
	 */
	public function getPlayerCashbackWalletBalance($playerId) {
		$this->db->select('playeraccount.totalBalanceAmount	AS cashbackwalletBalanceAmount
						   ')
			->from('playeraccount')
			->join('player', 'player.playerId = playeraccount.playerId', 'left');

		$this->db->where('playeraccount.type', 'cashbackwallet');
		$this->db->where('playeraccount.typeOfPlayer', 'real');
		$this->db->where('player.playerId', $playerId);

		$query = $this->db->get();
		return $query->result_array();
	}

	public function isAllFinishedPlayerPromotion($playerId) {

		if ($this->utils->isEnabledFeature('check_disable_cashback_by_promotion')) {

			$this->db->select('withdraw_conditions.id')->from('withdraw_conditions')
				->join('promorules', 'withdraw_conditions.promotion_id=promorules.promorulesId')
                ->where('withdraw_conditions.withdraw_condition_type', self::WITHDRAW_CONDITION_TYPE_BETTING)
				->where('withdraw_conditions.status', self::STATUS_NORMAL)
				->where('promorules.disable_cashback_if_not_finish_withdraw_condition', '1')
				->where('withdraw_conditions.playerId', $playerId);

			$rows = $this->runMultipleRowArray();

			return empty($rows);
		}

		return true;
	}

    public function getPlayerByUnfinishedAllWithdrawCondition($player_id = null, $filter_is_deducted_from_calc_cashback_flag = true){
        $disabled_cashback_wc = [];
        $unfinished_all_wc_player = [];
        $this->load->model(['promorules']);

        $this->db->select('withdraw_conditions.id,
                withdraw_conditions.player_id,
                withdraw_conditions.status,
                withdraw_conditions.started_at,
                withdraw_conditions.updated_at,
                withdraw_conditions.detail_status,
                withdraw_conditions.is_finished,
                withdraw_conditions.is_deducted_from_calc_cashback,
                withdraw_conditions.promotion_id,
                promocmssetting.promoCmsSettingId,
                promocmssetting.deleted_flag AS promocms_del_flag,
                promorules.deleted_flag AS promorules_del_flag');

        $this->db->from($this->tableName);
        $this->db->join('promorules', 'promorules.promorulesId = withdraw_conditions.promotion_id');
        $this->db->join('promocmssetting', 'promocmssetting.promoId = promorules.promorulesId');

        //is generate by promo
        $this->db->where('withdraw_conditions.promotion_id is not null', null, false);

        //nither deleted promo manager nor deleted promorule
        $this->db->where('promocmssetting.deleted_flag IS NULL', null, false);
        $this->db->where('promorules.deleted_flag IS NULL', null, false);

        $this->db->where('withdraw_conditions.withdraw_condition_type', self::WITHDRAW_CONDITION_TYPE_BETTING);
        $this->db->where('withdraw_conditions.status', self::STATUS_NORMAL); // status still active

        if($filter_is_deducted_from_calc_cashback_flag){
            //promorule had ticked disable_cashback_if_not_finish_withdraw_condition
            $this->db->where('promorules.disable_cashback_if_not_finish_withdraw_condition', self::TRUE);

            $this->db->where_in('withdraw_conditions.is_deducted_from_calc_cashback', [self::NOT_DEDUCTED_FROM_CALC_CASHBACK, self::TEMP_DEDUCT_FROM_CALC_CASHBACK]);

            //need to format $start_period into mysql time
            $start_period = $this->utils->getConfig('datetime_of_exclude_withdraw_condition_when_calculate_cashback');
            if(!empty($start_period)){
                $this->db->where("withdraw_conditions.started_at >= ", $start_period);
            }

        }

        if(!empty($player_id)){
            $this->db->where('withdraw_conditions.player_id', $player_id);
        }


        $rows = $this->runMultipleRowArray();
        $this->utils->printLastSQL();

        if(!empty($rows)){
            foreach ($rows as $row){
                if($row['is_finished'] == self::FINISHED_WITHDRAW_CONDITIONS_FLAG){
                    $disabled_cashback_wc[$row['player_id']]['finished_wc'][] = $row['id'];
                }

                if($row['is_finished'] == self::UN_FINISHED_WITHDRAW_CONDITIONS_FLAG){
                    $disabled_cashback_wc[$row['player_id']]['un_finished_wc'][] = $row['id'];
                }

                //$disabled_cashback_wc[$row['player_id']]['total_wc'][] = $row['id'];
            }

            $this->utils->debug_log('getPlayerByUnfinishedAllWithdrawCondition disabled_cashback_wc', $disabled_cashback_wc);

            if(empty($disabled_cashback_wc)) {
                $this->utils->debug_log('getPlayerByUnfinishedAllWithdrawCondition', 'no player have unfinished disabled cashback withdraw conditions');
                return $unfinished_all_wc_player;
            }

            foreach ($disabled_cashback_wc as $wc_player => $wc_info){
                /*
                    // all disabled cashback's wc not finished
                    $un_finished_wc_cnt = !empty($wc_info['un_finished_wc']) ? count($wc_info['un_finished_wc']) : 0;
                    $total_wc_cnt = !empty($total_wc) ? count($wc_info['total_wc']) : 0;
                    if($un_finished_wc_cnt == $total_wc_cnt){
                        $unfinished_all_wc_player[] = $wc_player;
                    }
                */

                // at least one disabled cashback's wc not finished
                if(!empty($this->utils->getConfig('exclude_wc_available_bet_after_cancelled_wc'))){
                    if(!empty($wc_info['un_finished_wc'])){
                        $unfinished_all_wc_player[] = $wc_player;
                    }
                }else{
                    $unfinished_all_wc_player[] = $wc_player;
                }

            }

            $this->utils->debug_log('getPlayerByUnfinishedAllWithdrawCondition unfinished_all_wc_player', $unfinished_all_wc_player);
        }

        return $unfinished_all_wc_player;
    }

    public function getPlayerCancelledWithdrawalConditionWithUnFinishedFlag($unfinished_wc_player = null){
        $this->load->model(['promorules']);

        $this->db->select('withdraw_conditions.id,
                withdraw_conditions.player_id,
                withdraw_conditions.started_at,
                withdraw_conditions.updated_at,
                withdraw_conditions.promotion_id,
                withdraw_conditions.detail_status,
                promocmssetting.deleted_flag AS promocms_del_flag,
                promorules.deleted_flag AS promorules_del_flag');
        $this->db->from($this->tableName);

        $this->db->join('promorules', 'promorules.promorulesId = withdraw_conditions.promotion_id');
        $this->db->join('promocmssetting', 'promocmssetting.promoId = promorules.promorulesId');

        //is generate by promo
        $this->db->where('withdraw_conditions.promotion_id is not null', null, false);

        //promorule had ticked disable_cashback_if_not_finish_withdraw_condition
        $this->db->where('promorules.disable_cashback_if_not_finish_withdraw_condition', self::TRUE);

        //nither deleted promo manager nor deleted promorule
        $this->db->where('promocmssetting.deleted_flag IS NULL', null, false);
        $this->db->where('promorules.deleted_flag IS NULL', null, false);

        $this->db->where('withdraw_conditions.withdraw_condition_type', self::WITHDRAW_CONDITION_TYPE_BETTING);
        $this->db->where('withdraw_conditions.is_finished', self::UN_FINISHED_WITHDRAW_CONDITIONS_FLAG);    // still not finished
        $this->db->where_in('withdraw_conditions.is_deducted_from_calc_cashback', [self::NOT_DEDUCTED_FROM_CALC_CASHBACK, self::TEMP_DEDUCT_FROM_CALC_CASHBACK]);

        $this->db->where('withdraw_conditions.status', parent::STATUS_DISABLED); //cancelled status
        $this->db->where_in('withdraw_conditions.detail_status', [self::DETAIL_STATUS_CANCELLED_MANUALLY, self::DETAIL_STATUS_CANCELLED_DUE_TO_SMALL_BALANCE]); //cancelled wc

        if(!empty($unfinished_wc_player)){
            $this->db->where_not_in('withdraw_conditions.player_id', $unfinished_wc_player);
        }

//        if(!empty($playerId)){
//            $this->db->where('withdraw_conditions.player_id', $playerId);
//        }

        $this->db->order_by('withdraw_conditions.id', 'ASC');

        $rows = $this->runMultipleRowArray();
        $this->utils->printLastSQL();

        $playerCancelledMaxRange = [];

        if(!empty($rows)){
            $wc_cancelled_maps = [];
            $promo_game_list = [];

            foreach($rows as $row){
                $wc_cancelled_maps[$row['player_id']][] = ['id' => $row['id'], 'started_at' => $row['started_at'], 'updated_at' => $row['updated_at'], 'promorule_id' => $row['promotion_id']];

                if(!empty($promo_game_list[$row['promotion_id']])){
                    continue;
                }

                //combine all promorules's available game description id
                $gameDescIdArr = $this->promorules->getPlayerGamesKV($row['promotion_id']);
                $promo_game_list[$row['promotion_id']] = $gameDescIdArr;
            }

            $this->utils->debug_log('getPlayerCancelledWithdrawalConditionWithUnFinishedFlag wc_cancelled_maps', $wc_cancelled_maps);

            if(!empty($wc_cancelled_maps)){
                foreach($wc_cancelled_maps as $wc_cancelled_key => $wc_cancelled_value){
                    $game_description_id = [];
                    $player_cancelled_wc = $wc_cancelled_value;

                    $each_started_at = array_column($player_cancelled_wc, 'started_at');
                    $each_updated_at = array_column($player_cancelled_wc, 'updated_at');
                    $each_wc_id = array_column($player_cancelled_wc, 'id');

                    array_multisort($each_started_at,SORT_ASC, $each_updated_at, SORT_DESC, $each_wc_id, SORT_ASC);

                    $each_promorule_id = array_column($player_cancelled_wc, 'promorule_id');
                    foreach($each_promorule_id as $promorule_id){
                        if(!empty($promo_game_list[$promorule_id])){
                            $game_description_id = array_merge($game_description_id, $promo_game_list[$promorule_id]);
                        }
                    }

                    $game_description_id = array_unique($game_description_id);
                    sort($game_description_id);

                    $playerCancelledMaxRange[$wc_cancelled_key] = [
                                  'from_time' => $each_started_at['0'],   // the earliest started_at of all cancelled wc
                                    'to_time' => $each_updated_at['0'],   // last update_at of all cancelled wc
                                      'wc_id' => $each_wc_id,             // all wc id
                        'game_description_id' => $game_description_id     // player's wc all game description id
                    ];
                }
            }
        }

        return $playerCancelledMaxRange;
	}

	/**
	 * detail: get the cancelled withdrawa condition of a certain player
	 *
	 * @param int $player_id
	 * @return array
	 */
	public function getPlayerCancelledWithdrawalCondition($playerId = '', $where, $values) {

		$DepositType = lang('Deposit');
		$NonDepositType = lang('Non Deposit');
		$randomBonus = lang('Random Bonus');

		$select = '(CASE WHEN withdraw_conditions.source_type = ' . Withdraw_condition::SOURCE_DEPOSIT . ' THEN "' . $DepositType . '"
					WHEN withdraw_conditions.source_type = ' . Withdraw_condition::SOURCE_BONUS . ' THEN "' . $randomBonus . '"
					WHEN withdraw_conditions.source_type = ' . Withdraw_condition::SOURCE_NON_DEPOSIT . ' THEN "' . $NonDepositType . '" ELSE promocmssetting.promo_code END) as transaction_type,
					promocmssetting.promoName, promocmssetting.promo_code,
					withdraw_conditions.deposit_amount,
					withdraw_conditions.bonus_amount,
					withdraw_conditions.started_at,
					withdraw_conditions.condition_amount,
					withdraw_conditions.id as wc_id,
					withdraw_conditions.bet_amount,
					withdraw_conditions.note AS notes,
					withdraw_conditions.updated_at,
					withdraw_conditions.stopped_at,
					withdraw_conditions.detail_status,
					adminusers.username,
					transaction_notes.note as cancel_wc_note';

		$this->db->select($select)
			->from($this->tableName)
            ->join('playerpromo', 'playerpromo.playerpromoId = withdraw_conditions.player_promo_id', 'LEFT')
            ->join('promocmssetting', 'promocmssetting.promoCmsSettingId = playerpromo.promoCmsSettingId', 'LEFT')
			->join('adminusers', 'adminusers.userId = withdraw_conditions.admin_user_id', 'LEFT')
			->join('transaction_notes', 'transaction_notes.transaction_id = withdraw_conditions.id', 'LEFT')
			->where('withdraw_conditions.player_id', $playerId);


		if(!empty($where['0']) && !empty($values['0'])){
            $this->db->where($where['0'], $values['0']);
        }

        if(!empty($where['1']) && !empty($values['1'])){
            $this->db->where($where['1'], $values['1']);
        }

        $this->db->where('withdraw_conditions.status', self::STATUS_DISABLED)
                 ->order_by('withdraw_conditions.started_at', 'DESC');

        if($this->config->item('debug_data_table_sql')){
            $sql = $this->db->last_query();
            $this->utils->debug_log('in getPlayerCancelledWithdrawalCondition().$sql:', $sql);
        }

		return $this->runMultipleRowArray();
	} // EOF getPlayerCancelledWithdrawalCondition

	public function getAvailableWithdrawConditionOnlyPromoWithBet($date, $startHour, $endHour, $playerId=null){

		$lastDate = $this->utils->getLastDay($date);

		if (intval($endHour) == 23) {
			//all yesterday
			$date = $lastDate;
		}

		$from = $lastDate . ' ' . $startHour . ':00:00';
		$to = $date . ' ' . $endHour . ':59:59';

		return $this->getAvailableWithdrawConditionOnlyPromoWithBetByTime($from, $to, $playerId);

	}

	public function getAvailableWithdrawConditionOnlyPromoWithBetByTime($timeStart, $timeEnd, $playerId=null) {
		$this->db->select("withdraw_conditions.player_id, sum(withdraw_conditions.condition_amount) as amount, min(withdraw_conditions.started_at) as started_at")
		    ->from('withdraw_conditions')
		    ->join('promorules', 'promorules.promorulesId=withdraw_conditions.promotion_id')
            ->where('withdraw_conditions.withdraw_condition_type', self::WITHDRAW_CONDITION_TYPE_BETTING)
		    ->where('withdraw_conditions.started_at >=', $timeStart)
		    ->where('withdraw_conditions.started_at <=', $timeEnd)
		    ->where('promorules.disable_cashback_if_not_finish_withdraw_condition', 1)
		    ->group_by('withdraw_conditions.player_id');

		if(!empty($playerId)){
			$this->db->where('withdraw_conditions.player_id', $playerId);
		}

		$rows=$this->runMultipleRowArray();

		$this->utils->debug_log('getAvailableWithdrawConditionOnlyPromoWithBetByTime SQL:', $this->db->last_query());
		$this->utils->debug_log('result rows:', $rows);

		$wc_amount_map = [];

		if (!empty($rows)) {
			foreach ($rows as $row) {
				$wc_amount_map[$row['player_id']] = ['amount' => $row['amount'], 'started_at' => $row['started_at']];
			}
		}

		return $wc_amount_map;
	}

	public function getAvailableWithdrawConditionWithBet($date, $startHour, $endHour) {

		$lastDate = $this->utils->getLastDay($date);

		if (intval($endHour) == 23) {
			//all yesterday
			$date = $lastDate;
		}

		$from = $lastDate . ' ' . $startHour . ':00:00';
		$to = $date . ' ' . $endHour . ':59:59';

		return $this->getAvailableWithdrawConditionWithBetByTime($from, $to);

	}

	/**
	 * get available withdraw condition with bet by start time and end time
	 *
	 * @param string $timeStart
	 * @param string $timeEnd
	 * @return array
	 */
	public function getAvailableWithdrawConditionWithBetByTime($timeStart, $timeEnd) {

		$sql = "SELECT player_id, sum(condition_amount) as amount, min(started_at) as started_at FROM  withdraw_conditions WHERE promotion_id is not null and started_at BETWEEN ? AND ? GROUP BY player_id ";

		$data = array($timeStart, $timeEnd);

		$rows = $this->runRawSelectSQLArray($sql, $data);

		$this->utils->debug_log('getAvailableWithdrawConditionWithBetByTime SQL:', $this->db->last_query());
		$this->utils->debug_log('result rows:', $rows);

		$wc_amount_map = [];

		if (!empty($rows)) {
			foreach ($rows as $row) {
				$wc_amount_map[$row['player_id']] = ['amount' => $row['amount'], 'started_at' => $row['started_at']];
			}
		}

		// $query = $this->db->query($sql,$data);

		// $rows = $this->getMultipleRow($query);

		//    //echo $this->db->last_query();
		// $wc_amount_map = array();

		// foreach ($rows as $row) {
		// 	$wc_amount_map[$row->player_id] = $row->amount;
		// }

		return $wc_amount_map;

	}

	public function getDisabledBetFromWithdrawCondition() {

		$types = [Transactions::DEPOSIT, Transactions::WITHDRAWAL, Transactions::ADD_BONUS, Transactions::AUTO_ADD_CASHBACK_TO_BALANCE];
		$types = implode(',', $types);

		//get last transaction
		$sql = "
select transactions.amount, transactions.transaction_type, transactions.created_at, promorules.disable_cashback_if_not_finish_withdraw_condition
from transactions
left join playerpromo on transactions.player_promo_id=playerpromo.playerpromoId
left join promorules on promorules.promorulesId=playerpromo.promorulesId
where transactions.transaction_type in ({$types})
and withdraw_conditions.withdraw_condition_type=" . self::WITHDRAW_CONDITION_TYPE_BETTING . "
and transactions.to_id=?
and transactions.to_type=?
and transactions.created_at<?
order by transactions.created_at desc
limit 1
";

		$lastTransRows = $this->runRawSelectSQLArray($sql, [$playerId, Transactions::PLAYER,
			$this->utils->formatDateTimeForMysql($start)]);

		$lastTransRow = null;
		if (!empty($lastTransRows)) {
			$lastTransRow = $lastTransRows[0];
		}

		if ($lastTransRow) {

		}

	}

	public function updatePlayerPromoId($id, $playerPromoId) {
		$this->db->set('player_promo_id', $playerPromoId)->where('id', $id);
		return $this->runAnyUpdate('withdraw_conditions');
	}

	public function checkIfPlayerHasUnfinishedCondition($playerId) {
		$withdrawalCondition = $this->getPlayerWithdrawalCondition($playerId);

		$hasUnfinished = false;
		if (!empty($withdrawalCondition)) {
			foreach( $withdrawalCondition as $condition) {
                $unfinished = 0;
                if(isset($condition['unfinished'])){
                    $unfinished = $condition['unfinished'];
                }
				if($unfinished > 0) {
					$hasUnfinished = true;
				}
			}
		}
		return $hasUnfinished;
	}

	public function updateStatus($ids, $status) {
		$this->db->set('status', $status)->where_in('id', $ids);
		return $this->runAnyUpdate($this->tableName);
	}

    public function updateDeductFromCalcCashbackFlag($ids, $status) {
        $this->db->set('is_deducted_from_calc_cashback', $status)->where_in('id', $ids);
        return $this->runAnyUpdate($this->tableName);
    }

	public function updateWithdrawConditionBet($bet_amount, $withdraw_condition_id) {
		$this->db->where('id', $withdraw_condition_id);
		$this->db->set(['bet_amount' => $bet_amount]);
		$this->db->update($this->tableName);
	}

	public function getPlayerCancelledAndFinishedCondition($player_id, $start_date, $updated_at, $hourly_update=null) {
		$this->load->model(array('promorules'));

		$this->db->distinct()->select('promorules.promorulesId,
						   promorules.promoName,
						   promocmssetting.promoCmsSettingId,
						   promorules.promoType,
						   promorules.nonDepositPromoType,
						   player.username,
						   player.playerId,
						   playerpromo.playerpromoId,
						   withdraw_conditions.id withdraw_condition_id,
						   withdraw_conditions.started_at,
						   withdraw_conditions.updated_at,
						   withdraw_conditions.stopped_at,
						   withdraw_conditions.bet_amount,
						   withdraw_conditions.source_type,
						   withdraw_conditions.source_id,
						   withdraw_conditions.id as withdrawConditionId,
						   withdraw_conditions.deposit_amount as walletDepositAmount')
				->from('withdraw_conditions')
				->join('playerpromo', 'playerpromo.playerpromoId = withdraw_conditions.player_promo_id', 'left')
				->join('promorules', 'promorules.promorulesId = playerpromo.promorulesId OR promorules.promorulesId = withdraw_conditions.promotion_id', 'left')
				->join('promocmssetting', 'promocmssetting.promoId = promorules.promorulesId', 'left')
				->join('promotype', 'promotype.promotypeId = promorules.promoCategory', 'left')
				->join('player', 'player.playerId = withdraw_conditions.player_id', 'left')
                ->where('withdraw_conditions.withdraw_condition_type', self::WITHDRAW_CONDITION_TYPE_BETTING);

		if(!$hourly_update) {
			$this->db->where('withdraw_conditions.status', self::STATUS_DISABLED);
		}

		// update all if hourly
		if($hourly_update) {
		#	$this->utils->debug_log('hourly update');
		#	$this->db->where('withdraw_conditions.status', self::STATUS_NORMAL);
		}

		if(!empty($start_date)) {
			$this->db->where('withdraw_conditions.started_at >=', $start_date);
		#	$this->db->where('withdraw_conditions.started_at >=', '	2018-01-16 18:20:52');
		#	$this->db->where('withdraw_conditions.updated_at <=', $updated_at);
		}

		if (!empty($player_id)) {
			$this->db->where('withdraw_conditions.player_id', $player_id);
		}

		$this->db->order_by("withdraw_conditions.started_at", "desc");
		$rows = $this->runMultipleRowArray();

		$this->utils->debug_log('sql wc query', $this->utils->printLastSQL());

		if (!empty($rows)) {
			$this->load->model(array('total_player_game_hour', 'group_level', 'game_logs'));
			$wd_ident_ar = [];

			foreach($rows as $row) {
				$current_bet = 0;
				$wd_ident = "{$row['withdrawConditionId']}-{$row['promorulesId']}-{$row['playerpromoId']}-{$row['source_id']}";
				if (array_key_exists($wd_ident, $wd_ident_ar)) {
					continue;
				}
				else {
					$wd_ident_ar[$wd_ident] = 1;
				}

				$gameDescIdArr = $this->group_level->getAllowedGameIdArr($row['playerId']);
				$this->utils->debug_log('gameDescIdArr', count($gameDescIdArr));

				if ($row['promoType'] == Promorules::PROMO_TYPE_NON_DEPOSIT ||
						$row['promoType'] == Promorules::PROMO_TYPE_DEPOSIT) {
					if ($row['promorulesId']) {
						$this->load->model(array('promorules'));
						$gameDescIdArr = $this->promorules->getPlayerGames($row['promorulesId']);
					}
				}

				$current_bet = $this->game_logs->totalPlayerBettingAmountWithLimitByVIP($row['playerId'], $row['started_at'], $row['stopped_at'], $gameDescIdArr);

				$this->utils->debug_log('current bet', $current_bet, 'player id', $row['playerId'], 'DATE WC CONDITION', $row['started_at'], $row['stopped_at']);

				// update withdrawal condition bet
				if (!empty($current_bet)) {
					$this->updateWithdrawConditionBet($current_bet, $row['withdraw_condition_id']);
				}
			}
		} else {
			$this->utils->debug_log('empty data', $start_date, $updated_at);
		}
	}

	public function getPlayerUnfinishedWithdrawCondition($playerId) {
		$player_wc = $this->getPlayerWithdrawalCondition($playerId);

		if(empty($player_wc)){
            $this->utils->debug_log("player [$playerId], doesn't have unfinished withdraw condition");
		    return FALSE;
        }

		$total_bet = 0;
		$total_required_bet = 0;
        foreach( $player_wc as $data ) {
            if($data['withdraw_condition_type'] != self::WITHDRAW_CONDITION_TYPE_BETTING){
                continue;
            }

            if($this->utils->isEnabledFeature('disabled_withdraw_condition_share_betting_amount')){
                $total_bet += $data['currentBet'];
            }else{
                if($total_bet < $data['currentBet'] ) {
                    $total_bet = $data['currentBet'];
                }
            }

            $total_required_bet+= $data['conditionAmount'];
        }

		$unfinished_wc = $total_required_bet - $total_bet;
        $this->utils->debug_log("get_player_unfinished_withdraw_condition => player [$playerId], totalRequiredBet [$total_required_bet], totalPlayerBet [$total_bet], un_finished [$unfinished_wc]");

        if(empty($unfinished_wc) || $unfinished_wc < 0){
            return FALSE;
        }

		return $unfinished_wc;
	}

	public function getPlayerUnfinishedWithdrawConditionForeach($playerId){
        $player_wc = $this->getPlayerWithdrawalCondition($playerId);

        if(empty($player_wc)){
            $this->utils->debug_log("player [$playerId], doesn't have unfinished withdraw condition foreach");
            return FALSE;
        }

        foreach( $player_wc as $data ) {
            if($data['withdraw_condition_type'] != self::WITHDRAW_CONDITION_TYPE_BETTING){
                continue;
            }
            if($data['unfinished'] > 0) {
                $this->utils->debug_log("get_player_unfinished_withdraw_condition_foreach===>", "player", [$playerId], "conditionAmount", $data['conditionAmount'], "currentBet", $data['currentBet'], "un_finished", $data['unfinished']);
                return $data['unfinished'];
            }
        }

        return FALSE;
    }

    public function getPlayerUnfinishedDepositConditionForeach($playerId){
	    $this->load->model('promorules');
        $player_dc = $this->getPlayerDepositConditionInWithdrawalCondition($playerId);

        if(empty($player_dc)){
            $this->utils->debug_log("player [$playerId], doesn't have unfinished deposit condition");
            return FALSE;
        }

        $total_deposit = 0;
        $total_required_deposit = 0;

        foreach( $player_dc as $data ) {
            //if($total_deposit < $data['currentDeposit']) {
            $total_deposit = $data['currentDeposit'];
            //}

            $total_required_deposit = $data['conditionDepositAmount'];

            $unfinished_dc = $total_required_deposit - $total_deposit;
            $unfinished_dc = ($unfinished_dc <= 0) ? 0 : $unfinished_dc;

            if($unfinished_dc > 0) {
                $this->utils->debug_log("get_player_unfinished_withdraw_condition => player [$playerId], totalRequiredBet [$total_required_deposit], totalPlayerBet [$total_deposit], un_finished [$unfinished_dc]");
                return $unfinished_dc;
            }
        }

        return FALSE;
	}

	/**
	 * this will Cancel the withdrawal condition of deleted promotion
	 *
	 *
	 * @return int $row_count the rows updated
	 */
	public function cancelWithdrawalConditionOfDeletedPromotion()
	{
		$this->load->model(['promorules']);

		$promo_rules_id = $this->db->distinct()->select('pr.promorulesId')
								->from('promorules pr')
								->join('promocmssetting pcms','pcms.promoId = pr.promorulesId','left')
								->where('pcms.deleted_flag',1)
								->or_where('pcms.promoId IS NULL',null,false)
								->get();

		$promorules_array = $promo_rules_id->result_array();

		$promorules_count = count($promorules_array);

		if($promorules_count > 0){
			$imploded_pr_id = implode(',',array_column($promorules_array,'promorulesId'));
			$this->CI->utils->debug_log('row counts --------------->>>>>>>>>>>>>>',$promo_rules_id->num_rows);
			$this->CI->utils->debug_log('Promotion ID\'s to be update --------------->>>>>>>>>>>>>>',$imploded_pr_id);

			# updating in withdraw_conditions table
			$data['status'] = parent::STATUS_DISABLED;
			$data['detail_status'] = self::DETAIL_STATUS_CANCELLED_MANUALLY;
			$data['updated_at'] = $this->utils->getNowForMysql();
			$data['stopped_at'] = $this->utils->getNowForMysql();

			$this->db->where_in('promotion_id',explode(',',$imploded_pr_id));
			$this->db->set($data);
			$row_count = $this->runAnyUpdateWithResult('withdraw_conditions');

			$this->CI->utils->debug_log('Updated rows count --------------->>>>>>>>>>>>>>',$row_count);

			$this->utils->printLastSQL();

			return $row_count;
		}

		return $promorules_count;
	}

    function getWithdrawConditionByPromorulesId($promorulesId){
        $this->db->select('*')
            ->from($this->tableName)
            ->where('promotion_id', $promorulesId);

        return $this->runMultipleRowArray();
    }

    // step1 & step2
    function appendPlayerPromoIdOnWithdrawCondition($promorulesId, $promoCmsSettingId){
        $rows = $this->getWithdrawConditionByPromorulesId($promorulesId);
        if(empty($rows)){
            $this->utils->debug_log('empty withdraw condition data', $rows);
            return null;
        }

        $this->load->model(['player_promo']);
        $playerPromo = $this->player_promo->getSpecifyPlayPromo($promorulesId, $promoCmsSettingId);

        if(empty($playerPromo)){
            $this->utils->debug_log('empty player promo data', $playerPromo);
            return null;
        }

        if (!empty($rows) && !empty($playerPromo)) {
            $wcArr = [];
            foreach($rows as $row) {

                if(!is_null($row['player_promo_id'])){
                    $this->utils->debug_log('======================================================= appendPlayerPromoIdOnWithdrawCondition player_promo_id is not null', 'wc_id :'.$row['id'].' / player_promo_id :'. $row['player_promo_id']);
                    continue;
                }

                if(isset($playerPromo[$row['player_id'].'-'.$row['condition_amount']])){
                    $this->utils->debug_log('======================================================= appendPlayerPromoIdOnWithdrawCondition id :' . $row['id'], $playerPromo[$row['player_id'].'-'.$row['condition_amount']]['playerpromoId']);
                    $wcArr[] = $row['id'];
                    $this->updatePlayerPromoId($row['id'], $playerPromo[$row['player_id'].'-'.$row['condition_amount']]['playerpromoId']);
                }

            }

            $this->utils->debug_log('================================== setFinishedToPlayerPromo total wcArr', count($wcArr));
        } else {
            $this->utils->debug_log('other error');
        }
    }

    // step3
    public function setFinishedToPlayerPromo($promorulesId){
        $rows = $this->getWithdrawConditionByPromorulesId($promorulesId);
        $this->load->model(['player_promo']);

        if(empty($rows)){
            $this->utils->debug_log('empty withdraw condition data', $rows);
            return null;
        }

        $playerPromoArr = [];
        foreach($rows as $row){

            if($row['promotion_id'] != $promorulesId){
                $this->utils->debug_log('================================== setFinishedToPlayerPromo promotion_id : ', $row['promotion_id'] . ' / promorulesId : ' . $promorulesId);
                continue;
            }

            if($row['status'] == self::STATUS_DISABLED){
                $playerPromoArr[] = $row['player_promo_id'];
            }else{
                $this->utils->debug_log('================================== setFinishedToPlayerPromo status ' . $row['status'] .' is invalid: ');
            }

        }

        if (!empty($playerPromoArr)) {
            $this->utils->debug_log('================================== setFinishedToPlayerPromo playerPromoArr', $playerPromoArr);
            $this->utils->debug_log('================================== setFinishedToPlayerPromo total playerPromoArr', count($playerPromoArr));
            $this->player_promo->finishPlayerPromos($playerPromoArr, 'Cancelled Manually');
        }
    }

}

/////end of file///////
