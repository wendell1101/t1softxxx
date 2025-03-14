<?php

trait affiliate_command_module {

    /**
     * do_hide_affiliate_by_csv
     *
     * P.S. This command is Not Yet supported in enabled mdb.
     *
     * $config['inactive_affiliate_csv_file']
     * sudo /bin/bash admin/shell/command.sh do_hide_affiliate_by_csv > ./logs/command_do_hide_affiliate_by_csv.log 2>&1 &
     *
     * In mdb:
     * bash ./admin/shell/command_mdb_noroot.sh usd do_hide_affiliate_by_csv > ./logs/command_do_hide_affiliate_by_csv_usd.log 2>&1 &
     *
	 *
     * for revert, (remove hide)
     * sudo /bin/bash admin/shell/command.sh do_hide_affiliate_by_csv 1 > ./logs/command_do_hide_affiliate_by_csv.log 2>&1 &
     * @param boolean $do_revert If it's true, that's means to remove the hiden mark of the affiliate.
     *
     * @return void
     */
	public function do_hide_affiliate_by_csv($do_revert = false){

        $this->load->model(['operatorglobalsettings', 'affiliatemodel']);

        $_csv_file =  $this->utils->getConfig('inactive_affiliate_csv_file');
        $ignore_first_row = true;

        $args_in_loopCSV = [];

        $args_in_loopCSV['do_revert'] = $do_revert;
        $args_in_loopCSV['filename'] = $_csv_file;
        $args_in_loopCSV['ignore_first_row'] = $ignore_first_row;
        $args_in_loopCSV['csv_header'] = [ 'AffiliateCode' // #1 for the field, "affiliates.username"
                                    , 'AffTrackingCode' // #2 for the field,"affiliates.trackingCode".
                                    , 'Realname'
                                    , 'Email'
                                ];
        //  for dynamically specified the column in username of affiliate.
        $_csv_header_in_config = $this->utils->getConfig('inactive_affiliate_csv_header');
        if( ! empty($_csv_header_in_config) ){
            $args_in_loopCSV['csv_header'] = $_csv_header_in_config;
        }

        $args_in_loopCSV['column_count'] = count($args_in_loopCSV['csv_header']);
        $args_in_loopCSV['failed_list'] = [];
        $args_in_loopCSV['failed_count'] = 0;
        $args_in_loopCSV['success_count'] = 0;
        $args_in_loopCSV['row_count'] = 0;
        $args_in_loopCSV['rlt_details'] = [];


        // $uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		// $csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltAff['filename'];

    	if( ! file_exists( $args_in_loopCSV['filename'] ) ){
    		return $this->utils->error_log("File not exist! ". $args_in_loopCSV['filename']);
    	}

        $fp = file($args_in_loopCSV['filename']);// this one works
		$totalCount = count($fp);
		$fp = [];
		unset($fp);
        if($ignore_first_row){
            $totalCount--;
        }
        $args_in_loopCSV['row_count_in_prepare'] = $totalCount;

        $csv_file = $args_in_loopCSV['filename'];
        $controller = $this;
        $cnt = 0;
        $message = '';
		$this->utils->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
            use($controller, &$args_in_loopCSV) {

                $controller->utils->debug_log("compare column headings" , $args_in_loopCSV['csv_header'], $csv_row);
				$row = $controller->utils->combine_arr($args_in_loopCSV['csv_header'], $csv_row);
                $row = $controller->utils->_extract_row($row);
                $do_revert = $args_in_loopCSV['do_revert'];

                $is_hide  =null;
                $affId = $controller->affiliatemodel->getAffiliateIdByUsername($row['AffiliateCode']);
                if( ! empty($affId) ){
                    $is_hide = $controller->affiliatemodel->is_hide($affId);
                }

                if( empty($affId) ){
                    // Not Found
                    $args_in_loopCSV['failed_count']++;
                    array_push($args_in_loopCSV['failed_list'], $row);

                    $_rlt_no = 'notFoundAffiliateIdByUsername';

                }else if( $is_hide
                        && ! is_null($is_hide) // Not be default
                        && $do_revert == false // do hide
                ){
                    // that had already be hidden
                    $args_in_loopCSV['success_count']++;

                    $_rlt_no = Affiliatemodel::DO_HIDE_RLT_NO_IN_HAD_ALREADY_HIDDEN;

                }else if(  ! $is_hide
                        && !is_null($is_hide) // Not be default
                        && $do_revert == false // do hide
                ){
                    // to mark hidden
                    $rlt = $controller->affiliatemodel->mark_hide($affId);
                    $_rlt_no = $rlt['rlt_no'];

                    if( ! $rlt['bool'] ){
                        $args_in_loopCSV['failed_count']++;
                        array_push($args_in_loopCSV['failed_list'], $row);
                    }else{
                        $args_in_loopCSV['success_count']++;
                    }
                }else if(  $is_hide
                        && !is_null($is_hide) // Not be default
                        && $do_revert !== false // do re-appear
                ){
                    // to remove hidden mark to appear
                    $rlt = $controller->affiliatemodel->remove_hide($affId);
                    $_rlt_no = $rlt['rlt_no'];

                    if( ! $rlt['bool'] ){
                        $args_in_loopCSV['failed_count']++;
                        array_push($args_in_loopCSV['failed_list'], $row);
                    }else{
                        $args_in_loopCSV['success_count']++;
                    }

                }else if(  ! $is_hide
                        && !is_null($is_hide) // Not be default
                        && $do_revert !== false // do re-appear
                ){
                    // that had already be appear
                    $args_in_loopCSV['success_count']++;

                    $_rlt_no = Affiliatemodel::DO_HIDE_RLT_NO_IN_REMOVE_HIDE_ALREADY_APPEARS;
                }

                if( empty($args_in_loopCSV['rlt_details'][$_rlt_no])){
                    $args_in_loopCSV['rlt_details'][$_rlt_no] = []; // initial
                }
                array_push($args_in_loopCSV['rlt_details'][$_rlt_no], $row['AffiliateCode']);

                switch($_rlt_no){
                    case Affiliatemodel::DO_HIDE_RLT_NO_IN_HIDDEN_COMPLETED:
                    case Affiliatemodel::DO_HIDE_RLT_NO_IN_REMOVE_HIDE_COMPLETED:
                        if( !! $controller->utils->isEnabledMDB() && ! empty($affId) ){
                            // Sync to others
                            $controller->load->model(['multiple_db_model']);
                            $_rlt = $controller->multiple_db_model->syncAffFromCurrentToOtherMDB($affId, true);
                            $controller->utils->debug_log('syncAffFromCurrentToOtherMDB', $_rlt, 'affId', $affId);
                        }else{
                            $controller->utils->debug_log('ignore syncAffFromCurrentToOtherMDB() By Empty affId');
                        }
                    break;

                    default: // others in data still keep original.
                        $controller->utils->debug_log('ignore syncAffFromCurrentToOtherMDB() as that data still keep as original.');
                    break;
                }

                $args_in_loopCSV['row_count']++;
                $controller->utils->debug_log('Process count:', $args_in_loopCSV['row_count'], 'total count:', $args_in_loopCSV['row_count_in_prepare'], 'row:', $row);
        });

        $this->utils->debug_log('Process aff file:', $args_in_loopCSV['filename']);
        $this->utils->debug_log('Result success_count:', $args_in_loopCSV['success_count']
                                        , 'failed_count', $args_in_loopCSV['failed_count']
                                        , 'row_count', $args_in_loopCSV['row_count']
                                );
        $this->utils->debug_log('Result failed list:', $args_in_loopCSV['failed_list']);

        // convert Result No to String
        if( !empty($args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_HIDDEN_COMPLETED])){
            $args_in_loopCSV['rlt_details']['hiddenCompleted'] = $args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_HIDDEN_COMPLETED];
            unset($args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_HIDDEN_COMPLETED]);
        }

        if( !empty($args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_HIDDEN_FAILED])){
            $args_in_loopCSV['rlt_details']['hiddenFailed'] = $args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_HIDDEN_FAILED];
            unset($args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_HIDDEN_FAILED]);
        }

        if( !empty($args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_HAD_ALREADY_HIDDEN])){
            $args_in_loopCSV['rlt_details']['hiddenAlreadyExists'] = $args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_HAD_ALREADY_HIDDEN];
            unset($args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_HAD_ALREADY_HIDDEN]);
        }

        if( !empty($args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_REMOVE_HIDE_COMPLETED])){
            $args_in_loopCSV['rlt_details']['removeHideCompleted'] = $args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_REMOVE_HIDE_COMPLETED];
            unset($args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_REMOVE_HIDE_COMPLETED]);
        }

        if( !empty($args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_REMOVE_HIDE_FAILED])){
            $args_in_loopCSV['rlt_details']['removeHideFailed'] = $args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_REMOVE_HIDE_FAILED];
            unset($args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_REMOVE_HIDE_FAILED]);
        }

        if( !empty($args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_REMOVE_HIDE_ALREADY_APPEARS])){
            $args_in_loopCSV['rlt_details']['hiddenAlreadyNotExists'] = $args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_REMOVE_HIDE_ALREADY_APPEARS];
            unset($args_in_loopCSV['rlt_details'][Affiliatemodel::DO_HIDE_RLT_NO_IN_REMOVE_HIDE_ALREADY_APPEARS]);
        }

        $this->utils->debug_log('Result details:', $args_in_loopCSV['rlt_details']);

    } // EOF do_hide_affiliate_by_csv

}