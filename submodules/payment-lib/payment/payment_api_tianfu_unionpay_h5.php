<?php
require_once dirname(__FILE__) . '/abstract_payment_api_tianfu.php';

/**
 * TIANFU 天天付
 * *
 * * TIANFU_UNIONPAY_H5_PAYMENT_API, ID: 968
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
class Payment_api_tianfu_unionpay_h5 extends Abstract_payment_api_tianfu {

    public function getPlatformCode() {
        return TIANFU_UNIONPAY_H5_PAYMENT_API;
    }

    public function getPrefix() {
        return 'tianfu_unionpay_h5';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel'] = self::CHANNEL_UNIONPAY;
        $params['payType'] = self::PAYTYPE_UNIONPAY_H5;
        
        if(!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if(!empty($extraInfo['card_number'])){
                $params['payerAccountNo'] = $extraInfo['card_number'];    
            }
        }

    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'card_number', 'type' => 'number', 'label_lang' => 'cashier.player.bank_num'),     
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
