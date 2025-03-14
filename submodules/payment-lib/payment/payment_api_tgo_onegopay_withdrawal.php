<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tgo_onegopay.php';
/**
 * TGO_ONEGOPAY_WITHDRAWAL
 *
 * * TGO_ONEGOPAY_WITHDRAWAL_PAYMENT_API, ID: 5908
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: https://six666.net
 * * Key: ## API token ##
 *
 * Notes:
 * 1 This API uses HTTP bearer authentication
 * 2 Minimum amount = ?
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_tgo_onegopay_withdrawal extends Abstract_payment_api_tgo_onegopay {

    const PATH_WITHDRAWAL_REQUEST               = 'api/payment-transaction';

    const ERRORCODE_WITHDRAWAL_SUCCESS          = 200;
    const ERRORCODE_WITHDRAWAL_CHECK_SUCCESS    = 200;

    const WX_REQUEST_STATE_NEW                  = 'new';
    const WX_REQUEST_STATE_PROCESSING           = 'processing';
    const WX_REQUEST_STATE_COMPLETED            = 'completed';
    const WX_REQUEST_STATE_FAILED               = 'failed';
    const WX_REQUEST_STATE_REJECTED             = 'rejected';
    const WX_REQUEST_STATE_REFUND               = 'refund';

    const STATUS_WITHDRAWAL_CHECK_PROGRESS      = 'progress';
    const STATUS_WITHDRAWAL_CHECK_WITHDRAWING   = 'withdrawing';
    const STATUS_WITHDRAWAL_CHECK_SUCCESS       = 'success';
    const STATUS_WITHDRAWAL_CHECK_FAILED        = 'failed';

    public function getPlatformCode() {
        return TGO_ONEGOPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tgo_onegopay_withdrawal';
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
        // $bankCode = isset($bankInfo[$bank]) ? $bankInfo[$bank]['code'] : $bankInfo[-1]['code'];
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

        $params['_signature'] = $this->calc_sign_wx_request($params);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} getWithdrawParams params", $params);
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

        if (empty($params['bank_branch'])) {
            return [
                'success' => false ,
                'message' => 'Bank branch not set'
            ];
        }

        $url_withdrawal = $this->getWithdrawRequestUrl();

        $this->setup_header_auth();

        $response = $this->submitPostForm($url_withdrawal, $params, false, $params['code']);

        // $response = $this->submitPostForm($url_withdrawal, $params, 'as_json', $params['code']);
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} params submit response", $response);

        $result = $this->decodeWxResult($response, false, $accNum, $name, $amount, $transId);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} decoded result", $result);

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
        $this->utils->debug_log(__METHOD__, "{$this->ident} json_decode result", $result);

        if (isset($result['message'])) {
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
        if (intval($amount) != intval($result['amount'])) {
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
            'message' => sprintf("{$this->ident}_WITHDRAWAL successful, uuid=%s, state=%s", $result['uuid'], $result['state'])
        ];

        // if ($result['code'] == self::ERRORCODE_WITHDRAWAL_SUCCESS) {
        //     return [
        //         'success' => true ,
        //         'message' => sprintf("{$this->ident} withdrawal successful, id: %s, status: %s", $result['id'], $result['status'])
        //     ];
        // }
        // else {
        //     return [
        //         'success'   => false ,
        //         'message'   => sprintf("{$this->ident} withdrawal error: %s (%d)", $result['message'], $result['code'] )
        //     ];
        // }

    }

    public function checkWithdrawStatus($transId) {
        $this->CI->load->model(array('wallet_model'));

        $url = "{$this->getCheckWithdrawStatusUrl()}/{$transId}";
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} withdrawal checkWithdrawStatus url: ", $url );

        $this->setup_header_auth(false);

        $response = $this->submitGetForm($url, [], false, $transId);
        // $response = $this->submitPostForm($url, [], false, $transId);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} withdrawal checkWithdrawStatus result: ", $response );

        $result = $this->parseWithdrawalCheckResult($response, false, $transId);

        if (isset($result['action'])) {
            // Reject withdrawal order
            if ($result['action'] == 'reject') {
                $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $result['message']);
            }
            unset($result['action']);
        }

        return $result;
    }

    public function parseWithdrawalCheckResult($result_str, $queryAPI = false, $transId) {
        $resp = json_decode($result_str, 'as_array');
        $this->utils->debug_log(__METHOD__, "{$this->ident} withdrawal withdrawal check res", $resp);

        // $expected_fields = [ 'code', 'message' ];

        try {
            $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

            // check for code (transID)
            if ($resp['code'] != $transId) {
                throw new Exception("code mismatch, expected={$transId}, received={$resp['code'] }", 0x21);
            }

            // check for account_number
            if ($resp['account_number'] != $order['bankAccountNumber']) {
                throw new Exception("account_number mismatch, expected={$order['bankAccountNumber']}, received={$resp['account_number']}", 0x22);
            }

            // check for state
            if (in_array($resp['state'], [ self::WX_REQUEST_STATE_FAILED, self::WX_REQUEST_STATE_REJECTED ])) {
                throw new Exception("Withdrawal request failed, state={$resp['state']}", 0x23);
            }

            // for state = completed
            if ($resp['state'] == self::WX_REQUEST_STATE_COMPLETED) {
                return [
                    'success'   => true ,
                    'message'   => "Withdrawal successful, state={$resp['state']}"
                ];
            }

            // for other states
            return [
                'success'   => false ,
                'message'   => "Withdrawal in process, state={$resp['state']}"
            ];
        }
        catch (Exception $ex) {
            $this->utils->debug_log(__METHOD__, "{$this->ident} withdrawal check failed - ({$ex->getCode()}) {$ex->getMessage()} ");
            $ret = [
                'success' => false ,
                'message' => $ex->getMessage() ,
                'action'  => null
            ];
            if ($ex->getCode() == 0x23) {
                $ret['action'] = 'reject';
            }

            return $ret;
        }

    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServerBare($transId, $params);

        // $params = $_REQUEST;

        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("{$this->ident}_WITHDRAWAL callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("{$this->ident}_WITHDRAWAL callbackFromServer json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        // $this->CI->utils->debug_log("{$this->ident}_WITHDRAWAL callbackFromServer transId", $transId);
        $this->CI->utils->debug_log("{$this->ident}_WITHDRAWAL callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkWxCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['state'] == self::WX_REQUEST_STATE_COMPLETED) {
            $msg = sprintf("{$this->ident}_WITHDRAWAL successful: trade ID=%s", $params['code']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            // $state_text = $this->wx_callback_state_to_text($params['status']);
            $msg = sprintf("{$this->ident}_WITHDRAWAL payment unsuccessful or pending: status=%s", $params['state']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkWxCallbackOrder($order, $fields) {
        /**
         * expected fields:
         *     type
         *     uuid
         *     code             secure_id
         *     bank_code
         *     bank
         *     bank_branch
         *     amount           amount
         *     state            new | processing | completed | failed
         *     account_number   order.bankAccountNumber
         *     name
         *     callback
         *     updated_at
         *     completed_at
         *     _signature       sign
         */
        try {
            $requiredFields = [ 'code', 'state', 'amount', '_signature' ];

            foreach ($requiredFields as $f) {
                if (!array_key_exists($f, $fields)) {
                    throw new Exception("Callback field missing: '{$f}'", 0x41);
                    return false;
                }
            }

            // Check signature
            $sign_expected = $this->calc_sign_wx_callback($fields);
            if ($sign_expected != $fields['_signature']) {
                throw new Exception("_signature mismatch, received={$fields['_signature']}, expected={$sign_expected}", 0x42);
            }

            // Check code
            $code_expected = $order['transactionCode'];
            if ($code_expected != $fields['code']) {
                throw new Exception("code mismatch, received={$fields['code']}, expected={$code_expected}", 0x43);
            }

            // Check amount
            $amount_expected = floatval($order['amount']);
            if ($amount_expected != floatval($fields['amount'])) {
                throw new Exception("amount mismatch, received={$fields['amount']}, expected={$amount_expected}", 0x44);
            }

            // Check state
            if ($fields['state'] != self::WX_REQUEST_STATE_COMPLETED) {
                throw new Exception("state unexpected, received={$fields['state']}, expected=" . self::WX_REQUEST_STATE_COMPLETED, 0x45);
            }

            // Point of wx check success
            $ret = true;
        }
        catch (Exception $ex) {
            $this->CI->utils->debug_log(__METHOD__, sprintf("{$this->ident}_WITHDRAWAL %s (0x%x)", $ex->getMessage(), $ex->getCode()));
            $this->writePaymentErrorLog("{$this->ident}_WITHDRAWAL {$ex->getMessage()}", $fields);
            $ret = false;
        }
        finally {
            return $ret;
        }
    }

    protected function findBankName($bank_id) {
        $bank_row = $this->CI->banktype->getBankTypeById($bank_id);
        $bank_name = lang($bank_row->bankName);

        return $bank_name;
    }

    protected function getCheckWithdrawStatusUrl() {
        return $this->getWithdrawRequestUrl();
    }

    protected function getWithdrawRequestUrl() {
        $url = $this->getSystemInfo('url');
        $url_wx_request = "{$url}/" . self::PATH_WITHDRAWAL_REQUEST;

        return $url_wx_request;
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
            $this->utils->debug_log(__METHOD__, "{$this->ident}_withdrawal: bank info from extra_info: ", $bankInfo);
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

            $this->utils->debug_log(__METHOD__, "{$this->ident}_withdrawal: using default (built-in) bankInfo");
        }

        // fail-safe value for everything unmapped
        $bankInfo[-1] = [ 'name' => '其他银行', 'code' => 'ABCD' ];

        return $bankInfo;
    }

    // protected function getNotifyUrl($orderId) {
    //     return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    // }

    public function calc_sign_wx_request($args) {
        $plain = $this->sign_plain($args);
        $hash = $this->sign_hash($plain);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} hash calc", [ 'plain' => $plain, 'hash' => $hash ]);

        return $hash;
    }

    public function calc_sign_wx_callback($args) {
        $plain = $this->sign_plain($args);
        $hash = $this->sign_hash($plain);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} hash calc", [ 'plain' => $plain, 'hash' => $hash ]);

        return $hash;
    }
}