<?php
require_once dirname(__FILE__) . '/abstract_payment_api_vnpay.php';
/**
 * VNPAY
 *
 * * VNPAY_WITHDRAWAL_PAYMENT_API, ID: 5818
 *
 * Required Fields:
 * * URL
 * * Key
 * * uid (merchant ID)
 *
 * Field Values:
 * * URL        : https://api.vnpay.one/applyfor
 * * Key        : ## Live key ##
 * * uid        : ## merchant ID ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_vnpay_withdrawal extends Abstract_payment_api_vnpay {

    public function getPlatformCode() {
        return VNPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'vnpay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $bankInfo = $this->getBankInfo();
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        // $params = array();
        // $params['txseq'] = $transId;
        // $params['memberBank'] = $bankInfo[$bank]['code'];
        // $params['memberAccount'] = $accNum;
        // $params['memberAccountName'] = $name;
        // $params['countryCode'] = $this->getSystemInfo('countryCode');
        // $params['group'] = $this->getSystemInfo('group');
        // $params['amount'] = $this->convertAmountToCurrency($amount);
        // $params['brand'] = $this->getSystemInfo('brand');

        $mapped_bank_id = $bankInfo[$bank]['code'];

        $this->CI->utils->debug_log(__METHOD__, 'vnpay_withdrawal basic creds', [ 'accNum' => $accNum, 'name' => $name, 'bank' => $bank, 'mapped_bank_id' => $mapped_bank_id, 'order' => $order ]);

        $params = [
            'uid'           => $this->getSystemInfo('uid') ,
            'orderid'       => $transId ,
            'channel'       => self::CHANNEL_WITHDRAWAL_DEFAULT ,
            'notify_url'    => $this->getNotifyUrl($transId) ,
            'amount'        => $this->convertAmountToCurrency($amount) ,
            'userip'        => $this->getClientIp() ,
            'timestamp'     => gmdate('U') ,
            'custom'        => '' ,
            'bank_account'  => $order['bankAccountFullName'],
            'bank_no'       => $order['bankAccountNumber'],
            'bank_id'       => $mapped_bank_id,
            'bank_province' => $order['bankProvince'],
            'bank_city'     => $order['bankCity'],
            'bank_sub'      => $order['bankBranch']
        ];

        $params['sign'] = $this->sign($params);

        $this->CI->utils->debug_log(__METHOD__, 'vnpay_withdrawal getWithdrawParams params', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log(__METHOD__, $result);
            return $result;
        }

        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log(__METHOD__, "withdrawal bank not supported", [ 'bankTypeId' => $bank ]);
            return array('success' => false, 'message' => 'withdrawal bank not supported');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        // $encrypt_params = $this->encrypt(json_encode($params));

        // $url = $this->getSystemInfo('url');

        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['orderid']);
        $this->CI->utils->debug_log(__METHOD__, 'params submit response', $response);

        $result = $this->decodeResult($response);

        $this->CI->utils->debug_log(__METHOD__, 'decoded result', $result);

        return $result;

        // list($content, $response_result) = $this->submitPostForm($url, $encrypt_params, true, $transId, true);
        // $decodedResult = $this->decodeResult($content);
        // $this->CI->utils->debug_log(__METHOD__, 'vnpay_withdrawal submitWithdrawRequest decoded Result', $decodedResult);
        // $decodedResult['response_result'] = $response_result;

        // return $decodedResult;
    }

    public function decodeResult($resp, $queryAPI = false) {
        $result = json_decode($resp, true);
        $this->utils->debug_log(__METHOD__, "vnpay_withdrawal json_decode result", $result);


        if ( !isset($result['status']) || !isset($result['result']) || !isset($result['sign']) ) {
            $res_mesg = [
                'success'   => false ,
                // 'type'      => self::REDIRECT_TYPE_ERROR ,
                'message'   => lang('Invalid API response')
            ];
        }
        else if ($result['status'] != self::STATUS_SUCCESSFUL) {
            $mesg = isset($this->status_mesgs[$result['status']]) ? $this->status_mesgs[$result['status']] : '(unknown error)';
            $res_mesg = [
                'success'   => false ,
                // 'type'      => self::REDIRECT_TYPE_ERROR ,
                'message'   => sprintf("Error %d: %s", $result['status'], $mesg)
            ];
        }
        else if (!$this->verify_callback_sign($result)) {
            $res_mesg = [
                'success'   => false ,
                // 'type'      => self::REDIRECT_TYPE_ERROR ,
                'message'   => lang('Sign does not match')
            ];
        }
        else {
            $res_mesg = [
                'success' => true ,
                'message' => sprintf('vnpay withdrawal successful, transaction ID: %d', $result['result']['transactionid'])
            ];
        }

        return $res_mesg;
    }

    // public function decodeResult($resultString, $queryAPI = false) {


    //     $respCode = $result['resultCode'];
    //     $resultMsg = $result['resultDescription'];
    //     $this->utils->debug_log(__METHOD__, "vnpay_withdrawal withdrawal resultMsg", $resultMsg);

    //     if($respCode == self::RESULT_CODE_SUCCESS) {
    //         $message = "smartpay_withdrawal request successful.";
    //         return array('success' => true, 'message' => $message);
    //     }
    //     else {
    //         if($resultMsg == '' || $resultMsg == false) {
    //             $this->utils->error_log(__METHOD__, "vnpay_withdrawal return UNKNOWN ERROR!");
    //             $resultMsg = "未知错误";
    //         }

    //         $message = "smartpay_withdrawal withdrawal response, Code: [ ".$respCode." ] , Msg: ".$resultMsg;
    //         return array('success' => false, 'message' => $message);
    //     }
    // }

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
            $this->utils->debug_log("==================getting smartpay_withdrawal bank info from extra_info: ", $bankInfo);
        } else {
            // The default bankInfo is listed here for reference; clients that uses this payment need to set up these banks in system banktype and include them in extra info of payment API.
            $bankInfo = [
                // 101 => [ 'code' => 1548, 'name' => 'VIB' ] ,
                // 102 => [ 'code' => 1549, 'name' => 'VPBank' ] ,
                // 103 => [ 'code' => 2001, 'name' => 'BIDV' ] ,
                // 104 => [ 'code' => 2002, 'name' => 'VietinBank' ] ,
                // 105 => [ 'code' => 2003, 'name' => 'SHB' ] ,
                // 106 => [ 'code' => 2004, 'name' => 'ABBANK' ] ,
                // 107 => [ 'code' => 2005, 'name' => 'AGRIBANK' ] ,
                // 108 => [ 'code' => 2006, 'name' => 'Vietcombank' ] ,
                // 109 => [ 'code' => 2007, 'name' => 'Techcom' ] ,
                // 110 => [ 'code' => 2008, 'name' => 'ACB' ] ,
                // 111 => [ 'code' => 2009, 'name' => 'SCB' ] ,
                // 112 => [ 'code' => 2011, 'name' => 'MBBANK' ] ,
                // 113 => [ 'code' => 2012, 'name' => 'EIB' ] ,
                // 114 => [ 'code' => 2020, 'name' => 'STB' ] ,
                // 115 => [ 'code' => 2031, 'name' => 'DongABank' ] ,
                // 116 => [ 'code' => 2032, 'name' => 'GPBank' ] ,
                // 117 => [ 'code' => 2033, 'name' => 'Saigonbank' ] ,
                // 118 => [ 'code' => 2034, 'name' => 'PG Bank' ] ,
                // 119 => [ 'code' => 2035, 'name' => 'Oceanbank' ] ,
                // 120 => [ 'code' => 2036, 'name' => 'NamABank' ] ,
                // 121 => [ 'code' => 2037, 'name' => 'TPB' ] ,
                // 122 => [ 'code' => 2038, 'name' => 'HDB' ] ,
                // 123 => [ 'code' => 2039, 'name' => 'VAB' ] ,
            ];

            $this->utils->debug_log("=======================getting smartpay_withdrawal bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
}