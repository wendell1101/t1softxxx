<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dynastypay.php';

/**
 * DYNASTYPAY
 *
 *
 * * DYNASTYPAY_PAYMENT_API ID: 5557
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 *
 * Field Values:
 *
 * * Extra Info:
 * > {
 * >    "HashKey" : "## HashKey ##",
 * >	   "HashIV" : "## HashIV ##"
 * > }
 *https://www.dynaspay.com/GatePayment.php
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dynastypay extends Abstract_payment_api_dynastypay
{
    public function getPlatformCode()
    {
        return DYNASTYPAY_PAYMENT_API;
    }
    public function getPrefix()
    {
        return 'dynastypay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo()
    {
        return array(array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'));
    }

    protected function processPaymentUrlForm($params)
    {
        return $this->processPaymentUrlFormPost($params);
    }
}