<?php
require_once dirname(__FILE__) . '/abstract_payment_api_onewallet.php';

/**
 * ONEWALLET
 *
 * * ONEWALLET_WITHDRAWAL_PAYMENT_API, ID: 5685
 * * ONEWALLET_WITHDRAWAL_2_PAYMENT_API, ID: 5686
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL: https://api-tg.100scrop.tech/11-dca/SH/sendWithdraw
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_onewallet_withdrawal extends Abstract_payment_api_onewallet {
    public function getPlatformCode() {
        return ONEWALLET_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'onewallet_withdrawal';
    }

    public function __construct($params = null) {
        parent::__construct($params);
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $this->CI->load->model(array('wallet_model', 'player_model', 'playerbankdetails'));
        $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAccount($bank, $accNum);
        $this->utils->debug_log("Get playerBankDetails using [$bank] + [$accNum]", $playerBankDetails);
        if(!empty($playerBankDetails)){
            $province    = empty($playerBankDetails['province'])    ? "无" : $playerBankDetails['province'];
            $city        = empty($playerBankDetails['city'])        ? "无" : $playerBankDetails['city'];
            $bankBranch  = empty($playerBankDetails['branch'])      ? "无" : $playerBankDetails['branch'];
            $bankAddress = empty($playerBankDetails['bankAddress']) ? "无" : $playerBankDetails['bankAddress'];
        } else {
            $bankBranch  = '无';
            $province    = '无';
            $city        = '无';
            $bankAddress = '无';
        }

        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);
        $orderDateTime = $order['dwDateTime'];

        # look up bank code
        $bankInfo = $this->getBankInfo();

        $params = array();
        $params['sh_order_no']    = $transId;
        $params['bank_code']      = $bankInfo[$bank]['code'];
        $params['bank_account']   = $accNum;
        $params['bank_user_name'] = $name;
        $params['order_amount']   = $this->convertAmountToCurrency($amount);
        $params['notify_url']     = $this->getNotifyUrl($transId);

        $this->CI->utils->debug_log('=========================OneWallet getWithdrawParams params', $params);
        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            $this->utils->debug_log($result);
            return $result;
        }

        # look up bank code
        $bankInfo = $this->getBankInfo();
        if(!array_key_exists($bank, $bankInfo)) {
            $this->utils->error_log("========================OneWallet withdrawal bank whose bankTypeId=[$bank] is not supported by onewallet");
            return array('success' => false, 'message' => 'Bank not supported by onewallet');
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $payload = $this->encrypt($params);
        list($content, $response_result) = $this->processCurl($this->getSystemInfo('url'), $payload, $transId, true);

        $data = $this->decrypt($content);
        $this->CI->utils->debug_log("============================OneWallet submitWithdrawRequest decrypted", $data);

        $decodedResult = $this->decodeResult($data);
        $this->CI->utils->debug_log('=========================OneWallet submitWithdrawRequest decoded Result', $decodedResult);
        $decodedResult['response_result'] = $response_result;

        return $decodedResult;
    }

    public function decodeResult($data) {
        if($data['success']) {
            $result = $data['decrypted'];

            if($result['error_code'] == self::RETURN_CODE_SUCESS) {
                $message = "Onewallet request successful.";
                return array('success' => true, 'message' => $message);
            } else {
                $message = "Onewallet withdrawal response: [".$result['error_code']."] ".$result['data']['message'];
                return array('success' => false, 'message' => $message);
            }
        } else {
            $message = '['.$data['error']['code'].']'.$data['error']['message'];
            return array('success' => false, 'message' => $message);
        }
    }

    public function callbackFromServer($transId, $params) {
        $result = array('success' => false, 'message' => $this->encrypt(['result' => 'success']));
        $response_result_id = parent::callbackFromServer($transId, $params);

        if(empty($params)){
            $params = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("============================OneWallet raw_post_data", $params);
        }

        $data = $this->decrypt($params);
        if($data['success']) {
            $params = $data['decrypted'];
            $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

            if (!$this->checkCallbackOrder($order, $params)) {
                return $result;
            }

            if($params['order_status'] == self::ORDER_STATUS_SUCCESS) {
                $msg = sprintf('OneWallet withdrawal was successful: trade ID [%s]',$params['sh_order_no']);
                $this->utils->debug_log('==========================', $msg);
                $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);
                $result['success'] = true;
            }elseif($params['order_status'] == self::ORDER_STATUS_FAILED){
                $msg = sprintf('OneWallet withdrawal payment was failed: trade ID [%s]',$params['sh_order_no']);
                $this->utils->debug_log('==========================', $msg);
                $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
                $result['success'] = true;
            }elseif($params['order_status'] == self::ORDER_STATUS_MANUAL){
                $msg = sprintf('This transaction is necessary to be in contact with OneWallet: trade ID [%s]',$params['sh_order_no']);
                $this->utils->debug_log('==========================', $msg);
                $result['success'] = true;
            }else{
                $msg = sprintf('OneWallet withdrawal payment was not successful trade ID [%s]',$params['sh_order_no']);
                $this->utils->debug_log('==========================', $msg);
            }
        } else {
            $result['message'] = $this->encrypt(['result' => 'failed']);
        }

        return $result;
    }


    public function checkCallbackOrder($order, $fields) {
        $requiredFields = array('order_amount', 'paid_amount', 'sh_order_no', 'order_status');

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=======================OneWallet withdrawal checkCallbackOrder missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['paid_amount'] != $this->convertAmountToCurrency($order['amount'])) {
            $this->writePaymentErrorLog('=========================OneWallet withdrawal checkCallbackOrder payment amount is wrong, expected =>'. $order['amount'], $fields);
            return false;
        }

        if ($fields['sh_order_no'] != $order['transactionCode']) {
            $this->writePaymentErrorLog('=========================OneWallet withdrawal checkCallbackOrder order IDs do not match, expected =>'. $order['transactionCode'], $fields);
            return false;
        }

        # everything checked ok
        return true;
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
            $this->utils->debug_log("==================getting onewallet bank info from extra_info: ", $bankInfo);
        } else  {
            $bankInfo = array(
                '1' => array('name' => 'Bangkok Bank', 'code' => 'BBL'),
                '2' => array('name' => 'Kasikorn Bank', 'code' => 'KB'),
                '3' => array('name' => 'Siam Commercial Bank', 'code' => 'SCB'),
                '4' => array('name' => 'Krung Thai Bank', 'code' => 'KTB'),
                '5' => array('name' => 'Bank of Ayudhya', 'code' => 'BAY'),
            );
            $this->utils->debug_log("=======================getting onewallet bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }
}