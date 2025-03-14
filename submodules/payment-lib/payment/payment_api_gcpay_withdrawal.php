<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gcpay.php';

/**
 * gcpay
 * https://drt1iji2j13.gopay001.com/createwd
 * * GCPAY_WITHDRAWAL_PAYMENT_API, ID: 6195
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra Info
 *
 * Field Values:
 * * URL: https://drt1iji2j13.gopay001.com/createwd
 * * Account: ## Merchant ID ##
 * * Key: ## App Key ##
 * * Secret: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gcpay_withdrawal extends Abstract_payment_api_gcpay
{
     public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

    public function getPlatformCode()
    {
        return GCPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix()
    {
        return 'gcpay_withdrawal';
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
        $bankInfo = $this->getBankInfo();
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $params = array();
        $params['mch_id']         = $this->getSystemInfo('account');
        $params['out_order_no']   = $transId;
        $params['bank_name']      = $bankInfo[$bank]['name'];
        $params['account_name']   = $name;
        $params['account_no']     = $accNum;
        $params['amount']         = $this->convertAmountToCurrency($amount);
        $params['notify_url']     = $this->getNotifyUrl($transId);
        $params['timestamp']      = time();
        $params['version']        = 'v1.0';
        $params['sign']           = $this->sign($params);
        $this->CI->utils->debug_log("=====================gcpay getWithdrawParams", $params);

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
            $this->utils->error_log("========================gcpay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by gcpay");
            return array('success' => false, 'message' => 'Bank not supported by gcpay');
            $bank = '无';
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getSystemInfo('url');
        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);
        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('=====================gcpay submitWithdrawRequest content', $response);
        $this->CI->utils->debug_log('======================================gcpay submitWithdrawRequest decoded Result', $decodedResult);
        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================gcpay json_decode result", $result);
        $message = 'Unknow errors';
        if(!empty($result['code']) && $result['code'] == self::RESULT_CODE_SUCCESS) {
            $message = "gcpay request successful.";
            return array('success' => true, 'message' => $message);
        }
        else {
            if(!empty($result['message'])){
                $message = "gcpay withdrawal response, message:".$result['message'];
            }
            return array('success' => false, 'message' => $message);
        }
    }

    public function callbackFromServer($transId, $params)
    {
        $response_result_id = parent::callbackFromServer($transId, $params);
        return $this->callbackFrom('server', $transId, $params, $response_result_id);
    }

    public function callbackFrom($source, $transId, $params, $response_result_id)
    {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log('=========================gcpay process withdrawalResult order id', $transId);
        $this->CI->utils->debug_log("=========================gcpay checkCallback params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['pay_status'] == self::CALLBACK_SUCCESS) {
            $msg = sprintf('gcpay withdrawal was successful: trade ID [%s]', $params['out_order_no']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('gcpay withdrawal was not success: [%s]', $params['pay_status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = self::RETURN_FAIL_CODE;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields)
    {
        # does all required fields exist in the header?
        $requiredFields = array(
           'mch_id', 'out_order_no', 'pay_status', 'amount', 'sign'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================gcpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if (!$this->validateSign($fields)) {
               $this->writePaymentErrorLog('=====================xlpay withdrawal checkCallbackOrder Signature Error', $fields);
               return false;
        }

        if ($fields['amount'] != $order['amount']) {
            $this->writePaymentErrorLog("=====================gcpay Payment amounts do not match, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['out_order_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("========================gcpay checkCallbackOrder type2 order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    # -- Private functions --
    public function getNotifyUrl($orderId) {
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
            $this->utils->debug_log("==================getting gcpay bank info from extra_info: ", $bankInfo);
        } else {
            if(!empty($bankInfoArr)) {
                foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                    $bankInfo[$system_bank_type_id] = array('name' => $bankInfoItem['name'], 'code' => $bankInfoItem['code']);
                }
                $this->utils->debug_log("==================getting gcpay bank info from extra_info: ", $bankInfo);
            } else {
                $bankInfo = array(
                    '1' => array('name' => '中国工商银行', 'code' => '1'),
                    '2' => array('name' => '招商银行', 'code' => '6'),
                    '3' => array('name' => '中国建设银行', 'code' => '2'),
                    '4' => array('name' => '中国农业银行', 'code' => '3'),
                    '5' => array('name' => '交通银行', 'code' => '5'),
                    '6' => array('name' => '中国银行', 'code' => '4'),
                    '8' => array('name' => '广发银行', 'code' => '15'),
                    '10' => array('name' => '中信银行', 'code' => '7'),
                    '11' => array('name' => '中国民生银行', 'code' => '8'),
                    '12' => array('name' => '中国邮政储蓄银行', 'code' => '11'),
                    '13' => array('name' => '兴业银行', 'code' => '9'),
                    '14' => array('name' => '华夏银行', 'code' => '14'),
                    '15' => array('name' => '平安银行', 'code' => '13'),
                    '17' => array('name' => '广州银行', 'code' => '69'),
                    '18' => array('name' => '南京银行', 'code' => '132'),
                    '20' => array('name' => '中国光大银行', 'code' => '12'),
                    '24' => array('name' => '浦发银行', 'code' => '10'),
                );
                $this->utils->debug_log("=======================getting xlpay bank info from code: ", $bankInfo);
            }
        }
        return $bankInfo;
    }

}
