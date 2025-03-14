<?php
require_once dirname(__FILE__) . '/abstract_payment_api_kolapay.php';

/**
 * KOLAPAY_WITHDRAWAL
 *
 * * KOLAPAY_WITHDRAWAL_PAYMENT_API, ID: 6132
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://tianciv070115.com/api/payment
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_kolapay_withdrawal extends Abstract_payment_api_kolapay {
    const RESULT_STATUS_SUCCESS = '200';
    const CALLBACK_STATUS_SUCCESS = 'true';

    public function getPlatformCode() {
        return KOLAPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'kolapay_withdrawal';
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
        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================kolapay submitWithdrawRequest bank whose bankTypeId=[$bank] is not supported by kolapay");
            return array('success' => false, 'message' => 'Bank not supported by kolapay');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();
        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('======================================kolapay submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================kolapay submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================kolapay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        # look up bank code
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $bankInfo = $this->getBankInfo();
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $bankCode = $bankInfo[$bank]['code'];
        $params = array();
        $params['merchantId'] = $this->getSystemInfo("account");
        $params['billId'] = $transId;
        $params['bankName'] = $bankCode;
        $params['bankAccount'] = $accNum;
        $params['bankOwner'] = $name;
        $params['notifyUrl'] = $this->getNotifyUrl($transId);
        $params['amount'] = $this->convertAmountToCurrency($amount); //元
        $params['sign'] = $this->sign($params);
        $this->utils->debug_log('=========================kolapay getWithdrawParams params', $params);
        return $params;
    }

    public function decodeResult($resultString) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }
        $decodeResult = json_decode($resultString, true);
        if($decodeResult['code'] == self::RESULT_STATUS_SUCCESS) {
            $message = 'kolapay withdrawal request success.';
            return array('success' => true, 'message' => $message);
        }
        else {
            if(empty($decodeResult['msg'])) {
                $message = "exist errors";
            }else {
                $message = "kolapay withdrawal request failed: ".$decodeResult['msg'];
            }
            return array('success' => false, 'message' => $message);
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================kolapay callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================kolapay callbackFromServer json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================kolapay callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================kolapay callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['isDone'] == self::CALLBACK_STATUS_SUCCESS) {
            $msg = sprintf('kolapay withdrawal success: trade ID [%s]', $params['billId']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = 'kolapay withdrawal payment was not successful';
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = array('billId', 'amount','isDone','sign');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================kolapay withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================kolapay withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['isDone'] != self::CALLBACK_STATUS_SUCCESS) {
            $this->writePaymentErrorLog("======================kolapay withdrawal checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================kolapay withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['billId'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================kolapay withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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
            $this->utils->debug_log("==================getting kolapay bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '60' => array('name' => 'Ngân hàng Nông nghiệp và Phát triển Nông thôn Việt Nam (AGRIBANK)', 'code' => 'AGRIBANK'),
                '61' => array('name' => 'NGAN HANG TMCP DAU TU VA PHAT TRIEN VIET NAM (BIDV)', 'code' => 'BIDV'),
                '62' => array('name' => 'Ngan hang TMCP Sai Gon Thuong Tin (Sacombank)', 'code' => 'SACOMBANK'),
                '63' => array('name' => 'NGAN HANG TMCP CONG THUONG VIET NAM (VIETINBANK)', 'code' => 'VTB'),
                '64' => array('name' => 'NGAN HANG TMCP NGOAI THUONG VIET NAM (VIETCOMBANK)', 'code' => 'VCB'),
                '65' => array('name' => 'NGAN HANG TMCP A CHAU (ACB)', 'code' => 'ACB'),
                '66' => array('name' => 'NGAN HANG TMCP HANG HAI VIET NAM (MARITIME BANK)', 'code' => 'MSB'),
                '67' => array('name' => 'NGAN HANG TMCP VIET NAM THINH VUONG (VPBANK)', 'code' => 'VPB'),
                '68' => array('name' => 'Ngân Hàng TMCP Đông Á  (DONG A BANK)', 'code' => 'DONGABANK'),
                '69' => array('name' => 'NGAN HANG TMCP QUAN DOI (MB)', 'code' => 'MB'),
                '70' => array('name' => 'NGAN HANG TMCP KY THUONG VIET NAM (TCB)', 'code' => 'TCB'),
                '71' => array('name' => 'NGAN HANG BUU DIEN LIEN VIET (LIENVIETPOSTBANK)', 'code' => 'LPB'),
                '73' => array('name' => 'Ngân Hàng Quốc Tế (VIB)', 'code' => 'VIB'),
                '74' => array('name' => 'NGAN HANG TMCP SAI GON - HA NOI (SHB)', 'code' => 'SHBVN'),
                '75' => array('name' => 'NGAN HANG TMCP PHAT TRIEN TP.HCM (HDBANK)', 'code' => 'HDB'),
                '76' => array('name' => 'NGAN HANG TMCP AN BINH (ABBANK)', 'code' => 'ABBANK'),
                '77' => array('name' => 'NGAN HANG TMCP XUAT NHAP KHAU VIET NAM (EXIMBANK)', 'code' => 'EXIMBANK'),
                '78' => array('name' => 'NGAN HANG TMCP SAI GON (SCB)', 'code' => 'SCB'),
                '79' => array('name' => 'NGAN HANG TMCP DONG NAM A (SEABANK)', 'code' => 'SEABANK'),
                '80' => array('name' => 'Ngân hàng TMCP Sài Gòn Công Thương (SAIGONBANK)', 'code' => 'SAIGONBANK'),
                '81' => array('name' => 'Ngân hàng TMCP Đại Chúng Việt Nam (PVCOMBANK)', 'code' => 'PVCOMBANK'),
                '82' => array('name' => 'Ngân hàng TMCP Bắc Á (BAC A BANK)', 'code' => 'BAB'),
                '84' => array('name' => 'Ngân hàng thương mại cổ phần Việt Á (VIET A BANK)', 'code' => 'VIETABANK'),
                '85' => array('name' => 'NGAN HANG TMCP DAI DUONG (OCEANBANK)', 'code' => 'OJB'),
                '86' => array('name' => 'NGAN HANG TMCP TIEN PHONG (TPBANK)', 'code' => 'TPB'),
                '87' => array('name' => 'Ngân hàng TMCP Nam Á (NAM A BANK)', 'code' => 'NAMABANK'),
                '89' => array('name' => 'NGAN HANG TMCP QUOC DAN (NCB)', 'code' => 'NCB')
            );
            $this->utils->debug_log("=======================getting aipay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    public function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

}