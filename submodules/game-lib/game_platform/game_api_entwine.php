<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Get platform code
 * * Login/logout player
 * * Check Player's status if blocked
 * * Generates Token for the Player
 * * Decodes XML result to Array
 * * Test Account
 * * Deposit to game
 * * Withdraw from game
 * * Synchronize player account
 * * Check Player Balance
 * * Check Player Daily Balance
 * * Check Game Records
 * * Check Forward Game
 * * Synchronize Original Game Logs
 * * Retrieve XML from local
 * * Filter XML
 * * Extract XML Record
 * * Get Game Description Information
 * * Revert Broken Game
 * *
 * All below behaviors are not yet implemented
 * * Check Login Token
 * * Check Total Betting Amount
 * * Check Transaction
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game API
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
/*extra info{
    url
    entwine_websinglelogin_url
    entwine_mobilesinglelogin_url
    entwine_vendor_id
    entwine_merchant_name
    entwine_currency_id
    entwine_lang_id
    entwine_token_timeout
    entwine_game_records_path
}*/
class Game_api_entwine extends Abstract_game_api {
    private $api_url;
    private $entwine_websinglelogin_url;
    private $entwine_mobilesinglelogin_url;
    private $entwine_vendor_id;
    private $entwine_vendor_passcode;
    private $entwine_merchant_name;
    private $entwine_currency_id;
    private $entwine_lang_id;
    private $entwine_game_records_path;
    private $entwine_is_xml;
    private $entwine_timezone;
    const CHILD_USERID = 0;
    const PASSWORD_LOGIN = 1;
    const CHILD_UUID = 1;
    const CHILD_IP = 2;
    const FLAG_FAIL = "fail";
    const FLAG_SUCCESS = "0";
    const FLAG_SUCCESS_STR = "success";
    const ERROR_INVALID_ACCOUNT_ID = "101";
    const ERROR_ACCOUNT_SUSPENDED  = "104";
    const ERROR_INVALID_USER = "611";
    const ERROR_CODES = array("003", "201", "202", "203", "204", "205", "206", "211", "212", "213", "401", "402", "404", "701");
    const IDR_CURRENCY = "360";

    public function __construct() {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->entwine_websinglelogin_url = $this->getSystemInfo('entwine_websinglelogin_url');
        $this->entwine_mobilesinglelogin_url = $this->getSystemInfo('entwine_mobilesinglelogin_url');
        $this->entwine_vendor_id = $this->getSystemInfo('entwine_vendor_id');
        $this->entwine_vendor_passcode = $this->getSystemInfo('entwine_vendor_passcode');
        $this->entwine_merchant_name = $this->getSystemInfo('entwine_merchant_name');
        $this->entwine_currency_id = $this->getSystemInfo('entwine_currency_id');
        $this->entwine_lang_id = $this->getSystemInfo('entwine_lang_id');
        $this->entwine_token_timeout = $this->getSystemInfo('entwine_token_timeout');
        $this->entwine_game_records_path = $this->getSystemInfo('entwine_game_records_path');
        $this->entwine_is_xml = $this->getSystemInfo('entwine_is_xml');
        $this->entwine_timezone = $this->getSystemInfo('entwine_timezone');
    }

    // const URI_MAP = array(
    //  self::API_queryPlayerBalance => 'configs/external/checkclient/webet/server.php',
    //  self::API_depositToGame => 'configs/external/deposit/webet/server.php',
    //  self::API_withdrawFromGame => 'configs/external/withdrawal/webet/server.php',
    // );
    //
    const URI_MAP = array(
        self::API_queryPlayerBalance => 'transaction/CheckClient',
        self::API_depositToGame => 'transaction/PlayerDeposit',
        self::API_withdrawFromGame => 'transaction/PlayerWithdrawal',
        self::API_syncGameRecords => 'Trading/GameInfo',
    );

    protected function generateXmlRpcMethod($apiName, $params) {

        switch ($apiName) {

            case self::API_syncGameRecords:
                return array('Trading/GameInfo', $params, 'xml');
                break;


            default:
                # code...
                break;
        }

    }

    /**
     * overview : get platform code
     *
     * @return int
     */
    public function getPlatformCode() {
        return ENTWINE_API;
    }

    /**
     * overview : generate url
     *
     * @param $apiName
     * @param $params
     * @return string
     */
    function generateUrl($apiName, $params) {
        $apiUri = self::URI_MAP[$apiName];
        $url = $this->api_url . "/" . $apiUri;
        return $url;
    }

    /**
     * overview : callback
     *
     * @param $method
     * @param $params
     * @return mixed
     */
    public function callback($method, $params) {
        $data = $this->{$method}($params);
        return $data;
    }

    /**
     * overview : login
     *
     * @param $params
     * @return mixed
     */
    public function dlogin($params) {
        $resultXml = simplexml_load_string(preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $params));

        $resultArr = $this->processXmlResultToArray($resultXml);
        $this->utils->debug_log("RESULT ARR: >=============================> ", json_encode($resultArr));

        $playerUsername = $resultArr['element']['properties'][self::CHILD_USERID];
        $passwordFromParams = $resultArr['element']['properties'][self::PASSWORD_LOGIN];

        $gameUsername= $this->getGameUsernameByPlayerUsername($playerUsername);
        $playerPassword = $this->getPasswordByGameUsername($gameUsername);

        $this->testAccountChecker($gameUsername);
        $uuid = $resultArr['element']['properties'][self::CHILD_UUID];
        // $ip = $resultArr['element']['properties'][self::CHILD_IP];
        $element_id = $resultArr['element']['@attributes']['id'];

        $playerInfo = $this->getPlayerInfoByToken($uuid);

        $this->utils->debug_log("Player Info >=============================> ", $playerInfo, " gameUsername:", $gameUsername);

        if($this->checkBlockStatus($playerUsername)){
            $xmlData = new SimpleXMLElement("<message><status>" . self::FLAG_FAIL . "</status><result action=\"clogin\"><element id=\"" . $element_id . "\"><properties name=\"userid\">" . $gameUsername . "</properties><properties name=\"username\">" . $gameUsername . "</properties><properties name=\"acode\"></properties><properties name=\"vendorid\">" . $this->entwine_vendor_id . "</properties><properties name=\"merchantpasscode\">" . $this->entwine_vendor_passcode . "</properties><properties name=\"currencyid\">". $this->entwine_currency_id ."</properties><properties name=\"status\">" . self::ERR_ACCOUNT_SUSPENDED . "</properties><properties name=\"errdesc\"></properties></element></result></message>");

        }else{
            if($gameUsername &&($playerPassword == $passwordFromParams)){
                $xmlData = new SimpleXMLElement("<message><status>" . self::FLAG_SUCCESS_STR . "</status><result action=\"clogin\"><element id=\"" . $element_id . "\"><properties name=\"userid\">" . $gameUsername . "</properties><properties name=\"username\">" . $gameUsername . "</properties><properties name=\"acode\"></properties><properties name=\"vendorid\">" . $this->entwine_vendor_id . "</properties><properties name=\"merchantpasscode\">" . $this->entwine_vendor_passcode . "</properties><properties name=\"currencyid\">". $this->entwine_currency_id ."</properties><properties name=\"status\">" . self::FLAG_SUCCESS . "</properties><properties name=\"errdesc\"></properties></element></result></message>");
            }else{
                $xmlData = new SimpleXMLElement("<message><status>" . self::FLAG_FAIL . "</status><result action=\"clogin\"><element id=\"" . $element_id . "\"><properties name=\"userid\">" . $gameUsername . "</properties><properties name=\"username\">" . $gameUsername . "</properties><properties name=\"acode\"></properties><properties name=\"vendorid\">" . $this->entwine_vendor_id . "</properties><properties name=\"merchantpasscode\">" . $this->entwine_vendor_passcode . "</properties><properties name=\"currencyid\">". $this->entwine_currency_id ."</properties><properties name=\"status\">" . self::ERROR_INVALID_USER . "</properties><properties name=\"errdesc\"></properties></element></result></message>");
            }
        }

        $xml_response = $this->CI->utils->arrayToXml(array(), $xmlData);
        $this->utils->debug_log("EA CALLBACK RESPONSE >=============================> ", json_encode($xml_response));
        return $xml_response;
    }

    public function checkBlockStatus($playerName) {
        if ($playerName) {
            $playerId = $this->getPlayerIdFromUsername($playerName);
            $this->CI->load->model('player_model');
            return $this->CI->player_model->isBlocked($playerId);
        }
    }

    public function wlogin($params) {
        $resultXml = simplexml_load_string(preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $params));

        $resultArr = $this->processXmlResultToArray($resultXml);
        $this->utils->debug_log("RESULT ARR Web Login: >=============================> ", json_encode($resultArr));

        $gameUsername = $resultArr['element']['properties'][self::CHILD_USERID];
        $password = $resultArr['element']['properties'][self::PASSWORD_LOGIN];
        $playerUsername = $this->getPlayerUsernameByGameUsername($gameUsername);

        $this->utils->debug_log("Player Username ==================================>", $playerUsername);

        $this->testAccountChecker($gameUsername);
        $uuid = $resultArr['element']['properties'][self::CHILD_UUID];
        $ip = $resultArr['element']['properties'][self::CHILD_IP];
        $element_id = $resultArr['element']['@attributes']['id'];

        $playerInfo = $this->getPlayerInfoByToken($uuid);

        $this->utils->debug_log("Player Info >=============================> ", $playerInfo, " gameUsername:", $gameUsername);

        if($this->checkBlockStatus($playerUsername)){
            $xmlData = new SimpleXMLElement("<message><status>" . self::FLAG_FAIL . "</status><result action=\"userverf\"><element id=\"" . $element_id . "\"><properties name=\"userid\">" . $gameUsername . "</properties><properties name=\"username\">" . $gameUsername . "</properties><properties name=\"uuid\">" . $uuid . "</properties><properties name=\"vendorid\">" . $this->entwine_vendor_id . "</properties><properties name=\"merchantpasscode\">" . $this->entwine_vendor_passcode . "</properties><properties name=\"clientip\">". $ip ."</properties><properties name=\"currencyid\">". $this->entwine_currency_id ."</properties><properties name=\"acode\"></properties><properties name=\"errdesc\"></properties><properties name=\"status\">" . self::ERR_ACCOUNT_SUSPENDED . "</properties></element></result></message>");
        }else{
            if($playerUsername){
                $xmlData = new SimpleXMLElement("<message><status>" . self::FLAG_SUCCESS_STR . "</status><result action=\"userverf\"><element id=\"" . $element_id . "\"><properties name=\"userid\">" . $gameUsername . "</properties><properties name=\"username\">" . $gameUsername . "</properties><properties name=\"uuid\">" . $uuid . "</properties><properties name=\"vendorid\">" . $this->entwine_vendor_id . "</properties><properties name=\"merchantpasscode\">" . $this->entwine_vendor_passcode . "</properties><properties name=\"clientip\">". $ip ."</properties><properties name=\"currencyid\">". $this->entwine_currency_id ."</properties><properties name=\"acode\"></properties><properties name=\"errdesc\"></properties><properties name=\"status\">" . self::FLAG_SUCCESS . "</properties></element></result></message>");
            }else{
                $xmlData = new SimpleXMLElement("<message><status>" . self::FLAG_FAIL . "</status><result action=\"userverf\"><element id=\"" . $element_id . "\"><properties name=\"userid\">" . $gameUsername . "</properties><properties name=\"username\">" . $gameUsername . "</properties><properties name=\"uuid\">" . $uuid . "</properties><properties name=\"vendorid\">" . $this->entwine_vendor_id . "</properties><properties name=\"merchantpasscode\">" . $this->entwine_vendor_passcode . "</properties><properties name=\"clientip\">". $ip ."</properties><properties name=\"currencyid\">". $this->entwine_currency_id ."</properties><properties name=\"acode\"></properties><properties name=\"errdesc\"></properties><properties name=\"status\">" . self::ERROR_INVALID_USER . "</properties></element></result></message>");
            }
        }

        $xml_response = $this->CI->utils->arrayToXml(array(), $xmlData);
        $this->utils->debug_log("EA CALLBACK RESPONSE >=============================> ", json_encode($xml_response));
        return $xml_response;
    }

    public function auto_cashier($params) {
        $this->utils->debug_log("EA CALLBACK REQUEST >=============================> ", $params);
        $params = str_replace('utf-16', 'utf-8', $params);
        $this->utils->debug_log("EA CALLBACK TO UTF-8 >=============================> ", $params);
        $resultXml = simplexml_load_string($params);

        $resultArr = $this->processXmlResultToArray($resultXml);
        $gameUsername = $resultArr['element']['properties'][self::CHILD_USERID];
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

        $this->testAccountChecker($gameUsername);

        $uuid = $resultArr['element']['properties'][self::CHILD_UUID];
        $ip = $resultArr['element']['properties'][self::CHILD_IP];
        $element_id = $resultArr['element']['@attributes']['id'];

        $this->CI->load->model(array('common_token'));
        $ticket = $this->CI->common_token->createTokenBy($playerId, 'player_id', $this->entwine_token_timeout);

        if (!empty($gameUsername)) {
            $xmlData = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\"?><message><status>". self::FLAG_SUCCESS_STR ."</status><result action=\"cgetticket\"><element id=\"" . $element_id . "\"><properties name=\"username\">" . $gameUsername . "</properties><properties name=\"ticket\">" . $ticket . "</properties><properties name=\"status\">" . self::FLAG_SUCCESS . "</properties></element></result></message>");
        } else {
            $xmlData = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\"?><message><status>". self::FLAG_FAIL ."</status><result action=\"cgetticket\"><element id=\"" . $element_id . "\"><properties name=\"username\">" . $gameUsername . "</properties><properties name=\"ticket\">" . $ticket . "</properties><properties name=\"status\">" . self::ERROR_INVALID_USER . "</properties></element></result></message>");
        }

        $xml_response = $this->CI->utils->arrayToXml(array(), $xmlData);
        $xml_response = str_replace('utf-8', 'utf-16', $xml_response);

        $this->utils->debug_log("EA CALLBACK RESPONSE >=============================> ", $xml_response);
        return $xml_response;
    }

    /**
     * overview : processing game list
     * @param $game
     * @return array
     */
    public function processGameList($game) {
        $game = parent::processGameList($game);
        $game['gp'] = "iframe_module/gotogame/" . $this->getPlatformCode() . "/" . $game['c']; //game param
        return $game;
    }

    /**
     * overview : create custom http call
     * @param $ch
     * @param $params
     */
    protected function customHttpCall($ch, $params) {
        //curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    }

    /**
     * overview : process xml result to array
     *
     * @param $resultXml
     * @return mixed
     */
    private function processXmlResultToArray($resultXml) {
        $resultJson = json_encode($resultXml);
        return json_decode($resultJson, TRUE);
    }

    /**
     * overview : account checker
     *
     * @param $playerName
     */
    private function testAccountChecker($playerName) {
        $testAccountsArr = array("
            wbtcnytest01" => array("vendorid" => "3", "currencyid" => "156"),
            "wbtcnytest02" => array("vendorid" => "3", "currencyid" => "156"),
            "wbtcnytest02" => array("vendorid" => "3", "currencyid" => "156"),
            "wbtgbptest01" => array("vendorid" => "4", "currencyid" => "826"),
            "wbtgbptest02" => array("vendorid" => "4", "currencyid" => "826"),
            "wbtgbptest03" => array("vendorid" => "4", "currencyid" => "826"),
            "wbtusdtest01" => array("vendorid" => "5", "currencyid" => "840"),
            "wbtusdtest02" => array("vendorid" => "5", "currencyid" => "840"),
            "wbtusdtest03" => array("vendorid" => "5", "currencyid" => "840"),
            "wbteurtest01" => array("vendorid" => "6", "currencyid" => "978"),
            "wbteurtest02" => array("vendorid" => "6", "currencyid" => "978"),
            "wbteurtest03" => array("vendorid" => "6", "currencyid" => "978"),
            "wbtthbtest01" => array("vendorid" => "7", "currencyid" => "764"),
            "wbtthbtest02" => array("vendorid" => "7", "currencyid" => "764"),
            "wbtthbtest03" => array("vendorid" => "7", "currencyid" => "764"),
            "wbtidrtest01" => array("vendorid" => "8", "currencyid" => "360"),
            "wbtidrtest02" => array("vendorid" => "8", "currencyid" => "360"),
            "wbtidrtest03" => array("vendorid" => "8", "currencyid" => "360"),
            "wbttest01" => array("vendorid" => "2", "currencyid" => "1111"),
            "wbttest02" => array("vendorid" => "2", "currencyid" => "1111"),
            "wbttest03" => array("vendorid" => "2", "currencyid" => "1111"),
            "wbttest04" => array("vendorid" => "2", "currencyid" => "1111"),
            "wbttest05" => array("vendorid" => "2", "currencyid" => "1111"),
            "wbttest06" => array("vendorid" => "2", "currencyid" => "1111"),
            "wbtprotest01" => array("vendorid" => "2", "currencyid" => "1111"),
            "wbtprotest02" => array("vendorid" => "2", "currencyid" => "1111"),
            "wbtprotest03" => array("vendorid" => "2", "currencyid" => "1111"),
            "wbtprotest04" => array("vendorid" => "2", "currencyid" => "1111"),
            "wbtprotest05" => array("vendorid" => "2", "currencyid" => "1111"),
            "testea1" => array("vendorid" => "2", "currencyid" => "1111"),
            "testea2" => array("vendorid" => "2", "currencyid" => "1111"),
            "testea3" => array("vendorid" => "2", "currencyid" => "1111"),
            "testea4" => array("vendorid" => "2", "currencyid" => "1111"),
            "testea5" => array("vendorid" => "2", "currencyid" => "1111"),
            "testgreen" => array("vendorid" => "48", "currencyid" => "1111"),
            "testwhite" => array("vendorid" => "48", "currencyid" => "156"),
            "fwtestwhite" => array("vendorid" => "48", "currencyid" => "156"),
            "testblack" => array("vendorid" => "48", "currencyid" => "156"),
            "testdarkred" => array("vendorid" => "48", "currencyid" => "156"),
            "fw_test01" => array("vendorid" => "48", "currencyid" => "156"),
            "fwtest02" => array("vendorid" => "48", "currencyid" => "156"),
            "fwtest03" => array("vendorid" => "48", "currencyid" => "156"),
            "fwtest04" => array("vendorid" => "48", "currencyid" => "156"),
            "fwtest05" => array("vendorid" => "48", "currencyid" => "156"),
            "test01" => array("vendorid" => "48", "currencyid" => "156"),
            "test02" => array("vendorid" => "48", "currencyid" => "156"),
            "test03" => array("vendorid" => "48", "currencyid" => "156"),
            "test04" => array("vendorid" => "48", "currencyid" => "156"),
            "test05" => array("vendorid" => "48", "currencyid" => "156"),
            "fwcnytest01" => array("vendorid" => "48", "currencyid" => "1111"),
            "fwcnytest02" => array("vendorid" => "48", "currencyid" => "1111"),
            "fwcnytest03" => array("vendorid" => "48", "currencyid" => "1111"),
            "fwcnytest04" => array("vendorid" => "48", "currencyid" => "1111"),
            "fwcnytest05" => array("vendorid" => "48", "currencyid" => "1111"),
            "cnytest01" => array("vendorid" => "48", "currencyid" => "1111"),
            "cnytest02" => array("vendorid" => "48", "currencyid" => "1111"),
            "cnytest03" => array("vendorid" => "48", "currencyid" => "1111"),
            "cnytest04" => array("vendorid" => "48", "currencyid" => "1111"),
            "cnytest05" => array("vendorid" => "48", "currencyid" => "1111"),
            "fwprotest01" => array("vendorid" => "46", "currencyid" => "1111"),
            "fwprotest02" => array("vendorid" => "46", "currencyid" => "1111"),
            "fwprotest03" => array("vendorid" => "46", "currencyid" => "1111"),
            "fwprotest04" => array("vendorid" => "46", "currencyid" => "1111"),
            "fwprotest05" => array("vendorid" => "46", "currencyid" => "1111"),
        );
        if (array_key_exists($playerName, $testAccountsArr)) {
            $this->entwine_vendor_id = $testAccountsArr[$playerName]['vendorid'];
            $this->entwine_currency_id = $testAccountsArr[$playerName]['currencyid'];

            $this->utils->debug_log("Test Account Currency ID =============================>", $this->entwine_currency_id);
        }
    }

    /**
     * overview : deposit to game
     *
     * @param $playerName
     * @param $amount
     * @param null $transfer_secure_id
     * @return array
     */
    function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $this->testAccountChecker($playerName);
        //if currency is IDR add 3 zeros to the original amount
        if ($this->entwine_currency_id == self::IDR_CURRENCY) {
            $amount = substr($amount, 0, -3);
        }
        $refno = 'DEP' . random_string('unique');
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'amount' => $amount,
            'refno' => $refno,
        );
        $element_id = "D" . random_string('numeric');

        $xml_object = new SimpleXMLElement('<request action="cdeposit"><element id="' . $element_id . '"><properties name="userid">' . $playerName . '</properties><properties name="acode"></properties><properties name="vendorid">' . $this->entwine_vendor_id . '</properties><properties name="merchantpasscode">' . $this->entwine_vendor_passcode . '</properties><properties name="currencyid">' . $this->entwine_currency_id . '</properties><properties name="amount">' . $amount . '</properties><properties name="refno">' . $refno . '</properties></element></request>');
        $xmlData = $this->CI->utils->arrayToXml(array(), $xml_object);
        $this->utils->debug_log("Deposit Api Request params=============================>", $xmlData,'Context =================>', $context);

        return $this->callApi(self::API_depositToGame, $xmlData, $context);
    }

    function getHttpHeaders($params = null) {
        return array('Content-Type' => 'application/xml');
    }

    function convertResultXmlFromParams($params) {
        $resultText = @$params['resultText'];
        $resultXml = null;
        if (!empty($resultText)) {
            $resultText = str_replace('utf-16', 'utf-8', $resultText);
            // $resultText = preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $resultText);
            $resultXml  = new SimpleXMLElement($resultText);
        }
        return $resultXml;
    }

    /**
     * overview : procesing deposit to game
     *
     * @param $params
     * @return array
     */
    function processResultForDepositToGame($params) {

        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = $this->processXmlResultToArray($resultXml);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $refno = $this->getVariableFromContext($params, 'refno');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $this->CI->utils->debug_log('processResultForDepositToGame resultArr ==============================>', $resultArr);
        $result = array('response_result_id' => $responseResultId);
        if ($success) {
            #update player register flag
            $isPlayerExist = $this->isPlayerExist($playerName);
            if (empty($isPlayerExist['exists'])) {
                $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE, $isDemoFlag);
            }

            $confirmResult = $this->confirmTransaction(@$resultArr['result']['element']['@attributes']['id'], @$resultArr['result']['element']['properties'][0]);
            if ($confirmResult['success']) {
                $afterBalance = $this->queryPlayerBalance($playerName)['balance'];
                $result["currentplayerbalance"] = $afterBalance;
                $result["transId"] = $refno;
                $result["userNotFound"] = false;

                //update
                $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
                if ($playerId) {
                    //deposit
                    $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
                        $this->transTypeMainWalletToSubWallet());
                } else {
                    $this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
                }
            }
            $success = $confirmResult['success'];
        }

        return array($success, $result);
    }

    /**
     * overview : confirm transaction
     *
     * @param $elementId
     * @param $paymentId
     * @return array
     */
    function confirmTransaction($elementId, $paymentId) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForConfirmDeposit',
            //for this api
            'enabled_guess_success_for_curl_errno_on_this_api' => $this->enabled_guess_success_for_curl_errno_on_this_api,
        );
        $xml_object = new SimpleXMLElement("<request action=\"cdeposit-confirm\"><element id=\"" . $elementId . "\"><properties name=\"acode\"></properties><properties name=\"status\">" . self::FLAG_SUCCESS . "</properties><properties name=\"paymentid\">" . $paymentId . "</properties><properties name=\"vendorid\">" . $this->entwine_vendor_id . "</properties><properties name=\"merchantpasscode\">" . $this->entwine_vendor_passcode . "</properties><properties name=\"errdesc\"></properties></element></request>");
        $xmlData = $this->CI->utils->arrayToXml(array(), $xml_object);
        return $this->callApi(self::API_depositToGame, $xmlData, $context);
    }

    /**
     * overview : processing result for confirmation deposit
     *
     * @param $params
     * @return bool
     */
    function processResultForConfirmDeposit($params) {
        $resultXml = $this->getResultXmlFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->processXmlResultToArray($resultXml);

        $success = $this->processResultBoolean($responseResultId, $resultArr, null, false, true);
        return $success;
    }

    /**
     * overview : result after process
     *
     * @param $apiName
     * @param $params
     * @param $responseResultId
     * @param $resultText
     * @param $statusCode
     * @param null $statusText
     * @param null $extra
     * @param null $resultObj
     * @return array
     */
    function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return $this->returnUnimplemented();
    }

    /**
     * overview : processing result
     *
     * @param $responseResultId
     * @param $resultArr
     * @param null $playerName
     * @param bool|false $isCheckGetBalanceError
     * @param bool|false $isCheckDepositError
     * @return bool
     */
    function processResultBoolean($responseResultId, $resultArr, $playerName = null, $isCheckGetBalanceError = false, $isCheckDepositError = false,$isCheckWithdrawalError = false) {
        if ($isCheckGetBalanceError) {
            if (!in_array($resultArr['result']['element']['properties'][0], self::ERROR_CODES)) {
                return true;
            } else {
                $this->setResponseResultToError($responseResultId);
                $this->CI->utils->debug_log('Entwine got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
                return false;
            }
        }
        if ($isCheckDepositError) {
            if (!in_array($resultArr['result']['element']['properties'][1], self::ERROR_CODES)) {
                return true;
            } else {
                $this->setResponseResultToError($responseResultId);
                $this->CI->utils->debug_log('Entwine got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
                return false;
            }
        }
        //withdrawal
        if ($isCheckWithdrawalError) {
            if (!in_array($resultArr['result']['element']['properties'][1], self::ERROR_CODES)) {
                return true;
            } else {
                $this->setResponseResultToError($responseResultId);
                $this->CI->utils->debug_log('Entwine got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
                return false;
            }
        }
        //deposit
        $deposit = isset($resultArr['result']['@attributes']['action']) ? $resultArr['result']['@attributes']['action'] : null;
        if( $deposit == "cdeposit"){
            $success = !empty($resultArr) && $resultArr['status'] == self::FLAG_SUCCESS_STR;
        } else {
            $success = !empty($resultArr) && $resultArr['status'] == self::FLAG_SUCCESS;
        }

        //sync game logs
        if($resultArr['status'] ==self::FLAG_SUCCESS_STR){
            $success = !empty($resultArr) && $resultArr['status'] == self::FLAG_SUCCESS_STR;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('Entwine got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }

    /**
     * overview : sync player account
     * @param $username
     * @param $password
     * @param $playerId
     * @return array
     */
    public function syncPlayerAccount($username, $password, $playerId) {
        return $this->returnUnimplemented();
    }

    /**
     * overview : create player
     *
     * @param $playerName
     * @param $playerId
     * @param $password
     * @param null $email
     * @param null $extra
     * @return array
     */
    function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        #set success to false to consider player not yet registered
        return array("success" => true, array("playerName" => $playerName));
    }

    /**
     * overview : query player information
     *
     * @param $playerName
     * @return array
     */
    function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    /**
     * overview : process result for getting player information
     * @param $params
     * @return array
     */
    function processResultForQueryPlayerInfo($params) {
        return $this->returnUnimplemented();
    }

    /**
     * overview : changing password
     *
     * @param $playerName
     * @param $oldPassword
     * @param $newPassword
     * @return array
     */
    function changePassword($playerName, $oldPassword, $newPassword) {
        return $this->returnUnimplemented();
    }

    /**
     * overview : process result for changing password
     *
     * @param $params
     * @return array
     */
    function processResultForChangePassword($params) {
        return $this->returnUnimplemented();
    }

    /**
     * overview : withdraw player from game
     * @param $playerName
     * @param $amount
     * @param null $transfer_secure_id
     * @return array
     */
    function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $this->testAccountChecker($playerName);
        //if currency is IDR add 3 zeros to the original amount
        if ($this->entwine_currency_id == self::IDR_CURRENCY) {
            $amount = substr($amount, 0, -3);
        }
        $refno = 'WIT' . random_string('unique');
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'amount' => $amount,
            'refno' => $refno,
        );
        $element_id = "W" . random_string('numeric');
        $xml_object = new SimpleXMLElement("<request action=\"cwithdrawal\"><element id=\"" . $element_id . "\"><properties name=\"userid\">" . $playerName . "</properties><properties name=\"vendorid\">" . $this->entwine_vendor_id . "</properties><properties name=\"merchantpasscode\">" . $this->entwine_vendor_passcode . "</properties><properties name=\"amount\">" . $amount . "</properties><properties name=\"currencyid\">" . $this->entwine_currency_id . "</properties><properties name=\"refno\">" . $refno . "</properties></element></request>");
        $xmlData = $this->CI->utils->arrayToXml(array(), $xml_object);
        return $this->callApi(self::API_withdrawFromGame, $xmlData, $context);
    }

    /**
     * overview : processing result for withdraw player from game
     *
     * @param $params
     * @return array
     */
    function processResultForWithdrawFromGame($params) {

        $resultXml = $this->getResultXmlFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->processXmlResultToArray($resultXml);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $refno = $this->getVariableFromContext($params, 'refno');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName, false,false,true);
        $this->CI->utils->debug_log('processResultForWithdrawFromGame resultArr =======================> ', $resultArr);

        // $resultArr['status'] . $resultArr['result']['element']['properties']['1']

        $result = array('response_result_id' => $responseResultId);
        if ($success) {
            $afterBalance = $this->queryPlayerBalance($playerName)['balance'];
            $result["currentplayerbalance"] = $afterBalance;
            $result["transId"] = $refno;
            $result["userNotFound"] = false;

            //update
            $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
            if ($playerId) {
                //withdraw
                $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
                    $this->transTypeMainWalletToSubWallet());
            } else {
                $this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
            }
        }

        return array($success, $result);
    }

    /**
     * overview : login player
     *
     * @param $playerName
     * @param null $password
     * @return array
     */
    function login($playerName, $password = null) {
        return $this->returnUnimplemented();
    }

    /**
     * overview : logout player
     *
     * @param $playerName
     * @param null $password
     * @return array
     */
    function logout($playerName, $password = null) {
        return $this->returnUnimplemented();
    }

    /**
     * overview : update mo
     *
     * @param $playerName
     * @param $infos
     * @return array
     */
    function updatePlayerInfo($playerName, $infos) {
        return $this->returnUnimplemented();
    }

    /**
     * overview : processing result for update player information
     *
     * @param $apiName
     * @param $params
     * @param $responseResultId
     * @param $resultJson
     * @return array
     */
    function processResultForUpdatePlayerInfo($apiName, $params, $responseResultId, $resultJson) {
        return $this->returnUnimplemented();
    }

    /**
     * overview : get player balance
     *
     * @param $playerName
     * @return array
     */
    function queryPlayerBalance($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $this->testAccountChecker($playerName);

        $isPlayerExist = $this->isPlayerExist($playerName);
        if($isPlayerExist){
            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForQueryPlayerBalance',
                'playerName' => $playerName,
            );

            $element_id = "C" . random_string('numeric');
            $xml_object = new SimpleXMLElement("<request action=\"ccheckclient\"><element id=\"" . $element_id . "\"><properties name=\"userid\">" . $playerName . "</properties><properties name=\"vendorid\">" . $this->entwine_vendor_id . "</properties><properties name=\"merchantpasscode\">" . $this->entwine_vendor_passcode . "</properties><properties name=\"currencyid\">" . $this->entwine_currency_id . "</properties></element></request>");
            $xmlData = $this->CI->utils->arrayToXml(array(), $xml_object);

            // $this->CI->utils->debug_log('queryPlayerBalance request params ====================>', $xmlData);
            return $this->callApi(self::API_queryPlayerBalance, $xmlData, $context);
        }
    }

    /**
     * overview : process result for query player balance
     *
     * @param $params
     * @return array
     */
    function processResultForQueryPlayerBalance($params) {
        $resultXml = $this->getResultXmlFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->processXmlResultToArray($resultXml);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName, true);
        $this->CI->utils->debug_log('processResultForQueryPlayerBalance resultArr =============================> ', $resultArr);
        $result = array();
        if ($success) {
            $result["balance"] = floatval($resultArr['result']['element']['properties'][1]);
            $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
            $this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName',
                $playerName, 'balance', @$result["balance"]);
        } else {
            $success = false;
        }

        return array($success, $result);
    }

    /**
     * overview : get player daily balance
     *
     * @param $playerName
     * @param $playerId
     * @param null $dateFrom
     * @param null $dateTo
     * @return array
     */
    function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
        $daily_balance = parent::getPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null);
        $result = array();
        if ($daily_balance != null) {
            foreach ($daily_balance as $key => $value) {
                $result[$value['updated_at']] = $value['balance'];
            }
        }

        return array_merge(array('success' => true, "balanceList" => $result));
    }

    /**
     * overview : query game records
     *
     * @param $dateFrom
     * @param $dateTo
     * @param null $playerName
     * @return array
     */
    function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
        $gameRecords = parent::getGameRecords($dateFrom, $dateTo, $playerName, $this->getPlatformCode());
        return array('success' => true, 'gameRecords' => $gameRecords);
    }

    /**
     * overview : check the login status
     *
     * @param $playerName
     * @return array
     */
    function checkLoginStatus($playerName) {
        return array("success" => true);
    }

    /**
     * overview : processing result for check login status
     *
     * @param $apiName
     * @param $params
     * @param $responseResultId
     * @param $resultJson
     * @return array
     */
    function processResultForCheckLoginStatus($apiName, $params, $responseResultId, $resultJson) {
        return $this->returnUnimplemented();
    }


    /**
     * overview : check the login token
     *
     * @param $playerName
     * @param $token
     * @return array
     */
    public function checkLoginToken($playerName, $token) {
        return $this->returnUnimplemented();
    }
    public function processResultForCheckLoginToken($params) {
        return $this->returnUnimplemented();
    }

    /**
     * overview : total betting amount
     *
     * @param $playerName
     * @param $dateTimeFrom
     * @param $dateTimeTo
     * @return array
     */
    function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
        return $this->returnUnimplemented();
    }

    /**
     * overview : query transaction
     * @param $transactionId
     * @param $extra
     * @return array
     */
    function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    /**
     * overview : process result for query transactions
     *
     * @param $apiName
     * @param $params
     * @param $responseResultId
     * @param $resultJson
     * @return array
     */
    function processResultForQueryTransaction($apiName, $params, $responseResultId, $resultJson) {
        return $this->returnUnimplemented();
    }

    /**
     * overview : get forward game
     *
     * @param $playerName
     * @param null $params
     * @return array
     */
    // function queryForwardGame($playerName, $params = null) {
    //  $playerName = $this->getGameUsernameByPlayerUsername($playerName);
    //  $this->CI->utils->debug_log('gameLauncher playerName: ', $playerName);
    //  $uuid = 'webet88' . random_string('unique');
    //  if ($params['extra'] == "web") {
    //      $url = $this->entwine_websinglelogin_url . '/' . $this->entwine_merchant_name . '/index.php?userid=' . $playerName . '&uuid=' . $uuid . '&lang=' . $params['language'];
    //  } elseif ($params['extra'] == "mobile") {
    //      $url = $this->entwine_mobilesinglelogin_url . '/mobile/src/mobile.php?userid=' . $playerName . '&uuid=' . $uuid . '&lang=' . $this->entwine_lang_id;
    //  }
    //  return array(
    //      'success' => true,
    //      'url' => $url,
    //      'iframeName' => "ENTWINE API",
    //  );
    // }
    function queryForwardGame($playerName, $params = null) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $this->CI->utils->debug_log('gameLauncher playerName: ', $playerName);
        $uuid = random_string('unique');

        $this->CI->utils->debug_log('<--------------------queryForwardGame params:---------------------> ', $params);

        switch ($params['language']) {
            case '2':
                $language = "zh-cn";
                break;

            default:
                $language = "en";
                break;
        }

        if ($params['extra'] == "web") {
            $url = $this->entwine_websinglelogin_url . 'merchantcode=' . $this->entwine_merchant_name . '&lang=' . $language . '&userid=' . $playerName . '&uuId=' . $uuid;
        } elseif ($params['extra'] == "mobile") {
            $url = $this->entwine_mobilesinglelogin_url . 'merchantcode=' . $this->entwine_merchant_name .  '&lang=' .  $language  . '&userid=' . $playerName . '&uuId=' . $uuid;
        }

        return array(
            'success' => true,
            'url' => $url,
            'iframeName' => "ENTWINE API",
        );
    }
    /**
     * overview : processing result for query forward game
     *
     * @param $params
     * @return array
     */
    function processResultQueryForwardGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJson);
        $this->CI->utils->debug_log('processResultLaunchGame resultJson: ', $resultJson);
        $result = array();
        if ($success && isset($resultJson['url'])) {

            $isPlayerExist = $this->isPlayerExist($playerName);
            if (empty($isPlayerExist['exists'])) {
                $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE, $isDemoFlag);
            }

            $this->CI->utils->debug_log('Launch Game: ', 'playerName', $playerName, 'balance', @$resultJson['url']);
            $result['url'] = $resultJson['url'];
            $result['iframeName'] = 'ENTWINE_API';
        } else {
            $success = false;
        }

        return array($success, $result);
    }

    /**
     * overview : sync original game logs
     *
     * @param $token
     * @return array
     */
    function syncOriginalGameLogs($token) {
        $gameLogDirectory = $this->entwine_game_records_path . '/';
        $playerName = $this->getValueFromSyncInfo($token, 'playerName');

        $dateTimeFrom = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');

        //adjust less 20 minutes
        $dateTimeTo = date_sub($dateTimeTo,date_interval_create_from_date_string("20 minutes"));

        $syncId = parent::getValueFromSyncInfo($token, 'syncId');
        $timeZone = ($this->entwine_timezone) ? $this->entwine_timezone: '480';

        // $intervalObj = DateInterval::createFromDateString('1 hours');
        $dateTimeFrom->modify($this->getDatetimeAdjust());
        $this->CI->utils->debug_log('from', $dateTimeFrom, 'to', $dateTimeTo);

        // $element_id = "D" . random_string('numeric');
        if($this->entwine_is_xml){

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncGameRecords',
            );

            $xml_object = new SimpleXMLElement('<request action="gameinfo"><element><properties name="vendorid">' . $this->entwine_vendor_id . '</properties><properties name="merchantpasscode">' . $this->entwine_vendor_passcode .'</properties><properties name="startdate">' . $dateTimeFrom->format('Y-m-d H:i:s') . '</properties><properties name="enddate">' . $dateTimeTo->format('Y-m-d H:i:s') . '</properties><properties name="timezone">' . $timeZone . '</properties></element></request>');

            $xmlData = $this->CI->utils->arrayToXml(array(), $xml_object);
            $this->CI->utils->debug_log("Sync Original Game logs ===========================>", $xmlData);

            return $this->callApi(self::API_syncGameRecords, $xmlData, $context);

        }else{
            $this->retrieveXMLFromLocal($gameLogDirectory, $dateTimeFrom, $dateTimeTo, $playerName, $syncId);
            return array('success' => true);
        }

    }

    /**
     * overview : retrieve xml data from local
     *
     * @param $directory
     * @param null $dateTimeFrom
     * @param null $dateTimeTo
     * @param null $playerName
     * @param null $syncId
     */
    private function retrieveXMLFromLocal($directory, $dateTimeFrom = null, $dateTimeTo = null, $playerName = null, $syncId = null) {
        $intervalObj = DateInterval::createFromDateString('1 hours');
        $this->filterXML($directory, $dateTimeFrom->sub($intervalObj), $dateTimeTo, $playerName, $syncId);
    }


    /**
     * overview : filter xml data
     * @param $directory
     * @param $dateTimeFrom
     * @param $dateTimeTo
     * @param $playerName
     * @param $syncId
     */
    private function filterXML($directory, $dateTimeFrom, $dateTimeTo, $playerName, $syncId) {
        //convert to game time
        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
        $dateTimeTo = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));

        $startDate = $dateTimeFrom->format("Ymd");

        for ($i = $dateTimeFrom; $i <= $dateTimeTo; $i->modify('+1 day')) {
            //extract local xml to entwineGameLogs table
            if (is_dir($directory . $i->format("Ymd"))) {
                $entwineGameLogsXml = array_diff(scandir($directory . $i->format("Ymd")), array('..', '.'));

                foreach ($entwineGameLogsXml as $key) {

                    if (current(explode(".", $key)) >= $startDate) {
                        //save to response result
                        $filepath = $this->entwine_game_records_path . '/' . $i->format("Ymd") . '/' . $key;
                        $responseResultId = $this->saveResponseResultForFile(true, 'ENTWINE_FTP', null, $filepath, array('sync_id' => $syncId));
                        $this->extractXMLRecord($i->format("Ymd"), $key, $playerName, $responseResultId);
                    }
                }
            }
        }
    }

    /**
     * overview : extract file name
     * @param $folderName
     * @param $file
     * @param null $playerName
     * @param null $responseResultId
     */
    private function extractXMLRecord($folderName, $file, $playerName = null, $responseResultId = null) {
        $gameLogDirectoryEntwine = $this->entwine_game_records_path . '/';

        $source = $gameLogDirectoryEntwine . $folderName . '/' . $file;
        $xmlData = file_get_contents($source, true);

        $reportData = simplexml_load_string($xmlData);
        $gameRecords = array();
        foreach ($reportData as $key => $value) {
            if (!empty($playerName) && $playerName != $value->deal->betinfo->clientbet['login']) {
                //ignore
                continue;
            }
            foreach ($value as $val) {
                $startdate = $val['startdate'];
                if (is_object($startdate) && $startdate instanceof DateTime) {
                    $startdate = $this->CI->utils->formatDateTimeForMysql($startdate);
                }
                $enddate = $val['enddate'];
                if (is_object($enddate) && $enddate instanceof DateTime) {
                    $enddate = $this->CI->utils->formatDateTimeForMysql($enddate);
                }

                $data = array(
                    "deal_id" => (string) @$val['id'],
                    "game_code" => (string) @$value['code'],
                    "deal_code" => (string) @$val['code'],
                    "deal_status" => (string) @$val['status'],
                    "deal_startdate" => $startdate,
                    "deal_enddate" => $enddate,
                    "payout_amount" => @$val->betinfo->clientbet['payout_amount'],
                    "game_name" => (string) @$val->betinfo->clientbet['login'],
                    "hold" => (string) @$val->betinfo->clientbet['hold'],
                    "handle" => (string) @$val->betinfo->clientbet['handle'],
                    "bet_amount" => @$val->betinfo->clientbet['bet_amount'],
                    "bet_details" => json_encode(@$val->betinfo->clientbet->betdetail),
                    "deal_details" => json_encode(@$val->dealdetails),
                    "results" => json_encode(@$val->results),
                    "external_uniqueid" => (string) @$val['id'],
                    "response_result_id" => $responseResultId,
                );

                $gameRecords[] = $data;
            }
        }
        if (empty($gameRecords) || !is_array($gameRecords)) {
            $this->CI->utils->debug_log('No records', $gameRecords);
        }
        $this->CI->load->model(array('entwine_game_logs_model'));
        $availableRows = $this->CI->entwine_game_logs_model->getAvailableRows($gameRecords);
        if (!empty($availableRows)) {
            foreach ($availableRows as $record) {
                $deal_startdate = $this->CI->utils->formatDateTimeForMysql((new DateTime(@$record['deal_startdate']))->modify('30 minute'));
                $deal_enddate = $this->CI->utils->formatDateTimeForMysql((new DateTime(@$record['deal_enddate']))->modify('30 minute'));
                $entwineGameData = array(
                    'deal_id' => $record['deal_id'],
                    'game_code' => $record['game_code'],
                    'deal_code' => $record['deal_code'],
                    'deal_status' => $record['deal_status'],
                    'deal_startdate' => $deal_startdate,
                    'deal_enddate' => $deal_enddate,
                    'payout_amount' => $record['payout_amount'],
                    'game_name' => $record['game_name'],
                    'hold' => $record['hold'],
                    'handle' => $record['handle'],
                    'bet_amount' => $record['bet_amount'],
                    'bet_details' => $record['bet_details'],
                    'deal_details' => $record['deal_details'],
                    'results' => $record['results'],
                    'external_uniqueid' => $record['external_uniqueid'],
                    'response_result_id' => $record['response_result_id'],
                );
                $this->CI->entwine_game_logs_model->insertEntwineGameLogs($entwineGameData);
            }
        }

    }

    /**
     * overview : get string value from xml data
     *
     * @param $xml
     * @param $key
     * @return string
     */
    private function getStringValueFromXml($xml, $key) {
        $value = (string) $xml[$key];
        if (empty($value) || $value == 'null') {
            $value = '';
        }

        return $value;
    }

    /**
     * overview : processing result for sync game records
     *
     * @param $params
     * @return array
     */
    function processResultForSyncGameRecords($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = $this->processXmlResultToArray($resultXml);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        if ($success) {
            if($this->entwine_is_xml){
                $data = $this->processEntwineGameLogs($resultArr, $responseResultId);
            }
        }

        $result = array();
        return array($success, $result);
    }

    function processEntwineGameLogs($params, $responseResultId){
        $this->CI->load->model('entwine_game_logs_model');

        if(!empty($params['result']['gameinfo']['game']['deal'])){
            $checkIfHaveGameRecords = true;
        }elseif(!empty($params['result']['gameinfo']['game'][0]['deal'])){
            $checkIfHaveGameRecords = true;
            $multiple_game_type = true;
        }

        if($checkIfHaveGameRecords){
            #check if game logs have multiple game type
            if(!empty($multiple_game_type)){
                $gameRecords = $params['result']['gameinfo']['game'];
                foreach ($gameRecords as $key => $gameRecord) {

                    #check if game code have played many times
                    $multiple_game_played_per_game_type = !empty($gameRecord['deal'][0]) ? true : false;

                    #get unique id if the the game code is played once
                    $uniqueid = !empty($gameRecord['deal']['@attributes']['id']) ? $gameRecord['deal']['@attributes']['id'] : null;

                    $game_code = $gameRecord['@attributes']['code'];
                    $availableRows = $this->CI->entwine_game_logs_model->getAvailableRows($gameRecord['deal']);
                    $this->CI->utils->debug_log('availableRows', count($availableRows));
                    #check if game type have many game logs
                    $this->validateEntwineGameRecords($gameRecord['deal'],$game_code,$uniqueid,$multiple_game_played_per_game_type,$responseResultId);
                }
            }else{

                $gameRecords = $params['result']['gameinfo']['game']['deal'];
                $game_code = $params['result']['gameinfo']['game']['@attributes']['code'];

                #get unique id if the the game code is played once
                $uniqueid = !empty($gameRecords['@attributes']['id']) ? $gameRecords['@attributes']['id'] : null;

                #check if game code have played many times
                $multiple_game_played_per_game_type = !empty($gameRecords[0]) ? true : false;

                $availableRows = $this->CI->entwine_game_logs_model->getAvailableRows($gameRecords);
                $this->CI->utils->debug_log('availableRows', count($availableRows));

                $this->validateEntwineGameRecords($gameRecords,$game_code,$uniqueid,$multiple_game_played_per_game_type,$responseResultId);

            }
        }else{
            return array("success",true);
        }
    }

    private function validateEntwineGameRecords($gameRecords,$game_code,$uniqueid = null,$multiple_game_played_per_game_type = null,$responseResultId){
        #check if game type have many game logs
        if($multiple_game_played_per_game_type){
            foreach ($gameRecords as $ids => $record) {
                $uniqueid = $record['@attributes']['id'];
                $isRowIdAlreadyExists = $this->CI->entwine_game_logs_model->isRowIdAlreadyExists($uniqueid);

                if($isRowIdAlreadyExists) continue;
                if(empty($uniqueid)) continue;

                $this->insertEntwineGameRecords($uniqueid, $game_code,$record,$responseResultId);
            }
        }else{
            $uniqueid =  $gameRecords['@attributes']['id'];
            $isRowIdAlreadyExists = $this->CI->entwine_game_logs_model->isRowIdAlreadyExists($uniqueid);

            if($isRowIdAlreadyExists) return;
            if(empty($uniqueid)) return;

            $this->insertEntwineGameRecords($uniqueid, $game_code,$gameRecords,$responseResultId);
        }
    }

    private function insertEntwineGameRecords($deal_id, $code,$record,$responseResultId){
        $data = [];

        $data['deal_id'] = $deal_id;
        $data['game_code'] = $code;
        $data['deal_code'] = $record['@attributes']['code'];
        $data['deal_status'] = $record['@attributes']['status'];
        $data['deal_startdate'] = $record['@attributes']['startdate'];
        $data['deal_enddate'] =$record['@attributes']['enddate'];
        $data['payout_amount'] = $record['betinfo']['clientbet']['@attributes']['payout_amount'];
        $data['game_name'] = $record['betinfo']['clientbet']['@attributes']['login'];
        $data['hold'] = $record['betinfo']['clientbet']['@attributes']['hold'];
        $data['handle'] = $record['betinfo']['clientbet']['@attributes']['handle'];
        $data['bet_amount'] = $record['betinfo']['clientbet']['@attributes']['bet_amount'];
        $data['bet_details'] =  json_encode($record['betinfo']['clientbet']['betdetail']);
        $data['deal_details'] =  json_encode($record['dealdetails']['dealdetail']);
        $data['results'] =  json_encode($record['results']);
        $data['response_result_id'] = $responseResultId;
        $data['createdAt'] = $this->CI->utils->getNowForMysql();
        $data['updatedAt'] = $this->CI->utils->getNowForMysql();
        $data['external_uniqueid'] = $deal_id;
        $data['uniqueid'] = $deal_id;

        $entwineGameData = $this->CI->entwine_game_logs_model->insertEntwineGameLogs($data);
    }
    /**
     * overview : sync merge to game logs
     * s
     * @param $token
     * @return array
     */
    function syncMergeToGameLogs($token) {
        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $dateTimeFrom->modify($this->getDatetimeAdjust());
        $rlt = array('success' => true);

        $this->CI->load->model(array('game_logs', 'player_model', 'entwine_game_logs_model'));
        $result = $this->CI->entwine_game_logs_model->getEntwineGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));
        $this->CI->utils->debug_log('syncMergeToGameLogs result =-------------------------> ', count($result),
            'date time from', $dateTimeFrom, 'date time to', $dateTimeTo);

        if ($result) {
            $unknownGame = $this->getUnknownGame();
            $gameDescIdMap = $this->CI->game_description_model->getGameCodeMap($this->getPlatformCode());
            foreach ($result as $entwinedata) {

                $player_id = $entwinedata->player_id;
                $this->CI->utils->debug_log('syncMergeToGameLogs player_id =-------------------------> ', $player_id);
                if (!$player_id) {
                    continue;
                }

                // remove for now since its divided to 100, can't get the right amount value
                // $bet_amount = $this->utils->roundCurrency($entwinedata->bet_amount / 100);
                // $result_amount = $this->utils->roundCurrency(($entwinedata->result_amount / 100)); //-bet amount

                $bet_amount = $this->utils->roundCurrency($entwinedata->bet_amount);
                $result_amount = $this->utils->roundCurrency(($entwinedata->result_amount));

                if ($bet_amount == 0 && $result_amount == 0) {
                    $this->CI->utils->debug_log('ignore bet_amount and result amount is zero', $entwinedata->id);
                    continue;
                }

                $player = $this->CI->player_model->getPlayerById($player_id);
                $player_username = $player->username;

                // $gameDate = new \DateTime($entwinedata->deal_enddate);
                // $gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);
                $startDate = $entwinedata->deal_startdate;
                $endDate = $entwinedata->deal_enddate;

                list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($entwinedata, $unknownGame, $gameDescIdMap);

                $extra_info=['table'=>$entwinedata->uniqueid];

                $this->syncGameLogs($game_type_id,
                    $game_description_id,
                    $entwinedata->gameshortcode,
                    $entwinedata->game_type,
                    $entwinedata->game,
                    $player_id,
                    $player_username,
                    $bet_amount,
                    $result_amount,
                    null, # win_amount
                    null, # loss_amount
                    null, # after_balance
                    0, # has_both_side
                    $entwinedata->external_uniqueid,
                    $startDate,
                    $endDate,
                    $entwinedata->response_result_id,
                    Game_logs::FLAG_GAME, $extra_info);
            }
        } else {
            $rlt = array('success' => true);
        }
        return $rlt;
    }

    /**
     * overview : game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */
    private function getGameDescriptionInfo($row, $unknownGame, $gameDescIdMap) {
        $game_description_id = null;
        if (isset($row->game_description_id)) {
            $game_description_id = $row->game_description_id;
        }
        $game_type_id = null;
        if (isset($row->game_type_id)) {
            $game_type_id = $row->game_type_id;
        }

        $externalGameId = $row->gameshortcode;
        $extra = array('game_code' => $row->gameshortcode);
        if (empty($game_description_id)) {
            //search game_description_id by code
            if (isset($gameDescIdMap[$externalGameId]) && !empty($gameDescIdMap[$externalGameId])) {
                $game_description_id = $gameDescIdMap[$externalGameId]['game_description_id'];
                $game_type_id = $gameDescIdMap[$externalGameId]['game_type_id'];
                if ($gameDescIdMap[$externalGameId]['void_bet'] == 1) {
                    return array(null, null);
                }
            }
        }

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $row->game, $row->game_type, $externalGameId, $extra,
            $unknownGame);
    }


    /**
     * overview : revert the broken game
     *
     * @param $playerName
     * @return array
     */
    public function revertBrokenGame($playerName) {
        return $this->returnUnimplemented();
    }

    protected function httpCallApi($url, $params) {
        list($header, $resultText, $statusCode, $statusText, $errCode, $error, $resultObj) = parent::httpCallApi($url, $params);
        $resultText = @gzdecode($resultText) ? : $resultText;
        return array($header, $resultText, $statusCode, $statusText, $errCode, $error, $resultObj);
    }

}

/*end of file*/