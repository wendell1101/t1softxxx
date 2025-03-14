<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pgtianci.php';

/**
 * PGTIANCI_WITHDRAWAL
 *
 * * PGTIANCI_WITHDRAWAL_PAYMENT_API, ID: 5889
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
class Payment_api_pgtianci_withdrawal extends Abstract_payment_api_pgtianci {
    const STATUS_SUCCESSFUL = 'completed';
    const RETURN_SUCCESS_CODE = 'ok';

    public function getPlatformCode() {
        return PGTIANCI_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'pgtianci_withdrawal';
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
            $this->utils->error_log("========================pgtianci withdrawal bank whose bankTypeId=[$bank] is not supported by pgtianci");
            return array('success' => false, 'message' => 'Bank not supported by pgtianci');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        $response = $this->processCurl($params);
        if (empty($response)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================pgtianci withdrawal raw_post_data", $raw_post_data);
            $response = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================pgtianci withdrawal json_decode params", $params);
        }

        $decodedResult = $this->decodeResult($response);

        $this->CI->utils->debug_log('======================================pgtianci submitWithdrawRequest url: ', $url );
        $this->CI->utils->debug_log('======================================pgtianci submitWithdrawRequest response', $response);
        $this->CI->utils->debug_log('======================================pgtianci submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        # look up bank code
        $bankInfo = $this->getBankInfo();
        $bankCode = $bankInfo[$bank]['code'];

        $params = array();
        $params['out_trade_no'] = $transId;
        $params['bank_id'] = $bankCode;
        $params['bank_owner'] = $name;
        $params['account_number'] = $accNum;
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['callback_url'] = $this->getNotifyUrl($transId);
        $params['sign'] = $this->sign($params);

        $this->utils->debug_log('=========================pgtianci getWithdrawParams params', $params);

        return $params;
    }

    public function decodeResult($resultString) {
        if(isset($resultString['success']) && $resultString['success']) {
            $message = "pgtianci withdrawal response successful";
            return array('success' => true, 'message' => $message);
        }elseif (isset($resultString['status_code'])) {
            $message = 'pgtianci withdrawal response: ' . $this->getReturnErrorMsg($resultString['status_code']);
            if(!empty($resultString['errors'])){
                $errMsg = json_encode($resultString['errors'], JSON_UNESCAPED_UNICODE);
                $message .= ' [ ' . $errMsg . ' ]';
            }
            return array('success' => false, 'message' => $message);
        }else{
            return array('success' => false, 'message' => "pgtianci decoded fail.");
        }
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        if (empty($params)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================pgtianci callbackFromServer raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data, true);
            $this->CI->utils->debug_log("=====================pgtianci callbackFromServer json_decode params", $params);
        }

        $result = array('success' => false, 'message' => 'Payment failed');

        $this->CI->utils->debug_log('=========================pgtianci callbackFromServer transId', $transId);
        $this->CI->utils->debug_log("=========================pgtianci callbackFromServer params", $params);

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if (!$this->checkCallbackOrder($order, $params)) {
            return $result;
        }

        if ($params['state'] == self::STATUS_SUCCESSFUL) {
            $msg = sprintf('pgtianci withdrawal success: trade ID [%s]', $params['out_trade_no']);
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else {
            $msg = sprintf('pgtianci withdrawal payment was not successful: [%s]', $params['state']);
            $this->writePaymentErrorLog($msg, $params);
            $result['message'] = $msg;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields) {
        $requiredFields = [ 'state', 'out_trade_no', 'sign', 'amount' ];

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("======================pgtianci withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if (!$this->validateSign($fields)) {
            $this->writePaymentErrorLog('=========================pgtianci withdrawal checkCallback signature Error', $fields);
            return false;
        }

        if ($fields['state'] != self::STATUS_SUCCESSFUL) {
            $this->writePaymentErrorLog("======================pgtianci withdrawal checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================pgtianci withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['out_trade_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================pgtianci withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
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
            $this->utils->debug_log("==================getting pgtianci bank info from extra_info: ", $bankInfo);
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
                '71' => array('name' => 'NGAN HANG BUU DIEN LIEN VIET (LIENVIETPOSTBANK)', 'code' => 'LVPB'),
                '73' => array('name' => 'Ngân Hàng Quốc Tế (VIB)', 'code' => 'VIB'),
                '74' => array('name' => 'NGAN HANG TMCP SAI GON - HA NOI (SHB)', 'code' => 'SHB'),
                '75' => array('name' => 'NGAN HANG TMCP PHAT TRIEN TP.HCM (HDBANK)', 'code' => 'HDB'),
                '76' => array('name' => 'NGAN HANG TMCP AN BINH (ABBANK)', 'code' => 'ABB'),
                '77' => array('name' => 'NGAN HANG TMCP XUAT NHAP KHAU VIET NAM (EXIMBANK)', 'code' => 'EIB'),
                '78' => array('name' => 'NGAN HANG TMCP SAI GON (SCB)', 'code' => 'SCB'),
                '79' => array('name' => 'NGAN HANG TMCP DONG NAM A (SEABANK)', 'code' => 'SEABANK'),
                '80' => array('name' => 'Ngân hàng TMCP Sài Gòn Công Thương (SAIGONBANK)', 'code' => 'SAIGONBANK'),
                '81' => array('name' => 'Ngân hàng TMCP Đại Chúng Việt Nam (PVCOMBANK)', 'code' => 'PVCOMBANK'),
                '82' => array('name' => 'Ngân hàng TMCP Bắc Á (BAC A BANK)', 'code' => 'BACABANK'),
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

    public function sign($params) {
        $signStr = '';
        ksort($params);
        foreach($params as $key => $value) {
            $signStr .= "$key=$value&";
        }
        $signStr = rtrim($signStr, '&');
        $signStr .= $this->getSystemInfo('key').$this->getSystemInfo('callback_token');
        $sign = md5($signStr);

        return $sign;
    }
}