<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yeeyk.php';

/**
 * 易游酷 YeeYK
 * http://www.yeeyk.com/
 *
 * YEEYK_PAYMENT_API, ID: 169
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.shoumipay.com/gatepay.do
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yeeyk extends Abstract_payment_api_yeeyk {

	public function getPlatformCode() {
		return YEEYK_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yeeyk';
	}

	public function getChannelId() {
		return parent::CHANNEL_BANK;
	}

	public function getPlayerInputInfo() {
		return array(
			array('name' => 'cardCode', 'type' => 'singlelist', 'label_lang' => 'pay.loadcard.type',
				'list' => $this->getCardList(), 'list_info' => $this->getCardInfo()),
			array('name' => 'cardAmt', 'type' => 'float_amount', 'size' => 50, 'label_lang' => '卡額'),
			array('name' => 'cardNo', 'type' => 'text', 'size' => 50, 'label_lang' => 'pay.loadcard.number'),
			array('name' => 'cardPwd', 'type' => 'text', 'size' => 50, 'label_lang' => 'pay.loadcard.password'),
		);
	}

	public function getCardList() {
		$list = array();
		$cardListInfo = $this->getCardListInfo();
		foreach ($cardListInfo as $cardInfo) {
			$list[$cardInfo['value']] = $cardInfo['label'];
		}
		return $list;
	}

	public function getCardInfo() {
		$list = array();
		$cardListInfo = $this->getCardListInfo();
		foreach ($cardListInfo as $cardInfo) {
			$list[$cardInfo['value']] = $cardInfo['info'];
		}
		return $list;
	}

	public function getCardListInfo() {
		$cardinfo = $this->getSystemInfo('cardinfo');
		$result = array();
		if (!empty($cardinfo)) {
			foreach($cardinfo as $card) {
				$result[] = array(
					'label' => $card['cardName'],
					'value' => $card['cardCode'],
					'info' => $card['cardPercent'] . '%'
				);
			}
			return $result;
		} else {
			return array(
				array(
					'label' => '移动充值卡', 'value' => 'MOBILE', 'info' => '5%'
				),
				array(
					'label' => '联通卡', 'value' => 'UNICOM', 'info' => '5%'
				),
				array(
					'label' => '电信卡', 'value' => 'TELECOM', 'info' => '5%'
				),
				// array(
				// 	'label' => '易充卡', 'value' => 'YC'
				// ),
				array(
					'label' => '32 一卡通', 'value' => 'CARDTW', 'info' => '17%'
				),
				array(
					'label' => '久游一卡通', 'value' => 'JY', 'info' => '20%'
				),
				array(
					'label' => '骏网一卡通', 'value' => 'JW', 'info' => '17%'
				),
				array(
					'label' => 'Q 币卡', 'value' => 'QQ', 'info' => '15%'
				),
				array(
					'label' => '盛大卡', 'value' => 'SD', 'info' => '15%'
				),
				array(
					'label' => '搜狐一卡通', 'value' => 'SH', 'info' => '16%'
				),
				array(
					'label' => '天宏一卡通', 'value' => 'TH', 'info' => '17%'
				),
				// array(
				// 	'label' => '天下通一卡通', 'value' => 'TX'
				// ),
				array(
					'label' => '完美一卡通', 'value' => 'WM', 'info' => '15%'
				),
				array(
					'label' => '网易一卡通', 'value' => 'WY', 'info' => '15%'
				),
				array(
					'label' => '征途卡', 'value' => 'ZT', 'info' => '15%'
				),
				array(
					'label' => '纵游一卡通', 'value' => 'ZY', 'info' => '17%'
				),
				// array(
				// 	'label' => '易充天宏卡', 'value' => 'YCTH'
				// ),
				// array(
				// 	'label' => '易充纵游卡', 'value' => 'YCZY'
				// ),
				// array(
				// 	'label' => '易充 32 卡', 'value' => 'YCCARDTW'
				// ),
				// array(
				// 	'label' => '骏卡话费通', 'value' => 'JKHFT'
				// ),
				// array(
				// 	'label' => '纵游全网通', 'value' => 'ZYZC'
				// ),
			);
		}
	}

	public function getAmount($fields) {
		return isset($fields['cardAmt']) ? $fields['cardAmt'] : null;
	}
}
