<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_PAYMENT_API, ID: 6541
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2023 tot
 */
class Payment_api_paybus_panpay_bank extends Abstract_payment_api_paybus {

    public function getPlatformCode() {
        return PAYBUS_PANPAY_BANK_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_panpay_bank';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $bankCodeUse=$this->getSystemInfo('bankCodeUse');
        $channel_input = null;
        if (!empty($params['client_id'])) {
            $sale_order = $this->sale_order->getSaleOrderBySecureId($params['client_id']);
        }
		if(!empty($direct_pay_extra_info)) {
	        $extraInfo = json_decode($direct_pay_extra_info, true);
            $channel_input = $extraInfo['field_required_bank_code'];
	        if(!empty($extraInfo['field_required_bank_code'])&&$bankCodeUse){
                $params['channel_input'] = json_decode(json_encode([
                    self::CHANNEL_PANPAY_BANK =>  array(
                        "uid"     => $sale_order->player_id,
                        "payType" => $channel_input
                    )
                ]));
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
                array('label' => "Vietcombank", 'value' => 'Vietcombank'),
                array('label' => "VietinBank", 'value' => 'VietinBank'),
                array('label' => "BIDV", 'value' => 'BIDV'),
                array('label' => "ACB", 'value' => 'ACB'),
                array('label' => "VIB", 'value' => 'VIB'),
                array('label' => "EximBank", 'value' => 'EximBank'),
                array('label' => "SHB", 'value' => 'SHB'),
                array('label' => "HDBank", 'value' => 'HDBank'),
                array('label' => "TPBank", 'value' => 'TPBank'),
                array('label' => "VPBANK", 'value' => 'VPBANK'),
                array('label' => "Techcombank", 'value' => 'Techcombank'),
                array('label' => "MB", 'value' => 'MB')      
            );
        }
        return $banklist;
    }
    
}