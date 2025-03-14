<?php

/**
 * Defines the common functions a Roulette API should implement.
 *
 * @category Roulette
 * @copyright 2013-2022 tot
 */
interface Roulette_api_interface {
	/**
	 * @param int orderId
	 * @param int $promo_cms_id
	 * @param array callbackExtraInfo
	 *
	 * @return array ('success' => boolean, 'spinTimes' => int, 'message' => lang())
	 */
	public function generateRouletteSpinTimes($player_id, $promo_cms_id);
	/**
	 * @param int player_id
	 * @param int $promo_cms_id
	 * @param int $subWalletId
	 * @param DateTime orderDateTime
	 *
	 * @return array ('success' => boolean, 'type'=>1=super, 2=normal, 'params'=>array, 'html'=>string)
	 */
	public function createRoulette($player_id, $promo_cms_id, $sub_wallet_id);
	/**
	 * @param int orderId
	 * @param array callbackExtraInfo
	 *
	 * @return array ('success' => boolean, 'data' => array, 'message' => lang())
	 */
	public function getRouletteWinningList($start_date, $end_date, $offset, $limit, $player_id = null, $refreshCache= false);
	/**
	 * detail:get Player Bet And Deposit Amount by Date
	 * 
	 * @return array
	 */
	public function getPlayerBetAndDepositAmount($player_id, $start_date, $end_date);
}
