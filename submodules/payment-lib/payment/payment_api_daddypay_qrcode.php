<?php
require_once dirname(__FILE__) . '/abstract_payment_api_daddypay.php';

/**
 *
 * DaddyPay QRCode 扫描二维码
 *
 * DADDYPAY_QRCODE_PAYMENT_API, ID: 116
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
 * >     "daddypay_alipay_note" : "## alipay note: see documentation ##"
 * > }
 *
 * 支付宝附言规范举例：
 * 如果账户是手机号，则传递昵称（如果支付宝没有设置昵称，则传递姓名）最后一个字+账户的前三位+账户的后四位。比如：昵称“张三”，手机账号“13100004567”，则附言为“三1314567”；如果该客户的支付宝没有昵称，姓名为“大山”，则附言为“山1314567”。
 * 如果账户是Email，则传递昵称（如果支付宝没有设置昵称，则传递姓名）最后一个字+账户的前三位+@号以后的后缀，包括@号。例如：昵称“张三”，Email账号kevin@gmail.com，则附言为“三kev@gmail.com”；如果该客户支付宝账号没有设置昵称，姓名为“大山”，则附言为“山kev@gmail.com”。
 * 如果账户既绑定了手机号又绑定了Email,则使用开启登录方式的信息为附言组成内容。例如：昵称“张三”，绑定手机账号“13100004567”，绑定Email账号“kevin@gmail.com”，但是该客户选择以Email账号登录支付宝，则附言为“三kev@gmail.com”。
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_daddypay_qrcode extends Abstract_payment_api_daddypay {
	const BANK_ID_ALIPAY = '30';

	public function getPlatformCode() {
		return DADDYPAY_QRCODE_PAYMENT_API;
	}

	public function getPrefix() {
		return 'daddypay_qrcode';
	}

	public function getDepositMode() {
		return parent::DEPOSIT_MODE_QRCODE;
	}

	public function getNoteModel() {
		return parent::NOTE_MODEL_PLATFORM;
	}

	public function getNote($player_id, $direct_pay_extra_info) {
		return $this->getSystemInfo('daddypay_alipay_note');
	}

	protected function getBankId($direct_pay_extra_info) {
		# Only alipay is supported in this mode
		return self::BANK_ID_ALIPAY;
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	public function handlePaymentFormResponse($resp, $params) {
		if($this->getTerminal() == self::TERMINAL_MOBILE) {
			# 传送的URL可以自动或手动跳转打开手机支付宝APP
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_URL,
				'url' => $resp['break_url'],
			);
		} else {
			# 此URL为二维码图片地址
			return array(
				'success' => true,
				'type' => self::REDIRECT_TYPE_QRCODE,
				'url' => $resp['break_url'],
			);
		}
	}

    protected function configParams(&$params, $direct_pay_extra_info) {}
}
