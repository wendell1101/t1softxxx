<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/game_api_mpoker.php';

/**
* Game Provider: V8poker
* Game Type: Poker/Cards
* Wallet Type: Transfer
*
* @category Game_platform
* @version not specified
* @copyright 2013-2024 tot
* Ticket: OGP-32826

**/

class Game_api_v8poker extends game_api_mpoker
{
    const ORIGINAL_TRANSACTIONS = 'v8poker_game_logs';
    public function __construct()
    {
        parent::__construct();
        $this->originalTable = self::ORIGINAL_TRANSACTIONS;

        $this->api_url = $this->getSystemInfo('url');
        $this->agent = $this->getSystemInfo('agent');
        $this->md5Key = $this->getSystemInfo('md5Key', '');
        $this->lineCode  = $this->getSystemInfo('linecode', '');
        $this->timeStamp = $this->CI->utils->getTimestampNow();
        $this->game_record_url = $this->getSystemInfo('game_record_url');
        $this->language = $this->getSystemInfo('language', '');
        $this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', '30');
        $this->prefix_generated = $this->getSystemInfo('prefix_generated', '71175_');


        // for encryption
        $this->desKey = $this->getSystemInfo('desKey', '78F8118832B62D9C');
        $this->encrypt_method = $this->getSystemInfo('encrypt_method', 'AES-128-ECB');

    }

    public function getPlatformCode()
    {
        return V8POKER_GAME_API;
    } 

    public function getLauncherLanguage($language)
    {
        $lang='';
        switch ($language) {
        	case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
                $lang = 'en-us'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
                $lang = 'zh-cn'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-id':
                $lang = 'id-id'; // indonesia
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-vn':
            case 'vi_vn':
                $lang = 'vi_vn'; // vietnamese
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
            case 'th':
                $lang = 'th-th'; // thai
                break;
            default:
                $lang = 'th'; // default as th as per provider api docs
                break;
        }
        return $lang;
	}

    public function processResultForLogin($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('processResultForLogin resultArr', $resultArr);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $language = $this->getVariableFromContext($params, 'lang');
        $returnType = $this->getVariableFromContext($params, 'returnType');
        $result = array();

        if($success && isset($resultArr['d']['url'])){

            if ($success && isset($resultArr['d']['url'])) {
                $url = $resultArr['d']['url'];
            
                // Check if the URL already contains a query string
                if (strpos($url, '?') !== false) {
                    if (strpos($url, 'lang=') !== false) {
                        // Update the existing lang parameter value
                        $lang = '?lang=' . $this->getLauncherLanguage($language);
                        $url = preg_replace('/(lang=)[^&]*/', $lang, $url);
                    } else {
                        // Append lang parameter to the existing query string
                        $url .= '?lang=' . $this->getLauncherLanguage($language);
                    }
                } else {
                    $url .= '?lang=' . $this->getLauncherLanguage($language);
                }
            
                $result['url'] = $url;
            }

        }
        $this->CI->utils->debug_log('processResultForLogin', $success);

        return array($success, $result);

    }
}
//end of class
