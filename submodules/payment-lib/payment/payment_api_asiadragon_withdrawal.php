<?php
require_once dirname(__FILE__) . '/abstract_payment_api_asiadragon.php';
/**
 * ASIADRAGON
 *
 * * ASIADRAGON_WITHDRAWAL_PAYMENT_API, ID: 5929
 *
 * Required Fields:
 * * URL
 * * Account    (merchant id)
 * * extra_info.merchant_priv_key
 * * extra_info.platform_public_key
 *
 * Field Values:
 * * URL        http://api.asia-pay8.com/api/unifiedorder
 * * Account    ## merchant id #
 * * extra_info.merchant_priv_key       ## merchant private key ##
 * * extra_info.platform_public_key     ## platform public key ##
 *
 *  * Required fields:
 *     ? bankifsc (india-only bank identifier, precise to each branch)
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_asiadragon_withdrawal extends Abstract_payment_api_asiadragon {

    const URL_PATH_WITHDRAW             = '/api/agentPay/draw';
    const URL_PATH_WITHDRAW_QUERY       = '/api/agentPay/query';

    const WX_REQ_TYPE_BANKCARD          = '1';
    const WX_REQ_TYPE_UPI               = '2';
    const WX_REQ_TYPE_DEFAULT           = '1';

    const WX_REQ_IFSC_DEFAULT           = 1;

    const WX_RESP_CODE_SUCCESS          = 0;
    const WX_RESP_CODE_FAILURE          = 500;

    const WX_CALLBACK_STATUS_PAID       = 2;
    const WX_CALLBACK_STATUS_REJECTED   = 3;

    protected $wx_callback_status_text = [
        self::WX_CALLBACK_STATUS_PAID       => 'paid' ,
        self::WX_CALLBACK_STATUS_REJECTED   => 'rejected' ,
    ];

    public function getPlatformCode() {
        return ASIADRAGON_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'asiadragon_withdrawal';
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

        // $params = [
        //     'usercode'      => $this->getSystemInfo('account') ,
        //     'username'      => self::PAY_USERNAME_DEFAULT ,
        //     'customno'      => $transId ,
        //     'type'          => self::WX_TYPE_DEFAULT ,
        //     'money'         => $this->convertAmountToCurrency($amount) ,
        //     'currency'      => self::WX_CURRENCY_DEFAULT ,
        //     'country'       => self::WX_COUNTRY_DEFAULT ,
        //     'city'          => $bank_branch['city'] ,
        //     'realname'      => $name ,
        //     'cardno'        => $order['bankAccountNumber'] ,
        //     'address'       => $bank_branch['addr'] ,
        //     'bankname'      => $bank_branch['bank'],
        //     'branchname'    => $bank_branch['branch'] ,
        //     'bankifsc'      => $bank_ifsc ,
        //     'bankcode'      => self::WX_BANKCODE_DEFAULT ,
        //     'sendtime'      => $this->pay_timestamp() ,
        //     'notifyurl'     => $this->getNotifyUrl($transId) ,
        //     'buyerip'       => $this->getClientIp() ,
        //     // 'buyerip'       => '220.135.118.23' ,
        // ];

        $params = [
            'mchId'         => $this->getSystemInfo('account') ,
            'banknumber'    => $order['bankAccountNumber'] ,
            'bankfullname'  => $order['bankAccountFullName'] ,
            // 'bankname'      => '' ,
            'tkmoney'       => $this->convertAmountToCurrency($amount) ,
            'orderid'       => $transId ,
            'notifyurl'     => $this->getNotifyUrl($transId) ,
            // 'walletUrl'     => '' ,
            'type'          => self::WX_REQ_TYPE_DEFAULT ,
            'mail'          => $player['email'] ,
            'ifsc'          => self::WX_REQ_IFSC_DEFAULT ,
            'branchName'    => $order['bankBranch'] ,
            'mobile'        => $player_details['contactNumber'] ,
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

        // if (empty($params['bankifsc'])) {
        //     return [
        //         'success' => false ,
        //         'message' => 'IFSC not set, please set IFSC code of your withdrawal account'
        //     ];
        // }

        // if (empty($params['branchname'])) {
        //     return [
        //         'success' => false ,
        //         'message' => 'Cannot find the bank branch corresponding to account IFSC code'
        //     ];
        // }

        $response = $this->submitPostForm($this->getWithdrawUrl(), $params, false, $params['orderid']);
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

        if (in_array($params['status'], [ self::WX_CALLBACK_STATUS_PAID ])) {
            $msg = sprintf("{$this->ident}_WITHDRAWAL successful: trade ID=%s", $params['orderid']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $state_text = $this->wx_callback_state_to_text($params['status']);
            $msg = sprintf("{$this->ident}_WITHDRAWAL payment unsuccessful or pending: status=%s (%s)", $state_text, $params['status']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    protected function wx_callback_state_to_text($status) {
        if (isset($this->wx_callback_status_text[$status])) {
            return $this->wx_callback_status_text[$status];
        }

        return 'UNKNOWN';
    }

    private function checkWxCallbackOrder($order, $fields) {
        try {
            // check for fields
            $req_fields = [ 'orderid', 'mchId', 'status', 'sign' ];

            foreach ($req_fields as $f) {
                if (!array_key_exists($f, $fields)) {
                    throw new Exception("Callback field missing: {$f}", 0x41);
                }
            }

            if (!$this->verify_sign_wx_callback($fields)) {
                throw new Exception("sign verification failure", 0x42);
            }

            // check mchId
            if ($this->getSystemInfo('account') != $fields['mchId']) {
                throw new Exception("mchId mismatch, received={$fields['mchId']}, expected={$this->getSystemInfo('account')}", 0x43);
            }

            // check orderId
            if ($order['transactionCode'] != $fields['orderid']) {
                throw new Exception("orderid mismatch, received={$fields['orderid']}, expected={$order['transactionCode']}", 0x44);
            }

            // check status
            // if (self::WX_CALLBACK_STATUS_PAID != $fields['status']) {
            //     throw new Exception("status != paid, received={$fields['status']}, expected=" . self::WX_CALLBACK_STATUS_PAID, 0x44);
            // }

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

    // public function checkWithdrawStatus($transId) {
    //     $this->CI->load->model(array('wallet_model'));

    //     $params = [ 'requestid' => $transId ];

    //     $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL checkWithdrawStatus params: ", $params);

    //     $url = $this->getCheckWithdrawStatusUrl();
    //     $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL checkWithdrawStatus url: ", $url );

    //     // $response = $this->submitGetForm($url, $param);
    //     $response = $this->submitPostForm($url, $params, 'as_json', $transId);

    //     $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL checkWithdrawStatus result: ", $response );

    //     $result = $this->parseWithdrawalCheckResult($response, true);

    //     if (isset($result['action'])) {
    //         // Reject withdrawal order
    //         if ($result['action'] == 'reject') {
    //             $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $result['message']);
    //         }
    //         unset($result['action']);
    //     }

    //     return $result;
    // }

    // public function parseWithdrawalCheckResult($result_str, $queryAPI = false) {
    //     $res = json_decode($result_str, 'as_array');
    //     $this->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL withdrawal check res", $res);

    //     if ( !array_key_exists('StatusName', $res) || !array_key_exists('StatusCode', $res) ) {
    //         return [
    //             'success' => false ,
    //             'message' => 'malformed API result'
    //         ];
    //     }
    //     else {
    //         if ($res['StatusCode'] == self::STATUSCODE_WITHDRAWAL_CHECK_APPROVED) {
    //             // Return success, approve order
    //             return [
    //                 'success'   => true ,
    //                 'message'   => "{$this->ident}_WITHDRAWAL withdrawal check result: APPROVED"
    //             ];
    //         }
    //         else if ($res['StatusCode'] == self::STATUSCODE_WITHDRAWAL_CHECK_REJECTED) {
    //             // Return failed and additional action to reject order
    //              return [
    //                 'success'   => false ,
    //                 'message'   => $mesg ,
    //                 'action'    => 'reject'
    //             ];
    //         }
    //         else {
    //             // Return failed, no other action taken (stay in processing status)
    //             $mesg = sprintf("{$this->ident}_WITHDRAWAL withdrawal check results: Status = %s (%s), ErrorCode = %d (%s)", $res['StatusName'], $res['StatusCode'], $res['ErrorCode'], $res['ErrorMessage']);
    //             return [
    //                 'success'   => false ,
    //                 'message'   => $mesg
    //             ];
    //         }
    //     }

    // }

    // public function getPlayerInputInfo() {
    //     return array(
    //         array('name' => 'wx_bank_city', 'type' => 'text', 'size' => 50, 'label_lang' => 'withdrawal_bank_city'),
    //     );
    // }

    protected function findBankName($bank_id) {
        $bank_row = $this->CI->banktype->getBankTypeById($bank_id);
        $bank_name = lang($bank_row->bankName);

        return $bank_name;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url') . self::URL_PATH_WITHDRAW;
    }

    protected function getCheckWithdrawStatusUrl() {
        $url = $this->getSystemInfo('url');
        return $this->getSystemInfo('url') . self::URL_PATH_WITHDRAWAL_QUERY;
    }

    // protected function calc_sign_wx_request($params) {
    //     $fields = [ 'usercode', 'customno', 'bankifsc', 'type', 'cardno', 'money' , 'sendtime', 'buyerip' ];
    //     $sign_ar = $this->calc_sign_general($params, $fields);

    //     $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for wx-request", [ 'params' => $params, 'sign_ar' => $sign_ar ]);
    //     return $sign_ar['sign'];
    // }

    // protected function calc_sign_wx_resp($params) {
    //     $fields = [ 'usercode', 'type', 'bankcode', 'customno', 'orderno', 'tjmoney', 'money', 'cardno', 'status' ];
    //     $sign_ar = $this->calc_sign_general($params, $fields, '');

    //     $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for wx-resp", [ 'params' => $params, 'sign_ar' => $sign_ar ]);
    //     return $sign_ar['sign'];
    // }

    // protected function calc_sign_wx_callback($params) {
    //     // $fields = [ 'usercode', 'orderno', 'bankcode', 'customno', 'type', 'cardno', 'tjmoney', 'money', 'status', 'currency' ];
    //     $fields = [ 'usercode', 'orderno', 'bankcode', 'customno', 'type', 'cardno', 'idcard', 'tjmoney', 'money', 'status', 'currency' ];
    //     $sign_ar = $this->calc_sign_general($params, $fields, '|');

    //     $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for wx-callback", [ 'params' => $params, 'sign_ar' => $sign_ar ]);
    //     return $sign_ar['sign'];
    // }

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