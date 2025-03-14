<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';
/**
 * PAYMERO CRYPTO
 *
 * * PAYMEROCRYPTO_BTC_PAYMENT_API, ID: 5820
 * * PAYMEROCRYPTO_BCH_PAYMENT_API, ID: 5821
 * * PAYMEROCRYPTO_BSV_PAYMENT_API, ID: 5822
 * * PAYMEROCRYPTO_ETH_PAYMENT_API, ID: 5823
 * * PAYMEROCRYPTO_LTC_PAYMENT_API, ID: 5824
 * * PAYMEROCRYPTO_USDT_PAYMENT_API, ID: 5825
 * * PAYMEROCRYPTO_USDT_2_PAYMENT_API, ID: 5877
 * * PAYMEROCRYPTO_BTC_WITHDRAWAL_PAYMENT_API, ID: 5831
 * * PAYMEROCRYPTO_BCH_WITHDRAWAL_PAYMENT_API, ID: 5832
 * * PAYMEROCRYPTO_BSV_WITHDRAWAL_PAYMENT_API, ID: 5833
 * * PAYMEROCRYPTO_ETH_WITHDRAWAL_PAYMENT_API, ID: 5834
 * * PAYMEROCRYPTO_LTC_WITHDRAWAL_PAYMENT_API, ID: 5835
 * * PAYMEROCRYPTO_USDT_WITHDRAWAL_PAYMENT_API, ID: 5836
 * *
 * Required Fields:
 * * URL
 * * wallet_id
 * * token
 * * address
 *
 * Field Values:
 * * URL: https://service-api.paymero.io/v1/crypto/
    # url 
    # address 
    # https://service-api.paymero.io/v1/crypto/deposit/receiving-address
    # Deposit Network Fee
    # https://service-api.paymero.io/v1/crypto/deposit/network-fee/USDT/USDT
    # Rate
    # https://service-api.paymero.io/v1/crypto/rate
    # Payment
    # https://service-api.paymero.io/v1/crypto/payment/038f1bc2-2de9-4ae5-9dc2-a9ef99aa1dee
    # Network Fee
    # https://service-api.paymero.io/v1/crypto/network-fee/USDT/CNY
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_paymerocrypto extends Abstract_payment_api {
    const COIN_BTC  = "BTC";
    const COIN_USDT = "USDT";
    const COIN_LTC  = "LTC";
    const COIN_ETH  = "ETH";
    const COIN_BCH  = "BCH";
    const COIN_BSV  = "BSV";

    const CALLBACK_SUCCESS        = "credited";
    const CALLBACK_SUCCESS_PAYOUT = 'debited';
    const CALLBACK_SUCCESS_STATUS = "confirmed";
    const RETURN_SUCCESS_CODE     = 'success';
    const RETURN_ERROR_CODE       = 'fail';
    const CALLBACK_TYPE_RECEIVE   = "deposit";
    const CALLBACK_TYPE_SEND      = "payout";
    const CRYPTO_SELL = 'sell';
    const DEFAULT_AMOUNT_DIFF = 100;
    const DEFAULT_PERCENTAGE_DIFF = 10;

    const STATUS_FAILED         = "removed";

    public $coin;
    public $api_key;
    public $paymeny_type;

    public function __construct($params = null) {
        parent::__construct($params);

        $this->_custom_curl_header = ["Content-Type: application/json","X-Api-Key: ".$this->getSystemInfo('key')];

        $this->coin           = $this->getCoin();
        $this->api_key        = $this->getSystemInfo('key');
        $this->paymeny_type   = $this->getSystemInfo('paymeny_type');
        $this->currency       = $this->getSystemInfo('currency');
        $this->targetCurrency = $this->getSystemInfo('targetCurrency');
    }

    # Implement these to specify pay type
    protected abstract function getCoin();
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {}
    // protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected function handlePaymentFormResponse($handle) {}
    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));

        #check deposit or withdrawal
        if (!empty($orderId)) {
            if(substr($orderId, 0, 1) == 'W') {
                $order     = $this->CI->wallet_model->getWalletAccountByTransactionCode($orderId);
                $secure_id = $order['transactionCode'];
            }
            else{
                $order     = $this->CI->sale_order->getSaleOrderById($orderId);
                $secure_id = $order->secure_id;
            }
        }

        $processed = false;

        $raw_post_data = file_get_contents('php://input', 'r');
        $flds = json_decode($raw_post_data, true);
        $this->CI->utils->debug_log('=====================callbackFromServer raw_post_data', $raw_post_data);
        $this->CI->utils->debug_log('=====================callbackFromServer json_decode flds', $flds);

        if(isset($flds['status'])){
            $txid = $flds['id'];
            $type = $flds['type'];

            $this->CI->utils->debug_log('=====================callbackFromServer get id id', $txid,$type);

            if($type == self::CALLBACK_TYPE_RECEIVE){
                if ($flds['status'] !== $this->getSystemInfo('callback_tatus', self::CALLBACK_SUCCESS)) {
                    $this->writePaymentErrorLog('=====================callbackFromServer Callback status error', $flds);
                    $result['return_error'] = lang('paymerocrypto Get Transaction Failed').': ['.$flds['status'].']';
                    return $result;
                }
            }
            else if($type == self::CALLBACK_TYPE_SEND){
                if ($flds['status'] !== self::CALLBACK_SUCCESS_STATUS) {
                    $this->writePaymentErrorLog('=====================callbackFromServer Callback status error', $flds);
                    $result['return_error'] = lang('paymerocrypto Get Transaction Failed').': ['.$flds['status'].']';
                    return $result;
                }
            }

            #----Get Transaction info----
            #https://service-api.paymero.io/v1/crypto/payment/id
            $get_transaction_url = $this->getSystemInfo('url').'payment/'.$txid;
            $response = $this->submitGetForm($get_transaction_url, [], true, $secure_id, false);
            $response = json_decode($response);
            $this->CI->utils->debug_log('=====================callbackFromServer Payment json_decode response', $response);

            if (isset($response->status)) {
                if ($response->status == self::RETURN_SUCCESS_CODE) {
                    #check more params when receive
                    if($type == self::CALLBACK_TYPE_RECEIVE){
                        $validate['status'] = $response->data->status;
                        // $validate['targetAmount'] = $response->data['targetAmount'];
                        $validate['cashierAmount'] = $response->data->cashierAmount;
                        $validate['merchantOrderId'] = $response->data->address->externalId;
                        $validate['address'] = $response->data->address->address;
                        $this->utils->debug_log('=====================callbackFromServer Payment validate', $validate);

                        if (!$order || !$this->checkCallbackOrder($order, $validate, $processed)) {
                            return $result;
                        }
                    }
                    else if($type == self::CALLBACK_TYPE_SEND){

                        $validate['status'] = $response->data->status;
                        // $validate['targetAmount'] = $response->data['targetAmount'];
                        $validate['cashierAmount'] = $response->data->cashierAmount;
                        $validate['merchantOrderId'] = $response->data->externalId;
                        $this->utils->debug_log('=====================callbackFromServer Payment validate', $validate);

                        if (!$order || !$this->checkCallbackTransaction($order, $validate)) {
                            return $result;
                        }
                    }
                } else {
                    $this->utils->writePaymentErrorLog('=====================callbackFromServer Payment status error', $response);
                    $result['return_error'] = lang('paymerocrypto Get Payment status Failed').': ['.$response->status.'] '.$response->message;
                    return $result;
                }

            } else {
                $this->utils->writePaymentErrorLog('=====================callbackFromServer Payment error', $response);
                $result['return_error'] = lang('paymerocrypto Get callback Payment Failed').': ['.$response->message.']';
                return $result;
            }

        }else{
            $this->writePaymentErrorLog('=====================callbackFromServer Callback flds error', $flds);
            $result['return_error'] = lang('paymerocrypto Get Transaction Failed').': ['.$flds['message'].']';
            return $result;
        }

        if($type == self::CALLBACK_TYPE_RECEIVE){
            # Update player balance based on order status
            # if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
            if ($order->status == Sale_order::STATUS_BROWSER_CALLBACK || $order->status == Sale_order::STATUS_SETTLED) {
                $this->CI->utils->debug_log('callbackFromServer already get callback for order:' . $order->id, $response);
            } else {
                if ($validate['status'] == $this->getSystemInfo('callback_tatus', self::CALLBACK_SUCCESS)) {
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
            }

            $result['success'] = true;
            if ($processed) {
                $result['message'] = self::RETURN_SUCCESS_CODE;
            } else {
                $result['return_error'] = 'Error';
            }
        }
        else if($type == self::CALLBACK_TYPE_SEND){
            if ($validate['status'] == self::CALLBACK_SUCCESS_STATUS) {
                $msg = sprintf('paymerocrypto withdrawal success: paymerocrypto id ID [%s]', $validate['merchantOrderId']);
                $this->CI->wallet_model->withdrawalAPIReturnSuccess($orderId, $msg);

                $result['message'] = self::RETURN_SUCCESS_CODE;
                $result['success'] = true;
            }
            // else if ($validate['status'] == self::STATUS_FAILED) {
            //     $msg = sprintf('paymerocrypto withdrawal failed.');
            //     $this->writePaymentErrorLog($msg, $response);
            //     $this->CI->wallet_model->withdrawalAPIReturnFailure($orderId, $msg);
            //     $result['message'] = $msg;
            // }
            else {
                $msg = sprintf('paymerocrypto withdrawal payment was not successful: [%s]', $validate['status']);
                $this->writePaymentErrorLog($msg, $response);
                $result['message'] = $msg;
            }
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'status', 'merchantOrderId', 'cashierAmount', 'address'
        );

        foreach($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================paymerocrypto checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        $processed = true; # processed is set to true once the signature verification pass

        if ($fields['status'] != $this->getSystemInfo('callback_tatus', self::CALLBACK_SUCCESS)) {
            $this->writePaymentErrorLog("=====================paymerocrypto checkCallbackOrder Payment status is not credited", $fields);
            return false;
        }

        if ($fields['address'] != $order->external_order_id) {
            $this->writePaymentErrorLog("=====================paymerocrypto checkCallbackOrder Payment address is wrong, expected [$order->external_order_id]", $fields);
            return false;
        }

        $amount = $this->convertAmountToCurrency($order->amount);
        if ($fields['cashierAmount'] != $amount) {
            if($this->getSystemInfo('allow_callback_amount_diff')){

                $percentage = $this->getSystemInfo('diff_amount_percentage');
                $limit_amount = $this->getSystemInfo('diff_limit_amount');

                if (!empty($percentage) && !empty($limit_amount)) {
                    $percentage_amt = str_replace(',', '', $amount) * ($percentage / 100);
                    $diffAmtPercentage = abs(str_replace(',', '', $amount) - $percentage_amt);

                    $this->CI->utils->debug_log("=====================paymerocrypto checkCallbackOrder amount details",$percentag,$limit_amount,$percentage_amt,$diffAmtPercentage);

                    if ($percentage_amt > $limit_amount) {
                        $this->writePaymentErrorLog("=====================paymerocrypto checkCallbackOrder Payment amounts ordAmt - payAmt > $limit_amount limit amount, expected [$order->amount]", $fields ,$diffAmount);
                        return false;
                    }

                    if ($fields['cashierAmount'] < $diffAmtPercentage) {
                        $this->writePaymentErrorLog("=====================paymerocrypto checkCallbackOrder Payment amounts ordAmt - payAmt > $percentage Percentage, expected [$order->amount]", $fields ,$diffAmtPercentage);
                        return false;
                    }
                }

                $this->CI->utils->debug_log("=====================paymerocrypto checkCallbackOrder amount not match expected [$order->amount]",$fields);

                $orderStatus = $order->status;
                if ($orderStatus == Sale_order::STATUS_DECLINED || $orderStatus == Sale_order::STATUS_SETTLED) {
                    $this->writePaymentErrorLog("=====================paymerocrypto checkCallbackOrder Payment order status has been approved or declined : [$orderStatus]", $fields);
                    return false;
                }else{
                    $notes = $order->notes . " | callback diff amount, origin was: " . $order->amount;
                    $this->CI->sale_order->fixOrderAmount($order->id, str_replace(',', '', $fields['cashierAmount']), $notes);
                }
            }
        }

        if ($fields['merchantOrderId'] != $order->secure_id) {
            $this->writePaymentErrorLog("=====================paymerocrypto checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    private function checkCallbackTransaction($order, $fields) {
        $requiredFields = array(
            'status', 'merchantOrderId', 'cashierAmount'
        );

        foreach($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================paymerocrypto checkCallbackTransaction payout Missing parameter: [$f]", $fields);
                return false;
            }
        }

        if ($fields['status'] != self::CALLBACK_SUCCESS_STATUS) {
            $this->writePaymentErrorLog("=====================paymerocrypto checkCallbackTransaction payout status is not confirmed", $fields);
            return false;
        }

        if ($fields['cashierAmount'] != $order['amount']) {
            $amount = $order['amount'];
            $this->writePaymentErrorLog("=====================paymerocrypto checkCallbackTransaction payout amount is wrong, expected [$amount]", $fields);
            return false;
        }

        if ($fields['merchantOrderId'] != $order['transactionCode']) {
            $transId = $order['transactionCode'];
            $this->writePaymentErrorLog("========================paymerocrypto checkCallbackTransaction payout transId do not match, expected [$transId]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    public function getCryptoNetworkFee(){
        $network_fee_url = $this->getSystemInfo('url').$this->paymeny_type.'/network-fee/'.$this->currency.'/'.$this->targetCurrency;
        return $this->submitGetForm($network_fee_url, [], true, null,false);
    }

    public function getCryptoRate(){
        #https://service-api.paymero.io/v1/crypto/rate
        $crypto_rate_url = $this->getSystemInfo('url').'rate';
        $params['ticker'] = $this->currency.'_'.$this->targetCurrency;
        $params['amount'] = 1;
        $params['action'] = self::CRYPTO_SELL;
        return $this->submitPostForm($crypto_rate_url, $params, true, null);
    }

    protected function convertAmountToCurrency($amount) {
        return number_format($amount, 2, '.', '');
    }
}