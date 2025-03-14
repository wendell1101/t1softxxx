<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dingan.php';

/** 
 *
 * DINGAN 定安科技
 * 
 * 
 * * 'DINGAN_QUICKPAY_PAYMENT_API', ID 5443
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: 
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dingan_quickpay extends Abstract_payment_api_dingan {

	public function getPlatformCode() {
		return DINGAN_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dingan_quickpay';
	}

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_way'] = self::PAYWAY_QUICKPAY;

        if(!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if(!empty($extraInfo['field_required_card_number'])){
                $params['bank_card_no'] = $extraInfo['field_required_card_number'];
            }
        }
    }

    # Hide bank selection drop-down
    # 加上前綴 " field_required_ ", 欄位為必填
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
            array('name' => 'field_required_card_number', 'type' => 'number', 'label_lang' => 'cashier.player.bank_num'),  
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);  
    }

}
