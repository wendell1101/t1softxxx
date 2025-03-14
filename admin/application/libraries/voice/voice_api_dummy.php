<?php
require_once dirname(__FILE__) . '/abstract_voice_api.php';

/**
 * Dummy API, always returns success and write to logs. For testing purpose.
 *
 * To use this, go to admin's config_local.php and add:
 * $config['voice_api'] = 'voice_api_dummy';
 */
class Voice_api_dummy extends Abstract_voice_api {
	# Overwrite the main function to directly return success
	const VOICE_FAIL_NUMBER = '00000000';

	public function send($mobile, $content, $dialingCode = NULL){

		$this->CI->utils->debug_log("Sending voice through the Dummy voice API. Number, Content:", $mobile, $content);

		$this->CI->load->model(array('response_result'));
		$player_id = NULL;
		$resultAll['type']     = 'voice';
        $resultAll['url']      = $this->getUrl();
		$resultAll['params']   = $content;
		$resultAll['content']  = 'dummy-response dialingCode : '.$dialingCode.', mobile : '.$mobile;
		$flag = $this->isSuccess($mobile) ? Response_result::FLAG_NORMAL:Response_result::FLAG_ERROR;

		$response_result_id = $this->CI->response_result->saveResponseResult($this->getPlatformCode(), $flag,
			self::VOICE_API, self::VOICE_API, json_encode($resultAll), 200, 'dummy', '',
			array('player_id' => $player_id, 'related_id3' => $mobile));


		return $mobile ;
	}

	public function getUrl() {
		return "dummy-url";
	}

	public function getFields($mobile, $content, $dialingCode) {
		return array();
	}

	public function getErrorMsg($returnQueryString) {
		return "Dummy voice api error";
	}

	public function isSuccess($returnQueryString) {
			return $returnQueryString == (self::VOICE_FAIL_NUMBER) ? false : true;
	}

	public function getBalanceString() {
		return 'Dummy API balance';
	}

}
