<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_table_summary_game_total_bet extends CI_Migration {
    private $tableName = 'summary_game_total_bet';
    public function up()
    {   
        $fields = [
            'id' => [
                'type' => 'INT',
                'null' => false,
                'auto_increment' => true
            ],
            'game_description_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'game_type_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'game_platform_id' => [
                'type' => 'INT',
                'null' => true,
            ],
            'total_players' => [
                'type' => 'INT',
                'null' => true,
            ],
            'total_bets' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'total_half_percentage' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false
            ],
        ];

        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            // default
            $this->player_model->addIndex($this->tableName, 'idx_game_description_id', 'game_description_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_platform_id', 'game_platform_id');
            $this->player_model->addIndex($this->tableName, 'idx_game_type_id', 'game_type_id');
            // $this->player_model->addIndex($this->tableName, 'idx_external_game_id', 'external_game_id');
        }
    }

    public function down()
    {
        if ($this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
    }
