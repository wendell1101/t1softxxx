<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_columns_to_iovation_evidence_table_20210921 extends CI_Migration {

    private $tableName = 'iovation_evidence';

    public function up() {
        
        $field = array(
            'device_alias' => array(
				'type' => 'VARCHAR',
				'constraint' => '50',
				'null' => true,
            ),
            'applied_to_type' => array(
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => true,
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('device_alias', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $field);
                $this->load->model('player_model'); # Any model class will do
                $this->player_model->addIndex($this->tableName,	'idx_device_alias' , 'device_alias');
                $this->player_model->addIndex($this->tableName,	'idx_applied_to_type' , 'applied_to_type');
            }
        }

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('device_alias', $this->tableName)){
                $modifyFields = array(
                    'account_code' => array(
                        'type' => 'VARCHAR',
                        'constraint' => '60',
                        'null' => true,
                    )
                );
                $this->dbforge->modify_column($this->tableName, $modifyFields);
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('device_alias', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'device_alias');
            }
            if($this->db->field_exists('applied_to_type', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'applied_to_type');
            }
        }
    }
}
