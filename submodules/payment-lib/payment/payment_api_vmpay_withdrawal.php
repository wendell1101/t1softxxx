<?php
require_once dirname(__FILE__) . '/abstract_payment_api_vmpay.php';
/**
 * VMPAY
 *
 * * VMPAY_PAYMENT_WITHDRAWAL_API, ID: 5748
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://wmpay.wshangfu.com/apitakecash
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_vmpay_withdrawal extends Abstract_payment_api_vmpay {

    const URLPATH_WITHDRAW          = 'apitakecash';
    const URLPATH_WITHDRAWQUERY     = 'cashquery';
    const URLPATH_QUERYPAYCHANNEL   = 'dfbalance';

    const WDSTATUS_PENDING      = 0;
    const WDSTATUS_PAID         = 1;
    const WDSTATUS_FROZEN       = 2;
    const WDSTATUS_REJECTED     = 3;
    const WDSTATUS_PROCESSING   = 4;

    const SN_PREFIX_DEFAULT     = 'T1';

    public $ident = 'VMPAY_WITHDRAWAL';

    public function getPlatformCode() {
        return VMPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'vmpay_withdrawal';
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


        $player = $this->CI->player_model->getPlayerDetailArrayById($order['playerId']);

        $querytime = date('YmdHis');

        // if (1) {
        //     $paychannel = 92; // test only
        // }
        // else {
            $paychannel_res = $this->selectPayChannel();
            $paychannel = ($paychannel_res['code'] == 0) ? $paychannel_res['result'] : -1;
        // }

        $bank_code = $this->findBankCode($bank);

        $params = [
            'version'       =>  '1.0' ,
            'customerid'    =>  $this->getSystemInfo('account') ,
            'txmoney'       =>  $this->convertAmountToCurrency($amount) ,
            'bankcode'      =>  $bank_code ,
            'sn'            =>  $this->secureIdToSn($transId) ,
            'province'      =>  $order['bankProvince'] ,
            'city'          =>  $order['bankCity'] ,
            'branchname'    =>  $order['bankBranch'] ,
            'accountname'   =>  $order['bankAccountFullName'] ,
            'paychannel'    =>  $this->getSystemInfo('paychannel') ,
            'cardno'        =>  $order['bankAccountNumber'] ,
            'tradeTime'     =>  $querytime ,
            'paytype'       =>  'bank' ,
            'notifyurl'     =>  $this->getNotifyUrl($transId) ,
            'telephone'     =>  null ,
            'idcard'        =>  null ,
        ];

        $params['sign'] = $this->calc_sign_withdraw($params, 'withdraw');


        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} getWithdrawParams params", $params);
        return $params;
    }

    public function queryPayChannel() {
        $querytime = date('YmdHis');
        $params = [
            'customerid'    =>  $this->getSystemInfo('account') ,
            'querytime'     =>  $querytime ,
            'randstr'       =>  md5($querytime)
        ];
        $params['sign'] = $this->calc_sign_general($params);

        $url_base = $this->getSystemInfo('url');
        $url_queryPayChannel = $url_base . self::URLPATH_QUERYPAYCHANNEL;

        $resp = $this->submitPostForm($url_queryPayChannel, $params, false, null);

        return $resp;
    }

    public function selectPayChannel() {
        $query_resp_raw = $this->queryPayChannel();

        $result = json_decode($query_resp_raw, 'as_array');

        $this->utils->debug_log(__METHOD__, [ 'query_resp_raw' => $query_resp_raw, 'result' => $result ]);

        try {
            if (!isset($result['status'])) {
                throw new Exception('Malformed query paychannel result, status not found', 1);
            }

            if ($result['status'] != 1) {
                throw new Exception('query paychannel unsuccessful', 2);
            }

            if (!isset($result['balance']) || !is_array($result['balance'])) {
                throw new Exception('Malformed query paychannel result, balance not found', 3);
            }

            if (count($result['balance']) == 0) {
                throw new Exception('Malformed query paychannel result, balance empty', 4);
            }

            foreach ($result['balance'] as $row) {
                if (!isset($row['unpaid']) || !isset($row['paychannel'])) {
                    throw new Exception('Malformed query paychannel result, paychannel or unpaid not found', 5);
                }
            }

            $channels = $result['balance'];

            usort($channels, function ($a, $b) { return $a['unpaid'] < $b['unpaid']; });

            $selected_channel = reset($channels);

            $this->utils->debug_log(__METHOD__, 'selected paychannel', $selected_channel);

            if ($selected_channel['unpaid'] <= 0) {
                throw new Exception('No pay channel with unpaid greater than 0', 6);
            }

            return [
                'error'     => 0 ,
                'code'      => null ,
                'result'    => $selected_channel['paychannel']
            ];

        }
        catch (Exception $ex) {
            $this->utils->debug_log(__METHOD__, 'exception', [ 'error' => $ex->getMessage(), 'code' => $ex->getCode() ]);
            return [
                'error'     => $ex->getMessage() ,
                'code'      => $ex->getCode() ,
                'result'    => null
            ];
        }

    }

    public function calc_sign_callback($params) {
        $fields = [ 'customerid', 'fee', 'finishtime', 'pushnoncestr', 'pushtime', 'sn', 'status', 'txmoney' ];
        $query_ar = [];
        foreach ($fields as $f) {
            $query_ar[$f] = $params[$f];
        }
        $query = http_build_query($query_ar);

        $api_key = $this->getSystemInfo('key');
        $plain = "{$query}&{$api_key}";
        $hash = md5($plain);

        $this->utils->debug_log(__METHOD__, "sign calc-withdraw", [ 'plain' => $plain, 'hash' => $hash ]);

        return $hash;
    }

    public function calc_sign_withdraw($params) {
        $fields = [ 'version' , 'customerid' , 'txmoney' , 'bankcode' , 'province' , 'city' , 'branchname' , 'accountname' , 'cardno' , 'paytype' , 'paychannel' , 'sn' , 'tradeTime' ];
        $query_ar = [];
        foreach ($fields as $f) {
            $query_ar[$f] = $params[$f];
        }
        $query = http_build_query($query_ar);

        $api_key = $this->getSystemInfo('key');
        $plain = "{$query}&{$api_key}";
        $hash = md5($plain);

        $this->utils->debug_log(__METHOD__, "sign calc-withdraw", [ 'plain' => $plain, 'hash' => $hash ]);

        return $hash;
    }

    public function calc_sign_general($params) {
        $query = http_build_query($params);
        $api_key = $this->getSystemInfo('key');
        $plain = "{$query}&{$api_key}";
        $hash = md5($plain);
        $this->utils->debug_log(__METHOD__, "sign calc-general", [ 'plain' => $plain, 'hash' => $hash ]);

        return $hash;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log(__METHOD__, $result);
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        if (empty($params['bankcode'])) {
            return [
                'success' => false ,
                'message' => 'bank type not supported'
            ];
        }

        if ($params['paychannel'] == -1) {
            return [
                'success' => false ,
                'message' => 'no pay channel available'
            ];
        }

        $url_withdraw = $this->getSystemInfo('url') . self::URLPATH_WITHDRAW;

        $response = $this->submitPostForm($url_withdraw, $params, false, $transId);
        $this->CI->utils->debug_log(__METHOD__, 'params submit response', $response);

        $result = $this->decodeResult($response);

        $this->CI->utils->debug_log(__METHOD__, 'decoded result', $result);

        return $result;

    }

    public function decodeResult($resp, $queryAPI = false) {
        $result = json_decode($resp, true);
        $this->utils->debug_log(__METHOD__, "{$this->ident} json_decode result", $result);

        $expected_fields = [ 'status', 'msg' ];

        foreach ($expected_fields as $ef) {
            if (!array_key_exists($ef, $result)) {
                return [
                    'success'   => false ,
                    'message'   => sprintf('Invalid API response, missing field: %s', $ef)
                ];
            }
        }

        if ($result['status'] == 1) {
            return [
                'success' => true ,
                'message' => sprintf("Withdrawal successful, transaction ID: %s, message: %s", $result['sn'], $result['msg'])
            ];
        }
        else {
            return [
                'success'   => false ,
                'message'   => sprintf("Unsuccessful, status: %s, message: %s", $result['status'], $result['msg'] )
            ];
        }

    }

    public function checkWithdrawStatus($transId) {
        $this->CI->load->model(array('wallet_model'));
        // $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        // $params = [ 'requestid' => $transId ];

        $querytime = date('YmdHis');

        $params = [
            'version'       =>  '1.0' ,
            'customerid'    => $this->getSystemInfo('account') ,
            'sn'            => $this->secureIdToSn($transId) ,
            'querytime'     => $querytime ,
            'type'          => 'cashorder' ,
        ];

        $params['sign'] = $this->calc_sign_general($params);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} checkWithdrawStatus params: ", $params);

        // $url = $this->getCheckWithdrawStatusUrl();
        $url_statuscheck = $this->getSystemInfo('url') . self::URLPATH_WITHDRAWQUERY;
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} checkWithdrawStatus url: ", $url_statuscheck );

        // $response = $this->submitGetForm($url, $param);
        $response = $this->submitPostForm($url_statuscheck, $params, false, $transId);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} checkWithdrawStatus result: ", $response );

        $result = $this->parseWithdrawalCheckResult($response, true);

        if (isset($result['action'])) {
            // Reject withdrawal order
            if ($result['action'] =='reject') {
                $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $result['message']);
            }
            unset($result['action']);
        }

        return $result;
    }

    public function parseWithdrawalCheckResult($result_str, $queryAPI = false) {
        $res = json_decode($result_str, 'as_array');
        $this->utils->debug_log(__METHOD__, "{$this->ident} withdrawal check res", $res);

        if ( !array_key_exists('status', $res)) {
            return [
                'success' => false ,
                'message' => 'malformed API result'
            ];
        }
        else {
            if ($res['status'] == self::WDSTATUS_PAID) {
                // Return success, approve order
                return [
                    'success'   => true ,
                    'message'   => '{$this->ident} withdrawal check result: APPROVED'
                ];
            }
            else if ($res['status'] == self::WDSTATUS_REJECTED) {
                // Return failed and additional action to reject order
                 return [
                    'success'   => false ,
                    'message'   => $res['msg'] ,
                    'action'    => 'reject'
                ];
            }
            else {
                // Return failed, no other action taken (stay in processing status)
                $mesg = sprintf("Withdrawal check results: status = %s, msg = %s", $res['status'], $res['msg']);
                return [
                    'success'   => false ,
                    'message'   => $mesg
                ];
            }
        }

    }

    protected function findBankCode($bank_id) {
        // $bank_row = $this->CI->banktype->getBankTypeById($bank_id);

        $bank_list = [
              1 => [ 'label' => '中國工商銀行', 'value' => 'ICBC' ] ,
              4 => [ 'label' => '中國農業銀行', 'value' => 'ABC' ] ,
              6 => [ 'label' => '中國銀行', 'value' => 'BOC' ] ,
              3 => [ 'label' => '中國建設銀行', 'value' => 'CCB' ] ,
              5 => [ 'label' => '交通銀行', 'value' => 'BOCOM' ] ,
             20 => [ 'label' => '中國光大銀行', 'value' => 'CEB' ] ,
             27 => [ 'label' => '上海浦東發展銀行', 'value' => 'SPDB' ] ,
             29 => [ 'label' => '北京銀行', 'value' => 'BCCB' ] ,
             26 => [ 'label' => '廣東發展銀行', 'value' => 'GDB' ] ,
             15 => [ 'label' => '平安銀行', 'value' => 'PAB' ] ,
             13 => [ 'label' => '興業銀行', 'value' => 'CIB' ] ,
              2 => [ 'label' => '招商銀行', 'value' => 'CMB' ] ,
             12 => [ 'label' => '中國郵政儲蓄銀行', 'value' => 'PSBC' ] ,
             14 => [ 'label' => '華夏銀行', 'value' => 'HXB' ] ,
             11 => [ 'label' => '民生銀行', 'value' => 'CMBC' ] ,
             10 => [ 'label' => '中信銀行', 'value' => 'ECITIC' ] ,
        ];

        if (isset($bank_list[$bank_id])) {
            return $bank_list[$bank_id]['value'];
        }
        else {
            return '---';
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        return $this->callbackFrom('server', $transId, $params, $response_result_id);
    }

    public function callbackFrom($source, $transId, $params, $response_result_id) {
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $this->CI->utils->debug_log("{$this->ident} process withdrawalResult transId", $transId);
        $this->CI->utils->debug_log("{$this->ident} checkCallback params", $params);

        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("{$this->ident} callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
        }

        $this->CI->utils->debug_log("{$this->ident} callbackFromServer json_decode params", $params);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == 1) {
            $msg = sprintf("{$this->ident} withdrawal payment successful, sn=%s", $params['sn']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
            $result['success'] = true;
            $result['message'] = 'success';
        }
        else {
            $msg = sprintf("{$this->ident} withdrawal payment failed, status=%s", $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        if (empty($fields)) {
            $this->writePaymentErrorLog("{$this->ident} Invalid API response, empty POST", $fields);
            return false;
        }

        $expected_fields = [ 'customerid', 'fee', 'finishtime', 'pushnoncestr', 'pushtime', 'sn', 'status', 'txmoney', 'sign' ];

        foreach ($expected_fields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog(sprintf("{$this->ident} Invalid API response, missing field: %s", $f), $fields);
                return false;
            }
        }

        $expected_sign = $this->calc_sign_callback($fields);
        if ($expected_sign != $fields['sign']) {
            $this->writePaymentErrorLog(sprintf("{$this->ident} sign mismatch, expected: %s, returned: %s", $expected_sign, $fields['sign']), $fields);
                return false;
        }


        if ($fields['status'] != 1) {
            $this->writePaymentErrorLog(sprintf("{$this->ident} checkCallback status=%s", $fields['status']), $fields);
            return false;
        }

        $sn_expected = $this->secureIdToSn($order['transactionCode']);
        if ($fields['sn'] != $sn_expected) {
            $this->writePaymentErrorLog(sprintf("{$this->ident} checkCallback sn mismatch, expected=%s, returned=%s", $sn_expected, $fields['sn']), $fields);
            return false;
        }

        return true;

    }

    public function secureIdToSn($secure_id) {
        $sn_prefix = $this->getSystemInfo('sn_prefix');

        $sn_full = $sn_prefix . self::SN_PREFIX_DEFAULT . $secure_id;

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} secureId-to-sn conversion", [ 'secure_id' => $secure_id, 'sn_prefix+default' => $sn_prefix . self::SN_PREFIX_DEFAULT , 'sn_full' => $sn_full ]);

        return $sn_full;
    }

}