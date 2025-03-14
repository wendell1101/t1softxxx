<?php
require_once dirname(__FILE__) . '/abstract_payment_api_iq.php';

/**
 * PaymentIQ
 * https://backoffice.paymentiq.io
 * https://test-backoffice.paymentiq.io
 *
 * * IQ_NETELLER_PAYMENT_API, ID: 5559
 *
 * Required Fields:
 * * URL
 * * Account
 *
 * Field Values:
 * * URL: https://api.paymentiq.io/paymentiq/api/neteller/deposit/process
 * * Account: ## Merchant ID ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_iq_neteller extends Abstract_payment_api_iq {

    public function getPlatformCode() {
        return IQ_NETELLER_PAYMENT_API;
    }

    public function getPrefix() {
        return 'iq_neteller';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if(!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if(!empty($extraInfo['field_required_neteller_account']) && !empty($extraInfo['field_required_neteller_secureid'])){
                $params['account'] = $extraInfo['field_required_neteller_account'];
                $params['secureId'] = $extraInfo['field_required_neteller_secureid'];

                unset($extraInfo['field_required_neteller_account']);
                unset($extraInfo['field_required_neteller_secureid']);

                $info = json_encode($extraInfo);
                $this->CI->sale_order->updateSaleOrderDirectPayExtraInfoById($params['orderId'], $info);
            }
        }
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'field_required_neteller_account', 'type' => 'text', 'label_lang' => 'Neteller Email or Account', 'value' => '', 'attr_required' => 'required'),
            array('name' => 'field_required_neteller_secureid', 'type' => 'text', 'label_lang' => 'Neteller security ID', 'value' => '',  'attr_required' => 'required'),
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params, $orderId) {
        return $this->processPaymentUrlFormForRedirect($params, $orderId);
    }
}
