<?php
/**
 *   filename:   agency_management.php
 *   date:       2016-05-03
 *   @brief:     controller for agency sub-system
 */
require_once dirname(__FILE__) . '/modules/base_agency_controller.php';
require_once dirname(__FILE__) . '/modules/agent_domain_module.php';
require_once dirname(__FILE__) . '/modules/agent_list_info_module.php';
require_once dirname(__FILE__) . '/modules/agent_bank_info_module.php';
require_once dirname(__FILE__) . '/modules/agent_tier_comm_pattern.php';
require_once dirname(__FILE__) . '/modules/agency_settlement_module.php';
require_once dirname(__FILE__) . '/modules/agency_help_page_module.php';

/**
 * Agency Management
 *
 * Agency Management Controller
 *
 *
 */

class Agency_management extends Base_agency_controller {

    use agent_domain_module;
    use agent_list_info_module;
    use agent_bank_info_module;
    use agent_tier_comm_pattern;
    use agency_settlement_module;
    use agency_help_page_module;

    public function error_access() {
        $this->load_template(lang('Agency'), '', '', 'agency');
        $agencyUrl = $this->utils->activeAgencySidebar();
        $data['redirect'] = $agencyUrl;

        $message = lang('con.plm01');
        $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);

        $this->template->write_view('main_content', 'error_page', $data);
        $this->template->render();
    }

    function __construct() {
        parent::__construct();
        $this->load->helper(array('date_helper', 'url'));
        $this->load->library(array('agency_library', 'permissions', 'excel', 'form_validation', 'template', 'pagination', 'player_manager', 'report_functions', 'gcharts', 'marketing_functions', 'depositpromo_manager', 'transactions_library', 'security'));
        $this->load->model(array('agency_model', 'transactions', 'group_level'));

        $this->permissions->checkSettings();
        $this->permissions->setPermissions(); //will set the permission for the logged in user
        $this->controller_name = 'agency_management';
    }

    /**
     *  Load template for views based on regions in config/template.php
     *
     */
    private function load_template($title, $description, $keywords, $activenav) {
        $this->template->add_css('resources/css/agency_management/style.css');
        $this->template->add_js('resources/js/agency_management/agency_management.js');
        $this->template->add_js('resources/js/datatables.min.js');
        $this->template->add_css('resources/css/general/style.css');
        $this->template->add_css('resources/css/datatables.min.css');

        $this->template->write('title', $title);
        $this->template->write('description', $description);
        $this->template->write('keywords', $keywords);
        $this->template->write('activenav', $activenav);
        $this->template->write('username', $this->authentication->getUsername());
        $this->template->write('userId', $this->authentication->getUserId());
        $this->template->write_view('sidebar', 'agency_management/sidebar');
    }

    /**
     *  show structures (search conditions supported)
     *
     *  @param  <param>
     *  @return <return>
     */
    public function structure_list() {
        if (!$this->permissions->checkPermissions('structure_list')) {
            $this->error_access();
        } else {
            $this->load_template(lang('Agent Template List'), '', '', 'agency');
            $this->template->add_js('resources/js/bootstrap-switch.min.js');
            $this->template->add_css('resources/css/bootstrap-switch.min.css');
            $this->template->write_view('main_content', 'agency_management/view_structure_list');
            $this->template->render();
        }
    }

    /**
     *  create a new structure
     */
    public function create_structure($vip_levels = '') {
        if (!$this->permissions->checkPermissions('structure_list')) {
            return $this->error_access();
        }

        $data['vipgrouplist'] = $this->group_level->getVipGroupList();
        $this->load->model('game_type_model');
        $data['game_types'] = $this->game_type_model->getGameTypesArray();
        $data['conditions'] = $this->safeLoadParams(array(
            'structure_name' => '',
            'currency' => '',
            'status' => 'active',
            'vip_level' => '',
            'allowed_level' => 5,
            'credit_limit' => '0.00',
            'agent_type' => array(),
            'settlement_period' => '',
            'start_day' => '',
            'admin_fee' => '0.00',
            'transaction_fee' => '0.00',
            'bonus_fee' => '0.00',
            'cashback_fee' => '0.00',
            'min_rolling_comm' => '0.00',

            'can_have_sub_agent'=>'',
            'can_have_players'=>'',
            'show_bet_limit_template'=>'',
            'show_rolling_commission'=>'',
            'can_view_agents_list_and_players_list'=>'',
            'can_do_settlement'=>'',

            'enabled_can_have_sub_agent' => true,
            'enabled_can_have_players'=> true,
            'enabled_show_bet_limit_template'=> true,
            'enabled_show_rolling_commission'=> true,
            'enabled_can_view_agents_list_and_players_list'=> true,
            'enabled_can_do_settlement'=> true,
        ));
        $this->utils->debug_log('create_structure CONDITIONS', $data['conditions']);
        $data['fields'] = $this->fields_array;
        $data['labels'] = $this->labels_array;

        $data['vip_levels'] = $this->agency_model->get_vip_levels();
        $data['form_url'] = site_url('agency_management/verify_structure');
		$data['validate_ajax_url'] = site_url('/agency_management/create_agent_validation_ajax');
		$data['parent_game_ajax_url'] = site_url('/agency_management/get_parent_game_types_ajax');

        $is_new = true;
        $this->get_game_comm_settings($data, null, 'structure', $is_new);

        $this->load_template(lang('Create Agent Template'), '', '', 'agency');
        $this->template->add_js('resources/js/bootstrap-switch.min.js');
        $this->template->add_css('resources/css/bootstrap-switch.min.css');

        $this->addJsTreeToTemplate();

        $this->template->write_view('main_content', 'includes/common_agent_form', $data);
        $this->template->render();
    }

    /**
     *  set level names for allowed levels
     *
     *  @param  int number of allowed levels
     *  @return redirect to structure_list
     */
    public function set_level_names($level_count) {
        $data['level_count'] = $level_count;
        $this->load->view('agency_management/set_level_names', $data);
    }

    /**
     *  set for rules for structure creation
     *
     *  @param
     *  @return
     */
    private function structure_form_rules() {
        $this->form_validation->set_rules('settlement_period', lang('Settlement Period'), 'trim|required');
    }

    /**
     *  player_vip_levels is required
     *
     *  @param
     *  @return
     */
    public function check_player_vip_levels() {
        $selected_vip_levels = $this->input->post('selected_vip_levels');
        $this->utils->debug_log('selected_vip_levels', $selected_vip_levels, explode(',', $selected_vip_levels));
        if ($selected_vip_levels == '') {
            $this->form_validation->set_message('check_player_vip_levels',
                lang('VIP Level') . ' ' . lang('is required'));
            return false;
        }
        return true;
    }

    /**
     *  agent_settlement_period is >=0.00 and <= 90.00
     *
     *  @param
     *  @return
     */
    public function check_agent_settlement_period() {
        $settlement_period = $this->input->post('settlement_period');
        $this->utils->debug_log('settlement_period', $settlement_period);
        if ($settlement_period == '') {
            $this->form_validation->set_message('check_agent_settlement_period',
                lang('Settlement Period') . ' ' . lang('is required'));
            return false;
        }
        $start_day = '';
        if ($settlement_period == 'Weekly') {
            $start_day = $this->input->post('start_day');
            $this->utils->debug_log('start_day', $start_day);
            if ($start_day == '') {
                $this->form_validation->set_message('check_agent_settlement_period',
                    lang('Please select start day for weekly settlement'));
                return false;
            }
        }
        return true;
    }

    /**
     *  create json string for all allowed levels
     *
     *  @param  int number of allowed levels
     *  @return string allowed level names
     */
    private function allowed_level_names_json($level_cnt) {
        $data = '{';
        for ($i = 0; $i < $level_cnt; $i++) {
            $level_id = 'allowed_level_' . $i;
            $level_name = $this->input->post("'".$level_id."'");
            if ($i < ($level_cnt - 1)) {
                $data .= '"'. $level_id . '": "' . $level_name . '",';
            } else {
                $data .= '"'. $level_id . '": "' . $level_name . '"';
            }
        }
        $data .= '}';

        return $data;
    }

    /**
     *  Add a new structure into DB table agency_structures
     *
     *  @param
     *  @return structure ID
     */
    private function add_new_structure() {

        $today = date("Y-m-d H:i:s");

        $agent_types = $this->input->post('agent_type');

        $data = array(
            'structure_name'            => $this->input->post('structure_name'),
            'currency'                  => $this->input->post('currency'),
            'credit_limit'              => $this->input->post('credit_limit'),
            'status'                    => $this->input->post('status'),
            'vip_level'                 => $this->input->post('vip_level'),
            'settlement_period'         => $this->input->post('settlement_period'),
            'settlement_start_day'      => $this->input->post('start_day'),
            'allowed_level'             => $this->input->post('allowed_level'),
            'allowed_level_names'       => json_encode($this->input->post('allowed_level_names') ? : array_fill(0, $this->input->post('allowed_level'), '')),
            'can_have_sub_agent'        => is_array($agent_types) ? in_array('can-have-sub-agents', $agent_types)? 1:0 : 0,
            'can_have_players'          => is_array($agent_types) ? in_array('can-have-players', $agent_types)? 1:0 : 0,
            'can_do_settlement'         => is_array($agent_types) ? in_array('can-do-settlement', $agent_types)? 1:0 : 0,
            'show_bet_limit_template'   => is_array($agent_types) ? in_array('show-bet-limit-template', $agent_types)? 1:0 : 0,
            'show_rolling_commission'   => is_array($agent_types) ? in_array('show-rolling-commission', $agent_types)? 1:0 : 0,
            'can_view_agents_list_and_players_list' => is_array($agent_types) ? in_array('can-view-agents-list-and-players-list', $agent_types)? 1:0 : 0,
            'admin_fee'                 => $this->input->post('admin_fee'),
            'transaction_fee'           => $this->input->post('transaction_fee'),
            'bonus_fee'                 => $this->input->post('bonus_fee'),
            'cashback_fee'              => $this->input->post('cashback_fee'),
            'min_rolling_comm'          => $this->input->post('min_rolling_comm'),
            'created_on'                => $today,
            'updated_on'                => $today,
        );

        $this->utils->debug_log('verify_structure DATA:', $data);
        $this->agency_model->startTrans();

        $structure_id = $this->agency_model->add_structure($data);
        $game_platforms = $this->input->post('game_platforms');
        $game_types = $this->input->post('game_types');
        $this->utils->debug_log('add_new_structure game_platforms, game_types:', $game_platforms, $game_types);
        $this->agency_model->add_game_comm_settings($game_platforms, $game_types, $structure_id, 'structure');

        $succ = $this->agency_model->endTransWithSucc();

        if(!$succ) {
            throw new Exception('Sorry, save structure failed.');
        }

        return $structure_id;
    }

    /**
     *  get all allowed level names in '(name1, name2, ..., name8)' format
     *
     *  @param  input number of allowed levels
     *  @return array for allowed level names
     */
    private function get_allowed_level_names($allowed_level_cnt) {
        $level_names = array();
        for ($i = 0; $i < $allowed_level_cnt; $i++) {
            $level_id = 'agent_level_' . $i;
            $level_names[] = $this->input->post($level_id);
        }

        return implode(',', $level_names);
    }

    /**
     *  get vip level names according to given array of vip level ids
     *
     *  @param  array vip levle ids
     *  @return string ',' separated vip level names
     */
    private function get_vip_level_names($vip_levels) {
        $vip_level_names = array();
        foreach($vip_levels as $vip_level_id) {
            $vip_level_names[] = $this->input->post('vip_level_name_'.$vip_level_id);
        }

        return implode(',', $vip_level_names);
    }

    /**
     *  verify new created structure and redirect to structure list
     *
     *  @param
     *  @return
     */
    public function verify_structure() {
        if (!$this->permissions->checkPermissions('structure_list')) {
            return $this->error_access();
        }

        $this->structure_form_rules();

        $this->form_validation->set_rules('structure_name', lang('Structure Name'), 'trim|required|min_length[2]|max_length[12]|alpha_numeric|is_unique[agency_structures.structure_name]');

        $this->utils->debug_log('the log input post --->',$this->input->post());

        if ($this->form_validation->run() == false) {
            $this->utils->debug_log("the log validation_errors", validation_errors());
            $this->create_structure();
        } else {

            $structure_id = $this->add_new_structure();

            $this->utils->debug_log('the log structure id --->', $structure_id);

            $structure_details = $this->agency_model->get_structure_by_id($structure_id);

            $log_params = array(
                'action'   => 'create_structure',
                'link_url' => site_url('includes/common_agent_form'),
                'done_by'  => $this->authentication->getUsername(),
                'done_to'  => $this->authentication->getUsername(),
                'details'  => 'Create agent template: '. json_encode($structure_details),
            );

            $this->agency_library->save_action($log_params);
            $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Save successfully'));
            $this->utils->debug_log('the log should redirect --->');
            redirect('agency_management/structure_list', "refresh");
        }
    }

    /**
     *  parse allowd level name array according to given structure info and allowed level
     *
     *  @param  array structure_details
     *  @return string agent level name
     */
    private function get_allowed_level_names_in_array($structure_details) {
        $allowed_level = $structure_details['allowed_level'];
        $names= array();
        $level_names = $structure_details['allowed_level_names'];
        if(!empty($level_names) || $level_names != 0) {
            $names_obj = json_decode($level_names);
            for ($i = 0; $i < $allowed_level; $i++) {
                $level_id = 'allowed_level_' . $i;
                $names[] = $names_obj->$level_id;
            }
        }
        return $names;
    }

    /**
     *  edit an existed structure
     *
     *  @param  int structure_id
     *  @return
     */
    public function edit_structure($structure_id, $vip_levels = '') {
        if (!$this->permissions->checkPermissions('structure_list')) {
            return $this->error_access();
        }

        $data['vipgrouplist'] = $this->group_level->getVipGroupList();
        $this->load->model('game_type_model');
        $data['game_types'] = $this->game_type_model->getGameTypesArray();
        $structure_details = $this->agency_model->get_structure_by_id($structure_id);
        $this->utils->debug_log('Edit Structure:', $structure_details);
        $allowed_level_names = explode(',', $structure_details['allowed_level_names']);

        if (empty($vip_levels)) {
            $vip_levels = $structure_details['vip_levels'];
        }
        $data['conditions'] = $this->safeLoadParams(array(
            'structure_id' => $structure_details['structure_id'],
            'structure_name' => $structure_details['structure_name'],
            'currency' => $structure_details['currency'],
            'status' => $structure_details['status'],
            'credit_limit' => $structure_details['credit_limit'],
            'allowed_level' => $structure_details['allowed_level'],
            'allowed_level_names' => $allowed_level_names,
            'vip_level' => $structure_details['vip_level'],
            'rev_share' => $structure_details['rev_share'],
            'total_bets_except' => $structure_details['total_bets_except'],
            'can_have_sub_agent' => $structure_details['can_have_sub_agent'] == 1,
            'can_have_players' => $structure_details['can_have_players'] == 1,
            'can_do_settlement' => $structure_details['can_do_settlement'] == 1,
            'show_bet_limit_template' => $structure_details['show_bet_limit_template'] == 1,
            'show_rolling_commission' => $structure_details['show_rolling_commission'] == 1,
            'can_view_agents_list_and_players_list' => $structure_details['can_view_agents_list_and_players_list'] == 1,
            'settlement_period' =>  $structure_details['settlement_period'],
            'start_day' => $structure_details['settlement_start_day'],
            'vip_levels' => $vip_levels,
            'agent_type' => array(),
            'admin_fee' => number_format($structure_details['admin_fee'],2),
            'transaction_fee' => number_format($structure_details['transaction_fee'],2),
            'bonus_fee' => number_format($structure_details['bonus_fee'],2),
            'cashback_fee' => number_format($structure_details['cashback_fee'],2),
            'min_rolling_comm' => number_format($structure_details['min_rolling_comm'],2),

            'enabled_can_have_sub_agent' => true,
            'enabled_can_have_players'=> true,
            'enabled_show_bet_limit_template'=> true,
            'enabled_show_rolling_commission'=> true,
            'enabled_can_view_agents_list_and_players_list'=> true,
            'enabled_can_do_settlement'=> true,

        ));
        $data['fields'] = $this->fields_array;
        $data['labels'] = $this->labels_array;

        $data['game_types'] = $this->game_type_model->getGameTypesArray();
        $this->get_game_comm_settings($data, $structure_id, 'structure');

        $data['agency_player_rolling_settings']=$this->utils->getConfig('agency_player_rolling_settings');

        $data['is_agent'] = FALSE;
        $data['vip_levels'] = $this->agency_model->get_vip_levels();

        $data['form_url'] = site_url('/agency_management/verify_update_structure');
		$data['validate_ajax_url'] = site_url('/agency_management/create_agent_validation_ajax');
		$data['parent_game_ajax_url'] = site_url('/agency_management/get_parent_game_types_ajax');

        $this->load_template(lang('Edit Agent Template'), '', '', 'agency');
        $this->template->add_js('resources/js/bootstrap-switch.min.js');
        $this->template->add_css('resources/css/bootstrap-switch.min.css');

        $this->addJsTreeToTemplate();

        $this->template->write_view('main_content', 'includes/common_agent_form', $data);
        $this->template->render();
    }

    /**
     *  Add a new structure into DB table agency_structures
     *
     *  @param  int structure_id
     */
    private function update_structure($structure_id) {
        $agent_types = $this->input->post('agent_type') ?: array();

        $rolling_comm_basis = $this->input->post('rolling_comm_basis');
        $total_bets_except = '';
        if ('total_bets' == $rolling_comm_basis) {
            $total_bets_except = $this->input->post('except_game_type');
        }
        $this->utils->debug_log('rolling common basis ----->', $rolling_comm_basis, ' : total bets except ----->',$total_bets_except);

        $settlement_period = $this->input->post('settlement_period');
        $start_day = '';
        if ($settlement_period == 'Weekly') {
            $start_day = $this->input->post('start_day');
        }
        $today = date("Y-m-d H:i:s");

        $allowed_level_cnt =  $this->input->post('allowed_level');
        $allowed_level_names = $this->get_allowed_level_names($allowed_level_cnt);
        $vip_level = $this->input->post('vip_level');

        $selected_vip_levels = $this->input->post('selected_vip_levels');
        $this->utils->debug_log('selected_vip_levels', $selected_vip_levels, explode(',', $selected_vip_levels));
        list($player_vip_groups, $player_vip_levels) = $this->agency_library->get_player_vip_info(explode(',',$selected_vip_levels));
        $this->utils->debug_log('vip groups levels ---->', $player_vip_groups, $player_vip_levels);

        $data = array(
            'structure_name' => $this->input->post('structure_name'),
            'currency' => $this->input->post('currency'),
            'credit_limit' => $this->input->post('credit_limit'),
            'status' => $this->input->post('status'),
            'rev_share' => $this->input->post('rev_share'),
            'rolling_comm' => $this->input->post('rolling_comm'),
            'rolling_comm_basis' => $rolling_comm_basis,
            'total_bets_except' => $total_bets_except,
            'allowed_level' => $allowed_level_cnt,
            'allowed_level_names' => $allowed_level_names,
            'can_have_sub_agent' => in_array('can-have-sub-agents', $agent_types)? 1:0,
            'can_have_players' => in_array('can-have-players', $agent_types)? 1:0,
            'can_do_settlement' => in_array('can-do-settlement', $agent_types)? 1:0,
            'show_bet_limit_template' => in_array('show-bet-limit-template', $agent_types) ? 1 : 0,
            'show_rolling_commission' => in_array('show-rolling-commission', $agent_types) ? 1 : 0,
            'can_view_agents_list_and_players_list' => in_array('can-view-agents-list-and-players-list', $agent_types)? 1:0,
            'vip_level' => $vip_level,
            'settlement_period' => $settlement_period,
            'settlement_start_day' => $start_day,
            'created_on' => $today,
            'updated_on' => $today,
            'vip_groups' => implode(',', $player_vip_groups),
            'vip_levels' => implode(',', $player_vip_levels),

            'admin_fee' => $this->input->post('admin_fee'),
            'transaction_fee' => $this->input->post('transaction_fee'),
            'bonus_fee' => $this->input->post('bonus_fee'),
            'cashback_fee' => $this->input->post('cashback_fee'),
        );
        $this->utils->debug_log('verify_update_structure DATA:', $data);

        $this->agency_model->startTrans();

        $row = $this->agency_model->update_structure($structure_id, $data);
        $game_platforms = $this->input->post('game_platforms');
        $game_types = $this->input->post('game_types');
        $is_update = true;
        $this->agency_model->add_game_comm_settings($game_platforms, $game_types, $structure_id, 'structure', $is_update);

        $succ = $this->agency_model->endTransWithSucc();
        if (!$succ) {
            throw new Exception('Sorry, save structure failed.');
        }
        return $row;
    }

    /**
     *  update a structure and redirect to structure list
     *
     *  @return
     */
    public function verify_update_structure() {
        if (!$this->permissions->checkPermissions('structure_list')) {
            return $this->error_access();
        }

        $this->structure_form_rules();
        $this->form_validation->set_rules('structure_name', lang('Structure Name'),
            'trim|required|min_length[2]|max_length[12]|alpha_numeric|xss_clean');

        $structure_id = $this->input->post('structure_id');

        if ($this->form_validation->run() == false) {
            $selected_vip_levels = $this->input->post('selected_vip_levels');
            list($player_vip_groups, $player_vip_levels) =
                $this->agency_library->get_player_vip_info(explode(',',$selected_vip_levels));
            $vip_levels = implode(',', $player_vip_levels);
            $this->edit_structure($structure_id, $vip_levels);
        } else {
            $old_details = $this->agency_model->get_structure_by_id($structure_id);
            $this->utils->debug_log('verify_update_structure old_details:', $old_details, 'structure_id', $structure_id);

            $this->update_structure($structure_id);

            $structure_details = $this->agency_model->get_structure_by_id($structure_id);
            $this->utils->debug_log('verify_update_structure new_details:', $structure_details);
            $modified_fields = '';
            if(!empty($old_details) && !empty($structure_details)) {
                $modified_fields = $this->check_modified_fields($old_details, $structure_details);
            }
            $log_params = array(
                'action' => 'modify_structure',
                'link_url' => site_url('agency_management/edit_structure'). '/' . $structure_id,
                'done_by' => $this->authentication->getUsername(),
                'done_to' => $this->authentication->getUsername(),
                'details' => lang('Edit Agent Template'). ' ' . $old_details['structure_name']. ': '. $modified_fields,
            );
            $this->agency_library->save_action($log_params);
            redirect('agency_management/structure_list');
        }
    }

    /**
     * Check modified fields on player info
     *
     * @param   int
     * @param   array
     * @return  string
     */
    public function check_modified_fields($old_data, $new_data) {
        $diff = array_diff_assoc($new_data, $old_data);

        if(empty($diff)) {
            return false;
        }
        foreach ($diff as $key => $value) {
            $changes[lang('reg.fields.' . $key) ?: $key] = [
                'old' => $old_data[$key],
                'new' => $new_data[$key],
            ];
        }

        ksort($changes);

        $output = '<ul>';
        foreach ($changes as $key => $value) {
            $output .= "<li>{$key}:<br><code>Old: {$value['old']}</code><br><code>New: {$value['new']}</code></li>";
        }
        $output .= '</ul>';

        return $output;
    }

    /**
     *  remove a structure
     *
     *  @param  int structure_id
     *  @param  string structure_name
     *  @return
     */
    public function remove_structure($structure_id, $structure_name) {
        if (!$this->permissions->checkPermissions('structure_list')) {
            return $this->error_access();
        }

        $this->agency_model->remove_structure($structure_id);

        $message = lang('Remove structure') . ": " . $structure_name;
        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);

        redirect('agency_management/structure_list');
    }

    /**
     *  parse the agent name according to given structure info and agent level
     *
     *  @param  array structure_details
     *  @param  int agent_level
     *  @return string agent level name
     */
    private function get_agent_level_name($structure_details, $agent_level = 0) {
        $level_names = explode(',', $structure_details['allowed_level_names']);
        return $level_names[$agent_level];
    }

    /**
     *  check whether agent name is unique
     *
     *  @param  string
     *  @return bool
     */
    public function check_agent_name($value) {
        $query_array = $this->agency_model->get_agent_by_name($value);
        $this->utils->debug_log('check agent name', $query_array);
        if (!empty($query_array)) {
            $this->form_validation->set_message('check_agent_name', lang('Agent name has been used!'));
            return false;
        } else {
            return true;
        }
    }

    /**
     *  transfer credit(cash) from parent agent to the player
     *
     *  @param  int agent_id
     *  @return void
     */
    public function agent_hierarchical_tree($agent_id) {
        if(!$this->permissions->checkPermissions('view_agent') || empty($agent_id)) {
            return $this->error_access();
        }

        $data['agent_id'] = $agent_id;

        $this->addJsTreeToTemplate();
        $this->load->view('agency_management/agent_hierarchical_tree', $data);
    }
    /**
     * Adjust credit for a given agent
     *
     * @return  void
     */
    public function adjust_credit($agent_id) {
        if(!$this->permissions->checkPermissions('edit_agent') || empty($agent_id)) {
            return $this->error_access();
        }

            $this->load_template(lang('Adjust Credit'), '', '', 'agency');

            $data['agent_id'] = $agent_id;
            $data['agent'] = $this->agency_model->get_agent_by_id($agent_id);

            $this->template->write_view('main_content', 'agency_management/adjust_credit', $data);
            $this->template->render();
    }

    /**
     * Adjust credit for a given agent
     *
     * @return  void
     */
    public function process_adjust_credit($agent_id) {
        if(!$this->permissions->checkPermissions('edit_agent') || empty($agent_id)) {
            return $this->error_access();
        }

        $this->form_validation->set_rules('adjust_amount', lang('Adjust Amount'),
            'trim|required|greater_than[0]|xss_clean|callback_check_credit_adjust');

        if ($this->form_validation->run() == false) {
            $this->adjust_credit($agent_id);
        } else {
            $op = $this->input->post('transaction_type');
            $adjust_amount = $this->input->post('adjust_amount');
            $success = false;
            $controller = $this;
            $message=null;
            $success= $this->lockAndTrans(Utils::LOCK_ACTION_AGENCY_BALANCE , $agent_id, function()
                use ($controller, $agent_id, $op, $adjust_amount, &$message){
                    $controller->utils->debug_log('PROCESS_ADJUST_CREDIT', $agent_id, $op, $adjust_amount);
                    $agent_details = $controller->agency_model->get_agent_by_id($agent_id);
                    $parent_details = null;
                    if ($agent_details['parent_id'] > 0) {
                        $parent_details = $controller->agency_model->get_agent_by_id($agent_details['parent_id']);
                    }

                    list($success, $message) = $controller->agency_library->check_adjust_credit($op, $agent_details,
                        $parent_details, $adjust_amount);

                    if ($success) {
                        $controller->agency_library->do_adjust_credit($op, $agent_details, $parent_details, $adjust_amount);

                        // record credit transaction
                        list($trans_id, $trans_type) = $controller->agency_library->record_transaction_on_adjust($op, $agent_details, $adjust_amount);

                        // update 'balance_history'
                        $controller->agency_library->record_balance_history_on_adjust($trans_type,
                            $trans_id, $agent_details, $adjust_amount);

                        // record action in agency log
                        $action_url = site_url('agency_management/adjust_credit') . '/' . $agent_id;
                        $operator = $controller->authentication->getUsername();
                        $controller->agency_library->save_action_on_adjust_credit($action_url, $operator,
                            $op, $agent_details, $parent_details, $adjust_amount);

                        $message = lang('Successfully adjust credit.');
                    }
                    return $success;
                });
            if ($success) {
                $this->alertMessage(1, $message);
            } else {
                $this->alertMessage(2, $message); // error message
            }
            redirect("agency_management/agent_information/" . $agent_id, "refresh");
        }
    }

    /**
     *  check credit and credit_limit to assure the values are all reasonable
     *
     *  @param
     *  @return
     */
    public function check_credit_adjust() {
        $agent_id = $this->input->post('agent_id');
        $agent_details = $this->agency_model->get_agent_by_id($agent_id);
        $parent_details = null;
        if ($agent_details['parent_id'] > 0) {
            $parent_details = $this->agency_model->get_agent_by_id($agent_details['parent_id']);
        }
        $op = $this->input->post('transaction_type');
        $adjust_amount = $this->input->post('adjust_amount');
        $old_amount = $agent_details['available_credit'];
        $limit = $agent_details['credit_limit'];
        if ($op == 'add') {
            $new_credit = $old_amount + $adjust_amount;
            if ($new_credit > $limit) {
                $message = lang('Available credit cannot exceed credit limit');
                $this->form_validation->set_message('check_credit_adjust', $message);
                return false;
            }
            if ($agent_details['parent_id'] > 0) {
                if ($adjust_amount > $parent_details['available_credit']) {
                    $message = lang('No enough credit in parent agent');
                    $this->form_validation->set_message('check_credit_adjust', $message);
                    return false;
                }
            }
        } else {
            if ($adjust_amount > $old_amount) {
                $message = lang('No enough credit');
                $this->form_validation->set_message('check_credit_adjust', $message);
                return false;
            }
            if ($agent_details['parent_id'] > 0) {
                if ($adjust_amount + $parent_details['available_credit'] > $parent_details['credit_limit']) {
                    $message = lang('Exceed parent credit limit');
                    $this->form_validation->set_message('check_credit_adjust', $message);
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Adjust credit for a given agent
     *
     * @return  void
     */
    public function adjust_credit_limit($agent_id) {
        if(!$this->permissions->checkPermissions('edit_agent') || empty($agent_id)) {
            return $this->error_access();
        }

            $this->load_template(lang('Adjust Credit Limit'), '', '', 'agency');

            $data['agent_id'] = $agent_id;
            $data['agent'] = $this->agency_model->get_agent_by_id($agent_id);

            $this->template->write_view('main_content', 'agency_management/adjust_credit_limit', $data);
            $this->template->render();
    }

    /**
     * Adjust credit for a given agent
     *
     * @return  void
     */
    public function process_adjust_credit_limit($agent_id) {
        if(!$this->permissions->checkPermissions('edit_agent') || empty($agent_id)) {
            return $this->error_access();
        }

        $agent_max_credit_limit=$this->utils->getConfig('agent_max_credit_limit');

        $this->form_validation->set_rules('new_credit_limit', lang('New Credit Limit'),
            'trim|required|numeric|greater_than[0]|less_than['.$agent_max_credit_limit.']|xss_clean|callback_check_new_credit_limit');

        if ($this->form_validation->run() == false) {
            $this->adjust_credit_limit($agent_id);
        } else {
            $new_limit = $this->input->post('new_credit_limit');
            $data = array(
                'credit_limit' => $new_limit,
            );

            $this->agency_model->update_agent($agent_id, $data);

            $message = lang('Successfully Adjust Credit Limit.');
            $this->alertMessage(1, $message); //will set and send message to the user
            redirect("agency_management/agent_information/" . $agent_id, "refresh");
        }
    }

    /**
     *  check credit and credit_limit to assure the values are all reasonable
     *
     *  @param
     *  @return
     */
    public function check_new_credit_limit() {
        $current_credit = $this->input->post('current_credit');
        $new_credit_limit = $this->input->post('new_credit_limit');
        $parent_id = $this->input->post('parent_id');

        if ($current_credit > $new_credit_limit) {
            $this->form_validation->set_message('check_new_credit_limit',
                $this->labels_array['available_credit'].' '.lang('cannot exceed').' ' . $this->labels_array['credit_limit']
            );
            return false;
        } else if ($parent_id > 0) {
            $parent_details = $this->agency_model->get_agent_by_id($parent_id);
            $parent_limit  = $parent_details['credit_limit'];
            if ($new_credit_limit > $parent_limit) {
                $this->form_validation->set_message('check_new_credit_limit',
                    lang("New credit limit cannot exceed its parent's credit limit"). ' ' . $parent_limit);
                return false;
            }
        }
        return true;
    }

    public function activate_agent($agent_id) {
        if(!$this->permissions->checkPermissions('edit_agent') || empty($agent_id)) {
            return $this->error_access();
        }

            //active agent
            if ($this->agency_model->activate($agent_id, $this->authentication->getUsername(), true)) {
                $message = lang('Activated');
                $this->utils->debug_log($agent_id, $message);
                $this->alertMessage(1, $message); //will set and send message to the user
            } else {
                $message = lang('Failed to Activate Agent!');
                $this->alertMessage(2, $message); //will set and send message to the user

            }
            redirect("agency_management/agent_information/" . $agent_id);
    }

    public function inactivate_agent($agent_id) {
        if(!$this->permissions->checkPermissions('edit_agent') || empty($agent_id)) {
            return $this->error_access();
        }

            //inactivate agent
            if ($this->agency_model->inactivate($agent_id)) {
                $message = lang('Inactivated');
                $this->utils->debug_log($agent_id, $message);
                $this->alertMessage(1, $message); //will set and send message to the user
            } else {
                $message = lang('Failed to Inactivate Agent!');
                $this->alertMessage(2, $message); //will set and send message to the user

            }
            redirect("agency_management/agent_information/" . $agent_id);
    }

    public function suspend_agent($agent_id) {
        if(!$this->permissions->checkPermissions('edit_agent') || empty($agent_id)) {
            return $this->error_access();
        }

            // suspend agent
            if ($this->agency_model->suspend($agent_id, $this->authentication->getUsername(), true)) {
                $message = lang('Suspended');
                $this->utils->debug_log($agent_id, $message);
                $this->alertMessage(1, $message); //will set and send message to the user
            } else {
                $message = lang('Failed to Suspend Agent!');
                $this->alertMessage(2, $message); //will set and send message to the user

            }
            redirect("agency_management/agent_information/" . $agent_id);
    }

    public function freeze_agent($agent_id) {
        if(!$this->permissions->checkPermissions('edit_agent') || empty($agent_id)) {
            return $this->error_access();
        }

            //freeze agent
            if ($this->agency_model->freeze($agent_id, $this->authentication->getUsername(), true)) {
                $message = lang('Frozen');
                $this->utils->debug_log($agent_id, $message);
                $this->alertMessage(1, $message); //will set and send message to the user
            } else {
                $message = lang('Failed to Freeze Agent!');
                $this->alertMessage(2, $message); //will set and send message to the user
            }
            redirect("agency_management/agent_information/" . $agent_id);
    }

    public function activate_agent_array() {
        if(!$this->permissions->checkPermissions('edit_agent')) {
            return $this->error_access();
        }

            //active agent
            $agents = $_POST['agent_ids'];
            $arr = explode(',', $agents);
            $this->utils->debug_log('activate_agent_array', $agents, $arr);
            $cnt = 0;
            foreach($arr as $agent_id) {
                if ($this->agency_model->activate($agent_id, $this->authentication->getUsername(), true)) {
                    $cnt++;
                }
            }
            if ($cnt == count($arr)) {
                $message = lang('Activated'). ' ' . $cnt . ' ' . lang('Agent');
                $this->alertMessage(1, $message); //will set and send message to the user
            } else {
                $message = lang('Failed to Activate Agent!');
                $this->alertMessage(2, $message); //will set and send message to the user
            }
            redirect("agency_management/agent_list" , 'refresh');
    }

    public function suspend_agent_array() {
        if(!$this->permissions->checkPermissions('edit_agent')) {
            return $this->error_access();
        }

            //active agent
            $agents = $_POST['agent_ids'];
            $arr = explode(',', $agents);
            $this->utils->debug_log('suspend_agent_array', $agents, $arr);
            $cnt = 0;
            foreach($arr as $agent_id) {
                if ($this->agency_model->suspend($agent_id, $this->authentication->getUsername(), true)) {
                    $cnt++;
                }
            }
            if ($cnt == count($arr)) {
                $message = lang('Suspended'). ' ' . $cnt . ' ' . lang('Agent');
                $this->alertMessage(1, $message); //will set and send message to the user
            } else {
                $message = lang('Failed to Suspend Agent!');
                $this->alertMessage(2, $message); //will set and send message to the user
            }
            redirect("agency_management/agent_list" , 'refresh');
    }

    public function freeze_agent_array() {
        if(!$this->permissions->checkPermissions('edit_agent')) {
            return $this->error_access();
        }

            //active agent
            $agents = $_POST['agent_ids'];
            $arr = explode(',', $agents);
            $this->utils->debug_log('freeze_agent_array', $agents, $arr);
            $cnt = 0;
            foreach($arr as $agent_id) {
                if ($this->agency_model->freeze($agent_id, $this->authentication->getUsername(), true)) {
                    $cnt++;
                }
            }
            if ($cnt == count($arr)) {
                $message = lang('Froze'). ' ' . $cnt . ' ' . lang('Agent');
                $this->alertMessage(1, $message); //will set and send message to the user
            } else {
                $message = lang('Failed to Freeze Agent!');
                $this->alertMessage(2, $message); //will set and send message to the user
            }
            redirect("agency_management/agent_list" , 'refresh');
    }

    /**
     * modify password  page
     *
     * @param int agent_id
     * @return  void
     */
    public function reset_password($agent_id) {
        if(!$this->permissions->checkPermissions('edit_agent') || empty($agent_id)) {
            return $this->error_access();
        }

            $this->load_template(lang('Reset Password'), '', '', 'agency');

            $data['agent_id'] = $agent_id;

            $this->template->write_view('main_content', 'agency_management/reset_password', $data);
            $this->template->render();
    }

    /**
     * verify change password
     *
     * @return  void
     */
    public function verify_reset_password($agent_id) {
        if(!$this->permissions->checkPermissions('edit_agent') || empty($agent_id)) {
            return $this->error_access();
        }

        $this->form_validation->set_rules('new_password', 'New Password', 'trim|required|xss_clean');
        $this->form_validation->set_rules('confirm_new_password', 'Confirm New Password',
            'trim|required|xss_clean|matches[new_password]');

        if ($this->form_validation->run() == false) {
            $this->reset_password($agent_id);
        } else {
            $password = $this->salt->encrypt($this->input->post('new_password'), $this->getDeskeyOG());
            $data = array(
                'password' => $password,
            );

            $this->agency_model->update_agent($agent_id, $data);

            $this->load->model(['multiple_db_model']);
            $rlt=$this->multiple_db_model->syncAgencyFromCurrentToOtherMDB($agent_id, false);
            $this->utils->debug_log('syncAgencyFromCurrentToOtherMDB :'.$agent_id, $rlt);
            $message = lang('Successfully Reset Password.');
            $this->alertMessage(1, $message); //will set and send message to the user
            redirect("agency_management/agent_information/" . $agent_id, "refresh");
        }
    }

    /**
     * verify change password
     *
     * @return  void
     */
    public function reset_random_password($agent_id) {
        if(!$this->permissions->checkPermissions('edit_agent') || empty($agent_id)) {
            return $this->error_access();
        }

        $pass = $this->utils->create_random_password();
        $this->utils->debug_log('reset_random_password', $pass);
        $password = $this->salt->encrypt($pass, $this->getDeskeyOG());

        $this->agency_model->startTrans();
        $data = array(
            'password' => $password,
        );

        $this->agency_model->update_agent($agent_id, $data);
        $succ = $this->agency_model->endTransWithSucc();
        if (!$succ) {
            throw new Exception('Sorry, Reset Password Failed.');
        }

        $this->load->model(['multiple_db_model']);
        $rlt=$this->multiple_db_model->syncAgencyFromCurrentToOtherMDB($agent_id, false);
        $this->utils->debug_log('syncAgencyFromCurrentToOtherMDB :'.$agent_id, $rlt);

        $message = lang('Successfully Reset Password.');
        $this->alertMessage(1, $message); //will set and send message to the user
        $arr = array('status' => 'success', 'result' => $pass);
        $this->returnJsonResult($arr);
    }

    /**
     * add new bank account
     *
     * @return  bool
     */
    public function add_bank_account($agent_id) {
        if(!$this->permissions->checkPermissions('edit_agent') || empty($agent_id)) {
            return $this->error_access();
        }

            $this->load_template(lang('Add Bank Account'), '', '', 'agency');

            $data['agent_id'] = $agent_id;
            $data['conditions'] = $this->safeLoadParams(array(
                'bank_name' => '',
                'account_name' => '',
                'account_number' => '',
                'branch_address' => '',
            ));

            $this->template->write_view('main_content', 'agency_management/agent_bank_account', $data);
            $this->template->render();

    }

    /**
     * verify add new bank account
     *
     * @return  void
     */
    public function verify_bank_account() {
        $this->form_validation->set_rules('bank_name', 'Bank Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('account_name', 'Account Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('account_number', 'Account Number', 'trim|xss_clean|required|numeric|is_unique[agent_payment.account_number]');
        $this->form_validation->set_rules('branch_address', 'Account Info', 'trim|xss_clean|required');

        $agent_id = $this->input->post('agent_id');
        if(!$this->permissions->checkPermissions('edit_agent') || empty($agent_id)) {
            return $this->error_access();
        }

        $agent_details = $this->agency_model->get_agent_by_id($agent_id);

        if ($this->form_validation->run() == false) {
            $this->add_bank_account($agent_id);
        } else {
            $data = array(
                'agent_id' => $agent_id,
                'payment_method' => 'Wire Transfer',
                'bank_name' => $this->input->post('bank_name'),
                'branch_address' => $this->input->post('branch_address'),
                'account_name' => $this->input->post('account_name'),
                'account_number' => $this->input->post('account_number'),
                'created_on' => date('Y-m-d H:i:s'),
                'updated_on' => date('Y-m-d H:i:s'),
            );
            $this->agency_model->insert_payment($data);
            $message = lang('Successfully Added Agent Bank Information.');
            $this->alertMessage(1, $message); //will set and send message to the user
            redirect("agency_management/agent_information/" . $agent_id, "refresh");
        }
    }

    /**
     * verify add new bank account
     *
     * @return  void
     */
    public function verify_update_bank_account() {
        $this->form_validation->set_rules('bank_name', 'Bank Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('account_name', 'Account Name', 'trim|xss_clean|required');
        $this->form_validation->set_rules('account_number', 'Account Number', 'trim|xss_clean|required|numeric');
        $this->form_validation->set_rules('branch_address', 'Account Info', 'trim|xss_clean|required');

        $agent_id = $this->input->post('agent_id');
        if(!$this->permissions->checkPermissions('edit_agent') || empty($agent_id)) {
            return $this->error_access();
        }

        $payment_id = $this->input->post('agent_payment_id');
        if ($this->form_validation->run() == false) {
            $this->edit_bank_account($payment_id);
        } else {
            $data = array(
                'agent_id' => $agent_id,
                'payment_method' => 'Wire Transfer',
                'bank_name' => $this->input->post('bank_name'),
                'branch_address' => $this->input->post('branch_address'),
                'account_name' => $this->input->post('account_name'),
                'account_number' => $this->input->post('account_number'),
                'updated_on' => date('Y-m-d H:i:s'),
            );
            $this->agency_model->update_payment($payment_id, $data);
            $message = lang('Successfully Updated Agent Bank Information.');
            $this->alertMessage(1, $message); //will set and send message to the user
            redirect("agency_management/agent_information/" . $agent_id, "refresh");
        }
    }

    /**
     * add new bank account
     *
     * @return  bool
     */
    public function edit_bank_account($agent_payment_id) {
        if (!$this->permissions->checkPermissions('view_agent')) {
            $this->error_access();
        } else {
            $this->load_template(lang('Edit Bank Account'), '', '', 'agency');

            $banks = $this->agency_model->get_payment_by_id($agent_payment_id);
            $this->utils->debug_log('banks', $banks);
            $data['agent_id'] = $banks['agent_id'];
            $data['agent_payment_id'] = $agent_payment_id;
            $data['conditions'] = $this->safeLoadParams(array(
                'bank_name' => $banks['bank_name'],
                'account_name' => $banks['account_name'],
                'account_number' => $banks['account_number'],
                'branch_address' => $banks['branch_address'],
            ));

            $this->template->write_view('main_content', 'agency_management/edit_bank_account', $data);
            $this->template->render();
        }
    }

    /**
     *  settings for agency sub-system
     */
    public function save_agent_settings($agent_id) {
        if (!$this->permissions->checkPermissions('view_agent')) {
            return $this->error_access();
        }

        $set_type = $this->input->post('set_type');
        switch($set_type) {
        case 'agent_terms':
            $this->save_agent_terms($agent_id);
            $message = lang('Successfully Updated Agent Terms');
            break;
        case 'sub_agent_terms':
            $this->save_sub_agent_terms($agent_id);
            $message = lang('Successfully Updated Sub Agent Terms');
            break;
        case 'operator_settings':
        default:
            break;
        }
        $this->alertMessage(1, $message);
        redirect('agency_management/agent_information/'.$agent_id);
    }

    /**
     *  settings for agency sub-system
     */
    private function save_agent_terms($agent_id) {
        $name= 'agent_terms';

        $value = '{';
        $value .= '"total_active_players":"' .$this->input->post('total_active_players') . '",';
        $value .= '"game_providers": [' . implode(',', $this->input->post('game_providers')) . '],';
        $value .= '"min_betting":"' .$this->input->post('min_betting') . '",';
        $value .= '"min_deposit":"' .$this->input->post('min_deposit') . '"';
        $value .= '}';

        $this->agency_model->insert_or_update_terms($agent_id, $name, $value);
    }

    /**
     *  settings for agency sub-system
     */
    private function save_sub_agent_terms($agent_id) {
        $name= 'sub_agent_terms';

        $value = '{';
        $value .= '"sub_level_cnt":"' .$this->input->post('sub_level_cnt') . '",';
        $value .= '"sub_level_shares": [' . implode(',', $this->input->post('sub_level_shares')) . ']';
        $value .= '}';

        $this->agency_model->insert_or_update_terms($agent_id, $name, $value);
    }

    /**
     *  admin user login agent sub system directly
     *
     *  @param  int agent_id
     *  @return void
     */
    public function login_as_agent($agent_id) {
        if (!$this->permissions->checkPermissions('login_as_agent')) {
            $this->error_access();
        } else {
            $adminUserId = $this->authentication->getUserId();
            //get currency from agency
            $agent_details = $this->agency_model->get_agent_by_id($agent_id);
            $defaultCurrency=$agent_details['default_currency'];
            if(empty($defaultCurrency)){
                $defaultCurrency=$agent_details['currency'];
            }
            $defaultCurrency=strtolower($defaultCurrency);
            $this->utils->debug_log('getAdminTokenByCurrency', $defaultCurrency, $adminUserId);
            $token = $this->getAdminTokenByCurrency($adminUserId, $defaultCurrency);
            // record action in agency log {{{3
            $agent_name = $agent_details['agent_name'];
            $log_params = array(
                'action' => 'login_as_agent',
                'link_url' => site_url('agency_management/login_as_agent'). '/' . $agent_id,
                'done_by' => $this->authentication->getUsername(),
                'done_to' => $agent_name,
                'details' => 'Admin login as agent '. $agent_name . ' in agency UI',
            );
            $this->agency_library->save_action($log_params);
            // record action in agency log }}}3
            if ($token) {
                $url=$this->utils->getSystemUrl('agency'). '/agency/login/' . $token . '/' . $agent_id;
                if(!$this->utils->isActiveCurrency($defaultCurrency)){
                    $this->utils->appendDBToUrl($url, $defaultCurrency);
                }else{
                    $this->appendActiveDBToUrl($url);
                }
                $this->utils->debug_log('login_as_agent url', $url);
                redirect($url);
            } else {
                $this->error_access();
            }
        }
    }

    /**
     *  player report for agency sub system in which each player has a parent agent id
     *
     *  @return
     */
    public function agency_player_report() {
        if (!$this->permissions->checkPermissions('agency_player_report')) {
            $this->error_access();
        } else {

            $data['allLevels'] = $this->player_manager->getAllPlayerLevels();
            $data['export_report_permission'] = $this->permissions->checkPermissions('export_player_report');

            $this->load_template(lang('Player Report'), '', '', 'agency');
            $this->template->write_view('main_content', 'agency_management/view_player_report', $data);
            $this->template->render();
        }
    }

    /**
     *  player report for agency sub system in which each player has a parent agent id
     *
     *  @return
     */
    public function agency_game_report() {
        if (!$this->permissions->checkPermissions('agency_game_report')) {
            $this->error_access();
        } else {
            $this->load->helper('form');

            if (!$this->permissions->checkPermissions('export_report')) {
                $data['export_report_permission'] = FALSE;
            } else {
                $data['export_report_permission'] = TRUE;
            }

            $data['conditions'] = $this->safeLoadParams(array(
                'date_from' => $this->utils->getTodayForMysql(),
                'hour_from' => '00',
                'date_to' => $this->utils->getTodayForMysql(),
                'hour_to' => '23',
                'total_bet_from' => '',
                'total_bet_to' => '',
                'total_loss_from' => '',
                'total_loss_to' => '',
                'total_gain_from' => '',
                'total_gain_to' => '',
                'group_by' => '',
                'username' => '',
                'affiliate_username' => '',
                'agent_name' => '',
            ));
            $this->utils->debug_log($data['conditions']);

            $this->load_template(lang('Games Report'), '', '', 'agency');
            $this->template->write_view('main_content', 'agency_management/view_games_report', $data);
            $this->template->render();
        }
    }

    /**
     *  search and display credit transactions
     */
    public function credit_transactions() {
        if (!$this->permissions->checkPermissions('credit_transactions') || $this->utils->isEnabledFeature('agent_settlement_to_wallet'))
            return $this->error_access();

        $data['conditions'] = $this->safeLoadParams(array(
            'agent_tag' => '',
            'min_credit_amount' => '',
            'max_credit_amount' => '',
            'agent_name' => '',
            'ip' => '',
        ));

        $this->utils->debug_log($data['conditions']);

        $this->load_template(lang('Credit Transactions'), '', '', 'agency');
        $this->template->add_js('resources/js/bootstrap-switch.min.js');
        $this->template->add_css('resources/css/bootstrap-switch.min.css');
        $this->template->write_view('main_content', 'agency_management/credit_transactions', $data);
        $this->template->render();
    }

    /**
     *  record transaction when creating an agent or a sub-agent
     *
     *  @param  int parent_id  0 for level 0 agent
     *  @param  int agent_id   ID of the newly-created agent
     *  @return int transaction_id
     */
    private function record_transaction_on_creation($parent_id, $agent_id) {
        $transaction_id = null;
        $agent_details = $this->agency_model->get_agent_by_id($agent_id);
        if (!empty($agent_details['available_credit']) && $agent_details['available_credit'] > 0) {
            $name = $agent_details['agent_name'];
            $credit = $agent_details['available_credit'];
            if ($parent_id == 0) {
                // for level 0 agent which can only created by admin
                $trans_type = Transactions::FROM_ADMIN_TO_AGENT;
                $note = lang('Level 0 agent '). $name . lang(' is created').'.';
                $from_type = Transactions::ADMIN;
                $from_name = $this->authentication->getUsername();
                $from_id = $this->authentication->getUserId();
            } else {
                $parent_details = $this->agency_model->get_agent_by_id($parent_id);

                $trans_type = Transactions::FROM_AGENT_TO_SUB_AGENT;
                $from_type = Transactions::AGENT;
                $from_name = $parent_details['agent_name'];
                $from_id = $parent_id;
                $note = lang('Sub agent '). $name . lang(' is created').'.';
            }
            //$transaction_id = $this->agency_model->insert_transaction($data);
            $data = array(
                'transaction_type' => $trans_type,
                'amount' => $credit,
                'from_type' => $from_type,
                'from_id' => $from_id,
                'from_username' => $from_name,
                'to_type' => Transactions::AGENT,
                'to_id' => $agent_id,
                'to_username' => $name,
                'note' => $note,
                'before_balance' => 0,
                'after_balance' => $credit,
            );
            $transaction_id = $this->transactions->add_new_transaction($data);
        }
        return $transaction_id;
    }

    /**
     *  settlement information for agents
     */
    public function settlement($agent_name = null, $status = 'current') {
        if (!$this->permissions->checkPermissions('settlement')) {
            return $this->error_access();
        }

        if ($agent_name == null) {
            $all_agents = $this->agency_model->get_active_agents();
            foreach ($all_agents as $agent) {
                $this->agency_library->create_settlement($agent['agent_id']);
            }
            $agent_name = '';
        }

        $data['conditions'] = $this->safeLoadParams(array(
            'agent_name' => $agent_name,
            'parent_name' => '',
            'status' => $status,
        ));

        $this->utils->debug_log($data['conditions']);

        $this->load_template(lang('Settlement'), '', '', 'agency');
        $this->template->add_js('resources/js/bootstrap-switch.min.js');
        $this->template->add_css('resources/css/bootstrap-switch.min.css');
        $this->template->write_view('main_content', 'agency_management/settlement', $data);
        $this->template->render();
    }

    /**
     *  sent settlement invoice
     *
     *  @param  int number of allowed levels
     *  @return redirect to structure_list
     */
    public function settlement_send_invoice($settlement_id) {
        if (!$this->permissions->checkPermissions('settlement')) {
            return $this->error_access();
        }

        $data['settlement_id'] = $settlement_id;
        $this->load->view('agency_management/settlement_send_invoice', $data);
    }

    /**
     *  change status from 'Current' or 'Unsettled' into 'Settled'
     *
     *  @param  int settlement_id
     *  @return settlement_id
     */
    public function do_settlement($settlement_id) {
        if (!$this->permissions->checkPermissions('settlement')) {
            return $this->error_access();
        }

        $unsettled_rec = $this->agency_model->get_settlement_by_id($settlement_id);
        $agent_id = $unsettled_rec['agent_id'];
        $agent_details = $this->agency_model->get_agent_by_id($agent_id);
        $agent_name = $agent_details['agent_name'];

        $this->utils->debug_log('DO_SETTLEMENT unsettled rec', $unsettled_rec);
        // do settlement only for records in 'unsettled' status
        if (empty($unsettled_rec) || count($unsettled_rec) == 0 || $unsettled_rec['status'] != 'unsettled') {
            $message = lang('"do settlement" is only for "unsettled" records');
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
            redirect('agency_management/settlement/'. $agent_name . '/unsettled', 'refresh');
        } else {
            // update corresponding 'current' settlement record to substract the balance of this one
            $period = $unsettled_rec['settlement_period'];
            $date_from = null;
            $status = 'current';
            $current_rec = $this->agency_model->get_settlement_by_agent($agent_id, $period, $date_from, $status);
            $this->utils->debug_log('DO_SETTLEMENT current rec', $current_rec);
            if (count($current_rec) > 0) {
                $id = $current_rec[0]['settlement_id'];
                $bal = (double)$current_rec[0]['balance'] - (double)$unsettled_rec['balance'];
                if ($bal < 0.01 && $bal > -0.01) $bal = 0;
                $this->utils->debug_log('DO_SETTLEMENT new balance', $bal);
                $data = array(
                    'balance' => $bal,
                );
                $this->agency_model->update_settlement($id, $data);
            }

            // do settlement for the given record in 'unsettled' status
            $data = array(
                'status' => 'settled',
                'balance' => 0,
            );
            $this->agency_model->update_settlement($settlement_id, $data);

            $message = lang('Successfully do settlement');
            $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, $message);
            redirect('agency_management/settlement/'. $agent_name . '/settled', 'refresh');
        }
    }

    /**
     * Do WL settlement; 'Do Settlement' button
     * Also deposit to/withdraw from user account, 'flattening' account to base credit
     * (PRH-768; see also PRH-652, PRH-326)
     * @uses    POST.user_id    == agency_wl_settlement.user_id, may refer to player_id or agent_id
     * @uses    POST.date_start Start of settlement date range
     * @uses    POST.date_end   End of settlement date range
     *
     * @return  none
     */
    public function do_settlement_wl() {
        if (!$this->permissions->checkPermissions('settlement_wl')) {
            return $this->error_access();
        }

        $debug = false;
        $this->load->model(['agency_player_details']);

        $user_id = $this->input->post('user_id');
        $date_start = $this->input->post('date_start');
        $date_end = $this->input->post('date_end');

        // Read settlement group
        $wl_settlements = $this->agency_model->getWlSettlementRowGroup($user_id, $date_start, $date_end);
        $settlement_success = true;

        foreach ($wl_settlements as $wlst) {
            $controller = $this;
            $message = null;


            // Determine deposit amount: by player W/L
            $player_wl = $wlst['result_amount'];
            $player_base_credit = $this->agency_player_details->base_credit_read($wlst['user_id'], $wlst['agent_id']);
            /**
             * Flattening amount: with player's base credit given,
             * - if player wins  (W/L > 0): deduct his balance to match base credit
             *      flattening_amount = base_credit - player_wl     (result <= 0)
             * - if player loses (W/L < 0): amend him with enough cash to match base credit
             *      flattening_amount = base_credit + (-player_wl)  (result > 0)
             */
            $flattening_amount = $player_base_credit - $player_wl;

            $this->utils->debug_log("Agency::do_settlement_wl: calc flattening", 'agent', $wlst['agent_id'], 'player', $wlst['user_id'], 'base_credit', $player_base_credit, 'player wl', $player_wl, 'flattening', $flattening_amount);

            $deposit_amount = $flattening_amount;

            // Transfer credit to player (protected by system feature switch)
            $deposit_result = null;

            // Deposit_amount > 0 : agent => player transfer
            if ($wlst['user_id'] != $wlst['agent_id'] && $deposit_amount > 0) {
                // Actual deposit is protected by the system feature switch
                if($this->utils->isEnabledFeature('transfer_rolling_to_player_when_do_settlement_wl')){
                    if (!$debug) {
                        $deposit_result = $controller->agent_to_player_transfer($wlst['user_id'], $wlst['agent_id'], abs($deposit_amount));
                    }
                    $this->utils->debug_log("Agency::do_settlement_wl: transfer: agent => player", 'agent', $wlst['agent_id'], 'player', $wlst['user_id'], 'amount', abs($deposit_amount), 'result', $deposit_result);
                }
                else {
                    $deposit_result = true;
                    $this->utils->debug_log("Agency::do_settlement_wl: transfer (disabled by sys feature) : agent => player", 'agent', $wlst['agent_id'], 'player', $wlst['user_id'], 'amount', abs($deposit_amount), 'result', $deposit_result);
                }
            }
            // Deposit amount < 0 : player => agent transfer
            else if ($wlst['user_id'] != $wlst['agent_id'] && $deposit_amount < 0) {
                // Actual withdraw is protected by the system feature switch
                if($this->utils->isEnabledFeature('transfer_rolling_to_player_when_do_settlement_wl')){
                    if (!$debug) {
                        $deposit_result = $controller->player_to_agent_transfer($wlst['user_id'], $wlst['agent_id'], abs($deposit_amount));
                    }
                    $this->utils->debug_log("Agency::do_settlement_wl: transfer: player => agent", 'agent', $wlst['agent_id'], 'player', $wlst['user_id'], 'amount', abs($deposit_amount), 'result', $deposit_result);
                }
                else {
                    $deposit_result = true;
                    $this->utils->debug_log("Agency::do_settlement_wl: transfer: (disabled by sys feature) player => agent", 'agent', $wlst['agent_id'], 'player', $wlst['user_id'], 'amount', abs($deposit_amount), 'result', $deposit_result);
                }
            }
            // Skip amount=0 deposits
            else if ($wlst['user_id'] != $wlst['agent_id'] && $deposit_amount == 0) {
                $this->utils->debug_log("Agency::do_settlement_wl: transfer: skipping amount=0", 'agent', $wlst['agent_id'], 'player', $wlst['user_id'], 'amount', $deposit_amount);
                $deposit_result = true;
            }
            // For agent self
            else {
                $this->utils->debug_log("Agency::do_settlement_wl: skipping agent self", $wlst['user_id']);
                $deposit_result = true;
            }

            // Do single settlement
            if (!empty($deposit_result)) {
                if($this->utils->isEnabledFeature('agent_settlement_to_wallet')) {
                    $success =
                        $this->lockAndTransForAgencyBalance($wlst['user_id'], function () use ($controller, $wlst) {
                            $row_affected = $controller->agency_model->doSingleWlSettlement($wlst['id'], true);
                            return $row_affected > 0;
                        });
                } else {
                    $success =
                        $this->lockAndTransForAgencyCredit($wlst['user_id'], function () use ($controller, $wlst) {
                            $row_affected = $controller->agency_model->doSingleWlSettlement($wlst['id'], false);
                            return $row_affected > 0;
                        });
                }
                $this->utils->debug_log("Agency::do_settlement_wl: doSingleWlSettlement success?", $success);
                $settlement_success = $settlement_success && $success;
            }

        } // End of foreach ($wl_settlements as $wlst)

        if ($settlement_success) {
            $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Settlement record settled'));
        } else {
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Error doing settlement'));
        }
    }

    /**
     *  freeze given settlement
     *
     *  @param  int settlement_id
     *  @return void
     */
    public function freeze_settlement($settlement_id) {
        if (!$this->permissions->checkPermissions('settlement')) {
            return $this->error_access();
        }

        $data = array(
            'frozen' => '1',
        );
        $this->agency_model->update_settlement($settlement_id, $data);
        redirect('agency_management/settlement', 'refresh');
    }

    /**
     *  unfreeze given settlement
     *
     *  @param  int settlement_id
     *  @return void
     */
    public function unfreeze_settlement($settlement_id) {
        if (!$this->permissions->checkPermissions('settlement')) {
            return $this->error_access();
        }

        $data = array(
            'frozen' => '0',
        );
        $this->agency_model->update_settlement($settlement_id, $data);
        redirect('agency_management/settlement', 'refresh');
    }

    /**
     *  create invoice file and supply downloading
     *
     *  @param
     *  @return
     */
    public function invoice($settlement_id = null) {
        if (!$this->permissions->checkPermissions('settlement')) {
            return $this->error_access();
        }

        $this->load_template(lang('Invoice'), '', '', 'agency');
        $this->template->add_js('resources/js/bootstrap-switch.min.js');
        $this->template->add_css('resources/css/bootstrap-switch.min.css');
        $this->template->add_css('resources/third_party/bootstrap-multiselect-master/dist/css/bootstrap-multiselect.css');
        $this->template->add_js('resources/third_party/bootstrap-multiselect-master/dist/js/bootstrap-multiselect.js');
        $this->template->add_js('resources/js/bootstrap-notify.min.js');

        $data = array();
        if ($settlement_id) {
            $data['settlement_id'] = $settlement_id;
        }
        $this->template->write_view('main_content', 'agency_management/invoice_page', $data);
        $this->template->render();
    }

    /**
     *  get invoice data throught ajax
     *
     *  @param  input post
     *  @return array
     */
    public function get_invoice_info_ajax() {
        $request = $this->input->post();
        $this->utils->debug_log('GET_INVOICE_INFO_AJAX REQUEST', $request);
        $result = $this->agency_library->get_invoice_info($request);
        $this->utils->debug_log('GET_INVOICE_INFO_AJAX RESULT', $result);

        $arr = array('status' => 'success', 'result' => $result);
        $this->returnJsonResult($arr);
    }

    /**
     *  agency_logs information for agents
     */
    public function agency_logs() {
        if (!$this->permissions->checkPermissions('agency_logs')) {
            $this->error_access();
        }else{
            $data['conditions'] = $this->safeLoadParams(array(
                'agent_name' => '',
                'parent_agent_name' => '',
                'status' => '',
                'search_on_date' => '1' // OGP-11798: set 'search_on_date' by default
            ));
            $data['agency_actions'] =  $this->utils->unique_multidim_array($this->agency_library->log_actions, 'name');

            $this->load_template(lang('Agency Logs'), '', '', 'agency');
            $this->template->add_js('resources/js/bootstrap-switch.min.js');
            $this->template->add_css('resources/css/bootstrap-switch.min.css');
            $this->template->write_view('main_content', 'agency_management/agency_logs', $data);
            $this->template->render();
        }
    }

    /**
     *  save action into table agency_logs
     *
     *  @param  string action name
     *  @return int log_id
     */
    public function save_action_old($action, $params) {
        $method = 'save_action_old_'.$action;
        $this->utils->debug_log('in save action', $params, is_callable(array($this, $method)), is_array($params));

        if (!is_callable(array($this, $method)) || !is_array($params)) {
            echo "ERROR: parameter error for save_action_old";
            return false;
        }
        $data = call_user_func_array(array($this, $method), $params);
        $this->utils->debug_log('save action', $data);
        return $this->agency_model->insert_log($data);
    }

    /**
     *  save 'create_structure' action in agency_logs
     *
     *  @param  array for structure name
     *  @return bool true for success
     */
    private function save_action_create_structure($structure_name) {
        $action = 'create_structure';
        $user = $this->authentication->getUsername();
        $action_name = $this->log_actions[$action]['name'];
        $link_name = $this->log_actions[$action]['link_name'];
        $link_url = site_url($this->log_actions[$action]['link_url']);
        $data = array(
            'done_at' => date("Y-m-d H:i:s"),
            'done_by' => $user,
            'done_to' => $user,
            'action' => $action_name,
            'details' => 'structure_name = '.$structure_name,
            'link_name' => $link_name,
            'link_url' => $link_url,
        );
        $data['conditions'] = $this->safeLoadParams(array(
            'structure_id' => '',
            'structure_name' => '',
        ));
        $this->utils->debug_log('in save_action_create_structure', $data);

        return $data;
    }

    /**
     *  settings for agency sub-system
     */
    public function agency_setting() {
        if (!$this->permissions->checkPermissions('view_agent')) {
            return $this->error_access();
        }

        $this->load_template(lang('Agency Setting'), '', '', 'agency');

        // load game list
        $this->load->model(array('external_system'));
        $data['games'] = $this->external_system->getAllActiveSytemGameApi();
        $data['agent_terms'] = $this->agency_model->get_default_agent_terms();
        $data['sub_agent_terms'] = $this->agency_model->get_default_sub_agent_terms();
        $data['operator_settings'] = $this->agency_model->get_default_operator_settings();
        $this->utils->debug_log('default agent terms', $data['agent_terms']);
        $this->utils->debug_log('default sub agent terms', $data['sub_agent_terms']);
        $this->utils->debug_log('default operator_settings', $data['operator_settings']);

        // if post is not empty save agency settings data into DB
        if (!empty($_POST)) {
            $this->save_default_agency_settings();
        }

        $this->template->write_view('main_content', 'agency_management/agency_setting', $data);
        $this->template->render();
    }

    /**
     *  settings for agency sub-system
     */
    public function save_default_agency_settings() {
        $set_type = $this->input->post('set_type');
        switch($set_type) {
        case 'operator_settings':
            $this->save_default_operator_settings();
            $message = lang('Successfully Updated Default Operator Settings');
            break;
        case 'agent_terms':
            $this->save_default_agent_terms();
            $message = lang('Successfully Updated Default Agent Terms');
            break;
        case 'sub_agent_terms':
            $this->save_default_sub_agent_terms();
            $message = lang('Successfully Updated Default Sub Agent Terms');
            break;
        default:
            break;
        }
        $this->alertMessage(1, $message);
        redirect('agency_management/agency_setting');
    }

    /**
     *  settings for agency sub-system
     */
    private function save_default_operator_settings() {
        $name= 'default_operator_settings';

        $value = '{';
        $value .= '"level_master":"' .$this->input->post('level_master') . '",';
        $allowedFee = $this->input->post('allowed_fee');
        if (!empty($allowedFee)) {
            foreach ($allowedFee as $fee) {
                $value .= '"' . $fee. '": "' . $this->input->post($fee) . '",';
            }
        }
        $value .= '"min_monthly_pay":"' .$this->input->post('min_monthly_pay') . '",';
        $value .= '"monthly_payday":"' .$this->input->post('monthly_payday') . '"';
        $value .= '}';

        $this->agency_model->insert_or_update_default_terms($name, $value);
    }

    /**
     *  settings for agency sub-system
     */
    private function save_default_agent_terms() {
        $name= 'default_agent_terms';

        $value = '{';
        $value .= '"total_active_players":"' .$this->input->post('total_active_players') . '",';
        $value .= '"game_providers": [' . implode(',', $this->input->post('game_providers')) . '],';
        $value .= '"min_betting":"' .$this->input->post('min_betting') . '",';
        $value .= '"min_deposit":"' .$this->input->post('min_deposit') . '"';
        $value .= '}';

        $this->agency_model->insert_or_update_default_terms($name, $value);
    }

    /**
     *  settings for agency sub-system
     */
    private function save_default_sub_agent_terms() {
        $name= 'default_sub_agent_terms';

        $value = '{';
        $value .= '"sub_level_cnt":"' .$this->input->post('sub_level_cnt') . '",';
        $value .= '"sub_level_shares": [' . implode(',', $this->input->post('sub_level_shares')) . ']';
        $value .= '}';

        $this->agency_model->insert_or_update_default_terms($name, $value);
    }

	/**
	 * overview : search payment
	 *
	 * @return	void
	 */
	public function agency_payment() {
		if (!$this->permissions->checkPermissions('agency_payment')) {
			$this->error_access();
		} else {
			$this->load->model(array('agency_model'));
			$search = array(
				"agent_name" => $this->input->get('agent_name'),
				"status" => $this->input->get('status'),
			);

			$data['input'] = $this->input->get();
			if ($this->input->get('start_date') && $this->input->get('end_date')) {
				$search['request_range'] = "'" . $this->input->get('start_date') . "' AND '" . $this->input->get('end_date') . "'";
			} else {
				$search['request_range'] = "'" . date("Y-m-d 00:00:00") . "' AND '" . date("Y-m-d 23:59:59") . "'";
				$data['input']['start_date'] = date("Y-m-d 00:00:00");
				$data['input']['end_date'] = date("Y-m-d 23:59:59");
			}
			$data['input']['status'] = $this->input->get('status');
			$data['input']['agent_name'] = $this->input->get('agent_name');

			$data['status_list'] = $this->agency_model->getStatusListKV();

			$this->load_template(lang('Agency Payment'), '', '', 'agency');

			$data['payments'] = $this->agency_model->getSearchPayment(null, null, $search);

			$this->template->write_view('main_content', 'agency_management/payments/view_payments', $data);
			$this->template->render();
		}
	}

	/*
	 * overview : approved payment
	 */
	public function approve_payment() {
		if (!$this->permissions->checkPermissions('affiliate_payments')) {
			$this->error_access();
		} else {
            $this->form_validation->set_rules('reason', 'Reason', 'trim|xss_clean|htmlspecialchars');
			if($this->form_validation->run()) {

                $history_id = $this->input->post('history_id');
                $reason = $this->input->post('reason');
                $agent_id = $this->input->post('agent_id');
                $adminUserId = $this->authentication->getUserId();
                $success = !empty($history_id) && !empty($agent_id);

                if ($success) {
                    $self = $this;

                    $success = $this->lockAndTransForAgencyBalance($agent_id, function ()
                            use ($self, $history_id, $reason, $adminUserId) {
                        $success = $self->agency_model->approvePayment($history_id, $reason, $adminUserId);
                        return $success;
                    });

                }

                if ($success) {
                    $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Approved this payment'));
                } else {
                    $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('error.default.db.message'));
                }
                //go back
                $url = '/agency_management/agency_payment';
                if (isset($_SERVER['HTTP_REFERER'])) {
                    $url = $_SERVER['HTTP_REFERER'];
                }
                redirect($url);
            }

		}
	}

	/**
	 * overview : decline payment
	 */
	public function decline_payment() {
		if (!$this->permissions->checkPermissions('affiliate_payments')) {
			$this->error_access();
		} else {
            $this->form_validation->set_rules('reason', 'Reason', 'trim|xss_clean|htmlspecialchars');
			if($this->form_validation->run()) {

                $history_id = $this->input->post('history_id');
                $reason = $this->input->post('reason');
                $agent_id = $this->input->post('agent_id');
                $adminUserId = $this->authentication->getUserId();
                $success = !empty($history_id) && !empty($agent_id);

                if ($success) {

                    $this->load->model(array('agency_model'));

                    $self = $this;

                    $success = $this->lockAndTransForAgencyBalance($agent_id, function ()
                            use ($self, $agent_id, $history_id, $reason, $adminUserId) {
                        $success = $self->agency_model->declinePayment($history_id, $reason, $adminUserId);
                        $self->utils->debug_log('declinePayment:'.$history_id, $agent_id, $success);
                        return $success;
                    });
                }

                if ($success) {
                    $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Declined this payment'));
                } else {
                    $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('error.default.db.message'));
                }
                //go back
                $url = '/agency_management/agency_payment';
                if (isset($_SERVER['HTTP_REFERER'])) {
                    $url = $_SERVER['HTTP_REFERER'];
                }
                redirect($url);
            }

		}
	}

    public function settlement_wl($agent_name = null, $status = 'current') {
        if (!$this->permissions->checkPermissions('settlement_wl')) {
            return $this->error_access();
        }

        $data = $this->initSettlementWl($agent_name, $status);

        $data['is_admin'] = true;

        $this->load_template(lang('Settlement'), '', '', 'agency');
        $this->template->add_js('resources/js/bootstrap-switch.min.js');
        $this->template->add_css('resources/css/bootstrap-switch.min.css');
        $this->template->write_view('main_content', 'includes/agency_settlement_wl.php', $data);
        $this->template->render();
        return;

        $data = [];
        $data['conditions'] = $this->safeLoadParams(array(
            'agent_name' => $agent_name,
            'parent_name' => '',
            'status' => $status
        ));

        $agent = $this->agency_model->getTopAgent();

        $agent_id = $agent->agent_id;
        $agent_username = $this->input->get('agent_username');

        if(empty($agent_id)){
            $tmp_agent = $this->agency_model->get_agent_by_name($agent_username);
            $agent_id = $tmp_agent['parent_id'];
        }

        $agent = $this->agency_model->get_agent_by_id($agent_id);

        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');

        if(empty($date_from) && empty($date_to)){
            $date_to = date("Y-m-d") . " 23:59:59";
            $date_from = date('Y-m-d', strtotime("-15 day", strtotime($date_to))) . " 00:00:00";
        }

        $rows = [];
        $summary = [];

        list($agent_rows, $agent_summary) = $this->agency_model->getWsSettlement($agent_id, $agent_username, $date_from, $date_to);

        $data['rows'] = $rows;
        $data['agent_rows'] = $agent_rows;

        $data['agents'] = $this->agency_model->getAllActiveAgents();
        $data['agent_username'] = $agent_username;
        $data['agent'] = $agent;
        $data['date_from'] = $date_from;
        $data['date_to'] = $date_to;
        $data['summary'] = $summary;
        $data['agent_summary'] = $agent_summary;

        $this->load_template(lang('Settlement'), '', '', 'agency');
        $this->template->add_js('resources/js/bootstrap-switch.min.js');
        $this->template->add_css('resources/css/bootstrap-switch.min.css');
        $this->template->write_view('main_content', 'includes/agency_settlement_wl.php', $data);
        $this->template->render();
    }

    public function sync_agent_to_mdb($agent_id){
        if(!$this->permissions->checkPermissions('edit_agent') || empty($agent_id)) {
            return $this->error_access();
        }

        $this->load->model(['agency_model']);
        $username=$this->agency_model->getAgentNameById($agent_id);
        $rlt=null;
        $success=$this->syncAgentCurrentToMDBWithLock($agent_id, $username, false, $rlt);

        if(!$success){
            $errKeys=[];
            foreach ($rlt as $dbKey => $dbRlt) {
                if(!$dbRlt['success']){
                    $errKeys[]=$dbKey;
                }
            }
            $errorMessage=implode(',', $errKeys);
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Sync Agent Failed').': '.$errorMessage);
        }else{
            $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Sync Agent Successfully'));
        }

        redirect('/agency_management/agent_information/'.$agent_id);

    }

    public function regenerate_keys($agent_id, $agent_name) {
        $this->load->library('report_functions');
        $agent_data = [
            'staging_secure_key' => md5(uniqid().'stg_secure'.$agent_name),
            'staging_sign_key' => md5(uniqid().'stg_sign'.$agent_name),
            'live_secure_key' => md5(uniqid().'live_secure'.$agent_name),
            'live_sign_key' => md5(uniqid().'live_sign'.$agent_name),
        ];
        if($agent_id && $agent_name) {
            $this->agency_model->update_agent($agent_id, $agent_data);
            $this->returnJsonResult($agent_data);

            $agent_data['agent_name'] = $agent_name;
            $data = array(
                'username' => $this->authentication->getUsername(),
                'management' => 'Agency Management',
                'userRole' => $this->rolesfunctions->getRoleByUserId($this->authentication->getUserId())['roleName'],
                'action' => __FUNCTION__,
                'description' => "<pre>".json_encode($agent_data)."</pre>",
                'logDate' => date("Y-m-d H:i:s"),
                'status' => '0',
            );
            $this->report_functions->recordAction($data);
        } else {
            $this->utils->debug_log("Agent [$agent_name] not found. Agent id [$agent_id]");
        }
    }

    public function readonly_account($agentId){

        $data=[];
        if(!$this->initReadonlyAccount($agentId, $data)){
            return;
        }
        $data['returnUrl']=site_url('/agency_management/agent_information/'.$agentId);
        $data['saveAccountsUrl']=site_url('/agency_management/save_readonly_account/'.$agentId);
        $data['resetPasswordUrl']=site_url('/agency_management/reset_readonly_account_password/'.$agentId);

        $this->load_template(lang('Readonly account'), '', '', 'agency');
        $this->template->write_view('main_content', 'includes/agency_readonly_account', $data);
        $this->template->render();
    }

    /**
     *
     * save_readonly_account
     * ajax call
     *
     * @return array
     */
    public function save_readonly_account($agentId){
        $result=$this->saveReadonlyAccountAJAX($agentId);
        $this->returnJsonResult($result);
    }

    public function reset_readonly_account_password($agentId, $indexOfAccount){
        $result=$this->resetReadonlyAccountPasswordAJAX($agentId, $indexOfAccount);
        $this->returnJsonResult($result);
    }
}
