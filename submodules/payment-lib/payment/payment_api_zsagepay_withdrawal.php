<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zsagepay.php';

/**
 * ZSAGEPAY 泽圣
 *
 *
 * * ZSAGEPAY_WITHDRAWAL_PAYMENT_API, ID: 599
 *
 * Required Fields:
 *
 * * URL
 * * Account
 * * Extra Info
 *
 * Field Values:
 *
 * * TEST-URL: http://test.zsagepay.net/website/api/pay2bank.htm
 * * LIVE-URL: https://www.zsagepay.net/website/api/pay2bank.htm
 * * Account: ## partner ID ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_zsagepay_withdrawal extends Abstract_payment_api_zsagepay {
	const CALLBACK_STATUS_SUCCESS = '00';


	public function getPlatformCode() {
		return ZSAGEPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'zsagepay_withdrawal';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}
	protected function processPaymentUrlForm($params) {}

	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		return $this->returnUnimplemented();
	}

	# APIs with withdraw function need to implement these methods
	## This function returns the URL to submit withdraw request to
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

	## This function returns the params to be submitted to the withdraw URL
	## Note that $bank param is the bank_type ID in database, we compare it with the supported bank_codes by this API
	private $errMsg = 'Payment failed'; # This variable is used to store error message that's available upon submit
	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
		# look up bank code
		$bankInfo = $this->getBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================zsagepay withdrawl bank whose bankTypeId=[$bank] is not supported by zsagepay withdrawl");
			return array('success' => false, 'message' => 'Bank not supported by zsagepay withdrawl');
		}

		$params = array();
        $params['merchantCode'] = $this->getSystemInfo('account');
		$params['outOrderId'] = $transId;
        $params['totalAmount'] =$this->convertAmountToCurrency($amount);
        $params['intoCardNo'] = $accNum;
        $params['intoCardName'] = $name;
		$params['intoCardType'] = '2';
		$params['nonceStr'] = 'asdqawe';
		$params['type'] = '04';
		$params['notifyUrl'] = $this->getNotifyUrl($transId);
		$params['remark'] = "Withdrawal";
		$params['bankName'] = '';
		$params['bankCode'] = '';

		$hash_key = array(
			'bankCode','bankName','intoCardName', 'intoCardNo','intoCardType','merchantCode', 'nonceStr', 'outOrderId', 'totalAmount','type'
		);
		ksort($params);
        $params["sign"] = $this->sign($params, $hash_key);

		return $params;
	}

	public function convertAmountToCurrency($amount) {
		return number_format($amount*100, 0, '.', '');
	}

	## This function takes in the return value of the URL and translate it to the following structure
	## array('success' => false, 'message' => 'Error message')
	public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
		$this->utils->debug_log("=========================zsagepay withdrawal decoded result string", $result);

		if(is_array($result)){
            if($result['code'] == self::CALLBACK_STATUS_SUCCESS)  {
                $message = '['.$result['code'].'] : '.$result['msg'];
                return array('success' => true, 'message' => $message);
            }else {
                $this->errMsg = '['.$result['code'].'] : '.$result['msg'];
            }
        }
        else {
            $this->errMsg = '失敗';
		}

		return array('success' => false, 'message' => $this->errMsg);
	}

	public function callbackFromServer($transId, $params) {
	 	$result = array('success' => false, 'message' => 'Payment failed');

		$raw_xml_data = file_get_contents("php://input");
		$this->utils->debug_log('=========================zsagepay process withdrawalResult raw_xml_data id', $raw_xml_data);

		$result = explode('&', $raw_xml_data);
		foreach($result as $val){
			$temp_arr = explode('=',$val);
			$params[$temp_arr['0']] = $temp_arr['1'];
		}
        $result = $params;

	 	$this->utils->debug_log("=========================zsagepay checkCallback params", $params);
	 	$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

	 	if (!$this->checkCallbackOrder($order, $params)) {
	 		return $result;
	 	}

	 	if($result['state'] == self::CALLBACK_STATUS_SUCCESS) {
	 		$msg = sprintf('Zsagepay withdrawal payment was successful: trade ID [%s]', $params['outOrderId']);

	 		$this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
	 		$result['message'] = $msg;
	 		$result['success'] = true;
	 	} else {
	 		$this->errMsg = '['.$result['state'].']: '.urldecode($result['errorMsg']);
	 		$msg = sprintf('Zsagepay withdrawal payment was not successful: [%s]', $this->errMsg);

	 		$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
	 		$result['message'] = $msg;
	 	}

	 	return $result;
	}

	private function checkCallbackOrder($order, $fields) {
	 	# does all required fields exist in the header?
	 	$requiredFields = array('merchantCode', 'outOrderId', 'totalAmount', 'orderId', 'fee', 'sign');
	 	foreach ($requiredFields as $f) {
	 		if (!array_key_exists($f, $fields)) {
	 			$this->writePaymentErrorLog("======================zsagepay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
	 			return false;
	 		}
		 }

		$hash_key = array(
			'fee','merchantCode','orderId','outOrderId', 'state','totalAmount','transTime'
		);

	 	if (!$this->validateSign($fields, $hash_key)) {
	 		$this->writePaymentErrorLog('=========================zsagepay withdrawal checkCallback signature Error', $fields);
	 		return false;
		 }


		$checkamount = $this->convertAmountToCurrency($order['amount']);
	 	if ((string)$fields['totalAmount'] != (string)$checkamount) {
	 		$this->writePaymentErrorLog("======================zsagepay withdrawal checkCallbackOrder payment amount is wrong, expected <= ".$checkamount, $fields);
	 		return false;
	 	}

	 	# everything checked ok
	 	return true;
	}

	public function callbackFromBrowser($transId, $params) {
	 	return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
	}

	public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                if(isset($bankInfoItem['name'])){
                    $bankInfo[$system_bank_type_id]['name'] = $bankInfoItem['name'];
                }
                if(isset($bankInfoItem['code'])){
                    $bankInfo[$system_bank_type_id]['code'] = $bankInfoItem['code'];
                }
            }
            $this->utils->debug_log("=========================zsagepay bank info from extra_info: ", $bankInfo);
        } else {
			$bankInfo = array(
				'1' => array('name' => '中国工商银行', 'code' => 'ICBC'),
				'2' => array('name' => '招商银行', 'code' => 'CMB'),
				'3' => array('name' => '中国建设银行', 'code' => 'CCB'),
				'4' => array('name' => '中国农业银行', 'code' => 'ABC'),
				'5' => array('name' => '交通银行', 'code' => 'BCM'),
				'6' => array('name' => '中国银行', 'code' => 'BOC'),
				'8' => array('name' => '广发银行', 'code' => 'GDB'),
				'10' => array('name' => '中信银行', 'code' => 'CITIC'),
				'11' => array('name' => '中国民生银行', 'code' => 'CMBC'),
				'12' => array('name' => '中国邮政储蓄银行', 'code' => 'PSBC'),
				'13' => array('name' => '兴业银行', 'code' => 'CIB'),
				'15' => array('name' => '平安银行', 'code' => 'PAB'),
                '20' => array('name' => '中国光大银行', 'code' => 'CEB'),
            );

			$this->utils->debug_log("=========================zsagepay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

    public function sign($params, $hash_key) {
		ksort($hash_key);

		$signStr = '';
		$KEY_1 = $this->getSystemInfo('key');
		foreach($hash_key as $key) {
			if(array_key_exists($key, $params)) {
				$signStr .= $key."=".$params[$key] ."&";
			}
		}
		$signStr = $signStr."KEY=".$KEY_1;
		$sign = md5($signStr);
		$sign = strtoupper($sign);
		return $sign;
	}

	public function validateSign($params,$hash_key) {
        ksort($hash_key);
		$signStr = '';
		$KEY_1 = $this->getSystemInfo('key');
		foreach($hash_key as $key ) {
			if(array_key_exists($key, $params)) {
				$signStr .= $key."=".$params[$key] ."&";
			}
		}
		$signStr .= "KEY=".$KEY_1;
		$sign = md5($signStr);
		$sign = strtoupper($sign);
		return strcasecmp($params['sign'], $sign) === 0;
	}
}
