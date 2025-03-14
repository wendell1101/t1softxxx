<?php

require_once dirname(__FILE__) . '/abstract_payment_api_paysec.php';

Class Payment_api_paysec_banktransfer extends Abstract_payment_api_paysec {
	public $payType = 'BANKTRANS';

    public function getPrefix() {
        return 'paysec';
	}

    // It's execute in Abstract_payment_api initial function that provide ID of payment defined
    public function getPlatformCode() {
		return PAYSEC_BANKTRANSFER_PAYMENT_API;
	}

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['version']  = '3.0';
        $params['channelCode'] = self::FIELD_CHANNEL_CODE_BANKTRANSFER;
        if($this->getSystemInfo('use_usd_currency')) {
            $params['orderAmount'] = $this->convertAmountToCurrency($this->gameAmountToDBByCurrency($params['orderAmount'], $this->utils->getTodayForMysql(),'USD','CNY') );
        }
    }

    protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}

    public function getPlayerInputInfo() {
        $playerInputInfo = parent::getPlayerInputInfo();

        if($this->getSystemInfo('pass_player_info')) {
            $playerId = $this->CI->session->userdata('player_id');
            $playerDetails = $this->getPlayerDetails($playerId);

            $firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : 'no firstName';
            $lastname =  (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))  ? $playerDetails[0]['lastName']  : 'no lastName';
            $emailAddr = (isset($playerDetails[0]) && !empty($playerDetails[0]['email']))     ? $playerDetails[0]['email']     : 'no email';
            $contactNumber = (isset($playerDetails[0]) && !empty($playerDetails[0]['contactNumber'])) ? $playerDetails[0]['contactNumber']: 'no contactNumber';

            array_splice($playerInputInfo, 1, 0, array(array('name' => 'player_bank_num',    'type' => 'number', 'label_lang' => 'cashier.player.bank_num')));
            $playerInputInfo[] = array('name' => 'player_email',       'type' => 'hidden', 'label_lang' => 'cashier.player.email', 'value' => $emailAddr);
            $playerInputInfo[] = array('name' => 'player_phone',       'type' => 'hidden', 'label_lang' => 'cashier.player.phone', 'value' => $contactNumber);
            $playerInputInfo[] = array('name' => 'player_first_name',  'type' => 'hidden', 'label_lang' => 'cashier.player.first_name', 'value' => $firstname);
            $playerInputInfo[] = array('name' => 'player_last_name',   'type' => 'hidden', 'label_lang' => 'cashier.player.last_name', 'value' => $lastname);
        }

        return $playerInputInfo;
    }
}