<?php
require_once dirname(__FILE__) . '/abstract_payment_api_stashpay.php';

/**
 * STASHPAY_WITHDRAWAL
 *
 * * STASHPAY_WITHDRAWAL_PAYMENT_API, ID: 6419
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_stashpay_withdrawal extends Abstract_payment_api_stashpay {
    const REQUEST_RESULT_SUCCESS = 'INITIAL';

    public function getPlatformCode() {
        return STASHPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'stashpay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}
    protected function processPaymentUrlForm($params) {}
    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();
        $token = $this->_getBearerToken();
        list($response, $response_result) = $this->processCurl($url, $params, $token ,$transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================stashpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================stashpay submitWithdrawRequest token: ', $token );
        $this->CI->utils->debug_log('======================================stashpay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================stashpay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerInfo = $this->getPlayerInfoByTransactionCode($transId);
        $playerId = $playerInfo['playerId'];

        $params = array();
        $params['accountId'] = $this->getSystemInfo("accountId");
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $this->_setPlayerInfoToParams($playerId, $params, $accNum);
        $params['clientTransactionId'] = $transId;
        $params['callbackUrl'] = $this->getNotifyUrl($transId);

        $this->CI->utils->debug_log('=========================stashpay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = $resultString;
        
        $this->utils->debug_log("=========================stashpay json_decode result", $result);

        if (isset($result['data']['status'], $result['ok']) && $result['ok'] ) {
            if(!empty($result['data']['clientTransactionId']) && $result['data']['status'] == self::REQUEST_RESULT_SUCCESS) {
                $message = "stashpay withdrawal response successful, orderId:".$result['data']['clientTransactionId'];
                return array('success' => true, 'message' => $message);
            }
            
            $message = "stashpay withdrawal response failed. third party status: ".$result['data']['status'];
            return array('success' => false, 'message' => $message);
        }

        $defaultErrorMsg = 'stashpay withdrawal exist errors, decoded fail';
        return array('success' => false, 'message' => $this->getErrorMsgWithResponse($result, $defaultErrorMsg));
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);

        if(empty($params) || is_null($params)){
			$raw_post_data = file_get_contents('php://input', 'r');
        	$params = json_decode($raw_post_data, true);
		}

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================stashpay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================stashpay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('stashpay withdrawal success: order ID [%s]', $params['clientTransactionId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['success'] = true;
            $result['message'] = [
                "ok" => true,
                "data" => [
                    "id" => $params['clientTransactionId']
                ]
            ];
        }else {
            $msg = sprintf("stashpay withdrawal payment unsuccessful or pending: status=%s", $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'id', 'clientTransactionId', 'amount', 'status'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================stashpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================stashpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['clientTransactionId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================stashpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}