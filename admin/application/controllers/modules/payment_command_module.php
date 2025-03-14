<?php
/**
 * 
 * @property Queue_result $queue_result
 * @property Sale_order_library $Sale_order_library
 */
trait payment_command_module
{
    public function process_queue_approve_to_approved($token, $saleOrderId = _COMMAND_LINE_NULL){
        $this->utils->info_log("start process_queue_approve_to_approved-$token");
        $this->load->model(['queue_result']);
        $this->load->library(['sale_order_library']);

        if(!empty($token) && $token != _COMMAND_LINE_NULL){
            $params = $this->_getParamsByToken($token);
        }else if($saleOrderId != _COMMAND_LINE_NULL){
            $params = $this->_getParamsBySaleOrderId($saleOrderId);
        }

        $saleOrderId = isset($params['saleOrderId']) ? $params['saleOrderId'] : null;
        $approvedSubWallet = isset($params['approvedSubWallet']) ? $params['approvedSubWallet'] : false;
        $approvePromotion = isset($params['approvePromotion']) ? $params['approvePromotion'] : false;
        $saleOrder = $this->sale_order->getSaleOrderById($saleOrderId);

        if(empty($saleOrder) || empty($saleOrder->player_id)){
            $this->utils->error_log("Sale Order Data Error", $saleOrder, $token, $saleOrderId);
            $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> "Sale Order Data Error" ], true);
            return false;
        }

        $playerId = $saleOrder->player_id;

        $this->utils->debug_log("data-$token", $params);

        if(!empty($token) && $token != _COMMAND_LINE_NULL){
            $dateTimeStr = $this->utils->getNowForMysql();
            $result = [
                      'saleOrderId'=>$saleOrderId, 
                      'message'=>'Processing...', 
                      'dateTimeStr'=>$dateTimeStr
                    ];
            $done = false;
            $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done);
        }

        list($processResult, $extraInfo) = $this->sale_order_library->setQueueApproveToSettled($saleOrderId, $approvedSubWallet, $approvePromotion);

        $this->utils->info_log('processing process_queue_approve_to_approved', $processResult, $extraInfo);

        if($processResult['success']){
            // status is changed to settled
            $systemId = empty($saleOrder->system_id) ? null : $saleOrder->system_id;

            if (empty($systemId) || !$this->utils->getConfig('disable_3rd_party_payment_trigger_event')) {
                $this->_triggerDepositEvent(
                    $playerId, 
                    $saleOrder->id, 
                    null, 
                    $systemId, 
                    $saleOrder->payment_account_id, 
                    Users::SUPER_ADMIN_ID
                );
            }
            
            $this->sale_order_library->processSuccessApprove($saleOrderId, $playerId, $extraInfo, $processResult);
        }else{
            // waiting for cronjob to retry
            $this->sale_order_library->setQueueApproveToTransferring($saleOrderId);
        }

        if(!empty($token) && $token != _COMMAND_LINE_NULL){
			$result = [
                'success' => $processResult['success'], 
                'message' => 'completed', 
                'dataResult' => $processResult
            ];
			$done = true;
            $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, false);

            $this->utils->info_log('end process_queue_approve_to_approved', $result);
		}

        return true;
    }

    public function process_transferring_to_queue_approve($from = _COMMAND_LINE_NULL, $to = _COMMAND_LINE_NULL, $saleOrderId = _COMMAND_LINE_NULL){
        $this->load->model(['sale_order', 'sale_orders_additional']);
        $this->load->library(['sale_order_library']);

        $dateFrom = ($from != _COMMAND_LINE_NULL) ? date('Y-m-d H:i:s', strtotime($from)) : $this->utils->getLast24HoursForMysql();
		$dateTo = ($to != _COMMAND_LINE_NULL) ? date('Y-m-d H:i:s', strtotime($to)) : $this->utils->getNowForMysql();

        if($saleOrderId == _COMMAND_LINE_NULL || empty($saleOrderId)){
            // no need to filter by saleOrderId
            $saleOrderId = null;
        }

        $saleOrders = $this->sale_order->getSaleOrdersByStatus(Sale_order::VIEW_STATUS_TRANSFERRING, $dateFrom, $dateTo, $saleOrderId);
        $result = [
            'tokens' => [],
            'transferring_counts' => count($saleOrders),
        ];

        $this->utils->info_log('start process_transferring_to_queue_approve Date from', $dateFrom, 'Date to ', $dateTo);

        if(!empty($saleOrders) && is_array($saleOrders))
        {               
            foreach($saleOrders as $saleOrder){
                $orderId = $saleOrder->id;

                $this->sale_order->lockAndTransForDepositLock($orderId, function() use ($saleOrder, $orderId, &$result){ 
                    $currentStatus = $saleOrder->status;
                    $notes['action_log'] = 'set transferring to queue approve';
                    $success = $this->sale_order->setDepositStatus($orderId, Users::SUPER_ADMIN_ID, $currentStatus, Sale_order::STATUS_QUEUE_APPROVE, $notes);  
                    
                    if(!$success){
                        $this->utils->error_log('setDepositStatus failed', $orderId);
                        return false;
                    }

                    $this->sale_order_library->sendMatterMostMessageWhenChangingStatus('Changed Deposit Status', 'Successfully set '.$saleOrder->secure_id.' to queue approve from transferring');
                                        
                    $additionalData = $this->sale_orders_additional->getDetailBySaleOrderId($orderId);                
                    $params = json_decode($additionalData['async_job_params'], true);
                    $token = $this->sale_order_library->generateQueueApproveJob($orderId, $params);

                    if(!empty($token)){
                        $result['tokens'][] = $token;
                        return true;
                    }

                    return false;
                });				

                $this->utils->recordAction('Payment Management', 'Approve Deposit Request', "Command is process transferring to queue approve and generate async job:".json_encode($result['tokens']));
            }
        }

        $this->utils->info_log('end process_transferring_to_queue_approve Date from', $dateFrom, 'Date to ', $dateTo, 'result', $result);
    }

    /**
     * triggerDepositEvent
     * @param  int $orderId
     * @param  int $transId
     * @param  int $systemId
     * @param  int $userId
     * @return string $token
     */
    private function _triggerDepositEvent($playerId, $orderId, $transId, $systemId, $paymentAccountId, $userId){
        if(!$this->utils->getConfig('enabled_remote_async_event')){
            return null;
        }
        if(empty($orderId)){
            return null;
        }
        if(empty($playerId)){
            return null;
        }
        $this->load->library(['lib_queue']);
        $this->load->model(['queue_result', 'transactions']);

        $callerType = Queue_result::CALLER_TYPE_SYSTEM;
        $caller = Queue_result::SYSTEM_UNKNOWN;

        if(!empty($systemId)){
            $callerType=Queue_result::CALLER_TYPE_SYSTEM;
            $caller=$systemId;
        }else if(!empty($userId)){
            $callerType=Queue_result::CALLER_TYPE_ADMIN;
            $caller=$userId;
        }

        if(empty($transId)){
            $transId=$this->transactions->getTransIdBySaleOrderId($orderId);
        }

        if(empty($paymentAccountId)){
            $paymentAccountId=$this->transactions->getPaymentAccountIdBySaleOrderId($orderId);
        }

        $token=$this->lib_queue->triggerAsyncRemoteDepositEvent(Queue_result::EVENT_DEPOSIT_AFTER_DB_TRANS,
            $playerId, $orderId, $transId, $paymentAccountId, $callerType, $caller);

        $this->utils->debug_log('deposit event EVENT_DEPOSIT_AFTER_DB_TRANS', $token);

        return $token;
    }

    private function _getParamsByToken($token){
        $data = $this->initJobData($token);
        $params = [];

		if (isset($data['params']) && !empty($data['params'])) {
			$params = $data['params'];
		}

        return $params;
    }

    private function _getParamsBySaleOrderId($saleOrderId){
        $params = [];
        $additionalData = $this->sale_orders_additional->getDetailBySaleOrderId($saleOrderId);
        if(!empty($additionalData)){
            $params = json_decode($additionalData['async_job_params'], true);
        }

        return $params;
    }
}
