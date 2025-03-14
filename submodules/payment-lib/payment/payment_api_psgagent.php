<?php
require_once dirname(__FILE__) . '/abstract_payment_api_psgagent.php';

/**
 *
 * * PSGAGENT_PAYMENT_API', ID: 5298
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
class Payment_api_psgagent extends Abstract_payment_api_psgagent {

    public function getPlatformCode() {
        return PSGAGENT_PAYMENT_API;
    }

    public function getPrefix() {
        return 'psgagent';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {

        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
        $params['bizType']  = self::BIZTYPE_ONLINEBANK;
        $params['bankCode'] = $bank;
        $params['commodityName'] = 'Deposit';
        $params['notifyUrl'] = $this->getReturnUrl($orderId);

        $params['phoneNo']  = '';
        $params['cerdType'] = '';
        $params['cerdId']   = '';
        $params['acctNo']   = '';
        $params['cvn2']     = '';
        $params['expDate']  = '';
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }

    private function getReturnUrl($orderId) {
        return parent::getCallbackUrl('/callback/browser/success/' . $this->getPlatformCode() . '/' . $orderId);
    }
}
