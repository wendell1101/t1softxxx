<?php
use League\CLImate\TerminalObject\Basic\Json;
require_once dirname(__FILE__) . '/base_model.php';

// //login
// define('TRACKINGEVENT_SOURCE_TYPE_LAST_LOGIN', 10);

// //deposit
// define('TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT', 20);
// define('TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_SUCCESS', 21);
// define('TRACKINGEVENT_SOURCE_TYPE_FIRST_DEPOSIT_SUCCESS', 22);

// //withdrawal
// define('TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL', 30);

// //vip
// define('TRACKINGEVENT_SOURCE_TYPE_VIP_UPGRADE', 40);
// define('TRACKINGEVENT_SOURCE_TYPE_VIP_DOWNGRADE', 41);

// //reg
// define('TRACKINGEVENT_SOURCE_TYPE_REGISTER_COMMOM', 50);
// define('TRACKINGEVENT_SOURCE_TYPE_REGISTER_LINE', 60);
// define('TRACKINGEVENT_SOURCE_TYPE_REGISTER_FACEBOOK', 70);
// define('TRACKINGEVENT_SOURCE_TYPE_REGISTER_GOOGLE', 80);

/**
 * Summary of Player_trackingevent
 * @property Utils $utils
 */
class Player_trackingevent extends BaseModel
{

    const TRACKINGEVENT_SOURCE_TYPE_PAGE_VIEW = 999;
    const TRACKINGEVENT_SOURCE_TYPE_LAST_LOGIN = 10;
    const TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT = 20;
    const TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_SUCCESS = 21;
    const TRACKINGEVENT_SOURCE_TYPE_FIRST_DEPOSIT_SUCCESS = 22;
    const TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL = 30;
    const TRACKINGEVENT_SOURCE_TYPE_VIP_UPGRADE = 40;
    const TRACKINGEVENT_SOURCE_TYPE_VIP_DOWNGRADE = 41;
    const TRACKINGEVENT_SOURCE_TYPE_REGISTER_COMMOM = 50;
    const TRACKINGEVENT_SOURCE_TYPE_REGISTER_LINE = 51;
    const TRACKINGEVENT_SOURCE_TYPE_REGISTER_FACEBOOK = 52;
    const TRACKINGEVENT_SOURCE_TYPE_REGISTER_GOOGLE = 53;
    const TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_FAILED = 23;
    const TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_SUCCESS = 31;
    const TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_FAILED = 32;
    const TRACKINGEVENT_SOURCE_TYPE_PROMO_PENDING = 60;
    const TRACKINGEVENT_SOURCE_TYPE_PROMO_APPROVED = 61;
    const TRACKINGEVENT_SOURCE_TYPE_PROMO_REJECTED = 62;
    const TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED = 24;
    const TRACKINGEVENT_SOURCE_TYPE_SENT_MESSAGE_SUCCESS = 70;
    const TRACKINGEVENT_SOURCE_TYPE_ADD_ANNOUNCEMENT_MESSAGE = 71;
    const TRACKINGEVENT_SOURCE_TYPE_PLAY = 72;


    const TRACKINGEVENT_SOURCE_TYPE_R1 = 91; // first deposit custom event
    const TRACKINGEVENT_SOURCE_TYPE_D1 = 92; // custom event deposit greater than 100
    const TRACKINGEVENT_SOURCE_TYPE_D2 = 93; // custom event deposit greater than 1000
    const TRACKINGEVENT_SOURCE_TYPE_D3 = 94; // custom event deposit greater than 5000

    const TRACKINGEVENT_SOURCE_TYPE_MISSION_JOIN_TELEGRAM = 100;
    const TRACKINGEVENT_SOURCE_TYPE_QUEST_SHARE_SOCIAL_MEDIA = 101;

    private $tableName = 'player_trackingevent';
    private $table_trackinginfo = 'player_tracking_info';
    private $table_player_tracking_report = 'player_tracking_report';

    /**
     * Summary of __construct
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Summary of getSourceTypeCode
     * @param mixed $source_type
     * @return mixed
     */
    private function getSourceTypeCode($source_type){
        $eventCode = [
            'TRACKINGEVENT_SOURCE_TYPE_PAGE_VIEW' => player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_PAGE_VIEW,
            'TRACKINGEVENT_SOURCE_TYPE_LAST_LOGIN'  => player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_LAST_LOGIN,
            'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT' =>player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT,
            'TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_SUCCESS' =>player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_SUCCESS,
            'TRACKINGEVENT_SOURCE_TYPE_FIRST_DEPOSIT_SUCCESS' =>player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_FIRST_DEPOSIT_SUCCESS,
            'TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL' =>player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL,
            'TRACKINGEVENT_SOURCE_TYPE_VIP_UPGRADE' =>player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_VIP_UPGRADE,
            'TRACKINGEVENT_SOURCE_TYPE_VIP_DOWNGRADE' =>player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_VIP_DOWNGRADE,
            'TRACKINGEVENT_SOURCE_TYPE_REGISTER_COMMOM' =>player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_REGISTER_COMMOM,
            'TRACKINGEVENT_SOURCE_TYPE_REGISTER_LINE' =>player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_REGISTER_LINE,
            'TRACKINGEVENT_SOURCE_TYPE_REGISTER_FACEBOOK' =>player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_REGISTER_FACEBOOK,
            'TRACKINGEVENT_SOURCE_TYPE_REGISTER_GOOGLE' =>player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_REGISTER_GOOGLE,
            'TRACKINGEVENT_SOURCE_TYPE_MISSION_JOIN_TELEGRAM' => player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_MISSION_JOIN_TELEGRAM,
            'TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_FAILED' => Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_FAILED,
            'TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_SUCCESS' => Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_SUCCESS,
            'TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_FAILED' => Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_FAILED,
            'TRACKINGEVENT_SOURCE_TYPE_PROMO_PENDING' => Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_PROMO_PENDING,
            'TRACKINGEVENT_SOURCE_TYPE_PROMO_APPROVED' => Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_PROMO_APPROVED,
            'TRACKINGEVENT_SOURCE_TYPE_PROMO_REJECTED' => Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_PROMO_REJECTED,
            'TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED' => Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_CREATE_DEPOSIT_FAILED,
            'TRACKINGEVENT_SOURCE_TYPE_SENT_MESSAGE_SUCCESS' => Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_SENT_MESSAGE_SUCCESS,
            'TRACKINGEVENT_SOURCE_TYPE_ADD_ANNOUNCEMENT_MESSAGE' => Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_ADD_ANNOUNCEMENT_MESSAGE,
            'TRACKINGEVENT_SOURCE_TYPE_PLAY' => Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_PLAY,
        ];

        return $this->utils->safeGetArray($eventCode, $source_type, false);
    }

    /**
     * Summary of hasNotify
     * @param mixed $player_id
     * @return array|bool
     */
    public function hasNotify($player_id)
    {
        return $this->getNotify($player_id);
    }

    /**
     * Summary of getNotify
     * @param mixed $player_id
     * @return array|bool
     */
    public function getNotify($player_id, $date_from = null, $date_to = null)
    {
        $condition = [
            'player_id' => $player_id,
            'is_notify' => 0
        ];

        if (!empty($date_from) && !empty($date_to)) {
            $condition['created_at >='] = $date_from;
            $condition['created_at <='] = $date_to;
        }
        
        $query = $this->db->get_where($this->tableName, $condition);

        $result = $this->getMultipleRowArray($query);

        if (empty($result)) {
            return FALSE;
        }

        $list = [];
        foreach ($result as $data) {
            $list[$data['id']] = $data;
        }

        return $list;
    }

    /**
     * Summary of getNotifyBySource
     * @param mixed $player_id
     * @param mixed $source_type
     * @return array|bool
     */
    public function getNotifyBySource($player_id, $source_type)
    {
        $query = $this->db->get_where($this->tableName, [
            'player_id' => $player_id,
            'source_type' => $source_type,
        ]);

        $result = $this->getMultipleRowArray($query);

        if (empty($result)) {
            return FALSE;
        }

        $list = [];
        foreach ($result as $data) {
            $list[$data['id']] = $data;
        }

        return $list;
    }

    public function getNotifyBySourceAndDate($player_id, $source_type, $date_from, $date_to)
    {
        $condition = [
            'player_id' => $player_id,
            'source_type' => $source_type,
            'created_at >=' => $date_from,
            'created_at <=' => $date_to,
        ];

        $query = $this->db->get_where($this->tableName, $condition);

        $result = $this->getMultipleRowArray($query);

        if (empty($result)) {
            return FALSE;
        }

        $list = [];
        foreach ($result as $data) {
            $list[$data['id']] = $data;
        }

        return $list;
    }

    public function getNotifyBySourceAndNotify($player_id, $source_type, $is_notify = 0)
    {
        $query = $this->db->get_where($this->tableName, [
            'player_id' => $player_id,
            'source_type' => $source_type,
            'is_notify' => $is_notify,
        ]);

        $result = $this->getMultipleRowArray($query);

        if (empty($result)) {
            return FALSE;
        }

        return $result;
    } 

    /**
     * Summary of createNotify
     * @param mixed $player_id
     * @param mixed $source_type
     * @param mixed $params
     * @return object
     */
    public function createNotify($player_id, $source_type, $params)
    {
        $data = [
            'player_id' => $player_id,
            'source_type' => $this->getSourceTypeCode($source_type),
            'params' => json_encode($params),
            'is_notify' => 0,
            'notify_time' => NULL,
            'created_at' => $this->utils->getNowForMysql(),
            'updated_at' => $this->utils->getNowForMysql(),
        ];
        $result = $this->db->insert($this->tableName, $data);

        return ($result);
    }

    /**
     * Summary of createSettledNotify
     * @param mixed $player_id
     * @param mixed $source_type
     * @param mixed $params
     * @return object
     */
    public function createSettledNotify($player_id, $source_type, $params)
    {
        $data = [
            'player_id' => $player_id,
            'source_type' => $source_type,
            'params' => json_encode($params),
            'is_notify' => 1,
            'notify_time' => $this->utils->getNowForMysql(),
            'created_at' => $this->utils->getNowForMysql(),
            'updated_at' => $this->utils->getNowForMysql(),
        ];
        $result = $this->db->insert($this->tableName, $data);

        return ($result);
    }

    /**
     * Summary of setIsNotify
     * @param mixed $player_id
     * @param mixed $notify_id
     * @return bool
     */
    public function setIsNotify($player_id, $notify_id)
    {
        $where = [
            'player_id' => $player_id
        ];
        if (!empty($notify_id)) {
            $where['id'] = $notify_id;
        }

        $data = [
            'is_notify' => 1,
            'notify_time' => $this->utils->getNowForMysql(),
            'updated_at' => $this->utils->getNowForMysql(),
        ];

        $result = $this->db->update($this->tableName, $data, $where);

        return ($result) ? TRUE : FALSE;
    }

    /**
     * Summary of clearNotify
     * @param mixed $player_id
     * @param mixed $notify_id
     * @return bool
     */
    public function clearNotify($player_id, $notify_id = NULL)
    {
        $where = [
            'player_id' => $player_id
        ];
        if (!empty($notify_id)) {
            $where['id'] = $notify_id;
        }

        $result = $this->db->delete($this->tableName, $where);

        return ($result) ? TRUE : FALSE;
    }

    /**
     * Summary of addTrackingInfo
     * @param mixed $string
     * @param array $params
     * @param string $token
     * @return 
     */
    public function addTrackingInfo($platform_id, $params, $token){
        if ($token) {
            //check unique
            $this->db->select('token')->from($this->table_trackinginfo)->where('token', $token);
            if ($this->runExistsResult()) {
                $this->utils->debug_log('exists token', $token);
                return false;
            }
        }
        $data = [
            'platform_id' => $platform_id,
            'extra_info' => json_encode($params),
            'token' => $token,
            'ref_domain' => $this->utils->safeGetArray($params, 'ref_domain'),
            'current_domain' => $this->utils->safeGetArray($params, 'current_domain'),
            'created_at' => $this->utils->getNowForMysql(),
            'updated_at' => $this->utils->getNowForMysql(),
        ];
        $result = $this->db->insert($this->table_trackinginfo, $data);
        return ($result);
    }
    /**
     * Summary of updateTrackingInfo
     * @param mixed $player_id
     * @return void
     */
    public function updateTrackingInfo($player_id, $token){
        $where = [
            'player_id' => null,
            'token' => $token
        ];

        $data = [
            'player_id' => $player_id,
        ];

        $result = $this->db->update($this->table_trackinginfo, $data, $where);
        return ($result);
    }

    public function getTrackingInfoByToken($token)
    {
        $query = $this->db->get_where($this->table_trackinginfo, [
            'token' => $token
        ]);

        $result = $this->getOneRowArray($query);

        if (empty($result)) {
            return array();
        }
        return $result;
    }

    public function getTrackingInfoByPlayerId($player_id)
    {
        $query = $this->db->get_where($this->table_trackinginfo, [
            'player_id' => $player_id
        ]);

        $result = $this->getOneRowArray($query);

        if (empty($result)) {
            return array();
        }
        return $result;
    }


    // tracking report
    public function insertReportRecord($event_name, $recid, $playerId=null, $tracking_extra_info, $result_postBack, $external_id) {

        $data = [
            'event_name' => $event_name,
            'player_id' => $playerId,
            'platform_id' => $recid,
            'external_id' => $external_id,
            'extra_info' => json_encode($tracking_extra_info),
            'response_result' => json_encode($result_postBack),
        ];
        $result = $this->db->insert($this->table_player_tracking_report, $data);
        return ($result);
    }

    public function checkRecordExist($event_name, $external_id, $playerId)
    {
        $query = $this->db->get_where($this->table_player_tracking_report, [
            'event_name' => $event_name,
            'player_id' => $playerId,
            'external_id' => $external_id,
        ]);

        $result = $this->getOneRowArray($query);
        return !empty($result);
    }

}
