<?php
require_once 'PlayerCenterBaseController.php';

require_once dirname(dirname(__FILE__)) . '/modules/promo_module.php';
/**
 * Lists promotion
 *
 * @property Promoru $promorules
 * @property Player_model $player_model
 */
class Promotion extends PlayerCenterBaseController {

    /// How to override trait function and call it from the overridden function?
    // Ref. to https://stackoverflow.com/a/11939306
    use promo_module {
        addtopromo as protected traitAddtopromo;
    }

    public function __construct(){
        parent::__construct();
        $this->load->helper('url');
        // $this->preloadPromotionVars();
    }

    public function _remap($method){
        global $CI, $URI;

        if(!method_exists($CI, $method)){
            return call_user_func_array(array(&$this, 'index'), array('promocode'=> $method));
        }

		return call_user_func_array(array(&$CI, $method), array_slice($URI->rsegments, 2));
    }

    private function preloadPromotionVars() {
        $playerId = $this->load->get_var('playerId');

        $allpromo = $this->_loadVars4allpromo($playerId);

        $subwallet=null;
        $success=$this->wallet_model->lockAndTransForPlayerBalance($playerId, function () use (
            $playerId, &$subwallet) {

            $subwallet = $this->wallet_model->getAllPlayerAccountByPlayerId($playerId);
            return !empty($subwallet);
        });

        $promoCategoryList = $this->utils->getAllPromoType();

        if($this->utils->isEnabledFeature('show_promotion_view_all')){
            $view_all_category_entry = [
                'id' => 0,
                'name' => lang('View All'),
                'displayPromo' => 3
            ];
            array_unshift($promoCategoryList, $view_all_category_entry);

            @reset($promoCategoryList);
            $default_show_category_id = 0;
        }else{
            @reset($promoCategoryList);
            $default_show_category_id = (empty($promoCategoryList)) ? 0 : current($promoCategoryList)['id'];
        }


        $this->load->vars('mypromo', $this->utils->getPlayerPromo("mypromo", $playerId));
        $this->load->vars('promo_list', (isset($allpromo['promo_list'])) ? $allpromo['promo_list'] : []);
        $this->load->vars('promoCategoryList', $promoCategoryList);
        $this->load->vars(['default_show_category_id' => $default_show_category_id]);
        $this->load->vars('currency', $this->utils->getCurrentCurrency());
    }

    private function _loadVars4allpromo($playerId){


        if($this->utils->getConfig('enabled_get_allpromo_with_category_via_ajax')){
            $allpromo['promo_list'] = []; // get data will via ajax
        }else{
            // genrated from server
            $allpromo = $this->utils->getPlayerPromo("allpromo", $playerId);
        }


        //unset the promo that not equivalent to promo rules language
        if(isset($allpromo['promo_list']) && is_array($allpromo['promo_list'])) {
            foreach ($allpromo['promo_list'] as $allpromokey => $allpromovalue) {
                if(isset($allpromovalue['promorule']['language'])){
                    if($allpromovalue['promorule']['language']){
                        if($allpromovalue['promorule']['language'] != $this->language_function->getCurrentLanguage()){
                            unset($allpromo['promo_list'][$allpromokey]);
                        }
                    }
                }
            }
        }
        $this->load->vars('allpromo', $allpromo);
        return $allpromo;
    } // EOF _loadVars4allpromo

    public function index($promoCode = null) {
        $this->preloadPromotionVars();

        $this->load->model(['player_model']);
        $enable_OGP19808 = $this->utils->getConfig('enable_OGP19808');
        if( ! empty($enable_OGP19808) ){
            $playerId = $this->load->get_var('playerId');
            $result4fromLine = $this->player_model->check_playerDetail_from_line($playerId);
            if($result4fromLine['success'] === false ){ // OGP-19808 check  real name & SMS OTP
                if( $this->utils->is_mobile() ){
                    $url = site_url( $this->utils->getPlayerProfileUrl() );
                }else{
                    $url = site_url( $this->utils->getPlayerProfileSetupUrl() );
                }
                return redirect($url);
            }
        } // EOF if( ! empty($enable_OGP19808) ){...

        # Templates
        $this->loadTemplate();

        # Custom
        $this->template->add_function_js('/common/js/player_center/promotions.js');

        $this->CI->load->library(['iovation_lib']);
		$data['is_iovation_enabled'] = $this->utils->isOperatorSettingItemEnabled('iovation_api_list', 'enabled_iovation_in_promotion') && $this->CI->iovation_lib->isReady;
        if($data['is_iovation_enabled']){
            $this->template->add_function_js('/common/js/player_center/iovation.js');
			if($this->utils->getConfig('iovation')['use_first_party']){
				$this->template->add_js($this->utils->jsUrl($this->utils->getConfig('iovation')['first_party_js_config']));
			}else{
				$this->template->add_js($this->utils->jsUrl('config.js'));
			}
			$this->template->add_js($this->utils->jsUrl('iovation.js'));
		}

        $data['player_verified_phone'] = $this->player_model->isVerifiedPhone($this->load->get_var('playerId'));

        # Template-related variables
        $data['activeNav'] = 'promotions';
        $data['content_template'] = 'default_with_menu.php';
        $data['promoCmsSettingId'] = 0;
        $data['currentPromoCategory'] = 0;
        if( !empty($promoCode) ){
            // promoCode convert to promoCmsDetail
            list($promoruleDetail, $promoCmsSettingId) = $this->promorules->getByCmsPromoCodeOrId($promoCode);
            $data['promoCmsSettingId'] = $promoCmsSettingId?:0;
            $data['currentPromoCategory'] = $this->utils->safeGetArray($promoruleDetail, 'promoCategory', 0);
        }

        # Render
        $this->template->append_function_title(lang('cms.mobile.promoReqAppList'));
        $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/promotion/promotion', $data);
        $this->template->render();
    }

    /**
     * Add to the promo by promo-code at player_center, with embedded method.
     * Used for the entrance, //player.og.local/player_center2/promotion/addtopromo/nn4vmpfk
     *
     * @param string $promoCode The PromoCode of the promo
     * @param string $usageMode If embed will display promo list for resp. of joined, else same as trait function, "Player_center::addtopromo()".
     * @return void
     */
    public function addtopromo($promoCode, $usageMode = 'embed'){

        // hide the promolist
        $this->load->vars('mypromo', []);
        $this->load->vars('promo_list', []);
        $this->load->vars('promoCategoryList', []);
        $this->load->vars(['default_show_category_id' => 0]);
        $this->load->vars('currency', $this->utils->getCurrentCurrency());

        if( strtolower($usageMode) == 'embed'){
            $addtopromo = true;
            return $this->embed( $promoCode, $addtopromo );
        }else{
            return $this->traitAddtopromo($promoCode); // trait promo_module
        }

    }

    /**
     * Used for the entrance,  http://player.og.local/player_center2/promotion/embed/kcgtzzav
     *
     * @param string $promoCode
     * @param boolean $addtopromo
     * @return void
     */
    public function embed( $promoCode ='', $addtopromo = true) {
        // $addtopromo = true;// do add to preload-promo
        $playerId = $this->load->get_var('playerId'); // #6
        # Templates
        $this->loadTemplate();

        # Custom
        $this->template->add_function_js('/common/js/player_center/promotions.js');

        # Template-related variables
        $data['activeNav'] = 'promotions';
        $data['content_template'] = 'default_without_menu.php';

        $data['preloadPromoRespJoined'] = json_encode((object)[]);
        $data['preloadPromoJson'] = json_encode((object)[]);
        $data['lastAlertMessageJson'] = json_encode((object)[]);
        $isNotFondPromo = null;
        $promoCmsDetails = [];
        if( ! empty($promoCode) ){
            // promoCode convert to promoCmsDetail
            list($promoruleDetail, $promoCmsSettingId) = $this->promorules->getByCmsPromoCodeOrId($promoCode);
            $availableCmsDetails = $this->utils->getPlayerAvailablePromoList($playerId, $promoCmsSettingId);
            $promoCmsDetails = $availableCmsDetails['promo_list'];
        }else{
            $promoCmsSettingId = 0;
        }

        if( ! empty($promoCmsSettingId) ){
            if( ! empty($promoCmsDetails) ){
                $promoCmsDetail = $promoCmsDetails[0];

                if($addtopromo){
                    $promoCmsSettingId = $promoCmsDetail['promoCmsSettingId']; // #1
                    $action = 0; // #2
                    $preapplication = null; // #3
                    $is_api_call = false;  // #4
                    $ret_to_api = false;  // #5
                    $playerId = $this->load->get_var('playerId'); // #6
                    $allowGoPlayerPromotions = false; // #7
                    $allowAlertMessage = false; // #8
                    $lastAlertMessagesCollection = [];
                    $extra_info = null;
                    $resp = $this->request_promo($promoCmsSettingId // #1
                                            , $action // #2
                                            , $preapplication // #3
                                            , $is_api_call  // #4
                                            , $ret_to_api  // #5
                                            , $playerId // #6
                                            , $extra_info // #6.1
                                            , $allowGoPlayerPromotions // #7
                                            , $allowAlertMessage // #8
                                            , $lastAlertMessagesCollection // #9
                                        );

                    if( ! empty($resp) ){
                        $data['preloadPromoRespJoined'] = $resp;
                        $respJson = json_decode($resp, true);

                         // reload promo list
                        if( $respJson['status'] == 'success'
                            && false // hide the promolist
                        ){
                            $this->_loadVars4allpromo($playerId);
                        }
                    }

                    if( ! empty($lastAlertMessagesCollection) ){
                        $toSetUserdata = false;
                        $lastAlertMessageArray = $lastAlertMessagesCollection[ count($lastAlertMessagesCollection)-1 ];
                        $lastAlertMessageJson = $this->alertMessage($lastAlertMessageArray[0], $lastAlertMessageArray[1], $toSetUserdata);
                        $lastAlertMessageJson['type'] = $lastAlertMessageJson['result'];
                        $data['lastAlertMessageJson'] = json_encode($lastAlertMessageJson);
                    }


                    // 預載促銷資料。 preloadPromoJson
                    $playerId = $this->load->get_var('playerId');
                    $promoCmsSettingId = $promoCmsDetail['promoCmsSettingId'];
                    $preloadPromoJson = json_encode( $this->utils->getPlayerPromo('promojoint', $playerId, $promoCmsSettingId) );
                    $data['preloadPromoJson'] = $preloadPromoJson;

                    $this->utils->debug_log('error.default.message$preloadPromoJson:', $preloadPromoJson);
                }else{

                } // EOF if($addtopromo){...
            }else{
                // not fond promo by promoCode - will render with MessageBox
                $isNotFondPromo = true;
            } // EOF if( ! empty($promoCmsDetails) )
        }else{
            // not fond promo by promoCode - will render with MessageBox
            $isNotFondPromo = true;
        }

        if($isNotFondPromo == true){
            // not fond promo by promoCode - will render with MessageBox
            $toSetUserdata = false;
            $msg = sprintf(lang('gen.error.not_exist'), lang('Promotion'));
            $lastAlertMessageJson = $this->alertMessage(self::MESSAGE_TYPE_WARNING, $msg, $toSetUserdata);
            $lastAlertMessageJson['type'] = $lastAlertMessageJson['result'];
            $data['lastAlertMessageJson'] = json_encode($lastAlertMessageJson);
        }


        // not_login.t1t.player redirect to login page
        // default_all.min.js 需要 vagrant@default_og_livestableprod:~/Code/og$ ./create_links.sh
        // Implemented in promotionDetails.src.js, keyword:"isLogged".
        # Render
        $this->template->append_function_title(lang('cms.mobile.promoReqAppList'));
        $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/promotion/promotion', $data);
        $this->template->render();
    } // EOF embed

}
