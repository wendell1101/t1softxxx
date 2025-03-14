<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yzfpay.php';

/**
 *
 * * YZFPAY_STATIC_QRCODE_PAYMENT_API', ID: 5186
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 
 * Field Values:
 * * URL: dora-elb-public
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yzfpay_static_qrcode extends Abstract_payment_api_yzfpay {

    public function getPlatformCode() {
        return YZFPAY_STATIC_QRCODE_PAYMENT_API;
    }

    public function getPrefix() {
        return 'yzfpay_static_qrcode';
    }

        //====implements Payment_api_interface start===================================
    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null) {
        $order = $this->CI->sale_order->getSaleOrderById($orderId);
        $direct_pay_extra_info = $order->direct_pay_extra_info;
        $extraInfo = json_decode($direct_pay_extra_info, true);
        $field_required_external_order_num = $extraInfo['field_required_external_order_num'];

        $this->CI->utils->debug_log("==================================yzfpay generatePaymentUrlForm extraInfo ", $extraInfo);
        $this->CI->load->model(['sale_order']);
        $this->CI->sale_order->updateExternalInfo($orderId,'无第三方帐单编号', $field_required_external_order_num, # no info available 
            null, null, null);

        return array('success' => true, 'type' => Abstract_payment_api::REDIRECT_TYPE_DIRECT_PAY, 'next_url'=> '');
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['OptType'] = self::OPTTYPE_ALIPAY;
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'staticQrcode', 'type' => 'html', 'value' => $this->getStaticImage()),
            array('name' => 'field_required_external_order_num', 'type' => 'number', 'label_lang' => 'field_required_external_order_num'),
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    public function getStaticImage() {
        $hint = $this->getSystemInfo("hint");
        $qrUrl = $this->getSystemInfo("qrUrl");
        $html = '<a href='.$qrUrl.' target="_blank"><button type="button" class="btn btn-staticQrcode">'.$hint.'</button></a>';
        return $html;
    }

    public function directPay($order = null) {
        return array('success' => true , 'next_url'=> ''); # direct pay not supported by this API
    }

    protected function processPaymentUrlForm($params) {}
}
