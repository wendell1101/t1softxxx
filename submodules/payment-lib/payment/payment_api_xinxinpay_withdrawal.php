<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xinxinpay.php';

/**
 * xinxinpay取款
 *
 * * XINXINPAY_WITHDRAWAL_PAYMENT_API, ID: 6073
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.xinxinpay.com/openApi/payout/createOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xinxinpay_withdrawal extends Abstract_payment_api_xinxinpay {

	public function getPlatformCode() {
		return XINXINPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xinxinpay_withdrawal';
	}

	# Implement abstract function but do nothing
	protected function configParams(&$params, $direct_pay_extra_info) {}

	/**
	 * detail: override common API functionsh
	 *
	 * @return void
	 */
	public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
	}

	public function processPaymentUrlForm($params) {
	}

	# APIs with withdraw function need to implement these methods
	## This function returns the URL to submit withdraw request to
	public function getWithdrawUrl() {
		return $this->getSystemInfo('url');
	}

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        # look up bank code
        $bankInfo = $this->getBankInfo();
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        if(!empty($playerBankDetails)){
            $playerId = $playerBankDetails['playerId'];
            $playerDetails = $this->CI->player_model->getPlayerDetails($playerId);
            $phone     = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '8615551234567';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $params = array();
        $params['merchNo']      = $this->getSystemInfo("account");
        $params['orderNo']      = $transId;
        $params['amount']       = $this->convertAmountToCurrency($amount);
        $params['currency']     =  'CNY';
        $params['outChannel']   =  'acp';
        $params['bankName']     =  $bankInfo[$bank]['name'];
        $params['bankCode']     =  $bankInfo[$bank]['code'];
        $params['bankNo']       = $accNum;
        $params['acctName']     = $name;
        $params['certNo']       = '43333333330000';
        $params['mobile']       = $phone;
        $params['title']        = 'withdrawal';
        $params['product']      = 'withdrawal';
        $params['notifyUrl']    = $this->getNotifyUrl($transId);
        $params['reqTime']      = date('YmdHis');
        $params['userId']       = $playerId;

        $submitParams['sign']        = $this->sign($params);
        $submitParams['context']     = base64_encode(json_encode($params));
        $submitParams['encryptType'] = "MD5";

        $this->CI->utils->debug_log('=========================xinxinpay getWithdrawParams params', $params);
        return $submitParams;
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
            $this->utils->error_log("========================xinxinpay withdrawal bank whose bankTypeId=[$bank] is not supported by xinxinpay");
            return array('success' => false, 'message' => 'Bank not supported by xinxinpay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        $url = $this->getSystemInfo('url');

        list($content, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($content);
        $this->CI->utils->debug_log('=========================xinxinpay submitWithdrawRequest decoded Result', $decodedResult);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;
    }

	public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================xinxinpay json_decode result", $result);
        if(!empty($result) && isset($result)){
            if($result['code'] == self::RESULT_CODE_SUCCESS){
                return array('success' => true, 'message' => 'xinxinpay withdrawal request successful.');
            }else if(isset($result['msg']) && !empty($result['msg'])){
                $errorMsg = $result['msg'];
                return array('success' => false, 'message' => $errorMsg);
            }
            else{
                return array('success' => false, 'message' => 'xinxinpay withdrawal exist errors');
            }
        }else{
            return array('success' => false, 'message' => 'xinxinpay withdrawal exist errors');
        }
    }

	protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        if(!empty($this->getSystemInfo("convert_amount_to_currency_unit"))){
            $convert_amount_to_currency_unit = $this->getSystemInfo("convert_amount_to_currency_unit");
        }else{
            $convert_amount_to_currency_unit = 1;
        }
        return number_format($amount *  $convert_amount_to_currency_unit, 2, '.', '');
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================xinxinpay process withdrawalResult order id', $transId);
        $this->CI->utils->debug_log("=========================xinxinpay checkCallback params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        $contextData = json_decode(base64_decode($params['context']), true);

        if($contextData['orderState'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('xinxinpay withdrawal was successful: trade ID [%s]', $contextData['orderNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('xinxinpay withdrawal was not success: [%s]', $params['Status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'code', 'context','sign'
        );

        $requiredContext = array(
            'amount', 'orderState'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================XINXINpay withdrawal checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $contextData = json_decode(base64_decode($fields['context']), true);

        foreach ($requiredContext as $f) {
            if (!array_key_exists($f, $contextData)) {
                $this->writePaymentErrorLog("=====================XINXINpay withdrawal checkCallbackOrder Missing contextData parameter: [$f]", $contextData);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields, $contextData)) {
            $this->writePaymentErrorLog('=====================XINXINpay withdrawal checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($contextData['orderState'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("======================XINXINpay withdrawal checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($contextData['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog("======================XINXINpay withdrawal checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($contextData['orderNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================XINXINpay withdrawal checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
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
            $this->utils->debug_log("==================getting xinxinpay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => array('name' => '工商银行', 'code' => 'ICBC'),
                '2' => array('name' => '招商银行', 'code' => 'CMB'),
                '3' => array('name' => '工商建设银行', 'code' => 'CCB'),
                '4' => array('name' => '农业银行', 'code' => 'ABC'),
                '5' => array('name' => '交通银行', 'code' => 'COMM'),
                '6' => array('name' => '中国银行', 'code' => 'BOC'),
                '10' => array('name' => '中信银行', 'code' => 'CITIC'),
                '11' => array('name' => '民生银行', 'code' => 'CMBC'),
                '12' => array('name' => '邮政储蓄银行', 'code' => 'PSBC'),
                '13' => array('name' => '兴业银行', 'code' => 'CIB'),
                '14' => array('name' => '华夏银行', 'code' => 'HXBANK'),
                '15' => array('name' => '平安银行', 'code' => 'PAB'),
                '20' => array('name' => '光大银行', 'code' => 'CEB'),
            );

            $this->utils->debug_log("=======================getting xinxinpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
}
