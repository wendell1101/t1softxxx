<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/abstract_tracking_api.php';

/**
 * Clever tap api
 * OGP-30236
 * doc: https://developer.clevertap.com/docs/upload-events-api
 *
 *
 * @version		1.0.0
 */

 Class Clever_tap_api extends Abstract_tracking_api{

    public $account_id;
    public $passcode;
    public $player;

    const EVENTNAME = 'evtName';

    public function init()
    {
        $this->CI->load->model(array('player_login_report', 'player', 'third_party_login', 'payment_account'));

        $this->_options = array_replace_recursive($this->_options, config_item('clever_tap'));
        $this->account_id = '';
        $this->passcode = '';
        $this->player = false;
        $this->identity_content = [
            "d" => [[
                "identity"     => "",
                "type"         => "profile",
                "profileData"  => ""
            ]]
        ];
        $this->post_content = [
            "d" => [[
                "identity" => "",
                "type"     => "event",
                "evtName"  => "",
                "evtData"  => ""
            ]]
        ];
    }

    public function generatePostContent($platform_setting, $tracking_extra_info, $player_id = null)
    {
        $this->account_id = $this->utils->safeGetArray($platform_setting, 'account_id');
        $this->passcode = $this->utils->safeGetArray($platform_setting, 'passcode'); 
    }
    public function triggerPlayerPostBack($eventName, $player, $platform_setting, $tracking_extra_info)
    {
        $this->platform_setting = $platform_setting;
		        
        $res = false;
        $this->player = $player ?: null;
        $this->generatePostContent($platform_setting, $tracking_extra_info);
        $this->utils->debug_log('============processTrackingCallback eventName============ ', $eventName);

        switch ($eventName) {
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_LAST_LOGIN:
                $res = $this->loginPostBack($player);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_REGISTER_COMMOM:
                $res = $this->regPostBack('', $player);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_FIRST_DEPOSIT_SUCCESS:
                $deposit_order = $this->utils->safeGetArray($tracking_extra_info, 'deposit_order');
                $res = $this->everyTimeDepositPostBack($tracking_extra_info, $player, '', $deposit_order);
                // $this->largeAmountDepositPostBack($tracking_extra_info, $player, $deposit_order);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_SUCCESS:
                $deposit_order = $this->utils->safeGetArray($tracking_extra_info, 'deposit_order');
                $res = $this->everyTimeDepositPostBack($tracking_extra_info, $player, 'Success', $deposit_order);
                // $this->largeAmountDepositPostBack($tracking_extra_info, $player, $deposit_order);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_SENT_MESSAGE_SUCCESS:
                $res = $this->inboxMessagePostBack($player, $tracking_extra_info);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_ADD_ANNOUNCEMENT_MESSAGE:
                $res = $this->announcementMessagePostBack($player, $tracking_extra_info);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_SUCCESS:
                $walletAccount = $this->utils->safeGetArray($tracking_extra_info, 'walletAccount');
                $res = $this->withdrawalPostBack($tracking_extra_info, $player, 'Success', $walletAccount);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_DEPOSIT_FAILED:
                $deposit_order = $this->utils->safeGetArray($tracking_extra_info, 'deposit_order');
                $res = $this->everyTimeDepositPostBack($tracking_extra_info, $player, 'Failed', $deposit_order);
                break;
            case Player_trackingevent::TRACKINGEVENT_SOURCE_TYPE_WITHDRAWAL_FAILED:
                $walletAccount = $this->utils->safeGetArray($tracking_extra_info, 'walletAccount');
                $res = $this->withdrawalPostBack($tracking_extra_info, $player, 'Failed', $walletAccount);
                break;
            default:
                // $res = $this->commonPostBack($eventName, $platform_setting, $tracking_extra_info, $player);
        }
        return $res;
    }

    public function loginPostBack($player, $extra_info = null)
    {
        $this->utils->debug_log(' ========================loginPostBack player:', $player);

        if (!in_array('login', $this->platform_setting['available_event'])) {
            return false;
        }
        $eventName = $this->getEventName('login');
        if (empty($eventName)) {
            return false;
        }
        // identity
        $this->init_identity_content($player, $player['playerId'], $player['username']);

        // main event
        $this->post_content['d'][0]['identity'] = $player['playerId'];
        $this->post_content['d'][0][self::EVENTNAME] = $eventName;
        $player_info = $this->CI->player_model->getPlayerInfoDetailById($player['playerId']);
        $first_deposit_date = $this->CI->player_model->getPlayerFirstDepositDate($player['playerId']);
        $last_deposit_date  = $this->CI->transactions->getLastDepositDate($player['playerId']);
        $tag =  $this->CI->player_model->player_tagged_list($player['playerId']);

        $evtData = array();
        $evtData = [
            'username'             => $player['username'],
            'player_id'            => $player['playerId'],
            'phone_number'         => $player_info['contactNumber'],
            'vip_level'            => sprintf('%s - %s', lang($player['groupName']), lang($player['levelName'])),
            'last_login_date'      => $player_info['last_login_time'],
            'first_deposit_date'   => $first_deposit_date, 	 
            'last_deposit_date'	   => $last_deposit_date,
            'deposit_count'	       => $player['total_deposit_count'],
            'total_deposit_amount' => $player['totalDepositAmount'],	 
            'player_tag'           => join(', ', $tag)
        ];
        $this->post_content['d'][0]['evtData'] = $evtData;

        $post_back_data = $this->post_content;

        return $this->doPost($post_back_data, $player);
    }

    public function regPostBack($clickId, $player, $extra_info = null)
    {
        $this->utils->debug_log(' ========================regPostBack player:', $player);

        if (!in_array('reg', $this->platform_setting['available_event'])) {
            return false;
        }
        $eventName = $this->getEventName('reg');
        if (empty($eventName)) {
            return false;
        }
        // identity
        $this->init_identity_content($player, $player['playerId'], $player['username']);

        // main event
        $this->post_content['d'][0]['identity'] = $player['playerId'];
        $this->post_content['d'][0][self::EVENTNAME] = $eventName;
        $player_info = $this->CI->player->getPlayerById($player['playerId']);
        $player_line = $this->CI->third_party_login->getLineInfoByPlayerId($player['playerId']);
        $affiliate = $this->CI->player->getAffiliateOfPlayer($player['playerId']);
        $this->utils->debug_log(' ========================player_line:', $player_line);

        $evtData = array();
        $evtData = [
            'register_option' => $player_info['registered_by'],
            'status'          => "Success",
            'username'        => $player['username'],
            'player_id'       => $player['playerId'],
            'phone_number'    => $player_info['phone'],
            'vip_level'       => sprintf('%s - %s', lang($player['groupName']), lang($player['levelName'])),
            'signup_date'     => $player['createdOn'],
            'under_affiliate' => $affiliate, 	 
            'last_name'	      => $player_info['lastName'],
            'first_name'	  => $player_info['firstName'],
            'language'        => $player_info['language'],	 
            'contact_number	' => $player_info['contactNumber'],
            'line'            => $player_info['imAccount']
        ];

        $this->post_content['d'][0]['evtData'] = $evtData;
        $post_back_data = $this->post_content;

        return $this->doPost($post_back_data, $player);
    }

    public function everyTimeDepositPostBack($tracking_extra_info, $player, $status, $deposit_order = null)
    {
        $this->utils->debug_log(' ========================everyTimeDepositPostBack deposit_order:', $deposit_order);

        if (!in_array('dep', $this->platform_setting['available_event'])) {
            return false;
        }
        $eventName = $this->getEventName('dep');
        if (empty($eventName)) {
            return false;
        }
        // identity
        $this->init_identity_content($player, $player['playerId'], $player['username']);

        // main event
        $this->post_content['d'][0]['identity'] = $player['playerId'];
        $this->post_content['d'][0][self::EVENTNAME] = $eventName;

        if ($deposit_order) {

            // $revenue = $deposit_order['amount'];
            
            // $payment_account = $this->CI->payment_account->getPaymentAccount($deposit_order['payment_account_id']);
           
            // switch($payment_account->flag){
            //     case "1":
            //         $flag = "Bank Deposit";
            //         break;
            //     case "2":
            //         $flag = "3rd Party Payment";
            //         break;
            //     case "3":
            //         $flag = "ATM/Cashier";
            //         break;
            //     default:
            //         $flag = "";
            //         break;
            // }
            $trans = $this->CI->transactions->getTransactionBySaleOrderId($deposit_order['id']);
            $currency = $this->CI->utils->getCurrentCurrency();
            $evtData = array();
            $evtData = [
                // 'username' => $player['username'],
                // 'amount'   => $revenue,
                // 'type'     => $flag,
                'orderid'             => $deposit_order['id'],
                'secure_id'           => $deposit_order['secure_id'],
                'amount'              => $deposit_order['amount'],
                "Type"                => "Deposit",
                "Status"              => $status,
                "Currency"            => $currency['currency_code'],
                "Transaction ID"      => $deposit_order['secure_id'],
                "Channel"             => $deposit_order['payment_account_name'],
                "Time Taken"          => strtotime($trans->created_at) - strtotime($deposit_order['created_at']),
                "Last Deposit Amount" => $deposit_order['amount']
            ];

            $this->post_content['d'][0]['evtData'] = $evtData;
        }
        $post_back_data = $this->post_content;
        return $this->doPost($post_back_data, $player, $deposit_order['secure_id']);
    }

    public function withdrawalPostBack($tracking_extra_info, $player, $status, $walletAccount = null)
    {
        $this->utils->debug_log(' ========================withdrawalPostBack walletAccount:', $walletAccount);
        if (!in_array('wit', $this->platform_setting['available_event'])) {
            $this->utils->debug_log(' ========================withdrawalPostBack available_event error');
            return false;
        }
        $eventName = $this->getEventName('wit');
        if (empty($eventName)) {
            $this->utils->debug_log(' ========================withdrawalPostBack getEventName error');
            return false;
        }
        // identity
        $this->init_identity_content($player, $player['playerId'], $player['username']);

        // main event
        $this->post_content['d'][0]['identity'] = $player['playerId'];
        $this->post_content['d'][0][self::EVENTNAME] = $eventName;

        $last_deposit_amount = $this->CI->transactions->queryAmountByPlayerIdFromLastTransaction($walletAccount['playerId']);
        $is_first_withdrawal = $this->CI->transactions->isOnlyFirsWithdrawal($walletAccount['playerId']);
        $currency = $this->CI->utils->getCurrentCurrency();

        if($walletAccount){
            $evtData = array();
            $evtData = [
                'transactionCode' 	  => $walletAccount['transactionCode'],
                'dwDateTime' 		  => $walletAccount['dwDateTime'],
                'amount' 			  => $walletAccount['amount'],
                "Type"				  => "Withdrawal",
                "Status"			  => $status,
                "Currency"			  => $currency['currency_code'],
                "Transaction ID"	  => $walletAccount['transactionCode'],
                "Channel"			  => ($walletAccount['paymentAPI'] > 0) ? $this->CI->external_system->getSystemName($walletAccount['paymentAPI']) : "Manual Payment",
                "Time Taken" 		  => $walletAccount['spent_time'],
                "Last Deposit Amount" => $last_deposit_amount,
                "First Withdrawal"	  => ($is_first_withdrawal) ? "Yes" : "No",
            ];

            $this->post_content['d'][0]['evtData'] = $evtData;
        }
        $post_back_data = $this->post_content;
        return $this->doPost($post_back_data, $player, $walletAccount['transactionCode']);
    }

    public function inboxMessagePostBack($player, $extra_info = null)
    {   
        $this->utils->debug_log(' ========================inboxMessagePostBack player:', $player);

        if (!in_array('inbox_msg', $this->platform_setting['available_event'])) {
            return false;
        }
        $eventName = $this->getEventName('inbox_msg');
        if (empty($eventName)) {
            return false;
        }
        // identity
        $this->init_identity_content($player, $player['playerId'], $player['username']);

        // main event
        $this->post_content['d'][0]['identity'] = $player['playerId'];
        $this->post_content['d'][0][self::EVENTNAME] = $eventName;

        $evtData = array();
        $evtData = [
            'title'   => $extra_info['subject'],
            'message' => $extra_info['message'],
        ];
        $this->post_content['d'][0]['evtData'] = $evtData;
        $post_back_data = $this->post_content;
        return $this->doPost($post_back_data, $player);
    }

    public function announcementMessagePostBack($adminUser, $extra_info = null)
    {   
        $this->utils->debug_log(' ========================announcementMessagePostBack adminUser:', $adminUser);

        if (!in_array('ann_msg', $this->platform_setting['available_event'])) {
            return false;
        }
        $eventName = $this->getEventName('ann_msg');
        if (empty($eventName)) {
            return false;
        }
        // identity
        $this->init_identity_content(null, $adminUser['userId'], $adminUser['username']);

        // main event
        $this->post_content['d'][0]['identity'] = $adminUser['userId'];
        $this->post_content['d'][0][self::EVENTNAME] = $eventName;

        $evtData = array();
        $evtData = [
            'message' => $extra_info['message'],
        ];
        $this->post_content['d'][0]['evtData'] = $evtData;
        $post_back_data = $this->post_content;
        return $this->doPost($post_back_data, null);
    }
    
    private function getEventName($event)
    {
        $postback_setting =  $this->getOptions($event);

        if ($postback_setting) {
            $eventName = $this->utils->safeGetArray($postback_setting, 'eventName');
            return $eventName;
        }

        return false;
    }

    public function doPost($post_back_data, $player = null, $deposit_order = null)
    {
        $_post_back_data = json_encode($post_back_data, JSON_UNESCAPED_UNICODE);
        if (!$this->getOptions('isDebug')) {
            $this->_options['CURLOPT'][CURLOPT_HTTPHEADER] = array(
                'Content-Type: application/json',
                'X-CleverTap-Account-Id:'. $this->account_id,
                'X-CleverTap-Passcode:'. $this->passcode
            );
        }
        return parent::doPost($_post_back_data, $player, $deposit_order);
    }

    protected function getApiURL()
    {
        $api_url = $this->getOptions('api_url');
        return $api_url;
    }

    public function checkApifields($platform_setting, $tracking_extra_info) {
        return $this->utils->safeGetArray($platform_setting, 'account_id') && $this->utils->safeGetArray($platform_setting, 'passcode');
    }
    
    public function init_identity_content($player, $player_id, $player_name)
    {
        $this->utils->debug_log(' ========================init_identity_content player:', $player);
        
        $this->identity_content['d'][0]['identity'] = $player_id;
        $this->identity_content['d'][0]['profileData'] = [
            'Name'  => $player_name
        ];

        $post_back_data = $this->identity_content;
        $this->doPost($post_back_data, $player);
    }
 }