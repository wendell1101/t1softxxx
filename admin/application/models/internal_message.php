<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 *
 * General behaviors include
 * * set or update status of a message for a certain player
 * * get the message of a certain player
 * * add new message admin
 * * add new message
 * * Will get messages history based on the session
 * * Will get messages status based on the session
 * * delete a certain message
 * * mark message as close
 * * count unread message of a certain player
 *
 * @category CS Model
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class Internal_message extends BaseModel {

	function __construct() {
		parent::__construct();
	}

    const STATUS_NEW = 3;
    const STATUS_READ = 4;
    const STATUS_DRAFTS = 5;
    const STATUS_ADMIN_NEW = 6;
    const STATUS_UNPROCESSED = 7;
    const STATUS_PROCESSED = 8;

	const MESSAGE_DETAILS_UNREAD = 0;
	const MESSAGE_DETAILS_READ = 1;

    const ADMIN_ID = 1;
    const SUPERADMIN_ID = 2;

    const MESSAGE_TYPE_NORMAL = 0;
    const MESSAGE_TYPE_REQUEST_FORM = 1;

    const FLAG_GETMESG_UNREAD	= 11;
    const FLAG_GETMESG_READ		= 12;
    const FLAG_GETMESG_ALL		= 13;

    const MESSAGE_ADMIN_UNREAD = 1;
    const MESSAGE_ADMIN_READ = 0;

	protected $tableName = 'messages';

	/**
	 * detail: get the message of a certain player
	 *
	 * @param int $playerId messages player id
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array
	 */
	public function getMessages($playerId, $limit, $offset, $where = '', $sort = 'DESC') {
        $playerId=intval($playerId);
		$this->db->select('c.messageId,
						  c.message_type,
						  c.playerId,
						  c.adminId,
						  c.session,
						  c.subject,
						  c.date,
						  c.status,
						  c.deleted,
						  c.deleted_at,
						  c.deleted_by,
						  p.username AS sender,
						  au.username AS recepient,
						  cd.message AS detail,
						  c.admin_unread_count,
						  c.player_unread_count,
						  c.broadcast_id as broadcastId,
						  cd.status as cd_status,
						  cd.flag');
		$this->db->from('messages AS c');
		$this->db->join('messagesdetails AS cd', 'cd.messageId = c.messageId', 'left');
		$this->db->join('player AS p', 'p.playerId = c.playerId', 'left');
        $this->db->join('adminusers AS au', 'au.userId = cd.adminId', 'left');
		$this->db->where('c.playerId', $playerId);
		$this->db->group_by('c.messageId');
		$this->db->order_by('c.messageId', $sort);

		if( ! empty( $where ) ) $this->db->where($where);

		$this->db->where('cd.messageDetailsId IN (SELECT MAX(messageDetailsId) FROM messagesdetails join messages on messagesdetails.messageId=messages.messageId where messages.playerId='.$playerId.' group by messagesdetails.messageId)');

		if ($limit != null||$limit != 0) {
			$this->db->limit($limit,$offset);
		}

		$query = $this->db->get();
		// $this->CI->utils->debug_log('retrieve sql', $this->db->last_query());

		return $query->result_array();
	}

	/**
	 * Converted from Internal_message::getMessage()
	 * @param	int		$playerId 	== player.playerId
	 * @param	int		$limit    	Limit for paging
	 * @param	int		$offset   	Offset for paging
	 * @param	int		$read_flag	Any of FLAG_GETMESG_UNREAD, FLAG_GETMESG_READ, FLAG_GETMESG_ALL.  Defaults to FLAG_GETMESG_ALL.
	 * @param	bool	$with_deleted	True to select deleted messages.  Defaults to false.
	 * @return [type]           [description]
	 */
	public function getMessages2($playerId, $limit, $offset, $read_flag = self::FLAG_GETMESG_ALL, $with_deleted = false) {
		$this->db->select([
			'c.messageId'  ,
			'c.message_type' ,
			'c.playerId' ,
			'c.adminId' ,
			// 'c.session' ,
			'c.subject' ,
			'c.date' ,
			'c.status' ,
			// 'c.deleted' ,
			// 'c.deleted_at' ,
			// 'c.deleted_by' ,
			'p.username AS sender' ,
			'au.username AS recepient' ,
			'COUNT(cd.messageDetailsId) AS threadLen' ,
			'cd.message AS detail' ,
			// Note: admin/player_unread_count are inverted in implementation
			'c.admin_unread_count AS player_unread' ,
			'c.player_unread_count AS admin_unread'
		]);
		$this->db->from('messages AS c');
		$this->db->join('messagesdetails AS cd', 'cd.messageId = c.messageId', 'left');
		$this->db->join('player AS p', 'p.playerId = c.playerId', 'left');
        $this->db->join('adminusers AS au', 'au.userId = cd.adminId', 'left');
		$this->db->where('c.playerId', $playerId);
		$this->db->group_by('c.messageId');
		$this->db->order_by('c.messageId','DESC');

		// read_flag
		switch ($read_flag) {
			case self::FLAG_GETMESG_UNREAD :
				$this->db->where('c.status', self::STATUS_UNPROCESSED);
				break;
			case self::FLAG_GETMESG_READ :
				$this->db->where('c.status', self::STATUS_READ);
				break;
			case self::FLAG_GETMESG_ALL :
				default :
				break;
		}

		// with_deleted
		if ($with_deleted) {
			$this->db->select('c.deleted');
		}
		else {
			$this->db->where('c.deleted', 0);
		}

		if ($limit != null||$limit != 0) {
			$this->db->limit($limit,$offset);
		}

		$res = $this->runMultipleRowArray();

		// $this->CI->utils->printLastSQL();

		return $res;
	}

	public function playerUnreadMessages($player_id){
        $filter = 'c.deleted = 0 AND cd.flag = "admin" AND cd.status = ' . static::MESSAGE_DETAILS_UNREAD;
        return $this->getMessages($player_id, null, null, $filter);
    }

	public function getMessageById($message_id, $player_id = NULL){
        $this->db->select('c.messageId,
						  c.message_type,
						  cd.messageDetailsId,
						  c.playerId,
						  p.username AS playerUsername,
						  c.adminId,
						  au.username AS adminUsername,
						  cd.sender AS admin_custom_name,
						  c.subject,
						  cd.message AS detail,
						  cd.date,
						  c.status,
						  cd.status AS detail_status,
						  cd.flag,
						  c.is_system_message,
						  c.disabled_replay,
						  c.deleted,
						  c.deleted_at,
						  c.deleted_by');
        $this->db->from('messages AS c');
        if(!empty($message_id)){
            $this->db->join('messagesdetails AS cd', 'cd.messageId = c.messageId AND cd.messageId="' . $message_id . '"', 'left');
        }else{
            $this->db->join('messagesdetails AS cd', 'cd.messageId = c.messageId', 'left');
        }
        $this->db->join('player AS p', 'p.playerId = c.playerId', 'left');
        $this->db->join('adminusers AS au', 'au.userId = cd.adminId', 'left');
        $this->db->order_by('cd.messageDetailsId','ASC');

        if(!empty($message_id)){
            $this->db->where('c.messageId', $message_id);
        }

        if(!empty($player_id)){
            $this->db->where('c.playerId', $player_id);
        }

        return $this->runMultipleRowArray();
    }

	public function getBroadcastMessageById($broadcast_id, $player_id = null){
		$broadcast = $this->getBroadcastMessages($broadcast_id);
		$this->CI->utils->printLastSQL();
		$this->utils->debug_log(__METHOD__, 'getBroadcastMessages', $broadcast);
		if (empty($broadcast)) {
			return false;
		}

		$this->load->model('player_model','users');
		$this->load->library(['player_message_library']);

		$userId = $broadcast[0]['adminId'];
		$adminUsername = $this->users->getUserUsernameByid($userId);
		$playerUsername = $this->player_model->getUsernameById($player_id);
		$sender = $this->player_message_library->getDefaultAdminSenderName();

		$broadcast[0]['flag'] = 'admin';
		$broadcast[0]['message_type'] = self::MESSAGE_TYPE_NORMAL;
		$broadcast[0]['playerId'] = $player_id;
		$broadcast[0]['playerUsername'] = $playerUsername;
		$broadcast[0]['adminId'] = $userId;
		$broadcast[0]['adminUsername'] = $adminUsername;
		$broadcast[0]['admin_custom_name'] = $sender;
		$broadcast[0]['status'] = self::STATUS_ADMIN_NEW;
		$broadcast[0]['detail_status'] = static::MESSAGE_DETAILS_UNREAD;
		$broadcast[0]['is_system_message'] = '0';
		$broadcast[0]['disabled_replay'] = '0';
		$broadcast[0]['is_broadcast_messages'] = true;
		$broadcast[0]['deleted'] = '0';
		$broadcast[0]['admin_unread_count'] = 1;
		$this->utils->debug_log(__METHOD__, 'broadcast result', $broadcast);
		return $broadcast;
	}

	/**
	 * detail: add new message admin
	 *
	 * @param int $user_id messages adminId
	 * @param int $playerId messages player id
	 * @param string  $ticket_number messages session
	 * @param string $subject messages subject
	 * @param string $sender messagedetails sender
	 * @param string $message messagedetails message
	 *
	 * @return int
	 */
	public function addNewMessageAdmin($user_id // #1
		, $playerId // #2
		, $sender // #3
		, $subject // #4
		, $message // #5
		, $is_system_message = FALSE // #6
		, $disabled_replay = FALSE // #7
		, $broadcast_id = NULL // #8
	) {
        $this->startTrans();

        $ticket_number = 'Ticket#' . random_string('numeric', 16);

		$currentMessageDate = empty($broadcast_id) ? $this->utils->getNowForMysql(): $this->getBroadcastDate($broadcast_id);
        $messageId = $this->insertData($this->tableName, array(
            'message_type' => self::MESSAGE_TYPE_NORMAL,
			'adminId' => $user_id,
			'playerId' => $playerId,
			'session' => $ticket_number,
			'subject' => $subject,
			'date' => $currentMessageDate,
			'is_system_message' => $is_system_message,
			'disabled_replay' => $disabled_replay,
			'admin_last_reply_id' => $user_id,
			'admin_last_reply_dt' => $currentMessageDate,
			'admin_unread_count' => 1,
			'status' => self::STATUS_ADMIN_NEW,
			'broadcast_id' => $broadcast_id
		));

        if(empty($messageId)){
            $this->rollbackTrans();
            return FALSE;
        }

        $messageDetailsId = $this->addNewMessageDetailWithAdmin($messageId, $user_id, $sender, $message, $currentMessageDate);

        if(empty($messageDetailsId)){
            $this->rollbackTrans();
            return FALSE;
        }
        $this->endTransWithSucc();

        return $messageId;
	}

	/**
	 * detail: add new message
	 *
	 * @param int $player_id message player id
	 * @param string $ticket_number message session
	 * @param string $subject message subject
	 * @param string $message messagedetail message
	 *
	 * @return int
	 */
	public function addNewMessage($player_id, $subject, $sender, $message, $drafts = FALSE) {
        $this->startTrans();

        $ticket_number = 'Ticket#' . random_string('numeric', 16);

        $messageId = $this->insertData($this->tableName, array(
            'message_type' => self::MESSAGE_TYPE_NORMAL,
            'adminId' => static::ADMIN_ID,
			'playerId' => $player_id,
			'session' => $ticket_number,
			'subject' => $subject,
			'date' => $this->utils->getNowForMysql(),
            'is_system_message' => FALSE,
            'disabled_replay' => FALSE,
            'player_last_reply_dt' => $this->utils->getNowForMysql(),
            'player_unread_count' => 1,
            'status' => (!empty($drafts)) ? self::STATUS_DRAFTS : self::STATUS_NEW,
		));

        if(empty($messageId)){
            $this->rollbackTrans();
            return FALSE;
        }

        $messageDetailsId = $this->addNewMessageDetail($messageId, $sender, $message);

        if(empty($messageDetailsId)){
            $this->rollbackTrans();
            return FALSE;
        }
        $this->endTransWithSucc();

		return $messageId;
	}

    public function addRequestFormMessage($player_id = NULL, $sender = NULL, $message = NULL) {
        $this->startTrans();

        $ticket_number = 'Ticket#' . random_string('numeric', 16);

        $messageId = $this->insertData($this->tableName, array(
            'message_type' => self::MESSAGE_TYPE_REQUEST_FORM,
            'adminId' => static::ADMIN_ID,
            'playerId' => (empty($player_id)) ? '' : $player_id,
            'session' => $ticket_number,
            'subject' => lang($this->CI->utils->getConfig('message_request_form_default_subject')),
            'date' => $this->utils->getNowForMysql(),
            'is_system_message' => FALSE,
            'disabled_replay' => FALSE,
            'player_last_reply_dt' => $this->utils->getNowForMysql(),
            'player_unread_count' => 1,
            'status' => self::STATUS_NEW,
        ));

        if(empty($messageId)){
            $this->rollbackTrans();
            return FALSE;
        }

        $messageDetailsId = $this->addNewMessageDetail($messageId, $sender, $message);

        if(empty($messageDetailsId)){
            $this->rollbackTrans();
            return FALSE;
        }
        $this->endTransWithSucc();

        return $messageId;
    }

    /**
	 * detail: add new message details
	 *
	 * @param int $messageId messagesdetails/messages messageId
	 * @param string $sender messagesdetails sender
	 * @param string $message messagesdetails message
	 * @param int $flag messagesdetails flag
	 *
	 * @return bool|int
	 */
	public function addNewMessageDetail($messageId, $sender, $message) {
		$data = array(
            'messageId' => $messageId,
            'sender' => (empty($sender)) ? '' : $sender,
            'recipient' => '',
            'message' => $message,
            'date' => $this->utils->getNowForMysql(),
            'status' => static::MESSAGE_DETAILS_UNREAD,
            'flag' => 'player',
        );

		return $this->insertData('messagesdetails', $data);
	}

    public function addNewMessageDetailWithAdmin($messageId, $adminId, $sender, $message, $currentMessageDate = null) {
		$date = empty($currentMessageDate) ? $this->utils->getNowForMysql() : $currentMessageDate;
        $data = array(
            'messageId' => $messageId,
            'adminId' => $adminId,
            'sender' => (empty($sender)) ? '' : $sender,
            'recipient' => '',
            'message' => $message,
            'date' => $date,
            'status' => static::MESSAGE_DETAILS_UNREAD,
            'flag' => 'admin',
        );

        return $this->insertData('messagesdetails', $data);
    }

    /**
	 * detail: Will get messages history based on the session
	 *
	 * @param int $message_id messagesdetails message_id
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function getMessagesHistoryByMessageId($message_id, $limit, $offset, $order = 'a') {
		if ($limit != null) {
			$limit = "LIMIT " . $limit;
		}

		if ($offset != null && $offset != 'undefined') {
			$offset = "OFFSET " . $offset;
		} else {
			$offset = ' ';
		}

		$order = strtolower($order);
		$order_opt = in_array($order, [ 'desc', 'd' ]) ? 'DESC' : 'ASC';
		$order_clause = " ORDER BY messageDetailsId {$order_opt} ";

		$sql = "SELECT * FROM `messagesdetails` where messageId = ? {$order_clause} $limit $offset";

		$query = $this->db->query($sql, array($message_id));

		return $query->result_array();
	}

	/**
	 * detail: Will get messages status based on the session
	 *
	 * @param int $messages_id messages messageId
	 * @return int
	 */
	public function getMessagesStatusByMessageId($messages_id) {
		$sql = "SELECT * FROM `messages` where messageId = ? ";

		$query = $this->db->query($sql, array($messages_id));

		$result = $query->row_array();

		return $result['status'];
	}

	public function isDeleted($message_id){
        $sql = "SELECT * FROM `messages` where messageId = ? ";

        $query = $this->db->query($sql, array($message_id));

        $result = $query->row_array();

        return ($result['deleted'] > 0);
    }

	/**
	 * detail: delete a certain message
	 *
	 * @param int $message_id messages message_id
	 * @param int $userId messages deleted_by
	 *
	 * @return array
	 */
	public function deleteMessage($message_id, $userId = null) {
		$this->db->update('messages', array(
			'deleted' => self::DB_TRUE,
			'deleted_at' => $this->utils->getNowForMysql(),
			'deleted_by' => $userId,
		), array('messageId' => $message_id));
	}

	/**
	 * detail: delete Message by Date
	 *
	 * @param string $date '2023-01-01 00:00:00'
	 * @param int $playerId playerId
	 * @param int $userId messages deleted_by
	 *
	 * @return array
	 */
	public function deleteMessageByDate($date = null, $playerId = null, $userId = null)
	{
		if(empty($date) && empty($playerId)) {
			return false;
		}
		if ($date) {

			$this->db->where('date <=', "$date");
		}
		if ($playerId) {
			// $whereCon['playerId'] = $playerId;
			$this->db->where('playerId', $playerId, false);
		}

		$this->db->update('messages', array(
			'deleted' => self::DB_TRUE,
			'deleted_at' => $this->utils->getNowForMysql(),
			'deleted_by' => $userId,
		), ['deleted !=' => self::DB_TRUE]);

		$this->utils->debug_log($this->db->last_query());
		return ($this->db->affected_rows());
	}

	public function deleteBroadcastMessagesByDate($date = null, $message_id = null, $userId = null)
	{
		if(empty($date) && empty($message_id)) {
			return false;
		}

		if ($message_id) {
			// $whereCon['playerId'] = $playerId;
			$this->db->where('id', $message_id, false);
		} else 
		if ($date) {
			$this->db->where('date <=', "$date");
		}

		$this->db->update('broadcast_messages', array(
			'isDeleted' => self::DB_TRUE,
			'deletedAt' => $this->utils->getNowForMysql(),
			'deletedBy' => $userId,
		),['isDeleted !=' => self::DB_TRUE]);

		$this->utils->debug_log($this->db->last_query());
		return ($this->db->affected_rows());
	}

	public function deletePlayerBroadcastMessages($date = null, $broadcast_message_id = null, $userId = null)
	{
		if(empty($date) && empty($broadcast_message_id)) {
			return false;
		}


		if ($broadcast_message_id) {
			$this->db->where('broadcast_id', $broadcast_message_id, false);
		}else 
		if ($date) {
			$this->db->select('id');
			$this->db->from('broadcast_messages');
			$this->db->where('date <=', "$date");
			$subQuery = $this->db->_compile_select();

			$this->db->_reset_select();
			$this->db->where("messages.broadcast_id in ($subQuery)");
		}		

		$this->db->update('messages', array(
			'deleted' => self::DB_TRUE,
			'deleted_at' => $this->utils->getNowForMysql(),
			'deleted_by' => $userId,
		), ['deleted !=' => self::DB_TRUE]);

		$this->utils->debug_log($this->db->last_query());
		return ($this->db->affected_rows());
	}

	/**
	 * detail: Will get the messages
	 *
	 * @param int $user_id
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function getAdminMessages($user_id, $limit, $offset) {

		$this->db->select('c.messageId,
						  c.message_type,
						  c.playerId,
						  c.adminId,
						  c.session,
						  c.subject,
						  c.date,
						  c.status,
						  c.deleted,
						  c.deleted_at,
						  c.deleted_by,
						  p.username AS sender,
						  MIN(cd.status) AS isread');
		$this->db->from('messages AS c');
		$this->db->join('messagesdetails AS cd', 'cd.messageId = c.messageId', 'left');
		$this->db->join('player AS p', 'p.playerId = c.playerId', 'left');
		$this->db->join('adminusers AS au', 'au.userId = c.adminId', 'left');
		$this->db->where('c.deleted', 0);
		$this->db->group_by('c.messageId');
		$this->db->order_by('c.messageId','ASC');

		if ($limit != null||$limit != 0) {
			$this->db->limit($limit,$offset);
		}

		$query = $this->db->get();
		return $this->db->last_query();exit;
	}

	/**
	 * detail: mark message as close
	 *
	 * @param int $message_id messages messageId
	 * @param int $user_id
	 * @return Boolean
	 */
	public function markAsClose($message_id, $user_id=null) {
		//set disabled
        $this->db->update('messages', array(
            'admin_unread_count' => 0,
            'player_unread_count' => 0,
            'status' => self::STATUS_DISABLED,
            'admin_last_reply_id' => $user_id,
            'admin_last_reply_dt' => $this->utils->getNowForMysql(),
        ), array('messageId' => $message_id));
		//set read
		$this->db->set('status', self::MESSAGE_DETAILS_READ)->where('messageId', $message_id);
		$this->runAnyUpdate('messagesdetails');
	}

	/**
	 * detail: count unread messages for a certain player
	 *
	 * @param int $player_id messages playerId
	 * @return int
	 */
    public function countPlayerUnreadMessages($player_id) {
        $result = $this->playerUnreadMessages($player_id);

        return (empty($result)) ? 0 : count($result);
    }

    /**
     * detail: count unread messages
     */
    public function countAdminTotalUnreadMessages() {
        $this->db->select('COUNT(messageId) as cnt')
            ->from('messages')
            ->where('deleted', 0)
            ->where_in('status', [self::STATUS_NEW, self::STATUS_UNPROCESSED])
        ;

        $cnt = $this->runOneRowOneField('cnt');

        return (empty($cnt)) ? 0 : (int)$cnt;
    }

    public function countTotalUnreadByMessageId($message_id){
        $this->db->select('flag, COUNT(messageDetailsId) as cnt')
            ->from('messagesdetails')
            ->where('messageId', $message_id)
            ->where('status', self::MESSAGE_DETAILS_UNREAD)
            ->group_by('flag')
        ;

        $rows = $this->runMultipleRowArray();

        $result = [
            'admin' => 0,
            'player' => 0
        ];
        if(empty($rows)){
            return $result;
        }

        foreach($rows as $entry){
            $result[$entry['flag']] = $entry['cnt'];
        }

        return $result;
    }

	/**
	 * detail: remove Message from checkbox selection
	 *
	 * @param int $messagecmsId
	 * @return Boolean
	 */
	public function deleteMessageFromCheckbox($user_id, $messagecmsId) {
        $fields = array(
            'deleted' => self::DB_TRUE,
            'deleted_at' => $this->utils->getNowForMysql(),
            'deleted_by' => $user_id,
        );

		$this->db->where('messageId', $messagecmsId);

        $this->db->update('messages', $fields);

        return ($this->db->affected_rows());
	}

    /**
     * detail: update status to read of a message
     *
     * @param int $messageId messages messageId
     * @return Boolean
     */
    public function updateMessageStatus($messageId, $flag, $status = NULL, $detail_status = NULL, $player_id = NULL) {
        if(!empty($status)){
            $this->db->set('status', $status)->where('messageId', $messageId);
            if(!empty($player_id)){
                $this->db->where('playerId', $player_id);
            }
            $this->runAnyUpdate('messages');
        }

        if(!empty($detail_status)){
            $this->db->set('status', $detail_status)->where('messageId', $messageId)->where('flag', $flag);
            $this->runAnyUpdate('messagesdetails');
        }
    }

	/**
     * detail: update unread message readtime
     */
	public function updateUnreadMessageReadtime($messageId, $flag) {
		$this->db->set('read_At', $this->utils->getNowForMysql())->where('messageId', $messageId)->where('flag', $flag);
        $this->runAnyUpdate('messagesdetails');
	}

    /**
     * detail: update messages
     *
     * @param array $messages
     * @param int $message_id messages messageId
     * @return 	void
     */
    public function updateMessages($messages, $message_id) {
        $this->db->where('messageId', $message_id);
        $this->db->update('messages', $messages);
    }

    /**
     * detail: update messages details
     *
     * @param array $message_details
     * @param int $message_id messagesdetails messageId
     *
     * @return Boolean
     */
    public function updateMessagesDetailsByMessageId($messages_details, $message_id, $flag = NULL) {
        $this->db->where('messageId', $message_id);
        if(!empty($flag)){
            $this->db->where('flag', $flag);
        }
        $this->db->update('messagesdetails', $messages_details);

        return ($this->db->affected_rows());
    }

    /**
     * Check if a message belong to given player
     * @param	int		$messageId	== message.messageId
     * @param	int		$playerId	== message.playerId
     * @return	bool	true if the message belongs to the player; otherwise false
     */
    public function checkMesgOwnership($messageId, $playerId) {
    	$this->db->from($this->tableName)
    		->select('COUNT(*) AS num')
    		->where('messageId', $messageId)
    		->where('playerId', $playerId)
    	;

    	$num = $this->runOneRowOneField('num');

    	$this->utils->printLastSQL();

    	return $num > 0;
    }

    //BroadcastMessage
    public function addNewBroadcastMessageAdmin($user_id, $subject, $message){
		$this->startTrans();

        $broadcastMessageId = $this->insertData('broadcast_messages', array(
			'adminId' => $user_id,
			'date' => $this->utils->getNowForMysql(),
			'status' => self::STATUS_ADMIN_NEW,
			'subject' => $subject,
			'message' => $message,
		));

        if(empty($broadcastMessageId)){
            $this->rollbackTrans();
            return FALSE;
        }

        $this->endTransWithSucc();

        return $broadcastMessageId;
    }

    public function getBroadcastMessages($broadcast_id = null, $broadcast_id_list = [], $player_registr_date = null){
		$this->db->select('b.id as broadcastId,
						  b.adminId,
						  b.date,
						  (CASE WHEN datediff(b.date, now()) > 1 THEN 4 ELSE b.status END ) as status,
						  b.subject,
						  b.message as detail,
						  b.isDeleted,
						  b.deletedAt,
						  b.deletedBy,
						  b.externalId as messageId,');
		$this->db->from('broadcast_messages AS b');
		$this->db->where('b.isDeleted', 0);

		if(!empty($broadcast_id)){
		    $this->db->where('b.id', $broadcast_id);
        }

        if (!empty($broadcast_id_list)) {
			$this->db->where_not_in('b.id', $broadcast_id_list);
        }

        if (!empty($player_registr_date)) {
			$this->db->where('b.date >=', $player_registr_date);
        }

		return $this->runMultipleRowArray();
    }

    public function getPlayerbroadcastId($player_id){
		$this->db->select('broadcast_id')
            ->from('messages')
            ->where('playerId', $player_id)
            ->where('deleted', 0)
            ->where('broadcast_id IS NOT NULL');

        $cnt = $this->runMultipleRowArray();

        return (empty($cnt)) ? [] : array_column($cnt,'broadcast_id');
    }

	public function getBroadcastDate($broadcast_id){
		if(empty($broadcast_id)){
			return $this->util->getNowForMysql();
		}
		$this->db->select('date');
		$this->db->from('broadcast_messages');
		$this->db->where('id', $broadcast_id);
		return $this->runOneRowOneField('date');

		
	}

	public function addNewMessageFromBroadcast($broadcast_id, $username){
		$broadcast = $this->getBroadcastMessages($broadcast_id);
		$this->utils->debug_log(__METHOD__, 'getBroadcastMessages', $broadcast);
		if (empty($broadcast)) {
			return false;
		}

		$this->load->model('player_model','users');

		$userId = $broadcast[0]['adminId'];
		$subject = $broadcast[0]['subject'];
		$message = $broadcast[0]['detail'];
		$sender = $this->users->getUserUsernameByid($userId);
		$player_id = $this->player_model->getPlayerIdByUsername($username);
		$message_id = $this->addNewMessageAdmin($userId, $player_id, $sender, $subject, $message, FALSE, FALSE, $broadcast_id);

		if ($message_id) {
			return $message_id;
		}
		return false;
	}

	/**
	 * overview : reset messages broadcast_id column default value to null
	 */
	public function resetMessagesBroadcastIdDefaultToNull() {
		$this->db->where('broadcast_id', '0')->set(['broadcast_id' => NULL]);
		$this->runAnyUpdate('messages');
		return $this->db->affected_rows();
	}

	public function getAllMessages($playerId=null , $status=null, $admin_unread=null, $from=null, $to=null, $limit = null, $page = null){
		$result = $this->getDataWithAPIPagination($this->tableName, function() use($playerId, $status, $admin_unread, $from, $to) {
            $this->db->select('messages.messageId AS id,messagesdetails.sender AS sender, messages.message_type, messagesdetails.flag, messages.subject, messages.date, messages.status AS new, messages.player_unread_count AS admin_unread, messages.status AS status, messages.playerId AS playerId, adminusers.username AS operator');

            $this->db->join('messagesdetails', 'messagesdetails.messageId = messages.messageId', 'left');
            $this->db->join('adminusers', 'adminusers.userId = messages.adminId', 'left');

            if (!empty($playerId)) {
				$this->db->where('messages.playerId', $playerId);
            }

            if (!empty($status)) {
				if ($status == Internal_message::STATUS_DISABLED) {
					$this->db->where('messages.status', Internal_message::STATUS_DISABLED);
				}
			}

            if (!empty($admin_unread)) {
                switch($admin_unread){
                    case 'admin_unread':
						$this->db->where('messages.player_unread_count', Internal_message::MESSAGE_ADMIN_UNREAD);

                        break;
                    case 'admin_read':
						$this->db->where('messages.player_unread_count', Internal_message::MESSAGE_ADMIN_READ);
                        break;
                }
            }

            if (!empty($from) && !empty($to)) {
                $this->db->where('messages.date BETWEEN ' . $this->db->escape($from) . ' AND ' . $this->db->escape($to) . '');
            }

            $this->db->where('messages.deleted', 0);
            $this->db->group_by('messages.messageId');
            $this->db->order_by('messages.date', 'desc');
        }, $limit, $page);

		$this->CI->utils->debug_log(__METHOD__, 'result', $result);
		$this->load->library(['player_message_library']);
		$default_admin_sender_name = $this->player_message_library->getDefaultAdminSenderName();
        $request_default_guest_name = lang('message.request_form.default_guest_name');
        $this->CI->utils->debug_log(__METHOD__, $default_admin_sender_name, $request_default_guest_name);

        foreach($result['list'] as &$entry) {
            $username = $this->player_model->getUsernameById($entry['playerId']);
            $sender = $entry['sender'];
            $received_by = $username;
            if($entry['message_type'] == Internal_message::MESSAGE_TYPE_REQUEST_FORM){
                if($entry['flag'] === 'admin'){
                    $sender = $default_admin_sender_name;
                }else{
                    if(empty($entry['playerId'])){
                        $sender = $request_default_guest_name;
                    }else{
                        $sender = $sender;
                    }
                }
            }else{
                if($entry['flag'] === 'admin'){
                    $sender = $default_admin_sender_name;
                }else{
                    $sender = $sender;
                }
            }
            if($entry['message_type'] == Internal_message::MESSAGE_TYPE_REQUEST_FORM){
                if($entry['flag'] === 'admin'){
                    if(empty($entry['playerId'])){
                        $received_by = $request_default_guest_name;
                    }else{
                        $received_by = $username;
                    }
                }else{
                    $received_by = $default_admin_sender_name;
                }
            }else{
                if($entry['flag'] === 'admin'){
                    $received_by = $username;
                }else{
                    $received_by = $default_admin_sender_name;
                }
            }
            $entry['sender'] = $sender;
            $entry['received_by'] = $received_by;
            $entry['admin_unread'] = (int)$entry['admin_unread'];
            $entry['id'] = (int)$entry['id'];
            $entry['new'] = $entry['new'] == Internal_message::STATUS_NEW ? 1 : 0;
            $entry['status'] = $entry['status'] == Internal_message::STATUS_DISABLED ? lang('lang.close') : lang('lang.open');
            unset($entry['message_type'], $entry['flag'], $entry['playerId']);
        }
        return $result;
	}
}

///END OF FILE/////
