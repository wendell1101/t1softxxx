<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_top_bet_players_20200721 extends CI_Migration {

    private $tableName = 'top_bet_players';

    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'top_bet_amount_players' => array(
                'type' => 'text',
                'null' => true,
            ),
            'active' => array(
                'type' => 'BOOLEAN',
                'null' => false,
                'default' => 0,
            ),
            'other_info' => array(
                'type' => 'text',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
       );

        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
       }
    }

    public function down() {
       if($this->db->table_exists($this->tableName)){
           $this->dbforge->drop_table($this->tableName);
       }
    }
}
