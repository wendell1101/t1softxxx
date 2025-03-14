<?php
require_once dirname(__FILE__) . '/abstract_payment_api_daddypay.php';

/**
 *
 * DaddyPay Withdrawal 出款
 *
 * DADDYPAY_WITHDRAWAL_PAYMENT_API, ID: 126
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_daddypay_withdrawal extends Abstract_payment_api_daddypay {

	public function getPlatformCode() {
		return DADDYPAY_WITHDRAWAL_PAYMENT_API;
	}

	public function getPrefix() {
		return 'daddypay_withdrawal';
	}

	public function generatePaymentUrlFormgeneratePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = NULL, $enabledSecondUrl = true, $bankId = NULL) {}
	protected function configParams(&$params, $direct_pay_extra_info) {}


    /**
     * detail: Initiates a withdraw request
     *
     * note: Note that the $bank is the bank_type ID, we should map it to the bank_code and bank_name required by this API
     *
     * @param int $bank
     * @param string $accNum
     * @param string $name
     * @param float $amount
     * @param int $transId
     * @return json
     */
    public function getWithdrawParams($bank, $accNum, $name, $amount, $transId) {
        $bankInfo = $this->getBankInfo();

        $params = [];
        $params['company_id'] = $this->getSystemInfo("account");
        $params['bank_id'] = $bankInfo[$bank]['code'];
        $params['company_order_num'] = $transId;
        $params['amount'] = $this->convertAmountToCurrency($amount);
        $params['card_num'] = $accNum;
        $params['card_name'] = $name;
        $params['company_user'] = $transId;
        $params['issue_bank_name'] = '';
        $params['issue_bank_address'] = '';
        $params['web_url'] = $this->getNotifyUrl($transId);
        $params['key'] = $this->signWithdrawal($params);

        $this->CI->utils->debug_log('========================daddypay getWithdrawParams params: ', $params);

        return $params;
    }

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        if(!array_key_exists($bank, $this->getBankInfo())) {
            $this->utils->error_log("========================daddypay withdraw bank whose bankTypeId=[$bank] is not supported by daddypay");
            $result['message'] = lang("Bank not supported by daddypay");
            return $result;
        }

        $params = $this->getWithdrawParams($bank, $accNum, $name, $amount, $transId);
        $url = $this->getWithdrawUrl();

        list($response, $response_result) = $this->submitPostForm($url, $params, false, $transId, true);

        $decodedResult = $this->decodeResult($response);
        $decodedResult['response_result'] = $response_result;
        $this->CI->utils->debug_log('=======================daddypay submitWithdrawRequest decoded Result', $decodedResult);

        return $decodedResult;
    }

    public function decodeResult($resultString) {
        if(is_array($resultString) && isset($resultString['lock']) && isset($resultString['msg'])) {
            $result = array('success' => false, 'message' => $resultString['msg'], 'lock' => true);
            return $result;
        }

        $result = array('success' => false, 'message' => 'Daddypay decoded fail.');
        $response = json_decode($resultString, true);
        $this->CI->utils->debug_log('=======================daddypay submitWithdrawRequest decodeResult json decoded', $response);

        $result['success'] = $response['status']==self::WITHDRAWAL_RESULT_CODE_SUCCESS;

        if($result['success']) {
            $result['message'] = "Daddypay withdrawal response success! Transaction fee: " . $response['transaction_charge'];
        } elseif(isset($response['error_msg'])) {
            $result['message'] = 'Daddypay withdrawal failed. Error Message: ' . $response['error_msg'];
        }

        return $result;
    }

    //MD5(MD5(config)+company_id+bank_id+company_order_num+amount+card_num+card_name+company_user+issue_bank_name+issue_bank_address+memo)
    public function signWithdrawal($params){
        $key = $this->getSystemInfo('key');
        $signStr = md5($key);
        $dataKeys = array('company_id', 'bank_id', 'company_order_num', 'amount', 'card_num', 'card_name', 'company_user', 'issue_bank_name', 'issue_bank_address', 'memo');
        foreach($dataKeys as $dataKey) {
            if(array_key_exists($dataKey, $params)) {
                $signStr .= $params[$dataKey];
            }
        }
        $md5=md5($signStr);

        return $md5;
    }

    //MD5(MD5(config)+company_order_num+mownecum_order_num+amount+card_num+card_name+company_user)
    public function signWithdrawalApprove($params){
        $key = $this->getSystemInfo('key');
        $signStr = md5($key);
        $dataKeys = array('company_order_num', 'mownecum_order_num', 'amount', 'card_num', 'card_name', 'company_user');
        foreach($dataKeys as $dataKey) {
            if(array_key_exists($dataKey, $params)) {
                $signStr .= $params[$dataKey];
            }
        }
        $md5=md5($signStr);

        return $md5;
    }

    //MD5(MD5(config)+mownecum_order_num+company_order_num+status+amount+exact_transaction_charge)
    public function signWithdrawalResult($params){
        $key = $this->getSystemInfo('key');
        $signStr = md5($key);
        $dataKeys = array('mownecum_order_num', 'company_order_num', 'status', 'amount', 'exact_transaction_charge');
        foreach($dataKeys as $dataKey) {
            if(array_key_exists($dataKey, $params)) {
                $signStr .= $params[$dataKey];
            }
        }
        $md5=md5($signStr);

        return $md5;
    }

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
            $this->utils->debug_log("=========================daddypay bank info from extra_info: ", $bankInfo);
        } else {
            $bankInfo = array(
                '1' => array('name' => '中国工商银行', 'code' => '1'),
                '2' => array('name' => '招商银行', 'code' => '2'),
                '3' => array('name' => '中国建设银行', 'code' => '3'),
                '4' => array('name' => '中国农业银行', 'code' => '4'),
                '5' => array('name' => '交通银行', 'code' => '6'),
                '6' => array('name' => '中国银行', 'code' => '5'),
                '8' => array('name' => '广东发展银行', 'code' => '13'),
                '10' => array('name' => '中信银行', 'code' => '8'),
                '11' => array('name' => '民生银行', 'code' => '7'),
                '12' => array('name' => '中国邮政储蓄', 'code' => '10'),
                '13' => array('name' => '兴业银行', 'code' => '15'),
                '14' => array('name' => '华夏银行', 'code' => '14'),
                '15' => array('name' => '平安银行', 'code' => '12'),
                '20' => array('name' => '光大银行', 'code' => '11')
            );
            $this->utils->debug_log("=======================getting daddypay bank info from code: ", $bankInfo);
        }
        return $bankInfo;
    }

    public function getWithdrawUrl() {
        return $this->getSystemInfo('url');
    }

    ## After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }
}
