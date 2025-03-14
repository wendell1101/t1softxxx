<?php
require_once dirname(__FILE__) . '/base_model.php';

/**
 */
class Total_cashback_player_game_daily extends BaseModel {
    private $tableName = 'total_cashback_player_game_daily';

    public function __construct() {
        parent::__construct();
    }


    public function get($id, $field = 'id', $recalculate_table = null) {
        $this->db->where($field, $id);

        if(!empty($recalculate_table)){
            $qry = $this->db->get($recalculate_table);
        }else{
            $qry = $this->db->get($this->tableName);
        }

        return $this->getMultipleRowArray($qry);
	}


    public function setAppointId($id, $appoint_id, $recalculate_table = null) {
		$data = ['appoint_id' => $appoint_id];

        $list = $this->get($id, 'id', $recalculate_table);

        /// update the appoint_id in the applied_info
        $total_cashback_player_game_daily = $list[0];
        if( ! empty($total_cashback_player_game_daily['applied_info']) ){
            $applied_info = json_decode($total_cashback_player_game_daily['applied_info'], true);
            $applied_info['appoint_id'] = $appoint_id;
            $data['applied_info'] = json_encode($applied_info);
        }

        return $this->update($id, $data, $recalculate_table);
	}
    /**
     * Undocumented function
     *
     * @param [type] $id
     * @param [type] $mergee_applied_info
     * @return void
     */
    /**
     * Update Or Merge the applied_info array into the field of one data.
     *
     * @param integer $to_id The field,"total_cashback_player_game_daily.id" for the target of update/merge action.
     * @param array|string $mergee_applied_info The json format string Or thje
     * @param array $applied_info_orig
     * @return void
     */
    public function updateOrMergeAppointInfo($to_id, $mergee_applied_info = [], $applied_info_orig = []) {
        $return = null;
        $applied_info_new = [];

        $list = $this->get($to_id);
        /// update the appoint_id in the applied_info
        if( empty($applied_info_orig) ){
            // get $applied_info_orig form $to_id.
            $total_cashback_player_game_daily = $list[0];
            $applied_info_orig = $this->utils->json_decode_handleErr($total_cashback_player_game_daily['applied_info'], true);
        }

        if( empty($applied_info_orig) ){
            $applied_info_orig = [];
        }else{
            $applied_info_new = $applied_info_orig;
        }

        if( ! empty($mergee_applied_info) ){
            if( is_string($mergee_applied_info) ){
                $this->utils->debug_log('The mergee_applied_info is No Array type, it will converted to array.', $mergee_applied_info);
                $mergee_applied_info = $this->utils->json_decode_handleErr($mergee_applied_info);
            }
            if( ! is_array($mergee_applied_info) ){
                $mergee_applied_info = (array)$mergee_applied_info;
            }
            /// override the $applied_info_new after merge array.
            $applied_info_new = array_merge($applied_info_new, $mergee_applied_info);
        }

        if( ! empty($applied_info_new) ){
            // convert array to string via jaon string format.
            $applied_info_new_json_string = json_encode($applied_info_new);
            $data = ['applied_info' => $applied_info_new_json_string];
            $return = $this->update($to_id, $data);
        }

        return $return;
    } // EOF updateOrMergeAppointInfo



    /**
     * Create a data
     *
     * @param array $data The field-value array.
     * @return integer The inseted id.
     */
    public function create($data = []){

        return $this->db->insert($this->tableName, $data);
    } // EOF create

    /**
     * Update Extra Applied
     *
     * @param integer $id The P.K.
     * @param array $data The field-value array.
     * @return bool The updated result, If it's true, it means the update is success, otherwise it fails.
     */
    public function update($id, $data, $recalculate_table = null){
        if(!empty($recalculate_table)){
            $result = $this->db->update($recalculate_table, $data, ['id' => $id]);
        }else{
            $result = $this->db->update($this->tableName, $data, ['id' => $id]);
        }
        return ($result) ? TRUE : FALSE;
    } // EOF update


}
