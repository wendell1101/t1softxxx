<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yongpay.php';

/**
 * YONGPAY
 *
 * * YONGPAY_WITHDRAWAL_PAYMENT_API, ID: 657
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://client.yongpay.com/
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yongpay_withdrawal extends Abstract_payment_api_yongpay {
	const CALLBACK_STATUS_SUCCESS = 1;

	public function getPlatformCode() {
		return YONGPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yongpay_withdrawal';
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
		$this->utils->debug_log("===============================Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
		if(!empty($playerBankDetails)){
            $province   = $playerBankDetails['province'];
            $city       = $playerBankDetails['city'];
            $bankBranch = $playerBankDetails['branch'];
		}
        $province   = empty($province) ? "无" : $province;
        $city       = empty($city) ? "无" : $city;
        $bankBranch = empty($bankBranch) ? "无" : $bankBranch;

		# look up bank code
		$bankInfo = $this->getBankInfo();

        $params = array();
        $params['merchant'] = $this->getSystemInfo("account");
        $params['withdrawId'] = $transId;
        $params['drawAmount'] = $this->convertAmountToCurrency($amount);
        $params['drawAccountNo']      = $accNum;
        $params['drawName']           = urlencode($name);
        $params['drawBankName']       = urlencode($bankInfo[$bank]['name']);
        $params['drawBankId']         = $bankInfo[$bank]['code'];
        $params['drawBankBranchName'] = urlencode($bankBranch);
        $params['noticeUrl']          = $this->getNotifyUrl($transId);
        $params['state']              = $province;
        $params['city']               = $city;

		$sign_key = array(
			'withdrawId', 'drawAccountNo', 'drawName', 'drawBankId', 'key', 'noticeUrl', 'drawAmount'
		);
		$params['md5Info'] = $this->sign_md5info($params, $sign_key);

		$sign_key = array(
			'merchant', 'withdrawId', 'drawAmount', 'key', 'drawAccountNo', 'noticeUrl', 'drawName'
		);
        $params['sign'] = $this->sign($params, $sign_key);

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
            $this->utils->error_log("========================yongpay withdrawal bank whose bankTypeId=[$bank] is not supported by yongpay");
            return array('success' => false, 'message' => 'Bank not supported by yongpay');
            $bank = '无';
        }

		$params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
		$url = $this->getSystemInfo('url');

		$postString = http_build_query($params);
		$curlConn = curl_init($url);
		curl_setopt($curlConn, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curlConn, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curlConn, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlConn, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlConn, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curlConn, CURLOPT_POSTFIELDS, $postString);

		$this->setCurlProxyOptions($curlConn);

		$result['result'] = curl_exec($curlConn);
		$result['success'] = (curl_errno($curlConn) == 0);
		$result['message'] = curl_error($curlConn);
		$this->utils->debug_log("===============================yongpay withdrawal postString", $postString, "curl result", $result);
		curl_close($curlConn);


		$decodedResult = $this->decodeResult($result['result']);
		$this->utils->debug_log("===============================yongpay withdrawal decoded Result", $decodedResult);
		return $decodedResult;

	}

	public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $result_array = $result;
		if($queryAPI) {
			$this->utils->debug_log("=========================yongpay checkWithdrawStatus decoded result string", $result_array);
		}
		else {
			$this->utils->debug_log("=========================yongpay withdrawal decoded result string", $result_array);
		}

		if(array_key_exists("status", $result)) {
			if ($result_array['status'] == '2'){
				$message = 'yongpay payment response successful, result: '.$result_array['status'];
				return array('success' => true, 'message' => $message);
			}
		} else {
			$this->errMsg = 'yongpay payment failed for ['.$resultString.']'.$result_array['message'];
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
            $this->utils->debug_log("=========================yongpay bank info from extra_info: ", $bankInfo);
        } else {
			$bankInfo = array(
				'1' => array('name' => '中国工商银行', 'code' => 'icbc'),
				'2' => array('name' => '招商银行', 'code' => 'cmb'),
				'3' => array('name' => '中国建设银行', 'code' => 'ccb'),
				'4' => array('name' => '中国农业银行', 'code' => 'abc'),
				'5' => array('name' => '交通银行', 'code' => 'comm'),
				'6' => array('name' => '中国银行', 'code' => 'boc'),
				'8' => array('name' => '广东发展银行', 'code' => 'gdb'),
				'10' => array('name' => '中信银行', 'code' => 'ecitic'),
				'11' => array('name' => '民生银行', 'code' => 'cmbc'),
				'12' => array('name' => '中国邮政储蓄', 'code' => 'post'),
				'13' => array('name' => '兴业银行', 'code' => 'cib'),
				'14' => array('name' => '华夏银行', 'code' => 'hxb'),
				'15' => array('name' => '平安银行', 'code' => 'pingan'),
				'20' => array('name' => '光大银行', 'code' => 'ceb'),
				'24' => array('name' => '浦发银行', 'code' => 'spdb'),
			);
			$this->utils->debug_log("=======================getting yongpay bank info from code: ", $bankInfo);
		}
		return $bankInfo;
	}

	public function checkWithdrawStatus($transId) {
		$param = array();
		$param['merchant'] = $this->getSystemInfo("account");
		$params['withdrawId'] = $transId;

		$sign_key = array(
			'merchant', 'withdrawId'
		);
		$param['sign'] = $this->sign($param, $sign_key);

		$url = $this->getSystemInfo('url');
		$response = $this->submitGetForm($url, $param);
		$decodedResult = $this->decodeResult($response, true);

		return $decodedResult;
    }

	public function callbackFromServer($transId, $params) {
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($result['status'] == '2') {
            $result['success'] = true;
            $result['message'] = 'Yongpay withdrawal payment was successful: trade ID ['.$params['withdrawId'].']';
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $result['message']);
       	} else {
            $result['message'] = 'Yongpay withdrawal payment was not successful: trade ID ['.$result['withdrawId'].']';
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array('merchant', 'withdrawId', 'serialId', 'status', 'amount', 'recordDate', 'accountNo', 'accountName', 'accountBankName', 'accountBranchBankName', 'state', 'city', 'sign');
        foreach ($requiredFields as $f) {
        	if (!array_key_exists($f, $fields)) {
        		$this->writePaymentErrorLog("======================bojimart withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
        		return false;
        	}
        }

        if ($fields["sign"] != $this->validateSign($fields)) {
        	$this->writePaymentErrorLog('=========================bojimart withdrawal checkCallback signature Error', $fields);
        	return false;
        }

        # everything checked ok
        return true;
    }

	# -- signing --
	public function sign($data,$sign_key) {
		$data['key'] = $this->getSystemInfo('key');
		$signStr = $this->createSignStr($data,$sign_key);
		$sign = strtoupper(md5($signStr));
		return $sign;
	}

	public function sign_md5info($data,$sign_key) {
		$data['key'] = $this->getSystemInfo('key');
		$signStr = $this->createSignStr($data,$sign_key);
		$sign = strtolower(md5($signStr));
		return $sign;
	}

	public function createSignStr($params,$sign_key) {
        $signStr = "";
        foreach ($sign_key as $key) {
			if (array_key_exists($key, $params)) {
				$signStr .= $params[$key];
			}
		}
		return $signStr;
	}

    public function validateSign($params) {
        $params['key'] = $this->getSystemInfo('key');

        $params_keys = array(
            'merchant', 'withdrawId', 'serialId', 'status', 'amount', 'recordDate', 'accountBankName', 'accountName', 'accountNo', 'state', 'city', 'key'
        );

        $signStr = $this->createSignStr($params,$params_keys);
        $sign = strtoupper(md5($signStr));
        return $sign;
    }
}
