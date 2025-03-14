<?php

/**
 * General behaviors include
 * * get messages report
 *
 * @category report_module_player_remarks_list
 * @version 1.0.0
 * @copyright 2013-2022 tot
 */
trait report_module_player_remarks_list
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
	public function export_player_remarks_report($request, $is_export = false) {        
            $this->load->library('data_tables');
		    $this->load->model(array('player_model'));
		# START DEFINE COLUMNS #################################################################################################################################################
		$i = 0;
        $columns = array(
			array(
				'dt'=>$i++,
				'alias' => 'playername',
				'select' => 'player.username',
				'name' => lang('Player Name'),
                'formatter' => function ($d, $row) use ($is_export) {
                    return $d;
                }
            ),
			array(
				'dt'=>$i++,
				'alias' => 'date',
				'select' => 'playernotes.createdOn',
				'name' => lang('Date'),
                'formatter' => function ($d, $row) use ($is_export) {
                    return $d;
                }
			),  
			array(
				'dt'=>$i++,
				'alias' => 'message',
				'select' => 'playernotes.notes',
				'name' => lang('Message'),
                'formatter' => function ($d, $row) use ($is_export) {
                    return $d;
                }
			),
            array(
				'dt'=>$i++,
				'alias' => 'operator',
				'select' => 'adminusers.username',
				'name' => lang('Operator'),
                'formatter' => function ($d, $row) use ($is_export) {
                    return $d;
                }
			),
            array(
                'dt'=>$i++,
                'alias' => 'category',
                'select' => 'tag_remarks.tagRemarks',
                'name' => lang('Category'),
                'formatter' => function ($d, $row) use ($is_export) {
                    return $d;
                }
            ),
			// array(
			// 	'dt'=>(!$is_export) ? $i++ : NULL,
			// 	'alias' => 'messageId',
			// 	'select' => 'messages.messageId',
			// 	'formatter'=>function ($d,$row)use ($is_export) {
            //         if(!$is_export){
            //             $output = 
            //             '<td style="text-align:center;">
            //             <input type="checkbox" data-checked-all-for="checkWhite" class="checkWhite" id="'.$row['messageId'].'" name="messagecms[]" value="'.$row['messageId'].'" onclick="uncheckAll(this.id)" data-player-id="' . $row['playerId']. '" data-player-name="' . $row['playerUsername'] .'" />
            //             </td>';
            //             return $output;
            //         }
            //         return $d;
            //     }
            // ),
		);
		# END DEFINE COLUMNS #################################################################################################################################################
		$table = 'playernotes';
		$joins = array(
			'player' => 'player.playerId = playernotes.playerId',
			'adminusers'=>'adminusers.userId = playernotes.userId',
			'tag_remarks'=>'tag_remarks.remarkId = playernotes.tag_remark_id',
		);		
		# START PROCESS SEARCH FORM #################################################################################################################################################
		$where = array();
		$values = array();
		$group_by = [ 'playernotes.playerId' ];

		$input = $this->data_tables->extra_search($request);

        if (isset($input['tag_remark'])) {
            $where[] = "playernotes.tag_remark_id = ?";
            $values[] = $input['tag_remark'];
        }

        if (isset($input['operator'])) {
            $where[] = "playernotes.userId = ?";
            $values[] = $input['operator'];
        }

        if (isset($input['playerUsername'])) {
            $where[] = "(player.username LIKE '%".$this->db->escape_like_str($input['playerUsername'])."%')";
            // $values[] = $input['sender'];
        }

        if (isset($input['date_from'], $input['date_to'])) {
            $where[] = "playernotes.createdOn >=?";
            $where[] = "playernotes.createdOn <=?";
            $values[] = $input['date_from'];
            $values[] = $input['date_to'];
        }

        $where[] = "playernotes.status = 1";


		if($is_export){
            $this->data_tables->options['is_export']=true;
            if(empty($csv_filename)){
                $csv_filename=$this->utils->create_csv_filename(__FUNCTION__);
            }
            $this->data_tables->options['csv_filename']=$csv_filename;
		}

		$result = $this->data_tables->get_data($request, $columns, $table, $where, $values, $joins);

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
