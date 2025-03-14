<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_sportsbook_flash_tech.php';


/**
 * Ticket Number: OGP-18708
 * Wallet Type(Transfer/Seamless) : Transfer
 * 
 * @see Service Interface Description Version:1.8.1.7
 * 
 * @category Game API
 * @copyright 2013-2022 tot
 * @author Jason Miguel
 */

 class Game_api_sportsbook_flash_tech_cny1 extends Abstract_game_api_common_sportsbook_flash_tech
 {

    /** @var const */
    const CURRENCY = 'CNY';

    /** @var const*/
    const OGL = 'flash_tech_cny1_game_logs';

    public function __construct()
    {
        parent::__construct();

        /** extra info */
        $this->currency = $this->getSystemInfo('currency',self::CURRENCY);
        $this->originalGameLogsTable = $this->getSystemInfo('originalGameLogsTable',self::OGL);
    }

    /**
     * Get Platform code of Game API
     * 
     * @return int game platform code
    */
    public function getPlatformCode()
    {
        return SPORTSBOOK_FLASH_TECH_GAME_CNY1_API;
    }

    /** 
     * Get original game logs table
     * 
     *@return string original game logs table in database
    */
    public function getOriginalTable()
    {
        return $this->originalGameLogsTable;
    }
 }
 /** END OF FILE */