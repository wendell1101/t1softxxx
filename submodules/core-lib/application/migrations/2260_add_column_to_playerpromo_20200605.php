<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_playerpromo_20200605 extends CI_Migration
{
    private $tableName = 'playerpromo';

    public function up() {

        $fields1 = array(
            'order_generated_by' => array(
                'type' => 'TINYINT',
                'null' => false,
                'default' => 0,
                'constraint' => 4,
            ),
        );

        $fields2 = array(
            'player_request_ip' => array(
                'type' => 'VARCHAR',
                'null' => true,
                'constraint' => 32,
            ),
        );


        if(!$this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('order_generated_by', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields1);
            }
            if(!$this->db->field_exists('player_request_ip', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields2);
            }
        }

    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('order_generated_by', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'order_generated_by');
            }
            if($this->db->field_exists('player_request_ip', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'player_request_ip');
            }
        }
    }
}