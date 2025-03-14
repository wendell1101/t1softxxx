<?php
require_once dirname(__FILE__) . '/abstract_notify_api.php';

/**
 * mobi_push notify API implementation
 * * url:    https://ole777-mobi-push.vercel.app/id
 * * method: GET
 *
 * Config items:
 * * notify_api_mobi_push_username
 * * notify_api_mobi_push_sendsmspassword
 * * notify_api_mobi_push_url			  (optional)
 */
class Notify_api_mobi_push extends Abstract_notify_api {
	// const URL_DEFAULT					= 'https://ole777-mobi-push.vercel.app/id';
    const URL_DEFAULT					= 'http://player.og.local/api/smash/fake_mobi_push/ok';

    const MODE_IN_SEND_NOTIFICATION			= 1;
    const MODE_IN_NOTICE_UNREAD_MESSAGE		= 2;

	protected $ident = 'NOTIFY_MOBI_PUSH';
	protected $api_name = 'mobi_push';
	protected $error_code = [
		100 => 'Other' ,
	];

    public function __construct() {
        parent::__construct();
	}

    public function getUrl() {
        $url = $this->getParam('url'); // aka. notify_api_mobi_push_url
        $url = empty($url) ? self::URL_DEFAULT : $url;
        return $url;
    }
    public function getHeaders(){
        $_headers = [];
        $_headers['Content-Type'] = 'application/json';
        $_headers['Authorization'] = 'Bearer '. $this->getParam('bearer');
        return $_headers;
    }

    /**
     * Provide the fields about notify
     *
     * @param string|array $notify_token the token for target of notify
     * @param array $notify_data The array
     * @param string $mode for switch the notify content.
     * @return void
     */
	public function getFields($notify_token, $notify_data, $mode = 'sendNotification') {
        $fields = [];
        switch($mode){
            default:
            case self::MODE_IN_SEND_NOTIFICATION:
                $fields = $this->getFields4sendNotification($notify_token, $notify_data['title'], $notify_data['body'] );
                break;
            case self::MODE_IN_NOTICE_UNREAD_MESSAGE:
                $fields = $this->getFields4noticeUnreadMessage($notify_token, $notify_data['inAppNotificationMessage'], $notify_data['badge']);
                break;
        }
		$this->utils->debug_log("{$this->ident} request fields", $fields);

		return $fields;
	} // EOF getFields()


    public function getFields4noticeUnreadMessage($notify_token, $inAppNotificationMessage = '', $badge = 0){
        // 可以放入多個接收者 可接受最高100 但建議不超過80個
        /// ref. to https://docs.google.com/document/d/1FIMIYNB5nfC4HzaeJrR7GggDDw3BENsmf6JkUjnJf7Y/edit
        // {
        //     "interests": [
        //         "id",
        //         "debug-id"
        //     ],
        //     "fcm": {
        //         "notification": {
        //             "data": {
        //                 "inAppNotificationMessage": "",
        //                 "badge": 0
        //             }
        //         }
        //     }
        // }
        // $notify_token = "6b6265bb-3a09-4e89-b162-2da773de74b8";
        $fields = [];
        $fields['interests'] = [];
        $notify_token_list = $this->get_list_from_notify_token($notify_token);
        foreach($notify_token_list as $a_token){
            $fields['interests'][] = 'id-'. $a_token;
            $fields['interests'][] = 'debug-id-'. $a_token;
        }
        $fields['fcm'] = [];
        $fields['fcm']['notification'] = [];
        $fields['fcm']['notification']['data'] = [];
        $fields['fcm']['notification']['data']['inAppNotificationMessage'] = $inAppNotificationMessage;
        $fields['fcm']['notification']['data']['badge'] = $badge;
        return $fields;
    } // EOF getFields4noticeUnreadMessage


    public function getFields4sendNotification($notify_token, $title = 'Notification Header', $body = 'Notification Message'){
        // 可以放入多個接收者 可接受最高100 但建議不超過80個
        /// ref. to https://docs.google.com/document/d/1FIMIYNB5nfC4HzaeJrR7GggDDw3BENsmf6JkUjnJf7Y/edit
        // {
        //     "interests": [
        //         "id",
        //         "debug-id"
        //     ],
        //     "apns": {
        //         "aps": {
        //             "alert": {
        //                 "title": "Notification Header",
        //                 "body": "Notification Message"
        //             },
        //             "sound": "bingbong.aiff"
        //         }
        //     },
        //     "fcm": {
        //         "notification": {
        //                 "title": "Notification Header",
        //                 "body": "Notification Message",
        //                 "data": {
        //                     "inAppNotificationMessage": ""
        //                 }
        //         }
        //     }
        // }
        // $notify_token = "6b6265bb-3a09-4e89-b162-2da773de74b8";
        // $notify_title = 'Notification Header';
        // $notify_body = 'Notification Message';
        $fields = [];
        $fields['interests'] = [];
        $notify_token_list = $this->get_list_from_notify_token($notify_token);
        foreach($notify_token_list as $a_token){
            $fields['interests'][] = 'id-'. $a_token;
            $fields['interests'][] = 'debug-id-'. $a_token;
        }
        $fields['apns'] = [];
        $fields['apns']['aps'] = [];
        $fields['apns']['aps']['alert'] = [];
        $fields['apns']['aps']['alert']['title'] = $title;
        $fields['apns']['aps']['alert']['body'] = $body;
        $fields['apns']['aps']['sound'] = "bingbong.aiff";
        $fields['fcm'] = [];
        $fields['fcm']['notification'] = [];
        $fields['fcm']['notification']['title'] = $title;
        $fields['fcm']['notification']['body'] = $body;
        $fields['fcm']['notification']['data'] = [];
        $fields['fcm']['notification']['data']['inAppNotificationMessage'] = '';
        return $fields;
    } // EOF getFields4sendNotification



    public function get_list_from_notify_token($notify_token){
        $notify_token_list = [];
        if(is_string($notify_token)){
            $notify_token_list[] = $notify_token;
        }else if(is_array($notify_token)){
            foreach($notify_token as $a_token){
                $notify_token_list[] = $a_token;
            }
        }
        return $notify_token_list;
    }



	public function getErrorMsg($returnQueryString) {
		$mesg = 'Unknown error';

		// $resp = json_decode($returnQueryString, 1);
		// if (empty($resp)) {
		// 	return $mesg;
		// }
        //
		// $mesg = isset($resp['Message']) ? $resp['Message'] : null;
		// $mesg .= isset($resp['Code']) ? "; Code={$resp['Code']}" : null;
		// $mesg .= isset($resp['SmsPerMessage']) ? "; SmsPerMessage={$resp['SmsPerMessage']}" : null;

		// $this->utils->debug_log("{$this->ident} getErrorMsg", [ 'mesg' => $mesg, 'resp' => $resp ]);

		return $mesg;
	}

	public function isSuccess($returnQueryString) {
        return strpos($returnQueryString, 'publishId') !== false;
	}


}
