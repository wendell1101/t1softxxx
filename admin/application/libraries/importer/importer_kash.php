<?php if (!defined('BASEPATH')) {

	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/abstract_importer.php';

/**
 * Cloned form Importer_ole
 */
class Importer_kash extends Abstract_importer{

	function __construct() {
		parent::__construct();

		$this->import_player_csv_header=self::IMPORT_PLAYER_CSV_HEADER; // OGP-28059
		$this->import_player_contact_csv_header=self::IMPORT_PLAYER_CONTACT_CSV_HEADER;
		$this->import_player_bank_csv_header=self::IMPORT_PLAYER_BANK_CSV_HEADER;
		$this->import_aff_csv_header=self::IMPORT_AFF_CSV_HEADER; // OGP-28059
		$this->import_aff_contact_csv_header=self::IMPORT_AFF_CONTACT_CSV_HEADER;
		$this->import_agent_csv_header=self::IMPORT_AGENT_CSV_HEADER;
		$this->import_agent_contact_csv_header=self::IMPORT_AGENT_CSV_HEADER;
	}
    // cloned from Ole777
    const IMPORT_PLAYER_CSV_HEADER=[ 'RealName' // #1 For the fields, "playerdetails.firstName" and "playerdetails.lastName".
                                    , 'Birthday' // #2 For the field, "playerdetails.birthdate". ex: "1987-02-24"
                                    , 'GenderID' // #3 For the field, "playerdetails.gender". The values,'NULL' or empty() is means "Female", and "1" is means Male.
                                    , 'CountryID' // #4 Optional ,it is reference to COUNTRY_ISO2.
                                                    // For the field, "playerdetails.country"
                                                    // ex: "BR" is means as Brazil.
                                                    // About COUNTRY_ISO2, Please visit: https://onlinephp.io/c/50105
                                    , 'Mobile' // #5 For the field, "playerdetails.contactNumber"
                                    , 'CurrencyID' // #6 No-referenced.
                                    , 'Email' // #7 For the field, "player.email".
                                    , 'CreateDate' // #8 For the field, "player.createdOn"
                                    , 'UserCode' // #9 For the field, "player.username"
                                    , 'password' // #10 For the field, "player.password"
                                    , 'AffiliateCode' // #11 For the field, "player.affiliateId". 請參考 IMPORT_AFF_CSV_HEADER 的 AffiliateCode
                                    , 'AvailableBalance' // #12 The amount will call wallet_model::syncAllWallet().
                                                        // The related setting, importer_ole_balance_rate. Default as 1.
                                    , 'Status' // #13 player.blocked
                                    , 'LastLogOutTime' // #14 For the fields,"player_runtime.lastLogoutTime", "player_runtime.lastActivityTime" via player_model::updateLastActivity()
									, 'IMAccount' // #15 For the field, "playerdetails.imAccount"
                            ];
	const IMPORT_PLAYER_CONTACT_CSV_HEADER=['UserCode', 'ContactType', 'ContactAccount'];
	const IMPORT_PLAYER_BANK_CSV_HEADER=['UserCode', 'UserBankAccountName', 'BankAccountNo', 'BankName','BankID','BranchBankName'];
	const IMPORT_AFF_CSV_HEADER=[ 'AffiliateCode' // #1 for the field, "affiliates.username"
                                , 'password' // #2 for the field, "affiliates.password"
                                , 'Status' // #3 for the field, "affiliates.status"
                                , 'AffiliateID' // #4 Required, for the field, "affiliates.externalId".
                                , 'ParentAffiliateUsername' // #5 Required, for the field, "affiliates.parentId".
                                , 'CurrencyID' // #6 for the field, "affiliates.currency" @todo: aff.代理都使用 BRL?
                                , 'CountryID' // #7 Optional ,it is reference to COUNTRY_ISO2.
                                                // ex: "BR" is means as Brazil.
                                                // About COUNTRY_ISO2, Please visit: https://onlinephp.io/c/50105
                                , 'RealName' // #8 Optional, it will parse to "affiliates.firstName" and "affiliates.lastName"
                                , 'Gender' // #9 Male OR Female. for the field, "affiliates.gender".
                                , 'CreateDate' // #10 affiliates.createdOn. The string,"NULL" as null value in the field.
                                , 'Birthday' // #11 for the field, "affiliates.birthday".  The string,"NULL" as EMPTY STRING value in the field.
                                , 'Mobile' // #12 for the field, "affiliates.mobile".  The string,"NULL" as EMPTY STRING value in the field.
                                , 'Email' // #13 for the field, "affiliates.email". The string,"NULL" as EMPTY STRING value in the field.
                                , 'PromotionWebsite' // #14 for the field, "affiliates.website". The string,"NULL" as EMPTY STRING value in the field.
                                , 'AffTrackingCode' // #15 for the field,"affiliates.trackingCode".
                            ];
	const IMPORT_AFF_CONTACT_CSV_HEADER=['AffiliateCode','CurrencyID', 'ContactType', 'ContactAccount'];
	const IMPORT_AGENT_CSV_HEADER=['AgentID','AgentUsername','AgentLevel','ParentAgentUsername','password','AgentStatus','FirstName','Currency','PositionTaking'];

	private function checkWrongDateTimeAndFix($date_str){
		if (false === strtotime($date_str)) {
			return $this->utils->getNowForMysql();
		}
		$d = new DateTime($date_str);
		$years_check = ['1900','0000'];
		$year = $d->format('Y');
		if(in_array($year, $years_check)){
			return $this->utils->getNowForMysql();
		}
		return $date_str;
	}


    public function _extract_row($csv_row){
        if( ! empty($csv_row) ){
            $_csv_row = [];
            foreach($csv_row as $_index => $col){
                $col = trim($col);
                // https://regex101.com/r/9wNaka/1
                preg_match('/[\'\"]{1}(?P<val>.+)[\'\"]{1}/', $col, $matches);
                if( ! empty($matches['val'])){
                    $_col = $matches['val'];
                }else{
                    $_col = $col;
                }
                $_col = trim($_col);
                $_csv_row[$_index] = $_col;
            }
        }else{
            $_csv_row = $csv_row;
        }
        return $_csv_row;
    }

	private function processAff(&$rltAff, &$affMap){

		$this->CI->load->model(array('affiliatemodel','kash777_model'));
		$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltAff['filename'];
		$ignore_first_row = true;
		$country = unserialize(COUNTRY_ISO2);
		$country_map = [];
		foreach ($country as $key => $value) {
			$country_map[$value] = $key;
		}
		$controller = $this;
		$affiliatemodel = $this->CI->affiliatemodel;
		$kash777_model = $this->CI->kash777_model;
		$failCount = 0;
		$totalCount = 0;

        $import_mode = 'IMPORT_AFF_MODE'; // IMPORT_AFF_MODE or UPDATE_AFF_MODE
        $importer_kash_enabled = $this->utils->getConfig('importer_kash_enabled');
        if( count(self::IMPORT_AFF_CSV_HEADER) != count($importer_kash_enabled['IMPORT_AFF_CSV_HEADER']) ){
            $import_mode = 'UPDATE_AFF_MODE';
        }

		$this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
			use($controller, $affiliatemodel, $kash777_model, $country_map, &$totalCount, &$failCount, &$rltAff, $import_mode) {

				$controller->utils->debug_log("compare column headings" , self::IMPORT_AFF_CSV_HEADER, $csv_row);
				$row = array_combine(self::IMPORT_AFF_CSV_HEADER, $csv_row);
                $row = $controller->_extract_row($row);

                if( $row['AffiliateID'] == 'NULL'){
                    $_externalId = 'importer_kash_%s_%s';
                    $externalId = sprintf($_externalId, $cnt, rand(100,999) ); // TODO: confirm this, the data are all new player? For new one, please add the prefix.

                }else{
                    $externalId = $row['AffiliateID'];
                }

				if(empty($externalId)){
					$failCount++;
					$csv_row['reason'] = 'No AffiliateId Found on CSV';
					array_push($rltAff['failed_list'], $csv_row);
				}

				$username = $row['AffiliateCode'];
				$password = $row['password'];

				$createdOnStr= $row['CreateDate'] == 'NULL' ? '' : $row['CreateDate'];
				$createdOn = $controller->checkWrongDateTimeAndFix($createdOnStr);
				$createdOn = $controller->utils->formatDateTimeForMysql(new DateTime($createdOn));
				$affId = $affiliatemodel->getAffiliateIdByUsername($row['AffiliateCode']);

				$real_name_arr = explode(" ", $row['RealName']);
				$real_name_arr_count = count($real_name_arr);

				$firstName = '';
				$lastName = '';

				if($real_name_arr_count == 1){
					$firstName = $real_name_arr[0];
				}elseif($real_name_arr_count == 2){
					$firstName = $real_name_arr[0];
					$lastName = $real_name_arr[1];
				}elseif($real_name_arr_count == 3 ){
					$firstName = $real_name_arr[0].' '.$real_name_arr[1];
					$lastName = $real_name_arr[2];
				}elseif($real_name_arr_count == 4 ){
					$firstName = $real_name_arr[0].' '.$real_name_arr[1];
					$lastName = $real_name_arr[2];
					if(isset($real_name_arr[3])){
						$lastName .= ' '.$real_name_arr[3];
					}
				}

				$gender = $row['Gender'];
				//$trackingCode = $affiliatemodel->randomizer('trackingCode');
				$trackingCode = $row['AffTrackingCode'];
                /// affiliates.status => active:0, inactive:1, deleted:2
                if ( $row['Status'] == 'Active'
                    || $row['Status'] == '1'
                ){
                    $status = 0;
                }else{
                    $status = 1;
                }
				$birthday_str = $row['Birthday'] == 'NULL' ? '' : $row['Birthday'];
				$birthday_str = $controller->checkWrongDateTimeAndFix($birthday_str);
				$birthday_obj = new DateTime($birthday_str);

                $_notes = empty($row['Notes'])? '': $row['Notes'];
                $row['Notes'] = $_notes;

                $_domain = empty($row['Domain'])? null: $row['Domain'];
                $row['Domain'] = $_domain;

                if( $row['CountryID'] == "BRL"){
                    $row['CountryID']  = "BR";
                }
                $_countryID = isset($country_map[$row['CountryID']]) ? $country_map[$row['CountryID']] : '';

				$extra  = array(
					'notes' => $row['Notes'] == 'NULL' ? '' : $row['Notes'],
					'gender' => $gender,
					'email' => $row['Email'] == 'NULL' ? '' : $row['Email'],
					'affdomain' => $row['Domain'] == 'NULL' ? null : $row['Domain'],
					'birthday' => $controller->utils->formatDateForMysql($birthday_obj),
					'currency' => $row['CurrencyID'],
					'country' => $_countryID,
					'mobile' => $row['Mobile'] == 'NULL' ? '' : (string)$row['Mobile'],
					'affiliatePayoutId' => '0',
					'createdOn' => $createdOn,
					'website' => ($row['PromotionWebsite'] == 'NULL' || empty($row['PromotionWebsite'])) ? '' : $row['PromotionWebsite']
				);
                // $this->utils->debug_log("OGP-28121.215.row.ParentAffiliateUsername" , $row['ParentAffiliateUsername']);

                //process parent id
                $extra['parentId'] = 0;
                if( ! empty($row['ParentAffiliateUsername'])
                    && strtoupper($row['ParentAffiliateUsername']) != 'NULL'
                ){
                    $parentAffiliateUsername = $row['ParentAffiliateUsername'];
                    $parentAffId = $affiliatemodel->getAffiliateIdByUsername($parentAffiliateUsername);

                    // $this->utils->debug_log("OGP-28121.226.row.parentAffId" , $parentAffId);

                    if(!empty($parentAffId)){
                        $extra['parentId'] = $parentAffId;
                    }else{
                        //identifies that it has a parent set but cannot get the parent affiliate ID
                        // $extra['parentId'] = -1;
                        $this->utils->debug_log('ParentAffiliateUsername cannot get the parent affiliate ID:', $parentAffId, 'parentAffiliateUsername:', $parentAffiliateUsername);
                    }
                }

				$kash777_model->startTrans();
				$failMessage = '';
				$this->utils->debug_log('importAffiliate params',$externalId,$username, $password,
					$trackingCode,$createdOn, $firstName, $lastName,$status, $extra, $import_mode );

                switch($import_mode){ //  IMPORT_AFF_MODE or UPDATE_AFF_MODE
                    case 'IMPORT_AFF_MODE':
                        $affId = $kash777_model->importAffiliate( $externalId // #1
                                                        , $username // #2
                                                        , $password // #3
                                                        , $trackingCode // #4
                                                        , $createdOn // #5
                                                        , $firstName // #6
                                                        , $lastName // #7
                                                        , $status // #8
                                                        , $extra // #9
                                                    );
                        break;
                    case 'UPDATE_AFF_MODE':
                        $affId = $kash777_model->updateAffiliate( $externalId // #1
                                                        , $username // #2
                                                        , $password // #3
                                                        , $trackingCode // #4
                                                        , $createdOn // #5
                                                        , $firstName // #6
                                                        , $lastName // #7
                                                        , $status // #8
                                                        , $extra // #9
                                                    );
                        break;
                }

				if ($kash777_model->isErrorInTrans()) {
					$failCount++;
					$csv_row['reason'] = 'Trans_error';
					array_push($rltAff['failed_list'], $csv_row);
					$controller->utils->error_log("Import failed: [$failMessage]" , $csv_row);
				}
				$kash777_model->endTrans();
				$totalCount++;
		});//

		$rltAff['success_count'] = $totalCount - $failCount;
		$rltAff['row_count'] = $totalCount;
		$rltAff['failed_count'] = $failCount;
		$rltAff['column_count'] = count(self::IMPORT_AFF_CSV_HEADER);
		$rltAff['success']=true;
		$this->CI->utils->debug_log('process aff file:'.$rltAff['filename']);
	}


	private function DEL_processAffContact(&$rltAffContact, $affMap){

		$this->CI->load->model(array('affiliatemodel'));
		$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltAffContact['filename'];
		$ignore_first_row = true;

		$controller = $this;
		$affiliatemodel = $this->CI->affiliatemodel;
		$failCount = 0;
		$totalCount = 0;

		/*
            CN----------------------------------------
            $config['aff_custom_im_type_fields_map'] = [
                'qq'=>'imType1' ,
                'skype'=> 'imType2',
                '微信' => 'imType3',
                'line'=>'imType4',
                'whatsapp'=>'imType5',
                'ym'=>'imType6',

            ];
            $config['aff_custom_im_account_fields_map'] = [
                'qq'=>'im1' ,
                'skype'=> 'im2',
                '微信' => 'im3',
                'line'=>'im4',
                'whatsapp'=>'im5',
                'ym'=>'im6',
            ];
            THB----------------------------------------
            $config['aff_custom_im_type_fields_map'] = [
                            'line'=>'imType1' ,
                            'skype' => 'imType2',
                            'qq'=> 'imType3',
                            'facebook'=>'imType4'
            ];
            $config['aff_custom_im_account_fields_map'] = [
                            'line'=>'im1' ,
                            'skype'=> 'im2',
                            'qq' => 'im3',
                            'facebook'=>'im4',

            ];
            ID-------------------------------------------
            $config['aff_custom_im_type_fields_map'] = [
                            'whatsapp'=>'imType1' ,
                            'qq'=> 'imType2',
                            'skype' => 'imType3',
                            'facebook'=>'imType4',
                            'line'=>'imType5',
                            'bbm'=>'imType6',

            ];
            $config['aff_custom_im_account_fields_map'] = [
                            'whatsapp'=>'im1' ,
                            'qq'=> 'im2',
                            'skype' => 'im3',
                            'facebook'=>'im4',
                            'line'=>'im5',
                            'bbm'=>'im6',
            ];

        */
		$aff_custom_im_account_fields_map = $this->utils->getConfig('aff_custom_im_account_fields_map');
		$aff_custom_im_type_fields_map = $this->utils->getConfig('aff_custom_im_type_fields_map');

		$custom_fields_map_cnt =count($aff_custom_im_account_fields_map);
		$aff_site_assigned_im_accounts = [];// ['qq','wechat','weibo']

   		foreach ($aff_custom_im_account_fields_map as $key => $value) {
			array_push($aff_site_assigned_im_accounts, $key);
		}

		$this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
			use($controller, $affiliatemodel, $aff_custom_im_account_fields_map, $aff_custom_im_type_fields_map, $custom_fields_map_cnt,
				$aff_site_assigned_im_accounts, &$totalCount, &$failCount, &$rltAffContact) {

				$controller->utils->debug_log("compare column headings" , self::IMPORT_AFF_CONTACT_CSV_HEADER, $csv_row);
				$row = array_combine(self::IMPORT_AFF_CONTACT_CSV_HEADER, $csv_row);

				$affiliate_contacts = [];
				$contactType = (string)$row['ContactType'];
				$contactAccount = (string)$row['ContactAccount'];
			    $lang_json ='_json:{"1":"'.$contactType.'","2":"'.$contactType.'","3":"'.$contactType.'","4":"'.$contactType.'", "5":"'.$contactType.'","6":"'.$contactType.'"}';

				if(in_array(strtolower($row['ContactType']), $aff_site_assigned_im_accounts)){
			    	// like this $player_contact['imAccount'] = 'Wechat'
			    	$affiliate_contacts[$aff_custom_im_account_fields_map[strtolower($contactType)]] = $contactAccount;
					$affiliate_contacts[$aff_custom_im_type_fields_map[strtolower($contactType)]] = $lang_json ;
				}else{
			    	if($custom_fields_map_cnt == 4 ){
			    		$affiliate_contacts ['im5'] = $contactAccount;//th uses only 4 fields
			    		$affiliate_contacts ['imType5'] = $lang_json ;
			    	}
			    }

			    $affMap = $affiliatemodel->getUsernameMap();
			    $affId = !empty($affMap[$row['AffiliateCode']]) ? $affMap[$row['AffiliateCode']] : null;

			    if(empty($affId)){
					$failCount++;
			    	$csv_row['reason'] = 'No AffiliateID Found';
					$csv_row['assigned_ims'] = implode(",", $site_assigned_im_accounts);
					array_push($rltAffContact['failed_list'], $csv_row);
				}


				//print_r($affiliate_contacts);
				if(!empty($affId)){
					$affiliatemodel->startTrans();
					$failMessage = '';
					$controller->utils->debug_log('username', @$row['AffiliateCode'],'affiliate_contacts',$affiliate_contacts,'import contactType',@$row['ContactType'],'assigned_ims',$aff_site_assigned_im_accounts, 'affiliateId',$affId);
					if(!empty($affiliate_contacts)){// fix error in update
						$affiliatemodel->editAffiliate($affiliate_contacts, $affId);
					}else{
						$failCount++;
						$csv_row['reason'] = 'Empty Affiliate Contacts';
						$csv_row['assigned_ims'] = implode(",", $aff_site_assigned_im_accounts);
						array_push($rltAffContact['failed_list'], $csv_row);
					}

					if ($affiliatemodel->isErrorInTrans()){
						$failCount++;
						$csv_row['reason'] = 'Trans_error';
						array_push($rltAffContact['failed_list'], $csv_row);
						$controller->utils->error_log("Import failed: [$failMessage]" , $csv_row);
					}
					$affiliatemodel->endTrans();
					$totalCount++;
				}
			});

		$rltAffContact['success_count'] = $totalCount - $failCount;
		$rltAffContact['row_count'] = $totalCount;
		$rltAffContact['failed_count'] = $failCount;
		$rltAffContact['success']=true;
		$rltAffContact['column_count'] = count(self::IMPORT_AFF_CONTACT_CSV_HEADER);
		$this->CI->utils->debug_log('process aff contact:'.$rltAffContact['filename']);
	}

	public function addUpdateAgencyAgentGamePlatformsAndTypes($kash777_model,$game_platforms_and_types,$agent_id,$rev_share){

		foreach ($game_platforms_and_types as $game_platform_id => $platform_row_details) {
			//add agency_agent_game_platforms table [id,agent_id,game_platform_id]
			$agency_agent_game_platforms_row = array('agent_id' => $agent_id, 'game_platform_id' => $game_platform_id);
			$this->utils->debug_log('agency_agent_game_platforms_row',$agency_agent_game_platforms_row);
			$kash777_model->addUpdateAgencyAgentGamePlatforms($agency_agent_game_platforms_row,$game_platform_id,$agent_id);
			//['id','agent_id','game_platform_id','game_type_id','rolling_comm_basis','rev_share','rolling_comm','rolling_comm_out','pattern_id','bet_threshold','platform_fee','min_rolling_comm']
			$game_types = $platform_row_details['game_types'];

			foreach ($game_types as $game_type) {
				$this->utils->info_log('game_type',$game_type);
				//$rev_share = (double)$row['PositionTaking'];
				$game_type_id = $game_type['id'];
				$agency_agent_game_types_row = array(
					'agent_id' => $agent_id ,
					'game_platform_id' => $game_platform_id,
					'game_type_id' => $game_type_id,
					'rolling_comm_basis' => 'total_bets_except_tie_bets',
					'rev_share' => $rev_share,
					'rolling_comm'=> 0,
					'rolling_comm_out'=>0,
					'pattern_id'=>0,
					'bet_threshold'=>0,
					'platform_fee'=>0,
					'min_rolling_comm'=>0
				);
				$this->utils->debug_log('agency_agent_game_types_row',$agency_agent_game_types_row);
				$kash777_model->addUpdateAgencyAgentGameType($agency_agent_game_types_row,$game_platform_id,$game_type_id,$agent_id);

			}
		}
	}

	private function DEL_processAgent(&$rltAgent, &$agentMap){

		$this->CI->load->model(array('kash777_model','agency_model'));
		$this->CI->load->library(array('salt'));
		$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltAgent['filename'];
		$ignore_first_row = true;
		$controller = $this;
		$agency_model = $this->CI->agency_model;
		$kash777_model = $this->CI->kash777_model;
		$failCount = 0;
		$totalCount = 0;

		$game_platforms_and_types = $agency_model->get_game_platforms_and_types();
		$root_agent_name = 'ole777cn';
		$password = '123ole777cn';
		$tracking_code = 'ole777cn';
		$createdOn = $controller->utils->getNowForMysql();
		$agent_level = 0;
		$agent_level_name = 0;
		$rev_share = 100;

		$root_agent_data  = array(
			//'agent_id' => $agent_id,
			'parent_id' => 0,
			'agent_name' => $root_agent_name,
			'tracking_code' => 0,
			'agent_level' => $agent_level,
			'agent_level_name' =>  $agent_level_name,
			'firstname' => $root_agent_name,
			'lastname' => $root_agent_name,
			'password' =>  $this->CI->salt->encrypt($password, $this->CI->config->item('DESKEY_OG')),
			'can_have_sub_agent' => 1,
			'can_have_players' => 1,
			'can_do_settlement' => 1,
			'can_view_agents_list_and_players_list' => 1,
			'show_bet_limit_template' => 1,
			'show_rolling_commission' => 1,
			'currency' => 'CNY',
			'credit_limit' => 0,
			'available_credit'=>0,
			'status' => Agency_model::AGENT_STATUS_ACTIVE,
			'active'  => '1',
			'vip_level'=> 1,
			'settlement_period' => 'Weekly',
			'settlement_start_day'  => 'Monday',
			'created_on' => $createdOn,
			'updated_on' => $createdOn,
			'admin_fee' => 0,
			'transaction_fee' => 0,
			'bonus_fee' => 0,
			'cashback_fee' => 0,
			'min_rolling_comm' => 0,
		);

		$id_parent_agent_list= [];

		$this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
			use($controller,  $kash777_model,$agency_model,$game_platforms_and_types,&$agentMap,&$id_parent_agent_list,&$totalCount, &$failCount, &$rltAgent) {

				$row = array_combine(self::IMPORT_AGENT_CSV_HEADER, $csv_row);
				$externalId = $row['AgentID'];
				if(empty($externalId)){
					$failCount++;
					$csv_row['reason'] = 'No agent_user_id Found';
					array_push($rltAgent['failed_list'], $csv_row);
				}
				$agent_name= (string)$row['AgentUsername'];
				$parent_name= (string)$row['ParentAgentUsername'];
				$password = isset($row['password']) ?  $row['password'] : '' ;
				$agent_id = $agency_model->getAgentIdByUsername($agent_name);
				$parent_id = $agency_model->getAgentIdByUsername($parent_name);
				$createdOn = $controller->utils->getNowForMysql();
				$firstName = isset($row['FirstName']) ?  $row['FirstName'] : '' ;
				$lastName = isset($row['LastName']) ?  $row['LastName'] : '' ;
				$lastName ='';
				$tracking_code = $externalId;

				$agent_status = (isset($row['AgentStatus']) && $row['AgentStatus'] == 'Active' ) ?  Agency_model::AGENT_STATUS_ACTIVE : Agency_model::AGENT_STATUS_FROZEN ;
				$is_agent_active = ($agent_status == Agency_model::AGENT_STATUS_ACTIVE) ? 1 : 0;
				$rev_share = ((double)$row['PositionTaking'])*100;

				$extra  = array(
					//'agent_name'=> $agent_name,
					//'tracking_code' => $tracking_code,
					'currency' => $row['Currency'],
					'credit_limit' => 0,
					'available_credit'=>0,
					'status' => $agent_status,
					'active'  => $is_agent_active,
					'agent_level' => $row['AgentLevel'],
					'agent_level_name' => $row['AgentLevel'],
					// 'can_have_sub_agent' => $parent_agent_details['can_have_sub_agent'],
				 //    'can_have_players' => $parent_agent_details['can_have_players'] ,
				 //    'can_do_settlement' => $parent_agent_details['can_do_settlement'],
				 //    'can_view_agents_list_and_players_list' =>$parent_agent_details['can_view_agents_list_and_players_list'] ,
					// 'show_bet_limit_template' =>$parent_agent_details['show_bet_limit_template'] ,
			  //   	'show_rolling_commission' => $parent_agent_details['show_rolling_commission'] ,
					'vip_level'=> 1,  //verify
			     	'settlement_period' => 'Weekly',// verify
                    'settlement_start_day'  => 'Monday', // verify
                    'created_on' => $createdOn,
                    'updated_on' => $createdOn,
                    'parent_id' => !empty($parent_id) ? $parent_id : '0',
                    'admin_fee' => 0,
                    'transaction_fee' => 0,
                    'bonus_fee' => 0,
                    'cashback_fee' => 0,
                    'min_rolling_comm' => 0,
                );
                 $agentMap[$agent_name] = $externalId;
                //if(empty($parent_id)){
                	 array_push($id_parent_agent_list, array('agent_id'=> $externalId, 'parent_username' => $parent_name));
                //}

				$kash777_model->startTrans();
				$failMessage = '';
				$this->utils->debug_log('importAgent params',$externalId,$agent_name, $password, $tracking_code, $createdOn, $firstName, $lastName,$extra );

				$kash777_model->importAgent($externalId,$agent_name, $password, $tracking_code, $createdOn, $firstName, $lastName,$extra);
				$controller->addUpdateAgencyAgentGamePlatformsAndTypes($kash777_model,$game_platforms_and_types,$externalId,$rev_share);

				if ($kash777_model->isErrorInTrans()){
					$failCount++;
					$csv_row['reason'] = 'Trans_error';
					array_push($rltAgent['failed_list'], $csv_row);
					$controller->utils->error_log("Import failed: [$failMessage]" , $csv_row);
				}
				$kash777_model->endTrans();
				$totalCount++;
		});//

		if(!empty($id_parent_agent_list)){

			foreach ($id_parent_agent_list as  $agent_row) {
				$kash777_model->startTrans();

				if($agent_row['parent_username'] != "\N"){
					$id_parent_agent_row = array('parent_id' => $agentMap[$agent_row['parent_username']]);
					$kash777_model->updateAgentInfoByAgentId($id_parent_agent_row,$agent_row['agent_id']);
				}

				if ($kash777_model->isErrorInTrans()){
					$failCount++;
					$agent_row['reason'] = 'Trans_error ';
					array_push($rltAgent['failed_list'], $agent_row);
				}

				$kash777_model->endTrans();

			}
		}

        //root agent
		$root_agent_id = $kash777_model->addUpdateAgentInfoByAgentName($root_agent_data,$root_agent_name);
		$controller->addUpdateAgencyAgentGamePlatformsAndTypes($kash777_model,$game_platforms_and_types,$root_agent_id,$rev_share);
		if(!empty($root_agent_id)){
			$this->CI->utils->debug_log('root_agent_id :',$root_agent_id);
			$kash777_model->updateAllLevelOneParentId($root_agent_id);
		}else{
			$this->CI->utils->debug_log('NO root_agent_id',empty($root_agent_id));
		}
		$rltAgent['success_count'] = $totalCount - $failCount;
		$rltAgent['row_count'] = $totalCount;
		$rltAgent['column_count'] = count(self::IMPORT_AGENT_CSV_HEADER);
		$rltAgent['success']=true;
		$this->CI->utils->debug_log('process aff file:'.$rltAgent['filename']);
	}



	private function processPlayer(&$rltPlayer, $affMap, &$playerMap){

		$this->CI->load->model(array('affiliatemodel', 'kash777_model','player_model','vipsetting','game_provider_auth','group_level','wallet_model','http_request'));
		$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltPlayer['filename'];
		$ignore_first_row = true;

		$country = unserialize(COUNTRY_ISO2);
		$country_map = [];

		foreach ($country as $key => $value) {
			$country_map[$value] = $key;
		}
		// $config['importer_ole_vip_settings']=[
		// 	'new member'=> '1',
		// 	'normal member'=>'2'
		// ];

		$import_vip_settings = $this->utils->getConfig('importer_ole_vip_settings');
	    $controller = $this;
		$affiliatemodel = $this->CI->affiliatemodel;
		$kash777_model =  $this->CI->kash777_model;
		$player_model =  $this->CI->player_model;
		$group_level = $this->CI->group_level;
		$wallet_model = $this->CI->wallet_model;
		$http_request= $this->CI->http_request;

		$importer_ole_balance_rate=$this->utils->getConfig('importer_ole_balance_rate');
		if(empty($importer_ole_balance_rate)){
			$importer_ole_balance_rate=1;
		}

		$totalCount = 0;
		$failCount  = 0;
		/*
		$config['assigned_game_apis_map'] =  [
			ONEWORKS_API => ['prefix' => null],
			BBIN_API => ['prefix' => null],
			BETSOFT_API => ['prefix' => null],
			QT_API => ['prefix' => null],
			OG_API => ['prefix' => "ah_"],
		];
		*/
        $assigned_game_apis_map = $this->utils->getConfig('assigned_game_apis_map');
        $controller->utils->debug_log("assigned_game_apis_map" , $assigned_game_apis_map);

        $import_mode = 'IMPORT_PLAYER_MODE'; // IMPORT_PLAYER_MODE or UPDATE_PLAYER_MODE
        $importer_kash_enabled = $this->utils->getConfig('importer_kash_enabled');
        if( count(self::IMPORT_PLAYER_CSV_HEADER) != count($importer_kash_enabled['IMPORT_PLAYER_CSV_HEADER']) ){
            $import_mode = 'UPDATE_PLAYER_MODE';
        }
        $controller->utils->debug_log('OGP-28121.685.import_mode:', $import_mode, count(self::IMPORT_PLAYER_CSV_HEADER), count($importer_kash_enabled['IMPORT_PLAYER_CSV_HEADER']) );
		$this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
			use($controller, $affiliatemodel, $kash777_model, $country_map, $import_vip_settings,
			    $assigned_game_apis_map,$group_level,$wallet_model, $player_model, $importer_ole_balance_rate,
			     $http_request,&$totalCount, &$failCount, &$rltPlayer, $import_mode) {

				//compare log consistency of columns
				$controller->utils->info_log("compare column headings" , self::IMPORT_PLAYER_CSV_HEADER, $csv_row);
				$row = array_combine(self::IMPORT_PLAYER_CSV_HEADER, $csv_row);
                $row = $controller->_extract_row($row);
                // print_r($vip_settings_map);

                // $externalId = $row['PlayerID'];
                if(empty($row['PlayerID']) ){
                    $_externalId = 'importer_kash_%s_%s';
                    $externalId = sprintf($_externalId, $cnt, rand(100,999) ); // TODO: confirm this, the data are all new player? For new one, please add the prefix.
                }else{
                    $externalId = $row['PlayerID'];
                }

				if(empty($externalId)){
					$failCount++;
					$csv_row['reason'] = 'No playerId Found on CSV';
					array_push($rltPlayer['failed_list'], $csv_row);
				}

                $levelId='1';
			    // if(isset($row['MemberCategory']) && $row['MemberCategory'] != '' ){
			    // 	$levelId = $import_vip_settings[$row['MemberCategory']];
			    // }
			  	$username = $row['UserCode'];
				//$password = empty($row['password']) ? '' : $row['password'] ;
                if( strtoupper($row['AvailableBalance']) == 'NULL'){
                    $row['AvailableBalance'] = 0;
                }
				$balance =  empty($row['AvailableBalance']) ? 0: $row['AvailableBalance'];
				//convert balance
				$balance = round($balance * $importer_ole_balance_rate, 4);

				$createdOnStr= $row['CreateDate'] == 'NULL' ? '' : $row['CreateDate'];
				$createdOnStr = $controller->checkWrongDateTimeAndFix($createdOnStr);
				$createdOn = $controller->utils->formatDateTimeForMysql(new DateTime($createdOnStr));

				$affMap = $affiliatemodel->getUsernameMap();

				$real_name_arr = explode(" ", $row['RealName']);
				$real_name_arr_count = count($real_name_arr);

				$firstName = null;
				$lastName = null;

				if($real_name_arr_count == 1){
					$firstName = $real_name_arr[0];
				}elseif($real_name_arr_count == 2){
					$firstName = $real_name_arr[0];
					$lastName = $real_name_arr[1];
				}elseif($real_name_arr_count == 3 ){
					$firstName = $real_name_arr[0].' '.$real_name_arr[1];
					$lastName = $real_name_arr[2];
				}elseif($real_name_arr_count == 4 ){
					$firstName = $real_name_arr[0].' '.$real_name_arr[1];
					$lastName = $real_name_arr[2];
					if(isset($real_name_arr[3])){
						$lastName .= ' '.$real_name_arr[3];
					}
				}

				$gender = $row['GenderID'];

				if($gender == 'NULL' || empty($gender)){
					$gender = 'Female';
				}elseif($gender == '1'){
					$gender = 'Male';
				}else{
					$gender = 'Female';
				}
				$affId = !empty($affMap[$row['AffiliateCode']]) ? $affMap[$row['AffiliateCode']] : '';

				$hours_adjust = "+12";

				// $csv_login_time = new DateTime($row['LastLoginTime']);
				// $login_time_arr = (array)$csv_login_time;
				// $LastLoginTime = (new DateTime($login_time_arr['date']))->modify("".$hours_adjust." hours");

				// $csv_logout_time = new DateTime($row['LastLogOutTime']);
				// $logout_time_arr = (array)$csv_logout_time;
				// $LastLogOutTime = (new DateTime($logout_time_arr['date']))->modify("".$hours_adjust." hours");



                if($row['LastLogOutTime'] == 'NULL'){
                    $row['LastLogOutTime'] = $controller->utils->formatDateTimeForMysql(new DateTime());
                }

                // $login_time_arr = explode(" ", $row['LastLoginTime']);
                // $login_time_str = $login_time_arr[0]." ".$login_time_arr[1];
                // $login_time = $controller->checkWrongDateTimeAndFix($login_time_str);
                // $LastLoginTime = (new DateTime($login_time))->modify("".$hours_adjust." hours");
                //
                $logout_time_arr = explode(" ", $row['LastLogOutTime']);
				$logout_time_str = $logout_time_arr[0]." ".$logout_time_arr[1];
				$logout_time = $controller->checkWrongDateTimeAndFix($logout_time_str);
				$LastLogOutTime = (new DateTime($logout_time))->modify("".$hours_adjust." hours");
                //
                $LastLoginTime = $LastLogOutTime;



				$extra = array(
					'email' =>  $row['Email'] == 'NULL' ? '' : $row['Email'],
					'createdOn' => $createdOn,
					'updatedOn' => $controller->utils->getNowForMysql(),
                    // for player.status => active:0, inactive:1
                    // But the field usually for display.
					'status' => Player_model::OLD_STATUS_ACTIVE,
                    /// for player.status =>
                    // block = 1;
                    // suspended = 5;
                    // selfexclusion = 7;
                    // blocked_failed_login_attempt
                    'blocked' => $row['Status'] == '1' ? Player_model::OLD_STATUS_ACTIVE  : Player_model::BLOCK_STATUS,
					'affiliateId' => $affId,
					'lastLoginTime' => $controller->utils->formatDateTimeForMysql($LastLoginTime),
					'lastLogoutTime' => $controller->utils->formatDateTimeForMysql($LastLogOutTime),
					//	'frozen' => $frozen,
				);

				$birthday_str = $row['Birthday'] == 'NULL' ? '' : $row['Birthday'];
				$birthday_str = $controller->checkWrongDateTimeAndFix($birthday_str);
				$birthday_obj = new DateTime($birthday_str);

                if( $row['CountryID'] == "BRL"){
                    $row['CountryID']  = "BR";
                }
                $_countryID = isset($country_map[$row['CountryID']]) ? $country_map[$row['CountryID']] : '';

				$details = array(
					'firstName' =>  $firstName,
					'lastName' => $lastName,
					'gender' => $gender,
					'country' => $_countryID,
					'birthdate' => $controller->utils->formatDateForMysql($birthday_obj),
					'contactNumber' => $row['Mobile'],
					'imAccount' => $row['IMAccount'],
				);

				$playerTagName = empty($row['UserCategoryName'])? null: $row['UserCategoryName'];
				$password = $row['password'];
				$kash777_model->startTrans();
                $controller->utils->debug_log('importPlayer params', $externalId
                    , $levelId
                    , $username
                    , $password
                    , $balance
                    , $extra
                    , $details
                    , $import_mode
                );

				$failMessage = '';
                switch($import_mode){
                    case 'IMPORT_PLAYER_MODE':
                        $importPlayerId = $kash777_model->importPlayer( $externalId // #1
                                                                , $levelId // #2
                                                                , $username // #3
                                                                , $password // #4
                                                                , $balance // #5
                                                                , $extra // #6
                                                                , $details // #7
                                                                , $failMessage // #8
                                                                , $playerTagName // #9
                                                                , $assigned_game_apis_map // #10
                                                                , $group_level // #11
                                                                , $wallet_model // #12
                                                                , $player_model // #13
                                                                , $http_request // #14
                                                            );
                        break;
                    case 'UPDATE_PLAYER_MODE':
                        $importPlayerId = $kash777_model->updatePlayer( $externalId // #1
                                                                , $levelId // #2
                                                                , $username // #3
                                                                , $password // #4
                                                                , $balance // #5
                                                                , $extra // #6
                                                                , $details // #7
                                                                , $failMessage // #8
                                                                , $playerTagName // #9
                                                                , $assigned_game_apis_map // #10
                                                                , $group_level // #11
                                                                , $wallet_model // #12
                                                                , $player_model // #13
                                                                , $http_request // #14
                                                            );
                        break;
                }

				if ($kash777_model->isErrorInTrans()) {
					$failCount++;
					$csv_row['reason'] = 'Trans_error';
					array_push($rltPlayer['failed_list'], $csv_row);
			    	$controller->utils->error_log("Import failed: [$failMessage]" , $csv_row);
				}
				$totalCount++;
				$kash777_model->endTrans();

			});
		$rltPlayer['success_count'] = $totalCount - $failCount;
		$rltPlayer['row_count'] = $totalCount;
		$rltPlayer['failed_count'] = $failCount;
		$rltPlayer['column_count'] = count(self::IMPORT_PLAYER_CSV_HEADER);
		$rltPlayer['success']=true;
		$this->CI->utils->debug_log('process player:'.$rltPlayer['filename']);
	} // EOF processPlayer()

	private function DEL_processPlayerContact(&$rltPlayerContact, $playerMap){

		$this->CI->load->model(array('player_model'));
		$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltPlayerContact['filename'];
		$ignore_first_row = true;

		$controller = $this;
		$player_model = $this->CI->player_model;
		$failCount = 0;
		$totalCount = 0;
		$playerMap = $player_model->getPlayerUsernameIdMap();
		/*
		CN----------------------------------------
		$config['custom_im_account_fields_map'] = [
			'qq'=>'imAccount' ,
			'wechat'=> 'imAccount2',
			'skype' => 'imAccount3',
			'weibo'=>'imAccount4',
		];
		 THB----------------------------------------
		 $config['custom_im_account_fields_map'] = [
		 	'微信'=>'imAccount' ,
		 	'line'=> 'imAccount2',
		 	'whatsapp' => 'imAccount3',
		 	'facebook'=>'imAccount4',
		 	'kakaotalk'=>'imAccount5',
		 ];
		ID-------------------------------------------
		$config['custom_im_account_fields_map'] = [
			'微信'=>'imAccount' ,
			'line'=> 'imAccount2',
			'bbm' => 'imAccount3',
			'whatsapp'=>'imAccount4',
			'facebook'=>'imAccount5',
		];
		*/

		$custom_im_account_fields_map = $this->utils->getConfig('custom_im_account_fields_map');

		//print_r($custom_im_account_fields_map); exit;
		$custom_fields_map_cnt =count($custom_im_account_fields_map);
		$site_assigned_im_accounts = [];// ['qq','wechat','weibo']

		foreach ($custom_im_account_fields_map as $key => $value) {
			array_push($site_assigned_im_accounts, $key);
		}

		$this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
			use($controller, $player_model, $playerMap,$custom_im_account_fields_map,$site_assigned_im_accounts,$custom_fields_map_cnt,
				&$totalCount, &$failCount, &$rltPlayerContact) {

				//compare log consistency of columns
				$controller->utils->debug_log("compare column headings" , self::IMPORT_PLAYER_CONTACT_CSV_HEADER, $csv_row);

				$row = array_combine(self::IMPORT_PLAYER_CONTACT_CSV_HEADER, $csv_row);


				$player_id = isset($playerMap[$row['UserCode']]) ? $playerMap[$row['UserCode']] : null;

				if(empty($player_id)){
					$failCount++;
					$csv_row['reason'] = 'No playerId Found';
					$csv_row['assigned_ims'] = implode(",", $site_assigned_im_accounts);
					array_push($rltPlayerContact['failed_list'], $csv_row);
					return;
				}

				$player_contacts = [];

				if(in_array(strtolower($row['ContactType']), $site_assigned_im_accounts)){
			    	// like this $player_contact['imAccount'] = 'Wechat'
					$player_contacts[$custom_im_account_fields_map[strtolower($row['ContactType'])]] = (string)$row['ContactAccount'];
				}else{
			    	if($custom_fields_map_cnt == 4 ){//cn uses only 4 im accounts
			    		$player_contacts['imAccount5'] = $row['ContactType'].'-'.(string)$row['ContactAccount'];
			    	}
			    }
			    $player_model->startTrans();
			    $failMessage = '';
			    $controller->utils->debug_log('player_contacts',$player_contacts,'import contactType',@$row['ContactType'],'assigned_ims',$site_assigned_im_accounts, 'player_id',$player_id );

			    if(!empty($player_contacts)){// fix error in update
			    	 $player_model->editPlayerDetails($player_contacts, $player_id);
			    }else{
			    	$failCount++;
			    	$csv_row['reason'] = 'Empty Player Contacts';
			    	$csv_row['assigned_ims'] = implode(",", $site_assigned_im_accounts);
					array_push($rltPlayerContact['failed_list'], $csv_row);
			    }
			    if ($player_model->isErrorInTrans()){
			    	$failCount++;
			    	$csv_row['reason'] = 'Trans_error';
					array_push($rltPlayerContact['failed_list'], $csv_row);
			    	$controller->utils->error_log("Import failed: [$failMessage]" , $csv_row);
			    }
			    $totalCount++;
			    $player_model->endTrans();
			});

		$rltPlayerContact['success_count'] = $totalCount - $failCount;
		$rltPlayerContact['row_count'] = $totalCount;
		$rltPlayerContact['failed_count'] = $failCount;
		$rltPlayerContact['success']=true;
		$rltPlayerContact['column_count'] = count(self::IMPORT_PLAYER_CONTACT_CSV_HEADER);
		$this->CI->utils->debug_log('process player contact:'.$rltPlayerContact['filename']);

	} // EOF processPlayerContact()

    /*
    - CN
    $config['ole777_sbe_banktype_id_map']  = [
    '1' => 6,'2' => 1,'3' => 3,'4' => 4,'5' => 5,'6' => 2,'7' => 11,'8' => 20,'9' => 13,'10' => 12,'11' => 15,'12' => 10,'13' => 26,'14' => 14,'15' => 27,'16' => 28,'17' => 29,'18' => 30,'19' => 31,'20' => 32,'21' => 33,'49' => 34,'50' => 35,'51' => 36,'52' => 37,'53' => 38,'54' => 39,'55' => 40,'56' => 41,'57' => 42,'58' => 43,'59' => 44,'60' => 45,'61' => 17,'62' => 46,'63' => 47,'64' => 48,'65' => 49,'66' => 50,'67' => 51,'68' => 52,'69' => 53,'70' => 54,'71' => 55,'72' => 56,'73' => 57,'74' => 58,'75' => 59,'76' => 60,'77' => 61,'78' => 62,'79' => 63,'80' => 64,'81' => 65,'82' => 66,'83' => 18,'84' => 67,'85' => 68,'86' => 69,'87' => 70,'88' => 71,'89' => 71,'90' => 72,'91' => 73,'92' => 74,'93' => 75,'94' => 76,'95' => 77,'96' => 78,'97' => 79,'98' => 80,'99' => 81,'100' => 82,'101' => 83,'102' => 84,'103' => 85,'104' => 86,'105' => 87,'106' => 88,'107' => 89,'108' => 90,'109' => 91,'110' => 92,'111' => 93,'112' => 94,'113' => 95,'114' => 96,'115' => 97,'116' => 98,'117' => 99,'118' => 100,'119' => 101,'120' => 102,'121' => 103,'122' => 104,'123' => 105,'124' => 106,'125' => 107,'126' => 108,'127' => 109,'128' => 110,'129' => 111,'130' => 112,'131' => 113,'132' => 114,'133' => 115,'134' => 116,'135' => 117,'136' => 118,'137' => 119,'138' => 120,'139' => 121,'140' => 122,'141' => 123,'142' => 124,'143' => 125,'144' => 126,'145' => 127,'146' => 128,'147' => 129,'148' => 130,'149' => 131,'150' => 132,'151' => 133,'152' => 134,'153' => 135,'154' => 136,'155' => 137,'156' => 138,'157' => 139,'158' => 140,'159' => 141,'160' => 142,'161' => 143,'162' => 144,'163' => 145,'164' => 146,'165' => 147,'166' => 148,'167' => 149,'168' => 150,'169' => 151,'170' => 152,'171' => 153,'172' => 154,'173' => 155,'174' => 156,'175' => 157,'176' => 158,'177' => 159,'178' => 160,'179' => 161,'180' => 162,'181' => 9,'182' => 163,'183' => 19,'184' => 164,'185' => 165,'186' => 166,'187' => 167,'188' => 168,'189' => 169,'190' => 170,'191' => 171,'192' => 172,'193' => 173,'194' => 174,'195' => 175,'196' => 176,'197' => 177,'198' => 178,'199' => 179,'200' => 180,'201' => 181,'202' => 182,'203' => 183,'204' => 184,'205' => 185,'206' => 186,'207' => 187,'208' => 188,'209' => 189,'210' => 190,'211' => 191,'212' => 192,'213' => 193,'214' => 194,'215' => 195,'216' => 196,'217' => 197,'218' => 198,'219' => 199,'220' => 200,'221' => 201,'222' => 202,'223' => 203,'224' => 204,'225' => 205,'226' => 206,'227' => 207,'228' => 208,'229' => 209,'230' => 210,'231' => 211,'232' => 212,'233' => 213,'234' => 214,'235' => 215,'236' => 216,'237' => 217,'238' => 218,'239' => 219,'240' => 220,'241' => 221,'242' => 222,'243' => 223,'244' => 224,'245' => 225,'246' => 226,'247' => 227,'248' => 228,'249' => 229,'250' => 230,'251' => 231,'252' => 232,'253' => 233,'350' => 282,'351' => 282,'352' => 282,'357' => 234,'358' => 235,
    ];
    - IDN
    $config['ole777_sbe_banktype_id_map']  = [
    '40' => 27,'41' => 28,'260' => 29,'261' => 30,'262' => 31,'263' => 32,'264' => 33,'265' => 34,'266' => 35,'267' => 36,'268' => 37,'269' => 38,'270' => 39,'271' => 5,'272' => 40,'273' => 41,'274' => 42,'275' => 43,'276' => 44,'277' => 45,'278' => 46,'279' => 47,'280' => 48,'281' => 49,'282' => 50,'283' => 51,'284' => 52,'285' => 53,'286' => 54,'287' => 55,'288' => 56,'289' => 57,'290' => 58,'291' => 59,'292' => 60,'293' => 61,'294' => 62,'295' => 63,'296' => 64,'297' => 65,'298' => 66,'299' => 67,'300' => 68,'301' => 69,'302' => 70,'303' => 71,'304' => 72,'305' => 73,'306' => 74,'307' => 75,'308' => 76,'309' => 77,'310' => 78,'311' => 79,'312' => 80,'313' => 81,'314' => 82,'315' => 83,'316' => 84,'317' => 85,'318' => 86,'319' => 87,'320' => 88,'321' => 89,'322' => 90,'323' => 91,'324' => 92,'325' => 93,'326' => 94,'327' => 95,'351' => 99,'355' => 96,
    ];

    -  THB
    $config['ole777_sbe_banktype_id_map'] = [
    '42' => 28,'43' => 29,'44' => 30,'45' => 31,'46' => 32,'47' => 33,'332' => 34,'333' => 35,'336' => 36,'340' => 37,'346' => 38,'347' => 39,'348' => 40,'356' => 41,
    ];

    */
    private function DEL_processPlayerBank(&$rltPlayerBank, $playerMap){

        $this->CI->load->model(array('player_model','kash777_model','playerbankdetails', 'banktype'));
        $uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
        $csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltPlayerBank['filename'];
        $ignore_first_row = true;

        $controller = $this;
        $player_model = $this->CI->player_model;
        $kash777_model = $this->CI->kash777_model;
        $banktype = $this->CI->banktype;
        $playerbankdetails = $this->CI->playerbankdetails;
        $ole777_sbe_banktype_id_map  = $this->utils->getConfig('ole777_sbe_banktype_id_map');
        //$config['ole777_player_import_dwbank'] = ['0','1'];
        $ole777_player_import_dwbank = $this->utils->getConfig('ole777_player_import_dwbank');
        $failCount = 0;
        $totalCount = 0;
        $playerMapDw = [];

        $this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
            use($controller, $player_model, $kash777_model, $banktype, $playerbankdetails,$ole777_sbe_banktype_id_map,
                $ole777_player_import_dwbank, &$totalCount, &$failCount, &$rltPlayerBank, &$playerMapDw ) {

            //compare log consistency of columnss
            $controller->utils->debug_log("compare column headings" , self::IMPORT_PLAYER_BANK_CSV_HEADER, $csv_row);

            $row = array_combine(self::IMPORT_PLAYER_BANK_CSV_HEADER, $csv_row);

            $playerId =  $player_model->getPlayerIdByUsername($row['UserCode']);

            if(empty($playerId)){
                $failCount++;
                $csv_row['reason'] = 'No playerId Found';
                array_push($rltPlayerBank['failed_list'], $csv_row);
                return;
            }

            $bankTypeId = isset($row['BankID']) && isset($ole777_sbe_banktype_id_map[$row['BankID']]) ? $ole777_sbe_banktype_id_map[$row['BankID']] : null;
            //$controller->utils->debug_log("bankTypeId" , $bankTypeId, $csv_row);
            if(empty($bankTypeId)){
                $failCount++;
                $csv_row['reason'] = 'No bankTypeId Found';
                array_push($rltPlayerBank['failed_list'], $csv_row);
                return;
            }


            $bankAccountNumber = (string)$row['BankAccountNo'];
            $bankAccountFullName = $row['UserBankAccountName'];
            $province = '';
            $city =  '';
            $branch = $row['BranchBankName'];
            $bankAddress = null; //included in branch we cant separate due to language;
            $createdOn = $controller->utils->getNowForMysql();
            $status = '0';
            $message = null;

            $external_id = $row['BankAccountNo'].'-'.$row['BankID'].'-'.$row['UserCode'];

            if(!empty($playerId ) && !empty($bankTypeId)){
                $kash777_model->startTrans();
                $failMessage = '';
                // const DEPOSIT_BANK = '0';
                // const WITHDRAWAL_BANK = '1';
                $default = ['0','1'];
                $dwBank = empty($ole777_player_import_dwbank) ? $default : $ole777_player_import_dwbank ;
                foreach ($dwBank as $v) {
                    $kash777_model->importPlayerBank($external_id, $playerId, $bankTypeId, $v,
                        $bankAccountFullName, $bankAccountNumber, $province, $city, $branch, $bankAddress, $createdOn, $status, $message);
                }
                if ($kash777_model->isErrorInTrans()){
                    $failCount++;
                    $csv_row['reason'] = 'Trans_error';
                    array_push($rltPlayerBank['failed_list'], $csv_row);
                    $controller->utils->error_log("Import failed: [$failMessage]" , $csv_row);
                }
                $totalCount++;
                $kash777_model->endTrans();
            }
        });

        $rltPlayerBank['success_count'] = $totalCount - $failCount;
        $rltPlayerBank['row_count'] = $totalCount;
        $rltPlayerBank['failed_count'] = $failCount;
        $rltPlayerBank['column_count'] = count(self::IMPORT_PLAYER_BANK_CSV_HEADER);
        $rltPlayerBank['success']=true;
        $this->CI->utils->debug_log('process player bank:'.$rltPlayerBank['filename']);
    } // EOF processPlayerBank()

	public function importCSV(array $files, &$summary, &$message){
		// import_player_csv_file, import_aff_csv_file, import_aff_contact_csv_file, import_player_contact_csv_file, import_player_bank_csv_file

		$success=true;

		$rltAff=[
			'filename'=>$files['import_aff_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
		];
		$affMap=[];
		if(!empty($files['import_aff_csv_file'])){
			$this->processAff($rltAff, $affMap);
		}


		$rltAffContact=[
			'filename'=>$files['import_aff_contact_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
		];
		if(!empty($files['import_aff_contact_csv_file'])){
			$this->processAffContact($rltAffContact, $affMap);
		}


		$rltAgent=[
			'filename'=>$files['import_agency_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
		];
		$agentMap=[];
		if(!empty($files['import_agency_csv_file'])){
			$this->processAgent($rltAgent, $agentMap);
		}

		// $rltAgentContact=[
		// 	'filename'=>$files['import_agent_contact_csv_file'],
		// 	'success'=>true,
		// 	'failed_list'=>[],
		// 	'failed_count'=>0,
		// 	'success_count'=>0,
		// 	'column_count'=>0,
		// 	'row_count'=>0,
		// ];
		// $this->processAgentContact($rltAgentContact, $agentMap);

		$rltPlayer=[
			'filename'=>$files['import_player_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
		];
		$playerMap=[];
		if(!empty($files['import_player_csv_file'])){
			$this->processPlayer($rltPlayer, $affMap, $playerMap);
		    unset($affMap);
		}


		$rltPlayerContact=[
			'filename'=>$files['import_player_contact_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
		];
		if(!empty($files['import_player_contact_csv_file'])){
			$this->processPlayerContact($rltPlayerContact, $playerMap);
		}

		$rltPlayerBank=[
			'filename'=>$files['import_player_bank_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
		];
		if(!empty($files['import_player_bank_csv_file'])){
			$this->processPlayerBank($rltPlayerBank, $playerMap);
		}


		$summary=[
			'import_player_csv_file'=>$rltPlayer,
			'import_agency_csv_file'=>$rltAgent,
			'import_aff_csv_file'=>$rltAff,
			'import_aff_contact_csv_file'=>$rltAffContact,
			'import_player_contact_csv_file'=>$rltPlayerContact,
			'import_player_bank_csv_file'=>$rltPlayerBank,
		];
		$this->CI->utils->debug_log('Import Summary', $summary);
		$message=null;

		return $success;
	}

}

