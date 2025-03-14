<?php
trait player_basic_amount_list_module {

	/**
     * api/adjusted_deposits_game_totals_list
     *
     * @return void
     */
    public function adjusted_deposits_game_totals_list(){
		$this->load->model(['player_basic_amount_list']);
        $this->load->library(array('permissions'));
		$this->permissions->setPermissions();
		//get post data
		$request = $this->input->post();

		$permissions = $this->getContactPermissions();
		$permissions['modified_adjusted_deposits_game_totals'] = $this->permissions->checkPermissions('modified_adjusted_deposits_game_totals');
		$is_export = false;
		$result = $this->player_basic_amount_list->dataTablesList($request, $permissions, $is_export);
		return $this->returnJsonResult($result);

	}



}