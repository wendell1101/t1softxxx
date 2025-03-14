<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Player
 *
 * This model represents ip data. It operates the following tables:
 * - player
 * - playerdetails
 *
 * @author	Johann Merle
 */

class Registration_setting extends BaseModel {

	const CACHE_TTL = 3600; # 1 hour

	const TABLE = 'registration_fields';

	const PLAYER = 1;
	const AFFILIATE = 2;

	const VISIBLE = 0;
	const HIDDEN = 1;
	const REQUIRED = 0;
	const OPTIONAL = 1;
	const CAN_BE_REQUIRED = 0;

	const EDIT_ENABLED = 0;
	const EDIT_DISABLED = 1;

	// OGP-16730: Moved from Marketing_management
	const PASSWORD_MINIMUM_LENGTH = 6;
	const PASSWORD_MAXIMUM_LENGTH = 20;

	const FIELD_TYPE_FREE_INPUT = 1;
	const FIELD_TYPE_SINGLE = 2;
	const FIELD_TYPE_MULTIPLE = 3;

	private $registration_fields = array();
	private $registration_fields_by_alias = array();
	private $registration_fields_bare = [];
	
	private function getCacheKey($key) {
		return PRODUCTION_VERSION.'|'.self::TABLE.'|'.$key;
	}

	public function getRegistrationFields($type = self::PLAYER) {
		if (!isset($this->registration_fields[$type])) {
			$this->db->order_by('field_order', 'ASC');
			$query = $this->db->get_where(self::TABLE, array('type' => $type));
			$result = $query->result_array();
			$result = array_combine(array_column($result, 'field_name'), $result);
			$this->registration_fields[$type] = $result;
		}
		$regFields = $this->registration_fields[$type];

		return $regFields;
	}

    public function getRegistrationFieldsByAlias($type = self::PLAYER) {

        if (!isset($this->registration_fields_by_alias[$type])) {
			$this->db->order_by('field_order', 'ASC');
            $query = $this->db->get_where(self::TABLE, array('type' => $type));
            $result = $query->result_array();
            $result = array_combine(array_column($result, 'alias'), $result);
            $this->registration_fields_by_alias[$type] = $result;
        }

        return $this->registration_fields_by_alias[$type];
    }

    /**
     * Reads bare rows from registration_fields
     * @param	int		$type	self::PLAYER or self::AFFILIATE
     * @return	array of rows
     */
    public function getRegistrationFieldsBare($type = self::PLAYER) {
		if (!isset($this->registration_fields_bare[$type])) {
			$this->db->order_by('field_order', 'ASC');
            $query = $this->db->get_where(self::TABLE, array('type' => $type));
            $result = $query->result_array();
            $this->registration_fields_bare[$type] = $result;
        }

        return $this->registration_fields_bare[$type];
    }

    public function checkAccountInfoFieldAllowEdit($player, $fieldName, $disable_edit = false, &$errorMsg = null) {
        $errors = [];
	    $registrationFields = $this->getRegistrationFieldsByAlias();        
	    # empty > disable_edit >= limited counts > account_info setting
	    if (isset($player) && empty($player[$fieldName])) {
            return true;
	    }
        
        $allowed = !$disable_edit;
        
        if($allowed){
            if(!$this->validateLimitedUpdateTimes($player['playerId'], $fieldName)){
                $errorMsg = 'reach_limit';
                return false;
            }

            if (!isset($registrationFields[$fieldName]) || $registrationFields[$fieldName]['account_edit'] != 0) { # 0 is can edit ( Registration Settings )
                $errorMsg = 'disabled_by_reg_settings';
                return false;
            }
        }

        return $allowed;
    }

    public function checkAccountInfoFieldAllowVisible($fieldName, $disable_visible = false) {
	    $registrationFields = $this->getRegistrationFieldsByAlias();
	    # empty > disable_visible > account_info setting

	    $allowed = false;

     	if (isset($registrationFields[$fieldName]) && $registrationFields[$fieldName]['account_visible'] == 0) { # 0 is can show ( Registration Settings )
	        $allowed = !$disable_visible;
	    } else {
	        $allowed = false;
	    }

	    return $allowed;
    }


	public function isRegistrationFieldVisible($field_name, $type = self::PLAYER) {
		if (isset($this->getRegistrationFields($type)[$field_name]['visible'])) {
			return @$this->getRegistrationFields($type)[$field_name]['visible'] == self::VISIBLE;
		}
		return false;
	}

	public function isRegistrationFieldRequired($field_name, $type = self::PLAYER) {
		return $this->isRegistrationFieldVisible($field_name, $type) &&
		isset($this->getRegistrationFields($type)[$field_name]['required']) &&
		@$this->getRegistrationFields($type)[$field_name]['required'] == self::REQUIRED;
	}

	public function isRegistrationFieldVisibleByAlias($alias, $type = self::PLAYER) {
		if (isset($this->getRegistrationFieldsByAlias($type)[$alias]['visible'])) {
			return @$this->getRegistrationFieldsByAlias($type)[$alias]['visible'] == self::VISIBLE;
		}
		return false;
	}

	public function isRegistrationFieldRequiredByAlias($alias, $type = self::PLAYER) {
		return $this->isRegistrationFieldVisibleByAlias($alias, $type) &&
		isset($this->getRegistrationFieldsByAlias($type)[$alias]['required']) &&
		@$this->getRegistrationFieldsByAlias($type)[$alias]['required'] == self::REQUIRED;
	}

	public function updateRegistrationField($registrationField, $registrationFieldId) {
		$this->utils->deleteCache($this->getCacheKey(self::PLAYER));
		$this->utils->deleteCache($this->getCacheKey(self::AFFILIATE));
		$this->db->update(self::TABLE, $registrationField, array('registrationFieldId' => $registrationFieldId));
	}

	public function displayPlaceholderHint($field_name){
		$required = '';
		if ( $this->system_feature->isEnabledFeature('enabled_display_placeholder_hint_require') ) {
			if ( $this->isRegistrationFieldRequired($field_name) ){
				$required = '(' . lang('Required') . ')';
			}
		}
		return $required;
	}

	public function displaySymbolHint($field_name){
		$required = '';

		if ( $this->isRegistrationFieldRequired($field_name) ){
			$required = '<span class="required_hint"><i class="text-danger register required">*</i></span>';
		}
		return $required;
	}

	public function player_profile_field_requirement() {
		$reg_fields = $this->getRegistrationFields();
		$fields = [];
		foreach ($reg_fields as $row) {
			$fields[$row['alias']] = !(bool) $row['account_required'];
		}

		return $fields;
	}

	public function getPlayerPreferenceAlias(){
		$playerPreferenceList = [];
		$config_prefs = $this->utils->getConfig('communication_preferences');
		if($this->utils->isEnabledFeature('enable_communication_preferences') && !empty($config_prefs)){
			$playerPreferenceList = array_map(function($key) {
				return 'player_preference_' . $key;
			}, array_keys($config_prefs));
		}
		return $playerPreferenceList;
	}

	public function getOptionsByAlias($alias) {
		$list = [];
		$registrationFields = $this->getRegistrationFieldsByAlias();
		if (!empty($registrationFields[$alias]['options'])) {
			$optionsList = json_decode($registrationFields[$alias]['options'], true);
			$list = array_column($optionsList, 'value', 'name');
		}
		return $list;
    }

    private function validateLimitedUpdateTimes($playerId, $field_name){

        $this->load->model(['player_profile_update_log']);
    
        $settings = $this->utils->getConfig('limit_update_player_times');
        // no limit
        if(empty($settings[$field_name]['limit'])){
            return true;
        }

        // field name does not exist, equal to no limit.
        $fieldUpdatedTimes = $this->player_profile_update_log->getFieldUpdateCountByPlayerId($playerId, $field_name);
        
        if($fieldUpdatedTimes >= $settings[$field_name]['limit']) {
            return false;
        }

        return true;
    }

	# TODO:
	# IS USERNAME AVAILABLE

}

/* End of file ip.php */
/* Location: ./application/models/registration_setting.php */