<?php
require_once dirname(__FILE__) . '/abstract_payment_api_fkpay.php';

/**
 * 66支付
 * http://www.fkpay.vip/client/pay/getPayUrl
 *
 * * FKPAY_WEIXIN_PAYMENT_API, ID: 5648
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
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_fkpay_weixin extends Abstract_payment_api_fkpay
{
    public function getPlatformCode()
    {
        return FKPAY_WEIXIN_PAYMENT_API;
    }

    public function getPrefix()
    {
        return 'fkpay_weixin';
    }

    protected function configParams(&$params, $direct_pay_extra_info)
    {
        $params['payType'] = self::PAYTYPE_WEIXIN;
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo()
    {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params)
    {
        return $this->processPaymentUrlFormRedirect($params);
    }
}
