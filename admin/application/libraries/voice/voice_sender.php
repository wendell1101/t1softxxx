<?php

/**
 * Wrapper class used to send voice through API.
 *
 * voice API configuration sample is defined in config_default_common.php. Each API should have
 * their own parameters defined in config_secret_local.php.
 *
 * Check the documentation of each voice API implementation class to see what parameters are expected.
 */
class Voice_sender {
	private $lastError;
	private $CI;

	public function __construct() {
		$this->CI = &get_instance();
	}

	/**
	 * [send description]
	 * @param  [string] $recipientNumber
	 * @param  [string] $voiceContent
	 * @param  [string] $usevoiceApiName       [eg. voice_api_<name>]
	 * @return [boolean]
	 */
	public function send($recipientNumber, $voiceContent, $usevoiceApiName = null) {
		if(!$usevoiceApiName) {
			$apiName = $this->getvoiceApiName();
			if(empty($apiName)) {
				$this->CI->utils->error_log("No voice API configured.");
				$this->lastError = lang("No voice API configured.");
				return false;
			}
		} else {
			# Use the specified API to send voice
			$apiName = $usevoiceApiName;
		}

		$this->CI->load->library('voice/'.$apiName);
		$voiceApi = $this->CI->$apiName;

		if (preg_match("/\|/", $recipientNumber)) {
			$convertRecipientNumber = explode('|',$recipientNumber);
            $dialingCode = $convertRecipientNumber[0];
            $mobileNumber = $convertRecipientNumber[1];
		}
		else{
			$mobileNumber = $recipientNumber;
			$dialingCode = null;
		}

		$sendResult = $voiceApi->send($mobileNumber, $voiceContent, $dialingCode);
		$this->CI->utils->debug_log("Sending voice to [$recipientNumber], content before signature is [$voiceContent], result is [$sendResult]");
		$success = $voiceApi->isSuccess($sendResult);

		if(!$success){
			$this->lastError = $voiceApi->getErrorMsg($sendResult);
		}
		return $success;
	}

	public function getLastError() {
		return $this->lastError;
	}

	# Returns an array of configured voice API's balances
	public function getvoiceBalances() {
		$apiNames = $this->CI->config->item('voice_api');
		if(!is_array($apiNames)) {
			$apiNames = array($apiNames);
		}

		$apiBalances = array();
		foreach($apiNames as $apiName) {
			$this->CI->load->library('voice/'.$apiName);
			$voiceApi = $this->CI->$apiName;
			$balanceString = $voiceApi->getBalanceString();
			if(!empty($balanceString)) {
				$apiBalances[$apiName] = $balanceString;
			}
		}
		return $apiBalances;
	}

	public function getvoiceApiName() {
        $settings = $this->CI->operatorglobalsettings->getSystemSetting('voice_api_list');
		$value = $this->CI->operatorglobalsettings->getSettingValueWithoutCache('voice_api_list');
		$this->CI->utils->debug_log("VOICE api list value", $value);
		if(!is_null($value) && !empty($settings)){
			$this->CI->utils->debug_log("VOICE api list", $settings);
			$apis = array_values($settings['params']['list']);
			$apiName = isset($settings['params']['list'][$value]) ? $settings['params']['list'][$value] : array_shift($apis);
			$this->CI->utils->debug_log("System Setting VOICE API", $apiName);
		}
		else{
			# load the API instance based on configuration (defined in config_secret_local)
			$apiNames = $this->CI->config->item('voice_api');
			if(is_array($apiNames)) {
				$apiIndex = array_rand($apiNames); # pick a random API to send
				$apiName = $apiNames[$apiIndex];
				$this->CI->utils->debug_log("Random VOICE API", $apiName);
			} else {
				$apiName = $apiNames;
				$this->CI->utils->debug_log("Fixed VOICE API", $apiName);
			}
		}
		return $apiName;
	}
}
