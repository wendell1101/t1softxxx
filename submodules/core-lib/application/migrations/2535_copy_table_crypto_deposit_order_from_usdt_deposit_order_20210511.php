<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_copy_table_crypto_deposit_order_from_usdt_deposit_order_20210511 extends CI_Migration {

    public function up() {
        $this->db->query('INSERT crypto_deposit_order SELECT * FROM usdt_deposit_order');
    }

    public function down() {}
}