<?php

trait export_player_basic_amount_list_module {

    public function adjusted_deposits_game_totals_via_queue() {
        $this->load->library(array('permissions'));
		$this->permissions->setPermissions();


        $funcName='export_player_basic_amount_list'; // will call report_module_player_basic_amount_list::export_player_basic_amount_list()
         // exportPlayerLoginViaSameIpLogsList
		$callerType=Queue_result::CALLER_TYPE_SYSTEM;
		$caller=0;
		$state='';
		$is_export = true;
		// $permissions = $this->getContactPermissions();
        $permissions['export_adjusted_deposits_game_totals'] = $this->permissions->checkPermissions('export_adjusted_deposits_game_totals');

		$extra_params=[self::HTTP_REQEUST_PARAM,$permissions,$is_export];

		$rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);

		if($rlt['success']){
			redirect($rlt['link']);
		}else{
			//return error
			return show_error(lang('Export failed'));
		}




        // $this->load->model(['Player_basic_amount_list']);

        // // $request = $this->input->post();
        // // $is_export = false;
        // // $permissions=$this->getContactPermissions();

        // $result = $this->player_basic_amount_list->dataTablesList($request, $permissions, $is_export);
        // return $result;



        // $this->load->model(['Player_basic_amount_list']);
        // $this->load->library(array('permissions'));
		// $this->permissions->setPermissions();

        // //get post data
        // $request = $this->input->post();

        // $permissions = $this->getContactPermissions();
		// // $permissions['player_cpf_number'] = $this->permissions->checkPermissions('player_cpf_number');
		// $is_export = false;
        // $result = $this->player_basic_amount_list->dataTablesList($request, $permissions, $is_export);
        // return $this->returnJsonResult($result);
    }






}
