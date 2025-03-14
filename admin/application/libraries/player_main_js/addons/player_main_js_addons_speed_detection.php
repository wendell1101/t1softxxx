<?php
/**
 * player_main_js_addons_speed_detection.php
 *
 */
class Player_main_js_addons_speed_detection extends Player_main_js_addons_abstract {
    public function isEnabled(){
        return TRUE;
    }


    public function variables(){
        // $this->CI->load->model(['cms_model']);

        // $variables['announcement'] = $this->CI->cms_model->getAllNews(NULL, NULL, 'date desc');
        // $variables['announcement_option'] = $this->CI->operatorglobalsettings->getSettingIntValue('announcement_option');
        // $variables['auto_popup_announcements_on_the_first_visit'] = $this->CI->utils->isEnabledFeature('auto_popup_announcements_on_the_first_visit');

        $variables = [
            // 'currentLang' => $this->CI->language_function->getCurrentLanguage(),
            // 'currentLangName' => $this->CI->language_function->getCurrentLanguageName(),
            '_langText' => [
                'speed_detection' => lang('Speed Detection'),
                'lang_millisecond_abr' => lang('lang.millisecond.abr'),
                'lang_timeout' => lang('timeout'),
                'lang_go' => lang('lang.go'),
                'lang_refresh' => lang('lang.refresh'),
                // 'close_button_text' => lang('close_button_text'),
                'lang_close' => lang('lang.close'),
                'directily_test' => lang('Directily Test'),
                'translate_by_dict' => lang('Translate By Dict')
            ],
            'report_uri' => $this->CI->utils->getPlayerCmsUrl('/pub/speed_detect_report/${timestamp}')
        ];
        return $variables;
    }

}