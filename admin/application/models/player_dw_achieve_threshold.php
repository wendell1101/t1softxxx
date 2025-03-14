<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * player_dw_achieve_threshold
 *
 *
 */
class player_dw_achieve_threshold extends BaseModel {
	protected $tableName = 'player_dw_achieve_threshold';

	const ACHIEVE_THRESHOLD_DEPOSIT    = 1;
	const ACHIEVE_THRESHOLD_WITHDRAWAL = 2;

	public function __construct() {
		parent::__construct();
	}

	/**
	 * overview : get Player Achieve Threshol Details
	 * @param int $playerId
	 * @return array
	 */
	public function getPlayerAchieveThresholdDetails($playerId) {
		$this->db->select('*')
		->from($this->tableName)
		->where('player_id', $playerId);

		return $this->runMultipleRow();
	}

	/**
	 * overview : get Player Achieve Threshol Details
	 * @param int $playerId
	 * @return array
	 */
	public function getPlayerAchieveThresholdHistoryByPlayerId($playerId, $transaction_type) {
		$this->db->select('*')
		->from('player_dw_achieve_threshold_history')
		->where('player_id', $playerId)
		->where('achieve_threshold_type', $transaction_type);

		return $this->runMultipleRow();
	}

	/**
	 * @param  count id
	 * @return int
	 */
	public function getPlayerDwAchieveThresholdCount() {
		$this->db->select('count(player_dw_achieve_threshold_id) as cnt');
		$this->db->from('player_dw_achieve_threshold_history');

		return $this->runOneRowOneField('cnt');
	}

	/**
	 * overview : insert & update player_dw_achieve_threshold
	 *
	 * @param  array data
	 * @return int achieve_threshol id
	 */
	public function setAchieveThreshold($data) {

		$checkAchieveThresholExist = false;
		$player_id = $data['player_id'];

		if (!empty($player_id)) {
			$checkAchieveThresholExist = $this->getPlayerAchieveThresholdDetails($player_id);
		}

		$this->utils->debug_log('--------------setAchieveThreshold checkAchieveThresholExist', $checkAchieveThresholExist, $player_id);

		if (!empty($checkAchieveThresholExist)) {
			$data = array(
                'update_by'                   => $this->authentication->getUsername(),
                'update_at'                   => date('Y-m-d H:i:s'),
                'before_deposit_achieve_threshold'    => $data['before_deposit_achieve_threshold'],
                'before_withdrawal_achieve_threshold' => $data['before_withdrawal_achieve_threshold'],
                'after_deposit_achieve_threshold'     => $data['after_deposit_achieve_threshold'],
                'after_withdrawal_achieve_threshold'  => $data['after_withdrawal_achieve_threshold'],
            );
			$this->db->where('player_id', $player_id);
			$this->db->update($this->tableName, $data);

			return $this->db->affected_rows();
		}else{
			$this->db->insert($this->tableName, $data);
		}

		return $this->db->insert_id();
	}

	public function setAchieveThresholdHistory($data, $transaction_type, $is_updated) {

		$checkAchieveThresholExist = false;
		$player_id = $data['player_id'];

		if (!empty($player_id)) {
			$checkAchieveThresholExist = $this->getPlayerAchieveThresholdHistoryByPlayerId($player_id, $transaction_type);
		}

		$this->utils->debug_log('--------------setAchieveThreshold checkAchieveThresholExist', $checkAchieveThresholExist, $player_id, $is_updated);

		if (empty($checkAchieveThresholExist)) {
			$this->db->insert('player_dw_achieve_threshold_history', $data);
			return $this->db->insert_id();
		}

		if ($is_updated) {
			$this->db->insert('player_dw_achieve_threshold_history', $data);
			return $this->db->insert_id();
		}

		return false;
	}
}
