<?php

trait player_api_mock_module{

	protected function _mockDataForPlayerapi($uri=null){
		if(empty($uri)){
			$uri=$this->current_uri;
		}
		switch ($uri) {
			case '/site_config/languages':
				return [
					"en-US", "zh-CN"
				];
				break;
			case '/site_config/currencies':
				return [
					['currency'=>'CNY', 'active'=>true],
				];
				break;
			case '/cms/content-store/all':
				return [
					"template"=>[
						"type"=> 2,
						"content"=> "1"
					],
					'bannerMobile'=>[
						'type'=>1,
						'content'=>"{}"
					],
					'customerService'=>[
						'type'=>1,
						'content'=>"{'en':{'customLink':'','customCode':'','Line':'','Skype':'','Telegram':'','WhatsApp':'','Wechat':'','QQ':''},'zh':{'customLink':'','customCode':'','Line':'','Skype':'','Telegram':'','WhatsApp':'','Wechat':'','QQ':''},'vi':{'customLink':'','customCode':'','Line':'','Skype':'','Telegram':'','WhatsApp':'','Wechat':'','QQ':''},'th':{'customLink':'','customCode':'','Line':'','Skype':'','Telegram':'','WhatsApp':'','Wechat':'','QQ':''},'hi':{'customLink':'','customCode':'','Line':'','Skype':'','Telegram':'','WhatsApp':'','Wechat':'','QQ':''}}"
					],
					'module'=>[
						"type"=> 1,
						"content"=> "",
					],
					'module'=>[
						'type'=> 1,
						'content'=> ''
					],
					'banner'=>[
						'type'=>1,
						'content'=>"{}"
					],
					'moduleMobile'=>[
						"type"=> 1,
						"content"=> "",
					],
					'moduleMobile'=>[
						'type'=> 1,
						'content'=> ''
					],
					'info'=>[
						'type'=>1,
						'content'=>"{'logo':'','slogan':'','title':'','template':1}"
					],
				];
				break;
			case '/site_properties/site-info':
				return [
					'title'=>'', 'logo'=>'', 'slogan'=>'', 'template'=>1,
				];
				break;
			case '/cms/announcements':
				return [
					"total"=> 0,
					"list"=> [],
					"pageNum"=> 1,
					"pageSize"=> 0,
					"size"=> 0,
					"startRow"=> 0,
					"endRow"=> 0,
					"pages"=> 0,
					"prePage"=> 0,
					"nextPage"=> 0,
					"isFirstPage"=> true,
					"isLastPage"=> true,
					"hasPreviousPage"=> false,
					"hasNextPage"=> false,
					"navigatePages"=> 8,
					"navigatepageNums"=> [],
					"navigateFirstPage"=> 0,
					"navigateLastPage"=> 0
				];
				break;
			case '/site_config/game-types':
				return [
					[
						"id"=> 1,
						"name"=> "电子游戏",
						"gameCount"=> 3384,
						"activeGameCount"=> 2806,
						"gameApis"=> [
							[
							  "id"=> 19,
							  "name"=> "CQ9电子",
							  "code"=> "T1GCQ9",
							  "currency"=> "VND",
							  "logoUrl"=> "http://gamegateway.t1t.games/includes/images/game-vendor-logo/180x140/cq9-logo.png",
							  "lobbyLogoUrl"=> "http://gamegateway.t1t.games/includes/images/game-vendor-logo/lobby-logo/2ft412/template-1/1/T1GCQ9.png"
							],
							[
							  "id"=> 25,
							  "name"=> "PT电子",
							  "code"=> "T1GPT",
							  "currency"=> "VND",
							  "logoUrl"=> "http://gamegateway.t1t.games/includes/images/game-vendor-logo/180x140/playtech-logo.png",
							  "lobbyLogoUrl"=> "http://gamegateway.t1t.games/includes/images/game-vendor-logo/lobby-logo/2ft412/template-1/1/T1GPT.png"
							],
						]
					],
					[
						"id"=> 2,
						"name"=> "真人游戏",
						"gameCount"=> 105,
						"activeGameCount"=> 99,
						"gameApis"=> [
							[
							  "id"=> 27,
							  "name"=> "PP视讯",
							  "code"=> "T1GPRAGMATICPLAY",
							  "currency"=> "VND",
							  "logoUrl"=> "http://gamegateway.t1t.games/includes/images/game-vendor-logo/180x140/pragmatic-logo.png",
							  "lobbyLogoUrl"=> "http://gamegateway.t1t.games/includes/images/game-vendor-logo/lobby-logo/2ft412/template-1/2/T1GPRAGMATICPLAY.png"
							],
							[
							  "id"=> 28,
							  "name"=> "MG视讯",
							  "code"=> "T1GMGPLUS",
							  "currency"=> "VND",
							  "logoUrl"=> "http://gamegateway.t1t.games/includes/images/game-vendor-logo/180x140/microgaming-logo.png",
							  "lobbyLogoUrl"=> "http://gamegateway.t1t.games/includes/images/game-vendor-logo/lobby-logo/2ft412/template-1/2/T1GMGPLUS.png"
							]
						]
					],
					[
					  "id"=> 8,
					  "name"=> "金融",
					  "gameCount"=> 0,
					  "activeGameCount"=> 0,
					  "gameApis"=> []
					],
				];
				break;
			case '/cashier/payment-settings':
				return [
					"withdrawal" => [
					  "minPerTrans" => 10,
					  "maxPerTrans" => 10000,
					  "dailyLimit" => 100000
					],
					"deposit" => [
					  "uploadFileMaxSize" => 5000
					],
					"bankAccount" => [
					  "enabledIfscCode" => true,
					  "enabledPhoneNumber" => true,
					  "enabledProvince" => true,
					  "enabledBankBranch" => true
					]
				];
				break;
			case '/player/info':
				return [
					'agent'=> false,
					'currency'=>'CNY',
					'emailVerified'=>true,
					'group'=>[
						'description'=>'VIP1',
						'id'=>1,
						'imageUrl'=>'',
						'name'=>'VIP1',
					],
					'id'=>1,
					'lastLogin'=>[
						'ip'=>'127.0.0.1',
						'location'=>'CN',
						'time'=>'2021-07-23T09:43:43.496Z',
					],
					'loginCampaign'=>null,
					'progress'=> [
						'bonusAmount'=>0,
						'bonusStatus'=>0,
						'playerLoginCount'=>0,
						'requiredLoginDay'=>0,
					],
					'rules'=>null,
					'startTime'=>'2021-07-23T09:43:43.496Z',
					'startTimeLocal'=>'2021-07-23T09:43:43.496Z',
					'taskType'=>'0',
					'totalReleasedBonus'=>0,
					'type'=>'1',
					'uid'=>'live_stable_prod-integrate-new-player-center-ui',
					'updatedAt'=>'2021-07-23T09:43:43.496Z'
				];
				break;
			case '/wallets':
				return [
					[
					  "gameApiId"=> 0,
					  "gameApiName"=> null,
					  "currency"=> "CNY",
					  "balance"=> 99964022.31,
					  "pending"=> 300,
					  "dirty"=> true,
					  "lastSync"=> "1970-01-01T00:00:01Z",
					  "allowDecimalTransfer"=> true,
					  "status"=> null,
					],
					[
					  "gameApiId"=> 19,
					  "gameApiName"=> "T1GCQ9 VND",
					  "currency"=> "CNY",
					  "balance"=> 0,
					  "pending"=> 0,
					  "dirty"=> true,
					  "lastSync"=> "2021-08-09T11:16:22.91Z",
					  "allowDecimalTransfer"=> true,
					  "status"=> 1,
					],
					[
					  "gameApiId"=> 25,
					  "gameApiName"=> "T1GPT VND",
					  "currency"=> "CNY",
					  "balance"=> 0,
					  "pending"=> 0,
					  "dirty"=> true,
					  "lastSync"=> "2021-08-09T11:16:22.606Z",
					  "allowDecimalTransfer"=> true,
					  "status"=> 1,
					],
					[
					  "gameApiId"=> 27,
					  "gameApiName"=> "T1GPRAGMATICPLAY VND",
					  "currency"=> "CNY",
					  "balance"=> 0,
					  "pending"=> 0,
					  "dirty"=> true,
					  "lastSync"=> "2021-08-09T11:16:22.612Z",
					  "allowDecimalTransfer"=> true,
					  "status"=> 1,
					],
				];
				break;
			case '/player/bank-accounts':
				return [
					[
					  "id"=> 2,
					  "bankId"=> 1001,
					  "accountHolderName"=> "Li Cheng",
					  "accountNumber"=> "2222000033332222",
					  "bankBranch"=> "広東分行",
					  "defaultAccount"=> true,
					],
					[
					  "id"=> 3,
					  "bankId"=> 1001,
					  "accountHolderName"=> "Li Cheng",
					  "accountNumber"=> "3333111122223333",
					  "bankBranch"=> "北京分行",
					  "defaultAccount"=> false,
					],
					[
					  "id"=> 4,
					  "bankId"=> 1001,
					  "accountHolderName"=> "Li Cheng",
					  "accountNumber"=> "4444222255556666",
					  "bankBranch"=> "上海分行",
					  "defaultAccount"=> false,
					]
				];
				break;
			case '/campaigns':
				return [
				[
				  "uid"=> "11-1",
				  "id"=> 1,
				  "type"=> 11,
				  "taskType"=> null,
				  "currency"=> "CNY",
				  "name"=> "Deposit 100 get 180",
				  "content"=> "Deposit 100 get 180，limited 1st deposit",
				  "bannerUrlMobile"=> "/promo_banner/deposit_en-1.png",
				  "bannerUrl"=> "/promo_banner/deposit_en-1.png",
				  "startTime"=> "2021-07-20T14:28:43Z",
				  "endTime"=> "2021-08-19T17:28:43Z",
				  "effectiveStartTime"=> "2021-07-20T14:28:43Z",
				  "effectiveEndTime"=> null,
				  "budget"=> 1000,
				  "allowSameIp"=> true,
				  "totalReleasedBonus"=> 180,
				  "createdAt"=> "2021-07-20T17:28:43.672Z",
				  "updatedAt"=> "2021-07-20T17:28:43.672Z",
				  "players"=> [],
				  "campaignRepeatSetting"=> [
					"bonusStartTime"=> "2021-07-20T14:28:43Z",
					"repeatPeriod"=> 0,
					"periodsPerCycle"=> 1,
					"repeatOn"=> null
				  ],
				  "lastBonusId"=> null,
				  "hasMoreBonus"=> true,
				  "startTimeLocal"=> "2021-07-20T14:28:43",
				  "effectiveStartTimeLocal"=> null,
				  "playerTags"=> [],
				  "playerGroups"=> [],
				  "denyPlayerTags"=> [],
				  "denyPlayerGroups"=> [],
				],
				];
				break;
			case '/promotions/cashback-setting':
				return [
					"withdrawConditionCalculationType"=> 1,
					"withdrawConditionBonusMultiplier"=> 10,
					"withdrawConditionDepositMultiplier"=> 0,
					"withdrawConditionAmount"=> null,
					"withdrawConditionDepositCalculationType"=> 0,
					"withdrawConditionMinDeposit"=> null,
					"executionType"=> 1,
					"minBonusAmount"=> [
					  [
						"currency"=> "CNY",
						"amount"=> 100
					  ],
					  [
						"currency"=> "USD",
						"amount"=> 20
					  ]
					],
					"maxBonusAmount"=> [
					  [
						"currency"=> "CNY",
						"amount"=> 10000
					  ],
					  [
						"currency"=> "USD",
						"amount"=> 2000
					  ]
					],
					"enabled"=> true,
					"dailyCashbackStartTime"=> "2021-08-08T00:00:00Z",
					"nextCalculationTime"=> "2021-08-10T08:30:00Z"
				];
				break;
			case '/bonuses/cashback/stats':
				return [
					"totalBet"=> 0,
					"receivedCashback"=> 105,
					"receivableCashback"=> 92.55,
					"receivedCashbackRefAmount"=> 105000,
					"receivableCashbackRefAmount"=> 2051,
					"nextCalculationDate"=> "2021-08-10T08:30:00Z",
					"receivable"=> false
				];
				break;
			case '/game/bets/latest':
				$mockDataJson = '{"code":20000,"data":{"game_logs":[{"playerUsername":"t****1dev","gamePlatformId":"6235","gameUniqueId":"1","uniqueId":"mock-gbl-01","gameName":"Baby Monkey","betTime":"2023-04-18 12:26:30","payoutTime":"2023-04-18 12:26:45","realBetAmount":"5.05","payoutAmount":"0","resultAmount":"-5.05","multiplier":"-","currency":"BRL"},{"playerUsername":"t****adef","gamePlatformId":"6235","gameUniqueId":"41","uniqueId":"mock-gbl-02","gameName":"Break Away","betTime":"2023-04-18 12:25:30","payoutTime":"2023-04-18 12:25:35","realBetAmount":"6.5","payoutAmount":"3.5","resultAmount":"-3","multiplier":"0.54","currency":"BRL"},{"playerUsername":"t****aer","gamePlatformId":"6235","gameUniqueId":"2003","uniqueId":"mock-gbl-03","gameName":"Cai Shen","betTime":"2023-04-18 12:25:00","payoutTime":"2023-04-18 12:25:15","realBetAmount":"3.05","payoutAmount":"0","resultAmount":"-3.05","multiplier":"-","currency":"BRL"},{"playerUsername":"a****sert","gamePlatformId":"6235","gameUniqueId":"40","uniqueId":"mock-gbl-04","gameName":"Candy Pop","betTime":"2023-04-18 12:24:45","payoutTime":"2023-04-18 12:24:50","realBetAmount":"6.45","payoutAmount":"0","resultAmount":"-6.45","multiplier":"-","currency":"BRL"},{"playerUsername":"t****swer","gamePlatformId":"6235","gameUniqueId":"27","uniqueId":"mock-gbl-05","gameName":"Captain’s Treasure","betTime":"2023-04-18 12:24:30","payoutTime":"2023-04-18 12:24:45","realBetAmount":"5.05","payoutAmount":"0","resultAmount":"-5.05","multiplier":"-","currency":"BRL"}]},"version":"3.01","request_id":"c7c04ffdc2c2edc35b5b774580e2b71a","server_time":"2023-04-18 12:22:48","cost_ms":5.9700012207031,"external_request_id":false}';
				$mockDataOutput = json_decode($mockDataJson, true);
				return $mockDataOutput;
				break;
			case '/game/rollers/high':
				$mockDataJson = '{"code":20000,"data":{"game_logs":[{"playerUsername":"t****1dev","gamePlatformId":"6235","gameUniqueId":"1","uniqueId":"mock-grh-01","gameName":"Baby Monkey","betTime":"2023-04-18 12:08:00","payoutTime":"2023-04-18 12:08:30","realBetAmount":"5.70","payoutAmount":"10.01","resultAmount":"4.31","multiplier":"1.75","currency":"BRL"},{"playerUsername":"t****aden","gamePlatformId":"6235","gameUniqueId":"11","uniqueId":"mock-grh-02","gameName":"Alliance","betTime":"2023-04-18 12:07:59","payoutTime":"2023-04-18 12:08:29","realBetAmount":"5.00","payoutAmount":"10.00","resultAmount":"5.00","multiplier":"2.00","currency":"BRL"},{"playerUsername":"a****mith","gamePlatformId":"6235","gameUniqueId":"19","uniqueId":"mock-grh-03","gameName":"Always Fa","betTime":"2023-04-18 12:06:59","payoutTime":"2023-04-18 12:07:31","realBetAmount":"5.05","payoutAmount":"11.00","resultAmount":"5.95","multiplier":"0.2","currency":"BRL"},{"playerUsername":"c****keno","gamePlatformId":"6235","gameUniqueId":"41","uniqueId":"mock-grh-04","gameName":"Break Away","betTime":"2023-04-18 12:05:59","payoutTime":"2023-04-18 12:06:31","realBetAmount":"3.00","payoutAmount":"15.45","resultAmount":"12.45","multiplier":"5.15","currency":"BRL"},{"playerUsername":"d****kwon","gamePlatformId":"6235","gameUniqueId":"41","uniqueId":"mock-grh-05","gameName":"Break Away","betTime":"2023-04-18 12:05:00","payoutTime":"2023-04-18 12:05:31","realBetAmount":"4.15","payoutAmount":"15.45","resultAmount":"10.95","multiplier":"3.72","currency":"BRL"}]},"version":"3.01","request_id":"078c1e1aea403fbc91744a21a54be537","server_time":"2023-04-18 12:03:46","cost_ms":4.1379928588867,"external_request_id":false}';
				$mockDataOutput = json_decode($mockDataJson, true);
				return $mockDataOutput;
				break;
			case '/game/favorite/list':
				return [
					'totalCount'=>0,
					'list'=>[
						[
							"gamePlatformId"=> 232,  //pp
							"gameUniqueId"=> "401",
							"gameName"=> "Baccarat A",
							"tags"=>['live_dealer'],
							"onlineCount"=>0,
							"bonusTag"=>"",
							"pcEnable"=>true,
							"mobileEnable"=>true,
							"demoEnable"=>true,
							"gameImgUrl"=>"",
							"playerImgUrl"=>null,
						],
						[
							"gamePlatformId"=> 232,  //pp
							"gameUniqueId"=> "511",
							"gameName"=> "Blackjack Azure A",
							"tags"=>['live_dealer'],
							"onlineCount"=>0,
							"bonusTag"=>"",
							"pcEnable"=>true,
							"mobileEnable"=>true,
							"demoEnable"=>true,
							"gameImgUrl"=>"",
							"playerImgUrl"=>null,
						],
						[
							"gamePlatformId"=> 232,  //pp
							"gameUniqueId"=> "511",
							"gameName"=> "Blackjack Azure B",
							"tags"=>['live_dealer'],
							"onlineCount"=>0,
							"bonusTag"=>"",
							"pcEnable"=>true,
							"mobileEnable"=>true,
							"demoEnable"=>true,
							"gameImgUrl"=>"",
							"playerImgUrl"=>null,
						],
					],
				];
				break;
			case '/missions/list':
				return [
					"list"=> [
						[
							"currency"=> "BRL",
							"subtype" => "registration",
							"promoId"=> "1",
							"prize" => "1",
							"promoName"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission-Registration"
								]
							],
							"promoDescription"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission-Registration"
								]
							],
							"promoThumbnail"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission-Registration"
								]
							],
							"promoCode"=> "HjkK82H",
							"currentTotal"=> 1,
							"threshHold"=> 1,
							"status"=> 3
						],
						[
							"currency"=> "BRL",
							"subtype" => "friendReferral",
							"promoId"=> "2",
							"prize" => "1",
							"promoName"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Invitation"
								]
							],
							"promoDescription"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Invitation"
								]
							],
							"promoThumbnail"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Invitation"
								]
							],
							"promoCode"=> "HjkK82H",
							"currentTotal"=> 0,
							"threshHold"=> 1,
							"status"=> 1
						],
						[
							"currency"=> "BRL",
							"subtype" => "friendReferral",
							"promoId"=> "3",
							"prize" => "1",
							"promoName"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Invitation"
								]
							],
							"promoDescription"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Invitation"
								]
							],
							"promoThumbnail"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Invitation"
								]
							],
							"promoCode"=> "HjkK82H",
							"currentTotal"=> 1,
							"threshHold"=> 1,
							"status"=> 3
						],
						[
							"currency"=> "BRL",
							"subtype" => "profile",
							"promoId"=> "4",
							"prize" => "1",
							"promoName"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission-Bind CPF"
								]
							],
							"promoDescription"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission-Bind CPF"
								]
							],
							"promoThumbnail"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission-Bind CPF"
								]
							],
							"promoCode"=> "HjkK82H",
							"currentTotal"=> 0,
							"threshHold"=> 1,
							"status"=> 1
						],
						[
							"currency"=> "BRL",
							"subtype" => "profile",
							"promoId"=> "5",
							"prize" => "1",
							"promoName"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- Bind Name"
								]
							],
							"promoDescription"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- Bind Name"
								]
							],
							"promoThumbnail"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- Bind Name"
								]
							],
							"promoCode"=> "HjkK82H",
							"currentTotal"=> 1,
							"threshHold"=> 1,
							"status"=> 2
						],
						[
							"currency"=> "BRL",
							"subtype" => "profile",
							"promoId"=> "6",
							"prize" => "1",
							"promoName"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission-Bind Email"
								]
							],
							"promoDescription"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission-Bind Email"
								]
							],
							"promoThumbnail"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission-Bind Email"
								]
							],
							"promoCode"=> "HjkK82H",
							"currentTotal"=> 1,
							"threshHold"=> 1,
							"status"=> 3
						],
						[
							"currency"=> "BRL",
							"subtype" => "profile",
							"promoId"=> "7",
							"prize" => "1",
							"promoName"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- Bind Phone"
								]
							],
							"promoDescription"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- Bind Phone"
								]
							],
							"promoThumbnail"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- Bind Phone"
								]
							],
							"promoCode"=> "HjkK82H",
							"currentTotal"=> 1,
							"threshHold"=> 1,
							"status"=> 3
						],
						[
							"currency"=> "BRL",
							"subtype" => "deposit",
							"promoId"=> "8",
							"prize" => "1",
							"promoName"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Deposit"
								]
							],
							"promoDescription"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Deposit"
								]
							],
							"promoThumbnail"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Deposit"
								]
							],
							"promoCode"=> "HjkK82H",
							"currentTotal"=> 0,
							"threshHold"=> 50,
							"status"=> 1
						],
						[
							"currency"=> "BRL",
							"subtype" => "deposit",
							"promoId"=> "9",
							"prize" => "1",
							"promoName"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Deposit"
								]
							],
							"promoDescription"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Deposit"
								]
							],
							"promoThumbnail"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Deposit"
								]
							],
							"promoCode"=> "HjkK82H",
							"currentTotal"=> 20,
							"threshHold"=> 50,
							"status"=> 1
						],
						[
							"currency"=> "BRL",
							"subtype" => "bet",
							"promoId"=> "10",
							"prize" => "1",
							"promoName"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Bet"
								]
							],
							"promoDescription"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Bet"
								]
							],
							"promoThumbnail"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Bet"
								]
							],
							"promoCode"=> "HjkK82H",
							"currentTotal"=> 100,
							"threshHold"=> 100,
							"status"=> 2
						],
						[
							"currency"=> "BRL",
							"subtype" => "bet",
							"promoId"=> "11",
							"prize" => "1",
							"promoName"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Bet"
								]
							],
							"promoDescription"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Bet"
								]
							],
							"promoThumbnail"=> [
								[
									"lang"=> "pt-PT",
									"content"=> "New Player Mission- First Bet"
								]
							],
							"promoCode"=> "HjkK82H",
							"currentTotal"=> 10,
							"threshHold"=> 10,
							"status"=> 3
						]
					]
				];
				break;
		}

		if($this->utils->startsWith($uri, '/games/search')){
			return [
				[
					"gameNameId"=> 113892,
					"gameApiId"=> 28,
					"gameApiCode"=> "T1GMGPLUS",
					"gameCode"=> "SMG_solarWilds",
					"userEnabled"=> true,
					"status"=> 1,
					"gameTypeName"=> "电子游戏",
					"gameName"=> "太阳系百搭符号",
					"gameImgUrl"=> "http://www.gamegateway.t1t.games/includes/images/cn/microgaming/SMG_solarWilds.jpg",
					"featured"=> false,
					"releasedDate"=> null,
					"favorite"=> false,
					"channel"=> [
					  "web",
					  "mobile"
					]
				],
				[
					"gameNameId"=> 113887,
					"gameApiId"=> 28,
					"gameApiCode"=> "T1GMGPLUS",
					"gameCode"=> "SMG_silverSeas",
					"userEnabled"=> true,
					"status"=> 1,
					"gameTypeName"=> "电子游戏",
					"gameName"=> "银色之海",
					"gameImgUrl"=> "http://www.gamegateway.t1t.games/includes/images/cn/microgaming/SMG_silverSeas.jpg",
					"featured"=> false,
					"releasedDate"=> null,
					"favorite"=> false,
					"channel"=> [
					  "web",
					  "mobile"
					]
				],
			];
		}
		$this->utils->error_log('no mock data for', $uri);
		return [];
	}

}
