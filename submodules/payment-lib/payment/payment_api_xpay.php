<?php

require_once dirname(__FILE__) . '/abstract_payment_api_xpay.php';
/**
 * XPAY
 *
 * * XPAY_PAYMENT_API, ID: 5461
 *
 * Required Fields:
 * * Account
 * * URL
 * * ApiKey
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://www.paymentonline515.com/payment.php
 * * ApiKey: ## Api Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xpay extends Abstract_payment_api_xpay
{
    public function getPlatformCode()
    {
        return XPAY_PAYMENT_API;
    }

    public function getPrefix()
    {
        return 'xpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info)
    {
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $params['subIssuingBank'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }
    }

    protected function processPaymentUrlForm($params)
    {
        return $this->processPaymentUrlFormPost($params);
        // return $this->processPaymentUrlFormUrl($params);
    }
}
