<?php
require_once dirname(__FILE__) . '/abstract_payment_api_heropay.php';
/**
 * HEROPAY
 *
 * * HEROPAY_PAYMENT_API, ID: 5805
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://47.251.11.242:3020/api/pay/create_order
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_heropay extends Abstract_payment_api_heropay {

	public function getPlatformCode() {
		return HEROPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'heropay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		if(!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if(!empty($extraInfo)){
				$bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
				$cardNumber = $extraInfo['field_required_card_number'];
				$bankName = $extraInfo['field_required_bank_name'];
            }
        }
        $params['productId'] = $this->getSystemInfo('productId') ? $this->getSystemInfo('productId') : self::PAY_TYPE_BANK;
        $params['param1'] = $bank.';'.$bankName.';'.$cardNumber;
	}

    // # Hide bank selection drop-down
    // # 加上前綴 " field_required_ ", 欄位為必填
    public function getPlayerInputInfo() {

        return array(
        	array('name' => 'bank_list', 'type' => 'bank_list', 'label_lang' => 'pay.bank',
					'external_system_id' => $this->getPlatformCode(),
					'bank_list' => $this->getBankList(), 'bank_tree' => $this->getBankListTree(), 'bank_list_default' => $this->getSystemInfo('bank_list_default')),
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),

            array('name' => 'field_required_card_number', 'type' => 'number', 'label_lang' => 'cashier.player.bank_num'),
            array('name' => 'field_required_bank_name', 'type' => 'text', 'value' => '','label_lang' => 'cashier.player.bank_name'),
        );
    }


    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getBankListInfoFallback() {
		return array(
			array('label' => 'Canara Bank'  			, 'value' => 'IDPT0001') ,
			array('label' => 'DCB Bank'     			, 'value' => 'IDPT0002') ,
			array('label' => 'Federal Bank' 			, 'value' => 'IDPT0003') ,
			array('label' => 'HDFC Bank'    			, 'value' => 'IDPT0004') ,
			array('label' => 'Punjab National Bank' 	, 'value' => 'IDPT0005') ,
			array('label' => 'Indian Bank'     			, 'value' => 'IDPT0006') ,
			array('label' => 'ICICI Bank'     			, 'value' => 'IDPT0007') ,
			array('label' => 'Syndicate Bank'     		, 'value' => 'IDPT0008') ,
			array('label' => 'Karur Vysya Bank'     	, 'value' => 'IDPT0009') ,
			array('label' => 'Union Bank of India'  	, 'value' => 'IDPT0010') ,
			array('label' => 'Kotak Mahindra Bank'  	, 'value' => 'IDPT0011') ,
			array('label' => 'IDFC First Bank'     		, 'value' => 'IDPT0012') ,
			array('label' => 'Andhra Bank'     			, 'value' => 'IDPT0013') ,
			array('label' => 'Karnataka Bank'     		, 'value' => 'IDPT0014') ,
			array('label' => 'icici corporate bank' 	, 'value' => 'IDPT0015') ,
			array('label' => 'Axis Bank'     			, 'value' => 'IDPT0016') ,
			array('label' => 'UCO Bank'     			, 'value' => 'IDPT0017') ,
			array('label' => 'South Indian Bank'   		, 'value' => 'IDPT0018') ,
			array('label' => 'Yes Bank'     			, 'value' => 'IDPT0019') ,
			array('label' => 'Standard Chartered Bank'  , 'value' => 'IDPT0020') ,
			array('label' => 'State Bank of India'      , 'value' => 'IDPT0021') ,
			array('label' => 'Indian Overseas Bank'     , 'value' => 'IDPT0022') ,
			array('label' => 'Bandhan Bank'     		, 'value' => 'IDPT0023') ,
			array('label' => 'Central Bank of India'    , 'value' => 'IDPT0024') ,

		);
	}

}