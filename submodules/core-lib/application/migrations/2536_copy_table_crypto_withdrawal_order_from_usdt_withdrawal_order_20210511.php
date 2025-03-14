<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_copy_table_crypto_withdrawal_order_from_usdt_withdrawal_order_20210511 extends CI_Migration {

    public function up() {
        $this->db->query('INSERT crypto_withdrawal_order SELECT * FROM usdt_withdrawal_order');
    }

    public function down() {}
}