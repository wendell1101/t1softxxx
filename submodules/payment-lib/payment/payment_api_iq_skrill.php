<?php
require_once dirname(__FILE__) . '/abstract_payment_api_iq.php';

/**
 * PaymentIQ
 * https://backoffice.paymentiq.io
 * https://test-backoffice.paymentiq.io
 *
 * * IQ_SKRILL_PAYMENT_API, ID: 5560
 *
 * Required Fields:
 * * URL
 * * Account
 *
 * Field Values:
 * * URL: https://api.paymentiq.io/paymentiq/api/skrill/deposit/process
 * * Account: ## Merchant ID ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_iq_skrill extends Abstract_payment_api_iq {

    public function getPlatformCode() {
        return IQ_SKRILL_PAYMENT_API;
    }

    public function getPrefix() {
        return 'iq_skrill';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo['field_required_skrill_email'])) {
                $params['email'] = $extraInfo['field_required_skrill_email'];

                unset($extraInfo['field_required_skrill_email']);

                $info = json_encode($extraInfo);
                $this->CI->sale_order->updateSaleOrderDirectPayExtraInfoById($params['orderId'], $info);
            }
        }
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'field_required_skrill_email','type' => 'text', 'label_lang' => 'Skrill Email', 'value' => ''),
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params, $orderId) {
        return $this->processPaymentUrlFormForRedirect($params, $orderId);
    }
}
