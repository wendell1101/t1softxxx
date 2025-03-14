<?php
require_once dirname(__FILE__) . '/abstract_payment_api_mgppay.php';

/**
 * MGPPAY_WITHDRAWAL
 *
 * * MGPPAY_WITHDRAWAL_PAYMENT_API, ID: 6172
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://khgri4829.com:6084/api/defray/V2
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_mgppay_withdrawal extends Abstract_payment_api_mgppay {
    const CHANNLETYPE = 1;
    const RESPONSE_ORDER_SUCCESS = '0';
    const CALLBACK_STATUS_SUCCESS = '1';

    public function getPlatformCode() {
        return MGPPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'mgppay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info) {}
    protected function processPaymentUrlForm($params) {}
    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================mgppay withdrawal bank whose bankTypeId=[$bank] is not supported by mgppay");
            return array('success' => false, 'message' => 'Bank not supported by mgppay');
        }

        $this->_custom_curl_header = array('Content-Type: application/json');
        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, true, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================mgppay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================mgppay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================mgppay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        # look up bank code
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================mgppay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $province = $playerBankDetails['province'];
            $city = $playerBankDetails['city'];
            $bankBranch = $playerBankDetails['branch'];
        } else {
            $province = 'none';
            $city = 'none';
            $bankBranch = 'none';
        }

        $province = empty($province) ? "none" : $province;
        $city = empty($city) ? "none" : $city;
        $bankBranch = empty($bankBranch) ? "none" : $bankBranch;

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $params = array();
        $params['version']        = 'V2';
        $params['signType']       = 'MD5';
        $params['merchantNo']     = $this->getSystemInfo("account");
        $params['date']           = date("Y-m-d h:i:s");
        $params['channleType']    = $this->getSystemInfo('channleType', self::CHANNLETYPE);
        $params['orderNo']        = $transId;
        $params['bizAmt']         = $this->convertAmountToCurrency($amount);
        $params['accName']        = $name;
        $params['bankCode']       = $bankCode;
        $params['bankBranchName'] = $bankBranch;
        $params['cardNo']         = $accNum;
        $params['noticeUrl']      = $this->getNotifyUrl($transId);
        $params['openProvince']   = $province;
        $params['openCity']       = $city;
        $params['sign']           = $this->sign($params);

        $this->CI->utils->debug_log('=========================mgppay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================mgppay json_decode result", $result);

        if(isset($result['code'])) {
            if($result['code'] == self::RESPONSE_ORDER_SUCCESS) {
                $message = "mgppay withdrawal response successful, code:[".$result['code']."]: ".$result['msg'];
                return array('success' => true, 'message' => $message);
            }
            $message = "mgppay withdrawal response failed. [".$result['code']."]: ".$result['msg'];
            return array('success' => false, 'message' => $message);

        }
        elseif($result['msg']){
            $message = 'mgppay withdrawal response: '.$result['message'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "mgppay decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================mgppay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================mgppay json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================mgppay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================mgppay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('mgppay withdrawal success: trade ID [%s]', $params['orderNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS;
            $result['success'] = true;
        }
        // else if ($params['Status'] != self::ORDER_STATUS_PROCESS && $params['Status'] != self::ORDER_STATUS_CREATED) {
        //     $msg = sprintf('mgppay withdrawal failed: [%s]', $params['Message']);
        //     $this->writePaymentErrorLog($msg, $fields);
        //     $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
        //     $result['message'] = $msg;
        // }
        else {
            $msg = sprintf('mgppay withdrawal payment was not successful: [%s]', $params['Message']);
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'orderNo', 'bizAmt', 'status', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================mgppay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================mgppay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['status'] != self::CALLBACK_STATUS_SUCCESS) {
            $this->writePaymentErrorLog("======================mgppay withdrawal checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['bizAmt'] != $this->convertAmountToCurrency($order['amount'])) {
            if ($this->getSystemInfo('allow_callback_amount_diff')) {
                $diffAmount = abs($this->convertAmountToCurrency($order->amount) - floatval($fields['bizAmt']));
                if ($diffAmount >= 1) {
                    $this->writePaymentErrorLog("=====================mgppay checkCallbackOrder Payment amounts ordAmt - payAmount > 1, expected [$order->amount]", $fields, $diffAmount);
                    return false;
                }
            }else {
                $this->writePaymentErrorLog("=====================mgppay withdrawal amounts do not match, expected [$order->amount]", $fields);
                return false;
            }
        }

        if ($fields['orderNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================mgppay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function callbackFromBrowser($transId, $params) {
        return array('success' => false, 'next_url' => null, 'message' => 'Error: not implemented');
    }

    # -- bankinfo --
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
            $this->utils->debug_log("==================getting mgppay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '60' => array('name' => 'Ngân hàng Nông nghiệp và Phát triển Nông thôn Việt Nam (AGRIBANK)', 'code' => 'V_AGRIBANK'),
                '61' => array('name' => 'NGAN HANG TMCP DAU TU VA PHAT TRIEN VIET NAM (BIDV)', 'code' => 'V_BIDV'),
                '62' => array('name' => 'Ngan hang TMCP Sai Gon Thuong Tin (Sacombank)', 'code' => 'V_SACOMBANK'),
                '63' => array('name' => 'NGAN HANG TMCP CONG THUONG VIET NAM (VIETINBANK)', 'code' => 'V_VIETINBANK'),
                '64' => array('name' => 'NGAN HANG TMCP NGOAI THUONG VIET NAM (VIETCOMBANK)', 'code' => 'V_VIETCOMBANK'),
                '65' => array('name' => 'NGAN HANG TMCP A CHAU (ACB)', 'code' => 'V_ACB'),
                '66' => array('name' => 'NGAN HANG TMCP HANG HAI VIET NAM (MARITIME BANK)', 'code' => 'V_MSB'),
                '67' => array('name' => 'NGAN HANG TMCP VIET NAM THINH VUONG (VPBANK)', 'code' => 'V_VPBANK'),
                '68' => array('name' => 'Ngân Hàng TMCP Đông Á  (DONG A BANK)', 'code' => 'V_DONGABANK'),
                '71' => array('name' => 'NGAN HANG BUU DIEN LIEN VIET (LIENVIETPOSTBANK)', 'code' => 'V_LIENVIETPOSTBANK'),
                '73' => array('name' => 'Ngân Hàng Quốc Tế (VIB)', 'code' => 'V_VIB'),
                '74' => array('name' => 'NGAN HANG TMCP SAI GON - HA NOI (SHB)', 'code' => 'V_SHB'),
                '75' => array('name' => 'NGAN HANG TMCP PHAT TRIEN TP.HCM (HDBANK)', 'code' => 'V_HDBANK'),
                '76' => array('name' => 'NGAN HANG TMCP AN BINH (ABBANK)', 'code' => 'V_ABBANK'),
                '77' => array('name' => 'NGAN HANG TMCP XUAT NHAP KHAU VIET NAM (EXIMBANK)', 'code' => 'V_EXIMBANK'),
                '78' => array('name' => 'NGAN HANG TMCP SAI GON (SCB)', 'code' => 'V_SCB'),
                '79' => array('name' => 'NGAN HANG TMCP DONG NAM A (SEABANK)', 'code' => 'V_SEABANK'),
                '80' => array('name' => 'Ngân hàng TMCP Sài Gòn Công Thương (SAIGONBANK)', 'code' => 'V_SAIGONBANK'),
                '81' => array('name' => 'Ngân hàng TMCP Đại Chúng Việt Nam (PVCOMBANK)', 'code' => 'V_PVBANK'),
                '84' => array('name' => 'Ngân hàng thương mại cổ phần Việt Á (VIET A BANK)', 'code' => 'V_VIETABANK'),
                '85' => array('name' => 'NGAN HANG TMCP DAI DUONG (OCEANBANK)', 'code' => 'V_OCEANBANK'),
                '86' => array('name' => 'NGAN HANG TMCP TIEN PHONG (TPBANK)', 'code' => 'V_TPBANK'),
                '87' => array('name' => 'Ngân hàng TMCP Nam Á (NAM A BANK)', 'code' => 'V_NAMABANK'),
                '89' => array('name' => 'NGAN HANG TMCP QUOC DAN (NCB)', 'code' => 'V_NCB')
            );
            $this->utils->debug_log("=======================getting mgppay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- signatures --
    public function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        return $sign;
    }

    public function createSignStr($params) {
        $signStr = '';
        ksort($params);
        foreach($params as $key => $value) {
            if( ($key == 'sign') || empty($value)) {
                continue;
            }

            $signStr.=$key."=".$value."&";
        }
        $signStr = rtrim($signStr, '&');
        $signStr .= $this->getSystemInfo('key');
        return $signStr;
    }

    public function validateSign($params) {
        $signStr = $this->createSignStr($params);
        $sign = md5($signStr);
        if($params['sign'] == $sign){
            return true;
        }
        else{
            return false;
        }
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}