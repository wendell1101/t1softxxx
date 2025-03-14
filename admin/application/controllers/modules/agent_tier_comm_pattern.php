<?php
/**
 *   filename:   agent_tier_comm_pattern.php
 *   date:       2017-11-11
 *   @brief:     tier commission patterns can be created and used in agency commission setting
 */

trait agent_tier_comm_pattern{

    // tier_comm_patterns {{{2
    /**
     *  create and display tier commission patterns
     *
     *  @param
     *  @return
     */
    public function tier_comm_patterns($pattern_name = '', $tier_count = "") {
        if (!$this->utils->isEnabledFeature('agent_tier_comm_pattern')) {
            return $this->error_access();
        }
        if ($this->hasPermission('tier_comm_patterns')) {

            $data['conditions'] = $this->safeLoadParams(array(
                'pattern_name' => $pattern_name,
                'tier_count' => $tier_count,
            ));
            $data['controller_name'] = $this->controller_name;

            $this->load_template(lang('Agent Tier Commission Patterns'), '', '', 'agency');
            $this->template->add_js('resources/js/bootstrap-switch.min.js');
            $this->template->add_css('resources/css/bootstrap-switch.min.css');
            $this->template->write_view('main_content', 'includes/agent_tier_comm_pattern', $data);
            $this->template->render();
        }
    } // tier_comm_patterns  }}}2

    // add_new_pattern {{{2
    /**
     *  create and display tier commission patterns
     *
     *  @param
     *  @return
     */
    public function add_new_pattern() {
        if ($this->hasPermission('edit_tier_comm_pattern')) {
            $this->load_template(lang('Agency Management'), '', '', 'agency');

            $data['conditions'] = $this->safeLoadParams(array(
                'pattern_name' => '',
                'cal_method' => 0,
                'tier_count' => 1,
                'rev_share' => 0.00,
                'rolling_comm_basis' => 'total_bets',
                'rolling_comm' => 0.00,
                'min_active_player_count' => 0,
                'min_bets' => 0.00,
                'min_trans' => 0.00,
            ));
            $data['controller_name'] = $this->controller_name;

            $this->template->write_view('main_content', 'includes/agent_tier_comm_pattern_form', $data);
            $this->template->render();
        }
    } // add_new_pattern  }}}2
    // save_tier_comm_pattern {{{2
    /**
     *  save tier commission pattern
     *
     *  @param
     *  @return
     */
    public function save_tier_comm_pattern($is_edit= null) {
        $this->utils->debug_log('save_tier_comm_pattern  POSTS: ', $this->input->post());
        $tier_count = $this->input->post("tier_count");
        $tier_index = $this->input->post("tier_index");
        $upper_bound = $this->input->post("upper_bound");
        $rev_share = $this->input->post("rev_share");
        $rolling_comm = $this->input->post("rolling_comm");
        $agents = $this->input->post("agents");
        $game_types = $this->input->post("game_types");

       $_rev_share = empty($rev_share[$tier_count - 1])? 0: $rev_share[$tier_count - 1];
       $_rolling_comm = empty($rolling_comm[$tier_count - 1])? 0: $rolling_comm[$tier_count - 1];

        $data = array(
            "pattern_name" => $this->input->post("pattern_name"),
            "cal_method" => $this->input->post("cal_method"),
            "rolling_comm_basis" => $this->input->post("rolling_comm_basis"),
            "tier_count" => $tier_count,
            "rev_share" => $_rev_share,
            "rolling_comm" => $_rolling_comm,
        );

        $this->agency_model->startTrans();
        if (isset($is_edit) && $is_edit){
            $pattern_id = $this->input->post('pattern_id');
            $this->agency_model->update_tier_comm_pattern($pattern_id, $data);
        } else {
            $pattern_id = $this->agency_model->insert_tier_comm_pattern($data);
        }

        $tiers = array();
        // save tier info into table agency_tier_comm_pattern_tiers
        for ($i = 0; $i < $tier_count; $i++) {
            $tier = array(
                "pattern_id" => $pattern_id,
                "tier_index" => $tier_index[$i],
                "upper_bound" => $upper_bound[$i],
                "rev_share" => $rev_share[$i],
                "rolling_comm" => $rolling_comm[$i],
            );
            $tiers[] = $tier;
        }
        if (isset($is_edit) && $is_edit) {
            $this->agency_model->remove_tier_comm_pattern_tiers_by_pattern_id($pattern_id);
        }
        if( ! empty($tiers) ){
            $this->db->insert_batch('agency_tier_comm_pattern_tiers', $tiers);
        }

        if($this->utils->getConfig('enable_batch_update_tier_commission_settings')){
            //update agent and game type, commision rate
            if(empty($agents)){
                $agents = [];
            }

            if(empty($game_types)){
                $game_types = [];
            }

            $agentIds = [];
            $gameTypeIds = [];
            $agentSelected = array_filter($agents, function($agent) use(&$agentIds){
                $enabled = isset($agent['enabled'])?$agent['enabled']:0;
                if(isset($agent['enabled'])&&$agent['enabled']){
                    $agentIds[] = $agent['agent_id'];
                    return true;
                }
                return false;
            });
            $gameTypeSelected = array_filter($game_types, function($gameType) use(&$gameTypeIds){
                $enabled = isset($gameType['enabled'])?$gameType['enabled']:0;
                if(isset($gameType['enabled'])&&$gameType['enabled']){
                    return true;
                }
                return false;
            });

            $pattern = $this->agency_model->get_tier_comm_pattern($pattern_id);
            foreach ($agentIds as $agentKey => $agentId) {
                $agent_name = $this->agency_model->getAgentNameById($agentId);

                foreach ($gameTypeSelected as $key => $gameType) {

                    $game_type= [];
                    $gameTypeId = $gameType['game_type_id'];
                    $gamePlatformId = $gameType['game_platform_id'];
                    $game_type['game_type_id'] = $gameTypeId;
                    $game_type['agent_id'] = $agentId;
                    $game_type['game_platform_id'] = $gamePlatformId;
                    $game_type['pattern_id'] = $pattern_id;
                    $game_type['rev_share'] = $pattern['rev_share'];
                    $game_type['rolling_comm_basis'] = $pattern['rolling_comm_basis'];
                    $game_type['rolling_comm'] = $pattern['rolling_comm'];
                    $game_type['bet_threshold'] = $pattern['min_bets'];


                    $this->db->where('game_type_id',$gameTypeId);
                    $this->db->where('agent_id',$agentId);
                    $this->db->where('game_platform_id',$gamePlatformId);
                    $q = $this->db->get('agency_agent_game_types');

                    $action = 'modify_structure';
                    if ( $q->num_rows() > 0 )
                    {
                        $this->db->where('game_type_id',$gameTypeId);
                        $this->db->where('agent_id',$agentId);
                        $this->db->where('game_platform_id',$gamePlatformId);
                        $this->db->update('agency_agent_game_types',$game_type);
                    } else {
                        $this->db->set($game_type);
                        $this->db->insert('agency_agent_game_types',$game_type);
                    }

                    //log update
                    $link_url = $link_url = site_url('edit_pattern/edit_pattern/'.$is_edit);
                    $log_params = array(
                        'action' => $action,
                        'link_url' => $link_url,
                        'done_by' => $this->authentication->getUsername(),
                        'done_to' => $agent_name,
                        'details' => 'Batch update commision settings'. $agent_name,
                    );
                    $this->agency_library->save_action($log_params);

                }
            }
        }

        if($is_edit){
            $message = lang('con.agen15');
        }else{
            $message = lang('con.agen17');
        }

        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);


        $succ = $this->agency_model->endTransWithSucc();
        if (!$succ) {
            $message = lang('con.agen16');
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
            throw new Exception('Sorry, save tier comm pattern failed.');
        }

        redirect($this->controller_name . '/tier_comm_patterns');

    } // save_tier_comm_pattern  }}}2
    // edit_pattern {{{2
    /**
     *  create and display tier commission patterns
     *
     *  @param
     *  @return
     */
    public function edit_pattern($pattern_id) {
        if ($this->hasPermission('edit_tier_comm_pattern')) {
            $this->load_template(lang('Agency Management'), '', '', 'agency');

            $pattern_info = $this->agency_model->get_tier_comm_pattern($pattern_id);
            $data['conditions'] = $this->safeLoadParams(array(
                'pattern_id' => $pattern_info['pattern_id'],
                'pattern_name' => $pattern_info['pattern_name'],
                'cal_method' => $pattern_info['cal_method'],
                'tier_count' => $pattern_info['tier_count'],
                'rolling_comm_basis' => $pattern_info['rolling_comm_basis'],
                'rev_share' => $pattern_info['rev_share'],
                'rolling_comm' => $pattern_info['rolling_comm'],
                'min_active_player_count' => $pattern_info['min_active_player_count'],
                'min_bets' => $pattern_info['min_bets'],
                'min_trans' => $pattern_info['min_trans'],
            ));

            $tiers = $this->agency_model->get_tier_comm_pattern_tiers_by_pattern_id($pattern_id);
            $this->utils->debug_log('edit pattern TIERS: ', $tiers);

            $tier_count = $pattern_info['tier_count'];
            $upper_bounds = array();
            $rev_shares = array();
            $rolling_comms = array();
            for ($i = 0; $i < $tier_count; $i++) {
                $upper_bounds[$tiers[$i]['tier_index']] = $tiers[$i]['upper_bound'];
                $rev_shares[$tiers[$i]['tier_index']] = $tiers[$i]['rev_share'];
                $rolling_comms[$tiers[$i]['tier_index']] = $tiers[$i]['rolling_comm'];
            }
            $data['upper_bounds'] = $upper_bounds;
            $data['rev_shares'] = $rev_shares;
            $data['rolling_comms'] = $rolling_comms;
            $data['controller_name'] = $this->controller_name;
            $data['pattern_id'] = $pattern_id;
            $data['old_tier_count'] = $tier_count;
            $data['is_edit'] = true;
            $this->utils->debug_log('edit pattern DATA: ', $data);

            //OGP-26199
            $data['game_platform_list'] = [];
            $data['agents'] = [];
            if($this->utils->getConfig('enable_batch_update_tier_commission_settings')){
                //get all game platform and Game types for checkbox
                $data['game_platform_list'] = $this->agency_model->get_game_platforms_and_types();

                //get all agents using pattern for checkbox
                $data['agents'] = $this->agency_model->getMultipleActiveAgents($pattern_id);
            }

            $this->template->write_view('main_content', 'includes/agent_tier_comm_pattern_form', $data);
            $this->template->render();
        }
    } // edit_pattern  }}}2
    // remove_pattern {{{2
    /**
     *  create and display tier commission patterns
     *
     *  @param
     *  @return
     */
    public function remove_pattern($pattern_id) {
        if ($this->hasPermission('edit_tier_comm_pattern')) {
            $this->agency_model->startTrans();
            $this->agency_model->remove_tier_comm_pattern($pattern_id);
            $this->agency_model->remove_tier_comm_pattern_tiers_by_pattern_id($pattern_id);
            $succ = $this->agency_model->endTransWithSucc();
            if (!$succ) {
                throw new Exception('Sorry, remove tier comm pattern failed.');
            }

            redirect($this->controller_name . '/tier_comm_patterns');
        }
    } // remove_pattern  }}}2
}
// zR to open all folded lines
// vim:ft=php:fdm=marker
// end of agent_tier_comm_pattern.php
