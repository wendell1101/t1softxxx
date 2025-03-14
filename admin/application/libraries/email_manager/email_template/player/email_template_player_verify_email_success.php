<?php

require_once (dirname(__FILE__) . '/abstract_email_template_main.php');

Class email_template_player_verify_email_success extends abstract_email_template_main
{
    public function __construct($params)
    {
        parent::__construct($params);
    }

    public function replaceRule()
    {
        $parent_element = parent::replaceRule();
        return $parent_element;
    }
    public function getTemplateParams()
    {
        $params = [
            'player_name' => $this->getPlayerName(),
            'player_email' => $this->getPlayerEmail(),
            'player_username' => $this->getPlayerUsername(),
        ];

        return $params;
    }

    public function getApiTemplateID()
    {
        $current_smtp_api = $this->CI->config->item('current_smtp_api');
        $template_setting_key = 'player_verify_email_success';
        $smtp_api_template_config = $this->CI->config->item('smtp_api_template');
        if(isset($smtp_api_template_config[$current_smtp_api]) && isset($smtp_api_template_config[$current_smtp_api][$template_setting_key])) {
            $template_setting = $smtp_api_template_config[$current_smtp_api][$template_setting_key];
            if(isset($template_setting['enable']) && ($template_setting['enable'] == true) && !empty($template_setting['template_id'])){
                return $template_setting['template_id'];
            }
        }
        return false;
    }
}
