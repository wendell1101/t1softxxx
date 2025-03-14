<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_ezugi_evo_seamless_wallet_transactions_20240928 extends CI_Migration {
    private $tableName = 'ezugi_evo_seamless_wallet_transactions';
    private $originalTableName = 'ezugi_seamless_wallet_transactions';

    public function up() {
        if (!$this->db->table_exists($this->tableName)) {
            $this->CI->load->model(['player_model']);
            $this->CI->player_model->runRawUpdateInsertSQL("CREATE TABLE {$this->tableName} LIKE {$this->originalTableName}");
        }
    }

    public function down() {
        if (!$this->db->table_exists($this->tableName)) {
            $this->dbforge->drop_table($this->tableName);
        }
    }
}
