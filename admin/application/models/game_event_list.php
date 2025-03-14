<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/base_model.php';

/**
 * General behaviors include :
 *
 * * Get game event list 
 *
 * @category Game Model
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Game_event_list extends BaseModel {

    const STATUS_NORMAL = 1;
    const STATUS_DISABLED = 2;
    
    
    const STATUS_PC_ENABLE = 1;
    const STATUS_MOBILE_ENABLE = 1;

	public function __construct() {
		parent::__construct();
	}

	protected $tableName = "game_event_list";
    
}

///END OF FILE///////
