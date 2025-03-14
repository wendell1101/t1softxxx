<?php

/**
 *
 * api for transfer balance
 */
trait player_login_via_same_ip_logs_module {

    /**
     * api/player_login_via_same_ip_list
     *
     * @return void
     */
    public function player_login_via_same_ip_list(){
        $this->load->model(['player_login_via_same_ip_logs']);
        $this->load->library(array('permissions'));
		$this->permissions->setPermissions();

        //get post data
        $request = $this->input->post();

        $permissions = $this->getContactPermissions();
		// $permissions['player_cpf_number'] = $this->permissions->checkPermissions('player_cpf_number');
		$is_export = false;
        $result = $this->player_login_via_same_ip_logs->dataTablesList($request, $permissions, $is_export);
        return $this->returnJsonResult($result);

		// return $this->reportManyPlayerLoginViaSameIpLogsList($request, $permissions, $is_export);
    }
    // /**
    //  * api/reportManyPlayerLoginViaSameIpLogsList
    //  *
    //  * @return void
    //  */
    // public function reportManyPlayerLoginViaSameIpLogsList($request, $permissions, $is_export = false,$csv_filename=null){
    //     $this->load->model(['player_login_via_same_ip_logs']);

    //     // $request = $this->input->post();
    //     // $is_export = false;
    //     // $permissions=$this->getContactPermissions();

    //     $result = $this->player_login_via_same_ip_logs->dataTablesList($request, $permissions, $is_export);
    //     $this->returnJsonResult($result);
    // }


    /**
	 * Update the detected_tag_id in operator_setting from "report_management/viewPlayerLoginViaSameIp".
	 *
     * the uri, api/updateDetectedTagIdInViewPlayerLoginViaSameIp
     *
	 * @return string The json string, the formats as following,
     * - $jsonResult['bool'] boolean
     * - $jsonResult['msg'] string
     * - $jsonResult['operatorSetting'] array
	 */
	public function updateDetectedTagIdInViewPlayerLoginViaSameIp(){
        $this->load->library(array('permissions'));
        if (!$this->permissions->checkPermissions('setup_tag_of_players_login_via_same_ip')) {
			return $this->error_access();
		}

		$this->load->model(['operatorglobalsettings', 'player_model', 'player_login_via_same_ip_logs']);
		$jsonResult = [];
        $detected_tag_id_key = Player_login_via_same_ip_logs::_operator_setting_name4detected_tag_id;
		$defaultTagId = $this->player_login_via_same_ip_logs->getTagIdByTagNameDetectedOfConfig();
        $_request = $this->safeLoadParams(array(
			$detected_tag_id_key => $defaultTagId,
		));

		$detected_tag_id = $_request[$detected_tag_id_key];
		$tagName = $this->player_model->getTagNameByTagId($detected_tag_id);
		if( ! empty($tagName) ){
            if($this->operatorglobalsettings->existsSetting($detected_tag_id_key)) {
                $this->operatorglobalsettings->putSetting($detected_tag_id_key, $detected_tag_id, 'value');
            } else {
                $this->operatorglobalsettings->insertSetting($detected_tag_id_key, $detected_tag_id, 'value');
            }
            $note = 'Auto add tag,"%s" to players suspected of being hacked';
            $note = sprintf($note, $tagName);
            $this->operatorglobalsettings->putSetting($detected_tag_id_key, $note, 'note');


			$operatorSetting = [];
			$operatorSetting['value'] = $detected_tag_id;
			$operatorSetting['name'] = $detected_tag_id_key;
			$this->operatorglobalsettings->setOperatorGlobalSetting($operatorSetting);
			$jsonResult['operatorSetting'] = $operatorSetting;
			$jsonResult['msg'] = lang('Update completed.');
			$jsonResult['bool'] = true;
		}else{
			$jsonResult['msg'] = lang('The tag_id does not exist.');
			$jsonResult['bool'] = false;
		}
		$jsonResult['currect_tag_id'] = $this->operatorglobalsettings->getSettingValueWithoutCache($detected_tag_id_key);

		return $this->returnJsonResult($jsonResult);
	}// EOF updateDetectedTagIdInViewPlayerLoginViaSameIp

} // EOF trait withdrawal_risk_api_module
