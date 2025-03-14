<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_netent_seamless.php';


/**
 * Ticket Number: OGP-18063
 * Wallet Type(Transfer/Seamless) : Seamless
 * 
 * @see https://developer.netent.com/
 * @see Password: NetEntIntegration123
 * 
 * @category Game API
 * @copyright 2013-2022 tot
 * @author Jason Miguel
 */

 class Game_api_netent_seamless_thb1 extends Abstract_game_api_common_netent_seamless
 {

    /** @var const */
    const CURRENCY = 'THB';

    /** @var const*/
    const OGL = 'common_seamless_wallet_transactions';

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
        return NETENT_SEAMLESS_GAME_THB1_API;
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