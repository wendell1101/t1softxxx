<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';
require_once dirname(__FILE__) . '/reports/define_summary2_report.php';
require_once dirname(__FILE__) . '/reports/define_player_report.php';
require_once dirname(__FILE__) . '/reports/define_payment_report.php';
require_once dirname(__FILE__) . '/reports/define_game_report.php';
require_once dirname(__FILE__) . '/reports/define_promotion_report.php';
require_once dirname(__FILE__) . '/reports/define_cashback_report.php';

/**
 * Class Multiple_db_model
 *
 * sync mdb
 *
 * @property Wallet_model $wallet_model
 * @property Game_tags $game_tags
 * @property Player_model $player_model
 * @property Game_type_model $game_type_model
 */
class Multiple_db_model extends BaseModel {

    use define_summary2_report;
    use define_player_report;
    use define_payment_report;
    use define_game_report;
    use define_promotion_report;
    use define_cashback_report;

    const SUPER_REPORT_TYPE_SUMMARY2='summary2';
    const SUPER_REPORT_TYPE_PLAYER='player';
    const SUPER_REPORT_TYPE_GAME='game';
    const SUPER_REPORT_TYPE_PAYMENT='payment';
    const SUPER_REPORT_TYPE_PORMOTION='promotion';
    const SUPER_REPORT_TYPE_CASHBACK='cashback';

    const DRY_RUN_MODE_IN_DISABLED = 0;
    const DRY_RUN_MODE_IN_NORMAL = 1;
    const DRY_RUN_MODE_IN_INCREASED_LEVELS = 2;
    const DRY_RUN_MODE_IN_DECREASED_LEVELS = 3;

    /// INCREASED/DECREASED VIP Group Level in current DB, then sync to others
    // the below items mean it will be execute sync( with P.K.id )
    const DRY_RUN_MODE_IN_DISABLED_NORMAL = 4;
    const DRY_RUN_MODE_IN_DISABLED_INCREASED_LEVELS = 5;
    const DRY_RUN_MODE_IN_DISABLED_DECREASED_LEVELS = 6;

    const DRY_RUN_MODE_IN_ADD_GROUP = 7;
    const DRY_RUN_MODE_IN_DISABLED_ADD_GROUP = 8;

    const GET_LEVELS_VIA_PK_MODE = 0;
    const GET_LEVELS_VIA_FK_MODE = 1;
    const GET_LEVELS_VIA_BOTH_MODE= 2;

    /// only referenced to the method, syncVIPGroupFromOneToOtherMDBWithFixPKidVer2()
    const doUpdate4None2LevelOfOther = 0;
    const doUpdate4Overwrite2LevelOfOther = 1;
    const doUpdate4SoftDelete2LevelOfOther = 2;

    // for result code in soft_deleted_rows.
    const code4IgnoreInEmptySoftDeletedRows = 0;
    const code4NoSoftDeletedRowToDelete = 1;
    const code4AllSoftDeletedRowsDeleted = 2;
    const code4SomeSoftDeletedRowsNotExistButOthersDeleted = 3;

    public $soft_deleted_rows = []; // for vipsettingcashbackrule table

    protected function _clear4soft_deleted_rows(){
        $this->soft_deleted_rows = [];
    }
    /**
     * Remove soft_deleted_row form The property,"soft_deleted_rows" By vipsettingcashbackruleId
     *
     * @param integer $vipsettingcashbackruleId
     * @return boolean If true, thats mean remove completed.
     */
    protected function _remove4soft_deleted_rowsById($vipsettingcashbackruleId){
        $is_removed = false;
        if( !empty($this->soft_deleted_rows) ){
            $_vipsettingcashbackruleId_list = array_column($this->soft_deleted_rows, 'vipsettingcashbackruleId');
            foreach($this->soft_deleted_rows as $_index => $_row){
                if($_row['vipsettingcashbackruleId'] == $vipsettingcashbackruleId){
                    array_splice($this->soft_deleted_rows, $_index, 1);
                    $is_removed = true;
                    break;
                }
            }
        }
        return $is_removed;
    } // EOF _remove4soft_deleted_rowsById()
    /**
     * Store soft_deleted_row
     *
     * @param array $soft_deleted_row The row must has the key,"vipsettingcashbackruleId".
     * @return array The property array,"soft_deleted_rows" .
     */
    protected function _add2soft_deleted_rows($soft_deleted_row){
        $do_array_push = false;
        if(!empty($soft_deleted_row['vipsettingcashbackruleId']) ){
            if( !empty($this->soft_deleted_rows) ){
                $_vipsettingcashbackruleId_list = array_column($this->soft_deleted_rows, 'vipsettingcashbackruleId');
                $in_array = in_array($soft_deleted_row['vipsettingcashbackruleId'], $_vipsettingcashbackruleId_list );
                if(!$in_array){
                    $do_array_push = true;
                }else{
                    $do_array_push = false;
                }
            }else{
                $do_array_push = true;
            }
        }

        if($do_array_push){
            array_push($this->soft_deleted_rows, $soft_deleted_row);
        }
        return $this->soft_deleted_rows;
    } // EOF _add2soft_deleted_rows()
    //
    /**
     * Real delete the rows with soft_deleted(, deleted=1)
     *
     * @param CI_DB_driver $db
     * @return array $return The format as,
     * - @.success bool Its true for any one more updated, Or empty soft deleted row.
     * - @.code integer For caller to referenced.
     * - @.msg message The intro of the result.
     * - @.results array More details.
     * - @.results.deleted_id_list array The deleted id list
     * - @.results.no_affected_id_list array The No affected id list, and them should be deleted.
     * - @.results.affected_rows_amount integer The amount of the affected row(s).
     *
     */
    protected function _delete4soft_deleted_rows($db = null){
        if( empty($db) ){
            $db = $this->db;
        }
        $isEmpty4soft_deleted_rows = empty($this->soft_deleted_rows);
        $return = [];
        $return['success'] = null;

        $return['msg'] = '';
        $return['results'] = [];
        $return['results']['deleted_id_list'] = [];
        $return['results']['no_affected_id_list'] = [];

        $return['results']['affected_rows_amount'] = 0;
        $affected_rows_amount = 0;

        if( !empty($this->soft_deleted_rows) ){
            $_vipsettingcashbackruleId_list = array_column($this->soft_deleted_rows, 'vipsettingcashbackruleId');
            // one by one
            foreach($_vipsettingcashbackruleId_list as $_id){
                $db->where('vipsettingcashbackruleId', $_id);
                $db->delete('vipsettingcashbackrule');
                $affected_rows = $db->affected_rows();
                if($affected_rows > 0){
                    array_push($return['results']['deleted_id_list'], $_id);
                    $this->_remove4soft_deleted_rowsById($_id);
                    $affected_rows_amount++;
                }else{
                    array_push($return['results']['no_affected_id_list'], $_id);
                }
            }
        }
        $return['results']['affected_rows_amount'] = $affected_rows_amount;

        // true for any one more updated Or empty soft deleted row.
        if(!empty($return['results']['deleted_id_list'])
            || $isEmpty4soft_deleted_rows
        ){
            $return['success'] = true;
        }else{
            $return['success'] = false;
        }

        if( $isEmpty4soft_deleted_rows ){
            $return['code'] = Multiple_db_model::code4IgnoreInEmptySoftDeletedRows; /// 0
            $return['msg'] = 'Ignore by empty soft_deleted_rows.';
        }else if(empty($return['results']['deleted_id_list'])){
            $return['code'] = Multiple_db_model::code4NoSoftDeletedRowToDelete; /// 1
            $return['msg'] = 'There is No soft-deleted row has been deleted';
        }else if(count($return['results']['deleted_id_list']) == $affected_rows_amount){
            $return['code'] = Multiple_db_model::code4AllSoftDeletedRowsDeleted; /// 2
            $return['msg'] = 'All soft-deleted rows had been deleted';
        }else{
            $return['code'] = Multiple_db_model::code4SomeSoftDeletedRowsNotExistButOthersDeleted; /// 3
            $return['msg'] = 'Some soft-deleted rows already Not exist, and others had been deleted.';
        }

        return $return;
    }// EOF _delete4soft_deleted_rows()

    //===sync mdb=================================================================================
    //sync to other db, so doesn't work on same db transaction

    public function syncRoleFromSuperToOtherMDB($roleId, $insertOnly=false){
        return $this->syncRoleFromOneToOtherMDB(Multiple_db::SUPER_TARGET_DB, $roleId, $insertOnly);
    }

    public function syncRoleFromCurrentToOtherMDB($roleId, $insertOnly=false){
        $sourceDB=$this->getActiveTargetDB();
        return $this->syncRoleFromOneToOtherMDB($sourceDB, $roleId, $insertOnly);
    }

    /**
     * sync role
     * @param  string $roleName
     * @return array
     */
    public function syncRoleFromOneToOtherMDB($sourceDB, $roleId, $insertOnly=false){
        $result=null;
        if($this->utils->isEnabledMDB() && !empty($roleId)){
            // $sourceDB=$this->getActiveTargetDB();
            $roleFuncList=null;
            $roleFuncGivingList=null;
			$functionsReportFieldsList = [];
			$genealogy = [];

            //get db from super
            $role=$this->runAnyOnSingleMDB($sourceDB,function($db)
                    use($roleId, &$roleFuncList, &$roleFuncGivingList, &$functionsReportFieldsList, &$genealogy){
                $db->from('roles')->where('roleId', $roleId);
                $role=$this->runOneRowArray($db);
                if(!empty($role)){
                    $db->from('rolefunctions')->where('roleId', $role['roleId']);
                    $rows=$this->runMultipleRowArray($db);
                    $roleFuncList=[];
                    if(!empty($rows)){
                        foreach ($rows as $row) {
                            $roleFuncList[]=$row['funcId'];
                        }
                    }
                    $db->select('funcId')->from('rolefunctions_giving')->where('roleId', $role['roleId']);
                    $rows=$this->runMultipleRowArray($db);
                    $roleFuncGivingList=[];
                    if(!empty($rows)){
                        foreach ($rows as $row) {
                            $roleFuncGivingList[]=$row['funcId'];
                        }
                    }

                    //OGP-33137 sync the permission of the report field
                    $db->select('funcCode,fields')->from('functions_report_field')->where('roleId', $role['roleId']);
                    $rows=$this->runMultipleRowArray($db);
                    $functionsReportField=[];
                    if(!empty($rows)){
                        foreach ($rows as $row) {
                            $functionsReportFieldsList[] = [
                                'funcCode' => $row['funcCode'],
                                'fields' => $row['fields']
                            ];
                        }
                    }

                    $db->select('genealogyId,generation,gene,roleId')->from('genealogy')->where('roleId',$role['roleId']);
                    
                    $rows = $this->runMultipleRowArray($db);
                    $genealogy = [];
                    if(!empty($rows)) {
                        foreach ($rows as $row) {
                            $genealogy[] = [
                                'roleId' => $role['roleId'], 
                                'genealogyId' => $row['genealogyId'],
                                'generation' => $row['generation'],
                                'gene' => $row['gene'],
							];
						}
					}
				}

                return $role;
            });
            if(!empty($role)){

                $roleId=$role['roleId'];
                $active_on_other_mdb=$this->activeItOnOtherMDB('role');

                //copy role and func
                // unset($role['roleId']);
                unset($role['status']);
                $this->utils->debug_log('roleId: '.$roleId, $role, $active_on_other_mdb);
                $result=$this->foreachMultipleDBWithoutSourceDB($sourceDB, function($db, &$rlt)
                        use($insertOnly, $roleId, $role, $roleFuncList, $roleFuncGivingList, $active_on_other_mdb, $functionsReportFieldsList, $genealogy){
                    //update other db
                    $db->select('roleId')->from('roles')->where('roleId', $roleId);
                    $roleId=$this->runOneRowOneField('roleId', $db);
                    $success=false;
                    $inserted=false;
                    if(!empty($roleId)){
                        if(!$insertOnly){
                            $db->where('roleId', $roleId);
                            //update
                            $success=$this->runUpdateData('roles', $role, $db);
                        }else{
                            $success=true;
                        }
                    }else{
                        if($active_on_other_mdb){
                            $role['status']=self::OLD_STATUS_ACTIVE;
                        }
                        //insert with id
                        $roleId=$this->runInsertData('roles', $role, $db);
                        $success=!empty($roleId);
                        $inserted=true;
                    }
                    //if insert only and inserted, or not insert only,  change this
                    if($success && !empty($roleFuncList) && (($inserted && $insertOnly) || !$insertOnly)){
                        //try update role func list
                        //delete first
                        //then insert all
                        $db->where('roleId', $roleId);
                        $this->runRealDelete('rolefunctions', $db);
                        $data=[];
                        foreach ($roleFuncList as $funcId) {
                            $data[]=['roleId'=>$roleId, 'funcId'=>$funcId];
                        }
                        //batch insert
                        $db->insert_batch('rolefunctions', $data);
                        //giving table
                        $db->where('roleId', $roleId);
                        $this->runRealDelete('rolefunctions_giving', $db);
                        if(!empty($roleFuncGivingList)){
                            $data=[];
                            foreach ($roleFuncGivingList as $funcId) {
                                $data[]=['roleId'=>$roleId, 'funcId'=>$funcId];
                            }
                            //batch insert
                            $db->insert_batch('rolefunctions_giving', $data);
                        }
                    }

                    if($this->config->item('enable_roles_report',false) && $success && (($inserted && $insertOnly) || !$insertOnly)) {
                        $db->where('roleId', $roleId);
						$this->runRealDelete('functions_report_field', $db);
                        $data = [];
                        foreach ($functionsReportFieldsList as $value) {
                            $data[] = ['roleId'=>$roleId, 'funcCode' => $value['funcCode'], 'fields' => $value['fields']];
                        }
						//batch insert
                        $result = $db->insert_batch('functions_report_field', $data);
                    }

                    if ($success && (($inserted && $insertOnly) || !$insertOnly)) {
                        $db->where('roleId', $roleId);
                        $this->runRealDelete('genealogy', $db);

                        $data = [];
                        foreach ($genealogy as $value) {
                            $data[] = [
                                'roleId' => $roleId, 
                                'genealogyId' => $value['genealogyId'],
                                'generation' => $value['generation'],
                                'gene' => $value['gene'],

                            ];
                        }
						//batch insert
                        $result = $db->insert_batch('genealogy', $data);
                    }

                    $rlt=$roleId;

                    return $success;
                });
            }else{
                $this->utils->error_log('not found role by roleId: '.$roleId);
            }
        }

        return $result;
    }

    public function syncUserFromCurrentToOtherMDB($userId, $insertOnly=false){
        $sourceDB=$this->getActiveTargetDB();
        return $this->syncUserFromOneToOtherMDB($sourceDB, $userId, $insertOnly);
    }

    public function syncUserFromSuperToOtherMDB($userId, $insertOnly=false){
        return $this->syncUserFromOneToOtherMDB(Multiple_db::SUPER_TARGET_DB, $userId, $insertOnly);
    }

    /**
     * sync user on MDB, super to others
     * @param  string $userId
     * @return
     */
    public function syncUserFromOneToOtherMDB($sourceDB, $userId, $insertOnly=false){

        $result=null;
        if($this->utils->isEnabledMDB() && !empty($userId)){
            // $sourceDB=$this->getActiveTargetDB();
            //get db from active
            $roleId=null;
            $user=$this->runAnyOnSingleMDB($sourceDB, function($db)
                    use($userId, &$roleId){
                $db->from('adminusers')->where('userId', $userId);
                $user=$this->runOneRowArray($db);
                if(!empty($user)){
                    //get role
                    $db->select('userroles.roleId')->from('userroles')
                        ->where('userroles.userId', $user['userId'])->limit(1);
                    $roleId=$this->runOneRowOneField('roleId', $db);
                }
                return $user;
            });

            if(!empty($user) && !empty($roleId)){
                //sync role first
                //just make sure the id is exist
                $this->syncRoleFromOneToOtherMDB($sourceDB, $roleId, true);

                $active_on_other_mdb=$this->activeItOnOtherMDB('user');
                $this->clearFieldsOnData($user, ['maxWidAmt','approvedWidAmt','singleWidAmt',]);

                // $onlySyncFields=$this->getSyncFieldsByTableName('adminusers');
                // $user=$this->cleanSyncFields($user, $onlySyncFields);
                $result=$this->foreachMultipleDBWithoutSourceDB($sourceDB, function($db, &$rlt)
                        use($insertOnly, $userId, $user, $roleId, $active_on_other_mdb){
                    //update other db
                    $db->select('userId')->from('adminusers')->where('userId', $userId);
                    $userId=$this->runOneRowOneField('userId', $db);
                    $success=false;
                    $inserted=false;
                    if(!empty($userId)){
                        if(!$insertOnly){
                            $db->where('userId', $userId);
                            //update
                            $success=$this->runUpdateData('adminusers', $user, $db);
                        }else{
                            $success=true;
                        }
                    }else{
                        //init status
                        if(!$active_on_other_mdb){
                            $user['status']=self::DB_TRUE;
                        }
                        //insert
                        $userId=$this->runInsertData('adminusers', $user, $db);
                        $success=!empty($userId);
                        $inserted=true;
                    }
                    //sync userroles
                    if($success && (($inserted && $insertOnly) || !$insertOnly)){
                        //try set role id by roleName
                        //delete and insert
                        $db->where('userId', $userId);
                        $this->runRealDelete('userroles', $db);
                        //insert
                        $success=$this->runInsertData('userroles', ['userId'=>$userId, 'roleId'=>$roleId], $db);
                    }
                    $rlt=$userId;

                    return $success;
                });
            }else{
                $this->utils->error_log('not found user by userId: '.$userId.' or roleId: '.$roleId);
            }
        }

        return $result;
    }

    public function syncPlayerFromCurrentToOtherMDB($playerId, $insertOnly=false){
        $sourceDB=$this->getActiveTargetDB();
        return $this->syncPlayerFromOneToOtherMDB($sourceDB, $playerId, $insertOnly);
    }

    public function syncPlayerFromSuperToOtherMDB($playerId, $insertOnly=false){
        return $this->syncPlayerFromOneToOtherMDB(Multiple_db::SUPER_TARGET_DB, $playerId, $insertOnly);
    }

    /**
     * sync player from super to others
     * @param  string $playerId
     * @return array of bool
     */
    public function syncPlayerFromOneToOtherMDB($sourceDB, $playerId, $insertOnly=false){
        $result=null;
        if($this->utils->isEnabledMDB() && !empty($playerId)){
            //get db from super
            $player=$this->runAnyOnSingleMDB($sourceDB,function($db)
                    use($playerId){
                $db->from('player')->where('playerId', $playerId);
                return $this->runOneRowArray($db);
            });
            if(!empty($player)){

                $playerId=$player['playerId'];
                list($playerDetails, $updateHistoryList, $invitedPlayer, $playernotes, $line_players, $facebook_players, $google_players, $player_attached_proof_file)=$this->runAnyOnSingleMDB($sourceDB, function($db)
                        use($playerId){
                    $db->from('playerdetails')->where('playerId', $playerId);
                    $details=$this->runOneRowArray($db);
                    $db->from('playerupdatehistory')->where('playerId', $playerId);
                    $history=$this->runMultipleRowArray($db);
                    //sync be invited player id
                    $db->from('playerfriendreferral')->where('invitedPlayerId', $playerId);
                    $invitedPlayer=$this->runOneRowArray($db);

                    $db->from('playernotes')->where('playerId', $playerId);
                    $playernotes=$this->runMultipleRowArray($db);

                    $db->from('line_players')->where('player_id', $playerId);
                    $line_players=$this->runOneRowArray($db);

                    $db->from('facebook_players')->where('player_id', $playerId);
                    $facebook_players=$this->runOneRowArray($db);

                    $db->from('google_players')->where('player_id', $playerId);
                    $google_players=$this->runOneRowArray($db);

                    // for avatar
                    $db->from('player_attached_proof_file')->where('player_id', $playerId)->where('tag', 'profile');
                    $_player_attached_proof_file=$this->runOneRowArray($db);

                    return [$details, $history, $invitedPlayer, $playernotes, $line_players, $facebook_players, $google_players, $_player_attached_proof_file];
                });
                $active_on_other_mdb=$this->activeItOnOtherMDB('player');

                //sync aff and agent
                if(!empty($player['agent_id'])){
                    //just make sure the id is exist
                    $this->syncAgencyFromOneToOtherMDB($sourceDB, $player['agent_id'], true);
                }
                if(!empty($player['affiliateId'])){
                    //just make sure the id is exist
                    $this->syncAffFromOneToOtherMDB($sourceDB, $player['affiliateId'], true);
                }

                $this->clearFieldsOnData($player, ['approved_deposit_count', 'declined_deposit_count', 'total_deposit_count', 'totalBettingAmount',
                    'totalDepositAmount', 'session_id', 'deleted_at', 'approvedWithdrawCount', 'approvedWithdrawAmount',
                    'big_wallet', 'main_real', 'main_bonus', 'main_cashback', 'main_win_real', 'main_win_bonus', 'main_withdrawable', 'main_total_nofrozen',
                    'main_total', 'total_real', 'total_bonus', 'total_cashback', 'total_win_real', 'total_win_bonus', 'total_withdrawable', 'total_frozen',
                    'total_total_nofrozen', 'total_total',]);
                // remove 'refereePlayerId', OGP-34438

                //copy player playerdetails
                // $onlySyncFieldsOnPlayer=$this->getSyncFieldsByTableName('player');
                // $onlySyncFieldsOnPlayerDetails=$this->getSyncFieldsByTableName('playerdetails');
                $this->clearFieldsOnData($playerDetails, ['playerDetailsId']);

                $this->utils->debug_log('playerId: '.$playerId, $player, $playerDetails,
                    $active_on_other_mdb);

                // $player=$this->cleanSyncFields($player, $onlySyncFieldsOnPlayer);
                // $playerDetails=$this->cleanSyncFields($playerDetails, $onlySyncFieldsOnPlayerDetails);
                $result=$this->foreachMultipleDBWithoutSourceDB($sourceDB, function($db, &$rlt)
                            use($sourceDB, $insertOnly, $playerId, $player, $active_on_other_mdb,
                            $playerDetails, $updateHistoryList, $invitedPlayer, $playernotes, $line_players, $facebook_players, $google_players, $player_attached_proof_file){
                    //update other db
                    $db->select('playerId')->from('player')->where('playerId', $playerId);
                    $playerId=$this->runOneRowOneField('playerId', $db);
                    $success=false;
                    $inserted=false;
                    $currencyInfo=$this->getCurrencyByDB($db);
                    $player['currency']=$currencyInfo['code'];

                    unset($player['levelId']);
                    unset($player['levelName']);
                    unset($player['groupName']);

                    if(!empty($playerId)){
                        if(!$insertOnly){
                            $db->where('playerId', $playerId);
                            //update
                            $success=$this->runUpdateData('player', $player, $db);
                        }else{
                            $success=true;
                        }
                    }else{
                        if(!$active_on_other_mdb){
                            $player['status']=self::OLD_STATUS_ACTIVE;
                        }
                        //set default language
                        $playerDetails['language']=$currencyInfo['player_default_language'];
                        //insert
                        $playerId=$this->runInsertData('player', $player, $db);
                        $success=!empty($playerId);
                        if($success){
                            //don't check lock
                            //update wallet to empty
                            $this->wallet_model->refreshBigWalletOnDB($playerId, $db, false);
                            //set level
                            $levelId = $currencyInfo['player_default_level_id'];

                            $this->runInsertData('playerlevel', array(
                                'playerId' => $playerId,
                                'playerGroupId' => $levelId,
                            ), $db);

                            $db->select('vipsettingcashbackrule.vipLevelName,vipsetting.groupName')->from('vipsettingcashbackrule')
                                ->join('vipsetting', 'vipsettingcashbackrule.vipSettingId = vipsetting.vipSettingId')
                                ->where('vipsettingcashbackrule.vipsettingcashbackruleId', $levelId)->limit(1);
                            $row=$this->runOneRowArray($db);

                            $groupName = $row['groupName'];
                            $levelName = $row['vipLevelName'];

                            $db->where('playerId', $playerId);
                            $success=$this->runUpdateData('player', [
                                'levelId' => $levelId,
                                'groupName' => $groupName,
                                'levelName' => $levelName,
                            ], $db);

                        }
                    }

                    if($success){
                        $db->select('playerDetailsId')->from('playerdetails')->where('playerId', $playerId);
                        $playerDetailsId=$this->runOneRowOneField('playerDetailsId', $db);

                        if(!empty($playerDetailsId)){
                            $db->where('playerDetailsId', $playerDetailsId);
                            //update
                            $success=$this->runUpdateData('playerdetails', $playerDetails, $db);
                        }else{
                            $playerDetailsId=$this->runInsertData('playerdetails', $playerDetails, $db);
                            $success=!empty($playerDetailsId);
                        }
                    }

                    if($success && !empty($updateHistoryList)){
                        $db->where('playerId', $playerId);
                        $success=$this->runRealDelete('playerupdatehistory', $db);
                        if($success){
                            $data=[];
                            //sync updateHistoryList
                            foreach ($updateHistoryList as $row) {
                                unset($row['playerUpdateHistoryId']);
                                $data[]=$row;
                            }
                            $success=$this->runBatchInsertWithLimit($db, 'playerupdatehistory', $data);
                        }
                    }
                    if($success && !empty($playernotes)){
                        $db->where('playerId', $playerId);
                        $success=$this->runRealDelete('playernotes', $db);
                        if($success){
                            $data=[];
                            foreach ($playernotes as $row) {
                                unset($row['noteId']);
                                $data[]=$row;
                            }
                            $success=$this->runBatchInsertWithLimit($db, 'playernotes', $data);
                        }
                    }
                    if($success && !empty($invitedPlayer)){
                        $db->select('referralId')->from('playerfriendreferral')->where('invitedPlayerId', $playerId);
                        $referralId=$this->runOneRowOneField('referralId', $db);
                        if(empty($referralId)){
                            //insert if not exist
                            unset($invitedPlayer['referralId']);
                            $referralId=$this->runInsertData('playerfriendreferral', $invitedPlayer, $db);
                            $success=!empty($referralId);
                        }
                    }

                    if($success && !empty($line_players)){
                        $db->select('id')->from('line_players')->where('player_id', $playerId);
                        $id=$this->runOneRowOneField('id', $db);
                        if(empty($id)){
                            //insert if not exist
                            unset($line_players['id']);
                            $id=$this->runInsertData('line_players', $line_players, $db);
                            $success=!empty($id);
                        }
                    }

                    if($success && !empty($facebook_players)){
                        $db->select('id')->from('facebook_players')->where('player_id', $playerId);
                        $id=$this->runOneRowOneField('id', $db);
                        if(empty($id)){
                            //insert if not exist
                            unset($facebook_players['id']);
                            $id=$this->runInsertData('facebook_players', $facebook_players, $db);
                            $success=!empty($id);
                        }
                    }

                    if($success && !empty($google_players)){
                        $db->select('id')->from('google_players')->where('player_id', $playerId);
                        $id=$this->runOneRowOneField('id', $db);
                        if(empty($id)){
                            //insert if not exist
                            unset($google_players['id']);
                            $id=$this->runInsertData('google_players', $google_players, $db);
                            $success=!empty($id);
                        }
                    }

                    if($success && !empty($player_attached_proof_file)){
                        $db->select('id')->from('player_attached_proof_file')->where('player_id', $playerId)->where('tag', 'profile');
                        $id=$this->runOneRowOneField('id', $db);
                        if(empty($id)){
                            //insert if not exist
                            unset($player_attached_proof_file['id']);
                            $id=$this->runInsertData('player_attached_proof_file', $player_attached_proof_file, $db);
                            $success=!empty($id);
                        }else{
                            unset($player_attached_proof_file['id']);
                            $db->where('id', $id);
                            //update
                            $success=$this->runUpdateData('player_attached_proof_file', $player_attached_proof_file, $db);
                        }
                    }

                    $rlt=$playerId;

                    return $success;
                });
            }else{
                $this->utils->error_log('not found player by playerId: '.$sourceDB.'-'.$playerId);
            }
        }

        return $result;
    }

    public function showResultOnMDB($sql, $params){

        return $this->runRawSelectSQLArrayOnMDB($sql, $params);

    }

    public function listPlayerFromMDB($username){

        return $this->foreachMultipleDB(function($db, &$result)
            use($username){

            $db->from('player')->where('username', $username);
            $player=$this->runOneRowArray($db);
            // $this->utils->debug_log('search by '.$username, $user);
            $playerDetails=null;
            $success=!empty($player);
            if($success){
                $db->from('playerdetails')
                    ->where('playerId', $player['playerId']);
                $playerDetails=$this->runOneRowArray($db);
                // $this->utils->debug_log('the player details is', $role);
            }else{
                $this->utils->error_log('not found user', $username);
            }

            $result=['player'=>$player, 'playerDetails'=>$playerDetails];

            return $success;
        });

    }

    public function syncAgencyFromCurrentToOtherMDB($agent_id, $insertOnly=false){
        $sourceDB=$this->getActiveTargetDB();
        return $this->syncAgencyFromOneToOtherMDB($sourceDB, $agent_id, $insertOnly);
    }

    public function syncAgencyFromSuperToOtherMDB($agent_id, $insertOnly=false){
        return $this->syncAgencyFromOneToOtherMDB(Multiple_db::SUPER_TARGET_DB, $agent_id, $insertOnly);
    }

    /**
     * sync player from super to others
     * @param  string $agent_id
     * @return array of bool
     */
    public function syncAgencyFromOneToOtherMDB($sourceDB, $agent_id, $insertOnly=false){
        $result=null;
        if($this->utils->isEnabledMDB() && !empty($agent_id)){

            $this->load->model(['agency_model']);

            $agent=$this->runAnyOnSingleMDB($sourceDB,function($db)
                    use($agent_id){
                $db->from('agency_agents')->where('agent_id', $agent_id);
                return $this->runOneRowArray($db);
            });
            if(!empty($agent)){

                $agent_id=$agent['agent_id'];
                list($agency_agent_game_platforms, $agency_agent_game_types, $agency_flattening, $agency_flattening_options,
                    $agency_tracking_domain)=
                    $this->runAnyOnSingleMDB($sourceDB, function($db)
                        use($agent_id){
                    //agency_agent_game_platforms
                    //agency_agent_game_types
                    //agency_flattening
                    //agency_flattening_options
                    //agency_tier_comm_patterns
                    //agency_tier_comm_pattern_tiers
                    $db->from('agency_agent_game_platforms')->where('agent_id', $agent_id);
                    $agency_agent_game_platforms=$this->runMultipleRowArray($db);
                    $db->from('agency_agent_game_types')->where('agent_id', $agent_id);
                    $agency_agent_game_types=$this->runMultipleRowArray($db);
                    $db->from('agency_flattening')->where('agent_id', $agent_id);
                    $agency_flattening=$this->runMultipleRowArray($db);
                    $db->from('agency_flattening_options')->where('agent_id', $agent_id);
                    $agency_flattening_options=$this->runMultipleRowArray($db);
                    $db->from('agency_tracking_domain')->where('agent_id', $agent_id);
                    $agency_tracking_domain=$this->runMultipleRowArray($db);
                    // $this->utils->printLastSQL($db);
                    // $db->from('agency_tier_comm_patterns')->where('agent_id', $agent_id);
                    // $agency_tier_comm_patterns=$this->runMultipleRowArray($db);
                    // $db->from('agency_tier_comm_pattern_tiers')->where('agent_id', $agent_id);
                    // $agency_tier_comm_pattern_tiers=$this->runMultipleRowArray($db);

                    return [$agency_agent_game_platforms, $agency_agent_game_types, $agency_flattening, $agency_flattening_options,
                        $agency_tracking_domain];
                });
                $active_on_other_mdb=$this->activeItOnOtherMDB('agency');

                $this->clearFieldsOnData($agent, ['credit_limit', 'available_credit', 'wallet_balance', 'wallet_hold', 'frozen']);
                $remote_wallet_field_to_unset = $this->utils->getConfig('remote_wallet_field_to_unset_on_sync_agency_to_other_currency');
                if(!empty($remote_wallet_field_to_unset)){
                    $this->clearFieldsOnData($agent, $remote_wallet_field_to_unset);
                }
                $this->clearFieldsOnRows($agency_agent_game_platforms, ['id']);
                $this->clearFieldsOnRows($agency_agent_game_types, ['id']);
                $this->clearFieldsOnRows($agency_flattening, ['id']);
                $this->clearFieldsOnRows($agency_flattening_options, ['id']);
                $this->clearFieldsOnRows($agency_tracking_domain, ['id']);

                $this->utils->debug_log('agent_id: '.$agent_id, $active_on_other_mdb, 'agency_tracking_domain', $agency_tracking_domain);
                // $agent=$this->cleanSyncFields($agent, $onlySyncFields);
                $result=$this->foreachMultipleDBWithoutSourceDB($sourceDB, function($db, &$rlt)
                        use($agent_id, $agent, $agency_agent_game_platforms, $agency_agent_game_types, $agency_flattening,
                            $agency_flattening_options, $agency_tracking_domain, $active_on_other_mdb){
                    //update other db
                    $db->select('agent_id')->from('agency_agents')->where('agent_id', $agent_id);
                    $agent_id=$this->runOneRowOneField('agent_id', $db);
                    $success=false;

                    $currencyInfo=$this->getCurrencyByDB($db);
                    //adjust field
                    $agent['currency']=$currencyInfo['code'];
                    if(!empty($agent_id)){
                        $db->where('agent_id', $agent_id);
                        //update
                        $success=$this->runUpdateData('agency_agents', $agent, $db);
                    }else{
                        if(!$active_on_other_mdb){
                            $agent['status']=agency_model::AGENT_STATUS_FROZEN;
                        }
                        $agent['language']=$currencyInfo['default_language'];
                        //insert
                        $agent_id=$this->runInsertData('agency_agents', $agent, $db);
                        $success=!empty($agent_id);
                    }
                    // if($success){
                    //     //sync agency_agent_game_platforms, delete then insert
                    //     $db->where('agent_id', $agent_id);
                    //     $this->runRealDelete('agency_agent_game_platforms', $db);
                    //     $success=$this->runBatchInsertWithLimit($db, 'agency_agent_game_platforms', $agency_agent_game_platforms);
                    // }
                    // if($success){
                    //     //sync agency_agent_game_types, delete then insert
                    //     $db->where('agent_id', $agent_id);
                    //     $this->runRealDelete('agency_agent_game_types', $db);
                    //     $success=$this->runBatchInsertWithLimit($db, 'agency_agent_game_types', $agency_agent_game_types);
                    // }
                    if($success){
                        //sync agency_flattening, delete then insert
                        $db->where('agent_id', $agent_id);
                        $this->runRealDelete('agency_flattening', $db);
                        $success=$this->runBatchInsertWithLimit($db, 'agency_flattening', $agency_flattening);
                    }
                    if($success){
                        //sync agency_flattening_options, delete then insert
                        $db->where('agent_id', $agent_id);
                        $this->runRealDelete('agency_flattening_options', $db);
                        $success=$this->runBatchInsertWithLimit($db, 'agency_flattening_options', $agency_flattening_options);
                    }
                    if($success){
                        // $this->utils->debug_log('sync agency_tracking_domain');
                        //sync agency_tracking_domain, delete then insert
                        $db->where('agent_id', $agent_id);
                        $this->runRealDelete('agency_tracking_domain', $db);
                        // $this->utils->printLastSQL($db);
                        $success=$this->runBatchInsertWithLimit($db, 'agency_tracking_domain', $agency_tracking_domain);
                        // $this->utils->printLastSQL($db);

                        // $db->from('agency_tracking_domain')->where('agent_id', $agent_id);
                        // $this->utils->info_log($this->runMultipleRowArray($db));
                    }

                    $this->utils->debug_log('success', $success);

                    $rlt=$agent_id;

                    return $success;
                });

            }else{
                $this->utils->error_log('not found agency by agent_id: '.$agent_id);
            }
        }
        return $result;
    }
    public function syncAffFromCurrentToOtherMDB($affiliateId, $insertOnly=false){
        $sourceDB=$this->getActiveTargetDB();
        return $this->syncAffFromOneToOtherMDB($sourceDB, $affiliateId, $insertOnly);
    }

    public function syncAffFromSuperToOtherMDB($affiliateId, $insertOnly=false){
        return $this->syncAffFromOneToOtherMDB(Multiple_db::SUPER_TARGET_DB, $affiliateId, $insertOnly);
    }

    /**
     * sync affiliate from super to others
     * @param  string $affiliateId
     * @return array of bool
     */
    public function syncAffFromOneToOtherMDB($sourceDB, $affiliateId, $insertOnly=false){
        $result=null;
        if($this->utils->isEnabledMDB() && !empty($affiliateId)){
            $aff=$this->runAnyOnSingleMDB($sourceDB,function($db)
                    use($affiliateId){
                $db->from('affiliates')->where('affiliateId', $affiliateId);
                return $this->runOneRowArray($db);
            });
            // $this->load->model(['affiliate_newly_registered_player_tags']);

            list($affiliate_terms, $aff_tracking_link, $affiliate_read_only_account, $affiliate_newly_registered_player_tags)=$this->runAnyOnSingleMDB($sourceDB, function($db)
                use($affiliateId){
                $db->from('affiliate_terms')->where('affiliateId', $affiliateId);
                $affiliate_terms=$this->runMultipleRowArray($db);
                //aff_tracking_link
                $db->from('aff_tracking_link')->where('aff_id', $affiliateId);
                $aff_tracking_link=$this->runMultipleRowArray($db);
                // affiliate_read_only_account
                $db->from('affiliate_read_only_account')->where('affiliate_id', $affiliateId);
                $affiliate_read_only_account=$this->runMultipleRowArray($db);
                // affiliate_newly_registered_player_tags
                $db->from('affiliate_newly_registered_player_tags')->where('affiliate_id', $affiliateId);
                $affiliate_newly_registered_player_tags=$this->runMultipleRowArray($db);
                return [$affiliate_terms, $aff_tracking_link, $affiliate_read_only_account, $affiliate_newly_registered_player_tags];
            });

            $this->clearFieldsOnData($aff, ['balance', 'wallet_balance', 'wallet_hold', 'frozen']);

            //remove id
            $this->clearFieldsOnRows($affiliate_terms, ['id']);
            $this->clearFieldsOnRows($aff_tracking_link, ['id']);
            $this->clearFieldsOnRows($affiliate_read_only_account, ['id']);
            $this->clearFieldsOnRows($affiliate_newly_registered_player_tags, ['id']);

            $this->utils->debug_log('affiliate_terms', $affiliate_terms
                , 'aff_tracking_link', $aff_tracking_link
                , 'affiliate_read_only_account', $affiliate_read_only_account
                , 'affiliate_newly_registered_player_tags', $affiliate_newly_registered_player_tags );

            if(!empty($aff)){
                $active_on_other_mdb=$this->activeItOnOtherMDB('affiliate');

                $this->utils->debug_log('affiliateId: '.$affiliateId, $active_on_other_mdb);
                // $agent=$this->cleanSyncFields($agent, $onlySyncFields);
                $result=$this->foreachMultipleDBWithoutSourceDB($sourceDB, function($db, &$rlt)
                        use($affiliateId, $aff, $active_on_other_mdb,
                            $affiliate_terms, $aff_tracking_link,
                            $affiliate_read_only_account, $affiliate_newly_registered_player_tags ){
                    //update other db
                    $db->select('affiliateId')->from('affiliates')->where('affiliateId', $affiliateId);
                    $affiliateId=$this->runOneRowOneField('affiliateId', $db);
                    $success=false;
                    $currencyInfo=$this->getCurrencyByDB($db);
                    //set currency
                    $aff['currency']=$currencyInfo['code'];
                    $this->utils->debug_log('search by id', $affiliateId);
                    if(!empty($affiliateId)){
                        $db->where('affiliateId', $affiliateId);
                        //update
                        $success=$this->runUpdateData('affiliates', $aff, $db);
                    }else{
                        if(!$active_on_other_mdb){
                            $aff['status']=self::OLD_STATUS_ACTIVE;
                        }
                        $aff['language']=$currencyInfo['default_language'];
                        //insert
                        $affiliateId=$this->runInsertData('affiliates', $aff, $db);
                        $success=!empty($affiliateId);
                    }

                    //sync $affiliate_terms
                    // if($success){
                    //     //sync affiliate_terms, delete then insert
                    //     $db->where('affiliateId', $affiliateId);
                    //     $this->runRealDelete('affiliate_terms', $db);
                    //     // $this->utils->printLastSQL($db);
                    //     $success=$this->runBatchInsertWithLimit($db, 'affiliate_terms', $affiliate_terms);
                    // }
                    //sync $aff_tracking_link
                    if($success){
                        //sync aff_tracking_link, delete then insert
                        $db->where('aff_id', $affiliateId);
                        $this->runRealDelete('aff_tracking_link', $db);
                        $success=$this->runBatchInsertWithLimit($db, 'aff_tracking_link', $aff_tracking_link);
                    }
                    //sync $affiliate_read_only_account
                    if($success){
                        //sync affiliate_read_only_account, delete then insert
                        $db->where('affiliate_id', $affiliateId);
                        $this->runRealDelete('affiliate_read_only_account', $db);
                        $success=$this->runBatchInsertWithLimit($db, 'affiliate_read_only_account', $affiliate_read_only_account);
                    }
                    // sync affiliate_newly_registered_player_tags
                    if($success){
                        //sync affiliate_newly_registered_player_tags, delete then insert
                        $db->where('affiliate_id', $affiliateId);
                        $this->runRealDelete('affiliate_newly_registered_player_tags', $db);
                        $success=$this->runBatchInsertWithLimit($db, 'affiliate_newly_registered_player_tags', $affiliate_newly_registered_player_tags);

                        // $tag_id_list = [];
                        // affiliate_newly_registered_player_tags->updatePlayerTagsByAffiliate($affiliate_id, $tag_id_list)
                        $this->utils->debug_log('syncAffFromOneToOtherMDB.737');
                    }

                    $rlt=$affiliateId;

                    return $success;
                });

            }else{
                $this->utils->error_log('not found affiliate by affiliateId: '.$affiliateId);
            }
        }

        return $result;
    }

    public function clearFieldsOnRows(&$rows, $fields){
        if(!empty($rows)){
            foreach ($rows as &$row) {
                $this->clearFieldsOnData($row, $fields);
            }
        }
    }

    public function clearFieldsOnData(&$row, $fields){
        foreach ($fields as $fldName) {
            unset($row[$fldName]);
        }
    }

    //===sync mdb=================================================================================


    public function syncVIPGroupFromCurrentToOtherMDB($featureKey, $insertOnly=false){
        $sourceDB=$this->getActiveTargetDB();
        return $this->syncVIPGroupFromOneToOtherMDBWithFixPKidVer2($sourceDB, $featureKey, $insertOnly);
    }
    /**
     * sync VIPGroup And VIPLevels to others, without sourceDB
     *
     * @param string $sourceDB The currency key, like as: cny, brl,...
     * @param integer $featureKey The VIP Group P.K. vipSettingId.
     * @param boolean $insertOnly So far, its not exists in the requirements.
     * @return void
     */
    public function syncVIPGroupFromOneToOtherMDB($sourceDB, $featureKey, $insertOnly=false){

        return $this->syncVIPGroupFromOneToOtherMDBWithFixPKidVer2($sourceDB, $featureKey, $insertOnly);
    } // EOF syncVIPGroupFromOneToOtherMDB

    public function _parseGroupNameAndLevelCountInFeatureKey($featureKey){
        // format: groupName_XXXXXXgroupLevelCount_YYYYY
        // ex:
        // - GroupName: "asd123.en", LevelCount: 3
        //   groupName__json:{"1":"asd123.en","2":"asd123.cn","3":"asd123","4":"asd123","5":"asd123","6":"asd123","7":"asd123","8":"asd123","9":"asd123","10":"asd123"}_groupLevelCount_5
        // - GroupName: "HelloString", LevelCount: 2
        //  groupName_HelloStringgroupLevelCount_2

        $re = '/groupName_(?P<name>.+).*groupLevelCount_(?P<count>\d+)/m';
        preg_match_all($re, $featureKey, $matches, PREG_SET_ORDER, 0);

        $resultList = [];
		if( !empty($matches) ){
            foreach($matches as $i => $matche){
                $resultList[$i]['name'] = $matche['name'];
                $resultList[$i]['count'] = $matche['count'];
            }
		}

        $result = [];
        $result['name'] = '';
        $result['count'] = '';

        if( ! empty($resultList[0]) ){
            $result = $resultList[0];
        }

        return [$result['name'], $result['count']];// capture with list($name, $count)
    } // EOF _parseGroupNameAndLevelCountInFeatureKey
    public function DEL_parseGroupLevelCountInFeatureKey($featureKey){
        $re = '/groupLevelCount_(?P<count>\d+)/m';
        preg_match_all($re, $featureKey, $matches, PREG_SET_ORDER, 0);

        $groupLevelCountList = [];
		if( !empty($matches) ){
            foreach($matches as $matche){
                $groupLevelCountList[] = $matche['count'];
            }
		}
        $groupLevelCount = empty($groupLevelCountList[0])? 0: $groupLevelCountList[0];
		return $groupLevelCount;
    }
    public function _getGroupLevelsFromCurrencyKey($currencyKey, $featureKey){
        $_this = &$this;
        $filter_deleted = false;
        $_results = $this->runAnyOnSingleMDB($currencyKey,function($db)
                    use($featureKey, $filter_deleted, $_this) {
                $results = [];
                $results['vipGroupRow'] = [];
                $results['vipLevelList'] = [];

                $db->from('vipsetting');
                if(!empty($featureKey)){
                    $db->where('vipSettingId', $featureKey);
                }
                $_vipGroupList = $_this->runMultipleRowArray($db);

                $_this->clearFieldsOnRows($_vipGroupList, ['vipSettingId']);
                $_vipGroupList = $_this->appendLangField2Rows($_vipGroupList);
                reset($_vipGroupList);
                $vipGroupRow_of_sourceDB =current($_vipGroupList); // result of group
                $results['vipGroupRow'] = $vipGroupRow_of_sourceDB;

                if( ! empty($vipGroupRow_of_sourceDB) ){
                    $db->from('vipsettingcashbackrule');
                    if(!empty($featureKey)){
                        $db->where('vipSettingId', $featureKey);
                    }
                    if(!empty($filter_deleted)){
                        $db->where('deleted != 1', null, false);
                    }
                    $db->order_by('vipLevel', 'asc');

                    $vipLevelList_of_sourceDB = $_this->runMultipleRowArray($db); // result of levels
                    $results['vipLevelList'] = $vipLevelList_of_sourceDB;
                }

                return $results;
            }); // EOF $this->runAnyOnSingleMDB(...
        return$_results;
    }

    /// $getLevelsViaFK
    // getLevels Via PK, getLevelsViaFK=0, GET_LEVELS_VIA_FK_MODE
    // getLevels Via FK, getLevelsViaFK=1, GET_LEVELS_VIA_PK_MODE
    // getLevels Via Both, getLevelsViaFK=2, GET_LEVELS_VIA_BOTH_MODE
    public function listVIPGroupAndLevelsByParamsWithForeachMultipleDBWithoutSourceDB( $sourceDB //  #1
                                                                            , $vipGroupLevels_of_source //  #2
                                                                            , $filter_deleted = true //  #3
                                                                            , $select_fields_list = [] //  #4
                                                                            , $return_source = false // #5
                                                                            , $getLevelsViaFK = Multiple_db_model::GET_LEVELS_VIA_FK_MODE // #6
    ){


        $_this = &$this;
        $readonly=true;

        if( empty($select_fields_list['vipsetting']) ){
            $select_fields_list['vipsetting'] = [];
        }
        //
        if( empty($select_fields_list['vipsettingcashbackrule']) ){
            $select_fields_list['vipsettingcashbackrule'] = [];
        }

        /// The select fields recommended includes P.K. field.
        // In Vip Group
        if( ! in_array('vipSettingId', $select_fields_list['vipsetting']) ){
            array_push($select_fields_list['vipsetting'], 'vipSettingId');
        }
        // In Vip Level
        if( ! in_array('vipsettingcashbackruleId', $select_fields_list['vipsettingcashbackrule']) ){
            array_push($select_fields_list['vipsettingcashbackrule'], 'vipsettingcashbackrule.vipsettingcashbackruleId');
        }
        if( ! in_array('vipSettingId', $select_fields_list['vipsettingcashbackrule']) ){
            array_push($select_fields_list['vipsettingcashbackrule'], 'vipsettingcashbackrule.vipSettingId'); // F.K. to VIP Group
        }


        /// TODO, vipGroupLevels_of_source
        // ref. to listVIPGroupAndLevelsWithForeachMultipleDBWithoutSourceDB()
        // @.vipsetting rows
        // @.vipsettingcashbackrule rows

        // vipGroupLevels_of_source
        $this->utils->debug_log('OGP-28577.934.vipsetting:', empty($vipGroupLevels_of_source['vipsetting'])? null: $vipGroupLevels_of_source['vipsetting'] );
        $this->utils->debug_log('OGP-28577.935.vipsettingcashbackrule:', empty($vipGroupLevels_of_source['vipsettingcashbackrule'])? null: $vipGroupLevels_of_source['vipsettingcashbackrule'] );
        $result = $this->foreachMultipleDBWithoutSourceDB($sourceDB, function($db, &$rlt)
        use($_this, $vipGroupLevels_of_source, $filter_deleted, $select_fields_list, $getLevelsViaFK){

            $dbName = $db->getOgTargetDB();
            $dbKey = str_replace('_readonly', '', $dbName);

            // Group of source
            $vipSettingId = 0;
            $vipGroupRow_of_source = [];
            if( ! empty($vipGroupLevels_of_source['vipsetting']) ){
                $vipGroupRow_of_source = reset($vipGroupLevels_of_source['vipsetting']);
                $vipSettingId = empty($vipGroupRow_of_source['vipSettingId'])? 0: $vipGroupRow_of_source['vipSettingId'];
            }
            // Levels of source
            $vipLevelRows_of_source = [ [] ];
            if( ! empty($vipGroupLevels_of_source['vipsettingcashbackrule']) ){
                $vipLevelRows_of_source = $vipGroupLevels_of_source['vipsettingcashbackrule'];
            }

            $_this->utils->debug_log('OGP-28577.954.dbKey:', $dbKey
                                        , 'vipSettingId:', $vipSettingId
                                        , 'vipGroupRow_of_source:', $vipGroupRow_of_source
                                        , 'getLevelsViaFK:', $getLevelsViaFK );


            $rlt = [];
            $success = true;

            // Group of other currency DB
            $_table_name = 'vipsetting';
            $db->from($_table_name);
            if( ! empty($select_fields_list[$_table_name]) ){
                $select_clause_list = $select_fields_list[$_table_name];
                foreach($select_clause_list as $_select_key => $_select_field){
                    if( is_array($_select_field) ){
                        // like as $db->select($select_name, null, false);
                        call_user_func_array([$db, 'select'], $_select_field);
                    }else if( is_string($_select_field) ){
                        $db->select($_select_field);
                    }
                }// EOF foreach
            }else{
                $db->select('*');
            }
            $db->where('vipSettingId', $vipSettingId);
            if(!empty($filter_deleted)){
                $db->where('deleted != 1', null, false);
            }
            $_rlt4vipGroup = $this->runMultipleRowArray($db);
            //
            if( empty($_rlt4vipGroup) ){
                $_rlt4vipGroup = [ [] ];
            }
            $vipGroupRow = reset($_rlt4vipGroup); // get first, and $_rlt4vipGroup should be one data only.
            if( ! empty($vipGroupRow) ){
                $rlt[$_table_name] = $vipGroupRow;
            }
            // EOF, Group of other currency DB

            $_this->utils->debug_log('OGP-28577.1038.dbKey:', $dbKey, 'last_query:', $db->last_query() );

            $_this->utils->debug_log('OGP-28577.1040.dbKey:', $dbKey, 'cond1:', (! empty($vipGroupRow))? 1:0
            , 'cond2:', ($getLevelsViaFK === self::GET_LEVELS_VIA_PK_MODE)? 1:0
            , 'cond3:', ($getLevelsViaFK === self::GET_LEVELS_VIA_BOTH_MODE)? 1:0 );

            if( ! empty($vipGroupRow) // in other, for F.K. referenced
                || $getLevelsViaFK === self::GET_LEVELS_VIA_PK_MODE //get the related levels, when query row via P.K.
                || $getLevelsViaFK === self::GET_LEVELS_VIA_BOTH_MODE //get the related levels, when query row via P.K. and F.K.
            ){
                // Levels of other currency DB
                $_table_name = 'vipsettingcashbackrule';
                $db->from($_table_name);
                $db->join('vipsetting', 'vipsetting.vipSettingId = vipsettingcashbackrule.vipSettingId');// for the affected group of other and diff to source.
                if( ! empty($select_fields_list[$_table_name]) ){
                    $select_clause_list = $select_fields_list[$_table_name];
                    foreach($select_clause_list as $_select_key => $_select_field){
                        if( is_array($_select_field) ){
                            // like as $db->select($select_name, null, false);
                            call_user_func_array([$db, 'select'], $_select_field);
                        }else if( is_string($_select_field) ){
                            $db->select($_select_field);
                        }
                    }// EOF foreach
                }else{
                    $db->select('*');
                }

                switch($getLevelsViaFK){
                    default:
                    case self::GET_LEVELS_VIA_PK_MODE:
                        $db->where_in($_table_name. '.vipsettingcashbackruleId',  array_column($vipLevelRows_of_source, 'vipsettingcashbackruleId') );
                        break;
                    case self::GET_LEVELS_VIA_FK_MODE: // get vipGroupRow(of other) by vipSettingId of source
                        $db->where($_table_name. '.vipSettingId', $vipGroupRow['vipSettingId']);
                        break;
                    case self::GET_LEVELS_VIA_BOTH_MODE:
                        $isEmptyByVipLevelRowsInSource = true;
                        if( ! empty($vipLevelRows_of_source) ){
                            $isEmptyByVipLevelRowsInSource = false;
                        }
                        $isEmptyByVipGroupRowInOther = true;
                        if( ! empty($vipGroupRow) ){
                            $isEmptyByVipGroupRowInOther = false;
                        }
                        if( ! $isEmptyByVipLevelRowsInSource
                            && ! $isEmptyByVipGroupRowInOther
                        ){
                            $pk_list = array_column($vipLevelRows_of_source, 'vipsettingcashbackruleId');
                            $pk_list_imploded = implode(', ', $pk_list);
                            // P.K or F.K.
                            $_formater = '('. $_table_name. '.vipSettingId = %s OR vipsettingcashbackruleId IN (%s) )'; // 2 params: vipSettingId, vipsettingcashbackruleId list with "," join
                            $_where = sprintf($_formater, $vipGroupRow['vipSettingId'], $pk_list_imploded );
                            $db->where($_where, false, false);
                        }else if( ! $isEmptyByVipGroupRowInOther) {
                            // only with F.K.
                            $db->where($_table_name. '.vipSettingId', $vipGroupRow['vipSettingId']);
                        }else if( ! $isEmptyByVipLevelRowsInSource) {
                            // only with P.K.
                            $db->where_in('vipsettingcashbackruleId',  array_column($vipLevelRows_of_source, 'vipsettingcashbackruleId') );
                        }
                        break;
                } // EOF switch($getLevelsViaFK){...

                if(!empty($filter_deleted)){
                    $db->where($_table_name.'.deleted != 1', null, false);
                }
                // $db->limit('2'); // TEST
                $db->order_by('vipLevel', 'asc'); // for increase/decrease level
                $_rlt4vipLevels = $_this->runMultipleRowArray($db);

                $_this->utils->debug_log('OGP-28577.1023.dbKey:', $dbKey, 'last_query:', $db->last_query() );

                if( ! empty($_rlt4vipLevels) ){
                    $rlt['vipsettingcashbackrule'] = $_rlt4vipLevels;
                }else{
                    $rlt['vipsettingcashbackrule'] = []; // for No levels in the group
                }
                // EOF, Levels of other currency DB
            }

            // $dbName = $db->getOgTargetDB();
            // $dbKey = str_replace('_readonly', '', $dbName);
            // $_this->utils->debug_log('2159.rlt:', $rlt, 'success:', $success, 'dbKey:', $dbKey);

            return $success;
        }, $readonly); // EOF $result = $this->foreachMultipleDBWithoutSourceDB(...

        // strip suffix, "_readonly"
        foreach($result as $_dbkey => $_rlt){
            $_remove_suffix_db = str_replace('_readonly', '', $_dbkey);
            if($_remove_suffix_db != $_dbkey){ // has appended suffix
                $result[ $_remove_suffix_db ] = $_rlt;

                // clear for duplicate data
                $result[ $_dbkey ] = [];
                unset($result[ $_dbkey ]);
            }
        }

        // multi-langs convertion
        foreach($result as $_dbkey => &$_rlt){

            // $result[ $_dbkey ]
            if( ! empty($_rlt['result']) ){
                /// for vipsetting
                if( ! empty($_rlt['result']['vipsetting']) ){
                    $_rlt['result']['vipsetting'] = $this->appendLangField2Row($_rlt['result']['vipsetting']);
                }
            }

            if( ! empty($_rlt['result']) ){
                /// for vipsettingcashbackrule list
                $_rlt['result']['vipsettingcashbackrule'] = $this->appendLangField2Rows($_rlt['result']['vipsettingcashbackrule']);
            }
        }

        if($return_source){
            $result['__source'] = $vipGroupLevels_of_source;
            $result['__sourceDB'] = $sourceDB;
        }


        return $result;
    } // EOF listVIPGroupAndLevelsByParamsWithForeachMultipleDBWithoutSourceDB

    /**
     * Sync VIP Group From One To Other MDB With Fix PKid Ver2
     *
     * @param string $sourceDB The database string.
     * @param integer $featureKey The vipsetting.vipSettingId field.
     * @param boolean $insertOnly The sync actions only insert function, without soft-delete and update.
     * @param integer $dryRun The sync modes, DRY_RUN_MODE_IN_XXX.
     * @param string $others_in For target other MDB(s).
     * @param callable $completedCB4OEOM The script will execute when sync comleted in each other MDB.
     * @param boolean $doRealDeleteRowsWithSoftDeleted Do real delete the soft-deleted rows of VIP level(s), when sync comleted in each other MDB.
     * @return void
     */
    public function syncVIPGroupFromOneToOtherMDBWithFixPKidVer2($sourceDB // #1
        , $featureKey  // #2 aka. vipSettingId
        , $insertOnly=false // #3
        , $dryRun = self::DRY_RUN_MODE_IN_NORMAL  // #4
        , $others_in = 'all' // #5
        , callable $completedCB4OEOM = null // #6
        , $doRealDeleteRowsWithSoftDeleted = true // #7
    ){
        $this->load->library(['group_level_lib']);
        $this->load->model(['vipsetting']);

        $result=null;
        $_this = &$this;

        if( ! $this->utils->isEnabledMDB()
            || empty($featureKey)
        ){
            return $result; // TODO
        }

        $this->utils->debug_log('OGP-28577.1192.dryRun:', $dryRun, 'DRY_RUN_MODE_IN_DECREASED_LEVELS:', self::DRY_RUN_MODE_IN_DECREASED_LEVELS);

        // TODO, DELETE_GROUP
        /// In DRY_RUN_MODE_IN_ADD_GROUP and featureKey = groupLevelCount_XXX
        if($dryRun == self::DRY_RUN_MODE_IN_ADD_GROUP){

            list($groupName, $groupLevelCount) = $this->_parseGroupNameAndLevelCountInFeatureKey($featureKey);
            // $groupLevelCount = $this->_parseGroupLevelCountInFeatureKey($featureKey);
            $_vipSettingId = 0; // Get autoIncrementId to be the expected vipSettingId when group will added
            if( ! empty($groupLevelCount) ){
                $autoIncrementId = $this->runAnyOnSingleMDB($sourceDB,function($db) use($_this){
                    return $_this->utils->getMaxPrimaryIdByTable('vipsetting', true, $db);
                });
                $_vipSettingId = $autoIncrementId;
            }
            /// For new the sample row from default_level_id,
            $default_level_id = $this->group_level_lib->getDefaultLevelIdBySourceDB($sourceDB);

            // get the group id from default level id
            $default_vipSettingId = $this->runAnyOnSingleMDB($sourceDB,function($db)
                    use($default_level_id, $_this) {
                /// Get vipSettingId form default_level_id
                $vipSettingId = $_this->group_level_lib->getVipSettingIdFromLevelId($default_level_id, $db);
                return $vipSettingId;
            });
            // get the group and levels from the group id of default level id
            $_results = $this->_getGroupLevelsFromCurrencyKey($sourceDB, $default_vipSettingId);
            // the group
            $vipGroupRow_of_sourceDB = [];
            if( ! empty($_results['vipGroupRow']) ){
                $vipGroupRow_of_sourceDB = $_results['vipGroupRow'];
                $vipGroupRow_of_sourceDB['vipSettingId'] = $_vipSettingId;
                $vipGroupRow_of_sourceDB['groupLevelCount'] = $groupLevelCount;
                $vipGroupRow_of_sourceDB['groupName'] = $groupName;
                $vipGroupRow_of_sourceDB['groupName'] = $_this->utils->appendSuffix2langField($vipGroupRow_of_sourceDB['groupName'], ' simulatedNew'); // add into the format,"_json:{"1":"OLE777 ","2":"<E8><B1><AA><E5><88><A9>777","3":"OLE777","4":"OLE777","5":"OLE777","6":"OLE777 "}"
                $vipGroupRow_of_sourceDB['groupLevelCount'] = $groupLevelCount;

                // re-generate language fields
                $vipGroupRow_of_sourceDB = $this->appendLangField2Row($vipGroupRow_of_sourceDB);
            }
            // generate the levels from the first
            $vipLevelList = [];
            if( ! empty($_results['vipLevelList']) ){
                $vipLevelList = $_results['vipLevelList']; // re-assign by groupLevelCount of $featureKey
                reset($vipLevelList);
                $firstVipLevel =current($vipLevelList);

                if( !empty($groupLevelCount) ){
                    $autoIncrementId = $this->runAnyOnSingleMDB($sourceDB,function($db) use($_this){
                        return $_this->utils->getMaxPrimaryIdByTable('vipsettingcashbackrule', true, $db);
                    });
                    $_vipsettingcashbackruleId = $autoIncrementId;
                    $vipLevelList = [];
                    for($i = 0; $i < $groupLevelCount; $i++){
                        $vipLevel = $i+1;
                        $vipLevelList[$i] = $firstVipLevel;
                        /// cloned from Vipsetting::increaseVipGroupLevel()
                        $vipLevelList[$i]['minDeposit'] = 100 * $vipLevel;
                        $vipLevelList[$i]['maxDeposit'] = 1000 * $vipLevel;
                        $vipLevelList[$i]['dailyMaxWithdrawal'] = 10000 * $vipLevel;
                        $vipLevelList[$i]['vipLevel'] = $vipLevel;
                        $vipLevelList[$i]['vipLevelName'] = 'Level Name '. $vipLevel;

                        $vipLevelList[$i]['vipSettingId'] = $vipGroupRow_of_sourceDB['vipSettingId'];
                        $vipLevelList[$i]['vipsettingcashbackruleId'] = $_vipsettingcashbackruleId + $i;
                    }
                    // free
                    $firstVipLevel = [];
                    unset($firstVipLevel);
                }
            }else{
                $this->utils->error_log('OGP-28577.1174.default_level_id should Not be Empty.'
                                            , 'default_level_id:', $default_level_id
                                            , '_vipSettingId:', $_vipSettingId
                                        );
            } // EOF if( !empty($vipLevelList) ){...
            $featureKey = $_vipSettingId; // override for Auto Increment into $featureKey

            $this->utils->debug_log('OGP-28577.1226.$vipLevelList:', $vipLevelList );
            // EOF if($dryRun == self::DRY_RUN_MODE_IN_ADD_GROUP){...
        }else{
            $filter_deleted = false;
            $_results = $this->_getGroupLevelsFromCurrencyKey($sourceDB, $featureKey);
            $vipGroupRow_of_sourceDB = [];
            $vipLevelList = [];
            if( ! empty($_results['vipGroupRow']) ){
                $vipGroupRow_of_sourceDB = $_results['vipGroupRow'];
            }
            if( ! empty($_results['vipLevelList']) ){
                $vipLevelList = $_results['vipLevelList'];
            }
        }

        foreach($vipLevelList as $_index => $vipLevel_of_source){
            if( ! empty($vipGroupRow_of_sourceDB) ){
                $vipLevel_of_source['groupName'] = $vipGroupRow_of_sourceDB['groupName'];
            }
            $vipLevelList[$_index] =  $_this->appendLangField2Row($vipLevel_of_source);
        }

        if( intval($dryRun) === self::DRY_RUN_MODE_IN_INCREASED_LEVELS){ // dry run in increase group player level

            $_notDeletedVipLevelList = array_filter($vipLevelList, function($v, $k) {
                return empty($v['deleted']);
            }, ARRAY_FILTER_USE_BOTH);

            $_countOfVipLevelList = count($_notDeletedVipLevelList);
            $autoIncrementId = $this->runAnyOnSingleMDB($sourceDB,function($db) use($_this){
                return $_this->utils->getMaxPrimaryIdByTable('vipsettingcashbackrule', true, $db);
            });
            $_countOfVipLevelList++;
            $_vipLevel = []; // vipsettingcashbackruleId, vipSettingId, vipLevel, vipLevelName, deleted
            $_vipLevel['vipsettingcashbackruleId'] = $autoIncrementId;
            $_vipLevel['vipSettingId'] = $featureKey;
            $_vipLevel['groupName'] = $vipGroupRow_of_sourceDB['__lang_groupName'];
            $_vipLevel['vipLevel'] = $_countOfVipLevelList;
            $_vipLevel['vipLevelName'] = 'Level Name '. $_vipLevel['vipLevel']. ' simulated';
            $_vipLevel['deleted'] = 0;
            // if($_countOfVipLevelList > 0) {
            //     $_vipLevel = $vipLevelList[$_countOfVipLevelList-1];
            // }
            array_push($vipLevelList, $_vipLevel);
            $this->utils->debug_log('OGP-28577.910.$vipLevelList.count:', empty($vipLevelList)? 0: count($vipLevelList) );

            $vipGroupRow_of_sourceDB['groupLevelCount'] = $_countOfVipLevelList;
            // EOF if( intval($dryRun) === self::DRY_RUN_MODE_IN_INCREASED_LEVELS){...
        }else if( intval($dryRun) === self::DRY_RUN_MODE_IN_DECREASED_LEVELS){ // dry run in decrease group player level
            $_countOfVipLevelList = count($vipLevelList);
            $this->utils->debug_log('OGP-28577.1214.dryRun:', $dryRun);
            if($_countOfVipLevelList > 1) {
                array_pop($vipLevelList);
            }
            $_countOfVipLevelList--;
            $vipGroupRow_of_sourceDB['groupLevelCount'] = $_countOfVipLevelList;
            $this->utils->debug_log('OGP-28577.1220.$vipLevelList.count:', empty($vipLevelList)? 0: count($vipLevelList) );
            // EOF if( intval($dryRun) === self::DRY_RUN_MODE_IN_DECREASED_LEVELS){...
        }else{
        }

        $vipLevelList = $this->appendLangField2Rows($vipLevelList);

        $result4vipGroup = []; // default
        // $vipGroupRow_of_sourceDB: the Group of sourceDB
        // $vipLevelList: the Levels of sourceDB
        if(!empty($vipGroupRow_of_sourceDB)){

            /// Get VIP Group And Levels form others DB  via param #2
            $_vipGroupLevels_of_source = [];
            $_vipGroupLevels_of_source['vipsetting'][0] = $vipGroupRow_of_sourceDB;
            // for first row of vipsetting, to assign vipSettingId
            $_vipGroupLevels_of_source['vipsetting'][0]['vipSettingId'] = $featureKey;
            $_vipGroupLevels_of_source['vipsettingcashbackrule'] = $vipLevelList;
            $this->utils->debug_log('OGP-28577.1250.dbKey:', $sourceDB, 'vipGroupRow_of_sourceDB:',$vipGroupRow_of_sourceDB);
            $this->utils->debug_log('OGP-28577.1251.dbKey:', $sourceDB, 'vipLevelList.count:', empty($vipLevelList)? 0: count($vipLevelList) );
            $this->utils->debug_log('OGP-28577.1255.dbKey:', $sourceDB, '_vipGroupLevels_of_source:',$_vipGroupLevels_of_source);
            // collect VIP Group and Levels of other Currency DBs
            $filter_deleted = false;
            $select_fields_list = [];
            // for posi
            $select_fields_list['vipsetting'] = [];
            $select_fields_list['vipsetting'][] = 'groupName';
            $select_fields_list['vipsettingcashbackrule'] = [];
            $select_fields_list['vipsettingcashbackrule'][] = 'vipLevelName';
            $select_fields_list['vipsettingcashbackrule'][] = 'groupName'; // $db->join('vipsetting'...
            $select_fields_list['vipsettingcashbackrule'][] = 'vipsettingcashbackrule.deleted';
            $return_source = false;
            $getLevelsViaMode = self::GET_LEVELS_VIA_BOTH_MODE;

            $this->benchmark->mark('listVIPGroupAndLevelsByParamsWithForeachMultipleDBWithoutSourceDB_start');
            /// ref. to listVIPGroupAndLevelsWithForeachMultipleDBWithoutSourceDB
            $_rlt_list = $this->listVIPGroupAndLevelsByParamsWithForeachMultipleDBWithoutSourceDB( $sourceDB // #1
                                                                            , $_vipGroupLevels_of_source // #2
                                                                            , $filter_deleted // #3
                                                                            , $select_fields_list // #4
                                                                            , $return_source // #5
                                                                            , $getLevelsViaMode // #6
                                                                    );
            $this->benchmark->mark('listVIPGroupAndLevelsByParamsWithForeachMultipleDBWithoutSourceDB_stop');
            $_total = count($_rlt_list); // The others DB amount.
            $_progress = 0;
            // strip the wrapper
            $_rlt4listVIPGroupAndLevels = [];
            foreach($_rlt_list as $dbKey => $_rlt){
                if($_rlt['success']){
                    if( ! empty($_rlt['result']['vipsetting'] ) ){
                        // Group row
                        $_rlt4listVIPGroupAndLevels[$dbKey]['vipsetting'] = $_rlt['result']['vipsetting'];
                    }
                    if( ! empty($_rlt['result']['vipsettingcashbackrule'] ) ){
                        // Level Rows
                        $_rlt4listVIPGroupAndLevels[$dbKey]['vipsettingcashbackrule'] = $_rlt['result']['vipsettingcashbackrule'];
                    }
                }
            } // EOF foreach($_rlt_list as $dbKey => $_rlt){...
            $_rlt_list = []; // free
            unset($_rlt_list);

            ////////// ////// ///////////////// ///////
            if( $others_in == 'all'){
                $others_in = '';
            }
            $this->benchmark->mark('foreachOthersDBWithoutSourceDB_start');
            $result4vipGroup = $this->foreachOthersDBWithoutSourceDB($sourceDB, function($db, &$rlt)
            use($vipGroupRow_of_sourceDB, $vipLevelList, $featureKey, $_rlt4listVIPGroupAndLevels, $_this, $dryRun, $insertOnly, &$_total, &$_progress, &$completedCB4OEOM, $doRealDeleteRowsWithSoftDeleted ){
                $_this->_clear4soft_deleted_rows();

                // for $dbKey, strip the suffix string, "_readonly".
                $dbName = $db->getOgTargetDB();
                $dbKey = str_replace('_readonly', '', $dbName);
                $_benchmark_mark = 'foreachOthersDBWith'. strtoupper($dbKey);
                $_this->benchmark->mark($_benchmark_mark. '_start'); // ex: foreachOthersDBWithUSD_start

                $rlt=[];
                $rlt['success_finial'] = null;
                $rlt['vipsetting'] = [];
                $rlt['vipSettingId'] = null;
                $rlt['vipSettingId_with'] = null;
                $rlt['vipsettingcashbackrule_list'] = [];

                $rlt['dbg'] = [];
                $rlt['dbg']['vipsettingcashbackrule_list'] = [];

                if( ! empty($dryRun) ){
                    $rlt['dryRun'] = [];
                    $rlt['dryRun']['call_method_list'] = [];
                }

                $vipGroupRow_of_otherDB = [];
                if( ! empty($_rlt4listVIPGroupAndLevels[$dbKey]['vipsetting']) ) {
                    $vipGroupRow_of_otherDB = $_rlt4listVIPGroupAndLevels[$dbKey]['vipsetting']; // vipGroupRow of otherDB
                }

                $success_list = [];
                $success_list['vipsetting'] = false;
                $success_list['vipsettingcashbackruleId_with'] = [];

                $this->utils->debug_log('OGP-28577.1312.dbKey:', $dbKey, '_rlt4listVIPGroupAndLevels.dbKey:', empty($_rlt4listVIPGroupAndLevels[$dbKey])? null:$_rlt4listVIPGroupAndLevels[$dbKey] );
                $this->utils->debug_log('OGP-28577.1313.dbKey:', $dbKey, 'vipGroupRow_of_otherDB:',$vipGroupRow_of_otherDB);
                $success=false;
                // $db->select('vipSettingId')->from('vipsetting')->where('vipSettingId', $featureKey);
                // $vipSettingId=$_this->runOneRowOneField('vipSettingId', $db);
                // $success=false;
                // $rlt=[];
                // $vipGroupRow_of_sourceDB = reset($vipGroupList); // get first row, and $vipGroupList should be one row by vipSettingId = $featureKey.
                //
                // $vipGroupRow_of_sourceDB = $this->appendLangField2Row($vipGroupRow_of_sourceDB);

                $this->utils->debug_log('OGP-28577.1326.dbKey:', $dbKey, 'vipGroupRow_of_sourceDB:',$vipGroupRow_of_sourceDB );
                if($dbKey == 'php'|| 1){

                    $this->utils->debug_log('OGP-28577.1329.dbKey:', $dbKey, '_rlt4listVIPGroupAndLevels.dbKey:', empty($_rlt4listVIPGroupAndLevels[$dbKey])? null:$_rlt4listVIPGroupAndLevels[$dbKey] );
                    $this->utils->debug_log('OGP-28577.1330.dbKey:', $dbKey, 'vipGroupRow_of_sourceDB:',$vipGroupRow_of_sourceDB);
                    $this->utils->debug_log('OGP-28577.1331.dbKey:', $dbKey, 'vipGroupRow_of_otherDB:',$vipGroupRow_of_otherDB);
                }

                if( ! empty($vipGroupRow_of_sourceDB) ){
                    // dryRun for preview
                    if( ! empty($dryRun) ){
                        $isEmpty4vipGroupRow_of_otherDB = empty($vipGroupRow_of_otherDB['vipSettingId']);
                        $_event_info = [];
                        $_event_info['token'] = uniqid('event_');
                        $_event_info['token_by_line'] = __LINE__; // for trace cause
                        if( ! $isEmpty4vipGroupRow_of_otherDB ){
                            $_event_info['caseStr'] = 'overrideVipGroup';
                            $_event_info['is_warning'] = 1; // there is a row of the same as source, in other DB.
                            $_event_info['affected_group_name'] = $vipGroupRow_of_otherDB['__lang_groupName'];
                            $_event_info['affected_vipSettingId'] = $vipGroupRow_of_otherDB['vipSettingId'];
                        }else{
                            $_vipSettingId = $this->utils->getMaxPrimaryIdByTable('vipsetting', true, $db);
                            $_event_info['caseStr'] = 'newVipGroup';
                            $_event_info['is_warning'] = 0;
                            $_event_info['affected_group_name'] = $vipGroupRow_of_sourceDB['__lang_groupName'];
                            $_event_info['affected_vipSettingId'] = $_vipSettingId;
                        }
                        $_event_info['affected_vipSettingId'] = $featureKey;

                        if( ! $isEmpty4vipGroupRow_of_otherDB ){
                            $vipsetting_id = $featureKey;
                            $rlt['vipSettingId'] = $vipsetting_id;
                            $rlt['vipSettingId_with'] = 'editVIPGroup, dryRun=1';

                            $_will_call = [];
                            $_will_call['event'] = $_event_info;
                            $_will_call['method_name'] = 'Group_level::editVIPGroup';
                            $_will_call['params'] = [];
                            $_will_call['params']['data'] = $vipGroupRow_of_sourceDB;
                            $_will_call['params']['vipsettingId'] = $featureKey;
                            $rlt['dryRun']['call_method_list'][] = $_will_call;
                        }else{
                            // $_vipSettingId = $this->utils->getMaxPrimaryIdByTable('vipsetting', true, $db);
                            $rlt['vipSettingId'] = $_vipSettingId;
                            $rlt['vipSettingId_with'] = 'addVIPGroup, dryRun=1';

                            $_dryRun_vipGroupRow_of_sourceDB = $vipGroupRow_of_sourceDB;
                            $_dryRun_vipGroupRow_of_sourceDB['vipSettingId'] = $featureKey; // the P.K. of the other DB, that has the same as the P.K. of the sourceDB.
                            $_dryRun_vipGroupRow_of_sourceDB['groupLevelCount'] = 0; // to skip, handle in levels sync

                            $_will_call = [];
                            $_will_call['event'] = $_event_info;
                            $_will_call['method_name'] = 'Group_level::addVIPGroup';
                            $_will_call['params'] = [];
                            $_will_call['params']['data'] = $_dryRun_vipGroupRow_of_sourceDB;
                            $_will_call['before_row'] = [];
                            $_will_call['after_row'] = $vipGroupRow_of_sourceDB;
                            $rlt['dryRun']['call_method_list'][] = $_will_call;
                        }

                    }else if( ! empty($vipGroupRow_of_otherDB['vipSettingId']) ){
                        // When the id is exists in other DB
                        if( ! empty($insertOnly) ){
                            $success_list['vipsetting'] = false;
                            $rlt['vipSettingId'] = $featureKey;
                            $rlt['vipSettingId_with'] = 'editVIPGroup Failed by insertOnly=1';
                        }else{
                            // Update
                            $vipsetting_id = $featureKey;
                            $data = $_this->filterLangField2Row($vipGroupRow_of_sourceDB);
                            // $data['groupLevelCount '] = 0; // reset the level amount
                            // // because Not sure the levels need to new action. So do refresh groupLevelCount before of end.
                            $_rlt = $_this->group_level->editVIPGroup($data, $vipsetting_id, $db);
                            if($_rlt){
                                $success_list['vipsetting'] = true;
                                $rlt['vipSettingId'] = $vipsetting_id;
                                $rlt['vipSettingId_with'] = 'editVIPGroup';
                            }
                        }
                    }else{
                        /// When the row of the same as source, that did Not exists in other DB
                        // insert
                        $vipGroupRow_copied_sourceDB = $vipGroupRow_of_sourceDB;
                        $vipGroupRow_copied_sourceDB['vipSettingId'] = $featureKey; // the P.K. of the other DB, that has the same as the P.K. of the sourceDB.
                        $vipGroupRow_copied_sourceDB['groupLevelCount'] = 0; // to skip, handle in levels sync
                        // filter the "__lang_" prefix fields
                        // $this->utils->debug_log('OGP-28577.1409.dbKey:', $dbKey, 'filterLangField2Row', $_this->filterLangField2Row($vipGroupRow_copied_sourceDB));
                        $_vipSettingId = $_this->group_level->addVIPGroup($_this->filterLangField2Row($vipGroupRow_copied_sourceDB), $db);
                        // $this->utils->debug_log('OGP-28577.1411.dbKey:', $dbKey, '_vipSettingId', $_vipSettingId);
                        if($featureKey == $_vipSettingId){
                            $success_list['vipsetting'] = true;
                            $rlt['vipSettingId'] = $_vipSettingId;
                            $rlt['vipSettingId_with'] = 'addVIPGroup';
                        }
                        // TODO, re-query for level
                        // $_rlt4listVIPGroupAndLevels[$dbKey]['vipsettingcashbackrule']
                    }
                    /// moved to after sync levels.
                    // // re-assign for $rlt
                    // $db->select('vipSettingId, groupLevelCount, groupName, status, deleted')
                    //     ->from('vipsetting')
                    //     ->where('vipSettingId', $rlt['vipSettingId']);
                    // $_vipGroupRow = $_this->runOneRowArray($db);
                    // $rlt['vipsetting'] = $_vipGroupRow;

                } // EOF if( ! empty($vipGroupRow_of_sourceDB) ){...

                $rlt['success_finial'] = $success_list['vipsetting'];

                $this->utils->debug_log('OGP-28577.1432.dbKey:', $dbKey, 'SyncVipGroup.foreachMultipleDBWithoutSourceDB.rlt:', $rlt, 'success_list:', $success_list, 'dbKey:', $dbKey);

                // $vipLevelRows = []; //  form other // sol1
                $vipLevelRows_of_other = [];// sol2
                // The $vipLevelRows includes the data by P.K. and the data by the F.K. with Group's P.K.
                if( ! empty($_rlt4listVIPGroupAndLevels[$dbKey]['vipsettingcashbackrule']) ){ // form other
                    // $vipLevelRows = $_rlt4listVIPGroupAndLevels[$dbKey]['vipsettingcashbackrule'];
                    $vipLevelRows_of_other = $_rlt4listVIPGroupAndLevels[$dbKey]['vipsettingcashbackrule'];
                }
                $this->utils->debug_log('OGP-28577.1438.dbKey:', $dbKey, 'vipLevelList, form source:', $vipLevelList); // form source
                $this->utils->debug_log('OGP-28577.1439.dbKey:', $dbKey, 'vipLevelRows_of_other, form other:', $vipLevelRows_of_other); // form other


                // Get the level of source, thats not exists in other
                $_vipsettingcashbackruleId_list_of_other = array_column($vipLevelRows_of_other, 'vipsettingcashbackruleId');
                $sourceLevelRowsList_NotExistsInOther = array_filter($vipLevelList, function($v, $k) use ($_vipsettingcashbackruleId_list_of_other, $dbKey) {
                    $isNotExistsInOther = ! in_array($v['vipsettingcashbackruleId'], $_vipsettingcashbackruleId_list_of_other);
                    return $isNotExistsInOther;
                }, ARRAY_FILTER_USE_BOTH);
                $sourceLevelRowsList_NotExistsInOther = array_values($sourceLevelRowsList_NotExistsInOther);
                if( !empty($sourceLevelRowsList_NotExistsInOther) ){
                    // New level in other, by source
                    if( ! empty($sourceLevelRowsList_NotExistsInOther) ) {
                        foreach($sourceLevelRowsList_NotExistsInOther as $vipLevelRow_of_source){
                            $_rlt = $_this->_doUpdate4NewLevelInOtherBySource($vipLevelRow_of_source, $vipGroupRow_of_sourceDB, $dryRun, $db);

                            if( ! empty($_rlt['_vipsettingcashbackrule_row']) ){
                                $_vipsettingcashbackruleId = $_rlt['_vipsettingcashbackrule_row']['vipsettingcashbackruleId'];
                                $rlt['vipsettingcashbackrule_list'][$_vipsettingcashbackruleId] = $_rlt['_vipsettingcashbackrule_row'];

                                $success_list['vipsettingcashbackruleId_with'][$_vipsettingcashbackruleId] = 'added';
                            }


                            if( ! empty($_rlt['dryRun']['call_method_list']) ){
                                foreach($_rlt['dryRun']['call_method_list'] as $_will_call){
                                    $rlt['dryRun']['call_method_list'][] = $_will_call;
                                }
                            }
                        }
                    } // EOF if( ! empty($sourceLevelRowsList_NotExistsInOther) ) {...
                } // EOF if( !empty($sourceLevelRowsList_NotExistsInOther) ){

                // $vipLevelList, form source
                $_vipsettingcashbackruleId_list_of_source = array_column($vipLevelList, 'vipsettingcashbackruleId');
                $otherLevelRowsList_NotExistsInSource = array_filter($_vipsettingcashbackruleId_list_of_other, function($v, $k)
                use ($_vipsettingcashbackruleId_list_of_source, $dbKey) {
                    $isNotExistsInSource = ! in_array($v, $_vipsettingcashbackruleId_list_of_source);
                    return $isNotExistsInSource;
                }, ARRAY_FILTER_USE_BOTH);
                $otherLevelRowsList_NotExistsInSource = array_values($otherLevelRowsList_NotExistsInSource);
                if( !empty($otherLevelRowsList_NotExistsInSource) ){
                    // The level of other, Not found in source.
                    // should to deleted
                    $_this->utils->debug_log('OGP-28577.1579.otherLevelRowsList_NotExistsInSource:', $otherLevelRowsList_NotExistsInSource);
                    foreach($otherLevelRowsList_NotExistsInSource as $_vipsettingcashbackruleId ){
                        $_default_level_id = 0; // for use config
                        $_rlt = $_this->_doUpdate4SoftDelete2LevelOfOther( $_vipsettingcashbackruleId // #1
                                                                , $_default_level_id // #2
                                                                , $insertOnly // #3
                                                                , $dryRun // #4
                                                                , __LINE__ // #5
                                                                , $db ); // #6
                        if( ! empty($_rlt['dryRun']['call_method_list']) ){
                            foreach($_rlt['dryRun']['call_method_list'] as $_will_call){
                                $rlt['dryRun']['call_method_list'][] = $_will_call;
                            }
                        }

                        // $_rlt['affected_list']['adjustPlayerLevel_rows']
                        // $_rlt['affected_list']['soft_deleted_rows']
                        // $_rlt['affected_list']['refresh_groupLevelCount_rows']
                        if(! empty($_rlt['affected_list']['soft_deleted_rows']) ){
                            $success_list['vipsettingcashbackruleId_with'][$_vipsettingcashbackruleId] = 'deleted';
                        }
                    } // EOF foreach($sourceLevelRowsList_NotExistsInOther as $_vipsettingcashbackruleId){...
                }

                $_vipSettingId = $featureKey;
                /// ignored by the $vipLevelRows_of_other includes Non under in group of source
                // if( ! empty($vipLevelRows_of_other) ){
                //     $_vipSettingId = $vipLevelRows_of_other[0]['vipSettingId'];
                // }
                $vipGroupRow_of_other = $_this->vipsetting->getVIPGroupOneDetails($_vipSettingId, $db);
                if( ! empty($vipGroupRow_of_other )){
                    $vipGroupRow_of_other = $_this->appendLangField2Row($vipGroupRow_of_other);
                }


                if( $dryRun == self::DRY_RUN_MODE_IN_ADD_GROUP
                    && empty($vipGroupRow_of_other )
                ){
                    // assign from source
                    $vipGroupRow_of_other = $vipGroupRow_of_sourceDB;
                }


                foreach($vipLevelRows_of_other as $vipLevelRow_of_other){

                    //is Exists data via F.K.
                    $isExistsUnderAsGroup = ($vipLevelRow_of_other['vipSettingId'] == $featureKey)? '1': '0';

                    $vipsettingcashbackruleId_of_other = $vipLevelRow_of_other['vipsettingcashbackruleId'];

                    $levelRowsExistsSource = array_filter($vipLevelList, function($v, $k) use ($vipsettingcashbackruleId_of_other, $dbKey) {
                        return $v['vipsettingcashbackruleId'] == $vipsettingcashbackruleId_of_other;
                    }, ARRAY_FILTER_USE_BOTH);
                    $levelRowsSource = array_values($levelRowsExistsSource);
                    $vipLevelRow_of_source = [];
                    if( ! empty($levelRowsExistsSource) ){
                        $vipLevelRow_of_source = $levelRowsSource[0];
                    }

                    $doUpdate = Multiple_db_model::doUpdate4None2LevelOfOther;
                    $isExistsByOther1 = empty($levelRowsExistsSource)? '0': '1'; // is Exists data via P.K.
                    $_switch = $isExistsUnderAsGroup. '::'. $isExistsByOther1;
                    $rlt['dbg']['vipsettingcashbackrule_list'][$vipsettingcashbackruleId_of_other] = $_switch;
                    switch( $_switch ) {// Int via F.K. :: Int via P.K.
                        default:
                        case '0::0':
                            /// Expected:
                            // none
                            $doUpdate = Multiple_db_model::doUpdate4None2LevelOfOther;
                            break;
                        case '0::1':
                            /// Expected:
                            // add(Increase) level: forcedUpdate
                            // remove(Decrease) level: forcedUpdate for deleted_at

                            // forcedUpdate
                            $doUpdate = Multiple_db_model::doUpdate4Overwrite2LevelOfOther;
                            break;
                        case '1::0':
                            /// Expected:
                            // add(Increase) level: forcedUpdate for deleted_at
                            // remove(Decrease) level: forcedUpdate for deleted_at

                            // soft-delete: forcedUpdate for deleted_at
                            $doUpdate = Multiple_db_model::doUpdate4SoftDelete2LevelOfOther;
                            break;
                        case '1::1':
                            /// Expected:
                            // add(Increase) level: forcedUpdate
                            // remove(Decrease) level: forcedUpdate for deleted_at

                            if($vipLevelRow_of_source['deleted'] != '0'){
                                // soft-delete: forcedUpdate for deleted_at
                                $doUpdate = Multiple_db_model::doUpdate4SoftDelete2LevelOfOther;
                            }else{
                                // forcedUpdate
                                $doUpdate = Multiple_db_model::doUpdate4Overwrite2LevelOfOther;
                            }
                            break;
                    }

                    if($doUpdate == Multiple_db_model::doUpdate4SoftDelete2LevelOfOther){
                        $_default_level_id = 0; // for use config
                        $_rlt = $_this->_doUpdate4SoftDelete2LevelOfOther( $vipsettingcashbackruleId_of_other // #1
                                                                , $_default_level_id // #2
                                                                , $insertOnly // #3
                                                                , $dryRun // #4
                                                                , $_switch // #5
                                                                , $db ); // #6
                        if( ! empty($_rlt['dryRun']['call_method_list']) ){
                            foreach($_rlt['dryRun']['call_method_list'] as $_will_call){
                                $rlt['dryRun']['call_method_list'][] = $_will_call;
                            }
                        }

                        // $_rlt['affected_list']['adjustPlayerLevel_rows']
                        // $_rlt['affected_list']['soft_deleted_rows']
                        // $_rlt['affected_list']['refresh_groupLevelCount_rows']
                        if(! empty($_rlt['affected_list']['soft_deleted_rows']) ){
                            $_vipsettingcashbackruleId = $vipsettingcashbackruleId_of_other;
                            $success_list['vipsettingcashbackruleId_with'][$_vipsettingcashbackruleId] = 'deleted';
                        }

                    }else if($doUpdate == Multiple_db_model::doUpdate4Overwrite2LevelOfOther){

                        $_rlt = $_this->_doUpdate4Overwrite2LevelOfOther($vipLevelRow_of_other // #1
                                                                        , $vipLevelRow_of_source // #2
                                                                        , $featureKey // #3
                                                                        , $dryRun // #4
                                                                        , $db ); // #5
                        if( ! empty($_rlt['dryRun']['call_method_list']) ){
                            foreach($_rlt['dryRun']['call_method_list'] as $_will_call){
                                $rlt['dryRun']['call_method_list'][] = $_will_call;
                            }
                        }
                        if(! empty($_rlt['vipsettingcashbackrule_list']) ){
                            foreach($_rlt['vipsettingcashbackrule_list'] as $_vipsettingcashbackruleId => $_vipsettingcashbackrule_row){
                                $rlt['vipsettingcashbackrule_list'][$_vipsettingcashbackruleId] = $_vipsettingcashbackrule_row;
                            }
                        }
                        if(! empty($_rlt['success_list']['vipsettingcashbackruleId_with']) ){
                            foreach($_rlt['success_list']['vipsettingcashbackruleId_with'] as $_vipsettingcashbackruleId => $_with){
                                $success_list['vipsettingcashbackruleId_with'][$_vipsettingcashbackruleId] = $_with;
                            }
                        }

                    } // EOF if($doUpdate == Multiple_db_model::doUpdate4...

                    // // query for $rlt
                    // $db->select('vipsettingcashbackruleId, vipSettingId, vipLevel, vipLevelName, deleted')
                    //     ->from('vipsettingcashbackrule')
                    //     ->where('vipsettingcashbackruleId', $_vipsettingcashbackruleId);
                    // $_vipsettingcashbackrule_row =  $_this->runOneRowArray($db);
                    // if( ! empty($_vipsettingcashbackrule_row)){
                    //     $rlt['vipsettingcashbackrule_list'][$_vipsettingcashbackruleId] = $_vipsettingcashbackrule_row;
                    //     $success_list['vipsettingcashbackruleId_with'][$_vipsettingcashbackruleId] = 'Update';
                    // }

                } // EOF foreach($vipLevelRows_of_other as $vipLevelRow_of_other){...

                // ///TODO,
                // $rlt['vipsettingcashbackrule_list'][$_vipsettingcashbackruleId] = $_vipsettingcashbackrule_row;
                // $success_list['vipsettingcashbackruleId_with'][$_vipsettingcashbackruleId] = $__sync_with;

                if($doRealDeleteRowsWithSoftDeleted){
                    $rlt4delete4soft_deleted_rows = $this->_delete4soft_deleted_rows($db);
                    $rlt['dbg']['delete4soft_deleted_rows'] = $rlt4delete4soft_deleted_rows;
                }

                if($success_list['vipsetting']){ // when group handled
                    // re-assign to $rlt for groupLevelCount
                    $db->select('vipSettingId, groupLevelCount, groupName, status, deleted')
                        ->from('vipsetting')
                        ->where('vipSettingId', $rlt['vipSettingId']);
                    $_vipGroupRow = $_this->runOneRowArray($db);
                    $rlt['vipsetting'] = $_vipGroupRow;
                }
                $_notDeletedVipLevelList = array_filter($vipLevelList, function($v, $k) use ( $dbKey ) {
                    return empty($v['deleted']);
                }, ARRAY_FILTER_USE_BOTH);
                $_notDeletedVipLevelListCount = count($_notDeletedVipLevelList);

                if( ! empty($dryRun) ){
                    $rlt['success_finial'] = true; // always be true in dryrun
                }else{
                    if( count($rlt['vipsettingcashbackrule_list']) != $_notDeletedVipLevelListCount ){
                        // detect levels has sync success
                        // The levels amount of other DB , that is Not eq. to the amount of source

                        $rlt['success_finial'] = $rlt['success_finial'] && false;
                    }

                    $_notDeletedVipLevelList = []; // free
                    unset($_notDeletedVipLevelList);
                }

                $_forcedSuccessFinial = $_this->_getForcedSuccessFinial(); // sync_vip_group2others_success_finial
                if( ! is_null($_forcedSuccessFinial) ){
                    $rlt['success_finial'] = $_forcedSuccessFinial;
                }else{
                    $_forcedSuccessFinial_dbKey = $_this->_getForcedSuccessFinial($dbKey); // sync_vip_group2otherUSD_success_finial
                    if( ! is_null( $_forcedSuccessFinial_dbKey ) ){
                        $rlt['success_finial'] = $_forcedSuccessFinial_dbKey;
                    }
                }

                $rlt['dbg']['count'] = [];
                $rlt['dbg']['count']['vipsettingcashbackrule_list'] = count($rlt['vipsettingcashbackrule_list']);
                $rlt['dbg']['count']['notDeletedVipLevelListCount'] = $_notDeletedVipLevelListCount;

                $_this->utils->debug_log('OGP-28577.1807.dbKey:', $dbKey
                                                    , 'dbg.count:', $rlt['dbg']['count']
                                                    , '_forcedSuccessFinial:', $_this->_getForcedSuccessFinial()
                                                    , '_forcedSuccessFinial_dbKey:', $_this->_getForcedSuccessFinial($dbKey)
                                                );

                $_this->benchmark->mark($_benchmark_mark. '_stop'); // ex: foreachOthersDBWithUSD_stop
                $rlt[$_benchmark_mark.'_elapsed_time'] = $_this->benchmark->elapsed_time($_benchmark_mark. '_start', $_benchmark_mark. '_stop');

                /// OEOM = completedCB
                // completedCB4OEOM
                if( is_callable($completedCB4OEOM) ) {
                    $_progress++;
                    $_extra = [];
                    $completedCB4OEOM($_progress, $_total, $_extra); // for call queue_result->updateFinalResult()
                }

                $_this->utils->debug_log('OGP-28577.1806.dbKey:', $dbKey
                            , 'success_finial', $rlt['success_finial']
                            , 'foreach.foreachMultipleDBWithoutSourceDB.success_list', $success_list);

                return $rlt['success_finial'];
                // return $success;
            }, false // readonly
            , $others_in); // EOF $this->foreachOthersDBWithoutSourceDB()...

            $this->benchmark->mark('foreachOthersDBWithoutSourceDB_stop');
            $_elapsed_time = $_this->benchmark->elapsed_time('foreachOthersDBWithoutSourceDB_start', 'foreachOthersDBWithoutSourceDB_stop');

        }else{
            $_elapsed_time = 0;
            // Not found any VIP Group by featureKey
            $this->utils->debug_log('OGP-28577.1898.SyncVipGroup.featureKey is empty.'
                                    , 'vipGroupRow_of_sourceDB:', $vipGroupRow_of_sourceDB
                                    , 'featureKey:',$featureKey);
        } // EOF if(!empty($vipGroupRow_of_sourceDB)){...
        $result = $result4vipGroup;
        $this->utils->debug_log('OGP-28577.1903.result:', $result, '_elapsed_time', $_elapsed_time);


        return $result;
    } // EOF syncVIPGroupFromOneToOtherMDBWithFixPKidVer2

    private function _getForcedSuccessFinial($dbKey = 'usd'){
        $return  = null;
        /// _forced_success_finial:
        // sync_vip_group2others_success_finial /// default
        // sync_vip_group2otherUSD_success_finial
        // sync_vip_group2otherPHP_success_finial
        // ... etc
        $_forced_success_finial = $this->utils->getConfig('sync_vip_group2other'. strtoupper($dbKey). '_success_finial');
        if( $_forced_success_finial == 1 ){ // forced assign in true.
            $return = true; // to dev.
        }else if( $_forced_success_finial == -1 ){ // forced assign in false.
            // forced assign.
            $return = false; // to dev.
        }else{
            $return = null;
        }
        return $return;
    }

    private function _doUpdate4NewLevelInOtherBySource($vipLevelRow_of_source, $vipGroupRow_of_sourceDB, $dryRun = false, $db = null){
        $this->load->model(['vipsetting', 'group_level']);
        $rlt = [];
        $rlt['affected_list'] = []; // affected_rows list

        $dbName = $db->getOgTargetDB();
        $dbKey = str_replace('_readonly', '', $dbName);

        $vipSettingId = $vipLevelRow_of_source['vipSettingId'];
        $vipsettingcashbackruleId = $vipLevelRow_of_source['vipsettingcashbackruleId'];

        // unset the settings
        unset($vipLevelRow_of_source['vip_upgrade_id']);
        unset($vipLevelRow_of_source['vip_downgrade_id']);

        if( ! empty($dryRun) ){
            $rlt['dryRun'] = [];
            $rlt['dryRun']['call_method_list'] = [];

            // $vipGroupRow_of_other = $this->vipsetting->getVIPGroupOneDetails($vipSettingId, $db);
            $vipLevelRow_of_other = $this->vipsetting->getVipGroupLevelDetails($vipsettingcashbackruleId, $db);
            $_event_info = [];
            $_event_info['token'] = uniqid('event_');
            $_event_info['token_by_line'] = __LINE__; // for trace cause
            $_event_info['caseStr'] = 'increaseVipGroupLevel';
            $_event_info['is_warning'] = 0;
            $_event_info['affected_vipSettingId'] = $vipLevelRow_of_source['vipSettingId'];
            $_event_info['affected_group_name'] = $vipGroupRow_of_sourceDB['__lang_groupName'];
            $_event_info['affected_level_name'] = $vipLevelRow_of_source['__lang_vipLevelName'];
            $_event_info['affected_vipsettingcashbackruleId'] =  $vipLevelRow_of_source['vipsettingcashbackruleId'];


            $_is_exist_in_other = empty($vipLevelRow_of_other)? false: true;
            $_will_call = [];
            $_will_call['method_name'] = 'Group_level::editVipGroupBonusRule';
            $_will_call['event'] = $_event_info; // the part of $_event_info['caseStr'] = 'increaseVipGroupLevel';
            $_will_call['params'] = [];
            $_will_call['params']['data'] = $vipLevelRow_of_source;
            // for confirm the data of other
            $_will_call['_is_exist_in_other'] = $_is_exist_in_other;
            $_will_call['_row_of_other'] = $vipLevelRow_of_other;

            $rlt['dryRun']['call_method_list'][] = $_will_call;

            // simulate the not yet added level
            $_vipsettingcashbackruleId = $this->utils->getMaxPrimaryIdByTable('vipsettingcashbackrule', true, $db);

        }else{
            // When the id is Not  exists in other DB
            $_addTransaction = false;
            // /// DEBUG
            // $db->select()
            //     ->from('vipsettingcashbackrule')
            //     ->order_by('vipsettingcashbackruleId', 'desc')
            //     ->limit(5);
            // $_dbg_vipLevelRow = $_this->runMultipleRowArray($db);
            // $this->utils->debug_log('OGP-28577.1238.will.increaseVipGroupLevel.dbKey:', $dbKey, '_dbg_vipLevelRow:', $_dbg_vipLevelRow);
            // /// DEBUG END
            $autoIncrementId = $this->utils->getMaxPrimaryIdByTable('vipsettingcashbackrule', true, $db);
            $this->utils->debug_log('OGP-28577.1974.dbKey:', $dbKey, '_vipsettingcashbackruleId.autoIncrementId:', $autoIncrementId);
            $_vipsettingcashbackruleId = $this->group_level->increaseVipGroupLevel($vipSettingId, $_addTransaction, $db);
            // $called_increaseVipGroupLevel = true;
            $rlt['affected_list']['increaseVipGroupLevel_rows'] = $db->affected_rows();
            $this->utils->debug_log('OGP-28577.1978.dbKey:', $dbKey, '_vipsettingcashbackruleId:', $_vipsettingcashbackruleId);

            /// Patch the issue,  added a group into the database of relatively few data.
            // The PK of the added levels in source DB, that had exist in other DBs.
            $syncLevelPKofOther_with_Source = true;
            if($syncLevelPKofOther_with_Source) { /// adjust vipsettingcashbackruleId to the same as source.
                $flag = $this->group_level->editData('vipsettingcashbackrule', // table
                                            array('vipsettingcashbackruleId' => $_vipsettingcashbackruleId), // where
                                            ['vipsettingcashbackruleId' => $vipLevelRow_of_source['vipsettingcashbackruleId'] ] // data
                                            , $db );
                if($flag){
                    $_vipsettingcashbackruleId =  $vipLevelRow_of_source['vipsettingcashbackruleId'];
                }
            } // EOF if($syncLevelPKofOther_with_Source) {...

            $_data = $this->filterLangField2Row($vipLevelRow_of_source);
            if( ! empty($_data['groupName']) ){
                unset($_data['groupName']);
            }
            $this->group_level->editVipGroupBonusRule($_data, $db);

            $rlt['affected_list']['editVipGroupBonusRule_rows'] = $db->affected_rows();
        }

        // query for $rlt
        $db->select('vipsettingcashbackruleId, vipSettingId, vipLevel, vipLevelName, deleted')
            ->from('vipsettingcashbackrule')
            ->where('vipsettingcashbackruleId', $_vipsettingcashbackruleId);
        $_vipsettingcashbackrule_row =  $this->runOneRowArray($db);

        if( empty($_vipsettingcashbackrule_row)
            && ! empty($dryRun)
        ){ /// when in dryRun, $_vipsettingcashbackrule_row will be empty

            $vipLevelRow_of_source['vipLevelName'] .= ' simulated';
            $_vipsettingcashbackrule_row = $vipLevelRow_of_source;

            // // simulate to make a level of dryRun
            // $_groupLevelCount = $this->group_level->getGroupCurrLevelCount($vipSettingId); // from vipsetting.groupLevelCount
            // $_groupLevelCount++;
            //
            // $autoIncrementId = $this->utils->getMaxPrimaryIdByTable('vipsettingcashbackrule', true, $db);
            // $_vipLevel = []; // vipsettingcashbackruleId, vipSettingId, vipLevel, vipLevelName, deleted
            // $_vipLevel['vipsettingcashbackruleId'] = $autoIncrementId;
            // $_vipLevel['vipSettingId'] = $vipSettingId;
            // $_vipLevel['groupName'] = $vipGroupRow_of_sourceDB['__lang_groupName'];
            // $_vipLevel['vipLevel'] = $_groupLevelCount;
            // $_vipLevel['vipLevelName'] = 'Level Name '. $_vipLevel['vipLevel']. ' simulated';
            // $_vipLevel['deleted'] = 0;
            // $_vipsettingcashbackrule_row = $_vipLevel;
        }

        $rlt['_vipsettingcashbackrule_row'] = $_vipsettingcashbackrule_row;
        return $rlt;
    } // EOF _doUpdate4NewLevelInOtherBySource

    private function _refreshGroupLevelCountByVipSettingId($vipSettingId, $db = null){
        if( empty($db)){
            $db = $this->db;
        }
        // in other,
        $affected_rows = null; // init
        $filter_deleted = true;
        // get from vipsettingcashbackrule
        $_groupLevels = $this->group_level->getGroupLevels($vipSettingId, $filter_deleted, $db);
        $_groupLevels_amount = empty($_groupLevels)? 0: count($_groupLevels);
        // get from vipsetting
        $_groupLevelCount = $this->group_level->getGroupCurrLevelCount($vipSettingId, $db);
        if($_groupLevels_amount != $_groupLevelCount && !empty($_groupLevels_amount) ){
            // update the real level amount into groupLevelCount field.
            $_data = array(
                'groupLevelCount' => $_groupLevels_amount,
            );
            $db->where('vipSettingId', $vipSettingId);
            $db->update('vipsetting', $_data);
            $affected_rows = $db->affected_rows();
        }
        return $affected_rows;
    } // EOF _refreshGroupLevelCountByVipSettingId;

    private function _doUpdate4Overwrite2LevelOfOther($vipLevelRow_of_other // #1
                                                    , $vipLevelRow_of_source // #2
                                                    , $featureKey // #3
                                                    , $dryRun // #4
                                                    , $db = null // #5
    ){
        $this->load->library(['group_level_lib']);

        $rlt = [];
        $rlt['success_list'] = [];
        $rlt['success_list']['vipsettingcashbackruleId_with'] = [];
        $rlt['dryRun'] = [];
        $rlt['dryRun']['call_method_list'] = [];

        if( ! empty($dryRun) ){
            $_vipLevelRow = $vipLevelRow_of_other;

            $_event_info = [];
            $_event_info['token'] = uniqid('event_');
            $_event_info['is_warning'] = 1;
            if( $_vipLevelRow['vipSettingId'] !== $vipLevelRow_of_source['vipSettingId']){
                // move vipLevel
                $_event_info['caseStr'] = 'moveVipLevel'; //  move level to another Group
                $_event_info['token_by_line'] = __LINE__; // for trace cause
                $_event_info['from_vipSettingId'] = $_vipLevelRow['vipSettingId'];
                $_event_info['from_group_name'] = $_vipLevelRow['__lang_groupName']; // affected in other
                $_event_info['from_level_name'] = $_vipLevelRow['__lang_vipLevelName'];
                $_event_info['from_level_deleted'] = $_vipLevelRow['deleted'];
                $_event_info['from_vipsettingcashbackruleId'] = $_vipLevelRow['vipsettingcashbackruleId'];
                $_event_info['to_vipSettingId'] = $featureKey;
                $_event_info['to_group_name'] = $vipLevelRow_of_source['__lang_groupName']; // affected in other
                $_event_info['to_level_name'] = $vipLevelRow_of_source['__lang_vipLevelName'];
                $_event_info['to_level_deleted'] = $vipLevelRow_of_source['deleted']; // aka. from_level_deleted
                $_event_info['to_vipsettingcashbackruleId'] = $vipLevelRow_of_source['vipsettingcashbackruleId'];
            }else{
                $_event_info['caseStr'] = 'overrideVipLevel';
                $_event_info['token_by_line'] = __LINE__; // for trace cause
                $_event_info['affected_vipSettingId'] = $_vipLevelRow['vipSettingId'];
                $_event_info['affected_group_name'] = $_vipLevelRow['__lang_groupName']; // affected in other
                $_event_info['affected_level_name'] = $_vipLevelRow['__lang_vipLevelName'];
                $_event_info['affected_vipsettingcashbackruleId'] = $_vipLevelRow['vipsettingcashbackruleId'];
            }

            $_will_call = [];
            $_will_call['method_name'] = 'Group_level::editVipGroupBonusRule';
            $_will_call['event'] = $_event_info; // the part of $_event_info['caseStr'] = 'increaseVipGroupLevel';
            $_will_call['params'] = [];
            $_will_call['params']['data'] = $vipLevelRow_of_source;
            // for confirm the data of other
            $_will_call['_is_exist_in_other'] = true;
            $_will_call['_row_of_other'] = $vipLevelRow_of_other;

            // query for $rlt
            $_vipsettingcashbackruleId = $vipLevelRow_of_source['vipsettingcashbackruleId'];
            $rlt['dryRun']['call_method_list'][] = $_will_call;
        }else{

            // query for $rlt
            $_vipsettingcashbackruleId = $vipLevelRow_of_source['vipsettingcashbackruleId'];
            // unset the settings
            unset($vipLevelRow_of_source['vip_upgrade_id']);
            unset($vipLevelRow_of_source['vip_downgrade_id']);

            $_data = $this->filterLangField2Row($vipLevelRow_of_source);
            if( ! empty($_data['groupName']) ){
                unset($_data['groupName']);
            }
            $this->group_level->editVipGroupBonusRule($_data, $db);

            // query for $rlt
            $db->select('vipsettingcashbackruleId, vipSettingId, vipLevel, vipLevelName, deleted')
                ->from('vipsettingcashbackrule')
                ->where('vipsettingcashbackruleId', $_vipsettingcashbackruleId);
            $_vipsettingcashbackrule_row =  $this->runOneRowArray($db);

            // query for $rlt
            $rlt['vipsettingcashbackrule_list'][$_vipsettingcashbackruleId] = $_vipsettingcashbackrule_row;
            $rlt['success_list']['vipsettingcashbackruleId_with'][$_vipsettingcashbackruleId] = 'Update';

            /// refresh groupLevelCount
            if( $vipLevelRow_of_other['vipSettingId'] !== $vipLevelRow_of_source['vipSettingId']){
                $this->_refreshGroupLevelCountByVipSettingId($vipLevelRow_of_source['vipSettingId'], $db);
            }
            $this->_refreshGroupLevelCountByVipSettingId($vipLevelRow_of_other['vipSettingId'], $db);

        }
        return $rlt;
    } // EOF _doUpdate4Overwrite2LevelOfOther

    /**
     * To Do Soft Delete Level in Other DB
     *
     * @param integer $vipsettingcashbackruleId The VIP level id
     * @param integer $default_level_id When the players under the level, them should be moved to another leve.
     * @param boolean $insertOnly If its be true that means function disallow update.
     * @param boolean $dryRun To generate the dryRun related results.
     * @param CI_dB_driver $db
     * @return array $rlt The result elements as,
     * - $rlt['affected_list']['adjustPlayerLevel_rows']
     * - $rlt['affected_list']['soft_deleted_rows']
     * - $rlt['affected_list']['refresh_groupLevelCount_rows']
     * - $rlt['dryRun']['call_method_list']
     */
    private function _doUpdate4SoftDelete2LevelOfOther($vipsettingcashbackruleId // #1
                                                        , $default_level_id = 0 // #2
                                                        , $insertOnly= false // #3
                                                        , $dryRun = true // #4
                                                        , $outerTraceStr = '' // #5
                                                        , $db = null // #6
    ){
        if( empty($db) ){
            $db = $this->db;
        }
        $this->load->model(['vipsetting', 'group_level']);

        $dbName = $db->getOgTargetDB();
        $dbKey = str_replace('_readonly', '', $dbName); // for debug

        $rlt = [];
        $rlt['affected_list'] = []; // affected_rows list
        if( empty($default_level_id) ){
            $default_level_id = $this->utils->getConfig('default_level_id');
            $currencyInfo=$this->getCurrencyByDB($db);
            $default_level_id = $currencyInfo['player_default_level_id'];
        }


        $playerIds = [];
        if( $default_level_id != $vipsettingcashbackruleId) {
            $playerIds = $this->group_level->getPlayerIdsByLevelId($vipsettingcashbackruleId, $db);
        }

        // && empty($insertOnly) // allow update in insertOnly=0
        $switchStr = '';
        $switchStr .= empty($dryRun)? '0': '1';
        $switchStr .= '::';
        $switchStr .= empty($insertOnly)? '0': '1';
        $switchStr .= '::';
        $switchStr .= empty($playerIds)? '0': '1';

        $doAdjustPlayerLevel = null;
        $doSoftDelete = null;
        $doDryRun = null;
        switch($switchStr){ // !empty(dryRun) :: !empty(insertOnly) :: !empty(playerIds)
            case '0::0::0': // execute, allow update, no player under the level
                $doAdjustPlayerLevel = false;
                $doSoftDelete = true;
                $doDryRun = false;
                break;
            case '0::0::1': // execute, allow update, some players under the level
                $doAdjustPlayerLevel = true;
                $doSoftDelete = true;
                $doDryRun = false;
                break;
            case '0::1::0': // execute, disallow update, no player under the level
                $doAdjustPlayerLevel = false;
                $doSoftDelete = false;
                $doDryRun = false;
                break;
            case '0::1::1': // execute, disallow update, some players under the level
                $doAdjustPlayerLevel = true;
                $doSoftDelete = false;
                $doDryRun = false;
                break;
            case '1::0::0': // dryRun, allow update, no player under the level
                $doAdjustPlayerLevel = false;
                $doSoftDelete = true;
                $doDryRun = true;
                break;
            case '1::0::1': // dryRun, allow update, some players under the level
                $doAdjustPlayerLevel = true;
                $doSoftDelete = true;
                $doDryRun = true;
                break;
            case '1::1::0': // dryRun, disallow update, no player under the level
                $doAdjustPlayerLevel = false;
                $doSoftDelete = false;
                $doDryRun = true;
                break;
            case '1::1::1': // dryRun, disallow update, some players under the level
                $doAdjustPlayerLevel = true;
                $doSoftDelete = false;
                $doDryRun = true;
                break;
        } // EOF switch($switchStr){...
        if( ! empty($doDryRun) ){
            $_event_token = uniqid('event_');

            $rlt['dryRun'] = [];
            $rlt['dryRun']['call_method_list'] = [];
            $this->utils->debug_log('OGP-28577.2188.dbKey:', $dbKey, 'vipsettingcashbackruleId:', $vipsettingcashbackruleId);

            // get level detail
            $_VipLevelDetail = $this->group_level->getVipGroupLevelDetails($vipsettingcashbackruleId, $db);
            // re-generate language fields
            $_VipLevelDetail = $this->appendLangField2Row($_VipLevelDetail);

            /// get Group detail form level
            $vipGroupRow = [];
            if( ! empty( $_VipLevelDetail ) ){
                // empty, when increase level in dryrun
                $_vipsettingId = $_VipLevelDetail['vipSettingId'];
                $vipGroupRow = $this->vipsetting->getVIPGroupOneDetails($_vipsettingId, $db);
                // re-generate language fields
                $vipGroupRow = $this->appendLangField2Row($vipGroupRow);
            }

        } // EOF if( ! empty($doDryRun) ){...

        if( $doAdjustPlayerLevel ){ // player need move to default level
            if( $default_level_id == $vipsettingcashbackruleId) {
                // the level already be default level, and thats disallow be soft-delete.
            }else if( ! empty($doDryRun) ){ //call_method_list

                $playerIds_count = count($playerIds);
                $_event_info = [];
                $_event_info['token'] = uniqid('event_'); // $_event_token;
                $_event_info['token_by_line'] = __LINE__; // for trace cause
                $_event_info['outer_trace_str'] = $outerTraceStr; // for trace cause
                $_event_info['switchStr'] = $switchStr;
                $_event_info['caseStr'] = 'adjustPlayerLevel';
                $_event_info['is_warning'] = empty($playerIds)? 0: 1;
                $_event_info['affected_level_name'] = $_VipLevelDetail['__lang_vipLevelName'];
                $_event_info['playerIds_count'] = $playerIds_count;
                // $_VipLevelDetail = $_this->group_level->getVipGroupLevelDetails($vipsettingcashbackruleId, $db);
                $_event_info['affected_group_name'] = lang($_VipLevelDetail['groupName']);
                $_event_info['affected_vipSettingId'] = lang($_VipLevelDetail['vipSettingId']);
                $_event_info['affected_vipsettingcashbackruleId'] = lang($_VipLevelDetail['vipsettingcashbackruleId']);

                foreach($playerIds as $_playerId ){
                    $_will_call = [];
                    $_will_call['method_name'] = 'Group_level::adjustPlayerLevel';
                    $_will_call['event'] = $_event_info;
                    $_will_call['params'] = [];
                    $_will_call['params']['playerId'] = $_playerId;
                    $_will_call['params']['newLevelId'] = $default_level_id;
                    $_will_call['_player_count_in_level'] = $playerIds_count;
                    $_will_call['posi'] = [];
                    $_will_call['posi']['vipsettingcashbackruleId'] = $vipsettingcashbackruleId;
                    $rlt['dryRun']['call_method_list'][] = $_will_call;
                } // EOF foreach($playerIds as $_playerId ){...
            }else{
                foreach($playerIds as $_playerId ){ // move players to default level, before delete level
                    $this->group_level->adjustPlayerLevel($_playerId, $default_level_id, $db);
                    $rlt['affected_list']['adjustPlayerLevel_rows'] = $db->affected_rows();
                }
            }

        }
        if( $doSoftDelete ){
            if( ! empty($doDryRun) ){ //call_method_list
                $_event_info = [];
                $_event_info['token'] = uniqid('event_'); // $_event_token;
                $_event_info['token_by_line'] = __LINE__; // for trace cause
                $_event_info['outer_trace_str'] = $outerTraceStr; // for trace cause
                $_event_info['switchStr'] = $switchStr;
                $_event_info['caseStr'] = 'softDeleteVipLevel';
                $_event_info['is_warning'] = 1;
                $_event_info['affected_level_name'] = $_VipLevelDetail['__lang_vipLevelName'];
                // $_VipLevelDetail = $this->group_level->getVipGroupLevelDetails($vipsettingcashbackruleId, $db);
                $_event_info['affected_group_name'] = lang($_VipLevelDetail['groupName']);
                $_event_info['affected_vipSettingId'] = $_VipLevelDetail['vipSettingId'];
                $_event_info['affected_level_deleted'] = $_VipLevelDetail['deleted'];
                $_event_info['affected_vipsettingcashbackruleId'] = $_VipLevelDetail['vipsettingcashbackruleId'];

                $_will_call = [];
                $_will_call['method_name'] = 'CI_DB_active_record::update';
                $_will_call['event'] = $_event_info;
                $_will_call['params'] = [];
                $_will_call['params']['table'] = 'vipsettingcashbackrule';
                $_will_call['params']['set'] = ['deleted' => 1];
                $_will_call['params']['where'] = ['vipsettingcashbackruleId' => $vipsettingcashbackruleId];
                $rlt['dryRun']['call_method_list'][] = $_will_call;
            }else{
                //update deleted to 1 instead to delete
                $_data = array(
                    'deleted' => 1,
                );
                $db->where('vipsettingcashbackruleId', $vipsettingcashbackruleId);
                $db->update('vipsettingcashbackrule', $_data);
                $rlt['affected_list']['soft_deleted_rows'] = $db->affected_rows();

                $this->_add2soft_deleted_rows(['vipsettingcashbackruleId' => $vipsettingcashbackruleId]);
            }
        }

        if( $doSoftDelete ){
            // refresh groupLevelCount of Group
            $vipSettingId = $this->group_level_lib->getVipSettingIdFromLevelId($vipsettingcashbackruleId, $db);

            $filter_deleted = true;
            $_groupLevels = $this->group_level->getGroupLevels($vipSettingId, $filter_deleted, $db);
            $_groupLevels_amount = empty($_groupLevels)? 0: count($_groupLevels);
            $_groupLevelCount = $this->group_level->getGroupCurrLevelCount($vipSettingId, $db);

            if( $_groupLevels_amount != $_groupLevelCount
                && !empty($_groupLevels_amount)
            ){
                // update the real level amount into groupLevelCount field.
                $_data = array(
                    'groupLevelCount' => $_groupLevels_amount,
                );
                if( ! empty($doDryRun) ){
                    $_event_info = [];
                    $_event_info['token'] = uniqid('event_'); // $_event_token;
                    $_event_info['token_by_line'] = __LINE__; // for trace cause
                    $_event_info['outer_trace_str'] = $outerTraceStr; // for trace cause
                    $_event_info['switchStr'] = $switchStr;
                    $_event_info['caseStr'] = 'refreshLevelCountOfVipGroup';
                    $_event_info['is_warning'] = 1;
                    $_event_info['affected_group_name'] = $vipGroupRow['__lang_groupName'];
                    $_event_info['affected_vipSettingId'] = $vipSettingId;

                    $_will_call = [];
                    $_will_call['method_name'] = 'CI_DB_active_record::update';
                    $_will_call['event'] = $_event_info;
                    $_will_call['params'] = [];
                    $_will_call['params']['table'] = 'vipsetting';
                    $_will_call['params']['set'] = $_data;
                    $_will_call['params']['where'] = ['vipSettingId' => $vipSettingId];
                    $rlt['dryRun']['call_method_list'][] = $_will_call;
                }else{
                    $db->where('vipSettingId', $vipSettingId);
                    $db->update('vipsetting', $_data);
                    // $db->affected_rows();
                    $rlt['affected_list']['refresh_groupLevelCount_rows'] = $db->affected_rows();
                }
            }
        }

        return  $rlt;
    } // EOF _doUpdate4SoftDelete2LevelOfOther
    /**
     * sync VIPGroup And VIPLevels to others, without sourceDB
     *
     * The extra diff levels between source and others.
     *
     * When The extra diff levels had exist,
     * this function will move the players of the level to default level,
     * and update deleted to 1 instead to delete.
     *
     *
     * The extra diff levels of other, e.g.,
     * In the same Group
     * The currency DBs: source: USD; other1: BRL; other2: THB
     * There are Levels 1, 2 and 3, in USD.
     * There are Levels 1, 2, 3 and 4, in BRL.
     * There are Levels 1 and 2, in THB.
     *
     * For BRL, the extra diff levels of other as the level 4.
     * For THB, the extra diff levels of other as empty.
     *
     *
     *
     * @param string $sourceDB The currency key, like as: cny, brl,...
     * @param integer $featureKey The VIP Group P.K. vipSettingId.
     * @param boolean $insertOnly So far, its not exists in the requirements.
     * @param integer $dryRun, The dryRun mode,
     * - DRY_RUN_MODE_IN_DISABLED, Zero: Execute syncing;
     * - DRY_RUN_MODE_IN_NORMAL, 1: dry run current group
     * - DRY_RUN_MODE_IN_INCREASED_LEVELS, 2: dry run in increase group player level
     * - DRY_RUN_MODE_IN_DECREASED_LEVELS, 3: dry run in decrease group player level
     *
     * P.S. The following modes implemented in vipsetting_management::sync_vip_group()
     * - DRY_RUN_MODE_IN_DISABLED_NORMAL
     * - DRY_RUN_MODE_IN_DISABLED_INCREASED_LEVELS
     * - DRY_RUN_MODE_IN_DISABLED_DECREASED_LEVELS
     *
     * @return void
     */
    public function syncVIPGroupFromOneToOtherMDBWithFixPKid($sourceDB // #1
                                                            , $featureKey  // #2 aka. vipSettingId
                                                            , $insertOnly=false // #3
                                                            , $dryRun = self::DRY_RUN_MODE_IN_NORMAL  // #4
                                                            , $others_in = 'all' // #5
    ){
        $this->load->model(['group_level', 'vipsetting']);
        $result=null;
        if($this->utils->isEnabledMDB() && ! empty($featureKey)){
            $_this = $this;
            $filter_deleted = false; // the deleted rows does not reused when increase.
            // the Group in sourceDB
            $vipGroupList=$this->runAnyOnSingleMDB($sourceDB,function($db)
                    use($featureKey){
                $db->from('vipsetting');
                if(!empty($featureKey)){
                    $db->where('vipSettingId', $featureKey);
                }
                return $this->runMultipleRowArray($db);
            });
            $this->clearFieldsOnRows($vipGroupList, ['vipSettingId']);
            //
            $vipGroupList = $this->appendLangField2Rows($vipGroupList);
            reset($vipGroupList);
            $vipGroupRow_of_sourceDB =current($vipGroupList);
            $this->utils->debug_log('OGP-28577.881.vipGroupRow_of_sourceDB:', $vipGroupRow_of_sourceDB );
            // The Levels under the Group, in sourceDB
            $vipLevelList=$this->runAnyOnSingleMDB($sourceDB,function($db)
                    use($featureKey, $filter_deleted){
                $db->from('vipsettingcashbackrule');
                if(!empty($featureKey)){
                    $db->where('vipSettingId', $featureKey);
                }
                if(!empty($filter_deleted)){
                    $db->where('deleted != 1', null, false);
                }
                $db->order_by('vipLevel', 'asc');

                return $this->runMultipleRowArray($db);
            });

            $this->utils->debug_log('OGP-28577.902.dryRun:', $dryRun, 'DRY_RUN_MODE_IN_DECREASED_LEVELS:', self::DRY_RUN_MODE_IN_DECREASED_LEVELS);
            $_countOfVipLevelList = count($vipLevelList);
            if( intval($dryRun) === self::DRY_RUN_MODE_IN_INCREASED_LEVELS){ // dry run in increase group player level
                $autoIncrementId = $this->runAnyOnSingleMDB($sourceDB,function($db) use($_this){
                    return $_this->utils->getMaxPrimaryIdByTable('vipsettingcashbackrule', true, $db);
                });
                $_countOfVipLevelList++;
                $_vipLevel = []; // vipsettingcashbackruleId, vipSettingId, vipLevel, vipLevelName, deleted
                $_vipLevel['vipsettingcashbackruleId'] = $autoIncrementId;
                $_vipLevel['vipSettingId'] = $featureKey;
                $_vipLevel['groupName'] = $vipGroupRow_of_sourceDB['__lang_groupName'];
                $_vipLevel['vipLevel'] = $_countOfVipLevelList;
                $_vipLevel['vipLevelName'] = 'Level Name '. $_vipLevel['vipLevel']. ' simulated';
                $_vipLevel['deleted'] = 0;
                // if($_countOfVipLevelList > 0) {
                //     $_vipLevel = $vipLevelList[$_countOfVipLevelList-1];
                // }
                array_push($vipLevelList, $_vipLevel);
                $this->utils->debug_log('OGP-28577.910.$vipLevelList.count:', empty($vipLevelList)? 0: count($vipLevelList) );

                $vipGroupRow_of_sourceDB['groupLevelCount'] = $_countOfVipLevelList;
            }else if( intval($dryRun) === self::DRY_RUN_MODE_IN_DECREASED_LEVELS){ // dry run in decrease group player level
                $this->utils->debug_log('OGP-28577.924.dryRun:', $dryRun);
                if($_countOfVipLevelList > 1) {
                    array_pop($vipLevelList);
                }
                $_countOfVipLevelList--;
                $vipGroupRow_of_sourceDB['groupLevelCount'] = $_countOfVipLevelList;
                $this->utils->debug_log('OGP-28577.915.$vipLevelList.count:', empty($vipLevelList)? 0: count($vipLevelList) );
            }
            //
            $vipLevelList = $this->appendLangField2Rows($vipLevelList);

            $result4vipGroup = [];

            // $vipGroupRow_of_sourceDB: the Group of sourceDB
            // $vipLevelList: the Levels of sourceDB
            if(!empty($vipGroupRow_of_sourceDB)){
                // the Group of sourceDB Not Empty

                // collect VIP Group and Levels of other Currency DBs
                $filter_deleted = false;
                $select_fields_list = [];
                // for posi
                $select_fields_list['vipsetting'] = [];
                $select_fields_list['vipsetting'][] = 'groupName';
                $select_fields_list['vipsettingcashbackrule'] = [];
                $select_fields_list['vipsettingcashbackrule'][] = 'vipLevelName';
                $return_source = false;
                $getLevelsViaFK = true;
                $_rlt_list = $this->listVIPGroupAndLevelsWithForeachMultipleDBWithoutSourceDB( $sourceDB // #1
                                                                                            , $featureKey // #2
                                                                                            , $filter_deleted // #3
                                                                                            , $select_fields_list // #4
                                                                                            , $return_source // #5
                                                                                            , $getLevelsViaFK // #6
                                                                                        );
                // $this->utils->debug_log('OGP-28577.852._rlt4listVIPGroupAndLevels:',$_rlt4listVIPGroupAndLevels);
                // $this->utils->debug_log('OGP-28577.852.vipGroupList:',$vipGroupList);
                //
                // strip wrapper
                $_rlt4listVIPGroupAndLevels = [];
                foreach($_rlt_list as $dbKey => $_rlt){
                    if($_rlt['success']){
                        if( ! empty($_rlt['result']['vipsetting'] ) ){
                            // Group row
                            $_rlt4listVIPGroupAndLevels[$dbKey]['vipsetting'] = $_rlt['result']['vipsetting'];
                        }
                        if( ! empty($_rlt['result']['vipsettingcashbackrule'] ) ){
                            // Level Rows
                            $_rlt4listVIPGroupAndLevels[$dbKey]['vipsettingcashbackrule'] = $_rlt['result']['vipsettingcashbackrule'];
                        }
                    }
                } // EOF foreach($_rlt_list as $dbKey => $_rlt){...
                $this->utils->debug_log('OGP-28577.1160._rlt4listVIPGroupAndLevels:',$_rlt4listVIPGroupAndLevels);

                if( $others_in == 'all'){
                    $others_in = '';
                }
                $result4vipGroup = $this->foreachOthersDBWithoutSourceDB($sourceDB, function($db, &$rlt)
                use($vipGroupRow_of_sourceDB, $vipLevelList, $featureKey, $_rlt4listVIPGroupAndLevels, $_this, $dryRun ){

                    $rlt=[];
                    $rlt['success_finial'] = null;
                    $rlt['vipsetting'] = [];
                    $rlt['vipSettingId'] = null;
                    $rlt['vipSettingId_with'] = null;
                    $rlt['vipsettingcashbackrule_list'] = [];
                    if( ! empty($dryRun) ){
                        $rlt['dryRun'] = [];
                        $rlt['dryRun']['call_method_list'] = [];
                    }

                    $vipGroupRow_of_otherDB = [];
                    $dbName = $db->getOgTargetDB();
                    $dbKey = str_replace('_readonly', '', $dbName);
                    if( ! empty($_rlt4listVIPGroupAndLevels[$dbKey]['vipsetting']) ) {
                        $vipGroupRow_of_otherDB = $_rlt4listVIPGroupAndLevels[$dbKey]['vipsetting']; // vipGroupRow of otherDB
                    }

                    $success_list = [];
                    $success_list['vipsetting'] = false;
                    $success_list['vipsettingcashbackruleId_with'] = [];

                    $this->utils->debug_log('OGP-28577.1183.dbKey:', $dbKey, '_rlt4listVIPGroupAndLevels.dbKey:', empty($_rlt4listVIPGroupAndLevels[$dbKey])? null:$_rlt4listVIPGroupAndLevels[$dbKey] );
                    $this->utils->debug_log('OGP-28577.1184.dbKey:', $dbKey, 'vipGroupRow_of_otherDB:',$vipGroupRow_of_otherDB);
                    $success=false;
                    // $db->select('vipSettingId')->from('vipsetting')->where('vipSettingId', $featureKey);
                    // $vipSettingId=$_this->runOneRowOneField('vipSettingId', $db);
                    // $success=false;
                    // $rlt=[];
                    // $vipGroupRow_of_sourceDB = reset($vipGroupList); // get first row, and $vipGroupList should be one row by vipSettingId = $featureKey.
                    //
                    // $vipGroupRow_of_sourceDB = $this->appendLangField2Row($vipGroupRow_of_sourceDB);

                    $this->utils->debug_log('OGP-28577.1191.dbKey:', $dbKey, 'vipGroupRow_of_sourceDB:',$vipGroupRow_of_sourceDB );
                    if($dbKey == 'php'|| 1){

                        $this->utils->debug_log('OGP-28577.1194.dbKey:', $dbKey, '_rlt4listVIPGroupAndLevels.dbKey:', empty($_rlt4listVIPGroupAndLevels[$dbKey])? null:$_rlt4listVIPGroupAndLevels[$dbKey] );
                        $this->utils->debug_log('OGP-28577.1195.dbKey:', $dbKey, 'vipGroupRow_of_sourceDB:',$vipGroupRow_of_sourceDB);
                        $this->utils->debug_log('OGP-28577.1196.dbKey:', $dbKey, 'vipGroupRow_of_otherDB:',$vipGroupRow_of_otherDB);
                    }

                    if( ! empty($vipGroupRow_of_sourceDB) ){
                        // dryRun for preview
                        if( ! empty($dryRun) ){
                            $isEmpty4vipGroupRow_of_otherDB = empty($vipGroupRow_of_otherDB['vipSettingId']);
                            $_event_info = [];
                            $_event_info['token'] = uniqid('event_');
                            $_event_info['token_by_line'] = __LINE__; // for trace cause
                            if( ! $isEmpty4vipGroupRow_of_otherDB ){
                                $_event_info['caseStr'] = 'overrideVipGroup';
                                $_event_info['is_warning'] = 1;
                                $_event_info['affected_group_name'] = $vipGroupRow_of_otherDB['__lang_groupName'];
                                $_event_info['affected_vipSettingId'] = $vipGroupRow_of_otherDB['vipSettingId'];
                            }else{
                                $_vipSettingId = $this->utils->getMaxPrimaryIdByTable('vipsetting', true, $db);
                                $_event_info['caseStr'] = 'newVipGroup';
                                $_event_info['is_warning'] = 0;
                                $_event_info['affected_group_name'] = $vipGroupRow_of_sourceDB['__lang_groupName'];
                                $_event_info['affected_vipSettingId'] = $_vipSettingId;
                            }
                            $_event_info['affected_vipSettingId'] = $featureKey;

                            if( ! $isEmpty4vipGroupRow_of_otherDB ){
                                $vipsetting_id = $featureKey;
                                $rlt['vipSettingId'] = $vipsetting_id;
                                $rlt['vipSettingId_with'] = 'editVIPGroup, dryRun=1';

                                $_will_call = [];
                                $_will_call['event'] = $_event_info;
                                $_will_call['method_name'] = 'Group_level::editVIPGroup';
                                $_will_call['params'] = [];
                                $_will_call['params']['data'] = $vipGroupRow_of_sourceDB;
                                $_will_call['params']['vipsettingId'] = $featureKey;
                                $rlt['dryRun']['call_method_list'][] = $_will_call;
                            }else{
                                // $_vipSettingId = $this->utils->getMaxPrimaryIdByTable('vipsetting', true, $db);
                                $rlt['vipSettingId'] = $_vipSettingId;
                                $rlt['vipSettingId_with'] = 'addVIPGroup, dryRun=1';

                                $_dryRun_vipGroupRow_of_sourceDB = $vipGroupRow_of_sourceDB;
                                $_dryRun_vipGroupRow_of_sourceDB['vipSettingId'] = $featureKey; // the P.K. of the other DB, that has the same as the P.K. of the sourceDB.
                                $_dryRun_vipGroupRow_of_sourceDB['groupLevelCount'] = 0; // to skip, handle in levels sync

                                $_will_call = [];
                                $_will_call['event'] = $_event_info;
                                $_will_call['method_name'] = 'Group_level::addVIPGroup';
                                $_will_call['params'] = [];
                                $_will_call['params']['data'] = $_dryRun_vipGroupRow_of_sourceDB;
                                $_will_call['before_row'] = [];
                                $_will_call['after_row'] = $vipGroupRow_of_sourceDB;
                                $rlt['dryRun']['call_method_list'][] = $_will_call;
                            }

                        }else if( ! empty($vipGroupRow_of_otherDB['vipSettingId']) ){
                            // When the id is exists in other DB
                            // Update
                            $vipsetting_id = $featureKey;
                            $data = $_this->filterLangField2Row($vipGroupRow_of_sourceDB);
                            $_rlt = $_this->group_level->editVIPGroup($data, $vipsetting_id, $db);
                            if($_rlt){
                                $success_list['vipsetting'] = true;
                                $rlt['vipSettingId'] = $vipsetting_id;
                                $rlt['vipSettingId_with'] = 'editVIPGroup';
                            }
                        }else{
                            /// When the id is Not  exists in other DB
                            // insert
                            $vipGroupRow_copied_sourceDB = $vipGroupRow_of_sourceDB;
                            $vipGroupRow_copied_sourceDB['vipSettingId'] = $featureKey; // the P.K. of the other DB, that has the same as the P.K. of the sourceDB.
                            $vipGroupRow_copied_sourceDB['groupLevelCount'] = 0; // to skip, handle in levels sync
                            // filter the "__lang_" prefix fields
                            $this->utils->debug_log('OGP-28577.1088.dbKey:', $dbKey, 'filterLangField2Row', $_this->filterLangField2Row($vipGroupRow_copied_sourceDB));
                            $_vipSettingId = $_this->group_level->addVIPGroup($_this->filterLangField2Row($vipGroupRow_copied_sourceDB), $db);
                            $this->utils->debug_log('OGP-28577.1217.dbKey:', $dbKey, '_vipSettingId', $_vipSettingId);
                            if($featureKey == $_vipSettingId){
                                $success_list['vipsetting'] = true;
                                $rlt['vipSettingId'] = $_vipSettingId;
                                $rlt['vipSettingId_with'] = 'addVIPGroup';
                            }
                            // TODO, re-query for level
                            // $_rlt4listVIPGroupAndLevels[$dbKey]['vipsettingcashbackrule']
                        }
                        /// moved to after sync levels.
                        // // re-assign for $rlt
                        // $db->select('vipSettingId, groupLevelCount, groupName, status, deleted')
                        //     ->from('vipsetting')
                        //     ->where('vipSettingId', $rlt['vipSettingId']);
                        // $_vipGroupRow = $_this->runOneRowArray($db);
                        // $rlt['vipsetting'] = $_vipGroupRow;

                    } // EOF if( ! empty($vipGroupRow_of_sourceDB) ){...

                    $rlt['success_finial'] =  $success_list['vipsetting'];

                    $this->utils->debug_log('OGP-28577.1236.dbKey:', $dbKey, 'SyncVipGroup.foreachMultipleDBWithoutSourceDB.rlt:', $rlt, 'success_list:', $success_list, 'dbKey:', $dbKey);

                    $vipLevelRows = []; //  form other
                    if( ! empty($_rlt4listVIPGroupAndLevels[$dbKey]['vipsettingcashbackrule']) ){ // form other
                        $vipLevelRows = $_rlt4listVIPGroupAndLevels[$dbKey]['vipsettingcashbackrule'];
                    }
                    $this->utils->debug_log('OGP-28577.1242.dbKey:', $dbKey, 'vipLevelList', $vipLevelList); // form source
                    $this->utils->debug_log('OGP-28577.1243.dbKey:', $dbKey, 'vipLevelRows', $vipLevelRows); // form other

                    /// for Levels sync,  $vipLevelList means The Levels under the Group of sourceDB
                    foreach($vipLevelList as $vipLevelRow_of_source ){ // The levels under the VIP Group of source
                        $called_increaseVipGroupLevel = false;

                        $vipLevelId = $vipLevelRow_of_source['vipsettingcashbackruleId'];
                        if( ! empty($vipLevelRows) ){
                            // _vipLevelRows form other
                            $_vipLevelRows = array_filter($vipLevelRows, function($v, $k) use ($vipLevelRow_of_source) {
                                $is_met = true;
                                // $is_met = $is_met && ($vipLevelRow_of_source['vipSettingId'] == $v['vipSettingId']);
                                // $is_met = $is_met && ($vipLevelRow_of_source['vipLevel'] == $v['vipLevel']);

                                $is_met = $is_met && ($vipLevelRow_of_source['vipsettingcashbackruleId'] == $v['vipsettingcashbackruleId']);
                                return $is_met;
                            }, ARRAY_FILTER_USE_BOTH);
                            #reset values of array after filter
                            $_vipLevelRows = array_values($_vipLevelRows);
                        }else{
                            $_vipLevelRows = [];
                        }
                        if( empty($_vipLevelRows) ){
                            $_vipLevelRows = [ [] ];
                        }
                        $_vipLevelRow = reset($_vipLevelRows); // get first. If Not empty, there should be only one row here.
                        $this->utils->debug_log('OGP-28577.1266.dbKey:', $dbKey, 'vipLevelRows', $vipLevelRows);
                        $this->utils->debug_log('OGP-28577.1267.dbKey:', $dbKey, '_vipLevelRow', $_vipLevelRow);
                        $this->utils->debug_log('OGP-28577.1268.dbKey:', $dbKey, 'vipLevelRow_of_source.vipsettingcashbackruleId', $vipLevelRow_of_source['vipsettingcashbackruleId']);

                        // dryRun for preview
                        if( ! empty($dryRun) ){

                            $_vipsettingcashbackruleId = $vipLevelRow_of_source['vipsettingcashbackruleId'];
                            $db->select()
                                ->from('vipsettingcashbackrule')
                                ->join('vipsetting', 'vipsettingcashbackrule.vipSettingId = vipsetting.vipSettingId')
                                ->where('vipsettingcashbackruleId', $_vipsettingcashbackruleId);
                            $_vipLevelRow = $_this->runOneRowArray($db);
                            $this->utils->debug_log('OGP-28577.1143.dbKey:', $dbKey, 'vipLevelRow_of_source', $vipLevelRow_of_source);
                            $this->utils->debug_log('OGP-28577.1144.dbKey:', $dbKey, '_vipLevelRow', $_vipLevelRow);
                            if( empty($_vipLevelRow) ){


                                $called_increaseVipGroupLevel = true;
                                $_addTransaction = false;

                                $_event_info = [];
                                $_event_info['token'] = uniqid('event_');
                                $_event_info['token_by_line'] = __LINE__; // for trace cause
                                $_event_info['caseStr'] = 'increaseVipGroupLevel';
                                $_event_info['is_warning'] = 0;
                                $_event_info['affected_vipSettingId'] = $vipLevelRow_of_source['vipSettingId'];
                                $_event_info['affected_group_name'] = $vipGroupRow_of_sourceDB['__lang_groupName'];
                                $_event_info['affected_level_name'] = $vipLevelRow_of_source['__lang_vipLevelName'];
                                $_event_info['affected_vipsettingcashbackruleId'] =  $vipLevelRow_of_source['vipsettingcashbackruleId'];

                                $_will_call = [];
                                $_will_call['event'] = $_event_info;
                                $_will_call['method_name'] = 'Group_level::increaseVipGroupLevel';
                                $_will_call['params'] = [];
                                $_will_call['params']['vipSettingId'] = $featureKey;
                                $_will_call['params']['addTransaction'] = $_addTransaction;
                                $rlt['dryRun']['call_method_list'][] = $_will_call;

                                $_will_call = [];
                                $_will_call['event'] = $_event_info;
                                $_will_call['method_name'] = 'Group_level::editData';
                                $_will_call['params'] = [];
                                $_will_call['params']['tableName'] = 'vipsettingcashbackrule';
                                $_will_call['params']['whereArr'] = array('vipsettingcashbackruleId' => $_vipsettingcashbackruleId, 'dryRun' => true);
                                $_will_call['params']['updateDataArr'] = ['vipsettingcashbackruleId' => $vipLevelRow_of_source['vipsettingcashbackruleId'] ];
                                $rlt['dryRun']['call_method_list'][] = $_will_call;
                            }else{

                                $_vipLevelRow = $_this->appendLangField2Row($_vipLevelRow);
                                $this->utils->debug_log('OGP-28577.1182.dbKey:', $dbKey, '_vipLevelRow', $_vipLevelRow);
                                $this->utils->debug_log('OGP-28577.1183.dbKey:', $dbKey, 'vipLevelRow_of_source', $vipLevelRow_of_source);
                                $this->utils->debug_log('OGP-28577.1184.dbKey:', $dbKey, '_vipLevelRow.vipSettingId', $_vipLevelRow['vipSettingId']);
                                $this->utils->debug_log('OGP-28577.1185.dbKey:', $dbKey, 'vipLevelRow_of_source.vipSettingId', $vipLevelRow_of_source['vipSettingId']);

                                if( $_vipLevelRow['vipSettingId'] !== $vipLevelRow_of_source['vipSettingId']){
                                    // move vipLevel
                                    $_event_info = [];
                                    $_event_info['token'] = uniqid('event_');
                                    $_event_info['token_by_line'] = __LINE__; // for trace cause
                                    $_event_info['caseStr'] = 'moveVipLevel'; //  move level to another Group
                                    $_event_info['is_warning'] = 1;
                                    $_event_info['from_vipSettingId'] = $_vipLevelRow['vipSettingId'];
                                    $_event_info['from_group_name'] = $_vipLevelRow['__lang_groupName']; // affected in other
                                    $_event_info['from_level_name'] = $_vipLevelRow['__lang_vipLevelName'];
                                    $_event_info['from_level_deleted'] = $_vipLevelRow['deleted'];
                                    $_event_info['from_vipsettingcashbackruleId'] = $_vipLevelRow['vipsettingcashbackruleId'];
                                    $_event_info['to_vipSettingId'] = $featureKey;
                                    $_event_info['to_group_name'] = $vipGroupRow_of_sourceDB['__lang_groupName']; // affected in other
                                    $_event_info['to_level_name'] = $vipLevelRow_of_source['__lang_vipLevelName'];
                                    $_event_info['to_level_deleted'] = $_vipLevelRow['deleted']; // aka. from_level_deleted
                                    $_event_info['to_vipsettingcashbackruleId'] = $vipLevelRow_of_source['vipsettingcashbackruleId'];
                                }else{
                                    $_event_info = [];
                                    $_event_info['token'] = uniqid('event_');
                                    $_event_info['token_by_line'] = __LINE__; // for trace cause
                                    $_event_info['caseStr'] = 'overrideVipLevel';
                                    $_event_info['is_warning'] = 1;
                                    $_event_info['affected_vipSettingId'] = $_vipLevelRow['vipSettingId'];
                                    $_event_info['affected_group_name'] = $_vipLevelRow['__lang_groupName']; // affected in other
                                    $_event_info['affected_level_name'] = $_vipLevelRow['__lang_vipLevelName'];
                                    $_event_info['affected_vipsettingcashbackruleId'] = $_vipLevelRow['vipsettingcashbackruleId'];
                                }

                            }

                        }else if( empty($_vipLevelRow) ){ // _vipLevelRow from other
                            $vipSettingId = $featureKey;
                            // When the id is Not  exists in other DB
                            $_addTransaction = false;
                            /// DEBUG
                            $db->select()
                                ->from('vipsettingcashbackrule')
                                ->order_by('vipsettingcashbackruleId', 'desc')
                                ->limit(5);
                            $_dbg_vipLevelRow = $_this->runMultipleRowArray($db);
                            $this->utils->debug_log('OGP-28577.1238.will.increaseVipGroupLevel.dbKey:', $dbKey, '_dbg_vipLevelRow:', $_dbg_vipLevelRow);
                            /// DEBUG END
                            $_vipsettingcashbackruleId = $_this->group_level->increaseVipGroupLevel($vipSettingId, $_addTransaction, $db);
                            $called_increaseVipGroupLevel = true;

                            /// adjust vipsettingcashbackruleId to the same as source.
                            $flag = $_this->group_level->editData('vipsettingcashbackrule', // table
                                    array('vipsettingcashbackruleId' => $_vipsettingcashbackruleId), // where
                                    ['vipsettingcashbackruleId' => $vipLevelRow_of_source['vipsettingcashbackruleId'] ] // data
                                    , $db );
                            if($flag){
                                $_vipsettingcashbackruleId =  $vipLevelRow_of_source['vipsettingcashbackruleId'];
                            }

                            // re-assign
                            $db->select()
                                ->from('vipsettingcashbackrule')
                                ->where('vipsettingcashbackruleId', $_vipsettingcashbackruleId);
                            $_vipLevelRow = $_this->runOneRowArray($db);
                            $this->utils->debug_log('OGP-28577.1292.dbKey:', $dbKey, '_vipsettingcashbackruleId:', $_vipsettingcashbackruleId, '_vipLevelRow:', $_vipLevelRow);
                        }else{
                            // update data after moveVipLevel and override VipLevel
                            $_vipsettingcashbackruleId = $_vipLevelRow['vipsettingcashbackruleId'];
                            $this->utils->debug_log('OGP-28577.1295.dbKey:', $dbKey, '_vipsettingcashbackruleId:', $_vipsettingcashbackruleId);
                        }

                        // unset the settings
                        unset($vipLevelRow_of_source['vip_upgrade_id']);
                        unset($vipLevelRow_of_source['vip_downgrade_id']);
                        // TODO, Sync with vip_upgrade_setting
                        if( ! empty($dryRun) ){
                            $_is_exist_in_other = empty($_vipLevelRow)? false: true;
                            $_will_call = [];
                            $_will_call['method_name'] = 'Group_level::editVipGroupBonusRule';
                            $_will_call['event'] = $_event_info; // the part of $_event_info['caseStr'] = 'increaseVipGroupLevel';
                            $_will_call['params'] = [];
                            $_will_call['params']['data'] = $vipLevelRow_of_source;
                            // for confirm the data of other
                            $_will_call['_is_exist_in_other'] = $_is_exist_in_other;
                            $_will_call['_row_of_other'] = $_vipLevelRow;

                            $rlt['dryRun']['call_method_list'][] = $_will_call;
                        }else{
                            $_this->group_level->editVipGroupBonusRule($_this->filterLangField2Row($vipLevelRow_of_source), $db);
                        }
                        // $success = true;
                        $this->utils->debug_log('OGP-28577.1303.dbKey:', $dbKey, 'called_increaseVipGroupLevel:', $called_increaseVipGroupLevel, 'vipLevelId:', $vipLevelId, 'dryRun:', $dryRun );

                        // query for $rlt
                        $db->select('vipsettingcashbackruleId, vipSettingId, vipLevel, vipLevelName, deleted')
                            ->from('vipsettingcashbackrule')
                            ->where('vipsettingcashbackruleId', $_vipsettingcashbackruleId);
                        $_vipsettingcashbackrule_row =  $_this->runOneRowArray($db);
                        // return $success;

                        $__sync_with = $called_increaseVipGroupLevel? 'Add': 'Update';
                        if( ! empty($dryRun) ){
                            $__sync_with .= ', dryRun=true';
                        }

                        $_vipsettingcashbackrule_row['__sync_with'] = $__sync_with;

                        $rlt['vipsettingcashbackrule_list'][$_vipsettingcashbackruleId] = $_vipsettingcashbackrule_row;
                        $success_list['vipsettingcashbackruleId_with'][$_vipsettingcashbackruleId] = $__sync_with;

                    } // EOF foreach($vipLevelList as $vipLevelRow_of_source ){...

                    /// handle the extra diff levels of other
                    $_vipLevelRows_diff_to_source = []; // for group_level::adjustPlayerLevel()
                    $_vipLevelRows_diff_to_source = array_filter($vipLevelRows, function($v, $k) use ($vipLevelList, $dbKey) {
                        // $vipLevelRows, form other
                        // $vipLevelList, form source
                        $is_met = true;
                        if( ! empty($vipLevelList) ){ // // The levels under the VIP Group of source

                            $_is_met = ! in_array($v['vipsettingcashbackruleId'], array_column($vipLevelList, 'vipsettingcashbackruleId') );
                            $is_met = $is_met && $_is_met;
                        }else{
                            $is_met = false;
                        }
                        return $is_met;
                    }, ARRAY_FILTER_USE_BOTH);
                    #reset values of array after filter
                    $_vipLevelRows_diff_to_source = array_values($_vipLevelRows_diff_to_source);
                    $_this->utils->debug_log('OGP-28577.1332.dbKey:', $dbKey, '_vipLevelRows_diff_to_source', $_vipLevelRows_diff_to_source);


                    $currencyInfo=$_this->getCurrencyByDB($db);
                    $default_level_id = $currencyInfo['player_default_level_id'];
                    foreach($_vipLevelRows_diff_to_source as $_vipLevelRows_diff ){

                        if( $default_level_id != $_vipLevelRows_diff['vipsettingcashbackruleId']) {
                            $playerIds = $_this->group_level->getPlayerIdsByLevelId($_vipLevelRows_diff['vipsettingcashbackruleId'], $db);
                            $_this->utils->debug_log('OGP-28577.1336.dbKey:', $dbKey
                                                    , 'playerIds.count:', count($playerIds)
                                                    , 'default_level_id:', $default_level_id
                                                    , '_vipLevelRows_diff.vipsettingcashbackruleId:', $_vipLevelRows_diff['vipsettingcashbackruleId']
                                                );
                            // $playerIds[] = 999; // Test move players, when level will be soft delete.
                            if( ! empty($dryRun) ){
                                if( ! empty($playerIds) ){
                                    $playerIds_count = count($playerIds);
                                    $_event_info = [];
                                    $_event_info['token'] = uniqid('event_');
                                    $_event_info['token_by_line'] = __LINE__; // for trace cause
                                    $_event_info['caseStr'] = 'adjustPlayerLevel';
                                    $_event_info['is_warning'] = 1;
                                    $_event_info['affected_level_name'] = $_vipLevelRows_diff['__lang_vipLevelName'];
                                    $_event_info['playerIds_count'] = $playerIds_count;
                                    $_VipLevelDetail = $_this->group_level->getVipGroupLevelDetails($_vipLevelRows_diff['vipsettingcashbackruleId'], $db);
                                    $_event_info['affected_group_name'] = lang($_VipLevelDetail['groupName']);
                                    $_event_info['affected_vipSettingId'] = lang($_VipLevelDetail['vipSettingId']);
                                    $_event_info['affected_vipsettingcashbackruleId'] = lang($_VipLevelDetail['vipsettingcashbackruleId']);

                                    foreach($playerIds as $_playerId ){
                                        $_will_call = [];
                                        $_will_call['method_name'] = 'Group_level::adjustPlayerLevel';
                                        $_will_call['event'] = $_event_info;
                                        $_will_call['params'] = [];
                                        $_will_call['params']['playerId'] = $_playerId;
                                        $_will_call['params']['newLevelId'] = $default_level_id;
                                        $_will_call['_player_count_in_level'] = $playerIds_count;
                                        $_will_call['posi'] = [];
                                        $_will_call['posi']['vipsettingcashbackruleId'] = $_vipLevelRows_diff['vipsettingcashbackruleId'];
                                        $rlt['dryRun']['call_method_list'][] = $_will_call;
                                    }
                                }
                            }else if( ! empty($playerIds) ){
                                foreach($playerIds as $_playerId ){ // move players to default level, before delete level
                                    $_this->group_level->adjustPlayerLevel($_playerId, $default_level_id, $db);
                                }
                            }

                            if( ! empty($dryRun) ){
                                $_event_info = [];
                                $_event_info['token'] = uniqid('event_');
                                $_event_info['token_by_line'] = __LINE__; // for trace cause
                                $_event_info['caseStr'] = 'softDeleteVipLevel';
                                $_event_info['is_warning'] = 1;
                                $_event_info['affected_level_name'] = $_vipLevelRows_diff['__lang_vipLevelName'];
                                $_VipLevelDetail = $_this->group_level->getVipGroupLevelDetails($_vipLevelRows_diff['vipsettingcashbackruleId'], $db);
                                $_event_info['affected_group_name'] = lang($_VipLevelDetail['groupName']);
                                $_event_info['affected_vipSettingId'] = $_VipLevelDetail['vipSettingId'];
                                $_event_info['affected_level_deleted'] = $_VipLevelDetail['deleted'];
                                $_event_info['affected_vipsettingcashbackruleId'] = $_VipLevelDetail['vipsettingcashbackruleId'];

                                $_will_call = [];
                                $_will_call['method_name'] = 'CI_DB_active_record::update';
                                $_will_call['event'] = $_event_info;
                                $_will_call['params'] = [];
                                $_will_call['params']['table'] = 'vipsettingcashbackrule';
                                $_will_call['params']['set'] = ['deleted' => 1];
                                $_will_call['params']['where'] = ['vipsettingcashbackruleId' => $_vipLevelRows_diff['vipsettingcashbackruleId']];
                                $rlt['dryRun']['call_method_list'][] = $_will_call;
                            }else{
                                //update deleted to 1 instead to delete
                                $_data = array(
                                    'deleted' => 1,
                                );
                                $db->where('vipsettingcashbackruleId', $_vipLevelRows_diff['vipsettingcashbackruleId']);
                                $db->update('vipsettingcashbackrule', $_data);
                            }

                        }else{
                            $_this->utils->error_log('OGP-28577.1356.dbKey:', $dbKey, 'default_level_id should difference to _vipLevelRows_diff.vipsettingcashbackruleId');
                            $_this->utils->debug_log('OGP-28577.1357.dbKey:', $dbKey
                                                    , 'default_level_id:', $default_level_id
                                                    , '_vipLevelRows_diff.vipsettingcashbackruleId:', $_vipLevelRows_diff['vipsettingcashbackruleId']
                                                );
                        }
                    } // EOF foreach($_vipLevelRows_diff_to_source as $_vipLevelRows_diff ){...

                    // , $filter_deleted = fals
                    // if($filter_deleted){
                    //     $this->db->where('vipsettingcashbackrule.deleted = 0', null, false);
                    // }
                    // getGroupLevels
                    // getGroupCurrLevelCount
                    // groupLevelCount
                    $vipSettingId = $featureKey;
                    $filter_deleted = true;
                    $_groupLevels = $_this->group_level->getGroupLevels($vipSettingId, $filter_deleted, $db);
                    $_groupLevels_amount = empty($_groupLevels)? 0: count($_groupLevels);
                    $_groupLevelCount = $_this->group_level->getGroupCurrLevelCount($vipSettingId, $db);
                    if($_groupLevels_amount != $_groupLevelCount && !empty($_groupLevels_amount) ){
                        // update the real level amount into groupLevelCount field.
                        $_data = array(
                            'groupLevelCount' => $_groupLevels_amount,
                        );
                        if( ! empty($dryRun) ){
                            $_event_info = [];
                            $_event_info['token'] = uniqid('event_');
                            $_event_info['token_by_line'] = __LINE__; // for trace cause
                            $_event_info['caseStr'] = 'refreshLevelCountOfVipGroup';
                            $_event_info['is_warning'] = 1;
                            $_event_info['affected_group_name'] = $vipGroupRow_of_sourceDB['__lang_groupName'];
                            $_event_info['affected_vipSettingId'] = $vipSettingId;

                            $_will_call = [];
                            $_will_call['method_name'] = 'CI_DB_active_record::update';
                            $_will_call['event'] = $_event_info;
                            $_will_call['params'] = [];
                            $_will_call['params']['table'] = 'vipsetting';
                            $_will_call['params']['set'] = $_data;
                            $_will_call['params']['where'] = ['vipSettingId' => $vipSettingId];
                            $rlt['dryRun']['call_method_list'][] = $_will_call;
                        }else{
                            $db->where('vipSettingId', $vipSettingId);
                            $db->update('vipsetting', $_data);
                        }
                    } // EOF if($_groupLevels_amount != $_groupLevelCount && !empty($_groupLevels_amount) ){...


                    if($success_list['vipsetting']){
                        // re-assign to $rlt for groupLevelCount
                        $db->select('vipSettingId, groupLevelCount, groupName, status, deleted')
                            ->from('vipsetting')
                            ->where('vipSettingId', $rlt['vipSettingId']);
                        $_vipGroupRow = $_this->runOneRowArray($db);
                        $rlt['vipsetting'] = $_vipGroupRow;
                    }


                    if( ! empty($dryRun) ){
                        $rlt['success_finial'] = true; // always be true in dryrun
                    }else if( count($rlt['vipsettingcashbackrule_list']) != count($vipLevelList) ){
                        // detect levels has sync success
                        // The levels amount of other DB , that is Not eq. to the amount of source

                        $rlt['success_finial'] = $rlt['success_finial'] && false;
                    }

                    $this->utils->debug_log('OGP-28577.1382.dbKey:', $dbKey
                                , 'success_finial', $rlt['success_finial']
                                , 'foreach.foreachMultipleDBWithoutSourceDB.success_list', $success_list);

                    return $rlt['success_finial'];
                    // return $success;
                }, false // readonly
                , $others_in); // EOF $this->foreachOthersDBWithoutSourceDB()...

                // $result4vipGroup format as,
                // @[dbKey]['success'] bool
                // @[dbKey]['result'] array
                // @[dbKey]['result']['success_finial'] The finial success flag.
                // @[dbKey]['result']['vipsetting'] After updated form souece, the vipsetting row( hidded some fields) of other currency DB.
                // @[dbKey]['result']['vipSettingId'] After updated form souece, the vipsetting P.K. of other currency DB.
                // @[dbKey]['result']['vipSettingId_with'] The method for update action from souece.
                // @[dbKey]['result']['vipsettingcashbackrule_list'] After updated form souece, the level list of other currency DB.
                $this->utils->debug_log('OGP-28577.1398.SyncVipGroup.foreachMultipleDBWithoutSourceDB.result4vipGroup', $result4vipGroup);
            }else{
                // Not found any VIP Group by featureKey
                $this->utils->debug_log('OGP-28577.1401.SyncVipGroup.featureKey is empty.', 'vipGroupRow_of_sourceDB:', $vipGroupRow_of_sourceDB, 'featureKey:',$featureKey);
            } // EOF if(!empty($vipGroupRow_of_sourceDB)){...
            $result = $result4vipGroup;

        } // EOF if($this->utils->isEnabledMDB() && ! empty($featureKey)){...
        return $result;
    } // EOF syncVIPGroupFromOneToOtherMDBWithFixPKid



    public function syncPlayerVipLevelFromCurrentToOtherMDB($player_id, $sourceDB = null, $trigger_method = null, $insertOnly=false){
        if(empty($sourceDB) ){
            $sourceDB=$this->getActiveTargetDB();
        }
        if(empty($trigger_method) ){
            $trigger_method = __METHOD__;
        }

        return $this->syncPlayerVipLevelFromOneToOtherMDB($sourceDB, $trigger_method, $player_id, $insertOnly);
    }
    //
    public function syncPlayerVipLevelByUsernameFromCurrentToOtherMDB($username, $trigger_method = null, $insertOnly=false){
        $sourceDB=$this->getActiveTargetDB();
        if(empty($trigger_method) ){
            $trigger_method = __METHOD__;
        }
        $player = $this->runAnyOnSingleMDB($sourceDB,function($db)
                use($username){
            $db->from('player')->where('username', $username);
            return $this->runOneRowArray($db);
        });
        $player_id = 0;
        if( ! empty($player) ){
            $player_id = $player['playerId'];
        }
        return $this->syncPlayerVipLevelFromOneToOtherMDB($sourceDB, $trigger_method, $player_id, $insertOnly);
    }
    //
    public function syncPlayerVipLevelFromSuperToOtherMDB($player_id, $insertOnly=false){
        return $this->syncPlayerVipLevelFromOneToOtherMDB(Multiple_db::SUPER_TARGET_DB, __METHOD__, $player_id, $insertOnly);
    }
    //
    public function syncPlayerVipLevelFromOneToOtherMDB($sourceDB, $trigger_method, $player_id, $insertOnly=false){
        $this->load->library(['group_level_lib']);
        if(empty($trigger_method) ){
            $trigger_method = __METHOD__;
        }

        $sql4playerlevel = '';
        $sql4playerlevel .= ' SELECT player.playerId, playerlevel.playerGroupId';
        $sql4playerlevel .= ' FROM player';
        $sql4playerlevel .= ' JOIN playerlevel ON playerlevel.playerId = player.playerId';
        $sql4playerlevel .= ' WHERE player.playerId = ?';
        $_this = $this;
        $result=null;
        $playerlevel = $this->runAnyOnSingleMDB($sourceDB,function($db)
                use($player_id, $_this, $sql4playerlevel){

                $rows = $_this->runRawSelectSQLArray($sql4playerlevel, [$player_id], $db);
                $row = reset($rows);
                return $row;
        });
        if( $this->utils->isEnabledMDB() && ! empty($playerlevel) ){
            $playerId = $player_id;
            $newPlayerLevel = $playerlevel['playerGroupId'];
            $processed_by = Users::SUPER_ADMIN_ID;
            $action_management_title ='Multiple DB Module';
            $logsExtraInfo = [];
            $logsExtraInfo['source_method'] = $trigger_method;
            $logsExtraInfo['source_currency'] = $sourceDB;
            $result = $this->group_level_lib->adjustPlayerLevelWithLogsWithForeachMultipleDBWithoutSourceDB($playerId
                , $newPlayerLevel
                , $processed_by
                , $action_management_title
                , $logsExtraInfo
            );
        }
        return $result;
    } // EOF syncPlayerVipLevelFromOneToOtherMDB

    public function filterLangField2Row($_row, $prefix='__lang_'){
        $filtered = [];
        foreach($_row as $field_name => $field_value){
            if( strpos($field_name, $prefix) === false){
                $filtered[$field_name] = $field_value;
            }
        }
        return $filtered;
    } // EOF filterLangField2Row
    public function appendLangField2Row($_row, $_langs_field_list =[]){
        if( empty($_langs_field_list ) ){
            $_langs_field_list = [];
            $_langs_field_list[] = 'groupName';
            $_langs_field_list[] = 'vipLevelName';
        }
        // for vipsetting
        foreach($_row as $_field => $_val){
            if(in_array($_field, $_langs_field_list) ){
                $_row['__lang_'. $_field] = lang($_val);
            }
        }
        return $_row;
    } // EOF appendLangField2Row
    //
    public function filterLangField2Rows($_rows, $prefix='__lang_'){
        $_filtered_rows = [];
        foreach($_rows as $_indexNum => $_row){
            $_filtered_row = $this->filterLangField2Row($_row, $prefix);
            $_filtered_rows[$_indexNum] = $_filtered_row;
        } // EOF foreach($_rows as...
        return $_filtered_rows;
    } // EOF filterLangField2Rows
    public function appendLangField2Rows($_rows, $_langs_field_list = []){
        foreach($_rows as $_indexNum => $_row){
            $_rowHasLangFields = $this->appendLangField2Row($_row, $_langs_field_list);
            $_rows[$_indexNum] = array_merge($_rows[$_indexNum], $_rowHasLangFields);
        } // EOF foreach($_rows as...
        return $_rows;
    } // EOF appendLangField2Rows

    /**
     * Undocumented function
     *
     * @param string $sourceDB The currency key
     * @param integer $vipSettingId The VIP Group P.K. , as the field, "vipsetting.vipSettingId".
     * @param boolean $filter_deleted Its usually be true. When query the soft deleted data to use false.
     * @param array $select_fields_list The 2d array for select_clause_list,
     * - @[vipsetting] array The fields for VIP Group aka. data-table, "vipsetting".
     * - @[vipsettingcashbackrule] array The fields for VIP Level aka. data-table, "vipsettingcashbackrule".
     * @param boolean $return_source
     * @param boolean $getLevelsViaFK
     * @return array $result formats as,
     * - {dbkey} string The currency key. such as "usd", "brl", "thb",...
     * - $result[{dbkey}] array
     * - $result[{dbkey}]['success'] bool
     * - $result[{dbkey}]['result'] array
     * - $result[{dbkey}]['result']['vipsetting'] array The Group Row.
     * - $result[{dbkey}]['result']['vipsettingcashbackrule'] array The Level Rows of the group.
     */
    public function listVIPGroupAndLevelsWithForeachMultipleDBWithoutSourceDB( $sourceDB //  #1
                                                                            , $vipSettingId //  #2
                                                                            , $filter_deleted = true //  #3
                                                                            , $select_fields_list = [] //  #4
                                                                            , $return_source = false // #5
                                                                            , $getLevelsViaFK = false // #6
    ){

        $_this = $this;
        $readonly=true;

        if( empty($select_fields_list['vipsetting']) ){
            $select_fields_list['vipsetting'] = [];
        }
        //
        if( empty($select_fields_list['vipsettingcashbackrule']) ){
            $select_fields_list['vipsettingcashbackrule'] = [];
        }

        /// The select fields recommended includes P.K. field.
        // In Vip Group
        if( ! in_array('vipSettingId', $select_fields_list['vipsetting']) ){
            array_push($select_fields_list['vipsetting'], 'vipSettingId');
        }
        // In Vip Level
        if( ! in_array('vipsettingcashbackruleId', $select_fields_list['vipsettingcashbackrule']) ){
            array_push($select_fields_list['vipsettingcashbackrule'], 'vipsettingcashbackruleId');
        }
        if( ! in_array('vipSettingId', $select_fields_list['vipsettingcashbackrule']) ){
            array_push($select_fields_list['vipsettingcashbackrule'], 'vipSettingId'); // F.K. to VIP Group
        }

        $vipGroupLevels_of_source = $this->runAnyOnSingleMDB($sourceDB, function($db)
                                        use($vipSettingId, $filter_deleted){

            // VIP Group
            $_table_name = 'vipsetting';
            $db->from($_table_name);
            if( ! empty($select_fields_list[$_table_name]) ){
                $select_clause_list = $select_fields_list[$_table_name];
                foreach($select_clause_list as $_select_key => $_select_field){
                    if( is_array($_select_field) ){
                        // like as $db->select($select_name, null, false);
                        call_user_func_array([$db, 'select'], $_select_field);
                    }else if( is_string($_select_field) ){
                        $db->select($_select_field);
                    }
                }// EOF foreach
            }else{
                $db->select('*');
            }
            if(!empty($vipSettingId)){
                $db->where('vipSettingId', $vipSettingId);
            }
            // assign to $rlt for return
            $rlt['vipsetting'] = $this->runMultipleRowArray($db); // 2-d array

            /// VIP Levels
            // $_table_name = 'vipsettingcashbackrule';
            // $select_clause_list =[];
            // $where_clause =[];
            // $this->do_runMultipleRowArray($_table_name, $select_clause_list, $where_clause, $db);
            //
            $_table_name = 'vipsettingcashbackrule';
            $db->from($_table_name);
            if( ! empty($select_fields_list[$_table_name]) ){
                $select_clause_list = $select_fields_list[$_table_name];
                foreach($select_clause_list as $_select_key => $_select_field){
                    if( is_array($_select_field) ){
                        // like as $db->select($select_name, null, false);
                        call_user_func_array([$db, 'select'], $_select_field);
                    }else if( is_string($_select_field) ){
                        $db->select($_select_field);
                    }
                }
            }else{
                $db->select('*');
            }
            if(!empty($vipSettingId)){
                $db->where('vipSettingId', $vipSettingId);
            }
            if(!empty($filter_deleted)){
                $db->where('deleted != 1', null, false);
            }
            $db->order_by('vipLevel', 'asc');
            // assign to $rlt for return
            $rlt[$_table_name] = $this->runMultipleRowArray($db); // 2-d array

            return $rlt;
        }); // EOF $vipGroupLevels_of_source = $this->runAnyOnSingleMDB(...



        $result = $this->foreachMultipleDBWithoutSourceDB($sourceDB, function($db, &$rlt)
        use($_this, $vipGroupLevels_of_source, $filter_deleted, $select_fields_list, $getLevelsViaFK){

            // Group of source
            $vipSettingId = 0;
            if( ! empty($vipGroupLevels_of_source['vipsetting']) ){
                $vipGroupRow_of_source = reset($vipGroupLevels_of_source['vipsetting']);
                $vipSettingId = $vipGroupRow_of_source['vipSettingId'];
            }
            // Levels of source
            $vipLevelRows_of_source = [ [] ];
            if( ! empty($vipGroupLevels_of_source['vipsettingcashbackrule']) ){
                $vipLevelRows_of_source = $vipGroupLevels_of_source['vipsettingcashbackrule'];
            }

            $rlt = [];
            $success = true;

            // Group of other currency DB
            $_table_name = 'vipsetting';
            $db->from($_table_name);
            if( ! empty($select_fields_list[$_table_name]) ){
                $select_clause_list = $select_fields_list[$_table_name];
                foreach($select_clause_list as $_select_key => $_select_field){
                    if( is_array($_select_field) ){
                        // like as $db->select($select_name, null, false);
                        call_user_func_array([$db, 'select'], $_select_field);
                    }else if( is_string($_select_field) ){
                        $db->select($_select_field);
                    }
                }// EOF foreach
            }else{
                $db->select('*');
            }
            $db->where('vipSettingId', $vipSettingId);
            if(!empty($filter_deleted)){
                $db->where('deleted != 1', null, false);
            }
            $_rlt4vipGroup = $this->runMultipleRowArray($db);
            //
            if( empty($_rlt4vipGroup) ){
                $_rlt4vipGroup = [ [] ];
            }
            $vipGroupRow = reset($_rlt4vipGroup); // get first, and $_rlt4vipGroup should be one data only.
            if( ! empty($vipGroupRow) ){
                $rlt[$_table_name] = $vipGroupRow;
            }
            // EOF, Group of other currency DB

            // $dbName = $db->getOgTargetDB();
            // $dbKey = str_replace('_readonly', '', $dbName);
            // $_this->utils->debug_log('OGP-28577.2307.dbKey:', $dbKey, 'last_query:', $db->last_query() );

            if( ! empty($vipGroupRow) ){
                // Levels of other currency DB
                $_table_name = 'vipsettingcashbackrule';
                $db->from($_table_name);
                if( ! empty($select_fields_list[$_table_name]) ){
                    $select_clause_list = $select_fields_list[$_table_name];
                    foreach($select_clause_list as $_select_key => $_select_field){
                        if( is_array($_select_field) ){
                            // like as $db->select($select_name, null, false);
                            call_user_func_array([$db, 'select'], $_select_field);
                        }else if( is_string($_select_field) ){
                            $db->select($_select_field);
                        }
                    }// EOF foreach
                }else{
                    $db->select('*');
                }

                if($getLevelsViaFK){
                    $db->where('vipSettingId', $vipGroupRow['vipSettingId']);
                }else{
                    $db->where_in('vipsettingcashbackruleId',  array_column($vipLevelRows_of_source, 'vipsettingcashbackruleId') );
                }

                if(!empty($filter_deleted)){
                    $db->where('deleted != 1', null, false);
                }
                // $db->limit('2'); // TEST
                $db->order_by('vipLevel', 'asc');
                $_rlt4vipLevels = $this->runMultipleRowArray($db);


                $dbName = $db->getOgTargetDB();
                $dbKey = str_replace('_readonly', '', $dbName);
                $_this->utils->debug_log('OGP-28577.2327.dbKey:', $dbKey, 'last_query:', $db->last_query() );

                if( ! empty($_rlt4vipLevels) ){
                    $rlt['vipsettingcashbackrule'] = $_rlt4vipLevels;
                }else{
                    $rlt['vipsettingcashbackrule'] = []; // for No levels in the group
                }
                // EOF, Levels of other currency DB
            }

            // $dbName = $db->getOgTargetDB();
            // $dbKey = str_replace('_readonly', '', $dbName);
            // $_this->utils->debug_log('2159.rlt:', $rlt, 'success:', $success, 'dbKey:', $dbKey);

            return $success;
        }, $readonly); // EOF $result = $this->foreachMultipleDBWithoutSourceDB(...

        // strip suffix, "_readonly"
        foreach($result as $_dbkey => $_rlt){
            $_remove_suffix_db = str_replace('_readonly', '', $_dbkey);
            if($_remove_suffix_db != $_dbkey){ // has appended suffix
                $result[ $_remove_suffix_db ] = $_rlt;

                // clear for duplicate data
                $result[ $_dbkey ] = [];
                unset($result[ $_dbkey ]);
            }
        }

        // multi-langs convertion
        foreach($result as $_dbkey => &$_rlt){

            // $result[ $_dbkey ]
            if( ! empty($_rlt['result']) ){
                /// for vipsetting
                $_rlt['result']['vipsetting'] = $this->appendLangField2Row($_rlt['result']['vipsetting']);
            }

            if( ! empty($_rlt['result']) ){
                /// for vipsettingcashbackrule list
                $_rlt['result']['vipsettingcashbackrule'] = $this->appendLangField2Rows($_rlt['result']['vipsettingcashbackrule']);
            }
        }

        if($return_source){
            $result['__source'] = $vipGroupLevels_of_source;
            $result['__sourceDB'] = $sourceDB;
        }


        return $result;
    }// EOF listVIPGroupAndLevelsWithForeachMultipleDBWithoutSourceDB

    public function getCurrencyByDB($db){
        if(empty($db)){
            return $this->utils->getConfig('super_default_currency_info');
        }
        $availableCurrencyList=$this->utils->getAvailableCurrencyList();
        $dbKey=$db->getOgTargetDB();
        if($dbKey==Multiple_db::SUPER_TARGET_DB || empty($availableCurrencyList)){
            return $this->utils->getConfig('super_default_currency_info');
        }
        return $availableCurrencyList[$dbKey];
    }

    public function enableCurrencyForPlayerOnSuper($playerId, $currencyKey){

    }

    public function disableCurrencyForPlayerOnSuper($playerId, $currencyKey){

    }

    const ID_TYPE_PLAYER_ID='player_id';
    const ID_TYPE_AGENCY_ID='agency_id';
    const ID_TYPE_USER_ID='user_id';
    const ID_TYPE_AFFILIATE_ID='affiliate_id';

    public function enableDisableCurrencyOnDB($id, $type,
        $enableCurrencyKeyList, $disableCurrencyKeyList, $db){

        if(empty($id)){
            return false;
        }

        $idFldName=null;
        switch ($type) {
            case self::ID_TYPE_PLAYER_ID:
                $idFldName='player_id';
                break;
            case self::ID_TYPE_AGENCY_ID:
                $idFldName='agency_id';
                break;
            case self::ID_TYPE_USER_ID:
                $idFldName='user_id';
                break;
            case self::ID_TYPE_AFFILIATE_ID:
                $idFldName='affiliate_id';
                break;
            default:
                return false;
                break;
        }

        $currencyKeyList=array_merge($enableCurrencyKeyList, $disableCurrencyKeyList);
        //delete
        $db->where($idFldName, $id)->where_in('currency_key', $currencyKeyList)
            ->where('status', self::DB_FALSE);
        $success=$this->runRealDelete('currency_permissions', $db);
        if($success && !empty($disableCurrencyKeyList)){
            $rows=[];
            //insert disable only
            foreach ($disableCurrencyKeyList as $currencyKey) {
                $rows[]=[
                    $idFldName=>$id,
                    'currency_key'=>$currencyKey,
                    'status'=>self::DB_FALSE,
                    'created_at'=>$this->utils->getNowForMysql(),
                ];
            }

            $success=$this->runBatchInsertWithLimit($db, 'currency_permissions', $rows);
        }

        return $success;

    }


    public function syncPlayerRegSettingsFromCurrentToOtherMDB($type){
        $sourceDB=$this->getActiveTargetDB();
        return $this->syncPlayerRegSettingsFromOneToOtherMDB($sourceDB, $type);
    }

    public function syncPlayerRegSettingsFromSuperToOtherMDB($type){
        return $this->syncPlayerRegSettingsFromOneToOtherMDB(Multiple_db::SUPER_TARGET_DB, $type);
    }

    /**
     * sync player reg settings from super to others
     * @param  string $playerId
     * @return array of bool
     */
    public function syncPlayerRegSettingsFromOneToOtherMDB($sourceDB, $type){
        $result=null;
        if($this->utils->isEnabledMDB()){
            list($playerRegSettings, $operatorSettings)=$this->runAnyOnSingleMDB($sourceDB,function($db)
                    use($type){

                $settings=['registration_captcha_enabled', 'login_captcha_enabled',
                    'login_after_registration_enabled', 'login_after_registration_enabled',
                    'remember_password_enabled', 'forget_password_enabled', 'restrict_username_enabled',
                    'generate_pep_gbg_auth_after_registration_enabled', 'set_password_min_max',
                ];

                $db->from('operator_settings')->where_in('name', $settings);
                $operatorSettings=$this->runMultipleRowArray($db);

                $db->from('registration_fields')->where('type', $type);
                $playerRegSettings=$this->runMultipleRowArray($db);

                return [$playerRegSettings, $operatorSettings];
            });
            if(!empty($playerRegSettings)){
                $result=$this->foreachMultipleDBWithoutSourceDB($sourceDB, function($db, &$rlt)
                        use($playerRegSettings, $operatorSettings, $type){

                    $rlt=['registration_fields'=>0, 'operator_settings'=>0];

                    //delete then insert all
                    $db->where('type', $type);
                    $success=$this->runRealDelete('registration_fields', $db);
                    if($success){
                        foreach ($playerRegSettings as $row) {
                            unset($row['id']);
                            $success=$this->runInsertData('registration_fields', $row, $db);
                            if(!$success){
                                break;
                            }
                            $rlt['registration_fields']++;
                        }
                    }
                    //check name
                    if($success){
                        foreach ($operatorSettings as $row) {
                            unset($row['id']);
                            $db->where('name', $row['name']);
                            $success=$this->runRealDelete('operator_settings', $db);
                            if(!$success){
                                break;
                            }
                            $success=$this->runInsertData('operator_settings', $row, $db);
                            if(!$success){
                                break;
                            }
                            $rlt['operator_settings']++;
                        }
                        //clear cache
                        $this->utils->deleteCache();
                    }
                    return $success;
                });
            }

        }

        return $result;
    }

    public function syncAffiliateRegSettingsFromCurrentToOtherMDB($type){
        $sourceDB=$this->getActiveTargetDB();
        return $this->syncAffiliateRegSettingsFromOneToOtherMDB($sourceDB, $type);
    }

    public function syncAffiliateRegSettingsFromSuperToOtherMDB($type){
        return $this->syncAffiliateRegSettingsFromOneToOtherMDB(Multiple_db::SUPER_TARGET_DB, $type);
    }

    /**
     * sync aff reg settings from super to others
     * @param  string $playerId
     * @return array of bool
     */
    public function syncAffiliateRegSettingsFromOneToOtherMDB($sourceDB, $type){
        $result=null;
        if($this->utils->isEnabledMDB()){
            $affRegSettings=$this->runAnyOnSingleMDB($sourceDB,function($db)
                    use($type){

                $db->from('registration_fields')->where('type', $type);
                $affRegSettings=$this->runMultipleRowArray($db);

                return $affRegSettings;
            });
            if(!empty($affRegSettings)){
                $result=$this->foreachMultipleDBWithoutSourceDB($sourceDB, function($db, &$rlt)
                        use($affRegSettings, $type){

                    $rlt=['registration_fields'=>0];

                    //delete then insert all
                    $db->where('type', $type);
                    $success=$this->runRealDelete('registration_fields', $db);
                    if($success){
                        foreach ($affRegSettings as $row) {
                            unset($row['id']);
                            $success=$this->runInsertData('registration_fields', $row, $db);
                            if(!$success){
                                break;
                            }
                            $rlt['registration_fields']++;
                        }
                    }
                    return $success;
                });
            }

        }

        return $result;
    }

    //===super report===================================================
    const QUERY_REPORT_TYPE_ONE_PAGE='onePage';
    const QUERY_REPORT_TYPE_COUNT='count';
    const QUERY_REPORT_TYPE_TOTAL='total';
    const QUERY_REPORT_TYPE_SUMMARY='summary';
    const QUERY_REPORT_TYPE_EXPORT='export';
    public function syncSummary2Report($from, $to){
        //insert to super db
        return $this->mergeTableOverMDBToSuper('summary2_report_daily', 'summary_date>=? and summary_date<=?', [$from, $to]);
    }
	public function syncPlayerReportHourly($from, $to){
		$from=$this->utils->formatDateHourForMysql(new DateTime($from));
		$to=$this->utils->formatDateHourForMysql(new DateTime($to));
		//insert to super db
		return $this->mergeTableOverMDBToSuper('player_report_hourly', 'date_hour>=? and date_hour<=?', [$from, $to]);
	}
    public function syncGameReportHourly($from, $to){
        $from=$this->utils->formatDateHourForMysql(new DateTime($from));
        $to=$this->utils->formatDateHourForMysql(new DateTime($to));
        //insert to super db
        return $this->mergeTableOverMDBToSuper('game_report_hourly', 'date_hour>=? and date_hour<=?', [$from, $to]);
    }
    public function syncPaymentReportDaily($from, $to){
        $from=$this->utils->formatDateForMysql(new DateTime($from));
        $to=$this->utils->formatDateForMysql(new DateTime($to));
        //sync to super db
        return $this->mergeTableOverMDBToSuper('payment_report_daily', 'payment_date>=? and payment_date<=?', [$from, $to]);
    }
    public function syncCashbackReportDaily($from, $to){
        $from=$this->utils->formatDateForMysql(new DateTime($from));
        $to=$this->utils->formatDateForMysql(new DateTime($to));
        //sync to super db
        return $this->mergeTableOverMDBToSuper('cashback_report_daily', 'cashback_date>=? and cashback_date<=?', [$from, $to]);
    }
    public function syncPromotionReportDetails($from, $to){
        //sync to super db
        return $this->mergeTableOverMDBToSuper('promotion_report_details', 'promotion_datetime>=? and promotion_datetime<=?', [$from, $to]);
    }
    public function syncTotalPlayerGameDay($from, $to){
        //sync to super db
        return $this->mergeTableOverMDBToSuper('total_player_game_day', 'date>=? and date<=?', [$from, $to]);
    }

    public function syncSummaryGameTotalBet($from, $to){
        //sync to super db
        return $this->mergeTableOverMDBToSuper('summary_game_total_bet', 'api_date>=? and api_date<=?', [$from, $to]);
    }

    public function syncSummary_game_total_bet_daily($from, $to){
        //sync to super db
        return $this->mergeTableOverMDBToSuper('summary_game_total_bet_daily', 'api_date>=? and api_date<=?', [$from, $to]);
    }

    /**
     *
     * build select query
     * set select on db
     *
     * @param  array $columns
     * @param  object $db
     */
    public function buildSelectOnDBFromColumns($columns, $db){
        // 'dt' => index of column,
        // 'alias' => sql alias,
        // 'name' => readable name,
        // 'select' => sql select string,
        // 'visible' => ,
        // 'formatter' => formatter function or standard formatter
        if(!empty($columns)){
            foreach ($columns as $col) {
                //default is visible
                if(!isset($col['visible']) || $col['visible']){
                    if(isset($col['alias'])){
                        $db->select($col['select'].' as '.$col['alias'], false);
                    }else{
                        $db->select($col['select'], false);
                    }
                }
            }
        }
    }
    /**
     * build count on db
     * @param  array $columns
     * @param  object $db
     * @return array $colForCount
     */
    public function buildCountOnDBFromColumns($columns, $db){
        $colForCount=null;
        if(!empty($columns)){
            foreach ($columns as $col) {
                if(isset($col['key_for_count'])){
                    $db->select('count('.$col['select'].') as '.$col['alias'], false);
                    $colForCount=$col;
                    break;
                }
            }
        }
        return $colForCount;
    }
    /**
     * build total columns
     * @param  array $columns
     * @param  object $db
     * @return array $totalColMap
     */
    public function buildTotalOnDBFromColumns($columns, $db, &$existsSumField=false){
        // $map=[];
        // $i=0;
        foreach ($columns as $column) {
            if(!isset($col['visible']) || $col['visible']){
                //only keep sum field
                if(isset($column['select_sum'])){
                    $existsSumField=true;
                    $alias=$column['select'];
                    if(isset($column['alias'])){
                        $alias=$column['alias'];
                    }
                    $db->select($column['select_sum'].' as '.$alias, false);
                    // $map[$i]=$column;
                    // $i++;
                }else{
                    $alias=$column['select'];
                    if(isset($column['alias'])){
                        $alias=$column['alias'];
                    }
                    $db->select('"" as '.$alias, false);
                }
            }
        }

        // return $map;
    }
    // public function convertToSumColumns($columns){
    //     $sumColumns=[];
    //     // $i=0;
    //     foreach ($columns as $column) {
    //         //only keep sum field
    //         if(isset($column['select_sum']) && isset($column['alias'])){
    //             $sumColumns[]=$column;
    //         }
    //     }
    //     return $sumColumns;
    // }
    /**
     *
     * @param  array $joinList
     * [
     *     ['table'=>, 'mode'=>(left, inner), 'join_condition'=>]
     * ]
     * @param  object $db
     *
     */
    public function buildJoinOnDB($joinList, $db){
        if(!empty($joinList)){
            foreach ($joinList as $join) {
                if(empty($join['mode'])){
                    $join['mode']='inner';
                }
                $db->join($join['table'], $join['join_condition'], $join['mode']);
            }
        }
    }

    /**
     *
     * @param  array $groupList,
     * @param  object $db
     *
     */
    public function buildGroupByOnDB($groupList, $db){
        if(!empty($groupList)){
            foreach ($groupList as $group) {
                //no escape
                $db->group_by($group, false);
            }
        }
    }
    public function buildOrderByOnDB($orderList, $db){
        if(!empty($orderList)){
            foreach ($orderList as $ord) {
                //no escape
                $db->order_by($ord['field'], $ord['direction'], false);
            }
        }
    }
    public function buildSimpleWhereOnDB($whereList, $db){
        if(!empty($whereList)){
            foreach ($whereList as $where) {
                $db->where($where['key'], $where['value']);
            }
        }
    }
    /**
     * build limit on $db
     * @param  array $conditions
     * @param  object $db
     */
    public function buildCommonLimitOnDB($conditions, $db){
        $size=$conditions['limitBy']['sizePerPage'];
        $startRow=($conditions['limitBy']['currentPage']-1)*$size;
        $db->limit($size, $startRow);
    }
    /**
     * 'dt' => index of column,
     * 'alias' => sql alias,
     * 'name' => readable name,
     * 'select' => sql select string,
     * 'visible' => ,
     * 'formatter' => formatter function or standard formatter
     *
     * @param  array $columns
     * @return array $header ['label'=>, 'key'=>, 'fixed'=>]
     *
     */
    public function buildHeaderByColumns($columns){
        $header=[];
        if(!empty($columns)){
            foreach ($columns as $column) {
                if(!isset($column['dt'])){
                    continue;
                }
                if(isset($col['visible']) && !$col['visible']){
                    continue;
                }
                $h=['label'=>$column['name'], 'key'=>$column['alias'], 'fixed'=>false,
                'sortable'=>true, 'align'=>'left'];
                if(isset($column['fixed'])){
                    $h['fixed']=$column['fixed'];
                }
                if(isset($column['sortable'])){
                    $h['sortable']=$column['sortable'];
                }
                if(isset($column['width'])){
                    $h['width']=intval($column['width']);
                }
                if(isset($column['minWidth'])){
                    $h['minWidth']=intval($column['minWidth']);
                }
                if(isset($column['align'])){
                    $h['align']=$column['align'];
                }else{
                    if(isset($column['formatter']) ){
                        if($column['formatter']=='currencyFormatter'
                            || $column['formatter']=='percentageFormatter'){
                            $h['align']='right';
                        }
                    }
                }
                $header[]=$h;
            }
        }
        return $header;
    }

    public function buildNameListFromColumns($columns){
        $headerNameList=[];
        foreach ($columns as $col) {
            //index and visible
            if(!isset($col['dt'])){
                continue;
            }
            if(isset($col['visible']) && !$col['visible']){
                continue;
            }

            $headerNameList[]=$col['name'];
        }
        return $headerNameList;
    }

    public function formatAnyValue($type, &$d, $row, $keepEmpty=false, $exporting=false){
        $success=true;
        if(is_callable($type)){
            //call format function
            $d=$type($d, $row, $exporting);
            if(is_array($d)){
                $this->utils->debug_log('format result', $d);
                $d=$d['value'];
            }
            return $success;
        }
        if(($d===null || $d==='') && $keepEmpty){
            $d='';
            return $success;
        }

        switch ($type) {
            case 'percentageFormatter':
                if(empty($d)){
                    $d=0;
                }
                $d=$this->utils->formatCurrencyNoSym($d * 100).'%';
                break;
            case 'currencyFormatter':
                if(empty($d)){
                    $d=0;
                }
                $d=$this->utils->formatCurrencyNoSym($d);
                break;
            case 'languageFormatter':
                if(!empty($d)){
                    $d=lang($d);
                }else{
                    $d='';
                }
                break;
            case 'dateTimeFormatter':
                if(!empty($d)){
                    $d=date('Y-m-d H:i:s', strtotime($d));
                }else{
                    $d='';
                }
                break;
            case 'dateFormatter':
                if(!empty($d)){
                    $d=date('Y-m-d', strtotime($d));
                }else{
                    $d='';
                }
                break;
            default:
                //unknown formatter
                $this->utils->error_log('unknown formatter', $type, $d);
                $success=false;
                break;
        }

        return $success;
    }

    public function formatColumns(&$row, $columns, $headerOfDB, $keepEmpty=false, $exporting=false){
        $success=true;

        $asscRow=array_combine($headerOfDB, $row);
        for ($i=0; $i < count($row); $i++) {
            // $this->utils->debug_log($i, $colMap[$i]);
            if(isset($columns[$i]) && isset($columns[$i]['formatter'])){
                $success=$this->formatAnyValue($columns[$i]['formatter'], $row[$i], $asscRow, $keepEmpty, $exporting);
                if(!$success){
                    break;
                }
            }
        }

        return $success;
    }

    // public function convertColumnsToMap($columns){
    //     $map=[];
    //     for ($i=0; $i < count($columns); $i++) {
    //         //only keep exist index
    //         $map[$i]=$columns[$i];
    //     }

    //     return $map;
    // }
    public function generateOrderByDefine($orderBy, $columns){
        $orderList=[];
        if(!empty($orderBy)){
            if(!empty($orderBy['orderAlias']) && !empty($orderBy['direction'])){
                //try add
                $orderList[]=[
                    'field'=>$orderBy['orderAlias'],
                    'direction'=>$orderBy['direction']];
            }
        }
        return $orderList;
    }

    public function getSuperReportSettings($reportType){
        $settings;
        $super_report_settings=$this->utils->getConfig('super_report_settings');
        if(isset($super_report_settings[$reportType])){
            $settings=$super_report_settings[$reportType];
        }else{
            //default settings
            $settings=$this->utils->getConfig('default_super_settings');
        }
        return $settings;
    }

    public function commonRemoteExportCSV($conditions, $reportName, $modelName, $funcName, $db=null){
        //create remote job
        $this->load->library(['lib_queue']);
        $this->load->model(['queue_result']);
        $params=[
            'event'=>[
                'name'=>Queue_result::EVENT_EXPORT_CSV,
                'class'=>'ExportEvent',
                'data'=>[
                    'conditions'=>$conditions,
                    'reportName'=>$reportName,
                    'exportingModel'=>$modelName,
                    'exportingFunc'=>$funcName,
                ]
            ]
        ];
        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=0;
        $this->utils->debug_log('trigger remote event:export', $reportName, $funcName);
        $token=$this->lib_queue->triggerAsyncRemoteEvent(Queue_result::SYSTEM_UNKNOWN, $params,
            $callerType, $caller);
        $url='/system_management/common_queue/'.$token;
        return ['result_url'=>$url, 'token'=>$token];
    }


    public function syncGameTagFromOneToOtherMDB($sourceDB, $gameDescId = null){
        $result=null;
        if($this->utils->isEnabledMDB() && !empty($gameDescId)){

            $this->load->model(['agency_model', 'game_tag_list']);

            $externalGameId = $this->runAnyOnSingleMDB($sourceDB,function($db)
                    use($gameDescId){
                $db->from('game_description')->where('id', $gameDescId);
                return $this->runOneRowOneField('external_game_id', $db);
            });

            if(!empty($externalGameId)){
                $tagCodes = $this->runAnyOnSingleMDB($sourceDB,function($db)
                    use($gameDescId){
                    $db->select('game_tags.tag_code, game_tag_list.expired_at, game_tag_list.game_order, game_tag_list.status');
                    $db->from('game_tag_list')->where('game_tag_list.game_description_id', $gameDescId);
                    $db->join('game_tags', 'game_tag_list.tag_id = game_tags.id', 'left');
                    $db->where('game_tag_list.game_description_id', $gameDescId);
                    return $this->runMultipleRowArray($db);
                });

                if(!empty($tagCodes)){
                    $result=$this->foreachMultipleDBWithoutSourceDB($sourceDB, function($db, &$rlt)
                        use($externalGameId, $tagCodes){
                        // if($db->getOgTargetDB() == "eth"){
                        //     $rlt = "empty id";
                        //     return true;;
                        // }

                        //check other db
                        $db->from('game_description')->where('external_game_id', $externalGameId);
                        $id = $this->runOneRowOneField('id', $db);

                        $success = false;
                        if(empty($id)){
                            $rlt = "External game id not exist!";
                            return true;
                        }

                        $tags = array_column($tagCodes, 'tag_code');
                        $db->select('id, tag_code');
                        $db->from('game_tags')->where_in('tag_code', $tags);
                        $tagIds = $this->runMultipleRowArray($db);
                        if(empty($tagIds)){
                            $rlt = "Tag id's not exist!";
                            return true;
                        }

                        foreach ($tagIds as $key => $tagId) {
                            $key = array_search($tagId['tag_code'], array_column($tagCodes, 'tag_code'));
                            if(isset($tagCodes[$key])){
                                $tagCodes[$key]['id'] = $tagId['id'];
                            }
                        }

                        $runUpdate = true;
                        $rlt = null;
                        foreach ($tagCodes as $key => $tagCode) {
                            if(!isset($tagCode['id'])){
                                continue;
                            }
                            // $tagCode['game_order'] = 9999;#testing if updating
                            $success = $this->game_tag_list->addToGameTagList($tagCode['id'], $id, $tagCode['status'], $tagCode['game_order'], $tagCode['expired_at'], $runUpdate, $db);
                            if($success){
                                $tagCode['game_desc_id'] = $id;
                                $tagCode['game_tag_id'] = $tagCode['id'];
                                $rlt = $tagCode;
                                $this->utils->debug_log('success added tag on db: '.$tagCode['id']);
                            }
                        }
                        return true;
                    });
                }
            }else{
                $this->utils->error_log('not found game desc id: '.$gameDescId);
            }
        }

        return $result;
    }

    public function getPlayerTagByCurrencyToSuper($currency, $player_username) {
        $currency = strtolower($currency);

        // get super db
        $method = __METHOD__;
        $super_db = $this->getSuperDBFromMDB();
        $result = [];

        $this->utils->debug_log($method, 'superDB getOgTargetDB', $super_db->getOgTargetDB());

        $this->foreachMultipleDBWithoutSuper(function($db) use($super_db, $method, $currency, $player_username, &$result) {
            $db_name = $db->getOgTargetDB();
            $this->utils->debug_log($method, 'db_name', $db_name);

            if ($currency == $db_name) {
                $db->select('tagName, tagColor')->from('tag')
                ->join('playertag', 'tag.tagId = playertag.tagId', 'left')
                ->join('player', 'playertag.playerId = player.playerId', 'left')
                ->where([
                    'player.username' => $player_username,
                    /* 'playertag.status' => 1,
                    'playertag.isDeleted' => 0 */
                ]);
                $result = $this->runMultipleRowArray($db);

                $this->utils->info_log(__METHOD__, 'db_name', $db_name, 'result', $result);

                return $result;
            }
        });

        return $result;
    }

    public function getTagsByCurrencyToSuper($currency) {
        $currency = strtolower($currency);

        // get super db
        $method = __METHOD__;
        $super_db = $this->getSuperDBFromMDB();
        $result = [];

        $this->utils->debug_log($method, 'superDB getOgTargetDB', $super_db->getOgTargetDB());

        $this->foreachMultipleDBWithoutSuper(function($db) use($super_db, $method, $currency, &$result) {
            $db_name = $db->getOgTargetDB();
            $this->utils->debug_log($method, 'db_name', $db_name);

            if ($currency == $db_name) {
                $db->select('tagName')->from('tag');
                $result = $this->runMultipleRowArray($db);

                $this->utils->info_log(__METHOD__, 'db_name', $db_name, 'result', $result);

                return $result;
            }
        });

        return $result;
    }

    public function addPlayerTagsToSuperCashbackReportDaily() {
        // get super db
        $method = __METHOD__;
        $superDb = $this->getSuperDBFromMDB();
        $result = [];

        $this->utils->debug_log($method, 'superDB getOgTargetDB', $superDb->getOgTargetDB());

        $this->foreachMultipleDBWithoutSuper(function($db) use($superDb, $method, &$result) {
            $db_name = $db->getOgTargetDB();
            $this->utils->debug_log($method, 'db_name', $db_name);

            $cashBackReportDailyTable = 'cashback_report_daily';

            $sql= <<<EOD
SELECT
playerId,
CONCAT('["', GROUP_CONCAT(tag.tagName separator '","'), '"]') AS playerTags
FROM playertag
JOIN tag ON playertag.tagId = tag.tagId
GROUP BY playertag.playerId
EOD;

            $query = $db->query($sql);
            $results = $query->result_array();

            foreach ($results as $result) {
                $superDb->where(['player_id' => $result['playerId']])->set(['playerTags' => $result['playerTags']]);
                $this->runAnyUpdate($cashBackReportDailyTable, $superDb, true);
            }

            return true;
        });

        return true;
    }

    public function syncTagsToSuper() {
        // get super db
        $method = __METHOD__;
        $super_db = $this->getSuperDBFromMDB();
        $result = [];

        $this->utils->debug_log($method, 'superDB getOgTargetDB', $super_db->getOgTargetDB());

        $this->foreachMultipleDBWithoutSuper(function($db) use($super_db, $method, &$result) {
            $tagTable = 'tag';

            $db_name = $db->getOgTargetDB();
            $this->utils->debug_log($method, 'db_name', $db_name);

            $db->from($tagTable);
            $dbTags = $this->runMultipleRowArray($db);
            $insert_to_tag_table = 0;

            // insert currency tag to super
            foreach ($dbTags as $dbTag) {
                $is_exist = $this->isRecordExist($tagTable, [
                    'tagName' => $dbTag['tagName'],
                ], $super_db);

                if (!$is_exist) {
                    $insert_data = [
                        'tagName' => $dbTag['tagName'],
                        'tagDescription' => $dbTag['tagDescription'],
                        'tagColor' => $dbTag['tagColor'],
                        'createBy' => $dbTag['createBy'],
                        'createdOn' => $dbTag['createdOn'],
                        'updatedOn' => $dbTag['updatedOn'],
                        'status' => $dbTag['status'],
                        'evidence_type' => $dbTag['evidence_type'],
                    ];

                    $is_inserted = $this->runInsertData($tagTable, $insert_data, $super_db);

                    if ($is_inserted) {
                        $insert_to_tag_table++;
                    }
                }
            }

            $this->utils->debug_log(__METHOD__, 'insert_to_tag_table', $insert_to_tag_table);
            $insert_to_tag_table = 0;

            return true;
        });

        return true;
    }

    //===super report===================================================

}
