<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_suncity_game_logs_20200427 extends CI_Migration
{
    private $tableName = 'suncity_game_logs';

    public function up() {

        $fields = array(
            'rollingturnover' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('rollingturnover', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('rollingturnover', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'rollingturnover');
        }
    }
}