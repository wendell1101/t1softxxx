<?php

require_once dirname(__FILE__) . '/BaseController.php';

/**
 *
 * @deprecated
 *
 * Class Auth
 * @deprecated
 *
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Auth extends BaseController {

	public function __construct() {
		parent::__construct();

		$this->load->helper('url');
		$this->load->library(array('form_validation', 'authentication', 'player_functions', 'template', 'affiliate_process', 'email', 'salt', 'cms_function', 'game_platform/game_platform_manager', 'duplicate_account'));
	}

	/**
	 * overview : set message for users
	 *
	 * @param $type
	 * @param $message
	 * @return set session user data
	 */
	// public function alertMessage($type, $message) {
	// 	switch ($type) {
	// 		case '1':
	// 			$show_message = array(
	// 				'result' => 'success',
	// 				'message' => $message,
	// 			);
	// 			$this->session->set_userdata($show_message);
	// 			break;

	// 		case '2':
	// 			$show_message = array(
	// 				'result' => 'danger',
	// 				'message' => $message,
	// 			);
	// 			$this->session->set_userdata($show_message);
	// 			break;

	// 		case '3':
	// 			$show_message = array(
	// 				'result' => 'warning',
	// 				'message' => $message,
	// 			);
	// 			$this->session->set_userdata($show_message);
	// 			break;
	// 	}
	// }

	/**
	 * overview : index page for login
	 */
	public function index() {
		redirect('/');
		// $this->login();
	}

	/**
	 * overview : loads template for view based on regions in
	 *
	 * detail : config > template.php
	 *
	 * @param string $title
	 * @param string $description
	 * @param string $keywords
	 * @param string $activenav
	 */
	// private function loadTemplate($title, $description, $keywords, $activenav) {
	// 	$this->template->add_js('resources/js/player/player.js');
	// 	$this->template->add_css(CSSPATH . 'bootstrap.css');
	// 	$this->template->add_css(CSSPATH . 'template.css');

	// 	$this->template->write('skin', 'template1.css');
	// 	$this->template->write('title', $title);
	// 	$this->template->write('description', $description);
	// 	$this->template->write('keywords', $keywords);
	// 	$this->template->write('activenav', $activenav);
	// 	$this->template->write('username', $this->authentication->getUsername());
	// 	$this->template->write('player_id', $this->authentication->getPlayerId());
	// }

	/**
	 * overview : send promo code
	 *
	 * @param $promoCode
	 */
	// public function sendPromoCode($promoCode) {
	// 	$checkPromoCodeExist = $this->player_functions->checkPromoCodeExist($promoCode);
	// 	$promo = $this->player_functions->retrievePromo($checkPromoCodeExist['promoId']);
	// 	$player = $this->player_functions->getPlayerById($this->session->userdata('player_id'));
	// 	$sorted_rules = $promo['levels'];
	// 	$isInLevel = false;

	// 	foreach ($sorted_rules as $row) {
	// 		if ($player['playerLevel'] == $row['id']) {
	// 			$isInLevel = true;
	// 			break;
	// 		} else {
	// 			$isInLevel = false;
	// 		}
	// 	}

	// 	if ($isInLevel) {
	// 		$this->session->set_userdata('promoCode', $promoCode);
	// 		if (!$this->authentication->isLoggedIn()) {
	// 			redirect(BASEURL . 'auth/register');
	// 		} else {
	// 			redirect(BASEURL . 'cashier/makeDeposit/' . $this->session->userdata('player_id'));
	// 		}
	// 	} else {
	// 		//$message = "This promo is not for your level";
	// 		$message = lang('notify.13');
	// 		$this->alertMessage(2, $message);
	// 		redirect(BASEURL . 'online/promotions');
	// 	}
	// }

	/**
	 * overview : resend email
	 *
	 * @param int	$player_id
	 */
	// public function resendEmail($player_id) {
	// 	$player = $this->player_functions->getPlayerById($player_id);
	// 	$email_settings = $this->player_functions->getEmail();

	// 	$data['email_sender'] = (string) $email_settings['email'];
	// 	$data['email_sender_pass'] = (string) base64_decode($email_settings['password']);

	// 	$send_email = $this->send_email('verification', $player['email'], $data);

	// 	//$message = "New verification message was sent to your email";
	// 	$message = lang('notify.14');
	// 	$this->alertMessage(1, $message);
	// 	redirect(BASEURL . 'player_controller/playerSettings/' . $player_id);
	// }

	/**
	 * overview : Testing Email Sending
	 *
	 * @return void
	 */
	// public function testSendEmail() {
	// 	$this->email_setting->testSendEmail();
	// }
}
