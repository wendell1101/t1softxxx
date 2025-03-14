<?php
require_once dirname(__FILE__) . '/abstract_payment_api_heropay.php';

/**
 * HEROPAY_WITHDRAWAL
 *
 * * HEROPAY_WITHDRAWAL_PAYMENT_API, ID:5854
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://47.251.34.145:3020/api/trans/create_order
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_heropay_withdrawal extends Abstract_payment_api_heropay {

    const CHANNLETYPE = '1';
    const RESPONSE_ORDER_SUCCESS = 'SUCCESS';
    const CALLBACK_STATUS_SUCCESS = '2';
    const RETURN_SUCCESS_CODE = 'success';


    public function getPlatformCode() {
        return HEROPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'heropay_withdrawal';
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
            $this->utils->error_log("========================heropay withdrawal bank whose bankTypeId=[$bank] is not supported by heropay");
            return array('success' => false, 'message' => 'Bank not supported by heropay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        $post_params['params'] = json_encode($params);

        list($response, $response_result) = $this->submitPostForm($url, $post_params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================heropay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================heropay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================heropay submitWithdrawRequest decoded Result', $decodedResult);

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
        $bankName = $bankInfo[$bank]['name'];
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("===============================heropay Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
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
        $params['mchId']            = $this->getSystemInfo("account");
        $params['appId']            = $this->getSystemInfo('appId');
        $params['mchTransOrderNo']  = $transId;
        $params['currency']         = $this->getSystemInfo('currency');
        $params['amount']           = $this->convertAmountToCurrency($amount);
        $params['notifyUrl']        = $this->getNotifyUrl($transId);
        $params['bankCode']         = $bankCode;
        $params['bankName']         = $bankName;
        $params['accountType']      = self::CHANNLETYPE;
        $params['accountName']      = $name;
        $params['accountNo']        = $accNum;
        $params['province']         = $province;
        $params['city']             = $city;
        $params['sign']             = $this->sign($params);

        $this->CI->utils->debug_log('=========================heropay getWithdrawParams params', $params);

        return $params;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $result = json_decode($resultString, true);
        $this->utils->debug_log("=========================heropay json_decode result", $result);

        if(isset($result['retCode'])) {
            if($result['retCode'] == self::RESPONSE_ORDER_SUCCESS) {
                $message = "heropay withdrawal response successful, code:".$result['retCode'];
                return array('success' => true, 'message' => $message);
            }
            $message = "heropay withdrawal response failed. [".$result['retCode']."]: ".$result['retMsg'];
            return array('success' => false, 'message' => $message);

        }
        elseif(isset($result['retMsg']) && $result['retMsg']){
            $message = 'heropay withdrawal response: '.$result['retMsg'];
            return array('success' => false, 'message' => $message);
        }
        return array('success' => false, 'message' => "heropay decoded fail.");
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================heropay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================heropay json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================heropay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================heropay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['status'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('heropay withdrawal success: trade ID [%s]', $params['mchTransOrderNo']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        // else if ($params['Status'] != self::ORDER_STATUS_PROCESS && $params['Status'] != self::ORDER_STATUS_CREATED) {
        //     $msg = sprintf('heropay withdrawal failed: [%s]', $params['Message']);
        //     $this->writePaymentErrorLog($msg, $fields);
        //     $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
        //     $result['message'] = $msg;
        // }
        else {
            $msg = 'heropay withdrawal payment was not successful';
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array(
            'mchTransOrderNo', 'amount', 'status', 'sign'
        );
        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================heropay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================heropay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['status'] != self::CALLBACK_STATUS_SUCCESS) {
            $this->writePaymentErrorLog("======================heropay withdrawal checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================heropay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['mchTransOrderNo'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================heropay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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
            $this->utils->debug_log("==================getting heropay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '63' => array('name' => 'Ngân hàng Á Châu', 'code' => 'VPT00001'),
                '58' => array('name' => 'Ngân hàng K​ỹ Th​ươ​ng Việ​ t Nam', 'code' => 'VPT00002'),
                '61' => array('name' => 'Ngân hàng Sài Gòn Th​ươ​ng Tín', 'code' => 'VPT00003'),
                '57' => array('name' => 'Ngân hàng Ngo​ại th​ươ​ng Việ​ t Nam', 'code' => 'VPT00004'),
                '60' => array('name' => 'NgânhàngĐ​ ầut​ư​vàPháttri​ểnVi​ệtNam', 'code' => 'VPT00006'),
                '82' => array('name' => 'NgânhàngĐ​ ạiD​ươ​ng', 'code' => 'VPT00008'),

                '59' => array('name' => 'Ngân hàng Nông nghi​ệp và Phát tri​ển Nông thôn', 'code' => 'VPT00010'),
                '83' => array('name' => 'Ngân hàng Tiên Phong', 'code' => 'VPT00011'),
                '76' => array('name' => 'Ngân hàng ​Đ​ông Nam', 'code' => 'VPT00013'),
                '64' => array('name' => 'Ngân hàng Hàng H​ải Vi​ệt Nam', 'code' => 'VPT00017'),
                '84' => array('name' => 'Ngân hàng Nam Á', 'code' => 'VPT00019'),
                '86' => array('name' => 'Ngân hàng Quố​ c Dân', 'code' => 'VPT00020'),
                '65' => array('name' => 'Ngân hàng Vi​ệt Nam Th​ịnh V​ượng', 'code' => 'VPT00021'),
                '72' => array('name' => 'Ngân hàng Phát tri​ển nhà Thành phố​ H​ồ Chí', 'code' => 'VPT00022'),
                '67' => array('name' => 'Ngân hàng Quân ​đội', 'code' => 'VPT00024'),
                '70' => array('name' => 'Ngân hàng Quố​ c tế', 'code' => 'VPT00026'),
                '75' => array('name' => 'Ngân hàng Sài Gòn', 'code' => 'VPT00027'),
                '77' => array('name' => 'Ngân hàng Sài Gòn Công Th​ươn​g', 'code' => 'VPT00028'),
                '71' => array('name' => 'Ngân hàng Sài Gòn-Hà N​ội', 'code' => 'VPT00029'),
                '74' => array('name' => 'Ngân hàng Xu​ất Nhậ​ p kh​ẩu Vi​ệt', 'code' => 'VPT00033'),
            );
            $this->utils->debug_log("=======================getting heropay bank info from code: ", $bankInfo);
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
            if( $key == 'sign' || empty($value)) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= "key=".$this->getSystemInfo('key');
        return $signStr;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}