<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_big_gaming.php';


/**
 * Wallet Type(Transfer/Seamless) : Transfer
 * 
 * @see Operation instruction document V1.2.8.8
 * @see Big Gaming open platform API documentation
 * 
 * @category Game API
 * @copyright 2013-2022 tot
 */

 class Game_api_big_gaming extends Abstract_game_api_common_big_gaming
 {

    /** @var const */
    const CURRENCY = 'CNY';

    const ORIGINAL_TABLE = "biggaming_game_logs";

    public function __construct()
    {
        parent::__construct();

        /** extra info */
        $this->currency = $this->getSystemInfo('currency',self::CURRENCY);
        $this->original_gamelogs_table = self::ORIGINAL_TABLE;   
    }

    /**
     * Get Platform code of Game API
     * 
     * @return int game platform code
    */
    public function getPlatformCode()
    {
        return BG_GAME_API;
    }

    public function isSeamLessGame()
    {
        return false;
    }

    /** 
     * Get original game logs table
     * 
     *@return string original game logs table in database
    */
    public function getOriginalTable()
    {
        return $this->original_gamelogs_table;
    }

 }
 /** END OF FILE */