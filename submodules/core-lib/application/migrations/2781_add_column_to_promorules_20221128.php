<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_promorules_20221128 extends CI_Migration {

    private $tableName = 'promorules';

    public function up() {
        $fields = array(
            'promo_period_countdown' => array(
                "type" => "TINYINT",
                'constraint' => 4,
                'unsigned' => TRUE,
                'default' => 0,
            ),
        );

        $this->load->model('player_model');
        if(!$this->db->field_exists('promo_period_countdown', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('promo_period_countdown', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'promo_period_countdown');
        }
    }
}