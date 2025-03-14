<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_vipsettingcashbackrule_20210308 extends CI_Migration
{
    private $tableName = 'vipsettingcashbackrule';

    public function up() {

        $fields1 = array(
            'points_limit' => array(
				'type' => 'DOUBLE',
				'null' => true,
            ),
        );

        $fields2 = array(
			'points_limit_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '10',
				'null' => true,
			),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('points_limit', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields1);
            }
            if(!$this->db->field_exists('points_limit_type', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields2);
            }
        }

    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('points_limit', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'points_limit');
            }
            if($this->db->field_exists('points_limit_type', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'points_limit_type');
            }
        }
    }
}