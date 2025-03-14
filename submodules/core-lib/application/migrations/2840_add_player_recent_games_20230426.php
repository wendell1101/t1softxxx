<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_player_recent_games_20230426 extends CI_Migration {


    private $tableName = 'player_recent_games';

    public function up() {
        $fields = [
            // default
            'id' => [
                'type' => 'INT',
                'null' => false,
                'auto_increment' => true
            ],
            'player_id' => [
                'type' => 'INT',
                'null' => false
            ],
            'game_description_id' => [
                'type' => 'INT',
                'null' => false
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false
            ],
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_description_id', 'game_description_id');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
        }
    }

    public function down() {
    }


}