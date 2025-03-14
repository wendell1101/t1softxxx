<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paymero.php';
/**
 * PAYMERO
 *
 * * PAYMERO_QRCODE_PAYMENT_API, ID: 5721
 *
 * Required Fields:
 * * URL
 * * Key
 *
 * Field Values:
 * * URL:
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_paymero_qrcode extends Abstract_payment_api_paymero {

    public function getPlatformCode() {
        return PAYMERO_QRCODE_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paymero_qrcode';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->utils->is_mobile()) {
            $params['deviceType'] = self::DEVICE_MOBILE;
        } else {
            $params['deviceType'] = self::DEVICE_PC;
        }

        if ($this->getSystemInfo('unset_pmid_params')) {
            unset($params['PMID']);
        }
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
