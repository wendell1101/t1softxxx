<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_asia_gaming_game_logs_20200330 extends CI_Migration
{
	private $tableName = 'asia_gaming_game_logs';

    public function up() {

        $fields = array(
            'rollingturnover' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('rollingturnover', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields, 'winloss');
        }
    }

    public function down() {
        if($this->db->field_exists('rollingturnover', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'rollingturnover');
        }
    }
}