<?php

/**
 *
 * game bet only wallet is only for free bet
 *
 * @see Wallet_model
 *
 */
trait game_bet_only_wallet_module {

    /**
     * insert or ignore
     * @param  int $playerId
     * @param  string $username
     * @param  object $db
     * @return boolean
     */
    public function safeInitGameBetOnlyWallet($playerId, $username, $db=null){
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
        $this->utils->debug_log('start safeInitGameBetOnlyWallet');
        $this->load->model(['player_model']);
        $agentId=null;
        if($this->utils->getConfig('write_agent_id_to_game_bet_only_wallet')){
            $agentId=$this->player_model->getAgentIdFromPlayerId($playerId);
        }

        $internalUniqueKey=$username;
        $externalUniqueKey=$username;
        $data=[
            'player_id'=>$playerId,
            'internal_unique_key'=>$internalUniqueKey,
            'external_unique_key'=>$externalUniqueKey,
            'created_at'=>$this->utils->getNowForMysql(),
            'updated_at'=>$this->utils->getNowForMysql(),
            'agent_id'=>$agentId,
        ];
  
        $initBalance=0;
        $data['balance']=$initBalance;
        //insert new
        $id=$this->insertOrIgnoreRow($data, 'game_bet_only_wallet', $db);
        $this->utils->debug_log('id of insert ignore', $id);
        $this->utils->printLastSQL();

        return $success;
    }

    /**
     * transferGameBetOnlyWallet
     * @param  int $playerId
     * @param  string $type
     * @param  double $amount
     * @param  int &$reasonId
     * @return boolean $success
     */
    public function transferGameBetOnlyWallet($playerId, $type, $amount, &$reasonId){
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
                $succ=$this->queryGameBetOnlyWallet($playerId, $balance, $reasonId);
                //check enough balance
                if($succ){
                    if($this->utils->compareResultFloat($amount, '>', $balance)){
                        $this->utils->debug_log('no enough balance', $playerId, $type, $amount, $balance);
                        //no enough balance
                        $reasonId=Abstract_game_api::REASON_NO_ENOUGH_BALANCE;
                        return false;
                    }
                }else{
                    //reasonid in queryGameBetOnlyWallet
                    return false;
                }
                $updBal='balance-'.$amount;
            }
            $this->db->set('balance', $updBal, false);
            $this->db->where('player_id', $playerId);
            $cnt=$this->runAnyUpdateWithResult('game_bet_only_wallet');
            $this->utils->printLastSQL();
            $success=$cnt>0;
            if($cnt<=0){
                $this->utils->error_log('update nothing, init and retry', $playerId, $amount, $cnt);
                //try sync and do it again
                $username=$this->player_model->getUsernameById($playerId);
                $succ=$this->safeInitGameBetOnlyWallet($playerId, $username);
                if($succ){
                    $this->db->set('balance', $updBal, false);
                    $this->db->where('player_id', $playerId);
                    $cnt=$this->runAnyUpdateWithResult('game_bet_only_wallet');
                    $success=$cnt>0;
                    if($success){
                        $this->utils->debug_log('retry is success');
                    }else{
                        $this->utils->error_log('retry is failed', $playerId, $amount, $cnt);
                        $reasonId=Abstract_game_api::REASON_BALANCE_NOT_SYNC;
                    }
                }
            }else{
                $this->utils->debug_log('transferGameBetOnlyWallet success', $playerId, $amount, $cnt);
            }
        }else{
            //wrong amount
            $this->utils->error_log('invalid amount on transferGameBetOnlyWallet', $playerId, $amount);
            $reasonId=Abstract_game_api::REASON_INVALID_TRANSFER_AMOUNT;
        }
        return $success;
    }

    public function queryGameBetOnlyWallet($playerId, &$balance, &$reasonId){
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

        $this->db->select('balance')->from('game_bet_only_wallet')->where('player_id', $playerId);
        $balance=$this->runOneRowOneField('balance');
        if($balance===null){
            $this->utils->error_log('cannot find balance from game_bet_only_wallet');
            $username=$this->player_model->getUsernameById($playerId);
            //means missing balance
            $succ=$this->safeInitGameBetOnlyWallet($playerId, $username);
            if($succ){
                $balance=0;
                $success=true;
            }else{
                $this->utils->error_log('init game bet only wallet failed');
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

    public function withdrawAllGameBetOnlyWallet($playerId, &$amount, &$reasonId){
        if(empty($playerId)){
            $reasonId=Abstract_game_api::REASON_NOT_FOUND_PLAYER;
            return false;
        }
        $success=false;
        //get balance
        $succ=$this->queryGameBetOnlyWallet($playerId, $balance, $reasonId);
        //check enough balance
        if($succ){
            if($balance>0){
                //only do it >0
                //try withdraw all
                $this->db->set('balance', 0)->where('player_id', $playerId);
                $cnt=$this->runAnyUpdateWithResult('game_bet_only_wallet');
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
            //reasonid in queryGameBetOnlyWallet
            return false;
        }

        return $success;
    }

    public function blockGameBetOnlyWallet($playerId){
        if(empty($playerId)){
            $this->utils->error_log('blockGameBetOnlyWallet failed, player id is empty');
            return false;
        }
        $success=false;
        $this->db->set('is_blocked', self::DB_TRUE)->where('player_id', $playerId);
        $cnt=$this->runAnyUpdateWithResult('game_bet_only_wallet');
        if($cnt<=0){
            $username=$this->player_model->getUsernameById($playerId);
            //try sync
            $success=$this->safeInitGameBetOnlyWallet($playerId, $username);
            if($success){
                $this->db->set('is_blocked', self::DB_TRUE)->where('player_id', $playerId);
                $success=$this->runAnyUpdate('game_bet_only_wallet');
            }
        }else{
            $success=true;
        }
        return $success;
    }

    public function unblockGameBetOnlyWallet($playerId){
        if(empty($playerId)){
            $this->utils->error_log('unblockGameBetOnlyWallet failed, player id is empty');
            return false;
        }
        $success=false;
        $this->db->set('is_blocked', self::DB_FALSE)->where('player_id', $playerId);
        $cnt=$this->runAnyUpdateWithResult('game_bet_only_wallet');
        if($cnt<=0){
            $username=$this->player_model->getUsernameById($playerId);
            //try sync
            $success=$this->safeInitGameBetOnlyWallet($playerId, $username);
        }else{
            $success=true;
        }
        return $success;
    }

    /**
     * isBlockedOnGameBetOnlyWallet
     * @param  int $playerId
     * @return boolean blocked or not
     */
    public function isBlockedOnGameBetOnlyWallet($playerId){
        if(empty($playerId)){
            $this->utils->error_log('isBlockedOnGameBetOnlyWallet failed, player id is empty');
            return false;
        }
        $this->db->select('is_blocked')->from('game_bet_only_wallet')->where('player_id', $playerId);
        return $this->runOneRowOneField('is_blocked')==self::DB_TRUE;
    }

    // public function gameBetOnlyWalletAllToMainWallet($playerId){
    //     //withdraw all
    //     $amount=0;
    //     $success=$this->withdrawAllGameBetOnlyWallet($playerId, $amount, $reasonId);

    //     if($success){
    //         if($amount>0){
    //             $success=$this->incMainManuallyOnBigWallet($playerId, $amount);
    //         }else{
    //             //ignore
    //         }
    //     }else{
    //         $this->utils->error_log('withdrawal free bet wallet failed');
    //     }
    //     return $success;
    // }

    // public function mainWalletAllToGameBetOnlyWallet($playerId){
    //     $success=false;
    //     //query main
    //     $mainWalletAmount=$this->getMainWalletTotalNofrozenOnBigWalletByPlayer($playerId);
    //     if($mainWalletAmount>0){
    //         //withdraw from main
    //         $success=$this->decMainManuallyOnBigWallet($playerId, $mainWalletAmount);
    //         if($success){
    //             //save to free bet wallet
    //             $success=$this->transferGameBetOnlyWallet($playerId, Wallet_model::TRANSFER_TYPE_IN,
    //                 $mainWalletAmount, $reasonId);
    //             if(!$success){
    //                 $this->utils->error_log('transferGameBetOnlyWallet failed', $reasonId, $playerId, $mainWalletAmount);
    //             }
    //         }
    //     }else{
    //         $success=true;
    //     }
    //     return $success;
    // }

}

