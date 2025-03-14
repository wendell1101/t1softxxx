<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Class Iovation Logs
 *
 *
 * @category Payment Model
 * @version 1.8.10
 * @copyright 2013-2016 TripleOneTech
 */

class Seamless_missing_payout extends BaseModel {

	protected $tableName = 'seamless_missing_payout_report';

	protected $idField = 'id';
	protected $gameLogsStatus;
	
	const NOT_FIXED = 0;
    const FIXED = 1;	

	public function __construct()
	{
		$this->CI->load->model(['game_logs', 'player_model']);

		$this->gameLogsStatus = [
			Game_logs::STATUS_PENDING => 'pending',
			Game_logs::STATUS_SETTLED => 'settled',
			Game_logs::STATUS_ACCEPTED => 'accepted',
			Game_logs::STATUS_REJECTED => 'rejected',
			Game_logs::STATUS_CANCELLED => 'cancelled',
			Game_logs::STATUS_VOID => 'void',
			Game_logs::STATUS_REFUND => 'refund',
			Game_logs::STATUS_SETTLED_NO_PAYOUT => 'settled_no_payout',
			Game_logs::STATUS_UNSETTLED => 'unsettled'
		];

	}

	public function addData($params) {
		$data = [];
		$data['transaction_date'] 		= isset($params['transaction_date'])?$data['transaction_date']:null;

		$data['game_platform_id'] 		= isset($params['game_platform_id'])?$data['game_platform_id']:null;
		$data['player_id'] 				= isset($params['player_id'])?$data['player_id']:null;
		$data['round_no'] 				= isset($params['round_no'])?$data['round_no']:null;
		$data['transaction_type'] 		= isset($params['transaction_type'])?$data['transaction_type']:null;		
		$data['game_status'] 			= isset($params['game_status'])?$data['game_status']:null;
		$data['real_betting_amount'] 	= isset($params['real_betting_amount'])?$data['real_betting_amount']:null;
		$data['result_amount'] 			= isset($params['result_amount'])?$data['result_amount']:null;
		$data['added_amount'] 			= isset($params['added_amount'])?$data['added_amount']:null;
		$data['deducted_amount'] 		= isset($params['deducted_amount'])?$data['deducted_amount']:null;
		$data['fixed_by'] 				= isset($params['fixed_by'])?$data['fixed_by']:null;
		$data['game_description_id'] 	= isset($params['game_description_id'])?$data['game_description_id']:null;
		$data['game_type_id'] 			= isset($params['game_type_id'])?$data['game_type_id']:null;		
		$data['external_uniqueid'] 		= isset($params['external_uniqueid'])?$data['external_uniqueid']:null;		
		$data['status'] 				= isset($params['status'])?$data['status']:self::NOT_FIXED;
		
		$data['note'] = null;
		if(isset($params['note'])){
			$data['note'] = json_encode((array)$data['note']);
		}		
		
		return $this->db->insert($this->tableName, $data);
    } 

	public function setFixed($id, $extra){
		if(!$id){
			return false;
		}

		$qry = $this->db->get_where($this->tableName, array('id' => $id));
		$oldData = $this->getOneRowArray($qry);
		if (!$oldData) {
			return false;
		}

		$oldNote = isset($oldData['note'])?$oldData['note']:[];
		$oldNote = json_decode($oldNote, true);

		$data = array(
			'fixed_by' => isset($extra['fixed_by'])?$extra['fixed_by']:null,
			'status' => self::FIXED,
			//'added_amount' => isset($extra['added_amount'])?$extra['added_amount']:0,
			//'deducted_amount' => isset($extra['deducted_amount'])?$extra['deducted_amount']:0,			
		);		
		if(isset($extra['note'])){
			if(!isset($oldNote['comments'])){
				$oldNote['comments'] = [];
			}
			$oldNote['comments'] = $extra['note'];

			$extra['note'] = json_encode((array)$oldNote);
		}
		$this->db->where('id', $id);
		return $this->db->update($this->tableName, $data);
	}

	public function queryStatus($id, $extra = []) {
		if (!$id) {
			return false;
		}
	
		$qry = $this->db->get_where($this->tableName, array('id' => $id));
		$oldData = $this->getOneRowArray($qry);
		if (!$oldData) {
			return false;
		}
	
		$player_username = $this->CI->player_model->getPlayerUsername($oldData['player_id'])['username'];
		$status = $this->gameLogsStatus[$oldData['transaction_status']];
		$gamePlatformId = $oldData['game_platform_id'];
		$externalUniqueId = $oldData['external_uniqueid'];
	
		// Load game API 
		$api = $this->utils->loadExternalSystemLibObject($gamePlatformId); 
	
		$rlt = $api->queryBetTransactionStatus($gamePlatformId, $externalUniqueId);

		$success = $rlt['success'];
		$uniqueId = $oldData['external_uniqueid'];
		$playerId = $oldData['player_id'];
		$amount = $oldData['amount'];

		$dataToUpdate = [];
	
		if ($rlt) {
			$status = $this->gameLogsStatus[$rlt['status']];
			$newTransStatus = $rlt['status'];
			if ($success && $newTransStatus != $oldData['transaction_status']) {
				$dataToUpdate = [
					'transaction_status' => $newTransStatus,
					'status' => self::FIXED
				];
				// Update status
				$this->db->where('id', $id)->update($this->tableName, $dataToUpdate);
			}
		}
	
		return [
			'success' => $success,
			'unique_id' => $uniqueId,
			'player_id' => $playerId,
			'player_name' => $player_username,
			'amount' => $amount,
			'status' => $status
		];
	}

	public function checkIfAlreadyExist($externalUniqueId) {
		$qry = $this->db->get_where($this->tableName, ['external_uniqueid' => $externalUniqueId]);
		$transaction = $this->getOneRowArray($qry);
		return $transaction ? $transaction : false;
	}
}

/* End of file Iovation_logs.php */
/* Location: ./application/models/iovation_logs.php */
