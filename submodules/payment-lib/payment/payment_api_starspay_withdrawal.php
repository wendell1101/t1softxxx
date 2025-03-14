<?php
require_once dirname(__FILE__) . '/abstract_payment_api_starspay.php';

/**
 * STARSPAY取款
 *
 * * STARSPAY_WITHDRAWAL_PAYMENT_API, ID: 5965
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.stars-pay.com/api/gateway/withdraw
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_starspay_withdrawal extends Abstract_payment_api_starspay {
	const CALLBACK_SUCCESS = 1;

	public function getPlatformCode() {
		return STARSPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'starspay_withdrawal';
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
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);

        $this->utils->debug_log("==================starspay withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $pix_number  = (isset($playerDetails[0]) && !empty($playerDetails[0]['pix_number']))? $playerDetails[0]['pix_number'] : 'none';
        }

        $detailInfo['merchant_ref'] = $transId;
        $detailInfo['product'] = 'BrazilPayout';
        $detailInfo['amount'] = $this->convertAmountToCurrency($amount);
        $detailInfo['extra']['account_name'] = $name;
        $detailInfo['extra']['account_no'] = $pix_number;
        $detailInfo['extra']['bank_code'] = 'CPF';

		$params = array();
        $params['merchant_no'] = $this->getSystemInfo("account");
        $params['params'] = json_encode($detailInfo);
        $params['sign_type'] = 'MD5';
        $params['timestamp'] = time();
        $params['sign'] = $this->sign($params);

		$this->CI->utils->debug_log('=========================starspay withdrawal paramStr before sign', $params);
		return $params;
	}

	public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }
        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($content, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================starspay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================starspay submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================starspay submitWithdrawRequest response ', $response);
        $this->CI->utils->debug_log('======================================starspay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

	public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================starspay json_decode result", $result);
        if(!empty($result) && isset($result)){
            if(!empty($result['code']) && isset($result['code']) && $result['code'] == self::REQUEST_SUCCESS ){
                return array('success' => true, 'message' => 'starspay withdrawal request successful.');
            }else if(isset($result['message']) && !empty($result['message'])){
                $errorMsg = $result['message'];
                return array('success' => false, 'message' => $errorMsg);
            }
            else{
                return array('success' => false, 'message' => 'starspay withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'starspay withdrawal exist errors');
        }
    }

    public function getOrderIdFromParameters($params) {
        $this->utils->debug_log('====================================stars callbackOrder params', $params);
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }else{
            $params = json_decode($params['params'],true);
        }

        $transId = null;

        //for fixed return url on browser
        if (isset($params['merchant_ref']) && !empty($params['merchant_ref'])) {
            $transId = $params['merchant_ref'];

            $this->CI->load->model(array('wallet_model'));
            $walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

            if(!empty($walletAccount)){
                $transId = $walletAccount['transactionCode'];
            }else{
                $this->utils->debug_log('====================================stars callbackOrder transId is empty when getOrderIdFromParameters', $params);
            }
        }
        else {
            $this->utils->debug_log('====================================stars callbackOrder cannot get any transId when getOrderIdFromParameters', $params);
        }
        return $transId;
    }

	private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
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
        }else{
            $jsonParams = json_decode($params['params'],true);
        }

        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================starspay process withdrawalResult order id', $transId);
        $this->CI->utils->debug_log("=========================starspay checkCallback params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($jsonParams['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('starspay withdrawal was successful: trade ID [%s]', $jsonParams['merchant_ref']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('starspay withdrawal was not success: [%s]', $jsonParams['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields, &$processed = false)
    {
        $requiredFields = array('merchant_ref', 'system_ref', 'amount', 'status');

        if(isset($fields['params'])){
            $jsonData = json_decode($fields['params'],true);
        }else{
            $jsonData = array();
        }

        $this->CI->utils->debug_log("=========================starspay checkCallback detailData", $jsonData);

        foreach ($requiredFields as $f) {
           if (!array_key_exists($f, $jsonData)) {
                $this->writePaymentErrorLog("=======================stars withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->verifySignature($fields)) {
               $this->writePaymentErrorLog('=====================stars withdrawal checkCallbackOrder Signature Error', $fields);
               return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($jsonData['merchant_ref'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================stars withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
            return false;
        }

        if ($jsonData['amount']  != $order['amount']) {
            $this->writePaymentErrorLog("======================paysec withdrawal checkCallbackOrder payment amount is wrong, expected <= ". $order['amount'], $fields);
            return false;
        }

        return true;
    }

    public function sign($params) {
        $signStr = $this->getSystemInfo("account").$params['params'].$params['sign_type'].$params['timestamp'].$this->getSystemInfo('key');
        $signature = md5($signStr);
        return $signature;
    }

    public function verifySignature($params) {
        $callback_sign = $this->getSystemInfo("account").$params['params'].$params['sign_type'].$params['timestamp'].$this->getSystemInfo('key');
        $sign= md5($callback_sign);
        return $sign == $params['sign'];
    }
}
