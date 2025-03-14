<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_PAYMENT_API, ID: 6388
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_paybus_wedpay_bank_qr extends Abstract_payment_api_paybus {

    public function getPlatformCode() {
        return PAYBUS_WEDPAY_BANK_QR_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_wedpay_bank_qr';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
		$bankCodeUse=$this->getSystemInfo('bankCodeUse');
        $channel_input = null;
		if(!empty($direct_pay_extra_info)) {
	        $extraInfo = json_decode($direct_pay_extra_info, true);
            $channel_input['bank']=$extraInfo['field_required_bank_code'];
	        if(!empty($extraInfo['field_required_bank_code'])&&$bankCodeUse){
                $params['channel_input'] = json_decode(json_encode([self::CHANNEL_WEDPAY_BANK_QR => $channel_input]));
	        }
		}
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {
		$getPlayerInputInfo= array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
	   	);
		if($this->getSystemInfo('bankCodeUse')){
			$newArray = array('name' => 'field_required_bank_code', 'type' => 'list', 'label_lang' => 'pay.bank','list' => $this->getBankList());
			array_push($getPlayerInputInfo, $newArray);			
		}
        return $getPlayerInputInfo;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    protected function getBankListInfoFallback() {
        $banklist=$this->getSystemInfo('bank_list');
        if(empty($banklist)){
            $banklist=array(
                array('label' => "BANGKOK BANK ธนาคารกรุงเทพ", 'value' => 'BBL'),
                array('label' => "Kasikornbank ธนาคารกสิกรไทย", 'value' => 'KBANK'),
                array('label' => "Krung Thai Bank บริษัท ธนาคารกรุงไทย", 'value' => 'KTB'),
                array('label' => "TMBThanachart Bank ธนาคารทหารไทย", 'value' => 'TTB'),
                array('label' => "The Siam Commercial Bank ธนาคารไทยพาณิชย์", 'value' => 'SCB'),
                array('label' => "Citibank Thailand ธนาคารซิตี้แบงก์", 'value' => 'CITI'),
                array('label' => "Standard Chartered ธนาคารสแตนดาร์ดชาร์เตอร์ด", 'value' => 'SCBT'),
                array('label' => "CIMB Thai Bank ธนาคารซีไอเอ็มบีไทย", 'value' => 'CIMBT'),
                array('label' => "United Overseas Bank ธนาคารยูโอบี", 'value' => 'UOBT'),
                array('label' => "Bank of Ayudhya ธนาคารกรุงศรีอยุธยา", 'value' => 'BAY'),
                array('label' => "Government Savings Bank ธนาคารออมสิน",'value' => 'GSB'),
                array('label' => "Government Housing Bank ธนาคารอาคารสงเคราะห์", 'value' => 'GHB'),
                array('label' => "Bank for Agriculture and Agricultural Cooperatives ธนาคารเพื่อการเกษตรและสหกรณ์การเกษตร", 'value' => 'BAAC'),
                array('label' => "Export-Import Bank of Thailand ธนาคารเพื่อการส่งออกและนำเข้าแห่งประเทศไทย", 'value' => 'EXIM'),
                array('label' => "Bank of China (Thai) ธนาคารแห่งประเทศจีน", 'value' => 'BOC'),
                array('label' => "Islamic Bank of Thailand ธนาคารอิสลามแห่งประเทศไทย", 'value' => 'ISBT'),
                array('label' => "TISCO Bank ธนาคารทิสโก้", 'value' => 'TISCO'),
                array('label' => "KIATNAKIN BANK ธนาคารเกียรตินาคิน", 'value' => 'KKP'),
                array('label' => "ICBC Bank ธนาคารไอซีบีซี", 'value' => 'ICBCT'),
                array('label' => "Thai Credit Retail Bank ธนาคารไทยเครดิตเพื่อรายย่อย", 'value' => 'TCD'),
                array('label' => "LH Bank ธนาคารแลนด์ แอนด์ เฮาส์", 'value' => 'LHFG'),
                array('label' => "Australia and New Zealand Banking Group Limited ธนาคารเอเอ็นแซด", 'value' => 'ANZ'),
                array('label' => "Sumitomo Mitsui Trust Bank ธนาคารซูมิโตโม มิตซุย ทรัสต์", 'value' => 'SMBT/SMBC'),
                array('label' => "SME Development Bank ธนาคารพัฒนาวิสาหกิจขนาดกลางและขนาดย่อมแห่งประเทศไทย", 'value' => 'SME'),
            );
        }
        return $banklist;
    }
}