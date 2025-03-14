<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Class Player_center_api_domains
 *
 *
 */
class Player_center_api_domains extends BaseModel {

    const ALLOWED = 1;
    const BLOCKED = 2;

    const SUBSITE_BLOCKED = 1;
    const SUBSITE_ALLOWED = 2;

    private $tableName = 'player_center_api_domain';

    public function __construct() {
        parent::__construct();
    }

    public function getPlayerCenterApiDomainList( $is_export = false ) {
        $this->db->select('player_center_api_domain.*');
        $this->db->from($this->tableName);
        $query = $this->db->get()->result();
        if( $is_export ){
            return $query;
        }
        $data['data'] = $query;
        return $data;
    }

    /**
     * detail: Inserts data to player_center_api_domain
     *
     * @param array $data
     * @return Boolean
     */
    public function addPlayerCenterApiDomain($params) {
        $data = array(
            'domain' => $params['domain'],
            'note' => $params['note'],
            'status' => $params['status'],
            'created_by' => $params['created_by'],
            'updated_by' => $params['updated_by']
        );
        return $this->insertData('player_center_api_domain', $data);
    }

    /**
     * detail: edit domain data by ids to player_center_api_domain table
     *
     * @param array $data array
     * @param int domainIds
     * @return Boolean
     */
    public function editPlayerCenterApiDomain($data, $domainId) {
        return $this->updateData('id', $domainId, $this->tableName, $data);
    }

    public function deleteDomains($domainIds) {
        return $this->runBatchDeleteByIdWithLimit($this->tableName, $domainIds);
    }

    public function blockedDomains($domainIds, $updatedby) {
        $this->db->set('status', self::BLOCKED);
        $this->db->set('updated_by', $updatedby);
        $this->db->where_in('id', $domainIds);
        return $this->runAnyUpdate($this->tableName);
    }

    public function unBlockedDomains($domainIds, $updatedby) {
        $this->db->set('status', self::ALLOWED);
        $this->db->set('updated_by', $updatedby);
        $this->db->where_in('id', $domainIds);
        return $this->runAnyUpdate($this->tableName);
    }

    public function queryPlayerCenterApiDomainList($status = null ) {
        $this->db->select('domain');
        $this->db->from($this->tableName);
        if( is_null($status) ){
            $status = self::ALLOWED;
        }
        $this->db->where('status', $status);
        return $this->runMultipleRowArray();
    }

}

/* End of file player_center_api_domains.php *
/* Location: ./application/models/player_center_api_domains.php */
