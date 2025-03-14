<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_to_habanero_transactions_20200429 extends CI_Migration {
    private $tableName = 'habanero_transactions';

    public function up() {
        $this->load->model('player_model');
        if(!$this->player_model->existsIndex($this->tableName, 'idx_updated_at')) {
            $this->player_model->addIndex($this->tableName, 'idx_updated_at', 'updated_at');
        }
    }

    public function down() {

    }
}
