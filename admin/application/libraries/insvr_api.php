<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Insvr_api {

    private $uris=[];
    private $settings = [];
    private $default_connect_timeout = 0;

    public function __construct() {

        $this->uris = [];
        // CAABM = CreateAndApplyBonusMulti
        $this->uris['live']['CAABM'] = 'https://ws.insvr.com/jsonapi/createandapplybonusmulti';
        $this->uris['test']['CAABM'] = 'https://ws-test.insvr.com/jsonapi/createandapplybonusmulti';

		$this->CI =& get_instance();
        $this->utils=$this->CI->utils;
        $this->reset();
    }

    public function reset(){
        $this->settings['CAABM'] = $this->CAABM_getDefaultSettings(); // CAABM = CreateAndApplyBonusMulti
    }

    public function getHabaneroApiList(){
        $habanero_api_list = [];
        $habanero_api_list[] = HABANERO_SEAMLESS_GAMING_API;

		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_IDR1_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_CNY1_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_THB1_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_MYR1_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_VND1_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_USD1_API;
        // IDR
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_IDR2_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_IDR3_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_IDR4_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_IDR5_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_IDR6_API;
        $habanero_api_list[] = HABANERO_SEAMLESS_GAMING_IDR7_API;
        // CNY
        $habanero_api_list[] = HABANERO_SEAMLESS_GAMING_CNY2_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_CNY3_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_CNY4_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_CNY5_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_CNY6_API;
        $habanero_api_list[] = HABANERO_SEAMLESS_GAMING_CNY7_API;

        // THB
        $habanero_api_list[] = HABANERO_SEAMLESS_GAMING_THB2_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_THB3_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_THB4_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_THB5_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_THB6_API;
        $habanero_api_list[] = HABANERO_SEAMLESS_GAMING_THB7_API;
        // MYR
        $habanero_api_list[] = HABANERO_SEAMLESS_GAMING_MYR2_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_MYR3_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_MYR4_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_MYR5_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_MYR6_API;
        $habanero_api_list[] = HABANERO_SEAMLESS_GAMING_MYR7_API;
        // VND
        $habanero_api_list[] = HABANERO_SEAMLESS_GAMING_VND2_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_VND3_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_VND4_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_VND5_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_VND6_API;
        $habanero_api_list[] = HABANERO_SEAMLESS_GAMING_VND7_API;
        // USD
        $habanero_api_list[] = HABANERO_SEAMLESS_GAMING_USD2_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_USD3_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_USD4_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_USD5_API;
		$habanero_api_list[] = HABANERO_SEAMLESS_GAMING_USD6_API;
        $habanero_api_list[] = HABANERO_SEAMLESS_GAMING_USD7_API;
        return $habanero_api_list;
    } // EOF getHabaneroApiList

    public function getSettingsByUriKey($uriKey = 'CAABM'){
        return $this->settings[$uriKey];
    } // EOF getSettingsByUriKey

    public function getUriWithKey($uriKey, $isTestUri){
        $uri = $this->uris['live'][$uriKey];
        if($isTestUri){
            $uri = $this->uris['test'][$uriKey];
        }
        return $uri;
    } // EOF getUriWithKey

    /**
     * Send the settings To ws.insvr.com.
     * CAABM = CreateAndApplyBonusMulti
     * @param array $theSettings4ICS The settings for send to "ws.insvr.com".
     * @param bool $isTestUri The switch for target URI with live or test.Default is test uri.
     * @return array The return Array,
     * - retuen[0] $header The header of response.
     * - retuen[1] $content The content of response.
     * - retuen[2] $url The target Uri.
     * - retuen[3] $params The params of the request.
     */
    public function send2Insvr($uriKey = 'CAABM', $isTestUri = true){

        $uri = $this->getUriWithKey($uriKey, $isTestUri);
        // $uri = $this->uris['live'][$uriKey];
        // if($isTestUri){
        //     $uri = $this->uris['test'][$uriKey];
        // }

        $url = $uri;
        $method = 'POST';
        $params = json_encode($this->settings[$uriKey]);
        $curlOptions = [];

        // $default_connect_timeout = $this->getConfig('default_connect_timeout');
        $curlOptions[CURLOPT_CONNECTTIMEOUT] = $this->default_connect_timeout;

        $curlOptions[CURLOPT_RETURNTRANSFER] = true;
        $curlOptions[CURLOPT_ENCODING] = '';
        $curlOptions[CURLOPT_MAXREDIRS] = 10;
        // $curlOptions[CURLOPT_TIMEOUT] = 0;
        // $curlOptions[CURLOPT_FOLLOWLOCATION] = true; // in utils::callHttp()
        $curlOptions[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
        $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
        $headers = ['Content-Type:application/json'];

        $this->utils->debug_log('start callHttp', $url, $method, $params, $curlOptions, $headers);

        list($header, $content, $statusCode, $statusText, $errCode, $error, $obj)=
            $this->utils->callHttp($url, $method, $params, $curlOptions, $headers);

            // "X-HABA-ErrorCode" – numeric code detailed in grid below
            // "X-HABA-ErrorName" – Name detailed in grid below
            // "X-HABA-ErrorDetail" – Additional information about the error
            //
            // Code Name
            // 1 InvalidRequest See the ErrorDesc for details on missing/wrong parameters
            // 2 SecurityError IP Whitelisting error, wrong credentials etc
            // 3 SystemMaintenanceMode System is in Maintenance Mode
            // 4 RateLimited Your requests are rate limited. Please slow down rate of requests
            // 5 GeneralError See ErrorDesc for details
            // 6 DataDelayedRetry There is a delay in reporting data. Please retry the same request later
            // 7 GroupApiQueryDisabled Contact support to enable this method
            // 8 ConfigurationRequired Contact support to enable feature
            // 9 PlayerNotFound The player not found for the method

            if( !empty($header['X-HABA-ErrorCode']) ){
                $xErrorCode = $header['X-HABA-ErrorCode'];
                $xErrorName =$header['X-HABA-ErrorName'];
                $xErrorDetail =$header['X-HABA-ErrorDetail'];
                $this->utils->debug_log('headers', $header, 'xErrorCode',$xErrorCode);
            }

        $this->utils->debug_log('end callHttp', $header, $content, $statusCode, $statusText, $errCode, $error);
        return [$header, $content, $url, $params];
    } // EOF send2Insvr

    /**
     * Get CreateAndApplyBonusMulti default settings (Habanero)
     * CAABM = CreateAndApplyBonusMulti
     *
     * @return array The Array of param,json
     */
    public function CAABM_getDefaultSettings(){
        // "BrandId": "3aaf0577-3601-ea11-828b-281878586926",
        // "APIKey": "0F1672D0-1B64-49BF-9238-DAAD174F7884",

        /*
        "couponCurrencyData": [
            {
                "CurrencyCode": "THA",
                "CoinPosition": 0
            },
            {
                "CurrencyCode": "TRY",
                "CoinPosition": 0
            },
            {
                "CurrencyCode": "EUR",
                "CoinPosition": 0
            }
        ],
        */
        $jsonStr = <<<EOF
        {
            "BrandId": "",
            "APIKey": "",
            "ReplaceActiveCoupon": true,
            "CouponTypeId": 5,
            "DtStartUTC": "20200724120000",
            "DtEndUTC": "20201231120000",
            "ExpireAfterDays": 3,
            "MaxRedemptionsPerPlayer": 10,
            "MaxRedemptionsForBrand": 1000,
            "MaxRedemptionIntervalId": 0,
            "WagerMultiplierRequirement": 0,
            "MaxConversionToRealMultiplier": 0,
            "NumberOfFreeSpins": 10,
            "GameKeyNames": [
            ],
            "couponCurrencyData": [
                {
                    "CurrencyCode": "THA",
                    "CoinPosition": 0
                }
            ],
            "QueueUnregisteredPlayers": true,
            "CreatePlayerIfNotExist": true,
            "Players": [
            ]
        }
EOF;

/* Example,
            "BrandId": "3aaf0577-3601-ea11-828b-281878586926",
            "APIKey": "0F1672D0-1B64-49BF-9238-DAAD174F7884",

            "DtStartUTC": "20200724120000",
            "DtEndUTC": "20201231120000",

            "GameKeyNames": [
              "SGShaolinFortunes100"
            ],

            "couponCurrencyData": [
              {
                "CurrencyCode": "TRY",
                "CoinPosition": 0
              },
              {
                "CurrencyCode": "EUR",
                "CoinPosition": 0
              }
            ],

            "Players": [
              {
                "Username": "amcgno7jbvw3"
              }
            ]
*/
        $json = json_decode($jsonStr, true);

        return $json;
    } // EOF CAABM_getDefaultSettings

    /**
     * Update the Setting from theSettings4CAABM
     * CAABM = CreateAndApplyBonusMulti
     *
     * @todo,
     * - setup the secrets,
     * "BrandId": "3aaf0577-3601-ea11-828b-281878586926",
     * "APIKey": "0F1672D0-1B64-49BF-9238-DAAD174F7884",
     * - from now to the end of year.
     * "DtStartUTC": "20200724120000",
     * "DtEndUTC": "20201231120000",
     * - the Currency?
     * couponCurrencyData
     *
     *
     * @param array $theUpdateSettings
     * @param array $uriKey The target mothed key. ex: CAABM = CreateAndApplyBonusMulti
     * @return array The updated Settings4CAABM array
     */
    public function CAABM_updateSetting($theUpdateSettings, $uriKey = 'CAABM') {

        $defaults = $this->CAABM_getDefaultSettings();
        $defaultsKeys = array_keys($defaults);

        foreach($theUpdateSettings as $settingKeyStr => $settingValue){
            if( in_array($settingKeyStr, $defaultsKeys) ){
                $this->settings[$uriKey][$settingKeyStr] = $settingValue;
            }
        }
        // $this->settings[$uriKey] = array_merge($this->settings[$uriKey], $theUpdateSettings);
        return $this->settings[$uriKey];
    } // EOF CAABM_updateSetting

    /**
     * Add a GameKeyName into Settings
     * CAABM = CreateAndApplyBonusMulti
     *
     * @param string $gameKeyName The field, "game_description.game_code".
     * @param array $uriKey The target mothed key. ex: CAABM = CreateAndApplyBonusMulti
     * @return array the added GameKeyNames only
     */
    public function CAABM_addGameKeyNameToSettings($gameKeyName, $uriKey = 'CAABM'){
        $gameKeyNames = $this->settings[$uriKey]['GameKeyNames'];
        $gameKeyNames[] = $gameKeyName;
        $this->settings[$uriKey]['GameKeyNames'] = $gameKeyNames;
        return $gameKeyNames;
    }// CAABM_addGameKeyNameToSettings

    /**
     * add the Player into the CAABM Settings.
     * CAABM = CreateAndApplyBonusMulti
     *
     * @param string $username The username of player for the game, should be the field, game_provider_auth.login_name.
     * The return of game_provider_auth::getGameUsernameByPlayerId().
     * @param string $currencyCode Patch for the response,"Currency is required to create a player".
     * @param array $uriKey The target mothed key. ex: CAABM = CreateAndApplyBonusMulti
     * @return void
     */
    public function CAABM_addPlayerToSettings($username, $currencyCode = '',  $uriKey = 'CAABM'){
        $players = $this->settings[$uriKey]['Players'];

        if( empty($currencyCode) ){
            if( ! empty($this->settings[$uriKey]['couponCurrencyData'][0]['CurrencyCode']) ){
                $currencyCode = $this->settings[$uriKey]['couponCurrencyData'][0]['CurrencyCode'];
            }
        }

        $player = ['Username' => $username];
        if ( ! empty($currencyCode) ){
            $player['CurrencyCode'] = $currencyCode;
        }

        array_push( $players, $player);
        $this->settings[$uriKey]['Players'] = $players;
        return $players;
    }// CAABM_addPlayerToSettings

}// EOF insvr