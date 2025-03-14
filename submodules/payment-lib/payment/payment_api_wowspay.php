<?php
require_once dirname(__FILE__) . '/abstract_payment_api_wowspay.php';

/**
 * wowspay
 *
 * * WOWSPAY_PAYMENT_API, ID: 6347
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_wowspay extends Abstract_payment_api_wowspay {

    public function getPlatformCode() {
        return WOWSPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'wowspay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);

        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }

        $params['paytype']    = self::MODE_BANKTRANSFER;
        $params['tobankcode'] = $bank;
        $params['bankflag']   = !empty($bank) ? $this->getBankFlag($bank) : "";
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getBankFlag($bankCode) {
        $bank_flag = '';
        $extrainfo_bank = $this->getSystemInfo('bank_list');
        if (!empty($extrainfo_bank)) {
            $bank_flag = !empty($extrainfo_bank[$bankCode]) ? $extrainfo_bank[$bankCode] : "";
        } else {
            $extrainfo_bank = $this->getBankListInfoFallback();
            foreach ($extrainfo_bank as $bank) {
                if ($bank['value'] == $bankCode) {
                    $bank_flag = $bank['label'];
                }
            }
        }
        return $bank_flag;
    }

    public function getBankListInfoFallback() {
        return array(
            array('label' => 'Bank Central Asian', 'value' => 'CENA'),
            array('label' => 'Bank Negara Indonesia', 'value' => 'BNIN'),
            array('label' => 'Bank Mandiri', 'value' => 'BMRI'),
            array('label' => 'Bank Rakyat Indonesia', 'value' => 'BRIN'),
            array('label' => 'Panin Bank', 'value' => 'PINB'),
            array('label' => 'Bank Danamon', 'value' => 'BDIN'),
            array('label' => 'Bank Bukopin', 'value' => 'BBUK'),
            array('label' => 'Bank Mega', 'value' => 'MEGA'),
            array('label' => 'Bank Sinarmas', 'value' => 'SBJK'),
            array('label' => 'Bank BTN', 'value' => 'BTAN'),
            array('label' => 'Bank Maybank Indonesia', 'value' => 'IBBK'),
            array('label' => 'Bank Permata', 'value' => 'BBBA'),
            array('label' => 'Bank Central Asian (VA)', 'value' => 'VA01'),
            array('label' => 'Bank Negara Indonesia (VA)', 'value' => 'VA02'),
            array('label' => 'Bank Mandiri (VA)', 'value' => 'VA03'),
            array('label' => 'Bank Rakyat Indonesia (VA)', 'value' => 'VA04'),
            array('label' => 'Panin Bank (VA)', 'value' => 'VA05'),
            array('label' => 'Bank Danamon (VA)', 'value' => 'VA06'),
            array('label' => 'Bank Bukopin (VA)', 'value' => 'VA07'),
            array('label' => 'Bank Mega (VA)', 'value' => 'VA08'),
            array('label' => 'Bank Sinarmas (VA)', 'value' => 'VA09'),
            array('label' => 'Bank BTN (VA)', 'value' => 'VA10'),
            array('label' => 'Bank Maybank Indonesia (VA)', 'value' => 'VA11'),
            array('label' => 'Bank Permata (VA)', 'value' => 'VA12')
        );
    }
}