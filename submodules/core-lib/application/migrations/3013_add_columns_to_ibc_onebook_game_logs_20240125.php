<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_ibc_onebook_game_logs_20240125 extends CI_Migration {

    private $tableName = 'ibc_onebook_game_logs';

    public function up() {
        $voucherQuotaColumn = array(
            'voucher_quota VARCHAR(100) DEFAULT NULL',
        );
    
        $refNoColumn = array(
            'ref_no INT DEFAULT NULL',
        );
    
        if ($this->utils->table_really_exists($this->tableName)) {
            if (!$this->db->field_exists('voucher_quota', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $voucherQuotaColumn);
            }
            if (!$this->db->field_exists('ref_no', $this->tableName)) {
                $this->dbforge->add_column($this->tableName, $refNoColumn);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('voucher_quota', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'voucher_quota');
            }
            if($this->db->field_exists('ref_no', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'ref_no');
            }
        }
    }
}
