<?php
require_once dirname(__FILE__) . '/abstract_notify_api.php';

/**
 * Dummy API, always returns success and write to logs. For testing purpose.
 *
 * To use this, go to admin's config_local.php and add:
 * $config['voice_api'] = 'voice_api_dummy';
 */
class Notify_api_dummy extends Abstract_notify_api {
	# Overwrite the main function to directly return success
	const NOTIFY_FAIL_NUMBER = '00000000';

	public function getUrl() {
		return "dummy-url";
	}

	public function getFields($mobile, $content, $dialingCode) {
		return array();
	}

	public function getErrorMsg($returnQueryString) {
		return "Dummy notify api error";
	}

	public function isSuccess($returnQueryString) {
        $this->CI->utils->debug_log("isSuccess() through the Dummy notify API.func_get_args:", func_get_args() );

        return $returnQueryString == (self::NOTIFY_FAIL_NUMBER) ? false : true;
	}

	public function getBalanceString() {
		return 'Dummy API balance';
	}

}
