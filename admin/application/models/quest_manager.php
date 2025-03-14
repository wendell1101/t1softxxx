<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';
require_once dirname(__FILE__) . '/modules/apply_quest_module.php';

/**
 * Depositpromo
 *
 * This model represents promo type.
 *
 */

class Quest_manager extends BaseModel {

    use apply_quest_module;

	protected $tableName = 'quest_manager';
    protected $ruleTable = 'quest_rule';
    protected $jobTable = 'quest_job';

    const SYSTEM_MANUAL_PROMO_TYPE_NAME = '_SYSTEM_MANUAL';
	const SYSTEM_MANUAL_PROMO_RULE_NAME = '_SYSTEM_MANUAL';
	const SYSTEM_MANUAL_PROMO_CMS_NAME = '_SYSTEM_MANUAL';

    const QUEST_CATEGORY_ORDER_MAX_CHARACTERS = 3;
    const QUEST_CATEGORY_NAME_MAX_CHARACTERS = 100;
    const QUEST_CATEGORY_INTERNAL_REMARK_MAX_CHARACTERS = 300;
    const QUEST_REWARD_STATUS_NOT_ACHIEVED = 1;
    const QUEST_REWARD_STATUS_ACHIEVED_NOT_RECEIVED = 2;
    const QUEST_REWARD_STATUS_RECEIVED = 3;
    const QUEST_REWARD_STATUS_EXPIRED = 4;
    const QUEST_BONUS_RELEASE_RULE_FIXED_AMOUNT = 1;
    const WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT = 1;
    const WITHDRAW_CONDITION_TYPE_BETTING_TIMES = 2;
    const WITHDRAW_CONDITION_TYPE_BONUS_TIMES = 3;
    const QUEST_LEVEL_TYPE_SINGLE = 1;
    const QUEST_LEVEL_TYPE_HIERARCHY = 2;
    const BONUS_APPLICATION_LIMIT_DATE_TYPE_NONE = 999;
    const BONUS_APPLICATION_LIMIT_DATE_TYPE_DAILY = 1;
    const BONUS_APPLICATION_LIMIT_DATE_TYPE_WEEKLY = 2;
    const BONUS_APPLICATION_LIMIT_DATE_TYPE_MONTHLY = 3;

    const ENABLED_AUTO_TICK_NEW_GAME = 1;
	const DISABLED_AUTO_TICK_NEW_GAME = 0;

	function __construct() {
		parent::__construct();
	}

    /**
	 * overview : get quest manager
	 * @return bool
    */
	public function getQuestManager() {
		$qry = "SELECT q_m.*,admin1.username AS createdBy, admin2.username AS updatedBy, q_c.title AS questCategoryTitle, q_c.questCategoryId AS questCategoryId, q_j.title AS questTitle, q_r.questRuleId as questRuleId
                FROM quest_manager AS q_m
                LEFT JOIN adminusers AS admin1
                    ON admin1.userId = q_m.createdBy
                LEFT JOIN adminusers AS admin2
                    ON admin2.userId = q_m.updatedBy
                LEFT JOIN quest_category AS q_c
                    ON q_c.questCategoryId = q_m.questCategoryId
                LEFT JOIN quest_job AS q_j
                    ON q_j.questManagerId = q_m.questManagerId
                LEFT JOIN quest_rule AS q_r
                    ON q_r.questRuleId = q_j.questRuleId 
                    OR q_r.questRuleId = q_m.questRuleId
                WHERE q_m.title != ?
                AND q_m.deleted <> 1
                AND q_r.status = 1
                ORDER BY q_m.questCategoryId DESC";
        $query = $this->db->query($qry, array(self::SYSTEM_MANUAL_PROMO_TYPE_NAME));

		if ($query->num_rows() > 0) {
			$data = $query->result_array();
			return $data;
		}
		return false;
	}

	/**
	 * add promo type
	 *
	 * @return	$array
	 */
	public function addQuestCategory($data) {
		$this->db->insert($this->tableName, $data);

		//checker
		if ($this->db->affected_rows()) {
			return $this->db->insert_id();
		}

		return FALSE;
	}

    public function getNextOrder() {
        $lastOrder = $this->getLastOrder();
        return $lastOrder += 1;
    }

    /**
     * detail: get the last order in the lists
     *
     * @return int
     */
    public function getLastOrder() {
        $this->db->select('sort')
                 ->from($this->tableName)
                 ->order_by('sort', 'desc')
                 ->limit(1);

        $qry = $this->db->get();
        $ord = $this->getOneRowOneField($qry, 'sort');
        if ($ord) {
            return intval($ord);
        }
        return self::DEFAULT_START_ORDER;
    }

    public function getQuestCategory() {
		$qry = "SELECT q_c.*,admin1.username AS createdBy, admin2.username AS updatedBy
                FROM quest_category AS q_c
                LEFT JOIN adminusers AS admin1
                    ON admin1.userId = q_c.createdBy
                LEFT JOIN adminusers AS admin2
                    ON admin2.userId = q_c.updatedBy
                WHERE q_c.title != ?
                AND q_c.deleted <> 1
                AND q_c.status = 1
                ORDER BY q_c.questCategoryId DESC";
        $query = $this->db->query($qry, array(self::SYSTEM_MANUAL_PROMO_TYPE_NAME));

		if ($query->num_rows() > 0) {
			$data = $query->result_array();
			return $data;
		}
		return false;
	}

    /**
	 * getQuestManagerDetails
	 *
	 * @return	$array
	 */
	public function getQuestManagerDetails($questManagerId) {
        $qry = "SELECT q_m.*, q_c.title AS questCategoryTitle, q_c.questCategoryId AS questCategoryId
                FROM quest_manager AS q_m
                LEFT JOIN quest_category AS q_c
                    ON q_c.questCategoryId = q_m.questCategoryId
                WHERE q_m.questManagerId = ?
                AND q_m.deleted <> 1";
		$query = $this->db->query($qry, $questManagerId);

		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
	}

    public function getAllCategoryTitle(){
        $qry = "SELECT questCategoryId,title FROM quest_category WHERE deleted = 0";
        $query = $this->db->query($qry);

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $data[] = $row;
            }
            return $data;
        }
    }

    public function getQuestRuleDetails($questRuleId) {
        $this->db->select('*')
                 ->from($this->ruleTable);
		$this->db->where('questRuleId', $questRuleId
                )->where('status', 1);
		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			foreach ($query->result_array() as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
    }

    public function getQuestRuleDetailsWithJob($questManagerId){
        $qry = "SELECT q_r.*, q_j.title AS title, q_j.questJobId AS questJobId
                FROM quest_rule AS q_r
                LEFT JOIN quest_job AS q_j
                    ON q_j.questRuleId = q_r.questRuleId
                WHERE q_j.questManagerId = ?
                AND q_r.status = 1";
        $query = $this->db->query($qry, $questManagerId);

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return false;
    }

    public function editQuestManager($data, $id) {
		$this->db->where('questManagerId', $id);
		$this->db->update($this->tableName, $data);

        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
	}

    public function addQuestRule($data){
        $this->db->insert($this->ruleTable, $data);

		//checker
		if ($this->db->affected_rows()) {
			return $this->db->insert_id();
		}

		return FALSE;
    }

    public function editQuestRule($data, $id) {
		$this->db->where('questRuleId', $id);
		$this->db->update($this->ruleTable, $data);
	}
    
    public function addQuestManager($data){
        $this->db->insert($this->tableName, $data);

        //checker
		if ($this->db->affected_rows()) {
			return $this->db->insert_id();
		}

		return FALSE;
    }

    public function addQuestJob($data){
        $this->db->insert($this->jobTable, $data);

        //checker
		if ($this->db->affected_rows()) {
			return $this->db->insert_id();
		}

		return FALSE;
    }

    public function editQuestJob($data, $id) {
        if($id != null){
            $this->db->where('questJobId', $id);
            $this->db->update($this->jobTable, $data);
        }else{
            $this->db->insert($this->jobTable, $data);

            //checker
            if ($this->db->affected_rows()) {
                return $this->db->insert_id();
            }
    
            return FALSE;
        }
    }
    public function getQuestJobByQuestManagerId($managerId){
        $this->db->select([
            'quest_job.questJobId',
            'quest_job.questManagerId',
            'quest_job.questRuleId',
            'quest_job.title',
            'quest_rule.questConditionType',
            'quest_rule.questConditionValue',
            'quest_rule.personalInfoType',
            'quest_rule.bonusConditionType',
            'quest_rule.bonusConditionValue',
            'quest_rule.withdrawalConditionType',
            'quest_rule.withdrawReqBonusTimes',
            'quest_rule.withdrawReqBetAmount',
            'quest_rule.withdrawReqBettingTimes',
            'quest_rule.communityOptions',
            'quest_rule.extraRules',
            'quest_rule.createdAt',
            'quest_rule.status'
        ]);
        $this->db->from('quest_job');
        $this->db->join('quest_rule', 'quest_job.questRuleId = quest_rule.questRuleId', 'left');
        $this->db->where('quest_job.questManagerId', $managerId);
        $this->db->where('quest_rule.status', 1);
        $result = $this->runMultipleRowArray();
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query(), 'result', $result);
        return $result;
    }

    public function getQuestProgressByPlayer($playerId = _COMMAND_LINE_NULL, $questId, $fromDatetime = null, $toDatetime = null, $isHierarchy = false, $limit = null, $conditions = []){

        $this->utils->debug_log(__METHOD__, 'questId', $questId, 'fromDatetime', $fromDatetime, 'toDatetime', $toDatetime);
        $this->db->select([
            'player_quest_job_state.id',
            'player_quest_job_state.playerId',
            'player_quest_job_state.questManagerId',
            'player_quest_job_state.questJobId',
            'player_quest_job_state.jobStats',
            'player_quest_job_state.transactionId',
            'player_quest_job_state.bonusAmount',
            'player_quest_job_state.rewardStatus',
            'player_quest_job_state.createdAt',
        ]);
        $this->db->from('player_quest_job_state');
        $this->db->where('player_quest_job_state.questManagerId', $questId);

        if (isset($conditions['rewardStatus'])) {
            $this->db->where_in('player_quest_job_state.rewardStatus', $conditions['rewardStatus']);
        }

        if($playerId != _COMMAND_LINE_NULL){
            if(is_array($playerId)){
                $this->db->where_in('player_quest_job_state.playerId', $playerId);
            }else{
                $this->db->where('player_quest_job_state.playerId', $playerId);
            }
        }

        if(!empty($fromDatetime) && !empty($toDatetime)){
            $this->db->where('player_quest_job_state.createdAt >=', $fromDatetime);
            $this->db->where('player_quest_job_state.createdAt <=', $toDatetime);
        }

        if($isHierarchy){
            $this->db->join('quest_job', 'quest_job.questJobId = player_quest_job_state.questJobId', 'left');
            $this->db->join('quest_rule', 'quest_rule.questRuleId = quest_job.questRuleId', 'left');
            $this->db->where('quest_rule.status', 1);

            if($limit != null){
                $this->db->order_by('player_quest_job_state.id', 'desc');
                $this->db->limit($limit);
            }
        }

        $result = $this->runMultipleRowArray();

        if($isHierarchy){
            $idArray = array_column($result, 'id');
            // 使用 'id' 字段对 $result 进行排序
            array_multisort($idArray, SORT_ASC, $result);
        }

        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query(), 'limit', $limit);
        return $result;
    }

    public function getQuestRuleByQuestRuleId($questRuleId){
        $this->db->select([
            'quest_rule.questRuleId',
            'quest_rule.questConditionType',
            'quest_rule.questConditionValue',
            'quest_rule.personalInfoType',
            'quest_rule.bonusConditionType',
            'quest_rule.bonusConditionValue',
            'quest_rule.withdrawalConditionType',
            'quest_rule.withdrawReqBonusTimes',
            'quest_rule.withdrawReqBetAmount',
            'quest_rule.withdrawReqBettingTimes',
            'quest_rule.communityOptions',
            'quest_rule.extraRules',
            'quest_rule.createdAt',
        ]);
        $this->db->from('quest_rule');
        $this->db->where('quest_rule.questRuleId', $questRuleId);

        $result = $this->runOneRowArray();
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query(), 'result', $result);
        return $result;
    }

    public function getQuestRuleByJobId($questJobId){
        $this->db->select([
            'quest_rule.questRuleId',
            'quest_rule.questConditionType',
            'quest_rule.questConditionValue',
            'quest_rule.personalInfoType',
            'quest_rule.bonusConditionType',
            'quest_rule.bonusConditionValue',
            'quest_rule.withdrawalConditionType',
            'quest_rule.withdrawReqBonusTimes',
            'quest_rule.withdrawReqBetAmount',
            'quest_rule.withdrawReqBettingTimes',
            'quest_rule.communityOptions',
            'quest_rule.extraRules',
            'quest_rule.createdAt',
        ]);
        $this->db->from('quest_job');
        $this->db->join('quest_rule', 'quest_job.questRuleId = quest_rule.questRuleId', 'left');
        $this->db->where('quest_job.questJobId', $questJobId);

        $result = $this->runOneRowArray();
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
        return $result;
    }

    public function getQuestProgressByQuestJobId($playerId, $questJobId, $fromDatetime = null, $toDatetime = null){
        $this->db->select([
            'player_quest_job_state.id',
            'player_quest_job_state.playerId',
            'player_quest_job_state.questManagerId',
            'player_quest_job_state.questJobId',
            'player_quest_job_state.jobStats',
            'player_quest_job_state.rewardStatus',
            'player_quest_job_state.createdAt',
        ]);
        $this->db->from('player_quest_job_state');
        $this->db->where('player_quest_job_state.playerId', $playerId);
        $this->db->where('player_quest_job_state.questJobId', $questJobId);

        if(!empty($fromDatetime) && !empty($toDatetime)){
            $this->db->where('player_quest_job_state.createdAt >=', $fromDatetime);
            $this->db->where('player_quest_job_state.createdAt <=', $toDatetime);
        }

        $this->db->order_by('player_quest_job_state.createdAt', 'desc');

        $result = $this->runOneRowArray();
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query(), 'result', $result);
        return $result;
    }

    public function createQuestProgress($data) {
        return $this->insertData('player_quest_job_state', $data);
    }

    public function updatePlayerQuestJobState($playerId, $questManagerId, $questStateId, $statsData){

        if(empty($statsData)){
            return false;
        }
        $this->db->where('playerId', $playerId);
        $this->db->where('questManagerId', $questManagerId);
        $this->db->where('id', $questStateId);
        $this->db->update('player_quest_job_state', $statsData);

        if ($this->db->affected_rows()) {
            return true;
        }else{
            return false;
        }
    }

    public function getAllQuestManager($questCategoryId = _COMMAND_LINE_NULL, $questManagerId = _COMMAND_LINE_NULL, $conditions = []){

        $this->db->from("quest_manager");

        if($questCategoryId != _COMMAND_LINE_NULL){
            $this->db->where('questCategoryId', $questCategoryId);
        }

        if($questManagerId != _COMMAND_LINE_NULL){
            $this->db->where('questManagerId', $questManagerId);
        }

        if(!empty($conditions['questManagerType'])){
            $this->db->where('questManagerType', $conditions['questManagerType']);
        }

        $this->db->where('deleted', 0);
        $this->db->where('status', 1);

        $result = $this->runMultipleRowArray();
        // $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query(), 'result', $result);
        return $result;
    }

    public function getQuestManagerDetailsById($questManagerId) {
        $this->db->from("quest_manager")->where('questManagerId', $questManagerId)->where('deleted', 0);
        $result = $this->runOneRowArray();
        // $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
        return $result;
    }

    public function getQuestCategoryDetails($questCategoryId) {
        $this->db->from("quest_category")->where('questCategoryId', $questCategoryId)->where('deleted', 0);
        $result = $this->runOneRowArray();
        // $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
        return $result;
    }

    public function getQuestManagerByCategoryId($questCategoryId) {
        $this->db->from("quest_manager")->where('questCategoryId', $questCategoryId);
        $result = $this->runMultipleRowArray();
        // $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query(), 'result', $result);
        return $result;
    }

    public function getMaxQuestJobId($questManagerId){
        $this->db->select_max('questJobId')->from('player_quest_job_state')->where('questManagerId', $questManagerId)->where('rewardStatus', self::QUEST_REWARD_STATUS_RECEIVED);
        $result = $this->runOneRowOneField('questJobId');
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query(), 'result', $result);
        return empty($result) ? 0 : $result;
    }

    public function existsSinglePlayerQuestJobState($questManagerId) {
        $this->db->select('player_quest_job_state.id')
                 ->from('player_quest_job_state')
                 ->where('player_quest_job_state.questManagerId', $questManagerId)
                 ->where('player_quest_job_state.rewardStatus', self::QUEST_REWARD_STATUS_RECEIVED);
        $this->db->limit(1);

        return $this->runExistsResult();
    }

    public function existsPlayerQuestFromSameIp($questManagerId, $ip, $questJobId, $playerId) {
        $this->db->select('player_quest_job_state.id')
                 ->from('player_quest_job_state')
                 ->where('player_quest_job_state.questManagerId', $questManagerId)
                 ->where('player_quest_job_state.questJobId', $questJobId)
                 ->where('playerRequestIp', $ip)
                 ->where('playerId != ', $playerId);

		return $this->runExistsResult();
	}

    public function setPlayerQuestState($questManagerId, $fromState, $toState, $playerId = _COMMAND_LINE_NULL, $questJobId = _COMMAND_LINE_NULL){
        $this->utils->debug_log(__METHOD__, 'questManagerId', $questManagerId, 'fromState', $fromState, 'toState', $toState, 'playerId', $playerId, 'questJobId', $questJobId);
        if($playerId != _COMMAND_LINE_NULL){
            $this->db->where('playerId', $playerId);
        }

        if($questJobId != _COMMAND_LINE_NULL){
            $this->db->where('questJobId', $questJobId);
        }

        $this->db->where('questManagerId', $questManagerId);
        $this->db->where('rewardStatus', $fromState);
        $this->db->update('player_quest_job_state', ['rewardStatus' => $toState]);

        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query(), 'affected', $this->db->affected_rows());

        return $this->db->affected_rows();
    }
    public function getGameTreeForQuestById($managerId, $filterColumn=array()) {

		$this->load->model(array('game_description_model'));

		list($gamePlatformList, $gameTypeList, $gameDescList) = $this->getQuestIdGameTypeAndDesc($managerId);

		$showGameDescTree = $this->config->item('show_particular_game_in_tree');

		return $this->game_description_model->getGameTreeArray($gamePlatformList, $gameTypeList, $gameDescList, false, $showGameDescTree, $filterColumn);
	}

	public function getGameTreeForQuest($filterColumn=array()) {

		$this->load->model(array('game_description_model'));

		$gamePlatformList = array();
		$gameTypeList = array();
		$gameDescList = array();

		$showGameDescTree = $this->config->item('show_particular_game_in_tree');

		return $this->game_description_model->getGameTreeArray($gamePlatformList, $gameTypeList, $gameDescList, false, $showGameDescTree, $filterColumn);
	}

    public function getQuestIdGameTypeAndDesc($managerId) {
		$this->db->select('game_description.game_platform_id,game_description.game_type_id, game_description.id as game_description_id')
			->from('quest_game_bet_rule')
			->join('game_description', 'game_description.id = quest_game_bet_rule.game_description_id', 'left')
			->where('quest_game_bet_rule.managerId', $managerId);

		$rows = $this->runMultipleRowArray();

		$gamePlatformList = array();
		$gameTypeList = array();
		$gameDescList = array();

		if (!empty($rows)) {
			foreach ($rows as $row) {
				$gamePlatformList[$row['game_platform_id']] = 0; //$row['game_platform_percentage'];
				$gameTypeList[$row['game_type_id']] = 0; //$row['game_type_percentage'];
				$gameDescList[$row['game_description_id']] = 0; //$row['game_desc_percentage'];
			}
		}

		return array($gamePlatformList, $gameTypeList, $gameDescList);
	}

    public function batchAddAllowedGames($managerId, $gamesAptList) {
		$this->utils->debug_log('quest batchAddAllowedGames ===========>', $managerId);
		$this->deleteQuestGameBetRule($managerId);

		foreach ($gamesAptList as $gameDescriptionId) {
			$this->db->select('questgametypeId')->where('game_description_id',$gameDescriptionId['id'])->where('managerId',$managerId);
			$isGameExist = $this->db->get('quest_game_bet_rule');

			if ($isGameExist->row('questgametypeId')) continue;

			$data[] = array(
				'managerId' => $managerId,
				'game_description_id' => $gameDescriptionId['id'],
			);
		}

		if (!empty($data)) {
			return $this->db->insert_batch('quest_game_bet_rule', $data);
		}

		return true;
	}

    public function deleteQuestGameBetRule($managerId = '') {
		$this->db->where('managerId', $managerId);
		$this->db->delete('quest_game_bet_rule');
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
	}

    public function getPlayerGamesKV($questIdOrArr)
    {
        $this->db->distinct()->select('game_description_id')->from('quest_game_bet_rule');
        if (is_array($questIdOrArr)) {
            $this->db->where_in('managerId', $questIdOrArr);

        } else {
            $this->db->where('managerId', $questIdOrArr);

        }
        $qry = $this->db->get();
        
        if ($qry && $qry->num_rows() > 0) {
            foreach ($qry->result_array() as $row) {
                $data[$row['game_description_id']] = $row['game_description_id'];
            }
            return $data;
        }

        return false;
    }

    public function getQuestManagerInfoOfEnableAutoTickNewGame($rows='q_m.questManagerId')
	{
		$query = $this->db->select($rows)
					->from($this->tableName." q_m")
					->where('q_m.auto_tick_new_game_in_cashback_tree',self::ENABLED_AUTO_TICK_NEW_GAME)
					->get();
		return $this->getMultipleRowArray($query);
	}

    /**
     * Check if Game is duplicate/exist in table quest_game_bet_rule
     *
     * @param int $gameDescriptionId
     * @param int $managerId
	 *
     * @return boolean
    */
    public function isDuplicateQuestGameBetRule($gameDescriptionId,$managerId){

		$this->db->select('questgametypeId')
				->from('quest_game_bet_rule')
				->where("game_description_id",$gameDescriptionId)
				->where("managerId",$managerId);

        return $this->runExistsResult();
    }

    /**
	 * Add Game/s to Quest Manager if auto_tick_new_game_in_cashback_tree = 1 field in quest_manager table
	 * @param int $gameDescriptionId
	 *
	 * @return void
	*/
	public function addGameIntoManagerGameType($gameDescriptionId, &$failedQuestManagerId)
	{
		$this->load->model(['game_description_model']);

		$questManagers = $this->getQuestManagerInfoOfEnableAutoTickNewGame();
		$isGameExist = $this->game_description_model->getGamePlatformIdByGameDescriptionId($gameDescriptionId);
		$failedQuestManagerId = [];
		if(is_array($questManagers) && count($questManagers) > 0){
			foreach($questManagers as $aQuestManager){
				if( !empty($gameDescriptionId) && $isGameExist){
					$managerId = isset($aQuestManager['questManagerId']) ? $aQuestManager['questManagerId'] : null;
					$isDuplicateQuestGameBetRule = $this->isDuplicateQuestGameBetRule($gameDescriptionId,$managerId);

					# insert it if not yet exist
					if(! $isDuplicateQuestGameBetRule){
						$data = [
							'managerId' => $managerId,
							'game_description_id' => $gameDescriptionId,
						];

						$this->insertData('quest_game_bet_rule', $data);

                        if( empty($this->db->insert_id() ) ){
                            $this->utils->error_log('sync quest game description failed', ['managerId'=> $managerId, 'game_description_id'=>$gameDescriptionId]);
                            $failedQuestManagerId[] = $managerId;
                        }
					}
				}
			}
		}
	}

    public function checkAndUpdateDisplayPanel()
    {
        $this->db->select('*');
        $this->db->from($this->tableName);
        $this->db->where('levelType', 1);
        $this->db->where('displayPanel', 1);
        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            $this->db->set('displayPanel', 0);
            $this->db->where('levelType', 1);
            $this->db->where('displayPanel', 1);
            $this->db->update($this->tableName);

            return $this->db->affected_rows();
        } else {
            return false;
        }
    }
}
/* End of file depositpromo.php */
/* Location: ./application/models/promo_type.php */
