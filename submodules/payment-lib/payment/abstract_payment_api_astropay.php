<?php
require_once dirname(__FILE__) . '/abstract_payment_api.php';

/**
 * astropay
 *
 * * ASTROPAY_CARD_PAYMENT_API, ID: 5292
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://sandbox-api.astropaycard.com/verif/validator
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Abstract_payment_api_astropay extends Abstract_payment_api {

    const X_TYPE            = "AUTH_CAPTURE";
    const X_CURRENCY        = "RMB";
    const X_RESPONSE_FORMAT = "json";

    const RESULT_CODE_SUCCESS   = "1";
    const RESPONSE_CODE_SUCCESS = "1";
    Const RESPONSE_CODE_FAIL    = "3";
    const CALLBACK_STATUS_SUCCESS = "0";

    const RETURN_FAIL_CODE = 'FAIL';
    const RETURN_SUCCESS_CODE = 'success';

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
        $params['x_login']       = $this->getSystemInfo('account');
        $params['x_tran_key']    = $this->getSystemInfo('x_trans_key');
        $params['x_type']        = self::X_TYPE;
        $params['x_card_num']    = $this->getCardNum($order->direct_pay_extra_info);
        $params['x_card_code']   = $this->getCardCode($order->direct_pay_extra_info);
        $params['x_exp_date']    = $this->getExpDate($order->direct_pay_extra_info);
        $params['x_amount']      = $this->convertAmountToCurrency($amount,$order->created_at);
        $params['x_currency']    = $this->getSystemInfo('currency','RMB');
        $params['x_unique_id']   = $player['username'];
        $params['x_invoice_num'] = $order->secure_id;
        $params['x_response_format'] = self::X_RESPONSE_FORMAT;
        $this->configParams($params, $order->direct_pay_extra_info);

        $this->CI->utils->debug_log('=====================astropay generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function handlePaymentFormResponse($params) {
        $response = $this->processCurl($this->getSystemInfo('url'), $params);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================astropay handlePaymentFormResponse response', $response);

        $order = $this->CI->sale_order->getSaleOrderBySecureId($params['x_invoice_num']);
        $this->unsetExtraInfo($order);

        if($response['md5_hash'] == $this->sign($response)){
            $this->CI->sale_order->updateExternalInfo($order->id, $response['TransactionID']);

            if($response['response_code'] == self::RESPONSE_CODE_SUCCESS){
                $success=$this->approveSaleOrder($order->id, 'Astropay auto direct success ' . $this->getPlatformCode(), false);
                if($success){
                    return array(
                        'success' => true,
                        'type'    => self::REDIRECT_TYPE_URL,
                        'url'     => '/callback/show_success/'.$this->getPlatformCode().'/'.$order->id
                    );
                }else{
                    return array(
                        'success' => false,
                        'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                        'message' => lang('Approve order failed')
                    );
                }
            }else{
                $message = $this->getSystemInfo('error_message');
                $this->CI->session->set_flashdata('message', $message);

                return array(
                    'success' => true,
                    'type'    => self::REDIRECT_TYPE_URL, # will be redirected to a view for error display
                    'url'     => '/callback/show_error/'.$this->getPlatformCode().'/'.$order->id
                );
            }
        } else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidate API response')
            );
        }
    }

    protected function unsetExtraInfo($order){
        $direct_pay_extra_info = $order->direct_pay_extra_info;
        $extraInfo = json_decode($direct_pay_extra_info, true);
        $this->CI->utils->debug_log('=====================astropay unsetExtraInfo direct_pay_extra_info', $direct_pay_extra_info);

        unset($extraInfo['get_bank_num']);
        unset($extraInfo['get_card_code']);
        unset($extraInfo['get_exp_date']);
        $this->CI->utils->debug_log('=====================astropay unsetExtraInfo updateExternalInfo', $extraInfo);

        $info = json_encode($extraInfo);
        $this->CI->sale_order->updateSaleOrderDirectPayExtraInfoById($order->id, $info);
    }

    protected function processCurl($url, $params) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $this->setCurlProxyOptions($ch);
        $response = curl_exec($ch);
        $this->CI->utils->debug_log('=========================processCurl curl content ', $response);

        $errCode = curl_errno($ch);
        $error = curl_error($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $content = substr($response, $header_size);

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        $statusText = $errCode . ':' . $error;
        curl_close($ch);

        $this->CI->utils->debug_log('url', $url, 'params', $params , 'response', $response, 'errCode', $errCode, 'error', $error, 'statusCode', $statusCode);

        $response_result_id = $this->submitPreprocess($params, $content, $url, $response, ['errCode' => $errCode, 'error' => $error, 'statusCode' => $statusCode], $params['x_invoice_num']);

        return $content;
    }

    public function checkDepositStatus($secureId) {
        $param = array();
        $param['x_login'] = $this->getSystemInfo('account');
        $param['x_trans_key'] = $this->getSystemInfo('x_trans_key');
        $param['x_invoice_num'] = $secureId;
        $param['x_response_format'] = 'json';
        $this->CI->utils->debug_log('======================================astropay checkDepositStatus param: ', $param);

        $checkDepositURL = $this->getSystemInfo('checkDepositURL');
        $response = $this->processCurl($checkDepositURL, $param);

        return $this->decodeAstropayDepositStatusResult($response);
    }

    public function decodeAstropayDepositStatusResult($response){
        if(empty($response)){
            $this->CI->utils->debug_log('==================================astropay decodeAstropayDepositStatusResult unknown result: ', $response);
            return [
                'success' => FALSE,
                'message' => 'Unknown response data'
            ];
        }
        $decode_response = json_decode($response,true);
        $this->CI->utils->debug_log('======================================astropay decodeAstropayDepositStatusResult decode_response: ', $decode_response);
        $returnOrderId = $decode_response['x_invoice_num'];

        if(isset($decode_response['code'])){
            $returnCode = $decode_response['code'];
            $returnReasonMsg = $decode_response['reason_text'];
        }else{
            $returnResponseCode = $decode_response['response_code'];
            $returnMsg = $decode_response['response_reason_text'];
        }

        if($returnCode == self::RESPONSE_CODE_SUCCESS && $decode_response['md5hash'] == $this->sign($decode_response)){
            $order = $this->CI->sale_order->getSaleOrderBySecureId($returnOrderId);
            if($decode_response['x_amount'] == $this->convertAmountToCurrency($order->amount)){
                $message = "astropay payment success orderId:".$returnOrderId.", Status code: ".$returnCode.", Msg:".$returnReasonMsg;
                return array('success' => true, 'message' => $message);
            }
        }elseif($returnCode == self::RESPONSE_CODE_FAIL){
            $message = "astropay payment failed orderId:".$returnOrderId.", Status code: ".$returnResponseCode.", Msg:".$returnMsg;
            return array('success' => false, 'message' => $message);
        }else{
            $message = "astropay payment failed orderId:".$returnOrderId.", Status code: ".$returnCode.", Msg:".$returnReasonMsg;
            return array('success' => false, 'message' => $message);
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

    public function directPay($order = null) {
        return array('success' => false); # direct pay not supported by this API
    }

    # -- signatures --
    protected function sign($params) {
        $signStr = $this->getSystemInfo('account').$params['TransactionID'].$params['x_amount'];
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

    ## Format the amount value for the API
    protected function convertAmountToCurrency($amount, $orderDateTime) {
        if($this->getSystemInfo('use_usd_currency')){
            if(is_string($orderDateTime)){
                $orderDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $orderDateTime);
            }
            $amount = $this->gameAmountToDBByCurrency($amount, $this->utils->getTimeForMysql($orderDateTime), 'USD', 'CNY');
            $this->CI->utils->debug_log('=====================astropay convertAmountToCurrency use_usd_currency', $amount);
        }
        return number_format($amount, 2, '.', '');
    }

    protected function getCardNum($direct_pay_extra_info){
        $decode_direct_pay_extra_info = json_decode($direct_pay_extra_info,true);
        $CardNum = $decode_direct_pay_extra_info['get_bank_num'];
        return $CardNum;
    }

    protected function getCardCode($direct_pay_extra_info){
        $decode_direct_pay_extra_info = json_decode($direct_pay_extra_info,true);
        $CardCode = $decode_direct_pay_extra_info['get_card_code'];
        return $CardCode;
    }

    protected function getExpDate($direct_pay_extra_info){
        $decode_direct_pay_extra_info = json_decode($direct_pay_extra_info,true);
        $ExpDate = $decode_direct_pay_extra_info['get_exp_date'];
        return $ExpDate;
    }
}