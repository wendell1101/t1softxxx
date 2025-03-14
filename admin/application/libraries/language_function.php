<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * Language function
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
		$this->ci->load->model(array('users'));
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
    const PROMO_SHORT_LANG_PORTUGUESE_BRAZIL = 'pt';
	const PROMO_SHORT_LANG_KAZAKH = 'kk';
	const PROMO_SHORT_LANG_JAPANESE = 'ja';
	const PROMO_SHORT_LANG_CHINESE_TRADITIONAL = 'hk';
	const PROMO_SHORT_LANG_FILIPINO = 'ph';

	const DATE_PICKER_LANG_ENGLISH = 'en-US';
	const DATE_PICKER_LANG_CHINESE = 'zh-CN';
	const DATE_PICKER_LANG_INDONESIAN = 'id';
	const DATE_PICKER_LANG_VIETNAMESE = 'vi';
	const DATE_PICKER_LANG_KOREAN = 'ko';
	const DATE_PICKER_LANG_THAI = 'th';
	const DATE_PICKER_LANG_INDIA = 'in';
	const DATE_PICKER_LANG_PORTUGUESE = 'pt';
	const DATE_PICKER_LANG_SPANISH = 'es';
	const DATE_PICKER_LANG_KAZAKH = 'kk';
	const DATE_PICKER_LANG_PORTUGUESE_BRAZIL = 'pt';
	const DATE_PICKER_LANG_JAPANESE = 'ja';
	const DATE_PICKER_LANG_CHINESE_TRADITIONAL = 'hk';
	const DATE_PICKER_LANG_FILIPINO = 'ph';


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
	const PLAYER_LANG_CHINESE_TRADITIONAL = 'Chinese_Traditional';
	const PLAYER_LANG_JAPANESE = 'Japanese';
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
		self::INT_LANG_JAPANESE=>'ja',
		self::INT_LANG_CHINESE_TRADITIONAL=>'hk',
		self::INT_LANG_FILIPINO=>'ph'
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
		self::INT_LANG_JAPANESE=>'ja-JP',
		self::INT_LANG_SPANISH_MX=>'es-MX',
		self::INT_LANG_CHINESE_TRADITIONAL=>'zh-HK',
		self::INT_LANG_FILIPINO=>'fil-PH',
	];

	/**
	 * lang country code to index
	 *
	 * @param string $langCountry
	 * @return integer null=not found
	 */
	public function isoLangCountryToIndex($langCountry){
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
	 * @param	int
	 * @return 	array
	 */
	function setCurrentLanguage($language) {
		$this->ci->session->set_userdata('login_lan', $language); //$this->ci->player->setCurrentLanguage($data);
		$this->ci->config->set_item('language', $this->getLanguage($language));
	}

	/**
	 * get language code
	 *
	 * @return	rendered Template
	 */
	function getLanguageCode($lang) {
		switch ($lang) {
			default:
				return 'main';
				break;
		}
	}

	/**
	 * get language
	 *
	 * @return	rendered Template
	 */
	function getLanguage($lang) {
		switch ($lang) {
			case 1:
				return 'english';
				break;
			case 2:
				return 'chinese';
				break;
			case 3:
				return 'indonesian';
				break;
			case 4:
				return 'vietnamese';
				break;
			case 5:
				return 'korean';
				break;
			case 6:
				return 'thai';
				break;
			case 7:
				return 'india';
				break;
			case 8:
				return 'portuguese';
				break;
            case 9:
                return 'spanish';
                break;
            case 10:
                return 'kazakh';
                break;
			case 12:
				return 'japanese';
				break;
			case 14:
				return 'chinese_traditional';
				break;
			case 15:
				return 'filipino';
				break;
			default:
				return 'english';
				break;
		}
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
		$language = $this->ci->session->userdata('login_lan');

		if (empty($language)) {
			$default_admin_login_language=$this->getIntLanguage($this->ci->utils->getConfig('default_admin_login_language'));

			$this->ci->session->set_userdata('login_lan', $default_admin_login_language);
			$language = $default_admin_login_language;
		}

		return $language;
	}

	function getCurrentLanguageName() {
		return $this->getLanguage($this->getCurrentLanguage());
	}

	function getCurrentLangForPromo($isKeyword=false,$lang=false) {
		$currLang = $lang ?: $this->getCurrentLanguage();

		switch ($currLang) {
			case self::INT_LANG_CHINESE:
				$language = self::PROMO_SHORT_LANG_CHINESE;
				break;
			case self::INT_LANG_ENGLISH:
				$language = self::PROMO_SHORT_LANG_ENGLISH;
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
				$language = self::PROMO_SHORT_LANG_ENGLISH;
				break;
		}
		return $isKeyword ? $language : $currLang;
	}

	function langStrToInt($langStr) {
		switch (strtolower($langStr)) {
			case 'english':
			case 'en':
				return '1';
				break;
			case 'chinese':
			case 'zh-cn':
				return '2';
				break;
			case 'indonesian':
			case 'idn':
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
		}
	}

	#-------------only used in admin-------------
	function getAllSystemLanguages(){
		return array("languages"=>
			array("key"=>self::INT_LANG_ENGLISH,
				  "word"=>$this->getLanguage(self::INT_LANG_ENGLISH),
				  "short_code"=>self::PROMO_SHORT_LANG_ENGLISH
				),
			array("key"=>self::INT_LANG_CHINESE,
				  "word"=>$this->getLanguage(self::INT_LANG_CHINESE),
				  "short_code"=>self::PROMO_SHORT_LANG_CHINESE
				),
			array("key"=>self::INT_LANG_INDONESIAN,
				  "word"=>$this->getLanguage(self::INT_LANG_INDONESIAN),
				  "short_code"=>self::PROMO_SHORT_LANG_INDONESIAN
				),
			array("key"=>self::INT_LANG_VIETNAMESE,
				  "word"=>$this->getLanguage(self::INT_LANG_VIETNAMESE),
				  "short_code"=>self::PROMO_SHORT_LANG_VIETNAMESE
				),
			array("key"=>self::INT_LANG_KOREAN,
				  "word"=>$this->getLanguage(self::INT_LANG_KOREAN),
				  "short_code"=>self::PROMO_SHORT_LANG_KOREAN
				),
			array("key"=>self::INT_LANG_THAI,
				  "word"=>$this->getLanguage(self::INT_LANG_THAI),
				  "short_code"=>self::PROMO_SHORT_LANG_THAI
				),
			array("key"=>self::INT_LANG_INDIA,
				  "word"=>$this->getLanguage(self::INT_LANG_INDIA),
				  "short_code"=>self::PROMO_SHORT_LANG_INDIA
				),
			array("key"=>self::INT_LANG_PORTUGUESE,
				  "word"=>$this->getLanguage(self::INT_LANG_PORTUGUESE),
				  "short_code"=>self::PROMO_SHORT_LANG_PORTUGUESE
				),
            array("key"=>self::INT_LANG_SPANISH,
                "word"=>$this->getLanguage(self::INT_LANG_SPANISH),
                "short_code"=>self::PROMO_SHORT_LANG_SPANISH
                ),
            array("key"=>self::INT_LANG_KAZAKH,
                "word"=>$this->getLanguage(self::INT_LANG_KAZAKH),
                "short_code"=>self::PROMO_SHORT_LANG_KAZAKH
            ),
            array("key"=>self::INT_LANG_JAPANESE,
                "word"=>$this->getLanguage(self::INT_LANG_JAPANESE),
                "short_code"=>self::PROMO_SHORT_LANG_JAPANESE
            ),
            array("key"=>self::INT_LANG_CHINESE_TRADITIONAL,
                "word"=>$this->getLanguage(self::INT_LANG_CHINESE_TRADITIONAL),
                "short_code"=>self::PROMO_SHORT_LANG_CHINESE_TRADITIONAL
            ),
            array("key"=>self::INT_LANG_FILIPINO,
                "word"=>$this->getLanguage(self::INT_LANG_FILIPINO),
                "short_code"=>self::PROMO_SHORT_LANG_FILIPINO
            ),

		);
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

	function convertToDatePickerLang($lang) {

		switch ($lang) {
			case self::INT_LANG_CHINESE:
				$datePickerLang = self::DATE_PICKER_LANG_CHINESE;
				break;
			case self::INT_LANG_ENGLISH:
				$datePickerLang = self::DATE_PICKER_LANG_ENGLISH;
				break;
			case self::INT_LANG_INDONESIAN:
				$datePickerLang = self::DATE_PICKER_LANG_INDONESIAN;
				break;
			case self::INT_LANG_VIETNAMESE:
				$datePickerLang = self::DATE_PICKER_LANG_VIETNAMESE;
				break;
			case self::INT_LANG_KOREAN:
				$datePickerLang = self::DATE_PICKER_LANG_KOREAN;
				break;
			case self::INT_LANG_THAI:
				$datePickerLang = self::DATE_PICKER_LANG_THAI;
				break;
			case self::INT_LANG_INDIA:
				$datePickerLang = self::DATE_PICKER_LANG_INDIA;
				break;
			case self::INT_LANG_PORTUGUESE:
				$datePickerLang = self::DATE_PICKER_LANG_PORTUGUESE;
				break;
            case self::INT_LANG_SPANISH:
                $datePickerLang = self::DATE_PICKER_LANG_SPANISH;
                break;
            case self::INT_LANG_KAZAKH:
                $datePickerLang = self::DATE_PICKER_LANG_KAZAKH;
                break;
			case self::INT_LANG_JAPANESE:
				$datePickerLang = self::DATE_PICKER_LANG_JAPANESE;
				break;
			case self::INT_LANG_CHINESE_TRADITIONAL:
				$datePickerLang = self::DATE_PICKER_LANG_CHINESE_TRADITIONAL;
				break;
			case self::INT_LANG_FILIPINO:
				$datePickerLang = self::DATE_PICKER_LANG_FILIPINO;
				break;
			default:
				$datePickerLang = self::DATE_PICKER_LANG_ENGLISH;
				break;
		}

		return $datePickerLang;
	}

	function getAllSystemLangLocalWord(){
		return array("languages"=>
			array("key"=>self::INT_LANG_ENGLISH,
				  "word"=>$this->langToLocalWord(self::INT_LANG_ENGLISH),
				  "short_code"=>self::PROMO_SHORT_LANG_ENGLISH
				),
			array("key"=>self::INT_LANG_CHINESE,
				  "word"=>$this->langToLocalWord(self::INT_LANG_CHINESE),
				  "short_code"=>self::PROMO_SHORT_LANG_CHINESE
				),
			array("key"=>self::INT_LANG_INDONESIAN,
				  "word"=>$this->langToLocalWord(self::INT_LANG_INDONESIAN),
				  "short_code"=>self::PROMO_SHORT_LANG_INDONESIAN
				),
			array("key"=>self::INT_LANG_VIETNAMESE,
				  "word"=>$this->langToLocalWord(self::INT_LANG_VIETNAMESE),
				  "short_code"=>self::PROMO_SHORT_LANG_VIETNAMESE
				),
			array("key"=>self::INT_LANG_KOREAN,
				  "word"=>$this->langToLocalWord(self::INT_LANG_KOREAN),
				  "short_code"=>self::PROMO_SHORT_LANG_KOREAN
				),
			array("key"=>self::INT_LANG_THAI,
				  "word"=>$this->langToLocalWord(self::INT_LANG_THAI),
				  "short_code"=>self::PROMO_SHORT_LANG_THAI
				),
			array("key"=>self::INT_LANG_INDIA,
				  "word"=>$this->langToLocalWord(self::INT_LANG_INDIA),
				  "short_code"=>self::PROMO_SHORT_LANG_INDIA
				),
			array("key"=>self::INT_LANG_PORTUGUESE,
				  "word"=>$this->langToLocalWord(self::INT_LANG_PORTUGUESE),
				  "short_code"=>self::PROMO_SHORT_LANG_PORTUGUESE
				),
            array("key"=>self::INT_LANG_SPANISH,
                "word"=>$this->langToLocalWord(self::INT_LANG_SPANISH),
                "short_code"=>self::PROMO_SHORT_LANG_SPANISH
                ),
            array("key"=>self::INT_LANG_KAZAKH,
                "word"=>$this->langToLocalWord(self::INT_LANG_KAZAKH),
                "short_code"=>self::PROMO_SHORT_LANG_KAZAKH
                ),
			array("key"=>self::INT_LANG_JAPANESE,
				"word"=>$this->langToLocalWord(self::INT_LANG_JAPANESE),
				"short_code"=>self::PROMO_SHORT_LANG_JAPANESE
				),
			array("key"=>self::INT_LANG_CHINESE_TRADITIONAL,
				"word"=>$this->langToLocalWord(self::INT_LANG_CHINESE_TRADITIONAL),
				"short_code"=>self::PROMO_SHORT_LANG_CHINESE_TRADITIONAL
				),
			array("key"=>self::INT_LANG_FILIPINO,
				"word"=>$this->langToLocalWord(self::INT_LANG_FILIPINO),
				"short_code"=>self::PROMO_SHORT_LANG_FILIPINO
				),
		);
	}

	function langToLocalWord($langStr) {
		switch (strtolower($langStr)) {
			case 'english':
			case 'en':
			case '1':
				return 'English';
				break;
			case 'chinese':
			case 'zh-cn':
			case '2':
				return '中文';
				break;
			case 'indonesian':
			case 'idn':
			case '3':
				return 'Indonesian';
				break;
			case 'vietnamese':
			case 'vn':
			case '4':
				return 'Vietnamese';
				break;
			case 'korean':
			case 'kr':
			case '5':
				return 'Korean';
				break;
			case 'thai':
			case 'th':
			case '6':
				return 'Thai';
				break;
			case 'india':
			case 'in':
			case '7':
				return 'India';
				break;
			case 'portuguese':
			case 'pt':
			case '8':
				return 'Portuguese';
				break;
            case 'spanish':
            case 'es':
            case '9':
                return 'Spanish';
                break;
            case 'kazakh':
            case 'kk':
            case '10':
                return 'Kazakh';
                break;
			case 'japanese':
			case 'ja':
			case '12':
				return 'Japanese';
				break;
			case 'chinese_traditional':
			case 'hk':
			case '14':
				return 'chinese_traditional';
				break;
			case 'filipino':
			case 'ph':
			case '15':
				return 'filipino';
				break;
			default:
				return 'English';
				break;
		}
	}
}

/* End of file language_function.php */
/* Location: ./application/libraries/language_function.php */
