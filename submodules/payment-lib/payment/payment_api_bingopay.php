<?php
require_once dirname(__FILE__) . '/abstract_payment_api_bingopay.php';
/** 
 *
 * BINGOPAY 
 * 
 * 
 * * BINGOPAY_PAYMENT_API, ID: 673
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://pay.bingopay.com/chargebank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bingopay extends Abstract_payment_api_bingopay {

	public function getPlatformCode() {
		return BINGOPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'bingopay';
	}

   

	protected function configParams(&$params,&$data, $direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
                $params['bank_code'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
				switch ($params['bank_code']) {
					case '102':
						$params['cardname'] =  '工商银行';
						break;
					case '103':
						$params['cardname'] =  '农业银行';
						break;
					case '104':
						$params['cardname'] =  '中国银行';
						break;
					case '105':
						$params['cardname'] =  '建设银行';
						break;
					case '302':
						$params['cardname'] =  '中信银行';
						break;
					case '303':
						$params['cardname'] =  '光大银行';
						break;
					case '304':
						$params['cardname'] =  '华夏银行';
						break;
					case '306':
						$params['cardname'] =  '广发银行';
						break;
					case '308':
						$params['cardname'] =  '招商银行';
						break;
					case '309':
						$params['cardname'] =  '兴业银行';
						break;
					case '310':
						$params['cardname'] =  '浦发银行';
						break;	
					case '403':
						$params['cardname'] =  '邮储银行';
						break;
				}

			}
		}
		$params['bus_no'] = self::DEFAULTNANK_BANK;
		$data['productId']='0500';
		
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}
}
