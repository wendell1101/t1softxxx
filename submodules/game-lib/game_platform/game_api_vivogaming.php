<?php require_once dirname(__FILE__) . '/abstract_game_api_common_vivo_gaming.php';

class Game_api_vivogaming extends Abstract_game_api_common_vivo_gaming {

    private $currency_type;

    public function __construct()
    {
        parent::__construct();

        $this->currency_type =  $this->getSystemInfo('currency_type',"CNY");
    }

    /**
     * Get Platform code of Game API
     * 
     * @return int game platform code
    */
    public function getPlatformCode()
    {
        return VIVOGAMING_API;
    }

    /**
     * Get original game logs table
     * 
     *@return string original game logs table in database
    */
    public function getOriginalTable()
    {
        return 'vivo_gaming_game_logs';
    }
}
/** end of file */