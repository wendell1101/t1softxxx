<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_payment_account_20201015 extends CI_Migration {

    private $tableName = 'payment_account';

    public function up() {
        $fields = array(
            "preset_amount_buttons" => array(
                "type" => "VARCHAR",
                "constraint" => "150",
                "null" => true
            )
        );

        if(!$this->db->field_exists('preset_amount_buttons', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('preset_amount_buttons', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'preset_amount_buttons');
        }
    }
}