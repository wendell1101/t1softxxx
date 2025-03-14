<?php
trait points_transaction_module {

	public function adjust_points_balance_form($transaction_type, $player_id = null, $is_own_page = 'false') {
		$this->load->model(array('promorules', 'external_system', 'common_category'));
		$data = array(					
			'platform_name' => 'Points Balance',
			'transaction_type' => $transaction_type,
			'player_id' => $player_id,			
		);

		$userId=$this->authentication->getUserId();
		$data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenFieldForAdmin($userId);

		$this->session->set_userdata('prevent_refresh', true);
		//move to marketing_management

		$data['is_own_page'] = $is_own_page ;
		$this->load->view('payment_management/balance_adjustment/adjust_points_balance_form', $data);
	}

	/**
	 *
	 * detail: save/update the points balance for a certain player
	 *
	 * @POST $amount
	 * @POST $reason
	 * @POST $show_in_front_end	 
	 * @param string $adjustment_type
	 * @param int $player_id player id
	 * @return void
	 */
	public function adjust_points_balance_post($adjustment_type, $player_id, $is_own_page = 'false') {
		$userId=$this->authentication->getUserId();

		if(!$this->verifyAndResetDoubleSubmitForAdmin($userId)){
			$message = lang('Please refresh and try, and donot allow double submit');
			$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			redirect('payment_management/adjust_balance/' . $player_id);
			return;
		}

		# GET PARAMETERS FROM SYSTEM
		$adminUserId = $this->authentication->getUserId();
		$this->load->model(array('point_transactions', 'wallet_model', 'response_result','player_model'));
        
        # GET REQUIRED PARAMETERS FROM REQUEST		
		$amount = (float)$this->input->post('amount');
		$reason = $this->input->post('reason');
		$beforePointBalance = $this->point_transactions->getPlayerAvailablePoints($player_id);

		$message = lang('notify.61');

		if(strlen($reason)>120) {
            $message = lang('Maximum 120 characters (including spaces).');
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
            redirect('payment_management/adjust_balance/' . $player_id);
            return;
		}
		$reason = htmlentities($reason, ENT_QUOTES);

		$success = false;
		$infufficient = false;
		
		$this->utils->debug_log('bermar adjust_points_balance_post amount:', $amount);

		$this->utils->debug_log('bermar adjust_points_balance_post beforePointBalance:', $beforePointBalance);
		if($amount > 0){

			$success = $this->lockAndTrans(Utils::LOCK_ACTION_MANUALLY_ADJUST_POINTS_BALANCE, $player_id, function ()
			use ($player_id, $beforePointBalance, $amount, &$infufficient, $adjustment_type, 
			$reason, &$message, $adminUserId) {

				switch ((int)$adjustment_type) {
					case Point_transactions::MANUAL_DEDUCT_POINTS:
	
						if ($amount > $beforePointBalance) {

							$infufficient = true;			
							$this->utils->debug_log('bermar adjust_points_balance_post infufficient:', $infufficient);				
							return false;
							
						}
						$newPointBalance = round($beforePointBalance - $amount, 4);
						$action_name = 'Add manually deduct points balance.';
						break;
					case Point_transactions::MANUAL_ADD_POINTS:
						$newPointBalance = round($beforePointBalance + $amount, 4);
						$action_name = 'Add manually add points balance.';
						break;
				}
				
				//add point transaction record
				$success = $this->point_transactions->createPointTransaction($adminUserId, $player_id, $amount, $beforePointBalance, $newPointBalance, null, null, $adjustment_type);
				if(!$success){
					return false;
				}
				$this->utils->debug_log('bermar adjust_points_balance_post createPointTransaction:', $success);
				//update overall points
				$success = $this->player_model->updatePlayerPointBalance($player_id, $newPointBalance);
				if(!$success){
					return false;
				}
				$this->utils->debug_log('bermar adjust_points_balance_post updatePlayerPointBalance:', $success);

				$this->utils->debug_log('bermar adjust_points_balance_post end lock');
				return true;
			});
		}
		$this->utils->debug_log('bermar adjust_points_balance_post success:', $success);
			
		if(!$success){
			if($infufficient){
				$message = lang('con.insufficientBalance');
				$this->utils->debug_log('bermar adjust_points_balance_post infufficient:', $infufficient);
				$this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
			}
		}else{
			$this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('con.pym09'));
		}
		redirect('payment_management/adjust_balance/' . $player_id);
	}

	

}
