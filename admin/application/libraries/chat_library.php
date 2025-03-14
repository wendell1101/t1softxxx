<?php
/**
 * chat_library.php
 *
 * @author
 *
 * @property BaseController $CI
 */
class Chat_library {
    /* @var BaseController */
    public $CI;

    public function __construct(){
        $this->CI =& get_instance();

        $this->CI->load->model(array('chat_manager', 'player_model'));
        $this->chat_manager = $this->CI->chat_manager;
    }

    public function revokeChatToken($username){
        $this->chat_manager->revokeChatToken($username);
    }

    public function isExpiredToken($tokenInfo){
        $isExpired = false;
        if(empty($tokenInfo)){
            $isExpired = true;
            return $isExpired;
        }

        $token = $tokenInfo['id'];
        $username = $tokenInfo['user_id'];
        $expires_at = $tokenInfo['expires_at'];
        $now = $this->CI->utils->getNowForMysql();

        $timeInfo = [
            'expires_at' => $expires_at,
            'now' => $now,
            'isExpired' => strtotime($expires_at) <= strtotime('now'),
        ];
        $this->CI->utils->debug_log(__METHOD__, 'time compare', $token, $timeInfo);

        if(strtotime($expires_at) <= strtotime('now')){
            $isExpired = true;
            $this->revokeChatToken($username);
            $this->CI->utils->debug_log(__METHOD__, 'revokeChatToken', $token, $username);
        }

        return $isExpired;
    }

    public function isDuplicateToken($token){
        $isDuplicate = false;
        $tokenInfo = $this->chat_manager->getChatTokenByToken($token);
        $this->CI->utils->debug_log(__METHOD__, 'isDuplicateToken', $token, $tokenInfo);
        if(!empty($tokenInfo)){
            $isDuplicate = $tokenInfo['id'] === $token;
        }
        return $isDuplicate;
    }

    public function generateChatToken($username, $chatRoomId){
        $token = base64_encode(md5($username . $chatRoomId . date('Ymd')));
        return $token;
    }

    public function requestChatToken($username, $chatRoomId, &$message, $language){
        $chatToken = $this->getChatToken($username, $chatRoomId);
        if(!empty($chatToken)){
            $tokenInfo = $this->getTokenInfoByToken($chatToken);
            if(!$this->isExpiredToken($tokenInfo)){
                $this->CI->utils->debug_log(__METHOD__, 'Token already exists, create token failed', $chatToken);
                return $chatToken;
            }
        }

        $token = $this->generateChatToken($username, $chatRoomId);

        $data = [
            'id' => $token,
            'user_id' => $username,
            'room_id' => $chatRoomId,
            'revoked' => Chat_manager::DB_FALSE,
            'language' => $language,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
        ];

        $succ = $this->chat_manager->createChatToken($data);
        $this->CI->utils->debug_log(__METHOD__, 'createChatToken', $token);

        if(!$succ){
            $message = 'Create token failed';
            $this->CI->utils->debug_log(__METHOD__, $message, $token);
            return false;
        }
        return $token;
    }

    public function getTokenInfoByToken($token){
        return $this->chat_manager->getChatTokenByToken($token);
    }

    public function getTokenLanguage($token){
        $result = null;
        $tokenLang = $this->chat_manager->getChatTokenLang($token);
        if(!empty($tokenLang)){
            $result = $tokenLang;
            $this->CI->utils->debug_log(__METHOD__, 'lang', $tokenLang);
        }
        return $result;
    }

    public function getChatToken($username, $chatRoomId){
        $result = false;
        $token = $this->chat_manager->getChatToken($username, $chatRoomId);
        if(!empty($token)){
            $result = $token;
        }
        return $result;
    }

    public function getPlayerIdByToken($token){
        $username = $this->chat_manager->getPlayerIdByToken($token);
        $this->CI->load->model('player_model');
        $playerId = $this->CI->player_model->getPlayerIdByUsername($username);
        return $playerId;
    }
}
