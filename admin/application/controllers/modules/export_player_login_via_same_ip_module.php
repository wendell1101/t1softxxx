<?php
trait export_player_login_via_same_ip_module {


    public function reportManyPlayerLoginViaSameIpLogsListViaQueue(){
        $this->load->library(array('permissions'));
		$this->permissions->setPermissions();

        $is_export = true;
		$permissions=$this->getContactPermissions();
		// $permissions['player_cpf_number'] = $this->permissions->checkPermissions('player_cpf_number');
        // $funcName='player_list_reports';
		$funcName='exportPlayerLoginViaSameIpLogsList'; // will call export_player_login_via_same_ip_module::exportPlayerLoginViaSameIpLogsList()
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';

		$extra_params=[self::HTTP_REQEUST_PARAM, $permissions, $is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}
    }

    public function reportManyPlayerLoginViaSameIpLogsListdDirectly() {
        $this->load->library(array('permissions'));
		$this->permissions->setPermissions();

        $this->load->model(array('report_model', 'player_login_via_same_ip_logs'));

        $is_export = false; // false, for utils->create_excel()
        $request = $this->input->post();
        $permissions=$this->getContactPermissions();
$this->utils->debug_log('reportManyPlayerLoginViaSameIpLogsListdDirectly.request:',$request);
        $result = $this->player_login_via_same_ip_logs->dataTablesList($request, $permissions, $is_export);
$this->utils->debug_log('reportManyPlayerLoginViaSameIpLogsListdDirectly.result:',$result);
        foreach ($result['data'] as &$row) {
            $row = array_map('strip_tags', $row);
        }

        $d = new DateTime();
        $link = $this->utils->create_excel($result, 'player_login_via_same_ip_logs_reports_' . $d->format('Y_m_d_H_i_s') . '_' . rand(1, 9999), TRUE);

        //return file link
        $rlt = array('success' => true, 'link' => $link);
        $this->returnJsonResult($rlt);
    }
}