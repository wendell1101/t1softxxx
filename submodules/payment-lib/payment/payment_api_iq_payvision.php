<?php
require_once dirname(__FILE__) . '/abstract_payment_api_iq.php';

/**
 * PaymentIQ
 * https://backoffice.paymentiq.io
 * https://test-backoffice.paymentiq.io
 *
 * * IQ_PAYVISION_PAYMENT_API, ID: 5563
 *
 * Required Fields:
 * * URL
 * * Account
 *
 * Field Values:
 * * URL: https://api.paymentiq.io/paymentiq/api/creditcard/deposit/process
 * * Account: ## Merchant ID ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_iq_payvision extends Abstract_payment_api_iq {

    public function getPlatformCode() {
        return IQ_PAYVISION_PAYMENT_API;
    }

    public function getPrefix() {
        return 'iq_payvision';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if(!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if(!empty($extraInfo)){
                $pubkey = openssl_get_publickey($this->getServerPubKeyStr());

                $params['encCreditcardNumber'] = $this->rsaEncrypt($extraInfo['field_required_payvision_cardnumber'], $pubkey);
                $params['encCvv']              = $this->rsaEncrypt($extraInfo['field_required_payvision_cvv'], $pubkey);
                $params['cardHolder']          = $extraInfo['field_required_payvision_name'];
                $params['expiryMonth']         = $extraInfo['field_required_payvision_expired_month'];
                $params['expiryYear']          = $extraInfo['field_required_payvision_expired_year'];

                unset($extraInfo['field_required_payvision_cardnumber']);
                unset($extraInfo['field_required_payvision_cvv']);
                unset($extraInfo['field_required_payvision_name']);
                unset($extraInfo['field_required_payvision_expired_month']);
                unset($extraInfo['field_required_payvision_expired_year']);

                $info = json_encode($extraInfo);
                $this->CI->sale_order->updateSaleOrderDirectPayExtraInfoById($params['orderId'], $info);
            }
        }
    }

    public function getPlayerInputInfo() {
        return array(
            array(
                'label_lang' => 'Card Holder Name',
                'name' => 'field_required_payvision_name',
                'type' => 'text',
                'value' => '',
            ),
            array(
                'label_lang' => 'Creditcard Number',
                'name' => 'field_required_payvision_cardnumber',
                'type' => 'number',
                'value' => '',
            ),
            array(
                'label_lang' => 'CVV',
                'name' => 'field_required_payvision_cvv',
                'type' => 'number',
                'value' => '',
                'attr_placeholder' => '123',
            ),
            array(
                'label_lang' => 'Expired Month',
                'name' => 'field_required_payvision_expired_month',
                'type' => 'number',
                'value' => '',
                'attr_placeholder' => '01',
            ),
            array(
                'label_lang' => 'Expired Year',
                'name' => 'field_required_payvision_expired_year',
                'type' => 'number',
                'value' => '',
                'attr_placeholder' => '2025',
            ),
            array(
                'label_lang' => 'cashier.09',
                'name' => 'deposit_amount',
                'type' => 'float_amount',
            ),
        );
    }

    protected function processPaymentUrlForm($params, $orderId) {
        return $this->processPaymentUrlFormForRedirect($params, $orderId);
    }

   # -- RSA Encrypt --
    private function rsaEncrypt($data, $pubkey) {
        openssl_public_encrypt($data, $encrypt_info, $pubkey, OPENSSL_PKCS1_PADDING);

        $encrypted = base64_encode($encrypt_info);
        $this->utils->debug_log("=========================iq rsaEncrypt encrypted result", $encrypted);
        return $encrypted;
    }

    # -- public key --
    private function getServerPubKeyStr() {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://test-api.paymentiq.io/paymentiq/api/viq/getvaultiqpublickey/'.$this->getSystemInfo("account"),
            CURLOPT_USERAGENT => 'CURL PHP'
        ));
        $resp = curl_exec($curl);
        curl_close($curl);
        $this->utils->debug_log("=========================iq getServerPubKeyStr resp", $resp);
        return $resp;
    }
}
