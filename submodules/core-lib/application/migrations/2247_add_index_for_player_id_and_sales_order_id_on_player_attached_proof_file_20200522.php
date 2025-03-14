<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_index_for_player_id_and_sales_order_id_on_player_attached_proof_file_20200522 extends CI_Migration {
    private $tableName = 'player_attached_proof_file';

    public function up() {
        $this->load->model(['player_model']);

        $this->player_model->addIndex($this->tableName, 'idx_player_id', 'player_id');
        $this->player_model->addIndex($this->tableName, 'idx_sales_order_id', 'sales_order_id');
    }

    public function down() {}
}
