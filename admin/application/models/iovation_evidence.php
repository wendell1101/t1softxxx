<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

class Iovation_evidence extends BaseModel {

	protected $tableName = 'iovation_evidence';

	protected $idField = 'id';

	const SUCCESS ='0';
	const FAILED ='1';


	const EVIDENCE_STATUS_ADDED = 0;
	const EVIDENCE_STATUS_UPDATED = 1; // Change/Updated
	const EVIDENCE_STATUS_RETRACTED = 2;
	/**
	 * get log
	 * @param  string		$id
	 * @return boolean
	 */
	public function getEvidenceById($id, $status = self::SUCCESS) {
		$qry = $this->db->get_where($this->tableName, array('id' => $id, 'status' => $status));
		return $this->getOneRow($qry);
    }

	/**
	 * get log
	 * @param  string		$id
	 * @return boolean
	 */
	public function getEvidenceByPlayerId($id, $status = self::SUCCESS) {
		$qry = $this->db->get_where($this->tableName, array('player_id' => $id, 'status' => $status));
		return $this->getOneRow($qry);
    }

	/**
	 * get log
	 * @param  string		$id
	 * @return boolean
	 */
	public function isEvidenceExist($id, $evidenceType) {
		$this->db->where('player_id', $id);
		$this->db->where('evidence_type', $evidenceType);
		$this->db->where_in('evidence_status', [self::EVIDENCE_STATUS_ADDED, self::EVIDENCE_STATUS_UPDATED]);
		$query = $this->db->get($this->tableName);
		$evidences = $query->row_array();
		return $evidences;
    }


	/**
	 * Get evidence rows by player username and
	 *
	 * @param string $playerUsername The player.username field.
	 * @return array The iovation_evidence rows.
	 */
	public function getEvidenceRowsByUsername($playerUsername) {
		$prefix = '';
		$config_iovation = $this->utils->getConfig('iovation');

		// query username OR prefix+username
		$account_code_format = ' account_code = "%s" ';
		$where_fragments = [];
		$where_fragments[] = sprintf($account_code_format, $playerUsername );
		if( ! empty($config_iovation['prefix']) ){
			$prefix = $config_iovation['prefix'];
			$where_fragments[] = sprintf($account_code_format, $prefix. $playerUsername );
		}
		$where = '('. implode(' OR ', $where_fragments). ')';
		$this->db->where($where);

		$this->db->where_in('evidence_status', [self::EVIDENCE_STATUS_ADDED, self::EVIDENCE_STATUS_UPDATED]);
		$query = $this->db->get($this->tableName);
		$last_query = $this->db->last_query();

		return $this->getMultipleRowArray($query);
    }
}

/* End of file Iovation_evidence.php */
/* Location: ./application/models/iovation_evidence.php */
