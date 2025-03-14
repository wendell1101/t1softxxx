<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_seamless_single_wallet_20200622 extends CI_Migration {

    private $tableName = 'seamless_single_wallet';

    public function up() {
        $fields = array(
            'is_blocked' => array(
                'type' => 'TINYINT',
                'default' => 0,
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('is_blocked', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('is_blocked', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'is_blocked');
        }
    }
}
