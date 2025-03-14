<?php

trait sync_pos_player_latest_game_logs_command_module {

    public function sync_pos_player_latest_game_logs($dateTimeFromStr = null, $dateTimeToStr = null, $gamePlatformId = null, $queue_token=null) {
        $default_sync_game_logs_max_time_second = $this->config->item('default_sync_game_logs_max_time_second');
        set_time_limit($default_sync_game_logs_max_time_second);

        $this->utils->debug_log('========= start sync_pos_player_latest_game_logs ============================ api', $gamePlatformId);
        $this->utils->debug_log('========= start sync_pos_player_latest_game_logs ============================ date', $dateTimeFromStr, '-', $dateTimeToStr);

        $this->load->model(array('sync_status_model', 'queue_result'));

        if (!empty($queue_token)) {
            $this->utils->debug_log('append result ', _REQUEST_ID, $queue_token);
            //update queue_results
            $this->queue_result->appendResult($queue_token, ['game_api'=> $gamePlatformId,
            'request_id'=>_REQUEST_ID, 'func'=>'sync_pos_player_latest_game_logs']);
        }

        ## rebuild seamless latest game records
        # get data from game logs

        $this->load->model(array('pos_player_latest_game_logs'));
        $dateTimeTo = $dateTimeFrom = new DateTime();

        if (empty($dateTimeFromStr)) {
            $dateTimeFromStr = $dateTimeFrom->modify('-15 minutes')->format('Y-m-d H:i:00');
        }

        if (empty($dateTimeToStr)) {
            $dateTimeToStr = $dateTimeTo->format('Y-m-d H:i:s');
        }

        $resp = $this->pos_player_latest_game_logs->sync(new DateTime($dateTimeFromStr), new DateTime($dateTimeToStr));

        $this->utils->info_log('========= end syncSeamlessGameBatchPayoutOnOnePlatform ============================', $gamePlatformId, 'response', $resp);
        return;
    }

}