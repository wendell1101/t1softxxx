<?php

require_once dirname(__FILE__) . "/evolution_seamless_service_api.php";

class Idn_evolution_seamless_service_api extends Evolution_seamless_service_api
{
    protected $SUBPROVIDERS = [
        IDN_EVOLUTION_SEAMLESS_GAMING_API,
        IDN_EVOLUTION_BTG_SEAMLESS_GAMING_API,
        IDN_EVOLUTION_NLC_SEAMLESS_GAMING_API,
        IDN_EVOLUTION_REDTIGER_SEAMLESS_GAMING_API,
        IDN_EVOLUTION_NETENT_SEAMLESS_GAMING_API
    ];

}
