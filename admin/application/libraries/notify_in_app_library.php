<?php
/**
 * Notify_in_app_library
 *
 * Cloned from player_message_library.php
 *
 * @property BaseController $CI
 */
class Notify_in_app_library {

    /* @var BaseController */
    public $CI;

    public function __construct(){
        $this->CI =& get_instance();

    }

    public function do_notify_send($player_id, $source_method, $mapping_id = null){

        $player_id_list = [];
        if( strpos($player_id, '_') !== false){
            $player_id_list = array_filter(explode('_', $player_id));
        }else{
            // only one
            $player_id_list[0] = $player_id;
        }

        if( ! empty( $player_id_list ) ){
            $totals = count( $player_id_list);

            foreach($player_id_list as $index_number => $player_id){
                $unreadMessageAmmount = $this->getUnreadMessagesByPlayerId($player_id);
                if( ! empty($unreadMessageAmmount) ){
                    $this->do_notify_send_one($player_id, $source_method, $mapping_id);
                    $this->CI->utils->debug_log('notify_in_app_library.do_notify_send_one.index:', $index_number, 'totals:', $totals, 'unreadMessageAmmount:', $unreadMessageAmmount );
                }else{
                    $this->CI->utils->debug_log('notify_in_app_library.do_notify_send_one.index:', $index_number, 'totals:', $totals, 'Ignore by No unread message.' );
                }
            }
        }
    } // EOF do_notify_send_cmd

    public function do_notify_send_one($player_id, $source_method, $mapping_id = null)  {
        $this->CI->load->library(["lib_queue", "notify_in_app/notify_sender"]);
        $this->CI->load->model('fcm_model', 'fcm');

        $notifyContent = [];
        $notifyContent['notify_data'] = $this->getNotifyDataByMethodListInMobiPush($player_id, $source_method, $mapping_id);
        $notifyContent['mode'] = $this->getModeByMethodListInMobiPush($source_method);
        $notifyContent['player_id'] = $player_id;

        $fcm_row = $this->CI->fcm->getExistIdByPlayerId($player_id);
        if( ! empty($fcm_row) ){
            $notification_token = $fcm_row['notification_token'];
            $recipientNotifyToken = $notification_token;
        }
        if(! empty($recipientNotifyToken) ){
            $useNotifyApiName = null;
            $_isSuccess = null; // for collect
            $_lastError = null; // for collect
            $isSuccess = $this->CI->notify_sender->send($recipientNotifyToken, $notifyContent, $useNotifyApiName, $_isSuccess, $_lastError );
        }

    }

    public function getNotifyDataByMethodListInMobiPush($player_id, $source_method, $mapping_id = null){
        $notifyData = [];
        switch(true){
            case $this->isSendNoticeMethodInMobiPush($source_method):
                $notifyData = $this->getNotifyDataByMethodListInMobiPush4sendNotification($player_id, $mapping_id);
            break;
            case $this->isNoticeUnreadMethodInMobiPush($source_method):
                $notifyData = $this->getNotifyDataByMethodListInMobiPush4noticeUnread($player_id);
            break;
        }
        $this->CI->utils->debug_log('OGP-27618.getNotifyDataByMethodListInMobiPush.source_method:', $source_method, 'notifyData:', $notifyData );
        return $notifyData;
    } // EOF getNotifyDataByMethodListInMobiPush()
    public function getNotifyDataByMethodListInMobiPush4sendNotification($player_id, $mapping_id = null){
        $this->CI->load->model(['Internal_message']);
        $message = [];
        $rows = $this->CI->Internal_message->getMessageById($mapping_id);
        if( !empty($rows)){
            $message = $rows[0];
        }

        $notify_data = [];
        $notify_data['title'] = 'Notification Header';
        $notify_data['body'] = 'Notification Message';

        if( ! empty($message['subject']) ){
            $notify_data['title'] = $message['subject'];
        }
        if( ! empty($message['detail']) ){
            $_detail = $message['detail'];
            $_detail = strip_tags($_detail);
            $_detail = html_entity_decode($_detail);
            $notify_data['body'] = strip_tags($_detail);
        }

        return $notify_data;
    } // EOF getNotifyDataByMethodListInMobiPush4sendNotification()
    public function getNotifyDataByMethodListInMobiPush4noticeUnread($player_id){

        $badge = $this->getUnreadMessagesByPlayerId($player_id);

        $notify_data = [];
        $notify_data['inAppNotificationMessage'] = '';
        $notify_data['badge'] = $badge;
        return $notify_data;
    } // EOF getNotifyDataByMethodListInMobiPush4noticeUnread()
    //
    public function getModeByMethodListInMobiPush($source_method){
        $this->CI->load->library(["notify_in_app/notify_sender", "notify_in_app/notify_api_mobi_push"]);
        $_notifyApi = $this->CI->notify_sender->load_notify_in_app();
        $_mode = null; // default
        if($_notifyApi instanceof Notify_api_mobi_push){
            switch(true){
                case $this->isSendNoticeMethodInMobiPush($source_method):
                    $_mode = Notify_api_mobi_push::MODE_IN_SEND_NOTIFICATION;
                break;
                case $this->isNoticeUnreadMethodInMobiPush($source_method):
                    $_mode = Notify_api_mobi_push::MODE_IN_NOTICE_UNREAD_MESSAGE;
                break;
            }
        }

        return $_mode;
    } // EOF getModeByMethodListInMobiPush

    public function isSendNoticeMethodInMobiPush($source_method){
        $this->CI->load->library(["notify_in_app/notify_sender", "notify_in_app/notify_api_mobi_push"]);
        $_notifyApi = $this->CI->notify_sender->load_notify_in_app();
        $isSendNoticeMethod = false;
        if($_notifyApi instanceof Notify_api_mobi_push){
            $_method_list = $this->CI->utils->getConfig('notify_api_mobi_push_send_notice_method_list');
            $isSendNoticeMethod = $this->CI->utils->_getIsEnableWithMethodAndList($source_method, $_method_list);
        }
        return $isSendNoticeMethod;
    }
    public function isNoticeUnreadMethodInMobiPush($source_method){
        $this->CI->load->library(["notify_in_app/notify_sender", "notify_in_app/notify_api_mobi_push"]);
        $_notifyApi = $this->CI->notify_sender->load_notify_in_app();
        $isSendNoticeMethod = false;
        if($_notifyApi instanceof Notify_api_mobi_push){
            $_method_list = $this->CI->utils->getConfig('notify_api_mobi_push_notice_unread_method_list');
            $isSendNoticeMethod = $this->CI->utils->_getIsEnableWithMethodAndList($source_method, $_method_list);
        }
        return $isSendNoticeMethod;
    }


    public function getUnreadMessagesByPlayerId($player_id){
        $this->CI->load->model(['player_model']);
        $this->CI->load->library(['player_message_library']);

        $badge = $this->CI->utils->unreadMessages($player_id);
        if ($this->CI->utils->getConfig('enabled_new_broadcast_message_job')) {
            $player_registr_date = $this->CI->player_model->getPlayerRegisterDate($player_id);
            $broadcast_messages = $this->CI->player_message_library->getPlayerAllBroadcastMessages($player_id, $player_registr_date);
            if (!empty($broadcast_messages)) {
                $badge += count($broadcast_messages);
            }
        }
        return $badge;
    }

    /**
     * clone from triggerPlayerLoginEvent
     */
    public function triggerOnGotMessagesEvent($player_id, $source_method) {
        // EVENT_ON_GOT_MESSAGES
        $this->CI->load->library(['lib_queue']);
        $eventName = Queue_result::EVENT_ON_GOT_MESSAGES;
        $eventData = [];
        $callerType = Queue_result::CALLER_TYPE_PLAYER;
		$caller = $player_id;
        $eventData['player_id'] = $player_id;
        $eventData['source_method'] = $source_method;
        $token = $this->CI->lib_queue->triggerAsyncRemoteInternalMessageEvent($eventName, $eventData, $callerType, $caller);
        return $token;
    } // EOF triggerOnGotMessagesEvent


    public function triggerOnSentMessageFromAdminEvent($player_id, $source_method, $adminId, $extra_info = []) {
        // `EVENT_ON_SENT_MESSAGE_FROM_ADMIN`
        $this->CI->load->library(['lib_queue']);
        $eventName = Queue_result::EVENT_ON_SENT_MESSAGE_FROM_ADMIN;
        $eventData = [];
        $callerType = Queue_result::CALLER_TYPE_ADMIN;
        $caller = $adminId; // @todo
        $eventData['player_id'] = $player_id;
        $eventData['admin_id'] = $adminId;
        $eventData['source_method'] = $source_method;
        $eventData['extra_info'] = $extra_info; // the param should be short, and that will via command
        $token = $this->CI->lib_queue->triggerAsyncRemoteInternalMessageEvent($eventName, $eventData, $callerType, $caller);
        return $token;
    } // EOF triggeronSentMessageFromAdminEvent

    /**
     * clone from triggerPlayerLoginEvent
     *
     */
    public function triggerOnAddedNewMessageEvent($player_id, $source_method) {
        // EVENT_ON_ADDED_NEW_MESSAGE
        $this->CI->load->library(['lib_queue']);
        $eventName = Queue_result::EVENT_ON_ADDED_NEW_MESSAGE;
        $eventData = [];
        $callerType = Queue_result::CALLER_TYPE_PLAYER;
		$caller = $player_id;
        $eventData['player_id'] = $player_id;
        $eventData['source_method'] = $source_method;
        $token = $this->CI->lib_queue->triggerAsyncRemoteInternalMessageEvent($eventName, $eventData, $callerType, $caller);
        return $token;
    } // EOF triggerOnGotMessagesEvent

    /**
     * clone from triggerOnGotMessagesEvent
     *
     */
    public function triggerOnUpdatedMessageStatusToReadEvent($player_id, $source_method) {
        // EVENT_ON_UPDATED_MESSAGE_STATUS_TO_READs
        $this->CI->load->library(['lib_queue']);
        $eventName = Queue_result::EVENT_ON_UPDATED_MESSAGE_STATUS_TO_READ;
        $eventData = [];
        $callerType = Queue_result::CALLER_TYPE_PLAYER;
		$caller = $player_id;
        $eventData['player_id'] = $player_id;
        $eventData['source_method'] = $source_method;
        $token = $this->CI->lib_queue->triggerAsyncRemoteInternalMessageEvent($eventName, $eventData, $callerType, $caller);
        return $token;
    } // EOF triggerOnUpdatedMessageStatusToReadEvent

    public function triggerOnGotProfileViaApiEvent($player_id, $source_method) {
        // EVENT_ON_GOT_PROFILE_VIA_API
        $this->CI->load->library(['lib_queue']);
        $eventName = Queue_result::EVENT_ON_GOT_PROFILE_VIA_API;
        $eventData = [];
        $callerType = Queue_result::CALLER_TYPE_PLAYER;
        $caller = $player_id;
        $eventData['player_id'] = $player_id;
        $eventData['source_method'] = $source_method;
        $token = $this->CI->lib_queue->triggerAsyncRemotePlayerProfileEvent($eventName, $eventData, $callerType, $caller);
        return $token;
    } // EOF triggerOnGotProfileViaApiEvent

}