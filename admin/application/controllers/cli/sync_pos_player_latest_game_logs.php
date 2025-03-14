<?php
require_once dirname(__FILE__) . "/base_cli.php";

//require_once dirname(__FILE__) . '/../modules/sync_pos_player_latest_game_logs_command_module.php';

class Sync_pos_player_latest_game_logs extends Base_cli {

    //use sync_pos_player_latest_game_logs_command_module;

    const LOG_TITLE = '[sync_pos_player_latest_game_logs_controller]';

    public $oghome = null;
    public $CI;

    /**
     * overview : Sync_game_records constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->CI->config->set_item('print_log_to_console', true);
        $this->oghome = realpath(dirname(__FILE__) . "/../../../");
    }

    /**
     * overview : sync service start
     *
     * @param string $dateTimeFromStr
     */
    public function start_sync_pos_player_latest_game_logs($dateTimeFromStr = '-10 minutes') {
        //never stop
        set_time_limit(0);
        $dateTimeFromStr = (new \DateTime($dateTimeFromStr))->format('Y-m-d H:i:00');
        $dateTimeToStr = date('Y-m-d H:i:59');
        $this->CI->utils->debug_log('runing... started from:' . $dateTimeFromStr . 'to:' . $dateTimeToStr);

        $mark = 'benchSyncPosPlayerLatestGameLogs';
        //run sync
        while (true) {
            # reset db
            $this->resetDb($this->CI->db);
        
            $this->CI->utils->markProfilerStart($mark);
            $this->run_sync_shell($dateTimeFromStr, $dateTimeToStr);
            $this->CI->utils->markProfilerEndAndPrint($mark);

            $syncSleepTime = $this->CI->utils->getConfig('sync_pos_player_latest_game_logs_sleep_seconds');
            $this->CI->utils->debug_log('sleep...', $syncSleepTime, 'from', $dateTimeFromStr, 'to', $dateTimeToStr);
            sleep($syncSleepTime);

            //set next
            $dateTimeFromStr = $dateTimeToStr;
            $dateTimeToStr = date('Y-m-d H:i:s');

            $this->CI->utils->debug_log('from', $dateTimeFromStr, 'to', $dateTimeToStr);

            gc_collect_cycles();
        }

        $this->CI->utils->debug_log(self::LOG_TITLE, 'stopped');
    }

    /**
     * overview : run sync shell
     *
     * @param string    $dateTimeFromStr
     * @param string    $dateTimeToStr
     * @param string    $playerName
     */
    public function run_sync_shell($dateTimeFromStr, $dateTimeToStr) {

        $og_home = $this->oghome;

        $this->CI->utils->debug_log("sync_pos_player_latest_game_logs");

        $cmd = 'bash ' . $og_home . '/shell/sync_pos_player_latest_game_logs.sh "' . $dateTimeFromStr . '" "' . $dateTimeToStr . '"';

        //run merge
        $this->CI->utils->debug_log('start sync', $cmd);
        $return = shell_exec($cmd);
        $this->CI->utils->debug_log("sync_pos_player_latest_game_logs", 'return', $return);

        $this->CI->utils->debug_log("sync_pos_player_latest_game_logs done");

        //all done
        // $this->returnText($str);
    }

    public function sync($dateTimeFromStr = null, $dateTimeToStr = null) {
        $default_sync_game_logs_max_time_second = $this->CI->utils->getConfig('default_sync_game_logs_max_time_second');
        set_time_limit($default_sync_game_logs_max_time_second);

        $this->CI->utils->debug_log('========= start sync_pos_player_latest_game_logs ============================ date', $dateTimeFromStr, '-', $dateTimeToStr);

        $this->CI->load->model(array('sync_status_model', 'queue_result'));

        ## rebuild seamless latest game records
        # get data from game logs

        $this->CI->load->model(array('pos_player_latest_game_logs'));
        $dateTimeTo = $dateTimeFrom = new DateTime();

        if (empty($dateTimeFromStr)) {
            $dateTimeFromStr = $dateTimeFrom->modify('-15 minutes')->format('Y-m-d H:i:00');
        }

        if (empty($dateTimeToStr)) {
            $dateTimeToStr = $dateTimeTo->format('Y-m-d H:i:s');
        }

        $this->CI->utils->debug_log('========= end syncSeamlessGameBatchPayoutOnOnePlatform ============================ date', $dateTimeFromStr . ' to ' . $dateTimeToStr);
        $resp = $this->CI->pos_player_latest_game_logs->sync(new DateTime($dateTimeFromStr), new DateTime($dateTimeToStr));

        $this->CI->utils->info_log('========= end syncSeamlessGameBatchPayoutOnOnePlatform ============================', 'response', $resp);
        return;
    }

}

/// END OF FILE//////////////
