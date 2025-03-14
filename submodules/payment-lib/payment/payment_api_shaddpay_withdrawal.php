<?php
require_once dirname(__FILE__) . '/abstract_payment_api_shaddpay.php';

/**
 * SHADD 刷得多V2
 * SHADDPAY_WITHDRAWAL, ID: 6027
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra Info
 *
 * Field Values:
 * * URL: https://m18pay.com/api/payment-transaction
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_shaddpay_withdrawal extends Abstract_payment_api_shaddpay
{
    const WX_REQUEST_STATE_COMPLETED            = 'completed';
    const WX_REQUEST_STATE_FAILED               = 'failed';
    const WX_REQUEST_STATE_REJECTED             = 'rejected';

    public function getPlatformCode() {
        return SHADDPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'shaddapy_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {

        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $bankInfo = $this->getBankInfo();
        $bank = isset($bankInfo[$bank]) ? $bankInfo[$bank] : $bankInfo[-1];

        $this->CI->utils->debug_log(__METHOD__, 'bank', $bank);

        $params = [
            'code'              => $transId ,
            'bank_code'         => $bank['code'] ,
            'bank'              => $bank['name'] ,
            'bank_branch'       => $order['bankBranch'] ,
            'account_number'    => $order['bankAccountNumber'] ,
            'name'              => $order['bankAccountFullName'] ,
            'amount'            => $this->convertAmountToCurrency($amount) ,
            'callback'          => $this->getNotifyUrl($transId)
        ];

        $params['_signature'] = $this->createSign($params);

        $this->CI->utils->debug_log("======================shaddpay withdrawa getWithdrawParams params", $params);
        return $params;
    }

    /**
     * Set up custom header for HTTP basic authentication
     * @see     generate_basic_auth_text()
     * @return  none
     */
    protected function setup_header_auth($with_content_type = true) {
        $access_token = $this->getSystemInfo('key');
        $this->_custom_curl_header = [
            "Accept: application/json" ,
            "Authorization: Bearer {$access_token}"
        ];

        if (!empty($with_content_type)) {
            $this->_custom_curl_header[] = "Content-Type: application/x-www-form-urlencoded";
        }

        $this->CI->utils->debug_log(__METHOD__, "auth text", $this->_custom_curl_header);
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log(__METHOD__, $result);
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        if (empty($params['bank_branch'])) {
            return [
                'success' => false ,
                'message' => 'Bank branch not set'
            ];
        }

        $this->setup_header_auth();

        $response = $this->submitPostForm($this->getWithdrawUrl(), $params, false, $params['code']);

        $this->CI->utils->debug_log("======================shaddpay withdrawal params submit response", $response);

        $result = $this->decodeWxResult($response, false, $accNum, $name, $amount, $transId);

        $this->CI->utils->debug_log("======================shaddpay withdrawal decoded result", $result);

        return $result;

    }

    protected function error_to_text($error_raw) {
        $err_ar = is_array($error_raw) ? $error_raw : json_decode($error_raw, 'as_array');
        $err_text = '';
        foreach ($err_ar as $em => $er) {
            $err_text .= "$em: $er; ";
        }

        return $err_text;
    }

    public function decodeWxResult($resp, $queryAPI = false, $accNum, $name, $amount, $transId) {
        $result = json_decode($resp, true);
        $this->utils->debug_log("======================shaddpay withdrawal json_decode result", $result);

        if (!empty($result['message'])) {
            if (!empty($result['errors'])) {
                $message = sprintf('Withdrawal request failed, message=%s; errors=%s', $result['message'], $this->error_to_text($result['errors']));
            }
            else {
                $message = sprintf('Withdrawal request failed, message=%s', $result['message']);
            }
            return [
                'success'   => false ,
                'message'   => $message
            ];
        }

        // assume successful return
        // check secure id
        if ($transId != $result['code']) {
            return [
                'success'   => false ,
                'message'   => "code mismatch, expected={$transId}, received={$result['code']}"
            ];
        }

        // check amount
        if ($this->convertAmountToCurrency($amount) != $result['amount']) {
            return [
                'success'   => false ,
                'message'   => "amount mismatch, expected={$amount}, received={$result['amount']}"
            ];
        }

        // check account_number
        if ($accNum != intval($result['account_number'])) {
            return [
                'success'   => false ,
                'message'   => "account_number mismatch, expected={$accNum}, received={$result['account_number']}"
            ];
        }

        // Check state
        if (in_array($result['state'], [ self::WX_REQUEST_STATE_FAILED, self::WX_REQUEST_STATE_REJECTED ])) {
            return [
                'success'   => false ,
                'message'   => "Withdrawal request failed, state={$result['state']}"
            ];
        }

        // If everything OK
        return [
            'success' => true ,
            'message' => sprintf("SHADDPAY_WITHDRAWAL successful, code=%s, state=%s", $result['code'], $result['state'])
        ];
    }

    public function checkWithdrawStatus($transId) {
        $this->CI->load->model(array('wallet_model'));

        $url = $this->getWithdrawUrl()."/".$transId;
        $this->CI->utils->debug_log("======================shaddpay withdrawal withdrawal checkWithdrawStatus url: ", $url );

        $this->setup_header_auth(false);

        $response = $this->submitGetForm($url, [], false, $transId);
        // $response = $this->submitPostForm($url, [], false, $transId);

        $this->CI->utils->debug_log("======================shaddpay withdrawal withdrawal checkWithdrawStatus result: ", $response );

        $result = $this->parseWithdrawalCheckResult($response, false, $transId);

        if (!empty($result['state'])) {
            // Reject withdrawal order
            if ($result['state'] == self::WX_REQUEST_STATE_REJECTED) {
                $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $result['message']);
            }
            unset($result['state']);
        }

        return $result;
    }

    public function parseWithdrawalCheckResult($result_str, $queryAPI = false, $transId) {
        $resp = json_decode($result_str, true);
        $this->utils->debug_log("======================shaddpay withdrawal withdrawal withdrawal check res", $resp);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        // check for code (transID)
        if ($resp['code'] != $transId) {
            return [
                'success'   => false ,
                'message'   => "SHADDPAY WITHDRAWAL code mismatch, expected={$transId}, received={$resp['code']}"
            ];
        }

        // check for account_number
        if ($resp['account_number'] != $order['bankAccountNumber']) {
            return [
                'success'   => false ,
                'message'   => "SHADDPAY WITHDRAWAL account_number mismatch, expected={$order['bankAccountNumber']}, received={$resp['account_number']}"
            ];
        }

        // check for state
        if (in_array($resp['state'], [ self::WX_REQUEST_STATE_FAILED, self::WX_REQUEST_STATE_REJECTED ])) {
            return [
                'success'   => false ,
                'message'   => "SHADDPAY WITHDRAWAL Withdrawal request failed, state={$resp['state']}"
            ];
        }

        // for state = completed
        if ($resp['state'] == self::WX_REQUEST_STATE_COMPLETED) {
            return [
                'success'   => true ,
                'message'   => "SHADDPAY WITHDRAWAL successful, state={$resp['state']}"
            ];
        }

        // for other states
        return [
            'success'   => false ,
            'message'   => "Withdrawal in process, state={$resp['state']}"
        ];

    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("SHADDPAY_WITHDRAWAL callbackFromServer raw_post_data type", gettype($raw_post_data));
            $this->CI->utils->debug_log("SHADDPAY_WITHDRAWAL callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("SHADDPAY_WITHDRAWAL callbackFromServer json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log("SHADDPAY_WITHDRAWAL callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['state'] == self::WX_REQUEST_STATE_COMPLETED) {
            $msg = sprintf("SHADDPAY_WITHDRAWAL successful: trade ID=%s", $params['code']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf("SHADDPAY_WITHDRAWAL payment unsuccessful or pending: status=%s", $params['state']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = [ 'code', 'state', 'amount', '_signature' ];

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================shaddpay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['_signature'] != $this->validateSign($fields)) {
            $this->writePaymentErrorLog('==========================shaddpay withdrawal checkCallback signature Error',$fields);
            return false;
        }

        if ($fields['code'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================shaddpay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================shaddpay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        # everything checked ok
        return true;
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
            $this->utils->debug_log("======================shaddpay withdrawal: bank info from extra_info: ", $bankInfo);
        } else {

            $bankInfo = [
                1 => [ 'name' => '工商银行', 'code' => 'ICBC' ] ,
                2 => [ 'name' => '招商银行', 'code' => 'CMB' ] ,
                3 => [ 'name' => '建设银行', 'code' => 'CCB' ] ,
                4 => [ 'name' => '农业银行', 'code' => 'ABC' ] ,
                5 => [ 'name' => '交通银行', 'code' => 'COMM' ] ,
                6 => [ 'name' => '中国银行', 'code' => 'BOC' ] ,
                9 => [ 'name' => '东莞农商银行', 'code' => 'DRCB' ] ,
                10 => [ 'name' => '中信银行', 'code' => 'CITIC' ] ,
                11 => [ 'name' => '民生银行', 'code' => 'CMBC' ] ,
                12 => [ 'name' => '邮政银行', 'code' => 'PSBC' ] ,
                13 => [ 'name' => '兴业银行', 'code' => 'CIB' ] ,
                14 => [ 'name' => '华夏银行', 'code' => 'HXB' ] ,
                15 => [ 'name' => '平安银行', 'code' => 'PABC' ] ,
                17 => [ 'name' => '广州银行', 'code' => 'GUA' ] ,
                18 => [ 'name' => '南京银行', 'code' => 'NJCB' ] ,
                19 => [ 'name' => '广州农商银行', 'code' => 'GRCB' ] ,
                20 => [ 'name' => '光大银行', 'code' => 'CEB' ] ,
                26 => [ 'name' => '广发银行', 'code' => 'GDB' ] ,
                27 => [ 'name' => '浦发银行', 'code' => 'SPDB' ] ,
                28 => [ 'name' => '东亚银行', 'code' => 'BEA' ] ,
                29 => [ 'name' => '北京银行', 'code' => 'BOB' ] ,
                30 => [ 'name' => '天津银行', 'code' => 'TIANJIN' ] ,
                31 => [ 'name' => '上海银行', 'code' => 'BOS' ] ,
                32 => [ 'name' => '上海农商银行', 'code' => 'SRC' ] ,
                33 => [ 'name' => '北京农商', 'code' => 'BRCB' ] ,
                39 => [ 'name' => '成都银行', 'code' => 'CHENGDU' ] ,
                40 => [ 'name' => '重庆银行', 'code' => 'CQCBCN' ] ,
                41 => [ 'name' => '大连银行', 'code' => 'BOD' ] ,
                44 => [ 'name' => '东莞银行', 'code' => 'DGCB' ] ,
                48 => [ 'name' => '杭州银行', 'code' => 'HZB' ] ,
                49 => [ 'name' => '河北银行', 'code' => 'BOHB' ] ,
                52 => [ 'name' => '内蒙古银行', 'code' => 'BOIM' ] ,
                55 => [ 'name' => '吉林银行', 'code' => 'JLCB' ] ,
                57 => [ 'name' => '济宁银行', 'code' => 'BOJN' ] ,
                58 => [ 'name' => '锦州银行', 'code' => 'BOJZ' ] ,
                60 => [ 'name' => '昆仑银行', 'code' => 'BOKL' ] ,
                61 => [ 'name' => '廊坊银行', 'code' => 'BOL' ] ,
                67 => [ 'name' => '宁波银行', 'code' => 'NBCB' ] ,
                69 => [ 'name' => '青岛银行', 'code' => 'BQD' ] ,
                76 => [ 'name' => '台州银行', 'code' => 'TZB' ] ,
                79 => [ 'name' => '西安银行', 'code' => 'XIAN' ] ,
                81 => [ 'name' => '郑州银行', 'code' => 'BOZZ' ] ,
                86 => [ 'name' => '渤海银行', 'code' => 'CBHB' ] ,
                89 => [ 'name' => '浙商银行', 'code' => 'CZBANK' ] ,
                100 => [ 'name' => '恒丰银行', 'code' => 'HFB' ] ,
                102 => [ 'name' => '富滇银行', 'code' => 'FDB' ] ,
                105 => [ 'name' => '广东南粤银行', 'code' => 'GNB' ] ,
                106 => [ 'name' => '广西北部湾银行', 'code' => 'GBGB' ] ,
                107 => [ 'name' => '桂林银行', 'code' => 'GLBANK' ] ,
                110 => [ 'name' => '汉口银行', 'code' => 'HKB' ] ,
                111 => [ 'name' => '哈尔滨银行', 'code' => 'HCCB' ] ,
                117 => [ 'name' => '晋商银行', 'code' => 'JSYH' ] ,
                122 => [ 'name' => '临商银行', 'code' => 'LSB' ] ,
                123 => [ 'name' => '龙江银行', 'code' => 'LJB' ] ,
                132 => [ 'name' => '齐商银行', 'code' => 'QSB' ] ,
                133 => [ 'name' => '盛京银行', 'code' => 'SHENGJING' ] ,
                141 => [ 'name' => '威海银行', 'code' => 'WCCB' ] ,
                143 => [ 'name' => '厦门银行', 'code' => 'xiamen' ] ,
                147 => [ 'name' => '稠州商业银行', 'code' => 'CZCB' ] ,
                148 => [ 'name' => '民泰商业银行', 'code' => 'MTB' ] ,
                149 => [ 'name' => '浙江泰隆银行', 'code' => 'ZJTLCB' ] ,
                158 => [ 'name' => '成都农商银行', 'code' => 'CRCBB' ] ,
                159 => [ 'name' => '重庆农商银行', 'code' => 'CRCB' ] ,
                172 => [ 'name' => '江南农村商业银行', 'code' => 'CZRCB' ] ,
                175 => [ 'name' => '吴江农村商业银行', 'code' => 'WRCB' ] ,
                203 => [ 'name' => '深圳农商银行', 'code' => 'SRCB' ] ,
                204 => [ 'name' => '顺德农商银行', 'code' => 'SDCU' ] ,
                231 => [ 'name' => '珠海农商银行', 'code' => 'ZRCB' ] ,
                235 => [ 'name' => '浙江农村信用社', 'code' => 'ZJRCU' ] ,
                716 => [ 'name' => '四川省农村信用社', 'code' => 'SCRCU' ] ,
                717 => [ 'name' => '福建省农村信用社', 'code' => 'FJRCU' ] ,
                721 => [ 'name' => '贵州省农村信用社', 'code' => 'GUIZHOU' ] ,
                722 => [ 'name' => '江西农村信用社', 'code' => 'JBCU' ] ,
            ];

            $this->utils->debug_log("======================shaddpay withdrawal: using default (built-in) bankInfo");
        }

        // fail-safe value for everything unmapped
        $bankInfo[-1] = [ 'name' => '其他银行', 'code' => 'ABCD' ];

        return $bankInfo;
    }

    // protected function getNotifyUrl($orderId) {
    //     return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    // }

    public function validate_args($args){
        $except_keys = [ '_signature' ];

        foreach ($args as $key=>$val) {
            if (in_array($key, $except_keys)) {
                unset($args[$key]);
                continue;
            }

            if (empty($val)) {
                unset($args[$key]);
                continue;
            }
        }

        // Convert to json
        $args_json = json_encode($args);

        return $args_json;

    }

    public function sign_args($plain) {
        $api_token = $this->getSystemInfo('key');
        $hash = base64_encode(hash_hmac('sha256', $plain, $api_token, true));

        return $hash;
    }

    public function createSign($args) {
        $jsonArgs = $this->validate_args($args);
        $hash = $this->sign_args($jsonArgs);

        $this->CI->utils->debug_log("======================shaddpay withdrawal hash calc", [ 'jsonArgs' => $jsonArgs, 'hash' => $hash ]);

        return $hash;
    }

    public function validateSign($args) {
        $jsonArgs = $this->validate_args($args);
        $hash = $this->sign_args($jsonArgs);

        $this->CI->utils->debug_log("======================shaddpay withdrawal callback hash calc", [ 'jsonArgs' => $jsonArgs, 'hash' => $hash ]);

        return $hash;
    }
}
