<?php

require_once 'PlayerCenterBaseController.php';

/**
 * Provides bank account function
 *
 * @property Playerbankdetails $playerbankdetails
 * @property Player_Functions $player_functions
 */
class Bank_account extends PlayerCenterBaseController {
    public function __construct() {
        parent::__construct();

        $this->preloadCashierVars();

        $this->load->model(['playerbankdetails', 'banktype', 'financial_account_setting','player_model','registration_setting']);
    }

    public function index() {
        $enable_OGP19808 = $this->utils->getConfig('enable_OGP19808');
        $playerId = $this->load->get_var('playerId');

        $result4fromLine = $this->player_model->check_playerDetail_from_line($playerId);
        if($result4fromLine['success'] === false ){
            if( ! empty($enable_OGP19808) ){
                if( $this->utils->is_mobile() ){
                    $url = site_url( $this->utils->getPlayerProfileUrl() );
                }else{
                    $url = site_url( $this->utils->getPlayerProfileSetupUrl() );
                }
                return redirect($url);
            }// EOF if( ! empty($enable_OGP19808) ){...
        }// EOF if($result4fromLine['success'] === false ){...

        if($this->utils->getConfig('hidden_status_inactive_on_player_center')){
            $options['exclude_status'] = [Playerbankdetails::STATUS_INACTIVE];
        }else{
            $options = [];
        }

        $bank_details = $this->playerbankdetails->getNotDeletedBankInfoList($playerId, $options);

        $data['bank_details'] = $bank_details;
        $data['sub_nav_active'] = 'bank_account';
        $data['banktype'] = $this->banktype;
        $data['double_submit_hidden_field']=$this->initDoubleSubmitAndReturnHiddenField($playerId);

        $registrationFields = $this->registration_setting->getRegistrationFieldsByAlias();
        $data['registrationFields'] = $registrationFields;
        if (isset($registrationFields['pix_number']) && $registrationFields['pix_number']['account_edit'] == 0) {
            $data['edit_cpf_number_status'] = 'allow_edit_cpf_number';
        } else {
            $data['edit_cpf_number_status'] = 'not_allow_edit_cpf_number';
        }

        # List of subwallet and game wallet with custom list control display in quick transfer bar
        $subwallet=null;
        $success=$this->wallet_model->lockAndTransForPlayerBalance($playerId, function () use (
            $playerId, &$subwallet) {

            $subwallet = $this->wallet_model->getAllPlayerAccountByPlayerId($playerId);
            return !empty($subwallet);
        });
        $data['game'] = $this->external_system->getAllActiveSytemGameApi();
        $data['subwallet'] = $subwallet;
        $data['active_flag'] = $this->banktype->getDistinctActivePaymentTypeFlag();

        $this->loadTemplate();
        $this->template->append_function_title(lang('Bank Account'));
        $this->template->add_js('/common/js/player_center/player-cashier.js');
		$this->template->add_js('/common/js/plugins/province_city_select.js');
        $this->template->add_js('/common/js/player_center/player-bank-account.js');
		$this->template->add_js('/resources/js/validator.js');
        $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/bank_account/list', $data);
        $this->template->render();
    }
}
