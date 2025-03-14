<?php
require_once dirname(__FILE__) . '/abstract_payment_api_today.php';

/**
 * today
 *
 * * TODAY_WITHDRAWAL_PAYMENT_API, ID: 6109
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.tdaypay.com/gateway/base/biz
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_today_withdrawal extends Abstract_payment_api_today {
	const RETURN_SUCCESS_CODE = 'ok';

	public function getPlatformCode() {
		return TODAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'today_withdrawal';
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

	public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
		$this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerInfo = $this->getPlayerInfoByTransactionCode($transId);
        $params = array(
            'mchId' => $this->getSystemInfo('account'),
            'mchOrderId' => $transId,
            'amount' => $this->convertAmountToCurrency($amount),
            'currency' => $this->getSystemInfo('currency','BRL'),
            'purpose' => 'withdrawal',
            'beneficiaryMobile' => $playerInfo['phone'],
            // 'beneficiaryBankType' => '',
            // 'beneficiaryBankCode' => '',
            // 'beneficiaryBankBranch' => '',
            'beneficiaryAccountNumber' => $playerInfo['cpfNumber'],
            'beneficiaryName' => $playerInfo['lastName'].$playerInfo['firstName'],
            'beneficiaryEmail' => $playerInfo['email'],
            'docType' => $this->getSystemInfo('docType','PIX'),
            'docNumber' => $playerInfo['cpfNumber'],
            'callbackUrl' => $this->getNotifyUrl($transId)
        );

        $params['headers'] = array(
            'serviceName' => $this->getSystemInfo('serviceName','api.pay'),
            'method'      => $this->getSystemInfo('method','payOut'),
            'mchId'       => $this->getSystemInfo('account'),
            'signType'    => $this->getSystemInfo('signType','SHA512'),
            'timestamp'   => time()
        );

		$this->CI->utils->debug_log('=========================today withdrawal paramStr before sign', $params);
		return $params;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');
        $url = $this->getSystemInfo('url');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $header = $params['headers'];
        unset($params['headers']);

        $headers = array(
            "serviceName: ".$this->getSystemInfo('serviceName','api.pay'),
            "content-type: Content-Type: application/json",
            "method: ".$this->getSystemInfo('method','payOut'),
            "mchId: ".$this->getSystemInfo('account'),
            "signType: ".$this->getSystemInfo('signType','SHA512'),
            "timestamp: ". $header['timestamp'],
            "sign: ". $this->sign($params,$header)
        );

        $this->_custom_curl_header = $headers;
        list($content, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);
        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('======================================today submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================today submitWithdrawRequest decoded Result', $decodedResult);
        return $decodedResult;

    }

	public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================today json_decode result", $result);
        if(!empty($result) && isset($result)){
            if($result['resultCode'] == self::RESPONSE_STATUS_SUCCESS){
                return array('success' => true, 'message' => 'today withdrawal request successful.');
            }else if(isset($result['errorMsg']) && !empty($result['errorMsg'])){
                $errorMsg = $result['errorMsg'];
                return array('success' => false, 'message' => $errorMsg);
            }
            else{
                return array('success' => false, 'message' => 'today withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'today withdrawal exist errors');
        }
    }

	public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================today process withdrawalResult order id', $transId);
        $this->CI->utils->debug_log("=========================today checkCallback params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['orderStatus'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('today withdrawal was successful: trade ID [%s]', $params['orderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('today withdrawal was not success: [%s]', $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        # does all required fields exist in the header?
        $requiredFields = array('mchOrderId', 'amount', 'orderStatus','orderId');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================today withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=======================today checkCallbackOrder verify signature Error', $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================today withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['mchOrderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================today withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }
        # everything checked ok
        return true;
    }
}
