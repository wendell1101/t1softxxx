<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_sa_gaming_seamless_thb1_game_logs_20200507 extends CI_Migration
{
	private $tableName = "sa_gaming_seamless_thb1_game_logs";

    public function up() {

        $fields = array(
            'TransactionID' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('TransactionID', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('TransactionID', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'TransactionID');
        }
    }
}