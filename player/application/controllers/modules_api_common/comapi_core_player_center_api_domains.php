<?php

/**
 * uri: /player-resource
 */

trait comapi_core_player_center_api_domains {

	public function apiDomains(){

		$api_key = $this->input->post('api_key');
		if (!$this->__checkKey($api_key)) { return; }


		// $this->__returnApiResponse($success, $code, $message, $list);

		try {

			$this->load->model(['player_center_api_domains']);

			$result = $this->player_center_api_domains->queryPlayerCenterApiDomainList();
			$list = array_column($result, 'domain');
			$message = lang('Successfully got the list of domains');
			// $success = TRUE;
			// $code = Api_common::CODE_SUCCESS;

			$ret =  [
                'success'   => true ,
                'code'      => Api_common::CODE_SUCCESS ,
                'mesg'      => $message ,
                'result'    => $list
            ];
		} catch (Exception $ex) {

			$this->utils->debug_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

			$ret = [
				'success'   => false,
				'code'      => $ex->getCode(),
				'mesg'      => $ex->getMessage(),
				'result'    => null
			];
		}
		finally {
			$this->returnApiResponseByArray($ret, 'return_empty_array');
		}
		return;
	}

}
