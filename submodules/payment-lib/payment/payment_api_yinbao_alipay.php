<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yinbao.php';

/**
 * YINBAO 银宝 - 支付宝
 * http://www.9vpay.com/
 *
 * YINBAO_ALIPAY_PAYMENT_API, ID: 150
 *
 * Required Fields:
 *
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: http://wytj.9vpay.com/PayBank.aspx
 * * Extra Info
 * > {
 * >	"yinbao_partner" : "## Partner ID ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yinbao_alipay extends Abstract_payment_api_yinbao {

	public function getPlatformCode() {
		return YINBAO_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yinbao_alipay';
	}

	public function getBankType($direct_pay_extra_info){
		return $this->utils->is_mobile() ? parent::BANK_TYPE_ALIPAY_WAP : parent::BANK_TYPE_ALIPAY;
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}
}