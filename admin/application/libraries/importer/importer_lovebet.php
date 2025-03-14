<?php if (!defined('BASEPATH')) {

	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/abstract_importer.php';

class Importer_lovebet extends Abstract_importer{

	function __construct() {
		parent::__construct();

		$this->import_tag_csv_header=self::IMPORT_TAG_CSV_HEADER;
		$this->import_banktype_csv_header=self::IMPORT_BANKTYPE_CSV_HEADER;
		$this->import_aff_csv_header=self::IMPORT_AFF_CSV_HEADER;
		$this->import_aff_bank_csv_header=self::IMPORT_AFF_BANK_CSV_HEADER;
		$this->import_aff_links_csv_header=self::IMPORT_AFF_LINKS_CSV_HEADER;
		$this->import_player_csv_header=self::IMPORT_PLAYER_CSV_HEADER;
		$this->import_player_bank_csv_header=self::IMPORT_PLAYER_BANK_CSV_HEADER;
		$this->import_player_transactions_total_csv_header=self::IMPORT_PLAYER_TRANSACTIONS_TOTAL_CSV_HEADER;

		$this->prefix = 'LB';	
	}

	const IMPORT_TAG_CSV_HEADER=[
		//tag
		'TagName', //tag.tagName
		'TagDescription', //tag.tagDescription
		'TagColor', //tag.tagColor
	];

	const IMPORT_AFF_TAG_CSV_HEADER=[
		//tag
		'TagName', //affiliatetaglist.tagName
		'TagDescription', //affiliatetaglist.tagDescription		
	];
	
	//affiliates, tag
	const IMPORT_AFF_CSV_HEADER=[
		'AffiliateId', //affiliates.affiliateId
		'AffiliateUserName', //affiliates.username
		'AffiliateTrackingCode', //affiliates.trackingCode
		'Password', //affiliates.password 
		'FirstName', //affiliates.firstname 
		'LastName', //affiliates.lastname 
		'Email', //affiliates.email 
		'Mobile', //affiliates.mobile 
		'Currency', //affiliates.currency
		'Country', //affiliates.country
		'Location', //affiliates.location
		'IPAddress', //affiliates.ip_address
		'CreatedOn', //affiliates.createdOn
		'UpdatedOn', //affiliates.updatedOn
		'ParentAffiliateUsername', //get affiliates.username by affiliates.parentId
		'Status', //affiliates.status int
		'LevelNumber', //affiliates.level_number int
		'CountSub', //affiliates.countSub int
		'CountPlayer', //affiliates.countPlayer int
		'SecondPassword', //affiliates.second_password 
		'Domain', //affiliates.affdomain 
		'Gender',//affiliates.gender 

		'Birthday',//affiliates.birthday 
		'PlayerPrefix',//affiliates.prefix_of_player 
		'Occupation',//affiliates.occupation 
		'Company',//affiliates.company 
		'Website',//affiliates.website 
		'ImType1',//affiliates.imType1 
		'Im1',//affiliates.im1 
		'ImType2',//affiliates.imType2 
		'Im2',//affiliates.im2 
		'Address',//affiliates.address 
		'City',//affiliates.city 
		'State',//affiliates.state 
		'ZipCode',//affiliates.zip 
		'Phone',//affiliates.phone 

		'WalletBalance',//affiliates.wallet_balance 

		//affiliatetag
		'AffiliateTag',//comma delimited tag codes
	];

	const IMPORT_AFF_TERMS_CSV_HEADER=[		
		'AffiliateUserName', //affiliates.username
		'OptionType', //affiliate_terms.optionType		
		'OptionValue', //affiliate_terms.optionValue		
	];

	//affiliatepayment, affiliates
	const IMPORT_AFF_BANK_CSV_HEADER=[
		'AffiliateUserName', //affiliates.username
		'PaymentMethod', //affiliatepayment.paymentMethod
		'BankAccountName', //affiliatepayment.accountName
		'BankAccountNo', //affiliatepayment.accountNumber
		'BankName', //affiliatepayment.bankName		
		'AccountInfo', //affiliatepayment.accountInfo		
		'CreatedOn', //affiliatepayment.createdOn
		'UpdatedOn', //affiliatepayment.updatedOn		
	];

	//affiliates, aff_tracking_link
	const IMPORT_AFF_LINKS_CSV_HEADER=[
		'AffiliateUserName', //affiliates.username
		'TrackingDomain', //aff_tracking_link.tracking_domain
		'TrackingType', //aff_tracking_link.tracking_type
		'CreatedAt', //aff_tracking_link.created_at
		'UpdatedAt', //aff_tracking_link.updated_at		
	];

	//banktype check if bank type exist by bankName before insert, to check if can manually compared to OLETH banktype
	const IMPORT_BANKTYPE_CSV_HEADER=[
		//banktype
		'BankTypeID', //banktype.bankTypeId
		'BankTypeName', //banktype.bankName
		'BankCode', //banktype.bank_code		
	];

	//player, affiliates, playerdetails, tag
	const IMPORT_PLAYER_CSV_HEADER=[
		//player
		'PlayerId', //not needed will use username player.playerId
		'PlayerUsername', //to add 'LB' prefix player.username
		'Password', //player.password		
		'IsActive', //player.active int
		'Email', //player.email		
		'LastLoginIP', //player.lastLoginIp
		'LastLoginTime', //player.lastLoginTime
		'LastLogoutTime', //player.lastLogoutTime
		'LastActivityTime', //player.lastActivityTime
		'CreatedOn', //player.createdOn
		'UpdatedOn', //player.updatedOn
		'InvitationCode', //player.invitationCode
		'VerifyHash', //player.verify
		'RegisteredBy', //player.registered_by
		'EnabledWithdrawal', //player.enabled_withdrawal
		'ApprovedDepositCount', //player.approved_deposit_count
		'DeclinedDepositCount', //player.declined_deposit_count
		'TotalDepositCount', //player.total_deposit_count
		'TotalBettingAmount', // player.totalBettingAmount		
		'TotalDepositAmount', //player.totalDepositAmount
		'VIPLevel',//player.levelId
		'VIPGroupName',//player.groupName
		'VIPLevelName',//player.levelName
		'IsVerifiedEmail',//player.verified_email int
		'ApprovedWithdrawCount', //player.approvedWithdrawCount
		'ApprovedWithdrawAmount', //player.approvedWithdrawAmount
		'AffiliateUserName', // affiliates.username to add prefix 'LB' in case of duplicate, to get aff username by aff id
		'ActiveStatus', //player.active_status
		'IsVerifiedPhone', //player.verified_phone int
		'SecureId', //player.secure_id
		'WalletBalance',//player.total_real
		'TrackingCode',//player.tracking_code
		'IsDisabledCashback',//player.disabled_cashback int
		'IsDisabledPromotion',//player.disabled_promotion int
		'WithrawPassword',//player.withdraw_password
		'IsPhoneRegistered',//player.is_phone_registered
		//'DispatchAccountLevel',//player.dispatch_account_level_id TO VERIFY
		'FirstDesposit',//player.first_deposit
		'SecondDesposit',//player.second_deposit
		'ReferredBy',//to get referral user id by player.refereePlayerId

		//playerdetails
		'FirstName', //playerdetails.firstName
		'LastName', //playerdetails.lastName
		'Gender', //playerdetails.gender
		'Language', //playerdetails.language
		'Birthdate', //playerdetails.birthdate yyyy-mm-dd
		'ContactNumber', //playerdetails.contactnumber
		'RegisteredWebsite', //playerdetails.registrationWebsite
		'LineAccount', //playerdetails.imAccount
		'WeChatAccount', //playerdetails.imAccount2		
		'RegistrationIP',  //playerdetails.registrationIP	
		'IDCardNumber',  //playerdetails.id_card_number	

		//player tags
		'PlayerTags',//comma delimited tag codes
	];

	//playerbankdetails
	const IMPORT_PLAYER_BANK_CSV_HEADER=[
		'PlayerUsername', //player.username
		'BankTypeName', //banktype.bankName
		'BankTypeID', //banktype.bankTypeId
		'UserBankAccountName', //playerbankdetails.bankAccountFullName
		'BankAccountNo', //playerbankdetails.bankAccountNumber
		'BranchBankName', //playerbankdetails.branch		
		'IsDefault', //playerbankdetails.isDefault int
		'DWBank', //playerbankdetails.dwBank int
		'Status', //playerbankdetails.status int
		'IsVerified', //playerbankdetails.verified int
		'Phone', //playerbankdetails.phone
		'CustomBankName', //playerbankdetails.customBankName
		'ExternalId', //playerbankdetails.external_id
		'CreatedOn', //playerbankdetails.createdOn
		'UpdatedOn', //playerbankdetails.updatedOn
		'Province', //playerbankdetails.province
		'City', //playerbankdetails.city
		'BankAddress', //playerbankdetails.bankAddress
		
	];

	//transactions, player | groubBy to_id, from_id, transaction_type, subwallet_id
	const IMPORT_PLAYER_TRANSACTIONS_TOTAL_CSV_HEADER=[
		//player
		'PlayerUsername', //player.username

		//transactions
		'TransactionType', //transactions.transaction_type
		//'FromUsername', //transactions.from_username
		//'FromType', //transactions.from_type mainwallet=1 or subwallet=2
		'ToUsername', //transactions.to_username
		'ToType', //transactions.to_type mainwallet=1 or subwallet=2
		'Amount', //SUM(transactions.amount)
		'TransactionCount', //COUNT(t.id)
		'TransactionDate', //player.createdOn
		'SubWalletId', //transactions.sub_wallet_id		
	];

	//banktype mapping bigbet=>ole777th
	/*const BANKTYPE_MAP=[
		'28'=>'28',
		'29'=>'29',
		'30'=>'30',
		'31'=>'31',
		'32'=>'32',
		'33'=>'33',
		'34'=>'34',
		'35'=>'35',
		'36'=>'36',
		'37'=>'37',
		'38'=>'38',
		'39'=>'39',
		'40'=>'40',
		'41'=>'41',
		'42'=>'42',
		'43'=>'43',
		'44'=>'44',
		'47'=>'65',
		'52'=>'32'
	];*/

	//vip level mapping
	/*const VIPGROUP_MAP=[
		
		'default' => [
			'levelId'=> '53',
			'levelName'=> 'LB New Member',
			'groupName'=> 'Default Player Group - LB' 
		],
		'1' => [
			'levelId'=> '53',
			'levelName'=> 'LB New Member',
			'groupName'=> 'Default Player Group - LB'  
		],
		'43' => [
			'levelId'=> '54',
			'levelName'=> 'LB VIP1',
			'groupName'=> 'Default Player Group - LB'  
		],
		'44' => [
			'levelId'=> '56',
			'levelName'=> 'LB VIP3',
			'groupName'=> 'Default Player Group - LB'  
		],
		'45' => [
			'levelId'=> '57',
			'levelName'=> 'LB VIP4',
			'groupName'=> 'Default Player Group - LB'  
		],
		'46' => [
			'levelId'=> '58',
			'levelName'=> 'LB VIP5',
			'groupName'=> 'Default Player Group - LB'  
		],
		'47' => [
			'levelId'=> '58',
			'levelName'=> 'LB VIP5',
			'groupName'=> 'Default Player Group - LB'  
		],
		'49' => [
			'levelId'=> '59',
			'levelName'=> 'LB VIP6',
			'groupName'=> 'Default Player Group - LB'  
		],
		'50' => [
			'levelId'=> '61',
			'levelName'=> 'LB VIP8',
			'groupName'=> 'Default Player Group - LB'  
		],
		
		'default' => [
			'levelId'=> '1',
			'levelName'=> 'New Member (Starter)',
			'groupName'=> 'Default Player Group' 
		],
		'1' => [
			'levelId'=> '3',
			'levelName'=> 'Silver',
			'groupName'=> 'Default Player Group'  
		],
	];*/

	/*const PLAYER_TAG_MAP=[
		"WC2"=>"WC4",
		"WC3"=>"WC5"
	];*/

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
	
	//##################### EXPORT METHODS #####################//

	private function processExportTag(&$rltTag, &$affMap){
		
		$this->CI->load->model(array('lovebet_model'));		
		//get data

		$lovebet_model = $this->CI->lovebet_model;
		
		//export as csv file
		$uploadCsvFilepath=$this->CI->utils->getSharingUploadPath('/upload_temp_csv');
		$result = $lovebet_model->getTags();
		
		$additionalTag = ['tagName'=>'Imported From Lovebet', 'tagDescription'=>'Imported From Lovebet', 'tagColor'=>'#eb4d0e'];
		$result[] = $additionalTag;
		$fileName = isset($rltTag['filename'])?$rltTag['filename']:'exported_lovebet_tags';
		$file = $this->exportCSVFile($fileName, self::IMPORT_TAG_CSV_HEADER, $result, $uploadCsvFilepath);
		if(!$file){
			return false;
		}
		
		$rltTag['filename'] = $file;
		$rltTag['success_count'] = count($result);
		$rltTag['row_count'] = count($result);
		$rltTag['failed_count'] = 0;
		$rltTag['column_count'] = count(self::IMPORT_TAG_CSV_HEADER);
		$rltTag['success']=true;
		$this->CI->utils->debug_log('processExportTag file:'.$rltTag['filename']);
		return true;
	}

	private function processExportAffTag(&$rltTag, &$affMap){
		
		$this->CI->load->model(array('lovebet_model'));		
		//get data

		$lovebet_model = $this->CI->lovebet_model;
		
		//export as csv file
		$uploadCsvFilepath=$this->CI->utils->getSharingUploadPath('/upload_temp_csv');
		$result = $lovebet_model->getAffTags();	
		
		$additionalTag = ['tagName'=>'Imported From Lovebet', 'tagDescription'=>'Imported From Lovebet'];
		$result[] = $additionalTag;	
		$fileName = isset($rltTag['filename'])?$rltTag['filename']:'exported_lovebet_aff_tags';
		$file = $this->exportCSVFile($fileName, self::IMPORT_AFF_TAG_CSV_HEADER, $result, $uploadCsvFilepath);
		if(!$file){
			return false;
		}
		
		$rltTag['filename'] = $file;
		$rltTag['success_count'] = count($result);
		$rltTag['row_count'] = count($result);
		$rltTag['failed_count'] = 0;
		$rltTag['column_count'] = count(self::IMPORT_AFF_TAG_CSV_HEADER);
		$rltTag['success']=true;
		$this->CI->utils->debug_log('processExportAffTag file:'.$rltTag['filename']);
		return true;
	}
	
	private function processExportAff(&$rltAff, &$affMap){
		
		$this->CI->load->model(array('lovebet_model'));		
		//get data

		$lovebet_model = $this->CI->lovebet_model;
		
		//export as csv file
		$uploadCsvFilepath=$this->CI->utils->getSharingUploadPath('/upload_temp_csv');
		$result = $lovebet_model->getAffs();		
		$this->CI->load->library('salt');	

		foreach($result as $key => $row){
			$password = $this->CI->salt->decrypt($row['Password'], $this->getDeskeyOG());
			$result[$key]['Password'] = $password;
		}

		$fileName = isset($rltAff['filename'])?$rltAff['filename']:'exported_lovebet_affs';
		$file = $this->exportCSVFile($fileName, self::IMPORT_AFF_CSV_HEADER, $result, $uploadCsvFilepath);
		if(!$file){
			return false;
		}
		
		$rltAff['filename'] = $file;
		$rltAff['success_count'] = count($result);
		$rltAff['row_count'] = count($result);
		$rltAff['failed_count'] = 0;
		$rltAff['column_count'] = count(self::IMPORT_TAG_CSV_HEADER);
		$rltAff['success']=true;
		$this->CI->utils->debug_log('processExportAff file:'.$rltAff['filename']);
		return true;
	}

	private function processExportAffTerms(&$rltTerms, &$affMap){
		
		$this->CI->load->model(array('lovebet_model'));		
		//get data

		$lovebet_model = $this->CI->lovebet_model;
		
		//export as csv file
		$uploadCsvFilepath=$this->CI->utils->getSharingUploadPath('/upload_temp_csv');
		$result = $lovebet_model->getAffTerms();		
		$fileName = isset($rltTerms['filename'])?$rltTerms['filename']:'exported_lovebet_aff_terms';
		$file = $this->exportCSVFile($fileName, self::IMPORT_AFF_TERMS_CSV_HEADER, $result, $uploadCsvFilepath);
		if(!$file){
			return false;
		}
		
		$rltTerms['filename'] = $file;
		$rltTerms['success_count'] = count($result);
		$rltTerms['row_count'] = count($result);
		$rltTerms['failed_count'] = 0;
		$rltTerms['column_count'] = count(self::IMPORT_AFF_TERMS_CSV_HEADER);
		$rltTerms['success']=true;
		$this->CI->utils->debug_log('processExportAffTerms file:'.$rltTerms['filename']);
		return true;
	}

	private function processExportAffBank(&$rltAffbank, &$affMap){
		
		$this->CI->load->model(array('lovebet_model'));		
		//get data

		$lovebet_model = $this->CI->lovebet_model;
		
		//export as csv file
		$uploadCsvFilepath=$this->CI->utils->getSharingUploadPath('/upload_temp_csv');
		$result = $lovebet_model->getAffBank();		
		$fileName = isset($rltAffbank['filename'])?$rltAffbank['filename']:'exported_lovebet_aff_bank';
		$file = $this->exportCSVFile($fileName, self::IMPORT_AFF_BANK_CSV_HEADER, $result, $uploadCsvFilepath);
		if(!$file){
			return false;
		}
		
		$rltAffbank['filename'] = $file;
		$rltAffbank['success_count'] = count($result);
		$rltAffbank['row_count'] = count($result);
		$rltAffbank['failed_count'] = 0;
		$rltAffbank['column_count'] = count(self::IMPORT_AFF_BANK_CSV_HEADER);
		$rltAffbank['success']=true;
		$this->CI->utils->debug_log('processExportAffTerms file:'.$rltAffbank['filename']);
		return true;
	}

	private function processExportAffLinks(&$rltAffLinks, &$affMap){
		
		$this->CI->load->model(array('lovebet_model'));		
		//get data

		$lovebet_model = $this->CI->lovebet_model;
		
		//export as csv file
		$uploadCsvFilepath=$this->CI->utils->getSharingUploadPath('/upload_temp_csv');
		$result = $lovebet_model->getAffLinks();		
		$fileName = isset($rltAffLinks['filename'])?$rltAffLinks['filename']:'exported_lovebet_aff_bank';
		$file = $this->exportCSVFile($fileName, self::IMPORT_AFF_LINKS_CSV_HEADER, $result, $uploadCsvFilepath);
		if(!$file){
			return false;
		}
		
		$rltAffLinks['filename'] = $file;
		$rltAffLinks['success_count'] = count($result);
		$rltAffLinks['row_count'] = count($result);
		$rltAffLinks['failed_count'] = 0;
		$rltAffLinks['column_count'] = count(self::IMPORT_AFF_LINKS_CSV_HEADER);
		$rltAffLinks['success']=true;
		$this->CI->utils->debug_log('processExportAffTerms file:'.$rltAffLinks['filename']);
		return true;
	}
	
	private function processExportPlayer(&$rltPlayer, &$affMap){
		
		$this->CI->load->model(array('lovebet_model','player_model'));		
		//get data

		$lovebet_model = $this->CI->lovebet_model;
		
		//export as csv file
		$uploadCsvFilepath=$this->CI->utils->getSharingUploadPath('/upload_temp_csv');
		$result = $lovebet_model->getPlayers();	
		$this->CI->load->library('salt');								
		
		foreach($result as $key => $row){
			$password = $this->CI->salt->decrypt($row['Password'], $this->getDeskeyOG());
			$result[$key]['Password'] = $password;
		}

		$fileName = isset($rltPlayer['filename'])?$rltPlayer['filename']:'exported_lovebet_players';
		$file = $this->exportCSVFile($fileName, self::IMPORT_PLAYER_CSV_HEADER, $result, $uploadCsvFilepath);
		if(!$file){
			return false;
		}
		
		$rltPlayer['filename'] = $file;
		$rltPlayer['success_count'] = count($result);
		$rltPlayer['row_count'] = count($result);
		$rltPlayer['failed_count'] = 0;
		$rltPlayer['column_count'] = count(self::IMPORT_PLAYER_CSV_HEADER);
		$rltPlayer['success']=true;
		$this->CI->utils->debug_log('processExportPlayer file:'.$rltPlayer['filename']);
		return true;
	}
	
	private function processExportPlayerBankDetails(&$rltPlayer, &$affMap){
		
		$this->CI->load->model(array('lovebet_model'));		
		//get data

		$lovebet_model = $this->CI->lovebet_model;
		
		//export as csv file
		$uploadCsvFilepath=$this->CI->utils->getSharingUploadPath('/upload_temp_csv');
		$result = $lovebet_model->getPlayerBank();		
		$fileName = isset($rltPlayer['filename'])?$rltPlayer['filename']:'exported_lovebet_players_transactions';
		$file = $this->exportCSVFile($fileName, self::IMPORT_PLAYER_BANK_CSV_HEADER, $result, $uploadCsvFilepath);
		if(!$file){
			return false;
		}
		
		$rltPlayer['filename'] = $file;
		$rltPlayer['success_count'] = count($result);
		$rltPlayer['row_count'] = count($result);
		$rltPlayer['failed_count'] = 0;
		$rltPlayer['column_count'] = count(self::IMPORT_PLAYER_BANK_CSV_HEADER);
		$rltPlayer['success']=true;
		$this->CI->utils->debug_log('processExportPlayerBankDetails file:'.$rltPlayer['filename']);
		return true;
	}
	
	private function processExportPlayersTransactions(&$rltPlayer, &$affMap){
		
		$this->CI->load->model(array('lovebet_model'));		
		//get data

		$lovebet_model = $this->CI->lovebet_model;
		
		//export as csv file
		$uploadCsvFilepath=$this->CI->utils->getSharingUploadPath('/upload_temp_csv');
		$result = $lovebet_model->getPlayersTransactions();		
		$fileName = isset($rltPlayer['filename'])?$rltPlayer['filename']:'exported_lovebet_players_transactions';
		$file = $this->exportCSVFile($fileName, self::IMPORT_PLAYER_TRANSACTIONS_TOTAL_CSV_HEADER, $result, $uploadCsvFilepath);
		if(!$file){
			return false;
		}
		
		$rltPlayer['filename'] = $file;
		$rltPlayer['success_count'] = count($result);
		$rltPlayer['row_count'] = count($result);
		$rltPlayer['failed_count'] = 0;
		$rltPlayer['column_count'] = count(self::IMPORT_PLAYER_TRANSACTIONS_TOTAL_CSV_HEADER);
		$rltPlayer['success']=true;
		$this->CI->utils->debug_log('processExportPlayersTransactions file:'.$rltPlayer['filename']);
		return true;
	}

	//##################### IMPORT METHODS #####################//

	private function processTag(&$rltTag, &$affMap){

		$this->CI->load->model(array('affiliatemodel','lovebet_model'));
		$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltTag['filename'];
		$ignore_first_row = true;		
		$controller = $this;
		$lovebet_model = $this->CI->lovebet_model;
		$failCount = 0;
		$totalCount = 0;

		$this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
			use($controller, $lovebet_model, &$totalCount, &$failCount, &$rltTag) {

				$controller->utils->debug_log("compare column headings" , self::IMPORT_TAG_CSV_HEADER, $csv_row);
				$row = array_combine(self::IMPORT_TAG_CSV_HEADER, $csv_row);
				$externalId = $row['TagName'];

				if(empty($externalId)){
					$failCount++;
					$csv_row['reason'] = 'No TagName Found on CSV';
					array_push($rltTag['failed_list'], $csv_row);
				}

				$tagName = $row['TagName'];
				$tagDescription = $row['TagDescription'];
				$tagColor = $row['TagColor'];

				$lovebet_model->startTrans();
				$failMessage = '';
				$this->utils->debug_log('importTag params',$externalId,$tagName, $tagDescription,
					$tagColor );
				$tagId = $lovebet_model->importTag($tagName, $tagDescription, $tagColor);
				if ($lovebet_model->isErrorInTrans()) {
					$failCount++;
					$csv_row['reason'] = 'Trans_error';
					array_push($rltTag['failed_list'], $csv_row);
					$controller->utils->error_log("Import failed: [$failMessage]" , $csv_row);
				}
				$lovebet_model->endTrans();
				$totalCount++;
		});//

		$rltTag['success_count'] = $totalCount - $failCount;
		$rltTag['row_count'] = $totalCount;
		$rltTag['failed_count'] = $failCount;
		$rltTag['column_count'] = count(self::IMPORT_TAG_CSV_HEADER);
		$rltTag['success']=true;
		$this->CI->utils->debug_log('process tag file:'.$rltTag['filename']);
	}	

	private function processAffTag(&$rltTag, &$affMap){

		$this->CI->load->model(array('affiliatemodel','lovebet_model'));
		$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltTag['filename'];
		$ignore_first_row = true;		
		$controller = $this;
		$lovebet_model = $this->CI->lovebet_model;
		$failCount = 0;
		$totalCount = 0;

		$this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
			use($controller, $lovebet_model, &$totalCount, &$failCount, &$rltTag) {

				$controller->utils->debug_log("compare column headings" , self::IMPORT_AFF_TAG_CSV_HEADER, $csv_row);
				$row = array_combine(self::IMPORT_AFF_TAG_CSV_HEADER, $csv_row);
				$externalId = $row['TagName'];

				if(empty($externalId)){
					$failCount++;
					$csv_row['reason'] = 'No TagName Found on CSV';
					array_push($rltTag['failed_list'], $csv_row);
				}

				$tagName = $row['TagName'];
				$tagDescription = $row['TagDescription'];				

				$lovebet_model->startTrans();
				$failMessage = '';
				$this->utils->debug_log('importAffTag params',$externalId,$tagName, $tagDescription);
				$tagId = $lovebet_model->importAffTag($tagName, $tagDescription);
				if ($lovebet_model->isErrorInTrans()) {
					$failCount++;
					$csv_row['reason'] = 'Trans_error';
					array_push($rltTag['failed_list'], $csv_row);
					$controller->utils->error_log("Import failed: [$failMessage]" , $csv_row);
				}
				$lovebet_model->endTrans();
				$totalCount++;
		});//

		$rltTag['success_count'] = $totalCount - $failCount;
		$rltTag['row_count'] = $totalCount;
		$rltTag['failed_count'] = $failCount;
		$rltTag['column_count'] = count(self::IMPORT_TAG_CSV_HEADER);
		$rltTag['success']=true;
		$this->CI->utils->debug_log('process tag file:'.$rltTag['filename']);
	}

	private function processAff(&$rltAff, &$affMap){

		$this->CI->load->model(array('affiliatemodel','lovebet_model'));
		$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltAff['filename'];
		$ignore_first_row = true;
		$controller = $this;
		$lovebet_model = $this->CI->lovebet_model;
		$affiliatemodel = $this->CI->affiliatemodel;
		$failCount = 0;
		$totalCount = 0;
		$this->CI->load->library('salt');
		$salt = $this->CI->salt;

		$this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
			use($controller, $salt, $affiliatemodel, $lovebet_model, &$totalCount, &$failCount, &$rltAff) {

				try {
					$lovebet_model->startTrans();

					$controller->utils->debug_log("compare column headings" , self::IMPORT_AFF_CSV_HEADER, $csv_row);
					$row = array_combine(self::IMPORT_AFF_CSV_HEADER, $csv_row);
					$externalId = $row['AffiliateUserName'];

					if(empty($externalId)){						
						throw new Exception('No AffiliateUserName Found on CSV');
					}	
					$externalId = $this->prefix.$externalId;			

					$importData = [];
					unset($importData['AffiliateId']);
					$affiliateUserName = $this->prefix.$row['AffiliateUserName'];
					$importData['username'] = $affiliateUserName;
					$affId = $affiliatemodel->getAffiliateIdByUsername($affiliateUserName);
					if(!empty($affId)){
						$controller->utils->debug_log("importer_lovebet processAff data already exists" , $row);
						throw new Exception('Affiliate already exists');
					}

					$importData['trackingCode'] = $row['AffiliateTrackingCode'];
					
					$password = $salt->encrypt($row['Password'], $controller->getDeskeyOG());
					$importData['password'] = $password;
					$importData['firstname'] = $row['FirstName'];
					$importData['lastname'] = $row['LastName'];
					$importData['email'] = $row['Email'];
					$importData['mobile'] = $row['Mobile'];
					$importData['currency'] = $row['Currency'];
					$importData['country'] = $row['Country'];
					$importData['location'] = $row['Location'];
					$importData['ip_address'] = $row['IPAddress'];

					$createdOnStr= $row['CreatedOn'] == 'NULL' ? '' : $row['CreatedOn'];
					$createdOn = $controller->checkWrongDateTimeAndFix($createdOnStr);
					$createdOn = $controller->utils->formatDateTimeForMysql(new DateTime($createdOn));
					$importData['createdOn'] = $createdOn;

					$updatedOnStr= $row['UpdatedOn'] == 'NULL' ? '' : $row['UpdatedOn'];
					$updatedOn = $controller->checkWrongDateTimeAndFix($updatedOnStr);
					$updatedOn = $controller->utils->formatDateTimeForMysql(new DateTime($updatedOn));
					$importData['updatedOn'] = $updatedOn;

					//process parent id
					$importData['parentId'] = 0;				
					if(!empty($row['ParentAffiliateUsername']) && $row['ParentAffiliateUsername']!=0){
						$parentAffiliateUsername = $this->prefix.$row['ParentAffiliateUsername'];
						$parentAffId = $affiliatemodel->getAffiliateIdByUsername($parentAffiliateUsername);					
						if(!empty($parentAffId)){
							$importData['parentId'] = $parentAffId; 
						}else{
							//identifies that it has a parent set but cannot get the parent affiliate ID
							$importData['parentId'] = -1; 
						}
					}

					$importData['ip_address'] = $row['IPAddress'];
					$importData['status'] = (int)$row['Status'];
					$importData['levelNumber'] = (int)$row['LevelNumber'];
					$importData['countSub'] = (int)$row['CountSub'];
					$importData['countPlayer'] = (int)$row['CountPlayer'];
					$importData['second_password'] = $row['SecondPassword'];
					$importData['affdomain'] = empty($row['Domain'])?null:$row['Domain'];
					$importData['gender'] = $row['Gender'];					

					$importData['birthday'] = $row['Birthday'];					
					$importData['prefix_of_player'] = empty($row['PlayerPrefix'])?null:$row['PlayerPrefix'];					
					$importData['occupation'] = $row['Occupation'];					
					$importData['company'] = $row['Company'];					
					$importData['website'] = $row['Website'];					
					$importData['imType1'] = $row['ImType1'];					
					$importData['im1'] = $row['Im1'];					
					$importData['imType2'] = $row['ImType2'];					
					$importData['im2'] = $row['Im2'];					
					$importData['address'] = $row['Address'];			
					$importData['state'] = $row['State'];			
					$importData['zip'] = $row['ZipCode'];				
					$importData['phone'] = $row['Phone'];				
					$importData['city'] = $row['City'];			
					$importData['wallet_balance'] = $row['WalletBalance'];							

					//insert affiliate
					$insertedAffId = $lovebet_model->importAffiliate($externalId,$importData);
					if(!$insertedAffId){
						$controller->utils->debug_log("importer_lovebet processAff error inserting data" , $importData);
						throw new Exception('Error inserting affiliate');
					}

					//process affiliate tags
					$tags = explode(',',$row['AffiliateTag']);
					$tags[] = 'Imported From Lovebet';
					foreach($tags as $tag){
						$this->CI->db->from('affiliatetaglist')->where('tagName', $tag);
						$tagId = $affiliatemodel->runOneRowOneField('tagId');
						if(empty($tagId)){
							continue;
						}

						//insert afftags
						$lovebet_model->tagAffiliate($insertedAffId,$tagId);
					}

					if ($lovebet_model->isErrorInTrans()) {
						$controller->utils->error_log("Import failed: processAff error inserting data" , $importData);
						throw new Exception('Trans_error');
					}

					$totalCount++;
					$lovebet_model->endTrans();
				} catch (Exception $e) {
					$failCount++;
					$csv_row['reason'] = $e->getMessage();
					array_push($rltAff['failed_list'], $csv_row);
					$lovebet_model->rollbackTrans();
				}
		});//

		$rltAff['success_count'] = $totalCount - $failCount;
		$rltAff['row_count'] = $totalCount;
		$rltAff['failed_count'] = $failCount;
		$rltAff['column_count'] = count(self::IMPORT_AFF_CSV_HEADER);
		$rltAff['success']=true;
		$this->CI->utils->debug_log('process aff file:'.$rltAff['filename']);
	}

	private function processAffTerms(&$rltAffTerms, &$affMap){

		$this->CI->load->model(array('affiliatemodel','lovebet_model'));
		$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltAffTerms['filename'];
		$ignore_first_row = true;		
		$controller = $this;
		$lovebet_model = $this->CI->lovebet_model;
		$affiliatemodel = $this->CI->affiliatemodel;
		$failCount = 0;
		$totalCount = 0;

		$this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
			use($controller, $lovebet_model, $affiliatemodel, &$totalCount, &$failCount, &$rltAffTerms) {
				try {
					$lovebet_model->startTrans();
					$controller->utils->debug_log("compare column headings" , self::IMPORT_AFF_TERMS_CSV_HEADER, $csv_row);
					$row = array_combine(self::IMPORT_AFF_TERMS_CSV_HEADER, $csv_row);
					
					//$affiliateUserName = $row['AffiliateUserName'];
					$affiliateUserName = $this->prefix.$row['AffiliateUserName'];
					$optionType = $row['OptionType'];
					$optionValue = $row['OptionValue'];	
					
					$affId = $affiliatemodel->getAffiliateIdByUsername($affiliateUserName);	
					if(empty($affId)){
						$controller->utils->debug_log("importer_lovebet processAffTerms affiliate does not exist" , $row);
						throw new Exception('Affiliate does not exist exists');
					}				

					$this->utils->debug_log('processAffTerms params',$affiliateUserName,$optionType, $optionValue);
					$affTermsId = $lovebet_model->importAffTerms($affId, $optionType, $optionValue);
					if ($lovebet_model->isErrorInTrans()) {
						$controller->utils->error_log("Import failed: processAffTerms error inserting data" , $affId, $optionType, $optionValue);
						throw new Exception('Trans_error');						
					}

					$totalCount++;
					$lovebet_model->endTrans();
				} catch (Exception $e) {
					$failCount++;
					$csv_row['reason'] = $e->getMessage();
					array_push($rltAffTerms['failed_list'], $csv_row);
					$lovebet_model->rollbackTrans();
				}
		});//

		$rltAffTerms['success_count'] = $totalCount - $failCount;
		$rltAffTerms['row_count'] = $totalCount;
		$rltAffTerms['failed_count'] = $failCount;
		$rltAffTerms['column_count'] = count(self::IMPORT_AFF_TERMS_CSV_HEADER);
		$rltAffTerms['success']=true;
		$this->CI->utils->debug_log('process tag file:'.$rltAffTerms['filename']);
		$this->CI->utils->debug_log('process tag file rltAffTerms:',$rltAffTerms);
	}

	private function processAffBank(&$rltAffBank, &$affMap){

		$this->CI->load->model(array('affiliatemodel','lovebet_model'));
		$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltAffBank['filename'];
		$ignore_first_row = true;		
		$controller = $this;
		$lovebet_model = $this->CI->lovebet_model;
		$affiliatemodel = $this->CI->affiliatemodel;
		$failCount = 0;
		$totalCount = 0;

		$this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
			use($controller, $lovebet_model, $affiliatemodel, &$totalCount, &$failCount, &$rltAffBank) {
				try {
					$lovebet_model->startTrans();
					$controller->utils->debug_log("compare column headings" , self::IMPORT_AFF_BANK_CSV_HEADER, $csv_row);
					$row = array_combine(self::IMPORT_AFF_BANK_CSV_HEADER, $csv_row);
					
					$affiliateUserName = $this->prefix.$row['AffiliateUserName'];
					//$affiliateUserName = $row['AffiliateUserName'];
					
					$affId = $affiliatemodel->getAffiliateIdByUsername($affiliateUserName);	
					if(empty($affId)){
						$controller->utils->debug_log("importer_lovebet processAffTerms affiliate does not exist" , $row);
						throw new Exception('Affiliate does not exist exists');
					}				

					$importData = [];
					$importData['affiliateId'] = $affId;
					$importData['paymentMethod'] = isset($row['PaymentMethod'])?$row['PaymentMethod']:null;
					$importData['accountName'] = isset($row['BankAccountName'])?$row['BankAccountName']:null;
					$importData['accountNumber'] = isset($row['BankAccountNo'])?$row['BankAccountNo']:null;
					$importData['bankName'] = isset($row['BankName'])?$row['BankName']:null;
					$importData['accountInfo'] = isset($row['AccountInfo'])?$row['AccountInfo']:null;
					$importData['createdOn'] = isset($row['CreatedOn'])?$row['CreatedOn']:$this->utils->formatDateTimeForMysql(new DateTime());
					$importData['updatedOn'] = isset($row['UpdatedOn'])?$row['UpdatedOn']:$this->utils->formatDateTimeForMysql(new DateTime());
					
					$affDataId = $lovebet_model->importAffBank($importData);
					if ($lovebet_model->isErrorInTrans()) {
						$controller->utils->error_log("Import failed: processAffBank error inserting data", 'affId', $affId, 'importData', $importData);
						throw new Exception('Trans_error');						
					}

					$totalCount++;
					$lovebet_model->endTrans();
				} catch (Exception $e) {
					$failCount++;
					$csv_row['reason'] = $e->getMessage();
					array_push($rltAffBank['failed_list'], $csv_row);
					$lovebet_model->rollbackTrans();
				}
		});//

		$rltAffBank['success_count'] = $totalCount - $failCount;
		$rltAffBank['row_count'] = $totalCount;
		$rltAffBank['failed_count'] = $failCount;
		$rltAffBank['column_count'] = count(self::IMPORT_AFF_TERMS_CSV_HEADER);
		$rltAffBank['success']=true;
		$this->CI->utils->debug_log('process tag file:'.$rltAffBank['filename']);
		$this->CI->utils->debug_log('process tag file rltAffBank:',$rltAffBank);
	}

	private function processAffLinks(&$rltAffLinks, &$affMap){

		$this->CI->load->model(array('affiliatemodel','lovebet_model'));
		$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltAffLinks['filename'];
		$ignore_first_row = true;		
		$controller = $this;
		$lovebet_model = $this->CI->lovebet_model;
		$affiliatemodel = $this->CI->affiliatemodel;
		$failCount = 0;
		$totalCount = 0;

		$this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
			use($controller, $lovebet_model, $affiliatemodel, &$totalCount, &$failCount, &$rltAffLinks) {
				try {
					$lovebet_model->startTrans();
					$controller->utils->debug_log("compare column headings" , self::IMPORT_AFF_LINKS_CSV_HEADER, $csv_row);
					$row = array_combine(self::IMPORT_AFF_LINKS_CSV_HEADER, $csv_row);
					
					$affiliateUserName = $this->prefix.$row['AffiliateUserName'];
					//$affiliateUserName = $row['AffiliateUserName'];
					
					$affId = $affiliatemodel->getAffiliateIdByUsername($affiliateUserName);	
					if(empty($affId)){
						$controller->utils->debug_log("importer_lovebet processAffLinks affiliate does not exist" , $row);
						throw new Exception('Affiliate does not exist exists');
					}				

					$importData = [];
					$importData['aff_id'] = $affId;
					$importData['tracking_domain'] = isset($row['TrackingDomain'])?$row['TrackingDomain']:null;
					$importData['tracking_type'] = isset($row['TrackingType'])?$row['TrackingType']:null;
					$importData['created_at'] = isset($row['CreatedAt'])?$row['CreatedAt']:$this->utils->getNowForMysql();
					$importData['updated_at'] = isset($row['UpdatedAt'])?$row['UpdatedAt']:$this->utils->getNowForMysql();					
					
					$affDataId = $lovebet_model->importAffLinks($importData);
					if ($lovebet_model->isErrorInTrans()) {
						$controller->utils->error_log("Import failed: processAffLinks error inserting data", 'affId', $affId, 'importData', $importData);
						throw new Exception('Trans_error');						
					}

					$totalCount++;
					$lovebet_model->endTrans();
				} catch (Exception $e) {
					$failCount++;
					$csv_row['reason'] = $e->getMessage();
					array_push($rltAffLinks['failed_list'], $csv_row);
					$lovebet_model->rollbackTrans();
				}
		});//

		$rltAffLinks['success_count'] = $totalCount - $failCount;
		$rltAffLinks['row_count'] = $totalCount;
		$rltAffLinks['failed_count'] = $failCount;
		$rltAffLinks['column_count'] = count(self::IMPORT_AFF_TERMS_CSV_HEADER);
		$rltAffLinks['success']=true;
		$this->CI->utils->debug_log('process tag file:'.$rltAffLinks['filename']);
		$this->CI->utils->debug_log('process tag file rltAffLinks:',$rltAffLinks);
	}

	private function processPlayer(&$rltAff, &$playerMap){				

		$this->CI->load->model(array('affiliatemodel', 'lovebet_model','player_model','vipsetting','game_provider_auth','group_level','wallet_model','http_request'));
		$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltAff['filename'];
		$ignore_first_row = true;
		$controller = $this;
		$lovebet_model = $this->CI->lovebet_model;
		$affiliatemodel = $this->CI->affiliatemodel;
		$player_model = $this->CI->player_model;
		$wallet_model = $this->CI->wallet_model;
		$group_level = $this->CI->group_level;
		$failCount = 0;
		$totalCount = 0;	
		$this->CI->load->library('salt');
		$salt = $this->CI->salt;
		
		$assigned_game_apis_map = $this->utils->getConfig('assigned_game_apis_map');
		$controller->utils->debug_log("assigned_game_apis_map" , $assigned_game_apis_map);

		$this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
			use($controller, $salt, $affiliatemodel, $lovebet_model, $player_model,$wallet_model,$group_level, $assigned_game_apis_map, &$totalCount, &$failCount, &$rltAff) {

				try {
					$lovebet_model->startTrans();

					$controller->utils->debug_log("compare column headings" , self::IMPORT_PLAYER_CSV_HEADER, $csv_row);
					$row = array_combine(self::IMPORT_PLAYER_CSV_HEADER, $csv_row);
					$externalId = $row['PlayerUsername'];

					if(empty($externalId)){						
						throw new Exception('No PlayerUsername Found on CSV');
					}	
					$externalId = $this->prefix.$externalId;			

					$importData = [];
					unset($importData['PlayerId']);
					$playerUserName = $this->prefix.$row['PlayerUsername'];
					$importData['username'] = $playerUserName;
					$playerId = $player_model->getPlayerIdByUsername($playerUserName);
					if(!empty($playerId)){
						$controller->utils->debug_log("importer_lovebet processPlayer data already exists" , $row);
						throw new Exception('Player already exists');
					}
					
					$password = $salt->encrypt($row['Password'], $controller->getDeskeyOG());

					$importData['password'] = $password;					
					$importData['active'] = $row['IsActive'];			
					$importData['email'] = $row['Email'];		
					$importData['lastLoginIp'] = $row['LastLoginIP'];
					$importData['lastLoginTime'] = $row['LastLoginTime'];
					$importData['lastLogoutTime'] = $row['LastLogoutTime'];
					$importData['lastActivityTime'] = $row['LastActivityTime'];
					$importData['createdOn'] = $row['CreatedOn'];
					$importData['updatedOn'] = $row['UpdatedOn'];
					$importData['invitationCode'] = $row['InvitationCode'];
					$importData['verify'] = $row['VerifyHash'];
					$importData['registered_by'] = $row['RegisteredBy'];
					$importData['enabled_withdrawal'] = $row['EnabledWithdrawal'];
					$importData['approved_deposit_count'] = $row['ApprovedDepositCount'];
					$importData['declined_deposit_count'] = $row['DeclinedDepositCount'];
					$importData['total_deposit_count'] = $row['TotalDepositCount'];
					$importData['totalBettingAmount'] = $row['TotalBettingAmount'];
					$importData['totalDepositAmount'] = $row['TotalDepositAmount'];
					$importData['verified_email'] = $row['IsVerifiedEmail'];
					$importData['approvedWithdrawCount'] = $row['ApprovedWithdrawCount'];
					$importData['approvedWithdrawAmount'] = $row['ApprovedWithdrawAmount'];

					//process affiliateId
					$importData['affiliateId'] = null;
					if(!empty($row['AffiliateUserName'])){
						$affiliateUserName = $this->prefix.$row['AffiliateUserName'];	
						$importData['affiliateId'] = $affiliatemodel->getAffiliateIdByUsername($affiliateUserName);
					}

					$importData['active_status'] = $row['ActiveStatus'];
					$importData['verified_phone'] = $row['IsVerifiedPhone'];
					$importData['secure_id'] = $row['SecureId'];					
					$importData['tracking_code'] = $row['TrackingCode'];
					$importData['disabled_cashback'] = $row['IsDisabledCashback'];
					$importData['disabled_promotion'] = $row['IsDisabledPromotion'];
					$importData['withdraw_password'] = $row['WithrawPassword'];
					$importData['is_phone_registered'] = $row['IsPhoneRegistered'];
					$importData['first_deposit'] = $row['FirstDesposit'];
					$importData['second_deposit'] = $row['SecondDesposit'];
					$importData['levelId'] = 0;
					
					//do the mapping
					list($levelId, $groupName, $levelName) = $this->mapLevelId($row['VIPLevel'],$row);
					$importData['levelId'] = $levelId;
					$importData['groupName'] = $groupName;
					$importData['levelName'] = $levelName;

					$balance = floatval($row['WalletBalance']);//TODO VALIDATE IF BALANCE WILL APPEAR

					//process parent referralId
					$importData['refereePlayerId'] = null;
					if(!empty($row['ReferredBy'])){
						$referrePlayerUserName = $this->prefix.$row['ReferredBy'];						
						$importData['refereePlayerId'] = $affiliatemodel->getAffiliateIdByUsername($referrePlayerUserName);
					}
					
					$playerDetails = [];
					$playerDetails['firstname'] = $row['FirstName'];
					$playerDetails['lastname'] = $row['LastName'];
					$playerDetails['gender'] = $row['Gender'];
					$playerDetails['language'] = $row['Language'];
					$playerDetails['birthdate'] = $row['Birthdate'];
					$playerDetails['contactnumber'] = $row['ContactNumber'];
					$playerDetails['registrationWebsite'] = $row['RegisteredWebsite'];
					$playerDetails['imAccount'] = $row['LineAccount'];
					$playerDetails['imAccount2'] = $row['WeChatAccount'];
					$playerDetails['registrationIP'] = $row['RegistrationIP'];
					$playerDetails['id_card_number'] = $row['IDCardNumber'];


					//process dispatch account
					$default_dispatch_account_group_id = $controller->utils->getConfig('default_dispatch_account_group_id');
					$default_dispatch_account_level_id = $controller->utils->getConfig('default_dispatch_account_level_id');
			
					$importData['dispatch_account_level_id'] = 1;

					//insert player					
					$playerId = $lovebet_model->importPlayer($externalId,$importData,$playerDetails, $player_model, $wallet_model, $balance, $assigned_game_apis_map);
					if(empty($playerId)){
						$controller->utils->debug_log("importer_lovebet processPlayer error inserting data", 'importData', $importData, 'playerDetails', $playerDetails);
						throw new Exception('Error inserting player');
					}

					//process player VIP level					
					$group_level->adjustPlayerLevel($playerId, $levelId);


					//process tags
					$controller->utils->debug_log("process player tags", $csv_row);
					
					$tags = explode(',',$row['PlayerTags']);
					$tags[] = 'Imported From Lovebet';
					if(!empty($tags)){
						foreach($tags as $tag){
							
							//switch tag
							$lovebet_migration_playertag_switch_map = $this->utils->getConfig('lovebet_migration_playertag_switch_map');
							if(!empty($lovebet_migration_playertag_switch_map) && is_array($lovebet_migration_playertag_switch_map)){
								foreach($lovebet_migration_playertag_switch_map as $tagKey => $tagSwitch){
									if($tagKey==$tag){
										$tag = $tagSwitch;
									}
								}
							}

							$this->CI->db->select('tagId')
							->from('tag')
							->where('tagName', $tag);
							$tagId = $affiliatemodel->runOneRowOneField('tagId');
							if(empty($tagId)){
								$controller->utils->error_log("process player tags missing", $tag, $tags,$row['PlayerTags']);
								continue;
							}
	
							//insert afftags
							$lovebet_model->tagPlayer($playerId,$tagId);
						}
					}else{
						$controller->utils->error_log("empty player tags csv_row", $csv_row);
					}

					if ($lovebet_model->isErrorInTrans()) {
						$controller->utils->error_log("Import failed: processPlayyer error inserting data", 'importData', $importData, 'playerDetails', $playerDetails, 'playertag', $tags);
						throw new Exception('Trans_error');
					}

					$totalCount++;
					$lovebet_model->endTrans();
				} catch (Exception $e) {
					$failCount++;
					$csv_row['reason'] = $e->getMessage();
					array_push($rltAff['failed_list'], $csv_row);
					$lovebet_model->rollbackTrans();
				}
		});//

		$rltAff['success_count'] = $totalCount - $failCount;
		$rltAff['row_count'] = $totalCount;
		$rltAff['failed_count'] = $failCount;
		$rltAff['column_count'] = count(self::IMPORT_AFF_CSV_HEADER);
		$rltAff['success']=true;
		$this->CI->utils->debug_log('process aff file:'.$rltAff['filename']);
	}

	private function processPlayerBankDetails(&$rltPlayerTrans, &$playerTransMap){
		$this->CI->load->model(array('player_model','lovebet_model'));
		$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltPlayerTrans['filename'];
		$ignore_first_row = true;
		$controller = $this;
		$lovebet_model = $this->CI->lovebet_model;
		$player_model = $this->CI->player_model;
		$failCount = 0;
		$totalCount = 0;

		$this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
			use($controller, $player_model, $lovebet_model, &$totalCount, &$failCount, &$rltPlayerTrans) {

				try {
					$lovebet_model->startTrans();

					$controller->utils->debug_log("compare column headings" , self::IMPORT_PLAYER_BANK_CSV_HEADER, $csv_row);
					$row = array_combine(self::IMPORT_PLAYER_BANK_CSV_HEADER, $csv_row);
					$playerUsername = $row['PlayerUsername'];

					if(empty($playerUsername)){						
						throw new Exception('No playerUsername Found on CSV');
					}	
					$playerUsername = $this->prefix.$playerUsername;
					$playerId = $player_model->getPlayerIdByUsername($playerUsername);
					if(empty($playerId)){
						$controller->utils->debug_log("importer_lovebet processPlayerBankDetails player doed not exist" , $row, 'playerUsername', $playerUsername);
						throw new Exception('Player does not exist : '. $playerUsername);
					}	

					$importData = [];
					$importData['playerId'] = $playerId;
					$importData['bankTypeId'] = null;
					$lovebet_migration_banktype_map = $this->utils->getConfig('lovebet_migration_banktype_map');
					if(!empty($lovebet_migration_banktype_map) && is_array($lovebet_migration_banktype_map)){
						foreach($lovebet_migration_banktype_map as $key => $val){
							if($key==$row['BankTypeID']){
								$importData['bankTypeId'] = $val;
							}
						}
					}
					if(empty($importData['bankTypeId'])){
						$controller->utils->debug_log("importer_lovebet processPlayerBankDetails bankType is not defined" , $row);
						throw new Exception('Bank type is not defined');
					}

					$importData['bankAccountFullName'] = $row['UserBankAccountName'];
					$importData['bankAccountNumber'] = $row['BankAccountNo'];
					$importData['branch'] = $row['BranchBankName'];
					$importData['isDefault'] = $row['IsDefault'];
					$importData['dwBank'] = $row['DWBank'];
					$importData['status'] = (int)$row['Status'];
					$importData['verified'] = (int)$row['IsVerified'];
					$importData['phone'] = $row['Phone'];
					$importData['customBankName'] = $row['CustomBankName'];
					$importData['external_id'] = $row['ExternalId'];
					$importData['createdOn'] = $row['CreatedOn'];
					$importData['updatedOn'] = $row['UpdatedOn'];
					$importData['province'] = $row['Province'];
					$importData['city'] = $row['City'];
					$importData['bankAddress'] = $row['BankAddress'];

					//check if already exist
					$this->CI->db->from('playerbankdetails')
					->where('playerId', $playerId)
					->where('dwBank', $row['DWBank'])
					->where('bankTypeId', $row['BankTypeID'])
					->where('bankAccountNumber', $row['BankAccountNo']);
					$bankDetailsId = $player_model->runOneRowOneField('playerBankDetailsId');
					if(!empty($bankDetailsId)){
						$this->utils->debug_log("importer_lovebet processPlayerBankDetails data already exists" , $row);
						throw new Exception('PlayerBankDetails already exists');
					}					

					//insert player transactions
					$transId = $lovebet_model->importPlayerBank($playerId, $importData);
					if(!$transId){
						$controller->utils->debug_log("importer_lovebet processPlayerBankDetails error inserting data" , $importData);
						throw new Exception('Error inserting bankdetails');
					}

					if ($lovebet_model->isErrorInTrans()) {
						$controller->utils->error_log("Import failed: processPlayerBankDetails error inserting data" , $importData);
						throw new Exception('Trans_error');
					}

					$totalCount++;
					$lovebet_model->endTrans();
				} catch (Exception $e) {
					$failCount++;
					$csv_row['reason'] = $e->getMessage();
					array_push($rltPlayerTrans['failed_list'], $csv_row);
					$lovebet_model->rollbackTrans();
				}
		});//

		$rltPlayerTrans['success_count'] = $totalCount - $failCount;
		$rltPlayerTrans['row_count'] = $totalCount;
		$rltPlayerTrans['failed_count'] = $failCount;
		$rltPlayerTrans['column_count'] = count(self::IMPORT_AFF_CSV_HEADER);
		$rltPlayerTrans['success']=true;
		$this->CI->utils->debug_log('process player transactions file:'.$rltPlayerTrans['filename']);
	}

	private function processPlayerTransactions(&$rltPlayerTrans, &$playerTransMap){
		$this->CI->load->model(array('player_model','lovebet_model'));
		$uploadCsvFilepath=$this->utils->getSharingUploadPath('/upload_temp_csv');
		$csv_file = rtrim($uploadCsvFilepath, '/').'/'.$rltPlayerTrans['filename'];
		$ignore_first_row = true;
		$controller = $this;
		$lovebet_model = $this->CI->lovebet_model;
		$player_model = $this->CI->player_model;
		$failCount = 0;
		$totalCount = 0;

		$this->loopCSV($csv_file, $ignore_first_row, $cnt, $message, function($cnt, $csv_row, $stop_flag)
			use($controller, $player_model, $lovebet_model, &$totalCount, &$failCount, &$rltPlayerTrans) {

				try {
					$lovebet_model->startTrans();

					$controller->utils->debug_log("compare column headings" , self::IMPORT_PLAYER_TRANSACTIONS_TOTAL_CSV_HEADER, $csv_row);
					$row = array_combine(self::IMPORT_PLAYER_TRANSACTIONS_TOTAL_CSV_HEADER, $csv_row);
					$playerUsername = $row['ToUsername'];

					if(empty($playerUsername)){						
						throw new Exception('No playerUsername Found on CSV');
					}	
					$playerUsername = $this->prefix.$playerUsername;
					$playerId = $player_model->getPlayerIdByUsername($playerUsername);
					if(empty($playerId)){
						$controller->utils->debug_log("importer_lovebet processPlayerTransactions player doed not exist" , $row);
						throw new Exception('Player does not exist');
					}	

					if(!isset($row['TransactionType']) || empty($row['TransactionType'])){
						throw new Exception('No TransactionType Found on CSV');
					}

					$importData = [];
					
					$importData['amount'] = $row['Amount'];
					$importData['transaction_type'] = $row['TransactionType'];
					$importData['from_type'] = 0;
					$importData['to_id'] = $playerId;
					$importData['to_type'] = $row['ToType'];
					$importData['external_transaction_id'] = $row['TransactionType'].'-'.$playerId.'-'.$importData['to_type'].'-'.$row['SubWalletId'];
					$importData['note'] = 'Consolidated transactions for player migrated from Lovebet';
					$importData['status'] = 1;
					$importData['before_balance'] = $row['Amount'];
					$importData['after_balance'] = $row['Amount'];
					$importData['sub_wallet_id'] = $row['SubWalletId'];
					$importData['to_username'] = $row['ToUsername'];					
					
					$transactionDate = $row['TransactionDate'];
					$transactionDate = $this->utils->getNowForMysql();
					$importData['created_at'] = date("Y-m-d H:i:s", strtotime($transactionDate));
					$importData['trans_date'] = date("Y-m-d", strtotime($transactionDate));
					$importData['trans_year_month'] = date("Ym", strtotime($transactionDate));
					$importData['trans_year'] = date("Y", strtotime($transactionDate));


					//check if already exist
					$this->CI->db->from('transactions')
					->where('to_id', $playerId)
					->where('transaction_type', $row['TransactionType'])
					->where('sub_wallet_id', $row['SubWalletId']);
					$transId = $player_model->runOneRowOneField('id');
					if(!empty($transId)){
						$this->utils->debug_log("importer_lovebet processPlayerTransactions data already exists" , $row);
						throw new Exception('PlayerTransactions already exists');
					}					

					//insert player transactions
					$transId = $lovebet_model->importPlayerTransactions($playerId, $importData);
					if(!$transId){
						$controller->utils->debug_log("importer_lovebet processAff error inserting data" , $importData);
						throw new Exception('Error inserting affiliate');
					}

					if ($lovebet_model->isErrorInTrans()) {
						$controller->utils->error_log("Import failed: processAff error inserting data" , $importData);
						throw new Exception('Trans_error');
					}

					$totalCount++;
					$lovebet_model->endTrans();
				} catch (Exception $e) {
					$failCount++;
					$csv_row['reason'] = $e->getMessage();
					array_push($rltPlayerTrans['failed_list'], $csv_row);
					$lovebet_model->rollbackTrans();
				}
		});//

		$rltPlayerTrans['success_count'] = $totalCount - $failCount;
		$rltPlayerTrans['row_count'] = $totalCount;
		$rltPlayerTrans['failed_count'] = $failCount;
		$rltPlayerTrans['column_count'] = count(self::IMPORT_AFF_CSV_HEADER);
		$rltPlayerTrans['success']=true;
		$this->CI->utils->debug_log('process player transactions file:'.$rltPlayerTrans['filename']);
	}

	//COMMON FUNCTIONS
	public function importCSV(array $files, &$summary, &$message){
		$success=true;
		
		//IMPORT TAG
		$rltTag=[
			'filename'=>$files['import_tag_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
		];
		$tagMap=[];
		if(!empty($files['import_tag_csv_file'])){
			$this->processTag($rltTag, $tagMap);
		}
		
		//IMPORT AFF TAG
		$rltAffTag=[
			'filename'=>$files['import_aff_tag_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
		];
		$tagMap=[];
		if(!empty($files['import_aff_tag_csv_file'])){
			$this->processAffTag($rltAffTag, $tagMap);
		}

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

		$rltAffTerms=[
			'filename'=>$files['import_aff_terms_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
		];
		$affTermsMap=[];
		if(!empty($files['import_aff_terms_csv_file'])){
			$this->processAffTerms($rltAffTerms, $affTermsMap);
		    unset($affTermsMap);
		}

		$rltAffLinks=[
			'filename'=>$files['import_aff_links_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
		];
		$affMap=[];
		if(!empty($files['import_aff_links_csv_file'])){
			$this->processAffLinks($rltAffLinks, $affMap);
		    unset($affMap);
		}

		$rltAffBank=[
			'filename'=>$files['import_aff_bank_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
		];
		$affMap=[];
		if(!empty($files['import_aff_bank_csv_file'])){
			$this->processAffBank($rltAffBank, $affMap);
		    unset($affMap);
		}
		
		$rltPlayer=[ 
			'filename'=>$files['import_players_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
		];
		$playerMap=[];
		if(!empty($files['import_players_csv_file'])){
			$this->processPlayer($rltPlayer, $playerMap);
		    unset($playerMap);
		}
		
		$rltPlayerBank=[ 
			'filename'=>$files['import_players_banks_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
		];
		$playerTransMap=[];
		if(!empty($files['import_players_banks_csv_file'])){
			$this->processPlayerBankDetails($rltPlayerBank, $playerTransMap);
		    unset($playerTransMap);
		}
		
		$rltPlayerTrans=[ 
			'filename'=>$files['import_players_transactions_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
		];
		$playerTransMap=[];
		if(!empty($files['import_players_transactions_csv_file'])){
			$this->processPlayerTransactions($rltPlayerTrans, $playerTransMap);
		    unset($playerTransMap);
		}
		

		$summary=[
			'import_tag_csv_file'=>$rltTag,
			'import_aff_tag_csv_file'=>$rltAffTag,
			'import_aff_csv_file'=>$rltAff,
			'import_aff_terms_csv_file'=>$rltAffTerms,
			'import_aff_bank_csv_file'=>$rltAffBank,
			'import_player_csv_file'=>$rltPlayer,
			'import_players_banks_csv_file'=>$rltPlayerBank,
			'import_players_transactions_csv_file'=>$rltPlayerTrans,
		];
		$this->CI->utils->debug_log('Import Summary', $summary);
		$message=null;

		return $success;
	}

	public function exportCSV(array $files, &$summary, &$message){		
		$this->CI->utils->debug_log('process files',$files);
		$success=true;
		
		//EXPORT PLAYER TAG
		$rltTag=[
			'filename'=>$files['export_tag_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
			'file'=>0,
		];
		$tagMap=[];
		if(!empty($files['export_tag_csv_file'])){
			$this->processExportTag($rltTag, $tagMap);
		}
		
		//EXPORT AFF TAG
		$rltAffTag=[
			'filename'=>$files['export_aff_tag_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
			'file'=>0,
		];
		$affTagMap=[];
		if(!empty($files['export_aff_tag_csv_file'])){
			$this->processExportAffTag($rltAffTag, $affTagMap);
		}
		
		//EXPORT AFF
		$rltAff=[
			'filename'=>$files['export_aff_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
			'file'=>0,
		];
		$affMap=[];
		if(!empty($files['export_aff_csv_file'])){
			$this->processExportAff($rltAff, $affMap);
		}
		
		//EXPORT TERMS
		$rltAffTerms=[
			'filename'=>$files['export_aff_terms_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
			'file'=>0,
		];
		$affTermsMap=[];
		if(!empty($files['export_aff_terms_csv_file'])){
			$this->processExportAffTerms($rltAffTerms, $affTermsMap);
		}
		
		//EXPORT AFF BANK
		$rltAffBank=[
			'filename'=>$files['export_aff_bank_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
			'file'=>0,
		];
		$affBankMap=[];
		if(!empty($files['export_aff_bank_csv_file'])){
			$this->processExportAffBank($rltAffBank, $affBankMap);
		}
		
		//EXPORT AFF LINKS
		$rltAffLinks=[
			'filename'=>$files['export_aff_links_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
			'file'=>0,
		];
		$affLinkMap=[];
		if(!empty($files['export_aff_links_csv_file'])){
			$this->processExportAffLinks($rltAffLinks, $affBankMap);
		}
		
		//EXPORT PLAYER
		$rltPlayer=[
			'filename'=>$files['export_players_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
			'file'=>0,
		];
		$playerMap=[];
		if(!empty($files['export_players_csv_file'])){
			$this->processExportPlayer($rltPlayer, $playerMap);
		}
		
		//EXPORT PLAYER BANK
		$rltPlayerBanks=[
			'filename'=>$files['export_players_banks_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
			'file'=>0,
		];
		$playerTransMap=[];
		if(!empty($files['export_players_banks_csv_file'])){
			$this->processExportPlayerBankDetails($rltPlayerBanks, $playerTransMap);
		}
		
		//EXPORT PLAYER
		$rltPlayerTrans=[
			'filename'=>$files['export_players_transactions_csv_file'],
			'success'=>true,
			'failed_list'=>[],
			'failed_count'=>0,
			'success_count'=>0,
			'column_count'=>0,
			'row_count'=>0,
			'file'=>0,
		];
		$playerTransMap=[];
		if(!empty($files['export_players_transactions_csv_file'])){
			$this->processExportPlayersTransactions($rltPlayerTrans, $playerTransMap);
		}


		$summary=[
			'export_tag_csv_file'=>$rltTag,
			'export_aff_tag_csv_file'=>$rltAffTag,
			'export_aff_csv_file'=>$rltAff,
			'export_aff_terms_csv_file'=>$rltAffTerms,
			'export_aff_bank_csv_file'=>$rltAffBank,
			'export_aff_links_csv_file'=>$rltAffLinks,
			'export_player_csv_file'=>$rltPlayer,
			'export_player_transactions_csv_file'=>$rltPlayerTrans,
			'export_players_banks_csv_file'=>$rltPlayerBanks,
		];
		$this->CI->utils->debug_log('Export Summary', $summary);
		$message=null;

		return $success;
	}

	public function exportCSVFile($fileName, $header, $data, $directory = null, $quote='"'){
		$this->CI->load->model(array('affiliatemodel'));
    	
		if(empty($fileName) || empty($header) || empty($data)){
			return false;
		}
    	
    	$d = new DateTime();
		if(empty($directory)){
			$directory = '/home/vagrant/Code/';
		}
    	
    	$csv_filepath =  $directory.'/'.$fileName.'-'.$d->format('Ymd_His').'.csv' ;    	

		$fp = fopen($csv_filepath, 'w');
		if ($fp) {
			$BOM = "\xEF\xBB\xBF";
			fwrite($fp, $BOM); //
		} else {
			//create report failed
			$this->utils->error_log('create csv file failed', $csv_filepath);
			return false;
		}
		$this->utils->debug_log('csv_filepath', $csv_filepath);
		fputcsv($fp, $header, ',', $quote);
		foreach($data as $key => $row){
			fputcsv($fp, $row, ',', $quote);
		}
		fclose($fp);
		return $csv_filepath;
	}

	protected function getDeskeyOG() {
		return $this->utils->getConfig('DESKEY_OG');;
	}

	public function mapLevelId($origLeveId, $data){		
		$groupName = $levelName = $levelId= null;
		$lovebet_migration_viplevel_match = $this->utils->getConfig('lovebet_migration_viplevel_match');
		if(empty($lovebet_migration_viplevel_match) && !is_array($lovebet_migration_viplevel_match)){
			return [null, null, null];
		}

		//get matched levelId
		$levelId = $lovebet_migration_viplevel_match['default']['levelId'];
		$levelName = $lovebet_migration_viplevel_match['default']['levelName'];
		$groupName = $lovebet_migration_viplevel_match['default']['groupName'];
		foreach($lovebet_migration_viplevel_match as $key => $val){
			if($key==$origLeveId){
				$levelId = $lovebet_migration_viplevel_match[$key]['levelId'];
				$levelName = $lovebet_migration_viplevel_match[$key]['levelName'];
				$groupName = $lovebet_migration_viplevel_match[$key]['groupName'];
			}
		}

		return [$levelId, $groupName, $levelName];
	}

}//end of class

