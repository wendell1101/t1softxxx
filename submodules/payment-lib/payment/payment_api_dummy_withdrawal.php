<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * Dummy payment API
 */
/// Patch for OGP-13520, visit "http://player.og.local/callback/process/9997/W673477851493/?callback_status_case=to_apid&dbg=1607" failed.
// because override parent::callbackFromServer(),
// should call Abstract_payment_api::callbackFromServer(), not Payment_api_dummy::callbackFromServer().
class Payment_api_dummy_withdrawal extends Abstract_payment_api {
    const SUBMIT_CASE_SUCCESS = 'to_processing';
    const SUBMIT_CASE_FAIL = 'stay_pending';

    const CASE_STAY = 'stay_processing';
    const CASE_SUCCESS = 'to_paid';
    const CASE_FAIL = 'to_declined';

    const RETURN_SUCCESS_CODE = 'success';

    /// ignore for withdrawal
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {}
    public function directPay($order = NULL){
        $this->utils->debug_log("Invoked in dummy payment API", $order);
        return $this->returnUnimplemented();
    }

    public function getPlatformCode() {
        return DUMMY_WITHDRAWAL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'dummy_withdrawal';
    }

    # Implement abstract function but do nothing
    protected function configParams(&$params, $direct_pay_extra_info){}
    protected function processPaymentUrlForm($params){}

    public function submitWithdrawRequest($bank, $accNum, $name, $amount, $transId) {
        $result = array('success' => false, 'message' => 'payment failed');

        if(!$this->isAllowWithdraw()) {
            $result['message'] = lang("Withdraw not allowed with this API");
            return $result;
        }

        $params = array();
        $params["bank_id"] = $bank;
        $params["accNum"] = $accNum;
        $params["name"] = $name;
        $params["amount"] = $amount;
        $params["transId"] = $transId;

        $content = array();
        $content['submit'] = $this->getSystemInfo('submit_api_case', self::SUBMIT_CASE_SUCCESS);


        #save response result
        $response_result = [ $params, json_encode($content), 'dummy_url', null, array(), $transId ];
        $response_result_id = $this->submitPreprocess($params, json_encode($content), 'dummy_url', null, array(), $transId);

        $decodedResult = $this->decodeResult($content);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================dummy_withdrawal submitWithdrawRequest result ', $decodedResult);
        return $decodedResult;
    }

    public function decodeResult($resultString, $queryAPI = false) {

        if($queryAPI){
            if($resultString["check"] == self::CASE_SUCCESS){
                $result = array('success' => true, 'message' => 'DUMMY withdrawal success!', 'payment_fail' => false);
            }
            else if($resultString["check"] == self::CASE_FAIL){
                $result = array('success' => false, 'message' => 'DUMMY withdrawal failed.', 'payment_fail' => true);
            }
            else if($resultString["check"] == self::CASE_STAY){
                $result = array('success' => false, 'message' => 'DUMMY withdrawal still processing.');
            }
        }
        else{
            if($resultString["submit"] == self::SUBMIT_CASE_SUCCESS){
                $result = array('success' => true, 'message' => 'DUMMY withdrawal submit success!', 'payment_fail' => false);
            }
            else if($resultString["submit"] == self::SUBMIT_CASE_FAIL){
                $result = array('success' => false, 'message' => 'DUMMY withdrawal submit response failed.');
            }
        }

        return $result;
    }

    public function checkWithdrawStatus($transId) {
        $params = array();
        $params["transId"] = $transId;

        $response = array();
        $response['check']  = $this->getSystemInfo('check_status_case', self::CASE_SUCCESS);

        #save response result
        $response_result = [ $params, json_encode($response), 'dummy_url', null, array(), $transId ];
        $response_result_id = $this->submitPreprocess($params, json_encode($response), 'dummy_url', null, array(), $transId);

        $decodedResult = $this->decodeResult($response, true);
        $decodedResult['response_result'] = $response_result;

        $this->CI->utils->debug_log('======================================dummy_withdrawal checkWithdrawStatus result ', $decodedResult);
        return $decodedResult;
    }

    public function callbackFromServer($transId, $params) {
        $response_result_id = parent::callbackFromServer($transId, $params);
        $result = array('success' => false, 'message' => 'Payment failed');
        $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($transId);

        if(isset($params['callback_status_case'])){
            $statusCode = $params['callback_status_case'];
        } else {
            $statusCode = $this->getSystemInfo('check_status_case', self::CASE_SUCCESS);
        }

        if($statusCode == self::CASE_SUCCESS) {
            $msg = "DUMMY withdrawal callback success!";
            $this->CI->wallet_model->withdrawalAPIReturnSuccess($transId, $msg);

            $result['message'] = self::RETURN_SUCCESS_CODE;
            $result['success'] = true;
        }
        else if($statusCode == self::CASE_FAIL){
            $msg = "DUMMY withdrawal callback failed.";
            $this->CI->wallet_model->withdrawalAPIReturnFailure($transId, $msg);
            $result['message'] = $msg;
        }
        else if($statusCode == self::CASE_STAY){
            $msg = "DUMMY withdrawal callback response [".$statusCode."]";
            $this->writePaymentErrorLog($msg, $fields);
            $result['message'] = $msg;
        }

        return $result;
    }
}