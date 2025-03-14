<?php
require_once 'PlayerCenterBaseController.php';

/**
 * Provides Report function
 *
 */
class Report extends PlayerCenterBaseController{
    public function __construct(){
        parent::__construct();

        $this->load->model(array('player_promo','operatorglobalsettings', 'player_model'));

        $this->load->vars('content_template', 'default_with_menu.php');
        $this->load->vars('activeNav', 'report');
    }

    public function index($report_type = NULL){
        $player_id = $this->load->get_var('playerId');

        $enable_OGP19808 = $this->utils->getConfig('enable_OGP19808');
        if( ! empty($enable_OGP19808) ){
            $result4fromLine = $this->player_model->check_playerDetail_from_line($player_id);
            if($result4fromLine['success'] === false ){
                if( $this->utils->is_mobile() ){
                    $url = site_url( $this->utils->getPlayerProfileUrl() );
                }else{
                    $url = site_url( $this->utils->getPlayerProfileSetupUrl() );
                }
                return redirect($url);
            }
        } // EOF if( ! empty($enable_OGP19808) ){...


        $data = [];
        $data['startLastMonth' ] = $startLastMonth = mktime(0, 0, 0, date("m") - 1, 1, date("Y"));
        $data['endLastMonth'] = $endLastMonth = mktime(0, 0, 0, date("m"), 0, date("Y"));

        $data['year'] = $year = date('Y') - 1; // Get current year and subtract 1
        $data['start'] = $start = mktime(0, 0, 0, 1, 1, $year);
        $data['end']  =$end = mktime(0, 0, 0, 12, 31, $year);

        $data['first'] = $first = date("Y-m-d 00:00:00", strtotime("first day of this month"));
        $data['last'] = $last = date("Y-m-d 23:59:59", strtotime("last day of this month"));

        $data['firstDayYear'] = $firstDayYear = date("Y-m-d 00:00:00", strtotime("first day of this year"));
        $data['lastDayYear'] = $lastDayYear = date("Y-m-d 23:59:59", strtotime("last day of this year"));

        $data['game_platforms'] =$game_platforms = $this->external_system->getSystemCodeMapping();

        if($this->utils->getConfig('account_history_unsettled_game_history_game_codes')) {

            $this->CI->load->model(['game_description_model']);

            $game_code_list = array();

            $game_platform_game_code = $this->utils->getConfig('account_history_unsettled_game_history_game_codes');

            foreach($game_platform_game_code as $game_platform => $game_codes){


                foreach($game_codes as $game_code) {

                    $game_code_detail = array();

                    $game_description = $this->game_description_model->getGameDetailsByGameCodeAndGamePlatform($game_platform, $game_code);

                    $current_lang = $this->language_function->getCurrentLangForPromo();

                    $game_names = $this->utils->extractLangJson($game_description->game_name);

                    $game_name_lang = isset($game_names[$current_lang]) ? $game_names[$current_lang] : $game_names['en'];

                    $game_code_detail["game_code"] = $game_description->game_code;
                    $game_code_detail["game_name"] = $game_name_lang;
                    $game_code_list[] = $game_code_detail;


                }
            }

            $data['game_codes'] = $game_code_list;
        }


        if($this->utils->getConfig('eanble_display_player_total_bet_amount_in_game_history')){
            $totalBettingAmount = $this->player_model->getPlayersTotalBettingAmount($player_id);
        }
        $totalBettingAmount = !empty($totalBettingAmount) ? $totalBettingAmount : 0;
        $data['totalBettingAmount'] = $this->utils->formatCurrencyNoSym($totalBettingAmount);

        $forceFetchSeamlessSubwallet = true;
        $data['game_wallet_settings'] = $this->operatorglobalsettings->getGameWalletSettings($forceFetchSeamlessSubwallet);

        $data['report_type'] = (empty($report_type)) ? $this->utils->getConfig('default_player_center_account_history_tab') : $report_type;

        $data['player_promo_status'] = $player_promo_status = [
            Player_promo::TRANS_STATUS_APPROVED => lang('PENDING'),
            Player_promo::TRANS_STATUS_REQUEST => lang('APPLIED'),
            Player_promo::TRANS_STATUS_MANUAL_REQUEST_APPROVED_WITHOUT_RELEASE_BONUS => lang('ACTIVE'),
            Player_promo::TRANS_STATUS_APPROVED_WITHOUT_RELEASE_BONUS => lang('LOCKED BONUS'),
            Player_promo::TRANS_STATUS_FINISHED_WITHDRAW_CONDITION => lang('FINISHED'),
            Player_promo::TRANS_STATUS_DECLINED => lang('DECLINED')
        ];

        $data['datelimit_start'] = $datelimit_start = new DateTime();
        $datelimit_start->modify('-30 days');
        if (!empty($this->input->get('loosen'))) { $datelimit_start->modify('-10 years'); }
        $data['datelimit_end'] = $datelimit_end = new DateTime();
        $data['default_date'] = $default_date = new DateTime();

        $data['player_credit_mode'] = $this->player_model->isEnabledCreditMode($player_id);

        $this->loadTemplate();
        $this->template->append_function_title(lang('Account History'));
        $this->template->write_view('main_content', $this->utils->getPlayerCenterTemplate() . '/report/report', $data);
        $this->template->render();
    }

    public function uploadDepositReceiptImage(){
        $file1 = isset($_FILES['uploadDepositReceipt1']) ? $_FILES['uploadDepositReceipt1'] : null;
        $file2 = isset($_FILES['uploadDepositReceipt2']) ? $_FILES['uploadDepositReceipt2'] : null;

        $reference_id = $this->input->post('deposit_order_id');
        $playerId = $this->load->get_var('playerId');
        $success_url = $failed_url = site_url('player_center2/report');

        if(empty($playerId)){
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Empty').' '.lang('Player Id'));
            $this->utils->debug_log("uploadDepositReceiptImage error, empty player_id or deposit_order_id");
            return redirect($failed_url);
        }

        if(empty($reference_id)){
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('Empty').' '.lang('pay.sale_order_id'));
            $this->utils->debug_log("uploadDepositReceiptImage error, empty sale_order_id");
            return redirect($failed_url);
        }

        $this->load->model(array('sale_order'));
        $sale_order = $this->sale_order->getSaleOrderById($reference_id);
        if(empty($sale_order)){
            $this->alertMessage(self::MESSAGE_TYPE_ERROR, lang('lang.norecord'));
            $this->utils->debug_log("uploadDepositReceiptImage error, empty sale_order");
            return redirect($failed_url);
        }

        if(FALSE !== $upload_file1 = $this->doUploadDepositReceiptImage($file1,$reference_id,$playerId)){
            if(isset($upload_file1['status']) && ($upload_file1['status'] == 'error')){
                $this->alertMessage(self::MESSAGE_TYPE_ERROR, $upload_file1['msg']);
                $this->utils->debug_log("uploadDepositReceiptImage error, empty sale_order");
                return redirect($failed_url);
            }
        }

        if(FALSE !== $upload_file2 = $this->doUploadDepositReceiptImage($file2,$reference_id,$playerId)){
            if(isset($upload_file2['status']) && ($upload_file2['status'] == 'error')){
                $this->alertMessage(self::MESSAGE_TYPE_ERROR, $upload_file2['msg']);
                $this->utils->debug_log("uploadDepositReceiptImage error, empty sale_order");
                return redirect($failed_url);
            }
        }

        $this->alertMessage(self::MESSAGE_TYPE_SUCCESS, lang('Successfully uploaded.'));
        return redirect($success_url);
    }

    function doUploadDepositReceiptImage($file , $reference_id , $playerId){
        $response = FALSE;
        if(!empty($file)){
            $this->load->model(array('player_attached_proof_file_model'));
            $input = array(
                "player_id"       => $playerId,
                "tag"             => player_attached_proof_file_model::Deposit_Attached_Document,
                "sales_order_id"  => $reference_id,
            );

            $data = [
                'input' => $input,
                'image' => $file
            ];

            $response = $this->player_attached_proof_file_model->upload_deposit_receipt($data);
        }
        return $response;
    }

}