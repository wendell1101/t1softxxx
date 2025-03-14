<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Dispatch_withdrawal_definition
 *
 */
class Dispatch_withdrawal_definition extends BaseModel {
    protected $tableName = 'dispatch_withdrawal_definition';
    // protected $tableName_included_game_type = 'dispatch_withdrawal_conditions_included_game_description';

    public function __construct() {
        parent::__construct();
    }


    /**
     * Add a record
     *
     * @param array $params the fields of the table,"dispatch_withdrawal_definition".
     * @return void
     */
    public function add($params) {

        $nowForMysql = $this->utils->getNowForMysql();
        $data['created_at'] = $nowForMysql;
        $data['updated_at'] = $nowForMysql;
        $data = array_merge($data, $params);
        return $this->insertRow($data);
    } // EOF add

    /**
     * Update record by id
     *
     * @param integer $id
     * @param array $data The fields for update.
     * @return boolean|integer The affected_rows.
     */
    public function update($id, $data = array() ) {
        $nowForMysql = $this->utils->getNowForMysql();
        $data['updated_at'] = $nowForMysql;
        return $this->updateRow($id, $data);
    }// EOF update

    /**
     * Delete a record by id(P.K.)
     *
     * @param integer $id The id field.
     * @return boolean Return true means delete the record completed else false means failed.
     */
    public function delete($id){
        $this->db->where('id', $id);
        return $this->runRealDelete($this->tableName);
    }// EOF delete

    /**
     * Get a record by id(P.K.)
     *
     * @param integer $id The id field.
     * @return array The field-value of the record.
     */
    public function getDetailById($id) {
        $this->db->select('*')
                ->from($this->tableName)
                ->where('id', $id);

        $result = $this->runOneRowArray();

        return $result;
    }// EOF getDetailById


    /**
     * Get the rows for withdrawal_risk_api_module::processPreChecker()
     *
     * @param boolean $getEnabledOnly filter the inactived rows.
     * @param string $order_by_field The field name.
     * @param string $order_by order asc/desc.
     * @return array The rows.
     */
    public function getDetailList($getEnabledOnly = true, $order_by_field='dispatch_order', $order_by ='asc'){
        $this->db->select('*')
                ->from($this->tableName);
        if($getEnabledOnly){
            $this->db->where('status', BaseModel::DB_TRUE);
        }

        $this->db->order_by($order_by_field, $order_by);
        $result = $this->runMultipleRowArray();

        return $result;
    }// EOF getDetailList

    /**
     * Generate custom_stage_N array from CustomWithdrawalProcessingStage
     *
     * @param array (point) $getEnabledValue The Enabled value in the array by custom_stage_N key.
     * @return array The key-value format array, custom_stage_N-"the lang".
     */
    public function utils_buildCustomStageArray(&$getEnabledArray = []){
        // for handle CUSTOM_WITHDRAWAL_PROCESSING_STAGES.
        $customStage = [];
        $stages = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
        foreach ($stages as $key => $value) {
            if(is_array($value) && array_key_exists('enabled', $value)){
				if(	is_int($key) ){
                    $keyStr = 'custom_stage_'.($key+1);
                    $customStage[$keyStr] = $value['name'];
                    $getEnabledArray[$keyStr] = $value['enabled'];
                }
            }
        }
        return $customStage;
    } // EOF utils_buildCustomStageArray

	/**
	 * The list for sorted by dispatch_order ASC for Withdrawal Risk Process List.
	 *
	 * @param array $request $_REQUEST
	 * @return array
	 */
    public function dispatchOrderlist($request){
        // previewDefinitionOrderModalBody

        // $this->load->model(['wallet_model']);
        $is_export = false;
        # START DEFINE COLUMNS #################################################################################################################################################
        $i = 0;
        $columns = [];

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'id',
            'select' => $this->tableName. '.id',
            'name' => lang('ID'),
            'formatter' => function($d, $row) use ($is_export){
                return $d;
            },
        );
        $columns[] = array(
            'dt' => $i++,
            'alias' => 'name',
            'select' => $this->tableName. '.name',
            'name' => lang('cms.title'),
            'formatter' => function($d, $row) use ($is_export){
                $formatted = $d;
                return $formatted;
            },
        );
        $columns[] = array(
            'dt' => $i++,
            'alias' => 'dispatch_order',
            'select' => $this->tableName. '.dispatch_order',
            'name' => lang('Dispatch Order'),
            'formatter' => function($d, $row) use ($is_export){
                return $d;
            },
        );
        # END DEFINE COLUMNS #################################################################################################################################################

        $table = $this->tableName;
        $joins = array();

        # START PROCESS SEARCH FORM #################################################################################################################################################
        $where = array();
        $values = array();

        $this->load->library('data_tables');
        $input = $this->data_tables->extra_search($request);
        if (isset($input['status'])) {
            $where[] = $this->tableName. ".status = ?";
            $values[] = $input['status'];
        }
        # END PROCESS SEARCH FORM #################################################################################################################################################

        // if($is_export){
        //     $this->data_tables->options['is_export']=true;
        //             // $this->data_tables->options['only_sql']=true;
        //     if(empty($csv_filename)){
        //         $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
        //     }
        //     $this->data_tables->options['csv_filename']=$csv_filename;
        // }
        $external_order = [['column' => 2, 'dir' => 'asc']];
        // $external_order = [];
        // $external_order = 'dispatch_order ASC';
        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, [], [], true, $external_order);

        // if($is_export){
        //             //drop result if export
        //     return $csv_filename;
        // }


        return $result;
    } // EOF dispatchOrderlist

    /**
     * The dispatch_withdrawal_definition List
     *
     * @param array $request
     * @param array $permissions
     * @param boolean $is_export
     *
     * @example The example source-code for source of $permissions and $is_export.
     * <code>
     *  $is_export = true;
     *  $permissions=$this->getContactPermissions();
     *
     *  $funcName='player_analysis_report';
     *  $callerType=Queue_result::CALLER_TYPE_SYSTEM;
     *  $caller=0;
     *  $state='';
     *
     *  $extra_params=[self::HTTP_REQEUST_PARAM, $permissions, $is_export];
     *
     *  $rlt=$this->exportData($funcName, $extra_params, $callerType, $caller, $state);
     *
     * </code>
     * @return void
     */
    public function dataTablesList($request, $permissions, $is_export = false){
        $this->load->model(['wallet_model','operatorglobalsettings']);

        // $eligible2dwStatus4Options = [];
        // $eligible2dwStatus4Options[] =  Wallet_model::PENDING_REVIEW_STATUS; // Pending Review
        // $eligible2dwStatus4Options[] =  Wallet_model::PAY_PROC_STATUS; // payProc
        // $eligible2dwStatus4Options[] =  Wallet_model::DECLINED_STATUS;
        // $eligible2dwStatus4Options[] =  Wallet_model::PENDING_REVIEW_CUSTOM_STATUS; // Pending VIP

		// for handle CUSTOM_WITHDRAWAL_PROCESSING_STAGES.
        // $customStage = [];
        // $stages = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
        // foreach ($stages as $key => $value) {
        //     if(is_array($value) && array_key_exists('enabled', $value)){
		// 		if(	is_int($key)
		// 			&& ! empty($value['enabled'])
		// 		){
        //             $customStage['custom_stage_'.($key+1)] = $value['name'];
        //         }
        //     }
        // }

        $customStageEnabledList = [];
        $customStage = $this->utils_buildCustomStageArray($customStageEnabledList);
        $customWithdrawalProcessingStage = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
        # START DEFINE COLUMNS #################################################################################################################################################
        $i = 0;
        $columns = [];

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'id',
            'select' => $this->tableName. '.id',
            'name' => lang('ID'),
            'formatter' => function($d, $row) use ($is_export){
                return $d;
            },
        );
        $columns[] = array(
            'dt' => $i++,
            'alias' => 'dispatch_order',
            'select' => $this->tableName. '.dispatch_order',
            'name' => lang('Dispatch Order'),
            'formatter' => function($d, $row) use ($is_export){
                return $d;
            },
        );
        $columns[] = array(
            'dt' => $i++,
            'alias' => 'name',
            'select' => $this->tableName. '.name',
            'name' => lang('cms.title'),
            'formatter' => function($d, $row) use ($is_export){

                if($is_export) {
                    $formatted = $d;
                }else{
                    $id = $row['id'];
                    $uri = site_url('payment_management/dispatch_withdrawal_condition_list/'.$id);
                    $html = <<<EOF
<a href="$uri" class="viewDispatchWithdrawalConditions">
    $d
</a>
EOF;
                    $formatted = $html;
                }


                return $formatted;
            },
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'eligible2dwStatus',
            'select' => $this->tableName. '.eligible2dwStatus',
            'name' => lang('eligible2dwStatus'),
            'formatter' => function($d, $row) use ($is_export, $customStage, $customStageEnabledList, $customWithdrawalProcessingStage){
                $formatted = $d;
                switch($d){

                    case Wallet_model::PENDING_REVIEW_STATUS:
                    case Wallet_model::PAY_PROC_STATUS:
                    case Wallet_model::DECLINED_STATUS:
                    case Wallet_model::PAID_STATUS:
                        $formatted = $this->wallet_model->getStageName($d);
                    break;
                    case Wallet_model::PENDING_REVIEW_CUSTOM_STATUS:
                        if($this->utils->getConfig('enable_pending_review_custom')){
                            if($customWithdrawalProcessingStage['pendingCustom']['enabled']) {
                                $formatted = $this->wallet_model->getStageName($d);
                            }else{
                                $formatted = lang('N/A');
                            }
                        }else{
                            $formatted = lang('role.nopermission');
                        }
                    break;

					default:
						// handle CUSTOM_WITHDRAWAL_PROCESSING_STAGES
                        if( ! empty($customStage[$d]) ){
                            if($customStageEnabledList[$d]){
                                $formatted = $customStage[$d];
                            }else{
                                $formatted = lang('N/A');
                            }
                        }
                    break;
                }
                return $formatted;
            },
        );
        $columns[] = array(
            // 'dt' => $i++,
            'alias' => 'eligible2external_system_id',
            'select' => $this->tableName. '.eligible2external_system_id',
            'name' => lang('eligible2external_system_id'),
            'formatter' => function($d, $row) use ($is_export){
                return $d;
            },
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'status',
            'select' => $this->tableName. '.status',
            'name' => lang('lang.status'),
            'formatter' => function($d, $row) use ($is_export){
                if($d == self::DB_TRUE){
                    $formatted = lang('lang.activate');
                }else{
                    $formatted = lang('lang.deactivate');
                }
                return $formatted;
            },
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'created_at',
            'select' => $this->tableName. '.created_at',
            'name' => lang('Created At'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'updated_at',
            'select' => $this->tableName. '.updated_at',
            'name' => lang('Updated At'),
        );

        $columns[] = array(
            'dt' => $i++,
            'alias' => 'action',
            'select' => $this->tableName. '.id',
            'name' => lang('lang.action'),
            'formatter' => function($d, $row) use ($is_export){
                $formatted = '';
                $lang4delete = lang('cms.delete');
                $lang4edit = lang('cms.edit');
                $id = $d;
                // @todo OGP-18088, HTML script should moved into the js file.
                $html = <<<EOF
<span tabindex="0" data-toggle="tooltip" title="$lang4edit"  data-placement="top">
    <button type="button"class="btn btn-default btn-xs editWithdrawalDefinition" >
        <span class="glyphicon glyphicon-edit" data-detail-id="$id" >
        </span>
    </button>
</span>
<span tabindex="0" data-toggle="tooltip" title="$lang4delete"  data-placement="top">
    <button type="button" class="btn btn-default btn-xs deleteWithdrawalDefinition">
        <span class="glyphicon glyphicon-trash" data-detail-id="$id" >
        </span>
    </button>
</span>
EOF;
                $formatted .= $html;
                return $formatted;
            },
        );
        # END DEFINE COLUMNS #################################################################################################################################################

        $table = $this->tableName;
        $joins = array();

        # START PROCESS SEARCH FORM #################################################################################################################################################
        $where = array();
        $values = array();

        $this->load->library('data_tables');
        $input = $this->data_tables->extra_search($request);
        if (isset($input['name'])) {
            $where[] = $this->tableName. ".name like ?";
            $values[] = $input['name'];
        }
        # END PROCESS SEARCH FORM #################################################################################################################################################

        if($is_export){
            $this->data_tables->options['is_export']=true;
                    // $this->data_tables->options['only_sql']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
        }
        // $external_order = [['column' => 2, 'dir' => 'asc']];
        $external_order = [];
        $result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, [], [], true, $external_order);

        if($is_export){
                    //drop result if export
            return $csv_filename;
        }


        return $result;
    } // EOF list



    /**
     * Get the Required Bet of the player and current Withdrawal Amount Rate
     *
     * @param float|integer $currentWithdrawalAmount The current Withdrawal Amount
     * @param integer $playerId The Field, "player.playerId".
     * @param string $from_datetime The query begin datetime, ex: "2020-07-30 12:23:34".
     * @param string $to_datetime The query end datetime, ex: "2020-07-30 12:23:34".
     * @param (point)array $resultDetail
     * @return void
     */
    public function getRequiredBetAndWithdrawalRateByPlayerId( $currentWithdrawalAmount // # 1
        , $playerId // # 2
        , $from_datetime = null // # 3
        , $to_datetime = null // # 4
        , &$resultDetail = [] // # 5
    ){
        $this->load->model(['withdraw_condition']);

        $betAndWithdrawalRate = 0;
        // $from_datetime = null;

        // reference to "Total Required Bet" in "Withdrawal Condition" of player detail.
        $computation = $this->withdraw_condition->computePlayerWithdrawalConditions($playerId);
        $betAmount = $computation['totalRequiredBet'];

        $withdrawalAmount = $currentWithdrawalAmount;
        // $withdrawalAmount = $this->getTotalWithdrawalAmountFromLastWithdrawDatetime($playerId, $from_datetime);

        if( ! empty($withdrawalAmount) ){
            $betAndWithdrawalRate = ($betAmount / doubleval($withdrawalAmount) )* 100; // unit: %
        }

        $resultDetail['from_datetime'] = $from_datetime;
        $resultDetail['to_datetime'] = $to_datetime;
        $resultDetail['betAmount'] = $betAmount;
        // $resultDetail['gameDescriptionIdList'] = $gameDescriptionIdList;
        $resultDetail['withdrawalAmount'] = $withdrawalAmount;
        $resultDetail['betAndWithdrawalRate'] = $betAndWithdrawalRate;
        // $resultDetail['calcAvailableBetOnly'] = $calcAvailableBetOnly;
        // $this->utils->debug_log('OGP-18088,$betAmount:', $betAmount, 'withdrawalAmount:', $withdrawalAmount, '$betAndWithdrawalRate', $betAndWithdrawalRate);
        return $betAndWithdrawalRate;
    } // EOF getRequiredBetAndWithdrawalRateByPlayerId

    /**
     * Get counter/data of AG hedging data by the player
     *
     * @param integer $playerId The Field, "player.playerId".
     * @param string $from_datetime [so far, not used] The query begin datetime, ex: "2020-07-30 12:23:34".
     * @param string $to_datetime [so far, not used] The query end datetime, ex: "2020-07-30 12:23:34".
     * @param boolean $is_count If true means return rows count.
     * @param (point)array $resultDetail For log result.
     * @return boolean
     */
    public function hasHedgingDataByPlayerId($playerId, $from_datetime, $to_datetime, $is_count = true, &$resultDetail = []){
        $this->load->model(['hedging_total_detail_player']);
        $return = null;

        $select = 'id, player_id, table_id';
        // during in 3 year
        $where = <<<EOF
player_id = "$playerId" and created_at > DATE_SUB(NOW(),INTERVAL 3 YEAR)
EOF;
        $rows = $this->hedging_total_detail_player->getDetailListByQuery($select, $where);
        $sql = $this->hedging_total_detail_player->db->last_query();
		$this->utils->debug_log('OGP-18088, hasHedgingDataByPlayerId.$sql', $sql);

        $counter = count($rows);
        // $resultDetail['from_datetime'] = $from_datetime;
        // $resultDetail['to_datetime'] = $to_datetime;
        $resultDetail['hedging_in_ag_counter'] = $counter;
        $resultDetail['formula'] = 'Shoule be Not-Found any Hedging Data in AG games, If met the condition.';
        if($is_count){
            return $counter;
        }else{
            return $rows;
        }

    }

    /**
     * Get Canceled Game data counter
     *
     * 无注单被取消
     *
     * @param integer $playerId The Field, "player.playerId".
     * @param string $from_datetime The query begin datetime, ex: "2020-07-30 12:23:34".
     * @param string $to_datetime The query end datetime, ex: "2020-07-30 12:23:34".
     * @param array $gameDescIdList limit in the game description.
     * @param boolean $is_count If true means return rows count.
     * @param array $resultDetail For log result.
     * @return boolean|integer The data counter.
     */
    public function hasCanceledGameDataInUnsettled($playerId, $from_datetime, $to_datetime, $gameDescIdList,$is_count, &$resultDetail = []){
        $this->load->model(['game_logs']);
        $return = null;
        $counter = $this->game_logs->getCanceledGameDataInUnsettled($playerId, $from_datetime, $to_datetime, $gameDescIdList, $is_count);

        $resultDetail['from_datetime'] = $from_datetime;
        $resultDetail['to_datetime'] = $to_datetime;
        $resultDetail['canceledGameDataCounter'] = $counter;
        $resultDetail['formula'] = 'Shoule be Not-Found any Canceled Game Data in game log,If met the condition.';
        return $counter;
    }// EOF hasCanceledGameDataInUnsettled


    /**
     * Get Bet And Withdrawal Rate By PlayerId
     *
     * 1. Get the Bet amount during last Withdrawal to now.
     * 2. The rate = the bet amount / current withdrawal amount
     *
     * requirement, BetAndWithdrawalRate
     * Bet Amount ≥ 1 times of Withdrawal Amount
     * Bet : Withdrawal ≥ 1
     * 流水要求≥100%
     *
     * @param float|integer $currentWithdrawalAmount The current Withdrawal Amount
     * @param integer $playerId The Field, "player.playerId".
     * @param array $gameDescriptionIdList The included Game Description, "game_description.id" condition.
     *
     * @param point $resultDetail The array for more detail during execute the condition.
     * @return float Bet / Withdrawal Rate, for the condition, Bet Amount ≥ 1 times of Withdrawal Amount.
     */
    public function getBetAndWithdrawalRateByPlayerId( $currentWithdrawalAmount // # 1
                                                , $playerId // # 2
                                                , $gameDescriptionIdList = [] // # 3
                                                , $from_datetime = null // # 4
                                                , $to_datetime = null // # 5
                                                , $calcAvailableBetOnly = false // # 6
                                                , &$resultDetail = [] // # 7
    ){
        $betAndWithdrawalRate = 0;
        // $from_datetime = null;

        $summary = $this->getTotalsAmountByGameDescriptionIdListFromLastWithdrawDatetime( $playerId // # 1
                                                                                        , $gameDescriptionIdList // # 2
                                                                                        , $from_datetime // # 3
                                                                                        , $to_datetime // # 4
                                                                                        , $calcAvailableBetOnly // # 5
                                                                                    );
        $betAmount = $summary[0];

        $withdrawalAmount = $currentWithdrawalAmount;
        // $withdrawalAmount = $this->getTotalWithdrawalAmountFromLastWithdrawDatetime($playerId, $from_datetime);

        if( ! empty($withdrawalAmount) ){
            $betAndWithdrawalRate = ($betAmount / doubleval($withdrawalAmount) )* 100; // unit: %
        }

        $resultDetail['from_datetime'] = $from_datetime;
        $resultDetail['to_datetime'] = $to_datetime;
        $resultDetail['betAmount'] = $betAmount;
        $resultDetail['gameDescriptionIdList'] = $gameDescriptionIdList;
        $resultDetail['withdrawalAmount'] = $withdrawalAmount;
        $resultDetail['betAndWithdrawalRate'] = $betAndWithdrawalRate;
        $resultDetail['calcAvailableBetOnly'] = $calcAvailableBetOnly;
// $this->utils->debug_log('OGP-18088,$betAmount:', $betAmount, 'withdrawalAmount:', $withdrawalAmount, '$betAndWithdrawalRate', $betAndWithdrawalRate);
        return $betAndWithdrawalRate;
    } // EOF getBetAndWithdrawalRateByPlayerId

    /**
     * Get the bet amount by the player in the games during times.
     *
     * @param integer $playerId The field, "player.playerId".
     * @param string $from_datetime The query begin datetime, ex: "2020-07-30 12:23:34".
     * @param string $to_datetime The query end datetime, ex: "2020-07-30 12:23:34".
     * @param array $gameDescriptionIdList The allow games,"game_description.id"
     * @param boolean $calcAvailableBetOnly If true then to calc real_betting_amount > 0 data only.
     * @param array $resultDetail For log result.
     * @return integer|float The bet amount.
     */
    public function getBetAmountByPlayerId($playerId // # 1
                                , $gameDescriptionIdList = [] // # 2
                                , $from_datetime = null // # 3
                                , $to_datetime = null // # 4
                                , $calcAvailableBetOnly = false // # 5
                                , &$resultDetail = [] // # 6
    ){
        $summary = $this->getTotalsAmountByGameDescriptionIdListFromLastWithdrawDatetime( $playerId
                                                                                        , $gameDescriptionIdList
                                                                                        , $from_datetime
                                                                                        , $to_datetime
                                                                                        , $calcAvailableBetOnly
                                                                                    );

        $betAmount = $summary[0];

        $resultDetail['betAmount'] = $betAmount;
        $resultDetail['from_datetime'] = $from_datetime;
        $resultDetail['to_datetime'] = $to_datetime;
        $resultDetail['calcAvailableBetOnly'] = $calcAvailableBetOnly;
        $resultDetail['gameDescriptionIdList'] = $gameDescriptionIdList;

        return $betAmount;
    } // EOF getBetAmountByPlayerId

    /**
     * Get the Result Amount and Deposit Amount for Rate.
     *
     * @param integer $playerId The field, player.id .
     * @param array $gameDescriptionIdList
     * @param string $from_datetime The query begin datetime, ex: "2020-07-30 12:23:34".
     * @param string $to_datetime The query end datetime, ex: "2020-07-30 12:23:34".
     * @param integer $hasPlayerPromoIdCondition
     * @param boolean $calcAvailableBetOnly If true then to calc real_betting_amount > 0 data only.
     * @param array $resultDetail For log result.
     * @return float Result / Deposit Rate, for the condition, Win Amount  ≤ 2X Deposit Amount (Win : Deposit ≥ 2).
     */
    public function getResultAndDepositRateByPlayerId(  $playerId // # 1
                                            , $gameDescriptionIdList = [] // # 2
                                            , $from_datetime = null // # 3
                                            , $to_datetime = null // # 4
                                            , $hasPlayerPromoIdCondition = -1 // # 5
                                            , $calcAvailableBetOnly = false // # 6
                                            , &$resultDetail = [] // # 7
    ){
        $this->load->model(array('total_player_game_hour'));
        $winAndDepositRate = 0;

        $gamePlatformId=null;
        $gameDescriptionId = $gameDescriptionIdList;
        list($totalBet, $totalResult, $totalWin, $totalLoss)
            = $this->total_player_game_hour
                ->getTotalAmountFromHourlyReportByPlayerAndDateTime(  $playerId // # 1
                                                                        , $from_datetime // # 2
                                                                        , $to_datetime // # 3
                                                                        , $gamePlatformId // # 4
                                                                        , $gameDescriptionId // # 5
                                                                        , $calcAvailableBetOnly // # 6
                                                                    );


        $depositAmount = $this->getTotalDepositAmountFromLastWithdrawDatetime($playerId, $from_datetime, $to_datetime, $hasPlayerPromoIdCondition);

        if( empty($totalResult) ){
            $totalResult = 0;
        }

        if( empty($depositAmount) ){
            $depositAmount = 0;
        }

        if( ! empty($depositAmount) ){
            // After QA review, need remove x100 for %
            $winAndDepositRate = $totalResult / $depositAmount;  // unit: %
        }


        // $resultDetail['totalBet'] = $totalBet;
        // $resultDetail['resultAmount'] = $totalResult;
        // $resultDetail['totalWin'] = $totalWin;
        // $resultDetail['totalLoss'] = $totalLoss;

        $resultDetail['resultAmount'] = $totalResult;
        $resultDetail['depositAmount'] = $depositAmount;
        $resultDetail['from_datetime'] = $from_datetime;
        $resultDetail['to_datetime'] = $to_datetime;
        $resultDetail['hasPlayerPromoIdCondition'] = $hasPlayerPromoIdCondition;
        $resultDetail['winAndDepositRate'] = $winAndDepositRate;
        $resultDetail['calcAvailableBetOnly'] = $calcAvailableBetOnly;
        $resultDetail['gameDescriptionIdList'] = $gameDescriptionIdList;

        return $winAndDepositRate;
    } // EOF getResultAndDepositRateByPlayerId



    /**
     * Get Win / Deposit Rate (Win : Deposit)
     *
     * requirement, WinAndDepositRate
     *
     * for the requirement condition, Win Amount  ≤ 2X Deposit Amount
     *
     * @param integer $playerId The Field, "player.playerId".
     * @param array $gameDescriptionIdList The included Game Description list, for "game_description.id" condition.
     *
     * @param point $resultDetail The array for more detail during execute the condition.
     * @return float Win / Deposit Rate, for the condition, Win Amount  ≤ 2X Deposit Amount (Win : Deposit ≥ 2).
     */
    public function getWinAndDepositRateByPlayerId(  $playerId // # 1
                                            , $gameDescriptionIdList = [] // # 2
                                            , $from_datetime = null // # 3
                                            , $to_datetime = null // # 4
                                            , $hasPlayerPromoIdCondition = -1 // # 5
                                            , $calcAvailableBetOnly = false // # 6
                                            , &$resultDetail = [] // # 7
    ){
        $winAndDepositRate = 0;
        // $from_datetime = null;
        $summary = $this->getTotalsAmountByGameDescriptionIdListFromLastWithdrawDatetime( $playerId
                                                                                        , $gameDescriptionIdList
                                                                                        , $from_datetime
                                                                                        , $to_datetime
                                                                                        , $calcAvailableBetOnly
                                                                                    );
        $winAmount = $summary[1];

        $depositAmount = $this->getTotalDepositAmountFromLastWithdrawDatetime($playerId, $from_datetime, $to_datetime, $hasPlayerPromoIdCondition);

        if( ! empty($depositAmount) ){
            // After QA review, need remove x100 for %
            $winAndDepositRate = $winAmount / $depositAmount;  // unit: %
        }

        $resultDetail['winAmount'] = $winAmount;
        $resultDetail['depositAmount'] = $depositAmount;
        $resultDetail['from_datetime'] = $from_datetime;
        $resultDetail['to_datetime'] = $to_datetime;
        $resultDetail['hasPlayerPromoIdCondition'] = $hasPlayerPromoIdCondition;
        $resultDetail['winAndDepositRate'] = $winAndDepositRate;
        $resultDetail['calcAvailableBetOnly'] = $calcAvailableBetOnly;
        $resultDetail['gameDescriptionIdList'] = $gameDescriptionIdList;

        return $winAndDepositRate;
    }// EOF getWinAndDepositRateByPlayerId


    /**
     * Get the Game Revenue Percentage for the condrtion of pre-checker.
     *
     * @param integer $playerId The field,"player.playerId".
     * @param string $from_datetime The query begin datetime, ex: "2020-07-30 12:23:34".
     * @param string $to_datetime The query end datetime, ex: "2020-07-30 12:23:34".
     * @param array $gameDescriptionIdList The allow games,"game_description.id"
     * @param boolean $calcAvailableBetOnly If true then to calc real_betting_amount > 0 data only.
     * @param array $resultDetail To get more detail of the return.
     * @return float $gameRevenuePercentage
     */
    public function getGameRevenuePercentageByPlayerId( $playerId // # 1
                                                , $from_datetime // # 2
                                                , $to_datetime // # 3
                                                , $gameDescriptionIdList = [] // # 4
                                                , $calcAvailableBetOnly = false // # 5
                                                , &$resultDetail // # 6
    ){
        $this->load->model(array('total_player_game_hour'));

        $gameRevenuePercentage = 0;
        // list($from_datetime, $to_datetime) = $this->getThePlayerLastWithdrawlDatetimeToNow($playerId);


        $fromDateMinuteStr = $this->utils->formatDateMinuteForMysql(new DateTime($from_datetime));
        $toDateMinuteStr = $this->utils->formatDateMinuteForMysql(new DateTime($to_datetime));
        $total_player_game_table='total_player_game_minute' ;
        $where_date_field = 'date_minute';
        $gamePlatformId=null;
        $gameDescriptionId = $gameDescriptionIdList;
        list($totalBet, $totalResult, $totalWin, $totalLoss) = $this->total_player_game_hour
                                                                    ->getTotalAmountFromHourlyReportByPlayerAndDateTime(  $playerId // # 1
                                                                                                                            , $fromDateMinuteStr // , $from_datetime // # 2
                                                                                                                            , $toDateMinuteStr // , $to_datetime // # 3
                                                                                                                            , $gamePlatformId // # 4
                                                                                                                            , $gameDescriptionId // # 5
                                                                                                                            , $calcAvailableBetOnly // # 6
                                                                                                                            , $total_player_game_table // # 7
																		                                                    , $where_date_field // # 8
                                                                                                                        );

        if( ! empty($totalBet) ){
            // After QA review, need absolute() and x100 for %
            $gameRevenuePercentage = $totalResult / $totalBet; // disable abs() from OGP-20724
            $gameRevenuePercentage = $gameRevenuePercentage *100 ; // unit: %
        }

        $resultDetail['from_datetime'] = $from_datetime;
        $resultDetail['to_datetime'] = $to_datetime;
        $resultDetail['totalResult'] = $totalResult;
        $resultDetail['totalBet'] = $totalBet;
        $resultDetail['gameRevenuePercentage'] = $gameRevenuePercentage;
        $resultDetail['calcAvailableBetOnly'] = $calcAvailableBetOnly;
        return $gameRevenuePercentage;

        // GameRevenuePercentage // 'percent' => $row['bet_sum'] ? (($row['gain_loss_sum'] / $row['bet_sum']) * 100) : 0,
        //
        // getTotalAmountFromHourlyReportByPlayerAndDateTime
        // total_bet, total_result
        //
        // getTotalAmountFromHourlyReportByPlayerAndDateTime

    }// EOF getGameRevenuePercentageByPlayerId

    /**
     * Get Bet/Wins/Loss Amount By Game Description Id List From Last Withdraw Datetime
     *
     * requirement, WinAndDepositRate/BetAndWithdrawalRate
     *
     * @param integer $playerId The Field, "player.playerId".
     * @param array $gameDescriptionIdList The included Game Description, "game_description.id" condition.
     * @param string $from_datetime The default is the last Withdraw Datetime, or register date. The begin time of calc Deposit Amount subtotal.
     * @return void
     */
    public function getTotalsAmountByGameDescriptionIdListFromLastWithdrawDatetime(  $playerId // # 1
                                                                            , $gameDescriptionIdList = [] // # 2
                                                                            , $from_datetime = null // # 3
                                                                            , $to_datetime = null // # 4
                                                                            , $calcAvailableBetOnly = false // # 5
    ){

        $this->load->model(array('total_player_game_hour'));

        if( empty($from_datetime) || empty($to_datetime) ){
			list($_from_datetime, $_to_datetime) = $this->getThePlayerLastWithdrawlDatetimeToNow($playerId);
		}
		if( empty($from_datetime) ){
			$from_datetime = $_from_datetime;
		}
		if( empty($to_datetime) ){
			$to_datetime = $_to_datetime;
		}

        $playerIds = [$playerId];
        $dateTimeFrom = $from_datetime;
        $dateTimeTo = $to_datetime;
        $gamePlatformId = null;
        $gameDescriptionId = $gameDescriptionIdList; // [ 1,2,3,4,5,6 ];// @todo 需要 getIncludedGameTypeByDefinitionId()
        list($totalBets, $totalWins, $totalLoss) = $this->total_player_game_hour->getTotalBetsWinsLossByPlayers(  $playerIds // # 1
                                                                                            , $dateTimeFrom // # 2
                                                                                            , $dateTimeTo // # 3
                                                                                            , $gamePlatformId // # 4
                                                                                            , $gameDescriptionId // # 5
                                                                                            , $calcAvailableBetOnly // # 6
                                                                                        );

        $summary = [];
        $summary[] = $totalBets; // # 0
        $summary[] = $totalWins; // # 1
        $summary[] = $totalLoss; // # 2
        return $summary;
    }// EOF getTotalsAmountByGameDescriptionIdListFromLastWithdrawDatetime


    /**
     * Get The Last Withdraw Datetime to Now by The Player.
     *
     * @param integer $playerId player.playerId
     * @param string $from_datetime The Last Withdrawl Datetime, begin datetime.ex: "2020-07-12 09:02:02"
     * @param string $to_datetime The Now Datetime, end datetime.ex: "2020-07-13 14:22:32"
     * @return array The first element is begin datetime, and the secend element is end datetime.
     */
    public function getThePlayerLastWithdrawlDatetimeToNow($playerId, $from_datetime = null, $to_datetime = 'now'){
        $this->load->model(array('player_model', 'transactions'));

        $to = new DateTime($to_datetime);
        $now_datetime = $this->utils->formatDateTimeForMysql($to);
        $to_datetime = $now_datetime;

        if( empty($from_datetime) ){
            $from_datetime = $this->transactions->getLastWithdrawDatetime($playerId);
            if( empty($from_datetime) ){
                $from_datetime = $this->player_model->getPlayerRegisterDate($playerId);
                // $from_datetime = $from_datetime4withoutLastWithdraw;
            }
        }
        return [$from_datetime, $to_datetime];
    } // EOF _getThePlayerLastWithdrawDatetimeToNow


    /**
     * Get Withdrawal Count from the last Withdraw Datetime
     *
     * requirement, todayWithdrawalCount,
     *
     * @param integer $playerId The player.playerId
     * @param string $from_datetime The default is the last Withdraw Datetime, or register date. The begin time of calc Deposit Amount subtotal.
     * @param integer $hasPlayerPromoId The criteria of query, the modes,
     * - If -1 mean IGNORE the Deposit with promo condition.
     * - If 0 mean query the Deposit WITHOUT promo condition.
     * - If 1 mean query the Deposit WITH promo condition.
     * @param point $resultDetail The array for more detail during execute the condition.
     *
     * @return integer The total Deposit Count.
     */
    public function getTotalWithdrawalCountFromLastWithdrawDatetime( $playerId // # 1
                                                            , $from_datetime = null // # 2
                                                            , $to_datetime = null // # 3
                                                            , $hasPlayerPromoIdCondition = -1 // # 4
                                                            , $transactionTypeCriteria = Transactions::WITHDRAWAL // # 5
                                                            , &$resultDetail // # 6
    ){
        $this->load->model(array('transactions'));

        if( empty($from_datetime) || empty($to_datetime) ){
            list($_from_datetime, $_to_datetime) = $this->getThePlayerLastWithdrawlDatetimeToNow($playerId, $from_datetime);
        }
        if( empty($from_datetime) ){
            $from_datetime = $_from_datetime;
// $this->utils->debug_log('OGP-19922.914.from_datetime', $from_datetime);
        }
        if( empty($to_datetime) ){
            $to_datetime = $_to_datetime;
        }
// $this->utils->debug_log('OGP-19922.918.from_datetime', $from_datetime);

        $criteria_1 = array(
            'to_id'            => $playerId,
            'to_type'          => Transactions::PLAYER,
            'transaction_type' => $transactionTypeCriteria,
            'status'           => Transactions::APPROVED,
            'created_at >='	   => $from_datetime,
            'created_at <='	   => $to_datetime,
        );
        $criteria_2 = [];

        switch($hasPlayerPromoIdCondition){
            default:
            case '-1': // IGNORE the Deposit with promo condition.
            break;

            case '0': // query the Deposit WITHOUT promo condition.
                $criteria_2 = ' player_promo_id IS NULL OR player_promo_id = 0 ';
                // $criteria_1['player_promo_id IS NULL'] ='';
            break;

            case '1': // the Deposit WITH promo condition.
                // player_promo_id is not null
                // and player_promo_id > 0
                $criteria_1['player_promo_id >'] = '0';
                $criteria_1['player_promo_id IS NOT NULL'] ='';
            break;
        }

        $transactionCount = $this->transactions->getTransactionCount($criteria_1, $criteria_2);

        $resultDetail['from_datetime'] = $from_datetime;
        $resultDetail['to_datetime'] = $to_datetime;
        $resultDetail['hasPlayerPromoIdCondition'] = $hasPlayerPromoIdCondition;
        $resultDetail['withdrawalCount'] = $transactionCount;
        return $transactionCount;
    } // EOF getTotalWithdrawalCountFromLastWithdrawDatetime

    /**
     * Get Deposit Count from the last Withdraw Datetime
     *
     * requirement, totalDepositCount
     *
     * @param integer $playerId The player.playerId
     * @param string $from_datetime The default is the last Withdraw Datetime, or register date. The begin time of calc Deposit Amount subtotal.
     * @param integer $hasPlayerPromoId The criteria of query, the modes,
     * - If -1 mean IGNORE the Deposit with promo condition.
     * - If 0 mean query the Deposit WITHOUT promo condition.
     * - If 1 mean query the Deposit WITH promo condition.
     * @param point $resultDetail The array for more detail during execute the condition.
     *
     * @return integer The total Deposit Count.
     */
    public function getTotalDepositCountFromLastWithdrawDatetime($playerId, $from_datetime = null, $to_datetime = null, $hasPlayerPromoIdCondition = -1, &$resultDetail = []){
        $this->load->model(array('transactions'));

        // list($from_datetime, $to_datetime) = $this->getThePlayerLastWithdrawlDatetimeToNow($playerId, $from_datetime);
        if( empty($from_datetime) || empty($to_datetime) ){
            list($_from_datetime, $_to_datetime) = $this->getThePlayerLastWithdrawlDatetimeToNow($playerId, $from_datetime);
        }
        if( empty($from_datetime) ){
            $from_datetime = $_from_datetime;
        }
        if( empty($to_datetime) ){
            $to_datetime = $_to_datetime;
        }


        $criteria_1 = array(
            'to_id'            => $playerId,
            'to_type'          => Transactions::PLAYER,
            'transaction_type' => Transactions::DEPOSIT,
            'status'           => Transactions::APPROVED,
            'created_at >='	   => $from_datetime,
            'created_at <='	   => $to_datetime,
        );
        $criteria_2 = [];

        switch($hasPlayerPromoIdCondition){
            default:
            case '-1': // IGNORE the Deposit with promo condition.
            break;

            case '0': // query the Deposit WITHOUT promo condition.
                $criteria_2 = ' player_promo_id IS NULL OR player_promo_id = 0 ';
                // $criteria_1['player_promo_id IS NULL'] ='';
            break;

            case '1': // the Deposit WITH promo condition.
                // player_promo_id is not null
                // and player_promo_id > 0
                $criteria_1['player_promo_id >'] = '0';
                $criteria_2 = 'player_promo_id IS NOT NULL';
                // $criteria_1['player_promo_id IS NOT NULL'] = '';
            break;
        }

        $transactionCount = $this->transactions->getTransactionCount( $criteria_1, $criteria_2);

        $resultDetail['from_datetime'] = $from_datetime;
        $resultDetail['to_datetime'] = $to_datetime;
        $resultDetail['depositCount'] = $transactionCount;

        return $transactionCount;
    }// EOF getTotalDepositCountFromLastWithdrawDatetime


    /**
     * Get Withdrawal Amount subtotal from the last Withdraw Datetime
     *
     * requirement, BetAndWithdrawalRate
     *
     * @param integer $playerId The player.playerId
     * @param string $from_datetime The default is the last Withdraw Datetime, or register date. The begin time of calc Deposit Amount subtotal.
     * @return void
     */
    public function getTotalWithdrawalAmountFromLastWithdrawDatetime($playerId, $from_datetime = null, $to_datetime = null, &$resultDetail = []){
        $this->load->model(array('transactions'));

        // list($from_datetime, $to_datetime) = $this->getThePlayerLastWithdrawlDatetimeToNow($playerId, $from_datetime);
        if( empty($from_datetime) || empty($to_datetime) ){
            list($_from_datetime, $_to_datetime) = $this->getThePlayerLastWithdrawlDatetimeToNow($playerId, $from_datetime);
        }
        if( empty($from_datetime) ){
            $from_datetime = $_from_datetime;
        }
        if( empty($to_datetime) ){
            $to_datetime = $_to_datetime;
        }

        $isToday = false;
        $totalWithdrawalAmount = $this->transactions->getPlayerTotalWithdrawals($playerId, $isToday, $from_datetime, $to_datetime);
        return $totalWithdrawalAmount;
    }// EOF getTotalWithdrawalAmountFromLastWithdrawDatetime

    /**
     * query the Duplicate Username
     *
     * @param string $username The Usernameto find.
     * @param point $resultDetail The array for more detail during execute the condition.
     * - $resultDetail[count] the Duplicate First Name count.
     * - $resultDetail[isDuplicate] Is the First Name Duplicate?
     * - $resultDetail[firstName] the param, $firstName.
     * @return boolean $isDuplicate If true, that's means the first name is Duplicate.
     */
    public function isDuplicateAccount($username, &$resultDetail = null){
        $this->load->model(array('report_model'));
        $request['extra_search'][0]['name'] = 'by_username';
        $request['extra_search'][0]['value'] = $username;
        $isDuplicate = false; // default
        $result = $this->report_model->duplicateAccountTotal($request);
        $count = $result['recordsTotal'];
        if( $count > 0){
            $isDuplicate = true;
        }
        $resultDetail['count'] = $count;
        $resultDetail['isDuplicate'] = $isDuplicate;
        $resultDetail['username'] = $username;
        return $isDuplicate;
    } // EOF isDuplicateAccount

    /**
     * query the Duplicate First Name
     *
     * @param string $firstName The first name to find.
     * @param point $resultDetail The array for more detail during execute the condition.
     * - $resultDetail[count] the Duplicate First Name count.
     * - $resultDetail[isDuplicate] Is the First Name Duplicate?
     * - $resultDetail[firstName] the param, $firstName.
     * @return boolean $isDuplicate If true, that's means the first name is Duplicate.
     */
    public function isDuplicateFirstName($firstName, &$resultDetail = null){
        $this->load->model(array('player_model'));
        $isDuplicate = false;
        $offset = 0;
        $limit = -1;
        $in = '';
        $sort_by = '';
        $search = [];
        $search['search_by'] = null;
        $search['search_reg_date'] = null;
        $search['firstName'] = $firstName;
        $search['wallet_order'] = Player_model::PLAYERACCOUNT_MAINWALLET;
        $rows = $this->player_model->searchAllPlayer($search, $sort_by, $in, $limit, $offset);
        $count = count($rows);
        if( $count > 1){ // filter more tags in a player.
            $rows_unique = array_unique(array_column($rows, 'playerId'));
            $rows = $rows_unique;
            $count = count($rows);
        }

        if( $count > 1){
            $isDuplicate = true;
        }
        $resultDetail['count'] = $count;
        $resultDetail['isDuplicate'] = $isDuplicate;
        $resultDetail['firstName'] = $firstName;
        // $resultDetail['sql'] = $this->player_model->db->last_query();
        return $isDuplicate;
    } // EOF isDuplicateFirstName

    /**
     * query the Duplicate Last Name
     *
     * @param string $lastName The last name to find.
     * @param point $resultDetail The array for more detail during execute the condition.
     * - $resultDetail[count] the Duplicate Last Name count.
     * - $resultDetail[isDuplicate] Is the Last Name Duplicate?
     * - $resultDetail[lastName] the param, $lastName.
     * @return boolean $isDuplicate If true, that's means the last name is Duplicate.
     */
    public function isDuplicateLastName($lastName, &$resultDetail = null){
        $this->load->model(array('player_model'));
        $isDuplicate = false;
        $offset = 0;
        $limit = -1;
        $in = '';
        $sort_by = '';
        $search = [];
        $search['search_by'] = null;
        $search['search_reg_date'] = null;
        $search['lastName'] = $lastName;
        $search['wallet_order'] = Player_model::PLAYERACCOUNT_MAINWALLET;
        $rows = $this->player_model->searchAllPlayer($search, $sort_by, $in, $limit, $offset);
        $count = count($rows);
        if( $count > 1){ // filter more tags in a player.
            $rows_unique = array_unique(array_column($rows, 'playerId'));
            $rows = $rows_unique;
            $count = count($rows);
        }

        if( $count > 1){
            $isDuplicate = true;
        }
        $resultDetail['count'] = $count;
        $resultDetail['isDuplicate'] = $isDuplicate;
        $resultDetail['lastName'] = $lastName;
        // $resultDetail['sql'] = $this->player_model->db->last_query();
        return $isDuplicate;
    } // EOF isDuplicateLastName

    /**
     * Get Deposit Amount subtotal from the last Withdraw Datetime
     *
     * requirement, WinAndDepositRate
     *
     * @param integer $playerId The player.playerId
     * @param string $from_datetime The default is the last Withdraw Datetime, or register date. The begin time of calc Deposit Amount subtotal.
     * @return integer The total Deposit Amount
     */
    public function getTotalDepositAmountFromLastWithdrawDatetime($playerId, $from_datetime = null, $to_datetime = null, $hasPlayerPromoIdCondition = -1){

        $this->load->model(array('transactions'));

        // list($from_datetime, $to_datetime) = $this->getThePlayerLastWithdrawlDatetimeToNow($playerId, $from_datetime);
        if( empty($from_datetime) || empty($to_datetime) ){
            list($_from_datetime, $_to_datetime) = $this->getThePlayerLastWithdrawlDatetimeToNow($playerId, $from_datetime);
        }
        if( empty($from_datetime) ){
            $from_datetime = $_from_datetime;
        }
        if( empty($to_datetime) ){
            $to_datetime = $_to_datetime;
        }

        $totalDepositAmount = $this->transactions->getPlayerTotalDepositsByDatetime($playerId, $from_datetime, $to_datetime, $hasPlayerPromoIdCondition);
        return $totalDepositAmount;
    } // EOF getTotalDepositAmountFromLastWithdrawDatetime

    /**
     * Get the actived dwStatus list for setup options in definition list.
     *
     * @param boolean $doCheckPermissions To do Check Permissions, for display the Options in UI OR execute pre-checker via the queue server.
     * @return array $eligible2dwStatus4Options The format,
     * - $eligible2dwStatus4Options[string $dwStatusKeyStr] string The lang string.
     */
    public function getEligible2dwStatus4OptionList($doCheckPermissions = false){
        $this->load->model(['wallet_model', 'operatorglobalsettings']);
        $setting = $this->operatorglobalsettings->getCustomWithdrawalProcessingStage();
		$eligible2dwStatus4Options = [];
		$eligible2dwStatus4Options[Wallet_model::PAY_PROC_STATUS] = lang('Manual Payment'); // Manual Payment
		// $eligible2dwStatus4Options[Wallet_model::PAY_PROC_STATUS] = lang('PaymentWithAPI'); // 3rd Payment - should with external_system_id, and some params,
		// - transaction_fee: 0
		// - ignoreWithdrawalAmountLimit: 0
		// - ignoreWithdrawalTimesLimit: 0
		/// ignore, no requirement,"Paid".
		// $eligible2dwStatus4Options[Wallet_model::PAID_STATUS] = lang('Paid'); // Pending Review
		        //         case Wallet_model::PAID_STATUS :
                // case Wallet_model::APPROVED_STATUS :

        $permission4view_pending_custom_stage = true; // for ignore
        $permission4view_pending_review_stage = true; // for ignore
        if( $doCheckPermissions ){
            $this->load->library(['permissions']);
            $permission4view_pending_custom_stage = $this->permissions->checkPermissions('view_pending_custom_stage');
            $permission4view_pending_review_stage = $this->permissions->checkPermissions('view_pending_review_stage');
        }
        if( $this->utils->getConfig('enable_pending_review_custom')
            && $permission4view_pending_custom_stage
        ){
			if($setting['pendingCustom']['enabled']) {
				$eligible2dwStatus4Options[Wallet_model::PENDING_REVIEW_CUSTOM_STATUS] =  lang('Pending VIP'); // Pending VIP
			}
		}
        if( $this->utils->isEnabledFeature("enable_withdrawal_pending_review")
            && $permission4view_pending_review_stage
        ){
			$eligible2dwStatus4Options[Wallet_model::PENDING_REVIEW_STATUS] = lang('pay.penreview'); // Pending Review
		}
        // $eligible2dwStatus4Options[Wallet_model::PAY_PROC_STATUS] =  Wallet_model::PAY_PROC_STATUS; // payProc
		for($i = 0; $i < CUSTOM_WITHDRAWAL_PROCESSING_STAGES; $i++){
			if ( ! empty($setting[$i]['enabled'])) {
				if ( ! empty($setting[$i]['name'])) {
					$eligible2dwStatus4Options['custom_stage_'. ($i+1)] = $setting[$i]['name'];
				}
			}
		}
        $eligible2dwStatus4Options[Wallet_model::DECLINED_STATUS] =  lang('Declined');
        return $eligible2dwStatus4Options;
    } // EOF getEligible2dwStatus4OptionList

}

///END OF FILE////////