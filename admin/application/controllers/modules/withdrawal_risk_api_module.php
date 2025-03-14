<?php

/**
 *
 * api for transfer balance
 */
trait withdrawal_risk_api_module {

    // ==== dispatch_withdrawal_definition series

    /**
     * The Sorted Definition List by dispatch_order.
     *
     * @return string The json for $.DataTable().
     */
    public function dispatchOrderlist(){
        $this->load->model(['dispatch_withdrawal_definition']);
        $request = $this->input->post();
        $result = $this->dispatch_withdrawal_definition->dispatchOrderlist($request);
        return $this->returnJsonResult($result);
    }// EOF dispatchOrderlist

    /**
     * List for payment_management::withdrawal_risk_process_list().
     *
     * @return string The json for $.DataTable().
     */
    public function dispatch_withdrawal_definition_list(){
        $this->load->model(['dispatch_withdrawal_definition']);

        $request = $this->input->post();
        $is_export = false;
        $permissions=$this->getContactPermissions();

        $result = $this->dispatch_withdrawal_definition->dataTablesList($request, $permissions, $is_export);
        $this->returnJsonResult($result);
    }// EOF dispatch_withdrawal_definition_list

    /**
     * For Add,Update and Get a dispatch_withdrawal_definition record.
     *
     * @param string $id The field,"dispatch_withdrawal_definition.id".
     * @return string The json for ajax handle.
     */
    public function dispatch_withdrawal_definition_detail($id = ''){
        $this->load->model(['dispatch_withdrawal_definition','wallet_model']);
//         $request = $this->input->get_post();
// $this->utils->debug_log('23request',$request);
        $method = $this->input->server('REQUEST_METHOD');// $_SERVER['REQUEST_METHOD']
$this->utils->debug_log('OGP-18088,23method',$method);
        if( $method == 'POST' ){
            $request = $this->input->post();
$this->utils->debug_log('OGP-18088,23request',$request);
            if( empty($id) ){// add
                $result = $this->dispatch_withdrawal_definition->add($request);
            }else{ // update
                $result = $this->dispatch_withdrawal_definition->update($id, $request);
            }
        }else{ // GET
            if( ! empty($id) ){ // get a detail
                $result = $this->dispatch_withdrawal_definition->getDetailById($id);

                $customStage = $this->dispatch_withdrawal_definition->utils_buildCustomStageArray();
                if( ! empty( $customStage[$result['eligible2dwStatus']] ) ){
                    $lang4eligible2dwStatus = $customStage[$result['eligible2dwStatus']];
                }else{
                    $lang4eligible2dwStatus = $this->wallet_model->getStageName($result['eligible2dwStatus']);
                }
                $result['lang4eligible2dwStatus'] = $lang4eligible2dwStatus;

            }else{ // get list
                // ignore, implemented in dispatch_withdrawal_definition_list().
            }
        }
        return $this->returnJsonResult($result);

    }// EOF dispatch_withdrawal_definition_detail

    /**
     * Delete dispatch_withdrawal_definition and the related dispatch_withdrawal_conditions data.
     *
     * @param integer $id The field, "dispatch_withdrawal_definition.id".
     * @return void Output Json String. The json format,
     * - $json[definition][id] integer The field, "dispatch_withdrawal_definition.id".
     * - $json[definition][isDelete] boolean If true means delete complete.
     * - $json[conditions][n][id] integer The field, "dispatch_withdrawal_conditions.id".
     * - $json[conditions][n][isDelete] boolean If true means delete complete.
     */
    public function delete_dispatch_withdrawal_definition($id){
        $this->load->model(['dispatch_withdrawal_definition', 'dispatch_withdrawal_conditions']);

        $result = [];
        $result['definition'] = [];
        $result['definition']['id'] = $id;
        $result['definition']['isDelete'] = $this->dispatch_withdrawal_definition->delete($id);

        // delete conditions of the deleted definition.
        if($result){
            $conditionList = $this->dispatch_withdrawal_conditions->getDetailListByDefinitionId($id);
            $result['conditions'] = [];
            if( ! empty($conditionList) ){
                foreach( $conditionList as $indexNumber => $conditionDetail){
                    $result['conditions'][$indexNumber]['id'] = $conditionDetail['id'];
                    $result['conditions'][$indexNumber]['isDelete'] = $this->dispatch_withdrawal_conditions->delete($conditionDetail['id']);
                }
            }
        }

        return $this->returnJsonResult($result);
    } // EOF delete_dispatch_withdrawal_definition

    // ==== dispatch_withdrawal_results series

    /**
     * List for payment_management::dispatch_withdrawal_condition_list().
     *
     * @requires MUST input the definition_id, "dispatch_withdrawal_definition.id" via POST, for dispatch_withdrawal_conditions::list().
     * @return string The json for $.DataTable().
     */
    public function dispatch_withdrawal_results_list($transCode){
        $this->load->model(['dispatch_withdrawal_results']);
        // $request = array_merge($this->input->get(), $this->input->post());
        $request = $this->input->post();
        $is_export = false;
        $permissions=$this->getContactPermissions();
        $result = $this->dispatch_withdrawal_results->dataTablesList($request, $permissions, $is_export);
        $this->returnJsonResult($result);
    } // EOF dispatch_withdrawal_results_list

    /**
     * get the results Count By transactionCode
     *
     * @param string $transCode The F.K to "walletaccount.transactionCode".
     * @return string The json string.
     */
    public function getResultsByTransCode($transCode){
        $this->load->library(['auto_risk_dispatch_withdrawal_lib']);
        $result = $this->auto_risk_dispatch_withdrawal_lib->getResultsByTransCode($transCode);
        return $this->returnJsonResult($result);
    } // EOF getResultsByTransCode

    // ==== dispatch_withdrawal_condition series

    /**
     * List for payment_management::dispatch_withdrawal_condition_list().
     *
     * @requires MUST input the definition_id, "dispatch_withdrawal_definition.id" via POST, for dispatch_withdrawal_conditions::list().
     * @return string The json for $.DataTable().
     */
    public function dispatch_withdrawal_condition_list(){ // definition_id
        $this->load->model(['dispatch_withdrawal_conditions']);
        // $request = array_merge($this->input->get(), $this->input->post());
        $request = $this->input->post();
        $is_export = false;
        $permissions=$this->getContactPermissions();
$this->utils->debug_log('OGP-18088,58request',$request);
        $result = $this->dispatch_withdrawal_conditions->dataTablesList($request, $permissions, $is_export);
        $this->returnJsonResult($result);
    } // EOF dispatch_withdrawal_condition_list


    /**
     * For Add,Update and Get a dispatch_withdrawal_conditions record.
     *
     * @param string $id The field,"dispatch_withdrawal_conditions.id".
     * @return string The json for ajax handle.
     *
     */
    public function dispatch_withdrawal_condition_detail($id = ''){
        $this->load->model(['dispatch_withdrawal_conditions']);

        $method = $this->input->server('REQUEST_METHOD');// $_SERVER['REQUEST_METHOD']

        if( $method == 'POST' ){
            $request = $this->input->post();
$this->utils->debug_log('OGP-18088,72request',$request);
            if( empty($id) ){// add
                $result = $this->add_dispatch_withdrawal_condition_detail($request);
            }else{ // update
                // $result = $this->dispatch_withdrawal_conditions->update($id, $request);
                $result = $this->update_dispatch_withdrawal_condition_detail($id, $request);
            }
        }else{ // GET
            if( ! empty($id) ){ // get a detail
                $pickFromExtra = true;
                $result = $this->dispatch_withdrawal_conditions->getDetailById($id, $pickFromExtra);
            }else{ // get list
                // ignore, implemented in dispatch_withdrawal_condition_list().
            }
        }
        return $this->returnJsonResult($result);
    } // EOF dispatch_withdrawal_condition_detail

    /**
     * Update a dispatch_withdrawal_conditions and Store selected_game_tree array
     *
     * @param integer $condition_id The field,"dispatch_withdrawal_conditions".
     * @param array $request Will to update the field-value array.
     * @return array $results The result array after executed and foramt,
     * - $results[update] boolean|integer The affected_rows.
     * - $results['doStoreSelected_game_tree'] array Pls referrence to the return of self::doStoreSelected_game_tree().
     */
    public function update_dispatch_withdrawal_condition_detail($condition_id, $request){
        $this->load->model(['dispatch_withdrawal_conditions', 'dispatch_withdrawal_conditions_included_game_description']);
        $params = $request;
        $results = [];

        // selected_game_tree => game_description_id list
        // clear selected_game_tree for convertion to game_description_id list
        $params['selected_game_tree'] = null;
        unset($params['selected_game_tree']);
        // parse selected_game_tree for game_description_id list
        $selected_game_tree = [];
        if( ! empty($request['selected_game_tree']) ){
            $selected_game_tree = explode(',', $request['selected_game_tree']);
        }

        // Patch for the Severity: Warning, Invalid arguments passed /home/vagrant/Code/og/admin/application/controllers/modules/withdrawal_risk_api_module.php 226
        $params['excludedPlayerTag_list'] = ''; // default
        if(! empty($request['excludedPlayerTag_list']) ){
            $params['excludedPlayerTag_list'] = implode(',',$request['excludedPlayerTag_list']); //array
        }

        $result4update = $this->dispatch_withdrawal_conditions->update($condition_id, $params);
        $results['update'] = $result4update;
$this->utils->debug_log('OGP-18088,109result4update',$result4update);

        $doStoreSelected_game_tree = false;
        if( ! empty($condition_id)
            && ! empty($selected_game_tree)
        ){ // will  store the selected_game_tree
            $doStoreSelected_game_tree = true;
        }
        if($doStoreSelected_game_tree){
            // $condition_id, $selected_game_tree
            $result4doStoreSelected_game_tree = $this->doStoreSelected_game_tree($condition_id, $selected_game_tree);
            $results['doStoreSelected_game_tree'] = $result4doStoreSelected_game_tree;
        }

        return $results;
    } // EOF update_dispatch_withdrawal_condition_detail

    /**
     * Delete a dispatch_withdrawal_conditions by "dispatch_withdrawal_conditions.id".
     *
     * @param integer $id The field, "dispatch_withdrawal_conditions.id".
     * @return string The json string for ajax handle. more detail pls reference to the return of dispatch_withdrawal_conditions::delete().
     */
    public function delete_dispatch_withdrawal_condition($id){
        $this->load->model(['dispatch_withdrawal_conditions']);
        $result = $this->dispatch_withdrawal_conditions->delete($id);
        return $this->returnJsonResult($result);
    } // delete_dispatch_withdrawal_condition

    /**
     * Do Store selected_game_tree array
     * The Store steps,
     * - Clear dispatch_withdrawal_conditions_included_game_description data by dispatch_withdrawal_conditions.id
     * - Insert $selected_game_tree array into dispatch_withdrawal_conditions_included_game_description
     *
     * @param integer $condition_id the field, "dispatch_withdrawal_conditions.id"
     * @param array $selected_game_tree The GameString array,
     * - $selected_game_tree[0] = gp_XX_gt_XX_gd_XX
     * - $selected_game_tree[1] = gp_XX
     * - $selected_game_tree[2] = gp_XX_gt_XX
     * - $selected_game_tree[n] = gp_XX_gt_XX_gd_XX
     * @return array The result info,
     * - $return['clearByConditionsId'] Boolean If false, there may be no data before execute the action.
     * - $return['addedCount'] Affected count. If its empty, there may be no data to setup.
     */
    public function doStoreSelected_game_tree($condition_id, $selected_game_tree){
        $this->load->model(['dispatch_withdrawal_conditions', 'dispatch_withdrawal_conditions_included_game_description']);
        $return = [];
        // for reset by condition_id
        $deleted = $this->dispatch_withdrawal_conditions_included_game_description->deleteByConditionsId($condition_id);
        $return['clearByConditionsId'] = $deleted;
$this->utils->debug_log('OGP-18088,109deleted',$deleted);
        // for add by condition_id
        $addedCounter = 0;
        foreach($selected_game_tree as $key => $curr ){
            $params = [];
            $params['dispatch_withdrawal_conditions_id'] = $condition_id;
            $game_description_id = $this->dispatch_withdrawal_conditions_included_game_description->parseGameStrOfJstree($curr)[2];
            if( ! empty($game_description_id) ){
                $params['game_description_id'] =  $game_description_id;
                $added = $this->dispatch_withdrawal_conditions_included_game_description->add($params);
                if( ! empty($added) ){
                    $addedCounter++;
                }
$this->utils->debug_log('OGP-18088,109added',$added);
            }else{ // ignore empty game_description_id
                continue;
            }
        }// EOF foreach($selected_game_tree as $key => $curr )
        $return['addedCount'] = $addedCounter;

        return $return;
    } // EOF doStoreSelected_game_tree

    /**
     * Add a dispatch_withdrawal_condition via array.
     *
     * @param array $request The field-value array for add.
     * @return integer The added condition_id,"dispatch_withdrawal_conditions.id". If empty means failed.
     */
    public function add_dispatch_withdrawal_condition_detail($request){
        $this->load->model(['dispatch_withdrawal_conditions', 'dispatch_withdrawal_conditions_included_game_description']);
        $params = $request;

        // selected_game_tree => game_description_id list
        // clear selected_game_tree for convertion to game_description_id list
        $params['selected_game_tree'] = null;
        unset($params['selected_game_tree']);
        // parse selected_game_tree for game_description_id list
        $selected_game_tree = [];
        if( ! empty($request['selected_game_tree']) ){
            $selected_game_tree = explode(',', $request['selected_game_tree']);
        }

        $params['excludedPlayerTag_list'] = '';
        if( ! empty( $request['excludedPlayerTag_list'] ) ){
            $params['excludedPlayerTag_list'] = implode(',',$request['excludedPlayerTag_list']); //array
        }

        $condition_id = $this->dispatch_withdrawal_conditions->add($params);

        $doStoreSelected_game_tree = false;
        if( ! empty($condition_id)
            && ! empty($selected_game_tree)
        ){ // will  store the selected_game_tree
            $doStoreSelected_game_tree = true;
        }

        if( $doStoreSelected_game_tree ){
            $result4doStoreSelected_game_tree = $this->doStoreSelected_game_tree($condition_id, $selected_game_tree);
$this->utils->debug_log('OGP-18088,109result4doStoreSelected_game_tree',$result4doStoreSelected_game_tree);
        }

$this->utils->debug_log('OGP-18088,109condition_id',$condition_id);


        // // update to dispatch_withdrawal_definition.extra
        // if( ! empty($condition_id) ){
        //     // look for dispatch_withdrawal_definition.id
        //     $dispatch_withdrawal_condition_detail = $this->dispatch_withdrawal_conditions->getDetailById($condition_id);
        //     $dispatch_withdrawal_definition_id = $dispatch_withdrawal_condition_detail['dispatch_withdrawal_definition_id'];
        //     $dispatch_withdrawal_condition_list = $this->dispatch_withdrawal_conditions->getDetailListByDefinitionId($dispatch_withdrawal_definition_id);
        // }

        return $condition_id;
    } // EOF add_dispatch_withdrawal_condition_detail


    /**
     * Add the results for trace via SBE
     *
     * @param array $request The field-value array for add into the table,"dispatch_withdrawal_results".
     * @return integer|boolean $result_id The added id, "dispatch_withdrawal_results.id".
     */
    public function add_dispatch_withdrawal_result($request){
        $this->load->model(['dispatch_withdrawal_results']);
        $params = $request;
        $result_id = $this->dispatch_withdrawal_results->add($params);
        return $result_id;
    } // EOF add_dispatch_withdrawal_result

    public function get_game_tree_by_condition($id = ''){
        $this->load->model(['dispatch_withdrawal_conditions']);
        $result = $this->dispatch_withdrawal_conditions->get_game_tree_by_condition($id);
        $this->returnJsonResult($result, false, '*', false);
    } // EOF get_game_tree_by_condition

    /**
     * Process the pre-checker while withdrawal order request.
     *
     * @param integer $walletAccountId The field, "walletaccount.walletAccountId".
     * @return void
     */
    public function processPreCheckerWithTransCode($transCode){
        $this->load->model(['dispatch_withdrawal_results']);

        $walletAccountId = null; // for get the walletAccountId in isExistsInByTransCode().
        $dwDateTime = null; // for get the dwDateTime in isExistsInByTransCode().
        $result = $this->dispatch_withdrawal_results->isExistsInByTransCode($transCode, $walletAccountId, $dwDateTime);
        $this->processPreChecker($walletAccountId); // test

        return $this->getResultsByTransCode($transCode);
    } // EOF processPreCheckerWithTransCode

    /**
     * Process the pre-checker while withdrawal order request.
     *
     * @param integer $walletAccountId The field, "walletaccount.walletAccountId".
     * @return void
     */
    public function processPreChecker($walletAccountId){

        // require_once dirname(__FILE__) . '/../libraries/payment/abstract_payment_api.php';
		// return $this->loadDynamicClass($classType, $classKey, $enabledCache);
        $this->load->model(['dispatch_withdrawal_definition','wallet_model', 'dispatch_withdrawal_conditions', 'player_model']);

        $this->customizedDefinitionResultList = [];
        $this->preChecker = [];
        // $transactionCode = $walletAccountData['transactionCode']; // ex, W063808502389
        $transactionCode = $this->wallet_model->getRequestSecureId($walletAccountId);
        $walletAccountDeatil = $this->wallet_model->getWalletAccountByTransactionCode($transactionCode);
        $playerId = $this->wallet_model->getPlayerIdFromWalletAccount($walletAccountDeatil['walletAccountId']) ;
        $playerDetail = $this->player_model->getPlayerArrayById($playerId);
        $_playerDetails = $this->player_model->getPlayerDetails($playerId);
        if( ! empty($_playerDetails) ){ // for noDuplicateFirstNames and noDuplicateLastNames
            $playerDetail = array_merge($playerDetail, $_playerDetails[0]);
        }
        $getEnabledOnly = true;
        $order_by_field='dispatch_order';
        $order_by ='asc';
        $definitionDetailList = $this->dispatch_withdrawal_definition->getDetailList($getEnabledOnly, $order_by_field, $order_by);

        $this->preChecker['playerDetail'] = $playerDetail;
        $this->preChecker['walletAccountDeatil'] = $walletAccountDeatil;

        /// disable for OGP-19258
        // $isMetWithdrawalRequirements = true;
        // $this->preChecker['isMetWithdrawalRequirements']['resultDetail'] = null;
        // #check  withdrawal conditions
        // if ($this->utils->isEnabledFeature('check_withdrawal_conditions')|| true) {
        //     $un_finished = $this->withdraw_condition->getPlayerUnfinishedWithdrawCondition($playerId);
        //     if(FALSE !== $un_finished){
        //         //un_finished_withdrawal
        //         $isMetWithdrawalRequirements = false;
        //     }
        //     $this->preChecker['isMetWithdrawalRequirements']['resultDetail']['un_finished'] = $un_finished;
        // }
        // #check  withdrawal conditions -- for each
        // if ($this->utils->isEnabledFeature('check_withdrawal_conditions_foreach') || true) {
        //     $withdraw_data = $this->withdraw_condition->getPlayerUnfinishedWithdrawConditionForeach($playerId);
        //     if( FALSE !== $withdraw_data){
        //         //un_finished
        //         $isMetWithdrawalRequirements = false;
        //     }
        //     $this->preChecker['isMetWithdrawalRequirements']['resultDetail']['withdraw_data'] = $withdraw_data;
        // }
        // ##check deposit conditions in withdrawal conditions -- for each
        // if($this->utils->isEnabledFeature('check_deposit_conditions_foreach_in_withdrawal_conditions')|| true){
        //     $un_finished_deposit = $this->withdraw_condition->getPlayerUnfinishedDepositConditionForeach($playerId);
        //     if(FALSE !== $un_finished_deposit){
        //         //un_finished_deposit
        //         $isMetWithdrawalRequirements = false;
        //     }
        //     $this->preChecker['isMetWithdrawalRequirements']['resultDetail']['un_finished_deposit'] = $un_finished_deposit;
        // }
        // $this->preChecker['isMetWithdrawalRequirements']['result'] = $isMetWithdrawalRequirements;

        if( ! empty($definitionDetailList) ){
$this->utils->debug_log('OGP-18088,234.walletAccountId', $walletAccountId);
            $customizedDefinitionList = [];
            foreach($definitionDetailList as $indexNumber => $definitionDetail){
                $definition_id = $definitionDetail['id'];
                $doCustomized = false;
                $customizedObj = $this->loadCustomizedDefinitionObject($definition_id);
                if( ! empty($customizedObj) ){ // loadCustomizedDefinitionObject success
                    $doCustomized = true;
                }

                if( $doCustomized ){ // common assign
                    $customizedResultInfo = [];
                    $customizedResultInfo['dispatch_order']= $definitionDetail['dispatch_order'];
                    $customizedResultInfo['definitionId']= $definitionDetail['id']; // for debug
                    $customizedResultInfo['definitionNamed']= $definitionDetail['name']; // for debug
                    $this->customizedDefinitionResultList[$indexNumber] = [];
                    // pre-loading
                    $customizedObj->playerId = $playerId;
                    $customizedObj->playerDetail = $playerDetail;
                    $customizedObj->walletAccountDeatil = $walletAccountDeatil;
                    $customizedObj->definitionDetail = $definitionDetail;
                    $getEnabledOnly = true;
                    $pickFromExtra = true;
                    $customizedObj->contdtionList = $this->dispatch_withdrawal_conditions->getDetailListByDefinitionId($definition_id, $getEnabledOnly, $pickFromExtra);
                    $customizedObj->init();

                    $this->utils->debug_log('OGP-18088,234.customizedObj', $customizedObj->getClassName() );
                } // EOF if( $doCustomized ){ // common assign

                $pushToNextStageParams = null; // default
                if( $doCustomized ){ // met and break
                    $doBreakWhileMet = true;
                    $resultBuiltIn = $customizedObj->runBuiltInPreChecker( $doBreakWhileMet ); // in Abstract_customized_definition
                    $result_PreChecker = $customizedObj->runPreChecker($resultBuiltIn);
                    $customizedResultInfo = array_merge($customizedResultInfo, $result_PreChecker );
                    $this->customizedDefinitionResultList[$indexNumber] = $customizedResultInfo;
$this->utils->debug_log('OGP-18088,323.customizedDefinitionResultList.result_PreChecker', $result_PreChecker);
$this->utils->debug_log('OGP-18088,323.customizedDefinitionResultList.indexNumber', $indexNumber, $this->customizedDefinitionResultList[$indexNumber]);
                    $pushToNextStageParams = $this->getNextStageByCustomizedDefinitionList([$customizedResultInfo]); // in Abstract_customized_definition for pre-check
                    if( ! empty($pushToNextStageParams['dwStatus']) ){ // The CustomizedDefinition has next dwStatus, 有下一步的狀態
                        $pushToNextStageByDefinitionId = $customizedObj->definitionDetail['id'];
                        break;
                    }
                } // EOF if( $doCustomized ){ // met and break

    //             if( $doCustomized // calc all definition , Not-Recommand by performance
    //                 && false
    //             ){
    //                 // $customizedResultInfo = [];
    //                 // $customizedResultInfo['dispatch_order']= $definitionDetail['dispatch_order'];
    //                 // $customizedResultInfo['definitionId']= $definitionDetail['id']; // for debug
    //                 // $customizedResultInfo['definitionNamed']= $definitionDetail['name'];
    //                 // $this->customizedDefinitionResultList[$indexNumber] = [];
    //                 // // pre-loading
    //                 // $customizedObj->playerId = $playerId;
    //                 // $customizedObj->playerDetail = $playerDetail;
    //                 // $customizedObj->walletAccountDeatil = $walletAccountDeatil;
    //                 // $customizedObj->definitionDetail = $definitionDetail;
    //                 // $customizedObj->contdtionList = $this->dispatch_withdrawal_conditions->getDetailListByDefinitionId($definition_id);
    //                 // $customizedObj->init();
    // // $this->utils->debug_log('OGP-18088,234.customizedObj', $customizedObj->getClassName() );
    // //                 $customizedObj->runBuiltInPreChecker(); // in Abstract_customized_definition
    //                 $result_PreChecker = $customizedObj->runPreChecker();
    //                 $customizedResultInfo = array_merge($customizedResultInfo, $result_PreChecker );
    //                 $this->customizedDefinitionResultList[$indexNumber] = $customizedResultInfo;
    // $this->utils->debug_log('OGP-18088,234.customizedDefinitionResultList.result_PreChecker', $result_PreChecker);
    // $this->utils->debug_log('OGP-18088,234.customizedDefinitionResultList.indexNumber', $this->customizedDefinitionResultList[$indexNumber]);
    //             } // EOF if( $doCustomized // calc all definition , Not-Recommand by performance

            } // EOF foreach($definitionDetailList ...
$this->utils->debug_log('OGP-18088,234.customizedDefinitionResultList', $this->customizedDefinitionResultList);
            // $pushToNextStageByDefinitionId = null;
            // $pushToNextStageParams = $this->getNextStageByCustomizedDefinitionList($this->customizedDefinitionResultList, $pushToNextStageByDefinitionId); // in Abstract_customized_definition
$this->utils->debug_log('OGP-18088,234.pushToNextStageParams', $pushToNextStageParams);
$this->utils->debug_log('OGP-18088,234.pushToNextStageByDefinitionId', empty($pushToNextStageByDefinitionId)? 0: $pushToNextStageByDefinitionId);


            $doUpdateResults4dwStatus = false;
            // @todo $pushToNextStageParams['dwStatus'] exists ?

            if( ! empty( $this->config->item('idle_sec_before_withdrawalARP_pushStage') ) ){
                $_idle_sec = $this->config->item('idle_sec_before_withdrawalARP_pushStage');
                $this->utils->debug_log('OGP-25163.583.will _idle_sec', $_idle_sec, 'walletAccountId', $walletAccountId);
                $this->utils->idleSec( $_idle_sec );
            }

            if( ! empty($pushToNextStageByDefinitionId) ) {
                // $this->updateResults4result_dwStatus($walletAccountId, $pushToNextStageByDefinitionId, $this->customizedDefinitionResultList);
                // To reload walletAccountDeatil[dwStatus] for check dwStatus value equire before.
                // $transactionCode = $this->wallet_model->getRequestSecureId($walletAccountId);
                $hasProcessed = null;
                $_walletAccountDeatil = $this->wallet_model->getWalletAccountByTransactionCode($transactionCode);
                $isIgnoreByDwStatus = !in_array($_walletAccountDeatil['dwStatus'], [Wallet_model::REQUEST_STATUS, Wallet_model::PENDING_REVIEW_STATUS]); // for 6.1
                if( $isIgnoreByDwStatus ){
                    $hasProcessed = true;
                }else if($_walletAccountDeatil['dwStatus'] == $this->preChecker['walletAccountDeatil']['dwStatus']){
                    $hasProcessed = false;
                }else{
                    // Someone has processed the withdrawal request.
                    $hasProcessed = true;
                }

                if( ! $hasProcessed ){
                    if( empty( $this->config->item('skip_withdrawalARP_pushStage') ) ){

                        $this->updateResults4dwStatus($walletAccountId, $pushToNextStageByDefinitionId, $pushToNextStageParams['dwStatus']);

                        $this->utils->debug_log('OGP-18088,will pushToNextStage().');
                        call_user_func_array([$this, 'pushToNextStage'], [$pushToNextStageParams]); // handle return of  customizedDefinition::runPreChecker().

                        $this->updateResults4after_status($walletAccountId, $pushToNextStageParams['byDefinitionId']);
                    }else{
                        // for Risk Process Results List
                        $result_dwStatus = $this->get_result_dwStatus_json( Dispatch_withdrawal_results::RESULT_DW_STATUS_CODE_DWSTATUS_SKIP_BY_CONFIG // code:259
                                            , 'Skip pushToNextStage() by config.');
                        $this->updateResults4dwStatus($walletAccountId, $pushToNextStageByDefinitionId, $result_dwStatus);
                        $this->utils->debug_log('OGP-18088,'. $result_dwStatus. '.');
                    } // EOF if( empty( $this->config->item('skip_withdrawalARP_pushStage') ) ){...
                }else{
                    // for Risk Process Results List
                    $result_dwStatus = $this->get_result_dwStatus_json( Dispatch_withdrawal_results::RESULT_DW_STATUS_CODE_DWSTATUS_HAS_PROCESSED // code:258
                                        , 'Someone has processed the withdrawal request, skip pushToNextStage().' );
                    if( $isIgnoreByDwStatus ){
                        $result_dwStatus = $this->get_result_dwStatus_json( Dispatch_withdrawal_results::RESULT_DW_STATUS_CODE_DISALLOW_DWSTATUS // code:257
                                        , 'The withdrawal request(walletAccountId=%s) has the disallow dwStatus, %s, skip pushToNextStage().'
                                        , [ $walletAccountId, $_walletAccountDeatil['dwStatus'] ] );
                    }
                    $this->updateResults4dwStatus($walletAccountId, $pushToNextStageByDefinitionId, $result_dwStatus);
                    $this->utils->debug_log('OGP-21225,'. $result_dwStatus. '.');
                } // EOF if( ! $hasProcessed ){...
            } // EOF if( ! empty($pushToNextStageByDefinitionId) ) {...

        } // EOF if( ! empty($definitionDetailList) )
    } // EOF processPreChecker


    public function get_result_dwStatus_json($result_dw_status_code, $msg, $msg_extra_array = []){
        $_result_dwStatus = [];
        $_result_dwStatus['code'] = $result_dw_status_code;
        $_result_dwStatus['msg'] = $msg;
        if( ! empty($msg_extra_array) ){
            $_result_dwStatus['msg'] = vsprintf($msg, $msg_extra_array);
        }
        return json_encode($_result_dwStatus);
    } // EOF get_result_dwStatus_json

    /**
     * update the result_dwStatus by each customized definition.
     *
     * @param integer $walletAccountId The field,"walletaccount.walletAccountId".
     * @param integer $definitionId
     * @param array $customizedDefinitionResultList
     * @return void
     */
    public function updateResults4result_dwStatus($walletAccountId, $definitionId, $customizedDefinitionResultList){
        $this->load->model(['dispatch_withdrawal_results']);
$this->utils->debug_log('OGP-18088,updateResults4result_dwStatus');
        if( ! empty($customizedDefinitionResultList) ){
            foreach($customizedDefinitionResultList as $indexNumber => $customizedDefinitionResult){
                // $definitionId = $customizedDefinitionResult['definitionId'];
                $data = [];
                $data['result_dw_status'] = $customizedDefinitionResult['dwStatus'];
                $this->dispatch_withdrawal_results->updateByDefinitionIdAndWalletAccountId($walletAccountId, $definitionId, $data);
            }
        }
    }// EOF updateResults4result_dwStatus



    public function updateResults4dwStatus($walletAccountId, $definitionId, $dwStatus){
        $this->load->model(['dispatch_withdrawal_results']);

        $data = [];
        $data['result_dw_status'] = $dwStatus;
        return $this->dispatch_withdrawal_results->updateByDefinitionIdAndWalletAccountId($walletAccountId, $definitionId, $data);

    }// EOF updateResults4result_dwStatus


    /**
     * Reload walletaccount and update to the field "dispatch_withdrawal_results.after_status".
     *
     * @param integer $walletAccountId The field,"walletaccount.walletAccountId".
     * @param integer $definitionId The field,"dispatch_withdrawal_definition.id" that will push the order to next stage.
     * @return boolean|integer
     */
    public function updateResults4after_status($walletAccountId, $definitionId){
        $this->load->model(['wallet_model', 'dispatch_withdrawal_results']);

        $wallet_account = $this->wallet_model->getWalletAccountObject($walletAccountId);

        // $transactionCode = $this->wallet_model->getRequestSecureId($walletAccountId);
        // $walletAccountDeatil = $this->wallet_model->getWalletAccountByTransactionCode($transactionCode);
        $data = [];
        $data['after_status'] = $wallet_account['dwStatus'];
        return $this->dispatch_withdrawal_results->updateByDefinitionIdAndWalletAccountId($walletAccountId, $definitionId, $data);
    }// EOF updateResults4after_status

    /**
     * Push Withdrawal Order to next stage (update walletaccount.dwStatus and other actions)
     *
     *
     * @param array $push2dwStatusInfo The array for referenced the next stage.
     *
     * @return void Just to call pushToNextStageXXX() and pushToSpecStage(). The withdrawal order, "walletaccount.walletAccountId" will referenced in that.
     */
    public function pushToNextStage($push2dwStatusInfo){
        $this->load->model(['wallet_model']);
        $targetStage = '';
        if( ! empty($push2dwStatusInfo['dwStatus']) ){
            $targetStage = $push2dwStatusInfo['dwStatus'];
        }

$this->utils->debug_log('OGP-18088, pushToNextStage.$push2dwStatusInfo', $push2dwStatusInfo);
$this->utils->debug_log('OGP-18088, pushToNextStage.$targetStage', $targetStage);
        switch ($targetStage){

            // The following functions and current function diff.
            // public function payProcWithdrawal($adminUserId, $walletAccountId, $apiId = -1, $reason = null, $transaction_fee = null, $showNotesFlag = null) {
            // public function paidWithdrawal($adminUserId, $walletAccountId, $reason = null,
            // set_withdrawal_request_to_paid
            case Wallet_model::PAY_PROC_STATUS: // for Manual Payment
                $this->pushToNextStage4ManualPayment();
            break;

            case Wallet_model::PAID_STATUS:
                /// @todo OGP-18088 To dev. psuh Withdrawal order to paid.
                // Is need todo?
            break;

            case Wallet_model::PENDING_REVIEW_STATUS:
                $this->pushToSpecStage(Wallet_model::PENDING_REVIEW_STATUS);
            break;

            case Wallet_model::DECLINED_STATUS:
                $this->pushToNextStage4Declined();
            break;

            case Wallet_model::PENDING_REVIEW_CUSTOM_STATUS:
$this->utils->debug_log('OGP-18088, pushToNextStage.$targetStage', $targetStage);
                // $this->pushToNextStage4CustomPendingReview();
                $this->pushToSpecStage(Wallet_model::PENDING_REVIEW_CUSTOM_STATUS);
            break;

            default: // CUSTOM_WITHDRAWAL_PROCESSING_STAGES / No-action
                $prefix = 'custom_stage_';
                if( strpos($targetStage, $prefix) !== false){ // CUSTOM_WITHDRAWAL_PROCESSING_STAGES
                    $customStageNo = str_replace($prefix, '', $targetStage);
$this->utils->debug_log('OGP-18088, pushToNextStage.$customStageNo', $customStageNo);
                    $customStageNo--; // CS0 means "custom stage 1"
                    $this->pushToNextStage4CustomStages($customStageNo);
                }else{
                    // No action
                }
            break;

            /// ======
            // case'PayProcWithSithdrawAPI':
            //     $dwStatus='PayProc';
            //     $withdrawExternalSystemId = '9994';
            //     $CustomStagesNo = '-1';
            // break;
            //
            // case'CS2':
            //     $dwStatus='CS';
            //     $withdrawExternalSystemId = '-1';
            //     $CustomStagesNo = '2';
            // break;
            //
            // case'ManualPayment':
            //     $dwStatus='ManualPayment'; // PayProc?
            //     $withdrawExternalSystemId = '-1';
            //     $CustomStagesNo = '-1';
            // break;
            //
            // case'Declined':
            //     $dwStatus='Declined';
            //     $withdrawExternalSystemId = '-1';
            //     $CustomStagesNo = '-1';
            // break;
        }
    } // EOF pushToNextStage

    /**
     * Push the Withdrawal to Manual Payment
     *
     * @return void
     */
    public function pushToNextStage4ManualPayment(){
        $this->load->model(array('wallet_model'));
        // ALT = ACTION_LOG_TITLE
        $paymentManagementALT = 'Payment Management';
        $walletAccountDeatil = $this->preChecker['walletAccountDeatil'];
        $this->utils->debug_log('OGP-18088, pushToNextStage4ManualPayment.walletAccountDeatil', $walletAccountDeatil);

        // reference to, http://admin.og.local/payment_management/setWithdrawToPayProc/35/0/null
        $actionlogNotes = '[autoRiskProcess]Manual Payment : Set Withdraw To PayProc';
        $adminUserId = '1'; // apply adminuser_id, system
        $walletAccountId = $walletAccountDeatil['walletAccountId'];
        $playerId = $walletAccountDeatil['playerId'];
        $amount = $walletAccountDeatil['amount'];
        $adminUsername = $this->users->getUsernameById($adminUserId);
        $transaction_fee = 0; // @todo
        $ignoreWithdrawalAmountLimit = 0; // @todo
        $ignoreWithdrawalTimesLimit = 0; // @todo
        $withdrawApi = 0; // @todo external_system_id
        $data = null; // @todo external_system_id
        $wallet_account = $this->wallet_model->getWalletAccountObject($walletAccountId);

        $playerWithdrawalRule = $this->utils->getWithdrawMinMax($playerId);
        list($withdrawalProcessingCount, $processingAmount) = $this->wallet_model->countTodayProcessingWithdraw($playerId);
        list($withdrawalPaidCount, $paidAmount) = $this->transactions->count_today_withdraw($playerId);

        #check if withdrawal amount exceeds limit
        $isExceedsLimit = false;
        $amount_used = $processingAmount + $paidAmount;
        if ($amount + $amount_used > $playerWithdrawalRule['daily_max_withdraw_amount']) {
            if($ignoreWithdrawalAmountLimit ){ // && $this->permissions->checkPermissions('ignore_vip_daily_withdrawal_maximum_amount_settings_when_approve')){
				$this->utils->debug_log("=====setWithdrawToPayProc ignoreWithdrawalAmountLimit", $ignoreWithdrawalAmountLimit, "adminUserId", $adminUserId);
			}
			else{
				// $result=['success'=>false, 'message'=> lang('notify.56') . ' ( $'. $amount_used . '/ $'.$playerWithdrawalRule['daily_max_withdraw_amount'].' )'];
				// $this->returnJsonResult($result);
                // return;
                $isExceedsLimit = true;
			}
        }
        $isExceedsTimesLimit = false;
        if ($withdrawalProcessingCount + $withdrawalPaidCount >= $playerWithdrawalRule['withdraw_times_limit']) {
            if($ignoreWithdrawalTimesLimit ){ // && $this->permissions->checkPermissions('ignore_vip_daily_withdrawal_maximum_times_settings_when_approve')){
				$this->utils->debug_log("=====setWithdrawToPayProc ignoreWithdrawalTimesLimit", $ignoreWithdrawalTimesLimit, "adminUserId", $adminUserId);
			}
			else{
	            // $result=['success'=>false, 'message'=>lang('notify.106')];
	            // $this->returnJsonResult($result);
                // return;
                $isExceedsTimesLimit = true;
			}
        }

        if( $isExceedsLimit == false
            && $isExceedsTimesLimit == false
        ){
            if ($withdrawApi > 0) {
                # Trigger the API payment process automatically. This will put the status to payProc
                // $this->setWithdrawToPaid($walletAccountId, $withdrawApi, $data);
                /// ignore, setWithdrawToPaid() in Payment_management class. Need wrapper into models.
            } else {
                // $this->load->model(array('wallet_model'));
                $this->wallet_model->payProcWithdrawal($adminUserId, $walletAccountId, $withdrawApi, $actionlogNotes, $transaction_fee, null);
            }
        }

    } // EOF pushToNextStage4ManualPayment

    /**
     * Push to Custom Stages 1~6.
     *
     * @param [type] $targetCustomStageNo
     * @return void
     */
    public function pushToNextStage4CustomStages($targetCustomStageNo){

        return $this->pushToSpecStage('CS'.$targetCustomStageNo);

//         // ALT = ACTION_LOG_TITLE
//         $paymentManagementALT = 'Payment Management';

//         $this->load->model(array('wallet_model', 'withdraw_condition', 'system_feature', 'users'));

//         $walletAccountDeatil = $this->preChecker['walletAccountDeatil'];
//         $this->utils->debug_log('OGP-18088, pushToNextStage4CustomStages.customStageNo', $targetCustomStageNo);
//         $this->utils->debug_log('OGP-18088, pushToNextStage4CustomStages.walletAccountDeatil', $walletAccountDeatil);

//         // reference to, http://admin.og.local/payment_management/respondToWithdrawalRequest/33/65/null/CS0
//         $nextStatus = 'CS'. $targetCustomStageNo;
//         $showRemarksToPlayer = null;
//         $actionlogNotes = '[autoRiskProcess] Set Withdraw To Custom Stage';
//         $walletAccountId = $walletAccountDeatil['walletAccountId'];
//         $lockPlayerBalanceResourceDone = false;

//         $playerId = $this->wallet_model->getPlayerIdFromWalletAccount($walletAccountId);
//         $walletAccount = $this->wallet_model->getWalletAccountBy($walletAccountId);

//         $lockedKey=null;
//         $lock_it = $this->lockPlayerBalanceResource($playerId, $lockedKey);
//         if (!$lock_it) {
//             $this->utils->error_log('[autoRiskProcess] lockPlayerBalanceResource() failed in pushToNextStage4Declined().', $wallet_account);
//         }else{
//             $lockPlayerBalanceResourceDone = true;
//         }

//         //lock success
// 		try {
// 			$this->startTrans();

//             // $user_id = $this->authentication->getUserId();
//             $adminUserId = '1'; // @todo OGP-18088, apply adminuser_id
//             $adminUsername = $this->users->getUsernameById($adminUserId); // $this->authentication->getUsername()
// 			$currentStatus = $walletAccount->dwStatus;

// 			$succ = $this->wallet_model->updateWithdrawalRequestStatus($adminUserId, $walletAccountId, $nextStatus, $actionlogNotes, $showRemarksToPlayer);
// 			$this->saveAction($paymentManagementALT, '[autoRiskProcess] Modify Witdrawal Request Status', "User " . $adminUsername . " has changed withdrawal request [$walletAccountId] from [$currentStatus] to [$nextStatus].");
// // $this->utils->debug_log('OGP-18088, pushToNextStage4CustomStages.succ', $succ);
// 			$succ = $this->endTransWithSucc() && $succ;
// 			if ($succ) {
// 				// return $this->returnText('success');
// 			} else {
// 				// return $this->returnText(lang('Update Status Failed'));
// 			}

// 		} finally {
// 			// release it
// 			$rlt = $this->releasePlayerBalanceResource($playerId, $lockedKey);
// 		}
    }// EOF pushToNextStage4CustomStages


    public function pushToSpecStage($targetStage){
        // ALT = ACTION_LOG_TITLE
        $paymentManagementALT = 'Payment Management';

        $this->load->model(array('wallet_model', 'withdraw_condition', 'system_feature', 'users'));

        $walletAccountDeatil = $this->preChecker['walletAccountDeatil'];
        $this->utils->debug_log('OGP-18088, pushToSpecStage.targetStage', $targetStage);
        $this->utils->debug_log('OGP-18088, pushToSpecStage.walletAccountDeatil', $walletAccountDeatil);

        // reference to, http://admin.og.local/payment_management/respondToWithdrawalRequest/33/65/null/CS0
        $nextStatus = $targetStage;
        $showRemarksToPlayer = null;
        $actionlogNotesFormat = '[autoRiskProcess] Set Withdraw To Custom Stage, %s.'; // apply $targetStage
        $actionlogNotes = sprintf($actionlogNotesFormat, $targetStage);
        $walletAccountId = $walletAccountDeatil['walletAccountId'];
        $lockPlayerBalanceResourceDone = false;

        $playerId = $this->wallet_model->getPlayerIdFromWalletAccount($walletAccountId);
        $walletAccount = $this->wallet_model->getWalletAccountBy($walletAccountId);

        $lockedKey=null;
        $lock_it = $this->lockPlayerBalanceResource($playerId, $lockedKey);
        if (!$lock_it) {
            $this->utils->error_log('[autoRiskProcess] lockPlayerBalanceResource() failed in pushToSpecStage().', $wallet_account);
        }else{
            $lockPlayerBalanceResourceDone = true;
        }

        if($lockPlayerBalanceResourceDone) {
            //lock success
            try {
                $this->startTrans();

                $adminUserId = '1'; // apply adminuser_id
                $adminUsername = $this->users->getUsernameById($adminUserId);
                $currentStatus = $walletAccount->dwStatus;
                $ignoreToStatusLimit = true;
                $succ = $this->wallet_model->updateWithdrawalRequestStatus($adminUserId, $walletAccountId, $nextStatus, $actionlogNotes, $showRemarksToPlayer, $ignoreToStatusLimit );

                /// Patch "Fatal Error (E_ERROR): Call to undefined method Command::saveAction()".
                $management = $paymentManagementALT;
                $action = '[autoRiskProcess] Modify Witdrawal Request Status';
                $description = "User " . $adminUsername . " has changed withdrawal request [$walletAccountId] from [$currentStatus] to [$nextStatus].";
                $this->utils->recordAction($management, $action, $description);
                // $this->saveAction($paymentManagementALT, '[autoRiskProcess] Modify Witdrawal Request Status', "User " . $adminUsername . " has changed withdrawal request [$walletAccountId] from [$currentStatus] to [$nextStatus].");
$this->utils->debug_log('OGP-18088, pushToSpecStage.succ', $succ);
                $succ = $this->endTransWithSucc() && $succ;
                if ($succ) {
                    // return $this->returnText('success');
                } else {
                    // return $this->returnText(lang('Update Status Failed'));
                }

            } finally {
                // release it
                $rlt = $this->releasePlayerBalanceResource($playerId, $lockedKey);
            }
        } // EOF if($lockPlayerBalanceResourceDone)

    }// EOF pushToSpecStage

    /**
     * Push the walletAccount to Declined
     *
     * @return void
     */
    public function pushToNextStage4Declined(){
        $this->load->model(['wallet_model', 'users']);

        // ALT = ACTION_LOG_TITLE
        $paymentManagementALT = 'Payment Management';

        $walletAccountDeatil = $this->preChecker['walletAccountDeatil'];
        $this->utils->debug_log('OGP-18088, pushToNextStage4Declined.walletAccountDeatil', $walletAccountDeatil);

        $playerDetail = $this->preChecker['playerDetail'];

        // reference to, http://admin.og.local/payment_management/respondToWithdrawalDeclined/36/null/null/request?notesType=101
        $showDeclinedReason = null;
        $walletAccountId = $walletAccountDeatil['walletAccountId'];
        $actionlogNotes = '[autoRiskProcess] Set Withdraw To Declined';
        $succ = false;
        $wallet_account = $this->wallet_model->getWalletAccountObject($walletAccountId);
        $lockedKey=null;
        $playerId = $playerDetail['playerId'];
        $lockPlayerBalanceResourceDone = false;
        $lock_it = $this->lockPlayerBalanceResource($playerId, $lockedKey);
		if (!$lock_it) {
            $this->utils->error_log('[autoRiskProcess] lockPlayerBalanceResource() failed in pushToNextStage4Declined().', $wallet_account);
        }else{
            $lockPlayerBalanceResourceDone = true;
        }

        if($lockPlayerBalanceResourceDone == true){
            //lock success
            try {

                // $adminUserId = $this->authentication->getUserId();
                // $adminUsername = $this->authentication->getUsername();

                $adminUserId = 1; /// apply system admin.
                $adminUsername = $this->users->getUsernameById($adminUserId);;

                $isLockedForManual = true;
                $isLockedForManual = $this->wallet_model->isLockedForManual($walletAccountId, $adminUserId);
                if( $isLockedForManual ){
                    $result = ['success' => false, 'message'=>lang('this withdrawal has been locked')];
                    $this->utils->error_log('[autoRiskProcess] the walletAccount isLockedForManual()  in pushToNextStage4Declined().', $result);
                }else{
                    $this->startTrans();
                    $declinedCategoryId = null;
                    /// ignore enable_withdrawal_declined_category
                    // if ($this->utils->isEnabledFeature('enable_withdrawal_declined_category') ){
                    //     $declinedCategoryId = $this->input->get('declined_category_id');
                    //     $this->utils->debug_log('declinedCategoryId init ', $declinedCategoryId);
                    // }

                    $succ = $this->wallet_model->declineWithdrawalRequest($adminUserId, $walletAccountId, $actionlogNotes, $showDeclinedReason, $declinedCategoryId);

                    /// Patch "Fatal Error (E_ERROR): Call to undefined method Command::saveAction()".
                    $management = $paymentManagementALT;
                    $action = '[autoRiskProcess] Declined Withdrawal Request';
                    $description = "User " . $adminUsername . " has declined deposit/withdrawal request.";
                    $this->utils->recordAction($management, $action, $description);
                    // $this->saveAction($paymentManagementALT, '[autoRiskProcess] Declined Withdrawal Request', "User " . $adminUsername . " has declined deposit/withdrawal request.");

                    $succ = $this->endTransWithSucc() && $succ;
                }
            } finally {
                // release it
                $rlt = $this->releasePlayerBalanceResource($playerId, $lockedKey);
            }

            if($succ) {
                // $this->wallet_model->userUnlockWithdrawal($walletAccountId); // not need, cuz No review detail at SBE in auto risk process flow.
                $this->sendPromptMessag4declineWithdrawalOrder($playerId);

                $this->createDeclinedNotification($playerId, $wallet_account);
            }
        } // EOF if($lockPlayerBalanceResourceDone == true)

    } // EOF pushToNextStage4Declined

    /**
     * Add player notification while Declined Withdrawal Order
     *
     * @todo OGP-18088, TEST player notification while Declined Withdrawal Order
     *
     * @param integer $player_id The player.playerId.
     * @param array $wallet_account The walletaccount row.
     * @return void
     */
    public function createDeclinedNotification($player_id, $wallet_account){
        $this->load->library(['player_notification_library']);
        $this->player_notification_library->danger($wallet_account['playerId'], Player_notification::SOURCE_TYPE_WITHDRAWAL, [
            'player_notify_danger_withdrawal_title',
            $wallet_account['transactionCode'],
            $wallet_account['dwDateTime'],
            $this->utils->displayCurrency($wallet_account['amount']),
            $this->utils->getLiveChatLink(),
            $this->utils->getLiveChatOnClick()
        ], [
            'player_notify_danger_withdrawal_message',
            $wallet_account['transactionCode'],
            $wallet_account['dwDateTime'],
            $this->utils->displayCurrency($wallet_account['amount']),
            $this->utils->getLiveChatLink(),
            $this->utils->getLiveChatOnClick()
        ]);
    } // EOF createDeclinedNotification

    /**
     * Send Prompt Message via SMS for Declined Withdrawal Order
     * @todo OGP-18088, TEST send Prompt Messag while Declined Withdrawal Order
     *
     * @param integer $player_id The player.playerId.
     * @return void
     */
    public function sendPromptMessag4declineWithdrawalOrder($playerId){
        # send prompt message when withdrawal order is decline
        if ($this->utils->isEnabledFeature('enable_sms_withdrawal_prompt_action_declined')) {

            $this->load->model(['cms_model', 'queue_result', 'player_model']);
            $this->load->library(["lib_queue", "sms/sms_sender"]);

            $player = $this->player_model->getPlayerInfoDetailById($playerId);
            $mobileNumIsVeridied = $player['verified_phone'];

            if($mobileNumIsVeridied) {
                $isUseQueueToSend    = $this->utils->isEnabledFeature('enabled_send_sms_use_queue_server');
                $dialingCode = $player['dialing_code'];
                $mobileNum = !empty($dialingCode)? $dialingCode.'|'.$player['contactNumber'] : $player['contactNumber'];
                $smsContent = $this->cms_model->getManagerContent(Cms_model::SMS_MSG_WITHDRAWAL_DECLINE);
                $use_new_sms_api_setting = $this->utils->getConfig('use_new_sms_api_setting');
                $useSmsApi = null;
                $sms_setting_msg = '';
                if ($use_new_sms_api_setting) {
                #restrictArea = action type
                    $sessionId = $this->session->userdata('session_id');
                    $restrictArea = 'sms_api_manager_setting';
                    list($useSmsApi, $sms_setting_msg) = $this->utils->getSmsApiNameByNewSetting($playerId, $mobileNum, $restrictArea, $sessionId);
                }

                $this->utils->debug_log(__METHOD__, 'use new sms api',$useSmsApi, $sms_setting_msg);

                if ($isUseQueueToSend) {
                    $callerType = Queue_result::CALLER_TYPE_ADMIN;
                    $caller = $adminUserId;
                    $state  = null;
                    $this->lib_queue->addRemoteSMSJob($mobileNum, $smsContent, $callerType, $caller, $state, null);
                } else {
                    $this->sms_sender->send($mobileNum, $smsContent);
                }
            }
        }
    } // EOF sendPromptMessag4declineWithdrawalOrder

    /**
     * Get the Next Stage by Definition List.
     *
     * The field,"dispatch_order" smaller will push first.
     *
     * @param array $customizedDefinitionResultList
     * @param point $breakByDefinitionId The DefinitionId means the definition mets the conditions and the NextStage used.
     * @return array $push2dwStatusInfo
     */
    public function getNextStageByCustomizedDefinitionList($customizedDefinitionResultList, &$breakByDefinitionId = null){
        $push2dwStatusInfo = null; // initialize
        if( ! empty($customizedDefinitionResultList) ){
            // usort($customizedDefinitionResultList, [$this, 'dispatch_order_cmp']);
            usort($customizedDefinitionResultList, function($a, $b){
                return $a["dispatch_order"] > $b["dispatch_order"];
            });
            $push2dwStatusInfo = []; // default

            foreach($customizedDefinitionResultList as $indexNumber => $conditionResult){
                if( empty($conditionResult['dwStatus']) ){
                    continue; // check next
                }

                $push2dwStatusInfo = array_merge($push2dwStatusInfo, $conditionResult); // handle return of  customizedDefinition::runPreChecker().
                $breakByDefinitionId = $conditionResult['definitionId'];
                break; // leave the loop
            }

        }
        /// @todo OGP-18088, 我们可以用一个细表或字段来记录发生这件事的时候，当时每个条件是如何判断的吗
        // 比如 当时的数字是多少，这样可以检查是否判断错误，或者是一些数据有延迟
        // 是指的每次请求时的状态，比如当时这些条件判断时的data
        // 建个细表比较好，与 walletaccount表关联
        // 昨天说的细表的问题可以建起来了，在判断条件的时候就写入细表，可以当成日志，也可以当成后面人工检查的依据
$this->utils->debug_log('OGP-18088, getNextStageByCustomizedDefinitionList.customizedDefinitionResultList', $customizedDefinitionResultList, 'push2dwStatusInfo:', $push2dwStatusInfo);
if(! empty($breakByDefinitionId) ) {
    $this->utils->debug_log('OGP-18088, getNextStageByCustomizedDefinitionList.breakByDefinitionId', $breakByDefinitionId);
}
        // $walletAccountDeatil = $this->preChecker['walletAccountDeatil'];
        // $data['walletAccountId'] =  $walletAccountDeatil['walletAccountId'];
        // $this->dispatch_withdrawal_results->add($data);
        $push2dwStatusInfo['byDefinitionId'] = $breakByDefinitionId;
        return $push2dwStatusInfo;
    } // EOF getNextStageByCustomizedDefinitionList


    /**
     * Load Customized Definition Class by "dispatch_withdrawal_definition.id".
     *
     * @param integer $definition_id The field,"dispatch_withdrawal_definition.id".
     * @return void
     */
    public function loadCustomizedDefinitionObject($definition_id) {
        $this->load->model(['dispatch_withdrawal_definition']);
        $definitionDetail = $this->dispatch_withdrawal_definition->getDetailById($definition_id);
        $extra = json_decode($definitionDetail['extra'], true);
        if( ! empty($extra['class']) ){
            $class_name = $extra['class'];
        }else{
            $extra['class'] = null;
            $class_name = 'customized_definition_default';
        }
		$customized_withdrawal_definitions_calss_file = 'customized_withdrawal_definitions/'. strtolower($class_name);

        $curr_file_dir = dirname(__FILE__).'/';
        $classPathFile = $curr_file_dir. '../../models/'.$customized_withdrawal_definitions_calss_file.'.php';
        // /home/vagrant/Code/og/admin/application/controllers/modules/../customized_withdrawal_definitions/customized_definition_default.php
        // admin/application/models/customized_withdrawal_definitions/abstract_customized_definition.php
        if( ! file_exists($classPathFile) ){
            $class_name = 'customized_definition_default'; // loading default promo_rule class.
            $customized_withdrawal_definitions_calss_file = 'customized_withdrawal_definitions/'. strtolower($class_name);

            // // for dry run.
			// $this->appendToDebugLog($extra_info['debug_log'], __METHOD__.'(): loaded FAILED. '. $desc_class['class']. ' Not Found, current class_name='. $class_name);

			// console out error_log().
            $this->utils->error_log(__METHOD__.'(): loaded FAILED. '. $extra['class']. ' Not Found, current class_name='. $class_name, '$classPathFile:', $classPathFile);

        }
        $this->load->model($customized_withdrawal_definitions_calss_file);
        $_class_name = strtolower($class_name);
        $customizedObj=$this->$_class_name;

        // $customizedObj->init();
        // $customizedObj->runPreChecker();
        // $customizedObj->getRecommandDwStatus();
		return $customizedObj;

    }// EOF loadCustomizedDefinitionObject

} // EOF trait withdrawal_risk_api_module
