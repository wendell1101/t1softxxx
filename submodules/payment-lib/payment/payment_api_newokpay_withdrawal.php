<?php
require_once dirname(__FILE__) . '/abstract_payment_api_newokpay.php';

/**
 * newokpay
 * * newokpay_WITHDRAWAL_PAYMENT_API, ID: 6017
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra Info
 *
 * Field Values:
 * * URL: https://api.okpay777.com/createwd
 * * Account: ## Merchant ID ##
 * * Key: ## App Key ##
 * * Secret: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_newokpay_withdrawal extends Abstract_payment_api_newokpay
{
     public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

    public function getPlatformCode()
    {
        return NEWOKPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix()
    {
        return 'newokpay_withdrawal';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {}

    protected function processPaymentUrlForm($params) {}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }


    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId)
    {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $params = array();
        $params['sendid']         = $this->getSystemInfo('account');
        $params['orderid']        = $transId;
        $params['amount']         = $this->convertAmountToCurrency($amount);
        $params['sign']           = $this->sign($params);
        $params['address']        = $accNum;
        $params['note']           = 'withdrawal';
        $params['notifyurl']      = $this->getNotifyUrl($transId);
        $this->CI->utils->debug_log("=====================newokpay getWithdrawParams", $params);

        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId)
    {
        $result = array('success' => false, 'message' => 'Payment failed');

        if (!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================newokpay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by newokpay");
            return array('success' => false, 'message' => 'Bank not supported by newokpay');
            $bank = '无';
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getSystemInfo('url');
        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);
        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('=====================newokpay submitWithdrawRequest content', $response);
        $this->CI->utils->debug_log('======================================newokpay submitWithdrawRequest decoded Result', $decodedResult);
        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false)
    {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $response = json_decode($resultString, true);

        if($queryAPI){
            if(isset($response['code']) && !empty($response['code']) && $response['code'] == self::RESULT_CODE_SUCCESS ) {
                $responseData = json_decode($response['data'],true);
                if(isset($responseData['state']) && $responseData['state'] == self::CALLBACK_SUCCESS){
                    $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($responseData['orderid']);
                    if (!$this->checkCallbackOrder($order, $responseData)) {
                        return array('success' => false, 'message' => "newokpay check order status fail.");
                    }else{
                        $message = sprintf('newokpay withdrawal payment was successful: trade ID [%s]', $responseData['orderid']);
                        $this->CI->wallet_model->withdrawalAPIReturnSuccess($responseData['orderid'], $message);
                        return array('success' => true, 'message' => $message);
                    }
                }else if(isset($responseData['state']) && !empty($responseData['state'])){
                    $message = 'newokpay withdrawal response=> process state['.$responseData['state'].']';
                    return array('success' => false, 'message' => $message);
                }else{
                    return array('success' => false, 'message' => "newokpay check status decoded fail.");
                }
            }
            elseif(isset($response['code']) && !empty($response['code']) && isset($response['msg']) && !empty($response['msg'])){
                $message = 'newokpay withdrawal response=> code['.$response['code'].']:'.$response['msg'];
                return array('success' => false, 'message' => $message);
            }else{
                return array('success' => false, 'message' => "newokpay check status decoded fail.");
            }
        }else{
            if(isset($response['code']) && !empty($response['code']) && $response['code'] == self::RESULT_CODE_SUCCESS ) {
                $responseData = json_decode($response['data'],true);
                if(isset($responseData['id']) && !empty($responseData['id'])){
                    $message = "newokpay withdrawal response successful, transId: ". $responseData['orderid'];
                    $this->CI->wallet_model->setExtraInfoByTransactionCode($responseData['orderid'],$responseData['id']);
                    return array('success' => true, 'message' => $message);
                }else{
                    return array('success' => false, 'message' => 'newokpay withdrawal order id is null');
                }
            }
            elseif(isset($response['code']) && !empty($response['code']) && isset($response['msg']) && !empty($response['msg'])){
                $message = 'newokpay withdrawal response=> code['.$response['code'].']:'.$response['msg'];
                return array('success' => false, 'message' => $message);
            }
        }

        return array('success' => false, 'message' => "newokpay decoded fail.");
    }

    public function callbackFromServer($transId, $params)
    {
        $response_result_id = parent::callbackFromServer($transId, $params);
        return $this->callbackFrom('server', $transId, $params, $response_result_id);
    }

    public function callbackFrom($source, $transId, $params, $response_result_id)
    {
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================newokpay process withdrawalResult transId', $transId);

        $raw_post_data = file_get_contents('php://input', 'r');
        $params = json_decode($raw_post_data, true);
        $this->CI->utils->debug_log("=====================newokpay callbackFromServer raw_post_data", $raw_post_data);
        $this->CI->utils->debug_log("=====================newokpay callbackFromServer json_decode params", $params );

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['state'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('newokpay withdrawal payment was successful: trade ID [%s]', $params['orderid']);
            $result['success'] = true;
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $msg = sprintf('newokpay withdrawal payment was not successful: status code [%s], ', $params['state']);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields)
    {
        # does all required fields exist in the header?
        $requiredFields = array(
            'id', 'orderid', 'state', 'amount'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================newokpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['amount'] != $order['amount']) {
            $this->writePaymentErrorLog("=====================newokpay Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['orderid'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("========================newokpay checkCallbackOrder type2 order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    private function getNotifyUrl($orderId)
    {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    public function getBankInfo()
    {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if (!empty($bankInfoArr)) {
            foreach ($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                if (isset($bankInfoItem['name'])) {
                    $bankInfo[$system_bank_type_id]['name'] = $bankInfoItem['name'];
                }
                if (isset($bankInfoItem['code'])) {
                    $bankInfo[$system_bank_type_id]['code'] = $bankInfoItem['code'];
                }
            }
            $this->utils->debug_log("==================getting newokpay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '46'  => array('name' => 'GOPAY', 'code' => 'OKPAY'),
                '209'  => array('name' => 'GOPAY', 'code' => 'OKPAY'),
            );
            $this->utils->debug_log("=======================getting newokpay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    public function checkWithdrawStatus($transId) {
        $params = array();
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $params['id'] = $order['extra_info'];
        $url = $this->getSystemInfo('check_withdraw_status_url', 'https://api.okpay777.com/getwd');

        list($content, $response_result) = $this->submitGetForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($content,  true);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================waas checkWithdrawStatus params: ', $params);
        $this->CI->utils->debug_log('======================================waas checkWithdrawStatus url: ', $url );
        $this->CI->utils->debug_log('======================================waas_usdt submitWithdrawRequest content', $content);
        $this->CI->utils->debug_log('======================================waas_usdt submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

}
