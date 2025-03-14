<?php

trait customized_promo_rules_module
{

    public function auto_apply_and_release_bonus_for_smash_sportsbet($player_id = _COMMAND_LINE_NULL){

        $promocms_ids = $this->utils->getConfig('auto_apply_and_release_bonus_for_smash_sportsbet_promocms_id');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_smash_sportsbet promocms_ids',$promocms_ids);
        $succ = false;
        $count = 0;
        $success_apply_id = [];

        if (!empty($promocms_ids)) {
            $this->load->model(['promorules','player_promo','player_model', 'total_player_game_day']);

            foreach ($promocms_ids as $promocms_id) {
                $promorule=$this->promorules->getPromoruleByPromoCms($promocms_id);

                $allow_game_type = $this->utils->getConfig('promo_rule_smash_sportsbet_bonus_allow_game_type');
                if(empty($allow_game_type)){
                    $this->utils->info_log(__METHOD__ . 'do not have allow game type', $allow_game_type);
                    return;
                }

                $this->db->select('tpgd.player_id, SUM(tpgd.betting_amount) as total_bet, p.username, pd.registrationIp, p.disabled_promotion')
                    ->from('total_player_game_day tpgd')
                    ->join('player p', 'p.playerId = tpgd.player_id  ', 'left')
                    ->join('playerdetails pd', 'pd.playerId = p.playerId', 'left');

                if(is_array($allow_game_type)){
                    $this->db->where_in('tpgd.game_type_id', $allow_game_type);
                }else{
                    $this->db->where('tpgd.game_type_id', $allow_game_type);
                }

                if($player_id != _COMMAND_LINE_NULL){
                    $this->db->where('tpgd.player_id', $player_id);
                }

                $this->db->group_by('tpgd.player_id');
                $query = $this->db->get();
                $players = $query->result_array();
                $this->utils->printLastSQL();
                $this->utils->info_log('get bet players',$players);

                if (!empty($players)) {
                    foreach ($players as $player) {
                        $playerId = $player['player_id'];
                        $registerIp = $player['registrationIp'];
                        $username = $player['username'];
                        $disabled_promotion = $player['disabled_promotion'];

                        if($disabled_promotion == 1){
                            $this->utils->debug_log('player disabled promotion', $playerId);
                            continue;
                        }

                        $test_player_list = $this->utils->getConfig('auto_apply_and_release_bonus_player_list');
                        if (!empty($test_player_list)) {
                            if (!in_array($username, $test_player_list)) {
                                continue;
                            }
                        }

                        try{
                            $msg=null;

                            $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $registerIp){

                                $success = true;
                                $preapplication=false;
                                $extra_info = [];
                                $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                                $extra_info['player_request_ip'] = $registerIp;

                                list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, $preapplication, null, $extra_info);
                                return $success;
                            });

                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[] = $playerId;
                            }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promocms_id, $extra_info, $registerIp);

                        }catch(WrongBonusException $e){
                            $this->utils->error_log($e);
                        }
                    }
                }
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_smash_sportsbet', $count, $success_apply_id);
    }

    public function auto_apply_and_release_bonus_for_alpha_bet_weekly($dateFromStr = _COMMAND_LINE_NULL, $dateToStr = _COMMAND_LINE_NULL, $player_id = _COMMAND_LINE_NULL, $promoCmsId = _COMMAND_LINE_NULL){
        $promocms_ids = $this->utils->getConfig('auto_apply_and_release_bonus_for_alpha_bet_weekly_promocms_id');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_alpha_bet_weekly promocms_ids',$promocms_ids);
        $count = 0;
        $success_apply_id = [];

        if (!empty($promocms_ids)) {
            $this->load->model(['promorules', 'player_promo', 'player_model', 'game_type_model']);
            $this->load->library(['og_utility']);

            if( ($dateFromStr != _COMMAND_LINE_NULL) && ($dateToStr != _COMMAND_LINE_NULL) ){
                $fromDate = $this->utils->formatDateForMysql(new DateTime($dateFromStr));
                $toDate = $this->utils->formatDateForMysql(new DateTime($dateToStr));
            }else{
                $lastMonday = date("Y-m-d", strtotime("last week monday"));
                list($fromDate, $toDate) = $this->utils->getFromToByWeek($lastMonday);
            }

            if($player_id == _COMMAND_LINE_NULL){
                $player_id = null;
            }

            if($promoCmsId == _COMMAND_LINE_NULL){
                $promoCmsId = null;
            }
            $this->utils->debug_log('params info', $fromDate, $toDate, $player_id, $promoCmsId);

            if(!empty($promocms_ids)){
                foreach($promocms_ids as $promo_cms_id => &$game_tag_id){
                    if(empty($game_tag_id)){
                        $this->utils->info_log('missing allow game tag', $promo_cms_id, $game_tag_id);
                        continue;
                    }

                    $game_type_id = [];
                    $game_type_raw_list = $this->game_type_model->getAllGameTypeListWithTag($game_tag_id);
                    if(!empty($game_type_raw_list)){
                        $game_type_id = $this->og_utility->array_pluck($game_type_raw_list, 'id');
                        $promocms_ids[$promo_cms_id] = $game_type_id;
                    }
                }
            }
            $this->utils->info_log('convert game tag to game type', $promocms_ids);

            if(!empty($promoCmsId) && !empty($promocms_ids[$promoCmsId])){
                // only allow this promo cms check
                $gameTypeId = $promocms_ids[$promoCmsId];
                $promocms_ids = [$promoCmsId => $gameTypeId];
                $this->utils->info_log('use Specified promo cms id', $promoCmsId);
            }

            foreach ($promocms_ids as $promo_cms_id => $game_type_id) {
                $this->utils->info_log('promo cms id', $promo_cms_id);
                $promorule=$this->promorules->getPromoruleByPromoCms($promo_cms_id);
                $players = $this->player_model->getPlayerByTotalLossesWeeklyCustomizedConditions($fromDate, $toDate, $player_id, $game_type_id);

                if (!empty($players)) {
                    foreach ($players as $player) {
                        $playerId = $player['player_id'];
                        $registerIp = $player['registrationIp'];
                        $username = $player['username'];
                        $disabled_promotion = $player['disabled_promotion'];

                        if($disabled_promotion == 1){
                            $this->utils->debug_log('player disabled promotion', $playerId);
                            continue;
                        }

                        $test_player_list = $this->utils->getConfig('auto_apply_and_release_bonus_player_list');
                        if (!empty($test_player_list)) {
                            if (!in_array($username, $test_player_list)) {
                                continue;
                            }
                        }

                        try{
                            $msg=null;

                            $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $promo_cms_id, $playerId, &$msg, &$extra_info, &$res, $registerIp, $game_type_id){

                                $success = true;
                                $preapplication=false;
                                $extra_info = [];
                                $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                                $extra_info['player_request_ip'] = $registerIp;
                                $extra_info['custom_promo_game_type_ids'] = $game_type_id;

                                list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promo_cms_id, $preapplication, null, $extra_info);
                                return $success;
                            });

                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[] = $playerId;
                            }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promo_cms_id, $extra_info, $registerIp);

                        }catch(WrongBonusException $e){
                            $this->utils->error_log($e);
                        }
                    }
                }
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_alpha_bet_weekly', $count, $success_apply_id);
    }

    public function auto_apply_for_ole777idr_total_losses_weekly($dateFromStr = _COMMAND_LINE_NULL, $dateToStr = _COMMAND_LINE_NULL, $player_id = _COMMAND_LINE_NULL, $promoCmsId = _COMMAND_LINE_NULL){
        $promocms_ids = $this->utils->getConfig('auto_apply_and_release_bonus_for_ole777idr_total_losses_weekly_promocms_id');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_ole777idr_total_losses_weekly promocms_ids',$promocms_ids);
        $count = 0;
        $success_apply_id = [];

        if (!empty($promocms_ids)) {
            $this->load->model(['promorules', 'player_promo', 'player_model', 'game_type_model']);
            $this->load->library(['og_utility']);

            if( ($dateFromStr != _COMMAND_LINE_NULL) && ($dateToStr != _COMMAND_LINE_NULL) ){
                $fromDate = $this->utils->formatDateForMysql(new DateTime($dateFromStr));
                $toDate = $this->utils->formatDateForMysql(new DateTime($dateToStr));
            }else{
                $lastMonday = date("Y-m-d", strtotime("last week monday"));
                list($fromDate, $toDate) = $this->utils->getFromToByWeek($lastMonday);
            }

            if($player_id == _COMMAND_LINE_NULL){
                $player_id = null;
            }
            
            if($promoCmsId == _COMMAND_LINE_NULL){
                $promoCmsId = null;
            }
            $this->utils->debug_log('params info', $fromDate, $toDate, $player_id, $promoCmsId);

            if(!empty($promocms_ids)){
                foreach($promocms_ids as $promo_cms_id => &$game_tag_id){
                    if(empty($game_tag_id)){
                        $this->utils->info_log('missing allow game tag', $promo_cms_id, $game_tag_id);
                        continue;
                    }

                    $game_type_id = [];
                    $game_type_raw_list = $this->game_type_model->getAllGameTypeListWithTag($game_tag_id);
                    if(!empty($game_type_raw_list)){
                        $game_type_id = $this->og_utility->array_pluck($game_type_raw_list, 'id');
                        $promocms_ids[$promo_cms_id] = $game_type_id;
                    }
                }
            }
            $this->utils->info_log('convert game tag to game type', $promocms_ids);
            
            if(!empty($promoCmsId) && !empty($promocms_ids[$promoCmsId])){
                // only allow this promo cms check
                $gameTypeId = $promocms_ids[$promoCmsId];
                $promocms_ids = [$promoCmsId => $gameTypeId];
                $this->utils->info_log('use Specified promo cms id', $promoCmsId);
            }

            foreach ($promocms_ids as $promo_cms_id => $game_type_id) {
                $this->utils->info_log('promo cms id', $promo_cms_id);
                $promorule=$this->promorules->getPromoruleByPromoCms($promo_cms_id);
                $players = $this->player_model->getPlayerByTotalLossesWeeklyCustomizedConditions($fromDate, $toDate, $player_id, $game_type_id);

                if (!empty($players)) {
                    foreach ($players as $player) {
                        $playerId = $player['player_id'];
                        $registerIp = $player['registrationIp'];
                        $username = $player['username'];
                        $disabled_promotion = $player['disabled_promotion'];

                        if($disabled_promotion == 1){
                            $this->utils->debug_log('player disabled promotion', $playerId);
                            continue;
                        }

                        $test_player_list = $this->utils->getConfig('auto_apply_and_release_bonus_player_list');
                        if (!empty($test_player_list)) {
                            if (!in_array($username, $test_player_list)) {
                                continue;
                            }
                        }

                        try{
                            $msg=null;

                            $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $promo_cms_id, $playerId, &$msg, &$extra_info, &$res, $registerIp){

                                $success = true;
                                $preapplication=false;
                                $extra_info = [];
                                $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                                $extra_info['player_request_ip'] = $registerIp;

                                list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promo_cms_id, $preapplication, null, $extra_info);
                                return $success;
                            });

                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[] = $playerId;
                            }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promo_cms_id, $extra_info, $registerIp);

                        }catch(WrongBonusException $e){
                            $this->utils->error_log($e);
                        }
                    }
                }
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_ole777idr_total_losses_weekly', $count, $success_apply_id);
    }

    public function auto_apply_bonus_for_ole777th_consecutive_deposit_bonus($dateFromStr = _COMMAND_LINE_NULL, $dateToStr = _COMMAND_LINE_NULL, $player_id = _COMMAND_LINE_NULL, $ignore_trans_for_test = false, $periodMinDeposit = 3000, $is_dry_run = false){
        $promocms_id = $this->utils->getConfig('auto_apply_and_release_bonus_for_ole777th_consecutive_deposit_bonus_promocms_id');
        $this->utils->info_log('start auto_apply_bonus_for_ole777th_consecutive_deposit_bonus promocms_id',$promocms_id);

        $count = 0;
        $success_apply_id = [];

        if (!empty($promocms_id)) {
            $this->load->model(['promorules','player_promo','player_model']);

            $ip = $this->utils->getIP();
            $promorule=$this->promorules->getPromoruleByPromoCms($promocms_id);

            if( ($dateFromStr != _COMMAND_LINE_NULL) && ($dateToStr != _COMMAND_LINE_NULL) ){
                $fromDate = $this->utils->formatDateForMysql(new DateTime($dateFromStr));
                $toDate = $this->utils->formatDateForMysql(new DateTime($dateToStr));
            }else{
                $fromDate = date('Y-m-01', strtotime('-1 month'));
                $toDate = date('Y-m-t', strtotime('-1 month'));
            }

            $is_dry_run=$is_dry_run=='true';
            $ignore_trans_for_test=$ignore_trans_for_test=='true';
            if($player_id == _COMMAND_LINE_NULL){
                $player_id = null;
            }

            $this->utils->info_log('from date AND to date', $fromDate, $toDate, 'is_dry_run', $is_dry_run, 'player', $player_id,
                'ignore_trans_for_test', $ignore_trans_for_test, 'periodMinDeposit', $periodMinDeposit);

            $players = $this->player_model->getDepositPlayersByOle777thConsecutiveDepositBonus($fromDate, $toDate, $player_id, $ignore_trans_for_test, $periodMinDeposit);
            $this->utils->info_log('get all deposit players', $players);

            if (!empty($players)) {
                foreach ($players as $player) {
                    $playerId = $player['playerId'];
                    $username = $player['username'];

                    $test_player_list = $this->utils->getConfig('auto_apply_and_release_bonus_player_list');
                    if (!empty($test_player_list)) {
                        if (!in_array($username, $test_player_list)) {
                            continue;
                        }
                    }

                    try{
                        $msg=null;

                        $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                        use($promorule, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $ip, $is_dry_run){

                            $success = true;
                            $preapplication=false;
                            $extra_info = [];
                            $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                            $extra_info['player_request_ip'] = $ip;

                            list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, $preapplication, null, $extra_info, $is_dry_run);
                            return $success;
                        });

                        if($succ && $res){
                            $count += 1;
                            $success_apply_id[] = $playerId;
                        }
                        $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promocms_id, $extra_info, $ip);

                    }catch(WrongBonusException $e){
                        $this->utils->error_log($e);
                    }
                }
            }else{
                $this->utils->info_log('No eligible players found',$players);
            }
        }
        $this->utils->info_log('end auto_apply_bonus_for_ole777th_consecutive_deposit_bonus', $count, $success_apply_id);
    }

    public function auto_release_bonus_for_ole777idr_total_losses_weekly($dateFromStr = _COMMAND_LINE_NULL, $dateToStr = _COMMAND_LINE_NULL, $player_id = null, $promoCmsId = null, $dry_run = false){
        $this->utils->info_log('start auto_release_bonus_for_ole777idr_total_losses_weekly');

        $promocms_ids = $this->utils->getConfig('auto_apply_and_release_bonus_for_ole777idr_total_losses_weekly_promocms_id');
        $this->utils->debug_log('origin promo cms id and game type', $promocms_ids);

        if(!empty($promoCmsId) && !empty($promocms_ids[$promoCmsId])){
            // only allow this promo cms check
            $gameTypeId = $promocms_ids[$promoCmsId];
            $promocms_ids = [$promoCmsId => $gameTypeId];
            $this->utils->info_log('use Specified promo cms id', $promoCmsId);
        }

        if( ($dateFromStr != _COMMAND_LINE_NULL) && ($dateToStr != _COMMAND_LINE_NULL) ){
            $fromDateTime = $this->utils->formatDateForMysql(new DateTime($dateFromStr)) . ' ' . Utils::FIRST_TIME;
            $toDateTime = $this->utils->formatDateForMysql(new DateTime($dateToStr)) . ' ' . Utils::LAST_TIME;
        }else{
            list($fromDateTime, $toDateTime) = $this->utils->getTodayStringRange();
        }

        $this->utils->debug_log('from date to date', $fromDateTime, $toDateTime, $player_id);

        if(!empty($promocms_ids)){
            $this->load->model(['promorules', 'player_promo', 'users']);
            $adminId = $this->users->getSuperAdminId();
            $reason = 'Auto Approve By Cronjob';

            foreach (array_keys($promocms_ids) as $promo_cms_id){
                $promorule=$this->promorules->getPromoruleByPromoCms($promo_cms_id);
                $promorulesId = $promorule['promorulesId'];

                $player_promos = $this->player_promo->getNotRelasedPromoRequest($promorulesId, $promo_cms_id, $player_id, $fromDateTime, $toDateTime);
                $this->utils->debug_log('get not release player promos', $player_promos);

                if(!empty($player_promos)){
                    $succ = false;
                    $count = 0;
                    $success_apply_id = [];

                    foreach ($player_promos as $player_promos){
                        $playerId = $player_promos['playerId'];
                        $player_promo_id = $player_promos['playerpromoId'];

                        try{
                            $succ = $this->lockAndTransForPlayerBalance($playerId, function()
                            use($playerId, $promorule, $promo_cms_id, $adminId, $player_promo_id, $reason, $dry_run, &$extra_info){
                                $success = !!$this->promorules->approvePromo($playerId, $promorule, $promo_cms_id, $adminId, null, null, $player_promo_id, $extra_info, $reason, $dry_run);
                                return $success;
                            });

                            if($succ){
                                $count += 1;
                                $success_apply_id[] = $player_promo_id;
                            }

                        }catch(WrongBonusException $e){
                            $this->utils->error_log($e);
                        }
                    }

                    $this->utils->info_log('auto_release_bonus_for_ole777idr_total_losses_weekly result', $promo_cms_id, $success_apply_id);
                }else{
                    $this->utils->debug_log('empty player promo with this promo', $promo_cms_id);
                }
            }
        }
        $this->utils->info_log('end auto_release_bonus_for_ole777idr_total_losses_weekly');
    }

    public function auto_apply_for_ole777idr_live_dealer_total_losses_weekly($dateFromStr = _COMMAND_LINE_NULL, $dateToStr = _COMMAND_LINE_NULL, $player_id = _COMMAND_LINE_NULL, $promoCmsId = _COMMAND_LINE_NULL){
        $promocms_ids = $this->utils->getConfig('auto_apply_for_ole777idr_live_dealer_total_losses_weekly_bonus_promocms_id');
        $this->utils->info_log('start auto_apply_for_ole777idr_live_dealer_total_losses_weekly promocms_ids',$promocms_ids);
        $count = 0;
        $success_apply_id = [];

        if (!empty($promocms_ids)) {
            $this->load->model(['promorules', 'player_promo', 'player_model', 'game_type_model']);
            $this->load->library(['og_utility']);

            if( ($dateFromStr != _COMMAND_LINE_NULL) && ($dateToStr != _COMMAND_LINE_NULL) ){
                $fromDate = $this->utils->formatDateForMysql(new DateTime($dateFromStr));
                $toDate = $this->utils->formatDateForMysql(new DateTime($dateToStr));
            }else{
                $lastMonday = date("Y-m-d", strtotime("last week monday"));
                list($fromDate, $toDate) = $this->utils->getFromToByWeek($lastMonday);
            }

            if($player_id == _COMMAND_LINE_NULL){
                $player_id = null;
            }

            if($promoCmsId == _COMMAND_LINE_NULL){
                $promoCmsId = null;
            }
            $this->utils->debug_log('params info', $fromDate, $toDate, $player_id, $promoCmsId);           
            
            if(!empty($promocms_ids)){
                foreach($promocms_ids as $promo_cms_id => &$game_tag_id){
                    if(empty($game_tag_id)){
                        $this->utils->info_log('missing allow game tag', $promo_cms_id, $game_tag_id);
                        continue;
                    }

                    $game_type_id = [];
                    $game_type_raw_list = $this->game_type_model->getAllGameTypeListWithTag($game_tag_id);
                    if(!empty($game_type_raw_list)){
                        $game_type_id = $this->og_utility->array_pluck($game_type_raw_list, 'id');
                        $promocms_ids[$promo_cms_id] = $game_type_id;
                    }
                }
            }
            $this->utils->info_log('convert game tag to game type', $promocms_ids);

            if(!empty($promoCmsId) && !empty($promocms_ids[$promoCmsId])){
                // only allow this promo cms check
                $gameTypeId = $promocms_ids[$promoCmsId];
                $promocms_ids = [$promoCmsId => $gameTypeId];
                $this->utils->info_log('use Specified promo cms id', $promoCmsId);
            }

            foreach ($promocms_ids as $promo_cms_id => $game_type_id) {
                $this->utils->info_log('promo cms id', $promo_cms_id);
                $promorule=$this->promorules->getPromoruleByPromoCms($promo_cms_id);
                $players = $this->player_model->getPlayerByTotalLossesWeeklyCustomizedConditions($fromDate, $toDate, $player_id, $game_type_id);

                if (!empty($players)) {
                    foreach ($players as $player) {
                        $playerId = $player['player_id'];
                        $registerIp = $player['registrationIp'];
                        $username = $player['username'];
                        $disabled_promotion = $player['disabled_promotion'];

                        if($disabled_promotion == 1){
                            $this->utils->debug_log('player disabled promotion', $playerId);
                            continue;
                        }

                        $test_player_list = $this->utils->getConfig('auto_apply_and_release_bonus_player_list');
                        if (!empty($test_player_list)) {
                            if (!in_array($username, $test_player_list)) {
                                continue;
                            }
                        }

                        try{
                            $msg=null;

                            $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $promo_cms_id, $playerId, &$msg, &$extra_info, &$res, $registerIp){

                                $success = true;
                                $preapplication=false;
                                $extra_info = [];
                                $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                                $extra_info['player_request_ip'] = $registerIp;

                                list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promo_cms_id, $preapplication, null, $extra_info);
                                return $success;
                            });

                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[] = $playerId;
                            }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promo_cms_id, $extra_info, $registerIp);

                        }catch(WrongBonusException $e){
                            $this->utils->error_log($e);
                        }
                    }
                }
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_ole777idr_live_dealer_total_losses_weekly', $count, $success_apply_id);
    }

    public function auto_release_bonus_for_ole777idr_live_dealer_total_losses_weekly($dateFromStr = _COMMAND_LINE_NULL, $dateToStr = _COMMAND_LINE_NULL, $player_id = null, $promoCmsId = null, $dry_run = false){
        $this->utils->info_log('start auto_release_bonus_for_ole777idr_live_dealer_total_losses_weekly');

        $promocms_ids = $this->utils->getConfig('auto_apply_for_ole777idr_live_dealer_total_losses_weekly_bonus_promocms_id');
        $this->utils->debug_log('origin promo cms id and game type', $promocms_ids);

        if( ($dateFromStr != _COMMAND_LINE_NULL) && ($dateToStr != _COMMAND_LINE_NULL) ){
            $fromDateTime = $this->utils->formatDateForMysql(new DateTime($dateFromStr)) . ' ' . Utils::FIRST_TIME;
            $toDateTime = $this->utils->formatDateForMysql(new DateTime($dateToStr)) . ' ' . Utils::LAST_TIME;
        }else{
            list($fromDateTime, $toDateTime) = $this->utils->getTodayStringRange();
        }

        $this->utils->debug_log('from date to date', $fromDateTime, $toDateTime, $player_id);

        if(!empty($promocms_ids)){
            $this->load->model(['promorules', 'player_promo', 'users']);
            $adminId = $this->users->getSuperAdminId();
            $reason = 'Auto Approve By Cronjob';

            foreach (array_keys($promocms_ids) as $promo_cms_id){
                $promorule=$this->promorules->getPromoruleByPromoCms($promo_cms_id);
                $promorulesId = $promorule['promorulesId'];

                $player_promos = $this->player_promo->getNotRelasedPromoRequest($promorulesId, $promo_cms_id, $player_id, $fromDateTime, $toDateTime);
                $this->utils->debug_log('get not release player promos', $player_promos);

                if(!empty($player_promos)){
                    $succ = false;
                    $count = 0;
                    $success_apply_id = [];

                    foreach ($player_promos as $player_promos){
                        $playerId = $player_promos['playerId'];
                        $player_promo_id = $player_promos['playerpromoId'];

                        try{
                            $succ = $this->lockAndTransForPlayerBalance($playerId, function()
                            use($playerId, $promorule, $promo_cms_id, $adminId, $player_promo_id, $reason, $dry_run, &$extra_info){
                                $success = !!$this->promorules->approvePromo($playerId, $promorule, $promo_cms_id, $adminId, null, null, $player_promo_id, $extra_info, $reason, $dry_run);
                                return $success;
                            });

                            if($succ){
                                $count += 1;
                                $success_apply_id[] = $player_promo_id;
                            }

                        }catch(WrongBonusException $e){
                            $this->utils->error_log($e);
                        }
                    }

                    $this->utils->info_log('auto_release_bonus_for_ole777idr_live_dealer_total_losses_weekly result', $promo_cms_id, $success_apply_id);
                }else{
                    $this->utils->debug_log('empty player promo with this promo', $promo_cms_id);
                }
            }
        }
        $this->utils->info_log('end auto_release_bonus_for_ole777idr_live_dealer_total_losses_weekly');
    }

    public function auto_release_bonus_for_ole777th_consecutive_deposit_bonus($dateFromStr = _COMMAND_LINE_NULL, $dateToStr = _COMMAND_LINE_NULL, $player_id = null, $dry_run = false){
        $this->utils->info_log('start auto_release_bonus_for_ole777th_consecutive_deposit_bonus');

        $promo_cms_id = $this->utils->getConfig('auto_apply_and_release_bonus_for_ole777th_consecutive_deposit_bonus_promocms_id');
        $this->utils->debug_log('origin promo cms id', $promo_cms_id);

        if( ($dateFromStr != _COMMAND_LINE_NULL) && ($dateToStr != _COMMAND_LINE_NULL) ){
            $fromDateTime = $this->utils->formatDateForMysql(new DateTime($dateFromStr)) . ' ' . Utils::FIRST_TIME;
            $toDateTime = $this->utils->formatDateForMysql(new DateTime($dateToStr)) . ' ' . Utils::LAST_TIME;
        }else{
            list($fromDateTime, $toDateTime) = $this->utils->getTodayStringRange();
        }

        $this->utils->debug_log('from date to date', $fromDateTime, $toDateTime, $player_id);

        if(!empty($promo_cms_id)){
            $this->load->model(['promorules', 'player_promo', 'users']);
            $adminId = $this->users->getSuperAdminId();
            $reason = 'Auto Approve By Cronjob';

            $promorule=$this->promorules->getPromoruleByPromoCms($promo_cms_id);
            $promorulesId = $promorule['promorulesId'];

            $player_promos = $this->player_promo->getNotRelasedPromoRequest($promorulesId, $promo_cms_id, $player_id, $fromDateTime, $toDateTime);
            $this->utils->debug_log('get not release player promos', $player_promos);

            if(!empty($player_promos)){
                $succ = false;
                $count = 0;
                $success_apply_id = [];

                foreach ($player_promos as $player_promos){
                    $playerId = $player_promos['playerId'];
                    $player_promo_id = $player_promos['playerpromoId'];

                    try{
                        $succ = $this->lockAndTransForPlayerBalance($playerId, function()
                        use($playerId, $promorule, $promo_cms_id, $adminId, $player_promo_id, $reason, $dry_run, &$extra_info){
                            $success = !!$this->promorules->approvePromo($playerId, $promorule, $promo_cms_id, $adminId, null, null, $player_promo_id, $extra_info, $reason, $dry_run);
                            return $success;
                        });

                        if($succ){
                            $count += 1;
                            $success_apply_id[] = $player_promo_id;
                        }
                    }catch(WrongBonusException $e){
                        $this->utils->error_log($e);
                    }
                }

                $this->utils->info_log('auto_release_bonus_for_ole777th_consecutive_deposit_bonus result', $promo_cms_id, $success_apply_id);
            }else{
                $this->utils->debug_log('empty player promo with this promo', $promo_cms_id);
            }
        }
        $this->utils->info_log('end auto_release_bonus_for_ole777th_consecutive_deposit_bonus');
    }

    public function auto_apply_and_release_bonus_for_sssbet_friend_referral($player_id = null, $status=null, $dry_run = false){
        $promocms_id = $this->utils->getConfig('auto_apply_and_release_bonus_for_sssbet_friend_referral_promocms_id');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_sssbet_friend_referral promocms_id',$promocms_id);

        $count = 0;
        $success_apply_id = [];

        if (!empty($promocms_id)) {
            $this->load->library(['og_utility']);
            $this->load->model(['promorules','player_promo','player_model','player_friend_referral']);
            $adminId = $this->users->getSuperAdminId();
            $promorule = $this->promorules->getPromoruleByPromoCms($promocms_id);
            $player_id_of_friend_referral = $this->player_friend_referral->getPlayerReferral($player_id, $status , null, null, true);
            $player_id_of_friend_referral = $this->og_utility->array_pluck($player_id_of_friend_referral, 'playerId');

            $this->utils->info_log('players', $player_id_of_friend_referral);

            if (!empty($player_id_of_friend_referral) && is_array($player_id_of_friend_referral)) {
                foreach ($player_id_of_friend_referral as $playerId) {
                    try{
                        $msg=null;

                        $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $dry_run){
                                $success = true;
                                $extra_info = [];
                                list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, false, null, $extra_info, $dry_run);
                                return $success;
                            });

                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[] = $playerId;
                            }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promocms_id, $extra_info);

                    }catch(WrongBonusException $e){
                        $this->utils->error_log($e);
                    }
                }
            }else{
                $this->utils->info_log('No eligible players found',$players);
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_sssbet_friend_referral', $count, $success_apply_id);
    }

	public function list_customized_prom_rules() {
        $customized_promo_rules_path = realpath(dirname(__FILE__) . '/../../models/customized_promo_rules/');

        $list = array_map(function($entry) {
            return basename($entry, '.php');
        }, glob($customized_promo_rules_path . '/*.php'));

        $ignore_list = [
            'abstract_promo_rule',
            'promo_rule_abet_sportsresurrection_bonus',
            'promo_rule_abet_upgrade_level_bonus',
            'promo_rule_bigbet999_trial_bonus_om_lotto',
            'promo_rule_bigbet999_vip_deposit_bouns',
            'promo_rule_caishen_rescue',
            'promo_rule_common_redemption_code',
            'promo_rule_default',
            'promo_rule_default_probability',
            'promo_rule_dj002_3_weeks_cumulative',
            'promo_rule_dj002_bets_amount_monthly',
            'promo_rule_dj002_friend_referral',
            'promo_rule_entaplayth_birthday_bonus',
            'promo_rule_entaplayth_special_weekend_bouns',
            'promo_rule_entaplayth_upgrade_level_bonus',
            'promo_rule_kinggaming_regress_first_deposit_bonus',
            'promo_rule_kinggaming_signin_bonus',
            'promo_rule_lovebet_birthday_bonus',
            'promo_rule_lovebet_free_bonus_monthly',
            'promo_rule_ole777_aptitude_freespin_weekly',
            'promo_rule_ole777_bets_deposit_bonus',
            'promo_rule_ole777_birthday_bonus',
            'promo_rule_ole777_birthday_bonus_v2',
            'promo_rule_ole777cn_first_bind_usdt_bonus',
            'promo_rule_ole777cn_first_usdt_deposit_bonus',
            'promo_rule_ole777_deposit_bonus_everyday',
            'promo_rule_ole777_deposit_bonus_everyday_v2',
            'promo_rule_ole777_deposit_bonus_monthly',
            'promo_rule_ole777_deposit_bonus_monthly_v2',
            'promo_rule_ole777_deposit_bonus_on_deposit_day',
            'promo_rule_ole777_deposit_withdrawal_bonus',
            'promo_rule_ole777_free_bonus_monthly',
            'promo_rule_ole777_free_bonus_monthly_v2',
            'promo_rule_ole777id_r26255_roulette',
            'promo_rule_ole777id_r26256_roulette',
            'promo_rule_ole777idr_birthday_bonus',
            'promo_rule_ole777idr_deposit_welcome_bonus',
            'promo_rule_ole777idr_free_bonus_monthly',
            'promo_rule_ole777idr_free_bonus_monthly_v2',
            'promo_rule_ole777idr_free_bonus_yearly',
            'promo_rule_ole777idr_game_deposit_bonus',
            'promo_rule_ole777idr_jersey_giveaway_bonus',
            'promo_rule_ole777idr_mid_month_bonus',
            'promo_rule_ole777idr_total_losses_weekly_bonus',
            'promo_rule_ole777idr_vip_deposit_bonus',
            'promo_rule_ole777_january_bonus_yearly',
            'promo_rule_ole777th_consecutive_deposit_bonus_bimonthly',
            'promo_rule_ole777th_consecutive_deposit_bonus_everyday',
            'promo_rule_ole777th_deposit_bonus',
            'promo_rule_ole777th_deposit_weekly_bonus',
            'promo_rule_ole777th_r25318_roulette',
            'promo_rule_ole777th_r26755_roulette',
            'promo_rule_ole777th_r26756_roulette',
            'promo_rule_ole777_upgrade_level_bonus',
            'promo_rule_ole777_upgrade_level_bonus_v2',
            'promo_rule_ole777_verification_bonus',
            'promo_rule_ole777_vip_limit_free_bonus',
            'promo_rule_ole777vn_birthday_bonus',
            'promo_rule_ole777vn_deposit_bouns',
            'promo_rule_ole777vn_deposit_bouns_within_ndays',
            'promo_rule_ole777vnd_new_year_daily_bonus',
            'promo_rule_ole777vn_first_deposit_bouns',
            'promo_rule_ole777vn_independence_day_bonus',
            'promo_rule_ole777vn_lucky_bonus_monthly',
            'promo_rule_ole777vn_registration_bonus',
            'promo_rule_roulette_r27831n',
            'promo_rule_roulette_r27831s',
            'promo_rule_sambabet_bets_deposit_daily_bonus',
            'promo_rule_sambabet_new_player_first_second_deposit_bonus',
            'promo_rule_sambabet_sportsbet_bonus',
            'promo_rule_sambabet_sportsresurrection_bonus',
            'promo_rule_sambabet_upgrade_level_bonus',
            'promo_rule_sexycasino_free_spins_coupon',
            'promo_rule_smash_check_exist_deposit_bonus',
            'promo_rule_smash_checkin_bonus',
            'promo_rule_smash_checkin_bonus_v2',
            'promo_rule_smash_daily_battles_bonus',
            'promo_rule_smash_daily_eucharist_bonus',
            'promo_rule_smash_deposit_bonus_everyday',
            'promo_rule_smash_deposit_bonus_monthly',
            'promo_rule_smash_deposit_bonus_on_deposit_day',
            'promo_rule_smash_deposit_daily_bonus',
            'promo_rule_smash_deposit_daily_bonus_v2',
            'promo_rule_smash_first_deposit_free_bonus_everyday',
            'promo_rule_smash_fixed_deposit_amount_bonus',
            'promo_rule_smash_free_bonus_monthly',
            'promo_rule_smash_newbet_bonus',
            'promo_rule_smash_new_player_first_second_deposit_bonus',
            'promo_rule_smash_new_year_bonus',
            'promo_rule_smash_normal_roulette',
            'promo_rule_smash_redemption_code',
            'promo_rule_smash_registration_bonus',
            'promo_rule_smash_registration_free_spin_nova',
            'promo_rule_smash_registration_free_spin',
            'promo_rule_smash_sportsbet_bonus',
            'promo_rule_smash_sportsbet_v2_bonus',
            'promo_rule_smash_sportsresurrection_bonus',
            'promo_rule_smash_super_roulette',
            'promo_rule_smash_upgrade_level_bonus',
            'promo_rule_sssbet_rescue_bonus',
            'promo_rule_sssbet_upgrade_level_bonus_monthly',
            'promo_rule_sssbet_upgrade_level_bonus',
            'promo_rule_whatsbet_christmas_bonus',
            'promo_rule_whatsbet_signin_bouns',
            'promo_rule_whatsbet_vip_signin_bouns',
            'promo_rule_win102_deposit_weekly_bonus',
            'promo_rule_roulette_r28561',
            'promo_rule_roulette_r33827_usd',
            'promo_rule_roulette_r33827_php',
            'promo_rule_roulette_r33827_jpy',
            'promo_rule_sssbet_friend_referral',
            'promo_rule_t1bet_bet_bonus_weekly',
            'promo_rule_t1bet_deposit_weekly_bonus',
        ];

        $filtered_list = array_filter($list, function($entry) use ($ignore_list) {
            return !in_array($entry, $ignore_list);
        });

        $result = array_map(function($entry) {
            return [
                'name' => $entry,
                'title' => lang('promo.customized_php_promo.' . $entry)
            ];
        }, array_values($filtered_list));

		$ret = [ 'success' => false, 'mesg' => null, 'result' => null ];
		try {
			$ret['result'] = $result;
		}
		catch (Exception $ex) {
			$ret = ['success' => false, 'mesg' => $ex->getMessage(), 'result' => null ];
		}
		finally {
			$this->returnJsonResult($ret);
		}
	}

    public function customized_prom_rule_detail() {
        $class_name = $this->input->get_post('class_name');

		$ret = [ 'success' => false, 'mesg' => null, 'result' => null ];

        $obj = $this->utils->loadCustomizedPromoRuleObject($class_name);
        if(empty($obj)) {
            return $this->returnJsonResult($ret);
        }

        $ret['result'] = [
            'name' => $class_name,
            'title' => lang('promo.customized_php_promo.' . $class_name),
            'detail' => lang('promo.customized_php_promo_detail.' . $class_name),
            'schemas' => [
                'condition' => $obj->getConditionSchema(),
                'release' => $obj->getReleaseSchema()
            ]
        ];

        return $this->returnJsonResult($ret);
    }

     public function auto_apply_for_t1bet_total_losses_weekly($dateFromStr = _COMMAND_LINE_NULL, $dateToStr = _COMMAND_LINE_NULL, $player_id = null, $promoCmsId = null){
        $promocms_ids = $this->utils->getConfig('auto_apply_and_release_bonus_for_t1bet_total_losses_weekly_promocms_id');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_t1bet_total_losses_weekly promocms_ids',$promocms_ids);
        $count = 0;
        $success_apply_id = [];

        if (!empty($promocms_ids)) {
            $this->load->model(['promorules', 'player_promo', 'player_model']);

            if( ($dateFromStr != _COMMAND_LINE_NULL) && ($dateToStr != _COMMAND_LINE_NULL) ){
                $fromDate = $this->utils->formatDateForMysql(new DateTime($dateFromStr));
                $toDate = $this->utils->formatDateForMysql(new DateTime($dateToStr));
            }else{
                $lastMonday = date("Y-m-d", strtotime("last week monday"));
                list($fromDate, $toDate) = $this->utils->getFromToByWeek($lastMonday);
            }

            $this->utils->debug_log('from date to date', $fromDate, $toDate, $player_id);

            $this->utils->debug_log('origin promo cms id and game type', $promocms_ids);
            if(!empty($promoCmsId) && !empty($promocms_ids[$promoCmsId])){
                // only allow this promo cms check
                $gameTypeId = $promocms_ids[$promoCmsId];
                $promocms_ids = [$promoCmsId => $gameTypeId];
                $this->utils->info_log('use Specified promo cms id', $promoCmsId);
            }

            foreach ($promocms_ids as $promo_cms_id => $game_type_id) {
                $this->utils->info_log('promo cms id', $promo_cms_id);
                $promorule=$this->promorules->getPromoruleByPromoCms($promo_cms_id);
                $players = $this->player_model->getPlayerByTotalLossesWeeklyCustomizedConditions($fromDate, $toDate, $player_id, $game_type_id);

                if (!empty($players)) {
                    foreach ($players as $player) {
                        $playerId = $player['player_id'];
                        $registerIp = $player['registrationIp'];
                        $username = $player['username'];
                        $disabled_promotion = $player['disabled_promotion'];

                        if($disabled_promotion == 1){
                            $this->utils->debug_log('player disabled promotion', $playerId);
                            continue;
                        }

                        $test_player_list = $this->utils->getConfig('auto_apply_and_release_bonus_player_list');
                        if (!empty($test_player_list)) {
                            if (!in_array($username, $test_player_list)) {
                                continue;
                            }
                        }

                        try{
                            $msg=null;

                            $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $promo_cms_id, $playerId, &$msg, &$extra_info, &$res, $registerIp){

                                $success = true;
                                $preapplication=false;
                                $extra_info = [];
                                $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                                $extra_info['player_request_ip'] = $registerIp;

                                list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promo_cms_id, $preapplication, null, $extra_info);
                                return $success;
                            });

                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[] = $playerId;
                            }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promo_cms_id, $extra_info, $registerIp);

                        }catch(WrongBonusException $e){
                            $this->utils->error_log($e);
                        }
                    }
                }
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_t1bet_total_losses_weekly', $count, $success_apply_id);
    }

    /**
     * The interface between cronjon and _auto_apply_and_release_for_daily_bonus().
     *
     * @param [type] $dateFromStr @see self::_auto_apply_and_release_for_daily_bonus()
     * @param [type] $dateToStr @see self::_auto_apply_and_release_for_daily_bonus()
     * @param [type] $player_id @see self::_auto_apply_and_release_for_daily_bonus()
     * @param [type] $promoCmsId @see self::_auto_apply_and_release_for_daily_bonus()
     * @return void
     */
    public function auto_apply_and_release_bonus_for_t1t_common_brazil_daily_rescue( $dateFromStr = _COMMAND_LINE_NULL // #1
                                                                                    , $dateToStr = _COMMAND_LINE_NULL // #2
                                                                                    , $player_id = null // #3
                                                                                    , $promoCmsId = null // #4
    ){
        /// aka. $this->_auto_apply_and_release_for_daily_bonus().
        $args = func_get_args();
        // prepend "auto_apply_and_release_bonus_for_t1t_common_brazil_daily_rescue_promocms_id" for $config_name_in_promocms_id
        array_unshift($args, 'auto_apply_and_release_bonus_for_t1t_common_brazil_daily_rescue_promocms_id');
        return call_user_func_array([$this, '_auto_apply_and_release_for_daily_bonus'], $args);
    }// EOF auto_apply_and_release_bonus_for_t1t_common_brazil_daily_rescue
    /**
     * Call promorules::triggerPromotionFromCronjob() with turnover via data-table, total_player_game_day.
     *
     *  The related Config,
     * {code}
     * $config[{$config_name_in_promocms_id}] = [
     *     // promo cms id
     *     "12" => [
     *         // game types
     *         1166,1190,1214,1267,1289, //mini games
     *         1163,1169,1292 //slots
     *     ]
     * ];
     * {code}
     *
     * The sample:
     * {code}
     * $config[{$auto_apply_and_release_bonus_for_t1t_common_brazil_daily_rescue_promocms_id}] = [
     *     // promo cms id
     *     "17194" => [
     *         // game types
     *         '*'
     *     ]
     * ];
     * {code}
     *
     * @param string $config_name_in_promocms_id The config key string
     * @param string $dateFromStr YYYY-mm-dd HH:ii:ss, ex: 2022-10-02 00:00:00
     * @param string $dateToStr YYYY-mm-dd HH:ii:ss, ex: 2022-10-02 23:59:59
     * @param null|integer $player_id The Specified player id.
     * @param null|integer $promoCmsId The Specified promo Cms Id.
     * @return void
     */
    private function _auto_apply_and_release_for_daily_bonus( $config_name_in_promocms_id = 'auto_apply_and_release_bonus_for_t1bet_total_revenue_daily_promocms_id' // #1
                                                            , $dateFromStr = _COMMAND_LINE_NULL // #2
                                                            , $dateToStr = _COMMAND_LINE_NULL // #3
                                                            , $player_id = null // #4
                                                            , $promoCmsId = null // #5
    ){
        $promocms_ids = $this->utils->getConfig($config_name_in_promocms_id);
        $this->utils->info_log('start _auto_apply_and_release_for_daily_bonus config_name_in_promocms_id:',$config_name_in_promocms_id);
        $this->utils->info_log('start _auto_apply_and_release_for_daily_bonus promocms_ids:',$promocms_ids);
        $count = 0;
        $success_apply_id = [];
        if (!empty($promocms_ids)) {
            $this->load->model(['promorules', 'player_promo', 'player_model']);

            /// for $fromDate and $toDate.
            // default used
            $current_date = $this->utils->getTodayForMysql();
            $yesterday_date = $this->utils->getLastDay($current_date);
            // get whole day yesterday
            $fromDate = $yesterday_date .' ' . '00:00:00';
            $toDate = $yesterday_date .' ' .  '23:59:59';

            /// for $fromDate and $toDate.
            // reference to params of CMD
            if( ($dateFromStr != _COMMAND_LINE_NULL) && ($dateToStr != _COMMAND_LINE_NULL) ){
                $fromDate = $this->utils->formatDateTimeForMysql(new DateTime($dateFromStr));
                $toDate = $this->utils->formatDateTimeForMysql(new DateTime($dateToStr));
            }

            $this->utils->debug_log('755.origin promo cms id and game type', $promocms_ids);
            if(!empty($promoCmsId) && !empty($promocms_ids[$promoCmsId])){
                // only allow this promo cms check
                $gameTypeId = $promocms_ids[$promoCmsId];
                $promocms_ids = [$promoCmsId => $gameTypeId];
                $this->utils->info_log('use Specified promo cms id', $promoCmsId);
            }

            foreach ($promocms_ids as $promo_cms_id => $game_type_id) {
                $this->utils->info_log('764.promo cms id', $promo_cms_id);
                $promorule=$this->promorules->getPromoruleByPromoCms($promo_cms_id);

                $promoruleWithFormulas=$this->promorules->getPromoDetailsWithFormulas($promorule['promorulesId']);
                $this->utils->info_log('777.promorule', $promorule);
                $this->utils->info_log('778.promoruleWithFormulas', $promoruleWithFormulas);

                if( !empty($promoruleWithFormulas['formula']['bonus_release']) ){
                    $formula4bonus_release = $promoruleWithFormulas['formula']['bonus_release'];
                    $to_skip_this_promo = null;
                    if(! empty($formula4bonus_release['triggered']['cron']) ){
                        $cron = $formula4bonus_release['triggered']['cron'];
                        $cronExp=Cron\CronExpression::factory($cron);
                        if( ! $cronExp->isDue() ){
                            $this->utils->info_log('OGP-30956.will Skip this promo cms id', $promo_cms_id, 'by isDue=false, the cron', $cron);
                            $to_skip_this_promo = true;
                        }else{
                            // do this round.
                            $this->utils->info_log('OGP-30956.Do this promo cms id', $promo_cms_id, ' the cron', $cron);
                            $to_skip_this_promo = false;
                        }
                    }else{
                        // when Not defined cron in bonus_release
                        // TODO, Not yet happened
                        $this->utils->info_log('OGP-30956.Undefined the bonus_release.cron in this promo cms id', $promo_cms_id);
                        $to_skip_this_promo = true;
                    }
                }else{
                    // empty formula in bonus_release
                    $this->utils->info_log('OGP-30956.Undefined the cron in this promo cms id', $promo_cms_id, ' with empty formula in bonus_release');
                    $to_skip_this_promo = true;
                }
                //
                if( ! empty($to_skip_this_promo) ){
                    $this->utils->info_log('OGP-30956 Will skip this promo cms id', $promo_cms_id
                    , ' the cron', empty($cron)? null: $cron
                    , 'bonus_release.formula:', empty($formula4bonus_release)? null: $formula4bonus_release );

                    continue; // skip this round.
                }

                /// for $fromDate and $toDate.
                // reference to $formula4bonus_release['allowed_date']['start'] and  $formula4bonus_release['allowed_date']['end']
                if( ! empty($formula4bonus_release['allowed_date']['start']) ){
                    $fromDate = $this->utils->formatDateTimeForMysql(new DateTime($formula4bonus_release['allowed_date']['start']));
                }
                if( ! empty($formula4bonus_release['allowed_date']['end']) ){
                    $toDate = $this->utils->formatDateTimeForMysql(new DateTime($formula4bonus_release['allowed_date']['end']));
                }
                $this->utils->info_log('OGP-30956 formula4bonus_release.allowed_date', $formula4bonus_release['allowed_date']);

                $this->utils->debug_log('from date to date', $fromDate, $toDate, $player_id);

                $players = $this->player_model->getPlayerByTotalLossesWeeklyCustomizedConditions($fromDate, $toDate, $player_id, $game_type_id);
                $this->utils->info_log('766.players.count:', empty($players)? 0: count($players) );
                if (!empty($players)) {
                    foreach ($players as $player) {
                        $playerId = $player['player_id'];
                        $registerIp = $player['registrationIp'];
                        $username = $player['username'];
                        $disabled_promotion = $player['disabled_promotion'];

                        if($disabled_promotion == 1){
                            $this->utils->debug_log('player disabled promotion', $playerId);
                            continue;
                        }

                        /// When auto_apply_and_release_bonus_player_list is Not empty,
                        // than process the Specific player list only.
                        $test_player_list = $this->utils->getConfig('auto_apply_and_release_bonus_player_list');
                        if (!empty($test_player_list)) {
                            if (!in_array($username, $test_player_list)) {
                                continue;
                            }
                        }

                        try{
                            $msg=null;

                            $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $promo_cms_id, $playerId, &$msg, &$extra_info, &$res, $registerIp){

                                $success = true;
                                $preapplication=false;
                                $extra_info = [];
                                $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                                $extra_info['player_request_ip'] = $registerIp;

                                list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promo_cms_id, $preapplication, null, $extra_info);
                                return $success;
                            });

                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[] = $playerId;
                            }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promo_cms_id, $extra_info, $registerIp);

                        }catch(WrongBonusException $e){
                            $this->utils->error_log($e);
                        }
                    }
                }
            }
        }
        $this->utils->info_log('end _auto_apply_and_release_for_daily_bonus.config_name_in_promocms_id:', $config_name_in_promocms_id, 'count:', $count, 'success_apply_id:', $success_apply_id);

    } // EOF _auto_apply_and_release_for_daily_bonus

    public function auto_apply_and_release_bonus_for_t1bet_total_revenue_daily_bonus($dateFromStr = _COMMAND_LINE_NULL, $dateToStr = _COMMAND_LINE_NULL, $player_id = null, $promoCmsId = null){
        $promocms_ids = $this->utils->getConfig('auto_apply_and_release_bonus_for_t1bet_total_revenue_daily_promocms_id');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_t1bet_total_revenue_daily_bonus promocms_ids',$promocms_ids);
        $count = 0;
        $success_apply_id = [];

        if (!empty($promocms_ids)) {
            $this->load->model(['promorules', 'player_promo', 'player_model']);

            if( ($dateFromStr != _COMMAND_LINE_NULL) && ($dateToStr != _COMMAND_LINE_NULL) ){
                $fromDate = $this->utils->formatDateForMysql(new DateTime($dateFromStr));
                $toDate = $this->utils->formatDateForMysql(new DateTime($dateToStr));
            }else{
                $current_date = $this->utils->getTodayForMysql();
                $yesterday_date = $this->utils->getLastDay($current_date);

                // get whole day yesterday
                $fromDate = $yesterday_date .' ' . '00:00:00';
                $toDate = $yesterday_date .' ' .  '23:59:59';
            }

            $this->utils->debug_log('from date to date', $fromDate, $toDate, $player_id);

            $this->utils->debug_log('origin promo cms id and game type', $promocms_ids);
            if(!empty($promoCmsId) && !empty($promocms_ids[$promoCmsId])){
                // only allow this promo cms check
                $gameTypeId = $promocms_ids[$promoCmsId];
                $promocms_ids = [$promoCmsId => $gameTypeId];
                $this->utils->info_log('use Specified promo cms id', $promoCmsId);
            }

            foreach ($promocms_ids as $promo_cms_id => $game_type_id) {
                $this->utils->info_log('promo cms id', $promo_cms_id);
                $promorule=$this->promorules->getPromoruleByPromoCms($promo_cms_id);
                $players = $this->player_model->getPlayerByTotalLossesWeeklyCustomizedConditions($fromDate, $toDate, $player_id, $game_type_id);

                if (!empty($players)) {
                    foreach ($players as $player) {
                        $playerId = $player['player_id'];
                        $registerIp = $player['registrationIp'];
                        $username = $player['username'];
                        $disabled_promotion = $player['disabled_promotion'];

                        if($disabled_promotion == 1){
                            $this->utils->debug_log('player disabled promotion', $playerId);
                            continue;
                        }

                        $test_player_list = $this->utils->getConfig('auto_apply_and_release_bonus_player_list');
                        if (!empty($test_player_list)) {
                            if (!in_array($username, $test_player_list)) {
                                continue;
                            }
                        }

                        try{
                            $msg=null;

                            $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $promo_cms_id, $playerId, &$msg, &$extra_info, &$res, $registerIp){

                                $success = true;
                                $preapplication=false;
                                $extra_info = [];
                                $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                                $extra_info['player_request_ip'] = $registerIp;

                                list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promo_cms_id, $preapplication, null, $extra_info);
                                return $success;
                            });

                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[] = $playerId;
                            }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promo_cms_id, $extra_info, $registerIp);

                        }catch(WrongBonusException $e){
                            $this->utils->error_log($e);
                        }
                    }
                }
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_t1bet_total_revenue_daily_bonus', $count, $success_apply_id);
    }    
    
    public function auto_apply_and_release_bonus_for_t1t_common_brazil_referral_daily_bonus($currentDateStr = _COMMAND_LINE_NULL, $betDateFromStr = _COMMAND_LINE_NULL, $betDateToStr = _COMMAND_LINE_NULL, $player_id = _COMMAND_LINE_NULL){
        $promocms_id = $this->utils->getConfig('auto_apply_and_release_bonus_for_t1t_common_brazil_referral_daily_bonus_promocms_id');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_t1t_common_brazil_referral_daily_bonus promocms_id',$promocms_id);

        $count = 0;
        $success_apply_id = [];
        $isPayTime = false;

        $current_date = $this->utils->getTodayForMysql();
        $current_hour = $this->utils->getHourOnlyForMysql();
        $today = $current_date;
        if($currentDateStr != _COMMAND_LINE_NULL){
            $current_date = $this->utils->formatDateForMysql(new DateTime($currentDateStr));
            $current_hour = $this->utils->formatHourOnlyForMysql(new DateTime($currentDateStr));
        }

        if( ($betDateFromStr != _COMMAND_LINE_NULL) && ($betDateToStr != _COMMAND_LINE_NULL) ){
            $betting_start = $this->utils->formatDateForMysql(new DateTime($betDateFromStr));
            $betting_end = $this->utils->formatDateForMysql(new DateTime($betDateToStr));
        }else{
            // OGP-32798: 投注記錄從每日計算的時間改為每小時計算
            $date_time = new DateTime();
            $date_time->modify('-1 hour');
            $date_time_hour = $date_time->format('Y-m-d H');
            $betting_start = $date_time_hour . ':00:00';
            $betting_end = $date_time_hour . ':59:59';
        }
        
        if($player_id == _COMMAND_LINE_NULL){
            $player_id = null;
        }

        if(!empty($promocms_id)){
            $this->load->model(['promorules','player_promo','player_model','player_friend_referral', 'total_player_game_day', 'users', 'queue_result']);
            
            $ip = $this->utils->getIP();
            $adminId = $this->users->getSuperAdminId();
            $promorule=$this->promorules->getPromoruleByPromoCms($promocms_id);
            $promorulesId = $promorule['promorulesId'];
            
            $PromoDetail = $this->promorules->getPromoDetailsWithFormulas($promorulesId);
            $bonus_condition = !empty($PromoDetail['formula']['bonus_condition']) ? $PromoDetail['formula']['bonus_condition'] : null;
            
            if(empty($bonus_condition)){
                $this->utils->info_log('empty bonus condition', $promocms_id);
                return;
            }
            
            $description = $bonus_condition;
            $game_platform_id = !empty($description['game_platform_id']) ? $description['game_platform_id'] : null;
            $game_type_id = !empty($description['game_type_id']) ? $description['game_type_id'] : null;
            $referral_start = !empty($description['referral_date']['start']) ? $description['referral_date']['start'] : null;
            $referral_end = !empty($description['referral_date']['end']) ? $description['referral_date']['end'] : null;

            if(!empty($description['betting_date']['start']) && !empty($description['betting_date']['end'])){
                $betting_start = $description['betting_date']['start'];
                $betting_end = $description['betting_date']['end'];
            }
                        
            $this->utils->debug_log('current_date', $current_date, 'current_hour', $current_hour, 'referral start', $referral_start, 'referral end', $referral_end, 'bet start', $betting_start, 'bet end', $betting_end, 'player_id', $player_id);

            $systemId = Queue_result::SYSTEM_UNKNOWN;
            $funcName = __FUNCTION__;
            $params = [
                'current_date' => $current_date,
                'current_hour' => $current_hour,
                'referral_start' => $referral_start,
                'referral_end' => $referral_end,
                'bet_start' => $betting_start,
                'bet_end' => $betting_end,
                'player_id' => $player_id
            ];

			$callerType = Queue_result::CALLER_TYPE_SYSTEM;
            $caller = 0;
			$state = null;
            $token = $this->queue_result->newResult($systemId, $funcName, $params, $callerType, $caller, $state);

            // $friend_referral_level_list = $this->player_friend_referral->getFriendReferralLevelIdByDate($current_date); // recalculate need this
            $referrer_cashback_info = $this->player_friend_referral->getPlayerReferralLevelList($referral_start, $referral_end, $betting_start, $betting_end, $game_platform_id, $game_type_id);
            
            if(!empty($token)){
                $this->queue_result->appendResult($token, ['request_id'=>_REQUEST_ID, 'func'=>$funcName, 'dateTimeFromStr'=>$this->utils->getNowForMysql()]);
            }

            if(!empty($player_id)){
                if(!empty($referrer_cashback_info[$player_id])){
                    $player_cashback_info[$player_id] = $referrer_cashback_info[$player_id];
                    $referrer_cashback_info = [ $player_id => $player_cashback_info[$player_id] ];
                    $this->utils->debug_log('only run specify referrer', $player_id);
                }else{
                    $this->utils->debug_log('not found specify referrer', $player_id);
                    return;
                }
            }

            $this->utils->info_log('referrer cashback info', $referrer_cashback_info);

            /*
            if(!empty($description['pay_hour'])){
                $pay_hour = $description['pay_hour'];
                if($current_hour == $pay_hour){
                    $isPayTime = true;
                }
            }

            $this->utils->info_log('current hour', $current_hour, 'pay hour', $pay_hour, 'is pay time', $isPayTime);

            if($isPayTime){
                $this->utils->info_log('start release_bonus_for_t1t_common_brazil_referral_daily_bonus');
                $adminId = $this->users->getSuperAdminId();
                $reason = 'Auto Approve By Cronjob';
                $fromDateTime = $current_date . ' ' . Utils::FIRST_TIME;
                $toDateTime = $current_date . ' ' . Utils::LAST_TIME;
                $this->utils->debug_log('from date to date', $fromDateTime, $toDateTime, $player_id);
                
                $player_promos = $this->player_promo->getNotRelasedPromoRequest($promorulesId, $promocms_id, $player_id, $fromDateTime, $toDateTime);
                $this->utils->debug_log('get not release player promos', $player_promos);
                
                if(!empty($player_promos)){
                    $count = 0;
                    $success_apply_id = [];
                    
                    foreach ($player_promos as $player_promos){
                        $playerId = $player_promos['playerId'];
                        $player_promo_id = $player_promos['playerpromoId'];
                        
                        try{
                            $succ = $this->lockAndTransForPlayerBalance($playerId, function()
                            use($playerId, $promorule, $promocms_id, $adminId, $player_promo_id, $reason, &$extra_info){
                                $success = !!$this->promorules->approvePromo($playerId, $promorule, $promocms_id, $adminId, null, null, $player_promo_id, $extra_info, $reason);
                                return $success;
                            });
                            
                            if($succ){
                                $count += 1;
                                $success_apply_id[] = $player_promo_id;
                            }
                            
                        }catch(WrongBonusException $e){
                            $this->utils->error_log($e);
                        }
                    }                    
                    $this->utils->info_log('release result', $promocms_id, $success_apply_id);
                }else{
                    $this->utils->debug_log('empty player promo with this promo', $promocms_id);
                }
                
                $this->utils->info_log('end release_bonus_for_t1t_common_brazil_referral_daily_bonus');
                return;
            }
            */

            $require_cashback_count = 0;
            $actual_execute_count = 0;
            $ignore_execute_count = 0;
            $not_active_referrer = [];
            $failed_apply_count = 0;
            if(!empty($referrer_cashback_info)){
                foreach ($referrer_cashback_info as $referrer_player_id => $referrer_cashback) {
                    $playerId = $referrer_player_id;

                    $total_cashback_count = count($referrer_cashback);
                    $require_cashback_count += $total_cashback_count;

                    $playerStatus = $this->utils->getPlayerStatus($playerId);
                    if($playerStatus != 0){
                        if(!isset($not_active_referrer[$playerId])){
                            $not_active_referrer[$playerId] = null;
                            $ignore_execute_count+=$total_cashback_count;
                        }
                        // add count to not active player
                        $this->utils->debug_log('ignore in cronjob, cause referrer player status not active', $playerStatus, $playerId);
                        continue;
                    }

                    foreach ($referrer_cashback as $cashback_info_row) {
                        $actual_execute_count+=1;
                        try{
                            $msg = null;
                            $isRecalculate = false;
                            $succ = $this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $cashback_info_row, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $ip, $current_date, $adminId){
                            // use($promorule, $cashback_info_row, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $ip, $current_date, $today, $isPayTime, $friend_referral_level_list, $adminId){
                                    $triggerRequestPromo = false;

                                    /*
                                    $isRecalculate = false;
                                    $uniqueId = sprintf("%s_%s_%s", $current_date, $cashback_info_row['referral_id'], $cashback_info_row['last_referral_id']);
                                    if(!empty($friend_referral_level_list[$uniqueId])){
                                        $this->utils->debug_log('start recalculte friend referral level list');
                                        if($current_date === $today){
                                            $isRecalculate = true;
                                            $player_promo_id = !empty($friend_referral_level_list[$uniqueId]['player_promo_id']) ? $friend_referral_level_list[$uniqueId]['player_promo_id'] : null;
                                            
                                            if(!empty($player_promo_id)){
                                                //declined promo request if exist
                                                $playerPromoStatus = $this->player_promo->getPlayerPromoStatusById($player_promo_id);
                                                $allow_decline_player_promo = in_array($playerPromoStatus, [PLAYER_PROMO::TRANS_STATUS_REQUEST, 
                                                Player_promo::TRANS_STATUS_MANUAL_REQUEST_APPROVED_WITHOUT_RELEASE_BONUS,
                                                Player_promo::TRANS_STATUS_APPROVED_WITHOUT_RELEASE_BONUS]);
                                                if($allow_decline_player_promo){
                                                    $reason = 'Auto Decline By Cronjob';
                                                    $this->promorules->declinePromo($playerId, $promorule, $promocms_id, $adminId, null, null, null, null, $reason, $player_promo_id);

                                                    // clear origin referral id
                                                    $this->player_promo->updatePlayerPromo($player_promo_id, ['referralId' => null]);
                                                }
                                            }
                                        }
                                        $this->utils->debug_log('end recalculte friend referral level list');
                                    }
                                    */

                                    $success = true;
                                    $preapplication=false;
                                    $extra_info = [];
                                    $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                                    $extra_info['player_request_ip'] = $ip;
                                    $extra_info['referrer_cashback'] = $cashback_info_row;
                                    $extra_info['triggerCronjobEvent'] = true;
                                    $extra_info['release_date'] = $current_date;
                                    /*
                                    if($isRecalculate){
                                        $extra_info['isRecalculate'] = $isRecalculate;
                                    }
                                    */

                                    list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, false, null, $extra_info);
                                    return $success;
                                }
                            );
    
                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[] = $playerId;
                            }else{
                                // cause bonus is < 0.005
                                $failed_apply_count+=1;
                            }
                            $this->utils->info_log('apply promo result on order:', $succ, $res, $msg);
    
                        }catch(WrongBonusException $e){
                            $this->utils->error_log($e);
                        }
                    }
                }
            }else{
                $this->utils->info_log('No eligible players found');
            }
        }

        $count_info = [
            'require cashback count' => $require_cashback_count,
            'not active referrer' => $not_active_referrer,
            'ignore execute with inactive referrer count' => $ignore_execute_count,
            'actual execute count' => $actual_execute_count,
            'actual execute success count' => $count,
            'actual executefailed count' => $failed_apply_count
        ];
        $this->utils->debug_log('count info', $count_info);

        if(!empty($token)){
            $success = !empty($count);
            $errMsg = $success ? null : 'No eligible players found';
            $result = ['request_id'=>_REQUEST_ID, 'func'=>$funcName, 'dateTimeToStr'=>$this->utils->getNowForMysql(), 'success'=>$success, 'info'=>$count_info, 'errorMsg' => $errMsg];
            $this->queue_result->appendResult($token, $result, true, false);
		}
        $this->utils->info_log('end auto_apply_and_release_bonus_for_t1t_common_brazil_referral_daily_bonus', $count, $success_apply_id);
    }

    public function auto_apply_and_release_bonus_for_king_referral_daily_bonus($player_id = _COMMAND_LINE_NULL, $dry_run = false){
        $promocms_id = $this->utils->getConfig('auto_apply_and_release_bonus_for_king_referral_daily_bonus_promocms_id');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_king_referral_daily_bonus promocms_id',$promocms_id);

        $count = 0;
        $success_apply_id = [];

        if($player_id == _COMMAND_LINE_NULL){
            $player_id = null;
        }
        $dry_run=$dry_run=='true';
        $this->utils->debug_log($player_id, 'dry_run', $dry_run);

        if(!empty($promocms_id)){
            $this->load->model(['promorules','player_promo','player_friend_referral', 'users']);

            $ip = $this->utils->getIP();
            $adminId = $this->users->getSuperAdminId();
            $promorule=$this->promorules->getPromoruleByPromoCms($promocms_id);
            $promorulesId = $promorule['promorulesId'];

            $PromoDetail = $this->promorules->getPromoDetailsWithFormulas($promorulesId);
            $bonus_condition = !empty($PromoDetail['formula']['bonus_condition']) ? $PromoDetail['formula']['bonus_condition'] : null;

            if(empty($bonus_condition)){
                $this->utils->info_log('empty bonus condition', $promocms_id);
                return;
            }

            $description = $bonus_condition;
            $promo_class = $description['class'];
            $allowed_date = isset($description['allowed_date'])? $description['allowed_date'] : null;
            if(!empty($allowed_date['start']) && !empty($allowed_date['end'])){
                $currentDate = $this->utils->getTodayForMysql();
                $minDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['start']));
                $maxDate = $this->utils->formatDateForMysql(new DateTime($allowed_date['end']));
                $this->utils->debug_log('allowed_date', ['currentDate'=>$currentDate, 'minDate'=>$minDate, 'maxDate'=>$maxDate]);
                if( $currentDate < $minDate || $currentDate > $maxDate ){
                    $this->utils->debug_log('Not right date');
                    return;
                }
            }

            $settings = [
                'promorulesId' => $promorulesId,
                'referral_start' => !empty($description['referral_date']['start']) ? $description['referral_date']['start'] : null,
                'referral_end' => !empty($description['referral_date']['end']) ? $description['referral_date']['end'] : null,
                'deposit_start' => !empty($description['deposit_date']['start']) ? $description['deposit_date']['start'] : null,
                'deposit_end' => !empty($description['deposit_date']['end']) ? $description['deposit_date']['end'] : null,
                'betting_start' => !empty($description['betting_date']['start']) ? $description['betting_date']['start'] : null,
                'betting_end' => !empty($description['betting_date']['end']) ? $description['betting_date']['end'] : null,
                'gameTypeId' => !empty($description['game_type']) ? $description['game_type'] : null,
                'gamePlatformId' => !empty($description['game_platform']) ? $description['game_platform'] : null,
                'min_deposit_cnt' => !empty($description['min_deposit_cnt']) ? $description['min_deposit_cnt'] : 3,
                'min_bet' => !empty($description['min_bet']) ? $description['min_bet'] : 100
            ];

            $this->utils->debug_log('custom settings', $settings);

            $referrer_cashback_info = $this->player_friend_referral->getReferralListByCustomPromo($promo_class, $player_id, $settings);
            if(empty($referrer_cashback_info)){
                $this->utils->info_log('empty referral cashback info', $promocms_id);
                return;
            }
            $this->utils->info_log('referrer cashback info', $referrer_cashback_info);

            if(!empty($referrer_cashback_info)){
                foreach ($referrer_cashback_info as $referrer_player_id => $referrer_cashback) {
                    $playerId = $referrer_player_id;

                    $playerStatus = $this->utils->getPlayerStatus($playerId);
                    if($playerStatus != 0){
                        $this->utils->debug_log('ignore in cronjob, cause referrer player status not active', $playerStatus, $playerId);
                        continue;
                    }

                    foreach ($referrer_cashback as $cashback_info_row) {
                        try{
                            $msg = null;
                            $succ = $this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $cashback_info_row, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $ip, $adminId, $dry_run){
                                    $success = true;
                                    $preapplication=false;
                                    $extra_info = [];
                                    $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                                    $extra_info['player_request_ip'] = $ip;
                                    $extra_info['referrer_cashback'] = $cashback_info_row;
                                    $extra_info['triggerCronjobEvent'] = true;

                                    list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, false, null, $extra_info, $dry_run);
                                    return $success;
                                }
                            );

                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[$playerId][] = $cashback_info_row['referralId'];
                            }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:', $playerId, $succ, $res, $msg);
                        }catch(WrongBonusException $e){
                            $this->utils->error_log($e);
                        }
                    }
                }
            }else{
                $this->utils->info_log('No eligible players found');
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_king_referral_daily_bonus', $count, $success_apply_id);
    }

    public function auto_apply_and_release_bonus_for_ole777th_friend_referral($player_id = null, $status=null, $dry_run = false){
        $promocms_id = $this->utils->getConfig('auto_apply_and_release_bonus_for_ole777th_friend_referral_promocms_id');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_ole777th_friend_referral promocms_id',$promocms_id);

        $count = 0;
        $success_apply_id = [];

        if (!empty($promocms_id)) {
            $this->load->library(['og_utility']);
            $this->load->model(['promorules','player_promo','player_model','player_friend_referral']);
            $adminId = $this->users->getSuperAdminId();
            $promorule = $this->promorules->getPromoruleByPromoCms($promocms_id);
            $player_id_of_friend_referral = $this->player_friend_referral->getPlayerReferral($player_id, $status , null, null, true);
            $player_id_of_friend_referral = $this->og_utility->array_pluck($player_id_of_friend_referral, 'playerId');

            $this->utils->info_log('players', $player_id_of_friend_referral);

            if (!empty($player_id_of_friend_referral) && is_array($player_id_of_friend_referral)) {
                foreach ($player_id_of_friend_referral as $playerId) {
                    try{
                        $msg=null;

                        $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $dry_run){
                                $success = true;
                                $extra_info = [];
                                list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, false, null, $extra_info, $dry_run);
                                return $success;
                            });

                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[] = $playerId;
                            }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promocms_id, $extra_info);

                    }catch(WrongBonusException $e){
                        $this->utils->error_log($e);
                    }
                }
            }else{
                $this->utils->info_log('No eligible players found',$players);
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_ole777th_friend_referral', $count, $success_apply_id);
    }

    public function auto_apply_and_release_bonus_for_t1bet_weekly_deposit_bonus($dateFromStr = _COMMAND_LINE_NULL, $dateToStr = _COMMAND_LINE_NULL, $player_id = _COMMAND_LINE_NULL, $ignore_trans_for_test = false, $periodMinDeposit = 500, $is_dry_run = false){
        $promocms_id = $this->utils->getConfig('auto_apply_and_release_bonus_for_t1bet_weekly_deposit_bonus_promocms_id');
        $this->utils->info_log('start auto_apply_bonus_for_t1bet_weekly_deposit_bonus promocms_id',$promocms_id);

        $count = 0;
        $success_apply_id = [];

        if (!empty($promocms_id)) {
            $this->load->model(['promorules','player_promo','player_model']);

            $ip = $this->utils->getIP();
            $promorule=$this->promorules->getPromoruleByPromoCms($promocms_id);

            if( ($dateFromStr != _COMMAND_LINE_NULL) && ($dateToStr != _COMMAND_LINE_NULL) ){
                $fromDate = $this->utils->formatDateForMysql(new DateTime($dateFromStr));
                $toDate = $this->utils->formatDateForMysql(new DateTime($dateToStr));
            }else{
                $lastMonday = date("Y-m-d", strtotime("last week monday"));
                list($fromDate, $toDate) = $this->utils->getFromToByWeek($lastMonday);
            }

            $is_dry_run=$is_dry_run=='true';
            $ignore_trans_for_test=$ignore_trans_for_test=='true';
            if($player_id == _COMMAND_LINE_NULL){
                $player_id = null;
            }

            $this->utils->info_log('from date AND to date', $fromDate, $toDate, 'is_dry_run', $is_dry_run, 'player', $player_id,
                'ignore_trans_for_test', $ignore_trans_for_test, 'periodMinDeposit', $periodMinDeposit);

            $players = $this->player_model->getDepositPlayersByOle777thConsecutiveDepositBonus($fromDate, $toDate, $player_id, $ignore_trans_for_test, $periodMinDeposit);
            $this->utils->info_log('get all deposit players', count($players), $players);

            // exit;
            if (!empty($players)) {
                foreach ($players as $player) {
                    $playerId = $player['playerId'];
                    $username = $player['username'];

                    $test_player_list = $this->utils->getConfig('auto_apply_and_release_bonus_player_list');
                    if (!empty($test_player_list)) {
                        if (!in_array($username, $test_player_list)) {
                            continue;
                        }
                    }

                    try{
                        $msg=null;

                        $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                        use($promorule, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $ip, $is_dry_run){

                            $success = true;
                            $preapplication=false;
                            $extra_info = [];
                            $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                            $extra_info['player_request_ip'] = $ip;

                            list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, $preapplication, null, $extra_info, $is_dry_run);
                            return $success;
                        });

                        if($succ && $res){
                            $count += 1;
                            $success_apply_id[] = $playerId;
                        }
                        $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promocms_id, $extra_info, $ip);

                    }catch(WrongBonusException $e){
                        $this->utils->error_log($e);
                    }
                }
            }else{
                $this->utils->info_log('No eligible players found');
            }
        }
        $this->utils->info_log('end auto_apply_bonus_for_t1bet_weekly_deposit_bonus', $count, $success_apply_id);
    }

    public function auto_apply_and_release_bonus_for_t1t_common_brazil_birthday_bonus($playerId = _COMMAND_LINE_NULL, $month = _COMMAND_LINE_NULL, $day = _COMMAND_LINE_NULL, $dry_run = false){
        $promocms_id = $this->utils->getConfig('auto_apply_and_release_bonus_for_t1t_common_brazil_birthday_bonus');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_t1t_common_brazil_birthday_bonus promocms_id',$promocms_id);

        $count = 0;
        $success_apply_id = [];

        if (!empty($promocms_id)) {
            $this->load->model(['promorules','player_promo','player_model','player_friend_referral']);
            $adminId = $this->users->getSuperAdminId();
            $promorule = $this->promorules->getPromoruleByPromoCms($promocms_id);
            if( ($month == _COMMAND_LINE_NULL) && ($day == _COMMAND_LINE_NULL) ){
                $thisMonthDay = $this->utils->getTodayForMysql();
                $date = new DateTime($thisMonthDay);
                $currentMonth = $date->format('m');
                $currentDay = $date->format('d');
            }else{
                $currentMonth = $month;
                $currentDay = $day;
            }

            if($playerId == _COMMAND_LINE_NULL){
                $playerId = null;
            }

            $birthdayPlayers = $this->player_model->getBitrhdayPlayers($currentMonth, $currentDay, $playerId);
            $this->utils->printLastSQL();
            $this->utils->info_log('date condition: ', 'month', $currentMonth, 'day', $currentDay, 'players', $birthdayPlayers);
            $this->utils->info_log('get all birthday players', count($birthdayPlayers), $birthdayPlayers);

            if (!empty($birthdayPlayers) && is_array($birthdayPlayers)) {
                foreach ($birthdayPlayers as $player) {
                    try{
                        $playerId = $player['playerId'];
                        $msg=null;

                        $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $dry_run){
                                $success = true;
                                $extra_info = [];
                                list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, false, null, $extra_info, $dry_run);
                                return $success;
                            });

                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[] = $playerId;
                            }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promocms_id, $extra_info);

                    }catch(WrongBonusException $e){
                        $this->utils->error_log($e);
                    }
                }
            }else{
                $this->utils->info_log('No eligible players found',$birthdayPlayers);
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_t1t_common_brazil_birthday_bonus', $count, $success_apply_id);
    }

    public function auto_apply_and_release_bonus_for_t1bet_referral_program($player_id = null, $status=null, $dry_run = false){
        $promocms_id = $this->utils->getConfig('auto_apply_and_release_bonus_for_t1bet_referral_program_promocms_id');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_t1bet_referral_program promocms_id',$promocms_id);

        $count = 0;
        $success_apply_id = [];

        if (!empty($promocms_id)) {
            $this->load->library(['og_utility']);
            $this->load->model(['promorules','player_promo','player_model','player_friend_referral']);
            $adminId = $this->users->getSuperAdminId();
            $promorule = $this->promorules->getPromoruleByPromoCms($promocms_id);
            $player_id_of_friend_referral = $this->player_friend_referral->getPlayerReferral($player_id, $status , null, null, true);
            $player_id_of_friend_referral = $this->og_utility->array_pluck($player_id_of_friend_referral, 'playerId');

            $this->utils->info_log('players', $player_id_of_friend_referral);

            if (!empty($player_id_of_friend_referral) && is_array($player_id_of_friend_referral)) {
                foreach ($player_id_of_friend_referral as $playerId) {
                    try{
                        $msg=null;

                        $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $dry_run){
                                $success = true;
                                $extra_info = [];
                                list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, false, null, $extra_info, $dry_run);
                                return $success;
                            });

                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[] = $playerId;
                            }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promocms_id, $extra_info);

                    }catch(WrongBonusException $e){
                        $this->utils->error_log($e);
                    }
                }
            }else{
                $this->utils->info_log('No eligible players found',$players);
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_t1bet_referral_program', $count, $success_apply_id);
    }

    public function auto_apply_and_release_bonus_for_t1t_common_brazil_deposit_bonus_weekly($player_id = _COMMAND_LINE_NULL, $dry_run = false, $date_from = _COMMAND_LINE_NULL, $date_to = _COMMAND_LINE_NULL, $ignore_trans_for_test = false, $periodMinDeposit = 1){
        $promocms_id = $this->utils->getConfig('auto_apply_and_release_bonus_for_t1t_common_brazil_deposit_bonus_weekly');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_t1t_common_brazil_deposit_bonus_weekly promocms_id',$promocms_id);

        if (!empty($promocms_id)) {
            $this->load->model(['promorules','player_promo','player_model','player_friend_referral']);
            $adminId   = $this->users->getSuperAdminId();
            $promorule = $this->promorules->getPromoruleByPromoCms($promocms_id);
            $formula   = json_decode($promorule['formula'], true);
            $this->utils->info_log('auto_apply_and_release_bonus_for_t1t_common_brazil_deposit_bonus_weekly formula', $formula);
            $count = 0;
            $success_apply_id = [];

            if(!empty($formula['bonus_release']) ){
                $description = $this->CI->utils->json_decode_handleErr($formula['bonus_release'], true);
                $accumulation_date = isset($description['accumulation_date']) ? $description['accumulation_date'] : null;
                if(!empty($accumulation_date)){
                    $fromDate = $accumulation_date['start'];
                    $toDate = $accumulation_date['end'];
                }else{
                    $thisMonday = date("Y-m-d", strtotime("midnight monday this week"));
                    list($fromDate, $toDate) = $this->utils->getFromToByWeek($thisMonday);
                }
                $this->utils->info_log('auto_apply_and_release_bonus_for_t1t_common_brazil_deposit_bonus_weekly accumulation_date', $accumulation_date);
            }

            if($player_id == _COMMAND_LINE_NULL){
                $player_id = null;
            }

            $ignore_trans_for_test=$ignore_trans_for_test=='true';

            $this->utils->info_log('from date AND to date', $fromDate, $toDate, 'dry_run', $dry_run, 'player', $player_id,
                'ignore_trans_for_test', $ignore_trans_for_test, 'periodMinDeposit', $periodMinDeposit);

            $players = $this->player_model->getDepositPlayersByOle777thConsecutiveDepositBonus($fromDate, $toDate, $player_id, $ignore_trans_for_test, $periodMinDeposit);
            $this->utils->info_log('get all deposit players', count($players), $players);

            if (!empty($players)) {
                foreach ($players as $player) {
                    $playerId = $player['playerId'];
                    $username = $player['username'];
                    $test_player_list = $this->utils->getConfig('auto_apply_and_release_bonus_player_list');
                    if (!empty($test_player_list)) {
                        if (!in_array($username, $test_player_list)) {
                            continue;
                        }
                    }
                    try{
                        $msg=null;
                        $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                        use($promorule, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $dry_run){
                            $success = true;
                            $preapplication=false;
                            $extra_info = [];
                            $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;

                            list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, $preapplication, null, $extra_info, $dry_run);
                            return $success;
                        });

                        if($succ && $res){
                            $count += 1;
                            $success_apply_id[] = $playerId;
                        }
                        $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promocms_id, $extra_info, $dry_run);

                    }catch(WrongBonusException $e){
                        $this->utils->error_log($e);
                    }
                }
            }else{
                $this->utils->info_log('No eligible players found',$players);
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_t1t_common_brazil_deposit_bonus_weekly', $count, $success_apply_id);
    }

    public function auto_generate_friend_referral_roulette_bonus($player_id = null, $status=null, $dry_run = false, $date_from = _COMMAND_LINE_NULL, $date_to = _COMMAND_LINE_NULL){
        $promocms_ids = $this->utils->getConfig('auto_generate_friend_referral_roulette_bonus_promocms_id');
        $this->utils->info_log('start auto_generate_friend_referral_roulette_bonus promocms_ids',$promocms_ids, $player_id, $status, $dry_run, $date_from, $date_to);

        if (!empty($promocms_ids)) {
            foreach ($promocms_ids as $promocms_id) {
                $this->auto_generate_friend_referral_roulette_bonus_by_promocms_id($player_id, $status, $promocms_id, $dry_run, $date_from, $date_to);
            }
        }

        $this->utils->info_log('end auto_generate_friend_referral_roulette_bonus promocms_ids',$promocms_ids);
    }

    public function auto_generate_friend_referral_roulette_bonus_by_promocms_id($player_id, $status, $promocms_id, $dry_run, $date_from, $date_to){

        $this->utils->info_log('start auto_generate_friend_referral_roulette_bonus_by_promocms_id_'.$promocms_id);
        $count = 0;
        $success_apply_id = [];

        if (!empty($promocms_id)) {
            $this->load->library(['og_utility']);
            $this->load->model(['promorules','player_promo','player_model','player_friend_referral']);
            $adminId = $this->users->getSuperAdminId();
            $promorule = $this->promorules->getPromoruleByPromoCms($promocms_id);
            $formula = json_decode($promorule['formula'], true);

            if(!empty($formula['bonus_release']) ){
                $description = $this->CI->utils->json_decode_handleErr($formula['bonus_release'], true);
                $use_application_date = isset($description['use_application_date']) ? $description['use_application_date'] : false;

                $date_from = $use_application_date ? $promorule['applicationPeriodStart'] : null;
                $date_to  = $this->utils->getNowForMysql();
            }

            // $date_from = $promorule['applicationPeriodStart'];
            // $date_to = $this->utils->getNowForMysql();
            $player_id_of_friend_referral = $this->player_friend_referral->getPlayerReferral($player_id, $status , $date_from, $date_to, true);
            $player_id_of_friend_referral = $this->og_utility->array_pluck($player_id_of_friend_referral, 'playerId');

            $this->utils->info_log('players', $player_id_of_friend_referral);

            if (!empty($player_id_of_friend_referral) && is_array($player_id_of_friend_referral)) {
                foreach ($player_id_of_friend_referral as $playerId) {
                    try{
                        $msg=null;

                        $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $dry_run){
                                $success = true;
                                $extra_info = [];
                                list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, false, null, $extra_info, $dry_run);
                                return $success;
                            });

                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[] = $playerId;
                            }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promocms_id, $extra_info);

                    }catch(WrongBonusException $e){
                        $this->utils->error_log($e);
                    }
                }
            }else{
                $this->utils->info_log('No eligible players found',$player_id_of_friend_referral);
            }
        }
        $this->utils->info_log('end auto_generate_friend_referral_roulette_bonus_by_promocms_id_'.$promocms_id, $count, $success_apply_id);
    }
    public function auto_apply_and_release_t1t_common_bonus() {
        $promocms_list = $this->utils->getConfig('auto_apply_and_release_t1t_common_bonus_list');
        $this->utils->info_log('start auto_apply_and_release_t1t_common_bonus promocms list', $promocms_list);

        if (empty($promocms_list)) {
            $this->utils->info_log('end auto_apply_and_release_t1t_common_bonus promocms list is empty', $promocms_list);
            return;
        }

        $is_blocked = false;
        $db = $this->CI->db;
        $res = null;

        foreach ($promocms_list as $command) {
            $this->utils->info_log('auto_apply_and_release_t1t_common_bonus funcName', $command);

            if (method_exists('Command', $command)) {
                $dbName = !empty($db) ? $db->getOgTargetDB() : null;
                $file_list = [];

                $command_params = [
                    // 'player_id' => $player_id,
                    // 'dry_run' => $dry_run,
                    // 'date_from' => $date_from,
                    // 'date_to' => $date_to,
                ];

                $cmd = $this->utils->generateCommandLine($command, $command_params, $is_blocked, $file_list, $dbName);
                $this->utils->info_log('auto_apply_and_release_t1t_common_bonus cmd' . (empty($db) ? ' empty db' : ' db'), $cmd, $dbName);

                if (!empty($cmd)) {
                    $res = $this->utils->runCmd($cmd);
                    $this->utils->info_log('auto_apply_and_release_t1t_common_bonus res', $res);
                }
            } else {
                $this->utils->info_log('auto_apply_and_release_t1t_common_bonus command not exist', $command);
            }
        }
        $this->utils->info_log('end auto_apply_and_release_t1t_common_bonus promocms list', $promocms_list, $res);
    }

    public function auto_apply_and_release_bonus_for_t1t_common_brazil_vip_weekly_bonus($dateFromStr = _COMMAND_LINE_NULL, $dateToStr = _COMMAND_LINE_NULL, $player_id = _COMMAND_LINE_NULL, $ignore_trans_for_test = false, $periodMinDeposit = 20, $is_dry_run = false){
        $promocms_ids = $this->utils->getConfig('auto_apply_and_release_bonus_for_t1t_common_brazil_vip_weekly_bonus_promocms_ids');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_t1t_common_brazil_vip_weekly_bonus promocms_ids',$promocms_ids, $dateFromStr, $dateToStr, $player_id, $ignore_trans_for_test, $periodMinDeposit, $is_dry_run);

        $count = 0;
        $success_apply_id = [];

        foreach ($promocms_ids as $promocms_id) {
            if (!empty($promocms_id)) {
                $this->load->model(['promorules','player_promo','player_model']);

                $ip = $this->utils->getIP();
                $promorule=$this->promorules->getPromoruleByPromoCms($promocms_id);
                $formula = json_decode($promorule['formula'], true);

                $this->utils->info_log('auto_apply_and_release_bonus_for_t1t_common_brazil_vip_weekly_bonus formula', $formula);

                $allowed_release_days = 'Mon';
                if(!empty($formula['bonus_release']) ){
                    $description = $this->CI->utils->json_decode_handleErr($formula['bonus_release'], true);
                    $allowed_date = isset($description['allowed_date']) ? $description['allowed_date'] : null;
                    $allowed_release_days = isset($description['allowed_release_days']) ? $description['allowed_release_days'] : $allowed_release_days;
                    $periodMinDeposit = isset($description['period_min_deposit']) ? $description['period_min_deposit'] : $periodMinDeposit;

                    $this->utils->info_log('auto_apply_and_release_bonus_for_t1t_common_brazil_vip_weekly_bonus allowed_date', $allowed_date);
                }

                $release_day = !empty($allowed_release_days) ? $allowed_release_days : 'Mon';
                $now_day =  date("D");

                if( ($dateFromStr != _COMMAND_LINE_NULL) && ($dateToStr != _COMMAND_LINE_NULL) ){
                    $fromDate = $this->utils->formatDateForMysql(new DateTime($dateFromStr));
                    $toDate = $this->utils->formatDateForMysql(new DateTime($dateToStr));
                }else{
                    $lastWeekStart = date("Y-m-d 00:00:00", strtotime("last week monday"));
                    $lastWeekEnd = date("Y-m-d 23:59:59", strtotime("last week sunday"));
                    $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $lastWeekStart;
		            $toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $lastWeekEnd;
                }

                $is_dry_run=$is_dry_run=='true';
                $ignore_trans_for_test=$ignore_trans_for_test=='true';
                if($player_id == _COMMAND_LINE_NULL){
                    $player_id = null;
                }

                $this->utils->info_log('from date AND to date', $fromDate, $toDate,
                    'is_dry_run', $is_dry_run,
                    'player', $player_id,
                    'ignore_trans_for_test', $ignore_trans_for_test,
                    'periodMinDeposit', $periodMinDeposit,
                    'allowed_date', $allowed_date,
                    'allowed_release_days', $allowed_release_days,
                    'release_day', $release_day,
                    'now_day', $now_day
                );

                if(is_array($allowed_release_days)){
                    if(!in_array($now_day, $allowed_release_days)){
                        $this->utils->info_log('not allowed_release_days', lang('notify.78'));
                        return;
                    }
                }else{
                    if($now_day != $release_day){
                        $this->utils->info_log('not allowed_release_days', lang('notify.78'));
                        return;
                    }
                }

                $players = $this->player_model->getDepositPlayersByOle777thConsecutiveDepositBonus($fromDate, $toDate, $player_id, $ignore_trans_for_test, $periodMinDeposit);
                $this->utils->info_log('get all deposit players', count($players), $players);

                if (!empty($players)) {
                    foreach ($players as $player) {
                        $playerId = $player['playerId'];
                        $username = $player['username'];

                        $test_player_list = $this->utils->getConfig('auto_apply_and_release_bonus_player_list');
                        if (!empty($test_player_list)) {
                            if (!in_array($username, $test_player_list)) {
                                continue;
                            }
                        }

                        try{
                            $msg=null;

                            $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $ip, $is_dry_run){

                                $success = true;
                                $preapplication=false;
                                $extra_info = [];
                                $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                                $extra_info['player_request_ip'] = $ip;

                                list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, $preapplication, null, $extra_info, $is_dry_run);
                                return $success;
                            });

                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[] = $playerId;
                            }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promocms_id, $extra_info, $ip);

                        }catch(WrongBonusException $e){
                            $this->utils->error_log($e);
                        }
                    }
                }else{
                    $this->utils->info_log('No eligible players found');
                }
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_t1t_common_brazil_vip_weekly_bonus', $count, $success_apply_id);
    }

    public function auto_apply_for_t1bet_sports_total_losses_weekly($player_id = null, $promoCmsId = null){
        $promocms_ids = $this->utils->getConfig('auto_apply_and_release_bonus_for_t1bet_sports_total_losses_weekly_promocms_id');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_t1bet_sports_total_losses_weekly promocms_ids',$promocms_ids);
        $count = 0;
        $success_apply_id = [];

        if (!empty($promocms_ids)) {
            $this->load->model(['promorules', 'player_promo', 'player_model']);

            $this->utils->debug_log('origin promo cms id and game type', $promocms_ids);
            if(!empty($promoCmsId) && !empty($promocms_ids[$promoCmsId])){
                // only allow this promo cms check
                $gameTypeId = $promocms_ids[$promoCmsId];
                $promocms_ids = [$promoCmsId => $gameTypeId];
                $this->utils->info_log('use Specified promo cms id', $promoCmsId);
            }

            foreach ($promocms_ids as $promo_cms_id => $game_type_id) {
                $this->utils->info_log('promo cms id', $promo_cms_id);
                $promorule=$this->promorules->getPromoruleByPromoCms($promo_cms_id);
                $formula = json_decode($promorule['formula'], true);

                if(!empty($formula['bonus_release']) ){
                    $description = $this->CI->utils->json_decode_handleErr($formula['bonus_release'], true);
                    $fromDate = $description['start'];
                    $toDate  = $description['end'];
                }

                if (empty($fromDate) || empty($toDate)) {
                    $lastMonday = date("Y-m-d", strtotime("last week monday"));
                    list($fromDate, $toDate) = $this->utils->getFromToByWeek($lastMonday);
                }

                $this->utils->debug_log('from date to date', $fromDate, $toDate);

                $players = $this->player_model->getPlayerByTotalLossesWeeklyCustomizedConditions($fromDate, $toDate, $player_id, $game_type_id);

                if (!empty($players)) {
                    foreach ($players as $player) {
                        $playerId = $player['player_id'];
                        $registerIp = $player['registrationIp'];
                        $username = $player['username'];
                        $disabled_promotion = $player['disabled_promotion'];

                        if($disabled_promotion == 1){
                            $this->utils->debug_log('player disabled promotion', $playerId);
                            continue;
                        }

                        $test_player_list = $this->utils->getConfig('auto_apply_and_release_bonus_player_list');
                        if (!empty($test_player_list)) {
                            if (!in_array($username, $test_player_list)) {
                                continue;
                            }
                        }

                        try{
                            $msg=null;

                            $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                            use($promorule, $promo_cms_id, $playerId, &$msg, &$extra_info, &$res, $registerIp){

                                $success = true;
                                $preapplication=false;
                                $extra_info = [];
                                $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                                $extra_info['player_request_ip'] = $registerIp;

                                list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promo_cms_id, $preapplication, null, $extra_info);
                                return $success;
                            });

                            if($succ && $res){
                                $count += 1;
                                $success_apply_id[] = $playerId;
                            }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promo_cms_id, $extra_info, $registerIp);

                        }catch(WrongBonusException $e){
                            $this->utils->error_log($e);
                        }
                    }
                }
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_t1bet_sports_total_losses_weekly', $count, $success_apply_id);
    }

    public function auto_apply_and_release_bonus_for_amusino_friend_referral($dateFromStr = _COMMAND_LINE_NULL, $dateToStr = _COMMAND_LINE_NULL, $bet_player_id = _COMMAND_LINE_NULL, $dry_run = false){
        $db = $this->db;
        $dbName = !empty($db) ? $db->getOgTargetDB() : null;
        $db_key = !empty($dbName) ? strtoupper($dbName) : null;

        $promo_cms_id_with_db = $this->utils->getConfig('auto_apply_and_release_bonus_for_amusino_friend_referral_promocms_id');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_amusino_friend_referral promocms_id with key', $promo_cms_id_with_db);
        if(!isset($promo_cms_id_with_db[$db_key])){
            $this->utils->info_log('no promo cms id for this db', $db_key);
            return;
        }

        $promo_cms_id = $promo_cms_id_with_db[$db_key];
        $this->utils->info_log('start auto_apply_and_release_bonus_for_amusino_friend_referral promocms_id', $promo_cms_id, $db_key);

        $count = 0;
        $success_apply_id = [];
        $valid_players = [];
        $players = [];
        $dry_run=$dry_run=='true';

        if (!empty($promo_cms_id)) {
            $this->load->model(['promorules', 'player_promo', 'player_model', 'game_type_model', 'player_friend_referral']);
            $this->load->library(['og_utility']);

            if( ($dateFromStr != _COMMAND_LINE_NULL) && ($dateToStr != _COMMAND_LINE_NULL) ){
                $fromDate = $this->utils->formatDateForMysql(new DateTime($dateFromStr));
                $toDate = $this->utils->formatDateForMysql(new DateTime($dateToStr));
            }else{
                $current_date = $this->utils->getTodayForMysql();
                $yesterday_date = $this->utils->getLastDay($current_date);
                $fromDate = $toDate = $yesterday_date;
            }

            if($bet_player_id == _COMMAND_LINE_NULL){
                $bet_player_id = null;
            }

            $promorule=$this->promorules->getPromoruleByPromoCms($promo_cms_id);
            $promorulesId = $promorule['promorulesId'];
            $PromoDetail = $this->promorules->getPromoDetailsWithFormulas($promorulesId);
            $bonus_condition = !empty($PromoDetail['formula']['bonus_condition']) ? $PromoDetail['formula']['bonus_condition'] : null;
            if(empty($bonus_condition)){
                $this->utils->info_log('empty bonus condition', $promo_cms_id);
                return;
            }

            $description = $bonus_condition;
            $promo_class = $description['class'];
            $bet_allowed_date = isset($description['bet_allowed_date'])? $description['bet_allowed_date'] : null;
            if(!empty($bet_allowed_date['start']) && !empty($bet_allowed_date['end'])){
                $fromDate = $this->utils->formatDateForMysql(new DateTime($bet_allowed_date['start']));
                $toDate = $this->utils->formatDateForMysql(new DateTime($bet_allowed_date['end']));
            }

            $this->utils->debug_log('params info', ['fromDate' => $fromDate, 'toDate' => $toDate, 'bet_player_id' => $bet_player_id, 'dry_run' => $dry_run]);

            $bet_players = $this->player_model->getBetPlayersForCustomizedConditions($fromDate, $toDate, $bet_player_id, '*');
            if(empty($bet_players)){
                $this->utils->info_log('empty bet player', $bet_players);
                return;
            }
            $this->utils->debug_log('===========================================', $bet_players);

            // collet referrer by invited
            foreach($bet_players as $bet_player){
                $invited_record = $this->player_friend_referral->getPlayerReferralList(null, null, null, null, $bet_player['player_id']);
                if(!empty($invited_record)){
                    $valid_players[] = $invited_record[0]->playerId;
                }
            }
            $this->utils->debug_log('=========================================== valid_players', $valid_players);

            if(empty($valid_players)){
                $this->utils->info_log('empty valid player', $bet_players);
                return;
            }

            $players = $this->player_model->getPlayerInfoForCustomizedConditions($valid_players);
            $this->utils->debug_log('=========================================== players', $players);

            if (!empty($players)) {
                foreach ($players as $player) {
                    $playerId = $player['playerId'];
                    $registerIp = $player['registrationIp'];
                    $username = $player['username'];
                    $disabled_promotion = $player['disabled_promotion'];

                    if($disabled_promotion == 1){
                        $this->utils->debug_log('player disabled promotion', $playerId);
                        continue;
                    }

                    $test_player_list = $this->utils->getConfig('auto_apply_and_release_bonus_player_list');
                    if (!empty($test_player_list)) {
                        if (!in_array($username, $test_player_list)) {
                            continue;
                        }
                    }

                    try{
                        $msg=null;

                        $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                        use($promorule, $promo_cms_id, $playerId, &$msg, &$extra_info, &$res, $registerIp, $dry_run){

                            $success = true;
                            $preapplication=false;
                            $extra_info = [];
                            $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                            $extra_info['player_request_ip'] = $registerIp;

                            list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promo_cms_id, $preapplication, null, $extra_info, $dry_run);
                            return $success;
                        });

                        if($succ && $res){
                            $count += 1;
                            $success_apply_id[] = $playerId;
                        }
                        $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promo_cms_id, $extra_info, $registerIp);

                    }catch(WrongBonusException $e){
                        $this->utils->error_log($e);
                    }
                }
            }else{
                $this->utils->info_log('No eligible players found', $players);
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_amusino_friend_referral', $count, $success_apply_id);
    }

    public function auto_apply_and_release_bonus_for_king_total_losses_weekly_bonus($dateFromStr = _COMMAND_LINE_NULL, $dateToStr = _COMMAND_LINE_NULL, $player_id = null, $is_dry_run = false){
        $promocms_id = $this->utils->getConfig('auto_apply_and_release_bonus_for_king_total_losses_weekly_bonus_promocms_id');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_king_total_losses_weekly_bonus promocms_id', $promocms_id);
        $count = 0;
        $success_apply_id = [];
        
        if (!empty($promocms_id)) {
            $this->load->model(['promorules', 'player_promo', 'player_model']);
            
            $ip = $this->utils->getIP();
            $promorule=$this->promorules->getPromoruleByPromoCms($promocms_id);
            
            $is_dry_run=$is_dry_run=='true';
            if($player_id == _COMMAND_LINE_NULL){
                $player_id = null;
            }

            if( ($dateFromStr != _COMMAND_LINE_NULL) && ($dateToStr != _COMMAND_LINE_NULL) ){
                $fromDate = $this->utils->formatDateForMysql(new DateTime($dateFromStr));
                $toDate = $this->utils->formatDateForMysql(new DateTime($dateToStr));
            }else{
                $formula = json_decode($promorule['formula'], true);
    
                if(!empty($formula['bonus_release']) ){
                    $description = $this->utils->json_decode_handleErr($formula['bonus_release'], true);
                    $allowed_date = isset($description['allowed_date']) ? $description['allowed_date'] : null;
                }
                $lastWeekStart = date("Y-m-d 00:00:00", strtotime("last week monday"));
                $lastWeekEnd = date("Y-m-d 23:59:59", strtotime("last week sunday"));
                $fromDate = !empty($allowed_date['start']) ? $allowed_date['start'] : $lastWeekStart;
                $toDate = !empty($allowed_date['end']) ? $allowed_date['end'] : $lastWeekEnd;
            }


            $this->utils->info_log('from date AND to date', $fromDate, $toDate, 'is_dry_run', $is_dry_run, 'player', $player_id);

            $players = $this->player_model->getPlayerByTotalLossesWeeklyCustomizedConditions($fromDate, $toDate, $player_id, '*');

            if (!empty($players)) {
                foreach ($players as $player) {
                    $playerId = $player['player_id'];
                    $username = $player['username'];
                    $disabled_promotion = $player['disabled_promotion'];

                    if($disabled_promotion == 1){
                        $this->utils->debug_log('player disabled promotion', $playerId);
                        continue;
                    }

                    $test_player_list = $this->utils->getConfig('auto_apply_and_release_bonus_player_list');
                    if (!empty($test_player_list)) {
                        if (!in_array($username, $test_player_list)) {
                            continue;
                        }
                    }

                    try{
                        $msg=null;

                        $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                        use($promorule, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $ip){

                            $success = true;
                            $preapplication=false;
                            $extra_info = [];
                            $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                            $extra_info['player_request_ip'] = $ip;

                            list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, $preapplication, null, $extra_info);
                            return $success;
                        });

                        if($succ && $res){
                            $count += 1;
                            $success_apply_id[] = $playerId;
                        }
                        $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg);

                    }catch(WrongBonusException $e){
                        $this->utils->error_log($e);
                    }
                }
            }
        }
        $this->utils->info_log('end auto_apply_and_release_bonus_for_king_total_losses_weekly_bonus', $count, $success_apply_id);
    }

    public function auto_apply_and_release_bonus_for_t1t_common_brazil_vip_monthly_bonus($player_id = null, $is_dry_run = false){
        $promocms_id = $this->utils->getConfig('auto_apply_and_release_bonus_for_t1t_common_brazil_vip_monthly_bonus_promocms_id');
        $this->utils->info_log('start auto_apply_and_release_bonus_for_t1t_common_brazil_vip_monthly_bonus promocms_id',$promocms_id);

        $count = 0;
        $success_apply_id = [];

        if (!empty($promocms_id)) {
            $this->load->model(['promorules','player_promo','player_model']);
            $ip = $this->utils->getIP();
            $promorule=$this->promorules->getPromoruleByPromoCms($promocms_id);
            
            $formula = json_decode($promorule['formula'], true);
            if(!empty($formula['bonus_release']) ){
                $description = $this->CI->utils->json_decode_handleErr($formula['bonus_release'], true);
                $levelStart = isset($description['levelStart']) ? $description['levelStart'] : null;
                $levelEnd = isset($description['levelEnd']) ? $description['levelEnd'] : null;
            }

            $players = $this->player_model->getPlayerByVipMonthlyCustomizedConditions($levelStart, $levelEnd, $player_id);
                $this->utils->info_log('get all players', $players);

                if (!empty($players)) {
                    foreach ($players as $player) {
                        $playerId = $player['playerId'];
                    try{
                        $msg=null;
                        $succ=$this->lockAndTransForPlayerBalance($playerId, function()
                        use($promorule, $promocms_id, $playerId, &$msg, &$extra_info, &$res, $ip, $is_dry_run){

                            $success = true;
                            $preapplication=false;
                            $extra_info = [];
                            $extra_info['order_generated_by'] = Player_promo::ORDER_GENERATED_BY_AUTO_APPLY_FROM_CRONJOB;
                            $extra_info['player_request_ip'] = $ip;

                            list($res, $msg)=$this->promorules->triggerPromotionFromCronjob($playerId, $promorule, $promocms_id, $preapplication, null, $extra_info, $is_dry_run);
                            return $success;
                        });

                        if($succ && $res){
                            $count += 1;
                            $success_apply_id[] = $playerId;
                        }
                            $this->utils->info_log(__METHOD__,'apply promo result on order:',$playerId, $succ, $res, $msg, $promocms_id, $extra_info, $ip);

                        }catch(WrongBonusException $e){
                            $this->utils->error_log($e);
                        }
                    }
                }else{
                    $this->utils->info_log('No eligible players found',$players);
                }
            }
            $this->utils->info_log('end auto_apply_and_release_bonus_for_t1t_common_brazil_vip_monthly_bonus', $count, $success_apply_id);
    }
}
