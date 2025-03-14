<?php

/**
 *
 * seamless single wallet is only for seamless wallet
 *
 * @see Wallet_model
 *
 */
trait seamless_single_wallet_module {

    /**
     * insert or ignore
     * @param  int $playerId
     * @param  string $username
     * @param  object $db
     * @return boolean
     */
    public function safeInitSeamlessSingleWallet($playerId, $username, $db=null){
        if(empty($playerId) || empty($username)){
            return false;
        }
        if(empty($db)){
            $db=$this->db;
        }
        if(!$this->isResourceInsideLock($playerId, Utils::LOCK_ACTION_BALANCE, false)){
            $this->utils->error_log('not found lock', $playerId, Utils::LOCK_ACTION_BALANCE);
            $reasonId=Abstract_game_api::REASON_USERS_WALLET_LOCKED;
            return false;
        }
        $success=true;
        $this->utils->debug_log('start safeInitSeamlessSingleWallet');
        $this->load->model(['player_model']);
        $agentId=null;
        if($this->utils->getConfig('write_agent_id_to_seamless_single_wallet')){
            $agentId=$this->player_model->getAgentIdFromPlayerId($playerId);
        }
        //internal_unique_key is username of player table, external_unique_key is username too
        //ignore game_platform_id
        $internalUniqueKey=$username;
        $externalUniqueKey=$username;
        $data=[
            'player_id'=>$playerId,
            // 'game_platform_id'=>$gamePlatformId,
            'internal_unique_key'=>$internalUniqueKey,
            'external_unique_key'=>$externalUniqueKey,
            'created_at'=>$this->utils->getNowForMysql(),
            'updated_at'=>$this->utils->getNowForMysql(),
            'agent_id'=>$agentId,
        ];
        // $db->select('id')->from('seamless_single_wallet')->where('internal_unique_key', $internalUniqueKey);
        // $id=$this->runOneRowOneField('id', $db);
        // if(empty($id)){
            $initBalance=0;
            $data['balance']=$initBalance;
            //insert new
            $id=$this->insertOrIgnoreRow($data, 'seamless_single_wallet', $db);
            $this->utils->debug_log('id of insert ignore', $id);
            $this->utils->printLastSQL();
        // }else{
            //ignore update
        // }
        return $success;
    }

    // public function syncSeamlessSingleWallet($playerId, $username, $db=null){
    //     if(empty($playerId) || empty($username)){
    //         return false;
    //     }
    //     if(empty($db)){
    //         $db=$this->db;
    //     }
    //     $this->utils->debug_log('start sync seamless wallet');
    //     //internal_unique_key is username of player table, external_unique_key is username too
    //     //ignore game_platform_id
    //     $internalUniqueKey=$username;
    //     $externalUniqueKey=$username;
    //     $data=[
    //         'player_id'=>$playerId,
    //         // 'game_platform_id'=>$gamePlatformId,
    //         'internal_unique_key'=>$internalUniqueKey,
    //         'external_unique_key'=>$externalUniqueKey,
    //         'created_at'=>$this->utils->getNowForMysql(),
    //         'updated_at'=>$this->utils->getNowForMysql(),
    //     ];
    //     $db->select('id')->from('seamless_single_wallet')->where('internal_unique_key', $internalUniqueKey);
    //     $id=$this->runOneRowOneField('id', $db);
    //     if(empty($id)){
    //         $initBalance=0;
    //         $data['balance']=$initBalance;
    //         //insert new
    //         $id=$this->insertData('seamless_single_wallet', $data, $db);
    //     }else{
    //         //update by id
    //         $db->set($data)->where('id', $id);
    //         $this->runAnyUpdate('seamless_single_wallet', $db);
    //     }
    //     return $id;
    // }

    /**
     * transferSeamlessSingleWallet
     * @param  int $playerId
     * @param  string $type
     * @param  double $amount
     * @param  int &$reasonId
     * @return boolean $success
     */
    public function transferSeamlessSingleWallet($playerId, $type, $amount, &$reasonId){
        if(empty($playerId)){
            $reasonId=Abstract_game_api::REASON_NOT_FOUND_PLAYER;
            return false;
        }
        if(!$this->isResourceInsideLock($playerId, Utils::LOCK_ACTION_BALANCE, false)){
            $this->utils->error_log('not found lock', $playerId, Utils::LOCK_ACTION_BALANCE);
            $reasonId=Abstract_game_api::REASON_USERS_WALLET_LOCKED;
            return false;
        }
        $success=false;
        if($amount>0){
            if($type==Wallet_model::TRANSFER_TYPE_IN){
                $updBal='balance+'.$amount;
            }else{
                $balance=null;
                $succ=$this->querySeamlessSingleWallet($playerId, $balance, $reasonId);
                //check enough balance
                if($succ){
                    if($this->utils->compareResultFloat($amount, '>', $balance)){
                        $this->utils->debug_log('no enough balance', $playerId, $type, $amount, $balance);
                        //no enough balance
                        $reasonId=Abstract_game_api::REASON_NO_ENOUGH_BALANCE;
                        return false;
                    }
                }else{
                    //reasonid in querySeamlessSingleWallet
                    return false;
                }
                $updBal='balance-'.$amount;
            }
            $this->db->set('balance', $updBal, false);
            $this->db->where('player_id', $playerId);
            $cnt=$this->runAnyUpdateWithResult('seamless_single_wallet');
            $this->utils->printLastSQL();
            $success=$cnt>0;
            if($cnt<=0){
                $this->utils->error_log('update nothing, init and retry', $playerId, $amount, $cnt);
                //try sync and do it again
                $username=$this->player_model->getUsernameById($playerId);
                $succ=$this->safeInitSeamlessSingleWallet($playerId, $username);
                if($succ){
                    $this->db->set('balance', $updBal, false);
                    $this->db->where('player_id', $playerId);
                    $cnt=$this->runAnyUpdateWithResult('seamless_single_wallet');
                    $success=$cnt>0;
                    if($success){
                        $this->utils->debug_log('retry is success');
                    }else{
                        $this->utils->error_log('retry is failed', $playerId, $amount, $cnt);
                        $reasonId=Abstract_game_api::REASON_BALANCE_NOT_SYNC;
                    }
                }
            }else{
                $this->utils->debug_log('transferSeamlessSingleWallet success', $playerId, $amount, $cnt);
            }
        }else{
            //wrong amount
            $this->utils->error_log('invalid amount on transferSeamlessSingleWallet', $playerId, $amount);
            $reasonId=Abstract_game_api::REASON_INVALID_TRANSFER_AMOUNT;
        }
        return $success;
    }

    public function querySeamlessSingleWallet($playerId, &$balance, &$reasonId){
        $success=false;
        if(empty($playerId)){
            $reasonId=Abstract_game_api::REASON_NOT_FOUND_PLAYER;
            return false;
        }
        if(!$this->isResourceInsideLock($playerId, Utils::LOCK_ACTION_BALANCE, false)){
            $this->utils->error_log('not found lock', $playerId, Utils::LOCK_ACTION_BALANCE);
            $reasonId=Abstract_game_api::REASON_USERS_WALLET_LOCKED;
            return false;
        }

        $this->db->select('balance')->from('seamless_single_wallet')->where('player_id', $playerId);
        $balance=$this->runOneRowOneField('balance');
        if($balance===null){
            $this->utils->error_log('cannot find balance from seamless_single_wallet');
            $username=$this->player_model->getUsernameById($playerId);
            //means missing balance
            $succ=$this->safeInitSeamlessSingleWallet($playerId, $username);
            if($succ){
                $balance=0;
                $success=true;
            }else{
                $this->utils->error_log('init seamless single wallet failed');
                $reasonId=Abstract_game_api::REASON_BALANCE_NOT_SYNC;
            }
        }else{
            //get right balance
            $success=true;
            $this->utils->debug_log('get right balance', $balance, $playerId);
            $this->utils->printLastSQL();
        }

        return $success;
    }

    public function withdrawAllSeamlessSingleWallet($playerId, &$amount, &$reasonId){
        if(empty($playerId)){
            $reasonId=Abstract_game_api::REASON_NOT_FOUND_PLAYER;
            return false;
        }
        $success=false;
        //get balance
        $succ=$this->querySeamlessSingleWallet($playerId, $balance, $reasonId);
        //check enough balance
        if($succ){
            if($balance>0){
                //only do it >0
                //try withdraw all
                $this->db->set('balance', 0)->where('player_id', $playerId);
                $cnt=$this->runAnyUpdateWithResult('seamless_single_wallet');
                $success=$cnt>0;
                if($cnt<=0){
                    $reasonId=Abstract_game_api::REASON_BALANCE_NOT_SYNC;
                    //nothing update
                    $this->utils->error_log('withdraw all failed', $playerId);
                }else{
                    $amount=$balance;
                }
            }else{
                $amount=0;
                $this->utils->debug_log('do nothing because balance is <= 0');
                $success=true;
            }
        }else{
            //reasonid in querySeamlessSingleWallet
            return false;
        }

        return $success;
    }

    public function blockSeamlessSingleWallet($playerId){
        if(empty($playerId)){
            $this->utils->error_log('blockSeamlessSingleWallet failed, player id is empty');
            return false;
        }
        $success=false;
        $this->db->set('is_blocked', self::DB_TRUE)->where('player_id', $playerId);
        $cnt=$this->runAnyUpdateWithResult('seamless_single_wallet');
        if($cnt<=0){
            $username=$this->player_model->getUsernameById($playerId);
            //try sync
            $success=$this->safeInitSeamlessSingleWallet($playerId, $username);
            if($success){
                $this->db->set('is_blocked', self::DB_TRUE)->where('player_id', $playerId);
                $success=$this->runAnyUpdate('seamless_single_wallet');
            }
        }else{
            $success=true;
        }
        return $success;
    }

    public function unblockSeamlessSingleWallet($playerId){
        if(empty($playerId)){
            $this->utils->error_log('blockSeamlessSingleWallet failed, player id is empty');
            return false;
        }
        $success=false;
        $this->db->set('is_blocked', self::DB_FALSE)->where('player_id', $playerId);
        $cnt=$this->runAnyUpdateWithResult('seamless_single_wallet');
        if($cnt<=0){
            $username=$this->player_model->getUsernameById($playerId);
            //try sync
            $success=$this->safeInitSeamlessSingleWallet($playerId, $username);
        }else{
            $success=true;
        }
        return $success;
    }

    /**
     * isBlockedOnSeamlessSingleWallet
     * @param  int $playerId
     * @return boolean blocked or not
     */
    public function isBlockedOnSeamlessSingleWallet($playerId){
        if(empty($playerId)){
            $this->utils->error_log('isBlockedOnSeamlessSingleWallet failed, player id is empty');
            return false;
        }
        $this->db->select('is_blocked')->from('seamless_single_wallet')->where('player_id', $playerId);
        return $this->runOneRowOneField('is_blocked')==self::DB_TRUE;
    }

    public function seamlessAllToMainWallet($playerId){
        //withdraw all
        $amount=0;
        $success=$this->withdrawAllSeamlessSingleWallet($playerId, $amount, $reasonId);

        if($success){
            if($amount>0){
                $success=$this->incMainManuallyOnBigWallet($playerId, $amount);
            }else{
                //ignore
            }
        }else{
            $this->utils->error_log('withdrawal seamless failed');
        }
        return $success;
    }

    public function mainWalletAllToSeamless($playerId){
        $success=false;
        //query main
        $mainWalletAmount=$this->getMainWalletTotalNofrozenOnBigWalletByPlayer($playerId);
        if($mainWalletAmount>0){
            //withdraw from main
            $success=$this->decMainManuallyOnBigWallet($playerId, $mainWalletAmount);
            if($success){
                //save to seamless
                $success=$this->transferSeamlessSingleWallet($playerId, Wallet_model::TRANSFER_TYPE_IN,
                    $mainWalletAmount, $reasonId);
                if(!$success){
                    $this->utils->error_log('transferSeamlessSingleWallet failed', $reasonId, $playerId, $mainWalletAmount);
                }
            }
        }else{
            $success=true;
        }
        return $success;
    }

}

