<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_pragmatic_tournaments_winners_20230207 extends CI_Migration {
    private $tableName = 'game_tournaments_winners';

    public function up() {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'game_platform_id' => [
                'type' => 'INT',
                'constraint' => '10'
            ],
            'tournament_name' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'tournament_id' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'start_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'end_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'player_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'player_username' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'position' => [
                'type' => 'SMALLINT',
                'constraint' => '5'
            ],
            'score' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'prize_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'currency' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true
            ],
            // SBE additional info
            'response_result_id' => [
                'type' => 'INT',
                'null' => true
            ],
            'external_unique_id' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'null' => true
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'md5_sum' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true
            ]
        ];

        if (!$this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_unique_id', 'external_unique_id');
        }
    }

    public function down() {
        if ($this->utils->table_really_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}