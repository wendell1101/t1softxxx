<?php
require_once dirname(__FILE__) . '/abstract_payment_api_macaubus.php';

/**
 *
 * * MACAUBUS_QUICKPAY_PAYMENT_API', ID: 789
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
class Payment_api_macaubus_quickpay extends Abstract_payment_api_macaubus {

    public function getPlatformCode() {
        return MACAUBUS_QUICKPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'macaubus_quickpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['app_secret'] = $this->getSystemInfo('app_secret');
        //$params['pay_code'] = self::SCANTYPE_QUICKPAY;
        //$params['cardno'] = 'mbICBC';
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
                $playerBankNum = $extraInfo['player_bank_num'];
            }
        }
        $params['cardno'] = $playerBankNum;
        $params['pay_code'] = self::SCANTYPE_QUICKPAY;
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        $playerInputInfo = parent::getPlayerInputInfo();
        $playerInputInfo[] = array('name' => 'player_bank_num',   'type' => 'number', 'label_lang' => 'cashier.player.bank_num');

        return $playerInputInfo;
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),

        );
       

    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
