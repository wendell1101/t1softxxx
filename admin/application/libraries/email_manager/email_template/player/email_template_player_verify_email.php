<?php

require_once (dirname(__FILE__) . '/abstract_email_template_main.php');

Class email_template_player_verify_email extends abstract_email_template_main
{
    public function __construct($params)
    {
        parent::__construct($params);
        $this->setExpireTime();
        $this->verify_code = isset($params['verify_code']) ? $params['verify_code'] : 0;
    }

    public function replaceRule()
    {
        $parent_element = parent::replaceRule();
        $element = [
            '[link]' => [
                'callback' => 'getVerifyLink',
                'preview'  => 1
            ],
            '[link_exptime]' => [
                'callback' => 'getExpireTime',
                'preview'  => 1
            ]
        ];

        if ($this->CI->config->item('email_template_show_verify_token')) {
            $verify_token = [
                '[verify_token]' => [
                    'callback' => 'getVerifyToken',
                    'preview'  => 1
                ]
            ];

            $element = array_merge($element, $verify_token);
        }

        if ($this->CI->config->item('enable_verify_mail_via_otp')) {
            $otp_code = [
                '[one_time_password]' => [
                    'callback' => 'getVerifyCode',
                    'preview'  => 1
                ]
            ];

            $element = array_merge($element, $otp_code);
        }


        return array_merge($parent_element, $element);
    }

    private function setExpireTime()
    {
        $player = $this->user;
        $this->expire_mins = $this->CI->config->item('verify_link_expire_mins') ?: 720;
        $this->expired_time = date('Y-m-d H:i:s', strtotime('+' . $this->expire_mins . ' mins'));

        $data = ["email_verify_exptime" => $this->expired_time];
        $this->CI->player_model->updatePlayer($player['playerId'], $data);
    }

    public function getVerifyLink( $link_only = false)
    {
        $url = $this->CI->utils->getSystemUrl('player', '/iframe_module/verify');
        $link = $url . '/' . $this->getVerifyToken();

        if ($this->email_mode == 'text' || $link_only) {
            return $link;
        } else {
            return anchor($link, $link, 'target="_blank"');
        }
    }

    public function getExpireTime()
    {
        return $this->expire_mins;
    }

    public function getVerifyToken()
    {
        $player = $this->user;
        $verify_token = md5($player['playerId']) . $player['playerId'] . md5($this->expired_time);

        return $verify_token;
    }

    public function getVerifyCode()
    {
        return $this->verify_code;
    }

    public function getTemplateParams()
    {
        $params = [
            'player_name' => $this->getPlayerName(),
            'player_email' => $this->getPlayerEmail(),
            'player_username' => $this->getPlayerUsername(),
            'link' => $this->getVerifyLink(true),
            'link_exptime' => $this->getExpireTime(),
            'verify_token' => $this->getVerifyToken(),
            'one_time_password' => $this->getVerifyCode(),
        ];

        return $params;
    }

    public function getApiTemplateID()
    {
        $current_smtp_api = $this->CI->config->item('current_smtp_api');
        $template_setting_key = 'player_verify_email';
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