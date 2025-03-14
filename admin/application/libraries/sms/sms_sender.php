<?php

/**
 * Wrapper class used to send SMS through API.
 *
 * SMS API configuration sample is defined in config_default_common.php. Each API should have
 * their own parameters defined in config_secret_local.php.
 *
 * Check the documentation of each SMS API implementation class to see what parameters are expected.
 */
class Sms_sender {
	private $lastError;
	private $CI;

	public function __construct() {
		$this->CI = &get_instance();
	}

	/**
	 * [send description]
	 * @param  [string] $recipientNumber
	 * @param  [string] $smsContent
	 * @param  [string] $useSmsApiName       [eg. sms_api_<name>]
	 * @return [boolean]
	 */
	public function send($recipientNumber, $smsContent, $useSmsApiName = null) {

		$use_new_sms_api_setting = $this->CI->utils->getConfig('use_new_sms_api_setting');
		if(!$useSmsApiName) {
			$apiName = $use_new_sms_api_setting ? false : $this->getSmsApiName();
			if(empty($apiName)) {
				$this->CI->utils->error_log("No SMS API configured.");
				$this->lastError = lang("No SMS API configured.");
				return false;
			}
		} else {
			# Use the specified API to send sms
			$apiName = $useSmsApiName;
		}

		$this->CI->load->library('sms/'.$apiName);
		$smsApi = $this->CI->$apiName;

		if (preg_match("/\|/", $recipientNumber)) {
			$convertRecipientNumber = explode('|',$recipientNumber);
            $dialingCode = $convertRecipientNumber[0];
            $mobileNumber = $convertRecipientNumber[1];
		}
		else{
			$mobileNumber = $recipientNumber;
			$dialingCode = null;
		}

		$sendResult = $smsApi->send($mobileNumber, $smsContent, $dialingCode);
		$this->CI->utils->debug_log("Sending sms to [$recipientNumber], content before signature is [$smsContent], result is [$sendResult]");
		$success = $smsApi->isSuccess($sendResult);

		if(!$success){
			$this->lastError = $smsApi->getErrorMsg($sendResult);
		}

		return $success;
	}

	public function getLastError() {
		return $this->lastError;
	}

	# Returns an array of configured SMS API's balances
	public function getSmsBalances() {
		$apiNames = $this->CI->config->item('sms_api');
		if(!is_array($apiNames)) {
			$apiNames = array($apiNames);
		}

		$apiBalances = array();
		foreach($apiNames as $apiName) {
			$this->CI->load->library('sms/'.$apiName);
			$smsApi = $this->CI->$apiName;
			$balanceString = $smsApi->getBalanceString();
			if(!empty($balanceString)) {
				$apiBalances[$apiName] = $balanceString;
			}
		}
		return $apiBalances;
	}

	public function getSmsApiName() {
        $settings = $this->CI->operatorglobalsettings->getSystemSetting('sms_api_list');
		$value = $this->CI->operatorglobalsettings->getSettingValueWithoutCache('sms_api_list');
		$this->CI->utils->debug_log("SMS api list value", $value);
		if(!is_null($value) && !empty($settings)){
			$this->CI->utils->debug_log("SMS api list", $settings);
			$apis = array_values($settings['params']['list']);
			$apiName = isset($settings['params']['list'][$value]) ? $settings['params']['list'][$value] : array_shift($apis);
			$this->CI->utils->debug_log("System Setting SMS API", $apiName);
		}
		else{
			# load the API instance based on configuration (defined in config_secret_local)
			$apiNames = $this->CI->config->item('sms_api');
			if(is_array($apiNames)) {
				$apiIndex = array_rand($apiNames); # pick a random API to send
				$apiName = $apiNames[$apiIndex];
				$this->CI->utils->debug_log("Random SMS API", $apiName);
			} else {
				$apiName = $apiNames;
				$this->CI->utils->debug_log("Fixed SMS API", $apiName);
			}
		}
		return $apiName;
	}

	public function getSmsApiNameNew($mobileNumber, $usageType, $playerId = null){
		$apiName = false;
		$msg = lang('No sms api available');
		$this->CI->load->model(array('sms_verification'));

		$this->CI->utils->debug_log(__METHOD__,"SMS api usageType", $usageType, $mobileNumber);
		if (empty($mobileNumber) || $mobileNumber == 'null' || $mobileNumber == null) {
			return $apiName;
		}

		if ($usageType == sms_verification::USAGE_DEFAULT) {
			return $apiName;
		}

		$sms_api_setting = $this->CI->operatorglobalsettings->getSettingJson($usageType);
		$rotation_setting = $this->CI->operatorglobalsettings->getSettingJson('sms_api_rotation_order_settings');
		$condition = $this->CI->config->item('use_new_sms_api_setting');
		$condition['mobileNumber'] = $mobileNumber;
		$condition['playerId'] = $playerId;
		$rotation_api_list = $sms_api_setting['rotation'];
		$random = $rotation_setting['random'];
		$rotationOrder = $rotation_setting['rotationOrder'];

		$this->CI->utils->debug_log(__METHOD__,"SMS api setting", $sms_api_setting, $rotation_setting, $condition);

		if ($sms_api_setting['enabled']) {
			if ($sms_api_setting['mode'] == 'single_mode') {
				if (!empty($sms_api_setting['single'])) {
					$msg = lang('Get random sms api success');
					$apiName = $sms_api_setting['single'];
					$this->CI->utils->debug_log(__METHOD__, $msg, $apiName, $random);
				}
			}elseif ($sms_api_setting['mode'] == 'rotation_mode') {

				if (empty($rotation_api_list)) {
					$msg = lang('Rotation sms api setting not yet set');
					$this->CI->utils->debug_log(__METHOD__, $msg, $rotation_api_list);
					return array($apiName, $msg);
				}

				$availableRotation = $this->availableRotationApiList($condition, $rotation_api_list);

				$this->CI->utils->debug_log(__METHOD__, 'availableRotation', $availableRotation, $condition);

				if (empty($availableRotation)) {
					$msg = lang('You are sending SMS too frequently. Please wait.');
					$this->CI->utils->debug_log(__METHOD__, 'availableRotation is empty', $msg, $availableRotation);
					return array($apiName, $msg);
				}


				if ($random) {
					if(!empty($availableRotation)) {
						$apiIndex = array_rand($availableRotation); # pick a random API to send
						$apiName = $availableRotation[$apiIndex];
						$msg = lang('Get random sms api success');
						$this->CI->utils->debug_log(__METHOD__, $msg, $apiName, $random);
					}else{
						$msg = lang('Get random sms api failed');
						$this->CI->utils->debug_log(__METHOD__, $msg, $apiName, $random);
					}
				}else{
					foreach ($rotationOrder as $index => $api) {
						if (in_array($api,$availableRotation)) {
							$apiName = $api;
							$msg = lang('Get rotationOrder sms api success');
							break;
						}
					}
					$this->CI->utils->debug_log(__METHOD__, $msg, $apiName);
				}
			}
			$this->CI->utils->debug_log(__METHOD__, $msg , $apiName, $random);
		}
		return array($apiName, $msg);
	}

	public function availableRotationApiList($condition, $rotation_api_list){
		$this->CI->load->model(array('sms_verification'));
		$availableRotation = [];
		foreach ($rotation_api_list as $apiName) {
			$res = $this->CI->sms_verification->getAvailableRotationSmsApi($condition, $apiName);
			if (empty($res)) {
				$availableRotation[] = $apiName;
			}
		}
		return $availableRotation;
	}
}
