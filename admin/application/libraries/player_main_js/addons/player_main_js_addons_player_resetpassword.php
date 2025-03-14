<?php
/**
 * player_main_js_addons_player_resetpassword.php
 *
 * @author
 */
class Player_main_js_addons_player_resetpassword extends Player_main_js_addons_abstract {
    public function isEnabled(){
        return $this->CI->utils->getConfig('force_reset_password_after_operator_reset_password_in_sbe');
    }

    public function variables(){
        $variables = [
            'player_resetpassword' => [
                'enabled' => $this->CI->utils->getConfig('force_reset_password_after_operator_reset_password_in_sbe'),
                'url' => '/player_center2/security',
                'message' => '',
                'title' => '',
                'button_lang' => lang('Change Password')
            ]
        ];
        return $variables;
    }
}