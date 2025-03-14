<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_for_transactionCode_on_walletaccount_20200522 extends CI_Migration {
    private $tableName = 'walletaccount';

    public function up() {
        $this->load->model(['player_model']);

        $this->player_model->addIndex($this->tableName, 'idx_transactionCode', 'transactionCode');
    }

    public function down() {}
}
