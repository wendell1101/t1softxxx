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

$route['default_controller'] = "auth";
$route['404_override'] = '';

$route['pub/(:any)/(:any).js'] = 'pub/$2_js/$1';
$route['player/upload/(:any)'] = 'player_internal/player/$1';

$route['(:any)/(:any)/(:any)/refresh_session.gif'] = 'async/refresh_session/$1/$2/$3';
$route['remote-wallet/(:any)']='service_api/remote_wallet/$1';
$route['seamless-gateway/(:any)']='service_api/seamless_gateway/$1';

$route['validate.aspx'] = 'callback/game/10/ValidateMember';

// $route['__clockwork/(.*)'] = 'clockwork_controller/$1';

$route['player_management/usernames.csv'] = 'player_management/exportUsernames';
$route['ultraplay_service_api/(:any)'] = 'ultraplay_service_api/index/$1';

$route['elg_service_api/Users/(:any)/Tokens/(:any)/KeepAlive'] = 'extremelivegaming_service_api/prolongToken/$1/$2';
$route['elg_service_api/Cash/Users/(:any)/Transactions'] = 'extremelivegaming_service_api/transaction/$1';
$route['elg_service_api/Cash/Users/(:any)'] = 'extremelivegaming_service_api/getUserInfo/$1';
$route['rwb_service_api/(:any)'] = 'rwb_service_api/transaction/$1';
$route['ls_service_api/apikey/(:any)'] = 'ls_casino_service_api/index/$1';
$route['redtiger_service_api/(:any)'] = 'redtiger_service_api/index/$1';
$route['redtiger_service_api/(:any)/(:any)'] = 'redtiger_service_api/index/$1/$2';
$route['Xpress/(:any)'] = 'golden_race_service_api/$1';
$route['red_rake_service_api/'] = 'red_rake_service_api/index';
$route['habanero_service_api/(:any)'] = 'habanero_service_api/$1';
$route["evolution_service_api/(:any)"] = "evolution_seamless_service_api/index/$1";
$route["evolution_service_api/(:any)/(:any)"] = "evolution_seamless_service_api/index/$1/$2";
$route["idn_evolution_service_api/(:any)/(:any)"] = "idn_evolution_seamless_service_api/index/$1/$2";
$route['slot_factory_service_api/cmwallet'] = 'slot_factory_service_api/index';
$route['sexybaccarat_service_api/(:any)'] = 'sexybaccarat_service_api/index';
$route['sexybaccarat_remote_service_api/(:any)'] = 'sexybaccarat_remote_service_api/index';
$route['sv388_remote_service_api/(:any)'] = 'sv388_remote_service_api/index';

$route['dg_service_api/user/(:any)/(:any)'] = 'dg_service_api/user/$1/$2';
$route['dg_service_api/account/(:any)/(:any)'] = 'dg_service_api/account/$1/$2';

$route['pragmatic_play_service_api/(:any).html'] = 'pragmaticplay_service_api/index/5632/$1';
$route['pragmatic_play_common_service_api/(:any)/(:any).html'] = 'pragmaticplay_service_api/index/$1/$2';
$route['pragmatic_play_common_service_api/(:any)/session/expired'] = 'pragmaticplay_service_api/index/$1/remove_token';

# $route['ag_service_api/rest/integration/(:any)'] = 'ag_seamless_service_api_old/index/$1';
$route['lucky_streak_service_api/(:any)'] = 'Lucky_streak_seamless_service_api/index/$1';

$route['iconic_service_api/(:any)'] = 'iconic_service_api/$1';

$route['sa_gaming_service_api/(:any)'] = 'sa_gaming_service_api/$1';
$route['sagaming_service_api/(:any)'] = 'sa_gaming_service_api/$1';
$route['sa_gaming_common_service_api/(:any)'] = 'sa_gaming_common_service_api/$1';
$route['habanero_service_api/(:any)/(:any)'] = 'habanero_service_api/$1/$2';
$route['vivogaming_service_api/(:any)/(:any)'] = 'vivogaming_service_api/$1/$2';
$route['amb_service_api/(:any)/(:any)'] = 'amb_service_api/$2/$1';

$route['remote_logs/(:any)'] = 'player_internal/remote_logs/$1';

$route['amg/notifications/v1/(:any)'] = 'callback/game/5672/$1';
$route['ruby_play_services/(:any)'] = 'rubyplay_service_api/$1';
$route['betgames_service_api'] = 'betgames_seamless_service_api/index';
$route['netent_service_api/(:any)/walletserver/players/(:any)/account/(:any)'] = 'netent_seamless_service_api/index/$3/$1/$2'; // $1 api id, $2 username, $3 method
$route['isb_seamless_service_api/(:any)'] = 'isb_seamless_service_api/index/$1';
$route['big_gaming_service_api/(:any)'] = 'big_gaming_seamless_service_api/index/$1';
$route['pgsoft_seamless_service_api/(:any)'] = 'pgsoft_seamless_service_api/index/$1/5865';
$route['live12_seamless_service_api/(:any)'] = 'pgsoft_seamless_service_api/index/$1/5865';
$route['lotto97_seamless_service_api/(:any)/(:any)/api/lotto97/(:any)'] = 'lotto97_seamless_service_api/index/$1/$2/$3';

$route['fast_track_service_api/(:any)'] = 'fast_track_service_api/$1';
$route['fast_track_service_api/bonus/credit'] = 'fast_track_service_api/bonusCredit';
$route['fast_track_service_api/bonus/credit/funds'] = 'fast_track_service_api/bonusCreditFunds';

$route['ibc_onebook_service_api/(:any)/(:any)'] = 'ibc_onebook_service_api/index/$1/$2';
$route['mgplus_service_api/(:any)/(:any)'] = 'mgplus_service_api/index/$1/$2';
$route['fg_campaign_api/(:any)'] = 'fg_campaign_api/index/$1';
$route['t1lottery_service_api/(:any)/(:any)'] = 't1lottery_seamless_service_api/$2/$1';
$route['ezugi_seamless_service_api/(:any)/(:any)'] = 'ezugi_seamless_service_api/index/$1/$2';

$route['kagaming_service_api/(:any)'] = 'kagaming_service_api/$1';
$route['bdmjoker_service_api/(:any)'] = 'bdmjoker_service_api/index/$1';
$route['loto_service_api/(:any)'] = 'loto_service_api/$1';
$route['joker_service_api/(:any)'] = 'bdmjoker_service_api/index/$1';
$route['jili_seamless_service_api/(:any)/(:any)'] = 'jili_seamless_service_api/$2/$1';
$route['bistro_service_api/(:any)/(:any)'] = 'bistro_seamless_service_api/$2/$1';

// $route['pgsoft_seamless_game_service_api/(:any)/(:any)'] = 'pgsoft_seamless_game_service_api/$1/$2';
$route['pgsoft_seamless_game_service_api/(:any)/VerifySession'] = 'pgsoft_seamless_game_service_api/verifySession/$1';
$route['pgsoft_seamless_game_service_api/(:any)/Cash/Get'] = 'pgsoft_seamless_game_service_api/cashGet/$1';
$route['pgsoft_seamless_game_service_api/(:any)/Cash/TransferInOut'] = 'pgsoft_seamless_game_service_api/cashTransferInOut/$1';
$route['pgsoft_seamless_game_service_api/(:any)/Cash/Adjustment'] = 'pgsoft_seamless_game_service_api/cashAdjustment/$1';
$route['pgsoft_seamless_game_service_api/(:any)/Cash/Rollback'] = 'pgsoft_seamless_game_service_api/rollback/$1';
$route['truco_service_api/(:any)'] = 'truco_service_api/index/$1';
$route['pariplay_service_api/(:any)'] = 'pariplay_service_api/index/$1';
$route['pariplay_bonus_service_api/(:any)'] = 'pariplay_service_api/bonus/$1';
$route['hotgraph_seamless_service_api/(:any)'] = 'hotgraph_seamless_service_api/index/6061/$1';
$route['smash_chat_service_api/(:any)'] = 'smash_chat_service_api/$1';
$route['digitain_seamless_game_service_api/(:any)'] = 'digitain_seamless_game_service_api/index/$1';
$route['bti_service_api/(:any)'] = 'bti_service_api/index/$1';
$route['whl_bti_service_api/(:any)'] = 'bti_service_api/whl/$1';
$route['qt_hacksaw_seamless_service_api/(:any)'] = 'qt_hacksaw_seamless_service_api/$1';

$route['evoplay_seamless_service_api/(:any)/(:any)'] = 'evoplay_seamless_service_api/index/$1/$2/$3';
$route['idnlive_seamless_service_api/(:any)/(:any)'] = 'idnlive_seamless_service_api/index/$1/$2';
$route['cq9_seamless_service_api/(:any)/player/check/(:any)'] = 'cq9_seamless_service_api/index/$1/checkPlayer/$2';
$route['cq9_seamless_service_api/(:any)/transaction/balance/(:any)'] = 'cq9_seamless_service_api/index/$1/balance/$2';
$route['cq9_seamless_service_api/(:any)/transaction/game/(:any)'] = 'cq9_seamless_service_api/index/$1/$2';
$route['cq9_seamless_service_api/(:any)/transaction/user/(:any)'] = 'cq9_seamless_service_api/index/$1/$2';
$route['cq9_seamless_service_api/(:any)/transaction/record/(:any)'] = 'cq9_seamless_service_api/index/$1/record/$2';
$route['softswiss_seamless_service_api/(:any)'] = 'softswiss_seamless_service_api/index/$1';
$route['bgaming_seamless_service_api/(:any)/(:any)'] = 'bgaming_seamless_service_api/index/$1/$2';
$route['wazdan_seamless_service_api/(:any)/(:any)'] = 'wazdan_seamless_service_api/index/$1/$2';
$route['yl_nttech_seamless_service_api/(:any)'] = 'yl_nttech_seamless_service_api/index/$1';
$route['ag_seamless_service_api/(:any)/rest/integration/(:any)'] = 'ag_seamless_service_api/index/$1/$2';
$route['tada_seamless_service_api/(:any)/(:any)'] = 'tada_seamless_service_api/index/$1/$2';
$route['spadegaming_seamless_service_api/(:any)'] = 'spadegaming_seamless_service_api/index/$1';
$route['booming_seamless_service_api/(:any)/(:any)/(:any)'] = 'booming_seamless_service_api/index/$1/$2/$3';
$route['cmd_seamless_service_api/(:any)/(:any)'] = 'cmd_seamless_service_api/index/$1/$2';
$route['awc_universal_seamless_service_api/(:any)'] = 'awc_universal_seamless_service_api/index/$1';
$route['sv388_awc_seamless_service_api/(:any)'] = 'awc_universal_seamless_service_api/index/$1';
$route['dcs_universal_seamless_service_api/(:any)'] = 'dcs_universal_seamless_service_api/index/$1/$2';
$route['king_maker_seamless_service_api/(:any)/wallet/(:any)'] = 'king_maker_seamless_service_api/index/$1/$2';
$route['betgames_seamless_service_api/(:any)'] = 'betgames_seamless_service_api/index/$1';
$route['twain_seamless_service_api/(:any)'] = 'betgames_seamless_service_api/index/$1';
$route['flow_gaming_seamless_service_api/(:any)/v1/(:any)'] = 'flow_gaming_seamless_service_api/index/$1/$2';
$route['fg_quickspin_seamless_service_api/(:any)/v1/(:any)'] = 'flow_gaming_seamless_service_api/index/$1/$2';
$route['hacksaw_seamless_service_api/(:any)'] = 'hacksaw_seamless_service_api/index/$1';
$route['bng_seamless_service_api/(:any)'] = 'bng_seamless_service_api/index/$1';
$route['rtg_seamless_service_api/(:any)/account/getbalance/membercode/(:any)'] = 'rtg_seamless_service_api/index/$1/getBalance/$2';
$route['rtg_seamless_service_api/(:any)/account/(:any)'] = 'rtg_seamless_service_api/index/$1/$2';
$route['rtg_seamless_service_api/(:any)/(:any)'] = 'rtg_seamless_service_api/index/$1/$2';
$route['one_touch_seamless_service_api/(:any)/user/(:any)'] = 'one_touch_seamless_service_api/index/$1/$2';
$route['one_touch_seamless_service_api/(:any)/transaction/(:any)'] = 'one_touch_seamless_service_api/index/$1/$2';
$route['one_touch_seamless_service_api/(:any)/(:any)'] = 'one_touch_seamless_service_api/index/$1/$2';
$route['ab_seamless_service_api/(:any)/(:any)'] = 'ab_seamless_service_api/index/$1/$2';
$route['mpoker_seamless_service_api/(:any)/wallet/(:any)'] = 'mpoker_seamless_service_api/index/$1/$2';
$route['pt_seamless_service_api/(:any)/(:any)'] = 'pt_seamless_service_api/index/$1/$2';
$route['fa_seamless_service_api/helper/(:any)'] = 'fa_seamless_service_api/helper/$1';
$route['fa_seamless_service_api/wallet/(:any)'] = 'fa_seamless_service_api/wallet/$1';

// $route['mpoker_seamless_service_api/(:any)/wallet/(:any)'] = 'mpoker_service_api/index/$1/$2';

$route['flow_gaming_service_api/(:any)/testRemoveFromRedis'] = 'flow_gaming_service_api/testRemoveFromRedis/$1';
$route['flow_gaming_service_api/(:any)/testGetAllFromRedis'] = 'flow_gaming_service_api/testGetAllFromRedis/$1';

## PINNACLE SEAMLESS
$route['pinnacle_seamless_service_api/(:any)/v1/ping'] = 'pinnacle_seamless_service_api/ping/$1';
$route['pinnacle_seamless_service_api/(:any)/v1/(:any)/wallet/usercode/(:any)/balance'] = 'pinnacle_seamless_service_api/balance/$1/$2/$3';
$route['pinnacle_seamless_service_api/(:any)/v1/(:any)/wagering/usercode/(:any)/request/(:any)'] = 'pinnacle_seamless_service_api/wagering/$1/$2/$3/$4';

$route['ygg_service_api/(:any)/extapi/ygg/(:any).json'] = 'ygg_service_api/$2/$1';
$route['ygg_service_api/(:any).json'] = 'ygg_service_api/$1/6157';
$route['png_service_api/(:any)/(:any)'] = 'png_service_api/index/$1/$2';
$route['wm_seamless_service_api/(:any)'] = 'wm_seamless_service_api/index/$1';

$route['bgsoft_service_api/(:any)/(:any)'] = 'bgsoft_seamless_service_api/$2/$1';
$route['t1games_service_api/(:any)/(:any)'] = 'bgsoft_seamless_service_api/$2/$1';
$route['skywind_seamless_service_api/api/(:any)'] = 'skywind_seamless_service_api/api/$1';
$route['kplay_service_api/(:any)'] = 'kplay_service_api/api/$1';
$route['we_service_api/(:any)'] = 'we_service_api/api/$1';
$route['bigpot_service_api/(:any)'] = 'bigpot_service_api/api/$1';
$route['fc_service_api/(:any)'] = 'fc_service_api/api/$1';
$route['fc_helper_service_api/(:any)'] = 'fc_service_api/helper_service/$1';
$route['ameba_service_api/(:any)/(:any)'] = 'ameba_service_api/api/$1/$2';
$route['ameba_helper_service_api/(:any)'] = 'ameba_service_api/helper_service/$1';
$route['cherry_gaming_service_api/(:any)/(:any)'] = 'cherry_gaming_service_api/index/$1/$2';
$route['beter_service_api/(:any)/(:any)'] = 'beter_service_api/index/$1/$2';
$route['beter_sports_service_api/(:any)/(:any)/(:any)'] = 'beter_sports_service_api/index/$1/$2/$3';
$route['beter_sports_service_api/(:any)'] = 'beter_sports_service_api/helper_service/$1';
$route['gfg_seamless_service_api/api/Balance/(:any)'] = 'gfg_seamless_service_api/balance_api/$1';
$route['betby_seamless_service_api/(:any)/(:any)'] = 'betby_seamless_service_api/api/$1/$2';
$route['betby_seamless_service_api/(:any)'] = 'betby_seamless_service_api/api/$1';
$route['ttg_seamless_service_api/(:any)'] = 'ttg_seamless_service_api/index/$1';
$route['ultraplay_seamless_service_api/(:any)/(:any)'] = 'ultraplay_seamless_service_api/index/$1/$2';

$route['betixon_seamless_service_api/(:any)/(:any)'] = 'betixon_seamless_service_api/index/$1/$2';

$route['mgw_seamless_game_service_api/(:any)/AuthenticateToken'] 	 = 'mgw_seamless_game_service_api/authenticate/$1';
$route['mgw_seamless_game_service_api/(:any)/GetBalance'] 		 	 = 'mgw_seamless_game_service_api/getBalance/$1';
$route['mgw_seamless_game_service_api/(:any)/PlaceBet'] 		 	 = 'mgw_seamless_game_service_api/placeBet/$1';
$route['mgw_seamless_game_service_api/(:any)/SettleBet'] 		 	 = 'mgw_seamless_game_service_api/settleBet/$1/settle';
$route['mgw_seamless_game_service_api/(:any)/UnsettleBet'] 		 	 = 'mgw_seamless_game_service_api/settleBet/$1/unsettle';
$route['mgw_seamless_game_service_api/(:any)/Encrypt'] 				 = 'mgw_seamless_game_service_api/EncryptDecrypt/$1/encrypt';
$route['mgw_seamless_game_service_api/(:any)/Decrypt'] 				 = 'mgw_seamless_game_service_api/EncryptDecrypt/$1/decrypt';
$route['yeebet_service_api/(:any)'] = 'yeebet_service_api/api/$1';
$route['won_service_api/(:any)'] = 'won_service_api/api/$1';
$route['im_seamless_service_api/(:any)/(:any)'] = 'im_seamless_service_api/index/$1/$2';
$route['spinomenal_seamless_service_api/(:any)/(:any)'] = 'spinomenal_seamless_service_api/index/$1/$2';

$route['smartsoft_seamless_service_api/(:any)']  = 'smartsoft_seamless_service_api/index/$1';
$route['astar_seamless_service_api/(:any)']  = 'astar_seamless_service_api/index/$1';
$route['endorphina_seamless_service_api/(:any)']  = 'endorphina_seamless_service_api/index/$1';

$route['bet_detail/(:any)/(:any)'] = 'async/get_bet_detail_link/$1/$2';
$route['belatra_service_api/(:any)/(:any)'] = 'belatra_service_api/api/$1/$2';
$route['nextspin_seamless_service_api/(:any)/(:any)'] = 'nextspin_seamless_service_api/$1/$2';
$route['pegasus_seamless_service_api/(:any)']  = 'pegasus_seamless_service_api/index/$1';
$route['spinix_seamless_service_api'] = 'spinix_seamless_service_api';
$route['spinix_seamless_service_api/users/getBalance'] = 'spinix_seamless_service_api/getBalance';
$route['spinix_seamless_service_api/round/payout'] = 'spinix_seamless_service_api/roundPayout';
$route['fastspin_seamless_service_api/(:any)']  = 'fastspin_seamless_service_api/index/$1';
$route['dragonsoft_seamless_service_api/api/wallet/(:any)']  = 'dragoonsoft_seamless_service_api/index/$1';

// region start - tournament
$route['tournament_management/tournament/(:num)'] = 'tournament_management/tournament/update/$1';
$route['tournament_management/schedule/(:num)'] = 'tournament_management/schedule/update/$1';
$route['tournament_management/event/(:num)'] = 'tournament_management/event/update/$1';
// region end - tournament
$route['popokgaming_service_api/(:any)']  = 'popok_gaming_seamless_service_api/index/$1';

$route['creedroomz_seamless_service_api/(:any)']  = 'creedroomz_seamless_service_api/index/$1';

$route['redgenn_service_api/(:any)'] = 'redgenn_seamless_service_api/index/$1';
$route['get_gameplatform_gamelist/(:any)']  = 'gameprovider_gamelist_service_api/getGamePlatformList/$1';
$route['download_game_list/(:any)']  = 'gameprovider_gamelist_service_api/downloadGameList/$1';
$route['simpleplay_seamless_service_api/(:any)'] = 'simpleplay_seamless_service_api/index/$1';
$route['fb/callback/(:any)'] = 'fbsports_seamless_service_api/index/$1';#FBSPORTS_SEAMLESS_GAME_API routes

$route['oneapi_seamless_service_api/(:any)'] = 'oneapi_seamless_service_api/index/$1';
// $route['MegaXcess/servlet/getTransaction'] = 'gameprovider_transaction_service_api/getTransaction';
$route['MegaXcess/servlet/(:any)'] = 'mega_xcess_service_api/index/$1';
$route['lightning_seamless_service_api/(:any)']  = 'lightning_seamless_service_api/index/$1';

$route['aviatrix_seamless_service_api/(:any)']  = 'aviatrix_seamless_service_api/index/$1';

$route['holi_seamless_service_api/(:any)']  = 'holi_seamless_service_api/index/$1';
$route['worldmatch_seamless_service_api/(:any)']  = 'worldmatch_seamless_service_api/index/$1';
$route['tomhorn_service_api/(:any)/(:any)'] = 'tom_horn_seamless_service_api/index/$1/$2';

$route['bfgames_seamless_service_api/(:any)']  = 'bfgames_seamless_service_api/index/$1';

$route['get_gameplatform_logs/(:any)']  = 'gameprovider_service_api/getGamePlatformLogs/$1';
$route['get_gameplatform_list/(:any)']  = 'gameprovider_service_api/getGamePlatformList/$1';
$route['jgameworksapi/(:any)']  = 'jgameworks_seamless_service_api/index/$1';
$route['jgameworksapi/(:any)/(:any)']  = 'jgameworks_seamless_service_api/index/$1/$2';
$route['5ggaming/api/(:any)']  = 'fiveg_gaming_seamless_service_api/api/$1';
$route['playstar_seamless_service_api/(:any)/(:any)']  = 'playstar_seamless_service_api/index/$1/$2';
$route['idn_playstar_seamless_service_api/(:any)/(:any)']  = 'playstar_seamless_service_api/index/$1/$2';

$route['idnplay_seamless_service_api/(:any)'] = 'idnplay_seamless_service_api/index/$1';

/* End of file routes.php */
/* Location: ./application/config/routes.php */

