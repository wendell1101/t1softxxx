<?php
/**
 * SABA Odds Direct exclusive API endpoint
 * OGP-25143
 *
 * @see		routes		(player/application/config/routes.php)
 * @see		api_common	(player/application/controllers/api_common.php)
 *
 */

require_once dirname(__FILE__) . '/t1t_ac_tmpl.php';

class Saba extends T1t_ac_tmpl {

	protected $black_list_enabled = false;
	protected $black_list = [];

	protected $white_list_enabled = true;
	protected $white_list = [
        'refreshToken',

        'createGameAccount',
        'getSports',
        'getLeagues',
        'getEvents',
        'getMarkets',
        'getOutrights',
        'getPromotions',
        'getStreaming',
        'getAnnouncement',
        'getSingleTicket',
        'getParlayTickets',
        'getOutrightTicket',
        'placeBet',
        'placeParlayBet',
        'placeOutrightBet',
        'checkWaitingTicketStatus',
        'checkPlaceBet',
        'getCashoutPrice',
        'sellBack',
        'checkSellingStatus',
        'getBetDetails',
        'getSystemParlayDetails',
        'getGameDetails',
        'getSportResults',
        'getEventResults',
        'getOutrightResults',
        'checkUserBalance',

        
        'getLeaguesPush',
        'getEventsPush',
        'getMarketsPush',
        'getOutrightsPush',
        'getSportsPush',

        
        'refreshBalance',
	];

    protected $apiMethodMap = [
        'createGameAccount'         => 'POST',
        'login'                     => 'POST',
        'refreshToken'              => 'POST',
        'getSports'                 => 'GET',
        'getLeagues'                => 'GET',
        'getEvents'                 => 'GET',
        'getMarkets'                => 'GET',
        'getOutrights'              => 'GET',
        'getPromotions'             => 'GET',
        'getStreaming'              => 'GET',
        'getAnnouncement'           => 'GET',
        'getSingleTicket'           => 'GET',
        'getParlayTickets'          => 'POST',
        'getOutrightTicket'         => 'GET',
        'placeBet'                  => 'POST',
        'placeParlayBet'            => 'POST',
        'placeOutrightBet'          => 'POST',
        'checkWaitingTicketStatus'  => 'GET',
        'checkPlaceBet'             => 'GET',
        'getCashoutPrice'           => 'GET',
        'sellBack'                  => 'POST',
        'checkSellingStatus'        => 'GET',
        'getBetDetails'             => 'GET',
        'getSystemParlayDetails'    => 'GET',
        'getGameDetails'            => 'GET',
        'getSportResults'            => 'GET',
        'getEventResults'           => 'GET',
        'getOutrightResults'        => 'GET',
        'checkUserBalance'          => 'GET',

        'getLeaguesPush'            => 'GET',
        'getEventsPush'             => 'GET',
        'getMarketsPush'            => 'GET',
        'getOutrightsPush'          => 'GET',
        'getSportsPush'             => 'GET',
    ];


    const CODE_INVALID_REQUEST = 400;


	function __construct() {
		parent::__construct();
        $this->version = $this->utils->getConfig('saba_odds_direct_api_version');

        $this->load->model(array('wallet_model','game_provider_auth','external_common_tokens','player_model','game_description_model','external_system'));

        $this->URL_MAP = [
            'login'                     => '/login',
            'refreshToken'              => '/refreshToken',
            'getSports'                 => '/sports/'. $this->version . '/GetSports',
            'getLeagues'                => '/sports/'. $this->version . '/GetLeagues',
            'getEvents'                 => '/sports/'. $this->version . '/GetEvents',
            'getMarkets'                => '/sports/'. $this->version . '/GetMarkets',
            'getOutrights'              => '/sports/'. $this->version . '/GetOutrights',
            'getPromotions'             => '/sports/'. $this->version . '/GetPromotions',
            'getStreaming'              => '/sports/'. $this->version . '/GetStreaming',
            'getAnnouncement'           => '/sports/'. $this->version . '/GetAnnouncement',
            'getSingleTicket'           => '/betting/'. $this->version . '/GetSingleTicket',
            'getParlayTickets'          => '/betting/'. $this->version . '/GetParlayTickets',
            'getOutrightTicket'         => '/betting/'. $this->version . '/GetOutrightTicket',
            'placeBet'                  => '/betting/'. $this->version . '/PlaceBet',
            'placeParlayBet'            => '/betting/'. $this->version . '/PlaceParlayBet',
            'placeOutrightBet'          => '/betting/'. $this->version . '/PlaceOutrightBet',
            'checkWaitingTicketStatus'  => '/betting/'. $this->version . '/CheckWaitingTicketStatus',
            'checkPlaceBet'             => '/betting/'. $this->version . '/CheckPlaceBet',
            'getCashoutPrice'           => '/cashout/'. $this->version . '/GetCashoutPrice',
            'sellBack'                  => '/cashout/'. $this->version . '/SellBack',
            'checkSellingStatus'        => '/cashout/'. $this->version . '/CheckSellingStatus',
            'getBetDetails'             => '/betting/'. $this->version . '/GetBetDetails',
            'getSystemParlayDetails'    => '/betting/'. $this->version . '/GetSystemParlayDetails',
            'getGameDetails'            => '/betting/'. $this->version . '/GetGameDetails',
            'getSportResults'           => '/sports/'. $this->version . '/GetSportResults',
            'getEventResults'           => '/sports/'. $this->version . '/GetEventResults',
            'getOutrightResults'        => '/sports/'. $this->version . '/GetOutrightResults',
            'checkUserBalance'          => '/betting/'. $this->version . '/CheckUserBalance',

            'getLeaguesPush'            => '/sports/stream/'. $this->version . '/GetLeagues',
            'getEventsPush'             => '/sports/stream/'. $this->version . '/GetEvents',
            'getMarketsPush'            => '/sports/stream/'. $this->version . '/GetMarkets',
            'getOutrightsPush'          => '/sports/stream/'. $this->version . '/GetOutrights',
            'getSportsPush'             => '/sports/stream/'. $this->version . '/GetSports',
        ];

        $this->vendor_id = $this->utils->getConfig('saba_odds_direct_api_vendor_id');

        try {
            $this->processRequest();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
            return $this->returnApiResponseByArray($response);
        }
	}


    /**
     * calls the createPlayer api end point of SABA Odds Direct
     */
    public function createGameAccount($gamePlatformId){
        try {
            $api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
            $rlt = $api->isPlayerExist($this->playerUsername);
            if(isset($rlt['exists']) && $rlt['exists']==false){
                $password = $this->player_model->getPasswordByUsername($this->playerUsername);
                $rlt = $api->createPlayer($this->playerUsername, $this->playerId, $password);

                if(!isset($rlt['success']) || !$rlt['success']){
                    throw new Exception(lang('Error creating player to external system'), self::CODE_INVALID_USER);
                }
            }

            $this->loginOrRefreshToken($gamePlatformId);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetSports api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-GetSports
     */
    public function getSports($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'query',
                'from',
                'until',
                'language',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getSports', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetLeagues api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-GetLeagues
     */
    public function getLeagues($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'query',
                'from',
                'until',
                'language',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getLeagues', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }



    /**
     * calls the GetEvents api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-GetEvents
     */
    public function getEvents($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'query',
                'from',
                'until',
                'language',
                'includeMarkets',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getEvents', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetMarkets api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-GetMarkets
     */
    public function getMarkets($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'query',
                'language',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getMarkets', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }

    /**
     * calls the GetOutrights api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-GetOutrights
     */
    public function getOutrights($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'query',
                'from',
                'until',
                'language',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getOutrights', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetPromotions api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-GetPromotions
     */
    public function getPromotions($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'query',
                'language',
                'includeMarkets',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getPromotions', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetStreaming api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-GetStreaming
     */
    public function getStreaming($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'sportType',
                'streamingOption',
                'channelCode',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getStreaming', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetAnnouncement api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-GetAnnouncement
     */
    public function getAnnouncement($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'start',
                'end',
                'stickOption',
                'language',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getAnnouncement', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetSingleTicket api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-GetSingleTicket
     */
    public function getSingleTicket($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'sportType',
                'marketId',
                'key',
                'oddsType',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getSingleTicket', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetParlayTickets api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-GetParlayTickets
     */
    public function getParlayTickets($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'parlayTickets', //? see data structure in docs
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getParlayTickets', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetOutrightTicket api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-GetOutrightTicket
     */
    public function getOutrightTicket($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'SportType',
                'Orid',
                'Language',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getOutrightTicket', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the PlaceBet api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-PlaceBet
     */
    public function placeBet($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'vendorTransId',
                'sportType',
                'marketId',
                'price',
                'point',
                'key',
                'stake',
                'oddsOption',
                'oddsType',
                'language',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $options['force_http_build_query'] = true;

            $this->callOddsDirectEndpoint('placeBet', $params, $options);

            list($success, $balance)=$this->refreshPlayerBalance($gamePlatformId, $this->playerId, $this->playerUsername);            

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * refreshh player balance
     *
     */
    public function refreshBalance($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $this->callOddsDirectEndpoint('checkUserBalance');

            list($success, $balance)=$this->refreshPlayerBalance($gamePlatformId, $this->playerId, $this->playerUsername);
            $response = $this->getOddsDirectResponse();
            $response['success']= $success;
            $response['balance']= $balance;
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }

    private function refreshPlayerBalance($gamePlatformId, $playerId, $playerUsername){
        $this->utils->debug_log('SABA API refreshPlayerBalance init', 'gamePlatformId', $gamePlatformId, 'playerId', $playerId, 'playerUsername', $playerUsername);
            
        $controller = $this;
        //$playerId = $this->playerId;
        $balance = null;
        $success = $this->lockAndTransForPlayerBalance($playerId, function () use ($controller, $gamePlatformId, $playerUsername, $playerId, &$balance) {
            $api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
            $rlt = $api->queryPlayerBalance($playerUsername);
            $this->utils->debug_log('SABA API refreshPlayerBalance ', 'rlt', $rlt, 'gamePlatformId', $gamePlatformId, 'playerId', $playerId, 'playerUsername', $playerUsername);
            if(isset($rlt['success']) && $rlt['success']){
                if(isset($rlt['balance'])&&! $api->isSeamLessGame()){
                    $balance = $rlt['balance']; 
                    $api->updatePlayerSubwalletBalanceWithoutLock($playerId, $balance);
                }                            
            }
            return true;
        });
        return [$success, $balance];
    }


    /**
     * calls the PlaceParlayBet api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-PlaceParlayBet
     */
    public function placeParlayBet($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'betInfo', //? see data structure in docs
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('placeParlayBet', $params);

            list($success, $balance)=$this->refreshPlayerBalance($gamePlatformId, $this->playerId, $this->playerUsername); 

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }

    /**
     * calls the PlaceOutrightBet api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-PlaceOutrightBet
     */
    public function placeOutrightBet($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'vendorTransId',
                'sportType',
                'orid',
                'price',
                'stake',
                'language',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $options['force_http_build_query'] = true;

            $this->callOddsDirectEndpoint('placeOutrightBet', $params, $options);

            list($success, $balance)=$this->refreshPlayerBalance($gamePlatformId, $this->playerId, $this->playerUsername); 

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the CheckPlaceBet api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-CheckPlaceBet
     */
    public function checkPlaceBet($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'VendorTransId',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('checkPlaceBet', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the CheckWaitingTicketStatus api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-CheckWaitingTicketStatus
     */
    public function checkWaitingTicketStatus($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'transId',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('checkWaitingTicketStatus', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetCashoutPrice api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/GetCashoutPrice
     */
    public function getCashoutPrice($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'transIds',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getCashoutPrice', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the SellBack api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/SellBack
     */
    public function sellBack($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'transId',
                'cashoutPrice',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $options['force_http_build_query'] = true;

            $this->callOddsDirectEndpoint('sellBack', $params, $options);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the CheckSellingStatus api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-CheckSellingStatus
     */
    public function checkSellingStatus($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'transId',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('checkSellingStatus', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetBetDetails api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-GetBetDetails
     */
    public function getBetDetails($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'start',
                'end',
                'isSettled',
                'language',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getBetDetails', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetSystemParlayDetails api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-GetSystemParlayDetails
     */
    public function getSystemParlayDetails($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'parlayTicketNo',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getSystemParlayDetails', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetGameDetails api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/beta-GetGameDetails
     */
    public function getGameDetails($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'eventIds',
                'language',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getGameDetails', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }

    /**
     * calls the GetSportResults api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/GetSportResults
     */
    public function getSportResults($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'from',
                'until',
                'language',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getSportResults', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetEventResults api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/GetEventResults
     */
    public function getEventResults($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'from',
                'until',
                'language',
                'query',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getEventResults', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetOutrightResults api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/GetOutrightResults
     */
    public function getOutrightResults($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'from',
                'until',
                'language',
                'query',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $this->callOddsDirectEndpoint('getOutrightResults', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the CheckUserBalance api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/CheckUserBalance
     */
    public function checkUserBalance($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            list($success, $balance)=$this->refreshPlayerBalance($gamePlatformId, $this->playerId, $this->playerUsername);
            
            $this->callOddsDirectEndpoint('checkUserBalance');

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }

    // ---------------------INTERNAL FUNCTIONS--------------------- //

    private function getResponseResult(){
        return $this->response[1];
    }

    /**
     * returns the content of the callHttp result in a formatted output
     */
    private function getOddsDirectResponse($status = null, $code = null, $message = null, $response = null){
        if(empty($status)){
            $status = true;
        }
        if(empty($code)){
            $code = self::CODE_SUCCESS;
        }
        if(empty($message)){
            $message = lang('Success');
        }
        if(empty($response)){
            $response = [ json_decode($this->getResponseResult()) ];
        }

        return [
            'success' => $status,
            'code' => $code,
            'mesg' => $message,
            'result' => $response
        ];
    }

    private function buildExceptionResponse(Exception $exception){
        return [
            'success'   => false,
            'code'      => $exception->getCode(),
            'mesg'   => $exception->getMessage(),
            'result'    => null
        ];
    }

    /**
     * retrieves the JSON content of the request and inserts them into the object instance
     */
    private function processRequest(){
        $this->requestContents = json_decode(trim(file_get_contents('php://input')), true);

        $this->CI->utils->debug_log('SABA ONEWORKS Request body Contents', $this->requestContents);

        if (empty($this->requestContents) || $this->requestContents == null){
            throw new Exception(lang('Request invalid.'), self::CODE_INVALID_REQUEST);
        }

        $request = $this->requestContents;

        $this->requestApiKey = isset($request['api_key']) ? $request['api_key'] : '';

        $this->verifyApiKey($this->requestApiKey);

        $token = isset($request['token']) ? $request['token'] : '';
        $this->verifyToken($token);
    }

    private function loginOrRefreshToken($gamePlatformId){
        // $this->playerAvailableToken = $this->external_common_tokens->getExternalToken($this->playerId, $gamePlatformId);

        // if ($this->external_common_tokens->checkActiveExternalToken($this->playerId, $gamePlatformId, $this->playerAvailableToken)){
            $this->sabaLogin($gamePlatformId);
        // } else {
            // $this->sabaRefreshToken($gamePlatformId);
        // }

        $this->insertOrUpdateAuthToken($gamePlatformId);
    }

    private function callHttp($apiMethod, $params = [], $callOptions = []) {
        // $curlOptions = null;

        $curlOptions[CURLOPT_ENCODING] = '';

        if ($this->utils->getConfig('RUNTIME_ENVIRONMENT') == 'local'){
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = false;
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
        }

        $options = [
            'call_socks5_proxy' => $this->utils->getConfig('saba_odds_direct_curl_proxy'),
        ];

        $domain = $this->utils->getConfig('saba_odds_direct_api_base_url');
        $url = $domain . $this->URL_MAP[$apiMethod];
        $method = $this->apiMethodMap[$apiMethod];

        if (isset($callOptions['force_http_build_query']) && $callOptions['force_http_build_query'] == true){
            $url .= '?' . http_build_query($params);
        } else {
            $params = $method != 'POST' ? $params : json_encode($params);
        }

        $this->buildHeaders($apiMethod);

        $this->utils->debug_log('Oneworks Saba API URL', $url);
        $this->utils->debug_log('Oneworks Saba API PARAMS', $method, $params, $this->httpHeaders);

        $this->response = $this->utils->callHttpWithProxy($url, $method, $params, $options, $curlOptions, $this->httpHeaders);

        $this->utils->debug_log('Oneworks Saba RESPONSE', $this->response);
    }

    private function buildUrl($apiMethod, $params = []){
        $domain = $this->utils->getConfig('saba_odds_direct_api_base_url');
        $url = $domain . $this->URL_MAP[$apiMethod];
        $method = $this->apiMethodMap[$apiMethod];
        if($method != 'POST'){
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }

    private function callOddsDirectEndpoint($endpoint, $parameters = [], $options = []){
        $this->callHttp($endpoint, $parameters, $options);
    }

    private function buildHeaders($apiMethod){
        if (!in_array($apiMethod,['login','refreshToken'])){
            $this->httpHeaders = [
                'Authorization' => $this->playerAvailableToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip',
                'X-Forwarded-For' => $this->utils->getIP(),
            ];
            if(in_array($apiMethod,['getLeaguesPush','getEventsPush', 'getMarketsPush', 'getOutrightsPush', 'getSportsPush'])){
                $this->httpHeaders = [
                    #'Authorization' => $this->playerAvailableToken,
                    #'Content-Type' => 'application/json',
                    'Accept' => 'text/event-stream',
                    'Accept-Encoding' => 'gzip',
                    #'X-Forwarded-For' => $this->utils->getIP(),
                ];
            }
        
        } else {
            $this->httpHeaders = [
                'X-Forwarded-For' => $this->_getClientIp(),
                'Content-Type' => 'application/json',
            ];
        }
    }

    /**
     * calls the login api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/Login-API
     */
    private function sabaLogin($gamePlatformId){
        $vendorMemberId =  $this->game_provider_auth->getGameUsernameByPlayerId($this->playerId, $gamePlatformId);

        # check if player exist
        $api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
        $rlt = $api->isPlayerExist($this->playerUsername);
        if(isset($rlt['exists']) && $rlt['exists']==false){
            $password = $this->player_model->getPasswordByUsername($this->playerUsername);
            $rlt = $api->createPlayer($this->playerUsername, $this->playerId, $password);
            if(!isset($rlt['success']) || !$rlt['success']){
                throw new Exception(lang('Error creating player to external system'), self::CODE_INVALID_USER);
            }
        }

        $params = [
            'vendor_id' => $this->vendor_id,
            'vendor_member_id' => $vendorMemberId,
        ];

        $this->callHttp('login', $params);
    }

    /**
     * calls the refreshToken api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/RefreshToken-API
     */
    private function sabaRefreshToken($gamePlatformId){
        $vendorMemberId =  $this->game_provider_auth->getGameUsernameByPlayerId($this->playerId, $gamePlatformId);

        $params = [
            'vendor_id' => $this->vendor_id,
            'vendor_member_id' => $vendorMemberId,
        ];

        $this->callHttp('refreshToken', $params);
    }

    /**
     * determines whether or not to store or update the received access_token from the provider
     * ? tokens are not currently stored since provider suggested to not do that
     */
    private function insertOrUpdateAuthToken($gamePlatformId){

        $response = json_decode($this->getResponseResult(), true);

        // commented out since provider said JWT tokens dont have max and can't accurately store tokens
        // so we will login every time
        if(isset($response['token_type']) && isset($response['access_token']) ){
        //     $updatedAuthToken = $response['token_type'] . ' ' . $response['access_token'];
        //     if($this->playerAvailableToken == $updatedAuthToken){

        //         $this->external_common_tokens->setPlayerToken($this->playerId, $updatedAuthToken, $gamePlatformId);
        //         $this->playerAvailableToken = $updatedAuthToken;
        //     } else {
        //         $this->external_common_tokens->addPlayerToken($this->playerId, $this->playerAvailableToken, $gamePlatformId);
        //     }
            $this->sabaAccessToken = $response['access_token'];
            $this->playerAvailableToken = $response['token_type'] . ' ' . $response['access_token'];
        }

        if (empty($this->playerAvailableToken)){
            throw new Exception(lang('Invalid token or user not logged in'), self::CODE_COMMON_INVALID_TOKEN);
        }
    }

    private function verifyApiKey($apiKey){
        if (empty($apiKey) || $apiKey != $this->utils->getConfig('saba_odds_direct_api_key')){
            $this->__returnApiResponse(false, self::CODE_INVALID_SIGNATURE, lang('Invalid signature').", your api_key {$apiKey} is not listed");
        }
    }

    private function verifyToken($token){

        if (empty($token)){
            throw new Exception(lang('Invalid token or user not logged in'), self::CODE_COMMON_INVALID_TOKEN);
        }

        $this->load->model('common_token');
        $player = $this->common_token->getPlayerInfoByToken($token);

        if (empty($player)){
            throw new Exception(lang('Invalid token or user not logged in'), self::CODE_COMMON_INVALID_TOKEN);
        }

        $this->playerUsername = $player['username'];
        $this->playerId = $player['player_id'];

    }


    /**
     * retrieves the json input from the object instance and returns an array
     * containing the key value pair according to the provided $parameters
     *
     * @param array $parameters
     *
     * @return array $resultParameter
     */
    private function getParamsFromRequest($parameters){
        $resultParameter = [];

        foreach($parameters as $parameter){
            if(isset($this->requestContents[$parameter])){
                $resultParameter[$parameter] = $this->requestContents[$parameter];
            }
        }

        if (empty($resultParameter)){
            throw new Exception(lang('Request empty'), self::CODE_INVALID_REQUEST);
        }

        return $resultParameter;
    }


    protected function comapi_return_json($result, $addOrigin = true, $origin = "*") {
        parent::comapi_return_json($result, $addOrigin, $origin);

        $this->output->_display();
        exit();
    }
    
    /**
     * calls the GetLeagues_push api end point of SABA Odds Direct
     * This API is to get how many events count for each league.
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/GetLeagues-push
     */
    public function getLeaguesPush($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'query',
                'from',
                'until',
                'language',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $params['token'] = $this->sabaAccessToken;

            $url = $this->buildUrl('getLeaguesPush', $params);

            $response = [
                'url'=> $url,
                'access_token'=> $this->sabaAccessToken
            ];

            $response = $this->getOddsDirectResponse(null, null, null, $response);
            
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }



    /**
     * calls the GetEvents_push api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/GetEvents-push
     */
    public function getEventsPush($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'query',
                'from',
                'until',
                'language',
                'includeMarkets',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $params['token'] = $this->sabaAccessToken;

            $url = $this->buildUrl('getEventsPush', $params);

            $response = [
                'url'=> $url,
                'access_token'=> $this->sabaAccessToken
            ];

            $response = $this->getOddsDirectResponse(null, null, null, $response);

        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetMarkets push api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/GetMarkets-push
     */
    public function getMarketsPush($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'query',
                'language',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $params['token'] = $this->sabaAccessToken;

            $url = $this->buildUrl('getMarketsPush', $params);

            $response = [
                'url'=> $url,
                'access_token'=> $this->sabaAccessToken
            ];

            $response = $this->getOddsDirectResponse(null, null, null, $response);
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }

    /**
     * calls the GetOutrights push api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/GetOutrights-push
     */
    public function getOutrightsPush($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'query',
                'from',
                'until',
                'language',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $params['token'] = $this->sabaAccessToken;

            $url = $this->buildUrl('getOutrightsPush', $params);

            $response = [
                'url'=> $url,
                'access_token'=> $this->sabaAccessToken
            ];

            $response = $this->getOddsDirectResponse(null, null, null, $response);
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the GetSports push api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/GetSports-push
     */
    public function getSportsPush($gamePlatformId){
        try {
            $this->loginOrRefreshToken($gamePlatformId);

            $endpointParameters = [
                'query',
                'from',
                'until',
                'language',
            ];

            $params = $this->getParamsFromRequest($endpointParameters);

            $params['token'] = $this->sabaAccessToken;

            $url = $this->buildUrl('getSportsPush', $params);

            $response = [
                'url'=> $url,
                'access_token'=> $this->sabaAccessToken
            ];

            $response = $this->getOddsDirectResponse(null, null, null, $response);
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the RefreshToken push api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/RefreshToken-API
     */
    public function refreshToken($gamePlatformId){
        try {
            $vendorMemberId =  $this->game_provider_auth->getGameUsernameByPlayerId($this->playerId, $gamePlatformId);
            $params = [
                'vendor_id' => $this->vendor_id,
                'vendor_member_id' => $vendorMemberId,
            ];

            $this->callOddsDirectEndpoint('refreshToken', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


    /**
     * calls the Login push api end point of SABA Odds Direct
     *
     * ? https://github.com/Saba-sports/OddsDirectAPI/wiki/Login-API
     */
    public function sabaApiLogin($gamePlatformId){
        try {
            $vendorMemberId =  $this->game_provider_auth->getGameUsernameByPlayerId($this->playerId, $gamePlatformId);
            $params = [
                'vendor_id' => $this->vendor_id,
                'vendor_member_id' => $vendorMemberId,
            ];

            $this->callOddsDirectEndpoint('login', $params);

            $response = $this->getOddsDirectResponse();
        } catch (Exception $ex) {
            $response = $this->buildExceptionResponse($ex);
        } finally {
            $this->returnApiResponseByArray($response);
        }
    }


}