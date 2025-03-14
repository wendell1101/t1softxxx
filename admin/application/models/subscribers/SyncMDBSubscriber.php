<?php

require_once dirname(__FILE__) . "/../events/SyncMDBEvent.php";
require_once dirname(__FILE__) . "/AbstractSubscriber.php";

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SyncMDBSubscriber extends AbstractSubscriber implements EventSubscriberInterface{

    public function __construct(){
        parent::__construct();
        $this->utils->info_log('load subscriber class', get_class());
    }

    public static function getSubscribedEvents(){
        return array(
            Queue_result::EVENT_SYNC_MDB => 'syncMDB',
        );
    }

    protected function getSuccessFromRlt($rlt){
        $success=false;
        if(!empty($rlt)){
            foreach ($rlt as $key => $dbRlt) {
                $success=$dbRlt['success'];
                if(!$success){
                    break;
                }
            }
        }
        return $success;
    }

    public function syncMDB(SyncMDBEvent $event){
        // ...
        $this->utils->debug_log('SyncMDBEvent', $event);

        if(!$this->utils->isEnabledMDB()){
            return $this->appendResult($event, ['enabled_mdb'=>false], true, true);
        }

        $this->load->model(['multiple_db_model']);
        $insertOnly=false;
        $success=false;
        $agent_id=$event->getAgentId();
        if(isset($agent_id) && !empty($agent_id) && !empty($event->getAgentLockUniqueName())){
            $success=$this->utils->globalLockSyncAgency($event->getAgentLockUniqueName(), function ()
                    use ($agent_id, $insertOnly, &$rlt) {
                $rlt=$this->multiple_db_model->syncAgencyFromCurrentToOtherMDB($agent_id, $insertOnly);
                return $this->getSuccessFromRlt($rlt);
            });

            $this->utils->debug_log('syncAgencyFromCurrentToOtherMDB :'.$agent_id, $rlt);
            $done=!$success; $failed=!$success;
            $this->appendResult($event, ['syncAgencyFromCurrentToOtherMDB'=>$rlt], $done, $failed);
            if($failed){
                $this->utils->error_log('syncAgencyFromCurrentToOtherMDB failed', $rlt);
                return false;
            }
        }

        $affiliate_id=$event->getAffiliateId();
        if(isset($affiliate_id) && !empty($affiliate_id) && !empty($event->getAffiliateLockUniqueName())){
            $success=$this->utils->globalLockSyncAffiliate($event->getAffiliateLockUniqueName(), function ()
                    use ($affiliate_id, $insertOnly, &$rlt) {
                $rlt=$this->multiple_db_model->syncAffFromCurrentToOtherMDB($affiliate_id, $insertOnly);
                return $this->getSuccessFromRlt($rlt);
            });

            $this->utils->debug_log('syncAffFromCurrentToOtherMDB :'.$affiliate_id, $rlt);
            $done=!$success; $failed=!$success;
            $this->appendResult($event, ['syncAffFromCurrentToOtherMDB'=>$rlt], $done, $failed);
            if($failed){
                $this->utils->error_log('syncAffFromCurrentToOtherMDB failed', $rlt);
                return false;
            }
        }

        $admin_user_id=$event->getAdminUserId();
        if(isset($admin_user_id) && !empty($admin_user_id) && !empty($event->getAdminUserLockUniqueName())){
            $success=$this->utils->globalLockSyncUser($event->getAdminUserLockUniqueName(), function ()
                    use ($admin_user_id, $insertOnly, &$rlt) {
                $rlt=$this->multiple_db_model->syncUserFromCurrentToOtherMDB($admin_user_id, $insertOnly);
                return $this->getSuccessFromRlt($rlt);
            });

            $this->utils->debug_log('syncUserFromCurrentToOtherMDB :'.$admin_user_id, $rlt);
            $done=!$success; $failed=!$success;
            $this->appendResult($event, ['syncUserFromCurrentToOtherMDB'=>$rlt], $done, $failed);
            if($failed){
                $this->utils->error_log('syncUserFromCurrentToOtherMDB failed', $rlt);
                return false;
            }
        }

        $role_id=$event->getRoleId();
        if(isset($role_id) && !empty($role_id) && !empty($event->getRoleLockUniqueName())){
            $success=$this->utils->globalLockSyncRole($event->getRoleLockUniqueName(), function ()
                    use ($role_id, $insertOnly, &$rlt) {
                $rlt=$this->multiple_db_model->syncRoleFromCurrentToOtherMDB($role_id, $insertOnly);
                return $this->getSuccessFromRlt($rlt);
            });

            $this->utils->debug_log('syncRoleFromCurrentToOtherMDB :'.$role_id, $rlt);
            $done=!$success; $failed=!$success;
            $this->appendResult($event, ['syncRoleFromCurrentToOtherMDB'=>$rlt], $done, $failed);
            if($failed){
                $this->utils->error_log('syncRoleFromCurrentToOtherMDB failed', $rlt);
                return false;
            }
        }

        $player_id=$event->getPlayerId();
        if(isset($player_id) && !empty($player_id) && !empty($event->getPlayerLockUniqueName())){
            $success=$this->utils->globalLockSyncPlayer($event->getPlayerLockUniqueName(), function ()
                    use ($player_id, $insertOnly, &$rlt) {
                $rlt=$this->multiple_db_model->syncPlayerFromCurrentToOtherMDB($player_id, $insertOnly);
                return $this->getSuccessFromRlt($rlt);
            });

            $this->utils->debug_log('syncPlayerFromCurrentToOtherMDB :'.$player_id, $rlt);
            $done=!$success; $failed=!$success;
            $this->appendResult($event, ['syncPlayerFromCurrentToOtherMDB'=>$rlt], $done, $failed);
            if($failed){
                $this->utils->error_log('syncPlayerFromCurrentToOtherMDB failed', $rlt);
                return false;
            }
        }
        if(isset($player_id) && !empty($player_id) && !empty($event->getPlayerlevelLockUniqueName())){
            $sourceDB = $event->getSourceCurrency();
            $_trigge_method = $event->getTriggerMethod();
            $success=$this->utils->globalLockSyncPlayer($event->getPlayerlevelLockUniqueName(), function ()
                    use ($player_id, $sourceDB, $_trigge_method, $insertOnly, &$rlt) {
                $rlt=$this->multiple_db_model->syncPlayerVipLevelFromCurrentToOtherMDB($player_id, $sourceDB, $_trigge_method, $insertOnly);
                $this->utils->debug_log('globalLockSyncPlayer.syncPlayerVipLevelFromCurrentToOtherMDB :'.$player_id, $rlt);
                return $this->getSuccessFromRlt($rlt);
            });

            $this->utils->debug_log('syncPlayerVipLevelFromCurrentToOtherMDB :'.$player_id, $rlt);
            $done=!$success; $failed=!$success;
            $this->appendResult($event, ['syncPlayerVipLevelFromCurrentToOtherMDB'=>$rlt], $done, $failed);
            if($failed){
                $this->utils->error_log('syncPlayerVipLevelFromCurrentToOtherMDB failed', $rlt);
                return false;
            }
        }

        $_isEnable4syncVipGroup2others = $this->utils->_getSyncVipGroup2othersWithMethod(__METHOD__);
        $vipsettingId=$event->getVipsettingId();
        $dryrun_in_vipsettingid=$event->getDryrunInVipsettingid();
        $extra_info=$event->getExtraInfo();

        $this->utils->debug_log('Run by syncVIPGroupFromOneToOtherMDBWithFixPKidVer2 in 153.syncMDB().'
                                    , 'vipsettingId:', $vipsettingId
                                    , 'dryrun_in_vipsettingid:', $dryrun_in_vipsettingid
                                    , 'DRY_RUN_MODE_IN_DISABLED:', Multiple_db_model::DRY_RUN_MODE_IN_DISABLED // Execute syncing
                                    , '_isEnable4syncVipGroup2others:', $_isEnable4syncVipGroup2others
                                );

        if( $dryrun_in_vipsettingid == Multiple_db_model::DRY_RUN_MODE_IN_DISABLED // Execute syncing
            && ! $_isEnable4syncVipGroup2others
        ){ // forced to dryrun by disable Sync VipGroup to others
            $dryrun_in_vipsettingid = Multiple_db_model::DRY_RUN_MODE_IN_NORMAL; // dry run
            $this->utils->debug_log('Forced to dryrun by sync_vip_group2others_method_list in syncMDB().'
                                    , 'sourceDB:', $sourceDB
                                    , 'vipsettingId:', $vipsettingId
                                );
        }
        if(isset($vipsettingId) && !empty($vipsettingId) && !empty($event->getVipsettingIdLockUniqueName())){

            $_this = $this;
            $completedCB4OEOM = function($_progress, $_total, $_extra) use ($_this, $event) {
                $_done = false; // set done on appendResult()
                $error=false;
                $download_filelink=null;
                $extra = $_extra;
                $_this->updateFinalResult($event, $_progress, $_total, $_done, $error, $download_filelink, $extra);
            };
            $sourceDB = $event->getSourceCurrency();
            $_successFromRlt = null;
            $success=$this->utils->globalLockVipsettingId($event->getVipsettingIdLockUniqueName(), function ()
                    use ($vipsettingId, $sourceDB, $insertOnly, &$rlt, $dryrun_in_vipsettingid, $extra_info, $completedCB4OEOM, &$_successFromRlt ) {

                $insertOnly=false;
                $dryRun = $dryrun_in_vipsettingid;
                //$extra_info ignore for use vipsettingId to sunc to others
                $others_in = 'all'; // as default
                $rlt=$this->multiple_db_model->syncVIPGroupFromOneToOtherMDBWithFixPKidVer2( $sourceDB // #1
                                                                                        , $vipsettingId  // #2 aka. vipSettingId
                                                                                        , $insertOnly // #3
                                                                                        , $dryRun  // #4
                                                                                        , $others_in // #5
                                                                                        , $completedCB4OEOM // #6
                                                                                    );

                // $rlt=$this->multiple_db_model->syncPlayerVipLevelFromCurrentToOtherMDB($player_id, $sourceDB, $_trigge_method, $insertOnly);
                $this->utils->debug_log('globalLockVipsettingId.getVipsettingIdLockUniqueName :'.$vipsettingId, $rlt);

                $_successFromRlt = $this->getSuccessFromRlt($rlt);
                return $_successFromRlt;
            });

            $success=$_successFromRlt;
            $done=!$success; $failed=!$success;
            $this->utils->debug_log('syncVIPGroupFromOneToOtherMDBWithFixPKidVer2.failed :'.$vipsettingId, $failed);
            $this->utils->debug_log('syncVIPGroupFromOneToOtherMDBWithFixPKidVer2 :'.$vipsettingId, $rlt);
            $event->setQueueResult($rlt);
            if($failed){
                $event->setError($failed);
                $this->utils->error_log('syncVIPGroupFromOneToOtherMDBWithFixPKidVer2 failed', $rlt);
                return false;
            }

        }

        $reg_setting_type=$event->getRegSettingType();
        if(isset($reg_setting_type) && !empty($reg_setting_type) && !empty($event->getRegSettingLockUniqueName())){
            $success=$this->utils->globalLockSyncRegistrationSettings($event->getRegSettingLockUniqueName(), function ()
                    use ($reg_setting_type, $insertOnly, &$rlt) {
                $rlt=$this->multiple_db_model->syncPlayerRegSettingsFromCurrentToOtherMDB($reg_setting_type, $insertOnly);
                return $this->getSuccessFromRlt($rlt);
            });

            $this->utils->debug_log('syncPlayerRegSettingsFromCurrentToOtherMDB :'.$reg_setting_type, $rlt);
            $done=!$success; $failed=!$success;
            $this->appendResult($event, ['syncPlayerRegSettingsFromCurrentToOtherMDB'=>$rlt], $done, $failed);
            if($failed){
                $this->utils->error_log('syncPlayerRegSettingsFromCurrentToOtherMDB failed', $rlt);
                return false;
            }
        }

        $this->appendResult($event, ['done'=>true], true, $success);

    }

}
