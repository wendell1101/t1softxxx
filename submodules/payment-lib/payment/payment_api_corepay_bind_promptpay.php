<?php
require_once dirname(__FILE__) . '/abstract_payment_api_corepay.php';

/**
 * corepay
 *
 * * COREPAY_PAYMENT_API, ID: 6250
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://vippay.corepaypro.com/trade/repay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_corepay_bind_promptpay extends Abstract_payment_api_corepay {

    public function getPlatformCode() {
        return COREPAY_BIND_PROMPTPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'corepay_bind_promptpay';
    }

    protected function configParams(&$unSignParams, $direct_pay_extra_info) {
		$unSignParams['bank_code'] = self::CHANNEL_BIND_PROMPTPAY;
		$extraInfo = json_decode($direct_pay_extra_info, true);
        if(!empty($direct_pay_extra_info)) {
	        // $extraInfo = json_decode($direct_pay_extra_info, true);
	         if(!empty($extraInfo['field_required_player_account_number'])){
				$unSignParams['repay_account_number'] =$extraInfo['field_required_player_account_number'];
				$unSignParams['repay_account_bank'] =$extraInfo['field_required_corepay_bank_code'];
			}elseif(!empty($extraInfo['player_account_number'])){
				$unSignParams['repay_account_number'] =$extraInfo['player_account_number'];
				$unSignParams['repay_account_bank'] =$extraInfo['corepay_bank_code'];
			}
	    }
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {

		$direct_pay_extra_info= $this->direct_pay_extra_info;
		$player_account_number="";
		$bank_code="";
		$bank_detail="";

		if(!empty($direct_pay_extra_info)){
			if(isset($direct_pay_extra_info['field_required_player_account_number'])&&isset($direct_pay_extra_info['field_required_corepay_bank_code'])){
				$player_account_number=$direct_pay_extra_info['field_required_player_account_number'];
				$bank_code=$direct_pay_extra_info['field_required_corepay_bank_code'];
			}elseif(isset($direct_pay_extra_info['player_account_number'])&&isset($direct_pay_extra_info['corepay_bank_code'])){
				$player_account_number=$direct_pay_extra_info['player_account_number'];
				$bank_code=$direct_pay_extra_info['corepay_bank_code'];
			}
		}

		$bank_detail=$this->getBackDetail($bank_code);

        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
            array('name' => 'field_required_player_account_number', 'type' => 'number', 'label_lang' => 'cashier.player.bank_num','default_value' => $player_account_number),
            array('name' => 'field_required_corepay_bank_code', 'type' => 'list', 'label_lang' => 'pay.bank',
                'list' => $this->getBankList(),'default_value' => $bank_detail),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
    protected function getBankListInfoFallback() {
		return array(
			array('label' => lang('Bank for Agriculture and Agricultural Cooperatives'), 'value' => 'BAAC'),
			array('label' => lang('Bank of Ayudhya Public Company Limited'), 'value' => 'BAY'),
			array('label' => lang('BANGKOK BANK PUBLIC COMPANY LIMITED'), 'value' => 'BBL'),
			array('label' => lang('CIMB Thai Bank Public Company Limited'), 'value' => 'CIMB'),
			array('label' => lang('Citibank Thailand'), 'value' => 'CITI'),
			array('label' => lang('Government Housing Bank'), 'value' => 'GHB'),
			array('label' => lang('Government Savings Bank'), 'value' => 'GSB'),
			array('label' => lang('The Hongkong and Shanghai Banking Corporation Limited'), 'value' => 'HSBC'),
			array('label' => lang('ICBC Bank (Thai) Public Company Limited'), 'value' => 'ICBC'),
			array('label' => lang('Islamic bank of thailand'), 'value' => 'IBANK'),
			array('label' => lang('Kasikorn Bank Plc.'), 'value' => 'KBANK'),
			array('label' => lang('KIATNAKIN PHATRA BANK PUBLIC COMPANY LIMITED'), 'value' => 'KKP'),
			array('label' => lang('Land and Houses Bank Public Company Limited'), 'value' => 'LHBANK'),
			array('label' => lang('Mizuho Corporate Bank Limited'), 'value' => 'MHCB'),
			array('label' => lang('Siam Commercial Bank Plc.'), 'value' => 'SCB'),
			array('label' => lang('Standard Chartered Bank Nakornthon Plc.'), 'value' => 'SCBN'),
			array('label' => lang('Sumitomo Mitsui Banking Corporation'), 'value' => 'SMBC'),
			array('label' => lang('Thanachart Bank Public Company Limited'), 'value' => 'TBANK'),
			array('label' => lang('Thai Credit Retail Bank Public Company Limited'), 'value' => 'TCRB'),
			array('label' => lang('TISCO Bank Plc'), 'value' => 'TISCO'),
            array('label' => lang('TMB Bank Plc.'), 'value' => 'TMB'),
			array('label' => lang('UOB Bank Plc.'), 'value' => 'UOB'),
            array('label' => lang('AIG Retail Bank Public Company Limited'), 'value' => 'AIG'),
			array('label' => lang('Bank of America.'), 'value' => 'BOA'),
			array('label' => lang('BNP Paribas Bangkok Bank'), 'value' => 'BNP'),
			array('label' => lang('Bank of china'), 'value' => 'BOC'),
			array('label' => lang('Tokyo Mitsubishi Bank'), 'value' => 'TMB'),
            array('label' => lang('Agrigol Bank Indonesia'), 'value' => 'ABI'),
			array('label' => lang('Deutsche Bank'), 'value' => 'DEUTB'),
			array('label' => lang('Export-Import Bank of Thailand'), 'value' => 'EXIMB'),
			array('label' => lang('IOB Indian Overseas'), 'value' => 'IOB'),
			array('label' => lang('JP Morgan Chase Bank Bangkok Branch'), 'value' => 'JPMCB'),
			array('label' => lang('Mega International Commercial Bank'), 'value' => 'MICB'),
			array('label' => lang('Oversea-Chinese Banking Corporation Limited'), 'value' => 'OCBC'),
			array('label' => lang('ABN AMRONV. Bank'), 'value' => 'ABN'),
			array('label' => lang('RHB Bank Limited'), 'value' => 'RHB'),
			array('label' => lang('Small and Medium Enterprise Development Bank of Thailand'), 'value' => 'SMEB'),
			array('label' => lang('Krung Thai Bank'), 'value' => 'KTB'),
			array('label' => lang('TMB Bank and Thanachart'), 'value' => 'TTB'),
		);
	}

	public function initPlayerPaymentInfo($player_id) {
		$direct_pay_extra_info=[];
		$playerId = $player_id;
		$this->CI->load->model('sale_order');
		$lastSaleOrder=$this->CI->sale_order->getLastSaleOrderByIdSysId($playerId,$this->getPlatformCode());
		if(!empty($lastSaleOrder)){
			$direct_pay_extra_info=json_decode($lastSaleOrder->direct_pay_extra_info,true);
		}
		$this->direct_pay_extra_info = $direct_pay_extra_info;
	}

	public function getBackDetail($bankCode) {
		$bankList=$this->getBankListInfoFallback();
		foreach ($bankList as $key => $value) {
			if($value['value']==$bankCode){
				return ['code'=>$value['value'],'name'=>$value['label']];
			}
		}
	}
}