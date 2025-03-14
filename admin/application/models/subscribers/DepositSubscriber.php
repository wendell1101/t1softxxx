<?php

require_once dirname(__FILE__) . "/../events/DepositEvent.php";
require_once dirname(__FILE__) . "/AbstractSubscriber.php";

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DepositSubscriber extends AbstractSubscriber implements EventSubscriberInterface
{
    public function __construct()
    {
        parent::__construct();
        // $this->utils->info_log('load subscriber class', get_class());
    }

    public static function getSubscribedEvents()
    {
        return array(
            Queue_result::EVENT_DEPOSIT_AFTER_DB_TRANS => 'afterDepositDBTrans',
        );
    }

    public function afterDepositDBTrans(DepositEvent $event)
    {
        $this->utils->debug_log('===================Deposit Event afterDepositDBTrans', $event);

        $saleOrderId=$event->getSaleOrderId();
        $transactionId=$event->getTransactionId();
        $playerId=$event->getPlayerId();
        $paymentAccountId=$event->getPaymentAccountId();
        $saleOrder=null;
        $transaction=null;
        $player=null;
        $paymentAccount=null;

        $this->load->model(['sale_order', 'transactions', 'player_model', 'payment_account']);
        $isEnabledMDB = $this->utils->isEnabledMDB();
        if ( $isEnabledMDB ) {
            $file_list = [];
            $multiple_databases = $this->utils->getConfig('multiple_databases');
            $og_target_db = $event->getOgTargetDb($isEnabledMDB, $multiple_databases);
            if( empty($og_target_db) ){
                $this->utils->error_log('The database does not exist, og_target_db:', $og_target_db);
            }else{
                $_multiple_db=Multiple_db::getSingletonInstance();
                $_multiple_db->switchCIDatabase($og_target_db);
                $sourceDB=$this->utils->getActiveTargetDB();
                $this->utils->debug_log('===================Deposit Event getActiveTargetDB', $sourceDB);
            }
        }

        if (!empty($saleOrderId)) {
            $saleOrder=$this->sale_order->getSaleOrderInfoById($saleOrderId);
        }
        if (!empty($transactionId)) {
            $transaction=$this->transactions->getTransactionInfoById($transactionId);
        }
        if (!empty($playerId)) {
            $player=$this->player_model->getPlayerArrayById($playerId);
        }
        if (!empty($paymentAccountId)) {
            $paymentAccount=$this->payment_account->getPaymentAccountDetails($paymentAccountId);
        }

        //check promotion rules
        $this->utils->debug_log('start process deposit event', $event, $saleOrder, $transaction, $player, $paymentAccount);

        //check payment account deposit limit
        if (!empty($paymentAccount)) {
            $this->checkPaymentAccountTotalAmountForNotification($paymentAccount);
        }

        //check first deposit event
        $this->checkPlayerFirstDeposit($player, $transaction, $saleOrder);
        $this->processTrackingCallback($playerId, $player, $transaction, $saleOrder);

        $this->checkAndSendPlayerInternalMsg($player, $transaction, $saleOrder);

        $this->utils->debug_log('end process deposit event', $event);
    }

    public function checkPaymentAccountTotalAmountForNotification($payment_account)
    {
        $this->load->model(['payment_account']);

        $get_payment_account_over_limit_percentage_result = $this->payment_account->checkIfPaymentAccountOverLimitPercentage($payment_account->id);
        $this->utils->debug_log('========checkPaymentAccountTotalAmountForNotification get_payment_account_over_limit_percentage_result', $get_payment_account_over_limit_percentage_result);
        if (isset($get_payment_account_over_limit_percentage_result['success'])) {
            if ($get_payment_account_over_limit_percentage_result['success']) {
                $url = $this->getConfig('payment_account_notify_url');
                $user = $this->getConfig('payment_account_notify_user');
                $channel = $this->getConfig('payment_account_notify_channel');
                $this->utils->sendMessageService($get_payment_account_over_limit_percentage_result['msg'], $url, $user, $channel);
            }
        }
    }

    public function checkPlayerFirstDeposit($player, $transaction = null, $saleOrder = null)
    {
        $this->load->model(['transactions', 'player_model']);

        $sourceDB=$this->utils->getActiveTargetDB();
        $this->utils->debug_log('===================Deposit Event checkPlayerFirstDeposit getActiveTargetDB', $sourceDB);

        $playerId = $player['playerId'];
        $this->utils->debug_log("============Deposit Event checkPlayerFirstDeposit start============". "playerid:[$playerId]");

        $is_approve = false;
        if (!empty($transaction) && $transaction['transaction_type'] == transactions::DEPOSIT &&$transaction['status'] == transactions::APPROVED) {
            $is_approve = true;
        } else {
            $this->utils->debug_log("============Deposit Event checkPlayerFirstDeposit not approve action============". "playerid:[$playerId]");
            return;
        }

        if (!$this->utils->getConfig('enable_3rd_party_affiliate')){
            $this->utils->debug_log("============3rdparty_affiliate_network not enable============". "playerid:[$playerId]");
            return;
        }
        if (!empty($playerId) && $is_approve) {
            $playerInfo = $this->player_model->getPlayerDetailArrayById($playerId);
            if (!empty($playerInfo['cpaId'])) {
                $aff_source_detail = json_decode($playerInfo['cpaId'], true);
                $this->utils->debug_log("============3rdparty_affiliate_network_detail============", $aff_source_detail, $playerInfo);

                $rec = null;
                if (isset($aff_source_detail['rec'])) {
                    $rec = $aff_source_detail['rec'];
                } elseif (isset($aff_source_detail['pub_id']) || isset($aff_source_detail['esub'])) {
                    $rec = 'adcombo';
                }

                $clickid = isset($aff_source_detail['clickid'])? $aff_source_detail['clickid']: (isset($aff_source_detail['esub'])? $aff_source_detail['esub']: null);
                if (empty($clickid)) {
                    $this->utils->debug_log("============3rdparty_affiliate_network_detail no clickid provide============", $aff_source_detail);
                    return;
                }

                if ($rec && $clickid && $this->utils->getConfig($rec)) {
                    $apiName = $rec.'_api';
                    $classExists = file_exists(strtolower(APPPATH . 'libraries/cpa_api/' . $apiName . ".php"));
                    if(!$classExists){
                        return;
                    }
                    $this->load->library(array('cpa_api/'.$apiName));
                    $trackApi = $this->CI->$apiName;
                    $is_first_deposit = $this->transactions->isOnlyFirstDeposit($playerId);
                    if ($is_first_deposit) {
                        $this->utils->debug_log("============Deposit Event checkPlayerFirstDeposit is first deosit============". "playerid:[$playerId]");
                        $this->utils->debug_log("============3rdparty_affiliate_network_first_deposit_postback============". "clickid:[$clickid]");
    
                        $result_postBack = $trackApi->depositPostBack($clickid, $player, $saleOrder);
                        $this->utils->debug_log('============result_postBackTrackreg============', $result_postBack);  
                    } else {
                        $this->utils->debug_log("============Deposit Event checkPlayerFirstDeposit not first deosit============". "playerid:[$playerId]");
                        $this->utils->debug_log("============3rdparty_affiliate_network_first_deposit_postback============". "clickid:[$clickid]");
                        $result_postBack = $trackApi->everyTimeDepositPostBack($clickid, $player, '', $saleOrder);
                        $this->utils->debug_log('============result_postBackTrackreg============', $result_postBack);  
                    }
                } else {
                    $this->utils->debug_log("============3rdparty_affiliate_network not enable============". "playerid:[$playerId]");
                    return;
                }
            } else {
                $this->utils->debug_log("============Deposit Event checkPlayerFirstDeposit not from 3rdparty affiliate networks============". "playerid:[$playerId]");
            }

        }
        $this->utils->debug_log("============Deposit Event checkPlayerFirstDeposit end============");
    }
    public function processTrackingCallback($playerId, $player, $transaction, $saleOrder) {
        $this->load->library(['player_trackingevent_library']);
        $tracking_info = $this->player_trackingevent_library->getTrackingInfoByPlayerId($playerId);
        $this->utils->debug_log('============processTrackingCallback============ ', $playerId, $tracking_info);
        $this->utils->debug_log('============processTrackingCallback saleOrder============ ', $saleOrder);
        if($tracking_info){
            $recid = $tracking_info['platform_id'];
            $is_approve = false;
            if (!empty($transaction) && $transaction['transaction_type'] == transactions::DEPOSIT &&$transaction['status'] == transactions::APPROVED) {
                $is_approve = true;
            } else {
                return true;
            }
            $is_first_deposit = $this->transactions->isOnlyFirstDeposit($playerId);
            if ($is_first_deposit) {
                $this->player_trackingevent_library->processPaymentSuccess($recid, Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_FIRST_DEPOSIT_SUCCESS, $tracking_info, $playerId, $player, $saleOrder);

            } else {
                $this->player_trackingevent_library->processPaymentSuccess($recid, Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_SUCCESS, $tracking_info, $playerId, $player, $saleOrder);

            }
        }
        if($this->utils->getConfig('third_party_tracking_platform_list')){
            $tracking_list = $this->utils->getConfig('third_party_tracking_platform_list');
            foreach($tracking_list as $key => $val){
                if(isset($val['always_tracking'])){
                    $recid = $key;
                    $is_approve = false;
                    if (!empty($transaction) && $transaction['transaction_type'] == transactions::DEPOSIT &&$transaction['status'] == transactions::APPROVED) {
                        $is_approve = true;
                    } else {
                        return true;
                    }
                    $is_first_deposit = $this->transactions->isOnlyFirstDeposit($playerId);
                    if ($is_first_deposit) {
                        $this->player_trackingevent_library->processPaymentSuccess($recid, Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_FIRST_DEPOSIT_SUCCESS, $tracking_info, $playerId, $player, $saleOrder);

                    } else {
                        $this->player_trackingevent_library->processPaymentSuccess($recid, Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_SUCCESS, $tracking_info, $playerId, $player, $saleOrder);
                    }
                }
            }
        }
    }
    function testDummy3rdPartyNetworkProviderPostback($player, $transaction = null, $saleOrder = null){
        var_dump($player, $transaction, $saleOrder);
        $apiName = 'dummy_track_api';
        $this->load->library(array('cpa_api/'.$apiName));
        $trackApi = $this->CI->$apiName;
        $clickid = 'dummy_track_test';
        $this->utils->debug_log("============3rdparty_affiliate_network_first_deposit_postback============". "clickid:[$clickid]");

        $result_postBack = $trackApi->depositPostBack($clickid, $player, $saleOrder);
        // var_dump($result_postBack);

        $this->utils->debug_log('============result_postBackTrackreg============', $result_postBack);
    }

    public $_player;
    public $_transaction;
    public $_saleOrder;
    function checkAndSendPlayerInternalMsg($player, $transaction = null, $saleOrder = null){
        $this->_player = $player;
        $this->_transaction = $transaction;
        $this->_saleOrder = $saleOrder;
        $this->ruleOGP28227();
    }
    
    function ruleOGP28227(){
        $config = $this->utils->getConfig('OGP28227_setting');
        if(!$this->utils->safeGetArray($config, 'enabled', false)) {
			$this->utils->debug_log('============ruleOGP28227============ not enabled ', ['config' => $config]);
			return;
		}

        if(!(!empty($this->_transaction) && $this->_transaction['transaction_type'] == transactions::DEPOSIT &&$this->_transaction['status'] == transactions::APPROVED)){
			$this->utils->debug_log('============ruleOGP28227============ not approve action ', ['config' => $config]);
            return;
        }

        $playerId = $this->_player['playerId'];
        $totalDeposit = $this->transactions->getPlayerTotalDeposits($playerId);
        $this->utils->debug_log('============ruleOGP28227============', ["playerid"=>$playerId, "totalDeposit"=>$totalDeposit]);
        
        $this->load->library(['player_message_library','authentication']);
        $this->load->model(array('player_model', 'internal_message','users', 'player_trackingevent'));
        $l = $this->utils->safeGetArray($config, 'tier', []);
        if(empty($l)) {return;}
        switch($totalDeposit) {
            case $totalDeposit >= $l['d3']:
                $source_type = player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_D3;
                $mk = 'd3';
                break;
            case $totalDeposit >= $l['d2']:
                $source_type = player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_D2;
                $mk = 'd2';
                break;
            case $totalDeposit >= $l['d1']:
                $source_type = player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_D1;
                $mk = 'd1';
                break;
            default:
                $this->utils->debug_log('============ruleOGP28227============ skip', ["playerid"=>$playerId, "totalDeposit"=>$totalDeposit]);
                return;
        }
        $this->utils->debug_log("============ruleOGP28227============ [$mk]", ["playerid"=>$playerId, "source_type"=>$source_type]);

        $msg = $this->utils->safeGetArray($config, $mk, []);
        $subject = $this->utils->safeGetArray($msg, 'subject', '');
        $message = $this->utils->safeGetArray($msg, 'content', '');

        if(!$this->player_trackingevent->getNotifyBySource($playerId, $source_type) && ($msg && $subject && $message)) {
            $userId 	= 1;
			$msgSenderName = $this->player_message_library->getDefaultAdminSenderName() ?: $this->users->getUsernameById($userId);
            $res = $this->internal_message->addNewMessageAdmin($userId, $playerId, $msgSenderName, $subject, $message, TRUE, TRUE);
            if ($res) {
                $this->player_trackingevent->createSettledNotify($playerId, $source_type, array());
            }
        }
        
    }
}