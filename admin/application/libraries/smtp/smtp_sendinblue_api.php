<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once dirname(__FILE__) . '/abstract_smtp_api.php';

/**
 *
 * T1 - SENDINBLUE SMTP API LIBRARY (uses abstract_smtp_api class)
 *
 *
 * @category SMTP
 * @version 1.0.0
 * @author Cholo Miguel Antonio
 * @copyright 2013-2022 tot
 *
 */
class Smtp_sendinblue_api extends Abstract_smtp_api{

    /**
     * Initialize default variables
     */
    public $api_url = 'https://api.sendinblue.com/v3/smtp/email';

    public $_SMTP_API_status_codes = array(
		'200' => 'OK. Successful Request',
		'201' => 'OK. Successful Creation',
		'202' => 'OK. Request accepted',
		'204' => 'OK. Successful Update/Deletion',
		'400' => 'Error. Bad Request',
		'401' => 'Error. Authentication Needed',
		'402' => 'Error. Not enough credit, plan upgrade needed',
		'403' => 'Error. Permission denied',
		'404' => 'Error. Object does not exist',
		'405' => 'Error. Method not allowed',
		'406' => 'Error. Not Acceptable',
    );

	// public $parameters = array(
	// 	'personalizations' => array(
    //         array(
    // 			'to' => array(),
    // 			'subject' => ''
    //         ),
	// 	),
	// 	'from' => array(
	// 		'email' => ''
	// 	),
	// 	'content' => array(
    //         array(
    // 			'type' => 'text/html',
    // 			'value' => ''
    //         )
	// 	)
	// );
	public $parameters = array(
		'to' => array(),
		'sender' => array(
			'name' => '',
			'email' => ''
		),
		'subject' => '',
		// 'htmlContent' => '',
		// 'textContent' => '',
	);

	public function __construct(){
		parent::__construct();
	}

	/**
	 * Set Api Credentials
	 *
	 * @param void
	 * @return void
	 * @author Cholo Miguel Antonio
	 */
	public function setApiKeys($current_config = false){
		$API_INFO = !$current_config ? $this->CI->config->item('smtp_api_info'): $this->CI->config->item($current_config);

        if(  !is_array($API_INFO) || empty($API_INFO) ||
            (!isset($API_INFO['api_key']) || empty($API_INFO['api_key'])) ||
            (!isset($API_INFO['api_name']) || empty($API_INFO['api_name'])) || 
            (!isset($API_INFO['api_key_id']) || empty($API_INFO['api_key_id'])))
        {
            if($this->debug) $this->print_log("SENDINBLUE SMTP API: Unable to setup API keys due to missing configurations");
            $this->utils->debug_log('SENDINBLUE SMTP API: Unable to setup API keys due to missing configurations');
            return false;
        }

		$this->api_key = $API_INFO['api_key'];
		$this->api_name = $API_INFO['api_name'];
		$this->api_key_id = $API_INFO['api_key_id'];

		if(isset($API_INFO['from_mail'])) {
			$this->from = $API_INFO['from_mail'];
		}
		if(isset($API_INFO['from_name'])) {
			$this->from_name = $API_INFO['from_name'];
		}

        return $this->api_key;
	}

    /**
     * Get Api Credentials
     *
     * @param void
     * @return string API KEY
     * @author Cholo Miguel Antonio
     */
    public function getApiKeys(){
        return $this->api_key;
    }

	/**
	 * Return CURL headers
	 *
	 * @param void
	 * @return array API Headers
	 * @author Cholo Miguel Antonio
	 */
	public function getHeaders(){
		return array(
			'accept: application/json',
			'api-key: ' . $this->api_key,
			'Content-Type: application/json',
			// 'Authorization: Bearer ' . $this->api_key
		);
	}

	/**
	 * Return API URL
	 *
	 * @param void
	 * @return string API URL
	 * @author Cholo Miguel Antonio
	 */
	public function getURL()
    {
        return $this->api_url;
    }


    /**
     * Sets the API Request Parameters for sending emails
     * 
     * @param string $to        
     * @param string $from      
     * @param string $from_name 
     * @param string $subject   
     * @param string $content   
     * @param string $cc        
     * @param string $bcc       
     * @return array API Request Parameters
     * @author Cholo Miguel Antonio
     */
    public function setParameters($to, $from, $from_name, $subject, $content, $cc = null, $bcc = null, $template_id = null){

		$valid_content = json_encode($content);
        if($this->debug) $this->print_log("===============setParameters arguments' values: TO => $to | FROM => $from | FROM_NAME => $from_name | SUBJECT => $subject | CONTENT => $valid_content | CC => $cc | BCC => $bcc");

        $this->utils->debug_log("===============setParameters arguments' values: TO => $to | FROM => $from | FROM_NAME => $from_name | SUBJECT => $subject | CONTENT => $valid_content | CC => $cc | BCC => $bcc");
    	if(empty(trim($to)) || empty(trim($from)) || empty(trim($from_name)) || empty(trim($subject)) || empty(trim($valid_content)))
    		return false;

    	$tos = explode(',',$to);
    	$ccs = explode(',',$cc);
    	$bccs = explode(',',$bcc);

    	// -- set TO recepients
    	foreach ($tos as $to_key => $to_item) {
			$this->parameters['to'][] = array('email' => $to_item);
		}

		// -- set FROM details from operator settings
    	$this->parameters['sender']['name']  = $from_name;
    	$this->parameters['sender']['email'] = $from;

    	// -- set CC recepients
    	if(isset($cc) && trim($cc) != null){
    		$this->parameters['cc'] = array();

    		$ccs = explode(',',$cc);
    		foreach ($ccs as $cc_key => $cc_item) {
				$this->parameters['cc'][] = array('email' => $cc_item);
			}
    	}

    	// -- set BCC recepients
    	if(isset($bcc) && trim($bcc) != null){
    		$this->parameters['bcc'] = array();

    		$bccs = explode(',',$bcc);
    		foreach ($bccs as $bcc_key => $bcc_item) {
				$this->parameters['bcc'][] = array('email' => $bcc_item);
			}
    	}

        $this->parameters['subject'] = $subject;
		if(is_array($content)) {
			// $content = [
			// 	'template_id' => $this->getApiTemplateID(),
			// 	'params' => $this->getTemplateParams(),
			// 	'htmlContent' => $mail['mail_content'],
			// ];
			foreach ($content as $item => $value) {
				switch($item) {
					case 'htmlContent':
						$this->parameters['htmlContent'] = $content['htmlContent'];
						break;
					case 'template_id':
						$this->parameters['templateId'] = $content['template_id'];
						break;
					case 'params':
						$this->parameters['params'] = $content['params'];
						break;
				}
			}

		} else {
			if(!empty($template_id)){
				$this->parameters['templateId'] = $template_id;
			} else {
	
				$this->parameters['htmlContent'] = $content;
			}
		}



    	return $this->parameters;
    }


    /**
     * Get error messages from API
     * 
     * @param  array $response API response
     * @return array           Array of error messages
     * @author Cholo Miguel Antonio
     */
    public function getErrorMessages($response)
    {
        $result = (array) json_decode($response);
        $error_messages = array();

        if(isset($result['errors']) && is_array($result['errors']))
        {
        	foreach ($result['errors'] as $errors_key => $errors) {
                
                $error_messages[] = $errors->message;
        	}
        }

        return $error_messages;
    }

    /**
     * Checks if sending of email was a success
     * 
     * @param  json  $response API Respone
     * @return boolean         API Response Status
     * @author Cholo Miguel Antonio
     */
    public function isSuccess($response)
    {
    	if(trim($response) == '')
    		return true;

    	$result = (array) json_decode($response);

        if(isset($result['errors']) && is_array($result['errors']))
        	return false;
        
        return true;
    }

}