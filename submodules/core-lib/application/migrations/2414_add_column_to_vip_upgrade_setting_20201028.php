<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_vip_upgrade_setting_20201028 extends CI_Migration {

    private $tableName = 'vip_upgrade_setting';

    public function up() {
        $fields = array(
            "bet_amount_settings" => array(
                "type" => "JSON",
                "null" => true
            ),
        );

        if(!$this->db->field_exists('bet_amount_settings', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('bet_amount_settings', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'bet_amount_settings');
        }
    }
}