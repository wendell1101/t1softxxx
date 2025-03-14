<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tianfu.php';

/**
 * TIANFU 天天付
 * *
 * * TIANFU_PAYMENT_API, ID: 964
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.zhizeng-pay.net/mas/mobile/create.do
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_tianfu extends Abstract_payment_api_tianfu {
    const RESULT_CODE_SUCCESS = '0000';

    public function getPlatformCode() {
        return TIANFU_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tianfu';
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        if ($this->shouldRedirect($enabledSecondUrl)) {
            $url = $this->CI->utils->getPaymentUrl($this->getSystemInfo('second_url'), $this->getPlatformCode(), $amount, $playerId, $playerPromoId, false, $bankId, $orderId);
            $result = array('success' => true, 'type' => self::REDIRECT_TYPE_URL, 'url' => $url);
            return $result;
        }

        $order = $this->CI->sale_order->getSaleOrderById($orderId);

        $params = array();
        //$params['app_id']         = $this->getSystemInfo('account');
        $params['method']         = 'realpay.trade.ebankpay';
        $params['format']         = 'JSON';
        $params['charset']        = 'utf-8';
        $params['sign_type']      = 'MD5';
        $params['format']         = 'JSON';
        $params['timestamp']      = $orderDateTime->format('Y-m-d H:i:s');
        $params['version']        = '1.0';
        $params['notify_url']     = $this->getNotifyUrl($orderId);

        $params['biz_content']['out_trade_no']   = $order->secure_id;
        $params['biz_content']['total_amount']   = $this->convertAmountToCurrency($amount);
        $params['biz_content']['subject']        = 'Topup';
        $params['biz_content']['body']           = 'Topup';
        $this->configParams($params, $order->direct_pay_extra_info);
        $params['biz_content']['payer_ip']       = $this->getClientIP();
        $params['biz_content']['referer_url']    = $_SERVER['HTTP_HOST']; #发起支付交易的用户端来源url地址
        $params['biz_content']['page_url']       = $this->getReturnUrl($orderId);

        $params['sign'] = $this->sign($params);
        $this->CI->utils->debug_log('=====================tianfu generatePaymentUrlForm params', $params);

        return $this->processPaymentUrlForm($params);
    }

    protected function processPaymentUrlFormPost($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, true, $params['biz_content']['out_trade_no']);
        $this->CI->utils->debug_log('=====================tianfu processPaymentUrlFormPost response', $response);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================tianfu processPaymentUrlFormPost response', $response);

        if($response['realpay_trade_create_ebank_pay_response']['code'] == self::RESULT_CODE_SUCCESS) {
            $order = $this->CI->sale_order->getSaleOrderBySecureId($params['biz_content']['out_trade_no']);
            $this->CI->sale_order->updateExternalInfo($order->id, $response['realpay_trade_create_ebank_pay_response']['trade_no']);
            return array(
                'success' => true,
                'type' => self::REDIRECT_TYPE_URL,
                'url' => $response['realpay_trade_create_ebank_pay_response']['pay_url'],
            );
        }
        else if(isset($response['realpay_trade_create_ebank_pay_response']['msg'])) {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => $response['realpay_trade_create_ebank_pay_response']['code'].': '.$response['realpay_trade_create_ebank_pay_response']['msg']
            );
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidte API response')
            );
        }
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['biz_content']['payType'] = self::PAYTYPE_DEBITCARD;

        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['biz_content']['channel'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
