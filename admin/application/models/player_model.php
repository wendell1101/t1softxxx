<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * General behaviors include :
 *
 * * Get player details
 * * Get/insert/update/ players
 * * Check active player
 * * Get player total bets
 * * Check referrals
 * * Get sub wallet accounts
 * * Deposit history
 * * Get pending balance
 * * Update total deposit/total betting amount
 *enablePlayerReferral
 * @category Player Management
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Player_model extends BaseModel {

	const WITHDRAWAL_ENABLED = 1;
	const WITHDRAWAL_DISABLED = 0;

	const REGISTERED_BY_IMPORTER = 'importer';
	const REGISTERED_BY_WEBSITE = 'website';
	const REGISTERED_BY_MASS_ACCOUNT = 'mass_account';
	const REGISTERED_BY_AGENCY_CREATED = 'agency_created';
	const REGISTERED_BY_MOBILE = 'mobile';
    const REGISTERED_BY_PLAYER_CENTER_API = 'player_center_api';

	const BANKTYPE_DEPOSIT = 0;
	const BANKTYPE_WITHDRAW = 1;

	const RESET_PASSWORD = 1;
	const CHANGE_PASSWORD = 2;
	const RESET_PASSWORD_BY_ADMIN = 3;

	// player.blocked
    const BLOCK_STATUS = 1;
    const SUSPENDED_STATUS = 5;
    const SELFEXCLUSION_STATUS = 7;
    const STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT = 8;

    const INT_LANG_ENGLISH = '1';
	const INT_LANG_CHINESE = '2';
	const INT_LANG_INDONESIAN = '3';
	const INT_LANG_VIETNAMESE = '4';
	const INT_LANG_KOREAN = '5';
	const INT_LANG_THAI = '6';

	const PLAYER_ONLINE = 1;
	const PLAYER_OFFLINE = 0;

	const DEFAULT_PLAYERACCOUNT_BATCHPASSWORD = 0;
	const DEFAULT_PLAYERACCOUNT_STATUS = 0;
	const DEFAULT_PLAYERGAME_STATUS = 0;
	const DEFAULT_PLAYERGAME_BLOCKED = 0;

    const NOTE_COMPONENT_SBE = 1;
    const NOTE_COMPONENT_AGENCY = 2;

	const EMAIL_IS_VERIFIED = 1;
    const EMAIL_NOT_VERIFIED = 0;

    const BLOCKED_SOURCE_PLAYER_CENTER_API=1;
    const BLOCKED_SOURCE_PLAYER_CENTER_LOGIN=2;
    const BLOCKED_SOURCE_WWW_LOGIN=3;

	protected $tableName = 'player';
	protected $idField = 'playerId';

	public function __construct() {
		parent::__construct();
        $this->load->model('game_provider_auth');
	}

	/**
	 * overview : will get player main wallet balance
	 *
	 * @param 	int $player_id
	 * @return 	float totalBalanceAmount | 0
	 */
	public function getMainWalletBalance($player_id) {

		$this->load->model(['wallet_model']);
		return $bigWallet = $this->wallet_model->getMainWalletTotalNofrozenOnBigWalletByPlayer($player_id);

		// $this->db->select_sum('totalBalanceAmount', 'totalBalanceAmount');
		// $this->db->from('playeraccount');
		// $this->db->where(['playerId' => $player_id, 'type' => 'wallet']);

		// if ($query = $this->db->get()->row()) {
		// 	return $query->totalBalanceAmount;
		// } else {
		// 	return 0;
		// }
	}

    /**
     * overview : will get player sub wallet balance
     *
     * @param 	int $player_id
     * @return 	float totalBalanceAmount | 0
     */
    public function getSubWalletBalance($player_id, $game_platform_id) {

        $this->load->model(['wallet_model']);
        return $this->wallet_model->getSubWalletTotalNofrozenOnBigWalletByPlayer($player_id, $game_platform_id);
    }

	/**
	 * overview : will get player bonus amount
	 *
	 * @param 	int $playerpromoId
	 * @return 	float bonusAmount | 0
	 */
	public function getPlayerBonusAmount($playerpromoId) {

		$this->db->select('bonusAmount');
		$this->db->from('playerpromo');
		$this->db->where('playerpromoId', $playerpromoId);

		if ($query = $this->db->get()->row()) {
			return $query->bonusAmount;
		} else {
			return 0;
		}

	}

	/**
	 * overview : will set player main wallet new balance amount
	 *
	 * @param array	$transaction
	 * @return array|false
	 *
	 */
	public function updateMainWalletBalance($transaction) {

		$this->load->library(array('transactions_library'));
		$this->load->model(array('wallet_model', 'transactions'));

		try {

			$extra_transaction_type = null;
			if( ! empty($transaction['transaction_type']) ){
				$extra_transaction_type = $transaction['transaction_type'];
			}

			$extra_note = null;
			if( ! empty($transaction['note']) ){
				$extra_note = $transaction['note'];
			}

			if (!isset($transaction['to_id'], $transaction['amount'])) {
				throw new Exception("Required parameters not found", 1);
			}

			$playerId = $transaction['to_id'];
			$amount = $transaction['amount'];
			$userId = $transaction['from_id'];

			# GET BEFORE BALANCE FOR VALIDATION
			$totalBeforeBalance = $this->wallet_model->getTotalBalance($playerId);
			$before_balance = $this->getMainWalletBalance($playerId);

			# GET AFTER BALANCE FOR VALIDATION
			$after_balance = $before_balance + $amount; // $this->getMainWalletBalance($playerId);

			# VALIDATE
			// if (floatval($before_balance + $amount) !== floatval($after_balance)) {
			// 	throw new Exception("before_balance[{$before_balance}] + amount[{$amount}]) != after_balance[{$after_balance}]", 1);
			// }

			# SAVE TO TRANSACTION
			$transaction['before_balance'] = $before_balance;
			$transaction['after_balance'] = $after_balance;
			$transaction['created_at'] = $this->utils->getNowForMysql();
			$transaction['total_before_balance'] = $totalBeforeBalance;
			// $transaction = $this->transactions_library->saveTransaction($transaction);

			$transId = $this->transactions->createPlayerReferralTransaction($playerId, $userId, $amount,
				$totalBeforeBalance, $before_balance, $after_balance, $extra_note, $extra_transaction_type);

			$transaction['id'] = $transId;

			# UPDATE MAIN WALLET
			// if (!$this->wallet_model->incMainWallet($playerId, $amount)) {
			// 	throw new Exception("Update Main Wallet Balance Failed", 1);
			// }

			if (!$transId) {
				throw new Exception("Failed to save transaction in transactions table", 1);
			}

			return $transaction;

		} catch (Exception $e) {
			$transaction = json_encode($transaction, JSON_PRETTY_PRINT);
			log_message('error', "PlayerModel->updateMainWalletBalance({$transaction}): " . $e->getMessage());
			return false;
		}
	}

	/**
	 * overview : will save player withdrawal condition
	 *
	 * @param  array	$conditionData
	 * @return null
	 */
	public function savePlayerWithdrawalCondition($conditionData) {
		$this->db->insert('withdraw_conditions', $conditionData);
	}

	/**
	 * overview : get sub wallet account
	 *
	 * @param 	$playerAccountId
	 * @return 	array
	 */
	public function getSubwalletAccount($playerAccountId) {
		$this->db->from('playeraccount')->limit(1);
		$this->db->join('game', 'game.gameId = playeraccount.typeId', 'left');
		$this->db->where('playeraccount.type', 'subwallet');
		$this->db->where('playeraccount.playerAccountId', $playerAccountId);
		$item = $this->db->get()->row_array();
		return $item;
	}

    public function getSubwalletsByPlayerId($player_id) {
        $this->db->select('pa.currency, pa.typeId as id, game.game as game');
        $this->db->from('game');
        $this->db->join('playeraccount pa', 'game.gameId=pa.typeId and pa.type="subwallet"', 'left');
        $this->db->where('pa.playerId', $player_id);

        $rows = $this->runMultipleRow();
        $result = [];

        if(!empty($rows)){
            foreach ($rows as $row){
                $result[$row->id]['currency'] = $row->currency;
                $result[$row->id]['game'] = $row->game;
            }
        }

        return $result;
    }

	/**
	 * overview : get player id by username
	 *
	 * @param $username
	 * @return null
	 */
	public function getPlayerIdByUsername($username) {
		// $this->utils->debug_log("Player Model getPlayerIdByUsername: ", $username, " Table: ", $this->tableName);
		if (!empty($username)) {
			$this->db->select('playerId');
			$this->db->where('username', $username);
			$qry = $this->db->get($this->tableName);
			return $this->getOneRowOneField($qry, 'playerId');
		}
		return null;
	}

	/**
	 * overview : get player id by email
	 *
	 * @param $email
	 * @return null
	 */
	public function getPlayerIdByEmail($email) {
		// $this->utils->debug_log("Player Model getPlayerIdByEmail: ", $email, " Table: ", $this->tableName);
		if (!empty($email)) {
			$this->db->select('playerId');
			$this->db->where('email', $email);
			$qry = $this->db->get($this->tableName);
			return $this->getOneRowOneField($qry, 'playerId');
		}
		return null;
	}

	/**
	 * overview : get player list
	 *
	 * @return array
	 */
	public function getPlayersList() {
		$this->db->select('playerId,username');
		$qry = $this->db->get($this->tableName);
		return $qry->result_array();
	}

	public function getPlayersListOfUnverifyEmail() {
		$this->db->select('playerId,username')
		->where( 'email <>', '')
		->where('verified_email', 0);
		$qry = $this->db->get($this->tableName);
		return $qry->result_array();
	}

	/**
	 * overview : get username by id
	 *
	 * @param  int	$playerId
	 * @return null|string
	 */
	public function getUsernameById($playerId) {
		if (!empty($playerId)) {
			$this->db->select('username');
			$this->db->where('playerId', $playerId);
			$qry = $this->db->get($this->tableName);
			return $this->getOneRowOneField($qry, 'username');
		}
		return null;
	}

	/**
	 * overview : inc. total deposit count
	 *
	 * @param int	$playerId
	 */
	public function incTotalDepositCount($playerId) {
		$playerId = intval($playerId);
		$this->db->set('total_deposit_count', '(SELECT COUNT(sale_orders.id) FROM sale_orders WHERE sale_orders.player_id = \''.$playerId.'\')', false);
		$this->db->where($this->idField, $playerId);
		$this->db->update($this->tableName);
	}

	/**
	 * overview : inc. approved deposit count
	 *
	 * @param int	$playerId
	 */
	public function incApprovedDepositCount($playerId) {
		$playerId = intval($playerId);

        if($this->utils->getConfig('enable_async_approve_sale_order')){
            $this->db->set('approved_deposit_count', 
            '(SELECT COUNT(sale_orders.id) FROM sale_orders 
             WHERE sale_orders.player_id = \''.$playerId.'\' 
             AND ( sale_orders.status = \''.Sale_order::STATUS_SETTLED.
                '\' OR sale_orders.status = \''.Sale_order::STATUS_QUEUE_APPROVE.
                '\' OR sale_orders.status = \''.Sale_order::STATUS_TRANSFERRING.
                '\'))', false);
        }else{
            $this->db->set('approved_deposit_count', '(SELECT COUNT(sale_orders.id) FROM sale_orders WHERE sale_orders.player_id = \''.$playerId.'\' AND sale_orders.status = \''.Sale_order::STATUS_SETTLED.'\')', false);
        }

		$this->db->where($this->idField, $playerId);
		$this->db->update($this->tableName);
	}

	/**
	 * overview : inc. declined deposit count
	 *
	 * @param int	$playerId
	 */
	public function incDeclinedDepositCount($playerId) {
		$playerId = intval($playerId);
		$this->db->set('declined_deposit_count', '(SELECT COUNT(sale_orders.id) FROM sale_orders WHERE sale_orders.player_id = \''.$playerId.'\' AND sale_orders.status = \''.Sale_order::STATUS_DECLINED.'\')', false);
		$this->db->where($this->idField, $playerId);
		$this->db->update($this->tableName);
	}

	/**
	 * overview : inc. withdraw amount and count
	 *
	 * @param int	$playerId
	 */
	public function updateApprovedWithdrawAmountAndCount($playerId, $approvedWithdrawCount, $approvedWithdrawAmount) {
		$playerData = array('approvedWithdrawCount' => $approvedWithdrawCount, 'approvedWithdrawAmount' => $approvedWithdrawAmount);
		$this->db->update($this->tableName, $playerData, array('playerId' => $playerId));
		return $this->db->affected_rows();
	}

	/**
	 * overview : get player by id
	 *
	 * @param $id
	 * @return array
	 */
	public function getPlayerArrayById($id) {
		return $this->getOneRowArrayById($id);
	}

	/**
	 * overview : get player
	 * cache ?
	 *
	 * @param $id
	 * @return array
	 */
	public function getPlayerById($id) {
		return $this->getOneRowById($id);
	}

	/**
	 * overview : get player information by id
	 * Note: As this method is being used in numerous places, its value is cached locally for efficiency reason
	 *
	 * @param int	$player_id
	 * @return bool|array
	 */
	// public function getPlayerInfoDetailById($player_id, $wallet ='wallet') {
	public function getPlayerInfoDetailById($player_id, $wallet ='wallet') {
			$this->db->select(" p.*, t.*, pd.*, pa.*,
								p.playerId AS playerId,
								p.status AS playerStatus,
								p.createdOn AS playerCreatedOn,
								t.createdOn AS tagCreatedOn,
								t.status AS tagStatus,
								pa.status AS playerAccountStatus,
								vipcbr.vipsettingcashbackruleId,
								vipcbr.vipLevel,
								vipcbr.vipLevelName,
								vipst.groupName,
								pr.lastLoginTime as last_login_time, pr.lastLogoutTime as last_logout_time", false);
			$this->db->from('player as p');
			$this->db->join('playerdetails as pd', 'p.playerId = pd.playerId', 'left');
			$this->db->join('playeraccount as pa', 'p.playerId = pa.playerId', 'left');
			$this->db->join('playertag as pt', 'p.playerId = pt.playerId', 'left');
			$this->db->join('tag as t', 'pt.tagId = t.tagId', 'left');
			$this->db->join('player_runtime AS pr', 'p.playerId = pr.playerId', 'left');
            $this->db->join('playerlevel as pl', 'p.playerId = pl.playerId', 'left');
			$this->db->join('vipsettingcashbackrule as vipcbr', 'vipcbr.vipsettingcashbackruleId = pl.playerGroupId', 'left');
			$this->db->join('vipsetting as vipst', 'vipst.vipSettingId = vipcbr.vipSettingId', 'left');
			$this->db->where('p.playerId', $player_id);
			if(!empty($wallet)) {

				$this->db->where('pa.type', $wallet);
			} else {
				$this->db->group_by('p.playerId');
			}
			$query = $this->db->get();
			if (!$query->row_array()) {
				return false;
			} else {
				return $query->row_array();
			}
	}

	/**
	 * overview : get player details
	 *
	 * @param int	$playerId
	 * @return array
	 */
	public function getPlayerDetailsById($playerId) {
		$this->db->select('playerdetails.firstName,playerdetails.language,playerdetails.lastName,playerdetails.birthdate,playerdetails.contactNumber,playerdetails.dialing_code,player.createdOn, playerdetails.pix_number as cpfNumber,player.email');
		$this->db->where('player.playerId', $playerId);
		$this->db->join('playerdetails', 'playerdetails.playerId = player.playerId');
		$qry = $this->db->get($this->tableName);
		return $this->getOneRow($qry);
	}

	/**
	 * overview : get pending balance by id
	 *
	 * @param  int	$playerId
	 * @return array
	 */
	public function getPendingBalanceById($playerId) {
		$this->load->model(['wallet_model']);
		return $this->wallet_model->getPendingBalanceById($playerId);

		// $this->db->select('frozen');
		// $this->db->where('player.playerId', $playerId);
		// $qry = $this->db->get($this->tableName);
		// return floatval($this->getOneRowOneField($qry, 'frozen'));
	}

	/**
	 * overview : get player pending balance
	 *
	 * @param int	$playerId
	 * @return object
	 */
	public function getPlayerPendingBalance($playerId) {
		$frozen = $this->getPendingBalanceById($playerId);
		return (object) ['frozen' => $frozen];
		// $this->db->select('frozen');
		// $this->db->where('player.playerId', $playerId);
		// $qry = $this->db->get($this->tableName);
		// return $this->getOneRow($qry);
	}

	// const FRIEND_REFER_STATUS_INACTIVE = 0;

	/**
	 * overview : check if disabled referee by is exist
	 *
	 * @param int	$playerId
	 * @return bool
	 */
	public function existsDisabledRefereeBy($playerId) {
		$player = $this->getPlayerById($playerId);
		//exists referee also disabled
		$this->utils->debug_log(self::DEBUG_TAG, $player->refereePlayerId, $player->refereeEnabledStatus);
		return $player && !empty($player->refereePlayerId) && $player->refereeEnabledStatus == self::STATUS_DISABLED;
	}

	/**
	 * overview : create player referral
	 *
	 * @param int	$playerId
	 * @param int	$refereePlayerId
	 * @return array
	 */
	public function createPlayerReferral($playerId, $refereePlayerId) {
		//default is disabled
		//TODO check settings first

		$this->updateRow($playerId, array('refereePlayerId' => $refereePlayerId));
		$invitedPlayerId = $playerId;
		$this->load->model(array('player_friend_referral'));
		return $this->player_friend_referral->syncReferral($refereePlayerId, $invitedPlayerId);
		// $data = array(
		// 	'playerId' => $refereePlayerId,
		// 	'invitedPlayerId' => $invitedPlayerId,
		// 	'referredOn' => $this->getNowForMysql(),
		// 	'status' => self::STATUS_DISABLED,
		// );

		// return $this->insertData('playerfriendreferral', $data);
	}

	/**
	 * overview : enable player referral
	 *
	 * @param int	$playerId
	 */
	public function enablePlayerReferral($playerId) {
		$this->updateRow($playerId, array(
			'refereeEnabledStatus' => self::STATUS_NORMAL,
			'refereeEnabledDatetime' => $this->getNowForMysql(),
		));
	}

	/**
	 * overview : disable player referral
	 *
	 * @param int	$playerId
	 */
	public function disablePlayerReferral($playerId) {
		$this->updateRow($playerId, array('refereeEnabledStatus' => self::STATUS_DISABLED));
	}

	/**
	 * overview : update all players
	 *
	 * @return array
	 */
	public function updateAllPlayersTotalBettingAmount() {
		//update total betting amount from
		//TODO ignore some uncount game
		$this->db->set('totalBettingAmount', '(SELECT ifnull(sum(betting_amount),0) FROM total_player_game_year where total_player_game_year.player_id=player.playerId)', false);
		$this->db->update($this->tableName);
		return $this->db->affected_rows();
	}

	/**
	 * overview : update total deposit amount
	 *
	 * @param $playerId
	 */
	public function updateTotalDepositAmount($playerId) {
		$this->load->model(array('transactions'));
		$this->db->set('totalDepositAmount',
			'(select ifnull(sum(amount),0) from transactions where to_type=' . Transactions::PLAYER . ' and transaction_type=' . Transactions::DEPOSIT .
			' and status=' . Transactions::APPROVED . ' and player.playerId=transactions.to_id)', false);
		$this->db->where('playerId', $playerId);
		$this->db->update($this->tableName);
		return $this->db->affected_rows();
	}

	/**
	 * overview : update all players total deposit amount
	 *
	 * @return array
	 */
	public function updateAllPlayersTotalDepositAmount() {
		$this->load->model(array('transactions'));
		$this->db->set('totalDepositAmount',
			'(select ifnull(sum(amount),0) from transactions where to_type=' . Transactions::PLAYER . ' and transaction_type=' . Transactions::DEPOSIT .
			' and status=' . Transactions::APPROVED . ' and player.playerId=transactions.to_id)', false);
		$this->db->update($this->tableName);
		return $this->db->affected_rows();
	}

	/**
	 * update all players referee
	 */
	public function updateAllPlayersReferee() {
		//FIXME
		// $this->load->model(array('transactions'));
		// $this->db->set('totalDepositAmount',
		// 	'(select ifnull(sum(amount),0) from transactions where to_type=' . Transactions::PLAYER . ' and transaction_type=' . Transactions::DEPOSIT .
		// 	' and status=' . Transactions::APPROVED . ' and player.playerId=transactions.to_id)', false);
		// $this->db->update($this->tableName);
		// return $this->db->affected_rows();
	}

	/**
	 * overview : get player
	 *
	 * @param  array $data
	 * @return array
	 */
	public function getPlayer($data) {
		$this->db->where($data);
		$query = $this->db->get($this->tableName);
		return $query->row_array();
	}

	/**
	 * overview : create player :
	 *
	 * @param  array $player
	 * @return int
	 */
	public function insertPlayer($player) {

		$this->load->library('salt');
		$this->load->model('group_level');

		$player['password'] = $this->salt->encrypt($player['password'], $this->getDeskeyOG());

		$playerId = $this->insertData('player', $player);

		$this->group_level->addPlayerLevel($playerId, $player['levelId']);

		return $playerId;
	}

	/**
	 * overview : update player
	 *
	 * @param int	$id
	 * @param array	$data
	 */
	public function updatePlayer($id, $data) {
		return $this->db->update($this->tableName, $data, array(
			$this->idField => $id,
		));
	}

	/**
	 * overview : insert batch
	 *
	 * @param $data
	 * @return array
	 */
	public function insertBatch($data) {
		$this->db->insert('batch', $data);
		return $this->db->insert_id();
	}

	/**
	 * overview : insert player details
	 *
	 * @param  array $data
	 * @return array
	 */
	public function insertPlayerdetails($data) {
		$this->db->insert('playerdetails', $data);
		return $this->db->insert_id();
	}

	/**
	 * overview : insert player details extra
	 *
	 * @param  array $data
	 * @return array
	 */
	public function insertPlayerDetails_extra($data) {
		$this->db->insert('playerdetails_extra', $data);
		return $this->db->insert_id();
	}

	/**
	 * overview : update player details
	 *
	 * @param int	$id
	 * @param array	$data
	 */
	public function updatePlayerdetails($id, $data) {
		$this->db->update('playerdetails', $data, array(
			'playerId' => $id,
		));
	}

	/**
	 * overview : update players
	 *
	 * @param array	$ids
	 * @param array	$data
	 */
	public function updatePlayers($ids, $data) {
		$this->db->where_in('playerId', $ids);
		$this->db->update($this->tableName, $data);
	}

	/**
	 * overview : array $data
	 *
	 * @param array $data
	 */
	public function insertPlayerAccounts($data) {
		$this->db->insert_batch('playeraccount', $data);
	}

	/**
	 * overview insert player account
	 *
	 * @param array $data
	 */
	public function insertPlayerAccount($data) {
		$this->db->insert('playeraccount', $data);
	}

	/**
	 * overview : insert player games
	 *
	 * @param $data
	 */
	public function insertPlayerGames($data) {
		$this->db->insert_batch('playergame', $data);
	}

	/**
	 * overview : check if username exist
	 *
	 * @param $username
	 * @return int
	 */
	public function usernameExist($username) {
		$this->db->from($this->tableName);
		$this->db->where('username', $username);
		return $this->db->count_all_results();
	}

	/**
	 * overview : get active currency code
	 *
	 * @return array
	 */
	public function getActiveCurrencyCode() {
		$this->load->model(['currencies']);
		return $this->currencies->getActiveCurrencyCode();
	}

	/**
	 * overview : get all players
	 *
	 * @param $sort_by
	 * @param $in
	 * @param $limit
	 * @param $offset
	 * @return array|bool
	 */
	public function getAllPlayers($sort_by, $in, $limit, $offset) {
		// unlock players first
		$this->unlockPlayers();

		$where = '';

		// if ($limit != null) {
		// 	$limit = "LIMIT " . $limit;
		// }

		// if ($offset != null && $offset != 'undefined') {
		// 	$offset = "OFFSET " . $offset;
		// } else {
		// 	$offset = ' ';
		// }

		// if ($sort_by == 'active') {
		// 	$where = " AND p.status = '0'";
		// 	$sort_by = 'username';
		// }

		// if ($sort_by == 'inactive') {
		// 	$where = " AND p.status = '1'";
		// 	$sort_by = 'username';
		// }

		// $query = $this->db->query("SELECT p.*, pd.*,vipcbr.vipLevel,vipcbr.vipLevelName,vipst.groupName, pa.typeOfPlayer, pa.type, pa.typeId, t.tagName, p.status
		// 	FROM player AS p
		// 	LEFT JOIN playeraccount AS pa ON p.playerId = pa.playerId
		// 	LEFT JOIN playerdetails AS pd ON p.playerId = pd.playerId
		// 	LEFT JOIN playertag AS pt ON p.playerId = pt.playerId
		// 	LEFT JOIN playerlevel as pl on pl.playerId = p.playerId
		// 	LEFT JOIN vipsettingcashbackrule as vipcbr on vipcbr.vipsettingcashbackruleId = pl.playerGroupId
		// 	LEFT JOIN vipsetting as vipst on vipst.vipSettingId = vipcbr.vipSettingId
		// 	LEFT JOIN tag AS t ON pt.tagId = t.tagId
		// 	WHERE pa.type = 'wallet' $where
		// ");

		$this->db->select('p.*, pd.*,vipcbr.vipLevel,vipcbr.vipLevelName,vipst.groupName, pa.typeOfPlayer, pa.type, pa.typeId, t.tagName, p.status');
		$this->db->from('player AS p');
		$this->db->join('playeraccount AS pa', 'p.playerId = pa.playerId', 'left');
		$this->db->join('playerdetails AS pd', 'p.playerId = pd.playerId', 'left');
		$this->db->join('playertag AS pt', 'p.playerId = pt.playerId', 'left');
		$this->db->join('playerlevel as pl', 'p.playerId = pl.playerId', 'left');
		$this->db->join('vipsettingcashbackrule as vipcbr', 'vipcbr.vipsettingcashbackruleId = pl.playerGroupId', 'left');
		$this->db->join('vipsetting as vipst', 'vipst.vipSettingId = vipcbr.vipSettingId', 'left');
		$this->db->join('tag AS t', 'pt.tagId = t.tagId', 'left');
		$this->db->where('pa.type', 'wallet');
		$this->ignoreDeleted('p.deleted_at');
		$this->db->limit($limit, $offset);

		if ($sort_by == 'active') {
			$this->db->where('p.status', '0');
		}

		if ($sort_by == 'inactive') {
			$this->db->where('p.status', '1');
		}

		$query = $this->db->get();

		// $this->utils->printLastSQL();

		if (!$query->result_array()) {
			return false;
		} else {
			$result = $query->result_array();
			$return = array();

			foreach ($result as $key => $value) {
				$result_query = $this->db->query("SELECT playerAccountId FROM playeraccount WHERE playerId = '" . $value['playerId'] . "' AND type = 'batch'");

				$res = $result_query->row_array();

				if (!empty($res)) {
					$value['playertype'] = $res['playerAccountId'];
				} else {
					$value['playertype'] = '';
				}

				array_push($return, $value);
			}
			return $return;
		}
	}

	/**
	 * overview : unlock players
	 */
	public function unlockPlayers() {
		// init vars
		$currentDate = date('Y-m-d H:i:s');
		$unlockData = array(
			'status' => 0,
			'lockedStart' => '0',
			'lockedEnd' => '0',
			'updatedOn' => $currentDate,
		);

		$query = <<<EOD
SELECT *
FROM player
WHERE status = 1
AND lockedEnd < '$currentDate'
EOD;

		// query locked players
		$lockedPlayers = $this->db->query($query);

		// check if result record > 0
		if ($lockedPlayers->num_rows > 0) {
			foreach ($lockedPlayers->result() as $lp) {
				// unlock player
				$this->db->where('playerId', $lp->playerId);
				$this->db->update('player', $unlockData);
			}
		}
	}

	/**
	 * searchAllPlayer
	 *
	 * @param 	int
	 * @param 	array
	 * @return 	array
	 */
	const ANY_AMOUNT = 3;
	const LESS_THAN_AMOUNT = 1;
	const GREATER_THAN_AMOUNT = 2;
	const FIRST_DEPOSIT = 1;
	const SECOND_DEPOSIT = 2;
	const NEVER_DEPOSIT = 3;
	const DEPOSITED = 4;
	const APPROVED_DEPOSIT = 5;
	const PLAYERACCOUNT_MAINWALLET = 0;
	const SIMILAR_TYPE = 1;
	const EXACT_TYPE = 2;
	const IS_BLOCKED = 1;

	/**
	 * overview : search all players
	 *
	 * @param $search
	 * @param $sort_by
	 * @param $in
	 * @param $limit
	 * @param $offset
	 * @return bool
	 */
	public function searchAllPlayer($search, $sort_by, $in, $limit, $offset) {

		// for wallet account balance search
		if ( isset($search['wallet_order']) ){
			$this->load->model(array('external_system'));
			$game_platforms = $this->external_system->getAllActiveSytemGameApi();
		}

		$this->db->distinct();
		$this->db->select('player.playerId,
						   player.username,
						   player.email,
						   player_runtime.lastLoginTime,
						   player_runtime.lastLoginIp,
						   player.createdOn,
						   player.registered_by,
						   player.status,
						   player.invitationCode,
						   playeraccount.type,
						   playeraccount.typeId,
						   playeraccount.typeOfPlayer,
						   playerdetails.firstName,
		 				   playerdetails.lastName,
		 				   playerdetails.registrationIP,
		 				   playerdetails.imAccount,
		 				   playerdetails.imAccount2,
		 				   playerdetails.city,
		 				   playerdetails.country,
		 				   vipsettingcashbackrule.vipLevel,
		 				   vipsettingcashbackrule.vipLevelName,
		 				   vipsetting.groupName,
		 				   tag.tagName,
		 				   ', FALSE)
			->from('player');
		$this->db->join('playeraccount', 'playeraccount.playerId = player.playerId', 'left');
		$this->db->join('player_runtime', 'player_runtime.playerId = player.playerId', 'left');
		$this->db->join('playerdetails', 'playerdetails.playerId = player.playerId', 'left');
		$this->db->join('playerlevel', 'playerlevel.playerId = player.playerId', 'left');
		$this->db->join('playerpromo', 'playerpromo.playerId = player.playerId', 'left');
		$this->db->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = playerlevel.playerGroupId', 'left');
		$this->db->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId', 'left');
		$this->db->join('playertag', 'playertag.playerId = player.playerId', 'left');
		// $this->db->join('playergame', 'playergame.playerId = player.playerId', 'left');
		$this->db->join('tag', 'tag.tagId = playertag.tagId', 'left');
		$this->ignoreDeleted('player.deleted_at');

		//username search
		if(isset( $search['search_by'] ) ){
			if ($search['search_by'] == self::EXACT_TYPE) {
				$search['username'] == '' ? '' : $this->db->where('player.username', $search['username']);
			} elseif ($search['search_by'] == self::SIMILAR_TYPE) {
				$search['username'] == '' ? '' : $this->db->like('player.username', $search['username']);
			}
		}


		//signup date search
		if ( isset($search['search_reg_date']) ){
			$search['signup_startdate'] == '' ? '' : $this->db->where('player.createdOn >= ', $search['signup_startdate']);
			$search['signup_enddate'] == '' ? '' : $this->db->where('player.createdOn <= ', $search['signup_enddate']);
		}

		//member details search
		// Patch for Severity: Notice  --> Undefined index: XXXX /home/vagrant/Code/og/admin/application/models/player_model.php
		!isset( $search['firstName'] )? '' : $this->db->where('playerdetails.firstName', $search['firstName']);
		!isset( $search['lastName'] )? '' : $this->db->where('playerdetails.lastName', $search['lastName']);
		!isset( $search['email'] )? '' : $this->db->where('player.email', $search['email']);
		!isset( $search['registrationWebsite'] )? '' : $this->db->where('playerdetails.registrationWebsite', $search['registrationWebsite']);
		!isset( $search['city'] )? '' : $this->db->where('playerdetails.city', $search['city']);
		!isset( $search['country'] )? '' : $this->db->where('playerdetails.country', $search['country']);
		if( isset( $search['imAccount'] ) ){
			$imSearch = "playerdetails.imAccount = '" . $search['imAccount'] . "' OR playerdetails.imAccount2 = '" . $search['imAccount'] . "'";
			$this->db->where($imSearch);
		}
		!isset( $search['status'] )? '' : $this->db->where('player.blocked', $search['status']);
		!isset( $search['registered_by'] )? '' : $this->db->where('player.registered_by', $search['registered_by']);
		!isset( $search['registrationIP'] )? '' : $this->db->where('playerdetails.registrationIP', $search['registrationIP']);
		!isset( $search['tagId'] )? '' : $this->db->where('playertag.playerTagId', $search['tagId']);
		!isset( $search['referral_id'] )? '' : $this->db->where('player.invitationCode', $search['referral_id']);

		//blocked search
		if ( isset($search['blocked']) ){
			$this->db->select('gpa.is_blocked,gpa.game_provider_id,gpa.player_id');
			$this->db->join('(SELECT is_blocked, game_provider_id, player_id FROM game_provider_auth
					    WHERE
					        game_provider_id = ' . $search['blocked'] . '
					    ) as gpa', 'gpa.player_id = player.playerId', 'left');
			$this->db->where('gpa.is_blocked', self::IS_BLOCKED);
		}

		//player level search
		!isset( $search['playerLevel'] )? '' : $this->db->where('playerlevel.playerGroupId', $search['playerLevel']);

		//affiliate search
		if ( isset($search['affiliate']) ){
			$this->db->select('pa.type,pa.playerId,pa.typeId');
			$this->db->join("(SELECT type, playerId,typeId FROM playeraccount WHERE type = 'affiliate') as pa",
				'pa.playerId = player.playerId', 'left');
			$this->db->where('pa.typeId', $search['affiliate']);
		}

		!isset( $search['promoId'] )? '' : $this->db->where('playerpromo.promorulesId', $search['promoId']);
		!isset( $search['account_type'] )? '' : $this->db->where('playeraccount.typeOfPlayer', $search['account_type']);

		//member deposit search
		if ( isset($search['deposit_order']) ){
			if ($search['deposit_order'] == self::FIRST_DEPOSIT || $search['deposit_order'] == self::SECOND_DEPOSIT) {
				$this->db->select('so1.deposit_count,
							   so1.last_deposit_at,
							   so2.amount,');
				$this->db->join('(SELECT player_id, COUNT(*) deposit_count, MAX(created_at) last_deposit_at FROM sale_orders
							WHERE
								status = ' . self::APPROVED_DEPOSIT . '
							GROUP BY
							player_id) as so1', 'so1.player_id = player.playerId', 'left');
				$this->db->join('sale_orders as so2', 'so1.player_id = so2.player_id AND so1.last_deposit_at = so2.created_at', 'left');

				if ($search['deposit_order'] != self::NEVER_DEPOSIT) {
					if ($search['deposit_order'] == self::FIRST_DEPOSIT) {
						$depositCount = 1;
						$this->db->having('deposit_count', $depositCount);
					} elseif ($search['deposit_order'] == self::SECOND_DEPOSIT) {
						$depositCount = 2;
						$this->db->having('deposit_count', $depositCount);
					}

					if ($search['deposit_amount_type'] == self::LESS_THAN_AMOUNT) {
						$this->db->where('so2.amount <', $search['deposit_amount']);
					} elseif ($search['deposit_amount_type'] == self::GREATER_THAN_AMOUNT) {
						$this->db->where('so2.amount >', $search['deposit_amount']);
					}

					$this->db->where('last_deposit_at >=', $search['deposit_date_from']);
					$this->db->where('last_deposit_at <=', $search['deposit_date_to']);
					// $this->db->group_by('player.playerId');
					$this->db->order_by('max(last_deposit_at)', 'asc');
				} else {
					$this->db->where('deposit_count', null);
				}
			} elseif ($search['deposit_order'] == self::DEPOSITED) {
				$this->db->select('sale_orders.amount,sale_orders.status,sale_orders.created_at');
				$this->db->join('sale_orders', 'sale_orders.player_id = player.playerId', 'left');
				if ($search['deposit_amount_type'] == self::LESS_THAN_AMOUNT) {
					$this->db->where('sale_orders.amount <', $search['deposit_amount']);
				} elseif ($search['deposit_amount_type'] == self::GREATER_THAN_AMOUNT) {
					$this->db->where('sale_orders.amount >', $search['deposit_amount']);
				}
				$this->db->where('sale_orders.status', self::APPROVED_DEPOSIT);
				$this->db->where('sale_orders.created_at >=', $search['deposit_date_from']);
				$this->db->where('sale_orders.created_at <=', $search['deposit_date_to']);
			}
		} // EOF if ( isset($search['deposit_order']) ){...

		// wallet account balance search
		/// moved to top of the function. for avoid mix into the $this->db.
		// $this->load->model(array('player_model', 'external_system'));
		// $game_platforms = $this->external_system->getAllActiveSytemGameApi();
		if ( isset($search['wallet_order']) ){
			foreach ($game_platforms as $key) {
				if ($search['wallet_order'] == $key['id']) {
					if ( isset($search['wallet_amount_type']) ){
						if ($search['wallet_amount_type'] == self::LESS_THAN_AMOUNT) {
							$search['wallet_amount'] == '' ? '' : $this->db->where('playeraccount.totalBalanceAmount < ', $search['wallet_amount']);
						} elseif ($search['wallet_amount_type'] == self::GREATER_THAN_AMOUNT) {
							$search['wallet_amount'] == '' ? '' : $this->db->where('playeraccount.totalBalanceAmount >', $search['wallet_amount']);
						}
					}
					$this->db->where('playeraccount.typeId', $key['id']);
					$this->db->where('playeraccount.type', 'subwallet');
				}
			}


			if ($search['wallet_order'] == self::PLAYERACCOUNT_MAINWALLET) {
				if ( isset($search['wallet_amount_type']) ){
					if ($search['wallet_amount_type'] == self::LESS_THAN_AMOUNT) {
						$search['wallet_amount'] == '' ? '' : $this->db->where('playeraccount.totalBalanceAmount < ', $search['wallet_amount']);
					} elseif ($search['wallet_amount_type'] == self::GREATER_THAN_AMOUNT) {
						$search['wallet_amount'] == '' ? '' : $this->db->where('playeraccount.totalBalanceAmount >', $search['wallet_amount']);
					}
				}
				$this->db->where('playeraccount.typeId', self::PLAYERACCOUNT_MAINWALLET);
				$this->db->where('playeraccount.type', 'wallet');
			}
		} // EOF if ( isset($search['wallet_order']) ){...

		$query = $this->db->get();
		// $this->utils->printLastSQL();
		if ($query->num_rows() > 0) {
			// foreach ($query->result_array() as $row) {
			// $row['lastLoginTime'] = mdate('%M %d, %Y %h:%i:%s %A', strtotime($row['lastLoginTime']));
			// $row['createdOn'] = mdate('%M %d, %Y %h:%i:%s %A', strtotime($row['createdOn']));
			// 	$data[] = $row;
			// }
			// return $data;
			return $query->result_array();
		}
		return false;
	}

	/**
	 * overview : get deposit history
	 */
	public function getDepositHistory() {
		$this->db->select('player.playerId,
						   player.username,
						   so1.deposit_count,
						   so1.last_deposit_at,
						   so2.amount
		 				   ')
			->from('player');
		$this->db->join('(SELECT
					        player_id,
					        COUNT(*) deposit_count,
					        MAX(created_at) last_deposit_at
						    FROM
						        sale_orders
						    WHERE
						        status = ' . self::APPROVED_DEPOSIT . '
						    GROUP BY
					        player_id) as so1', 'so1.player_id = player.playerId', 'left');
		$this->db->join('sale_orders as so2', 'so1.player_id = so2.player_id AND so1.last_deposit_at = so2.created_at', 'left');
		$this->db->group_by('player.playerId');
		//$this->db->having('deposit_count', 1);
		// $this->db->where('last_deposit_at <=', '2015-10-10 00:00:00');
		// $this->db->where('last_deposit_at >=', '2015-10-30 00:00:00');
		//$this->db->where('deposit_count', null);
		$this->db->order_by('max(last_deposit_at)', 'asc');
		$query = $this->db->get();
		$data = $query->result_array();
	}

	/**
	 * overview : get player total deposit count
	 *
	 * @param 	int		$player_id
	 * @return 	array
	 */
	public function getPlayerTotalDepositsCnt($player_id) {
		$this->load->model(array('sale_order'));
		//FIXME should use transactions
		$sql = "SELECT COUNT(*) as totalNumberOfDeposit FROM sale_orders WHERE player_id = ? AND status in (" . Sale_order::STATUS_SETTLED . "," . Sale_order::STATUS_BROWSER_CALLBACK . ")";
		$query = $this->db->query($sql, array($player_id));
		return $query->row_array();
	}

	/**
	 * overview : get all player account by player id
	 * @param  int	$player_id
	 * @return array
	 */
	public function getAllPlayerAccountByPlayerId($player_id) {
		$this->db->select('playeraccount.*, game.game as game')
			->from('game')->join('playeraccount', 'game.gameId=playeraccount.typeId and playeraccount.type="subwallet"', 'left')
			->where('playeraccount.playerId', $player_id)->or_where('playeraccount.playerId is null', null, false);

		// $qry = "SELECT *,
		// 	(SELECT g.game FROM game as g where g.gameId = p.typeId) as game
		// 	FROM playeraccount as p where type = 'subwallet' and playerId = ?";
		$query = $this->db->get(); //query($sql, array($player_id));

		// $this->utils->printLastSQL();

		return $query->result_array();
	}

	/**
	 * @param int $player_id
	 * @param array $game_platforms
	 * @return array
	 */
	public function getPlayersSubWalletBalance($player_id = null, $game_platforms = null) {
		if (!$game_platforms) {
			$this->load->model('external_system');
			$game_platforms = $this->external_system->getAllActiveSytemGameApi();
		}
		$this->db->select('playeraccount.playerId');
		$this->db->select('player.username');
		$this->db->select('player.groupName');
		$this->db->select('player.levelName');
		$this->db->select_sum("(CASE WHEN type =  'wallet' AND typeId = 0 THEN totalBalanceAmount ELSE 0 END)", 'main');
		foreach ($game_platforms as $game_platform) {
			$this->db->select_sum('(CASE WHEN playeraccount.typeId = ' . $game_platform['id'] . ' THEN playeraccount.totalBalanceAmount ELSE 0 END)', '"' . strtolower($game_platform['system_code']) . '"');
		}
		$this->db->select_sum('playeraccount.totalBalanceAmount', 'total');
		$this->db->from('playeraccount');
		$this->db->join('player', 'player.playerId = playeraccount.playerId');
		if ($player_id) {
			$this->db->where('player.playerId', $player_id);
		}
		$this->db->where_in('playeraccount.type', array('wallet', 'subwallet'));
		$this->db->group_by('playeraccount.playerId');
		$query = $this->db->get();
		return $player_id ? $query->row_array() : $query->result_array();
	}

	/**
	 * overview : get player sub wallet balance
	 *
	 * @param  int	$player_id
	 * @param  int	$game_platform_id
	 * @return int
	 */
	public function getPlayerSubWalletBalance($player_id, $game_platform_id, $useReadonly = false) {
        $this->load->model(['wallet_model']);
        $seamless_main_wallet_reference_enabled = $this->utils->getConfig('seamless_main_wallet_reference_enabled');
        if($seamless_main_wallet_reference_enabled) {
            $api = $this->utils->loadExternalSystemLibObject($game_platform_id);
            if($api != null && $api->isSeamLessGame()) {
                if($useReadonly){
                    return $this->wallet_model->readonlyMainWalletFromDB($player_id);
                }else{
                    return $this->getMainWalletBalance($player_id);
                }

            }
        }
        if($useReadonly){
            return $this->wallet_model->readonlySubWalletFromDB($player_id, $game_platform_id);
        }else{
            return $this->getSubWalletBalance($player_id, $game_platform_id);
        }

	}

	/**
	 * overview : get member group bonus
	 *
	 * @param	int		$player_id
	 * @return array
	 */
	public function getMemberGroupBonus($player_id) {
		$this->db->select('vipsettingcashbackrule.*')->from('vipsettingcashbackrule');
		$this->db->join('player', 'player.levelId = vipsettingcashbackrule.vipsettingcashbackruleId', 'left');
		$this->db->where('player.playerId', $player_id);
		$query = $this->db->get();
		return $query->row_array();
	}

	/**
	 * overview : local bank withdrawal
	 *
	 * @param array	$walletAccountData
	 * @param array	$localBankWithdrawalDetails
	 * @param int	$playerId
	 * @return int
	 */
	public function localBankWithdrawal($walletAccountData, $localBankWithdrawalDetails, $playerId) {
		// $this->startTrans();

		$result = $this->db->insert('walletaccount', $walletAccountData);

		// if ($result) {
		//insert local bank deposit
		$walletAccountId = $this->db->insert_id();

		# Format of transaction code: Wxxxxxxxx, unique
		$transactionCode = 'W' . sprintf("%'.08d", $walletAccountId) . random_string('numeric', 8);
		$this->db->where('walletAccountId', $walletAccountId);
		$this->db->update('walletaccount', array('transactionCode' => $transactionCode));

		if ($localBankWithdrawalDetails) {
			$localBankWithdrawalDetails['walletAccountId'] = $walletAccountId;
			$this->db->insert('localbankwithdrawaldetails', $localBankWithdrawalDetails);
		}
		// $this->insertLocalBankWithdrawalDetails($localBankWithdrawalDetails);
		//update player  frozen
		// $this->db->select('frozen')
		// 	->from('player')
		// 	->where('playerId', $playerId);
		// $query = $this->db->get();
		// $result_array = $query->result_array();
		// if ($result_array) {
		// 	$record = $result_array[0];
		// 	$frozen = $record['frozen'];
		// 	$newFrozen = $frozen + $walletAccountData['amount'];
		// 	$player_data = array(
		// 		'frozen' => $newFrozen,
		// 	);
		// 	$this->db->where('playerId', $playerId);

		// 	$this->db->update('player', $player_data);
		// }
		// $this->db->set('frozen', 'frozen + ' . $walletAccountData['amount'], false)
		// 	->where('playerId', $playerId)
		// 	->update('player');

		// $this->db->set('totalBalanceAmount', 'totalBalanceAmount - ' . $walletAccountData['amount'], false)
		// 	->where('playerId', $playerId)
		// 	->where('type', 'wallet')
		// 	->update('playeraccount');

		$this->load->model(array('wallet_model'));

		$this->wallet_model->incFrozenOnBigWallet($playerId, $walletAccountData['amount']);

		$this->wallet_model->decMainOnBigWallet($playerId, self::BIG_WALLET_SUB_TYPE_REAL, $walletAccountData['amount']);

		//record balance history
		$this->recordPlayerAfterActionWalletBalanceHistory(Wallet_model::BALANCE_ACTION_WITHDRAW,
			$playerId, null, null, $walletAccountData['amount'], null, null,
			null, $walletAccountId, null);

		// $this->setPlayerNewMainWalletBalAmount($playerId, $walletAccountData['amount'], '-');
		// 	return $result;
		// } else {
		// 	return false;
		// }
		// $this->endTrans();
		// return $this->succInTrans();
		return $walletAccountId;

	}

	/**
	 * overview : get all deposit limit
	 *
	 * @param int		$playerId
	 * @param int		$limit
	 * @param int		$offset
	 * @param string	$search
	 * @param bool|false $isCount
	 * @return null
	 */
	public function getAllDepositsWLimit($playerId, $limit, $offset, $search, $isCount = false) {

		$this->load->model(['sale_order']);

		if ($isCount) {
			$this->db->select('count(so.id) as cnt');
		} else {
			$this->db->select(implode(',', [
				'so.secure_id as id',
				'so.created_at',
				'so.player_deposit_transaction_code',
				'so.payment_flag',
				'so.amount',
				'so.reason',
				'so.show_reason_to_player',
				'so.status',
				'pa.payment_account_name',
				'bt.bankName',
			]));
		}

		if (isset($search['from'], $search['to'])) {
			// $this->db->where("so.created_at BETWEEN '" . $search['from'] . "' AND '" . $search['to'] . "'");

			$this->db->where("so.created_at >=", $search['from']);
			$this->db->where("so.created_at <=", $search['to']);
		}

		if (isset($limit, $offset)) {
			$this->db->limit($limit, $offset);
		}

		$this->db->from('sale_orders so');
		$this->db->join('payment_account pa', 'so.payment_account_id = pa.id', 'left');
		$this->db->join('banktype bt', 'pa.payment_type_id = bt.bankTypeId', 'left');
		$this->db->where('so.player_id', $playerId);
		$this->db->where('so.payment_kind', Sale_order::PAYMENT_KIND_DEPOSIT);
		$this->db->order_by('so.created_at', 'desc');
		$query = $this->db->get();

		if ($isCount) {
			return $this->getOneRowOneField($query, 'cnt');

		} else {
			return $query->result_array();

		}
	}

	public function getAllWithdrawalsWLimit($playerId, $limit, $offset, $search, $isCount = false) {
		//$query = $this->db->query("SELECT * FROM playeraccount as pa inner join walletaccount as wa on pa.playerAccountId = wa.playerAccountId where playerId = '" . $player_id . "' AND transactionType = 'withdrawal' AND type = 'wallet'");

		if ($isCount) {
			$this->db->select('count(walletaccount.walletAccountId) as cnt');
		} else {
			$this->db->select('walletaccount.dwDateTime,
	    					   walletaccount.processDatetime,
	    					   walletaccount.transactionCode,
	    					   walletaccount.dwMethod,
	    					   walletaccount.amount,
	    					   walletaccount.notes,
	    					   walletaccount.showNotesFlag,
	    					   walletaccount.transactionCode,
	    					   walletaccount.transactionType,
	    					   walletaccount.dwStatus,
	    					   walletaccount.is_checking,
							  ');
		}
		$this->db->from('walletaccount')
			->order_by('walletaccount.dwDatetime', 'desc');

		if (isset($limit, $offset)) {
			$this->db->limit($limit, $offset);
		}

		$this->db->where('walletaccount.playerId', $playerId);
		$this->db->where('walletaccount.transactionType', 'withdrawal');
		// $this->db->where('playeraccount.type', 'wallet');

		if (!empty($search['from']) && !empty($search['to'])) {
			$this->db->where("dwDatetime BETWEEN '" . $search['from'] . "' AND '" . $search['to'] . "'");
		}

		$query = $this->db->get();
		if ($isCount) {
			return $this->getOneRowOneField($query, 'cnt');

		} else {
			return $query->result_array();
		}

	}

	/**
	 * overview : get all withdraws limit
	 *
	 * @param int		$playerId
	 * @param int		$limit
	 * @param int		$offset
	 * @param string	$search
	 * @param bool|false $isCount
	 * @return null
	 */
	public function getAllWithdrawsWLimit($playerId, $limit, $offset, $search, $isCount = false) {

		$this->load->model(['sale_order']);

		if ($isCount) {
			$this->db->select('count(walletAccountId) as cnt');
		} else {
			$this->db->select(implode(',', [
				'transactionCode',
				'dwDateTime',
				'dwLocation',
				'dwStatus',
				'amount',
				'walletAccountId',
				'note',
				'showNotesFlag',
			]));
		}

		if (isset($search['from'], $search['to'])) {
			$this->db->where("dwDateTime BETWEEN '" . $search['from'] . "' AND '" . $search['to'] . "'");
		}

		if (isset($limit, $offset)) {
			$this->db->limit($limit, $offset);
		}

		$this->db->from('walletaccount');
		$this->db->join('playeraccount', 'walletaccount.playerAccountId = playeraccount.playerAccountId');
		$this->db->join('transaction_notes', 'walletaccount.walletAccountId = transaction_notes.transaction_id');
		$this->db->where('playeraccount.playerId', $playerId);
		$this->db->where('walletaccount.transactionType', 'withdrawal');
		$this->db->order_by('dwDateTime desc');
		$query = $this->db->get();
		if ($isCount) {
			return $this->getOneRowOneField($query, 'cnt');

		} else {
			return $query->result_array();

		}
	}

	/**
	 * overview : get member total bonus
	 *
	 * @param int	$player_id
	 * @param $bonusType
	 * @return array
	 */
	public function getMemberTotalBonus($player_id, $bonusType) {
		$sql = "SELECT SUM(amount) as totalBonus FROM transactions WHERE to_id = ? AND transaction_type = ?";
		$query = $this->db->query($sql, array($player_id, $bonusType));
		return $query->row_array();
	}

	/**
	 * overview : get player updates
	 *
	 * @param  int	$playerId
	 * @return array
	 */
	public function getPlayerUpdates($playerId) {
		$this->db->select(array(
			'p.*',
			'puh.changes',
			'puh.createdOn',
			'puh.operator',
		));
		$this->db->from('playerupdatehistory puh');
		$this->db->join('player p', 'puh.playerId = p.playerId', 'left');
		$this->db->where('puh.playerId', $playerId);
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * overview : get player referral
	 *
	 * @param  int	$player_id
	 * @return array
	 */
	public function getPlayerReferral($player_id,$request = null) {
		//var_dump($request);die();
		$this->db->select(array(
			'player.username',
			'player.createdOn',
			'SUM(total_player_game_day.betting_amount) totalBettingAmount',
			"transactions.amount",
		));
		$this->db->from('player');
		// $this->db->_protect_identifiers = false;
		$this->db->join('playerfriendreferral referred', 'referred.invitedPlayerId = player.playerId', 'left');
		$this->db->join('total_player_game_day', 'total_player_game_day.player_id = player.playerId', 'left');
		$this->db->join('transactions', 'transactions.to_type = ' . Transactions::PLAYER . ' AND transactions.transaction_type = ' . Transactions::PLAYER_REFER_BONUS . ' AND transactions.status = ' . Transactions::APPROVED . ' AND transactions.id = referred.transactionId', 'left', false);
        $this->db->where('refereePlayerId', $player_id);
		if(!empty($request)){
			$this->db->where($request);
		}

		// $this->db->_protect_identifiers = true;
		// OGP-3434: correction of mis-grouping, which makes the result contains only one row
		$this->db->group_by('player.playerId');
		// $this->db->group_by('player.refereePlayerId');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * Overview : get player referral status
	 *
	 * @param int	$player_id
	 * @param string $where $values
	 * @return array
	 */
	public function getPlayerReferralStatus($player_id, $where = null, $values = null) {
		$this->db->select(array(
			'player.username',
			'player.createdOn',
			'SUM(total_player_game_day.betting_amount) totalBettingAmount',
			"transactions.amount",
		));
		$this->db->from('player');
		$this->db->join('playerfriendreferral referred', 'referred.invitedPlayerId = player.playerId', 'left');
		$this->db->join('total_player_game_day', 'total_player_game_day.player_id = player.playerId', 'left');

        if(!empty($this->utils->getConfig('enabled_friend_referral_promoapp_list'))){
            $this->db->join('transactions', 'transactions.to_type = ' . Transactions::PLAYER . ' AND transactions.transaction_type IN (' . Transactions::PLAYER_REFER_BONUS . ',' . Transactions::ADD_BONUS . ') AND transactions.status = ' . Transactions::APPROVED . ' AND transactions.id = referred.transactionId', 'left', false);
        }else{
            $this->db->join('transactions', 'transactions.to_type = ' . Transactions::PLAYER . ' AND transactions.transaction_type = ' . Transactions::PLAYER_REFER_BONUS . ' AND transactions.status = ' . Transactions::APPROVED . ' AND transactions.id = referred.transactionId', 'left', false);
        }

        $this->db->where('player.refereePlayerId', $player_id);
        $this->db->where($where['0'], $values['0']);
		$this->db->where($where['1'], $values['1']);
		$this->db->group_by('player.playerId');
		$query = $this->db->get();
		$result = $query->result_array();
        $result = json_decode(json_encode($result),true);
        return $result;
	}

	/**
	 * overview : get player registration status
	 *
	 * @param $playerId
	 * @return bool
	 */
	public function getPlayerRegistrationStatus($playerId) {
		$data = $this->getPlayerRegistrationRequirements();

		foreach ($data as $key) {
			if (!$this->isPlayerCompleteProfile($key['alias'], $playerId)) {
				return false; //registration is incomplete
			}
		}
		return true;
	}

	public function getPlayerAccountInfoStatus($playerId) {
        $playerAccountInfoStatus = array('status' => true ,'missing_fields' => '');
        $missing_fields = array();
		$data = $this->getPlayerAccountInfoRequirements();
        $skip_fields = $this->config->item('skip_fields_in_registration_fields');

        $all_player_field_name = $this->utils->getTableAllFieldsName('player');
        $all_playerdetails_field_name = $this->utils->getTableAllFieldsName('playerdetails');

		foreach ($data as $key) {
		    if(!in_array($key['alias'],$skip_fields)) {
                $required_field = $this->isPlayerCompleteProfile($key['alias'], $playerId, $all_player_field_name, $all_playerdetails_field_name);
                if (!$required_field['exist_status']) {
                    $playerAccountInfoStatus['status'] = $required_field['exist_status'];
                    array_push($missing_fields,key($required_field['field'])); //registration is incomplete
                }
            }
		}
        $playerAccountInfoStatus['missing_fields'] = $missing_fields;
		return $playerAccountInfoStatus;
	}

	public function getPlayerAccountInfoRequirements() {
		$this->db->select('alias')->from('registration_fields');
		$this->db->where('type', '1'); //player
		$this->db->where('account_required', '0'); //required
		$this->db->where('alias !=', '');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * overview : will get player registration requirements
	 *
	 * @return  bool
	 */
	public function getPlayerRegistrationRequirements() {
		$this->db->select('alias')->from('registration_fields');
		$this->db->where('type', '1'); //player
		$this->db->where('visible', '0'); //required
		$this->db->where('alias !=', ''); // OGP-2047
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * Will get if player complete profile
	 *
	 * @param   string	$field str
	 * @param   int		$playerId int
	 * @return  bool
	 */
	public function isPlayerCompleteProfile($field, $playerId, $all_player_field_name, $all_playerdetails_field_name) {
        $result = array("exist_status" => false ,"field" => "");
		if ($field != 'secretQuestion' || $field != 'secretAnswer') {
		    if(isset($all_playerdetails_field_name[$field])){
                $this->db->select($field)->from('playerdetails');
                $this->db->join('player', 'player.playerId = playerdetails.playerId', 'left');
            }else{
		        if(isset($all_player_field_name[$field])){
		            // cause email field is in player, not playerdetails
                    $this->db->select($field)->from('player');
                }else{
                    $this->utils->debug_log('isPlayerCompleteProfile field not exist in player details', $field);
                    return ["exist_status" => true];
                }
            }
		} else {
		    if(isset($all_player_field_name[$field])){
                $this->db->select($field)->from('player');
            }else{
                $this->utils->debug_log('isPlayerCompleteProfile field not exist in player', $field);
                return ["exist_status" => true];
            }

		}

		$this->db->where('player.playerId', $playerId); //player
		$query = $this->db->get();

        $result['exist_status'] = (empty($query->row_array()[$field])) ? false : true ;
        $result['field'] = $this->getOneRow($query);

		return $result;
	}

	/**
	 * overview : get player profile progress
	 *
	 * @param  string	$fields
	 * @param  int		$player_id
	 * @return array
	 */	
	public function getPlayerProfileProgres($fields, $player_id) {

		// remove "sms_verification_code" in fields
		$needle = 'sms_verification_code';
		if( strpos($fields, $needle) !== false ){
			$explode = explode(',', $fields);
			$explode = array_filter($explode, function($v, $k) use ($needle) {
				return strpos($v, $needle) === false ;
			}, ARRAY_FILTER_USE_BOTH);
			$fields = implode(',', $explode); // hotfix in live.
		}


		$query = $this->db->query(
			"SELECT
				$fields
			FROM playerdetails as a
			LEFT JOIN player b
				ON a.playerId = b.playerId
            LEFT JOIN playerdetails_extra
                ON a.playerId = playerdetails_extra.playerId
			LEFT JOIN playerbankdetails c
				ON a.playerId = c.playerId AND c.status = 0
			LEFT JOIN banktype d
				ON c.bankTypeId = d.bankTypeId
			WHERE a.playerId = $player_id"
		);

		if (!$query->row_array()) {
			return false;
		} else {
			return $query->row_array();
		}
	}

	/**
	 * Will get player register date with given the Id
	 *
	 * @param 	int
	 * @return	array
	 */
	public function getPlayerRegisterDate($playerId) {
		$this->db->select('createdOn')->from('player');
		$this->db->where('player.playerId', $playerId);
		// $query = $this->db->get();
		return $this->runOneRowOneField('createdOn');
		// return $query->row_array();
	}

	/**
	 * overview : update email verified flag
	 *
	 * @param  int	$playerId
	 * @return bool
	 */
	public function verifyEmail($playerId) {
		$this->db->where('playerId', $playerId);

		return $this->runUpdate(array('verified_email' => self::DB_TRUE,
			'active' => self::OLD_STATUS_ACTIVE));
	}

	/**
	 * Check the field, blockedUntil is Expired
	 * So far,
	 * At expired , that is not includes other abnormal status, ex:"STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT".
	 *
	 * @param integer $playerId The field, "player.playerId".
	 * @param string $now For the param in new DateTime()
	 * @param boolean $toUpdateBlockedUntilIfExpired If its expired, then update the fields, blockedUntil and blocked.
	 * @return array $return The format as the followings,
	 * - $return['isBlocked'] bool If the value is be true, thats means the field,"player.blocked" is equile to BLOCK_STATUS, otherwise false.
	 * - $return['isExpired'] bool If the value is be true, thats means the field,"player.blockedUntil" had expired.
	 */
	public function isBlockedUntilExpired($playerId, $now = 'now', $toUpdateBlockedUntilIfExpired = true){

		$db = $this->db;

		$enabled_block_player_account_with_until = $this->utils->getConfig('enabled_block_player_account_with_until');

		$return = [];
		$return['isBlocked'] = null;
		$return['isExpired'] = null;
		$isBlocked = null;
		$isExpired = null;
		$nowDT = new DateTime($now);

		$db->select('blockedUntil');
		$db->select('blocked');
		$db->from($this->tableName);
		$db->where('playerId', $playerId);
		$row = $this->runOneRowArray($db);

		// for $isBlocked
		$inBlockedStatusList =[];
		$inBlockedStatusList[] = self::BLOCK_STATUS;
		$inBlockedStatusList[] = self::SUSPENDED_STATUS;
		if( in_array( $row['blocked'], $inBlockedStatusList) ){
			$isBlocked = true;
		}else{
			$isBlocked = false;
		}

		/// for $isBlockedUntilExpired,
		// its only check the field, blockedUntil is Expired
		$isBlockedUntilExpired = null;
		if( $row['blockedUntil'] > 0 ){
			if( $nowDT->getTimestamp() >= $row['blockedUntil'] ){
				$isBlockedUntilExpired = true;
			}else{
				$isBlockedUntilExpired = false;
			}
		}else{
			// default assign.
		}

		// for $isExpired
		if( $isBlocked ){
			if( $row['blockedUntil'] > 0 ){
				if( $nowDT->getTimestamp() >= $row['blockedUntil'] ){
					$isExpired = true;
				}else{
					$isExpired = false;
				}
			}else{
				// blocked=BLOCK_STATUS and blockedUntil=0, it's means the player had blocked forever.
				$isExpired = false; // blocked forever
			}
		}else{
			// blocked=DB_FALSE, it's means the player is Not blocked.
			$isExpired = false;
		}

		$isUpdatedPlayer = false;
		if( $toUpdateBlockedUntilIfExpired
			&& ! empty($enabled_block_player_account_with_until) // to unblock by over block time, its must be working in enabled the setting.
		){
			$updateData = [];
			if( $isBlocked
				&& $isExpired
			){
				$updateData['blocked_status_last_update'] = $this->utils->getNowForMysql();
				$updateData['blockedUntil'] = 0;
				$updateData['blocked'] = self::DB_FALSE;
				// $this->unblockPlayerWithGame($playerId);
			}else if( ! $isBlocked ){
				if( ! empty($isBlockedUntilExpired) ){
					$updateData['blockedUntil'] = 0;
				}
			}
			if( ! empty($updateData) ){
				$this->updatePlayer($playerId, $updateData);
				$isUpdatedPlayer = true;
			}
		} // EOF if( $toUpdateBlockedUntilIfExpired ){...

		if($isUpdatedPlayer){
			$db->select('blockedUntil');
			$db->select('blocked');
			$db->from($this->tableName);
			$db->where('playerId', $playerId);
			$row = $this->runOneRowArray($db);
		}


		$return['isBlocked'] = $isBlocked;
		$return['isExpired'] = $isExpired;
		$return['row'] = $row;
		return $return;
	} // EOF isBlockedUntilExpired

	/**
	 * overview : user email
	 *
	 * @param string	$username
	 * @param string	$email
	 * @return array
	 */
	public function isUserEmailExist($username, $email) {

		$qry = $this->db->get_where($this->tableName, array('email' => $email, 'username' => $username));

		return $this->getOneRow($qry);

	}

	/**
	 * overview : check if email is verified
	 * @param $playerId
	 * @return bool
	 */
	public function isVerifiedEmail($playerId) {
		$this->db->select('verified_email')->from($this->tableName)->where('playerId', $playerId);
		$verified_email = $this->runOneRowOneField('verified_email');

		return $verified_email == self::DB_TRUE;
	}

	public function isVerifiedInputEmail($playerId, $email) {
		$this->db->select('verified_email')->from($this->tableName)->where('playerId', $playerId)->where('email', $email);
		$verified_email = $this->runOneRowOneField('verified_email');

		return $verified_email == self::DB_TRUE;
	}

	public function isFilledBirthdate($playerId){
        $player = $this->getPlayerInfoDetailById($playerId);
        $filledBirthdate = (!empty($player['birthdate']) && ($player['birthdate'] != '1970-01-01')) ? true : false;
        return $filledBirthdate;
    }

    public function getBitrhdayPlayers($momth = null, $day = null, $player_id = null){
        $this->db->select('playerdetails.playerId,playerdetails.birthdate')->from('playerdetails');
        if(!empty($player_id)){
        	$this->db->where('playerdetails.playerId', $player_id);
        }

        if(!empty($momth)){
        	$this->db->where("DATE_FORMAT(playerdetails.birthdate, '%c') = ", (int)$momth);
        }

        if(!empty($day)){
        	$this->db->where("DATE_FORMAT(playerdetails.birthdate, '%e') = ", (int)$day);
        }

		return $this->runMultipleRowArray();
    }

	public function isFilledCPFnumber($playerId){
        $player = $this->getPlayerInfoDetailById($playerId);
        $filledCPF = (!empty($player['pix_number'])) ? true : false;
        return $filledCPF;
    }

	/**
	 * overview : check if username is block
	 *
	 * @param  int	$playerId
	 * @return bool
	 */
	public function isBlocked($playerId) {
		$this->db->select('blocked')->from($this->tableName)->where('playerId', $playerId);
		$blocked = $this->runOneRowOneField('blocked');
		// $this->utils->debug_log('blocked', $blocked, 'playerId', $playerId);
		return $blocked == '1';
	}

    public function isSelfExclusion($playerId){
        $playerId = intval($playerId);
        if($playerId==0){
            return false;
        }
        $status_selfExclusion = self::SELFEXCLUSION_STATUS;
        $this->load->library(array('utils'));
        $status =  $this->utils->getPlayerStatus($playerId);
        $this->utils->debug_log(__METHOD__, 'status', $status);
        if($status ==$status_selfExclusion){
            return true;
        }else{
            return false;
        }
    }

    public function isCooloff($playerId){
        $playerId = intval($playerId);
        if($playerId==0){
            return false;
        }
        $this->load->model(array('responsible_gaming'));
        $coolOff = self::SELFEXCLUSION_STATUS;
        $status =  $this->responsible_gaming->chkCoolOff($playerId);
        if($status ==$coolOff){
            return true;
        }else{
            return false;
        }

    }
	/**
	 * overview : check if username is deleted
	 *
	 * @param  int	$playerId
	 * @return bool
	 */
	public function isDeleted($playerId) {
		$this->db->select('deleted_at')->from($this->tableName)->where('playerId', $playerId);
		$deleted = $this->runOneRowOneField('deleted_at');
		return !empty($deleted);
	}

	/**
	 * overview : check if username is close
	 *
	 * @param  int	$playerId
	 * @return bool
	 */

	public function isClosed($playerId) {
		$this->db->select('blockedUntil')->from($this->tableName)->where('playerId', $playerId);
		$blockedUntil = $this->runOneRowOneField('blockedUntil');
		return $blockedUntil > 0;
	}

	/**
	 * overview : validate username and password
	 *
	 * @param string	$username
	 * @param string	$password
	 * @param string	 $message
	 * @return bool
	 */
	public function validateUsernamePassword($username, $password, &$message = null) {
		$success = true;
		$message = '';
		$player_validator = $this->utils->getConfig('player_validator');

		$usernameValidator = $player_validator['username'];
		$passwordValidator = $player_validator['password'];

		if (strlen($username) < $usernameValidator['min']) {
			$message .= 'username min ' . strlen($username) . '<' . $usernameValidator['min'] . "\n";
			$success = false;
		}

		if (strlen($username) > $usernameValidator['max']) {
			$message .= 'username max ' . strlen($username) . '>' . $usernameValidator['max'] . "\n";
			$success = false;
		}

		if (strlen($password) < $passwordValidator['min']) {
			$message .= 'password min ' . strlen($password) . '<' . $passwordValidator['min'] . "\n";
			$success = false;
		}

		if (strlen($password) > $passwordValidator['max']) {
			$message .= 'password max ' . strlen($password) . '>' . $passwordValidator['max'] . "\n";
			$success = false;
		}

		return $success;
	}

	/**
	 * Import player from external source.
	 * Updated 20161027 for function import_v8.
	 *
	 * @param int		$externalId
	 * @param int		$levelId
	 * @param string	$username
	 * @param string	$password
	 * @param double 	$balance
	 * @param array		$extra
	 * @param string	$details
	 * @param string	$message
	 * @return int
	 */
	public function importPlayer($externalId, $levelId, $username, $password, $balance, $extra = null, $details = null, &$message = null) {

		$this->load->library(array('salt'));
		$this->load->model(array('wallet_model', 'group_level', 'game_provider_auth', 'http_request'));

		if (empty($username)) {
			$message = "Empty username: [$username]";
			return false;
		}
		if(empty($password)){
			$password='';
		}

		// $this->db->select('playerId')->from('player')->where('username', $username)->where('external_id !=', $externalId);
		// $playerId = $this->runOneRowOneField('playerId');
		// if ($playerId) {
		// 	$this->utils->debug_log('duplicate username', $username, $playerId);
		// 	$message = "Duplicate username: [$username]";
		// 	return false;
		// }

		# Basic player fields
		$data = array(
			'username' => $username,
			'gameName' => $username,
			'password' => empty($password) ? '' : $this->salt->encrypt($password, $this->getDeskeyOG()),
			'active' => self::OLD_STATUS_ACTIVE,
			'blocked' => self::DB_FALSE,
			'status' => self::OLD_STATUS_ACTIVE,
			'registered_by' => self::REGISTERED_BY_IMPORTER,
			'enabled_withdrawal' => self::DB_TRUE,
			'levelId' => $levelId,
			'external_id' => $externalId,
			'codepass' => $password,
		);

		$data = array_merge($data, $extra);

		$this->db->select('playerId')->from('player')->where('username', $username);
		$playerId = $this->runOneRowOneField('playerId');

		$exists=false;
		# Create / Update the player record
		if (!empty($playerId)) {
			$exists=true;
			$this->db->set($data)->where('playerId', $playerId)->update('player');
		} else {
			$exists=false;
			$this->db->set($data)->insert('player');
			$playerId = $this->db->insert_id();
		}

		# Player level
		$this->group_level->adjustPlayerLevel($playerId, $levelId);

		# Playerdetails fields
		$this->db->select('playerDetailsId')->from('playerdetails')->where('playerId', $playerId);
		$playerDetailsId = $this->runOneRowOneField('playerDetailsId');
		$data = $details;
		$data['playerId'] = $playerId;
		if (!empty($playerDetailsId)) {
			$this->db->set($data)->where('playerDetailsId', $playerDetailsId)->update('playerdetails');
		} else {
			$this->db->set($data)->insert('playerdetails');
		}

		if(!$exists){

			if(!empty($extra['lastLoginIp'])){
				$http_request=[
					'playerId'=>$playerId,
					'ip'=>$extra['lastLoginIp'],
					'createdat'=>$extra['createdOn'],
					'type'=>Http_request::TYPE_LAST_LOGIN, //login
				];

				$http_request_id=$this->http_request->insertHttpRequest($http_request);
			}
			if(!empty($details['registrationIP'])){
				$http_request=[
					'playerId'=>$playerId,
					'ip'=>$details['registrationIP'],
					'createdat'=>$extra['createdOn'],
					'type'=>Http_request::TYPE_REGISTRATION, //reg
				];

				$http_request_id=$this->http_request->insertHttpRequest($http_request);
			}

			if(!empty($extra['lastLoginIp'])){

				$last_request=[
					'player_id'=>$playerId,
					'ip'=>$extra['lastLoginIp'],
					'last_datetime'=>$extra['createdOn'],
					'http_request_id'=>$http_request_id,
				];

				$this->http_request->insertData('player_ip_last_request', $last_request);
			}else{
				//convert ip to http_request and player_ip_last_request
				if(!empty($details['registrationIP'])){

					$last_request=[
						'player_id'=>$playerId,
						'ip'=>$details['registrationIP'],
						'last_datetime'=>$extra['createdOn'],
						'http_request_id'=>$http_request_id,
					];

					$this->http_request->insertData('player_ip_last_request', $last_request);
				}
			}

			//admin is 1
			$notes='import player '.$username.', balance: '.$balance.', level: '.$levelId.' at '.$this->utils->getNowForMysql();
			$this->addPlayerNote($playerId, 1, $notes);

			$currency = $this->getActiveCurrencyCode();
			$this->wallet_model->syncAllWallet($playerId, $balance, $currency);
		}
		// $this->game_provider_auth->syncAllGamePlatform($playerId, $username, $password);

		return $playerId;
	}

	/**
	 * overview : Checks if tagname exist
	 *
	 * @param  array
	 * @return int tagId
	 */
	public function getTagIdByTagName($tagName) {
		$this->db->select("tagId");
		$this->db->from("tag");
		$this->db->where('tagName', $tagName);
		return $this->runOneRowOneField('tagId');
	}

	/**
	 * overview : get tagName by tadId
	 *
	 * @param  array
	 * @return int tagId
	 */
	public function getTagNameByTagId($tagId) {
		$this->db->select("tagName");
		$this->db->from("tag");
		$this->db->where('tagId', $tagId);
		return $this->runOneRowOneField('tagName');
	}

	/**
	 * overview : insert  tag
	 *
	 * @param  array
	 * @return int tagId
	 */
	public function insertNewTag($data) {
		$this->db->insert('tag', $data);
		return $this->db->insert_id();
	}
	/**
	 * overview : insert player tag
	 *
	 * @param  array
	 * @return int tagId
	 */
	public function insertPlayerTag($data) {
		$this->db->insert('playertag', $data);
		return $this->db->insert_id();
	}
	/**
	 * overview : update  tag
	 *
	 * @param  array
	 * @return bool
	 */
	public function updateTag($data,$tagId) {
		$this->db->where('tagId', $tagId);
		return $this->db->update('tag', $data);
	}
	/**
	 * overview : update player tag
	 *
	 * @param  array
	 * @return bool
	 */
	public function updatePlayerTag($data,$playerId,$tagId) {
		$this->db->where('playerId', $playerId);
		$this->db->where('tagId', $tagId);
		return $this->db->update('playertag', $data);
	}

	/**
	 * overview : get all import players
	 *
	 * @return array
	 */
	public function getAllImportPlayers() {
		$this->db->select('playerId, username, codepass, password')->from('player')
			->where('registered_by', self::REGISTERED_BY_IMPORTER)
			->where('external_id >', '0');

		return $this->runMultipleRow();
	}

	/**
	 * overview : update affiliate id
	 *
	 * @param int	$playerId
	 * @param int	$affiliateId
	 */
	public function updateAffiliateId($playerId, $affiliateId) {
		$this->db->set('affiliateId', $affiliateId)->where('playerId', $playerId)
			->update('player');
	}

	/**
	 * overview : remove player affiliate id
	 *
	 * @param int	$playerId
	 * @param int	$affiliateId
	 */
	public function removeAffiliateId($playerId) {
		$this->db->set('affiliateId', null)->where('playerId', $playerId)
			->update('player');
	}

	/**
	 * overview : get player current default bank account
	 *
	 * @param int	$playerId
	 * @param int 	$type
	 * @return string
	 */
	public function getPlayerCurrentDefaultBankAccount($playerId, $type = self::BANKTYPE_DEPOSIT) {
		$this->db->select('playerBankDetailsId')->from('playerbankdetails')->where('isDefault', '1');
		$this->db->where('dwBank', $type);
		$this->db->where('playerId', $playerId);
		$return = $this->runOneRowOneField('playerBankDetailsId');

		return $return;
	}

	/**
	 * overview : get player bank acount name
	 *
	 * @param int	$playerBankDetailsId
	 * @return string
	 */
	public function getPlayerBankAccountName($playerBankDetailsId) {
		$this->db->select('bankAccountFullName')->from('playerbankdetails')->where('playerBankDetailsId', $playerBankDetailsId);
		return $this->runOneRowOneField('bankAccountFullName');
	}

	/**
	 * overview : get password by username
	 *
	 * @param  string	$username
	 * @return null|string
	 */
	public function getPasswordByUsername($username) {
		$this->load->library('salt');
		$this->db->select('password')->from($this->tableName)->where('username', $username);
		$pwd = $this->runOneRowOneField('password');
		if (!empty($pwd)) {
			return $this->salt->decrypt($pwd, $this->getDeskeyOG());
		}
		return null;
	}

	const DEBUG_TAG = '[player_model]';

	/**
	 * overview : get total registered players
	 * @param null $data
	 * @return int
	 */
	public function totalRegisteredPlayers($data = null, $player_ids = null) {
		$this->db
            ->from('player')
            ->where('deleted_at IS NULL');
		if ($data) {
			$this->db->where($data);
		}
        if($player_ids){ $this->db->where_in('playerId', $player_ids); }
		return $this->db->count_all_results();
	}

	/**
	 * overview : get total registered players by date
	 *
	 * @param $date
	 * @return mixed
	 */
	public function totalRegisteredPlayersByDate($date, $player_ids = null) {
		$this->db
            ->from('player')
            ->where('createdOn >=', $date . ' ' . Utils::FIRST_TIME)
            ->where('createdOn <=', $date. ' ' . Utils::LAST_TIME)
            ->where('deleted_at IS NULL');
        if($player_ids) { $this->db->where_in('playerId', $player_ids); }
		return $this->db->count_all_results();
	}

	public function totalRegisteredPlayersByIp($ip, $date_from = NULL, $date_to = NULL) {
		$this->db
            ->from('player')
            ->join('playerdetails','playerdetails.playerId = player.playerId')
            ->where('playerdetails.registrationIP', $ip)
            ->where('player.deleted_at IS NULL');

		if(!empty($date_from)){
		    $this->db->where('player.createdOn >=', $date_from);
        }
		if(!empty($date_to)){
		    $this->db->where('player.createdOn <=', $date_to);
        }
		return $this->db->count_all_results();
	}

	/**
	 * overview :get last members
	 *
	 * @param int $number_of_days
	 * @return array
	 */
	public function getLastMembers($number_of_days = 7, $base_date = null, $disp_date = null) {
		if (empty($disp_date) && !empty($base_date)) {
			$disp_date = $base_date;
		}

		$this->db->select('DATE(player.createdOn) date');
		$this->db->select('COUNT(distinct player.playerId) count');
		$this->db->select('COUNT(distinct transactions.to_id) deposit_count');
		$this->db->from($this->tableName);
		$this->db->join('transactions', 'transactions.to_id = player.playerId AND transactions.to_type = ' . Transactions::PLAYER . ' AND transactions.transaction_type = ' . Transactions::DEPOSIT . ' AND transactions.status = ' . Transactions::APPROVED, 'left');
		// $this->db->where('player.createdOn >=', date('Y-m-d 00:00:00', strtotime('-6 days')));
		// $this->db->where('player.createdOn <=', date('Y-m-d 23:59:59'));
		// OGP-15002 add argument base_date
		$this->db->where('player.createdOn >=', date('Y-m-d 00:00:00', strtotime("{$base_date} -6 days")));
		$this->db->where('player.createdOn <=', date('Y-m-d 23:59:59', strtotime("{$base_date} +0 day")));
		$this->db->group_by('DATE(player.createdOn)');
		$this->db->order_by('date', 'desc');
		$query = $this->db->get();
		$result = $query->result();

		$data = array();
		foreach ($result as $row) {
			$data[$row->date] = $row;
		}

		$ret = array();
		for ($i = 0; $i < $number_of_days; $i++) {
			$date = date('Y-m-d', strtotime("{$base_date} -{$i} days"));
			$disp_date_i = date('Y-m-d', strtotime("{$disp_date} -{$i} days"));
			$ret[$disp_date_i] = isset($data[$date]) ? $data[$date] : null;
		}

		return $ret;
	}

	/**
	 * overview : get total player deposited
	 *
	 * @return int
	 */
	public function totalPlayerDeposited($player_ids = null) {
		$use_transaction_count = $this->utils->getConfig('dashboard_use_transaction_count_totalPlayerDeposited');
		if($use_transaction_count) {

			$this->db->select('COUNT(distinct transactions.to_id) cnt')
				->from('transactions')->where('transactions.to_type', Transactions::PLAYER)
				->where('transactions.transaction_type', Transactions::DEPOSIT)
				->where('transactions.status', Transactions::APPROVED);
			if($player_ids){ $this->db->where_in('transactions.to_id', $player_ids); }

			$query = $this->db->get();
		} else {
			$this->db->select('COUNT(playerId) as cnt')
				->from('player')
				->where('approved_deposit_count  >', 0);
				if($player_ids){ $this->db->where_in('playerId', $player_ids); }
			$query = $this->db->get();
		}
		// $this->db->select('COUNT(distinct transactions.to_id) count');
		// $this->db->from($this->tableName);
		// $this->db->join('transactions', 'transactions.to_id = player.playerId AND transactions.to_type = ' . Transactions::PLAYER . ' AND transactions.transaction_type = ' . Transactions::DEPOSIT . ' AND transactions.status = ' . Transactions::APPROVED, 'left');
		// $query = $this->db->get();
		return $this->getOneRowOneField($query, 'cnt');
	}

	/**
	 * overview : will get player deposit rule
	 *
	 * @param 	int	$playerId
	 * @return	array
	 */
	public function getPlayerDepositRule($playerId) {
		// $playerLevel = $this->getPlayerGroupLevel($playerId);
		//var_dump($playerLevel[0]['playerGroupId']);exit();
		$this->db->select('vipsettingcashbackrule.minDeposit,vipsettingcashbackrule.maxDeposit')
			->from('player')
			->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId', 'left');
		$this->db->where('player.playerId', $playerId);
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * overview : will get player deposit rule
	 *
	 * @param 	int		$playerId
	 * @return	array
	 */
	public function getPlayerWithdrawalRule($playerId) {
		// $playerLevel = $this->getPlayerGroupLevel($playerId);
		//var_dump($playerLevel[0]['playerGroupId']);exit();
		$this->db->select('vipsettingcashbackrule.dailyMaxWithdrawal, vipsettingcashbackrule.withdraw_times_limit, vipsettingcashbackrule.max_withdraw_per_transaction')
			->from('player')
			->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId', 'left');
		$this->db->where('player.playerId', $playerId);
		$query = $this->db->get();
		// return $query->result_array();
		if (!$query->row_array()) {
			return false;
		} else {
			return $query->row_array();
		}
	}

	/**
	 * overview : will get player total withdrawal today
	 *
	 * @param 	int		$playerAccountId
	 * @return	array
	 */
	public function getPlayerCurrentTotalWithdrawalToday($playerAccountId) {
		//var_dump($todayAm);exit();
		$this->db->select('SUM(walletaccount.amount) AS totalWithdrawalToday')
			->from('walletaccount')
			->join('playeraccount', 'playeraccount.playerAccountId = walletaccount.playerAccountId', 'left');
		$this->db->where('walletaccount.playerAccountId', $playerAccountId);
		$this->db->where('walletaccount.walletType', 'Main');
		$this->db->where('walletaccount.dwStatus', 'approved');
		$this->db->where('walletaccount.transactionType', 'withdrawal');
		$this->db->where('walletaccount.processDatetime >=', date('Y-m-d') . ' 00:00:00');
		$this->db->where('walletaccount.processDatetime <=', date('Y-m-d') . ' 23:59:59');
		$query = $this->db->get();
		// return $query->result_array();
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
			//var_dump($data);exit();
			return $data;
		}
		return false;
	}

    /**
     *  return all players under given agents
     *
     *  @param  array/int agent_id(s)
     *  @return array all records of players
     */
    public function get_player_ids_by_agent_ids($agent_ids) {
		$this->db->select('playerId');
        if (is_array($agent_ids)) {
		$this->db->where_in('agent_id', $agent_ids);
        } else {
            $this->db->where('agent_id', $agent_ids);
        }
        $result = $this->db->get('player');
        $rows = $this->getMultipleRowArray($result);
        $ids = array();
        if (!empty($rows)) {
            foreach($rows as $rec) {
                $ids[] = $rec['playerId'];
            }
        }
        return $ids;
	}

	/**
	 *  overview : get all players under given agent
	 *
	 *  @param  int $agent_id
	 *  @return array
	 */
	public function get_players_by_agent_id($agent_id) {
		$sql = "SELECT * FROM player WHERE agent_id = ?";
		return $this->db->query($sql, array($agent_id))->result_array();
	}

	/**
	 *  overview : get all players under given agent
	 *
	 *  @param  int $request
	 *  @return array players
	 */
	public function get_players_under_agent($request, $readonlyLogged) {
		$this->load->library(array('data_tables'));
		$this->load->model(array('agency_model', 'wallet_model'));

		$hide_transfer_on_agency = $this->utils->isEnabledFeature('hide_transfer_on_agency');

        $platform_ids = $this->utils->getConfig('apis_with_bet_limit');
        $show_bet_limit_on_agency = false;
        foreach($platform_ids as $id){
            $api = $this->utils->loadExternalSystemLibObject($id);
            if(!empty($api)){
                $show_bet_limit_on_agency = true;
            }
        }

        $hide_bet_limit_on_agency = $this->utils->isEnabledFeature('hide_bet_limit_on_agency') || !$show_bet_limit_on_agency;

		$input = $this->data_tables->extra_search($request);
		$table = $this->tableName;
		$where = array();
		$values = array();
		$joins['playerdetails'] = 'player.playerId = playerdetails.playerId';
		$joins['player_runtime'] = 'player.playerId = player_runtime.playerId';
		$where[] = "player.agent_id IS NOT NULL";

		if (isset($input['search_on_date']) && $input['search_on_date']) {
			if (isset($input['date_from'], $input['date_to'])) {
				$where[] = "createdOn BETWEEN ? AND ?";
				$values[] = $input['date_from'];
				$values[] = $input['date_to'];
			}
		}
		if (isset($input['player_username']) && $input['player_username'] != '') {
			$where[] = "player.username LIKE ?";
			$values[] = '%' . $input['player_username'] . '%';
		}
		/*
					if (isset($input['agent_id']) && $input['agent_id'] != '') {
						$where[] = "player.agent_id = ?";
						$values[] = $input['agent_id'];
			        }
		*/
		if (isset($input['agent_name']) && $input['agent_name'] != '') {
			$agent_name = $input['agent_name'];
			$agent_details = $this->agency_model->get_agent_by_name($agent_name);
			$where[] = "agent_id = ?";
			$values[] = $agent_details['agent_id'];
		} else if (isset($input['agent_id']) && $input['agent_id'] != '') {
			// need to get all downline players
			$joins['agency_agents'] = 'player.agent_id = agency_agents.agent_id';
			$parent_ids = array($input['agent_id']);
			$sub_ids = array();
			$all_ids = $parent_ids;
			while (!empty($sub_ids = $this->agency_model->get_sub_agent_ids_by_parent_id($parent_ids))) {
				//$this->utils->debug_log('sub_ids', $sub_ids);
				$all_ids = array_merge($all_ids, $sub_ids);
				$parent_ids = $sub_ids;
				$sub_ids = array();
			}
			foreach ($all_ids as $i => $id) {
				if ($i == 0) {
					$w = "(player.agent_id = ?";
				} else {
					$w .= " OR player.agent_id = ?";
				}
				$values[] = $id;
			}
			$w .= ")";
			$where[] = $w;
		}

		# DEFINE TABLE COLUMNS #####################################################################
		$i = 0;
		$columns = array(
			array(
				'alias' => 'blocked',
				'select' => 'player.blocked',
			),
			array(
				'alias' => 'playerId',
				'select' => 'player.playerId',
			),
			array(
				'dt' => $i++,
				'alias' => 'action',
				'select' => 'player.playerId',
				'formatter' => function ($d, $row) use ($hide_transfer_on_agency, $hide_bet_limit_on_agency, $readonlyLogged) {
					$output = '';
					//hide button when readonly account
					if ($row['blocked'] == 0 && !$readonlyLogged) {
						if($this->utils->isEnabledFeature('enabled_agency_adjust_player_balance')){
							$title = lang('Deposit');
							$output .= "<a href='javascript:void(0)' class='agent-oper' data-toggle='tooltip' title='$title' onclick='player_deposit($d)'><span class='fa fa-plus-circle text-info'></span></a> ";
							$title = lang('Withdraw');
							$output .= "<a href='javascript:void(0)' class='agent-oper' data-toggle='tooltip' title='$title' onclick='player_withdraw($d)'><span class='fa fa-minus-circle text-primary'></span></a> ";
						}
						if (!$hide_transfer_on_agency) {
							$title = lang('Transfer from subwallet');
							$output .= "<a href='" . site_url('/agency/playerAction/3/' . $d) . "' class='agent-oper' data-toggle='tooltip' title='$title'><span class='fa fa-arrow-circle-up text-warning'></span></a> ";
							$title = lang('Transfer to subwallet');
							$output .= "<a href='" . site_url('/agency/playerAction/4/' . $d) . "' class='agent-oper' data-toggle='tooltip' title='$title'><span class='fa fa-arrow-circle-down text-success'></span></a> ";
						}

                        $playerGameList = $this->game_provider_auth->getGamePlatforms($d);
                        $playerGames = array();
                        foreach($playerGameList as $val){
                            if($val['register'] == 1){
                                $playerGames[] = $val['id'];
                            }
                        }
                        $result = count(array_intersect($this->utils->getConfig('apis_with_bet_limit'), $playerGames));

						if(!$hide_bet_limit_on_agency && $result > 0){
							$title = lang('Player Bet Limit');
							$output .= "<a href='" . site_url('/agency/playerBetLimit/' . $d) . "' class='agent-oper' data-toggle='tooltip' title='$title'><span class='fa fa-hand-stop-o text-danger'></span></a> ";
						}
						$title = lang('Refresh Balance');
						$output .= "<a href='" . site_url('/agency/player_refresh_balance/' . $d) . "' class='agent-oper' data-toggle='tooltip' title='$title'><span class='fa fa-refresh text-info'></span></a> ";
						// $title = lang('Freeze this player');
						// $output .= "<a href='javascript:void(0)' class='agent-oper' data-toggle='tooltip' title='$title' onclick='freeze_player($d)'><span class='fa fa-lock text-danger'></span></a> ";
						$title = lang('Credit Transactions');
						$output .= "<a href='javascript:void(0)' class='agent-oper' data-toggle='tooltip' title='$title' onclick='credit_transaction(\"" . $row['username'] . "\")'><span class='fa fa-money text-success'></span></a> ";
						$title = lang('Game History');
						$output .= "<a href='javascript:void(0)' class='agent-oper' data-toggle='tooltip' title='$title' onclick='game_history(\"" . $row['username'] . "\")'><span class='fa fa-exchange text-danger'></span></a> ";
						return $output;
					} else {
						// when frozen only 'unfreeze' is enabled
						// $title = lang('Unfreeze this player');
						// $output .= "<a href='javascript:void(0)' class='agent-oper' data-toggle='tooltip' title='$title' onclick='unfreeze_player($d)'><span class='fa fa-unlock text-success'></span></a> ";
						return $output;
					}
					return $output;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'username',
				'select' => 'player.username',
				'formatter' => function ($d, $row) use($readonlyLogged){
					$player_id = $row['playerId'];
					$ret = "<i class='fa fa-user' ></i> ";
					$title = lang('Show Player Info');
					if($readonlyLogged){
						//for readonly account
						$ret .= $d;
					}else{
						$ret .= "<a href='" . site_url('/agency/player_information/' . $player_id) . "' class='agent-oper' data-toggle='tooltip' title='$title'>$d</a> ";
					}
					return $ret;
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'agent_name',
				'select' => 'player.agent_id',
				'formatter' => function ($d) use($readonlyLogged) {
					$agent_id = $d;
					if (!isset($agent_name)) {
						$agent_details = $this->agency_model->get_agent_by_id($agent_id);
						$agent_name = $agent_details['agent_name'];
					}
					$ret = "<i class='fa fa-user' ></i> ";
					$title = lang('Show Agent Info');
					if($readonlyLogged){
						//for readonly account
						$ret .= $agent_name;
					}else{
						$ret .= "<a href='" . site_url('/agency/agent_information/' . $agent_id) . "' class='agent-oper' data-toggle='tooltip' title='$title' onclick='show_agent_info($agent_id)'>$agent_name</a> ";
					}
					return $ret;
				},
			),
			// array(
			// 	'dt' => $i++,
			// 	'alias' => 'rolling_comm',
			// 	'select' => 'player.rolling_comm',
			// ),
			array(
				'dt' => $i++,
				'alias' => 'balance',
				'select' => 'player.playerId',
				'formatter' => function ($d) {
					//return $this->wallet_model->getTotalBalance($d);
					$this->load->model(array('player_model', 'external_system'));
					$game_platforms = $this->external_system->getAllActiveSytemGameApi();
					$playerDetails = $this->player_model->getPlayersSubWalletBalance($d, $game_platforms);
					return number_format($playerDetails['total'], 2);
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'createdOn',
				'select' => 'player.createdOn',
				'formatter' => function ($d) {
					return (!$d || strtotime($d) < 0) ? '<i>' . lang('lang.norecyet') . '</i>' : date('Y-m-d H:i:s', strtotime($d));
				},
			),
			array(
				'dt' => $i++,
				'alias' => 'firstName',
				'select' => 'playerdetails.firstName',
			),
			array(
				'dt' => $i++,
				'alias' => 'lastName',
				'select' => 'playerdetails.lastName',
			),
			array(
				'dt' => $i++,
				'alias' => 'ip',
				'select' => 'player_runtime.lastLoginIp',
			),
		);

		# OUTPUT ###################################################################################
		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);
		// $this->utils->debug_log($result);

		return $result;
	} // get_players_under_agent  }}}2

	/**
	 * overview : add new log when player's info is updated
	 * Cloned from Player::addPlayerInfoUpdates() of models.
     *
	 * @param 	int		$playerId
	 * @param	array	$data
	 * @return 	array
	 */
	public function addPlayerInfoUpdates($playerId, $data, $db = null) {

		$modify_empty_changes = str_replace(["<code>Old: </code>", "<code>New: </code>"],["<code>Old: N/A</code>", "<code>New: N/A</code>"],$data['changes']);

		$data['changes'] = $modify_empty_changes;


        if ($db == null) {
            $db = $this->db;
        }
		$db->insert('playerupdatehistory', $data);
	}

    /**
	 * get player's level
	 * Cloned from Player::getPlayerLevel() of models.
     *
	 * @param 	int
	 * @return 	array
	 */
	public function getPlayerLevel($player_id, $db = null) {
        if ($db == null) {
            $db = $this->db;
        }
		$sql = "SELECT pl.playerlevelId, pl.playerGroupId, vs.vipSettingId, vs.groupName, vscbr.vipsettingcashbackruleId, vscbr.vipLevel, vscbr.upgradeAmount, vscbr.downgradeAmount, vscbr.vipLevelName
		FROM playerlevel AS pl
		LEFT JOIN vipsettingcashbackrule AS vscbr ON pl.playerGroupId = vscbr.vipsettingcashbackruleId
		LEFT JOIN vipsetting AS vs ON vscbr.vipSettingId = vs.vipSettingId
		WHERE playerId = ? ";

        $query = $db->query($sql, array($player_id));

		return $query->row_array();
	}

	/**
	 * overview : get players affiliate
	 *
	 * @param int	$affiliateId
	 * @param int 	$limit
	 * @param int 	$offset
	 * @param bool|false $today
	 * @return array
	 */
	public function getPlayersByAffiliateId($affiliateIds, $limit = 0, $offset = 10, $today = false) {

		$this->db->select('player.*');
		$this->db->select('affiliates.username as affiliate_username');
		$this->db->select('playerdetails.firstName');
		$this->db->select('playerdetails.lastName');
		$this->db->select('player_runtime.lastLoginTime as last_login');
		if($this->utils->getConfig('enable_3rd_party_affiliate')){

			$this->db->select('playerdetails.cpaId as aff_source_detail');
		}
		$this->db->from('player');
		$this->db->join('playerdetails','playerdetails.playerId = player.playerId');
		$this->db->join('affiliates', 'affiliates.affiliateId = player.affiliateId');
		$this->db->join('player_runtime', 'player_runtime.playerId = player.playerId', 'left');

		if (is_array($affiliateIds)) {
			$this->db->where_in('player.affiliateId', $affiliateIds);
		} else {
			$this->db->where('player.affiliateId', $affiliateIds);
		}

		$query = $this->db->get();

		return $query->result_array();
	}

	/**
	 * Returns players under a given affiliate
	 * Modified from Player_model::getPlayersByAffiliateId() above, OGP-14933
	 *
	 * @param	int/array 	$affiliateIds	affiliateId, may use scalar or array
	 * @param	datestring	$date_from		Signup (registration) date from
	 * @param	datestring	$date_to		Signup date to
	 * @return	array
	 */
	public function getPlayersByAffIdAndDate($affiliateIds, $date_from = null, $date_to = null) {

		$this->db
			->select([ 'P.*', 'AF.username as affiliate_username', 'D.firstName', 'D.lastName', 'R.lastLoginTime as last_login' ])
			->from('player AS P')
			->join('playerdetails AS D','D.playerId = P.playerId')
			->join('affiliates AS AF', 'AF.affiliateId = P.affiliateId')
			->join('player_runtime AS R', 'R.playerId = P.playerId', 'left')
		;
		if ($this->utils->getConfig('enable_3rd_party_affiliate')) {
			$this->db->select('D.cpaId as aff_source_detail');
		}
		if (is_array($affiliateIds)) {
			$this->db->where_in('P.affiliateId', $affiliateIds);
		} else {
			$this->db->where('P.affiliateId', $affiliateIds);
		}

		if (!empty($date_from)) {
			$this->db->where('P.createdOn >=', $date_from);
		}

		if (!empty($date_to)) {
			$this->db->where('P.createdOn <=', $date_to);
		}

		$query = $this->db->get();

		// $this->utils->printLastSQL();

		return $query->result_array();
	}

	/**
	 * overview : get players by agent id
	 *
	 * @param int	$agentId
	 * @param int 	$limit
	 * @param int 	$offset
	 * @param bool|false $today
	 * @return array
	 */
	public function getPlayersByAffIdAndDatePerformance($affiliateIds, $date_from, $date_to,$by_date,$p_date_from, $p_date_to,$p_by_date) {
		$this->db
			->select([
				'P.*',
				'AF.username as affiliate_username',
				'D.firstName',
				'D.lastName',
				'R.lastLoginTime as last_login',
				'SUM(PH.total_bonus) AS sum_total_bonus',
				'SUM(PH.total_deposit) AS sum_total_deposit',
				'SUM(PH.total_withdrawal) AS sum_total_withdrawal',
				'SUM(PH.total_cashback) AS sum_total_cashback',
				'SUM(PH.total_bet) AS sum_total_bet',
				'SUM(PH.total_win) AS sum_total_win',
				'SUM(PH.total_loss) AS sum_total_loss',
				'SUM(PH.deposit_times) AS total_deposit_times',

			]);

		if($p_by_date&&$by_date){
			$this->db
			->from('player AS P')
			->join('player_report_hourly AS PH','PH.player_id = P.playerId','inner')
			->where('P.createdOn >=', $date_from)
			->where('P.createdOn <=', $date_to)
			->where('PH.date_hour >=', $this->utils->formatDateHourForMysql(new DateTime($p_date_from)))
			->where('PH.date_hour <=', $this->utils->formatDateHourForMysql(new DateTime($p_date_to)))
			;
		}elseif($p_by_date&&!$by_date){
			$this->db
			->from('player_report_hourly AS PH')
			->join('player AS P','P.playerId = PH.player_id','left')
			->where('PH.date_hour >=', $this->utils->formatDateHourForMysql(new DateTime($p_date_from)))
			->where('PH.date_hour <=', $this->utils->formatDateHourForMysql(new DateTime($p_date_to)))
			;
		}elseif(!$p_by_date&&$by_date){
			$this->db
			->from('player AS P')
			->join('player_report_hourly AS PH','PH.player_id = P.playerId','inner')
			->where('P.createdOn >=', $date_from)
			->where('P.createdOn <=', $date_to)
			;
		}else{
			$this->db
			->from('player AS P')
			->join('player_report_hourly AS PH','PH.player_id = P.playerId','left');
		}
		$this->db
		->join('affiliates AS AF', 'AF.affiliateId = P.affiliateId')
		->join('playerdetails AS D','D.playerId = P.playerId')
		->join('player_runtime AS R', 'R.playerId = P.playerId', 'left')
		->group_by('PH.player_id')
		->order_by('PH.date_hour','desc');

		if ($this->utils->getConfig('enable_3rd_party_affiliate')) {
			$this->db->select('D.cpaId as aff_source_detail');
		}
		if (is_array($affiliateIds)) {
			$this->db->where_in('P.affiliateId', $affiliateIds);
		} else {
			$this->db->where('P.affiliateId', $affiliateIds);
		}



		$query = $this->db->get();
		$all_data=$query->result_array();
		return $all_data;

		// $return_data=[];
		// foreach ($all_data as $value) {
		// 	$playerId = $value['playerId'];
		// 	$return_data[$playerId] = $value;
		// }
		// return $return_data;
	}



	/**
	 * overview : get player account information
	 *
	 * @param int	$playerId
	 * @param int 	$limit
	 * @param int 	$offset
	 * @return array
	 */
	public function getPlayerAccountInfo($playerId, $limit = 0, $offset = 10) {
		$this->db->where('playerId', $playerId);

		$result = $this->db->get('playerdetails');

		if ($result->num_rows() == 1) {
			return $result->row_array();
		} else {
			return $result->result_array();
		}
	}

	/**
	 * overview : get player signup information
	 *
	 * @param int	$playerId
	 * @param int	$agentId
	 * @return array
	 */
	public function getPlayerSignupInfoByAgentId($playerId, $agentId) {

		$this->db->where('playerId', $playerId);
		// $this->db->where('agent_id', $agentId); TODO: Allow to view player info of subagent's player

		$result = $this->db->get('player');

		if ($result->num_rows() == 1) {
			return $result->row_array();
		} else {
			return $result->result_array();
		}
	}

	/**
	 * overview : get player signup information
	 *
	 * @param int	$playerId
	 * @param int 	$limit
	 * @param int 	$offset
	 * @return mixed
	 */
	public function getPlayerSignupInfo($playerId, $limit = 0, $offset = 10) {
		// simple security

		$this->db->where('playerId', $playerId);

		$result = $this->db->get('player');

		if ($result->num_rows() == 1) {
			return $result->row_array();
		} else {
			return $result->result_array();
		}
	}

	/**
	 * overview : get player type
	 *
	 * @param int	$playerId
	 * @param int 	$limit
	 * @param int 	$offset
	 * @return array
	 */
	public function getPlayerType($playerId, $limit = 0, $offset = 10) {
		$this->db->select('typeOfPlayer');
		$this->db->where('playerId', $playerId);
		$result = $this->db->get('playeraccount', 1)->result();

		return $result[0]->typeOfPlayer;
	}

	/**
	 * overview : get player total bets
	 *
	 * @param int	$player_id
	 * @param date	$start_date
	 * @param date	$end_date
	 * @return int
	 */
	public function getPlayerTotalBets($player_id, $start_date, $end_date) {
		$this->db->select('SUM(betting_amount) as bets');
		$this->db->where('player_id', $player_id);
		if (!empty($start_date)) {
			$this->db->where('updated_at >= ', $start_date);
			$this->db->where('updated_at <= ', $end_date);
		}
		$bets = $this->db->get('total_player_game_day');

		if ($bets->num_rows() > 0) {
			return $bets->result()[0]->bets;
		} else {
			return 0;
		}

	}

	/**
	 * overview : get player total win
	 *
	 * @param int	$player_id
	 * @param date	$start_date
	 * @param date	$end_date
	 * @return int
	 */
	public function getPlayerTotalWin($player_id, $start_date, $end_date) {
		$this->db->select('SUM(betting_amount) as win');
		$this->db->where('player_id', $player_id);
		$this->db->where('result_amount >', 0);
		if (!empty($start_date)) {
			$this->db->where('updated_at >= ', $start_date);
			$this->db->where('updated_at <= ', $end_date);
		}
		$win = $this->db->get('total_player_game_day');

		if ($win->num_rows() > 0) {
			return $win->result()[0]->win;
		} else {
			return 0;
		}

	}

	/**
	 * overview : get player total loss
	 *
	 * @param int	$player_id
	 * @param date	$start_date
	 * @param date	$end_date
	 * @return int
	 */
	public function getPlayerTotalLoss($player_id, $start_date, $end_date) {
		$this->db->select('SUM(betting_amount) as loss');
		$this->db->where('player_id', $player_id);
		$this->db->where('result_amount', 0);
		if (!empty($start_date)) {
			$this->db->where('updated_at >= ', $start_date);
			$this->db->where('updated_at <= ', $end_date);
		}
		$loss = $this->db->get('total_player_game_day');

		if ($loss->num_rows() > 0) {
			return $loss->result()[0]->loss;
		} else {
			return 0;
		}

	}

	/**
	 * overview : get player total bonus
	 *
	 * @param int	$player_id
	 * @param date	$start_date
	 * @param date	$end_date
	 * @return int
	 */
	public function getPlayerTotalBonus($player_id, $start_date, $end_date) {

		if (count($player_id) > 0) {
			$this->load->model(['transactions']);
			// $players_id = implode(',', $players_id);
			$where = null;

			if (!empty($start_date)) {
				$where = "AND created_at BETWEEN '" . $start_date . "' AND '" . $end_date . "'";
			}

			$this->db->select_sum('amount')->from('transactions')
				->where('to_type', Transactions::PLAYER)
				->where_in('to_id', $player_id)
				->where('status', Transactions::APPROVED)
				->where_in('transaction_type', [Transactions::ADD_BONUS,
					Transactions::MEMBER_GROUP_DEPOSIT_BONUS, Transactions::PLAYER_REFER_BONUS]);

			$amount = $this->runOneRowOneField('amount');

			return $amount;
		} else {
			return 0;
		}
	}

	/**
	 * overview : get players total balance include sub wallet
	 *
	 * @return int
	 */
	public function getPlayersTotalBallanceIncludeSubwallet($player_ids = null) {
		// $list = array();
		// $list = $this->utils->getAllCurrentGameSystemList();
        $query = $this->db->select('SUM(playeraccount.totalBalanceAmount) AS amount')
		->from('playeraccount')
		->join('player', 'playeraccount.playerId = player.playerId')
		->where('player.deleted_at', NULL, FALSE)
		->where_in('playeraccount.type', ['wallet', 'subwallet']);
		// if($list && $this->utils->getConfig('ig')){
		// 	$query->where_in('playeraccount.typeId', $list)
		// 		  ->or_where('playeraccount.type','wallet');
		// }
        if($player_ids) { $query->where_in('player.playerId', $player_ids); }

		$amount = $query->get();
		$this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
		if ($amount->num_rows() > 0) {
			return $amount->result()[0]->amount;
		} else {
			return 0;
		}
	}

	/**
	 * overview : get players total bets
	 *
	 * @param int	$players_id
	 * @param date	$start_date
	 * @param date	$end_date
	 * @return int
	 */
	public function getPlayersTotalBets($players_id, $start_date, $end_date) {
		if (count($players_id) > 0) {
			$this->db->select('SUM(betting_amount) as bets');
			$this->db->where_in('player_id', implode(',', $players_id));
			if (!empty($start_date)) {
				$this->db->where('updated_at >= ', $start_date);
				$this->db->where('updated_at <= ', $end_date);
			}
			$bets = $this->db->get('total_player_game_day');

			if ($bets->num_rows() > 0) {
				return $bets->result()[0]->bets;
			} else {
				return 0;
			}

		} else {
			return 0;
		}

	}

	/**
	 * overview : get players total bet
	 *
	 * @param int	$players_id
	 * @param date 	$start_date
	 * @param date 	$end_date
	 * @param int	 $game_id
	 * @return int
	 */
	public function getPlayersTotalBet($players_id, $start_date = null, $end_date = null, $game_id = null) {
		// var_dump(func_get_args()); // debug: get gross income per affiliate by Bet-Win Condition

		if (count($players_id) > 0) {
			$this->db->select('SUM(betting_amount) as bet');
			$this->db->where_in('player_id', implode(',', $players_id));
			if (!empty($start_date)) {
				$this->db->where('updated_at >= ', $start_date);
				$this->db->where('updated_at <= ', $end_date);
			}
			if (!empty($game_id)) {
				$this->db->where('game_platform_id', $game_id);
			}
			$transaction = $this->db->get('total_player_game_day');

			if ($transaction->num_rows() > 0) {
				return $transaction->result()[0]->bet;
			} else {
				return 0;
			}

		} else {
			return 0;
		}

	}

	/**
	 * overview : get players total win
	 *
	 * @param $players_id
	 * @param null $start_date
	 * @param null $end_date
	 * @param null $game_id
	 * @return int
	 */
	public function getPlayersTotalWin($players_id, $start_date = null, $end_date = null, $game_id = null) {
		if (count($players_id) > 0) {
			$this->db->select('SUM(betting_amount) as win');
			$this->db->where_in('player_id', implode(',', $players_id));
			$this->db->where('result_amount =', 1);
			if (!empty($start_date)) {
				$this->db->where('updated_at >= ', $start_date);
				$this->db->where('updated_at <= ', $end_date);
			}
			if (!empty($game_id)) {
				$this->db->where('game_platform_id', $game_id);
			}
			$transaction = $this->db->get('total_player_game_day');

			if ($transaction->num_rows() > 0) {
				return $transaction->result()[0]->win;
			} else {
				return 0;
			}

		} else {
			return 0;
		}

	}

	/**
	 * overview : get players total loss
	 *
	 * @param int	$players_id
	 * @param date	$start_date
	 * @param date	$end_date
	 * @return int
	 */
	public function getPlayersTotalLoss($players_id, $start_date, $end_date) {
		if (count($players_id) > 0) {
			$this->db->select('SUM(betting_amount) as loss');
			$this->db->where_in('player_id', implode(',', $players_id));
			$this->db->where('result_amount', 0);
			if (!empty($start_date)) {
				$this->db->where('updated_at >= ', $start_date);
				$this->db->where('updated_at <= ', $end_date);
			}
			$transaction = $this->db->get('total_player_game_day');

			if ($transaction->num_rows() > 0) {
				return $transaction->result()[0]->loss;
			} else {
				return 0;
			}

		} else {
			return 0;
		}

	}

	/**
	 * overview : get players total bonus
	 *
	 * @param int	$players_id
	 * @param date	$start_date
	 * @param date	$end_date
	 * @return int
	 */
	public function getPlayersTotalBonus($players_id, $start_date = null, $end_date = null) {
		if (isset($players_id) && (!is_array($players_id) || count($players_id) > 0)) {

			$this->load->model(['transactions']);

			$this->db->select_sum('(CASE WHEN transactions.transaction_type = ' . Transactions::ADD_BONUS . ' THEN transactions.amount
										 WHEN transactions.transaction_type = ' . Transactions::MEMBER_GROUP_DEPOSIT_BONUS . ' THEN transactions.amount
										 WHEN transactions.transaction_type = ' . Transactions::PLAYER_REFER_BONUS . ' THEN transactions.amount
										 WHEN transactions.transaction_type = ' . Transactions::SUBTRACT_BONUS . ' THEN -transactions.amount
										 ELSE 0
									END)', 'amount');
			$this->db->from('transactions');
			$this->db->where('status', Transactions::APPROVED);
			$this->db->where('to_type', Transactions::PLAYER);
			$this->db->where_in('transaction_type', [Transactions::ADD_BONUS, Transactions::MEMBER_GROUP_DEPOSIT_BONUS, Transactions::PLAYER_REFER_BONUS, Transactions::SUBTRACT_BONUS]);

			if (is_array($players_id)) {
				$this->db->where_in('to_id', $players_id);
			} else {
				$this->db->where('to_id', $players_id);
			}

			if (!empty($start_date)) {
				$this->db->where('created_at >=', $start_date);
			}

			if (!empty($end_date)) {
				$this->db->where('created_at <=', $end_date);
			}

			$amount = $this->runOneRowOneField('amount');

			return $amount !== NULL ? $amount : 0;

		} else {
			return 0;
		}

	}

	/**
	 * overview : get players total deposit
	 *
	 * @param array|int	$players_id
	 * @param date	$start_date
	 * @param date  $end_date
	 * @return int|null
	 */
	public function getPlayersTotalDeposit($players_id, $start_date = null, $end_date = null) {
		// var_dump(func_get_args()); // debug: get gross income per affiliate by Deposit-Withdraw Condition
		if (count($players_id) > 0) {
			$this->load->model(['transactions']);
			$this->db->select('SUM(amount) as deposit')->from('transactions');
			$this->db->where_in('to_id', $players_id);
			$this->db->where('to_type', Transactions::PLAYER);
			$this->db->where('transaction_type', Transactions::DEPOSIT);
			$this->db->where('transactions.status', transactions::APPROVED);
			if (!empty($start_date)) {
				$this->db->where('created_at >= ', $start_date . ' ' . Utils::FIRST_TIME);
				$this->db->where('created_at <= ', $end_date . ' ' . Utils::LAST_TIME);
			}
			// $transaction = $this->db->get('transactions');

			$deposit = $this->runOneRowOneField('deposit');

			if (empty($deposit)) {
				$deposit = 0;
			}

			return $deposit;
			// if ($transaction->num_rows() > 0) {
			// 	// var_dump($transaction->result()[0]->deposit); // debug: get gross income per affiliate by Deposit-Withdraw Condition
			// 	return $transaction->result()[0]->deposit;
			// } else {
			// 	return 0;
			// }

		} else {
			return 0;
		}

	}

	/**
	 * overview : get players total withdraw
	 *
	 * @param array|int	$players_id
	 * @param date $start_date
	 * @param date $end_date
	 * @return int|null
	 */
	public function getPlayersTotalWithdraw($players_id, $start_date = null, $end_date = null) {
		// var_dump(func_get_args()); // debug: get gross income per affiliate by Deposit-Withdraw Condition
		if (count($players_id) > 0) {
			$this->load->model(['transactions']);
			$this->db->select('SUM(amount) as withdraw')->from('transactions');
			$this->db->where_in('to_id', $players_id);
			$this->db->where('to_type', Transactions::PLAYER);
			$this->db->where('transaction_type', Transactions::WITHDRAWAL);
			if (!empty($start_date)) {
				$this->db->where('created_at >= ', $start_date . ' ' . Utils::FIRST_TIME);
				$this->db->where('created_at <= ', $end_date . ' ' . Utils::LAST_TIME);
			}

			// $transaction = $this->db->get('transactions');

			$withdraw = $this->runOneRowOneField('withdraw');

			if (empty($withdraw)) {
				$withdraw = 0;
			}

			return $withdraw;

			// if ($transaction->num_rows() > 0) {
			// 	// var_dump($transaction->result()[0]->withdraw); // debug: get gross income per affiliate by Deposit-Withdraw Condition
			// 	return $transaction->result()[0]->withdraw;
			// } else {
			// 	return 0;
			// }

		} else {
			return 0;
		}

	}

	/**
	 * overview : get players total cash back
	 *
	 * @param int	$players_id
	 * @param date  $start_date
	 * @param date  $end_date
	 * @param int   $total_cashback_date_type
	 *
	 * Updated by Frans Eric Dela Cruz (frans.php.ph) 2018-10-31
	 * @return int
	 */
	public function getPlayersTotalCashback($players_id, $start_date = null, $end_date = null, $total_cashback_date_type = null) {
		if (count($players_id) > 0) {
			$this->load->model(['transactions']);

			$this->db->select_sum('amount')->from('transactions')
				->where('status', Transactions::APPROVED)
				->where('to_type', Transactions::PLAYER)
				->where_in('to_id', $players_id)
				->where_in('transaction_type', array(Transactions::AUTO_ADD_CASHBACK_TO_BALANCE, Transactions::CASHBACK));

			if (!empty($start_date)) {
				$start_date = $this->transactions->getTotalCashBackDate($total_cashback_date_type, $start_date);
				$this->db->where('created_at >=', $start_date);
			}

			if (!empty($end_date)) {
				$end_date = $this->transactions->getTotalCashBackDate($total_cashback_date_type, $end_date);
				$this->db->where('created_at <=', $end_date);
			}

			$amount = $this->runOneRowOneField('amount');

			return $amount !== NULL ? $amount : 0;
		} else {
			return 0;
		}

	}

	/**
	 * Get the Cashback Revenue of Affiliate in Transactions
	 * @param array $Affiliate_id The Affiliate_id list
	 * @param string $start_date The start date
	 * @param string $end_date The end date
	 * @param integer $total_cashback_date_type
	 */
	public function getAffiliateTotalCashbackRevenue($Affiliate_id, $start_date = null, $end_date = null, $total_cashback_date_type = null){
		if (count($Affiliate_id) > 0) {
			$this->load->model(['transactions']);

			$this->db->select_sum('amount')->from('transactions')
				->where('status', Transactions::APPROVED)
				->where('to_type', Transactions::AFFILIATE)
				->where_in('to_id', $Affiliate_id)
				->where_in('transaction_type', array(Transactions::AUTO_ADD_CASHBACK_AFFILIATE));

			if (!empty($start_date)) {
				$start_date = $this->transactions->getTotalCashBackDate($total_cashback_date_type, $start_date);
				$this->db->where('created_at >=', $start_date);
			}

			if (!empty($end_date)) {
				$end_date = $this->transactions->getTotalCashBackDate($total_cashback_date_type, $end_date);
				$this->db->where('created_at <=', $end_date);
			}

			$amount = $this->runOneRowOneField('amount');

			return $amount !== NULL ? $amount : 0;
		} else {
			return 0;
		}
	}

	/**
	 * overview : check if player is active
	 *
	 * @param array	$affiliate_terms
	 * @param int	$playerId
	 * @param date	$start_date
	 * @param date	$end_date
	 * @param string $use_total
	 * @return bool
	 */
	public function isActivePlayer($affiliate_terms, $playerId, $start_date, $end_date, $use_total = 'hour') {
		// var_dump(func_get_args()); // debug: filter player by minimum bet and deposit condition (active_player)

		//check game logs and deposit
		$this->load->model(array('total_player_game_day', 'total_player_game_hour', 'transactions'));

		$betInfo = [];
		if ($use_total == 'day') {
			$betInfo = $this->total_player_game_day->getTotalBetsWinsLossGroupByGamePlatformByPlayers($playerId, $start_date, $end_date);
		} else {
			$betInfo = $this->total_player_game_hour->getTotalBetsWinsLossGroupByGamePlatformByPlayers($playerId, $start_date, $end_date);
		}
		// list($totalDeposit, $totalWithdrawal, $totalBonus) = $this->transactions->getPlayerTotalDepositWithdrawalBonusByDatetime($playerId, $start_date, $end_date);

		$totalBettingAmount = 0; //$totalBet; // $this->game_logs->sumBettingAmountByPlayer($playerId, $start_date, $end_date);
		// $depositAmount = $totalDeposit; //$this->transactions->getTotalDepositByPlayer($playerId, $start_date, $end_date);

		//use betting by platform
		$provider_betting_amount = $affiliate_terms['provider_betting_amount'];

		$provider = $affiliate_terms['provider'];
		if (empty($provider)) {
			//full empty which means use default
			foreach ($betInfo as $gamePlatformId => $betInfo) {
				$totalBettingAmount += $betInfo[0];
			}

			$validBetting = $totalBettingAmount >= $affiliate_terms['minimumBetting'];

			$this->utils->debug_log('empty provider', $validBetting, 'bet', $totalBettingAmount, $affiliate_terms['minimumBetting']);

		} else {

			$validBetting = true;

			foreach ($provider_betting_amount as $gameProviderId => $betting) {
				if ($betting > 0) {
					if (isset($betInfo[$gameProviderId])) {
						$validBetting = $betInfo[$gameProviderId][0] >= $betting;
					} else {
						$validBetting = false;
					}

					$this->utils->debug_log('validBetting', $validBetting, 'gameProviderId', $gameProviderId, 'bet', @$betInfo[$gameProviderId][0], $betting);

					if (!$validBetting) {
						break;
					}
				}
			}

		}

		// $this->utils->debug_log('isActivePlayer :'.$playerId, $start_date, $end_date, 'use_total', $use_total, $affiliate_terms);

		return $validBetting;
		// } else {
		// return false;
		// }
	}

	/**
	 * overview : check if player by provider is active
	 *
	 * @param array	 $affiliate_term
	 * @param int	 $playerId
	 * @param date	 $start_date
	 * @param date	 $end_date
	 * @param int	 $providers_id
	 * @param string $use_total
	 * @return bool
	 */
	public function isActivePlayerByProvider($affiliate_term, $playerId, $start_date, $end_date, $providers_id, $use_total = 'hour') {
		//check game logs and deposit
		$this->load->model(array('total_player_game_day', 'total_player_game_hour', 'transactions'));

		$totalBet = 0;
		$totalWin = 0;
		$totalLoss = 0;
		if ($use_total == 'day') {
			list($totalBet, $totalWin, $totalLoss) = $this->total_player_game_day->getPlayerTotalBetsWinsLossByDatetime(
				$playerId, $start_date, $end_date, $providers_id);

		} else {
			list($totalBet, $totalWin, $totalLoss) = $this->total_player_game_hour->getPlayerTotalBetsWinsLossByDatetime(
				$playerId, $start_date, $end_date, $providers_id);

		}

		list($totalDeposit, $totalWithdrawal, $totalBonus) = $this->transactions->getPlayerTotalDepositWithdrawalBonusByDatetime(
			$playerId, $start_date, $end_date);

		$bettingAmount = $totalBet; // $this->total_player_game_month->sumGameLogsByPlayerByProvider($playerId, $year, $month, $providers_id);
		$depositAmount = $totalDeposit; // $this->transactions->getTotalDepositByPlayer($playerId, $year, $month);

		// $this->utils->debug_log('playerId', $playerId, 'start_date', $start_date, 'end_date', $end_date,
		// 	'bettingAmount', $bettingAmount, 'depositAmount', $depositAmount);

		// if ($bettingAmount > 0 && $depositAmount > 0) {
		// $defaultAffiliateTerms = $this->affiliate->getDefaultAffiliateTerms();

		// $affiliate_settings = json_decode($defaultAffiliateTerms);

		// $minBet = 0;
		// if (isSet($affiliate_settings->minimumBetting)) {
		// 	$minBet = $affiliate_settings->minimumBetting;
		// }

		// $minDep = 0;
		// if (isSet($affiliate_settings->minimumDeposit)) {
		// 	$minDep = $affiliate_settings->minimumDeposit;
		// }
		if($this->utils->isEnabledFeature('affiliate_commision_check_deposit_and_bet')){
			return $bettingAmount >= $affiliate_term['minimumBetting'] && $depositAmount >= $affiliate_term['minimumDeposit'];
		} else {
			return $bettingAmount >= $affiliate_term['minimumBetting'] || $depositAmount >= $affiliate_term['minimumDeposit'];
		}
		// return $bettingAmount >= $affiliate_term['minimumBetting'] && $depositAmount >= $affiliate_term['minimumDeposit'];
		// return $bettingAmount > $minBet && $depositAmount > $minDep;
		// } else {
		// return false;
		// }

	}

	/**
	 * overview : get available players
	 *
	 * @param string $username
	 * @return null
	 */
	public function getAvailablePlayers($username = null) {
		$this->db->from($this->tableName)->where('status', self::OLD_STATUS_ACTIVE)->where('deleted_at', null);
		if ($username) {
			$this->db->like('username', $username, 'after');
		}
		return $this->runMultipleRow();
	}

	/**
	 * overview : get available players arr
	 *
	 * @param string $username
	 * @return null
	 */
	public function getAvailablePlayersArr($username = null) {
		$this->db->from($this->tableName)->where('status', self::OLD_STATUS_ACTIVE)->where('deleted_at', null);
		if ($username) {
			$this->db->like('username', $username, 'after');
		}
		return $this->runMultipleRowArray();
	}

	/**
	 * overview : get available playerIds
	 *
	 */
	public function getAvailablePlayerIds() {
		$this->db->select('playerId')->from($this->tableName)->where('status', self::OLD_STATUS_ACTIVE)->where('deleted_at', null);

		return $this->runMultipleRow();
	}

	/**
	 * overview : batch process active status
	 *
	 * @return bool|int
	 */
	public function batchProcessActiveStatus() {
		//process all player
		$this->load->model(array('wallet_model'));
		$rows = $this->getAvailablePlayers();
		$cnt = 0;
		foreach ($rows as $row) {
			$playerId = $row->playerId;
			//search balance
			$bal = $this->wallet_model->getTotalBalance($playerId);
			if ($bal <= 0) {
				//inactive
				$cnt += $this->inactivePlayer($playerId);
			}
		}
		return $cnt;
	}

	/**
	 * overview : check if player is active
	 *
	 * @param int	$playerId
	 * @return bool
	 */
	public function activePlayer($playerId) {
		$this->db->set('active_status', self::DB_TRUE)
			->where('playerId', $playerId);

		return $this->runAnyUpdate($this->tableName);
	}

	/**
	 * overview : check if inactive player
	 *
	 * @param  int	$playerId
	 * @return bool
	 */
	public function inactivePlayer($playerId) {
		$this->db->set('active_status', self::DB_FALSE)
			->where('playerId', $playerId);

		return $this->runAnyUpdate($this->tableName);
	}

	/**
	 * overview : will get player details
	 *
	 * @param 	int		$playerId
	 * @return 	array
	 */
	public function getPlayerDetails($playerId) {
        $data = array();
		$this->db->select('player.playerId,
						   player.username,
						   player.email,
						   player.createdOn,
						   playerdetails.firstName,
						   playerdetails.lastName,
						   playerdetails.contactNumber,
						   playerdetails.country,
						   playerdetails.region,
						   playerdetails.city,
						   playerdetails.zipcode,
						   playerdetails.address,
						   playerdetails.id_card_number,
						   playerdetails.phone,
						   vipsettingcashbackrule.vipLevel,
						   vipsettingcashbackrule.vipLevelName,
						   vipsetting.groupName,
						   playerdetails.pix_number,
						   playerdetails.birthdate,
						   player.invitationCode,
						   playerdetails.registrationWebsite,
						   player.registered_by,
						   player.affiliateId
						   ')
			->from('playeraccount')
			->join('player', 'player.playerId = playeraccount.playerId', 'left')
			->join('playerdetails', 'playerdetails.playerId = player.playerId', 'left')
			->join('playerlevel', 'playerlevel.playerId = player.playerId', 'left')
			->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = playerlevel.playerGroupId', 'left')
			->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId', 'left');

		$this->db->where('player.playerId', $playerId);

		return $this->runMultipleRowArray();
	}

	/**
	 * overview : get all players under affiliated id
	 *
	 * @param int	 $affiliate_id
	 * @param date	 $date_from
	 * @param date	 $date_to
	 * @return array
	 */
	public function getAllPlayersUnderAffiliateId($affiliate_id, $date_from = null, $date_to = null) {

		$this->db->from($this->tableName)->select('playerId')->where('affiliateId', $affiliate_id);

		if (!empty($date_from) && !empty($date_to)) {
			$this->db->where('createOn >=', $date_from)->where('createOn <=', $date_to);
		}

		// $playerIds=array();
		$rows = $this->runMultipleRow();
		return $this->convertRowsToArray($rows);
	}

	/**
	 * overview : will get code of the player who has referral code in registration.
	 *
	 * @param 	int		$player_id
	 * @return	array
	 */
	public function getRefereePlayerId($player_id) {
		// $this->db->select('refereePlayerId')->from($this->tableName)->where('playerId', $player_id);

		return $this->runOneRowOneFieldById($player_id, 'refereePlayerId');
		// $sql = "SELECT * FROM player WHERE playerId = ? ";

		// $query = $this->db->query($sql, array($player_id));

		// $result = $query->row_array();

		// if (empty($result)) {
		// 	return 0;
		// } else {
		// 	return $result['refereePlayerId'];
		// }
	}

	/**
	 * overview : will add favorite to game player
	 *
	 * @param  array	$data
	 * @return bool
	 */
	public function addFavoriteGameToPlayer($data) {
		$this->db->insert('player_favorites', $data);
		return ($this->db->affected_rows() != 1) ? false : true;
	}

	/**
	 * overview : update player point balance
	 *
	 * @param int	$playerId
	 * @param int	$point
	 * @return bool
	 */
	public function updatePlayerPointBalance($playerId, $point) {
		$this->db->where('playerId', $playerId);
		$this->db->update('player', array('point' => $point));
		if ($this->db->affected_rows() == '1') {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * overview : get player by username
	 *
	 * @param  string	$username
	 * @return null|array
	 */
	public function getPlayerByUsername($username) {
		if (!empty($username)) {
			// $this->db->select('playerId');
			$this->db->where('username', $username);
			$qry = $this->db->get($this->tableName);
			return $this->getOneRow($qry);
		}
		return null;

	}

	/**
	 * overview : get public information by id
	 *
	 * @param  int	$playerId
	 * @return array
	 */
	public function getPublicInfoById($playerId) {
		$this->db->select('username,email')->from('player')->where('playerId', $playerId);

		return $this->getOneRow();
	}

	/**
	 * overview : check if phone is verified
	 *
	 * @param  int	$playerId
	 * @return bool
	 */
	public function isVerifiedPhone($playerId) {
		$this->db->select('verified_phone')->from($this->tableName)->where('playerId', $playerId);
		$verified_phone = $this->runOneRowOneField('verified_phone');

		return $verified_phone == self::DB_TRUE;
	}

	/**
	 * Check the some fields of player/details from LINE registation/login.
	 *
	 * @param integer $playerId The field. "player.playerId".
	 * @return boolean If true, it's means pass, else means verifi failed.
	 */
	public function check_playerDetail_from_line($playerId ){
		$this->load->library(['player_functions']);

		$resultBool = false;
		$result = [];
		$result['success'] = $resultBool;
		$result['message'] = null;
		$result['isFirstNameFill'] = null;
		$result['isVerifiedPhone'] = null;

		$line_credential = $this->utils->getConfig('line_credential');
		$isContactNumberRequired = $this->player_functions->checkAccountFieldsIfRequired('Contact Number');
		$isFirstNameRequired = $this->player_functions->checkAccountFieldsIfRequired('First Name');
		$isLineCredential = ! empty($line_credential);
		if(	$isContactNumberRequired
			&& $isFirstNameRequired
			&& $isLineCredential
		){
			$isVerifiedPhone = $this->isVerifiedPhone($playerId);
			$thePlayerDetail = $this->getPlayerDetailArrayById($playerId);
			$isFirstNameFill = ! empty($thePlayerDetail['firstName']);
			$result['isFirstNameFill'] = $isFirstNameFill;
			$result['isVerifiedPhone'] = $isVerifiedPhone;

			if( $isVerifiedPhone && $isFirstNameFill ){
				$resultBool = true;
			}else{
				// default failed message
				$result['message'] = lang('Please fill and save mandatory fields to enjoy full service.');
				if( $isFirstNameFill
					&& ! $isVerifiedPhone
				){ // only isVerifiedPhone = false
					$result['message'] = lang('checkPlayerContactNumberVerified.message');
				}
			}
		}else{
			$resultBool = null;// ignore
		}
		$result['success'] = $resultBool;

		return $result;
	} // EOF check_playerDetail_from_line

	/**
	 * overview : check if enabled
	 * @param $playerId
	 * @return bool
	 */
	public function isEnabled($playerId) {
		$this->db->select('status,blocked,deleted_at')->from($this->tableName)->where('playerId', $playerId);
		$row = $this->runOneRow();
		if (!empty($row)) {
			return $row->status == self::OLD_STATUS_ACTIVE && $row->blocked != self::DB_TRUE
				&& (empty($row->deleted_at) || $row->deleted_at == '0000-00-00 00:00:00');
		}

		return false;
	}

	/**
	 * overview : get all unlimit players
	 *
	 * @return array
	 */
	public function getAllUnlimitPlayers() {
		$this->db->from($this->tableName);
		return $this->runMultipleRow();
	}

	/**
	 * overview : search player sessions
	 *
	 * @param  int	$player_id
	 * @return array
	 */
	public function searchPlayerSession($player_id) {
        $this->load->library('lib_session_of_player');
        $_config4session_of_player = $this->lib_session_of_player->_extractConfigFromParams( $this->utils->getConfig('session_of_player') );

        if( $_config4session_of_player['sess_use_database'] ){
			// $this->load->library(array('session'));
			$this->db->from('ci_player_sessions')->where('player_id', $player_id);
			$sessions = array();
			$rows = $this->runMultipleRow();
			if (!empty($rows)) {
				foreach ($rows as $row) {
					// $user_data = $row->user_data;
					// if (!empty($user_data)) {
					// 	$data = $this->utils->unserializeSession($user_data);
					// if (!empty($data) && isset($data['player_id']) && $data['player_id'] == $player_id) {
					$sessions[] = $row->session_id;
					// }
					// }
				}
			}
			return $sessions;
        }else if( $_config4session_of_player['sess_use_redis'] ){
			$specialSessionTable='ci_player_sessions';
			return $this->searchSessionIdByObjectIdOnRedis($specialSessionTable, $player_id);
        }else if( $_config4session_of_player['sess_use_file'] ){
            return $this->lib_session_of_player->searchSessionIdByObjectIdOnFile($player_id);
        }else{
            $this->utils->error_log('wrong settings, no db, no redis and no file');
            return [];
		}
	}

	/**
	 * overview : kick out player
	 *
	 * @param  int	$player_id
	 * @return bool
	 */
	public function kickoutPlayer($player_id) {
        $this->load->library('lib_session_of_player');
        $_config4session_of_player = $this->lib_session_of_player->_extractConfigFromParams( $this->utils->getConfig('session_of_player') );

		if( $_config4session_of_player['sess_use_database'] ){
			$sessions = $this->searchPlayerSession($player_id);
			if (!empty($sessions)) {
				$this->db->where_in('session_id', $sessions)->delete('ci_player_sessions');
				return $this->db->affected_rows();
			}
        }else if( $_config4session_of_player['sess_use_redis'] ){
			//clear redis
			$specialSessionTable='ci_player_sessions';
			$this->deleteSessionsByObjectIdOnRedis($specialSessionTable, $player_id);
        }else if($_config4session_of_player['sess_use_file']){
            $this->lib_session_of_player->deleteSessionsByObjectIdOnFile($player_id);
        }else{
            $this->utils->error_log('wrong settings, no db, no redis and no file');
		}

		return true;
	}

	/**
	 * overview : count player session
	 *
	 * @param DateTime $from
	 * @return null
	 */
	public function countPlayerSession(\DateTime $from, $player_ids = null) {
        $this->load->library('lib_session_of_player');
        $_config4session_of_player = $this->lib_session_of_player->_extractConfigFromParams( $this->utils->getConfig('session_of_player') );

		if( $_config4session_of_player['sess_use_database'] ){
			$this->db->from('ci_player_sessions')->select('count(distinct player_id) as cnt', null, false);
			$this->db->where('last_activity >=', $from->getTimestamp());
	        if($player_ids){
	            $this->db->where_in('player_id', $player_ids);
	        }
			$cnt = $this->runOneRowOneField('cnt');

			return $cnt;
        }else if( $_config4session_of_player['sess_use_redis'] ){
			$specialSessionTable='ci_player_sessions';
			return $this->countSessionIdByObjectIdOnRedis($specialSessionTable, $from->getTimestamp(), $player_ids);
        }else if($_config4session_of_player['sess_use_file']){
            return $this->lib_session_of_player->countSessionIdByObjectIdOnFile($from->getTimestamp(), $player_ids);
        }else{
            $this->utils->error_log('wrong settings, no db, no redis and no file');
            $cnt = 0;
            return $cnt;
		}
	}

	/**
	 * overview : get player information by id
	 *
	 * @param  int	$player_id
	 * @return bool
	 */
	public function getPlayerInfoById($player_id, $wallet_type = 'wallet') {

		$this->db->select('p.*, t.*, pd.*, pa.*, p.playerId as playerId, p.status as playerStatus, p.createdOn as playerCreatedOn, t.createdOn as tagCreatedOn, t.status as tagStatus, pa.status as playerAccountStatus')
			->select('player_runtime.lastLoginTime as last_login_time, player_runtime.lastLogoutTime as last_logout_time, player_runtime.beforeLastLoginTime as before_last_login_time, newsletter_subscription, p.currency as playerCurrency');
		$this->db->from('player as p');
		$this->db->join('playerdetails as pd', 'p.playerId = pd.playerId', 'left');
		$this->db->join('playeraccount as pa', 'p.playerId = pa.playerId', 'left');
		$this->db->join('playertag as pt', 'p.playerId = pt.playerId', 'left');
		$this->db->join('tag as t', 'pt.tagId = t.tagId', 'left');
		$this->db->join('player_runtime', 'p.playerId = player_runtime.playerId', 'left');
		$this->db->where('p.playerId', $player_id);
		if(!empty($wallet_type)) {
			$this->db->where('pa.type', $wallet_type);
		}

		$query = $this->db->get();

		if (!$query->row_array()) {
			return false;
		} else {
			return $query->row_array();
		}
	}

	/**
	 * overview : check if transfer is enabled
	 *
	 * @param int	$playerId
	 * @return bool
	 */
	public function isEnabledTransfer($playerId) {
		if ($this->utils->getConfig('disable_transfer_on_credit_aff')) {
			$this->db->select('affiliateId')->from('player')->where('playerId', $playerId);
			$affId = $this->runOneRowOneField('affiliateId');
			//check affiliate
			$this->db->from('affiliates')->where('affiliateId', $affId)->where('status', '0');
			$bal = $this->runOneRowOneField('balance');
			$this->utils->debug_log('playerId', $playerId, 'affiliateId', $affId, 'bal', $bal);
			return $bal == 0 || $bal == null;
		}
		return true;
	}

	/**
	 * overview : get player list
	 *
	 * @param	int		$gamePlatformId
	 * @return 	array
	 */
	public function getPlayerListOnlyAvailBalOrExistGameLogs($gamePlatformId) {
		$rows = $this->getPlayerListOnlyAvailBal($gamePlatformId);
		$this->utils->debug_log('rows count', count($rows));

		$this->db->distinct()->select('player.*')->from('player')
			->join('total_player_game_hour', 'player.playerId=total_player_game_hour.player_id and total_player_game_hour.game_platform_id=' . $gamePlatformId);

		$gameRows = $this->runMultipleRow();
		$this->utils->debug_log('gameRows count', count($gameRows));
		if (empty($rows)) {
			$rows = array();
		}
		if (empty($gameRows)) {
			$gameRows = array();
		}
		return array_merge($rows, $gameRows);
	}

	/**
	 * overview : get player list only available balance or exist deposit
	 *
	 * @param int	$gamePlatformId
	 * @return array
	 */
	public function getPlayerListOnlyAvailBalOrExistDeposit($gamePlatformId) {
		$rows = $this->getPlayerListOnlyAvailBal($gamePlatformId);
		$this->utils->debug_log('rows count', count($rows));
		$this->db->distinct()->select('player.*')->from('player')
			->where('totalDepositAmount >= 0.009', null, false);

		$depositRows = $this->runMultipleRow();
		$this->utils->debug_log('depositRows count', count($depositRows));
		if (empty($rows)) {
			$rows = array();
		}
		if (empty($depositRows)) {
			$depositRows = array();
		}
		return array_merge($rows, $depositRows);
	}

	/**
	 * overview :get player list only available balance
	 *
	 * @param  int	$gamePlatformId
	 * @return array
	 */
	public function getPlayerListOnlyAvailBal($gamePlatformId) {
		$this->db->distinct()->select('player.*, playeraccount.totalBalanceAmount as bal')->from('player')
			->join('playeraccount', 'player.playerId=playeraccount.playerId')
			->where('typeId', $gamePlatformId)
			->where('playeraccount.totalBalanceAmount >= 0.009', null, false);

		// select count(*),sum(totalBalanceAmount) from playeraccount where typeId=1 and totalBalanceAmount>=0.01

		return $this->runMultipleRow();
	}

	/**
	 * overview : get unblocked players
	 *
	 * @param  int	$platformId
	 * @return array
	 */
	public function getUnblockedPlayers($platformId) {
		$this->db->select('player.*')->from($this->tableName)
			->join('game_provider_auth', 'game_provider_auth.player_id=player.playerId and game_provider_auth.game_provider_id=' . $platformId)
			->where('game_provider_auth.is_blocked', self::DB_FALSE)
			->where('game_provider_auth.register', self::DB_TRUE);
		return $this->runMultipleRow();
	}

	/**
	 * overview : get password by id
	 * @param  int	$playerId
	 * @return null|string
	 */
	public function getPasswordById($playerId) {
		$this->load->library('salt');
		$this->db->select('password')->from($this->tableName)->where('playerId', $playerId);
		$pwd = $this->runOneRowOneField('password');
		if (!empty($pwd)) {
			return $this->salt->decrypt($pwd, $this->getDeskeyOG());
		}
		return null;
	}

	/**
	 * Check if player's password is not set yet
	 * OGP-17826, for use with mobile number-only registration
	 * @param	int		$player_id		== player.playerId
	 * @return	bool	true if password not set, otherwise false
	 */
	public function isPasswordNotSetById($player_id) {
		$this->db->from($this->tableName)
			->select('password')
			->where('playerId', $player_id)
			->limit(1)
		;

		$passwd = $this->runOneRowOneField('password');

		return empty($passwd);
	}

	/**
	 * overview : get all username
	 *
	 * @param  string $where
	 * @return mixed
	 */
	public function getAllUsernames($where = null) {
		$this->db->select('username');
		$this->db->from('player');
		if ($where) {
			$this->db->where($where);
		}
		$this->db->order_by('username', 'asc');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * overview : get all username by game platform
	 *
	 * @param  int	$gamePlatformId
	 * @return array
	 */
	public function getAllUsernamesByGamePlatform($gamePlatformId) {
		$this->db->select('login_name');
		$this->db->from('game_provider_auth');
		$this->db->where('game_provider_id', $gamePlatformId)->where('register', self::DB_TRUE);
		$this->db->order_by('login_name', 'asc');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * overview : get all players by game platform
	 *
	 * @param  int	$gamePlatformId
	 * @return array
	 */
	public function getAllPlayersByGamePlatform($gamePlatformId) {
		$this->db->select('game_provider_auth.login_name, game_provider_auth.player_id, player.username ')
			->from('game_provider_auth')
			->join('player', 'player.playerId = game_provider_auth.player_id')
			->where('game_provider_auth.game_provider_id', $gamePlatformId)
			->where('game_provider_auth.register', self::DB_TRUE);

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * overview : get avail balance player list
	 *
	 * @return array
	 */
	public function getAvailBalancePlayerList() {
		$this->load->model('wallet_model');
		return $this->wallet_model->getAvailBalancePlayerList();
	}

	public function countWithdrawByStatusList($playerId, $status_list=array('paid', 'declined', 'approved'), $equal_flag=false) {
		$this->db->select('count(walletaccount.walletAccountId) cnt', false)->from('walletaccount')->where('playerId', $playerId)->where('transactionType', 'withdrawal');
		if($equal_flag) {
			$this->db->where_in('dwStatus', $status_list);
		}
		else {
			$this->db->where_not_in('dwStatus', $status_list);
		}

		return $this->runOneRowOneField('cnt');
	}

	/**
	 * overview : update wallet account player
	 *
	 * @return array
	 */
	public function updateWalletAccountPlayerId() {
		$sql = 'update walletaccount set playerId=(select playeraccount.playerId from playeraccount where playeraccount.playerAccountId=walletaccount.playerAccountId) where playerId is null';
		return $this->runRawUpdateInsertSQL($sql);
	}

	/**
	 * @param int	$playerId
	 * @param date	$startTime
	 * @return array
	 */
	public function getPlayerTotalPlayingTimeInMinutes($playerId, $startTime) {
		$sql = <<<EOD
SELECT MAX(minute) as total
FROM
	total_player_game_minute as tpgm
WHERE
	tpgm.player_id = ? AND tpgm.date_minute >= ?
EOD;

		$query = $this->db->query($sql, array(
			$playerId,
			$startTime,
		));
		return $this->getOneRow($query);
	}

	/**
	 * overview : will check if batch is already existing
	 *
	 * @param 	string	$name
	 * @return	array
	 */
	public function checkBatchExist($name) {
		$query = $this->db->query("SELECT * FROM player where username REGEXP '^" . $name . "[0-9]+$'");

		if (!empty($query->row_array())) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * overview : will get all player Levels
	 *
     * @param boolean $filter_deleted The vipsettingcashbackrule.deleted field has been Deprecated.
	 * @return array
	 */
	public function getAllPlayerLevels($only_active = false, $filter_deleted = false) {
		$this->db->select('vipsettingcashbackrule.vipLevel,
			vipsettingcashbackrule.vipLevelName,
			vipsettingcashbackrule.vipsettingcashbackruleId,
			vipsettingcashbackrule.upgradeAmount,
			vipsettingcashbackrule.downgradeAmount,
			vipsetting.vipSettingId,
			vipsetting.groupName')
			->from('vipsettingcashbackrule')
			->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId', 'left')
			->where('vipsetting.deleted', '0');
		if ($only_active) {
			$this->db->where('vipsetting.status', 'active');
		}
        if ($filter_deleted) {
            $this->db->where('vipsettingcashbackrule.deleted = 0', null, false);
        }
		$this->db->order_by('vipsettingcashbackrule.vipsettingcashbackruleId', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * Returns list of all vip level id/name
	 * @param	boolean	$return_kv	Return a KV object if true, or plain list otherwise
	 * @return	array
	 */
	public function getAllPlayerLevelsForOption($return_kv = false) {
		$this->db->select([
				'R.vipLevelName',
				'R.vipsettingcashbackruleId',
				'S.groupName'
			])
			->from('vipsettingcashbackrule AS R')
			->join('vipsetting AS S', 'S.vipSettingId = R.vipSettingId', 'left')
			->where('S.deleted', '0');
		$this->db->order_by('R.vipsettingcashbackruleId', 'ASC');

		$res = $this->runMultipleRowArray();

		$ret = [];
		foreach ($res as $row) {
			$entry = [
				'level_id'		=> $row['vipsettingcashbackruleId'] ,
				'level_name'	=> sprintf('%s - %s', lang($row['groupName']), lang($row['vipLevelName']))
			];
			if ($return_kv) {
				$ret[$row['vipsettingcashbackruleId']] = $entry;
			}
			else {
				$ret[] = $entry;
			}
		}

		return $ret;
	}

	/**
	 * overview : will get all dispatch account Levels
	 *
	 * @return array
	 */
	public function getAllDispatchAaccountLevels() {
		$this->db->select('dispatch_account_level.id,
			dispatch_account_level.level_name,
			dispatch_account_level.group_id,
			dispatch_account_group.group_name')
			->from('dispatch_account_level')
			->join('dispatch_account_group', 'dispatch_account_group.id = dispatch_account_level.group_id', 'left')
			->where('dispatch_account_level.status', '1');
		$this->db->order_by('dispatch_account_level.id', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * Will get affiliate code based on the passed parameter
	 *
	 * @param 	int
	 * @return	array
	 */
	public function getBatchCode() {
		// $query = $this->db->query("SELECT typeCode FROM batch ORDER BY batchId DESC");

		$this->db->select('typeCode');
		$this->db->from('batch');
		$this->db->order_by('batchId', 'desc');

		$query = $this->db->get();

		return $query->row_array();
	}

	/**
	 * overview : will get player username
	 *
	 * @param 	int		$player_id
	 * @return	array
	 */
	public function getPlayerUsername($player_id) {
		$this->db->select('username');
		$this->db->from('player as p');
		$this->db->where('p.playerId', $player_id);

		$query = $this->db->get();

		if (!$query->row_array()) {
			return false;
		} else {
			return $query->row_array();
		}
	}

	/**
	 * overview : get all enabled players
	 *
	 * @return array
	 */
	public function getAllEnabledPlayers($select = null, $except_levelId = null, $limit = null) {
		$this->db->from('player')
			->where('player.status', self::OLD_STATUS_ACTIVE)
			->where('player.deleted_at is null', null, false)
			->where('player.blocked', 0);
        if( ! is_null($except_levelId) ){
            $this->db->where('player.levelId != ', $except_levelId);
        }
        if(!is_null($select)){
            $this->db->select($select);
        }
        if( !is_null($limit)){
            $this->db->limit($limit);
        }
		return $this->runMultipleRow();
	}

	/**
	 * overview : create batch secure id
	 *
	 * @return bool
	 */
	public function batchCreateSecureId() {
		$this->db->from('player')->where('secure_id is null', null, false);
		$rows = $this->runMultipleRowArray();
		foreach ($rows as $row) {
			$secureId = $this->getSecureId('player', 'secure_id', true, 'P');
			$this->db->set('secure_id', $secureId)->where('playerId', $row['playerId'])
				->update('player');
		}

		return true;
	}

	/**
	 * overview : get player id by secure id
	 *
	 * @param int	$secureId
	 * @return null
	 */
	public function getPlayerIdBySecureId($secureId) {
		$this->db->from('player')->where('secure_id', $secureId)->limit(1);

		return $this->runOneRowOneField('playerId');
	}

	/**
	 * overview : get player secure id player id
	 *
	 * @param $username
	 * @return null
	 */
	public function getSecureIdByPlayerUsername($username) {
		$this->db->from('player')->where('username', $username)->limit(1);

		return $this->runOneRowOneField('secure_id');
	}

	/**
	 * overview : get friend referral bonus
	 *
	 * @param int	$player_id
	 * @param date	$start_date
	 * @param date	$end_date
	 * @return array
	 */
	public function getFriendReferralBonus($player_id, $start_date, $end_date) {

		$this->load->model(['transactions']);

		$sql = <<<EOD
select created_at transactionDatetime, player.username, playerfriendreferral.status, transactions.amount,
 playerfriendreferral.playerId
from playerfriendreferral join transactions on (playerfriendreferral.transactionId=transactions.id)
join player on player.playerId = playerfriendreferral.invitedPlayerId
where playerfriendreferral.playerId=?
and transactions.created_at>=?
and transactions.created_at<=?
EOD;
		$qry = $this->db->query($sql, [$player_id, $start_date, $end_date]);

		return $this->getMultipleRowArray($qry);

		// return $this->transactions->getReferralBonusList($player_id, $start_date, $end_date);

		// $query = $this->db->query("
		// 						SELECT
		// 						  `playerfriendreferraldetails`.`transactionDatetime`,
		// 						  `player`.`username`,
		// 						  `playerfriendreferral`.`status`,
		// 						  `playerfriendreferraldetails`.`amount`,
		// 						  `playerfriendreferral`.`playerId`
		// 						FROM
		// 						  `playerfriendreferral`
		// 						  INNER JOIN `playerfriendreferraldetails`
		// 						    ON (
		// 						      `playerfriendreferral`.`referralId` = `playerfriendreferraldetails`.`referralId`
		// 						    )
		// 						  INNER JOIN `player`
		// 						    ON (
		// 						      `player`.`playerId` = `playerfriendreferral`.`invitedPlayerId`
		// 						    )
		// 						WHERE ( `playerfriendreferral`.`playerId` = ?
		// 						AND `playerfriendreferraldetails`.transactionDatetime BETWEEN ?
		// 	  					AND ? )
		// 					", array($player_id, $start_date, $end_date));
		// $result = $query->result_array();
		// return $result;
	}

	/**
	 * overview : set language to player
	 *
	 * @param int	 $playerId
	 * @param string $language
	 * @return bool
	 */
	public function setLanguageToPlayer($playerId, $language) {
		switch ($language) {
				case Language_function::INT_LANG_ENGLISH:
					$lang = Language_function::PLAYER_LANG_ENGLISH;
					break;
				case Language_function::INT_LANG_CHINESE:
					$lang = Language_function::PLAYER_LANG_CHINESE;
					break;
				case Language_function::INT_LANG_INDONESIAN:
					$lang = Language_function::PLAYER_LANG_INDONESIAN;
					break;
				case Language_function::INT_LANG_VIETNAMESE:
					$lang = Language_function::PLAYER_LANG_VIETNAMESE;
					break;
				case Language_function::INT_LANG_KOREAN:
					$lang = Language_function::PLAYER_LANG_KOREAN;
					break;
				case Language_function::INT_LANG_THAI:
					$lang = Language_function::PLAYER_LANG_THAI;
					break;
				case Language_function::PLAYER_LANG_INDIA:
					$lang = Language_function::PLAYER_LANG_INDIA;
					break;
				case Language_function::PLAYER_LANG_PORTUGUESE:
					$lang = Language_function::PLAYER_LANG_PORTUGUESE;
					break;
                case Language_function::PLAYER_LANG_SPANISH:
                    $lang = Language_function::PLAYER_LANG_SPANISH;
                    break;
                case Language_function::PLAYER_LANG_KAZAKH:
                    $lang = Language_function::PLAYER_LANG_KAZAKH;
                    break;
				default:
					$lang = null;
					break;
			}

		$this->db->set('language', $lang)->where('playerId', $playerId);

		return $this->runAnyUpdate('playerdetails');
	}

	/**
	 * overview : update last activity
	 *
	 * @param int	 $player_id
	 * @param date|string   $lastActivityTime
	 * @param date   $lastLoginTime
	 * @param date	 $lastLogoutTime
	 * @param string $lastLoginIp
	 * @param date   $lastLogoutIp
	 * @return bool
	 */
	public function updateLastActivity($player_id, $lastActivityTime, $lastLoginTime = null, $lastLogoutTime = null,
		$lastLoginIp = null, $lastLogoutIp = null) {

		$this->utils->verbose_log('updateLastActivity player_id', $player_id, 'lastActivityTime', $lastActivityTime, 'lastLoginTime', $lastLoginTime,
			'lastLogoutTime', $lastLogoutTime, 'lastLoginIp', $lastLoginIp, 'lastLogoutIp', $lastLogoutIp);

		$isAllEmpty = empty($lastActivityTime) && empty($lastLoginTime) && empty($lastLogoutTime) && empty($lastLoginIp);

		if (!empty($player_id) && !$isAllEmpty) {

			$getLastActivity = $this->getLastActivity($player_id);
			$beforeLastLoginTime = null;
			if (!empty($getLastActivity)) {
				$beforeLastLoginTime = $getLastActivity->beforeLastLoginTime;
			}

			$this->db->from('player_runtime')->where('playerId', $player_id);
			if ($this->runExistsResult()) {
				$data = ['playerId' => $player_id];
				if (!empty($lastActivityTime)) {
					$this->db->set('lastActivityTime', $lastActivityTime);
				}
				if (!empty($lastLoginTime)) {
					$this->db->set('lastLoginTime', $lastLoginTime);
					$this->db->set('online', self::DB_TRUE);
					$data["online"]=self::DB_TRUE;

					if (strtotime($lastLoginTime) !== strtotime($beforeLastLoginTime)) {
						$this->db->set('beforeLastLoginTime', $lastLoginTime);
					}
				}
				if (!empty($lastLogoutTime)) {
					$this->db->set('lastLogoutTime', $lastLogoutTime);
					$this->db->set('online', self::DB_FALSE);
					$data["online"]=self::DB_FALSE;
				}
				if (!empty($lastLoginIp)) {
					$this->db->set('lastLoginIp', $lastLoginIp);
				}
				if (!empty($lastLogoutIp)) {
					$this->db->set('lastLogoutIp', $lastLogoutIp);
				}

				$this->db->where('playerId', $player_id);

				$this->runAnyUpdate('player_runtime');
				$this->updatePlayer($player_id, $data);
			} else {
				$data = ['playerId' => $player_id];
				if (!empty($lastActivityTime)) {
					$data['lastActivityTime'] = $lastActivityTime;
				}
				if (!empty($lastLoginTime)) {
					$data['lastLoginTime'] = $lastLoginTime;
					$data['online'] = self::DB_TRUE;
				}
				if (!empty($lastLogoutTime)) {
					$data['lastLogoutTime'] = $lastLogoutTime;
					$data['online'] = self::DB_FALSE;
				}
				if (!empty($lastLoginIp)) {
					$data['lastLoginIp'] = $lastLoginIp;
				}
				if (!empty($lastLogoutIp)) {
					$data['lastLogoutIp'] = $lastLogoutIp;
				}

				$this->updatePlayer($player_id, $data);

				if (!empty($lastLoginTime)) {
					#not update to player table
					$data['beforeLastLoginTime'] = $lastLoginTime;
				}
				$this->insertData('player_runtime', $data);
			}

		}

		return true;
	}

	public function getLastActivity($player_id){
        $this->db->from('player_runtime')->where('playerId', $player_id);

        $query = $this->db->get();

        return $this->getOneRow($query);
    }

	/**
	 * overview : update login information
	 *
	 * @param $player_id
	 * @param $record_ip
	 * @param $record_time
	 * @return null
	 */
	public function updateLoginInfo($player_id, $record_ip, $record_time) {
		$this->updateLastActivity($player_id, $this->utils->getNowForMysql(), $record_time, null, $record_ip);

		$token = null;
		if ($this->utils->isEnabledFeature('generate_player_token_login')) {
			$this->load->model(array('common_token'));
			$token = $this->common_token->getPlayerToken($player_id);
		}

		return $token;
	}

	/**
	 * overview : Update BeforeLastLoginTime
	 *
	 * @return bool
	 */
	public function batchUpdateBeforeLastLoginTime() {

		$this->db->select('*');
		$this->db->from('player_runtime');

		$query = $this->runMultipleRow();

		$this->utils->debug_log('----------------get player_runtime query ', $query);

		$sql = $this->db->last_query();
		$this->utils->debug_log('----------------get player_runtime sql ' . $sql);


		$update_data = [];

		foreach ($query as $player_runtime) {

			$playerId      = $player_runtime->playerId;
			$lastLoginTime = $player_runtime->lastLoginTime;

			if (!empty($playerId) && !empty($lastLoginTime)) {
				$update_data[] = array(
					'playerId' => $playerId,
					'beforeLastLoginTime' => $lastLoginTime,
				);
			}
		}

		$this->utils->debug_log('----------------batchUpdateBeforeLastLoginTime  update_data', $update_data);

		$this->startTrans();
		$this->db->update_batch('player_runtime', $update_data, 'playerId');
		$success = $this->endTransWithSucc();

		$sql2 = $this->db->last_query();
		$this->utils->debug_log('----------------get player_runtime sql2 ' . $sql2);
		return $success;
	}

	/**
	 * overview : insert player note
	 *
	 * @param  array	$data
	 * @return array
	 */
	public function insertPlayerNote($data) {
		return $this->db->insert('playernotes', $data);
	}

	/**
	 * @param  $playerId
	 * @param  $userId
	 * @param  $notes
	 * @return array
	 */
	public function addPlayerNote($playerId, $userId, $notes, $component = null) {
		// return $this->db->insert('playernotes', $data);
		$data = [
			'playerId' => $playerId,
			'userId' => $userId,
			'component' => $component,
			'createdOn' => $this->utils->getNowForMysql(),
			'updatedOn' => $this->utils->getNowForMysql(),
		];

		if($this->utils->getConfig('add_tag_remarks')){
			$data['notes']=$notes['notes'];
		}else{
			$data['notes']=$notes;
		}

		if(!empty($notes['tag_remark_id'])&&($this->utils->getConfig('add_tag_remarks')==true)){
			$data['tag_remark_id']=$notes['tag_remark_id'];
		}
		return $this->insertData('playernotes', $data);

	}

	/**
	 * overview : get player notes
	 *
	 * @param  int	$player_id
	 * @return bool
	 */
	public function getPlayerNotes($player_id, $component = null, $tag_remark_id = null) {

		// $sql = "SELECT pn.*, au.username FROM playernotes as pn LEFT JOIN adminusers AS au ON pn.userId = au.userId WHERE pn.playerId = ? AND pn.status = ?";
		// if($tag_remark_id!="all"&&$tag_remark_id!=null){
		// 	$sql .= " AND pn.tag_remark_id = $tag_remark_id";
		// }
        // $params = array($player_id, 1);
        // if($this->config->item('split_player_notes') && $component !== null) {
        //     $sql = "SELECT pn.*, au.username FROM playernotes as pn LEFT JOIN adminusers AS au ON pn.userId = au.userId WHERE pn.playerId = ? AND pn.status = ? AND ( component is NULL or component = ? )";
		// 	if($tag_remark_id!="all"&&$tag_remark_id!=""){
		// 		$sql .= " AND pn.tag_remark_id = $tag_remark_id";
		// 	}
        //     $params = array($player_id, 1, $component);
        // }
		// $sql ." ORDER BY pn.updatedOn";
		// $query = $this->db->query($sql, $params);

		if ($this->config->item('split_player_notes') && $component !== null) {
			$this->db->select('pn.*, au.username');
			$this->db->from('playernotes as pn');
			$this->db->join('adminusers as au', 'pn.userId = au.userId', 'left');
			$this->db->where('pn.playerId', $player_id);
			$this->db->where('pn.status',1 );
			$this->db->where(sprintf('(component IS NULL OR component = %s)', $component), '', false);
			// $this->db->where('component IS NULL', null, false);
			// $this->db->or_where('component', $component);
			if ($tag_remark_id != "all" && $tag_remark_id != "") {
				$this->db->where('pn.tag_remark_id', $tag_remark_id);
			}
			$this->db->order_by('pn.updatedOn');
		} else {
			$this->db
			->select('pn.*, au.username')
			->from('playernotes as pn')
			->join('adminusers as au', 'pn.userId = au.userId', 'left')
			->where('pn.status', 1);
			if ($tag_remark_id != "all" && $tag_remark_id != "") {
				$this->db->where('pn.tag_remark_id', $tag_remark_id);
			}
			if($player_id!==NULL){
			$this->db->where('pn.playerId', $player_id);
			}
		}
		$query = $this->db->get();


		if (!$query->row_array()) {
			return false;
		} else {
			return $query->result_array();
		}
	}

	/**
	 * overview : get player remarks
	 *
	 * @param  int
	 * @return array
	 */
	public function getTagRemarks() {
        $sql = "SELECT tr.* FROM tag_remarks as tr";
		$query = $this->db->query($sql);
		if (!$query->row_array()) {
			return false;
		} else {
			return $query->result_array();
		}
	}
	public function createTagRemarks($tagRemark) {
		if(!empty($tagRemark)){
			$data = [
				'tagRemarks' => $tagRemark,
				'created_at' => $this->utils->getNowForMysql(),
			];
			return $this->insertData('tag_remarks', $data);
		}else{
			$this->utils->error_log('cannot find name', $tagRemark);
			return false;
		}
	}



	/**
	 * overview : edit note
	 *
	 * @param int	$note_id
	 * @return bool
	 */
	public function editPlayerNote($note_id, $new_notes,$tag_remark_id) {
		$this->db->where('noteId', $note_id );
		//$this->db->delete('playernotes');
		if($this->utils->getConfig('add_tag_remarks')){
			$check = $this->db->update('playernotes',
				array(
					'updatedOn' => $this->utils->getNowForMysql(),
					'notes'     => $new_notes,
					'userId' => $this->authentication->getUserId(),
					'tag_remark_id'=>$tag_remark_id
				)
			);
			return $check;
		}

		$check = $this->db->update('playernotes',
			array(
				'updatedOn' => $this->utils->getNowForMysql(),
				'notes'     => $new_notes,
				'userId' => $this->authentication->getUserId(),
			)
		);

		return $check;
	}

	/**
	 * overview : delete note
	 *
	 * @param int	$note_id
	 * @return bool
	 */
	public function deleteNote($note_id) {
		$this->db->where('noteId', $note_id );
		//$this->db->delete('playernotes');
		$check = $this->db->update('playernotes',
			array(
				'deletedOn' => $this->utils->getNowForMysql(),
				'status'    => 0
			)
		);

		return $check;
	}

	/**
	 * overview :
	 * @param $player
	 * @return int|null
	 */
	public function import_win007_players($player) {
		if (!$this->usernameExist($player['username'])) {
			$player_id = $this->insertPlayer($player);
			if (!$this->getPlayerDetailsById($player_id)) {
				$data['firstName'] = $player['username'];
				// $data['lastName'] = $player['username'];
				$data['playerId'] = $player_id;
				$this->insertPlayerdetails($data);
			}
			return $player_id;
		}
		return null;
	}

	/**
	 * overview : block player with game
	 *
	 * @param  int	$playerId
	 * @return bool
	 */
	public function blockPlayerWithGame($playerId) {

		$this->db->where('playerId', $playerId)->set('blocked', 1);

		$success = $this->runAnyUpdate('player');

		$this->load->model(['game_provider_auth']);
		$success = $this->game_provider_auth->blockPlayersGameAccount($playerId) && $success;

		return $success;

	}

	/**
	 * overview : block player without game
	 *
	 * @param  int	$playerId
	 * @return bool
	 */
	public function blockPlayerWithoutGame($playerId) {

		$this->db->where('playerId', $playerId)->set('blocked', 1);

		$success = $this->runAnyUpdate('player');
		return $success;

	}

	/**
	 * overview : unblock player with game
	 *
	 * @param $playerId
	 * @return bool
	 */
	public function unblockPlayerWithGame($playerId) {

		$this->db->where('playerId', $playerId)->set('blocked', 0);

		$success = $this->runAnyUpdate('player');

		$this->load->model(['game_provider_auth']);
		$success = $this->game_provider_auth->unblockPlayersGameAccount($playerId) && $success;

		return $success;

	}

	/**
	 * overview : check player session timeout
	 *
	 * @param $sessionId
	 * @return bool
	 */
	public function isPlayerSessionTimeout($sessionId) {
        $this->load->library('lib_session_of_player');
        $_config4session_of_player = $this->lib_session_of_player->_extractConfigFromParams( $this->utils->getConfig('session_of_player') );

		$is_timeout = true;
		$timeout_seconds = $this->utils->getConfig('player_session_timeout_seconds');
        if( $_config4session_of_player['sess_use_database'] ){
			// $this->load->library(array('session'));
			$this->db->from('ci_player_sessions')->where('session_id', $sessionId)->limit(1);
			$row = $this->runOneRowArray();
			if (!empty($row)) {
				$last_activity = $row['last_activity'];

				// $this->utils->info_log('time', time(), 'last_activity', $last_activity, 'timeout_seconds', $timeout_seconds);

				$is_timeout = time() > $last_activity + $timeout_seconds;
			}
        }else if( $_config4session_of_player['sess_use_redis'] ){
			$specialSessionTable='ci_player_sessions';
			$data=$this->getSessionBySessionIdOnRedis($specialSessionTable, $sessionId);
			if(!empty($data)){
				// $this->utils->debug_log('get session', $data);
				$last_activity = $data['last_activity'];
				$is_timeout = time() > $last_activity + $timeout_seconds;
			}
        }else if($_config4session_of_player['sess_use_file']){
            // $data=$this->session->readBySessionIdFromFile($sessionId);
            $data=$this->lib_session_of_player->getSessionBySessionIdOnFile($sessionId);
            if(!empty($data)){
                $last_activity = $data['last_activity'];
				$is_timeout = time() > $last_activity + $timeout_seconds;
            }
		}else{ // write some error log to indicate the session setup is wrong
            $this->utils->error_log('wrong settings, no db, no redis and no file');
		}
		return $is_timeout;
	}

	/**
	 * overview : get language from player
	 *
	 * @param  int	$playerId
	 * @return string
	 */
	public function getLanguageFromPlayer($playerId) {
		$this->load->library('language_function');
		$this->db->select('language')->from('playerdetails')->where('playerId', $playerId);

		$lang = $this->runOneRowOneField('language');

		switch (ucfirst($lang)) {
			case Language_function::PLAYER_LANG_ENGLISH :
				return Language_function::PLAYER_LANG_ENGLISH;
				break;
			case Language_function::PLAYER_LANG_CHINESE :
				return Language_function::PLAYER_LANG_CHINESE;
				break;
			case Language_function::PLAYER_LANG_INDONESIAN :
				return Language_function::PLAYER_LANG_INDONESIAN;
				break;
			case Language_function::PLAYER_LANG_VIETNAMESE :
				return Language_function::PLAYER_LANG_VIETNAMESE;
				break;
			case Language_function::PLAYER_LANG_KOREAN :
				return Language_function::PLAYER_LANG_KOREAN;
				break;
			case Language_function::PLAYER_LANG_THAI :
				return Language_function::PLAYER_LANG_THAI;
				break;
		}

		return false;
	}

	/**
	 * overview : get bank details
	 *
	 * @param int	$player_id
	 * @return array
	 */
	public function getBankDetails($player_id) {

		$this->load->model(['playerbankdetails']);

		return $this->playerbankdetails->getBankDetails($player_id);
	}

	/**
	 * overview : enable cash back by player id
	 *
	 * @param $playerId
	 * @return bool
	 */
	public function enableCashbackByPlayerId($playerId) {
		$this->db->where('playerId', $playerId)->set(['disabled_cashback' => self::DB_FALSE]);
		return $this->runAnyUpdate('player');
	}

	/**
	 * overview : disable cash back by player id
	 *
	 * @param $playerId
	 * @return bool
	 */
	public function disableCashbackByPlayerId($playerId) {
		$this->db->where('playerId', $playerId)->set(['disabled_cashback' => self::DB_TRUE]);
		return $this->runAnyUpdate('player');
	}

	/**
	 * overview : enable promotions by player
	 *
	 * @param $playerId
	 * @return bool
	 */
	public function enablePromotionByPlayerId($playerId) {
		$this->db->where('playerId', $playerId)->set(['disabled_promotion' => self::DB_FALSE]);
		return $this->runAnyUpdate('player');
	}

	/**
	 * overview : disable promotions by player id
	 *
	 * @param $playerId
	 * @return bool
	 */
	public function disablePromotionByPlayerId($playerId) {
		$this->db->where('playerId', $playerId)->set(['disabled_promotion' => self::DB_TRUE]);
		return $this->runAnyUpdate('player');
	}

	/**
	 * overview : check if cash back is disabled
	 *
	 * @param int	$playerId
	 * @return bool
	 */
	public function isDisabledCashback($playerId) {
		$this->db->select('disabled_cashback')->from('player')->where('playerId', $playerId);

		return $this->runOneRowOneField('disabled_cashback') == '1';
	}

	/**
	 * overview : check if disabled promotion
	 *
	 * @param int	$playerId
	 * @return bool
	 */
	public function isDisabledPromotion($playerId) {
		$this->db->select('disabled_promotion')->from('player')->where('playerId', $playerId);

		return $this->runOneRowOneField('disabled_promotion') == '1';
	}

	/**
	 * overview : get player current level
	 *
	 * @param $playerId
	 * @return mixed
	 */
	public function getPlayerCurrentLevel($playerId) {
		$this->db->select('vipsettingcashbackrule.vipLevel,
			vipsettingcashbackrule.vipLevelName,
			vipsettingcashbackrule.vipsettingcashbackruleId,
			vipsetting.vipSettingId,
			vipsetting.groupName')
			->from('playerlevel')
			->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = playerlevel.playerGroupId', 'left')
			->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId', 'left');
		$this->db->where('playerlevel.playerId', $playerId);
		$this->db->where('vipsetting.deleted', self::DB_FALSE);
		$query = $this->db->get();

		return $query->result_array();
	}

	public function getPlayerCurrentLevelForExport($playerId, $return_group_level_str = true) {
		$this->db->select('R.vipLevel,
			R.vipLevelName,
			R.vipsettingcashbackruleId,
			V.vipSettingId,
			V.groupName')
			->from('playerlevel AS L')
			->join('vipsettingcashbackrule AS R', 'R.vipsettingcashbackruleId = L.playerGroupId', 'left')
			->join('vipsetting AS V', 'V.vipSettingId = R.vipSettingId', 'left');
		$this->db->where('L.playerId', $playerId);
		// $query = $this->db->getOne();
		// return $query->result_array();
		$res = $this->runOneRowArray();
		if ($return_group_level_str) {
			$group_level_str = sprintf("%s - %s", lang($res['groupName']), lang($res['vipLevelName']));
			return $group_level_str;
		}
		return $res;
	}

	/**
	 * overview : get player current level
	 *
	 * @param $player_id
	 * @return mixed
	 */
	public function getCurrentDispatchAccountLevel($player_id) {
		$this->db->select('dispatch_account_level.level_name,
			dispatch_account_level.id,
			dispatch_account_level.group_id,
			dispatch_account_group.group_name')
			->from('player')
			->join('dispatch_account_level', 'dispatch_account_level.id = player.dispatch_account_level_id', 'left')
			->join('dispatch_account_group', 'dispatch_account_group.id = dispatch_account_level.group_id', 'left');
		$this->db->where('player.playerId', $player_id);
		$query = $this->db->get();
		return $query->result_array();
	}

	public function enabledCreditMode($playerId) {

		return $this->updatePlayer($playerId, array('credit_mode' => true));
	}

	public function disabledCreditMode($playerId) {

		return $this->updatePlayer($playerId, array('credit_mode' => false));
	}

	/**
	 * overview : check player Credit Mode
	 *
	 * @param  int	$playerId
	 * @return bool
	 */
	public function isEnabledCreditMode($playerId) {
		$this->db->select('credit_mode')->from($this->tableName)->where('playerId', $playerId);
		$credit_mode = $this->runOneRowOneField('credit_mode');

		return $credit_mode == self::DB_TRUE;
	}

	public function isUnderAgent($playerId) {
		$this->db->select('agent_id')->from('player')->where('playerId', $playerId);

		return !empty($this->runOneRowOneField('agent_id'));
	}

	public function getAgentByPlayerId($playerId) {
		$this->db->from('player AS P')
			->join('agency_agents AS A', 'P.agent_id = A.agent_id', 'left')
			->select('A.*')
			->where([ 'P.playerId' => $playerId ])
		;

		$res = $this->runOneRowArray();

		$this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());

		return $res;
	}

	/**
	 * overview : update phone verified flag
	 *
	 * @param  int	$playerId
	 * @return bool
	 */
	public function verifyPhone($playerId) {
		$this->db->where('playerId', $playerId);

		return $this->runUpdate(array('verified_phone' => self::DB_TRUE));
	}

	/**
	 * validate withdrawal password
	 *
	 * @param  string $playerId
	 * @param  string $password
	 * @return bool
	 */
	public function validateWithdrawalPassword($playerId, $password) {
		$success = false;

		$this->db->select('withdraw_password, withdraw_password_md5')->from('player')
			->where('playerId', $playerId);
		$row = $this->runOneRowArray();
		$withdraw_password = $row['withdraw_password'];
		if (!empty($withdraw_password)) {
			//validate password first
			//$success=$this->utils->validate_password($withdraw_password, $password);
			if ($withdraw_password == $password) {
				$success = $password;
			}
		} else {
			//try withdraw_password_md5
			$withdraw_password_md5 = $row['withdraw_password_md5'];
			if (!empty($withdraw_password_md5)) {
				$success = $this->utils->validate_password_md5($withdraw_password_md5, $password);
				if ($success) {
					//change to our encrypt
					$encrypted_password = $this->utils->encodePassword($password);
					$this->db->set('withdraw_password', $encrypted_password)->where('playerId', $playerId);
					$this->runAnyUpdate('player');
				}
			}
		}

		return $success;
	}

	/**
	 * get email in email table
	 *
	 * @return	array
	 */
	public function resetPassword($player_id, $data) {
		$this->db->from($this->tableName);
		$this->db->where('playerId', $player_id);
		$this->db->update('player', $data);
	}

	public function registrationIP($playerId) {
		$this->db->select('playerdetails.registrationIp')->from('playerdetails');
		$this->db->where('playerdetails.playerId', $playerId);
		$this->db->join('player', 'player.playerId = playerdetails.playerId', 'left');

		$ip = $this->runOneRowOneField('registrationIp');

		$class = $this->getPlayerWithSameIp($ip) >= 2 ? 'text-danger' : 'text-info';
		$cityCountry = ' (' . implode(', ', $this->utils->getIpCityAndCountry($ip)) . ')';

		return array('ip' => $ip, 'cityCountry' => $cityCountry, 'text_color' => $class);
	}

	public function getLastLoginIP($playerId) {
		$this->db->select('player_login_report.ip')->from('player_login_report');
		$this->db->where('player_id', $playerId);
		$this->db->order_by('create_at', 'desc');

		$ip = $this->runOneRowOneField('ip');

		$class = $this->getPlayerLoginReportWithSameIp($ip, $playerId) > 0 ? 'text-danger' : 'text-info';
		$cityCountry = ' (' . implode(', ', $this->utils->getIpCityAndCountry($ip)) . ')';

		return array('ip' => $ip, 'cityCountry' => $cityCountry, 'text_color' => $class);
	}

	public function registrationIpfunction($playerId, $use_old_function = false) {
		$this->db->select('playerdetails.registrationIp')->from('playerdetails');
		$this->db->where('playerdetails.playerId', $playerId);
		$this->db->join('player', 'player.playerId = playerdetails.playerId', 'left');

		$ip = $this->runOneRowOneField('registrationIp');

		$class = $this->getPlayerWithSameIp($ip) >= 2 ? 'text-danger' : 'text-info';
		$noneFormatIp = $this->utils->getIpCityAndCountry($ip, $use_old_function);
		$cityCountry = ' (' . implode(', ', $noneFormatIp) . ')';

		return array('ip' => $ip, 'cityCountry' => $cityCountry, 'text_color' => $class, 'city' => $noneFormatIp[0], 'country' => $noneFormatIp[1]);
	}

	public function getPlayerWithSameIp($registrationIp) {
		$this->db->select('count(playerdetails.registrationIp) as cnt', false)->from('playerdetails');
        $this->db->join('player', 'player.playerId = playerdetails.playerId', 'right');
		$this->db->where('playerdetails.registrationIp', $registrationIp);
		return $this->runOneRowOneField('cnt');
		// return count($this->db->get()->result());
	}

	public function getPlayerLoginReportWithSameIp($ip, $playerId) {
		$allow_same_last_login_ip_days = !empty($this->utils->getConfig('allow_same_last_login_ip_days')) ? $this->utils->getConfig('allow_same_last_login_ip_days') : '7';
		$this->db->select('count(player_login_report.ip) as cnt', false)->from('player_login_report');
		$this->db->where('player_login_report.ip', $ip);
		$this->db->where('player_id !=', $playerId);
		$this->db->where('create_at >=', date('Y-m-d H:i:s', strtotime('-'.$allow_same_last_login_ip_days.' day')));
		return $this->runOneRowOneField('cnt');
	}

	public function existCommonRegistrationIP($firstPlayerId, $secondPlayerId) {
		$result = false;
		$firstPlayer = $this->registrationIP($firstPlayerId);
		$secondPlayer = $this->registrationIP($secondPlayerId);

		$firstPlayerIp = !empty($firstPlayer['ip']) ? (string) $firstPlayer['ip'] : '';
		$secondPlayerIp = !empty($secondPlayer['ip']) ? (string) $secondPlayer['ip'] : '';

		if(!empty($firstPlayerIp) && !empty($secondPlayerIp)){
			$result = ($firstPlayerIp === $secondPlayerIp);
		}

		$this->utils->debug_log(__METHOD__ .' result', ['firstPlayerIp' => $firstPlayerIp, 'secondPlayerIp', $secondPlayerIp, 'result' => $result]);
		return $result;

	}
	public function insertPasswordHistory($playerId, $action, $newPassword) {

		$password = $this->getPasswordById($playerId);
		if(!empty($password)) {
			$data = array(
				'player_id' => $playerId,
				'action' => $action,
				'current_password' => $this->utils->encodePassword($password),
				'new_password' => $newPassword,
				'updated_at' => $this->utils->getNowForMysql(),
			);

			$this->db->insert('player_password_history', $data);
		}
	}

	/**
	 * overview : check if player password is reset by admin
	 * @param $playerId
	 * @return bool
	 */
	public function isResetPasswordByAdmin($playerId) {
		$this->db->select('action')->from('player_password_history')->where('player_id', $playerId)->order_by('player_password_history.updated_at', 'desc');;
		$action = $this->runOneRowOneField('action');
		return $action == self::RESET_PASSWORD_BY_ADMIN;
	}

	public function getLastResetPassword($playerId) {
		$this->db->select('player_password_history.*')
		         ->from('player_password_history')
				 ->where('player_id', $playerId)
		         ->order_by('updated_at', 'desc');
		return $this->runOneRowArray();
	}

	public function updatePlayerPasswordHistory($id, $is_message_notify, $message_id) {
		$this->db->where('id', $id)
				  ->set('is_message_notify', $is_message_notify)
				  ->set('messageId', $message_id);
		return $this->runAnyUpdate('player_password_history');
	}

	public function generateReferralCode() {

		$isUnique = false;
		$invitationCode = null;

		while (!$isUnique) {
			$invitationCode = random_string();
			if ($this->invitationCodeIsUnique($invitationCode)) {
				$isUnique = true;
				break;
			}
		}

		return $invitationCode;

	}

	public function invitationCodeIsUnique($invitationCode) {

		$this->db->where('invitationCode', $invitationCode);
		$query = $this->db->get($this->tableName);

		if ($query->num_rows() > 0) {
			return false;
		}
		return true;
	}

	public function getPlayerWithoutRefCode($dateTimeFromStr, $dateTimeToStr) {

		$sql = "SELECT playerId FROM player WHERE invitationCode = '0' AND createdOn >= ? AND createdOn <= ?";

		$query = $this->db->query($sql, array($dateTimeFromStr, $dateTimeToStr));
		$rows = $query->result_array();

		$arr = array();
		foreach ($rows as $row) {
			array_push($arr, $row['playerId']);
		}
		return $arr;
	}

	public function addReferralCodeToPlayer($playerId, $invitationCode) {
		$this->db->where('playerId', $playerId);
		$this->db->set('invitationCode', $invitationCode);
		$this->db->update($this->tableName);
		if ($this->db->_error_message()) {
			return false;
		} else {
			return true;
		}
	}

	public function getPlayerLogInTime($playerId = null) {
		if (empty($playerId)) {
			$playerId = $this->authentication->getPlayerId();
		}
		$this->db->select('lastLoginTime')
			->from('player_runtime')
			->where('playerId', $playerId);

		return $this->runOneRowArray()['lastLoginTime'];
	}

	public function getPlayerLogInIp($playerId = null) {
		if (empty($playerId)) {
			$playerId = $this->authentication->getPlayerId();
		}
		$this->db->select('lastLoginIp')
			->from('player_runtime')
			->where('playerId', $playerId);

		return $this->runOneRowArray()['lastLoginIp'];
	}

	public function updateVerifyCode($playerId, $code) {

		return $this->db->where('playerId', $playerId)
			->update('player', array(
				'verify' => $code,
			));

	}

	public function getVerifyCode($playerId, $code) {

		$qobj = $this->db->from('player')
			->where('playerId', $playerId)
			->where('verify', $code)
			->get();

		return (count($qobj->row()) > 0) ? true : false;

	}

	public function updateEmailStatusToVerified($playerId) {
		$this->db->where('playerId', $playerId);

		return $this->runUpdate(array('verified_email' => self::DB_TRUE));
	}

	public function updatePlayerEmail($playerId, $email) {
		$updateset = [
			'email'				=> $email ,
			'verified_email'	=> self::DB_FALSE
		];

		$res = $this->db->where([ $this->idField => $playerId ])
			->set($updateset)->update($this->tableName)
		;

		return $res;
	}

	public function updatePhoneStatusToVerified($playerId) {
		$this->db->where('playerId', $playerId);

		return $this->runUpdate(array('verified_phone' => self::DB_TRUE));
	}

	public function getPhoneNumbersByIds($ids) {
		$this->db->select('CASE WHEN playerdetails.dialing_code is null THEN playerdetails.contactNumber ELSE concat(playerdetails.dialing_code,"|",playerdetails.contactNumber) END as contactNumber, player.playerId, player.username',false)
			->from('player')->join('playerdetails', 'playerdetails.playerId=player.playerId')
			->where_in('player.playerId', $ids)
			->where('playerdetails.contactNumber is not null', null, false);

		return $this->runMultipleRowArray();
	}

	public function getEmailsByPlayerIds($ids, $verified_email = false) {
		$this->db->select('username, email, verified_email',false)
			->from('player')
			->where_in('player.playerId', $ids);
		if($verified_email) {
			$this->db->where('verified_email', self::EMAIL_IS_VERIFIED);
		}
		return $this->runMultipleRowArray();
	}

	public function getAllPlayerEmails($verified_email = false){
		$this->db->select('username, email, verified_email', false)
            ->from('player');
        if ($verified_email) {
            $this->db->where('verified_email', self::EMAIL_IS_VERIFIED);
        }
        return $this->runMultipleRowArray();
	}

	public function getPlayerUsernameByEmail($email) {

		if (!empty($email)) {
			$this->db->select('username');
			$this->db->where('email', $email);
			$qry = $this->db->get($this->tableName);
			return $this->runMultipleRowOneFieldArray('username');
		}
		return null;
	}

	public function getPhoneNumberByPlayerId($playerId) {

		$this->db->select('contactNumber')
			->from('playerdetails')
			->where('playerId', $playerId);

		$query = $this->db->get();
		return $query->num_rows();
	}

	public function checkPhoneNumberIfCorrect($playerId, $contactNumber) {

		$this->db->select('contactNumber')
			->from('playerdetails')
			->where('playerId', $playerId)
			->where('contactNumber', $contactNumber);

		$query = $this->db->get();
		return $query->num_rows();

	}

	/**
	 * Returns playerdetails.contactNumber for given PlayerId
	 * @param	int		$playerId	== player.playerId
	 * @return	numeric string		== playerdetails.contactNumber
	 */
	public function getPlayerContactNumber($playerId) {
		$this->db->from('playerdetails')
			->where('playerId', $playerId)
			->select('contactNumber');

		$res = $this->runOneRowOneField('contactNumber');

		return $res;
	}

	public function getPlayerContactNumberByUsername($username) {
		$this->db->from('player')
			->join('playerdetails', 'playerdetails.playerId=player.playerId')
			->where('player.username', $username);
		$this->ignoreDeleted('player.deleted_at');

		$res = $this->runOneRowOneField('contactNumber');

		return $res;
	}

	public function getPlayerPoints($playerId) {
		$this->db->select('point');
		$this->db->where('playerId', $playerId);
		$qry = $this->db->get($this->tableName);
		return $this->getOneRowOneField($qry, 'point');
	}

	public function getEnabledPlayers($specifiedPlayerIds = [], $filterLowestLevels = false) {
        $this->load->model(array('group_level'));

        if($filterLowestLevels){
            $levelIdList = [];
            $_rows=$this->group_level->getLowestLevelOfLevels('lowest');
            if( !empty($_rows)){
                $levelIdList = array_column($_rows, 'vipsettingcashbackruleId');
            }
        }

		$this->db->select('playerId,username')
			->from('player')
			->where('player.status', self::OLD_STATUS_ACTIVE)
			->where('player.deleted_at is null', null, false)
			->where('player.blocked', 0);

        if( ! empty($specifiedPlayerIds) ){
            $this->db->where_in('playerId', $specifiedPlayerIds);
        }

        if($filterLowestLevels){
            // $this->db->join('vipsettingcashbackrule', 'player.levelId = vipsettingcashbackrule.vipsettingcashbackruleId');
            if(!empty($levelIdList)){
                // for filter, player.levelId F.K. vipsettingcashbackruleId
                $this->db->where_not_in('player.levelId', $levelIdList);

                // for filter, playerlevel.playerId with playerGroupId F.K. vipsettingcashbackruleId
                $_levelIdList = implode(', ', $levelIdList);
                $this->db->where("player.playerId NOT IN( SELECT distinct `playerId` FROM `playerlevel` WHERE `playerGroupId` IN ( {$_levelIdList} ) )", null, false);
            }
        }
        $rlt = $this->runMultipleRow();
		return $rlt;
	}

	public function getAllPubNews($sort) {
		$this->db->select('cmsnews.title, cmsnews.newsId, cmsnews.content, cmsnews.date, cmsnews.language');
		$this->db->from('cmsnews');
		if (!empty($sort)) {
			$this->db->order_by($sort);
		}

		return $this->runMultipleRowArray();
	}

	/**
	 * overview : will get player id by referal code
	 *
	 * @param 	int		$referal_code
	 * @return	array
	 */
	public function getPlayerIdByReferralCode($referal_code) {
		$this->db->select('playerId');
		$this->db->from('player as p');
		$this->db->where('p.invitationCode', $referal_code);

		$query = $this->db->get();

		if (!$query->row_array()) {
			return false;
		} else {
			return $query->row_array();
		}
	}

	public function generateUsernameByMobileNumber($contact_number, $random_size) {

		do {
			$username = strtolower(random_string('alpha', $random_size)) . substr($contact_number, -4);
		} while (!$this->usernameIsUnique($username));

		return $username;
	}

	public function usernameIsUnique($username) {

		$this->db->where('username', $username)->from('player');

		return !$this->runExistsResult();
	}

	public function getRandomVerificationCode() {
		return random_string('alnum', 16);
	}

	public function checkContactExist($contactNumber) {
		$this->db->from('player')
			->join('playerdetails', 'playerdetails.playerId=player.playerId')
			->where('contactNumber', $contactNumber);
		$this->ignoreDeleted('player.deleted_at');

		$contactExists = $this->runExistsResult();

		if ($contactExists) {
			$configEnabled = $this->utils->getConfig('enable_player_to_register_with_existing_contactnumber');
			if ($configEnabled) {
				return !$this->verifyContactAvailable($contactNumber);
			} else {
				return true; // Cannot use existing contact number
			}
		} else {
			return false; // Contact number doesn't exist, can be used
		}
	}

	public function verifyContactAvailable($contactNumber, $offset = 0){
		$daysThreshold = $this->utils->getConfig('duplicate_contactnumber_day_limit');
		$currentDate = date('Y-m-d');

		$this->db->select('*');
		$this->db->from('player');
		$this->db->join('playerdetails', 'playerdetails.playerId = player.playerId');
		$this->db->where('contactNumber', $contactNumber);
		$this->db->where('player.deleted_at IS NULL');
		$this->db->order_by('player.createdOn', 'DESC');
		$this->db->limit(1, $offset);

		$query = $this->db->get();
		$result = $query->row();

		$this->utils->debug_log(__METHOD__, 'contactNumber', $contactNumber, 'offset', $offset, 'result', $result);

		if ($result) {

			$createdOnDate = date('Y-m-d', strtotime($result->createdOn));
			$interval = date_diff(date_create($createdOnDate), date_create($currentDate));
			$daysDifference = $interval->format('%a');

			$this->utils->debug_log(__METHOD__, 'createdOnDate', $createdOnDate, 'currentDate', $currentDate, 'interval', $interval, 'daysDifference', $daysDifference, 'daysThreshold', $daysThreshold);

			if ($daysDifference >= $daysThreshold) {
				return true; // Contact number can be used again
			} else {
				return false; // Contact number cannot be used by other players
			}
		} else {
			return false; // Contact number doesn't exist, can be used
		}
	}

	public function getDuplicateContactNumberUser($contactNumber, $playerId = null){

		$this->db->select('username');
		$this->db->from('player');
		$this->db->join('playerdetails', 'playerdetails.playerId = player.playerId');
		$this->db->where('contactNumber', $contactNumber);
		$this->ignoreDeleted('player.deleted_at');

		if (!empty($playerId)) {
			$this->db->where('player.playerId != ', $playerId);
		}

		$result = $this->runMultipleRowArray();
		$this->utils->printLastSQL();

		$usernames = array_column($result, 'username');
		$this->utils->debug_log(__METHOD__ . $playerId,'usernames',$usernames);
		return $usernames;
	}

	public function checkCpfNumberExist($pix_number) {
		$this->db->from('player')
			->join('playerdetails', 'playerdetails.playerId=player.playerId')
			->where('pix_number', $pix_number);
		$this->ignoreDeleted('player.deleted_at');
		return $this->runExistsResult();
	}

	public function checkImAccountExist($number, $currentField, $compareField) {
		$this->db->from('player')
			->join('playerdetails', 'playerdetails.playerId=player.playerId')
			->where('imAccount'.$currentField, $number)
			->or_where('imAccount'.$compareField, $number);
		$this->ignoreDeleted('player.deleted_at');
		return $this->runExistsResult();
	}

	public function checkEmailExist($email) {
		$this->db->from("player")->where('email', $email);
		$this->ignoreDeleted('player.deleted_at');
		return $this->runExistsResult();
	}

	public function checkUsernameExist($username) {
		$this->db->from("player")->where('username', $username);
		$this->ignoreDeleted('player.deleted_at');
		return $this->runExistsResult();
	}

	public function isBlockedByMobileNumber($mobileNumber) {

		$this->db->select('player.blocked')->from('player')
			->join('playerdetails', 'playerdetails.playerId=player.playerId')
			->where('playerdetails.contactNumber', $mobileNumber);
		$blocked = $this->runOneRowOneField('blocked');
		// $this->utils->debug_log('blocked', $blocked, 'playerId', $playerId);
		return $blocked == '1';

	}

	public function getPlayerLoginInfoByNumber($contactNumber) {
		$this->db->select('player.*')->from('player')
			->join('playerdetails', 'playerdetails.playerId=player.playerId')
			->where('playerdetails.contactNumber', $contactNumber);

		$this->ignoreDeleted('player.deleted_at');

		return $this->runOneRowArray();
	}

    public function getPlayersLoginInfoByNumberAndPassword($contactNumber, $password) {
        $passwordIndb = $this->utils->encodePassword($password);
		$this->db->select('player.*')->from('player')
			->join('playerdetails', 'playerdetails.playerId=player.playerId')
			->where('playerdetails.contactNumber', $contactNumber)
            ->where('player.password', $passwordIndb);

		$this->ignoreDeleted('player.deleted_at');
        $result = $this->runMultipleRowArray();
        return $result;
	}

	/**
	 * Will get player account given the Id
	 *
	 * @param 	int
	 * @return	array
	 */
	public function getPlayerAccountByPlayerIdOnly($player_id) {
		$query = $this->db->select('playerAccountId')
						  ->from('player as p')
						  ->join('playeraccount as pa', 'p.playerId = pa.playerId')
						  ->where('p.playerId', $player_id)
						  ->where('type', 'wallet')
						  ->get();

		if (!$query->row_array()) {
			return false;
		} else {
			return $query->row_array();
		}
	}

	public function getPlayerMyPromo() {

	}

	public function update_secret_question($playerId,$data){
		$this->db->where('playerId', $playerId);
		return $this->runUpdate($data);
	}


	public function getPlayerIdByPlayerName($playerName) {
		$this->db->select('playerId')->from('player');
		$this->db->where('username', $playerName);
		return $this->runOneRowOneField('playerId');
	}

	/**
	 * overview : get player referral
	 *
	 * @param  int	$player_id
	 * @return array
	 */
	public function getPlayerReferralWLimit($playerId, $limit, $offset, $search, $is_count = false) {

		if ($is_count) {
			$this->db->select('count(player.refereePlayerId) as cnt');
		} else {
			$this->db->select(array(
				'player.username',
				'player.createdOn',
				'SUM(total_player_game_day.betting_amount) totalBettingAmount',
				"SUM(transactions.amount) amount",
			));
		}

		if (isset($search['from'], $search['to'])) {
			$this->db->where("player.createdOn >=", $search['from']);
			$this->db->where("player.createdOn <=", $search['to']);
		}

		if (isset($limit, $offset)) {
			$this->db->limit($limit, $offset);
		}

		$this->db->from('player');
		$this->db->where('refereePlayerId', $playerId);
		$this->db->join('total_player_game_day', 'total_player_game_day.player_id = player.playerId', 'left');
		$this->db->join('transactions', 'transactions.to_type = ' . Transactions::PLAYER . ' AND transactions.transaction_type = ' . Transactions::PLAYER_REFER_BONUS . ' AND transactions.status = ' . Transactions::APPROVED . ' AND transactions.to_id = player.playerId', 'left', false);

		$this->db->group_by('player.refereePlayerId');
		$this->db->order_by('player.createdOn', 'desc');
		$query = $this->db->get();


		if ($is_count) {
			return $query->num_rows();
			//return $this->getOneRowOneField($query, 'cnt');
		} else {
			return $query->result_array();
		}

		return null;
	}

	/**
	 * overview : enable withdrawal by player
	 *
	 * @param $playerId
	 * @return bool
	 */
	public function enableWithdrawalByPlayerId($playerId) {
		$this->db->where('playerId', $playerId)->set(['enabled_withdrawal' => self::DB_TRUE]);
		return $this->runAnyUpdate('player');
	}

	/**
	 * overview : disable withdrawal by player id
	 *
	 * @param $playerId
	 * @return bool
	 */
	public function disableWithdrawalByPlayerId($playerId) {
		$this->db->where('playerId', $playerId)->set(['enabled_withdrawal' => self::DB_FALSE]);
		return $this->runAnyUpdate('player');
	}

	public function getSecureId($tableName, $fldName, $needUnique = true, $prefix = null, $random_length = 12)
	{
		return parent::getSecureId($tableName, $fldName, $needUnique, $prefix, $random_length);
	}

	/**
	 * Update player contact number, and set number verified status to true
	 * @param	int		$playerId	== playerdetails.playerId
	 * @param	string	$number		phone number, numeric string
	 * @return	bool	true if update is successful, otherwise false
	 */
	public function updateAndVerifyContactNumber($playerId, $number) {
		$this->updatePlayerdetails($playerId, [ 'contactNumber' => $number ]);
		$res = $this->verifyPhone($playerId);

		return $res;
	}

	/**
	 * overview : get total wrong login attempt by playerId
	 *
	 * @param $playerId
	 * @return null
	 */
	public function getPlayerTotalWrongLoginAttempt($playerId) {
		// $this->utils->debug_log("Player Model getPlayerIdByUsername: ", $username, " Table: ", $this->tableName);
		if (!empty($playerId)) {
			$this->db->select('total_wrong_login_attempt');
			$this->db->where('playerId', $playerId);
			$qry = $this->db->get($this->tableName);
			return $this->getOneRowOneField($qry, 'total_wrong_login_attempt');
		}
		return null;
	}

	/**
	 * overview : update total wrong login attempt by playerId
	 *
	 * @param $playerId
	 * @return bool
	 */
	public function updatePlayerTotalWrongLoginAttempt($playerId,$attempt = 0) {
		$this->db->where('playerId', $playerId)->set(['total_wrong_login_attempt' => $attempt]);
		if($attempt == 0){
			$this->db->set("failed_login_attempt_timeout_until", null);
		}
		return $this->runAnyUpdate('player');
	}

	public function importAffiliateIdToUsername($playerUsername, $affiliateId) {
		$this->db->set('affiliateId', $affiliateId)->where('username', $playerUsername)->where('affiliateId is null', null, false);
			// ->update('player');

		return $this->runAnyUpdateWithResult('player');
	}

	public function existsOnlineSession($playerId) {
        $this->load->library('lib_session_of_player');
        $_config4session_of_player = $this->lib_session_of_player->_extractConfigFromParams( $this->utils->getConfig('session_of_player') );

		$timeout_seconds = $this->utils->getConfig('player_session_timeout_seconds');
        if( $_config4session_of_player['sess_use_database'] ){
			// $this->load->library(array('session'));
			$this->db->from('ci_player_sessions')->where('player_id', $playerId)->order_by('last_activity desc')->limit(1);
			$is_online = false;
			$row = $this->runOneRowArray();
			if (!empty($row)) {
				$last_activity = $row['last_activity'];

				// $this->utils->info_log('time', time(), 'last_activity', $last_activity, 'timeout_seconds', $timeout_seconds);

				$is_online = time() < $last_activity + $timeout_seconds;
			}
			return $is_online;
        }else if( $_config4session_of_player['sess_use_redis'] ){
			$specialSessionTable='ci_player_sessions';
			$session=$this->getAnyAvailableSessionByObjectIdOnRedis($specialSessionTable, $playerId, $timeout_seconds);
			return !empty($session);
        }else if($_config4session_of_player['sess_use_file']){
            $session=$this->lib_session_of_player->getAnyAvailableSessionByObjectIdOnFile($playerId, $timeout_seconds);
            return !empty($session);
        }else{
            $this->utils->error_log('wrong settings, no db, no redis and no file');
            $is_online = false;
            return $is_online;
		}
	}

    public function updateAllOnlinePlayerStatus() {
        $this->load->library('lib_session_of_player');
        $_config4session_of_player = $this->lib_session_of_player->_extractConfigFromParams( $this->utils->getConfig('session_of_player') );

        $time = time();
        $timeout_seconds = $this->utils->getConfig('player_session_timeout_seconds');
        $time_range = $time - $timeout_seconds;
        $ids=[];
        if( $_config4session_of_player['sess_use_database'] ){
	        $this->db->select('player_id')
	            ->from('ci_player_sessions')
	            ->where('last_activity >', $time_range)
	            ->where('player_id is NOT NULL', NULL, FALSE)
	            ->distinct();
	        // $query = $this->db->get();
	        $data = $this->runMultipleRowArray();// $query->result_array();

	        $ids =  array_column($data, 'player_id');
        }else if( $_config4session_of_player['sess_use_redis'] ){
	    	//get from redis
			$specialSessionTable='ci_player_sessions';
	    	$ids=$this->searchAllObjectIdOnRedis($specialSessionTable, $time_range);
        }else if($_config4session_of_player['sess_use_file']){
            $ids = $this->lib_session_of_player->searchAllObjectIdOnFile($time_range);
        }else{
            $this->utils->error_log('wrong settings, no db, no redis and no file');
	    }
        if(!empty($ids)){
            $online = $this->db->where_in('playerId', $ids);
            $online = $this->db->update('player', array('online' => self::PLAYER_ONLINE));

            $offline = $this->db->where_not_in('playerId', $ids);
            $offline = $this->db->update('player', array('online' => self::PLAYER_OFFLINE));
        } else{
        	//maybe it's wrong, the sql is too big
        	$this->utils->debug_log('cannot find any online session');
        }
    }

    public function updatePlayerOnlineStatus($player_id, $status) {
        return $this->db->update('player', array('online' => $status), array('playerId' => intval($player_id)));
    }

	public function getPlayerTotalCashback($player_id) {
		$this->load->model(['transactions']);

		$this->db->select_sum('amount')->from('transactions')
				->where('status', Transactions::APPROVED)
				->where('to_type', Transactions::PLAYER)
				->where('to_id', $player_id)
				->where_in('transaction_type', array(Transactions::AUTO_ADD_CASHBACK_TO_BALANCE, Transactions::CASHBACK));

		$amount = $this->runOneRowOneField('amount');

		return $amount !== NULL ? $amount : 0;
	}

	public function getExternalIdMap($external_id) {

		$map=[];

		$this->db->select('playerId, external_id')->from('player')
			->where('external_id is not null');

		$rows=$this->runMultipleRowArray();
		if(!empty($rows)){
			foreach ($rows as $row) {
				if(!empty($row['external_id'])){
					$map[$row['external_id']]=$row['playerId'];
				}
			}
		}

		return $map;
	}

	public function getAllVerifiedPhonePlayer() {
		$this->db->select('playerdetails.contactNumber');
		$this->db->from($this->tableName);
		$this->db->where([
			'player.verified_phone' => 1,
			'player.blocked' => 0,
		]);
		$this->db->join('playerdetails', 'playerdetails.playerId = player.playerId');
		$qry = $this->db->get();

		return $qry->result_array();
	}

	/**
	 * Was checkIfPlayerIsTagged() from models/player.php
	 * @param	int		$playerId	== player.playerId
	 * @param	int		$tagId		== playertag.tagId	(optional)
	 * @return	bool	true if player is tagged with tagId, or any tag at all if $tagId absent
	 */
	public function checkIfPlayerIsTagged($playerId, $tagId = NULL) {
		$sql = "SELECT T.tagId, T.tagName FROM playertag AS PT LEFT JOIN tag AS T ON T.tagId = PT.tagId WHERE PT.playerId = ? ";

		$where = array($playerId);
		if(NULL !== $tagId){
			$sql .= "AND PT.tagId = ?";
			$where[] = $tagId;
		}
		$query = $this->db->query($sql, $where);
		if(empty($query)){
			return FALSE;
		}

		$res = $query->result_array();

		$count = count($res);
		if ($count > 0) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Was getPlayerById() from models/player.php
	 * Renamed to avoid Player_model::getPlayerById
	 *
	 * @param 	int
	 * @return	array
	 */
	public function getPlayerDetailsTagsById($player_id) {
		$this->db->select('p.*, t.*, pd.*, pa.*, p.playerId as playerId, p.status as playerStatus, p.createdOn as playerCreatedOn, t.createdOn as tagCreatedOn, t.status as tagStatus, pa.status as playerAccountStatus');
		$this->db->from('player as p');
		$this->db->join('playerdetails as pd', 'p.playerId = pd.playerId', 'left');
		$this->db->join('playeraccount as pa', 'p.playerId = pa.playerId', 'left');
		$this->db->join('playertag as pt', 'p.playerId = pt.playerId', 'left');
		$this->db->join('tag as t', 'pt.tagId = t.tagId', 'left');
		$this->db->where('pa.type', 'wallet');
		$this->db->where('p.playerId', $player_id);

		$query = $this->db->get();

		if (!$query->row_array()) {
			return false;
		} else {
			return $query->row_array();
		}
	}

	public function setIsRegisterPopUpDone($data) {
		$this->db->set("is_registered_popup_success_done", $data['is_registered_popup_success_done']);
		$this->db->where('playerId', $data['playerId']);

		return $this->db->update('player');
	}

	/**
	 * Will get tag based on the player id
	 *
	 * @param 	int
	 * @return 	array
	 */
	public function getPlayerTags($player_id, $only_tag_id = FALSE, $include_soft_deleted = FALSE) {
		$sql = "SELECT * FROM playertag AS pt INNER JOIN tag AS t ON pt.tagId = t.tagId WHERE pt.playerId = ? and pt.isDeleted = 0";
		if($include_soft_deleted){
			$sql = "SELECT * FROM playertag AS pt INNER JOIN tag AS t ON pt.tagId = t.tagId WHERE pt.playerId = ?";
		}

		$query = $this->db->query($sql, array($player_id));

		if (!$query->result_array()) {
			return [];
		} else {
			if(FALSE === $only_tag_id){
				return $query->result_array();
			}else{
				$results = $query->result_array();
				$tagIds = [];
				foreach($results as $result){
					$tagIds[] = $result['tagId'];
				}

				return $tagIds;
			}
		}
	}

	public function getPlayerDetailArrayById($id) {
		$this->db->from('playerdetails')->where('playerId', $id);

		return $this->runOneRowArray();
	}

	/**
	 * overview : check if username is block cause of Failed login attempt
	 *
	 * @param  int	$playerId
	 * @return bool
	 */
	public function isBlockedFailedLoginAttempt($playerId) {
		$this->db->select('blocked')->from($this->tableName)->where('playerId', $playerId);
		$blocked = $this->runOneRowOneField('blocked');
		// $this->utils->debug_log('blocked', $blocked, 'playerId', $playerId);
		return $blocked == self::STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT;
	}

	/**
	 * overview : get failed_login_attempt_timeout_until by playerId
	 *
	 * @param $playerId
	 * @return null
	 */
	public function getFailedLoginAttemptTimeoutUntilByPlayerId($playerId) {
		// $this->utils->debug_log("Player Model getPlayerIdByUsername: ", $username, " Table: ", $this->tableName);
		if (!empty($playerId)) {
			$this->db->select('failed_login_attempt_timeout_until');
			$this->db->where('playerId', $playerId);
			$qry = $this->db->get($this->tableName);
			return $this->getOneRowOneField($qry, 'failed_login_attempt_timeout_until');
		}
		return null;
	}

    public function getPlayerIdsByFailedLoginAttemptTimeoutUntil($beginDatetime, $endDatetime){

        $this->db->select('playerId');
        $this->db->from($this->tableName);

        $this->db->where("failed_login_attempt_timeout_until BETWEEN '{$beginDatetime}' AND '{$endDatetime}'", null, false);

		$res = $this->runMultipleRowArray();
        return empty($res)? 0: count($res);
    }

	/**
	 * date: 3/7/2018 by jhunel.php.ph
	 */
	public function register($player, $batch = false, $disabled_auto_create_game_account_on_registration=true, $upload_batch = false, $save_http_request = true) {
		$this->load->model(array('affiliatemodel', 'agency_model', 'group_level','http_request', 'game_provider_auth',
			'communication_preference_model','player_attached_proof_file_model', 'common_token', 'player_api_verify_status'
			, 'affiliate_newly_registered_player_tags', 'dispatch_account' ));
		$this->load->library(array('language_function'));

		$gamePlatformManager = $this->utils->loadGameManager();

		$current_time = $this->utils->getNowForMysql();
		$games = $this->external_system->getAllActiveSytemGameApi();
		$common_data = array(
			'playeraccount' => array(
				'currency' => isset($player['currency']) ? $player['currency'] : $this->getActiveCurrencyCode(),
				'batchPassword' => self::DEFAULT_PLAYERACCOUNT_BATCHPASSWORD,
				'status' => self::DEFAULT_PLAYERACCOUNT_STATUS,
			),
			'playergame' => array(
				'blocked' => self::DEFAULT_PLAYERGAME_BLOCKED,
				'status' => self::DEFAULT_PLAYERGAME_STATUS,
			),
		);

		$tracking_code = !empty($player['tracking_code']) ? $player['tracking_code'] : null;
		$tracking_source_code = !empty($player['tracking_source_code']) ? $player['tracking_source_code'] : null;

		$agent_tracking_code = !empty($player['agent_tracking_code']) ? $player['agent_tracking_code'] : null;
		$agent_tracking_source_code = !empty($player['agent_tracking_source_code']) ? $player['agent_tracking_source_code'] : null;

		// $agent_code, $affiliate_source_code, $agent_source_code: not used in following code
		$agent_code = !empty($player['agent_id']) ? $player['agent_id'] : null;
		$affiliate_source_code = !empty($player['affiliateId']) ? $player['affiliateId'] : null;
		$agent_source_code = !empty($player['agent_source_code']) ? $player['agent_source_code'] : null;

		if ($batch) {
			$batchId = $this->insertBatch(array(
				'name' => $player['username'],
				'count' => $player['count'],
				'typeCode' => $player['type_code'],
				'description' => $player['description'],
				'createdOn' => $current_time,
				'updatedOn' => $current_time,
			));
		}

		//--for agent---------------------------------------------------
		//agent_name from bo batch
		//agent_id from agency batch
		//agent_tracking_code is from web
		$agent_name = !empty($player['agent_name']) ? $player['agent_name'] : null;
		$agent=null;
		$root_agent_id=null;
		if(!empty($agent_name)){
			$agent=$this->agency_model->get_agent_by_name($agent_name);
            if( !empty($agent) ){
                $player['agent_id']=$agent['agent_id'];
            }
		}else if(!empty($agent_tracking_code)){
			$agent=$this->agency_model->get_agent_by_tracking_code($agent_tracking_code);
            if( !empty($agent) ){
                $player['agent_id']=$agent['agent_id'];
            }
		}else if(!isset($player['agent_id'])){
			$player['agent_id']=null;
		}

		if(!empty($player['agent_id'])){
			$root_agent_id = $this->agency_model->getRootAgencyByAgentId($player['agent_id']);
		}

		//--for affiliate---------------------------------------------------
		$affiliate_name = !empty($player['affiliate_name']) ? $player['affiliate_name'] : null;
		if(!empty($affiliate_name)){
			$player['affiliateId']=$this->affiliatemodel->getAffiliateIdByUsername($affiliate_name);
		}else if(!empty($tracking_code)){
			$player['affiliateId']=$this->affiliatemodel->getAffiliateIdByTrackingCode($tracking_code);
		}else if(!isset($player['affiliateId'])){
			$player['affiliateId']=null;
		}
		$theAffiliat = [];
		if(!empty($player['affiliateId'])) {
			$disable_cashback_on_registering = 0;
			$disable_promotion_on_registering = 0;
			$theAffiliat = $this->affiliatemodel->getAffiliateById($player['affiliateId']);
			if( !empty($theAffiliat) ){
				$disable_cashback_on_registering = isset($theAffiliat['disable_cashback_on_registering']) ? $theAffiliat['disable_cashback_on_registering'] : 0;
				$disable_promotion_on_registering = isset($theAffiliat['disable_promotion_on_registering']) ? $theAffiliat['disable_promotion_on_registering'] : 0;
			}
			$player['disable_cashback_on_registering'] = $disable_cashback_on_registering;
			$player['disable_promotion_on_registering'] = $disable_promotion_on_registering;
		}

		$username = strtolower(str_replace(' ', '', $player['username']));
        $temp_connect_str = 'tempStr';

		$levelId = empty($this->config->item('default_level_id'))? 1 : $this->config->item('default_level_id');
		if(!empty($agent['vip_level'])){
			$levelId=$agent['vip_level'];
		}
        if(!empty($player['affiliateId'])) {
            $affiliate = $this->affiliatemodel->getAffiliateById($player['affiliateId']);
            if(!empty($affiliate['vip_level_id'])) {
                $levelId = $affiliate['vip_level_id'];
            }
        }

		$level=$this->group_level->getLevelById($levelId);
		$levelName=$level['vipLevelName'];
		$group=$this->group_level->getGroupById($level['vipSettingId']);
		$groupName = $group['groupName'];

		$playerId = null;
		$playerIds = array();
		$count = 0;
		do {
			if ($batch && $player['count'] > 0) {
				do {
					if ($player['count'] == 1) {
						$username = $username;
					} else {
                        $username = increment_string($username, '', 1, $temp_connect_str);
					}
				} while ($this->usernameExist($username));
			}

			$secure_id = $this->getSecureId('player', 'secure_id', true, 'P');

			$disable_cashback_on_register = $this->utils->getConfig('disable_cashback_on_register');
			$disable_promotion_on_register = $this->utils->getConfig('disable_promotion_on_register');

			if( empty( $disable_cashback_on_register ) ){
				if( !empty($player['disable_cashback_on_registering']) ){ // check the field, "affiliates.disable_cashback_on_registering".
					$disable_cashback_on_register = $player['disable_cashback_on_registering'];
				}
			}else{
				$disable_cashback_on_register = $disable_cashback_on_register;
			}
			if( empty( $disable_promotion_on_register ) ){
				if( !empty($player['disable_promotion_on_registering']) ){ // check the field, "affiliates.disable_promotion_on_registering".
					$disable_promotion_on_register = $player['disable_promotion_on_registering'];
				}
			}else{
				$disable_promotion_on_register = $disable_promotion_on_register;
			}

			$invitationCode = $this->generateReferralCode();
			$withdraw_password = isset($player['withdraw_password']) ? $player['withdraw_password'] : null;

			if( !empty($player['registered_by']) ){ // Specified directly
				$registered_by = $player['registered_by'];
			}else if( $batch || $upload_batch ){
				$registered_by = self::REGISTERED_BY_MASS_ACCOUNT;
			}else if( $this->utils->is_mobile() ){
				$registered_by = self::REGISTERED_BY_MOBILE;
			}else{
				$registered_by = self::REGISTERED_BY_WEBSITE;
			}
			// $registered_by= ( $batch || $upload_batch ) ? self::REGISTERED_BY_MASS_ACCOUNT :
			// 	($this->utils->is_mobile() ? self::REGISTERED_BY_MOBILE : self::REGISTERED_BY_WEBSITE );

			if($this->utils->getConfig('record_player_center_api_register_to_aff_trafic') && $registered_by == self::REGISTERED_BY_PLAYER_CENTER_API){
				$visit_record_id = false;
				if (!empty($tracking_code)) {
					if (($this->utils->getConfig('track_visit_only_once') && $visit_record_id == false) || !$this->utils->getConfig('track_visit_only_once')) {
						$visit_record_id = $this->http_request->recordPlayerRegistration(null, $tracking_code, $tracking_source_code);
						$player['visit_record_id'] = $visit_record_id;
						$this->session->set_userdata('visit_record_id', $visit_record_id);
					}
				}
			}

			// return true;
			$btag = $this->utils->isEnabledFeature('enable_income_access') && isset($player['btag']) && $player['btag'] ? $player['btag'] : null;

			if(!empty($btag)) $this->utils->debug_log('btag', $btag);

			$dispatch_account_level_id = ($this->config->item('default_dispatch_account_level_id')) ? $this->config->item('default_dispatch_account_level_id') : 1;
			if( ! empty($player['affiliateId']) ){
				if( ! empty($theAffiliat['dispatch_account_level_id_on_registering']) ){
					$theDispatchAccountLevelDetails = $this->dispatch_account->getDispatchAccountLevelDetailsById($theAffiliat['dispatch_account_level_id_on_registering']);
					$dispatch_account_level_id = $theDispatchAccountLevelDetails['id'];
				}
			}

			$playerId = $this->insertPlayer(array_filter(array(
				'username' => $username,
				'gameName' => ( $batch || $upload_batch ) ? null : ((isset($player['gameName'])) ? $player['gameName'] : NULL),
				'email' => ( $batch || $upload_batch ) ? null : ((isset($player['email'])) ? $player['email'] : NULL),
				'password' => $player['password'],
				'secretQuestion' => ( $batch || $upload_batch ) ? null : ((isset($player['secretQuestion'])) ? $player['secretQuestion'] : NULL),
				'secretAnswer' => ( $batch || $upload_batch ) ? null : ((isset($player['secretAnswer'])) ? $player['secretAnswer'] : NULL),
				'verify' => ( $batch || $upload_batch ) ? null : ((isset($player['verify'])) ? $player['verify'] : NULL),
				'registered_by' => $registered_by,
				'verified_phone' => ( $batch || $upload_batch ) ? null : ((isset($player['verified_phone'])) ? $player['verified_phone'] : NULL), # phone verification only available thru website
				'levelId' => $levelId,
				'levelName' => $levelName,
				'groupName' => $groupName,
				'invitationCode' => $invitationCode,
				'btag' => $btag,
				'secure_id' => $secure_id,
				'tracking_code' => $tracking_code,
				'tracking_source_code' => $tracking_source_code,
				'agent_tracking_code'=>$agent_tracking_code,
				'agent_tracking_source_code'=>$agent_tracking_source_code,
				'disabled_cashback' => $disable_cashback_on_register ? '1' : '0',
				'disabled_promotion' => $disable_promotion_on_register ? '1' : '0',
				'withdraw_password' => $withdraw_password,
				'affiliateId' => ((isset($player['affiliateId'])) ? $player['affiliateId'] : NULL),
				'agent_id' => ((isset($player['agent_id'])) ? $player['agent_id'] : NULL),
				'root_agent_id' => $root_agent_id,
				'is_phone_registered' => isset($player['is_phone_registered']) ? $player['is_phone_registered'] : 0,
				'createdOn' => $upload_batch ? ((isset($player['createdOn'])) ? $player['createdOn'] : $current_time) : $current_time,
                'newsletter_subscription' => ( $batch || $upload_batch ) ? null : ((isset($player['newsletter_subscription'])) ? $player['newsletter_subscription'] : NULL),
                'dispatch_account_level_id' => $dispatch_account_level_id
			)));

			if (empty($playerId)) {
			    continue;
			}

			if ($this->utils->getConfig('enabledCreditMode')) {
				$enabled_credit_mode =  $this->enabledCreditMode($playerId);
				$this->utils->debug_log('update player credit_mode result', $enabled_credit_mode);
			}

            if($batch && (strpos($username, $temp_connect_str) !== false)){
                $origin_username = str_replace($temp_connect_str, '', $username);
                $this->updatePlayer($playerId, ['username' => $origin_username]);
                $this->utils->debug_log('update username of batch create, temp: '. $username .' / origin ', $origin_username);
            }

			if( ! empty($player['affiliateId']) ){
				$adminUserId = Transactions::ADMIN;
				$affiliate_id = $player['affiliateId'];
				$tag_id_list = $this->affiliate_newly_registered_player_tags->getTagsByAffiliateId($affiliate_id);
				if( ! empty($tag_id_list) ){
					foreach($tag_id_list as $indexNumber => $currnewly_player_tag){
						$tagId = $currnewly_player_tag['tag_id'];
						$this->addTagToPlayer($playerId,$tagId,$adminUserId);
					}
				}
			} // EOF if( ! empty($player['affiliateId']) ){...

			#OGP-14046 [Fraud Check新顏] Add 4th status for bo created members
			// if($this->utils->isEnabledFeature('enable_show_trigger_XinyanApi_validation_btn')){
			// 	$this->player_api_verify_status->add($playerId, player_api_verify_status::NO_VERFY_REQUIRED);
			// }

			//$this->ci->utils->debug_log('playerId', $playerId, 'username', $username, 'batch', $batch, 'post email', @$player['email']);

			// OGP-19536: Use uniformal format (YYYY-mm-dd) for birthdate
			$birthdate = ( $batch || $upload_batch ) ? null : ((isset($player['birthdate'])) ? $player['birthdate'] : NULL);
			if(!empty($birthdate)){
				$birthdate = date('Y-m-d', strtotime($birthdate));
			}

			$this->insertPlayerDetails(array_filter(array(
				'playerId' => $playerId,
				'firstName' => ( $batch || $upload_batch ) ? null : ((isset($player['firstName'])) ? $player['firstName'] : NULL),
				'lastName' => ( $batch || $upload_batch ) ? null : ((isset($player['lastName'])) ? $player['lastName'] : NULL),
				'language' => empty($player['language']) ? $this->language_function->getCurrentLanguageName() : $player['language'],
				'gender' => ( $batch || $upload_batch ) ? null : ((isset($player['gender'])) ? $player['gender'] : NULL),
				// 'birthdate' => ( $batch || $upload_batch ) ? null : ((isset($player['birthdate'])) ? $player['birthdate'] : NULL),
				'birthdate' => $birthdate ,
				'contactNumber' => ( $batch || $upload_batch ) ? null : ((isset($player['contactNumber'])) ? $player['contactNumber'] : NULL),
				'citizenship' => ( $batch || $upload_batch ) ? null : ((isset($player['citizenship'])) ? $player['citizenship'] : NULL),
				'imAccount' => ( $batch || $upload_batch ) ? null : ((isset($player['imAccount'])) ? $player['imAccount'] : NULL),
				'imAccountType' => ( $batch || $upload_batch ) ? null : ((isset($player['imAccountType'])) ? $player['imAccountType'] : NULL),
				'imAccount2' => ( $batch || $upload_batch ) ? null : ((isset($player['imAccount2'])) ? $player['imAccount2'] : NULL),
				'imAccountType2' => ( $batch || $upload_batch ) ? null : ((isset($player['imAccountType2'])) ? $player['imAccountType2'] : NULL),
				'imAccount3' => ( $batch || $upload_batch ) ? null : ((isset($player['imAccount3'])) ? $player['imAccount3'] : NULL),
				'imAccountType3' => ( $batch || $upload_batch ) ? null : ((isset($player['imAccountType3'])) ? $player['imAccountType3'] : NULL),
				'imAccount4' => ( $batch || $upload_batch ) ? null : ((isset($player['imAccount4'])) ? $player['imAccount4'] : NULL),
				'imAccountType4' => ( $batch || $upload_batch ) ? null : ((isset($player['imAccountType4'])) ? $player['imAccountType4'] : NULL),
				'imAccount5' => ( $batch || $upload_batch ) ? null : ((isset($player['imAccount5'])) ? $player['imAccount5'] : NULL),
				'imAccountType5' => ( $batch || $upload_batch ) ? null : ((isset($player['imAccountType5'])) ? $player['imAccountType5'] : NULL),
				'birthplace' => ( $batch || $upload_batch ) ? null : ((isset($player['birthplace'])) ? $player['birthplace'] : NULL),
				'registrationIp' => ( $batch || $upload_batch ) ? null : ((isset($player['registrationIp'])) ? $player['registrationIp'] : NULL),
				'registrationWebsite' => ( $batch || $upload_batch ) ? null : ((isset($player['registrationWebsite'])) ? $player['registrationWebsite'] : NULL),
				'residentCountry' => ( $batch || $upload_batch ) ? null : ((isset($player['residentCountry'])) ? $player['residentCountry'] : NULL),
				'city' => ( $batch || $upload_batch ) ? null : ((isset($player['city'])) ? $player['city'] : NULL),
				'address' => ( $batch || $upload_batch ) ? null : ((isset($player['address'])) ? $player['address'] : NULL),
				'address2' => ( $batch || $upload_batch ) ? null : ((isset($player['address2'])) ? $player['address2'] : NULL),
				'address3' => ( $batch || $upload_batch ) ? null : ((isset($player['address3'])) ? $player['address3'] : NULL),
				'zipcode' => ( $batch || $upload_batch ) ? null : ((isset($player['zipcode'])) ? $player['zipcode'] : NULL),
				'dialing_code' => ( $batch || $upload_batch ) ? null : ((isset($player['dialing_code'])) ? $player['dialing_code'] : NULL),
				'id_card_number' => ( $batch || $upload_batch ) ? null : ((isset($player['id_card_number'])) ? $player['id_card_number'] : NULL),
				'id_card_type' => ( $batch || $upload_batch ) ? null : ((isset($player['id_card_type'])) ? $player['id_card_type'] : NULL),
				'communication_preference' => ( $batch || $upload_batch || !isset($player['communication_preference'])) ? null : $player['communication_preference'],
				'pix_number' => ( $batch || $upload_batch ) ? null : ((isset($player['pix_number'])) ? $player['pix_number'] : NULL),
			)));

			$this->insertPlayerDetails_extra(array_filter(array(
				'playerId' => $playerId,
				'middleName' => ( $batch || $upload_batch ) ? null : ((isset($player['middleName'])) ? $player['middleName'] : NULL),
				'maternalName' => ( $batch || $upload_batch ) ? null : ((isset($player['maternalName'])) ? $player['maternalName'] : NULL),
				'issuingLocation' => ( $batch || $upload_batch ) ? null : ((isset($player['issuingLocation'])) ? $player['issuingLocation'] : NULL),
				'issuanceDate' => ( $batch || $upload_batch ) ? null : ((isset($player['issuanceDate'])) ? $player['issuanceDate'] : NULL),
				'expiryDate' => ( $batch || $upload_batch ) ? null : ((isset($player['expiryDate'])) ? $player['expiryDate'] : NULL),
				'isPEP' => ( $batch || $upload_batch ) ? null : ((isset($player['isPEP'])) ? $player['isPEP'] : NULL),
				'acceptCommunications' => ( $batch || $upload_batch ) ? null : ((isset($player['acceptCommunications'])) ? $player['acceptCommunications'] : NULL),
			)));

            $enable_restrict_username_more_options = empty($this->utils->getConfig('enable_restrict_username_more_options'))? false: true;

            if( $enable_restrict_username_more_options ){
                $usernameRegDetails = [];
                $this->utils->getUsernameReg($usernameRegDetails);
                if( empty($usernameRegDetails['username_case_insensitive'])
                    && $player['username'] !== $player['username_on_register']
                ){ // store for case sensitive
                    $this->load->model('player_preference');
                    $this->player_preference->storeUsernameOnRegister($player['username_on_register'], $playerId);
                }
            }

			// -- Process communication preference
			if($this->utils->isEnabledFeature('enable_communication_preferences') && isset($player['communication_preference'])) {
				$this->communication_preference_model->saveNewLog($playerId, $player['communication_preference'], $playerId, Communication_preference_model::PLATFORM_PLAYER_CENTER);
			}

			//update visit
			if (!empty($player['visit_record_id'])) {
				$this->utils->debug_log('visit_record_id', $player['visit_record_id']);
				$this->http_request->updateVisitRecordSignUp($player['visit_record_id'], $playerId);
			}

			$extra = [
				'ip'			=> !empty($player['registrationIp']) ? $player['registrationIp'] : null ,
                'user_agent'	=> !empty($player['reg_user_agent']) ? $player['reg_user_agent'] : '' ,
                'referrer'		=> !empty($player['registrationWebsite']) ? $player['registrationWebsite'] : '' ,
            ];
			// if (!empty($player['reg_user_agent'])) {
			// 	$extra['reg_user_agent'] = $player['reg_user_agent'];
			// }
			if($save_http_request) $this->utils->saveHttpRequest($playerId, Http_request::TYPE_REGISTRATION, $extra);

			// -- save default attached file status history
			$this->player_attached_proof_file_model->saveAttachedFileStatusHistory($playerId);

            $refreshBigWalletOnDB_result = false;
			switch($this->utils->getConfig('init_wallet_action_when_register_player')){
                case 0: // disable, but still call wallet_model::initCreateAllWalletForRegister() with lock.
                    $controller = $this;
                    $refreshBigWalletOnDB_result = true;
                    $this->wallet_model->lockAndTransForPlayerBalance($playerId, function () use ($controller,$playerId) {
                        return $this->wallet_model->initCreateAllWalletForRegister($playerId);
                    });
                break;

                default:
                case 1: // without lock: call wallet_model::refreshBigWalletOnDB()
                    $refreshBigWalletOnDB_result = true;
                    $this->wallet_model->refreshBigWalletOnDB($playerId, $this->db, false);
                break;

                case 2: // with lock:  call wallet_model::refreshBigWalletOnDB()
                    $_this = $this;
                    $doExceptionPropagation = true;
                    $isLockFailed = false; // default
                    $add_prefix = true;
                    try{
                        /// cloned form "init player wallet" in admin/application/controllers/gamegateway.php
                        $this->lockAndTransForPlayerBalance($playerId, function () use ($_this,$playerId) {
                            return $_this->wallet_model->refreshBigWalletOnDB($playerId, $_this->db, false);
                        }, $add_prefix, $isLockFailed, $doExceptionPropagation);
                        $refreshBigWalletOnDB_result = true;
                    }catch(Exception $e){

                    }
                break;
            } // EOF switch($this->utils->getConfig('init_wallet_action_when_register_player')){...

			$common_data['playeraccount']['playerId'] = $playerId;
			$common_data['playergame']['playerId'] = $playerId;


			if (!empty($player['affiliateId'])) {
				//update count
				$this->affiliatemodel->incCountPlayer($player['affiliateId']);

			}

			if (isset($player['referral_code']) && $player['referral_code']) {

				$this->utils->debug_log('referral_code', $player['referral_code']);
				$referedBy = $this->player_model->getPlayer(array(
					'invitationCode' => $player['referral_code'],
				));

				if(!empty($referedBy)){
					if(isset($referedBy['playerId'])){
						$this->player_model->createPlayerReferral($playerId, $referedBy['playerId']);
					}
				}

			}

            if($refreshBigWalletOnDB_result){
                /// An error in your SQL syntax caused by an exception in wallet_model->refreshBigWalletOnDB().
                // Some extra Non-Expected data appeared in SQL.
                $this->common_token->getPlayerToken($playerId);
            }


			if($this->utils->isEnabledFeature('send_sms_after_registration') && !$batch && !empty($player['verified_phone']) && $upload_batch){

				//send sms
				$searchArr=['{player_username}', '{player_center_url}', '{mobile_number}'];
				$replaceArr=[ $username, $this->utils->getSystemUrl('player'), $player['contactNumber'] ];
				$msg=$this->utils->generateSmsTemplate($searchArr, $replaceArr);
				$dialingCode = $player['dialing_code'];
            	$mobileNum = !empty($dialingCode)? $dialingCode.'|'.$player['contactNumber'] : $player['contactNumber'];

				$this->utils->debug_log('send registration sms to '.$mobileNum, $username, $msg);
				if(!empty($msg)){
					$this->utils->sendSmsByApi($player['contactNumber'], $msg);
				}
			}

        	//save additional info
			$rlt=$this->game_provider_auth->updateT1LotteryAdditionalInfo($playerId, $agent_tracking_code, $agent_tracking_source_code);
			$this->utils->debug_log('updateT1LotteryAdditionalInfo :'.$playerId, $agent_tracking_code, $agent_tracking_source_code, $rlt);

			$this->agency_model->registerPlayerToAgent($playerId, $agent_tracking_code, $agent_tracking_source_code);

			$playerIds[] = $playerId;

		} while ($batch && ($player['count'] > ++$count));

		return count($playerIds) > 1 ? $playerIds : $playerId;
	}

	public function insertAndGetPlayerTag($data) {
		try {
			$this->db->insert('playertag', $data);

			if ($this->db->_error_message()) {
				throw new Exception($this->db->_error_message());
			} else {
				//New tag
				return $this->checkIfPlayerIsTagged($data['playerId'], $data['tagId']);
			}

		} catch (Exception $e) {
			return FALSE;
		}
	}

	public function insertPlayerTagHistory($params, $action){
		$data = array(
			'player_id' => $params['playerId'],
			'tag_id' => $params['tagId'],
			'action' => $action,
			'created_by' => $params['taggerId'],
			'updated_by' => $params['taggerId'],
			'tag_name' => $params['tagName'],
			'tag_color' => $params['tagColor'],
		);
		return $this->db->insert('player_tag_history', $data);
	}

	/**
	 * Will check if player is test account based on tag by player id
	 *
	 * @param 	int
	 * @return 	boolean
	 */
	public function isTestAccountByPlayerTags($player_id) {
		$response = false;


		$player_tag_test_account = $this->utils->getConfig('player_tag_test_account');
		$sql = "SELECT * FROM playertag AS pt INNER JOIN tag AS t ON pt.tagId = t.tagId WHERE pt.playerId = ?";
		//$sql = "SELECT * FROM playertag AS pt INNER JOIN tag AS t ON pt.tagId = t.tagId WHERE pt.playerId = ? AND t.tagName IN ('".$player_tag_test_account."')";
		$query = $this->db->query($sql, array($player_id));
		$results = $query->result_array();
		//echo "<pre>";print_r($results);die();
		if (!empty($results) && !empty($player_tag_test_account)) {

			$tagIds = [];
			foreach($results as $result){
				if(in_array($result['tagName'], $player_tag_test_account)){
					$response = true;
				}
			}
		}

		return $response;
	}

	/**
	 * get player list
	 *
	 * @param  int $source_vip_group_id
	 * @param  array $exclude_vip_level_id_array
	 * @return array list of player
	 */
	public function getPlayerListByVipGroupId($source_vip_group_id, $exclude_vip_level_id_array=[]){

		$this->db->select('vipsettingcashbackruleId')->from('vipsettingcashbackrule')->where('vipSettingId', $source_vip_group_id)
			->where_not_in('vipsettingcashbackruleId', $exclude_vip_level_id_array);

		$rows=$this->runMultipleRowArray();

		// $this->utils->printLastSQL();
		// $this->utils->debug_log($rows);

		$levelIdArray=[];
		foreach ($rows as $row) {
			$levelIdArray[]=$row['vipsettingcashbackruleId'];
		}

		$this->db->select('playerId, username')->from('player')->where_in('levelId', $levelIdArray);

		return $this->runMultipleRowArray();
	}

	/**
	 * get player list
	 *
	 * @param  int $source_vip_group_id
	 * @param  array $exclude_vip_level_id_array
	 * @return array list of player
	 */
	public function getPlayerListByVipLevelId($source_vip_level_id){

		$this->db->select('playerId, username')->from('player')->where('levelId', $source_vip_level_id);

		return $this->runMultipleRowArray();
	}

	/**
	 * get player list
	 *
	 * @param  array $level_ids
	 * @param  array $tag_ids
	 * @return array list of player
	 */
	public function getPlayerListByVipLevelIdAndTagId($level_ids, $tag_ids){
		$withRestriction = false;
		if(!empty($level_ids) || !empty($tag_ids)){
			$withRestriction = true;
		}

		if(!$withRestriction){
			return false;
		}

		$this->db->select('player.playerId player_id,
		player.username,
		playertag.playerTagId player_tag_id,
		tag.tagId tag_id,
		tag.tagName tag_name')->from('player');

		if($level_ids){
			$this->db->where_in('player.levelId', $level_ids);
		}

		if($tag_ids){
			$this->db->where_in('playertag.tagId', $tag_ids);
		}

		$this->db->join('playertag', 'playertag.playerId = player.playerId');
		$this->db->join('tag', 'tag.tagId = playertag.tagId');
		$this->db->group_by('playertag.playerId');
		$query = $this->db->get();
		$result = $query->result_array();
		return $result;
	}

	public function getPlayerIdsByUsernames($usernames) {
		$this->db->select('playerId');
		$this->db->from($this->tableName);
		$this->db->where_in('username', $usernames);
		return $this->runMultipleRowArray();
	}

	public function getPlayerIdsAndUsernamesByUsernames($usernames) {
		$this->db->select(['username','playerId']);
		$this->db->from($this->tableName);
		$this->db->where_in('username', $usernames);
		return $this->runMultipleRowArray();
	}

	public function getPlayerArrayByUsername($username) {
		$this->db->from('player')->where('username', $username);

		return $this->runOneRowArray();
	}

	/**
	 * syncPlayerInfoFromExternal
	 * @param  string  $username
	 * @param  string  $password
	 * @param  string  $realname
	 * @param  integer $levelId
	 * @param  int  $agent_id
	 * @param  array  $extra
	 * @return int $playerId
	 */
	public function syncPlayerInfoFromExternal($username, $password, $realname, $levelId, $agent_id, $extra=null){

		//		$success=!empty($username) && !empty($password);
		$this->load->model(array('agency_model', 'game_provider_auth', 'wallet_model', 'group_level'));
		$this->db->select('playerId')->from('player')->where('username', $username);
		$playerId=$this->runOneRowOneField('playerId');
		$currency = $this->agency_model->getAgentCurrencyByAgentId($agent_id);

		$detailData=[];
		//debug level is 1
		$data=array(
			'username'=>$username,
			'password'=> $this->utils->encodePassword($password),
			'levelId'=>$levelId,
			'agent_id'=>$agent_id,
			'currency'=> $currency
		);

		if(!empty($extra['language'])){
			$detailData['language']=$extra['language'];
		}
		if(!empty($extra['country'])){
			$detailData['country']=$extra['country'];
		}
		if(!empty($extra['email'])) {
            $data['email'] = $extra['email'];
    	}
    	if(empty($realname)){
    		if(!empty($extra['first_name'])){
				$detailData['firstName']=$extra['first_name'];
			}
			if(!empty($extra['last_name'])){
				$detailData['lastName']=$extra['last_name'];
			}
		}else{
			$detailData['firstName']=$realname;
		}
		if(empty($playerId)){
			$data['createdOn']=$this->utils->getNowForMysql();
			$data['updatedOn']=$this->utils->getNowForMysql();
			//insert
			$playerId=$this->insertData('player', $data);
			if(!empty($playerId)){
				$detailData['playerId']=$playerId;
				$this->insertData('playerdetails', $detailData);
				//add to level
				$this->group_level->addPlayerLevel($playerId);
		        // $this->wallet_model->initCreateAllWalletForRegister($playerId,$currency);
			}else{
				$playerId=null;
			}
			$this->utils->debug_log('insert player:'.$playerId, $data, 'detail', $detailData);
		}else{
			unset($data['password']);
			$data['updatedOn']=$this->utils->getNowForMysql();
			//update
			$this->db->set($data)->where('playerId', $playerId);
			$this->runAnyUpdate('player');

			if(!empty($detailData)){
				$this->db->set($detailData)->where('playerId', $playerId);
				$this->runAnyUpdate('playerdetails');
			}
			$this->utils->debug_log('update player:'.$playerId, $data, 'detail', $detailData);

			// $this->group_level->addPlayerLevel($playerId);

	        //sync wallet info
	        // $this->wallet_model->initCreateAllWalletForRegister($playerId,$currency);
		}

		return $playerId;
	}

	/**
	 * overview : get player currency by player id
	 *
	 * @param $playerId
	 * @return currency code
	 */
	public function getPlayerCurrencyByPlayerId($playerId) {
		if (!empty($playerId)) {
			$this->db->select('currency');
			$this->db->where('playerId', $playerId);
			$qry = $this->db->get($this->tableName);
			return $this->getOneRowOneField($qry, 'currency');
		}
		return null;
	}

	/**
	 * Construct player update log and insert into table playerupdatehistory
	 *
	 * @param	int		$player_id	playerId of changed player
	 * @param	string	$changes	changes, text description
	 * @param	string	$updatedBy	update username, text description
	 *
	 * @return	none
	 */
	public function savePlayerUpdateLog($player_id, $changes, $updatedBy) {
		$dataset = [
			'playerId'	=> $player_id,
			'changes'	=> $changes,
			'createdOn'	=> $this->getNowForMysql(),
			'operator'	=> $updatedBy,
		];

		$this->addPlayerInfoUpdates($player_id, $dataset);
	}

	/**
	 * Set player's agent_id/affiliate id to player table
	 *
	 * @param	int		$player_id		player.playerId
	 * @param	int		$agent_id		agency_agents.agent_id
	 * @param	int		$affiliate_id	affiliates.affiliateId
	 *
	 * @return  none
	 */
	public function setPlayerAgent($player_id, $agent_id, $affiliate_id = null) {
		$dataset = [
			'agent_id' => $agent_id
		];
		if(!empty($affiliate_id)){
			$dataset['affiliateId']=$affiliate_id;
		}
		$this->db->where('playerId', $player_id);
		$this->db->set($dataset);

		return $this->runAnyUpdate('player');
	}

	/**
	 * save player bank changes
	 *
	 * @return  rendered template
	 */
	public function saveBankChanges($changes) {
		$this->db->insert('playerbankhistory', $changes);
	}

	public function searchByFirstName($firstName){

		$this->db->from('playerdetails')->where('firstName', $firstName);

		return $this->runOneRowArray();
	}

	public function getColumnsForPlayerDetails() {
		$res = $this->db->list_fields('playerdetails');

		return $res;
	}

	/**
	 * get player id from ntoe id
	 * @param  int $noteId
	 * @return int
	 */
	public function getPlayerIFromNoteId($noteId){
		$this->db->select('playerId')->from('playernotes')->where('noteId', $noteId);

		return $this->runOneRowOneField('playerId');
	}

	/**
	 * Update player's withdrawal password
	 * @param	int		$player_id		== player.playerId
	 * @param	string	$wd_password	New withdrawal password
	 * @return	int		1 if changed successfully; otherwise 0
	 */
	public function updateWithdrawalPassword($player_id, $wd_password) {
		$updateset = [ 'withdraw_password' => $wd_password ];
		$this->db->where($this->idField, $player_id)
			->update($this->tableName, $updateset);

		return $this->db->affected_rows();
	}

	public function isPlayerWithdrawalPasswordEmpty($player_id) {
		$this->db->from($this->tableName)
			->where($this->idField, $player_id)
			->select('withdraw_password')
		;


		$res = $this->runOneRowOneField('withdraw_password');

		return empty($res);
	}

	public function getAllPlayerDetailsById($playerId) {
		$this->db->from('playerdetails')
			->where('playerId', $playerId);

		$res = $this->runOneRowArray();

		return $res;
	}

	public function insertTransactionsTag($data) {
		$this->db->insert('transactions_tag', $data);
	}

	public function createT1TestPlayers(){

		$jsonFile=APPPATH.'config/standard_t1_players.json';
		$json=file_get_contents($jsonFile);
		$t1_players = $this->utils->decodeJson($json);
		$this->load->library('salt');
		$this->load->model('group_level');

		$levelId = 1;
		$level=$this->group_level->getLevelById($levelId);
		$levelName=$level['vipLevelName'];
		$group=$this->group_level->getGroupById($level['vipSettingId']);
		$groupName = $group['groupName'];

		foreach ($t1_players as $group_title => $groups) {
			foreach ($groups as $player) {
				if(!$this->usernameExist($player['username'])){
					$player_basic_info = array(
						'levelId' => 1,
						'levelName' => $levelName,
						'groupName' => $groupName,
						'password' => $this->salt->encrypt($player['password'], $this->getDeskeyOG()),
						'username' => $player['username'],
						'email' => $player['email'],
						'createdOn' => $this->utils->getNowForMysql()
					);
					$player_id = $this->insertData('player', $player_basic_info);
					$player_details = array(
						'playerId' => $player_id,
						'firstName' => $player['firstName'],
						'lastName' => $player['lastName']);
					$this->insertData('playerdetails',  $player_details);
				}
			}
		}
	}


	/**
	 * Generate result set for tracking daily signup with BTAG.
	 * The date range can be set manually, but by default it set to yesterday
	 *
	 * @param  string/date $from From date
	 * @param  string/date $to   To date
	 * @return result set/array       Player record
	 */
	public function getDailySignupWithBtag($from = null, $to = null, $username = null){

		$from = $from ?: date('Y-m-d 00:00:00', strtotime(date('Y-m-d 00:00:00').' - 1 days'));
		$to = $to ?: date('Y-m-d 23:59:59', strtotime(date('Y-m-d 23:59:59').' - 1 days'));

		if(!$this->validateDate($from) || !$this->validateDate($to)) return false;

		// -- Passed date validation; Proceed to query:
		$this->db->select('p.createdOn AS ACCOUNT_OPENING_DATE, p.btag AS BTAG, p.playerId as PLAYER_ID, p.username AS USERNAME, pd.residentCountry AS PLAYER_COUNTRY');
		$this->db->from('player as p');
		$this->db->join('playerdetails as pd', 'pd.playerId = p.playerId');
		$this->db->where("p.createdOn BETWEEN '" . $from . "' AND '" . $to . "'");
		$this->db->where("p.btag != ''");
		if($username != null) $this->db->where("p.username", $username);
		$query = $this->db->get();
		$result_set = $query->result_array();
		$content = array();

		$signup_csv_headers = $this->utils->getConfig('ia_daily_signup_csv_headers');

		$content = $this->setDefaultValuesForIncomeAccessReports($result_set, $signup_csv_headers, $from, $to);

		return $content;
	}

	private function validateDate($date, $format = 'Y-m-d H:i:s')
	{
	    return $this->utils->validateDate($date, $format);
	}

	/**
	 * Generate result set for tracking daily sales with BTAG.
	 * The date range can be set manually, but by default it set to yesterday
	 *
	 * @param  string/date $from From date
	 * @param  string/date $to   To date
	 * @return result set/array       Player record
	 */
	public function getDailySalesWithBtag($from = null, $to = null, $username = null){

		$from = $from ?: date('Y-m-d 00:00:00', strtotime(date('Y-m-d 00:00:00').' - 1 days'));
		$to = $to ?: date('Y-m-d 23:59:59', strtotime(date('Y-m-d 23:59:59').' - 1 days'));
		$this->load->model('transactions');

		// -- validate the date value
		if(!$this->validateDate($from) || !$this->validateDate($to)) return false;

		// -- Create sub query for deposit amount
		$DEPOSIT_SUB_QUERY = "SELECT SUM(amount) FROM `transactions` WHERE p.playerId = to_id AND transaction_type = ".Transactions::DEPOSIT." AND to_type = ".Transactions::PLAYER." AND status = ".Transactions::APPROVED." AND created_at >= '".$from."' AND created_at <= '".$to."'";

		// -- Prepare bet counting query
		$BET_SUB_QUERY = array();
		$BET_AMOUNT_SUB_QUERY = array();
		$REVENUE_SUB_QUERY = array();
		$BONUS_SUB_QUERY = array();
		// -- game types
		$products = $this->utils->getConfig('ia_daily_sales_products');
		$final_game_types = array();

		foreach ($products as $product_key => $product) {

			// -- Prepare query for searching per game type
			$this->db->select('id');
			$arr_products = explode(",", $product);
			$like_query = "";
			$numItems = count($arr_products);
			$i = 0;
			foreach ($arr_products as $key => $arr_product) {
				$like_query .= 'game_type_code LIKE "%'.$arr_product.'%"';
				if(++$i != $numItems) $like_query .= ' OR ';
			}

			$qry = array();

			if(!empty($like_query)) $qry = (array) $this->db->get_where('game_type', $like_query)->result();

			$this->utils->debug_log(" Gametype Query -------> ", $this->db->last_query());

			// -- if game type does not exist, skip
			if(count($qry) <= 0) continue;

			$game_types = array();

			// -- store game type ids
			foreach ($qry as $qry_key => $row) {
				$final_game_types[$product_key] = $row->id;
				$game_types[] = $row->id;
			}

			$where = '(';
			$numItems = count($game_types);
			$i = 0;
			// -- Generate WHERE clause per game type ID
			foreach ($game_types as $key => $game_type) {
				$where .= 'game_type_id = '. $game_type;
				if(++$i != $numItems) $where .= ' OR ';
				else $where .= ') AND ';
			}

			// -- Add a new bet count query
			$BET_SUB_QUERY[] = " (SELECT COUNT(id) FROM `game_logs` WHERE p.playerId = player_id AND $where start_at >= '".$from."' AND start_at <= '".$to."') as ".$product_key."_BETS ,";
			// -- Add a new bet amount query
			$BET_AMOUNT_SUB_QUERY[] = " (SELECT SUM(real_betting_amount) FROM `game_logs` WHERE p.playerId = player_id AND $where start_at >= '".$from."' AND start_at <= '".$to."') as ".$product_key."_STAKE ,";
			// -- Add a new Revenue query
			$REVENUE_SUB_QUERY[] = " (SELECT SUM(loss_amount) FROM `game_logs` WHERE p.playerId = player_id AND $where start_at >= '".$from."' AND start_at <= '".$to."') as ".$product_key."_REVENUE ,";
			// -- Add a new bonus query
			$BONUS_SUB_QUERY[] = " (SELECT SUM(paid_amount) FROM `total_cashback_player_game_daily` WHERE p.playerId = player_id AND $where paid_date >= '".$from."' AND paid_date <= '".$to."' AND paid_flag = ".self::DB_TRUE." ) as ".$product_key."_BONUS ,";

		}

		// -- get the final bet query
		$final_bet_count_query = '';
		if(!empty($BET_SUB_QUERY)){
			foreach ($BET_SUB_QUERY as $BET_SUB_QUERY_KEY => $QUERY) {
				$final_bet_count_query .= $QUERY;
			}
		}

		// -- get the final bet amount query
		$final_bet_amount_query = '';
		if(!empty($BET_AMOUNT_SUB_QUERY)){
			foreach ($BET_AMOUNT_SUB_QUERY as $BET_AMOUNT_SUB_QUERY_KEY => $QUERY) {
				$final_bet_amount_query .= $QUERY;
			}
		}

		// -- get the final revenue query
		$final_revenue_query = '';
		if(!empty($REVENUE_SUB_QUERY)){
			foreach ($REVENUE_SUB_QUERY as $REVENUE_SUB_QUERY_KEY => $QUERY) {
				$final_revenue_query .= $QUERY;
			}
		}

		// -- get the final bonus query
		$final_bonus_count_query = '';
		if(!empty($BONUS_SUB_QUERY)){
			foreach ($BONUS_SUB_QUERY as $BONUS_SUB_QUERY_KEY => $QUERY) {
				$final_bonus_count_query .= $QUERY;
			}
		}


		// -- Proceed to full query:
		$this->db->select('p.btag AS BTAG, p.playerId as PLAYER_ID, pd.residentCountry as PLAYER_COUNTRY, ('.$DEPOSIT_SUB_QUERY.') as DEPOSITS, '.$final_bet_count_query.' '.$final_bet_amount_query.' '.$final_revenue_query.' '.$final_bonus_count_query);
		$this->db->from('player as p');
		$this->db->join('playerdetails as pd', 'pd.playerId = p.playerId');
		if($username != null) $this->db->where("p.username", $username);
		$this->db->where("p.btag != ''");
		$query = $this->db->get();

		$this->utils->debug_log(" Sales Query -------> ", $this->db->last_query());

		if (!$query->result_array()) return false;

		// -- Prepare some default ammounts
		$basic_result = $query->result_array();
		$this->load->model('game_logs');
		foreach ($basic_result as $player_key => $player) {
			foreach ($final_game_types as $final_game_type_key => $final_game_type) {

				if(!isset($basic_result[$player_key][$final_game_type_key . '_BETS']))
					$basic_result[$player_key][$final_game_type_key . '_BETS'] = '0';

				if(!isset($basic_result[$player_key][$final_game_type_key . '_BONUS']))
					$basic_result[$player_key][$final_game_type_key . '_BONUS'] = '0';

				if(!isset($basic_result[$player_key][$final_game_type_key . '_STAKE']))
					$basic_result[$player_key][$final_game_type_key . '_STAKE'] = '0';

				if(!isset($basic_result[$player_key][$final_game_type_key . '_REVENUE']))
					$basic_result[$player_key][$final_game_type_key . '_REVENUE'] = '0';

				$basic_result[$player_key]['TRANSACTION_DATE'] = '0';
				$basic_result[$player_key]['CHARGEBACKS'] = '0';


			}

			// -- Remove records that has no transactions for the given time
			$tmp_container = $player;
			unset($tmp_container['TRANSACTION_DATE']);
			unset($tmp_container['PLAYER_ID']);
			unset($tmp_container['BTAG']);
			unset($tmp_container['PLAYER_COUNTRY']);
			$has_no_transactions = TRUE;

			foreach ($tmp_container as $tmp_container_key => $tmp_container_value) {
				if($tmp_container_value != null && $tmp_container_value != '0') $has_no_transactions = FALSE;
			}

			if($has_no_transactions) unset($basic_result[$player_key]);

		}

		$sales_csv_headers = $this->utils->getConfig('ia_daily_sales_csv_headers');

		$content = $this->setDefaultValuesForIncomeAccessReports($basic_result, $sales_csv_headers, $from, $to);

		return $content;
	}

	/**
	 * Set default values for income access reports
	 * @param string $result_set  Reports' Data
	 * @param string $csv_headers CSV Headers
	 * @param string $from        Date from
	 * @param string $to          Date to
	 * @return array Result set with default values
	 */
	public function setDefaultValuesForIncomeAccessReports($result_set, $csv_headers, $from, $to){

		$content = array();

		foreach ($result_set as $result_set_key => $result) {
			foreach ($csv_headers as $header_key => $header_value) {
				$new_value = 'N/A';

				if(array_key_exists($header_value,$result))
				{
					$from = date('Y-m-d', strtotime($from));
					$to = date('Y-m-d', strtotime($to));
					// -- SET DEFAULT VALUES
					if($header_value == 'ACCOUNT_OPENING_DATE')
						$new_value = !empty(trim($result[$header_value])) ? date('Y-m-d', strtotime($result[$header_value])) : 'N/A';
					elseif($header_value == 'TRANSACTION_DATE')
						$new_value = $from !== $to ? $from . ' - ' . $to : $from;
					elseif($header_value == 'USERNAME')
						$new_value = !empty(trim($result[$header_value])) ? substr($result[$header_value], 0, 50): 'N/A';
					elseif($header_value == 'PLAYER_COUNTRY'){

						$new_value = !empty(trim($result[$header_value])) ? $result[$header_value] : 'N/A';
						$country_list = $this->utils->getConfig('country_list');

						if(!empty($country_list)){
							$country_code = array_search(strtoupper($new_value), $country_list);
							if($country_code) $new_value = $country_code;
						}
					}
					elseif(strpos($header_value, '_BETS') !== false)
						$new_value = !empty(trim($result[$header_value])) ? $result[$header_value] : '0';
					elseif($header_value == 'DEPOSITS' || $header_value == 'CHARGEBACKS' || (strpos($header_value, '_REVENUE') !== false) || (strpos($header_value, '_STAKE') !== false) || (strpos($header_value, '_BONUS') !== false)){

						$new_value = !empty(trim($result[$header_value])) ? $result[$header_value] : '0';
						if($new_value != '0') $new_value = number_format($new_value,2);
					}
					else
						$new_value = !empty(trim($result[$header_value])) ? $result[$header_value] : 'none';
				}

				$content[$result_set_key][$header_value] = $new_value;
			}
		}

		return $content;

	}

	public function loadImporter($importer_formatter, &$message){
		$this->load->library('importer/'.$importer_formatter);

		$importer=$this->$importer_formatter;

		if(empty($importer)){
			$message=lang('cannot load importer').' '.$importer_formatter;
			return null;
		}

		return $importer;

	}

	public function validAffCSV($importer_formatter, $filepath, &$summary, &$message){
		$importer=$this->loadImporter($importer_formatter, $message);
		if(empty($importer)){
			return false;
		}

		return $importer->validAffCSV($filepath, $summary, $message);
	}

	public function validAffContactCSV($importer_formatter, $filepath, &$summary, &$message){
		$importer=$this->loadImporter($importer_formatter, $message);
		if(empty($importer)){
			return false;
		}

		return $importer->validAffContactCSV($filepath, $summary, $message);
	}

	public function validPlayerCSV($importer_formatter, $filepath, &$summary, &$message){
		$importer=$this->loadImporter($importer_formatter, $message);
		if(empty($importer)){
			return false;
		}

		return $importer->validPlayerCSV($filepath, $summary, $message);
	}

	public function validPlayerContactCSV($importer_formatter, $filepath, &$summary, &$message){
		$importer=$this->loadImporter($importer_formatter, $message);
		if(empty($importer)){
			return false;
		}

		return $importer->validPlayerContactCSV($filepath, $summary, $message);
	}

	public function validPlayerBankCSV($importer_formatter, $filepath, &$summary, &$message){
		$importer=$this->loadImporter($importer_formatter, $message);
		if(empty($importer)){
			return false;
		}

		return $importer->validPlayerBankCSV($filepath, $summary, $message);
	}

	public function validAgencyCSV($importer_formatter, $filepath, &$summary, &$message){
		$importer=$this->loadImporter($importer_formatter, $message);
		if(empty($importer)){
			return false;
		}

		return $importer->validAgencyCSV($filepath, $summary, $message);
	}

	public function validAgencyContactCSV($importer_formatter, $filepath, &$summary, &$message){
		$importer=$this->loadImporter($importer_formatter, $message);
		if(empty($importer)){
			return false;
		}

		return $importer->validAgencyContactCSV($filepath, $summary, $message);
	}

	/**
	 *
	 * @param  string $importer_formatter
	 * @param  array  $files   import_player_csv_file, import_aff_csv_file, import_aff_contact_csv_file, import_player_contact_csv_file, import_player_bank_csv_file
	 * @param  array &$summary
	 * @param  string &$message
	 * @return bool
	 */
	public function importFromCSV($importer_formatter, array $files, &$summary, &$message){
		$importer=$this->loadImporter($importer_formatter, $message);
		if(empty($importer)){
			return false;
		}
		return $importer->importCSV($files, $summary, $message);
	}

	public function exportToCSV($importer_formatter, array $files, &$summary, &$message){
		$importer=$this->loadImporter($importer_formatter, $message);
		if(empty($importer)){
			return false;
		}

		return $importer->exportCSV($files, $summary, $message);
	}

	public function getPlayerUsernameIdMap() {
		$playerMap=[];
		// $this->utils->debug_log("Player Model getPlayerIdByUsername: ", $username, " Table: ", $this->tableName);
		$this->db->select('playerId,username')->from($this->tableName);
		$rows=$this->runMultipleRowArrayUnbuffered();
		if(!empty($rows)){
			foreach ($rows as $row) {
				$playerMap[$row['username']]=$row['playerId'];
			}
		}
		unset($rows);
		return $playerMap;
	}

	public function getPlayerIdUsernameMap() {
		$playerMap=[];
		$this->db->select('playerId,username')->from($this->tableName);
		$rows=$this->runMultipleRowArrayUnbuffered();
		if(!empty($rows)){
			foreach ($rows as $row) {
				$playerMap[$row['playerId']]=$row['username'];
			}
		}
		unset($rows);
		return $playerMap;
	}

	public function getAgentPlayerIdUsernameMap($agent_id) {
		$playerMap=[];
		$this->db->select('playerId,username');
		$this->db->where('agent_id', $agent_id);
		$this->db->from($this->tableName);
		$rows=$this->runMultipleRowArrayUnbuffered();
		if(!empty($rows)){
			foreach ($rows as $row) {
				$playerMap[$row['playerId']]=$row['username'];
			}
		}
		unset($rows);
		return $playerMap;
	}

	public function editPlayerDetails($data, $player_id) {
		$this->db->where('playerId', $player_id);
		$this->db->update('playerdetails', $data);
	}

	public function getBetsAndDepositByDate($playerId, $fromDate, $toDate){
		$from=new DateTime($fromDate);
		$to=new DateTime($toDate);

		$this->db->select_sum('total_deposit')->select_sum('total_bet')
		    ->from('player_report_hourly')->where('player_id', $playerId)
			->where('date_hour >=', $from->format('YmdH'))
			->where('date_hour <=', $to->format('YmdH'));

		$bets=0;
		$deposit=0;

		$row=$this->runOneRowArray();

		$this->utils->printLastSQL();

		if(!empty($row)){
			$deposit=$row['total_deposit']===null ? 0 : $row['total_deposit'];
			$bets=$row['total_bet']===null ? 0 : $row['total_bet'];
		}

		$this->utils->debug_log('bets and deposit', $bets, $deposit);

		return [$bets, $deposit];
	}

	/**
	 * detail: get the highest deposit count
	 *
	 * @param int $limit
	 *
	 * @return array
	 */
	public function getTopDepositCountFromPlayer($limit = 10){

		$this->db->select('player.playerId as playerid');
		$this->db->select('player.username');
		$this->db->select('player.totalDepositAmount as total');
		if ($this->utils->getConfig('only_show_approve_order_on_dashboard')) {
			$this->db->select('player.approved_deposit_count as count');
			$this->db->order_by('player.approved_deposit_count', 'desc');
		}else{
			$this->db->select('player.total_deposit_count as count');
			$this->db->order_by('player.total_deposit_count', 'desc');
		}
		$this->db->from($this->tableName);
		$this->db->limit($limit);
		$query = $this->db->get();
		$this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
		return $query->result();
	}

	/**
	 * Get all enabled players who have last activity in during given period
	 * @param	datetime	$login_time_min		The minimum datetime
	 * @param	datetime	$login_time_max		The maximum datetime
     * @param	integer	$except_levelId		The except levelId via the field, "player.levelId" aka. "vipsettingcashbackrule.vipsettingcashbackruleId".
	 * @param boolean $doPlayerFilter Enable/Disable the player filter,logged in during given period.
	 * @param string $conditionField The field name of the filter.
	 * @return	array 	array of rows containing single field 'playerId'
	 */
	public function getAllEnabledPlayersByActivityTime($login_time_min, $login_time_max, $except_levelId = null, $conditionField = 'player_runtime.lastActivityTime') {

		$this->db->from('player')
			->join('player_runtime', 'player.playerId = player_runtime.playerId', 'left')
			->where('player.status', self::OLD_STATUS_ACTIVE)
			->where('player.deleted_at is null', null, false)
			->where('player.blocked', 0)
			->where("{$conditionField} BETWEEN '{$login_time_min}' AND '{$login_time_max}'", null, false);
        if( ! is_null($except_levelId) ){
            $this->db->where('player.levelId != ', $except_levelId);
        }
		$res = $this->runMultipleRow();

		return $res;
	} // EOF getAllEnabledPlayersByActivityTime


	/**
	 * Get playerId's for players who has logged in during given period
	 * @param	datetime	$login_time_min		The minimum datetime
	 * @param	datetime	$login_time_max		The maximum datetime
	 * @return	array 	array of rows containing single field 'playerId'
	 */
	public function getPlayerIdsByLoginTime($login_time_min, $login_time_max) {
		$this->db->from('player_runtime')
			->select(['playerId', 'lastLoginTime'])
			->where("lastLoginTime BETWEEN '{$login_time_min}' AND '{$login_time_max}'", null, false);

		$res = $this->runMultipleRowArray();

		// $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());

		return $res;
	}

	public function getAvailPlayerIds() {
		$this->db->from('player')
			->select(['playerId'])
			->order_by('playerId', 'asc');

		$res = $this->runMultipleRowArray();

		return $res;
	}

	public function getAvailPlayerIdAndLastLogin($TEST_MODE = false) {
		$this->db->from('player AS P')
			->join('player_runtime AS R', 'P.playerId = R.playerId', 'left')
			->select(['P.playerId', 'R.lastLoginTime'])
		;

		if ($TEST_MODE == true) {
			$this->db->order_by('playerId', 'desc')->limit(20);
		}
		else {
			$this->db->order_by('playerId', 'asc');
		}

		$res = $this->runMultipleRowArray();

		return $res;
	}

	public function getPlayerRuntimeByPlayerId($player_id) {
		$this->db->from('player_runtime')
			->where('playerId', $player_id)
			->limit(1)
		;

		$res = $this->runOneRowArray();

		return $res;
	}

	/**
	 * detail: adjust dispatch account level for a certain player
	 *
	 * @param int $player_id
	 * @param int $new_level_id
	 * @return Boolean
	 */
	public function adjustDispatchAccountLevel($player_id, $new_level_id) {
		if ($new_level_id <= 0) {
			throw new Exception("new_level_id({$new_level_id}) is invalid");
		}

		$this->db->from($this->tableName)->where('playerId', $player_id);
		if ($this->runExistsResult()) {
			$this->db->where('playerId', $player_id);
			$result = $this->db->update($this->tableName, array('dispatch_account_level_id' => $new_level_id));
			$this->utils->debug_log('==============adjustDispatchAccountLevel new_level_id', $new_level_id);
			$this->utils->debug_log('==============adjustDispatchAccountLevel result', $result);
			return $result;
		} else {
			$this->utils->debug_log('==============adjustDispatchAccountLevel Failed');
			throw new Exception("player_id({$player_id}) not exist");
		}
	}

	/**
	 * detail: get level id for a certain player
	 *
	 * @param string $player_id
	 * @return int or string
	 */
	public function getPlayerDispatchAccountLevelId($player_id) {
		$player = $this->getPlayerById($player_id);

		if ($player) {
			return $player->dispatch_account_level_id;
		}
		return null;
	}

	/**
	 * get player's dispatch account level
	 *
	 * @param 	int
	 * @return 	array
	 */
	public function getPlayerDispatchAccountLevel($player_id) {
		$sql = "SELECT player.dispatch_account_level_id, dispatch_account_level.group_id, dispatch_account_group.group_name, dispatch_account_level.level_name
		FROM player
		LEFT JOIN dispatch_account_level ON player.dispatch_account_level_id = dispatch_account_level.id
		LEFT JOIN dispatch_account_group ON dispatch_account_level.group_id = dispatch_account_group.id
		WHERE player.playerId = ? ";

		$query = $this->db->query($sql, array($player_id));

		return $query->row_array();
	}

	function getPlayerCountByDispatchAccountLevelId($level_id) {
		$this->db->select('COUNT(playerId) member_count')
            ->from('player')
            ->join('dispatch_account_level', 'player.dispatch_account_level_id = dispatch_account_level.id', 'left')
		    ->where('player.dispatch_account_level_id', $level_id);
		return $this->runOneRowOneField('member_count');
	}

	public function getUnlimitedPlayerId() {
		$this->db->select('playerId')->from($this->tableName);
		$rows=$this->runMultipleRowArray();
		$idList=array_column($rows, 'playerId');
		return $idList;
	}

	public function getPlayersByCustomizedCondition($condition){
		$this->db->select('player.playerId');
		$this->db->from('player');
		$this->db->join('playeraccount', 'playeraccount.playerId = player.playerId');
		$this->db->where($condition);
		$rows=$this->runMultipleRowArray();
		$idList=array_column($rows, 'playerId');
		$this->utils->debug_log('getPlayersByCustomizedCondition', $idList, 'count', count($idList), 'condition', $condition, 'sql', $this->db->last_query());
		return $idList;
	}

	/**
	 * Generates referral code of a player and updates the record
	 *
	 * @param  int $player_id
	 * @return int number of affected rows
	 */
	public function generateReferralCodePerPlayer($player_id) {

		$invitationCode = $this->generateReferralCode();

		$this->db->where('playerId', $player_id);
		$this->db->where('invitationCode', "0")->or_where('invitationCode', '');
		$this->db->update('player', array('invitationCode' => $invitationCode));

		if($this->db->affected_rows() <= 0)
			return false;

		return $invitationCode;
	}

	public function isReachDailyIPAllowedRegistrationLimit($ip){
        $limit_of_single_ip_registrations_per_day = (int)$this->operatorglobalsettings->getSettingIntValue('limit_of_single_ip_registrations_per_day');
        $limit_of_single_ip_registrations_per_day = (empty($limit_of_single_ip_registrations_per_day)) ? 50 : $limit_of_single_ip_registrations_per_day;

        $count = $this->totalRegisteredPlayersByIp($ip, $this->utils->getNowSub(86400));

        return ($count >= $limit_of_single_ip_registrations_per_day);
    }

    /**
     * overview: get player's total betting amount under game history in account history
     * @param int $player_id
     * @return int
     */
    public function getPlayersTotalBettingAmount($player_id){
        $this->db->select('totalBettingAmount')
                 ->from($this->tableName)
                 ->where('playerId', $player_id);

        return $this->runOneRowOneField('totalBettingAmount');
    }

	/**
	 * Updates players' total betting amount under player table
	 * @param int $player_id
	 * @return int
	 */
	public function updatePlayersTotalBettingAmount($player_id = null) {

		if($player_id != null){
			$this->db->set('totalBettingAmount', '(SELECT ifnull(sum(betting_amount),0) FROM total_player_game_month where total_player_game_month.player_id=player.playerId)', false);
			$this->db->where('playerId', $player_id);
			$this->db->update($this->tableName);
			return $this->db->affected_rows();
		}else{
			$d=new DateTime();
			$thisMonth=$d->format('Ym');
			$d->modify('-1 month');
			$lastMonth=$d->format('Ym');
			$this->db->select('player_id')->distinct()->from('total_player_game_month')
				->where('month >=', $lastMonth)->where('month <=', $thisMonth);
			$rows=$this->runMultipleRowArray();
			$cnt=0;
			if(!empty($rows)){
				$rows=array_column($rows, 'player_id');
				$limit=500;
				$this->utils->printLastSQL();
				$this->utils->debug_log('load player', count($rows));
				$arr = array_chunk($rows, $limit);
				foreach ($arr as $data) {
					$this->db->set('totalBettingAmount', '(SELECT ifnull(sum(betting_amount),0) FROM total_player_game_month where total_player_game_month.player_id=player.playerId)', false);
					$this->db->where_in('playerId', $data);
					$this->db->update($this->tableName);
					$cnt+=$this->db->affected_rows();
					$this->utils->debug_log('try update', count($data), 'total', $cnt);
				}
			}
			unset($rows);
			return $cnt;
		}

	}

	/**
	 * Updates players' total approved withdraw amount under player table
	 * @param int $player_id
	 * @return int
	 */
	public function updatePlayersApprovedWithdrawAmount($player_id = null){

		$this->load->model('transactions');

		$this->db->set('approvedWithdrawAmount', '(SELECT ifnull(sum(transactions.amount),0) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = \''.Transactions::PLAYER.'\' AND transactions.transaction_type = \''.Transactions::WITHDRAWAL.'\' AND transactions.status = \''.Transactions::APPROVED.'\')', false);

		if($player_id != null)
			$this->db->where('playerId', $player_id);

		$this->db->update($this->tableName);

		return $this->db->affected_rows();
	}


	/**
	 * Updates players' total approved withdraw count under player table
	 * @param int $player_id
	 * @return int
	 */
	public function updatePlayersApprovedWithdrawCount($player_id = null){

		$this->load->model('transactions');

		$this->db->set('approvedWithdrawCount', '(SELECT COUNT(transactions.id) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = \''.Transactions::PLAYER.'\' AND transactions.transaction_type = \''.Transactions::WITHDRAWAL.'\' AND transactions.status = \''.Transactions::APPROVED.'\')', false);

		if($player_id != null)
			$this->db->where('playerId', $player_id);

		$this->db->update($this->tableName);

		return $this->db->affected_rows();
	}


	/**
	 * Updates players' total approved deposit count under player table
	 * @param int $player_id
	 * @return int
	 */
	public function updatePlayersTotalDepositCount($player_id){
		$player_id = intval($player_id);
		$this->db->set('total_deposit_count', '(SELECT COUNT(sale_orders.id) FROM sale_orders WHERE sale_orders.player_id = \''.$player_id.'\')', false);

		$this->db->where('playerId', $player_id);

		$this->db->update($this->tableName);

		return $this->db->affected_rows();
	}


	/**
	 * Updates players' total approved first deposit amount under player table
	 * @param int $player_id
	 * @return int
	 */
	public function updatePlayersFirstDeposit($player_id = null){

		$this->load->model('transactions');

		$this->db->set('first_deposit', '(SELECT ifnull(transactions.amount,0) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = '.Transactions::PLAYER.' AND transactions.transaction_type = '.Transactions::DEPOSIT.' AND transactions.status = '.Transactions::APPROVED.' ORDER BY created_at ASC LIMIT 0,1)', false);

		if($player_id != null)
			$this->db->where('playerId', $player_id);

		$this->db->update($this->tableName);

		return $this->db->affected_rows();

	}


	/**
	 * Updates players' total approved second deposit amount under player table
	 * @param int $player_id
	 * @return int
	 */
	public function updatePlayersSecondDeposit($player_id = null){

		$this->load->model('transactions');

		$this->db->set('second_deposit', '(SELECT ifnull(transactions.amount,0) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = '.Transactions::PLAYER.' AND transactions.transaction_type = '.Transactions::DEPOSIT.' AND transactions.status = '.Transactions::APPROVED.' ORDER BY created_at ASC LIMIT 1,1)', false);

		if($player_id != null)
			$this->db->where('playerId', $player_id);

		$this->db->update($this->tableName);

		return $this->db->affected_rows();
	}

	/**
	 * OGP-10305
	 * ---
	 * This saves all players' totals/summary to a temporary table named: `player_summary_tmp`
	 * Currently stores the following:
	 * > totalBettingAmount
	 * > totalDepositAmount
	 * > approvedWithdrawAmount
	 * > total_deposit_count
	 * > approvedWithdrawCount
	 * > second_deposit
	 * > first_deposit
	 *
	 * @return void
	 */
	public function temporarilySaveAllPlayerSummary(){

		$this->utils->debug_log('START temporarilySaveAllPlayerSummary');

		$this->load->model(['transactions', 'wallet_model', 'sale_order']);

		$usingSourceTable = $this->utils->getConfig('sync_player_deposit_withdraw_from_order_table');
		if($usingSourceTable){
			    $this->utils->debug_log('usingSourceTable');
				//region  usingSourceTable
				$totalBettingAmount_qry = '(SELECT ifnull(sum(betting_amount),0) FROM total_player_game_day where total_player_game_day.player_id = player.playerId)';

				$approvedWithdrawAmount_qry = '(SELECT ifnull(sum(transactions.amount),0) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = \''.Transactions::PLAYER.'\' AND transactions.transaction_type = \''.Transactions::WITHDRAWAL.'\' AND transactions.status = \''.Transactions::APPROVED.'\')';

				$approvedWithdrawAmount_qry = '(SELECT ifnull(sum(walletaccount.amount),0) from walletaccount where walletaccount.playerId = player.playerId AND dwStatus = \''.Wallet_model::PAID_STATUS.'\')';

				$totalDepositAmount_qry = '(SELECT ifnull(sum(sale_orders.amount),0) FROM sale_orders WHERE sale_orders.player_id = player.playerId AND sale_orders.status = \''.sale_order::STATUS_SETTLED.'\')';

				$approvedWithdrawCount_qry = '(SELECT COUNT(walletaccount.walletAccountId) FROM walletaccount WHERE walletaccount.playerId = player.playerId AND dwStatus = \''.Wallet_model::PAID_STATUS.'\')';

				$total_deposit_count_qry = '(SELECT COUNT(sale_orders.id) FROM sale_orders WHERE sale_orders.player_id = player.playerId AND sale_orders.status = \''.sale_order::STATUS_SETTLED.'\')';

				$first_deposit_qry = '(SELECT ifnull(sale_orders.amount,0)  FROM sale_orders where sale_orders.player_id = player.playerId AND sale_orders.status = \''.sale_order::STATUS_SETTLED.'\' ORDER BY sale_orders.process_time ASC LIMIT 0,1)';

				$second_deposit_qry = '(SELECT ifnull(sale_orders.amount,0)  from sale_orders where sale_orders.player_id = player.playerId and sale_orders.status = \''.sale_order::STATUS_SETTLED.'\' ORDER BY sale_orders.process_time ASC LIMIT 1,1)';
				//endregion usingSourceTable
		} else {

				// -- START Sub-queries
				$totalBettingAmount_qry = '(SELECT ifnull(sum(betting_amount),0) FROM total_player_game_day where total_player_game_day.player_id = player.playerId)';

				$approvedWithdrawAmount_qry = '(SELECT ifnull(sum(transactions.amount),0) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = \''.Transactions::PLAYER.'\' AND transactions.transaction_type = \''.Transactions::WITHDRAWAL.'\' AND transactions.status = \''.Transactions::APPROVED.'\')';
		
				$totalDepositAmount_qry = '(select ifnull(sum(amount),0) from transactions where to_type=' . Transactions::PLAYER . ' and transaction_type=' . Transactions::DEPOSIT . ' and status=' . Transactions::APPROVED . ' and player.playerId = transactions.to_id)';
		
				$approvedWithdrawCount_qry = '(SELECT COUNT(transactions.id) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = \''.Transactions::PLAYER.'\' AND transactions.transaction_type = \''.Transactions::WITHDRAWAL.'\' AND transactions.status = \''.Transactions::APPROVED.'\')';
		
				$total_deposit_count_qry = '(SELECT COUNT(transactions.id) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = \''.Transactions::PLAYER.'\' AND transactions.transaction_type = \''.Transactions::DEPOSIT.'\' AND transactions.status = \''.Transactions::APPROVED.'\')';
		
				$first_deposit_qry = '(SELECT ifnull(transactions.amount,0) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = '.Transactions::PLAYER.' AND transactions.transaction_type = '.Transactions::DEPOSIT.' AND transactions.status = '.Transactions::APPROVED.' ORDER BY created_at ASC LIMIT 0,1)';
		
				$second_deposit_qry = '(SELECT ifnull(transactions.amount,0) FROM transactions WHERE transactions.to_id = player.playerId AND transactions.to_type = '.Transactions::PLAYER.' AND transactions.transaction_type = '.Transactions::DEPOSIT.' AND transactions.status = '.Transactions::APPROVED.' ORDER BY created_at ASC LIMIT 1,1)';
				// -- END Sub-queries
		}

		// -- Select query for setting values to be inserted/updated
		$values_select_query = 'SELECT player.playerId, '.$totalBettingAmount_qry.' as totalBettingAmount, '.$approvedWithdrawAmount_qry.' as approvedWithdrawAmount, '.$totalDepositAmount_qry.' as totalDepositAmount, '.$approvedWithdrawCount_qry.' as approvedWithdrawCount, '.$total_deposit_count_qry.' as total_deposit_count, '.$first_deposit_qry.' as first_deposit, '.$second_deposit_qry.' as second_deposit, "'.$this->utils->getNowForMysql().'" as created_at FROM player_runtime as player'; 
		$days=$this->utils->getConfig('sync_player_deposit_withdraw_only_for_days');
		if(empty($days)){
			$days=3;
		}
		// query only last 7 days logged player
		// $values_select_query.= "\njoin player_runtime on (player_runtime.playerId=player.playerId) WHERE player_runtime.lastLoginTime >= DATE_SUB(NOW(), INTERVAL ".$days." DAY)";
		$values_select_query.= "\n WHERE player.lastLoginTime >= DATE_SUB(NOW(), INTERVAL ".$days." DAY)";


		// -- Prepare whole insert query

		// -- trucate the table first before inserting new records
		$this->utils->debug_log('Truncating player_summary_tmp table...');
		// $this->db->truncate('player_summary_tmp');
		$main_query = 'CREATE TEMPORARY TABLE `player_summary_tmp` ' . $values_select_query;
		$this->utils->debug_log('Successfully truncated player_summary_tmp table');


		// -- execute insert query
		$this->db->query($main_query);

		$total_affected_rows = $this->db->affected_rows();

		$this->utils->debug_log('TOTAL NUMBER OF INSERTED RECORDS: '.$total_affected_rows);

		$this->utils->debug_log('END temporarilySaveAllPlayerSummary');
	}

	/**
	 * Sync all players' summary in player_summary_tmp to player table
	 *
	 * @return boolean / int
	 */
	public function syncAllPlayersSummary(){

		// -- get chunk limit | set to 100 if config was not set
		$chunk_limit = $this->utils->getConfig('sync_all_player_withdraw_deposit_totals_chunk_limit') ?: 100;

		// -- Declare end of player list
		$player_list_end = FALSE;
		$offset = 0;
		$total_affected_rows = 0;

		while ($player_list_end === FALSE) {
			// -- get all players first
			$qry = $this->db->select('playerId')->get('player_summary_tmp', $chunk_limit, $offset);
			$all_players = $this->getMultipleRowArray($qry);

			// -- If there are no more players, end the loop.
			if(empty($all_players)){
				$player_list_end = TRUE;
				break;
			}

			// -- put player ids of current set to an array
			$player_ids_array = array_map(function($player){
				return $player['playerId'];
			}, $all_players);


			// -- implode to comma separate the array values
			$player_ids = implode(",", $player_ids_array);

			// -- build update query
			$query = '
			UPDATE `player`
			INNER JOIN `player_summary_tmp`
			ON `player_summary_tmp`.`playerId` = `player`.`playerId`
			SET
				`player`.`totalBettingAmount` = `player_summary_tmp`.`totalBettingAmount`,
				`player`.`approvedWithdrawAmount` = `player_summary_tmp`.`approvedWithdrawAmount`,
				`player`.`totalDepositAmount` = `player_summary_tmp`.`totalDepositAmount`,
				`player`.`approvedWithdrawCount` = `player_summary_tmp`.`approvedWithdrawCount`,
				`player`.`total_deposit_count` = `player_summary_tmp`.`total_deposit_count`,
				`player`.`first_deposit` = `player_summary_tmp`.`first_deposit`,
				`player`.`second_deposit` = `player_summary_tmp`.`second_deposit`
			WHERE `player`.`playerId` IN ('.$player_ids.')';

			// -- execute query
			$this->db->query($query);

			if($this->db->_error_message()) return FALSE;

			$total_affected_rows += $this->db->affected_rows();

			// -- increase limit offset
			$offset += $chunk_limit;

		}

		return $total_affected_rows;

	}

	public function getDailyPlayerWalletBalanceByDate($date, $playerId = null) {

		$_database = '';
		$_extra_db_name = '';
		$is_daily_balance_in_extra_db = $this->utils->_getDailyBalanceInExtraDbWithMethod(__METHOD__, $this->utils->getActiveTargetDB(), $_extra_db_name );
		if($is_daily_balance_in_extra_db){
			$_database = "`{$_extra_db_name}`";
			$_database .= '.'; // ex: "og_OGP-26371_extra."
		}


        $this->db->select("sub_wallet_id AS wallet_id")
        	->select("player_id")
			->select("balance");
		if( ! empty($playerId) ) {
			$this->db->from($_database. 'daily_balance USE INDEX(idx_game_date, idx_player_id)');
		}else{
			$this->db->from($_database. 'daily_balance USE INDEX(idx_game_date)');
		}

		$this->db->where('game_date', $date)
        	->where('balance !=', 0)
        	->where('sub_wallet_id IS NOT NULL');

        if (!empty($playerId)) {
			$this->db->where('player_id', $playerId);
        }

		$results = $this->runMultipleRowArray();

		if (!empty($results)) {
			$walletBalance = [];

			foreach ($results as $key => $result) {
				$walletBalance[$result["player_id"]][$result["wallet_id"]] = $result["balance"];
				// $walletBalance[$result["wallet_id"]] = $result["balance"];
			}

			return $walletBalance;
		}

		return [];
	}

	/**
	 * Will check if the tag is duplicate in specific player
	 *
	 * @param 	int
	 * @return 	boolean
	 */
	public function checkIfDuplicateTag($player_id, $blockReason) {
		$sql = "SELECT * FROM playertag  WHERE playerId = ? and tagId = ?";

		$result = false;
		$query = $this->db->query($sql, array($player_id,$blockReason));
		$data = $this->getOneRowArray($query);

		if(!empty($data)){
            $result = true;
        }
		return $result;
	}

	/**
	 * Get players list dependes on player id and currency
	 * @param  array $player_username [description]
	 * @param  string $currency  [description]
	 * @return array
	 */
	public function getPlayersByUserNameAndCurrency($player_username, $currency){
		$this->db->select('username')
				 ->where_in('username', $player_username)
				 ->where('currency', $currency)
				 ->from($this->tableName);
		$results = $this->runMultipleRowArray();

		return (!empty($results)) ? array_column($results, 'username') : [];
	}

    public function syncPlayerOnlineStatus()
    {
        include_once dirname(__FILE__) . '/../../../player/application/config/config.php';

        # Search all online user
        $querySql = "
            SELECT playerId FROM player WHERE online = 1
        ";
        $query = $this->db->query($querySql);
        $onlineUser = $query->result_array();
        $this->utils->debug_log('===========' . __FUNCTION__ . '=========== Online user :' . count($onlineUser));

        # Search all active user
        $sessionExpire   = isset($config['sess_expiration']) ? $config['sess_expiration'] : 72000;
        $timeoutSetting  = $this->utils->getConfig('player_session_timeout_seconds');
        $timeoutDatetime = date("Y-m-d H:i:s", (time() - $timeoutSetting - $sessionExpire));
        $querySql = "
            SELECT playerId FROM player_runtime WHERE lastActivityTime >= '$timeoutDatetime'
        ";
        $query = $this->db->query($querySql);
        $activeUser = $query->result_array();
        $this->utils->debug_log('===========' . __FUNCTION__ . '=========== Active user :' . count($onlineUser));

        $activeId = [];
        foreach ($activeUser as $player) {
            $activeId[] = $player['playerId'];
        }

        foreach ($onlineUser as $key => $player) {
            if (!in_array($player['playerId'], $activeId)) {
                $updateSql =  "
                    UPDATE player SET online = 0 WHERE playerId = " . $player['playerId'] . "
                ";
                $this->db->query($updateSql);
            } else {
                unset($onlineUser[$key]);
            }
        }

        $this->utils->debug_log('===========' . __FUNCTION__ . '=========== Un Active user :' . count($onlineUser));
    }


	/**
	 * Process all players by batch using their playerIds
	 *
	 * @param  callable  $callback    function to execute
	 * @param  integer   $chunk_limit
	 * @return integer                No. of affected rows
	 * @author Cholo Miguel Antonio
	 */
	public function processAllPlayersByBatch($callback, $chunk_limit = 100){

		// -- set chunk limit default to 100
		if(empty($chunk_limit)) $chunk_limit = 100;

		// -- Declare end of player list
		$player_list_end = FALSE;
		$offset = 0;
		$total_affected_rows = 0;

		$success = true;

		while ($player_list_end === FALSE) {
			// -- get all players first
			$qry = $this->db->select('playerId')->get('player', $chunk_limit, $offset);
			$all_players = $this->getMultipleRowArray($qry);

			// -- If there are no more players, end the loop.
			if(empty($all_players)){
				$player_list_end = TRUE;
				break;
			}

			// -- put player ids of current set to an array
			$player_ids_array = array_map(function($player){
				return $player['playerId'];
			}, $all_players);

			// -- call the callable
			$success = call_user_func_array($callback, array(&$total_affected_rows, &$player_ids_array));

			// -- if problem occurs, stop the loop
			if(!$success) return $total_affected_rows;

			$offset += $chunk_limit;

		}

		return $total_affected_rows;
	}


    public function checkCrossSiteByUsername($username, $useLock = false)
    {
        $result = false;
        $controller = $this;
        $usernameCrossSiteUrl = $this->utils->getConfig('username_cross_site_url');
        $crossSiteUserNameChecking = function () use ($controller, $username, $usernameCrossSiteUrl, &$result) {
            foreach ($usernameCrossSiteUrl as $key => $value) {
                $url = $value;
                $postdata = array("username" => $username);
                $response = $this->utils->simpleSubmitPostForm($url, $postdata);
                $response = json_decode($response,true);
                if($response == true){
                    $result = $response;
                }
            }
            # always true    for lockAndTrans
            return true;
        };

        if ($useLock) {
            $lockResult = $this->lockAndTrans(Utils::GLOBAL_LOCK_ACTION_REGISTRATION . '-' . $username, 0, $crossSiteUserNameChecking,false);
            return (!$lockResult) ? true : (($result) ? true : false);
        } else {
            $crossSiteUserNameChecking();
        }


        return $result;
    }

    public function getReferrerByPlayerId($playerId) {
    	$this->db->from('playerfriendreferral')
    		->select('playerId')
    		->where([ 'invitedPlayerId' => $playerId ])
    	;

    	$referrer_id = $this->runOneRowOneField('playerId');

    	if (empty($referrer_id)) {
    		return [];
    	}

    	$referrer = $this->getPlayerArrayById($referrer_id);

    	return $referrer;
    }

    /**
     * Returns names of tags for given player
     *
     * @param   int     $player_id      == player.playerId
     * @return	array 	plain array of tag names
     */
    public function getPlayerTagsForApi($player_id) {
    	$this->from('playertag AS PT')
    		->join('tag AS T', 'PT.tagId = T.tagId')
    		->where('PT.playerId', $player_id)
    		->select('tagName')
    	;

    	$rows = $this->runMultipleRowArray();

    	$res = [];
    	if (!empty($rows)) {
	    	foreach ($rows as $row) {
	    		$res[] = $row['tagName'];
	    	}
	    }

    	return $res;
    }

    /**
	 * Will get tag (first row) based on the id
	 *
	 * @param 	int The tagId of tag.
	 * @param &object The query, for get all rows with foreach ($query->result() as $row){...}
	 * @return 	array The first row.
	 *
	 */
	public function getPlayerTagById($tag_id, &$query) {
		$sql = "SELECT * FROM playertag AS pt INNER JOIN tag AS t ON pt.tagId = t.tagId WHERE pt.tagId = ? ";

		$query = $this->db->query($sql, array($tag_id));

		if ($query->num_rows() > 0)
		{
			if (!$query->row_array()) {
				return false;
			} else {
				return $query->row_array();
			}
		}else{
			return false;
		}
	}

    /**
     * Returns all-time total betting amount of all players, OGP-12377
     * @param	none
     * @return	double	Sum of all player.totalBettingAmount field
     */
    public function getTotalBettingAmountAllTime() {
    	$this->from('player')
    		->select('SUM(totalBettingAmount) AS total')
    	;

    	$res = $this->runOneRowOneField('total');

    	return floatval($res);
    }

    public function getTemppassById($playerId){
    	$this->db->select('temppass')->from(['playerdetails'])
    		->where('playerId', $playerId);

    	return $this->runOneRowOneField('temppass');
    }

    public function updateAgentIdByPlayerId($playerId, $agentId){
    	$this->db->set('agent_id', $agentId)->where('playerId', $playerId);

    	return $this->runAnyUpdate('player');
	}

	public function getPlayerListCreatedOn() {
		$this->db->select('MIN(createdOn) AS min, MAX(createdOn) AS max')->from('player');
		$res = $this->runOneRow();
		return $res;
	}

	/**
     * get agent name by player id
     *
     * @param  int
     * @return string
     */
    public function getAgentNameByPlayerId($player_id) {
    	$this->db->select('agency_agents.agent_name')
		     ->from('agency_agents')
		     ->join('player', 'agency_agents.agent_id = player.agent_id')
		     ->where('player.playerId', $player_id);
		$result = $this->runOneRowArray();
		if (empty($result)) {
            return null;
        }
		return $result['agent_name'];
    }

    public function getPlayerIdAndAgentIdByUsername($username){
		if(!empty($username)){
			$this->db->select('playerId, agent_id')->from('player')->where('username', $username);
			$row=$this->runOneRowArray();
			if(!empty($row)){
				return [$row['playerId'], $row['agent_id']];
			}
		}
		return [null, null];
	}

	public function getResetCodeExpireByPlayerId($player_id) {
		$this->db->select('resetCode,resetExpire');
		$this->db->where('playerId', $player_id);
		$qry = $this->db->get($this->tableName);

		return $qry->result_array();
	}

    const ERROR_USERNAME_PASSWORD_DOESNOT_MATCH=1;
    const ERROR_USERNAME_BLOCKED=2;
    const ERROR_USERNAME_DELETED=3;

    /**
     * login by username/password
     *
     * no any session in this function
     *
     * @param  string $username
     * @param  string $password
     * @return array [$playerId, $error]
     */
    public function loginBy($username, $password,
    		$allow_clear_session, $updateOnlineStatus, $sendNotification){
    	//search by username
		$this->db->select('playerId, password, status, deleted_at')->from('player')->where('username', $username);
		$row=$this->runOneRowArray();
		$playerId=null;
		$errorCode=null;
		if(!empty($row)){
			$this->utils->debug_log('found player', $row['playerId'], $username);
			//check status
			if($row['status']==self::OLD_STATUS_ACTIVE && empty($row['deleted_at'])){
				$logged=false;
				$isEmptyPassword=empty($row['password']);
				if($isEmptyPassword && !empty($this->utils->getConfig('external_login_api_class'))){
					//try external login
					$message=null;
					$logged=$this->utils->login_external($row['playerId'], $username, $password, $message);
					$this->utils->debug_log('7778.external login username:'.$username, $logged, $message);
				}else{
					$logged=$this->utils->decodePassword($row['password'])==$password;
				}
				$this->utils->debug_log('isEmptyPassword', $isEmptyPassword);

				if($logged){

					$this->utils->debug_log($username.' logged', $logged);

					$this->load->model(array('operatorglobalsettings', 'external_system', 'game_provider_auth'));

					if($isEmptyPassword){
						//update empty password
						$this->resetPassword($row['playerId'], ['password'=>$this->utils->encodePassword($password)]);
						$this->game_provider_auth->updateEmptyPassword($row['playerId'], $password);
					}

					//process single session
					if($allow_clear_session){
						$single_player_session=$this->operatorglobalsettings->getSettingBooleanValue('single_player_session');
						$this->utils->debug_log('single_player_session is', $single_player_session, 'allow_clear_session', $allow_clear_session);
						if ($single_player_session && $allow_clear_session) {
	                        $this->load->library(array('player_library'));
	                        $kickedPlayer = $this->player_library->kickPlayer($row['playerId']);
	                        if($kickedPlayer){
	                            $this->player_library->kickPlayerGamePlatform($username, $row['playerId']);
	                        }
						}
					}

					$this->utils->debug_log($username.' allow_clear_session', $allow_clear_session);

					//update online
                    if($updateOnlineStatus){
	                    $this->updatePlayerOnlineStatus($row['playerId'], Player_model::PLAYER_ONLINE);
						$this->updateLoginInfo($row['playerId'], $this->input->ip_address(), $this->utils->getNowForMysql());
					}

					$this->utils->debug_log($username.' updateOnlineStatus', $updateOnlineStatus);

					//notify to player
                    if($sendNotification){
	                    $lastActivity = $this->getLastActivity($row['playerId']);
						if(!empty($lastActivity)){
					        $this->load->library(['player_notification_library']);
	                        list($city, $countryName) = $this->utils->getIpCityAndCountry($lastActivity->lastLoginIp);
	                        $this->player_notification_library->success($lastActivity->playerId, Player_notification::SOURCE_TYPE_LAST_LOGIN, 'player_notify_success_last_login_title', [
	                            'player_notify_success_last_login_message',
	                            $lastActivity->lastLoginTime,
	                            $lastActivity->lastLoginIp,
	                            $countryName,
	                            $city
	                        ]);
	                    }
                    }

					$this->utils->debug_log($username.' sendNotification', $sendNotification);

					$playerId=$row['playerId'];
				}else{
					$this->utils->debug_log('username password doesnot match', $this->utils->decodePassword($row['password']), $password);
					$errorCode=self::ERROR_USERNAME_PASSWORD_DOESNOT_MATCH;
				}
			}else{
				$this->utils->debug_log('status is wrong', $row);
				if($row['status']!=self::OLD_STATUS_ACTIVE){
					$errorCode=self::ERROR_USERNAME_BLOCKED;
				}elseif(!empty($row['deleted_at'])){
					$errorCode=self::ERROR_USERNAME_DELETED;
				}
			}
		}

		return [$playerId, $errorCode];
    }

	/**
	 * gets player's affiliate
	 * ported from player.php, OGP-16961
	 * @param	int		$player_id		== player.playerId
	 * @return	string	Affiliate username for player, or null if unapplicable
	 */
	public function getAffiliateOfPlayer($player_id, $field = 'username') {

		$this->db->from("{$this->tableName} AS P")
			->join('affiliates as A', 'A.affiliateId = P.affiliateId')
			->where("P.{$this->idField}", $player_id);
			;
		switch ($field) {
			case 'affiliateId':
				$this->db->select('A.affiliateId');
				$res = $this->runOneRowOneField('affiliateId');
				break;
			case 'username':
			default:
				$this->db->select('A.username');
				$res = $this->runOneRowOneField('username');
				break;
		}


		return $res;
	}

	/**
	 * gets player's reg affiliate code
	 * @param	int		$player_id == player.playerId
	 * @return	string	Affiliate source code when reg
	 */
	public function getPlayerRegAffCode($player_id) {

		$this->db->from("affiliate_traffic_stats")
			->where("player_id", $player_id)
			->select([ 'tracking_source_code' ]);

		$res = $this->runOneRowOneField('tracking_source_code');

		return $res;
	}

	public function batchDeleteSession($timeout, $sessionTable){

		$climate = new \League\CLImate\CLImate;
		$cnt=0;
		$this->db->select('last_activity')->from($sessionTable)
		  ->order_by('last_activity')->limit(1);

		$firstActivity=$this->runOneRowOneField('last_activity');
		$nowTime=time();
		$this->utils->debug_log('search last_activity', $firstActivity, $nowTime, $timeout);
		if(!empty($firstActivity)){
			while($nowTime-$firstActivity>=$timeout){
				$this->utils->debug_log('try delete '.$sessionTable, $nowTime, $firstActivity, $timeout);
				// $input=$climate->input('delete '.$sessionTable.' by '.$firstActivity);
				// $response = $input->prompt();
				//next 100
				$firstActivity+=100;
				//available rows
				$this->db->where('last_activity <=', $firstActivity);
				$this->runRealDelete($sessionTable);
				$currentCnt=$this->db->affected_rows();
				$this->utils->debug_log('delete '.$sessionTable, $currentCnt, $firstActivity);
				$cnt+=$currentCnt;
				// if($currentCnt==0){
				// 	//no data
				// 	break;
				// }
			}
		}

		return 'delete '.$cnt.' rows from '.$sessionTable;
	}

		/**
	 * Will get player list account given the platform id
	 *
	 * @param 	int game_platform_id
	 * @return	array
	 */
	public function getPlayerAccountListByGamePlatformId($game_platform_id, $get_zero_balance = 'false', $username_list, $page_number, $size_per_page, $agent, $max_player_create_time, $createTempTable = true) {

		if($page_number<1){
			$page_number=1;
		}

		$result=[[], 0, 0, 0];

		$limit_count=$size_per_page;
		$offset=($page_number-1)*$size_per_page;
        $agent_id=intval($agent['agent_id']);

		$whereStatement = "pa.type = 'subwallet' and pa.typeId = ? and gpa.agent_id=? and p.createdOn <= ?";
		$getZeroWhereSql = ($get_zero_balance == 'false') ? " and pa.totalBalanceAmount > 0" : "";
		$userListJoinSql = "";
		$userListWhereSql = "";

		if(!empty($username_list)){
			if($createTempTable){

				$username_list_table='gw_username_list_tmp_' . time() . random_string('alnum');
				$this->utils->debug_log('create username_list table '.$username_list_table);

				$usql = 'CREATE TEMPORARY TABLE '.$username_list_table.' select login_name as username FROM game_provider_auth LIMIT 0';
				$this->runRawUpdateInsertSQL($usql);

				$filtered_username = array();
				if(is_array($username_list)){
					$username_list = array_unique($username_list);//filter duplicate username's
					if(!empty($username_list)){
						foreach ($username_list as $key => $username) {
							$filtered_username[] = array("username" => $username);
						}
					}
				} else {
					$filtered_username[] = array("username" => $username_list);
				}

				if(!empty($filtered_username)){
					$sql = $this->db->insert_batch($username_list_table, $filtered_username);
				}

				$userListWhereSql = "";
				$userListJoinSql = "JOIN {$username_list_table} as ul ON gpa.login_name = ul.username";
			} else {
				if(is_array($username_list)){
					$userListWhereSql = 'and gpa.login_name in ("' . implode('", "', $username_list) . '")' ;
				} else {
					$userListWhereSql = "and gpa.login_name = '{$username_list}'";
				}
			}

		}


$sql = <<<EOD
SELECT
gpa.login_name as username,
pa.typeId as game_platform_id,
pa.totalBalanceAmount as balance

FROM playeraccount as pa
JOIN game_provider_auth as gpa ON pa.playerId = gpa.player_id and pa.typeId = gpa.game_provider_id
JOIN player as p ON pa.playerId = p.playerId
{$userListJoinSql}
WHERE

{$whereStatement}
{$getZeroWhereSql}
{$userListWhereSql}
limit {$offset}, {$limit_count}

EOD;

		$params=[$game_platform_id, $agent_id, $max_player_create_time];

		$this->utils->debug_log('query player account balance list', $sql, $params);
		$rows=$this->runRawSelectSQLArray($sql, $params);

		if(empty($rows)){
			$rows=[];
		}

$countSql=<<<EOD
SELECT count(pa.playerId) cnt,
sum(pa.totalBalanceAmount) as sum_balance

FROM playeraccount as pa
JOIN game_provider_auth as gpa ON pa.playerId = gpa.player_id and pa.typeId = gpa.game_provider_id
JOIN player as p ON pa.playerId = p.playerId
{$userListJoinSql}
WHERE

{$whereStatement}
{$getZeroWhereSql}
{$userListWhereSql}
EOD;

		$this->utils->debug_log('count query player account balance list', $countSql, $params);
		$cntRows=$this->runRawSelectSQLArray($countSql, $params);

		$total_pages=0;
		if(!empty($cntRows)){
			$totalCnt=$cntRows[0]['cnt'];
			$total_pages= intval($totalCnt / $size_per_page) + ($totalCnt % $size_per_page > 0 ? 1 : 0);
		}

		$total_rows_current_page=count($rows);
		$current_page=$page_number;
        if($total_pages<$current_page){
            $current_page=$total_pages;
        }

        $result=[$rows, $total_pages, $current_page, $total_rows_current_page];

        if(!empty($username_list) && $createTempTable){
        	//drop table after process
			$sql='drop table if exists '.$username_list_table;
			$this->runRawUpdateInsertSQL($sql);
        }

		return $result;
	}

	/**
	 * overview : block player
	 *
	 * @param  int	$playerId
	 * @return bool
	 */
	public function blockPlayerById($playerId) {
		$this->db->where('playerId', $playerId)->set('blocked', self::DB_TRUE);
		$success = $this->runAnyUpdate('player');
		return $success;
	}

	/**
	 * overview : unblock player
	 *
	 * @param $playerId
	 * @return bool
	 */
	public function unblockPlayerById($playerId) {
		$this->db->where('playerId', $playerId)->set('blocked', self::DB_FALSE);
		$success = $this->runAnyUpdate('player');
		return $success;
	}

	/**
	 * model method for public API /pub/get_player_list
	 *
	 * @uses	string	GET:username		player username
	 * @uses	string	GET:aff				affiliate username
	 * @uses	string	GET:agent			agent username
	 * @uses	int		GET:vip_level		vip level ID
	 * @uses	int		GET:username_exact	option, username exact match
	 * @uses	int		GET:aff_downlines	option, include direct downlines of aff
	 * @uses	int		GET:agent_downlines	option, include all downlines of agent
	 * @uses	int		GET:offset			offset, for paging
	 * @uses	int		GET:limit			limit, for paging
	 * @see		Pub::get_player_list()
	 *
	 * @return	array  	[ result, code, mesg ]
	 */
	public function get_player_list() {
		$this->load->model([ 'agency_model' ]);
		// Default return
        $ret = [
        	'success'	=> false ,
        	'code'		=> 128 ,
        	'mesg'		=> 'Execution incomplete' ,
        	'result'	=> null
        ];

		try {
			$mesgs = [];
			$mesg_success = '';
			$username_exact = !empty($this->input->get('username_exact'));

			// basic arguments
	        $basic_arg_keys = [ 'username', 'aff', 'agent', 'vip_level' ];
	        $basic_arg_check = '';
	        $arg = [];
	        foreach ($basic_arg_keys as $arg_key) {
	        	$arg[$arg_key] = trim($this->input->get($arg_key));
	        	$basic_arg_check .= $arg[$arg_key];
	        } // End foreach

	        if (empty($basic_arg_check)) {
	        	throw new Exception("0x11: Please specify at least one search keyword", 0x11);
	        }

	        // Check for each basic argument
			if (!empty($arg['username'])) {
				if ($username_exact) {
    				$player_id = $this->getPlayerIdByUsername($arg['username']);
    				if (empty($player_id)) {
    					$mesgs[] = "0x21: No exact match for given argument username ({$arg['username']})";
    				}
    			}
			}

			if (!empty($arg['aff'])) {
	        	$entry_aff = $this->affiliate->getAffiliateByName($arg['aff']);
	        	if (empty($entry_aff)) {
	        		$mesgs[] = "0x22: No match for given argument aff ({$arg['aff']})";
	        	}
	        }

			if (!empty($arg['agent'])) {
	        	$entry_agent = $this->agency_model->get_agent_by_name($arg['agent']);
	        	if (empty($entry_agent)) {
	        		$mesgs[] = "0x23: No match for given argument agent ({$arg['agent']})";
	        	}
	        }

			if (!empty($arg['vip_level'])) {
				$vip_level_kv = $this->getAllPlayerLevelsForOption('return_kv');
				if (!isset($vip_level_kv[$arg['vip_level']])) {
					$mesgs[] = "0x24: No match for given argument vip_level ({$arg['vip_level']})";
				}
			}

	        // other arguments
	        $arg['username_exact']	= $username_exact;
	        $arg['aff_downlines']	= !empty($this->input->get('aff_downlines'));
	        $arg['agent_downlines']	= !empty($this->input->get('agent_downlines'));
	        $arg['limit']			= intval($this->input->get('limit'));
	        $arg['offset']			= intval($this->input->get('offset'));

	        // limit: use 20 if not specified; not exceeding 100
	        $arg['limit'] = empty($arg['limit']) ? 20 : $arg['limit'];
	        $arg['limit'] = $arg['limit'] > 100 ? 100 : $arg['limit'];

	        // 1st pass: count results
	        $this->_get_player_list_select($arg, $basic_arg_keys);
	        $this->db->select('COUNT(P.playerId) AS count_total');
	        $row_count_total = (int) $this->runOneRowOneField('count_total');

	        // 2nd pass: run the paged results
	        $this->_get_player_list_select($arg, $basic_arg_keys);
	        $this->db->select([
	        		'P.playerId' ,
	        		'P.username' ,
	        		// online: 0 for offline, 1 for online (2 for ?)
	        		'IF(P.online <> 0, 1, 0)	AS online_status' 	,
	        		'R.lastLoginTime			AS last_login_date' ,
	        		'R.lastLoginIp				AS last_login_ip' 	,
	        		'P.blocked					AS account_status' 	,
	        		'P.createdOn				AS reg_date' 		,
	        		'P.registered_by			AS reg_by' 			,
	        		'D.registrationWebsite		AS reg_website' 	,
	        		'D.registrationIp			AS reg_ip' 			,
	        		'P.invitationCode			AS referral_code' 	,
	        		'RP.username				AS referred_by' 	,
	        		'AF.username				AS under_aff' 		,
	        		'AG.agent_name				AS under_agent' 	,
	        		'P.playerId					AS player_tags' 	,	// placeholder
	        		'P.levelId                  AS vip_level' 		, 	// placeholder
	        		'P.levelId					AS vip_level_id'	,
	        		'D.firstName				AS first_name' 		,
	        		'D.lastName					AS last_name' 		,
	        		'P.email					AS email' 			,
	        		'D.contactNumber			AS contact_number'
	        	])
	        	->order_by('P.username', 'asc')
	            ->limit($arg['limit'], $arg['offset'])
	        ;

	        $res_rows = $this->runMultipleRowArray();

	        $this->utils->printLastSQL();

	        // Format fields
	        foreach ($res_rows as & $row) {
	        	$row['online_status'] = !empty($row['online_status']);
	        	$row['account_status'] = $this->utils->getPlayerStatus(
	        		$row['playerId'], 'format', $row['account_status'], 'is_export'
	        	);
	        	$row['vip_level'] = $this->getPlayerCurrentLevelForExport($row['playerId']);
	        	$row['vip_level_id'] = (int) $row['vip_level_id'];
	        	$row['player_tags'] = $this->player_tagged_list($row['playerId'], 'text');

	        	unset($row['playerId']);
	        }

	        $result = [
	        	'row_count_total'	=> $row_count_total ,
	        	'rows'				=> $res_rows
	        ];

	        if ($row_count_total <= 0) {
	        	// throw new Exception("0x12: No result matches given search argument(s)", 0x12);
	        	$mesg_success = "0x20: No result matches given search argument(s)";
	        }
	        else {
	        	$llim = $arg['offset'] + 1;
	        	$rlim = $arg['offset'] + $arg['limit'];
	        	$rlim = $rlim > $row_count_total ? $row_count_total : $rlim;
	        	$mesg_success = sprintf("0x00: Returning row %d - %d of all %d result(s)", $llim, $rlim, $row_count_total);
	        }

	        $mesgs = array_merge($mesgs, [ $mesg_success ]);

	        $ret = [
	        	'success'	=> true ,
	        	'code'		=> 0 ,
	        	'mesg'		=> $mesgs ,
	        	'result'	=> $result ,
	        ];
	    }
	    catch (Exception $ex) {
	    	$mesgs = array_merge($mesgs, [ $ex->getMessage() ]);
	    	$ret['success']	= false;
	    	$ret['code']	= $ex->getCode();
	    	$ret['mesg']	= $mesgs;
	    	$ret['result']	= null;
	    }
	    finally {
	    	return $ret;
	    }

    } // end function get_player_list()

	/**
	 * DB select and join routine for get_player_list()
	 * @param	array 	$arg	arguments prepared in get_player_list()
	 * @see		Player_model::get_player_list()
	 * @return	none
	 */
	protected function _get_player_list_select($arg) {
		$this->load->model([ 'affiliatemodel', 'agency_model' ]);

        if ($arg['username']) {
	        if ($arg['username_exact']) {
	            $this->db->where('P.username', $arg['username']);
	        }
	        else {
	            $this->db->like('P.username', $arg['username']);
	        }
	    }

	    if ($arg['aff']) {
	    	if ($arg['aff_downlines']) {
	    		$aff_id = $this->affiliatemodel->getAffiliateIdByUsername($arg['aff']);
	    		$aff_ids = $this->affiliatemodel->getDirectDownlinesAffiliateIdsByParentId($aff_id);
	    		$aff_ids = array_merge([ $aff_id ], $aff_ids);
	    		$this->utils->debug_log(__METHOD__, 'aff_ids', $aff_ids);
	    		$this->db->where_in('P.affiliateId', $aff_ids);
	    	}
	    	else {
		    	$this->db->where('AF.username', $arg['aff']);
		    }
	    }

	    if ($arg['agent']) {
	    	if ($arg['agent_downlines']) {
	    		$agent_id = $this->agency_model->get_agent_id_by_agent_name($arg['agent']);
	    		$agent_ids = $this->agency_model->get_all_downline_arr($agent_id);
	    		$this->db->where_in('P.agent_id', $agent_ids);
	    	}
	    	else {
		    	$this->db->where('AG.agent_name', $arg['agent']);
		    }
	    }

	    if ($arg['vip_level']) {
	    	$this->db->where('P.levelId', $arg['vip_level']);
	    }

	    $this->db->from('player AS P')
	    	->join('playerdetails AS D', 'P.playerId = D.playerId', 'left')
	    	->join('player_runtime AS R', 'R.playerId = P.playerId', 'left')
	    	->join('affiliates AS AF', 'AF.affiliateId = P.affiliateId', 'left')
	    	->join('agency_agents AS AG', 'AG.agent_id = P.agent_id', 'left')
	    	->join('player AS RP', 'P.refereePlayerId = RP.playerId', 'left')
		;
	} // end function _get_player_list_select()

	/**
	 * copied from player_helper
	 * @param	int		$playerId		== player.playerId
	 * @see		Player_model::get_player_list()
	 * @return	array 	array of tag names (string)
	 */
	public function player_tagged_list($playerId){
	    $tag_list = $this->getPlayerTags($playerId);

	    if(FALSE === $tag_list || !is_array($tag_list)){
	    	return [];
	    }

	    $text_list = [];

	    foreach($tag_list as $tag_entry){
	        $text_list[] = $tag_entry['tagName'];
	    }

	    return $text_list;
	} // end function player_tagged_list()

    public function getAllPlayerInformations($playerId) {
        $this->db->select('player.*,playerdetails.*');
        $this->db->where('player.playerId', $playerId);
        $this->db->join('playerdetails', 'playerdetails.playerId = player.playerId');
        $qry = $this->db->get($this->tableName);
        return $this->getOneRow($qry);
    }


    public function getAllPlayerByStatus($status = self::STATUS_NORMAL, $fields = '*') {
        //block status
        //block =0 active
        //block =1 block
        //block =5 suspended
        //block =8 Failed login attempts
        $this->db->select($fields);
        $this->db->where('blocked', $status);
        $qry = $this->db->get($this->tableName);
        return $this->getMultipleRowArray($qry);
    }

	public function checkPlayerMultipleLoginIp($player_id, $login_ip, $login_info) {
		$this->utils->debug_log('checkPlayerMultipleLoginIp', $player_id, $login_ip);
		if (!empty($login_ip)) {
			$result_msg =
				" Login IP: " .$login_ip. " |\n".
				" Login Info: " .$login_info. " |";

			$this->utils->debug_log(
				self::DEBUG_TAG, '============checkPlayerMultipleLoginIp true, '.$result_msg
			);

			$result_msg = "=============== Multiple Logins Notification ===============\n".$result_msg;

			return array('success' => true, 'msg' => $result_msg);
		}
		return array('success' => false, 'msg' => 'no records found.');
	}
	public function getTagsMap() {
		$tagsMap=[];

		$this->db->select('tagId,tagName,tagColor')->from('tag');
		$rows=$this->runMultipleRowArrayUnbuffered();

		if(!empty($rows)){
			foreach ($rows as $row) {
				$tagsMap[$row['tagId']]['tagName']=$row['tagName'];
				$tagsMap[$row['tagId']]['tagColor']=$row['tagColor'];
			}
		}
		unset($rows);
		return $tagsMap;
	}

    public function getTagDetails($tag_id) {

		$this->db->select('tagId, tagName, tagDescription, tagColor, createBy, createdOn, updatedOn, status, evidence_type')
                ->from('tag');
        if(is_array($tag_id)){
            $this->db->where_in('tagId', $tag_id);
        }else{
            $this->db->where('tagId', $tag_id);
        }

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
			//var_dump($data);exit();
			return $data;
		}
		return false;
	}

	public function createNewTags($tagName,$userId){

		$tagData = array(
			"tagName" => $tagName,
			"tagDescription" => lang("Auto Generated Tag Through export"),
			"tagColor" => @$this->utils->generateRandomColor()['hex'],
			"createBy" => $userId,
			"createdOn" => $this->utils->getNowForMysql(),
			"updatedOn" => $this->utils->getNowForMysql(),
			"status" => 0,
		);
		return $this->insertNewTag($tagData);

	}

	/**
	 * Find players by playerdetails.id_card_number, OGP-20074
	 * @param	string	$id_card_number		== playerdetails.id_card_number
	 * @param	int		$player_id			== playerdetails.playerId, use null to search for any presence; will exclude the player if not null.
	 * @return	array 	row array of player_id if present, null if not
	 */
	public function find_players_by_id_card_number($id_card_number, $player_id = null) {
		$this->db->from('playerdetails')
			->select('playerId')
			->where('id_card_number', $id_card_number)
		;
		if (!empty($player_id)) {
			$this->db->where('playerId !=', $player_id);
		}

		$res = $this->runMultipleRowArray();

		return $res;
	}

	/**
	 * Check if id_card_number is in use by players other than provided one
	 * @param	string	$id_card_number		== playerdetails.id_card_number
	 * @param	int		$player_id      	== playerdetails.playerId.  use null to check for any usage; will search for any usage but provided one if not null.
	 * @return	bool	false if no usage found; otherwise true.
	 */
	public function is_id_card_number_in_use($id_card_number, $player_id) {
		$players_by_idnum = $this->find_players_by_id_card_number($id_card_number, $player_id);

		if (!empty($players_by_idnum)) {
			return true;
		}

		return false;
	}

	/**
	 * Return player's full name
	 * The method uses config item 'player_fullname_rule' to determine player's fullname:
	 * 		0: first name only					default rule, for most sites
	 * 		1: [first name][space][last name]	western rule, for ole777th
	 * 		2: [last name][first name]			oriental rule
	 * 		3: [last name][space][first name]	special rule 1
	 * 		4: [first name][last name]			special rule 2
	 * @param	int		$player_id		== player.playerId
	 * @return	string	player's fullname
	 */
	public function playerFullNameById($player_id) {
		$player_details = $this->player_model->getAllPlayerDetailsById($player_id);
		$first_name = $player_details['firstName'];
		$last_name = $player_details['lastName'];

		$player_fullname_rule = $this->utils->getConfig('player_fullname_rule');

		switch ($player_fullname_rule) {
			case 4 :
				$fullname = "{$first_name}{$last_name}";
				break;
			case 3 :
				$fullname = "{$last_name} {$first_name}";
				break;
			case 2 :
				$fullname = "{$last_name}{$first_name}";
				break;
			case 1 :
				$fullname = "{$first_name} {$last_name}";
				break;
			case 0 : default :
				$fullname = $first_name;
				break;
		}

		return $fullname;
	}

	public function addTagToPlayer($playerId, $tagId, $adminUserId){
		if(empty($tagId) || empty($playerId) || empty($adminUserId)){
			return false;
		}

		try{
			$adminUserId = 1;
			$insflg = false;
			$today = date("Y-m-d H:i:s");
			$tagged = $this->getPlayerTags($playerId);
			$this->utils->debug_log('addTagToPlayer $tagged', $tagged);
			$keptagArr = [];
			if (FALSE === $tagged) {
				$insflg=true;
			}else{
				foreach($tagged as $playerTag){
					$keptagArr[]= $playerTag['tagId'];
				}

				if (!in_array($tagId, $keptagArr)) {
					$insflg=true;
				}
			}

			if($insflg){
				$data = array(
					'playerId' => $playerId,
					'taggerId' => $adminUserId,
					'tagId' => $tagId,
					'createdOn' => $today,
					'updatedOn' => $today,
					'status' => 1,

				);
				$this->utils->debug_log('addTagToPlayer $data', $data);
				$this->insertAndGetPlayerTag($data);
				$rtStatus['status']=1;
			}
			return true;
		}catch(Exception $e){
			$this->utils->error_log('addTagToPlayer', $e->getMessage());
			return false;
		}
	}

	/**
	 * overview : update CPA unique click identifier
	 *
	 * @param int	$playerId
	 * @param int	$cpa_id
	 */
	public function updateCPAId($playerId, $cpa_id) {
		$this->db->set('cpaId', $cpa_id)->where('playerId', $playerId)
			->update('playerdetails');
	}

    /**
     * Will get all tags Only
     *
     * @return 	array
     */
    public function getAllTagsOnly() {
        $query = $this->db->query("SELECT * FROM tag ");
        return $query->result_array();
    }

    public function getAllDepositPlayerIds(){
        $this->db->select('playerId, username')
                 ->from($this->tableName)
                 ->where('totalDepositAmount >= ', 0)
                 ->order_by('createdOn', 'asc');

        $this->runMultipleRow();
    }

    public function getDepositPlayersByOle777thConsecutiveDepositBonus($fromDate, $toDate, $player_id = null, $ignore_trans_for_test = false, $periodMinDeposit = 3000){
        if(!$ignore_trans_for_test){
            $this->load->model(['transactions']);
            $this->db->select('p.playerId, p.username')
                ->select_sum('t.amount', 'total_deposit')
                ->from('transactions t')
                ->join('player p', 'p.playerId = t.to_id  ', 'left')
                ->join('playerdetails pd', 'pd.playerId = p.playerId', 'left')
                ->where('t.transaction_type', Transactions::DEPOSIT)
                ->where('p.disabled_promotion !=', self::TRUE)
                ->where('t.status', Transactions::APPROVED)
                ->where('t.to_type', Transactions::PLAYER)
                ->where('t.trans_date >=', $fromDate)
                ->where('t.trans_date <=', $toDate);

            if(!empty($player_id)){
                $this->db->where('t.to_id', $player_id);
            }

            $this->db->group_by('t.to_id');
            $this->db->having('total_deposit >=', $periodMinDeposit);

            $players = $this->runMultipleRowArray();
        }else{
            if(!empty($player_id)){
                // single player
                $player = $this->getPlayerInfoDetailById($player_id);
                if(!empty($player)){
                    $players[] = [
                        'playerId' => $player['playerId'],
                        'username' => $player['username'],
                        'disabled_promotion' => $player['disabled_promotion']
                    ];
                }
            }

        }

        $this->utils->printLastSQL();

        return $players;
    }

    /**
     * detail: getNotReleasedPromoPlayerListById
     *
     * @param period year/week/days/hours/minutes/seconds
     * @param time_length any number
     * @param date_base specified day
     *
     * format: date_base -time_length period
     * example : 2021-09-05 12:23:44 -1 hours
     *
     * @return boolean
     */
    public function getNotReleasedPromoPlayerListById($promorulesId, $date_base){

		$period 	 = !empty($date_base['period']) ? $date_base['period']: 'minutes';
		$time_length = !empty($date_base['time_length']) ? $date_base['time_length']: '11';
        $date_base   = !empty($date_base['date_base']) ? date('Y-m-d', strtotime($date_base['date_base'])) : $this->utils->getNowForMysql();
        $date_from   = date('Y-m-d H:i:s', strtotime("{$date_base} -{$time_length} {$period}"));
        $date_to     = $date_base;

        $this->utils->debug_log(__METHOD__,'get getNotReleasedPromoPlayerListById ', $period, $time_length, $date_base, $date_from, $date_to);

		$this->db->select('player.playerId,player.username,player.createdOn,playerdetails.registrationIp,playerpromo.promorulesId')
			->from('player')
			->join('playerdetails', 'playerdetails.playerId = player.playerId', 'left')
			->join('playerpromo', 'playerpromo.playerId = player.playerId', 'left')
			->where('player.playerId not in(select playerpromo.playerId from playerpromo where promorulesId='.$promorulesId.')')
			->where('player.createdOn >=', $date_from)
			->where('player.createdOn <=', $date_to)
			->group_by('player.playerId');

        return $this->runMultipleRow();
    }

    public function viewPlayerByBatchId($search) {
        $q1 = $this->db->query("SELECT batchId, name, count FROM `batch`
            WHERE name='".$search."'
			");

        $r1 = $q1->result_array();

        $list = "";
        foreach ($r1 as $row) {
            $cnt = $row['count'];
            for($x=0; $x < $row['count']; $x++){
                if($row['count']==1){
                    $list .= "'".$row['name']."'";
                }else{
                    $list .= "'".$row['name'].$cnt."',";
                }
                $cnt--;
            }

            if($row['count']!=1){
                $list = substr($list, 0, -1);
            }
        }

		$query = $this->db->query("SELECT playerId, username, active FROM `player`
            WHERE registered_by='mass_account' AND username IN (".$list.")
            ORDER BY username ASC
			");

		return $query->result_array();
	}

	/**
	 * queryOneRandomPlayerId
	 * no deleted, status=0
	 * @return int
	 */
	public function queryOneRandomPlayerId(){
		$this->db->select('playerId')->from('player')
			->where('deleted_at is null')
			->where('status', 0)
			->order_by('rand()')
			->limit(1);
		return intval($this->runOneRowOneField('playerId'));
	}

	/**
	 * writeBlockedPlayerRecord
	 * @param  string $username
	 * @param  string $ip
	 * @param  string $realIp
	 * @param  string $source
	 * @return boolean
	 */
	public function writeBlockedPlayerRecord($username, $ip, $realIp, $source){
		if(!$this->utils->getConfig('record_blocked_player_login')){
			// skip
			return true;
		}

		$use_real_ip=$this->utils->getConfig('try_real_ip_on_acl_api');
		$playerId=null;
		if(!empty($username)){
			$playerId=$this->getPlayerIdByUsername($username);
			if(empty($playerId)){
				$playerId=0;
			}
		}
		$data=[
			'player_id'=>$playerId,
			'username'=>$username,
			'ip'=>$ip,
			'real_ip'=>$realIp,
			'source'=>$source,
			'use_real_ip'=>$use_real_ip,
			'created_at'=>$this->utils->getNowForMysql(),
		];

		return $this->runInsertData('blocked_player_on_acl_rule', $data);
	}

	public function getPlayerFirstDepositDate($player_id) {
        $this->db
            ->from('transactions')
            ->join('player', 'player.playerId = transactions.to_id', 'left')
            ->select('transactions.created_at AS firstDepositDate')
            ->where('transactions.transaction_type', Transactions::DEPOSIT)
            ->where('transactions.status ', Transactions::APPROVED)
            ->where('transactions.to_type', Transactions::PLAYER)
            ->where('player.deleted_at', NULL)
            ->where('transactions.to_id', $player_id)
            ->order_by('transactions.created_at', 'ASC')
            ->limit(1);
        $first_deposit_date = $this->runOneRowOneField('firstDepositDate');
        return $first_deposit_date;
    }

	public function getBetPlayersForCustomizedConditions($fromDate, $toDate, $player_id, $game_type_id){
		$this->db->select('player_id, SUM(betting_amount) as total_bet')
				->from('total_player_game_day')
				->where('date >=', $fromDate)
				->where('date <=', $toDate);

		if( $game_type_id == '*' ){
			// for ignore where of game_type_id
		}else if(is_array($game_type_id)){
			if( ! in_array('*', $game_type_id) ){
				$this->db->where_in('game_type_id', $game_type_id);
			}
		}else{
			$this->db->where('game_type_id', $game_type_id);
		}

		if(!empty($player_id)){
			$this->db->where('player_id', $player_id);
		}

		$this->db->group_by('player_id');
		$players = $this->runMultipleRowArray();
		//$this->utils->printLastSQL();

		return $players;
	}

	public function getPlayerInfoForCustomizedConditions($player_id){
		$this->db->select('p.playerId, p.username, pd.registrationIp, p.disabled_promotion')
			->from('player p')
			->join('playerdetails pd', 'pd.playerId = p.playerId', 'left');

		if( is_array($player_id) ){
			$this->db->where_in('p.playerId', $player_id);
		}else{
			$this->db->where('p.playerId', $player_id);
		}
		$this->db->group_by('p.playerId');

		$players = $this->runMultipleRowArray();
        //$this->utils->printLastSQL();

        return $players;
    }

    public function getPlayerByTotalLossesWeeklyCustomizedConditions($fromDate, $toDate, $player_id, $game_type_id){
        $this->db->select('tpgd.player_id, SUM(tpgd.betting_amount) as total_bet, p.username, pd.registrationIp, p.disabled_promotion')
            ->from('total_player_game_day tpgd')
            ->join('player p', 'p.playerId = tpgd.player_id  ', 'left')
            ->join('playerdetails pd', 'pd.playerId = p.playerId', 'left')
            ->where('tpgd.date >=', $fromDate)
            ->where('tpgd.date <=', $toDate);

        if( $game_type_id == '*' ){
            // for ignore where of game_type_id
        }else if(is_array($game_type_id)){
            if( ! in_array('*', $game_type_id) ){
                $this->db->where_in('tpgd.game_type_id', $game_type_id);
            }
        }else{
            $this->db->where('tpgd.game_type_id', $game_type_id);
        }

        if(!empty($player_id)){
            $this->db->where('tpgd.player_id', $player_id);
        }

        $this->db->group_by('tpgd.player_id');
        $players = $this->runMultipleRowArray();
        //$this->utils->printLastSQL();

        return $players;
    }

    public function getPlayerByCustomizedConditions($momth, $groupId, $levelStart, $levelEnd, $playerId = null){
		$this->db->select('player.playerId,
						   player.username,
						   player.createdOn,
						   player.levelId,
						   playerdetails.contactNumber,
						   playerdetails.birthdate,
						   vipsettingcashbackrule.vipLevel,
						   vipsettingcashbackrule.vipLevelName,
						   vipsetting.groupName,
						   vipsetting.vipSettingId
						   ')
			->from('player')
			->join('playerdetails', 'playerdetails.playerId = player.playerId', 'left')
			->join('playerlevel', 'playerlevel.playerId = player.playerId', 'left')
			->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = playerlevel.playerGroupId', 'left')
			->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId and vipsetting.vipSettingId = ' . $groupId, 'left');

		if (!empty($playerId)) {
			$this->db->where('player.playerId', $playerId);
		}

		$this->db->where("DATE_FORMAT(playerdetails.birthdate, '%c') = ", (int)$momth);
		$this->db->where('player.levelId >=', $levelStart);
		$this->db->where('player.levelId <=', $levelEnd);
		$this->db->group_by('player.playerId');

		return $this->runMultipleRowArray();
    }

	public function getAllPlayersForWormhole($params) {

		$this->utils->debug_log("===================getAllPlayer params",$params);

		$limit = $params['limit'];
		$username = $params['username'];
		$date_from = $params['date_from'];
		$date_to = $params['date_to'];
		$offset = $params['offset'];
		$tag = $params['tag'];
		$first_name = $params['first_name'];
		$affiliate = $params['affiliate'];
		$vip_level = $params['vip_level'];
		$email = $params['email'];
		$phone_num = $params['phone_num'];
		$account_status = $params['account_status'] ;
	    $last_login_time = $params['lastLoginTime'];
        $last_deposit_date = $params['lastDepositDate'];

		$this->db->select('
		p.username,p.email,p.playerId,
		p.blocked As account_status,
		p.createdOn,
		pd.firstName,pd.lastName,pd.contactNumber,pd.imAccount,pd.imAccount2,pd.imAccount3,
		pd.imAccount4,pd.imAccount5,
		affiliates.username As affiliate,
		vsck.vipLevelName,
		GROUP_CONCAT(t.tagName) AS tagNames,
		vs.groupName,
		pr.lastLoginTime,
		plt.last_deposit_date as lastDepositDate
		');
		$this->db->from('player AS p');
		$this->db->join('playerdetails AS pd','p.playerId = pd.playerId ','left');
		$this->db->join('player_runtime AS pr','p.playerId = pr.playerId','left');
		$this->db->join('affiliates','p.affiliateId = affiliates.affiliateId','left');
		$this->db->join('playertag','p.playerId = playertag.playerId','left');
		$this->db->join('tag As t','playertag.tagId = t.tagId','left');
		$this->db->join('playerlevel As pl','p.playerId = pl.playerId','left');
		$this->db->join('vipsettingcashbackrule As vsck','pl.playerGroupId = vsck.vipsettingcashbackruleId','left');
		$this->db->join('vipsetting As vs','vsck.vipSettingId = vs.vipSettingId','left');
		$this->db->join('player_last_transactions As plt','p.playerId = plt.player_id','left');
		$this->db->group_by('p.playerId');
		$this->db->limit($limit);
		$this->db->offset($offset);
		$this->db->order_by("p.createdOn",'desc');
		if (!empty($username)) {
			$this->db->where("p.username = ",$username);
		}
		if (!empty($first_name)) {
		$this->db->where("pd.firstName = ",$first_name);
		}
		if (!empty($affiliate)) {
			$this->db->where("affiliates.username = ",$affiliate);
		}
		if (!empty($vip_level)) {
			if(strstr($vip_level,"-") == true){
				$search_targe_vip_group=trim(explode("-",$vip_level)[0]);
				$search_targe_vip_level=trim(explode("-",$vip_level)[1]);
				$this->db->where("vsck.vipLevelName = ",$search_targe_vip_level);
			}else{
				$this->db->like("vsck.vipLevelName",$vip_level);
			}
		}
		if (!empty($account_status)||$account_status == "0") {
			$this->db->where("p.blocked = ",intval($account_status));//是否被鎖定
		}
		if (!empty($email)) {
			$this->db->where("p.email = ",$email);
		}
		if (!empty($phone_num)) {
			$this->db->where("pd.contactNumber = ",$phone_num);
		}
		if (!empty($tag)) {
			$this->db->where("t.tagname = ",$tag);//需要修改
		}
		if (!empty($date_from)) {
			$this->db->where("p.createdOn >= ",$date_from);
		}
		if(!empty($date_to)){
			$this->db->where("p.createdOn <= ",$date_to);
		}
		if (!empty($last_login_time)) {
			$this->db->where("pr.lastLoginTime = ",$last_login_time);
		}
		if (!empty($last_deposit_date)) {
			$this->db->where("plt.last_deposit_date = ",$last_deposit_date);
		}
		$query = $this->db->get();
		$result = $query->result_array();
		return $result;
	}

	public function getAllPlayersForWormhole2($params, $page = null, $limit = null, $playerId = null) {

		$result = $this->getDataWithAPIPagination(null , function() use($params, $playerId) {

			$firstDayHourOfMon = date('Ym01') . '00';
            $lastDayHourOfMon = date('Ymt') . '23';

			$subquery_tag = "SELECT playerId, GROUP_CONCAT(tag.tagName) as tagName
							FROM playertag LEFT JOIN tag on tag.tagId = playertag.tagId GROUP BY playerId";

			$subquery_prh = "SELECT playerId,
							SUM(prh.deposit_times) as deposit_times,
							SUM(prh.total_gross) as net_deposit,
							SUM((prh.total_win - prh.total_loss) - prh.total_bonus - prh.total_cashback) as total_revenue
							FROM player_report_hourly as prh LEFT JOIN player on player.playerId = prh.player_id
							WHERE date_hour >= $firstDayHourOfMon AND date_hour <= $lastDayHourOfMon GROUP BY playerId";

			$this->db->select('
				p.username,p.email,p.playerId,p.blocked As account_status,
				p.createdOn,p.totalDepositAmount,
				pd.firstName,pd.lastName,pd.contactNumber,
				pd.imAccount,pd.imAccount2,pd.imAccount3,pd.imAccount4,pd.imAccount5,
				affiliates.username As affiliate,tag.tagName as tagNames,
				p.levelName as vipLevelName,p.groupName,pr.lastLoginTime,
				plt.last_deposit_date as lastDepositDate,
				player_report.deposit_times as depositTimesInMonth,
				player_report.net_deposit as netDepositInMonth,
				player_report.total_revenue as totalRevenueInMonth
			');

			$this->db->from('player AS p');
			$this->db->join('playerdetails AS pd','p.playerId = pd.playerId','left');
			$this->db->join('player_runtime AS pr','p.playerId = pr.playerId','left');
			$this->db->join('affiliates','p.affiliateId = affiliates.affiliateId','left');
			$this->db->join("($subquery_tag) tag", 'tag.playerId = p.playerId', 'left');
			$this->db->join("($subquery_prh) player_report", 'player_report.playerId = p.playerId', 'left');
			$this->db->join('player_last_transactions AS plt','p.playerId = plt.player_id','left');
			$this->db->group_by('p.playerId');
			$this->db->limit($params['limit']);
			$this->db->offset($params['offset']);
			$this->db->order_by('p.createdOn','desc');

			if (!empty($playerId)) {
				$this->db->where("p.playerId = ", $playerId);
			}
			if (!empty($params['first_name'])) {
			$this->db->where("pd.firstName = ", $params['first_name']);
			}
			if (!empty($params['affiliate'])) {
				$this->db->where("affiliates.username = ", $params['affiliate']);
			}
			if (!empty($params['vip_level'])) {
				if (strstr($params['vip_level'], "-") == true) {
					$vip_level = trim(explode("-", $params['vip_level'])[1]);
					$this->db->where("p.levelName = ", $vip_level);
				} else {
					$this->db->like("p.levelName", $params['vip_level']);
				}
			}
			if (!empty($params['account_status'])) {
				$this->db->where("p.blocked = ", $params['account_status']);
			}
			if (!empty($params['email'])) {
				$this->db->where("p.email = ", $params['email']);
			}
			if (!empty($params['phone_num'])) {
				$this->db->where("pd.contactNumber = ", $params['phone_num']);
			}
			if (!empty(($params['tag']))) {
				$this->db->where("FIND_IN_SET('".$params['tag']."', tag.tagName)");
			}
			if (!empty($params['date_from'])) {
				$this->db->where("p.createdOn >= ", $params['date_from']);
			}
			if (!empty($params['date_to'])) {
				$this->db->where("p.createdOn <= ", $params['date_to']);
			}
			if (!empty($params['lastLoginTime'])) {
				$this->db->where("pr.lastLoginTime = ", $params['lastLoginTime']);
			}
			if (!empty($params['lastDepositDate'])) {
				$this->db->where("plt.last_deposit_date = ", $params['lastDepositDate']);
			}
			if (!empty($params['last_deposit_date_from'])) {
				$this->db->where("plt.last_deposit_date <= ", $params['last_deposit_date_from'].' '.'23:59:59');
			}
			if (!empty($params['last_deposit_date_to'])) {
				$this->db->where("plt.last_deposit_date >= ", $params['last_deposit_date_to'].' '.'00:00:00');
			}
			if (!empty($params['deposit_times_in_month_from'])) {
				$this->db->having('depositTimesInMonth >=', $params['deposit_times_in_month_from']);
			}
			if (!empty($params['deposit_times_in_month_to'])) {
				$this->db->having('depositTimesInMonth <=', $params['deposit_times_in_month_to']);
			}
		}, $limit, $page);
		return $result;
	}

	public function getPlayerFirstDepositDateByPeriod($player_id, $date_from = null, $date_to = null) {
        $this->db
            ->from('transactions')
            ->select('transactions.created_at AS firstDepositDate')
            ->where('transactions.transaction_type', Transactions::DEPOSIT)
            ->where('transactions.status ', Transactions::APPROVED)
            ->where('transactions.to_type', Transactions::PLAYER)
            ->where('transactions.to_id', $player_id)
            ->order_by('transactions.created_at', 'ASC')
        	->limit(1);

		if (!empty($date_from)) {
			$this->db->where('transactions.created_at >=', $date_from);
		}
		if (!empty($date_to)) {
			$this->db->where('transactions.created_at <=', $date_to);
		}

        $first_deposit_date = $this->runOneRowOneField('firstDepositDate');
        return $first_deposit_date;
    }

	public function getAllSoftDeletedPlayers($player_ids = null) {
		$data = array();
		$this->db->select('player.playerId,
						   player.username,
						   player.gameName,
						   player.email,
						   playerdetails.contactNumber,
						   player.deleted_at
						   ')
			->from('player')
			->join('playerdetails', 'playerdetails.playerId = player.playerId', 'left')
			->where('deleted_at IS NOT NULL');;


		if($player_ids){ $this->db->where_in('player.playerId', $player_ids); }

		return $this->runMultipleRowArray();

	}

	public function getAllNotEmptyNamePlayers($player_ids = null) {
		$this->db->select('player.playerId,
						playerdetails.firstName,
						playerdetails.lastName')
			->from('player')
			->join('playerdetails', 'playerdetails.playerId = player.playerId', 'left')
			->where('deleted_at IS NULL')
			->where('(( firstName IS NOT NULL and firstName <> "" ) or ( lastName IS NOT NULL and lastName <> "" ))');

		if (!empty($player_ids)) {
			$this->db->where_in('player.playerId', $player_ids);
		}
		$result = $this->runMultipleRowArray();
		return $result;
	}

    /**
	 * overview : enable withdrawal by player ids
	 *
	 * @param array $playerIds
	 * @return bool
	 */
    public function enableWithdrawalByPlayerIds(array $playerIds) {
        $this->db->where_in('playerId', $playerIds)->set(['enabled_withdrawal' => self::DB_TRUE]);
        return $this->runAnyUpdate($this->tableName);
    }

    public function isPlayerTagExist($player_id, $tag_id) {
        $this->db->from('playertag')
        ->where('playerId', $player_id)
        ->where('tagId', $tag_id);

        return $this->runExistsResult();
    }

	public function updatePlayerImAccount($playerId, $imAccount){
		$updateset = [
			'imAccount'	=> $imAccount
		];

		$res = $this->db->where([ $this->idField => $playerId ])
			->set($updateset)->update('playerdetails')
		;

		return $res;
	}

	public function getPlayerByVipMonthlyCustomizedConditions($levelStart, $levelEnd, $playerId = null){
		$this->db->select('player.playerId,
						   player.username,
						   player.levelId,
						   ')
			->from('player');

		if (!empty($playerId)) {
			$this->db->where('player.playerId', $playerId);
		}

		$this->db->where('player.levelId >=', $levelStart);
		$this->db->where('player.levelId <=', $levelEnd);
		$this->db->group_by('player.playerId');
		$players = $this->runMultipleRowArray();

		return $players;
    }
}


// zR to open all folded lines
// vim:ft=php:fdm=marker
/* End of file Player_model.php */
/* Location: ./application/models/player_model.php */
