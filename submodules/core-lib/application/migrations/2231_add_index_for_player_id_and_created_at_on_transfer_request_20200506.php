<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_for_player_id_and_created_at_on_transfer_request_20200506 extends CI_Migration {
	private $tableName = 'transfer_request';

    public function up() {
        $this->load->model(['player_model']);

        $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
        $this->player_model->addIndex($this->tableName, 'idx_created_at', 'created_at');
    }

    public function down() {}
}
