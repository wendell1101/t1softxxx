<?php
/**
 * OLE777 exclusive API endpoint
 * OGP-9073
 *
 * @see		routes		(player/application/config/routes.php)
 * @see		api_common	(player/application/controllers/api_common.php)
 *
 * @author 	Rupert Chen
 */

require_once dirname(__FILE__) . '/t1t_ac_tmpl.php';
require_once dirname(__FILE__) . '/t1t_comapi_module_ole777_reward_sys.php';
require_once dirname(__FILE__) . '/t1t_comapi_module_ole777_wormhole.php';

class Ole777 extends T1t_ac_tmpl {

	use t1t_comapi_module_ole777_reward_sys;
    use t1t_comapi_module_ole777_wormhole;

    // cloned form t1t_comapi_module_ole777_reward_sys
    protected $errors = [
		'SUCCESS'                 => 0 ,
		'ERR_INVALID_SECURE'      => 101 ,
		'ERR_INVALID_MEMBER_CODE' => 102 ,
		'ERR_INVALID_TOKEN'       => 103 ,
		'ERR_FAILURE'             => 190 ,
        'ERR_NO_PERMISSION_ACL'   => 104 ,
	];

	protected $black_list_enabled = false;
	protected $black_list = [];

	protected $white_list_enabled = true;
	protected $white_list = [
		'apiEcho' ,
		'apiPostEcho' ,
		'loginCaptcha',
		'login' ,
		'logout' ,
		'isPlayerExist' ,
		'createPlayer' ,
		'manualDeposit' ,
		'transfer' ,
		'queryPlayerBalance' ,
		'queryDepositBank' ,
		'queryDepositWithdrawalAvailableBank' ,
		'updatePlayerPassword' ,		// OGP-6504
		'updatePlayerProfile' ,			// OGP-6710
		'listPlayerWithdrawAccounts' ,	// OGP-6741
		'manualWithdraw' ,				// OGP-6741
		'listGamePlatforms' ,			// OGP-6822
		'listGamesByPlatform' ,			// OGP-6822
		'listGamesByPlatformGameType' ,	// OGP-6822
		'listGame_settings' ,			// OGP-6822 (internal use only)
		'updatePlayerWithdrawalPassword' ,	// OGP-6908
		'playerLoginGetToken_CT' ,		// OGP-6932
		'gameReport' ,
		'createAffiliate' ,				// OGP-6459
		'getPlayerProfile', 			// OGP-7624
		'ole777_login' ,				// OGP-9073
		'ole777_auth_check' ,			// OGP-11087
		'ole777_auth_check_local' ,		// OGP-11087
		'ole777_verify_member',			// OGP-28347
        'getAllFailedLoginAttempts',	// OGP-29624
        'getAllMessages',				// OGP-29625
        'sendMessage',					// OGP-29626
		'getPlayerProfileV2', 		    // OGP-29888
		'getAllPlayer',					// OGP-29889
		'manualDepositTo3rdParty',		// OGP-30821
		'getAllPlayer2',				// OGP-33683
	];

	function __construct() {
		parent::__construct();

		// Enable adjustPlayerBalance (disabled by default)
		// $this->enable_method_access('adjustPlayerBalance');
		// $this->enable_method_access('getPlayerPasswordPlain');
	}


}