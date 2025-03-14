<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

class player_in_priority extends BaseModel {

    const PRIORITY_UNTICK = 0;
    const PRIORITY_TICKED = 1;
    const JOIN_POPUP_NOT_YET_SHOW = 0;
    const JOIN_POPUP_SHOWN = 1;


	protected $tableName = 'player_in_priority';


	public function __construct() {
		parent::__construct();
	}

    /**
     * tick the prop, Priority Player
     *
     * @param int $player_id The player.playerId
     * @param int|null $insert_id When insert completed, the inserted id will assigned.
     * @return int When 0,then its means not yet join;
     *  When 1,then its means the player already joined;
     *  When 2,then its means the player has joined at this time.
     */
    public function tickPriority($player_id, &$insert_id = null){
        $_isPriority = $this->isPriority($player_id);
        $whereArr = [];
        $whereArr['player_id'] = $player_id;
        $data = [];
        $data['is_priority'] = self::PRIORITY_TICKED;
        $this->editData($this->tableName, $whereArr, $data);
        return !empty($this->isPriority($player_id));
    } // EOF tickPriority
    /**
	 * untick Priority player.
     * aka. Delete data by player_id
	 *
	 * @param int $player_id The field, player.playerId .
	 * ...
	 * @return bool The action result had completed, or Not.
	 */
	public function untickPriority($player_id){
        $_isPriority = $this->isPriority($player_id);
        $whereArr = [];
        $whereArr['player_id'] = $player_id;
        $data = [];
        $data['is_priority'] = self::PRIORITY_UNTICK;
        $this->editData($this->tableName, $whereArr, $data);
        return empty($this->isPriority($player_id));
	} // EOF untickPriority

    /**
     * Check the player has Joined Or Not.
     *
     * @param int $player_id The "player.playerId" filed.
     * @return boolean When true, it means the player had joined.
     * When false, it means the player has not yet join.
     */
    public function isPriority($player_id){
        $is_priority = null;
        //get data
        $this->db->from($this->tableName);
		$this->db->where('player_id', $player_id);

		$query = $this->db->get();
		$data = $query->row_array();

        if( empty($data) ){
            $_data = [];
            $data['player_id'] = $player_id;
            $data['is_priority'] = self::PRIORITY_UNTICK;
            $this->insertData($this->tableName, $data);
            // $insert_id = $this->db->insert_id();
            $is_priority = false;
        }else{
            if($data['is_priority'] == self::PRIORITY_UNTICK){
                $is_priority = false;
            }else{
                $is_priority = true;
            }
        }
        unset($query);
        return $is_priority;
    } // EOF isPriority

    public function countPriority($start_datetime = null, $end_datetime = null){
        $this->db->select("COUNT(id) AS count");
        $this->db->from($this->tableName);
        $this->db->where('is_priority = ', self::PRIORITY_TICKED);
        if ( !empty($start_datetime) ) {
			$this->db->where('updated_at >= ', $start_datetime);
		}
        if ( !empty($end_datetime) ) {
            $this->db->where('updated_at <= ', $end_datetime);
        }
        $count = $this->runOneRowOneField('count');
        return $count;
    }
    /**
     * Update the Join Popup had shown done
     * and assign tick or not for checkbox of the player.
     *
     * @param integer $player_id The "player.playerId" filed.
     * @param int $tickJoin When 1, it means the player had Joined Priority player.
     * When 0, it means the player has Not Join.
     *
     * @return int The affected rows amount.
     * P.S. When there are no changes, return Zero.
     */
    public function setIsJoinShowDone($player_id, $tickJoin = 0){
        // for create a data via isPriority(),
        // once the player data is not exists.
        $_isPriority = $this->isPriority($player_id);
        $whereArr = [];
        $whereArr['player_id'] = $player_id;
        $data = [];
        // Join Popup had shown done
        $data['is_join_show_done'] = self::JOIN_POPUP_SHOWN;
        // assign tick or not, for checkbox of the player.
        if($tickJoin){
            // join Priority players
            if(!$_isPriority){ // Not be ticked, 1
                // update is_priority to 1
                $data['is_priority'] = self::PRIORITY_TICKED;
            }
        }else{
            // quit Priority players
            if($_isPriority){ // Not be untick, 0
                // update is_priority to 0
                $data['is_priority'] = self::PRIORITY_UNTICK;
            }
        }
        $this->editData($this->tableName, $whereArr, $data);
        return $this->db->affected_rows();
    }

    public function isJoinShowDone($player_id){

        $_isPriority = $this->isPriority($player_id);
        //get data
        $this->db->from($this->tableName);
		$this->db->where('player_id', $player_id);
		$query = $this->db->get();
		$data = $query->row_array();
        unset($query);
        return !!($data['is_join_show_done'] == self::JOIN_POPUP_SHOWN);
    }

}

/* Location: ./application/models/player_in_priority.php */
