<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_on_payment_account_player_level_20200526 extends CI_Migration {
    private $tableName = 'payment_account_player_level';

    public function up() {
        $this->load->model(['player_model']);

        $this->player_model->addIndex($this->tableName, 'idx_player_level_id', 'player_level_id');
        $this->player_model->addIndex($this->tableName, 'idx_payment_account_id', 'payment_account_id');
    }

    public function down() {}
}
