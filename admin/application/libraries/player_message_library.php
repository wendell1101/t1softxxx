<?php
/**
 * player_message_library.php
 *
 * @author Elvis Chen
 *
 * @property BaseController $CI
 * @property Internal_message $internal_message
 * @property CI_Form_validation $form_validation
 */
class Player_message_library {
    const VALIDATION_TYPE_ADD = 'add';
    const VALIDATION_TYPE_REPLY = 'reply';

    const CODE_MESSAGE_ID_INVALID           = 0x301;
    const CODE_MESSAGE_DELETED              = 0x302;
    const CODE_MESSAGE_CLOSED               = 0x303;
    const CODE_MESSAGE_BODY_TOO_LONG        = 0x304;
    const CODE_ERROR_SENDING_REPLY          = 0x305;
    const CODE_ERROR_UPDATING_MESG_STATUS   = 0x306;
    const CODE_ERROR_CREATING_NEW_MESG      = 0x307;
    const CODE_MESSAGE_DISABLED_REPLY       = 49025;

    /* @var BaseController */
    public $CI;

    protected $_player_center_title = NULL;
    protected $_system_feature_hidden_msg_sender_from_sysadmin = FALSE;
    protected $_request_default_guest_name = NULL;

    public function __construct(){
        $this->CI =& get_instance();

        $this->CI->load->model(['internal_message']);
        $this->CI->load->library(['form_validation']);

        $this->internal_message = $this->CI->internal_message;
        $this->form_validation = $this->CI->form_validation;

        $this->_player_center_title = $this->CI->utils->getPlayertitle();
        $this->_system_feature_hidden_msg_sender_from_sysadmin = $this->CI->utils->isEnabledFeature('hidden_msg_sender_from_sysadmin');
        $this->_request_default_guest_name = lang('message.request_form.default_guest_name');
    }

    public function getDefaultAdminSenderName(){
        $value = $this->CI->operatorglobalsettings->getSettingValue('default_message_admin_sender_name');

        return (empty($value)) ? $this->_player_center_title : $value;
    }

    public function getDefaultAdminSendMessageLengthLimit(){
        $value = (int)$this->CI->operatorglobalsettings->getSettingIntValue('admin_send_message_length_limit');
        return (empty($value)) ? 0 : $value;
    }

    public function getDefaultPlayerSendMessageLengthLimit(){
        $value = (int)$this->CI->operatorglobalsettings->getSettingIntValue('player_send_message_length_limit');

        return (empty($value)) ? 0 : $value;
    }

    public function getPlayerAllMessages($player_id, $filter){
        $filter .= 'c.deleted = 0';
        return $this->internal_message->getMessages($player_id, null, null, $filter);
    }

    public function getPlayerLastMessages($player_id, $limit = null, $offset = null, $filter = '', $sort = 'DESC' ){
        $filter .= 'c.deleted = 0';
        return $this->internal_message->getMessages($player_id, $limit, $offset, $filter, $sort);
    }

    public function getPlayerUnreadMessages($player_id){
        return $this->internal_message->playerUnreadMessages($player_id);
    }

    public function getAllBroadcastMessages($broadcast_id = null, $broadcast_id_list = [], $player_registr_date = null){
        return $this->internal_message->getBroadcastMessages($broadcast_id, $broadcast_id_list, $player_registr_date);
    }

    public function addNewMessageFromBroadcast($broadcast_id, $username){
        $message_id = $this->internal_message->addNewMessageFromBroadcast($broadcast_id, $username);
        $this->CI->utils->debug_log(__METHOD__, 'message_id', $message_id, 'broadcast_id', $broadcast_id, $username);
        return $message_id;
    }

    public function getPlayerbroadcastId($player_id){
        $broadcast_id_list = $this->internal_message->getPlayerbroadcastId($player_id);
        $this->CI->utils->debug_log(__METHOD__, 'broadcast_id_list', $broadcast_id_list);
        return $broadcast_id_list;
    }

    public function getPlayerAllBroadcastMessages($player_id, $player_registr_date){
        $broadcast_id_list = $this->getPlayerbroadcastId($player_id);
        $broadcast_messages = $this->getAllBroadcastMessages(NULL ,$broadcast_id_list, $player_registr_date);
        $this->CI->utils->printLastSQL();
        $this->CI->utils->debug_log(__METHOD__, 'broadcast_messages', $broadcast_messages);
        return $broadcast_messages;
    }

    public function run_validation($type){
        switch($type){
            case self::VALIDATION_TYPE_REPLY:
                $this->form_validation->set_rules('message', lang('cs.messagedetail'), 'trim|required|xss_clean');
                break;
            case self::VALIDATION_TYPE_ADD:
            default:
                $this->form_validation->set_rules('subject', lang('cs.subject'), 'trim|required|xss_clean');
                $this->form_validation->set_rules('message', lang('cs.messagedetail'), 'trim|required|xss_clean');
        }

        return $this->form_validation->run();
    }

    public function addMessage($player_id, $player_name, $subject, $message, $save_to_drafts = FALSE){
        $result = [
            'status' => FALSE,
            'message' => NULL,
            'code'  => -1
        ];

        if(!$this->checkMessageLength($message, $this->getDefaultPlayerSendMessageLengthLimit())){
            $result['status'] = FALSE;
            $result['message'] = sprintf(lang('form.validation.max_length'), lang('cs.messagedetail'), $this->getDefaultPlayerSendMessageLengthLimit());
            $result['code'] = self::CODE_MESSAGE_BODY_TOO_LONG;
            return $result;
        }
        $add_result = $this->internal_message->addNewMessage($player_id, $subject, $player_name, $message, $save_to_drafts);
        $result['status'] = $add_result;
        $result['code'] = $add_result ? 0 : self::CODE_ERROR_CREATING_NEW_MESG;

        return $result;
    }

    public function addRequestForm($player_id = NULL, $player_name, $real_name = NULL, $useranme = NULL, $contact_number = NULL, $email = NULL){
        $field_name_first_name = lang('First Name');
        $field_name_username = lang('Username');
        $field_name_contact_number = lang('Contact Number');
        $field_name_email = lang('Email Address');

        $message = <<<EOD
${field_name_first_name}: ${real_name}<br />
${field_name_username}: ${useranme}<br />
${field_name_contact_number}: ${contact_number}<br />
${field_name_email}: ${email}
EOD;

        $result = [
            'status' => FALSE,
            'message' => NULL
        ];

        $result['status'] = $this->internal_message->addRequestFormMessage($player_id, $player_name, $message);

        return $result;
    }

    protected function _process_message_entry($message){
        if($message['flag'] == 'admin'){
            $message['sender'] = $message['adminUsername'];
            $message['sender'] = (!empty($message['admin_custom_name'])) ? $message['admin_custom_name'] : $message['sender'];
            if(!$this->CI->utils->isAdminSubProject()){
                $message['sender'] = ($this->_system_feature_hidden_msg_sender_from_sysadmin) ? $this->getDefaultAdminSenderName() : $message['sender'];
            }
            if($message['message_type'] == Internal_message::MESSAGE_TYPE_REQUEST_FORM){
                $message['recipient'] = (empty($message['playerId'])) ? $this->_request_default_guest_name : $message['playerUsername'];
            }else{
                $message['recipient'] = $message['playerUsername'];
            }
        }else{
            if($message['message_type'] == Internal_message::MESSAGE_TYPE_REQUEST_FORM){
                $message['sender'] = (empty($message['playerId'])) ? $this->_request_default_guest_name : $message['playerUsername'];
            }else{
                $message['sender'] = $message['playerUsername'];
            }
            $message['recipient'] = $message['adminUsername'];
            $message['recipient'] = (!empty($message['admin_custom_name'])) ? $message['admin_custom_name'] : $message['recipient'];
            if(!$this->CI->utils->isAdminSubProject()){
                $message['recipient'] = ($this->_system_feature_hidden_msg_sender_from_sysadmin) ? $this->getDefaultAdminSenderName() : $message['recipient'];
            }
        }
        $message['detail'] = stripslashes(htmlspecialchars_decode($message['detail']));

        return $message;
    }

    public function getMessageById($message_id, $player_id = NULL, $broadcast_id = NULL){
        $this->CI->utils->debug_log(__METHOD__, 'message_id', $message_id, 'broadcast_id', $broadcast_id);
        $messages = $this->internal_message->getMessageById($message_id, $player_id);

        if ($message_id == 'null' && !empty($broadcast_id)) {
            $messages = $this->internal_message->getBroadcastMessageById($broadcast_id, $player_id);
        }

        if(empty($messages)){
            return FALSE;
        }

        foreach($messages as $key => $entry){
            $messages[$key] = $this->_process_message_entry($entry);
        }

        $this->CI->utils->debug_log(__METHOD__, 'messages', $messages);

        $topic = $messages[0];

        $results = [];
        $results['topic'] = $topic;
        $results['messages'] = $messages;
        $results['flags'] = [
            'is_new' => $topic['status'] == Internal_message::STATUS_NEW,
            'is_admin_new' => $topic['status'] == Internal_message::STATUS_ADMIN_NEW,
            'is_disabled' => $topic['status'] == Internal_message::STATUS_DISABLED,
            'is_disabled_reply' => $topic['status'] == Internal_message::STATUS_DISABLED || !!$topic['disabled_replay'],
            'is_deleted' => !!$topic['deleted'],
            'is_system_message' => !!$topic['is_system_message'],
        ];

        return $results;
    }

    public function getMessageByIdForPlayer($player_id, $message_id, $broadcast_id = null){
        $data = $this->getMessageById($message_id, $player_id, $broadcast_id);

        if(empty($data)){
            return FALSE;
        }

        $this->updateUnreadMessageReadtime($message_id);

        if(!$data['flags']['is_new'] && !$data['flags']['is_disabled'] && !$data['flags']['is_deleted']){
            $this->updateMessageStatus($message_id, 'admin', Internal_message::STATUS_READ, Internal_message::MESSAGE_DETAILS_READ); //update message as read
        }else{
            $this->updateMessageStatus($message_id, 'admin', NULL, Internal_message::MESSAGE_DETAILS_READ); //update message as read
        }

        $total_unreads = $this->internal_message->countTotalUnreadByMessageId($message_id);

        $this->internal_message->updateMessages([
            'admin_unread_count' => $total_unreads['admin'],
            'player_unread_count' => $total_unreads['player'],
        ], $message_id);

        return $data;
    }

    public function updateUnreadMessageReadtime($message_id){
        $unreads = $this->internal_message->countTotalUnreadByMessageId($message_id);
        if ($unreads['admin'] > 0) {
            $this->internal_message->updateUnreadMessageReadtime($message_id, 'admin');
        }
    }

    public function getMessageByIdForAdmin($message_id){
        $data = $this->getMessageById($message_id);

        if(empty($data)){
            return FALSE;
        }

        $this->updateMessageStatus($message_id, 'player', NULL, Internal_message::MESSAGE_DETAILS_READ); //update message as read

        $total_unreads = $this->internal_message->countTotalUnreadByMessageId($message_id);

        $this->internal_message->updateMessages([
            'admin_unread_count' => $total_unreads['admin'],
            'player_unread_count' => $total_unreads['player'],
        ], $message_id);

        return $data;
    }

    public function replyMessage($message_id, $player_name, $message){
        $result = [
            'status'    => FALSE,
            'message'   => NULL,
            'code'      => -1 ,
            'details'   => null,
        ];

        $data = $this->getMessageByIdForAdmin($message_id);

        if(empty($data)){
            $result['message'] = lang('error.default.db.message');
            $result['code'] = self::CODE_MESSAGE_ID_INVALID;
            return $result;
        }

        if($data['flags']['is_deleted']){
            $result['message'] = lang('mess.20');
            $result['code'] = self::CODE_MESSAGE_DELETED;
            return $result;
        }

        if($data['flags']['is_disabled_reply']){
            $result['message'] = lang('mess.20');
            $result['code'] = self::CODE_MESSAGE_DISABLED_REPLY;
            return $result;
        }

        if(!$this->checkMessageLength($message, $this->getDefaultPlayerSendMessageLengthLimit())){
            $result['message'] = sprintf(lang('form.validation.max_length'), lang('cs.messagedetail'), $this->getDefaultPlayerSendMessageLengthLimit());
            $result['code'] = self::CODE_MESSAGE_BODY_TOO_LONG;
            $result['details'] = [ 'max_length' => $this->getDefaultPlayerSendMessageLengthLimit() ];
            return $result;
        }

        $this->internal_message->startTrans();

        $message_details_id = $this->internal_message->addNewMessageDetail($message_id, $player_name, $message);

        if(empty($message_details_id)){
            $result['message'] = lang('error.default.db.message');
            $result['code'] = self::CODE_ERROR_SENDING_REPLY;
            return $result;
        }

        if(!$data['flags']['is_new']){
            $this->updateMessageStatus($message_id, 'admin', Internal_message::STATUS_UNPROCESSED, Internal_message::MESSAGE_DETAILS_READ); //update message as read
        }

        $total_unreads = $this->internal_message->countTotalUnreadByMessageId($message_id);

        $this->internal_message->updateMessages([
            'player_last_reply_dt' => $this->CI->utils->getNowForMysql(),
            'admin_unread_count' => $total_unreads['admin'],
            'player_unread_count' => $total_unreads['player'],
        ], $message_id);

        if(!$this->internal_message->endTransWithSucc()){
            $result['message'] = lang('error.default.db.message');
            $result['code'] = self::CODE_ERROR_UPDATING_MESG_STATUS;
            return $result;
        }

        $result['status'] = TRUE;
        $result['message_details_id'] = $message_details_id;

        return $result;
    }

    public function replyMessageWithAdmin($message_id, $adminId, $username, $message){
        $result = [
            'status' => FALSE,
            'message' => NULL
        ];

        $data = $this->getMessageByIdForAdmin($message_id);

        if(empty($data)){
            $result['message'] = lang('error.default.db.message');

            return $result;
        }

        $this->internal_message->startTrans();

        $message_details_id = $this->internal_message->addNewMessageDetailWithAdmin($message_id, $adminId, $username, $message);

        if(empty($message_details_id)){
            $this->internal_message->rollbackTrans();
            return FALSE;
        }

        if(!$data['flags']['is_disabled'] && !$data['flags']['is_deleted']){
            $this->updateMessageStatus($message_id, 'player', Internal_message::STATUS_PROCESSED, Internal_message::MESSAGE_DETAILS_READ); //update message as read
        }

        $total_unreads = $this->internal_message->countTotalUnreadByMessageId($message_id);

        $this->internal_message->updateMessages([
            'admin_last_reply_id' => $adminId,
            'admin_last_reply_dt' => $this->CI->utils->getNowForMysql(),
            'admin_unread_count' => $total_unreads['admin'],
            'player_unread_count' => $total_unreads['player'],
        ], $message_id);

        if(!$this->internal_message->endTransWithSucc()){
            $result['message'] = lang('error.default.db.message');

            return $result;
        }

        $result['status'] = TRUE;

        return $result;
    }

    public function updateMessageStatus($message_id, $flag, $status, $detail_status, $player_id = NULL){
        return $this->internal_message->updateMessageStatus($message_id, $flag, $status, $detail_status, $player_id);
    }

    public function checkMessageLength($content, $limit_length){
        // $content = filter_var($content, FILTER_SANITIZE_STRING);
        $strlen = mb_strlen(strip_tags(html_entity_decode($content)));

        if($limit_length!=0 && empty($limit_length)){
            return TRUE;
        }

        return ($strlen > 0 && $strlen <= $limit_length);
    }

    public function getRequestFormSettings(){
        $request_form_settings = $this->CI->operatorglobalsettings->getPlayerMessageRequestFormSettings();
        $request_form_settings['request_form_url'] = $this->CI->utils->getSystemUrl("player", '/api/submit_request_form');

        $request_form_rules = $this->CI->config->item('player_validator');

        $usernameRegDetails = [];
        $request_form_rules['username']['regex'] = $this->CI->utils->getUsernameRegForJS($usernameRegDetails);
        $contact_number_regex = $this->CI->utils->getConfig('register_mobile_number_regex');
        if(isset($request_form_rules['contact_number']) && !empty($contact_number_regex)){
            $request_form_rules['contact_number']['regex'] = $contact_number_regex;
        }

        $request_form_settings['request_form_rules'] = $request_form_rules;

        return $request_form_settings;
    }

}