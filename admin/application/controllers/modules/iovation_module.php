<?php

/**
 * Class iovation_module
 *
 * General behaviors include :
 *
 * * Sends data to iovation fraud assessment
 *
 * @category Player Management
 * @version 1
 * @copyright 2020-2022 tot
 */
trait iovation_module {


	public function register_to_iovation_by_promotion() {
        $playerId = $this->authentication->getPlayerId();

		$this->CI->load->library(['iovation_lib']);
		$isIovationEnabled = $this->utils->isOperatorSettingItemEnabled('iovation_api_list', 'enabled_iovation_in_promotion') && $this->CI->iovation_lib->isReady;	

        if($playerId && $isIovationEnabled){			
			$ioBlackBox = $this->input->post('ioBlackBox');
			$promoCmsSettingId = $this->input->post('promoCmsSettingId');
			if(empty($ioBlackBox)){
				$jsonResult = ['success' => false, 'message' => lang('Error encountered! Empty Blackbox.') ];
				return $this->returnJsonResult($jsonResult);
			}

			$iovationparams = [
				'player_id'=>$playerId,
				'ip'=>$this->utils->getIP(),
				'blackbox'=>$ioBlackBox,
				'promo_cms_setting_id'=>$promoCmsSettingId,
			];
        	$iovationResponse = $this->CI->iovation_lib->registerPromotionToIovation($iovationparams);
            $this->utils->debug_log('Post registration Iovation response', $iovationResponse);
            $jsonResult = ['success' => $iovationResponse['success'], 'message' => $iovationResponse['msg'] ];
        }else{
            $jsonResult = ['success' => false, 'message' => lang('Error encountered!') ];
        }
		return $this->returnJsonResult($jsonResult);
	} // EOF function register_to_iovation(...

	
}
////END OF FILE/////////