<?php
require_once APPPATH . '/libraries/payment/abstract_payment_api.php';

abstract class Abstract_crypto_payment_api extends Abstract_payment_api
{
    public function __construct()
    {
        parent::__construct();
    }

    public function generatePaymentUrlForm($orderId, $playerId, $amount, $orderDateTime, $playerPromoId = null, $enabledSecondUrl = true, $bankId = null)
    {
        return null;
    }

    public function getChains($coin_id) {
        $coin_id = strtolower($coin_id);

        return $this->getSystemInfo('support_chains_' . $coin_id, []);
    }

    /**
     * GetAddress function
     *
     * @param int|string $player_id
     * @param string $chain_id
     * @param string $token
     * @return null|string
     */
    abstract function getAddress($player_id, $chain_id, $token);
}