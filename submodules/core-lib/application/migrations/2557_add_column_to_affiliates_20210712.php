<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_affiliates_20210712 extends CI_Migration {

    private $tableName = 'affiliates';

    public function up() {
        $field = array(
            'disable_cashback_on_registering' => array(
                'type' => 'TINYINT',
                'null' => true,
                'default' => 0
            )
        );

        $field2 = array(
            'disable_promotion_on_registering' => array(
                'type' => 'TINYINT',
                'null' => true,
                'default' => 0
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            $this->load->model('player_model');

            if(!$this->db->field_exists('disable_cashback_on_registering', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
                // $this->player_model->addIndex($this->tableName, 'idx_disable_cashback_on_registering', 'disable_cashback_on_registering');
            }
            if(!$this->db->field_exists('disable_promotion_on_registering', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field2);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('disable_cashback_on_registering', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'disable_cashback_on_registering');
            }
            if($this->db->field_exists('disable_promotion_on_registering', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'disable_promotion_on_registering');
            }
        }
    }
}
