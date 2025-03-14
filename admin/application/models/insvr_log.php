<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

class Insvr_log extends BaseModel {

    protected $tableName = 'insvr_log';

    function __construct() {
		parent::__construct();
		$this->load->helper('date');
    }

    /**
     * Add a record
     *
     * @param array $params the fields of the table,"insvr_log".
     * @return void
     */
    public function add($params) {
        $this->load->model(['insvr_game_description_log']);

        $gameDescriptionIdList = [];
        if( ! empty($params['gameDescriptionIdList']) ){
            $gameDescriptionIdList = $params['gameDescriptionIdList'];
            // remove gameDescriptionIdList
            $params['gameDescriptionIdList'] = null;
            unset($params['gameDescriptionIdList']);
        }

        $nowForMysql = $this->utils->getNowForMysql();
        $data['created_at'] = $nowForMysql;
        $data['updated_at'] = $nowForMysql;
        $data = array_merge($data, $params);

        $insvr_log_id = $this->insertRow($data);

        if( ! empty($gameDescriptionIdList)
            &&  ! empty($insvr_log_id)
        ){
            $gamePlatformId = 0; // ignore
            foreach($gameDescriptionIdList as $gameDescriptionId){
                $gameDescriptionIdList = [$gameDescriptionId];
                $gameCodeList = [];
                $theGameCodes = $this->getGameCodesByGamePlatformIdAndDescriptionId($gamePlatformId, $gameDescriptionIdList, $gameCodeList);

                $insvr_game_description_data = [];
                $insvr_game_description_data['insvr_log_id'] = $insvr_log_id;
                $insvr_game_description_data['game_description_id'] = $theGameCodes[0]['game_description_id'];
                $insvr_game_description_data['game_code'] = $theGameCodes[0]['game_code'];
                $this->insvr_game_description_log->add($insvr_game_description_data);
            }

        }

        return $insvr_log_id;
    } // EOF add

    /**
	 * Update record by id
	 *
	 * @param integer $id
	 * @param array $data The fields for update.
	 * @return boolean|integer The affected_rows.
	 */
	public function update($id, $data = array() ) {
        $this->load->model(['insvr_game_description_log']);

        // detect the key,"gameDescriptionIdList" for add/delete insvr_game_description_log.
        $gameDescriptionIdList = [];
        if( ! empty($data['gameDescriptionIdList']) ){
            $gameDescriptionIdList = $data['gameDescriptionIdList'];
            // remove gameDescriptionIdList
            $data['gameDescriptionIdList'] = null;
            unset($data['gameDescriptionIdList']);
        }


        $nowForMysql = $this->utils->getNowForMysql();
        $data['updated_at'] = $nowForMysql;
        $affected_rows = $this->updateRow($id, $data);

        if( ! empty($gameDescriptionIdList) ){ // will rebuild
            // delete insvr_game_description_log data
            $insvr_log_id = $id;
            $this->insvr_game_description_log->deleteByInsvrLogId($insvr_log_id);

            // add insvr_game_description_log data
            $gamePlatformId = 0;
            $gameCodeList = [];
            foreach($gameDescriptionIdList as $gameDescriptionId){
                $gameDescriptionIdList = [$gameDescriptionId];

                $theGameCodes = $this->getGameCodesByGamePlatformIdAndDescriptionId($gamePlatformId, $gameDescriptionIdList, $gameCodeList);

                $insvr_game_description_data = [];
                $insvr_game_description_data['insvr_log_id'] = $insvr_log_id;
                $insvr_game_description_data['game_description_id'] = $theGameCodes[0]['game_description_id'];
                $insvr_game_description_data['game_code'] = $theGameCodes[0]['game_code'];
                $this->insvr_game_description_log->add($insvr_game_description_data);
            }

        } // EOF if( ! empty($gameDescriptionIdList) )

        return $affected_rows;

    } // EOF update

    /**
	 * Delete a record by id(P.K.)
	 *
	 * @param integer $id The id field.
	 * @return boolean If true means delete the record completed else false means failed.
	 */
	public function delete($id){
        $this->load->model(['insvr_game_description_log']);

        $insvr_log_id = $id;
        $this->insvr_game_description_log->deleteByInsvrLogId($insvr_log_id);

		$this->db->where('id', $id);
		return $this->runRealDelete($this->tableName);
    } // EOF delete


    function getGameCodesByGamePlatformIdAndDescriptionId($gamePlatformId = 0, $gameDescriptionIdList = [] , $gameCodeList = []){
        $this->load->model(['game_description_model']);
        if( ! empty($gamePlatformId ) ){
            $whereStr = 'game_description.game_platform_id = '. $gamePlatformId;
        }else{
            $whereStr = 'game_description.game_platform_id > 0';
        }

		if( ! empty($gameDescriptionIdList) ){
			$gameDescriptionIdList = array_filter($gameDescriptionIdList);
			$whereStr .= ' AND ';
			$whereStr .= 'id in("'. implode('","', $gameDescriptionIdList). '")';
		}
		if( ! empty($gameCodeList) ){
			$gameCodeList = array_filter($gameCodeList);
			$whereStr .= ' AND ';
			$whereStr .= 'game_code in("'. implode('","', $gameCodeList). '")';
		}
		$gameCodeList = $this->game_description_model->getGameByQuery('game_code, id as game_description_id',$whereStr);
		return $gameCodeList;
    }

    /**
     * Check the data exist by PlayerPromoId(s)
     *
     * @param array $thePlayerPromoIdList
     * @return array The rows of result.
     */
    function checkExistsByPlayerPromoIdList($thePlayerPromoIdList){
        $this->db->select('playerpromo_id, count(id) as counter');
        $this->db->where_in('playerpromo_id', $thePlayerPromoIdList);
        $this->db->from($this->tableName);
        $this->db->group_by('playerpromo_id');
		$rows = $this->transactions->runMultipleRowArray();

        return $rows;
    } // EOF checkExistsByPlayerPromoIdList


} /// EOF Insvr_log