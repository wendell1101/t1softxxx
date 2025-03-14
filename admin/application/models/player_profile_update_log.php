<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Payer Profile Update Log
 *
 * @author	Gary
 */

class Player_profile_update_log extends BaseModel {

	const TABLE = 'player_profile_update_log';

    function __construct() {
		parent::__construct();
    }

	public function getFieldUpdateCountByPlayerId($player_id, $field_name) {
        $this->db->select('update_count');
        $this->db->where('player_id', $player_id);
        $this->db->where('field_name', $field_name);
        $query = $this->db->get(self::TABLE);
        $result = $query->row_array();

        if (empty($result)) {
            return 0;
        }

        return $result['update_count'];
    }

    public function incrementFieldUpdateCountByPlayerId($player_id, $field_name) {
        $this->db->set('update_count', 'update_count + 1', FALSE);
        $this->db->where('player_id', $player_id);
        $this->db->where('field_name', $field_name);
        $this->db->update(self::TABLE);
    }

    public function insertFieldUpdateCount($player_id, $field_name) {
        $data = array(
            'player_id' => $player_id,
            'field_name' => $field_name,
            'update_count' => 1,
        );

        $this->db->insert(self::TABLE, $data);
    }

    public function isExistedFieldUpdateCount($player_id, $field_name) {
        $this->db->where('player_id', $player_id);
        $this->db->where('field_name', $field_name);
        $query = $this->db->get(self::TABLE);
        $result = $query->row_array();

        return !empty($result);
    }

}

/* End of file ip.php */
/* Location: ./application/models/registration_setting.php */