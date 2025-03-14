<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_CLOUDPAY_THB_QR_100204_PAYMENT_API, ID: 6567
 *
 * Field Values:
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_paybus_cloudpay_thb_qr_100204 extends Abstract_payment_api_paybus {

    const CHANNEL_CLOUDPAY_THB_QR_100204 = 'cloudpay_thb.qr_100204';

    public function getPlatformCode() {
        return PAYBUS_CLOUDPAY_THB_QR_100204_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_cloudpay_thb_qr_100204';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
		if(!empty($direct_pay_extra_info)) {
	        $extraInfo = json_decode($direct_pay_extra_info, true);
            $playerDetails = $params['playerDetails'];

            $firstname  = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName']))     ? $playerDetails[0]['firstName'] : '';
            $lastname   = (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))      ? $playerDetails[0]['lastName'] : '';
            $phone      = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber'] : '';
            $email      = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))         ? $playerDetails[0]['email'] : '';
            $bankType   = '';
            $cardNumber = '';

            if(!empty($extraInfo['field_required_bank_code']) && !empty($extraInfo['field_required_card_number'])) {
                $bankType = $extraInfo['field_required_bank_code'];
                $cardNumber = $extraInfo['field_required_card_number'];
                $this->utils->debug_log(__METHOD__, 'bankType', $bankType, 'cardNumber', $cardNumber);
            }elseif(isset($extraInfo['bank']) && isset($extraInfo['player_account_number'])) {
                $bankType = $extraInfo['bank'];
                $cardNumber = $extraInfo['player_account_number'];
                $this->utils->debug_log(__METHOD__, 'bankType', $bankType, 'cardNumber', $cardNumber);

            }

            $params['channel_input'] = json_decode(json_encode([
                self::CHANNEL_CLOUDPAY_THB_QR_100204 => array(
                    "pname"     => $firstname . ' ' . $lastname,
                    "pemail"    => $email,
                    "phone"     => $phone,
                    "accNo"     => $cardNumber,
                    "accType"   => $bankType,
                )
            ]));
		}
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
        $getPlayerInputInfo= array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
            array('name' => 'field_required_bank_code', 'type' => 'list', 'label_lang' => 'pay.bank','list' => $this->getBankList()),
            array('name' => 'field_required_card_number', 'type' => 'number', 'label_lang' => 'cashier.player.bank_num', 'attr_required' => 'required'),
	   	);

        return $getPlayerInputInfo;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    protected function getBankListInfoFallback() {
        $banklist=$this->getSystemInfo('bank_list');
        if(empty($banklist)){
            $banklist=array(
                array('label' => "ABN Amro Bank N.V.", 'value' => 'ABN'),
                array('label' => "BANK FOR AGRICULTURE AND AGRICULTURAL CO-OPERATIVES", 'value' => 'BAAC'),
                array('label' => "BANK OF AYUDHAYA PUBLIC COMPANY LTD/krungsri", 'value' => 'BAY'),
                array('label' => "BANGKOK BANK PUBLIC COMPANY LTD", 'value' => 'BBL'),
                array('label' => "CIMB THAI BANK PUBLIC COMPANY LTD", 'value' => 'CIMB'),
                array('label' => "Citibank N.A.", 'value' => 'CITI'),
                array('label' => "GOVERNMENT HOUSING BANK", 'value' => 'GHB'),
                array('label' => "THE GOVERNMENT SAVING BANK", 'value' => 'GSB'),
                array('label' => "Islamic Bank of Thailand", 'value' => 'IBANK'),
                array('label' => "KASIKORNBANK PCL", 'value' => 'KBANK'),
                array('label' => "Kiatnakin Phatra Bank Public Company Limited", 'value' => 'KKP'),
                array('label' => "KRUNG THAI BANK PUBLIC COMPANY LTD", 'value' => 'KTB'),
                array('label' => "Land and Houses Bank", 'value' => 'LHBA'),
                array('label' => "Mizuho Corporate Bank Limited", 'value' => 'MHCB'),
                array('label' => "THE SIAM COMMERCIAL BANK PUBLIC COMPANY", 'value' => 'SCB'),
                array('label' => "TISCO Bank Plc", 'value' => 'TISCO'),
                array('label' => "TMB BANK PUBLIC COMPANY LTD/TTB", 'value' => 'TMB'),
                array('label' => "UOB Bank Plc", 'value' => 'UOB'),
            );
        }
        return $banklist;
    }
}