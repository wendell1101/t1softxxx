<?php if (!defined("BASEPATH")) {exit("No direct script access allowed");}

class MY_Exceptions extends CI_Exceptions {


    public function __construct()
    {
        parent::__construct();
        log_message('error', 'MY_Exceptions.admin.9.__construct', $this->generateCallTrace());
    }
    public function show_wait_reload($wait_sec = 2){

        $LANG = &load_class('Lang', 'core');
        $LANG->load('main');

        $this->CI = &get_instance();
        $isAjaxCall = $this->CI->input->is_ajax_request() ? true : false;

        //add request id
		header('X-Request-Id: '._REQUEST_ID);

        $url = $_SERVER["REQUEST_URI"];
        $delay_sec = 3;
        // log_message('error', $heading.': '.$page);
        $this->CI->utils->debug_log('OGP-32372.21.show_wait_reload', 'wait_sec:', $wait_sec, '_REQUEST_ID:', _REQUEST_ID);

        if($isAjaxCall){
            $msg_sprintf = "The request should wait %d seconds before replaying";
        }else{
            $msg_sprintf = "The page will wait %d seconds before reloading";
        }
        $msg_sprintf = $LANG->line($msg_sprintf);
        $message = sprintf($msg_sprintf, $wait_sec);

        $this->CI->utils->debug_log('OGP-32372.37.show_wait_reload.Refresh', 'wait_sec:', $wait_sec, 'url:', $url);
        if($isAjaxCall){
            $data=[];
            $data['code'] = 99991163; // just uniqueness
            $data['message'] = $message;
            $data['wait_sec'] = $wait_sec;
            $data['error'] = $message; // for DataTables
            $data['request_uri'] = $url;
            $txt = json_encode($data);
            $this->CI->output->set_content_type('application/json')
                            /// 503, for dataTable in admin site.
                            // ref. to "$.fn.dataTable.ext.errMode"; "alert" is default in player site.
                            ->set_status_header(503) // the server is busy, overloaded, or down for maintenance
                            ->set_output($txt);
        }else{
            // header("Refresh:". $delay_sec,  $url);
            $sprint_header = 'Refresh:%d; url=%s';// 2 params, delay_sec, url
            $_header = sprintf($sprint_header, $delay_sec, $url);

            $message = '<p>' . implode('</p><p>', (!is_array($message)) ? array($message) : $message) . '</p>';

            $this->CI->output->set_header($_header)
                            // 200, for show the prompt.
                            ->set_status_header(200)
                            ->set_output($message);
        }
        $this->CI->output->_display();
        exit();
    }



    function generateCallTrace(){
        $e = new Exception();
        $trace = explode("\n", $e->getTraceAsString());
        // reverse array to make steps line up chronologically
        $trace = array_reverse($trace);
        array_shift($trace); // remove {main}
        array_pop($trace); // remove call to this method
        $length = count($trace);
        $result = array();

        for ($i = 0; $i < $length; $i++)
        {
            $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
        }

        return "\t" . implode("\n\t", $result);
    }
}