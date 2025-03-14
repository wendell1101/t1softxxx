<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

class Lovebet_model extends BaseModel {


	public function __construct() {
		parent::__construct();

	}

    const REGISTERED_BY_IMPORTER = 'importer';

    public function getTags(){
        $this->db->select('tagName, tagDescription, tagColor')->from('tag');
        return $this->runMultipleRowArray();
    }		

    public function getAffTags(){
        $this->db->select('tagName, tagDescription')->from('affiliatetaglist');
        return $this->runMultipleRowArray();
    }		

    public function getAffTerms(){
        $this->db->select('
			aff.username AffiliateUserName, 
			affterms.optionType OptionType, 
			affterms.optionValue OptionValue
			')
		->from('affiliate_terms affterms')
		->join('affiliates aff', 'aff.affiliateId=affterms.affiliateId', 'LEFT');
		$result = $this->runMultipleRowArray();
		foreach($result as &$row){
			$row['OptionValue'] = addslashes($row['OptionValue']);
		}

        return $result;
    }	

    public function getAffs(){
        $this->db->select('
		aff.affiliateId AffiliateId, 
		aff.username AffiliateUserName, 
		aff.trackingCode AffiliateTrackingCode, 
		aff.`password` Password, 
		aff.firstname FirstName, 
		aff.lastname LastName, 
		aff.email Email, 
		aff.mobile Mobile, 
		aff.currency Currency, 
		aff.country Country, 
		aff.location Location, 
		aff.ip_address IPAddress, 
		aff.createdOn CreatedOn, 
		aff.updatedOn UpdatedOn, 
		affp.username ParentAffiliateUsername, 
		aff.`status` Status, 
		aff.levelNumber LevelNumber, 
		aff.countSub CountSub, 
		aff.countPlayer CountPlayer, 
		aff.second_password SecondPassword, 
		aff.affdomain Domain, 
		aff.gender Gender, 
		
		aff.birthday Birthday, 
		aff.prefix_of_player PlayerPrefix, 
		aff.occupation Occupation, 
		aff.company Company, 
		aff.website Website, 
		aff.imType1 ImType1, 
		aff.im1 Im1, 
		aff.imType2 ImType2, 
		aff.im2 Im2, 
		aff.address Address, 
		aff.city City, 
		aff.state State, 
		aff.zip ZipCode, 
		aff.phone Phone, 

		aff.wallet_balance WalletBalance, 


		GROUP_CONCAT(DISTINCT afftl.tagName) AffiliateTag		
		')
		->from('affiliates as aff')
		->join('affiliates affp', 'affp.affiliateId=aff.affiliateId', 'LEFT')
		->join('affiliatetag afft', 'afft.affiliateId=aff.affiliateId', 'LEFT')
		->join('affiliatetaglist as afftl', 'afftl.tagId=afft.tagId', 'LEFT')
		->group_by('AffiliateId')
		->order_by('aff.createdOn', 'ASC');
        return $this->runMultipleRowArray();
    }			

    public function getAffBank(){
        $this->db->select('
			aff.username AffiliateUserName, 
			affpmnt.paymentMethod PaymentMethod, 
			affpmnt.accountName BankAccountName, 
			affpmnt.accountNumber BankAccountNo, 
			affpmnt.bankName BankName, 
			affpmnt.accountInfo AccountInfo, 
			affpmnt.createdOn CreatedOn, 
			affpmnt.updatedOn UpdatedOn
			')
		->from('affiliatepayment affpmnt')
		->join('affiliates aff', 'aff.affiliateId=affpmnt.affiliateId');
		return $this->runMultipleRowArray();
    }

    public function getAffLinks(){
        $this->db->select('
			aff.username AffiliateUserName, 
			afflink.tracking_domain TrackingDomain, 
			afflink.tracking_type TrackingType, 
			afflink.created_at CreatedAt, 
			afflink.updated_at UpdatedAt
			')
		->from('aff_tracking_link afflink')
		->join('affiliates aff', 'aff.affiliateId=afflink.aff_id');
		return $this->runMultipleRowArray();
    }				

    public function getPlayerBank(){
        $this->db->select('
			p.username PlayerUsername, 
			bt.bankName BankTypeName,
			bt.bankTypeId BankTypeID,
			pb.bankAccountFullName UserBankAccountName,
			pb.bankAccountNumber BankAccountNo,
			pb.branch BranchBankName,
			pb.isDefault IsDefault,
			pb.dwBank DWBank,
			pb.status Status,
			pb.verified IsVerified,
			pb.phone Phone,
			pb.customBankName CustomBankName,
			pb.playerBankDetailsId ExternalId,
			pb.createdOn CreatedOn,
			pb.updatedOn UpdatedOn,
			pb.province Province,
			pb.city City,
			pb.bankAddress BankAddress
			')
		->from('playerbankdetails pb')
		->join('player p', 'p.playerId=pb.playerId')
		->join('banktype bt', 'bt.bankTypeId=pb.bankTypeId');
		
		$result = $this->runMultipleRowArray();
		foreach($result as &$row){
			$row['BankTypeName'] = addslashes($row['BankTypeName']);
		}

		return $result;
    }		

    public function getPlayers(){
        $this->db->select('		
			p.playerId PlayerId, 
			p.username PlayerUsername, 
			p.password Password, 
			p.active IsActive, 
			p.email Email, 
			p.lastLoginIp LastLoginIP, 
			p.lastLoginTime LastLoginTime, 
			p.lastLogoutTime LastLogoutTime, 
			p.lastActivityTime LastActivityTime, 
			p.createdOn CreatedOn, 
			p.updatedOn UpdatedOn, 
			p.invitationCode InvitationCode, 			
			p.verify VerifyHash, 
			p.registered_by RegisteredBy, 
			p.enabled_withdrawal EnabledWithdrawal, 
			p.approved_deposit_count ApprovedDepositCount, 
			p.declined_deposit_count DeclinedDepositCount, 
			p.total_deposit_count TotalDepositCount, 
			p.totalBettingAmount TotalBettingAmount, 
			p.totalDepositAmount TotalDepositAmount, 
			p.levelId VIPLevel, 
			p.groupName VIPGroupName, 
			p.levelName VIPLevelName, 
			p.verified_email IsVerifiedEmail, 
			p.approvedWithdrawCount ApprovedWithdrawCount, 
			p.approvedWithdrawAmount ApprovedWithdrawAmount, 
			aff.username AffiliateUserName, 
			p.active_status ActiveStatus, 
			p.verified_phone IsVerifiedPhone, 
			p.secure_id SecureId, 
			pa.totalBalanceAmount WalletBalance, 
			p.tracking_code TrackingCode, 
			p.disabled_cashback IsDisabledCashback, 
			p.disabled_promotion IsDisabledPromotion, 
			p.withdraw_password WithrawPassword, 
			p.is_phone_registered IsPhoneRegistered, 
			
			p.first_deposit FirstDesposit, 
			p.second_deposit SecondDesposit, 
			ref.username ReferredBy,

			pd.firstName FirstName,
			pd.lastName LastName,
			pd.gender Gender,
			pd.language Language,
			pd.birthdate Birthdate,
			pd.contactnumber ContactNumber,
			pd.registrationWebsite RegisteredWebsite,
			pd.imAccount LineAccount,
			pd.imAccount2 WeChatAccount,
			pd.registrationIP RegistrationIP,
			pd.id_card_number IDCardNumber,
			

			GROUP_CONCAT(t.tagName) PlayerTags
			')
		->from('player p')
		->join('affiliates aff', 'aff.affiliateId=p.affiliateId', 'LEFT')
		->join('player ref', 'ref.playerId=p.refereePlayerId', 'LEFT')
		->join('playerdetails pd', 'pd.playerId=p.playerId', 'LEFT')
		->join('playertag pt', 'pt.playerId=p.playerId', 'LEFT')
		->join('playeraccount pa', 'pa.playerId=p.playerId AND pa.type="wallet"', 'LEFT')
		->join('tag t', 't.tagId=pt.tagId', 'LEFT')
		->group_by('PlayerId')
		->order_by('PlayerId', 'ASC');
		return $this->runMultipleRowArray();
    }				

    public function getPlayersTransactions(){
        $this->db->select("			
			p.username PlayerUsername,
			t.transaction_type TransactionType,			
			p.username ToUsername,
			t.to_type ToType,
			SUM(t.amount) Amount,
			COUNT(t.id) TransactionCount,
			p.createdOn TransactionDate,
			t.sub_wallet_id SubWalletId
			")
		->from('transactions t')
		->join('player p', 'p.playerId=t.to_id')		
		->group_by('t.transaction_type,t.to_id,t.to_type,t.sub_wallet_id')
		->order_by('t.transaction_type', 'ASC');
		return $this->runMultipleRowArray();
    }	
	
	/**
	 * Import Player Tag
	 */
	public function importTag($tagName, $tagDescription, $tagColor) {

		if (empty($tagName)) {
			$message = "Empty tagName: [$tagName]";
			return false;
		}

		
		$data = array(
			'tagName' => $tagName,
			'tagDescription' => $tagDescription,
			'tagColor' => $tagColor,
		);

		$this->db->from('tag')->where('tagName', $tagName);
		$tagId = $this->runOneRowOneField('tagId');

		if(!empty($tagId)){
			return $tagId;
		}

		return $this->insertData('tag', $data);
	}		

	/**
	 * Import Affiliate Tag
	 */
	public function importAffTag($tagName, $tagDescription) {

		//$this->load->model(array('pl'));

		if (empty($tagName)) {
			$message = "Empty tagName: [$tagName]";
			return false;
		}

		
		$data = array(
			'tagName' => $tagName,
			'tagDescription' => $tagDescription,			
		);

		$this->db->from('affiliatetaglist')->where('tagName', $tagName);
		$tagId = $this->runOneRowOneField('tagId');

		if(!empty($tagId)){
			return $tagId;
		}

		return $this->insertData('affiliatetaglist', $data);
	}		

	/**
	 * Import Affiliate Tag
	 */
	public function tagAffiliate($affId, $tagId) {

		//$this->load->model(array('pl'));

		if (empty($affId)) {
			$message = "Empty affId: [$affId]";
			return false;
		}

		if (empty($tagId)) {
			$message = "Empty tagId: [$tagId]";
			return false;
		}

		
		$data = array(
			'affiliateId' => $affId,
			'tagId' => $tagId,			
		);

		$this->db->from('affiliatetag')->where('affiliateId', $affId)->where('tagId', $tagId);
		$affiliateTagId = $this->runOneRowOneField('affiliateTagId');

		if(!empty($affiliateTagId)){
			return $affiliateTagId;
		}

		return $this->insertData('affiliatetag', $data);
	}	

	/**
	 * Import Affiliate Tag
	 */
	public function tagPlayer($playerId, $tagId) {

		//$this->load->model(array('pl'));

		if (empty($playerId)) {
			$message = "Empty playerId: [$playerId]";
			return false;
		}

		if (empty($tagId)) {
			$message = "Empty tagId: [$tagId]";
			return false;
		}

		
		$data = array(
			'playerId' => $playerId,
			'tagId' => $tagId,			
		);

		$this->db->from('playertag')->where('playerId', $playerId)->where('tagId', $tagId);
		$playerTagId = $this->runOneRowOneField('playerTagId');

		if(!empty($playerTagId)){
			return $playerTagId;
		}

		return $this->insertData('playertag', $data);
	}
	
	/**
	 * Import Aff Terms
	 */
	public function importAffTerms($affId, $optionType, $optionValue) {

		if (empty($affId)) {
			$message = "Empty affId: [$affId]";
			return false;
		}		
		
		if (empty($optionType)) {
			$message = "Empty optionType: [$optionType]";
			return false;
		}

		$optionValue = stripslashes($optionValue);
		$data = array(
			'affiliateId' => $affId,
			'optionType' => $optionType,
			'optionValue' => $optionValue,
		);

		$this->db->from('affiliate_terms')
		->where('affiliateId', $affId)
		->where('optionType', $optionType);
		$affTermsId = $this->runOneRowOneField('id');

		if(!empty($affTermsId)){
			return $affTermsId;
		}

		return $this->insertData('affiliate_terms', $data);
	}		
	
	/**
	 * Import Aff Bank
	 */
	public function importAffBank($importData) {

		if (empty($importData)) {			
			return false;
		}	

		if (!isset($importData['affiliateId'])||empty($importData['affiliateId'])) {			
			$this->utils->error_log("Import failed: processAffBank error inserting data empty affiliateId");
			return false;
		}		

		if (!isset($importData['paymentMethod'])||empty($importData['paymentMethod'])) {			
			$this->utils->error_log("Import failed: processAffBank error inserting data empty paymentMethod");
			return false;
		}		

		if (!isset($importData['accountName'])||empty($importData['accountName'])) {	
			//$this->utils->error_log("Import failed: processAffBank error inserting data empty accountName");		
			//return false;
		}		

		if (!isset($importData['accountNumber'])||empty($importData['accountNumber'])) {	
			$this->utils->error_log("Import failed: processAffBank error inserting data empty accountNumber");				
			return false;
		}		

		if (!isset($importData['bankName'])||empty($importData['bankName'])) {
			$this->utils->error_log("Import failed: processAffBank error inserting data empty bankName");							
			return false;
		}				

		if (!isset($importData['accountInfo'])||empty($importData['accountInfo'])) {			
			//$this->utils->error_log("Import failed: processAffBank error inserting data empty accountInfo");							
			//return false;
		}	

		$data = $importData;

		$this->db->from('affiliatepayment')
		->where('paymentMethod', $data['paymentMethod'])
		->where('accountName', $data['accountName'])
		->where('accountNumber', $data['accountNumber'])
		->where('affiliateId', $data['affiliateId']);
		//->where('accountInfo', $data['accountInfo']);
		$affDataId = $this->runOneRowOneField('affiliatePaymentId');

		if(!empty($affDataId)){
			return $affDataId;
		}

		return $this->insertData('affiliatepayment', $data);
	}	
	
	/**
	 * Import Aff Links
	 */
	public function importAffLinks($importData) {

		if (empty($importData)) {			
			return false;
		}	

		if (!isset($importData['aff_id'])||empty($importData['aff_id'])) {			
			$this->utils->error_log("Import failed: importAffLinks error inserting data empty affiliateId");
			return false;
		}		

		if (!isset($importData['tracking_domain'])||empty($importData['tracking_domain'])) {			
			$this->utils->error_log("Import failed: importAffLinks error inserting data empty tracking_domain");
			return false;
		}		

		$data = $importData;

		$this->db->from('aff_tracking_link')
		->where('aff_id', $data['aff_id'])
		->where('tracking_domain', $data['tracking_domain']);
		$affDataId = $this->runOneRowOneField('id');

		if(!empty($affDataId)){
			return $affDataId;
		}

		return $this->insertData('aff_tracking_link', $data);
	}

	public function importPlayer($username, $playerData, $playerDetailsData, $player_model,$wallet_model, $balance, $assigned_game_apis_map) {

		$this->load->library(array('salt'));

		if (empty($username)) {
			return false;
		}
		if(empty($playerData)){
			return false;
		}
		if(!isset($data['password']) || empty($data['password'])){
			$data['password'] = '';
		}

		//prepare player fields
		$data = $playerData;
		$data['registered_by'] = Player_model::REGISTERED_BY_IMPORTER;

		$this->db->select('playerId')->from('player')->where('username', $username);
		$playerId = $this->runOneRowOneField('playerId');

		# Create / Update the player record
		$exists=false;
		if(!empty($playerId)){
			$exists=true;
			$this->db->set($data)->where('playerId', $playerId)->update('player');
		}else{
			$exists=false;
			$this->db->set($data)->insert('player');
			$playerId = $this->db->insert_id();
		}

		# playerdetails fields
		
		$this->db->select('playerDetailsId')->from('playerdetails')->where('playerId', $playerId);
		$playerDetailsId = $this->runOneRowOneField('playerDetailsId');
		if (!empty($playerDetailsId)) {
			$this->db->set($playerDetailsData)->where('playerDetailsId', $playerDetailsId)->update('playerdetails');
		} else {
			$playerDetailsData['playerId'] = $playerId;
			$this->db->set($playerDetailsData)->insert('playerdetails');
		}

		$lastActivityTime = (!empty($playerData['lastLogoutTime'])) ? $playerData['lastLogoutTime'] : '';
		$lastLogoutTime = (!empty($playerData['lastLogoutTime'])) ? $playerData['lastLogoutTime'] : '';
		$lastLoginTime = (!empty($playerData['lastLoginTime'])) ? $playerData['lastLoginTime'] : '';
		$lastLoginIp =  (!empty($playerData['lastLoginIp'])) ? $playerData['lastLoginIp'] : '';
		$lastLogoutIp = (!empty($playerData['lastLogoutIp'])) ? $playerData['lastLogoutIp'] : '';

		$this->utils->debug_log('player_runtime_data'.$playerId, $lastActivityTime, $lastLogoutTime, $lastLoginTime, $lastLoginIp, $lastLogoutIp);
		$player_model->updateLastActivity($playerId, $lastActivityTime, $lastLoginTime, $lastLogoutTime, $lastLoginIp, $lastLogoutIp);

		if(!$exists){
			//admin is 1
			$notes='import player '.$username.' at '.$this->utils->getNowForMysql();
			$player_model->addPlayerNote($playerId, 1, $notes);
		}		

		$ole777_model = $this;

		$currency = $player_model->getActiveCurrencyCode();
		$success = $this->lockAndTransForPlayerBalance($playerId, function() use($wallet_model,$playerId, $balance, $currency,$username,$assigned_game_apis_map) {

			$playeraccount = array(
            	'playerId' => $playerId,
            	'currency' =>  $this->player_model->getActiveCurrencyCode(),
            	'type' => Wallet_model::TYPE_MAINWALLET,
            	'typeOfPlayer' => Wallet_model::TYPE_OF_PLAYER_REAL,
            	'totalBalanceAmount' => $balance,
            	'typeId' => Wallet_model::MAIN_WALLET_ID ,
            	'status' => Player_model::DEFAULT_PLAYERACCOUNT_STATUS
            );
            $this->player_model->insertData('playeraccount',  $playeraccount);

			$wallet_model->syncAllWallet($playerId, $balance, $currency);
			$wallet_model->moveAllToRealOnMainWallet($playerId);
			$this->createPlayerInDB($playerId,$username,'',null,null,$assigned_game_apis_map);
			return true;

		});
		
		return $playerId;
	}


	public function importPlayerBank($playerId, $importData){

		if (empty($playerId)) {
			$message = "Empty playerId: [$playerId]";
			return false;
		}

		if (empty($importData['bankTypeId'])) {
			$message = "Empty bankTypeId: [".$importData['bankTypeId']."]";
			return false;
		}
		$this->db->select('playerbankdetailsId')->from('playerbankdetails')
		->where('playerId', $playerId)
		->where('dwBank', $importData['dwBank'])
		->where('bankAccountNumber', $importData['bankAccountNumber']);

		if(!empty($importData['external_id'])){
			$this->db->where('external_id', $importData['external_id']);
		}else{
			$this->db->where('bankTypeId', $importData['bankTypeId']);
		}

		$playerBankDetailsId= $this->runOneRowOneField('playerbankdetailsId');
		 
		if (!empty($playerBankDetailsId)) {
			$exists=true;
			$this->db->set($importData)
			->where('bankTypeId', $importData['bankTypeId'])
			->where('playerId', $playerId)
			->where('dwBank', $importData['dwBank'])
			->where('bankAccountNumber', $importData['bankAccountNumber'])
			->update('playerbankdetails');
		} else {
			$exists=false;
			$this->db->set($importData)->insert('playerbankdetails');
			$playerBankDetailsId = $this->db->insert_id();
		}

		return $playerBankDetailsId;
	}


	public function importPlayerTransactions($playerId, $data){

		if (empty($playerId)) {
			$message = "Empty playerId: [$playerId]";
			return false;
		}

		if (empty($data)) {
			$message = "Empty data";
			return false;
		}
		$data['from_username'] = self::REGISTERED_BY_IMPORTER;
		
		$this->db->set($data)->insert('transactions');
		$transId = $this->db->insert_id();

		return $transId;
	}


		/**
	 * overview : import ole777 affiliate
	 *
	 * @param int		$externalId
	 * @param string	$username
	 * @param string	$password
	 * @param string	$trackingCode
	 * @param date		$createdOn
	 * @param string	$firstname
	 * @param string	$lastname
	 * @param int		$status
	 * @param null $extra
	 * @return null
	 */
		public function importAffiliate($username, $params) {

			$this->load->model(array('affiliatemodel'));

			if (empty($username)) {
				$message = "Empty username: [$username]";
				return false;
			}

			if (empty($params)) {
				$message = "Empty params: [$params]";
				return false;
			}

			$this->load->library(array('salt'));
			$data = $params;
			$data['registered_by'] = self::REGISTERED_BY_IMPORTER;
			
			if (!empty($extra)) {
				$data = array_merge($data, $extra);
			}

			$this->db->from('affiliates')->where('username', $username);
			$affId = $this->runOneRowOneField('affiliateId');

			if(!empty($affId)){
				return $affId;
			}

			return $this->insertData('affiliates', $data);
		}

		public function importAgent($externalId,$agent_name, $password,
			$tracking_code,$createdOn, $firstName, $lastName, $extra){

			$this->load->model(array('agency_model'));
			$this->load->library(array('salt'));

			$agent_id = $externalId;
			$data = array(
				    'agent_id' => $agent_id,
				    'agent_name' => $agent_name,
			     	'tracking_code' => $tracking_code,
			     	'password' => empty($password) ? '' : $this->salt->encrypt($password, $this->getDeskeyOG()),
					'can_have_sub_agent' => 1,
					'can_have_players' => 1,
					'can_do_settlement' => 1,
					'can_view_agents_list_and_players_list' => 1,
					'show_bet_limit_template' => 1,
					'show_rolling_commission' => 1,
				);


		 $data = array_merge($data, $extra);
			$this->utils->info_log('agent rowdata', $data);
			$this->updateAgentInfoByAgentId($data,$agent_id);
			return $agent_id;
		}

		public function updateAgentInfoByAgentId($data,$agent_id){

			$agency_agents_tbl = 'agency_agents';
		    $this->db->from($agency_agents_tbl)->where('agent_id', $agent_id);

			if ($this->runExistsResult()) {
				$this->db->where('agent_id', $agent_id);
			    $this->db->update($agency_agents_tbl,$data);
			} else {
				$this->db->insert($agency_agents_tbl,$data);
			}

		}

		public function addUpdateAgentInfoByAgentName($data,$agent_name){
			$table = 'agency_agents';
			$agent_id =null;
			$qry = $this->db->from($table)->where('agent_name', $agent_name);
			$agent_id = $this->runOneRowOneField('agent_id');
			if (!empty($agent_id)) {
				 $this->db->where('agent_id', $agent_id);
				 $this->db->update($table,$data);
			} else {
				$agent_id =$this->insertData($table, $data);
			}
			return $agent_id;
		}

		public function updateAllLevelOneParentId($root_agent_id){
			$table = 'agency_agents';
			$data = array('parent_id'=> $root_agent_id);
			$this->db->set($data)->where('agent_level', 1)->update($table);
		}

		public function addUpdateAgencyAgentGamePlatforms($data,$game_platform_id,$agent_id) {

			$table = 'agency_agent_game_platforms';
		    $this->db->from($table)->where('game_platform_id', $game_platform_id)->where('agent_id', $agent_id);

			if ($this->runExistsResult()) {
				$this->db->where('game_platform_id', $game_platform_id)->where('agent_id', $agent_id);
			    $this->db->update($table,$data);
			} else {
				$this->db->insert($table,$data);
			}

		}

		public function addUpdateAgencyAgentGameType($data,$game_platform_id,$game_type_id,$agent_id) {

			$table = 'agency_agent_game_types';
		    $this->db->from($table)
		    ->where('game_platform_id', $game_platform_id)
		    ->where('game_type_id', $game_type_id)
		    ->where('agent_id', $agent_id);

			if ($this->runExistsResult()) {
				$this->db->where('game_platform_id', $game_platform_id)
		                 ->where('game_type_id', $game_type_id)
		                 ->where('agent_id', $agent_id);
			    $this->db->update($table,$data);
			} else {
				$this->db->insert($table,$data);
			}

		}

		public function syncUser($rolesModel,$roleId, $userInfo){

			$userId = $this->syncAdminUser($rolesModel,$roleId, $userInfo);

			if(!empty($userId)){
				$genealogyTbl = 'genealogy';
				$this->db->select('genealogyId')->from($genealogyTbl)->where('roleId', $roleId);
				$genealogyId = $this->runOneRowOneField('genealogyId');

				$data=[
					'generation' => 1,
					'gene'=> $roleId,
					'roleId'=> $roleId
				];
				if(empty($genealogyId)){
					$this->insertData($genealogyTbl, $data);
				}else{
					$this->updateData('genealogyId', $genealogyId, $genealogyTbl, $data);
				}

				$userRolesTbl = 'userroles';
				$this->db->select('id')->from($userRolesTbl)->where('roleId', $roleId)->where('userId', $userId);
				$userRoleId=$this->runOneRowOneField('id');
				$data=[
					'userId'=> $userId,
					'roleId'=> $roleId
				];
				if(empty($userRoleId)){
					$this->insertData($userRolesTbl, $data);
				}else{
					$this->updateData('id', $userRoleId, $userRolesTbl, $data);
				}
			}
		}

		public function syncAdminUser($rolesModel,$roleId, $userInfo){

			if(!empty($userInfo)){
				$username=$userInfo['username'];
				$this->db->select('userId')->from('adminusers')->where('username', $username);
				$userId=$this->runOneRowOneField('userId');
				$adminusersTbl = 'adminusers';
				$password='';
				$password=$rolesModel->hashPassword($userInfo['password']);

				$data=[
					'username'=>$username,
					'password'=> $rolesModel->hashPassword($userInfo['password']),
					'realname'=> $userInfo['realname'],
					'department'=> $userInfo['department'],
					'position'=> $userInfo['position'],
					'email'=> $userInfo['email'],
					'position'=> $userInfo['position'],
					'createTime'=> $rolesModel->utils->getNowForMysql(),
					'createPerson'=>1,
					'status'=>self::DB_TRUE,
					'maxWidAmt'=> isset($userInfo['maxWidAmt']) ? $this->utils->extractFloatNumber($userInfo['maxWidAmt']): 0,
					'singleWidAmt' => isset($userInfo['singleWidAmt']) ? $this->utils->extractFloatNumber($userInfo['singleWidAmt']): 0,
					'tele_id' => isset($userInfo['tele_id']) ? $userInfo['tele_id'] : '',
					'note' => isset($userInfo['note']) ? $userInfo['note'] : '',
					'deleted'=>0,
				];

				if(empty($userId)){
					return $this->insertData('adminusers', $data);
				}else{
					$this->updateData('userId', $userId, $adminusersTbl, $data);
					return $userId;
				}
			}
		}



		//COMMON FUNCTIONS
		public function createPlayerInDB($playerId,$playerName,$password='',$agent_id=null,$sma_id=null,$assigned_game_apis_map) {

			$source = Game_provider_auth::SOURCE_REGISTER;
			$is_demo_flag = false;
			$game_provider_auth_tbl = 'game_provider_auth';
	
			if(empty($assigned_game_apis_map)){
				$this->utils->debug_log('assigned_game_apis_map is empty ',$assigned_game_apis_map);
				return;
			}
	
			foreach ($assigned_game_apis_map as $apiId => $value) {
	
				$gameAccount=$playerName;
				if(!empty($value['prefix'])){
					$gameAccount = $value['prefix'].$playerName;
				}
				$player_game_acct = array(
					'login_name' => $gameAccount,
					"player_id" => $playerId,
					"password" => $password,
					"source" => $source,
					"is_demo_flag" => $is_demo_flag,
					"agent_id" => $agent_id,
					"sma_id" => $sma_id,
					'game_provider_id' => $apiId,
					'status' => self::STATUS_NORMAL,
				);
	
				$this->db->select('id')->from($game_provider_auth_tbl)->where('player_id', $playerId)
					 ->where('game_provider_id', $apiId);
				$id=$this->runOneRowOneField('id');
	
				if(empty($id)){
					//double check login_name
					$this->db->select('id')->from($game_provider_auth_tbl)->where('login_name', $gameAccount)
						->where('game_provider_id', $apiId);
					$id=$this->runOneRowOneField('id');
				}
	
				if (!empty($id)) {
					$this->db->where('id', $id);
					$this->db->update($game_provider_auth_tbl,$player_game_acct);
				} else {
					$this->db->insert($game_provider_auth_tbl,$player_game_acct);
				}
				$this->utils->info_log('Create update player game account  ','game_name', $playerName,'api', $apiId);

				$playeraccount = array(
					'playerId' => $playerId,
					'currency' =>  $this->player_model->getActiveCurrencyCode(),
					'type' => Wallet_model::TYPE_SUBWALLET,
					'typeOfPlayer' => Wallet_model::TYPE_OF_PLAYER_REAL,
					'totalBalanceAmount' => 0,
					'typeId' => $apiId,
					'status' => Player_model::DEFAULT_PLAYERACCOUNT_STATUS
				);
	
				$this->db->from('playeraccount')
						->where('playerId', $playerId)				
						->where('typeId', $apiId);
				$playerAccountId=$this->runOneRowOneField('playerAccountId');
	
				if(empty($playerAccountId)){
					$this->db->insert('playeraccount',$playeraccount);
				}
			}
		}

}


