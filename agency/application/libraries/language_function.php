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

	const DATE_PICKER_LANG_ENGLISH = 'en-US';
    const DATE_PICKER_LANG_CHINESE = 'zh-CN';
    const DATE_PICKER_LANG_INDONESIAN = 'id';
    const DATE_PICKER_LANG_VIETNAMESE = 'vi';
    const DATE_PICKER_LANG_KOREAN = 'ko';
    const DATE_PICKER_LANG_THAI = 'th';
    const DATE_PICKER_LANG_INDIA = 'in';

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
		self::INT_LANG_PORTUGUESE_BRAZIL=>'pt-BR',
		self::INT_LANG_SPANISH_MX=>'es-MX',
		self::INT_LANG_JAPANESE=>'ja',
		self::INT_LANG_CHINESE_TRADITIONAL=>'hk',
		self::INT_LANG_FILIPINO=>'ph',
	];

	function __construct() {
		$this->ci = &get_instance();
		$this->ci->load->library(array('session'));
		// $this->ci->load->model(array());
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
		case self::INT_LANG_PORTUGUESE:
			return 'portuguese';
			break;
        case self::INT_LANG_SPANISH:
            return 'spanish';
            break;
        case self::INT_LANG_KAZAKH:
            return 'kazakh';
            break;
		case self::INT_LANG_JAPANESE:
			return 'japanese';
			break;
		case self::INT_LANG_CHINESE_TRADITIONAL:
			return 'chinese_traditional';
			break;
		case self::INT_LANG_FILIPINO:
			return 'filipino';
			break;
		default:
			# code...
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
		$language = $this->ci->session->userdata('agency_lang');

		if (empty($language)) {
			$type = $this->ci->config->item('default_agency_login_language');

			switch ($type) {
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
					$language =  self::INT_LANG_PORTUGUESE;
					break;
                case 'spanish':
                    $language =  self::INT_LANG_SPANISH;
                    break;
                case 'kazakh':
                    $language =  self::INT_LANG_KAZAKH;
                    break;
				case 'japanese':
					$language =  self::INT_LANG_JAPANESE;
					break;
				case 'chinese_traditional':
					$language =  self::INT_LANG_CHINESE_TRADITIONAL;
					break;
				case 'filipino':
					$language =  self::INT_LANG_FILIPINO;
					break;
				default:
					return self::INT_LANG_ENGLISH;
					break;
			}
		}

		return $language;
	}

	/**
	 * get current language
	 *
	 * @param	data
	 * @return 	array
	 */
	function setCurrentLanguage($data) {
		$this->ci->session->set_userdata('agency_lang', $data);
	}

	function getCurrentLanguageName() {
		return $this->getLanguage($this->getCurrentLanguage());
	}
}

/* End of file language_function.php */
/* Location: ./application/libraries/language_function.php */
