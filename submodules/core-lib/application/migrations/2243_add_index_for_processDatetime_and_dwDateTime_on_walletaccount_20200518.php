<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_for_processDatetime_and_dwDateTime_on_walletaccount_20200518 extends CI_Migration {
    private $tableName = 'walletaccount';

    public function up() {
        $this->load->model(['player_model']);

        $this->player_model->addIndex($this->tableName, 'idx_dwDateTime', 'dwDateTime');
        $this->player_model->addIndex($this->tableName, 'idx_processDatetime', 'processDatetime');
    }

    public function down() {}
}
