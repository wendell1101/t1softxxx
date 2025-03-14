<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_seamless_single_wallet_20200827 extends CI_Migration {

    private $tableName = 'seamless_single_wallet';

    public function up() {
        $fields = array(
            'agent_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
        );

        if(!$this->db->field_exists('agent_id', $this->tableName)){
            $this->dbforge->add_column($this->tableName, $fields);
            $this->player_model->addIndex($this->tableName, 'idx_agent_id', 'agent_id');
        }
    }

    public function down() {
        if($this->db->field_exists('agent_id', $this->tableName)){
            $this->dbforge->drop_column($this->tableName, 'agent_id');
        }
    }
}
