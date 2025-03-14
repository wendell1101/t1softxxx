<?php
require_once dirname(__FILE__) . '/abstract_payment_api_hrpay.php';

/**
 *
 * HrPay Withdrawal
 *
 * HRPAY_PAYMENT_API_WITHDRAWAL, ID: 261
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * Field Values:
 *
 * * Extra Info
 * > {
 * >     "hrpay_company_id" : "## company id ##",
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_hrpay_withdrawal extends Abstract_payment_api_hrpay {
	const CALLBACK_STATUS_SUCCESS = 2000;

	public function getPlatformCode() {
		return HRPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'hrpay_withdrawal';
	}

	# Ref: Documentation page 1
	protected function getPageCode() {
		return parent::PAGECODE_WITHDRAWAL;
	}

	# get selected bank id
	protected function configParams(&$params, $direct_pay_extra_info) {

	}

	/**
	 * detail: override common API functions
	 *
	 * @return void
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		return $this->returnUnimplemented();
	}

	/**
	 * detail: hrpay withdraw callback implementation
	 *
	 * @param int $transId transaction id
	 * @param int $paramsRaw
	 * @return array
	 */
	public function callbackFromServer($transId, $params) {
		$this->CI->utils->debug_log('==============hrpay process withdrawalResult order id', $transId);
		$result = array('success' => false, 'message' => 'Payment failed');

		$walletAccount=$this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		$decryptedString = $this->decrypt($params);

		$decodedResult = json_decode(urldecode($decryptedString), true);

		if(is_array($decodedResult) && count($decodedResult)==1){
			$this->utils->debug_log("===============hrpay withdraw decoded Result", $decryptedString, $decodedResult);

			$ret = $decodedResult[0];

			if($ret['v_result'] == self::CALLBACK_STATUS_SUCCESS) {
				$msg = sprintf('=================hrpay withdraw payment was successful');
				$fee = 0; # Fee is not specified by this API
				$amount = $this->convertAmountToCurrency($ret['v_amount']);

				$this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg, $fee, $amount);
				$result = ['success' => true, 'message' => self::RETURN_SUCCESS_CODE];
			}
			else {
				$this->utils->debug_log("===============hrpay withdraw decoded Result v_result not 2000 but ", $ret['v_result'], $decodedResult);
				$result = ['success' => false, 'message' => 'decoded successfully but withdraw failed'];
			}
		}
		else {
			$this->utils->debug_log("===============hrpay withdraw decoded Result failed", $params);

			$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, '==============hrpay withdraw decoded Result failed');
			$result = ['success' => false, 'message' => 'decoded Result failed'];	
		}


		if($walletAccount['amount'] < $params['amount']){
			$result = ['success'=>false, 'return_error'=> json_encode(['status'=>0,'error_msg'=>'wrong amount , must <= '.$walletAccount['amount']])];
			
		}

		return $result;
	}

}
