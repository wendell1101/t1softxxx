<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
 */
//$route['www.lg.com/(:any)'] = "online/casino";
$route['default_controller'] = "player_center/home";
$route['404_override'] = 'player_center/show_404';

$route['iframe_module/(:any).js'] = 'iframe_module/$1_js';
$route['player_center/(:any).js'] = 'player_center/$1_js';
$route['pub/(:any)/(:any).js'] = 'pub/$2_js/$1';
$route['player_center2/pub/(:any).js'] = 'player_center2/pub/$1_js';
$route['pub/player_main/(:any)/(:num)'] = 'pub/player_main_js/$1/true';
$route['player/upload/(:any)'] = 'player_internal/player/$1';
// $route['iframe_module/(:any)'] = 'iframe_module/$1';

// player registration tracking route
// $route['aff/(:any)'] = 'iframe_module/iframe_register/$1';

// template routes
// $route['ttt/(:any)'] = 'iframe/$1';

$route['(:any)/(:any)/(:any)/refresh_session.gif'] = 'pub/refresh_session/$1/$2/$3';

// $route['__clockwork/(.*)'] = 'clockwork_controller/$1';


#WEBET ENGLISH
$route['player_center/cashier'] = 'player_center/dashboard';
$route['player_center/my-promotions'] = 'player_center/iframe_promos';
$route['player_center/deposit-online'] = 'player_center/iframe_makeDeposit';
$route['player_center/transfer'] = 'player_center/dashboard';
$route['player_center/messages'] = 'player_center2/messages';

$route['player-center/cashier'] = 'player_center/dashboard';
$route['player-center/my-promotions'] = 'player_center/iframe_promos';
$route['player-center/deposit-online'] = 'player_center/iframe_makeDeposit';
$route['player-center/transfer'] = 'player_center/dashboard';
$route['player-center/messages'] = 'player_center2/messages';
$route['player-center/profile'] = 'player_center/profile';
$route['player-center/transfer'] = 'player_center/money_transfer';
$route['player-center/promotion/(:any)'] = 'player_center/show_promo_from_site/$1';
$route['rwb_service_api/(:any)'] = 'rwb_service_api/transaction/$1';

// #WEBET CHINESE
// $route['player_center/zh/cashier'] = 'player_center/dashboard';
// $route['player_center/zh/profile'] = 'player_center/profile';
// $route['player_center/zh/my-promotions'] = 'player_center/iframe_promos';
// $route['player_center/zh/deposit-online'] = 'player_center/iframe_makeDeposit';
// $route['player_center/zh/transfer'] = 'player_center/dashboard';
// $route['player_center/zh/withdraw'] = 'player_center/withdraw';

// $route['player_center/zh/transactions'] = 'player_center/dashboard';
// $route['player_center/zh/dashboard'] = 'player_center/dashboard';
// $route['player_center/zh/messages'] = 'player_center2/messages';
// $route['player_center/zh/money_transfer'] = 'player_center/money_transfer';
// $route['player_center/zh/iframe_changePassword'] = 'player_center/iframe_changePassword';


$route['aff/(:any)'] = 'pub/aff/$1';
$route['ag/(:any)'] = 'pub/ag/$1';
$route['promotion/(:any)'] = 'pub/promotion/$1';
$route['api/yongji/(:any)'] = 'customer_api/yongji/$1';
$route['api/fcm/(:any)'] = 'customer_api/fcm/$1';
$route['api/youhu/(:any)'] = 'customer_api/youhu/$1';
$route['api/lanhai/(:any)'] = 'customer_api/lanhai/$1';
$route['api/tailai/(:any)'] = 'customer_api/tailai/$1';
$route['api/testground/(:any)'] = 'customer_api/testground/$1';
$route['api/lequ/(:any)'] = 'customer_api/lequ/$1';
$route['api/player_center/(:any)'] = 'customer_api/player_center/$1';
$route['api/aff/(:any)'] = 'customer_api/aff/$1';
$route['api/ole777/(:any)'] = 'customer_api/ole777/$1';
$route['api/chatai/(:any)'] = 'customer_api/chatai/$1';
$route['api/saba/(:any)/(:any)'] = 'customer_api/saba/$2/$1';
$route['api/b9b/(:any)'] = 'customer_api/b9b/$1';
$route['api/laba360/(:any)'] = 'customer_api/laba360/$1';
$route['api/smash/(:any)'] = 'customer_api/smash/$1';

// OGP-18764
$route['api/public_cs/(:any)'] = 'customer_api/public_cs/$1';

$route['elg_service_api/Users/(:any)/Tokens/(:any)/KeepAlive'] = 'extremelivegaming_service_api/prolongToken/$1/$2';
$route['elg_service_api/Cash/Users/(:any)/Transactions'] = 'extremelivegaming_service_api/transaction/$1';
$route['elg_service_api/Cash/Users/(:any)'] = 'extremelivegaming_service_api/getUserInfo/$1';
$route['ls_service_api/apikey/(:any)'] = 'ls_casino_service_api/index/$1';
$route['Xpress/(:any)'] = 'golden_race_service_api/$1';
$route['habanero_service_api/(:any)'] = 'habanero_service_api/$1';

$route['player_center/goto_sagaming_logout'] = 'player_center/goto_sagaming_logout';
# $route['ag_service_api/rest/integration/(:any)'] = 'ag_seamless_service_api_old/redirection/$1';

// public
$route['playerapi/public/forgot/password']='playerapi/player/forgot/password/login';
$route['playerapi/public/otp']='playerapi/player/otp-public';
$route['playerapi/public/vip/list']='playerapi/player/vip-public';
$route['playerapi/auth/otp']='playerapi/player/auth-otp';

// player
$route['playerapi/verification-questions']='playerapi/player/verification-questions';
$route['playerapi/otp']='playerapi/player/otp';
$route['playerapi/login/otp']='playerapi/player/login/otp';
$route['playerapi/login/phone']='playerapi/oauth/phone';
$route['playerapi/captcha-image']='playerapi/player/captcha-image';
$route['playerapi/public/captcha/image']='playerapi/player/captcha-image';
$route['playerapi/player-resource']='playerapi/player_resource';
$route['playerapi/player-resource/(:any)']='playerapi/player_resource/$1';
$route['playerapi/player-resource/(:any)/(:any)']='playerapi/player_resource/$1/$2';
$route['playerapi/player/referral/list']= 'playerapi/player/referral-list';
$route['playerapi/player/referral/statistics']= 'playerapi/player/referral-statistics';
$route['playerapi/player/forgot/password/(:any)'] = 'playerapi/player/forgot/password/$1';
$route['playerapi/player/upload/avatar'] = 'playerapi/player/upload-avatar';
$route['playerapi/player/vip/list']= 'playerapi/player/vip-list';
$route['playerapi/player/runtime/info']= 'playerapi/player/runtime-info';

$route['playerapi/public/rank/list']= 'playerapi/player/rank-list-public';
$route['playerapi/public/rank/records']= 'playerapi/player/rank-records-public';
$route['playerapi/player/rank/info']= 'playerapi/player/rank-info';

// cashier
$route['playerapi/payment/deposit/methods']='playerapi/cashier/payment-methods';
$route['playerapi/payment-methods']='playerapi/cashier/payment-methods';

$route['playerapi/payment/deposit']='playerapi/cashier/payment-requests';
$route['playerapi/payment-requests']='playerapi/cashier/payment-requests';
$route['playerapi/payment/deposit/file/(:any)/(:any)']= 'playerapi/cashier/fetch-deposit-file/$1/$2';
$route['playerapi/payment/deposit/(:any)']='playerapi/cashier/payment-requests/$1';
$route['playerapi/payment-requests/(:any)']='playerapi/cashier/payment-requests/$1';

$route['playerapi/payment/deposit/pending']='playerapi/cashier/payment-requests/pending-amount';
$route['playerapi/payment-requests/pending-amount']='playerapi/cashier/payment-requests/pending-amount';

$route['playerapi/payment/withdrawal/conditions']='playerapi/cashier/withdraw-conditions';
$route['playerapi/payment/withdrawal/conditions/completed']='playerapi/cashier/withdraw-conditions-completed';
// $route['playerapi/withdraw-conditions']='playerapi/cashier/withdraw-conditions';

$route['playerapi/payment/withdrawal']='playerapi/cashier/withdraw-requests';
$route['playerapi/withdraw-requests']='playerapi/cashier/withdraw-requests';

$route['playerapi/payment/withdrawal/pending']='playerapi/cashier/withdraw-requests/pending-amount';
$route['playerapi/withdraw-requests/pending-amount']='playerapi/cashier/withdraw-requests/pending-amount';

$route['playerapi/payment/deposit/upload']= 'playerapi/cashier/file-upload';


$route['playerapi/payment/settings']='playerapi/cashier/payment-settings';

// games
$route['playerapi/favorite-game']='playerapi/game/favorite';
$route['playerapi/favorite-game/(:any)']='playerapi/game/favorite/$1';
$route['playerapi/launch-game']='playerapi/games/launch-game';
$route['playerapi/launch-game-lobby']='playerapi/games/launch-game-lobby';
$route['playerapi/random-games']='playerapi/games/random-games';
$route['playerapi/games']='playerapi/games/search';
$route['playerapi/games/(:any)']='playerapi/games/search/$1';
$route['playerapi/game/list']='playerapi/gameList/list';
$route['playerapi/game/list/special']='playerapi/gameList/special';
$route['playerapi/game/launch']='playerapi/game/launch';
$route['playerapi/game/launch/lobby']='playerapi/game/launchLobby';
$route['playerapi/game/launch/demo/lobby']='playerapi/game/launchLobbyDemo';
$route['playerapi/game/launch/demo']='playerapi/game/launchDemo';
$route['playerapi/site/game/platform/list']='playerapi/games/platform-list';
$route['playerapi/event/list']='playerapi/event/list';
$route['playerapi/event/launch']='playerapi/event/launch';


//reports

$route['playerapi/reports/gamelog']='playerapi/reports/gamelog';
$route['playerapi/reports/promorequest']='playerapi/reports/promorequest';

// cms
$route['playerapi/cms/announcements']='playerapi/cms/announcements';
$route['playerapi/cms/announcements/(:any)']='playerapi/cms/announcements/$1';
$route['playerapi/messages']='playerapi/cms/messages';
$route['playerapi/messages/send']='playerapi/cms/messages/send';
$route['playerapi/messages/all/read']='playerapi/cms/messages/all-read';
$route['playerapi/messages/(:any)/thread-read']='playerapi/cms/messages/$1/thread-read';
$route['playerapi/messages/(:any)/reply']='playerapi/cms/messages/$1/reply';
$route['playerapi/site-announcements']='playerapi/cms/site-announcements';
$route['playerapi/player/cms/announcements'] = 'playerapi/cms/player-announcements';
$route['playerapi/player/cms/announcements/(:any)'] = 'playerapi/cms/player-announcements/$1';
// site info
$route['playerapi/site-config/(:any)']='playerapi/site_config/$1';
$route['playerapi/site-config/(:any)/(:any)']='playerapi/site_config/$1/$2';
$route['playerapi/site-properties/(:any)']='playerapi/site_properties/$1';
$route['playerapi/site-properties/(:any)/(:any)']='playerapi/site_properties/$1/$2';
$route['playerapi/site/traffic/aff/(:any)']='playerapi/site/traffic_stat/$1';
$route['playerapi/site/(:any)/(:any)']='playerapi/site/$1/$2';
$route['playerapi/site/(:any)']='playerapi/site/$1';

// kyc
$route['playerapi/player/kyc/settings']='playerapi/kyc/settings';
$route['playerapi/player/kyc/status']= 'playerapi/kyc/status';
$route['playerapi/player/kyc/upload']='playerapi/kyc/upload';
$route['playerapi/player/kyc/update']='playerapi/kyc/update';

//wallet
$route['playerapi/wallets/(:any)/(:any)']='playerapi/wallets/$1/$2';

// #region - promotion
$route['playerapi/public/campaigns'] = 'playerapi/campaigns/public';
$route['playerapi/public/campaigns/category'] = 'playerapi/campaigns/category';
$route['playerapi/public/campaign/info/(:any)'] = 'playerapi/campaigns/public/info/$1';
$route['playerapi/campaign/info/(:any)'] = 'playerapi/campaigns/info/$1';
$route['playerapi/campaign/redemption/apply'] = 'playerapi/redemption/apply';
$route['playerapi/campaigns'] = 'playerapi/campaigns/items';
$route['playerapi/campaigns/(:any)'] = 'playerapi/campaigns/items/$1';
$route['playerapi/promotion/cashback/setting'] = 'playerapi/promotions/cashback-setting';
$route['playerapi/promotion/referral/custom/info'] = 'playerapi/promotions/referral-custom-info';
// #endregion - promotion

// mission
$route['playerapi/player/missions'] = 'playerapi/missions/list';
$route['playerapi/player/missions/apply'] = 'playerapi/missions/apply';
$route['playerapi/player/missions/interact'] = 'playerapi/missions/interact';

// quests
//add quests crud
$route['playerapi/player/quest/category/list'] = 'playerapi/quest/category';
$route['playerapi/player/quest/list/(:any)'] = 'playerapi/quest/list/$1';
$route['playerapi/player/quest/progress'] = 'playerapi/quest/progress';
$route['playerapi/player/quest/claim'] = 'playerapi/quest/claim';
$route['playerapi/player/quest/interact'] = 'playerapi/quest/interact';
$route['playerapi/player/quest/requests'] = 'playerapi/quest/quest-requests';

$route['playerapi/player/lucky-code/list'] = 'playerapi/lucky_code/list';

// responsible gaming
$route['playerapi/responsible/self-exclusion'] = 'playerapi/responsible_game/apply/self-exclusion';
$route['playerapi/responsible/cool-off'] = 'playerapi/responsible_game/apply/cool-off';
$route['playerapi/responsible/deposit-limits'] = 'playerapi/responsible_game/apply/deposit-limits';
$route['playerapi/responsible/info'] = 'playerapi/responsible_game/info';

// chatform ai
$route['playerapi/player/init-chat'] = 'playerapi/chat/init';

//roulette
$route['playerapi/promotion/roulette/(:any)'] = 'playerapi/roulette/$1';
$route['playerapi/public/promotion/roulette/list'] = 'playerapi/roulette/list';
$route['playerapi/public/roulette/bonus/latest'] = 'playerapi/roulette/latest';

#region - crypto
$route['playerapi/crypto/info'] = 'playerapi/crypto/getAllAddress';
$route['playerapi/crypto/withdrawal'] = 'playerapi/crypto/postCryptoWithdrawal';
$route['playerapi/crypto/settings'] = 'playerapi/crypto/getCryptoSetting';

#endregion - crypto

$route['topup-advisory'] = 'player_center/topup_advisory';

#OGP-31786 player bet detail link
$route['bet_detail/(:any)/(:any)'] = 'async/get_bet_detail_link/$1/$2';
$route['bet_detail_with_token/(:any)/(:any)/(:any)'] = 'async/get_bet_detail_link_with_token/$1/$2/$3';

#region - notification
$route['playerapi/player/notification/list'] = 'playerapi/notification/list';
$route['playerapi/player/notification/uninformed'] = 'playerapi/notification/uninformed';
$route['playerapi/player/notification/informed'] = 'playerapi/notification/informed';
#endregion - notification

#region - tournament
$route['playerapi/player/tournament/apply'] = 'playerapi/tournament/apply';
$route['playerapi/tournament/rank/list'] = 'playerapi/tournament/rank-list';
$route['playerapi/player/tournament/rank/(:any)'] = 'playerapi/tournament/player-event-rank/$1';
$route['playerapi/player/tournament/apply/(:any)'] = 'playerapi/tournament/apply/$1';
$route['playerapi/tournament/detail'] = 'playerapi/tournament/detail/$1';

#endregion - tournament

/* End of file routes.php */
/* Location: ./application/config/routes.php */
