<?php

trait player_score_module
{
    /**
     * Overview : Sync all player score and ranking player score
     *
     * @param string $dateTimeStr
     * @return void
     */
    public function old_syncPlayerRankWithScore($dateTimeFrom = _COMMAND_LINE_NULL, $dateTimeTo = _COMMAND_LINE_NULL, $sync_rank = true)
    {
        if (!$this->utils->getConfig('enabled_player_score')) {
            $this->utils->debug_log('ABORT syncPlayerRankWithScore: Config disabled');
            return;
        }

        $this->utils->debug_log('=========start syncPlayerRankWithScore============================');
        $this->load->model(['player_score_model']);

        list($dateFrom, $dateTo) = $this->utils->getTodayStringRange();

        $currentDateTimeFrom = new DateTime();
        if ($dateTimeFrom != _COMMAND_LINE_NULL) {
            $currentDateTimeFrom = new DateTime($dateTimeFrom);
            $dateFrom = $this->utils->formatDateTimeForMysql($currentDateTimeFrom);
        }

        $currentDateTimeTo = new DateTime();
        if ($dateTimeTo != _COMMAND_LINE_NULL) {
            $currentDateTimeTo = new DateTime($dateTimeTo);
            $dateTo = $this->utils->formatDateTimeForMysql($currentDateTimeTo);
        }

        $sync_rank = ($sync_rank === false || $sync_rank === 'false' || $sync_rank === 'FALSE' || $sync_rank === '0') ? false : true;

        $this->player_score_model->syncPlayerTotalScore($dateFrom, $dateTo, $sync_rank);

        $this->utils->debug_log('=========end syncPlayerRankWithScore============================');
    }

    /**
     * Overview : Sync all player score and ranking player score
     *
     * @param string $dateTimeStr
     * @return void
     */
    public function syncPlayerRankWithScore($dateTimeFrom = _COMMAND_LINE_NULL, $dateTimeTo = _COMMAND_LINE_NULL, $sync_rank = true)
    {
        if (!$this->utils->getConfig('enabled_player_score')) {
            $this->utils->debug_log('ABORT syncPlayerRankWithScore: Config disabled');
            return;
        }
        $custom_player_rank_list = $this->utils->getConfig('custom_player_rank_list');
        if(empty($custom_player_rank_list)) { 
            $this->utils->debug_log('ABORT syncPlayerRankWithScore: Config disabled');
            return false;
        }

        foreach ($custom_player_rank_list as $rank => $settings) {
            if (isset($settings['enable']) && $settings['enable'] == true) {
                switch ($rank) {
                    case 'newbet':
                        return $this->syncPlayerNewbet($dateTimeFrom, $dateTimeTo, $sync_rank);
                        break;
                }
            }
        }

        $this->utils->debug_log('=========end syncPlayerRankWithScore============================');
    }

    public function resyncPlayerRankManualScore()
    {
        $this->utils->debug_log('=========start resyncPlayerRankManualScore============================');
        $this->load->model(['player_score_model']);
        //get score_history
        $manul_list = $this->player_score_model->getScoreHistoryList();
        //compare manual
        foreach ($manul_list as $record) {
            $this->utils->debug_log('======================== update manual score', $record);
            $player_id = $record['player_id'];
            $score = $record["total_manual_score"];
            $userId = 1;
            $this->player_score_model->insertUpdatePlayerManualScore($player_id, $score, $userId);
        }

        $this->utils->debug_log('=========end resyncPlayerRankManualScore============================');
    }

    public function syncPlayerNewbet($dateTimeFrom = _COMMAND_LINE_NULL, $dateTimeTo = _COMMAND_LINE_NULL, $sync_rank = true)
    {

        if (!$this->utils->getConfig('enabled_player_score')) {
            $this->utils->debug_log('ABORT syncPlayerNewbet: Config disabled');
            return;
        }

        $this->load->model(['player_score_model']);
        if (!$this->player_score_model->checkCustomRank('newbet')) {
            $this->utils->debug_log('ABORT syncPlayerNewbet: Config disabled');
            return false;
        }

        $this->utils->info_log('=========start syncPlayerNewbet============================');

        list($dateFrom, $dateTo) = $this->utils->getTodayStringRange();

        // $currentDateTimeFrom = new DateTime();
        if ($dateTimeFrom != _COMMAND_LINE_NULL) {
            $currentDateTimeFrom = new DateTime($dateTimeFrom);
            $dateFrom = $this->utils->formatDateForMysql($currentDateTimeFrom);
        }

        // $currentDateTimeTo = new DateTime();
        if ($dateTimeTo != _COMMAND_LINE_NULL) {
            $currentDateTimeTo = new DateTime($dateTimeTo);
            $dateTo = $this->utils->formatDateForMysql($currentDateTimeTo);
        }

        // $sync_rank = ($sync_rank === false || $sync_rank === 'false' || $sync_rank === 'FALSE' || $sync_rank === '0') ? false : true;
        $success = false;
        if ($dateTimeFrom == _COMMAND_LINE_NULL) {
            $hourForNow = $this->utils->getHourOnlyForMysql();
            if($hourForNow < 1){
                $_yesterdayForMysql = new DateTime($this->utils->getYesterdayForMysql());
                $yesterdayForMysql = $this->utils->formatDateForMysql($_yesterdayForMysql);
                $success = $this->player_score_model->syncNewbetTotalScore($yesterdayForMysql);
                if($success) {
                    // build list
                    $success = $this->player_score_model->syncNewbetScore($yesterdayForMysql, true);
                }
            }
        }

        $success = $this->player_score_model->syncNewbetTotalScore($dateFrom);
        if($success) {
            // build list
            $success = $this->player_score_model->syncNewbetScore($dateFrom, true);
        }

        $this->utils->info_log('=========end syncPlayerNewbet============================');

        return $success;
    }

    public function auto_apply_and_release_bonus_for_smash_newbet($date_base = null)
    {
        $this->load->model(['promorules', 'player_promo', 'player_model', 'player_score_model']);

        $dates = [];

        $promocms_id = $this->utils->getConfig('auto_apply_and_release_bonus_for_smash_newbet_promocms_id');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_smash_newbet promocms_id', $promocms_id, $date_base);
        $succ = false;
        $count = 0;
        $success_apply_id = [];

        $syncDate = $date_base ?: $this->utils->getYesterdayForMysql();
        if (is_object($syncDate) && $syncDate instanceof DateTime) {
            $syncDate = $syncDate->format('Y-m-d');
        }
        if(is_string($syncDate)){
            $syncDate=$this->utils->formatDateForMysql(new DateTime($syncDate));
        }
        $rank_key = 'newbet_'.$syncDate;
        $do_sync_record = $this->syncPlayerNewbet($syncDate);
        $newbet_setting = $this->player_score_model->checkCustomRank('newbet');
        if (!$newbet_setting) {
            $this->utils->debug_log('ABORT auto_apply_and_release_bonus_for_smash_newbet: Config disabled');
        }
        if (!$do_sync_record) {
            $this->utils->debug_log('ABORT auto_apply_and_release_bonus_for_smash_newbet: sync record failed');

        }
        if (!empty($promocms_id) && !empty($newbet_setting)) {
            
            $promorule = $this->promorules->getPromoruleByPromoCms($promocms_id);
            $promorulesId = $promorule['promorulesId'];
            // $players = $this->player_model->getNotReleasedPromoPlayerListById($promorulesId,$dates);
            // $players = $this->player_score_model->getNewbetNotReleasedPromoPlayerListById($promorulesId, $date_base);
            $players  = $this->player_score_model->getPlayerNewbetRanklist(false, null, null, $syncDate);

            $this->utils->printLastSQL();
            $this->utils->debug_log(__METHOD__, 'get newbet players :', $players);

            if (!empty($players)) {
                foreach ($players as $player) {
                    $playerId = $player['player_id'];
                    $username = $player['username'];
                    $extra_info = [];
                    if(empty($player['playerpromoId'])){
                        try {
                            $msg = null;
                            $res = false;
                            $playerpromoId = null;
                            $succ = $this->lockAndTransForPlayerBalance($playerId, function ()
                            use ($promorule, $promocms_id, $playerId, &$extra_info, &$playerpromoId, $syncDate, &$res, &$msg) {
    
                                $success = true;
                                $preapplication = false;
                                // $extra_info = [];
                                $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                                $extra_info['sync_date'] = $syncDate;
    
                                // $playerpromoId = $this->promorules->requestPromo($playerId, $promorule, null, $promocms_id, null, null, false, null, null, $extra_info);
                                list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, $preapplication, null, $extra_info);
                                return $success;
                            });
    
                            if ($succ && $res) {
                                $count += 1;
                                $success_apply_id[] = $playerId;
                                $this->utils->debug_log(__METHOD__.'apply newbet result on order:', $playerId, $succ, $res, $msg, $promocms_id, $extra_info);
                            } else {
                                $this->utils->info_log(__METHOD__.'apply newbet failed:', $playerId, $succ, $res, $msg, $promocms_id, $extra_info);
                            }
                            $bonusAmount = $extra_info['bonusAmount'];
                            $newbet_total_bonus = $extra_info['newbet_total_bonus'];
                            $player_rate = $extra_info['player_rate'];
                            $update_status = $this->player_score_model->updateRankListPlayerPromoId($playerId, $extra_info['player_promo_request_id'], $rank_key);
                            $this->utils->debug_log(__METHOD__, 'updateRankListPlayerPromoId', ['player_promo_request_id' => $extra_info['player_promo_request_id'], 'update_status' => $update_status]);

                            $score = round($player['score'], 2);
                            $rank = $player['rank'];
                            $msgSenderUserId = 1;
                            $this->load->library(['player_message_library']);
                            $msgSenderName = $this->player_message_library->getDefaultAdminSenderName();//lang('System');
                            $subject = 'Parabéns!';
                            $release_date = new \DateTime;
                            $release_date = $release_date->format('Y / m / d');
                            $content = "Caro $username, sua aposta total em $syncDate é $score reais. Você está atualmente classificado como No.$rank e pode obter $player_rate% do prêmio total de $newbet_total_bonus reais, totalizando $bonusAmount reais.
                            </br>
                            </br> 
                            O bônus foi adicionado à sua conta de plataforma, por favor verifique. </br>
                            Se você tiver alguma dúvida, entre em contato com nosso serviço de atendimento ao cliente online. </br>
                            Desejamos a você um feliz jogo!
                            </br>
                            </br>
                            Enviar de Smash OP team </br>
                            $release_date 1:00AM </br>
                            ";
                            $this->sendInternalMessageToPlayer($msgSenderUserId, $playerId, $msgSenderName, $subject, $content, TRUE);
                            // $this->utils->info_log(__METHOD__, 'apply newbet result on order: player_promo_request_id', $extra_info['player_promo_request_id']);
                            // var_dump($extra_info);
                        } catch (WrongBonusException $e) {
                            $this->utils->error_log($e);
                        }
                    } else {
                        $this->utils->info_log('ignore player due to bonus released', $player);
                    }
                }
            } else {
                $this->utils->info_log('No eligible players found', $players);
            }
        } else {
            $this->utils->info_log(__METHOD__, 'no cmsid provide');

        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_smash_newbet', $count, $success_apply_id);
    }

    public function auto_apply_and_release_bonus_for_smash_newbet_testsend($date_base = null, $username = 'test002'){
        $this->utils->info_log('[START] auto_apply_and_release_bonus_for_smash_newbet_testsend promocms_id', $date_base);
        $this->load->model(['player_model']);

        $msgSenderUserId = 1;
        $this->load->library(['player_message_library']);
        $msgSenderName = $this->player_message_library->getDefaultAdminSenderName();//lang('System');
        $subject = 'Parabéns!';
        $release_date = new \DateTime;
        $release_date = $release_date->format('Y / m / d');
        $syncDate = $date_base;
        $score = 938478.98;
        $rank = 3;
        $player_rate = 29.4;
        $newbet_total_bonus = 33338842965;
        $bonusAmount = 989893.22;
        $content = "Caro $username, sua aposta total em $syncDate é $score reais. Você está atualmente classificado como No.$rank e pode obter $player_rate% do prêmio total de $newbet_total_bonus reais, totalizando $bonusAmount reais.
        </br>
        </br> 
        O bônus foi adicionado à sua conta de plataforma, por favor verifique. </br>
        Se você tiver alguma dúvida, entre em contato com nosso serviço de atendimento ao cliente online. </br>
        Desejamos a você um feliz jogo!
        </br>
        </br>
        Enviar de Smash OP team </br>
        $release_date 1:00AM </br>
        ";

        $player_id = $this->player_model->getPlayerIdByUsername($username);
        $this->utils->debug_log('player',['player_id' => $player_id, 'username' => $username ]);
        $this->sendInternalMessageToPlayer($msgSenderUserId, $player_id, $msgSenderName, $subject, $content, TRUE);
        $this->utils->info_log('[END] auto_apply_and_release_bonus_for_smash_newbet_testsend promocms_id', $date_base);

    }

    public function sendInternalMessageToPlayer(
        $msgSenderUserId // #1
      , $playerId // #2
      , $msgSenderName // #3
      , $subject // #4
      , $message // #5
      , $is_system_message = FALSE // #6
      , $disabled_replay = FALSE // #7
      , $broadcast_id = NULL // #8
      ) {
          $this->load->model(array('internal_message'));
          if($this->utils->getConfig('enable_send_internal_message_in_newbet_process') != false){
              $this->internal_message->addNewMessageAdmin(
                  $msgSenderUserId, 
                  $playerId, 
                  $msgSenderName, 
                  $subject, 
                  $message, 
                  $is_system_message, 
                  $disabled_replay, 
                  $broadcast_id
              );
          }
      }

}
