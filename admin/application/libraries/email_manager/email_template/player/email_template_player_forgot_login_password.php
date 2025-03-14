<?php

require_once (dirname(__FILE__) . '/abstract_email_template_main.php');

Class email_template_player_forgot_login_password extends abstract_email_template_main
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->verify_code = isset($params['verify_code']) ? $params['verify_code'] : 0;
    }

    public function replaceRule()
    {
        $parent_element = parent::replaceRule();
        $element = [
            '[verify_code]' => [
                'callback' => 'getVerifyCode',
                'preview'  => 0
            ],
            '[verify_code_exptime]' => [
                'callback' => 'getExpireTime',
                'preview'  => 1
            ]
        ];

        return array_merge($parent_element, $element);
    }

    public function getVerifyCode()
    {
        return $this->verify_code;
    }

    public function getExpireTime()
    {
        $expire_mins = $this->CI->config->item('password_reset_code_expire_mins') ? : 30;
        return $expire_mins;
    }
    public function getTemplateParams()
    {
        $params = [
            'player_name' => $this->getPlayerName(),
            'player_email' => $this->getPlayerEmail(),
            'player_username' => $this->getPlayerUsername(),
            'verify_code' => $this->getVerifyCode(),
            'verify_code_exptime' => $this->getExpireTime(),
        ];

        return $params;
    }

    public function getApiTemplateID()
    {
        $current_smtp_api = $this->CI->config->item('current_smtp_api');
        $template_setting_key = 'player_forgot_login_password';
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
