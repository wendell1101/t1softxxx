<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

class Ifsc_cache extends BaseModel {

	const TTL = 15;

	protected $tableName = 'indian_bank_ifsc_info';

	public function store($ifsc_code, $resp, $full_url) {
		$this->delete_by($ifsc_code);
		$dataset = [
			'deleted'		=> false ,
			'ifsc_code'		=> $ifsc_code ,
			'source_url'	=> $full_url ,
			'response'		=> $resp ,
			'ttl'			=> self::TTL ,
			'updated_at'	=> $this->utils->getNowForMysql()
		];
		$this->insertRow($dataset);

		return $this->db->affected_rows();
	}

	public function delete_by($ifsc_code) {
		$this->db->from($this->tableName)
			->where('ifsc_code', $ifsc_code);
		$deleteset = [ 'deleted' => 1 ];

		$this->db->update($this->tableName, $deleteset);

		return $this->db->affected_rows();
	}

	public function get($ifsc_code) {
		$this->db->from($this->tableName)
			->where('ifsc_code', $ifsc_code)
			->where('deleted', 0)
			->order_by('updated_at', 'desc')
			->limit(1)
		;

		// Returns the row array only - without multi-row array
		$res = $this->runOneRowArray();

		// $this->utils->debug_log(__METHOD__, 'res', $res);

		// If no result
		if (empty($res)) {
			return null;
		}
		// If result too old
		$created_at = strtotime($res['updated_at']);
		if (time() - $created_at > self::TTL * 86400) {
			return null;
		}

		return $res;
	}



}

///END OF FILE/////////