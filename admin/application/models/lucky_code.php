<?php
require_once dirname(__FILE__) . '/base_model.php';

class Lucky_code extends BaseModel {

	protected $tableName = 'lucky_code';
    const DEPOSIT = 1;
    const WITHDRAWAL = 2;
    const BONUS = 3;
	const LUCKY_CODE_STATUS_NORMAL = 1;
	const PERIOD_CODE_STATUS_NORMAL = 1;
	const PERIOD_CODE_STATUS_DISABLE = 0;

	public function addLuckyCodePeriod($start_date, $end_date, $period_name){
		$this->db->insert("lucky_code_period", array(
				'start_date'  => $start_date,
				'end_date'	  => $end_date,
				'status'	  => self::PERIOD_CODE_STATUS_NORMAL,
				'period_name' => $period_name
			)
		);
	}

	public function updateLuckyCodePeriod($Id, $start_date, $end_date, $status, $period_name){
		
		$data = array(
			'start_date' => $start_date,
			'end_date'	 => $end_date,
			'status'	 => $status
		);
		if($period_name != null){
			$data['period_name'] = $period_name;

		}
		$this->db->where('id', $Id)->update('lucky_code_period', $data);
	}

	public function getLuckyCodePeriod($start_time, $end_time){
		$this->db->select('*')
				 ->from('lucky_code_period')
				 ->where('start_date <=', $start_time)
				 ->where('end_date >=', $end_time)
				 ->where('status', self::PERIOD_CODE_STATUS_NORMAL);
		return $this->runOneRow();
	}

	public function getHasLuckyCodeOrder($start_time, $end_time){
		$sql = $this->db->select('trans_id')
						->from($this->tableName)
						->where('sale_order_settled_time >=', $start_time)
                        ->where('sale_order_settled_time <=', $end_time)
						->where('status', self::LUCKY_CODE_STATUS_NORMAL)
						->order_by('id', 'desc')
						->group_by('trans_id')
						->get();
		return $sql->result_array();
	}

	public function getSaleOrdersWithPeriod($start_time, $end_time, $order_ids=null){

		$this->db->select('sale_orders.id, 
                           sale_orders.player_id, 
                           sale_orders.amount, 
                           sale_orders.secure_id,
                           sale_orders_timelog.create_date as settled_time');
        $this->db->from('sale_orders');
		$this->db->where('sale_orders.status', 5);
		$this->db->join('sale_orders_timelog','sale_orders.id = sale_orders_timelog.sale_order_id', 'inner');
		$this->db->where('sale_orders_timelog.after_status', 5);
		$this->db->where('sale_orders_timelog.create_date >=', $start_time);
		$this->db->where('sale_orders_timelog.create_date <=', $end_time);

		if($order_ids != null){
            $this->db->where("sale_orders.id IN ({$order_ids})", null, false);
		}

        $this->db->order_by('sale_orders.id', 'desc');
        $qry = $this->db->get();
		$this->utils->debug_log(__METHOD__, 'sql', $this->db->last_query());
        return $this->getMultipleRowArray($qry);
	}

	public function generateLuckyCode($piece, $data, $period_id, $code_period){
		$insertArr = array();
		if($this->db->field_exists('ip', $this->tableName)){
			$this->load->model('player_model');
			$player_registrationIp   = $this->player_model->registrationIpfunction($data['player_id'], true);
			$insertArr['ip'] = $player_registrationIp['ip'];
        }
		if($this->db->field_exists('city', $this->tableName)){
			if(!empty($player_registrationIp['city'])){
				$insertArr['city'] =$player_registrationIp['city'];
			}
		}
		if($this->db->field_exists('country', $this->tableName)){
			if(!empty($player_registrationIp['country'])){
				$insertArr['country'] = $player_registrationIp['country'];
			}
        }
		for($i = 0; $i < $piece; $i++){
			$insertArr['period_id'] = $period_id;
			$insertArr['player_id'] = $data['player_id'];
			$insertArr['trans_type'] = self::DEPOSIT;
			$insertArr['trans_id'] = $data['id'];
			$insertArr['code'] = uniqid($code_period.'_');
			$insertArr['status'] = 1;
			$insertArr['remark'] = (php_sapi_name() == 'cli') ? 'manual' : 'cronjob';
			$insertArr['sale_order_settled_time'] = $data['settled_time'];
			$this->db->insert($this->tableName, $insertArr);
		}
	}
    public function deleteLuckyCode($player_id, $trans_id){

        if($trans_id != null){
            $this->db->where('trans_id', $trans_id);
        }

        if($player_id != "all"){
            $this->db->where('player_id', $player_id);
        }else{
            $this->db->where('status', self::LUCKY_CODE_STATUS_NORMAL);
        }

        $this->db->delete($this->tableName);
    }

	public function getLuckyCodePeriodByCurrentTime($current_time){
		$this->db->select('*')
				->where('start_date <=', $current_time)
				->where('end_date >=', $current_time)
				->where('status', self::PERIOD_CODE_STATUS_NORMAL)
				->limit(1);
		$query = $this->db->get('lucky_code_period');
		return $this->getOneRowArray($query);
	}

	public function getLastPeriodByCurrentTime($current_time) {
		$this->db->select('*')
				->where('end_date <=', $current_time)
				->where('status', self::PERIOD_CODE_STATUS_NORMAL)
				->order_by('end_date', 'desc')
				->limit(1);
		$query = $this->db->get('lucky_code_period');
		return $this->getOneRowArray($query);
	}
	public function getLuckyCodePeriodByPeriodName($period_id){
		$this->db->select('*')
				->where('id', $period_id)
				->where('status', self::PERIOD_CODE_STATUS_NORMAL)
				->limit(1);
		$query = $this->db->get('lucky_code_period');
		return $this->getOneRowArray($query);
	}
	public function get_none_ip_records($period_id){
		$this->db->select('player_id, period_id');
		$this->db->where('period_id', $period_id);
		$this->db->group_by('player_id');
		// $this->db->where('ip', null);
		$query = $this->db->get('lucky_code');
		return $this->getMultipleRowArray($query);
	}
	public function get_none_ip_records_count($period_id){
		$this->db->select('count(*) as count');
		$this->db->where('period_id', $period_id);
		// $this->db->where('ip', null);
		$query = $this->db->get('lucky_code');
		return $this->getOneRowArray($query);
	}

	public function update_lucky_code_ipinfo($playerId, $period_id){
		$this->load->model('player_model');
		$player_registrationIp   = $this->player_model->registrationIpfunction($playerId, true);
		$ip = $player_registrationIp['ip'];
		$city = $player_registrationIp['city'];
		$country = $player_registrationIp['country'];
		$this->db->where('player_id', $playerId);
		$this->db->where('period_id', $period_id);
		// $this->db->where('ip', null)
		$this->db->update('lucky_code', array('ip' => $ip, 'country' => $country, 'city' => $city));
		return $this->db->affected_rows();
	}
	public function get_player_code_list_pagination($playerId, $period_id, $limit, $page){
		$table = 'lucky_code';
		$result = $this->getDataWithAPIPagination($table, function() use($playerId, $period_id) {
			$this->db->select('code as luckyCode');
			$this->db->where('player_id', $playerId);
			$this->db->where('period_id', $period_id);
			$this->db->where('status', self::LUCKY_CODE_STATUS_NORMAL);
		}, $limit, $page);
		return $result;
	}

}

/////end of file///////
