<?php
require_once dirname(__FILE__) . '/abstract_payment_api_external_deposit.php';

/**
 *
 * POST parameters
 *
 * @param string $api api type: transfer
 * @param string $order_id order id, any string , less than 32
 * @param string $apply_site source site (fn139)
 * @param string $apply_username source username
 * @param string $apply_type type of source (birdegg)
 * @param integer $num balance
 * @param string $receive_username target username (player's username)
 * @param stirng $receive_type target type (main_wallet)
 * @param string $time timestamp , format: same with time()
 * @param string $token sign of parameters
 *
 * Web Return, json format
 *
 * {"state": , "errorNum": , "info" }
 *
 * state: "succeed"/"error"
 * errorNum:
 * 11: wrong api type
 * 12: wrong ip
 * 13: invalid site
 * 14: invalid token
 * 15: timeout
 * 20: doesn't exist username
 * 21: duplicate order id
 * 31: invalid apply type
 * 32: invalid receive type
 * 33: invalid balance
 * 40: "order doesn't exist",
 * 41: "order failed",
 * 50: unknown error
 *
 * info: any error message
 *
 */
class Payment_api_fn139 extends Abstract_payment_api_external_deposit{

	const API_TYPES=['transfer', 'confirm'];
	const AVAILABLE_SITES=['fn139'];
	const AVAILABLE_APPLY_TYPES=['birdegg'];
	const AVAILABLE_RECEIVE_TYPES=['main_wallet'];
	const DEFAULT_AMOUNT_RATE=1;

	const ERROR_MESSAGES=[
		11=> 'wrong api type',
 		12=> 'wrong ip',
 		13=> 'invalid site',
		14=> 'invalid token',
		15=> 'timeout',
		20=> "doesn't exist username",
		21=> 'duplicate order id',
		31=> 'invalid apply type',
		32=> 'invalid receive type',
		33=> 'invalid balance',
		40=> "order doesn't exist",
		41=> "order failed",
		50=> 'unknown error',
	];
	public function __construct($params = null) {
		parent::__construct($params);

		$this->available_sites= $this->getSystemInfo('available_sites', self::AVAILABLE_SITES);
		$this->api_types= $this->getSystemInfo('api_types', self::API_TYPES);
		$this->available_apply_types= $this->getSystemInfo('available_apply_types', self::AVAILABLE_APPLY_TYPES);
		$this->available_receive_types= $this->getSystemInfo('available_receive_types', self::AVAILABLE_RECEIVE_TYPES);
		$this->amount_rate= $this->getSystemInfo('amount_rate', self::DEFAULT_AMOUNT_RATE);
	}

	public function getPlatformCode(){
		return FN139_PAYMENT_API;
	}

	public function getPrefix(){
		return 'fn139';
	}

	public function validateCallbackParameters($params){

		$processed_info=[];
		$errorNum=0;
		$message=null;
		$process_by_api=true;
		$api_type=@$params['api'];
		$success= in_array($api_type, $this->api_types);
		if(!$success){
			$errorNum=11;
		}

		//white ip
		if($success){
			$success=$this->validateWhiteIp($this->getClientIP());
			if(!$success){
				$errorNum=12;
			}
		}
		//validate timeout
		if($success){
			$time=intval(@$params['time']);
			//marked by spencer.kuo 2017.04.18
			//$success=$this->timeout+$time >= time();
			//add by spencer.kuo 2017.04.18
			$success=$this->getTimeoutSecond()+$time >= time();
			if(!$success){
				$errorNum=15;
			}
		}

		if($api_type=='confirm'){

			if($success){
				//validate sign
				$success=$this->validateSign($params);
				if(!$success){
					$errorNum=14;
				}
			}
			//exists order
			if($success){
				$this->CI->load->model(['sale_order']);
				//marked by spencer.kuo 2017.04.18
				//$orderInfo=$this->CI->sale_order->getSaleOrderById(@$params['order_id']);
				//add by spencer.kuo 2017.04.18
				$orderInfo=$this->CI->sale_order->getSaleOrderByExternalOrderId(@$params['order_id']);
				$success= !!$orderInfo;
				if(!$success){
					$errorNum=40;
				}else{
					//check status
					$success=$orderInfo->status==Sale_order::STATUS_SETTLED;
					if(!$success){
						$errorNum=41;
					}
				}
			}

			$process_by_api=false;

		}elseif($api_type=='transfer'){
			//transfer
			//validate apply_site
			if($success){
				$success=in_array(@$params['apply_site'], $this->available_sites);
				if(!$success){
					$errorNum=13;
				}
			}

			if($success){
				//validate sign
				$success=$this->validateSign($params);
				if(!$success){
					$errorNum=14;
				}
			}

			// if($success){
			// 	$success=strlen($params['order_id'])<=32;
			// 	if($success){
			// 		$processed_info[self::FIELD_EXTERNAL_ORDER_ID]=$params['order_id'];
			// 	}else{
			// 		$errorNum=11;
			// 	}
			// }
			//validate duplicate order id
			if($success){
				$this->CI->load->model(['sale_order']);
				$success=!$this->CI->sale_order->existsSaleOrderByExternalOrderId(@$params['order_id']);
				if(!$success){
					$errorNum=21;
				}else{
					$processed_info[self::FIELD_EXTERNAL_ORDER_ID]=@$params['order_id'];
				}
			}
			//validate receive_type
			if($success){
				$success=in_array(@$params['receive_type'], $this->available_receive_types);
				if(!$success){
					$errorNum=32;
				}
			}
			//validate receive_username
			if($success){
				$receive_username=@$params['receive_username'];
				$this->CI->load->model(['player_model']);
				//search player
				$playerId=$this->CI->player_model->getPlayerIdByUsername($receive_username);
				$success=!empty($playerId);
				if($success){
					$processed_info[self::FIELD_PLAYER_ID]=$playerId;
				}else{
					$errorNum=20;
				}
			}
			//validate apply_type
			if($success){
				$success=in_array(@$params['apply_type'], $this->available_apply_types);
				if(!$success){
					$errorNum=31;
				}
			}
			//validate num
			if($success){
				$amount=floatval(@$params['num']);
				$success=$amount>0;
				if($success){
					$processed_info[self::FIELD_AMOUNT]=$amount * $this->amount_rate;
				}else{
					$errorNum=33;
				}
			}

			if($success){
				$process_by_api=true;
			}else{
				$process_by_api=false;
			}
		}

		if($errorNum>0){
			$message=self::ERROR_MESSAGES[$errorNum];
		}

		$return_result=['state'=> $success ? 'succeed' : 'error', 'errorNum'=>$errorNum, 'info'=>$message];

		$result=['success'=>$success, 'return_type'=>'json',
			'process_by_api' => $process_by_api,
			'return_result'=>$return_result,
			'processed_info'=>$processed_info];

		return $result;
	}

	public function validateSign($params){

		$token= @$params['token'];

		if($params['api']=='confirm'){

			$original=$this->key.$params['api'].$params['order_id'].$params['time'];

		}else{

			unset($params['token']);

			//sort params and append
			ksort($params, SORT_STRING);

			$values=array_values($params);

			$original=$this->key.implode('', $values);
		}

		$md5=md5($original);

		$this->CI->utils->debug_log('original', $original, 'md5', $md5,'token',$token);

		return $token==$md5;

	}

	public function callbackFromServer($orderId, $params){

		$processed_info=@$params['processed_info'];
		$player_promo_id = null;
		$subWalletId=null;
		$group_level_id=null;
		$playerId=@$processed_info['_playerId'];
		$amount=@$processed_info['_amount'];
		//call api to create order
		//write to sale_orders
		if(empty($orderId)){
			$orderId=$this->createSaleOrder($playerId, $amount, $player_promo_id, $params, $subWalletId, $group_level_id);
		}

		$response_result_id = parent::callbackFromServer($orderId, $params);

		$result=['success'=>false];

		$errorNum=0;

		if($orderId){
			$this->CI->load->model(['sale_order']);
			$controller=$this;
			$message=null;
			//approve
			// $success=$this->CI->sale_order->lockAndTransForPlayerBalance($playerId, function()
			// 	use ($controller, $orderId, $params, $response_result_id, $processed_info, &$message, &$errorNum){

				$controller->CI->sale_order->updateExternalInfo($orderId,
					@$processed_info['_external_order_id'], '',
					null, null, $response_result_id);

				$show_reason_to_player=false;
				$extra_info=['only_add_balance'=>true];
				$success=$controller->CI->sale_order->approveSaleOrder($orderId, 'from call api', $show_reason_to_player, $extra_info);
				if(!$success){
					$message=lang('Internal Error');
					$errorNum=50;
				}
			// 	return $success;
			// });


		}else{
			//internal error
			$success=false;
			$message=lang('Create Order Failed');
			$errorNum=50;
		}
		$return_result=['state'=> $success ? 'succeed' : 'error', 'errorNum'=>$errorNum, 'info'=>$message];
		$result['success']=$success;
		$result['return_result']=$return_result;
		$result['return_type']='json';

		return $result;

	}



}
