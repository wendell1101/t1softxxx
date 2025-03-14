<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_redgenn_playson_seamless_streamer_wallet_transactions_20240803 extends CI_Migration {
    private $tableName = 'redgenn_playson_seamless_streamer_wallet_transactions';
    private $originalTableName = 'redgenn_playson_seamless_wallet_transactions';

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
