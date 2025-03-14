<?php if (!defined('BASEPATH')) {

	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/abstract_importer.php';

/**
 * Cloned form Importer_kesh
 */
class Importer_fastwin extends Abstract_importer{

	function __construct() {
		parent::__construct();

		$this->import_player_csv_header=self::IMPORT_PLAYER_CSV_HEADER; // OGP-34976
	}

    const IMPORT_PLAYER_CSV_HEADER=[ 'FirstName'            // #01 For the fields, store to "playerdetails.firstName".
                                   , 'MiddleName'           // #02 For the fields, store to "playerdetails_extra.middleName".
                                                                // *** The value, 'NULL' or empty(), set NULL in the field.
                                   , 'LastName'             // #03 For the fields, store to "playerdetails.lastName".
                                   , 'BirthPlace'           // #04 For the fields, store to "playerdetails.birthplace".
                                                                // *** The value, 'NULL' or empty(), set NULL in the field.
                                   , 'DOB'                  // #05 For the fields, store to "playerdetails.birthdate".
                                                                // original csv value is "mm/dd/yyyy" ex: "11/10/1994"
                                                                // format csv value to YYYY-mm-dd, ex: "1987-02-24"
                                                                // re-format to "Y-m-d"
                                   , 'GenderID'             // #06 For the fields, store to "playerdetails.gender".
                                                                // - if get "1", means Male -> stroe value Male; other value, store value Female
                                   , 'AddressLine'          // #07 For the fields, store to "playerdetails.address".
                                   , 'Nationality'          // #08 For the fields, store to "playerdetails.citizenship".
                                   , 'MobileNumber'         // #09 For the fields, store to "playerdetails.contactnumber". 存10碼(不含國碼63)
                                                                // get from csv, will get 639185223345 or 9876543212
                                                                // - if get '63' at the beginning, [remove '63'] -> 9185223345, return this value and set player.verified_phone = 1
                                                                // - if not start with '63', return this value
                                   , 'Email'                // #10 For the fields, store to "player.email".
                                   , 'CreateDate'           // #11 For the fields, store to "player.createdOn". convert to "Y-m-d H:i:s", ex: "1987-02-24 00:00:00".
                                   , 'UserCode'             // #12 For the fields, store to "player.username".
                                   , 'EncryptCode'          // #13 For the fields, store to "playerdetails_extra.storeCode". (不用比對fastwin_outlet.encryptcode)
                                   , 'GlobalStatus'         // #14 For the fields, store to "player.status".
                                                                /**
                                                                    Original csv column is "global_status", will rename to "GlobalStatus"
                                                                    for player.status => active:0, inactive:1

                                                                    value from csv, 1 = activate , 0 = not active
                                                                    if value == 1, return 0 (player.status = 0, aka active)
                                                                    if valuse != 1 , return 1 (player.status = 1 aka inactive)
                                                                 */
                                   , 'NatureOfWork'         // #15 For the fields, store to "playerdetails_extra.natureWork".
                                   , 'SourceOfIncome'       // #16 For the fields, store to "playerdetails_extra.sourceIncome".
                                   , 'Balance'              // #17 The amount will call wallet_model::syncAllWallet().
                                                                // The related setting, importer_ole_balance_rate. Default as 1.
    ];

    private function processContactNumber($mobileNumber) {
        $dialingCode = null;
        $contactNumber = null;
        if (strpos($mobileNumber, '63') === 0) {
            $dialingCode = '63';
            $contactNumber = substr($mobileNumber, 2);
            return [$dialingCode, $contactNumber];
        }

        $contactNumber = $mobileNumber;
        return [$dialingCode, $contactNumber];
    }

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

    private function processPlayer(&$rltPlayer, &$playerMap){
		$this->CI->load->library(['player_library']);
		$this->CI->load->model(['fastwin_model', 'player_model', 'group_level', 'wallet_model', 'http_request', 'player_preference']);
		$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltPlayer['filename'];
		$ignore_first_row = true;

        $controller = $this;
		$player_library =  $this->CI->player_library;
		$fastwin_model =  $this->CI->fastwin_model;
		$player_model =  $this->CI->player_model;
		$group_level = $this->CI->group_level;
		$wallet_model = $this->CI->wallet_model;
		$http_request= $this->CI->http_request;
		$player_preference= $this->CI->player_preference;

		$totalCount = 0;
		$failCount  = 0;

        $importer_fastwin_balance_rate=$this->utils->getConfig('importer_fastwin_balance_rate');
		if(empty($importer_fastwin_balance_rate)){
			$importer_fastwin_balance_rate=1;
		}

		$this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
			use($controller, $player_library, $fastwin_model, $player_model, $group_level, $wallet_model, $http_request, $player_preference,
                $importer_fastwin_balance_rate, &$totalCount, &$failCount, &$rltPlayer) {
                try {
                    $fastwin_model->startTrans();

                    //compare log consistency of columns
                    $controller->utils->info_log("compare column headings" , self::IMPORT_PLAYER_CSV_HEADER, $csv_row);
                    $row = array_combine(self::IMPORT_PLAYER_CSV_HEADER, $csv_row);
                    $row = $controller->_extract_row($row);
                    $controller->utils->debug_log('row:', $row);

                    // player table
                    $externalId = null;
                    $levelId = '1';
                    $username = $row['UserCode'];
                    $password = '';

                    $balance =  empty($row['Balance']) ? 0: $row['Balance'];
                    //convert balance
				    $balance = round($balance * $importer_fastwin_balance_rate, 4);

                    $createdOnStr= !empty($row['CreateDate']) ? $row['CreateDate'] : '';
                    $createdOnStr = $controller->checkWrongDateTimeAndFix($createdOnStr);
                    $createdOn = $controller->utils->formatDateTimeForMysql(new DateTime($createdOnStr));

                    $affId = null;  //need to check whether need to add affiliateId
                    $currentDateTime = $controller->checkWrongDateTimeAndFix(false);
                    $LastLoginTime = $LastLogOutTime = $currentDateTime;

                    $verified_phone = Player_model::DB_FALSE;
                    $mobileNumber = !empty($row['MobileNumber']) ? $row['MobileNumber'] : null;
                    list($dialingCode, $contactNumber) = $controller->processContactNumber($mobileNumber);
                    if(!empty($contactNumber)){
                        $verified_phone = Player_model::DB_TRUE;
                    }

                    $extra = [
                        'verified_phone' => $verified_phone,
                        'email'     => !empty($row['Email']) ? $row['Email'] : '',
                        'createdOn' => $createdOn,
                        // for player.status => active:0, inactive:1
                        // But the field usually for display.
                        'status'    => Player_model::OLD_STATUS_ACTIVE,
                        /// for player.status =>
                        // block = 1;
                        // suspended = 5;
                        // selfexclusion = 7;
                        // blocked_failed_login_attempt
                        'blocked' => $row['GlobalStatus'] == '1' ? Player_model::OLD_STATUS_ACTIVE  : Player_model::BLOCK_STATUS,
                        'affiliateId' => null,
                        'lastLoginTime' => $LastLoginTime,
                        'lastLogoutTime' => $LastLogOutTime,
                        //	'frozen' => $frozen,
                    ];

                    // playerdetails table
                    $firstName = !empty($row['FirstName']) ? $row['FirstName'] : null;
                    $lastName = !empty($row['LastName']) ? $row['LastName'] : null;
                    $birthplace = !empty($row['BirthPlace']) ? $row['BirthPlace'] : null;

                    $birthday_str = !empty($row['DOB']) ? $row['DOB'] : null;
                    $birthday_str = $controller->checkWrongDateTimeAndFix($birthday_str);
                    $birthday_obj = new DateTime($birthday_str);

                    $gender = $row['GenderID'];
                    if($gender == 'NULL' || empty($gender)){
                        $gender = 'Female';
                    }elseif($gender == '1'){
                        $gender = 'Male';
                    }else{
                        $gender = 'Female';
                    }

                    $address = !empty($row['AddressLine']) ? $row['AddressLine'] : null;
                    $citizenship = !empty($row['Nationality']) ? $row['Nationality'] : null;

                    $details = [
                        'firstName' =>  $firstName,
                        'lastName' => $lastName,
                        'birthplace' => $birthplace,
                        'birthdate' => $controller->utils->formatDateForMysql($birthday_obj),
                        'gender' => $gender,
                        'address' => $address,
                        'citizenship' => $citizenship,
                        'dialing_code' => $dialingCode,
                        'contactNumber' => $contactNumber,
                    ];

                    // playerdetails_extra table
                    $playerDetailsExtra = [
                        'middleName'    => !empty($row['MiddleName']) ? $row['MiddleName'] : null,
                        'storeCode'     => !empty($row['EncryptCode']) ? $row['EncryptCode'] : null,
                        'natureWork'    => !empty($row['NatureOfWork']) ? $row['NatureOfWork'] : null,
                        'sourceIncome'  => !empty($row['SourceOfIncome']) ? $row['SourceOfIncome'] : null,
                    ];

                    $failMessage = '';
                    $importPlayerId = $fastwin_model->importPlayer( $externalId // #1
                                                                    , $levelId // #2
                                                                    , $username // #3
                                                                    , $password // #4
                                                                    , $balance // #5
                                                                    , $extra // #6
                                                                    , $details // #7
                                                                    , $failMessage // #8
                                                                    , $group_level // #9
                                                                    , $wallet_model // #10
                                                                    , $player_model // #11
                                                                    , $http_request // #12
                                                                    , $playerDetailsExtra // #13
                                                                    , $player_library // #14
                                                                    , $player_preference // #15
                                                                );

                    $totalCount++;

                    if(empty($importPlayerId)){
                        $controller->utils->debug_log('Import failed reason: ', $failMessage);
                        throw new Exception($failMessage);
                    }

                    if ($fastwin_model->isErrorInTrans()) {
                        $controller->utils->error_log("Import failed: processPlayer error inserting data", 'username', $username);
                        throw new Exception('Trans_error');
                    }

                    $fastwin_model->endTrans();
                    $controller->utils->debug_log('Import success: ', $importPlayerId);
                } catch (Exception $e) {
                    $failCount++;
                    $csv_row['reason'] = $e->getMessage();
                    array_push($rltPlayer['failed_list'], $csv_row);
                    $fastwin_model->rollbackTrans();
                }
        });

		$rltPlayer['success_count'] = $totalCount - $failCount;
		$rltPlayer['row_count'] = $totalCount;
		$rltPlayer['failed_count'] = $failCount;
		$rltPlayer['column_count'] = count(self::IMPORT_PLAYER_CSV_HEADER);
		$rltPlayer['success']=true;
		$this->CI->utils->debug_log('process file:'.$rltPlayer['filename']);
	} // EOF processPlayer()

	public function importCSV(array $files, &$summary, &$message){
		$success=true;
        $playerMap=[];

        $rltPlayer=[
			'filename'=>$files['import_player_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
		];

		if(!empty($files['import_player_csv_file'])){
			$this->processPlayer($rltPlayer, $playerMap);
		}

		$summary=[
			'import_player_csv_file'=>$rltPlayer,
            /*
                // 'import_agency_csv_file'=>$rltAgent,
                // 'import_aff_csv_file'=>$rltAff,
                // 'import_aff_contact_csv_file'=>$rltAffContact,
                // 'import_player_contact_csv_file'=>$rltPlayerContact,
                // 'import_player_bank_csv_file'=>$rltPlayerBank,
            */
		];
		$this->CI->utils->debug_log('Import Summary', $summary);
		$message=null;

		return $success;
	}

}

