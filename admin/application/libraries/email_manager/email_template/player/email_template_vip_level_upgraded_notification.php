<?php

require_once (dirname(__FILE__) . '/abstract_email_template_main.php');

Class email_template_vip_level_upgraded_notification extends abstract_email_template_main
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->previous_viplevel = isset($params['previous_viplevel']) ? $params['previous_viplevel'] : 0;
        $this->new_viplevel = isset($params['new_viplevel']) ? $params['new_viplevel'] : 0;
    }

    public function replaceRule()
    {
        $parent_element = parent::replaceRule();
        $element = [
            '[previous_viplevel]' => [
                'callback' => 'getPreviousViplevel',
                'preview'  => 0
            ],
            '[new_viplevel]' => [
                'callback' => 'getNewViplevel',
                'preview'  => 0
            ]
        ];

        return array_merge($parent_element, $element);
    }

    public function getPreviousViplevel()
    {
        return $this->previous_viplevel;
    }

    public function getNewViplevel()
    {
        return $this->new_viplevel;
    }
    public function getTemplateParams()
    {
        $params = [
            'player_name' => $this->getPlayerName(),
            'player_email' => $this->getPlayerEmail(),
            'player_username' => $this->getPlayerUsername(),
            'previous_viplevel' => $this->getPreviousViplevel(),
            'new_viplevel' => $this->getNewViplevel(),
        ];

        return $params;
    }

    public function getApiTemplateID()
    {

        $current_smtp_api = $this->CI->config->item('current_smtp_api');
        $template_setting_key = 'vip_level_upgraded_notification';
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
