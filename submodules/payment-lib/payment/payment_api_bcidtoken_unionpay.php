<?php
require_once dirname(__FILE__) . '/abstract_payment_api_easypays.php';

/** 
 *
 * bcidtoken
 * 
 * 
 * * 'BCIDTOKEN_UNIONPAY_PAYMENT_API', ID 5279
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.bcidtoken.com/get_qrcode_link
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bcidtoken_unionpay extends Abstract_payment_api_easypays {

	public function getPlatformCode() {
		return BCIDTOKEN_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'bcidtoken_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channel'] = "unionpay";
	}

	protected function processPaymentUrlForm($params) {

		return $this->processPaymentUrlFormRedirectUnion($params);
	}

	public function getPlayerInputInfo() {
        
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }





    public function processPaymentUrlFormRedirectUnion($params) {
        $response = $this->submitPostForm($this->getSystemInfo('url'), $params, false, $params['order_id']);
        $response = json_decode($response, true);
        $this->CI->utils->debug_log('=====================bcidtoken processPaymentUrlFormRedirectUnion response', $response);

        if(!empty($response['code']) && ($response['code'] == self::RETURN_SUCCESS_CODE)) {
                return array(
                    'success' => true,
                    'type' => self::REDIRECT_TYPE_URL,
                    'url' => $response['qrcode']
                );                
        }
        else {
            return array(
                'success' => false,
                'type' => self::REDIRECT_TYPE_ERROR, # will be redirected to a view for error display
                'message' => lang('Invalidte API response')
            );
        }
    }

}
