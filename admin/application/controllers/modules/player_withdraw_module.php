<?php
trait player_withdraw_module {

	public function iframe_viewWithdraw(){
		redirect('player_center/withdraw');
	}

	/**
	 * view withdrawal form
	 *
	 *
	 * @return rendered template
	 */
	public function withdraw( $new_bank = '' ) {
        redirect('player_center2/withdraw'); // disable legacy function.
	}

	//sum
    public function multi_array_sum($arr,$key) {
		if ($arr) {
			$sum_no = 0;
			foreach($arr as $v){
			   $sum_no +=  $v[$key];
			 }
			return $sum_no;
		} else {
			return 0;
		}
	}

	public function check_new_withdrawal_bank_account_number($bank_account_number){
		$playerId = $this->authentication->getPlayerId();
		if(empty($playerId)){
			$this->form_validation->set_message('check_new_withdrawal_bank_account_number', lang('Please login again'));
			return false;
		}
		$bank_details_id=null;
		$this->load->model(['playerbankdetails']);

		$bank_type=Playerbankdetails::WITHDRAWAL_BANK;
		return $this->common_validate_bank_account_number('check_new_withdrawal_bank_account_number',
			$playerId, $bank_account_number, $bank_type, $bank_details_id);
	}
}
