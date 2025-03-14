<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

class Balance_check_report extends BaseModel {

	function __construct() {
		parent::__construct();
	}

	protected $tableName = "transactions";

	function getTransactions($request) {
		$this->load->model(array('transactions'));
		$jsonString = key($request);
		$request = json_decode($jsonString, true);
		$playerName = isset($request['player_name']) ? $request['player_name'] : null;
		$playerId = $this->getPlayerIdFromUsername($playerName);
		$request['player_id'] = $playerId;

		$from = isset($request['from_date']) ? (new DateTime($request['from_date']))->format('Y-m-d 00:00:00') : null;
		$to = isset($request['to_date']) ? (new DateTime($request['to_date']))->format('Y-m-d 00:00:00') : null;


		$sql = <<<EOD
		SELECT 
transactions.id AS id,
SUM(CASE
    WHEN transactions.transaction_type IN (5, 11, 12) THEN transactions.amount
    ELSE 0
END) AS subwallet_amount,
SUM(CASE
    WHEN transactions.transaction_type NOT IN (5, 11, 12) THEN transactions.amount
    ELSE 0
END) AS mainwallet_amount,
transactions.amount,
transactions.from_type AS from_type,
transactions.from_id AS from_id,
transactions.to_type AS to_type,
transactions.to_id AS to_id,
sale_orders.secure_id AS order_id,
walletaccount.walletAccountId AS walletAccountId,
transactions.status AS status,
transactions.created_at AS created_at,
transactions.transaction_type AS transaction_type,

(CASE 
	transactions.from_type 
	WHEN 1 THEN fromUser.username 
	WHEN 2 THEN fromPlayer.username 
	ELSE NULL 
	END) AS from_username,

(CASE 
	transactions.to_type 
	WHEN 1 THEN toUser.username 
	WHEN 2 THEN toPlayer.username 
	WHEN 5 THEN to_username 
	ELSE NULL 
	END) AS to_username,

ROUND(transactions.amount, 2) AS amount,
transactions.before_balance AS before_balance,
transactions.after_balance AS after_balance,
external_system.system_code AS subwallet,
external_system.id AS game_platform_id,

transactions.changed_balance AS total_before_balance,
transactions.flag AS flag,
transactions.request_secure_id AS secure_id,
transactions.id AS transaction_id,
transactions.external_transaction_id AS external_transaction_id,
transactions.is_manual_adjustment,
transactions.note AS note,
sale_orders.reason AS Remarks

FROM 
    {$this->tableName}
LEFT JOIN adminusers AS fromUser 
    ON transactions.from_type = 1 AND fromUser.userId = transactions.from_id
LEFT JOIN player AS fromPlayer 
    ON transactions.from_type = 2 AND fromPlayer.playerId = transactions.from_id
LEFT JOIN adminusers AS toUser 
    ON transactions.to_type = 1 AND toUser.userId = transactions.to_id
LEFT JOIN player AS toPlayer 
    ON transactions.to_type = 2 AND toPlayer.playerId = transactions.to_id
LEFT JOIN external_system 
    ON external_system.id = transactions.sub_wallet_id
LEFT JOIN sale_orders 
    ON sale_orders.transaction_id = transactions.id
LEFT JOIN walletaccount 
    ON walletaccount.transaction_id = transactions.id

WHERE 
    transactions.status = 1
    AND transactions.created_at BETWEEN ? AND ?
	AND to_id=?
    AND toPlayer.deleted_at IS NULL
GROUP BY 
    external_system.system_code
ORDER BY 
    created_at DESC
EOD;

		$data = array(
			$from,
			$to,
			$playerId
		);
	
		$query = $this->db->query($sql, $data);
		$rlt = $this->getMultipleRow($query);

		$this->CI->utils->debug_log("balance_check_report @getTransactions raw sql: " , $this->CI->db->last_query());

		$current_player_balance =  $this->getMainWalletBalance($playerId);

		$pending_withdrawal_amount =  $this->getPendingWithdrawalAmount($playerId);

		$manualAdjustmentTransTypes = [
			Transactions::MANUAL_ADD_SEAMLESS_BALANCE,
			Transactions::MANUAL_SUBTRACT_SEAMLESS_BALANCE,
			Transactions::MANUAL_ADD_BALANCE,
			Transactions::MANUAL_SUBTRACT_BALANCE,
			Transactions::MANUAL_ADD_BALANCE_ON_SUB_WALLET,
			Transactions::MANUAL_SUBTRACT_BALANCE_ON_SUB_WALLET,
			Transactions::MANUALLY_DEPOSIT_TO_SUB_WALLET,
			Transactions::MANUALLY_WITHDRAW_FROM_SUB_WALLET,
			Transactions::MANUAL_ADD_SEAMLESS_BALANCE,
			Transactions::MANUAL_SUBTRACT_SEAMLESS_BALANCE,
			Transactions::MANUAL_SUBTRACT_WITHDRAWAL_FEE
		];

		$total_manual_deposit_balance= 0;
		$total_manual_add_balance = 0;
		$total_manual_add_bonus = 0;
		$total_manual_add_cashback = 0;
		$total_manual_add_referral_bonus = 0;
		$total_manual_add_vip_bonus = 0;
		$total_manual_withdraw_balance= 0;
		$total_manual_deduct_balance = 0;
		$total_manual_deduct_bonus = 0;
		$total_manual_withdraw_fee_from_player = 0;
		$total_manual_subtract_withdrawal_fee = 0;
		$total_fund_in = 0;
		$total_fund_transfer_to_mainwallet = 0;
		$total_fund_transfer_to_subwallet = 0;
		$total_game_payout = 0;
		$total_expected_wallet_balance = 0;
		$total_actual_wallet_balance = 0;
		$total_actual_wallet_minus_expected_wallet_amount = 0;
		$total_balance_difference = 0;

		$result['gamelogs'] = $game_logs= $this->getGameHistory($request);
		foreach ($rlt as &$item) {
			$item->subwallet_balance = $this->getSubWalletBalance($playerId, $item->game_platform_id);

			$item->subwallet_mainwallet_difference = $item->balance_difference = $item->subwallet_amount - $item->mainwallet_amount;
			$transaction_game_platform_id = $item->game_platform_id;
			$item->game_payout  = $this->getResultAmountSumBasedOnGamePlatformId($result['gamelogs'] , $transaction_game_platform_id);
			$total_game_payout += $item->game_payout;
			$item->expected_wallet_balance = $item->balance_difference - $item->game_payout;
			$item->actual_wallet_balance = $current_player_balance;
			
			switch($item->transaction_type){
				case Transactions::DEPOSIT:
				case Transactions::MANUALLY_DEPOSIT_TO_SUB_WALLET:
					$total_manual_deposit_balance += $item->amount;
					break;
				
				case Transactions::WITHDRAWAL:
				case Transactions::MANUALLY_WITHDRAW_FROM_SUB_WALLET:
					$total_manual_withdraw_balance += $item->amount;
					break;

				case Transactions::WITHDRAWAL_FEE_FOR_PLAYER:
					$total_manual_withdraw_fee_from_player += $item->amount;
					break;

				case Transactions::MANUAL_SUBTRACT_WITHDRAWAL_FEE:
					$total_manual_subtract_withdrawal_fee += $item->amount;
					break;

				case Transactions::MANUAL_ADD_BALANCE:
				case Transactions::MANUAL_ADD_BALANCE_ON_SUB_WALLET:
					$total_manual_add_balance += $item->amount;
					break;
				case Transactions::MANUAL_SUBTRACT_BALANCE:
				case Transactions::MANUAL_SUBTRACT_BALANCE_ON_SUB_WALLET:
					$total_manual_deduct_balance += $item->amount;
					break;
				
				case Transactions::RANDOM_BONUS:
				case Transactions::ADD_BONUS:
				case Transactions::BIRTHDAY_BONUS:
				case Transactions::ROULETTE_BONUS:
				case Transactions::PLAYER_REFERRED_BONUS:
				case Transactions::QUEST_BONUS:
				case Transactions::TOURNAMENT_BONUS:
				case Transactions::CSV_TYPE_BATCH_ADD_BONUS:
					$total_manual_add_bonus += $item->amount;
					break;
				case Transactions::SUBTRACT_BONUS:
					$total_manual_deduct_bonus += $item->amount;
					break;

				case Transactions::AUTO_ADD_CASHBACK_TO_BALANCE:
				case Transactions::AUTO_ADD_CASHBACK_AFFILIATE:
					$total_manual_add_cashback += $item->amount;
					break;
				

				case Transactions::PLAYER_REFER_BONUS:
				case Transactions::PLAYER_REFERRED_BONUS:
					$total_manual_add_referral_bonus += $item->amount;
					break;
				case Transactions::MEMBER_GROUP_DEPOSIT_BONUS:
					$total_manual_add_vip_bonus += $item->amount;
					break;
				default:
					 break;
			}

			$total_fund_in += $item->subwallet_amount;
			$total_fund_transfer_to_mainwallet += $item->mainwallet_amount;
			$total_fund_transfer_to_subwallet += $item->subwallet_amount;
			$total_expected_wallet_balance += $item->expected_wallet_balance;
			$total_actual_wallet_balance = $current_player_balance;
			$item->fund_in = $item->subwallet_amount;
			$item->actual_wallet_minus_expected_wallet_amount = $current_player_balance - $item->expected_wallet_balance;
			$total_actual_wallet_minus_expected_wallet_amount += $item->actual_wallet_minus_expected_wallet_amount;
			 //exclude manual Adjustments
			if($item->is_manual_adjustment){ 
				unset($item);
			}
		}

		$result['transactions'] = $rlt;
		$game_logs_total_bet_amount = 0;
		$game_logs_total_win_amount = 0;
		$game_logs_total_result_amount = 0;

		foreach($game_logs as $item){
			$game_logs_total_bet_amount+=$item->bet_amount;
			$game_logs_total_win_amount+=$item->win_amount;
			$game_logs_total_result_amount+=$item->result_amount;
		}

		$result['extra'] = [
			'automatic_adjustments' => [
				'total_fund_in' => $total_fund_in,
				'total_fund_transfer_to_mainwallet' => $total_fund_transfer_to_mainwallet,
				'total_fund_transfer_to_subwallet' => $total_fund_transfer_to_subwallet,
				'total_game_payout' => $total_game_payout,
				'total_expected_wallet_balance' => $total_expected_wallet_balance,
				'total_actual_wallet_balance' => $total_actual_wallet_balance,
				'total_balance_difference' => $total_fund_transfer_to_subwallet - $total_fund_transfer_to_mainwallet,
				'total_actual_wallet_minus_expected_wallet_amount' => $total_actual_wallet_minus_expected_wallet_amount,
			],
			'manual_adjustments' => [
				'total_manual_deposit_balance' => $total_manual_deposit_balance,
				'total_manual_add_balance' => $total_manual_add_balance,
				'total_manual_add_bonus' => $total_manual_add_bonus,
				'total_manual_add_cashback' => $total_manual_add_cashback,
				
				'total_manual_add_cashback' => $total_manual_add_cashback,
				'total_manual_add_referral_bonus' => $total_manual_add_referral_bonus,
				'total_manual_add_vip_bonus' => $total_manual_add_vip_bonus,
				'total_manual_fund_in' => array_sum([
					$total_manual_deposit_balance,
					$total_manual_add_balance,
					$total_manual_add_bonus,
					$total_manual_add_cashback,
					$total_manual_add_cashback,
					$total_manual_add_referral_bonus,
					$total_manual_add_vip_bonus
				]),

				'total_manual_deduct_balance' => $total_manual_deduct_balance,
				'total_manual_deduct_bonus' => $total_manual_deduct_bonus,
				'total_manual_withdraw_fee_from_player' => $total_manual_withdraw_fee_from_player,
				'total_manual_withdraw_balance' => $total_manual_withdraw_balance,
				'total_manual_subtract_withdrawal_fee' => $total_manual_subtract_withdrawal_fee,
				'total_manual_fund_out' => array_sum([
					$total_manual_deduct_balance,
					$total_manual_deduct_bonus,
					$total_manual_withdraw_balance,
					$total_manual_withdraw_fee_from_player,
					$total_manual_subtract_withdrawal_fee,
				]),
			]
		];

		$total_fund_in_amount=  $result['extra']['automatic_adjustments']['total_fund_in'] + $result['extra']['manual_adjustments']['total_manual_fund_in'];
		$total_fund_out_amount=  $result['extra']['automatic_adjustments']['total_fund_transfer_to_mainwallet'] + $result['extra']['manual_adjustments']['total_manual_fund_out'];
		
	

		$result['extra']['summary'] = [
			'total_fund_in_fund_out' => $total_fund_in_amount . ' - ' . $total_fund_out_amount,
			'total_fund_transfer_to_subwallet_to_mainwallet' =>  $total_fund_transfer_to_subwallet .' - '. $total_fund_transfer_to_mainwallet,
			'current_player_balance' => $current_player_balance,
			'pending_withdrawal_amount' => $pending_withdrawal_amount
		];

		$result['extra']['gamelogs'] = [
			'game_logs_total_bet_amount' => $game_logs_total_bet_amount,
			'game_logs_total_win_amount' => $game_logs_total_win_amount,
			'game_logs_total_result_amount' => $game_logs_total_result_amount,
		];
	
		return $result;
	}

	public function getGameHistory($request){
		$this->load->model(array('game_logs'));
		$from = isset($request['from_date']) ? (new DateTime($request['from_date']))->format('Y-m-d 00:00:00') : null;
		$to = isset($request['to_date']) ? (new DateTime($request['to_date']))->format('Y-m-d 00:00:00') : null;
		$playerId = isset($request['player_id']) ? $request['player_id'] : null;

		$sql = <<<EOD
SELECT
	external_system.system_name as system_name,
	game_platform_id,
	SUM(bet_amount) as bet_amount,
	SUM(win_amount) as win_amount,
	SUM(result_amount) as result_amount
FROM
	game_logs
LEFT JOIN external_system ON external_system.id=game_logs.game_platform_id
WHERE
	flag = 1 
	and end_at >= ? 
	and end_at <= ?
	and player_id = ?
GROUP BY game_platform_id
ORDER BY
	start_at ASC
EOD;
		$data = array(
			$from,
			$to,
			$playerId
		);
	
		$query = $this->db->query($sql, $data);
		return $this->getMultipleRow($query);
	}

	private function getPendingWithdrawalAmount($playerId){
		$this->load->model(array('game_logs'));
		$from = isset($request['from_date']) ? (new DateTime($request['from_date']))->format('Y-m-d 00:00:00') : null;
		$to = isset($request['to_date']) ? (new DateTime($request['to_date']))->format('Y-m-d 00:00:00') : null;


		$sql = <<<EOD
SELECT
	sum(amount) as pending_withdrawal_amount
FROM
	walletaccount
WHERE
	playerAccountId = ?
	AND dwStatus in ('request','pending_review','checking')
	AND transactionType = 'withdrawal'
EOD;
		$data = array(
			$playerId
		);
	
		$query = $this->db->query($sql, $data);
		$rlt = $this->getMultipleRow($query);
		return isset($rlt[0]->pending_withdrawal_amount) ? $rlt[0]->pending_withdrawal_amount : 0;
	}

	public function getMainWalletBalance($player_id) {
		$this->load->model(['wallet_model']);
		return $bigWallet = $this->wallet_model->getMainWalletTotalNofrozenOnBigWalletByPlayer($player_id);
	}

	public function getSubWalletBalance($player_id, $game_platform_id) {

        $this->load->model(['wallet_model']);
        return $this->wallet_model->getSubWalletTotalNofrozenOnBigWalletByPlayer($player_id, $game_platform_id);
    }

	private function getResultAmountSumBasedOnGamePlatformId($game_logs, $game_platform_id){
		$total_game_payout = 0;
		foreach($game_logs as $item){
			if($item->game_platform_id == $game_platform_id){
				$total_game_payout += $item->result_amount;
			}
		}
		return $total_game_payout;
	}

	private function getPlayerIdFromUsername($playerName) {
        $playerId = null;
        $this->CI->load->model(array('player_model'));
        $playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);
        return $playerId;
    }

}

///END OF FILE///////

