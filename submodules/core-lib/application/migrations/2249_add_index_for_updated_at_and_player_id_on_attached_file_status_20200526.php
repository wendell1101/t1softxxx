<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_for_updated_at_and_player_id_on_attached_file_status_20200526 extends CI_Migration {
    private $tableName = 'attached_file_status';

    public function up() {
        $this->load->model(['player_model']);

        $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
        $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
    }

    public function down() {}
}
