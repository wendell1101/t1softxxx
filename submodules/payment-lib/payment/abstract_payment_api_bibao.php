<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * BIBAO
 *
 * * BIBAO_DC_PAYMENT_API, ID: 5176
 * *
 * Required Fields:
 * * URL
 * * wallet_id
 * * token
 * * address
 *
 * Field Values:
 * * URL: http://localhost:3080/api/v2/

 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_bibao extends Abstract_payment_api {
    const COIN_DC = "DC";
    const CALLBACK_TYPE_MER_CHARGE    = "1";#商户充币
    const CALLBACK_TYPE_USER_CHARGE   = "4";#用户充币
    const CALLBACK_TYPE_USER_COIN     = "5";#用户提币
    const CALLBACK_TYPE_USER_TRANSFER = "6";#用户转出
    const CALLBACK_SUCCESS            = "4";#状态:成功
    const STATUS_FAILED               = "removed";
    const RETURN_SUCCESS_CODE         = 'success';

    public $coincode;
    public $deskey;
    public $keyA;
    public $keyB;
    public $keyC;
    public $mercode;

    public function __construct($params = null) {
        parent::__construct($params);
        $this->coincode = $this->getCoincode();
        $this->deskey   = $this->getSystemInfo('key');
        $this->keyA     = $this->getSystemInfo('keyA');
        $this->keyB     = $this->getSystemInfo('keyB');
        $this->keyC     = $this->getSystemInfo('keyC');
        $this->mercode  = $this->getSystemInfo('account');
    }

    # Implement these to specify pay type
    protected abstract function getCoincode();
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {}
    protected function handlePaymentFormResponse($handle) {}
    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        $this->utils->debug_log('=========================bibao getOrderIdFromParameters flds', $flds);
        if(empty($flds) || is_null($flds)){
            $raw_post_data = file_get_contents('php://input', 'r');
            $flds = json_decode($raw_post_data, true);
        }
        $flds = json_decode($raw_post_data, true);
        $this->CI->utils->debug_log('=====================bibao getOrderIdFromParameters raw_post_data', $raw_post_data);
        $this->CI->utils->debug_log('=====================bibao getOrderIdFromParameters json_decode flds', $flds);

        if(isset($flds['OrderNo'])) {

            $txid = $flds['OrderNo'];
            $this->utils->debug_log('=====================bibao getOrderIdFromParameters get transfer id', $txid);

            #deposit
            if($substr($txid, 0, 1) == 'D'){

                $this->utils->debug_log('=====================bibao getOrderIdFromParameters deposit OrderNo', $flds);
                $order = $this->CI->sale_order->getSaleOrderBySecureId($txid);
                if(is_null($order)){
                    $this->utils->debug_log('=====================bibao getOrderIdFromParameters cannot find order by address', $txid);
                    return;
                }
                return $order->id;
            }
            else if(substr($txid, 0, 1) == 'W'){
                $order = $this->CI->wallet_model->getWalletAccountByTransactionCode($txid);
                if(is_null($order)){
                    $this->utils->debug_log('=====================bibao getOrderIdFromParameters cannot find order by txid', $txid);
                    return;
                }
                return $order['transactionCode'];
            }
        }
        else {
            $this->utils->debug_log('=====================bibao getOrderIdFromParameters cannot get any transfer from webhook params', $flds);
            return;
        }
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));

        #check deposit or withdrawal
        if (!empty($orderId)) {
            if(substr($orderId, 0, 1) == 'W') {
                $order     = $this->CI->wallet_model->getWalletAccountByTransactionCode($orderId);
                $type      = self::CALLBACK_TYPE_USER_COIN;
                $secure_id = $order['transactionCode'];
            }
            else{
                $order     = $this->CI->sale_order->getSaleOrderById($orderId);
                $type      = self::CALLBACK_TYPE_MER_CHARGE;
                $secure_id = $order->secure_id;
            }
        }

        $processed = false;

        $raw_post_data = file_get_contents('php://input', 'r');
        $flds = json_decode($raw_post_data, true);
        $this->CI->utils->debug_log('=====================callbackFromServer raw_post_data', $raw_post_data);
        $this->CI->utils->debug_log('=====================callbackFromServer json_decode flds', $flds);


        if(isset($flds['OrderNo'])) {
            $txid      = $flds['OrderNo'];
            $this->utils->debug_log('=====================callbackFromServer get transfer id', $txid);

            #check more params when receive
            if($type == self::CALLBACK_TYPE_USER_CHARGE){
                if (!$order || !$this->checkCallbackOrder($order, $flds, $processed)) {
                    return $result;
                }
            }
            else if($type == self::CALLBACK_TYPE_USER_TRANSFER){
                if (!$order || !$this->checkCallbackTransaction($order, $flds)) {
                    return $result;
                }
            }else if($type == self::CALLBACK_TYPE_MER_CHARGE){
                if (!$order || !$this->checkCallbackMerCharge($order, $flds)) {
                    return $result;
                }
            }
        }


        if($type == self::CALLBACK_TYPE_MER_CHARGE){
            # Update player balance based on order status
            # if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
            if ($order->status == Sale_order::STATUS_BROWSER_CALLBACK || $order->status == Sale_order::STATUS_SETTLED) {
                $this->CI->utils->debug_log('callbackFromServer already get callback for order:' . $order->id, $response);
            } else {
                $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
            }

            $result['success'] = true;
            if ($processed) {
                $result['message'] = self::RETURN_SUCCESS_CODE;
            } else {
                $result['return_error'] = 'Error';
            }
        }
        else if($type == self::CALLBACK_TYPE_USER_COIN){
            if ($flds['Success']) {
                $msg = sprintf('bibao withdrawal success: bibao transfer ID [%s]', $flds['OrderNo']);
                $this->CI->wallet_model->withdrawalAPIReturnSuccess($orderId, $msg);

                $result['message'] = self::RETURN_SUCCESS_CODE;
                $result['success'] = true;
            }else {
                $msg = sprintf('bibao withdrawal payment was not successful: [%s]', $flds['Message']);
                $this->writePaymentErrorLog($msg, $fields);
                $result['message'] = $msg;
            }
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'Id', 'OrderNo', 'TradeNo', 'TradeTime', 'Amount', 'Fee', 'State', 'Type', 'Coin', 'FromAddr', 'ToAddr', 'sign'
        );

        foreach($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================bibao checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass


        if ($fields['State'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("=====================bibao checkCallbackOrder Payment status is not confirmed", $fields['State']);
            return false;
        }

        if ($fields['Type'] != self::CALLBACK_TYPE_USER_CHARGE) {
            $this->writePaymentErrorLog("=====================bibao checkCallbackOrder Payment confirmations Type ", $fields['Type']);
            return false;
        }

        if ($this->convertAmountToCurrency($order->amount) != floatval( $fields['Amount'] )) {
            $this->writePaymentErrorLog("=====================bibao checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields['Amount']);
            return false;
        }

        if ($fields['OrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================bibao checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields['OrderNo']);
            return false;
        }

        # everything checked ok
        return true;
    }

    private function checkCallbackTransaction($order, $fields) {
        $requiredFields = array(
            'Id', 'OrderNo', 'TradeNo', 'TradeTime', 'Amount', 'Fee', 'State', 'Type', 'Coin', 'FromAddr', 'ToAddr', 'sign'
        );

        foreach($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================bibao checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['State'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("=====================bibao checkCallbackOrder Payment status is not confirmed", $fields['State']);
            return false;
        }

        if ($fields['Type'] != self::CALLBACK_TYPE_USER_TRANSFER) {
            $this->writePaymentErrorLog("=====================bibao checkCallbackOrder Payment confirmations Type ", $fields['Type']);
            return false;
        }

        if ($this->convertAmountToCurrency($order->amount) != floatval( $fields['Amount'] )) {
            $this->writePaymentErrorLog("=====================bibao checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields['Amount']);
            return false;
        }

        if ($fields['OrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================bibao checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields['OrderNo']);
            return false;
        }

        # everything checked ok
        return true;
    }

    private function checkCallbackMerCharge($order, $fields) {
        $requiredFields = array(
            'Id', 'OrderNo', 'TradeNo', 'TradeTime', 'Amount', 'Fee', 'State', 'Type', 'Coin', 'FromAddr', 'ToAddr', 'sign'
        );

        foreach($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================bibao checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['State'] != self::CALLBACK_SUCCESS) {
            $this->writePaymentErrorLog("=====================bibao checkCallbackOrder Payment status is not confirmed", $fields['State']);
            return false;
        }

        if ($fields['Type'] != self::CALLBACK_TYPE_MER_CHARGE) {
            $this->writePaymentErrorLog("=====================bibao checkCallbackOrder Payment confirmations Type ", $fields['Type']);
            return false;
        }

        if ($this->convertAmountToCurrency($order->amount) != floatval( $fields['Amount'] )) {
            $this->writePaymentErrorLog("=====================bibao checkCallbackOrder Payment amounts do not match, expected [$order->amount]", $fields['Amount']);
            return false;
        }

        if ($fields['OrderNo'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================bibao checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields['OrderNo']);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    public function getMillisecond() {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    public function buildFormData($data) {
        if(!is_array($data))
            return "";

        $result = "";
        foreach ($data as $k=>$v) {
            $result.=sprintf("%s=%s&",$k,$v);
        }
        $result=rtrim($result,"&");
        return $result;
    }

    public function desEncrypt($params,$deskey) {
        $result = openssl_encrypt($params,"DES-CBC",$deskey,OPENSSL_RAW_DATA, $deskey);
        return strtoupper(bin2hex($result));
    }

    public function md5KeyB($params,$keyA,$keyB,$keyC,$GetAddress=true) {
        $today = date("Ymd",time());
        if($GetAddress){
            $md5KeyB = md5($params['MerCode'].$params['UserType'].$params['CoinCode'].$keyB.$today);
        }else{
            $md5KeyB = md5($params['MerCode'].$params['UserName'].$keyB.$today);
        }
        $sign = $keyA.$md5KeyB.$keyC;
        return $sign;
    }
}