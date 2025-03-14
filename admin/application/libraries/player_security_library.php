<?php

/**
 * player_security_library.php
 *
 * @author Elvis Chen
 *
 * @property BaseController $CI
 * @property Registration_setting $registration_setting
 * @property kyc_status_model $kyc_status_model
 * @property player_attached_proof_file_model $player_attached_proof_file_model
 * @property Multiple_image_uploader $multiple_image_uploader
 * @property Authentication $authentication
 * @property player_kyc $player_kyc
 */
class Player_security_library {
    static $supportType = ['identify', 'address', 'income', 'transaction'];

    const KYC_TYPE_IDENTIFY = 'identify';
    const KYC_TYPE_ADDRESS = 'address';
    const KYC_TYPE_INCOME = 'income';
    const KYC_TYPE_TRANSACTION = 'transaction';

    const KYC_STATUS_NOT_VERIFIED = 0;
    const KYC_STATUS_VERIFIED = 1;
    const KYC_STATUS_NO_ATTACH = 2;
    const KYC_STATUS_WRONG_ATTACH = 3;

    protected $_player = null;

    public function __construct(){
        $this->CI =& get_instance();

        $this->CI->load->model(['registration_setting']);
        $this->CI->load->model(['player_model','player_attached_proof_file_model','kyc_status_model', 'player_kyc']);

        $this->CI->load->library(['multiple_image_uploader','authentication', 'player_library']);

        $this->registration_setting = $this->CI->registration_setting;
        $this->kyc_status_model = $this->CI->kyc_status_model;
        $this->player_attached_proof_file_model = $this->CI->player_attached_proof_file_model;

        $this->multiple_image_uploader = $this->CI->multiple_image_uploader;
        $this->authentication = $this->CI->{"authentication"};
        $this->player_kyc = $this->CI->{"player_kyc"};

        $this->_init();
    }

    protected function _init(){
        $this->_load_player();
    }

    protected function _load_player(){
        $player = $this->CI->load->get_var('player');

        if(!empty($player)){
            return FALSE;
        }
        return $this->setPlayer($player);
    }

    public function setPlayer($player){
        $this->_player = $player;

        return $this;
    }

    public function toTag($kycType) {
        switch($kycType) {
            case static::KYC_TYPE_IDENTIFY:
                return 'photo_id';
            break;
            case static::KYC_TYPE_ADDRESS:
                return 'address';
            break;
            case static::KYC_TYPE_INCOME:
                return 'income';
            break;
            case static::KYC_TYPE_TRANSACTION:
                return 'dep_wd';
            break;
        }
    }

    public function getKycSettings($kycType = null) {
        $settings = [];

        #region - identify
        $settings[static::KYC_TYPE_IDENTIFY]['enable'] = $this->CI->utils->isEnabledFeature('show_player_upload_realname_verification');
        $settings[static::KYC_TYPE_IDENTIFY]['document'] = $this->getDocumentOptions(static::KYC_TYPE_IDENTIFY);
        $settings[static::KYC_TYPE_IDENTIFY]['fields']['countryCode'] = 'hidden';
        $settings[static::KYC_TYPE_IDENTIFY]['fields']['realName'] = 'hidden';
        $settings[static::KYC_TYPE_IDENTIFY]['fields']['birthdayDay'] = 'hidden';
        $settings[static::KYC_TYPE_IDENTIFY]['fields']['idCardNumber'] = 'required';
        #endregion

        #region - address
        $settings[static::KYC_TYPE_ADDRESS]['enable'] = $this->CI->utils->isEnabledFeature('show_player_upload_proof_of_address');
        $settings[static::KYC_TYPE_ADDRESS]['document'] = $this->getDocumentOptions(static::KYC_TYPE_ADDRESS);
        $settings[static::KYC_TYPE_ADDRESS]['fields']['postalCode'] = 'hidden';
        $settings[static::KYC_TYPE_ADDRESS]['fields']['state'] = 'hidden';
        $settings[static::KYC_TYPE_ADDRESS]['fields']['city'] = 'hidden';
        $settings[static::KYC_TYPE_ADDRESS]['fields']['address1'] = 'hidden';
        $settings[static::KYC_TYPE_ADDRESS]['fields']['address2'] = 'hidden';
        #endregion

        #region - income
        $settings[static::KYC_TYPE_INCOME]['enable'] = $this->CI->utils->isEnabledFeature('show_player_upload_proof_of_income');
        $settings[static::KYC_TYPE_INCOME]['document'] = $this->getDocumentOptions(static::KYC_TYPE_INCOME);
        #endregion

        #region - transaction
        $settings[static::KYC_TYPE_TRANSACTION]['enable'] = $this->CI->utils->isEnabledFeature('show_player_upload_proof_of_deposit_withdrawal');
        $settings[static::KYC_TYPE_TRANSACTION]['document'] = $this->getDocumentOptions(static::KYC_TYPE_TRANSACTION);
        #endregion

        return (isset($settings[$kycType])) ? $settings[$kycType] : $settings;
    }

    public function getDocumentOptions($kycType) {
        if(!in_array($kycType, static::$supportType)) {
            return [];
        }
        
        if($kycType == static::KYC_TYPE_IDENTIFY){
            $pix_system_info = $this->CI->utils->getConfig('pix_system_info');
            if($pix_system_info['identify_cpf_numer_on_kyc']['enabled']){
                //if enabled cpf kyc , only return the CPF document type.
                return [
                    [
                        "type" => "cpfNumber",
                        "fileMinLimit" => 1,
                        "fileMaxLimit" => 1
                    ]
                ];                
            }
        }

        $options = $this->CI->utils->getConfig('kyc_doc_options');

        if(!isset($options[$kycType])) {
            return [];
        }

        if(is_array($options[$kycType])){
            foreach ($options[$kycType] as &$type) {
                if(!isset($type['fileMaxLimit'])){
                    $type['fileMaxLimit'] = $this->CI->utils->getConfig('kyc_limit_of_upload_attachment');
                }
            }
        }

        return $options[$kycType];
    }

    public function assign_common_vars(){
        $this->CI->load->vars([
            'player_verification' => $this->player_verification_info($this->_player['playerId']),
            'limit_of_upload_attachment' => $this->CI->utils->getConfig('kyc_limit_of_upload_attachment'),
            'showSMSField' => $this->CI->utils->isSMSEnabled(),
            'showEmailVerifyField' => $this->CI->utils->isEmailVerifyEnabled($this->_player['playerId']),
            'isPhoneVerified' => $this->_player['verified_phone'],
            'isEmailVerified' => $this->_player['verified_email'],
            'isEmailFilledIn' => $this->_player['email'],
            'secretQuestion' => $this->_player['secretQuestion'],
            'secretAnswer' => urldecode($this->_player['secretAnswer']),
            'withdraw_password' => $this->_player['withdraw_password'],
            'directChangePassword' => $this->CI->utils->checkPlayerCanDirectlyChangePassword()
        ]);
    }

    public function player_verification_info($player_id, $return_with_token = false) {
        $this->kyc_status_model->set_default_value_proof_filename($player_id);

        $count_proof_attachment = [
            'photo_id' => ['total' => 0, 'allowUpload' => BaseModel::TRUE],
            'address' => ['total' => 0, 'allowUpload' => BaseModel::TRUE],
            'income' => ['total' => 0, 'allowUpload' => BaseModel::TRUE],
            'dep_wd' => ['total' => 0, 'allowUpload' => BaseModel::TRUE],
        ];

        $imgInfo = $this->player_attached_proof_file_model->getAttachementRecordInfo($player_id);
        $img_file = array();
        $token = '';
        if(!empty($imgInfo)){
            foreach ($imgInfo as $key => $value) {
                $file_name = $this->CI->utils->getPlayerInternalUrl('player',PLAYER_INTERNAL_KYC_ATTACHMENT_PATH).$value['player_id'].'/'.$value['id'];

                if($return_with_token){
                    $token = $this->player_attached_proof_file_model->getApiToken($value['player_id'], $value['id']);
                    $file_name .= '/'.$token;
                }

                $img_file[] = array(
                    "file_name" => $file_name,
                    "tag" => $value['tag'],
                    "visible" => $value['visible_to_player']
                );

                $count_proof_attachment[$value['tag']]['total']++;
                if($count_proof_attachment[$value['tag']]['total'] >= $this->CI->utils->getConfig('kyc_limit_of_upload_attachment')){
                    $count_proof_attachment[$value['tag']]['allowUpload'] = BaseModel::FALSE;
                }
            }
        }

        $verified = $this->kyc_status_model->player_valid_documents($player_id);
        $verified_address = $this->kyc_status_model->player_valid_identity_and_proof_of_address($player_id);
        $verified_income = $this->kyc_status_model->player_valid_proof_of_income($player_id);
        $verified_deposit_withdrawal = $this->kyc_status_model->player_valid_proof_of_deposit_withdrawal($player_id);

        $data = array(
            "verified" => $verified,
            "verified_address" => $verified_address,
            "verified_income" => $verified_income,
            "verified_dep_wd" => $verified_deposit_withdrawal,
            "count_proof_attachment" => $count_proof_attachment,
            "img_file" => $img_file,
        );

        return $data;
    }

    function getPlayerKYCStatus($playerId)
    {
        $verification_data = $this->kyc_status_model->get_verification_info($playerId);

        $result = [
            static::KYC_TYPE_IDENTIFY => [
                'status' => static::KYC_STATUS_NO_ATTACH
            ],
            static::KYC_TYPE_ADDRESS => [
                'status' => static::KYC_STATUS_NO_ATTACH
            ],
            static::KYC_TYPE_INCOME => [
                'status' => static::KYC_STATUS_NO_ATTACH
            ],
            static::KYC_TYPE_TRANSACTION => [
                'status' => static::KYC_STATUS_NO_ATTACH
            ]
        ];
        foreach($verification_data as $tag => $remark_data) {
            switch($tag) {
                case BaseModel::Verification_Photo_ID:
                    $kycType = static::KYC_TYPE_IDENTIFY;
                break;
                case BaseModel::Verification_Adress:
                    $kycType = static::KYC_TYPE_ADDRESS;
                break;
                case BaseModel::Verification_Income:
                    $kycType = static::KYC_TYPE_INCOME;
                break;
                case BaseModel::Verification_Deposit_Withrawal:
                    $kycType = static::KYC_TYPE_TRANSACTION;
                break;
            }

            $status = static::KYC_STATUS_NOT_VERIFIED;

            if(isset($remark_data[BaseModel::Remark_Not_Verified])) {
                $data = $remark_data[BaseModel::Remark_Not_Verified];
                if($data['status'] || $data['auto_status']) {
                    $status = static::KYC_STATUS_NOT_VERIFIED;
                }
            }

            if(isset($remark_data[BaseModel::Remark_No_Attach])) {
                $data = $remark_data[BaseModel::Remark_No_Attach];
                if ($data['status'] || $data['auto_status']) {
                    $status = static::KYC_STATUS_NO_ATTACH;
                }
            }

            if(isset($remark_data[BaseModel::Remark_Wrong_attach])) {
                $data = $remark_data[BaseModel::Remark_Wrong_attach];
                if ($data['status'] || $data['auto_status']) {
                    $status = static::KYC_STATUS_WRONG_ATTACH;
                }
            }

            if(isset($remark_data[BaseModel::Remark_Verified])) {
                $data = $remark_data[BaseModel::Remark_Verified];
                if ($data['status'] || $data['auto_status']) {
                    $status = static::KYC_STATUS_VERIFIED;
                }
            }

            $result[$kycType]['status'] = $status;
        }

        return $result;
    }

    function isVerified($playerId, $kycType)
    {
        switch ($kycType) {
            case static::KYC_TYPE_IDENTIFY:
                return $this->kyc_status_model->player_valid_documents($playerId);
                break;
            case static::KYC_TYPE_ADDRESS:
                return $this->kyc_status_model->player_valid_identity_and_proof_of_address($playerId);
                break;
            case static::KYC_TYPE_INCOME:
                return $this->kyc_status_model->player_valid_proof_of_income($playerId);
                break;
            case static::KYC_TYPE_TRANSACTION:
                return $this->kyc_status_model->player_valid_proof_of_deposit_withdrawal($playerId);
                break;
        }

        return false;
    }

    function removeKycDocument($playerId, $kycType)
    {
        switch ($kycType) {
            case static::KYC_TYPE_IDENTIFY:
                $document_list = $this->player_attached_proof_file_model->getAttachementRecordInfo($playerId, null, BaseModel::Verification_Photo_ID);
                break;
            case static::KYC_TYPE_ADDRESS:
                $document_list = $this->player_attached_proof_file_model->getAttachementRecordInfo($playerId, null, BaseModel::Verification_Adress);
                break;
            case static::KYC_TYPE_INCOME:
                $document_list = $this->player_attached_proof_file_model->getAttachementRecordInfo($playerId, null, BaseModel::Verification_Income);
                break;
            case static::KYC_TYPE_TRANSACTION:
                $document_list = $this->player_attached_proof_file_model->getAttachementRecordInfo($playerId, null, BaseModel::Verification_Deposit_Withrawal);
                break;
        }

        if(empty($document_list)) {
            return true;
        }

        foreach($document_list as $document) {
            $receiptRecord = $document['file_name'];
            $file_path = $this->CI->utils->getPlayerInternalPath(PLAYER_INTERNAL_KYC_ATTACHMENT_PATH . $receiptRecord);

            if (!file_exists($file_path)) {
                $response = [
                    'status' => 'error',
                    'msg' => lang('image is not exist.'),
                    'msg_type' => BaseController::MESSAGE_TYPE_ERROR
                ];
                $this->CI->utils->debug_log('remove kyc player proof document file not exist', $response);
            } else {
                $deleted_file = unlink($file_path);
                if (!$deleted_file) {
                    $response = [
                        'status' => 'error',
                        'msg' => lang('Failed to remove image. Please try again.'),
                        'msg_type' => BaseController::MESSAGE_TYPE_ERROR
                    ];
                    $this->CI->utils->debug_log('remove kyc player proof document delete file failed', $response);
                }
            }

            $this->player_attached_proof_file_model->removeAttachementRecord([
                'id' => $document['id'],
                'playerId' => $document['player_id']
            ]);
        }

        return true;
    }

    function updateKycInfo($player_id, $tag, $context)
    {
        /** @var Player_library $player_library */
        $player_library = $this->CI->{"player_library"};

        $this->uploadResponseStatus($player_id, $tag, BaseModel::Remark_Not_Verified, null, $context);

        switch($tag) {
            case BaseModel::Verification_Photo_ID:
                $playerdetails['id_card_number'] = $context['idCardNumber'];
                $modifiedFields = $player_library->checkModifiedFields($player_id, $playerdetails);
                $player_library->editPlayerDetails($playerdetails, $player_id);
                $player_library->savePlayerUpdateLog($player_id, lang('lang.edit') . ' ' . lang('lang.playerinfo') . ' (' . $modifiedFields . ')', $this->authentication->getUsername());
            break;
            case BaseModel::Verification_Adress:
            break;
            case BaseModel::Verification_Income:
            break;
            case BaseModel::Verification_Deposit_Withrawal:
            break;
        }

        return true;
    }

    function allowUploadAttachment($player_id, $tag, $limit_upload_attachment = null){
        $result = FALSE;
        $player_attachment_record = $this->player_attached_proof_file_model->getAttachementRecordInfo($player_id, null, $tag);
        $limit_of_upload_attachment = (null === $limit_upload_attachment) ? $this->CI->utils->getConfig('kyc_limit_of_upload_attachment') : $limit_upload_attachment;
        if(count($player_attachment_record) >= $limit_of_upload_attachment){
            $result = array(
                'status' => 'error',
                'msg' => sprintf(lang('kyc_attachment.upload_file_max_up_to'),$limit_of_upload_attachment),
                'msg_type' => BaseController::MESSAGE_TYPE_ERROR
            );
            $this->CI->utils->debug_log($tag.' attached proof file is full, can\'t upload !', $result);
        }
        return $result;
    }

    function uploadResponseStatus($player_id, $tag, $remarks, $comments, $context = null){
        $data = [
            $tag => [
                $remarks => [
                    "status" => BaseController::TRUE,
                    "auto_status" => BaseController::FALSE,
                    "comments" => (!empty($comments)) ? $comments : null,
                    "context" => $context
                ]
            ],
        ];

        if ($this->CI->utils->getConfig('send_msg_to_remind_re_kyc')['add_date_after_verified']) {
            if ($tag == BaseModel::Verification_Photo_ID && $remarks == BaseModel::Remark_Verified) {
                $data[$tag][$remarks]['set_verified_date'] = date('Y-m-d H:i:s');
            }
        }

        if ($this->CI->utils->getConfig('blocked_account_over_kyc_fail_times')) {
            if ($tag == BaseModel::Verification_Photo_ID) {
                $total_kyc_fail_times = $this->check_blocked_account_over_kyc_fail_times($player_id, $remarks);
                $data[$tag][$remarks]['total_kyc_fail_times'] = $total_kyc_fail_times;
            }
        }

        $this->kyc_status_model->update_verification_data($player_id,$data);
        $response = array(
            'status' => 'success',
            'msg' => lang('save.success'),
            'msg_type' => BaseController::MESSAGE_TYPE_SUCCESS
        );

        return $response;
    }

    public function check_blocked_account_over_kyc_fail_times($player_id, $remarks) {
        $total_kyc_fail_times = 0;
        $origin_kyc_status = BaseModel::Remark_No_Attach;
        $max_times = $this->CI->utils->getConfig('blocked_account_over_kyc_fail_times');
        $verification_data = $this->kyc_status_model->get_verification_info($player_id);
        $player_id_data = isset($verification_data[BaseModel::Verification_Photo_ID]) ? $verification_data[BaseModel::Verification_Photo_ID] : '';

        if (!empty($player_id_data)) {
            foreach ($player_id_data as $key => $value) {
                $origin_kyc_status = $key;
                if (isset($value['total_kyc_fail_times'])) {
                    $total_kyc_fail_times = $value['total_kyc_fail_times'];
                }
            }
        }

        if ($remarks == BaseModel::Remark_Wrong_attach && $origin_kyc_status != BaseModel::Remark_Wrong_attach) {
            $total_kyc_fail_times += 1;
            if ($total_kyc_fail_times >= $max_times) {
                $rlt = $this->CI->player_model->blockPlayerById($player_id);
                if ($rlt) {
                    $total_kyc_fail_times = 0;
                    $this->CI->utils->debug_log('Over max kyc fail times, blocked player', ['player_id' => $player_id , 'success' => $rlt]);
                }
            }
        }
        return $total_kyc_fail_times;
    }

    public function request_upload_realname_verification($player_id, $tag = BaseModel::Verification_Photo_ID, $image, $remarks = null, $comments = null) {
        $response = array();
        if(!empty($remarks) || !empty($comments)) {
            $response = $this->uploadResponseStatus($player_id, $tag, $remarks, $comments);
        }

        if(!empty($image['name'][0]) && !empty($image['tmp_name'][0])){
            if(FALSE !== $response = $this->allowUploadAttachment($player_id, $tag)){
                return $response;
            }

            $path = $this->CI->utils->getPlayerInternalPath(PLAYER_INTERNAL_KYC_ATTACHMENT_PATH);
            $config = $this->CI->utils->getUploadConfig($path);

            $uploadResponse = $this->multiple_image_uploader->do_multiple_uploads($image,$path,$config);

            $this->CI->utils->debug_log('Response after upload realname verification', $uploadResponse);
            if($uploadResponse['status'] == "success") {
                foreach ($uploadResponse['filename'] as $key => $value) {
                    $data = [
                        'player_id' => $player_id,
                        'admin_user_id' => ($this->authentication->getUserId()) ? $this->authentication->getUserId() : 0,
                        'file_name' => $value,
                        'tag' => $tag,
                        'created_at' => $this->CI->utils->getCurrentDatetime()
                    ];

                    $insert_id = $this->player_attached_proof_file_model->addAttachementRecord($data);
                }

                if($insert_id){
                    $response = array(
                        'status' => 'success',
                        'msg' => lang('Successfully uploaded.'),
                        'msg_type' => BaseController::MESSAGE_TYPE_SUCCESS
                    );
                }else{
                    $response = array(
                        'status' => 'error',
                        'msg' => lang('error.default.db.message'),
                        'msg_type' => BaseController::MESSAGE_TYPE_ERROR
                    );
                }

            } else {
                $response = array(
                    'status' => 'error',
                    'msg' => $uploadResponse['message'],
                    'msg_type' => BaseController::MESSAGE_TYPE_ERROR
                );
            }
        }

        $this->CI->utils->debug_log('upload realname verification done', $response);
        return $response;
    }

    public function request_upload_address($player_id, $tag = BaseModel::Verification_Adress, $image, $remarks = null, $comments = null) {
        $response = array();
        if(!empty($remarks) || !empty($comments)) {
            $response = $this->uploadResponseStatus($player_id, $tag, $remarks, $comments);
        }

        if(!empty($image['name'][0]) && !empty($image['tmp_name'][0])){
            if(FALSE !== $response = $this->allowUploadAttachment($player_id, $tag)){
                return $response;
            }

            $path = $this->CI->utils->getPlayerInternalPath(PLAYER_INTERNAL_KYC_ATTACHMENT_PATH);
            $config = $this->CI->utils->getUploadConfig($path);

            $uploadResponse = $this->multiple_image_uploader->do_multiple_uploads($image,$path,$config);

            $this->CI->utils->debug_log('Response after upload address', $uploadResponse);
            if($uploadResponse['status'] == "success") {
                foreach ($uploadResponse['filename'] as $key => $value) {
                    $data = [
                        'player_id' => $player_id,
                        'admin_user_id' => ($this->authentication->getUserId()) ? $this->authentication->getUserId() : 0,
                        'file_name' => $value,
                        'tag' => $tag,
                        'created_at' => $this->CI->utils->getCurrentDatetime()
                    ];

                    $insert_id = $this->player_attached_proof_file_model->addAttachementRecord($data);
                }

                if($insert_id){
                    $response = array(
                        'status' => 'success',
                        'msg' => lang('Successfully uploaded.'),
                        'msg_type' => BaseController::MESSAGE_TYPE_SUCCESS
                    );
                }else{
                    $response = array(
                        'status' => 'error',
                        'msg' => lang('error.default.db.message'),
                        'msg_type' => BaseController::MESSAGE_TYPE_ERROR
                    );
                }

            } else {
                $response = array(
                    'status' => 'error',
                    'msg' => $uploadResponse['message'],
                    'msg_type' => BaseController::MESSAGE_TYPE_ERROR
                );
            }
        }

        $this->CI->utils->debug_log('upload address done', $response);
        return $response;
    }

    public function request_upload_deposit_withdrawal($player_id, $tag = BaseModel::Verification_Deposit_Withrawal, $image, $remarks = null, $comments = null) {
        $response = array();
        if(!empty($remarks) || !empty($comments)) {
            $response = $this->uploadResponseStatus($player_id, $tag, $remarks, $comments);
        }

        if(!empty($image['name'][0]) && !empty($image['tmp_name'][0])){
            if(FALSE !== $response = $this->allowUploadAttachment($player_id, $tag)){
                return $response;
            }

            $path = $this->CI->utils->getPlayerInternalPath(PLAYER_INTERNAL_KYC_ATTACHMENT_PATH);
            $config = $this->CI->utils->getUploadConfig($path);

            $uploadResponse = $this->multiple_image_uploader->do_multiple_uploads($image,$path,$config);

            $this->CI->utils->debug_log('Response after upload deposit withdrawal', $uploadResponse);
            if($uploadResponse['status'] == "success") {
                foreach ($uploadResponse['filename'] as $key => $value) {
                    $data = [
                        'player_id' => $player_id,
                        'admin_user_id' => ($this->authentication->getUserId()) ? $this->authentication->getUserId() : 0,
                        'file_name' => $value,
                        'tag' => $tag,
                        'created_at' => $this->CI->utils->getCurrentDatetime()
                    ];

                    $insert_id = $this->player_attached_proof_file_model->addAttachementRecord($data);
                }

                if($insert_id){
                    $response = array(
                        'status' => 'success',
                        'msg' => lang('Successfully uploaded.'),
                        'msg_type' => BaseController::MESSAGE_TYPE_SUCCESS
                    );
                }else{
                    $response = array(
                        'status' => 'error',
                        'msg' => lang('error.default.db.message'),
                        'msg_type' => BaseController::MESSAGE_TYPE_ERROR
                    );
                }

            } else {
                $response = array(
                    'status' => 'error',
                    'msg' => $uploadResponse['message'],
                    'msg_type' => BaseController::MESSAGE_TYPE_ERROR
                );
            }
        }

        $this->CI->utils->debug_log('upload deposit withdrawal done', $response);
        return $response;
    }

    public function request_upload_income($player_id, $tag = BaseModel::Verification_Income, $image, $remarks = null, $comments = null) {
        $response = array();
        if(!empty($remarks) || !empty($comments)) {
            $response = $this->uploadResponseStatus($player_id, $tag, $remarks, $comments);
        }

        if(!empty($image['name'][0]) && !empty($image['tmp_name'][0])){
            if(FALSE !== $response = $this->allowUploadAttachment($player_id, $tag)){
                return $response;
            }

            $path = $this->CI->utils->getPlayerInternalPath(PLAYER_INTERNAL_KYC_ATTACHMENT_PATH);
            $config = $this->CI->utils->getUploadConfig($path);

            $uploadResponse = $this->multiple_image_uploader->do_multiple_uploads($image,$path,$config);

            $this->CI->utils->debug_log('Response after upload income', $uploadResponse);
            if($uploadResponse['status'] == "success") {
                foreach ($uploadResponse['filename'] as $key => $value) {
                    $data = [
                        'player_id' => $player_id,
                        'admin_user_id' => ($this->authentication->getUserId()) ? $this->authentication->getUserId() : 0,
                        'file_name' => $value,
                        'tag' => $tag,
                        'created_at' => $this->CI->utils->getCurrentDatetime()
                    ];

                    $insert_id = $this->player_attached_proof_file_model->addAttachementRecord($data);
                }

                if($insert_id){
                    $response = array(
                        'status' => 'success',
                        'msg' => lang('Successfully uploaded.'),
                        'msg_type' => BaseController::MESSAGE_TYPE_SUCCESS
                    );
                }else{
                    $response = array(
                        'status' => 'error',
                        'msg' => lang('error.default.db.message'),
                        'msg_type' => BaseController::MESSAGE_TYPE_ERROR
                    );
                }

            } else {
                $response = array(
                    'status' => 'error',
                    'msg' => $uploadResponse['message'],
                    'msg_type' => BaseController::MESSAGE_TYPE_ERROR
                );
            }
        }

        $this->CI->utils->debug_log('upload income done', $response);
        return $response;
    }

    public function remove_kyc_player_proof_document($data){
        $response = array();
        if(!empty($data)){
            $imgInfo = $this->player_attached_proof_file_model->getAttachementRecordInfo($data['playerId'], $file_name = null, $tag = null, $data['picId'],true);
            if(empty($imgInfo)){
                $response = [
                    'status' => 'error',
                    'msg' => lang('imageInfo is not exist.'),
                    'msg_type' => BaseController::MESSAGE_TYPE_ERROR
                ];
                $this->CI->utils->debug_log('remove kyc player proof document file not exist record',$response);
                return $response;
            }

            $receiptRecord = $imgInfo['file_name'];
            $file_path = $this->CI->utils->getPlayerInternalPath(PLAYER_INTERNAL_KYC_ATTACHMENT_PATH.$receiptRecord);

            if(!file_exists($file_path)){
                $response = [
                    'status' => 'error',
                    'msg' => lang('image is not exist.'),
                    'msg_type' => BaseController::MESSAGE_TYPE_ERROR
                ];
                $this->CI->utils->debug_log('remove kyc player proof document file not exist',$response);
                return $response;
            }

            $deleted_file = unlink($file_path);
            if(!$deleted_file){
                $response = [
                    'status' => 'error',
                    'msg' => lang('Failed to remove image. Please try again.'),
                    'msg_type' => BaseController::MESSAGE_TYPE_ERROR
                ];
                $this->CI->utils->debug_log('remove kyc player proof document delete file failed',$response);
                return $response;
            }

            $this->player_attached_proof_file_model->removeAttachementRecord($data);
            $response = array(
                'status' => 'success',
                'msg' => lang('Image successfully deleted!'),
                'msg_type' => BaseController::MESSAGE_TYPE_SUCCESS
            );
        }
        return $response;
    }

    public function get_player_kyc_real_name_image_list($playerId){
        $player_verification = $this->player_verification_info($playerId, true);

        $list = [];
        if (!isset($player_verification['img_file'])) {
            return $list;
        }

        foreach ($player_verification['img_file'] as $key => $val) {
            if (($val['tag'] == BaseModel::Verification_Photo_ID) && $val['visible']) {
                $list[] = $val['file_name'];
            }
        }

        return $list;
    }
}