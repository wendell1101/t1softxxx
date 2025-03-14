<?php
require_once dirname(__FILE__) . '/abstract_payment_api_atrustpay.php';

/**
 * ATRUSTPAY 信付宝-出款
 *
 * * ATRUSTPAY_WITHDRAWAL_PAYMENT_API, ID: 487
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
class Payment_api_atrustpay_withdrawal extends Abstract_payment_api_atrustpay {
	const CALLBACK_STATUS_SUCCESS = 1;

	public function getPlatformCode() {
		return ATRUSTPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'atrustpay_withdrawal';
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
		$params['versionId']      = '1.0';
		$params['orderAmount']    = $amount * 100 ;
		$params['orderDate'] 	  = date('YmdHis');
		$params['currency'] 	  = 'RMB';
		$params['transType'] 	  = '008';
		$params['asynNotifyUrl']  = $this->getNotifyUrl($transId);
		$params['signType'] 	  = 'MD5';
		$params['merId']     	  = $this->getSystemInfo('account');
		$params['prdOrdNo']       = $transId;
		$params['receivableType'] = 'D00';
		$params['isCompay'] 	  = '0';

		$params['phoneNo'] 		  = '1234567890';
		$params['customerName']   = $name;
		$params['cerdId'] 		  = '1234567890';
		$params['acctNo'] 		  = $accNum;
		$params['outaccounttype'] = '2';
		

		# look up bank code
		$bankInfo = $this->getAtrustPayBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================atrustpay withdraw bank whose bankTypeId=[$bank] is not supported by atrustpay");
			return array('success' => false, 'message' => 'Bank not supported by atrustpay');
		}

		$params['rcvBranchCode'] = $bankInfo[$bank]['code']; # bank SN mapping

		$params['signData'] = $this->sign($params);
		

		# look up bank detail from playerbankdetails table, using bank_type ID and accountNumber
		# but if we cannot look up those info, will leave the fields blank
		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("=========================atrustpay get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

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
		$this->utils->debug_log("=========================atrustpay decoded result string", $result);

		if($result['retCode'] == '1') {
			$message = $result['retMsg']. ', order id: '.$result['prdOrdNo'] ;
			return array('success' => true, 'message' => $message);
		} else if($result['retMsg']) {
			$this->errMsg = '['.$result['respCode'].']: '.$result['retMsg'];
		} else {
			$this->errMsg = 'beeepay payment failed for unknown reason';
		} 

		return array('success' => false, 'message' => $this->errMsg);
	}	

	public function getAtrustPayBankInfo() {
		$bankInfo = array();
		$bankInfoArr = $this->getSystemInfo("atrustpay_bank_info");
		if(!empty($bankInfoArr)) {
			foreach($bankInfoArr as $bankInfoItem) {
				$bankInfo[$bankInfoItem[0]] = array('name' => $bankInfoItem[1], 'code' => $bankInfoItem[2]);
			}
			$this->utils->debug_log("==================getting atrustpay bank info from extra_info: ", $bankInfo);
		} else {
			$bankInfo = array(
				'1' => array('name' =>  '工商银行', 'code' => '102'),
				'3' => array('name' =>  '建设银行', 'code' => '105'),
				'4' => array('name' =>  '农业银行', 'code' => '103'),
				'5' => array('name' =>  '交通银行', 'code' => '301'),
				'6' => array('name' =>  '中国银行', 'code' => '104'),
				'8' => array('name' =>  '广发银行', 'code' => '306'),
				'10' => array('name' => '中信银行', 'code' => '302'),
				'11' => array('name' => '民生银行', 'code' => '305'),
				'12' => array('name' => '邮储银行', 'code' => '403'),
				'13' => array('name' => '兴业银行', 'code' => '309'),
				'14' => array('name' => '华夏银行', 'code' => '304'),
				'15' => array('name' => '平安银行', 'code' => '307'),
				'20' => array('name' => '光大银行', 'code' => '303'),
			);
			$this->utils->debug_log("=======================getting atrustpay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function checkWithdrawStatus($transId) {

		$param = array();
		$param['signType'] = 'MD5';
		$param['merId'] = $this->getSystemInfo("account");
		$param['prdOrdNo'] = $transId;

		
		$param['signData'] = $this->sign($param);

		$this->CI->utils->debug_log('====================================== atrustpay checkWithdrawStatus params: ', $param);

		$url = 'http://online.atrustpay.com/payment/OrderStatusQuery.do';
		$this->CI->utils->debug_log('====================================== atrustpay checkWithdrawStatus url: ', $url );

		$response = $this->submitGetForm($url, $param);

		$this->CI->utils->debug_log('====================================== atrustpay checkWithdrawStatus result: ', $response );

		return $this->decodeAtrustpayCheckWithdrawStatusResult($response);
	}

	public function decodeAtrustpayCheckWithdrawStatusResult($response){
        if(empty($response)){
            $this->CI->utils->debug_log('======================================atrustpay checkWithdrawStatus unknown result: ', $response);
            return [
                'success' => FALSE,
                'message' => 'Unknown response data'
            ];
        }
        
        $json_data = json_decode($response, TRUE);
        if(!is_array($json_data) || !isset($json_data['code'])){
            $this->CI->utils->debug_log('======================================atrustpay checkWithdrawStatus invalid result: ', $response);
            return [
                'success' => FALSE,
                'message' => 'Unknown response data'
            ];
        }
        
        if('1' != (int)$json_data['retCode']){
			$this->utils->error_log("========================atrustpay checkWithdrawStatus response failed", $json_data);
            return [
                'success' => FALSE,
                'message' => (isset($json_data['retMsg'])) ? sprintf('Code: %s, Msg: %s', $json_data['retCode'], $json_data['retMsg']) : 'atrustpay checkWithdrawStatus failed'
            ];
        }
        
        if(!isset($json_data['orderstatus']) || !isset($json_data['signData'])){
			$this->utils->error_log("========================atrustpay checkWithdrawStatus response lost the necessary info.", $json_data);
            return [
                'success' => FALSE,
                'message' => 'Lost the necessary info'
            ];
        }

        
        if(!$this->verifyAtrustpayWithdrawalSign($json_data)){
			$this->utils->error_log("========================atrustpay checkWithdrawStatus response sign verify failed.", $json_data);
            return [
                'success' => FALSE,
                'message' => 'Sign verify failed'
            ];
        }
        
        $success = FALSE;
        $message = '';
        switch((int)$json_data['orderstatus']){
            case 01:
                $success = TRUE;
                $message = 'Atrustpay 代付 提现成功。（Withdrawal successful）';
                break;
            case 00:
                $success = FALSE;
                $message = 'Atrustpay 代付 未支付。（Withdrawal Failed）';
                break;
            case 02:
                $success = FALSE;
                $message = 'Atrustpay 代付 银行处理中。（Withdrawal Processing）';
                break;
            case 14:
                $success = FALSE;
                $message = 'Atrustpay 代付 冻结。（Withdrawal Failed）';
                break;
            case 21:
                $success = FALSE;
                $message = 'Atrustpay 代付 银行处理中。（Withdrawal Processing）';
                break;
            case 22:
                $success = FALSE;
                $message = 'Atrustpay 代付 退还支付账户。（Withdrawal Failed）';
                break;
            default:
                $success = FALSE;
                $message = 'Atrustpay 代付 未处理 或 无效订单。（Not processed or Invalid Order）';
                break;
        }
        
        return [
            'success' => $success,
            'message' => $message
        ];
    }


	# After payment is complete, the gateway will invoke this URL asynchronously
	private function getNotifyUrl($orderId) {
		return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
	}

	public function verifyAtrustpayWithdrawalSign($params){
        if(!isset($params['signData'])){
            return FALSE;
        }
             
        $keys = array('ordamt', 'orderstatus', 'prdordtype', 'retCode', 'retMsg');
		$signStr = '';
		foreach($keys as $key) {
			$signStr .=  $key . '=' . $params[$key] . '&';
		}
		$signStr .= 'key='. $this->getSystemInfo('key');
		$sign = md5($signStr);


        return (strtoupper($params['signData']) === strtoupper($sign)) ? TRUE : FALSE;
    }
	

	/**
	 * MD5 加签
	 * @param $params
	 * @param $secretKey
	 */
	public function sign($params){
		ksort($params);
		$signStr = '';

		foreach ($params as $name => $value) {

			$signStr .= $name . '=' . $value . '&';
			
		}

		$signStr .= 'key='. $this->getSystemInfo('key');
	    $sign = strtoupper(md5($signStr));


		return $sign;
	}

	
}
