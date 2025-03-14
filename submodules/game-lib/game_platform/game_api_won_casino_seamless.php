<?php
require_once dirname(__FILE__) . '/game_api_yeebet_seamless.php';
/**
 * Won casino Single Wallet API Document
 * OGP-31571
 *
 * @author  Jerbey Capoquian
 *
 * casino development handle by yeebet
 * 
 *
 * By function:
    
 *
 * 
 * Related File

     - yeebet_service_api.php
 */


class Game_api_won_casino_seamless extends Game_api_yeebet_seamless {

    const ORIGINAL_TRANSACTION_TABLE = 'won_casino_seamless_wallet_transactions';
    const ORIGINAL_LOGS_TABLE_NAME = 'won_casino_seamless_game_logs';
    const POST = 'POST';
    const GET = 'GET';
    
    public function __construct() {
        parent::__construct();
        $this->original_transaction_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->api_url = $this->getSystemInfo('url');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->currency = $this->getSystemInfo('currency', "PHP");
        $this->redirect = $this->getSystemInfo('redirect', false);
        $this->force_lang = $this->getSystemInfo('force_lang', false);
        $this->language_code = $this->getSystemInfo('language_code', 2);
        #for launching
        $this->app_id = $this->getSystemInfo('app_id', 'xtdDA1UWYVNH');
        $this->secret_key = $this->getSystemInfo('secret_key', 'A0C0CB27404DCC05624B3B6EBC6311DA');
        #for seamless
        $this->seamless_app_id = $this->getSystemInfo('seamless_app_id', 'T1SoftPHPSTG');
        $this->seamless_secret_key = $this->getSystemInfo('seamless_secret_key', '9f8c2eee3f4338d5667445df91e0f0fc');

        $this->commratio = $this->getSystemInfo('commratio');
        $this->quotas = $this->getSystemInfo('quotas');
        $this->portrait = $this->getSystemInfo('portrait');
        $this->state = $this->getSystemInfo('state');
        $this->list_of_method_for_force_error = $this->getSystemInfo('list_of_method_for_force_error', []);
        $this->allow_multiple_settlement = $this->getSystemInfo('allow_multiple_settlement', false);
        $this->gameid_prefix = $this->getSystemInfo('gameid_prefix');# "stg" for staging only,
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', false);
    }

    public function getPlatformCode() {
        return WON_CASINO_SEAMLESS_GAME_API;
    }
}
