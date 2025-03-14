<?php
require_once dirname(__FILE__) . '/base_model.php';

require_once dirname(__FILE__) . '/modules/cashback_model_module.php';

/**
 * General behaviors include :
 * * get group level for a certain player
 * * adding vip group record
 * * create vip cashback game rule
 * * get all available games
 * * get VIP setting game provider
 * * create VIP cashback bonus per each game
 * * get VIP setting list to export
 * * add level to a certain player
 * * adjust level for a certain player
 * * write player level history for a certain level
 * * sync player level history by batch
 * * sync player level history
 * * add player level history game details
 * * get game allowed game level rules
 * * update all vip group bonus per each game
 * * delete cashback allowed game type of a certain cashback rule setting
 * * get vip group level details
 *
 * VIP group includes many levels
 *
 * VIP level has settings: cashback, instant deposit bonus
 *
 * vipsetting is group
 *
 * vipsettingcashbackrule is level
 *
 * playerlevel is player with level
 *
 * vipsetting.vipSettingId=vipsettingcashbackrule.vipSettingId
 * vipsettingcashbackrule.vipsettingcashbackruleId=playerlevel.playerGroupId
 * playerlevel.playerId=player.playerId
 *
 * MOVED playerLevel to player.levelId , levelName and groupName
 *
 *
 * @category Player
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Group_level extends BaseModel {

	const DEPOSIT_FIRST = 1;
	const DEPOSIT_SUCCEEDING = 2;
	const BONUS_MODE_ENABLE = 1;
	const BONUS_MODE_DISABLE = 0;
	const BONUS_TYPE_FIXAMOUNT = 1;
	const BONUS_TYPE_BYPERCENTAGE = 2;

	const UPGRADE_SETTING_ENABLE = 1;
	const UPGRADE_SETTING_DISABLE = 2;

	// for vip_upgrade_setting.level_upgrade
	const UPGRADE_ONLY = 1;
	const UPGRADE_DOWNGRADE = 2;
	const DOWNGRADE_ONLY = 3;

	const RECORD_UPGRADE = 1;
	const RECORD_DOWNGRADE = 2;
	const RECORD_SPECIFICGRADE = 3;

	const GRADE_FAILED = 0;
	const GRADE_SUCCESS = 1;

	const REQUEST_TYPE_AUTO_GRADE = 1;
	const REQUEST_TYPE_MANUAL_GRADE = 2;
	const REQUEST_TYPE_SPECIFIC_GRADE = 3;

	//for cashback_request
	const CASHBACK_STATE_REQUEST = 1;
	const CASHBACK_STATE_APPROVED = 2;
	const CASHBACK_STATE_PAID = 3;
	const CASHBACK_STATE_DECLINED = 4;

	const CASHBACK_PERIOD_SETTING_DAILY = 1;
	const CASHBACK_PERIOD_SETTING_WEEKLY = 2;

	const CASHBACK_SETTINGS_NAME = 'cashback_settings';
	const LAST_UPDATE_CASHBACK_NAME= 'last_batch_update_cashback_time';

	const ACCUMULATION_MODE_DISABLE = 0; // Accumulation: No
	const ACCUMULATION_MODE_FROM_REGISTRATION = 1; // Accumulation: Yes, Computation from Registration Date
	const ACCUMULATION_MODE_LAST_UPGEADE = 2; // Deprecated!! Accumulation: Yes, Computation from Last Upgeade Period
	const ACCUMULATION_MODE_LAST_DOWNGRADE = 3; // Deprecated!! Accumulation: Yes, Computation from Last Downgrade Period
	const ACCUMULATION_MODE_LAST_CHANGED_GEADE = 4; // Accumulation: Yes, Computation from Last changed Grade Period
	const ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET = 5; // Accumulation: Yes, Computation from Last changed Grade Period With Reset if met

	const DOWN_MAINTAIN_TIME_UNIT_DAY = 1;
	const DOWN_MAINTAIN_TIME_UNIT_WEEK = 2;
	const DOWN_MAINTAIN_TIME_UNIT_MONTH = 3;


	const CASHBACK_TARGET_PLAYER = 1;
	const CASHBACK_TARGET_AFFILIATE = 2;

    const CODE_DECREASEVIPGROUPLEVEL_IN_LEVEL_EXIST_PLAYER = 343;
    const CODE_DECREASEVIPGROUPLEVEL_IN_DECREASE_COMPLETED = 351;
    const CODE_DECREASEVIPGROUPLEVEL_IN_DECREASE_NO_GOOD = 353;

    // The exception for to cancel in the try block,
    // the try block is in foreach loop.
    // just assign by line no.
    const EXCEPTION_CODE_IN_CANCEL_CONTINUE = 105; // for cancel of try {} from foreach, just assign by line

    const FILENAME_SUSPEND_IN_DRYRUN_FOR_CALCULATECASHBACK = 'suspend_in_dryrun_of_calculatecashback';
    // shell: touch secret_keys/suspend_in_dryrun_of_calculatecashback

	const SYSTEM_FEATURE_CASHBACK= 'disable_auto_add_cash_back';

	protected $groupTable = 'vipsetting';
	protected $groupId = 'vipSettingId';

	protected $levelTable = 'vipsettingcashbackrule';
	protected $levelId = 'vipsettingcashbackruleId';
	protected $levelName = 'vipLevelName';
	protected $levelGroupId = 'vipSettingId';

	protected $playerTable = 'player';
	protected $playerLevelId = 'levelId';
	protected $playerLevelName = 'levelName';

	//move to playerLevel to player.levelId and levelName
	protected $playerlevelTable = 'playerlevel';
	protected $plLevelId = 'playerGroupId';

	//@deprecated move to group_level_cashback_percentage_history
	protected $levelGamePlatformTable = 'vipsettingcashbackbonuspergame';
	protected $levelGamePlatformId = 'gameType';
	protected $levelGamePlatformLevelId = 'vipsettingcashbackruleId';

	//@deprecated move to group_level_cashback_percentage_history
	protected $levelAllowedGameTable = 'vipsetting_cashback_game';
	protected $levelAllowedGameLevelId = 'vipsetting_cashbackrule_id';
	//@deprecated move to group_level_cashback_percentage_history
	protected $playerLevelHistoryTable = 'player_level_history';
	protected $plHistoryGameDetailsTable = 'player_level_history_game_api_details';
	protected $plHistoryAllowedGamesTable = 'player_level_history_allowed_games';

	protected $table_agency_game_rolling_comm = 'agency_game_rolling_comm';

	private $grade_record = [];

    private $max_min_id_in_game_logs_by_end_at = []; // for property cache in cronjob

	// PLAH = player_level_adjustment_history
	private $PLAH = [];

	const DEFAULT_GAME_PLATFORM = PT_API;

	private $basicAmountList = [];
    private $enable_multi_currencies_totals = false; // for vip levels up/down grade checking



	use cashback_model_module;

	function __construct() {
		parent::__construct();

        if( $this->utils->isEnabledMDB()
            && $this->utils->getConfig('enable_multi_currencies_totals')
        ){
            $this->enable_multi_currencies_totals = true;
        }
	}

	/**
	 * Get the private attr.,"PLAH".
	 * @return array
	 */
	public function getPLAH(){
		return $this->PLAH;
	}// EOF getPLAH

	/**
	 * detail: get group level for a certain player
	 *
	 * @param int $playerId player playerId
	 * @return array
	 */
	public function getPlayerGroupLevel($playerId) {
		$this->db->select('levelId')->from('player');
		$this->db->where('playerId', $playerId);

		$query = $this->db->get();
		$rows = $this->getMultipleRow($query);
		return $this->convertRowsToArray($rows, 'levelId');

	}

    public function detectFileInSecret_keys($filename){
		$_pathfile = APPPATH . '../../secret_keys/'.$filename;
		return file_exists($_pathfile);
    }
	/**
	 * load the Basic Amount List By the json file.
	 * It will referenced to player_basic_amount_list_json_filename of the setting in config.
	 * The json file should be in the secret_keys folder.
	 *
	 * @return void
	 */
	public function initialBasicAmountImported(){
		$player_basic_amount_list_json_filename = $this->utils->getConfig('player_basic_amount_list_json_filename');
		$player_basic_amount_list_json_pathfile = APPPATH . '../../secret_keys/'.$player_basic_amount_list_json_filename;
		if (file_exists($player_basic_amount_list_json_pathfile) ) {
			$strJsonFileContents = file_get_contents($player_basic_amount_list_json_pathfile);
			$assoc = true;
			$this->basicAmountList = $this->utils->json_decode_handleErr($strJsonFileContents, $assoc);
		}
		$this->utils->debug_log('OGP23800.187.basicAmountList.count', count($this->basicAmountList));
	} // EOF initialBasicAmountImported()
	/**
	 * Release the basicAmountList attr.
	 *
	 * @return void
	 */
	public function resetBasicAmountAfterImported(){
		if( ! empty($this->basicAmountList) ){
			unset($this->basicAmountList);
			$this->basicAmountList = []; // re-assign to initial value
		}
	} // EOF resetBasicAmountAfterImported()
	/**
	 * Get the Basic Amount by username
	 *
	 * @param string the player's username.
	 * @return array The base amount detail,
	 * - $theBasicAmountDetail[username] string|null If null, it is means Not found.
	 * - $theBasicAmountDetail[total_deposit_amount] float|integer
	 * - $theBasicAmountDetail[total_bet_amount] float|integer
	 */
	public function getBasicAmountAfterImported($username){
		$theBasicAmountDetail = [];
		$theBasicAmountDetail['username'] = null;
		$theBasicAmountDetail['total_deposit_amount'] = 0;
		$theBasicAmountDetail['total_bet_amount'] = 0;
		$player_basic_amount_list = &$this->basicAmountList;
		if( ! empty($player_basic_amount_list) ){
			$username_list = array_column($player_basic_amount_list, 'username');
			$key = array_search($username, $username_list);
			if($key !== false){
				$theBasicAmountDetail = $player_basic_amount_list[$key];
			}
		}

		$is_enabled_player_basic_amount_list_data_table = $this->utils->getConfig('player_basic_amount_list_data_table');
		if($is_enabled_player_basic_amount_list_data_table){
			$this->load->model(['player_basic_amount_list']);
			$data_list = $this->player_basic_amount_list->getDataListByField($username);
			if(!empty($data_list)){
				// override
				$theBasicAmountDetail['username'] = $data_list[0]['player_username'];
				$theBasicAmountDetail['total_deposit_amount'] = $data_list[0]['total_deposit_amount'];
				$theBasicAmountDetail['total_bet_amount'] = $data_list[0]['total_bet_amount'];
			}
		}

		return $theBasicAmountDetail;
	} // EOF getBasicAmountAfterImported()

	/**
	 * detail: adding vip group record
	 *
	 * @param array $data
	 * @retrurn Boolean
	 */
	public function addVIPGroup($data, $db = null) {
        if(empty($db)) {
            $db = $this->db;
        }
		$db->insert($this->groupTable, $data);

		$vipdata['vipsettingId'] = $db->insert_id();

		$enforce_cashback_target = $this->utils->getConfig('enforce_cashback_target');
		if( ! empty($enforce_cashback_target) ){
			$vipdata['cashback_target'] = $enforce_cashback_target;
		}

		for ($i = 1; $i < $data['groupLevelCount'] + 1; $i++) {
			$vipdata['minDeposit'] = 100 * $i;
			$vipdata['maxDeposit'] = 1000 * $i;
			$vipdata['dailyMaxWithdrawal'] = 10000 * $i;
			$vipdata['vipLevel'] = $i;
			$vipdata['vipLevelName'] = 'Level Name ' . $i;

			$this->createVipCashbackGameRule($vipdata, $db);
		}
        return $vipdata['vipsettingId'] ;
	}

	/**
	 * detail: create vip cashback game rule
	 *
	 * @param array $data
	 * @return Boolean
	 */
	public function createVipCashbackGameRule($data, $db = null) {
        if(empty($db)) {
            $db = $this->db;
        }
		$db->insert($this->levelTable, $data);
        $affected_id = $db->insert_id();
        return $affected_id;
	}

	/**
	 * detail: add Cashback allowed game
	 *
	 * @param array $game_data
	 * @return Boolean
	 */
	public function addCashbackAllowedGame($game_data) {}

	/**
	 * detail: get VIP setting allowed game
	 *
	 * @param int $vipsettingcashbackruleId vipsettingcashbackrule vipsettingcashbackruleId
	 * @return array
	 */
	public function getVipSettingAllowedGame($vipsettingcashbackruleId) {}

	/**
	 * detail: get VIP setting game provider
	 *
	 * @param int $vipsettingcashbackruleId vipsetting_cashback_game vipsetting_cashbackrule_id
	 * @return array
	 */
	public function getVipSettingGamesProvider($vipsettingcashbackruleId) {}

	/**
	 * detail: get vip setting game typed
	 *
	 * @param int $vipsettingcashbackruleId vipsetting_cashback_game vipsetting_cashbackrule_id
	 * @return array
	 */
	public function getVipSettingsGamesType($vipsettingcashbackruleId) {}

	/**
	 * detail: create VIP cashback bonus per each game
	 *
	 * @param array $data
	 * @return Boolean
	 */
	public function createVipCashbackBonusPerGame($data) {}

	/**
	 * detail: get VIP Setting Lists
	 *
	 * @param string $sort
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array or Boolean
	 */
	public function getVIPSettingList($sort, $limit, $offset) {
		if ($limit != null) {
			// $limit = "LIMIT " . $limit;
		}

		if ($offset != null && $offset != 'undefined') {
			// $offset = "OFFSET " . $offset;
		} else {
			$offset = null;
		}
		if ($offset == 'undefined') {
			$offset = null;
		}
		$this->db->select('vipsetting.*, admin1.username AS createdBy, admin2.username AS updatedBy', false)
			->from($this->groupTable)
			->join('adminusers as admin1', 'admin1.userId = vipsetting.createdBy', 'left')
			->join('adminusers AS admin2', 'admin2.userId = vipsetting.updatedBy', 'left')
			->order_by($this->groupTable . "." . $sort, 'asc')
			->where('vipsetting.deleted <> 1');
		if ($limit) {
			$this->db->limit($limit, $offset);
		}

		$query = $this->db->get();
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;

	}

	/**
	 * detail: get VIP setting list to export
	 *
	 * @return array
	 */
	public function getVIPSettingListToExport() {
		$this->db->select('vipsetting.groupName,
						   vipsetting.groupLevelCount,
						   vipsetting.groupDescription,
						   vipsetting.createdOn,
						   adminusers.userName as createdBy,
						   vipsetting.updatedOn,
						   vipsetting.status,
						   adminusers.userName as updatedBy
							', false)->from('vipsetting')
			->join('adminusers', 'adminusers.userId = vipsetting.createdBy')
			->where('vipsetting.deleted <> 1');

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			return $query->result();
		}
		return false;
	}

	/**
	 * detail: get VIP Group name
	 *
	 * @param string $group_name
	 * @return array
	 */
	public function getVipGroupName($group_name) {
		$this->db->from($this->groupTable)->where('groupName', $group_name);
		$query = $this->db->get();

		if (!$query->row_array()) {
			return false;
		} else {
			return $query->row_array();
		}
	}

	/**
	 * detail: get level name of a certain level
	 *
	 * @param int $levelId vipsettingcashbackrule level id
	 *
	 * @return string or Boolean
	 */
	public function getLevelName($levelId) {
		$this->db->select($this->levelName);
		$this->db->where($this->levelId, $levelId);
		$query = $this->db->get($this->levelTable);
		$row = $query->row_array();
		return $row ? $row[$this->levelName] : false;
	}

	/**
	 * detail: add level to a certain player
	 *
	 * @param int $playerId
	 * @param int $levelId
	 * @return int
	 */
	public function addPlayerLevel($playerId, $levelId = null) {

        if(empty($levelId)) {
            $levelId = empty($this->utils->getConfig('default_level_id'))? 1 : $this->utils->getConfig('default_level_id');
        }
		$playerLevelId = $this->insertData($this->playerlevelTable, array(
			'playerId' => $playerId,
			'playerGroupId' => $levelId,
		));

		$this->updatePlayerTable($playerId, $levelId);

		return $playerLevelId;
	}

	/**
	 * detail: adjust level for a certain player
	 *
	 * @param int $playerId
	 * @param int $newLevelId
	 * @return Boolean
	 */
	public function adjustPlayerLevel($playerId, $newLevelId, $db = null) {
        if(empty($db)){
            $db=$this->db;
        }

		if ($newLevelId <= 0) {
			throw new Exception("newLevelId({$newLevelId}) is invalid");
		}

        $db->from($this->playerlevelTable)->where('playerId', $playerId);
		if ($this->runExistsResult($db)) {
            $this->utils->debug_log('OGP-33350.494.last_query', $db->last_query(), 'for catch legacy level .');

			$db->where('playerId', $playerId);
			$db->update($this->playerlevelTable, array('playerGroupId' => $newLevelId));
		} else {
			$data = array('playerId' => $playerId, 'playerGroupId' => $newLevelId);
            $db->insert($this->playerlevelTable, $data);
		}

        $this->utils->debug_log('OGP-33350.503.afftectedRows:', $db->affected_rows()
                                , 'last_query', $db->last_query(), 'for catch legacy level .');

		$return = $this->updatePlayerTable($playerId, $newLevelId, $db);

        $this->utils->debug_log('OGP-33350.508.afftectedRows:', $db->affected_rows()
                                , 'last_query', $db->last_query(), 'for catch legacy level .');

        return $return;
	}

	/**
	 * detail: adjust level for batch players
	 *
	 * @param int $playerId
	 * @param int $newLevelId
	 * @return Boolean
	 */
	public function batchAdjustPlayerLevel($playerId, $newLevelId) {

		$result = array();

		$this->db->from($this->playerlevelTable)->where('playerId', $playerId);
		$this->db->where('playerId', $playerId);
		$this->db->update($this->playerlevelTable, array('playerGroupId' => $newLevelId));

		$result['updatePlayerlevel'] = true;
		if ($this->db->_error_message()) {
			$result['updatePlayerlevel'] = false;
		}

		$result['updatePlayer'] = false;
		if ($this->updatePlayerTable($playerId, $newLevelId)) {
			$result['updatePlayer'] = true;
		}

		return $result;

	}

	/**
	 * detail: get level id for a certain player
	 *
	 * @param string $playerId
	 * @return int or string
	 */
	public function getPlayerLevelId($playerId) {
		$this->load->model(array('player_model'));
		$player = $this->player_model->getPlayerById($playerId);

		if ($player) {
			return $player->levelId;
		}
		return null;
	}

	/**
	 * detail: MOVED TO adjustPlayerLevel
	 *
	 * @param int $playerId
	 * @param array $data
	 * @return Boolean
	 */
	public function changePlayerLevel($playerId, $data) {
		$newLevelId = $data[$this->plLevelId];
		return $this->adjustPlayerLevel($playerId, $newLevelId);
	}

	/**
	 * detail: get id from the given array
	 *
	 * @param array $oneBatchPlayers
	 * @return array
	 */
	protected function getIdArr($oneBatchPlayers) {
		$rlt = array();
		if (!empty($oneBatchPlayers)) {
			foreach ($oneBatchPlayers as $player) {
				$rlt[] = $player->playerId;
			}
		}
		return $rlt;
	}

	/**
	 * detail: get cash back rule
	 *
	 * @param int $levelId
	 * @return array
	 */
	public function getCashbackRule($levelId) {
		$this->db->from($this->levelTable)->where($this->levelId, $levelId);

		return $this->runOneRow();
	}

	/**
	 * detail: get game allowed game level rules
	 *
	 * @param int $levelId
	 * @return array
	 */
	public function getAllowedGameLevelRules($levelId) {
		$this->db->from('group_level_cashback_game_description')
			->where('vipsetting_cashbackrule_id', $levelId);
		return $this->runMultipleRow();
	}

	# OG-732 add levelId, levelName to player table
	# FOR PRIVATE USE ONLY

	/**
	 * detail: update player table record
	 *
	 * @param int $playerId player playerId
	 * @param int $levelId player levelId
	 *
	 * @return Boolean
	 */
	private function updatePlayerTable($playerId, $levelId, $db = null) {
        if(empty($db)){
            $db=$this->db;
        }
		$groupLevel = $this->getVipGroupLevelDetails($levelId, $db);
		$groupName = $groupLevel['groupName'];
		$levelName = $groupLevel['vipLevelName'];

		$db->update('player', [
			'levelId' => $levelId,
			'groupName' => $groupName,
			'levelName' => $levelName,
		], [
			'playerId' => $playerId,
		]);

		if ($db->_error_message()) {
			return false;
		}

		return true;
	}

	/**
	 * @param int $levelId
	 * @param string $bonusPercentage
	 * @param string $maxBonus
	 *
	 * @return array
	 */
	public function isChangedRule($levelId, $bonusPercentage, $maxBonus) {}

	/**
	 * detail: update VIP group bonus rules
	 *
	 * @param array $data
	 * @return array
	 */
	public function editVipGroupBonusRule($data, $db = null) {
        if(empty($db)){
            $db=$this->db;
        }

		$flag = $this->editData('vipsettingcashbackrule',
			array('vipsettingcashbackruleId' => $data['vipsettingcashbackruleId']),
            $data,
            $db );

		if ($flag) {
			$this->editData('player',
				array('levelId' => $data['vipsettingcashbackruleId']),
                array('levelName' => $data['vipLevelName']),
                $db );
		}
	}

	/**
	 * detail: update all vip group bonus per each game
	 *
	 * @param int $levelId
	 * @param string $bonusPercentage
	 * @param string $maxBonus
	 *
	 * @return array
	 */
	public function updateAllVipGroupBonusPerGame($levelId, $bonusPercentage, $maxBonus) {}

	/**
	 * detail: update VIP group bonus per game
	 *
	 * @parama array $data
	 * @return array
	 */
	public function editVipGroupBonusPerGame($data) {}

	/**
	 * detail: update VIP group
	 *
	 * @param array $data
	 * @param int $vipsettingId
     * @param CI_DB_driver $db
	 *
	 * @return array
	 */
    public function editVIPGroup($data, $vipsettingId, $db = null) {
        if(empty($db)){
            $db=$this->db;
        }
		$flag = $this->editData('vipsetting'
                            , array('vipsettingId' => $vipsettingId)
                            , $data
                            , $db
                        );

		if ($flag && isset($data['groupName'])) {
			/// move to controllers
			// $this->triggerGenerateCommandEvent('batch_sync_group_name_in_player', [$vipSettingId, '_replace_to_queue_token_'], $is_blocked);
		}
		return $flag;
	} // EOF editVIPGroup

	/**
	 * detail: add cashback allowed game
	 *
	 * @param array $data
	 * @return Boolean
	 */
	public function addCashbackAllowedGames($data) {
		// 	return $this->insertData('vipsetting_cashback_game', $data);
	}

	/**
	 * detail: remove agaency game rolling
	 *
	 * @param int $agentId
	 * @return Boolean
	 */
	public function delete_agency_game_rolling_comm($agent_id) {
		$this->db->where('agent_id', $agent_id);
		$this->db->delete('agency_game_rolling_comm');
	}

	/**
	 * detail: add agency game rolling com by batch
	 *
	 * @param int $agent_id
	 * @param array $gamesAptList
	 *
	 * @return Boolean
	 */
	public function batch_add_agency_game_rolling_comm($agent_id, $gamesAptList) {

		$this->delete_agency_game_rolling_comm($agent_id);
		$cnt = 0;
		$data = array();
		$this->utils->debug_log('gamesAptList count', count($gamesAptList));
		foreach ($gamesAptList as $gameDescriptionId) {

			$data[] = array(
				'agent_id' => $agent_id,
				'game_description_id' => $gameDescriptionId['id'],
				'game_platform_percentage' => $gameDescriptionId['game_platform_number'],
				'game_type_percentage' => $gameDescriptionId['game_type_number'],
				'game_desc_percentage' => $gameDescriptionId['game_desc_number'],
			);
			if ($cnt >= 500) {
				//insert and clean
				$this->db->insert_batch($this->table_agency_game_rolling_comm, $data);
				$data = array();
				$cnt = 0;
			}
			$cnt++;
		}
		if (!empty($data)) {
			$this->db->insert_batch($this->table_agency_game_rolling_comm, $data);
		}
	}

	/**
	 * detail: set paid of a certain cashback
	 *
	 * @param int $id
	 * @return int
	 */
	public function setPaidFlag($id, $paid_amount) {
		//set paid_flag to true
		$this->db->where(['id' => $id])->update('total_cashback_player_game_daily', array(
			'paid_flag' => self::DB_TRUE,
			'paid_date' => $this->utils->getNowForMysql(),
			'paid_amount' => $paid_amount,
		));
		return $this->db->affected_rows() > 0;
	}

	/**
	 * @param int $palyerId
	 * @param string $totalDate
	 * @param string $paid_amount
	 *
	 * @return int
	 */
	public function setPaidFlagByPlayerAndDate($playerId, $totalDate, $paid_amount, $withdraw_condition_amount) {
		$this->db->where('player_id', $playerId)->where('total_date', $totalDate)
			->update('total_cashback_player_game_daily', array(
				'paid_flag' => self::DB_TRUE,
				'paid_date' => $this->utils->getNowForMysql(),
				'paid_amount' => $paid_amount,
				'withdraw_condition_amount' => $withdraw_condition_amount,
			));
		return $this->db->affected_rows() > 0;
	}

	/**
	 * detail: get max bonus for a certain player id
	 *
	 * @param int $playerId
	 * @return string
	 */
	public function getMaxbonusByPlayerId($playerId) {
		$this->db->select('cashback_maxbonus')->from('vipsettingcashbackrule')
			->join('player', 'vipsettingcashbackrule.vipsettingcashbackruleId=player.levelId')
			->where('player.playerId', $playerId);

		return $this->runOneRowOneField('cashback_maxbonus');
	}

	/**
	 * get from vip level
	 * @param  int $playerId
	 * @param  double $commonMaxBonus max bonus from common cashback rules
	 * @return double daily max bonus
	 */
	public function getDailyMaxbonusByPlayerId($playerId, $commonMaxBonus) {
		$this->db->select('cashback_daily_maxbonus')->from('vipsettingcashbackrule')
			->join('player', 'vipsettingcashbackrule.vipsettingcashbackruleId=player.levelId')
			->where('player.playerId', $playerId);

		$dailyMaxBonus= $this->runOneRowOneField('cashback_daily_maxbonus');

		if (empty($dailyMaxBonus)) {
			//try get it from common rules
			$dailyMaxBonus=$commonMaxBonus;
		}

		return $dailyMaxBonus;
	}

	public function processMaxBonus($playerId, $currentAmount, $commonMaxBonus, $theDate = null) {
		$amount=$currentAmount;

		$dailyMaxBonus = $this->getDailyMaxbonusByPlayerId($playerId, $commonMaxBonus);

		if (!empty($dailyMaxBonus)) {
			$this->utils->debug_log('check daily max bons playerId', $playerId, 'amount', $amount, 'dailyMaxBonus', $dailyMaxBonus);
			if($amount > $dailyMaxBonus){
				//minus
				$amount=$this->utils->roundCurrency($dailyMaxBonus);
			}
		}

		$totalMaxBonus = $this->getMaxbonusByPlayerId($playerId);

		if (!empty($totalMaxBonus)) {
			//sum cashback from transactions
			$this->load->model('transactions');
			$_periodFrom = null;
			$_periodTo = null;
			if($theDate !== null){
				$theDateDT = new DateTime($theDate);
				$_periodFrom = $theDateDT->format('Y-m-d 00:00:00');
				$_periodTo = $theDateDT->format('Y-m-d 23:59:59');
			}
			$cashback = $this->transactions->sumCashback($playerId, $_periodFrom, $_periodTo);
			$this->utils->debug_log('check max bonus playerId', $playerId, 'cashback', $cashback, 'amount', $amount, 'totalMaxBonus', $totalMaxBonus);
			if($cashback + $amount > $totalMaxBonus){

				//minus
				$amount=$this->utils->roundCurrency($totalMaxBonus-$cashback);
			}
		}

		return $amount;
	}

	/**
	 * detail: check if the cashback is already paid
	 *
	 * @param int $playerId
	 * @param string $totalDate
	 *
	 * @return Boolean
	 */
	public function isPaidCashback($playerId, $totalDate) {
		$this->db->where('player_id', $playerId)->where('total_date', $totalDate)
			->where('paid_flag', self::DB_TRUE)->from('total_cashback_player_game_daily');

		return $this->runExistsResult();
	}

	/**
	 * only from config as default value
	 * @return int
	 */
	public function getMiniCashbackAmount() {
		return $this->utils->getConfig('min_cashback_amount');
	}

	protected function payRows($rows, $min_cashback_amount, $withdraw_condition_bet_times, $superadmin, $dry_run,
			&$lockFailedRows=[], $debug_mode=false){
		$this->load->model(array('player_model','transactions'));
		$commonSettings=(array) $this->getCashbackSettings();
		$commonMaxBonus=$commonSettings['max_cashback_amount'];

		foreach ($rows as $row) {
			$message = null;
			$playerId=$row->player_id;
			//player id
			// $allFinished=$this->withdraw_condition->isAllFinishedPlayerPromotion($row->player_id);

			$controller = $this;
			$rlt = $this->lockAndTransForPlayerBalance($playerId, function()
				use ($controller, &$message, $playerId, $commonMaxBonus, $row, $debug_mode, $dry_run,
					$withdraw_condition_bet_times, $min_cashback_amount, $superadmin) {
				$success=true;
				//it maybe update wallet, that's why put it inside lock
				$allFinished = $this->withdraw_condition->autoCheckWithdrawConditionAndMoveBigWallet($playerId, $message,
					null, true, false);

				$this->utils->debug_log('check withdraw condition on pay cashback allFinished',
					$allFinished, $message, $row->total_date, $row->player_id, $row->amount);

				if ($allFinished) {

					$old_amount=$row->amount;
					//try fix with max bonus
					$row->amount=$this->processMaxBonus($row->player_id, $row->amount, $commonMaxBonus, $row->total_date);
                $row->withdraw_condition_amount = $this->utils->roundCurrencyForShow($row->amount * $withdraw_condition_bet_times);
					$this->utils->debug_log('processMaxBonus', $old_amount, $row->amount, 'row->total_date:', $row->total_date);

					// $this->lessThanMaxbonus($row->player_id, $row->amount);
					if ($row->amount >= $min_cashback_amount && $row->amount>0){
						// && $this->lessThanDailyMaxbonus($row->player_id, $row->amount, $commonMaxBonus) && $this->lessThanMaxbonus($row->player_id, $row->amount, $commonMaxBonus)) {

						if ($this->utils->isEnabledFeature('refresh_player_balance_before_pay_cashback')) {
							$this->utils->resetBalance($row->player_id);
						}

						$this->utils->debug_log('try pay cashback', $row->total_date, $row->player_id, $row->amount);
						//check paid flag again
						if (!$controller->isPaidCashback($row->player_id, $row->total_date)) {
							if ($debug_mode) {
								//print log and return
								$controller->utils->debug_log('pay cashback debug mode, locked player:' . $row->player_id . ' amount:' . $row->amount, 'date', $row->total_date);
								return true;
							}

							$refBy = ''; // for trace the referenced.
							$enforce_cashback_target = $this->config->item('enforce_cashback_target');
							if( empty($enforce_cashback_target) ){
								$cashback_target = $row->cashback_target;
								$refBy = 'total_cashback_player_game_daily.cashback_target'; // add/update while add/edit the group level, then write the field, 'total_cashback_player_game_daily.cashback_target' while calc the cashback.
							}else if($enforce_cashback_target == Group_level::CASHBACK_TARGET_PLAYER) {
								/// ref. to the config, enforce_cashback_target
								$cashback_target = Group_level::CASHBACK_TARGET_PLAYER;
								$refBy = 'The enforce_cashback_target in config';
							}else if($enforce_cashback_target == Group_level::CASHBACK_TARGET_AFFILIATE) {
								/// ref. to the config, enforce_cashback_target
								$cashback_target = Group_level::CASHBACK_TARGET_AFFILIATE;
								$refBy = 'The enforce_cashback_target in config';
							}
							$this->utils->debug_log('cashback_target:', $cashback_target, 'refBy:', $refBy);

							if($cashback_target  == self::CASHBACK_TARGET_PLAYER){
								$transaction_type = Transactions::AUTO_ADD_CASHBACK_TO_BALANCE;
								$tranId = $controller->transactions->insertAutoCashbackTransaction($row->player_id, $row->amount, $superadmin->userId, $row->total_date, $transaction_type);
							}else if($cashback_target  == self::CASHBACK_TARGET_AFFILIATE){
								$transaction_type = Transactions::AUTO_ADD_CASHBACK_AFFILIATE; // AUTO_ADD_CASHBACK_TO_BALANCE_FROM_DOWNLINE;

								// get the player
								$player = $this->player_model->getPlayerById($row->player_id);

								$cashback_amount = $row->amount;
								$reason = sprintf(Transactions::AUTO_CASHBACK_AFFILIATE_NOTE_FORMAT, $cashback_amount, $player->affiliateId, $row->player_id, $player->username, $row->total_date);
								if( !empty($player->affiliateId) ){
									$affId = $player->affiliateId;
									$amount = $row->amount;
									$adminUserId = Transactions::ADMIN;
									$flag = Transactions::PROGRAM;
									$date = null; // now
									$islocked = true;
									$tranId = $controller->transactions->depositToAff($affId, $amount, $reason, $adminUserId, $flag, $date, $transaction_type, $islocked);
								}else{
									$tranId = 0;
									$this->utils->debug_log('ignore cashback by affiliate is empty. total_date:', $row->total_date, 'player_id:', $row->player_id, 'amount:', $row->amount, 'affiliateId:', $player->affiliateId);
								}
							}

							if ($tranId) {
								//update flag
								$controller->setPaidFlagByPlayerAndDate($row->player_id, $row->total_date, $row->amount, $row->withdraw_condition_amount);
								//add withdraw condition
								if ($row->withdraw_condition_amount > 0) {
									//write to withdraw condition
									$controller->withdraw_condition->createWithdrawConditionForCashback($tranId,
										$row->withdraw_condition_amount, $withdraw_condition_bet_times, $row->player_id, $row->amount);
								}
							}else{
								$success=false;
							}

							/// override for empty player.affiliateId
							if($cashback_target == self::CASHBACK_TARGET_AFFILIATE){
								$success = true;
							}
						} else {
							$controller->utils->error_log('already paid player id', $row->player_id, 'total date', $row->total_date);
						}
					} else {
						$this->utils->debug_log('ignore cashback', $row->total_date, $row->player_id, $row->amount, 'mini', $min_cashback_amount);
					}

				} else {
					$this->utils->debug_log('allFinished is false ignore', $allFinished, 'player id', $row->player_id);
				}
				//trans result
				return $success;
			}); // EOF $rlt = $this->lockAndTransForPlayerBalance(($playerId, function()...

			if (!$rlt) {
				$lockFailedRows[]=$row;
				$this->utils->error_log('pay cashback failed, transaction failed, player id', $row->player_id, 'amount', $row->amount, 'date', $row->total_date);
			}

		} //foreach ($rows as $row) {

	}

	/**
	 * detail: pay cashback
	 *
	 * @param string $date
	 * @param int $playerId
	 *
	 * @return array
	 */
	public function payCashback($date, $playerId = null, $debug_mode = false) {
		$result = true;

		$this->utils->debug_log('pay start', $date, $playerId, 'debug_mode', $debug_mode);
		$this->load->model(array('transactions', 'withdraw_condition'));

		$cashbackSettings = $this->getCashbackSettings();
		$withdraw_condition_bet_times = isset($cashbackSettings->withdraw_condition) ? $cashbackSettings->withdraw_condition : 0;
		$superadmin = $this->users->getSuperAdmin();

		// OGP-17697: skip players that are disabled of cashbacks
		$this->db->select('sum(T.amount) as amount, sum(T.withdraw_condition_amount) as withdraw_condition_amount, T.total_date, T.player_id, T.cashback_target, T.vip_level_info', false)
// <<<<<<< HEAD
// 		$this->db->select('sum(T.amount) as amount, sum(T.withdraw_condition_amount) as withdraw_condition_amount, T.total_date, T.player_id, T.cashback_target', false)
// =======
// 		$this->db->select('sum(T.amount) as amount, sum(T.withdraw_condition_amount) as withdraw_condition_amount, T.total_date, T.player_id, T.vip_level_info', false)
// >>>>>>> live_stable_prod-OGP-23329-20210830
			->from('total_cashback_player_game_daily AS T')
			->join('player AS P', 'T.player_id = P.playerId')
			->where('T.total_date', $date)
			->where('T.paid_flag', self::DB_FALSE)
			->where('P.disabled_cashback !=', self::DB_TRUE)
		;

		if (!empty($playerId)) {
			$this->db->where('T.player_id', $playerId);
		}
		$this->db->group_by('T.player_id, T.total_date');

		$rows = $this->runMultipleRow();

		$this->utils->debug_log('pay cashback total', count($rows));
		$min_cashback_amount = $cashbackSettings->min_cashback_amount; //$this->getMiniCashbackAmount();
		$lockFailedRows=[];
		$dry_run = false;
		if (!empty($rows)) {

			$this->payRows($rows, $min_cashback_amount, $withdraw_condition_bet_times, $superadmin, $dry_run, $lockFailedRows, $debug_mode);

		} else {
			$this->utils->debug_log('cashback is empty');
		}

		while (!empty($lockFailedRows)) {

			$this->utils->debug_log('try pay cashback again', $lockFailedRows);
			sleep(2);
			$lockFailedAgainRows=[];
			$this->payRows($lockFailedRows, $min_cashback_amount,$withdraw_condition_bet_times, $superadmin, $dry_run, $lockFailedAgainRows);

			$this->utils->debug_log('try pay cashback again again', $lockFailedAgainRows);

			$lockFailedRows=$lockFailedAgainRows;

		}

		return $result;
	}

	/**
	 * detail: get bet of a certain player by date range
	 *
	 * @param string $startDateTime
	 * @param string $endDateTime
	 *
	 * @return array
	 */
	public function getPlayerBetThruDate($startDateTime, $endDateTime) {
		$this->load->model(array('game_logs'));

		$sql = <<<EOD
SELECT tpgh.player_id, sum(tpgh.bet_amount) as betting_total, tpgh.game_description_id, tpgh.game_platform_id
  FROM game_logs as tpgh
  join game_description as gd on tpgh.game_description_id=gd.id and gd.no_cash_back!=1
where date_format(tpgh.end_at, '%Y-%m-%d%H')>=? and date_format(tpgh.end_at, '%Y-%m-%d%H')<=?
and flag=?
group by tpgh.player_id, tpgh.game_description_id, tpgh.game_platform_id
EOD;
		$qry = $this->db->query($sql, array($startDateTime, $endDateTime, Game_logs::FLAG_GAME));
		return $this->getMultipleRow($qry);
	}

	/**
	 * detail: check if the certain game level is allowed
	 *
	 * @param int $levelId
	 * @param int $gameDescriptionId
	 * @return array
	 */
	public function isAllowedGameInLevel($levelId, $gameDescriptionId) {
		//empty means every game
		$result = empty($gameDescriptionId);
		if (!$result) {

			$this->db->from('group_level_cashback_game_description')->where('vipsetting_cashbackrule_id', $levelId)
				->where('game_description_id', $gameDescriptionId);

			$result = $this->runExistsResult();
		}
		return $result;
	}

	/**
	 *
	 * priority: game description > game type > game platform > level
	 *
	 * @param  int $player_id           [description]
	 * @param  int $levelId             [description]
	 * @param  int $game_platform_id    [description]
	 * @param  int $game_type_id        [description]
	 * @param  int $game_description_id [description]
	 * @param  int $extra_info          [description]
	 * @return object      cashback_percentage, cashback_maxbonus, id, level_id
	 */
	public function getPlayerRateFromLevel($player_id, $levelId, $game_platform_id, $game_type_id, $game_description_id, $extra_info = null) {
		$row = null;

		if(isset($extra_info['levelCashbackMap']) && !empty($extra_info['levelCashbackMap'])){
            $row = $this->getCashbackRuleFromLevelCashbackMap(@$extra_info['levelCashbackMap'], $levelId, $game_platform_id, $game_type_id, $game_description_id, FALSE);
        }

		$this->utils->debug_log('player id:' . $player_id . ' level:' . $levelId . ' row', $row);

		if (empty($row)) {
			//if no any vip level settings, try common settings
			$commonRules = [];
			$playerSumBetAmount = 0;

			if (!empty($extra_info)) {
				if (isset($extra_info['commonRules'])) {
					$commonRules = $extra_info['commonRules'];
				}
				if (isset($extra_info['mapPlayerBet'])) {
					$mapPlayerBet = $extra_info['mapPlayerBet'];
					if (!empty($mapPlayerBet)) {
						if (isset($mapPlayerBet[$player_id])) {
							$playerSumBetAmount = $mapPlayerBet[$player_id];
						}
					}
				}
			}

			if (!empty($commonRules) && !empty($playerSumBetAmount)) {
				//search on common rules
				$row = $this->getPercentageByCommonRules($player_id, $game_description_id, $playerSumBetAmount, $commonRules);
				$this->utils->debug_log('use cashback common rules', $row, $player_id, 'game descripton:'.$game_description_id, $playerSumBetAmount);
			}
		}

		return $row;
	}

	public function getCashbackRuleFromLevelCashbackMap($levelCashbackMap, $levelId, $game_platform_id, $game_type_id, $game_description_id, $allowed_empty_percentage = FALSE){
	    $row = null;

        //priority: game description > game type > game platform > level
        if (!isset($levelCashbackMap[$levelId])) {
            return $row;
        }

        //only available on game description
        $available_on_level = isset($levelCashbackMap[$levelId]['game_description'][$game_description_id]);

        if (!$available_on_level) {
            $this->utils->debug_log('level:' . $levelId . ' not available', $game_platform_id, $game_type_id, $game_description_id);
            return $row;
        }

        $row = (object) ['cashback_percentage' => $levelCashbackMap[$levelId]['cashback_percentage'],
            'cashback_maxbonus' => $levelCashbackMap[$levelId]['cashback_maxbonus'],
            'id' => $levelId, 'level_id' => $levelId];

        if (isset($levelCashbackMap[$levelId]['game_platform'][$game_platform_id])) {
            $row->cashback_percentage = ($levelCashbackMap[$levelId]['game_platform'][$game_platform_id] > 0) ? $levelCashbackMap[$levelId]['game_platform'][$game_platform_id] : $row->cashback_percentage;
        }

        if (isset($levelCashbackMap[$levelId]['game_type'][$game_type_id])) {
            $row->cashback_percentage = ($levelCashbackMap[$levelId]['game_type'][$game_type_id] > 0) ? $levelCashbackMap[$levelId]['game_type'][$game_type_id] : $row->cashback_percentage;
        }

        if (isset($levelCashbackMap[$levelId]['game_description'][$game_description_id])) {
            $row->cashback_percentage = ($levelCashbackMap[$levelId]['game_description'][$game_description_id] > 0) ? $levelCashbackMap[$levelId]['game_description'][$game_description_id] : $row->cashback_percentage;
        }

        if($allowed_empty_percentage){
            return $row;
        }else{
            return ($row->cashback_percentage > 0) ? $row : NULL;
        }
    }

	/**
	 * detail: betting amount from total_player_game_day cahsback rate from player_level_history to total_cashback_player_game_daily
	 *
     * @deprecated
     *
	 * @param string $date
	 * @param int $playerId
	 *
	 * @return double
	 */
	public function totalCashbackDaily($date, $playerId = null) {
		$startHour = $this->config->item('cashback_start_hour');
		$endHour = $this->config->item('cashback_end_hour');

		return $this->totalCashback($date, $startHour, $endHour, $playerId);
	}

	/**
	 * detail: daily total cashback using setting
	 *
     * @deprecated
     * @see Player_cashback_library::calculateDailyTotalCashbackBySettings
     *
	 * @param object $cashBackSettings
	 * @param string $date
	 * @param int $playerId
	 *
	 * @return double
	 */
	public function totalCashbackDailyBySettings($cashBackSettings, $date, $playerId = null) {
		$startHour = $cashBackSettings->fromHour;
		$endHour = $cashBackSettings->toHour;
		$withdraw_condition_bet_times = isset($cashBackSettings->withdraw_condition) ? $cashBackSettings->withdraw_condition : 0;

		return $this->totalCashback($date, $startHour, $endHour, $playerId, $withdraw_condition_bet_times);
	}

    public function totalCashbackDailyFriendReferralBySettings($cashBackSettings, $date, $playerId = null) {
        $startHour = $cashBackSettings->fromHour;
        $endHour = $cashBackSettings->toHour;
        $withdraw_condition_bet_times = isset($cashBackSettings->withdraw_condition) ? $cashBackSettings->withdraw_condition : 0;
        $start_date = null;
        $end_date = null;
        $result = false;
        return $this->totalCashbackFriendReferral($date, $startHour, $endHour, $playerId, $withdraw_condition_bet_times, $result, $start_date, $end_date, self::FRIEND_REFERRAL_CASHBACK, false);
    }

	function getDayCashbackSetting($cashbackWeeklySetting) {
		$day = '';
		if ($cashbackWeeklySetting == 1) {
			$day = 'Monday';
		} elseif ($cashbackWeeklySetting == 2) {
			$day = 'Tuesday';
		} elseif ($cashbackWeeklySetting == 3) {
			$day = 'Wednesday';
		} elseif ($cashbackWeeklySetting == 4) {
			$day = 'Thursday';
		} elseif ($cashbackWeeklySetting == 5) {
			$day = 'Friday';
		} elseif ($cashbackWeeklySetting == 6) {
			$day = 'Saturday';
		} elseif ($cashbackWeeklySetting == 7) {
			$day = 'Sunday';
		}
		return $day;
	}

    /**
     * @deprecated
     * @see Player_cashback_library::calculateWeeklyTotalCashbackBySettings()
     *
     * @param      $cashBackSettings
     * @param      $date
     * @param null $playerId
     *
     * @return bool|int|void
     */
	public function totalCashbackPeriodBySettings($cashBackSettings, $date, $playerId = null) {

		if(!$this->utils->isEnabledFeature('enabled_weekly_cashback')){
			return;
		}

		$startHour = $cashBackSettings->fromHour;
		$endHour = $cashBackSettings->toHour;
		$withdraw_condition_bet_times = isset($cashBackSettings->withdraw_condition) ? $cashBackSettings->withdraw_condition : 0;
		$weekly = $cashBackSettings->weekly;

		$startDate = date("Y-m-d", strtotime("last week monday"));
		$endDate = date("Y-m-d", strtotime("last week sunday"));
		$result = false;

		// if found today is the day in cashback setting then calculate last week cash back
		$cashback_day = $this->getDayCashbackSetting($weekly);
		$current_day = date('l', strtotime($date));

		if ($cashback_day == $current_day) {
			return $this->totalCashback($date, $startHour, $endHour, $playerId, $withdraw_condition_bet_times, $result, $startDate, $endDate);
		} else {
			return false;
		}
	}

	/**
	 * manually calc cashback
     *
     * @deprecated
	 * @param  string $startDate [description]
	 * @param  string $endDate   [description]
	 * @param  int $playerId  null or id
	 * @return bool
	 */
	public function totalCashbackManually($date, $playerId = null) {

		$cashBackSettings = $this->getCashbackSettings();

		$startHour = $cashBackSettings->fromHour;
		$endHour = $cashBackSettings->toHour;
		$withdraw_condition_bet_times = isset($cashBackSettings->withdraw_condition) ? $cashBackSettings->withdraw_condition : 0;

		//one by one
		//from start date to end date
		//$success = false;
		return $this->totalCashback($date, $startHour, $endHour, $playerId, $withdraw_condition_bet_times, $success);
	}

	/**
	 * detail: get daily cashback id
	 *
	 * @param int $player_id
	 * @param int $game_description_id
	 * @param string $total_date
	 *
	 * @return int
	 */
	public function getCashbackDailyId($player_id, $game_description_id, $total_date, $cashback_type) {
		//player id, game description id, total date
		$this->db->select('id')->from('total_cashback_player_game_daily')
			->where('total_date', $total_date)
			->where('player_id', $player_id)
			->where('game_description_id', $game_description_id);

		if ($cashback_type == self::FRIEND_REFERRAL_CASHBACK) {
			$this->db->where('cashback_type', $cashback_type);
		}else if ($cashback_type == self::MANUALLY_ADD_CASHBACK) {
			$this->db->where('cashback_type', $cashback_type);
		}

		return $this->runOneRowOneField('id');
	}

    public function getCashbackDailayIdFromUniqueId($unique_id) {
        //player id, game description id, total date
        $this->db->select('id')
            ->from('total_cashback_player_game_daily')
            ->where('uniqueid', $unique_id);

        return $this->runOneRowOneField('id');
    }

	/**
	 * detail: get cashback id
	 *
	 * @param int $player_id
	 * @param int $game_description_id
	 * @param string $time_start
	 * @param string $time_end
	 *
	 * @return int
	 */
	public function getCashbackId($player_id, $game_description_id, $time_start, $time_end) {
		//player id, game description id, total date
		$this->db->select('id')
			->from('total_cashback_player_game')
			->where('time_start', $time_start)
			->where('time_end', $time_end)
			->where('player_id', $player_id)
			->where('game_description_id', $game_description_id);

		return $this->runOneRowOneField('id');
	}

	/**
	 * detail: sync daily cashback
	 *
	 * @param int $player_id The F.K. to player.playerId
	 * @param int $game_platform_id The F.K. to external_system.id
	 * @param int $game_description_id The F.K. to game_description.id
	 * @param string $total_date The date of the cashback.
	 * @param double $cashback_amount The cashback amount
	 * @param int $history_id Reference place.
	 * @param int $game_type_id The F.K. to game_type.id
	 * @param int $level_id The level of the player
	 * @param string $cashback_percentage The rate of betting for cashback.
	 * @param double $bet_amount The betting amount
	 * @param double $withdraw_condition_amount The withdraw condition amount after paid cashback.
	 * @param double $max_bonus The bonus max limit of the setting. The max limit of the bonus in the settings.
	 * @param double $original_bet_amount
	 * @param double $cashback_type The cashback types, self::NORMAL_CASHBACK or self::FRIEND_REFERRAL_CASHBACK
	 * @return integer $invited_player_id The invited player, F.K. to player.playerId.
	 * @return string $uniqueid The combo uniqueid contains total_date, cashback_type, player_id, game_description_id, is_parlay.
	 * @return mixed $applied_info The original data.
	 * @return integer $appoint_id The combo data set, appointment id, F.K. to total_cashback_player_game_daily.id
	 *
	 */
	public function syncCashbackDaily(	$player_id // #1
									, $game_platform_id // #2
									, $game_description_id // #3
									, $total_date // #4
									, $cashback_amount // #5
									, $history_id // #6
									, $game_type_id // #7
									, $level_id // #8
									, $cashback_percentage // #9
									, $bet_amount // #10
									, $withdraw_condition_amount = null // #11
									, $max_bonus = 0 // #12
									, $original_bet_amount = 0 // #13
									, $cashback_type = 1 // #14
									, $invited_player_id = null // #15
									, $uniqueid = '' // #16
									, $applied_info = null // #17
									, $appoint_id = 0 // #18
                                    , $recalculate_cashback_table = null // #19
	) {
		$this->load->model(['vipsetting']);

		//get id by unique key: player_id, game_description_id, total_date
        if(!empty($uniqueid)){
            $id = $this->getCashbackDailayIdFromUniqueId($uniqueid);
        }else{
            $id = $this->getCashbackDailyId($player_id, $game_description_id, $total_date, $cashback_type);
        }
        if(empty($uniqueid)){
        	$uniqueid=null;
        }

		/// the player's VIP level data will be cached for report list.
		$theVipGroupLevelDetail = $this->vipsetting->getVipGroupLevelInfoByPlayerId($player_id);

		$cashback_target = 1; // default, to player
		if( isset($theVipGroupLevelDetail['vipsettingcashbackrule']['cashback_target']) ){
			$cashback_target = $theVipGroupLevelDetail['vipsettingcashbackrule']['cashback_target'];
		}

		//set same data for update/insert
		$data = array('player_id' => $player_id,
			'game_description_id' => $game_description_id,
			'game_type_id' => $game_type_id,
			'game_platform_id' => $game_platform_id,
			'total_date' => $total_date,
			'amount' => $this->utils->formatCurrencyNumber($cashback_amount),
			'history_id' => $history_id,
			'level_id' => $level_id,
			'updated_at' => $this->utils->getNowForMysql(),
			'cashback_percentage' => $cashback_percentage,
			'bet_amount' => $this->utils->formatCurrencyNumber($bet_amount),
			'withdraw_condition_amount' => $this->utils->formatCurrencyNumber($withdraw_condition_amount),
			'max_bonus' => $max_bonus,
			'original_bet_amount' => $this->utils->formatCurrencyNumber($original_bet_amount),
			'cashback_type' => $cashback_type,
			'invited_player_id' => $invited_player_id,
			'uniqueid' => $uniqueid,
			'applied_info' => json_encode($applied_info),
			'appoint_id' => $appoint_id,
			'cashback_target' => $cashback_target,
			'vip_level_info' => json_encode($theVipGroupLevelDetail),
		);

		$this->db->set($data);

        if(!empty($recalculate_cashback_table)){
            $this->db->insert($recalculate_cashback_table);
            $affected_id = $this->db->insert_id();
        }else{
            if ($id) {
                //add where condition for update
                $this->db->where('id', $id);
                $this->db->update('total_cashback_player_game_daily');
                $affected_id = $id;
            } else {
                $this->db->insert('total_cashback_player_game_daily');
                $affected_id = $this->db->insert_id();
            }
        }

        return $affected_id;
	} // EOF syncCashbackDaily

	/**
	 * detail: sync cashback
	 *
	 * @param int $player_id
	 * @param int $cashback_request_id
	 * @param int $game_platform_id
	 * @param int $game_description_id
	 * @param string $time_start
	 * @param string $time_end
	 * @param double $cashback_amount
	 * @param int $history_id
	 * @param int $game_type_id
	 * @param int $level_id
	 * @param string $cashback_percentage
	 * @param double $bet_amount
	 * @param double $withdraw_condition_amount
	 * @param int $max_bonus
	 * @param int $original_bet_amount
	 */
	public function syncCashbackByTime($player_id, $cashback_request_id, $game_platform_id, $game_description_id,
		$time_start, $time_end, $cashback_amount, $history_id, $game_type_id, $level_id,
		$cashback_percentage, $bet_amount, $withdraw_condition_amount = null,
		$max_bonus = 0, $original_bet_amount) {

		//get id by unique key: player_id, game_description_id, total_date
		$id = $this->getCashbackId($player_id, $game_description_id, $time_start, $time_end);

		//set same data for update/insert
		$data = array(
			'game_platform_id' => $game_platform_id,
			'game_description_id' => $game_description_id,
			'player_id' => $player_id,
			'cashback_request_id' => $cashback_request_id,
			'amount' => $cashback_amount,
			'time_start' => $time_start,
			'time_end' => $time_end,
			'history_id' => $history_id,
			'game_type_id' => $game_type_id,
			'level_id' => $level_id,
			'cashback_percentage' => $cashback_percentage,
			'bet_amount' => $bet_amount,
			'withdraw_condition_amount' => $withdraw_condition_amount,
			'max_bonus' => $max_bonus,
			'original_bet_amount' => $original_bet_amount,
			'created_at' => $this->utils->getNowForMysql(),
			'updated_at' => $this->utils->getNowForMysql(),
		);

		if ($id) {
			unset($data['created_at']);
			$this->db->set($data);

			//add where condition for update
			$this->db->where('id', $id);
			$this->db->update('total_cashback_player_game');
		} else {
			$this->db->set($data);
			$this->db->insert('total_cashback_player_game');
		}
	}

	/**
	 * detail: sync recalculate cashback
	 *
	 * @param string $total_date
	 *  string $total_date The date of the cashback.
	 */
    public function syncReCalculateCashbackDaily($total_date, $recalculate_uniqueid = null){
        $this->load->library(['authentication']);
        $this->load->model(['users']);

        $this->db->from('recalculate_cashback')->where('total_date', $total_date);
        $row = $this->runOneRowArray();
        $this->utils->debug_log('syncReCalculateCashbackDaily original row', $row);

        if(!empty($row)){
            $admin_id = !empty($this->authentication->getUserId()) ? $this->authentication->getUserId() : Users::SUPER_ADMIN_ID;
            $data = [
                'recalculate_times' => $row['recalculate_times'] + 1,
                'last_recalculate_date_on' => $this->utils->getNowForMysql(),
                'last_recalculate_by' => $admin_id,
                'uniqueid' => $recalculate_uniqueid
            ];

            $this->db->set($data);
            $this->db->where('total_date', $total_date);
            $this->db->update('recalculate_cashback');
            $this->utils->debug_log('syncReCalculateCashbackDaily recalculate', $total_date, $data);
        }else{
            $data['total_date'] = $total_date;
            $this->db->set($data);
            $this->db->insert('recalculate_cashback');
            $this->utils->debug_log('syncReCalculateCashbackDaily new', $total_date, $data);
        }
	}
	/**
	 * After multi-foreach called,
	 * Update the recalculate_times in the table, "recalculate_cashback".
	 * If the calculate at 1st time (recalculate_times=0) ,set to null .
	 *
	 * @param integer $recalculate_times
	 * @param string $total_date
	 * @param string $uniqueid The WHERE condition "IS NULL",please carry in String,"NULL".
	 * @return void
	 */
	public function update_recalculate_times_by_uniqueid($recalculate_times, $total_date, $uniqueid = ''){
		$this->load->library(['authentication']);
        $this->load->model(['users']);

		$use_accumulate_deduction_when_calculate_cashback = $this->utils->getConfig('use_accumulate_deduction_when_calculate_cashback');
		$this->utils->debug_log('OGP-27272.1567.use_accumulate_deduction_when_calculate_cashback', $use_accumulate_deduction_when_calculate_cashback, 'params:',$recalculate_times, $total_date, $uniqueid );
		if( ! $use_accumulate_deduction_when_calculate_cashback ){
			return false;
		}

		$_tablename = 'recalculate_cashback';

		$this->db->from($_tablename)->where('total_date', $total_date);
		if($uniqueid === 'NULL'){
			// its really query NULL data
			$this->db->where('uniqueid IS NULL', null, false);
		}else{
			$this->db->where('uniqueid', $uniqueid);
		}

        $row = $this->runOneRowArray();

		$this->utils->debug_log('OGP-27272.1578.recalc_rows', $row, 'last_query:', $this->db->last_query() );
		if(!empty($row)){
			$rlt = false;
			$admin_id = !empty($this->authentication->getUserId()) ? $this->authentication->getUserId() : Users::SUPER_ADMIN_ID;
			$this->db->set('recalculate_times', $recalculate_times);
			$this->db->set('last_recalculate_date_on', $this->utils->getNowForMysql());
			$this->db->set('last_recalculate_by', $admin_id);

			if($recalculate_times == 0){ // set to NULL in recalculate_times=0
				$this->db->set('last_recalculate_date_on', null);
				$this->db->set('last_recalculate_by', null);
			}

            $this->db->where('total_date', $total_date);
            $rlt = $this->db->update($_tablename);
            $this->utils->debug_log('update_recalculate_times_by_uniqueid recalculate', $total_date, 'last_query:', $this->db->last_query() );
		}else{
			$rlt = true;
		}
		return $rlt;
	}

	public function query_total_date_recalculate_cashback($_currDate){
		$_tablename = 'recalculate_cashback';
		$this->db->from($_tablename)->where('total_date', $_currDate);
		$row = $this->runOneRowArray();
		return $row;
	}

    /**
     *
     */
    public function createRecalculateTable($recalculate_cashback_table = null, $recalculate_deducted_process_table = null){
        $exist_cashback_table = $this->utils->table_really_exists($recalculate_cashback_table);
        $exist_deducted_process_table = $this->utils->table_really_exists($recalculate_deducted_process_table);

        if(!$exist_cashback_table){
            $this->db->query("CREATE TABLE " . $recalculate_cashback_table . " LIKE total_cashback_player_game_daily");
            $this->utils->debug_log('[CREATE TABLE]', $recalculate_cashback_table);
        }else{
            $this->utils->debug_log('[TABLE ALREADY EXIST]', $recalculate_cashback_table);
        }

        if(!$exist_deducted_process_table){
            $this->db->query("CREATE TABLE " . $recalculate_deducted_process_table . " LIKE withdraw_condition_deducted_process");
            $this->utils->debug_log('[CREATE TABLE]', $recalculate_deducted_process_table);
        }else{
            $this->utils->debug_log('[TABLE ALREADY EXIST]', $recalculate_deducted_process_table);
        }
    }

    public function checkRecalculateCashbackInfo($uniqueId){
        $recalculate_cashback_table = 'total_cashback_player_game_daily_' . $uniqueId;
        $recalculate_deducted_process_table = 'withdraw_condition_deducted_process_' . $uniqueId;

        $this->createRecalculateTable($recalculate_cashback_table, $recalculate_deducted_process_table);

        $this->utils->debug_log('recalculate cashback table', $recalculate_cashback_table,
            'recalculate deducted process table', $recalculate_deducted_process_table);

        return [$recalculate_cashback_table, $recalculate_deducted_process_table];
    }

	/**
	 * check is recalculate cashback on total_date
	 *
	 * @param string $total_date
     * @return array
	 */
    public function isRecalculateCashback($total_date){
        $recalculate = false;
        $uniqueId = null;

        $this->db->from('recalculate_cashback')
                 ->where('total_date', $total_date);

		$recalc_rows = $this->runMultipleRowArray();
        if( ! empty($recalc_rows) ){
            $recalculate = true;
            $uniqueId = uniqId();
        }

        $this->utils->debug_log('Is Recalculate Cashback', $recalculate, 'Recalculate uniqueId', $uniqueId);

        return [$recalculate, $uniqueId];
	}

	/**
	 * detail: increase vip group level
	 *
	 * @param int $vipSettingId
	 * @return void
	 */
	public function increaseVipGroupLevel($vipSettingId, $addTransaction = true, $db = null) {
        if(empty($db)){
            $db=$this->db;
        }
        if($addTransaction){
            $this->startTrans();
        }


		# INCREASE GROUP LEVEL COUNT
		$db->set('groupLevelCount', 'groupLevelCount + 1', false);
		$db->where('vipSettingId', $vipSettingId);
		$db->update('vipsetting');

		# ADD LEVEL
		$vip_group = $this->getVIPGroupDetails($vipSettingId, $db);

        $this->utils->debug_log('1734.last_query', $db->last_query());
        $this->utils->debug_log('1734.vip_group', $vip_group);

		$vipLevel = $vip_group[0]['groupLevelCount'];
		$data = [
			'vipsettingId' => $vipSettingId,
			'minDeposit' => 100 * $vipLevel,
			'maxDeposit' => 1000 * $vipLevel,
			'dailyMaxWithdrawal' => 10000 * $vipLevel,
			'vipLevel' => $vipLevel,
			'vipLevelName' => 'Level Name ' . $vipLevel,
		];

		$enforce_cashback_target = $this->utils->getConfig('enforce_cashback_target');
		if( ! empty($enforce_cashback_target) ){
			$data['cashback_target'] = $enforce_cashback_target;
		}


        $enable_vip_downgrade_switch = $this->utils->getConfig('enable_vip_downgrade_switch');
        if( ! empty($enable_vip_downgrade_switch) ){
            $data['enable_vip_downgrade'] = self::DB_FALSE;
        }

        $insert_id = $this->createVipCashbackGameRule( $data, $db);

        if($addTransaction){
            $this->endTrans();
            $this->succInTrans();
        }


		return $insert_id;
	}

    public function playerIdsInHighestLevelByVipSettingId($vipSettingId){
        $playerIdsInTheLevel = [];
        $highestLevel = $this->getVipGroupHighestLevel($vipSettingId);
        if(!empty($highestLevel)){
            $_vipsettingcashbackruleId = $highestLevel['vipsettingcashbackruleId'];
            $playerIdsInTheLevel = $this->getPlayerIdsByLevelId($_vipsettingcashbackruleId);
        }
        return $playerIdsInTheLevel;
    }
	/**
	 * detail: decrease vip group level
	 *
	 * @param int $vipSettingId
     * @param bool $forceStopWhenPlayerExists
     * @param array &$playerIdsInTheLevel When forceStopWhenPlayerExists = true, this param will assign playerIds of the Level.
	 * @return void
	 */
	public function decreaseVipGroupLevel($vipSettingId, $forceStopWhenPlayerExists = false, &$playerIdsInTheLevel = null) {
        if($forceStopWhenPlayerExists){
            $playerIdsCounter = 0;
            $playerIdsInTheLevel = [];
            $highestLevel = $this->getVipGroupHighestLevel($vipSettingId);
            if(!empty($highestLevel)){
                $_vipsettingcashbackruleId = $highestLevel['vipsettingcashbackruleId'];
                $playerIdsInTheLevel = $this->getPlayerIdsByLevelId($_vipsettingcashbackruleId);
                $playerIdsCounter = count($playerIdsInTheLevel);
            }
            if( !empty($playerIdsCounter) ){
                return false; // forceStopWhenPlayerExists
            }
        }

		$this->startTrans();

		# DECREASE GROUP LEVEL COUNT
		$this->db->set('groupLevelCount', 'groupLevelCount - 1', false);
		$this->db->where('vipSettingId', $vipSettingId);
		$this->db->where('groupLevelCount !=', 1); # VIP GROUP CANNOT HAVE ZERO LEVEL
		$this->db->update('vipsetting');

		if ($this->db->affected_rows()) {
			# DELETE LEVEL
			$this->db->select('vipsettingcashbackruleId');
			$this->db->from('vipsettingcashbackrule');
			$this->db->where('vipSettingId', $vipSettingId);
			$this->db->order_by('vipLevel', 'DESC');
			$this->db->limit(1);
			$row = $this->db->get()->row_array();
			if ($row) {
				$this->db->delete('vipsettingcashbackrule', ['vipsettingcashbackruleId' => $row['vipsettingcashbackruleId']]);
			}
		}

		$this->endTrans();
		return $this->succInTrans();
	}

    public function getVipGroupHighestLevel($vipSettingId, $db=null) {
        if(empty($db)){
            $db=$this->db;
        }
        # DELETE LEVEL
        $db->select('vipsettingcashbackruleId');
        $db->from('vipsettingcashbackrule');
        $db->where('vipSettingId', $vipSettingId);
        $db->order_by('vipLevel', 'DESC');
        $db->limit(1);
        $row = $db->get()->row_array();
        return $row;
    } // EOF getVipGroupHighestLevel

	/**
	 * detail: get vip group level details
	 *
	 * @param int $vipgrouplevelId
	 * @return array
	 */
	public function getVipGroupLevelDetails($vipgrouplevelId, $db=null) {
		$this->load->model(['vipsetting']);
        if(empty($db)){
            $db=$this->db;
        }
		return $this->vipsetting->getVipGroupLevelDetails($vipgrouplevelId, $db);
	}

	/**
	 * detail: get cashback bonus per game
	 *
	 * @param int $vipsettingcashbackruleId
	 * @return array
	 */
	public function getCashbackBonusPerGame($vipsettingcashbackruleId) {}

	/**
	 * detail: remove vip group level
	 *
	 * @param int $vipgrouplevelId
	 * @return Boolean
	 */
	public function deletevipgrouplevel($vipgrouplevelId, $db = null) {
        if(empty($db)) {
            $db = $this->db;
        }
		$db->where('vipSettingId', $vipgrouplevelId);
		$db->delete('vipsettingcashbackrule');

        return $db->affected_rows();
	}

	/**
	 * detail: get vip group rules
	 *
	 * @param int $vipgroupId
	 * @return array or Boolean
	 */
	public function getVIPGroupRules($vipgroupId, $filter_deleted = false) {
		$this->db->select('vipsettingcashbackrule.*,vipsetting.groupName')
            ->from('vipsettingcashbackrule')
			->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId');
		$this->db->where('vipsetting.vipSettingId', $vipgroupId);
        if($filter_deleted){
            $this->db->where('vipsettingcashbackrule.deleted = 0', null, false);
        }


		$query = $this->db->get();
		$cnt = 0;
		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
				if ($row['vipsettingcashbackruleId']) {
					$data[$cnt]['vipsettingcashbackgamerule'] = $this->getVIPGroupGamesRule($row['vipsettingcashbackruleId']);
				}
				$cnt++;
			}

			// var_dump($data);exit();
			return $data;
		}
		return false;
	}

	/**
	 * detail: get vip group game rule
	 *
	 * @param int $vipsettingcashbackruleId
	 * @return array or Boolean
	 */
	public function getVIPGroupGamesRule($vipsettingcashbackruleId) {}

	/**
	 * detail: activate vip group
	 *
	 * @param array $data
	 * @return Boolean
	 */
	public function activateVIPGroup($data) {
		$this->db->where('vipsettingId', $data['vipsettingId']);
		$this->db->update('vipsetting', $data);
	}

	/**
	 * detail: get vip group details
	 *
	 * @param int $vipsettingId
	 * @return array or Boolean
	 */
	public function getVIPGroupDetails($vipsettingId, $db = null) {
        if(empty($db)){
            $db=$this->db;
        }
		$this->load->model(['vipsetting']);
		return $this->vipsetting->getVIPGroupDetails($vipsettingId, $db);
	}

	/**
	 * detail: get vip group levels
	 *
	 * @param int $vipsettingId
	 * @return array or Boolean
	 */
	public function getVIPGroupLevels($vipsettingId) {
		$this->db->from('vipsettingcashbackrule');
		$this->db->where('vipSettingId', $vipsettingId);

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			return $query->result_array();
		}
		return false;
	}

	/**
	 * detail: get vip group details
	 *
	 * @param int $vipsettingId
	 * @return array or Boolean
	 */
	public function getVIPGroupDetail($vipsettingId) {
		$this->db->from('vipsetting');
		$this->db->where('vipsettingId', $vipsettingId);

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			return $query->row_array();
		}
		return false;
	}

	/**
	 * detail: get vip group upgrade details
	 *
	 * @param int $vipsettingId
	 * @return array or Boolean
	 */
	public function getVIPGroupUpgradeDetails($upgradeId) {
		$this->db->from('vip_upgrade_setting');
		$this->db->where('upgrade_id', $upgradeId);

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			return $query->row_array();
		}
		return false;
	}

	/**
	 * detail: remove vip group
	 *
	 * @param int $vipsettingId
	 * @return void
	 */
	public function deleteVIPGroup($vipsettingId) {
		$this->startTrans();

		$default_level_id = $this->utils->getConfig('default_level_id');

		$playerIds = $this->getPlayerIdsByVipSettingId($vipsettingId);
		foreach ($playerIds as $playerId) {
             $_rlt = $this->adjustPlayerLevel($playerId, $default_level_id);

             $is_enable = $this->utils->_getIsEnableWithMethodAndList(__METHOD__, $this->getConfig("adjust_player_level2others_method_list"));
             if($is_enable && $_rlt){
                 $newVipLevelId = $default_level_id;
                 $logsExtraInfo = [];
                 $logsExtraInfo['source_method'] = __METHOD__;
                 $_rlt_mdb = $this->group_level_lib->adjustPlayerLevelWithLogsFromCurrentToOtherMDBWithLock( $playerId // #1
                                                                                                             , $newVipLevelId // #2
                                                                                                             , Users::SUPER_ADMIN_ID // #3
                                                                                                             , 'Player Management' // #4
                                                                                                             , $logsExtraInfo // #5
                                                                                                             , $_rlt_mdb_inner // #6
                                                                                                         );
                 $this->utils->debug_log('OGP-28577.2020045._rlt_mdb:', $_rlt_mdb, '_rlt_mdb_inner:', $_rlt_mdb_inner);
             }
		}

		$this->deleteVIPGroupItem($vipsettingId);

		$vipsettingcashbackruleIds = $this->getVipGroupItemRules($vipsettingId);
		if ($vipsettingcashbackruleIds && is_array($vipsettingcashbackruleIds) && !empty($vipsettingcashbackruleIds)) {
			foreach ($vipsettingcashbackruleIds as $vipsettingcashbackrule) {
				$this->db->delete('vipsettingcashbackrule', array('vipsettingcashbackruleId' => $vipsettingcashbackrule['vipsettingcashbackruleId']));
				// $this->db->delete('vipsettingcashbackbonuspergame', array('vipsettingcashbackruleId' => $vipsettingcashbackrule['vipsettingcashbackruleId']));
				// $this->db->delete('vipsetting_cashback_game', array('vipsetting_cashbackrule_id' => $vipsettingcashbackrule['vipsettingcashbackruleId']));
			}
		}

		$this->endTrans();

		return $this->succInTrans();
	}

	/**
	 * detail: soft delete vip group
	 *
	 * @param int $vipsettingId
	 * @return void
	 */
	public function fakeDeleteVIPGroup($vipsettingId) {
        $this->startTrans();
		$default_level_id = $this->utils->getConfig('default_level_id');

		$playerIds = $this->getPlayerIdsByVipSettingId($vipsettingId);
		foreach ($playerIds as $playerId) {
			$this->adjustPlayerLevel($playerId, $default_level_id);
            $is_enable = $this->utils->_getIsEnableWithMethodAndList(__METHOD__, $this->getConfig("adjust_player_level2others_method_list"));
            if($is_enable){
                $newVipLevelId = $default_level_id;
                $logsExtraInfo = [];
                $logsExtraInfo['source_method'] = __METHOD__;
                $_rlt_mdb = $this->group_level_lib->adjustPlayerLevelWithLogsFromCurrentToOtherMDBWithLock( $playerId // #1
                                                                                                            , $newVipLevelId // #2
                                                                                                            , Users::SUPER_ADMIN_ID // #3
                                                                                                            , 'Player Management' // #4
                                                                                                            , $logsExtraInfo // #5
                                                                                                            , $_rlt_mdb_inner // #6
                                                                                                        );
                $this->utils->debug_log('OGP-28577.2035._rlt_mdb:', $_rlt_mdb, '_rlt_mdb_inner:', $_rlt_mdb_inner);
            }
		}

		$data = array(
			'deleted' => 1,
		);

		$this->db->set('deleted', 1);
		$this->db->set('note', 'groupName', false);
		$this->db->set('groupName', random_string());
		$this->db->where('vipsettingId', $vipsettingId);
		$this->db->update('vipsetting');

		$vipsettingcashbackruleIds = $this->getVipGroupItemRules($vipsettingId);
		foreach ($vipsettingcashbackruleIds as $vipsettingcashbackrule) {
			//update deleted 1 instead to delete
			$this->db->where('vipsettingcashbackruleId', $vipsettingcashbackrule['vipsettingcashbackruleId']);
			$this->db->update('vipsettingcashbackrule', $data);
		}

		$this->endTrans();

		return $this->succInTrans();
	}

	/**
	 * detail: get the vip group rules
	 *
	 * @param int $vipsettingId
	 * @return array
	 */
	public function getVipGroupItemRules($vipsettingId) {
		$this->db->select('vipsettingcashbackruleId')->from('vipsettingcashbackrule');
		$this->db->where('vipSettingId', $vipsettingId);
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

	/**
	 * detail: remove vip group
	 *
	 * @param int $vipsettingId
	 * @return Boolean
	 */
	public function deleteVIPGroupItem($vipsettingId) {
		$this->db->where('vipsettingId', $vipsettingId);
		$this->db->delete('vipsetting');
	}

	/**
	 * detail: update level for a certain player
	 *
	 * @param int $playerGroupId
	 * @return Boolean
	 */
	public function updatePlayerLevel($playerGroupId) {
		$newPlayerLevel = array('playerGroupId' => 1);
		$this->db->where('playerGroupId', $playerGroupId);
		$this->db->update('playerlevel', $newPlayerLevel);
	}

	/**
	 * detail: search vip group lists
	 *
	 * @param array $search
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array
	 */
	public function searchVipGroupList($search, $limit, $offset) {

		if ($limit != null) {
			$limit = "LIMIT " . $limit;
		}

		if ($offset != null && $offset != 'undefined') {
			$offset = "OFFSET " . $offset;
		} else {
			$offset = ' ';
		}

		$query = $this->db->query("SELECT vipsetting.*, admin1.username AS createdBy, admin2.username AS updatedBy
			FROM vipsetting
			LEFT JOIN adminusers AS admin1
			ON admin1.userId = vipsetting.createdBy
			LEFT JOIN adminusers AS admin2
			ON admin2.userId = vipsetting.updatedBy
			WHERE vipsetting.groupName = ?
			$limit
			$offset
		", $search);
		if (!$query->result_array()) {
			return false;
		} else {
			return $query->result_array();
		}
	}

	public function getAllPlayerLevelsForSelect() {
		$this->db->select('vipsettingcashbackrule.vipLevel,
						   vipsettingcashbackrule.vipsettingcashbackruleId,
						   vipsetting.vipSettingId,
						   vipsetting.groupName,
						   vipsettingcashbackrule.vipLevelName', FALSE)
			->from('vipsettingcashbackrule')
			->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId', 'left')
			->where('vipsetting.deleted', 0);
		$this->db->order_by('groupName', 'ASC');
		$this->db->order_by('vipLevelName', 'ASC');
		$query = $this->db->get();
		$rows = $query->result_array();
		foreach ($rows as &$row) {
			$row['groupLevelName'] = lang($row['groupName']) . '|' . lang($row['vipLevelName']);
		}
		return $rows;
	}

	/**
	 * Get All PlayerLevels Dropdown.
	 *
	 * @param string $emptyWording The Wording of empty value,default.
	 * @return array
	 */
	public function getAllPlayerLevelsDropdown($addEmptyWording = true, $emptyWording = '') {
		$this->db->select('vipsettingcashbackrule.vipsettingcashbackruleId,
                           vipsettingcashbackrule.vipLevelName,
						   vipsetting.vipSettingId,
						   vipsetting.groupName')
			->from('vipsettingcashbackrule')
			->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId', 'left');
		$this->db->where('vipsetting.deleted', 0);
		$this->db->order_by('groupName', 'ASC');
		$this->db->order_by('vipLevelName', 'ASC');
		$query = $this->db->get();
		$rows = $query->result_array();
		$query->free_result();
		foreach ($rows as &$row) {
			$row['groupLevelName'] = lang($row['groupName']) . ' - ' . lang($row['vipLevelName']);
		}
		if($emptyWording == ''){
			$emptyWording = lang('player.08');
		}
		$empty = array('' => $emptyWording);
		$list = array_column($rows, 'groupLevelName', 'vipsettingcashbackruleId');

		return $addEmptyWording ? $empty + $list : $list;
	} // EOF getAllPlayerLevelsDropdown

	/**
	 * detail: Will get all player Levels
	 *
	 * @return array
	 */
	public function getAllPlayerLevels() {
		$this->db->select('vipsettingcashbackrule.*,
						   vipsetting.vipSettingId,
						   vipsetting.groupLevelCount,
						   vipsetting.groupName')
			->from('vipsettingcashbackrule')
			->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId', 'left');
		$this->db->where('vipsetting.deleted', 0);
		$this->db->order_by('vipsettingcashbackrule.vipsettingcashbackruleId', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * detail: Will get all player Levels
	 *
	 * @param Boolean $showGameTree
	 * @return array
	 */
	public function getGroupPlayerLevels($showGameTree = true) {
		$groupPlayerLvl = $this->getAllPlayerLevels();
		$playerLvlTree = array();
		foreach ($groupPlayerLvl as $gpl) {
			$groupId = $gpl['vipSettingId'];
			if (!array_key_exists($groupId, $playerLvlTree)) {
				$playerLvlTree[$groupId] = array('groupName' => $gpl['groupName'], 'playerLvlTree' => array());

				$playerLvlTree[$groupId]['playerLvlTree'][] =
				array('playerLevelId' => $gpl['vipsettingcashbackruleId'],
					'playerLevelName' => $gpl['vipLevelName'],
					'playerLevel' => $gpl['vipLevel']);
			} else {
				$playerLvlTree[$groupId]['playerLvlTree'][] =
				array('playerLevelId' => $gpl['vipsettingcashbackruleId'],
					'playerLevelName' => $gpl['vipLevelName'],
					'playerLevel' => $gpl['vipLevel']);
			}
		}

		return $playerLvlTree;
	}

	/**
	 * detail: get selected promo rule
	 *
	 * @param int $promoruleId
	 * @return array
	 */
	public function getSelectedPromoRuleGroup($promoruleId) {
		$this->db->select('vipSettingId')->from('promorulesallowedplayerlevel');
		$this->db->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = promorulesallowedplayerlevel.playerLevel', 'left');
		$this->db->where('promorulesallowedplayerlevel.promoruleId', $promoruleId);
		$this->db->group_by('vipSettingId');
		$query = $this->db->get();
		$rows = $query->result();
		$arr = [];
		if (!empty($rows)) {
			foreach ($rows as $row) {
				$arr[] = $row->vipSettingId;
			}
		}
		return $arr;
	}

	/**
	 * detail: Will get all member group
	 *
	 * @return array
	 */
	public function getAllMemberGroup() {
		$this->db->select('vipsetting.groupName,vipsetting.vipSettingId')
			->from('vipsetting');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * detail: Will get player deposit rule
	 *
	 * @param int $playerId playerlevel playerId
	 * @return array
	 */
	public function getPlayerDepositRule($playerId) {
		$this->db->select('vipsettingcashbackrule.minDeposit,vipsettingcashbackrule.maxDeposit')
			->from('playerlevel')
			->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = playerlevel.playerGroupId', 'left');
		$this->db->where('playerlevel.playerId', $playerId);
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * detail: Will get player withdraw rule
	 *
	 * @param int $playerId playerlevel playerId
	 * @return array
	 */
	public function getPlayerWithdrawRule($playerId) {
		$this->db->select('vipsettingcashbackrule.dailyMaxWithdrawal, vipsettingcashbackrule.max_withdraw_per_transaction, vipsettingcashbackrule.max_withdrawal_non_deposit_player, vipsettingcashbackrule.withdraw_times_limit, vipsettingcashbackrule.min_withdrawal_per_transaction,vipsettingcashbackrule.max_monthly_withdrawal')
			->from('playerlevel')
			->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = playerlevel.playerGroupId', 'left');
		$this->db->where('playerlevel.playerId', $playerId);
		$query = $this->db->get();
        if ($query->num_rows() < 1) {
        	$this->utils->debug_log('getPlayerWithdrawRule SQL', $this->db->last_query());
        }
		return $query->result_array();
	}

	/**
	 * detail: get all levels
	 *
	 * @return array
	 */
	public function getAllLevels() {
		$this->db->from($this->levelTable);
		return $this->runMultipleRow();
	}

	/**
	 * detail: get highest group level
	 *
	 * @return array
	 */
	public function getHighestGroupLevelsId() {

		$sql = "SELECT  MAX(groupLevelCount) AS highest_count  FROM vipsetting";
		$query = $this->db->query($sql);
		$highest_count = $query->row_array()['highest_count'];

		$sql = "SELECT  vipSettingId  FROM vipsetting WHERE groupLevelCount = ? ";
		$query = $this->db->query($sql, array($highest_count));

		return $query->row_array()['vipSettingId'];

	}

	/**
	 * detail: get group levels
	 *
	 * @param int $vipSettingId
     * @param boolean $filter_deleted The vipsettingcashbackrule.deleted field has been Deprecated.
	 * @return array
	 */
	public function getGroupLevels($vipSettingId, $filter_deleted = false, $db = null) {
        if(empty($db)){
            $db=$this->db;
        }
		$sql = "SELECT vipLevel, vipLevelName  FROM vipsettingcashbackrule WHERE vipSettingId = ?";
        if($filter_deleted){
            $sql .= ' AND vipsettingcashbackrule.deleted = 0';
        }
		$query = $db->query($sql, array($vipSettingId));
		return $query->result_array();
	}

	/**
	 * detail: get current group level count
	 *
	 * @param int $vipSettingId
	 * @return array
	 */
	public function getGroupCurrLevelCount($vipSettingId, $db = null) {
        if(empty($db)){
            $db=$this->db;
        }
		$sql = "SELECT groupLevelCount FROM vipsetting WHERE vipSettingId = ?";
		$query = $db->query($sql, array($vipSettingId));
        $row = $query->row_array();
		return empty($row)? 0: $row['groupLevelCount'];

	}

	/**
	 * detail: get VIP group level setting
	 *
	 * @param int vipSettingId
	 * @param string $vipLevel
	 * @return array
	 */
	public function getVipGroupLevelSetting($vipSettingId, $vipLevel) {
		$sql = "SELECT * FROM vipsettingcashbackrule WHERE vipSettingId = ? AND vipLevel = ? ";
		$query = $this->db->query($sql, array($vipSettingId, $vipLevel));
		return $query->row_array();

	}

	/**
	 * detail: get VIP group level setting
	 *
	 * @param int vipSettingId
	 * @param string $vipLevel
	 * @return array
	 */
	public function checkSystemFeatureCashBack() {
        $sQuerySelect = "SELECT enabled FROM system_features WHERE name = ?";
		$query = $this->db->query($sQuerySelect, [self::SYSTEM_FEATURE_CASHBACK]);
		return $query->row_array();
	}

	/**
	 * detail: allow game description to all
	 *
	 * @param int $gameDescId
	 * @return Boolean
	 */
	public function allowGameDescToAll($gameDescId) {

		if($this->utils->getConfig('ignore_auto_add_new_game_to_vip_cashback_during_sync_unknowngame')){
			$this->utils->debug_log('ignore_auto_add_new_game_to_vip_cashback_during_sync_unknowngame enabled, ignore this game', $gameDescId);
			return;
		}

		//add game desc and game type and game platform to group level

		$this->load->model(array('game_type_model', 'game_description_model'));

		$isCheckedCashBackFeature = $this->checkSystemFeatureCashBack();
		if(isset($isCheckedCashBackFeature) && $isCheckedCashBackFeature['enabled'] === '1') {
			return true;
		}

		$autoAddCashback = $this->game_type_model->isGameTypeSetToAutoAddCashback($gameDescId);
		if ($autoAddCashback) {
			$gameDesc = $this->game_description_model->getGameDescription($gameDescId);
			$now=$this->utils->getNowForMysql();
			$this->db->from($this->levelTable);
			$rows = $this->runMultipleRowArray();
			foreach ($rows as $row) {
				$vipsetting_cashbackrule_id=$row[$this->levelId];
				$succ=$this->syncCashbackGamePlatform([
						'vipsetting_cashbackrule_id' => $vipsetting_cashbackrule_id,
						'game_platform_id' => $gameDesc->game_platform_id,
						'percentage' => 0,
						'status' => self::DB_TRUE,
						'updated_at' => $now,
					]);
				if(!$succ){
					$this->utils->error_log('sync cashback game platform failed',
						['vipsetting_cashbackrule_id'=>$vipsetting_cashbackrule_id, 'game_platform_id'=>$gameDesc->game_platform_id]);
					throw new Exception('sync cashback game platform failed');
				}

				$succ=$this->syncCashbackGameType([
					'vipsetting_cashbackrule_id' => $vipsetting_cashbackrule_id,
					'game_type_id' => $gameDesc->game_type_id,
					'percentage' => 0,
					'status' => self::DB_TRUE,
					'updated_at' => $now,
				]);
				if(!$succ){
					$this->utils->error_log('sync cashback game type failed',
						['vipsetting_cashbackrule_id'=>$vipsetting_cashbackrule_id, 'game_type_id'=>$gameDesc->game_type_id]);
					throw new Exception('sync cashback game type failed');
				}

				$succ=$this->syncCashbackGameDescription([
					'vipsetting_cashbackrule_id' => $vipsetting_cashbackrule_id,
					'game_description_id' => $gameDesc->id,
					'percentage' => 0,
					'status' => self::DB_TRUE,
					'updated_at' => $now,
				]);
				if(!$succ){
					$this->utils->error_log('sync cashback game description failed',
						['vipsetting_cashbackrule_id'=>$vipsetting_cashbackrule_id, 'game_description_id'=>$gameDesc->id]);
					throw new Exception('sync cashback game description failed');
				}
			}
		}

		//check all group level
		// $this->db->from($this->levelTable);
		// $rules = $this->runMultipleRow();

		// $ruleIdArr = array();
		// if (!empty($rules)) {
		// 	foreach ($rules as $rule) {
		// 		$ruleIdArr[] = $rule->vipsettingcashbackruleId;
		// 	}
		// }

		// if (!empty($ruleIdArr)) {
		// 	//add to vipsetting_cashback_game
		// 	$this->db->from($this->levelAllowedGameTable)->where('game_description_id', $gameDescId);
		// 	if (!$this->runExistsResult()) {

		// 		$autoAddCashback = $this->game_type_model->isGameTypeSetToAutoAddCashback($gameDescId);
		// 		if($autoAddCashback) {
		// 			$data = array();
		// 			//new game description
		// 			foreach ($ruleIdArr as $ruleId) {
		// 				$data[] = array($this->levelAllowedGameLevelId => $ruleId,
		// 						'game_description_id' => $gameDescId, 'percentage' => 0, 'maxBonus' => 0);
		// 			}
		// 			$rlt = $this->db->insert_batch($this->levelAllowedGameTable, $data);
		// 			$this->utils->debug_log('insert game desc to group level', $rlt);
		// 		}

		// 	} else {
		// 		$this->utils->debug_log('not new game description', $gameDescId);
		// 	}
		// }

		return true;
	}

	/**
	 * detail: get cashback settings
	 *
	 * @return Object
	 */
	public function getCashbackSettings() {
		$this->load->library(['player_cashback_library']);
		$this->load->model(array('operatorglobalsettings'));

		$settingsStr = $this->operatorglobalsettings->getSettingValueWithoutCache(self::CASHBACK_SETTINGS_NAME);

		$cashbackSettings = (object) array();
		if (!empty($settingsStr)) {
			$cashbackSettings = json_decode($settingsStr);
			if (empty($cashbackSettings)) {
				$cashbackSettings = (object) array();
			}
		}

        $cashbackSettings->common_cashback_rules_mode = (isset($cashbackSettings->common_cashback_rules_mode)) ? $cashbackSettings->common_cashback_rules_mode : Player_cashback_library::COMMON_CASHBACK_RULES_MODE_BY_SINGLE;
		if(!$this->utils->isEnabledFeature('enabled_cashback_of_multiple_range')){
            $cashbackSettings->common_cashback_rules_mode = Player_cashback_library::COMMON_CASHBACK_RULES_MODE_BY_SINGLE;
        }
		$cashbackSettings->fromHour = str_pad(intval($cashbackSettings->fromHour), 2, '0', STR_PAD_LEFT);

		$cashbackSettings->toHour = str_pad(intval($cashbackSettings->toHour), 2, '0', STR_PAD_LEFT);
		if(strlen($cashbackSettings->payTimeHour)!=5){
			$arr = explode(':', $cashbackSettings->payTimeHour);
			$hour = str_pad(intval($arr[0]), 2, '0', STR_PAD_LEFT);
			$minute = '00';
			if (count($arr) > 1) {
				$minute = str_pad(intval($arr[1]), 2, '0', STR_PAD_LEFT);
			}
			$cashbackSettings->payTimeHour = $hour . ':' . $minute;
		}

		$cashbackSettings->payLastUpdate = $this->getUpdateCashbackLastTime();

		if(!isset($cashbackSettings->min_cashback_amount)){
			$cashbackSettings->min_cashback_amount=$this->getMiniCashbackAmount();
		}

		return $cashbackSettings;
	}

	/**
	 * detail: update cashback last time
	 *
	 * @param string $dateTimeStr
	 * @return Boolean
	 */
	public function updateCashbackLastTime($dateTimeStr) {
		return $this->operatorglobalsettings->putSetting(self::LAST_UPDATE_CASHBACK_NAME, $dateTimeStr);
	}

	public function getUpdateCashbackLastTime(){
		return $this->operatorglobalsettings->getSettingValueWithoutCache(self::LAST_UPDATE_CASHBACK_NAME);
	}

	/**
	 * detail: get player by vip setting id
	 *
	 * @param int $vipSettingId
	 * @return array
	 */
	public function getPlayerIdsByVipSettingId($vipSettingId) {
		$this->db->distinct();
		$this->db->select('player.playerId');
		$this->db->from('player');
		$this->db->join('playerlevel', 'playerlevel.playerId = player.playerId', 'left');
		$this->db->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId OR vipsettingcashbackrule.vipsettingcashbackruleId = playerlevel.playerGroupId', 'left');
		$this->db->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId');
		$this->db->where('vipsetting.vipSettingId', $vipSettingId);
		$query = $this->db->get();
		return array_column($query->result_array(), 'playerId');
	}

    public function getPlayerIdsByLevelId($vipsettingcashbackruleId, $db=null) {
        if(empty($db)){
            $db=$this->db;
        }
		$db->distinct();
		$db->select('player.playerId');
		$db->from('player');
		$db->join('playerlevel', 'playerlevel.playerId = player.playerId', 'left');
		$db->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId OR vipsettingcashbackrule.vipsettingcashbackruleId = playerlevel.playerGroupId', 'left');
		$db->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId');
		$db->where('vipsettingcashbackrule.vipsettingcashbackruleId', $vipsettingcashbackruleId);
		$query = $db->get();
		return array_column($query->result_array(), 'playerId');
	}

	/**
	 * detail: get Allowed game ids for a cetain player
	 *
	 * @param int $playerId
	 * @return array
	 */
	public function getAllowedGameIdArr($playerId) {
		$levelId = $this->getPlayerLevelId($playerId);
		$rows = $this->getAllowedGameLevelRules($levelId);
		$arr = array();
		if (!empty($rows)) {
			foreach ($rows as $row) {
				$arr[] = $row->game_description_id;
			}
		}
		return $arr;
	}

	public function getAllowedGameIdKV($playerId) {
        $levelId = $this->getPlayerLevelId($playerId);
        $rows = $this->getAllowedGameLevelRules($levelId);
        $arr = array();
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $arr[$row->game_description_id] = $row->game_description_id;
            }
        }
        return $arr;
    }

	/**
	 * detail: get player withdrawal rule
	 *
	 * @param int $playerId
	 * @return array
	 */
	public function getPlayerWithdrawalRule($playerId) {
		$this->db->select('vipsettingcashbackrule.dailyMaxWithdrawal, vipsettingcashbackrule.withdraw_times_limit')
			->from('player')
			->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId', 'left');
		$this->db->where('player.playerId', $playerId);
		$query = $this->db->get();
		if (!$query->row_array()) {
			return false;
		} else {
			return $query->row_array();
		}
	}

	/**
	 * detail: get group level lists
	 *
	 * @return array
	 */
	public function getGroupLevelListKV() {
		$this->db->select('vipsettingcashbackrule.vipsettingcashbackruleId, vipsettingcashbackrule.vipLevelName, vipsetting.groupName')
			->from($this->levelTable)->join('vipsetting', 'vipsetting.vipSettingId=vipsettingcashbackrule.vipSettingId')
			->where('vipsetting.deleted !=', 1)
			->order_by('vipsettingcashbackrule.vipSettingId, vipsettingcashbackrule.vipLevel');
		$rows = $this->runMultipleRow();
		$result = array('' => lang('N/A'));
		if (!empty($rows)) {
			foreach ($rows as $row) {
				$result[$row->vipsettingcashbackruleId] = lang($row->groupName) . ' - ' . lang($row->vipLevelName);
			}
		}

		return $result;
	}

	/**
	 * detail: get group level lists
	 *
	 * @return array
	 */
	public function getGroupLevelList() {
		$result = [];
		$qry = $this->db->get($this->levelTable);
		$rlt = $qry->result_array();
		foreach ($rlt as $row) {
			$result[$row['vipsettingcashbackruleId']] = $row;
		}
		return $result;
	}

	/**
	 * detail: get VIP level id
	 *
	 * @param int $vipSettingId
	 * @param string $vipLevelNumber
	 * @return int
	 */
	public function getVipLevelIdByLevelNumber($vipSettingId, $vipLevelNumber) {
		$this->db->from($this->levelTable)->where('vipSettingId', $vipSettingId)->where('vipLevel', $vipLevelNumber);
		$row = $this->runOneRow();
		$levelId = null;
		if (!empty($row)) {
			$levelId = $row->vipsettingcashbackruleId;
		}
		return $levelId;
	}

	/**
	 * detail: update level up/down by batch
	 *
	 * @param string $fromDatatime
	 * @param string $toDatetime
	 *
	 * @return array
	 */
	public function batchUpDownLevel($fromDatetime, $toDatetime) {
		$this->load->model(array('total_player_game_day', 'player_model', 'transactions'));
        $this->load->library(['group_level_lib']);

		$result = array();

		$this->utils->debug_log('fromDatetime', $fromDatetime, 'toDatetime', $toDatetime);

        if( $this->enable_multi_currencies_totals ){
            $gameLogData = $this->group_level_lib->groupTotalBetsWinsLossGroupByPlayersWithForeachMultipleDBWithoutSuper($fromDatetime, $toDatetime);
        }else{
            $gameLogData = $this->total_player_game_day->groupTotalBetsWinsLossGroupByPlayers($fromDatetime, $toDatetime);
        }

		$allPlayerLevels = $this->getAllPlayerLevels(); // gets all player levels
		$levelMap = array();
		foreach ($allPlayerLevels as $lvl) {
			$levelMap[$lvl['vipsettingcashbackruleId']] = $lvl;
		}

		$this->utils->debug_log($gameLogData, 'all levels', count($levelMap));

		foreach ($gameLogData as $row) {
			$player = $this->player_model->getPlayerById($row['player_id']);
			if (!isset($levelMap[$player->levelId])) {
				//ignore
				$this->utils->error_log('ignore player', $player->playerId, $player->username);
				continue;
			}

			$this->startTrans();

			$playerLevel = $levelMap[$player->levelId];
			$this->utils->debug_log('upgradeAmount', $playerLevel['upgradeAmount'], 'downgradeAmount', $playerLevel['downgradeAmount']);

			if ($playerLevel['upgradeAmount'] > 0) { // No interface found.

				// Check upgrade amount if >= to total betting amount
				if ($row['total_bet'] >= $playerLevel['upgradeAmount'] && $playerLevel['vipLevel'] < $playerLevel['groupLevelCount']) {
					//find by vipLevel
					$newVipLevelId = $this->getVipLevelIdByLevelNumber($playerLevel['vipSettingId'], $playerLevel['vipLevel'] + 1);
					if (!empty($newVipLevelId)) {
						$this->adjustPlayerLevel($row['player_id'], $newVipLevelId);

						$result[] = $this->utils->debug_log('upgraded', $player->username, $playerLevel['vipLevel'], 'to', $newVipLevelId);
					} else {

						$this->utils->error_log('upgrade failed', $player->username, $playerLevel['vipLevel'], 'to', $newVipLevelId);
					}
				} else {
					$this->utils->debug_log('ignore upgrade', $player->username, $row['total_bet'], $playerLevel['upgradeAmount']);
				}
			} else {
				$this->utils->debug_log('ignore level upgrade', $playerLevel['vipsettingcashbackruleId'], $playerLevel['upgradeAmount']);
			}

			if ($playerLevel['downgradeAmount'] > 0) { // No interface found.

				// downgrade by deposit on group level
                $add_manual=false; // as default
                $deposit = $this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers($player->playerId, $fromDatetime, $toDatetime, $add_manual, $this->enable_multi_currencies_totals);

				if ($deposit[0] <= $playerLevel['downgradeAmount'] && $playerLevel['vipLevel'] != '1') {
					$newVipLevelId = $this->getVipLevelIdByLevelNumber($playerLevel['vipSettingId'], $playerLevel['vipLevel'] - 1);
					if (!empty($newVipLevelId)) {
						$this->adjustPlayerLevel($row['player_id'], $newVipLevelId);
						$result[] = $this->utils->debug_log('downgraded', $player->username, $playerLevel['vipLevel'], 'to', $newVipLevelId);
					} else {
						$this->utils->error_log('downgrade failed', $player->username, $playerLevel['vipLevel'], 'to', $newVipLevelId);
					}
				} else {
					$this->utils->debug_log('ignore downgrade', $player->username, $deposit[0], $playerLevel['downgradeAmount']);
				}

				// downgrade by bet amount on group level
				if ($row['total_bet'] <= $playerLevel['downgradeAmount'] && $playerLevel['vipLevel'] != '1') {
					$newVipLevelId = $this->getVipLevelIdByLevelNumber($playerLevel['vipSettingId'], $playerLevel['vipLevel'] - 1);
					if (!empty($newVipLevelId)) {
						$this->adjustPlayerLevel($row['player_id'], $newVipLevelId);

						$result[] = $this->utils->debug_log('downgraded', $player->username, $playerLevel['vipLevel'], 'to', $newVipLevelId);
					} else {

						$this->utils->error_log('downgrade failed', $player->username, $playerLevel['vipLevel'], 'to', $newVipLevelId);
					}
				} else {
					$this->utils->debug_log('ignore downgrade', $player->username, $row['total_bet'], $playerLevel['downgradeAmount']);
				}
			} else {
				$this->utils->debug_log('ignore level downgrade', $playerLevel['vipsettingcashbackruleId'], $playerLevel['downgradeAmount']);
			}

			$succ = $this->endTransWithSucc();
			if (!$succ) {
				$this->utils->error_log('process up/down level failed', $player->username, 'total bet', $row['total_bet'], 'up', $playerLevel['upgradeAmount'], 'down', $playerLevel['downgradeAmount']);
			}
		}

		return $result;
	}

	/**
	 * For new Features of VIP
	 *
	 * param $manual_batch If false means batch, if true means manual.
	 */
	public function batchUpDownLevelUpgrade($playerIds, $manual_batch = false, $check_hourly = false, $time_exec_begin = null, $order_generated_by = null) {
		if($check_hourly === true) {
			$this->utils->debug_log('batchUpDownLevelUpgrade trigger hourly cheack. Start time: ', date('Y-m-d H:i:s'));
		}
		$this->load->model(array('total_player_game_day', 'player_model', 'transactions'));

		$this->initialBasicAmountImported();

		// bplu = batch_player_level_upgrade
		$bplu_options = $this->config->item('batch_player_level_upgrade');
		if( ! empty($bplu_options['BUDLUintervalSec']) ){
			// BUDLU = batchUpDownLevelUpgrade
			$BUDLUintervalSec = $bplu_options['BUDLUintervalSec']; // 60 sec for test
		}

		// gets all player levels
		$allPlayerLevels = $this->getAllPlayerLevels();
		$levelMap = array();
		foreach ($allPlayerLevels as $lvl) {
			$levelMap[$lvl['vipsettingcashbackruleId']] = $lvl;
		}

		$result = array();

		$isBatchUpgrade = false;

		// batch upgrade
		$totalPlayerUpgrade = 0;
		if (is_array($playerIds)) {
			$isBatchUpgrade = true;
			if($manual_batch == 'false'){
				$manual_batch = false;
			}
			if ($manual_batch) {
				$isBatchUpgrade = !$manual_batch;
			}
			$i = 0;
			$totalPlayer = count($playerIds);
			foreach ($playerIds as $player) {
				$playerId = $player->playerId;
				$this->setGradeRecord(['start_time' => date('Y-m-d H:i:s')]);


				// OGP-25082
				$disable_player_multiple_upgrade = $this->utils->isEnabledFeature('disable_player_multiple_upgrade');
				if( ! $disable_player_multiple_upgrade ){
					$this->PLAH['multiple_upgrade_buff'] = []; // clear for this player.
				}


		        $success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, $levelMap, $isBatchUpgrade, $check_hourly, $time_exec_begin, &$result, $order_generated_by){
		            $result = $this->playerLevelAdjust($playerId, $levelMap, $isBatchUpgrade, $check_hourly, $time_exec_begin, $order_generated_by);
		            return $result;
		        });

                if(!$success){
                    $this->utils->error_log('batchUpDownLevelUpgrade lock failed! playerId:',$playerId);
                }

				if(isset($result['is_player_upgrade'])) {
					if ($result['is_player_upgrade']) {
						$totalPlayerUpgrade++;
					}
				}
				$i++;
				if( $totalPlayer > $i ){ // ignore the last one.
					// BUDLU = batchUpDownLevelUpgrade
					if( ! empty($BUDLUintervalSec) ){
						$this->utils->debug_log('will BUDLUintervalSec', $BUDLUintervalSec);
						$this->utils->idleSec($BUDLUintervalSec);
					}
				} // EOF if( $totalPlayer > $i ){...
			}
		} else {
			// manual upgrade
			$playerId = $playerIds;
			$this->setGradeRecord(['start_time' => date('Y-m-d H:i:s')]);

			// OGP-25082
			$disable_player_multiple_upgrade = $this->utils->isEnabledFeature('disable_player_multiple_upgrade');
			if( ! $disable_player_multiple_upgrade ){
				$this->PLAH['multiple_upgrade_buff'] = []; // clear for this player.
			}

	        $success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, $levelMap, $isBatchUpgrade, $check_hourly, $time_exec_begin, &$result, $order_generated_by){
	            $result = $this->playerLevelAdjust($playerId, $levelMap, $isBatchUpgrade, $check_hourly, $time_exec_begin, $order_generated_by);
	            return $result;
	        });

            if(!$success){
                $this->utils->error_log('batchUpDownLevelUpgrade lock failed! playerId:',$playerId);
            }

			if(isset($result['is_player_upgrade'])) {
				if ($result['is_player_upgrade']) {
					$totalPlayerUpgrade++;
				}
			}
		}
		$this->resetBasicAmountAfterImported();
		$this->utils->debug_log('OGP-21051.totalPlayerUpgrade in 2392:', $totalPlayerUpgrade);
		$result['totalPlayerUpgrade'] = $totalPlayerUpgrade;
		return $result;
	} // EOF batchUpDownLevelUpgrade

	public function batchUpDownLevelDowngrade($playerIds, $manual_batch = false, $order_generated_by = null, $time_exec_begin = null) {
		$this->load->model(array('total_player_game_day', 'player_model', 'transactions'));

		$this->initialBasicAmountImported();

		$bpld_options = $this->config->item('batch_player_level_downgrade');
		if( ! empty($bpld_options['BUDLDintervalSec']) ){
			$BUDLDintervalSec = $bpld_options['BUDLDintervalSec']; // 60 sec for test
		}

		// gets all player levels
		$allPlayerLevels = $this->getAllPlayerLevels();
		$levelMap = array();
		foreach ($allPlayerLevels as $lvl) {
			$levelMap[$lvl['vipsettingcashbackruleId']] = $lvl;
		}

		$result = array();

		$isBatchDowngrade = false;

		// batch downgrade
		$totalPlayerDowngrade = 0;
		if(is_array($playerIds)) {
			$isBatchDowngrade = true;
			if ($manual_batch)
				$isBatchDowngrade = !$manual_batch;
			$i = 0;
			$totalPlayer = count($playerIds);
			foreach($playerIds as $player) {
                $playerId = $player->playerId;

                $success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, $levelMap, $isBatchDowngrade, &$result, $order_generated_by, $time_exec_begin){
					$checkHourly=false;
                    $result = $this->playerLevelAdjustDowngrade($playerId, $levelMap, $isBatchDowngrade, $checkHourly, $time_exec_begin, $order_generated_by);
                    return $result;
                });

				if ( ! empty($result['is_player_downgrade']) ) {
					$totalPlayerDowngrade++;
				}

				$i++;
				if( $totalPlayer > $i ){ // ignore the last one.
					// BUDLD = batchUpDownLevelDowngrade
					if( ! empty($BUDLDintervalSec) ){
						$this->utils->debug_log('will BUDLDintervalSec', $BUDLDintervalSec);
						$this->utils->idleSec($BUDLDintervalSec);
					}
				} // EOF if( $totalPlayer > $i ){...
			}
			$this->utils->debug_log('Total player downgrade '.$totalPlayerDowngrade);
		} else {
			// manual downgrade
			$playerId = $playerIds;

            $success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, $levelMap, $isBatchDowngrade, &$result, $order_generated_by, $time_exec_begin){
				$checkHourly=false;
                $result = $this->playerLevelAdjustDowngrade($playerId, $levelMap, $isBatchDowngrade, $checkHourly, $time_exec_begin, $order_generated_by);
                return $result;
            });

			if ( ! empty($result['is_player_downgrade']) ) {
				$totalPlayerDowngrade++;
			}

			$this->utils->debug_log('manual downgrade player '.$playerId);
		}
		$result['totalPlayerDowngrade'] = $totalPlayerDowngrade;
		$this->resetBasicAmountAfterImported();
		return $result;
	}

	/**
	 * Is $time_exec_begin Met ScheduleDate from Period Settins of the level.
	 *
	 * @param array $schedule The data after json_decode() by the field, "vipsettingcashbackrule.period_up_down_2" for Up grade, Or "vipsettingcashbackrule.period_down" for Down grade.
	 * @param boolean $checkHourly If true, it's mean execute from batch_player_level_upgrade_check_hourly.
	 * @param boolean $isBatch If true, it's mean here are more one player in process to check upgrade/downgrade check.
	 * @param string $time_exec_begin The specified Current Datetime string. ie, "2020-06-11 23:05:12".
	 * @return boolean If true, it's mean the datetime,$time_exec_begin and hourly met the settings of the level.
	 */
	public function isMetScheduleDate(	$schedule // #1
										, $checkHourly = false // #2
										, $isBatch = false // #3
										, $time_exec_begin = null // #4
	) {
		$isMet = null;
		$currentDate = empty($time_exec_begin) ? new DateTime() : new DateTime($time_exec_begin);

		// To execute from batch_player_level_upgrade_check_hourly, fromHourlyCronjob
		// The setting had hourly, hourlyInSetting
		// Is met in date, isMetData
		// fromHourlyCronjob: 若 true 表示， cronjob 執行 hourly 任務。
		// hourlyInSetting: 若 true 表示，設定裡有勾選 hourly。
		// isMetData: 若 true 表示，該日期（ $time_exec_begin ）為預計要執行的日期。
		/// fromHourlyCronjob, hourlyInSetting, isMetData
		// 0,0,0 => 0, because isMetData=0 不符合日期
		// 0,0,1 => 1, because fromHourlyCronjob == hourlyInSetting and isMetData=0 符合日期，且 設定裡，沒有勾選 hourly 、 cronjob 剛好執行「非」hourly 任務。
		// 0,1,0 => 0, because isMetData=0 不符合日期
		// 0,1,1 => 0, because fromHourlyCronjob != hourlyInSetting
		// 1,0,0 => 0, because fromHourlyCronjob != hourlyInSetting
		// 1,0,1 => 0, because fromHourlyCronjob != hourlyInSetting
		// 1,1,0 => 1, because hourlyInSetting=1 and fromHourlyCronjob == hourlyInSetting
		// 1,1,1 => 1

		$fromHourlyCronjob = $checkHourly;
		if($fromHourlyCronjob === 'false'){
			$fromHourlyCronjob = false;
		}
		$setHourly = isset($schedule['hourly']) ? $schedule['hourly'] : false;
		if( $setHourly !== true){
			$setHourly = false;
		}
		$hourlyInSetting = $setHourly;

		$isMetData = false;
		if(isset($schedule['daily'])) {
			$isMetData = true;
		}else if(isset($schedule['weekly'])) {
			$currentWeekNum = $currentDate->format('N');
			if ((int)$currentWeekNum == (int)$schedule['weekly'] ) {
				$isMetData = true;
			}
		} else if(isset($schedule['monthly'])) {
			$currentDayNum = $currentDate->format('d');
			if ((int)$currentDayNum == (int)$schedule['monthly'] ) {
				$isMetData = true;
			}
		}
		if( empty($fromHourlyCronjob) && empty($hourlyInSetting) && empty($isMetData) ){
			// 0,0,0 => 0 , bc isMetData=0
			$isMet = false;
		}else if( empty($fromHourlyCronjob) && empty($hourlyInSetting) && !empty($isMetData) ){
			// 0,0,1 => 1, bc fromHourlyCronjob == hourlyInSetting and isMetData=0
			$isMet = true;
		}else if( empty($fromHourlyCronjob) && !empty($hourlyInSetting) && empty($isMetData) ){
			// 0,1,0 => 0, bc because fromHourlyCronjob != hourlyInSetting
			$isMet = false;
		}else if( empty($fromHourlyCronjob) && !empty($hourlyInSetting) && !empty($isMetData) ){
			// 0,1,1 => 0, bc because fromHourlyCronjob != hourlyInSetting
			$isMet = false;
		}else if( !empty($fromHourlyCronjob) && empty($hourlyInSetting) && empty($isMetData) ){
			// 1,0,0 => 0, bc because fromHourlyCronjob != hourlyInSetting
			$isMet = false;
		}else if( !empty($fromHourlyCronjob) && empty($hourlyInSetting) && !empty($isMetData) ){
			// 1,0,1 => 1, bc fromHourlyCronjob != hourlyInSetting
			$isMet = false;
		}else if( !empty($fromHourlyCronjob) && !empty($hourlyInSetting) && empty($isMetData) ){
			// 1,1,0 => 1, bc hourlyInSetting=1 and fromHourlyCronjob == hourlyInSetting
			$isMet = true;
		}else if( !empty($fromHourlyCronjob) && !empty($hourlyInSetting) && !empty($isMetData) ){
			// 1,1,1 => 1
			$isMet = true;
		}

		if( empty($isBatch) ){ // empty($isBatch)  always be confirm schedule from SBE to exec
			$isMet = false;
			if(isset($schedule['daily'])) {
				$isMet = true;
			}else if(isset($schedule['weekly'])) {
				$currentWeekNum = $currentDate->format('N');
				if ((int)$currentWeekNum == (int)$schedule['weekly'] ) {
					$isMet = true;
				}
			} else if(isset($schedule['monthly'])) {
				$currentDayNum = $currentDate->format('d');
				if ((int)$currentDayNum == (int)$schedule['monthly'] ) {
					$isMet = true;
				}
			}
		}

		$this->utils->debug_log('isMetScheduleDate params: schedule', $schedule
		, 'setHourly', $setHourly
		, 'checkHourly', $checkHourly
		, 'time_exec_begin', $time_exec_begin
		, 'fromHourlyCronjob', $fromHourlyCronjob
		, 'hourlyInSetting', $hourlyInSetting
		, 'isMetData', $isMetData
		, 'isBatch', $isBatch
		, 'isMet', $isMet );

		return $isMet;
	}// EOF isMetScheduleDate

	/**
	 * Get Date Range By Schedule Info
	 *
	 *
	 * @param array $schedule The data after json_decode() by the field, "vipsettingcashbackrule.period_up_down_2" for Up grade, Or "vipsettingcashbackrule.period_down" for Down grade.
	 * @param integer $subNumber
	 * - The return,"dateFrom" will get the date that's the difference n-cycles.
	 * - The return,"dateTo" will get the date that's the difference n-cycles.(default is 1, and max-limit is 2)
	 *
	 * @param boolean $isBatch For "Check Upgrade Condition" and "Check Downgrade Condition" of userInformation page.
	 * trigger calc player VIP level ignore Period Up/Down.
	 * @param boolean $setHourly for the vip level allows hourly upgrating
	 * @param string $time_exec_begin The specified Current Datetime string. ie, "2020-06-11 23:05:12".
	 * @param string $adjustGradeTo The adjust grade to "up"/"down"?
	 *
	 * @return array The return array format,
	 * - periodType daily, weekly, monthly and
	 * - baseTime means $time_exec_begin.
	 * - dateFrom After calc datetime for start.
	 * - dateTo After calc datetime for end.
	 */

    public function getScheduleDateRange($schedule, $subNumber = 1, $isBatch, $setHourly = false, $time_exec_begin = null, $adjustGradeTo = 'up') {
        $dateRange = array();
        $fromDatetime = null;
        $toDatetime = null;

        // OGP-8276: Use time of job execution instead of execution time of method
       	$currentDate = empty($time_exec_begin) ? new DateTime() : new DateTime($time_exec_begin);
       	$fromDateTime = clone $currentDate;
        $toDateTime = clone $currentDate;
		$currentHour = $currentDate->format('H');

$this->utils->debug_log('OGP-20868.getScheduleDateRange.currentDate:', $currentDate
							, 'subNumber:', $subNumber
							, 'time_exec_begin:', $time_exec_begin
							, 'adjustGradeTo:', $adjustGradeTo
							, 'schedule:', $schedule
						);
		if( empty($subNumber) ){ // Patch for $subNumber = null (related feature, vip_level_maintain_settings)
			$subNumber = 0;
		}

        $subEndNumber = 1;
        if ($subNumber > 1) {
        	$subEndNumber = 2;
		}


        // Scheduled daily
        $periodType = 'daily';
        if(isset($schedule['daily'])) {
	        $periodType = 'daily';
	        if($setHourly) { // By OGP-6439	//it means this vip level allows hourly upgrating
				if($currentHour == '00') {	//hour=00, use last period
					$fromDatetime = $fromDateTime->modify('-'.$subNumber.' day')->format('Y-m-d 00:00:00');
					$toDatetime = $toDateTime->modify('-'.$subEndNumber.' day')->format('Y-m-d 23:59:59');
				}
				else {
					$fromDatetime = $fromDateTime->format('Y-m-d 00:00:00');
					$toDatetime = $toDateTime->format('Y-m-d 23:59:59');
				}
			}
	        else {
				if ($subNumber == 0 && $adjustGradeTo != 'up') { // while guaranteed_downgrade_period_number = 0
					$subEndNumber = 0;
				}
				$fromDatetime = $fromDateTime->modify('-'.$subNumber.' day')->format('Y-m-d 00:00:00');
				$toDatetime = $toDateTime->modify('-'.$subEndNumber.' day')->format('Y-m-d 23:59:59');
	        }
        // Scheduled weekly
        } else if(isset($schedule['weekly'])) {
	        $periodType = 'weekly';
        	$currentWeekNum = $currentDate->format('N');
        	if ($currentWeekNum == $schedule['weekly'] || ($isBatch == false)) {
				$fromDatetime = $fromDateTime->modify('-'.$subNumber.' week')->modify('this week monday')->format('Y-m-d 00:00:00');
				$toDatetime = $toDateTime->modify('-'.$subEndNumber.' week')->modify('this week sunday')->format('Y-m-d 23:59:59');
        	}
			if($setHourly) { // By OGP-6439
				if( ($currentHour == '00') && ($currentWeekNum == '1') ) {	//week=1 (Monday) and hour=00, use last period
					$fromDatetime = $fromDateTime->modify('-'.$subNumber.' week')->modify('this week monday')->format('Y-m-d 00:00:00');
					$toDatetime = $toDateTime->modify('-'.$subEndNumber.' week')->modify('this week sunday')->format('Y-m-d 23:59:59');
				}
				else {
					$fromDatetime = $fromDateTime->modify('this week monday')->format('Y-m-d 00:00:00');
					$toDatetime = $toDateTime->format('Y-m-d 23:59:59');
				}
			}
		// Scheduled monthly
        } else if(isset($schedule['monthly'])) {
	        $periodType = 'monthly';
			$currentDayNum = $currentDate->format('d');

			if ( (int)$currentDayNum == (int)$schedule['monthly'] // current is check date.
				&& (int)$schedule['monthly'] == 1 // the check Period is Monthly 1st.
				&& $currentHour == '00' // the current time is within Zero o'clock.
			) {
				$currentHour = "01"; // for avoid the 2 months accumulation.
			}

        	if ((int)$currentDayNum == (int)$schedule['monthly'] || ($isBatch == false)) {
	        	$fromDatetime = $fromDateTime->modify('-'.$subNumber.' month')->modify('first day of this month')->format('Y-m-d 00:00:00');
	        	$toDatetime = $toDateTime->modify('-'.$subEndNumber.' month')->modify('last day of this month')->format('Y-m-d 23:59:59');
        	}
			if($setHourly) { // By OGP-6439
				if( ($currentHour == '00') && ($currentDayNum == '1') ) {	//date=1 and hour=00, use last period
					$fromDatetime = $fromDateTime->modify('-'.$subNumber.' month')->modify('first day of this month')->format('Y-m-d 00:00:00');
					$toDatetime = $toDateTime->modify('-'.$subEndNumber.' month')->modify('last day of this month')->format('Y-m-d 23:59:59');
				}
				else {
					$fromDatetime = $fromDateTime->modify('first day of this month')->format('Y-m-d 00:00:00');
					$toDatetime = $toDateTime->format('Y-m-d 23:59:59');
				}
			}
		// Scheduled yearly
        } else if(isset($schedule['yearly'])) { // OGP-7634 deprecate yearly option.
	        $periodType = 'yearly';
			$yearLySettingNum = sprintf('%02d', $yearly).'-01';
			$currentMonthDate = $currentDate->format('m-d');
            if ($currentMonthDate == $yearLySettingNum || ($isBatch == false)) {
                $fromDatetime = $fromDateTime->modify('-'.$subNumber.' year')->format('Y-01-01 00:00:00');
                $toDatetime = $toDateTime->modify('-'.$subEndNumber.' year')->format('Y-12-31 23:59:59');
            }
        }
        if(!empty($schedule)) {
			$dateRange = array( 'periodType' => $periodType
			, 'baseTime' => $currentDate
			, 'dateFrom' => $fromDatetime
			, 'dateTo' => $toDatetime
			, 'isBatch'=> $isBatch);
        }
		$this->utils->debug_log('OGP-21051.2614.getScheduleDateRange.dateRange:', $dateRange,
		'params:', 'schedule:', $schedule
		, 'subNumber:', $subNumber
		, 'isBatch:', $isBatch
		, 'setHourly:', $setHourly
		, 'time_exec_begin:',$time_exec_begin
		, 'adjustGradeTo:',$adjustGradeTo );

        return $dateRange;
    }

	/**
	 * Detect the some condition from LastGradeRecord
	 *
	 * @param integer $playerId The field, "player.playerId".
	 * @param string $playerCreatedOn The field, "player.createdOn". Or date format,"YYYY-mm-dd HH:ii:ss".
	 * @param string $time_exec_begin The datetime for now, or the specified time while executed with cli.The date format,"YYYY-mm-dd HH:ii:ss".
	 * @param function $fnCondition The condition with the param, last data of the player from vip_grade_report.
	 * - bool:fnCondition(array row_of_vip_grade_report)
	 * @return boolean The boolean of met the condition.
	 */
	public function detectConditionFromLastGradeRecord($playerId = 0, $playerCreatedOn = 'now', $time_exec_begin = 'now', $fnCondition) {
		$fromDate = $this->utils->checkDateWithMethodFormat($playerCreatedOn);
		$now = new DateTime($time_exec_begin);
		$toDate = $this->utils->formatDateTimeForMysql($now);
		$changedGrade = null;
		$theLastGradeRecord = $this->queryLastGradeRecordRowBy($playerId, $fromDate, $toDate, $changedGrade);
		$resultBool = $fnCondition($theLastGradeRecord);
		return $resultBool;
	}

	/**
	 * Detect the Player has Down and non-Specific Grade at the Last data From vip_grade_report
	 *
	 * @param integer $playerId The field, "player.playerId".
	 * @param string $playerCreatedOn The field, "player.createdOn". Or date format,"YYYY-mm-dd HH:ii:ss".
	 * @param string $time_exec_begin The datetime for now, or the specified time while executed with cli.The date format,"YYYY-mm-dd HH:ii:ss".
	 * @return boolean  If true means met the condition.
	 */
	public function isDownAndNonSpecificGradeFromLastGradeRecord($playerId, $playerCreatedOn, $time_exec_begin = null){
		$fnIsDowngrade = function($theLastGradeRecord){
					$is_level_from_great_to = intval($theLastGradeRecord['level_from']) > intval($theLastGradeRecord['level_to']) ;
					$is_request_type_neq_specific = $theLastGradeRecord['request_type'] != Group_level::REQUEST_TYPE_SPECIFIC_GRADE;
					$returnBool = 	$is_level_from_great_to && $is_request_type_neq_specific;
			return $returnBool;
		};
		return $this->detectConditionFromLastGradeRecord($playerId, $playerCreatedOn, $time_exec_begin, $fnIsDowngrade);
	}
	/**
	 * Detect the Player has Down OR Specific Grade at the Last data From vip_grade_report
	 *
	 * @param integer $playerId The field, "player.playerId".
	 * @param string $playerCreatedOn The field, "player.createdOn". Or date format,"YYYY-mm-dd HH:ii:ss".
	 * @param string $time_exec_begin The datetime for now, or the specified time while executed with cli.The date format,"YYYY-mm-dd HH:ii:ss".
	 * @return boolean  If true means met the condition.
	 */
	public function isDownOrSpecificGradeFromLastGradeRecord($playerId, $playerCreatedOn, $time_exec_begin = null){
		$fnIsDowngrade = function($theLastGradeRecord){
			// $is_level_from_great_to = intval($theLastGradeRecord['level_from']) > intval($theLastGradeRecord['level_to']) ;
			$is_request_grade_is_downgrade = $theLastGradeRecord['request_grade'] == Group_level::RECORD_DOWNGRADE;
			$is_request_type_is_specific = $theLastGradeRecord['request_type'] == Group_level::REQUEST_TYPE_SPECIFIC_GRADE;
			$returnBool = $is_request_grade_is_downgrade || $is_request_type_is_specific;
			return $returnBool;
		};
		return $this->detectConditionFromLastGradeRecord($playerId, $playerCreatedOn, $time_exec_begin, $fnIsDowngrade);
	}


	/**
	 * Get the Initial Amount from vip_upgrade_setting between currect and previous level.
	 *
	 * @param array $curr_vip_upgrade_setting The currect level from vip_upgrade_setting
	 * @param array $pre_vip_upgrade_setting The previous level of currect from vip_upgrade_setting
	 * @return array $initialValueList The initial amount list of the formula and bet_amount_settings.
	 *
	 * {"message":"OGP-19825.calcOffsetRules.$initialValueList","context":[{"bet_amount":"12125","deposit_amount":"12126"}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2020-11-24 15:34:38 413340","extra":{"tags":{"request_id":"815852e9a895841d11c7652d3a21e2d8","env":"live.og_local","version":"6.88.01.001","hostname":"default-og"},"file":"/home/vagrant/Code/og/admin/application/models/group_level.php","line":2888,"class":"Group_level","function":"playerLevelAdjust","url":"/player_management/manuallyUpgradeLevel/16821","ip":"172.22.0.1","http_method":"GET","referrer":"http://admin.og.local/player_management/userInformation/16821","host":"admin.og.local","real_ip":null,"browser_ip":null,"process_id":84981,"memory_peak_usage":"11.75 MB","memory_usage":"11.75 MB"}}
	 *
	 */
	public function getInitialValueFromVip_upgrade_setting($curr_vip_upgrade_setting, $pre_vip_upgrade_setting){

		// CUVUS = curr_vip_upgrade_setting
		$formula4CUVUS = $curr_vip_upgrade_setting['formula'];
		$bet_amount_settings4CUVUS = $curr_vip_upgrade_setting['bet_amount_settings'];
		// PRVUS = pre_vip_upgrade_setting
		$formula4PRVUS = $pre_vip_upgrade_setting['formula'];
		$bet_amount_settings4PRVUS = $pre_vip_upgrade_setting['bet_amount_settings'];

		$parseSetFormula4CUVUS = $this->parseSetStrListWithFormulaV2($formula4CUVUS, $bet_amount_settings4CUVUS);

		$parsedformula4CUVUS = $parseSetFormula4CUVUS['parsedFormula'];

		// $parsedformula4CUVUS['option']; // xxx_amount
		// $parsedformula4CUVUS['bet_amount_setting']
		$this->utils->debug_log('OGP-19825.getInitialValueFromVip_upgrade_setting.parseSetFormula4CUVUS',$parseSetFormula4CUVUS);
		// {"message":"OGP-19825.getInitialValueFromVip_upgrade_setting.parseSetFormula4CUVUS","context":[{"parsedFormula":["game_platform_id_8 > 201","and deposit_amount >= 111"],"formula":"game_platform_id_8 > 201 and deposit_amount >= 111","setting_list":{"game_platform_id_8":"201","deposit_amount":"111"}}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2020-11-24 14:59:28 183953","extra":{"tags":{"request_id":"7137c6688108d7b4216bbb44727dfd7c","env":"live.og_local","version":"6.88.01.001","hostname":"default-og"},"file":"/home/vagrant/Code/og/admin/application/models/group_level.php","line":2672,"class":"Group_level","function":"calcOffsetRules","url":"/player_management/manuallyUpgradeLevel/16821","ip":"172.22.0.1","http_method":"GET","referrer":"http://admin.og.local/player_management/userInformation/16821","host":"admin.og.local","real_ip":null,"browser_ip":null,"process_id":84698,"memory_peak_usage":"11.75 MB","memory_usage":"11.75 MB"}}

		// parsedFormula
		$parseSetFormula4PRVUS = $this->parseSetStrListWithFormulaV2($formula4PRVUS, $bet_amount_settings4PRVUS);
		$this->utils->debug_log('OGP-19825.getInitialValueFromVip_upgrade_setting.parseSetFormula4PRVUS',$parseSetFormula4PRVUS);
		// {"message":"OGP-19825.getInitialValueFromVip_upgrade_setting.parseSetFormula4PRVUS","context":[{"parsedFormula":["game_type_id_22 >= 122 and game_platform_id_38 >= 123","and deposit_amount >= 124"],"formula":"game_type_id_22 >= 122 and game_platform_id_38 >= 123 and deposit_amount >= 124","setting_list":{"game_type_id_22":"122","game_platform_id_38":"123","deposit_amount":"124"}}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2020-11-24 14:59:28 188757","extra":{"tags":{"request_id":"7137c6688108d7b4216bbb44727dfd7c","env":"live.og_local","version":"6.88.01.001","hostname":"default-og"},"file":"/home/vagrant/Code/og/admin/application/models/group_level.php","line":2672,"class":"Group_level","function":"calcOffsetRules","url":"/player_management/manuallyUpgradeLevel/16821","ip":"172.22.0.1","http_method":"GET","referrer":"http://admin.og.local/player_management/userInformation/16821","host":"admin.og.local","real_ip":null,"browser_ip":null,"process_id":84698,"memory_peak_usage":"11.75 MB","memory_usage":"11.75 MB"}}
		$initialValueList = [];
		if( ! empty($parseSetFormula4CUVUS['setting_list']) ){
			$setting_list = $parseSetFormula4CUVUS['setting_list'];
			foreach($setting_list as $settingKey => $settingAmount){
				if( !empty($parseSetFormula4PRVUS['setting_list'][$settingKey]) ){
					$PRVUS4CurrAmount = $parseSetFormula4PRVUS['setting_list'][$settingKey];
					$initialValueList[$settingKey] = $PRVUS4CurrAmount;
				}else{
					// If the previous had Not defined than add the initial value, Zero into the initialValueList for Check Upgrade Calculations.
					$initialValueList[$settingKey] = 0;
				}
			}
		}

		return $initialValueList;
	} // EOF getInitialValueFromVip_upgrade_setting
	/**
	 * Undocumented function
	 *
	 * @param array $playerRow The player data.
	 * @param array $playerLevelRow The data of vipsetting.
	 * @param array $vip_upgrade_setting The player currect upgrade setting, the row of the data-table,"vip_upgrade_setting".
	 * @param array $allLevelMap All level, Lv1~4,... under the vipsetting.
	 * @param string $time_exec_begin
	 * @param string $checkHourly
	 * @return array $initialAmountList The array for simulated and merge into result after calc, and the formats,
	 * - $initialAmountList[total_bet] integer|float The initial amount for Total bet amount. To ignore if separated_bet is Not empty.
	 * - $initialAmountList[separated_bet] array The dynamic separated bet amount by game tree.
	 *   Reference to followings for more example,
	 *     = $initialAmountList[separated_bet][game_type_id_324] = 7012
	 *     = $initialAmountList[separated_bet][game_platform_id_8] = 701
	 *
	 * - $initialAmountList[deposit] integer|float The initial amount for deposit.
	 * - $initialAmountList[total_win] integer|float
	 * - $initialAmountList[total_loss] integer|float
	 * should be [$deposit, $gameLogData] for apply.
	 */
	public function getInitialValues4player($playerRow, $playerLevelRow, $vip_upgrade_setting, $allLevelMap, $time_exec_begin = 'now', $checkHourly=false){
		// $playerRow
		// $playerLevelRow //vipsetting + vipsettingcashbackrule
		$vip = $playerLevelRow;

		$downgradedVipLevelId = $this->getVipLevelIdByLevelNumber($vip['vipSettingId'], $vip['vipLevel'] - 1);
		$upgradedVipLevelId = $this->getVipLevelIdByLevelNumber($vip['vipSettingId'], $vip['vipLevel'] + 1);
		$currVipLevelId = $this->getVipLevelIdByLevelNumber($vip['vipSettingId'], $vip['vipLevel']);

		$downgradedVipLevel = $allLevelMap[$downgradedVipLevelId];
		// for log into player_level_adjustment_history
		$this->PLAH['previous_vipsettingcashbackrule'] = $this->utils->encodejson($downgradedVipLevel);

		$vip_upgrade_setting4DGVL = [];// DGVL = downgradedVipLevel
		if( ! empty($downgradedVipLevel['vip_upgrade_id']) ){

			$vip_upgrade_setting4DGVL = $this->getSettingData($downgradedVipLevel['vip_upgrade_id']);
			// for log into player_level_adjustment_history
			$this->PLAH['previous_vipupgradesetting'] = $this->utils->encodejson($vip_upgrade_setting4DGVL);
		}
		// $formula4DGVL = $this->utils->decodeJson($vip_upgrade_setting4DGVL['formula']);// 上一等級的公式
		// $bet_amount_settings4DGVL = $this->utils->decodeJson($vip_upgrade_setting4DGVL['bet_amount_settings']);// 上一等級的遊戲拆分投注金額公式

		$this->utils->debug_log('OGP-19825.calcOffsetRules.$downgradedVipLevel', $downgradedVipLevel['vipLevelName'], 'vipsettingcashbackruleId:', $downgradedVipLevel['vipsettingcashbackruleId'], $downgradedVipLevel['vip_upgrade_id']);

		$currVipLevel = $allLevelMap[$currVipLevelId];
		if( empty($currVipLevel) ){
			// OGP-19825 handle not found currect setting, but should Not be trigger.
		}
		$this->utils->debug_log('OGP-19825.calcOffsetRules.$currVipLevel:', $currVipLevel['vipLevelName'], 'vipsettingcashbackruleId:', $currVipLevel['vipsettingcashbackruleId']);

		// for log into player_level_adjustment_history
		$this->PLAH['current_vipsettingcashbackrule'] = $this->utils->encodejson($currVipLevel);

		// CUVL = currVipLevel
		if( ! empty($currVipLevel['vip_upgrade_id']) ){
			$vip_upgrade_setting4CUVL = $this->getSettingData($currVipLevel['vip_upgrade_id']);

			// for log into player_level_adjustment_history
			$this->PLAH['current_vipupgradesetting'] = $this->utils->encodejson($vip_upgrade_setting4CUVL);
		}
		// $formula4CUVL = $this->utils->decodeJson($vip_upgrade_setting4CUVL['formula']);// 當下等級的公式
		// $bet_amount_settings4CUVL = $this->utils->decodeJson($vip_upgrade_setting4CUVL['bet_amount_settings']);// 當下等級的遊戲拆分投注金額公式


		$initialValueList = $this->getInitialValueFromVip_upgrade_setting($vip_upgrade_setting4CUVL, $vip_upgrade_setting4DGVL);
$this->utils->debug_log('OGP-19825.calcOffsetRules.$initialValueList', $initialValueList);
// {"message":"OGP-19825.calcOffsetRules.$initialValueList","context":[{"bet_amount":"301"}],"level":100,"level_name":"DEBUG","channel":"default-og","datetime":"2020-11-26 12:16:28 360806","extra":{"tags":{"request_id":"c9c179b201d60ae29da830efdc974f53","env":"live.og_local","version":"6.88.01.001","hostname":"default-og"},"file":"/home/vagrant/Code/og/admin/application/models/group_level.php","line":2915,"class":"Group_level","function":"playerLevelAdjust","url":"/player_management/manuallyUpgradeLevel/159500","ip":"172.22.0.1","http_method":"GET","referrer":"http://admin.og.local/player_management/userInformation/159500","host":"admin.og.local","real_ip":null,"browser_ip":null,"process_id":90800,"memory_peak_usage":"12 MB","memory_usage":"11.75 MB"}}


		$initialAmountList  = [];
		// $initialAmountList['bet_amount'] = 0; // default - common_bet
		// if( !empty( $initialValueList['bet_amount'] ) ){
		// 	$initialAmountList['bet_amount'] = $initialValueList['bet_amount'];
		// }
		$initialAmountList['total_bet'] = 0; // default - separated_bet
		if( !empty( $initialValueList['bet_amount'] ) ){ // for sacb
			$initialAmountList['total_bet'] = $initialValueList['bet_amount']; // from vip_upgrade_setting.formula
		}
$this->utils->debug_log('OGP-19825.2717.calcOffsetRules.$initialAmountList', $initialAmountList);
		if( !empty( $initialValueList['total_bet'] ) ){
			// $initialAmountList['total_bet'] = $initialValueList['total_bet'];
		}
		$initialAmountList['separated_bet'] = []; // default
		$findwe = [];
		$findwe[] = 'game_platform';
		$findwe[] = 'game_type';
		$findweResultList = $this->utils->filterKeyContainStringsOfList($findwe, $initialValueList);
		if( !empty( $findweResultList ) ){
			$initialAmountList['separated_bet'] = $findweResultList;
		}
		$this->utils->debug_log('OGP-19825.2717.calcOffsetRules.$findweResultList', $findweResultList);

		$initialAmountList['deposit'] = 0; // default
		if( !empty( $initialValueList['deposit_amount'] ) ){
			$initialAmountList['deposit'] = $initialValueList['deposit_amount']; // from vip_upgrade_setting.formula
		}

		$initialAmountList['total_win'] = 0; // default
		if( !empty( $initialValueList['win_amount'] ) ){
			$initialAmountList['total_win'] = $initialValueList['win_amount']; // from vip_upgrade_setting.formula
		}
		$initialAmountList['total_loss'] = 0; // default
		if( !empty( $initialValueList['loss_amount'] ) ){
			$initialAmountList['total_loss'] = $initialValueList['loss_amount']; // from vip_upgrade_setting.formula
		}
$this->utils->debug_log('OGP-19825.2733.calcOffsetRules.$initialAmountList', $initialAmountList);
		return $initialAmountList;

	} // EOF getInitialValues4player

	/**
	 * The Macro for Get Game Log Data via calcSeparateAccumulationByExtraSettingList()
	 *
	 * @param boolean $isSeparateAccumulation The return of self::_macroUpgradeSettingReturnFlagsSetting().
	 * @param array $setting The return of self::getSettingData(). Should be the row of the table, vip_upgrade_setting.
	 * @param object $player The return of player_model::getPlayerById().
	 * @param string $time_exec_begin The $time_exec_begin from param of self::playerLevelAdjust().
	 * @param array $playerLevel The player's data, the row of the data-table,"vipsettingcashbackrule". ( from self::getAllPlayerLevels() )
	 * @param boolean $isBatchUpgrade The $isBatchUpgrade from param of self::playerLevelAdjust().
	 * @param array $upgradeSched The return of self::getScheduleDateRange().
	 * @param array $schedule The result of self::_macroUpgradeSettingReturnFlagsSetting().
	 * @param boolean $setHourly The return of self::_macroUpgradeSettingReturnFlagsSetting().
	 * @param string $bet_amount_settingsStr The json string from the field,"vip_upgrade_setting.bet_amount_settings".
	 * @param array $initialAmount The return of self::_macroGetInitialAmount().
	 * @param string $fromDatetime The return of self::_macroPreviousMomentsByAccumulation().
	 * @param string $fromDatetime The return of self::_macroPreviousMomentsByAccumulation().
	 * @param array $isMetOffsetRules The return of self::_macroIsMetOffsetRules().
	 * @return array The return format,
	 * - $return['gameLogData'] array The array from self::calcSeparateAccumulationByExtraSettingList(), and should be contains the folloeing,
	 *  = $return['gameLogData']['total_bet'] integer|float The count of the bet amount.
	 *  = $return['gameLogData']['separated_bet'] array Priority reference to gameLogData. The return of self::calcSeparateAccumulationByExtraSettingList().
	 *  = $return['gameLogData']['total_loss'] integer|float The count of the loss amount.
	 *  = $return['gameLogData']['total_win'] integer|float The count of the win amount.
	 * - $return['deposit'] integer|float The count of the deposit amount.
	 * - $return['calcResult'] array The array for reference to the grade report , "vip_grade_report.remark".
	 *
	 */
	public function _macroGetGameLogDataAndDeposit( $isSeparateAccumulation // # 1
		, $setting // # 2
		, $player // # 3
		, $time_exec_begin // # 4
		, $playerLevel // # 5
		, $isBatchUpgrade // # 6
		, $upgradeSched // # 7
		, $schedule // # 8
		, $setHourly // # 9
		, $bet_amount_settingsStr // # 10
		, $initialAmount // # 11
		, $fromDatetime // # 12
		, $toDatetime // # 13
		, $isMetOffsetRules // # 14
	){

		$formula = '';
		$return = [];
		$gameLogData = [];
		$deposit = null;
		$isForceAccumulationLastChangePeriod = false; // default
		$accumulationMode = Group_level::ACCUMULATION_MODE_DISABLE; // for default
		$now = new DateTime($time_exec_begin); // OGP-19332 Patch for miss the param,"$now".
		$playerId = $player->playerId;
		$formula = null;
		$separate_accumulation_settings = null;

		if( $isMetOffsetRules['bool']
			// && $setting['accumulation'] == Group_level::ACCUMULATION_MODE_FROM_REGISTRATION
		){ // force Accumulation use "Last Change Period".
			$isForceAccumulationLastChangePeriod = true;
		}
$this->utils->debug_log('OGP-19825.2828.$isMetOffsetRules', $isMetOffsetRules, '$isForceAccumulationLastChangePeriod:', $isForceAccumulationLastChangePeriod, '$player:', $player);
		if($isSeparateAccumulation){
$this->utils->debug_log('OGP-19825.2814.$setting', $setting);
			// separate accumulation
			if(!empty($setting)){
				$formula = $setting['formula'];
				$separate_accumulation_settings = $setting['separate_accumulation_settings'];
			}

			$playerCreatedOn = $player->createdOn;
			$time_exec_begin = $now->format('Y-m-d H:i:s');
			$subNumber = 1; // upgrade only 1.
			$isBatch = $isBatchUpgrade;

			if( ! empty($separate_accumulation_settings) ){
$this->utils->debug_log('OGP-19825.2808.$separate_accumulation_settings', $separate_accumulation_settings);
				if(is_string($separate_accumulation_settings)){
					$separate_accumulation_settings = json_decode($separate_accumulation_settings, true);
				}else{
					$separate_accumulation_settings = $separate_accumulation_settings;
				}
// $this->utils->debug_log('OGP-19825.2813.$separate_accumulation_settings', var_export($separate_accumulation_settings, true) );
$this->utils->debug_log('OGP-19825.2814.$separate_accumulation_settings', $separate_accumulation_settings);
$this->utils->debug_log('OGP-19825.2815.$isMetOffsetRules', $isMetOffsetRules);

				foreach($separate_accumulation_settings as $keyString => $separated ){ // keyString = bet_amount, deposit_amount,...
$this->utils->debug_log('OGP-19825.2817.$separated', $separated);
					if( $isForceAccumulationLastChangePeriod
						// && $separated['accumulation'] != Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE
					){ // force Accumulation use "Last Change Period".
						$accumulationMode = Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE;
						$separate_accumulation_settings[$keyString]['accumulation'] = $accumulationMode;
						// $separate_accumulation_settings[$keyString]['isForceOffsetRules'] = true;
					}else{
						// $separate_accumulation_settings[$keyString]['isForceOffsetRules'] = false;
					}
				}
			} // EOF if( ! empty($separate_accumulation_settings) ){...
$this->utils->debug_log('OGP-19825.2823.$separate_accumulation_settings', $separate_accumulation_settings);

			$calcResult = $this->calcSeparateAccumulationByExtraSettingList( $separate_accumulation_settings // # 1
																			, $formula // # 2
																			, $bet_amount_settingsStr // #2.1
																			, $upgradeSched // # 3
																			, $playerId // # 4
																			, $playerCreatedOn // # 5
																			, $time_exec_begin // # 6
																			, $schedule // # 7
																			, $subNumber // # 8
																			, $isBatch // # 9
																			, $setHourly // # 10
																		);
			// $calcResult convert to $gameLogData
			if( ! empty($calcResult['separated_bet']) ){
				$gameLogData['total_bet'] = null;
				$gameLogData['separated_bet'] = $calcResult['separated_bet'];
				if( ! empty($initialAmount['separated_bet']) ){
					$this->separated_bet_adding($gameLogData['separated_bet'], $initialAmount['separated_bet']);
				}
			}else if( isset($calcResult['total_bet']) ){
				$gameLogData['total_bet'] = $calcResult['total_bet'];
				$gameLogData['total_bet'] += $initialAmount['total_bet']; // for sacb
			}else{
				$gameLogData['total_bet'] = null;
			}
// if( isset($gameLogData['separated_bet']) ){
// $this->utils->debug_log('OGP-19332.separated_bet', $gameLogData['separated_bet']);
// }
			// $calcResult convert to $gameLogData
			if( isset($calcResult['total_win']) ){
				$gameLogData['total_win'] = $calcResult['total_win'];
				$gameLogData['total_win'] += $initialAmount['total_win'];
			}else{
				$gameLogData['total_win'] = null;
			}
			if( isset($calcResult['total_loss']) ){
				$gameLogData['total_loss'] = $calcResult['total_loss'];
				$gameLogData['total_loss'] += $initialAmount['total_loss'];
			}else{
				$gameLogData['total_loss'] = null;
			}
			if( isset($calcResult['deposit']) ){
				$deposit = $calcResult['deposit'];
				$deposit += $initialAmount['deposit'];
			}else{
				$deposit = null;
			}
		// EOF if($isSeparateAccumulation){...
		}else{

			// "non-Accumulation" and "common accumulation"
			// for calc the settings in common accumulation,"enable_separate_accumulation_in_setting = false".
			// $this->utils->debug_log('-------- upgrade get deposit amount from to ------', $playerId, $fromDatetime, $toDatetime);
			$formula = '';
			if(! empty($setting) ){
				$accumulationMode = (int)$setting['accumulation']; // Patch for A PHP Error was encountered | Severity: Notice | Message: Undefined index: accumulation | Filename: models/group_level.php:2904
				// use "separate accumulation" settings to run "common accumulation"
				$formula = $setting['formula']; // Patch for A PHP Error was encountered | Severity: Notice | Message: Undefined index: formula | Filename: models/group_level.php:2906
			}

			$playerCreatedOn = $player->createdOn;
			$time_exec_begin = $now->format('Y-m-d H:i:s');
			$subNumber = 1; // $playerLevel['guaranteed_period_number'];
			$isBatch = $isBatchUpgrade;

			if( $isForceAccumulationLastChangePeriod ){ // force Accumulation use "Last Change Period".
				$accumulationMode = Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE;
			}


			// SAS = separate_accumulation_settings
			// for apply to calcSeparateAccumulationByExtraSettingList()
			$_sas = [];
			$_sas['bet_amount'] = [];
			$_sas['bet_amount']['accumulation'] = $accumulationMode;
			$_sas['deposit_amount'] = [];
			$_sas['deposit_amount']['accumulation'] = $accumulationMode;
			$_sas['loss_amount'] = [];
			$_sas['loss_amount']['accumulation'] = $accumulationMode;
			$_sas['win_amount'] = [];
			$_sas['win_amount']['accumulation'] = $accumulationMode;
$this->utils->debug_log('OGP-19825.2941.$accumulationMode', $accumulationMode, '$isForceAccumulationLastChangePeriod:', $isForceAccumulationLastChangePeriod);
			$calcResult = $this->calcSeparateAccumulationByExtraSettingList( $_sas // # 1
																				, $formula // # 2
																				, $bet_amount_settingsStr // #2.1
																				, $upgradeSched // # 3
																				, $playerId // # 4
																				, $playerCreatedOn // # 5
																				, $time_exec_begin // # 6
																				, $schedule // # 7
																				, $subNumber // # 8
																				, $isBatch // # 9
																				, $setHourly // # 10
																			);
$this->utils->debug_log('OGP-19825.2954.$initialAmount', $initialAmount);
$this->utils->debug_log('OGP-19825.2955.$calcResult', $calcResult);
			// $calcResult convert to $gameLogData
			if( ! empty($calcResult['separated_bet']) ){
				$gameLogData['total_bet'] = null;
				$gameLogData['separated_bet'] = $calcResult['separated_bet'];
				$this->utils->debug_log('OGP-19825.3025.$gameLogData[separated_bet]', $gameLogData['separated_bet']);
				$this->utils->debug_log('OGP-19825.3025.$initialAmount[separated_bet]', $initialAmount['separated_bet']);

				$this->separated_bet_adding($gameLogData['separated_bet'], $initialAmount['separated_bet']);
			}else if( isset($calcResult['total_bet']) ){
				$gameLogData['total_bet'] = $calcResult['total_bet'];
				$gameLogData['total_bet'] += $initialAmount['total_bet']; // for cacb
			}else{
				$gameLogData['total_bet'] = null;
			}
			if( isset($calcResult['total_win']) ){
				$gameLogData['total_win'] = $calcResult['total_win'];
				$gameLogData['total_win'] += $initialAmount['total_win'];
			}else{
				$gameLogData['total_win'] = null;
			}
			if( isset($calcResult['total_loss']) ){
				$gameLogData['total_loss'] = $calcResult['total_loss'];
				$gameLogData['total_loss'] += $initialAmount['total_loss'];
			}else{
				$gameLogData['total_loss'] = null;
			}
// $this->utils->debug_log('OGP-19332.2770.calcResult', $calcResult);
// if( isset($gameLogData['separated_bet']) ){
// $this->utils->debug_log('OGP-19332.2770.separated_bet', $gameLogData['separated_bet']);
// }

			// $total_player_game_table = 'total_player_game_minute';
			// $where_date_field = 'date_minute';
			// $fromDatetime4minute = $this->utils->formatDateMinuteForMysql(new DateTime($fromDatetime));
			// $toDatetime4minute = $this->utils->formatDateMinuteForMysql(new DateTime($toDatetime));
			// $gameLogData = $this->total_player_game_day->getPlayerTotalBetWinLoss( $playerId // #1
			// 																	, $fromDatetime4minute // #2
			// 																	, $toDatetime4minute // #3
			// 																	, $total_player_game_table // #4
			// 																	, $where_date_field // #5
			// 																); // @todo OGP-19332

			// $gameLogData['separated_bet']
			/// Patch for cacb+ ACCUMULATION_MODE_FROM_REGISTRATION.
			// list($deposit) = $this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers($playerId, $fromDatetime, $toDatetime);
			if( isset($calcResult['deposit']) ){
				$deposit = $calcResult['deposit'];
				$deposit += $initialAmount['deposit']; // for sacb
			}else{
				$deposit = 0;
			}
		} // EOF if($isSeparateAccumulation){...

		$return['gameLogData'] = $gameLogData;
		$return['deposit'] = $deposit;
		$return['calcResult'] = $calcResult;
		return $return;
	} // EOF _macroGetGameLogDataAndDeposit

	/**
	 * Get initialAmount array from OGP-19825.
	 *
	 * @param array $isMetOffsetRules The array of self::_macroIsMetOffsetRules().
	 * @param object $player The return of player_model::getPlayerById().
	 * @param array $setting The return of self::getSettingData().
	 * @param array $levelMap The all Player Levels and key-value array. The format,
	 * @param string $time_exec_begin The $time_exec_begin from param of self::playerLevelAdjust().
	 * @return array The array format,
	 * - $initialAmount['total_bet'] integer|float
	 * - $initialAmount['separated_bet'] array
	 * - $initialAmount['deposit'] integer|float
	 * - $initialAmount['total_win'] integer|float
	 * - $initialAmount['total_loss'] integer|float
	 */
	public function _macroGetInitialAmountV2( $isMetOffsetRules // # 1
											, $player // # 2
											, $setting // # 3
											, $levelMap // # 4
											, $time_exec_begin // # 5
	){
		// simulated initial value of calc.
		$initialAmount  = [];
		$initialAmount['total_bet'] = 0;
		$initialAmount['deposit'] = 0;
		$initialAmount['total_win'] = 0;
		$initialAmount['total_loss'] = 0;

		$playerLevel = $levelMap[$player->levelId];
		// $this->utils->debug_log('OGP-19825.3064.$playerLevel:', $playerLevel,'$levelMap:', $levelMap); // ignore for data too long in log
		// for log into player_level_adjustment_history
		$this->PLAH['is_met_offset_rules_info'] = $this->utils->encodejson($isMetOffsetRules);

		if($isMetOffsetRules['bool']){
			/// After detect many case ,
			// $isMetOffsetRules['bool'] will enable/disable the feature,"apply the Offset Rules" for upgrade.
			$playerRow = (array)$player;
			$vip_upgrade_setting = $setting; // vip_upgrade_setting
			// $playerLevel //vipsetting + vipsettingcashbackrule
			// $levelMap all vipsettingcashbackrule,"Lv1~4,..." under the vipsetting
			// list($deposit, $gameLogData) = $this->calcOffsetRules( $playerRow, $playerLevel, $vip_upgrade_setting, $levelMap, $time_exec_begin);
			$initialAmountList = $this->getInitialValues4player( $playerRow, $playerLevel, $vip_upgrade_setting, $levelMap, $time_exec_begin);
		$this->utils->debug_log('OGP-19825.2939.$initialAmountList:', $initialAmountList);
			if( ! empty($initialAmountList) ){
				$initialAmount = array_replace_recursive($initialAmount, $initialAmountList);
			}
		$this->utils->debug_log('OGP-19825.2944.$initialAmount:', $initialAmount);
			/// output
			// $gameLogData['total_bet'] / $gameLogData['separated_bet']
			// $deposit
			// $gameLogData['total_win']
			// $gameLogData['total_loss']

			// for log into player_level_adjustment_history
			$this->PLAH['initial_amount'] = $this->utils->encodejson($initialAmount);
		}
		if( empty($initialAmount['separated_bet']) ){ // for normal and applied casb settings.
			$initialAmount['separated_bet'] = [];
		}
		return $initialAmount;
	} // EOF _macroGetInitialAmountV2
// 	/**
// 	 * Get initialAmount array from OGP-19825.
// 	 *
// 	 * @param array $isMetOffsetRules The array of self::_macroIsMetOffsetRules().
// 	 * @param object $player The return of player_model::getPlayerById().
// 	 * @param array $setting The return of self::getSettingData().
// 	 * @param array $playerLevel The player's data, the row of the data-table,"vipsettingcashbackrule". ( from self::getAllPlayerLevels() )
// 	 * @param array $levelMap The all Player Levels and key-value array. The format,
// 	 * @param string $time_exec_begin The $time_exec_begin from param of self::playerLevelAdjust().
// 	 * @return array The array format,
// 	 * - $initialAmount['total_bet'] integer|float
// 	 * - $initialAmount['separated_bet'] array
// 	 * - $initialAmount['deposit'] integer|float
// 	 * - $initialAmount['total_win'] integer|float
// 	 * - $initialAmount['total_loss'] integer|float
// 	 */
// 	public function _macroGetInitialAmount( $isMetOffsetRules // # 1
// 		, $player // # 2
// 		, $setting // # 3
// 		, $playerLevel // # 4
// 		, $levelMap // # 5
// 		, $time_exec_begin // # 6
// 	){
// 		// simulated initial value of calc.
// 		$initialAmount  = [];
// 		$initialAmount['total_bet'] = 0;
// 		$initialAmount['deposit'] = 0;
// 		$initialAmount['total_win'] = 0;
// 		$initialAmount['total_loss'] = 0;
// $this->utils->debug_log('OGP-19825.3064.$playerLevel:', $playerLevel,'$levelMap:', $levelMap);
// 		// for log into player_level_adjustment_history
// 		$this->PLAH['is_met_offset_rules_info'] = $this->utils->encodejson($isMetOffsetRules);
//
// 		if($isMetOffsetRules['bool']){
// 			/// After detect many case ,
// 			// $isMetOffsetRules['bool'] will enable/disable the feature,"apply the Offset Rules" for upgrade.
// 			$playerRow = (array)$player;
// 			$vip_upgrade_setting = $setting; // vip_upgrade_setting
// 			// $playerLevel //vipsetting + vipsettingcashbackrule
// 			// $levelMap all vipsettingcashbackrule,"Lv1~4,..." under the vipsetting
// 			// list($deposit, $gameLogData) = $this->calcOffsetRules( $playerRow, $playerLevel, $vip_upgrade_setting, $levelMap, $time_exec_begin);
// 			$initialAmountList = $this->getInitialValues4player( $playerRow, $playerLevel, $vip_upgrade_setting, $levelMap, $time_exec_begin);
// 		$this->utils->debug_log('OGP-19825.2939.$initialAmountList:', $initialAmountList);
// 			if( ! empty($initialAmountList) ){
// 				$initialAmount = array_replace_recursive($initialAmount, $initialAmountList);
// 			}
// 		$this->utils->debug_log('OGP-19825.2944.$initialAmount:', $initialAmount);
// 			/// output
// 			// $gameLogData['total_bet'] / $gameLogData['separated_bet']
// 			// $deposit
// 			// $gameLogData['total_win']
// 			// $gameLogData['total_loss']
//
// 			// for log into player_level_adjustment_history
// 			$this->PLAH['initial_amount'] = $this->utils->encodejson($initialAmount);
// 		}
// 		if( empty($initialAmount['separated_bet']) ){ // for normal and applied casb settings.
// 			$initialAmount['separated_bet'] = [];
// 		}
// 		return $initialAmount;
// 	} // EOF _macroGetInitialAmount


	/**
	 * Get Previous Moments
	 *
	 * @param boolean $isAccumulation The Accumulation from "vip_upgrade_setting.accumulation" / "vip_upgrade_setting.separate_accumulation_settings".
	 * @param object $player The return of player_model::getPlayerById().
	 * @param array $setting The return of self::getSettingData().
	 * @param array $schedule The result of self::_macroUpgradeSettingReturnFlagsSetting().
	 * @param array $playerLevel The player's data, the row of the data-table,"vipsettingcashbackrule". ( from self::getAllPlayerLevels() )
	 * @param string $time_exec_begin The $time_exec_begin from param of self::playerLevelAdjust().
	 * @param array $upgradeSched The return of self::getScheduleDateRange().
	 * @param boolean $setHourly The return of self::_macroUpgradeSettingReturnFlagsSetting().
	 * @param boolean $isBatchUpgrade The $isBatchUpgrade from param of self::playerLevelAdjust().
	 * @return array The array format,
	 * - $return['fromDatetime'] string The command begin time for count amounts .
	 * - $return['toDatetime'] string The command end time for count amounts .
	 * - $return['previousFromDatetime'] string The Previous Period begin time for count amounts .
	 * - $return['previousToDatetime'] string The Previous Period end time for count amounts .
	 */
	public function _macroPreviousMomentsByAccumulation( $isAccumulation // # 1
														, $player // # 2
														, $setting // # 3
														, $schedule // # 4
														, $playerLevel // # 5
														, $time_exec_begin // # 6
														, $upgradeSched // # 7
														, $setHourly // # 8
														, $isBatchUpgrade // # 9
														// , $isMetOffsetRules // # 10 /// @todo OGP-19825 不一定是這邊，要撈數據的期間
	){
		$return = [];

		$now = new DateTime($time_exec_begin); // OGP-19332 Patch for miss the param,"$now".

		$playerId = $player->playerId;

		$this->utils->debug_log('OGP-22219.3202.isAccumulation',$isAccumulation);
		if ($isAccumulation) {
            //from player register date to now
            $register_date = new DateTime($player->createdOn);

            $fromDatetime = $register_date->format('Y-m-d H:i:s');
            $toDatetime = $now->format('Y-m-d H:i:s');
            $previousFromDatetime = $fromDatetime;
			$previousToDatetime = $toDatetime;
			$accumulationMode = Group_level::ACCUMULATION_MODE_DISABLE;

			if( ! empty($setting) ){
				/// The condition for the bottom level downgrade
				// Patch for,
				// - A PHP Error was encountered | Severity: Notice | Message:  Undefined index: dateFrom
				// - A PHP Error was encountered | Severity: Notice | Message:  Undefined index: dateTo

				$accumulationMode = (int)$setting['accumulation'];
				$playerCreatedOn = $register_date->format('Y-m-d H:i:s');
				$time_exec_begin = $toDatetime;
				$duringDatetime = $this->getDuringDatetimeFromAccumulationByPlayer($accumulationMode // # 1
																				, $upgradeSched // # 2
																				, $playerId // # 3
																				, $playerCreatedOn // # 4
																				, $time_exec_begin // # 5
																				, $schedule // #6
																				// ignore params,#7~#9 because isAccumulation = true ( $setting['accumulation'] > 0 )
																			);
$this->utils->debug_log('OGP-21051.3266.getDuringDatetimeFromAccumulationByPlayer.params:', $accumulationMode // # 1
, $upgradeSched // # 2
, $playerId // # 3
, $playerCreatedOn // # 4
, $time_exec_begin
);
$this->utils->debug_log('OGP-21051.3266.duringDatetime:', $duringDatetime);
				$fromDatetime = $duringDatetime['from'];
				$toDatetime = $duringDatetime['to'];
				$previousFromDatetime = $duringDatetime['previousFrom'];
				$previousToDatetime = $duringDatetime['previousTo'];
			}
        } else { // non-Accumulation
            $fromDatetime = $upgradeSched['dateFrom']; // OGP-19825 A PHP Error was encountered | Severity: Notice | Message: Undefined index: dateFrom | Filename: models/group_level.php:3125
            $toDatetime = $upgradeSched['dateTo']; // A PHP Error was encountered | Severity: Notice | Message: Undefined index: dateTo | Filename: models/group_level.php:3126
            if($setHourly === true) {
                $toDatetime = $now->format('Y-m-d H:i:s');
            }
            $periodType = $upgradeSched['periodType']; // A PHP Error was encountered | Severity: Notice | Message: Undefined index: periodType | Filename: models/group_level.php:3130
			$subNumber = 1;
			$previousSched = $this->getScheduleDateRange($schedule, $subNumber, $isBatchUpgrade, false, $time_exec_begin); // Get Previous, so $subNumber=1.

			$previousFromDatetime = $previousSched['dateFrom']; // A PHP Error was encountered | Severity: Notice | Message: Undefined index: dateFrom | Filename: models/group_level.php:3133
            $previousToDatetime = $previousSched['dateTo']; // A PHP Error was encountered | Severity: Notice | Message: Undefined index: dateTo | Filename: models/group_level.php:3134
		}

		$return['fromDatetime'] = $fromDatetime;
		$return['toDatetime'] = $toDatetime;
		$return['previousFromDatetime'] = $previousFromDatetime;
		$return['previousToDatetime'] = $previousToDatetime;
		return $return;
	} // EOF _macroPreviousMomentsByAccumulation

	/**
	 * Detect The Player data, VIP level and etc... to convert to the flags and the settings for return.
	 *
	 * @param object $player The return from player_model::getPlayerById().
	 * @param array $levelMap The all Player Levels and key-value array. The format,
	 * - $levelMap[vipsettingcashbackruleId] = the row of the data table,"vipsettingcashbackrule".
	 * @param string $time_exec_begin The specified start time.
	 * @return array The format,
	 * - $return['isUseOffsetUpGradeRulesAfterDownGrade'] boolean The setting,"isUseOffsetUpGradeRulesAfterDownGrade" of configure.
	 * - $return['isMetOffsetRules'] array The result for trigger OffsetRules
	 */
	public function _macroIsMetOffsetRulesV2( $player, $levelMap, $time_exec_begin ){
		$return = [];

		$playerId = $player->playerId;
		$playerLevel = $levelMap[$player->levelId];
		$isUseOffsetUpGradeRulesAfterDownGrade = $this->utils->getConfig('isUseOffsetUpGradeRulesAfterDownGrade');

		$isMetOffsetRules = [];
		$isMetOffsetRules['bool'] = false;
		$isMetOffsetRules['cuz'] = null;
		if( !empty($isUseOffsetUpGradeRulesAfterDownGrade ) ){
			$playerCreatedOn = $player->createdOn;
			$isMetOffsetRules['bool'] = $this->isDownOrSpecificGradeFromLastGradeRecord($playerId, $playerCreatedOn, $time_exec_begin);
			$isMetOffsetRules['cuz'] = __LINE__. ' : !empty($isDownOrSpecificGradeFromLastGradeRecord )';
		}else{
			$isMetOffsetRules['bool'] = false;
			$isMetOffsetRules['cuz'] = __LINE__. ' : empty($isUseOffsetUpGradeRulesAfterDownGrade )';
		}
		if($isMetOffsetRules['bool']){
			// 当客户未出现降级时（包含 V1 跟 手動調整級 ），初始状态（未出现降级和调级）起算投注=0，起算存款=0，起算时间=注册时间
			$vip = $playerLevel;
			$downgradedVipLevelId = $this->getVipLevelIdByLevelNumber($vip['vipSettingId'], $vip['vipLevel'] - 1);
			// $downgradedVipLevelId is the feild, "vipsettingcashbackrule.vipsettingcashbackruleId".
			if(empty($downgradedVipLevelId)){ // confirm the currect level eq. 1st ?
				$isMetOffsetRules['bool'] = false;
				$isMetOffsetRules['cuz'] = __LINE__. ' : empty($downgradedVipLevelId )';
			}else{
				$downgradedVipLevel = $levelMap[$downgradedVipLevelId];
				if( empty($downgradedVipLevel['vip_upgrade_id']) ){// confirm the pre-level upgrade setting is empty ?
					$isMetOffsetRules['bool'] = false;
					$isMetOffsetRules['cuz'] = __LINE__. ' : ! empty($downgradedVipLevelId )';
				}
			}
		}
		$return['isUseOffsetUpGradeRulesAfterDownGrade'] = $isUseOffsetUpGradeRulesAfterDownGrade;
		$return['isMetOffsetRules'] = $isMetOffsetRules;
		return $return;
	}// EOF _macroIsMetOffsetRulesV2

	/**
	 * Detect The Player data and VIP level and convert to the flags and the settings for return.
	 *
	 * @param object $player The return from player_model::getPlayerById().
	 * @param array $levelMap The all Player Levels and key-value array. The format,
	 * - $levelMap[vipsettingcashbackruleId] = the row of the data table,"vipsettingcashbackrule".
	 * @return array The flags and settings.
	 */
	public function _macroUpgradeSettingReturnFlagsSetting($player, $levelMap, $isForDowngrade = false){
		// defaults
		$isAccumulation = false; // for common and have accumulation
		$isSeparateAccumulation = false; // separate_accumulation_settings for bet_amount, deposit_amount, win_amount and loss_amount.
		$isBetAmountSettings = false; // if true, will use bet_amount of Formula.
		$setHourly = false;

		$bet_amount_settingsStr = 'null';
		$playerVIP = ''; // The player VIP Level Title
		$setting = [];
		$playerLevel = []; // The player level details
		$schedule = []; // group Level Upgrade Period Setting
		$return = []; // merge the above params.

		$playerLevel = $levelMap[$player->levelId]; // get player level details
		$playerLevelDetails = $this->getVipGroupLevelDetails($player->levelId);
		$playerLevel = array_merge($playerLevel, $playerLevelDetails);
		$playerVIP = lang($playerLevel['groupName']) . ' - ' . lang($playerLevel['vipLevelName']);

		if($isForDowngrade){
			// for Down grade.
			$schedule = json_decode($playerLevel['period_down'], true); // vipsettingcashbackrule.period_down
		}else{
			// for Up grade.
			$schedule = json_decode($playerLevel['period_up_down_2'], true); // vipsettingcashbackrule.period_up_down_2
		}

		if(!$isForDowngrade){ // upgrade only
			$setHourly = isset($schedule['hourly']) ? $schedule['hourly'] : false;
			if($setHourly !== true){
				$setHourly = false;
			}
		}

		$setting = []; // default
		if($isForDowngrade){
			// for downgrade
			if(!empty($playerLevel['vip_downgrade_id'])) {
				$setting = $this->getSettingData($playerLevel['vip_downgrade_id']); // vip_upgrade_setting
			}
		}else{
			// for upgrade
			if (!empty($playerLevel['vip_upgrade_id']) ) {
				$setting = $this->getSettingData($playerLevel['vip_upgrade_id']); // vip_upgrade_setting
			}
		}

		$separate_accumulation_settings = null;
		$isCommonAccumulationModeFromRegistration = null;
		if (!empty($setting)) {
			if ((int)$setting['accumulation'] > Group_level::ACCUMULATION_MODE_DISABLE ) {
				$isAccumulation = true; // common and have accumulation
			}

			// separate accumulation
			$separate_accumulation_settings = [];
			if ( ! empty($setting['separate_accumulation_settings']) ){
				$separate_accumulation_settings = json_decode($setting['separate_accumulation_settings'], true);
			}
			if ( ! empty($separate_accumulation_settings) ){
				$isSeparateAccumulation = true;
			}

			// $bet_amount_settingsStr = 'null';
			if( ! empty($setting['bet_amount_settings']) ){
				$isBetAmountSettings = true;
				$bet_amount_settingsStr = $setting['bet_amount_settings'];
			}

			if( $isAccumulation // Common Accumulation
				&& $setting['accumulation'] == Group_level::ACCUMULATION_MODE_FROM_REGISTRATION
			){
				$isCommonAccumulationModeFromRegistration = true;
			}else{
				$isCommonAccumulationModeFromRegistration = false;
			}

		}// EOF if (!empty($setting)) {...

		$return['isAccumulation'] = $isAccumulation;
		$return['isCommonAccumulationModeFromRegistration'] = $isCommonAccumulationModeFromRegistration;
		$return['isSeparateAccumulation'] = $isSeparateAccumulation;
		$return['separate_accumulation_settings'] = $separate_accumulation_settings;
		if( ! empty($setting['accumulation']) ){
			$return['accumulation_in_setting'] = $setting['accumulation'];
		}else{
			$return['accumulation_in_setting'] = Group_level::ACCUMULATION_MODE_DISABLE; // as default
		}

		$return['isBetAmountSettings'] = $isBetAmountSettings;
		$return['setting'] = $setting;
		$return['playerVIP'] = $playerVIP;
		$return['playerLevel'] = $playerLevel; // vipsettingcashbackrule
		$return['schedule'] = $schedule;
		$return['setHourly'] = $setHourly; // $schedule['hourly'] for upgrade only
		$return['bet_amount_settingsStr'] = $bet_amount_settingsStr;
		return $return;
	} // EOF _macroUpgradeSettingReturnFlagsSetting

	/**
	 *
	 *
	 * @param [type] $playerId
	 * @param [type] $levelMap
	 * @param [type] $isBatchUpgrade
	 * @param boolean $checkHourly If true means trigger from cronjob, Command::batch_player_level_upgrade_check_hourly()
	 * @param [type] $time_exec_begin
	 * @param [type] $order_generated_by
	 * @return void
	 */
	public function playerLevelAdjust($playerId, $levelMap, $isBatchUpgrade, $checkHourly=false, $time_exec_begin = null, $order_generated_by = null) {
		$this->load->library(array('authentication', 'player_notification_library', 'group_level_lib'));
		$this->load->model(array('total_player_game_day', 'player_model', 'transactions', 'player_level_adjustment_history'));
// OGP-19825 ====
		$_pgrm_start_time = date('Y-m-d H:i:s');
		$force_pgrm_start_time_delay_by_request_time_sec = $this->utils->getConfig('force_pgrm_start_time_delay_by_request_time_sec');
		if($force_pgrm_start_time_delay_by_request_time_sec !== false){
			$_pgrm_start_time = $this->group_level_lib->get_pgrm_time_by_request_time($time_exec_begin, $force_pgrm_start_time_delay_by_request_time_sec);
		}

		$this->setGradeRecord(['pgrm_start_time' => $_pgrm_start_time]);

		// for log into player_level_adjustment_history
		$this->PLAH['request_grade'] = $this->grade_record['request_grade'];
		$this->PLAH['request_time'] = $this->grade_record['request_time'];
		$this->PLAH['request_type'] = $this->grade_record['request_type'];

		// OGP-25082
		$disable_player_multiple_upgrade = $this->utils->isEnabledFeature('disable_player_multiple_upgrade');
		if( ! $disable_player_multiple_upgrade ){
			$this->PLAH['multiple_upgrade_buff'] = []; // initial for this player
			$this->PLAH['multiple_upgrade_buff']['time_exec_begin'] = $time_exec_begin;
			$this->PLAH['multiple_upgrade_buff']['pgrm_start_time'] = $this->grade_record['pgrm_start_time'];
			// begin datetime, time_exec_begin, pgrm_start_time

			$this->PLAH['multiple_upgrade_buff']['upgraded_list']= []; // clear for this player
			$updatedRemark = $this->rewriteRemarkInGrade_record( function( $_remark ) {
				$returnData = false; // No update as default
				$_remark['upgraded_list'] = []; // clear for this player
				$returnData = $_remark; // update into this->grade_record.remark
				return $returnData;
			});
		}

		// defaults
		// display count of player who upgrade and downgrade
		$upgradeCount = 0;
		$isPlayerUpgrade = false;

		$result = array();

		$player = $this->player_model->getPlayerById($playerId);

		// $setHourly = false; // moved to self::_macroUpgradeSettingReturnFlagsSetting()

		$now = new DateTime($time_exec_begin); // OGP-19332 Patch for miss the param,"$now".
		// make sure vip upgrade exist
        if (!array_key_exists($player->levelId, $levelMap)){
            $result = array('error' => 'VIP Level is not exist');
            $this->utils->debug_log('upgrade VIP Level is not exist', $playerId);

            return $result;
		}

		$macroReturn = $this->_macroUpgradeSettingReturnFlagsSetting( $player, $levelMap );
		$playerLevel = $macroReturn['playerLevel'];
		/// moved to self::_macroUpgradeSettingReturnFlagsSetting()
        // $playerLevel = $levelMap[$player->levelId]; // get player level details
// $this->utils->debug_log('OGP-19825.playerLevel', $playerLevel);
        $playerVIP = lang($playerLevel['groupName']) . ' - ' . lang($playerLevel['vipLevelName']);


		$playerLevel['insertBy'] = 3429;
		$playerLevel['groupLevelName'] = lang($playerLevel['groupName']) . ' - ' . lang($playerLevel['vipLevelName']);
		$playerLevel['vip_upgrade_setting'] = null;
		if( !empty($macroReturn['setting']) ){
			$playerLevel['vip_upgrade_setting'] = $macroReturn['setting'];// vip_upgrade_setting
		}
		$this->PLAH['playerLevelInfo'] = [];
		$this->PLAH['playerLevelInfo']['playerLevelData'] = $playerLevel; // the current level for popup
		$this->PLAH['playerLevelInfo']['newVipLevelDataList'] = []; // default for popup

		// 這個是當下處理的升級等級的結果。 ( for No upgrade settings)
		// $player = $this->player_model->getPlayerById($playerId); // reload player detail
		// $_macroReturn = $this->_macroUpgradeSettingReturnFlagsSetting( $player, $levelMap );
		// $playerLevel = array_merge($playerLevel, $_macroReturn['playerLevel']); // reload
		// $playerLevel = array_merge($playerLevel, ['groupLevelName' => lang($playerLevel['groupName']). ' - ' .lang($playerLevel['vipLevelName'])]);
		// $playerLevel['vip_upgrade_setting'] = $_macroReturn['setting'];
		$playerLevel['insertBy'] = 3446;
		$playerLevel['isConditionMet'] = null;
		$playerLevel['remark'] = [];
		if(true){
			$theOffsetAmountOfNextUpgrade = 1;
			$resultAfterDryCheckUpgrade = $this->dryCheckUpgrade($playerId, $levelMap, $isBatchUpgrade, $checkHourly, $time_exec_begin, $theOffsetAmountOfNextUpgrade);
			$playerLevel['nextVipLevelData'] = $resultAfterDryCheckUpgrade;
		}
		$this->PLAH['playerLevelInfo']['afterPlayerLevelData'] = $playerLevel; // the current level for popup

		if (empty($playerLevel['period_up_down_2'])){
            $result = array('error' => 'Cannot upgrade. No schedule is set');
            $this->utils->debug_log('Cannot upgrade, No schedule is set', $playerId);

            return $result;
		}


		$isAccumulation = $macroReturn['isAccumulation'];
		$isCommonAccumulationModeFromRegistration = $macroReturn['isCommonAccumulationModeFromRegistration']; // from _macroUpgradeSettingReturnFlagsSetting()
		$isSeparateAccumulation = $macroReturn['isSeparateAccumulation'];
		$isBetAmountSettings = $macroReturn['isBetAmountSettings'];
		$schedule = $macroReturn['schedule'];
		$setHourly = $macroReturn['setHourly']; // $schedule['hourly'] for upgrade only
		$setting = $macroReturn['setting'];
		$bet_amount_settingsStr = $macroReturn['bet_amount_settingsStr'];
		$separate_accumulation_settings = $macroReturn['separate_accumulation_settings'];
		$accumulation_setting = $macroReturn['accumulation_in_setting'];
		/// moved to self::_macroUpgradeSettingReturnFlagsSetting()

		$isMetScheduleDate = $this->isMetScheduleDate($schedule, $checkHourly, $isBatchUpgrade, $time_exec_begin);
		$upgradeSched = $this->getScheduleDateRange($schedule, 1, $isBatchUpgrade, $setHourly, $time_exec_begin);
		// $periodType = $upgradeSched['periodType'];// for debug_log

		$isValidUpgradeSchedDates = null;
        $this->utils->debug_log('---- upgrade schedule ---- params: '
			, 'playerId:' ,$playerId
			, 'player.levelId:', $player->levelId
			, 'isBatchUpgrade:' ,$isBatchUpgrade
			, 'setHourly:' ,$setHourly
			, 'upgradeSched:' ,$upgradeSched
			, 'schedule:' ,$schedule
			, 'isMetScheduleDate:' , $isMetScheduleDate
			, 'checkHourly:', $checkHourly
		);
        if (empty($upgradeSched['dateFrom']) || empty($upgradeSched['dateTo'])) {
			$isValidUpgradeSchedDates = false;
        }else{
			// for $upgradeSched['periodType'] : weekly
			$isValidUpgradeSchedDates = $this->doValidDateFromSmallerThenDateTo($upgradeSched['dateFrom'], $upgradeSched['dateTo']);

			if(!$isBatchUpgrade) { // from SBE trigger
				$isValidUpgradeSchedDates = true;
			}
		}
		if( empty($isValidUpgradeSchedDates) ){
			$reason = 'Check schedule. Player upgrade still processing !';
			$result = array('error' => $reason);
            $this->utils->debug_log('3473.'. $reason, $playerId);
			$this->utils->debug_log('OGP-21051.3473.handle dateFrom and dateTo is empty. playerId:', $playerId, 'upgradeSched:', $upgradeSched);
			/// OGP-21051.handle $upgradeSched[dateFrom] and $upgradeSched[dateTo] is empty.
			// The date should Not todo downgrade/upgrade.
            return $result;
		}

		if( empty($isMetScheduleDate) ){
			$reason = 'Check schedule. empty($isMetScheduleDate) !';
			$result = array('error' => $reason);
            $this->utils->debug_log('3569.'. $reason, $playerId);
			/// OGP-21051.handle $upgradeSched[dateFrom] and $upgradeSched[dateTo] is empty.
			// The date should Not todo downgrade/upgrade.
            return $result;
		}

		$macroReturn = $this->_macroIsMetOffsetRulesV2( $player, $levelMap, $time_exec_begin );
		$isUseOffsetUpGradeRulesAfterDownGrade = $macroReturn['isUseOffsetUpGradeRulesAfterDownGrade'];
		$isMetOffsetRules = $macroReturn['isMetOffsetRules'];
		/// moved to self::_macroIsMetOffsetRules()

		$macroReturn = $this->_macroPreviousMomentsByAccumulation( $isAccumulation // # 1
			, $player // # 2
			, $setting // # 3
			, $schedule // # 4
			, $playerLevel // # 5
			, $time_exec_begin // # 6
			, $upgradeSched // # 7
			, $setHourly // # 8
			, $isBatchUpgrade // # 9
			// , $isMetOffsetRules // # 10
		);
		$fromDatetime = $macroReturn['fromDatetime'];
		$toDatetime = $macroReturn['toDatetime'];
		$previousFromDatetime = $macroReturn['previousFromDatetime'];
		$previousToDatetime = $macroReturn['previousToDatetime'];
		/// moved to self::_macroPreviousMomentsByAccumulation()

// $this->utils->debug_log('OGP-19332.isBetAmountSettings', $isBetAmountSettings);
// if(isset($bet_amount_settings) ){
// $this->utils->debug_log('OGP-19332.bet_amount_settings', $bet_amount_settings);
// }
$this->utils->debug_log('OGP-19825.isMetOffsetRules', $isMetOffsetRules);

		$initialAmount = $this->_macroGetInitialAmountV2( $isMetOffsetRules // # 1
										, $player // # 2
										, $setting // # 3
										, $levelMap // # 4
										, $time_exec_begin // # 5
								); /// The orig moved to self::_macroGetInitialAmount()

		$player_basic_amount_enable_in_upgrade = $this->utils->getConfig('player_basic_amount_enable_in_upgrade');
		$is_enabled_player_basic_amount_list_data_table = $this->utils->getConfig('player_basic_amount_list_data_table');
		$this->utils->debug_log('OGP23800.3967initialAmount', $initialAmount, 'playerId:', $player->playerId
			, 'player_basic_amount_enable_in_upgrade:', $player_basic_amount_enable_in_upgrade
			, 'isCommonAccumulationModeFromRegistration:', $isCommonAccumulationModeFromRegistration
			, 'is_enabled_player_basic_amount_list_data_table:', $is_enabled_player_basic_amount_list_data_table );

		if( ! empty($player_basic_amount_enable_in_upgrade)
			&& ! empty($isCommonAccumulationModeFromRegistration)
		){
			$theBasicAmountDetail = $this->getBasicAmountAfterImported($player->username);
			if( !empty($theBasicAmountDetail['total_bet_amount']) ){
				$initialAmount['total_bet'] += $theBasicAmountDetail['total_bet_amount'];
			}
			if( !empty($theBasicAmountDetail['total_deposit_amount']) ){
				$initialAmount['deposit'] += $theBasicAmountDetail['total_deposit_amount'];
			}
			$this->utils->debug_log('OGP23800.3987.after.basic_amount.initialAmount:', $initialAmount, 'setting:', $setting, 'playerId:', $player->playerId);
		} // EOF if( ! empty($player_basic_amount_enable_in_upgrade) ){...

		$playerId = $player->playerId;
		$this->utils->debug_log('-------- upgrade get deposit amount from to ------', $playerId, $fromDatetime, $toDatetime);

		// bplu = batch_player_level_upgrade
		$bplu_options = $this->config->item('batch_player_level_upgrade');
		if( ! empty($bplu_options['BLAidleSec']) ){
			// BLA = playerLevelAdjust
			$BLAidleSec = $bplu_options['BLAidleSec']; // 60 sec for test
			if( ! empty($BLAidleSec) ){
				$this->utils->debug_log('will BLAidleSec', $BLAidleSec);
				$this->utils->idleSec($BLAidleSec);
			}
		}
		$macroReturn = $this->_macroGetGameLogDataAndDeposit($isSeparateAccumulation
			, $setting
			, $player
			, $time_exec_begin
			, $playerLevel
			, $isBatchUpgrade
			, $upgradeSched
			, $schedule
			, $setHourly
			, $bet_amount_settingsStr
			, $initialAmount
			, $fromDatetime
			, $toDatetime
			, $isMetOffsetRules
		);
		$this->utils->debug_log('OGP-19825.3560.macroReturn:', $macroReturn);
		$gameLogData = $macroReturn['gameLogData'];
		$deposit = $macroReturn['deposit'];
		$calcResult = $macroReturn['calcResult'];

        if (empty($gameLogData) && empty($deposit)) {
            $result = array('error' => 'No player deposit or bet data');
            $this->utils->debug_log('upgrade No player deposit or bet data', $playerId);

            return $result;
		}

        $vipSettingId = $playerLevel['vip_upgrade_id'];

        if (empty($vipSettingId)) {
            $result = array('error' => $playerVIP . ' upgrade setting is not set');
            $this->utils->debug_log('upgrade setting is not set', $playerId, $playerVIP);

            return $result;
        }

        $setting = $this->getSettingData($vipSettingId);

        if (empty($setting)) {
            $result = array('error' => $playerVIP . ' upgrade setting detail is not set');
            $this->utils->debug_log('upgrade setting detail is not set', $playerId, $playerVIP);

            return $result;

        }

        // VIP Multiple Upgrade
        $this->utils->debug_log('================playerLevelAdjust checkHourly flag', $checkHourly);
        $vips = $this->getAllVIPLevels($playerLevel['vipSettingId'], $playerLevel['vipLevel'], $checkHourly);
        $this->utils->debug_log('================playerLevelAdjust getAllVIPLevels as array', $vips);

        if(is_null($vips)) {
			$result = array('error' => 'Cannot get vips with parameters. vipSettingId: '.$playerLevel['vipSettingId']. ', vipLevel: '.$playerLevel['vipLevel'].', checkHourly:'. $checkHourly);
			$this->utils->debug_log('Cannot get vips with parameters. ', 'vipSettingId: '.$playerLevel['vipSettingId'], 'vipLevel: '.$playerLevel['vipLevel'], 'checkHourly:'. $checkHourly);

			return $result;
        }

		$disable_player_multiple_upgrade = $this->utils->isEnabledFeature('disable_player_multiple_upgrade');

        foreach ($vips as $vip) {
            if ($disable_player_multiple_upgrade) {
                if ($upgradeCount >= 1) {
                    break;
                }
			}
			if ($upgradeCount >= 1) { // need to reload

				$player = $this->player_model->getPlayerById($playerId); // reload player detail
				$macroReturn = $this->_macroUpgradeSettingReturnFlagsSetting( $player, $levelMap );
				$playerLevel = $macroReturn['playerLevel'];
				$isAccumulation = $macroReturn['isAccumulation'];
				$isCommonAccumulationModeFromRegistration = $macroReturn['isCommonAccumulationModeFromRegistration']; // from _macroUpgradeSettingReturnFlagsSetting()
				$isSeparateAccumulation = $macroReturn['isSeparateAccumulation'];
				$isBetAmountSettings = $macroReturn['isBetAmountSettings'];
				$schedule = $macroReturn['schedule'];
				$setHourly = $macroReturn['setHourly'];
				$setting = $macroReturn['setting'];
				$bet_amount_settingsStr = $macroReturn['bet_amount_settingsStr'];
				$separate_accumulation_settings = $macroReturn['separate_accumulation_settings'];
				$accumulation_setting = $macroReturn['accumulation_in_setting'];

				$macroReturn = $this->_macroIsMetOffsetRulesV2( $player, $levelMap, $time_exec_begin );
				$isUseOffsetUpGradeRulesAfterDownGrade = $macroReturn['isUseOffsetUpGradeRulesAfterDownGrade'];
				$isMetOffsetRules = $macroReturn['isMetOffsetRules'];
				$isMetScheduleDate = $this->isMetScheduleDate($schedule, $checkHourly, $isBatchUpgrade, $time_exec_begin);
				$upgradeSched = $this->getScheduleDateRange($schedule, 1, $isBatchUpgrade, $setHourly, $time_exec_begin);

				$this->utils->debug_log('---- upgrade schedule ---- in foreach ($vips as $vip) params: playerId', $playerId
						, 'isBatchUpgrade:', $isBatchUpgrade
						, 'setHourly:', $setHourly
						, 'upgradeSched:', $upgradeSched
						, 'isMetScheduleDate:', $isMetScheduleDate);
				if ( empty($upgradeSched['dateFrom']) || empty($upgradeSched['dateTo'])
					|| empty($isMetScheduleDate)
				) {
					$reason = '';
					if ( empty($upgradeSched['dateFrom']) || empty($upgradeSched['dateTo']) ){
						$reason = 'Check schedule. Player upgrade still processing!';
						$result = array('error' => $reason);
						$this->utils->debug_log('3623 '. $reason, $playerId);
					}

					if ( empty($isMetScheduleDate) ){
						$reason = 'Check schedule. empty($isMetScheduleDate)!';
						$result = array('error' => $reason);
						$this->utils->debug_log('3711 '. $reason, $playerId);
					}

					// upgrade to the top level(same as No setup upgrade settings)
					$playerLevel['insertBy'] = 3625;
					$playerLevel['isConditionMet'] = false;
					$playerLevel['reason'] = $reason;
					$theOffsetAmountOfNextUpgrade = 1;
					$playerLevel['nextVipLevelData'] = $this->dryCheckUpgrade($playerId, $levelMap, $isBatchUpgrade, $checkHourly, $time_exec_begin, $theOffsetAmountOfNextUpgrade);
					$newVipLevelDataListcounter = count($this->PLAH['playerLevelInfo']['newVipLevelDataList']);
					$this->PLAH['playerLevelInfo']['newVipLevelDataList'][$newVipLevelDataListcounter] = $playerLevel;

					return $result;
				}

				$macroReturn = $this->_macroPreviousMomentsByAccumulation( $isAccumulation // # 1
					, $player // # 2
					, $setting // # 3
					, $schedule // # 4
					, $playerLevel // # 5
					, $time_exec_begin // # 6
					, $upgradeSched // # 7
					, $setHourly // # 8
					, $isBatchUpgrade // # 9
				);
				$fromDatetime = $macroReturn['fromDatetime'];
				$toDatetime = $macroReturn['toDatetime'];
				$previousFromDatetime = $macroReturn['previousFromDatetime'];
				$previousToDatetime = $macroReturn['previousToDatetime'];

				$initialAmount = $this->_macroGetInitialAmountV2( $isMetOffsetRules // # 1
										, $player // # 2
										, $setting // # 3
										, $levelMap // # 4
										, $time_exec_begin // # 5
								);

				$player_basic_amount_enable_in_upgrade = $this->utils->getConfig('player_basic_amount_enable_in_upgrade');
				if( ! empty($player_basic_amount_enable_in_upgrade)
					&& ! empty($isCommonAccumulationModeFromRegistration)
				){
					$theBasicAmountDetail = $this->getBasicAmountAfterImported($player->username);
					$this->utils->debug_log('OGP23800.4143.after.basic_amount.theBasicAmountDetail:', $theBasicAmountDetail, 'playerId:', $player->playerId, 'username:', $player->username );

					if( !empty($theBasicAmountDetail['total_bet_amount']) ){
						$initialAmount['total_bet'] += $theBasicAmountDetail['total_bet_amount'];
					}
					if( !empty($theBasicAmountDetail['total_deposit_amount']) ){
						$initialAmount['deposit'] += $theBasicAmountDetail['total_deposit_amount'];
					}
					$this->utils->debug_log('OGP23800.4143.after.basic_amount.initialAmount:', $initialAmount, 'setting:', $setting, 'playerId:', $player->playerId );
				} // EOF if( ! empty($player_basic_amount_enable_in_upgrade) ){...

				if( ! empty($bplu_options['BLAidleSec']) ){
					/// BLA = playerLevelAdjust
					$BLAidleSec = $bplu_options['BLAidleSec']; // 60 sec for test
					if( ! empty($BLAidleSec) ){
						$this->utils->debug_log('will BLAidleSec', $BLAidleSec);
						$this->utils->idleSec($BLAidleSec);
					}
				}
				$macroReturn = $this->_macroGetGameLogDataAndDeposit( $isSeparateAccumulation
					, $setting
					, $player
					, $time_exec_begin
					, $playerLevel
					, $isBatchUpgrade
					, $upgradeSched
					, $schedule
					, $setHourly
					, $bet_amount_settingsStr
					, $initialAmount
					, $fromDatetime
					, $toDatetime
					, $isMetOffsetRules
				);
				$gameLogData = $macroReturn['gameLogData'];
				$deposit = $macroReturn['deposit'];
				$calcResult = $macroReturn['calcResult'];

				$this->utils->debug_log('OGP-19825.3814.macroReturn:', $macroReturn);
			} // EOF if ($upgradeCount >= 1) {...
				$this->utils->debug_log('OGP-24373.4276.macroReturn:', $macroReturn);

			$_path = 'details.total_bet.enforcedDetails.details.curr_condition_details';
			$extracted_result = $this->group_level_lib->extractCurrConditionDetails( $calcResult, $_path );
			if($extracted_result['bool']){
				$curr_total_bet = $extracted_result['extracted'];
				// $fromDatetime = $curr_total_bet['fromDatetime'];
				// $toDatetime = $curr_total_bet['toDatetime'];
				if( isset($curr_total_bet['gameLogData']['total_bet']) ){
					$gameLogData['total_bet'] = $curr_total_bet['gameLogData']['total_bet'];
				}
			}

			$_path = 'details.deposit.enforcedDetails.details.curr_condition_details';
			$extracted_result = $this->group_level_lib->extractCurrConditionDetails( $calcResult, $_path );
			if($extracted_result['bool']){
				$curr_deposit = $extracted_result['extracted'];
				if( isset($curr_deposit['deposit']) ){
					$deposit = $curr_deposit['deposit'];
				}
			}

			$bet_amount_settings  = [];
			if(!empty($vip['bet_amount_settings'])){
				$bet_amount_settings  = json_decode($vip['bet_amount_settings'], true);
			}

            $formula = json_decode($vip['formula'], true);
            $this->utils->debug_log('-------- upgrade generateUpDownFormula-------', $playerId, $formula, $gameLogData['total_bet'], $deposit, $gameLogData['total_loss'], $gameLogData['total_win']);

            $execCheckConditionMet = true;
			// executed form cronjob, batch_player_level_upgrade_check_hourly
            if($checkHourly === true) {
                $period_up_down_2 = json_decode($vip['period_up_down_2'], true);
                if(!isset($period_up_down_2['hourly'])) {
                    $execCheckConditionMet = false;
                }
                else if($period_up_down_2['hourly'] != true) {
                    $execCheckConditionMet = false;
                }
			}
			$this->utils->debug_log('OGP23800.4171.gameLogData:',$gameLogData, 'playerId:', $player->playerId);

			if( !empty($gameLogData['separated_bet']) ){
				$gameLogData4bet = $gameLogData['separated_bet'];
			} else {
				$gameLogData4bet = $gameLogData['total_bet'];
			}

			// OGP-24373 $formula consider the enforcedDetails
			$betEnforcedDetails = [];
			$depositEnforcedDetails = [];
			if( ! empty($macroReturn['calcResult']['details'])){
				$_macroReturn_calcResult_details = $macroReturn['calcResult']['details'];
				if( ! empty($_macroReturn_calcResult_details['total_bet']['enforcedDetails']) ){
					$betEnforcedDetails = $_macroReturn_calcResult_details['total_bet']['enforcedDetails'];
				}
				if( ! empty($_macroReturn_calcResult_details['deposit']['enforcedDetails']) ){
					$depositEnforcedDetails = $_macroReturn_calcResult_details['deposit']['enforcedDetails'];
				}
			}

			$settingInfoHarshInUpGraded = []; // defaults
			$settingInfoHarshInUpGraded['debugInsertBy'] = 4339;
			$settingInfoHarshInUpGraded['total_deposit'] = false;
			$settingInfoHarshInUpGraded['total_bet'] = false;
			if($isSeparateAccumulation) {
				// separate accumulation
				// {"bet_amount": {"accumulation": "5"}, "deposit_amount": {"accumulation": "5"}}
				if( ! empty($separate_accumulation_settings['bet_amount']['accumulation']) ){
					if( $separate_accumulation_settings['bet_amount']['accumulation'] == Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET ){
						$settingInfoHarshInUpGraded['total_bet'] = true;
					}
				}

				if( ! empty($separate_accumulation_settings['deposit_amount']['accumulation']) ){
					if( $separate_accumulation_settings['deposit_amount']['accumulation'] == Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET ){
						$settingInfoHarshInUpGraded['total_deposit'] = true;
					}
				}
			}else{
				// common accumulation
				if( $accumulation_setting == Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET ){
					$settingInfoHarshInUpGraded['total_bet'] = true;
					$settingInfoHarshInUpGraded['total_deposit'] = true;
				}
			}

			$tmp = null; // it has applied false and update in remark.
			// $isConditionMet = $this->generateUpDownFormula($formula, $gameLogData['total_bet'], $deposit, $gameLogData['total_loss'], $gameLogData['total_win']);
			$isConditionMet = $this->generateUpDownFormulaWithBetAmountSetting( $formula // #1
																				, $gameLogData4bet // #2
																				, $deposit // #3
																				, $gameLogData['total_loss'] // #4
																				, $gameLogData['total_win'] // #5
																				, $bet_amount_settings  // #6
																				, false // #7, isDisableSetupRemarkToGradeRecord
																				, $tmp // #8
																				, $betEnforcedDetails // #9
																				, $depositEnforcedDetails // #10
																				, $settingInfoHarshInUpGraded /// #11
																			);

			// for initialAmount, merge into the remark of vip_grade_report
			if( ! empty($initialAmount) ){
				// $initialAmount
				$remark = [];
				if( ! empty($this->grade_record['remark']) ){
					$remark = json_decode($this->grade_record['remark'], true) ;
				}
				$remark = array_merge($remark, [ 'initialAmount' => $initialAmount]);
				$this->setGradeRecord([
					'remark' => json_encode($remark)
				]);
			}
			if( ! empty($isMetOffsetRules) ){
				$remark = [];
				if( ! empty($this->grade_record['remark']) ){
					$remark = json_decode($this->grade_record['remark'], true) ;
				}
				$remark = array_merge($remark, [ 'isMetOffsetRules' => $isMetOffsetRules]);
				$this->setGradeRecord([
					'remark' => json_encode($remark)
				]);
			}
			/// OGP-24373 the amounts, bet and deposit, that should log into the player_accumulated_amounts_log data-table.

			// $bet_daterange['from'] = $fromDatetime; // = $macroReturn['fromDatetime'];
			// $bet_daterange['to'] = $toDatetime; // $toDatetime = $macroReturn['toDatetime'];
			// $deposit_daterange['from'] = $fromDatetime; // = $macroReturn['fromDatetime'];
			// $deposit_daterange['to'] = $toDatetime; // $toDatetime = $macroReturn['toDatetime'];
			// if( ! empty($calcResult['details']['total_bet']) ){
			// 	$bet_daterange = $calcResult['details']['total_bet']; // assign from and to.
			// }else{
			// 	/// Separate Bet
			// 	// @todo Not supported by Not yet requirements
			// }
			// if( ! empty($calcResult['deposit']) ){
			// 	$deposit_daterange = $calcResult['details']['deposit']; // assign from and to.
			// }
			// $syncedInPlayerAccumulatedAmountsLog = $this->group_level_lib->syncInPlayerAccumulatedAmountsLog( $player->playerId // #1
			// 															, $gameLogData4bet // #2
			// 															, $bet_daterange // #3
			// 															, $deposit // #4
			// 															, $deposit_daterange // #5
			// 															, $time_exec_begin // #6
			// 														);

			if($isSeparateAccumulation){
				// merge remark of vip_grade_report
				$remark = [];
				if( ! empty($this->grade_record['remark']) ){
					$remark = json_decode($this->grade_record['remark'], true) ;
				}
				$separate_accumulation_settings = $setting['separate_accumulation_settings'];
				$remark = array_merge($remark, [ 'separate_accumulation_settings' => $separate_accumulation_settings]);

				// $remark = array_merge($remark, [ 'separate_accumulation_calcResult' => $calcResult]);

				$this->setGradeRecord([
					'remark' => json_encode($remark)
				]);

			}// EOF if($isSeparateAccumulation)

			/// for support parse $calcResult and display in the report.
			if( ! empty($calcResult) ){
				$_this = $this;
				$remarkStr = $this->rewriteRemarkInGrade_record(function($_remark) use ($calcResult, $playerLevel, $vip, $setting, $_this) {
					$_remark['separate_accumulation_calcResult'] = $calcResult;
					$_remark['groupLevelName'] = lang($playerLevel['groupName']). ' - '. lang($vip['vipLevelName']);
					$_remark['setting_name'] = $setting['setting_name'];
					// json string convertion to array
					if( ! empty($_this->PLAH['multiple_upgrade_buff']['upgraded_list']) ){
						// Not contains this level grade, if this check had upgraded.
						$_upgraded_list = $_this->PLAH['multiple_upgrade_buff']['upgraded_list'];
						$_remark['upgraded_list'] = $_upgraded_list;
					}
					return $_remark;
				}); // EOF $this->rewriteRemarkInGrade_record(...

				$assoc = true;
				$remarkAry = $this->utils->json_decode_handleErr($remarkStr, $assoc);
				// for ajax, /player_management/ajaxManuallyUpgradeLevel/263418
				$remark = array_merge($remarkAry, [ 'separate_accumulation_calcResult' => $calcResult ]);

			} // EOF if( ! empty($calcResult) ){...

            if($execCheckConditionMet === false) {
                $this->utils->debug_log('================directly set isConditionMet = false because this vip ip not using hourly check', $vip);
                $isConditionMet = false;
            }
            $this->utils->debug_log('upgrade isConditionMet', $playerId, $isConditionMet, '$remark:', $remark, 'calcResult:',empty($calcResult)? null: $calcResult );

			// update isConditionMet, remark,... in $playerLevel.
			$playerLevel = array_merge($playerLevel, ['isConditionMet' => $isConditionMet]);
			$playerLevel = array_merge($playerLevel, ['remark' => $remark]);
			$playerLevel = array_merge($playerLevel, ['insertBy' => 3783]);
			$playerLevel = array_merge($playerLevel, ['vip_upgrade_setting' => $this->getSettingData($playerLevel['vip_upgrade_id']) ]);
			$theOffsetAmountOfNextUpgrade = 1;
			$resultAfterDryCheckUpgrade = $this->dryCheckUpgrade($playerId, $levelMap, $isBatchUpgrade, $checkHourly, $time_exec_begin, $theOffsetAmountOfNextUpgrade);
			$playerLevel['nextVipLevelData'] = $resultAfterDryCheckUpgrade;
			// $this->PLAH['playerLevelInfo']['playerLevelData'] = $playerLevel; // ignore for keep status before upgrade.


			// append $playerLevel into the array, @.PLAH.playerLevelInfo.newVipLevelDataList[n]
			$newVipLevelDataListcounter = count($this->PLAH['playerLevelInfo']['newVipLevelDataList']);
			$this->PLAH['playerLevelInfo']['newVipLevelDataList'][$newVipLevelDataListcounter] = $playerLevel;

            //get current level promo cms id
            $VipLevel = $this->getVipLevel($vip['vipsettingcashbackruleId']);

			// Multiple upgrade
            $newVipLevelId = $this->getVipLevelIdByLevelNumber($vip['vipSettingId'], $vip['vipLevel'] + 1);
			$newVipData = (isset($levelMap[$newVipLevelId])) ? $levelMap[$newVipLevelId] : NULL;
            $updateApplyPromoData = [];

            if (empty($newVipLevelId)) {
                $result = array('error' => 'Upgrade Failed');
                $this->utils->debug_log('upgrade failed', $playerId);

                return $result;
			}

			// for log into player_level_adjustment_history
			$this->PLAH['created_at'] = date('Y-m-d H:i:s');
			$this->PLAH['player_id'] = $playerId;
			$this->PLAH['is_condition_met'] = $isConditionMet;

            $this->utils->debug_log('================playerLevelAdjust adjustPlayerLevel origin vip id', $VipLevel);
            $this->utils->debug_log('================playerLevelAdjust adjustPlayerLevel new vip id', $newVipLevelId);
            if (!$isConditionMet) {
				$resultError = 'Upgrade Condition not met';
                if ($upgradeCount <= 0) {
						$result = array('error' => $resultError);
						$this->utils->debug_log('upgrade Condition not met', $playerId);
                }

				$success_grade = $this->utils->getConfig('get_only_success_grade_report');
				if (!$success_grade) {	# set status to GRADE_FAILED if not meet upgrade condition

					$_pgrm_end_time = date('Y-m-d H:i:s');
					$force_pgrm_end_time_delay_by_request_time_sec = $this->utils->getConfig('force_pgrm_end_time_delay_by_request_time_sec');
					if($force_pgrm_end_time_delay_by_request_time_sec !== false){
						$_pgrm_end_time = $this->group_level_lib->get_pgrm_time_by_request_time($time_exec_begin, $force_pgrm_end_time_delay_by_request_time_sec);
					}

                    $this->setGradeRecord([
                        'player_id'  => $playerId,
                        'level_from' => $vip['vipLevel'],
                        'level_to'   => !empty($newVipLevelId) ? $vip['vipLevel'] + 1 : 0 ,
                        'period_start_time' => $fromDatetime,
                        'period_end_time'   => $toDatetime,
                        'newvipId'                   => $newVipLevelId,
                        'vipsettingId'               => $vip['vipSettingId'],
                        'vipupgradesettingId'        => $playerLevel['vip_upgrade_id'],
                        'vipsettingcashbackruleId'   => $playerLevel['vipsettingcashbackruleId'],
                        'vipupgradesettinginfo'      => json_encode($setting),
                        'vipsettingcashbackruleinfo' => json_encode($playerLevel),
                        'pgrm_end_time' => $_pgrm_end_time,
                        'updated_by'    => $this->authentication->getUserId(),
                        'status'        => self::GRADE_FAILED,
                        'applypromomsg' => json_encode($updateApplyPromoData)
                    ]);
					$this->gradeRecode();
				}

				// for log into player_level_adjustment_history
				$this->player_level_adjustment_history->add($this->PLAH);

				// OGP-19825 clear legacy in $this->PLAH
				unset($this->PLAH['previous_vipsettingcashbackrule']);
				unset($this->PLAH['previous_vipupgradesetting']);
				unset($this->PLAH['current_vipsettingcashbackrule']);
				unset($this->PLAH['current_vipupgradesetting']);
				unset($this->PLAH['is_met_offset_rules_info']);
				unset($this->PLAH['initial_amount']);
				// unset($this->PLAH['request_grade']);
				// unset($this->PLAH['request_time']);
				// unset($this->PLAH['request_type']);
				unset($this->PLAH['created_at']);
				unset($this->PLAH['player_id']);
				unset($this->PLAH['is_condition_met']);


				// 這個是當下處理的升級等級的結果。
				// $player = $this->player_model->getPlayerById($playerId); // reload player detail
				// $_macroReturn = $this->_macroUpgradeSettingReturnFlagsSetting( $player, $levelMap );
				// $playerLevel = array_merge($playerLevel, $_macroReturn['playerLevel']); // reload
				$playerLevel = array_merge($playerLevel, ['groupLevelName' => lang($playerLevel['groupName']). ' - ' .lang($playerLevel['vipLevelName'])]);
				// $playerLevel['vip_upgrade_setting'] = $_macroReturn['setting'];
				$playerLevel['insertBy'] = 3828;
				$playerLevel['isConditionMet'] = $isConditionMet;
				$playerLevel = array_merge($playerLevel, ['vip_upgrade_setting' => $this->getSettingData($playerLevel['vip_upgrade_id']) ]);
				$playerLevel = array_merge($playerLevel, ['remark' => $remark]);
				if(true){
					$theOffsetAmountOfNextUpgrade = 1;
					$resultAfterDryCheckUpgrade = $this->dryCheckUpgrade($playerId, $levelMap, $isBatchUpgrade, $checkHourly, $time_exec_begin, $theOffsetAmountOfNextUpgrade);
					$playerLevel['nextVipLevelData'] = $resultAfterDryCheckUpgrade;
				}
				$this->PLAH['playerLevelInfo']['afterPlayerLevelData'] = $playerLevel; // the current level for popup



                return $result;
            } // EOF if (!$isConditionMet) {...

			$rlt = $this->adjustPlayerLevel($playerId, $newVipLevelId);

			$this->utils->debug_log('OGP-20657 check the rlt', $rlt, $playerId, ' for catch legacy level .');
			if( ! empty($newVipData) ){
				$this->utils->debug_log('OGP-20657 check the newVipLevelId', $newVipLevelId, $playerId, ' for catch legacy level .');

				$this->utils->debug_log('OGP-20657 check the newVipData', $newVipData, $playerId, ' for catch legacy level .');
			}

            $upgradeCount++;
            $isPlayerUpgrade = true;
            $result = array('success' => 'Player level successfully upgrade');
            $this->utils->debug_log('upgrade Player level successfully upgrade', $playerId);

			$_pgrm_end_time = date('Y-m-d H:i:s');
			$force_pgrm_end_time_delay_by_request_time_sec = $this->utils->getConfig('force_pgrm_end_time_delay_by_request_time_sec');
			if($force_pgrm_end_time_delay_by_request_time_sec !== false){
				$_pgrm_end_time = $this->group_level_lib->get_pgrm_time_by_request_time($time_exec_begin, $force_pgrm_end_time_delay_by_request_time_sec);
			}

            $this->setGradeRecord([
                'player_id'  => $playerId,
                'level_from' => $vip['vipLevel'],
                'level_to'   => !empty($newVipLevelId) ? $vip['vipLevel'] + 1 : 0 ,
                'period_start_time' => $fromDatetime,
                'period_end_time'   => $toDatetime,
                'newvipId'                   => $newVipLevelId,
                'vipsettingId'               => $vip['vipSettingId'],
                'vipupgradesettingId'        => $playerLevel['vip_upgrade_id'],
                'vipsettingcashbackruleId'   => $playerLevel['vipsettingcashbackruleId'],
                'vipupgradesettinginfo'      => json_encode($setting),
                'vipsettingcashbackruleinfo' => json_encode($playerLevel),
                'pgrm_end_time' => $_pgrm_end_time,
                'updated_by'    => $this->authentication->getUserId(),
                'status'        => self::GRADE_SUCCESS,
                'applypromomsg' => json_encode($updateApplyPromoData)
            ]);
            $insert_id = $this->gradeRecode();

            if ( ! empty($insert_id) ){
                $is_enable = $this->utils->_getIsEnableWithMethodAndList(__METHOD__, $this->getConfig("adjust_player_level2others_method_list"));
                if($is_enable){
                    $logsExtraInfo = [];
                    $logsExtraInfo['vip_grade_report_id'] = $insert_id;

                    $_rlt_mdb = $this->group_level_lib->adjustPlayerLevelWithLogsFromCurrentToOtherMDBWithLock( $playerId // #1
                                                                                                            , $newVipLevelId // #2
                                                                                                            , Users::SUPER_ADMIN_ID // #3
                                                                                                            , 'Player Management' // #4
                                                                                                            , $logsExtraInfo // #5
                                                                                                            , $_rlt_mdb_inner // #6
                                                                                                        );
                    $this->utils->debug_log('OGP-28577.4865._rlt_mdb:', $_rlt_mdb, '_rlt_mdb_inner:', $_rlt_mdb_inner);
                }
            }

			if ( ! empty($insert_id) ){
				// OGP-25082
				if( ! empty($this->PLAH['multiple_upgrade_buff']) ){
					if( ! isset($this->PLAH['multiple_upgrade_buff']['vip_grade_report_id_list']) ){
						$this->PLAH['multiple_upgrade_buff']['vip_grade_report_id_list'] = []; // initial array
					}
					$this->PLAH['multiple_upgrade_buff']['vip_grade_report_id_list'][] = $insert_id;

					if( empty($this->PLAH['multiple_upgrade_buff']['begin_vipsettingcashbackruleId']) ){ // only log the first
						$this->PLAH['multiple_upgrade_buff']['begin_vipsettingcashbackruleId'] = $vip['vipsettingcashbackruleId'];
					}

					if( ! isset($this->PLAH['multiple_upgrade_buff']['upgraded_list'][$insert_id]) ){
						$this->PLAH['multiple_upgrade_buff']['upgraded_list'][$insert_id] = [];
					}

					$_upgraded_info = $this->group_level_lib->build_upgraded_info($newVipLevelId, $playerLevel, $setting);
					$this->PLAH['multiple_upgrade_buff']['upgraded_list'][$insert_id] = $_upgraded_info;
					// vipupgradesettinginfo
					// {"upgrade_id":"17","setting_name":"New to VIP1","description":"Upgrade from new member to VIP 1","formula":"{\"bet_amount\":[\">=\",\"1700\"],\"operator_2\":\"and\",\"deposit_amount\":[\">=\",\"1700\"]}","status":"1","level_upgrade":"1","created_at":"2022-03-17 15:54:36","accumulation":"0","separate_accumulation_settings":"{\"bet_amount\": {\"accumulation\": \"5\"}, \"deposit_amount\": {\"accumulation\": \"5\"}}","bet_amount_settings":null}
				} // EOF if( ! empty($this->PLAH['multiple_upgrade_buff']) ){...
			} // EOF if ( ! empty($insert_id) ){...


			// 這個是當下處理的升級等級的結果。
			$player = $this->player_model->getPlayerById($playerId); // reload player detail
			$_macroReturn = $this->_macroUpgradeSettingReturnFlagsSetting( $player, $levelMap );
			$playerLevel = array_merge($playerLevel, $_macroReturn['playerLevel']); // reload
			$playerLevel['groupLevelName'] = $_macroReturn['playerVIP'];
			$playerLevel['vip_upgrade_setting'] = $_macroReturn['setting'];
			$playerLevel['insertBy'] = 3829;
			// for $remark reload while disable_player_multiple_upgrade=true.
			$theOffsetAmountOfNextUpgrade = 0;
			$resultAfterDryCheckUpgrade = $this->dryCheckUpgrade($playerId, $levelMap, $isBatchUpgrade, $checkHourly, $time_exec_begin, $theOffsetAmountOfNextUpgrade);
			$playerLevel['isConditionMet'] = $resultAfterDryCheckUpgrade['isConditionMet'];
			$playerLevel['remark'] = $resultAfterDryCheckUpgrade['remark'];
			if(true){
				$theOffsetAmountOfNextUpgrade = 1;
				$resultAfterDryCheckUpgrade = $this->dryCheckUpgrade($playerId, $levelMap, $isBatchUpgrade, $checkHourly, $time_exec_begin, $theOffsetAmountOfNextUpgrade);
				$playerLevel['nextVipLevelData'] = $resultAfterDryCheckUpgrade;
			}
			$this->PLAH['playerLevelInfo']['afterPlayerLevelData'] = $playerLevel; // the current level for popup


			// for log into player_level_adjustment_history
			$this->player_level_adjustment_history->add($this->PLAH);
			// OGP-19825 clear legacy in $this->PLAH
			unset($this->PLAH['previous_vipsettingcashbackrule']);
			unset($this->PLAH['previous_vipupgradesetting']);
			unset($this->PLAH['current_vipsettingcashbackrule']);
			unset($this->PLAH['current_vipupgradesetting']);
			unset($this->PLAH['is_met_offset_rules_info']);
			unset($this->PLAH['initial_amount']);
			// unset($this->PLAH['request_grade']); // keep value
			// unset($this->PLAH['request_time']); // keep value
			// unset($this->PLAH['request_type']); // keep value
			unset($this->PLAH['created_at']);
			unset($this->PLAH['player_id']);
			unset($this->PLAH['is_condition_met']);

            #OGP-16747 add disabled_promotion logic
            if(!$player->disabled_promotion){
                #OGP-1568 todo : upgrade bonus
                //if level up promo rule is set
                if (!empty($VipLevel['promo_cms_id'])) {
                    $promo_cms_id = $VipLevel['promo_cms_id'];
                    $this->utils->debug_log('upgrade Player have promo', $playerId, $promo_cms_id);
                    $this->load->model(array('promorules', 'player_promo'));

                    $promorule = $this->promorules->getPromoruleByPromoCms($promo_cms_id);
                    $promorulesId = $promorule['promorulesId'];
                    if (!empty($playerId) && !empty($promorule)) {
                        if($this->player_promo->isDeclinedForever($playerId, $promorulesId)){
                            $this->utils->debug_log('upgrade promo bonus declined forever', 'Sorry, promo application has been declined', $playerId);
                        } else {
                            $extra_info=$order_generated_by;
                            $this->utils->debug_log('upgrade request promo', $playerId, $promorule, $promo_cms_id, $extra_info);

                            list($applySuccess, $applyMessage) = $this->promorules->triggerPromotionFromManualAdmin($playerId, $promorule, $promo_cms_id, false, null, $extra_info);
							/// $extra_info will got the extra info of promorules::triggerPromotionFromManualAdmin().
							/// for trace the result of the promo request had sent.
							$player_promo_request_id = null; // default
							if( ! empty($extra_info['player_promo_request_id']) ){
								$player_promo_request_id = $extra_info['player_promo_request_id'];
							}
                            $applypromomsg = ['promoruleId' => $promorulesId
												, 'promo_cms_id'=> $promo_cms_id
												, 'success' => $applySuccess
												, 'message' => lang($applyMessage)
												, 'playerPromoId:' => $player_promo_request_id
											];
                            $this->utils->debug_log('upgrade request promo result', $applypromomsg, 'playerId:', $playerId);

                            $updateApplyPromoData = ['applypromomsg' => json_encode($applypromomsg)];
                            $this->updateGradeRecord($insert_id, $updateApplyPromoData);
                        }
                    }
                } else {
                    $this->utils->debug_log('upgrade promo bonus', 'no promo selected', $playerId);
                }
            }else{
                $this->utils->debug_log('upgrade userInformation promotion has been disabled playerId:',$playerId, 'user promotion is:', $player->disabled_promotion);
            }

			// for log into player_level_adjustment_history
			$this->player_level_adjustment_history->add($this->PLAH);
			// OGP-19825 clear legacy in $this->PLAH
			unset($this->PLAH['previous_vipsettingcashbackrule']);
			unset($this->PLAH['previous_vipupgradesetting']);
			unset($this->PLAH['current_vipsettingcashbackrule']);
			unset($this->PLAH['current_vipupgradesetting']);
			unset($this->PLAH['is_met_offset_rules_info']);
			unset($this->PLAH['initial_amount']);
			// unset($this->PLAH['request_grade']); // keep value
			// unset($this->PLAH['request_time']); // keep value
			// unset($this->PLAH['request_type']); // keep value
			unset($this->PLAH['created_at']);
			unset($this->PLAH['player_id']);
			unset($this->PLAH['is_condition_met']);

            $this->CI->player_notification_library->success($playerId, Player_notification::SOURCE_TYPE_VIP_UPGRADE, [
                'player_notify_success_vip_upgrade_title',
                date('Y-m-d H:i:s'),
                lang($player->groupName),
                lang($player->levelName),
                ($newVipData) ? lang($newVipData['groupName']) : NULL,
                ($newVipData) ? lang($newVipData['vipLevelName']) : NULL,
                $this->CI->utils->getPlayerHistoryUrl('promoHistory')
            ], [
                'player_notify_success_vip_upgrade_message',
                date('Y-m-d H:i:s'),
                lang($player->groupName),
                lang($player->levelName),
                ($newVipData) ? lang($newVipData['groupName']) : NULL,
                ($newVipData) ? lang($newVipData['vipLevelName']) : NULL,
                $this->CI->utils->getPlayerHistoryUrl('promoHistory')
            ]);
        } // EOF foreach ($vips as $vip) {...

        #sending email
        if($isPlayerUpgrade){
			$this->load->library(['email_manager']);
		    $template = $this->email_manager->template('player', 'vip_level_upgraded_notification', array('player_id' => $playerId, 'previous_viplevel' => lang($player->levelName), 'new_viplevel' => lang($newVipData['vipLevelName'])));
		    $template_enabled = $template->getIsEnableByTemplateName();

		    if($template_enabled['enable']){
		    	$template->sendingEmail($player->email, Queue_result::CALLER_TYPE_ADMIN, $this->authentication->getUserId());
		    }
    	}

		$result['is_player_upgrade'] = $isPlayerUpgrade;

		/// OGP-21051 需要多跑一次下一個等級設定的存款、投注數據！
		// 因為沒有多段升/降級 的時候，升級成功後，會需要知道下一個等級設定的存款、投注數據。
		if( $isConditionMet && $disable_player_multiple_upgrade ){
			$theOffsetAmountOfNextUpgrade = 0;
			$_newVipLevelData = $this->dryCheckUpgrade($playerId, $levelMap, $isBatchUpgrade, $checkHourly, $time_exec_begin, $theOffsetAmountOfNextUpgrade);
			/// append to PLAH.playerLevelInfo.newVipLevelDataList
			$theOffsetAmountOfNextUpgrade = 1;
			$_newVipLevelData['nextVipLevelData'] = $this->dryCheckUpgrade($playerId, $levelMap, $isBatchUpgrade, $checkHourly, $time_exec_begin, $theOffsetAmountOfNextUpgrade);
			$newVipLevelDataListcounter = count($this->PLAH['playerLevelInfo']['newVipLevelDataList']);
			$this->PLAH['playerLevelInfo']['newVipLevelDataList'][$newVipLevelDataListcounter] = $_newVipLevelData;
		}

		return $result;
	} // EOF playerLevelAdjust

	/**
	 * The separated bet and initials adding
	 *
	 * @param array $separated_betOfgameLogData The array should be the return of calcSeparateAccumulationByExtraSettingList().
	 * The array format,
	 * - $separated_betOfgameLogData[n][type] string "game_platform" or "game_type".
	 * - $separated_betOfgameLogData[n][value] integer|float The amount of setting.
	 * - $separated_betOfgameLogData[n][math_sign]
	 * - $separated_betOfgameLogData[n][game_platform_id/game_type_id]
	 * - $separated_betOfgameLogData[n][precon_logic_flag]
	 * - $separated_betOfgameLogData[n][result_amount] integer|float The amount after calc.(same as count)
	 * - $separated_betOfgameLogData[n][count] integer|float The amount after calc.
	 * - $separated_betOfgameLogData[n][{separated_bet_item}] integer|float The amount after calc.(same as count)
	 * P.S. The "{separated_bet_item}" should be dynamically exists, the examples: "game_platform_id_8", "game_type_id_22".
	 *
	 * @param array $separated_betOfInitialAmount The amount from settings between pre-level and current level.
	 * The array format,
	 * - $separated_betOfInitialAmount[bet_amount] integer|float The bet amount of the setting.
	 * - $separated_betOfInitialAmount[deposit_amount] integer|float The deposit amount of the setting.
	 * - $separated_betOfInitialAmount[win_amount] integer|float The win amount of the setting.
	 * - $separated_betOfInitialAmount[loss_amount] integer|float The loss amount of the setting.
	 * - $separated_betOfInitialAmount[{separated_bet_item}] integer|float The bet amount of the setting.
	 * @return void
	 */
	public function separated_bet_adding(&$separated_betOfgameLogData, $separated_betOfInitialAmount){
	$args = func_get_args();
	$this->utils->debug_log('OGP-19825.3378.$args:',$args);

		// $theGenerateCallTrace = $this->utils->generateCallTrace();
		// $this->utils->debug_log('OGP-19825.$theGenerateCallTrace:',$theGenerateCallTrace);

		if( ! empty($separated_betOfInitialAmount) ){
			foreach($separated_betOfgameLogData as $indexNumber => $separated_item){
				foreach($separated_betOfInitialAmount as $settingKey => $settingValue){
					if( isset($separated_betOfgameLogData[$indexNumber][$settingKey]) ){
						$separated_betOfgameLogData[$indexNumber][$settingKey] += (float)$settingValue;
					}
				} // EOF foreach($separated_betOfgameLogData as $indexNumber => $separated_item){...
			} // EOF foreach($separated_betOfgameLogData as $indexNumber => $separated_item){...
		}
		$this->utils->debug_log('OGP-19825.3378.$separated_betOfgameLogData:',$separated_betOfgameLogData);
	} // EOF separated_bet_adding

	#OGP-1568
	public function playerLevelAdjustDowngrade( $playerId // # 1
												, $levelMap // # 2
												, $isBatchDowngrade // # 3
												, $checkHourly=false // # 4
												, $time_exec_begin = null // # 5
												, $order_generated_by = null // # 6
	) {
		$this->load->library(array('authentication', 'player_notification_library', 'group_level_lib'));
		$this->load->model(array('total_player_game_day', 'player_model', 'transactions'));

		$_pgrm_start_time = date('Y-m-d H:i:s');
		$force_pgrm_start_time_delay_by_request_time_sec = $this->utils->getConfig('force_pgrm_start_time_delay_by_request_time_sec');
		if($force_pgrm_start_time_delay_by_request_time_sec !== false){
			$_pgrm_start_time = $this->group_level_lib->get_pgrm_time_by_request_time($time_exec_begin, $force_pgrm_start_time_delay_by_request_time_sec);
		}

		$this->setGradeRecord(['pgrm_start_time' => $_pgrm_start_time ]);
		$adjustGradeTo = 'down';
		$downgradeCount = 0;
		$isPlayerDowngrade = false;
		$accumulationModeDefault = Group_level::ACCUMULATION_MODE_DISABLE;
		$result = array();
		$hasDowngradeSetting = false; // default

		$player = $this->player_model->getPlayerById($playerId);

		$this->utils->debug_log('4247.player', $player, '$playerId:', $playerId);
		$isForDowngrade = true; // keep true in downgrade
		$macroReturn = $this->_macroUpgradeSettingReturnFlagsSetting( $player, $levelMap, $isForDowngrade);
		$isBetAmountSettings = $macroReturn['isBetAmountSettings'];
		$bet_amount_settingsStr = $macroReturn['bet_amount_settingsStr'];
		$playerLevel = $macroReturn['playerLevel'];
		$isAccumulation = $macroReturn['isAccumulation']; // common and have accumulation
		$isCommonAccumulationModeFromRegistration = $macroReturn['isCommonAccumulationModeFromRegistration']; // from _macroUpgradeSettingReturnFlagsSetting()
		$isSeparateAccumulation = $macroReturn['isSeparateAccumulation'];
		$setting = $macroReturn['setting'];
		$playerVIP = $macroReturn['playerVIP'];// lang($playerLevel['groupName']) . ' - ' . lang($playerLevel['vipLevelName']);
		$separate_accumulation_settings = $macroReturn['separate_accumulation_settings'];
		$setHourly = $macroReturn['setHourly'];

		$this->utils->debug_log('player level id', $player->levelId, '$playerId:', $playerId);
		// make sure vip upgrade exist
		if( ! empty($player)
			&& array_key_exists($player->levelId, $levelMap)
		) {
			$username = $player->username;

			/// moved to _macroUpgradeSettingReturnFlagsSetting().
			// get player level details
			// $playerLevel = $levelMap[$player->levelId];		// get player level details

			$playerVIP = $macroReturn['playerVIP']; /// moved to _macroUpgradeSettingReturnFlagsSetting().
			$playerLevel['vip_downgrade_setting'] = []; // default
			$playerLevel['insertBy'] = 4133;
			if( ! empty($playerLevel['vip_downgrade_id']) ){
				$playerLevel['vip_downgrade_setting'] = $setting; /// moved to _macroUpgradeSettingReturnFlagsSetting().
				$hasDowngradeSetting = true;
			}

			$playerLevel['groupLevelName'] =  $playerVIP;
			$this->PLAH['playerLevelInfo'] = [];
			$this->PLAH['playerLevelInfo']['playerLevelData'] = $playerLevel; // the current level for popup
			$this->PLAH['playerLevelInfo']['newVipLevelDataList'] = []; // default for popup

			if(!empty($playerLevel['period_down'])) {
				$schedule = json_decode($playerLevel['period_down'], true);
				// $setHourly = false; // without interface, keep disable. // OGP-21051 moved to _macroUpgradeSettingReturnFlagsSetting().

				/// moved to _macroUpgradeSettingReturnFlagsSetting().
				// $isAccumulation = false;
				// $isSeparateAccumulation = false;

				// if (!empty($playerLevel['vip_downgrade_id'])) {
				//
				// 	/// moved to _macroUpgradeSettingReturnFlagsSetting().
				// 	// $setting = $playerLevel['vip_downgrade_setting']; // $this->getSettingData($playerLevel['vip_downgrade_id']);
				// 	if (!empty($setting)) {
				// 		/// moved to _macroUpgradeSettingReturnFlagsSetting().
				// 		// // common accumulation
				// 		// if ((int)$setting['accumulation'] > 0) {
				// 		// 	$isAccumulation = true; // common and have accumulation.
				// 		// }
				//
				// 		/// moved to _macroUpgradeSettingReturnFlagsSetting().
				// 		// // separate accumulation
				// 		// $separate_accumulation_settings = [];
				// 		// if ( ! empty($setting['separate_accumulation_settings']) ){
				// 		// 	$separate_accumulation_settings = json_decode($setting['separate_accumulation_settings'], true);
				// 		// }
				//
				// 		/// moved to _macroUpgradeSettingReturnFlagsSetting().
				// 		// if ( ! empty($separate_accumulation_settings) ){
				// 		// 	$isSeparateAccumulation = true;
				// 		// }
				//
				// 		/// moved to _macroUpgradeSettingReturnFlagsSetting().
				// 		// $bet_amount_settingsStr = 'null';
				// 		// if( ! empty($setting['bet_amount_settings']) ){
				// 		// 	$isBetAmountSettings = true;
				// 		// 	$bet_amount_settingsStr = $setting['bet_amount_settings'];
				// 		// }
				//
				// 	} // EOF if (!empty($setting)) {...
				// }

				$previousFromDatetime = null;
				$previousToDatetime = null;
				$fromDatetime = null;
				$toDatetime = null;
				/// Should be disabled, because for OGP-21818
				// $isMetScheduleDate = $this->isMetScheduleDate($schedule, false, $isBatchDowngrade, $time_exec_begin);

				$subNumber = 1; // just get previous datetime range
				$upgradeSched = $this->getScheduleDateRange($schedule, $subNumber, $isBatchDowngrade, $setHourly, $time_exec_begin, $adjustGradeTo);

				// $periodType = $upgradeSched['periodType']; // for debug_log

				/// Should be disabled, because for OGP-21818
				// /// patch at cherry-pick 07860d8896
				// /// OGP-21051.handle $upgradeSched[dateFrom] and $upgradeSched[dateTo] is empty.
				// if (empty($upgradeSched['dateFrom']) || empty($upgradeSched['dateTo']) ) {
				// 	$reason = 'Check schedule. Player downgrade still processing!';
				// 	$this->utils->debug_log('OGP-21051.4209.handle dateFrom or dateTo is empty. playerId:', $playerId);
				// 	$this->utils->debug_log($playerVIP.' in 4209. player', $playerId, ' - ', $username, '$upgradeSched:', $upgradeSched);
				// 	return array('error' => $reason);
				// }
				// /// patch at cherry-pick 07860d8896
				// if (empty($isMetScheduleDate) ) {
				// 	$reason = 'Check schedule. empty($isMetScheduleDate) !';
				// 	$this->utils->debug_log('OGP-21051.4209.'. $reason. ' playerId:', $playerId);
				// 	return array('error' => $reason);
				// }

				$now = new DateTime($time_exec_begin);
				if ($isAccumulation) { // common and have accumulation
					//from player register date to now
					$register_date = new DateTime($player->createdOn);

					$fromDatetime = $register_date->format('Y-m-d H:i:s');
                    $toDatetime = $now->format('Y-m-d H:i:s');
                    $previousFromDatetime = $fromDatetime;
					$previousToDatetime = $toDatetime;

					$accumulationMode = (int)$setting['accumulation'];
					$playerCreatedOn = $player->createdOn;

					$subNumber = 1; // fixed 1, because guaranteed_downgrade_period_number only for guaranteed_downgrade_period_total_deposit > 0
					$isBatch = $isBatchDowngrade;
					$duringDatetime = $this->getDuringDatetimeFromAccumulationByPlayer(	$accumulationMode // # 1
																						, $upgradeSched // # 2
																						, $playerId // # 3
																						, $playerCreatedOn // # 4
																						, $time_exec_begin // # 5
																						, $schedule // # 6
																						, $subNumber // # 7
																						, $isBatch // # 8
																						, $setHourly // # 9
																						, $adjustGradeTo // #10
																					);
					$fromDatetime = $duringDatetime['from'];
					$toDatetime = $duringDatetime['to'];
					$previousFromDatetime = $duringDatetime['previousFrom'];
					$previousToDatetime = $duringDatetime['previousTo'];

					// if (empty($upgradeSched['dateFrom']) || empty($upgradeSched['dateTo']) ) {
					// 	$this->utils->debug_log($playerVIP.' 4299 schedule is not set. player', $playerId, ' - ', $username, '$upgradeSched:', $upgradeSched);
	                //     return array('error' => 'Check schedule. Player downgrade still processing! (4301)');
					// }
				} else { // non-Accumulation
$this->utils->debug_log('OGP-21051.4233.playerVIP: '.$playerVIP.' in 4233. player'
	, $playerId, ' - ', $username
	, '$upgradeSched:', $upgradeSched
);
	                if (!empty($upgradeSched['dateFrom']) && !empty($upgradeSched['dateTo']) ) {
						// for guaranteed_downgrade get previousFromDatetime and previousToDatetime
	                    $fromDatetime = $upgradeSched['dateFrom'];
	                    $toDatetime = $upgradeSched['dateTo'];
	                    $periodType = $upgradeSched['periodType'];
	                    $previousSched = $this->getScheduleDateRange($schedule, $playerLevel['guaranteed_downgrade_period_number'], $isBatchDowngrade, false, $time_exec_begin, $adjustGradeTo);
	                    $previousFromDatetime = $previousSched['dateFrom'];
	                    $previousToDatetime = $previousSched['dateTo'];
$this->utils->debug_log('OGP-21051.4244.playerVIP: '.$playerVIP.' 4240 schedule is not set. player'
	, $playerId, ' - ', $username
	, '$previousSched:', $previousSched
);
					/// Disable for patch the case,"CACB.EmptyPeriodMode.PeriodNotMet.OnLevelMaintainEnable.OverMaintainTime.IsMetLevelMaintainCondition.NoAccumulation".
					// } else {
					// 	/// OGP-21051.handle $upgradeSched[dateFrom] and $upgradeSched[dateTo] is empty.
					// 	// The date should Not todo downgrade/upgrade.
					// 	$theGenerateCallTrace = $this->utils->generateCallTrace();
					// 	$this->utils->debug_log('OGP-21051.4260.handle dateFrom and dateTo is empty. playerId:', $playerId, 'playerVIP:', $playerVIP, 'theGenerateCallTrace:', $theGenerateCallTrace);
					// 	return array('error' => 'Check schedule. Player downgrade still processing!');
	                }
            	}// EOF if ($isAccumulation)

				// Level Downgrade Maintain
				$enableDownMaintain = null;
				if( empty($schedule['enableDownMaintain']) ){
					$enableDownMaintain = false;
				}else{
					$enableDownMaintain = true;
				}
				if( empty($schedule['enableDownMaintain']) ){
					if (empty($upgradeSched['dateFrom']) || empty($upgradeSched['dateTo']) ) {
						$this->utils->debug_log($playerVIP.' 4321 schedule is not set. player', $playerId, ' - ', $username, '$upgradeSched:', $upgradeSched);
						return array('error' => 'Check schedule. Player downgrade still processing! (4301)');
					}
				}else{
					$previousFromDatetime = $now->format('Y-m-d H:i:s');
					$previousToDatetime = $now->format('Y-m-d H:i:s');
					$fromDatetime = $now->format('Y-m-d H:i:s');
					$toDatetime = $now->format('Y-m-d H:i:s');
					// /// continus for Down Maintain
					// related, $isConditionMet = $isMet4DownMaintain; // 使用 保級設定
					// // load Down Maintain Settings
					// $downMaintainConditionDepositAmount = $schedule['downMaintainConditionDepositAmount'];
					// $downMaintainConditionBetAmount = $schedule['downMaintainConditionBetAmount'];
					// $downMaintainUnit = $schedule['downMaintainUnit'];
					// $downMaintainTimeLength = $schedule['downMaintainTimeLength'];
					//
					// $playerCreatedOn = $player->createdOn;
					// $downMaintainFormula =null;// $downMaintainFormula will get remark about content.
					// $isMet4DownMaintain = $this->isMet4DownMaintain( $playerId // # 1
					// 												, $playerCreatedOn // # 2
					// 												, $downMaintainTimeLength // # 3
					// 												, $downMaintainUnit // # 4
					// 												, $downMaintainConditionBetAmount // # 5
					// 												, $downMaintainConditionDepositAmount // # 6
					// 												, $downMaintainFormula // # 7
					// 												, $time_exec_begin );  // # 8
				}

				$gameLogData = [];

				$deposit = 0; // default
				if($isSeparateAccumulation){

					// separate accumulation
					$formula = $setting['formula'];
					$separate_accumulation_settings = $setting['separate_accumulation_settings'];
					$playerCreatedOn = $player->createdOn;
					$time_exec_begin = $now->format('Y-m-d H:i:s');
					$subNumber = $playerLevel['guaranteed_downgrade_period_number'];
					$isBatch = $isBatchDowngrade;
					// $setHourly = false; // OGP-21051 moved to _macroUpgradeSettingReturnFlagsSetting().

					$calcResult = $this->calcSeparateAccumulationByExtraSettingList($separate_accumulation_settings // # 1
																					, $formula // # 2
																					, $bet_amount_settingsStr // #2.1
																					, $upgradeSched // # 3
																					, $playerId // # 4
																					, $playerCreatedOn // # 5
																					, $time_exec_begin // # 6
																					, $schedule // # 7
																					, $subNumber // # 8
																					, $isBatch // # 9
																					, $setHourly // # 10
																				);


					// $calcResult convert to $gameLogData
					if( ! empty($calcResult['separated_bet']) ){
						$gameLogData['total_bet'] = null;
						$gameLogData['separated_bet'] = $calcResult['separated_bet'];
					}else if( isset($calcResult['total_bet']) ){
						$gameLogData['total_bet'] = $calcResult['total_bet'];
					}else{
						$gameLogData['total_bet'] = null;
					}

					if( isset($calcResult['total_win']) ){
						$gameLogData['total_win'] = $calcResult['total_win'];
					}else{
						$gameLogData['total_win'] = null;
					}

					if( isset($calcResult['total_loss']) ){
						$gameLogData['total_loss'] = $calcResult['total_loss'];
					}else{
						$gameLogData['total_loss'] = null;
					}

					if( isset($calcResult['deposit']) ){
						$deposit = $calcResult['deposit'];
					}else{
						$deposit = null;
					}
				// EOF if($isSeparateAccumulation){...
				}else if( $hasDowngradeSetting ){ // found the downgrade setting
					/// "non-Accumulation" and "common accumulation"
					// for calc the settings in common accumulation,"enable_separate_accumulation_in_setting = false".

					/// ===== apply Separate Accumulation function,"calcSeparateAccumulationByExtraSettingList" to Downgrade ====
					// use "separate accumulation" to run "common accumulation"

					if( !empty($setting['formula']) ){
						$formula = $setting['formula'];
					}

					if( isset($setting['formula']) ){
						$accumulationMode = (int)$setting['accumulation'];
					}else{
						$accumulationMode = $accumulationModeDefault;
					}


					$playerCreatedOn = $player->createdOn;
					$time_exec_begin = $now->format('Y-m-d H:i:s');
					$subNumber = 1; /// fixed 1, because guaranteed_downgrade_period_number only for guaranteed_downgrade_period_total_deposit > 0
					$isBatch = $isBatchDowngrade;
					// SAS = separate_accumulation_settings
					// for apply to calcSeparateAccumulationByExtraSettingList()
					$_sas = [];
					$_sas['bet_amount'] = [];
					$_sas['bet_amount']['accumulation'] = $accumulationMode;
					$_sas['deposit_amount'] = [];
					$_sas['deposit_amount']['accumulation'] = $accumulationMode;
					$_sas['loss_amount'] = [];
					$_sas['loss_amount']['accumulation'] = $accumulationMode;
					$_sas['win_amount'] = [];
					$_sas['win_amount']['accumulation'] = $accumulationMode;
					$calcResult = $this->calcSeparateAccumulationByExtraSettingList( $_sas // # 1
																						, $formula // # 2
																						, $bet_amount_settingsStr // #2.1
																						, $upgradeSched // # 3
																						, $playerId // # 4
																						, $playerCreatedOn // # 5
																						, $time_exec_begin // # 6
																						, $schedule // # 7
																						, $subNumber // # 8
																						, $isBatch // # 9
																						, $setHourly // # 10
																					);
					// $calcResult convert to $gameLogData
					if( ! empty($calcResult['separated_bet']) ){
						$gameLogData['total_bet'] = null;
						$gameLogData['separated_bet'] = $calcResult['separated_bet'];
					}else if( isset($calcResult['total_bet']) ){
						$gameLogData['total_bet'] = $calcResult['total_bet'];
					}else{
						$gameLogData['total_bet'] = null;
					}
					if( isset($calcResult['total_win']) ){
						$gameLogData['total_win'] = $calcResult['total_win'];
					}else{
						$gameLogData['total_win'] = null;
					}
					if( isset($calcResult['total_loss']) ){
						$gameLogData['total_loss'] = $calcResult['total_loss'];
					}else{
						$gameLogData['total_loss'] = null;
					}
					// ===== EOF apply Separate Accumulation function,"calcSeparateAccumulationByExtraSettingList" to Downgrade ====

					/// origial
					// $total_player_game_table = 'total_player_game_minute';
					// $where_date_field = 'date_minute';
					// $fromDatetime4minute = $this->utils->formatDateMinuteForMysql(new DateTime($fromDatetime));
					// $toDatetime4minute = $this->utils->formatDateMinuteForMysql(new DateTime($toDatetime));
					// $gameLogData = $this->total_player_game_day->getPlayerTotalBetWinLoss($playerId, $fromDatetime4minute, $toDatetime4minute, $total_player_game_table, $where_date_field); // @todo OGP-19332

					$this->utils->debug_log('-------- downgrade get gameLogData and deposit amounts from to ------', $playerId, $fromDatetime, $toDatetime);
                    $add_manual = false; // as default
					list($deposit) = $this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers($playerId,$fromDatetime,$toDatetime, $add_manual, $this->enable_multi_currencies_totals);
				// EOF else if( $hasDowngradeSetting ){...
				} // EOF if($isSeparateAccumulation){...

				$player_basic_amount_enable_in_downgrade = $this->utils->getConfig('player_basic_amount_enable_in_downgrade');
				if( ! empty($player_basic_amount_enable_in_downgrade)
					&& ! empty($isCommonAccumulationModeFromRegistration)
				){
					$theBasicAmountDetail = $this->getBasicAmountAfterImported($player->username);
					if( !empty($theBasicAmountDetail['total_bet_amount']) ){
						$gameLogData['total_bet'] += $theBasicAmountDetail['total_bet_amount'];
					}
					if( !empty($theBasicAmountDetail['total_deposit_amount']) ){
						$deposit += $theBasicAmountDetail['total_deposit_amount'];
					}
					$this->utils->debug_log('OGP23800.4986.after.basic_amount.theBasicAmountDetail:', $theBasicAmountDetail, 'gameLogData:', $gameLogData, 'deposit:', $deposit);
				}

				$previousDeposit = 0;
				if (!$isAccumulation) {
					$this->utils->debug_log('-------- downgrade get previous deposit amount from to ------', $playerId, $previousFromDatetime, $previousToDatetime);
                    $add_manual = false; // as default
					list($previousDeposit) = $this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers($playerId, $previousFromDatetime, $previousToDatetime, $add_manual, $this->enable_multi_currencies_totals);
					$this->utils->debug_log('-------- downgrade get previous deposit amount ------', $playerId, $previousDeposit);
				}

				if( empty($gameLogData) || $enableDownMaintain ){
					$gameLogData['total_bet'] = null;
					$gameLogData['total_win'] = null;
					$gameLogData['total_loss'] = null;
				}
				$this->utils->debug_log('-------- downgrade deposit amount ---------', $playerId, $deposit, $previousDeposit, '$gameLogData:', $gameLogData);
				if(!empty($gameLogData) || !empty($deposit) || $enableDownMaintain ) {



					$vipSettingId = $playerLevel['vip_downgrade_id'];
					if( ! empty($vipSettingId) || $enableDownMaintain) {

						// as per leo for multiple upgrade don't consider schedule. just only the current player level setting
						$setting = $this->getSettingData($vipSettingId); // the row of the table,vip_upgrade_setting
						if( empty($setting) ){
							$separate_accumulation_settings = [];
						}
						if( ! empty($setting) || true ) {

							// VIP Multiple Upgrade
							$enableLeftJoin = true;
							$vips = $this->getAllVIPDowngradeLevels($playerLevel['vipSettingId'], $playerLevel['vipLevel'], $enableLeftJoin);

							$downgrade = 0; // met the condition counter
							$hadDowngradeCounter = 0; // calc for execute adjust level function in downgrade.

							foreach($vips as $vip) {
								// $this->utils->debug_log('OGP-19332.3270.$vip:', $vip);
								/// vip_level_maintain_settings = false
								// guaranteed condition check,
								// - guaranteed_downgrade_period_total_deposit
								// - guaranteed_downgrade_period_number
								/// vip_level_maintain_settings = true
								// @ = $vip[period_down]
								// @.enableDownMaintain
								// @.downMaintainConditionDepositAmount
								// @.downMaintainConditionBetAmount
								// @.downMaintainUnit
								// @.downMaintainTimeLength

								$currLevel = $levelMap[ $vip['vipsettingcashbackruleId'] ];
								$vip = array_merge($vip, $currLevel);
								$vip['groupLevelName'] = lang($vip['groupName']). ' - '. lang($vip['vipLevelName']);
								// $vip['vip_downgrade_id'] has avabiles after merged $levelMap[$vip['vipsettingcashbackruleId']].
								$vip['vip_downgrade_setting'] = $this->getSettingData($vip['vip_downgrade_id']);
$this->utils->debug_log('OGP-19332.3270.$vip:', $vip // the row of the table, vipsettingcashbackrule
			, '$setting:', $setting);
								$bet_amount_settings  = [];
								if(!empty($vip['bet_amount_settings'])){
									$bet_amount_settings  = json_decode($vip['bet_amount_settings'], true);
								}

								$formula = json_decode($vip['formula'], true);
								$this->utils->debug_log('-------- downgrade generateUpDownFormula-------', $playerId, $formula, $gameLogData['total_bet'], $deposit, $gameLogData['total_loss'], $gameLogData['total_win']);

								// $isConditionMet = $this->generateUpDownFormula($formula, $gameLogData['total_bet'], $deposit, $gameLogData['total_loss'], $gameLogData['total_win']);
								if( !empty($gameLogData['separated_bet']) ){
									$gameLogData4bet = $gameLogData['separated_bet'];
								} else {
									$gameLogData4bet = $gameLogData['total_bet'];
								}
								// $this->utils->debug_log('OGP-19332.3296.$gameLogData4bet:', $gameLogData4bet, '$gameLogData:', $gameLogData);


								$isDisableSetupRemarkToGradeRecord = false;
								$_remark = null;


								$bpld_options = $this->config->item('batch_player_level_downgrade');
								if( ! empty($bpld_options['BLADidleSec']) ){
									// BLAD = playerLevelAdjustDowngrade
									$BLADidleSec = $bpld_options['BLADidleSec']; // 60 sec for test
									if( ! empty($BLADidleSec) ){
										$this->utils->debug_log('will BLADidleSec', $BLADidleSec);
										$this->utils->idleSec($BLADidleSec);
									}
								}
								$isConditionMet = $this->generateUpDownFormulaWithBetAmountSetting( $formula // #1
																				, $gameLogData4bet // #2
																				, $deposit // #3
																				, $gameLogData['total_loss'] // #4
																				, $gameLogData['total_win'] // #5
																				, $bet_amount_settings  // #6
																				, $isDisableSetupRemarkToGradeRecord // #7
																				, $_remark // #8
																			); // met the condition will be downgraded

// $this->utils->debug_log('OGP-19332.3296.$isConditionMet:', $isConditionMet);
								// if($isSeparateAccumulation|| true){
								// merge remark of vip_grade_report
								$remark = [];
								if( ! empty($this->grade_record['remark']) ){
									$remark = json_decode($this->grade_record['remark'], true) ;
								}
								if( !empty($setting['separate_accumulation_settings']) ){
									$separate_accumulation_settings = $setting['separate_accumulation_settings'];
									$remark = array_merge($remark, [ 'separate_accumulation_settings' => $separate_accumulation_settings]);
								}
								if( !empty($calcResult) ){
									$remark = array_merge($remark, [ 'separate_accumulation_calcResult' => $calcResult]);
								}

								$this->setGradeRecord([
									'remark' => json_encode($remark)
								]);
								// }// EOF if($isSeparateAccumulation)

								if( empty($schedule['enableDownMaintain']) ){
									// for not yet setup the settings
									$enableDownMaintain = false;
								}else{
									$enableDownMaintain = $schedule['enableDownMaintain'];
								}

								// OGP-18071, to enable "Downgrade Guaranteed Setting" of "Edit VIP Group Level Setting", if disable the feature,"vip_level_maintain_settings".
								$vip_level_maintain_settings = $this->utils->isEnabledFeature('vip_level_maintain_settings');
								$isMet4DownMaintain = false; // default
								if( empty($vip_level_maintain_settings)
									&& ! $isSeparateAccumulation // Not support Separate Accumulation
								){
									//guaranteed condition check
									// $playerLevel same as $vip while in the frist loop.
									if (!empty($playerLevel['guaranteed_downgrade_period_total_deposit'])) {
										$this->utils->debug_log('OGP-20868.3703.guaranteed condition check : '
											, 'vip formula::', $vip['formula']
											, 'Player::'.$playerId.' - '.$username
											, 'total::'.$playerLevel['guaranteed_downgrade_period_number']
											.' period of::'. (empty($periodType)? null:$periodType)
											.' previousDeposit + deposit::' . ($deposit + $previousDeposit)
											. ' deposit limit ::' . $playerLevel['guaranteed_downgrade_period_total_deposit']
											, 'deposit::', $deposit
											, 'previousDeposit::', $previousDeposit
											,'Search previous keyword,"downgrade get previous deposit amount from to" for confirm the datetime range of previous.');

										if (((float)$deposit + (float)$previousDeposit) < (float)$playerLevel['guaranteed_downgrade_period_total_deposit']) {
											$this->utils->debug_log('downgrade guaranteed condition check : ', 'vip formula', $vip['formula'], 'Player '.$playerId.' - '.$username, 'total '.$playerLevel['guaranteed_downgrade_period_number'].' period of '. (empty($periodType)? null:$periodType).' deposit ' . ($deposit + $previousDeposit) . ' is not met guaranteed deposit ' . $playerLevel['guaranteed_downgrade_period_total_deposit'], 'deposit', $deposit, 'previousDeposit', $previousDeposit, 'downgrade player level');
											$isConditionMet = true; // met the condition will be downgraded.
										}
									}
								// EOF if( empty($vip_level_maintain_settings) ){...
								}else{
									/// reload Down Maintain
									if( empty($schedule['enableDownMaintain']) ){
										// for not yet setup the settings
										$downMaintainConditionDepositAmount = 0;
										$downMaintainConditionBetAmount = 0;
										$downMaintainUnit = self::DOWN_MAINTAIN_TIME_UNIT_DAY;
										$downMaintainTimeLength = 0;
									}else{
										$downMaintainConditionDepositAmount = $schedule['downMaintainConditionDepositAmount'];
										$downMaintainConditionBetAmount = $schedule['downMaintainConditionBetAmount'];
										$downMaintainUnit = $schedule['downMaintainUnit'];
										$downMaintainTimeLength = $schedule['downMaintainTimeLength'];
									}

									$downMaintainFormula =null;// default, $downMaintainFormula will get remark about content.
									$playerCreatedOn = $player->createdOn;

                                    if( empty($bpld_options['immediate4lastGradeRecordRow']) ){
                                        // the begin moment of cronjob, it also write to  vip_grade_report.request_time field.
                                        $time_exec_begin = $time_exec_begin;
                                    }else{
                                        // Assign the current moment.
                                        $time_exec_begin = $this->utils->getNowForMysql();
                                    }
									$isMet4DownMaintain = $this->isMet4DownMaintain( $playerId // # 1
																					, $playerCreatedOn // # 2
                                                                                    , $player->levelId // # 2.1
																					, $downMaintainTimeLength // # 3
																					, $downMaintainUnit // # 4
																					, $downMaintainConditionBetAmount // # 5
																					, $downMaintainConditionDepositAmount // # 6
																					, $downMaintainFormula // # 7
																					, $time_exec_begin // # 8
                                                                                );

									if($enableDownMaintain){
										// merge remark of vip_grade_report
										$remark = [];
										if( ! empty($this->grade_record['remark']) ){
											$remark = json_decode($this->grade_record['remark'], true) ;
										}
										$remark = array_merge($remark, [ 'downMaintain' => $downMaintainFormula]);

										$remark = array_merge($remark, [ 'isMet4DownMaintain' => $isMet4DownMaintain]);

										$this->setGradeRecord([
											'remark' => json_encode($remark)
										]);
									}// EOF if($enableDownMaintain)

								} // EOF if( empty($vip_level_maintain_settings) )

                                $enableDowngrade = true;
                                if( empty($playerLevel['enable_vip_downgrade']) ){
                                    $enableDowngrade = false;
                                }
                                $doDowngradeAction = $this->group_level_lib->getDowngradeAction($isConditionMet, $enableDowngrade, ! $isMet4DownMaintain, $enableDownMaintain);

								$this->utils->debug_log('downgrade 3710 isConditionMet', $isConditionMet
                                                                        , 'enableDowngrade:', $enableDowngrade
																		, 'playerId:', $playerId
																		, 'enableDownMaintain:', $enableDownMaintain
																		, 'isMet4DownMaintain:',  $isMet4DownMaintain
                                                                        , 'doDowngradeAction:', $doDowngradeAction
                                                                        , 'schedule:', $schedule );

                                $isConditionMet = $doDowngradeAction; // override

								$_remark = []; // for load the remark
								if( ! empty($this->grade_record['remark']) ){
									$_remark = json_decode($this->grade_record['remark'], true) ;
								}
								$vip['remark'] = $_remark;
								$vip['isConditionMet'] = $isConditionMet;
								$vip['isMet4DownMaintain'] = $isMet4DownMaintain;

								//get current level promo cms id
								$VipLevel = $this->getVipLevel($vip['vipsettingcashbackruleId']);
								$newVipLevelId = $this->getVipLevelIdByLevelNumber($vip['vipSettingId'], $vip['vipLevel'] - 1);

								$newVipData = (isset($levelMap[$newVipLevelId])) ? $levelMap[$newVipLevelId] : NULL;
								if(!empty($newVipData) ){
									$newVipData['groupLevelName'] = lang($newVipData['groupName']). ' - '. lang($newVipData['vipLevelName']);
									$newVipData['vip_downgrade_setting'] = $this->getSettingData($newVipData['vip_downgrade_id']);
								}
								$newVipData['insertBy'] = 4423;
								$newVipData['remark'] = []; // default
								if($isConditionMet){ // when success downgrade
									$theOffsetAmountOfNextDowngrade = 1;
									$_result = $this->dryCheckDowngradeLite($playerId, $levelMap, $time_exec_begin, $isBatchDowngrade, $theOffsetAmountOfNextDowngrade);
									$newVipData['remark'] = $_result['remark'];
								}
								$vip['nextVipLevelData'] = $newVipData;

								// assign downgraded level exist flag into the current level.
								if( ! isset($this->PLAH['playerLevelInfo']['playerLevelData']['has_new_vip_data']) ){
									$this->PLAH['playerLevelInfo']['playerLevelData']['has_new_vip_data'] = !empty($newVipData);
								}
                                $updateApplyPromoData = [];
								$this->utils->debug_log('3413.downgrade isConditionMet', $isConditionMet
																		, 'isMet4DownMaintain:', $isMet4DownMaintain
																		, 'playerId:', $playerId
																		, '$vip:', $vip
																		, '$newVipLevelId', $newVipLevelId);
								if($isConditionMet) {
									$downgrade++;
									if($vip['level_upgrade'] == self::DOWNGRADE_ONLY || $enableDownMaintain ) {
										if($downgrade == 1 ) { // 1 single downgrade
											// If condition is not met then downgrade
											$this->utils->debug_log('downgrade', true, $vip['vipSettingId'], $vip['vipLevel'] - 1);
											$this->utils->debug_log('downgrade newVipLevelId', $playerId, $newVipLevelId);
											if (!empty($newVipLevelId)) {
												$hadDowngradeCounter++;

												$this->adjustPlayerLevel($playerId, $newVipLevelId);



												/// assign adjusted level into the newVipLevelDataList for popup. (ignore for OGP-21051)
												// $newVipData['groupLevelName'] = lang($newVipData['groupName']) . ' - ' . lang($newVipData['vipLevelName']);
												// $newVipData['isConditionMet'] = $isConditionMet;
												// $newVipData['isMet4DownMaintain'] = $isMet4DownMaintain;
												// $newVipData['insertBy'] = 4589;
												// $newVipLevelDataListcounter = count($this->PLAH['playerLevelInfo']['newVipLevelDataList']);
												// $this->PLAH['playerLevelInfo']['newVipLevelDataList'][$newVipLevelDataListcounter] = $newVipData;
												$vip['insertBy'] = 4594;
												$newVipLevelDataListcounter = count($this->PLAH['playerLevelInfo']['newVipLevelDataList']);
												$this->PLAH['playerLevelInfo']['newVipLevelDataList'][$newVipLevelDataListcounter] = $vip;

												$result = array('success' => 'Player level successfully downgrade');
												$this->utils->debug_log('downgrade Player level successfully downgrade', $playerId);
												$isPlayerDowngrade = true;
												$downgradeCount++;

												$_pgrm_end_time = date('Y-m-d H:i:s');
												$force_pgrm_end_time_delay_by_request_time_sec = $this->utils->getConfig('force_pgrm_end_time_delay_by_request_time_sec');
												if($force_pgrm_end_time_delay_by_request_time_sec !== false){
													$_pgrm_end_time = $this->group_level_lib->get_pgrm_time_by_request_time($time_exec_begin, $force_pgrm_end_time_delay_by_request_time_sec);
												}

												$this->setGradeRecord([
													'player_id'  => $playerId,
													'level_from' => $vip['vipLevel'],
													'level_to'   => !empty($newVipLevelId) ? $vip['vipLevel'] - 1 : 0 ,
													'period_start_time' => $fromDatetime,
													'period_end_time'   => $toDatetime,
													'newvipId'                   => $newVipLevelId,
													'vipsettingId'               => $vip['vipSettingId'],
													'vipupgradesettingId'        => empty($playerLevel['vip_downgrade_id'])? 0: $playerLevel['vip_downgrade_id'], // default is Zero
													'vipsettingcashbackruleId'   => $playerLevel['vipsettingcashbackruleId'],
													'vipupgradesettinginfo'      => json_encode($setting),
													'vipsettingcashbackruleinfo' => json_encode($playerLevel),
													'pgrm_end_time' => $_pgrm_end_time,
													'updated_by'    => $this->authentication->getUserId(),
													'status'        => self::GRADE_SUCCESS,
                                                    'applypromomsg' => json_encode($updateApplyPromoData)
												]);

                                                $insert_id = $this->gradeRecode();

                                                if( ! empty($insert_id) ){
                                                    $is_enable = $this->utils->_getIsEnableWithMethodAndList(__METHOD__, $this->getConfig("adjust_player_level2others_method_list"));
                                                    if($is_enable){
                                                        $logsExtraInfo = [];
                                                        $logsExtraInfo['vip_grade_report_id'] = $insert_id;
                                                        $_rlt_mdb = $this->group_level_lib->adjustPlayerLevelWithLogsFromCurrentToOtherMDBWithLock( $playerId // #1
                                                                                                            , $newVipLevelId // #2
                                                                                                            , Users::SUPER_ADMIN_ID // #3
                                                                                                            , 'Player Management' // #4
                                                                                                            , $logsExtraInfo // #5
                                                                                                            , $_rlt_mdb_inner // #6
                                                                                                        );
                                                        $this->utils->debug_log('OGP-28577.5773._rlt_mdb:', $_rlt_mdb, '_rlt_mdb_inner:', $_rlt_mdb_inner);
                                                    }
                                                }

												#OGP-16747 add disabled_promotion logic
												if(!$player->disabled_promotion){
													if (!empty($VipLevel['downgrade_promo_cms_id'])) {
														$downgrade_promo_cms_id = $VipLevel['downgrade_promo_cms_id'];
														$this->utils->debug_log('3483.downgrade Player have promo', $playerId, $downgrade_promo_cms_id);
														$this->load->model(array('promorules', 'player_promo'));

														$promorule = $this->promorules->getPromoruleByPromoCms($downgrade_promo_cms_id);
														$promorulesId = $promorule['promorulesId'];
														if (!empty($playerId) && !empty($promorule)) {
															$this->utils->debug_log('playerId', $playerId, 'promorule', $promorule);
															if($this->player_promo->isDeclinedForever($playerId, $promorulesId)){
																$this->utils->debug_log('3491.downgrade promo bonus declined forever', 'Sorry, promo application has been declined', $playerId);
																$debugError = 'downgrade promo bonus declined forever';
																$this->utils->debug_log($debugError, '3493.Sorry, promo application has been declined', $playerId);
																$applypromomsg = ['promoruleId' => null, 'promo_cms_id'=> null, 'playerId' => $playerId, 'success' => false, 'message' => lang($debugError)];
																$this->setGradeRecord([
                                                                    'applypromomsg' => json_encode($applypromomsg)
                                                                ]);

															} else {
																$extra_info=$order_generated_by;
																$this->utils->debug_log('3501.downgrade request promo', $playerId, $promorule, $downgrade_promo_cms_id, $extra_info);

                                                                list($applySuccess, $applyMessage) = $this->promorules->triggerPromotionFromManualAdmin($playerId, $promorule, $downgrade_promo_cms_id, false, null, $extra_info);
                                                                $applypromomsg = ['promoruleId' => $promorulesId, 'promo_cms_id'=> $downgrade_promo_cms_id, 'success' => $applySuccess, 'message' => lang($applyMessage), 'playerId:', $playerId];
                                                                $this->utils->debug_log('3505.downgrade request promo result', $applypromomsg);

                                                                $updateApplyPromoData = ['applypromomsg' => json_encode($applypromomsg)];
                                                                $this->updateGradeRecord($insert_id, $updateApplyPromoData);
															}
														}else{
															$applypromomsg = ['promoruleId' => null, 'promo_cms_id'=> null, 'success' => false, 'message' => lang('empty downgrade promo'), 'playerId' => $playerId];
															$this->utils->debug_log('3512.downgrade request promo result', $applypromomsg);

															$this->setGradeRecord([
																'applypromomsg' => json_encode($applypromomsg)
															]);
														} // EOF if (!empty($playerId) && !empty($promorule)) {...
													}else{
														$applypromomsg = ['promoruleId' => null, 'promo_cms_id'=> null, 'success' => false, 'message' => lang('empty downgrade promo'), 'playerId' => $playerId];
														$this->setGradeRecord([
															'applypromomsg' => json_encode($applypromomsg)
														]);
													} // EOF if (!empty($VipLevel['downgrade_promo_cms_id'])) {...
												}else{
													$this->utils->debug_log('3525.Downgrade userInformation promotion has been disabled playerId:',$playerId, 'user promotion is:', $player->disabled_promotion);
													$applypromomsg = ['success' => false, 'playerId' => $playerId, 'user promotion is'=> $player->disabled_promotion, 'message' => lang('The user promotion had disabled')];
													$this->utils->debug_log('3527.downgrade request promo result', $applypromomsg);
													$this->setGradeRecord([
														'applypromomsg' => json_encode($applypromomsg)
													]);
												}



                                                #OGP-
                                                $this->CI->player_notification_library->success($playerId, Player_notification::SOURCE_TYPE_VIP_DOWNGRADE, [
                                                    'player_notify_success_vip_downgrade_title',
                                                    date('Y-m-d H:i:s'),
                                                    lang($player->groupName),
                                                    lang($player->levelName),
                                                    ($newVipData) ? lang($newVipData['groupName']) : NULL,
                                                    ($newVipData) ? lang($newVipData['vipLevelName']) : NULL,
                                                    $this->CI->utils->getPlayerHistoryUrl('promoHistory')
                                                ], [
                                                    'player_notify_success_vip_downgrade_message',
                                                    date('Y-m-d H:i:s'),
                                                    lang($player->groupName),
                                                    lang($player->levelName),
                                                    ($newVipData) ? lang($newVipData['groupName']) : NULL,
                                                    ($newVipData) ? lang($newVipData['vipLevelName']) : NULL,
                                                    $this->CI->utils->getPlayerHistoryUrl('promoHistory')
                                                ]);
											// EOF if (!empty($newVipLevelId)) {...
											} else {
												$result = array('error' => 'Downgrade Failed');
												$this->utils->debug_log('3556.downgrade failed', $playerId);
											}
										} // EOF if($downgrade == 1 ) { ...
									} else {
										$result = array('success' => 'Setting is not Downgrade Only, Keep Player Level ---'.$playerId);
										$this->utils->debug_log('3560.Setting is not Downgrade Only, Keep Player Level ---', $playerId);
									}

									if($hadDowngradeCounter == 0){
										$vip['insertBy'] = 4709;
										$newVipLevelDataListcounter = count($this->PLAH['playerLevelInfo']['newVipLevelDataList']);
										$this->PLAH['playerLevelInfo']['newVipLevelDataList'][$newVipLevelDataListcounter] = $vip;
									}else{
										// handle by $vip['insertBy'] = 4594;
									}
								// EOF if($isConditionMet) {...
								} else {
									$resultError = 'Player Downgrade condition is not met, Keep Player Level';
									$result = array('error' => $resultError);
									$this->utils->debug_log('3566.'.$resultError.' ---', $playerId);

									$success_grade = $this->utils->getConfig('get_only_success_grade_report');
									if (!$success_grade) {    # set status to GRADE_FAILED if not meet upgrade condition

										$_pgrm_end_time = date('Y-m-d H:i:s');
										$force_pgrm_end_time_delay_by_request_time_sec = $this->utils->getConfig('force_pgrm_end_time_delay_by_request_time_sec');
										if($force_pgrm_end_time_delay_by_request_time_sec !== false){
											$_pgrm_end_time = $this->group_level_lib->get_pgrm_time_by_request_time($time_exec_begin, $force_pgrm_end_time_delay_by_request_time_sec);
										}

										$this->setGradeRecord([
												'player_id'  => $playerId,
												'level_from' => $vip['vipLevel'],
												'level_to'   => !empty($newVipLevelId) ? $vip['vipLevel'] - 1 : 0 ,
												'period_start_time' => $fromDatetime,
												'period_end_time'   => $toDatetime,
												'newvipId'                   => $newVipLevelId,
												'vipsettingId'               => $vip['vipSettingId'],
												'vipupgradesettingId'        => $playerLevel['vip_downgrade_id'],
												'vipsettingcashbackruleId'   => $playerLevel['vipsettingcashbackruleId'],
												'vipupgradesettinginfo'      => json_encode($setting),
												'vipsettingcashbackruleinfo' => json_encode($playerLevel),
												'pgrm_end_time' => $_pgrm_end_time,
												'updated_by'    => $this->authentication->getUserId(),
												'status'        => self::GRADE_FAILED,
                                                'applypromomsg' => json_encode($updateApplyPromoData)
										]);

										$this->gradeRecode();
									}
									///
									$vip['insertBy'] = 4742;
									$newVipLevelDataListcounter = count($this->PLAH['playerLevelInfo']['newVipLevelDataList']);
									$this->PLAH['playerLevelInfo']['newVipLevelDataList'][$newVipLevelDataListcounter] = $vip;
									break; // for keep original flow
								} // EOF if($isConditionMet) {...

								break; // add for for 1 single downgrade
							} // EOF foreach($vips as $vip) {...
						} else {
							$result = array('error' => $playerVIP.' downgrade setting is not set');
							$this->utils->debug_log($playerVIP.' 4794.downgrade setting is not set', $playerId);
						}
					} else {
						$result = array('error' => $playerVIP.' downgrade setting is not set');
						$this->utils->debug_log($playerVIP.' downgrade setting is not set. player', $playerId, ' - ', $username);
					}
				} else {
					if( ! $hasDowngradeSetting){
						$result = array('error' => $playerVIP.' downgrade setting is not set');
						$this->utils->debug_log($playerVIP.' 4803.downgrade setting is not set', $playerId);
					}else{
						$result = array('error' => 'No player deposit or bet data', $playerId);
						$this->utils->debug_log('No deposit or bet data', 'player '.$playerId.' - '.$username, $playerVIP );
					}
				}
			} else {
				$result = array('error' => 'Cannot downgrade. No schedule is set');
				$this->utils->debug_log($playerVIP.' 3554 schedule is not set. player', $playerId, ' - ', $username);
			}
		} else {
			$result = array('error' => 'VIP Level is not exist', $playerId);
		}

		$result['is_player_downgrade'] = $isPlayerDowngrade;
		return $result;
	}

	public function getAllVIPLevels($vipSettingId, $vipLevelId, $checkHourly=false) {
		$this->db->select('vipsettingcashbackrule.vipSettingId');
		$this->db->select('vipsettingcashbackrule.vipLevel');
		$this->db->select('vipsettingcashbackrule.vipLevelName');
		$this->db->select('vipsettingcashbackrule.vipsettingcashbackruleId');
		$this->db->select('vipsettingcashbackrule.period_up_down_2');
		$this->db->select('vip_upgrade_setting.formula');
		$this->db->select('vip_upgrade_setting.level_upgrade');
		$this->db->select('vip_upgrade_setting.bet_amount_settings');
		$this->db->from('vipsettingcashbackrule');
		$this->db->join('vip_upgrade_setting', 'vip_upgrade_setting.upgrade_id=vipsettingcashbackrule.vip_upgrade_id');
		$this->db->where('vipsettingcashbackrule.vipSettingId', $vipSettingId);
		$this->db->where('vipsettingcashbackrule.vipLevel >=', $vipLevelId);
		if($checkHourly===true) {
			$this->db->like('vipsettingcashbackrule.period_up_down_2', '"hourly":true}');
		}

		$setting = $this->runMultipleRowArray();

		return $setting;
	}

	public function getAllVIPDowngradeLevels($vipSettingId, $vipLevelId, $enableLeftJoin = false) {
		$this->db->select('vipsettingcashbackrule.vipSettingId');
		$this->db->select('vipsettingcashbackrule.vipLevel');
		$this->db->select('vipsettingcashbackrule.vipLevelName');
		$this->db->select('vipsettingcashbackrule.vipsettingcashbackruleId');
		$this->db->select('vipsettingcashbackrule.period_down'); // that's like as the period_up_down_2 of upgrade
		$this->db->select('vip_upgrade_setting.formula');
		$this->db->select('vip_upgrade_setting.level_upgrade');
		$this->db->select('vip_upgrade_setting.bet_amount_settings');
		$this->db->from('vipsettingcashbackrule');
		if($enableLeftJoin){
			$this->db->join('vip_upgrade_setting', 'vip_upgrade_setting.upgrade_id=vipsettingcashbackrule.vip_downgrade_id', 'left');
		}else{
			$this->db->join('vip_upgrade_setting', 'vip_upgrade_setting.upgrade_id=vipsettingcashbackrule.vip_downgrade_id');
		}
		$this->db->where('vipsettingcashbackrule.vipSettingId', $vipSettingId);
		$this->db->where('vipsettingcashbackrule.vipLevel <=', $vipLevelId);
		$this->db->order_by('vipsettingcashbackrule.vipLevel', 'desc');

		$setting = $this->runMultipleRowArray();
		// $this->utils->debug_log('4859.getAllVIPDowngradeLevels SQL', $this->db->last_query() );
		return $setting;
	}

	public function getVipLevel($upgradeSettingId) {
		$this->db->select('vipsettingcashbackrule.vipSettingId');
		$this->db->select('vipsettingcashbackrule.vipLevel');
		$this->db->select('vipsettingcashbackrule.vipLevelName');
		$this->db->select('vipsettingcashbackrule.promo_cms_id');
		$this->db->select('vipsettingcashbackrule.downgrade_promo_cms_id');
		$this->db->select('vipsettingcashbackrule.guaranteed_period_number');
		$this->db->select('vipsettingcashbackrule.guaranteed_period_total_deposit');
		$this->db->select('vipsettingcashbackrule.guaranteed_downgrade_period_number');
		$this->db->select('vipsettingcashbackrule.guaranteed_downgrade_period_total_deposit');
		$this->db->select('vip_upgrade_setting.formula');
		$this->db->select('vip_upgrade_setting.level_upgrade');
		$this->db->from('vipsettingcashbackrule');
		$this->db->join('vip_upgrade_setting', 'vip_upgrade_setting.upgrade_id=vipsettingcashbackrule.vip_upgrade_id or vip_upgrade_setting.upgrade_id=vipsettingcashbackrule.vip_downgrade_id');
		$this->db->where('vipsettingcashbackrule.vipsettingcashbackruleId', $upgradeSettingId);

		return $this->db->get()->row_array();
	}


	public function getPlayerBetAmtForNextLvl($playerId, $period, $accumulation = 0, $bet_amount_settings = null) {
// $theCallTrace = $this->utils->generateCallTrace();
// $this->utils->debug_log('OGP-19332,getPlayerBetAmtForNextLvl.theCallTrace', $theCallTrace);
		$this->load->library(['group_level_lib']);
		if( is_null($bet_amount_settings) ){
			$bet_amount_settings = '{}';
		}
		if( is_string($bet_amount_settings) ){
			$isValidJon = $this->ci->utils->isValidJson($bet_amount_settings);
			if($isValidJon){
				$bet_amount_settings = json_decode($bet_amount_settings, true);
			}
		}

		$schedule = json_decode($period, true);
		$this->load->model(['player_model']);
		$playerDetails = $this->player_model->getPlayerDetailsById($playerId);
		$now = new DateTime();
		// $toDatetime = $this->utils->formatDateTimeForMysql($now);
// $this->utils->debug_log('OGP-19332,getPlayerBetAmtForNextLvl', $period);
		if(!empty($schedule)){
			$theScheduleInfo = $this->getUpgradeSchedule($schedule, true);
			$fromDatetime = $theScheduleInfo['dateFrom'];
			$toDatetime = $theScheduleInfo['dateTo'];
			if ((int)$accumulation == Group_level::ACCUMULATION_MODE_FROM_REGISTRATION) {
				$fromDatetime = $playerDetails->createdOn;
			}else if ((int)$accumulation == Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE) {
				$theLastGradeRecordRow = $this->queryLastGradeRecordRowBy($playerId, $playerDetails->createdOn, $this->utils->formatDateTimeForMysql($now), 'upgrade_or_downgrade');

				if( empty($theLastGradeRecordRow) ){
					$fromDatetime = $playerDetails->createdOn; // from registaction for into vip1 first
				}else{
					$fromDatetime = $theLastGradeRecordRow['pgrm_end_time'];
				}
			}else if ((int)$accumulation == Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET) {
				// Get the begin date time.
				$fromDatetime = $this->group_level_lib->_getBeginDatetimeInBetWithAccumulationModeLastChangedGeadeResetIfMet($playerId);
			}

			$toDatetime = $this->utils->formatDateTimeForMysql($now);
			$total_player_game_table = 'total_player_game_minute';
			$where_date_field = 'date_minute';
			$fromDatetime4minute = $this->utils->formatDateMinuteForMysql(new DateTime($fromDatetime));
			$toDatetime4minute = $this->utils->formatDateMinuteForMysql(new DateTime($toDatetime));

			$this->utils->debug_log("getPlayerBetAmtForNextLvl datetime from:[$fromDatetime4minute], to:[$toDatetime4minute]");
            if( $this->enable_multi_currencies_totals ){
                $gameLogData = $this->group_level_lib->getPlayerTotalBetWinLossWithForeachMultipleDBWithoutSuper($playerId, $fromDatetime4minute, $toDatetime4minute, $total_player_game_table, $where_date_field);
            }else{
                $gameLogData = $this->total_player_game_day->getPlayerTotalBetWinLoss($playerId, $fromDatetime4minute, $toDatetime4minute, $total_player_game_table, $where_date_field);
            }

// $this->utils->debug_log('OGP-19332.3542.$gameLogData', $gameLogData);
			$separatedGameLogData = $this->getSeparatedGameLogDataFromTotalPlayerGameMinute(	$playerId // #1
																		, $fromDatetime // #2
																		, $toDatetime // #3
																		, $bet_amount_settings // #4
																	);
			if( ! empty($separatedGameLogData) ){ // extend the separated game_log data
				$total_bet = 0;
				foreach($separatedGameLogData as $indexNumber => $currGameLogData){
					// value":"123",
					// "math_sign":">=",

					$currMathSign = $currGameLogData['math_sign'];
					$currValue = floatval($currGameLogData['result_amount']);
// $this->utils->debug_log('OGP-19332.3572.currMathSign:', $currMathSign, 'currValue:', $currValue,'currGameLogData:',$currGameLogData);
					switch($currMathSign){
						case '>=':
						case '==':
							$total_bet += $currValue;
						break;
						case '>':
							$total_bet += $currValue;
							$total_bet++;
						break;
						case '<':
						case '<=':
							$total_bet += 0;
						break;
					}
				}
// $this->utils->debug_log('OGP-19332.3572.total_bet:',$total_bet, 'separatedGameLogData:', $separatedGameLogData);
				$gameLogData['total_bet'] = $total_bet;
			}

// $this->utils->debug_log('OGP-19332.3572.gameLogData.total_bet:',$gameLogData['total_bet'],'$separatedGameLogData', $separatedGameLogData, 'bet_amount_settings:',$bet_amount_settings);
			return $gameLogData['total_bet'];
		}
		return null;
	}

	/**
	 * This will return date range for type of schedule
	 *
	 *
	 * @param string $type The period keyword, daily, weekly, monthly and yearly.
	 * @param string $ofSpecificTime Assign a specific time.
	 * @return void
	 *
	 *
	 * @param string $type The period keyword, daily, weekly, monthly and yearly.
	 * @param string $ofSpecificTime Assign a specific time.
	 * @return void
	 */
	public function getPlayerUpgradeScheduleRange($type, $ofSpecificTime = null) {
		if($ofSpecificTime === null){
			$currentTimestamp = time();
		}else{
			$currentTimestamp = DateTime::createFromFormat('Y-m-d H:i:s', $ofSpecificTime)->getTimestamp();
		}
		switch ($type) {
		case 'daily':
			$fromDatetime = date('Y-m-d 00:00:00', $currentTimestamp);
			$toDatetime = date('Y-m-d 23:59:59', $currentTimestamp);
			break;

		case 'weekly':
			$fromDatetime = date("Y-m-d 00:00:00", strtotime("last week monday", $currentTimestamp));
			$toDatetime = date("Y-m-d 23:59:59", strtotime("last week sunday", $currentTimestamp));
			break;

		case 'monthly':
			$fromDatetime = date("Y-m-d 00:00:00", strtotime("first day of previous month", $currentTimestamp));
			$toDatetime = date("Y-m-d 23:59:59", strtotime("last day of previous month", $currentTimestamp));
			break;

		case 'yearly':
			$fromDatetime = date("Y-01-01 00:00:00", strtotime("-1 year", $currentTimestamp));
			$toDatetime = date("Y-12-31 23:59:59", strtotime("-1 year", $currentTimestamp));
			break;
		}
		return array($fromDatetime, $toDatetime);
	} // EOF getPlayerUpgradeScheduleRange


	public function getUpgradeSchedule($schedule, $ignoreCurrentDate = null) {

		$dateRange = array();

		$fromDatetime = null;
		$toDatetime = null;
		$sched = '';

		if (isset($schedule['daily'])) {

			$fromDatetime = date('Y-m-d 00:00:00');
			$toDatetime = date('Y-m-d 23:59:59');
			$sched = 'daily';

		} else if (isset($schedule['weekly']) ) {

			// get last week if day setting is set today
			$currentDay = date('l');
			$currentDayNum = date('N', strtotime($currentDay));
			if ($currentDayNum == $schedule['weekly'] || ($ignoreCurrentDate == true)) {
				$fromDatetime = date("Y-m-d 00:00:00", strtotime("last week monday"));
				$toDatetime = date("Y-m-d 23:59:59", strtotime("last week sunday"));
				if ($ignoreCurrentDate) {
					$fromDatetime = date('Y-m-d 00:00:00', strtotime('this week monday'));
					$toDatetime = date("Y-m-d 23:59:59", strtotime("this week sunday"));
				}
			}
			$sched = 'weekly';

		} else if (isset($schedule['monthly']) ) {
			// check if current day of month is same as setting
			$currentDayNum = date('d');
			if ($currentDayNum == $schedule['monthly'] || ($ignoreCurrentDate == true)) {
				$fromDatetime = date("Y-m-d 00:00:00", strtotime("first day of previous month"));
				$toDatetime = date("Y-m-d 23:59:59", strtotime("last day of previous month"));
				if ($ignoreCurrentDate) {
					$fromDatetime = date('Y-m-d 00:00:00', strtotime('first day of this month'));
					$toDatetime = date("Y-m-d 23:59:59", strtotime("last day of this month"));
				}
			}
			$sched = 'monthly';

		} else if (isset($schedule['yearly'])  ) {
			// check if current date is first day of month
			$yearLySettingNum = sprintf('%02d', $schedule['yearly']) . '-01';
			$currentMonthDate = date('m-d');
			if ($currentMonthDate == $yearLySettingNum || ($ignoreCurrentDate == true)) {
				$fromDatetime = date("Y-01-01 00:00:00", strtotime("-1 year"));
				$toDatetime = date("Y-12-31 23:59:59", strtotime("-1 year"));
				if ($ignoreCurrentDate) {
					$fromDatetime = date('Y-01-01 00:00:00');
					$toDatetime = date("Y-12-31 23:59:59");
				}
			}
			$sched = 'yearly';
		}

		if (!empty($schedule)) {
			$dateRange = array('dateFrom' => $fromDatetime, 'dateTo' => $toDatetime, 'sched' => $sched);
		}
		return $dateRange;
	}

	public function getVIPUpgradeSetting($upgradeSettingId, $bet, $deposit, $loss, $win) {

		$this->db->select('vip_upgrade_setting.formula');
		$this->db->from('vipsettingcashbackrule');
		$this->db->join('vip_upgrade_setting', 'vip_upgrade_setting.upgrade_id=vipsettingcashbackrule.vip_upgrade_id');
		$this->db->where('vipsettingcashbackrule.vipsettingcashbackruleId', $upgradeSettingId);

		$setting = $this->db->get()->row_array();

		if (!empty($setting['formula'])) {
			$formula = json_decode($setting['formula'], true);
			return $this->generateUpDownFormula($formula, $bet, $deposit, $loss, $win);
		} else {
			return true; // don't downgrade if setting is empty
		}
	}

	/**
	 * Parse the json string,"vip_upgrade_setting.bet_amount_settings" for get the game platform/type conditions.
	 * Pls reference to the URLs for regex and test,
	 * - https://regex101.com/r/Ut3BV1/2
	 * - http://sandbox.onlinephpfunctions.com/code/c8316019133c122511e8e3144bb5b74d2e205f6a
	 *
	 * @param string $betAmountSettingStr The json string of the field,"vip_upgrade_setting.bet_amount_settings".
	 * @return array The array formats,
	 * - return[parsed] array The array after parsed.
	 * - return[bet_amount_setting] string The string after parsed and combine.
	 */
	public function parseSetStrListWithBetSettingV2($betAmountSettingStr = '{}'){
		$this->load->model(['game_type_model']);
		$return = [];
		$parsed = [];
		$parsed4human = [];
		$setting_list = [];
		$gameId4humanList = [];
		/// parse the following formats,
		// {"type": "game_type", "value": "22", "math_sign": ">=", "game_type_id/game_platform_id": "324"}
		// {"type": "game_type", "value": "123", "math_sign": ">=", "game_type_id/game_platform_id": "325", "precon_logic_flag": "and"}
		$regex = '/\{\"type\"\W+\"(?P<item_type>[\w]+)\"\W+\"value\"\W+\"?(?P<item_value>\d+)\"?\W+\"math_sign\"\W+\"(?P<item_sign>[<>=]{1,2})\"\W+\"game_\w+_id\"\W+\"(?P<item_id>\d+)\"\}?(\W+\"precon_logic_flag\"\W+\"(?P<item_precon>\w{1,3})\"\})?/m';
		// $betAmountSettingStr = '{"itemList": [{"type": "game_type", "value": "22", "math_sign": ">=", "game_type_id": "324"}, {"type": "game_type", "value": "123", "math_sign": ">=", "game_type_id": "325", "precon_logic_flag": "and"}, {"type": "game_platform", "value": "123", "math_sign": ">=", "game_platform_id": "8", "precon_logic_flag": "or"}, {"type": "game_platform", "value": "11", "math_sign": ">=", "game_platform_id": "24", "precon_logic_flag": "or"}], "defaultItem": {"value": "123", "math_sign": ">="}}';
		// $theGenerateCallTrace = $this->utils->generateCallTrace();
// $this->utils->debug_log('OGP-19332.3796.theGenerateCallTrace:', $theGenerateCallTrace);
		// if( ! is_string($betAmountSettingStr) ){
		// 	$betAmountSettingStr = json_encode($betAmountSettingStr);
		// }

		preg_match_all($regex, $betAmountSettingStr, $matches, PREG_SET_ORDER, 0);

		// Print the entire match result
		// var_dump($matches);
		if( ! empty($matches) ){
			// collect the game type and game platform for convert id to name of langs.
			$gameType4humanList = []; // [{game_id_key:"LANG_game_platform_name"},{game_id_key:"LANG_game_type_name"},...]
			$gamePlatform4humanList = [];
			foreach($matches as $indexNumber => $currMatche){
				if($currMatche['item_type'] == 'game_type'){
					$setting_id_key = 'game_type_id_'.$currMatche['item_id'];
					$_gameType4human = [];
					$_gameType4human[$setting_id_key] = $setting_id_key; // will replace to game type name of langs
					$_gameType4human['item_id'] = $currMatche['item_id'];
					array_push($gameType4humanList, $_gameType4human); // pre-setup for convert game_id_key,"game_type_id_XXX" to lang at one-time
				}else if($currMatche['item_type'] == 'game_platform'){
					$setting_id_key = 'game_platform_id_'.$currMatche['item_id'];
					$_gamePlatform4human = [];
					$_gamePlatform4human[$setting_id_key] = $setting_id_key; // will replace to game platform name of langs
					$_gamePlatform4human['item_id'] = $currMatche['item_id'];
					array_push($gamePlatform4humanList, $_gamePlatform4human); // pre-setup for convert game_id_key,"game_platform_id_XXX" to lang at one-time
				}
			} // EOF foreach($matches as $indexNumber => $currMatche){...

			if( ! empty($gamePlatform4humanList) ){
				foreach($gamePlatform4humanList as $indexNumber => $_gamePlatform){
					$setting_id_key = 'game_platform_id_'.$_gamePlatform['item_id'];
					$game_platform_id = $_gamePlatform['item_id'];
					$setting_id_key4human = $this->external_system->getNameById($game_platform_id);
					if($setting_id_key4human == $game_platform_id){
						/// When the output eq. the input, its means Not Found, in external_system::getNameById()
						$setting_id_key4human = $setting_id_key;
					}
					if( ! empty($setting_id_key4human) ){
						$gamePlatform4humanList[$indexNumber][$setting_id_key] = $setting_id_key4human;
						unset($gamePlatform4humanList[$indexNumber]['item_id']); // remove the item_id property of each elemant.
					}
				} // EOF foreach
$this->utils->debug_log('OGP-21051.4965.gamePlatform4humanList:', $gamePlatform4humanList);
				// sort by length of $setting_id_key, long to short.
				// for avoid the replace issue, "game_id_1234" will replaced to "abce234" by "game_id_1" while "game_id_1" = "abce".
				$volume = [];
				$edition = [];
				foreach ($gamePlatform4humanList as $key => $row) {
					$_key = key($row);
					$volume[$key]  = $row[$_key];
					$edition[$key] = $_key;
				}
				array_multisort($volume, SORT_DESC, $edition, SORT_ASC, $gamePlatform4humanList);

				// convert 2-way to 1-way array.
				$_gamePlatform4humanList = [];
				foreach ($gamePlatform4humanList as $key => $row) {
					$_key = key($row);
					$_gamePlatform4humanList[$_key] = $row[$_key];
				}
				$gamePlatform4humanList = $_gamePlatform4humanList;
			} // EOF if( ! empty($gamePlatform4humanList) ){...

			if( ! empty($gameType4humanList) ){
				foreach($gameType4humanList as $indexNumber => $_gameType){
					$setting_id_key = 'game_type_id_'.$_gameType['item_id'];
					$doAppendId = false;
					$separator = '=>';
					$typeIdList = [$_gameType['item_id']];
					$resultTypeList = $this->game_type_model->searchGameTypeByList($typeIdList, $separator, $doAppendId);
					if( ! empty($resultTypeList) ){
						$gameType4humanList[$indexNumber][$setting_id_key] = $resultTypeList[0];
						unset($gameType4humanList[$indexNumber]['item_id']); // remove the item_id property of each elemant.
					}
				} // EOF foreach

				// sort by length of $setting_id_key, long to short.
				// for avoid the replace issue, "game_id_1234" will replaced to "abce234" by "game_id_1" while "game_id_1" = "abce".
				$volume = [];
				$edition = [];
				foreach ($gameType4humanList as $key => $row) {
					$_key = key($row);
					$volume[$key]  = $row[$_key];
					$edition[$key] = $_key;
				}

				array_multisort($volume, SORT_DESC, $edition, SORT_ASC, $gameType4humanList);
$this->utils->debug_log('OGP-21051.5128.gameType4humanList:', $gameType4humanList);
				// convert 2-way to 1-way array.
				$_gameType4humanList = [];
				foreach ($gameType4humanList as $key => $row) {
					$_key = key($row);
					$_gameType4humanList[$_key] = $row[$_key];
				}
				$gameType4humanList = $_gameType4humanList;
			} // EOF if( ! empty($gameType4humanList) ){...

			$gameId4humanList = array_merge_recursive($gamePlatform4humanList, $gameType4humanList);

			foreach($matches as $indexNumber => $currMatche){
// $this->utils->debug_log('OGP-19825.4204.currMatche:', $currMatche);
				$_currList = [];
				$_currList4human = [];
				if( ! empty($parsed ) ){ // OGP-19825/OGP-19332 Patch for ,formula="and game_type_id_325 >= 6012 and game_platform_id_8 >= 6...
					if( ! empty($currMatche['item_precon'] ) ){
						array_push($_currList, $currMatche['item_precon']);
						array_push($_currList4human, $currMatche['item_precon']);
					}
				}

				if($currMatche['item_type'] == 'game_type'){
					$setting_id_key = 'game_type_id_'.$currMatche['item_id'];
					array_push($_currList, $setting_id_key, $currMatche['item_sign'], $currMatche['item_value'] );

					$setting_id_key4human = $setting_id_key; // default
					// $doAppendId = true;
					// $separator = '=>';
					// $typeIdList = [$currMatche['item_id']];
					// $resultTypeList = $this->game_type_model->searchGameTypeByList($typeIdList, $separator, $doAppendId);
					// if( ! empty($resultTypeList) ){
					// 	$setting_id_key4human = $resultTypeList[0];
					// }
					// array_push($gameType4humanList, [$setting_id_key=>$setting_id_key]); // pre-setup for convert game_id_key,"game_type_id_XXX" to lang at one-time

					if( ! empty($gameId4humanList[$setting_id_key]) ){
						$setting_id_key4human = $gameId4humanList[$setting_id_key];
					}
					array_push($_currList4human, $setting_id_key4human, $currMatche['item_sign'], $currMatche['item_value'] );

					$setting_list[$setting_id_key] = $currMatche['item_value'];
				}else if($currMatche['item_type'] == 'game_platform'){
					$setting_id_key = 'game_platform_id_'.$currMatche['item_id'];
					array_push($_currList, $setting_id_key, $currMatche['item_sign'], $currMatche['item_value'] );

					// $game_platform_id = $currMatche['item_id'];
					// $setting_id_key4human = $this->external_system->getNameById($game_platform_id);
					// if( empty($setting_id_key4human) ){
					// 	$setting_id_key4human = $setting_id_key; // when not found
					// }
					// array_push($gamePlatform4humanList, [$setting_id_key=>$setting_id_key]); // pre-setup for convert game_id_key,"game_platform_id_XXX" to lang at one-time
					$setting_id_key4human = $setting_id_key; // default
					if( ! empty($gameId4humanList[$setting_id_key]) ){
						$setting_id_key4human = $gameId4humanList[$setting_id_key];
					}
					array_push($_currList4human, $setting_id_key4human, $currMatche['item_sign'], $currMatche['item_value'] );

					$setting_list[$setting_id_key] = $currMatche['item_value'];
				}
				array_push($parsed, implode(' ',$_currList)); // implode and append into $parsed
				array_push($parsed4human, implode(' ',$_currList4human));
			} // EOF foreach


		}
		$return['parsed'] = $parsed;
		$return['parsed4human'] = $parsed4human;

		$return['bet_amount_setting'] = implode(' ', $parsed);
		$return['bet_amount_setting4human'] = implode(' ', $parsed4human);
		$return['setting_list'] = $setting_list;
		$return['game_id4human_list'] = $gameId4humanList;

		return $return;
	} // EOF parseSetStrListWithBetSettingV2

	/**
	 * Parse the formula for the option set,"option, operator and amount(bet_amount, <, 123)" and the operator set("and"/"or").
	 * Pls ref. to https://regex101.com/r/tqa1ka/3
	 *
	 * @param string $formulaStr
	 * @return array The array formats,
	 * - $return['parsedFormula'] The list after parsed.
	 * - $return['formula'] The combined formulas string.
	 */
	public function parseSetStrListWithFormulaV2($formulaStr = '{}', $bet_amount_setting = null){
		$return = [];
		$parsedFormula = [];
		$parsedFormula4human = [];
		$setting_list = [];
		$game_id4human_list = [];
		// catch the following formats from formula,
		// - "operator_2":"or"
		// - "bet_amount":[">","11"]
		$regex = '/(?P<operator_setting>operator_\d[\W]+\"(?P<operator_sym>\w{2,3})\"){0,1}\W+(?P<option_setting>\"(?P<option>\w+_amount)\"[\W]+\[\"(?P<operator>[><=]{1,2})\"\,\"?(?P<amount>\d+)\"?\]\,?)/m';
		// $formulaStr = '[{"bet_amount":[">","11"],"operator_2":"or","deposit_amount":["<=","22"],"operator_4":"or","win_amount":["<","33"]},{"bet_amount":["<","1"],"operator_2":"or","loss_amount":["<=","2"],"operator_3":"or","win_amount":[">","3"]}]';
		// {"deposit_amount":[">=","50000"],"operator_2":"and","bet_amount":[">=","1000000"]}
		if( empty($bet_amount_setting) ){
			$bet_amount_setting = '{}';
		}

		preg_match_all($regex, $formulaStr, $matches, PREG_SET_ORDER, 0);
		if( ! empty($matches) ){
// $theGenerateCallTrace = $this->utils->generateCallTrace();
// $this->utils->debug_log('OGP-19332.3842.$theGenerateCallTrace:',	$theGenerateCallTrace);
// $this->utils->debug_log('OGP-19332.$matches:',$matches,'$bet_amount_setting:', $bet_amount_setting, '$formulaStr:', $formulaStr);
			foreach($matches as $indexNumber => $currMatche){
				$_currList = [];
				$_currList4human = [];
				// operator_setting first
				if( ! empty($currMatche['operator_setting'] )){
					array_push($_currList, $currMatche['operator_sym']); // like as "or", "and" then append into $_currList
					array_push($_currList4human, $currMatche['operator_sym']); // like as "or", "and" then append into $_currList
				} // EOF if( ! empty($currMatche['operator_setting'] )){...

				// option_setting want parsed too.
				if( ! empty($currMatche['option_setting'] )){
					// defaults
					$doBetAmountSetting = false;
					if($currMatche['option'] == 'bet_amount'){
						if( ! empty($bet_amount_setting) ){
							$doBetAmountSetting = true;
							if( $this->utils->isValidJson($bet_amount_setting) ){
								$bet_amount_setting = json_decode($bet_amount_setting, true);
							}
						}
					}
					if( empty($bet_amount_setting) ){
						$bet_amount_setting = [];
					}

// $this->utils->debug_log('OGP-19332.$doBetAmountSetting:',$doBetAmountSetting, '$bet_amount_setting',$bet_amount_setting);
					if($doBetAmountSetting && ! empty($bet_amount_setting) ){
						$betAmountSettingStr = json_encode($bet_amount_setting);
						$parsed = $this->parseSetStrListWithBetSettingV2($betAmountSettingStr);
						if( ! empty($parsed['game_id4human_list']) ){
							$game_id4human_list = array_merge($game_id4human_list, $parsed['game_id4human_list']);
						}
						array_push($_currList, '('. $parsed['bet_amount_setting']. ')'); // parse bet_amount_setting then append into $_currList
						array_push($_currList4human, '('. $parsed['bet_amount_setting4human']. ')'); // parse bet_amount_setting then append into $_currList4human
$this->utils->debug_log('OGP-19825.4289._currList:',$_currList, '$parsed:', $parsed);
						$setting_list = array_merge($setting_list, $parsed['setting_list']);

					}else{
						array_push($_currList, $currMatche['option']); // like as "bet_amount", "deposit_amount",... then append into $_currList
						array_push($_currList, $currMatche['operator']); // like as ">", "<=",... then append into $_currList
						array_push($_currList, $currMatche['amount']); // like as "123" then append into $_currList

						array_push($_currList4human, $this->langOptionByFormula($currMatche['option']) ); // like as "bet_amount", "deposit_amount",... then append into $_currList
						array_push($_currList4human, $currMatche['operator']); // like as ">", "<=",... then append into $_currList
						array_push($_currList4human, $currMatche['amount']); // like as "123" then append into $_currList

						$setting_list[$currMatche['option']] = $currMatche['amount'];
						// array_push($setting_list, $currMatche['option']);
					}
				} // EOF if( ! empty($currMatche['option_setting'] )){...

// $this->utils->debug_log('OGP-19332.$_currList:',$_currList, '$currMatche:', $currMatche);
				array_push($parsedFormula, implode(' ',$_currList)); // implode and append into $parsedFormula
				array_push($parsedFormula4human, implode(' ',$_currList4human));
			}// foreach($matches as $indexNumber => $currMatche){...
		}
// $this->utils->debug_log('OGP-19332.$parsedFormula:',$parsedFormula);
		$return['parsedFormula'] = $parsedFormula;
		$return['parsedFormula4human'] = $parsedFormula4human;
		$return['formula'] = implode(' ', $parsedFormula);
		$return['formula4human'] = implode(' ', $parsedFormula4human);
		$return['setting_list'] = $setting_list;
		$return['game_id4human_list'] = $game_id4human_list;
		return $return;
	}// EOF parseSetStrListWithFormulaV2

	function langOptionByFormula($theOptionStr){
		$returnLang = '';
		switch( strtolower($theOptionStr) ){
			case 'bet_amount':
				$returnLang = lang('Bet');
				break;
			case 'deposit_amount':
				$returnLang = lang('Deposit');
				break;
			case 'loss_amount':
				$returnLang = lang('Loss');
				break;
			case 'win_amount':
				$returnLang = lang('Win');
				break;
			default:
				$returnLang = lang($theOptionStr);
				break;
		}
		return $returnLang ;
	} // EOF langOptionByFormula

	/**
	 * Ref. to https://regex101.com/r/EvwMaw/1
	 * @param object|array $formula The formula from the field, "vip_upgrade_setting.formula".
	 * @return array The match gamekey list.
	 */
	function parseGameIdKeyListWithFormulaStr($theFormulaStr){
		$gameIdKeyList = [];

		$regex = '/(?P<game_type>game_type_id_\d+)|(?P<game_platform>game_platform_id_\d+)/m';
		preg_match_all($regex, $theFormulaStr, $matches, PREG_SET_ORDER, 0);
		if( ! empty($matches) ){
			foreach($matches as $indexNumber => $currMatche){
				$_currList = [];
				if( ! empty($currMatche['game_platform'] ) ){
					$_currList['type'] = 'game_platform';
					$_currList['gameIdKey'] = $currMatche['game_platform'];
					array_push($gameIdKeyList, $_currList);
				}else if( ! empty($currMatche['game_type'] ) ){
					$_currList['type'] = 'game_type';
					$_currList['gameIdKey'] = $currMatche['game_type'];
					array_push($gameIdKeyList, $_currList);
				}
			}
		}
		return $gameIdKeyList;
	}

	/**
	 * Generate formula with total bet, deposit, loss, win amount then calc by bet_amount_settings.
	 *
	 * @param object|array $formula The formula from the field, "vip_upgrade_setting.formula".
	 * @param integer $bet The total bet amount.
	 * @param integer $deposit The deposit amount.
	 * @param integer $loss The loss amount.
	 * @param integer $win The win amount.
	 * @param object|array $betAmountSettings The bet_amount_settings from the field, "vip_upgrade_setting.bet_amount_settings".
	 * @param boolean $isDisableSetupRemarkToGradeRecord
	 * @param point $remark4get The remark of GradeRecord for catch.
	 * @param array $betEnforcedDetails The part of result array from group_level::_macroGetGameLogDataAndDeposit(). The part array is under that result array's location: @.calcResult.details.total_bet.enforcedDetails.
	 * @param array $depositEnforcedDetails The part of result array from group_level::_macroGetGameLogDataAndDeposit(). The part array is under that result array's location: @.calcResult.details.deposit.enforcedDetails
	 * @param array $settingInfoHarshInUpGraded The format as the follwings,
	 * - $settingInfoHarshInUpGraded['total_deposit'] bool If its true, the total bet/deposit amount of the upgrad level condition in the multi-level upgrade mode, that will be accumulated into the current condition of the grade .
	 * - $settingInfoHarshInUpGraded['total_bet'] bool If its true, the total bet/deposit amount of the upgrad level condition in the multi-level upgrade mode, that will be accumulated into the current condition of the grade .
	 * @return void
	 */
	public function generateUpDownFormulaWithBetAmountSetting( $formula // #1
																, $bet // #2
																, $deposit // #3
																, $loss // #4
																, $win // #5
																, $separatedBetSetting=[] // #6
																, $isDisableSetupRemarkToGradeRecord = false // #7
																, &$remark4get = null // #8
																, $betEnforcedDetails = [] // #9
																, $depositEnforcedDetails = [] // #10
																, $settingInfoHarshInUpGraded = [] // #11
	) {
		$this->load->library(['group_level_lib']);
		// $isMetOffsetRules
		// $separatedBetSetting
		$logs = '';
		// $option = 0;

		if( empty($settingInfoHarshInUpGraded)){ // for default
			$settingInfoHarshInUpGraded['total_deposit'] = false;
			$settingInfoHarshInUpGraded['total_bet'] = false;
		}
		$_upgraded_list = [];
		if( $settingInfoHarshInUpGraded['total_deposit']
			|| $settingInfoHarshInUpGraded['total_bet']
		){
			if( ! empty($this->PLAH['multiple_upgrade_buff']['upgraded_list']) ){
				$_upgraded_list = $this->PLAH['multiple_upgrade_buff']['upgraded_list'];
			}
		}

		if( $settingInfoHarshInUpGraded['total_deposit'] ){
			$upgraded_condition_amounts = [];
			$_isNeedUpdateDepositLimit = false;
			if( ! empty($formula['deposit_amount']) ){
				$_isNeedUpdateDepositLimit = true;
				$upgraded_condition['deposit_amount'] = 0;
			}

			if( ! empty($_upgraded_list) ){
				foreach($_upgraded_list as $_vip_grade_report_id => $_upgraded_info ){
					if( ! empty($_upgraded_info['deposit_amount_in_formula'][1]) && $_isNeedUpdateDepositLimit){
						$upgraded_condition['deposit_amount'] += $_upgraded_info['deposit_amount_in_formula'][1];
					}
				}
			} // EOF if( ! empty($_upgraded_list) ){...

			if($_isNeedUpdateDepositLimit){
				$formula['deposit_amount'][1] += $upgraded_condition['deposit_amount'];
			}

		}// EOF if( $settingInfoHarshInUpGraded['total_deposit'] ){...

		if( $settingInfoHarshInUpGraded['total_bet'] ){
			$upgraded_condition_amounts = [];
			$_isNeedUpdateTotalBetLimit = false;
			if( ! empty($formula['bet_amount']) ){
				$_isNeedUpdateTotalBetLimit = true;
				$upgraded_condition['bet_amount'] = 0;
			}
			if( ! empty($_upgraded_list) ){
				foreach($_upgraded_list as $_vip_grade_report_id => $_upgraded_info ){
					if( ! empty($_upgraded_info['bet_amount_in_formula'][1]) && $_isNeedUpdateTotalBetLimit){
						$upgraded_condition['bet_amount'] += $_upgraded_info['bet_amount_in_formula'][1];
					}
				}
			} // EOF if( ! empty($_upgraded_list) ){...
			if($_isNeedUpdateTotalBetLimit){
				$formula['bet_amount'][1] += $upgraded_condition['bet_amount'];
			}
		}// EOF if( $settingInfoHarshInUpGraded['total_bet'] ){...

		$formulaStr = json_encode($formula);
		$separatedBetSettingStr = json_encode($separatedBetSetting);
		$parsedFormula = $this->parseSetStrListWithFormulaV2($formulaStr, $separatedBetSettingStr );
		// $parsedFormula like as "game_type_id_324 >= 22 and game_type_id_325 >= 123 or game_platform_id_8 >= 123 or game_platform_id_24 >= 11".
		$resultFormula = $parsedFormula['formula']; // will applied real amount
		$resultFormula4human = $parsedFormula['formula4human'];
		$game_id4human_list = $parsedFormula['game_id4human_list'];

		/// OGP-24373 處理 19332.6181.parsedFormula 參考 betEnforcedDetails, depositEnforcedDetails
		if( !empty($betEnforcedDetails)){
			$_parsedFormula = $parsedFormula;
			if($betEnforcedDetails['isMet'] == true){
				$this->utils->debug_log('OGP-24373.6253.betEnforcedDetails', $betEnforcedDetails);
				if( ! empty($betEnforcedDetails['details']['remark'])
					&& is_string($betEnforcedDetails['details']['remark'])
				){
					$isValidJon = $this->utils->isValidJson($betEnforcedDetails['details']['remark']);
					if($isValidJon){
						$betEnforcedDetails['details']['remark'] = json_decode($betEnforcedDetails['details']['remark'], true);
					}
				}
				if( !empty($_parsedFormula['parsedFormula']) ){
					$amount_name = 'bet_amount';
					$amount_name4human = 'Bet';
					// $amount_keyword = 'bet_amount';
					$_keyIndex = $this->group_level_lib->searchKeywordInParsedFormula($amount_name, $parsedFormula['parsedFormula']);
					if( $_keyIndex !== null){ // find out
						// replace the condition,"bet_amount >= 99999999" to "(bet_amount >= 99999999 || true)"
						// replace the condition,"and deposit_amount >= 999999999" to "and (deposit_amount >= 999999999 || true)"
						if( ! empty($betEnforcedDetails['details']['remark']) ){ // source form $_reasonDetails['remark']
							$replaceToCondition4ParsedFormula = 'true';
							$this->utils->debug_log('OGP-25082.6264.betEnforcedDetails.remark:', $betEnforcedDetails['details']['remark']);
						}
						$parsedFormula['parsedFormula'][$_keyIndex] = $this->group_level_lib->wrapAppendedBoolInAmountCondition($parsedFormula['parsedFormula'][$_keyIndex], $amount_name, $replaceToCondition4ParsedFormula);

						// $_string = $parsedFormula['parsedFormula'][$_keyIndex] ;
						// $_pattern = '/[and|or ]?('.$amount_keyword.'\s?[>=<]{1,2}\s?\S+)/i'; // ref. to https://regex101.com/r/aZMUUx/1
						// $_replacement = '( ${1} || true )';
						// $parsedFormula['parsedFormula'][$_keyIndex] = preg_replace($_pattern, $_replacement, $_string);
						// $parsedFormula['formula'] = preg_replace($_pattern, $_replacement, $parsedFormula['formula']);
					}
					if( ! empty($betEnforcedDetails['details']['remark']) ){ // source form $_reasonDetails['remark']
						$replaceToCondition4Formula = 'true';
						if( !empty($betEnforcedDetails['details']['remark']['rlt']) ){
							$replaceToCondition4Formula = $betEnforcedDetails['details']['remark']['rlt'];
						}
						$replaceToCondition4Formula4human = 'true';
						if( !empty($betEnforcedDetails['details']['remark']['rule4human']) ){
							$replaceToCondition4Formula4human = $betEnforcedDetails['details']['remark']['rule4human'];
						}
					}
					$parsedFormula['formula'] = $this->group_level_lib->wrapAppendedBoolInAmountCondition($parsedFormula['formula'], $amount_name, $replaceToCondition4Formula);
					$parsedFormula['formula4human'] = $this->group_level_lib->wrapAppendedBoolInAmountCondition($parsedFormula['formula4human'], $amount_name4human, $replaceToCondition4Formula4human);
				}
			}
		} // EOF if( !empty($betEnforcedDetails)){...

		if( !empty($depositEnforcedDetails)){
			$_parsedFormula = $parsedFormula;
			if($depositEnforcedDetails['isMet'] == true){
				$this->utils->debug_log('OGP-24373.6291.depositEnforcedDetails', $depositEnforcedDetails);
				if( ! empty($depositEnforcedDetails['details']['remark'])
					&& is_string($depositEnforcedDetails['details']['remark'])
				){
					$isValidJon = $this->utils->isValidJson($depositEnforcedDetails['details']['remark']);
					if($isValidJon){
						$depositEnforcedDetails['details']['remark'] = json_decode($depositEnforcedDetails['details']['remark'], true);
					}
				}

				if( !empty($_parsedFormula['parsedFormula']) ){
					$amount_name = 'deposit_amount';
					$amount_name4human = 'Deposit';
					$_keyIndex = $this->group_level_lib->searchKeywordInParsedFormula($amount_name, $parsedFormula['parsedFormula']);
					if( $_keyIndex !== null){ // find out
						$replaceToCondition4ParsedFormula = 'true';
						if( ! empty($depositEnforcedDetails['details']['remark']) ){
							$replaceToCondition4ParsedFormula = 'true';
						}
						$parsedFormula['parsedFormula'][$_keyIndex] = $this->group_level_lib->wrapAppendedBoolInAmountCondition($parsedFormula['parsedFormula'][$_keyIndex], $amount_name, $replaceToCondition4ParsedFormula);
					}
					$replaceToCondition4Formula = 'true';
					if( !empty($depositEnforcedDetails['details']['remark']['rlt']) ){
						$replaceToCondition4Formula = $depositEnforcedDetails['details']['remark']['rlt'];
					}
					$replaceToCondition4Formula4human = 'true';
					if( !empty($depositEnforcedDetails['details']['remark']['rule4human']) ){
						$replaceToCondition4Formula4human = $depositEnforcedDetails['details']['remark']['rule4human'];
					}
					$parsedFormula['formula'] = $this->group_level_lib->wrapAppendedBoolInAmountCondition($parsedFormula['formula'], $amount_name, $replaceToCondition4Formula);
					$parsedFormula['formula4human'] = $this->group_level_lib->wrapAppendedBoolInAmountCondition($parsedFormula['formula4human'], $amount_name4human, $replaceToCondition4Formula4human);
				}
			}
		} // EOF if( !empty($depositEnforcedDetails)){...

		/// re-assign
		$resultFormula = $parsedFormula['formula']; // will applied real amount
		$resultFormula4human = $parsedFormula['formula4human'];
		$game_id4human_list = $parsedFormula['game_id4human_list'];

		$this->PLAH['result_formula_detail'] = [];
		$this->PLAH['result_formula_detail']['betGameIdKeyValList'] = []; // for separatedBetSetting
		if(is_array($bet) && ! empty($bet) ){
			$_betGameIdKeyValList = [];
			if(! empty($bet)){
				foreach($bet as $indexNumber => $currBet){
					switch($currBet['type']){
						case 'game_platform':
							$_gameIdKey = 'game_platform_id_'. $currBet['game_platform_id'];
						break;
						case 'game_type':
							$_gameIdKey = 'game_type_id_'. $currBet['game_type_id'];
						break;
					}
					$_betGameIdKeyValList[$_gameIdKey] = $currBet[$_gameIdKey];
				}
			}

// $this->utils->debug_log('OGP-19332.3772._betGameIdKeyValList', $_betGameIdKeyValList);
			$_betGameIdKeyValList = array_filter($_betGameIdKeyValList, function($value) {
											return isset($value);
									});
			uksort($_betGameIdKeyValList, function($a, $b) {
				$alen = strlen($a);
				$blen = strlen($b);
				if ($alen == $blen) {
					return 0;
				}
				return ($alen < $blen) ? 1 : -1;
			});
// $this->utils->debug_log('OGP-19825.OGP-19332.3776._betGameIdKeyValList', $_betGameIdKeyValList);
/// OGP-19825(OGP-19332) 這邊要把 key 字串較長的，優先取代！所以 key 字串較長，會在前面，愈短的愈後面。
// 因為當設定有「 game_platform_id_1":555,"game_platform_id_1002":123 」，則 會誤判 「game_platform_id_1002」取代成 「555002」。
// 已驗證「9825.OGP-19332.3776._betGameIdKeyValLis」在 debug_log 中，應該要將比較長的鍵值往前排列。!!!

			if( ! empty($_betGameIdKeyValList ) ){
				foreach ($_betGameIdKeyValList as $indexString => $_betGameIdVal) {
// $this->utils->debug_log('OGP-19332.indexString', $indexString);
// $this->utils->debug_log('OGP-19332._betGameIdVal', $_betGameIdVal);
					$findme = $indexString;
					$replaceTo = $_betGameIdVal;
// $this->utils->debug_log('OGP-19332.findme', $findme);
// $this->utils->debug_log('OGP-19332.replaceTo', $replaceTo);
					$resultFormula = str_replace($findme, $replaceTo, $resultFormula);
				}
				$this->PLAH['result_formula_detail']['betGameIdKeyValList'] = $_betGameIdKeyValList;
			}
// $this->utils->debug_log('OGP-19332.6229.resultFormula',$resultFormula);

		}else{
			if( empty($bet) ){
				$bet = 0;
			}
			// for bet total amount
			$findme = 'bet_amount';
			$replaceTo = !empty($bet)? $bet: 0;
			$resultFormula = str_replace($findme, $replaceTo, $resultFormula);

			$this->PLAH['result_formula_detail']['bet_amount'] = $bet;
		} // EOF if(is_array($bet) && ! empty($bet) ){...


		$findme = 'deposit_amount';
		if(empty($deposit)){
			$deposit = 0;
		}
		$replaceTo = $deposit;
		$resultFormula = str_replace($findme, $replaceTo, $resultFormula);
		$this->PLAH['result_formula_detail'][$findme] = $deposit;
		$findme = 'loss_amount';
		if(empty($loss)){
			$loss = 0;
		}
		$replaceTo = $loss;
		$resultFormula = str_replace($findme, $replaceTo, $resultFormula);
		$this->PLAH['result_formula_detail'][$findme] = $loss;
		$findme = 'win_amount';
		if(empty($win)){
			$win = 0;
		}
		$replaceTo = $win;
		$resultFormula = str_replace($findme, $replaceTo, $resultFormula);
		$this->PLAH['result_formula_detail'][$findme] = $win;

		// $resultFormula = $parsedFormula['formula']; // will applied real amount
		// game_type_id_
		// $parsedFormula['parsedFormula']
// $thegenerateCallTrace = $this->utils->generateCallTrace();
// $this->utils->debug_log('OGP-19332.3968.thegenerateCallTrace', $thegenerateCallTrace);
// $this->utils->debug_log('OGP-19332.bet', $bet);
// $this->utils->debug_log('OGP-19332.parsedFormula.formula', $parsedFormula['formula'],'parsedFormula:',$parsedFormula['parsedFormula']);
// $this->utils->debug_log('OGP-19332.parsedFormula.resultFormula:',$resultFormula);

		// while lower level setting has more GameIdKey with current level.
		// convert the undefined GameIdKey(,"game_type_id_XXX" or "game_platform_XXX") to Zero.
		$parsedGameIdKeyList = $this->parseGameIdKeyListWithFormulaStr($resultFormula);
		$this->utils->debug_log('OGP-19332.parsedFormula.parsedGameIdKeyList',$parsedGameIdKeyList);
		if( ! empty($parsedGameIdKeyList) ){
			foreach($parsedGameIdKeyList as $indexNumber => $currGameIdKey){
				$findme = $currGameIdKey['gameIdKey'];
				$replaceTo = 0;
				$resultFormula = str_replace($findme, $replaceTo, $resultFormula);
			}
		}

		/// Overide undefined replace to Zero.
		/// In case, there are more setting items than previous.
		// So replace the undefined setting item, ex: "bet_amount" to Zero.
		$findme = 'bet_amount';
		$replaceTo = 0;
		$resultFormula = str_replace($findme, $replaceTo, $resultFormula);

		$logs = $parsedFormula['formula']; // applied option items
		$condition = "return " . $resultFormula . ";"; //	make conditional statement out of string

		// for log into player_level_adjustment_history
		$this->PLAH['formula'] = $this->utils->encodejson($logs);
		$this->PLAH['result_formula'] = $this->utils->encodejson($resultFormula);

		$_remark = [];
		if( ! empty($this->grade_record['remark']) ){ // get the remark of the property,"grade_record"
			$_remark = json_decode($this->grade_record['remark'], true) ;
		}
		// merge in the remark
		$_remark4get = [];
		$_remark4get['rule'] = $logs;
		$_remark4get['rlt'] = $resultFormula;
		$_remark4get['rule4human'] = $resultFormula4human;
		$_remark4get['game_id4human_list'] = $game_id4human_list;
		if( ! empty($_upgraded_list) ){ /// @TODO !!!! 要看這邊會影響到 $_remark[upgraded_list] ？
			$_remark4get['additived_upgraded_list'] = $_upgraded_list;
		}
		$_remark4get['settingInfoHarshInUpGraded'] = $settingInfoHarshInUpGraded; // for report
		if( !empty($_remark4get) ){
			$_remark = array_merge($_remark, $_remark4get);
		}
		$remark4get = json_encode($_remark); // for param

		if( ! $isDisableSetupRemarkToGradeRecord ){
			$this->setGradeRecord([
				'remark' => $remark4get
			]);
		}

		$this->utils->debug_log('OGP-21051.5569.condition', $condition, 'remark4get:', $remark4get);
		$result = eval($condition);
		return $result ? true : false;
	}// EOF generateUpDownFormulaWithBetAmountSetting



	/**
	 * The script will query common bet amount and check the formula to return the result.
	 * It will execute getPlayerTotalBetWinLossBySchedule than return the result of generateUpDownFormulaWithBetAmountSetting().
	 *
	 * @param integer $playerId The field, "player.playerId".
	 * @param string $fromDatetime The begin datetime of the amount.
	 * @param string $toDatetime The end datetime of the amount.
	 * @param array $formula Assign the BET amount for generateUpDownFormulaWithBetAmountSetting().
	 * @param string $adjustGradeTo For get period, daily, weekly or monthly.
	 * @param point,array $_remark
	 * @param point,array $_gameLogData
	 * @param array $settingInfoHarshInUpGraded @see Group_level::generateUpDownFormulaWithBetAmountSetting()
	 * @return bool $_isConditionMet the result of generateUpDownFormulaWithBetAmountSetting().
	 */
	public function _macroGetPlayerTotalBetWinLossByScheduleWithFormulaAndReturnIsMet( $playerId // #1
																						, $fromDatetime // #2
																						, $toDatetime // #3
																						, $formula // #4
																						, $adjustGradeTo = 'up' // #5
																						, &$_remark = null // #6
																						, &$_gameLogData = null // #7
																						, $settingInfoHarshInUpGraded = [] // #8
	){
		$_bet_amount = 0;
		if( ! empty($formula['bet_amount']) ){
			$_bet_amount = $formula['bet_amount'];
		}

		/// Get the Formula Info for accumulate mode and datetime range
		$vipSettingId = $this->getPlayerLevelId($playerId); // vipsettingcashbackrule.vipsettingcashbackruleId
		$_formulaInfo = $this->utils->getFormulaInfoByVipsettingcashbackruleId($vipSettingId);
		if($adjustGradeTo == 'up'){ // for upgrade
			$theFormulaInfo = $_formulaInfo['upgrade'];
		}else{ // for downgrade
			$theFormulaInfo = $_formulaInfo['downgrade'];
		}

		// This is the 1st query, that's also be default result.
		$this->utils->debug_log('OGP-24373.10125.theFormulaInfo',$theFormulaInfo);
		// $fromDatetime = $duringDatetime['from'];  // "Last Changed Level "OR "Previous Period" by Group_level::getDuringDatetimeFromAccumulationByPlayer()
		// $toDatetime = $duringDatetime['to'];
		$_schedule4reset = $theFormulaInfo['period_json_decoded'];
        $_gameLogData = $this->utils->getPlayerTotalBetWinLossBySchedule( $playerId // #1
                                                                        , $fromDatetime // #2
                                                                        , $toDatetime // #3
                                                                        , $_schedule4reset // #4
                                                                        , $this->enable_multi_currencies_totals // #5
                                                                    ); // as 1st check amount
			$this->utils->debug_log('OGP-24373.6452._gameLogData',$_gameLogData, 'fromDatetime:', $fromDatetime, 'toDatetime:', $toDatetime, '_schedule4reset:', $_schedule4reset);

		// $formula only for bet, for get the result of the condition.
		$_formula = [];
		$_formula['bet_amount'] = $_bet_amount;
		$_gameLogData4bet = $_gameLogData['total_bet'];
		$_deposit = 0;
		$_total_loss = 0;
		$_total_win = 0;
		$_bet_amount_settings = [];
		$isDisableSetupRemarkToGradeRecord = true;
		// $_remark = null; // $_remark['rlt']
			$this->utils->debug_log('OGP-24373.10127.bet_amount._formula',$_formula);
		$_isConditionMet = $this->generateUpDownFormulaWithBetAmountSetting( $_formula // #1
																, $_gameLogData4bet // #2
																, $_deposit // #3
																, $_total_loss // #4
																, $_total_win // #5
																, $_bet_amount_settings  // #6
																, $isDisableSetupRemarkToGradeRecord // #7
																, $_remark // #8
																, [] // #9
																, [] // #10
																, $settingInfoHarshInUpGraded // #11
															);

		return $_isConditionMet;
	} // EOF _macroGetPlayerTotalBetWinLossByScheduleWithFormulaAndReturnIsMet

	/**
	 * The script will query Deposit and check the formula to return the result.
	 *
	 * @param integer $playerId The field, "player.playerId".
	 * @param string $fromDatetime The begin datetime of the amount.
	 * @param string $toDatetime The end datetime of the amount.
	 * @param array $formula Assign the DEPOSIT amount for generateUpDownFormulaWithBetAmountSetting().
	 * @param array $settingInfoHarshInUpGraded The format as the follwings,
	 * - $settingInfoHarshInUpGraded['total_deposit'] bool
	 * - $settingInfoHarshInUpGraded['total_bet'] bool
	 * @return void
	 */
	public function _macroGetTotalDepositWithdrawalBonusCashbackByPlayersWithFormulaAndReturnIsMet( $playerId // #1
																									, $fromDatetime // #2
																									, $toDatetime // #3
																									, $formula // #4
																									, &$_remark = null // #5
																									, &$_deposit = null // #6
																									, $settingInfoHarshInUpGraded = [] // #7
	){

		$this->load->model('transactions');

		$_formula_deposit_amount = 0;
		if( ! empty($formula['deposit_amount']) ){ // for default, the deposit_amount of formula in the level
			$_formula_deposit_amount = $formula['deposit_amount'];
		}

		if($_deposit === null){
            $add_manual = false; // as default
            list($_deposit) = $this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers($playerId, $fromDatetime, $toDatetime, $add_manual, $this->enable_multi_currencies_totals);
		}
		// $deposit = $_deposit; // as 1st check amount.
		$_formula = [];
		$_formula['deposit_amount'] = $_formula_deposit_amount;

		$_gameLogData4bet = 0;
		$_total_loss = 0;
		$_total_win = 0;
		$_bet_amount_settings = [];
		$isDisableSetupRemarkToGradeRecord = true;
		$_remark = null; // $_remark['rlt']
			$this->utils->debug_log('OGP-24373.6501.deposit_amount._formula', $_formula, 'fromDatetime:', $fromDatetime, 'toDatetime:', $toDatetime, '_deposit:'. $_deposit);
		$_isConditionMet = $this->generateUpDownFormulaWithBetAmountSetting( $_formula // #1
																, $_gameLogData4bet // #2
																, $_deposit // #3
																, $_total_loss // #4
																, $_total_win // #5
																, $_bet_amount_settings  // #6
																, $isDisableSetupRemarkToGradeRecord // #7
																, $_remark // #8
																, [] // #9
																, [] // #10
																, $settingInfoHarshInUpGraded // #11
															);

		return $_isConditionMet;
	} // EOF _macroGetTotalDepositWithdrawalBonusCashbackByPlayersWithFormulaAndReturnIsMet

	/**
	 * Generate formula
	 *
	 * return boolean
	 */
	public function generateUpDownFormula($formula, $bet, $deposit, $loss, $win) {
		$result = $logs = '';
		$option = 0;

		foreach ($formula as $key => $val) {
			if (is_array($val)) {
				if ($key == 'bet_amount') {
					$option = $bet;
				} elseif ($key == 'deposit_amount') {
					$option = $deposit;
				} elseif ($key == 'loss_amount') {
					$option = $loss;
				} elseif ($key == 'win_amount') {
					$option = $win;
				}
				$conjunction = $val[0];
				$amount = $val[1];
				$result .= $option . ' ' . $conjunction . ' ' . $amount;
				$logs .= $key . ' ' . $conjunction . ' ' . $amount;
			} else {
				if (strpos($key, 'operator') !== false) {
					$result .= ' ' . $val . ' ';
					$logs .= ' ' . $val . ' ';
				}
			}
		}

		$condition = "return " . $result . ";"; //	make conditional statement out of string

		$this->setGradeRecord([
			'remark' => json_encode([
				'rule' => $logs,
				'rlt'  => $result,
			])
		]);

		return eval($condition) ? true : false;
	}

	/**
	 * detail: check if player is can withdraw once
	 *
	 * @param int $playerId
	 * @return array
	 */
	public function isOneWithdrawOnly($playerId) {
		$this->db->select('vipsettingcashbackrule.one_withdraw_only')->from($this->levelTable)
			->join('player', 'player.levelId=vipsettingcashbackrule.vipsettingcashbackruleId')
			->where('player.playerId', $playerId);

		return $this->runOneRowOneField('one_withdraw_only');

	}

	/**
	 * Check if a vip class/group (specified by $vipsettingcashbackruleId) is allowed to perform cashback
	 *
	 * @param	int		$vipsettingcashbackruleId	self-explanatory
	 * @return	bool
	 */
	public function isAllowedCashback($vipsettingcashbackruleId) {
		$this->db
			->select('vipsettingcashbackrule.can_cashback')
			->from($this->levelTable)
			->where('vipsettingcashbackruleId', $vipsettingcashbackruleId);
		$raw = $this->runOneRowOneField('can_cashback');

		// As can_cashback is defined enum, type of $raw will be string
		// Convert the return to bool here
		$ret = $raw == 'true' ? true : false;

		return $ret;
	}

	/**
	 * detail: get vip setting game type and description
	 *
	 *
	 * @see MOVE TO getCashbackPercentage
	 *
	 * @param int $vipsettingcashbackruleId
	 * @return array
	 */
	public function getVipSettingsGamesTypeAndGameDesc($vipsettingcashbackruleId) {
		$this->db->select('game_description.game_platform_id,game_description.game_type_id, game_description.id as game_description_id, vipsetting_cashback_game.percentage, vipsetting_cashback_game.game_platform_percentage, vipsetting_cashback_game.game_type_percentage, vipsetting_cashback_game.game_desc_percentage')
			->from('vipsetting_cashback_game')
			->join('game_description', 'game_description.id = vipsetting_cashback_game.game_description_id', 'left')
			->where('vipsetting_cashback_game.vipsetting_cashbackrule_id', $vipsettingcashbackruleId);

		$rows = $this->runMultipleRowArray();

		$gamePlatformList = array();
		$gameTypeList = array();
		$gameDescList = array();

		if (!empty($rows)) {
			foreach ($rows as $row) {
				if (!empty($row['game_platform_percentage'])) {
					$gamePlatformList[$row['game_platform_id']] = $row['game_platform_percentage'];
				}
				if (!empty($row['game_type_percentage'])) {
					$gameTypeList[$row['game_type_id']] = $row['game_type_percentage'];
				}
				if (!empty($row['game_desc_percentage'])) {
					$gameDescList[$row['game_description_id']] = $row['game_desc_percentage'];
				}
			}
		}

		return array($gamePlatformList, $gameTypeList, $gameDescList);
	}

	/**
	 * detail: get game tree for a certain group level
	 *
	 * @param int $vipgrouplevelId
	 * @return void
	 */
	public function getGameTreeForGroupLevel($vipgrouplevelId, $filterColumn=array()) {
		$this->load->model(array('game_description_model'));

		list($gamePlatformList, $gameTypeList, $gameDescList) = $this->getCashbackPercentageMap($vipgrouplevelId);

		$showGameDescTree = $this->config->item('show_particular_game_in_tree');

		return $this->game_description_model->getGameTreeArray($gamePlatformList, $gameTypeList, $gameDescList, true, $showGameDescTree, $filterColumn);
	}

	public function getGameTreeForGroupLevel2($vipgrouplevelId) {
		$this->load->model(array('game_description_model'));

		list($gamePlatformList, $gameTypeList, $gameDescList) = $this->getCashbackPercentageMap($vipgrouplevelId);

		$showGameDescTree = $this->config->item('show_particular_game_in_tree');

		return $this->game_description_model->getGameTreeArray2($gamePlatformList, $gameTypeList, $gameDescList, true, $showGameDescTree);
	}

	/**
	 * detail: tree for agency game rolling comm setting
	 *
	 * @param int $agent_id
	 * @return array
	 */
	public function get_agency_game_rolling_comm_and_desc($agent_id) {
		$this->db->select('game_description.game_platform_id,game_description.game_type_id, game_description.id as game_description_id, agency_game_rolling_comm.game_platform_percentage, agency_game_rolling_comm.game_type_percentage, agency_game_rolling_comm.game_desc_percentage')
			->from('agency_game_rolling_comm')
			->join('game_description', 'game_description.id = agency_game_rolling_comm.game_description_id', 'left')
			->where('agency_game_rolling_comm.agent_id', $agent_id);
		$rows = $this->runMultipleRowArray();

		$gamePlatformList = array();
		$gameTypeList = array();
		$gameDescList = array();

		if (!empty($rows)) {
			foreach ($rows as $row) {
				$gamePlatformList[$row['game_platform_id']] = $row['game_platform_percentage'];
				$gameTypeList[$row['game_type_id']] = $row['game_type_percentage'];
				$gameDescList[$row['game_description_id']] = $row['game_desc_percentage'];
			}
		}

		return array($gamePlatformList, $gameTypeList, $gameDescList);
	}

	/**
	 * detail: get agency game rolling comm tree
	 *
	 * @param int $agent_id
	 * @return void
	 */
	public function get_agency_game_rolling_comm_tree($agent_id) {
		$this->load->model(array('game_description_model'));

		list($gamePlatformList, $gameTypeList, $gameDescList) = $this->get_agency_game_rolling_comm_and_desc($agent_id);

		$showGameDescTree = $this->config->item('show_particular_game_in_tree');

		return $this->game_description_model->getGameTreeArray($gamePlatformList, $gameTypeList, $gameDescList, true, $showGameDescTree);
	}

    public function getPlayerBetByCancelledWithdrawCondition($unfinished_wc_player = null){
        $playerBetByCancelledWithdrawConditions = [];
        $exclude_wc_id = [];

        $playerCancelledWithdrawConditionsDateRangeAndGame = $this->getCancelledWithdrawConditionsDateRangeAndGame($unfinished_wc_player);

        if(empty($playerCancelledWithdrawConditionsDateRangeAndGame)) {
            return array($playerBetByCancelledWithdrawConditions, $exclude_wc_id);
        }

        $this->load->model(['total_player_game_hour']);

        foreach($playerCancelledWithdrawConditionsDateRangeAndGame as $wc_player => $wc_info){
            $from_time = $wc_info['from_time'];
            $to_time = $wc_info['to_time'];
            $gameDescriptionId = $wc_info['game_description_id'];

            $exclude_wc_id = array_merge($exclude_wc_id, $wc_info['wc_id']);    //get all exclude wc ids

            $betRecord = $this->total_player_game_hour->getAllRecordOfPlayer($from_time, $to_time, $wc_player, $gameDescriptionId);
            if(!empty($betRecord)){
                $playerBetByCancelledWithdrawConditions[$wc_player] = $betRecord;
            }
        }

        $this->utils->debug_log('playerBetByCancelledWithdrawConditions', $playerBetByCancelledWithdrawConditions, 'exclude_wc_id', $exclude_wc_id);

        return array($playerBetByCancelledWithdrawConditions, $exclude_wc_id);
	}

	/**
	 * Dry Check Upgrade
	 *
	 * vip_upgrade_setting
	 * remark.isMetOffsetRules
	 * isMetOffsetRules
	 *
	 * @param integer $playerId The field, "player.playerId".
	 * @param array $levelMap
	 * - $levelMap[vipsettingcashbackruleId] = the row of the data table,"vipsettingcashbackrule".
	 * @param boolean $isBatchUpgrade If true, it's mean here are more one player in process.
	 * @param boolean $checkHourly If true means trigger from cronjob, Command::batch_player_level_upgrade_check_hourly()
	 * @param string $time_exec_begin The $time_exec_begin from param of self::playerLevelAdjust().
	 * @param integer $theOffsetAmountOfNextUpgrade The level offset amount for dry check.
	 * @return array $newVipLevelData The array need conatins the followings,
	 * - $newVipLevelData['insertBy'] stringFor debugging/tracking the update source, ex:'dryCheckEmptyUpgradeSched'.
	 * - $newVipLevelData['remark'] array ;
	 * - $newVipLevelData['isConditionMet'] boolean If true, it's mean met the condition for up/down grade.
	 */
	public function dryCheckUpgrade(	$playerId // #1
									, $levelMap // #2
									, $isBatchUpgrade // #3
									, $checkHourly // #4
									, $time_exec_begin // #5
									, $theOffsetAmountOfNextUpgrade = 1 // #6
									, $setHourly = false // from hourly cronjob // #7
	){
		$remark = [];
		/// Cloned from "need to reload"
		$player = $this->player_model->getPlayerById($playerId); // reload player detail
		$macroReturn = $this->_macroUpgradeSettingReturnFlagsSetting( $player, $levelMap );
		$playerLevel = $macroReturn['playerLevel'];
		$isAccumulation = $macroReturn['isAccumulation'];
		$isCommonAccumulationModeFromRegistration = $macroReturn['isCommonAccumulationModeFromRegistration']; // from _macroUpgradeSettingReturnFlagsSetting()
		$isSeparateAccumulation = $macroReturn['isSeparateAccumulation'];
		$isBetAmountSettings = $macroReturn['isBetAmountSettings'];
		$schedule = $macroReturn['schedule'];
		$setHourly = $macroReturn['setHourly'];
		$setting = $macroReturn['setting'];
		$bet_amount_settingsStr = $macroReturn['bet_amount_settingsStr'];

		$macroReturn = $this->_macroIsMetOffsetRulesV2( $player, $levelMap, $time_exec_begin );
		$isUseOffsetUpGradeRulesAfterDownGrade = $macroReturn['isUseOffsetUpGradeRulesAfterDownGrade'];
		$isMetOffsetRules = $macroReturn['isMetOffsetRules'];

		$upgradeSched = $this->getScheduleDateRange($schedule, 1, $isBatchUpgrade, $setHourly, $time_exec_begin);

		if (empty($upgradeSched['dateFrom']) || empty($upgradeSched['dateTo'])) {
			/// OGP-21051 Handle the case. still generate data.
			// It should be impossible executed into  here, because after success upgrade still mat the moment of to execute upgrade
			// But in the case, the Period is defference with next level setting. That still into here!!
			//
			$result = array('error' => 'Check schedule. Player upgrade still processing!!');
			$this->utils->debug_log('(dryCheckUpgrade)Check schedule. Player upgrade still processing!!', $playerId);

			$_remark = null;
			$newVipLevelData = [];
			$newVipLevelData['insertBy'] = 'dryCheckEmptyUpgradeSched';
			$newVipLevelData['remark'] = $_remark;
			$newVipLevelData['isConditionMet'] = false;
			$newVipLevelData['result'] = $result;
			return $newVipLevelData;
		}

		$macroReturn = $this->_macroPreviousMomentsByAccumulation( $isAccumulation // # 1
								, $player // # 2
								, $setting // # 3
								, $schedule // # 4
								, $playerLevel // # 5
								, $time_exec_begin // # 6
								, $upgradeSched // # 7
								, $setHourly // # 8
								, $isBatchUpgrade // # 9
							);
		$fromDatetime = $macroReturn['fromDatetime'];
		$toDatetime = $macroReturn['toDatetime'];
		$previousFromDatetime = $macroReturn['previousFromDatetime'];
		$previousToDatetime = $macroReturn['previousToDatetime'];

		$initialAmount = $this->_macroGetInitialAmountV2( $isMetOffsetRules // # 1
								, $player // # 2
								, $setting // # 3
								, $levelMap // # 4
								, $time_exec_begin // # 5
						);
		$macroReturn = $this->_macroGetGameLogDataAndDeposit( $isSeparateAccumulation
								, $setting
								, $player
								, $time_exec_begin
								, $playerLevel
								, $isBatchUpgrade
								, $upgradeSched
								, $schedule
								, $setHourly
								, $bet_amount_settingsStr
								, $initialAmount
								, $fromDatetime
								, $toDatetime
								, $isMetOffsetRules
							);
		$gameLogData = $macroReturn['gameLogData'];
		$deposit = $macroReturn['deposit'];
		$calcResult = $macroReturn['calcResult'];
		/// EOF Cloned from "need to reload"

		$vip = null;
$this->utils->debug_log('OGP-21051.5738.dryCheckUpgrade.gameLogData', $gameLogData);
		$newVipLevelId = $this->getVipLevelIdByLevelNumber($playerLevel['vipSettingId'], $playerLevel['vipLevel'] + $theOffsetAmountOfNextUpgrade);
		if( !empty($levelMap[$newVipLevelId])){ // for the top level of the VIP Group
			$newVipLevelData = $levelMap[$newVipLevelId];
			$newVipLevelData = array_merge($newVipLevelData, ['vip_upgrade_setting' => $this->getSettingData($newVipLevelData['vip_upgrade_id']) ]);
			$newVipLevelData['groupLevelName'] = lang($newVipLevelData['groupName']). ' - '. lang($newVipLevelData['vipLevelName']);
			$newVipLevelData['insertBy'] = 'dryCheck';
			$new_vip_upgrade_setting = $this->getSettingData($newVipLevelData['vip_upgrade_id']); // vip_upgrade_setting
			$vip = $new_vip_upgrade_setting;
		}else{
			$this->utils->debug_log('OGP-23050.dryCheckUpgrade.getVipLevelIdByLevelNumber.newVipLevelId:', $newVipLevelId, 'levelMap:', $levelMap);
		}

		if( ! empty($vip) ){
			$bet_amount_settings  = [];
			if(!empty($vip['bet_amount_settings'])){
				$bet_amount_settings  = json_decode($vip['bet_amount_settings'], true);
			}

			$formula = json_decode($vip['formula'], true);
			$this->utils->debug_log('-------- (dryCheckUpgrade)upgrade generateUpDownFormula-------', $playerId, $formula, $gameLogData['total_bet'], $deposit, $gameLogData['total_loss'], $gameLogData['total_win']);

			$execCheckConditionMet = true;

			if($checkHourly === true) {
				// vipsettingcashbackrule.period_up_down_2, so $vip should be a row of the data table,"vipsettingcashbackrule".
				$period_up_down_2 = [];
				if( ! empty($vip['period_up_down_2']) ){
					$period_up_down_2 = json_decode($vip['period_up_down_2'], true);
				}

				if(!isset($period_up_down_2['hourly'])) {
					$execCheckConditionMet = false;
				}
				else if($period_up_down_2['hourly'] != true) {
					$execCheckConditionMet = false;
				}
			}
			if( !empty($gameLogData['separated_bet']) ){
				$gameLogData4bet = $gameLogData['separated_bet'];
			} else {
				$gameLogData4bet = $gameLogData['total_bet'];
			}

			$isDisableSetupRemarkToGradeRecord = true;
			$_remark = null;
			$isConditionMet = $this->generateUpDownFormulaWithBetAmountSetting( $formula // #1
																			, $gameLogData4bet // #2
																			, $deposit // #3
																			, $gameLogData['total_loss'] // #4
																			, $gameLogData['total_win'] // #5
																			, $bet_amount_settings  // #6
																			, $isDisableSetupRemarkToGradeRecord // #7
																			, $_remark // #8
																		);
			$remark = $this->utils->decodeJson($_remark);

			if( ! empty($initialAmount) ){
				// $initialAmount
				$remark = array_merge($remark, [ 'initialAmount' => $initialAmount]);
				// $remark = [];
				// if( ! empty($this->grade_record['remark']) ){
				// 	$remark = json_decode($this->grade_record['remark'], true) ;
				// }
				// $remark = array_merge($remark, [ 'initialAmount' => $initialAmount]);
				// $this->setGradeRecord([
				// 	'remark' => json_encode($remark)
				// ]);
			}

			if($isSeparateAccumulation){
				// merge remark of vip_grade_report
				$separate_accumulation_settings = $setting['separate_accumulation_settings'];
				$remark = array_merge($remark, [ 'separate_accumulation_settings' => $separate_accumulation_settings]);
				// $remark = [];
				// if( ! empty($this->grade_record['remark']) ){
				// 	$remark = json_decode($this->grade_record['remark'], true) ;
				// }
				//
				// $separate_accumulation_settings = $setting['separate_accumulation_settings'];
				// $remark = array_merge($remark, [ 'separate_accumulation_settings' => $separate_accumulation_settings]);
				//
				// $remark = array_merge($remark, [ 'separate_accumulation_calcResult' => $calcResult]);
				//
				// $this->setGradeRecord([
				// 	'remark' => json_encode($remark)
				// ]);
			}// EOF if($isSeparateAccumulation)


			/// for support parse $calcResult and display in the report.
			if( ! empty($calcResult) ){
				// merge remark of vip_grade_report
				$remark = array_merge($remark, [ 'separate_accumulation_calcResult' => $calcResult ]);
				// $remark = [];
				// if( ! empty($this->grade_record['remark']) ){
				// 	$remark = json_decode($this->grade_record['remark'], true) ;
				// }
				//
				// $remark = array_merge($remark, [ 'separate_accumulation_calcResult' => $calcResult ]);
				// $this->setGradeRecord([
				// 	'remark' => json_encode($remark)
				// ]);


				$vipGroupLevelName = lang($playerLevel['groupName']) . ' - ' . lang($newVipLevelData['vipLevelName']);
				$remark = array_merge($remark, [ 'groupLevelName' => $vipGroupLevelName]);

				$setting_name = $vip['setting_name']; // vip_upgrade_setting.setting_name
				$remark = array_merge($remark, [ 'setting_name' => $setting_name]);

				$remark = array_merge($remark, [ 'separate_accumulation_calcResult' => $calcResult ]);
			}

			if($execCheckConditionMet === false) {
				$this->utils->debug_log('================(dryCheckUpgrade)directly set isConditionMet = false because this vip ip not using hourly check', $vip);
				$isConditionMet = false;
			}
			$this->utils->debug_log('(dryCheckUpgrade)upgrade isConditionMet', $playerId, $isConditionMet);

			$newVipLevelData['isConditionMet'] = $isConditionMet;
		}else{
			// empty $vip
			$result = array('error' => 'Empty Upgrade Setting of the New Vip Level');
			$this->utils->debug_log('(dryCheckUpgrade)',$result, 'playerId:',$playerId);

			// $newVipLevelData = []; // for "Next VIP Level Name" in the userInformation of SBE.
			$newVipLevelData['insertBy'] = 'dryCheckEmptySettingOfNewVipLevel';
			$newVipLevelData['isConditionMet'] = false;
			$newVipLevelData['result'] = $result;
		} // EOF if( ! empty($vip) ){
		$newVipLevelData['remark'] = $remark;
		return $newVipLevelData;
	} // EOF dryCheckUpgrade

	/**
	 * 降級成功後，依照當下等級的降級公式，去撈上次異動等級時間到當下的數據
	 * 雖然有 保級制度的 阻擋 或 提前降級 的可能。
	 * - 新的保級制度啟用後，就會是忽略當下等級降級的設定
	 * - 舊的保級制度，就是僅支援 (Common) No Accumulation，沒有支援其他狀況。
	 * 但是目前暫時忽略。
	 *
	 * @param integer $playerId The field, "player.playerId".
	 * @param array $levelMap The all Player Levels and key-value array. The format,
	 * - $levelMap[vipsettingcashbackruleId] = the row of the data table,"vipsettingcashbackrule".
	 * @param string $time_exec_begin The $time_exec_begin from param of self::playerLevelAdjustDowngrade().
	 * @param boolean $isBatchDowngrade If true, it's mean here are more one player in process.
	 * @param integer $theOffsetAmountOfNextDowngrade The level offset amount for dry check.
	 * @return void
	 */
	function dryCheckDowngradeLite($playerId, $levelMap, $time_exec_begin, $isBatchDowngrade, $theOffsetAmountOfNextDowngrade = 1){

		$accumulationModeDefault = Group_level::ACCUMULATION_MODE_DISABLE;
		$adjustGradeTo = 'down';
		$remark = [];
		$now = new DateTime($time_exec_begin);
		$isMetOffsetRules = false; // downgrade always disable
		$isForDowngrade = true;
		$player = $this->player_model->getPlayerById($playerId);
		$macroReturn = $this->_macroUpgradeSettingReturnFlagsSetting( $player, $levelMap, $isForDowngrade);
		$playerLevel = $macroReturn['playerLevel'];
		$isAccumulation = $macroReturn['isAccumulation']; // common and have accumulation
		$isSeparateAccumulation = $macroReturn['isSeparateAccumulation'];
		$isBetAmountSettings = $macroReturn['isBetAmountSettings'];
		$schedule = $macroReturn['schedule'];
		$setHourly = $macroReturn['setHourly'];
		$setting = $macroReturn['setting'];
		$bet_amount_settingsStr = $macroReturn['bet_amount_settingsStr'];
$this->utils->debug_log('OGP-21051.6020.dryCheckDowngradeLite.macroReturn', $macroReturn); /// 跑舊的保級制度，會出錯，需要確認：這邊，$macroReturn['schedule']應該會是降級的設定。
		$hasDowngradeSetting = false;
		if( ! empty($playerLevel['vip_downgrade_id']) ){
			// $playerLevel['vip_downgrade_setting'] = $this->getSettingData($playerLevel['vip_downgrade_id']);
			$hasDowngradeSetting = true;
		}

		// overide the $setting for the setting of the target level
		$newVipLevelId = $this->getVipLevelIdByLevelNumber($playerLevel['vipSettingId'], $playerLevel['vipLevel'] - $theOffsetAmountOfNextDowngrade);
		$newVipLevelData = null;
		if( ! empty( $levelMap[$newVipLevelId] ) ){
			$newVipLevelData = $levelMap[$newVipLevelId];
		}

		if( ! empty($newVipLevelData ) ){
			$subNumber = 1;
			$upgradeSched = $this->getScheduleDateRange($schedule, $subNumber, $isBatchDowngrade, $setHourly, $time_exec_begin, $adjustGradeTo);

			if( empty($upgradeSched['dateFrom']) || empty($toDatetime = $upgradeSched['dateTo']) ) {
				// $vip['insertBy'] = 4742;
				// $newVipLevelDataListcounter = count($this->PLAH['playerLevelInfo']['newVipLevelDataList']);
				// $this->PLAH['playerLevelInfo']['newVipLevelDataList'][$newVipLevelDataListcounter] = $vip;
				$remark = [];
				// $remark = $this->utils->decodeJson($_remark);
				// if( ! empty($initialAmount) ){
				// 	// $initialAmount
				// 	$remark = array_merge($remark, [ 'initialAmount' => $initialAmount]);
				// }

				if( !empty($setting['separate_accumulation_settings']) ){
					// merge remark of vip_grade_report
					$separate_accumulation_settings = $setting['separate_accumulation_settings'];
					$remark = array_merge($remark, [ 'separate_accumulation_settings' => $separate_accumulation_settings]);
				}

				// if( !empty($calcResult) ){
				// 	$remark = array_merge($remark, [ 'separate_accumulation_calcResult' => $calcResult]);
				// }
				$newVipLevelData['remark'] = $remark;
				return $newVipLevelData;
			}

			$newVipLevelData['groupLevelName'] = lang($newVipLevelData['groupName']). ' - '. lang($newVipLevelData['vipLevelName']);
			$newVipLevelData['insertBy'] = 'dryCheckDowngradeLite';
	$this->utils->debug_log('OGP-21051.5816.dryCheckDowngradeLite.newVipLevelData', $newVipLevelData);
			$new_vip_upgrade_setting = $this->getSettingData($newVipLevelData['vip_downgrade_id']); // vip_upgrade_setting
			$newVipLevelData['vip_downgrade_setting'] = $new_vip_upgrade_setting;
			$setting = $new_vip_upgrade_setting;
			if( ! empty($setting['bet_amount_settings']) ){
				$isBetAmountSettings = true;
				$bet_amount_settingsStr = $setting['bet_amount_settings'];
			}

			$formula = null;
			$deposit = 0; // default
			$separate_accumulation_settings = null;
			if($isSeparateAccumulation){

				// separate accumulation
				if( ! empty($setting) ){
					$formula = $setting['formula'];
					$separate_accumulation_settings = $setting['separate_accumulation_settings'];
				}

				$playerCreatedOn = $player->createdOn;
				$time_exec_begin = $now->format('Y-m-d H:i:s');
				$subNumber = // fixed 1, because guaranteed_downgrade_period_number only for guaranteed_downgrade_period_total_deposit > 0
				$isBatch = $isBatchDowngrade;
				$setHourly = false; // ignore, in dryCheckDowngradeLite().

				$subNumber = 1; // just get previous datetime range
				$upgradeSched = $this->getScheduleDateRange($schedule, $subNumber, $isBatchDowngrade, $setHourly, $time_exec_begin, $adjustGradeTo);

				$calcResult = $this->calcSeparateAccumulationByExtraSettingList($separate_accumulation_settings // # 1
																				, $formula // # 2
																				, $bet_amount_settingsStr // #2.1
																				, $upgradeSched // # 3
																				, $playerId // # 4
																				, $playerCreatedOn // # 5
																				, $time_exec_begin // # 6
																				, $schedule // # 7
																				, $subNumber // # 8
																				, $isBatch // # 9
																				, $setHourly // # 10
																			);


				// $calcResult convert to $gameLogData
				if( ! empty($calcResult['separated_bet']) ){
					$gameLogData['total_bet'] = null;
					$gameLogData['separated_bet'] = $calcResult['separated_bet'];
				}else if( isset($calcResult['total_bet']) ){
					$gameLogData['total_bet'] = $calcResult['total_bet'];
				}else{
					$gameLogData['total_bet'] = null;
				}
	// if( isset($gameLogData['separated_bet']) ){
	// $this->utils->debug_log('OGP-19332.3151.separated_bet', $gameLogData['separated_bet']);
	// }
				if( isset($calcResult['total_win']) ){
					$gameLogData['total_win'] = $calcResult['total_win'];
				}else{
					$gameLogData['total_win'] = null;
				}

				if( isset($calcResult['total_loss']) ){
					$gameLogData['total_loss'] = $calcResult['total_loss'];
				}else{
					$gameLogData['total_loss'] = null;
				}

				if( isset($calcResult['deposit']) ){
					$deposit = $calcResult['deposit'];
				}else{
					$deposit = null;
				}
			// EOF if($isSeparateAccumulation){...
			}else if( $hasDowngradeSetting ){ // found the downgrade setting
				/// "non-Accumulation" and "common accumulation"
				// for calc the settings in common accumulation,"enable_separate_accumulation_in_setting = false".

				$subNumber = 1; // just get previous datetime range
				$upgradeSched = $this->getScheduleDateRange($schedule, $subNumber, $isBatchDowngrade, $setHourly, $time_exec_begin, $adjustGradeTo);
				if(empty($upgradeSched['dateFrom']) || empty($upgradeSched['dateTo']) ){
					$remark = [];
					if( !empty($setting['separate_accumulation_settings']) ){
						// merge remark of vip_grade_report
						$separate_accumulation_settings = $setting['separate_accumulation_settings'];
						$remark = array_merge($remark, [ 'separate_accumulation_settings' => $separate_accumulation_settings]);
					}
					$newVipLevelData['remark'] = $remark;
					return $newVipLevelData;
				}

/// origial
				$fromDatetime = $upgradeSched['dateFrom'];
				$toDatetime = $upgradeSched['dateTo'];

				/// ===== apply Separate Accumulation function,"calcSeparateAccumulationByExtraSettingList" to Downgrade ====
				// use "separate accumulation" to run "common accumulation"
	// $this->utils->debug_log('OGP-19332.3188.setting:', $setting);
				if( !empty($setting['formula']) ){
					$formula = $setting['formula'];
				}

				if( isset($setting['formula']) ){
					$accumulationMode = (int)$setting['accumulation'];
				}else{
					$accumulationMode = $accumulationModeDefault;
				}

				$playerCreatedOn = $player->createdOn;
				$time_exec_begin = $now->format('Y-m-d H:i:s');
				$subNumber = 1; /// fixed 1, because guaranteed_downgrade_period_number only for guaranteed_downgrade_period_total_deposit > 0
				$isBatch = $isBatchDowngrade;
				// SAS = separate_accumulation_settings
				// for apply to calcSeparateAccumulationByExtraSettingList()
				$_sas = [];
				$_sas['bet_amount'] = [];
				$_sas['bet_amount']['accumulation'] = $accumulationMode;
				$_sas['deposit_amount'] = [];
				$_sas['deposit_amount']['accumulation'] = $accumulationMode;
				$_sas['loss_amount'] = [];
				$_sas['loss_amount']['accumulation'] = $accumulationMode;
				$_sas['win_amount'] = [];
				$_sas['win_amount']['accumulation'] = $accumulationMode;
				$calcResult = $this->calcSeparateAccumulationByExtraSettingList( $_sas // # 1
																					, $formula // # 2
																					, $bet_amount_settingsStr // #2.1
																					, $upgradeSched // # 3
																					, $playerId // # 4
																					, $playerCreatedOn // # 5
																					, $time_exec_begin // # 6
																					, $schedule // # 7
																					, $subNumber // # 8
																					, $isBatch // # 9
																					, $setHourly // # 10
																				);
				// $calcResult convert to $gameLogData
				if( ! empty($calcResult['separated_bet']) ){
					$gameLogData['total_bet'] = null;
					$gameLogData['separated_bet'] = $calcResult['separated_bet'];
				}else if( isset($calcResult['total_bet']) ){
					$gameLogData['total_bet'] = $calcResult['total_bet'];
				}else{
					$gameLogData['total_bet'] = null;
				}
				if( isset($calcResult['total_win']) ){
					$gameLogData['total_win'] = $calcResult['total_win'];
				}else{
					$gameLogData['total_win'] = null;
				}
				if( isset($calcResult['total_loss']) ){
					$gameLogData['total_loss'] = $calcResult['total_loss'];
				}else{
					$gameLogData['total_loss'] = null;
				}
				// ===== EOF apply Separate Accumulation function,"calcSeparateAccumulationByExtraSettingList" to Downgrade ====

				/// origial
				// $total_player_game_table = 'total_player_game_minute';
				// $where_date_field = 'date_minute';
				// $fromDatetime4minute = $this->utils->formatDateMinuteForMysql(new DateTime($fromDatetime));
				// $toDatetime4minute = $this->utils->formatDateMinuteForMysql(new DateTime($toDatetime));
				// $gameLogData = $this->total_player_game_day->getPlayerTotalBetWinLoss($playerId, $fromDatetime4minute, $toDatetime4minute, $total_player_game_table, $where_date_field); // @todo OGP-19332

				// $theGenerateCallTrace = $this->utils->generateCallTrace();
				// $this->utils->debug_log('OGP-21051.6254.theGenerateCallTrace:', $theGenerateCallTrace);
				$this->utils->debug_log('-------- dryCheckDowngradeLite.downgrade get gameLogData and deposit amounts from to ------', $playerId, $fromDatetime, $toDatetime);

                $add_manual=false; // as default
				list($deposit) = $this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers($playerId, $fromDatetime, $toDatetime, $add_manual, $this->enable_multi_currencies_totals);
			// EOF else if( $hasDowngradeSetting ){...
			}

			$upgradeSched = $this->getScheduleDateRange($schedule, 1, $isBatchDowngrade, $setHourly, $time_exec_begin);
			if(empty($upgradeSched['dateFrom']) || empty($upgradeSched['dateTo']) ){
				$remark = [];
				if( !empty($setting['separate_accumulation_settings']) ){
					// merge remark of vip_grade_report
					$separate_accumulation_settings = $setting['separate_accumulation_settings'];
					$remark = array_merge($remark, [ 'separate_accumulation_settings' => $separate_accumulation_settings]);
				}
				$newVipLevelData['remark'] = $remark;
				return $newVipLevelData;
			}

			$macroReturn = $this->_macroPreviousMomentsByAccumulation( $isAccumulation // # 1
									, $player // # 2
									, $setting // # 3
									, $schedule // # 4
									, $playerLevel // # 5
									, $time_exec_begin // # 6
									, $upgradeSched // # 7
									, $setHourly // # 8
									, $isBatchDowngrade // # 9
								);
			$fromDatetime = $macroReturn['fromDatetime'];
			$toDatetime = $macroReturn['toDatetime'];
			$previousFromDatetime = $macroReturn['previousFromDatetime'];
			$previousToDatetime = $macroReturn['previousToDatetime'];

			$initialAmount = $this->_macroGetInitialAmountV2( $isMetOffsetRules // # 1
						, $player // # 2
						, $setting // # 3
						, $levelMap // # 4
						, $time_exec_begin // # 5
				);
			$macroReturn = $this->_macroGetGameLogDataAndDeposit( $isSeparateAccumulation
						, $setting
						, $player
						, $time_exec_begin
						, $playerLevel
						, $isBatchDowngrade
						, $upgradeSched
						, $schedule
						, $setHourly
						, $bet_amount_settingsStr
						, $initialAmount
						, $fromDatetime
						, $toDatetime
						, $isMetOffsetRules
					);
			$gameLogData = $macroReturn['gameLogData'];
			if( !empty($gameLogData['separated_bet']) ){
				$gameLogData4bet = $gameLogData['separated_bet'];
			} else {
				$gameLogData4bet = $gameLogData['total_bet'];
			}



			$vip = $setting;
			$bet_amount_settings = [];
			if(!empty($vip['bet_amount_settings'])){
				$bet_amount_settings  = json_decode($vip['bet_amount_settings'], true);
			}


			$_formula = json_decode($formula, true);
			$isDisableSetupRemarkToGradeRecord = true;
			$_remark = null;
			$isConditionMet = $this->generateUpDownFormulaWithBetAmountSetting( $_formula // #1
																	, $gameLogData4bet // #2
																	, $deposit // #3
																	, $gameLogData['total_loss'] // #4
																	, $gameLogData['total_win'] // #5
																	, $bet_amount_settings  // #6
																	, $isDisableSetupRemarkToGradeRecord // #7
																	, $_remark // #8
																);
// $this->utils->debug_log('dryCheckDowngradeLite.generateUpDownFormulaWithBetAmountSetting.formula:', $formula
// , 'gameLogData4bet:', $gameLogData4bet
// , 'deposit:', $deposit
// , 'gameLogData:', $gameLogData
// , 'bet_amount_settings:', $bet_amount_settings
// , '_remark:', $_remark
// );
			/// @todo Ignore the Guaranteed Downgrade Period and Down Maintain Settings,
			// To disable/enable the feature, "vip_level_maintain_settings" for the function.
			// The pop-up just display the amount. So far, No requirement to spec the part.
			// Reference to,
			// $vip_level_maintain_settings, keyword,"downgrade guaranteed condition check".
			// $isMet4DownMaintain, keyword, "isMet4DownMaintain".

			$remark = $this->utils->decodeJson($_remark);
			if( ! empty($initialAmount) ){
				// $initialAmount
				$remark = array_merge($remark, [ 'initialAmount' => $initialAmount]);
			}

			if( !empty($setting['separate_accumulation_settings']) ){
				// merge remark of vip_grade_report
				$separate_accumulation_settings = $setting['separate_accumulation_settings'];
				$remark = array_merge($remark, [ 'separate_accumulation_settings' => $separate_accumulation_settings]);
			}

			if( !empty($calcResult) ){
				$remark = array_merge($remark, [ 'separate_accumulation_calcResult' => $calcResult]);
			}
		} // EOF if( ! empty($newVipLevelData ) ){..
		$newVipLevelData['remark'] = $remark;
		return $newVipLevelData;

	} // EOF dryCheckDowngradeLite



	/**
	 * detail: get bet for a certain player base on the date range
	 *
	 * @param string $date
	 * @param string $startHour
	 * @param string $endHour
	 * @param int $playerId
	 *
	 * @return array
	 */
	public function getPlayerBetByDate( $date // #1
										, $startHour // #2
										, $endHour // #3
										, $playerId = null // #4
										, $start_date = null // #5
										, $end_date = null // #6
										, $playerIds = null // #7
										, $exclude_game_platform_list = NULL // #8
										, $_group_by = 'tpgh.player_id, tpgh.game_description_id, tpgh.game_platform_id' // #9
	) {
        $this->load->model(array('game_logs'));

        if (!empty($start_date) && !empty($end_date)) {
            $lastDate = str_replace('-', '', $start_date);
            $date = str_replace('-', '', $end_date);
        } else {
            $lastDate = $this->utils->getLastDay($date);

            if (intval($endHour) == 23) {
                //all yesterday
                $date = $lastDate;
            }

            $date = str_replace('-', '', $date);
            $lastDate = str_replace('-', '', $lastDate);
        }
        $this->utils->debug_log(__METHOD__ . '(): start', $lastDate . $startHour, 'end', $date . $endHour);

		$playerQry = '';

		if (!empty($playerIds)) {
			$ids = implode(',', $playerIds);
			$playerQry .= ' and player_id IN ('.$ids.')';
		} else {
            $playerId = intval($playerId);
			if ($playerId) {
				$playerQry .= ' and player_id=' . $playerId;
			}
		}

        $bind_query = '';
		$bind_values = [];

		if( empty($_group_by) ){
			$_group_by = 'tpgh.player_id, tpgh.game_description_id, tpgh.game_platform_id';
		}

		$_suffix = '';
		$suffixInGetPlayerBetByDate = $this->utils->getConfig('suffixInGetPlayerBetByDate');
		if ( ! empty($suffixInGetPlayerBetByDate) ) {
			$_suffix = $suffixInGetPlayerBetByDate;
		}

		// cashback_start_hour to cashback_end_hour
		if (!$this->utils->getConfig('use_total_hour')) {
            if(!empty($exclude_game_platform_list)){
                $bind_query .= " and (";
                $bind_query .= "(tpgh.game_platform_id NOT IN (".implode(',', $exclude_game_platform_list).") and (date_format(tpgh.end_at, '%Y%m%d%H')>=? and date_format(tpgh.end_at, '%Y%m%d%H')<=?))";
                $bind_values[] = $lastDate . $startHour;
                $bind_values[] = $date . $endHour;

                $bind_query .= ")";
            }else{
                $bind_query .= " and (date_format(tpgh.end_at, '%Y%m%d%H')>=? and date_format(tpgh.end_at, '%Y%m%d%H')<=?)";
                $bind_values[] = $lastDate . $startHour;
                $bind_values[] = $date . $endHour;
            }

			$flag = Game_logs::FLAG_GAME;
			$sql = <<<EOD
SELECT tpgh.player_id, player.levelId, sum(tpgh.bet_amount) as betting_total, tpgh.game_description_id, gd.game_type_id, tpgh.game_platform_id
, 'game_logs' as source_table, tpgh.id as source_id
, GROUP_CONCAT(tpgh.id) as source_id_list
  FROM game_logs as tpgh
  join game_description as gd on tpgh.game_description_id=gd.id and gd.no_cash_back!=1
  join player on player.playerId=tpgh.player_id
where flag=$flag
and player.disabled_cashback!=1
{$playerQry}
{$bind_query}
group by {$_group_by}
{$_suffix}
EOD;
		} else {
            if(!empty($exclude_game_platform_list)){
                $bind_query .= " and (";
                $bind_query .= "(tpgh.game_platform_id NOT IN (".implode(',', $exclude_game_platform_list).") and (date_hour>=? and date_hour<=?))";
                $bind_values[] = $lastDate . $startHour;
                $bind_values[] = $date . $endHour;

                $bind_query .= ")";
            }else{
                $bind_query .= " and (date_hour>=? and date_hour<=?)";
                $bind_values[] = $lastDate . $startHour;
                $bind_values[] = $date . $endHour;
            }

			//use total_player_game_hour
			$sql = <<<EOD
SELECT tpgh.player_id, player.levelId, sum(tpgh.betting_amount) as betting_total, tpgh.game_description_id, gd.game_type_id, tpgh.game_platform_id
, 'total_player_game_hour' as source_table, tpgh.id as source_id
, GROUP_CONCAT(tpgh.id) as source_id_list
  FROM total_player_game_hour as tpgh
  join game_description as gd on tpgh.game_description_id=gd.id and gd.no_cash_back!=1
  join player on player.playerId=tpgh.player_id
where player.disabled_cashback!=1
{$playerQry}
{$bind_query}
group by  {$_group_by}
{$_suffix}
EOD;
		}

		$qry = $this->db->query($sql, $bind_values);
		return $this->getMultipleRow($qry);
	}

    public function get_max_min_id_in_game_logs_by_end_at($begin_of_end_at = '2022-12-13 12:00:00', $end_of_end_at = '2023-01-13 11:59:59'){
        // $totalDateQryInMapping .= " AND `bet_logs`.end_at BETWEEN '$_begin_endAtStr' AND '$_end_endAtStr' ";
        $_return_list = [];

        $prop_hash_list = []; // for query once time during a cronjob executed.
        array_push($prop_hash_list, $begin_of_end_at, $end_of_end_at);
        $prop_hash_str = md5(implode('_', $prop_hash_list));
$this->utils->debug_log('OGP-27832.7819.get_max_min_id_in_game_logs_by_end_at.prop_hash_str:', $prop_hash_str);
        if( ! empty($this->max_min_id_in_game_logs_by_end_at[$prop_hash_str])
            && ! empty($this->max_min_id_in_game_logs_by_end_at[$prop_hash_str][0]) // for [0, 0]
        ){
            // return form prop, "max_min_id_in_game_logs_by_end_at"
            $_return_list = $this->max_min_id_in_game_logs_by_end_at[$prop_hash_str];
        }else{
            $sql = <<<EOF
            SELECT max(`bet_logs`.id) as max_id
                , min(`bet_logs`.id) as min_id
                FROM `game_logs` AS `bet_logs`
                WHERE
             `bet_logs`.end_at BETWEEN "$begin_of_end_at" AND "$end_of_end_at"
            ;
EOF;
            $bind_values = [];
            $qry = $this->db->query($sql, $bind_values);
            $_last_query = $this->db->last_query();
            $this->utils->debug_log('OGP-27832.7837.get_max_min_id_in_game_logs_by_end_at._last_query:', $_last_query);
            $multipleRow = $this->getMultipleRowArray($qry);
            if( ! empty($multipleRow) ){
                $min_id = $multipleRow[0]['min_id'];
                $max_id = $multipleRow[0]['max_id'];

            }else{
                // not found, [0, 0]
                $min_id = 0;
                $max_id = 0;
            }
            $_return_list = [$min_id, $max_id];
            // assign in prop
            $this->max_min_id_in_game_logs_by_end_at[$prop_hash_str] = $_return_list;
        }

        return $_return_list; // [min_id, max_id]
    } // EOF get_max_min_id_in_game_logs_by_end_at


    public function getPlayerBetBySettledDate( $date // #1
												, $startHour // #2
												, $endHour // #3
												, $playerId = null // #4
												, $start_date = null // #5
												, $end_date = null // #6
												, $playerIds = null // #7
												, $include_game_platform_list = NULL // #8
												, $_group_by= 'tpgh.player_id, tpgh.game_description_id, tpgh.game_platform_id, tpgh.is_parlay' // #9
	) {
        $this->load->model(array('game_logs'));

        if (!empty($start_date) && !empty($end_date)) {
			$lastDate_date = $start_date;
			$date_date = $end_date;

            $lastDate = str_replace('-', '', $start_date);
			$date = str_replace('-', '', $end_date);
        } else {
            $lastDate = $this->utils->getLastDay($date);

            if (intval($endHour) == 23) {
                //all yesterday
                $date = $lastDate;
			}
			// get for date format, 'Y-m-d'
			$lastDate_date = $lastDate;
			$date_date = $date;

            $date = str_replace('-', '', $date);
			$lastDate = str_replace('-', '', $lastDate);
        }
        $this->utils->debug_log(__METHOD__ . '(): start', $lastDate . $startHour, 'end', $date . $endHour);

        $playerQry = '';

        if (!empty($playerIds)) {
            $ids = implode(',', $playerIds);
            $playerQry .= ' and player_id IN ('.$ids.')';
        } else {
            if ($playerId) {
                $playerId = intval($playerId);
                $playerQry .= ' and player_id=' . $playerId;
            }
        }

        $bind_query = '';
        $bind_values = [];

		if( empty($_group_by) ){
			$_group_by = 'tpgh.player_id, tpgh.game_description_id, tpgh.game_platform_id, tpgh.is_parlay';
		}

		$_suffix = '';
		$suffixInGetPlayerBetBySettledDate = $this->utils->getConfig('suffixInGetPlayerBetBySettledDate');
		if ( ! empty($suffixInGetPlayerBetBySettledDate) ) {
			$_suffix = $suffixInGetPlayerBetBySettledDate;
		}


        // cashback_start_hour to cashback_end_hour
        if(empty($include_game_platform_list)){
            return NULL;
        }



		$playerQryInJoin2CashbackReport = '';
		$playerQryInJoin2BetLogs = '';
		$playerQryInMapping = '';
        if (!empty($playerIds)) {
            $ids = implode(',', $playerIds);
            $playerQryInMapping .= ' and `cashback_report`.player_id IN ('.$ids.')';
			$playerQryInMapping .= ' and `bet_logs`.player_id IN ('.$ids.')';

			$playerQryInJoin2CashbackReport .= ' and `cashback_report`.player_id IN ('.$ids.')';
			$playerQryInJoin2BetLogs .= ' and `bet_logs`.player_id IN ('.$ids.')';
        } else {
            if ($playerId) {
                $playerId = intval($playerId);
                $playerQryInMapping .= ' and `cashback_report`.player_id='. $playerId;
				$playerQryInMapping .= ' and `bet_logs`.player_id='. $playerId;

				$playerQryInJoin2CashbackReport .= ' and `cashback_report`.player_id='. $playerId;
				$playerQryInJoin2BetLogs .= ' and `bet_logs`.player_id='. $playerId;
            }
        }

        $retroactiveTimeLimitInGetPlayerBetBySettledDate = $this->utils->getConfig('retroactiveTimeLimitInGetPlayerBetBySettledDate');
		$_begin_datetime = sprintf('%s %s:00:00', $lastDate_date , $startHour);
		// DATE_SUB(STR_TO_DATE('$_begin_datetime','%Y-%m-%d %H:%i:%s'),INTERVAL 2 MONTH) AND '$_begin_datetime'
		$_end_datetime = sprintf('%s %s:59:59', $date_date, $endHour);
		$_begin_datetimeDT = new DateTime($_begin_datetime);
		$_end_datetimeDT = new DateTime($_end_datetime);

		$_begin_totalDateDT = clone $_begin_datetimeDT;
		$_begin_totalDateDT->modify($retroactiveTimeLimitInGetPlayerBetBySettledDate); // RetroactiveTimeLimit
		$_begin_totalDateStr = $_begin_totalDateDT->format('Y-m-d');
		$_end_totalDateDT = clone $_end_datetimeDT;
		$_end_totalDateStr = $_end_totalDateDT->format('Y-m-d');
		//
		$_begin_endAtStr = $this->utils->formatDateTimeForMysql($_begin_totalDateDT);
		$_end_endAtStr = $this->utils->formatDateTimeForMysql($_end_totalDateDT);

        $do_use_max_min_id_in_game_logs = false;
        list($_begin_id_endAtStr, $_end_id_endAtStr) = $this->get_max_min_id_in_game_logs_by_end_at($_begin_endAtStr, $_end_endAtStr);
        if( ! empty($_begin_id_endAtStr) && ! empty($_end_id_endAtStr) ){
            $do_use_max_min_id_in_game_logs = true;
        }
		$totalDateQryInMapping = '';
		$totalDateQryInMapping .= " AND `cashback_report`.total_date BETWEEN '$_begin_totalDateStr' AND '$_end_totalDateStr' ";
        if($do_use_max_min_id_in_game_logs){
            $totalDateQryInMapping .= " AND `bet_logs`.id BETWEEN $_begin_id_endAtStr AND $_end_id_endAtStr ";
            $totalDateQryInMapping .= " /* aka. end_at BETWEEN '$_begin_endAtStr' AND '$_end_endAtStr' */ ";
        }else{
            $totalDateQryInMapping .= " AND `bet_logs`.end_at BETWEEN '$_begin_endAtStr' AND '$_end_endAtStr' ";
        }


		$totalDateQryInJoin2CashbackReport = "";
		$totalDateQryInJoin2CashbackReport .= " AND `cashback_report`.total_date BETWEEN '$_begin_totalDateStr' AND '$_end_totalDateStr' ";
		//
		$endAtQryInJoin2BetLogs = '';
        if($do_use_max_min_id_in_game_logs){
            $endAtQryInJoin2BetLogs .= " AND `bet_logs`.id BETWEEN $_begin_id_endAtStr AND $_end_id_endAtStr";
            $endAtQryInJoin2BetLogs .= " /* aka. end_at BETWEEN '$_begin_endAtStr' AND '$_end_endAtStr' */ ";
        }else{
            $endAtQryInJoin2BetLogs .= " AND `bet_logs`.end_at BETWEEN '$_begin_endAtStr' AND '$_end_endAtStr' ";
        }



		/// Add condition with `mapping`.player_id
		// $playerQryInMapping = ''; // disabled for append the condition
        if (!empty($playerIds)) {
            $ids = implode(',', $playerIds);
            $playerQryInMapping .= ' AND (`mapping`.`player_id` IN ('.$ids.') OR `mapping`.`player_id` = 0) ';
        } else {
            if ($playerId) {
                $playerId = intval($playerId);
                $playerQryInMapping .= ' AND (`mapping`.`player_id` = '. $playerId. ' OR `mapping`.`player_id` = 0 ) ';
            }
        }


		$_select_clause4paid_in_bet_logs = <<<EOF
`bet_logs`.id AS `bet_logs_id`
EOF;
		$paid_flag = self::DB_TRUE;
		/// 2 params, The slelect fields and paid_flag
		$_clause4paid_in_bet_logs = <<<EOF
		SELECT %s /* -- #1 */
		FROM `cashback_to_bet_list_mapping` AS `mapping`
		JOIN `total_cashback_player_game_daily` AS `cashback_report` ON `mapping`.`cashback_table` = 'total_cashback_player_game_daily'
		AND `mapping`.`cashback_id` = `cashback_report`.`id`
		%s /* -- #1.1a, totalDateQryInJoin2CashbackReport */
		%s /* -- #1.1b, playerQryInJoin2CashbackReport */
		JOIN `game_logs` AS `bet_logs` ON `mapping`.`bet_source_table` = 'game_logs'
		AND `mapping`.`bet_source_id` = `bet_logs`.`id`
		%s /* -- #1.2a, endAtQryInJoin2BetLogs */
		%s /* -- #1.2b, playerQryInJoin2BetLogs */
		WHERE `cashback_report`.paid_flag = %s  /* -- #2 */
		%s  /* -- #3, playerQryInMapping */
		%s  /* -- #4, totalDateQryInMapping */
EOF;
$this->utils->debug_log('OGP-27272.7857._clause4paid_in_bet_logs:', $_clause4paid_in_bet_logs);
		$clause4paid_in_bet_logs = sprintf($_clause4paid_in_bet_logs
											, $_select_clause4paid_in_bet_logs // #1
											, $totalDateQryInJoin2CashbackReport // #1.1a
											, $playerQryInJoin2CashbackReport // #1.1b
											, $endAtQryInJoin2BetLogs // #1.2a
											, $playerQryInJoin2BetLogs // #1.2b
											, $paid_flag // #2
											, $playerQryInMapping // #3
											, $totalDateQryInMapping );  // #4

		$where_clause4game_id_not_in = <<<EOF
AND tpgh.id NOT IN(
	$clause4paid_in_bet_logs
)
EOF;

        $bind_query .= " and (";
		$imploded_include_game_platform_list = implode(',', $include_game_platform_list);
		$sub_bind_query = <<<EOF
		tpgh.game_platform_id IN ( $imploded_include_game_platform_list )
		and ( ? <= tpgh.updated_at and tpgh.updated_at <= ? )
EOF;
		$bind_query .= $sub_bind_query;
		// the date format, 2020051212 convert to 2020-05-12 12:00:00.
		$bind_values[] = sprintf('%s %s:00:00', $lastDate_date , $startHour);
		$bind_values[] = sprintf('%s %s:59:59', $date_date, $endHour);

        $bind_query .= ")";

        $flag = Game_logs::FLAG_GAME;
        $sql = <<<EOD
SELECT tpgh.player_id, player.levelId, SUM(CASE WHEN tpgh.is_parlay = 1 THEN tpgh.bet_amount ELSE ABS(tpgh.result_amount) END) as betting_total, tpgh.game_description_id, gd.game_type_id, tpgh.game_platform_id, tpgh.is_parlay
, 'game_logs' as source_table, tpgh.id as source_id
, GROUP_CONCAT(tpgh.id) as source_id_list
  FROM game_logs as tpgh
  join game_description as gd on tpgh.game_description_id=gd.id and gd.no_cash_back!=1
  join player on player.playerId=tpgh.player_id
where flag=$flag
and player.disabled_cashback!=1
and tpgh.result_amount <> 0
{$playerQry}
{$bind_query}
{$where_clause4game_id_not_in}
group by {$_group_by}
{$_suffix}
EOD;
// $this->utils->debug_log('OGP-24813.7896.sql:', $sql, 'bind_values:', $bind_values);
		$qry = $this->db->query($sql, $bind_values);
		$_last_query = $this->db->last_query();
		$this->utils->debug_log('OGP-24813.7627._last_query:', $_last_query);
		$multipleRow = $this->getMultipleRow($qry);


		$do_debug4clarify = false;
		if($do_debug4clarify){
			$_debug_select_clause4paid_in_bet_logs = <<<EOF
`bet_logs`.id AS `bet_logs_id`
, (CASE WHEN bet_logs.is_parlay = 1 THEN bet_logs.bet_amount ELSE ABS(bet_logs.result_amount) END) AS bet_amount_for_cashback
EOF;
					$debug_clause4paid_in_bet_logs = sprintf($_clause4paid_in_bet_logs
															, $_debug_select_clause4paid_in_bet_logs // #1
															// TODO: #1.1a, #1.1b, #1.2a,  #1.2b
															, $paid_flag // #2
															, $playerQryInMapping // #3
															, $totalDateQryInMapping ); // #4
					$_qry = $this->db->query($debug_clause4paid_in_bet_logs);
					$not_in_rows = $this->getMultipleRow($_qry);
					$this->utils->debug_log('OGP-24813.7625.not_in_rows:', $not_in_rows);
		}

        return $multipleRow;
    }

	public function getPlayerBetBySettledDateLite( $date // #1
													, $startHour // #2
													, $endHour // #3
													, $playerId = null // #4
													, $start_date = null // #5
													, $end_date = null // #6
													, $playerIds = null // #7
													, $include_game_platform_list = NULL // #8
													, $_group_by= 'tpgh.player_id, tpgh.game_description_id, tpgh.game_platform_id, tpgh.is_parlay' // #9
	) {
        $this->load->model(array('game_logs'));

        if (!empty($start_date) && !empty($end_date)) {
			$lastDate_date = $start_date;
			$date_date = $end_date;

            $lastDate = str_replace('-', '', $start_date);
			$date = str_replace('-', '', $end_date);
        } else {
            $lastDate = $this->utils->getLastDay($date);

            if (intval($endHour) == 23) {
                //all yesterday
                $date = $lastDate;
			}
			// get for date format, 'Y-m-d'
			$lastDate_date = $lastDate;
			$date_date = $date;

            $date = str_replace('-', '', $date);
			$lastDate = str_replace('-', '', $lastDate);
        }
        $this->utils->debug_log(__METHOD__ . '(): start', $lastDate . $startHour, 'end', $date . $endHour);

        $playerQry = '';

        if (!empty($playerIds)) {
            $ids = implode(',', $playerIds);
            $playerQry .= ' and player_id IN ('.$ids.')';
        } else {
            if ($playerId) {
                $playerId = intval($playerId);
                $playerQry .= ' and player_id=' . $playerId;
            }
        }

        $bind_query = '';
        $bind_values = [];

		if( empty($_group_by) ){
			$_group_by = 'tpgh.player_id, tpgh.game_description_id, tpgh.game_platform_id, tpgh.is_parlay';
		}

		$_suffix = '';
		$suffixInGetPlayerBetBySettledDate = $this->utils->getConfig('suffixInGetPlayerBetBySettledDate');
		if ( ! empty($suffixInGetPlayerBetBySettledDate) ) {
			$_suffix = $suffixInGetPlayerBetBySettledDate;
		}

        // cashback_start_hour to cashback_end_hour
        if(empty($include_game_platform_list)){
            return NULL;
        }


		$_select_clause4paid_in_bet_logs = <<<EOF
`bet_logs`.id AS `bet_logs_id`
EOF;
		$paid_flag = self::DB_TRUE;
		/// 2 params, The slelect fields and paid_flag
		$_clause4paid_in_bet_logs = <<<EOF
		SELECT %s
		FROM `cashback_to_bet_list_mapping` AS `mapping`
		JOIN `total_cashback_player_game_daily` AS `cashback_report` ON `mapping`.`cashback_table` = 'total_cashback_player_game_daily'
		AND `mapping`.`cashback_id` = `cashback_report`.`id`
		JOIN `game_logs` AS `bet_logs` ON `mapping`.`bet_source_table` = 'game_logs'
		AND `mapping`.`bet_source_id` = `bet_logs`.`id`
		WHERE `cashback_report`.paid_flag = %s
EOF;

		$clause4paid_in_bet_logs = sprintf($_clause4paid_in_bet_logs, $_select_clause4paid_in_bet_logs, $paid_flag);

		$where_clause4game_id_not_in = <<<EOF
AND tpgh.id NOT IN(
	$clause4paid_in_bet_logs
)
EOF;

        $bind_query .= " and (";
		$imploded_include_game_platform_list = implode(',', $include_game_platform_list);
		$sub_bind_query = <<<EOF
		tpgh.game_platform_id IN ( $imploded_include_game_platform_list )
		and ( ? <= tpgh.updated_at and tpgh.updated_at <= ? )
EOF;
		$bind_query .= $sub_bind_query;
		// the date format, 2020051212 convert to 2020-05-12 12:00:00.
		$bind_values[] = sprintf('%s %s:00:00', $lastDate_date , $startHour);
		$bind_values[] = sprintf('%s %s:59:59', $date_date, $endHour);

        $bind_query .= ")";

        $flag = Game_logs::FLAG_GAME;
        $sql = <<<EOD
SELECT tpgh.player_id, player.levelId, SUM(CASE WHEN tpgh.is_parlay = 1 THEN tpgh.bet_amount ELSE ABS(tpgh.result_amount) END) as betting_total, tpgh.game_description_id, gd.game_type_id, tpgh.game_platform_id, tpgh.is_parlay
, 'game_logs' as source_table, tpgh.id as source_id
, GROUP_CONCAT(tpgh.id) as source_id_list
  FROM game_logs as tpgh
  join game_description as gd on tpgh.game_description_id=gd.id and gd.no_cash_back!=1
  join player on player.playerId=tpgh.player_id
where flag=$flag
and player.disabled_cashback!=1
and tpgh.result_amount <> 0
{$playerQry}
{$bind_query}
{$where_clause4game_id_not_in}
group by {$_group_by}
{$_suffix}
EOD;

		$qry = $this->db->query($sql, $bind_values);
		$_last_query = $this->db->last_query();
		$this->utils->debug_log('OGP-24813.7627.lite._last_query:', $_last_query);
		$multipleRow = $this->getMultipleRow($qry);


		$do_debug4clarify = false;
		if($do_debug4clarify){
			$_debug_select_clause4paid_in_bet_logs = <<<EOF
			`bet_logs`.id AS `bet_logs_id`
			, (CASE WHEN bet_logs.is_parlay = 1 THEN bet_logs.bet_amount ELSE ABS(bet_logs.result_amount) END) AS bet_amount_for_cashback
EOF;
			$debug_clause4paid_in_bet_logs = sprintf($_clause4paid_in_bet_logs, $_debug_select_clause4paid_in_bet_logs, $paid_flag);
			$_qry = $this->db->query($debug_clause4paid_in_bet_logs);
			$not_in_rows = $this->getMultipleRow($_qry);
			$this->utils->debug_log('OGP-24813.7625.not_in_rows:', $not_in_rows);
		}
        return $multipleRow;
    }

	/**
	 * detail: get bet for a certain player base on the date range
	 *
	 * @param string $date
	 * @param string $startHour
	 * @param string $endHour
	 * @param int $playerId
	 *
	 * @return array
	 */
	public function sumPlayerBetByDate($date, $startHour, $endHour, $playerId = null, $start_date = null, $end_date = null, $playerIds = null) {
        $this->load->model(array('game_logs'));

        if (!empty($start_date) && !empty($end_date)) {
            $lastDate = str_replace('-', '', $start_date);
            $date = str_replace('-', '', $end_date);
        } else {
            $lastDate = $this->utils->getLastDay($date);

            if (intval($endHour) == 23) {
                //all yesterday
                $date = $lastDate;
            }
            $date = str_replace('-', '', $date);
            $lastDate = str_replace('-', '', $lastDate);
        }
        $this->utils->debug_log(__METHOD__ . '(): start', $lastDate . $startHour, 'end', $date . $endHour);

		$playerQry = '';
		if (!empty($playerIds)) {
			$ids = implode(',', $playerIds);
			$playerQry .= ' and player_id IN ('.$ids.')';
		} else {
			if ($playerId) {
				$playerId = intval($playerId);
				$playerQry .= ' and player_id=' . $playerId;
			}
		}

        $bind_query = '';
        $bind_values = [];

		// cashback_start_hour to cashback_end_hour
		if (!$this->utils->getConfig('use_total_hour')) {
            $bind_query .= " and (date_format(tpgh.end_at, '%Y%m%d%H')>=? and date_format(tpgh.end_at, '%Y%m%d%H')<=?)";
            $bind_values[] = $lastDate . $startHour;
            $bind_values[] = $date . $endHour;

			$flag = Game_logs::FLAG_GAME;
			$sql = <<<EOD
SELECT tpgh.player_id, sum(tpgh.bet_amount) as betting_total
  FROM game_logs as tpgh
  join game_description as gd on tpgh.game_description_id=gd.id and gd.no_cash_back!=1
  join player on player.playerId=tpgh.player_id
where flag=$flag
and player.disabled_cashback!=1
{$playerQry}
{$bind_query}
group by tpgh.player_id
EOD;
		} else {
            $bind_query .= " and (date_hour>=? and date_hour<=?)";
            $bind_values[] = $lastDate . $startHour;
            $bind_values[] = $date . $endHour;

            //use total_player_game_hour
			$sql = <<<EOD
SELECT tpgh.player_id, sum(tpgh.betting_amount) as betting_total
  FROM total_player_game_hour as tpgh
  join game_description as gd on tpgh.game_description_id=gd.id and gd.no_cash_back!=1
  join player on player.playerId=tpgh.player_id
where player.disabled_cashback!=1
{$playerQry}
{$bind_query}
group by tpgh.player_id
EOD;
		}

		$qry = $this->db->query($sql, $bind_values);


		$map = [];
		$rows = $this->getMultipleRow($qry);
		if (!empty($rows)) {
			foreach ($rows as $row) {
				$player_id = $row->player_id;
				$betting_total = $row->betting_total;
				$map[$player_id] = $betting_total;
			}
		}

		return $map;
	}

    public function getCancelledWithdrawConditionsDateRangeAndGame($unfinished_wc_player = null){
        $this->load->model(['withdraw_condition']);
        $result = $this->withdraw_condition->getPlayerCancelledWithdrawalConditionWithUnFinishedFlag($unfinished_wc_player);
        return $result;
    }

	public function calculatePlayerBetTotal(&$player_bet_by_date, &$player_bet_by_settled_date, $playerBetByCancelledWithdrawConditions = null){
	    $map = [];

        if(!empty($player_bet_by_date)){
            $this->excludePlayerBetByCancelledWithdrawCondition($player_bet_by_date, $map, $playerBetByCancelledWithdrawConditions);
        }

        if(!empty($player_bet_by_settled_date)){
            $this->excludePlayerBetByCancelledWithdrawCondition($player_bet_by_settled_date, $map, $playerBetByCancelledWithdrawConditions);
        }

	    return $map;
    }

    public function excludePlayerBetByCancelledWithdrawCondition(&$playerBets, &$map, $playerBetByCancelledWithdrawConditions = null){
	    foreach($playerBets as $key => &$pbbd){
            $player_id = $pbbd->player_id;
            $betting_total = $pbbd->betting_total;
            $game_description_id = $pbbd->game_description_id;
            $this->utils->debug_log(__METHOD__ . '(): ', 'player_id', $player_id, 'game_description_id', $game_description_id, 'betting_total', $betting_total);

            if(!empty($playerBetByCancelledWithdrawConditions[$player_id][$game_description_id])){
                $this->utils->debug_log('exclude bet amount', $playerBetByCancelledWithdrawConditions[$player_id][$game_description_id], 'betting_total', $betting_total);

                if($playerBetByCancelledWithdrawConditions[$player_id][$game_description_id] >= $betting_total){
                    // bet amount in wc period is greater than last day total bet
                    $remain_amount = $playerBetByCancelledWithdrawConditions[$player_id][$game_description_id] - $betting_total;
                    $this->utils->debug_log('after [bet amount in wc period - last day total bet] ======> remain ', $remain_amount);
                    unset($playerBets[$key]);
                    continue;
                }

                $map[$player_id] = (isset($map[$player_id])) ? $map[$player_id] - $playerBetByCancelledWithdrawConditions[$player_id][$game_description_id] : $betting_total - $playerBetByCancelledWithdrawConditions[$player_id][$game_description_id];
                $pbbd->betting_total = $pbbd->betting_total -  $playerBetByCancelledWithdrawConditions[$player_id][$game_description_id];
            }else{
                $map[$player_id] = (isset($map[$player_id])) ? $map[$player_id] + $betting_total : $betting_total;
            }
        }
    }
	/**
	 * detail: get bet for a certain player base on the date range
	 *
	 * @param string $timeStart
	 * @param string $timeEnd
	 * @param int $playerId
	 *
	 * @return array
	 */
	public function sumPlayerBetByTime($timeStart, $timeEnd, $playerId = null) {
		$playerQry = '';
		if ($playerId) {
			$playerId = intval($playerId);
			$playerQry .= ' and player_id=' . $playerId;
		}

		$this->load->model(array('game_logs'));

		//disabled_cashback will disable player cashback
		// cashback_start_hour to cashback_end_hour
		$flag = Game_logs::FLAG_GAME;
		$sql = <<<EOD
			SELECT tpgh.player_id, sum(tpgh.bet_amount) AS betting_total
			FROM game_logs AS tpgh
				JOIN game_description AS gd
					ON tpgh.game_description_id=gd.id AND gd.no_cash_back!=1
				JOIN player
					ON player.playerId=tpgh.player_id
			WHERE tpgh.end_at>=?
				AND tpgh.end_at<=?
				AND flag={$flag}
				AND player.disabled_cashback!=1
				{$playerQry}
			GROUP BY tpgh.player_id
EOD;

		$qry = $this->db->query($sql, array($timeStart, $timeEnd));

		// $this->utils->printLastSQL();

		$map = [];
		$rows = $this->getMultipleRow($qry);
		if (!empty($rows)) {
			foreach ($rows as $row) {
				$player_id = $row->player_id;
				$betting_total = $row->betting_total;
				$map[$player_id] = $betting_total;
			}
		}

		return $map;

	}

	/**
	 * search percentage from commonrules
	 *
	 * order:
	 * 1. search game rules
	 * 2. if empty, get common rule
	 *
	 * @param  int $player_id
	 * @param  int $game_description_id
	 * @param  double $playerSumBetAmount
	 * @param  array [rule id => [min_bet_amount, max_bet_amount,default_percentage, game_rules=>[game_description_id=>game_rule]]]
	 * @return ['cashback_percentage'=>0, 'cashback_maxbonus'=>null, 'id'=>0, 'level_id'=>0]
	 */
	public function getPercentageByCommonRules($player_id, $game_description_id, $playerSumBetAmount, $commonRules) {
		$rateRow = (object) ['cashback_percentage' => 0, 'cashback_maxbonus' => null, 'id' => 0, 'level_id' => 0];

		$settings = $commonRules['settings'];
		$rules = $commonRules['rules'];

		$rateRow->cashback_maxbonus = @$settings['max_cashback_amount'];

		if (!empty($rules) && !empty($playerSumBetAmount)) {
			foreach ($rules as $ruleId => $ruleContent) {
				$min_bet_amount = $ruleContent['min_bet_amount'];
				$max_bet_amount = $ruleContent['max_bet_amount'];
				if ($playerSumBetAmount >= $min_bet_amount && $playerSumBetAmount < $max_bet_amount) {
					//use this
					$this->utils->debug_log('try search cashback rule', $ruleId, $playerSumBetAmount, $min_bet_amount, $max_bet_amount);
					//order is game_desc_percentage > game_type_percentage > game_platform_percentage > default_percentage
					$cashback_percentage = $ruleContent['default_percentage'];
					$found = false;
					if (!empty($ruleContent['game_rules'])) {
						if (isset($ruleContent['game_rules'][$game_description_id])) {
							$gameRuleRow = $ruleContent['game_rules'][$game_description_id];
							if (!empty($gameRuleRow)) {
								//order by
								if (!empty($gameRuleRow['game_desc_percentage'])) {
									$cashback_percentage = $gameRuleRow['game_desc_percentage'];
								} elseif (!empty($gameRuleRow['game_type_percentage'])) {
									$cashback_percentage = $gameRuleRow['game_type_percentage'];
								} elseif (!empty($gameRuleRow['game_platform_percentage'])) {
									$cashback_percentage = $gameRuleRow['game_platform_percentage'];
								}
								$found = true;
								$this->utils->debug_log('found cashback rule', $gameRuleRow);
							}
						}
					}
					if ($found) {
						$rateRow->cashback_percentage = $cashback_percentage;
						$rateRow->cashback_maxbonus = @$settings['max_cashback_amount'];
						break;
					}
				}
			}
		}
		return $rateRow;
	}

	/**
	 *
	 * @return array rule id => game rules
	 */
	public function getCommonCashbackRules() {
		$commonRules = ['settings' => (array) $this->getCashbackSettings()];
		$map = [];
		//load from cashback common rules
		$this->db->from('common_cashback_rules')->where('status', self::STATUS_NORMAL);
		$rows = $this->runMultipleRowArray();
		if (!empty($rows)) {
			foreach ($rows as $row) {
				$rule_id = $row['id'];
				$this->db->from('common_cashback_game_rules')->where('rule_id', $row['id']);
				$gameRules = $this->runMultipleRowArray();

				$gameRulesMap = [];
				if (!empty($gameRules)) {
					foreach ($gameRules as $gameRuleRow) {
						$gameRulesMap[$gameRuleRow['game_description_id']] = $gameRuleRow;
					}
				}
				$row['game_rules'] = $gameRulesMap;

				$map[$rule_id] = $row;

			}
		}
		$commonRules['rules'] = $map;

		return $commonRules;
	}

	public function isNoCashbackBonusForNonDepositPlayer() {
		$this->load->model(array('operatorglobalsettings', 'cashback_settings'));

		$cashbackSettings = $this->operatorglobalsettings->getSettingJson(self::CASHBACK_SETTINGS_NAME);

		$no_cashback_bonus_for_non_deposit_player = @$cashbackSettings['no_cashback_bonus_for_non_deposit_player'];
		//default is false
		return !!$no_cashback_bonus_for_non_deposit_player;
	}

	/**
	 * detail: get total cashback
	 *
	 * @param string $date
	 * @param string $startHour
	 * @param string $endHour
	 * @param int $playerId
	 * @param int $withdraw_condition_bet_times
	 *
	 * @return int
	 */
	public function totalCashback( $date // #1
                                    , $startHour // #2
                                    , $endHour // #3
                                    , $playerId = null // #4
                                    , $withdraw_condition_bet_times = 0 // #5
                                    , &$result = false // #6
                                    , $start_date = null // #7
                                    , $end_date = null // #8
                                    , $forceToPay = false // #9
                                    , $recalculate_cashback = false // #10
                                    , $uniqueId = null // #11
                                    , $doExceptionPropagationInChoppedLock = false // #12
    ) {
        $this->load->model(array('player_model', 'transactions', 'users', 'game_description_model', 'withdraw_condition', 'cashback_to_bet_list_mapping'));

        $use_settled_time_apis = $this->CI->utils->getConfig('api_array_when_calc_cashback_by_settled_time');

        $unfinished_wc_player = [];
        $playerBetByCancelledWithdrawConditions = null;
        $enabled_exclude_wc_available_bet_after_cancelled_wc = $this->utils->getConfig('exclude_wc_available_bet_after_cancelled_wc');
		$playerBetByCancelledWithdrawConditions = []; // default
        if($enabled_exclude_wc_available_bet_after_cancelled_wc){
            $unfinished_wc_player = $this->withdraw_condition->getPlayerByUnfinishedAllWithdrawCondition();
            list($playerBetByCancelledWithdrawConditions,$exclude_wc_id) = $this->getPlayerBetByCancelledWithdrawCondition($unfinished_wc_player);
        }

        $isPayTime = $this->isPayTime($date, $forceToPay);

		$playerBetByDate = $this->getPlayerBetByDate($date, $startHour, $endHour, $playerId, $start_date, $end_date, NULL, $use_settled_time_apis);

        $playerBetBySettledDate = $this->getPlayerBetBySettledDate($date, $startHour, $endHour, $playerId, $start_date, $end_date, NULL, $use_settled_time_apis);

		$this->utils->debug_log(__METHOD__ . '(): ', $date, $startHour, $endHour, $playerId,
            'getPlayerBetByDate count', empty($playerBetByDate)?0:count($playerBetByDate), 'playerBetByDate:', $playerBetByDate,
            'playerBetBySettledDate count', empty($playerBetBySettledDate)?0:count($playerBetBySettledDate), 'playerBetBySettledDate:', $playerBetBySettledDate);

        $cnt = 0;

        if (empty($playerBetByDate) && empty($playerBetBySettledDate)) {
            return $cnt;
        }

        $mapPlayerBet = $this->calculatePlayerBetTotal($playerBetByDate, $playerBetBySettledDate, $playerBetByCancelledWithdrawConditions);

		$this->utils->debug_log('sumPlayerBetByDate', $date, $startHour, $endHour, $playerId, 'count', count($mapPlayerBet));

		$unknownGames = $this->game_description_model->getUnknownGameList();

		$commonRules = $this->getCommonCashbackRules();

		$extra_info = [
			'mapPlayerBet' => $mapPlayerBet,
			'commonRules' => $commonRules,
		];

		$isNoCashbackBonusForNonDepositPlayer = $this->isNoCashbackBonusForNonDepositPlayer();

        $always_enable_unknown_games_on_callback = $this->getConfig('always_enable_unknown_games_on_callback');

        $extra_info['levelCashbackMap'] = $this->getFullCashbackPercentageMap();

        if(!empty($exclude_wc_id)){
            $this->withdraw_condition->updateDeductFromCalcCashbackFlag($exclude_wc_id, Withdraw_condition::ONLY_DEDUCT_BET_BEFORE_CANCELLED_WC_FROM_CALC_CASHBACK);
            $this->utils->debug_log('update excluded wc ids to deductFromCalcCashbackFlag ONLY_DEDUCT_BET_BEFORE_CANCELLED_WC_FROM_CALC_CASHBACK', $exclude_wc_id);
        }

        $recalculate_cashback_table = $recalculate_deducted_process_table = null;
        if($recalculate_cashback && !empty($uniqueId)){
            list($recalculate_cashback_table, $recalculate_deducted_process_table) = $this->checkRecalculateCashbackInfo($uniqueId);
        }
        $wc_amount_map = $this->withdraw_condition->getAllPlayersAvailableAmountOnWithdrawConditionByCashbackSettings($date, $startHour, $endHour, $start_date, $end_date, $playerId, $recalculate_cashback, $recalculate_deducted_process_table);

        $all_player_bet = [
            $playerBetByDate,
            $playerBetBySettledDate
        ];


        try{ /// for $doExceptionPropagationInChoppedLock
            $_e = NULL;
            $do_next = true;

            // @TODO OGP-278332 Solution2. - BEGIN
            $enabled_dryrun_in_calculatecashback = $this->utils->getConfig('enabled_dryrun_in_calculatecashback');
            $enabled_chopped_lock_in_calculatecashback = $this->utils->getConfig('enabled_chopped_lock_in_calculatecashback');
            if ($enabled_chopped_lock_in_calculatecashback) {
                $startTime=microtime(true);
                $this->startTrans();
            }

            /// Script Begin - OGP-278332 Solution2.
            foreach($all_player_bet as $player_bet_amount_list){
                if(empty($player_bet_amount_list)) continue;

                foreach ($player_bet_amount_list as $pbbd) {
                    $player_id = $pbbd->player_id;
                    $game_platform_id = $pbbd->game_platform_id;
                    $game_description_id = $pbbd->game_description_id;
                    $game_type_id = $pbbd->game_type_id;
                    $this->utils->debug_log('OGP-24813.7973.pbbd', $pbbd
                    , 'recalculate_cashback_table:', $recalculate_cashback_table
                    , 'recalculate_deducted_process_table:', $recalculate_deducted_process_table );
                    if($enabled_exclude_wc_available_bet_after_cancelled_wc && in_array($player_id, $unfinished_wc_player)){
                        $this->utils->debug_log('disable calculate cashback due to unfinished all wc', $player_id);
                        // disable calculate cashback due to unfinished all wc
                        continue;
                    }

                    if ($pbbd->betting_total <= 0) {
                        continue;
                    }

                    $rate = 0; // $playerFirstRate->cashback_percentage;
                    $max_bonus = 0; //$playerFirstRate->cashback_maxbonus;
                    $history_id = null; // $playerFirstRate->id;
                    $levelId = $pbbd->levelId;

                    if ($isNoCashbackBonusForNonDepositPlayer) {
                        //should check deposit
                        $playerObj = $this->player_model->getPlayerArrayById($player_id);
                        if ($playerObj['totalDepositAmount'] <= 0) {
                            $this->utils->debug_log('ignore player ' . $player_id . ' for none deposit');
                            continue;
                        }
                    }

                    // get daily rate
                    $playerDailyRate = $this->getPlayerRateFromLevel($player_id, $levelId,
                        $game_platform_id, $game_type_id, $game_description_id, $extra_info);

                    if ($playerDailyRate) {
                        $rate = $playerDailyRate->cashback_percentage;
                        $max_bonus = $playerDailyRate->cashback_maxbonus;
                        $history_id = $playerDailyRate->id;
                        $level_id = $playerDailyRate->level_id;
                    }
                    $this->utils->debug_log('process player:'.$player_id.', rate:'.$rate);

                    //only for exist cashback rate
                    if ($rate <= 0) {
                        continue;
                    }

                    $this->utils->debug_log('final rate', $rate, 'player_id', $player_id, 'game_description_id', $game_description_id,
                        'history_id', $history_id, 'level_id', $level_id, 'betting', $pbbd->betting_total, 'betting*rate', $pbbd->betting_total * ($rate / 100));

                    $game_platform_id = $pbbd->game_platform_id;
                    $game_description_id = $pbbd->game_description_id;
                    $game_type_id = $pbbd->game_type_id;
                    $total_date = $date;

                    $original_bet_amount = $this->utils->roundCurrencyForShow($pbbd->betting_total);


                    $this->auto_deduct_withdraw_condition_from_bet($player_id, $wc_amount_map, $pbbd, $isPayTime, $total_date, $recalculate_deducted_process_table);

                    $cashback_amount = $this->utils->roundCurrencyForShow($pbbd->betting_total * ($rate / 100));
                    $withdraw_condition_amount = $this->utils->roundCurrencyForShow($cashback_amount * $withdraw_condition_bet_times);

                    $cnt++;
                    $affected_id = $this->syncCashbackDaily($player_id, $game_platform_id, $game_description_id, $total_date,
                        $cashback_amount, $history_id, $game_type_id, $level_id, $rate,
                        $this->utils->roundCurrencyForShow($pbbd->betting_total),
                        $withdraw_condition_amount, $max_bonus, $original_bet_amount,
                        1, null, '', null, 0, $recalculate_cashback_table);

                    if( ! empty($affected_id)){
                        $_cashback_table = 'total_cashback_player_game_daily';
                        if( ! empty($recalculate_cashback_table) ){
                            $_cashback_table = $recalculate_cashback_table;
                        }
                        $_params = [];
                        $_params['cashback_table'] = $_cashback_table;
                        $_params['player_id'] = $player_id;
                        $_params['cashback_id'] = $affected_id;
                        $_params['bet_source_table'] = $pbbd->source_table;
                        $_params['bet_source_id_list'] = $pbbd->source_id_list;
                        $_params['is_pay'] = '0';
                        $_rlt = $this->cashback_to_bet_list_mapping->syncToDataWithBetSourceIdListAfterSyncCashbackDaily($_params);
                        $this->utils->debug_log('OGP-24813.8052._rlt', $_rlt);
                    } // EOF if( ! empty($affected_id_syncCashbackDaily)){...

                    if ($enabled_chopped_lock_in_calculatecashback) {
                        if( !empty($enabled_dryrun_in_calculatecashback) ){
                            if( ! empty( $this->detectFileInSecret_keys(Group_level::FILENAME_SUSPEND_IN_DRYRUN_FOR_CALCULATECASHBACK)) ){
                                $this->CI->utils->debug_log("OGP-27832 cancel continue of dryrun in Phase", 'player_id:', $player_id);
                                throw new Exception('Exception for SUSPEND in dryrun of calculatecashback in Phase. player_id:'. $player_id, Group_level::EXCEPTION_CODE_IN_CANCEL_CONTINUE);
                                // break 2;  // this will break both foreach loops
                            }
                        }
                    }
                } // EOF foreach ($player_bet_amount_list as $pbbd) {...
            } // EOF foreach($all_player_bet as $player_bet_amount_list){...
            /// Script End - OGP-278332 Solution2.

            // @TODO OGP-278332 Solution2. - END
            if ($enabled_chopped_lock_in_calculatecashback) {
                if( !empty($enabled_dryrun_in_calculatecashback) ){
                    // dryrun
                    $this->CI->player_model->rollbackTrans();
                    $this->CI->utils->debug_log('DRYRUN In totalCashback().');
                }else{
                    $isEndTransWithSucc_chopped_lock = $this->endTransWithSucc();
                }
                $this->utils->debug_log("OGP-27272 cost of Script Begin To End in totalCashback()", microtime(true)-$startTime);
            }
            $do_next = true;
            //// TODO 回傳 Trans 結果方式。(這邊是任務整批)


        // aka. EOF try{ /// for $doExceptionPropagationInChoppedLock
        } catch(Exception $_e) { /// for $doExceptionPropagationInChoppedLock

            if($doExceptionPropagationInChoppedLock){
                throw $_e;// $this->utils->debug_log('it is exception with code', $e->getCode());
            }

            if($_e->getCode() == Group_level::EXCEPTION_CODE_IN_CANCEL_CONTINUE){
                // for dryrun
                $this->CI->player_model->rollbackTrans();
                $this->CI->utils->debug_log('DRYRUN In totalCashback(), do_continue_in_dryrun_of_calculatecashback=0.');
            }
        } finally { /// for $doExceptionPropagationInChoppedLock

            if( ! is_null($_e) ){
                $this->rollbackTrans();
                $isEndTransWithSucc_chopped_lock = false;
                $do_next = false;
                $cnt = 0; // re-assign by rollbackTrans()
            }
        // aka. EOF catch(Exception $_e) { /// for $doExceptionPropagationInChoppedLock
        } // EOF try{ /// for $doExceptionPropagationInChoppedLock

        if($this->utils->getConfig('use_accumulate_deduction_when_calculate_cashback') && $do_next){
			$this->syncReCalculateCashbackDaily($date, $uniqueId);
        }

		return $cnt;
	}

	public function totalCashbackFriendReferral($date, $startHour, $endHour, $playerId = null, $withdraw_condition_bet_times = 0, &$result = false,
												$start_date = null, $end_date = null, $cashback_type = 1, $useWhiteListReferral = false) {

		$this->load->model(array('player_model', 'transactions', 'users', 'game_description_model',
				'withdraw_condition','friend_referral_settings', 'player_friend_referral'));

		$playerIds = array();

        $use_settled_time_apis = $this->CI->utils->getConfig('api_array_when_calc_cashback_by_settled_time');

        $playerBetByDate = $this->getPlayerBetByDate($date, $startHour, $endHour, $playerId, $start_date, $end_date, $playerIds, $use_settled_time_apis);

        $playerBetBySettledDate = $this->getPlayerBetBySettledDate($date, $startHour, $endHour, $playerId, $start_date, $end_date, NULL, $use_settled_time_apis);

		$this->utils->debug_log('getPlayerBetByDate', $date, $startHour, $endHour, $playerId, 'getPlayerBetByDate count', count($playerBetByDate), 'playerBetBySettledDate count', count($playerBetBySettledDate));

        $cnt = 0;

        if (empty($playerBetByDate) && empty($playerBetBySettledDate)) {
            return $cnt;
        }

        $mapPlayerBet = $this->calculatePlayerBetTotal($playerBetByDate, $playerBetBySettledDate);

		$this->utils->debug_log('sumPlayerBetByDate', $date, $startHour, $endHour, $playerId, 'count', count($mapPlayerBet));

		$commonRules = $this->getCommonCashbackRules();

		$extra_info = [
				'mapPlayerBet' => $mapPlayerBet,
				'commonRules' => $commonRules,
		];
		$isNoCashbackBonusForNonDepositPlayer = $this->isNoCashbackBonusForNonDepositPlayer();

        $extra_info['levelCashbackMap'] = $this->getFullCashbackPercentageMap(); # get game percentage

        $wc_amount_map=[];
        $wc_amount_map = $this->withdraw_condition->getAllPlayersAvailableAmountOnWithdrawConditionByCashbackSettings(
            $date, $startHour, $endHour, $start_date, $end_date, $playerId);

        $all_player_bet = [
            $playerBetByDate,
            $playerBetBySettledDate
        ];

        foreach($all_player_bet as $player_bet_amount_list){
            if(empty($player_bet_amount_list)) continue;

            foreach ($player_bet_amount_list as $pbbd) {
                $game_platform_id = $pbbd->game_platform_id;
                $game_description_id = $pbbd->game_description_id;
                $game_type_id = $pbbd->game_type_id;

                $this->utils->debug_log('total bet', $pbbd->betting_total, "player id : $pbbd->player_id");

                if ($pbbd->betting_total <= 0) {
                    continue;
                }

                $rate = 0;
                $max_bonus = 0;
                $history_id = null;
                $player_id = $pbbd->player_id;
                $level_id = $pbbd->levelId;

                // make sure player was referred to recieve referral cashback
                // get referrer by invited player id
                $referrer_id = $this->player_friend_referral->getReferrerByInvitedPlayerId($player_id);
                if(!$referrer_id) {
                    $this->utils->debug_log('player was not invited or refer by other', "player id : $player_id");
                    continue;
                }

                if ($isNoCashbackBonusForNonDepositPlayer) {
                    $playerObj = $this->player_model->getPlayerArrayById($referrer_id);
                    if ($playerObj['totalDepositAmount'] <= 0) {
                        $this->utils->debug_log('no cashback bonus for non deposit player', "player id : $referrer_id");
                        continue;
                    }
                }

                $playerWhiteIds = array();
                $allowed_player_for_referral_cashback = $this->utils->getConfig('allowed_player_for_referral_cashback');
                if (!empty($allowed_player_for_referral_cashback)) {
                    $this->utils->debug_log("======WHITELIST REFERRAL ENABLED============");

                    $player = $this->player_model->getPlayerIdsByUsernames($allowed_player_for_referral_cashback);
                    if (!empty($player)) {
                        foreach ($player as $key => $value) {
                            array_push($playerWhiteIds, $value['playerId']);
                        }
                    }

                    // only whitelist player can recieve cashback
                    if (!in_array($referrer_id, $playerWhiteIds)) {
                        $this->utils->debug_log('only whitelist can recieve cashback', "player id : $referrer_id");
                        continue;
                    }
                }

                $referralSetting = $this->friend_referral_settings->getFriendReferralSettings();
                if (isset($referralSetting['cashback_rate']) && $referralSetting['cashback_rate'] > 0) {
                    $rate = $referralSetting['cashback_rate'];
                }

                if ($rate <= 0) {
                    continue;
                }

                $this->utils->debug_log('final rate', $rate, 'player_id', $player_id, 'game_description_id', $game_description_id,
                    'history_id', $history_id, 'level_id', $level_id, 'betting', $pbbd->betting_total, 'betting*rate', $pbbd->betting_total * ($rate / 100));

                $game_platform_id = $pbbd->game_platform_id;
                $game_description_id = $pbbd->game_description_id;
                $game_type_id = $pbbd->game_type_id;
                $total_date = $date;

                $original_bet_amount = $this->utils->roundCurrencyForShow($pbbd->betting_total);

                $cashback_amount = $this->utils->roundCurrencyForShow($pbbd->betting_total * ($rate / 100));
                $withdraw_condition_amount = $this->utils->roundCurrencyForShow($cashback_amount * $withdraw_condition_bet_times);

                $cnt++;
                $this->syncCashbackDaily($referrer_id, $game_platform_id, $game_description_id, $total_date,
                    $cashback_amount, $history_id, $game_type_id, $level_id, $rate,
                    $this->utils->roundCurrencyForShow($pbbd->betting_total),
                    $withdraw_condition_amount, $max_bonus, $original_bet_amount, $cashback_type, $player_id);
            }
        }

		return $cnt;
	}

	public function auto_deduct_withdraw_condition_from_bet($player_id, &$wc_amount_map, &$pbbd, $isPayTime = false, $total_date = null, $recalculate_deducted_process_table = null) {
		if (array_key_exists($player_id, $wc_amount_map) &&
				$this->utils->compareResultCurrency($wc_amount_map[$player_id], '>', 0) ){

			$original_bet_amount = $this->utils->roundCurrencyForShow($pbbd->betting_total);

			//minus wc amount from original
			$current_wc_total = $wc_amount_map[$player_id];
            $use_accumulate_deduction = $this->utils->getConfig('use_accumulate_deduction_when_calculate_cashback');
            if($this->utils->isEnabledFeature('enabled_use_decuct_flag_to_filter_withdraw_condition_when_calc_cackback') && !$use_accumulate_deduction) {
                //$current_wc_total will be an array like [ 'player_id' => ['wc_id' => (int), 'amount' => (float)],['wc_id' => (int), 'amount' => (float)] ]
                $current_player_wc = $current_wc_total;
                $used_wc_id = [];
                $deducted_wc_id = [];

                $total_wc_amount = 0;
                foreach($current_player_wc as $wc_row){
                    $total_wc_amount += $wc_row['amount'];
                }
                $origin_total_wc_amount = $total_wc_amount;

                foreach ($current_player_wc as &$wc){
                    if($wc['is_deducted']){
                        continue;
                    }

                    $total_wc_amount = $total_wc_amount - $wc['amount'];

                    if($pbbd->betting_total >= $wc['amount']){
                        $pbbd->betting_total = $pbbd->betting_total - $wc['amount'];
                        $wc['is_deducted'] = true;
                        $used_wc_id[] = $wc['wc_id'];
                    }else{
                        $pbbd->betting_total = 0;
                        $wc['is_deducted'] = true;
                        $used_wc_id[] = $wc['wc_id'];
                        break;
                    }
                }

                if(!empty($used_wc_id)){
                    if($this->utils->isEnabledFeature('always_calc_before_pay_cashback')){
                        $deducted_wc_id['temp_deducted_id'] = $used_wc_id;
                        $this->withdraw_condition->updateDeductFromCalcCashbackFlag($used_wc_id, Withdraw_condition::TEMP_DEDUCT_FROM_CALC_CASHBACK);
                        if($isPayTime){
                            $deducted_wc_id['is_deducted_id'] = $used_wc_id;
                            $this->withdraw_condition->updateDeductFromCalcCashbackFlag($used_wc_id, Withdraw_condition::IS_DEDUCTED_FROM_CALC_CASHBACK);
                        }
                    }else{
                        $deducted_wc_id['is_deducted_id'] = $used_wc_id;
                        $this->withdraw_condition->updateDeductFromCalcCashbackFlag($used_wc_id, Withdraw_condition::IS_DEDUCTED_FROM_CALC_CASHBACK);
                    }
                }

                $wc_amount_map[$player_id] = array_replace_recursive($wc_amount_map[$player_id], $current_player_wc);

				$this->utils->debug_log('OGP-27272.8493.auto_deduct_withdraw_condition_from_bet_' . $player_id,
                    'game_description_id', $pbbd->game_description_id,
                    'total_wc_amount', $origin_total_wc_amount, 'remain_wc_amount', $total_wc_amount, 'used_wc_id', $used_wc_id,
                    'original_bet_amount', $original_bet_amount, 'betTotal - used_wc_total', $pbbd->betting_total,
                    'wc_amount_map[$player_id]', $wc_amount_map[$player_id], 'deducted_wc_id', $deducted_wc_id);


            }else if($this->utils->isEnabledFeature('enabled_use_decuct_flag_to_filter_withdraw_condition_when_calc_cackback') && $use_accumulate_deduction){
                $current_player_wc = $current_wc_total;
                $total_wc_amount = 0;
                $total_used_wc_id_of_deducted_process = [];

                $wc_id_of_finish_deducted_process = [];
                $wc_id_of_not_finish_deducted_process = [];

                $game_platform_id = $pbbd->game_platform_id;
                $game_type_id = $pbbd->game_type_id;
                $game_description_id = $pbbd->game_description_id;

                foreach($current_player_wc as $player_wc){
                    $total_wc_amount += $player_wc['amount'];
                }
                $origin_total_wc_amount = $total_wc_amount;

                foreach ($current_player_wc as &$wc){
                    if($wc['is_deducted']){
                        continue;
                    }

                    if(empty($wc['amount'])){
                        continue;
                    }

                    // flow of origin wc
                    $condition_amount = $wc['amount'];
                    $total_used_wc_id_of_deducted_process[] = $wc['wc_id'];

                    $wc_before_amount = $condition_amount;

                    if($pbbd->betting_total >= $condition_amount){
                        $wc['is_deducted'] = true;

                        $wc_after_amount = 0; // condition amount has completely deduct

                        $pbbd->betting_total -= $condition_amount; // betting amount has not completely deduct by condition amount

                        $wc_id = $wc['wc_id'];
                        $wc['amount'] = $wc_after_amount;  // clear one condition amount of withdraw condition

                        $wc_id_of_finish_deducted_process[] = $wc_id;

                        if(!empty($recalculate_deducted_process_table)){
                            $insert_id = $this->withdraw_condition_deducted_process->insertDeductedProcessToRecalculateTable($player_id, $total_date, $wc_id, $wc_before_amount,
                                $wc_after_amount, $game_platform_id, $game_type_id, $game_description_id, $recalculate_deducted_process_table);
                        }else{
                            $insert_id = $this->withdraw_condition_deducted_process->insertDeductedProcess($player_id, $total_date, $wc_id, $wc_before_amount,
                                $wc_after_amount, $game_platform_id, $game_type_id, $game_description_id);
                        }

                        $this->utils->debug_log('Deduct process successfully', $insert_id);
                    }else{
                        $wc_after_amount = $condition_amount - $pbbd->betting_total; // $pbbd->betting_total < $condition_amount

                        $pbbd->betting_total = 0;   // betting amount has completely deduct by condition amount

                        $wc_id = $wc['wc_id'];
                        $wc['amount'] = $wc_after_amount;  // remain amount of withdraw condition

                        $wc_id_of_not_finish_deducted_process[] = $wc_id;

                        if(!empty($recalculate_deducted_process_table)){
                            $insert_id = $this->withdraw_condition_deducted_process->insertDeductedProcessToRecalculateTable($player_id, $total_date, $wc_id, $wc_before_amount,
                                $wc_after_amount, $game_platform_id, $game_type_id, $game_description_id, $recalculate_deducted_process_table);
                        }else{
                            $insert_id = $this->withdraw_condition_deducted_process->insertDeductedProcess($player_id, $total_date, $wc_id, $wc_before_amount,
                                $wc_after_amount, $game_platform_id, $game_type_id, $game_description_id);
                        }

                        $this->utils->debug_log('Deduct process successfully', $insert_id);
                        break;
                    }
                }

                if(!empty($wc_id_of_not_finish_deducted_process)){
                    $this->withdraw_condition->updateDeductFromCalcCashbackFlag($wc_id_of_not_finish_deducted_process, Withdraw_condition::IS_ACCUMULATING_DEDUCTION_OF_WC_FROM_CALCULATE_CASHBACK);
                }
                if(!empty($wc_id_of_finish_deducted_process)){
                    $this->withdraw_condition->updateDeductFromCalcCashbackFlag($wc_id_of_finish_deducted_process, Withdraw_condition::IS_DEDUCTED_FROM_CALC_CASHBACK);
                }

                $wc_amount_map[$player_id] = array_replace_recursive($wc_amount_map[$player_id], $current_player_wc);

                $this->utils->debug_log('auto_deduct_withdraw_condition_from_bet_' . $player_id,
                    'recalculate_deducted_process_table', $recalculate_deducted_process_table,
                    'game_description_id', $pbbd->game_description_id, 'total_wc_amount', $origin_total_wc_amount,
                    'original_bet_amount', $original_bet_amount,'remaining bet amount', $pbbd->betting_total,
                    'finish_deducted_process', $wc_id_of_finish_deducted_process,
                    'not_finish_deducted_process', $wc_id_of_not_finish_deducted_process,
                    'used_wc_id_of_deducted_process', $total_used_wc_id_of_deducted_process,
                    'wc_amount_map[$player_id]', $wc_amount_map[$player_id]);

            }else{
                //$current_wc_total will be a number as total_wc_amount
                if($current_wc_total < $pbbd->betting_total){
                    $pbbd->betting_total = $pbbd->betting_total - $current_wc_total;
                    //clear wc amount
                    $wc_amount_map[$player_id] = 0;
                }else{
                    //minus wc amount, prevent double
                    $wc_amount_map[$player_id] = $wc_amount_map[$player_id] - $pbbd->betting_total;
                    $pbbd->betting_total = 0;
                }

                $this->utils->debug_log('auto_deduct_withdraw_condition_from_bet_' . $player_id,
                    'game_description_id', $pbbd->game_description_id,
                    'original_bet_amount', $original_bet_amount, 'betTotal - current_wc_total', $pbbd->betting_total,
                    'current_wc_total', $current_wc_total, 'wc_amount_map[$player_id]', $wc_amount_map[$player_id]);
            }

		}
	}

	public function getAllCanJoinIn() {
		$this->db->select('vipsetting.vipSettingId');
		$this->db->select('vipsettingcashbackrule.vipsettingcashbackruleId');
		$this->db->select('vipsetting.groupName');
		$this->db->select('vipsettingcashbackrule.vipLevelName');
		$this->db->select('vipsetting.groupDescription');
		$this->db->from('vipsetting');
		$this->db->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipSettingId = vipsetting.vipSettingId', 'left');
		$this->db->where('vipsetting.can_be_self_join_in', self::DB_TRUE);
		$this->db->order_by('vipsetting.groupName', 'asc');
		$this->db->order_by('vipsettingcashbackrule.vipLevelName', 'asc');
		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * search unpaid cashback by report condition
	 *
	 * @param  array $input condition from report
	 * @return array
	 */
	public function searchUnpaidCashbackByCondition($input) {

		$this->db->select('total_cashback_player_game_daily.id')->from('total_cashback_player_game_daily')
			->join('player', 'total_cashback_player_game_daily.player_id = player.playerId');

		if (isset($input['by_amount_less_than']) && $input['by_amount_less_than'] !== null && $input['by_amount_less_than'] !== '') {
			$this->db->where("total_cashback_player_game_daily.amount <=", $input['by_amount_less_than']);
		}

		if (isset($input['by_amount_greater_than']) && $input['by_amount_greater_than'] !== null && $input['by_amount_greater_than'] !== '') {
			$this->db->where("total_cashback_player_game_daily.amount <=", $input['by_amount_greater_than']);
		}

		if (isset($input['by_player_level']) && !empty($input['by_player_level'])) {
			$this->db->where("player.levelId", $input['by_player_level']);
		}

		if (isset($input['by_paid_flag']) && !empty($input['by_paid_flag'])) {
			$this->db->where("total_cashback_player_game_daily.paid_flag", $input['by_paid_flag']);
		}

		if (isset($input['enable_date']) && $input['enable_date'] == 'true') {

			if (isset($input['by_date_from'])) {
				$this->db->where("total_cashback_player_game_daily.total_date >=", $input['by_date_from']);
			}

			if (isset($input['by_date_to'])) {
				$this->db->where("total_cashback_player_game_daily.total_date <=", $input['by_date_to']);
			}
		}

		//convert memberUsername to player id
		if (isset($input['by_username']) && !empty($input['by_username'])) {
			$this->db->like("player.username", $input['by_username']);
		}

		return $this->convertArrayRowsToArray($this->runMultipleRowArray(), 'id');
	}

	/**
	 * Get player maximum withdrawal per transaction
	 *
	 * @param 	int
	 * @return	array
	 */
	public function getPlayerMaxWithdrawalPerTransaction($playerId) {
		$this->db->select('vipsettingcashbackrule.max_withdraw_per_transaction, vipsettingcashbackrule.max_withdrawal_non_deposit_player')
			->from('playerlevel')
			->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = playerlevel.playerGroupId', 'left');
		$this->db->where('playerlevel.playerId', $playerId);
		$query = $this->db->get();
		return $query->result_array();
	}

	public function convertToNewCashbackPercentage() {
		$this->db->select('vipsettingcashbackruleId')->from($this->levelTable);
		$vipLevelList = $this->runMultipleRowArray();

		foreach ($vipLevelList as $level) {

			$levelId = $level['vipsettingcashbackruleId'];

			$self = $this;
			//write tree to new table
			$this->dbtransOnly(function () use ($self, $levelId) {

				list($gamePlatformList, $gameTypeList, $gameDescList) =
				$self->getVipSettingsGamesTypeAndGameDesc($levelId);

				$self->deleteCashbackPercentage($levelId);

				if (!empty($gamePlatformList)) {

					$gamePlatformData = [];
					foreach ($gamePlatformList as $gamePlatformId => $percentage) {
						$gamePlatformData[] = [
							'vipsetting_cashbackrule_id' => $levelId,
							'game_platform_id' => $gamePlatformId,
							'percentage' => $percentage,
							'status' => self::DB_TRUE,
							'updated_at' => $self->utils->getNowForMysql(),
						];
					}
					$this->db->insert_batch('group_level_cashback_game_platform', $gamePlatformData);

				}

				if (!empty($gameTypeList)) {

					$gameTypeData = [];
					foreach ($gameTypeList as $gameTypeId => $percentage) {
						$gameTypeData[] = [
							'vipsetting_cashbackrule_id' => $levelId,
							'game_type_id' => $gameTypeId,
							'percentage' => $percentage,
							'status' => self::DB_TRUE,
							'updated_at' => $self->utils->getNowForMysql(),
						];
					}
					$this->db->insert_batch('group_level_cashback_game_type', $gameTypeData);

				}

				if (!empty($gameDescList)) {

					$gameDescData = [];
					foreach ($gameDescList as $gameDescId => $percentage) {
						$gameDescData[] = [
							'vipsetting_cashbackrule_id' => $levelId,
							'game_description_id' => $gameDescId,
							'percentage' => $percentage,
							'status' => self::DB_TRUE,
							'updated_at' => $self->utils->getNowForMysql(),
						];
					}
					$this->db->insert_batch('group_level_cashback_game_description', $gameDescData);

				}

				return true;
			});
		}
	}

	public function deleteCashbackPercentage($levelId) {

		$success = true;

		$this->db->delete('group_level_cashback_game_platform',
			['vipsetting_cashbackrule_id' => $levelId]);

		$this->db->delete('group_level_cashback_game_type',
			['vipsetting_cashbackrule_id' => $levelId]);

		$this->db->delete('group_level_cashback_game_description',
			['vipsetting_cashbackrule_id' => $levelId]);

		return $success;
	}

	public function getFullCashbackPercentageMap() {
		$levelCashbackMap = [];

		//ignore unchecked cashback level
		$this->db->from($this->levelTable)->where('bonus_mode_cashback', self::DB_TRUE);
        $this->utils->debug_log('bonus_mode_cashback query ----------> ', $this->db->_compile_select());
		$rows = $this->runMultipleRowArray();
		$levelIdArr=[];
		if(!empty($rows)) {
			foreach ($rows as $row) {
				$levelCashbackMap[$row[$this->levelId]] = [
					'cashback_maxbonus' => floatval($row['cashback_maxbonus']),
					'cashback_percentage' => floatval($row['cashback_percentage']),
				];

				$levelIdArr[]=$row[$this->levelId];
			}

            $this->db->from('group_level_cashback_game_platform')
                ->where_in('group_level_cashback_game_platform.vipsetting_cashbackrule_id', $levelIdArr);
            $rows = $this->runMultipleRowArray();

            if(!empty($rows)) {
                foreach ($rows as $row) {

                    if (!isset($levelCashbackMap[$row['vipsetting_cashbackrule_id']])) {
                        $levelCashbackMap[$row['vipsetting_cashbackrule_id']] = [];
                    }
                    if (!isset($levelCashbackMap[$row['vipsetting_cashbackrule_id']]['game_platform'])) {
                        $levelCashbackMap[$row['vipsetting_cashbackrule_id']]['game_platform'] = [];
                    }

                    $levelCashbackMap[$row['vipsetting_cashbackrule_id']]['game_platform'][$row['game_platform_id']] = floatval($row['percentage']);
                }
            }
            unset($rows);

            $this->db->from('group_level_cashback_game_type')
                ->where_in('group_level_cashback_game_type.vipsetting_cashbackrule_id', $levelIdArr);
            $rows = $this->runMultipleRowArray();

            if(!empty($rows)) {
                foreach ($rows as $row) {
                    if (!isset($levelCashbackMap[$row['vipsetting_cashbackrule_id']])) {
                        $levelCashbackMap[$row['vipsetting_cashbackrule_id']] = [];
                    }
                    if (!isset($levelCashbackMap[$row['vipsetting_cashbackrule_id']]['game_type'])) {
                        $levelCashbackMap[$row['vipsetting_cashbackrule_id']]['game_type'] = [];
                    }

                    $levelCashbackMap[$row['vipsetting_cashbackrule_id']]['game_type'][$row['game_type_id']] = floatval($row['percentage']);
                }
            }
            unset($rows);

			$this->getLevelGameDescription($levelIdArr, $levelCashbackMap);
		}
		return $levelCashbackMap;
	}

	public function getLevelGameDescription($levelIdArr, &$levelCashbackMap){
		$this->db->select('vipsetting_cashbackrule_id,game_description_id,percentage')->from('group_level_cashback_game_description')
		    ->where_in('group_level_cashback_game_description.vipsetting_cashbackrule_id', $levelIdArr);
		$rows = $this->runMultipleRowArrayUnbuffered();

		$this->utils->debug_log('process getLevelGameDescription run sql', count($rows));

		if(!empty($rows)) {
			foreach ($rows as $row) {
				if (!isset($levelCashbackMap[$row['vipsetting_cashbackrule_id']])) {
					$levelCashbackMap[$row['vipsetting_cashbackrule_id']] = [];
				}
				if (!isset($levelCashbackMap[$row['vipsetting_cashbackrule_id']]['game_description'])) {
					$levelCashbackMap[$row['vipsetting_cashbackrule_id']]['game_description'] = [];
				}

				$levelCashbackMap[$row['vipsetting_cashbackrule_id']]['game_description'][$row['game_description_id']] = floatval($row['percentage']);
			}
			unset($rows);
		}
	}

	/**
	 *
	 * batch add cashback
	 *
	 * @param  int $levelId
	 * @param  array $gamePlatformList
	 * @param  array $gameTypeList
	 * @param  array $gameDescList
	 * @return boolean
	 */
	public function batchAddCashbackPercentage($levelId, $gamePlatformList, $gameTypeList, $gameDescList, $adminUserId=null, $postTree=null, &$diffList=[]) {

		$success = true;

		$this->utils->debug_log('gamePlatformList count', count($gamePlatformList), 'gameTypeList count', count($gameTypeList), 'gameDescList count', count($gameDescList));

		$this->utils->debug_log($gamePlatformList, $gameTypeList, $gameDescList);

		if (!$this->backupCashbackPercentage($levelId, $adminUserId, $postTree)) {
			$this->utils->error_log('backupCashbackPercentage failed', $levelId);
		}

		$this->generateCashbackDiffList($levelId, $gamePlatformList, $gameTypeList, $diffList);

		$this->deleteCashbackPercentage($levelId);

		$data = [];
		foreach ($gamePlatformList as $gamePlatformId => $percentage) {

			$data[] = [
				'vipsetting_cashbackrule_id' => $levelId,
				'game_platform_id' => $gamePlatformId,
				'percentage' => $percentage,
				'status' => self::DB_TRUE,
				'updated_at' => $this->utils->getNowForMysql(),
			];

		}

		$this->batchInsertWithLimit('group_level_cashback_game_platform', $data);

		$data = [];
		foreach ($gameTypeList as $gameTypeId => $percentage) {

			$data[] = [
				'vipsetting_cashbackrule_id' => $levelId,
				'game_type_id' => $gameTypeId,
				'percentage' => $percentage,
				'status' => self::DB_TRUE,
				'updated_at' => $this->utils->getNowForMysql(),
			];

		}

		$this->batchInsertWithLimit('group_level_cashback_game_type', $data);

		$data = [];
		foreach ($gameDescList as $gameDescId => $percentage) {

			$data[] = [
				'vipsetting_cashbackrule_id' => $levelId,
				'game_description_id' => $gameDescId,
				'percentage' => $percentage,
				'status' => self::DB_TRUE,
				'updated_at' => $this->utils->getNowForMysql(),
			];

		}

		$this->batchInsertWithLimit('group_level_cashback_game_description', $data);

		return $success;
	}

	public function getVipGroupList() {
		$this->db->select('vipsettingId,groupName')
			->from('vipsetting')->where('vipsetting.status', 'active')
			->where('vipsetting.deleted !=', 1)
			->order_by('groupName', 'ASC');

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

	public function getCashbackPayoutSetting() {
		$this->db->select('*')->from('vipgrouppayoutsetting');

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$row['setTime'] = mdate('%h:%i:%s %A', strtotime($row['setTime']));
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

	public function getPlayerGroup() {
		$this->db->select('vipSettingId,groupName')->from('vipsetting')->where('vipsetting.status', 'active');
		$this->db->order_by('vipsetting.groupName', 'ASC');
		$query = $this->db->get();
		return $query->result_array();
	}

	public function getAllCanJoinInGroup() {
		// this reuturn only 1st level
		$this->db->select('vipsetting.vipSettingId');
		$this->db->select('vipsettingcashbackrule.vipsettingcashbackruleId');
		$this->db->select('vipsetting.groupName');
		$this->db->select('vipsettingcashbackrule.vipLevelName');
		$this->db->select('vipsetting.groupDescription');
		$this->db->from('vipsetting');
		$this->db->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipSettingId = vipsetting.vipSettingId', 'left');
		$this->db->where('vipsetting.can_be_self_join_in', self::DB_TRUE);
		$this->db->where('vipsettingcashbackrule.vipLevel', '1');
		$this->db->where('vipsetting.deleted', '0');
		$this->db->order_by('vipsetting.groupName', 'asc');
		$this->db->order_by('vipsettingcashbackrule.vipLevelName', 'asc');
		$query = $this->db->get();
		return $query->result_array();
	}

	public function getPlayerGroupLevelInfo($playerId) {
		$this->db->select('player.levelId, vipsettingcashbackrule.vipLevelName, vipsettingcashbackrule.vipLevel, vipsetting.groupName, vipsetting.vipSettingId')
			->from('player')
			->join('vipsettingcashbackrule', 'player.levelId=vipsettingcashbackrule.vipsettingcashbackruleId')
			->join('vipsetting', 'vipsetting.vipSettingId=vipsettingcashbackrule.vipSettingId');
		$this->db->where('playerId', $playerId);

		return $this->runOneRowArray();
	}

	public function getPlayerGroupLevelLimitInfo($playerId) {
		$this->db->select('player.levelId vip_level_id, vipsettingcashbackrule.points_limit as points_limit,
		vipsettingcashbackrule.points_limit_type as points_limit_type, vipsettingcashbackrule.vipLevelName vip_level_name, vipsetting.groupName vip_group_name')
			->from('player')
			->join('vipsettingcashbackrule', 'player.levelId=vipsettingcashbackrule.vipsettingcashbackruleId')
			->join('vipsetting', 'vipsetting.vipSettingId=vipsettingcashbackrule.vipSettingId');
		$this->db->where('playerId', $playerId);

		return $this->runOneRowArray();
	}

	public function addUpgradeLevelSetting($data) {
		if (isset($data['upgrade_id'])) {
			$this->db->where('upgrade_id', $data['upgrade_id']);
			$result = $this->db->update('vip_upgrade_setting', $data);
		} else {
			$result = $this->db->insert('vip_upgrade_setting', $data);
		}
		return $result;
	}

	public function upgradeLevelSetting($upgrade_id = '') {
		$this->db->select('*');
		$this->db->from('vip_upgrade_setting');

		if (!empty($upgrade_id)) {
			$this->db->where('upgrade_id', $upgrade_id);
		}

		$data = $this->db->get()->result();
		return $data;
	}

	public function deleteUpgradeLevelSetting($upgradeSettingId) {
		if ($upgradeSettingId) {
			$this->db->where('upgrade_id', $upgradeSettingId);
			$this->db->delete('vip_upgrade_setting');
		}
	}

	public function enableDisableSetting($upgradeSettingId, $status = '') {
		if ($status == self::UPGRADE_SETTING_ENABLE) {
			$this->db->set('status', self::UPGRADE_SETTING_DISABLE);
		} else {
			$this->db->set('status', self::UPGRADE_SETTING_ENABLE);
		}
		$this->db->where('upgrade_id', $upgradeSettingId);
		$this->db->update('vip_upgrade_setting');
	}

	public function upDownTemplateList() {
		$this->db->from('vip_upgrade_setting');
		$this->db->where('status', self::UPGRADE_SETTING_ENABLE);
		$data = $this->db->get()->result();
		return $data;
	}

	public function getUpgradeSettingById($upgradeSettingId) {
		$this->db->from('vip_upgrade_setting');
		$this->db->where('upgrade_id', $upgradeSettingId);
		$data = $this->db->get()->row();

		return $data ? $data->level_upgrade : '';
	}

	public function getSettingData($upgradeSettingId, $db = null) {
        if(empty($db)){
            $db=$this->db;
        }
		$db->select();
		$db->from('vip_upgrade_setting');
		$db->where('upgrade_id', $upgradeSettingId);
		$setting = $db->get()->row_array();
		return $setting;
	}

	/**
	 * Convert Points
	 */
	public function convertToPoints($fromDatetime, $toDatetime) {
		$this->load->library(array('authentication'));
		$this->load->model(array('total_player_game_minute', 'player_model', 'transactions', 'point_transactions'));

		$result = array();

		$winPoints = $lossPoints = 0;

		$adminUserId = $this->authentication->getUserId();

		$gameLogData = $this->total_player_game_minute->getTotalBetsWinsLossByAllPlayers($fromDatetime, $toDatetime);

		$allPlayerLevels = $this->getAllPlayerLevels(); // gets all player levels
		$levelMap = array();

		foreach ($allPlayerLevels as $lvl) {
			$levelMap[$lvl['vipsettingcashbackruleId']] = $lvl;
		}

		if (!empty($gameLogData)) {
			foreach ($gameLogData as $row) {
				$player = $this->player_model->getPlayerById($row['player_id']);

				if (!isset($levelMap[$player->levelId])) {
					$this->utils->error_log('ignore player', $player->playerId, $player->username);
					continue;
				}

				$playerTotalPoints = $this->player_model->getPlayerPoints($player->playerId);

				$playerLevel = $levelMap[$player->levelId];

				$betConvertRate = $playerLevel['bet_convert_rate'];
				$winConvertRate = $playerLevel['winning_convert_rate'];
				$lossConvertRate = $playerLevel['losing_convert_rate'];

				// if bet rate is not empty then convert
				if (!empty($betConvertRate)) {
					$betPoints = $row['total_bet'] * $betConvertRate / 100; // convert bet points

					$this->point_transactions->createPointTransaction(
						$adminUserId,
						$player->playerId,
						$betPoints, // points
						$playerTotalPoints, // before point balance
						$betPoints + $playerTotalPoints, // new points balance ( before points + points )
						'', // sales order id
						'', // sales order promo id
						Point_transactions::BET_POINT);

					if (!empty($winConvertRate)) {
						$winPoints = $row['total_win'] * $winConvertRate / 100; // convert win points

						$this->point_transactions->createPointTransaction(
							$adminUserId,
							$player->playerId,
							$winPoints, // points
							$playerTotalPoints + $betPoints, // before point balance
							$winPoints + $betPoints + $playerTotalPoints, // new points balance ( before points + points )
							'', // sales order id
							'', // sales order promo id
							Point_transactions::WIN_POINT);
					}

					if (!empty($lossConvertRate)) {
						$lossPoints = $row['total_loss'] * $lossConvertRate / 100; // convert loss points

						$this->point_transactions->createPointTransaction(
							$adminUserId,
							$player->playerId,
							$lossPoints, // points
							$playerTotalPoints + $winPoints + $betPoints, // before point balance
							$winPoints + $betPoints + $lossPoints + $playerTotalPoints, // new points balance ( before points + points )
							'', // sales order id
							'', // sales order promo id
							Point_transactions::LOSS_POINT);
					}

					$overAllPoints = $playerTotalPoints + $betPoints + $winPoints + $lossPoints;

					// update player point balance
					$this->player_model->updatePlayerPointBalance($player->playerId, $overAllPoints);

				} else {
					$this->utils->debug_log('no points for ' . $player->username);
				}
			}
		}

		return $result;
	}

	public function getNextLevel($vipgrouplevelId) {

		$this->db->select('vipsettingcashbackrule.*,vipsetting.groupName')->from('vipsettingcashbackrule');
		$this->db->join('vipsetting', 'vipsettingcashbackrule.vipSettingId = vipsetting.vipSettingId');
		$this->db->where('vipsettingcashbackrule.vipsettingcashbackruleId >', $vipgrouplevelId);
		$this->db->limit(1);

		$query = $this->db->get();
		return $query->row_array();

	}


	/**
	 * Get a level by VIP group (vipSettingId) and level number (vipLevel)
	 * @param	int		$vipSettingId
	 * @param	int		$vipLevel
	 * @return	array
	 */
	public function getVIPTopLevel($vipSettingId, $vipLevel) {

		$this->db->select('*');
		$this->db->from('vipsettingcashbackrule');
		$this->db->where('vipSettingId', $vipSettingId);
		$this->db->where('vipLevel', $vipLevel);

		return $this->db->get()->row();
	}

	/**
	 * Get a UNDELETED level by VIP group (vipSettingId) and level_number
	 * @param	int		$vipSettingId
	 * @param	int		$level_number
	 * @return	array
	 */
	public function getLevelByGroupAndLevelNum($vipSettingId, $level_number) {
		$this->db->from('vipsettingcashbackrule')
			->where('vipSettingId', $vipSettingId)
			->where('vipLevel', $level_number)
			->where('deleted', 0)
		;

		$res = $this->runOneRowArray();
		return $res;
	}

    /**
     * Get the level id of lowest Levels of each Groups
     * The level id also been the field, `vipsettingcashbackrule``.`vipsettingcashbackruleId`.
     *
     * @return array The rows, that there is only one column, vipsettingcashbackruleId.
     */
    public function getLowestLevelOfLevels($getMode = 'lowest'){
        $res = $this->getHighestOfLowestLevelOfLevels($getMode);
        return $res;
    } // EOF getLowestLevelOfLevels
    //
    public function getHighestOfLowestLevelOfLevels($getHighestOrLowest = 'highest'){

        $joinQuery = <<<EOD
( SELECT vipSettingId as vipSettingId
    , MAX(vipLevel) as max_vipLevel
    , MIN(vipLevel) as min_vipLevel
    , COUNT(vipsettingcashbackruleId) as vipLevel_count
FROM `vipsettingcashbackrule`
WHERE deleted = 0
GROUP BY vipSettingId ) AS %s
EOD;
// alias tablename
        $this->db->select('vipsettingcashbackruleId');
        $this->db->from('vipsettingcashbackrule');
        if( strtolower($getHighestOrLowest) == 'highest'){
            $aliasTablename = 'highest_level';
            $joinOn= "max_vipLevel=vipLevel AND {$aliasTablename}.vipSettingId=vipsettingcashbackrule.vipSettingId";
        }else{
            $aliasTablename = 'lowest_level';
            $joinOn= "min_vipLevel=vipLevel AND {$aliasTablename}.vipSettingId=vipsettingcashbackrule.vipSettingId";
        }
        $_joinQuery = sprintf($joinQuery, $aliasTablename);
        $this->db->join($_joinQuery, $joinOn);

        $res = $this->runMultipleRowArray();
        /// The src. SQL,
        // SELECT vipsettingcashbackruleId
        // FROM vipsettingcashbackrule
        // JOIN ( SELECT vipSettingId as vipSettingId
        //             , MIN(vipLevel) as min_vipLevel
        //             , COUNT(vipsettingcashbackruleId) as vipLevel_count
        //         FROM `vipsettingcashbackrule`
        //         WHERE deleted = 0
        //         GROUP BY vipSettingId
        // ) AS lowest_level ON min_vipLevel=vipLevel AND lowest_level.vipSettingId=vipsettingcashbackrule.vipSettingId
        // ;
        // SELECT vipsettingcashbackruleId
        // FROM vipsettingcashbackrule
        // JOIN ( SELECT vipSettingId as vipSettingId
        //             , MAX(vipLevel) as max_vipLevel
        //             , COUNT(vipsettingcashbackruleId) as vipLevel_count
        //         FROM `vipsettingcashbackrule`
        //         WHERE deleted = 0
        //         GROUP BY vipSettingId
        // ) AS highest_level ON max_vipLevel=vipLevel AND highest_level.vipSettingId=vipsettingcashbackrule.vipSettingId
        // ;
        return $res;
    } // EOF getHighestOfLowestLevelOfLevels

	/**
	 * List formula
	 *
	 * return boolean
	 */
	public function displayPlayerFormulaForUpgrade($formula) {
		$result = '';
		$option = 0;

		foreach ($formula as $key => $val) {
			if (is_array($val)) {
				if ($key == 'bet_amount') {
					$option = lang('Bet');
				} elseif ($key == 'deposit_amount') {
					$option = lang('Deposit');
				} elseif ($key == 'loss_amount') {
					$option = lang('Loss');
				} elseif ($key == 'win_amount') {
					$option = lang('Win');
				}
				$conjunction = $val[0];
				$amount = $val[1];
				$result .= $option .$conjunction . $amount;
			} else {
				if (strpos($key, 'operator') !== false) {
					$result .= ' ' . lang($val) . ' ';
				}
			}
		}
		return $result;
	}


	public function formatPlayerUpgradePercentageInfo($formula // #1
												, $betTotal=null // #2
												, $betReq=null // #3
												, $depositTotal=null // #4
												, $depositReq=null // #5
												, $lossTotal=null // #6
												, $lossReq=null // #7
												, $winTotal=null // #8
												, $winReq=null // #9
	) {
		$playerData = array();

		foreach ($formula as $key => $val) {
			if (is_array($val)) {
				if (	$key == 'bet_amount'
					&& ! is_null($betReq)
				) {
					array_push($playerData, array(
						'name' => lang('Bet'),
						'total' => $betTotal,
						'condition' => $betReq,
						'percentage_rate' => $this->playerRate($betTotal, $betReq),
					));
				} elseif ($key == 'deposit_amount'
					&& ! is_null($depositReq)
				) {
					array_push($playerData, array(
						'name' => lang('Deposit'),
						'total' => $depositTotal,
						'condition' => $depositReq,
						'percentage_rate' => $this->playerRate($depositTotal, $depositReq),
					));
				} elseif ($key == 'loss_amount'
					&& ! is_null($lossReq)
				) {
					array_push($playerData, array(
						'name' => lang('Loss'),
						'total' => $lossTotal,
						'condition' => $lossReq,
						'percentage_rate' => $this->playerRate($lossTotal, $lossReq)
					));
				} elseif ($key == 'win_amount'
					&& ! is_null($winReq)
				) {
					array_push($playerData, array(
						'name' => lang('Win'),
						'total' => $winTotal,
						'condition' => $winReq,
						'percentage_rate' => $this->playerRate($winTotal, $winReq)
					));
				}
			}
		}
		return $playerData;
	}



	public function getPlayerUpgradePercentage($playerId, $formula, $period) {
        $this->load->library(['group_level_lib']);
		$schedule = json_decode($period, true);

		$total_deposit = 0;
		$total_bet = 0;
		$total_loss = 0;
		$total_win = 0;

		$upgradeSched = $this->getUpgradeSchedule($schedule, $ignoreDate = true);

		// for accumulation
		$vipSettingId = $this->getPlayerLevelId($playerId);
		$getPlayerCurrentLevelDetails = $this->getVipGroupLevelDetails($vipSettingId);
		$vipUpgradeDetails = $this->getVIPGroupUpgradeDetails($getPlayerCurrentLevelDetails['vip_upgrade_id']);

		if (isset($vipUpgradeDetails['accumulation'])) {
			$this->load->model(['player_model']);
			$playerDetail = $this->player_model->getPlayerInfoDetailById($playerId);
			$now = new DateTime();
			if ((int)$vipUpgradeDetails['accumulation'] == Group_level::ACCUMULATION_MODE_FROM_REGISTRATION) {
				$upgradeSched['dateFrom'] = $playerDetail['playerCreatedOn'];
				$upgradeSched['dateTo'] = $now->format('Y-m-d H:i:s');
			}else if ((int)$vipUpgradeDetails['accumulation'] == Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE) {
				$upgradeSched['dateTo'] = $now->format('Y-m-d H:i:s');
				$theLastGradeRecordRow = $this->queryLastGradeRecordRowBy($playerId, $playerDetail['playerCreatedOn'], $this->utils->formatDateTimeForMysql($now), 'upgrade_or_downgrade');
				if( empty($theLastGradeRecordRow) ){
					$upgradeSched['dateFrom'] = $playerDetail['playerCreatedOn'];
				}else{
					$upgradeSched['dateFrom'] = $theLastGradeRecordRow['pgrm_end_time'];
				}
			}
		}
		if (!empty($upgradeSched['dateFrom']) && !empty($upgradeSched['dateTo'])) {
			$fromDatetime = $upgradeSched['dateFrom'];
			$toDatetime = $upgradeSched['dateTo'];

			$total_player_game_table = 'total_player_game_minute';
			$where_date_field = 'date_minute';
			$fromDatetime4minute = $this->utils->formatDateMinuteForMysql(new DateTime($fromDatetime));
			$toDatetime4minute = $this->utils->formatDateMinuteForMysql(new DateTime($toDatetime));
            if( $this->enable_multi_currencies_totals ){
                $gameLogData = $this->group_level_lib->getPlayerTotalBetWinLossWithForeachMultipleDBWithoutSuper($playerId, $fromDatetime4minute, $toDatetime4minute, $total_player_game_table, $where_date_field);
            }else{
                $gameLogData = $this->total_player_game_day->getPlayerTotalBetWinLoss($playerId, $fromDatetime4minute, $toDatetime4minute, $total_player_game_table, $where_date_field);
            }

			$total_deposit = $this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers($playerId, $fromDatetime, $toDatetime)[0];
			$total_bet = $gameLogData['total_bet'];
			$total_win = $gameLogData['total_win'];
			$total_loss = $gameLogData['total_loss'];
		}

		$playerData = array();

		foreach ($formula as $key => $val) {
			if (is_array($val)) {
				if ($key == 'bet_amount') {
					array_push($playerData, array(
						'name' => lang('Bet'),
						'total' => $total_bet,
						'condition' => $val[1],
						'percentage_rate' => $this->group_level->playerRate($total_bet, $val[1]),
					));
				} elseif ($key == 'deposit_amount') {
					array_push($playerData, array(
						'name' => lang('Deposit'),
						'total' => $total_deposit,
						'condition' => $val[1],
						'percentage_rate' => $this->group_level->playerRate($total_deposit, $val[1]),
					));
				} elseif ($key == 'loss_amount') {
					array_push($playerData, array(
						'name' => lang('Loss'),
						'total' => $total_loss,
						'condition' => $val[1],
						'percentage_rate' => $this->group_level->playerRate($total_loss, $val[1])
					));
				} elseif ($key == 'win_amount') {
					array_push($playerData, array(
						'name' => lang('Win'),
						'total' => $total_win,
						'condition' => $val[1],
						'percentage_rate' => $this->group_level->playerRate($total_win, $val[1])
					));
				}
			}
		}
		return $playerData;
	}

	public function playerRate($playerTotalAmount, $vipConditionAmount) {
		$percentage = $vipConditionAmount==0 ? 0 : round(($playerTotalAmount / $vipConditionAmount * 100));
		if($percentage >= 100) {
			return 100;
			// return 95; // need to wait auto upgrade. depends on schedule(daily, weekly, monthly, yearly) that is set in VIP Setting
		}
		return $percentage;
	}

	/**
	 * get if vip show is done
	 *
	 * @param string
	 * @return array
	 */
	function isVIPShowDone($player_id) {
		$query = $this->db->query("SELECT is_vip_show_done FROM player WHERE playerId = '" . $player_id . "'");

		return $query->row_array();
	}

	/**
	 * set if vip show is done 1:0
	 *
	 * @param string
	 * @return array
	 */
	function setIsVIPShowDone($data) {
		$this->db->set("is_vip_show_done", $data['is_vip_show_done']);
		$this->db->where('playerId', $data['playerId']);

		return $this->db->update('player');
	}

	/***
	 * VIP Cashback Period
	 */

	// temporary add constant here for easy tracking
	const VIP_CASHBACK_DAILY = 0;
	const VIP_CASHBACK_WEEKLY = 1;

	function getWeeklyPeriodInCashbackRule() {
		$this->db->select('vipsettingcashbackrule.vipsettingcashbackruleId');
		$this->db->select('vipsettingcashbackrule.vipLevelName');
		$this->db->select('vipsettingcashbackrule.vipSettingId');
		$this->db->select('vipsettingcashbackrule.cashback_period');
		$this->db->select('vipsetting.groupName');
		$this->db->from('vipsettingcashbackrule');
		$this->db->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId', 'left');
		$this->db->where('vipsettingcashbackrule.cashback_period >=', self::VIP_CASHBACK_WEEKLY);

		return $this->runMultipleRowArray();
	}

	function processWeeklyCashbackinVIP($currentDate, $cashbackSettings, $vipCashbackWeeklySettings) {

		foreach($vipCashbackWeeklySettings as $setting) {
			$day =  $setting['cashback_period'];   // 1-Mon, 2-Tues, 3-Wed, etc..

			$cashbackDay = $this->getDayCashbackSetting($day);
			$currentDay = date('l', strtotime($currentDate));

			// check if current day and cashback day is match (then proccess cashback)
			if($cashbackDay == $currentDay) {
				$this->totalCashbackWeeklySettings($cashbackSettings, $currentDate, null);
			} else {
				$this->utils->debug_log('VIP '. $setting['groupName'].'-'.$setting['vipLevelName'].' cashback still proccessing', "CurrentDay:$currentDay", "CashbackDay:$cashbackDay");
			}
		}
	}

	function totalCashbackWeeklySettings($cashBackSettings, $date, $playerId = null) {

		$startHour = $cashBackSettings->fromHour;
		$endHour = $cashBackSettings->toHour;
		$withdraw_condition_bet_times = isset($cashBackSettings->withdraw_condition) ? $cashBackSettings->withdraw_condition : 0;

		$startDate = date("Y-m-d", strtotime("last week monday"));
		$endDate = date("Y-m-d", strtotime("last week sunday"));

		return $this->totalCashbackWeekly($date, $startHour, $endHour, $playerId, $withdraw_condition_bet_times, $result, $startDate, $endDate);
	}

	function totalCashbackWeekly($date, $startHour, $endHour, $playerId = null,
								 $withdraw_condition_bet_times = 0, &$result = false, $start_date = null, $end_date = null) {

        $use_settled_time_apis = $this->CI->utils->getConfig('api_array_when_calc_cashback_by_settled_time');
		$playerBetByDate = $this->getPlayerBetByDate($date, $startHour, $endHour, $playerId, $start_date, $end_date, NULL, $use_settled_time_apis);
        $playerBetBySettledDate = $this->getPlayerBetBySettledDate($date, $startHour, $endHour, $playerId, $start_date, $end_date, NULL, $use_settled_time_apis);

		$this->utils->debug_log(__METHOD__ . '(): ', $date, $startHour, $endHour, $playerId, 'getPlayerBetByDate count', empty($playerBetByDate)?0:count($playerBetByDate), 'playerBetBySettledDate count', empty($playerBetBySettledDate)?0:count($playerBetBySettledDate));

        $cnt = 0;
        if (empty($playerBetByDate) && empty($playerBetBySettledDate)) {
            return $cnt;
        }

        $mapPlayerBet = $this->calculatePlayerBetTotal($playerBetByDate, $playerBetBySettledDate);
		$commonRules = $this->getCommonCashbackRules();
		$extra_info = ['mapPlayerBet' => $mapPlayerBet, 'commonRules' => $commonRules,];

		$wc_amount_map = array();
		$player_deducted_amt_index = array();

		$isNoCashbackBonusForNonDepositPlayer = $this->isNoCashbackBonusForNonDepositPlayer();
        $extra_info['levelCashbackMap'] = $this->getFullCashbackPercentageMap();

        //get total available player withdraw condition by cashback date
        $wc_amount_map = $this->withdraw_condition->getAllPlayersAvailableAmountOnWithdrawConditionByCashbackSettings($date, $startHour, $endHour, $start_date, $end_date);

        $all_player_bet = [
            $playerBetByDate,
            $playerBetBySettledDate
        ];

        foreach($all_player_bet as $player_bet_amount_list){
            if(empty($player_bet_amount_list)) continue;

            foreach ($player_bet_amount_list as $pbbd) {
                $rate = 0;
                $max_bonus = 0;
                $history_id = null;

                $game_platform_id = $pbbd->game_platform_id;
                $game_description_id = $pbbd->game_description_id;
                $game_type_id = $pbbd->game_type_id;
                $level_id = $pbbd->levelId;
                $player_id = $pbbd->player_id;

                $isPlayerCanCashback = $this->checkPlayerVIPCashbackWeekly($player_id, $date);

                if ($pbbd->betting_total <= 0) {
                    continue;
                }

                // no cashback bonus for non deposit player
                if ($isNoCashbackBonusForNonDepositPlayer) {
                    $playerObj = $this->player_model->getPlayerArrayById($player_id);
                    if ($playerObj['totalDepositAmount'] <= 0) {
                        $this->utils->debug_log('ignore player ' . $player_id . ' for none deposit');
                        continue;
                    }
                }

                // check if player can cashback depends on player vip cashback period
                if (!$isPlayerCanCashback) {
                    $this->utils->debug_log('ignore player ' . $player_id . ' for weekly cashback');
                    continue;
                }

                $playerRate = $this->getPlayerRateFromLevel($player_id, $level_id, $game_platform_id, $game_type_id, $game_description_id, $extra_info);

                if ($playerRate) {
                    $rate = $playerRate->cashback_percentage;
                    $max_bonus = $playerRate->cashback_maxbonus;
                    $history_id = $playerRate->id;
                    $level_id = $playerRate->level_id;
                }

                //only for exist cashback rate
                if ($rate <= 0) {
                    continue;
                }

                $this->utils->debug_log('final rate', $rate, 'player_id', $player_id, 'game_description_id', $game_description_id,
                    'history_id', $history_id, 'level_id', $level_id, 'betting', $pbbd->betting_total, 'betting*rate', $pbbd->betting_total * ($rate / 100));

                $total_date = $date;
                $original_bet_amount = $this->utils->roundCurrencyForShow($pbbd->betting_total);
                $this->auto_deduct_withdraw_condition_from_bet($player_id, $wc_amount_map, $pbbd);
                $cashback_amount = $this->utils->roundCurrencyForShow($pbbd->betting_total * ($rate / 100));
                $withdraw_condition_amount = $this->utils->roundCurrencyForShow($cashback_amount * $withdraw_condition_bet_times);
                $cnt++;

                $this->syncCashbackDaily($player_id, $game_platform_id, $game_description_id, $total_date,
                    $cashback_amount, $history_id, $game_type_id, $level_id, $rate,
                    $this->utils->roundCurrencyForShow($pbbd->betting_total),
                    $withdraw_condition_amount, $max_bonus, $original_bet_amount, self::NORMAL_CASHBACK);
            }
        }

		return $cnt;
	}

	function checkPlayerVIPCashbackWeekly($playerId, $currentDate) {

		$playerCashback = $this->checkPlayerCashbackPeriod($playerId);

		$currentDay = date('l', strtotime($currentDate));

		if (!empty($playerCashback)) {
			$period = $playerCashback['cashback_period'];

			if ($period != self::VIP_CASHBACK_DAILY) {
				$cashbackDay = $this->getDayCashbackSetting($period);
				if ($currentDay == $cashbackDay) {
					return true;
				}
			}
		}
		return false;
	}

	public function checkPlayerCashbackPeriod($playerId) {
		$this->db->select('vipsettingcashbackrule.vipLevel');
		$this->db->select('vipsettingcashbackrule.vipLevelName');
		$this->db->select('vipsettingcashbackrule.vipsettingcashbackruleId');
		$this->db->select('vipsettingcashbackrule.vipSettingId');
		$this->db->select('vipsettingcashbackrule.cashback_period');
		$this->db->select('vipsetting.groupName');
		$this->db->from('playerlevel');
		$this->db->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = playerlevel.playerGroupId', 'left');
		$this->db->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId', 'left');
		$this->db->where('playerlevel.playerId', $playerId);

		return $this->runOneRowArray();
	}

	public function getLevelById($levelId) {
		$this->db->from($this->levelTable)->where($this->levelId, $levelId);

		return $this->runOneRowArray();
	}

	public function getGroupById($groupId){
		$this->db->from($this->groupTable)->where($this->groupId, $groupId);

		return $this->runOneRowArray();
	}

	public function setGradeRecord($arr) {
		$paramsStandard = $this->getGradeRecordParamStandard();
		if (is_array($arr)) {
			foreach ($arr as $key => $val) {
				if (in_array($key, $paramsStandard)) {
					$this->grade_record[$key] = $val;
				}
			}
		}
	}

	/**
	 * Rewrite the part of remark in grade_record
	 *
	 * @param callable $willUpdatedPartOfRemarkCB If return false for No update, others will update into the remark of grade_record.
	 * The param as followings,
	 * - $remark array The array from the this::grade_record['remark'].
	 *
	 * @return array this::grade_record['remark'].
	 */
	public function rewriteRemarkInGrade_record(callable $willUpdatedPartOfRemarkCB ){
		$remark = [];
		if( ! empty($this->grade_record['remark']) ){
			// from grade_record
			$remark = $this->utils->json_decode_handleErr($this->grade_record['remark'], true);
			// $remark = json_decode($this->grade_record['remark'], true) ;
		}
		$newRemark = $willUpdatedPartOfRemarkCB($remark); // 1 param, the $remark form grade_record
		if($newRemark !== false){
			$this->setGradeRecord([
				'remark' => json_encode($newRemark)
			]);
		}
		return $this->grade_record['remark'];
	} // EOF rewriteRemarkInGrade_record

	public function updateGradeRecord($insert_id, $data){
	    $this->db->from('vip_grade_report')->where('id', $insert_id);
        $exist_record = $this->runExistsResult();

        if (!$exist_record) {
            $this->utils->debug_log('record not found, insert id: ', $insert_id);
            return $exist_record;
        }

        $this->db->set($data);
        $this->db->where('id', $insert_id);
        $this->db->update('vip_grade_report');
    }

	public function gradeRecode($validate = true, $db = null) {
        if ($db == null) {
            $db = $this->db;
        }
		$isInsert = true;
		$result = null;
		$paramsStandard = $this->getGradeRecordParamStandard();
		foreach ($paramsStandard as $val) {
			if ($validate && !isset($this->grade_record[$val])) {
				$isInsert = false;
				$this->utils->debug_log('6190.gradeRecode.isInsert',$isInsert, '$val', $val);
			}
		}
		if ($isInsert) {
			$db->insert('vip_grade_report', $this->grade_record);
			$result = $db->insert_id();
		}

		return $result;
	}

    /**
     * Append array to remark without affected to grade_record property
     *
     * @param array $remarkAry
     * @param CI_DB_driver $db
     * @return instger The insert id.
     */
    public function gradeRecodeWithRemarkArray($remarkAry, $db = null) {
        if ($db == null) {
            $db = $this->db;
        }

        $_data = $this->grade_record;
        $remark = []; // default

        if( ! empty($this->grade_record['remark']) ){
            $remark = json_decode($this->grade_record['remark'], true) ;
        }
        if( ! empty($remarkAry) ){
            $_data['remark'] = json_encode(array_merge($remark, $remarkAry));
        }
        $db->insert('vip_grade_report', $_data);
        $result = $db->insert_id();
        return $result;
    }

	public function getGradeRecordParamStandard() {
		return [
			'player_id', 'request_type', 'request_time', 'request_grade', 'level_from', 'level_to',
			'period_start_time', 'period_end_time', 'pgrm_start_time', 'pgrm_end_time', 'remark', 'vipsettingId',
			'vipsettingcashbackruleId', 'vipupgradesettingId','vipupgradesettinginfo', 'newvipId',
			'vipsettingcashbackruleinfo', 'status', 'updated_by', 'applypromomsg'
		];
	}

	/**
	 * queryFirstGradeRecordId
	 * @param  string $type RECORD_UPGRADE or RECORD_DOWNGRADE
	 * @param  string $fromDate
	 * @param  string $toDate
	 * @param  string $fromLevelId
	 * @param  string $toLevelId
	 * @return int
	 */
	public function queryFirstGradeRecordIdBy($playerId, $type, $fromDate, $toDate, $fromLevelId, $toLevelId){

		$this->db->select('id')->from('vip_grade_report')
			->where('player_id', $playerId)
			->where('pgrm_end_time >=', $fromDate)
			->where('pgrm_end_time <=', $toDate)
			->where('vipsettingcashbackruleId', $fromLevelId)
			->where('newvipId', $toLevelId)
			->where('status', self::GRADE_SUCCESS)
			->order_by('pgrm_end_time', 'asc')
			->limit(1);

		return $this->runOneRowOneField('id');
	}

	/**
	 * Get the lastest row by some condition.
	 *
	 * @param string|integer $playerId The player player_id.
	 * @param string $fromDate The start datetime for search.
	 * @param string $toDate The end datetime for search.
	 * @param string $changedGrade If need upgrade/downgrade condition else ignore the grade condition.
	 * Enumerated value: upgrade, downgrade and upgrade_or_downgrade.
	 * @param string $queryFieldname The fiels name in the WHERE clause, used for $fromDate and $toDate.
	 * @param boolean $join_newvipId_and_vipsettingcashbackrule For get the field,"vipSettingId" via the foreign key,"vip_grade_report.newvipId".
	 * @param array $excluded_pk_id_list The pk field, "id" will be excluded in where condition.
	 *
	 * @return array The one row for result.
	 */
	public function queryLastGradeRecordRowBy( $playerId // #1
		, $fromDate // #2
		, $toDate // #3
		, $changedGrade = null // #4
		, $queryFieldname = 'pgrm_end_time' // #5
		, $join_newvipId_and_vipsettingcashbackrule = false // #6
		, $excluded_pk_id_list = [] // #7
	){
		$this->load->model(['vip_grade_report']);
		return $this->vip_grade_report->queryLastGradeRecordRowBy($playerId // #1
			, $fromDate // #2
			, $toDate // #3
			, $changedGrade // #4
			, $queryFieldname // #5
			, $join_newvipId_and_vipsettingcashbackrule // #6
			, $excluded_pk_id_list // #7
		);
	} // EOF queryLastGradeRecordRowBy


	/**
	 * Get all grade history data,
	 * The data with new_vipSettingId via the foreign key,"vip_grade_report.newvipId".
	 *
	 * @param integer $player_id The player.playerId.
	 * @param string $from_date The begin date string for SQL query in the WHERE clause.
	 * @param string $to_date The end date string for SQL query in the WHERE clause.
	 * @param string $changedGrade The fields,"level_from" and "level_to" in WHERE clause.
	 * @param boolean $isCrossGroupFiltered To filter the cross group data.
	 * @return array The rows array.
	 */
	public function searchAllGradeRecords($player_id, $from_date = null, $to_date = null, $changedGrade = null, $isCrossGroupFiltered = true) {

		if( empty($from_date) ){
			$this->load->model(['player_model']);
			$player = $this->player_model->getPlayerById($player_id);
			$playerCreatedOn_DT = new DateTime($player->createdOn);
			$from_date = $this->utils->formatDateTimeForMysql($playerCreatedOn_DT);
		}
		if( empty($to_date) ){
			$now_DT = new DateTime();
			$to_date = $this->utils->formatDateTimeForMysql($now_DT);
		}

		$res = [];
		$new_vipSettingId = null;
		if( $isCrossGroupFiltered ){
			$_changedGrade = null;
			$_queryFieldname = 'pgrm_end_time';
			$_join_newvipId_and_vipsettingcashbackrule = true;
			$theLastGradeRecordRow = $this->queryLastGradeRecordRowBy( $player_id
				, $from_date
				, $to_date
				, $_changedGrade
				, $_queryFieldname
				, $_join_newvipId_and_vipsettingcashbackrule
			);
			$new_vipSettingId = $theLastGradeRecordRow['new_vipSettingId'];
		}

		$this->db->from('vip_grade_report')
			->where('player_id', $player_id)
			->where("pgrm_end_time BETWEEN '{$from_date}' AND '{$to_date}'", null, false)
			// ->where('pgrm_end_time <=', $to_date)
			->where('status', self::GRADE_SUCCESS)
			// ->where('level_from < level_to')
			//->order_by('pgrm_end_time', 'desc')
			->order_by('id', 'desc')
		;

		$this->db->select('vip_grade_report.*, new_vipsettingcashbackrule.vipSettingId as new_vipSettingId');
		$this->db->join('vipsettingcashbackrule AS new_vipsettingcashbackrule', 'vip_grade_report.newvipId = new_vipsettingcashbackrule.vipsettingcashbackruleId', 'left');

		switch( strtolower($changedGrade) ){
			case'upgrade':
				$this->db->where('level_from < level_to');
			break;

			case'downgrade':
				$this->db->where('level_from > level_to');
			break;

			case'upgrade_or_downgrade':
				$this->db->where('level_from <> level_to');
			break;

			default:
			break;
		}

		$rows = $this->runMultipleRowArray();

		if( ! empty($rows) ){
			foreach($rows as $indexNumber => $row){
				if( ! empty($new_vipSettingId) ){ // filter the different groups of the Last record.
					if($row['new_vipSettingId'] == $new_vipSettingId){
						$res[] = $row;
					}else{
						break; // loop exit
					}
				}else{
					$res[] = $row;
				}
			}// EOF foreach($rows as $indexNumber => $row){...
		}
		// $this->utils->debug_log('8861.searchAllGradeRecords', 'last_query:', $this->db->last_query(), '$res.counter:', count($res));

		return $res;
	} // EOF searchAllGradeRecords

	public function searchAllUpgradeRecords($player_id, $from_date, $to_date) {
		$this->db->from('vip_grade_report')
			->where('player_id', $player_id)
			->where("pgrm_end_time BETWEEN '{$from_date}' AND '{$to_date}'", null, false)
			// ->where('pgrm_end_time <=', $to_date)
			->where('status', self::GRADE_SUCCESS)
			->where('level_from < level_to')
			->order_by('pgrm_end_time', 'desc')
			->order_by('id', 'desc')
		;

		$res = $this->runMultipleRowArray();

		$this->utils->debug_log(__METHOD__, $this->db->last_query());

		return $res;
	}

	public function convertTypeToString($t){
		switch ($t) {
			case self::NORMAL_CASHBACK:
				return lang('Normal');
				break;
			case self::FRIEND_REFERRAL_CASHBACK:
				return lang('Referral');
				break;
		}
		return lang('Normal');
	}

    /**
     * Retain failed upgrade and downgrade data for the specified number of days
     * The default number of days is seven days
     *
     * @param integer
     * @return boolean
     */
    function retainFailedDataForSpecificDay($days=7){

        $days = (is_int($days) ? $days : $this->utils->getConfig('retain_vip_grade_report_failed_data_for_specific_days'));

        if ($days > 0) {
            $date = date('Y-m-d', strtotime("-$days days"));
            $this->db->where('request_time <=', $date);
        }

        $this->db->where('status', 0);
        return $this->db->delete('vip_grade_report');
    }

    /**
     *
     * from $levelId down to first level
     * high level to low level
     * @param  int $levelId
     * @return array high level to low level
     *
     */
    public function getLevelListOnSameGroupFrom($levelId){
    	$levelIdList=[];
    	$this->db->from($this->levelTable)
    	    ->where($this->levelId, $levelId);
    	$row=$this->runOneRowArray();
    	if(!empty($row)){
	    	$groupId=$row[$this->groupId];
	    	$vipLevel=$row['vipLevel'];
    		//same group id and <=level id
    		$this->db->from($this->levelTable)->where($this->groupId, $groupId)
				->where('vipLevel <=', $vipLevel)->order_by('vipLevel', 'desc');
			$rows=$this->runMultipleRowArray();
			if(!empty($rows)){
				foreach ($rows as $row) {
					$levelIdList[]=$row[$this->levelId];
				}
			}
    	}

    	return $levelIdList;
	}

	/**
	 * Get theDatetime Ranges for now and previous (from the player register date).
	 *
	 * @param string $playerCreatedOn The player register date, i.e. "2020-02-11 12:23:43". (will be start datetime). like as return of utils::formatDateForMysql().
	 * @param string $time_exec_begin The specified datetime for now that's will apply to the param of new DateTime(). (will be end datetime)
	 * @param integer|string $min_required The minimum time required, i.e. +6, +3.
	 * @param string $unit_required The examples,(('sec' | 'second' | 'min' | 'minute' | 'hour' | 'day' | 'fortnight' | 'forthnight' | 'month' | 'year') 's'?) | 'weeks' | daytext, Ref.to https://www.php.net/manual/en/datetime.formats.relative.php
	 * @return array $return The array contains fromDatetime, toDatetime, previousFromDatetime, previousToDatetime, isSufficient and diffInSeconds4Result.
	 */
	public function calcDatetimeRangeAndPreviousFromDatetime(	$playerCreatedOn // # 1
															, $time_exec_begin = 'now' // # 2
															, $min_required = null // # 3
															, $unit_required = 'sec' // # 4
	){

		//from player register date to now
		$register_date = new DateTime($playerCreatedOn);
		$now = new DateTime($time_exec_begin);
		$fromDatetime = $register_date->format('Y-m-d H:i:s');
		$toDatetime = $now->format('Y-m-d H:i:s');
		$previousFromDatetime = $fromDatetime;
		$previousToDatetime = $toDatetime;
        $this->utils->debug_log('7575.toDatetime:', $toDatetime, 'fromDatetime:', $fromDatetime, 'min_required:', $min_required);
		$isSufficient = null; // for collection
        $diffInSeconds4Result =null; // for collection
		if( ! is_null($min_required) ){
			$requiredToDatetime = null;
			$isSufficient = $this->isSufficient4RequiredDatetimeRange($fromDatetime, $toDatetime, $min_required, $unit_required, $requiredToDatetime, $diffInSeconds4Result);
			$fromDatetime =$this->utils->formatDateTimeForMysql($requiredToDatetime);
		}
		$return = [ $fromDatetime // #1
                    , $toDatetime // #2
                    , $previousFromDatetime // #3
                    , $previousToDatetime // #4
                    , $isSufficient // #5
                    , $diffInSeconds4Result // #6
                ];

		return $return;
	}// EOF calcDatetimeRangeAndPreviousFromDatetime

	/**
	 * Check The Datetime Range Is Sufficient.
	 *
	 * @param string $fromDatetime The Datetime string.
	 * @param string $toDatetime The Datetime string.
	 * @param integer|string $min_required The minimum time required, i.e. +6, +3.
	 * @param string $unit_required The examples,(('sec' | 'second' | 'min' | 'minute' | 'hour' | 'day' | 'fortnight' | 'forthnight' | 'month' | 'year') 's'?) | 'weeks' | daytext, Ref.to https://www.php.net/manual/en/datetime.formats.relative.php
     * @param DateTime $requiredFromDatetime The required begin date time of stay the level.
     * @param integer $diffInSeconds4Result The time who have stayed at this level, it to deducts the time who need to stay at this level.
     *     ex: 100-20=80, This means that the time has exceeded maintenance level.
     *     ex: 5-20=-15, This means that it will take another 15 seconds to meet the time of maintenance level.
	 * @return boolean $isSufficient If Sufficient then true else false.
	 */
	public function isSufficient4RequiredDatetimeRange( $fromDatetime // #1
                                                    , $toDatetime // #2
                                                    , $min_required = null // #3
                                                    , $unit_required = 'sec' // #4
                                                    , &$requiredFromDatetime = null // #5
                                                    , &$diffInSeconds4Result = null // #6
    ){
		$isSufficient = null;
		$_fromDatetime = new DateTime($fromDatetime);
		$_toDatetime = new DateTime($toDatetime);

		if( $_fromDatetime->format('U') > $_toDatetime->format('U') ){
			// if fromDatetime > toDatetime then swap.
			$_fromDatetime = new DateTime($toDatetime);
			$_toDatetime = new DateTime($fromDatetime);
		}

		$diffInSeconds = $_toDatetime->format('U') - $_fromDatetime->format('U');

		if($min_required > 0) { // convert the time to before from now. ( 14 day will be -14 day ago
			$min_required = -1 * $min_required;
		}
		$modify = sprintf('%d %s', $min_required, $unit_required); // ex: -14 day
		$requiredFromDatetime = clone $_toDatetime;
		$requiredFromDatetime->modify($modify);

		$diffInSeconds4Required = $_toDatetime->format('U') - $requiredFromDatetime->format('U');
        $this->utils->debug_log('isSufficient4RequiredDatetimeRange fromDatetime', $fromDatetime, 'toDatetime', $toDatetime, 'requiredFromDatetime', $requiredFromDatetime );
        $this->utils->debug_log('isSufficient4RequiredDatetimeRange diffInSeconds', $diffInSeconds
                        , $_toDatetime->format('Y-m-d H:i:s') .'~'. $_fromDatetime->format('Y-m-d H:i:s')
						, 'diffInSeconds4Required', $diffInSeconds4Required
                        , $_toDatetime->format('Y-m-d H:i:s') .'~'. $requiredFromDatetime->format('Y-m-d H:i:s')
					);

        // The time, $diffInSeconds, that means that player who had stayed at this level,
        // The time, $diffInSeconds4Required, that means that player who Should stay at this level by Required(, Downgrade maintenance)
        // The time, $diffInSeconds4Result, that means that player who needs to stay for some time in the current level, to met the Required time .
        // When $diffInSeconds4Result is negative, the required time is not met
        // When $diffInSeconds4Result is positive, the required time has be met and the level may be adjusted.
        $diffInSeconds4Result = $diffInSeconds- $diffInSeconds4Required; // 已停留該等級的時間 扣掉 需要停留該等級的時間
		if( $diffInSeconds >= $diffInSeconds4Required){
			$isSufficient = true;
		}else{
			$isSufficient = false;
		}
		return $isSufficient;
	} // EOF isSufficient4RequiredDatetimeRange

	/**
	 * Check The Down Maintain Is Met?
	 * ( Turnover to Maintain for 3 months )
	 *
	 * @param integer $playerId The player playerId.
	 * @param string $playerCreatedOn The player registration date, if No Last Grade Date.
	 * @param integer $downMaintainTimeLength The times of the to Maintain.
	 * @param integer $downMaintainUnit Enumerate. Such as self::DOWN_MAINTAIN_TIME_UNIT_DAY, self::DOWN_MAINTAIN_TIME_UNIT_WEEK and self::DOWN_MAINTAIN_TIME_UNIT_MONTH.
	 * @param float $downMaintainConditionBetAmount  The Deposit Amount ≥ N in "Level Maintain" Div.
	 * @param float $downMaintainConditionDepositAmount The Deposit Amount ≥ N in "Level Maintain" Div.
	 * @param string $time_exec_begin The now date time string, if need.
	 * @return boolean
	 */
	public function isMet4DownMaintain( $playerId // # 1
										, $playerCreatedOn // # 2
                                        , $playerLevelId // # 2.1
										, $downMaintainTimeLength // # 3
										, $downMaintainUnit // # 4
										, $downMaintainConditionBetAmount // # 5
										, $downMaintainConditionDepositAmount // # 6
										, &$downMaintainFormula // # 7
										, $time_exec_begin = 'now' // # 8
	){
        $isLastGradeEq2PlayerLevelId = null;
		$isConditionMet = null; // maybe  isSufficient false then isConditionMet will be null.
		$now = new DateTime($time_exec_begin);
		$now4formatDateTimeForMysql = $this->utils->formatDateTimeForMysql($now);
		$toDatetime = $now4formatDateTimeForMysql;
		$queryFieldname = 'request_time';

		$theLastGradeRecordRow = $this->queryLastGradeRecordRowBy( $playerId // #1
                                                                    , $playerCreatedOn // #2
                                                                    , $now4formatDateTimeForMysql // #3
                                                                    , 'upgrade_or_downgrade' // #4, the param,"upgrade" for downgrade.
                                                                    , $queryFieldname // #5
                                                                    , false // #6
                                                                    , [] // #7
                                                                );
        $fromDatetime = null;
		if( empty($theLastGradeRecordRow) ){
			// // will ignore if not found.
			// $fromDatetime = $toDatetime;
			// will use Registration Date if not found.
			$fromDatetime = $playerCreatedOn;

		}else{
            $bpld_options = $this->config->item('batch_player_level_downgrade');
            $_force2isLastGradeEq2PlayerLevelId = false;
            $isLastGradeEq2PlayerLevelId = (!empty($theLastGradeRecordRow['newvipId'] == $playerLevelId))? true: false;
            if( ! is_null($bpld_options['isLastGradeEq2PlayerLevelId']) ){
                // force assign from Config
                $isLastGradeEq2PlayerLevelId = $bpld_options['isLastGradeEq2PlayerLevelId'];
                $_force2isLastGradeEq2PlayerLevelId = true;
            }

            if($isLastGradeEq2PlayerLevelId){
                $fromDatetime = $theLastGradeRecordRow['request_time'];
            }else{
                $fromDatetime = $now4formatDateTimeForMysql;
            }
		}
        $this->utils->debug_log('7696.playerId:',$playerId
            , 'playerLevelId:', $playerLevelId
            , 'fromDatetime:', $fromDatetime
            , 'theLastGradeRecordRow:', $theLastGradeRecordRow
            , '_force2isLastGradeEq2PlayerLevelId:', empty($_force2isLastGradeEq2PlayerLevelId)? null: $_force2isLastGradeEq2PlayerLevelId
        );
		// 若沒有滿 3 個月，就忽略。
		// will ignore if less than 3 monthes.
		switch($downMaintainUnit){
			default:
			case self::DOWN_MAINTAIN_TIME_UNIT_DAY:
				$min_required = $downMaintainTimeLength;
				$unit_required = 'day';
			break;
			case self::DOWN_MAINTAIN_TIME_UNIT_WEEK:
				$min_required = $downMaintainTimeLength * 7; // 7 day/week
				$unit_required = 'day';
			break;
			case self::DOWN_MAINTAIN_TIME_UNIT_MONTH:
				$min_required = $downMaintainTimeLength;
				$unit_required = 'month';
			break;
		}
		list($fromDatetime // # 1
			, $toDatetime // # 2
			, $previousFromDatetime // # 3
			, $previousToDatetime // # 4
			, $isSufficient // # 5
            , $requiredSecToSufficient // # 6
		) = $this->calcDatetimeRangeAndPreviousFromDatetime( $fromDatetime // # 1
															, $toDatetime // # 2
															, $min_required // # 3
															, $unit_required ); // # 4

        $bet_amount = null;
		$deposit_amount = null;
        $insufficientReason = '';
		if($isSufficient){
			// to calc bet amount and deposit amount in datetime range

            if( $this->enable_multi_currencies_totals ){
                $gameLogData = $this->group_level_lib->getPlayerTotalBetWinLossWithForeachMultipleDBWithoutSuper($playerId, $fromDatetime, $toDatetime);
            }else{
                $gameLogData = $this->total_player_game_day->getPlayerTotalBetWinLoss($playerId, $fromDatetime, $toDatetime);
            }
			$bet_amount = $gameLogData['total_bet'];

            $add_manual = false; // as default
            list($deposit_amount) = $this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers($playerId, $fromDatetime, $toDatetime, $add_manual, $this->enable_multi_currencies_totals);

			/// Player will be downgraded after the guaranteed time if not finish the condition below.
			// Deposit Amount ≥ downMaintainConditionDepositAmount (AND) Bet Amount ≥ downMaintainConditionBetAmount
			if( (float)$bet_amount < (float)$downMaintainConditionBetAmount
				|| (float)$deposit_amount < (float)$downMaintainConditionDepositAmount
			){
				$isConditionMet = true; // Its will be probable to downgrade
			}else{
				$isConditionMet = false; // Disable downgrade for Downgrade Maintain time
			}
		}else{ /// isSufficient=false,
            // In the case, the $requiredSecToSufficient has been negative number
            if( empty($theLastGradeRecordRow) ){
                $insufficientReason = 'Skip, the Lastest Grade Record data has not found.';
            }elseif($isLastGradeEq2PlayerLevelId !== false){ // null or true
                if($requiredSecToSufficient < 0){
                    $_requiredSecToSufficient = $requiredSecToSufficient* -1;
                }
                $_timeParts = $this->utils->secondsToTime($_requiredSecToSufficient, $_reqSecToSuff);
                // $insufficientReason = sprintf('still take another %s to meet the maintenance time', var_export($_reqSecToSuff, true));
                $insufficientReason = sprintf('It still take another %s to meet the maintenance time', $_timeParts);
            }else{
                $insufficientReason = 'Skip, the Lastest Grade Record data is Not the same level data.';
            }
			$isConditionMet = false; // still during Downgrade Maintain time
		}

		// for remark of vip_grade_report.
		// need mapping to Remark of Grade Report.
		$downMaintainFormula = [];
		// for calc
		$downMaintainFormula4calc = [];
		$downMaintainFormula4calc['dateTimeRange'] = [];
		$downMaintainFormula4calc['dateTimeRange']['fromDatetime'] = $fromDatetime;
		$downMaintainFormula4calc['dateTimeRange']['toDatetime'] = $toDatetime;
		$downMaintainFormula4calc['playerId'] = $playerId;
		$downMaintainFormula['calc'] = $downMaintainFormula4calc;
		// for rules
		$downMaintainFormula4rule = [];
		$downMaintainFormula4rule['downMaintainTimeLength'] = $min_required;
		$downMaintainFormula4rule['downMaintainUnit'] = $unit_required;
		$downMaintainFormula4rule['downMaintainConditionDepositAmount'] = $downMaintainConditionDepositAmount;
		$downMaintainFormula4rule['downMaintainConditionBetAmount'] = $downMaintainConditionBetAmount;
		$downMaintainFormula['rule'] = $downMaintainFormula4rule;
		// for result
		$downMaintainFormula4result = [];
		$downMaintainFormula4result['betAmount'] = $bet_amount;
		$downMaintainFormula4result['deposit_amount'] = $deposit_amount;
		$downMaintainFormula4result['isSufficient'] = $isSufficient;
        $downMaintainFormula4result['insufficientReason'] = $insufficientReason;
		$downMaintainFormula4result['isConditionMet'] = $isConditionMet;
		$downMaintainFormula['result'] = $downMaintainFormula4result;
$this->utils->debug_log('7744.isMet4DownMaintain isSufficient', $isSufficient, 'isConditionMet:', $isConditionMet);
		return $isConditionMet;
	}// EOF isMet4DownMaintain

	public function syncCashbackGamePlatform($data){
		//if not exist
		$this->db->from('group_level_cashback_game_platform')
			->where('vipsetting_cashbackrule_id', $data['vipsetting_cashbackrule_id'])
			->where('game_platform_id', $data['game_platform_id']);

		$succ=true;
		if (!$this->runExistsResult()) {
			//add to group_level_cashback_game_platform
			$succ=$this->insertData('group_level_cashback_game_platform', $data);
		} else {
			$this->utils->debug_log('not new game platform', $data['game_platform_id'], $data['vipsetting_cashbackrule_id']);
		}
		return $succ;
	}

	public function syncCashbackGameType($data){
		//if not exist
		$this->db->from('group_level_cashback_game_type')
			->where('vipsetting_cashbackrule_id', $data['vipsetting_cashbackrule_id'])
			->where('game_type_id', $data['game_type_id']);

		$succ=true;
		if (!$this->runExistsResult()) {
			//add to group_level_cashback_game_type
			$succ=$this->insertData('group_level_cashback_game_type', $data);
		} else {
			$this->utils->debug_log('not new game type', $data['game_type_id'], $data['vipsetting_cashbackrule_id']);
		}
		return $succ;
	}

	public function syncCashbackGameDescription($data){
		//if not exist
		$this->db->from('group_level_cashback_game_description')
			->where('vipsetting_cashbackrule_id', $data['vipsetting_cashbackrule_id'])
			->where('game_description_id', $data['game_description_id']);

		$succ=true;
		if (!$this->runExistsResult()) {
			//add to group_level_cashback_game_description
			$succ=$this->insertData('group_level_cashback_game_description', $data);
		} else {
			$this->utils->debug_log('not new game description', $data['vipsetting_cashbackrule_id'], $data['game_description_id']);
		}
		return $succ;
	}

	/**
	 * detail: fix player table group name
	 *
	 * @param int $player_id
	 * @return Boolean
	 */
	public function fix_wrong_group_name_players($playerId = null) {
		$this->db->select(' player.playerId,
							player.username,
							player.levelId,
							player.levelName,
							vipsettingcashbackrule.vipSettingId as vipSettingId,
							vipsetting.groupName as vipsetting_group,
							player.groupName as wrong_player_group,
							')
		->from($this->playerTable)
		->join('vipsettingcashbackrule', 'vipsettingcashbackrule.vipsettingcashbackruleId = player.levelId ', 'left')
		->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId ', 'left')
		->where('player.groupName != vipsetting.groupName');

		if (!empty($playerId)) {
			$this->db->where('playerId',$playerId);
		}

		$wrong_group_name_players = $this->runMultipleRow();
		$this->utils->printLastSQL();
		$this->utils->debug_log(__METHOD__,'wrong_group_name_players',$wrong_group_name_players);
		$count = 0;
		foreach ($wrong_group_name_players as $value) {
			if ($value->vipsetting_group != $value->wrong_player_group) {
				$count += 1;
				$this->db->set('groupName', $value->vipsetting_group);
				$this->db->where('playerId', $value->playerId);
				$this->db->update($this->playerTable);
			}
		}

		$this->utils->debug_log(__METHOD__,'count',$count);

		return $this->db->affected_rows();
	}


	/**
	 * Add Game/s to VIP casback if auto_tick_new_game_in_cashback_tree = 1 field in vipsetting table
	 * @param int $gamePlatformId
	 * @param int $gameTypeId
	 * @param int $gameDescriptionId
	 *
	 * @return void
	*/
	public function addGameIntoVipGroupCashback($gamePlatformId,$gameTypeId,$gameDescriptionId)
	{
		if($this->utils->getConfig('ignore_auto_add_new_game_to_vip_cashback')){
			$this->utils->debug_log('ignore_auto_add_new_game_to_vip_cashback enabled, ignore this game', $gameDescriptionId);
			return;
		}

		$this->load->model(['game_description_model','vipsetting']);

		$vipLevels = $this->vipsetting->getVipGroupLevelInfoOfEnableAutoTickNewGame();
		$isGamePlatformAndGameTypeExist = $this->game_description_model->getGamePlatformIdByGameTypeId($gameTypeId);

		if(is_array($vipLevels) && count($vipLevels) > 0){
			$now=$this->utils->getNowForMysql();
			foreach($vipLevels as $vipLevel){

				$vipsetting_cashbackrule_id = isset($vipLevel['vipsettingcashbackruleId']) ? $vipLevel
				['vipsettingcashbackruleId'] : null;


				if(! empty($vipsetting_cashbackrule_id) && !empty($gamePlatformId) && !empty($gameTypeId) && !empty($gameDescriptionId) && (!empty($isGamePlatformAndGameTypeExist && $isGamePlatformAndGameTypeExist == $gamePlatformId))){
					# for game platform of game
					$succ=$this->syncCashbackGamePlatform([
						'vipsetting_cashbackrule_id' => $vipsetting_cashbackrule_id,
						'game_platform_id' => $gamePlatformId,
						'percentage' => 0,# default to 0
						'status' => self::DB_TRUE,
						'updated_at' => $now,
					]);
					if(!$succ){
						$this->utils->error_log('sync cashback game platform failed',
							['vipsetting_cashbackrule_id'=>$vipsetting_cashbackrule_id, 'game_platform_id'=>$gamePlatformId]);
						throw new Exception('sync cashback game platform failed');
					}

					# for game type of game
					$succ=$this->syncCashbackGameType([
						'vipsetting_cashbackrule_id' => $vipsetting_cashbackrule_id,
						'game_type_id' => $gameTypeId,
						'percentage' => 0,# default to 0
						'status' => self::DB_TRUE,
						'updated_at' => $now,
					]);
					if(!$succ){
						$this->utils->error_log('sync cashback game type failed',
							['vipsetting_cashbackrule_id'=>$vipsetting_cashbackrule_id, 'game_type_id'=>$gameTypeId]);
						throw new Exception('sync cashback game type failed');
					}

					# for game description/or the primary key(id) of game_description table of game
					$succ=$this->syncCashbackGameDescription([
						'vipsetting_cashbackrule_id' => $vipsetting_cashbackrule_id,
						'game_description_id' => $gameDescriptionId,
						'percentage' => 0,# default to 0
						'status' => self::DB_TRUE,
						'updated_at' => $now,
					]);
					if(!$succ){
						$this->utils->error_log('sync cashback game description failed',
							['vipsetting_cashbackrule_id'=>$vipsetting_cashbackrule_id, 'game_description_id'=>$gameDescriptionId]);
						throw new Exception('sync cashback game description failed');
					}

				}
			}
		}
	}

	/**
	 * Get GameLogData,"bet, win and loss" form total_player_game_minute
	 *
	 * @param integer $playerId The field, "player.playerId".
	 * @param string $fromDatetime The Datetime string.
	 * @param string $toDatetime The Datetime string.
	 * @param integer|array $where_game_platform_id
	 * @param integer|array $where_game_type_id
	 * @return array $gameLogData The format,
	 * - $gameLogData[total_bet] integer The bet amount.
	 * - $gameLogData[total_win] integer The win amount.
	 * - $gameLogData[total_loss] integer The loss amount.
	 */
	public function getGameLogDataFromTotalPlayerGameMinute( $playerId // #1
															, $fromDatetime // #2
															, $toDatetime // #3
															, $where_game_platform_id = null // #4
															, $where_game_type_id = null // #5
	){
		$total_player_game_table = 'total_player_game_minute';
		$where_date_field = 'date_minute';
		$fromDatetime4minute = $this->utils->formatDateMinuteForMysql(new DateTime($fromDatetime));
		$toDatetime4minute = $this->utils->formatDateMinuteForMysql(new DateTime($toDatetime));
        if( $this->enable_multi_currencies_totals ){
            $gameLogData = $this->group_level_lib->getPlayerTotalBetWinLossWithForeachMultipleDBWithoutSuper( $playerId // #1
                                                                                                            , $fromDatetime4minute // #2
                                                                                                            , $toDatetime4minute // #3
                                                                                                            , $total_player_game_table // #4
                                                                                                            , $where_date_field // #5
                                                                                                            , $where_game_platform_id // #6
                                                                                                            , $where_game_type_id // #7
                                                                                                        );
        }else{
            $gameLogData = $this->total_player_game_day->getPlayerTotalBetWinLoss( $playerId // #1
                                                                                    , $fromDatetime4minute // #2
                                                                                    , $toDatetime4minute // #3
                                                                                    , $total_player_game_table // #4
                                                                                    , $where_date_field // #5
                                                                                    , $where_game_platform_id // #6
                                                                                    , $where_game_type_id // #7
                                                                                );
        }

		return $gameLogData;
	} // EOF getGameLogDataFromTotalPlayerGameMinute
	/**
	 * Get the Separated GameLogData from total_player_game_minute.
	 *
	 * @param integer $playerId The field, "player.playerId".
	 * @param [type] $fromDatetime
	 * @param [type] $toDatetime
	 * @param array $betAmountSettings The bet_amount_settings from the field, "vip_upgrade_setting.bet_amount_settings".
	 * @return void
	 */
	public function getSeparatedGameLogDataFromTotalPlayerGameMinute( $playerId // #1
																	, $fromDatetime // #2
																	, $toDatetime // #3
																	, $bet_amount_settings // #4
	){
		$separatedGameLogData = [];
		$total_player_game_table = 'total_player_game_minute';
		$where_date_field = 'date_minute';
		$fromDatetime4minute = $this->utils->formatDateMinuteForMysql(new DateTime($fromDatetime));
		$toDatetime4minute = $this->utils->formatDateMinuteForMysql(new DateTime($toDatetime));

		if( ! empty($bet_amount_settings['itemList']) ){
			$separatedGameLogData = $bet_amount_settings['itemList'];
			foreach($bet_amount_settings['itemList'] as $indexNumber => $currItem){
				$where_game_platform_id = null;
				$where_game_type_id = null;
				if($currItem['type'] == 'game_type'){
					$where_game_type_id = $currItem['game_type_id'];
					$_idStr = $currItem['type']. '_id_'. $currItem['game_type_id']; // $_idStr like as game_type_id_123
				}else if($currItem['type'] == 'game_platform'){
					$where_game_platform_id = $currItem['game_platform_id'];
					$_idStr = $currItem['type']. '_id_'. $currItem['game_platform_id']; // $_idStr like as game_platform_id_345
				}
                if( $this->enable_multi_currencies_totals ){
                    $currGameLogData = $this->group_level_lib->getPlayerTotalBetWinLossWithForeachMultipleDBWithoutSuper( $playerId // #1
                                                                                                                    , $fromDatetime4minute // #2
                                                                                                                    , $toDatetime4minute // #3
                                                                                                                    , $total_player_game_table // #4
                                                                                                                    , $where_date_field // #5
                                                                                                                    , $where_game_platform_id // #6
                                                                                                                    , $where_game_type_id // #7
                                                                                                                );
                }else{
                    $currGameLogData = $this->total_player_game_day->getPlayerTotalBetWinLoss( $playerId // #1
                                                                                            , $fromDatetime4minute // #2
                                                                                            , $toDatetime4minute // #3
                                                                                            , $total_player_game_table // #4
                                                                                            , $where_date_field // #5
                                                                                            , $where_game_platform_id // #6
                                                                                            , $where_game_type_id // #7
                                                                                        );
                }

				$separatedGameLogData[$indexNumber]['result_amount'] = $currGameLogData['total_bet'];
				$separatedGameLogData[$indexNumber]['count'] = $currGameLogData['total_bet']; // for reporting

				/// for replace into the Formula.
				$separatedGameLogData[$indexNumber][$_idStr] = $currGameLogData['total_bet'];
			} // EOF foreach($bet_amount_settings['itemList'] as $indexNumber => $currItem){...
		}


		return $separatedGameLogData;
	} // EOF getSeparatedGameLogDataFromTotalPlayerGameMinute

	/**
	 * To Valid DateFrom Smaller Than DateTo
	 * @param string $dateFrom
	 * @param string $dateTo
	 * @return boolean If true, it's mean timestamp of dateFrom smaller than DateTo.
	 */
	public function doValidDateFromSmallerThenDateTo($dateFrom, $dateTo){
		$isValid = null;
		$dateFrom_DateTime = new DateTime($dateFrom);
		$dateFrom_Timestamp = $dateFrom_DateTime->getTimestamp();
		$dateTo_DateTime = new DateTime($dateTo);
		$dateTo_Timestamp = $dateTo_DateTime->getTimestamp();
		if($dateFrom_Timestamp > $dateTo_Timestamp){
			$isValid = false;
		}else{
			$isValid = true;
		}
		return $isValid;
	} // EOF doValidDateFromSmallerThenDateTo

	/**
	 * Get the during time by accumulation setting (ref. to the player)
	 *
	 * @param integer $accumulationMode The attr. pls reference to Group_level::ACCUMULATION_MODE_FROM_REGISTRATION and Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE.
	 * @param array $upgradeSched The return of Group_level::getScheduleDateRange() for "No accumulation" case.
	 * @param integer $playerId The field, "player.playerId".
	 * @param string $playerCreatedOn The field, "player.createdOn".
	 * @param null|string $time_exec_begin The Datetime class first param.
	 * @param array $schedule For Group_level::ACCUMULATION_MODE_DISABLE, pls reference to the param of Group_level::getScheduleDateRange().
	 * @param integer $subNumber For Group_level::ACCUMULATION_MODE_DISABLE, pls reference to the param of Group_level::getScheduleDateRange().
	 * @param boolean $isBatch For Group_level::ACCUMULATION_MODE_DISABLE, pls reference to the param of Group_level::getScheduleDateRange().
	 * @param boolean $setHourly For Group_level::ACCUMULATION_MODE_DISABLE, pls reference to the param of Group_level::getScheduleDateRange().
	 * @return array The $duringDatetime format,
	 * - $duringDatetime[from] string The Datetim string, ex:"2020-12-23 01:23:45".
	 * - $duringDatetime[to] string The Datetim string, ex:"2020-12-23 01:23:45".
	 * - $duringDatetime[previousFrom] string The Datetim string, ex:"2020-12-23 01:23:45".
	 * - $duringDatetime[previousTo] string The Datetim string, ex:"2020-12-23 01:23:45".
	 */
	public function getDuringDatetimeFromAccumulationByPlayer(	$accumulationMode // # 1
																, $upgradeSched // # 2
																, $playerId // # 3
																, $playerCreatedOn // # 4
																, $time_exec_begin = null // # 5
																, $schedule = [] // # 6
																, $subNumber = 1 // # 7
																, $isBatch = true // # 8
																, $setHourly = false // # 9
																, $adjustGradeTo = 'up' // #10
	){
		$now = new DateTime($time_exec_begin);
		$toDatetime = $now->format('Y-m-d H:i:s');
		$duringDatetime = [];
		$previousFromDatetime = null;
		$previousToDatetime = null;
		switch( $accumulationMode ){
			default:
			case Group_level::ACCUMULATION_MODE_DISABLE:
				// cloned from "} else { // non-Accumulation"
				$fromDatetime = $upgradeSched['dateFrom'];
				$toDatetime = $upgradeSched['dateTo'];
				if($setHourly === true) {
					// $now = new DateTime();
					$now = new DateTime($time_exec_begin);
					$toDatetime = $now->format('Y-m-d H:i:s');
				}
				$previousSched = $this->getScheduleDateRange($schedule, $subNumber, $isBatch, $setHourly, $time_exec_begin, $adjustGradeTo);
$this->utils->debug_log('OGP-21051.8571.upgradeSched:', $upgradeSched);
				$previousFromDatetime = $previousSched['dateFrom'];
				$previousToDatetime = $previousSched['dateTo'];
			break;

			case Group_level::ACCUMULATION_MODE_FROM_REGISTRATION:
				$now = new DateTime();
				$_time_exec_begin = $now->format('Y-m-d H:i:s');
				list($fromDatetime, $toDatetime, $previousFromDatetime, $previousToDatetime) = $this->calcDatetimeRangeAndPreviousFromDatetime($playerCreatedOn, $time_exec_begin);
			break;

			// case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE. "ISSUE":
			// // case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE:
			// 	$now = new DateTime();
			// 	$_time_exec_begin = $now->format('Y-m-d H:i:s');
			// 	$theLastGradeRecordRow = $this->queryLastGradeRecordRowBy($playerId, $playerCreatedOn, $toDatetime, 'upgrade_or_downgrade');
			// 	if( empty($theLastGradeRecordRow) ){
			// 		$fromDatetime = $playerCreatedOn;
			// 	}else{
			// 		$fromDatetime = $theLastGradeRecordRow['request_time'];
			// 	}
			// 	list($fromDatetime, $toDatetime, $previousFromDatetime, $previousToDatetime) = $this->calcDatetimeRangeAndPreviousFromDatetime($fromDatetime, $_time_exec_begin);
			// break;

			case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE. "PATCHED": // PATCHED
			case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE:
				$now = new DateTime();
				$_time_exec_begin = $now->format('Y-m-d H:i:s');
				$theLastGradeRecordRow = $this->queryLastGradeRecordRowBy($playerId, $playerCreatedOn, $_time_exec_begin, 'upgrade_or_downgrade'); // PATCHED
				$this->utils->debug_log('OGP-22219 issue','in LAST_CHANGED_GEADE.theLastGradeRecordRow:', $theLastGradeRecordRow);
				if( empty($theLastGradeRecordRow) ){
					$fromDatetime = $playerCreatedOn;
				}else{
					$fromDatetime = $theLastGradeRecordRow['pgrm_start_time'];
				}
				$time_exec_beginDT = new DateTime($time_exec_begin); // Request Time of this up/down grade
				$fromDatetimeDT = new DateTime($fromDatetime); // theLastGradeRecordRow.pgrm_start_time
				if( $time_exec_beginDT->getTimestamp() < $fromDatetimeDT->getTimestamp() ){
					// OGP-22219 issue, the Last Grade Record timestamp greater than this Request Time's timestamp
					$this->utils->debug_log('OGP-22219 issue', 'time_exec_begin:', $time_exec_begin, 'fromDatetime:', $fromDatetime, '_time_exec_begin:', $_time_exec_begin);
					$time_exec_begin = $_time_exec_begin;
				}else{
					// Patch for 6. Grace Period: (Last Process Start Time) ~ (This Process Start Time) of OGP-22219 in OGP-22410
					$time_exec_begin = $_time_exec_begin;
				}
				list($fromDatetime, $toDatetime, $previousFromDatetime, $previousToDatetime) = $this->calcDatetimeRangeAndPreviousFromDatetime($fromDatetime, $time_exec_begin); // PATCHED
			break;
			case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET:
					/// Step1. Get the latest Period range in the latest changed grade after
					// the amounts, bet and deposit, that should log into the player_accumulated_amounts_log data-table.
					// $now = new DateTime(); /// Disable to simulate the current moment.

					// OGP-25082, vip_grade_report.id in this multi-level upgrade checking
					$excluded_vip_grade_report_id_list = [];
					if( ! empty($this->PLAH['multiple_upgrade_buff']['vip_grade_report_id_list']) ){
						$excluded_vip_grade_report_id_list = $this->PLAH['multiple_upgrade_buff']['vip_grade_report_id_list'];
					}

					$_time_exec_begin = $now->format('Y-m-d H:i:s');
					$theLastGradeRecordRow = $this->queryLastGradeRecordRowBy( $playerId // #1
																				, $playerCreatedOn // #2
																				, $_time_exec_begin // #3
																				, 'upgrade_or_downgrade' // #4
																				, 'pgrm_end_time' // #5, as default
																				, false // #6, as default
																				, $excluded_vip_grade_report_id_list // #7, for OGP-25082
																			); // PATCHED

					if( empty($theLastGradeRecordRow) ){
						$_fromDatetime = $playerCreatedOn;
					}else{
						$_fromDatetime = $theLastGradeRecordRow['pgrm_start_time'];
					}


					$first = $_fromDatetime;
					$last = $_time_exec_begin;
					$isIncludeLatestRange = true;
					$periodType = array_keys($schedule)[0];
					$dateRanges = $this->utils->dateRangeByPeriod($first, $last, $periodType, $isIncludeLatestRange);
					$lastIndex = count($dateRanges)-1;
						$this->utils->debug_log('OGP-24373.OGP-24714.10293.dateRanges:', $dateRanges, $first, $last, $periodType, $isIncludeLatestRange);
					/// Patch for when player testvip001 upgrade from lv1 to lv2 on 1/14
					// execute upgrade on 1/21, system should check the accumulation from 1/10-1/16
					if( ! empty( $dateRanges[$lastIndex- 1] ) ){
							$this->utils->debug_log('OGP-24373.OGP-24714.10024.dateRanges.lastIndex-1:', $dateRanges[$lastIndex- 1]);
						$fromDatetime = $dateRanges[$lastIndex- 1]["from"];// get the
                        $_is_assign_last_changed = $this->utils->getConfig('assign_last_changed_into_from_of_period_in_accumulation_mode_reset_if_met');
						if( ! empty($dateRanges[$lastIndex- 1]["first"]) && $_is_assign_last_changed){
							$fromDatetime = $dateRanges[$lastIndex -1]["first"]; // override for last changed
						}
						$toDatetime = $dateRanges[$lastIndex- 1]["to"];// get the
					}else{
						$fromDatetime = $now->format('Y-m-d H:i:s'); // for accumulated Zero amount
						$toDatetime = $now->format('Y-m-d H:i:s');
					}

						$this->utils->debug_log('OGP-24373.OGP-24714.10308.fromDatetime:', $fromDatetime, 'toDatetime:', $toDatetime);
					$previousSched = $this->getScheduleDateRange($schedule, $subNumber, $isBatch, $setHourly, $time_exec_begin, $adjustGradeTo);
					$previousFromDatetime = $previousSched['dateFrom'];
					$previousToDatetime = $previousSched['dateTo'];
			break;
		}

		$duringDatetime['from'] = $fromDatetime;
		$duringDatetime['to'] = $toDatetime;
		$duringDatetime['previousFrom'] = $previousFromDatetime;
		$duringDatetime['previousTo'] = $previousToDatetime;
$this->utils->debug_log('OGP-19332.getDuringDatetimeFromAccumulationByPlayer.duringDatetime:', $duringDatetime);
		return $duringDatetime;
	}// EOF getDuringDatetimeFromAccumulationByPlayer

	/**
	 * To calc the bet, deposit, win and loss amounts with separate accumulation
	 *
	 * @param string $_separate_accumulation_settings The json string for separate_accumulation_settings.
	 * @param string $_formula The json string for formula, the format after json_decode(),
	 * - deposit_amount array (optional) for deposit amount rule,
	 *   - deposit_amount[0] string should be math symbol,ex: "<", ">", "=", ">=", "<=".
	 *   - deposit_amount[1] float|integer for limit amount.
	 * - bet_amount array (optional) for bet amount rule,
	 *   - bet_amount[0] string should be math symbol,ex: "<", ">", "=", ">=", "<=".
	 *   - bet_amount[1] float|integer for limit amount.
	 * - win_amount array (optional) for win amount rule,
	 *   - win_amount[0] string should be math symbol,ex: "<", ">", "=", ">=", "<=".
	 *   - win_amount[1] float|integer for limit amount.
	 * - loss_amount array (optional) for win amount rule,
	 *   - win_amount[0] string should be math symbol,ex: "<", ">", "=", ">=", "<=".
	 *   - win_amount[1] float|integer for limit amount.
	 * - operator_N string  (optional)The conjunction for between XXX_amount.ex: "and", "or".
	 * @param string $_bet_amount_settings Optional. Pls apply 'null' string,if empty.
	 * The json string for bet_amount_settings, the format after json_decode(),
	 * - $_bet_amount_settings[defaultItem] Optional. The format of the array,
	 * - $_bet_amount_settings[defaultItem][value] integer The amount will apply while the itemList[n][value] is empty.
	 * - $_bet_amount_settings[defaultItem][math_sign] string The param will apply while the itemList[n][math_sign] is empty.
	 * - $_bet_amount_settings[itemList] array Optional. The array contains the limit amount and math symbol of the game platform and type.
	 * - $_bet_amount_settings[itemList][n][type] string
	 * - $_bet_amount_settings[itemList][n][value]integer
	 * - $_bet_amount_settings[itemList][n][math_sign] string
	 * - $_bet_amount_settings[itemList][n][game_type_id/game_platform_id] integer
	 * - $_bet_amount_settings[itemList][n][precon_logic_flag] string
	 * @param array $upgradeSched The return of Group_level::getScheduleDateRange() for "No accumulation" case.
	 * @param integer $playerId The field,  player.playerId.
	 * @param string $playerCreatedOn The registion date time. ex: "2020-10-12 12:23:34".
	 * @param string $time_exec_begin The execute time. ex: "2020-10-12 12:23:34".
	 * @param array $schedule For Group_level::ACCUMULATION_MODE_DISABLE, pls reference to the param of Group_level::getScheduleDateRange().
	 * @param integer $subNumber For Group_level::ACCUMULATION_MODE_DISABLE, pls reference to the param of Group_level::getScheduleDateRange().
	 * @param boolean $isBatch For Group_level::ACCUMULATION_MODE_DISABLE, pls reference to the param of Group_level::getScheduleDateRange().
	 * @param boolean $setHourly For Group_level::ACCUMULATION_MODE_DISABLE, pls reference to the param of Group_level::getScheduleDateRange().
	 *
	 * @return array $calcResult The result array after calc,
	 * - $calcResult['total_bet'] null|integer|float If null mean disable else the amount of result.
	 *
	 * - $calcResult['total_win'] null|integer|float If null mean disable else the amount of result.
	 * - $calcResult['total_loss'] null|integer|float If null mean disable else the amount of result.
	 * - $calcResult['deposit'] null|integer|float If null mean disable else the amount of result.
	 * - $calcResult['details'] array The detail for referenced to debug/"Grade Report".
	 * - $calcResult['details']['total_bet'] array (optional)
	 * - $calcResult['details']['total_bet']['from'] string The during time of query, begin datetime.
	 * - $calcResult['details']['total_bet']['to'] string The during time of query, end datetime.
	 * - $calcResult['details']['separated_bet'] array The return of the function,"getSeparatedGameLogDataFromTotalPlayerGameMinute()".
	 * - $calcResult['details']['separated_bet']['from'] string The during time of query, begin datetime.
	 * - $calcResult['details']['separated_bet']['to'] string The during time of query, end datetime.
	 * - $calcResult['details']['total_win'] array (optional)
	 * - $calcResult['details']['total_win']['from'] string The during time of query, begin datetime.
	 * - $calcResult['details']['total_win']['to'] string The during time of query, end datetime.
	 * - $calcResult['details']['total_loss'] array (optional)
	 * - $calcResult['details']['total_loss']['from'] string The during time of query, begin datetime.
	 * - $calcResult['details']['total_loss']['to'] string The during time of query, end datetime.
	 * - $calcResult['details']['deposit'] array (optional)
	 * - $calcResult['details']['deposit']['from'] string The during time of query, begin datetime.
	 * - $calcResult['details']['deposit']['to'] string The during time of query, end datetime.
	 */
	public function calcSeparateAccumulationByExtraSettingList(	$_separate_accumulation_settings // # 1
																, $_formula // # 2
																, $_bet_amount_settings // # 2.1
																, $upgradeSched // # 3
																, $playerId // # 4
																, $playerCreatedOn // # 5
																, $time_exec_begin = null // # 6
																, $schedule = []// # 7
																, $subNumber = 1 // # 8
																, $isBatch = true // # 9
																, $setHourly = false // # 10
																, $adjustGradeTo = 'up' // #11

	){

		$this->load->model([ 'player_accumulated_amounts_log' ]);
		$this->load->library(['group_level_lib']);
		$calcResult = [];
		$calcResult['details'] = [];

		$accumulationModeDefault = Group_level::ACCUMULATION_MODE_DISABLE;
		if(is_string($_separate_accumulation_settings)){
			$separate_accumulation_settings = json_decode($_separate_accumulation_settings, true);
		}else{
			$separate_accumulation_settings = $_separate_accumulation_settings;
		}
		$doBetAmountSettings = false; // default
$this->utils->debug_log('OGP-19332.6563._bet_amount_settings:', $_bet_amount_settings);
		$bet_amount_settings = json_decode($_bet_amount_settings, true);

		if(is_string($_formula)){
			$formula = json_decode($_formula, true);
		}else{
			$formula = $_formula;
		}

		// If bet_amount_settings Not Empty,it will be used first
		if( ! empty($bet_amount_settings['itemList']) ){
			$doBetAmountSettings = true;
		}

		if( ! empty($formula['bet_amount']) ){

			// defaults
			$calcResult['total_bet'] = null;
			$calcResult['separated_bet'] = null;

			// separate accumulation for bet_amount
			if( ! empty($separate_accumulation_settings['bet_amount'])){
				// $fromDatetime, $toDatetime
				$accumulationMode = (int)$separate_accumulation_settings['bet_amount']['accumulation'];
			}else{
				$accumulationMode = $accumulationModeDefault;
			}
			// $playerCreatedOn = $player->createdOn;
			// $time_exec_begin = null;
			$duringDatetime = $this->getDuringDatetimeFromAccumulationByPlayer( $accumulationMode // #1
																				, $upgradeSched // #2
																				, $playerId // #3
																				, $playerCreatedOn // #4
																				, $time_exec_begin // #5
																				, $schedule // # 6
																				, $subNumber // # 7
																				, $isBatch // # 8
																				, $setHourly // # 9
																				, $adjustGradeTo // #10
																			);
			$this->utils->debug_log('OGP-20868.7072.duringDatetime',$duringDatetime, 'time_exec_begin:', $time_exec_begin);
			if($doBetAmountSettings){ // separate bet setting
				$fromDatetime = $duringDatetime['from'];
				$toDatetime = $duringDatetime['to'];
				$separated_bet = $this->getSeparatedGameLogDataFromTotalPlayerGameMinute( $playerId // #1
																				, $fromDatetime // #2
																				, $toDatetime // #3
																				, $bet_amount_settings // #4
																			);
				$calcResult['separated_bet'] = $separated_bet;
				$calcResult['details']['separated_bet'] = $separated_bet; // @todo OGP-19332 reporting
				$calcResult['details']['separated_bet']['from'] = $fromDatetime;
				$calcResult['details']['separated_bet']['to'] = $toDatetime;
				// enforcedDetails
				$calcResult['details']['separated_bet']['enforcedDetails'] = null;
			}else{ // common(total) bet setting


				switch( (int)$accumulationMode ){
					default:
					case Group_level::ACCUMULATION_MODE_DISABLE:
					case Group_level::ACCUMULATION_MODE_FROM_REGISTRATION:
					case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE:
						$fromDatetime = $duringDatetime['from'];
						$toDatetime = $duringDatetime['to'];
						// $previousFromDatetime = $duringDatetime['previousFrom'];
						// $previousToDatetime = $duringDatetime['previousTo'];
						// get bet amount
						$where_game_platform_id = null;
						$where_game_type_id = null;
						$_gameLogData = $this->getGameLogDataFromTotalPlayerGameMinute( $playerId // #1
																						, $fromDatetime // #2
																						, $toDatetime // #3
																						, $where_game_platform_id // #4
																						, $where_game_type_id // #5
																					);
					break;

					case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET:
						// OGP-24373 取該模式的流水金額

						// OGP-25082
						$excluded_vip_grade_report_id_list = [];
						if( ! empty($this->PLAH['multiple_upgrade_buff']['vip_grade_report_id_list']) ){
							$excluded_vip_grade_report_id_list = $this->PLAH['multiple_upgrade_buff']['vip_grade_report_id_list'];
						}
						/// Get the last time the player entered this level
						$queryBeginDatetime = ''; // for createOn of the player
						// if( isset($this->PLAH['multiple_upgrade_buff']['pgrm_start_time']) ){
						// 	$queryBeginDatetime = $this->PLAH['multiple_upgrade_buff']['pgrm_start_time'];
						// }
						$queryEndDatetime = $time_exec_begin;
						$lastGradeDatetime = $this->group_level_lib->queryLastGradeTimeByPlayerId($playerId, $queryBeginDatetime, $queryEndDatetime, $excluded_vip_grade_report_id_list);

						$fromDatetime = $duringDatetime['from'];  // "Last Changed Level "OR "Previous Period" by Group_level::getDuringDatetimeFromAccumulationByPlayer()
						$toDatetime = $duringDatetime['to'];
						$_gameLogData = null;
						$_remarkInMacro = null;
						$settingInfoHarshInUpGraded = [];
						$settingInfoHarshInUpGraded['debugInsertBy'] = 10694;
						$settingInfoHarshInUpGraded['total_deposit'] = true;
						$settingInfoHarshInUpGraded['total_bet'] = true;
						$_isConditionMet = $this->_macroGetPlayerTotalBetWinLossByScheduleWithFormulaAndReturnIsMet( $playerId // #1
																													, $fromDatetime // #2
																													, $toDatetime // #3
																													, $formula // #4
																													, 'up' // #5
																													, $_remarkInMacro // #6
																													, $_gameLogData // #7
																													, $settingInfoHarshInUpGraded // #8
																												);

								$theGenerateCallTrace = $this->utils->generateCallTrace();
							$this->utils->debug_log('OGP-24373.10184._isConditionMet:', $_isConditionMet, 'theGenerateCallTrace:', $theGenerateCallTrace);
							$this->utils->debug_log('OGP-24373.10184.after._macroGetPlayerTotalBetWinLossByScheduleWithFormulaAndReturnIsMet'
								, '_isConditionMet:', $_isConditionMet
								, 'fromDatetime:', $fromDatetime
								, 'toDatetime:', $toDatetime
								, 'formula:', $formula
								, '_remarkInMacro:', $_remarkInMacro
								, '_gameLogData:', $_gameLogData
							);

						$vipSettingId = $this->getPlayerLevelId($playerId); // vipsettingcashbackrule.vipsettingcashbackruleId
						// $_isConditionMet =  true; /// TEST
						/// for log "is_bet_met" result and "bet" amount into player_accumulated_amounts_log.
						$accumulated_type = Player_accumulated_amounts_log::ACCUMULATED_TYPE_BET;
						$query_token = sprintf(Player_accumulated_amounts_log::QUERY_TOKEN_IN_LEVEL, $vipSettingId);
						$begin_datetime = $fromDatetime;
						$end_datetime = $toDatetime;
						$amount = $_gameLogData['total_bet'];
						$is_met = ($_isConditionMet)? Player_accumulated_amounts_log::IS_MET_YES : Player_accumulated_amounts_log::IS_MET_NO ;
						$log_accumulated_amount_result = $this->player_accumulated_amounts_log->log_accumulated_amount(	$playerId
																														, $amount
																														, $accumulated_type
																														, $query_token
																														, $begin_datetime
																														, $end_datetime
																														, $is_met
																														, $time_exec_begin
																													);
						if( ! $_isConditionMet ){
							/// collect current condition info.
							$curr_condition_details = [];
							$curr_condition_details['fromDatetime'] = $fromDatetime;
							$curr_condition_details['toDatetime'] = $toDatetime;
							$curr_condition_details['gameLogData'] = $_gameLogData;
							$curr_condition_details['remark'] = $_remarkInMacro;
							$curr_condition_details['formula'] = $formula;


							/// OGP-24373 查詢 player_accumulated_amounts_log
							// 找出符合條件的紀錄：查詢 該玩家、從進來等級開始到現在的 query_token=is_bet_met_level_155、amount=AMOUNT_IS_MET 最新的一筆。
							$beginDatetine = $lastGradeDatetime;
							// $endDatetime = $time_exec_begin; // disable for patch the issue. Not found the meted condition log by simulate current time.
							// $now = new DateTime();
							$accumulated_type = Player_accumulated_amounts_log::ACCUMULATED_TYPE_BET;
							$endDatetime = $this->utils->formatDateTimeForMysql(new DateTime($time_exec_begin));

							$meted_log_rows = [];
							$meted_log_row = $this->group_level_lib->getMetedRowInAccumulatedAmountsLogWithParams($vipSettingId, $playerId, $accumulated_type, $beginDatetine, $endDatetime);
							if( ! empty($meted_log_row) ){
								$meted_log_rows[0] = $meted_log_row;
							}
							/// [Patched] 比較 "24373.10426.meted_log_rows.params"
							// 流水曾滿足、再次檢查存款滿足，找到流水曾經滿足，就可順利升級
							// 存款曾滿足、再次檢查流水滿足，但是找不到存款曾經滿足，沒有升級 <<< issue
							$this->utils->debug_log('OGP-24373.10527.meted_log_rows.params:', $vipSettingId, $playerId, $accumulated_type, $beginDatetine, $endDatetime);
							$this->utils->debug_log('OGP-24373.10527.meted_log_rows:', $meted_log_rows);
							$is_meted = null;
							if( ! empty($meted_log_rows) ){

								$_fromDatetime = $meted_log_rows[0]['begin_datetime'];
								$_toDatetime = $meted_log_rows[0]['end_datetime'];
								$_gameLogData = null;
								$_remarkInMacro = null;
								$_adjustGradeTo = 'up'; // The param,$adjustGradeTo mat be specified as "down", but the mode,ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET Not yet supported.
								$settingInfoHarshInUpGraded = [];
								$settingInfoHarshInUpGraded['debugInsertBy'] = 10764;
								$settingInfoHarshInUpGraded['total_deposit'] = true;
								$settingInfoHarshInUpGraded['total_bet'] = true;
								$_isConditionMet = $this->_macroGetPlayerTotalBetWinLossByScheduleWithFormulaAndReturnIsMet( $playerId // #1
																														, $_fromDatetime // #2
																														, $_toDatetime // #3
																														, $formula // #4
																														, $_adjustGradeTo // #5
																														, $_remarkInMacro // #6
																														, $_gameLogData // #7
																														, $settingInfoHarshInUpGraded // #8
																													);
								$is_meted = $_isConditionMet ? true: false;
								$this->utils->debug_log('OGP-24373.10537._isConditionMet:', $_isConditionMet
										, '_fromDatetime:', $_fromDatetime
										, '_toDatetime:', $_toDatetime
										, '_gameLogData:', $_gameLogData
										, '_remarkInMacro:', $_remarkInMacro
								);
							}
							if( !empty($is_meted) ){
								/// override the followings,
								$_isMet = true; /// need ref. to condition
								$_reason = group_level_lib::ENFORCED_CONDITION_RESULT_REASON_LAST_CHANGED_GEADE_RESET_IF_MET_HAD_MET_LOG;
								$_reasonDetails = [];
								$_reasonDetails['meted_log'] = $meted_log_rows[0];
								$_reasonDetails['insert_by'] = 10545;
								$_reasonDetails['remark'] = $_remarkInMacro;
								if( ! empty($curr_condition_details) ){
									$_reasonDetails['curr_condition_details'] = $curr_condition_details;
								}
								$_enforcedDetails = $this->group_level_lib->getEnforcedConditionResultDetail($_isMet, $_reason, $_reasonDetails);
								$_gameLogData['total_bet'] = $meted_log_rows[0]['amount']; // as 2rd check amount
								$fromDatetime = $meted_log_rows[0]['begin_datetime'];
								$toDatetime = $meted_log_rows[0]['end_datetime'];
							}

						} // EOF if( ! $_isConditionMet ){...
					break;
				}

				$calcResult['total_bet'] = $_gameLogData['total_bet'];
				$calcResult['details']['total_bet'] = [];
				$calcResult['details']['total_bet']['count'] = $calcResult['total_bet'];
				$calcResult['details']['total_bet']['from'] = $fromDatetime;
				$calcResult['details']['total_bet']['to'] = $toDatetime;
				if( ! empty($log_accumulated_amount_result) ){
					$calcResult['details']['total_bet']['log_accumulated_amount_result'] = $log_accumulated_amount_result;
				}
				// enforcedDetails
				// $calcResult['details']['total_bet']['enforcedDetails'] = null;
				if( ! empty($_enforcedDetails) ){
					$calcResult['details']['total_bet']['enforcedDetails'] = $_enforcedDetails;
					unset($_enforcedDetails); // clear
					unset($_reasonDetails); // clear
				}
				$this->utils->debug_log('7104 -------- (separate accumulation) get bet amount from to ------', $playerId, $fromDatetime, $toDatetime, '$total_bet:', $calcResult['total_bet']);

			} // EOF if($doBetAmountSettings){...


		} // EOF if( ! empty($formula['bet_amount']) ){...

		if( ! empty($formula['win_amount']) ){
			// separate accumulation for win_amount
			if( ! empty($separate_accumulation_settings['win_amount']) ){
				// $fromDatetime, $toDatetime
				$accumulationMode = (int)$separate_accumulation_settings['win_amount']['accumulation'];
			}else{
				$accumulationMode = $accumulationModeDefault;
			}
			// $playerCreatedOn = $player->createdOn;
			// $time_exec_begin = null;
			$duringDatetime = $this->getDuringDatetimeFromAccumulationByPlayer( $accumulationMode // #1
																				, $upgradeSched // #2
																				, $playerId // #3
																				, $playerCreatedOn // #4
																				, $time_exec_begin // #5
																				, $schedule // # 6
																				, $subNumber // # 7
																				, $isBatch // # 8
																				, $setHourly // # 9
																				, $adjustGradeTo // #10
																			);

			$fromDatetime = $duringDatetime['from'];
			$toDatetime = $duringDatetime['to'];
			// $previousFromDatetime = $duringDatetime['previousFrom'];
			// $previousToDatetime = $duringDatetime['previousTo'];

			// get win amount
			$_gameLogData = $this->getGameLogDataFromTotalPlayerGameMinute($playerId, $fromDatetime, $toDatetime);
			$calcResult['total_win'] = $_gameLogData['total_win'];
			$calcResult['details']['total_win'] = [];
			$calcResult['details']['total_win']['count'] = $calcResult['total_win'];
			$calcResult['details']['total_win']['from'] = $fromDatetime;
			$calcResult['details']['total_win']['to'] = $toDatetime;
			$this->utils->debug_log('-------- (separate accumulation) get win amount from to ------', $playerId, $fromDatetime, $toDatetime, '$total_win:', $calcResult['total_win']);
		}// EOF if( ! empty($formula['win_amount']) ){...
		if( ! empty($formula['loss_amount']) ){
			// separate accumulation for loss_amount
			if( ! empty($separate_accumulation_settings['loss_amount']) ){
				// $fromDatetime, $toDatetime
				$accumulationMode = (int)$separate_accumulation_settings['loss_amount']['accumulation'];
			}else{
				$accumulationMode = $accumulationModeDefault;
			}
			// $playerCreatedOn = $player->createdOn;
			// $time_exec_begin = null;
			$duringDatetime = $this->getDuringDatetimeFromAccumulationByPlayer( $accumulationMode // #1
																				, $upgradeSched // #2
																				, $playerId // #3
																				, $playerCreatedOn // #4
																				, $time_exec_begin // #5
																				, $schedule // # 6
																				, $subNumber // # 7
																				, $isBatch // # 8
																				, $setHourly // # 9
																				, $adjustGradeTo // #10
																			);
			$fromDatetime = $duringDatetime['from'];
			$toDatetime = $duringDatetime['to'];
			// $previousFromDatetime = $duringDatetime['previousFrom'];
			// $previousToDatetime = $duringDatetime['previousTo'];

			// get loss amount
			$_gameLogData = $this->getGameLogDataFromTotalPlayerGameMinute($playerId, $fromDatetime, $toDatetime);
			$calcResult['total_loss'] = $_gameLogData['total_loss'];
			$calcResult['details']['total_loss'] = [];
			$calcResult['details']['total_loss']['count'] = $calcResult['total_loss'];
			$calcResult['details']['total_loss']['from'] = $fromDatetime;
			$calcResult['details']['total_loss']['to'] = $toDatetime;
			$this->utils->debug_log('-------- (separate accumulation) get loss amount from to ------', $playerId, $fromDatetime, $toDatetime, '$total_loss:', $calcResult['total_loss']);
		}// EOF if( ! empty($formula['loss_amount']) ){...
		if( ! empty($formula['deposit_amount']) ){
		// separate accumulation for deposit_amount
			$deposit = 0;
			if( ! empty($separate_accumulation_settings['deposit_amount']) ){
				// $fromDatetime, $toDatetime
				$accumulationMode = (int)$separate_accumulation_settings['deposit_amount']['accumulation'];
			}else{
				$accumulationMode = $accumulationModeDefault;
			}

			switch( (int)$accumulationMode ){
				default:
				case Group_level::ACCUMULATION_MODE_DISABLE:
				case Group_level::ACCUMULATION_MODE_FROM_REGISTRATION:
				case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE:
					// $playerCreatedOn = $player->createdOn;
								// $time_exec_begin = null;
					$duringDatetime = $this->getDuringDatetimeFromAccumulationByPlayer( $accumulationMode // #1
																							, $upgradeSched // #2
																							, $playerId // #3
																							, $playerCreatedOn // #4
																							, $time_exec_begin // #5
																							, $schedule // # 6
																							, $subNumber // # 7
																							, $isBatch // # 8
																							, $setHourly // # 9
																							, $adjustGradeTo // #10
																						);
					$fromDatetime = $duringDatetime['from'];
					$toDatetime = $duringDatetime['to'];
					// $previousFromDatetime = $duringDatetime['previousFrom'];
					// $previousToDatetime = $duringDatetime['previousTo'];

                    $add_manual = false; // as default
                    list($deposit) = $this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers($playerId,$fromDatetime,$toDatetime, $add_manual, $this->enable_multi_currencies_totals);

					break;
				case Group_level::ACCUMULATION_MODE_LAST_CHANGED_GEADE_RESET_IF_MET:
					// handle $fromDatetime, $toDatetime and $deposit
					// handle $_enforcedDetails

					$duringDatetime = $this->getDuringDatetimeFromAccumulationByPlayer( $accumulationMode // #1
																						, $upgradeSched // #2
																						, $playerId // #3
																						, $playerCreatedOn // #4
																						, $time_exec_begin // #5
																						, $schedule // # 6
																						, $subNumber // # 7
																						, $isBatch // # 8
																						, $setHourly // # 9
																						, $adjustGradeTo // #10
																					);
					/// Get the last time the player entered this level
					$queryBeginDatetime = '';
					$queryEndDatetime = $time_exec_begin;
					$lastGradeDatetime = $this->group_level_lib->queryLastGradeTimeByPlayerId($playerId, $queryBeginDatetime, $queryEndDatetime);

					/// Get the Formula Info for accumulate mode and datetime range
					$vipSettingId = $this->getPlayerLevelId($playerId); // vipsettingcashbackrule.vipsettingcashbackruleId
					$this->utils->debug_log('OGP-24373.10692.multiple_upgrade_buff:', empty($this->PLAH['multiple_upgrade_buff'])? null: $this->PLAH['multiple_upgrade_buff'] );
					$this->utils->debug_log('OGP-24373.10693.duringDatetime:', $duringDatetime);

					$fromDatetime = $duringDatetime['from']; // "Last Changed Level "OR "Previous Period" by Group_level::getDuringDatetimeFromAccumulationByPlayer()
					$toDatetime = $duringDatetime['to'];
					// $previousFromDatetime = $duringDatetime['previousFrom'];
					// $previousToDatetime = $duringDatetime['previousTo'];
					$_deposit = null;
					$_remarkInMacro = null;
					$settingInfoHarshInUpGraded = [];
					$settingInfoHarshInUpGraded['debugInsertBy'] = 10961;
					$settingInfoHarshInUpGraded['total_deposit'] = true;
					$settingInfoHarshInUpGraded['total_bet'] = true;
					$_isConditionMet = $this->_macroGetTotalDepositWithdrawalBonusCashbackByPlayersWithFormulaAndReturnIsMet( $playerId // #1
																															, $fromDatetime // #2
																															, $toDatetime // #3
																															, $formula // #4
																															, $_remarkInMacro // #5
																															, $_deposit // #6
																															, $settingInfoHarshInUpGraded // #7
																														);
					$deposit = $_deposit; // as 1st check amount.
					/// _macroGetTotalDepositWithdrawalBonusCashbackByPlayersWithFormulaAndReturnIsMet ---- BEGIN
					// list($_deposit) = $this->transactions->getTotalDepositWithdrawalBonusCashbackByPlayers($playerId,$fromDatetime,$toDatetime);
					// $deposit = $_deposit; // as 1st check amount.
					// $_formula = [];
					// $_formula['deposit_amount'] = $formula['deposit_amount'];
					// $_gameLogData4bet = 0;
					// $_total_loss = 0;
					// $_total_win = 0;
					// $_bet_amount_settings = [];
					// $isDisableSetupRemarkToGradeRecord = true;
					// $_remark = null; // $_remark['rlt']
					// 	$this->utils->debug_log('OGP-24373.10389.deposit_amount._formula', $_formula, 'fromDatetime:', $fromDatetime, 'toDatetime:', $toDatetime);
					// $_isConditionMet = $this->generateUpDownFormulaWithBetAmountSetting( $_formula // #1
					// 														, $_gameLogData4bet // #2
					// 														, $_deposit // #3
					// 														, $_total_loss // #4
					// 														, $_total_win // #5
					// 														, $_bet_amount_settings  // #6
					// 														, $isDisableSetupRemarkToGradeRecord // #7
					// 														, $_remark // #8
					// 													);
					/// _macroGetTotalDepositWithdrawalBonusCashbackByPlayersWithFormulaAndReturnIsMet ---- END
						$this->utils->debug_log('OGP-24373.10397._isConditionMet:', $_isConditionMet);
						$this->utils->debug_log('OGP-24373.10397.after._macroGetTotalDepositWithdrawalBonusCashbackByPlayersWithFormulaAndReturnIsMet'
													, '_isConditionMet:', $_isConditionMet
													, 'fromDatetime:', $fromDatetime
													, 'toDatetime:', $toDatetime
													, 'formula:', $formula
													, '_remarkInMacro:', $_remarkInMacro
													, '_deposit:', $_deposit
												);

					// $_isConditionMet =  true; /// TEST
					/// for log "is_bet_met" result and "bet" amount into player_accumulated_amounts_log.
					$accumulated_type = Player_accumulated_amounts_log::ACCUMULATED_TYPE_DEPOSIT;
					$query_token = sprintf(Player_accumulated_amounts_log::QUERY_TOKEN_IN_LEVEL, $vipSettingId);
					$begin_datetime = $fromDatetime;
					$end_datetime = $toDatetime;
					$amount = $_deposit;
					$is_met = ($_isConditionMet)? Player_accumulated_amounts_log::IS_MET_YES : Player_accumulated_amounts_log::IS_MET_NO ;
					$log_accumulated_amount_result = $this->player_accumulated_amounts_log->log_accumulated_amount(	$playerId
																													, $amount
																													, $accumulated_type
																													, $query_token
																													, $begin_datetime
																													, $end_datetime
																													, $is_met
																													, $time_exec_begin
																												);

					if( ! $_isConditionMet ){
						/// collect current condition info.
						$curr_condition_details = [];
						$curr_condition_details['fromDatetime'] = $fromDatetime;
						$curr_condition_details['toDatetime'] = $toDatetime;
						$curr_condition_details['deposit'] = $_deposit;
						$curr_condition_details['remark'] = $_remarkInMacro;
						$curr_condition_details['formula'] = $formula;

						/// OGP-24373 查詢 player_accumulated_amounts_log
						// 找出符合條件的紀錄：查詢 該玩家、從進來等級開始到現在的 query_token=is_bet_met_level_155、amount=AMOUNT_IS_MET 最新的一筆。
						$beginDatetine = $lastGradeDatetime;
						$endDatetime = $time_exec_begin; // disable for patch the issue. Not found the meted condition log by simulate current time.
						// $now = new DateTime();
						$accumulated_type = Player_accumulated_amounts_log::ACCUMULATED_TYPE_DEPOSIT;
						$endDatetime = $this->utils->formatDateTimeForMysql(new DateTime($time_exec_begin));
						$query_token = sprintf(Player_accumulated_amounts_log::QUERY_TOKEN_IN_LEVEL, $vipSettingId);
						$is_met = Player_accumulated_amounts_log::IS_MET_YES;
						$meted_log_rows = $this->player_accumulated_amounts_log->getDetailListByPlayerIdAndQueryToken($playerId, $query_token, $accumulated_type, $beginDatetine, $endDatetime, $is_met);
						$this->utils->debug_log('OGP-24373.10426.meted_log_rows.params:', $playerId, $query_token, $accumulated_type, $beginDatetine, $endDatetime, $is_met);
						$this->utils->debug_log('OGP-24373.10426.meted_log_rows:', $meted_log_rows); // [Patched] issue, test03024(6152) // 存款曾滿足、再次檢查流水滿足，但是找不到存款曾經滿足，沒有升級
						$is_meted = null;
						if( ! empty($meted_log_rows) ){ // confirm the deposit amount in the formula

							$_fromDatetime = $meted_log_rows[0]['begin_datetime'];
							$_toDatetime = $meted_log_rows[0]['end_datetime'];
							$_deposit = null; // meted_log_rows[0]['amount']
							$_remarkInMacro = null;
							$settingInfoHarshInUpGraded = [];
							$settingInfoHarshInUpGraded['debugInsertBy'] = 11041;
							$settingInfoHarshInUpGraded['total_deposit'] = true;
							$settingInfoHarshInUpGraded['total_bet'] = true;
							$_isConditionMet = $this->_macroGetTotalDepositWithdrawalBonusCashbackByPlayersWithFormulaAndReturnIsMet( $playerId // #1
																																	, $_fromDatetime // #2
																																	, $_toDatetime // #3
																																	, $formula // #4
																																	, $_remarkInMacro // #5
																																	, $_deposit // #6
																																	, $settingInfoHarshInUpGraded // #7
																																);
							// 這是有 曾經滿足 的狀況的 存款
							$this->utils->debug_log('OGP-24373.10787._isConditionMet:', $_isConditionMet
										, '_fromDatetime:', $_fromDatetime
										, '_toDatetime:', $_toDatetime
										, '_deposit:', $_deposit
										, '_remarkInMacro:', $_remarkInMacro
								);
							$is_meted = $_isConditionMet ? true: false;
						}
						if( $is_meted ){
							/// override the followings,
							$_isMet = true; // need ref. to condition
							$_reason = group_level_lib::ENFORCED_CONDITION_RESULT_REASON_LAST_CHANGED_GEADE_RESET_IF_MET_HAD_MET_LOG;
							$_reasonDetails = [];
							$_reasonDetails['meted_log'] = $meted_log_rows[0];
							$_reasonDetails['insert_by'] = 10436;
							$_reasonDetails['remark'] = $_remarkInMacro; // the previous remark
							if( ! empty($curr_condition_details) ){
								$_reasonDetails['curr_condition_details'] = $curr_condition_details;
							}
							$_enforcedDetails = $this->group_level_lib->getEnforcedConditionResultDetail($_isMet, $_reason, $_reasonDetails);
							// $_gameLogData['log_id'] = $meted_log_rows[0]['id']; // for trace
							$deposit = $meted_log_rows[0]['amount']; // as 2rd check amount
							$fromDatetime = $meted_log_rows[0]['begin_datetime'];
							$toDatetime = $meted_log_rows[0]['end_datetime'];
						}
					} // EOF if( ! $_isConditionMet ){...

					break;
			}; // EOF switch( (int)$accumulationMode ){...

			$this->utils->debug_log('-------- (separate accumulation) get deposit amount from to ------', $playerId, $fromDatetime, $toDatetime, '$deposit:', $deposit, $separate_accumulation_settings, $upgradeSched);
			$calcResult['deposit'] = $deposit;
			$calcResult['details']['deposit'] = [];
			$calcResult['details']['deposit']['count'] = $calcResult['deposit'];
			$calcResult['details']['deposit']['from'] = $fromDatetime;
			$calcResult['details']['deposit']['to'] = $toDatetime;
			if( ! empty($log_accumulated_amount_result) ){
				$calcResult['details']['deposit']['log_accumulated_amount_result'] = $log_accumulated_amount_result;
			}
			// enforcedDetails
			// $calcResult['details']['deposit']['enforcedDetails'] = null;
			if( ! empty($_enforcedDetails) ){
				$calcResult['details']['deposit']['enforcedDetails'] = $_enforcedDetails;
				unset($_enforcedDetails); // clear
				unset($_reasonDetails); // clear
			}

		}// EOF if( ! empty($formula['deposit_amount']) ){...
		return $calcResult;

	}// EOF calcSeparateAccumulationByExtraSettingList

	/**
	 * Get playerGrooupId from playerlevel table
	 *
	 * @param integer  playerId
	 * @return integer
	 */
	public function getPlayerGroupIdFromPlayerlevelTbl($playerId){
		$this->db->select('playerGroupId');
		$this->db->from($this->playerlevelTable);
		$this->db->where('playerId', $playerId);
		return $this->runOneRowOneField('playerGroupId');
	}

	public function clear_duplicate_cashback_game_info($tableName, $fieldName){
		$cntOfDel=0;
		$sql=<<<EOD
select vipsetting_cashbackrule_id, $fieldName from $tableName
group by vipsetting_cashbackrule_id, $fieldName
having count(id)>1
EOD;

		$rows=$this->runRawSelectSQLArray($sql);
		if(!empty($rows)){
			foreach ($rows as $row) {
				$vipsetting_cashbackrule_id=$row['vipsetting_cashbackrule_id'];
				$gameInfoId=$row[$fieldName];
				//keep last one
				$this->db->select('id')->from($tableName)
					->where('vipsetting_cashbackrule_id', $vipsetting_cashbackrule_id)
					->where($fieldName, $gameInfoId);
				$gpRows=$this->runMultipleRowArray();
				$this->utils->debug_log('count of gpRows', count($gpRows));
				$idList=[];
				foreach ($gpRows as $gpRow) {
					$idList[]=$gpRow['id'];
				}
				unset($gpRows);
				//delete last one
				unset($idList[count($idList)-1]);
				$this->utils->debug_log('count of id list', count($idList));
				$this->db->where_in('id', $idList);
				$this->runRealDelete($tableName);
				$cntOfDelCurrent=$this->db->affected_rows();
				$cntOfDel+=$cntOfDelCurrent;
				$this->utils->debug_log('count of delete '.$tableName, $cntOfDelCurrent, 'total: '.$cntOfDel, 'vipsetting_cashbackrule_id', $vipsetting_cashbackrule_id, $fieldName, $gameInfoId);
			}
		}
		return $cntOfDel;
	}

/**
create unique index idx_game_platform_id_level_id on group_level_cashback_game_platform(vipsetting_cashbackrule_id,game_platform_id)

create unique index idx_game_type_id_level_id on group_level_cashback_game_type(vipsetting_cashbackrule_id,game_type_id)

create unique index idx_game_description_id_level_id on group_level_cashback_game_description(vipsetting_cashbackrule_id,game_description_id)
 * @return int count of delete
 */
	public function clear_duplicate_cashback(){
		$cntOfDel=0;
		$cntOfDelGamePlatform=$this->clear_duplicate_cashback_game_info('group_level_cashback_game_platform', 'game_platform_id');
		$this->utils->debug_log('count of delete game platform', $cntOfDelGamePlatform);
		$cntOfDel+=$cntOfDelGamePlatform;

		$cntOfDelGameType=$this->clear_duplicate_cashback_game_info('group_level_cashback_game_type', 'game_type_id');
		$this->utils->debug_log('count of delete game type', $cntOfDelGameType);
		$cntOfDel+=$cntOfDelGameType;

		$cntOfDelGameDecription=$this->clear_duplicate_cashback_game_info('group_level_cashback_game_description', 'game_description_id');
		$this->utils->debug_log('count of delete game description', $cntOfDelGameDecription);
		$cntOfDel+=$cntOfDelGameDecription;

		return $cntOfDel;
	}

    public function isPayTime($date, $forceToPay = false){
        $now = $this->utils->getNowForMysql();
        $cashBackSettings = $this->getCashbackSettings();
        $payDateTime = $date . ' ' . $cashBackSettings->payTimeHour . ':00';
        $isPayTime = substr($now, 0, 13) == substr($payDateTime, 0, 13);
        if($forceToPay){
            $isPayTime = $forceToPay;
        }
        $this->utils->debug_log('isPayTime :', $isPayTime, 'now', $now, 'payDateTime', $payDateTime, 'forceToPay', $forceToPay);
        return $isPayTime;
    }

    public function getPlayerCountByLevelId($levelId) {
        $this->db
            ->from('player')
            ->where('levelId', $levelId)
            ->where('deleted_at is NULL')
            ->select('count(*) as count');
        return $this->runOneRowOneField('count');
    }

    /**
     * Get the players count Or player_id of the specified level(s) via "playerlevel" data-table
     *
     * @param integer|array $levelId The field,`playerlevel`.`playerGroupId` (list) F.K. by the `vipsettingcashbackrule`.`vipsettingcashbackruleId`
     * @param boolean $returnPlayers If its needed, please assign it to true. And Please note that the number of players may be over 100k .
     * @return array $return
     * - $return['levelId']
     * - $return['count']
     * - $return['playerId']
     */
	public function getPlayerCountByLevelIdWithPlayerlevel($levelId, $returnPlayers = false) {

        $select4players = '';
        if($returnPlayers){
            $select4players .= '`player`.`playerId` AS `playerId` ';
        }else{
            $select4players .= ' count(`player`.`playerId`) AS count ';
        }

        $where4levelId = '';
        if(is_numeric($levelId)){
            $where4levelId .= sprintf('`playerlevel`.`playerGroupId` = %s', $levelId);
        }elseif(is_array($levelId)){
            $_imploded = implode(', ', $levelId);
            $where4levelId .=  sprintf('`playerlevel`.`playerGroupId` IN (%s)', $_imploded);
        }
		$sql = <<<EOD
SELECT $select4players
FROM `player`
WHERE `player`.`deleted_at` is NULL
AND `player`.`playerId` IN(
	SELECT `playerlevel`.`playerId`
	FROM `playerlevel`
	WHERE $where4levelId
);
EOD;
		$qry = $this->db->query($sql, []);
		$rows = $this->getMultipleRowArray($qry);

		$return = [];
		$return['levelId'] = $levelId;
        $return['count'] = 0;
        if($returnPlayers){
            $return['playerId'] = array_column($rows, 'playerId');
            $return['playerId'] = array_filter($return['playerId']);
            $return['playerId'] = array_unique($return['playerId']);
            $return['count'] = count($return['playerId']);
        }else{
            if( ! empty($rows[0]['count']) ){
                $return['count'] = $rows[0]['count'];
            }
        }
		return $return;
    } // EOF getPlayerCountByLevelIdWithPlayerlevel
    /**
     * Get the players count Or player_id of the specified level(s) via "player" data-table
     *
     * @param integer|array $levelId The field,`playerlevel`.`playerGroupId` (list) F.K. by the `vipsettingcashbackrule`.`vipsettingcashbackruleId`
     * @param boolean $returnPlayers If its needed, please assign it to true. And Please note that the number of players may be over 100k .
     * @return array $return
     * - $return['levelId']
     * - $return['count']
     * - $return['playerId']
     */
    public function getPlayerCountByLevelIdWithPlayer($levelId, $returnPlayers = false){
        $select4players = '';
        if($returnPlayers){
            $select4players .= ' `player`.`playerId` AS `playerId` ';
        }else{
            $select4players .= ' count(`player`.`playerId`) AS count ';
        }

        $where4levelId = '';
        if(is_numeric($levelId)){
            $where4levelId .= sprintf('`player`.`levelId` = %s', $levelId);
        }elseif(is_array($levelId)){
            $_imploded = implode(', ', $levelId);
            $where4levelId .=  sprintf('`player`.`levelId` IN (%s)', $_imploded);
        }
		$sql = <<<EOD
SELECT $select4players
FROM `player`
WHERE `player`.`deleted_at` is NULL
AND  $where4levelId
;
EOD;
		$qry = $this->db->query($sql, []);
		$rows = $this->getMultipleRowArray($qry);

		$return = [];
		$return['levelId'] = $levelId;
        $return['count'] = 0;

        if($returnPlayers){
            $return['playerId'] = array_column($rows, 'playerId');
            $return['playerId'] = array_filter($return['playerId']);
            $return['playerId'] = array_unique($return['playerId']);
            $return['count'] = count($return['playerId']);
        }else{
            if(!empty($rows)){
                $return['count'] = $rows[0]['count'];
            }
        }
		return $return;
    } // EOF getPlayerCountByLevelIdWithPlayer

    /**
	 * Add Game/s only to VIP casback if auto_tick_new_game_in_cashback_tree = 1 field in vipsetting table
	 * @param int $gamePlatformId
	 * @param int $gameTypeId
	 * @param int $gameDescriptionId
	 *
	 * @return void
	*/
	public function addGameOnlyIntoVipGroupCashback($gamePlatformId, $gameTypeId, $gameDescriptionId, &$failedCashbackRuleId)
	{

		$this->load->model(['game_description_model','vipsetting']);
		$vipLevels = $this->vipsetting->getVipGroupLevelInfoOfEnableAutoTickNewGame();
		$isGamePlatformAndGameTypeExist = $this->game_description_model->getGamePlatformIdByGameTypeId($gameTypeId);
		$failedCashbackRuleId = [];

		if(is_array($vipLevels) && count($vipLevels) > 0){
			$now=$this->utils->getNowForMysql();
			foreach($vipLevels as $vipLevel){
				$vipsetting_cashbackrule_id = isset($vipLevel['vipsettingcashbackruleId']) ? $vipLevel['vipsettingcashbackruleId'] : null;
				if(! empty($vipsetting_cashbackrule_id) && !empty($gamePlatformId) && !empty($gameTypeId) && !empty($gameDescriptionId) && (!empty($isGamePlatformAndGameTypeExist && $isGamePlatformAndGameTypeExist == $gamePlatformId))){

					#try add also game game platform id incase missing
					$this->syncCashbackGamePlatform([
							'vipsetting_cashbackrule_id' => $vipsetting_cashbackrule_id,
							'game_platform_id' => $gamePlatformId,
							'percentage' => 0,
							'status' => self::DB_TRUE,
							'updated_at' => $now,
					]);

					#try add also game type incase missing
					$this->syncCashbackGameType([
						'vipsetting_cashbackrule_id' => $vipsetting_cashbackrule_id,
						'game_type_id' => $gameTypeId,
						'percentage' => 0,
						'status' => self::DB_TRUE,
						'updated_at' => $now,
					]);


					#for game description/or the primary key(id) of game_description table of game
					$success=$this->syncCashbackGameDescription([
						'vipsetting_cashbackrule_id' => $vipsetting_cashbackrule_id,
						'game_description_id' => $gameDescriptionId,
						'percentage' => 0,# default to 0
						'status' => self::DB_TRUE,
						'updated_at' => $now,
					]);

					if(!$success){
						$this->utils->error_log('sync cashback game description failed', ['vipsetting_cashbackrule_id'=>$vipsetting_cashbackrule_id, 'game_description_id'=>$gameDescriptionId]);
						$failedCashbackRuleId[] = $vipsetting_cashbackrule_id;
					}
				}
			}
		}
		return true;
	}
}

// zR to open all folded lines
// vim:ft=php:fdm=marker
// end of group_level.php
///END OF FILE//////////
