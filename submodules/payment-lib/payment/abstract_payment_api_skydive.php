<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * skydive
 *
 * * SKYDIVE_PAYMENT_API, ID: 5783
 * * SKYDIVE_WITHDRAWAL_PAYMENT_API, ID: 5784
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.skydive3.com/hr/facade/order/merchant/requestOrder
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_skydive extends Abstract_payment_api {
    const ERROR_CODE_1 = "1";
    const ERROR_CODE_2 = "2";
    const ERROR_CODE_3 = "3";
    const ERROR_CODE_4 = "4";
    const ERROR_CODE_5 = "5";
    const ERROR_CODE_6 = "6";
    const ERROR_CODE_7 = "7";
    const ERROR_CODE_8 = "8";
    const ERROR_CODE_9 = "9";
    const ERROR_CODE_10 = "10";
    const ERROR_CODE_11 = "11";
    const ERROR_CODE_12 = "12";
    const ERROR_CODE_13 = "13";
    const ERROR_CODE_RESERVTRANSFERAPI = "14";
    const RETURN_RESERVTRANSFERAPI_SUCCESS = 0;
    const RETURN_RESERVTRANSFERAPI_ERROR = 1;
    const SOURCE_SYSTEM_NAME = 'SEXYCASINO';

    const DEFAULT_BANK = 'SCB';

    public function __construct($params = null) {
        parent::__construct($params);
    }
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function getSecretInfoList() {
        $secretsInfo = array('live_key', 'live_secret', 'sandbox_key', 'sandbox_secret', 'application_id');
        return $secretsInfo;
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {


        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters(&$params) {

        $this->CI->load->model(array('sale_order','wallet_model','player_model','playerbankdetails'));

        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }
        $this->CI->utils->debug_log('====================skydive getOrderIdFromParameters params', $params);

        $transId = null;

        //for fixed return url on browser
        if (isset($params['bank_no'], $params['amount'], $params['sign'])) {
            $systemId = SKYDIVE_PAYMENT_API;

            $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAcc($params['bank_no']);

            $sql = $this->CI->db->last_query();
            $this->CI->utils->debug_log('====================skydive get sale_orders sql getOrderIdFromParameters', $sql);
            $this->CI->utils->debug_log('====================skydive playerBankDetails sql getOrderIdFromParameters', $playerBankDetails);

                $checkOrder = [];
                foreach ($playerBankDetails as $rows => $row) {
                    $playerId = $row['playerId'];

                    if(!empty($playerId)){

                        $order = $this->CI->sale_order->getSaleOrderByPlayerIdAndAmount($playerId, sale_order::STATUS_PROCESSING, $params['amount']);
                        $checkOrder[$playerId] = $order;

                        $this->CI->utils->debug_log('====================skydive playerBankDetails checkOrder getOrderIdFromParameters', $order,$checkOrder);

                    }else{
                        $params['err_msg']['error_message'] = lang('Cannot find username.');
                        $params['err_msg']['error_code'] = self::ERROR_CODE_1;
                        $this->CI->utils->debug_log('====================skydive playerId is empty when getOrderIdFromParameters', $params);
                    }
                }

                $this->CI->utils->debug_log('====================skydive checkOrder end when getOrderIdFromParameters', $checkOrder);

                if (count($checkOrder) <= 0) {
                    $params['err_msg']['error_message'] = lang('Cannot find username.');
                    $params['err_msg']['error_code'] = self::ERROR_CODE_1;
                    $this->CI->utils->debug_log('====================skydive Cannot find username when getOrderIdFromParameters', $params);
                } elseif (count($checkOrder) > 1) {
                    $params['err_msg']['error_message'] = lang('More than one user matched this bank_no');
                    $params['err_msg']['error_code'] = self::ERROR_CODE_2;
                    $params['err_msg']['bank_no'] = $params['bank_no'];
                    $this->CI->utils->debug_log('====================skydive more than one user matched this bank_no when getOrderIdFromParameters', $params);
                } else {
                    if (!empty($checkOrder[$playerId]->id)) {

                        $orderId = $checkOrder[$playerId]->id;

                        list($res, $msg, $error_code) = $this->verifyCallbackFixProcess($params,$orderId,$systemId);
                        if ($res) {
                            $this->CI->utils->debug_log('====================skydive callback verify order is success when getOrderIdFromParameters', $params, $checkOrder[$playerId], $res, $msg, $error_code);
                            return $orderId;
                        } else {
                            $params['err_msg']['error_message'] = $msg;
                            $params['err_msg']['error_code'] = $error_code;
                            $this->CI->utils->debug_log('====================skydive callback verify failed when getOrderIdFromParameters', $params, $checkOrder[$playerId], $res, $msg, $error_code);
                        }

                    } else {
                        $params['err_msg']['error_message'] = lang('No orders found.');
                        $params['err_msg']['error_code'] = self::ERROR_CODE_4;
                        $this->CI->utils->debug_log('====================skydive order is empty when getOrderIdFromParameters', $params);
                    }
                }
            // }

            // $walletAccount = $this->CI->wallet_model->getWalletAccountByTransactionCode($trans_id);

            // if(!empty($walletAccount)){
            //     $transId = $walletAccount['transactionCode'];
            // }else{
            //     $this->CI->utils->debug_log('====================skydive getOrderIdFromParameters transId is empty when getOrderIdFromParameters', $params);
            // }
        }
        else {
            $params['err_msg']['error_message'] = lang('Missing required parameters.');
            $params['err_msg']['error_code'] = self::ERROR_CODE_3;
            $this->CI->utils->debug_log('====================skydive Missing required parameters getOrderIdFromParameters', $params);
        }
        return $transId;
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    ## This will be called when user redirects back to our page from payment API
    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    # $source can be 'server' or 'browser'
    public function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;
        $success = true;
        $resultData = [];
        $playerId = $order->player_id;

        $this->CI->utils->debug_log("=====================skydive callbackFrom $source params", $params);

        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================skydive raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================skydive json_decode params", $params);
        }

        if (!$order) {
            $params['err_msg']['result'] = false;
            $result['return_error_json'] = $params['err_msg'];
            $success = false;
            $this->CI->utils->writePaymentErrorLog("=====================skydive callbackOrder order is empty",$params, $result);
            return $result;
        }

        if($source == 'server' && isset($params['bank_no'])){

            $resultData = $this->verifyFixedProcess($params,$order,$processed);
            $this->CI->utils->debug_log("=====================skydive callbackFrom resultData", $resultData);

            if (!$resultData['result']) {
                $success = false;
            }

        }else if($source == 'server' && isset($params['username'], $params['amount'])){

            $resultData = $this->verifyProcess($params,$order,$processed);
            $this->CI->utils->debug_log("=====================skydive callbackFrom resultData", $resultData);

            if ($resultData['result']) {
                $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
                if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
                    $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
                    if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                        $this->CI->sale_order->setStatusToSettled($orderId);
                    }
                } else {
                    # update player balance
                    $this->CI->sale_order->updateExternalInfo($order->id, $resultData['transaction_no'], '', null, null, $response_result_id);
                    if ($source == 'browser') {
                        $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
                    } elseif ($source == 'server') {
                        $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                        $balance = $this->CI->player_model->getPlayersTotalBallanceIncludeSubwallet($playerId);
                        $resultData['balance'] = $balance;
                    }
                }
            }else{
                $success = false;
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['json_result'] = $resultData;
        } else {
            $result['return_error_json'] = $resultData;
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    //-- signatures --
    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $this->CI->utils->debug_log('====================skydive sign signStr', $signStr);
        $sign = strtoupper(md5($signStr));
        $this->CI->utils->debug_log('====================skydive sign result', $sign);
        return $sign;
    }

    private function createSignStr($params) {
        $signStr = '';
        ksort($params);
        foreach($params as $key => $value) {
            if( $key == 'sign' || $key == '' || $key == null) {
                continue;
            }
            $signStr .= "$key=$value&";
        }
        $signStr .= "APIKEY=".$this->getSystemInfo('key');
        return $signStr;
    }

    private function validateSign($params) {
        $sign = $this->sign($params);
        if($params['sign'] == $sign)
            return true;
        else
            return false;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    private function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 0, '.', '');
    }

    public function verifyFixedProcess($params, $order, &$processed = false) {
        $this->CI->load->model(array('sale_order','wallet_model','player_model','playerbankdetails'));
        $this->CI->utils->debug_log('====================skydive verifyFixedProcess params', $params);

        $data = [];
        $data['result'] = false;
        $processed = false;
        $playerId = $order->player_id;

        if(!empty($playerId)){

            $requiredFields = array(
                'bank_no','amount','sign','bank_code'
            );

            foreach ($requiredFields as $f) {
                if (!array_key_exists($f, $params)) {
                    $data['error_message'] = lang('Missing required parameters.');
                    $data['error_code'] = self::ERROR_CODE_3;
                    $this->writePaymentErrorLog("=====================skydive verifyFixedProcess Missing parameter: [$f]", $params);
                    return $data;
                }
            }

            #is signature authentic
            if (!$this->validateSign($params)) {
                $data['error_message'] = lang('Invalid signature.');
                $data['error_code'] = self::ERROR_CODE_13;
                $this->writePaymentErrorLog('=====================skydive verifyFixedProcess Signature Error', $params);
                return $data;
            }

            $username = $this->CI->player_model->getUsernameById($playerId);
            $processed = true;
            $data['result'] = true;
            $data['username'] = $username;
            $data['callbackUrl'] = $this->getNotifyUrl($order->id);
            $data['amount'] = $order->amount;

            $this->CI->utils->debug_log('====================skydive verifyFixedProcess playerId is success', $data);
        }else{
            $data['error_message'] = lang('Cannot find username');
            $data['error_code'] = self::ERROR_CODE_1;
            $this->writePaymentErrorLog("=====================skydive verifyFixedProcess playerId is empty", $data);
        }

        return $data;
    }

    public function verifyProcess($params, $order, &$processed = false) {
        $this->CI->load->model(array('sale_order','wallet_model','player_model','playerbankdetails'));
        $this->CI->utils->debug_log('====================skydive verifyProcess params', $params);

        $data = [];
        $data['result'] = false;
        $processed = false;
        $playerId = $order->player_id;

        if(!empty($playerId)){


            $requiredFields = array(
                'username','amount','sign'
            );

            foreach ($requiredFields as $f) {
                if (!array_key_exists($f, $params)) {
                    $data['error_message'] = lang('Missing required parameters');
                    $data['error_code'] = self::ERROR_CODE_3;
                    $this->writePaymentErrorLog("=====================skydive verifyProcess Missing parameter: [$f]", $params);
                    return $data;
                }
            }

            $username = $this->CI->player_model->getUsernameById($playerId);
            $balance = $this->CI->player_model->getPlayersTotalBallanceIncludeSubwallet($playerId);

            #is signature authentic
            if (!$this->validateSign($params)) {
                $data['error_message'] = lang('Invalid signature.');
                $data['error_code'] = self::ERROR_CODE_12;
                $this->writePaymentErrorLog('=====================skydive verifyProcess Signature Error', $params);
                return $data;
            }

            if ($params['username'] != $username) {
                $data['error_message'] = lang('Cannot find any match order by the username and amount.');
                $data['error_code'] = self::ERROR_CODE_6;
                $this->writePaymentErrorLog("======================skydive verifyProcess username do not match, expected [$username]", $params);
                return $data;
            }

            if ($params['amount'] != $order->amount) {
                $data['error_message'] = lang('Cannot find any match order by the username and amount.');
                $data['error_code'] = self::ERROR_CODE_6;
                $this->writePaymentErrorLog("=====================skydive verifyProcess Payment amount is wrong, expected [$order->amount]", $params);
                return $data;
            }

            $processed = true;
            $data['result'] = true;
            $data['transaction_no'] = $order->secure_id;
            $data['username'] = $username;
            $data['balance'] = $balance;
            $this->CI->utils->debug_log('====================skydive verifyProcess playerId is success', $data);
        }else{
            $data['error_message'] = lang('Cannot find username.');
            $data['error_code'] = self::ERROR_CODE_1;
            $this->writePaymentErrorLog("=====================skydive verifyProcess playerId is empty", $data);

        }

        return $data;
    }

    public function verifyCallbackFixProcess($flds,$orderId,$systemId){
        #if resend, skip ip check; if not validate ip, decline it
        $this->CI->load->model(['sale_orders_status_history', 'external_system', 'sale_order', 'operatorglobalsettings']);

        $orderMsg = 'system id: ' . $systemId . ', order id: ' . $orderId;
        $res = true;
        $msg = '';
        $error_code = '';
        if(!empty($flds['reSendBySecureId'])){
            $isResendCallback = $this->CI->response_result->checkResendCallbackExists($systemId ,$orderId ,$flds['reSendBySecureId']);
            if(!$isResendCallback){
                $msg = 'Wrong resend callback, resend callback does not exist.';
                $this->CI->utils->debug_log($msg.' '.$orderMsg);
                $this->callbackNeedToBeNotify($orderId, $flds, 'server', $msg);
                return array(false, $msg, self::ERROR_CODE_6);
            }
        }else{
            $ip = $this->getClientIP();
            if(!$this->validateWhiteIp($ip)){
                $msg = 'Wrong callback, callback ip : ['.$ip.'] not in white_ip_list.';
                $this->CI->utils->debug_log($msg.' '.$orderMsg, implode(',', $this->white_ip_list));
                $this->callbackNeedToBeNotify($orderId, $flds, 'server', $msg);
                return array(false, $msg, self::ERROR_CODE_7);
            }
        }

        #if callback time over valid hour, decline it
        $isOrderExpired = $this->isOrderExpired($orderId);
        if($isOrderExpired){
            if( empty($flds['reSendBySecureId']) ){ // resend is exception
                $validHour = $this->getValidHour();
                $msg = 'Wrong callback, callback time over valid hour. Valid hour: '.$validHour.' hours.';
                $this->CI->utils->error_log($msg.' '.$orderMsg);
                $this->callbackFailed($orderId, $flds, 'server', $msg);
                return array(false, $msg, self::ERROR_CODE_8);
            }
        }

        #if payment api is disabled, decline it
        $isApiDisabled = $this->CI->external_system->isApiDisabled($systemId);
        if($isApiDisabled){
            $msg = 'Wrong callback, api is disabled.';
            $this->CI->utils->error_log($msg.' '.$orderMsg);
            $this->callbackFailed($orderId, $flds, 'server', $msg);
            return array(false, $msg, self::ERROR_CODE_9);
        }

        #only check payment_account when deposit
        if(substr($orderId, 0, 1) != 'W'){
            #if payment account is disabled, decline it
            $isPaymentAccountDisabled = $this->CI->sale_order->isDisabledPaymentAccountByOrderId($orderId);
            if($isPaymentAccountDisabled){
                $msg = 'Wrong callback, payment account is disabled.';
                $this->CI->utils->error_log($msg.' '.$orderMsg);
                $this->callbackFailed($orderId, $flds, 'server', $msg);
                return array(false, $msg, self::ERROR_CODE_10);
            }

            #if is not default collection account, decline it
            $saleOrder = $this->CI->sale_order->getSaleOrderById($orderId);
            $isDefaultCollectionAccount = $this->CI->operatorglobalsettings->isDefaultCollectionAccount($saleOrder->payment_account_id);
            if(!$isDefaultCollectionAccount){
                $msg = 'Wrong callback, payment account is not default collection account.';
                $this->CI->utils->error_log($msg.' '.$orderMsg);
                $this->callbackFailed($orderId, $flds, 'server', $msg);
                return array(false, $msg, self::ERROR_CODE_11);
            }
        }
        return array($res, $msg, $error_code);
    }

    public function callReservTransferApi($username, $order){

        $params = array();
        $params['username'] = $username;
        $params['amount'] = $order->amount;
        $params['callback_url'] = $this->getOrderServerCallbackUrl($order->id);
        $params['source_system_name'] = $this->getSystemInfo('source_system_name',self::SOURCE_SYSTEM_NAME);
        $url = $this->getSystemInfo('reservTransferUrl','https://services.missilegroup.com/autodeposit/reserv_transfer');


        $this->_custom_curl_header = ["Content-Type: application/json"];
        $response = $this->submitPostForm($url, $params, true, $order->secure_id);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================skydive callReservTransferApi response', $response);

        $data = [];
        $data['result'] = false;

        if(!empty($response['result']) && $response['error_code'] == self::RETURN_RESERVTRANSFERAPI_SUCCESS) {

            $res_username = $response['reserv_transfer_transaction']['username'];

            if ($res_username != null && !empty($res_username)) {
                $notes = $order->notes . " | callback fix process diff amount, origin was: " . $order->amount;
                $this->CI->sale_order->fixOrderAmount($order->id, str_replace(',', '', $response['reserv_transfer_transaction']['reserv_amount']), $notes);

                $data['result'] = true;
                $data['username'] = $res_username;
                $data['callbackUrl'] = $this->getNotifyUrl($order->id);
                $data['amount'] = $response['reserv_transfer_transaction']['reserv_amount'];

                return $data;
            } else if(isset($response['error']) && $response['error_code'] == self::RETURN_RESERVTRANSFERAPI_ERROR){
                $data['error_message'] = $response['error'];
                $data['error_code'] = self::ERROR_CODE_RESERVTRANSFERAPI;
                $this->writePaymentErrorLog("=====================skydive callReservTransferApi Reserv Transfer Api username is empty", $data);
                return $data;
            }else {
                $data['error_message'] = lang('Reserv Transfer Api username is empty');
                $data['error_code'] = self::ERROR_CODE_RESERVTRANSFERAPI;
                $this->writePaymentErrorLog("=====================skydive callReservTransferApi Reserv Transfer Api username is empty", $data);
                return $data;
            }
        }
        else if(isset($response['error'])) {
            $data['error_message'] = $response['error_code'].': '.$response['error'];
            $data['error_code'] = self::ERROR_CODE_RESERVTRANSFERAPI;
            $this->writePaymentErrorLog("=====================skydive callReservTransferApi Reserv Transfer Api error", $data);
            return $data;
        }
        else {
            $data['error_message'] = lang('Invalidte callReservTransferApi response');
            $data['error_code'] = self::ERROR_CODE_RESERVTRANSFERAPI;
            $this->writePaymentErrorLog("=====================skydive callReservTransferApi Invalidte callReservTransferApi response", $data);
            return $data;
        }
    }
}