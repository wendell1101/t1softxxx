<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_tables_for_christmas_score_series_20211214 extends CI_Migration
{
    private $table_for_total_score = 'total_score';
    private $table_for_score_history = 'score_history';
    private $table_for_score_rank = 'score_rank';

    public function up()
    {
        # total_score
        $fields_for_total_score = array(
            'id' => ['type' => 'BIGINT', 'null' => false, 'auto_increment' => true],
            'player_id' => ['type' => 'INT', 'null' => false],
            'game_score' => ['type' => 'DOUBLE', 'null' => false],
            'manual_score' => ['type' => 'DOUBLE', 'null' => false],
            'generate_date DATE' => ['null' => true],
            'updated_at DATETIME' => ['null' => true ],
            'processed_by' => ['type' => 'INT', 'null' => true,]
        );

        if (!$this->utils->table_really_exists($this->table_for_total_score)) {
            $this->dbforge->add_field($fields_for_total_score);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->table_for_total_score);

            $this->load->model('player_model'); # Any model class will do
            $this->player_model->addIndex($this->table_for_total_score, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->table_for_total_score, 'idx_game_score', 'game_score');
            $this->player_model->addIndex($this->table_for_total_score, 'idx_manual_score', 'manual_score');
            $this->player_model->addIndex($this->table_for_total_score, 'idx_generate_date', 'generate_date');
            $this->player_model->addIndex($this->table_for_total_score, 'idx_updated_at', 'updated_at');
        }

        # score_history
        $fields_for_score_hisory = array(
            'id' => ['type' => 'BIGINT', 'null' => false, 'auto_increment' => true],
            'player_id' => ['type' => 'INT', 'null' => false],
            'type' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'score' => ['type' => 'DOUBLE', 'null' => false],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => ['null' => false],
            'created_by' => ['type' => 'INT', 'null' => false],
            'before_score' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'after_score' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'note' => ['type' => 'TEXT', 'null' => true],
            'action_log' => ['type' => 'TEXT', 'null' => true]
        );

        if (!$this->utils->table_really_exists($this->table_for_score_history)) {
            $this->dbforge->add_field($fields_for_score_hisory);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->table_for_score_history);

            $this->load->model('player_model'); # Any model class will do
            $this->player_model->addIndex($this->table_for_score_history, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->table_for_score_history, 'idx_score', 'score');
            $this->player_model->addIndex($this->table_for_score_history, 'idx_created_at', 'created_at');
        }

        # score_rank
        $fields_for_score_rank = array(
            'id' => ['type' => 'BIGINT', 'null' => false, 'auto_increment' => true],
            'player_id' => ['type' => 'INT', 'null' => false],
            'rank' => ['type' => 'VARCHAR', 'constraint' => '100', 'null' => true],
            'current_score' => ['type' => 'DOUBLE', 'null' => false],
            'updated_at DATETIME' => ['null' => true]
        );

        if (!$this->utils->table_really_exists($this->table_for_score_rank)) {
            $this->dbforge->add_field($fields_for_score_rank);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table($this->table_for_score_rank);

            $this->load->model('player_model'); # Any model class will do
            $this->player_model->addIndex($this->table_for_score_rank, 'idx_player_id', 'player_id');
            $this->player_model->addIndex($this->table_for_score_rank, 'idx_rank', 'rank');
            $this->player_model->addIndex($this->table_for_score_rank, 'idx_current_score', 'current_score');
        }
    }

    public function down(){
        if($this->db->table_exists($this->table_for_total_score)){
            $this->dbforge->drop_table($this->table_for_total_score);
        }
        if($this->db->table_exists($this->table_for_score_history)){
            $this->dbforge->drop_table($this->table_for_score_history);
        }
        if($this->db->table_exists($this->table_for_score_rank)){
            $this->dbforge->drop_table($this->table_for_score_rank);
        }
    }
}