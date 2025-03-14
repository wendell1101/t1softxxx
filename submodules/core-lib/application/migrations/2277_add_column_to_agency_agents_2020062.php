<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_agency_agents_2020062 extends CI_Migration {

    private $tableName = 'agency_agents';

    public function up() {
        $fields = array(
            'enabled_seamless_wallet' => array(
                'type' => 'TINYINT',
                'default' => 0,
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('enabled_seamless_wallet', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
        }
    }

    public function down() {
        if($this->db->field_exists('enabled_seamless_wallet', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'enabled_seamless_wallet');
        }
    }
}
