<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

class Vip_grade_report extends BaseModel {


	const GRADE_FAILED = 0;
	const GRADE_SUCCESS = 1;

    protected $tableName = "vip_grade_report";

    /**
	 * Get the lastest row by some condition.
	 *
	 * @param string|integer $playerId The player player_id.
	 * @param string $fromDate The start datetime for search.
	 * @param string $toDate The end datetime for search.
	 * @param string $changedGrade If need upgrade/downgrade condition else ignore the grade condition.
	 * Enumerated value: upgrade, downgrade and upgrade_or_downgrade.
	 * @param string $queryFieldname The fiels name in the WHERE clause, used for $fromDate and $toDate.
	 * @param boolean $join_newvipId_and_vipsettingcashbackrule For get the field,"vipSettingId" via the foreign key,"vip_grade_report.newvipId".
	 * @param array $excluded_pk_id_list The pk field, "id" will be excluded in where condition.
     *
	 * @return array The one row for result.
	 */
	public function queryLastGradeRecordRowBy( $playerId // #1
        , $fromDate // #2
        , $toDate // #3
        , $changedGrade = null // #4
        , $queryFieldname = 'pgrm_end_time' // #5
        , $join_newvipId_and_vipsettingcashbackrule = false // #6
        , $excluded_pk_id_list = [] // #7
    ){

        $this->db->from($this->tableName)
            ->where('player_id', $playerId)
            ->where($queryFieldname. ' >=', $fromDate)
            ->where($queryFieldname. ' <=', $toDate)
            ->where('status', self::GRADE_SUCCESS)
            ->order_by($queryFieldname, 'desc')
            ->order_by('id', 'desc')
            ->limit(1);

        switch( strtolower($changedGrade) ){
            case'upgrade':
                $this->db->where('level_from < level_to');
            break;

            case'downgrade':
                $this->db->where('level_from > level_to');
            break;

            case'upgrade_or_downgrade':
                $this->db->where('level_from <> level_to');
            break;

            default:
            break;
        }

        if($join_newvipId_and_vipsettingcashbackrule){
            $this->db->select('vip_grade_report.*, new_vipsettingcashbackrule.vipSettingId as new_vipSettingId');
            $this->db->join('vipsettingcashbackrule AS new_vipsettingcashbackrule', 'vip_grade_report.newvipId = new_vipsettingcashbackrule.vipsettingcashbackruleId', 'left');
        }

        if( ! empty($excluded_pk_id_list) ){
            $this->db->where_not_in('id', $excluded_pk_id_list);
        }

        $rowArray = $this->runOneRowArray();
        $this->utils->debug_log('OGP-32917.72.last_query:', $this->db->last_query() );

        return $rowArray;
    } // EOF queryLastGradeRecordRowBy

} // EOF Vip_grade_report
