<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_on_duplicate_account_info_20200601 extends CI_Migration {
    private $tableName = 'duplicate_account_info';

    public function up() {
        $this->load->model(['player_model']);

        $this->player_model->addIndex($this->tableName, 'idx_userName', 'userName');
    }

    public function down() {}
}
