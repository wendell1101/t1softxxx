<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Tracking_platform_model
 * @property authentication $authentication
 */
class Tracking_platform_model extends BaseModel{
    /**
     * Status
     */
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DELETE = 2;

    /**
     * TrackingPlatformType
     */
    const TRACKING_PLATFORMS = [
        self::PLATFORM_TYPE_GOOGLE, 
        self::PLATFORM_TYPE_META, 
        self::PLATFORM_TYPE_APPSFLYER, 
        self::PLATFORM_TYPE_KWAI
    ];
    const PLATFORM_TYPE_GOOGLE = 1;
    const PLATFORM_TYPE_META = 2;
    const PLATFORM_TYPE_APPSFLYER = 3;
    const PLATFORM_TYPE_KWAI = 4;
    const PLATFORM_TYPE_ADJUST = 5;
    
    /**
     * platformScope
     */
    const PLATFORM_SCOPE_GLOBAL = 1;
    const PLATFORM_SCOPE_DOMAIN = 2;

    private $tableName = 'tracking_platform';

    private $userId = '';

    public function __construct(){
        $this->load->library(['authentication', 'tournament_lib']);
        $this->userId = !empty($this->authentication->getUserId())? $this->authentication->getUserId() : 1;
        parent::__construct();
    }

    public function setMainTableName($tableName){
        $this->tableName = $tableName;
    }

    public function createTrackingPlatform($data){
        $data['createdBy'] = $this->userId;
        $data['createdAt'] = $this->utils->getNowForMysql();
        $data['status'] = self::STATUS_ACTIVE;
        $this->db->insert($this->tableName, $data);
        return $this->db->insert_id();
    }

    public function getTrackingPlatformListPagination($limit, $page, $conditions = [], $orderby = [], $direction = "desc"){
        $result = $this->getDataWithAPIPagination($this->tableName, function() use($conditions, $orderby, $direction) {
            $this->db->select('*');

            if(!empty($conditions['platformType'])){
                $this->db->where('platformType', $conditions['platformType']);
            }

            $this->db->where('status', self::STATUS_ACTIVE);

            if(!empty($orderby)){
                if(!in_array($direction, ["asc","desc"])){
                    $direction = '';
                }
                $this->db->order_by(implode(",", $orderby), $direction);
            }
        }, $limit, $page);
        return $result;
    }

    public function getTrackingPlatformById($platformId, $platformType = null){
        $this->db->from($this->tableName);
        $this->db->select('*');
        $this->db->where('platformId', $platformId);
        if(!empty($platformType)){
            $this->db->where('platformType', $platformType);
        }
        $query = $this->db->get();
        return $this->getOneRowArray($query);
    }

    public function updateTrackingPlatform($data, $platformId){
        $this->db->where('platformId', $platformId);
        $this->db->update($this->tableName, $data);
        if ($this->db->affected_rows() == '1') {
            return TRUE;
        }
        return FALSE;
    }

    public function deleteTrackingPlatform($platformId){
        $this->db->where('platformId', $platformId);
        $this->db->update($this->tableName, [
            'deletedAt' => $this->utils->getNowForMysql(),
            'deletedBy' => $this->userId,
            'status' => self::STATUS_DELETE
        ]);

        if ($this->db->affected_rows() > 0) {
            return TRUE;
        }
        return FALSE;
    }

    public function checkExistTrackingPlatform($platformType){
        $this->db->from($this->tableName);
        $this->db->select('platformId');
        $this->db->where('platformType', $platformType);
        $this->db->where('deletedAt IS NULL');
        $this->db->where('status', self::STATUS_ACTIVE);
        return $this->runExistsResult();
    }

    public function getTrackingPlatformByPlatformType($platformType){
        $this->db->from($this->tableName);
        $this->db->select('trackingId,token');
        $this->db->where('platformType', $platformType);
        $this->db->where('deletedAt IS NULL');
        $this->db->where('status', self::STATUS_ACTIVE);
        $query = $this->db->get();
        $this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
        
        return $this->getMultipleRowArray($query);
    }
}
