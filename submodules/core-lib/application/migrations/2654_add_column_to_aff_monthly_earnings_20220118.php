<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_aff_monthly_earnings_20220118 extends CI_Migration {
	private $tableName1 = 'aff_monthly_earnings';
    private $tableName2 = 'aff_daily_earnings';

    public function up() {
        $field1 = array(
            'total_commission_amount_by_fix_rate' => array(
                'type' => 'double',
                'default' => 0,
                'null' => true
            ),
        );

        $field2 = array(
            'total_commission_amount_by_fix_rate_without_sub_aff' => array(
                'type' => 'double',
                'default' => 0,
                'null' => true
            ),
        );

        if($this->utils->table_really_exists($this->tableName1)){
            if(!$this->db->field_exists('total_commission_amount_by_fix_rate', $this->tableName1)){
                $this->dbforge->add_column($this->tableName1, $field1);
            }
            if(!$this->db->field_exists('total_commission_amount_by_fix_rate_without_sub_aff', $this->tableName1)){
                $this->dbforge->add_column($this->tableName1, $field2);
            }
        }

        if($this->utils->table_really_exists($this->tableName2)){
            if(!$this->db->field_exists('total_commission_amount_by_fix_rate', $this->tableName2)){
                $this->dbforge->add_column($this->tableName2, $field1);
            }
            if(!$this->db->field_exists('total_commission_amount_by_fix_rate_without_sub_aff', $this->tableName2)){
                $this->dbforge->add_column($this->tableName2, $field2);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName1)){
            if($this->db->field_exists('total_commission_amount_by_fix_rate', $this->tableName1)){
                $this->dbforge->drop_column($this->tableName1, 'total_commission_amount_by_fix_rate');
            }
            if($this->db->field_exists('total_commission_amount_by_fix_rate_without_sub_aff', $this->tableName1)){
                $this->dbforge->drop_column($this->tableName1, 'total_commission_amount_by_fix_rate_without_sub_aff');
            }
        }

        if($this->utils->table_really_exists($this->tableName2)){
            if($this->db->field_exists('total_commission_amount_by_fix_rate', $this->tableName2)){
                $this->dbforge->drop_column($this->tableName2, 'total_commission_amount_by_fix_rate');
            }
            if($this->db->field_exists('total_commission_amount_by_fix_rate_without_sub_aff', $this->tableName2)){
                $this->dbforge->drop_column($this->tableName2, 'total_commission_amount_by_fix_rate_without_sub_aff');
            }
        }
    }
}
///END OF FILE/////