<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * Sale_order_library
 *
 * This class is used to process sale order
 * 
 * @package		Processing Sale Order library
 * @author		Gary
 * @version		1.0.0
 */

class Sale_order_library {
    const PROCESSING_BY_QUEUE = 1;
    const PROCESSING_BY_MANUAL = 2;
    public $CI;
    /** @var Sale_order $sale_order */
    private $sale_order;
    /** @var Sale_orders_additional $sale_orders_additional */
    private $sale_orders_additional;
    /** @var Player_model $player_model */
    private $player_model;
    /** @var Group_level $group_level */
    private $group_level;
    /** @var Promorules $promorules */
    private $promorules; 
    /** @var Point_transactions $point_transactions */
    private $point_transactions;
    /** @var Transactions $transactions */
    private $transactions;
    /** @var Permissions $permissions */
    private $permissions;
    /** @var Authentication $authentication */
    private $authentication;
    /** @var Utils $utils */
    private $utils;
    /** @var Lib_queue $lib_queue */
    private $lib_queue;
    /** @var Payment_library $payment_library */
    private $payment_library;
    /** @var Player_notification_library $player_notification_library */
    private $player_notification_library;
    /** @var Fast_track $fast_track */
    private $fast_track;
    private $adminUserId;
    private $adminUserName;
     
	function __construct($dependencies = []) {
		$this->CI = &get_instance();
        $this->initDependencies($dependencies);
        $this->adminUserId = !empty($this->authentication->getUserId()) ? $this->authentication->getUserId() : Users::SUPER_ADMIN_ID;
        $this->adminUserName = !empty($this->authentication->getUserName()) ? $this->authentication->getUserName() : Users::SUPER_ADMIN_NAME;
	}

    private function initDependencies($dependencies){
        $this->CI->load->model(['sale_order','group_level','player_model','payment_account','player_dw_achieve_threshold','transactions','point_transactions', 'sale_orders_additional']);
        $this->CI->load->library(['permissions','authentication','lib_queue', 'payment_library', 'player_notification_library', 'fast_track']);

        // models
        $this->sale_order = isset($dependencies['sale_order']) ? $dependencies['sale_order'] : $this->CI->sale_order;  
        $this->sale_orders_additional = isset($dependencies['sale_orders_additional']) ? $dependencies['sale_orders_additional'] : $this->CI->sale_orders_additional;
        $this->player_model = isset($dependencies['player_model']) ? $dependencies['player_model'] : $this->CI->player_model;
        $this->group_level = isset($dependencies['group_level']) ? $dependencies['group_level'] : $this->CI->group_level;
        $this->payment_account = isset($dependencies['payment_account']) ? $dependencies['payment_account'] : $this->CI->payment_account;
        $this->promorules = isset($dependencies['promorules']) ? $dependencies['promorules'] : $this->CI->promorules;
        $this->transactions = isset($dependencies['transactions']) ? $dependencies['transactions'] : $this->CI->transactions;
        $this->point_transactions = isset($dependencies['point_transactions']) ? $dependencies['point_transactions'] : $this->CI->point_transactions;

        // libraries
        $this->permissions = isset($dependencies['permissions']) ? $dependencies['permissions'] : $this->CI->permissions;
        $this->authentication = isset($dependencies['authentication']) ? $dependencies['authentication'] : $this->CI->authentication;
        $this->utils = isset($dependencies['utils']) ? $dependencies['utils'] : $this->CI->utils;
        $this->lib_queue = !empty($dependencies['lib_queue']) ? $dependencies['lib_queue'] : $this->CI->lib_queue;
        $this->payment_library = !empty($dependencies['payment_library']) ? $dependencies['payment_library'] : $this->CI->payment_library;
        $this->player_notification_library = !empty($dependencies['player_notification_library']) ? $dependencies['player_notification_library'] : $this->CI->player_notification_library;
        $this->fast_track = !empty($dependencies['fast_track']) ? $dependencies['fast_track'] : $this->CI->fast_track;
    }

    /**
     * Check permissions for processing order
     * @param mixed $permission
     * @return bool
     */
    public function checkProcessingOrderPermissions()
    {
        if(!$this->permissions->checkPermissions('deposit_list')){
            return false;  
        }

        if(!$this->permissions->checkPermissions('approve_decline_deposit') &&
            !$this->permissions->checkPermissions('single_approve_decline_deposit'))
        {
            return false;
        }

        return true;
    }

    /**
     * Check if the order is locked by other admin
     * @param  object $saleOrder
     * @return bool
     */
    public function isCacheLocked($saleOrder)
    {
        if(empty($saleOrder) || !is_object($saleOrder)){
            return false;
        }

        if($this->utils->notEmptyTextFromCache($saleOrder->secure_id.'_approved')){
            return true;
        }else{
            $this->utils->saveTextToCache($saleOrder->secure_id.'_approved', true, 300);
        }

        return false;
    }

    /**
     * Set deposit order status to queue_approve
     * @param int $orderId
     * @param array $extraInfo
     * @return array
     */
    public function setProcessingToQueueApprove($orderId, $extraInfo = [])
    {
        $result = [
            'success' => false,
            'message' => ''
        ];

        $controller = $this;

        $this->sale_order->lockAndTransForDepositLock($orderId, function() use ($orderId, $controller, $extraInfo, &$result)
        { 
            $saleOrder = $controller->sale_order->getSaleOrderWithPlayerById($orderId);

            if(empty($saleOrder)){
                $result['message'] = 'deposit order not found';
                return false;
            }
            
            $player = $saleOrder->player;
            $currentStatus = $saleOrder->status;
            $approvePlayerGroupLevelRequest = !empty($extraInfo['approvePlayerGroupLevelRequest'])? $extraInfo['approvePlayerGroupLevelRequest'] : false;
            $notes = [
                'action_log' => !empty($extraInfo['actionLog'])? $extraInfo['actionLog'] : null,
                'show_reason_to_player' => !empty($extraInfo['showReasonToPlayer'])? $extraInfo['showReasonToPlayer'] : null,
            ];
            
            if($currentStatus != Sale_order::STATUS_PROCESSING && $currentStatus != Sale_order::STATUS_CHECKING){
                $result['message'] = 'Status is not processing or checking';
                return false;
            }

            $result['success'] = $this->sale_order->setDepositStatus($orderId, $controller->adminUserId, $currentStatus, Sale_order::STATUS_QUEUE_APPROVE, $notes);
            
            if($result['success'] && $controller->utils->getConfig('update_player_approved_deposit_count_when_approve_sale_order')){
                $controller->player_model->incApprovedDepositCount($saleOrder->player_id);
            }
            
            // change player group level
            if ($approvePlayerGroupLevelRequest && isset($saleOrder->group_level_id) && $saleOrder->group_level_id > 0) {
                $controller->group_level->adjustPlayerLevel($player->playerId, $saleOrder->group_level_id);
            }
            
            $controller->utils->recordAction('Payment Management', 'Approve Deposit Request', "User " . $controller->adminUserName . " has successfully approve deposit request of " . $player->username . ".");

            // for inernal use
            $controller->sendMatterMostMessageWhenChangingStatus('Changed Deposit Status', 'Successfully set '.$saleOrder->secure_id.' to queue approve from processing');

            return $result['success'];
		});

        return $result;
    }

    /**
     * Set queue approve to transferring
     * @param int $orderId
     * @return array
     */
    public function setQueueApproveToTransferring($orderId){
        $result = [
            'success' => false,
            'message' => ''
        ];

        $controller = $this;
        $this->sale_order->lockAndTransForDepositLock($orderId, function() use ($orderId, $controller, &$result){ 
            $saleOrder = $this->sale_order->getSaleOrderWithPlayerById($orderId);

            if(empty($saleOrder)){
                $result['message'] = 'deposit order not found';
                return $result;
            }

            $currentStatus = $saleOrder->status;        

            if($currentStatus != Sale_order::STATUS_QUEUE_APPROVE){
                $result['message'] = 'Status is not queue approve';
                return $result;
            }

            $notes['action_log'] = 'set queue approve to transferring';
            $result['success'] = $this->sale_order->setDepositStatus($orderId, $controller->adminUserId, $currentStatus, Sale_order::STATUS_TRANSFERRING, $notes);
            $controller->utils->recordAction('Payment Management', 'Approve Deposit Request', "set the sale order status to transferring");

            // for inernal use
            $controller->sendMatterMostMessageWhenChangingStatus('Changed Deposit Status', 'Failed to set ['.$saleOrder->secure_id .'] to settled and back to transferring');
            
            return $result['success'];
        });

        return $result;
    }

    /**
     * set approve status and process deposit details
     * @param int $orderId
     * @param boolean $approvedSubWallet
     * @param boolean $approvePromotion
     * @return array
     */
    public function setQueueApproveToSettled($orderId, $approvedSubWallet, $approvePromotion){
        $result = [
            'success' => false,
            'message' => '',
            'promo_result' => [],
        ];

        $saleOrder = $this->sale_order->getSaleOrderWithPlayerById($orderId);
        $extraInfo = [
            'approve_SubWallet' => $this->utils->convertToBoolean($approvedSubWallet),
            'approve_promotion' => $this->utils->convertToBoolean($approvePromotion),
            'is_payment_account_promo' => false,
        ];
        
        if(empty($saleOrder)){
            $result['message'] = 'deposit order not found';
            return [$result, $extraInfo];
        }
    
        $currentStatus = $saleOrder->status;
        $playerId = $saleOrder->player_id;

        if($currentStatus != Sale_order::STATUS_QUEUE_APPROVE){
            $result['message'] = 'Status is not queue approve';
            return [$result, $extraInfo];
        }

        $controller = $this;
        $this->sale_order->lockAndTransForPlayerBalance($playerId, function ()
            use ($controller, $orderId, $saleOrder, &$extraInfo, &$result) {

            $notes['action_log'] = 'set queue approve to approve';
            $result['success'] = $this->sale_order->setDepositStatus($orderId, $controller->adminUserId, Sale_order::STATUS_QUEUE_APPROVE, Sale_order::STATUS_SETTLED, $notes);
            
            if(!$result['success']){ 
                $result['message'] = 'setted the sale order status failed';
                return false;
            }
        
            // clear withdraw condition && create transaction
            $result['success'] = $controller->sale_order->processApproveSaleOrder($saleOrder, $extraInfo);

            if(!$result['success']){
                $result['message'] = !empty($extraInfo['error_message'])? $extraInfo['error_message'] : 'Error occurred when approve deposit';
                return false;
            }

            if(!empty($extraInfo['apply_promo_success'])){
                $result['promo_result'] = [
                    'apply_promo_success' => $extraInfo['apply_promo_success'],
                    'apply_promo_message' => lang('Deposit Successfully').' '.@$extraInfo['apply_promo_message']
                ];
            }

            if($controller->utils->getConfig('enable_deposit_amount_to_point')){
                $controller->processDepositPoint($orderId);
            }

            $controller->utils->recordAction('Payment Management', 'Approve Deposit Request', "set the sale order status to approve");

            // for inernal use
            $controller->sendMatterMostMessageWhenChangingStatus('Changed Deposit Status', 'Successfully set '.$saleOrder->secure_id.' to settled from queue approve');

            return $result['success'];
        });

        if($result['success']){
            $this->notifySuccess($orderId);
        }else{
            $this->notifyFailed($orderId);
        }

        return [$result, $extraInfo];
    }

    /**
     * Process approve order success
     * @param int $orderId
     * @param array $extraInfo
     * @return array
     */
    public function processSuccessApprove($orderId, $playerId, &$extraInfo, &$result){
        if(!empty($extraInfo)){
            $this->promorules->releaseToAfterApplyPromo($extraInfo);
        }

        $result['success'] = $this->sale_order->approveSaleOrderSubWalletWithLock($orderId, $extraInfo);
        $result['message'] = (!empty($extraInfo['error_message'])) ? lang($extraInfo['error_message']) : '';
        
        if($result['success']){
            $result['success'] = $this->sale_order->approveSaleOrderPlayerPromotionWithLock($orderId, $extraInfo);
            $result['message'] = (!empty($extraInfo['apply_promo_message'])) ? lang($extraInfo['apply_promo_message']) : NULL;
        }
        
        if($result['success'] && $this->utils->isEnabledFeature('show_player_deposit_withdrawal_achieve_threshold')) {
            $this->payment_library->verify_dw_achieve_threshold_amount($playerId, Player_dw_achieve_threshold::ACHIEVE_THRESHOLD_DEPOSIT);
        }
        
        if(!empty($extraInfo['addRemoteJob']) ){
            $funcName = 'addRemoteSend2Insvr4CreateAndApplyBonusMultiJob';
            if( ! empty($extraInfo['addRemoteJob'][$funcName]) ){
                $thePromorulesId = $extraInfo['addRemoteJob'][$funcName]['params']['promorulesId'];
                $thePlayerId = $extraInfo['addRemoteJob'][$funcName]['params']['playerId'];
                $thePlayerPromoId = $extraInfo['addRemoteJob'][$funcName]['params']['playerPromoId'];
                try {
                    $callerType = Queue_result::CALLER_TYPE_ADMIN;
                    $caller = $thePlayerId;
                    $state  = null;
                    $lang=null;
                    $token = $this->lib_queue->addRemoteSend2Insvr4CreateAndApplyBonusMultiJob($thePromorulesId // #1
                                                                , $thePlayerId // #2
                                                                , $thePlayerPromoId // #3
                                                                , $callerType // #4
                                                                , $caller // #5
                                                                , $state // #6
                                                                , $lang // #7
                                                            );
                    if(!empty($token) ){
                        unset($extraInfo['addRemoteJob'][$funcName]); // completed
                    }
                } catch (Exception $e) {
                    $formatStr = 'Exception in set_deposit_approved(). (%s)';
                    $this->utils->error_log( sprintf( $formatStr, $e->getMessage() ) );
                }
            }
        }

        return $result;
    }

    /**
     * Process deposit point
     * @param int $orderId
     * @return void
     */
    public function processDepositPoint($orderId)
    {
        $saleOrder = $this->sale_order->getSaleOrderWithPlayerById($orderId);
        $player = $saleOrder->player;
        //get deposit convert rate of this player
        $depositConvertRate = $this->group_level->getVipGroupLevelDetails($player->levelId)['deposit_convert_rate'];

        //point based on convert rate
        $point = intval($saleOrder->amount * $depositConvertRate / 100, 4);
        $extra['source_amount'] = $saleOrder->amount;
        $extra['current_rate'] = $depositConvertRate;

        //player current point
        $beforePointBalance = intval($this->point_transactions->getPlayerAvailablePoints($player->playerId),4);

        //get limit setting
        $pointLimit = $this->group_level->getVipGroupLevelDetails($player->levelId)['points_limit'];
        $pointLimitType = $this->group_level->getVipGroupLevelDetails($player->levelId)['points_limit_type'];

        //get allowed points
        $dateWithinObj = new DateTime();
        $dateWithin = $dateWithinObj->format('Y-m-d');
        $calculateDepositToPointsResult = $this->point_transactions->calculateDepositToPoints($player->playerId, $saleOrder->amount, $orderId, $depositConvertRate, $pointLimit, $pointLimitType, $point, $dateWithin);

        $point = $calculateDepositToPointsResult['points_allowed_to_add'];
        $reason = 'Deposit to points. '. $calculateDepositToPointsResult['remarks'];
        $newPointBalance = intval($beforePointBalance + $point, 4);
        $extra = $calculateDepositToPointsResult['extra'];

        $this->point_transactions->createPointTransaction(
            $this->adminUserId,
            $player->playerId,
            $point,
            $beforePointBalance,
            $newPointBalance,
            $saleOrder->id,
            $saleOrder->player_promo_id,
            Point_transactions::DEPOSIT_POINT,
            $reason,
            null,
            1,
            $extra
        );

        //update player point balance
        $this->player_model->updatePlayerPointBalance($player->playerId, $newPointBalance);
    }

    /**
     * Notify success approve
     * @param int $orderId
     */
    private function notifySuccess($orderId){
        $saleOrder = $this->sale_order->getSaleOrderWithPlayerById($orderId);

        if($this->utils->getConfig('enable_fast_track_integration')) {
            $this->fast_track->approveDeposit((array) $saleOrder);
        }

        $trans = $this->transactions->getTransactionBySaleOrderId($saleOrder->id);
        $currency = $this->utils->getCurrentCurrency();

        $this->utils->playerTrackingEvent($saleOrder->player_id, 'approveDeposit',
        array(
            'orderid'			=> $saleOrder->id,
            'secure_id' 		=> $saleOrder->secure_id,
            'amount'			=> $saleOrder->amount,
            "Type"              => "Deposit",
            "Status"            => "Success",
            "Currency"          => $currency['currency_code'],
            "TransactionID"     => $saleOrder->secure_id,
            "Channel"           => $saleOrder->payment_account_name,
            "TimeTaken"         => strtotime($trans->created_at) - strtotime($saleOrder->created_at),
            "LastDepositAmount" => $saleOrder->amount
        ));
        
        $depositFlag = $this->transactions->isOnlyFirstDeposit($saleOrder->player_id) ? Player_notification::FLAG_FIRST_DEPOSIT : Player_notification::FLAG_COMMON_DEPOSIT;
        $this->player_notification_library->success($saleOrder->player_id, Player_notification::SOURCE_TYPE_DEPOSIT, [
            'player_notify_success_deposit_title',
            $saleOrder->secure_id,
            $saleOrder->created_at,
            $this->utils->displayCurrency($saleOrder->amount),
            $this->utils->getPlayerHistoryUrl('deposit')
        ], [
            'player_notify_success_deposit_message',
            $saleOrder->secure_id,
            $saleOrder->created_at,
            $this->utils->displayCurrency($saleOrder->amount),
            $this->utils->getPlayerHistoryUrl('deposit'),
            $depositFlag
        ]);
    }

    /**
     * Notify failed approve
     * @param int $orderId
     */
    private function notifyFailed($orderId){
        $saleOrder = $this->sale_order->getSaleOrderWithPlayerById($orderId);

        $this->player_notification_library->danger($saleOrder->player_id, Player_notification::SOURCE_TYPE_DEPOSIT, [
            'player_notify_danger_deposit_title',
            $saleOrder->secure_id,
            $saleOrder->created_at,
            $this->utils->displayCurrency($saleOrder->amount),
            $this->utils->getLiveChatLink(),
            $this->utils->getLiveChatOnClick()
        ], [
            'player_notify_danger_deposit_message',
            $saleOrder->secure_id,
            $saleOrder->created_at,
            $this->utils->displayCurrency($saleOrder->amount),
            $this->utils->getLiveChatLink(),
            $this->utils->getLiveChatOnClick()
        ]);
    }
    
    /**
     * Handle queue approve by pattern
     * @param int $orderId
     * @param int $pattern
     * @return array
     */
    public function handleQueueApprove($orderId, $approvedSubWallet, $approvePromotion, $pattern){
        $result = [
            'success' => false,
            'message' => ''
        ];

        switch ($pattern) {
            case self::PROCESSING_BY_QUEUE:

                $params = [
                    'operator' => $this->adminUserName,
                    'saleOrderId' => $orderId,
                    'approvedSubWallet' => $approvedSubWallet,
                    'approvePromotion' => $approvePromotion
                ];

                $result['token'] = $this->generateQueueApproveJob($orderId, $params);

                if(!empty($result['token'])){
                    $result['success'] = true;

                    // save token to additional table
                    $params['token'] = $result['token'];
                    $data['async_job_params'] = json_encode($params);
                    $this->sale_orders_additional->syncToAdditionalBySaleOrderId($orderId, $data);

                }else{
                    $result['message'] = 'generate queue approve job failed';
                }

                break;
            case self::PROCESSING_BY_MANUAL:
                // @todo processing by manual
                break;
        }

        return $result;
    }

    /**
     * Generate queue approve job
     * @param int $orderId
     * @return string $token
     */
    public function generateQueueApproveJob($orderId, $params)
    {
        if(empty($orderId)){
            return null;
        }

		$token = $this->lib_queue->addRemoteApproveSaleOrderJob($params, Queue_result::CALLER_TYPE_ADMIN, $this->adminUserId, null);
		return $token;
    }

    public function userUnlockDeposit($salesOrderId){
		$controller = $this;
		$this->sale_order->lockAndTransForDepositLock($salesOrderId, function() use ($controller, $salesOrderId) {
			$controller->sale_order->userUnlockDeposit($salesOrderId);
			return true;
		});
    }

    public function sendMatterMostMessageWhenChangingStatus($title, $message){ 
        $setting = $this->utils->getConfig('moniter_changing_deposit_status');
        if(!empty($setting['channel'])){
            $level = 'warning';
            $channel = $setting['channel'];
            $this->utils->sendMessageToMattermostChannel($channel, $level, $title, $message);
        }
    }

}
