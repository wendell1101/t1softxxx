<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';
/*
 * FB SPORTS Single Wallet API Controller
 * OGP-33679
 *
 * @author  Jerbey Capoquian
 * Docs: 
 * https://doc.newsportspro.com/single_wallet.en.html
 * https://doc.newsportspro.com/h5_pc_doc.en.html
 * https://doc.newsportspro.com/apidoc_data.en.html

    Integration Process
    Functions to Develop：

    1. Complete FB data service interface integration and can create users, get tokens and get orders (data service document address)
    2. Complete callback interface integration and can query user balance, deduct credit, check credit deduction result, payout, update order, update cashout order (document address)
    3. Complate app service interface integration if want to build your own frontend(for example native app) and can get match list, match statistics, match details, update odds before placing bets, bet interface, order list; get cashout price, get cashout order, query cashout order result; place reserve bet, reserve bet list; place reserve cashout order, query reserve cashout order list (App service document address)
    4. Config callback address in Merchant Platform(http://merch.newsportspro.com/#/login). The callback address is the service domain of callback interface，e.g：https://test.channel33333.com
    5. Retry mechanism：
        Order and cashout order related data will be retried 6 times within 8 hours after first failed push.
        Transaction related data will be tried 20 times within 50 hours after first failed push.


    0 to 1 Integration Process:

    1. Merchant calls the FB data service interface "Create User" to create user
    2. Merchant calls FB data service interface "Get Token" to get token as well as domains, including api, pc url and h5 url.
    3. Store the data that returned at step 2.
    4. Call FB app service with the token for the inteface "Match List with Odds" or "Match Details" to get odds to display in the frontend. Note: Token is required for all the interface, and the api domain name is the domain of FB app service.
    5. Get the latest odds for an option using the interface "Query Option's Odds" before bet placed
    6. Call the interface "Single Bet(Batch Allowed)" and "Parlay Bet" to place single bet and parlay bet respectively.
    7. After receiving bet request, FB service will call the interface "Channel balance query"(/fb/callback/balance) to check whether the balance is sufficient. If passed, FB will create an order and asynchronously call the interface "Channel payment"(/fb/callback/order_pay) to deduct credit from user. After a series of verifications passed, the bet is successfully placed.
    8. Bets are pcocessed asynchronously. The order status will be unconfirmed after bets placed. Please query the interface "Query the status of bet"(/v1/order/getStakeOrderStatus) to get the order status untill either confirmed or rejected.
    9. Once order settled, cancelled, rejected etc, FB service will call the interface "Transaction data push"(/fb/callback/sync_transaction) to send deduction or payout messages, and channel should process deduction and payout operations immediately.
    10. FB service will call the interface "Order data push"(/fb/callback/sync_orders) to push order details to merchant at the same time with step 9.
*/

class Fbsports_seamless_service_api extends BaseController {
    use Seamless_service_api_module;

    const ERROR_CODE_SUCCESS = 0;
    const ERROR_CODE_FAIL = 1;
    const ERROR_CODE_SYSTEM_ERROR = 6;
    const ERROR_CODE_BALANCE = 9;
    const SINGLE_LEVEL = "1x1*1";
    const METHOD_NO_VALIDATION = ['health'];
    const ALLOWED_METHOD = ['balance', 'order_pay', 'sync_transaction', 'check_order_pay', 'health', 'sync_orders', 'sync_cashout'];
    const METHOD_RULES_PARAMS = [
        "balance" => array(
            "merchantUserId" => "Required|String",
            "merchantId" => "Required|String",
            "currencyId" => "Integer",
        ),
        "order_pay" => array(
            "transactionId" => "Required|String",
            "userId" => "Required|String",
            "merchantId" => "Required|String",
            "merchantUserId" => "Required|String",
            "businessId" => "Required|String",
            "transactionType" => "Required|String",
            "transferType" => "Required|String",
            "currencyId" => "Required|Integer",
            "amount" => "Required|Numeric|Negative",
            "status" => "Required|Integer",
            "relatedId" => "String",
            "thirdRemark" => "String",
        ),
        "sync_transaction" => array(
            "transactionId" => "Required|String",
            "userId" => "Required|String",
            "merchantId" => "Required|String",
            "merchantUserId" => "Required|String",
            "businessId" => "Required|String",
            "transactionType" => "Required|String",
            "transferType" => "Required|String",
            "currencyId" => "Required|Integer",
            "amount" => "Required|Numeric",
            "status" => "Required|Integer",
            "relatedId" => "String",
            "thirdRemark" => "String",
        ),
        "check_order_pay" => array(
            "transactionId" => "Required|String",
            "userId" => "Required|String",
            "merchantId" => "Required|String",
            "merchantUserId" => "Required|String",
            "businessId" => "Required|String",
            "transactionType" => "Required|String",
            "transferType" => "Required|String",
            "currencyId" => "Required|Integer",
            "amount" => "Required|Numeric|Negative",
            "status" => "Required|Integer",
            "relatedId" => "String",
            "thirdRemark" => "String",
        ),
        "health" => [],
        "sync_orders" => array(
            "id" => "Required|String",
            "rejectReason" => "Integer",
            "rejectReasonStr" => "String",
            "userId" => "Required|String",
            "merchantId" => "Required|String",
            "merchantUserId" => "Required|String",
            "currency" => "Required|Integer",
            "exchangeRate" => "Required|String",
            "seriesType" => "Required|Integer",
            "betType" => "Required|String",
            "allUp" => "Required|Integer",
            "allUpAlive" => "Integer",
            "stakeAmount" => "Required|String",
            "liabilityStake" => "String",
            "settleAmount" => "String",#remove required
            "orderStatus" => "Required|Integer",
            "payStatus" => "Integer",
            "oddsChange" => "Integer",#remove required
            "device" => "String",
            "ip" => "String",
            "settleTime" => "String",
            "createTime" => "Required|String",
            "modifyTime" => "Required|String",
            "cancelTime" => "String",
            "thirdRemark" => "String",
            "relatedId" => "String",
            "maxWinAmount" => "String",
            "loseAmount" => "String",
            "rollBackCount" => "Integer",
            "itemCount" => "Required|Integer",
            "seriesValue" => "Required|Integer",
            "betNum" => "Required|Integer",
            "cashOutTotalStake" => "String",
            "liabilityCashoutStake" => "String",
            "cashOutPayoutStake" => "String",
            "reserveId" => "String",
            "cashOutCount" => "Integer",
            "unitStake" => "String",
            "reserveVersion" => "Integer",
            "betList" => "Required",
            "maxStake" => "String",
            "validSettleStakeAmount" => "String",
            "validSettleAmount" => "String",
            "cashOutCancelStake" => "String",
            "walletType" => "Integer",
            "version" => "Required|Integer",
        ),
        "sync_cashout" => array(
            "id" => "Required|String",
            "orderId" => "Required|String",
            "userId" => "Required|String",
            "merchantId" => "Required|String",
            "merchantUserId" => "Required|String",
            "walletType" => "Required|Integer",
            "currency" => "Required|Integer",
            "exchangeRate" => "Numeric",
            "cashoutTime" => "Required|String",
            "betTime" => "Required|String",
            "settleTime" => "String",
            "createTime" => "String",
            "cancelTime" => "String",
            "cashOutStake" => "Required|Numeric",
            "orderStatus" => "Required|Integer",
            "cashOutPayoutStake" => "Numeric",
            "acceptOddsChange" => "Required",
            "seriesType" => "Required|Integer",
            "betType" => "Required|String",
            "orderStakeAmount" => "Numeric",
            "ip" => "String",
            "remark" => "String",
            "cancelReasonCode" => "Integer",
            "cancelCashOutAmountTo" => "Numeric",
            "unitCashOutPayoutStake" => "Numeric",
            "device" => "String",
            "version" => "Required|Integer",
            "lastModifyTime" => "String",
        ),
    ];

    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'original_seamless_wallet_transactions', 'external_system', 'player_model', 'game_description_model', 'multiple_db_model'));
    }

    private function getGamePlatformId(){
        return FBSPORTS_SEAMLESS_GAME_API;
    }

    /**
     * getCurrencyAndValidateDB
     * @param  array $reqParams
     * @return [type]            [description]
     */
    private function getCurrencyAndValidateDB($currency) {
        if(!empty($currency)) {
            # Get Currency Code for switching of currency and db forMDB
            $valid = $this->validateCurrencyAndSwitchDB($currency);
            return $valid;
        } else {
            return false;
        }
    }

    protected function validateCurrencyAndSwitchDB($currency){
        if(!$this->utils->isEnabledMDB()){
            return true;
        }
        if(empty($currency)){
            return false;
        }else{
            $currency = strtolower($currency);
            //validate currency name
            if(!$this->utils->isAvailableCurrencyKey($currency)){
                //invalid currency name
                return false;
            }else{
                //switch to target db
                $_multiple_db=Multiple_db::getSingletonInstance();
                $_multiple_db->switchCIDatabase($currency);
                return true;
            }
        }
    }

    private function getCurrencyCode($id){
        switch ($id) {
            case 1 :
                $code = "CNY";
                break; 
            case 2 :
                $code = "USD";
                break; 
            case 3 :
                $code = "EUR";
                break; 
            case 4 :
                $code = "GBP";
                break; 
            case 5 :
                $code = "HKD";
                break; 
            case 6 :
                $code = "TWD";
                break; 
            case 7 :
                $code = "MYR";
                break; 
            case 8 :
                $code = "SGD";
                break; 
            case 9 :
                $code = "THB";
                break; 
            case 10:
                $code = "VND";
                break; 
            case 11:
                $code = "KRW";
                break; 
            case 12:
                $code = "JPY";
                break; 
            case 13:
                $code = "PHP";
                break; 
            case 14:
                $code = "IDR";
                break; 
            case 15:
                $code = "INR";
                break; 
            case 16:
                $code = "AUD";
                break; 
            case 17:
                $code = "MMK";
                break; 
            case 18:
                $code = "COP";
                break; 
            case 19:
                $code = "TZS";
                break; 
            case 20:
                $code = "NGN";
                break; 
            case 21:
                $code = "ZMW";
                break; 
            case 22:
                $code = "BRL";
                break;
            
            default:
                $code = null;
                break;
        }
        $this->utils->debug_log("==> fbsports currency code: {$code}");
        return $code;
    }

    //Initial callback request
    public function index($method = null){
        $this->requestBody = urldecode(file_get_contents("php://input"));
        $this->requestHeaders = $this->input->request_headers();
        $this->gamePlatformId = $this->getGamePlatformId();
        $this->remoteWalletEnabled = $this->ssa_enabled_remote_wallet();
        $this->paramsToInsertIncaseFailed = [];
        $this->remoteFailedTransactions = [];
        $this->uniqueIdOfSeamlessService = null;
        $this->playerId = null;
        $this->gameUsername = null;
        $this->method = $method;
        $this->saveToSuper = false;
        $this->currencyId = null;
        $this->message = null;

        $response = [];
        $errorMessage = "";
        $errorCode = self::ERROR_CODE_SYSTEM_ERROR;
        $requestArray = json_decode($this->requestBody, true);

        if(isset($requestArray['currencyId'])){
            $this->currencyId = $requestArray['currencyId'];
        }
        if(isset($requestArray['currency'])){
            $this->currencyId = $requestArray['currency'];
        }
        
        try {
            if($method == "sync_transaction"){
                return $this->sync_transaction($requestArray);
            }

            if($method == "health"){
                $this->saveToSuper = true;
                return $this->health();
            }

            if($method == "balance" && empty($this->currencyId)){
                $this->saveToSuper = true;
                return $this->getCurrencyBalance($requestArray);
            }

            if($this->currencyId){
                $currency = $this->getCurrencyCode($this->currencyId);
                $isDBswitchSuccess = $this->getCurrencyAndValidateDB($currency);
                if(!$isDBswitchSuccess){
                    throw new Exception(__LINE__.":Invalid Currency.", self::ERROR_CODE_SYSTEM_ERROR);
                }

                $this->api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
                if(!$this->api) {
                    throw new Exception(__LINE__.":Invalid API.", self::ERROR_CODE_SYSTEM_ERROR);
                }

                if(empty($this->method) 
                    || !method_exists($this, $this->method) 
                    || !in_array($this->method, self::ALLOWED_METHOD) 
                    || empty($this->requestBody)
                ){
                    throw new Exception(__LINE__.":Invalid Params.", self::ERROR_CODE_FAIL);
                }

                if($this->external_system->isGameApiMaintenance($this->gamePlatformId)){
                    throw new Exception(__LINE__.":The game is on maintenance.", self::ERROR_CODE_SYSTEM_ERROR);   
                }

                if(!$this->api->validateWhiteIP()){
                    throw new Exception(__LINE__.":Invalid IP.", self::ERROR_CODE_FAIL);
                }

                $this->validateParams($requestArray);  
                $this->authenticate($requestArray);
                list($errorCode, $response) = $this->$method($requestArray);
            }

        } catch (Exception $e) {
            $this->utils->debug_log('==> fbsports encounter error at line and message', $e->getMessage());
            $messageArray = explode(":", $e->getMessage());
            $errorMessage = isset($messageArray[1]) ? $messageArray[1] : "";
            $errorCode = $e->getCode();
        }

        return $this->setResponse($errorCode, $response, $errorMessage);
    }

    #Query user's balance at channel。Please note that if the currencyId in the interface request parameter is not empty, the currency of the interface response data needs to be consistent with the currencyId in the request parameter.
    private function balance(){
        return [self::ERROR_CODE_SUCCESS, [
                "data" => [
                    [
                        'balance' => $this->api->dBtoGameAmount($this->getPlayerBalance()),
                        'currencyId' => $this->currencyId
                    ]
                ]
            ]
        ];
    }

    private function getCurrencyBalance($params){

        if(empty($params)){
            throw new Exception(__LINE__.":Empty params.", self::ERROR_CODE_FAIL);
        }

        $gameUsername = isset($params['merchantUserId']) ? $params['merchantUserId'] : null;
        if(empty($gameUsername)){
            throw new Exception(__LINE__.":Empty merchantUserId.", self::ERROR_CODE_FAIL); 
        }

        $currencyBalance  = [];
        $error = [];
        $gamePlatformId = $this->gamePlatformId;
        $_this = $this;
        $this->CI->multiple_db_model->foreachMultipleDBWithoutSuper(function($db) use($_this, &$error, &$currencyBalance, $gameUsername, $gamePlatformId){
            $this->db = $db;
            $dbName= $db->getOgTargetDB();

            $this->utils->debug_log("==> fbsports foreachMultipleDBWithoutSuper: {$dbName}");
            if(!$this->external_system->isGameApiActive($gamePlatformId)){
                $error[$dbName] = "isGameApiActive";
                return false;
            }

            if($this->external_system->isGameApiMaintenance($gamePlatformId)){
                $error[$dbName] = "isGameApiMaintenance";
                return false;
            }

            $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->getGamePlatformId());
            $playerId = isset($playerDetails['player_id']) ? $playerDetails['player_id'] : null;
            if(empty($playerId) || empty($playerDetails)){
                $error[$dbName] = "getPlayerCompleteDetailsByGameUsername";
                return false; 
            }

            if($this->player_model->isBlocked($playerId)){
                $error[$dbName] = "isBlocked";
                return false;
            }

            $currencyId = $this->getCurrencyIdByCurrencyCode($dbName);
            if(empty($currencyId)){
                $error[$dbName] = "currencyId";
                return false;
            }

            $systemData = $this->external_system->getSystemById($gamePlatformId);
            $rate = 1;#default
            $precision = 2;#default
            $whitelistIpList = [];
            if(!empty($systemData)){
                $extraInfo = $systemData->live_mode ? $systemData->extra_info : $systemData->sandbox_extra_info;
                $extraInfo = json_decode($extraInfo, true);
                $rate = isset($extraInfo['conversion_rate']) ? $extraInfo['conversion_rate'] : $rate;
                $precision = isset($extraInfo['conversion_precision']) ? $extraInfo['conversion_precision'] : $precision;
                $whitelistIpList = isset($extraInfo['backend_api_white_ip_list']) ? $extraInfo['backend_api_white_ip_list'] : $whitelistIpList;
            }

            if(!empty($whitelistIpList)){
                if(!$this->validateWhiteIP($whitelistIpList)){
                    $error[$dbName] = "validateWhiteIP";
                    return false;
                }
            }

            $useReadonly = true;
            $balance =  $this->player_model->getPlayerSubWalletBalance($playerId, $gamePlatformId, $useReadonly);
            $balance = $this->dBtoGameAmount($balance, $rate, $precision);

            $currencyBalance[] = array(
                "balance" => $balance,
                "currencyId" => $currencyId
            );
        });
        $this->utils->debug_log('==> fbsports getCurrencyBalance error', $error);
        return $this->setResponse(self::ERROR_CODE_SUCCESS, ['data' => $currencyBalance]);
    }

    /**
     * for white ip
     * @return boolean $success
     */
    private function validateWhiteIP($whiteIpList = array()){
        $success=false;
        //init white ip info
        $this->load->model(['ip']);
        $success=$this->ip->checkWhiteIpListForAdmin(function ($ip, &$payload) use($whiteIpList){
            $this->utils->debug_log('search ip', $ip);
            if($this->ip->isDefaultWhiteIP($ip)){
                $this->utils->debug_log('it is default white ip', $ip);
                return true;
            }
            foreach ($whiteIpList as $whiteIp) {
                if($this->utils->compareIP($ip, $whiteIp)){
                    $this->utils->debug_log('found white ip', $whiteIp, $ip);
                    //found
                    return true;
                }
            }
            //not found
            return false;
        }, $payload);

        $this->utils->debug_log('get key info', $success);
        return $success;
    }

    private function dBtoGameAmount($amount, $rate = 1, $precision= 2) {
        $conversion_rate = floatval($rate);
        $value = floatval($amount * $conversion_rate);
        $precision = intval($precision);
        return round($value,$precision);
    }

    private function gameAmountToDBTruncateNumber($amount, $rate = 1, $precision = 2) {
        if($amount==0){
            return $amount;
        }

        $conversion_rate = floatval($rate);
        $precision = intval($precision);

        //compute amount with conversion rate
        $value = floatval($amount / $conversion_rate);

        return bcdiv($value, 1, $precision);
    }

    private function getCurrencyIdByCurrencyCode($currencyCode){
        switch (strtolower($currencyCode)) {
            case 'cny':
                return 1;
                break;
            case 'usd':
                return 2;
                break;
            case 'eur':
                return 3;
                break;
            case 'thb':
                return 9;
                break;
            case 'vnd':
                return 10;
                break;
            case 'krw':
                return 11;
                break;
            case 'jpy':
                return 12;
                break;
            case 'php':
                return 13;
                break;
            case 'idr':
                return 14;
                break;
            case 'aud':
                return 15;
                break;
            case 'php':
                return 13;
                break;
            case 'cop':
                return 18;
                break;
            case 'brl':
                return 22;
                break;
            case 'usdt':
                return 200;
                break;
            case 'btc':
                return 201;
                break;
            case 'eth':
                return 202;
                break;
            default:
                return null;
                break;
        }
    }

    #Whenever a cashout order placed and cancelled, FB service will call this interface to push cashout order to the channel that they should update. If channel processes successfully, please return code = 0, otherwise code = 1 and the order will be retried. FB supports partially cashout which means there could be several cashout orders that related to one original order.
    private function sync_cashout($params){
        $params['external_uniqueid'] = $params['cashOutOrderId'] = isset($params['id']) ? "cashout-".$params['id'] : null;
        $affectedRows = $this->preProcessOrder($params);
        if($affectedRows > 0){
            return [self::ERROR_CODE_SUCCESS, ["data" => []]];
        } else {
            return [self::ERROR_CODE_FAIL, ["data" => []]];
        }
    }

    #Whenever an order is settled, cancelled, rollbacked and rejected, FB service will call this interface to push order to the channel that they should update. If channel processes successfully, please return code = 0, otherwise code = 1 and the order will be retried.
    private function sync_orders($params){
        $params['external_uniqueid'] = $params['orderId'] = isset($params['id']) ? $params['id'] : null;
        $affectedRows = $this->preProcessOrder($params);
        if($affectedRows > 0){
            return [self::ERROR_CODE_SUCCESS, ["data" => []]];
        } else {
            return [self::ERROR_CODE_FAIL, ["data" => []]];
        }
    }

    private function preProcessOrder($params){
        $dataToInsert = array(
            "cashOutOrderId" => isset($params['cashOutOrderId']) ? $params['cashOutOrderId'] : null,
            "orderId" => isset($params['orderId']) ? $params['orderId'] : null,
            "rejectReason" => isset($params['rejectReason']) ? $params['rejectReason'] : null,
            "rejectReasonStr" => isset($params['rejectReasonStr']) ? $params['rejectReasonStr'] : null,
            "userId" => isset($params['userId']) ? $params['userId'] : null,
            "merchantId" => isset($params['merchantId']) ? $params['merchantId'] : null,
            "merchantUserId" => isset($params['merchantUserId']) ? $params['merchantUserId'] : null,
            "currency" => isset($params['currency']) ? $params['currency'] : null,
            "exchangeRate" => isset($params['exchangeRate']) ? $params['exchangeRate'] : null,
            "seriesType" => isset($params['seriesType']) ? $params['seriesType'] : null,
            "betType" => isset($params['betType']) ? $params['betType'] : null,
            "allUp" => isset($params['allUp']) ? $params['allUp'] : null,
            "allUpAlive" => isset($params['allUpAlive']) ? $params['allUpAlive'] : null,
            "orderStakeAmount" => isset($params['orderStakeAmount']) ? $params['orderStakeAmount'] : null,
            "stakeAmount" => isset($params['stakeAmount']) ? $params['stakeAmount'] : null,
            "liabilityStake" => isset($params['liabilityStake']) ? $params['liabilityStake'] : null,
            "settleAmount" => isset($params['settleAmount']) ? $params['settleAmount'] : null,
            "orderStatus" => isset($params['orderStatus']) ? $params['orderStatus'] : null,
            "payStatus" => isset($params['payStatus']) ? $params['payStatus'] : null,
            "oddsChange" => isset($params['oddsChange']) ? $params['oddsChange'] : null,
            "device" => isset($params['device']) ? $params['device'] : null,
            "ip" => isset($params['ip']) ? $params['ip'] : null,
            "cashoutTime" => isset($params['cashoutTime']) ? $params['cashoutTime'] : null,
            "betTime" => isset($params['betTime']) ? $params['betTime'] : null,
            "settleTime" => isset($params['settleTime']) ? date('Y-m-d H:i:s', $params['settleTime']/1000) : null,
            "createTime" => isset($params['createTime']) ? date('Y-m-d H:i:s', $params['createTime']/1000) : null,
            "modifyTime" => isset($params['modifyTime']) ? date('Y-m-d H:i:s', $params['modifyTime']/1000) : null,
            "cancelTime" => isset($params['cancelTime']) ? date('Y-m-d H:i:s', $params['cancelTime']/1000) : null,
            "lastModifyTime" => isset($params['lastModifyTime']) ? date('Y-m-d H:i:s', $params['lastModifyTime']/1000) : null,
            "remark" => isset($params['remark']) ? $params['remark'] : null,
            "thirdRemark" => isset($params['thirdRemark']) ? $params['thirdRemark'] : null,
            "relatedId" => isset($params['relatedId']) ? $params['relatedId'] : null,
            "maxWinAmount" => isset($params['maxWinAmount']) ? $params['maxWinAmount'] : null,
            "loseAmount" => isset($params['loseAmount']) ? $params['loseAmount'] : null,
            "rollBackCount" => isset($params['rollBackCount']) ? $params['rollBackCount'] : null,
            "itemCount" => isset($params['itemCount']) ? $params['itemCount'] : null,
            "seriesValue" => isset($params['seriesValue']) ? $params['seriesValue'] : null,
            "betNum" => isset($params['betNum']) ? $params['betNum'] : null,
            "cashOutTotalStake" => isset($params['cashOutTotalStake']) ? $params['cashOutTotalStake'] : null,
            "cashOutStake" => isset($params['cashOutStake']) ? $params['cashOutStake'] : null,
            "liabilityCashoutStake" => isset($params['liabilityCashoutStake']) ? $params['liabilityCashoutStake'] : null,
            "cashOutPayoutStake" => isset($params['cashOutPayoutStake']) ? $params['cashOutPayoutStake'] : null,
            "acceptOddsChange" => isset($params['acceptOddsChange']) ? $params['acceptOddsChange'] : null,
            "reserveId" => isset($params['reserveId']) ? $params['reserveId'] : null,
            "cashOutCount" => isset($params['cashOutCount']) ? $params['cashOutCount'] : null,
            "unitStake" => isset($params['unitStake']) ? $params['unitStake'] : null,
            "reserveVersion" => isset($params['reserveVersion']) ? $params['reserveVersion'] : null,
            "betList" => isset($params['betList']) ? json_encode($params['betList']) : null,
            "maxStake" => isset($params['maxStake']) ? $params['maxStake'] : null,
            "validSettleStakeAmount" => isset($params['validSettleStakeAmount']) ? $params['validSettleStakeAmount'] : null,
            "validSettleAmount" => isset($params['validSettleAmount']) ? $params['validSettleAmount'] : null,
            "cashOutCancelStake" => isset($params['cashOutCancelStake']) ? $params['cashOutCancelStake'] : null,
            "cancelReasonCode" => isset($params['cancelReasonCode']) ? $params['cancelReasonCode'] : null,
            "cancelCashOutAmountTo" => isset($params['cancelCashOutAmountTo']) ? $params['cancelCashOutAmountTo'] : null,
            "unitCashOutPayoutStake" => isset($params['unitCashOutPayoutStake']) ? $params['unitCashOutPayoutStake'] : null,
            "walletType" => isset($params['walletType']) ? $params['walletType'] : null,
            "version" => isset($params['version']) ? $params['version'] : null,
            "request_id" => $this->utils->getRequestId(), 
            "external_uniqueid" => isset($params['external_uniqueid']) ? $params['external_uniqueid'] : null,
            "md5_sum" => isset($params['md5_sum']) ? $params['md5_sum'] : null,
            "external_gameid" => isset($params['betType']) ? $params['betType'] : null,
        );
        
        if($params['betType'] == self::SINGLE_LEVEL){
            if(isset($params['betList'][0]['sportId'])){ #try get sport id if single level
                $dataToInsert['external_gameid'] = $params['betList'][0]['sportId'];
            }
        } else {
            $dataToInsert['external_gameid'] = "parlay";
        }

        $dataToInsert['md5_sum'] = $this->original_seamless_wallet_transactions->generateMD5SumOneRow($dataToInsert, ['orderStatus', 'version', 'lastModifyTime', 'createTime', 'cancelTime', 'settleTime', 'orderStatus', 'external_gameid'], ['settleAmount']);

        $uniqueid = $dataToInsert['external_uniqueid'];
        $row = $this->original_seamless_wallet_transactions->querySingleTransactionCustom('fbsports_seamless_wallet_game_records', ['external_uniqueid'=> $uniqueid],['id', 'md5_sum']);
        
        if(!empty($row)){
            if($row['md5_sum'] != $dataToInsert['md5_sum']){
                $id = $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom('fbsports_seamless_wallet_game_records', ['id' => $row['id']], $dataToInsert);
            } else {
                $id = $row['id'];
            }
        } else {
            $id = $this->original_seamless_wallet_transactions->insertTransactionData('fbsports_seamless_wallet_game_records', $dataToInsert);
        }

        return $id;
    }

    #FB service will call this interface very 3 seconds to check if channel service is availble. Availble - code = 0, unavailable code =1.
    private function health(){
        return $this->setResponse(self::ERROR_CODE_SUCCESS, ["data" => []]);
    }

    #If there is any exception that we enccountered when calling the interface "Deduct User Credit" and can not get transaction status, for instance timeout, FB service will call this interface to check if the credit has been deducted successfully by merchant.
    private function check_order_pay($params){
        $uniqueid = isset($params['transactionId']) ? $params['transactionId'] : null;
        $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom('fbsports_seamless_wallet_transactions', ['external_uniqueid'=> $uniqueid],['id', 'after_balance']);
        if(!empty($transactionDetails)){
            return [self::ERROR_CODE_SUCCESS, ["data" => []]];
        }

        return [self::ERROR_CODE_FAIL, ["data" => [["transactionid" => $uniqueid]]]];
    }

    #When placing a bet, FB service will create an order and asynchronously call this interface to deduct credit from user. Return code will be: 0 success, 1 failure, 6 exception handling, 9 insufficient balance. The field "transactionType" in this interface could only be "OUT" and "transferType" is "BET".
    private function order_pay($params){
        $uniqueid = isset($params['transactionId']) ? $params['transactionId'] : null;
        $transactionType = isset($params['transactionType']) ? $params['transactionType'] : null;
        $transferType = isset($params['transferType']) ? $params['transferType'] : null;

        if($transactionType != "OUT"){
            throw new Exception(__LINE__.":Request transactionType invalid.", self::ERROR_CODE_FAIL);
        }

        if($transferType != "BET"){
            throw new Exception(__LINE__.":Request transferType invalid.", self::ERROR_CODE_FAIL);
        }

        $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom('fbsports_seamless_wallet_transactions', ['external_uniqueid'=> $uniqueid],['id', 'after_balance']);
        if(!empty($transactionDetails)){
            $this->message = "Duplicate transaction. No adjustment happen.";
            return [self::ERROR_CODE_SUCCESS, ['balance' => $this->api->dBtoGameAmount($this->getPlayerBalance())]];
        }

        $errorCode = self::ERROR_CODE_SYSTEM_ERROR;
        $response = [];
        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use($params, &$response, &$errorCode, $uniqueid) {
            $success = false;
            $amount = isset($params['amount']) ? $this->api->gameAmountToDBTruncateNumber($params['amount']) : null;
            $params['amount'] = $amount;#save converted amount
            $amountToDeduct = abs($amount);

            $beforeBalance = $this->getPlayerBalance();
            $afterBalance =  null;
            if($beforeBalance === false){
                return false;
            }
            
            $params['before_balance'] = $beforeBalance;
            $params['after_balance'] = $beforeBalance;
            $params['external_uniqueid'] = $uniqueid;
            $params['sbe_status'] = GAME_LOGS::STATUS_PENDING;

            $this->transId = $this->processRequestData($params);
            if($this->transId){
                if($this->utils->compareResultFloat($amountToDeduct, '>', 0)) {
                    if($this->utils->compareResultFloat($amountToDeduct, '>', $beforeBalance)) {
                        $errorCode = self::ERROR_CODE_BALANCE;
                        return false;
                    }
                }

                if(($this->remoteWalletEnabled)){
                    $this->uniqueIdOfSeamlessService = $this->getGamePlatformId().'-'.$uniqueid;   
                    $this->wallet_model->setUniqueidOfSeamlessService($this->uniqueIdOfSeamlessService);
                    $this->wallet_model->setGameProviderActionType('bet');
                    $this->wallet_model->setGameProviderRoundId($params['businessId']);
                    $this->wallet_model->setGameProviderBetAmount($amount);
                    $this->wallet_model->setGameProviderIsEndRound(false);
                } 

                if($amountToDeduct > 0){ 
                    // $success = $this->wallet_model->decSubWallet($this->playerId, $this->getGamePlatformId(), $amount, $afterBalance);
                    $success = $this->wallet_model->decMainWallet($this->playerId, $amountToDeduct, $afterBalance);
                    
                } else if($amountToDeduct == 0 ){
                    $success = true;
                }

                if(!$success){ #error on adjustment, check if unique id already process
                    if ($this->remoteWalletEnabled) {
                        if (!empty($this->ssa_get_remote_wallet_error_code()) && $this->ssa_remote_wallet_error_double_unique_id()) {
                            $this->utils->debug_log('==> fbsports double unique');
                            $success = true; #override success
                        }
                        $this->remoteFailedTransactions[] = $uniqueid;
                    }
                }

                if($success){
                    if(is_null($afterBalance)){
                        $afterBalance = $this->getPlayerBalance();
                        if($afterBalance === false){
                            return false;
                        }
                    }

                    $errorCode = self::ERROR_CODE_SUCCESS;
                    $response = array(
                        "balance" => (string)$this->api->dBtoGameAmount($afterBalance)
                    );

                    $json_response = [
                        "message" => "",
                        "data" => array(
                            "balance" => (string)$this->api->dBtoGameAmount($afterBalance),
                            "currencyId" => $this->currencyId
                        ),
                        "code" => $errorCode
                    ];

                    $dataToUpdate = array(
                        "after_balance" => $afterBalance,
                        "json_response" => json_encode($json_response)
                    );

                    if($this->remoteWalletEnabled){
                        $dataToUpdate['remote_wallet_status'] = $this->ssa_get_remote_wallet_error_code();
                        $dataToUpdate['seamless_service_unique_id'] = $this->uniqueIdOfSeamlessService;
                    }

                    $this->paramsToInsertIncaseFailed[$params['external_uniqueid']]['after_balance'] = $afterBalance;
                    $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom('fbsports_seamless_wallet_transactions', ['id' => $this->transId], $dataToUpdate);
                    
                }
            }
            return $success;
        });

        return [$errorCode, $response];
    }

    function sortTransactions($params, $sortkey){
        foreach ($params as $key=>$val) $output[$val[$sortkey]][]=$val;
        return $output;
    }

    #Whenever an order is settled, cancelled, rollbacked and rejected, FB service will call this interface to push transactions to the channel that they should process. The interface supports batch processing. If all the transactions are processed unsuccessfully, please return code = 1. Otherwise return code = 0 but if any transaction failed, bring the transaction IDs in the response.
    private function sync_transaction($params){
        if(empty($params)){
            throw new Exception(__LINE__.":Empty params.", self::ERROR_CODE_FAIL);
        }
        
        $countParams = count($params);
        $failedTransactions = [];
        $params = $this->sortTransactions($params, 'currencyId');
        $countCurrency = count($params);
        if($countCurrency > 1){
            $this->saveToSuper = true;
        }

        $this->CI->multiple_db_model->foreachMultipleDBWithoutSuper(function($db) use($params, &$failedTransactions){
            $dbName= $db->getOgTargetDB();
            $currencyId = $this->getCurrencyIdByCurrencyCode($dbName);
            if(!isset($params[$currencyId])){
                return false;
            }

            $this->db = $db; #override db for query

            $currencyParams = $params[$currencyId];
            if(empty($currencyParams)){
                return false;
            }

            $typeOut = ['BET', 'CANCEL_DEDUCT', 'SETTLEMENT_ROLLBACK_DEDUCT', 'CASHOUT_CANCEL_ROLLBACK_DEDUCT'];
            $typeIn = ['WIN', 'REFUND', 'CASHOUT', 'CANCEL_RETURN', 'CASHOUT_CANCEL_RETURN', 'CASHOUT_CANCEL_ROLLBACK_RETURN'];

            foreach ($currencyParams as $key => $cParam) {
                $valid = $this->validateParamsV2($cParam);  
                $playerId = $this->authenticateV2($cParam);
                if(!$playerId || !$valid){
                    $failedTransactions[] = array("transactionId" => $cParam['transactionId'], "message" => "Account not valid.");
                    continue;
                }

                $transactionType = isset($cParam['transactionType']) ? strtoupper($cParam['transactionType']) : null;
                $transferType = isset($cParam['transferType']) ? strtoupper($cParam['transferType']) : null;
                $reqAmount = isset($cParam['amount']) ? $cParam['amount'] : null;

                #out is negative only
                #in is positive only
                if(in_array($transferType, $typeOut) && $transactionType != "OUT"){
                    $failedTransactions[] = array("transactionId" => $cParam['transactionId'], "message" => "Invalid transaction type.");
                    continue;
                }

                if(in_array($transferType, $typeIn) && $transactionType != "IN"){
                    $failedTransactions[] = array("transactionId" => $cParam['transactionId'], "message" => "Invalid transaction type.");
                    continue;
                }

                if($transactionType == "OUT" && $reqAmount > 0){# type out and postive
                    $failedTransactions[] = array("transactionId" => $cParam['transactionId'], "message" => "Invalid transactionType and amount.");
                    continue;
                }

                if($transactionType == "IN" && $reqAmount < 0){ #type in and negative
                    $failedTransactions[] = array("transactionId" => $cParam['transactionId'], "message" => "Invalid transactionType and amount.");
                    continue;
                }


                $businessId = isset($cParam['businessId']) ? $cParam['businessId'] : null;
                $isBetExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom('fbsports_seamless_wallet_transactions', ['business_id'=> $businessId,  'method' => 'order_pay']);
                if(!$isBetExist){
                    $failedTransactions[] = array("transactionId" => $cParam['transactionId'], "message" => "businessId not exist.");
                    continue;
                }
                

                $cancelCode = 0;
                $uniqueid = isset($cParam['transactionId']) ? $cParam['transactionId'] : null;
                $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom('fbsports_seamless_wallet_transactions', ['external_uniqueid'=> $uniqueid],['id', 'after_balance']);

                $isCancelBet = false;
                if(!empty($transactionDetails)){
                    if(strtoupper($cParam['transferType']) == "BET" && $cParam['status'] == $cancelCode){
                        $isCancelBet = true;
                    } else {
                        continue;
                    }
                } else {
                    if(strtoupper($cParam['transferType']) == "BET"){ #if type is BET but not exist, next loop
                        $failedTransactions[] = array("transactionId" => $cParam['transactionId'], "message" => "(BET)transactionId not exist.");
                        continue;
                    }
                }

                if($isCancelBet){
                    $cancelId = "C".$uniqueid;
                    $isCancelExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom('fbsports_seamless_wallet_transactions', ['external_uniqueid'=> $cancelId]);
                    if($isCancelExist){
                        $failedTransactions[] = array("transactionId" => $cParam['transactionId'], "message" => "transactionId already cancelled.");
                        continue;
                    }

                    $uniqueid = $cancelId;
                    $cParam['amount'] = abs($cParam['amount']);#override amount
                }

                $success = $this->lockAndTransForPlayerBalance($playerId, function() use($cParam, $uniqueid, $playerId) {
                    $systemData = $this->external_system->getSystemById($this->gamePlatformId);
                    $rate = 1;#default
                    $precision = 2;#default
                    if(!empty($systemData)){
                        $extraInfo = $systemData->live_mode ? $systemData->extra_info : $systemData->sandbox_extra_info;
                        $extraInfo = json_decode($extraInfo, true);
                        $rate = isset($extraInfo['conversion_rate']) ? $extraInfo['conversion_rate'] : $rate;
                        $precision = isset($extraInfo['conversion_precision']) ? $extraInfo['conversion_precision'] : $precision;
                    }

                    $success = false;
                    $amount = isset($cParam['amount']) ? $this->gameAmountToDBTruncateNumber($cParam['amount'], $rate, $precision) : null;
                    $cParam['amount'] = $amount;#save converted amount

                    $beforeBalance = $this->getPlayerBalance($playerId);
                    $afterBalance =  null;
                    if($beforeBalance === false){
                        return false;
                    }
                    
                    $cParam['player_id'] = $playerId;
                    $cParam['before_balance'] = $beforeBalance;
                    $cParam['after_balance'] = $beforeBalance;
                    $cParam['external_uniqueid'] = $uniqueid;
                    $cParam['sbe_status'] = $this->getStatusByTransferType($cParam['transferType']);#try guess by transfer type

                    $transId = $this->processRequestData($cParam);
                    if($transId){
                        if(($this->remoteWalletEnabled)){
                            $this->uniqueIdOfSeamlessService = $this->getGamePlatformId().'-'.$uniqueid;   
                            $this->wallet_model->setUniqueidOfSeamlessService($this->uniqueIdOfSeamlessService);
                            $actionType = $this->getProviderActionType($cParam['transferType']);
                            $this->wallet_model->setGameProviderActionType($actionType);
                            $this->wallet_model->setGameProviderRoundId($cParam['businessId']);
                        } 

                        if($amount > 0){ #increase if possitive
                            $amountToAdd = $amount;
                            // $success = $this->wallet_model->incSubWallet($playerId, $this->getGamePlatformId(), $amountToAdd, $afterBalance);
                            $success = $this->wallet_model->incMainWallet($playerId, $amountToAdd, $afterBalance);
                        } else if($amount < 0 ){#decrease if negative
                            $amountToDeduct = abs($amount);
                            if(($this->remoteWalletEnabled)){
                                // $success = $this->wallet_model->decSubWallet($playerId, $this->getGamePlatformId(), $amountToDeduct, $afterBalance);
                                $success =  $this->wallet_model->decMainWallet($playerId, $amountToDeduct, $afterBalance);
                            } else {
                                // $success = $this->wallet_model->decSubWalletAllowNegative($playerId, $this->getGamePlatformId(), $amountToDeduct);
                                // $success =  $this->wallet_model->decMainWallet($playerId, $amountToDeduct, $afterBalance);
                                $success = $this->wallet_model->decMainWalletAllowNegative($playerId, $amountToDeduct, $afterBalance);
                            }
                        } else if($amount == 0 ){
                            $success = true;
                        }

                        if(!$success){ #error on adjustment, check if unique id already process
                            if ($this->remoteWalletEnabled) {
                                if (!empty($this->ssa_get_remote_wallet_error_code()) && $this->ssa_remote_wallet_error_double_unique_id()) {
                                    $this->utils->debug_log('==> fbsports double unique');
                                    $success = true; #override success
                                }
                                $this->remoteFailedTransactions[] = $uniqueid;
                            }
                        }

                        if($success){
                            if(is_null($afterBalance)){
                                $afterBalance = $this->getPlayerBalance($playerId);
                                if($afterBalance === false){
                                    return false;
                                }
                            }

                            $dataToUpdate = array(
                                "after_balance" => $afterBalance,
                            );

                            if($this->remoteWalletEnabled){
                                $dataToUpdate['remote_wallet_status'] = $this->ssa_get_remote_wallet_error_code();
                                $dataToUpdate['seamless_service_unique_id'] = $this->uniqueIdOfSeamlessService;
                            }
                            
                            $this->paramsToInsertIncaseFailed[$cParam['external_uniqueid']]['after_balance'] = $afterBalance;
                            $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom('fbsports_seamless_wallet_transactions', ['id' => $transId], $dataToUpdate);
                            
                        }
                    }
                    return $success;
                });
                
                if(!$success){
                    $failedTransactions[] = array("transactionId" => $cParam['transactionId']);
                }
                return $success;
            }
        });

        if(empty($failedTransactions)){
            $errorCode = self::ERROR_CODE_SUCCESS;
        } else {
            if(count($failedTransactions) ==  $countParams){
                $errorCode = self::ERROR_CODE_FAIL;
            } else {
                $errorCode = self::ERROR_CODE_SUCCESS;
            }
        }

        $response = array("data" => $failedTransactions);
        return $this->setResponse($errorCode, $response);
    }

    private function getProviderActionType($transferType){
        switch (strtoupper($transferType)) {
            case 'WIN':
                return "payout";
                break;
            case 'REFUND':
            case 'SETTLEMENT_ROLLBACK_DEDUCT':
            case 'RESERVE_SUCCESS_REFUND':
            case 'RESERVE_FAIL_REFUND':
            case 'RESERVE_CANCEL_REFUND':
                return "refund";
                break;
            case 'CANCEL_DEDUCT':
            case 'CANCEL_RETURN':
            case 'CASHOUT_CANCEL_DEDUCT':
            case 'CASHOUT_CANCEL_RETURN':
            case 'CASHOUT_CANCEL_ROLLBACK_DEDUCT':
            case 'CASHOUT_CANCEL_ROLLBACK_RETURN':
                return "cancel";
                break;
            default:
                return "adjustment";
                break;
        }
    }

    #guess possible status, but not used as real status of business id or bet
    private function getStatusByTransferType($transferType){
        switch (strtoupper($transferType)) {
            case 'BET':
            case 'RESERVE_BET':
                return GAME_LOGS::STATUS_PENDING;
                break;
            case 'WIN':
            case 'CASHOUT':
                return GAME_LOGS::STATUS_SETTLED;
                break;
            case 'REFUND':
            case 'SETTLEMENT_ROLLBACK_DEDUCT':
            case 'RESERVE_SUCCESS_REFUND':
            case 'RESERVE_FAIL_REFUND':
            case 'RESERVE_CANCEL_REFUND':
                return GAME_LOGS::STATUS_REFUND;
                break;
            case 'CANCEL_DEDUCT':
            case 'CANCEL_RETURN':
            case 'CASHOUT_CANCEL_DEDUCT':
            case 'CASHOUT_CANCEL_RETURN':
            case 'CASHOUT_CANCEL_ROLLBACK_DEDUCT':
            case 'CASHOUT_CANCEL_ROLLBACK_RETURN':
                return GAME_LOGS::STATUS_CANCELLED;
                break;
            
            default:
                return null;
                break;
        }
    }

    //Function to merge default ouput and response
    private function setResponse($errorCode, $response = [], $errorMessage = "") {
        // $balance = isset($response['balance']) ? $response['balance'] : 0;
        // if($errorCode != self::ERROR_CODE_SUCCESS && $this->playerId){
        //     $balance = $this->api->dBtoGameAmount($this->getPlayerBalance());
        // }

        $defaultResponse = [
            "message" => $errorMessage,
            "data" => [],
            "code" => $errorCode
        ];

        if(isset($response['data'])){
            $defaultResponse['data'] = $response['data'];
        }

        if(!empty($this->message)){
            $defaultResponse['message'] = $this->message;
        }

        return $this->setOutput($errorCode, $defaultResponse);
    }

    //Function to return output and save response and request
    private function setOutput($errorCode, $response = []){
        if($this->saveToSuper){
            $this->db = $this->multiple_db_model->getSuperDBFromMDB();
        }
        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partialOutputOnError = false;
        $statusCode = 0;

        $extraFields = [
            "full_url" => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI'])
        ];
        
        if($this->playerId){
            $extraFields = [
                'player_id'=> $this->playerId
            ];
        }

        $flag = $errorCode == self::ERROR_CODE_SUCCESS ?  Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        
        $responseResultId = $this->response_result->saveResponseResult(
            $this->gamePlatformId,
            $flag,
            $this->method,
            json_encode($this->requestBody),
            $response,
            200,
            null,
            is_array($this->requestHeaders) ? json_encode($this->requestHeaders) : $this->requestHeaders,
            $extraFields
        );
        $methodSaveError = ["order_pay"];# save only bet incase failed
        if($flag == Response_result::FLAG_ERROR && in_array($this->method, $methodSaveError)){
            if(!empty($this->remoteFailedTransactions)){
                foreach ($this->remoteFailedTransactions as $uniqueid) {
                    if(array_key_exists($uniqueid, $this->paramsToInsertIncaseFailed)){
                        $dataToInsert = $this->paramsToInsertIncaseFailed[$uniqueid];
                        $dataToInsert['is_failed'] = true;
                        $dataToInsert['external_uniqueid'] = "failed-".$dataToInsert['external_uniqueid'];
                        $dataToInsert['json_response'] = json_encode($response);
                        if($this->remoteWalletEnabled){
                            $dataToInsert['remote_wallet_status'] = $this->ssa_get_remote_wallet_error_code();
                            $dataToInsert['seamless_service_unique_id'] = $this->uniqueIdOfSeamlessService;
                        }
                        $this->original_seamless_wallet_transactions->tableName = 'fbsports_seamless_wallet_transactions';
                        $this->original_seamless_wallet_transactions->insertIgnoreRow($dataToInsert);
                    }
                }
            }
        }
        return $this->returnJsonResult((object)$response, $addOrigin, $origin, $pretty, $partialOutputOnError, $statusCode);
    }


    #Function to validate params
    private function validateParams($params){
        if(empty($params)){
            throw new Exception(__LINE__.":Empty params.", self::ERROR_CODE_FAIL);
        }
        $this->gameUsername = isset($params['merchantUserId']) ? $params['merchantUserId'] : null;
        $rules = self::METHOD_RULES_PARAMS;
        $rules = isset($rules[$this->method]) ? $rules[$this->method] : [];
        if(!empty($rules)){
            foreach($rules as $key => $rule){
                $key_rules = explode("|", $rule);
                if(!empty($key_rules)){
                    foreach ($key_rules as $keyi => $key_rule) {
                        if($key_rule == 'Required' && !isset($params[$key])){
                            $this->utils->error_log("==> fbsports SEAMLESS SERVICE: (validateParams) Missing Parameter: ". $key, $params, $rules);   
                            throw new Exception(__LINE__.":Required param({$key}).", self::ERROR_CODE_FAIL);
                        }
                        
                        if($key_rule == 'Numeric'  && isset($params[$key]) && !is_numeric($params[$key])){
                            $this->utils->error_log("==> fbsports SEAMLESS SERVICE: (validateParams) Parameters is not Numeric: ". $key . '=' . $params[$key], $params, $rules);   
                            throw new Exception(__LINE__.":Param({$key}) should be Numeric.", self::ERROR_CODE_FAIL);
                        }

                        if($key_rule == 'String'  && isset($params[$key]) && !is_string($params[$key])){
                            $this->utils->error_log("==> fbsports SEAMLESS SERVICE: (validateParams) Parameters is not string: ". $key . '=' . $params[$key], $params, $rules);   
                            throw new Exception(__LINE__.":Param({$key}) should be string.", self::ERROR_CODE_FAIL);
                        }

                        if($key_rule == 'Array'  && isset($params[$key]) && !is_array($params[$key])){
                            $this->utils->error_log("==> fbsports SEAMLESS SERVICE: (validateParams) Parameters is not array: ". $key ,$params[$key], $params, $rules);   
                            throw new Exception(__LINE__.":Param({$key}) should be object.", self::ERROR_CODE_FAIL);
                        }

                        if($key_rule=='NonNegative' && isset($params[$key]) && $params[$key] < 0){
                            $this->utils->error_log("==> fbsports SEAMLESS SERVICE: (validateParams) Parameters is less than 0: ". $key . '=' . $params[$key], $params, $rules); 
                            throw new Exception(__LINE__.":Param({$key}) should be >= 0.", self::ERROR_CODE_FAIL);
                        }

                        if($key_rule=='Negative' && isset($params[$key]) && $params[$key] >= 0){
                            $this->utils->error_log("==> fbsports SEAMLESS SERVICE: (validateParams) Parameters is greater than 0: ". $key . '=' . $params[$key], $params, $rules); 
                            throw new Exception(__LINE__.":Param({$key}) should be < 0.", self::ERROR_CODE_FAIL);
                        }
                    }
                } else {
                    throw new Exception(__LINE__.":Empty rules.", self::ERROR_CODE_FAIL);
                }
            }  
        } else {
            throw new Exception(__LINE__.":Empty rules.", self::ERROR_CODE_FAIL);
        }

        return true;
    }

     #Function to validate params
    private function validateParamsV2($params){
        if(empty($params)){
            return false;
        }
        $this->gameUsername = isset($params['merchantUserId']) ? $params['merchantUserId'] : null;
        $rules = self::METHOD_RULES_PARAMS;
        $rules = isset($rules[$this->method]) ? $rules[$this->method] : [];
        if(!empty($rules)){
            foreach($rules as $key => $rule){
                $key_rules = explode("|", $rule);
                if(!empty($key_rules)){
                    foreach ($key_rules as $keyi => $key_rule) {
                        if($key_rule == 'Required' && !isset($params[$key])){
                            $this->utils->error_log("==> fbsports SEAMLESS SERVICE: (validateParams) Missing Parameter: ". $key, $params, $rules);   
                            return false;
                        }
                        
                        if($key_rule == 'Numeric'  && isset($params[$key]) && !is_numeric($params[$key])){
                            $this->utils->error_log("==> fbsports SEAMLESS SERVICE: (validateParams) Parameters is not Numeric: ". $key . '=' . $params[$key], $params, $rules);   
                            return false;
                        }

                        if($key_rule == 'String'  && isset($params[$key]) && !is_string($params[$key])){
                            $this->utils->error_log("==> fbsports SEAMLESS SERVICE: (validateParams) Parameters is not string: ". $key . '=' . $params[$key], $params, $rules);   
                            return false;
                        }

                        if($key_rule == 'Array'  && isset($params[$key]) && !is_array($params[$key])){
                            $this->utils->error_log("==> fbsports SEAMLESS SERVICE: (validateParams) Parameters is not array: ". $key ,$params[$key], $params, $rules);   
                            return false;
                        }

                        if($key_rule=='NonNegative' && isset($params[$key]) && $params[$key] < 0){
                            $this->utils->error_log("==> fbsports SEAMLESS SERVICE: (validateParams) Parameters is less than 0: ". $key . '=' . $params[$key], $params, $rules); 
                           return false;
                        }

                        if($key_rule=='Negative' && isset($params[$key]) && $params[$key] >= 0){
                            $this->utils->error_log("==> fbsports SEAMLESS SERVICE: (validateParams) Parameters is greater than 0: ". $key . '=' . $params[$key], $params, $rules); 
                            return false;
                        }
                    }
                } else {
                    return false;
                }
            }  
        } else {
            return false;
        }

        return true;
    }

    #Function for player authentication
    private function authenticate($params){
        if(empty($params)){
            throw new Exception(__LINE__.":Empty params.", self::ERROR_CODE_FAIL);
        }

        $gameUsername = isset($params['merchantUserId']) ? $params['merchantUserId'] : null;
        if(empty($gameUsername)){
            throw new Exception(__LINE__.":Empty merchantUserId.", self::ERROR_CODE_FAIL); 
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->getGamePlatformId());
        if(empty($playerDetails)){
            throw new Exception(__LINE__.":Player not found.", self::ERROR_CODE_FAIL); 
        }

        $playerId = isset($playerDetails['player_id']) ? $playerDetails['player_id'] : null;
        if(empty($playerId)){
            throw new Exception(__LINE__.":Player not found.", self::ERROR_CODE_FAIL); 
        }
        $this->playerId = $playerId;

        if($this->api->isBlockedUsernameInDB($gameUsername) || $this->player_model->isBlocked($this->playerId)){
            throw new Exception(__LINE__.":Player is blocked.", self::ERROR_CODE_FAIL);
        }
        return $playerId;
    }

    private function authenticateV2($params){
        if(empty($params)){
            return false;
        }

        $gameUsername = isset($params['merchantUserId']) ? $params['merchantUserId'] : null;
        if(empty($gameUsername)){
            return false; 
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->getGamePlatformId());
        if(empty($playerDetails)){
            return false; 
        }

        $playerId = isset($playerDetails['player_id']) ? $playerDetails['player_id'] : null;
        if(empty($playerId)){
            return false;
        }

        if($this->player_model->isBlocked($playerId)){
            return false;
        }
        return $playerId;
    }

    private function processRequestData($params){
        $dataToInsert = array(
            "player_id" => $this->playerId,
            "game_platform_id" => $this->getGamePlatformId(),
            "method" => $this->method,

            #params data
            "transaction_id" => isset($params['transactionId']) ? $params['transactionId'] : NULL, 
            "user_id" => isset($params['userId']) ? $params['userId'] : NULL, 
            "merchant_id" => isset($params['merchantId']) ? $params['merchantId'] : NULL, 
            "merchant_user_id" => isset($params['merchantUserId']) ? $params['merchantUserId'] : NULL, 
            "business_id" => isset($params['businessId']) ? $params['businessId'] : NULL, 
            "transaction_type" => isset($params['transactionType']) ? $params['transactionType'] : NULL, 
            "transfer_type" => isset($params['transferType']) ? $params['transferType'] : NULL, 
            "currency_id" => isset($params['currencyId']) ? $params['currencyId'] : NULL, 
            "amount" => isset($params['amount']) ? $params['amount'] : NULL, 
            "status" => isset($params['status']) ? $params['status'] : NULL,
            "related_id" => isset($params['relatedId']) ? $params['relatedId'] : NULL,
            "third_remark" => isset($params['thirdRemark']) ? $params['thirdRemark'] : NULL,

            #remote wallet 
            "remote_wallet_status" => isset($params['remote_wallet_status']) ? $params['remote_wallet_status'] : NULL,
            "is_failed" => isset($params['is_failed']) ? $params['is_failed'] : NULL,
            "seamless_service_unique_id" => isset($params['seamless_service_unique_id']) ? $params['seamless_service_unique_id'] : NULL,

            #sbe default
            "json_request" => $this->requestBody,
            "json_response" => isset($params['json_response']) ? $params['json_response'] : NULL,
            "sbe_status" => isset($params['sbe_status']) ? $params['sbe_status'] : NULL,
            "before_balance" => isset($params['before_balance']) ? $params['before_balance'] : NULL,
            "after_balance" => isset($params['after_balance']) ? $params['after_balance'] : NULL,
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000),
            "request_id" => $this->utils->getRequestId(), 
            "md5_sum" => null,
            "external_uniqueid" => isset($params['external_uniqueid']) ? $params['external_uniqueid'] : NULL, 
        );

        if(isset($params['player_id'])){
            $dataToInsert['player_id'] = $params['player_id'];
        }
        $this->paramsToInsertIncaseFailed[$params['external_uniqueid']] = $dataToInsert;
        $transId = $this->original_seamless_wallet_transactions->insertTransactionData('fbsports_seamless_wallet_transactions', $dataToInsert);
        return $transId;
    }

    //Function to get balance of exist player
    private function getPlayerBalance($playerId = null){
        $useReadonly = true;
        $playerId = $playerId ? $playerId : $this->playerId;
        if($playerId){
            return $this->player_model->getPlayerSubWalletBalance($playerId, $this->gamePlatformId, $useReadonly);
        } else {
            return false;
        }
    }
}

