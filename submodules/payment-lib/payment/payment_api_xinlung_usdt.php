<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xinlung_usdt.php';

/**
 * applepay
 *
 * * XINLUNG_USDT_PAYMENT_API, ID: 5924
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://merchant.chainpro.me/api/Interface/Guide
 * * Account: ## Live Merchant no ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_xinlung_usdt extends Abstract_payment_api_xinlung_usdt {

	public function getPlatformCode() {
		return XINLUNG_USDT_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xinlung_usdt';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['money']  = $extraInfo['crypto_amount'];
                $params['rate']  = $extraInfo['deposit_crypto_rate'];
                $params['blockType'] = $extraInfo['field_required_blockType'];
            }
        }

    }

	protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
    }

	public function getPlayerInputInfo() {
        return array(
            array('name' => 'crypto_amount', 'type' => 'crypto_amount', 'label_lang' => sprintf(lang('Please Enter Crypto Amount'), lang("USDT-Crypto")), 'crypto_currency_lang' => lang("USDT-Crypto"), 'default_currency_lang' => lang("CN Yuan")),
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09', 'readonly' => 1),
            [
                'name' => 'field_required_blockType' ,
                'type' => 'list' ,
                'label_lang' => 'crypto_xinlung_blockchain' ,
                'list' => [
                    self::PAY_BLOCKTYPE_ETHUSDT => 'crypto_ethereum' ,
                    self::PAY_BLOCKTYPE_TRXUSDT => 'crypto_tron'
                ]
            ]
        );
    }

}
