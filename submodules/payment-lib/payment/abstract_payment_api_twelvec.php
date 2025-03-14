<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * TWELVEC
 *
 * * TWELVEC_CARD_PAYMENT_API, ID: 5808
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://naptien.ga/api/SIM/RegCharge
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_twelvec extends Abstract_payment_api {

    const RESPONSE_CODE_SUCCESS = "1";
    const CHECK_STATUS_SUCCESS = 'success';
    const RETURN_SUCCESS_CODE = 'success';
    const CALLBACK_SUCCESS = 'success';

    # Implement these to specify pay type
    protected abstract function configParams(&$params, $direct_pay_extra_info);
    protected abstract function processPaymentUrlForm($params);

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $player = $this->CI->player->getPlayerById($playerId);

        $params = array();
        $params['apiKey']    = $this->getSystemInfo('key');
        $params['code']      = $this->getCardInfo($order->direct_pay_extra_info, 'get_bank_code');
        $params['serial']    = $this->getCardInfo($order->direct_pay_extra_info, 'get_card_serial');
        $params['type']      = $this->getSystemInfo('type','vt');
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['menhGia']   = $this->convertAmountToCurrency($amount,$order->created_at);
        $params['requestId'] = $order->secure_id;

        $this->CI->utils->debug_log('=====================twelvec generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function handlePaymentFormResponse($params) {
        $response = $this->submitGetForm($this->getSystemInfo('url'), $params, false, $params['requestId']);
        $this->CI->utils->debug_log('=====================twelvec handlePaymentFormResponse response before', $response);
        $response = json_decode($response,true);
        $this->CI->utils->debug_log('=====================twelvec handlePaymentFormResponse response after', $response);

        $order = $this->CI->sale_order->getSaleOrderBySecureId($params['requestId']);
        $this->unsetExtraInfo($order);

        //{"stt":1,"msg":"Complete","data":{"id":119}}
        if(isset($response['stt'])){

            if($response['stt'] == self::RESPONSE_CODE_SUCCESS){
                $data = array();
                $data['Message']   = $response['msg'];
                $this->CI->utils->debug_log("=====================twelvec handlePaymentFormResponse params", $data);

                $collection_text_transfer = '';
                $collection_text = $this->getSystemInfo("collection_text_transfer", array(''));
                if(is_array($collection_text)){
                    $collection_text_transfer = $collection_text;
                }
                $is_not_display_recharge_instructions = $this->getSystemInfo('is_not_display_recharge_instructions');

                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_STATIC,
                    'data' => $data,
                    'hide_timeout' => true,
                    'collection_text_transfer' => $collection_text_transfer,
                    'is_not_display_recharge_instructions' => $is_not_display_recharge_instructions
                );
            }else{
                return array(
                    'success' => false,
                    'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                    'message' => '['.$response['stt'].']'.$response['msg'],
                );
            }
        } elseif (isset($response['Message'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => '['.$response['Message'].']',
            );
        }else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
            );
        }
    }

    # Callback URI: /callback/fixed_process/<payment_id>
    public function getOrderIdFromParameters($flds) {
        if (empty($flds)) {
            $raw_post_data = file_get_contents('php://input', 'r');
            $flds = json_decode($raw_post_data, true);
        }

        if (isset($flds['requestId'])) {
            $this->CI->load->model(array('sale_order'));
            $order = $this->CI->sale_order->getSaleOrderBySecureId($flds['requestId']);
            return $order->id;
        }
        else {
            $this->utils->debug_log('=====================twelvec callbackOrder cannot get any order_id when getOrderIdFromParameters', $flds);
            return;
        }
    }

    protected function unsetExtraInfo($order){
        $direct_pay_extra_info = $order->direct_pay_extra_info;
        $extraInfo = json_decode($direct_pay_extra_info, true);
        $this->CI->utils->debug_log('=====================twelvec unsetExtraInfo direct_pay_extra_info', $direct_pay_extra_info);

        unset($extraInfo['get_bank_code']);
        unset($extraInfo['get_card_serial']);
        $this->CI->utils->debug_log('=====================twelvec unsetExtraInfo updateExternalInfo', $extraInfo);

        $info = json_encode($extraInfo);
        $this->CI->sale_order->updateSaleOrderDirectPayExtraInfoById($order->id, $info);
    }

    public function checkDepositStatus($secureId) {
        $param = array();
        $param['apiKey'] = $this->getSystemInfo('key');
        $param['trans_id'] = $secureId;
        $this->CI->utils->debug_log('=============================twelvec checkDepositStatus param: ', $param);
        $checkDepositURL = $this->getSystemInfo('checkDepositStatusURL','http://hub2.12c.biz:10004/api/sim/checkchargebytrans');
        $response = $this->submitGetForm($checkDepositURL, $param, false, $secureId);
        $this->CI->utils->debug_log('=============================twelvec handlePaymentFormResponse response', $response);

        return $this->decodetwelvecDepositStatusResult($response);
    }

    public function decodetwelvecDepositStatusResult($response){
        if(empty($response)){
            $this->CI->utils->debug_log('=============================twelvec decodetwelvecDepositStatusResult unknown result: ', $response);
            return ['success' => FALSE, 'message' => 'Unknown response data'];
        }

        $decode_response = json_decode($response,true);
        $this->CI->utils->debug_log('=============================twelvec decodetwelvecDepositStatusResult decode_response: ', $decode_response);

        //{"stt":1,"msg":"card_fail","data":{"id":117,"cardSerial":"123456789","cardPrice":0,"cardType":"mb","createDate":"2018-05-17T17:35:13.63","status":"card_fail"}}
        if (isset($decode_response['stt']) && $decode_response['stt'] == self::RESPONSE_CODE_SUCCESS) {

            $returnOrderId = $decode_response['data']['callback_id'];
            $returnCode = $decode_response['data']['status'];
            $returnReasonMsg = $decode_response['msg'];
            $returnAmount = $decode_response['data']['menhGia'];

            if($returnCode == self::CHECK_STATUS_SUCCESS ){
                $order = $this->CI->sale_order->getSaleOrderBySecureId($returnOrderId);

                if($returnAmount == $this->convertAmountToCurrency($order->amount)){
                    $message = "twelvec payment success orderId:".$returnOrderId.", Status code: ".$returnCode.", Msg:".$returnReasonMsg.", Amt:".$returnAmount;
                    return array('success' => true, 'message' => $message);
                }

            }else{
                $message = "twelvec payment failed orderId:".$returnOrderId.", Status code: ".$returnCode.", Msg:".$returnReasonMsg;
                return array('success' => false, 'message' => $message);
            }
        } else {
            $this->CI->utils->debug_log('=============================twelvec decodetwelvecDepositStatusResult unknown decode_response result: ', $decode_response);
            return ['success' => FALSE, 'message' => 'no information found'];
        }
    }

    public function callbackFromServer($orderId, $params) {
        $response_result_id = parent::callbackFromServer($orderId, $params);
        return $this->callbackFrom('server', $orderId, $params, $response_result_id);
    }

    public function callbackFromBrowser($orderId, $params) {
        $response_result_id = parent::callbackFromBrowser($orderId, $params);
        return $this->callbackFrom('browser', $orderId, $params, $response_result_id);
    }

    private function callbackFrom($source, $orderId, $flds, $response_result_id) {
        $result = array('success' => false, 'next_url' => null, 'message' => lang('error.payment.failed'));
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $processed = false;

        $this->CI->utils->debug_log("=====================twelvec callbackFrom $source flds", $flds);

        if($source == 'server'){
            if (!$order || !$this->checkCallbackOrder($order, $flds, $processed)) {
                return $result;
            }
        }

        # Update order payment status and balance
        $success = true;

        # Update player balance based on order status
        # if it's STATUS_SETTLED or STATUS_BROWSER_CALLBACK, put log, and ignore
        $orderStatus = $this->CI->sale_order->getSaleOrderStatusById($orderId);
        if ($orderStatus == Sale_order::STATUS_BROWSER_CALLBACK || $orderStatus == Sale_order::STATUS_SETTLED) {
            $this->CI->utils->debug_log('callbackFrom' . ucfirst($source) . ', already get callback for order:' . $order->id, $flds);
            if ($source == 'server' && $order->status == Sale_order::STATUS_BROWSER_CALLBACK) {
                $this->CI->sale_order->setStatusToSettled($orderId);
            }
        } else {
            # update player balance
            $this->CI->sale_order->updateExternalInfo($order->id, $flds['requestId'], null, null, null, $response_result_id);
            if ($source == 'browser') {
                $success = $this->CI->sale_order->browserCallbackSaleOrder($order->id, 'auto broswer callback ' . $this->getPlatformCode(), false);
            } elseif ($source == 'server') {
                if ($flds['status'] == self::CALLBACK_SUCCESS) {
                    $this->approveSaleOrder($order->id, 'auto server callback ' . $this->getPlatformCode(), false);
                }
            }
        }

        $result['success'] = $success;
        if ($processed) {
            $result['message'] = self::RETURN_SUCCESS_CODE;
        } else {
            $result['return_error'] = 'Error';
        }

        if ($source == 'browser') {
            $result['next_url'] = $this->getPlayerBackUrl();
            $result['go_success_page'] = true;
        }

        return $result;
    }

    private function checkCallbackOrder($order, $fields, &$processed = false) {
        $requiredFields = array(
            'requestId', 'menhGiaThe', 'menhGiaDK', 'menhGiaThuc','status', 'signature'
        );

        foreach ($requiredFields as $f) {
            if (!array_key_exists($f, $fields)) {
                $this->writePaymentErrorLog("=====================twelvec checkCallbackOrder Missing parameter: [$f]", $fields);
                return false;
            }
        }

        # is signature authentic?
        if ($fields['signature'] !== $this->sign($fields)) {
            $this->writePaymentErrorLog('======================twelvec checkCallbackOrder Signature Error', $fields);
            return false;
        }

        $processed = true; # processed is set to true once the signature verification pass

        #check cardValue only when success, failed value will be 0
        if($fields['status'] != self::CALLBACK_SUCCESS){
            $this->writePaymentErrorLog("======================twelvec checkCallbackOrder Payment status is not success", $fields);
            return false;
        }

        if ($fields['menhGiaThe'] != $this->convertAmountToCurrency($order->amount)) {
            $this->writePaymentErrorLog("======================twelvec checkCallbackOrder Payment amount is wrong, expected [$order->amount]", $fields);
            return false;
        }

        if ($fields['requestId'] != $order->secure_id) {
            $this->writePaymentErrorLog("======================twelvec checkCallbackOrder order IDs do not match, expected [$order->secure_id]", $fields);
            return false;
        }

        # everything checked ok
        return true;
    }


    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    protected function sign($params) {
        $signStr = $params['requestId'].$params['status'].$params['menhGiaThe'].$this->getSystemInfo('secretKey');
        $sign = md5($signStr);
        return $sign;
    }

    # -- Private functions --
    # After payment is complete, the gateway will invoke this URL asynchronously
    protected function getNotifyUrl($orderId) {
        return parent::getCallbackUrl('/callback/process/' . $this->getPlatformCode() . '/' . $orderId);
    }

    ## After payment is complete, the gateway will send redirect back to this URL
    protected function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }

    protected function convertAmountToCurrency($amount) {
        $convert_multiplier = $this->getSystemInfo('convert_multiplier', 1);
        return number_format($amount * $convert_multiplier, 0, '.', '') ;
    }

    protected function getCardInfo($direct_pay_extra_info, $type){
        $decode_direct_pay_extra_info = json_decode($direct_pay_extra_info,true);
        $cardInfo = $decode_direct_pay_extra_info[$type];
        return $cardInfo;
    }
}