<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_table_facebook_players_20220118 extends CI_Migration {
    private $tableName = 'facebook_players';

    public function up() {
        $fields = array(
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'facebook_user_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ),
            'facebook_username' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ),
            'id_token' => array(
                'type' => 'TEXT',
                'null' => false,
            ),
            'player_id' => array(
                'type' => 'INT',
                'unsigned' => TRUE,
                'constraint' => '10',
                'null' => true,
            ),
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => array(
                'null' => false,
            ),
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => array(
                'null' => false,
            )
        );


        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_facebook_user_id', 'facebook_user_id');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_player_id', 'player_id');
        }
    }

}
