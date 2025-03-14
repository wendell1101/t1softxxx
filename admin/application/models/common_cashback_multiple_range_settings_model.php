<?php
require_once dirname(__FILE__) . '/base_model.php';

/**
 */
class Common_cashback_multiple_range_settings_model extends BaseModel {
    private $tableName = 'common_cashback_multiple_range_settings';

    public function __construct() {
        parent::__construct();
    }

    public function getAllSettingsByTplId($tpl_id){
        $query = $this->db->get_where($this->tableName, [
            'tpl_id' => $tpl_id,
        ]);

        $data = $this->getMultipleRowArray($query);

        if(empty($data)){
            return FALSE;
        }

        return $data;
    }

    /**
     * Ref. by getAllSettingsByTplId().
     *
     * @param integer $tpl_id
     * @param array $game_tag_id_list The game_tags.id List
     * @return array The rows.
     */
    public function getAllSettingsByTplIdLimitGameTagList($tpl_id, $game_tag_id_list = []){
        // 撈取下面 type, type_map_id 狀況，屬於 Game Tag 的記錄。
        //  type = game_tag, type_map_id = game_tags.id
        $game_tag_list = [];
        if( ! empty($game_tag_id_list) ){
            $this->CI->load->library(['og_utility']);
            $this->CI->load->model('game_tags'); // getGameTypeIdByPlatformIdList
            $allGameTagsRows = $this->CI->game_tags->getAllGameTagsWithPagination(null, null);
            $game_tag_list = $this->og_utility->array_pluck($allGameTagsRows, 'id');
            $game_tag_list = array_unique($game_tag_list);
        }
        $sql = "SELECT * FROM $this->tableName ";
        $sql_params =[];
        $or_sentence_list = [];
        if( ! empty($game_tag_list) ){
            $or_sentence_list[] = '( type = "game_tag" AND type_map_id in ('. implode(',', $game_tag_list). ') )';
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
    } // EOF getAllSettingsByTplIdLimitGameTagList

    /**
     * Ref. by getAllSettingsByTplId().
     *
     * @param integer $tpl_id
     * @param array $game_platform_id_list The game_description.game_platform_id List
     * @return array The rows.
     */
    public function getAllSettingsByTplIdLimitGamePlatformList($tpl_id, $game_platform_id_list = []){
        // 撈取下面 type, type_map_id 狀況，屬於GamePlatform的記錄。
        //  type = game_platform, type_map_id = game_platform_id
        //  type = game_type, type_map_id = game_type.id
        //  type = game type_map_id = game_description.id


        $game_type_list = [];
        $game_description_list = [];
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
    } // EOF getAllSettingsByTplIdLimitGamePlatformList



    public function hasSetting($tpl_id, $type, $type_map_id){
        return $this->getSetting($tpl_id, $type, $type_map_id);
    }

    public function getSetting($tpl_id, $type, $type_map_id){
        $query = $this->db->get_where($this->tableName, [
            'tpl_id' => $tpl_id,
            'type' => $type,
            'type_map_id' => $type_map_id,
        ]);

        $data = $this->getOneRowArray($query);

        if(empty($data)){
            return FALSE;
        }

        return $data;
    }

    /**
     * Save Setting By Fields
     *
     * @param integer $tpl_id
     * @param string $type
     * @param integer $type_map_id
     * @param array $theFields
     * @return void
     */
    public function saveSettingByFields($tpl_id, $type, $type_map_id, $theFields = []){
        if(FALSE === $setting = $this->hasSetting($tpl_id, $type, $type_map_id)){
            // return $this->createSetting($tpl_id, $type, $type_map_id, $enabled_cashback);
            return $this->createSettingByFields($tpl_id, $type, $type_map_id, $theFields);
        }

        // return $this->updateSetting($tpl_id, $type, $type_map_id, $enabled_cashback);
        return $this->updateSettingByFields($tpl_id, $type, $type_map_id, $theFields);

    } // EOF saveSettingByFields

    public function saveSetting($tpl_id, $type, $type_map_id, $enabled_cashback = 0){
        if(FALSE === $setting = $this->hasSetting($tpl_id, $type, $type_map_id)){
            return $this->createSetting($tpl_id, $type, $type_map_id, $enabled_cashback);
        }

        return $this->updateSetting($tpl_id, $type, $type_map_id, $enabled_cashback);
    }

    /**
     * create Setting By Fields
     *
     * @param integer $tpl_id
     * @param string $type
     * @param integer $type_map_id
     * @param array $theFields the fields, the format,
     * - $thefields[FieldName] = FieldValue
     * @return void
     */
    public function createSettingByFields($tpl_id, $type, $type_map_id, $theFields = []){
        $data = [
            'tpl_id' => $tpl_id,
            'type' => $type,
            'type_map_id' => $type_map_id,
            'created_at' => $this->utils->getNowForMysql(),
            'updated_at' => $this->utils->getNowForMysql(),
        ];
        if( ! empty($theFields) ){
            $data = array_merge($data, $theFields);
        }

        $result = $this->db->insert($this->tableName, $data);

        return ($result) ? $this->getSetting($tpl_id, $type, $type_map_id) : FALSE;
    }// EOF createSettingByFields

    public function createSetting($tpl_id, $type, $type_map_id, $enabled_cashback = 0){
        $data = ['enabled_cashback' => ($enabled_cashback) ? 1 : 0 ];
        return $this->createSettingByFields($tpl_id, $type, $type_map_id, $data);

        // $data = [
        //     'tpl_id' => $tpl_id,
        //     'type' => $type,
        //     'type_map_id' => $type_map_id,
        //     'enabled_cashback' => ($enabled_cashback) ? 1 : 0,
        //     'created_at' => $this->utils->getNowForMysql(),
        //     'updated_at' => $this->utils->getNowForMysql(),
        // ];
        // $result = $this->db->insert($this->tableName, $data);
        //
        // return ($result) ? $this->getSetting($tpl_id, $type, $type_map_id) : FALSE;
    }


    /**
     * update Setting By Fields
     *
     * @param integer $tpl_id
     * @param string $type
     * @param integer $type_map_id
     * @param array $thefields the fields, the format,
     * - $thefields[FieldName] = FieldValue
     * @return void
     */
    public function updateSettingByFields($tpl_id, $type, $type_map_id, $theFields = []){
        $data = [
            'updated_at' => $this->utils->getNowForMysql()
        ];
        if( ! empty($theFields) ){
            $data = array_merge($data, $theFields);
        }

        $result = $this->db->update($this->tableName, $data, [
            'tpl_id' => $tpl_id,
            'type' => $type,
            'type_map_id' => $type_map_id,
        ]);

        return ($result) ? TRUE : FALSE;
    }

    public function updateSetting($tpl_id, $type, $type_map_id, $enabled_cashback = 0){

        $data = ['enabled_cashback' => ($enabled_cashback) ? 1 : 0];
        return $this->updateSettingByFields($tpl_id, $type, $type_map_id, $data);
        // $data = [
        //     'enabled_cashback' => ($enabled_cashback) ? 1 : 0,
        //     'updated_at' => $this->utils->getNowForMysql()
        // ];
        //
        // $result = $this->db->update($this->tableName, $data, [
        //     'tpl_id' => $tpl_id,
        //     'type' => $type,
        //     'type_map_id' => $type_map_id,
        // ]);
        //
        // return ($result) ? TRUE : FALSE;
    }
}
