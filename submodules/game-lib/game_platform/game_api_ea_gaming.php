<?php
require_once dirname(__FILE__) . '/game_api_common_ea_gaming.php';


/**
 * Ticket Number: OGP-17658
 * Wallet Type(Transfer/Seamless) : Transfer
 * 
 * @see Transfer Wallet Integration Guide Version 1.01
 * @see Game Info Specification Version 1.03
 * @see Game_api_common_ea_gaming::class
 * 
 * @category Game API
 * @copyright 2013-2022 tot
 * @author Jason Miguel
 */

 class Game_api_ea_gaming extends Game_api_common_ea_gaming
 {

    /** @var const */
    const CURRENCY = 'CNY';

    public function __construct()
    {
        parent::__construct();

        /** extra info */
        $this->currency = $this->getSystemInfo('currency',self::CURRENCY);
    }

    /**
     * Get Platform code of Game API
     * 
     * @return int game platform code
    */
    public function getPlatformCode()
    {
        return EA_GAME_API;
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