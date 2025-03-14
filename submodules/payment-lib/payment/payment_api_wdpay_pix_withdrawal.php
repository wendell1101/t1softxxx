<?php
use PHPHtmlParser\Content;
require_once dirname(__FILE__) . '/abstract_payment_api_wdpay.php';
/**
 * FORTUNEPAY
 *
 * * FORTUNEPAY_WITHDRAWAL_PAYMENT_API, ID: 6538
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.fortunepay.in/payout/pay/createOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_wdpay_pix_withdrawal extends Abstract_payment_api_wdpay {
    const RESULT_CODE_SUCCESS = 200;



    // orderStatusCode 
    //  1-已受理
    // 2-银行处理中
    // 4-失败(银行未受理)
    // 8-成功
    // 16-失败
    const ORDER_STATUS_CODE_ACCEPTED = 1;
    const ORDER_STATUS_CODE_BANK_PROCESSING = 2;
    const ORDER_STATUS_CODE_FAILED_BANK_NOT_ACCEPTED = 4;
    const ORDER_STATUS_CODE_SUCCESS = 8;
    const ORDER_STATUS_CODE_FAILED = 16;


    public function __construct($params = null) {
        parent::__construct($params);
    }

    public function getPlatformCode() {
        return WDPAY_PIX_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'wepay_pix_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $bankInfo = $this->getBankInfo();
        if(empty($bankInfo)){
            return array('success' => false, 'message' => 'Bank info not set.');
        }
        $bankCode = $bankInfo[$bank]['code'];
        $playerBankDetails =  $this->getPlayerInfoByTransactionCode($transId, $bankCode);
        $this->utils->debug_log("===============================wdpay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);

        
        if (empty($playerBankDetails)) {
            return array('success' => false, 'message' => 'Player bank details not found');
        }
        
        $firstname = $this->utils->safeGetArray($playerBankDetails, 'firstName', '');
        $lastname = $this->utils->safeGetArray($playerBankDetails, 'lastName', '');
        $cpfNumber = $this->utils->safeGetArray($playerBankDetails, 'cpfNumber', '');
        $pixAccount = $this->utils->safeGetArray($playerBankDetails, 'pixAccount', '');

        $params = array();
        $params = [
            "currencyAmount" => $this->convertAmountToCurrency($amount),
            "channelType" => $this->getSystemInfo("channelType", 'PIX'),
            "externalOrderId" => $transId,
            "personIdType" => $this->getSystemInfo("personIdType", 'CPF'),
            "personId" => $cpfNumber,
            "personName" => join(' ', array($firstname, $lastname)),
            "accountType" => $bankCode,
            "accountId" => $pixAccount,
            "notifyUrl" => $this->getNotifyUrl($transId),
        ];
        
        $_access_key = $this->getSystemInfo('account');
        $_timestamp = (int)(microtime(true)*1000);
        $_nonce = $this->guidv4();
        $params['access_key'] = $_access_key;
        $params['timestamp'] = $_timestamp;
        $params['nonce'] = $_nonce;
        
        $this->_custom_curl_header = [
            'sign: '. $this->sign($params),
            'access_key: ' .$_access_key,
            'timestamp: ' .$params['timestamp'],
            'nonce: ' .$params['nonce'],
            'Content-Type: application/json;charset=utf-8',
        ];
        
        $this->CI->utils->debug_log("=====================wdpay getWithdrawParams params", $params);

        unset($params['access_key']);
        unset($params['timestamp']);
        unset($params['nonce']);
        
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log(__METHOD__, $result);
            return $result;
        }

        if(!array_key_exists($bank, $this->getBankInfo())) {
			$this->utils->error_log("========================wdpay withdrawal submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by wdpay_withdrawal");
			return array('success' => false, 'message' => 'Bank not supported by wdpay');
		}

        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $playerId = $playerBankDetails['playerId'];
        $validationResults = $this->checkWalletaccountPlayerId($playerId, $transId);

        if (!$validationResults['success']) {
            $this->utils->debug_log("===========wdpay", ["result" => $validationResults]);
            return $validationResults;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        if(isset($params['success']) && !$params['success']){
            $result['message'] = $params['message'];
            return $result;
        }
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================wdpay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================wdpay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================wdpay submitWithdrawRequest decoded Result', $decodedResult);

        if($decodedResult['success']){
            $this->CI->wallet_model->setExtraInfoByTransactionCode($transId, $response['orderId']);
        }
        $this->CI->utils->debug_log('========================wdpay withdrawal submitWithdrawRequest decoded Result', $decodedResult);
        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) 
    {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================wdpay json_decode result", $result);

        if( !empty($result['code']) && $result['code'] == self::RESULT_CODE_SUCCESS && !empty($result['success'])){
            $message = "fortunepay request successful.";
            return array('success' => true, 'message' => $message);
        }

        $resultMsg = '';
        if(!empty($result['msg'])) {
            $resultMsg = $result['msg'];
        }else{
            $this->utils->error_log("========================fortunepay return UNKNOWN ERROR!");
            $resultMsg = "Unknown error";
        }

        $message = "wdpay withdrawal response, Msg: ".$resultMsg;
        return array('success' => false, 'message' => $message);
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================wdpay withdrawal  raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================wdpay withdrawal  json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================wdpay withdrawal  callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================wdpay withdrawal  callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['orderStatusCode'] == self::ORDER_STATUS_CODE_SUCCESS) {
            $msg = sprintf('wdpay withdrawal  withdrawal success: trade ID [%s]', $params['externalOrderId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            // $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
            $result['json_result']['code'] = self::RETURN_SUCCESS_CODE;
            $result['json_result']['success'] = true;

        }
        else if ($params['orderStatusCode'] != self::ORDER_STATUS_CODE_BANK_PROCESSING && $params['orderStatusCode'] != self::ORDER_STATUS_CODE_ACCEPTED) {
            $apiErrorMsg = $this->utils->safeGetArray($params, 'errorMsg', '');
            $msg = sprintf('wdpay withdrawal  withdrawal failed: [%s]', $apiErrorMsg);
            $this->writePaymentErrorLog($msg, null);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            // $result['message'] = $msg;
            $result['json_result']['code'] = self::RETURN_FAIL_CODE;
            $result['json_result']['success'] = false;

        }
        else {
            $apiErrorMsg = $this->utils->safeGetArray($params, 'errorMsg', '');
            $msg = sprintf('wdpay withdrawal  withdrawal payment was not successful: [%s]', $apiErrorMsg);
            $this->writePaymentErrorLog($msg, null);
            // $result['message'] = $msg;
            $result['json_result']['code'] = self::RETURN_FAIL_CODE;
            $result['json_result']['success'] = false;

        }

        return $result;
    }

    protected function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'externalOrderId', 'orderAmount', 'orderStatusCode', 'orderId'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================wdpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        $headers = $this->CI->input->request_headers();
        $this->CI->utils->debug_log("=====================wdpay checkCallbackOrder headers", $headers);
        if (!$this->validateSign($fields,  $headers)) {
            $this->writePaymentErrorLog('=========================wdpay withdrawal checkCallbackOrder signature Error', $fields);
            return false;
        }

        if ($fields['orderAmount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================wdpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['externalOrderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================wdpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }

    # -- Private functions --
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function findBankName($bank_id) {
        $bank_row = $this->CI->banktype->getBankTypeById($bank_id);
        $bank_name = lang($bank_row->bankName);

        return $bank_name;
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
            $this->utils->debug_log("==================fortunepay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '28' => array('name' => 'PIX_CPF', 'code' => 'CPF'),
                '29' => array('name' => 'PIX_EMAIL', 'code' => 'EMAIL'),
                '30' => array('name' => 'PIX_PHONE', 'code' => 'PHONE'),
            );
            $this->utils->debug_log("=======================getting fortunepay bank info from code: ", $bankInfo);
        }

        return $bankInfo;
    }
}
