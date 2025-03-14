<?php
/**
 * Generic player_center API wrapper
 * OGP-9053
 *
 * @see		routes		(player/application/config/routes.php)
 * @see		api_common	(player/application/controllers/api_common.php)
 *
 * @author 	Rupert Chen
 */

require_once dirname(__FILE__) . '/t1t_ac_tmpl.php';
require_once dirname(__FILE__) . '/t1t_comapi_module_third_party_deposit.php';
require_once dirname(__FILE__) . '/t1t_comapi_module_bonus_games.php';
require_once dirname(__FILE__) . '/t1t_comapi_module_player_game_log.php';
// require_once dirname(__FILE__) . '/t1t_comapi_module_get_player_risk_info.php';
require_once dirname(__FILE__) . '/t1t_comapi_module_sms_verification.php';
require_once dirname(__FILE__) . '/t1t_comapi_module_sms_registration.php';
require_once dirname(__FILE__) . '/t1t_comapi_module_mail_verification.php';
require_once dirname(__FILE__) . '/t1t_comapi_module_player_transaction.php';
require_once dirname(__FILE__) . '/t1t_comapi_module_pos.php';

class Player_center extends T1t_ac_tmpl {

	use t1t_comapi_module_third_party_deposit;
	use t1t_comapi_module_bonus_games;
	use t1t_comapi_module_player_game_log;
	use t1t_comapi_module_sms_verification;
	use t1t_comapi_module_sms_registration;
	use t1t_comapi_module_mail_verification;
	use t1t_comapi_module_player_transaction;
	use t1t_comapi_module_pos;

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
		'getRequestIP' ,
		'getClientIP' ,
		'createPlayer' ,
        'createPlayerCaptcha',
		'createPlayerAgency' ,
		'manualDeposit' ,
		'transfer' ,
		'transfer0' ,
		'queryPlayerBalance' ,
		'queryDepositBank' ,
		// 'queryBankTypeList' ,
		'queryDepositWithdrawalAvailableBank' ,
		'updatePlayerPassword' ,		// OGP-6504
		'updatePlayerProfile' ,			// OGP-6710
		'updatePlayerProfile2' ,		// OGP-13290
		'listPlayerWithdrawAccounts' ,	// OGP-6741
		'manualWithdraw' ,				// OGP-6741
		'listGamePlatforms' ,			// OGP-6822
		'listGamePlatforms2' ,			// OGP-13359 WIP
		'listGamesByPlatform' ,			// OGP-6822
		'listGamesByPlatformGameType' ,	// OGP-6822
		'listGame_settings' ,			// OGP-6822 (internal use only)
		'list_game_category',
		'list_game_icon_by_category',
		'list_game_types',
		'updatePlayerWithdrawalPassword' ,	// OGP-6908
		'playerLoginGetToken_CT' ,		// OGP-6932
		// 'adjustPlayerBalance' ,			// OGP-6952
		'gameReport' ,
		'createAffiliate' ,				// OGP-6459
		'getPlayerProfile', 			// OGP-7624
		// 'getPlayerPasswordPlain'	,	// OGP-7661
		// OGP-9568: third party payment series
		'listThirdPartyPayments' ,
		'thirdPartyDepositForm' ,
		'thirdPartyDepositRequest' ,
		'message' ,
		'addMessage' ,
		// OGP-13596
		'messageSetRead' ,
		// OGP-9493
		'addPlayerDepositAccount' ,
		'addPlayerWithdrawAccount' ,
		// OGP-9815
		'getPlayerReports' ,
		// OGP-9571
		'getSysFeatures' ,
		// 'getRegSettings' ,
		// OGP-10251
		'listDepositMethods' ,
		// OGP-9570
		'listPromos' ,
		'applyPromo' ,
		// OGP-13277
		'listPromos2' ,
		// OGP-14625 ,
		'applyPromo2' ,
		// OGP-10906
		'bgame' ,
		// OGP-13250, 13251, 13326
		'depositPaymentCategories' ,
		'manualDepositForm' ,
		'manualDepositRequest' ,
		'announcements' ,
		// OGP-13284
		'manualDepositLastResult' ,
		// OGP-12133
		'getPlayerGameLogs' ,
		// OGP-12844
		'smsVerifyStatus' ,
		'smsVerifySend' ,
		'smsVerify' ,
		// OGP-13669
		'mailVerifyStatus' ,
		'mailVerifySend' ,
		'mailVerify' ,
		// OGP-12843, 13030, 13033
		'passwordRecovMailSend' ,
		'passwordRecovMailRecv' ,
		'passwordRecovSmsSend' ,
		'passwordRecovSmsRecv' ,
		'playerKycImageUpload' ,
        'playerKycImageList' ,
		// OGP-13076
		'smsRegSendSms' ,
		'smsRegCreatePlayer' ,
        'smsRegCreatePlayerCaptcha' ,
		'depositPaymentCategories' ,
		// OGP-13106
		'setPlayerWithdrawAccountDefault' ,
		// OGP-14401
		'isIpBlocked' ,
		// OGP-14487
		'getAffTrackingCodeByAdditionalDomain' ,
		// OGP-15868
		'getPlayerVipStatus' ,
		// OGP-16295
		'transferTo' ,
		'transferAllTo' ,
		'queryPlayerBalance2' ,
		'playerWalletMappings' ,
		// OGP-16511
		'updatePlayerBalance' ,
		// OGP-16730
		'getRegSettings' ,
		'getReg0Settings' ,
		// OGP-16735
		'manualDepositAttUpload' ,
		// OGP-17093
		'aff_login' ,
		// OGP-17094
		'aff_logout' ,
		// OGP-17088
		'aff_reportSubAffs' ,
		// OGP-16998
		'removePlayerWithdrawAccount' ,
		'removePlayerDepositAccount' ,
		'listPlayerDepositAccounts' ,
		// OGP-17411, 17412
		'getPlayerTransferStatus' ,
		'summaryPlayerWithdrawalConditions' ,
		// OGP-17697
		'messageNew' ,
		'messageReply' ,
		'messageList' ,
		// OGP-18075
		'listBanners' ,
		'banner_img' ,
		// OGP-18556
		'getTopBetPlayers' ,
		//OGP-24350
		'getPlayerRankList',
		'getPlayerNewbetRanklist',
		// OGP-18765
		'listPromos3' ,
		'applyPromo3' ,
		// OGP-19508
		'getPlayerReferralCode' ,
		// OGP-19855
		'addPlayerUsdtAccountSend' ,
		'addPlayerUsdtAccountRecv' ,
		'getTopWinningPlayers' ,
		// OGP-20784
		'addPlayerDepositAccountSend' ,
    	'addPlayerWithdrawAccountSend' ,
    	'addPlayerDepositAccountRecv' ,
    	'addPlayerWithdrawAccountRecv' ,
    	// OGP-22577
    	'manualWithdrawForm' ,
    	// OGP-22838
    	'thirdPartyDepositRequestUsdt' ,
    	// OGP-22656
    	'mailVerifyRecvOtp' ,
    	// OGP-22728
    	'getPlayerWithdrawals' ,
    	'cancelPlayerWithdrawal' ,
    	// OGP-23150
    	'gameMaintenanceTime' ,
    	// OGP-23167
    	'playerGameFavAdd' ,
    	'playerGameFavRemove' ,
    	'playerGameFavList' ,
    	// OGP-23302
    	'respGamingStatus' ,
    	'respGamingStatus2' ,
    	'respGamingForm' ,
    	'respGamingRequest' ,
		// OGP-23730 to 23733
		'getRouletteSpinTimes' ,
		'applyRoulette' ,
		'listRoulette',
		'fetchBetAndDepositAmount',
		'getLatestBets', // OGP-24778
		'listPromoCategories',//OGP-26115
		'apiDomains',
        'joinPriorityPlayer',
        'updatePlayerTag',
		'getLatestBetsByGameType', //OGP-26415
		'getLatestBetsByPlayerAndGameType', //OGP-26415
		'getPlayersBetListByDate', // OGP-28526
		'getPlayersDepositListByDate', // OGP-28526
		'getProviderLatestBets',
		'getProviderBetRankings',
		'listGamesByPlatformGameTypeTagCode', //OGP-28594
        'getPlayersGameLogs', // OGP-28514
        'getPosBetDetails', // OGP-29014
        'savePosRealPlayerInfo', // OGP-29029
        'ajax_lang_list', // OGP-29157
        'getTopGamesByPlayers', // OGP-30213
		'getPlayerSalesAgent', // OGP-30852
        'listDailySignInPromoStatus', // OGP-30424
        'listMonthlySignInPromoStatus',
        'getMonthlyTotalTurnOverByPlayer',#OGP-32902
        'getHighRollers', // OGP-34126
	];

	function __construct() {
		parent::__construct();
		$this->comapi_log(__METHOD__, 'Beginning comapi request');

		// Enable adjustPlayerBalance (disabled by default)
		// $this->enable_method_access('adjustPlayerBalance');
		// $this->enable_method_access('getPlayerPasswordPlain');
	}


}
