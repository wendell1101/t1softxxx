<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/../base_model.php';

/**
 * Abstract Customized Definition Class
 *
 */
abstract class Abstract_customized_definition extends BaseModel{

    public $playerId = null; // The currect playerId
    public $playerDetail = null; // The currect playerDetail array.
    public $walletAccountDeatil = null;// The currect withdrawal data, walletaccount table.
    public $definitionDetail = null; // The currect dispatch_withdrawal_definition data.
    public $contdtionList = null; // The currect data,"dispatch_withdrawal_contdtions" under a dispatch_withdrawal_definition.


    public $builtInPreCheckerResults = []; // The container for store the result after process dispatch_withdrawal_contdtions.

    private $lastWithdrawlDatetime = 'now'; // The request time of the currect withdrawal, ex: "2020-07-12 13:23:34".
    private $currectWithdrawlDatetime = null;
    function __construct() {
      // $this->CI = &get_instance();
      // $definition_id = $params['definition_id'];
      parent::__construct();
    }

    /**
     * Just return self class name for the key string of stored result.
     *
     * @return string The self class name.
     */
    abstract public function getClassName();

    /**
     * Initialize extra informations,
     *
     * Abstract_customized_definition support the following variables,
     * - playerDetail array The Player information.
     * - walletAccountDeatil array The current withdrawal request information.
     * - definitionDetail array The definition of the pre-checker. (dispatch_withdrawal_definition)
     * - contdtionList array The contdtions of the definition in the pre-checker. (dispatch_withdrawal_conditions)
     * - builtInPreCheckerResults array The contdtions result.
     * Access variables directly for reference. ex, "$this->playerDetail['createdOn']".
     *
     * @return void
     */
    abstract public function init();

    /**
     * Process pre-check and return next stage.
     *
     * For example, pls reference to Customized_definition_default::runPreChecker().
     *
     * @return array The next stage for withdrawal request. The format,
     * - $return['dwStatus'] string If empty string means keep the current dwStatus.
     *
     * @todo For append the 3rd withdraw api params: external_system_id, transaction_fee, ignoreWithdrawalAmountLimit and ignoreWithdrawalTimesLimit.
     * Pls reference to the uri, "http://admin.og.local/payment_management/setWithdrawToPayProc/88/9997/null".
     *
     */
    abstract public  function runPreChecker($resultBuiltIn = null);

    /**
     * To execute conditions for built-in of pre-checker.
     *
     * @return null|boolean
     */
    public  function runBuiltInPreChecker($doBreakWhileMet = true){
        $this->load->model(['dispatch_withdrawal_definition', 'dispatch_withdrawal_conditions', 'dispatch_withdrawal_results']);

        $finallyResult = null; // default
        $from_datetime = null;
        /// @todo OGP-18088, If execute pre-checker via supervisor, the time should be request time,"walletaccount_timelog".
        // P.S. pendingVIP and customized_pendingVIP No request time.
        $to_datetime = 'now';
        $playerId = $this->playerId;
        list($from_datetime, $to_datetime) = $this->dispatch_withdrawal_definition->getThePlayerLastWithdrawlDatetimeToNow($playerId, $from_datetime, $to_datetime);
        // list($from_datetime, $to_datetime) = $this->dispatch_withdrawal_definition->getThePlayerLastWithdrawlDatetimeToNow($this->playerId, $from_datetime);
        $this->lastWithdrawlDatetime = $from_datetime; // lastWithdrawlDatetime for the each condition
        $this->currectWithdrawlDatetime = $to_datetime;
$this->utils->debug_log('OGP-18088,81.from_datetime',$from_datetime, 'playerId', $playerId, 'lastWithdrawlDatetime', $this->lastWithdrawlDatetime);
        if( ! empty($this->contdtionList ) ){
            foreach ($this->contdtionList as $indexNumber => $contdtionDetail){
                $result = $this->runBuiltInPreCheckerByContdtionDetail($contdtionDetail);

                // results log
                $contdtionKeyStr = sprintf('contdtionId_%d', $contdtionDetail['id']);
                $resultDetail = $this->builtInPreCheckerResults[$contdtionKeyStr]['resultsDetail'];
                $data = [];
                $data['wallet_account_id'] = $this->walletAccountDeatil['walletAccountId'];
                $data['definition_id'] = $this->definitionDetail['id'];
                $data['definition_results'] = json_encode($resultDetail);
                $data['dispatch_order'] = $this->definitionDetail['dispatch_order'];
                $resultsId = $this->dispatch_withdrawal_results->add($data);
                $this->builtInPreCheckerResults[$contdtionKeyStr]['resultsId'] = $resultsId;

                /// summary the conditions
                // condition || condition || condition....
                if(is_null($finallyResult) ){
                    $finallyResult = $result;
                }else{
                    $finallyResult = $finallyResult || $result;
                }
                if( $finallyResult === true
                    && $doBreakWhileMet
                ){
                    // break at first met(true).
                    break;
                }
            }
        }

        return $finallyResult;
    }// EOF runBuiltInPreChecker

    /**
     * Execute the built-in contdtions for Pre-Checker.
     *
     * @param array $contdtionDetail The dispatch_withdrawal_conditions rows.
     * @return void
     */
    private function runBuiltInPreCheckerByContdtionDetail($contdtionDetail){
        $this->load->model(['dispatch_withdrawal_conditions', 'dispatch_withdrawal_conditions_included_game_description', 'transactions', 'external_system', 'game_description_model', 'dispatch_withdrawal_results', 'player_model', 'iovation_evidence']);
        if( ! empty( $contdtionDetail ) ){

            // The param for the each condition
            $calcPromoDepositOnly_isEnable = $contdtionDetail['calcPromoDepositOnly_isEnable']; // 非首存与续存优惠
            $calcPromoDepositOnly_isEnable = false; // ignore since OGP-19257

            // gen $gameDescIdList for the each condition
            $gameDescIdList = null; // if null mean ignore; if [] means no-selected game.
            $includedGameType = ! empty($contdtionDetail['includedGameType_isEnable']); // 投注在（真人、彩票、棋牌、电子）
            if( $includedGameType ){// 投注在（真人、彩票、棋牌、电子）
                $conditions_id = $contdtionDetail['id'];
                $includedGameDescriptionList = $this->dispatch_withdrawal_conditions_included_game_description->getDetailListByConditionsId($conditions_id);
                $gameDescIdList = [];// default, empty array.
                if( !empty($includedGameDescriptionList) ){
                    foreach ( $includedGameDescriptionList as $indexNumber => $detail){
                        $gameDescIdList[] = $detail['game_description_id'];
                    }
                }
            }

            // $calcAvailableBetOnly = ! empty($contdtionDetail['calcAvailableBetOnly_isEnable']); // 統計无对冲记录
            $calcAvailableBetOnly = false; // keep false.
            /// As the requirment, If the game log has hedging result,( real_bet == 0 ) then the result is false.
            // 无对冲记录，若有对冲记录則條件不符合。
            $noHedgingRecord = ! empty($contdtionDetail['calcAvailableBetOnly_isEnable']);
/// calcEnabledGameOnly, Adjust Algorithm :
// Check the beting data of the game list(ref. to "allow game type") is exists.
// If be exist mean met.
//
//             // process $gameDescIdList by calcEnabledGameOnly_isEnable
//             $calcEnabledGameOnly = ! empty($contdtionDetail['calcEnabledGameOnly_isEnable']);// 投注非禁止游戏
//             if($calcEnabledGameOnly){ // 僅計算非禁止游戏
//                 if( is_null($gameDescIdList) ){ // 沒有啟用 允許的遊戲類型 （includedGameType）
//                     /// 取得所有有效的遊戲
//                     // game_description.flag_show_in_site
//                     $where = 'external_system.status = '. External_system::STATUS_NORMAL;
//                     $gameInfoList = $this->game_description_model->getGame($where);
//                     $_gameDescIdList = [];
//                     if( ! empty($gameInfoList) ){
//                         foreach ($gameInfoList as $indexNumber => $gameDetail) {
//                             $_gameDescIdList[] = $gameDetail->gameDescriptionId;
//                         }
//                     }
//                     $gameDescIdList = $_gameDescIdList; // replace
//                 }else if( ! empty($gameDescIdList) ){ // 有 允許的遊戲類型 （includedGameType）
//                     /// $gameDescIdList 過濾沒有效的遊戲
//                     $STATUS_NORMAL = External_system::STATUS_NORMAL;
//                     $gameDescIdListImploded = implode(',', $gameDescIdList);
//                     $where = <<<EOF
// game_description.id in ($gameDescIdListImploded)
// AND external_system.status = $STATUS_NORMAL
// EOF;
//                     $gameInfoList = $this->game_description_model->getGame($where);
//                     $_gameDescIdList = []; // for after filter status != STATUS_NORMAL.
//                     if( ! empty($gameInfoList) ){
//                         foreach ($gameInfoList as $indexNumber => $gameDetail) {
//                             $_gameDescIdList[] = $gameDetail->gameDescriptionId;
//                         }
//                     }
//                     $gameDescIdList = $_gameDescIdList; // replace
//                 }
//             } // EOF if($calcEnabledGameOnly)


            $this->utils->debug_log('OGP-18088,142.gameDescIdList', $gameDescIdList);

$this->utils->debug_log('OGP-18088,142.contdtionDetail', $contdtionDetail);
$this->utils->debug_log('OGP-18088,142.lastWithdrawlDatetime',$this->lastWithdrawlDatetime);
$this->utils->debug_log('OGP-18088,142.playerDetail', $this->playerDetail);

            $resultsDetail = [];
            $resultsDetail['conditions_id'] = $contdtionDetail['id'];
            $resultsDetail['conditions_name'] = $contdtionDetail['name'];
            foreach ($contdtionDetail as $fieldName => $fieldVal){
                $builtInPreCheckerResults = [];
                $builtInPreCheckerResults['conditions_id'] = $contdtionDetail['id'];
                switch ($fieldName){
                    case 'noDuplicateAccounts_isEnable':
                        $fieldKey = 'noDuplicateAccounts';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $isEnable =  false;
                        if( $contdtionDetail[$fieldKey.'_isEnable'] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        $count = 0;
                        if($isEnable){ // game_logs.bet_amount = 0 means hedging record
                            $username = $this->playerDetail['username'];
                            $resultDetail = []; // reset
                            $isDuplicate = $this->dispatch_withdrawal_definition->isDuplicateAccount($username, $resultDetail);
                            $count = $resultDetail['count'];
                            $builtInPreCheckerResults['resultDetail'] = $resultDetail;
                            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
                            // for The contdtion result.
                            $result = ! $isDuplicate; // Should be a Data in player,If met the condition.
                            // $this->builtInPreCheckerResults[$fieldKey]['result'] = $result;
                            $resultsDetail[$fieldKey]['result'] = $result;
                            $resultsDetail[$fieldKey]['count'] = $count;
                            $this->builtInPreCheckerResults[$fieldKey] = $resultsDetail[$fieldKey];
                        }else{
                            $this->builtInPreCheckerResults[$fieldKey] = null;
                            $resultsDetail[$fieldKey] = null;
                        }
                    break;

                    case 'noDuplicateFirstNames_isEnable':
                        $fieldKey = 'noDuplicateFirstNames';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $isEnable =  false;
                        if( $contdtionDetail['noDuplicateFirstNames_isEnable'] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        $count = 0;
                        if($isEnable){ // game_logs.bet_amount = 0 means hedging record
                            $firstName = $this->playerDetail['firstName'];
                            $resultDetail = []; // reset
                            $isDuplicate = $this->dispatch_withdrawal_definition->isDuplicateFirstName($firstName, $resultDetail);
                            $count = $resultDetail['count'];
                            $builtInPreCheckerResults['resultDetail'] = $resultDetail;
                            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
                            // for The contdtion result.
                            $result = ! $isDuplicate; // Should be a Data in player,If met the condition.
                            // $this->builtInPreCheckerResults[$fieldKey]['result'] = $result;
                            $resultsDetail[$fieldKey]['result'] = $result;
                            $resultsDetail[$fieldKey]['count'] = $count;
                            $this->builtInPreCheckerResults[$fieldKey] = $resultsDetail[$fieldKey];
                        }else{
                            $this->builtInPreCheckerResults[$fieldKey] = null;
                            $resultsDetail[$fieldKey] = null;
                        }
                    break;

                    case 'noDuplicateLastNames_isEnable':
                        $fieldKey = 'noDuplicateLastNames';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $isEnable =  false;
                        if( $contdtionDetail['noDuplicateLastNames_isEnable'] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        $count = 0;
                        if($isEnable){ // game_logs.bet_amount = 0 means hedging record
                            $lastName = $this->playerDetail['lastName'];
                            $resultDetail = []; // reset
                            $isDuplicate = $this->dispatch_withdrawal_definition->isDuplicateLastName($lastName, $resultDetail);
                            $count = $resultDetail['count'];
                            $builtInPreCheckerResults['resultDetail'] = $resultDetail;
                            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
                            // for The contdtion result.
                            $result = ! $isDuplicate; // Should be a Data in player,If met the condition.
                            // $this->builtInPreCheckerResults[$fieldKey]['result'] = $result;
                            $resultsDetail[$fieldKey]['result'] = $result;
                            $resultsDetail[$fieldKey]['count'] = $count;
                            $this->builtInPreCheckerResults[$fieldKey] = $resultsDetail[$fieldKey];
                        }else{
                            $this->builtInPreCheckerResults[$fieldKey] = null;
                            $resultsDetail[$fieldKey] = null;
                        }
                    break;

                    case 'excludedPlayerLevels_isEnable':
                        $fieldKey = 'excludedPlayerLevels';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $isEnable =  false;
                        if( $contdtionDetail['excludedPlayerLevels_isEnable'] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        if($isEnable){
                            $result = false;
                            $excludedPlayerLevel_list = $contdtionDetail['excludedPlayerLevel_list'];
                            // $excludedPlayerLevellist = explode(',', $excludedPlayerLevel_list);
$this->utils->debug_log('OGP-20820.293.excludedPlayerLevel_list',$excludedPlayerLevel_list);

                            $vipsettingcashbackruleId = $this->group_level->getPlayerLevelId($this->playerId);
$this->utils->debug_log('OGP-20820.293.vipsettingcashbackruleId',$vipsettingcashbackruleId);
                            if(in_array($vipsettingcashbackruleId, $excludedPlayerLevel_list)) {
                                $result = false;
                            }else{
                                $result = true;
                            }
                            $resultsDetail[$fieldKey]['PlayerLevelId'] = $vipsettingcashbackruleId; // of the player
                            $resultsDetail[$fieldKey]['excludedPlayerLevel_list'] = $excludedPlayerLevel_list;
                            $resultsDetail[$fieldKey]['result'] = $result;
                            $this->builtInPreCheckerResults[$fieldKey] = $resultsDetail[$fieldKey];

                            break;
                        }else{
                            $this->builtInPreCheckerResults[$fieldKey] = null;
                            $resultsDetail[$fieldKey] = null;
                        }
                    break;

                    case 'excludedPlayerTag_isEnable':
                        $fieldKey = 'excludedPlayerTag';

                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $isEnable =  false;
                        if( $contdtionDetail['excludedPlayerTag_isEnable'] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        if($isEnable){
                            $result = true;
                            $player_tags = $this->player_model->getPlayerTags($this->playerId, TRUE);
                            $excludedPlayerTag_list = $contdtionDetail['excludedPlayerTag_list'];
                            $excludedPlayerTagList = explode(',', $excludedPlayerTag_list);
                            if( ! empty($player_tags) && ! empty($excludedPlayerTagList) ){
                                foreach($player_tags as $indexNumber => $tagId){
                                    // $isTagged = $this->player_model->checkIfPlayerIsTagged($playerId, $tagId);
                                    if( in_array($tagId, $excludedPlayerTagList) ){
                                        $result = $result && false;
                                        $resultsDetail[$fieldKey]['excludedTagId'] = $tagId; // of the player
                                        $resultsDetail[$fieldKey]['excludedPlayerTag_list'] = $excludedPlayerTag_list;
                                        break;
                                    }
                                }
                            }
                            $resultsDetail[$fieldKey]['result'] = $result;
                            $this->builtInPreCheckerResults[$fieldKey] = $resultsDetail[$fieldKey];
                        }else{
                            $this->builtInPreCheckerResults[$fieldKey] = null;
                            $resultsDetail[$fieldKey] = null;
                        }


                    break;

                    case 'noHedgingRecord_isEnable': // 无对冲记录:若有一筆是無效投注，就沒有滿足，該 fieldName 沒有在資料表內。
                    case 'calcAvailableBetOnly_isEnable': // To Use the field, "calcAvailableBetOnly_isEnable" for enable/disable switch.
                        $fieldKey = 'noHedgingRecord';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $resultsDetail[$fieldKey] = [];
                        $from_datetime = $this->lastWithdrawlDatetime;
                        $to_datetime = $this->currectWithdrawlDatetime;

                        // enable by calcAvailableBetOnly_isEnable
                        $isEnable =  false;
                        if( $contdtionDetail['calcAvailableBetOnly_isEnable'] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        $count = 0;
                        $is_count = true;
                        if($isEnable){ // game_logs.bet_amount = 0 means hedging record
                            $resultDetail = []; // reset
                            $count = $this->dispatch_withdrawal_definition
                                            ->hasHedgingDataByPlayerId($this->playerId, $from_datetime, $to_datetime, $is_count, $resultDetail);
                            $builtInPreCheckerResults['resultDetail'] = $resultDetail;
                            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
                            // for The contdtion result.
                            $result = empty($count); // Shoule be Not-Found any Data in AG games,If met the condition.
                            // $this->builtInPreCheckerResults[$fieldKey]['result'] = $result;
                            $resultsDetail[$fieldKey]['result'] = $result;
                            $resultsDetail[$fieldKey]['count'] = $count;
                            $this->builtInPreCheckerResults[$fieldKey] = $resultsDetail[$fieldKey];
                        }else{
                            $this->builtInPreCheckerResults[$fieldKey] = null;
                            $resultsDetail[$fieldKey] = null;
                        }

                    break;// EOF calcAvailableBetOnly_isEnable // EOF noHedgingRecord_isEnable


                    // 无注单被取消:若有一筆試註銷單，就沒有滿足
                    case 'ignoreCanceledGameLogs_isEnable':// To Use the field, "ignoreCanceledGameLogs_isEnable" for enable/disable switch.
                        $fieldKey = 'ignoreCanceledGameLogs';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $resultsDetail[$fieldKey] = [];
                        $from_datetime = $this->lastWithdrawlDatetime;
                        $to_datetime = $this->currectWithdrawlDatetime;

                        // enable by calcAvailableBetOnly_isEnable
                        $isEnable =  false;
                        if( $contdtionDetail['ignoreCanceledGameLogs_isEnable'] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        $count = 0;
                        $is_count = true;
                        if($isEnable){ // game_logs.bet_amount = 0 means hedging record
                            $resultDetail = []; // reset
                            $count = $this->dispatch_withdrawal_definition
                                            ->hasCanceledGameDataInUnsettled($this->playerId, $from_datetime, $to_datetime, $gameDescIdList,$is_count, $resultDetail);
                            $builtInPreCheckerResults['resultDetail'] = $resultDetail;
                            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
                            // for The contdtion result.
                            $result = empty($count);
                            $this->builtInPreCheckerResults[$fieldKey]['result'] = $result; // Shoule be Not-Found any Zero Bet Amount Data in game log,If met the condition.
                            $resultsDetail[$fieldKey]['result'] = $result;
                            $resultsDetail[$fieldKey]['count'] = $count;
                        }else{
                            $this->builtInPreCheckerResults[$fieldKey] = null;
                            $resultsDetail[$fieldKey] = null;
                        }
                    break;// EOF ignoreCanceledGameLogs_isEnable


                    case 'theTotalBetGreaterOrEqualRequired_isEnable':
                        $isEnable =  false;
                        $fieldKey = 'theTotalBetGreaterOrEqualRequired';
                        $resultsDetail[$fieldKey] = [];
                        if( $contdtionDetail['theTotalBetGreaterOrEqualRequired_isEnable'] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }

                        if($isEnable){
                            $playerId = $this->playerId;
                            $result = $this->withdraw_condition->computePlayerWithdrawalConditions($playerId);

                            $totalRequiredBet = $result['totalRequiredBet'];
                            $totalPlayerBet = $result['totalPlayerBet'];
                            // $totalPlayerBet >= $totalRequiredBet
                            $count = $totalPlayerBet;
                            $symbol = '>=';
                            $limit = $totalRequiredBet;
                            $_resultCompareCondition = $this->processCompareCondition($count, $symbol, $limit, true);
                            // $resultsDetail[$fieldKey]['resultDetail']
$this->utils->debug_log('OGP-25007.440._resultCompareCondition:', $_resultCompareCondition);
                            $resultsDetail[$fieldKey]['resultCompareCondition'] = $_resultCompareCondition;

                            $resultCompareCondition['result'] = $resultsDetail[$fieldKey]['resultCompareCondition']['result'];

                            $resultsDetail[$fieldKey]['result'] = $resultCompareCondition['result'];
                            $resultsDetail[$fieldKey]['count'] = $count;
                            $resultsDetail[$fieldKey]['isEnable'] = $isEnable;
                            $this->builtInPreCheckerResults[$fieldKey] = $resultsDetail[$fieldKey];
                            $this->builtInPreCheckerResults[$fieldKey]['result'] = $resultCompareCondition['result'];
                        }else{
                            // $resultsDetail[$fieldKey]['isEnable'] = $isEnable;
                            $resultsDetail[$fieldKey] = null;
                            $this->builtInPreCheckerResults[$fieldKey] = null;
                        }
                    break; // EOF theTotalBetGreaterOrEqualRequired_isEnable

                    case 'thePlayerHadExistsInIovation_isEnable':
                        $fieldKey = 'thePlayerHadExistsInIovation';
                        $isEnable =  false;
                        if( $contdtionDetail['thePlayerHadExistsInIovation_isEnable'] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }

                        $username = $this->playerDetail['username'];
                        if($isEnable){
                            $resultDetail = []; // reset
                            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
                            $iovation_evidence_rows = $this->iovation_evidence->getEvidenceRowsByUsername($username);
                            $count =  count($iovation_evidence_rows);
                            $result = !empty($count); // isEnable
                            $resultsDetail[$fieldKey]['result'] = $result;
                            $resultsDetail[$fieldKey]['count'] = $count;
                            $resultsDetail[$fieldKey]['isEnable'] = $isEnable;
                            $this->builtInPreCheckerResults[$fieldKey] = $resultsDetail[$fieldKey];
                            $this->builtInPreCheckerResults[$fieldKey]['result'] = $result;
                        }else{
                            $resultsDetail[$fieldKey] = null;
                            $this->builtInPreCheckerResults[$fieldKey] = null;
                        }

                    break;// EOF thePlayerHadExistsInIovation_isEnable

                    case 'winAndDepositRate_isEnable':
                        $fieldKey = 'winAndDepositRate';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $resultsDetail[$fieldKey] = [];
                        // $builtInPreCheckerResults = [];
                        $this->load->model(array('transactions'));
                        $last_deposit = $this->transactions->getLastDepositDate($this->playerId);
                        $from_datetime = $last_deposit;
                        // $to_datetime = $this->utils->getNowForMysql();
                        $to_datetime = $this->currectWithdrawlDatetime;

                        // calcPromoDepositOnly, hasPlayerPromoIdCondition
                        $hasPlayerPromoIdCondition = '-1';
                        if($calcPromoDepositOnly_isEnable){
                            $hasPlayerPromoIdCondition = '1';
                        }

                        $isEnable =  false;
                        if( $contdtionDetail[$fieldName] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        $count = 0;
                        if($isEnable){

                            $resultDetail = []; // reset
                            $count = $this->dispatch_withdrawal_definition
                                            ->getResultAndDepositRateByPlayerId( $this->playerId // # 1
                                                                            , $gameDescIdList // # 2
                                                                            , $from_datetime // # 3
                                                                            , $to_datetime // # 4
                                                                            , $hasPlayerPromoIdCondition // # 5
                                                                            , $calcAvailableBetOnly // # 6
                                                                            , $resultDetail // # 7
                                                                        );
                            $builtInPreCheckerResults['resultDetail'] = $resultDetail;
                            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
                        }
                        $limit = $contdtionDetail['winAndDepositRate_rate'];
                        $symbol = $this->symbolIntToMathSymbol($contdtionDetail['winAndDepositRate_symbol']);
                        $builtInPreCheckerResults = array_merge($builtInPreCheckerResults, $this->processCompareCondition($count, $symbol, $limit, $isEnable, $from_datetime, $to_datetime) );

                        $this->builtInPreCheckerResults[$fieldKey] = $builtInPreCheckerResults;
                        $resultsDetail[$fieldKey] = array_merge($resultsDetail[$fieldKey], $builtInPreCheckerResults);

                    break;

                    case 'totalDepositCount_isEnable':
                        $fieldKey = 'totalDepositCount';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $resultsDetail[$fieldKey] = [];
                        // $builtInPreCheckerResults = [];
                        $from_datetime = $this->playerDetail['createdOn'];
                        // list($from_datetime, $to_datetime) = $this->dispatch_withdrawal_definition->getThePlayerLastWithdrawlDatetimeToNow($this->playerId, $from_datetime);
                        // $to_datetime = $this->lastWithdrawlDatetime;
                        // $from_datetime = $this->lastWithdrawlDatetime;
                        // $to_datetime = $this->utils->getNowForMysql();
                        $to_datetime = $this->currectWithdrawlDatetime;

                        // calcPromoDepositOnly, hasPlayerPromoIdCondition
                        $hasPlayerPromoIdCondition = '-1';
                        /// ignore since OGP-19256
                        // if($calcPromoDepositOnly_isEnable){
                        //     $hasPlayerPromoIdCondition = '1';
                        // }

                        $isEnable =  false;
                        if( $contdtionDetail[$fieldName] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        $count = 0;
                        if($isEnable){
                            $resultDetail = []; // reset
                            $count = $this->dispatch_withdrawal_definition->getTotalDepositCountFromLastWithdrawDatetime($this->playerId, $from_datetime, $to_datetime, $hasPlayerPromoIdCondition, $resultDetail);
                            $builtInPreCheckerResults['resultDetail'] = $resultDetail;
                            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
                        }

                        $limit = $contdtionDetail['totalDepositCount_limit'];
                        $symbol = $this->symbolIntToMathSymbol($contdtionDetail['totalDepositCount_symbol']);
                        $builtInPreCheckerResults = array_merge($builtInPreCheckerResults, $this->processCompareCondition($count, $symbol, $limit, $isEnable, $from_datetime, $to_datetime) );

                        $this->builtInPreCheckerResults[$fieldKey] = $builtInPreCheckerResults;
                        $resultsDetail[$fieldKey] = array_merge($resultsDetail[$fieldKey], $builtInPreCheckerResults);
                    break; // EOF case 'totalDepositCount_isEnable':

                    case 'calcEnabledGameOnly_isEnable':
                        $fieldKey = 'calcEnabledGameOnly';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $resultsDetail[$fieldKey] = [];
                        $from_datetime = $this->lastWithdrawlDatetime;
                        $to_datetime = $this->currectWithdrawlDatetime;
                        $isEnable =  false;
                        if( $contdtionDetail[$fieldName] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        if( $isEnable ){
                            $resultDetail = []; // reset
                            $calcAvailableBetOnly = false;
                            $betAmount = $this->dispatch_withdrawal_definition
                                                            ->getBetAmountByPlayerId(  $this->playerId // # 1
                                                                                        , $gameDescIdList // # 2
                                                                                        , $from_datetime // # 3
                                                                                        , $to_datetime // # 4
                                                                                        , $calcAvailableBetOnly // # 5
                                                                                        , $resultDetail // # 6
                                                                                    );
                            $result = !empty($betAmount);
                            $this->builtInPreCheckerResults[$fieldKey]['result'] = $result; // Shoule be found any More than Zero Bet Amount Data in game log,If met the condition.
                            $resultsDetail[$fieldKey]['result'] = $result;
                            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
                            $this->builtInPreCheckerResults[$fieldKey] = $resultsDetail[$fieldKey];
                        }else{
                            $this->builtInPreCheckerResults[$fieldKey] = null;
                            $resultsDetail[$fieldKey] = null;
                        }
                    break; // EOF case 'calcEnabledGameOnly_isEnable':

                    case 'betAndWithdrawalRate_isEnable':
                        $fieldKey = 'betAndWithdrawalRate';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $resultsDetail[$fieldKey] = [];
                        $isEnable =  false;
                        if( $contdtionDetail['betAndWithdrawalRate_isEnable'] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        if($isEnable){ // game_logs.bet_amount = 0 means hedging record
                            $this->load->model(array('withdraw_condition'));



                            $playerId = $this->playerId;
                            $result = $this->withdraw_condition->computePlayerWithdrawalConditions($playerId);
                            // $count = $this->dispatch_withdrawal_definition
                            //                 ->getRequiredBetAndWithdrawalRateByPlayerId(  $currentWithdrawalAmount // # 1
                            //                                                                 , $playerId // # 2
                            //                                                                 , $from_datetime // # 3
                            //                                                                 , $to_datetime // # 4
                            //                                                                 , $resultDetail // # 5
                            //                                                             );
                            $resultsDetail[$fieldKey]['resultDetail'] = $result;
                            $resultCompareCondition = $this->withdraw_condition->computePlayerWithdrawalConditionsV2( $playerId // # 1
                                                                                        , $this->currectWithdrawlDatetime // # 2
                                                                                        , $this->lastWithdrawlDatetime // # 3
                                                                                        , $isEnable // # 4
                                                                                        , $fieldKey // # 5
                                                                                        , $contdtionDetail // # 6
                                                                                        , $resultsDetail // # 7
                                                                                        , $gameDescIdList // # 8
                                                                                );
                            $builtInPreCheckerResults = array_merge($builtInPreCheckerResults, $resultCompareCondition);

                            $this->builtInPreCheckerResults[$fieldKey] = $builtInPreCheckerResults;
                            $resultsDetail[$fieldKey] = array_merge($resultsDetail[$fieldKey], $builtInPreCheckerResults);
                        }else{
                            $this->builtInPreCheckerResults[$fieldKey] = null;
                            $resultsDetail[$fieldKey] = null;
                        }


                    break;
                    case 'betAndWithdrawalRate_isEnable_DEL': // Disable for Not match requirement.
                        $fieldKey = 'betAndWithdrawalRate';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $resultsDetail[$fieldKey] = [];
                        // $builtInPreCheckerResults = [];

                        // $from_datetime = $this->playerDetail['createdOn'];
                        // list($from_datetime, $to_datetime) = $this->dispatch_withdrawal_definition->getThePlayerLastWithdrawlDatetimeToNow($this->playerId, $from_datetime);
                        // $to_datetime = $this->lastWithdrawlDatetime;
                        $from_datetime = $this->lastWithdrawlDatetime;
                        // $to_datetime = $this->utils->getNowForMysql();
                        $to_datetime = $this->currectWithdrawlDatetime;

                        $isEnable =  false;
                        if( $contdtionDetail[$fieldName] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        $count = 0;
                        if( $isEnable ){
                            $resultDetail = []; // reset
                            $currentWithdrawalAmount = $this->walletAccountDeatil['amount']; // data_type, double
                            $playerId = $this->playerId;
                            // $calcAvailableBetOnly
                            // $count = $this->dispatch_withdrawal_definition
                            //                 ->getBetAndWithdrawalRateByPlayerId(  $currentWithdrawalAmount // # 1
                            //                                                     , $playerId // # 2
                            //                                                     , $gameDescIdList // # 3
                            //                                                     , $from_datetime // # 4
                            //                                                     , $to_datetime // # 5
                            //                                                     , $calcAvailableBetOnly // # 6
                            //                                                     , $resultDetail // # 7
                            //                                                 );

                            $count = $this->dispatch_withdrawal_definition
                                            ->getRequiredBetAndWithdrawalRateByPlayerId(  $currentWithdrawalAmount // # 1
                                                                                            , $playerId // # 2
                                                                                            , $from_datetime // # 3
                                                                                            , $to_datetime // # 4
                                                                                            , $resultDetail // # 5
                                                                                        );

                            $builtInPreCheckerResults['resultDetail'] = $resultDetail;
                            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
                        }

                        $limit = $contdtionDetail['betAndWithdrawalRate_rate']; // betAndWithdrawalRate_rate
                        $symbol = $this->symbolIntToMathSymbol($contdtionDetail['betAndWithdrawalRate_symbol']);
                        $builtInPreCheckerResults = array_merge($builtInPreCheckerResults, $this->processCompareCondition($count, $symbol, $limit, $isEnable, $from_datetime, $to_datetime) );

                        $this->builtInPreCheckerResults[$fieldKey] = $builtInPreCheckerResults;
                        $resultsDetail[$fieldKey] = array_merge($resultsDetail[$fieldKey], $builtInPreCheckerResults);
                    break; // EOF case 'betAndWithdrawalRate_isEnable':


                    case 'gameRevenuePercentage_isEnable':
                        $fieldKey = 'gameRevenuePercentage';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $resultsDetail[$fieldKey] = [];
                        // $builtInPreCheckerResults = [];
                        // $builtInPreCheckerResults['conditions_id'] = $contdtionDetail['id'];

                        $isEnable =  false;
                        if( $contdtionDetail[$fieldName] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        $from_datetime = $this->lastWithdrawlDatetime;
                        // $to_datetime = $this->utils->getNowForMysql();
                        $to_datetime = $this->currectWithdrawlDatetime;

                        $count = 0;
                        if($isEnable){
                            $resultDetail = []; // reset
                            $playerId = $this->playerId;
                            $count = $this->dispatch_withdrawal_definition
                                            ->getGameRevenuePercentageByPlayerId( $playerId // # 1
                                                                                , $from_datetime // # 2
                                                                                , $to_datetime // # 3
                                                                                , $gameDescIdList // # 4
                                                                                , $calcAvailableBetOnly // # 5
                                                                                , $resultDetail // # 6
                                                                            );
                            $builtInPreCheckerResults['resultDetail'] = $resultDetail;
                            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
                        }

                        $limit = $contdtionDetail['gameRevenuePercentage_rate'];
                        $symbol = $this->symbolIntToMathSymbol($contdtionDetail['gameRevenuePercentage_symbol']);
                        // $this->builtInPreCheckerResults[$fieldKey] = $this->processCompareCondition($count, $symbol, $limit, $isEnable, $from_datetime, $to_datetime);
                        $builtInPreCheckerResults = array_merge($builtInPreCheckerResults, $this->processCompareCondition($count, $symbol, $limit, $isEnable, $from_datetime, $to_datetime) );
                        $this->builtInPreCheckerResults[$fieldKey] = $builtInPreCheckerResults;
                        $resultsDetail[$fieldKey] = array_merge($resultsDetail[$fieldKey], $builtInPreCheckerResults);
                    break; // EOF case 'gameRevenuePercentage_isEnable':

                    case 'afterDepositWithdrawalCount_isEnable':
                        $fieldKey = 'afterDepositWithdrawalCount';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $resultsDetail[$fieldKey] = [];
                        // $builtInPreCheckerResults = [];
                        $this->load->library(['player_manager']);
                        $theFirstLastApprovedTransaction = $this->player_manager->getPlayerFirstLastApprovedTransaction($this->playerId, Transactions::DEPOSIT);
                        // $this->utils->debug_log('OGP-19922.645.theFirstLastApprovedTransaction',$theFirstLastApprovedTransaction);
                        $from_datetime = $theFirstLastApprovedTransaction['last'];
                        $to_datetime = $this->currectWithdrawlDatetime;
                        $isEnable =  false;
                        if( $contdtionDetail[$fieldName] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        $count = 0;
                        if($isEnable){
                            $resultDetail = []; // reset
                            $playerId = $this->playerId;
                            $hasPlayerPromoIdCondition = -1; // should ignore
                            $count = $this->dispatch_withdrawal_definition
                                            ->getTotalWithdrawalCountFromLastWithdrawDatetime( $playerId // # 1
                                                , $from_datetime // # 2
                                                , $to_datetime // # 3
                                                , $hasPlayerPromoIdCondition // # 4
                                                , Transactions::WITHDRAWAL // # 5
                                                , $resultDetail // # 6
                                            );
                            $builtInPreCheckerResults['resultDetail'] = $resultDetail;
                            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
                        }

                        $limit = $contdtionDetail['afterDepositWithdrawalCount_limit'];
                        $symbol = $this->symbolIntToMathSymbol($contdtionDetail['afterDepositWithdrawalCount_symbol']);

                        // $this->builtInPreCheckerResults[$fieldKey] = $this->processCompareCondition($count, $symbol, $limit, $isEnable, $from_datetime, $to_datetime);
                        $builtInPreCheckerResults = array_merge($builtInPreCheckerResults, $this->processCompareCondition($count, $symbol, $limit, $isEnable, $from_datetime, $to_datetime) );
                        $this->builtInPreCheckerResults[$fieldKey] = $builtInPreCheckerResults;
                        $resultsDetail[$fieldKey] = array_merge($resultsDetail[$fieldKey], $builtInPreCheckerResults);;
                    break; // EOF case 'afterDepositWithdrawalCount_isEnable':

                    case 'todayWithdrawalCount_isEnable':
                        $fieldKey = 'todayWithdrawalCount';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $resultsDetail[$fieldKey] = [];
                        // $builtInPreCheckerResults = [];

                        $from_datetime = $this->utils->getTodayForMysql(). ' 00:00:00';
                        // $to_datetime = $this->utils->getNowForMysql();
                        $to_datetime = $this->currectWithdrawlDatetime;

                        $isEnable =  false;
                        if( $contdtionDetail[$fieldName] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        $count = 0;
                        if($isEnable){
                            $resultDetail = []; // reset
                            $playerId = $this->playerId;
                            $hasPlayerPromoIdCondition = -1; // should ignore
                            $count = $this->dispatch_withdrawal_definition
                                            ->getTotalWithdrawalCountFromLastWithdrawDatetime( $playerId // # 1
                                                , $from_datetime // # 2
                                                , $to_datetime // # 3
                                                , $hasPlayerPromoIdCondition // # 4
                                                , Transactions::WITHDRAWAL // # 5
                                                , $resultDetail // # 6
                                            );
                            $builtInPreCheckerResults['resultDetail'] = $resultDetail;
                            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
                        }

                        $limit = $contdtionDetail['todayWithdrawalCount_limit'];
                        $symbol = $this->symbolIntToMathSymbol($contdtionDetail['todayWithdrawalCount_symbol']);

                        // $this->builtInPreCheckerResults[$fieldKey] = $this->processCompareCondition($count, $symbol, $limit, $isEnable, $from_datetime, $to_datetime);
                        $builtInPreCheckerResults = array_merge($builtInPreCheckerResults, $this->processCompareCondition($count, $symbol, $limit, $isEnable, $from_datetime, $to_datetime) );
                        $this->builtInPreCheckerResults[$fieldKey] = $builtInPreCheckerResults;
                        $resultsDetail[$fieldKey] = array_merge($resultsDetail[$fieldKey], $builtInPreCheckerResults);
                    break; // EOF case 'todayWithdrawalCount_isEnable':

                    case 'withdrawalAmount_isEnable': /// current withdrawal Amount by walletAccountDeatil
                        $fieldKey = 'withdrawalAmount';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $resultsDetail[$fieldKey] = [];
                        // $builtInPreCheckerResults = [];

                        $from_datetime = $this->lastWithdrawlDatetime;
                        // $to_datetime = $this->utils->getNowForMysql();
                        $to_datetime = $this->currectWithdrawlDatetime;

                        $isEnable =  false;
                        if( $contdtionDetail[$fieldName] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }

                        $count = 0;
                        if($isEnable){
                            $resultDetail = []; // reset
                            $playerId = $this->playerId;
                            $hasPlayerPromoIdCondition = -1; // should ignore

                            /// calc withdrawal Amount from last to current.
                            // $count = $this->dispatch_withdrawal_definition->getTotalWithdrawalAmountFromLastWithdrawDatetime($playerId, $from_datetime, $to_datetime, $resultDetail);
                            // $builtInPreCheckerResults['resultDetail'] = $resultDetail;

                            $count = $this->walletAccountDeatil['amount']; // data_type, double
                        }
                        $limit = $contdtionDetail['withdrawalAmount_limit'];
                        $symbol = $this->symbolIntToMathSymbol($contdtionDetail['withdrawalAmount_symbol']);

                        // $this->builtInPreCheckerResults[$fieldKey] = $this->processCompareCondition($count, $symbol, $limit, $isEnable, $from_datetime, $to_datetime);
                        $_from_datetime = '';
                        $_to_datetime = '';
                        $builtInPreCheckerResults = array_merge($builtInPreCheckerResults, $this->processCompareCondition($count, $symbol, $limit, $isEnable, $_from_datetime, $_to_datetime) );
                        $this->builtInPreCheckerResults[$fieldKey] = $builtInPreCheckerResults;
                        $resultsDetail[$fieldKey] = array_merge($resultsDetail[$fieldKey], $builtInPreCheckerResults);
                    break; // EOF case 'withdrawalAmount_isEnable':

                    case 'noAddBonusSinceTheLastWithdrawal_isEnable':
                        $fieldKey = 'noAddBonusSinceTheLastWithdrawal';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $resultsDetail[$fieldKey] = [];
                        $from_datetime = $this->lastWithdrawlDatetime;
                        $to_datetime = $this->currectWithdrawlDatetime;
                        $isEnable =  false;
                        if( $contdtionDetail[$fieldName] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        $count = 0;
                        if($isEnable){
                            $this->load->model(['transactions']);
                            $player_ids = [];
                            $player_ids[] = $this->playerId;
                            $transaction_types = [];
                            $transaction_types[] = Transactions::ADD_BONUS; // Add bonus
                            $transaction_types[] = Transactions::SUBTRACT_BONUS; // Subtract bonus
                            $transaction_types[] = Transactions::MEMBER_GROUP_DEPOSIT_BONUS;  // VIP Bonus
                            $transaction_types[] = Transactions::PLAYER_REFER_BONUS; // Player Refer Bonus
                            $transaction_types[] = Transactions::RANDOM_BONUS; // Random Bonus
                            $transaction_types[] = Transactions::BIRTHDAY_BONUS; // Birthday Bonus
                            $start_date = $from_datetime;
                            $end_date = $to_datetime;
                            $count = $this->transactions->getCountByPlayersAndTypes($player_ids,$transaction_types, $start_date, $end_date);
                            $result = empty($count);
                            $resultDetail = [];
                            $resultDetail['result'] = $result;
                            $resultDetail['count'] = $count;
                            $resultDetail['from_datetime'] = $from_datetime;
                            $resultDetail['to_datetime'] = $to_datetime;

                            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
                            $resultsDetail[$fieldKey]['result'] = $result;
                            $builtInPreCheckerResults['resultDetail'] = $resultDetail;
                            $builtInPreCheckerResults['result'] = $result;

                        }else{
                            $builtInPreCheckerResults[$fieldKey] = null;
                            $resultsDetail[$fieldKey] = null;
                        }
                        $this->builtInPreCheckerResults[$fieldKey] = $builtInPreCheckerResults;
                    break; //  EOF case 'noAddBonusSinceTheLastWithdrawal':

                    case 'noDepositWithPromo_isEnable':
                        $fieldKey = 'noDepositWithPromo';
                        $this->builtInPreCheckerResults[$fieldKey] = [];
                        $resultsDetail[$fieldKey] = [];
                        $from_datetime = $this->lastWithdrawlDatetime;
                        $to_datetime = $this->currectWithdrawlDatetime;
                        $isEnable =  false;
                        if( $contdtionDetail[$fieldName] == Dispatch_withdrawal_conditions::DB_TRUE ){
                            $isEnable = true;
                        }
                        $count = 0;
                        if($isEnable){
                            $hasPlayerPromoIdCondition = '1';
                            $resultDetail = []; // reset
                            $count = $this->dispatch_withdrawal_definition->getTotalDepositCountFromLastWithdrawDatetime($this->playerId, $from_datetime, $to_datetime, $hasPlayerPromoIdCondition, $resultDetail);
                            $result = empty($count);
                            $resultsDetail[$fieldKey]['result'] = $result;
                            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
                            $builtInPreCheckerResults['resultDetail'] = $resultDetail;
                        }else{
                            $builtInPreCheckerResults[$fieldKey] = null;
                            $resultsDetail[$fieldKey] = null;
                        }
                        $this->builtInPreCheckerResults[$fieldKey] = $builtInPreCheckerResults;
                    break; // EOF case 'noDepositWithPromo_isEnable':

                    default:
                    break; // EOF default:

                } // EOF switch ($fieldName){...

            } // EOF foreach ($contdtionDetail as $fieldName => $fieldVal)

            /// disable for OGP-19258
            // // built-in, met the Withdrawal Requirements
            // $fieldKey = 'isMetWithdrawalRequirements';
            // $result = $this->preChecker[$fieldKey]['result'];
            // $resultDetail = $this->preChecker[$fieldKey]['resultDetail'];
            // $resultsDetail[$fieldKey] = [];
            // $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
            // $resultsDetail[$fieldKey]['result'] = $result;
            // $this->builtInPreCheckerResults[$fieldKey] = $resultsDetail[$fieldKey]; // merge to builtInPreCheckerResults for getFinallyResultFromBuiltInPreCheckerResults().

            // built-in, is the dwStatus actived?
            $fieldKey = 'isDwStatusActived';
            $eligible2dwStatus = $this->definitionDetail['eligible2dwStatus'];
            $doCheckPermissions = false;
            $activedDwStatusList = $this->dispatch_withdrawal_definition->getEligible2dwStatus4OptionList($doCheckPermissions);
            $isDwStatusActived = array_key_exists($eligible2dwStatus, $activedDwStatusList);
            $result = $isDwStatusActived;
            $resultDetail = [];
            $resultDetail['eligible2dwStatus'] = $eligible2dwStatus;
            $resultDetail['activedDwStatusList'] = $activedDwStatusList;
            $resultsDetail[$fieldKey] = [];
            $resultsDetail[$fieldKey]['resultDetail'] = $resultDetail;
            $resultsDetail[$fieldKey]['result'] = $result;
            $this->builtInPreCheckerResults[$fieldKey] = $resultsDetail[$fieldKey]; // merge to builtInPreCheckerResults for getFinallyResultFromBuiltInPreCheckerResults().

            // finallyResult = conditions_id finally result.
            // check by builtInPreCheckerResults attr.
            $finallyResult = $this->getFinallyResultFromBuiltInPreCheckerResults();
            $resultsDetail['finallyResult'] = $finallyResult; // for resultslog
            $contdtionKeyStr = sprintf('contdtionId_%d', $contdtionDetail['id']);
            // $this->builtInPreCheckerResults['finallyResult'] = $finallyResult; // finally result of a condition
            // $this->builtInPreCheckerResults['definitionId'] = $this->definitionDetail['id'];
            // $this->builtInPreCheckerResults['contdtionId'] = $contdtionDetail['id'];

            // $resultDetail = $this->builtInPreCheckerResults;
            $this->builtInPreCheckerResults[$contdtionKeyStr]['resultsDetail'] = $resultsDetail; // for assess by results log
            $this->builtInPreCheckerResults[$contdtionKeyStr]['finallyResult'] = $finallyResult; // store by the contdtion.id key

            // to log this moment what results into the table by walletaccount.walletAccountId.
$this->utils->debug_log('OGP-18088,118.builtInPreCheckerResults', $this->builtInPreCheckerResults, '$this->playerId:', $this->playerId, 'walletAccountId:', $this->walletAccountDeatil['walletAccountId']);

            // $data = [];
            // $data['wallet_account_id'] = $this->walletAccountDeatil['walletAccountId'];
            // $data['definition_id'] = $this->definitionDetail['id'];
            // $data['definition_results'] = json_encode($resultDetail);
            // $data['dispatch_order'] = $this->definitionDetail['dispatch_order'];
            // $resultsId = $this->dispatch_withdrawal_results->add($data);
            // $this->builtInPreCheckerResults[$contdtionKeyStr]['resultsId'] = $resultsId;

            return $finallyResult;
        } // EOF if( ! empty( $contdtionDetail ) )

    } // EOF runBuiltInPreCheckerByContdtionDetail

    /**
     * Collect all Conditions for Finally Result (boolean)
     * Use AND-gate during the each condition.
     *
     * @return boolean The Finally Result from each contdtions
     */
    function getFinallyResultFromBuiltInPreCheckerResults(){
        $finallyResult = null;
        $coniditionKeyList = $this->getConiditionKeyList();
        $builtInPreCheckerResults = $this->builtInPreCheckerResults;
$this->utils->debug_log('OGP-18088, 490.builtInPreCheckerResults',$builtInPreCheckerResults, '$this->definitionDetail[id]:', $this->definitionDetail['id'] );

        /// Check the eligibled withdraw status exists
        // If its Not exists, The false will be the default in finallyResult
        // If its exists, it does not affect to finallyResult.
        $coniditionKeyStr = 'isDwStatusActived';
        if( isset($coniditionKeyList[$coniditionKeyStr]) ) {
            // it should always be exists
            $result = $builtInPreCheckerResults[$coniditionKeyStr]['result'];
            if( empty($result) ){ // only false will be the default.
                $finallyResult = $result;
            }
        }

        foreach($coniditionKeyList as $coniditionKeyStr => $value){
            if( isset($builtInPreCheckerResults[$coniditionKeyStr]['result']) // enabled
                && ! is_null($builtInPreCheckerResults[$coniditionKeyStr]['result']) // after initialized
            ){
                $this->utils->debug_log('OGP-18088, 490.will.coniditionKeyStr', $coniditionKeyStr, 'finallyResult:', $finallyResult, 'result:', $builtInPreCheckerResults[$coniditionKeyStr]['result'] );
                if( $coniditionKeyStr !== 'isDwStatusActived' ) {  // isDwStatusActived had handled before.
                    if( is_null($finallyResult) ){ // first result directly assign
                        $finallyResult = $builtInPreCheckerResults[$coniditionKeyStr]['result'];
                    }
                    $result = $builtInPreCheckerResults[$coniditionKeyStr]['result'] === true; // filter each conidition result non-boolean type
                    $this->utils->debug_log('OGP-18088, 490.ing.result', $result);
                    $finallyResult = $finallyResult && $result;
                }
                $this->utils->debug_log('OGP-18088, 490.after', $finallyResult);
            }
        }

        return $finallyResult;
    } // EOF getFinallyResultFromBuiltInPreCheckerResults

    /**
     * For getFinallyResultFromBuiltInPreCheckerResults() reference
     *
     *
     * @return array The key need to confirm for finallyResult.
     */
    function getConiditionKeyList(){
        $coniditionKeyList = [];
		$coniditionKeyList['totalDepositCount'] = [];
		$coniditionKeyList['betAndWithdrawalRate'] = []; // for OGP-19258.
        // $coniditionKeyList['includedGameType'] = []; // ignore for referenced to all condition of the definition.
        $coniditionKeyList['gameRevenuePercentage'] = [];
        $coniditionKeyList['todayWithdrawalCount'] = [];
        $coniditionKeyList['afterDepositWithdrawalCount'] = [];
        $coniditionKeyList['withdrawalAmount'] = [];
        $coniditionKeyList['winAndDepositRate'] = [];
        $coniditionKeyList['noHedgingRecord'] = [];
        $coniditionKeyList['ignoreCanceledGameLogs'] = [];
        $coniditionKeyList['excludedPlayerTag'] = [];
        // $coniditionKeyList['isMetWithdrawalRequirements'] = []; // ignored since OGP-19258.
        $coniditionKeyList['isDwStatusActived'] = [];
        $coniditionKeyList['calcEnabledGameOnly'] = [];
        $coniditionKeyList['noDuplicateFirstNames'] = [];
        $coniditionKeyList['noDuplicateLastNames'] = [];
        $coniditionKeyList['noDuplicateAccounts'] = [];
        $coniditionKeyList['noAddBonusSinceTheLastWithdrawal'] = [];
        $coniditionKeyList['excludedPlayerLevels'] = [];
        $coniditionKeyList['noDepositWithPromo'] = [];
        $coniditionKeyList['thePlayerHadExistsInIovation'] = [];
        $coniditionKeyList['theTotalBetGreaterOrEqualRequired'] = [];

        return $coniditionKeyList;
    }// EOF getConiditionKeyList

} // EOF Abstract_customized_definition