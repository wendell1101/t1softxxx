<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_player_high_rollers_stream_20230321 extends CI_Migration
{
	private $tableName = 'player_high_rollers_stream';

    public function up() {
        $fields = array(
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'player_id' => [
                'type' => 'INT',
                'null' => false
            ],
            'player_username' => [
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true
            ],
            'game_platform_id' => [
                'type' => 'INT',
                'null' => false
            ],
            'game_type_id' => [
                'type' => 'INT',
                'null' => false
            ],
            'game_description_id' => [
                'type' => 'INT',
                'null' => false
            ],
            //VARCHAR
            'game' => [
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true
            ],
            'game_type' => [
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true
            ],
            'game_code' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true
            ],
            'round' => [
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true
            ],
            //DOUBLE
            'bet_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'real_betting_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'result_amount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            //DATE TIME
            'start_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'end_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],

            #Default
            'external_uniqueid' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true
            ],
            'md5_sum' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false
            ]
        );

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_type_id', 'game_type_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_description_id', 'game_description_id');
            $this->player_model->addIndex($this->tableName, 'idx_round', 'round');
            $this->player_model->addIndex($this->tableName, 'idx_end_at', 'end_at');
            $this->player_model->addIndex($this->tableName, 'idx_start_at', 'start_at');
            # add Index unique
            $this->player_model->addUniqueIndex($this->tableName, 'idx_external_uniqueid', 'external_uniqueid');
        }
    }

    public function down() {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}