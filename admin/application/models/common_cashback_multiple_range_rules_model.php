<?php
require_once dirname(__FILE__) . '/base_model.php';

/**
 */
class Common_cashback_multiple_range_rules_model extends BaseModel {
    private $tableName = 'common_cashback_multiple_range_rules';
    const COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG = 'game_tag'; // ref. to Common_Cashback_multiple_rules::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG
    public function __construct() {
        parent::__construct();
    }


    public function getAllRulesByTplIdLimitGameTagList($tpl_id, $game_tag_id_list = []){
        $where = [];
        if( ! empty($game_tag_id_list) ){
            $this->CI->load->library(['og_utility']);
            $this->CI->load->model('game_tags');
            $allGameTagsRows = $this->CI->game_tags->getAllGameTagsWithPagination(null, null);
            $game_tag_list = $this->og_utility->array_pluck($allGameTagsRows, 'id');
            $game_tag_list = array_unique($game_tag_list);
        }
        $sql = "SELECT * FROM $this->tableName ";
        $sql_params =[];
        $or_sentence_list = [];

        if( ! empty($game_tag_list) ){
            $or_sentence_list[] = '( type = "'. static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG. '" AND type_map_id in ('. implode(',', $game_tag_list). ') )';
        }

        $or_sentence = implode(' OR ', $or_sentence_list);

        if( ! empty($or_sentence_list) ){
            $sql .= 'WHERE ';
        }
        if( ! empty($or_sentence) ){
            $sql .= $or_sentence;
        }
        $query =$this->db->query($sql, $sql_params);
        $data = $this->getMultipleRowArray($query);

        if(empty($data)){
            return FALSE;
        }

        return $data;
    } // EOF getAllRulesByTplIdLimitGameTagList

    /**
     * Get all rules by a tpl_id and the game_platform_id list.
     *
     *  Ref. by self::getAllRulesByTplId().
     * @param integer $tpl_id The field, common_cashback_multiple_range_templates.cb_mr_tpl_id .
     * @param array $game_platform_id_list The field, "external_system.id" or "external_system_list.id" .
     *
     * @return false|array The rows array, if false for no data.
     */
    public function getAllRulesByTplIdLimitGamePlatformList($tpl_id, $game_platform_id_list = []){
        // 撈取下面 type	type_map_id 狀況，屬於GamePlatform的記錄。
        //  type = game_platform, type_map_id = game_platform_id
        //  type = game_type, type_map_id = game_type.id
        //  type = game type_map_id = game_description.id

        $where = [];
        if( ! empty($game_platform_id_list) ){
            $this->CI->load->model('game_description_model');
            $game_type_list = $this->CI->game_description_model->getGameTypeIdByPlatformIdList($game_platform_id_list);
            $game_type_list = array_unique($game_type_list);
            $game_description_list = $this->CI->game_description_model->getGameDescriptionIdByPlatformIdList($game_platform_id_list);
            $game_description_list = array_unique($game_description_list);

        }
        $sql = "SELECT * FROM $this->tableName ";
        $sql_params =[];
        $or_sentence_list = [];
        if( ! empty($game_platform_id_list) ){
            $or_sentence_list[] = '( type = "game_platform" AND type_map_id in ('. implode(',', $game_platform_id_list). ') )';
        }

        if( ! empty($game_type_list) ){
            $or_sentence_list[] = '( type = "game_type" AND type_map_id in ('. implode(',', $game_type_list). ') )';
        }

        if( ! empty($game_description_list) ){
            $or_sentence_list[] = '( type = "game" AND type_map_id in ('. implode(',', $game_description_list). ') )';
        }
        $or_sentence = implode(' OR ', $or_sentence_list);

        if( ! empty($or_sentence_list) ){
            $sql .= 'WHERE ';
        }
        if( ! empty($or_sentence) ){
            $sql .= $or_sentence;
        }
        $query =$this->db->query($sql, $sql_params);
        $data = $this->getMultipleRowArray($query);

        if(empty($data)){
            return FALSE;
        }

        return $data;

    } // EOF getAllRulesByTplIdLimitGamePlatformList


    /**
     * Get all rules by a tpl_id
     * @param integer $tpl_id The field, common_cashback_multiple_range_templates.cb_mr_tpl_id .
     * @return false|array The rows array, if false for no data.
     */
    public function getAllRulesByTplId($tpl_id){
        $query = $this->db->get_where($this->tableName, [
            'tpl_id' => $tpl_id,
        ]);

        $data = $this->getMultipleRowArray($query);

        if(empty($data)){
            return FALSE;
        }

        return $data;
    } // EOF getAllRulesByTplId

    public function hasRuleById($rule_id){
        return $this->getRuleById($rule_id);
    }

    public function getRuleById($rule_id){
        $query = $this->db->get_where($this->tableName, [
            'cb_mr_rule_id' => $rule_id
        ]);

        $data = $this->getOneRowArray($query);

        if(empty($data)){
            return FALSE;
        }

        return $data;
    }

    public function createRule($tpl_id, $type, $type_map_id, $min_bet_amount, $max_bet_amount, $cashback_percentage, $max_cashback_amount){
        $data = [
            'tpl_id' => $tpl_id,
            'type' => $type,
            'type_map_id' => $type_map_id,
            'min_bet_amount' => $min_bet_amount,
            'max_bet_amount' => $max_bet_amount,
            'cashback_percentage' => $cashback_percentage,
            'max_cashback_amount' => $max_cashback_amount,
            'created_at' => $this->utils->getNowForMysql(),
            'updated_at' => $this->utils->getNowForMysql(),
        ];
        $result = $this->db->insert($this->tableName, $data);

        return ($result) ? TRUE : FALSE;
    }

    public function updateRuleById($rule_id, $min_bet_amount, $max_bet_amount, $cashback_percentage, $max_cashback_amount){
        $data = [
            'min_bet_amount' => $min_bet_amount,
            'max_bet_amount' => $max_bet_amount,
            'cashback_percentage' => $cashback_percentage,
            'max_cashback_amount' => $max_cashback_amount,
            'updated_at' => $this->utils->getNowForMysql()
        ];

        $result = $this->db->update($this->tableName, $data, [
            'cb_mr_rule_id' => $rule_id
        ]);

        return ($result) ? TRUE : FALSE;
    }

    public function deleteRuleById($rule_id){
        $result = $this->db->delete($this->tableName, [
            'cb_mr_rule_id' => $rule_id
        ]);

        return ($result) ? TRUE : FALSE;
    }
}
