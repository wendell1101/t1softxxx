<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * Language function for player
 *
 * Language function library
 *
 * @package		Language function
 * @author		ASRII
 * @version		1.0.0
 */

class Language_function {
	private $error = array();

	function __construct() {
		$this->ci = &get_instance();
		$this->ci->load->library(array('session'));
		$this->ci->load->model(array('player'));
	}

	const INT_LANG_ENGLISH = '1';
	const INT_LANG_CHINESE = '2';
	const INT_LANG_INDONESIAN = '3';
	const INT_LANG_VIETNAMESE = '4';
	const INT_LANG_KOREAN = '5';
	const INT_LANG_THAI = '6';
	const INT_LANG_INDIA = '7';
	const INT_LANG_PORTUGUESE = '8';
	const INT_LANG_SPANISH = '9';
	const INT_LANG_KAZAKH = '10';
	const INT_LANG_PORTUGUESE_BRAZIL = '11';
	const INT_LANG_JAPANESE = '12';
	const INT_LANG_SPANISH_MX = '13';
	const INT_LANG_CHINESE_TRADITIONAL = '14';
	const INT_LANG_FILIPINO = '15';
	const PROMO_SHORT_LANG_ENGLISH = 'en';
	const PROMO_SHORT_LANG_CHINESE = 'ch';
	const PROMO_SHORT_LANG_INDONESIAN = 'id';
	const PROMO_SHORT_LANG_VIETNAMESE = 'vn';
	const PROMO_SHORT_LANG_KOREAN = 'kr';
	const PROMO_SHORT_LANG_THAI = 'th';
	const PROMO_SHORT_LANG_INDIA = 'in';
	const PROMO_SHORT_LANG_PORTUGUESE = 'pt';
	const PROMO_SHORT_LANG_SPANISH = 'es';
	const PROMO_SHORT_LANG_KAZAKH = 'kk';
	const PROMO_SHORT_LANG_PORTUGUESE_BRAZIL = 'pt';
	const PROMO_SHORT_LANG_JAPANESE = 'ja';
	const PROMO_SHORT_LANG_CHINESE_TRADITIONAL = 'hk';
	const PROMO_SHORT_LANG_FILIPINO = 'ph';

	const PLAYER_LANG_ENGLISH = 'English';
	const PLAYER_LANG_CHINESE = 'Chinese';
	const PLAYER_LANG_INDONESIAN = 'Indonesian';
	const PLAYER_LANG_VIETNAMESE = 'Vietnamese';
	const PLAYER_LANG_KOREAN = 'Korean';
	const PLAYER_LANG_THAI = 'Thai';
	const PLAYER_LANG_INDIA = 'India';
	const PLAYER_LANG_PORTUGUESE = 'Portuguese';
	const PLAYER_LANG_SPANISH = 'Spanish';
	const PLAYER_LANG_KAZAKH = 'Kazakh';
	const PLAYER_LANG_PORTUGUESE_BRAZIL = 'Portuguese';
	const PLAYER_LANG_JAPANESE = 'Japanese';
	const PLAYER_LANG_SPANISH_MX = 'Mexican';
	const PLAYER_LANG_CHINESE_TRADITIONAL = 'Chinese_Traditional';
	const PLAYER_LANG_FILIPINO = 'Filipino';

    const ISO2_LANG=[
        self::INT_LANG_ENGLISH=>'en',
        self::INT_LANG_CHINESE=>'cn',
        self::INT_LANG_INDONESIAN=>'id',
        self::INT_LANG_VIETNAMESE=>'vt',
        self::INT_LANG_KOREAN=>'kr',
        self::INT_LANG_THAI=>'th',
        self::INT_LANG_INDIA=>'in',
        self::INT_LANG_PORTUGUESE=>'pt',
        self::INT_LANG_SPANISH=>'es',
        self::INT_LANG_KAZAKH=>'kk',
		self::INT_LANG_PORTUGUESE_BRAZIL=>'pt',
		self::INT_LANG_SPANISH_MX=>'es',
		self::INT_LANG_JAPANESE=>'ja',
		self::INT_LANG_CHINESE_TRADITIONAL=>'hk',
		self::INT_LANG_FILIPINO=>'ph',
    ];

	const ISO_LANG_COUNTRY=[
		self::INT_LANG_ENGLISH=>'en-US',
		self::INT_LANG_CHINESE=>'zh-CN',
		self::INT_LANG_INDONESIAN=>'id-ID',
		self::INT_LANG_VIETNAMESE=>'vi-VN',
		self::INT_LANG_KOREAN=>'ko-KR',
		self::INT_LANG_THAI=>'th-TH',
		self::INT_LANG_INDIA=>'hi-IN',
		self::INT_LANG_PORTUGUESE=>'pt-PT',
		self::INT_LANG_SPANISH=>'es-ES',
		self::INT_LANG_KAZAKH=>'kk-KZ',
		self::INT_LANG_PORTUGUESE_BRAZIL=>'pt-BR',
		self::INT_LANG_SPANISH_MX=>'es-MX',
		self::INT_LANG_JAPANESE=>'ja-JP',
		self::INT_LANG_CHINESE_TRADITIONAL=>'zh-HK',
		self::INT_LANG_FILIPINO=>'fil-PH',
	];

	const ISO_PLAYER_LANG_COUNTRY=[
		self::PLAYER_LANG_ENGLISH=>'en-US',
		self::PLAYER_LANG_CHINESE=>'zh-CN',
		self::PLAYER_LANG_INDONESIAN=>'id-ID',
		self::PLAYER_LANG_VIETNAMESE=>'vi-VN',
		self::PLAYER_LANG_KOREAN=>'ko-KR',
		self::PLAYER_LANG_THAI=>'th-TH',
		self::PLAYER_LANG_INDIA=>'hi-IN',
		self::PLAYER_LANG_PORTUGUESE=>'pt-PT',
		self::PLAYER_LANG_SPANISH=>'es-ES',
		self::PLAYER_LANG_KAZAKH=>'kk-KZ',
		self::PLAYER_LANG_PORTUGUESE_BRAZIL=>'pt-BR',
		self::PLAYER_LANG_SPANISH_MX=>'es-MX',
		self::PLAYER_LANG_JAPANESE=>'ja-JP',
		self::PLAYER_LANG_CHINESE_TRADITIONAL=>'zh-HK',
		self::PLAYER_LANG_FILIPINO=>'fil-PH',
	];

	/**
	 * lang country code to index
	 *
	 * @param string $langCountry
	 * @return integer null=not found
	 */
	public function isoLangCountryToIndex($langCountry){
		switch ($langCountry) {
			case 'pt':
			case 'pt-BR':
				$langCountry = 'pt-PT';
				break;
			case 'es':
			case 'es-MX':
				$langCountry = 'es-ES';
				break;
			case 'kk':
				$langCountry = 'kk-KZ';
				break;
			default:
				break;
		}

		foreach(self::ISO_LANG_COUNTRY as $idx=>$code){
			if($code==$langCountry){
				return $idx;
			}
		}

		return null;
	}

	/**
	 * index to iso lang
	 *
	 * @param string $langCountry
	 * @return integer null=not found
	 */
	public function indexToisoLangCountry($index){
		foreach(self::ISO_LANG_COUNTRY as $idx=>$code){
			if($idx==$index){
				return $code;
			}
		}

		return null;
	}

	/**
	 * get current language
	 *
	 * @param	data
	 * @return 	array
	 */
	function setCurrentLanguage($language) {
		$this->ci->session->set_userdata('lang', $language); //$this->ci->player->setCurrentLanguage($data);
		$this->ci->config->set_item('language', strtolower($this->getLanguage($language)));
	}

	/**
	 * get language code
	 *
	 * @return	rendered Template
	 */
	function getLanguageCode($lang) {
		return 'main';
	}

	/**
	 * get language
	 *
	 * @return	rendered Template
	 */
	function getLanguage($lang) {
        return strtolower(self::getLanguageName($lang));
	}

	/**
	 * get language
	 *
	 * @return	rendered Template
	 */
	function getIntLanguage($lang) {
		switch (strtolower($lang)) {
			case 'english':
				return self::INT_LANG_ENGLISH;
				break;
			case 'chinese':
				return self::INT_LANG_CHINESE;
				break;
			case 'indonesian':
				return self::INT_LANG_INDONESIAN;
				break;
			case 'vietnamese':
				return self::INT_LANG_VIETNAMESE;
				break;
			case 'korean':
				return self::INT_LANG_KOREAN;
				break;
			case 'thai':
				return self::INT_LANG_THAI;
				break;
			case 'india':
				return self::INT_LANG_INDIA;
				break;
			case 'portuguese':
				return self::INT_LANG_PORTUGUESE;
				break;
            case 'spanish':
                return self::INT_LANG_SPANISH;
                break;
            case 'kazakh':
                return self::INT_LANG_KAZAKH;
                break;
			case 'japanese':
				return self::INT_LANG_JAPANESE;
				break;
			case 'chinese_traditional':
				return self::INT_LANG_CHINESE_TRADITIONAL;
				break;
			case 'filipino':
				return self::INT_LANG_FILIPINO;
				break;
			default:
				return self::INT_LANG_ENGLISH;
				break;
		}
	}

	/**
	 * get current language
	 *
	 * @param	int
	 * @return 	array
	 */
	function getCurrentLanguage() {
		$htmlLang=$this->ci->input->get('lang');
		// $htmlLang=$this->convertHtmlLang($htmlLang);

		if(!empty($htmlLang)){
			$language=$this->langStrToInt($this->convertHtmlLang($htmlLang));
		}else{
			$language = $this->ci->session->userdata('lang');
			$isForceToDefaultLanguage = $this->ci->utils->isForcePlayerCenterToDefaultLanguage();

			if (!$language || !empty($isForceToDefaultLanguage)) {
				$language = $this->ci->utils->getPlayerCenterLanguage();
			}

			if(empty($isForceToDefaultLanguage) && $this->ci->utils->isRetainCurrentLanguage()){
				$cookie_name = $this->ci->config->item('cookie_name_lang');
				if(!empty($cookie_name)){
					if(isset($_COOKIE[$cookie_name])){
						// $this->ci->utils->debug_log('cookie_name_lang', $_COOKIE[$cookie_name]);
						$language = $this->langStrToInt($_COOKIE[$cookie_name]);
					}
				}
			}
		}

		if (empty($language) || !self::isSupportLanguage($language)) {
			//default lang from config
			$default_lang = $this->ci->config->item('default_player_language');

			switch (ucfirst($default_lang)) {
				case self::PLAYER_LANG_ENGLISH:
					$this->ci->session->set_userdata('lang', '1');
					$language = '1';
					break;
				case self::PLAYER_LANG_CHINESE:
					$this->ci->session->set_userdata('lang', '2');
					$language = '2';
					break;
				case self::PLAYER_LANG_INDONESIAN:
					$this->ci->session->set_userdata('lang', '3');
					$language = '3';
					break;
				case self::PLAYER_LANG_VIETNAMESE:
					$this->ci->session->set_userdata('lang', '4');
					$language = '4';
					break;
				case self::PLAYER_LANG_KOREAN:
					$this->ci->session->set_userdata('lang', '5');
					$language = '5';
					break;
				case self::PLAYER_LANG_THAI:
					$this->ci->session->set_userdata('lang', '6');
					$language = '6';
					break;
				case self::PLAYER_LANG_INDIA:
					$this->ci->session->set_userdata('lang', '7');
					$language = '7';
					break;
				case self::PLAYER_LANG_PORTUGUESE:
					$this->ci->session->set_userdata('lang', '8');
					$language = '8';
					break;
                case self::PLAYER_LANG_SPANISH:
                    $this->ci->session->set_userdata('lang', '9');
                    $language = '9';
                    break;
                case self::PLAYER_LANG_KAZAKH:
                    $this->ci->session->set_userdata('lang', '10');
                    $language = '10';
                    break;
				case self::PLAYER_LANG_JAPANESE:
					$this->ci->session->set_userdata('lang', '12');
					$language = '12';
					break;
				case self::PLAYER_LANG_CHINESE_TRADITIONAL:
					$this->ci->session->set_userdata('lang', '14');
					$language = '14';
					break;
				case self::PLAYER_LANG_FILIPINO:
					$this->ci->session->set_userdata('lang', '15');
					$language = '15';
					break;
				default:
					$this->ci->session->set_userdata('lang', '1');
					$language = '1';
					break;
			}

		}

		return $language;
	}

	function getCurrentLanguageName() {
		return $this->getLanguage($this->getCurrentLanguage());
	}

	function getCurrentLangForPromo() {
		$currLang = $this->getCurrentLanguage();
		switch ($currLang) {
			case self::INT_LANG_ENGLISH:
				$language = self::PROMO_SHORT_LANG_ENGLISH;
				break;
			case self::INT_LANG_CHINESE:
				$language = self::PROMO_SHORT_LANG_CHINESE;
				break;
			case self::INT_LANG_INDONESIAN:
				$language = self::PROMO_SHORT_LANG_INDONESIAN;
				break;
			case self::INT_LANG_VIETNAMESE:
				$language = self::PROMO_SHORT_LANG_VIETNAMESE;
				break;
			case self::INT_LANG_KOREAN:
				$language = self::PROMO_SHORT_LANG_KOREAN;
				break;
			case self::INT_LANG_THAI:
				$language = self::PROMO_SHORT_LANG_THAI;
				break;
			case self::INT_LANG_INDIA:
				$language = self::PROMO_SHORT_LANG_INDIA;
				break;
			case self::INT_LANG_PORTUGUESE:
				$language = self::PROMO_SHORT_LANG_PORTUGUESE;
				break;
            case self::INT_LANG_SPANISH:
                $language = self::PROMO_SHORT_LANG_SPANISH;
                break;
            case self::INT_LANG_KAZAKH:
                $language = self::PROMO_SHORT_LANG_KAZAKH;
                break;
			case self::INT_LANG_JAPANESE:
				$language = self::PROMO_SHORT_LANG_JAPANESE;
				break;
			case self::INT_LANG_CHINESE_TRADITIONAL:
				$language = self::PROMO_SHORT_LANG_CHINESE_TRADITIONAL;
				break;
			case self::INT_LANG_FILIPINO:
				$language = self::PROMO_SHORT_LANG_FILIPINO;
				break;
			default:
				$language = self::PROMO_SHORT_LANG_CHINESE;
				break;
		}
		return $language;
	}

	function langStrToInt($langStr) {
		switch (strtolower($langStr)) {
			case 'english':
			case 'en':
				return '1';
				break;
			case 'chinese':
            case 'ch'://  for PROMO_SHORT_LANG_CHINESE
			case 'zh-cn':
				return '2';
				break;
			case 'indonesian':
			case 'idn':
			case 'in':
				return '3';
				break;
			case 'vietnamese':
			case 'vn':
				return '4';
				break;
			case 'korean':
			case 'kr':
				return '5';
				break;
			case 'thai':
			case 'th':
				return '6';
				break;
			case 'india':
			// case 'in':
			case 'inr':
				return '7';
				break;
			case 'portuguese':
			case 'pt':
				return '8';
				break;
            case 'spanish':
            case 'es':
                return '9';
                break;
            case 'kazakh':
            case 'kk':
                return '10';
                break;
			case 'japanese':
			case 'ja':
				return '12';
				break;
			case 'chinese_traditional':
			case 'hk':
				return '14';
				break;
			case 'filipino':
			case 'ph':
				return '15';
				break;
			default:
				# code...
				break;
		}
	}


    /**
	 * get language id from short_code
	 *
	 * @return	rendered Template
	 */
	function getPromoLanguageIdFromShortCode($lang) {
		switch ($lang) {
			case self::PROMO_SHORT_LANG_ENGLISH:
				return self::INT_LANG_ENGLISH;
				break;
			case self::PROMO_SHORT_LANG_CHINESE:
				return self::INT_LANG_CHINESE;
				break;
			case self::PROMO_SHORT_LANG_INDONESIAN:
				return self::INT_LANG_INDONESIAN;
				break;
			case self::PROMO_SHORT_LANG_VIETNAMESE:
				return self::INT_LANG_VIETNAMESE;
				break;
			case self::PROMO_SHORT_LANG_KOREAN:
				return self::INT_LANG_KOREAN;
				break;
			case self::PROMO_SHORT_LANG_THAI:
				return self::INT_LANG_THAI;
				break;
			case self::PROMO_SHORT_LANG_INDIA:
				return self::INT_LANG_INDIA;
				break;
			case self::PROMO_SHORT_LANG_PORTUGUESE:
				return self::INT_LANG_PORTUGUESE;
				break;
            case self::PROMO_SHORT_LANG_SPANISH:
                return self::INT_LANG_SPANISH;
                break;
            case self::PROMO_SHORT_LANG_KAZAKH:
                return self::INT_LANG_KAZAKH;
                break;
			case self::PROMO_SHORT_LANG_JAPANESE:
				return self::INT_LANG_JAPANESE;
				break;
			case self::PROMO_SHORT_LANG_CHINESE_TRADITIONAL:
				return self::INT_LANG_CHINESE_TRADITIONAL;
				break;
			case self::PROMO_SHORT_LANG_FILIPINO:
				return self::INT_LANG_FILIPINO;
				break;
			default:
				return self::INT_LANG_ENGLISH;
				break;
		}
	}

	#-------------only used in player-------------
	function convertHtmlLang($htmlLang){
		switch (strtolower($htmlLang)) {
			case 'en':
				$lang = 'english';
				break;
			case 'zh-cn':
				$lang = 'chinese';
				break;
			case 'idn':
				$lang = 'indonesian';
				break;
			case 'vn':
				$lang = 'vietnamese';
				break;
			case 'kr':
				$lang = 'korean';
				break;
			case 'th':
				$lang = 'thai';
				break;
			case 'in':
				$lang = 'india';
				break;
			case 'pt':
				$lang = 'portuguese';
				break;
            case 'es':
                $lang = 'spanish';
                break;
            case 'kk':
                $lang = 'kazakh';
                break;
			case 'ja':
				$lang = 'japanese';
				break;
			case 'hk':
				$lang = 'chinese_traditional';
				break;
			case 'ph':
				$lang = 'filipino';
				break;
			default:
				$lang = null;
				break;
		}
		return $lang;
	}

    public function convertGameLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case '1':
                $lang = 'en-us'; // english
                break;
            case '2':
                $lang = 'zh-cn'; // chinese
                break;
            case '3':
                $lang = 'id-id'; // indonesia
                break;
            case '4':
                $lang = 'vi-vn'; // vietnamese
                break;
            case '5':
                $lang = 'ko-kr'; // korean
                break;
            case '6':
                $lang = 'th-th'; // Thai
                break;
            case '7':
            	$lang = 'hi-in'; // India (Hindi)
            	break;
            case '8':
            	$lang = 'pt-pt'; // Portuguese
            	break;
            case '9':
                $lang = 'es-es'; // Spanish
                break;
            case '10':
                $lang = 'kk-KZ'; // Kazakh
                break;
			case '12':
				$lang = 'ja-JP'; // Japanese
				break;
			case '14':
				$lang = 'zh-HK'; // Chinese Traditional
				break;
			case '15':
				$lang = 'fil-PH'; // Filipino
				break;
            default:
                $lang = 'en-us'; // default as english
                break;
        }
        return $lang;
    }

    static function PlayerSupportLanguages(){
        $CI = &get_instance();
        $enabled_language = $CI->operatorglobalsettings->getSettingJson('player_center_enabled_language');

        $default_languages = [
            self::INT_LANG_ENGLISH => lang(self::PLAYER_LANG_ENGLISH),
            self::INT_LANG_CHINESE => lang(self::PLAYER_LANG_CHINESE),
            self::INT_LANG_INDONESIAN => lang(self::PLAYER_LANG_INDONESIAN),
            self::INT_LANG_VIETNAMESE => lang(self::PLAYER_LANG_VIETNAMESE),
            self::INT_LANG_KOREAN => lang(self::PLAYER_LANG_KOREAN),
            self::INT_LANG_THAI => lang(self::PLAYER_LANG_THAI),
            self::INT_LANG_INDIA => lang(self::PLAYER_LANG_INDIA),
            self::INT_LANG_PORTUGUESE => lang(self::PLAYER_LANG_PORTUGUESE),
            self::INT_LANG_SPANISH => lang(self::PLAYER_LANG_SPANISH),
            self::INT_LANG_KAZAKH => lang(self::PLAYER_LANG_KAZAKH),
			self::INT_LANG_PORTUGUESE_BRAZIL => lang(self::PLAYER_LANG_PORTUGUESE_BRAZIL),
			self::INT_LANG_SPANISH_MX => lang(self::PLAYER_LANG_SPANISH_MX),
			self::INT_LANG_JAPANESE => lang(self::PLAYER_LANG_JAPANESE),
			self::INT_LANG_CHINESE_TRADITIONAL => lang(self::PLAYER_LANG_CHINESE_TRADITIONAL),
			self::INT_LANG_FILIPINO => lang(self::PLAYER_LANG_FILIPINO)
        ];
        $support_languages = [];
        if(!empty($enabled_language)){
            foreach($default_languages as $lang_code => $lang_value){
                if(!in_array($lang_code, $enabled_language)){
                    continue;
                }

                $support_languages[$lang_code] = $lang_value;
            }
        }

        return (empty($support_languages)) ? $default_languages : $support_languages;
    }

    static function isSupportLanguage($langCode){
        $support_langs = [
            self::INT_LANG_ENGLISH,
            self::INT_LANG_CHINESE,
            self::INT_LANG_INDONESIAN,
            self::INT_LANG_VIETNAMESE,
            self::INT_LANG_KOREAN,
            self::INT_LANG_THAI,
            self::INT_LANG_INDIA,
            self::INT_LANG_PORTUGUESE,
            self::INT_LANG_SPANISH,
            self::INT_LANG_KAZAKH,
			self::INT_LANG_JAPANESE,
			self::INT_LANG_CHINESE_TRADITIONAL,
			self::INT_LANG_FILIPINO
        ];

        return (in_array((int)$langCode, $support_langs));
    }

    static function PlayerSupportLanguageNames(){
        $support_languages = self::PlayerSupportLanguages();
        if(empty($support_languages)) return [];

        $support_language_names = [];
        foreach($support_languages as $lang_code => $lang_value){
            $support_language_names[self::getLanguageName($lang_code)] = $lang_value;
        }

        return $support_language_names;
    }

    static public function getLanguageName($lang_code){
		switch ((int)$lang_code) {
			case self::INT_LANG_ENGLISH:
				return self::PLAYER_LANG_ENGLISH;
				break;
			case self::INT_LANG_INDONESIAN:
				return self::PLAYER_LANG_INDONESIAN;
				break;
			case self::INT_LANG_VIETNAMESE:
				return self::PLAYER_LANG_VIETNAMESE;
				break;
			case self::INT_LANG_KOREAN:
				return self::PLAYER_LANG_KOREAN;
				break;
			case self::INT_LANG_THAI:
				return self::PLAYER_LANG_THAI;
				break;
			case self::INT_LANG_INDIA:
				return self::PLAYER_LANG_INDIA;
				break;
			case self::INT_LANG_PORTUGUESE:
				return self::PLAYER_LANG_PORTUGUESE;
				break;
            case self::INT_LANG_SPANISH:
                return self::PLAYER_LANG_SPANISH;
                break;
            case self::INT_LANG_KAZAKH:
                return self::PLAYER_LANG_KAZAKH;
                break;
			case self::INT_LANG_JAPANESE:
				return self::PLAYER_LANG_JAPANESE;
				break;
			case self::INT_LANG_CHINESE_TRADITIONAL:
				return self::PLAYER_LANG_CHINESE_TRADITIONAL;
				break;
			case self::INT_LANG_FILIPINO:
				return self::PLAYER_LANG_FILIPINO;
				break;
			case self::INT_LANG_CHINESE:
			default:
				return self::PLAYER_LANG_CHINESE;
				break;
		}
    }

}

/* End of file language_function.php */
/* Location: ./application/libraries/language_function.php */