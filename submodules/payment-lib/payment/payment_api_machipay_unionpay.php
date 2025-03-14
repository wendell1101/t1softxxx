<?php
require_once dirname(__FILE__) . '/abstract_payment_api_machipay.php';

/**
 * MACHIPAY
 * https://mer.fastpay-technology.com/powerpay-mer/
 *
 * * MACHIPAY_UNIONPAY_PAYMENT_API, ID: 5261
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://paygate.fastpay-technology.com/powerpay-gateway-onl/txn
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_machipay_unionpay extends Abstract_payment_api_machipay {

    public function getPlatformCode() {
        return MACHIPAY_UNIONPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'machipay_unionpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['txnSubType'] = $this->getSystemInfo('txnSubType',self::SUBTYPE_UNIONPAY);
    }

    # Hide bank selection drop-down
    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        $redirect_type = $this->getSystemInfo('redirect_type');
        switch ($redirect_type) {
            case 'form':
                return $this->processPaymentUrlFormPost($params);
                break;

            case 'qrcode':
                return $this->processPaymentUrlFormQRCode($params);
                break;

            case 'url':
                return $this->processPaymentUrlFormRedirect($params);
                break;

            default:
                return $this->processPaymentUrlFormPost($params);
                break;
        }
    }
}
