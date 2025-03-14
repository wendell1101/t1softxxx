<?php require_once dirname(__FILE__) . '/abstract_game_api_common_vivo_gaming.php';

class Game_api_vivogaming_thb1_api extends Abstract_game_api_common_vivo_gaming{

    const CURRENCY_TYPE = "THB";

    public function __construct()
    {
        parent::__construct();

        $this->currency_type = self::CURRENCY_TYPE;
    }

    /**
     * Get Platform code of Game API
     * 
     * @return int game platform code
    */
    public function getPlatformCode()
    {
        return VIVOGAMING_THB_B1_API;
    }

    /** 
     * Get original game logs table
     * 
     *@return string original game logs table in database
    */
    public function getOriginalTable()
    {
        return 'vivo_gaming_thb1_game_logs';
    }
}
/** end of file */