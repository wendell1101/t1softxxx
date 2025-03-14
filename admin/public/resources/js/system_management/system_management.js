/**
 * System_management.js
 * Shared JS routine for various controllers in System_management, including:
 * 		system_settings()
 * 		system_Features()
 * 		game_wallet_settings()
 * 		view_smtp_api_report()
 */
// general
var base_url = "/";
var imgloader = "/resources/images/ajax-loader.gif";

$(document).ready(function sidebar_highlight() {
	var path_chunks = document.location.pathname.split('/');
	var path = path_chunks.slice(2).join('/');

	console.log('path', path);

	switch (path) {
		case 'system_features' :
			$('a#viewSystemFeatures').addClass('active');
			break;
		case 'view_smtp_api_report' :
			$('a#view_smtp_report').addClass('active');
			break;
		case 'view_sms_api_settings' :
			$('a#view_sms_api_settings').addClass('active');
			break;
		case 'game_wallet_settings' :
			$('a#game_wallet_settings').addClass('active');
			break;
		// ** System settings group
		case 'system_settings/smart_backend' :
			// $('a#system_settings').click();
			$('a#system_settings').addClass('collapsed');
			$('#collapseSubmenu_sys_settings').addClass('in');
			$('a#sys_settings_smart_backend').addClass('active');
			scroll_sidebar_to_bottom();
			break;
		case 'system_settings/player_center' :
			// 	$('a#system_settings').click();
			$('a#system_settings').addClass('collapsed');
			$('#collapseSubmenu_sys_settings').addClass('in');
			$('a#sys_settings_player_center').addClass('active');
			scroll_sidebar_to_bottom();
			break;
		// OGP-17383: game setting group
		case 'transactionsDailySummaryReportSettings' :
			$('a#submenu_game_setting').click();
			$('#submenu_game_setting').addClass('active');
			$('a#transactionsDailySummaryReportSettings').addClass('active');
			scroll_sidebar_to_bottom();
			break;
		case 'view_player_center_api_domain' :
			$('a#viewPlayerDomains').addClass('active');
			scroll_sidebar_to_bottom();
			break;
		default:
			// game setting group
			switch (path_chunks[1]) {
				case 'game_api' :
					$('a#submenu_game_setting').click();
					$('#submenu_game_setting').addClass('active');
					scroll_sidebar_to_bottom();
					break;
				case 'game_description' :
				case 'game_type' :
					scroll_sidebar_to_bottom();
					break;
				default :
					break;
			}
			break;
	} // End switch

}); // End function sidebar_highlight

function scroll_sidebar_to_bottom() {
	setTimeout(function() {
		$('#sidebar-wrapper').scrollTop($('#sidebar-wrapper').prop('scrollHeight'));
	}, 500);
}


