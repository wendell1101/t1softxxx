<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_index_on_table_pragmatic_tournaments_winners_20230208 extends CI_Migration {
    private $tableName = 'game_tournaments_winners';

    public function up() {
        if ($this->utils->table_really_exists($this->tableName)) {
            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName, 'idx_player_username', 'player_username');
        }
    }

    public function down() {
    }
}