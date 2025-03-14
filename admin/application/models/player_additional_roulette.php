<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Player_additional_roulette
 *
 */
class Player_additional_roulette extends BaseModel {
	protected $tableName = 'player_additional_roulette';
	const STATUS_NORMAL = 1;
	const STATUS_USED = 2;
	const STATUS_DELETED = 3;

	public function __construct() {
		parent::__construct();
	}

	/**
	 * create roulette record
	 * @param  array  $rouletteRecordData
	 * @return res id
	 */
	public function add($rouletteRecordData) {
		$this->db->insert($this->tableName, $rouletteRecordData);
		return $this->db->insert_id();
	}

	public function updateById($additionalSpinId, $update_data){

		$this->db->where('id', $additionalSpinId);
		$this->db->update($this->tableName, $update_data);
		if ($this->db->affected_rows() == '1') {
			#update
			return true;
		}
		return false;
	}

	public function getAvailableSpin($playerId, $roulette_type, $start_date = null, $end_date = null){

		$this->db->where('player_id', $playerId);
		$this->db->where('roulette_type', $roulette_type);
		$this->db->where('status', self::STATUS_NORMAL);
		if($start_date){
			$this->db->where("(expired_at >= date('$start_date') OR expired_at is NULL)"); //expired_at Y-m-d mysql date
		} else {
			$now = $this->utils->getNowForMysql();
			$this->db->where("(expired_at >= date('$now') OR expired_at is NULL)");
		}
		$query = $this->db->get($this->tableName);
		// $aaa = $this->utils->printLastSQL();
		return $results = $query->num_rows();

	}

	public function getUsedSpinByDate($playerId, $roulette_type, $start_date, $end_date, $generate_by = null){

		$this->db->where('player_id', $playerId);
		$this->db->where('roulette_type', $roulette_type);
		$this->db->where('status', self::STATUS_USED);
		$this->db->where("apply_at >=", $start_date);
		$this->db->where("apply_at <=", $end_date);
		if(!empty($generate_by)){
			$this->db->where("generate_by", $generate_by);
		}
		$query = $this->db->get($this->tableName);
		return $results = $query->num_rows();

	}

	public function getSpinByGenerateBy($playerId, $generate_by, $roulette_type = null, $status = null, $start_date = null, $end_date = null, $exp_at = null){

		$this->db->where('player_id', $playerId);
		$this->db->where('roulette_type', $roulette_type);
		$this->db->where("generate_by", $generate_by);

		if(!empty($status)){$this->db->where('status', $status);}
		if(!empty($start_date)){$this->db->where("created_at >=", $start_date);}
		if(!empty($end_date)){$this->db->where("created_at <=", $end_date);}
		if(!empty($exp_at)){$this->db->where("expired_at >=", $exp_at);}

		$query = $this->db->get($this->tableName);
		return $results = $query->num_rows();

	}

	public function getBonusByGenerateBy($playerId, $generate_by, $roulette_type = null, $status = null, $start_date = null, $end_date = null){

		$this->db->where('player_additional_roulette.player_id', $playerId);
		$this->db->where("player_additional_roulette.generate_by", $generate_by);
		
		if(!empty($roulette_type)){$this->db->where('player_additional_roulette.roulette_type', $roulette_type);}
		if(!empty($status)){$this->db->where('player_additional_roulette.status', $status);}
		if(!empty($start_date)){$this->db->where("player_additional_roulette.created_at >=", $start_date);}
		if(!empty($end_date)){$this->db->where("player_additional_roulette.created_at <=", $end_date);}

		$this->db->join('playerpromo', 'player_additional_roulette.player_promo_id = playerpromo.playerpromoId', 'LEFT');
		$this->db->select_sum('playerpromo.bonusAmount', 'bonusAmount');
		$this->db->from($this->tableName);
		$amount = $this->runOneRowOneField('bonusAmount');
		return $amount;
	}

	public function getFirstAvailableSpin($playerId, $roulette_type, $start_date = null, $end_date = null){

		$this->db->where('player_id', $playerId);
		$this->db->where('roulette_type', $roulette_type);
		$this->db->where('status', self::STATUS_NORMAL);
		if($start_date){
			$this->db->where("(expired_at >= date('$start_date') OR expired_at is NULL)"); //expired_at Y-m-d mysql date
		} else {
			$now = $this->utils->getNowForMysql();
			$this->db->where("(expired_at >= date('$now') OR expired_at is NULL)");
		}
		$this->db->order_by("created_at", 'ASC');
		$query = $this->db->get($this->tableName);
		// $this->utils->printLastSQL();
		return $query->first_row();

	}


}
