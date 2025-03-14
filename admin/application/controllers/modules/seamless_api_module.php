<?php

/**
 *
 * api for transfer balance
 */
trait seamless_api_module{
	public function __construct(){
		$this->CI->load->library('input');
	}
	public function seamless_transaction_auto_fix(){
		$id = $this->input->post('id');
		$extra = [];
		$this->CI->load->model(['seamless_missing_payout']);
		$this->CI->seamless_missing_payout->setFixed($id, $extra);
        $result = [
			'msg' => 'Auto fix success',
		];
		$this->returnJsonResult($result);

	}

	public function seamless_transaction_query_status() {
		$this->CI->load->model('seamless_missing_payout');
		
		$id = $this->input->post('id');
		
		// Query transaction status
		$res = $this->CI->seamless_missing_payout->queryStatus($id);
		
		$status = $res['status'];
		$uniqueId = $res['unique_id'];
		$playerName = $res['player_name'];
		$amount = $res['amount'];

		$result = [
			'msg' => "Status of $uniqueId is $status and this transaction belongs to $playerName. The amount is $amount."
		];
		
		$this->returnJsonResult($result);
	}
	
}
