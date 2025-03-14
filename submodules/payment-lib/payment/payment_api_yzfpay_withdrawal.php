<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yzfpay.php';

/**
 * Yzfpay取款
 *
 * * YZFPAY_WITHDRAWAL_PAYMENT_API, ID: 5168
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://60.172.1.136:9002/yzf/WS/PayWS.asmx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yzfpay_withdrawal extends Abstract_payment_api_yzfpay {
	const CALLBACK_SUCCESS = '1';
	const OPTTYPE_WITHDRAWAL = '1000'; //银联提现

	public function getPlatformCode() {
		return YZFPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yzfpay_withdrawal';
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
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

		$playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
		$this->utils->debug_log("============================yzfpay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
		if(!empty($playerBankDetails)){
			$province      = $playerBankDetails['province'];
			$city          = $playerBankDetails['city'];
			$bankBranch    = $playerBankDetails['branch'];
		}
		$province      = empty($province) ? "无" : $province;
		$city          = empty($city) ? "无" : $city;
		$bankBranch    = empty($bankBranch) ? "无" : $bankBranch;


		# look up bank code
		$bankInfo = $this->getBankInfo();

		$params = array();
		$params['OrgNo'] = $this->getSystemInfo("account");
		$params['OptType'] = $this->getSystemInfo("opttype", self::OPTTYPE_WITHDRAWAL);
		$params['Code'] = 'yzfdaifu'; #亿支付代付,需提交收款账号信息
		$params['TradNo'] = $transId.'1234567890';
		$params['PayMoney'] = $this->convertAmountToCurrency($amount);
		$params['ExtObj1'] = (object)array(
			'AccountNo'          => $accNum,
			'AccountName'        => $name,
			'BankName'           => $bankInfo[$bank]['name'],
			'BankNo'             => $bankInfo[$bank]['code'],
			'BankBranchName'     => $bankBranch,
			'BankBranchNo'       => '102361000000',
			'BankBranchProvince' => $province, #省
			'BankBranchCity'     => $city,
			'BankCardCertNo'     => '340521197204090000',
			'BankCardMobile'     => '18856930000'
		);

        $params['SecretValue'] = $this->sign($params);
		$this->CI->utils->debug_log('=========================yzfpay withdrawal paramStr before sign', $params);
		return $params;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'payment failed');
		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		$bankInfo = $this->getBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================yzfpay withdrawal bank whose bankTypeId=[$bank] is not supported by yzfpay");
			return array('success' => false, 'message' => 'Bank not supported by yzfpay');
			$bank = '无';
		}

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
    	$url = $this->getSystemInfo('url').'/PayRecordOpt';

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);
        $this->CI->utils->debug_log('=====================yzfpay submitWithdrawRequest received response', $response);
        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

		return $decodedResult;

	}

	public function decodeResult($resultString, $queryAPI = false) {
		if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

      	preg_match('/(<string xmlns="http:\/\/www\.sys\.com\/">)([\s\S]*)<\/string>/', $resultString, $xmlexplo);
	    $json_decXmlexplo = json_decode($xmlexplo['2'],true);

		if($queryAPI) {
			$json_decResult = json_decode($json_decXmlexplo['Model']['Result'],true);
		    $this->CI->utils->debug_log('=====================yzfpay processPaymentUrlFormQRcode json_decResult', $json_decResult);
	        $returnOrderId = $json_decXmlexplo['Model']['TradNo'];
	        $returnStatus  = $json_decResult['Status'];
	        $returnSuccess = $json_decResult['RespMsg'];
	        if(!empty($returnOrderId) && $returnStatus == '1' && $returnSuccess == 'success'){
	            $message = "yzfpay withdrawal success orderId:".$returnOrderId.", Status: ".$returnStatus.",RespMsg : ".$returnSuccess ;
				return array('success' => true, 'message' => $message);

	        }else{
				$message = "yzfpay withdrawal failed orderId:".$returnOrderId.", Status: ".$returnStatus.",RespMsg : ".$returnSuccess;
				return array('success' => false, 'message' => $message);
			}
		}
		else {
		    $returnOrderId = $json_decXmlexplo['Model']['TradNo'];
		    $returnMsg     = $json_decXmlexplo['Msg'];
	        $returnStatus  = $json_decXmlexplo['State'];
	        if(!empty($returnMsg) && $returnStatus == '1'){
	            $message = "yzfpay withdrawal success Msg:".$returnMsg.", State: ".$returnStatus."transId: ".$returnOrderId;
				return array('success' => true, 'message' => $message);

	        }else{
				$message = "yzfpay withdrawal failed Msg:".$returnMsg.", State: ".$returnStatus;
				return array('success' => false, 'message' => $message);
			}

		}
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
            $this->utils->debug_log("=========================yzfpay bank info from extra_info: ", $bankInfo);
        } else {
			$bankInfo = array(
				'1' => array('name' => '中国工商银行', 'code' => '1021000'),
				'2' => array('name' => '招商银行', 'code' => '3085840'),
				'3' => array('name' => '中国建设银行股份有限公司', 'code' => '1051000'),
				'4' => array('name' => '中国农业银行', 'code' => '1031000'),
				'5' => array('name' => '交通银行', 'code' => '3011000'),
				'6' => array('name' => '中国银行股份有限公司', 'code' => '1041000'),
				'8' => array('name' => '广发银行股份有限公司', 'code' => '3065810'),
				'10' => array('name' => '中信银行', 'code' => '3021000'),
				'11' => array('name' => '中国民生银行', 'code' => '3051000'),
				'12' => array('name' => '中国邮政储蓄银行有限责任公司', 'code' => '0025840'),
				'13' => array('name' => '兴业银行', 'code' => '3091000'),
				'14' => array('name' => '华夏银行', 'code' => '3041000'),
				'15' => array('name' => '深圳平安', 'code' => '3135840'),
				'17' => array('name' => '广州银行股份有限公司', 'code' => '3135842'),
				'20' => array('name' => '中国光大银行', 'code' => '3031000'),
				'24' => array('name' => '上海浦东发展银行', 'code' => '3102900'),
			);
			$this->utils->debug_log("==================getting yzfpay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function checkWithdrawStatus($transId) {
        $params = array();
        $params['OrgNo'] = $this->getSystemInfo('account');
        $params['OptType'] = self::OPTTYPE_WITHDRAWAL;
        $params['No'] = '';
        $params['MemberNo'] = '';
        $params['TradNo'] = $transId.'1234567890';

		$url = $this->getSystemInfo('url').'/PayRecordDetail';
		$response = $this->submitGetForm($url, $params, true, $transId);
		$decodedResult = $this->decodeResult($response, true);
		return $decodedResult;
    }

	public function callbackFromServer($transId, $params) {
    }

    public function checkCallbackOrder($order, $fields) {
    }

	public function sign($params) {
		$Today = date('Ymd');
		$signStr = $params['OrgNo'].$Today.$this->getSystemInfo('key');
        $sign = strtoupper(md5($signStr));
		return $sign;
	}

	public function verifySignature($data) {
	    $callback_sign = $data['sign'];
        $signStr = $this->getSystemInfo('account').$this->getSystemInfo('key');
        $sign=strtoupper(md5($signStr));
        return strcasecmp($sign, $callback_sign) === 0;
    }
}
