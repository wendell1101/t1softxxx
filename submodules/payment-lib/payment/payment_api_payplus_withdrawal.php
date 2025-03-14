<?php
require_once dirname(__FILE__) . '/abstract_payment_api_payplus.php';
/**
 * payplus
 *
 * * PAYPLUS_WITHDRAWAL_PAYMENT_API, ID: 6034
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_payplus_withdrawal extends Abstract_payment_api_payplus {
    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

    public function getPlatformCode() {
        return PAYPLUS_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'payplus_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {

        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $this->CI->load->library([ 'ifsc_razorpay_lib' ]);
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $bank_name = $this->findBankName($bank);
        $bank_ifsc = $order['bankBranch'];
        $this->CI->utils->debug_log(__METHOD__, 'payplus_withdrawal basic creds', [ 'accNum' => $accNum, 'name' => $name, 'bank' => $bank, 'bank_name' => $bank_name, 'bank_ifsc' => $bank_ifsc]);

        $params = [
            "merId"        => $this->getSystemInfo('account'),
            "orderId"      => $transId,
            "money"        => $this->convertAmountToCurrency($amount),
            "name"         => $name,
            "ka"           => $accNum,
            "zhihang"      => $bank_ifsc,
            "bank"         => 'bank',
            "notifyUrl"    => $this->getNotifyUrl($transId),
            "nonceStr"     => $this->getNonce(),
        ];
        $params['sign']    = $this->sign($params);
        $this->CI->utils->debug_log(__METHOD__, 'payplus_withdrawal getWithdrawParams params', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log(__METHOD__, $result);
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        if (empty($params['zhihang'])) {
            return [
                'success' => false ,
                'message' => 'IFSC not set, please set IFSC code of your withdrawal account'
            ];
        }

        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['orderId']);
        $this->CI->utils->debug_log(__METHOD__, 'params submit response', $response);

        $result = $this->decodeResult($response);

        return $result;

    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================payplus_withdrawal json_decode result", $result);

        if(isset($result['code'])) {
            $returnCode = $result['code'];
            $returnDesc = $result['msg'];
            if($returnCode == self::ORDER_STATUS_SUCCESS && isset($result['data']['orderId']) && !empty($result['data']['orderId'])) {
                $message = "payplus_withdrawal withdrawal response successful, transId: ". $result['data']['orderId']. ", msg: ". $returnDesc;
                return array('success' => true, 'message' => $message);
            }
            $message = "payplus_withdrawal withdrawal response failed. [".$returnCode."]: ".$returnDesc;
            return array('success' => false, 'message' => $message);

        }
        else{
            $message = $message.' API response: '.$resultString;
            return array('success' => false, 'message' => $message);
        }

        return array('success' => false, 'message' => "yc888pay decoded fail.");
    }

    protected function findBankName($bank_id) {
        $bank_row = $this->CI->banktype->getBankTypeById($bank_id);
        $bank_name = lang($bank_row->bankName);

        return $bank_name;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================payplus raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================payplus json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================payplus callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================payplus callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::ORDER_STATUS_SUCCESS) {
            $msg = sprintf('payplus withdrawal success: trade ID [%s]', $params['outTradeNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('payplus withdrawal payment was not successful: [%s]', $params['msg']);
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'orderId', 'money', 'status', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================payplus withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->verifySignature($fields)) {
            $this->writePaymentErrorLog('=========================payplus withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['money'] != $order['amount']) {
            $this->writePaymentErrorLog('=========================payplus withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['orderId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================payplus withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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

}