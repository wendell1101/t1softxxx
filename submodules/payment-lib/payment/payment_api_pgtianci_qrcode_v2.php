<?php
require_once dirname(__FILE__) . '/abstract_payment_api_pgtianci.php';
/**
 * PGTIANCI QRCODE V2
 *
 * * PGTIANCI_QRCODE_V2_PAYMENT_API, ID: 5886
 *
 * Required Fields:
 * * URL
 * * Key
 * * uid (merchant ID)
 *
 * Field Values:
 * * URL        : https://tianciv070115.com/api/transaction
 * * Key        : ## Live key ##
 * * uid        : ## merchant ID ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_pgtianci_qrcode_v2 extends Abstract_payment_api_pgtianci {

    public function getPlatformCode() {
        return PGTIANCI_QRCODE_V2_PAYMENT_API;
    }

    public function getPrefix() {
        return 'pgtianci_qrcode_v2';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['bank'] = $this->getBankType($direct_pay_extra_info);
    }

    public function getBankType($direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                return array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        } else {
            return parent::getBankType($direct_pay_extra_info);
        }
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }
}