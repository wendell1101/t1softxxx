<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/abstract_telesale_api.php';

/**
 * Dummy track api
 *
 *
 * @version		1.0.0
 */

class Super7_telesale_api extends Abstract_telesale_api
{
    public function init(){
        $this->CI->load->model(array('player_model'));
        $this->_options = array_replace_recursive($this->_options, config_item('super7_telesale'));
    }

    public function postSaveCustomerData($player_id, $player_details){
        $first_name           = (isset($player_details[0]) && !empty($player_details[0]['firstName']))? $player_details[0]['firstName'] : '';
        $last_name            = (isset($player_details[0]) && !empty($player_details[0]['lastName']))? $player_details[0]['lastName'] : '';
        $registered_by        = (isset($player_details[0]) && !empty($player_details[0]['registered_by']))? $player_details[0]['registered_by'] : '';
        $registration_website = (isset($player_details[0]) && !empty($player_details[0]['registrationWebsite']))? $player_details[0]['registrationWebsite'] : '';
        $affiliate_id         =  (isset($player_details[0]) && !empty($player_details[0]['affiliateId']))? $player_details[0]['affiliateId'] : '';

        if(!empty($affiliate_id)){
            $this->CI->load->model(array('affiliatemodel'));
            $affiliate = $this->CI->affiliatemodel->getUsernameById($affiliate_id);
        }else{
            $affiliate = '';
        }

        $params = array();
        $params['token']            = $this->getOptions('token');
        $params['username']         = (isset($player_details[0]) && !empty($player_details[0]['username']))? $player_details[0]['username'] : '';
        $params['email']            = (isset($player_details[0]) && !empty($player_details[0]['email']))? $player_details[0]['email'] : '';
        $params['phone_number']     = (isset($player_details[0]) && !empty($player_details[0]['contactNumber']))? $player_details[0]['contactNumber'] : '';
        $params['fullname']         = $first_name.' '.$last_name;
        $params['dob']              = (isset($player_details[0]) && !empty($player_details[0]['birthdate']))? $player_details[0]['birthdate'] : '';
        $params['referral_code']    = (isset($player_details[0]) && !empty($player_details[0]['invitationCode']))? $player_details[0]['invitationCode'] : '';
        $params['domain']           = $this->getDomain($registration_website);
        $params['division']         = $this->getOptions('division');
        $params['application']      = $this->switchRegisterSource($registered_by);
        $params['affiliate']        = $affiliate;

        return $this->processCurl($params, $player_id, 'saveCustomerData');
    }

    public function switchRegisterSource($registered_by){
        $application = '';
        switch ($registered_by) {
            case player_model::REGISTERED_BY_IMPORTER:
                $application = 'WEB_APP';
                break;
            case player_model::REGISTERED_BY_WEBSITE:
                $application = 'WEB_APP';
                break;
            case player_model::REGISTERED_BY_MASS_ACCOUNT:
                $application = 'WEB_APP';
                break;
            case player_model::REGISTERED_BY_MOBILE:
                $application = 'MOBILE_APP';
                break;
            case player_model::REGISTERED_BY_PLAYER_CENTER_API:
                $application = 'MOBILE_APP';
                break;
            default:
                $application = 'WEB_APP';
                break;
        }
        return $application;
    }
}
