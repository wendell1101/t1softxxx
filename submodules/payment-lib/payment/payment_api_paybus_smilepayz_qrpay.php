<?php
require_once dirname(__FILE__) . '/abstract_payment_api_paybus.php';

/**
 * paybus
 *
 * * PAYBUS_SMILEPAYZ_QRPAY_PAYMENT_API
 *
 * Field Values:
 * * URL: https://pay2-open.kyriandev.com/payment/deposit
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2024 tot
 */
class Payment_api_paybus_smilepayz_qrpay extends Abstract_payment_api_paybus {
    const CHANNEL_SMILEPAYZ_QRPAY = 'smilepayz.QRPAY';
    private $playerId;

    public function getPlatformCode() {
        return PAYBUS_SMILEPAYZ_QRPAY_PAYMENT_API;
    }

    public function getPrefix() {
        return 'paybus_smilepayz_qrpay';
    }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $channelInput = [
            self::CHANNEL_SMILEPAYZ_QRPAY => [
                "purpose" => "deposit",
            ],
        ];
        
        if ($this->getSystemInfo('accountNumberUse') && !empty($direct_pay_extra_info)) {
            $payerAccountNo = '';
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo['field_required_player_bank_list'])) {
                $memberBank = explode('-', $extraInfo['field_required_player_bank_list']);
                $payerAccountNo = $memberBank[1];
            }
            if (!empty($extraInfo['player_account_number'])) {
                $payerAccountNo = $extraInfo['player_account_number'];
            }
            $channelInput[self::CHANNEL_SMILEPAYZ_QRPAY]['payerAccountNo'] = $payerAccountNo;
        }
        
        $params['channel_input'] = json_decode(json_encode($channelInput));
    }

    # Hide bank list dropdown
    public function getPlayerInputInfo() {

        $getPlayerInputInfo = array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09')
	   	);

		if ($this->getSystemInfo('accountNumberUse')) {
            $bankAccount = $this->getBankAccount();
            $newArray = array('name' => 'field_required_player_bank_list', 'type' => 'player_bank_list', 'select_lang'=> 'Please Select Bank', 'label_lang' => 'cashier.player.bank_num', 'list' => $bankAccount, 'default_option_value' => $bankAccount[0]['bankAccountNumber'], 'disabled_btn' => 'disabled');
			array_unshift($getPlayerInputInfo, $newArray);
		}
		return $getPlayerInputInfo;
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
    protected function getBankAccount() {
        $bankAccountFullName = '';
        $bankAccountNumber = '';
        $this->CI->load->model(['playerbankdetails']);
        $default_bank_details = $this->CI->playerbankdetails->getDefaultBankDetail($this->playerId);

        if (count($default_bank_details['deposit']) > 0) {
            $bankAccountNumber = $default_bank_details['deposit'][0]['bankAccountNumber'];
            $bankAccountFullName = $default_bank_details['deposit'][0]['bankAccountFullName'];
        }

        $banklist[] = array(
            'bankName' => $bankAccountFullName,
            'bankAccountNumber' => $bankAccountNumber,
            'bankCode' => $bankAccountNumber,
        );
        return $banklist;
    }

    public function initPlayerPaymentInfo($player_id) {
		$this->playerId = $player_id;
	}
}