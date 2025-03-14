<?php

trait player_center_api_cool_down_time_module {


    /**
     * Delete the data by overdue cool down time.
     *
     * CMD:
     *
     * sh command.sh clear_cooldown_expired_in_player_center_api
	 * sudo /bin/bash admin/shell/command.sh clear_cooldown_expired_in_player_center_api > ./logs/command_clear_cooldown_expired_in_player_center_api &

     * @return string The json string for ajax handle. more detail pls reference to the return of dispatch_withdrawal_conditions::delete().
     */
    public function clear_cooldown_expired_in_player_center_api(){
        $this->load->model(['player_center_api_cool_down_time']);
        $controller = $this;
        return $this->player_center_api_cool_down_time->dbtransOnly(function () use ( $controller ) {
            $settingListOfConfigure = $controller->utils->getConfig('player_center_api_cool_down_time');
            $rltDetails = [];
            if( !empty($settingListOfConfigure) ){
                foreach($settingListOfConfigure as $indexNumber => $setting){
                    $class = $setting['class'];
                    $method = $setting['method'];
                    $cool_down_sec = $setting['cool_down_sec'];
                    $affected_rows = [];
                    $affected_count = $controller->player_center_api_cool_down_time->clear_cooldown_expired($class, $method, $cool_down_sec, $affected_rows);
                    $rltDetails[$class][$method]['affected_count'] = $affected_count;
                    $rltDetails[$class][$method]['affected_rows'] = $affected_rows;
                }
            }
            $this->utils->debug_log('OGP-25476.36.rltDetails:', $rltDetails);
            if( !empty($rltDetails) ){
				return true; // submit
			}

			return false; // rollback
        });

    } // clear_cooldown_expired_in_player_center_api

} // EOF trait player_center_api_cool_down_time_module
