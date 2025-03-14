<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_tournament_schedule_20240131 extends CI_Migration {
	private $tableName = 'tournament_schedule';

    public function up() {
        $field1 = array(
            'distributionType' => array(
                'type' => 'INT',
                'default' => 1,
            )
        );

        $field2 = array(
            'withdrawalConditionTimes' => array(
                'type' => 'INT',
                'default' => 1,
            )
        );




        if($this->utils->table_really_exists($this->tableName)){

            if(!$this->db->field_exists('distributionType', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field1);
            }
            if(!$this->db->field_exists('withdrawalConditionTimes', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('distributionType', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'distributionType');
            }
            if($this->db->field_exists('withdrawalConditionTimes', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'withdrawalConditionTimes');
            }
        }
    }
}
///END OF FILE/////