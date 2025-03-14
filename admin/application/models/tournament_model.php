<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Tournament_model
 * @property tournament_lib $tournament_lib
 */
class Tournament_model extends BaseModel{
    /**
     * Status : Tournament, Schedule, Event
     */
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DELETE = 2;

    /**
     * tournamentTemplate
     */
    const TOURNAMENT_TEMPLATE_ESPORTS = 1;
    const TOURNAMENT_TEMPLATE_SPORTS = 2;
    const TOURNAMENT_TEMPLATE_LIVE_DEALER = 3;
    const TOURNAMENT_TEMPLATE_FISHING_GAME = 4;

    /**
     * tournamentType
     */
    const TOURNAMENT_RANK_TYPE_BETS = 1;
    const TOURNAMENT_RANK_TYPE_PAYOUT = 2;

    /**
     *  tournamentSchedule BonusType
     */
    const SCHEDULE_BONUS_TYPE_SYSTEM = 1;
    const SCHEDULE_BONUS_TYPE_APPLY_AMOUNT = 2;
    const SCHEDULE_BONUS_TYPE_SYSTEM_AND_APPLY_AMOUNT = 3;

    /**
     *  tournamentSchedule releaseType
     */
    const SCHEDULE_RELEASE_TYPE_MANUAL = 1;
    const SCHEDULE_RELEASE_TYPE_AUTO = 2;
    
    /**
     *  tournament_event targetPlayerType
     */
    const EVENT_TARGET_PLAYER_TYPE_ALL = 1;
    const EVENT_TARGET_PLAYER_TYPE_VIP = 2;
    const EVENT_TARGET_PLAYER_TYPE_AFFILIATE = 3;
    const EVENT_TARGET_PLAYER_TYPE_AGENCY = 4;
    const EVENT_TARGET_PLAYER_TYPE_PLAYERS = 5;

    /**
     *  tournament_event applyConditionCountPeriod
     */
    const EVENT_APPLY_CONDITION_REGISTE_DATE = 1;
    const EVENT_APPLY_CONDITION_FIXED_DATE = 2;
    
    /*
     *  tournament_rank bonusType
     */
    const RANK_BONUS_TYPE_FIXED_AMOUNT = 1;
    const RANK_BONUS_TYPE_PERCENTAGE = 2;

    /*
     *  tournament_player_apply_records isApply
     */
    const PLAYER_APPLY_RECORDS_UNAPPLIED = 0;
    const PLAYER_APPLY_RECORDS_APPLIED = 1;
    
    /*
     *  tournament_player_apply_records isRelease
     */
    const PLAYER_APPLY_RECORDS_PAID = 1;
    const PLAYER_APPLY_RECORDS_UNPAID = 0;

    private $tableName = 'tournament';

    private $userId = '';

    public function __construct(){
        $this->load->library(['authentication', 'tournament_lib']);
        $this->userId = !empty($this->authentication->getUserId())? $this->authentication->getUserId() : 1;
        parent::__construct();
    }

    public function setMainTableName($tableName){
        $this->tableName = $tableName;
    }

    public function getTournamentAPIListPagination($limit, $page, $condtions = [], $orderby = [], $direction = "DESC"){
        $this->setMainTableName('tournament_schedule');
        $result = $this->getDataWithAPIPagination($this->tableName, function() use($condtions, $orderby, $direction) {
            $this->db->select('tournament.id as tournamentId, 
                tournamentName,
                currency,
                order,
                tournamentTemplate,
                tournamentType,
                tournament.description as tournamentDescription,
                tournament_schedule.id as scheduleId,
                scheduleName,
                periods,
                bonusType,
                tournamentStartedAt,
                tournamentEndedAt,
                applyStartedAt,
                applyEndedAt,
                contestStartedAt,
                contestEndedAt,
                tournament_schedule.description as scheduleDescription,
                icon,
                banner');
            $this->db->join('tournament', 'tournament.id = tournament_schedule.tournamentId', 'left');
            if(!empty($condtions)){
                foreach ($condtions as $condtion => $value) {
                    switch ($condtion) {
                        case 'tournamentStatus':
                            $this->db->where('tournament.status', $value);
                            break;
                        case 'scheduleStatus':
                            $this->db->where('tournament_schedule.status', $value);
                            break;
                        case 'activeTournamentDate':
                            $this->db->where('tournamentStartedAt <=', $value);
                            $this->db->where('tournamentEndedAt >=', $value);
                            break;
                        default:
                            $this->db->where($condtion, $value);
                            break;
                    }
                }
            }
            if(!empty($orderby)){
                if(!in_array($direction, ["asc","desc"])){
                    $direction = '';
                }
                $this->db->order_by(implode(",", $orderby), $direction);
            }
        }, $limit, $page);
        return $result;        
    }

    public function getTournamentListPagination($limit, $page, $conditions = [], $orderby = [], $direction = "desc"){
        $result = $this->getDataWithAPIPagination('tournament', function() use($conditions, $orderby, $direction) {
            $this->db->select('id as tournamentId, 
                tournamentName,
                currency,
                order,
                tournamentTemplate,
                tournamentType,
                description,
                status as tournamentStatus,
                createdAt as tournamentCreatedAt,
                createdBy as tournamentCreatedBy
                ');
            if(!empty($conditions['tournamentId'])){
                $this->db->where('tournament.id', $conditions['tournamentId']);
            }
            if(!empty($conditions['tournamentStatus'])){
                $this->db->where('tournament.status', $conditions['tournamentStatus']);
            }
            if(!empty($orderby)){
                if(!in_array($direction, ["asc","desc"])){
                    $direction = '';
                }
                $this->db->order_by(implode(",", $orderby), $direction);
            }
        }, $limit, $page);
        return $result;
    }

    public function getScheduleListPagination($limit, $page, $conditions = [], $orderby = [], $direction = "desc"){
        $result = $this->getDataWithAPIPagination('tournament_schedule', function() use($conditions, $orderby, $direction) {
            $this->db->select('id as scheduleId,
                tournamentId,
                scheduleName,
                periods,
                description,
                status as scheduleStatus,
                bonusType,
                tournamentStartedAt,
                tournamentEndedAt,
                applyStartedAt,
                applyEndedAt,
                contestStartedAt,
                contestEndedAt,
                distributionType,
                distributionTime,
                icon,
                banner,
                createdAt as scheduleCreatedAt,
                createdBy as scheduleCreatedBy
                ');
            $this->db->where('deletedAt IS NULL');
            if(!empty($conditions['scheduleId'])){
                $this->db->where('id', $conditions['scheduleId']);
            }
            if(!empty($conditions['tournamentId'])){
                $this->db->where('tournamentId', $conditions['tournamentId']);
            }
            if(!empty($conditions['activeTournamentDate'])){
                $this->db->where('tournamentStartedAt <=', $conditions['activeTournamentDate']);
                $this->db->where('tournamentEndedAt >=', $conditions['activeTournamentDate']);
            }
            if(!empty($conditions['scheduleStatus'])){
                $this->db->where('status', $conditions['scheduleStatus']);
            }
            if(!empty($orderby)){
                if(!in_array($direction, ["asc","desc"])){
                    $direction = '';
                }
                $this->db->order_by(implode(",", $orderby), $direction);
            }
        }, $limit, $page);
        return $result;
    }

    public function getEventListPagination($limit, $page, $conditions = [], $orderby = [], $direction = "desc"){
        $result = $this->getDataWithAPIPagination('tournament_event', function() use($conditions, $orderby, $direction) {
            $this->db->select('id as eventId,
                scheduleId,
                eventName,
                registrationFee,
                applyCountThreshold,
                status as eventStatus,
                createdAt as eventCreatedAt,
                createdBy as eventCreatedBy
                ');
            $this->db->where('deletedAt IS NULL');
            if(!empty($conditions['scheduleId'])){
                $this->db->where('scheduleId', $conditions['scheduleId']);
            }
            if(!empty($conditions['eventId'])){
                $this->db->where('id', $conditions['eventId']);
            }
            if(!empty($orderby)){
                if(!in_array($direction, ["asc","desc"])){
                    $direction = '';
                }
                $this->db->order_by(implode(",", $orderby), $direction);
            }
        }, $limit, $page);
        return $result;
    }

    public function getTournamentSeriesIds($tournamentId){
        $this->db->from('tournament');
        $this->db->select('tournament.id as tournamentId,
            tournament_schedule.id as scheduleId,
            GROUP_CONCAT( DISTINCT tournament_event.id) as eventIds');
        $this->db->join('tournament_schedule', 'tournament.id = tournament_schedule.tournamentId', 'left');
        $this->db->join('tournament_event', 'tournament_schedule.id = tournament_event.scheduleId', 'left');
        $this->db->where('tournament.id', $tournamentId);
        $query = $this->db->get();
        return $this->getOneRowArray($query);
    }

    public function getTournamentSchedule($conditions = []){
        $this->db->from('tournament_schedule');
        $this->db->join('tournament', 'tournament.id = tournament_schedule.tournamentId', 'left');
        $this->db->select('tournament.id as tournamentId, 
                tournament_schedule.id as scheduleId,
                tournamentName,
                currency,
                order,
                tournamentTemplate,
                tournamentType,
                tournament.description as tournamentDescription,
                tournament_schedule.id as scheduleId,
                scheduleName,
                periods,
                bonusType,
                tournamentStartedAt,
                tournamentEndedAt,
                applyStartedAt,
                applyEndedAt,
                contestStartedAt,
                contestEndedAt,
                tournament_schedule.description as scheduleDescription,
                distributionType,
                distributionTime,
                icon,
                banner');
        if(!empty($conditions['scheduleId'])){
            $this->db->where('tournament_schedule.id', $conditions['scheduleId']);
        }
        if(!empty($conditions['scheduleStatus'])){
            $this->db->where('tournament_schedule.status', $conditions['scheduleStatus']);
        }
        if(!empty($conditions['tournamentStatus'])){
            $this->db->where('tournament.status', $conditions['tournamentStatus']);
        }
        $query = $this->db->get();
        return $this->getOneRowArray($query);
    }

    public function getTournamentEvents($conditions = []){
        $this->db->from('tournament_event');
        $this->db->select('id,
            scheduleId,
            eventName,
            targetPlayerType,
            applyConditionDepositAmount,
            applyConditionCountPeriod,
            applyConditionCountPeriodStartAt,
            applyConditionCountPeriodEndAt,
            applyCountThreshold,
            registrationFee
        ');
        if(!empty($conditions['scheduleId'])){
            $this->db->where('scheduleId', $conditions['scheduleId']);
        }
        if(!empty($conditions['eventStatus'])){
            $this->db->where('status', $conditions['eventStatus']);
        }
        $this->db->order_by('order');
        $query = $this->db->get();
        return $this->getMultipleRowArray($query);
    }

    public function getTournamentRanks($conditions = []){
        $this->db->from('tournament_rank');
        $this->db->select('id,
            eventId,
            rankFrom,
            rankTo,
            bonusValue,
            withdrawalConditionFixedAmount,
            withdrawalConditionTimes,
        ');
        if(!empty($conditions['eventId'])){
            if(is_array($conditions['eventId'])){
                $this->db->where_in('eventId', $conditions['eventId']);
            }else{
                $this->db->where('eventId', $conditions['eventId']);
            }
        }
        $this->db->order_by('rankFrom');
        $query = $this->db->get();
        return $this->getMultipleRowArray($query);
    }

    public function getTournamentRanksSetting($eventId){
        $this->db->from('tournament_rank');
        $this->db->select(['tournament_rank.id'
            ,'tournament_rank.eventId'
            ,'tournament_rank.rankFrom'
            ,'tournament_rank.rankTo'
            ,'tournament_rank.bonusType'
            ,'tournament_rank.bonusValue'
            // ,'withdrawalConditionFixedAmount'
            ,'tournament_schedule.withdrawalConditionTimes'
            ]
        );
        $this->db->join('tournament_event', 'tournament_event.id = tournament_rank.eventId', 'left');
        $this->db->join('tournament_schedule', 'tournament_schedule.id = tournament_event.scheduleId', 'left');
        $this->db->where('eventId', $eventId);
        $this->db->order_by('rankFrom');
        $query = $this->db->get();
        return $this->getMultipleRowArray($query);
    }

    public function getScheduleApplyCount($scheduleId){
        $this->db->from('tournament_schedule');
        $this->db->select('count(tournament_player_apply_records.id) as applyCount');
		$this->db->join('tournament_event', 'tournament_event.scheduleId = tournament_schedule.id', 'left');
        $this->db->join('tournament_player_apply_records', 'tournament_event.id = eventId', 'left');
        $this->db->where('tournament_schedule.id', $scheduleId);
        $query = $this->db->get();
        return $this->getOneRowArray($query);
    }

    public function getScheduleTotalSysBonusAmount($scheduleId){
        $this->db->from('tournament_schedule');
        $this->db->select('systemBonusAmount');
        $this->db->where('tournament_schedule.id', $scheduleId);
        $query = $this->db->get();
        return $this->getOneRowArray($query);
    }

    public function getScheduleTotalRegistrationFee($scheduleId){
        $this->db->from('tournament_schedule');
        $this->db->select('accumulateRegistrationFee');
        $this->db->where('tournament_schedule.id', $scheduleId);
        $query = $this->db->get();
        return $this->getOneRowArray($query);
    }
    public function getScheduleTotalRegistrationFeeAndSysBonus($scheduleId){
        $this->db->from('tournament_schedule');
        $this->db->select('SUM(accumulateRegistrationFee + systemBonusAmount)');
        $this->db->where('tournament_schedule.id', $scheduleId);
        $query = $this->db->get();
        return $this->getOneRowArray($query);
    }

    public function getTournamentIdByScheduleId($scheduleId){
        $this->db->from('tournament_schedule');
        $this->db->select('tournamentId');
        $this->db->where('id', $scheduleId);
        $query = $this->db->get();
        return $this->getOneRowOneField($query, 'tournamentId');
    }

    public function getEventById($event_id, $getAvalible = false) {
        $this->db->from('tournament_event');
        $this->db->select('tournament_event.id as eventId, 
            tournament_event.registrationFee as applyAmount,
            tournament_event.status as eventStatus,
            tournament_event.applyCountThreshold as applyCountThreshold');

        $this->db->select('tournament.tournamentName as tournamentName,
            tournament.currency as currency,
            tournament.status as tournamentStatus');
        
        //tournament_schedule
        $this->db->select('tournament_schedule.tournamentId as tournamentId,
            tournament_schedule.status as scheduleStatus,
            tournament_schedule.id as scheduleId,
            tournament_schedule.scheduleName as scheduleName,
            tournament_schedule.tournamentStartedAt as tournamentStartedAt,
            tournament_schedule.tournamentEndedAt as tournamentEndedAt,
            tournament_schedule.applyStartedAt as applyStartedAt,
            tournament_schedule.applyEndedAt as applyEndedAt,
            tournament_schedule.contestStartedAt as contestStartedAt,
            tournament_schedule.contestEndedAt as contestEndedAt,
            tournament_schedule.distributionTime as distributionTime,
            tournament_schedule.withdrawalConditionTimes as withdrawalConditionTimes,
            tournament_schedule.distributionType as distributionType');

        //tournament bonus
        $this->db->select('tournament_schedule.bonusType as bonusType,
            tournament_schedule.systemBonusAmount as systemBonusAmount,
            tournament_schedule.accumulateRegistrationFee as accumulateRegistrationFee');
        
        //eventRequirements
        $this->db->select('tournament_event.targetPlayerType,
            tournament_event.applyConditionDepositAmount as applyConditionDepositAmount,
            tournament_event.applyConditionCountPeriod as applyConditionCountPeriod,
            tournament_event.applyConditionCountPeriodStartAt as applyConditionCountPeriodStartAt,
            tournament_event.applyConditionCountPeriodEndAt as applyConditionCountPeriodEndAt');
        
        $this->db->join('tournament_schedule', 'tournament_schedule.id = tournament_event.scheduleId', 'left');
        $this->db->join('tournament', 'tournament.id = tournament_schedule.tournamentId', 'left');
        $this->db->where('tournament_event.id', $event_id);

        if($getAvalible){
            $this->db->where('tournament_event.status', self::STATUS_ACTIVE);
            $this->db->where('tournament_schedule.status', self::STATUS_ACTIVE);
            $this->db->where('tournament.status', self::STATUS_ACTIVE);
        }

        $query = $this->db->get();
        $rowData = $this->getOneRowArray($query);
        if(empty($rowData)){
            return false;
        }
        return $rowData;
	}

	public function applyEvent($tournament_id, $event_id, $player_id) {
		//do insert
		if($this->checkEventPlayer($event_id, $player_id)) {
			return false;
		} else {
			$this->db->insert('tournament_player_apply_records', [
				'tournamentId' => $tournament_id,
				'eventId' => $event_id,
				'playerId' => $player_id,
				'eventScore' => 0,
				'bonusAmount' => 0,
				'applyTransId' => null,
				'bonusTransId' => null,
				'WithdrawalCondictionId' => null,
				'isReleased' => 0,
				'releaseTime' => null,
                'external_uniqueid' => $this->tournament_lib->generateApplyExternalUniqueid($tournament_id, $event_id, $player_id),
			]);
		}
		return $this->db->insert_id();
	}

    public function checkEventExist($event_id) {
        $this->db->select('id');
        $this->db->from('tournament_event');
        $this->db->where('id', $event_id);
        $query = $this->db->get();
        $row = $query->row_array();
        return $row ? true : false;
    }
	public function checkEventPlayer($event_id, $player_id) {
		$this->db->select('id');
		$this->db->from('tournament_player_apply_records');
		$this->db->where('eventId', $event_id);
		$this->db->where('playerId', $player_id);
		$query = $this->db->get();
		$row = $query->row_array();
		return $row ? true : false;
	}

    public function getPlayerEventRank($event_id, $player_id){
        $this->db->from('tournament_player_apply_records');
        $this->db->select('tournament_player_apply_records.eventScore,
        tournament_player_apply_records.bonusAmount,
        tournament_player_apply_records.createdAt as applyTime,');
        $this->db->where('eventId', $event_id);
        $this->db->where('playerId', $player_id);
        $query = $this->db->get();
        return $this->getOneRowArray($query);
    }

    public function getTournamentGamesByScheduleId($scheduleId){
        $tournament_id = $this->getTournamentIdByScheduleId($scheduleId);
        if(empty($tournament_id)){
            return false;
        }
        return $this->getTournamentGames($tournament_id);
    }
    public function getTournamentGames($tournament_id) {
        $this->db->select('tournament.id, tournament.gamePlatformId, tournament.gameTypeId, tournament.gameTagId, tournament.gameDescriptionId');
        $this->db->from('tournament');
        $this->db->where('tournament.id', $tournament_id);
        $query = $this->db->get();
        return $query->row_array();
    }

    /**
     * getActiveEventsByCurrentTime function
     *
     * @param array $conditions['scheduleId', 'eventStatus', 'current_time']
     * @return array
     */
    public function getActiveEventsByCurrentTime($conditions = []){
        $this->db->from('tournament');
        $this->db->select('tournament_event.id as eventId');
        $this->db->join('tournament_schedule', 'tournament.id = tournament_schedule.tournamentId', 'left');
        $this->db->join('tournament_event', 'tournament_schedule.id = tournament_event.scheduleId', 'left');

        if(!empty($conditions['tournamentId'])){
            $this->db->where('tournament.id', $conditions['tournamentId']);
        }
        if(!empty($conditions['current_time'])){
            $this->db->where('tournament_schedule.contestStartedAt <=', $conditions['current_time']);
            $this->db->where('tournament_schedule.tournamentEndedAt >=', $conditions['current_time']);
        } else {

            $this->db->where('tournament_schedule.contestStartedAt <=', $this->utils->getNowForMysql());
            $this->db->where('tournament_schedule.tournamentEndedAt >=', $this->utils->getNowForMysql());
        }
        
        $this->db->where('tournament.status', self::STATUS_ACTIVE);
        $this->db->where('tournament_schedule.status', self::STATUS_ACTIVE);
        $this->db->where('tournament_event.status', self::STATUS_ACTIVE);

        $query = $this->db->get();
        return $this->getMultipleRowArray($query);
    }

    public function getTournamentById($tournament_id) {
        $this->db->from('tournament');
        $this->db->select('*');
        $this->db->where('id', $tournament_id);
        $query = $this->db->get();
        return $this->getOneRowArray($query);
    }

    public function getGamesDescId($gamePlatformIds = null, $gameTypeIds = null, $gameTagIds = null, $gamedescriptionIds = null){
        $all_game_description_id_arr = [];
        $this->db->from('game_description');
        // $this->db->select('*');
        $this->db->select('id as game_description_id');
        if(!empty($gamePlatformIds) && is_array($gamePlatformIds)){
            $this->db->where_in('game_platform_id', $gamePlatformIds);
        }
        if(!empty($gameTypeIds) && is_array($gameTypeIds)){
            $this->db->where_in('game_type_id', $gameTypeIds);
        }
        if(!empty($gameTagIds) && is_array($gameTagIds)){
            $this->db->where_in('game_tag_id', $gameTagIds);
        }
        if(!empty($gamedescriptionIds) && is_array($gamedescriptionIds)){
            $this->db->where_in('id', $gamedescriptionIds);
        }
        $_game_description_id_result_arr = $this->db->get()->result_array();
        $game_description_id_result_arr = array_column($_game_description_id_result_arr, 'game_description_id');
        $all_game_description_id_arr = array_merge($all_game_description_id_arr, $game_description_id_result_arr);

        return $all_game_description_id_arr;
    }

    public function getEventPlayers($event_id, $onlyUnSettled = false, $player_id_to_pay = null) {
        $this->db->from('tournament_player_apply_records');
        $this->db->select('playerId, eventScore, bonusAmount, createdAt as applyTime');
        $this->db->select('external_uniqueid');
        $this->db->select('tournamentId, eventId');
        $this->db->where('eventId', $event_id);
        if($onlyUnSettled) {
            $this->db->where('isSettled', 0);
        }
        if($player_id_to_pay) {
            $this->db->where('playerId', $player_id_to_pay);
        }
        $query = $this->db->get();
        return $this->getMultipleRowArray($query);
    }

    public function getPlayersHasScore($event_id) {
        $this->db->select('(@row_number:=@row_number + 1) AS playerRank', false);
        $this->db->select('playerId, eventScore, bonusAmount');
        // $this->db->select('createdAt as applyTime');
        $this->db->select('external_uniqueid');
        $this->db->select('tournamentId, eventId');
        $this->db->where('eventId', $event_id);
        $this->db->where('eventScore >', 0);
        $this->db->order_by('eventScore', 'DESC');
        $this->db->order_by('lastSyncTime', 'ASC');
        $this->db->from('tournament_player_apply_records CROSS JOIN (SELECT @row_number := 0) AS t');
        $query = $this->db->get();
        return $this->getMultipleRowArray($query);
    }

    public function updatePlayerRank($event_id, $player_id, $rank) {
        $this->db->where('eventId', $event_id);
        $this->db->where('playerId', $player_id);
        $this->db->update('tournament_player_apply_records', ['playerRank' => $rank]);
        return $this->db->affected_rows();
    }

    public function countPlayerScore($player_id, $gameDescriptionIds, $from, $to, $table = 'total_player_game_minute', $countTotalScore = false) { 
        
        $this->db->select(' total_player_table.player_id, SUM(total_player_table.real_betting_amount) total_score');
        
        if($gameDescriptionIds) {
            $this->db->where_in("total_player_table.game_description_id", $gameDescriptionIds);
        } 

        switch($table) {
            case 'total_player_game_minute':
                $this->db->select('max(total_player_table.date_minute) lastbet');
                $this->db->from('total_player_game_minute as total_player_table');
                $this->db->where("date_minute >=", $from);
                $this->db->where("date_minute <=", $to);
                break;
        }
        if(!$countTotalScore) {
            $this->db->where("total_player_table.player_id", $player_id);
        }
        $qry = $this->db->get();
        $result = $this->getOneRowArray($qry);
        $this->utils->printLastSQL();
		return $result;
    }

    public function updatePlayerScore($event_id, $player_id, $eventScore, $lastbetAt, $doSettle = false) {

        $updateData = [
            'eventScore' => $eventScore,
            'lastSyncTime' => $lastbetAt
        ];
        if($doSettle) {
            $updateData['isSettled'] = $doSettle ? 1 : 0;
        }
        $this->db->where('eventId', $event_id);
        $this->db->where('playerId', $player_id);
        $this->db->update('tournament_player_apply_records',  $updateData);
        return $this->db->affected_rows();
    }

    public function hasSettledApplyRecords($event_id) {
        $this->db->from('tournament_player_apply_records');
        $this->db->select('id');
        $this->db->where('eventId', $event_id);
        $this->db->where('isSettled', 1);
        $query = $this->db->get();
        return $query->num_rows() > 0;
    }

    
    public function resetSettleRecords($event_id, $debug_mode = true, $player_id = null) {
        $this->db->where('eventId', $event_id);
        if($player_id) {
            $this->db->where('playerId', $player_id);
        }
        $this->db->update('tournament_player_apply_records', [
            'eventScore' => 0,
            'isSettled' => 0,
            'playerRank' => 0,
            'lastSyncTime' => null,
            'bonusAmount' => 0,
            'applyTransId' => null,
            'bonusTransId' => null,
            'WithdrawalCondictionId' => null,
            'isReleased' => 0,
            'releaseTime' => null,
        ]);
        if(!$debug_mode) {
            $this->db->where('applyTransId', null);
            $this->db->where('bonusTransId', null);
            $this->db->where('WithdrawalCondictionId', null);
            $this->db->where('isReleased', 0);
        }
        return $this->db->affected_rows();
    }

    public function payEvent($event_id, $playerIdToPay = null, $debug_mode = false)
    {
        $this->utils->debug_log('pay start event', $event_id, $playerIdToPay, 'debug_mode', $debug_mode);
        $this->load->model(array('transactions', 'withdraw_condition'));
        $result = true;
        $currentPlayerId = $playerIdToPay == 0 ? null : $playerIdToPay;
        $event = $this->getEventById($event_id, true);
        $playerRows = $this->getEventPlayers($event_id, false, $currentPlayerId);
        $withdraw_condition_bet_times = $event['withdrawalConditionTimes'];
        $lockFailedRows = [];
        $superadmin = $this->users->getSuperAdmin();
        $dry_run = false;
        if (!empty($playeeRows)) {

            $this->payRows($playerRows, $withdraw_condition_bet_times, $superadmin, $dry_run, $lockFailedRows, $debug_mode);

        } else {
            $this->utils->debug_log('cashback is empty');
        }

        // while (!empty($lockFailedRows)) {

        //     $this->utils->debug_log('try pay cashback again', $lockFailedRows);
        //     sleep(2);
        //     $lockFailedAgainRows = [];
        //     $this->payRows($lockFailedRows, $withdraw_condition_bet_times, $superadmin, $dry_run, $lockFailedAgainRows);

        //     $this->utils->debug_log('try pay cashback again again', $lockFailedAgainRows);

        //     $lockFailedRows = $lockFailedAgainRows;

        // }

        return $result;
    }

    protected function payRows($rows, $withdraw_condition_bet_times, $superadmin, $dry_run, &$lockFailedRows=[], $debug_mode=false){
        foreach ($rows as $row) {
            $this->utils->debug_log('payRows', $row, 'debug_mode', $debug_mode);
            $playerId = 

            $controller = $this;
			// $rlt = $this->lockAndTransForPlayerBalance($playerId, function()
			// 	use ($controller, &$message, $playerId, $commonMaxBonus, $row, $debug_mode, $dry_run,
			// 		$withdraw_condition_bet_times, $min_cashback_amount, $superadmin) {

            //         });
        }
    }


    public function getTournamentGameListData($params, $db=null){
		if(empty($db)){
			$db=$this->db;
		}
        # query
        $table = 'game_description';
        $select = 'game_description.*, game_description.id game_description_id, external_system.system_code game_api_system_code, external_system.status game_api_status, game_type.game_type game_type_name, external_system.maintenance_mode as under_maintenance';
        $where = "game_description.`status` = 1 AND game_description.`flag_show_in_site` = 1 AND game_type.`game_type` not like '%unknown%' ";

        $group_by = 'game_description.id';
        $order_by = null;

        $joins = [
            'external_system'=>'external_system.id=game_description.game_platform_id',
            'game_type'=>'game_type.id=game_description.game_type_id',
        ];

        if(isset($params['gameTypeCode']) && !empty($params['gameTypeCode'])){
            $where .=  " AND game_type.game_type_code = '".(string)$params['gameTypeCode']."'";
        }

        if(isset($params['gamePlatformId']) && !empty($params['gamePlatformId'])){
            $where .=  " AND game_description.game_platform_id = ".(int)$params['gamePlatformId'];
        }

        if(isset($params['virtualGamePlatform']) && !empty($params['virtualGamePlatform'])){
            $where .=  " AND game_description.game_platform_id = ".(int)$params['virtualGamePlatform'];
        }

        if(isset($params['gameName']) && !empty($params['gameName'])){
            $where .=  " AND game_description.game_name like '%".$params['gameName']."%'";
        }
        if(isset($params['mobileEnable']) && $params['mobileEnable']){
            $where .=  " AND game_description.mobile_enabled = 1";
        }
        if(isset($params['pcEnable']) && $params['pcEnable']){
            $where .=  " AND (game_description.flash_enabled = 1 OR game_description.html_five_enabled = 1)";
        }

        if(isset($params['gameDescriptionId']) && !empty($params['gameDescriptionId'])){
            $gameDescriptionId = $params['gameDescriptionId'];
            if(is_array($gameDescriptionId)){
                $where  .= ' AND game_description.id IN ('.implode(',', $gameDescriptionId).')';
            }else{
                $where  .= ' AND game_description.id ='.$gameDescriptionId;
            }
        }

        # pagination
        $page = isset($params['pageNumber'])?(int)$params['pageNumber']:1;
        $limit = isset($params['sizePerPage']) || !empty($params['sizePerPage'])?(int)$params['sizePerPage']:15;

        if(isset($params['mobile'])){
            if($params['mobile']=='true'){
                $where .=  " AND game_description.mobile_enabled = 1";
            }else{
                $where .=  " AND game_description.mobile_enabled = 0";
            }
        }

        if(isset($params['web'])){
            if($params['web']=='true'){
                $where .=  " AND game_description.html_five_enabled = 1";
            }else{
                $where .=  " AND game_description.html_five_enabled = 0";
            }
        }

        $sortColumnList = [
			'gameName'=>'game_description.english_name',
			'gamePlatformId'=>'game_description.game_platform_id',
			'virtualGamePlatform'=>'game_description.game_platform_id',
			'gameTypeCode'=>'game_type.game_type_code',
			'gameOrder' => 'game_description.game_order'
		];

        $gameTags = [];
        if(isset($params['gameTags']) && !empty($params['gameTags'])){

            # update where add tag code
            $gameTags = $params['gameTags'];
            if(is_array($gameTags)){
                $gameTagsImplode = implode("','", $gameTags);
                $where .=  " AND game_tags.tag_code in ('".$gameTagsImplode."')";
            }else{
                $where .=  " AND game_tags.tag_code = '".(string)$gameTags."'";
            }

            # update join add tags table
            $joins['game_tag_list'] = 'game_tag_list.game_description_id=game_description.id';
            $joins['game_tags'] = 'game_tags.id=game_tag_list.tag_id';

			// OGP-31311 to use game tag order if game tags is set
			$sortColumnList['gameOrder'] = 'game_tag_list.game_order';
        }

        // process sort
        if(isset($params['sort'])){
            preg_match_all('/[A-Za-z0-9]+/', $params['sort'], $matches);

            if( isset($matches[0]) && isset($matches[0][0]) && isset($matches[0][1])){
                $sortColumn = $matches[0][0];
                //echo $sortColumn;return;
                //var_dump(array_key_exists($sortColumn, $sortColumnList));
                if(!array_key_exists($sortColumn, $sortColumnList)){
                    $sortColumn = '';
                }
                $sortType = strtolower($matches[0][1]);
                if(!in_array($sortType, ["asc","desc"])){
                    $sortType = '';
                }

                if(!empty($sortColumn)&&!empty($sortType)){
                    $order_by = $sortColumnList[$sortColumn].' ' . $sortType;
                }
            }
        }

        // process sort
        if(isset($params['sortBy'])){
			$sortColumn = '';
			$sortType = 'ASC';
            if(isset($params['sortBy']['sortKey'])&&!empty($params['sortBy']['sortKey'])){
				$sortColumn = $params['sortBy']['sortKey'];
			}

			if(!array_key_exists($sortColumn, $sortColumnList)){
				$sortColumn = '';
			}

            if(isset($params['sortBy']['sortType'])&&!empty($params['sortBy']['sortType'])){
				$sortType = $params['sortBy']['sortType'];
			}

			if(!in_array($sortType, ["asc","desc"])){
				$sortType = '';
			}

			if(!empty($sortColumn)&&!empty($sortType)){
				$order_by = $sortColumnList[$sortColumn].' ' . $sortType;
				if($this->CI->utils->getConfig('api_gamelist_game_order_zero_set_to_last') && $sortColumn == "gameOrder"){
					$order_by = "{$sortColumnList[$sortColumn]} = 0, " . $sortColumnList[$sortColumn].' ' . $sortType;
				}
			}
        }

		$except_game_api_list = $this->CI->utils->getConfig('except_game_api_list');

		#OGP-31876
		if(!empty($except_game_api_list)){
			$where .=  " AND game_description.game_platform_id NOT IN (".implode(",", $except_game_api_list) . ")";
		}
        $result = $this->getDataWithPaginationData($table, $select, $where, $joins, $limit, $page, $group_by, $order_by, $db);

        return $result;

    }

    public function getTournamentApplyHistoryPagination($playerId, $limit, $page)
    {
        $result = $this->getDataWithAPIPagination('tournament_player_apply_records', function() use($playerId) {
            $this->db->select(
                'tournament_player_apply_records.id as applyId,
                tournament_player_apply_records.tournamentId,
                tournament.tournamentName,
                tournament_schedule.id as scheduleId,
                tournament_schedule.scheduleName,
                tournament_player_apply_records.eventId,
                tournament_event.eventName,
                tournament_player_apply_records.createdAt as applyTime,
                tournament_schedule.distributionTime,
                tournament_schedule.bonusType,
                tournament_player_apply_records.bonusAmount,
                tournament_schedule.contestStartedAt,
                tournament_schedule.contestEndedAt,
            ');//tournament_player_apply_records.rank,
            $this->db->join('tournament_event', 'tournament_event.id = tournament_player_apply_records.eventId', 'left');
            $this->db->join('tournament_schedule', 'tournament_schedule.id = tournament_event.scheduleId', 'left');
            $this->db->join('tournament', 'tournament.id = tournament_schedule.tournamentId', 'left');
            $this->db->where('tournament_player_apply_records.playerId', $playerId);
        }, $limit, $page);
        return $result;
    }

    public function getTournamentApplySummary($playerId)
    {
        $this->db->select(
            'sum(bonusAmount) as totalBonusAmount,
            count(*) as totalApplyCount,
            max(bonusAmount) as topBonusAmount,
        ');
        $this->db->from('tournament_player_apply_records');
        $this->db->where('playerId', $playerId);
        $query = $this->db->get();
        return $this->getOneRowArray($query);
    }

    public function createTournament($data){
        $result = $this->db->insert($this->tableName, $data);
        $tournamentId = $this->db->insert_id();
		if ($result) {
			return $tournamentId;
		} else {
			return false;
		}
    }

    public function createTournamentGame($data){
        $result = $this->db->insert('tournament_game', $data);
        $tournamentGameId = $this->db->insert_id();
		if ($result) {
			return $tournamentGameId;
		} else {
			return false;
		}
    }

    public function createTournamentSchedule($data){
        $result = $this->db->insert('tournament_schedule', $data);
        $scheduleId = $this->db->insert_id();
        if ($result) {
			return $scheduleId;
		} else {
			return false;
		}
    }

    public function createTournamentEvent($data){
        $result = $this->db->insert('tournament_event', $data);
        $eventId = $this->db->insert_id();
        if ($result) {
			return $eventId;
		} else {
			return false;
		}
    }

    public function createTournamentRank($data){
        $result = $this->db->insert('tournament_rank', $data);
        $eventId = $this->db->insert_id();
        if ($result) {
			return $eventId;
		} else {
			return false;
		}
    }

    public function updateTournament($data, $tournamentId){
        $this->db->where('id', $tournamentId);
        $result = $this->db->update($this->tableName, $data);
        return $result;
    }
    public function updateTournamentSchedule($data, $scheduleId){
        $this->db->where('id', $scheduleId);
        $result = $this->db->update('tournament_schedule', $data);
        return $result;
    }

    public function updateTournamentEvent($data, $eventId){
        $this->db->where('id', $eventId);
        $result = $this->db->update('tournament_event', $data);
        return $result;
    }


    public function softDeleteTournament($tournamentId){
        $this->db->where('id', $tournamentId);
        $data = [
            'status' => self::STATUS_DELETE,
        ];
        $result = $this->db->update($this->tableName, $data);
        return $result;
    }

    public function softDeleteTournamentSchedule($scheduleId){
        $this->db->where('id', $scheduleId);
        $data = [
            'status' => self::STATUS_DELETE,
            'deletedBy' => $this->userId,
            'deletedAt' => date('Y-m-d H:i:s')
        ];
        $result = $this->db->update('tournament_schedule', $data);
        return $result;
    }
    
    public function softDeleteTournamentEvent($eventId){
        $this->db->where('id', $eventId);
        $data = [
            'status' => self::STATUS_DELETE,
            'deletedBy' => $this->userId,
            'deletedAt' => date('Y-m-d H:i:s')
        ];
        $result = $this->db->update('tournament_event', $data);
        return $result;
    }

    public function getTournamentEventRank($event_id,$limit, $page, $direction = "DESC", $hasRank = true){
        $result = $this->getDataWithAPIPagination('tournament_player_apply_records', function() use($event_id, $direction, $hasRank) {
        $this->db->select('tournament_player_apply_records.eventScore,
        tournament_player_apply_records.bonusAmount as bonus,
        tournament_player_apply_records.playerId,
        player.username, tournament_player_apply_records.playerRank rank');
        // $this->db->select('tournament_player_apply_records.rank,');
        $this->db->join('player', 'tournament_player_apply_records.playerId = player.playerId', 'left');
        $this->db->where('eventId', (int)$event_id);
        if($hasRank){
            $this->db->where('tournament_player_apply_records.playerRank >', 0);
        }
        $this->db->order_by('tournament_player_apply_records.eventScore',$direction);
        }, $limit, $page);

        return $result;
    }

}
