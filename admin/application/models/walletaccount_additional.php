<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';


class Walletaccount_additional extends BaseModel {
	protected $tableName = 'walletaccount_additional';

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Get a record by walletAccountId(F.K.)
	 *
	 * @param integer $id The id field.
	 * @param boolean $pickFromExtra If true thats mean to parse and merge extra json string into the result.
	 * @return array The fields of the record.
	 */
	public function getDetailByWalletAccountId($walletAccountId) {
		$this->db->select('*')
				->from($this->tableName)
				->where('walletAccountId', $walletAccountId);

		$result = $this->runOneRowArray();

		return $result;
	}// EOF getDetailByWalletAccountId

	/**
	 * Sync fields into Additional table
	 * Update or insert data by sale_order_id.
	 *
	 *
	 * @param integer $sale_order_id The field, sale_order_id F.K. to  sale_order.id
	 * @param array $data The field-value array.
	 * @return integer|null The return of updateOrInsertRowByUniqueField().
	 */
	public function syncToAdditionalByWalletAccountId($walletAccountId, $data){
		$uniqueField='walletAccountId';
		$data['walletAccountId'] = $walletAccountId; // F.K. sale_order.id
		$id = $this->updateOrInsertRowByUniqueField( $this->tableName // #1
			, $data // #2
			, function(&$data, $id){
				if( empty($id) ) {
					// will insert
				}else{
					// will update
				}
			} // EOF preprocess // #3
			, $uniqueField // #4
		);
		if(empty($id)){
			$this->utils->error_log('update or insert failed. data:', $data, 'walletAccountId:', $walletAccountId);
		}
		return $id;
	}// EOF syncToAdditionalByWalletAccountId

	/**
     * Add a record
     *
     * @param array $params the fields of the table,"sale_orders_additional".
     * @return void
     */
    public function add($params) {
		$data = [];
        // $nowForMysql = $this->utils->getNowForMysql();
        // $data['created_at'] = $nowForMysql;
        // $data['updated_at'] = $nowForMysql;
        $data = array_merge($data, $params);
        return $this->insertRow($data);
    } // EOF add

    /**
     * Update record by id
     *
     * @param integer $id
     * @param array $data The fields for update.
     * @return boolean|integer The affected_rows.
     */
    public function update($id, $data = array() ) {
		$data = [];
        // $nowForMysql = $this->utils->getNowForMysql();
        // $data['updated_at'] = $nowForMysql;
        return $this->updateRow($id, $data);
    }// EOF update

	/** @todo
	 * Delete a record by id(P.K.)
	 *
	 * @param integer $id The id field.
	 * @return boolean Return true means delete the record completed else false means failed.
	 */
	public function delete($id){
		$this->db->where('id', $id);
		return $this->runRealDelete($this->tableName);
	} // EOF delete

	/**
	 * Get a record by id(P.K.)
	 *
	 * @param integer $id The id field.
	 * @return array The fields of the record.
	 */
	public function getDetailById($id) {
		$this->db->select('*')
				->from($this->tableName)
				->where('id', $id);

		$result = $this->runOneRowArray();

		return $result;
	}// EOF getDetailById

} // EOF Walletaccount_additional
