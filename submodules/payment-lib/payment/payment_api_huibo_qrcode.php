<?php
require_once dirname(__FILE__) . '/abstract_payment_api_huibo.php';

/**
 * HuiBo 汇博支付
 *
 * * HUIBO_ALIPAY_PAYMENT_API, ID: 118
 * * HUIBO_WEIXIN_PAYMENT_API, ID: 119
 *
 * Required Fields:
 *
 * * URL
 * * Account
 * * Extra Info
 *
 *
 * Field Values:
 *
 * * URL: http://47.90.92.130:9899/HBConn/online
 * * Account: ## Merchant Account ##
 * * Extra Info
 * > {
 * >     "huibo_api_url" : "http://47.90.92.130:9899/HBConn/LFT",
 * >     "huibo_priv_key": "## path to merchant's private key ##",
 * >     "huibo_pub_key" : "## path to merchant's public key ##",
 * >     "huibo_api_pub_key" : "## path to API's public key ##"
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
abstract class Payment_api_huibo_qrcode extends Abstract_payment_api_huibo {
	protected function getOrderCode() {
		return 'hb_Pay';
	}

	# Hide bank list dropdown
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function handlePaymentUrlForm($params, $context) {
		$priKey = $this->getPrivKey();
		$pubKey=$this->getAPIPubKey();

		$response = $this->postForm($this->getSystemInfo('huibo_api_url'), $params);
		$response = json_decode($response);
		$odata = $response->data;
		$signature = $response->signature;
		$decrypted = '';
		$data = str_split(base64_decode($odata), 256);
		foreach($data as $chunk) {
			$partial = '';
			$decryptionOK = openssl_private_decrypt($chunk, $partial, $priKey);
			if($decryptionOK === false){
				$this->CI->utils->error_log("api response decrypt failure");
				return array(
					'success' => false,
					'type' => self::REDIRECT_TYPE_ERROR,
					'message' => lang('Invalid API response'),
				);
			}
			$decrypted .= $partial;
		}

		$decrypted = base64_decode($decrypted);
		$response = json_decode($decrypted);
		$signature = base64_decode($signature);
		if(openssl_verify($response->msg,$signature,$pubKey,OPENSSL_ALGO_MD5)) {
			$response = json_decode($response->msg);

			$this->CI->utils->debug_log('response', $response);

			if($context['orderSecureId'] == $response->orderId && $response->respCode == "000000"){
				return array(
					'success' => true,
					'type' => self::REDIRECT_TYPE_QRCODE,
					'url' => $response->QRcodeURL,
				);
			} else {
				return array(
					'success' => false,
					'type' => self::REDIRECT_TYPE_ERROR,
					'message' => $this->getErrorMsg($response->respCode)
				);
			}
		} else {
			return array(
				'success' => false,
				'type' => self::REDIRECT_TYPE_ERROR,
				'message' => lang('Invalidte API response'),
			);
		}
	}
}