<?php

/**
 *
 * api for transfer balance
 */
trait middle_exchange_rate_log_module {
    /**
     * List for payment_management::withdrawal_risk_process_list().
     *
     * @return string The json for $.DataTable().
     */
    public function middle_exchange_rate_log_list(){
        $this->load->model(['middle_exchange_rate_log']);

        $request = $this->input->post();
        $is_export = false;
        $permissions=$this->getContactPermissions();

        $result = $this->middle_exchange_rate_log->dataTablesList($request, $permissions, $is_export);
        $this->returnJsonResult($result);
    }// EOF dispatch_withdrawal_definition_list

    public function update_middle_exchange_rate_in_operator($rate, $updated_by){
        $this->load->model(['middle_exchange_rate_log', 'operatorglobalsettings']);
        $enabled_sync_middle_exchange_rate_in_mdb = $this->utils->getConfig('sync_middle_exchange_rate_in_mdb');

        $data = [];
        $data['value'] = $rate;
		$data['name'] = Operatorglobalsettings::MIDDLE_CONVERSION_EXCHANGE_RATE;
		$rlt_in_updated = $this->operatorglobalsettings->syncSetting(Operatorglobalsettings::MIDDLE_CONVERSION_EXCHANGE_RATE, $rate);

        $params = [];
        $params['rate'] = $rate;
        $params['updated_by'] = $updated_by;
        $params['status'] = Middle_exchange_rate_log::DB_TRUE;
        $rlt_in_log_added = $this->middle_exchange_rate_log->add($params);

        if($rlt_in_updated && $enabled_sync_middle_exchange_rate_in_mdb){

            // Sync to other mdb
            if($this->utils->isEnabledMDB()){
                //update mdb first
                $_this = $this;
                $sourceDB = $this->utils->getActiveTargetDB();
                $rlt = $this->operatorglobalsettings->foreachMultipleDBWithoutSourceDB($sourceDB,
                    function($db, &$result) use( $_this, $rate, $updated_by ){
                    $result=$rate;

                    // for add log
                    $params = [];
                    $params['rate'] = $rate;
                    $params['updated_by'] = $updated_by;
                    $params['status'] = Middle_exchange_rate_log::DB_TRUE;
                    $rlt_in_log_added = $_this->middle_exchange_rate_log->add($params, $db);

                    $rlt_sync = $_this->operatorglobalsettings->syncSetting(Operatorglobalsettings::MIDDLE_CONVERSION_EXCHANGE_RATE, $rate, 'value', $db);

                    $_this->utils->deleteCache(); // TODO: to refresh with other db name key.( utils::getAppPrefix() )

                    return $rlt_sync;
                });
                $this->utils->debug_log('update middle conversion exchange rate on mdb', $rlt);




            }
        }

    } // EOF update_middle_exchange_rate_in_operator


} // EOF trait withdrawal_risk_api_module
