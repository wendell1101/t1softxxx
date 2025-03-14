<?php

/**
 * General behaviors include
 * * get messages report
 *
 * @category report_module_messages_list
 * @version 1.0.0
 * @copyright 2013-2022 tot
 */
trait report_module_messages_list
{

    /**
     * detail: get player reports with additional roulette
     * for C043-smash
     * @param array $request
     * @param Boolean $viewPlayerInfoPerm
     * @param Boolean $is_export
     *
     * @return array
     */
	public function export_message_list_report($request, $is_export = false) {        
            $this->load->library('data_tables');
            $this->load->library('player_message_library');
		    $this->load->model(array('player_model'));

		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
        $default_admin_sender_name = $this->player_message_library->getDefaultAdminSenderName();
        // $default_admin_sender_name = "admin";
        $request_default_guest_name = lang('message.request_form.default_guest_name');

        $columns = array(
			array(
				'dt'=>(!$is_export) ? $i++ : NULL,
				'alias' => 'messageId',
				'select' => 'messages.messageId',
            ),
			array(
				'alias' => 'message_type',
				'select' => 'messages.message_type',
			),  
            array(
				'dt'=>(!$is_export) ? $i++ : NULL,
				'alias' => 'messageId',
				'select' => 'messages.messageId',
				'formatter'=>function ($d,$row)use ($is_export) {
                    if(!$is_export){
                        $output = 
                        '<td style="text-align:center;">
                        <input type="checkbox" data-checked-all-for="checkWhite" class="checkWhite" id="'.$row['messageId'].'" name="messagecms[]" value="'.$row['messageId'].'" onclick="uncheckAll(this.id)" data-player-id="' . $row['playerId']. '" data-player-name="' . $row['playerUsername'] .'" />
                        </td>';
                        return $output;
                    }
                    return $d;
                }
            ),
			array(
				'alias' => 'playerId',
				'select' => 'messages.playerId',
			),
            array(
                'alias' => 'playerUsername',
                'select' => 'player.username',
			),
            array(
				'alias' => 'adminId',
				'select' => 'messagesdetails.adminId',
			),
            array(
				'alias' => 'flag',
				'select' => 'messagesdetails.flag',
			),
            array(
				'alias' => 'affiliates_username',
				'select' => 'receiver_affiliates.username',
			),
            array(
                'dt'=>$i++,
                'alias' => 'sender',
                'select' => 'messagesdetails.sender',
                'name' => lang('Sender'),
                'formatter' => function ($d, $row) use ($is_export,$default_admin_sender_name, $request_default_guest_name) {
                    if(!$is_export){
                        $sender_name_format = '<a target="_blank" href="/player_management/userInformation/%1$s" data-player-id="%1$s" data-player-name="%2$s">%3$s</a>';
                        if($row['message_type'] == Internal_message::MESSAGE_TYPE_REQUEST_FORM){
                            if($row['flag'] === 'admin'){
                                return $default_admin_sender_name;
                            }else{
                                if(empty($row['playerId'])){
                                    return $request_default_guest_name;
                                }else{
                                    return sprintf($sender_name_format, $row['playerId'], $d, (!empty($row['affiliates_username'])) ? $d . ' (' . $row['affiliates_username'] . ')' : $d);
                                }
                            }
                        }else{
                            if($row['flag'] === 'admin'){
                                return $row['sender'];
                            }else{
                                return sprintf($sender_name_format, $row['playerId'], $d, (!empty($row['affiliates_username'])) ? $d . ' (' . $row['affiliates_username'] . ')' : $d);
                            }
                        }
                    }
                    return $d;
                }
            ),
            array(
                'dt'=>$i++,
                'alias' => 'recieve',
                'select' => 'player.username',
                'name' => lang('Received By'),
                'formatter' => function ($d, $row) use ($is_export, $default_admin_sender_name, $request_default_guest_name) {
                    if(!$is_export){
                        $receive_name_format = '<a target="_blank" href="/player_management/userInformation/%1$s" data-player-id="%1$s" data-player-name="%2$s">%3$s</a>';
                        if($row['message_type'] == Internal_message::MESSAGE_TYPE_REQUEST_FORM){
                            if($row['flag'] === 'admin'){
                                if(empty($row['playerId'])){
                                    return $request_default_guest_name;
                                }else{
                                    return sprintf($receive_name_format, $row['playerId'], $d, (!empty($row['affiliates_username'])) ? $d . ' (' . $row['affiliates_username'] . ')' : $d);
                                }
                            }else{
                                return $default_admin_sender_name;
                            }
                        }else{
                            if($row['flag'] === 'admin'){
                                return sprintf($receive_name_format, $row['playerId'], $d, (!empty($row['affiliates_username'])) ? $d . ' (' . $row['affiliates_username'] . ')' : $d);
                            }else{
                                return $default_admin_sender_name;
                            }
                        }
                    }
                    return $d;
                },
            ),
            array(
                'dt'=>$i++,
                'alias' => 'adminUsername',
                'select' => 'adminusers.username',
                'name' => lang('Operator'),
                'formatter' => function ($d, $row) use ($is_export) {
                    return $d;
                },
            ),
            array(
				'dt'=>$i++,
				'alias' => 'subject',
				'select' => 'messages.subject',
                'name' => lang('Subject'),
                'formatter'=>function ($d,$row) use ($is_export){
                    if(!$is_export){
                        return '
                        <td style="text-align:center;">
                        <a href="javascript: void(0);" data-toggle="tooltip" title="'.lang('tool.cs01').'" onclick="message_reply_message(\''.$row['messageId'].'\');"><span style="display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; max-width:100px;">' .  $d. '</span> </a>
				    </td>';
                    }else{
                        return $d;
                    }
                }
			),
            array(
				'dt'=>$i++,
				'alias' => 'date',
				'select' => 'messages.date',
                'name' => lang('Date'),
                'formatter' => function ($d, $row) use ($is_export) {
                    return $d;
                },
			),
            array(
				'dt'=>$i++,
				'alias' => 'read_At',
				'select' => 'max(messagesdetails.read_At)',
                'name' => lang('Read Timestamp'),
                'formatter' => function ($d, $row) use ($is_export) {
                    return $d;
                },
			),
            array(
                'dt'=>$i++,
                'alias' => 'status',
                'select' => 'messages.status',
                'name' => lang('New'),
                'formatter'=>function ($d,$row) use ($is_export) {
                    if(!$is_export){
                        $output = '';
                        if ($d == Internal_message::STATUS_NEW) {
                            $output .= '<span class="glyphicon glyphicon-ok text-success"></span>';
                        } else {
                            $output .= '<span class="glyphicon glyphicon-remove text-danger"></span>';
                        }
                        return '<td style="text-align:center;">' . $output . '</td>';
                    }else{
                        if ($d == Internal_message::STATUS_NEW) {
                            return "O";
                        } else {
                            return "X";
                        }
                    }
                }
            ),
            array(
                'dt'=>$i++,
                'alias' => 'admin_unread_count',
                'select' => 'messages.admin_unread_count',
                'name' => lang('Admin Unread'),
                'formatter' => function ($d, $row) use ($is_export) {
                    return $d;
                },
            ),
            array(
                'dt'=>$i++,
                'alias' => 'player_unread_count',
                'select' => 'messages.player_unread_count',
                'name' => lang('Player Unread'),
                'formatter' => function ($d, $row) use ($is_export) {
                    return $d;
                },
            ),
            array(
                'dt'=>$i++,
                'alias' => 'isclose',
                'select' => 'messages.status',
                'name' => lang('Status'),
                'formatter'=>function ($d,$row) use ($is_export) {
                    if ($d == Internal_message::STATUS_DISABLED) {
                        return lang('lang.close');
                    } else {
                        return lang('lang.open');
                    }
                }
            ),
			array(
				'alias' => 'session',
				'select' => 'messages.session',
			),
		);
		# END DEFINE COLUMNS #################################################################################################################################################

		$table = 'messages';
		$joins = array(
			'messagesdetails' => 'messagesdetails.messageId = messages.messageId',
			'player'=>'player.playerId = messages.playerId',
			'adminusers'=>'adminusers.userId = messages.adminId',
			'affiliates as receiver_affiliates' => 'receiver_affiliates.affiliateId = player.affiliateId',
		);		
		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$group_by = [ 'messages.messageId' ];

		$input = $this->data_tables->extra_search($request);

        if (isset($input['adminUserId'])) {
            $where[] = "messagesdetails.adminId = '".$this->db->escape_like_str($input['adminUserId'])."'";
            // $values[] = $input['messages'];
        }

        if (isset($input['messages'])) {
            $where[] = "messagesdetails.message LIKE '%".$this->db->escape_like_str($input['messages'])."%'";
            // $values[] = $input['messages'];
        }

        if (isset($input['subject'])) {
            $where[] = "messages.subject LIKE '%".$this->db->escape_like_str($input['subject'])."%'";
            // $values[] = $input['subject'];
        }

        if (isset($input['playerUsername'])) {
            $where[] = "(player.username LIKE '%".$this->db->escape_like_str($input['playerUsername'])."%')";
            // $values[] = $input['sender'];
        }

        if (isset($input['date_from'], $input['date_to'])) {
            $where[] = "messages.date >=?";
            $where[] = "messages.date <=?";
            $values[] = $input['date_from'];
            $values[] = $input['date_to'];
        }

        if (isset($input['adminunread'])) {
            switch($input['adminunread']){
                case 'admin_unread':
                    $where[] = "messages.player_unread_count = " . Internal_message::MESSAGE_ADMIN_UNREAD;
                    break;
                case 'admin_read':
                    $where[] = "messages.player_unread_count = " . Internal_message::MESSAGE_ADMIN_READ;
                    break;
            }
        }

        if (isset($input['status'])) {
            switch($input['status']){
                case 'player_new':
                    $where[] = "messages.status = " . Internal_message::STATUS_NEW;
                    break;
                case 'unprocessed':
                    $unprocessed_status = [Internal_message::STATUS_NEW, Internal_message::STATUS_UNPROCESSED];
                    $where[] = "messages.status IN (" . implode(',', $unprocessed_status) . ")";
                    break;
                case 'processed':
                    $unprocessed_status = [Internal_message::STATUS_PROCESSED, Internal_message::STATUS_NORMAL, Internal_message::STATUS_READ];
                    $where[] = "messages.status IN (" . implode(',', $unprocessed_status) . ")";
                    break;
                case 'admin_new':
                    $unprocessed_status = [Internal_message::STATUS_ADMIN_NEW];
                    $where[] = "messages.status IN (" . implode(',', $unprocessed_status) . ")";
                    break;
                case 'admin_read':
                    $unprocessed_status = [Internal_message::STATUS_READ];
                    $where[] = "messages.status IN (" . implode(',', $unprocessed_status) . ")";
                    break;
                case 'markclose':
                    $where[] = "messages.status = " . Internal_message::STATUS_DISABLED;
                    break;
            }
        }
        $where[] = "messages.deleted = 0";


		if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins, $group_by);

		if($is_export){
		    //drop result if export
			return $csv_filename;
		}

		if( ! empty($this->data_tables->last_query) ){
			$result['sqls'] = $this->data_tables->last_query;
		}

		return $result;
	}
}
////END OF FILE/////////
