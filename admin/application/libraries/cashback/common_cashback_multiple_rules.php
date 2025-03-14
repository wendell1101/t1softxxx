<?php
/**
 * common_cashback_multiple_rules.php
 *
 * @author Elvis Chen
 */
class Common_Cashback_multiple_rules {
    const COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_PLATFORM = 'game_platform';
    const COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TYPE = 'game_type';
    const COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME = 'game';
    const COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG = 'game_tag';

    const CALC_MODE_BY_TIER_IN_GAME_TAG = 2;
    const CALC_MODE_BY_HEAP_IN_GAME_TAG = 3;

    /* @var BaseController */
    public $CI;

    /* @var Group_level */
    public $group_level;

    /* @var Game_type_model */
    public $game_type_model;

    /* @var Game_description_model */
    public $game_description_model;

    /* @var Common_cashback_multiple_range_templates_model */
    public $common_cashback_multiple_range_templates_model;

    /* @var Common_cashback_multiple_range_settings_model */
    public $common_cashback_multiple_range_settings_model;

    /* @var Common_cashback_multiple_range_rules_model */
    public $common_cashback_multiple_range_rules_model;

    public function __construct(){
        $this->CI =& get_instance();

        $this->CI->load->model([ 'group_level'
                                , 'game_type_model'
                                , 'game_description_model'
                                , 'common_cashback_multiple_range_templates_model'
                                , 'common_cashback_multiple_range_settings_model'
                                , 'common_cashback_multiple_range_rules_model'
                                , 'game_tags' ]);

        $this->group_level = $this->CI->group_level;
        $this->game_type_model = $this->CI->game_type_model;
        $this->game_tags = $this->CI->game_tags;
        $this->game_description_model = $this->CI->game_description_model;
        $this->common_cashback_multiple_range_templates_model = $this->CI->common_cashback_multiple_range_templates_model;
        $this->common_cashback_multiple_range_settings_model = $this->CI->common_cashback_multiple_range_settings_model;
        $this->common_cashback_multiple_range_rules_model = $this->CI->common_cashback_multiple_range_rules_model;

        $this->cashback_common_settings = NULL;
        $this->cashback_settings_with_game_data = NULL;
        $this->cashback_settings_with_game_tag_data = NULL;
        $this->all_vip_level = NULL;
        $this->level_cashback_map = NULL;
    }

    protected function _sortCashbackRules(&$cashback_rules){
        uasort($cashback_rules, function($entry_a, $entry_b){
            if($entry_a['min_bet_amount'] == $entry_b['min_bet_amount']){
                return 0;
            }
            return ($entry_a['min_bet_amount'] > $entry_b['min_bet_amount']) ? 1 : -1;
        });

        return $cashback_rules;
    }

    protected function _formateCashbackRule(&$rule_entry){
        $rule_entry['min_bet_amount'] = number_format((float)$rule_entry['min_bet_amount'], 0, '.', '');
        $rule_entry['max_bet_amount'] = number_format((float)$rule_entry['max_bet_amount'], 0, '.', '');
        $rule_entry['cashback_percentage'] = number_format((float)$rule_entry['cashback_percentage'], 3, '.', '');
        $rule_entry['max_cashback_amount'] = number_format((float)$rule_entry['max_cashback_amount'], 0, '.', '');

        if(!empty($rule_entry['min_bet_amount'])){
            $rule_entry['min_bet_amount_text'] = $this->CI->utils->formatCurrency($rule_entry['min_bet_amount'], TRUE, TRUE, FALSE);
        }else{
            $rule_entry['min_bet_amount_text'] = lang('Unlimited');
        }

        if(!empty($rule_entry['max_bet_amount'])){
            $rule_entry['max_bet_amount_text'] = $this->CI->utils->formatCurrency($rule_entry['max_bet_amount'], TRUE, TRUE, FALSE);
        }else{
            $rule_entry['max_bet_amount_text'] = lang('Unlimited');
        }

        $rule_entry['max_cashback_amount_text'] = $this->CI->utils->formatCurrency($rule_entry['max_cashback_amount'], TRUE, TRUE, FALSE);
    }

    protected function _match_rule($cashback_rules, $bet_amount){
        $match_rule = FALSE;

        $bet_amount = (float)$bet_amount;

        foreach($cashback_rules as $cashback_rule){
            if($bet_amount >= $cashback_rule['min_bet_amount']){
                if(empty($cashback_rule['max_bet_amount'])){
                    $match_rule = $cashback_rule;
                }elseif($bet_amount < $cashback_rule['max_bet_amount']){
                    $match_rule = $cashback_rule;
                }else{
                    continue;
                }
            }
        }

        return $match_rule;
    }

    public function init_caculate_cashback_require_data(){
        if( empty($this->cashback_common_settings) ){
            $this->cashback_common_settings = $this->group_level->getCashbackSettings();
        }

        if( empty($this->cashback_settings_with_game_data) ){
            $template = $this->getTemplate(null, 'Default'); // for Default template

            if(empty($template)){
                $this->CI->utils->debug_log(__METHOD__ . '(): invalid cashback template.');
                return FALSE;
            }

            $this->cashback_settings_with_game_data = $this->_getTemplateRulesWithGameData($template['cb_mr_tpl_id']);
        }

        if( empty($this->cashback_settings_with_game_tag_data) ){
            $gameTagTemplate = $this->getGameTagTemplate();  // for game_tag template
            if(empty($gameTagTemplate)){
                $this->CI->utils->debug_log(__METHOD__ . '(): invalid cashback template for game tag.');
                return FALSE;
            }

            list($_game_tag_data, $_game_type_data, $_game_data, $total_rows) = $this->_getTemplateRulesWithGameTagData($gameTagTemplate['cb_mr_tpl_id']);
            $this->cashback_settings_with_game_tag_data = $_game_tag_data;
            // $this->CI->utils->debug_log('131.total_rows:', $total_rows, '_game_tag_data:', $_game_tag_data);
        }

        if( empty($this->all_vip_level) ){
            $this->all_vip_level = $this->group_level->getGroupLevelList();
        }

        if( empty($this->level_cashback_map) ){
            $this->level_cashback_map = $this->group_level->getFullCashbackPercentageMap();
        }

        return $this;
    } // EOF init_caculate_cashback_require_data

    public function getDefaultSettingTemplate(){
        $default_setting_template = [
            "tpl_id" => false,
            "type" => false,
            "type_map_id" => false,
            "enabled_cashback" => false,
            "enabled_tier_calc_cashback" => false,
            "created_at" => false,
            "updated_at" => false
        ];
        return $default_setting_template;
    }


    public function getGameTagActiveTemplate(){
        $active_template = $this->common_cashback_multiple_range_templates_model->getGameTagActiveTemplate(Common_Cashback_multiple_rules::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG);

        return $active_template;
    }

    public function getActiveTemplate($template_name = 'Default'){
        $active_template = $this->common_cashback_multiple_range_templates_model->getActiveTemplate($template_name);

        return $active_template;
    }

    public function getGameTagTemplate($tpl_id = NULL){
        if(is_array($tpl_id)){
            return $tpl_id;
        }
        if(empty($tpl_id)){
            $template = $this->getGameTagActiveTemplate();

            if(empty($template)){
                return FALSE;
            }
        }else{
            $template = $this->common_cashback_multiple_range_templates_model->getGameTagTemplate($tpl_id);
        }
        if(empty($template)){
            return FALSE;
        }

        return $template;
    }// EOF getGameTagTemplate

    /**
     * Get a Template By tpl_id
     *
     * @param int $tpl_id
     * @param string $template_name The template name
     *
     * @return bool|null
     */
    public function getTemplate($tpl_id = NULL, $template_name = 'Default'){

        if(empty($tpl_id)){
            $template = $this->getActiveTemplate($template_name);
        }else{
            $template = $this->common_cashback_multiple_range_templates_model->getTemplate($tpl_id);
        }

        if(empty($template)){
            return FALSE;
        }

        return $template;
    }

    /**
     * Save the data into the common_cashback_multiple_range_settings
     *
     * @param {integer} $tpl_id The field, "tpl_id" in common_cashback_multiple_range_settings.
     * @param {string} $type The field, "type" in common_cashback_multiple_range_settings
     * @param {integer} $type_map_id The field, "type_map_id" in common_cashback_multiple_range_settings
     * @param {array} $data Other field data {string} The others key-value format,
     * The param name should be the field name and the value should be the field value in the data-table,"common_cashback_multiple_range_settings".
     * @param {array} $traceResult The message for trace results of the method.
     * @return boolean If it is true, that means saved(create/update) completed.
     */
    public function saveSetting($tpl_id, $type, $type_map_id, $data, &$traceResult){
        $traceResult = [];

        if(empty($tpl_id) || empty($type) || empty($type_map_id)){
            $return_message = 'The params, tpl_id, type and type_map_id, that has empty. (216)';
            $traceResult['message'] = $return_message;
            return FALSE;
        }

        if(FALSE === $this->common_cashback_multiple_range_templates_model->hasTemplate($tpl_id)){
            $return_message = 'The params, tpl_id No return true in the return of hasTemplate(). (222)';
            $traceResult['message'] = $return_message;
            return FALSE;
        }

        $result = $this->common_cashback_multiple_range_settings_model->saveSettingByFields($tpl_id, $type, $type_map_id, $data);

        if(!$result){
            $return_message = 'The return of saveSetting() is false. (230)';
            $traceResult['message'] = $return_message;
            return FALSE;
        }else{
            $traceResult['message'] = 'Save completed.';
            return TRUE;
        }

    }// EOF saveSetting

    public function saveSettings($tpl_id, $type, $type_map_id, $enabled_cashback, &$traceResult){
        $traceResult = [];

        if(empty($tpl_id) || empty($type) || empty($type_map_id)){
            $return_message = 'The params, tpl_id, type and type_map_id, that has empty. (214)';
            $traceResult['message'] = $return_message;
            return FALSE;
        }

        if(FALSE === $this->common_cashback_multiple_range_templates_model->hasTemplate($tpl_id)){
            $return_message = 'The params, tpl_id No return true in the return of hasTemplate(). (219)';
            $traceResult['message'] = $return_message;
            return FALSE;
        }

        $result = $this->common_cashback_multiple_range_settings_model->saveSetting($tpl_id, $type, $type_map_id, $enabled_cashback);
        if(!$result){
            $return_message = 'The return of saveSetting() is false. (225)';
            $traceResult['message'] = $return_message;
            return FALSE;
        }

        if($type === static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG){
            return TRUE;
        }else if($type === static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME){
            return TRUE;
        }


        // get template_name by $tpl_id for $template_name
        $template = $this->common_cashback_multiple_range_templates_model->getTemplate($tpl_id);
        $template_name = $template['template_name'];

        $active_template_rules = $this->getTemplateRulesWithTree($tpl_id, $template_name); // @todo performance issues

        if(empty($active_template_rules)){
            $return_message = 'The active_template_rules is empty(). (282)';
            $traceResult['message'] = $return_message;
            return FALSE;
        }
        $return_mgsList = [];
        if($type === static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_PLATFORM){
            foreach($active_template_rules['settings'] as $game_platform_id => $game_platform_data){
                if($type_map_id != $game_platform_id){
                    continue;
                }

                foreach($game_platform_data['types'] as $game_type_id => $game_type_data){
                    $rlt = $this->common_cashback_multiple_range_settings_model->saveSetting($tpl_id, static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TYPE, $game_type_id, $enabled_cashback);
                    $return_mgsList[] = [ 'method' => 'common_cashback_multiple_range_settings_model::saveSetting()'
                                        , 'params' => [$tpl_id, static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TYPE, $game_type_id, $enabled_cashback]
                                        , 'rlt' => $rlt
                                        , 'lineNo' => __LINE__
                                    ];
                    foreach($game_type_data['game_list'] as $game_description_id => $game_data){
                        $rlt = $this->common_cashback_multiple_range_settings_model->saveSetting($tpl_id, static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME, $game_description_id, $enabled_cashback);
                        $return_mgsList[] = [ 'method' => 'common_cashback_multiple_range_settings_model::saveSetting()'
                                        , 'params' => [$tpl_id, static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME, $game_description_id, $enabled_cashback]
                                        , 'rlt' => $rlt
                                        , 'lineNo' => __LINE__
                                    ];
                    }
                }
            }
        }elseif($type === static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TYPE){
            foreach($active_template_rules['settings'] as $game_platform_id => $game_platform_data){
                foreach($game_platform_data['types'] as $game_type_id => $game_type_data){
                    if($type_map_id != $game_type_id){
                        continue;
                    }

                    foreach($game_type_data['game_list'] as $game_description_id => $game_data){
                        $rlt = $this->common_cashback_multiple_range_settings_model->saveSetting($tpl_id, static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME, $game_description_id, $enabled_cashback);
                        $return_mgsList[] = [ 'method' => 'common_cashback_multiple_range_settings_model::saveSetting()'
                                        , 'params' => [$tpl_id, static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME, $game_description_id, $enabled_cashback]
                                        , 'rlt' => $rlt
                                        , 'lineNo' => __LINE__
                                    ];
                    }
                }
            }
        }

        $return_message = 'The active_template_rules is empty(). (329)';

        $traceResult['message'] = $return_message;
        $traceResult['mgsList'] = $return_mgsList;
        return FALSE;
    }

    public function createRule($tpl_id, $type, $type_map_id, $min_bet_amount, $max_bet_amount, $cashback_percentage, $max_cashback_amount){
        if(empty($tpl_id) || empty($type) || empty($type_map_id)){
            return FALSE;
        }

        if(FALSE === $this->common_cashback_multiple_range_templates_model->hasTemplate($tpl_id)){
            return FALSE;
        }

        $result = $this->common_cashback_multiple_range_rules_model->createRule($tpl_id, $type, $type_map_id, $min_bet_amount, $max_bet_amount, $cashback_percentage, $max_cashback_amount);
        if(!$result){
            return FALSE;
        }

        return TRUE;
    }

    public function updateRule($rule_id, $min_bet_amount, $max_bet_amount, $cashback_percentage, $max_cashback_amount){
        if(empty($rule_id)){
            return FALSE;
        }

        if(FALSE === $this->common_cashback_multiple_range_rules_model->hasRuleById($rule_id)){
            return FALSE;
        }

        $result = $this->common_cashback_multiple_range_rules_model->updateRuleById($rule_id, $min_bet_amount, $max_bet_amount, $cashback_percentage, $max_cashback_amount);
        if(!$result){
            return FALSE;
        }

        return TRUE;
    }

    public function deleteRule($rule_id){
        if(empty($rule_id)){
            return FALSE;
        }

        if(FALSE === $this->common_cashback_multiple_range_rules_model->hasRuleById($rule_id)){
            return FALSE;
        }

        $result = $this->common_cashback_multiple_range_rules_model->deleteRuleById($rule_id);
        if(!$result){
            return FALSE;
        }

        return TRUE;
    }

    protected function _getTemplateSettingsLimitByGameTagIdList($tpl_id, $game_tag_id_list=[]){
        return $this->_getTemplateSettings($tpl_id, [], $game_tag_id_list);
    }// EOF _getTemplateSettingsLimitByGameTagIdList

    /**
     * Generate cashback_setting Limit game_platform_id list
     *
     * @param integer $tpl_id The field, common_cashback_multiple_range_templates.cb_mr_tpl_id .
     * @param array $game_platform_id_list The strrings under game_platform_id list.
     * @return array The array for game_platform, game_type and game_description.
     */
    protected function _getTemplateSettingsLimitByGamePlatformIdList($tpl_id, $game_platform_id_list=[]){
        return $this->_getTemplateSettings($tpl_id, $game_platform_id_list);
    }// EOF _getTemplateSettingsLimitByGamePlatformIdList

    /**
     * Generate cashback_settings_list for game_platform/game_type/game_description from database.
     * @param integer $tpl_id The field, common_cashback_multiple_range_templates.cb_mr_tpl_id .
     * @param array $game_platform_id_list The strings under game_platform_id list.
     * @param array $game_tag_id_list The strings under game_tag_id list.
     * @return array The array cashback_settings list for game_platform, game_type and game_description.
     */
    protected function _getTemplateSettings( $tpl_id
                                            , $game_platform_id_list = []
                                            , $game_tag_id_list = []
    ){

        if( empty($game_platform_id_list) && empty($game_tag_id_list) ){
            $all_cashback_settings = $this->common_cashback_multiple_range_settings_model->getAllSettingsByTplId($tpl_id);
        }else if( !empty($game_platform_id_list) ){
            // 改成從 game_platform 撈取，tpl_id 固定需要。 getAllSettingsByTplIdLimitGamePlatformList
            $all_cashback_settings = $this->common_cashback_multiple_range_settings_model->getAllSettingsByTplIdLimitGamePlatformList($tpl_id, $game_platform_id_list);
        }else if( !empty($game_tag_id_list) ){
            $all_cashback_settings = $this->common_cashback_multiple_range_settings_model->getAllSettingsByTplIdLimitGameTagList($tpl_id, $game_tag_id_list);
        }

        $game_platform_cashback_settings_list = [];
        $game_type_cashback_settings_list = [];
        $game_cashback_settings_list = [];
        $game_tag_cashback_settings_list = [];

        if(!empty($all_cashback_settings)){
            foreach($all_cashback_settings as $setting_entry){
                $setting_entry['enabled_cashback'] = !!$setting_entry['enabled_cashback'];
                $setting_entry['enabled_tier_calc_cashback'] = !!$setting_entry['enabled_tier_calc_cashback'];
                switch($setting_entry['type']){
                    case static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_PLATFORM:
                        $game_platform_cashback_settings_list[$setting_entry['type_map_id']] = $setting_entry;
                        break;
                    case static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TYPE:
                        $game_type_cashback_settings_list[$setting_entry['type_map_id']] = $setting_entry;
                        break;
                    case static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME:
                        $game_cashback_settings_list[$setting_entry['type_map_id']] = $setting_entry;
                        break;
                    case static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG:
                        $game_tag_cashback_settings_list[$setting_entry['type_map_id']] = $setting_entry;
                        break;
                }
            }
        }
        /// OGP-16123
        // Patch for Fatal Error (E_ERROR): Allowed memory size exhausted.
        $all_cashback_settings = null;
        unset($all_cashback_settings);

$this->CI->utils->debug_log('end _getTemplateSettings.memory_get_usage:'. round(memory_get_usage()/1024) );

        return [$game_platform_cashback_settings_list, $game_type_cashback_settings_list, $game_cashback_settings_list, $game_tag_cashback_settings_list];
    } // EOF _getTemplateSettings

    protected function _getTemplateRulesLimitByGameTagIdList($tpl_id, $game_tag_id_list = []){
        return $this->_getTemplateRules($tpl_id, [], $game_tag_id_list );
    }

    /**
     * Generate cashback_rules_list for game_platform/game_type/game_description from database.
     * @param integer $tpl_id The field, common_cashback_multiple_range_templates.cb_mr_tpl_id .
     * @param array $game_platform_id_list The strrings under game_platform_id list.
     * @return array The array, cashback_rules list for game_platform, game_type and game_description.
     */
    protected function _getTemplateRulesLimitByGamePlatformIdList($tpl_id, $game_platform_id_list = []){
        return $this->_getTemplateRules($tpl_id, $game_platform_id_list );
    }
    protected function _getTemplateRules( $tpl_id
                                        , $game_platform_id_list = []
                                        , $game_tag_id_list = []
    ){
        if( empty($game_platform_id_list ) && empty($game_tag_id_list ) ){
            $all_cashback_rules = $this->common_cashback_multiple_range_rules_model->getAllRulesByTplId($tpl_id);
        }else if( ! empty($game_platform_id_list ) ){
            $all_cashback_rules = $this->common_cashback_multiple_range_rules_model->getAllRulesByTplIdLimitGamePlatformList($tpl_id, $game_platform_id_list);
        }else if( ! empty($game_tag_id_list ) ){
            $all_cashback_rules = $this->common_cashback_multiple_range_rules_model->getAllRulesByTplIdLimitGameTagList($tpl_id, $game_tag_id_list);
        }

        $game_platform_cashback_rules_list = [];
        $game_type_cashback_rules_list = [];
        $game_cashback_rules_list = [];
        $game_tag_cashback_rules_list = [];
        if(!empty($all_cashback_rules)){
            foreach($all_cashback_rules as $rule_entry){
                $this->_formateCashbackRule($rule_entry);
                switch($rule_entry['type']){
                    case static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_PLATFORM:
                        $game_platform_cashback_rules_list[$rule_entry['type_map_id']]['rule_id_' . $rule_entry['cb_mr_rule_id']] = $rule_entry;
                        break;
                    case static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TYPE:
                        $game_type_cashback_rules_list[$rule_entry['type_map_id']]['rule_id_' . $rule_entry['cb_mr_rule_id']] = $rule_entry;
                        break;
                    case static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME:
                        $game_cashback_rules_list[$rule_entry['type_map_id']]['rule_id_' . $rule_entry['cb_mr_rule_id']] = $rule_entry;
                        break;
                    case static::COMMON_CASHBACK_MULTIPLE_RANGE_TYPE_GAME_TAG:
                        $game_tag_cashback_rules_list[$rule_entry['type_map_id']]['rule_id_' . $rule_entry['cb_mr_rule_id']] = $rule_entry;
                        break;
                }
            }
        }
        /// OGP-16123
        // Patch for Fatal Error (E_ERROR): Allowed memory size exhausted.
        $all_cashback_rules = null;
        unset($all_cashback_rules);

        return [$game_platform_cashback_rules_list, $game_type_cashback_rules_list, $game_cashback_rules_list, $game_tag_cashback_rules_list];
    } // EOF _getTemplateRules

    /**
     *
     * OGP-16123 add pagination
     *
     * @param integer $tpl_id The field, common_cashback_multiple_range_templates.cb_mr_tpl_id .
     * @param integer $offset The offset of start data.
	 * @param integer $amountPerPage The data amount per page.
     * @return array The array, cashback_settings list for game_platform, game_type and game_description.
     */
    protected function _getTemplateRulesWithGameData($tpl_id, $offset = null, $amountPerPage = null){
        $total_rows = 0; // for game_platform
        $this->CI->utils->debug_log('start _getTemplateRulesWithGameData.memory_get_usage:'. round(memory_get_usage()/1024) );
        $default_setting_template = $this->getDefaultSettingTemplate();

        // pagination by game_platform list.
        $game_platform_id_list = [];
        $active_only = true;
        $game_map = $this->CI->utils->getGameSystemMap($active_only, $offset, $amountPerPage, $total_rows);

        if( !empty($game_map) ){
            $game_platform_id_list = array_keys($game_map);
        }
        $template_settings = $this->_getTemplateSettingsLimitByGamePlatformIdList($tpl_id, $game_platform_id_list);
        list($game_platform_cashback_settings_list, $game_type_cashback_settings_list, $game_cashback_settings_list) = $template_settings;

        $template_rules = $this->_getTemplateRulesLimitByGamePlatformIdList($tpl_id, $game_platform_id_list);
        // $template_rules = $this->getAllRulesByTplIdLimitGamePlatformList($tpl_id, $game_platform_id_list);
        list($game_platform_cashback_rules_list, $game_type_cashback_rules_list, $game_cashback_rules_list) = $template_rules;
        $game_type_raw_list = [];
        if( !empty($game_map) ){
            $game_type_raw_list = $this->game_type_model->getAllGameTypeList($game_platform_id_list);
        }
        $game_type_id_list = [];
        if( !empty($game_type_raw_list) ){
            $this->CI->load->library(['og_utility']);
            $game_type_id_list = $this->CI->og_utility->array_pluck($game_type_raw_list, 'id');
        }
        $game_raw_list = [];
        if( !empty($game_type_id_list) ){
            $game_raw_list = $this->game_description_model->getAllGameDescriptionListWithGameTypeIdList($game_type_id_list);
        }

        $cashback_settings_list_by_game_platform = [];
        $cashback_settings_list_by_game_type = [];
        $cashback_settings_list_by_game = [];
        foreach($game_map as $game_platform_id => $game_platform_name){
            $entry = [
                'game_platform_id' => $game_platform_id,
                'name' => lang($game_platform_name),
                'cashback_settings' => (isset($game_platform_cashback_settings_list[$game_platform_id])) ? $game_platform_cashback_settings_list[$game_platform_id] : $default_setting_template,
                'cashback_rules' => (isset($game_platform_cashback_rules_list[$game_platform_id])) ? $game_platform_cashback_rules_list[$game_platform_id] : [],
            ];

            if(!empty($entry['cashback_rules'])){
                $this->_sortCashbackRules($entry['cashback_rules']);
            }

            $cashback_settings_list_by_game_platform[$game_platform_id] = $entry;
        }

        /// OGP-16123
        // Patch for Fatal Error (E_ERROR): Allowed memory size exhausted.
        $game_map = null;
        unset($game_map);
        foreach($game_type_raw_list as $game_type_entry){

            /// filter the $game_type_entry of the inactive game platform
            if(!isset($cashback_settings_list_by_game_platform[$game_type_entry['game_platform_id']])){
                continue;
            }

            $game_type_entry['game_type'] = lang($game_type_entry['game_type']);
            $game_type_entry['game_type_lang'] = lang($game_type_entry['game_type_lang']);

            $entry = [
                'game_type_id' => $game_type_entry['id'],
                'game_platform_id' => $game_type_entry['game_platform_id'],
                'name' => lang($game_type_entry['game_type_lang']),
                'details' => $game_type_entry,
                'cashback_settings' => (isset($game_type_cashback_settings_list[$game_type_entry['id']])) ? $game_type_cashback_settings_list[$game_type_entry['id']] : $default_setting_template,
                'cashback_rules' => (isset($game_type_cashback_rules_list[$game_type_entry['id']])) ? $game_type_cashback_rules_list[$game_type_entry['id']] : [],
            ];

            if(!empty($entry['cashback_rules'])){
                $this->_sortCashbackRules($entry['cashback_rules']);
            }
            $cashback_settings_list_by_game_type[$game_type_entry['id']] = $entry;
        }

        /// OGP-16123
        // Patch for Fatal Error (E_ERROR): Allowed memory size exhausted.
        $game_type_raw_list = null;
        unset($game_type_raw_list);
        foreach($game_raw_list as $game_description_entry){

            /// filter the $game_description_entry of the inactive game platform
            if(!isset($cashback_settings_list_by_game_platform[$game_description_entry['game_platform_id']])){
                continue;
            }

            /// filter the $game_description_entry of the inactive game type.
            // The inactive attr. is reference to $cashback_settings_list_by_game_platform.
            if(!isset($cashback_settings_list_by_game_type[$game_description_entry['game_type_id']])){
                continue;
            }

            $game_type_entry = &$cashback_settings_list_by_game_type[$game_description_entry['game_type_id']];

            $game_description_entry['game_name'] = lang($game_description_entry['game_name']);

            $entry = [
                'game_id' => $game_description_entry['id'],
                'game_type_id' => $game_description_entry['game_type_id'],
                'game_platform_id' => $game_description_entry['game_platform_id'],
                'name' => $game_description_entry['game_name'],
                'details' => $game_description_entry,
                'cashback_settings' => (isset($game_cashback_settings_list[$game_description_entry['id']])) ? $game_cashback_settings_list[$game_description_entry['id']] : $default_setting_template,
                'cashback_rules' => (isset($game_cashback_rules_list[$game_description_entry['id']])) ? $game_cashback_rules_list[$game_description_entry['id']] : [],
            ];

            if(!empty($entry['cashback_rules'])){
                $this->_sortCashbackRules($entry['cashback_rules']);
            }

            if(!isset($game_cashback_settings_list[$game_description_entry['id']]) && $game_type_entry['details']['auto_add_to_cashback']){
                // @todo 亡羊補牢 新增 cashback_settings 記錄，自動啟用反水。
                //
                $entry['cashback_settings']['enabled_cashback'] = $game_type_entry['cashback_settings']['enabled_cashback'];
            }

            $cashback_settings_list_by_game[$game_description_entry['id']] = $entry;
        }

        /// OGP-16123
        // Patch for Fatal Error (E_ERROR): Allowed memory size exhausted.
        $game_raw_list = null;
        unset($game_raw_list);

$this->CI->utils->debug_log('end _getTemplateRulesWithGameData.memory_get_usage:'. round(memory_get_usage()/1024) );
        return [$cashback_settings_list_by_game_platform, $cashback_settings_list_by_game_type, $cashback_settings_list_by_game, $total_rows];
    }// EOF _getTemplateRulesWithGameData

    // ref. to _getTemplateRulesWithGameData
    public function _getTemplateRulesWithGameTagData($tpl_id, $offset = null, $amountPerPage = null){
        $this->CI->utils->debug_log('start _getTemplateRulesWithGameTagData.memory_get_usage:'. round(memory_get_usage()/1024) );
        $total_rows = 0; // for game_platform

        /// Hidden the games under the tag
        // For the issue,Fatal Error (E_ERROR): Allowed memory size of 157286400 bytes exhausted
        $isHideTheGamesUnderTag = true;

        $default_setting_template = $this->getDefaultSettingTemplate();

        // // pagination by game_platform list.
        // $game_platform_id_list = [];
        // $active_only = true;
        // $game_map = $this->CI->utils->getGameSystemMap($active_only, $offset, $amountPerPage, $total_rows);
        // pagination by game_tag list.
        $game_tag_map = $this->CI->utils->getGameTagMap($offset, $amountPerPage, $total_rows);

        $game_tag_id_list = [];
        if( !empty($game_tag_map) ){
            $game_tag_id_list = array_keys($game_tag_map);
        }

        // $template_settings = $this->_getTemplateSettingsLimitByGamePlatformIdList($tpl_id, $game_platform_id_list);
        $template_settings = $this->_getTemplateSettingsLimitByGameTagIdList($tpl_id, $game_tag_id_list);

        list($game_platform_cashback_settings_list
        , $game_type_cashback_settings_list
        , $game_cashback_settings_list
        , $game_tag_cashback_settings_list ) = $template_settings;

        unset($game_platform_cashback_settings_list);
        unset($template_settings);

        $template_rules = $this->_getTemplateRulesLimitByGameTagIdList($tpl_id, $game_tag_id_list);
        list($game_platform_cashback_rules_list
        , $game_type_cashback_rules_list
        , $game_cashback_rules_list
        , $game_tag_cashback_rules_list) = $template_rules;

        $game_tag_raw_list = [];
        if( !empty($game_tag_map) ){
            // getAllGameTags
            $game_tag_raw_list = $this->game_tags->getAllGameTagsWithPagination($offset, $amountPerPage, $total_rows);
        }

        $cashback_settings_list_by_game_tag = [];
        $cashback_settings_list_by_game_type = [];
        $cashback_settings_list_by_game = [];
        foreach($game_tag_map as $game_tag_id => $game_tag_code){

            $_game_tag_raw = $this->game_tags->getGameTagWithId($game_tag_id);

            $entry = [
                'game_tag_id' => $game_tag_id, // 'game_platform_id' => $game_tag_id,
                'name' => lang($_game_tag_raw['tag_name']),
                'cashback_settings' => (isset($game_tag_cashback_settings_list[$game_tag_id])) ? $game_tag_cashback_settings_list[$game_tag_id] : $default_setting_template,
                'cashback_rules' => (isset($game_tag_cashback_rules_list[$game_tag_id])) ? $game_tag_cashback_rules_list[$game_tag_id] : [],
            ];

            if(!empty($entry['cashback_rules'])){
                $this->_sortCashbackRules($entry['cashback_rules']);
            }

            $cashback_settings_list_by_game_tag[$game_tag_id] = $entry;
        }

        if( !empty($game_tag_map) ){
            $game_tag_id_list = array_keys($game_tag_map);
        }
        $game_type_raw_list = [];
        if( !empty($game_tag_map) ){
            $game_type_raw_list = $this->game_type_model->getAllGameTypeListWithTag($game_tag_id_list);
        }
        $game_type_id_list = [];
        if( !empty($game_type_raw_list) ){
            $this->CI->load->library(['og_utility']);
            $game_type_id_list = $this->CI->og_utility->array_pluck($game_type_raw_list, 'id');
            $game_tag_id_list = $this->CI->og_utility->array_pluck($game_type_raw_list, 'game_tag_id');

        }
        $game_raw_list = [];
        if( !empty($game_type_id_list) ){
            // $game_raw_list = $this->game_description_model->getAllGameDescriptionListWithGameTypeIdList($game_type_id_list);

            // Add the game_tag_id attr. in $game_raw_list.
            $game_type_id_map_tag_list = [];
            array_map(function($game_type_id, $game_tag_id) use (&$game_type_id_map_tag_list){
                $game_type_id_map_tag_list[$game_type_id] = $game_tag_id;
                return [$game_type_id => $game_tag_id];
            }, $game_type_id_list, $game_tag_id_list);
// $this->CI->utils->debug_log('665.game_type_id_map_tag_list:', $game_type_id_map_tag_list);
            if( ! empty($game_raw_list) && (! $isHideTheGamesUnderTag) ){
                foreach($game_raw_list as $indexNumber => $game_raw){
                    $game_raw_tag_id = $game_type_id_map_tag_list[$game_raw['game_type_id']];
                    $game_raw_list[$indexNumber]['game_tag_id'] = $game_raw_tag_id;
                }
            }
            $game_type_id_map_tag_list = null;
            unset($game_type_id_map_tag_list);
        }


        $game_tag_map = null;
        unset($game_tag_map);

        foreach($game_type_raw_list as $game_type_entry){
            /// filter the $game_type_entry of the inactive game platform
            if(!isset($cashback_settings_list_by_game_tag[$game_type_entry['game_tag_id']])){
                continue;
            }
            $game_type_entry['game_type'] = lang($game_type_entry['game_type']);
            $game_type_entry['game_type_lang'] = lang($game_type_entry['game_type_lang']);

            $entry = [
                'game_type_id' => $game_type_entry['id'],
                'game_tag_id' => $game_type_entry['game_tag_id'],
                // 'game_platform_id' => $game_type_entry['game_platform_id'],
                'name' => lang($game_type_entry['game_type_lang']),
                'details' => $game_type_entry,
                'cashback_settings' => (isset($game_type_cashback_settings_list[$game_type_entry['id']])) ? $game_type_cashback_settings_list[$game_type_entry['id']] : $default_setting_template,
                'cashback_rules' => (isset($game_type_cashback_rules_list[$game_type_entry['id']])) ? $game_type_cashback_rules_list[$game_type_entry['id']] : [],
            ];
            if(!empty($entry['cashback_rules'])){
                $this->_sortCashbackRules($entry['cashback_rules']);
            }
            $cashback_settings_list_by_game_type[$game_type_entry['id']] = $entry;
        } // EOF foreach($game_type_raw_list as $game_type_entry){...

        $game_type_raw_list = null;
        unset($game_type_raw_list);

        if( ! empty($game_raw_list) ){
            foreach($game_raw_list as $game_description_entry){
                /// filter the $game_description_entry of the inactive game tag
                if(!isset($cashback_settings_list_by_game_tag[$game_description_entry['game_tag_id']])){
                    continue;
                }
                /// filter the $game_description_entry of the inactive game type.
                // The inactive attr. is reference to $cashback_settings_list_by_game_platform.
                if(!isset($cashback_settings_list_by_game_type[$game_description_entry['game_type_id']])){
                    continue;
                }
                $game_type_entry = &$cashback_settings_list_by_game_type[$game_description_entry['game_type_id']];

                $game_description_entry['game_name'] = lang($game_description_entry['game_name']);

                $entry = [
                    'game_id' => $game_description_entry['id'],
                    'game_type_id' => $game_description_entry['game_type_id'],
                    'game_tag_id' => $game_description_entry['game_tag_id'],
                    'name' => $game_description_entry['game_name'],
                    'details' => $game_description_entry,
                    'cashback_settings' => (isset($game_cashback_settings_list[$game_description_entry['id']])) ? $game_cashback_settings_list[$game_description_entry['id']] : $default_setting_template,
                    'cashback_rules' => (isset($game_cashback_rules_list[$game_description_entry['id']])) ? $game_cashback_rules_list[$game_description_entry['id']] : [],
                ];

                if(!empty($entry['cashback_rules'])){
                    $this->_sortCashbackRules($entry['cashback_rules']);
                }

                if(!isset($game_cashback_settings_list[$game_description_entry['id']]) && $game_type_entry['details']['auto_add_to_cashback']){
                    $entry['cashback_settings']['enabled_cashback'] = $game_type_entry['cashback_settings']['enabled_cashback'];
                }
                $cashback_settings_list_by_game[$game_description_entry['id']] = $entry;
            }
        }


        $this->CI->utils->debug_log('end _getTemplateRulesWithGameTagData.memory_get_usage:'. round(memory_get_usage()/1024) );
        // return [$cashback_settings_list_by_game_tag, $total_rows];

        // cashback_settings_list_by_game_type
        // cashback_settings_list_by_game
        return [$cashback_settings_list_by_game_tag
        , $cashback_settings_list_by_game_type
        , $cashback_settings_list_by_game
        , $total_rows];
    } // EOF _getTemplateRulesWithGameTagData


    public function getTemplateRulesWithTagWithPagination($tpl_id = NULL,$offset = 0, $limit = 10, &$total_rows = 0){
        $template = $this->getGameTagActiveTemplate($tpl_id); // always one record.

        if(empty($template)){
            return FALSE;
        }

        list($cashback_settings_list_by_game_tag
            , $cashback_settings_list_by_game_type
            , $cashback_settings_list_by_game
            , $total_rows) = $this->_getTemplateRulesWithGameTagData($template['cb_mr_tpl_id'], $offset, $limit);
        if(empty($cashback_settings_list_by_game_tag) ){
            $cashback_settings_list_by_game_tag = [];
        }

        // @TODO _getTemplateRulesWithGameData
        // list($cashback_settings_list_by_game_platform
        // , $cashback_settings_list_by_game_type
        // , $cashback_settings_list_by_game
        // , $total_rows) = $this->_getTemplateRulesWithGameData($template['cb_mr_tpl_id'], $offset, $limit);

        $subtotal = [];
        $subtotal['by_game_tag'] = count($cashback_settings_list_by_game_tag);
        $subtotal['by_game_type'] = count($cashback_settings_list_by_game_type);
        $subtotal['by_game'] = count($cashback_settings_list_by_game);


        $tag_tree_map = [];// ref. to game_tree_map

        foreach($cashback_settings_list_by_game_tag as $game_tag_id => $game_tag_entry){
            $game_tag_entry['types'] = [];
            $tag_tree_map[$game_tag_id] = $game_tag_entry;
        }
        foreach($cashback_settings_list_by_game_type as $game_type_id => $game_type_entry){
            if(!isset($tag_tree_map[$game_type_entry['game_tag_id']])){
                continue;
            }

            $game_type_entry['game_list'] = [];

            $tag_tree_map[$game_type_entry['game_tag_id']]['types'][$game_type_id] = $game_type_entry;
        }

        foreach($cashback_settings_list_by_game as $game_description_id => $game_description_entry){
            if(!isset($tag_tree_map[$game_description_entry['game_tag_id']])){
                continue;
            }

            $game_tag_entry = &$tag_tree_map[$game_description_entry['game_tag_id']];

            if(!isset($game_tag_entry['types'][$game_description_entry['game_type_id']])){
                continue;
            }

            $game_type_entry = &$game_tag_entry['types'][$game_description_entry['game_type_id']];

            $game_type_entry['game_list'][$game_description_id] = $game_description_entry;
        }

        $template['settings'] = $tag_tree_map;
        $template['subtotal'] = $subtotal ;
        return $template;
    } // EOF getTemplateRulesWithTagWithPagination


    /**
     * Get Template Rules With Tree Contains Pagination function.
     *
     * Ref. by self::getTemplateRulesWithTree()
     *
     *
     * @param integer $tpl_id The field, common_cashback_multiple_range_templates.cb_mr_tpl_id .
     * @param integer $offset The offset of start data.
	 * @param integer $limit The data amount per page.
     * @param point $total_rows To get total amount without Pagination.
     * @param string $template_name The template name
     * @return array $template For "Multiple Range" UI of "Common Cashback Rules".
     */
    public function getTemplateRulesWithTreeWithPagination($tpl_id = NULL,$offset = 0, $limit = 10, &$total_rows = 0, $template_name = 'Default'){
        $this->CI->utils->debug_log('start getTemplateRulesWithTree.memory_get_usage:'. round(memory_get_usage()/1024) );
                $template = $this->getTemplate($tpl_id, $template_name); // always one record.

                if(empty($template)){
                    return FALSE;
                }

                list($cashback_settings_list_by_game_platform
                , $cashback_settings_list_by_game_type
                , $cashback_settings_list_by_game
                , $total_rows) = $this->_getTemplateRulesWithGameData($template['cb_mr_tpl_id'], $offset, $limit);

                $subtotal = [];
                $subtotal['by_game_platform'] = count($cashback_settings_list_by_game_platform);
                $subtotal['by_game_type'] = count($cashback_settings_list_by_game_type);
                $subtotal['by_game'] = count($cashback_settings_list_by_game);

                $game_tree_map = [];

                foreach($cashback_settings_list_by_game_platform as $game_platform_id => $game_platform_entry){
                    $game_platform_entry['types'] = [];
                    $game_tree_map[$game_platform_id] = $game_platform_entry;
                }

                foreach($cashback_settings_list_by_game_type as $game_type_id => $game_type_entry){
                    if(!isset($game_tree_map[$game_type_entry['game_platform_id']])){
                        continue;
                    }

                    $game_type_entry['game_list'] = [];

                    $game_tree_map[$game_type_entry['game_platform_id']]['types'][$game_type_id] = $game_type_entry;
                }

                foreach($cashback_settings_list_by_game as $game_description_id => $game_description_entry){
                    if(!isset($game_tree_map[$game_description_entry['game_platform_id']])){
                        continue;
                    }

                    $game_platform_entry = &$game_tree_map[$game_description_entry['game_platform_id']];

                    if(!isset($game_platform_entry['types'][$game_description_entry['game_type_id']])){
                        continue;
                    }

                    $game_type_entry = &$game_platform_entry['types'][$game_description_entry['game_type_id']];

                    $game_type_entry['game_list'][$game_description_id] = $game_description_entry;
                }

                $template['settings'] = $game_tree_map;
                $template['subtotal'] = $subtotal ;

        $this->CI->utils->debug_log('end getTemplateRulesWithTree.memory_get_usage:'. round(memory_get_usage()/1024) );
                return $template;
            } // EOF getTemplateRulesWithTreeWithPagination

    public function getTemplateRulesWithTree($tpl_id = NULL, $template_name = 'Default'){
$this->CI->utils->debug_log('start getTemplateRulesWithTree.memory_get_usage:'. round(memory_get_usage()/1024) );
        $template = $this->getTemplate($tpl_id, $template_name);

        if(empty($template)){
            return FALSE;
        }

        list($cashback_settings_list_by_game_platform, $cashback_settings_list_by_game_type, $cashback_settings_list_by_game) = $this->_getTemplateRulesWithGameData($template['cb_mr_tpl_id']);

        $game_tree_map = [];

        foreach($cashback_settings_list_by_game_platform as $game_platform_id => $game_platform_entry){
            $game_platform_entry['types'] = [];

            $game_tree_map[$game_platform_id] = $game_platform_entry;
        }

        foreach($cashback_settings_list_by_game_type as $game_type_id => $game_type_entry){
            if(!isset($game_tree_map[$game_type_entry['game_platform_id']])){
                continue;
            }

            $game_type_entry['game_list'] = [];

            $game_tree_map[$game_type_entry['game_platform_id']]['types'][$game_type_id] = $game_type_entry;
        }

        foreach($cashback_settings_list_by_game as $game_description_id => $game_description_entry){
            if(!isset($game_tree_map[$game_description_entry['game_platform_id']])){
                continue;
            }

            $game_platform_entry = &$game_tree_map[$game_description_entry['game_platform_id']];

            if(!isset($game_platform_entry['types'][$game_description_entry['game_type_id']])){
                continue;
            }

            $game_type_entry = &$game_platform_entry['types'][$game_description_entry['game_type_id']];

            $game_type_entry['game_list'][$game_description_id] = $game_description_entry;
        }

        $template['settings'] = $game_tree_map;
$this->CI->utils->debug_log('end getTemplateRulesWithTree.memory_get_usage:'. round(memory_get_usage()/1024) );
        return $template;
    }

    protected function _getPlayerBetMap($date, $startHour, $endHour, $playerId = null, $start_date = null, $end_date = null){
        $use_settled_time_apis = $this->CI->utils->getConfig('api_array_when_calc_cashback_by_settled_time');
$startTime=microtime(true);
        $playerBetByDate = $this->group_level->getPlayerBetByDate($date, $startHour, $endHour, $playerId, $start_date, $end_date, NULL, $use_settled_time_apis);
$this->CI->utils->debug_log("OGP-27272 cost of 1042.getPlayerBetByDate", microtime(true)-$startTime);
$startTime=microtime(true);
        $playerBetBySettledDate = $this->group_level->getPlayerBetBySettledDate($date, $startHour, $endHour, $playerId, $start_date, $end_date, NULL, $use_settled_time_apis);
$this->CI->utils->debug_log("OGP-27272 cost of getPlayerBetBySettledDate", microtime(true)-$startTime);

        $this->CI->utils->debug_log(__METHOD__ . '(): ', $date, $startHour, $endHour, $playerId, 'getPlayerBetByDate count', empty($playerBetByDate)? 0: count($playerBetByDate), 'playerBetBySettledDate count', empty($playerBetBySettledDate)? 0: count($playerBetBySettledDate));

        $player_bet_map = [];

        if(!empty($playerBetByDate)){

            $this->CI->utils->cloneArrayWithForeach($playerBetByDate, function($pbbd, $_playerBetByDate){ // aka. skipCondiCB($_curr, $arr)
                return $pbbd->betting_total <= 0;
            }, function( $pbbd, $_key, &$_player_bet_map, $_playerBetByDate ){
            // aka. handCurrCB( $_curr, $_key, &$new_arr, $arr )
                $_player_bet_map[$pbbd->player_id][] = $pbbd; // aka. $player_bet_map[$pbbd->player_id][] = $pbbd;
            }, $player_bet_map);
            // foreach($playerBetByDate as $pbbd){
            //     if ($pbbd->betting_total <= 0) {
            //         continue;
            //     }

            //     $player_bet_map[$pbbd->player_id][] = $pbbd;
            // }
        }

        if(!empty($playerBetBySettledDate)){

            $this->CI->utils->cloneArrayWithForeach($playerBetBySettledDate, function($pbbd, $_playerBetByDate){ // aka. skipCondiCB($_curr, $arr)
                return $pbbd->betting_total <= 0;
            }, function( $pbbd, $_key, &$_player_bet_map, $_playerBetByDate ){ // aka. handCurrCB( $_curr, $_key, &$new_arr, $arr )
                $_player_bet_map[$pbbd->player_id][] = $pbbd; // aka. $player_bet_map[$pbbd->player_id][] = $pbbd;
            }, $player_bet_map);

            // foreach($playerBetBySettledDate as $pbbd){
            //     if ($pbbd->betting_total <= 0) {
            //         continue;
            //     }

            //     $player_bet_map[$pbbd->player_id][] = $pbbd;
            // }
        }


        if(!empty($player_bet_map)){ // Add the game_tag_id
            foreach($player_bet_map as $player_id4apbm => $apbmList){
                foreach($apbmList as $indexNumber => $pbmByPlayer){
                    $game_type_id = $pbmByPlayer->game_type_id;
                    $game_description_id = $pbmByPlayer->game_description_id;

                    /// get the game_tag_id from game_type_id
                    // Game_type_model::getGameTagsByDescriptionId()
                    $gameTags = $this->game_type_model->getGameTagsByDescriptionId($game_description_id);
                    $player_bet_map[$pbmByPlayer->player_id][$indexNumber]->game_tag_id = $gameTags['id'];
                    $player_bet_map[$pbmByPlayer->player_id][$indexNumber]->tag_code = $gameTags['tag_code'];

                }
            }
        }

        return $player_bet_map;
    }

    public function getAvailableCashbackRule($cashback_rules, $total_bet_amount, $level_id, $game_platform_id, $game_type_id, $game_description_id){
        $level_details = (!empty($this->all_vip_level) && isset($this->all_vip_level[$level_id])) ? $this->all_vip_level[$level_id] : FALSE;

        if(empty($level_details)){
            $this->CI->utils->debug_log(__METHOD__ . '(): invalid vip level id [' . $level_id . '].');
            return FALSE;
        }

        $matched_cashback_rule = $this->_match_rule($cashback_rules, $total_bet_amount);

        $cashback_rule = [
            'history_id' => '',
            'cashback_percentage' => 0,
            'max_cashback_amount' => 0
        ];

        if(!empty($matched_cashback_rule)){
            $cashback_rule['history_id'] = 'cb_mr_' . $matched_cashback_rule['type'] . '_' . $matched_cashback_rule['cb_mr_rule_id'];
            $cashback_rule['cashback_percentage'] = (float)$matched_cashback_rule['cashback_percentage'];
            $cashback_rule['max_cashback_amount'] = (float)$matched_cashback_rule['max_cashback_amount'];
        }

        if($level_details['bonus_mode_cashback']){
            $cashback_percentage = (float)trim($level_details['cashback_percentage']);
            $cashback_maxbonus = (float)trim($level_details['cashback_maxbonus']);
            $cashback_rule['cashback_percentage'] = (!empty($cashback_percentage)) ? $cashback_percentage : $cashback_rule['cashback_percentage'];
            $cashback_rule['max_cashback_amount'] = (!empty($cashback_maxbonus)) ? $cashback_maxbonus : $cashback_rule['max_cashback_amount'];

            $vip_cashback_rule = $this->group_level->getCashbackRuleFromLevelCashbackMap($this->level_cashback_map, $level_id, $game_platform_id, $game_type_id, $game_description_id, TRUE);
            if(empty($vip_cashback_rule)){
                $this->CI->utils->debug_log(__METHOD__ . '(): disabled cashback by vip level setting by [' . $game_platform_id . ', ' . $game_type_id . ', ' . $game_description_id . '].', 'level_id:', $level_id);
                return FALSE;
            }else{
                $cashback_percentage = (float)trim($vip_cashback_rule->cashback_percentage);
                $cashback_maxbonus = (float)trim($vip_cashback_rule->cashback_maxbonus);

                $cashback_rule['history_id'] = 'vip_level_' . $vip_cashback_rule->level_id;
                $cashback_rule['cashback_percentage'] = (!empty($cashback_percentage)) ? $cashback_percentage : $cashback_rule['cashback_percentage'];
                $cashback_rule['max_cashback_amount'] = (!empty($cashback_maxbonus)) ? $cashback_maxbonus : $cashback_rule['max_cashback_amount'];
            }
        }
        return $cashback_rule;
    }

    /**
     * Get the Common Calculate Mode and Allow By multiple_range_settings_priority, enabled_cashback and enabled_tier_calc_cashback.
     *
     * @param string $configMRSP The setting, `multiple_range_settings_priority` in config.
     * @param bool $enabled_cashback The field,`enabled_cashback` in the data-table,`common_cashback_multiple_range_settings`.
     * @param bool $enabled_tier_calc_cashback The field,`enabled_tier_calc_cashback` in the data-table,`common_cashback_multiple_range_settings`.
     * @return array $return The format is the following,
     * - $return['allowCashback'] bool Is allow to Calc Cashback?
     * - $return['calcMode'] integer If it's 1, that means the calculation By betting amount matched in the min to max amount of the one rule.
     * If it's 2, that means the calculation, the betting will be applied and accumulated in the all rules By Tier.
     */
    public function getCalculateCashbackMode4GameTag($configMRSP, $enabled_cashback, $enabled_tier_calc_cashback){
        $return = [];
        $return['allowCashback'] = null;
        $return['calcMode'] = null;

        // MRSP = multiple_range_settings_priority
        $configMRSP_Int = 0; // platform
        if($configMRSP == 'tag'){
            $configMRSP_Int = 1;
        }else if($configMRSP == 'platform'){
            $configMRSP_Int = 0;
        }

        // EC = enabled_cashback
        // EC_Int: null=> -1,  true=> 1,  false=> 0,
        $EC_Int = -1; // Maybe it's 'Childs active',But it is impassable in Game Tag
        if( $enabled_cashback === false){
            $EC_Int = 0;
        }else if( $enabled_cashback === true){
            $EC_Int = 1;
        }

        // ETCC = enabled_tier_calc_cashback
        $ETCC_Int = -1; // Maybe it's 'Childs active',But it is impassable in Game Tag
        if( $enabled_tier_calc_cashback === false){
            $ETCC_Int = 0;
        }else if( $enabled_tier_calc_cashback === true){
            $ETCC_Int = 1;
        }

        // switchStr = configMRSP_Int, EC_Int, ETCC_Int
        /// 目前是 game platform 優先, $config['multiple_range_settings_priority'] = 'platform';
        // Case A1, // 0, 1, 1
        // Settings: active
        // Step Calculation: active
        //
        // 會算反水， By Tier, CB% Applied all Rules By Tier
        //
        // Case A2, // 0, 1, 0
        // Settings: active
        // Step Calculation: inactive
        //
        // 會算反水， 類舊算法, CB% Applied a Rule, with betting of the same game tag
        //
        // Case A3, // 0, 0, 1
        // Settings:  inactive
        // Step Calculation: active
        //
        // 會算反水， By Tier, CB% Applied all Rules By Tier
        //
        // Case A4, // 0, 0, 0
        // Settings:  inactive
        // Step Calculation: inactive
        //
        // 會算反水， 舊算法, CB% Applied a Rule
        //
        /// 目前是 game tag 優先, $config['multiple_range_settings_priority'] = 'tag';
        //
        // Case B1, // 1, 1, 1 [c]
        // Settings: active
        // Step Calculation: active
        //
        // 會算反水， By Tier, CB% Applied all Rules By Tier
        //
        // Case B2, // 1, 1, 0 [c]
        // Settings: active
        // Step Calculation: inactive
        //
        // 會算反水， 類舊算法, CB% Applied a Rule, with betting of the same game tag
        //
        // Case B3, // 1, 0, 1 [c]
        // Settings:  inactive
        // Step Calculation: active
        //
        // 不給返水, No Cashback
        //
        // Case B4, // 1, 0, 0 [c]
        // Settings:  inactive
        // Step Calculation: inactive
        //
        // 不給返水, No Cashback

        $switchAry = [];
        $switchAry[] = $configMRSP_Int;
        $switchAry[] = $EC_Int;
        $switchAry[] = $ETCC_Int;
        $switchStr = implode(',',$switchAry);
        $return['switchStr'] = $switchStr;

        switch($switchStr){

            case '0,1,0': // Case A2, 會算反水， 流水總和是依據 game tag，用流水總和 對應 所達到的一個等級設定，取得 CB% 。
            case '1,1,0': // Case B2, 會算反水， 流水總和是依據 game tag，CB% 套用流水所達到的一個等級設定。
                $return['allowCashback'] = true;
                $return['calcMode'] = Common_Cashback_multiple_rules::CALC_MODE_BY_HEAP_IN_GAME_TAG; // sum the bet of the same tag, and CB% Applied a Rule by the bet amount.
                break;

            case '0,0,0': // Case A4, 會算反水， 舊算法
            case '0,0,1': // Case A3, 會算反水， 舊算法
                $return['allowCashback'] = true;
                $return['calcMode'] = 1;
                break;

            case '0,1,1': // Case A1, 會算反水， By Tier
            case '1,1,1': // Case B1, 會算反水， By Tier
                $return['allowCashback'] = true;
                $return['calcMode'] = Common_Cashback_multiple_rules::CALC_MODE_BY_TIER_IN_GAME_TAG;
                break;

            case '1,0,0': // Case B4, 不給返水
            case '1,0,1': // Case B3, 不給返水
                $return['allowCashback'] = false;
                break;
        }
        return $return;
    } // EOF getCalculateCashbackMode4GameTag

    /**
     * Initial the array, $byTierTodoList
     *
     * For patch the following PHP Errors,
     * Severity: Notice | Message:  Undefined offset: 1
     * Severity: Notice | Message:  Undefined index: wc_amount_map
     * Severity: Notice | Message:  Undefined index: ...
     *
     * @param array $byTierTodoList The target array.
     * @param integer $game_tag_id The game_tags.id, it's for the location key.
     * @param integer $player_id The player.playerId, it's for the location key.
     * @param bool $is_contains_subtotal_bet_amount If true, it will set Zero into the matched location.
     * @return array $byTierTodoList The target array.
     */
    private function _initialByTierTodoListByGameTagAndPlayer(&$byTierTodoList, $game_tag_id, $player_id, $is_contains_subtotal_bet_amount = false){
        if( !isset($byTierTodoList['tag'][$game_tag_id]) ){
            $byTierTodoList['tag'][$game_tag_id] = [];
        }
        if( !isset($byTierTodoList['tag'][$game_tag_id][$player_id]) ){
            $byTierTodoList['tag'][$game_tag_id][$player_id] = [];
        }

        if($is_contains_subtotal_bet_amount){
            if( !isset($byTierTodoList['tag'][$game_tag_id][$player_id]['subtotal_bet_amount']) ){
                $byTierTodoList['tag'][$game_tag_id][$player_id]['subtotal_bet_amount'] = 0;
            }
        }

        return $byTierTodoList;
    }

    public function calculateTotalCashback( $date // #1
                                            , $startHour // #2
                                            , $endHour // #3
                                            , $playerId = null // #4
                                            , $withdraw_condition_bet_times = 0 // #5
                                            , &$result = false // #6
                                            , $start_date = null // #7
                                            , $end_date = null // #8
                                            , $forceToPay = false // #9
                                            , $recalculate_cashback = false // #10
                                            , $uniqueId = null // #11
                                            , $doExceptionPropagationInChoppedLock = false // #12
    ){
        $this->CI->load->model(array('player_model', 'transactions', 'users', 'game_description_model', 'withdraw_condition', 'group_level', 'total_cashback_player_game_daily'));
        // MRSP = multiple_range_settings_priority
        $configMRSP = $this->CI->utils->getConfig('multiple_range_settings_priority');
        $configMRSP = strtolower($configMRSP);// platform or tag

        $cnt = 0; // getAvailableCashbackRule
$startTime=microtime(true);
        if(FALSE === $this->init_caculate_cashback_require_data()){
            return $cnt;
        }
$this->CI->utils->debug_log("OGP-27272 cost of this->init_caculate_cashback_require_data", microtime(true)-$startTime);
        list($cashback_settings_list_by_game_platform, $cashback_settings_list_by_game_type, $cashback_settings_list_by_game) = $this->cashback_settings_with_game_data; // from _getTemplateRulesWithGameData()

        $cashback_settings_list_by_game_tag = $this->cashback_settings_with_game_tag_data;

        $isPayTime = $this->CI->group_level->isPayTime($date, $forceToPay);
$startTime=microtime(true);
        $player_bet_map = $this->_getPlayerBetMap($date, $startHour, $endHour, $playerId, $start_date, $end_date);
$this->CI->utils->debug_log("OGP-27272 cost of this->_getPlayerBetMap", microtime(true)-$startTime);
        if (empty($player_bet_map)) {
            return $cnt;
        }

        $isNoCashbackBonusForNonDepositPlayer = $this->group_level->isNoCashbackBonusForNonDepositPlayer();

        $recalculate_cashback_table = $recalculate_deducted_process_table = null;
        if($recalculate_cashback && !empty($uniqueId)){
            list($recalculate_cashback_table, $recalculate_deducted_process_table) = $this->group_level->checkRecalculateCashbackInfo($uniqueId);
        }
$startTime=microtime(true);
        $wc_amount_map = $this->CI->withdraw_condition->getAllPlayersAvailableAmountOnWithdrawConditionByCashbackSettings($date, $startHour, $endHour, $start_date, $end_date, $playerId, $recalculate_cashback, $recalculate_deducted_process_table);
$this->CI->utils->debug_log("OGP-27272 cost of withdraw_condition->getAllPlayersAvailableAmountOnWithdrawConditionByCashbackSettings", microtime(true)-$startTime);

        $byTierTodoList = []; // 存放 Tier Calc 的陣列，預計要有：該玩家的每筆投注記錄，依照哪一筆 Tier 的設定、FK、跟內容都要。
        $byTierTodoList['tag'] = []; // for game_tags


        $enabled_dryrun_in_calculatecashback = $this->CI->utils->getConfig('enabled_dryrun_in_calculatecashback');
        $enabled_chopped_lock_in_calculatecashback = $this->CI->utils->getConfig('enabled_chopped_lock_in_calculatecashback');
        if ($enabled_chopped_lock_in_calculatecashback) {
            $startTime=microtime(true);
            // $this->CI->player_model->startTrans();
            $this->chopped_lock_phase_one_except_list = [];
            $this->chopped_lock_phase_two_except_list = [];
        }

        /// Script Begin - OGP-278332 Solution2.
        foreach($player_bet_map as $player_id => $player_bet_list){
            if(empty($player_bet_list)) continue;

            if ($isNoCashbackBonusForNonDepositPlayer) {
                //should check deposit
                $playerObj = $this->CI->player_model->getPlayerArrayById($player_id);
                if ($playerObj['totalDepositAmount'] <= 0) {
                    $this->CI->utils->debug_log(__METHOD__ . '(): ignore player ' . $player_id . ' for none deposit');
                    continue;
                }
            }

            $use_tag_total_bet_amount_list = [];
            $game_tag_total_bet_amount = [];

            $use_platform_total_bet_amount_list = [];
            $game_platform_total_bet_amount = [];

            $use_type_total_bet_amount_list = [];
            $game_type_total_bet_amount = [];

            $this->CI->utils->debug_log(__METHOD__ . '(): ', $date, $startHour, $endHour, $player_id, '$player_bet_list count', count($player_bet_list));
            $startTime4_chopped_lock=microtime(true); // for 27272 cost of foreach.player_bet_map as player_id
            if ( $enabled_chopped_lock_in_calculatecashback ) {
                $this->CI->player_model->startTrans(); // trans by a player
            }
            try{ /// try for $enabled_chopped_lock_in_calculatecashback in each player

                /// TODO:OGP-27832 - 主要腳本。
                // Script Begin - OGP-278332 Solution2.
                foreach ($player_bet_list as $pbbd) { // to calc by game, game_description
                    $game_platform_id = $pbbd->game_platform_id;
                    $game_type_id = $pbbd->game_type_id;
                    $game_description_id = $pbbd->game_description_id;
                    $game_tag_id = $pbbd->game_tag_id;
                    $this->CI->utils->debug_log('OGP-24813.1343.pbbd:', $pbbd
                    , 'recalculate_cashback_table:', $recalculate_cashback_table
                    , 'recalculate_deducted_process_table:', $recalculate_deducted_process_table );

                    if(!isset($cashback_settings_list_by_game[$game_description_id])){
                        $this->CI->utils->debug_log(__METHOD__ . '(): Not found game setting on cashback settings list.', 'Game Description id:', $game_description_id);
                        continue;
                    }

                    $cashback_settings_by_game = $cashback_settings_list_by_game[$game_description_id];

                    if(!$cashback_settings_by_game['cashback_settings']['enabled_cashback']){
                        $this->CI->utils->debug_log(__METHOD__ . '(): Disabled cashback by game.', 'Game Description id:', $game_description_id, 'player_id:', $player_id);
                        continue;
                    }

                    if(empty($cashback_settings_by_game['cashback_rules'])){
                        $use_type_total_bet_amount_list[] = $pbbd; // assign for calc by game type, game_type

                        $game_type_total_bet_amount[$game_type_id] = (isset($game_type_total_bet_amount[$game_type_id])) ? $game_type_total_bet_amount[$game_type_id] + $pbbd->betting_total : $pbbd->betting_total;
                        continue; // skip this bet
                    }
                    $startTime=microtime(true);
                    $result = $this->_calculatePlayerCashback($pbbd, $cashback_settings_by_game['cashback_rules'], $pbbd->betting_total, $date, $wc_amount_map, $withdraw_condition_bet_times, $isPayTime, $recalculate_deducted_process_table, $recalculate_cashback_table);
                    $this->CI->utils->debug_log("OGP-27272 cost of 1396.this->_calculatePlayerCashback", microtime(true)-$startTime);
                    if(FALSE === $result){
                        continue;
                    }

                    $cnt++;
                }// EOF foreach ($player_bet_list as $pbbd) {...
                //
                foreach ($use_type_total_bet_amount_list as $pbbd) { // to calc by game type, game_type
                    $game_platform_id = $pbbd->game_platform_id;
                    $game_type_id = $pbbd->game_type_id;
                    $game_description_id = $pbbd->game_description_id;
                    $game_tag_id = $pbbd->game_tag_id;

                    if(!isset($cashback_settings_list_by_game_type[$game_type_id])){
                        $this->CI->utils->debug_log(__METHOD__ . '(): Not found game type setting on cashback settings list.', 'Game type id:', $game_type_id);
                        continue;
                    }

                    $cashback_settings_by_game_type = $cashback_settings_list_by_game_type[$game_type_id];

                    if($configMRSP == 'tag'){
                        if(empty($cashback_settings_by_game_type['cashback_rules'])){
                            $use_tag_total_bet_amount_list[] = $pbbd; // assign for calc by game tag, game_tags
                            $game_tag_total_bet_amount[$game_tag_id] = (isset($game_tag_total_bet_amount[$game_tag_id])) ? $game_tag_total_bet_amount[$game_tag_id] + $pbbd->betting_total : $pbbd->betting_total;
                            continue;
                        }
                    }else{ // $configMRSP == 'platform'
                        if(empty($cashback_settings_by_game_type['cashback_rules'])){
                            $use_platform_total_bet_amount_list[] = $pbbd; // assign for calc by game platform, external_system
                            $game_platform_total_bet_amount[$game_platform_id] = (isset($game_platform_total_bet_amount[$game_platform_id])) ? $game_platform_total_bet_amount[$game_platform_id] + $pbbd->betting_total : $pbbd->betting_total;
                            continue;
                        }
                    }

                    $result = $this->_calculatePlayerCashback($pbbd, $cashback_settings_by_game_type['cashback_rules'], $game_type_total_bet_amount[$game_type_id], $date, $wc_amount_map, $withdraw_condition_bet_times, $isPayTime, $recalculate_deducted_process_table, $recalculate_cashback_table);
                    if(FALSE === $result){
                        continue;
                    }

                    $cnt++;
                }
                //
                if(false){ // org
                    foreach ($use_platform_total_bet_amount_list as $pbbd) {
                        $game_platform_id = $pbbd->game_platform_id;
                        $game_type_id = $pbbd->game_type_id;
                        $game_description_id = $pbbd->game_description_id;

                        if(!isset($cashback_settings_list_by_game_platform[$game_platform_id])){
                            $this->CI->utils->debug_log(__METHOD__ . '(): Not found game platform setting on cashback settings list.', 'Game platform id:', $game_platform_id);
                            continue;
                        }

                        $cashback_settings_by_game_platform = $cashback_settings_list_by_game_platform[$game_platform_id];

                        if(empty($cashback_settings_by_game_platform['cashback_rules'])){
                            $this->CI->utils->debug_log(__METHOD__ . '(): disabled cashback by invalid game platform cashback rules by [' . $game_platform_id . ', ' . $game_type_id . ', ' . $game_description_id . '].', 'player_id', $player_id);
                            continue;
                        }

                        $result = $this->_calculatePlayerCashback($pbbd, $cashback_settings_by_game_platform['cashback_rules'], $game_platform_total_bet_amount[$game_platform_id], $date, $wc_amount_map, $withdraw_condition_bet_times, $isPayTime, $recalculate_deducted_process_table, $recalculate_cashback_table);

                        if(FALSE === $result){
                            continue;
                        }

                        $cnt++;
                    } // EOF foreach ($use_platform_total_bet_amount_list as $pbbd) {...
                }else if($configMRSP == 'tag'){ // tag first, tag > platform
                    // handle tag
                    foreach ($use_tag_total_bet_amount_list as $pbbd) { // to calc by game tag, game_tags
                        $game_tag_id = $pbbd->game_tag_id;
                        $game_type_id = $pbbd->game_type_id;
                        $game_description_id = $pbbd->game_description_id;
                        $game_platform_id = $pbbd->game_platform_id;

                        if(!isset($cashback_settings_list_by_game_tag[$game_tag_id])){
                            $this->CI->utils->debug_log(__METHOD__ . '(): Not found game tag setting on cashback settings list.', 'Game tag id:', $game_tag_id);
                            continue;
                        }

                        $cashback_settings_by_game_tag = $cashback_settings_list_by_game_tag[$game_tag_id];

                        $cashback_settings = $cashback_settings_by_game_tag['cashback_settings'];
                        $isTierCalc = $cashback_settings['enabled_tier_calc_cashback'];
                        $calculateCashbackMode = $this->getCalculateCashbackMode4GameTag($configMRSP, $cashback_settings['enabled_cashback'], $cashback_settings['enabled_tier_calc_cashback']);
                        if($calculateCashbackMode['calcMode'] == 1){
                            $isTierCalc = false;
                        }else if($calculateCashbackMode['calcMode'] == Common_Cashback_multiple_rules::CALC_MODE_BY_TIER_IN_GAME_TAG){
                            $isTierCalc = true; // $calculateCashbackMode['calcMode'] Need to ref.
                        }else if($calculateCashbackMode['calcMode'] == Common_Cashback_multiple_rules::CALC_MODE_BY_HEAP_IN_GAME_TAG){
                            $isTierCalc = true; // $calculateCashbackMode['calcMode'] Need to ref.
                        }
                        // $this->CI->utils->debug_log(__METHOD__ . '(): 1434.calculateCashbackMode():', $calculateCashbackMode, ' params:', $configMRSP, $cashback_settings['enabled_cashback'], $cashback_settings['enabled_tier_calc_cashback'], 'player_id:', $player_id);

                        if( empty($cashback_settings_by_game_tag['cashback_rules'])
                            || empty($calculateCashbackMode['allowCashback'])
                        ){
                            $use_platform_total_bet_amount_list[] = $pbbd;
                            $game_platform_total_bet_amount[$game_platform_id] = (isset($game_platform_total_bet_amount[$game_platform_id])) ? $game_platform_total_bet_amount[$game_platform_id] + $pbbd->betting_total : $pbbd->betting_total;

                            if( empty($cashback_settings_by_game_tag['cashback_rules']) ){
                                $this->CI->utils->debug_log(__METHOD__ . '(): disabled cashback by invalid game tag cashback rules by Empty Rules, [' . $game_platform_id . ', ' . $game_type_id . ', ' . $game_description_id . '].', 'game_tag_id:', $game_tag_id, 'player_id:', $player_id);
                            }
                            if( empty($calculateCashbackMode['allowCashback']) ){
                                $this->CI->utils->debug_log(__METHOD__ . '(): disabled cashback by invalid game tag cashback rules by calculateCashbackMode(), [' . $game_platform_id . ', ' . $game_type_id . ', ' . $game_description_id . '].', 'game_tag_id:', $game_tag_id, 'player_id:', $player_id);
                            }
                            continue;
                        }

                        if($isTierCalc){
                            $this->_initialByTierTodoListByGameTagAndPlayer($byTierTodoList, $game_tag_id, $player_id);
                            // $byTierTodoList['tag'][$game_tag_id][$player_id]['subtotal_bet_amount'] += $pbbd->betting_total;
                            // $byTierTodoList['tag'][$game_tag_id][$player_id]['sutotal_bet_part_list'][] = $pbbd; // append the bettings

                            $byTierTodoList['tag'][$game_tag_id][$player_id]['cashback_rules'] = $cashback_settings_by_game_tag['cashback_rules'];
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['cashback_settings'] = $cashback_settings_by_game_tag['cashback_settings'];
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['game_tag_total_bet_amount'] = $game_tag_total_bet_amount[$game_tag_id];
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['date'] = $date;
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['wc_amount_map'] = &$wc_amount_map;
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['withdraw_condition_bet_times'] = $withdraw_condition_bet_times;
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['isPayTime'] = $isPayTime;
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['calcMode'] = $calculateCashbackMode['calcMode'];
                            // $result = true;
                            $cashback_rules = []; // will applied Tier Calc.
                        }else{
                            $cashback_rules = $cashback_settings_by_game_tag['cashback_rules'];
                            // $result = $this->_calculatePlayerCashback($pbbd, $cashback_settings_by_game_tag['cashback_rules'], $game_tag_total_bet_amount[$game_tag_id], $date, $wc_amount_map, $withdraw_condition_bet_times, $isPayTime, $isTierCalc);
                        }

                        $result = $this->_calculatePlayerCashback($pbbd, $cashback_rules, $pbbd->betting_total, $date, $wc_amount_map, $withdraw_condition_bet_times, $isPayTime, $recalculate_deducted_process_table, $recalculate_cashback_table);
                        $this->CI->utils->debug_log('OGP-24480.1495.will._calculatePlayerCashback.pbbd:', $pbbd, 'cashback_rules:', $cashback_rules, 'result:', $result);
                        if(FALSE === $result){
                            $is_contains_subtotal_bet_amount = true;
                            $this->_initialByTierTodoListByGameTagAndPlayer($byTierTodoList, $game_tag_id, $player_id, $is_contains_subtotal_bet_amount);
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['subtotal_bet_amount'] += $pbbd->betting_total;
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['sutotal_bet_part_list'][] = $pbbd; // append the bettings
                            continue;
                        }
                    }
                    // handle platform
                    foreach ($use_platform_total_bet_amount_list as $pbbd) { // to calc by game platform, external_system
                        $game_tag_id = $pbbd->game_tag_id;
                        $game_type_id = $pbbd->game_type_id;
                        $game_description_id = $pbbd->game_description_id;
                        $game_platform_id = $pbbd->game_platform_id;

                        if(!isset($cashback_settings_list_by_game_platform[$game_platform_id])){
                            $this->CI->utils->debug_log(__METHOD__ . '(): Not found game platform setting on cashback settings list.', 'Game platform id:', $game_platform_id);
                            continue;
                        }

                        $cashback_settings_by_game_platform = $cashback_settings_list_by_game_platform[$game_platform_id];

                        if(empty($cashback_settings_by_game_platform['cashback_rules'])){
                            $this->CI->utils->debug_log(__METHOD__ . '(): disabled cashback by invalid game platform cashback rules by [' . $game_tag_id . ', ' . $game_type_id . ', ' . $game_description_id . '].', 'game_platform_id:', $game_platform_id, 'player_id:', $player_id);
                            continue;
                        }

                        $result = $this->_calculatePlayerCashback($pbbd, $cashback_settings_by_game_platform['cashback_rules'], $game_platform_total_bet_amount[$game_platform_id], $date, $wc_amount_map, $withdraw_condition_bet_times, $isPayTime, $recalculate_deducted_process_table, $recalculate_cashback_table);

                        if(FALSE === $result){
                            continue;
                        }

                        $cnt++;
                    }
                }else if($configMRSP == 'platform'){ //  platform first, platform > tag

                    // handle platform
                    foreach ($use_platform_total_bet_amount_list as $pbbd) { // to calc by game platform, external_system


                        $game_platform_id = $pbbd->game_platform_id;
                        $game_type_id = $pbbd->game_type_id;
                        $game_description_id = $pbbd->game_description_id;
                        $game_tag_id = $pbbd->game_tag_id;

                        if(!isset($cashback_settings_list_by_game_platform[$game_platform_id])){
                            $this->CI->utils->debug_log(__METHOD__ . '(): Not found game platform setting on cashback settings list.', 'Game platform id:', $game_platform_id);
                            continue;
                        }

                        $cashback_settings_by_game_platform = $cashback_settings_list_by_game_platform[$game_platform_id];

                        if(empty($cashback_settings_by_game_platform['cashback_rules'])){
                            $use_tag_total_bet_amount_list[] = $pbbd;
                            $game_tag_total_bet_amount[$game_tag_id] = (isset($game_tag_total_bet_amount[$game_tag_id])) ? $game_tag_total_bet_amount[$game_tag_id] + $pbbd->betting_total : $pbbd->betting_total;
                            continue;
                        }

                        $result = $this->_calculatePlayerCashback($pbbd, $cashback_settings_by_game_platform['cashback_rules'], $game_platform_total_bet_amount[$game_platform_id], $date, $wc_amount_map, $withdraw_condition_bet_times, $isPayTime, $recalculate_deducted_process_table, $recalculate_cashback_table);
                        if(FALSE === $result){
                            continue;
                        }
                    }

                    // handle tag
                    foreach ($use_tag_total_bet_amount_list as $pbbd) { // to calc by game tag, game_tags
                        $game_platform_id = $pbbd->game_platform_id;
                        $game_type_id = $pbbd->game_type_id;
                        $game_description_id = $pbbd->game_description_id;
                        $game_tag_id = $pbbd->game_tag_id;

                        if(!isset($cashback_settings_list_by_game_tag[$game_tag_id])){
                            $this->CI->utils->debug_log(__METHOD__ . '(): Not found game tag setting on cashback settings list.', 'Game tag id:', $game_tag_id);
                            continue;
                        }

                        $cashback_settings_by_game_tag = $cashback_settings_list_by_game_tag[$game_tag_id];

                        $cashback_settings = $cashback_settings_by_game_tag['cashback_settings'];
                        $isTierCalc = $cashback_settings['enabled_tier_calc_cashback'];
                        $calculateCashbackMode = $this->getCalculateCashbackMode4GameTag($configMRSP, $cashback_settings['enabled_cashback'], $cashback_settings['enabled_tier_calc_cashback']);
                        if($calculateCashbackMode['calcMode'] == 1){
                            $isTierCalc = false;
                        }else if($calculateCashbackMode['calcMode'] == Common_Cashback_multiple_rules::CALC_MODE_BY_TIER_IN_GAME_TAG){
                            $isTierCalc = true; // $calculateCashbackMode['calcMode'] Need to ref.
                        }else if($calculateCashbackMode['calcMode'] == Common_Cashback_multiple_rules::CALC_MODE_BY_HEAP_IN_GAME_TAG ){
                            $isTierCalc = true; // $calculateCashbackMode['calcMode'] Need to ref.
                        }

                        // $this->CI->utils->debug_log(__METHOD__ . '(): 1554.calculateCashbackMode():', $calculateCashbackMode
                        //     , '[' . $game_platform_id . ', ' . $game_type_id . ', ' . $game_description_id . ']. params:'
                        //     , $configMRSP, $cashback_settings['enabled_cashback'], $cashback_settings['enabled_tier_calc_cashback']
                        //     , 'player_id:', $player_id
                        //     , 'game_tag_id:', $game_tag_id
                        //     , 'cashback_settings_by_game_tag.cashback_rules:',$cashback_settings_by_game_tag['cashback_rules'] );

                        if( empty($calculateCashbackMode['allowCashback']) ){
                            $this->CI->utils->debug_log(__METHOD__ . '(): disabled cashback by invalid game tag cashback rules by calculateCashbackMode(), [' . $game_platform_id . ', ' . $game_type_id . ', ' . $game_description_id . '].', 'game_tag_id:', $game_tag_id, 'player_id:', $player_id);
                            continue;
                        }

                        if( empty($cashback_settings_by_game_tag['cashback_rules']) ){
                            $this->CI->utils->debug_log(__METHOD__ . '(): disabled cashback by invalid game tag cashback rules by empty cashback_rules[' . $game_platform_id . ', ' . $game_type_id . ', ' . $game_description_id . '].', 'game_tag_id:', $game_tag_id, 'player_id:', $player_id);
                            continue;
                        }

                        // $cashback_settings = $cashback_settings_by_game_tag['cashback_settings'];
                        // $isTierCalc = $cashback_settings['enabled_tier_calc_cashback'];
                        if($isTierCalc){
                            $this->_initialByTierTodoListByGameTagAndPlayer($byTierTodoList, $game_tag_id, $player_id);
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['cashback_rules'] = $cashback_settings_by_game_tag['cashback_rules'];
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['cashback_settings'] = $cashback_settings_by_game_tag['cashback_settings'];
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['game_tag_total_bet_amount'] = $game_tag_total_bet_amount[$game_tag_id];
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['date'] = $date;
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['wc_amount_map'] = &$wc_amount_map;
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['withdraw_condition_bet_times'] = $withdraw_condition_bet_times;
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['isPayTime'] = $isPayTime;
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['calcMode'] = $calculateCashbackMode['calcMode'];
                            $cashback_rules = [];
                        }else{
                            $cashback_rules = $cashback_settings_by_game_tag['cashback_rules'];
                        }

                        $result = $this->_calculatePlayerCashback($pbbd, $cashback_rules, $pbbd->betting_total, $date, $wc_amount_map, $withdraw_condition_bet_times, $isPayTime, $recalculate_deducted_process_table, $recalculate_cashback_table);
                        $this->CI->utils->debug_log('OGP-24480.1618.will._calculatePlayerCashback.pbbd:', $pbbd, 'cashback_rules:', $cashback_rules, 'result:', $result);

                        if(FALSE === $result){
                            $is_contains_subtotal_bet_amount = true;
                            $this->_initialByTierTodoListByGameTagAndPlayer($byTierTodoList, $game_tag_id, $player_id, $is_contains_subtotal_bet_amount);
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['subtotal_bet_amount'] += $pbbd->betting_total;
                            $byTierTodoList['tag'][$game_tag_id][$player_id]['sutotal_bet_part_list'][] = $pbbd; // append the bettings
                            continue;
                        }

                        $cnt++;
                    } // EOF foreach ($use_tag_total_bet_amount_list as $pbbd) {...

                } // EOF }else if($configMRSP == 'platform'){...
                // Script End - OGP-278332 Solution2.

                /// Make exception for $exception_in_chopped_lock_phase_one
                // if($player_id == $exception_in_chopped_lock_phase_one && $enabled_chopped_lock_in_calculatecashback){
                //     throw new Exception('Exception In chopped_lock_phase_One for testing.');
                // }
                $exception_in_chopped_lock_phase_one = $this->CI->utils->getConfig('exception_in_chopped_lock_phase_one');
                if( ! empty($exception_in_chopped_lock_phase_one) ){
                    $this->CI->utils->debug_log('exception_in_chopped_lock_phase_one.player_id:', $player_id);
                }
                $doException = false;
                if( is_numeric($exception_in_chopped_lock_phase_one) ){
                    $doException = $exception_in_chopped_lock_phase_one == $player_id;
                } else if( is_array($exception_in_chopped_lock_phase_one) ){
                    $doException = in_array($player_id, $exception_in_chopped_lock_phase_one);
                } else if( is_bool($exception_in_chopped_lock_phase_one) ){
                    $doException = $exception_in_chopped_lock_phase_one === true;
                }
                if( $doException && $enabled_chopped_lock_in_calculatecashback ){
                    throw new Exception('Exception In chopped_lock_phase_One for testing. player_id:'. $player_id);
                }


                if ( $enabled_chopped_lock_in_calculatecashback ) {
                    if( !empty($enabled_dryrun_in_calculatecashback) ){
                        // dryrun
                        $this->CI->player_model->rollbackTrans();
                        if( ! empty( $this->group_level->detectFileInSecret_keys(Group_level::FILENAME_SUSPEND_IN_DRYRUN_FOR_CALCULATECASHBACK)) ){
                            $this->CI->utils->debug_log("OGP-27832 cancel continue of dryrun in phase One", 'player_id:', $player_id);
                            $byTierTodoList = []; //clear for cancel
                            throw new Exception('Exception for SUSPEND in dryrun of calculatecashback. player_id:'. $player_id, Group_level::EXCEPTION_CODE_IN_CANCEL_CONTINUE);
                            // The break in Exception by Group_level::EXCEPTION_CODE_IN_CANCEL_CONTINUE
                        }
                    }else{
                        $this->CI->player_model->endTransWithSucc();
                    }
                }else{
                    // rollbackTrans() in Command::calculateCashback()
                }
                $this->CI->utils->debug_log("OGP-27272 cost of foreach.player_bet_map as player_id", microtime(true)-$startTime4_chopped_lock, 'player_id:', $player_id);
            }catch(Exception $e){ /// catch for $enabled_chopped_lock_in_calculatecashback
                $this->CI->utils->error_log('got exception in lockAndTrans', $e);
                $except = [];
                $except['code'] = $e->getCode();
                $except['message'] = $e->getMessage();
                // for clear the cashback data by player and date
                $except['player_id'] = $player_id;
                $except['date'] = $date;

                if ( $enabled_chopped_lock_in_calculatecashback ) {
                    $this->CI->player_model->rollbackTrans();
                    $this->chopped_lock_phase_one_except_list[$player_id] = $except;
                    $this->CI->utils->debug_log("OGP-27272 cost of foreach.player_bet_map as player_id(Exception)", microtime(true)-$startTime4_chopped_lock, 'player_id:', $player_id);

                }
                if($e->getCode() == Group_level::EXCEPTION_CODE_IN_CANCEL_CONTINUE){
                    $this->CI->utils->debug_log("OGP-27832 will break");
                    break; // leave the foreach
                }
                /// If its enable, "throw $e" will break foreach().
                // if($enabled_chopped_lock_in_calculatecashback){
                //     throw $e; // Catch Exception at outter try block, https://stackoverflow.com/a/9041245
                // }
            } /// EOF try for $enabled_chopped_lock_in_calculatecashback

        } // EOF foreach($player_bet_map as $player_id => $player_bet_list){...

        if( !empty($byTierTodoList) ){

            // $byTierTodoList['tag'][$game_tag_id][$player_id]
            foreach($byTierTodoList['tag'] as $game_tag_id => $byTierTodoByPlayer){

                foreach($byTierTodoByPlayer as $_player_id => $byTierTodoParams){
                    $_result = 0;
                    $startTime4_chopped_lock=microtime(true); // for 27272 cost of foreach.byTierTodoByPlayer as _player_id
                    if ( $enabled_chopped_lock_in_calculatecashback ) {
                        $this->CI->player_model->startTrans();
                    }
                    try{ /// try for $enabled_chopped_lock_in_calculatecashback
                        // Script Begin - OGP-278332 Solution2.
                        $this->CI->utils->debug_log('OGP-24813.1647.byTierTodoParams._player_id:'. $_player_id);
                        $this->CI->utils->debug_log('OGP-24813.1647.byTierTodoParams.count:', (empty($byTierTodoParams)? 0: count($byTierTodoParams))
                                                    , 'byTierTodoParams:'
                                                    , array_filter($byTierTodoParams, function($v, $k) {
                                                        // filter the contents too long, key = wc_amount_map.
                                                        return $k != 'wc_amount_map';
                                                    }, ARRAY_FILTER_USE_BOTH)
                        ); // EOF utils->debug_log()
                        if( ! empty($byTierTodoParams['sutotal_bet_part_list']) ){
                            $sutotal_bet_part_list = $byTierTodoParams['sutotal_bet_part_list'];
                            $player_id = $byTierTodoParams['sutotal_bet_part_list'][0]->player_id;
                            $levelId = $byTierTodoParams['sutotal_bet_part_list'][0]->levelId;
                            $game_tag_id = $game_tag_id;
                            $tag_code = $byTierTodoParams['sutotal_bet_part_list'][0]->tag_code;
                            $is_parlay = (isset($byTierTodoParams['sutotal_bet_part_list'][0]->is_parlay)) ? $byTierTodoParams['sutotal_bet_part_list'][0]->is_parlay : 0;

                            if( !empty($byTierTodoParams['cashback_rules']) ){
                                $cashback_rules = $byTierTodoParams['cashback_rules']; // issue // #6
                                $cashback_settings = $byTierTodoParams['cashback_settings']; // issue // #6.1
                                $subtotal_bet_amount = $byTierTodoParams['subtotal_bet_amount'];
                                $date = $byTierTodoParams['date']; // issue // #8
                                $wc_amount_map = $byTierTodoParams['wc_amount_map'];// issue // #9
                                $withdraw_condition_bet_times = $byTierTodoParams['withdraw_condition_bet_times'];// issue // #10
                                $isPayTime = $byTierTodoParams['isPayTime'];// issue // #11

                                $_result = $this->_calculatePlayerCashbackByTierByTags( $player_id // #1
                                    , $levelId // #2
                                    , $game_tag_id // #3
                                    , $tag_code // #4
                                    , $is_parlay // #5
                                    , $cashback_rules // #6
                                    , $cashback_settings // #6.1
                                    , $subtotal_bet_amount // #7
                                    , $date // #8
                                    , $wc_amount_map // #9
                                    , $withdraw_condition_bet_times // #10
                                    , $isPayTime // #11
                                    , $sutotal_bet_part_list // #12
                                    , $recalculate_deducted_process_table // #13
                                    , $recalculate_cashback_table // #14
                                    , $byTierTodoParams['calcMode'] // #15
                                );
                            } // EOF if( !empty($byTierTodoParams['cashback_rules']) ){...
                        } // EOF if( ! empty($byTierTodoParams['sutotal_bet_part_list']) ){...

                        // Script End - OGP-278332 Solution2.

                        $cnt += $_result;

                        $exception_in_chopped_lock_phase_two = $this->CI->utils->getConfig('exception_in_chopped_lock_phase_two');
                        if( ! empty($exception_in_chopped_lock_phase_two) ){
                            $this->CI->utils->debug_log('exception_in_chopped_lock_phase_two.player_id:', $player_id);
                        }
                        $doException = false;
                        if( is_numeric($exception_in_chopped_lock_phase_two) ){
                            $doException = $exception_in_chopped_lock_phase_two == $player_id;
                        } else if( is_array($exception_in_chopped_lock_phase_two) ){
                            $doException = in_array($player_id, $exception_in_chopped_lock_phase_two);
                        } else if( is_bool($exception_in_chopped_lock_phase_two) ){
                            $doException = $exception_in_chopped_lock_phase_two === true;
                        }
                        if( $doException && $enabled_chopped_lock_in_calculatecashback){
                            throw new Exception('Exception In chopped_lock_phase_Two for testing. player_id:'. $player_id);
                        }

                        if ( $enabled_chopped_lock_in_calculatecashback ) {
                            if( !empty($enabled_dryrun_in_calculatecashback) ){
                                // dryrun
                                $this->CI->player_model->rollbackTrans();
                                $this->CI->utils->debug_log('DRYRUN In calculateTotalCashback().');

                                if( ! empty( $this->group_level->detectFileInSecret_keys(Group_level::FILENAME_SUSPEND_IN_DRYRUN_FOR_CALCULATECASHBACK)) ){
                                    $this->CI->utils->debug_log("OGP-27832 cancel continue of dryrun in phase Two", 'player_id:', $player_id);
                                    throw new Exception('Exception for do_continue_in_dryrun_of_calculatecashback=0 in phase Two. player_id:'. $player_id, Group_level::EXCEPTION_CODE_IN_CANCEL_CONTINUE);
                                    // "break 2" in Exception by Group_level::EXCEPTION_CODE_IN_CANCEL_CONTINUE
                                }
                            }else{
                                $this->CI->player_model->endTransWithSucc();
                            }
                        }else{
                            // rollbackTrans() in Command::calculateCashback()
                        }
                        $this->CI->utils->debug_log("OGP-27272 cost of foreach.byTierTodoByPlayer as _player_id", microtime(true)-$startTime4_chopped_lock, '_player_id', $_player_id);

                    }catch(Exception $e){/// catch for $enabled_chopped_lock_in_calculatecashback
                        $this->CI->utils->debug_log(__METHOD__ . '(): 1686._calculatePlayerCashbackByTierByTags()._result:', $_result, 'byTierTodoParams:', $byTierTodoParams);
                        //
                        $this->CI->utils->error_log('got exception in lockAndTrans', $e);
                        $except = [];
                        $except['code'] = $e->getCode();
                        $except['message'] = $e->getMessage();
                        // for clear the cashback data by player and date
                        $except['player_id'] = $_player_id;
                        $except['date'] = $date;

                        if ( $enabled_chopped_lock_in_calculatecashback ) {
                            $this->CI->player_model->rollbackTrans();
                            $this->chopped_lock_phase_two_except_list[$_player_id] = $except;
                            $this->CI->utils->debug_log("OGP-27272 cost of foreach.byTierTodoByPlayer as _player_id(Exception)", microtime(true)-$startTime4_chopped_lock, '_player_id:', $_player_id);
                        }
                        if($e->getCode() == Group_level::EXCEPTION_CODE_IN_CANCEL_CONTINUE){
                            $this->CI->utils->debug_log("OGP-27832 break 2");
                            break 2; // this will break both foreach loops
                        }
                        /// If its enable, "throw $e" will break foreach().
                        // if($enabled_chopped_lock_in_calculatecashback){
                        //     throw $e; // Catch Exception at outter try block, https://stackoverflow.com/a/9041245
                        // }
                    } finally { /// finally for $enabled_chopped_lock_in_calculatecashback

                    } /// EOF try for $enabled_chopped_lock_in_calculatecashback

                } // EOF foreach($byTierTodoByPlayer as $_player_id => $byTierTodoParams){...

            } // EOF foreach($byTierTodoList['tag'] as $game_tag_id => $byTierTodoByPlayer){...

            $this->CI->utils->debug_log(__METHOD__ . 'OGP-27832.1806.chopped_lock_phase_one_except_list:'
                , empty($this->chopped_lock_phase_one_except_list)? null: $this->chopped_lock_phase_one_except_list
                , 'chopped_lock_phase_two_except_list:'
                , empty($this->chopped_lock_phase_two_except_list)? null: $this->chopped_lock_phase_two_except_list
            );

            $clear_by_player_date = []; // delete by the reason, cashback data is incomplete
            if( ! empty($this->chopped_lock_phase_one_except_list) ){
                array_walk( $this->chopped_lock_phase_one_except_list, function($except, $_player_id) use ( &$clear_by_player_date ){
                    $clear_by = [];
                    $clear_by['player_id'] = $_player_id;
                    $clear_by['date'] = $except['date'];
                    $clear_by_player_date[$_player_id] = $clear_by;
                });
            }
            if( ! empty($this->chopped_lock_phase_two_except_list) ){
                array_walk( $this->chopped_lock_phase_two_except_list, function($except, $_player_id) use ( &$clear_by_player_date ){
                    if( empty($clear_by_player_date[$_player_id]) ){
                        $clear_by = [];
                        $clear_by['player_id'] = $_player_id;
                        $clear_by['date'] = $except['date'];
                        $clear_by_player_date[$_player_id] = $clear_by;
                    }
                });
            }
            if( ! empty($clear_by_player_date)
                && empty($enabled_dryrun_in_calculatecashback) /// Not dryrun
            ){
                $this->CI->load->library(['player_cashback_library']);
                foreach($clear_by_player_date as $_will_delete_data){
                    $_rlt = $this->CI->player_cashback_library->delete_cashback_by_player_date($_will_delete_data['player_id'], $_will_delete_data['date']);
                }

                $this->CI->utils->debug_log(__METHOD__ . 'OGP-27832.1857.incomplete cashback in player_id', array_keys($clear_by_player_date) );
            }else{
                $this->CI->utils->debug_log(__METHOD__ . 'OGP-27832.1857.incomplete cashback in player_id', NULL );
            }

                // $rlt = $this->_calculatePlayerCashbackByTier(  $byTierTodoParams[0]
                //                                         , $byTierTodoParams[1]
                //                                         , $byTierTodoParams[2]
                //                                         , $byTierTodoParams[3]
                //                                         , $byTierTodoParams[4]
                //                                         , $byTierTodoParams[5]
                //                                         , $byTierTodoParams[6] );
                // $rlt = call_user_func_array([$this, '_calculatePlayerCashbackByTier'], $byTierTodoParams); // $this->_calculatePlayerCashbackByTier();


        } // EOF if( !empty($byTierTodoList) ){...
        /// Script End - OGP-278332 Solution2.

        if( $this->CI->utils->getConfig('use_accumulate_deduction_when_calculate_cashback') ){
            $this->group_level->syncReCalculateCashbackDaily($date, $uniqueId);
        }

        return $cnt;
    }


    /**
     * handle to calculate Cashback By Tier and Tags
     *
     * @param integer $player_id The player.playerId
     * @param [type] $level_id
     * @param integer $game_tag_id The game_tags.id
     * @param [type] $tag_code
     * @param integer $is_parlay The field only in game_logs.
     * @param [type] $cashback_rules
     * @param [type] $cashback_settings
     * @param [type] $subtotal_bet_amount
     * @param [type] $date
     * @param [type] $wc_amount_map
     * @param [type] $withdraw_condition_bet_times
     * @param [type] $isPayTime
     * @param [type] $sutotal_bet_part_list
     * @return void
     */
    protected function _calculatePlayerCashbackByTierByTags( $player_id // #1
        , $level_id // #2
        , $game_tag_id // #3
        , $tag_code // #4
        , $is_parlay // #5
        , $cashback_rules // #6
        , $cashback_settings // #6.1
        , $subtotal_bet_amount // #7
        , $date // #8
        , &$wc_amount_map // #9
        , $withdraw_condition_bet_times // #10
        , $isPayTime // #11
        , $sutotal_bet_part_list // #12
        , $recalculate_deducted_process_table // #13
        , $recalculate_cashback_table // #14
        , $calcMode = Common_Cashback_multiple_rules::CALC_MODE_BY_TIER_IN_GAME_TAG // #15 tier, heap
    ){
        $this->CI->load->model(['cashback_to_bet_list_mapping']);

        $cashback_type = Group_level::NORMAL_CASHBACK;
        $cnt = 0;


        if( ! empty($cashback_rules) ){

            $this->_sortCashbackRules($cashback_rules);

            $this->CI->utils->debug_log(__METHOD__ . '(): debug3.1564'
                , 'cashback_rules:'
                , $cashback_rules
                // , 'sutotal_bet_part_list:'
                // , $sutotal_bet_part_list
            );


            $_subtotal_bet_amount = $subtotal_bet_amount;
            $bonus = 0;

            // for auto deduct withdraw condition
            $sutotal_bet_part_list_deducted = [];
            if( ! empty($sutotal_bet_part_list) ) {
                $_subtotal_bet_amount = 0; // for re-sum, after auto_deduct_withdraw_condition_from_bet()
                foreach( $sutotal_bet_part_list as $indexNumber => $pbbd){
                    $player_id = $pbbd->player_id;
                    $_pbbd = clone $pbbd;
                    $_pbbd->original_betting_total = $_pbbd->betting_total; // clone original
                    $this->group_level->auto_deduct_withdraw_condition_from_bet($player_id, $wc_amount_map, $_pbbd, $isPayTime, $date, $recalculate_deducted_process_table);
                    $sutotal_bet_part_list_deducted[$indexNumber] = $_pbbd; // override after auto_deduct_withdraw_condition
                    $_subtotal_bet_amount += $_pbbd->betting_total;
                } // EOF foreach( $sutotal_bet_part_list as $pbbd){...
            }
            $_subtotal_bet_amount_deducted = $_subtotal_bet_amount; // for rate

            // $sutotal_bet_part_list
            // $sutotal_bet_part_list_deducted

            if($calcMode == Common_Cashback_multiple_rules::CALC_MODE_BY_TIER_IN_GAME_TAG ){
                /// Get $bonus from $_subtotal_bet_amount by cashback_rule
                foreach( $cashback_rules as $rule_id_index => $cashback_rule ){ // for cashback detail
                    $min = $cashback_rule['min_bet_amount'];
                    $max = $cashback_rule['max_bet_amount'];
                    $percentage = $cashback_rule['cashback_percentage']* 0.01; // percentage %
                    $max_bonus = $cashback_rule['max_cashback_amount'];
                    $history_id = 'cb_mr_' . $cashback_rule['type'] . '_' . $cashback_rule['cb_mr_rule_id'];
                    $max_bonus = (empty($max_bonus)) ? $this->cashback_common_settings->max_cashback_amount : $max_bonus;
                    // $max_bonus = 0;

                    $calced = 0;
                    $_bonus = $this->CI->utils->getBonusByTier($min, $max, $percentage, $_subtotal_bet_amount, $calced, $max_bonus);

                    $_subtotal_bet_amount -= $calced;
                    if( ! empty($calced) ){
                        if( empty($cashback_rules[$rule_id_index]['resultsByTier']) ){
                            $cashback_rules[$rule_id_index]['resultsByTier'] = [];
                        }
                        $resultsByTier = [];
                        $resultsByTier['calced'] = $calced;
                        $resultsByTier['bonus'] = $_bonus;
                        $resultsByTier['history_id'] = $history_id;
                        $resultsByTier['getBonusBy'] = 'tier';
                        $cashback_rules[$rule_id_index]['resultsByTier'] = $resultsByTier;
                        $bonus += $_bonus; // add for cashback amount
                    }
                    if( empty($_subtotal_bet_amount) ){
                        break;
                    }
                } // EOF foreach( $cashback_rules as $rule_id_index => $cashback_rule ){...
            }else if($calcMode == Common_Cashback_multiple_rules::CALC_MODE_BY_HEAP_IN_GAME_TAG){
                // $cashback_rule = $this->getAvailableCashbackRule($cashback_rules, $_subtotal_bet_amount, $level_id, $game_platform_id, $game_type_id, $game_description_id);
                $matched_cashback_rule = $this->_match_rule($cashback_rules, $_subtotal_bet_amount);

                if(!empty($matched_cashback_rule)){
                    $cashback_rules = [];
                    // $cashback_rule['history_id'] = 'cb_mr_' . $matched_cashback_rule['type'] . '_' . $matched_cashback_rule['cb_mr_rule_id'];
                    $cashback_rule['cashback_percentage'] = (float)$matched_cashback_rule['cashback_percentage']; // for float type
                    $cashback_rule['max_cashback_amount'] = (float)$matched_cashback_rule['max_cashback_amount']; // for float type
                    $cashback_rules[] = $matched_cashback_rule;
                }
                $calced = $_subtotal_bet_amount;
                $rate = (float)$matched_cashback_rule['cashback_percentage'];
                $_bonus = $_subtotal_bet_amount * ($rate / 100);

                $max_bonus = (empty($matched_cashback_rule['max_cashback_amount'])) ? $this->cashback_common_settings->max_cashback_amount : $matched_cashback_rule['max_cashback_amount'];
                if($_bonus > $max_bonus){
                    $_bonus = $matched_cashback_rule['max_cashback_amount'];
                }

                $history_id = 'cb_mr_' . $matched_cashback_rule['type'] . '_' . $matched_cashback_rule['cb_mr_rule_id'];
                $resultsByTier = [];
                $resultsByTier['calced'] = $calced;
                $resultsByTier['bonus'] = $_bonus;
                $resultsByTier['history_id'] = $history_id;
                $resultsByTier['getBonusBy'] = 'heap';
                foreach( $cashback_rules as $rule_id_index => $cashback_rule ){ // for cashback detail
                    $cashback_rules[$rule_id_index]['resultsByTier'] = $resultsByTier;
                }
                $bonus = $_bonus; // assign for cashback amount
            }

            $total_date = $date;
            if( ! empty($sutotal_bet_part_list_deducted) ) {
                $syncCashbackDailyParamsList = [];
                $syncCashbackDailyResultsList = [];
                foreach( $sutotal_bet_part_list_deducted as $pbbd){
                    $player_id = $pbbd->player_id;
                    $level_id = $pbbd->levelId;
                    $is_parlay = (isset($pbbd->is_parlay)) ? $pbbd->is_parlay : 0;
                    $game_platform_id = $pbbd->game_platform_id;
                    $game_type_id = $pbbd->game_type_id;
                    $game_description_id = $pbbd->game_description_id;
                    $original_bet_amount = $this->CI->utils->roundCurrencyForShow($pbbd->original_betting_total);
                    $uniqueid = sprintf("%s_%s_%s_%s_%s", $total_date, $cashback_type, $player_id, $game_description_id, $is_parlay);
                    // $tag_code = null;
                    // if(isset($pbbd->tag_code)){
                    //     $tag_code = $pbbd->tag_code;
                    // }
                    // $game_tag_id = null;
                    // if(isset($pbbd->game_tag_id)){
                    //     $game_tag_id = $pbbd->game_tag_id;
                    // }

                    // $max_bonus = $cashback_rule['max_cashback_amount'];
                    // $max_bonus = (empty($max_bonus)) ? $this->cashback_common_settings->max_cashback_amount : $max_bonus;
                    $max_bonus = 0; // @todo Maybe the sum of betting, there will be two settings (contains max_bonus)
                    $rate = 0; // @todo Maybe the sum of betting, there will be two rates
                    // $withdraw_condition_amount = $this->CI->utils->roundCurrencyForShow($cashback_amount * $withdraw_condition_bet_times);
                    $withdraw_condition_amount = 0; // @todo moved to the first cashback data
                    $cashback_amount = 0; // @todo moved to the first cashback data

                    $syncCashbackDailyParamsList[] = [  $player_id // #1
                                                            , $game_platform_id // #2
                                                            , $game_description_id // #3
                                                            , $total_date // #4
                                                            , $cashback_amount // #5
                                                            , $history_id // #6
                                                            , $game_type_id // #7
                                                            , $level_id // #8
                                                            , $rate // #9
                                                            , $this->CI->utils->roundCurrencyForShow($pbbd->betting_total) // #10
                                                            , $withdraw_condition_amount // #11
                                                            , $max_bonus // #12
                                                            , $original_bet_amount // #13
                                                            , $cashback_type // #14
                                                            , NULL // #15, invited_player_id
                                                            , $uniqueid // #16
                                                    ];
                } // EOF foreach( $sutotal_bet_part_list_deducted as $pbbd){...

                $syncCashbackDailyParamsList[0][8] =  !empty($_subtotal_bet_amount_deducted)? $this->CI->utils->roundCurrencyForShow( ( $bonus/ $_subtotal_bet_amount_deducted)* 100 ): 0; // rate
                $syncCashbackDailyParamsList[0][4] = $bonus; // cashback_amount

                $appoint_id = 0;
                $indexNumber = 0;
                foreach($syncCashbackDailyParamsList as $syncCashbackDailyParams){

                    list($player_id // #1
                        , $game_platform_id // #2
                        , $game_description_id // #3
                        , $total_date // #4
                        , $cashback_amount // #5
                        , $history_id // #6
                        , $game_type_id // #7
                        , $level_id // #8
                        , $rate // #9
                        , $betting_total // #10
                        , $withdraw_condition_amount // #11
                        , $max_bonus // #12
                        , $original_bet_amount // #13
                        , $cashback_type // #14
                        , $invited_player_id // #15, invited_player_id
                        , $uniqueid  // #16,
                    ) = $syncCashbackDailyParams;
                    $applied_info = [];
                    $applied_info['common_cashback_multiple_range_settings'] = $cashback_settings;
                    $applied_info['common_cashback_multiple_range_rules'] = $cashback_rules;
                    $applied_info['total_player_game_hour'] = $sutotal_bet_part_list_deducted; // for CB detail popup.
                    $applied_info['cashback_amount'] = $cashback_amount;
                    $applied_info['appoint_id'] = $appoint_id;

                    $affected_id = 0;
                    $syncAction = null;
                    $affected_id = $this->group_level->syncCashbackDaily( $player_id // #1
                                                    , $game_platform_id // #2
                                                    , $game_description_id // #3
                                                    , $total_date // #4
                                                    , $cashback_amount // #5
                                                    , $history_id // #6
                                                    , $game_type_id // #7
                                                    , $level_id // #8
                                                    , $rate // #9
                                                    , $betting_total // #10
                                                    , $withdraw_condition_amount // #11
                                                    , $max_bonus // #12
                                                    , $original_bet_amount // #13
                                                    , $cashback_type // #14
                                                    , $invited_player_id // #15, invited_player_id
                                                    , $uniqueid // #16
                                                    , $applied_info // #17
                                                    , $appoint_id // #18
                                                    , $recalculate_cashback_table // #19
                                                );

                    if( ! empty($affected_id)){
                        $cnt++;
                        $_cashback_table = 'total_cashback_player_game_daily';
                        if( ! empty($recalculate_cashback_table) ){
                            $_cashback_table = $recalculate_cashback_table;
                        }
                        $_params = [];
                        $_params['cashback_table'] = $_cashback_table;
                        $_params['player_id'] = $player_id;
                        $_params['cashback_id'] = $affected_id;
                        $_params['bet_source_table'] = $pbbd->source_table;
                        $_params['bet_source_id_list'] = $pbbd->source_id_list;
                        $_params['is_pay'] = '0';
                        $_rlt = $this->CI->cashback_to_bet_list_mapping->syncToDataWithBetSourceIdListAfterSyncCashbackDaily($_params);
                        $this->CI->utils->debug_log('OGP-24813.1970._rlt', $_rlt);
                    } // EOF if( ! empty($affected_id_syncCashbackDaily)){...

                        $_syncCashbackDailyResults = [];
                        $_syncCashbackDailyResults['affected_id'] = $affected_id;
                        $syncCashbackDailyResultsList[] = $_syncCashbackDailyResults;

                    if($indexNumber == 0){
                        $appoint_id = $affected_id;
                        if( ! empty( $appoint_id) ){
                            // $to_id = $affected_id;
                            // $mergee_applied_info = [];
                            // $mergee_applied_info['appoint_id'] = $appoint_id;
                            // $applied_info_orig = [];
                            // $this->CI->total_cashback_player_game_daily->updateOrMergeAppointInfo($to_id, $mergee_applied_info, $applied_info_orig);
                            $this->CI->total_cashback_player_game_daily->setAppointId($affected_id, $appoint_id, $recalculate_cashback_table);
                        }else{
                            $this->CI->utils->debug_log( '1729.appoint_id is empty.');
                        }
                    }

                    $indexNumber++;
                }// EOF foreach($syncCashbackDailyParamsList as $syncCashbackDailyParams){...

            } // EOF if( ! empty($sutotal_bet_part_list) ) {...

        }else{
            /// @todo handle empty $cashback_rules
            // there are no rules match the betting of the game.
        }
        return $cnt;
    } // EOF _calculatePlayerCashbackByTier

    protected function _calculatePlayerCashback($pbbd, $cashback_rules, $total_bet_amount, $date, &$wc_amount_map, $withdraw_condition_bet_times, $isPayTime, $recalculate_deducted_process_table, $recalculate_cashback_table){
        $cashback_type = Group_level::NORMAL_CASHBACK;
        $this->CI->load->model(['cashback_to_bet_list_mapping']);

        $player_id = $pbbd->player_id;
        $level_id = $pbbd->levelId;
        $game_platform_id = $pbbd->game_platform_id;
        $game_type_id = $pbbd->game_type_id;
        $game_description_id = $pbbd->game_description_id;
        $tag_code = null;
        if(isset($pbbd->tag_code)){
            $tag_code = $pbbd->tag_code;
        }
        $game_tag_id = null;
        if(isset($pbbd->game_tag_id)){
            $game_tag_id = $pbbd->game_tag_id;
        }
        $is_parlay = (isset($pbbd->is_parlay)) ? $pbbd->is_parlay : 0;

        $cashback_rule = $this->getAvailableCashbackRule($cashback_rules, $total_bet_amount, $level_id, $game_platform_id, $game_type_id, $game_description_id);
$this->CI->utils->debug_log( 'OGP-24813.2015.pbbd', $pbbd
, 'recalculate_cashback_table:', $recalculate_cashback_table
, 'recalculate_deducted_process_table:', $recalculate_deducted_process_table );
        if(empty($cashback_rule)){
            $this->CI->utils->debug_log(__METHOD__ . '(): invalid cashback rate in getAvailableCashbackRule(). cashback_rules is empty. params:'
                , 'total_bet_amount:', $total_bet_amount
                , 'level_id:', $level_id
                , 'game_platform_id:', $game_platform_id
                ,'game_type_id:', $game_type_id
                ,'game_description_id:' , $game_description_id
                , 'player_id', $player_id
                , 'cashback_rules.count', (empty($cashback_rules)? 0: count($cashback_rules))
            );
            return FALSE;
        }

        $rate = $cashback_rule['cashback_percentage'];
        $max_bonus = $cashback_rule['max_cashback_amount'];
        $history_id = $cashback_rule['history_id'];

        if($rate <= 0){
            $this->CI->utils->debug_log(__METHOD__ . '(): invalid cashback rate', 'cashback_rules', $cashback_rules
            , 'total_bet_amount', $total_bet_amount
            , 'rate', $rate
            , 'player_id', $player_id
            , 'game_platform_id:', $game_platform_id
            , 'game_type_id:', $game_type_id
            , 'game_description_id', $game_description_id
            , 'game_tag_id', $game_tag_id
            , 'tag_code', $tag_code
            , 'history_id', $history_id
            , 'level_id', $level_id
            , 'betting', $pbbd->betting_total
            , 'betting*rate', $pbbd->betting_total * ($rate / 100)
            , 'max_cashback_amount', $max_bonus
            , 'availabled_cashback_rule', $cashback_rule
        );
            return FALSE;
        }

        $max_bonus = (empty($max_bonus)) ? $this->cashback_common_settings->max_cashback_amount : $max_bonus;


        $this->CI->utils->debug_log(__METHOD__ . '(): final'
        , 'cashback_rules', $cashback_rules
        , 'total_bet_amount', $total_bet_amount
        , 'rate', $rate
        , 'player_id', $player_id
        , 'game_description_id', $game_description_id
        , 'game_tag_id', $game_tag_id
        , 'tag_code', $tag_code
        , 'game_type_id:', $game_type_id
        , 'history_id', $history_id
        , 'level_id', $level_id
        , 'betting', $pbbd->betting_total
        , 'betting*rate', $pbbd->betting_total * ($rate / 100)
        , 'max_cashback_amount', $max_bonus
        , 'is_parlay', $is_parlay);

        $game_platform_id = $pbbd->game_platform_id;
        $game_description_id = $pbbd->game_description_id;
        $game_type_id = $pbbd->game_type_id;
        $total_date = $date;

        $original_bet_amount = $this->CI->utils->roundCurrencyForShow($pbbd->betting_total);

        $this->CI->utils->debug_log('1427.auto_deduct_withdraw_condition_from_bet'
        , '$player_id:', $player_id
        , '$wc_amount_map_counter:', empty($wc_amount_map)? 0:count($wc_amount_map)
        , '$pbbd:', $pbbd
        , '$isPayTime:', $isPayTime);
$startTime=microtime(true);
        $this->group_level->auto_deduct_withdraw_condition_from_bet($player_id, $wc_amount_map, $pbbd, $isPayTime, $total_date, $recalculate_deducted_process_table);
$this->CI->utils->debug_log("OGP-27272 cost of group_level->auto_deduct_withdraw_condition_from_bet", microtime(true)-$startTime);
        $cashback_amount_float = $pbbd->betting_total * ($rate / 100);
        if($max_bonus < $cashback_amount_float){
            $cashback_amount_float = $max_bonus;
        }
        $cashback_amount = $this->CI->utils->roundCurrencyForShow($cashback_amount_float);

        $withdraw_condition_amount = $this->CI->utils->roundCurrencyForShow($cashback_amount * $withdraw_condition_bet_times);

        $uniqueid = sprintf("%s_%s_%s_%s_%s", $total_date, $cashback_type, $player_id, $game_description_id, $is_parlay);


        $affected_id = $this->group_level->syncCashbackDaily($player_id, $game_platform_id, $game_description_id, $total_date,
            $cashback_amount, $history_id, $game_type_id, $level_id, $rate,
            $this->CI->utils->roundCurrencyForShow($pbbd->betting_total),
            $withdraw_condition_amount, $max_bonus, $original_bet_amount, $cashback_type, NULL, $uniqueid,
             null, 0, $recalculate_cashback_table);

        if( ! empty($affected_id)){
            $_cashback_table = 'total_cashback_player_game_daily';
            if( ! empty($recalculate_cashback_table) ){
                $_cashback_table = $recalculate_cashback_table;
            }
            $_params = [];
            $_params['cashback_table'] = $_cashback_table;
            $_params['player_id'] = $player_id;
            $_params['cashback_id'] = $affected_id;
            $_params['bet_source_table'] = $pbbd->source_table;
            $_params['bet_source_id_list'] = $pbbd->source_id_list;
            $_params['is_pay'] = '0';
            $_rlt = $this->CI->cashback_to_bet_list_mapping->syncToDataWithBetSourceIdListAfterSyncCashbackDaily($_params);
            $this->CI->utils->debug_log('OGP-24813.2110._rlt', $_rlt);
        } // EOF if( ! empty($affected_id_syncCashbackDaily)){...

        return TRUE;
    }
}