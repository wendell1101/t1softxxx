<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . "/../libraries/roulette/abstract_roulette_api.php";
require_once dirname(__FILE__) . '/base_model.php';

/**
 * Roulette_api_record
 *
 */
class Roulette_api_record extends BaseModel {
	protected $tableName = 'roulette_api_record';

    //region awardType
    // CASH_BONUS(1), ROULETTE_GIVEAWAY(2), FREESPIN(3)， PHYSICAL(4)
    const ROULETTE_AWARDTYPE_CASH_BONUS = 1;
    const ROULETTE_AWARDTYPE_ROULETTE_GIVEAWAY = 2;
    const ROULETTE_AWARDTYPE_FREESPIN = 3;
    const ROULETTE_AWARDTYPE_PHYSICAL = 4;
    const ROULETTE_AWARDTYPE_RANDOM = 5;

    // endregion


	const ROULETTE_NAME_TYPES = [
		'normal_1' => Abstract_roulette_api::NORMAL_API_1,
		'super_1' => Abstract_roulette_api::SUPER_API_1,
		'normal_2' => Abstract_roulette_api::NORMAL_API_2,
		'super_2' => Abstract_roulette_api::SUPER_API_2,
		'r25318' => Abstract_roulette_api::R25318_API,
		'r26255' => Abstract_roulette_api::R26255_API,
		'r26256' => Abstract_roulette_api::R26256_API,
		'r26755' => Abstract_roulette_api::R26755_API,
		'r26756' => Abstract_roulette_api::R26756_API,
		'r26871' => Abstract_roulette_api::R26871_API,
		'r26872' => Abstract_roulette_api::R26872_API,
		'r27831n' => Abstract_roulette_api::R27831N_API,
		'r27831s' => Abstract_roulette_api::R27831S_API,
		'r28024' => Abstract_roulette_api::R28024_API,
		'r28025' => Abstract_roulette_api::R28025_API,
		'r28561' => Abstract_roulette_api::R28561_API,
		'r28683' => Abstract_roulette_api::R28683_API,
		'r29620' => Abstract_roulette_api::R29620_API,
		'r29758' => Abstract_roulette_api::R29758_API,
		'r29757' => Abstract_roulette_api::R29757_API,
		'r30774' => Abstract_roulette_api::R30774_API,
		'r30970' => Abstract_roulette_api::R30970_API,
		'r31492' => Abstract_roulette_api::R31492_API,
		'r31682' => Abstract_roulette_api::R31682_API,
		'r31874' => Abstract_roulette_api::R31874_API,
		'r32439' => Abstract_roulette_api::R32439_API,
        'r33827_usd' => Abstract_roulette_api::R33827_USD_API,
        'r33827_php' => Abstract_roulette_api::R33827_PHP_API,
        'r33827_jpy' => Abstract_roulette_api::R33827_JPY_API,
	];

	public function __construct() {
		parent::__construct();
	}

	/**
	 * create roulette record
	 * @param  array  $rouletteRecordData
	 * @return res id
	 */
	public function add($rouletteRecordData) {
		$this->db->insert($this->tableName, $rouletteRecordData);
		return $this->db->insert_id();
	}

	/**
	 * get roulette List
	 * @param  string $start_date
	 * @param  string $end_date
	 * @param  int $roulette_type
	 * @param  int $playerId id
	 * @return array
	 */
	public function rouletteList($start_date, $end_date, $playerId = null, $roulette_type = null, $sort = 'DESC', $limit = 30) {
		$this->db->select('id,player_id,promo_cms_id,bonus_amount,created_at,type,notes');
		$this->db->from($this->tableName);

		if (!empty($playerId)) {
			$this->db->where('player_id', $playerId);
		}

		if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('created_at >=', $start_date)
                ->where('created_at <=', $end_date);
        }

		if (!empty($roulette_type)) {
			$this->db->where('type', $roulette_type);
		}

		$this->db->limit($limit);

		$this->db->order_by('created_at', $sort);
		return $this->runMultipleRowArray();
	}

	public function checkPrizeLimit($product_id, $start_date = null, $end_date = null, $limit = 0, $playerId = null){
		$this->db->from($this->tableName)->where('product_id', $product_id);
		if (!empty($start_date) && !empty($end_date)) {
			$this->db->where('created_at >=', $start_date)->where('created_at <=', $end_date);
		}

		if (!empty($playerId)) {
			$this->db->where('player_id', $playerId);
		}

		$res = $this->runMultipleRowArray();

		if (!empty($res)) {
			return count($res) >= $limit ? true : false;
		}
		return false;
	}

	public function countPlayerPromoRecordByCurrentDate($player_id, $promo_cms_id, $start_date, $end_date=null){
		$valid_date = $this->utils->formatDateForMysql(new \DateTime($start_date));
		$to_date = $end_date ? $this->utils->formatDateForMysql(new \DateTime($end_date)) : $this->utils->formatDateForMysql(new \DateTime());
		$dateTimeFrom = $valid_date . ' ' . Utils::FIRST_TIME;
		$dateTimeTo = $to_date . ' ' . Utils::LAST_TIME;
		// $this->db->select('playerpromo.playerpromoId');
		$this->db->select('CASE WHEN (roulette_api_record.valid_date) THEN roulette_api_record.valid_date ELSE DATE(playerpromo.dateApply) END as valid_date', false);
		$this->db->join('roulette_api_record', 'playerpromo.playerpromoId = roulette_api_record.player_promo_id', 'LEFT');
		$this->db->where('playerpromo.promoCmsSettingId', $promo_cms_id);
		$this->db->where('playerpromo.playerId', $player_id);
		// $this->db->where('(CASE WHEN (roulette_api_record.valid_date IS NULL) THEN DATE(playerpromo.dateApply) ELSE roulette_api_record.valid_date END) =', $valid_date);
		$this->db->where('playerpromo.dateApply >=', $dateTimeFrom);
		// $this->db->where('playerpromo.dateApply <=', $dateTimeTo);
		$this->db->having('valid_date', $valid_date);
		$query = $this->db->get('playerpromo');
		$results = $query->num_rows();
		$this->utils->debug_log("===countPlayerPromoRecordByCurrentDate=== results [$results]");
		return $results;
	}


	public function countPlayerPromoRecordByDateRange($player_id, $promo_cms_id, $start_date, $end_date=null){
		$valid_date = $this->utils->formatDateForMysql(new \DateTime($start_date));
		$to_date = $end_date ? $this->utils->formatDateForMysql(new \DateTime($end_date)) : $this->utils->formatDateForMysql(new \DateTime());
		$dateTimeFrom = $valid_date . ' ' . Utils::FIRST_TIME;
		$dateTimeTo = $to_date . ' ' . Utils::LAST_TIME;
		// $this->db->select('playerpromo.playerpromoId');
		$this->db->select('CASE WHEN (roulette_api_record.valid_date) THEN roulette_api_record.valid_date ELSE DATE(playerpromo.dateApply) END as valid_date', false);
		$this->db->join('roulette_api_record', 'playerpromo.playerpromoId = roulette_api_record.player_promo_id', 'LEFT');
		$this->db->where('playerpromo.promoCmsSettingId', $promo_cms_id);
		$this->db->where('playerpromo.playerId', $player_id);
		// $this->db->where('(CASE WHEN (roulette_api_record.valid_date IS NULL) THEN DATE(playerpromo.dateApply) ELSE roulette_api_record.valid_date END) =', $valid_date);
		$this->db->where('playerpromo.dateApply >=', $dateTimeFrom);
		$this->db->where('playerpromo.dateApply <=', $dateTimeTo);
		// $this->db->having('valid_date', $valid_date);
		$query = $this->db->get('playerpromo');
		$results = $query->num_rows();
		$this->utils->debug_log("===countPlayerPromoRecordByCurrentDate=== results [$results]");
		return $results;
	}

	/**
	 * detail: count deposit of a certain player
	 *
	 * @param int $playerId player_id
	 * @param int $platform_code type
	 * @param string $periodFrom created_at
	 * @param string $periodTo created_at
	 */
	public function countRouletteById($playerId, $platformCode, $periodFrom, $periodTo) {
		$this->db->select('count(id) as cnt', false)->from($this->tableName)
			->where('type', $platformCode)
			->where('player_id', $playerId)
			->where('created_at >=', $periodFrom)
			->where('created_at <=', $periodTo)
			->where('transaction_id IS NOT NULL');

		return $this->runOneRowOneField('cnt');
	}

	public function getLastRecord( $playerId, $roulette_type, $limit = 1, $periodFrom = null, $periodTo = null) {

		$this->db->select('*');
		$this->db->from($this->tableName);
		$this->db->where('type', $roulette_type)
		->where('player_id', $playerId);
		if ($periodFrom) {
			$this->db->where('created_at >=', $periodFrom);
		}
		if ($periodTo) {
			$this->db->where('created_at <=', $periodTo);
		}
		$this->db->order_by('created_at', 'DESC');
		$this->db->limit($limit);

		return $this->db->get()->row_array();
	}

	public function getPlayerRoulettePagination($playerId=null , $rouletteType=null, $from=null, $to=null, $limit=null, $page=null, $sort='DESC'){
        $result = $this->getDataWithAPIPagination($this->tableName, function() use($playerId, $rouletteType, $from, $to, $sort) {
            $this->db->select('id,player_id,bonus_amount,created_at,type,notes');

            if (!empty($playerId)) {
				$this->db->where('player_id', $playerId);
            }

            if ($rouletteType) {
                $this->db->where('type', $rouletteType);
            }

            if (!empty($from) && !empty($to)) {
                $this->db->where('created_at BETWEEN ' . $this->db->escape($from) . ' AND ' . $this->db->escape($to) . '');
            }

            $this->db->order_by('created_at', $sort);
        }, $limit, $page);
		return $result;
	}
}
