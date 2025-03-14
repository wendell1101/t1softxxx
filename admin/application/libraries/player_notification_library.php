<?php
/**
 * player_notification_library.php
 *
 * @author Elvis Chen
 *
 * @property BaseController $CI
 * @property Player_notification $player_notification
 */
class Player_notification_library {
    /* @var BaseController */
    public $CI;

    public function __construct(){
        $this->CI =& get_instance();

        $this->CI->load->model(['player_notification']);
        $this->CI->load->library(['form_validation']);

        $this->player_notification = $this->CI->player_notification;
    }

    public function getNotifyExtraInfo($sourceType, $content){
        $result = [];
        if(empty($sourceType) || empty($content)){
            return $result;
        }
        $mapSourceType = [
            Player_notification::SOURCE_TYPE_LAST_LOGIN => $this->getLoninExtraInfo($content),
            Player_notification::SOURCE_TYPE_DEPOSIT => $this->getDepsitExtraInfo($content),
            Player_notification::SOURCE_TYPE_WITHDRAWAL => $this->getWithdrawalExtraInfo($content),
            Player_notification::SOURCE_TYPE_VIP_UPGRADE => $this->getVipExtraInfo($content),
            Player_notification::SOURCE_TYPE_VIP_DOWNGRADE => $this->getVipExtraInfo($content),
        ];
        if(isset($mapSourceType[$sourceType])){
            $result = $mapSourceType[$sourceType];
        }
        return $result;
    }

    public function getDepsitExtraInfo($content){
        $result = [
            'orderId' => $this->_getOrderIdFromPaymentContent($content),
            'amount' => '',
            'isFirst' => FALSE,

        ];
        if(!empty($result['orderId'])){
            $this->CI->load->model(['sale_order']);
            $paymentDetails = $this->CI->sale_order->getSaleOrderArrBySecureId($result['orderId']);
        }
        if(!empty($paymentDetails)){
            $result['amount'] = $paymentDetails['amount'];
        }

        if(in_array(Player_notification::FLAG_FIRST_DEPOSIT, $content)){
            $result['isFirst'] = TRUE;
        }
        
        return $result;
    }

    public function getWithdrawalExtraInfo($content){
        $result = [
            'orderId' => $this->_getOrderIdFromPaymentContent($content),
            'amount' => '',
        ];
        if(!empty($result['orderId'])){
            $this->CI->load->model(['wallet_model']);
            $paymentDetails = $this->CI->wallet_model->getWalletAccountByTransactionCode($result['orderId']);
        }
        if(!empty($paymentDetails)){
            $result['amount'] = $paymentDetails['amount'];
        }
        return $result;
    }

    private function _getOrderIdFromPaymentContent($content){
        if(empty($content)){
            return '';
        }
        $this->CI->load->library(['payment_library']);
        //$content[1] is a default fixed element in the payment notification.
        if(!empty($content[1])){
            if($this->CI->payment_library->validateOrderFormat($content[1])){
                return $content[1];
            }
        }
        foreach($content as $val){
            if($this->CI->payment_library->validateOrderFormat($val)){
                return $val;
            }
        }
        return '';
    }

    public function getLoninExtraInfo($content){
        $result = [
            'loginTime' => isset($content[1]) ? $content[1] : '',
            'loginIp' => isset($content[2]) ? $content[2] : '',
            'country' => isset($content[3]) ? $content[3] : '',
            'city' => isset($content[4]) ? $content[4] : '',
        ];
        return $result;
    }

    public function getVipExtraInfo($content){
        $result = [
            'oldGroupName' => isset($content[2]) ? lang($content[2]) : '',
            'oldLevelName' => isset($content[3]) ? lang($content[3]) : '',
            'newGroupName' => isset($content[4]) ? lang($content[4]) : '',
            'newLevelName' => isset($content[5]) ? lang($content[5]) : '',
        ];
        return $result;
    }
    
    public function getNotify($player_id){
        $self = $this;

        $display_player_notification_in_playerhost_only = $this->CI->utils->getConfig('display_player_notification_in_playerhost_only');
        if($display_player_notification_in_playerhost_only) {
            $is_player_host = !(strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) == false);
            $this->CI->utils->debug_log(" ============getNotify============ ",[
                'display_player_notification_in_playerhost_only' => $display_player_notification_in_playerhost_only,
                'is_player_host' => $is_player_host,
                'HTTP_HOST'=> $_SERVER['HTTP_HOST'], 
                'HTTP_REFERER' => $_SERVER['HTTP_REFERER']
            ]);
            if(!$is_player_host) {
                return [];
            }
        }

        $player_notify_list = $this->player_notification->getNotify($player_id);
        if(empty($player_notify_list)){
            return [];
        }

        foreach($player_notify_list as $notify_id => $notify_content){
            $title = @json_decode($notify_content['title'], TRUE);
            $message = @json_decode($notify_content['message'], TRUE);
            $notify_content['title'] = $this->getNotifyTitle($title, $notify_content);
            $notify_content['message'] = $this->getNotifyMessage($message, $notify_content);
            $player_notify_list[$notify_id] = $notify_content;
        }

        return $player_notify_list;
    }

    public function getNotifyTitle($title, $notify_content){
        $result = '';
        if(is_array($title)){
            $title[0] = lang($title[0]);
            switch($notify_content['source_type']){
                case Player_notification::SOURCE_TYPE_VIP_UPGRADE:
                    if(isset($title[2])){
                        $title[2] = lang($title[2]);
                    }
                    if(isset($title[3])){
                        $title[3] = lang($title[3]);
                    }
                    if(isset($title[4])){
                        $title[4] = lang($title[4]);
                    }
                    if(isset($title[5])){
                        $title[5] = lang($title[5]);
                    }
                    break;
                default:
                    break;
            }
            $result = @call_user_func_array('sprintf', $title);
        }else{
            $result = lang($notify_content['title']);
        }
        return $result;
    }

    public function getNotifyMessage($message, $notify_content){
        $result = '';
        if(is_array($message)){
            $message[0] = lang($message[0]);
            switch($notify_content['source_type']){
                case Player_notification::SOURCE_TYPE_VIP_UPGRADE:
                    if(isset($message[2])){
                        $message[2] = lang($message[2]);
                    }
                    if(isset($message[3])){
                        $message[3] = lang($message[3]);
                    }
                    if(isset($message[4])){
                        $message[4] = lang($message[4]);
                    }
                    if(isset($message[5])){
                        $message[5] = lang($message[5]);
                    }
                    break;
                default:
                    break;
            }
            $result = @call_user_func_array('sprintf', $message);
        }else{
            $result = lang($notify_content['message']);
        }
        return $result;
    }

    public function setIsNotify($player_id, $notify_id, $return_afftect = FALSE){
        return $this->player_notification->setIsNotify($player_id, $notify_id, $return_afftect);
    }

    public function createNotify($player_id, $source_type, $notify_type, $title = NULL, $message = NULL, $url = NULL, $url_target = NULL){
        $self = $this;

        if(!$this->CI->operatorglobalsettings->getSettingBooleanValue('player_center_notification')){
            return FALSE;
        }

        $allowed_source_type = $this->CI->operatorglobalsettings->getSettingJson('player_center_notification_source_type', 'value', []);
        if(empty($allowed_source_type)){
            return FALSE;
        }

        if(!in_array((string)$source_type, (array)$allowed_source_type)){
            return FALSE;
        }

        $title = (is_array($title)) ? json_encode($title) : $title;
        $message = (is_array($message)) ? json_encode($message) : $message;

        return $self->player_notification->createNotify($player_id, $source_type, $notify_type, $title, $message, $url, $url_target);
    }

    public function info($player_id, $source_type, $title = NULL, $message = NULL, $url = NULL, $url_target = NULL){
        return $this->createNotify($player_id, $source_type, Player_notification::NOTIFICATION_TYPE_INFO, $title, $message, $url, $url_target);
    }

    public function success($player_id, $source_type, $title = NULL, $message = NULL, $url = NULL, $url_target = NULL){
        return $this->createNotify($player_id, $source_type, Player_notification::NOTIFICATION_TYPE_SUCCESS, $title, $message, $url, $url_target);
    }

    public function warning($player_id, $source_type, $title = NULL, $message = NULL, $url = NULL, $url_target = NULL){
        return $this->createNotify($player_id, $source_type, Player_notification::NOTIFICATION_TYPE_WARNING, $title, $message, $url, $url_target);
    }

    public function danger($player_id, $source_type, $title = NULL, $message = NULL, $url = NULL, $url_target = NULL){
        return $this->createNotify($player_id, $source_type, Player_notification::NOTIFICATION_TYPE_DANGER, $title, $message, $url, $url_target);
    }
}
