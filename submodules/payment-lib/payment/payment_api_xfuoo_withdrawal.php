<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xfuoo.php';

/**
 * XFUOO 信付通-出款
 *
 * * XFUOO_WITHDRAWAL_PAYMENT_API, ID: 384
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://client.xfuoo.com/
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xfuoo_withdrawal extends Abstract_payment_api_xfuoo {
	const CALLBACK_STATUS_SUCCESS = 1;

	public function getPlatformCode() {
		return XFUOO_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xfuoo_withdrawal';
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
		$this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
		if(!empty($playerBankDetails)){
			$province      = $playerBankDetails['province'];
			$city          = $playerBankDetails['city'];
			$bankBranch    = $playerBankDetails['branch'];
			$bankSubBranch = $playerBankDetails['branch'];
		}
		$province      = empty($province) ? "无" : $province;
		$city          = empty($city) ? "无" : $city;
		$bankBranch    = empty($bankBranch) ? "无" : $bankBranch;
		$bankSubBranch = empty($bankSubBranch) ? "无" : $bankSubBranch;

		$bankInfo = $this->getBankInfo();

		$params = array();
		$params = [
			"merchantId" => $this->getSystemInfo("account"),
			"batchVersion" => "00",
			"batchBiztype" => "00000",
			"batchDate" => date("Ymd"),
			"batchNo" => $transId,
			"charset" => "utf8"
		];

		$accountType     = '0';
		$idType          = '身份证';
		$idNo            = "123456789";
		$licenseNo       = '123456';
		$remark          = "Withdrawal";

		$batchContent = [
			$accNum,
			$name,
			$bankInfo[$bank]['name'],
			$bankBranch,
			$bankSubBranch ,
			$accountType,
			$amount,
			'CNY',
			$province,
			$city,
			$this->getBankMobileNo(),
			$idType,
			$idNo,
			$licenseNo,
			$transId,
			$remark
		];

		$string = "1";
		foreach ($batchContent as $value) {
  			$string .= ',' . $value;
		}

		$batchContent = $string;
		$params['batchContent'] = $batchContent;
		$params['sign'] = $this->sign($params);
		$params['signType'] = "SHA";

		return $params;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
		$result = array('success' => false, 'message' => 'payment failed');
		$success = false;
		$message = 'payment failed';

		if(!$this->isAllowWithdraw()) {
			$result['message'] = lang("Withdraw not allowed with this API");
			$this->utils->debug_log($result);
			return $result;
		}

		# look up bank code
		$bankInfo = $this->getBankInfo();
		if(!array_key_exists($bank, $bankInfo)) {
			$this->utils->error_log("========================xfuoo withdrawal bank whose bankTypeId=[$bank] is not supported by xfuoo");
			return array('success' => false, 'message' => 'Bank not supported by xfuoo');
			$bank = '无';
		}

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
		$url = $this->getSystemInfo('url').'agentPay/v1/batch/'.$params['merchantId'].'-'.$transId;
		$this->CI->utils->debug_log('======================================xfuoo withdrawal url: ', $url );

		$postString = is_array($params) ? http_build_query($params) : $params;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);

        $this->setCurlProxyOptions($ch);

		$response    = curl_exec($ch);
		$errCode     = curl_errno($ch);
		$error       = curl_error($ch);
		$statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$content     = substr($response, $header_size);

		curl_close($ch);

		$this->submitPreprocess($params, $content, $url, $response, array('errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode), $params['batchNo']);
		$decodedResult = $this->decodeResult($response);
		$this->utils->debug_log("===============================xfuoo submitWithdrawRequest response", $response);
		$this->utils->debug_log("===============================xfuoo submitWithdrawRequest decoded Result", $decodedResult);

		return $decodedResult;
	}

	public function decodeResult($resultString, $queryAPI = false) {
		$result = json_decode($resultString, true);

		if($result['respCode'] == 'S0001') {
			$message = 'xfuoo payment response successful, respCode: '.$result['respCode'];

			if($queryAPI) {	//checkWithdrawStatus result
				$success = false;

				$batchContent = $result['batchContent'];
				$batchContentArr = explode(",", $batchContent);

				$this->utils->debug_log("=========================xfuoo checkWithdrawStatus batchContentArr", $batchContentArr);

				$tradeNum =          array_key_exists(0  , $batchContentArr) ? $batchContentArr[0]  : "default_empty_string";
				$tradeCustorder =    array_key_exists(1  , $batchContentArr) ? $batchContentArr[1]  : "default_empty_string";
				$tradeCardnum =      array_key_exists(2  , $batchContentArr) ? $batchContentArr[2]  : "default_empty_string";
				$tradeCardname =     array_key_exists(3  , $batchContentArr) ? $batchContentArr[3]  : "default_empty_string";
				$tradeBranchbank =   array_key_exists(4  , $batchContentArr) ? $batchContentArr[4]  : "default_empty_string";
				$tradeSubbranchban = array_key_exists(5  , $batchContentArr) ? $batchContentArr[5]  : "default_empty_string";
				$tradeAccountname =  array_key_exists(6  , $batchContentArr) ? $batchContentArr[6]  : "default_empty_string";
				$tradeAccounttype =  array_key_exists(7  , $batchContentArr) ? $batchContentArr[7]  : "default_empty_string";
				$tradeAmount =       array_key_exists(8  , $batchContentArr) ? $batchContentArr[8]  : "default_empty_string";
				$tradeAmounttype =   array_key_exists(9  , $batchContentArr) ? $batchContentArr[9]  : "default_empty_string";
				$tradeRemark =       array_key_exists(10 , $batchContentArr) ? $batchContentArr[10] : "default_empty_string";
				$contractUsercode =  array_key_exists(11 , $batchContentArr) ? $batchContentArr[11] : "default_empty_string";
				$tradeFeedbackcode = array_key_exists(12 , $batchContentArr) ? $batchContentArr[12] : "default_empty_string";
				$tradeReason =       array_key_exists(13 , $batchContentArr) ? $batchContentArr[13] : "default_empty_string";

				switch ($tradeFeedbackcode) {
					case 'null':
						$message = 'XFUOO_WITHDRAWAL(信付通代付) 处理中, 商户订单号: ['. $tradeCustorder .']';
						break;

					case '成功':
						$success = true;
						$message = 'XFUOO_WITHDRAWAL(信付通代付) 成功, 商户订单号: ['. $tradeCustorder .']';
						break;

					case '失败':
						$message = 'XFUOO_WITHDRAWAL(信付通代付) 失敗, 原因: ['. $tradeReason .'], 商户订单号: ['. $tradeCustorder .']';
						break;

					default:
						$message = 'xfuoo payment check withdraw status and got an unknown tradeFeedbackcode, tradeFeedbackcode: '.$tradeFeedbackcode;
						break;
				}

				return array('success' => $success, 'message' => $message);
			}

			return array('success' => true, 'message' => $message);
		} else if(isset($result['respMessage'])) {
			$this->errMsg = '['.$result['respCode'].']: '.$result['respMessage'];
		} else if(isset($result['error'])) {
			$this->errMsg = '['.$result['error'].']: '.$result['message'];
		} else {
			$this->errMsg = 'xfuoo payment failed for unknown reason';
		}

		return array('success' => false, 'message' => $this->errMsg);
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
            $this->utils->debug_log("=========================xfuoo bank info from extra_info: ", $bankInfo);
        } else {
			$bankInfo = array(
				'1' => array('name' => '中国工商银行', 'code' => 'ICBC'),
				'2' => array('name' => '招商银行', 'code' => 'CMB'),
				'3' => array('name' => '中国建设银行', 'code' => 'CCB'),
				'4' => array('name' => '中国农业银行', 'code' => 'ABC'),
				'5' => array('name' => '交通银行', 'code' => 'BOCM'),
				'6' => array('name' => '中国银行', 'code' => 'BOC'),
				'8' => array('name' => '广发银行', 'code' => 'CGB'),
				'10' => array('name' => '中信银行', 'code' => 'CITIC'),
				'11' => array('name' => '民生银行', 'code' => 'CMBC'),
				'12' => array('name' => '中国邮政储蓄', 'code' => 'PSBC'),
				'13' => array('name' => '兴业银行', 'code' => 'CIB'),
				'14' => array('name' => '华夏银行', 'code' => 'HXB'),
				'15' => array('name' => '平安银行', 'code' => 'PAYH'),
				'20' => array('name' => '光大银行', 'code' => 'CEB'),
				'24' => array('name' => '浦发银行', 'code' => 'SPDB')
			);
			$this->utils->debug_log("=======================getting xfuoo bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function checkWithdrawStatus($transId) {
		$this->CI->load->model(array('wallet_model'));
		$walletaccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

		$dateTimeString = $walletaccount['processDatetime'];
		$datetime = new DateTime($dateTimeString);

		# ---- First add bank card entry ----
		$param = array();
		$param['batchDate'] = $datetime->format('Ymd');
		$param['batchNo'] =  $transId;
		$param['batchVersion'] = "00";
		$param['charset'] = "utf8";
		$param['merchantId'] = $this->getSystemInfo("account");
		$param['sign'] = $this->sign($param);
		$param['signType'] = "SHA";

		$url = $this->getSystemInfo('url').'agentPay/v1/batch/'.$param['merchantId'].'-'.$transId;
		$response = $this->submitGetForm($url, $param, false, $transId);
		$decodedResult = $this->decodeResult($response, true);

		return $decodedResult;
	}

	public function getBankMobileNo() {
		$headNum = array("135", "139");
		$k = array_rand($headNum);
		$num = $headNum[$k].mt_rand(10000000, 99999999);

		return $num;
	}
}
