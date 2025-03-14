<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_big_gaming_seamless.php';


/**
 * Ticket Number: OGP-19292
 * Wallet Type(Transfer/Seamless) : Seamless
 * 
 * @see Operation instruction document V1.2.8.8
 * @see Big Gaming open platform API documentation
 * 
 * @category Game API
 * @copyright 2013-2022 tot
 * @author Jason Miguel
 */

 class Game_api_big_gaming_seamless extends Abstract_game_api_common_big_gaming_seamless
 {

    /** @var const */
    const CURRENCY = 'CNY';

    const OGL = 'seamless_wallet_transactions_5800';
    const ORIGINAL_TABLE = "biggaming_game_logs";	

    public function __construct()
    {
        parent::__construct();

        /** extra info */
        $this->currency = $this->getSystemInfo('currency',self::CURRENCY);
        $this->original_gamelogs_table = self::ORIGINAL_TABLE;   
        $this->original_transactions_table = self::OGL;
    }

    /**
     * Get Platform code of Game API
     * 
     * @return int game platform code
    */
    public function getPlatformCode()
    {
        return BG_SEAMLESS_GAME_API;
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