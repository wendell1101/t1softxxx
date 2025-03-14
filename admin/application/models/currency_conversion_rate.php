<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once dirname(__FILE__) . '/base_model.php';

/**
 * Currency_conversion_rate
 *
 * This model represents currency_conversion_rate data. It operates the following tables:
 * - currency_conversion_rate
 *
 * @category Player Management
 * @version 5.02.02
 * @copyright 2013-2022 tot
 * @author	Kaiser Dapar
 */

class Currency_conversion_rate extends BaseModel
{
    private $tableName='currency_conversion_rate';

    public function __construct(){
        parent::__construct();
    }

    public function getRateByTargetCurrency($target_currency, $db=null){
        if( empty($db) ){
            $db = $this->db;
        }
        $reRate = null;

        $db->select('rate');
        $db->from($this->tableName);
        $db->where('target_currency', $target_currency);
        $db->order_by('request_time', 'desc');
        $db->limit(1);

        $query = $db->get();
        $row = $query->row_array();
        $query->free_result();
        unset($query);

        if( ! empty($row) ){
            $reRate = $row['rate'];
        }
        return  $reRate;
    } // EOF getRateByTargetCurrency

}
