<?php
require_once dirname(__FILE__) . '/abstract_payment_api_skydive.php';

/**
 * skydive payment API 5783
 */
class Payment_api_richpay extends Abstract_payment_api_skydive{
    const CALLBACK_SUCCESS_CODE = '200.41';
    const CALLBACK_SUCCESS_TYPE = 'complete';


    public function __construct($params = NULL){
        parent::__construct($params);

        # Populate $info with the following keys
        # url, key, account, secret, system_info
        $this->info = $this->getInfoByEnv();
    }

    # -- implementation of abstract functions --
    public function getPlatformCode(){
        return RICHPAY_PAYMENT_API;
    }

    public function getPrefix(){
        return 'richpay';
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = NULL, $enabledSecondUrl = TRUE, $bankId = NULL){
    }

    public function getOrderIdFromParameters(&$params) {
        if(empty($params) || is_null($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $params = json_decode($raw_post_data, true);
        }

        if(isset($params['check']) && $params['check']){
            $this->CI->utils->debug_log('====================richpay_check_ma_url', $params);
            return;
        }

        $this->CI->load->model(array('payment_account', 'sale_orders_notes', 'sale_orders_timelog'));

        $result = $this->validateCallbackParams($params);
        if(is_array($result) && !empty($result)){
            $defaultCurrency = $this->CI->config->item('default_currency');
            $directPayExtraInfo['bankTypeId']     = $result['bankTypeId'];
            $directPayExtraInfo['deposit_from']   = $result['payment_account_id'];
            $directPayExtraInfo['minDeposit']     = $this->getSystemInfo('amount_limit_min');
            $directPayExtraInfo['maxDeposit']     = $this->getSystemInfo('amount_limit_max');
            $directPayExtraInfo['deposit_amount'] = $this->convertAmountToCurrency($params['data']['amount']);
            //load payment account by system id
            //create sale order
            $orderId = $this->CI->sale_order->createSaleOrder( $this->getPlatformCode() // #1
                , $result['playerId'] // #2
                , $this->convertAmountToCurrency($params['data']['amount']) // #3
                , Sale_order::PAYMENT_KIND_DEPOSIT // #4
                , Sale_order::STATUS_PROCESSING // #5
                , null // #6
                , null // #7
                , $defaultCurrency // #8
                , $result['payment_account_id'] // #9
                , null // #10
                , json_encode($directPayExtraInfo) // #11
                , null // #12
                , null // #13
                , false // #14
                , null // #15
                , null // #16
                , null // #17
            );
            #add notes to action log
            if(isset($orderId)){
                $this->CI->sale_orders_notes->add('create ' . $this->convertAmountToCurrency($params['data']['amount']) . ' from ' . $this->getPlatformCode(), Users::SUPER_ADMIN_ID, Sale_orders_notes::ACTION_LOG, $orderId);
                $this->CI->sale_orders_timelog->add($orderId, Sale_orders_timelog::PLAYER_USER, $result['playerId'], array('before_status' => Sale_order::STATUS_PROCESSING, 'after_status' => null));
                return $orderId;
            }
        }else{
            $this->CI->utils->debug_log('====================richpay validateCallbackParams failed', $params);
            return;
        }
    }

    # $source can be 'server' or 'browser'
    public function callbackFrom($source, $orderId, $params, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;
        $success = true;
        $resultData = [];
        $this->CI->utils->debug_log("=====================richpay callbackFrom $source params", $params);
        if(empty($params)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $this->CI->utils->debug_log("=====================richpay raw_post_data", $raw_post_data);
            $params = json_decode($raw_post_data,true);
            $this->CI->utils->debug_log("=====================richpay json_decode params", $params);
        }
        if(!$order) {
            if(isset($params['check']) && $params['check']){
                if($this->getSystemInfo('check_ma_url_status')){
                    $return_msg['is_ready'] = true;
                }else{
                    $return_msg['is_ready'] = false;
                }
                $result['success'] = true;
                $result['message'] = json_encode($return_msg);
                return $result;
            }else{
                $result['success'] = true;
                $result['message'] = 'callback success';
                $this->CI->utils->writePaymentErrorLog("=====================richpay callbackOrder order is empty",$params, $result);
                return $result;
            }
        }

        # Update order payment status and balance
        $success=true;
        $auto_approve_sale_order = !empty($this->getSystemInfo('auto_approve_sale_order'))? $this->getSystemInfo('auto_approve_sale_order') : false;
        # Update player balance based on order status
        # if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $params);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                if($this->getSystemInfo('auto_approve_sale_order')){
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }else{
                    $this->CI->utils->debug_log('=======================richpay callbackFromServer set in pending because not set auto_approve_sale_order in extra info');
                }
            }
        }

        if($source == 'server' && $success){
            $result['success'] = true;
            $result['message'] = 'callback success';
        }
        return $result;
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->CI->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
    }

    protected function processPaymentUrlForm($params) {
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

    public function validateCallbackParams($params) {

        if(!$this->checkApiKey($params)){
            $this->CI->utils->debug_log('====================richpay checkApiKey check failed', $params);
            return false;
        }

        if(!$this->validateCallbackStatus($params)){
            $this->CI->utils->debug_log('====================richpay validateCallbackStatus check failed', $params);
            return false;
        }

        if(!$this->checkAmountLimitByExtraInfo($params)){
            $this->CI->utils->debug_log('====================richpay checkAmountLimitByExtraInfo check failed', $params);
            return false;
        }

        $accountOfcheckPass = $this->checkPlayerBankAcc($params);
        if($accountOfcheckPass == false){
            $this->CI->utils->debug_log('====================richpay checkPlayerBankAcc check failed', $params);
            return false;
        }else{
            return $accountOfcheckPass;
        }
    }

    public function checkApiKey($params){
        $headers = $this->CI->input->request_headers();
        $apiKey = isset($headers['Apikey']) ? $headers['Apikey'] : FALSE;
        $this->CI->utils->debug_log('====================richpay header', $headers);
        if(!$apiKey){
            $params['status']['type'] = 'Api key dosent exist';
            $unusualNotificationRequests = $this->CI->sale_order->createUnusualNotificationRequests($params);
            $this->CI->utils->debug_log('====================richpay api key dosent exist', $headers);
        }else{
            if($apiKey != $this->getSystemInfo('key')){
                $params['status']['type'] = 'richpay api key not match';
                $unusualNotificationRequests = $this->CI->sale_order->createUnusualNotificationRequests($params);
                $this->CI->utils->debug_log('====================richpay api key not match', $apiKey);
            }else{
                return true;
            }
        }
    }

    public function validateCallbackStatus($params) {
        $validate = false;
        if(isset($params['status']['code']) && isset($params['status']['type'])){
            if($params['status']['code'] == self::CALLBACK_SUCCESS_CODE && $params['status']['type'] == self::CALLBACK_SUCCESS_TYPE){
                $validate = true;
            }else{
                $params['status']['type'] = 'status or code are not success';
                $this->CI->sale_order->createUnusualNotificationRequests($params);
            }
        }else{
            $unusualNotificationRequests = $this->CI->utils->debug_log('====================richpay validateCallbackStatus status or code not exist', $params);
        }

        return $validate;
    }

    public function checkPlayerBankAcc($params){
        $result = false;
        if(isset($params['data']['payer_account']) && !empty($params['data']['payer_account'])){
            $checkPlayerBankDetail = str_replace('x', '%', $params['data']['payer_account']);
            $playerBankDetails = $this->CI->playerbankdetails->getBankDetailsByBankAcc($checkPlayerBankDetail, 'LIKE', 'deposit');
            if (count($playerBankDetails) <= 0) {
                $this->CI->utils->debug_log('====================richpay Cannot find username when getOrderIdFromParameters', $params, $playerBankDetails);
                $params['status']['type'] = 'Cannot find payer account';
                $unusualNotificationRequests = $this->CI->sale_order->createUnusualNotificationRequests($params);
            } elseif (count($playerBankDetails) > 1) {
                $params['status']['type'] = 'More than one matched payer account';
                $unusualNotificationRequests = $this->CI->sale_order->createUnusualNotificationRequests($params);
                $this->CI->utils->debug_log('====================richpay more than one user matched this bank_no when getOrderIdFromParameters', $params, $playerBankDetails);
            }else{
                foreach ($playerBankDetails as $rows => $row) {
                    $playerId = $row['playerId'];
                    if(!empty($playerId)){
                        $this->CI->load->model(array('payment_account'));
                        $payment_account_id = $this->CI->payment_account->getPaymentAccountIdBySystemId($this->getPlatformCode());
                        $banktype = $this->CI->banktype->getBanktypeBySystemId($this->getPlatformCode());
                        if (!empty($payment_account_id)) {
                            if (!$this->CI->payment_account->checkPaymentAccountActive($payment_account_id)) {
                                $params['status']['type'] = 'Payment account is inactive';
                                $unusualNotificationRequests = $this->CI->sale_order->createUnusualNotificationRequests($params);
                                $this->CI->utils->debug_log('====================richpay payment account is inactive', $payment_account_id);
                            }else if(empty($banktype)){
                                $message = sprintf(lang('gen.error.not_exist'), lang('pay.depbanktype'));
                                $params['status']['type'] = 'Banktype is not exist';
                                $unusualNotificationRequests = $this->CI->sale_order->createUnusualNotificationRequests($params);
                                $this->CI->utils->debug_log('====================richpay payment account is not exist');
                            }else{
                                $result = array('payment_account_id' => $payment_account_id,
                                                'bankTypeId' => $banktype->bankTypeId,
                                                'playerId' => $playerId);
                            }
                        }else{
                            $this->CI->utils->debug_log('====================richpay payment_account_id is not exist');
                        }
                    }else{
                        $this->CI->utils->debug_log('====================richpay playerId is not exist');
                    }
                }
            }
        }else{
            $params['status']['type'] = 'params does not received payer_account';
            $unusualNotificationRequests = $this->CI->sale_order->createUnusualNotificationRequests($params);
        }
        return $result;
    }

    public function checkAmountLimitByExtraInfo($params){
        $result = false;
        if(isset($params['data']['amount']) && !empty($params['data']['amount'])){
            if($this->convertAmountToCurrency($params['data']['amount']) < $this->convertAmountToCurrency($this->getSystemInfo('amount_limit_min'))) {
                    $params['status']['type'] = 'Less than min amount';
                    $unusualNotificationRequests = $this->CI->sale_order->createUnusualNotificationRequests($params);
            }elseif($this->convertAmountToCurrency($params['data']['amount']) > $this->convertAmountToCurrency($this->getSystemInfo('amount_limit_max'))){
                $params['status']['type'] = 'More than max amount';
                $unusualNotificationRequests = $this->CI->sale_order->createUnusualNotificationRequests($params);
            }else{
                $result = true;
            }
        }else{
            $params['status']['type'] = 'params does not received amount';
            $unusualNotificationRequests = $this->CI->sale_order->createUnusualNotificationRequests($params);
        }

        return $result;
    }
}
