<?php
require_once 'PlayerCenterBaseController.php';

require_once dirname(dirname(__FILE__)) . '/modules/shopping_center_module.php';

/**
 * Lists promotion
 */
class Shop extends PlayerCenterBaseController {

    /// How to override trait function and call it from the overridden function?
    // Ref. to https://stackoverflow.com/a/11939306
    use shopping_center_module {
        // addtopromo as protected traitAddtopromo;
    }

    public function __construct(){
        parent::__construct();
        $this->load->helper('url');
        // $this->preloadPromotionVars();

        $this->load->vars('content_template', 'default_with_menu.php');
        $this->load->vars('activeNav', 'shop_link');
    }

    private function preloadShopVars() {
        $playerId = $this->load->get_var('playerId');


    }

    public function index() {
        $this->preloadShopVars();

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

        $shoppingList = $this->utils->getAvailableShoppingList();
        foreach ($shoppingList as $key => $item){

            $file_path = $this->utils->getShopThumbnailsPath();
            if (array_key_exists('promoThumbnail', $item)) {
                $file_path = $file_path . $item['promoThumbnail'];
            }

            if (file_exists($file_path) &&!empty($item['banner_url'])) {
                if ($item['is_default_banner_flag']) {
                    $item['shopBannerUrl'] = $this->utils->imageUrl('shopping_banner/' . $item['banner_url']);
                } else {
                    if ($this->utils->isEnabledMDB()) {
                        $activeDB = $this->utils->getActiveTargetDB();
                        $item['shopBannerUrl'] = base_url().'upload/'.$activeDB.'/shopthumbnails/'.$item['banner_url'];
                    } else {
                        $item['shopBannerUrl'] = base_url().'upload/shopthumbnails/'.$item['banner_url'];
                    }
                }
            } else {
                $item['shopBannerUrl'] = $this->utils->imageUrl('shopping_banner/shop_banner_temp1.jpg');
            }
            $item['required_points'] = json_decode($item['requirements'], true)['required_points'];
            $shoppingList[$key] = $item;
        }

        # Templates
        $this->loadTemplate();

        # Custom
        $this->template->add_function_js('/common/js/player_center/player-shop.js');

        # Template-related variables
        $data['shoppingList'] = $shoppingList;
        $data['playerId'] = $this->authentication->getPlayerId();

        # Render
        $this->template->append_function_title(lang("Shop"));

        $shop_path = '/includes/dashboard/shop';

        if ($this->utils->getConfig('custom_shop_ui') == 'smash') {
            $shop_path = '/includes/dashboard/smash/shop';
        }

        // $this->template->append_function_title(lang('Refer a Friend'));
        $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . $shop_path, $data);
        $this->template->render();
    }
}
