<?php

/**
 * Wrapper class used to send notify through API.
 *
 * notify API configuration sample is defined in config_default_common.php. Each API should have
 * their own parameters defined in config_secret_local.php.
 *
 * Check the documentation of each notify API implementation class to see what parameters are expected.
 */
class Notify_sender {
	private $lastError;
	private $CI;

	public function __construct() {
		$this->CI = &get_instance();
	}

    public function load_notify_in_app($useNotifyApiName = null){
        if( ! empty($useNotifyApiName) ){
            # Use the specified API to send notify
            $apiName = $useNotifyApiName;
        }else{
            $apiName = $this->getNotifyApiName();
        }

        if( ! empty($apiName) ) {
            $this->CI->load->library('notify_in_app/'.$apiName);
		    $notifyApi = $this->CI->$apiName;
        }else{
            $this->CI->utils->error_log("No notify API configured.");
            $this->lastError = lang("No notify API configured.");
            $notifyApi = false;
        }
        return $notifyApi;
    }
	/**
	 * [send description]
	 * @param  [string] $recipientNotifyToken tokenA|tokenB|tokenC...|tokenN
	 * @param  [string] $notifyContent
	 * @param  [string] $useNotifyApiName       [eg. notify_api_<name>]
	 * @return [boolean]
	 */
	public function send($recipientNotifyToken, $notifyContent, $useNotifyApiName = null, &$isSuccess = null, &$lastError = null) {

        $notify_data = $notifyContent['notify_data'];
        $mode = $notifyContent['mode'];
        $player_id = $notifyContent['player_id'];

        $notifyApi = $this->load_notify_in_app($useNotifyApiName);

        $sendResult = $notifyApi->send($recipientNotifyToken, $notify_data, $mode, $player_id);
        $this->CI->utils->debug_log("Sending notify to ", $recipientNotifyToken
                                    , "Sending notify content ", var_export($notifyContent, true)
                                    , "result is ", $sendResult );

        $isSuccess = $notifyApi->isSuccess($sendResult);
		if(!$isSuccess){
			$this->lastError = $notifyApi->getErrorMsg($sendResult);
		}
        $lastError = $this->lastError;

		return $isSuccess;
	}

	public function getLastError() {
		return $this->lastError;
	}

	# Returns an array of configured notify API's balances
	public function getNotifyBalances() { //ref.by Sms_sender::getSmsBalances()
		$apiNames = $this->CI->config->item('notify_api');
		if(!is_array($apiNames)) {
			$apiNames = array($apiNames);
		}

		$apiBalances = array();
		foreach($apiNames as $apiName) {
			$this->CI->load->library('notify/'.$apiName);
			$notifyApi = $this->CI->$apiName;
			$balanceString = $notifyApi->getBalanceString();
			if(!empty($balanceString)) {
				$apiBalances[$apiName] = $balanceString;
			}
		}
		return $apiBalances;
	}

	public function getNotifyApiName() {
        $settings = $this->CI->operatorglobalsettings->getSystemSetting('notify_api_list');
		$value = $this->CI->operatorglobalsettings->getSettingValueWithoutCache('notify_api_list');
		$this->CI->utils->debug_log("NOTIFY api list value", $value);
		if(!is_null($value) && !empty($settings)){
			$this->CI->utils->debug_log("NOTIFY api list", $settings);
			$apis = array_values($settings['params']['list']);
			$apiName = isset($settings['params']['list'][$value]) ? $settings['params']['list'][$value] : array_shift($apis);
			$this->CI->utils->debug_log("System Setting NOTIFY API", $apiName);
		}
		else{
			# load the API instance based on configuration (defined in config_secret_local)
			$apiNames = $this->CI->config->item('notify_api');
			if(is_array($apiNames) && !empty($apiNames) ) {
				$apiIndex = array_rand($apiNames); # pick a random API to send
				$apiName = $apiNames[$apiIndex];
				$this->CI->utils->debug_log("Random NOTIFY API", $apiName);
			} else if( !empty($apiNames) ) {
				$apiName = $apiNames;
				$this->CI->utils->debug_log("Fixed NOTIFY API", $apiName);
			}else{
                $apiName = '';
                $this->CI->utils->debug_log("No NOTIFY API");
            }
		}
		return $apiName;
	}
}
