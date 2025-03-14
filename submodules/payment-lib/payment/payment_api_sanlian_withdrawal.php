<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sanlian.php';
/**
 * SANLIAN
 *
 * * SANLIAN_PAYMENT_WITHDRAWAL_API, ID: 5937
 *
 * Required Fields:
 * * URL
 * * Account    (merchant id)
 * * extra_info.request_key
 * * extra_info.callback_key
 *
 * Field Values:
 * * URL        http://api.asia-pay8.com/api/unifiedorder
 * * Account    ## merchant id #
 * * extra_info.request_key      ## request key ##
 * * extra_info.callback_key     ## callback key ##
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_sanlian_withdrawal extends Abstract_payment_api_sanlian {

    const URL_PATH_WITHDRAW                 = '/paycenter/withdraw/withdraw';
    const URL_PATH_WITHDRAW_QUERY           = '/paycenter/withdraw/check';

    const WX_REQ_DRAWTYPE_BANKCARD          = 8201;
    const WX_REQ_DRAWTYPE_ALIPAY            = 8202;
    const WX_REQ_DRAWTYPE_DEFAULT           = 8201;

    const WX_REQ_SIGN_TYPE_DEFAULT          = 'md5';

    const WX_RESP_CODE_SUCCESS              = '200';

    const WX_CALLBACK_CALLBACKS_SUCCESS     = 'CODE_SUCCESS';
    const WX_CALLBACK_CALLBACKS_FAILURE     = 'CODE_FAILURE';

    const WX_QUERY_RESP_CODE_SUCCESS        = '200';


    public function getPlatformCode() {
        return SANLIAN_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'sanlian_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {

        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        // $this->CI->load->library([ 'ifsc_razorpay_lib' ]);
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $bank_name = $this->findBankName($bank);

        // $bank_ifsc = $order['bankBranch'];
        // $bank_branch = $this->CI->ifsc_razorpay_lib->get_branch_bank($bank_ifsc);
        // read combined details of bank branch
        // $bank_branch = $this->CI->ifsc_razorpay_lib->get_branch_combined_details($bank_ifsc);

        $player = $this->CI->player_model->getPlayerArrayById($order['playerId']);
        $player_details = $this->CI->player_model->getPlayerDetailArrayById($order['playerId']);

        // $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL basic creds", [ 'accNum' => $accNum, 'name' => $name, 'bank' => $bank, 'bank_name' => $bank_name, 'bank_ifsc' => $bank_ifsc, 'bank_branch' => $bank_branch ]);

        $client_ip = $this->getClientIp();
        // $client_ip = '220.135.118.23';

        $params = [
            'appid'             => $this->getSystemInfo('account') ,
            'drawtype'          => self::WX_REQ_DRAWTYPE_DEFAULT ,
            'order_id'          => $transId ,
            'amount'            => $this->convertAmountToCurrency($amount) ,
            'in_card_name'      => $order['bankAccountFullName'] ,
            'in_card_no'        => $order['bankAccountNumber'] ,
            'in_bank_name'      => lang($order['bankName']) ,
            'in_branch_name'    => $order['bankBranch'] ,
            'client_ip'         => $client_ip ,
            'sign_type'         => self::WX_REQ_SIGN_TYPE_DEFAULT ,
            'out_uid'           => $order['playerId'] ,
        ];

        $params['sign'] = $this->calc_sign_wx_req($params);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL getWithdrawParams params", $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->CI->utils->debug_log(__METHOD__, $result);
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        $response = $this->submitPostForm($this->getWithdrawUrl(), $params, false, $params['order_id']);
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL params submit response", $response);

        $result = $this->decodeWxResult($response, $amount, $transId);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL decoded result", $result);

        return $result;

    }

    // public function decodeResult($resp, $queryAPI = false) {
    public function decodeWxResult($resp, $amount, $transId) {
        // $wx_order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $result = json_decode($resp, true);
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL json_decode result", $result);

        $success = false;
        $message = "{$this->ident}_WITHDRAWAL decodeWxResult exec incomplete";

        try {
            // check for required fields (result)
            $req_fields_result = [ 'code', 'msg' ];
            foreach ($req_fields_result as $rf) {
                if (!isset($result[$rf])) {
                    throw new Exception("Callback field missing: {$rf}", 0x31);
                }
            }

            if (self::WX_RESP_CODE_SUCCESS != $result['code']) {
                throw new Exception("success != true, resultMsg={$result['msg']} ({$result['code']})", 0x32);
            }

            if (isset($result['data']) && isset($result['data']['out_trade_no'])) {
                if ($result['data']['out_trade_no'] != $transId) {
                    throw new Exception("out_trade_no mismatch, received={$result['data']['out_trade_no']}, expected={$transId}", 0x32);
                }
            }

            $ret = [
                'success' => true ,
                'message' => "{$this->ident}_WITHDRAWAL request submitted successfully"
            ];
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL decodeResult", 'return', $ret);
        }
        catch (Exception $ex) {
            $ex_code = $ex->getCode();
            $msg = $ex->getMessage();
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL decodeResult", sprintf("exception: (0x%x) %s", $ex_code, $msg));
            $ret = [
                'success' => false ,
                'message' => sprintf(sprintf("Error: %s", $msg))
            ];
        }
        finally {
            return $ret;
        }

    } // End function decodeResult()

    /**
     * Extracts orderId from fixed callback requests
     * Fixed callback URI: /callback/fixed_process/<payment_id>
     */
    public function getOrderIdFromParameters($params) {
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} getOrderIdFromParameters", "raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} getOrderIdFromParameters", "json_decode params", $params);
        }

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} getOrderIdFromParameters", "params", $params);

        if (!isset($params['order_id'])) {
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} getOrderIdFromParameters", "cannot find order id in callback request");
            return;
        }

        $this->CI->load->model([ 'sale_order','wallet_model' ]);
        $out_trade_no = $params['order_id'];
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} getOrderIdFromParameters", "out_trade_no", $out_trade_no);
        if (substr($out_trade_no, 0, 1) == 'D') {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($out_trade_no);
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident} getOrderIdFromParameters", "order_id", ($order ? $order->id : '(order=null)') );
            return $order->id;
        }
        else {
            return $out_trade_no;
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServerBare($transId, $params);

        // Use $_REQUEST: see Abstract_payment_api_asiadragon::callbackFrom()
        $params = $_REQUEST;

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

        // if (in_array($params['status'], [ self::WX_CALLBACK_STATUS_PAID ])) {
        if (self::WX_CALLBACK_CALLBACKS_SUCCESS == $params['callbacks']) {
            $msg = sprintf("{$this->ident}_WITHDRAWAL successful: trade ID=%s", $params['order_id']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            // $state_text = $this->wx_callback_state_to_text($params['status']);
            // $msg = sprintf("{$this->ident}_WITHDRAWAL payment unsuccessful or pending: status=%s (%s)", $state_text, $params['status']);
            $msg = "{$this->ident}_WITHDRAWAL payment callback failure";
            $result['message'] = $msg;
        }

        return $result;
    }

    // protected function wx_callback_state_to_text($status) {
    //     if (isset($this->wx_callback_status_text[$status])) {
    //         return $this->wx_callback_status_text[$status];
    //     }

    //     return 'UNKNOWN';
    // }

    private function checkWxCallbackOrder($order, $fields) {
        try {
            // check for fields
            // $req_fields = [ 'orderid', 'mchId', 'status', 'sign' ];
            $req_fields = [ 'callbacks', 'appid', 'order_id', 'fee', 'amount', 'sign', 'out_uid'  ];

            foreach ($req_fields as $f) {
                if (!array_key_exists($f, $fields)) {
                    throw new Exception("Callback field missing: {$f}", 0x41);
                }
            }

            // check sign
            $sign_expected = $this->calc_sign_wx_callback($fields);
            if ($sign_expected != $fields['sign']) {
                throw new Exception("sign mismatch, received={$fields['sign']}, expected={$sign_expected}", 0x42);
            }

            // check appid
            if ($this->getSystemInfo('account') != $fields['appid']) {
                throw new Exception("appid mismatch, received={$fields['appid']}, expected={$this->getSystemInfo('account')}", 0x43);
            }

            if ($order['transactionCode'] != $fields['order_id']) {
                throw new Exception("order_id mismatch, received={$fields['order_id']}, expected={$order['transactionCode']}", 0x43);
            }

            $amount_expected = $this->convertAmountToCurrency($order['amount']);
            $amount_received = $this->convertAmountToCurrency($fields['amount']);
            if ($amount_expected != $amount_received) {
                throw new Exception("amount mismatch, received={$amount_received} ({$fields['order_id']}), expected={$amount_expected}", 0x44);
            }

            if (self::WX_CALLBACK_CALLBACKS_SUCCESS != $fields['callbacks']) {
                throw new Exception("callbacks unexpected, received={$fields['callbacks']}, expected=" . self::WX_CALLBACK_CALLBACKS_SUCCESS, 0x45);
            }

            $wx_check_res = $this->checkWithdrawStatus($order);
            $this->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL wx_check_res", $wx_check_res);
            if (false == $wx_check_res) {
                throw new Exception("withdrawal check failed, check result={$wx_check_res}", 0x46);
            }

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

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }

    public function checkWithdrawStatus($order) {
        $this->CI->load->model(array('wallet_model'));

        $transId = $order['transactionCode'];

        // $params = [ 'requestid' => $transId ];
        $params = [
            'appid'             => $this->getSystemInfo('account') ,
            'out_trade_no'      => $order['transactionCode'] ,
            'amount'            => $this->convertAmountToCurrency($order['amount']) ,
        ];

        $params['sign'] = $this->calc_sign_wx_query($params);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL checkWithdrawStatus params: ", $params);

        $url = $this->getCheckWithdrawStatusUrl();
        $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL checkWithdrawStatus url: ", $url );

        $response = $this->submitPostForm($url, $params, false, $order['transactionCode']);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL checkWithdrawStatus result: ", $response );

        $result = $this->parseWithdrawalCheckResult($response);

        $this->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL withdrawal check result", $result);

        if (isset($result['action'])) {
            // Reject withdrawal order
            if ($result['action'] == 'reject') {
                $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $result['message']);
            }
            unset($result['action']);
        }

        return $result['success'];
    }

    public function parseWithdrawalCheckResult($result_str, $queryAPI = false) {
        $fields = json_decode($result_str, 'as_array');
        $this->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL withdrawal check res", $fields);

        if (!isset($fields['code'])) {
            return [
                'success' => false ,
                'message' => 'malformed API result, code not found'
            ];
        }

        if (self::WX_QUERY_RESP_CODE_SUCCESS != $fields['code']) {
            $mesg = "{$this->ident}_WITHDRAWAL withdrawal check result: FAILED";
            if (isset($fields['msg'])) {
                $mesg .= "; msg={$fields['msg']}";
            }
            return [
                'success'   => false ,
                'message'   => $mesg ,
                'action'    => 'reject'
            ];
        }

        if (self::WX_QUERY_RESP_CODE_SUCCESS == $fields['code']) {
            return [
                'success'   => true ,
                'message'   => "{$this->ident}_WITHDRAWAL withdrawal check result: APPROVED"
            ];
        }

        // if ( !array_key_exists('StatusName', $res) || !array_key_exists('StatusCode', $res) ) {
        //     return [
        //         'success' => false ,
        //         'message' => 'malformed API result'
        //     ];
        // }
        // else {
        //     if ($res['StatusCode'] == self::STATUSCODE_WITHDRAWAL_CHECK_APPROVED) {
        //         // Return success, approve order
        //         return [
        //             'success'   => true ,
        //             'message'   => "{$this->ident}_WITHDRAWAL withdrawal check result: APPROVED"
        //         ];
        //     }
        //     else if ($res['StatusCode'] == self::STATUSCODE_WITHDRAWAL_CHECK_REJECTED) {
        //         // Return failed and additional action to reject order
        //          return [
        //             'success'   => false ,
        //             'message'   => $mesg ,
        //             'action'    => 'reject'
        //         ];
        //     }
        //     else {
        //         // Return failed, no other action taken (stay in processing status)
        //         $mesg = sprintf("{$this->ident}_WITHDRAWAL withdrawal check results: Status = %s (%s), ErrorCode = %d (%s)", $res['StatusName'], $res['StatusCode'], $res['ErrorCode'], $res['ErrorMessage']);
        //         return [
        //             'success'   => false ,
        //             'message'   => $mesg
        //         ];
        //     }
        // }

    }

    // public function getPlayerInputInfo() {
    //     return array(
    //         array('name' => 'wx_bank_city', 'type' => 'text', 'size' => 50, 'label_lang' => 'withdrawal_bank_city'),
    //     );
    // }

    protected function calc_sign_wx_req($params) {
        $key = $this->getSystemInfo('request_key');
        $calc_res = $this->calc_sign_general($params, $key);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for wx-request", [ 'key' => $key ], $calc_res);

        return $calc_res['sign'];
    }

    protected function calc_sign_wx_callback($params) {
        $sign_key = $this->getSystemInfo('callback_key');

        $expected_fields = [ 'callbacks', 'appid', 'order_id', 'fee', 'amount', 'out_uid' ];
        foreach ($params as $key=>$val) {
            if (!in_array($key, $expected_fields)) {
                unset($params[$key]);
            }
        }

        $calc_res = $this->calc_sign_general($params, $sign_key, [ 'sign' ]);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for wx-callback", [ 'sign_key' => $sign_key ], $calc_res);

        return $calc_res['sign'];
    }

    protected function calc_sign_wx_query($params) {
        $sign_key = $this->getSystemInfo('request_key');

        $calc_res = $this->calc_sign_general($params, $sign_key);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for wx-query", [ 'sign_key' => $sign_key ], $calc_res);

        return $calc_res['sign'];
    }

    protected function findBankName($bank_id) {
        $bank_row = $this->CI->banktype->getBankTypeById($bank_id);
        $bank_name = lang($bank_row->bankName);

        return $bank_name;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url') . self::URL_PATH_WITHDRAW;
    }

    protected function getCheckWithdrawStatusUrl() {
        return $this->getSystemInfo('url') . self::URL_PATH_WITHDRAW_QUERY;
    }

    public function getBankInfo() {
        $bankInfo = array();
        $bankInfoArr = $this->getSystemInfo("withdrawal_bank_info");
        if(!empty($bankInfoArr)) {
            foreach($bankInfoArr as $system_bank_type_id => $bankInfoItem) {
                if(isset($bankInfoItem['name'])){
                    $bankInfo[$system_bank_type_id]['name'] = $bankInfoItem['name'];
                }
                if(isset($bankInfoItem['ifsc'])){
                    $bankInfo[$system_bank_type_id]['ifsc'] = $bankInfoItem['ifsc'];
                }
            }
            $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = [ ];

            $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL WARNING: no bankInfo available");
        }
        return $bankInfo;
    }
}