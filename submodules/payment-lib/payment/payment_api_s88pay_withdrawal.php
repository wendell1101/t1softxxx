<?php
require_once dirname(__FILE__) . '/abstract_payment_api_s88pay.php';

/**
 * s88pay 取款
 *
 * * S88PAY_WITHDRAWAL_PAYMENT_API, ID: 6159
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
class Payment_api_s88pay_withdrawal extends Abstract_payment_api_s88pay {
    const BUSICODE_WITHDRAWAL     = 'WTHB10';
    const RESULT_SUCCESS = 'request withdraw successful';

    public function getPlatformCode() {
        return S88PAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 's88pay_withdrawal';
    }

    public function __construct($params = null) {
        parent::__construct($params);
        $this->_custom_curl_header = array('Content-Type:application/json');
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}

    protected function processPaymentUrlForm($params) {}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url').'/api/v1/payout/'.$this->getSystemInfo('account');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));

        $bankInfo = $this->getBankInfo();
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $params = array();
        $params['merchant_code']         = $this->getSystemInfo('account');
        $params['transaction_code']      = $transId;
        $params['transaction_timestamp'] = time();
        $params['payout_code']           = self::BUSICODE_WITHDRAWAL;
        $params['transaction_amount']    = $this->convertAmountToCurrency($amount);
        $params['user_id']               = $order['playerId'];
        $params['currency_code']         = $this->getSystemInfo('currency');
        $params['bank_account_number']   = $accNum;
        $params['bank_code']             = $bankInfo[$bank]['code'];
        $params['bank_name']             = $bankInfo[$bank]['name'];
        $params['account_name']          = $name;
        $post_params['key']              = $this->sign($params);

        $this->CI->utils->debug_log('======================================s88pay getWithdrawParams :', $params);
        return $post_params;
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
            $this->utils->error_log("========================s88pay withdrawal bank whose bankTypeId=[$bank] is not supported by s88pay");
            return array('success' => false, 'message' => 'Bank not supported by s88pay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================gtpaynew submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================gtpaynew submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================gtpaynew submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================s88pay json_decode result", $result);
        $message = 'Unknow errors';
        if(!empty($result['message']) && $result['message'] == self::RESULT_SUCCESS) {
            $message = "s88pay request successful.";
            return array('success' => true, 'message' => $message);
        }
        else {
            if(!empty($result['message'])){
                $message = "s88pay withdrawal response, message:".$result['message'];
            }
            return array('success' => false, 'message' => $message);
        }
    }

    public function getOrderIdFromParameters($flds){
        if (empty($flds)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================s88pay raw_post_data", $raw_post_data);
            $flds = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================s88pay json_decode flds", $flds);
        }
        $transId = null;
        $decrypt_data = $this->encrypt_decrypt('decrypt', $flds['key']);
        if (isset($decrypt_data['transaction_code'])) {
            $this->CI->load->model(array('wallet_model'));
            $trans_id = $decrypt_data['transaction_code'];
            $walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($trans_id);

           if(!empty($walletAccount)){
                $transId = $walletAccount['transactionCode'];
            }else{
                $this->utils->debug_log('====================================s88pay callbackOrder transId is empty when getOrderIdFromParameters', $flds);
            }
        } else {
           $this->utils->debug_log('=====================s88pay callbackOrder cannot get any order_id when getOrderIdFromParameters', $flds);
        }

        return $transId;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        if(isset($params['key'])){
            $params = $this->encrypt_decrypt('decrypt', $params['key']);
        }else{
            return false;
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if($params['transaction_status'] == self::RESULT_CODE_SUCCESS) {
            $this->utils->debug_log('=========================s88pay withdrawal payment was successful: trade ID [%s]', $params['transaction_code']);
            $msg = sprintf('s88pay withdrawal was successful: trade ID [%s]',$params['transaction_code']);
            $this->withdrawalSuccess($transId, $msg);
            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        } else {
            $msg = 's88pay withdrawal payment was not successful';
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }


     public function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'transaction_code', 'transaction_amount', 'transaction_status'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================s88pay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['transaction_code'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("========================s88pay checkCallbackOrder type2 order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        if ($fields['transaction_amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================s88pay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['transaction_status'] != self::RESULT_CODE_SUCCESS) {
            $this->writePaymentErrorLog("======================s88pay checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    # -- info --
    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("s88pay_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $bankInfoItem) {
                $bankInfo[$bankInfoItem[0]] = $bankInfoItem[1];
            }
            $this->utils->debug_log("==================getting s88pay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                "28" =>  array('name' => "Bangkok Bank", 'code' => '101'),
                "29" =>  array('name' => "Krung Thai Bank", 'code' => '104'),
                "30" =>  array('name' => "Siam Commercial Bank", 'code' => '103'),
                "31" =>  array('name' => "Kasikorn Bank", 'code' => '102'),
                "34" =>  array('name' => "CIMB Bank", 'code' => '112'),
                "37" =>  array('name' => "Citibank National Association", 'code' => '115'),
                "39" =>  array('name' => "Thanachart Bank", 'code' => '107'),
                "43" =>  array('name' => "Government Savings Bank", 'code' => '114'),
                "47" =>  array('name' => "Government Housing Bank", 'code' => '110'),
                "56" =>  array('name' => "Sumitomo Mitsui Banking Corporation", 'code' => '121'),
                "57" =>  array('name' => "United Overseas Bank", 'code' => '113'),
                "60" =>  array('name' => "The Hongkong and Shanghai Banking Corporation Limited", 'code' => '122'),
                "61" =>  array('name' => "Bank for Agriculture and Agricultural Cooperatives", 'code' => '108'),
                "62" =>  array('name' => "Mizuho Bank", 'code' => '116'),
                "63" =>  array('name' => "Islamic Bank of Thailand", 'code' => '117'),
                "64" =>  array('name' => "Tisco Bank", 'code' => '118'),
                "66" =>  array('name' => "The Thai Credit Retail Bank", 'code' => '120'),
                "67" =>  array('name' => "Land and Houses Bank", 'code' => '109'),
                "73" =>  array('name' => "TMB Thanachart", 'code' => '127'),
            );
            $this->utils->debug_log("=======================getting s88pay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    public function sign($params) {
        $sign = $this->encrypt_decrypt('encrypt', json_encode($params));
        return $sign;
    }

    public function createSignStr($params) {
        $signStr = '';
        foreach($params as $key => $value) {
            if($key == 'sign' ) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr = rtrim($signStr, '&');
        return $signStr;
    }

    public function encrypt_decrypt($action, $string) {
        $output = false;
        $encrypt_method = "AES-256-CBC";
        $secret_key = $this->getSystemInfo('api_key');
        $secret_iv = $this->getSystemInfo('api_secret');
        // hash
        $key = substr(hash('sha256', $secret_key, true), 0, 32);

        $iv = substr(hash('sha256', $secret_iv), 0, 16);
        if ( $action == 'encrypt' ) {
            $output = openssl_encrypt($string, $encrypt_method, $key, OPENSSL_RAW_DATA, $iv);
            $output = base64_encode($output);
            $output = urlencode($output);
        } else if( $action == 'decrypt' ) {
           $decrypt_str = openssl_decrypt(base64_decode(urldecode($string)), $encrypt_method, $key, OPENSSL_RAW_DATA, $iv);
           $output = json_decode($decrypt_str,true);
        }
        return $output;
    }
}


