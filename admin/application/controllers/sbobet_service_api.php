<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Sbobet_service_api extends BaseController {

    const APICALL_GETBALANCE = "GetBalance";
    const APICALL_DEDUCT = "Deduct";
    const APICALL_SETTLE = "Settle";
    const APICALL_ROLLBACK = "Rollback";
    const APICALL_CANCEL = "Cancel";
    const APICALL_TIP = "Tip";
    const APICALL_BONUS = "Bonus";
    const APICALL_GETBETSTATUS = "GetBetStatus";
    const APICALL_RETURN_STAKE = "ReturnStake";

    const TRANSACTION_TYPE_DEDUCT = "DeductDeduct";
    const TRANSACTION_TYPE_ADDITIONAL_DEDUCT = "AdditionalDeduct";
    const TRANSACTION_TYPE_ADDITIONAL_INCREASE = "AdditionalAdd";
    const TRANSACTION_TYPE_SETTLE_ADD = "SettleAdd";
    const TRANSACTION_TYPE_SETTLE_DEDUCT = "SettleDeduct";
    const TRANSACTION_TYPE_ROLLBACK_DEDUCT = "RollbackDeduct";
    const TRANSACTION_TYPE_CANCEL_DEDUCT = "CancelDeduct";
    const TRANSACTION_TYPE_TIP_DEDUCT = "TipDeduct";
    const TRANSACTION_TYPE_BONUS_ADD = "BonusAdd";
    const TRANSACTION_TYPE_CANCEL_ADD = "CancelAdd";
    const TRANSACTION_TYPE_RETURN_STAKE = "ReturnStake";

    const BET_STATUS_SETTLED = "settled";
    const BET_STATUS_CANCELLED = "void";
    const BET_STATUS_RUNNING = "running";

    # error codes constant
    const CODE_SUCCESS = 0;
    const CODE_ERROR_PLAYER_DOES_NOT_EXISTS = 1;
    const CODE_ERROR_INVALID_IP = 2;
    const CODE_ERROR_USERNAME_EMPTY = 3;
    const CODE_ERROR_METHOD_NOT_ALLOWED = 15;
    const CODE_ERROR_WRONG_COMPANY_KEY = 4;
    const CODE_ERROR_USER_NOT_ENOUGH_BALANCE = 5;
    const CODE_ERROR_BET_DOES_NOT_EXISTS = 6;
    const CODE_ERROR_INTERNAL_ERROR = 7;
    const CODE_ERROR_DUPLICATE_TRANSFER_CODE = 5003;
    const CODE_ROLLBACK_EXISTS = 2003;
    const CODE_ERROR_WRONG_JSON_FORMAT = 10;
    const CODE_ERROR_PLAYER_IS_BLOCKED = 11;
    const CODE_ERROR_BET_IS_SETTLED_ALREADY = 2001;
    const CODE_ERROR_BET_IS_CANCELLED_ALREADY = 2002;
    const CODE_ERROR_TIP_ALREADY_EXISTS = 13;
    const CODE_ERROR_BONUS_ALREADY_EXISTS = 14;
    const CODE_ERROR_BET_ALREADY_RETURNED_STAKE = 5008;

    const API_MSG = [
                        self::CODE_SUCCESS => "No Error!",
                        self::CODE_ERROR_WRONG_COMPANY_KEY => "Wrong Company Key!",
                        self::CODE_ERROR_WRONG_JSON_FORMAT => "Wrong JSON Format!",
                        self::CODE_ERROR_PLAYER_DOES_NOT_EXISTS => "The member is not exist!",
                        self::CODE_ERROR_USER_NOT_ENOUGH_BALANCE => "Not Enough Balance!",
                        self::CODE_ERROR_DUPLICATE_TRANSFER_CODE => "Bet with same refNo already exists.",
                        self::CODE_ROLLBACK_EXISTS => "Bet Already Rollback!",
                        self::CODE_ERROR_PLAYER_IS_BLOCKED => "Player is blocked!",
                        self::CODE_ERROR_BET_DOES_NOT_EXISTS => "Bet Does not exists!",
                        self::CODE_ERROR_METHOD_NOT_ALLOWED => "Method Does Not Allowed!",
                        self::CODE_ERROR_INTERNAL_ERROR => "Internal Error!",
                        self::CODE_ERROR_BET_IS_CANCELLED_ALREADY => "Bet Already Canceled!",
                        self::CODE_ERROR_TIP_ALREADY_EXISTS => "Tip exists already!",
                        self::CODE_ERROR_BONUS_ALREADY_EXISTS => "Bonus exists already!",
                        self::CODE_ERROR_BET_IS_SETTLED_ALREADY => "Bet Already Settled!",
                        self::CODE_ERROR_USERNAME_EMPTY => "Username is empty!",
                        self::CODE_ERROR_BET_ALREADY_RETURNED_STAKE => "Bet already returned stake.",
                        self::CODE_ERROR_INVALID_IP => "The IP is invalid.",
                    ];

    const PRODUCT_TYPE_SPORTSBOOK = 1;
    const PRODUCT_TYPE_SBOGAMES = 3;
    const PRODUCT_TYPE_LIVECASINO = 7;
    const PRODUCT_TYPE_SLOTS = 9;

    const GAME_TYPE_SPORTSBOOK = [1];
    const GAME_TYPE_RNGLIVECASINO = [201,203,204,205,207,208,511,513,514,515,517,518];
    const GAME_TYPE_VIRTUAL_SPORTS = [200000,202601,201601,202602,201604];
    const GAME_TYPE_LIVECASINO = [1,3,4,5,7,9];
    const GAME_TYPE_WANMEI = [1000101,1000102,1000103,1000104,1000105,
                              1000106,1000107,1000108,1000109,1000110,
                              1000111];

    const RESULT_TYPE_WON = 0;
    const RESULT_TYPE_LOSS = 1;
    const RESULT_TYPE_TIE = 2;

    #GAME PROVIDERS
    const AFBGAMING_GPID = 1016;
    
    private $transaction_for_fast_track = null;

    function __construct() {
        parent::__construct();
        $this->load->model(['wallet_model',
                            'game_provider_auth',
                            'common_token',
                            'player_model',
                            'sbobet_seamless_game_transactions_model',
                            'sbobet_seamless_game_logs_model',
                            'external_system'
                          ]);
        $gamePlatformId = SBOBET_SEAMLESS_GAME_API;
        $this->game_api_sbo_seamless = $this->utils->loadExternalSystemLibObject($gamePlatformId);
        $this->gamePlatformId = $gamePlatformId;
    }

    /*
     *  We will use this API to get member's balance.
     */
    public function getBalance()
    {
        $result = $this->processRequest(self::APICALL_GETBALANCE);
        $playerId = $result['playerId'];

        #Default response (Error)
        $errorCode =  $result['errorCode'];
        $extra=null;

        if(isset($result['isError']) && $result['isError']){
            $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
            return;
        }

        $this->lockAndTransForPlayerBalance($playerId, function() use($result, &$errorCode, &$extra) {
            $playerId = $result['playerId'];
            $gameUsername = $result['gameUsername'];
            $balance = $this->getPlayerWalletBalance($playerId);
            $errorCode = self::CODE_SUCCESS;
            $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($balance)];
            return true;
        });

        $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
        return;
    }

    /*
     * In Sports, the same transferCode can't Deduct twice.
     * In VirtualSports, the same transferCode can't Deduct twice.
     *
     * In Casino, the same transferCode can Deduct twice,
     * but 2nd Deduct amount must be greater than 1st Deduct.
     *
     * In 3rd Wan Mei, the same transferCode and same transactionId can't Deduct twice,
     * but same transferCode can Deduct another transactionId.
     */
    public function deduct()
    {
        $result = $this->processRequest(self::APICALL_DEDUCT);
        $playerId = $result['playerId'];

        #Default response (Error)
        $errorCode =  $result['errorCode'];
        $extra=null;

        if(isset($result['isError']) && $result['isError']){
            $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
            return;
        }

        $this->lockAndTransForPlayerBalance($playerId, function() use($result, &$errorCode, &$extra) {

            $playerId = isset($result['playerId'])  ? $result['playerId'] : null;
            $transferCode = isset($result['transferCode'])  ? $result['transferCode'] : null;
            $transactionId = isset($result['transactionId'])  ? $result['transactionId'] : null;
            $gameUsername = isset($result['gameUsername'])  ? $result['gameUsername'] : null;
            $productType = isset($result['productType'])  ? $result['productType'] : null;
            $gameType = isset($result['gameType'])  ? $result['gameType'] : null;
            $betAmount = isset($result['amount'])  ? $this->game_api_sbo_seamless->gameAmountToDB($result['amount']) : null;
            $externalUniqueId = $this->generateExternalUniqueId($productType,$gameType,$transferCode,$transactionId);
            $gameId = isset($result['gameId'])  ? $result['gameId'] : null;
            $this->externalGameId = isset($result['extraInfo']['sportType'])  ? $result['extraInfo']['sportType'] : $gameId;
            $this->commonBetId = $externalUniqueId;
            // $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
            // if(!empty($enabled_remote_wallet_client_on_currency)){
            //     $uniqueIdOfSeamlessService=$this->gamePlatformId.'-'.$externalUniqueId;      
            //     $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
            // }

            # Check if player is blocked
            $isPlayerBlockedAll = $this->player_model->isBlocked($playerId);
            if($isPlayerBlockedAll){
                $errorCode = self::CODE_ERROR_PLAYER_IS_BLOCKED;
                return true; #return success
            }

            $beforeBalance = $this->getPlayerWalletBalance($playerId);
            if($beforeBalance == null){
                $errorCode = self::CODE_ERROR_INTERNAL_ERROR;
                $extra = ["beforeBalance"=>$this->game_api_sbo_seamless->dBtoGameAmount($beforeBalance),"betAmount"=>$this->game_api_sbo_seamless->dBtoGameAmount($betAmount)];
                return true; #return success
            }

            $hasEnoughBalance = $this->utils->compareResultFloat($beforeBalance,">=",$betAmount);
            if(!$hasEnoughBalance){
                $errorCode = self::CODE_ERROR_USER_NOT_ENOUGH_BALANCE;
                $extra = ["beforeBalance"=>$this->game_api_sbo_seamless->dBtoGameAmount($beforeBalance),"betAmount"=>$this->game_api_sbo_seamless->dBtoGameAmount($betAmount)];
                return true; #return success
            }

            $isBetStatusSettledAlready = $this->sbobet_seamless_game_logs_model->checkIfBetIsSettledAlready($externalUniqueId);
            if($isBetStatusSettledAlready){
                $errorCode = self::CODE_ERROR_DUPLICATE_TRANSFER_CODE;
                $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($this->getPlayerWalletBalance($playerId))];
                return true; #return success
            }

            $isCancelled = $this->sbobet_seamless_game_logs_model->isBetCancelledAlready($externalUniqueId);
            if($isCancelled){
                $errorCode = self::CODE_ERROR_DUPLICATE_TRANSFER_CODE;
                $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($this->getPlayerWalletBalance($playerId))];
                return true; #return success
            }

            if($this->sbobet_seamless_game_logs_model->isExternalUniqueIdAlreadyExist($externalUniqueId))
            {
                # Rule for existing transfer code:
                # -if product type and game type is sports or if game type is virtual sports,
                #  do not allow duplicate transfer code
                if($productType == self::PRODUCT_TYPE_SPORTSBOOK || in_array($gameType,self::GAME_TYPE_VIRTUAL_SPORTS)){
                    $errorCode = self::CODE_ERROR_DUPLICATE_TRANSFER_CODE;
                    return true; #return success
                }
                if($productType == self::PRODUCT_TYPE_SLOTS && in_array($gameType,self::GAME_TYPE_WANMEI)){
                    $errorCode = self::CODE_ERROR_DUPLICATE_TRANSFER_CODE;
                    return true; #return success
                }

                # -In Casino, the same transferCode can Deduct twice,
                #  but 2nd Deduct amount must be greater than 1st Deduct.
                if(($productType == self::PRODUCT_TYPE_LIVECASINO && in_array($gameType,self::GAME_TYPE_LIVECASINO)) || ($productType == self::PRODUCT_TYPE_SBOGAMES && in_array($gameType,self::GAME_TYPE_RNGLIVECASINO))){

                    $previousBetAmount = $this->sbobet_seamless_game_logs_model->getBetAmountByExternalUniqueId($externalUniqueId);

                    # do only if bet amount is greater than previuous
                    if($betAmount > $previousBetAmount)
                    {
                        $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                        if(!empty($enabled_remote_wallet_client_on_currency)){
                            $uniqueIdOfSeamlessService=$this->gamePlatformId.'-'.$betAmount.'-'.$externalUniqueId;      
                            $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                        }
                        $additionalDeductAmt = $betAmount - $previousBetAmount;
                        $isAdditionalDeduct = $this->subtractAmount($playerId,$additionalDeductAmt,$this->gamePlatformId);

                        $afterBalance = $this->getPlayerWalletBalance($playerId);

                        if($isAdditionalDeduct){
                            # Update the previuos bet amount in game logs,
                            # before balance and after balance
                            $updateData = [
                                            "bet_amount" => $betAmount,
                                            "updated_at" => $this->utils->getNowForMysql(),
                                          ];
                            $this->sbobet_seamless_game_logs_model->updateBetAmountByExternalUniqueId($externalUniqueId,$updateData);

                            $insertData = [
                                "transaction_type" => self::TRANSACTION_TYPE_ADDITIONAL_DEDUCT,
                                "gameusername" => $gameUsername,
                                "game_type" => $gameType,
                                "product_type" => $productType,
                                "transfer_code" => $transferCode,
                                "transaction_id" => $transactionId,
                                "amount" => $additionalDeductAmt,
                                "before_balance" => $beforeBalance,
                                "after_balance" => $afterBalance,
                                "response_result_id" => $this->response_result_id,
                                "unique_transaction_id" => $externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique(),
                            ];

                            $this->utils->debug_log("SBOBET_SEAMLESS_API TRANSACTION_TYPE_ADDITIONAL_DEDUCT-CASINO insertData: ",$insertData);

                            if($this->doInsertToGameTransactions($insertData))
                            {
                                $errorCode = self::CODE_SUCCESS;
                                $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($afterBalance),"betAmount" => $this->game_api_sbo_seamless->dBtoGameAmount($betAmount)];
                                return true;
                            }
                        }
                    }
                }

                // $errorCode = self::CODE_ERROR_DUPLICATE_TRANSFER_CODE;
                $errorCode = self::CODE_ERROR_INTERNAL_ERROR;
                return true; #return success
            } else {
                $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                if(!empty($enabled_remote_wallet_client_on_currency)){
                    $uniqueIdOfSeamlessService=$this->gamePlatformId.'-'.$betAmount.'-'.$externalUniqueId;     
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                }
                $isDeduct = $this->subtractAmount($playerId,$betAmount,$this->gamePlatformId);
                if($isDeduct)
                {
                    $afterBalance = $this->getPlayerWalletBalance($playerId);

                    $insertData = [
                        "transaction_type" => self::TRANSACTION_TYPE_DEDUCT,
                        "gameusername" => $gameUsername,
                        "game_type" => $gameType,
                        "product_type" => $productType,
                        "transfer_code" => $transferCode,
                        "transaction_id" => $transactionId,
                        "amount" => $betAmount,
                        "before_balance" => $beforeBalance,
                        "after_balance" => $afterBalance,
                        "response_result_id" => $this->response_result_id,
                        "unique_transaction_id" => $externalUniqueId,
                    ];

                    $this->utils->debug_log("SBOBET_SEAMLESS_API ACTION_DEDUCT-NORMAL_GAME insertData: ",$insertData);

                    if($this->doInsertToGameTransactions($insertData))
                    {
                        $gameLogsData = [
                                         "gameusername" => $gameUsername,
                                         "bet_amount" => $betAmount,
                                         "product_type" => $betAmount,
                                         "game_type" => $gameType,
                                         "product_type" => $productType,
                                         "external_uniqueid" => $externalUniqueId,
                                         "status" => self::BET_STATUS_RUNNING,
                                        ];

                        if($this->doInsertToGameLogs($gameLogsData))
                        {
                            $errorCode = self::CODE_SUCCESS;
                            $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($afterBalance),"betAmount" => $this->game_api_sbo_seamless->dBtoGameAmount($betAmount)];
                            return true;
                        }
                    }
                }
            }

            return false; #default error
        });


        $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
        return;
    }

    private function addAmount($player_id,$amount,$game_platform_id)
    {
        $success = $this->wallet_model->incSubWallet($player_id, $game_platform_id, $amount);
        $this->utils->debug_log('SBOBET_SEAMLESS_API add_amount', 'player_id', $player_id, 'amount', $amount, 'success', $success);
        return $success;
    }

    private function subtractAmount($player_id,$amount,$game_platform_id)
    {
        $success = $this->wallet_model->decSubWallet($player_id, $game_platform_id, $amount);
        $this->utils->debug_log('SBOBET_SEAMLESS_API subtract_amount', 'player_id', $player_id, 'amount', $amount, 'success', $success);
        return $success;
    }

    private function generateExternalUniqueId($productType,$gameType,$transferCode,$transactionId=null){
        if(($productType != self::PRODUCT_TYPE_SLOTS) || ($productType == self::PRODUCT_TYPE_SLOTS && in_array($gameType,self::GAME_TYPE_WANMEI))){
            return $transferCode."-".$transactionId;
        }else{
            return $transferCode;
        }
    }

    /*
     * We will use this API to send the result of bet for you to settle the bet.
     * This API may be requested by us many times under the same bet,
     * which means we resettle the bet.
     */
    public function settle()
    {
        $result = $this->processRequest(self::APICALL_SETTLE);
        $playerId = $result['playerId'];

        #Default response (Error)
        $errorCode =  $result['errorCode'];
        $extra=null;

        if(isset($result['isError']) && $result['isError']){
            $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
            return;
        }

        $this->lockAndTransForPlayerBalance($playerId, function() use($result, &$errorCode, &$extra) {

            $transferCode = isset($result['transferCode']) ? $result['transferCode'] : null;
            $resultType = isset($result['resultType']) ? $result['resultType'] : null;
            $resultAmount = isset($result['winloss']) ? $this->game_api_sbo_seamless->gameAmountToDB($result['winloss']) : 0;
            if(isset($result['winLoss'])){
                $resultAmount = $this->game_api_sbo_seamless->gameAmountToDB($result['winLoss']);
            }
            $playerId = isset($result['playerId']) ? $result['playerId'] : null;
            $gameUsername = isset($result['gameUsername']) ? $result['gameUsername'] : null;
            $productType = isset($result['productType']) ? $result['productType'] : null;
            $productType = isset($result['ProductType']) ? $result['ProductType'] : $productType;
            $gameType = isset($result['gameType']) ? $result['gameType'] : null;
            $gameType = isset($result['GameType']) ? $result['GameType'] : $gameType;

            $transactionId = $this->sbobet_seamless_game_transactions_model->getTransactionIdByTransferCode($transferCode,self::TRANSACTION_TYPE_DEDUCT);
            $externalUniqueId = $this->generateExternalUniqueId($productType,$gameType,$transferCode,$transactionId);
            $gameId = isset($result['gameId'])  ? $result['gameId'] : null;
            $this->externalGameId = isset($result['extraInfo']['sportType'])  ? $result['extraInfo']['sportType'] : $gameId;
            $this->commonBetId = $externalUniqueId;
            $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
            if(!empty($enabled_remote_wallet_client_on_currency)){
                $uniqueIdOfSeamlessService=$this->gamePlatformId.'-'.$externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique();      
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
            }

            # Checks if bet exists
            $isBetExists = $this->sbobet_seamless_game_logs_model->getBetAmountByExternalUniqueId($externalUniqueId);
            if(!$isBetExists){
                $errorCode = self::CODE_ERROR_BET_DOES_NOT_EXISTS;
                return true; #return success
            }

            $isBetStatusCancelledAlready = $this->sbobet_seamless_game_logs_model->checkIfBetIsCancelledAlready($externalUniqueId);
            if($isBetStatusCancelledAlready){
                $errorCode = self::CODE_ERROR_BET_IS_CANCELLED_ALREADY;
                $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($this->getPlayerWalletBalance($playerId))];
                return true; #return success
            }

            $isBetStatusSettledAlready = $this->sbobet_seamless_game_logs_model->checkIfBetIsSettledAlready($externalUniqueId);
            if($isBetStatusSettledAlready){
                $errorCode = self::CODE_ERROR_BET_IS_SETTLED_ALREADY;
                $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($this->getPlayerWalletBalance($playerId))];
                return true; #return success
            }

            $betAmount = $this->sbobet_seamless_game_logs_model->getBetAmountByExternalUniqueId($externalUniqueId);
            if($productType == self::PRODUCT_TYPE_SLOTS){#sum total bet of rounds
                $roundIds = $this->sbobet_seamless_game_transactions_model->getTransactionIdsByTransferCode($transferCode);
                if(!empty($roundIds)){
                    $betAmount = 0;
                    foreach ($roundIds as $key => $round) {
                        $roundExternalUniqueId = $this->generateExternalUniqueId($productType,$gameType,$transferCode,$round);
                        $betAmount += $this->sbobet_seamless_game_logs_model->getBetAmountByExternalUniqueId($roundExternalUniqueId,self::TRANSACTION_TYPE_DEDUCT);
                    }
                }
            }

            $beforeBalance = $this->getPlayerWalletBalance($playerId);
            $resultTime = isset($result['resultTime']) ? $result['resultTime'] : null;
            if($resultTime){
                $resultTime = new DateTime($resultTime);
                $resultTime = $resultTime->format("Y-m-d H:i:s");
            }

            $transactionData = [
                "transaction_type" => self::TRANSACTION_TYPE_SETTLE_ADD,
                "gameusername" => $gameUsername,
                "transfer_code" => $transferCode,
                "transaction_id" => $transactionId,
                "amount" => $resultAmount,
                "before_balance" => $beforeBalance,
                "result_type" => $resultType,
                "result_time" => $resultTime,
                "product_type" => $productType,
                "game_type" => $gameType,
                "unique_transaction_id" => $externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique(),
            ];

            if($this->sbobet_seamless_game_logs_model->isSettleBetAlready($externalUniqueId))
            {
                $transactionData['updated_at'] = $this->utils->getNowForMysql();
                # Check if existing result amount is greater than current request result amount, increase balance
                $beforeResultAmount = $this->sbobet_seamless_game_logs_model->getResultAmountByExternalUniqueId($externalUniqueId) + $betAmount;
                $this->utils->debug_log("SBOBET_SEAMLESS_API SETTLE-EXIST: ",$resultAmount,$beforeResultAmount);

                if($resultAmount > $beforeResultAmount)
                {
                    $increaseBalanceAmt = $resultAmount - $beforeResultAmount;
                    $isIncreaseBalance = $this->addAmount($playerId,$increaseBalanceAmt,$this->gamePlatformId);
                    $afterBalance = $this->getPlayerWalletBalance($playerId);
                    $this->utils->debug_log("SBOBET_SEAMLESS_API TRANSACTION_TYPE_SETTLE_ADD: ",$increaseBalanceAmt,$isIncreaseBalance);

                    if($isIncreaseBalance)
                    {
                        $transactionData['transaction_type'] = self::TRANSACTION_TYPE_SETTLE_ADD;
                        $transactionData['after_balance'] = $afterBalance;
                        $transactionData['amount'] = $increaseBalanceAmt;

                        # Inserts to transaction then update the game logs
                        # and insert for reference as well
                        if($this->doInsertToGameTransactions($transactionData))
                        {
                            # Update bet result
                            $betResultData = [
                                "result_amount" => $resultAmount-$betAmount,
                                "result_type" => $resultType,
                                "result_time" => $resultTime,
                                "updated_at" => $this->utils->getNowForMysql(),
                                "status" => self::BET_STATUS_SETTLED,
                            ];

                            $isBetResultUpdated = $this->sbobet_seamless_game_logs_model->updateBetResultByExternalUniqueId($externalUniqueId,$betResultData);

                            if($isBetResultUpdated)
                            {
                                # Also inserts result in the table, can be use as reference
                                $betResultData["response_result_id"] = $this->response_result_id;
                                $betResultData["external_uniqueid"] = $externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique();
                                $betResultData["currency"] = $this->game_api_sbo_seamless->getGameCurrency();
                                $betResultData["product_type"] = $productType;
                                $betResultData["game_type"] = $gameType;
                                $betResultData["gameusername"] = $gameUsername;
                                $betResultData["bet_amount"] = $betAmount;
                                $betResultData["remarks"] = "Additional Win Amount";
                                $isUpdateBetResultInserted = $this->sbobet_seamless_game_logs_model->insertGameLogs($betResultData);

                                if($isUpdateBetResultInserted){
                                    $errorCode = self::CODE_SUCCESS;
                                    $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($afterBalance),"resultAmount" => $this->game_api_sbo_seamless->dBtoGameAmount($resultAmount)];
                                    return true; #return success
                                }
                            }
                        }
                    }
                }

                # Check if existing result amount is less than current request result amount, decrease balance
                if($resultAmount < $beforeResultAmount)
                {
                    $decreaseBalanceAmt = $beforeResultAmount - $resultAmount;
                    $isDecreaseBalance = $this->subtractAmount($playerId,$decreaseBalanceAmt,$this->gamePlatformId);
                    $afterBalance = $this->getPlayerWalletBalance($playerId);
                    $this->utils->debug_log("SBOBET_SEAMLESS_API TRANSACTION_TYPE_SETTLE_DEDUCT: ",$decreaseBalanceAmt,$isDecreaseBalance);

                    if($isDecreaseBalance)
                    {
                        $transactionData['transaction_type'] = self::TRANSACTION_TYPE_SETTLE_DEDUCT;
                        $transactionData['after_balance'] = $afterBalance;
                        $transactionData['amount'] = $decreaseBalanceAmt;

                        # Inserts to transaction then update the game logs
                        # and insert for reference as well
                        if($this->doInsertToGameTransactions($transactionData))
                        {
                            # Update bet result
                            $betResultData = [
                                "result_amount" => $resultAmount-$betAmount,
                                "result_type" => $resultType,
                                "result_time" => $resultTime,
                                "updated_at" => $this->utils->getNowForMysql(),
                                "status" => self::BET_STATUS_SETTLED
                            ];

                            $isBetResultUpdated = $this->sbobet_seamless_game_logs_model->updateBetResultByExternalUniqueId($externalUniqueId,$betResultData);

                            if($isBetResultUpdated)
                            {
                                # Also inserts result for reference
                                $betResultData["response_result_id"] = $this->response_result_id;
                                $betResultData["external_uniqueid"] = $externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique();
                                $betResultData["currency"] = $this->game_api_sbo_seamless->getGameCurrency();
                                $betResultData["product_type"] = $productType;
                                $betResultData["game_type"] = $gameType;
                                $betResultData["gameusername"] = $gameUsername;
                                $betResultData["bet_amount"] = $betAmount;
                                $betResultData["remarks"] = "Deduct to Win Amount";
                                $isUpdateBetResultInserted = $this->sbobet_seamless_game_logs_model->insertGameLogs($betResultData);

                                if($isUpdateBetResultInserted){
                                    $errorCode = self::CODE_SUCCESS;
                                    $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($afterBalance),"resultAmount" => $this->game_api_sbo_seamless->dBtoGameAmount($resultAmount)];
                                    return true; #return success
                                }
                            }
                        }
                    }
                }

                $errorCode = self::CODE_ERROR_BET_IS_SETTLED_ALREADY;
                $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($this->getPlayerWalletBalance($playerId)),"resultAmount" => $this->game_api_sbo_seamless->dBtoGameAmount($resultAmount)];
                return true; #return success
            }else
            {
                $this->utils->debug_log("SBOBET_SEAMLESS_API SETTLE insertData: ",$transactionData);
                $isIncreaseBalance = false;
                if($resultAmount){
                    $isIncreaseBalance = $this->addAmount($playerId,$resultAmount,$this->gamePlatformId);
                }

                if($isIncreaseBalance || $resultAmount < 1){
                    $transactionData['after_balance'] = $this->getPlayerWalletBalance($playerId);
                    if($this->doInsertToGameTransactions($transactionData))
                    {
                        # Update bet result
                        $betResultData = [
                            "result_amount" => $resultAmount-$betAmount,
                            "result_type" => $resultType,
                            "result_time" => $resultTime,
                            "status" => self::BET_STATUS_SETTLED,
                        ];

                        $isBetResultUpdated = $this->sbobet_seamless_game_logs_model->updateBetResultByExternalUniqueId($externalUniqueId,$betResultData);
                        if($isBetResultUpdated)
                        {
                            # Also inserts result for reference
                            $betResultData["response_result_id"] = $this->response_result_id;
                            $betResultData["external_uniqueid"] = $externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique();
                            $betResultData["currency"] = $this->game_api_sbo_seamless->getGameCurrency();
                            $betResultData["product_type"] = $productType;
                            $betResultData["game_type"] = $gameType;
                            $betResultData["gameusername"] = $gameUsername;
                            $betResultData["bet_amount"] = $betAmount;
                            $isBetResultInserted = $this->sbobet_seamless_game_logs_model->insertGameLogs($betResultData);

                            if($isBetResultInserted){
                                $errorCode = self::CODE_SUCCESS;
                                $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($this->getPlayerWalletBalance($playerId)),"resultAmount" => $this->game_api_sbo_seamless->dBtoGameAmount($resultAmount)];
                                return true; #return success
                            }
                        }
                    }
                }
            }

            return false; #default error
        });

        $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
        return;
    }

    /*
     * If any situation force us to rollback the settlement,
     * after we've rollback the bet, we will send this API request to inform you.
     * Rollback means the settled bet in a game will go back to running state,
     * and will have to be settled again.
     */
    public function rollback()
    {
        $result = $this->processRequest(self::APICALL_ROLLBACK);
        $playerId = $result['playerId'];

        #Default response (Error)
        $errorCode =  $result['errorCode'];
        $extra=null;

        if(isset($result['isError']) && $result['isError']){
            $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
            return;
        }

        $this->lockAndTransForPlayerBalance($playerId, function() use($result, &$errorCode, &$extra) {
            $playerId = isset($result['playerId']) ? $result['playerId'] : null;
            $gameUsername = isset($result['gameUsername']) ? $result['gameUsername'] : null;
            $transferCode = isset($result['transferCode']) ? $result['transferCode'] : null;
            $gameType = isset($result['gameType']) ? $result['gameType'] : null;
            $productType = isset($result['productType']) ? $result['productType'] : null;
            $beforeBalance = $this->getPlayerWalletBalance($playerId);

            $transactionId = $this->sbobet_seamless_game_transactions_model->getTransactionIdByTransferCode($transferCode,self::TRANSACTION_TYPE_DEDUCT);
            $externalUniqueId = $this->generateExternalUniqueId($productType,$gameType,$transferCode,$transactionId);
            $gameId = isset($result['gameId'])  ? $result['gameId'] : null;
            $this->externalGameId = isset($result['extraInfo']['sportType'])  ? $result['extraInfo']['sportType'] : $gameId;
            $this->commonBetId = $externalUniqueId;
            $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
            if(!empty($enabled_remote_wallet_client_on_currency)){
                $uniqueIdOfSeamlessService=$this->gamePlatformId.'-'.$externalUniqueId."-RB-".$this->game_api_sbo_seamless->generateUnique();      
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
            }

            $betAmount = $this->sbobet_seamless_game_logs_model->getBetAmountByExternalUniqueId($externalUniqueId);
            if(!$betAmount){
                $errorCode = self::CODE_ERROR_BET_DOES_NOT_EXISTS;
                return true; #return success
            }

            # Check if bet is settled already by getting result amount
            $resultAmount = $this->sbobet_seamless_game_logs_model->getResultAmountByExternalUniqueId($externalUniqueId) ?: 0;
            # Check rollback time if already rolled back
            $isRolledBack = $this->sbobet_seamless_game_logs_model->isRolledBackAlready($externalUniqueId);
            # Do rollback if bet is settled already and rollback hasn't made yet
            if(!$isRolledBack)
            {
                if($productType == self::PRODUCT_TYPE_SLOTS && in_array($gameType,self::GAME_TYPE_WANMEI)){
                    $adjustmentAmt = $this->sbobet_seamless_game_transactions_model->getTotalBetByTransferCode($transferCode);
                    $isBalanceAdjusted = $this->subtractAmount($playerId,$adjustmentAmt,$this->gamePlatformId);
                }else{
                    $adjustmentAmt = $betAmount + $resultAmount;
                    $isBalanceAdjusted = false;
                    if($adjustmentAmt > 0){
                        $isBalanceAdjusted = $this->subtractAmount($playerId,$adjustmentAmt,$this->gamePlatformId);
                    }
                }

                $this->utils->debug_log("SBOBET_SEAMLESS_API TRANSACTION_TYPE_ROLLBACK: ",$adjustmentAmt,$isBalanceAdjusted);

                if($isBalanceAdjusted || ( !$isBalanceAdjusted && round($adjustmentAmt, 4)==0 ) )
                {
                    $afterBalance = $this->getPlayerWalletBalance($playerId);
                    $transactionData = [
                        "transaction_type" => self::TRANSACTION_TYPE_ROLLBACK_DEDUCT,
                        "gameusername" => $gameUsername,
                        "transfer_code" => $transferCode,
                        "transaction_id" => $transactionId,
                        "amount" => $adjustmentAmt,
                        "before_balance" => $beforeBalance,
                        "after_balance" => $afterBalance,
                        "product_type" => $productType,
                        "game_type" => $gameType,
                        "unique_transaction_id" => $externalUniqueId."-RB-".$this->game_api_sbo_seamless->generateUnique(),
                    ];

                    # Inserts to transaction then update the game logs
                    # and insert for reference as well
                    if($this->doInsertToGameTransactions($transactionData))
                    {
                        $now = $this->utils->getNowForMysql();
                        # Update bet result
                        $betResultData = [
                            "result_amount" => null,
                            "result_type" => null,
                            "result_time" => null,
                            "cancel_time" => null,
                            "rollback_time" => $now,
                            "updated_at" => $now,
                            "status" => self::BET_STATUS_RUNNING,
                            "remarks" => "Rolled Back Record",
                        ];

                        $isBetResultUpdated = $this->sbobet_seamless_game_logs_model->updateBetResultByExternalUniqueId($externalUniqueId,$betResultData);

                        if($isBetResultUpdated)
                        {
                            # Also inserts result for reference
                            $betResultData["response_result_id"] = $this->response_result_id;
                            $betResultData["external_uniqueid"] = $externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique();
                            $betResultData["currency"] = $this->game_api_sbo_seamless->getGameCurrency();
                            $betResultData["product_type"] = $productType;
                            $betResultData["game_type"] = $gameType;
                            $betResultData["gameusername"] = $gameUsername;
                            $betResultData["bet_amount"] = $betAmount;
                            $isUpdateBetResultInserted = $this->sbobet_seamless_game_logs_model->insertGameLogs($betResultData);

                            if($isUpdateBetResultInserted)
                            {
                                $errorCode = self::CODE_SUCCESS;
                                $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($this->getPlayerWalletBalance($playerId))];
                                return true; #return success
                            }
                        }
                    }
                }
            }else{
                $errorCode = self::CODE_ROLLBACK_EXISTS;
                return true; #return success
            }
            return false; #default error
        });

        $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
        return;
    }

    /*
     * If any situation force us to cancel the bet,
     * after we've canceled the bet, we will send this API request to inform you.
     * Cancel means the running or settled bet in a game will be void,
     * and will not be accepted anymore.
     */
    public function cancel()
    {
        $result = $this->processRequest(self::APICALL_CANCEL);
        $playerId = $result['playerId'];

        #Default response (Error)
        $errorCode =  $result['errorCode'];
        $extra=null;

        if(isset($result['isError']) && $result['isError']){
            $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
            return;
        }

        $this->lockAndTransForPlayerBalance($playerId, function() use($result, &$errorCode, &$extra) {
            $playerId = isset($result['playerId']) ? $result['playerId'] : null;
            $gameUsername = isset($result['gameUsername']) ? $result['gameUsername'] : null;
            $transferCode = isset($result['transferCode']) ? $result['transferCode'] : null;
            $gameType = isset($result['gameType']) ? $result['gameType'] : null;
            $productType = isset($result['productType']) ? $result['productType'] : null;
            $beforeBalance = $this->getPlayerWalletBalance($playerId);
            $transactionId = isset($result['transactionId']) ? $result['transactionId'] : null;
            $isCancelAll = isset($result['isCancelAll']) ? $result['isCancelAll'] : null;
            if(!$transactionId){
                $transactionId = $this->sbobet_seamless_game_transactions_model->getTransactionIdByTransferCode($transferCode,self::TRANSACTION_TYPE_DEDUCT);
            }
            $externalUniqueId = $this->generateExternalUniqueId($productType,$gameType,$transferCode,$transactionId);
            $gameId = isset($result['gameId'])  ? $result['gameId'] : null;
            $this->externalGameId = isset($result['extraInfo']['sportType'])  ? $result['extraInfo']['sportType'] : $gameId;
            $this->commonBetId = $externalUniqueId;
            $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
            if(!empty($enabled_remote_wallet_client_on_currency)){
                $uniqueIdOfSeamlessService=$this->gamePlatformId.'-'.$externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique();      
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
            }
            if($productType == self::PRODUCT_TYPE_SLOTS){
                #get last round status for product slots
                if($isCancelAll){
                    // $lastRound = $this->sbobet_seamless_game_transactions_model->getLastTransactionIdOfTransferCode($transferCode);
                    // $externalUniqueId = $this->generateExternalUniqueId($productType,$gameType,$transferCode,$lastRound);
                    $roundIds = $this->sbobet_seamless_game_transactions_model->getTransactionIdsByTransferCode($transferCode);
                    if(!empty($roundIds)){
                        $status = self::BET_STATUS_RUNNING;
                        foreach ($roundIds as $key => $round) {
                            $roundExternalUniqueId = $this->generateExternalUniqueId($productType,$gameType,$transferCode,$round);
                            if($status == self::BET_STATUS_RUNNING){
                                $isSettled = $this->sbobet_seamless_game_logs_model->checkIfBetIsSettledAlready($roundExternalUniqueId);
                                if($isSettled){
                                    $status = self::BET_STATUS_SETTLED;
                                    $externalUniqueId = $roundExternalUniqueId; #get id if have existing settled round and over ride current external id to get proper result amount
                                }
                            }
                        }
                    }
                }
            }
            # Checks if bet exists
            $betAmount = $this->sbobet_seamless_game_logs_model->getBetAmountByExternalUniqueId($externalUniqueId);
            $resultAmount = $this->sbobet_seamless_game_logs_model->getResultAmountByExternalUniqueId($externalUniqueId) ?: 0;
            $returnStakeAmount = $this->sbobet_seamless_game_logs_model->getReturnStakeAmountByExternalUniqueId($externalUniqueId."-return-stake") ?: 0;
            if($returnStakeAmount > 0){
                $resultAmount = $betAmount + $betAmount + $returnStakeAmount;
            }

            if(!$betAmount){
                $errorCode = self::CODE_ERROR_BET_DOES_NOT_EXISTS;
                return true; #return success
            } else {
                # Check cancel time if already cancelled
                $isCancelled = $this->sbobet_seamless_game_logs_model->isBetCancelledAlready($externalUniqueId);

                # Update bet details in records set status to cancelled
                if(!$isCancelled)
                {
                    $isBetRunningStatus = $this->sbobet_seamless_game_logs_model->checkIfBetIsRunningAlready($externalUniqueId);
                    if($isBetRunningStatus)
                    {
                        $increaseBalanceAmt = $betAmount;
                        // $increasedBalance = $this->wallet_model->incSubWallet($playerId, $this->gamePlatformId,$increaseBalanceAmt);
                        $increasedBalance = $this->addAmount($playerId,$increaseBalanceAmt,$this->gamePlatformId);

                        $this->utils->debug_log("SBOBET_SEAMLESS_API TRANSACTION_TYPE_CANCEL_ADD: ",$increaseBalanceAmt,$increasedBalance);

                        if($increasedBalance)
                        {
                            $afterBalance = $this->getPlayerWalletBalance($playerId);
                            $transactionData = [
                                    "transaction_type" => self::TRANSACTION_TYPE_CANCEL_ADD,
                                    "gameusername" => $gameUsername,
                                    "transfer_code" => $transferCode,
                                    "transaction_id" => $transactionId,
                                    "amount" => $betAmount,
                                    "before_balance" => $beforeBalance,
                                    "after_balance" => $afterBalance,
                                    "product_type" => $productType,
                                    "game_type" => $gameType,
                                    "unique_transaction_id" => $externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique(),
                            ];

                            # Inserts to transaction then update the game logs
                            # and insert for reference as well
                            if($this->doInsertToGameTransactions($transactionData))
                            {
                                $now = $this->utils->getNowForMysql();
                                # Update bet result
                                $betCancelData = [
                                                    "result_amount" => null,
                                                    "result_type" => null,
                                                    "result_time" => null,
                                                    "cancel_time" => $now,
                                                    "updated_at" => $now,
                                                    "status" => self::BET_STATUS_CANCELLED,
                                                    "remarks" => "Cancelled Record"
                                                 ];

                                $isBetCancelled = $this->sbobet_seamless_game_logs_model->updateBetResultByExternalUniqueId($externalUniqueId,$betCancelData);

                                if($isBetCancelled)
                                {
                                    # Also inserts result for reference
                                    $betResultData = [
                                                        "response_result_id" => $this->response_result_id,
                                                        "external_uniqueid" => $externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique(),
                                                        "currency" => $this->game_api_sbo_seamless->getGameCurrency(),
                                                        "product_type" => $productType,
                                                        "game_type" => $gameType,
                                                        "gameusername" => $gameUsername,
                                                        "bet_amount" => $betAmount,
                                                        "cancel_time" => $now,
                                                        "status" => self::BET_STATUS_CANCELLED,
                                                        "remarks" => "Cancelled Record"
                                                    ];

                                    $isUpdateBetResultInserted = $this->sbobet_seamless_game_logs_model->insertGameLogs($betResultData);

                                    if($isUpdateBetResultInserted)
                                    {
                                        $errorCode = self::CODE_SUCCESS;
                                        $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($this->getPlayerWalletBalance($playerId))];
                                        return true; #return success
                                    }
                                }
                            }
                        }
                    }else{
                        #default
                        $trans = false;
                        #settled win
                        if($this->utils->compareResultFloat($resultAmount, '>', 0)){
                            $decreaseBalanceAmt = $resultAmount;
                            $trans = $this->subtractAmount($playerId,$decreaseBalanceAmt,$this->gamePlatformId);
                            $trans_settled_type = self::TRANSACTION_TYPE_CANCEL_DEDUCT;
                            $this->utils->debug_log("SBOBET_SEAMLESS_API TRANSACTION_TYPE_CANCEL_DEDUCT: ",$decreaseBalanceAmt,$trans);
                        } 

                        #settled lose
                        if($this->utils->compareResultFloat($resultAmount, '<', 0)){
                            $resultAmount = abs($resultAmount);
                            $increaseBalanceAmt = $resultAmount;
                            $trans = $this->addAmount($playerId,$increaseBalanceAmt,$this->gamePlatformId);
                            $trans_settled_type = self::TRANSACTION_TYPE_CANCEL_ADD;
                            $this->utils->debug_log("SBOBET_SEAMLESS_API TRANSACTION_TYPE_CANCEL_ADD: ",$increaseBalanceAmt,$trans);
                        }

                        #settled tie or win = bet
                        if($this->utils->compareResultFloat($resultAmount, '=', 0)){
                            $trans = true;
                            $trans_settled_type = self::TRANSACTION_TYPE_CANCEL_ADD;
                        }

                        // $decreaseBalanceAmt = $resultAmount;
                        // $decreasedBalance = $this->wallet_model->decSubWallet($playerId, $this->gamePlatformId,$decreaseBalanceAmt);

                        // $decreasedBalance = $this->subtractAmount($playerId,$decreaseBalanceAmt,$this->gamePlatformId);

                        // $this->utils->debug_log("SBOBET_SEAMLESS_API TRANSACTION_TYPE_CANCEL_DEDUCT: ",$decreaseBalanceAmt,$decreasedBalance);

                        if($trans)
                        {
                            $afterBalance = $this->getPlayerWalletBalance($playerId);
                            $transactionData = [
                                    "transaction_type" => $trans_settled_type,
                                    "gameusername" => $gameUsername,
                                    "transfer_code" => $transferCode,
                                    "transaction_id" => $transactionId,
                                    "amount" => $resultAmount,
                                    "before_balance" => $beforeBalance,
                                    "after_balance" => $afterBalance,
                                    "product_type" => $productType,
                                    "game_type" => $gameType,
                                    "unique_transaction_id" => $externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique(),
                            ];

                            # Inserts to transaction then update the game logs
                            # and insert for reference as well
                            if($this->doInsertToGameTransactions($transactionData))
                            {
                                $now = $this->utils->getNowForMysql();
                                # Update bet result
                                $betCancelData = [
                                                    "result_amount" => null,
                                                    "result_type" => null,
                                                    "result_time" => null,
                                                    "cancel_time" => $now,
                                                    "updated_at" => $now,
                                                    "status" => self::BET_STATUS_CANCELLED,
                                                    "remarks" => "Cancelled Record"
                                                 ];

                                $isBetCancelled = $this->sbobet_seamless_game_logs_model->updateBetResultByExternalUniqueId($externalUniqueId,$betCancelData);

                                if($isBetCancelled)
                                {
                                    # Also inserts result for reference
                                    $betResultData = [
                                                        "response_result_id" => $this->response_result_id,
                                                        "external_uniqueid" => $externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique(),
                                                        "currency" => $this->game_api_sbo_seamless->getGameCurrency(),
                                                        "product_type" => $productType,
                                                        "game_type" => $gameType,
                                                        "gameusername" => $gameUsername,
                                                        "bet_amount" => $betAmount,
                                                        "cancel_time" => $now,
                                                        "status" => self::BET_STATUS_CANCELLED,
                                                        "remarks" => "Cancelled Record"
                                                    ];

                                    $isUpdateBetResultInserted = $this->sbobet_seamless_game_logs_model->insertGameLogs($betResultData);

                                    if($isUpdateBetResultInserted)
                                    {
                                        $errorCode = self::CODE_SUCCESS;
                                        $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($this->getPlayerWalletBalance($playerId))];
                                        return true; #return success
                                    }
                                }
                            }
                        }
                    }
                }else{
                    $errorCode = self::CODE_ERROR_BET_IS_CANCELLED_ALREADY;
                    return true; #return success
                }
            }
            return false; #default error
        });

        $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
        return;
    }

    /*
     *  When player giving some tips in a game,
     *  we will use this api to send request to deduct
     *  player's balance in their wallet directly.
     */
    public function tip()
    {
        $result = $this->processRequest(self::APICALL_TIP);
        $playerId = $result['playerId'];

        #Default response (Error)
        $errorCode =  $result['errorCode'];
        $extra=null;

        if(isset($result['isError']) && $result['isError']){
            $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
            return;
        }

        $this->lockAndTransForPlayerBalance($playerId, function() use($result, &$errorCode, &$extra) {
            $playerId = isset($result['playerId']) ? $result['playerId'] : null;
            $gameUsername = isset($result['gameUsername']) ? $result['gameUsername'] : null;
            $transferCode = isset($result['transferCode']) ? $result['transferCode'] : null;
            $gameType = isset($result['gameType']) ? $result['gameType'] : null;
            $productType = isset($result['productType']) ? $result['productType'] : null;
            $amount = isset($result['amount']) ? $this->game_api_sbo_seamless->gameAmountToDB($result['amount']) : null;
            $beforeBalance = $this->getPlayerWalletBalance($playerId);

            $externalUniqueId = $transferCode."-".$productType."-".$gameType;
            $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
            if(!empty($enabled_remote_wallet_client_on_currency)){
                $uniqueIdOfSeamlessService=$this->gamePlatformId.'-'.$externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique();      
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
            }

            # Checks if tip exists
            $isTipExist = $this->sbobet_seamless_game_logs_model->isPlayerTipExists($externalUniqueId);

            if($isTipExist){
                $errorCode = self::CODE_ERROR_TIP_ALREADY_EXISTS;
                return true; #return success
            }else
            {
                $hasEnoughBalance = $this->utils->compareResultFloat($beforeBalance,">=",$amount);
                if(!$hasEnoughBalance){
                    $errorCode = self::CODE_ERROR_USER_NOT_ENOUGH_BALANCE;
                    $extra = ["balance"=>$beforeBalance,"tipAmount"=>$amount];
                    return true; #return success
                }

                $decreaseBalanceAmt = $amount;
                // $isDecreaseBalance = $this->wallet_model->decSubWallet($playerId, $this->gamePlatformId,$decreaseBalanceAmt);
                $isDecreaseBalance = $this->subtractAmount($playerId,$decreaseBalanceAmt,$this->gamePlatformId);

                $this->utils->debug_log("SBOBET_SEAMLESS_API TRANSACTION_TYPE_TIP_DEDUCT: ",$decreaseBalanceAmt,$isDecreaseBalance);

                if($isDecreaseBalance)
                {
                    $afterBalance = $this->getPlayerWalletBalance($playerId);
                    $transactionData = [
                            "transaction_type" => self::TRANSACTION_TYPE_TIP_DEDUCT,
                            "gameusername" => $gameUsername,
                            "transfer_code" => $transferCode,
                            "amount" => $decreaseBalanceAmt,
                            "before_balance" => $beforeBalance,
                            "after_balance" => $afterBalance,
                            "product_type" => isset($result['productType']) ? $result['productType'] : null,
                            "game_type" => isset($result['gameType']) ? $result['gameType'] : null,
                            "unique_transaction_id" => $externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique(),
                    ];

                    # Inserts to transaction then update the game logs
                    # and insert for reference as well
                    if($this->doInsertToGameTransactions($transactionData))
                    {
                        # Also inserts result for reference
                        $betResultData = [
                                            "response_result_id" => $this->response_result_id,
                                            "external_uniqueid" => $externalUniqueId,
                                            "currency" => $this->game_api_sbo_seamless->getGameCurrency(),
                                            "product_type" => $productType,
                                            "game_type" => $gameType,
                                            "gameusername" => $gameUsername,
                                            "bet_amount" => $decreaseBalanceAmt,
                                            "tip_time" => $this->utils->getNowForMysql(),
                                            "remarks" => "Player Tip"
                                        ];

                        $isUpdateBetResultInserted = $this->sbobet_seamless_game_logs_model->insertGameLogs($betResultData);

                        if($isUpdateBetResultInserted)
                        {
                            $errorCode = self::CODE_SUCCESS;
                            $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($this->getPlayerWalletBalance($playerId))];
                            return true; #return success
                        }
                    }
                }
            }
            return false;
        });
        $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
        return;
    }

    /*
     *  When player having or getting some bonus/jackpot/promotion in game,
     *  we will use this api to send request to increase
     *  player's balance in their wallet directly.
     */
    public function bonus()
    {
        $result = $this->processRequest(self::APICALL_BONUS);
        $playerId = $result['playerId'];

        #Default response (Error)
        $errorCode =  $result['errorCode'];
        $extra=null;

        if(isset($result['isError']) && $result['isError']){
            $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
            return;
        }

        $this->lockAndTransForPlayerBalance($playerId, function() use($result, &$errorCode, &$extra) {
            $playerId = isset($result['playerId']) ? $result['playerId'] : null;
            $gameUsername = isset($result['gameUsername']) ? $result['gameUsername'] : null;
            $transferCode = isset($result['transferCode']) ? $result['transferCode'] : null;
            $transactionId = isset($result['transactionId']) ? $result['transactionId'] : null;
            $gameType = isset($result['gameType']) ? $result['gameType'] : null;
            $productType = isset($result['productType']) ? $result['productType'] : null;
            $amount = isset($result['amount']) ? $this->game_api_sbo_seamless->gameAmountToDB($result['amount']) : null;
            $beforeBalance = $this->getPlayerWalletBalance($playerId);

            $externalUniqueId = $this->generateExternalUniqueId($productType,$gameType,$transferCode,$transactionId);
            $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
            $gameId = isset($result['gameId'])  ? $result['gameId'] : null;
            $this->externalGameId = isset($result['extraInfo']['sportType'])  ? $result['extraInfo']['sportType'] : $gameId;
            $this->commonBetId = $externalUniqueId;
            if(!empty($enabled_remote_wallet_client_on_currency)){
                $uniqueIdOfSeamlessService=$this->gamePlatformId.'-'.$externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique();      
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
            }

            # Checks if bonus exists
            $isBonusExist = $this->sbobet_seamless_game_logs_model->isPlayerBonusExists($externalUniqueId);

            if($isBonusExist){
                $errorCode = self::CODE_ERROR_DUPLICATE_TRANSFER_CODE;
                return true; #return success
            }else
            {
                $increaseBalanceAmt = $amount;
                // $isIncreaseBalance = $this->wallet_model->incSubWallet($playerId, $this->gamePlatformId,$increaseBalanceAmt);
                $isIncreaseBalance = $this->addAmount($playerId,$increaseBalanceAmt,$this->gamePlatformId);

                $this->utils->debug_log("SBOBET_SEAMLESS_API TRANSACTION_TYPE_BONUS_ADD: ",$increaseBalanceAmt,$isIncreaseBalance);

                if($isIncreaseBalance)
                {
                    $afterBalance = $this->getPlayerWalletBalance($playerId);
                    $transactionData = [
                            "transaction_type" => self::TRANSACTION_TYPE_BONUS_ADD,
                            "gameusername" => $gameUsername,
                            "transfer_code" => $transferCode,
                            "amount" => $increaseBalanceAmt,
                            "before_balance" => $beforeBalance,
                            "after_balance" => $afterBalance,
                            "product_type" => isset($result['productType']) ? $result['productType'] : null,
                            "game_type" => isset($result['gameType']) ? $result['gameType'] : null,
                            "unique_transaction_id" => $externalUniqueId."-".$this->game_api_sbo_seamless->generateUnique(),
                            // "bonus_time" => $this->utils->getNowForMysql(),
                    ];

                    # Inserts to transaction then update the game logs
                    # and insert for reference as well
                    if($this->doInsertToGameTransactions($transactionData))
                    {
                        # Also inserts result for reference
                        $betResultData = [
                                            "response_result_id" => $this->response_result_id,
                                            "external_uniqueid" => $externalUniqueId,
                                            "currency" => $this->game_api_sbo_seamless->getGameCurrency(),
                                            "product_type" => $productType,
                                            "game_type" => $gameType,
                                            "gameusername" => $gameUsername,
                                            "bet_amount" => $increaseBalanceAmt,
                                            // "bonus_time" => $this->utils->getNowForMysql(),
                                            "status" => self::BET_STATUS_SETTLED,
                                            "remarks" => "Player Bonus"
                                        ];

                        $isUpdateBetResultInserted = $this->sbobet_seamless_game_logs_model->insertGameLogs($betResultData);

                        if($isUpdateBetResultInserted)
                        {
                            $errorCode = self::CODE_SUCCESS;
                            $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($this->getPlayerWalletBalance($playerId))];
                            return true; #return success
                        }
                    }
                }
            }
            return false;
        });
        $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
        return;
    }

    /*
        When player place bet on some game that will not accept all stake but reduce some stake, we will call return stake to reduce bet's stake, after that will have normal settle behavior.
        Note: When open Sv388Cockfighting games need to implement ReturnStake API
     */
    public function ReturnStake()
    {
        $result = $this->processRequest(self::APICALL_RETURN_STAKE);
        $playerId = $result['playerId'];

        #Default response (Error)
        $errorCode =  $result['errorCode'];
        $extra=null;

        if(isset($result['isError']) && $result['isError']){
            $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
            return;
        }

        $this->lockAndTransForPlayerBalance($playerId, function() use($result, &$errorCode, &$extra) {

            $playerId = isset($result['playerId'])  ? $result['playerId'] : null;
            $transferCode = isset($result['transferCode'])  ? $result['transferCode'] : null;
            $transactionId = isset($result['transactionId'])  ? $result['transactionId'] : null;
            $gameUsername = isset($result['gameUsername'])  ? $result['gameUsername'] : null;
            $productType = isset($result['productType'])  ? $result['productType'] : null;
            $gameType = isset($result['gameType'])  ? $result['gameType'] : null;
            $currentStake = isset($result['currentStake'])  ? $this->game_api_sbo_seamless->gameAmountToDB($result['currentStake']) : null;
            $beforeBalance = $this->getPlayerWalletBalance($playerId);
            $externalUniqueId = $this->generateExternalUniqueId($productType,$gameType,$transferCode,$transactionId);
            $gameId = isset($result['gameId'])  ? $result['gameId'] : null;
            $this->externalGameId = isset($result['extraInfo']['sportType'])  ? $result['extraInfo']['sportType'] : $gameId;
            $this->commonBetId = $externalUniqueId;

            # Check if player is blocked
            $isPlayerBlockedAll = $this->player_model->isBlocked($playerId);
            if($isPlayerBlockedAll){
                $errorCode = self::CODE_ERROR_PLAYER_IS_BLOCKED;
                return true; #return success
            }


            $isBetStatusSettledAlready = $this->sbobet_seamless_game_logs_model->checkIfBetIsSettledAlready($externalUniqueId);
            if($isBetStatusSettledAlready){
                $errorCode = self::CODE_ERROR_DUPLICATE_TRANSFER_CODE;
                $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($this->getPlayerWalletBalance($playerId))];
                return true; #return success
            }

            $isCancelled = $this->sbobet_seamless_game_logs_model->isBetCancelledAlready($externalUniqueId);
            if($isCancelled){
                $errorCode = self::CODE_ERROR_DUPLICATE_TRANSFER_CODE;
                $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($this->getPlayerWalletBalance($playerId))];
                return true; #return success
            }

            if($this->sbobet_seamless_game_logs_model->isExternalUniqueIdAlreadyExist($externalUniqueId))
            {
                $returnStakeUniqueId = $externalUniqueId."-return-stake";
                $previousBetAmount = $this->sbobet_seamless_game_logs_model->getBetAmountByExternalUniqueId($externalUniqueId);

                $isReturnStakeExist = $this->sbobet_seamless_game_logs_model->isExternalUniqueIdAlreadyExist($returnStakeUniqueId);
                if($isReturnStakeExist){
                    $errorCode = self::CODE_ERROR_BET_ALREADY_RETURNED_STAKE;
                    $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($this->getPlayerWalletBalance($playerId))];
                    return true; #return success
                }

                if($currentStake < $previousBetAmount){
                    $increaseBalanceAmt =  $previousBetAmount - $currentStake;
                    $isIncreaseBalance = $this->addAmount($playerId,$increaseBalanceAmt,$this->gamePlatformId);
                    $this->utils->debug_log("SBOBET_SEAMLESS_API TRANSACTION_TYPE_BONUS_ADD: ",$increaseBalanceAmt,$isIncreaseBalance);

                    if($isIncreaseBalance)
                    {
                        $afterBalance = $this->getPlayerWalletBalance($playerId);
                        $transactionData = [
                                "transaction_type" => self::TRANSACTION_TYPE_RETURN_STAKE,
                                "gameusername" => $gameUsername,
                                "transfer_code" => $transferCode,
                                "amount" => $increaseBalanceAmt,
                                "before_balance" => $beforeBalance,
                                "after_balance" => $afterBalance,
                                "product_type" => isset($result['productType']) ? $result['productType'] : null,
                                "game_type" => isset($result['gameType']) ? $result['gameType'] : null,
                                "unique_transaction_id" => $returnStakeUniqueId,
                        ];

                        # Inserts to transaction then update the game logs
                        # and insert for reference as well
                        if($this->doInsertToGameTransactions($transactionData))
                        {
                            
                            // # Update bet result
                            // $betResultData = [
                            //     "bet_amount" => $currentStake,
                            //     "updated_at" => $this->utils->getNowForMysql(),
                            //     "remarks" => "current stake: {$currentStake} & return stake : {$increaseBalanceAmt}"
                            // ];

                            // $isBetResultUpdated = $this->sbobet_seamless_game_logs_model->updateBetResultByExternalUniqueId($externalUniqueId,$betResultData);

                            # Also inserts result for reference
                            $betResultData = [
                                                "response_result_id" => $this->response_result_id,
                                                "external_uniqueid" => $returnStakeUniqueId,
                                                "currency" => $this->game_api_sbo_seamless->getGameCurrency(),
                                                "product_type" => $productType,
                                                "game_type" => $gameType,
                                                "gameusername" => $gameUsername,
                                                "bet_amount" => $increaseBalanceAmt,
                                                "remarks" => "current stake: {$currentStake} & return stake : {$increaseBalanceAmt}"
                                            ];

                            $isUpdateBetResultInserted = $this->sbobet_seamless_game_logs_model->insertGameLogs($betResultData);

                            if($isUpdateBetResultInserted)
                            {
                                $errorCode = self::CODE_SUCCESS;
                                $extra = ["accountName" => $gameUsername,"balance" => $this->game_api_sbo_seamless->dBtoGameAmount($this->getPlayerWalletBalance($playerId))];
                                return true; #return success
                            }
                        }
                    }
                }
                return false;
            } else {
                $errorCode = self::CODE_ERROR_BET_DOES_NOT_EXISTS;
                return true; #return success
            }

            return false; #default error
        });


        $this->returnJsonResponse($this->getResultResponse($errorCode, $extra));
        return;
    }

    /*
     *  We will use this API request to sync up bet status between us,
     *  normally for checking and debugging purpose.
     *  For example, if a bet in 568win system is settled,
     *  but the wallet status in your system is still running,
     *  then we can find out the bet has problem.
     */
    public function getBetStatus()
    {
        $result = array_filter($this->processRequest(self::APICALL_GETBETSTATUS));
        #Default response (Error)
        $errorCode =  $result['errorCode'];
        if(isset($result['isError']) && $result['isError']){
            $this->returnJsonResponse($this->getResultResponse($errorCode));
            return;
        }
        $playerId = isset($result['playerId']) ? $result['playerId'] : null;
        $gameUsername = isset($result['gameUsername']) ? $result['gameUsername'] : null;
        $transferCode = isset($result['transferCode']) ? $result['transferCode'] : null;
        $transactionId = isset($result['transactionId']) ? $result['transactionId'] : null;
        $gameType = isset($result['gameType']) ? $result['gameType'] : null;
        $productType = isset($result['productType']) ? $result['productType'] : null;

        $externalUniqueId = $this->generateExternalUniqueId($productType,$gameType,$transferCode,$transactionId);

        # Checks if bet exists
        $isBetExists = $this->sbobet_seamless_game_logs_model->getBetAmountByExternalUniqueId($externalUniqueId);

        if(!$isBetExists){
            $this->returnJsonResponse($this->getResultResponse(self::CODE_ERROR_BET_DOES_NOT_EXISTS));
            return;
        }else
        {
            $betDetails = $this->sbobet_seamless_game_logs_model->getBetDetails($externalUniqueId);
            if( isset($betDetails['status']) && ($betDetails['status'] == self::BET_STATUS_CANCELLED) ){
                $isCancelBetTransaction = $this->sbobet_seamless_game_transactions_model->isCancelBetTransaction($transferCode, $transactionId);
                if($isCancelBetTransaction){
                    $betDetails['winloss'] = $betDetails['stake'];
                } else {
                    $betAmount = $this->sbobet_seamless_game_transactions_model->getTotalBetByTransferCode($transferCode);
                    $betDetails['winloss'] = $betAmount;
                    $betDetails['stake'] = $betAmount;
                }
            } else {
                $betAmount = 0;
                $roundIds = $this->sbobet_seamless_game_transactions_model->getTransactionIdsByTransferCode($transferCode);
                if(!empty($roundIds)){
                    foreach ($roundIds as $key => $round) {
                        $ignoreVoid = true;
                        $roundExternalUniqueId = $this->generateExternalUniqueId($productType,$gameType,$transferCode,$round);
                        $betAmount += $this->sbobet_seamless_game_logs_model->getBetAmountByExternalUniqueId($roundExternalUniqueId, null, $ignoreVoid);
                    }
                }

                if( ($betDetails['status'] == self::BET_STATUS_RUNNING) ){
                    $betDetails['winloss'] = 0;

                    #recheck last transaction status
                    $transactionId = $this->sbobet_seamless_game_transactions_model->getTransactionIdByTransferCode($transferCode,self::TRANSACTION_TYPE_DEDUCT);
                    $externalUniqueId = $this->generateExternalUniqueId($productType,$gameType,$transferCode,$transactionId);
                    $lastTransDetails = $this->sbobet_seamless_game_logs_model->getBetDetails($externalUniqueId);
                    
                    if($lastTransDetails['status'] == self::BET_STATUS_SETTLED){
                        $betDetails = $lastTransDetails;
                        $betDetails['winloss'] = $this->game_api_sbo_seamless->dBtoGameAmount($betAmount + $betDetails['result_amount']);
                    }

                    if($lastTransDetails['status'] == self::BET_STATUS_CANCELLED){
                        $betDetails = $lastTransDetails;
                        $betAmount = $this->sbobet_seamless_game_transactions_model->getTotalBetByTransferCode($transferCode);
                        $betDetails['winloss'] = $this->game_api_sbo_seamless->dBtoGameAmount($betAmount);
                    }
                }

                $betDetails['stake'] = $this->game_api_sbo_seamless->dBtoGameAmount($betAmount);
                $betDetails['transferCode'] = $transferCode;
                $betDetails['transactionId'] = $transactionId;
            }

            $this->returnJsonResponse($this->getResultResponse(self::CODE_SUCCESS,$betDetails));
            return;
        }

    }

    /**
    * Convert keys in an array.
    *
    * @param array $array Source data
    * @param callable $callback Function name (strtolower, strtoupper, ucfirst, lcfirst, ucwords)
    * @return array
    */
    function arrayConvertKeyCase(array $array, callable $callback = null)
    {
        if (empty($array)) {
            return [];
        }
        return array_combine(
            array_map($callback, array_keys($array)),
            array_values($array)
        );
    }

    private function processRequest($requestType)
    {

        $request = file_get_contents("php://input");
        $requestArr = json_decode($request,true);
        $requestArr = $this->arrayConvertKeyCase($requestArr,'lcfirst');
        $this->gpid = null;
        $this->gameId = isset($requestArr['gameId']) ? $requestArr['gameId'] : null;
        if(isset($requestArr['gpid'])){
            $this->gpid = $requestArr['gpid'];
            switch ($this->gpid) {
                case self::AFBGAMING_GPID:
                    $this->gamePlatformId = AFB_SBOBET_SEAMLESS_GAME_API;
                    $this->game_api_sbo_seamless = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
                    break;
                
                default:
                    // do nothing
                    break;
            }
        }

        # save response result
        $response_result_id = $this->saveToResponseResult($requestType,$requestArr);
        $this->response_result_id = $response_result_id;

        $isError = false;
        $errorCode = self::CODE_ERROR_INTERNAL_ERROR;
        $gameUsername = $playerId = null;

        if(!$this->game_api_sbo_seamless->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            $this->utils->debug_log(" Request SBO IP =>>>>>>> ",$ip);

            $requestArr["errorCode"] = self::CODE_ERROR_INVALID_IP;;
            $requestArr["isError"] = true;
            $requestArr["playerId"] = null;
            return $requestArr;
        }

        if( (isset($requestArr['userName']) && !empty($requestArr['userName']) ) || ( isset($requestArr['username']) && !empty($requestArr['username']) ) ){
            $username = isset($requestArr['userName']) ? $requestArr['userName'] : $requestArr['username'];
            $playerId = $this->game_provider_auth->getPlayerIdByPlayerName($username, $this->gamePlatformId);
            $gameUsername = $this->game_provider_auth->getPlayerUsernameByGameUsername($username, $this->gamePlatformId);

            if(!$playerId || !$gameUsername){
                $errorCode = self::CODE_ERROR_PLAYER_DOES_NOT_EXISTS;
                $isError = true;
            }
        } else {
            $errorCode = self::CODE_ERROR_USERNAME_EMPTY;
            $isError = true;
        }

        if(isset($requestArr['companyKey'])){
            if(!$this->validateCompanyKey($requestArr['companyKey'])){
                $errorCode = self::CODE_ERROR_WRONG_COMPANY_KEY;
                $isError = true;
            }
        }

        $this->utils->debug_log($requestType." Request Data =>>>>>>> ",$request);
        if(!$this->isJson($request) || empty($requestArr)){
            $errorCode = self::CODE_ERROR_WRONG_JSON_FORMAT;
            $isError = true;
        }

        if($requestType == self::APICALL_DEDUCT && $this->external_system->isGameApiMaintenance($this->gamePlatformId)){
            $errorCode = self::CODE_ERROR_INTERNAL_ERROR;
            $isError = true;  
        }

        # check if method is POST in the request
        if(!$this->isPostMethod()){
            $errorCode = self::CODE_ERROR_METHOD_NOT_ALLOWED;
            $isError = true;
        }

        $requestArr["gameUsername"] = $gameUsername;
        $requestArr["playerId"] = $playerId;
        $requestArr["errorCode"] = $errorCode;
        $requestArr["isError"] = $isError;
        $this->utils->debug_log($requestType." Request Arr =>>>>>>> ",$requestArr);
        return $requestArr;
    }

    private function validateCompanyKey($companyKey)
    {
        if($companyKey == $this->game_api_sbo_seamless->getCompanyKey()){
            return true;
        }
        return false;
    }

    private function getResultResponse($responseCode,$extra=null)
    {
        $result = [
            "errorMessage" => self::API_MSG[$responseCode],
            "errorCode" => $responseCode,
            "balance" => 0 #if error default
        ];

        if(!empty($extra) && is_array($extra)){
            $result = array_merge($result,$extra);
        }

        $success = $responseCode == self::CODE_SUCCESS;
        $response_result_id = $this->response_result_id;

        if(!empty($response_result_id)){
            $response_result = $this->response_result->getResponseResultById($response_result_id);
            $rr_data   = $this->response_result->getRespResultByTableField($response_result->filepath);

            $content = json_decode($rr_data['content'], true);
            $content['resultText'] = json_encode($result);
            $content = json_encode($content);

            if(!$success){
                $this->response_result->setResponseResultToError($response_result_id);
            }

            $this->response_result->updateResponseResultContentByFilepath($response_result->filepath, $content);
        }

        return $result;
    }

    private function isJson($data = null)
    {
        $rlt = true;
        if(null === @json_decode($data)){
            $rlt = false;
        }
        return $rlt;
    }

    private function saveToResponseResult($request_method, $request_params = NULL)
    {
        return $this->CI->response_result->saveResponseResult(
            $this->gamePlatformId,
            null,
            $request_method,
            is_array($request_params) ? json_encode($request_params) : $request_params,
            null,
            200,
            null,
            null
        );
    }

    public function getGamePlatformCode()
    {
        return $this->game_api_sbo_seamless->getPlatformCode();
    }

    private function getPlayerWalletBalance($playerId, $is_locked = true)
    {
        if($this->utils->getConfig('enable_seamless_single_wallet')) {
            $seamless_balance = 0;
            $seamless_reason_id = null;
            if(!$is_locked){
                $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, &$seamless_balance, &$seamless_reason_id) {
                    return  $this->wallet_model->querySeamlessSingleWallet($playerId, $seamless_balance, $seamless_reason_id);
                });
            } else {
                $this->wallet_model->querySeamlessSingleWallet($playerId, $seamless_balance, $seamless_reason_id);
            }
            return $seamless_balance;
        }

        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId,$this->gamePlatformId);
        return $balance;
    }

    private function doInsertToGameLogs($data)
    {
        $gameUsername = isset($data["gameusername"]) ? $data["gameusername"] : null;
        $betAmount = isset($data["bet_amount"]) ? $data["bet_amount"] : null;
        $resultAmount = isset($data["result_amount"]) ? $data["result_amount"] : null;
        $resultType = isset($data["result_type"]) ? $data["result_type"] : null;
        $resultTime = isset($data["result_time"]) ? $data["result_time"] : null;
        $gameType = isset($data["game_type"]) ? $data["game_type"] : null;
        $productType = isset($data["product_type"]) ? $data["product_type"] : null;
        $status = isset($data["status"]) ? $data["status"] : null;
        $responseResultId = (!empty($this->response_result_id)) ? $this->response_result_id : null;
        $externalUniqueId = isset($data["external_uniqueid"]) ? $data["external_uniqueid"] : null;

        $data = [
            "gameusername" => $gameUsername,
            "product_type" => $productType,
            "game_type" => $gameType,
            "currency" => $this->game_api_sbo_seamless->getGameCurrency(),
            "bet_amount" => $betAmount,
            "result_amount" => $resultAmount,
            "result_type" => $resultType,
            "result_time" => $resultTime,
            "response_result_id" => $responseResultId,
            "status" => $status,
            "external_uniqueid" => $externalUniqueId,
            "game_platform_id" => $this->gamePlatformId,
            "gpid" => $this->gpid,
            "game_id" => $this->gameId,
        ];

        $insertRecordsCnt = $this->sbobet_seamless_game_logs_model->insertGameLogs($data);
        $this->CI->utils->debug_log("SBOBET_SEAMLESS_API insert records count is: ",$insertRecordsCnt);
        return $insertRecordsCnt;
    }

    private function doInsertToGameTransactions($data)
    {
        $token = isset($data["token"]) ? $data["token"] : null;
        if($token){
            $playerInfo = $this->game_api_sbo_seamless->getPlayerInfoByToken($token);
            $playerId = isset($playerInfo["playerId"]) ? $playerInfo["playerId"] : null;
        }

        $playerId = isset($data["player_id"]) ? $data["player_id"] : null;
        $transactionType = isset($data["transaction_type"]) ? $data["transaction_type"] : null;
        $productType = isset($data["product_type"]) ? $data["product_type"] : null;
        $gameType = isset($data["game_type"]) ? $data["game_type"] : null;
        $gameUsername = isset($data["gameusername"]) ? $data["gameusername"] : null;
        $beforeBalance = isset($data["before_balance"]) ? $data["before_balance"] : null;
        $afterBalance = isset($data["after_balance"]) ? $data["after_balance"] : null;
        $transactionId = isset($data["transaction_id"]) ? $data["transaction_id"] : null;
        $transferCode = isset($data["transfer_code"]) ? $data["transfer_code"] : null;
        $resultTime = isset($data["result_time"]) ? $data["result_time"] : null;
        $resultType = isset($data["result_type"]) ? $data["result_type"] : null;
        $responseResultId = (!empty($this->response_result_id)) ? $this->response_result_id : null;
        $amount = isset($data["amount"]) ? $data["amount"] : null;
        $uniqueTransactionId = isset($data["unique_transaction_id"]) ? $data["unique_transaction_id"] : null;

        $data = [
            "transaction_type" => $transactionType,
            "gameusername" => $gameUsername,
            "product_type" => $productType,
            "game_type" => $gameType,
            "currency" => $this->game_api_sbo_seamless->getGameCurrency(),
            "transfer_code" => $transferCode,
            "transaction_id" => $transactionId,
            "amount" => $amount,
            "before_balance" => $beforeBalance,
            "after_balance" => $afterBalance,
            "result_time" => $resultTime,
            "result_type" => $resultType,
            "response_result_id" => $responseResultId,
            "unique_transaction_id" => $uniqueTransactionId,
            "sbe_round_id" => $this->commonBetId,
            "sbe_external_game_id" => $this->externalGameId,
            "game_platform_id" => $this->gamePlatformId,
            "gpid" => $this->gpid,
            "game_id" => $this->gameId,
        ];

        $insertRecordsCnt = $this->sbobet_seamless_game_transactions_model->insertTransaction($data);
        $this->CI->utils->debug_log("SBOBET_SEAMLESS_API insert records count is: ",$insertRecordsCnt);

        
        $this->transaction_for_fast_track = null;
        if($insertRecordsCnt) {
            $this->transaction_for_fast_track = $data;
            $this->transaction_for_fast_track['id'] = $this->sbobet_seamless_game_transactions_model->getLastInsertedId();
        }
        return $insertRecordsCnt;
    }

    private function sendToFastTrack() {
        $this->utils->debug_log("SBOBET: (sendToFastTrack) transaction_for_fast_track", $this->transaction_for_fast_track);
        $this->CI->load->model(['game_description_model']);
        $betType = null;
        switch($this->transaction_for_fast_track['transaction_type']) {
            case 'DeductDeduct':
                $betType = 'Bet';
                break;
            case 'SettleAdd':
            case 'BonusAdd':
                $betType = 'Win';
                break;
            case 'CancelDeduct':
            case 'RollbackDeduct':
            case 'CancelAdd':
            case 'AdditionalDeduct':
                $betType = 'Refund';
                break;
            default:
                $betType = null;
                break;
        }

        if ($betType == null) {
            return;
        }
        $data = [
            'activity_id' => strval($this->transaction_for_fast_track['id']),
            'activity_id_reference' => strval($this->transaction_for_fast_track['id']),
            'amount' => (float) abs($this->transaction_for_fast_track['amount']),
            'bet_type' => '',
            'bets' => [
                [
                    'event_name' => '',
                    'is_free_bet' => false,
                    'is_risk_free_bet' => false,
                    'market' => '',
                    'match_start' => '',
                    'outcome' => '',
                    'sports_name' => '',
                    'tournament_name' => '',
                    'odds' => 0,
                    'is_live' => $betType == 'Bet' ? true : false,
                ]
            ],
            'bonus_wager_amount' => 0,
            "balance_after" =>  $this->transaction_for_fast_track['after_balance'],
            "balance_before" =>  $this->transaction_for_fast_track['before_balance'],
            'currency' => 'THB',
            'exchange_rate' => 1,
            'is_cashout' => $betType == 'Win' ? true : false,
            'locked_wager_amount' => 0,
            "origin" =>  $_SERVER['HTTP_HOST'],
            'status' => 'Approved',
            "timestamp" =>  str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            "type" =>  $betType,
            "user_id" => $this->game_api_sbo_seamless->getPlayerIdInPlayer($this->transaction_for_fast_track['gameusername']),
            'wager_amount' => $betType == 'Bet' ? (float) abs($this->transaction_for_fast_track['amount']) : 0,
        ];

        $this->load->library('fast_track');
        $this->utils->debug_log("SBOBET: (sendToFastTrack) data", $data);
        $this->fast_track->addToQueue('sendSportsGameLogs', $data);
    }

    protected function returnJsonResponse($result, $addOrigin = true, $origin = "*", $pretty = false, $partial_output_on_error = false) {
        if(isset($result['errorCode']) && $result['errorCode'] == self::CODE_SUCCESS) {
            if($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration')) {
                $this->sendToFastTrack();
            }
        }
        return parent::returnJsonResult($result, $addOrigin, $origin, $pretty, $partial_output_on_error);
    }
}
