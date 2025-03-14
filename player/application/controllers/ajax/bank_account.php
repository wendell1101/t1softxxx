<?php
require_once 'AjaxBaseController.php';

/**
 * Provides bank account ajax function
 *
 * @property Banktype $banktype
 * @property Playerbankdetails $playerbankdetails
 * @property CI_Form_validation $form_validation
 */
class Bank_account extends AjaxBaseController {
	public function __construct(){
		parent::__construct();

		$this->load->model(['banktype', 'playerbankdetails', 'player_model', 'financial_account_setting','player']);
		$this->load->library(['form_validation', 'player_functions']);
	}

    public function _remap($method){
        global $CI, $URI;
        if(!$this->load->get_var('isLogged')){
            return $this->returnJsonResult(array('status' => 'failed', 'msg' => lang('Not Login')));
        }

        $method = strtolower($this->input->server('REQUEST_METHOD')) . ucfirst($method);
        if(!method_exists($CI, $method)){
            return show_404();
        }

        $this->preloadBankData();

		return call_user_func_array(array(&$CI, $method), array_slice($URI->rsegments, 2));
    }

    protected function preloadBankData(){
        $player                 = $this->load->get_var('player');
        $bankTypeList           = $this->banktype->getBankTypes();
        $withdrawalBankTypeList = NULL;
        $depositBankTypeList    = NULL;

        foreach($bankTypeList as $bankType){
            if($bankType->payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_API){
                continue;
            }

            if($bankType->enabled_withdrawal){
                $withdrawalBankTypeList[] = $bankType;
            }

            if($bankType->enabled_deposit){
                $depositBankTypeList[] = $bankType;
            }
        }

        $this->load->vars('bankTypeList', $bankTypeList);
        $this->load->vars('withdrawalBankTypeList', $withdrawalBankTypeList);
        $this->load->vars('depositBankTypeList', $depositBankTypeList);
        $this->load->vars('realname', Player::getPlayerFullName($player['firstName'], $player['lastName'], $player['language']));


        if($this->utils->getConfig('enabled_set_realname_when_add_bank_card')){
            $this->load->vars('firstName', $player['firstName']);
            $this->load->vars('lastName', $player['lastName']);
        }
    }

    public function validate_bank_account_number($bank_account_number, $params){
        list($dwBank, $bank_type_id) = explode(',', $params);
        $playerId = $this->load->get_var('playerId');
        $bank_detail_id = $this->input->post('input-bank-detail-id');

        return $this->playerbankdetails->validate_bank_account_number($playerId, $bank_account_number, $dwBank, $bank_detail_id, $bank_type_id);
    }

    public function validate_crypto_account_network($crypto_network, $bank_type_id){
        if(empty($crypto_network)){
            return true;
        }

        $banktype = $this->banktype->getBankTypeById($bank_type_id);
        $cryptocurrency = $this->utils->getCryptoCurrency($banktype);
        $network_options = $this->utils->getConfig('network_options');
        $result = false;
        if(!empty($cryptocurrency) && !empty($network_options) && is_array($network_options)){
            $crypto = ($cryptocurrency == 'USDTL')? 'USDT' : $cryptocurrency;
            if(isset($network_options[$crypto])){
                if(in_array($crypto_network, $network_options[$crypto])) {
                    $result = true;
                }
            }
        }
        if(!$result){
            $this->form_validation->set_message('validate_crypto_account_network', "crypto network is not support.");
        }
        return $result;
    }

    public function validate_sms_code($sms_verification_code) {
        $this->utils->debug_log('validate_sms_verification_code', $sms_verification_code);

        $this->load->library('session');
        $this->load->model(['sms_verification','player_model']);

        $session_id = $this->session->userdata('session_id');
        $success    = false;
        $message    = lang('Verify SMS Code Failed');
        $player     = $this->load->get_var('player');
        $player_id  = $this->load->get_var('playerId');

        $player_contact_info = $this->player->getPlayerContactInfo($player_id);

        if(!empty($this->utils->getConfig('use_new_sms_api_setting'))){
            $restrict_area = 'sms_api_bankinfo_setting';
        }else{
            $restrict_area = 'default';
        }

        $success = !isset($sms_verification_code) || $this->sms_verification->validateVerificationCode($player_id, $session_id, $player_contact_info['contactNumber'], $sms_verification_code, $restrict_area);

        if(!$success) {
            $this->utils->debug_log('========== validate sms_verification_code from back office =====', $success);
            $success = $this->sms_verification->validateVerificationCode($player_id, null, $player_contact_info['contactNumber'], $sms_verification_code, $restrict_area);
        }

        if(!$success) {
            $validate_sms_record = $this->sms_verification->verificationCodeStatusDebug($player_id, $session_id, $player_contact_info['contactNumber'], $sms_verification_code, $restrict_area);
            $this->utils->debug_log('========== after verification =====', $validate_sms_record);

            if (count($validate_sms_record) > 0) {
                if ($validate_sms_record[0]['verified'] == '1' && $sms_verification_code == $validate_sms_record[0]['code'] ) {
                   $success = true;
                }
            }
        }
        return $success;
    }

	/**
     * Checks fields modified on player bank info
     *
     * @param array $origbank
     * @param array $data
     * @return boolean
     */
	protected function checkBankChanges($origbank, $data) {
		$array = null;

        if(isset($data['bankTypeId'])){
            $array .= $origbank['bankTypeId'] != $data['bankTypeId'] ? lang('player.ui35') . ', ' : '';
        }

        if(isset($data['bankAccountNumber'])){
            $array .= $origbank['bankAccountNumber'] != $data['bankAccountNumber'] ? lang('cashier.69') . ', ' : '';
        }

        if(isset($data['bankAccountFullName'])){
            $array .= $origbank['bankAccountFullName'] != $data['bankAccountFullName'] ? lang('cashier.68') . ', ' : '';
        }

        if(isset($data['province'])){
            $array .= $origbank['province'] != $data['province'] ? lang('cashier.70') . ', ' : '';
        }

        if(isset($data['city'])){
            $array .= $origbank['city'] != $data['city'] ? lang('cashier.71') . ', ' : '';
        }

        if(isset($data['branch'])){
            $array .= $origbank['branch'] != $data['branch'] ? lang('cashier.72') . ', ' : '';
        }

		return $modifiedField = empty($array) ? '' : substr($array, 0, -2);
	}

    protected function setupValidationRules($dwbank, $bank_type, $field_required, $is_new = TRUE){
        $this->form_validation->set_rules('input-bank-type-id', lang('cashier.81'), 'trim|required|xss_clean');
        if($is_new){
            if (!$this->utils->getConfig('hide_financial_account_ewallet_account_number')) {
                $this->form_validation->set_rules('input-acct-num', lang('financial_account.bankaccount'), 'trim|required|xss_clean|callback_validate_bank_account_number[' . $dwbank . ',' . $bank_type . ']');
                $this->form_validation->set_message('validate_bank_account_number', lang('account_number_can_not_be_duplicate'));
            }
        }

        if(in_array(Financial_account_setting::FIELD_NAME, $field_required)){
            if($this->utils->getConfig('enabled_set_realname_when_add_bank_card')){
                $this->form_validation->set_rules('input-first-name', lang('First Name'), 'trim|xss_clean|required');
                $this->form_validation->set_rules('input-last-name', lang('Last Name'), 'trim|xss_clean|required');
            }else{
                $this->form_validation->set_rules('input-acct-name', lang('financial_account.name'), 'trim|xss_clean|required');
            }
        }
        else{
            if($this->utils->getConfig('enabled_set_realname_when_add_bank_card')){
                $this->form_validation->set_rules('input-first-name', lang('First Name'), 'trim|xss_clean');
                $this->form_validation->set_rules('input-last-name', lang('Last Name'), 'trim|xss_clean');
            }else{
                $this->form_validation->set_rules('input-acct-name', lang('financial_account.name'), 'trim|xss_clean');
            }
        }

        if(in_array(Financial_account_setting::FIELD_BANK_AREA, $field_required)){
            $this->form_validation->set_rules('input-province', lang('financial_account.province'), 'trim|xss_clean|required');
            $this->form_validation->set_rules('input-city', lang('financial_account.city'), 'trim|xss_clean|required');
        }
        else{
            $this->form_validation->set_rules('input-province', lang('financial_account.province'), 'trim|xss_clean');
            $this->form_validation->set_rules('input-city', lang('financial_account.city'), 'trim|xss_clean');
        }

        if(in_array(Financial_account_setting::FIELD_BANK_BRANCH, $field_required)){
            $this->form_validation->set_rules('input-branch', ( $this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts') ? lang('financial_account.ifsc') : lang('financial_account.branch') ), 'trim|xss_clean|required');
        }
        else{
            $this->form_validation->set_rules('input-branch', ( $this->utils->getConfig('use_branch_as_ifsc_in_withdrawal_accounts') ? lang('financial_account.ifsc') : lang('financial_account.branch') ), 'trim|xss_clean');
        }

        if(in_array(Financial_account_setting::FIELD_NETWROK, $field_required)){
            $this->form_validation->set_rules('input-cryptonetwork',lang('financial_account.crypto_network'), 'trim|xss_clean|required|callback_validate_crypto_account_network[' . $bank_type . ']');
        }
        else{
            $this->form_validation->set_rules('input-cryptonetwork', lang('financial_account.crypto_network'), 'trim|xss_clean|callback_validate_crypto_account_network[' . $bank_type . ']');
        }

        if(in_array(Financial_account_setting::FIELD_PHONE, $field_required)){
            $this->form_validation->set_rules('input-mobile-num', lang('financial_account.phone'), 'trim|xss_clean|required');
        }
        else{
            $this->form_validation->set_rules('input-mobile-num', lang('financial_account.phone'), 'trim|xss_clean');
        }

        if(in_array(Financial_account_setting::FIELD_BANK_ADDRESS, $field_required)){
            $this->form_validation->set_rules('input-address', lang('financial_account.address'), 'trim|xss_clean|required');
        }
        else{
            $this->form_validation->set_rules('input-address', lang('financial_account.address'), 'trim|xss_clean');
        }

        $this->form_validation->set_rules('input-verification-code', lang('cashier.enter.sms'), 'trim|xss_clean|callback_validate_sms_code');
    }

    public function getEditDeposit($bank_detail_id){
        return $this->processGetEdit($bank_detail_id);
    }

    public function getEditWithdrawal($bank_detail_id){
        return $this->processGetEdit($bank_detail_id);
    }

    protected function processGetEdit($bank_detail_id){
        $playerId = $this->load->get_var('playerId');

        $playerBankDetail = $this->playerbankdetails->getPlayerBankDetailById($playerId, $bank_detail_id);
        if (empty($playerBankDetail)) {
            $message = sprintf(lang('gen.error.not_exist'), lang('pay.bankinfo'));
            $this->returnJsonResult(array('status' => 'success', 'msg' => $message));
            return;
        }

        $data = [];
        if($playerBankDetail->dwBank == Playerbankdetails::DEPOSIT_BANK){
            $data['preferredBankTypeList'] = $this->load->get_var('depositBankTypeList');
        }else{
            $data['preferredBankTypeList'] = $this->load->get_var('withdrawalBankTypeList');
        }

        $banktype = $this->banktype->getBankTypeById($playerBankDetail->bankTypeId);
        $payment_type_flag = $banktype->payment_type_flag;

        $data['account_validator'] = $this->financialAccountValidatorBuilder($payment_type_flag);
        $data['playerBankDetail'] = $playerBankDetail;

        return $this->load->view($this->utils->getPlayerCenterTemplate(FALSE) . '/bank_account/ajax/modal_edit_bank', $data);
    }

    /**
    * @deprecated No longer used because deactive operator_settings [financial_account_allow_edit]
    */
    public function postEditDeposit(){
        $playerId = $this->load->get_var('playerId');
        $bank_detail_id = $this->input->post('input-bank-detail-id');

        #Always true because deactive operator_settings [financial_account_allow_edit]
        if (true){
            $message = lang('Sorry, no permission');
            $this->returnJsonResult(array('status' => 'danger', 'msg' => $message));
            return;
        }

        $playerBankDetail = $this->playerbankdetails->getPlayerBankDetailById($playerId, $bank_detail_id, Playerbankdetails::DEPOSIT_BANK);
        if (empty($playerBankDetail)) {
            $message = sprintf(lang('gen.error.not_exist'), lang('pay.bankinfo'));
            $this->returnJsonResult(array('status' => 'success', 'msg' => $message));
            return;
        }

        $banktype = $this->banktype->getBankTypeById($playerBankDetail->bankTypeId);
        $payment_type_flag = $banktype->payment_type_flag;

        $account_validator = $this->financialAccountValidatorBuilder($payment_type_flag);
        $this->setupValidationRules(Playerbankdetails::DEPOSIT_BANK, $playerBankDetail->bankTypeId, $account_validator['field_required'], false);

        if ($this->form_validation->run() == false) {
            $validation_errors = validation_errors();
			$message = lang('notify.30');
            if(!empty($validation_errors)){
                $message .= $validation_errors;
            }
            return $this->returnJsonResult(['status' => 'error', 'msg' => $message]);
        }

        if($payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_CRYPTO){
            $branch = $this->input->post('input-cryptonetwork');
        }else{
            $branch = $this->input->post('input-branch');
        }

        $data = [];
        $data = [
            'bankAccountFullName' => $this->input->post('input-acct-name'),
            'bankAddress' => $this->input->post('input-address'),
            'city' => $this->input->post('input-city'),
            'province' => $this->input->post('input-province'),
            'branch' => $branch,
            'phone' => $this->input->post('input-mobile-num'),
        ];

        return $this->processPostEdit($playerId, $bank_detail_id, $data);
    }

    /**
    * @deprecated No longer used because deactive operator_settings [financial_account_allow_edit]
    */
    public function postEditWithdrawal(){
        $playerId = $this->load->get_var('playerId');
        $bank_detail_id = $this->input->post('input-bank-detail-id');

        #Always true because deactive operator_settings [financial_account_allow_edit]
        if (true){
            $message = lang('Sorry, no permission');
            $this->returnJsonResult(array('status' => 'danger', 'msg' => $message));
            return;
        }

        $playerBankDetail = $this->playerbankdetails->getPlayerBankDetailById($playerId, $bank_detail_id, Playerbankdetails::WITHDRAWAL_BANK, false);
        if (empty($playerBankDetail)) {
            $message = sprintf(lang('gen.error.not_exist'), lang('pay.bankinfo'));
            $this->returnJsonResult(array('status' => 'success', 'msg' => $message));
            return;
        }

        $banktype = $this->banktype->getBankTypeById($playerBankDetail->bankTypeId);
        $payment_type_flag = $banktype->payment_type_flag;

        $account_validator = $this->financialAccountValidatorBuilder($payment_type_flag);
        $this->setupValidationRules(Playerbankdetails::WITHDRAWAL_BANK, $playerBankDetail->bankTypeId, $account_validator['field_required']);
        if ($this->form_validation->run() == false) {
            $validation_errors = validation_errors();
			$message = lang('notify.30');
            if(!empty($validation_errors)){
                $message .= $validation_errors;
            }
            return $this->returnJsonResult(['status' => 'error', 'msg' => $message]);
        }

        if($payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_CRYPTO){
            $branch = $this->input->post('input-cryptonetwork');
        }else{
            $branch = $this->input->post('input-branch');
        }

        $data = [];
        $data = [
            'bankAccountFullName' => $this->input->post('input-acct-name'),
            'bankAddress' => $this->input->post('input-address'),
            'city' => $this->input->post('input-city'),
            'province' => $this->input->post('input-province'),
            'branch' => $branch,
            'phone' => $this->input->post('input-mobile-num'),
        ];

        return $this->processPostEdit($playerId, $bank_detail_id, $data);
    }

    protected function processPostEdit($playerId, $bank_detail_id, $data){
        //save bank changes
        $origbank = $this->player_functions->getBankDetailsById($bank_detail_id);
        $change = $this->checkBankChanges($origbank, $data);
        $changes = array(
            'playerBankDetailsId' => $bank_detail_id,
            'changes' => lang('lang.edit') . ' ' . lang('player.ui07') . ' (' . $change . ')',
            'createdOn' => date("Y-m-d H:i:s"),
            'operator' => $this->authentication->getUsername(),
        );
        $this->player_model->saveBankChanges($changes);

        $data['updatedOn'] = $this->utils->getNowForMysql();

        $result = $this->playerbankdetails->updatePlayerBankDetails($playerId, $bank_detail_id, $data);

        $message = ($result) ? lang('notify.32') : lang('notify.30');
        return $this->returnJsonResult(array('status' => 'success', 'msg' => $message));
    }

    public function getDepositDetail($bank_detail_id){
        return $this->processDetail($bank_detail_id);
    }

    public function getWithdrawalDetail($bank_detail_id){
        return $this->processDetail($bank_detail_id);
    }

    protected function processDetail($bank_detail_id){
        $playerId = $this->load->get_var('playerId');

        $playerBankDetail = $this->playerbankdetails->getPlayerBankDetailById($playerId, $bank_detail_id);
        if (empty($playerBankDetail)) {
            $message = sprintf(lang('gen.error.not_exist'), lang('pay.bankinfo'));
            $this->returnJsonResult(array('status' => 'success', 'msg' => $message));
            return;
        }

        $bankTypeDetail = new stdClass();
        $bankTypeDetail->bankTypeId = $playerBankDetail->bankTypeId;
        $bankTypeDetail->bankName = NULL;
        $bankTypeList = $this->load->get_var('bankTypeList');
        foreach($bankTypeList as $bankTypeData){
            if($playerBankDetail->bankTypeId == $bankTypeData->bankTypeId){
                $bankTypeDetail = $bankTypeData;
            }
        }

        $banktype = $this->banktype->getBankTypeById($playerBankDetail->bankTypeId);
        $payment_type_flag = $banktype->payment_type_flag;

        $data = [];
        $data['account_validator'] = $this->financialAccountValidatorBuilder($payment_type_flag);
        $data['playerBankDetail'] = $playerBankDetail;
        $data['bankTypeDetail'] = $bankTypeDetail;
        $data['double_submit_hidden_field'] = $this->initDoubleSubmitAndReturnHiddenFieldForAdmin($playerId);

        if ($this->utils->getConfig('enable_cpf_number') &&
            ($payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_BANK ||
             $payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_PIX)) {
            $cpf_number = $this->player->getPlayerPixNumberByPlayerId($playerId);
            if($this->utils->getConfig('switch_cpf_type')){
                $player_contact_info = $this->player->getPlayerContactInfo($playerId);
                if(strpos($bankTypeDetail->bank_code, 'PIX_CPF') !== false){
                    $playerBankDetail->pixType        = 'PIX_CPF';
                    $playerBankDetail->pixKey         = $cpf_number;
                    $playerBankDetail->pixKeyLabel = lang('financial_account.CPF_number').':';
                }else if(strpos($bankTypeDetail->bank_code, 'PHONE') !== false){
                    $playerBankDetail->pixType        = 'PHONE';
                    $playerBankDetail->pixKey         = $player_contact_info['contactNumber'];
                    $playerBankDetail->pixKeyLabel = lang('financial_account.phone').':';
                }else if(strpos($bankTypeDetail->bank_code, 'EMAIL') !== false){
                    $playerBankDetail->pixType        = 'EMAIL';
                    $playerBankDetail->pixKey         = $player_contact_info['email'];
                    $playerBankDetail->pixKeyLabel    = lang('lang.email').':';
                }else{
                    $playerBankDetail->pixType        = '';
                    $playerBankDetail->pixKey         = '';
                    $playerBankDetail->pixKeyLabel    = '';
                }
            }else{
                if(!empty($cpf_number)){
                    $data['cpf_number'] = $cpf_number;
                }else{
                    $data['cpf_number'] = '';
                }
            }
        }

        switch ($payment_type_flag) {
            case Financial_account_setting::PAYMENT_TYPE_FLAG_BANK:
            case Financial_account_setting::PAYMENT_TYPE_FLAG_PIX:
                return $this->load->view($this->utils->getPlayerCenterTemplate(FALSE) . '/bank_account/ajax/modal_view_bank', $data);
            case Financial_account_setting::PAYMENT_TYPE_FLAG_EWALLET:
                return $this->load->view($this->utils->getPlayerCenterTemplate(FALSE) . '/bank_account/ajax/modal_view_ewallet', $data);
            case Financial_account_setting::PAYMENT_TYPE_FLAG_CRYPTO:
                if (!empty($this->utils->getConfig('enable_crypto_details_in_crypto_bank_account'))) {
                    $playerCryptoBankDetail = $this->playerbankdetails->getPlayerCryptoBankDetailById($bank_detail_id);
                    $playerCryptoBankDetail = !empty($playerCryptoBankDetail) ? $playerCryptoBankDetail : false;
                    $data['playerCryptoBankDetail']  = $playerCryptoBankDetail;
                }
                return $this->load->view($this->utils->getPlayerCenterTemplate(FALSE) . '/bank_account/ajax/modal_view_crypto', $data);
        }
    }

    public function getAddDeposit($data = NULL){
        return $this->getAdd(Playerbankdetails::DEPOSIT_BANK, $data);
    }

    public function getAddWithdrawal($data){
        return $this->getAdd(Playerbankdetails::WITHDRAWAL_BANK, $data);
    }

    protected function getAdd($dwBank, $payment_type_flag){
        $playerId = $this->load->get_var('playerId');

        $playerBankDetail = new stdClass();
        $playerBankDetail->playerBankDetailsId = NULL;
        $playerBankDetail->bankTypeId          = NULL;
        $playerBankDetail->bankCode            = NULL;
        $playerBankDetail->bankAccountFullName = $this->load->get_var('realname');
        $playerBankDetail->bankAccountNumber   = NULL;
        $playerBankDetail->city                = NULL;
        $playerBankDetail->province            = NULL;
        $playerBankDetail->branch              = NULL;
        $playerBankDetail->phone               = NULL;
        $playerBankDetail->bankAddress         = NULL;
        if($this->utils->getConfig('enabled_set_realname_when_add_bank_card')){
            $playerBankDetail->bankAccountFirstName = $this->load->get_var('firstName');
            $playerBankDetail->bankAccountLastName = $this->load->get_var('lastName');
        }
        $player_contact_info = $this->player->getPlayerContactInfo($playerId);
        $data = [];
        $data['player_contact_info'] = $player_contact_info;
        if ($this->utils->getConfig('enable_cpf_number') &&
            ($payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_BANK ||
             $payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_PIX)){
            if($this->utils->getConfig('switch_cpf_type')){
                $cpf_number = !empty($this->player->getPlayerPixNumberByPlayerId($playerId))? $this->player->getPlayerPixNumberByPlayerId($playerId) : '';
                $playerBankDetail->pixTypeData  = ['PIX_CPF' => $cpf_number,
                                                   'PHONE' => $player_contact_info['contactNumber'],
                                                   'EMAIL' => $player_contact_info['email']];
                $playerBankDetail->pixType      = 'PIX'; //only CPF
                $playerBankDetail->pixKey       = !empty($cpf_number)?$cpf_number:'';
            }else{
                $cpf_number = $this->player->getPlayerPixNumberByPlayerId($playerId);
                $playerBankDetail->pixTypeData     = [];
                $playerBankDetail->pixType         = 'PIX'; //only CPF
                $playerBankDetail->pixKey          = !empty($cpf_number)?$cpf_number:'';
                $playerBankDetail->bankAccountNumber = !empty($cpf_number)?$cpf_number:'';
            }
        }else{
            $playerBankDetail->pixTypeData = [];
        }

        if($dwBank == Playerbankdetails::DEPOSIT_BANK){
            $bankTypeList = $this->load->get_var('depositBankTypeList');
        }else{
            $bankTypeList = $this->load->get_var('withdrawalBankTypeList');
        }
        $data['preferredBankTypeList'] = $bankTypeList;
        if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_one_account_per_institution')){
            $player_banktypes = $this->playerbankdetails->getBankTypesByPlayerId($playerId, $dwBank);
            if(!is_null($player_banktypes)){
                $available = array();
                foreach ($bankTypeList as $key => $value) {
                    if(!in_array($value->bankTypeId, $player_banktypes)){
                        $available[] = $value;
                    }
                }
                $data['preferredBankTypeList'] = $available;
            }
        }

        $data['account_validator'] = $this->financialAccountValidatorBuilder($payment_type_flag);
        $data['playerBankDetail']  = $playerBankDetail;
        switch ($payment_type_flag) {
            case Financial_account_setting::PAYMENT_TYPE_FLAG_BANK:
            case Financial_account_setting::PAYMENT_TYPE_FLAG_PIX:
                return $this->load->view($this->utils->getPlayerCenterTemplate(FALSE) . '/bank_account/ajax/modal_edit_bank', $data);
            case Financial_account_setting::PAYMENT_TYPE_FLAG_EWALLET:
                return $this->load->view($this->utils->getPlayerCenterTemplate(FALSE) . '/bank_account/ajax/modal_edit_ewallet', $data);
            case Financial_account_setting::PAYMENT_TYPE_FLAG_CRYPTO:
                return $this->load->view($this->utils->getPlayerCenterTemplate(FALSE) . '/bank_account/ajax/modal_edit_crypto', $data);
        }
    }

    protected function postAddDeposit(){
        $playerId = $this->load->get_var('playerId');

        $all_banks = $this->playerbankdetails->getDepositBankDetail($playerId);
        $input_banktype_id = $this->input->post('input-bank-type-id');

        $message = '';
        if (!Playerbankdetails::AllowAddBankDetail(Playerbankdetails::DEPOSIT_BANK, $all_banks, $message, $input_banktype_id)){
            $this->returnJsonResult(array('status' => 'failed', 'msg' => $message));
            return;
        }

        $banktype = $this->banktype->getBankTypeById($input_banktype_id);
        $payment_type_flag = $banktype->payment_type_flag;

        $account_validator = $this->financialAccountValidatorBuilder($payment_type_flag);
        $this->setupValidationRules(Playerbankdetails::DEPOSIT_BANK, $input_banktype_id, $account_validator['field_required']);

        if ($this->form_validation->run() == false) {
            $validation_errors = validation_errors();
			$message = lang('notify.30');
            if(!empty($validation_errors)){
                $message .= $validation_errors;
            }
            return $this->returnJsonResult(['status' => 'error', 'msg' => $message]);
        }

        $default_bank = array_filter((empty($all_banks)) ? [] : $all_banks, function($bank){
            return ($bank['isDefault']) ? TRUE : FALSE;
        });

        
        if($payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_CRYPTO){
            $branch = !($this->input->post('input-cryptonetwork')) ? '' : $this->input->post('input-cryptonetwork');
        }else{
            $branch = !($this->input->post('input-branch')) ? '' : $this->input->post('input-branch');
        }

        if($this->utils->getConfig('enabled_set_realname_when_add_bank_card')){
            $player    = $this->load->get_var('player');
            $firstName = !($this->input->post('input-first-name')) ? '' : $this->input->post('input-first-name');
            $lastName  = !($this->input->post('input-last-name')) ? '' : $this->input->post('input-last-name');
            $bankAccountFullName = Player::getPlayerFullName($firstName ,$lastName , $player['language']);
            $this->utils->debug_log('===========enabled_set_realname_when_add_bank_card',$firstName,$lastName,$bankAccountFullName);
        }else{
            $bankAccountFullName = !($this->input->post('input-acct-name')) ? '' : $this->input->post('input-acct-name');
        }

        $data = [
            'playerId' => $playerId,
            'bankTypeId' => $input_banktype_id,
            'bankAccountFullName' => $bankAccountFullName,
            'bankAccountNumber' => !($this->input->post('input-acct-num')) ? '' : $this->input->post('input-acct-num'),
            'bankAddress' => !($this->input->post('input-address')) ? '' : $this->input->post('input-address'),
            'city' => !($this->input->post('input-city')) ? '' : $this->input->post('input-city'),
            'province' => !($this->input->post('input-province')) ? '' : $this->input->post('input-province'),
            'branch' => $branch,
            'isDefault' => (empty($default_bank)) ? '1' : '0',
            'isRemember' => '1',
            'dwBank' => Playerbankdetails::DEPOSIT_BANK,
            'verified' => '1',
            'status' => '0',
            'phone' => !($this->input->post('input-mobile-num')) ? '' : $this->input->post('input-mobile-num'),
            'pixType' => !($this->input->post('input-pixtype')) ? '' : $this->input->post('input-pixtype'),
            'pixKey' => !($this->input->post('input-pixkey')) ? '' : $this->input->post('input-pixkey')
        ];

        if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_deposit_account_default_unverified')){
            $data['verified'] = '0';
        }

        if($this->utils->getConfig('enabled_set_realname_when_add_bank_card')){
            $data['firstName'] = $firstName;
            $data['lastName'] = $lastName;
        }

        if ($this->utils->getConfig('enable_cpf_number') &&
            ($payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_BANK ||
             $payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_PIX)){
            $cpf_number = $this->player->getPlayerPixNumberByPlayerId($playerId);
            if($this->utils->getConfig('switch_cpf_type')){
                $player_contact_info = $this->player->getPlayerContactInfo($playerId);
                if($data['pixType'] == 'PIX_CPF'){
                    $data['pixType']        = 'PIX_CPF';
                    $data['pixKey']         = $cpf_number;
                }else if($data['pixType'] == 'PHONE'){
                    $data['pixType']        = 'PHONE';
                    $data['pixKey']         = $player_contact_info['contactNumber'];
                }else if($data['pixType'] == 'EMAIL'){
                    $data['pixType']        = 'EMAIL';
                    $data['pixKey']         = $player_contact_info['email'];
                }else{
                    $data['pixType']  = '';
                    $data['pixKey'] = '';
                }
            }else{
                if(!empty($cpf_number)){
                    $data['pixType']        = 'CPF';
                    $data['pixKey']         = $cpf_number;
                }else{
                    $data['pixType']  = '';
                    $data['pixKey'] = '';
                }
            }
        }else{
            $data['pixType']  = '';
            $data['pixKey'] = '';
        }

        return $this->processPostAdd($playerId, $data);
    }

    protected function postAddWithdrawal(){
        $playerId = $this->load->get_var('playerId');

        $all_banks = $this->playerbankdetails->getWithdrawBankDetail($playerId);
        $input_banktype_id = $this->input->post('input-bank-type-id');

        $message = '';
        if (!Playerbankdetails::AllowAddBankDetail(Playerbankdetails::WITHDRAWAL_BANK, $all_banks, $message, $input_banktype_id)){
            $this->returnJsonResult(array('status' => 'failed', 'msg' => $message));
            return;
        }

        $banktype = $this->banktype->getBankTypeById($input_banktype_id);
        $payment_type_flag = $banktype->payment_type_flag;
        $oldBankAccountFullName = $this->load->get_var('realname');
        $newBankAccountFullName = !($this->input->post('input-acct-name')) ? '' : $this->input->post('input-acct-name');

        $account_validator = $this->financialAccountValidatorBuilder($payment_type_flag);

        $this->setupValidationRules(Playerbankdetails::WITHDRAWAL_BANK, $input_banktype_id, $account_validator['field_required']);
        if ($this->form_validation->run() == false) {
            $validation_errors = validation_errors();
			$message = lang('notify.30');
			if(!empty($validation_errors)){
                $message .= $validation_errors;
            }
            return $this->returnJsonResult(['status' => 'error', 'msg' => $message]);
        }

        if ($account_validator['field_show'][0]) {
            if (!$account_validator['allow_modify_name'] && !$this->utils->getConfig('enabled_set_realname_when_add_bank_card')) {
                if ($oldBankAccountFullName != $newBankAccountFullName) {
                    return $this->returnJsonResult(['status' => 'error', 'msg' => lang('financial_account.verify_account_name')]);
                }
            }
        }

        $default_bank = array_filter((empty($all_banks)) ? [] : $all_banks, function($bank){
            return ($bank['isDefault']) ? TRUE : FALSE;
        });

        if($payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_CRYPTO){
            $branch = !($this->input->post('input-cryptonetwork')) ? '' : $this->input->post('input-cryptonetwork');
        }else{
            $branch = !($this->input->post('input-branch')) ? '' : $this->input->post('input-branch');
        }

        if($this->utils->getConfig('enabled_set_realname_when_add_bank_card')){
            $player    = $this->load->get_var('player');
            $firstName = !($this->input->post('input-first-name')) ? '' : $this->input->post('input-first-name');
            $lastName  = !($this->input->post('input-last-name')) ? '' : $this->input->post('input-last-name');
            $bankAccountFullName = Player::getPlayerFullName($firstName ,$lastName , $player['language']);
            $this->utils->debug_log('===========enabled_set_realname_when_add_bank_card',$firstName,$lastName,$bankAccountFullName);
        }else{
            $bankAccountFullName = !($this->input->post('input-acct-name')) ? '' : $this->input->post('input-acct-name');
        }

        $data = [
            'playerId' => $playerId,
            'bankTypeId' => $input_banktype_id,
            'bankAccountFullName' => $bankAccountFullName,
            'bankAccountNumber' => !($this->input->post('input-acct-num')) ? '' : $this->input->post('input-acct-num'),
            'bankAddress' => !($this->input->post('input-address')) ? '' : $this->input->post('input-address'),
            'city' => !($this->input->post('input-city')) ? '' : $this->input->post('input-city'),
            'province' => !($this->input->post('input-province')) ? '' : $this->input->post('input-province'),
            'branch' => $branch,
            'isDefault' => (empty($default_bank)) ? '1' : '0',
            'isRemember' => '1',
            'dwBank' => Playerbankdetails::WITHDRAWAL_BANK,
            'verified' => '1',
            'status' => '0',
            'phone' => !($this->input->post('input-mobile-num')) ? '' : $this->input->post('input-mobile-num'),
        ];

        if($this->utils->getConfig('enabled_set_realname_when_add_bank_card')){
            $data['firstName'] = $firstName;
            $data['lastName'] = $lastName;
        }

        if($this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_withdraw_account_default_unverified')){
            $data['verified'] = '0';
        }

        if ($this->utils->getConfig('enable_cpf_number') &&
            ($payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_BANK ||
             $payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_PIX)) {
            $cpf_number = $this->player->getPlayerPixNumberByPlayerId($playerId);
            if(!empty($cpf_number)){
                $data['pixType'] = 'CPF';
                $data['pixKey'] = $cpf_number;
            }else{
                $data['pixType']  = '';
                $data['pixKey'] = '';
            }
        }else{
            $data['pixType']  = '';
            $data['pixKey'] = '';
        }

        return $this->processPostAdd($playerId, $data);
    }

    protected function processPostAdd($playerId, $data){
        $bank_details_id=null;

        if(!$this->verifyAndResetDoubleSubmitForAdmin($playerId)){
            $message = lang('Please refresh and try, and donot allow double submit');
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, $message);
            $this->returnJsonResult(array('status' => 'false', 'msg' => $message, 'bank_detail' => ''));
            return;
        }

        if($this->utils->getConfig('enabled_set_realname_when_add_bank_card')){
            $playerDetailsData['firstName'] = $data['firstName'];
            $playerDetailsData['lastName']  = $data['lastName'];
            unset($data['firstName']);
            unset($data['lastName']);
        }


        $isset_bank_account=$this->playerbankdetails->getBankDetailsByBankAccount($data['bankTypeId'], $data['bankAccountNumber']);
        if(!$isset_bank_account){
        $this->playerbankdetails->dbtransOnly(function()
            use($playerId, $data, &$bank_details_id){
            $bank_details_id = $this->playerbankdetails->addBankDetailsByDeposit($data);
            if($data['isDefault'] == '1') {
                $this->playerbankdetails->setPlayerDefaultBank($playerId, $data['dwBank'], $bank_details_id);
            }

            //save bank history
            $changes = array(
                'playerBankDetailsId' => $bank_details_id,
                'changes' => lang('Add') . ' ' . lang('lang.bank'),
                'createdOn' => date("Y-m-d H:i:s"),
                'operator' => $this->authentication->getUsername(),
            );
            $this->player_model->saveBankChanges($changes);

            return !empty($bank_details_id);
        });
        }else{
            $bank_details_id=$isset_bank_account['playerBankDetailsId'];
        }

        if (!empty($this->utils->getConfig('enable_crypto_details_in_crypto_bank_account')) && in_array($data['bankTypeId'], $this->utils->getConfig('enable_crypto_details_in_crypto_bank_account'))) {
            if ($bank_details_id) {
                $playercryptobankdetails = [
                    'player_id' => $playerId,
                    'player_bank_detailsid' => $bank_details_id,
                    'crypto_username' => !($this->input->post('input-crypto-name')) ? '' : $this->input->post('input-crypto-name'),
                    'crypto_email' => !($this->input->post('input-crypto-email')) ? '' : $this->input->post('input-crypto-email'),
                    'created_at' => date("Y-m-d H:i:s"),
                    'created_by' => $playerId
                ];

                $crypto_bank_details_id = $this->playerbankdetails->addCryptoBankDetails($playercryptobankdetails);
                $this->utils->debug_log('playercryptobankdetails', $playercryptobankdetails, $crypto_bank_details_id);
            }
        }

        if($this->utils->getConfig('enabled_set_realname_when_add_bank_card')){
            if ($bank_details_id) {
                $this->player_functions->editPlayerDetails($playerDetailsData, $playerId);
                $this->utils->debug_log('update player', $playerDetailsData, $playerId);
            }
        }

        $message = ($bank_details_id) ? lang('notify.31') : lang('notify.30');
        return $this->returnJsonResult(array('status' => 'success', 'msg' => $message, 'bank_detail' => $this->_displayBankDetail($this->playerbankdetails->getBankDetailInfo($bank_details_id))));
    }

    public function getDeleteDeposit($bank_detail_id){
        return $this->processDelete($bank_detail_id);
    }

    public function getDeleteWithdrawal($bank_detail_id){
        return $this->processDelete($bank_detail_id);
    }

    protected function processDelete($bank_detail_id){
        $playerId = $this->load->get_var('playerId');

        if (!$this->operatorglobalsettings->getSettingValueWithoutCache('financial_account_allow_delete')){
            $message = lang('Sorry, no permission');
            $this->returnJsonResult(array('status' => 'danger', 'msg' => $message));
            return;
        }
        //save bank history
        $changes = array(
            'playerBankDetailsId' => $bank_detail_id,
            'changes' => lang('Delete') . ' ' . lang('lang.bank'),
            'createdOn' => date("Y-m-d H:i:s"),
            'operator' => $this->authentication->getUsername(),
        );
        $this->player_model->saveBankChanges($changes);

        $playerBankDetail = $this->playerbankdetails->getPlayerBankDetailById($playerId, $bank_detail_id);
        if (empty($playerBankDetail)) {
            $message = sprintf(lang('gen.error.not_exist'), lang('pay.bankinfo'));
            $this->returnJsonResult(array('status' => 'failed', 'msg' => $message));
            return;
        }

        $response = [];
        if($this->playerbankdetails->deletePlayerBankInfo($bank_detail_id)){
            $response = ['status' => 'success', 'msg' => lang('sys.gd29')];
        }else{
            $response = ['status' => 'failed', 'msg' => lang('sys.gd26')];
        }

        $this->returnJsonResult($response);
        return;
    }

    protected function _displayBankDetail($source_bank_detail){
        $bank_detail = [
            'bankDetailsId' => $source_bank_detail['playerBankDetailsId'],
            'bankAccountFullName' => $source_bank_detail['bankAccountFullName'],
            'bankAccountNumber' => Playerbankdetails::getDisplayAccNum($source_bank_detail['bankAccountNumber']),
            'bankName' => lang($source_bank_detail['bankName']),
            'bankAddress' => $source_bank_detail['bankAddress'],
            'bankTypeId' => $source_bank_detail['bankTypeId'],
            'branch' => $source_bank_detail['branch'],
            'city' => $source_bank_detail['city'],
            'province' => $source_bank_detail['province'],
            'phone' => $source_bank_detail['phone'],
        ];
        return $bank_detail;
    }

    protected function getSetDefault($bank_details_id) {
        $playerId = $this->load->get_var('playerId');

        $playerBankDetail = $this->playerbankdetails->getPlayerBankDetailById($playerId, $bank_details_id);
        if (empty($playerBankDetail)) {
            $this->returnJsonResult(['status' => 'failed', 'msg' => sprintf(lang('gen.error.not_exist'), lang('pay.bankinfo'))]);
        }

        $this->playerbankdetails->setPlayerDefaultBank($playerId, $playerBankDetail->dwBank, $bank_details_id);

        $message = lang('notify.28');

        $this->returnJsonResult(array('status' => 'success', 'msg' => $message));
    }

    public function financialAccountValidatorBuilder($payment_type_flag){
        $financial_account_rule = $this->financial_account_setting->getPlayerFinancialAccountRulesByPaymentAccountFlag($payment_type_flag);

        $bank_card_validator = array();
        $bank_card_validator['only_allow_numeric'] = $financial_account_rule['account_number_only_allow_numeric'];
        $bank_card_validator['allow_modify_name']  = $financial_account_rule['account_name_allow_modify_by_players'];
        $bank_card_validator['field_required']     = explode(',', $financial_account_rule['field_required']);
        $bank_card_validator['field_show']         = explode(',', $financial_account_rule['field_show']);

        $account_min = $financial_account_rule['account_number_min_length'];
        $account_max = $financial_account_rule['account_number_max_length'];
        $bank_card_validator['bankAccountNumber'] = [
            'required'       => TRUE,
            'min_max_length' => [$account_min, $account_max],
            'remote'         => '/api/bankAccountNumber',
            'error_remote'   => [
                'invalid' => 'account_number_can_not_be_duplicate',
                'valid'   => 'account_number_allow_used'
            ]
        ];
        $bank_card_validator['bankAccountFullName'] = [
            'min_max_length' => [1, 200]
        ];
        $bank_card_validator['bankAccountFullName'] = (in_array(Financial_account_setting::FIELD_NAME, $bank_card_validator['field_required'])) ?'': '';
        $bank_card_validator['phone']               = (in_array(Financial_account_setting::FIELD_PHONE, $bank_card_validator['field_required'])) ? ['required' => true] : '';
        $bank_card_validator['branch']              = (in_array(Financial_account_setting::FIELD_BANK_BRANCH, $bank_card_validator['field_required'])) ? ['required' => true] : '';
        $bank_card_validator['area']                = (in_array(Financial_account_setting::FIELD_BANK_AREA, $bank_card_validator['field_required'])) ? ['required' => true] : '';
        $bank_card_validator['bankAddress']         = (in_array(Financial_account_setting::FIELD_BANK_ADDRESS, $bank_card_validator['field_required'])) ? ['required' => true] : '';
        $bank_card_validator['cryptonetwork']       = (in_array(Financial_account_setting::FIELD_NETWROK, $bank_card_validator['field_required'])) ? ['required' => true] : '';
        $bank_card_validator['smsVerificationCode'] = ['required' => true];

        if ($this->utils->getConfig('enable_cpf_number') &&
            ($payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_BANK ||
             $payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_PIX)) {
            $bank_card_validator['pixkey'] = ['required' => true];
            unset($bank_card_validator['bankAccountNumber']['required']);
            unset($bank_card_validator['bankAccountNumber']['min_max_length']);
        }

        if (!empty($this->utils->getConfig('enable_crypto_details_in_crypto_bank_account')) && $payment_type_flag == Financial_account_setting::PAYMENT_TYPE_FLAG_CRYPTO) {
            $bank_card_validator['bankAccountCryptoName'] = [
                'min_max_length' => [6, 50]
            ];
        }
        return $bank_card_validator;
    }

    protected function getCryptoNetwork($bank_type_id = '') {
        $banktype = $this->banktype->getBankTypeById($bank_type_id);
        $cryptocurrency = $this->utils->getCryptoCurrency($banktype);
        $network_options = $this->utils->getConfig('network_options');
        $crypto_network_options = [];
        if(!empty($cryptocurrency) && !empty($network_options) && is_array($network_options)){
            if($cryptocurrency == 'USDTL'){
                $crypto = 'USDT';
            }else{
                $crypto = $cryptocurrency;
            }
            if(isset($network_options[$crypto])){
                foreach ($network_options[$crypto] as $network_option) {
                    $crypto_network_options[] = $network_option;
                }
            }
        }
        $this->returnJsonResult(array('status' => 'success', 'crypto_network_options' => $crypto_network_options));
    }
}
