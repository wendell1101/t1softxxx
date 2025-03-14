<?php
require_once dirname(__FILE__) . '/abstract_payment_api_smartpay.php';
/**
 * SMARTPAY
 *
 * * SMARTPAY_WITHDRAWAL_PAYMENT_API, ID: 5680
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_smartpay_withdrawal extends Abstract_payment_api_smartpay {

    public function getPlatformCode() {
        return SMARTPAY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'smartpay_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        # look up bank code
        $bankInfo = $this->getBankInfo();
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        $params = array();
        $params['txseq'] = $transId;
        $params['memberBank'] = $bankInfo[$bank]['code'];
        $params['memberAccount'] = $accNum;
        $params['memberAccountName'] = $name;
        $params['countryCode'] = $this->getSystemInfo('countryCode');
        $params['group'] = $this->getSystemInfo('group');
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['brand'] = $this->getSystemInfo('brand');

        $this->CI->utils->debug_log('=========================smartpay_withdrawal getWithdrawParams params', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }

        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================smartpay_withdrawal withdrawal bank whose bankTypeId=[$bank] is not supported by smartpay_withdrawal");
            return array('success' => false, 'message' => 'Bank not supported by smartpay_withdrawal');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $encrypt_params = $this->encrypt(json_encode($params));

        $url = $this->getSystemInfo('url');

        list($content, $response_result) = $this->submitPostForm($url, $encrypt_params, true, $transId, true);
        $decodedResult = $this->decodeResult($content);
        $this->CI->utils->debug_log('=========================smartpay_withdrawal submitWithdrawRequest decoded Result', $decodedResult);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {
        $result = json_decode($this->decrypt($resultString), true);
        $this->utils->debug_log("=========================smartpay_withdrawal json_decode result", $result);

        $respCode = $result['resultCode'];
        $resultMsg = $result['resultDescription'];
        $this->utils->debug_log("=========================smartpay_withdrawal withdrawal resultMsg", $resultMsg);

        if($respCode == self::RESULT_CODE_SUCCESS) {
            $message = "smartpay_withdrawal request successful.";
            return array('success' => true, 'message' => $message);
        }
        else {
            if($resultMsg == '' || $resultMsg == false) {
                $this->utils->error_log("========================smartpay_withdrawal return UNKNOWN ERROR!");
                $resultMsg = "未知错误";
            }

            $message = "smartpay_withdrawal withdrawal response, Code: [ ".$respCode." ] , Msg: ".$resultMsg;
            return array('success' => false, 'message' => $message);
        }
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
            $this->utils->debug_log("==================getting smartpay_withdrawal bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '28' => array('name' => 'Bangkok Bank', 'code' => '001'),
                '29' => array('name' => 'Krung Thai Bank', 'code' => '003'),
                '30' => array('name' => 'Siam Commerical Bank', 'code' => '023'),
                '31' => array('name' => 'Kasikorn Thai Bank', 'code' => '002'),
                '32' => array('name' => 'Thai Military Bank', 'code' => '004'),
                '33' => array('name' => 'Krungsri Ayudhaya Bank', 'code' => '010'),
                '34' => array('name' => 'CIMB Bank', 'code' => '008'),
                '35' => array('name' => 'Citi Bank', 'code' => '005'),
                '37' => array('name' => 'Kiatnakin Bank', 'code' => '019'),
                '38' => array('name' => 'Standard Chartered Bank', 'code' => '007'),
                '39' => array('name' => 'Thanachart Bank', 'code' => '016'),
            );

            $this->utils->debug_log("=======================getting smartpay_withdrawal bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
}