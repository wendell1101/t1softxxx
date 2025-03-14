<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_sbtech_new_game_logs_20200430 extends CI_Migration
{
    private $tableName = 'sbtech_new_game_logs';

    public function up() {

        $fields = array(
            'validStake' => array(
                'type' => 'DOUBLE',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('validStake', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('validStake', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'validStake');
        }
    }
}