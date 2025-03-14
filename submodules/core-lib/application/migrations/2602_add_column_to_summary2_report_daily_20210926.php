<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_summary2_report_daily_20210926 extends CI_Migration {

    private $tableName = 'summary2_report_daily';

    public function up() {
        
        $column1 = array(
            'count_deposit_member' => array(
				'type' => 'INT',
				'null' => true,
				'default' => 0,
			),
        );

        $column2 = array(
            'count_active_member' => array(
                'type' => 'INT',
                'null' => true,
                'default' => 0,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('count_deposit_member', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $column1);
            }
            if(!$this->db->field_exists('count_active_member', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $column2);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('count_deposit_member', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'count_deposit_member');
            }
            if($this->db->field_exists('count_active_member', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'count_active_member');
            }
        }
    }
}