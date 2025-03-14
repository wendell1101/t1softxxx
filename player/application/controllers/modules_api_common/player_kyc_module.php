<?php

/**
 * player kyc function
 *
 * @property string $player_id
 * @property array Playerapi::codes
 * @property playerapi_lib $playerapi_lib
 * @property Player_security_library $player_security_library
 * @property Playerapi_model $playerapi_model
 * @property Player_attached_proof_file_model $player_attached_proof_file_model
 */
trait player_kyc_module
{

	public function kyc($action)
	{
		$this->load->library(['playerapi_lib', 'player_security_library']);
		$this->load->model(['playerapi_model', 'player_attached_proof_file_model']);
		$request_method = strtoupper($this->input->server('REQUEST_METHOD'));

		switch ($action) {
			case 'settings':
				return $this->getKycSettings();
				break;
			case 'status':
				return $this->getKycStatus();
				break;
			case 'upload':
				if ($request_method === 'POST') {
					return $this->postKycUpload();
				}
				break;
			case 'update':
				if ($request_method === 'POST') {
					return $this->postKycUpdate();
				}
				break;
		}

		$this->returnErrorWithCode(Playerapi::CODE_GENERAL_CLIENT_ERROR);
	}

	protected function getKycSettings()
	{
		if (!$this->initApi()) {
			return;
		}

		$result = [
			'code' => Playerapi::CODE_OK,
			'data' => $this->player_security_library->getKycSettings(),
		];

		return $this->returnSuccessWithResult($result);
	}

	protected function getKycStatus()
	{
		if (!$this->initApi()) {
			return;
		}

		$kyc_status = $this->player_security_library->getPlayerKYCStatus($this->player_id);

		$result = [
			'code' => Playerapi::CODE_OK,
			'data' => $kyc_status,
		];

		return $this->returnSuccessWithResult($result);
	}

	protected function validKYCRequest()
	{
		$validate_fields = [
			['name' => 'kycType', 'type' => 'string', 'required' => true, 'allowed_content' => Player_security_library::$supportType],
		];

		$request_body = $this->playerapi_lib->getRequestPramas();
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

		if (!$is_validate_basic_passed['validate_flag']) {
			throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
		}

        $tag = null;
		$kycType = !empty($request_body['kycType']) ? $request_body['kycType'] : null;
        $options = $this->player_security_library->getDocumentOptions($kycType);
        $types = array_column($options, 'type');
        $validate_fields = [
            ['name' => 'documentType', 'type' => 'string', 'required' => true, 'allowed_content' => $types],
        ];

		switch ($kycType) {
			case Player_security_library::KYC_TYPE_IDENTIFY:
				$tag = BaseModel::Verification_Photo_ID;				
				break;
			case Player_security_library::KYC_TYPE_ADDRESS:
				$tag = BaseModel::Verification_Adress;
				break;
			case Player_security_library::KYC_TYPE_INCOME:
				$tag = BaseModel::Verification_Income;
				break;
			case Player_security_library::KYC_TYPE_TRANSACTION:
				$tag = BaseModel::Verification_Deposit_Withrawal;
				break;
			default:
				throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
				break;
		}

		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

		if (!$is_validate_basic_passed['validate_flag']) {
			throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
		}

		$documentType = !empty($request_body['documentType']) ? $request_body['documentType'] : null;

		$setting = $this->player_security_library->getKycSettings($kycType);
		if (empty($setting)) {
			throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
		}

		if (false === $setting['enable']) {
			$is_validate_basic_passed['validate_msg'] = 'One of BO System features: show_player_upload_realname_verification, show_player_upload_proof_of_address, show_player_upload_proof_of_income, show_player_upload_proof_of_deposit_withdrawal is not enabled';
			throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
		}

		$documentTypeSetting = null;
		foreach ($setting['document'] as $documentSetting) {
			if ($documentSetting['type'] != $documentType) {
				continue;
			}

			$documentTypeSetting = $documentSetting;
		}

		return [
			$kycType,
			$tag,
			$documentType,
			$setting,
			$documentTypeSetting
		];
	}

	protected function validKYCContext($kycType, $settings)
	{
		$context = null;
		$requext_context = null;
		switch ($kycType) {
			case Player_security_library::KYC_TYPE_IDENTIFY:
			case Player_security_library::KYC_TYPE_ADDRESS:
				$validate_fields = [
					['name' => 'context', 'type' => 'array', 'required' => true],
				];
				$request_body = $this->playerapi_lib->getRequestPramas();
				$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

				if (!$is_validate_basic_passed['validate_flag']) {
					throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
				}
				$requext_context = !empty($request_body['context']) ? $request_body['context'] : null;
			break;
			default:
				return $context;
			break;
		}

		if(empty($requext_context)) {
			throw new \APIException(null, Playerapi::CODE_INVALID_PARAMETER);
		}

		$validate_fields = [];
		foreach ($settings['fields'] as $fieldName => $fieldType) {
			if($fieldType == 'required') {
				$validate_fields[] = ['name' => $fieldName, 'type' => 'string', 'required' => true];
			} elseif ($fieldType == 'optional') {
				$validate_fields[] = ['name' => $fieldName, 'type' => 'string', 'required' => false];
			}
		}

		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($requext_context, $validate_fields);

		if (!$is_validate_basic_passed['validate_flag']) {
			throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
		}

		foreach ($settings['fields'] as $fieldName => $fieldType) {
			$context[$fieldName] = (isset($requext_context[$fieldName])) ? $requext_context[$fieldName] : null;
		}

		return $context;
	}

	protected function postKycUpload()
	{
		if (!$this->initApi()) {
			return;
		}

		$validate_fields = [
			['name' => 'documents', 'type' => 'file[]', 'required' => true],
		];

		$result = ['code' => Playerapi::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);

		if (!$is_validate_basic_passed['validate_flag']) {
			throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
		}

		list($kycType, $tag, $documentType, $settings, $documentTypeSetting) = $this->validKYCRequest();
		$documents = !empty($_FILES['documents']) ? $_FILES['documents'] : null;

		$isVerified = $this->player_security_library->isVerified($this->player_id, $kycType);
		if (true === $isVerified) {
			throw new \APIException($this->codes[Playerapi::CODE_KYC_ALREADY_VERIFIED], Playerapi::CODE_KYC_ALREADY_VERIFIED);
		}

		$documentCount = (isset($documents['name']) && is_array($documents['name'])) ? count($documents['name']) : 0;
		if ($documentCount < $documentTypeSetting['fileMinLimit'] || $documentCount > $documentTypeSetting['fileMaxLimit']) {
			$is_validate_basic_passed['validate_msg'] = 'document count is '.$documentCount. ' which is not between fileMinLimit: '.$documentTypeSetting['fileMinLimit']. ' and fileMaxLimit: '.$documentTypeSetting['fileMaxLimit'];
			throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
		}

		$this->player_security_library->removeKycDocument($this->player_id, $kycType);

		switch ($kycType) {
			case Player_security_library::KYC_TYPE_IDENTIFY:
				$upload_result = $this->player_security_library->request_upload_realname_verification($this->player_id, $tag, $documents, BaseModel::Remark_Not_Verified);
				break;
			case Player_security_library::KYC_TYPE_ADDRESS:
				$upload_result = $this->player_security_library->request_upload_address($this->player_id, $tag,$documents, BaseModel::Remark_Not_Verified);
				break;
			case Player_security_library::KYC_TYPE_INCOME:
				$upload_result = $this->player_security_library->request_upload_income($this->player_id, $tag,$documents, BaseModel::Remark_Not_Verified);
				break;
			case Player_security_library::KYC_TYPE_TRANSACTION:
				$upload_result = $this->player_security_library->request_upload_deposit_withdrawal($this->player_id, $tag,$documents, BaseModel::Remark_Not_Verified);
				break;
		}

		if ($upload_result['status'] !== 'success') {
			return $this->returnErrorWithCode(Playerapi::CODE_KYC_UPLOAD_FAILED);
		}

		// -- update attached_file_status table for new attchment status history
		$this->player_attached_proof_file_model->saveAttachedFileStatusHistory($this->player_id);

		return $this->returnSuccessWithResult($result);
	}

	protected function postKycUpdate()
	{
		if (!$this->initApi()) {
			return;
		}

		$result = ['code' => Playerapi::CODE_OK];

		list($kycType, $tag, $documentType, $settings, $documentTypeSetting) = $this->validKYCRequest();

		$isVerified = $this->player_security_library->isVerified($this->player_id, $kycType);
		if (true === $isVerified) {
			throw new \APIException($this->codes[Playerapi::CODE_KYC_ALREADY_VERIFIED], Playerapi::CODE_KYC_ALREADY_VERIFIED);
		}

		$context = $this->validKYCContext($kycType, $settings);

		$update_result = $this->player_security_library->updateKycInfo($this->player_id, $tag, $context);

		if (false === $update_result) {
			return $this->returnErrorWithCode(Playerapi::CODE_KYC_UPLOAD_FAILED);
		}

		return $this->returnSuccessWithResult($result);
	}
}
