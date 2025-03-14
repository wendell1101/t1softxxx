<?php
//always include base testing
require_once dirname(__FILE__) . '/base_testing.php';

//always extends from BaseTesting
class Testing_model_withdraw_condition extends BaseTesting {

	//should overwrite init function
	public function init() {
		//init your model or lib
		$this->load->model('withdraw_condition');
		// $this->lang->load('main', 'chinese');
	}
	//should overwrite testAll
	public function testAll() {
		//init first
		$this->init();
		//call your test function
		// $this->testGetBankTypeTree();
	}

	public function testTarget($methodName) {
		$this->init();
		$this->$methodName();
	}

	private function testTotalBalance() {
		$playerId = 112;
		$this->load->model('wallet_model');
		$bal = $this->wallet_model->getTotalBalance($playerId);
		echo $bal;
	}

	//it's your real test function
	private function testCheckAndCleanWithdrawCondition() {
		// $systemId = IPS_PAYMENT_API;
		$playerId = 112;
		// $this->utils->debug_log($this->bank_list->getBankTypeTree($systemId));
		$this->withdraw_condition->checkAndCleanWithdrawCondition($playerId);
	}

	//it's your real test function
	private function testIsAccumulativeBetAmountGreaterThanWithdrawConditionAmount() {
		// SELECT * FROM `withdraw_conditions` WHERE `status` = '1' AND `wallet_type` != '0' LIMIT 50 OFFSET 400
		$withdrawConditionId = 2783564;
		//
		$withdrawConditionId = 1907088;
		// SELECT * FROM `withdraw_conditions` WHERE `status` = '1' AND `wallet_type` = '0' AND `is_finished` = '1' LIMIT 50
		$withdrawConditionId = 2107	;
		$withdrawConditionId = 2740560	;

		// $this->utils->debug_log($this->bank_list->getBankTypeTree($systemId));
		$rlt = $this->withdraw_condition->isAccumulativeBetAmountGreaterThanWithdrawConditionAmount($withdrawConditionId);
		$var_exported = var_export($rlt, true);
		echo $var_exported;
	}
}

///end of file/////////////