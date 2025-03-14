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
		self::INT_LANG_SPANISH_MX=>'es-MX',
		self::INT_LANG_CHINESE_TRADITIONAL=>'hk',
		self::INT_LANG_FILIPINO => 'ph'
    ];

	/**
	 * get current language
	 *
	 * @param	data
	 * @return 	array
	 */
	function setCurrentLanguage($data) {
		$this->ci->session->set_userdata('afflang', $data);
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
			return 'Filipino';
			break;
		default:
			# code...
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
		$language = $this->ci->session->userdata('afflang');

		if (empty($language)) {
			$type = $this->ci->config->item('default_lang');
			switch ($type) {
				case 'english':
					$language = self::INT_LANG_ENGLISH;
					break;
				case 'chinese':
					$language = self::INT_LANG_CHINESE;
					break;
				case 'indonesian':
					$language = self::INT_LANG_INDONESIAN;
					break;
				case 'vietnamese':
					$language = self::INT_LANG_VIETNAMESE;
					break;
				case 'korean':
					$language = self::INT_LANG_KOREAN;
					break;
				case 'thai':
					$language = self::INT_LANG_THAI;
					break;
				case 'india':
					$language =  self::INT_LANG_INDIA;
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
					$language = self::INT_LANG_JAPANESE;
					break;
				case 'chinese_traditional':
					$language = self::INT_LANG_CHINESE_TRADITIONAL;
					break;
				case 'filipino':
					$language = self::INT_LANG_FILIPINO;
					break;
				default:
					$language = self::INT_LANG_ENGLISH;
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
}

/* End of file language_function.php */
/* Location: ./application/libraries/language_function.php */
