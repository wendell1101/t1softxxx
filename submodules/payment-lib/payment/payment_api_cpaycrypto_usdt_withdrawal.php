<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cpaycrypto.php';

/**
 * CPAYCRYPTO
 *
 * * CPAYCRYPTO_USDT_WITHDRAWAL_PAYMENT_API, ID: 6224
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * 
 *
 * Field Values:
 * * URL: 
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 * 
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_cpaycrypto_usdt_withdrawal extends Abstract_payment_api_cpaycrypto {

	const REPONSE_CODE_SUCCESS = 0;
    const CALLBACK_SUCCESS = 0;
    const PAY_RESULT_SUCCESS = 14;

	protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

	public function getPlatformCode() {
		return CPAYCRYPTO_USDT_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'cpaycrypto_usdt_withdrawal';
	}

	public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================cpaycrypto_usdc submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================cpaycrypto_usdc submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================cpaycrypto_usdc submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================cpaycrypto_usdc json_decode result", $result);

        if(isset($result['code'])) {
            if($result['code'] == self::REPONSE_CODE_SUCCESS) {
                $message = "cpaycrypto_usdc withdrawal response successful, code:[".$result['code']."]: ".$result['msg'];
                return array('success' => true, 'message' => $message);
            }
            $message = "cpaycrypto_usdc withdrawal response failed. [".$result['code']."]: ".$result['msg'];
            return array('success' => false, 'message' => $message);

        }elseif(isset($result['msg'])){
            $message = 'cpaycrypto_usdc withdrawal response: '.$result['msg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "cpaycrypto_usdc decoded fail.");

    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("==================cpaycrypto_usdc withdraw get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $bankBranch = $playerBankDetails['branch'];
        }

        # look up bank code
        $wallet_account_id = $this->CI->wallet_model->getWalletaccountIdByTransactionCode($transId);
        $cryptolOrder = $this->CI->wallet_model->getCryptoWithdrawalOrderById($wallet_account_id);

        if(empty($cryptolOrder) && !is_array($cryptolOrder)){
            $this->utils->debug_log("=========================cpaycrypto_usdc crypto order not exists", $transId);
            return array('success' => false, 'message' => 'crypto order not exists');
        }

        $params = array();
		$params['merchantId']                = $this->getSystemInfo("account");
		$params['userId']                    = $playerId;
		$params['merchantTradeNo']           = $transId;
		$params['createTime']                = time();
		$params['cryptoCurrency']            = self::CPAYCRYPTO_USDT; 
        $params['network']                   = $bankBranch ? $bankBranch : $this->getSystemInfo("network");
        $params['totalAmount']               = $cryptolOrder['transfered_crypto'];
		$params['receivedAmount']            = $cryptolOrder['transfered_crypto'];
		$params['toAddress']                 = $accNum;
		$params['sign']                      = $this->sign($params);

        return $params;
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->CI->utils->debug_log('=====================cpaycrypto_usdc getOrderIdFromParameters flds', $flds);
        $raw_post_data = file_get_contents('php://input', 'r');
        $flds = json_decode($raw_post_data, true);

        $transId = null;
		//for fixed return url on browser
		if (isset($flds['merchantTradeNo'])) {
			$trans_id = $flds['merchantTradeNo'];

			$this->CI->load->model(array('wallet_model'));
	        $walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($trans_id);

			if(!empty($walletAccount)){

               	$transId = $walletAccount['transactionCode'];
            }else{
            	$this->utils->debug_log('====================================cpaycrypto_usdc callbackOrder transId is empty when getOrderIdFromParameters', $flds);
            }
		}
		else {
			$this->utils->debug_log('====================================cpaycrypto_usdc callbackOrder cannot get any transId when getOrderIdFromParameters', $flds);
		}

		return $transId;
    }

    public function callbackFromServer($transId, $params) {

        $response_result_id = parent::callbackFromServer($transId, $params);

        $raw_post_data = file_get_contents('php://input', 'r');
        $params = json_decode($raw_post_data, true);
        
        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('======================cpaycrypto_usdc callbackFromServer transId', $transId);
        $this->utils->debug_log("==========================cpaycrypto_usdc checkCallback params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['orderStatus'] == self::PAY_RESULT_SUCCESS) {
            $msg = sprintf('cpaycrypto_usdc withdrawal success: trade ID [%s]', $params['merchantTradeNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }else {
            $msg = sprintf('cpaycrypto_usdc withdrawal payment was not successful: trade ID [%s]', $params['merchantTradeNo']);
            $this->writePaymentErrorLog($msg, $params);
            $result['return_error_msg'] = msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array('merchantId', 'orderStatus', 'actualAmount', 'receivedAmount', 'fee');
        $cryptolOrder = $this->CI->wallet_model->getCryptoWithdrawalOrderById($order['walletAccountId']);

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================cpaycrypto_usdc withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['merchantTradeNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================cpaycrypto_usdc withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        if ($fields['orderStatus'] != self::PAY_RESULT_SUCCESS) {
            $this->writePaymentErrorLog("======================cpaycrypto_usdc withdrawal checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['receivedAmount'] != $cryptolOrder['transfered_crypto']){
            $this->writePaymentErrorLog('======================cpaycrypto_usdc withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $cryptolOrder['transfered_crypto'], $fields);
            return false;
        }

        if ($fields["sign"] != $this->validateSign($fields)) {
        	$this->writePaymentErrorLog('=========================cpaycrypto_usdc withdrawal checkCallback signature Error', $fields["sign"]);
        	return false;
        }

        # everything checked ok
        return true;
    }

    public function validateSign($params) {

        unset($params['sign']);
        ksort($params);
        
        $url = '';
        if (is_array($params) && count($params)>0) {
            foreach ($params as $k => $v) {
            $url = $url . "{$k}={$v}&";
            }
        }

        $key = $this->getSystemInfo('key');
        $url = $url.'key='.$key;
        $secret = $key;
        
        return hash_hmac("sha256", $url, $secret);
    }

}
