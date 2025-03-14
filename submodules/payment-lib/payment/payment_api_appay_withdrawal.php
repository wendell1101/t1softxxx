<?php
require_once dirname(__FILE__) . '/abstract_payment_api_appay.php';
/**
 * YOURSITE
 *
 * * APPAY_WITHDRAWAL_PAYMENT_API, ID: 5926
 *
 * Required Fields:
 * * URL
 * * Account    (merchant id)
 * * Key        (md5key)
 *
 * Field Values:
 * * URL        https://apiclient.ap2pay.com
 * * Account    ## merchant id ##
 * * Key        ## md5key ##
 *
 *  * Required fields:
 *     bankifsc (india-only bank identifier, precise to each branch)
 *
 * @category Payment
 * @copyright 2021 TripleOneTech
 */
class Payment_api_appay_withdrawal extends Abstract_payment_api_appay {

    // protected $path_wd_status    = 'api/withdrawrequestStatus';
    // protected $path_wd_request   = 'api/withdrawrequest';

    // const ERRORCODE_WITHDRAWAL_SUCCESS  = 1;
    // const ERRORCODE_WITHDRAWAL_FAILED   = 2;

    // const STATUSCODE_WITHDRAWAL_CHECK_PENDING       = 'PD';
    // const STATUSCODE_WITHDRAWAL_CHECK_APPROVED      = 'AP';
    // const STATUSCODE_WITHDRAWAL_CHECK_PROCESSING    = 'PR';
    // const STATUSCODE_WITHDRAWAL_CHECK_REJECTED      = 'RJ';

    const WX_TYPE_DEFAULT               = '107';
    const WX_CURRENCY_DEFAULT           = '1';          // 1=INR
    const WX_COUNTRY_DEFAULT            = 'IN';         // IN=India
    const WX_BANKCODE_DEFAULT           = 'BITOLO';
    const WX_ADDRESS_DEFAULT            = '';

    const WX_CALLBACK_STATUS_SUBMITTED   = 0;
    const WX_CALLBACK_STATUS_APPROVED    = 1;
    const WX_CALLBACK_STATUS_READY       = 2;
    const WX_CALLBACK_STATUS_SUCCESSFUL  = 3;
    const WX_CALLBACK_STATUS_UNSUCCESSFUL= 4;
    const WX_CALLBACK_STATUS_ERROR       = 5;
    const WX_CALLBACK_STATUS_PENDING     = 6;
    const WX_CALLBACK_STATUS_WAITING     = 7;

    protected $wx_callback_status_text = [
        self::WX_CALLBACK_STATUS_SUBMITTED   => 'SUBMITTED' ,
        self::WX_CALLBACK_STATUS_APPROVED    => 'APPROVED' ,
        self::WX_CALLBACK_STATUS_READY       => 'READY' ,
        self::WX_CALLBACK_STATUS_SUCCESSFUL  => 'SUCCESSFUL' ,
        self::WX_CALLBACK_STATUS_UNSUCCESSFUL=> 'UNSUCCESSFUL' ,
        self::WX_CALLBACK_STATUS_ERROR       => 'ERROR' ,
        self::WX_CALLBACK_STATUS_PENDING     => 'PENDING' ,
        self::WX_CALLBACK_STATUS_WAITING     => 'WAITING' ,
    ];

    public function getPlatformCode() {
        return APPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'appay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {

        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $this->CI->load->library([ 'ifsc_razorpay_lib' ]);
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $bank_name = $this->findBankName($bank);

        $bank_ifsc = $order['bankBranch'];
        // $bank_branch = $this->CI->ifsc_razorpay_lib->get_branch_bank($bank_ifsc);
        // read combined details of bank branch
        $bank_branch = $this->CI->ifsc_razorpay_lib->get_branch_combined_details($bank_ifsc);

        $player = $this->CI->player_model->getPlayerDetailArrayById($order['playerId']);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident}_WITHDRAWAL basic creds", [ 'accNum' => $accNum, 'name' => $name, 'bank' => $bank, 'bank_name' => $bank_name, 'bank_ifsc' => $bank_ifsc, 'bank_branch' => $bank_branch ]);

        // $params = [
        //     "requestid"         => $transId ,
        //     "amount"            => $this->convertAmountToCurrency($amount) ,
        //     "accountnumber"     => $order['bankAccountNumber'] ,
        //     "bankname"          => $bank_name ,
        //     // "branchname"        => $order['bankBranch'] ,
        //     "branchname"        => $bank_branch ,
        //     "accountholdername" => $order['bankAccountFullName'] ,
        //     "IfscCode"          => $bank_ifsc ,
        //     "phone"             => $player['contactNumber']
        // ];

        $params = [
            'usercode'      => $this->getSystemInfo('account') ,
            'username'      => self::PAY_USERNAME_DEFAULT ,
            'customno'      => $transId ,
            'type'          => self::WX_TYPE_DEFAULT ,
            'money'         => $this->convertAmountToCurrency($amount) ,
            'currency'      => self::WX_CURRENCY_DEFAULT ,
            'country'       => self::WX_COUNTRY_DEFAULT ,
            'city'          => $bank_branch['city'] ,
            'realname'      => $name ,
            'cardno'        => $order['bankAccountNumber'] ,
            'address'       => $bank_branch['addr'] ,
            'bankname'      => $bank_branch['bank'],
            'bankaddress'   => $bank_branch['addr'] ,
            'branchname'    => $bank_branch['branch'] ,
            'bankifsc'      => $bank_ifsc ,
            'bankcode'      => self::WX_BANKCODE_DEFAULT ,
            'sendtime'      => $this->pay_timestamp() ,
            'notifyurl'     => $this->getNotifyUrl($transId) ,
            'buyerip'       => $this->getClientIp() ,
            // 'buyerip'       => '220.135.118.23' ,
        ];

        $params['sign'] = $this->calc_sign_wx_request($params);

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

        // $bankInfo = $this->getBankInfo();
        // if(!array_key_exists($bank, $bankInfo)) {
        //     $this->utils->error_log(__METHOD__, "withdrawal bank not supported", [ 'bankTypeId' => $bank ]);
        //     return array('success' => false, 'message' => 'withdrawal bank not supported');
        // }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);

        if (empty($params['bankifsc'])) {
            return [
                'success' => false ,
                'message' => 'IFSC not set, please set IFSC code of your withdrawal account'
            ];
        }

        if (empty($params['branchname'])) {
            return [
                'success' => false ,
                'message' => 'Cannot find the bank branch corresponding to account IFSC code'
            ];
        }
        $url = $this->getWithdrawUrl($params);
        list($content, $response_result) = $this->submitPostForm($url, [], false, $transId, true);
        $decodedResult = $this->decodeWxResult($content, $amount, $transId);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('======================================appay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================appay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
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
            $req_fields_result = [ 'success', 'resultCode'];
            foreach ($req_fields_result as $rf) {
                if (!isset($result[$rf])) {
                    throw new Exception("Callback field missing: {$rf}", 0x31);
                }
            }

            if ($result['success'] != true) {
                throw new Exception("success != true, resultMsg={$result['resultMsg']} ({$result['resultCode']})", 0x32);
            }

            if (!isset($result['data'])) {
                throw new Exception("Callback field missing: data", 0x33);
            }

            // working with result.data
            $data = $result['data'];

            // check for required fields (data)
            $req_fields_data = [ 'usercode', 'customno', 'tjmoney', 'sign' ];
            foreach ($req_fields_data as $rf) {
                if (!isset($data[$rf])) {
                    throw new Exception("data field missing: {$rf}", 0x34);
                }
            }

            // check sign
            $sign_expected = $this->calc_sign_wx_resp($data);
            if ($data['sign'] != $sign_expected) {
                throw new Exception("sign mismatch, sign={$data['sign']}, expected={$sign_expected}", 0x35);
            }

            // check usercode
            if ($this->getSystemInfo('account') != $data['usercode']) {
                throw new Exception("usercode mismatch, received={$data['usercode']}, expected={$this->getSystemInfo('account')}", 0x36);
            }

            // check tjmoney
            if ($amount != $data['tjmoney']) {
                throw new Exception("tjmoney mismatch, received={$data['tjmoney']}, expected={$amount}", 0x37);
            }

            // check customno
            if ($transId != $data['customno']) {
                throw new Exception("customno mismatch, received={$data['customno']}, expected={$transId}", 0x38);
            }

            $ret = [
                'success' => true ,
                'message' => sprintf("{$this->ident}_WITHDRAWAL request submitted successfully, transaction ID: %s", $data['customno'])
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
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("{$this->ident}_WITHDRAWAL callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("{$this->ident}_WITHDRAWAL callbackFromServer json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log("{$this->ident}_WITHDRAWAL callbackFromServer transId", $transId);
        $this->CI->utils->debug_log("{$this->ident}_WITHDRAWAL callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if (in_array($params['status'], [ self::WX_CALLBACK_STATUS_SUCCESSFUL ])) {
            $msg = sprintf("{$this->ident}_WITHDRAWAL successful: trade ID=%s", $params['customno']);
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

    public function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array('orderno', 'usercode', 'customno', 'type', 'currency', 'cardno', 'realname', 'tjmoney', 'money', 'status', 'sign');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================appay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        $sign_expected = $this->calc_sign_wx_callback($fields);
        if ($fields['sign'] != $sign_expected) {
            $this->writePaymentErrorLog('=========================appay withdrawal checkCallback signature Error',$fields);
            return false;
        }

        if ($fields['customno'] != $order['transactionCode']) {
            $this->writePaymentErrorLog("======================appay withdrawal checkCallbackOrder order IDs do not match, expected ".$order['transactionCode'], $fields);
            return false;
        }

        $amount = $this->convertAmountToCurrency($order['amount']);
        if ($fields['tjmoney'] != $amount) {
            $this->writePaymentErrorLog("======================appay withdrawal checkCallbackOrder payment amount is wrong, expected ". $amount, $fields);
            return false;
        }
        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }

    protected function findBankName($bank_id) {
        $bank_row = $this->CI->banktype->getBankTypeById($bank_id);
        $bank_name = lang($bank_row->bankName);

        return $bank_name;
    }

    public function getWithdrawUrl($params) {
        $url = $this->getSystemInfo('url') . self::URL_PATH_WITHDRAW;
        $query_string = '';
        foreach ($params as $key => $value) {
            $query_string .= $key . '=' . $value . '&';
        }
        $query_string = rtrim($query_string,'&');
        return $url.'?'.$query_string;
    }

    protected function getCheckWithdrawStatusUrl() {
        $url = $this->getSystemInfo('url');
        return $this->getSystemInfo('url') . self::URL_PATH_WITHDRAWAL_QUERY;
    }

    protected function calc_sign_wx_request($params) {
        $fields = [ 'usercode', 'customno', 'bankifsc', 'type', 'cardno', 'money' , 'sendtime', 'buyerip' ];
        $sign_ar = $this->calc_sign_general($params, $fields);

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for wx-request", [ 'params' => $params, 'sign_ar' => $sign_ar ]);
        return $sign_ar['sign'];
    }

    protected function calc_sign_wx_resp($params) {
        $fields = [ 'usercode', 'type', 'bankcode', 'customno', 'orderno', 'tjmoney', 'money', 'cardno', 'status' ];
        $sign_ar = $this->calc_sign_general($params, $fields, '');

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for wx-resp", [ 'params' => $params, 'sign_ar' => $sign_ar ]);
        return $sign_ar['sign'];
    }

    protected function calc_sign_wx_callback($params) {
        // $fields = [ 'usercode', 'orderno', 'bankcode', 'customno', 'type', 'cardno', 'tjmoney', 'money', 'status', 'currency' ];
        $fields = [ 'usercode', 'orderno', 'bankcode', 'customno', 'type', 'cardno', 'idcard', 'tjmoney', 'money', 'status', 'currency' ];
        $sign_ar = $this->calc_sign_general($params, $fields, '|');

        $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc for wx-callback", [ 'params' => $params, 'sign_ar' => $sign_ar ]);
        return $sign_ar['sign'];
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

    protected function calc_sign_general($params, $fields, $separator = '|') {
        $plain_ar = [];

        foreach ($fields as $f) {
            $val = isset($params[$f]) ? $params[$f] : 'null';
            $plain_ar[] = $val;
        }

        // Append md5key
        $plain_ar[] = $this->getSystemInfo('key');

        $plain = implode($separator, $plain_ar);

        $sign = md5($plain);

        $ret = [ 'plain' => $plain, 'sign' => $sign ];

        // $this->CI->utils->debug_log(__METHOD__, "{$this->ident} sign calc general", );

        return $ret;

    }
}