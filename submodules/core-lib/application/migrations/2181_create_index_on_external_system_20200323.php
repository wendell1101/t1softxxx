<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_create_index_on_external_system_20200323 extends CI_Migration {
    // for OGP-16123 [RC Regression]Stuck on loading when switch to multiple range
    private $tableName = 'external_system';

    public function up() {
        $this->load->model('player_model');
        $this->player_model->addIndex($this->tableName, 'idx_system_type', 'system_type');
        $this->player_model->addIndex($this->tableName, 'idx_status', 'status');

    }

    public function down() {
        $this->load->model('player_model');
        $this->player_model->dropIndex($this->tableName, 'idx_system_type');
        $this->player_model->dropIndex($this->tableName, 'idx_status');
    }
}
