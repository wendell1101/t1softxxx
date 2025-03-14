<?php

trait apply_quest_module {

    /**
	 * overview : trigger quest from manual player
	 *
	 * @param int	$playerId
     * @param int	$questId
	 * @param bool|false $isHierarchy
	 * @return array
	 */
	public function triggerQuestFromManualPlayer($playerId, $questId, $questRule, $playerQuest, $isHierarchy, $fromDatetime, $toDatetime, $categoryDetails, $isDryRun = false) {
        return $this->checkAndProcessQuest(	$playerId // #1
                                        , $questId // #2
                                        , $questRule // #3
                                        , $playerQuest // #4
                                        , $isHierarchy // #5
                                        , $fromDatetime // #6
                                        , $toDatetime // #7
                                        , $categoryDetails // #8
                                        , $isDryRun // #9
                                        );
    }

    /**
	 * overview : check and process quest
	 *
	 * @param int	$playerId
	 * @param int	$questId
     * @param bool|false $isHierarchy
	 * @return array
	 */
    public function checkAndProcessQuest( $playerId // #1
                                        , $questId // #2
                                        , $questRule // #3
                                        , $playerQuest // #4
                                        , $isHierarchy // #5
                                        , $fromDatetime // #6
                                        , $toDatetime // #7
                                        , $categoryDetails // #8
                                        , $isDryRun = false // #9
    ) {

        $this->load->library(['quest_library']);
        $this->load->model(['quest_manager']);

        $success=false;
        $message='claim failed.';
        $adminId = $this->users->getSuperAdminId();

        if (empty($playerQuest)) {
            $success = false;
            $message = lang('Claim failed. Quest not found.');
            $this->utils->debug_log(__METHOD__."-$playerId", $message, $playerQuest);
            return array($success, $message);
        }

        $playerQuestId = $isHierarchy ? $playerQuest['id'] : $playerQuest[0]['id'];
        $rewardStatus = $isHierarchy ? $playerQuest['rewardStatus'] : $playerQuest[0]['rewardStatus'];
        $createdDatetime = $isHierarchy ? $playerQuest['createdAt'] : $playerQuest[0]['createdAt'];
        $jobStats = $isHierarchy ? $playerQuest['jobStats'] : $playerQuest[0]['jobStats'];
		$period = $categoryDetails['period'];

        if (($period == Quest_manager::BONUS_APPLICATION_LIMIT_DATE_TYPE_DAILY ||
            $period == Quest_manager::BONUS_APPLICATION_LIMIT_DATE_TYPE_WEEKLY ||
            $period == Quest_manager::BONUS_APPLICATION_LIMIT_DATE_TYPE_MONTHLY) &&
            ($createdDatetime < $fromDatetime || $createdDatetime > $toDatetime)) {
            $message = lang('Claim failed. Quest not in period.');
            $this->utils->debug_log(__METHOD__."-$playerId", $message, $createdDatetime, $fromDatetime, $toDatetime);
            return [false, $message];
        }

        if ($rewardStatus == Quest_manager::QUEST_REWARD_STATUS_RECEIVED ||
            $rewardStatus == Quest_manager::QUEST_REWARD_STATUS_EXPIRED ||
            $rewardStatus == Quest_manager::QUEST_REWARD_STATUS_NOT_ACHIEVED) {
            $message = '';
            switch ($rewardStatus) {
                case Quest_manager::QUEST_REWARD_STATUS_RECEIVED:
                case Quest_manager::QUEST_REWARD_STATUS_EXPIRED:
                    $message = lang('Claim failed. Quest already received or expired.');
                    break;
                case Quest_manager::QUEST_REWARD_STATUS_NOT_ACHIEVED:
                    $message = lang('Claim failed. Conditions not meet.');
                    break;
            }
            $this->utils->debug_log(__METHOD__."-$playerId", $message, $rewardStatus);
            return [false, $message];
        }

        $questConditionResult = $this->quest_library->getPlayerQuestProgressStatus($playerId, $questId, $questRule, $fromDatetime, $toDatetime, false);
        $this->utils->debug_log(__METHOD__, "questConditionResult-$playerId", $questConditionResult);

        if ($rewardStatus != Quest_manager::QUEST_REWARD_STATUS_ACHIEVED_NOT_RECEIVED) {
            $success = false;
            $message = lang('Claim failed. Quest not achieved.');
            $this->utils->debug_log(__METHOD__."-$playerId", $message, $rewardStatus);
            return array($success, $message);
        }

        $playerQuestId = $this->approveQuest( $playerId // #1
                                            , $questId // #2
                                            , $questRule // #3
                                            , $adminId // #4
                                            , $playerQuestId // #5
                                            , $jobStats // #6
                                            , $isDryRun // #7
                                        );
        if (!empty($playerQuestId)) {
            $success = true;
            $message = lang('Claim successfully.');
            $this->utils->debug_log(__METHOD__."-$playerId", 'approve quest successfully', $playerQuestId);
        }

        $this->utils->debug_log(__METHOD__."-$playerId", 'approve quest result', $success, $message);
        return array($success, $message);
    }

    /**
	 * overview : approve promo
	 * @param $playerId
	 * @param $promorule
	 * @param $promoCmsSettingId
	 * @param $adminId
	 * @param $playerQuestId
	 * @return int|| null
	 */
	public function approveQuest( $playerId // #1
								, $questId // #2
								, $questRule // #3
								, $adminId // #4
								, $playerQuestId // #5
                                , $jobStats // #6
								, $dryRun = false // #7
	) {
		$this->load->model(array('wallet_model', 'transactions', 'withdraw_condition'));

        $this->utils->debug_log(__METHOD__."-$playerId", 'start', $playerId, $questId, $questRule, $adminId, $playerQuestId, $jobStats, $dryRun);
 
		$playerBonusAmount = 0;
		$withdrawBetAmtCondition = 0;
        $depositAmount = 0;
		$betTimes = 0;
        $note = 'Add Quest Bonus';

        if (in_array($questRule['questConditionType'], [1,2]) ) {//deposit
            $depositAmount = $jobStats;
        }

        $playerBonusAmount = $this->getBonusAmount($playerId, $questId, $questRule, $dryRun);
        $withdrawBetAmtCondition = $this->getWithdrawCondAmount($playerId, $questRule, $playerBonusAmount, $depositAmount, $dryRun);

		if($dryRun){
			$this->utils->debug_log(__METHOD__."-$playerId", 'dry run, will ignore approveQuestToPlayer',
				['questId'=>$questId, 'playerBonusAmount'=>$playerBonusAmount,
				'withdrawBetAmtCondition'=>$withdrawBetAmtCondition, 'playerQuestId'=>$playerQuestId]);
			return null;
		}

		// if($this->isBindWithRoulette($dry_run, $extra_info)){
		// 	$generate_result = false;
		// 	$extra_info['sourcePlayerPromoId'] = $playerPromoId;
		// 	$generate_result = $this->generatePlayerAdditionalRouletteSpin($dry_run, $extra_info, $playerId);
		// 	if(!$generate_result) {
		// 		$success = false;
		// 		$this->appendToDebugLog($extra_info['debug_log'], 'generate spin failed', ['generatePlayerAdditionalRouletteSpinResult'=> $generate_result]);
		// 		$this->utils->debug_log('generate spin failed', ['generatePlayerAdditionalRouletteSpinResult'=> $generate_result]);
		// 		$exceptionMessage = 'Generate spin failed.';
		// 		$errorMessageLang = 'promo_rule.common.error';
		// 		throw new WrongBonusException($errorMessageLang, $exceptionMessage );
		// 		return null;
		// 	}
		// }
        
        $bonusTransId = $this->transactions->createQuestBonusTransaction($adminId, $playerId, $playerBonusAmount, null, $note);                

		if( empty($bonusTransId)){
            $this->utils->error_log('create quest bonus transaction failed', $playerId, $playerBonusAmount);
            return null;
        }

		$betTimes = 0;
		if ($questRule['withdrawalConditionType'] == Quest_manager::WITHDRAW_CONDITION_TYPE_BETTING_TIMES) {
			$betTimes = $questRule['withdrawReqBettingTimes'];
		}

        $withdrawConditionId = $this->withdraw_condition->createWithdrawConditionForQuestBonus($playerId, $bonusTransId, $withdrawBetAmtCondition, $playerBonusAmount, $betTimes);

        $playerQuestId = $this->syncPlayerQuest( $playerId
                                                , $playerQuestId
                                                , $playerBonusAmount
                                                , $withdrawConditionId
                                                , $bonusTransId
                                            );
    
        $this->utils->debug_log(__METHOD__."-$playerId", 'end', $playerId, $playerQuestId);
		return $playerQuestId;
	} // EOF approveQuest

    /**
	 * overview : sync player quest
	 *
	 * @param int		$playerId
	 * @param int		$playerQuestId
	 * @param double	$playerBonusAmount
	 * @param int	    $withdrawConditionId
	 * @return int
	 */
	public function syncPlayerQuest( $playerId // #1
                                    , $playerQuestId // #2
                                    , $playerBonusAmount // #3
                                    , $withdrawConditionId // #4
                                    , $bonusTransId // #5

    ) {
        $this->utils->debug_log(__METHOD__."-$playerId", $playerQuestId, $playerBonusAmount, $withdrawConditionId, $bonusTransId);
        if (!empty($playerQuestId)) {
            $data = array(
                'rewardStatus' => Quest_manager::QUEST_REWARD_STATUS_RECEIVED,
                'bonusAmount' => $playerBonusAmount,
                'withdrawConditionId' => $withdrawConditionId,
                'transactionId' => $bonusTransId,
                'playerRequestIp' => $this->utils->getIP(),
                'updatedAt' => $this->utils->getNowForMysql(),
                'releaseTime' => $this->utils->getNowForMysql(),
            );

            $playerQuestId = $this->updatePlayerQuestState($playerId, $playerQuestId, $data);
            return $playerQuestId;
        } 

        return false;
    }// EOF syncPlayerQuest

    public function updatePlayerQuestState($playerId, $playerQuestId, $data){
        $this->utils->debug_log(__METHOD__."-$playerId", $playerQuestId, $data);
        $this->db->where('id', $playerQuestId);
        return $this->db->update('player_quest_job_state', $data);
    }

    public function getBonusAmount($playerId, $questId, $questRule, $isDryRun = false) {
        $playerBonusAmount = 0;
        switch ($questRule['bonusConditionType']) {
            case Quest_manager::QUEST_BONUS_RELEASE_RULE_FIXED_AMOUNT:
                $playerBonusAmount = $questRule['bonusConditionValue'];
                break;
        }

        $this->utils->debug_log(__METHOD__."-$playerId", $playerBonusAmount, $questRule, $isDryRun);
        return $this->utils->roundCurrency($playerBonusAmount, 2);
    }

    public function getWithdrawCondAmount($playerId, $questRule, $playerBonusAmount, $depositAmount = null, $isDryRun = false) {
        $withdrawBetAmtCondition = 0;

        switch ($questRule['withdrawalConditionType']) {
            case Quest_manager::WITHDRAW_CONDITION_TYPE_FIXED_AMOUNT:
                $withdrawBetAmtCondition = $questRule['withdrawReqBetAmount'];
                break;
            case Quest_manager::WITHDRAW_CONDITION_TYPE_BONUS_TIMES:
                $betTimes = $questRule['withdrawReqBonusTimes'];
                $withdrawBetAmtCondition = $playerBonusAmount * $betTimes;
                break;
            case Quest_manager::WITHDRAW_CONDITION_TYPE_BETTING_TIMES:
                $betTimes = $questRule['withdrawReqBettingTimes'];
                $withdrawBetAmtCondition = ($playerBonusAmount + $depositAmount) * $betTimes;
                break;
        }

        $this->utils->debug_log(__METHOD__."-$playerId", $withdrawBetAmtCondition, $questRule, $playerBonusAmount, $depositAmount, $isDryRun);
        return $withdrawBetAmtCondition;
    }
}
