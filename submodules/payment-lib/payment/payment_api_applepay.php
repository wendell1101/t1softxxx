<?php
require_once dirname(__FILE__) . '/abstract_payment_api_applepay.php';

/**
 * applepay
 *
 * * applepay_PAYMENT_API, ID: 5847
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://mapay168.com/api_server/receive_add.php
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_applepay extends Abstract_payment_api_applepay
{
    public function getPlatformCode()
    {
        return APPLEPAY_PAYMENT_API;
    }

    public function getPrefix()
    {
        return 'applepay';
    }

    protected function configParams(&$params, $direct_pay_extra_info)
    {
        // $params['type'] = self::PAY_METHODS_ONLINE_BANK;
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo['field_required_card_number'])) {
                // $params['trans_name']      = $extraInfo['field_required_bankcard_number_name'];
             //    $params['trans_last_code'] = substr($extraInfo['field_required_card_number'], -5);
            }
        }
    }

    protected function processPaymentUrlForm($params)
    {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getPlayerInputInfo()
    {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
            // array('name' => 'field_required_bankcard_number_name',  'type' => 'text', 'label_lang' => 'Bank Account Owner Name','value' => ''),
            // array('name' => 'field_required_card_number', 'type' => 'number', 'label_lang' => 'cashier.player.bank_num'),
        );
    }
}
