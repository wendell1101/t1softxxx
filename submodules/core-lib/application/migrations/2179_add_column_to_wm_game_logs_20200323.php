<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_wm_game_logs_20200323 extends CI_Migration
{
	private $tableName = 'wm_game_logs';

    public function up() {

        $fields = array(
            'waterbet' => array(
                'type' => 'TEXT',
                'null' => true,
            ),
            'beforeCash' => array(
                'type' => 'TEXT',
                'null' => true,
            )
        );

        if(!$this->db->field_exists('waterbet', $this->tableName) && !$this->db->field_exists('beforeCash', $this->tableName)) {
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('waterbet', $this->tableName)) {
            $this->dbforge->drop_column($this->tableName, 'waterbet');
        }
        if($this->db->field_exists('beforeCash', $this->tableName)) {
            $this->dbforge->drop_column($this->tableName, 'beforeCash');
        }
    }
}