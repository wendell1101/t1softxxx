<?php

trait report_module_player_basic_amount_list{

	// exportPlayerLoginViaSameIpLogsList
	public function export_player_basic_amount_list($request, $permissions, $is_export = false) {
        $this->load->model(['player_basic_amount_list']);

        // $request = $this->input->post();
        // $is_export = false;
        // $permissions=$this->getContactPermissions();

        $result = $this->player_basic_amount_list->dataTablesList($request, $permissions, $is_export);
        return $result;
        // return $this->returnJsonResult($result);
    }


}

