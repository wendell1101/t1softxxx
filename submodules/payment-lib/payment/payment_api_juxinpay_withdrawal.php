<?php
require_once dirname(__FILE__) . '/abstract_payment_api_juxinpay.php';

/**
 * JUXINPAY 聚鑫-出款
 *
 * * JUXINPAY_WITHDRAWAL_PAYMENT_API, ID: 555
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://online.atrustpay.com/payment/WithdrawApply.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_juxinpay_withdrawal extends Abstract_payment_api_juxinpay {
	const CALLBACK_STATUS_PROCESS = '1';
	const CALLBACK_STATUS_SUCCESS = '2';
	const CALLBACK_STATUS_FAILED = '3';
	const RETURN_SUCCESS_CODE = 'OK';

	public function getPlatformCode() {
		return JUXINPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'juxinpay_withdrawal';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}

	/**
	 * detail: override common API functionsh
	 *
	 * @return void
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
		return $this->returnUnimplemented();
	}

	public function processPaymentUrlForm($params) {
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
		$params = array();
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));


		//基本参数
		$params['version'] = '1.0';
		$params['agentId'] = $this->getSystemInfo('account');
		$params['agentOrderId'] = $transId;

		# look up bank code
		$bankInfo = $this->getJuxinPayBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================juxinpay withdraw bank whose bankTypeId=[$bank] is not supported by juxinpay");
			return array('success' => false, 'message' => 'Bank not supported by juxinpay');
		}

		$params['bankCode'] = $bankInfo[$bank]['code']; # bank SN mapping


		$params['payeeType'] = '0';  // 0-對私, 1-對公
		$params['payeeName']  = $name;
		$params['payeeAccount'] = $accNum;

		# look up bank detail from playerbankdetails table, using bank_type ID and accountNumber
		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
		if(!empty($playerBankDetails)){
			$params["payeeOpeningBank"] = $playerBankDetails['branch'];
			$params['province'] = $playerBankDetails['province'];
			$params['city'] = $playerBankDetails['city'];
		} else {
			$params["payeeOpeningBank"] = '无';
			$params['province'] = '无';
			$params['city'] = '无';
		}

		$params['payeeOpeningBank'] = empty($params['payeeOpeningBank']) ? "无" : $params['payeeOpeningBank'];
		$params['province'] = empty($params['province']) ? "无" : $params['province'];
		$params['city'] = empty($params['city']) ? "无" : $params['city'];
		
		$params['amount'] = $this->convertAmountToCurrency($amount);
		$params['orderTime'] = date('YmdHis');
		$params['payIp'] = $this->CI->utils->getIP();
		//$params['payIp'] = '114.32.45.138';
		$params['notifyUrl']  = $this->getNotifyUrl($transId);
		$params['remark'] = 'deposit';
		
		
	

        $params["sign"] = $this->sign($params);

		$this->utils->debug_log("========================juxinpay withdrawal submit order Params: ", $params);

		return $params;


	}

	// public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
	// 	$result = array('success' => false, 'message' => 'payment failed');
	// 	$success = false;
	// 	$message = 'payment failed';
		
	// 	if(!$this->isAllowWithdraw()) {
	// 		$result['message'] = lang("Withdraw not allowed with this API");
	// 		$this->utils->debug_log($result);
	// 		return $result;
	// 	}

	// 	$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
		
	// 	$queryStr = $this->createSignStr($params);;
	// 	$params['sign'] = $this->sign($params);
	// 	$params['signType'] = "SHA";
	// 	$this->CI->utils->debug_log('======================================atrustpay submitWithdrawRequest params: ', $params);

	// 	$url = $this->getSystemInfo('url').'agentPay/v1/batch/'.$params['merchantId'].'-'.$transId;		
	// 	$this->CI->utils->debug_log('======================================atrustpay withdrawal url: ', $url );

	// 	$postString = is_array($params) ? http_build_query($params) : $params;
	// 	$curlConn = curl_init($url);
	// 	curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
	// 	curl_setopt($curlConn, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
	// 	curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
	// 	curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
	// 	curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, true);
	// 	curl_setopt($curlConn, CURLOPT_POSTFIELDS, $postString);

	// 	$result['result'] = curl_exec($curlConn);
	// 	$result['success'] = (curl_errno($curlConn) == 0);
	// 	$result['message'] = curl_error($curlConn);
	// 	$this->utils->debug_log("===============================atrustpay withdrawal postString", $postString, "curl result", $result);
	// 	curl_close($curlConn);

	// 	$decodedResult = $this->decodeResult($result['result']);
	// 	$this->utils->debug_log("===============================atrustpay withdrawal decoded Result", $decodedResult);
		
	// 	return $decodedResult;
	// }

	public function decodeResult($resultString, $queryAPI = false) {
		$result = json_decode($resultString, true);
		$this->utils->debug_log("=========================juxinpay decoded result string", $result);

		if($result['retCode'] == '0000') {
			$message = $result['retMsg'] ;
			return array('success' => true, 'message' => $message);
		} else if($result['retMsg']) {
			$this->errMsg = '['.$result['respCode'].']: '.$result['retMsg'];
		} else {
			$this->errMsg = 'juxinpay payment failed for unknown reason';
		} 

		return array('success' => false, 'message' => $this->errMsg);
	}	

	public function getJuxinPayBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("juxinpay_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
			}
			$this->utils->debug_log("==================getting juxinpay bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'1' => array('name' =>  '工商银行', 'code' => '1'),
				'2' => array('name' =>  '招商银行', 'code' => '7'),
				'3' => array('name' =>  '建设银行', 'code' => '2'),
				'4' => array('name' =>  '农业银行', 'code' => '3'),
				'5' => array('name' =>  '交通银行', 'code' => '6'),
				'6' => array('name' =>  '中国银行', 'code' => '5'),
				'8' => array('name' =>  '广发银行', 'code' => '11'),
				'10' => array('name' => '中信银行', 'code' => '12'),
				'11' => array('name' => '民生银行', 'code' => '14'),
				'12' => array('name' => '邮储银行', 'code' => '4'),
				'13' => array('name' => '兴业银行', 'code' => '13'),
				'14' => array('name' => '华夏银行', 'code' => '10'),
				'15' => array('name' => '平安银行', 'code' => '18'),
				'20' => array('name' => '光大银行', 'code' => '8'),
			);
			$this->utils->debug_log("=======================getting juxinpay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function checkWithdrawStatus($transId) {

		$param = array();
		$param['version'] = '1.0';
		$param['agentId'] = $this->getSystemInfo("account");
		$param['agentOrderId'] = $transId;

		
		$param['sign'] = $this->checkWithdrawsign($param);

		$this->CI->utils->debug_log('====================================== juxinpay checkWithdrawStatus params: ', $param);
		$checkWithdrawURL = $this->getSystemInfo('juxinpay_checkwithdraw_url');
		
		$this->CI->utils->debug_log('====================================== juxinpay checkWithdrawStatus url: ', $checkWithdrawURL );

		$response = $this->submitGetForm($checkWithdrawURL, $param);

		$this->CI->utils->debug_log('====================================== juxinpay checkWithdrawStatus result: ', $response );

		return $this->decodeJuxinpayCheckWithdrawStatusResult($response);
	}

	public function decodeJuxinpayCheckWithdrawStatusResult($response){
        if(empty($response)){
            $this->CI->utils->debug_log('======================================juxinpay checkWithdrawStatus unknown result: ', $response);
            return [
                'success' => FALSE,
                'message' => 'Unknown response data'
            ];
        }
        
        $json_data = json_decode($response, TRUE);        
        
        if(!isset($json_data['payeeResult']) || !isset($json_data['sign'])){
			$this->utils->error_log("========================juxinpay checkWithdrawStatus response lost the necessary info.", $json_data);
            return [
                'success' => FALSE,
                'message' => 'Lost the necessary info'
            ];
        }

        
        if(!$this->verifyJuxinpayWithdrawalSign($json_data)){
			$this->utils->error_log("========================juxinpay checkWithdrawStatus response sign verify failed.", $json_data);
            return [
                'success' => FALSE,
                'message' => 'Sign verify failed'
            ];
        }
        
        $success = FALSE;
        $message = '';
        switch($json_data['payeeResult']){
            case 'SUCCESS':
                $success = TRUE;
                $message = 'Juxinpay 代付 提现成功。（Withdrawal successful）';
                break;
            case 'FAIL':
                $success = FALSE;
                $message = 'Juxinpay 代付 失敗。（Withdrawal fail';
                break;
            case 'TREATMENT':
                $success = FALSE;
                $message = 'Juxinpay 代付 银行处理中。（Withdrawal Processing）';
                break;
            default:
                $success = FALSE;
                $message = 'Juxinpay 代付 未处理 或 无效订单。（Not processed or Invalid Order）';
                break;
        }
        
        return [
            'success' => $success,
            'message' => $message
        ];
    }

    public function callbackFromServer($transId, $params) {
		$result = array('success' => false, 'message' => 'Payment failed');
		$this->CI->utils->debug_log('process withdrawalResult order id', $transId);

		$this->utils->debug_log('=========================juxinpay process withdrawalResult order id', $transId);

		$this->utils->debug_log("=========================juxinpay checkCallback params", $params);		

		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		$raw_xml_data = file_get_contents("php://input");
        $this->utils->debug_log('=========================juxinpay process withdrawalResult raw_xml_data id', $raw_xml_data);

        $xmldata = $this->parseResultXML($raw_xml_data);

        $result_xmldata_params = array();
        if( isset($xmldata["RESPONSE_BODY"]["PAY_ITEM"]) ){
            foreach( $xmldata["RESPONSE_BODY"]["PAY_ITEM"] as $k => $v ){
                $result_xmldata_params[$k] = $v;
            }
        }

        if( isset($xmldata["RESPONSE_HEADER"]) ){
            foreach( $xmldata["RESPONSE_HEADER"] as $k => $v ){
                $result_xmldata_params[$k] = $v;
            }
        }

         $this->utils->debug_log('=========================juxinpay process withdrawalResult result_xmldata_params', $result_xmldata_params);

		if (!$this->checkCallbackOrder($order, $result_xmldata_params)) {
			return $result;
		}

		//process
		if ($result_xmldata_params['PAYEE_STATUS'] == self::CALLBACK_STATUS_PROCESS) {
			$msg = sprintf('Withdrawal Processing: status code [%s]', $result_xmldata_params['PAYEE_STATUS']);

			$result['message'] = $msg;
		} 

		//success
		if ($result_xmldata_params['PAYEE_STATUS'] == self::CALLBACK_STATUS_SUCCESS) {		
			$msg = 'Withdrawal successful' ; 

			$this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

			$result['message'] = $msg;
			$result['success'] = true;
		}

		//failed
		if ($result_xmldata_params['PAYEE_STATUS'] == self::CALLBACK_STATUS_FAILED) {
			$msg = sprintf('Withdrawal fail: status code [%s]', $result_xmldata_params['PAYEE_STATUS']);
			$this->writePaymentErrorLog($msg, $fields);
			$this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
			$result['message'] = $msg;
		} 

		$result['reset_msg'] = self::RETURN_SUCCESS_CODE;

		return $result;
	}

	private function checkCallbackOrder($order, $fields) {
		# does all required fields exist in the header?
		$requiredFields = array(
			'RET_CODE', 'RET_MSG', 'AGENT_ID', 'JKH_BATCH_NO', 'STATUS', 'BATCH_NO', 'BATCH_AMT', 'BATCH_NUM',  'EXT_PARAM1',  'SIGN', 'ORDER_ID', 'PAYEE_NAME', 'PAYEE_ACCOUNT', 'AMOUNT', 'PAYEE_STATUS'
		);
		foreach ($requiredFields as $f) {
			if (!array_key_exists($f, $fields)) {
				$this->writePaymentErrorLog("======================juxinpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
				return false;
			}
		}

		if (!$this->verifyJuxinpayCheckCallbackSign($fields)) {
			$this->writePaymentErrorLog('=========================juxinpay withdrawal checkCallback signature Error', $fields);
			return false;
		}	

		if ($this->convertAmountToCurrency($fields['BATCH_AMT']) != $this->convertAmountToCurrency($order['amount']) ) {
			$this->writePaymentErrorLog("======================juxinpay withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
			return false;
		}

		if ($fields['BATCH_NO'] != $order['transactionCode']) {
			$this->writePaymentErrorLog("======================juxinpay withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
			return false;
		}

		# everything checked ok
		return true;
	}

	public function parseResultXML($resultXml) {
        $result = NULL;
		$obj=simplexml_load_string($resultXml);
		$arr=$this->CI->utils->xmlToArray($obj);
		$this->CI->utils->debug_log(' =========================juxinpay process withdrawalResult parseResultXML', $arr);
        $result = $arr;

        return $result;
	}

	public function callbackFromBrowser($transId, $params) {
		return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
	}


	# After payment is complete, the gateway will invoke this URL asynchronously
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}


	/**
	 * detail: Format the amount value for the API
	 *
	 * @param float $amount
	 * @return float
	 */
	protected function convertAmountToCurrency($amount) {
		return number_format($amount, 2, '.', '');
	}

	public function verifyJuxinpayCheckCallbackSign($params){
        if(!isset($params['SIGN'])){
            return FALSE;
        }
             
        $keys = array('AGENT_ID', 'BATCH_AMT', 'BATCH_NO', 'BATCH_NUM', 'JKH_BATCH_NO', 'STATUS');
		
		$signStr = '';
		foreach($keys as $key) {
			$signStr .=  $key . '=' . $params[$key] . '&';
		}
		$signStr .= 'KEY='. $this->getSystemInfo('key');
		$signStr .= '&RET_CODE='. $params['RET_CODE'];
		$sign = md5($signStr);


        return (strtolower($params['SIGN']) === strtolower($sign)) ? TRUE : FALSE;
    }
	

	public function verifyJuxinpayWithdrawalSign($params){
        if(!isset($params['sign'])){
            return FALSE;
        }
             
        $keys = array('version', 'agentId', 'agentOrderId', 'jnetOrderId', 'amount', 'payeeResult', 'payeeName', 'payeeAccount');
		$signStr = '';
		foreach($keys as $key) {
			$signStr .= $params[$key].'|';
		}
		$signStr .= $this->getSystemInfo('key');
		$sign = md5($signStr);


        return (strtoupper($params['sign']) === strtoupper($sign)) ? TRUE : FALSE;
    }
	

	/**
	 * MD5 加签
	 * @param $params
	 * @param $secretKey
	 */
	public function sign($params){

		$keys = array('version', 'agentId', 'agentOrderId', 'bankCode', 'payeeType', 'payeeName', 'payeeAccount', 'amount', 'notifyUrl');
		$signStr = '';
		foreach($keys as $key) {
			$signStr .= $params[$key].'|';
		}
		$signStr .= $this->getSystemInfo('key');
		$sign = md5($signStr);
		
		return $sign;
	}

	public function checkWithdrawsign($params){

		$keys = array('version', 'agentId', 'agentOrderId');
		$signStr = '';
		foreach($keys as $key) {
			$signStr .= $params[$key].'|';
		}
		$signStr .= $this->getSystemInfo('key');
		$sign = md5($signStr);
	
		return $sign;
	}
	
}
