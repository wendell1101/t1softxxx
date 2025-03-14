<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_remove_index_sexy_baccarat_transaction_and_game_logs_table_20200414 extends CI_Migration {

    public function up() {
        $this->player_model->dropIndex('sexy_baccarat_transactions', 'platformTxId_UNIQUE');
        $this->player_model->dropIndex('sexy_baccarat_game_logs', 'platformTxId_UNIQUE');
    }

    public function down() {
        
    }
}