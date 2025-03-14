<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gtpayth.php';

/**
 * gtpayth 取款
 *
 * * GTPAYTH_WITHDRAWAL_PAYMENT_API, ID: 6163
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://tl.7xinpy.com/withdraw/singleOrder
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##g
 *
 * * Extra Info:
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gtpayth_withdrawal extends Abstract_payment_api_gtpayth {
    const RESULT_CODE_SUCCESS     = '1';
    const RESULT_SUCCESS          = '200';

    public function getPlatformCode() {
        return GTPAYTH_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'gtpayth_withdrawal';
    }

    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}

    protected function processPaymentUrlForm($params) {}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $bankInfo = $this->getBankInfo();
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $params = array();
        $params['merchant_id']       = $this->getSystemInfo('account');
        $params['out_trade_no']      = $transId;
        $params['realname']          = $name;
        $params['bank_code']         = $bankInfo[$bank]['code'];
        $params['bank_number']       = $accNum;
        $params['notify_url']        = $this->getNotifyUrl($transId);
        $params['money']             = $this->convertAmountToCurrency($amount);
        $params['sign']              = $this->sign($params);
        $params['sign_type']         = 'md5';

        $this->CI->utils->debug_log('======================================gtpayth getWithdrawParams :', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        # look up bank code
        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================gtpayth withdrawal bank whose bankTypeId=[$bank] is not supported by gtpayth");
            return array('success' => false, 'message' => 'Bank not supported by gtpayth');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================gtpayth submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================gtpayth submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================gtpayth submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================gtpayth json_decode result", $result);
        $message = 'Unknow errors';
        if($result['code'] == self::RESULT_SUCCESS) {
            $message = "gtpayth request successful.";
            return array('success' => true, 'message' => $message);
        }
        else {
            if(!empty($result['message'])){
                $message = "gtpayth withdrawal response, message:".$result['message'];
            }
            return array('success' => false, 'message' => $message);
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['state'] == self::RESULT_CODE_SUCCESS) {
            $this->utils->debug_log('=========================gtpayth withdrawal payment was successful: trade ID [%s]', $params['state']);
            $msg = sprintf('gtpayth withdrawal was successful: trade ID [%s]',$params['out_trade_no']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS;
            $result['success'] = true;
        } else {
            $msg = 'gtpayth withdrawal payment was not successful';
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'state', 'out_trade_no', 'money', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================gtpayth withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================gtpayth withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['money'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================gtpayth withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['out_trade_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================gtpayth withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    # -- info --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("gtpayth_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting gtpayth bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                "28" =>  array('name' => "Bangkok Bank", 'code' => 'BBL'),
                "29" =>  array('name' => "Krung Thai Bank", 'code' => 'KTB'),
                "30" =>  array('name' => "Siam Commercial Bank", 'code' => 'SCB'),
                "31" =>  array('name' => "Kasikorn Bank", 'code' => 'KBANK'),
                "37" =>  array('name' => "Citibank National Association", 'code' => 'CITI'),
                "43" =>  array('name' => "Government Savings Bank", 'code' => 'GSB'),
                "47" =>  array('name' => "Government Housing Bank", 'code' => 'GHB'),
                "56" =>  array('name' => "Sumitomo Mitsui Banking Corporation", 'code' => 'SMBC'),
                "57" =>  array('name' => "United Overseas Bank", 'code' => 'UOB'),
                "60" =>  array('name' => "The Hongkong and Shanghai Banking Corporation Limited", 'code' => 'HSBC'),
                "61" =>  array('name' => "Bank for Agriculture and Agricultural Cooperatives", 'code' => 'BAAC'),
                "62" =>  array('name' => "Mizuho Bank", 'code' => 'MHCB'),
                "64" =>  array('name' => "Tisco Bank", 'code' => 'TSCO'),
                "66" =>  array('name' => "The Thai Credit Retail Bank", 'code' => 'TCRB'),
                "67" =>  array('name' => "Land and Houses Bank", 'code' => 'LHBANK'),
                "73" =>  array('name' => "TMB Thanachart", 'code' => 'TTB'),
            );
            $this->utils->debug_log("=======================getting gtpayth bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

}


