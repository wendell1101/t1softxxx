<?php
require_once dirname(__FILE__) . '/base_model.php';

/**
 * Class Transfer_condition
 *
 * General behaviors include :
 *
// * * Cancelling transfer conditions
// * * Disable/create transfer condition for player, deposit, promo
 *
 * @category Marketing Management
 * @version 1.0.0
 * @copyright 2013-2022 tot
 */
class Transfer_condition extends BaseModel {

	protected $tableName = 'transfer_conditions';

	protected $idField = 'id';

	const STATUS_ACTIVE = 0;
    const STATUS_CANCEL = 1;
    const STATUS_COMPLETE = 2;

    // detail status flag
    const DETAIL_STATUS_ACTIVE = 1;
    const DETAIL_STATUS_CANCELED_DUE_TO_NEW_DEPOSIT = 2;   //存款时完成
    const DETAIL_STATUS_MANUAL_CANCELED = 3; //手動取消
    const DETAIL_STATUS_CANCELLED_DUE_TO_LOW_BALANCE = 4;  // if player balance is less than condition amount
    const DETAIL_STATUS_CANCELED_BY_DELETING_PROMO_MANAGER = 5;
    const DETAIL_STATUS_FINISHED_BET_REQUIREMENT = 6;

    /**
     * overview : get player transfer condition
     *
     * @param int	$playerId
     * @return array|bool
     */
    public function getPlayerTransferCondition($playerId, $withBetting=true){
        $this->load->model(array('transactions', 'promorules', 'game_logs'));

        $this->db->distinct()->select('promorules.promorulesId,
						   promorules.promoName,
						   promorules.promoType,
						   transfer_conditions.id,
						   transfer_conditions.promotion_id as promotion_id,
						   transfer_conditions.player_id,
						   transfer_conditions.condition_amount as conditionAmount,
						   transfer_conditions.started_at,
						   transfer_conditions.updated_at,
						   transfer_conditions.completed_at,
						   transfer_conditions.disallow_transfer_in,
						   transfer_conditions.disallow_transfer_out,
						   transfer_conditions.bet_details,
						   transfer_conditions.status')
            ->from($this->tableName)
            ->join('promorules', 'transfer_conditions.promotion_id = promorules.promorulesId', 'left')
            ->where('transfer_conditions.status', self::STATUS_ACTIVE)
            ->where('transfer_conditions.player_id', $playerId);

        $query = $this->db->get();
        $this->utils->printLastSQL();

        if ($query->num_rows() > 0) {

            $gameDescIdArrFromLevel=$this->group_level->getAllowedGameIdArr($playerId);

            foreach ($query->result_array() as $row) {
                $gameDescIdArr=$gameDescIdArrFromLevel;

                //check if deposit promo and non deposit promo (email,registration,mobile)
                if ($row['promoType'] == Promorules::PROMO_TYPE_NON_DEPOSIT ||
                    $row['promoType'] == Promorules::PROMO_TYPE_DEPOSIT) {
                    if ($row['promorulesId']) {
                        $this->load->model(array('promorules'));
                        $gameDescIdArr = $this->promorules->getPlayerGames($row['promorulesId']);
                        $this->utils->debug_log('get transfer condition gameDescIdArr from promo rules:'.$row['promorulesId']);
                    }
                }

                if($withBetting){
                    $row['currentBet'] = $this->game_logs->totalPlayerBettingAmountWithLimitByVIP($row['player_id'], $row['started_at'], null, $gameDescIdArr);
                }else{
                    $row['currentBet'] = 0;
                }

                $this->getDisallowTransferWalletName($playerId, $row);

                if($row['promoName'] == Promorules::SYSTEM_MANUAL_PROMO_RULE_NAME){
                    $row['promoName'] = lang('promo.'. Promorules::SYSTEM_MANUAL_PROMO_RULE_NAME);
                }

                $data[] = $row;
            }
            return $data;
        }

        return false;
    }

    /**
     * detail: get the cancelled transfer condition of a certain player
     *
     * @param int $player_id
     * @return array
     */
    public function getPlayerCancelledTransferCondition($playerId = '', $where, $values) {
        $this->load->model(array('transactions', 'promorules', 'game_logs'));

        $this->db->distinct()->select('promorules.promorulesId,
						   promorules.promoName,
						   promorules.promoType,
						   transfer_conditions.id as tc_id,
						   transfer_conditions.player_promo_id,
						   transfer_conditions.promotion_id as promotion_id,
						   transfer_conditions.player_id,
						   transfer_conditions.condition_amount,
						   transfer_conditions.started_at,
						   transfer_conditions.updated_at,
						   transfer_conditions.completed_at,
						   transfer_conditions.disallow_transfer_in,
						   transfer_conditions.disallow_transfer_out,
						   transfer_conditions.status,
						   transfer_conditions.detail_status,
						   transaction_notes.note as notes')
            ->from($this->tableName)
            ->join('(SELECT note, transaction_id FROM transaction_notes WHERE id in (SELECT MAX(id) FROM transaction_notes GROUP BY transaction_id)) transaction_notes', 'transaction_notes.transaction_id = transfer_conditions.id', 'left')
            ->join('promorules', 'transfer_conditions.promotion_id = promorules.promorulesId', 'left')
            ->where_in('transfer_conditions.status', [self::STATUS_CANCEL,self::STATUS_COMPLETE])
            ->where('transfer_conditions.player_id', $playerId)
            ->where($where['0'], $values['0'])
            ->where($where['1'], $values['1'])
            ->order_by('transfer_conditions.started_at', 'desc');

        $query = $this->db->get();
        $this->utils->printLastSQL();

        if ($query->num_rows() > 0) {

            $gameDescIdArrFromLevel=$this->group_level->getAllowedGameIdArr($playerId);

            foreach ($query->result_array() as $row) {
                $gameDescIdArr=$gameDescIdArrFromLevel;

                //check if deposit promo and non deposit promo (email,registration,mobile)
                if ($row['promoType'] == Promorules::PROMO_TYPE_NON_DEPOSIT ||
                    $row['promoType'] == Promorules::PROMO_TYPE_DEPOSIT) {
                    if ($row['promorulesId']) {
                        $this->load->model(array('promorules'));
                        $gameDescIdArr = $this->promorules->getPlayerGames($row['promorulesId']);
                        $this->utils->debug_log('get transfer condition gameDescIdArr from promo rules:'.$row['promorulesId']);
                    }
                }

                $row['currentBet'] = $this->game_logs->totalPlayerBettingAmountWithLimitByVIP($row['player_id'], $row['started_at'], null, $gameDescIdArr);
                $this->getDisallowTransferWalletName($playerId, $row);
                $data[] = $row;
            }
            return $data;
        }

        return null;
    }

    public function getAdminUserId(){
        $this->load->library('authentication');
        $admin_user_id = null;
        if (method_exists($this->authentication, 'getUserId')) {
            $admin_user_id = $this->authentication->getUserId();
        }
        if (empty($admin_user_id)) {
            //get super admin
            $admin_user_id = $this->users->getSuperAdminId();
        }

        return $admin_user_id;
    }

    public function createTransferCondition($promorule, $playerId, $playerPromoId, $transferBetAmtCondition){
        $promorulesId = $promorule['promorulesId'];
        if (empty($transferBetAmtCondition)) {
            $transferBetAmtCondition = 0;
        }

        $json_info = $promorule['json_info'];
        $promorule_arr = json_decode($json_info, true);
        $transferRequirementWalletsInfo = $promorule_arr['transferRequirementWalletsInfo'];
        $wallet_info = json_decode($transferRequirementWalletsInfo, true);

        $disallow_transfer_in = json_encode($wallet_info['disallow_transfer_in_wallets']);
        $disallow_transfer_out = json_encode($wallet_info['disallow_transfer_out_wallets']);

        $subWalletId = null;
        if (isset($promorule['releaseToSubWallet'])) {
            $subWalletId = $promorule['releaseToSubWallet'];
        }

        $cond = $this->getTransferConditionByPlayerPromoId($playerPromoId);
        if (empty($cond)) {

            $data = array(
                'promotion_id' => $promorulesId,
                'player_id' => $playerId,
                'player_promo_id' => $playerPromoId,
                'disallow_transfer_in' => $disallow_transfer_in,
                'disallow_transfer_out' => $disallow_transfer_out,
                'bet_details' => '',
                'wallet_json' => $transferRequirementWalletsInfo,
                'condition_amount' => $transferBetAmtCondition,
                'status' => self::STATUS_ACTIVE,
                'started_at' => $this->utils->getNowForMysql(),
                'updated_at' => $this->utils->getNowForMysql(),
                'admin_user_id' => $this->getAdminUserId(),
                'wallet_type' => $subWalletId
            );
            $rlt = $this->insertRow($data);
        } else {
            $data = array(
                'promotion_id' => $promorulesId,
                'player_id' => $playerId,
                'player_promo_id' => $playerPromoId,
                'disallow_transfer_in' => $disallow_transfer_in,
                'disallow_transfer_out' => $disallow_transfer_out,
                'bet_details' => '',
                'wallet_json' => $transferRequirementWalletsInfo,
                'condition_amount' => $transferBetAmtCondition,
                'status' => self::STATUS_ACTIVE,
                'updated_at' => $this->utils->getNowForMysql(),
                'admin_user_id' => $admin_user_id,
                'wallet_type' => $subWalletId
            );

            $this->db->where('id', $cond['id'])
                 ->update($this->tableName, $data);
            $rlt = $cond['id'];
        }

        return $rlt;
    }

    /**
     * overview : get transfer condition
     *
     * @param  int	$playerpromoId
     * @return array
     */
    public function getTransferConditionByPlayerPromoId($playerpromoId) {
        $this->db->from($this->tableName)
                 ->where('player_promo_id', $playerpromoId)
                 ->limit(1);
        return $this->runOneRowArray();
    }

    /**
     * overview : cancel transfer condition
     *
     * @param  array	$ids
     * @return bool
     */
    public function cancelTransferCondition($ids, $cancelManualStatus=self::DETAIL_STATUS_MANUAL_CANCELED) {
        $data['status'] = self::STATUS_CANCEL;
        $data['updated_at'] = $this->utils->getNowForMysql();
        $data['completed_at'] = $this->utils->getNowForMysql();
        $data['admin_user_id'] = $this->getAdminUserId();;
        $data['detail_status'] = $cancelManualStatus;

        $this->db->where_in('id', $ids);
        $this->db->set($data);
        $success = $this->runAnyUpdate($this->tableName);

        $this->utils->printLastSQL();

        if ($success) {
            $this->load->model(['player_promo']);

            $this->db->select('player_id, player_promo_id')
                     ->from($this->tableName)
                     ->where_in('id', $ids)
                     ->where('player_promo_id is not null', null, false);

            $rows = $this->runMultipleRowArray();

            $this->utils->printLastSQL();
            $this->utils->debug_log('rows', count($rows));
            if (!empty($rows)) {
                $playerPromoArr = [];
                foreach ($rows as $row) {
                    $playerPromoArr[] = $row['player_promo_id'];
                }
                if (!empty($playerPromoArr)) {
                    $this->player_promo->finishPlayerPromos($playerPromoArr, 'auto finished by cancel transfer condition');
                }
            }

        }

        return $success;
    }

    public function getDisallowTransferWalletName($playerId, &$row){
        $this->load->model(['game_provider_auth']);

        $disallow_transfer_in = json_decode($row['disallow_transfer_in'], true);
        if(!is_array($disallow_transfer_in['wallet_id'])){
            $disallow_transfer_in['wallet_id'] = [];
        }

        $disallow_transfer_out = json_decode($row['disallow_transfer_out'], true);
        if(!is_array($disallow_transfer_out['wallet_id'])){
            $disallow_transfer_out['wallet_id'] = [];
        }

        $game_data = $this->game_provider_auth->getGamePlatforms($playerId);
        foreach($game_data as $game_platform) {
            if(in_array($game_platform['id'], $disallow_transfer_in['wallet_id'])){
                $row['disallow_transfer_in_wallets_name'][] = $game_platform['system_code'];
            }

            if(in_array($game_platform['id'], $disallow_transfer_out['wallet_id'])){
                $row['disallow_transfer_out_wallets_name'][] = $game_platform['system_code'];
            }
        }

        if(isset($row['disallow_transfer_in_wallets_name']) && (count($row['disallow_transfer_in_wallets_name']) > 0) ){
            $row['disallow_transfer_in_wallets_name'] = json_encode($row['disallow_transfer_in_wallets_name']);
        }

        if(isset($row['disallow_transfer_out_wallets_name']) && (count($row['disallow_transfer_out_wallets_name']) > 0) ){
            $row['disallow_transfer_out_wallets_name'] = json_encode($row['disallow_transfer_out_wallets_name']);
        }

        return $row;
    }

    public function isPlayerTransferConditionExist($playerId, $transfer_from, $transfer_to, $withBetting=true){
        $result = FALSE;
        // calc without betting
        $transferConditions = $this->getPlayerTransferCondition($playerId, $withBetting);

        if(empty($transferConditions)) {
            return $result;
        }

        foreach($transferConditions as $transferCondition){
            if($transferCondition['status'] != self::STATUS_ACTIVE){
                continue;
            }

            $disallow_transfer_in = json_decode($transferCondition['disallow_transfer_in'], true);
            $disallow_transfer_out = json_decode($transferCondition['disallow_transfer_out'], true);

            if(isset($disallow_transfer_in['wallet_id']) && in_array($transfer_to, $disallow_transfer_in['wallet_id'])){
                $result['transfer_in_failed'] = 'Do not allow to transfer to platform_id '. $transfer_to .', because of transfer condition id '. $transferCondition['id'];
            }

            if(isset($disallow_transfer_out['wallet_id']) && in_array($transfer_from, $disallow_transfer_out['wallet_id'])){
                $result['transfer_out_failed'] = 'Do not allow to transfer form platform_id '. $transfer_from .', because of transfer condition id '. $transferCondition['id'];
            }

            // OGP-17203
            if (isset($result['transfer_in_failed']) || isset($result['transfer_out_failed'])) {
                $result['tx_cond'] = $transferCondition;
            }

            $this->utils->debug_log("Player Transfer Conditions", $result);
        }

        return $result;
    }

    public function getAllAvtiveTransferConditions(){
        $this->db->select('*')
                 ->from($this->tableName)
                 ->where('transfer_conditions.status', self::STATUS_ACTIVE);

        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            foreach ($query->result_array() as $row) {
                $data[] = $row;
            }
            return $data;
        }

        return FALSE;
    }

    public function updateTransferCondition($data, $id){
        $this->db->set($data);
        $this->db->where("id", $id);
        return $this->runAnyUpdate($this->tableName);
    }

    public function updateStatus($ids, $status) {
        $this->db->set('status', $status)->where_in('id', $ids);
        return $this->runAnyUpdate($this->tableName);
    }

    public function syncPlayerTotalBets(){
        $this->utils->debug_log("<=============================== TRANSFER_CONDITION: SYNC PLAYER TOTAL BETS ===============================>");
        $result = FALSE;

        $acvtiveTransferConditions = $this->getAllAvtiveTransferConditions();
        if(empty($acvtiveTransferConditions)) {
            $this->utils->debug_log("epmty transfercondition",$acvtiveTransferConditions);
            return $result;
        }

        $this->load->model(['total_player_game_minute', 'promorules']);
        $completed_at = $updated_at = $date_to = $this->utils->getNowForMysql();

        foreach($acvtiveTransferConditions as $transferCondition){
            $update_data = [];
            $total_bet_amount = 0;
            $id = $transferCondition['id'];

            ### GET TOTAL BETS GROUP BY GAME PLATFORM ID ####
            $totalBetsWinsLossGroupByGamePlatformByPlayers = $this->total_player_game_minute->getTotalBetsWinsLossGroupByGamePlatformByPlayers($transferCondition['player_id'], $transferCondition['started_at'], $date_to);
            if(empty($totalBetsWinsLossGroupByGamePlatformByPlayers)){
                $this->utils->debug_log("Get Total Bets Wins Loss Group By Game Platform By Players Result is empty with Tranfer Condition Id ", $transferCondition['id']);
                continue;
            }

            ### GET TOTAL BET WITH GAME DESCRIPTION ID
            $gameDescIdArr = $this->promorules->getPlayerGames($transferCondition['promotion_id']);
            $this->utils->debug_log('get transfer condition gameDescIdArr from promo rules:'.$transferCondition['promotion_id']);
            $total_bet_amount = $this->game_logs->totalPlayerBettingAmountWithLimitByVIP($transferCondition['player_id'], $transferCondition['started_at'], null, $gameDescIdArr);

            // set update bet_detail and condition amount
            $update_data['bet_details'] = json_encode($totalBetsWinsLossGroupByGamePlatformByPlayers);
            $update_data['updated_at'] = $updated_at;

            if($total_bet_amount >= $transferCondition['condition_amount']){
                //set update status
                $update_data['status'] = self::STATUS_COMPLETE;
                $update_data['completed_at'] = $completed_at;
                $update_data['detail_status'] = self::DETAIL_STATUS_FINISHED_BET_REQUIREMENT;
                $this->utils->debug_log('condition had met , total bet amount > condition amount', $total_bet_amount . ' > '. $transferCondition['condition_amount']);
            }

            $result = $this->updateTransferCondition($update_data, $id);
            if(!$result){
                $this->utils->debug_log("====== update tansfer condition failed , id ", $id);
            }
        }

        return $result;
    }

    function getTransferConditionByPromoCmsSettingId($promocmsId){
        $this->db->select('transfer_conditions.*')
            ->from($this->tableName)
            ->join('playerpromo','transfer_conditions.player_promo_id = playerpromo.playerpromoId', 'left')
            ->where('playerpromo.promoCmsSettingId', $promocmsId)
            ->where('transfer_conditions.status', self::STATUS_ACTIVE)
            ->where('transfer_conditions.detail_status != ', self::DETAIL_STATUS_CANCELED_BY_DELETING_PROMO_MANAGER);

        return $this->runMultipleRowArray();
    }

    function updateTransferConditionByPromoCmsSettingId($transfer_conditions){
        $success = FALSE;
        $playerPromoArr = [];
        if(!empty($transfer_conditions)){
            foreach($transfer_conditions as $tc){
                $data['detail_status'] = self::DETAIL_STATUS_CANCELED_BY_DELETING_PROMO_MANAGER;   //unfinished tc
                $data['status'] = self::STATUS_CANCEL;
                $data['updated_at'] = $this->utils->getNowForMysql();
                $data['completed_at'] = $this->utils->getNowForMysql();
                $data['admin_user_id'] = $this->getAdminUserId();

                $success = $this->updateRow($tc['id'], $data);
                $this->utils->debug_log('-----------------------> updateTransferConditionByPromoCmsSettingId transfer condition id ', $tc['id']);
                if($success){
                    $playerPromoArr[] = $tc['player_promo_id'];
                }
            }

            if(!empty($playerPromoArr)){
                $this->load->model(['player_promo']);
                $this->player_promo->finishPlayerPromos($playerPromoArr, 'Canceled transfer condition by deleting promo manager');
            }
        }

        return $success;
    }

    /**
     * overview : get available amount on transfer condition
     *
     * @param int	$playerId
     * @return double
     */
    public function getAvailableAmountOnTransferCondition($playerId) {
        $this->db->select('sum(condition_amount) as amount', false)
            ->from($this->tableName)->where('player_id', $playerId)
            ->where('status', self::STATUS_ACTIVE);

        return $this->runOneRowOneField('amount');
    }

    public function getAvailableTransferConditionIds($playerId){
        $this->db->select('id')
            ->from($this->tableName)
            ->where('player_id', $playerId)
            ->where('status', self::STATUS_ACTIVE);
        return $this->runMultipleRowOneFieldArray('id');
    }

    public function getAvailableTransferConditionIdsByWalletType($playerId, $walletType){
        $this->db->select('id')
            ->from($this->tableName)
            ->where('player_id', $playerId)
            ->where('wallet_type', $walletType)
            ->where('status', self::STATUS_ACTIVE);
        return $this->runMultipleRowOneFieldArray('id');
    }

    /**
     * overview : disable player transfer condition
     *
     * @param int	$playerId
     * @return bool
     */
    function disablePlayerTransferCondition($playerId, $reason=null, $detailStatus=self::DETAIL_STATUS_ACTIVE, $transferConditionIds=null) {
        if(empty($transferConditionIds)){
            //empty transfer condition
            return TRUE;
        }
        $data['status'] = self::STATUS_CANCEL;
        $data['updated_at'] = $this->utils->getNowForMysql();
        $data['completed_at'] = $this->utils->getNowForMysql();
        $data['admin_user_id'] = $this->getAdminUserId();
        $data['detail_status'] = $detailStatus;
        $data['note'] = $reason;

        $this->db->where('player_id', $playerId);
        $this->db->where('status', self::STATUS_ACTIVE);   # only clear active, to avoid update all
        $this->db->where_in('id', $transferConditionIds);
        $success =  $this->runUpdate($data);

        if ($success) {
            $this->load->model(['player_promo']);

            $this->db->select('player_id, player_promo_id')->from($this->tableName)
                ->where_in('id', $transferConditionIds)
                ->where('player_promo_id is not null', null, false);

            $rows = $this->runMultipleRowArray();

            if (!empty($rows)) {
                $playerPromoArr = [];
                foreach ($rows as $row) {
                    $playerPromoArr[] = $row['player_promo_id'];
                }
                if (!empty($playerPromoArr)) {
                    $this->player_promo->finishPlayerPromos($playerPromoArr, $reason);
                }
            }
        }

        return $success;
    }

    /**
     * overview : disable player transfer condition
     *
     * @param $playerId
     * @param $walletType
     * @return bool
     */
    function disablePlayerTransferConditionByWalletType($playerId, $walletType, $reason=null, $detailStatus=self::DETAIL_STATUS_ACTIVE, $transferConditionIds=null) {
        if(empty($transferConditionIds)){
            //empty transfer condition
            return TRUE;
        }
        $data['status'] = self::STATUS_CANCEL;
        $data['updated_at'] = $this->utils->getNowForMysql();
        $data['completed_at'] = $this->utils->getNowForMysql();
        $data['admin_user_id'] = $this->getAdminUserId();
        $data['detail_status'] = $detailStatus;
        $data['note'] = $reason;

        $this->db->where('player_id', $playerId)->where('wallet_type', $walletType);

        # only clear active, to avoid reupdate all. should not affect cancelled/finished tc
        $this->db->where('status', self::STATUS_ACTIVE);

        $this->db->where_in('id', $transferConditionIds);
        $this->db->set($data);
        // $this->db->update($this->tableName, $data);
        $success =  $this->runAnyUpdate($this->tableName);

        if ($success) {
            $this->load->model(['player_promo']);

            $this->db->select('player_id, player_promo_id')->from($this->tableName)
                ->where_in('id', $transferConditionIds)
                ->where('player_promo_id is not null', null, false);

            $rows = $this->runMultipleRowArray();

            if (!empty($rows)) {
                $playerPromoArr = [];
                foreach ($rows as $row) {
                    $playerPromoArr[] = $row['player_promo_id'];
                }
                if (!empty($playerPromoArr)) {
                    $this->player_promo->finishPlayerPromos($playerPromoArr, $reason);
                }
            }
        }

        return $success;
    }

    /**
     * overview : get available amount on transfer condition
     *
     * @param $playerId
     * @param $walletType
     * @return null
     */
    public function getAvailableAmountOnTransferConditionByWalletType($playerId, $walletType) {
        $this->db->select('sum(condition_amount) as amount', false)
            ->from($this->tableName)->where('player_id', $playerId)
            ->where('wallet_type', $walletType)
            ->where('status', self::STATUS_ACTIVE);

        return $this->runOneRowOneField('amount');
    }

    /**
     * overview : get first available transfer condition
     *
     * @param $playerId
     * @return null
     */
    public function getFirstAvailableTransferCondition($playerId) {
        $this->db->select('min(started_at) as firstDateTime', false)
            ->from($this->tableName)->where('player_id', $playerId)
            ->where('status', self::STATUS_ACTIVE);

        return $this->runOneRowOneField('firstDateTime');
    }

    /**
     * overview : get first available transfer condition
     *
     * @param int	$playerId
     * @param int	$walletType
     * @return string
     */
    public function getFirstAvailableTransferConditionByWalletType($playerId, $walletType) {
        $this->db->select('min(started_at) as firstDateTime', false)
            ->from($this->tableName)->where('player_id', $playerId)
            ->where('wallet_type', $walletType)
            ->where('status', self::STATUS_ACTIVE);

        return $this->runOneRowOneField('firstDateTime');
    }

    /**
     * overview : check if available transfer condition exist
     *
     * @param $playerId
     * @param $walletType
     * @return bool
     */
    public function existsAvailTransferlConditionByWalletType($playerId, $walletType) {
        $this->db->select('id')
            ->from($this->tableName)->where('player_id', $playerId)
            ->where('wallet_type', $walletType)
            ->where('status', self::STATUS_ACTIVE);

        return $this->runExistsResult();

    }

    /**
     * overview : auto check transfer condition
     *
     * @param int	$playerId
     * @param string $message
     * @return bool true=all finished
     */
    public function autoCheckTransferConditionAndMoveBigWallet($playerId, &$message = null, $clean_condition = false, $secureId = null) {
        //first check subwallets
        $this->load->model(['wallet_model', 'game_logs', 'withdraw_condition']);
        $settings = $this->withdraw_condition->getClearWithdrawConditionSettings();
        $message = '';

        $success = true;
        $changed = false;

        $bigWallet = $this->wallet_model->getBigWalletByPlayerId($playerId);
        $subwallets = $settings['subwallets'];

        foreach ($subwallets as $sub) {

            $detailStatus = self::DETAIL_STATUS_ACTIVE;
            $clear = false;

            $walletType = $sub['id']; // sub wallet

            if (!$this->existsAvailTransferlConditionByWalletType($playerId, $walletType)) {
                $message .= 'does not exist transfer condition on ' . $sub['label'] . "\n";
                continue;
            }

            $this->utils->debug_log('process wallet: ' . $walletType . ' , playerId: ' . $playerId);

            $conditionAmount = 0;
            $firstDateTime = null;
            $balLimit = doubleval($sub['value']);
            $bal = $bigWallet['sub'][$walletType]['total'];
            $transferConditionIds = $this->getAvailableTransferConditionIdsByWalletType($playerId, $walletType);
            if ($bal <= $balLimit) {
                //FIXME
                //inactive all
                $clear = true;
                $message .= 'finish transfer condition on ' . $sub['label'] . ', because last balance (' . $bal . ') of player ' . $playerId . ' <= ' . $balLimit . "\n";
                $detailStatus = self::DETAIL_STATUS_CANCELLED_DUE_TO_LOW_BALANCE;
            } else {
                //check bet amount >= transfer condition
                //get first date
                $firstDateTime = $this->getFirstAvailableTransferConditionByWalletType($playerId, $walletType);

                if (!empty($firstDateTime)) {
                    //add promorulesId
                    $betAmount = $this->game_logs->getPlayerCurrentBetByGamePlatformId($playerId, $walletType, $firstDateTime);
                    $conditionAmount = $this->getAvailableAmountOnTransferConditionByWalletType($playerId, $walletType);
                    $this->utils->debug_log('check transfer condition', 'playerId', $playerId, 'walletType', $walletType,
                        'firstDateTime', $firstDateTime, 'betAmount', $betAmount, 'conditionAmount', $conditionAmount);
                    $clear = $betAmount >= $conditionAmount;
                    if ($clear) {
                        $message .= 'finish transfer condition on ' . $sub['label'] . ', because total bet amount (' . $betAmount . ' from ' . $firstDateTime . ') of player ' . $playerId . ' >= ' . $conditionAmount . "\n";
                        $detailStatus = self::DETAIL_STATUS_CANCELED_DUE_TO_NEW_DEPOSIT;
                    }
                } else {
                    $this->utils->debug_log('cannot find first date time of transfer condition', $playerId, 'wallet', $walletType);
                }
            }

            if ($clear) {
                if ($clean_condition) {
                    $reason = 'auto finished transfer condition by deposit to subwallet ' . $walletType;
                    if(isset($secureId) && !empty($secureId)){
                        $reason .= ',Order ID : ' . $secureId;
                    }

                    $success = $success && $this->disablePlayerTransferConditionByWalletType($playerId, $walletType, $reason, $detailStatus, $transferConditionIds);
                    $this->utils->debug_log('disablePlayerTransferConditionByWalletType for subwallet', $success, 'playerId', $playerId, 'walletType', $walletType, 'transferConditionIds', $transferConditionIds);
                    if (!$success) {
                        return $success;
                    }
                    if (empty($transferConditionIds)){
                        $message .= 'transferCondition is empty';
                        $this->utils->debug_log('transferConditionIds is empty', $transferConditionIds);
                    }
                    //FIXME change status of player promo

                }
                $success = $this->wallet_model->moveSubWalletToReal($bigWallet, $walletType);
                if (!$success) {
                    $message .= "move subwallet failed\n";
                    return $success;
                }

                $changed = true;
            } else {
                $message .= 'still keep transfer condition on ' . $sub['label'] . ', from ' . $firstDateTime . ', amount: ' . $conditionAmount . "\n";
            }
        } // EOF foreach ($subwallets as $sub)
        if (!$success) {
            return $success;
        }

        //main
        $balLimit = $settings['total'];
        $walletType = 0; // main wallet

        $bal = $this->wallet_model->getTotalBalance($playerId);
        $this->utils->debug_log('playerId', $playerId, 'balLimit', $balLimit, 'bal', $bal);
        $clear = false;
        $description = null;
        $conditionAmount = 0;
        $firstDateTime = null;
        $detailStatus = self::DETAIL_STATUS_ACTIVE;
        $transferConditionIds = $this->getAvailableTransferConditionIds($playerId);
        if ($bal <= $balLimit) {
            //inactive all
            $clear = true;
            $message .= 'finish transfer condition, because last balance (' . $bal . ') of player ' . $playerId . ' <= ' . $balLimit . "\n";
            $detailStatus =  self::DETAIL_STATUS_CANCELLED_DUE_TO_LOW_BALANCE;
        } else {
            //check bet amount >= transfer condition
            //get first date
            $firstDateTime = $this->getFirstAvailableTransferCondition($playerId);
            if (!empty($firstDateTime)) {

                $betAmount = $this->game_logs->getPlayerCurrentBet($playerId, $firstDateTime);
                $conditionAmount = $this->getAvailableAmountOnTransferCondition($playerId);
                $this->utils->debug_log('check transfer condition', 'playerId', $playerId, 'main wallet',
                    'firstDateTime', $firstDateTime, 'betAmount', $betAmount, 'conditionAmount', $conditionAmount);
                $clear = $betAmount >= $conditionAmount;
                if ($clear) {
                    $message .= 'clean transfer condition, because total bet amount (' . $betAmount . ' from ' . $firstDateTime . ') of player ' . $playerId . ' >= ' . $conditionAmount . "\n";
                    $detailStatus = self::DETAIL_STATUS_CANCELED_DUE_TO_NEW_DEPOSIT;
                }
            } else {
                $this->utils->debug_log('cannot find first date time of transfer condition', $playerId, 'main wallet');
            }
        }
        if ($clear) {
            if ($clean_condition) {
                $reason = 'auto finished transfer condition by deposit to main wallet';
                if(isset($secureId) && !empty($secureId)){
                    $reason .= ',Order ID : ' . $secureId;
                }

                $success = $success && $this->disablePlayerTransferCondition($playerId, $reason, $detailStatus, $transferConditionIds);
                $this->utils->debug_log('disablePlayerTransferCondition for main wallet', $success, 'playerId', $playerId, 'transferConditionIds', $transferConditionIds);
                if (!$success) {
                    return $success;
                }
                if (empty($transferConditionIds)){
                    $message .= 'transferCondition is empty';
                    $this->utils->debug_log('transferConditionIds is empty', $transferConditionIds);
                }
                //change status of player promo
            }

            $success = $this->wallet_model->moveMainWalletToReal($bigWallet);
            if (!$success) {
                $message .= "move main wallet failed\n";
                return $success;
            }
            $changed = true;
        } elseif ($conditionAmount > 0) {
            $message .= 'still keep transfer condition on main wallet, from ' . $firstDateTime . ', amount: ' . $conditionAmount . "\n";
        }

        if ($changed && $success) {

            $this->wallet_model->totalBigWallet($bigWallet);

            $success = $this->wallet_model->updateBigWalletByPlayerId($playerId, $bigWallet);
            if (!$success) {
                $message .= 'update big wallet failed' . "\n";
            }
        }

        return $success;
    } // EOF autoCheckTransferConditionAndMoveBigWallet

}

/////end of file///////
