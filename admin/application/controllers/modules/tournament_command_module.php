<?php
/**
 * 
 * @property Tournament_model $tournament_model
 * @property tournament_lib $tournament_lib
 */
trait tournament_command_module
{
    public function processSyncTournament()
    {
        $this->load->model(['tournament_model']);
        $this->load->library(['tournament_lib']);

        $eventlist = $this->tournament_lib->getActiveEventList();
        $this->utils->debug_log('processSyncTournament', ['eventlist' => $eventlist]);
        //genegrate event job
        foreach ($eventlist as $event) {
            $this->utils->debug_log('processSyncTournament', ['event' => $event]);
            // $this->oneworkSyncTournamentEvent($event['eventId']);
            $is_blocked = false;
            $db = $this->CI->db;
            $res = null;
            $command = 'oneworkSyncTournamentEvent';
            $this->utils->info_log('auto_apply_and_release_t1t_common_bonus funcName', $command);
            $dbName = !empty($db) ? $db->getOgTargetDB() : null;
            $file_list = [];
            $command_params = [
                $event['eventId'],
            ];
            $cmd = $this->utils->generateCommandLine($command, $command_params, $is_blocked, $file_list, $dbName);
            $this->utils->info_log('auto_apply_and_release_t1t_common_bonus cmd' . (empty($db) ? ' empty db' : ' db'), $cmd, $dbName);

            if (!empty($cmd)) {
                $res = $this->utils->runCmd($cmd);
                $this->utils->info_log('auto_apply_and_release_t1t_common_bonus res', $res);
            }

        }
    }

    public function oneworkSyncTournamentEvent($event_id, $rebuild = false, $token=_COMMAND_LINE_NULL)
    {
        //Player_score_model::getPlayerRealBetByDateForNewbet
        $this->load->model(['tournament_model']);
        $this->load->library(['tournament_lib']);

        if(!empty($token) && $token != _COMMAND_LINE_NULL){
            $dateTimeStr = $this->utils->getNowForMysql();
			$result = array('event_id'=>$event_id, 'rebuild'=>$rebuild);
			$done=false;
			$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done);
		}
        // $isEventFound = $this->tournament_model->checkEventExist($event_id);
        try {
            $event = $this->tournament_model->getEventById($event_id);
            if(empty($event)){
                $this->utils->debug_log('syncTournament', ['event_id' => $event_id, 'message' => 'event not found']);
                // $message = 'event not found';
                throw new Exception('event not found');
            }
            $success = $this->lockAndTransForUpdateTournamentPlayerScore($event_id, function() use ($event_id, $event, $rebuild, &$message){
                $this->utils->debug_log('syncTournament', ['event_id' => $event_id, 'message' => 'event found']);
    
                $hasSettleRecords = $this->tournament_lib->hasSettleRecords($event_id);
    
                if($hasSettleRecords && !$rebuild){
                    $message = 'hasSettleRecords';
                    $this->utils->debug_log('hasSettleRecords syncTournament', ['event_id' => $event_id, 'message' => 'hasSettleRecords']);
                    return false;
                }
                
                $getUnsettledOnly = $rebuild ? false : true;
                $playerList = $this->tournament_model->getEventPlayers($event_id, $getUnsettledOnly);
                $this->utils->debug_log('syncTournament', ['event_id' => $event_id, 'playerList' => $playerList]);
    
    
                $tournamentId = $event['tournamentId'];
                list($gamePlatformIds, $gameTypeIds, $gameTagIds, $gamedescriptionIds) = $this->tournament_lib->getTournamentGamesSetting($tournamentId);
                $gameDescList = $this->tournament_model->getGamesDescId($gamePlatformIds, $gameTypeIds, $gameTagIds, $gamedescriptionIds);
                $this->utils->debug_log('getTournamentGamesSetting', ['getGamesDescId' => $gameDescList]);
    
                // //formate timestamp for total_player_game_minute report
                $formatDateMinute_contestStartedAt = $event['contestStartedAt'];
                $formatDateMinute_contestEndedAt = $event['contestEndedAt'];
                $doSettle = $this->tournament_lib->isEventSettle($event['contestEndedAt']);
    
                foreach ($playerList as $player) {
                    $this->utils->debug_log('syncTournament', ['event_id' => $event_id, 'player' => $player]);
                    $playerId = $player['playerId'];
                    //get game descriptions
                    list($eventScore, $lastbetAt) = $this->tournament_lib->getEventPlayerScore( $playerId, $event_id,  $gameDescList, $formatDateMinute_contestStartedAt, $formatDateMinute_contestEndedAt);
    
                    $success = $this->tournament_model->updatePlayerScore($event_id, $playerId, $eventScore, $lastbetAt, $doSettle);
                    $this->utils->info_log('syncTournament', ['event_id' => $event_id, 'player' => $player, 'eventScore' => $eventScore, 'success' => $success]);
                }
                $resultRankList = $this->tournament_lib->calcPlayerRank($event_id);
    
                $rankData = []; 
                if(empty($resultRankList)){
                    $message = 'resultRankList is empty';
                    return false;
                }
                $totalBonus = $this->tournament_lib->getScheduleTotalBonusByType($event['scheduleId'], $event['bonusType']);
                $rankSettingKeyArray = $this->tournament_lib->_getEventRankSettingKeyArray($event_id, $totalBonus, $event['distributionType']);
                
                $this->utils->info_log('syncTournament', ['event_id' => $event_id, 'resultRankList' => $resultRankList]);
                foreach($resultRankList as $index => $player){
                    $row['playerId'] = $player['playerId'];
                    $row['eventId'] = $event_id;
                    $row['external_uniqueid'] = $player['external_uniqueid'];
                    $row['playerRank'] = $player['playerRank'];
                    $row['bonusAmount'] = $this->tournament_lib->calcPlayerBonus($event_id, $player['playerId'], $player['playerRank'], $rankSettingKeyArray);
                    $rankData[] = $row; 
                }
                $this->db->update_batch('tournament_player_apply_records', $rankData, 'external_uniqueid');
                $this->utils->info_log('syncTournament', ['rankData' => $rankData]);
                return true;
            });
            if(!$success){
                throw new Exception('syncTournament failed : '. $message);
            }
        } catch (\Throwable $th) {
            $message = $th->getMessage();
            $success = false;
            $this->utils->error_log('syncTournament', ['event_id' => $event_id, 'message' => $message]);
        }
        if(!empty($token) && $token != _COMMAND_LINE_NULL){
			$result = array('success'=>$success, 'message'=>$message);
			$done=true;
			if ($success) {
				//success
				$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, false);
			} else {
				$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, true);
			}
		}
        $this->utils->info_log('syncTournament', ['event_id' => $event_id, 'success' => $success]);

    }

    public function onlyPayTouramentBonus($event_id, $playerId = 0, $debug_mode='false', $token=_COMMAND_LINE_NULL, $forceToPay=false){
        $this->load->model(['tournament_model']);
        $this->load->library(['tournament_lib']);
        
        if(!empty($token) && $token != _COMMAND_LINE_NULL){
            $dateTimeStr = $this->utils->getNowForMysql();
			$result = array('dateTimeStr'=>$dateTimeStr, 'playerId'=>$playerId, 'debug_mode'=>$debug_mode);
			$done=false;
			$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done);
		}

        $event = $this->tournament_model->getEventById($event_id, true);
        if(empty($event)){
            $this->utils->debug_log('onlyPayTouramentBonus', ['event_id' => $event_id, 'message' => 'event not found']);
            return;
        }
        $msg = $this->utils->debug_log('=========start onlyPayCashback============================', $token);
        $payEnabled = $this->tournament_lib->checkEventDistributionTime($event);
        if($payEnabled){
            $success = $this->lockAndTransForUpdateTournamentPlayerScore($event_id, function() use ($event_id, $event, $playerId, $debug_mode, $token, $forceToPay){
                // will switch to use tournament_model::payEvent
                $this->tournament_model->payEvent($event_id, $playerId, $forceToPay);
                // to pay
                // $payResult = $this->tournament_model->payEvent($event_id, $playerList, $forceToPay);
                // finished pay
                // $status = true;
                // $payAt = $this->utils->getNowForMysql();
                // $this->tournament_model->updateEventPayStatus($event_id, $status, $payAt);
                // update pay status & time to event table
                
                return true;
            });
            $this->utils->debug_log('onlyPayTouramentBonus', ['event_id' => $event_id, 'message' => 'payEnabled is false']);
            return;
        }
        $calcResult = [];
        $payResult = [];
        $this->utils->debug_log('PayTourament is success', 'calcResult', $calcResult, 'payResult', $payResult);
		$this->utils->debug_log('=========end onlyPayTouramentBonus============================', $token);
        
        if(!empty($token) && $token != _COMMAND_LINE_NULL){
			$result = array('payResult'=>$payResult, 'payEnabled'=>$payEnabled);
			$done=true;
			if ($payEnabled) {
				//success
				$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, false);
			} else {
				$this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'result'=> $result ], $done, true);
			}
		}
    }

    public function patchUpdateApplyUid($event_id){
        $this->load->model(['tournament_model']);
        $this->load->library(['tournament_lib']);

        $playerList = $this->tournament_model->getEventPlayers($event_id);
        $this->utils->debug_log('syncTournament', ['event_id' => $event_id, 'playerList' => $playerList]);
        $success = $this->lockAndTransForUpdateTournamentPlayerScore($event_id, function() use ($event_id, $playerList){
            foreach ($playerList as $player) {
                $this->utils->debug_log('syncTournament', ['event_id' => $event_id, 'player' => $player]);
                $playerId = $player['playerId'];
                $external_uniqueid = $this->tournament_lib->generateApplyExternalUniqueid($player['tournamentId'], $event_id, $playerId);
                $this->db->where('eventId', $event_id);
                $this->db->where('playerId', $playerId);
                $this->db->update('tournament_player_apply_records', ['external_uniqueid' => $external_uniqueid]);
                $result = $this->db->affected_rows();
                if(!$result){
                    return false;
                }
            }
            return true;
        });
    }

    public function resetSettleRecords($event_id,  $is_debug = false){
        $this->load->model(['tournament_model']);
        $success = $this->lockAndTransForUpdateTournamentPlayerScore($event_id, function() use ($event_id, $is_debug){
            $result = $this->tournament_model->resetSettleRecords($event_id, $is_debug);
            $this->utils->info_log('resetSettleRecords', ['event_id' => $event_id, 'result' => $result]);
            return $result? true : false;
        });
    }
}
