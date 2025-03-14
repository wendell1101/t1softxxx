<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_promocmssetting_20210324 extends CI_Migration {

    private $tableName = 'promocmssetting';

    public function up() {
        $fields = array(
            'display_apply_btn_in_promo_page' => array(
                'type' => 'tinyint',
                'constraint' => '4',
                'default' => '1',
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('display_apply_btn_in_promo_page', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('display_apply_btn_in_promo_page', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'display_apply_btn_in_promo_page');
            }
        }
    }
}