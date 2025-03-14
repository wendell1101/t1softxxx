<?php

require_once dirname(__FILE__).'/game_api_common_ag.php';

/**
 * Adapter of AG (Asia Gaming).
 *
 * General behaviors include:
 * * Create player
 * * Change password : can't
 * * Query Player Balance
 * * Generating payment forms
 * * Receiving callbacks
 * * Submitting withdrawal requests
 *
 *
 * extra_info:
 *
 * ```json
 *	{
 *	"CAGENT_AG": "XX_AGIN",
 *	"MD5KEY_AG": "xxxx",
 *	"DESKEY_AG": "xxxx",
 *	"GCIURL_AG": "http://gci.xxxxx.xxx:81/",
 *	"DM_AG": "http://localhost",
 *	"ag_game_records_path": "/var/game_platform/ag",
 *	"prefix_for_username": "sw",
 *	"balance_in_game_log": true,
 *	"adjust_datetime_minutes": 20
 *	}
 * ```
 *
 * @see gotogame_module.php
 * @see Sync_game_records
 *
 * @category Game API
 *
 * @version 2.8.10 new ftp structure
 *
 * @copyright 2013-2022 tot
 */
class Game_api_ag extends Game_api_common_ag
{
    public function getPlatformCode()
    {
        return AG_API;
    }

    public function __construct()
    {
        parent::__construct();

        $defaultIgnorePlatform=['IPM', 'BBIN', 'MG', 'SABAH', 'HG', 'PT',
            'OG', 'UGS', 'XTD', 'ENDO', 'BG'];

        $this->ignore_platformtypes = $this->getSystemInfo('ignore_platformtypes', $defaultIgnorePlatform);
    }

    public function getAvailableRows($dataResult)
    {
        $this->CI->load->model('ag_game_logs');
        return $this->CI->ag_game_logs->getAvailableRows($dataResult);
    }

    public function insertBatchToGameLogs($availableResult)
    {
        $this->CI->load->model('ag_game_logs');
        return $this->CI->ag_game_logs->insertBatchGameLogsReturnIds($availableResult);
    }

    public function syncGameLogsToDB($dataResult){
        $this->CI->load->model('ag_game_logs');
        return $this->CI->ag_game_logs->syncGameLogs($dataResult);
    }

    public function getIngorePlatformTypes()
    {
        //ignore by settings
        return $this->ignore_platformtypes;
    }

    public function getOriginalGameLogsByIds($ids)
    {
        $this->CI->load->model('ag_game_logs');

        return $this->CI->ag_game_logs->getGameLogStatisticsByIds($ids);
    }

    public function getOriginalGameLogsByDate($startDate, $endDate)
    {
        $this->CI->load->model('ag_game_logs');

        return $this->CI->ag_game_logs->getGameLogStatistics($startDate, $endDate);
    }
}

/*end of file*/
