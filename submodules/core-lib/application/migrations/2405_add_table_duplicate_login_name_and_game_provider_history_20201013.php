<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_duplicate_login_name_and_game_provider_history_20201013 extends CI_Migration {

    private $tableName = 'duplicate_login_name_and_game_provider_history';

    public function up() {
        $fields=array(
            'id' => array(
                'type' => 'BIGINT',
                'unsigned' => TRUE,
                'auto_increment' => TRUE,
            ),
            'current_player_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'duplicate_player_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'login_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => false,
            ),
            'game_provider_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
            //deleted, keep 2 accounts
            'action_type' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => false,
            ),
        );

        if(!$this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_current_player_id' , 'current_player_id');
            $this->player_model->addIndex($this->tableName,'idx_login_name' , 'login_name');
            $this->player_model->addIndex($this->tableName,'idx_created_at' , 'created_at');
        }
    }

    public function down() {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}