<?php

require_once dirname(__FILE__) . "/../events/WithdrawalEvent.php";
require_once dirname(__FILE__) . "/AbstractSubscriber.php";

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WithdrawalSubscriber extends AbstractSubscriber implements EventSubscriberInterface{

    public function __construct(){
        parent::__construct();
    }

    public static function getSubscribedEvents(){
        return array(
            Queue_result::EVENT_WITHDRAWAL_AFTER_DB_TRANS => 'afterWithdrawalDBTrans',
        );
    }

    public function afterWithdrawalDBTrans(WithdrawalEvent $event){
        $walletAccountId=$event->getWalletAccountId();
        $transactionId=$event->getTransactionId();
        $playerId=$event->getPlayerId();
        $walletAccount=null;
        $transaction=null;
        $player=null;

        $this->load->model(['wallet_model', 'transactions', 'player_model']);

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

        if(!empty($walletAccountId)){
            $walletAccount=$this->wallet_model->getWalletAccountInfoById($walletAccountId);
        }
        if(!empty($transactionId)){
            $transaction=$this->transactions->getTransactionInfoById($transactionId);
        }  
        if(!empty($playerId)){
            $player=$this->player_model->getPlayerArrayById($playerId);
        }

        $this->utils->debug_log('start process withdrawal event', $event);

        if ($this->utils->getConfig('enabled_withdrawal_abnormal_notification')) {
            $this->checkPlayerPaidWithdrawal($player, $walletAccount, $transaction);
        }
        $this->successNotification($player, $walletAccount, $transaction);
        $this->processTrackingCallback($playerId, $player, $transaction, $walletAccount);

        $this->utils->debug_log('end process withdrawal event', $event);

    }

    public function checkPlayerPaidWithdrawal($player, $walletAccount, $transaction = null){
        $this->load->model(['transactions', 'player_model','payment_abnormal_notification']);

        $this->utils->debug_log(__METHOD__,"player:",$player,"walletAccount:",$walletAccount);

        $result = false;
        if (!empty($player) && !empty($walletAccount)) {
            $result = $this->payment_abnormal_notification->generateWithdrawalAbnormalHistory($player, $walletAccount);
        }

        $this->utils->debug_log(__METHOD__,'result:',$result);
    }
//transactionCode
//amount
    public function successNotification($player, $walletAccount, $transaction = null) {
        $this->load->model(['transactions', 'player_model']);
        $this->utils->debug_log("============3rdparty_affiliate_network withdrawal successNotification start============");

        $playerId = $player['playerId'];
        // $transactionDetail = $controller->withdraw_condition->getWithdrawalTransactionDetail($walletAccountId);
        if (!$this->utils->getConfig('enable_3rd_party_affiliate')){
            $this->utils->debug_log("============3rdparty_affiliate_network not enable============". "playerid:[$playerId]");
            return;
        }
        if (!empty($playerId) && $transaction) {
            $playerInfo = $this->player_model->getPlayerDetailArrayById($playerId);
            if (empty($this->utils->safeGetArray($playerInfo, 'cpaId'))) {
                $this->utils->debug_log("============Not from 3rdparty affiliate networks============". "playerid:[$playerId]");
                return;
            }
            $aff_source_detail = json_decode($playerInfo['cpaId'], true);
            $this->utils->debug_log("============3rdparty_affiliate_network_detail============", $aff_source_detail, $playerInfo);

            $rec = null;
            if ( $this->utils->safeGetArray($aff_source_detail, 'rec') ) {
                $rec = $aff_source_detail['rec'];
            } elseif ($this->utils->safeGetArray($aff_source_detail, 'pub_id') || $this->utils->safeGetArray($aff_source_detail, 'esub')) { 
                $rec = 'adcombo';
            }

            // $clickid = isset($aff_source_detail['clickid'])? $aff_source_detail['clickid']: (isset($aff_source_detail['esub'])? $aff_source_detail['esub']: null);
            $clickid = $this->utils->safeGetArray($aff_source_detail, 'clickid') ?: $this->utils->safeGetArray($aff_source_detail, 'esub', null);
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
                $result_postBack = $trackApi->withdrawalSuccessPostBack($clickid, $player, '', $walletAccount);
                $this->utils->debug_log('============result_postBackTrackreg============', $result_postBack); 

            } else {
                $this->utils->debug_log("============3rdparty_affiliate_network not enable============". "playerid:[$playerId]");
                return;
            }
        }
        $this->utils->debug_log("============3rdparty_affiliate_network withdrawal successNotification end============");
        return;
    }

    public function processTrackingCallback($playerId, $player, $transaction, $walletAccount) {
        $this->load->library(['player_trackingevent_library']);
        $tracking_info = $this->player_trackingevent_library->getTrackingInfoByPlayerId($playerId);
        $this->utils->debug_log('============processTrackingCallback============ ', $playerId, $tracking_info);
        $this->utils->debug_log('============processTrackingCallback walletAccount============ ', $walletAccount);
        if($tracking_info){
            $recid = $tracking_info['platform_id'];
            $is_approve = false;
            if (!empty($transaction) && $transaction['transaction_type'] == transactions::WITHDRAWAL && $transaction['status'] == transactions::APPROVED) {
                $is_approve = true;
            } else {
                return true;
            }
            $this->player_trackingevent_library->processWithdrawalSuccess($recid, Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_SUCCESS, $tracking_info, $playerId, $player, $walletAccount);           
        }
        if($this->utils->getConfig('third_party_tracking_platform_list')){
            $tracking_list = $this->utils->getConfig('third_party_tracking_platform_list');
            foreach($tracking_list as $key => $val){
                if(isset($val['always_tracking'])){
                    $recid = $key;
                    $is_approve = false;
                    if (!empty($transaction) && $transaction['transaction_type'] == transactions::WITHDRAWAL && $transaction['status'] == transactions::APPROVED) {
                        $is_approve = true;
                    } else {
                        return true;
                    }
                    $this->player_trackingevent_library->processWithdrawalSuccess($recid, Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_SUCCESS, $tracking_info, $playerId, $player, $walletAccount);
                }
            }
        }
    }
}
