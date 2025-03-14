<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dorapay.php';

/**
 *
 * DORAPAY Bankcard 银行卡充值
 *
 * DORAPAY_BANKCARD_PAYMENT_API', ID: 723
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * Field Values:
 *
 * * URL (sandbox): http://52.69.65.224/Mownecum_2_API_Live/Deposit?format=json
 * * Extra Info
 * > {
 * >     "daddypay_company_id" : "## company id ##",
 * >     "bank_list": {
 * >         "ICBC" : "_json: { \"1\": \"ICBC\", \"2\": \"中国工商银行\"}",
 * >         "CMB" : "_json: { \"1\": \"CMB\", \"2\": \"招商银行\"}",
 * >         "CCB" : "_json: { \"1\": \"CCB\", \"2\": \"中国建设银行\"}",
 * >         "ABC" : "_json: { \"1\": \"ABC\", \"2\": \"中国农业银行\"}",
 * >         "BOC" : "_json: { \"1\": \"BOC\", \"2\": \"中国银行\"}",
 * >         "BCM" : "_json: { \"1\": \"BCM\", \"2\": \"交通银行\"}",
 * >         "CMBC" : "_json: { \"1\": \"CMBC\", \"2\": \"中国民生银行\"}",
 * >         "ECC" : "_json: { \"1\": \"ECC\", \"2\": \"中信银行\"}",
 * >         "SPDB" : "_json: { \"1\": \"SPDB\", \"2\": \"上海浦东发展银行\"}",
 * >         "PSBC" : "_json: { \"1\": \"PSBC\", \"2\": \"邮政储汇\"}",
 * >         "CEB" : "_json: { \"1\": \"CEB\", \"2\": \"中国光大银行\"}",
 * >         "PINGAN" : "_json: { \"1\": \"PINGAN\", \"2\": \"平安银行 （原深圳发展银行）\"}",
 * >         "CGB" : "_json: { \"1\": \"CGB\", \"2\": \"广发银行股份有限公司\"}",
 * >         "HXB" : "_json: { \"1\": \"HXB\", \"2\": \"华夏银行\"}",
 * >         "CIB" : "_json: { \"1\": \"CIB\", \"2\": \"福建兴业银行\"}",
 * >         "TENPAY" : "_json: { \"1\": \"TENPAY\", \"2\": \"财付通\"}"
 * >     }
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dorapay_bankcard extends Abstract_payment_api_dorapay {

    public function getPlatformCode() {
        return DORAPAY_BANKCARD_PAYMENT_API;
    }

    public function getPrefix() {
        return 'dorapay_bankcard';
    }

    public function getName() {
        return 'DORAPAY_BANKCARD';
    }


    public function getDepositMode() {
        return parent::DEPOSIT_MODE_BANKCARD;
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['biz_content']['channel_code'] = self::PAYTYPE_BANKCARD;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

}
