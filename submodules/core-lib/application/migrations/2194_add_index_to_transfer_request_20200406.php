<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_transfer_request_20200406 extends CI_Migration {
    private $tableName = 'transfer_request';

    public function up() {
        $this->load->model('player_model');
        if(!$this->player_model->existsIndex($this->tableName, 'idx_external_transaction_id')) {
            $this->player_model->addIndex($this->tableName, 'idx_external_transaction_id', 'external_transaction_id');
        }
    }

    public function down() {

    }
}
