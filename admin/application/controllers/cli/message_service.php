<?php
require_once dirname(__FILE__) . "/base_cli.php";

// require_once dirname(__FILE__) . "/../../libraries/vendor/autoload.php";

use \Pubnub\Pubnub;

/**
 * only cli
 *
 *
 *
 */
class Message_service extends Base_cli {

	public function __construct() {
		parent::__construct();

		$this->config->set_item('app_debug_log', APPPATH . 'logs/message_service.log');
	}

	public function run() {

		$this->load->model(array('users'));
		$utils = $this->utils;
		$user_model = $this->users;

		$subscribe_key = $this->utils->getConfig('pubnub_subscribe_key');
		$channel_admin_announcement = $this->utils->getConfig('channel_admin_announcement');

		$this->utils->debug_log('run on ', $channel_admin_announcement);

		$server_name = $utils->getConfig('server_name');

		if (empty($subscribe_key) || empty($channel_admin_announcement)) {
			throw new Exception('empty subscribe_key or channel_admin_announcement, halt');
		}

		if (empty($server_name)) {
			throw new Exception('empty server_name, halt');
		}

		$pubnub = new Pubnub(null, $subscribe_key);

		while (true) {

			$pubnub->subscribe($channel_admin_announcement, function ($data) use ($utils, $user_model, $server_name) {
				$utils->debug_log('get data', $data);
				//insert to db
				$msg = $data['message'];
				if (!empty($msg)) {
					$content = $msg['content'];
					$options = @$msg['options'];
					$servers = $msg['servers'];
					$user = $msg['user'];

					if (in_array($server_name, $servers)) {
						$user_model->writeUnreadAdminMessage($user, $content, $options);
					} else {
						$utils->debug_log('drop message', $servers, 'user', $user);
					}
				}
			});

		}

	}

}
