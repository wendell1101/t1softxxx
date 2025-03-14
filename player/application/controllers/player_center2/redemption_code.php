<?php

require_once 'PlayerCenterBaseController.php';

/**
 * Provides bank account function
 *
 * @property Playerbankdetails $playerbankdetails
 * @property Player_Functions $player_functions
 */
class Redemption_code extends PlayerCenterBaseController {
    public function __construct() {
        parent::__construct();

        $this->preloadCashierVars();

        $this->load->model(['player_model']);
    }

    public function index() {

        $playerId = $this->load->get_var('playerId');
        $data['sub_nav_active'] = 'redemption_code';
        $data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenField($playerId);

        if(!$this->utils->enableRedemptionCodeInPlayerCenter()){
            redirect('/');
        }
        $this->loadTemplate();
        $this->template->append_function_title(lang('redemptionCode.redemptionCode'));
        $this->template->add_js('/common/js/player_center/player-cashier.js');
		$this->template->add_js('/resources/js/validator.js');
        $this->template->add_js('resources/js/online/promotions.js');
        $this->template->add_js('/common/js/player_center/promotions.js');
        // $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/bank_account/list', $data);
        // /Users/min/Code/og_livestableprod/player/application/views/stable_center2/redemption_code/general.php
        $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/redemption_code/general', $data);
        $this->template->render();
    }
}
