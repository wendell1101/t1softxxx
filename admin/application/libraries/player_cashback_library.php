<?php
require_once __DIR__ . '/cashback/common_cashback_multiple_rules.php';

/**
 * player_cashback_library.php
 *
 * @author Elvis Chen
 *
 * @property BaseController $CI
 */
class Player_cashback_library {
    // cashback mode
    const COMMON_CASHBACK_RULES_MODE_BY_SINGLE = 0;
    const COMMON_CASHBACK_RULES_MODE_BY_MULTIPLE_RANGE = 1;

    /* @var BaseController */
    public $CI;

    /* @var Group_level */
    public $group_level;

    /* @var Common_Cashback_multiple_rules */
    public $common_cashback_multiple_rules;

    public function __construct(){
        $this->CI =& get_instance();

        $this->CI->load->model(['group_level', 'game_type_model', 'game_description_model', 'common_cashback_multiple_range_templates_model', 'common_cashback_multiple_range_settings_model', 'common_cashback_multiple_range_rules_model']);

        $this->group_level = $this->CI->group_level;

        $this->common_cashback_multiple_rules = new Common_Cashback_multiple_rules();
    }

    /**
     * Reference to group_level->getPlayerBetByDate() and group_level->getPlayerBetBySettledDate().
     * Only collect player.playerId list by the return of above methods.
     *
     * @param object $cashBackSettings The return from group_level->getCashbackSettings().
     * @param string $currentDate The date string form utils->formatDateForMysql().
     * @return array $player_list The player.playerId list in an array.
     */
    public function getPlayerListFromBetMap($cashBackSettings, $currentDate, $isUseGetPlayerBetBySettledDateLite = false){
        $player_bet_map = [];
        $player_list = [];
        $use_settled_time_apis = $this->CI->utils->getConfig('api_array_when_calc_cashback_by_settled_time');
        $date = $currentDate;
        $start_date = null; // ref. to Common_Cashback_multiple_rules::calculateTotalCashback()
        $end_date = null; // ref. to Common_Cashback_multiple_rules::calculateTotalCashback()
        $startHour = $cashBackSettings->fromHour;
        $endHour = $cashBackSettings->toHour;
        $playerId = null;
        // $player_bet_map = $this->_getPlayerBetMap($date, $startHour, $endHour, $playerId, $start_date, $end_date);
        $playerBetByDate = $this->group_level->getPlayerBetByDate( $date // #1
                                                                , $startHour // #2
                                                                , $endHour // #3
                                                                , $playerId // #4
                                                                , $start_date // #5
                                                                , $end_date // #6
                                                                , NULL // #7
                                                                , $use_settled_time_apis // #8
                                                                , 'tpgh.player_id' // #9
                                                            );
        if(!empty($playerBetByDate)){
            $this->CI->utils->cloneArrayWithForeach($playerBetByDate, function($pbbd, $_playerBetByDate){ // aka. skipCondiCB($_curr, $arr)
                return $pbbd->betting_total <= 0;
            }, function( $pbbd, $_key, &$_player_bet_map, $_playerBetByDate ){ // aka. handCurrCB( $_curr, $_key, &$new_arr, $arr )
                $_player_bet_map[$pbbd->player_id][] = 1; // aka. $player_bet_map[$pbbd->player_id][] = $pbbd;
            }, $player_bet_map);
        }

        if($isUseGetPlayerBetBySettledDateLite){
$startTime=microtime(true);
            $playerBetBySettledDate = $this->group_level->getPlayerBetBySettledDateLite( $date // #1
                , $startHour // #2
                , $endHour // #3
                , $playerId // #4
                , $start_date // #5
                , $end_date // #6
                , NULL // #7
                , $use_settled_time_apis // #8
                , 'tpgh.player_id' // #9
            );
$this->CI->utils->debug_log("OGP-27272 cost of 84.group_level->getPlayerBetBySettledDateLite", microtime(true)-$startTime);
        }else{
$startTime=microtime(true);
            $playerBetBySettledDate = $this->group_level->getPlayerBetBySettledDate( $date // #1
                , $startHour // #2
                , $endHour // #3
                , $playerId // #4
                , $start_date // #5
                , $end_date // #6
                , NULL // #7
                , $use_settled_time_apis // #8
                , 'tpgh.player_id' // #9
            );
$this->CI->utils->debug_log("OGP-27272 cost of 97.group_level->getPlayerBetBySettledDate", microtime(true)-$startTime);
        }

        if(!empty($playerBetBySettledDate)){
            $this->CI->utils->cloneArrayWithForeach($playerBetBySettledDate, function($pbbd, $_playerBetByDate){ // aka. skipCondiCB($_curr, $arr)
                return $pbbd->betting_total <= 0;
            }, function( $pbbd, $_key, &$_player_bet_map, $_playerBetByDate ){ // aka. handCurrCB( $_curr, $_key, &$new_arr, $arr )
                $_player_bet_map[$pbbd->player_id][] = 1; // aka. $player_bet_map[$pbbd->player_id][] = $pbbd;
            }, $player_bet_map);
        }

        $player_list = array_keys($player_bet_map);
        return $player_list;
    }

    public function calculateDailyTotalCashbackBySettings( $cashBackSettings // #1
                                                            , $date // #2
                                                            , $playerId = null // #3
                                                            , $forceToPay = false // #4
                                                            , $recalculate_cashback = false // #5
                                                            , $uniqueId = null // #6
                                                            , $doExceptionPropagationInChoppedLock = false // #7
    ){
        $startHour = $cashBackSettings->fromHour;
        $endHour = $cashBackSettings->toHour;
        $withdraw_condition_bet_times = isset($cashBackSettings->withdraw_condition) ? $cashBackSettings->withdraw_condition : 0;
        $result = false;

        switch((int)$cashBackSettings->common_cashback_rules_mode){
            case static::COMMON_CASHBACK_RULES_MODE_BY_MULTIPLE_RANGE:
                $calcResult = $this->common_cashback_multiple_rules->calculateTotalCashback( $date // #1
                                                                                            , $startHour // #2
                                                                                            , $endHour // #3
                                                                                            , $playerId // #4
                                                                                            , $withdraw_condition_bet_times // #5
                                                                                            , $result // #6
                                                                                            , null // #7
                                                                                            , null // #8
                                                                                            , $forceToPay // #9
                                                                                            , $recalculate_cashback // #10
                                                                                            , $uniqueId // #11
                                                                                        );
                break;
            case static::COMMON_CASHBACK_RULES_MODE_BY_SINGLE:
            default:
                $calcResult = $this->group_level->totalCashback( $date // #1
                                                                , $startHour // #2
                                                                , $endHour // #3
                                                                , $playerId // #4
                                                                , $withdraw_condition_bet_times // #5
                                                                , $result // #6
                                                                , null // #7
                                                                , null // #8
                                                                , $forceToPay // #9
                                                                , $recalculate_cashback // #10
                                                                , $uniqueId // #11
                                                            );
                break;
        }
        return $calcResult;
    }

    public function calculateWeeklyTotalCashbackBySettings($cashBackSettings, $date, $playerId = null, $forceToPay = false){
        if(!$this->CI->utils->isEnabledFeature('enabled_weekly_cashback')){
            return;
        }

        $startHour = $cashBackSettings->fromHour;
        $endHour = $cashBackSettings->toHour;
        $withdraw_condition_bet_times = isset($cashBackSettings->withdraw_condition) ? $cashBackSettings->withdraw_condition : 0;
        $weekly = $cashBackSettings->weekly;

        $startDate = date("Y-m-d", strtotime("last week monday"));
        $endDate = date("Y-m-d", strtotime("last week sunday"));
        $result = false;

        // if found today is the day in cashback setting then calculate last week cash back
        $cashback_day = $this->group_level->getDayCashbackSetting($weekly);
        $current_day = date('l', strtotime($date));

        if ($cashback_day != $current_day) {
            return;
        }

        switch((int)$cashBackSettings->common_cashback_rules_mode){
            case static::COMMON_CASHBACK_RULES_MODE_BY_MULTIPLE_RANGE:
                $calcResult = $this->common_cashback_multiple_rules->calculateTotalCashback($date, $startHour, $endHour, $playerId, $withdraw_condition_bet_times, $result, $startDate, $endDate, $forceToPay);
                break;
            case static::COMMON_CASHBACK_RULES_MODE_BY_SINGLE:
            default:
                $calcResult = $this->group_level->totalCashback($date, $startHour, $endHour, $playerId, $withdraw_condition_bet_times, $result, $startDate, $endDate, $forceToPay);
                break;
        }
        return $calcResult;
    }

    public function manuallyCalculateTotalCashback($date, $playerId = null){
        $cashBackSettings = $this->group_level->getCashbackSettings();

        $startHour = $cashBackSettings->fromHour;
        $endHour = $cashBackSettings->toHour;
        $withdraw_condition_bet_times = isset($cashBackSettings->withdraw_condition) ? $cashBackSettings->withdraw_condition : 0;

        switch((int)$cashBackSettings->common_cashback_rules_mode){
            case static::COMMON_CASHBACK_RULES_MODE_BY_MULTIPLE_RANGE:
                $calcResult = $this->common_cashback_multiple_rules->calculateTotalCashback($date, $startHour, $endHour, $playerId, $withdraw_condition_bet_times);
                break;
            case static::COMMON_CASHBACK_RULES_MODE_BY_SINGLE:
            default:
                $calcResult = $this->group_level->totalCashback($date, $startHour, $endHour, $playerId, $withdraw_condition_bet_times);
                break;
        }
        return $calcResult;
    }

    public function autoAddCashbackToBalance($player_id, $wallet_type = 'Main Wallet', $adjustment_type, $amount, $user_id, $reason, &$transaction) {
        $this->CI->load->model(array('transactions', 'player_model', 'users', 'wallet_model', 'transaction_notes', 'withdraw_condition'));
        $this->CI->load->library(['payment_manager']);
        $result = array('success' => false);
        $player_name = $this->CI->player_model->getUsernameById($player_id);
        $user_name = $this->CI->users->selectUsersById($user_id)['username'];

        //only main wallet
        $lockedKey=null;
        $lock_it = $this->CI->lockPlayerBalanceResource($player_id, $lockedKey);
        try {
            if ($lock_it) {
                $this->CI->startTrans();

                $totalBeforeBalance = $this->CI->wallet_model->getTotalBalance($player_id);
                $this->CI->utils->debug_log('player_id', $player_id, 'totalBeforeBalance', $totalBeforeBalance);

                $before_adjustment = $this->CI->player_model->getMainWalletBalance($player_id);
                $after_adjustment = $before_adjustment + $amount;
                $action_name = 'Add manually cashback';

                if ($after_adjustment < 0) {
                    $this->CI->rollbackTrans();
                    return $result;
                }

                $note = sprintf('%s <b>%s</b> balance to <b>%s</b>\'s <b>%s</b>(<b>%s</b> to <b>%s</b>) by <b>%s</b>',
                    $action_name, number_format($amount, 2), $player_name, 'Main Wallet',
                    number_format($before_adjustment, 2), number_format($after_adjustment, 2), $user_name);

                $note_reason = sprintf('<i>Reason:</i> %s <br>',$reason);
                $note = (trim($reason) != '')  ? ($note_reason . sprintf('<i>Normal Note:</i> %s <br>',$note)) : $note;

                $transaction = $this->CI->transactions->createAdjustmentTransaction($adjustment_type,
                    $user_id, $player_id, $amount, $before_adjustment, $note, $totalBeforeBalance,
                    null, null, $reason, null, null, Transactions::MANUALLY_ADJUSTED);


                if (!$transaction) {
                    //rollback and quit;
                    $this->CI->rollbackTrans();
                    return array('success' => false);
                }

                $this->CI->payment_manager->addPlayerBalAdjustmentHistory(array(
                    'playerId' => $transaction['to_id'],
                    'adjustmentType' => $transaction['transaction_type'],
                    'walletType' => 0, # 0 - MAIN WALLET
                    'amountChanged' => $transaction['amount'],
                    'oldBalance' => $transaction['before_balance'],
                    'newBalance' => $transaction['after_balance'],
                    'reason' => $reason,
                    'adjustedOn' => $transaction['created_at'],
                    'adjustedBy' => $transaction['from_id'],
                    'show_flag' => false,
                ));

                $commonSettings = (array)$this->group_level->getCashbackSettings();
                $withdraw_condition = (isset($commonSettings['withdraw_condition'])) ? (float)$commonSettings['withdraw_condition'] : 0;
                $withdraw_condition_amount = $amount * $withdraw_condition;

                $this->CI->withdraw_condition->createWithdrawConditionForCashback($transaction['id'], $withdraw_condition_amount, $withdraw_condition, $player_id, $amount);

                $adminUserId = $user_id;
                $this->CI->transaction_notes->add($reason, $adminUserId, $adjustment_type, $transaction['id']);

                $result['success'] = $this->CI->endTransWithSucc();
            }
        } finally {
            // release it
            $this->CI->releasePlayerBalanceResource($player_id, $lockedKey);
        }

        if (!$result['success']) {
            $note = $note . ' failed';
        }

        $this->CI->utils->recordAction("player_cashback_library/autoAddCashbackToBalance", 'Adjust Balance', $note);

        return $result;
    }

    public function delete_cashback_by_player_date($player_id, $date){
        $this->CI->load->model(array('player_model'));

        $tableName = 'total_cashback_player_game_daily';
        $this->CI->player_model->db->where('total_date', $date); // ex: 2022-10-02
        $this->CI->player_model->db->where('player_id', $player_id);
        $this->CI->player_model->db->where('paid_flag', Player_model::DB_FALSE); // DB_FALSE
        $rlt = $this->CI->player_model->runRealDelete($tableName); // return bool

        $this->CI->utils->debug_log('OGP-27832.271.delete total_cashback_player_game_daily sql.last_query', $this->CI->player_model->db->last_query() );
        return $rlt;
    }

}