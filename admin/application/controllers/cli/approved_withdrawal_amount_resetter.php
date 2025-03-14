<?php
// if (PHP_SAPI === 'cli') {
// 	exit('No web access allowed');
// }

class Approved_withdrawal_amount_resetter extends CI_Controller {

	function __construct() {
		parent::__construct();
		$this->load->library('user_functions');
	}

	function index() {
		$this->resetApprovedWithdrawAmount();
	}

	function resetApprovedWithdrawAmount() {
		$this->user_functions->resetApprovedWithdrawal(array(
			'approvedWidAmt' => 0,
			'cs0approvedWidAmt' => 0,
			'cs1approvedWidAmt' => 0,
			'cs2approvedWidAmt' => 0,
			'cs3approvedWidAmt' => 0,
			'cs4approvedWidAmt' => 0,
			'cs5approvedWidAmt' => 0,
		));
	}
}
