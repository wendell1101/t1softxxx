<?php
require_once dirname(__FILE__) . '/abstract_payment_api_iq.php';

/**
 * PaymentIQ
 * https://backoffice.paymentiq.io
 * https://test-backoffice.paymentiq.io
 *
 * * IQ_WITHDRAWAL_HELP2PAY_PAYMENT_API, ID: 5582
 *
 * Required Fields:
 * * URL
 * * Account
 *
 * Field Values:
 * * URL: https://api.paymentiq.io/paymentiq
 * * Account: ## Merchant ID ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_iq_withdrawal_help2pay extends Abstract_payment_api_iq {

    public function getPlatformCode() {
        return IQ_WITHDRAWAL_HELP2PAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'iq_withdrawal_help2pay';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params, $orderId){}

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================iq_withdrawal_help2pay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by iq_withdrawal_help2pay");
            return array('success' => false, 'message' => 'Bank not supported by PaymentIQ Help2pay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();
        list($response, $response_result) = $this->processPaymentByprovider($params, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================iq_withdrawal_help2pay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================iq_withdrawal_help2pay submitWithdrawRequest params: ', $params);
        $this->CI->utils->debug_log('======================================iq_withdrawal_help2pay submitWithdrawRequest response ', $response);
        $this->CI->utils->debug_log('======================================iq_withdrawal_help2pay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('player_model', 'playerbankdetails'));

        # look up bank code
        $bankInfo = $this->getBankInfo();
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

		# Get player id
		$order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
		$playerId = $order['playerId'];

        $params = array();
        $params['sessionId']       = base64_encode($transId);
        $params['userId']          = $playerId;
        $params['amount']          = $this->convertAmountToCurrency($amount);
        $params['merchantId']      = $this->getSystemInfo("account");
        $params['accountNumber']   = $accNum;
        $params['beneficiaryName'] = $name;
        $params['bankName']        = $bankInfo[$bank]['code'];

        $params['attributes']['secure_id']  = $transId;
        $params['attributes']['successUrl'] = $this->getReturnUrl($transId);
        $params['attributes']['failureUrl'] = $this->getReturnFailUrl($transId);
        $params['attributes']['cancelUrl']  = $this->getReturnFailUrl($transId);

        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

        $result = array('success' => false, 'message' => 'PaymentIQ Help2pay decoded fail.');
        $response = json_decode($resultString, true);
        $this->CI->utils->debug_log('==============iq_withdrawal_help2pay submitWithdrawRequest decodeResult json decoded', $response);
        if(empty($response)){
            return $result;
        }

        $returnState = $response['txState'];
        $txRefId = $response['txRefId'];

        if($returnState == self::RETURN_TXSTATE_WAITING_APPROVAL || $returnState == self::RETURN_TXSTATE_SUCCESS) {
            $result['success'] = true;
            $result['message'] = "PaymentIQ Help2pay withdrawal response success! [".$returnState."], TxRefId: ".$txRefId;
        } elseif ($returnState == self::RETURN_TXSTATE_FAILED) {
            $returnErrors = $response['errors'];
            $errmsg = $returnErrors[0]['msg'];

            $result['message'] = "PaymentIQ Help2pay withdrawal response failed. [".$returnState."]: ".$errmsg;
        }
        return $result;
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
            $this->utils->debug_log("=========================iq_withdrawal_help2pay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => array('name' => 'Karsikorn Bank (K-Bank)', 'code' => 'KKR'),
                '26' => array('name' => 'Maybank Berhad', 'code' => 'MBB'),
                '42' => array('name' => 'Bank Central Asia', 'code' => 'BCA'),
                '43' => array('name' => 'Mandiri Bank', 'code' => 'MDR'),
                '44' => array('name' => 'Bank Rakyat Indonesia', 'code' => 'BRI'),
                '45' => array('name' => 'Bank Negara Indonesia', 'code' => 'BNI'),
            );
            $this->utils->debug_log("=========================iq_withdrawal_help2pay bank info from code: ", $bankInfo);

        }
        return $bankInfo;
    }

    # -- signatures --
    protected function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        $this->CI->utils->debug_log("=======================iq_withdrawal_help2pay Signing [$signStr], signature is", $sign);
        return $sign;
    }

    private function createSignStr($params) {
        ksort($params);
        $signStr = '';
        foreach($params as $key => $value) {
            if(is_null($value) || $key == 'sign') {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        return $signStr.'key='.$this->getSystemInfo('key');
    }
}