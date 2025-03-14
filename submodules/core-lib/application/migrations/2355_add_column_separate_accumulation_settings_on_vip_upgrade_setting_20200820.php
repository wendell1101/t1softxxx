<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_separate_accumulation_settings_on_vip_upgrade_setting_20200820 extends CI_Migration {

    private $tableName = 'vip_upgrade_setting';

    public function up() {

        $fields = array(
            "separate_accumulation_settings" => array(
				"type" => "JSON",
                "null" => true
            ),
        );

        if(!$this->db->field_exists('separate_accumulation_settings', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('separate_accumulation_settings', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'separate_accumulation_settings');
        }
    }
}