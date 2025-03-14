<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_roulette_api_record_20220521 extends CI_Migration {

	private $tableName = 'roulette_api_record';

	public function up() {
        $this->load->model('player_model');

		$fields1 = array(
            'product_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
            )
        );
        $fields2 = array(
            'prize' => array(
                'type' => 'text',
                'null' => true,
            )
        );

        $fields3 = array(
            'status' => array(
                'type' => 'int',
                'null' => false,
                'default' => 1
            )
        );

        $fields4 = array(
            'updated_by' => array(
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
            )
        );

        $fields5 = array(
            'updated_at' => array(
                'type' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
                'null' => false,
            )
        );

        $fields6 = array(
            'deposit_amount' => array(
                'type' => 'DOUBLE',
                'null' => true,
                'default' => 0
            ),
        );

        $fields7 = array(
            'total_times' => array(
                'type' => 'int',
                'null' => false,
                'default' => 0
            )
        );

        $fields8 = array(
            'used_times' => array(
                'type' => 'int',
                'null' => false,
                'default' => 0
            )
        );

        $fields9 = array(
            'player_promo_id' => array(
                'type' => 'INT',
                'null' => false,
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('product_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields1);
                $this->player_model->addIndex($this->tableName,'idx_product_id' , 'product_id');
            }
            if(!$this->db->field_exists('prize', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields2);
            }
            if(!$this->db->field_exists('status', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields3);
                $this->player_model->addIndex($this->tableName,'idx_status' , 'status');
            }
            if(!$this->db->field_exists('updated_by', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields4);
            }
            if(!$this->db->field_exists('updated_at', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields5);
                $this->player_model->addIndex($this->tableName,'idx_updated_at' , 'updated_at');
            }
            if(!$this->db->field_exists('deposit_amount', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields6);
            }
            if(!$this->db->field_exists('total_times', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields7);
            }
            if(!$this->db->field_exists('used_times', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields8);
            }
            if(!$this->db->field_exists('player_promo_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields9);
            }


        }
	}

    public function down() {
        $this->load->model('player_model');

        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('product_id', $this->tableName)){
                $this->player_model->dropIndex($this->tableName, 'idx_product_id');
                $this->dbforge->drop_column($this->tableName, 'product_id');
            }
            if($this->db->field_exists('prize', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'prize');
            }
            if($this->db->field_exists('status', $this->tableName)){
                $this->player_model->dropIndex($this->tableName, 'idx_status');

                $this->dbforge->drop_column($this->tableName, 'status');
            }
            if($this->db->field_exists('updated_by', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'updated_by');
            }
            if($this->db->field_exists('updated_at', $this->tableName)){
                $this->player_model->dropIndex($this->tableName, 'idx_updated_at');

                $this->dbforge->drop_column($this->tableName, 'updated_at');
            }
            if($this->db->field_exists('deposit_amount', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'deposit_amount');
            }
            if($this->db->field_exists('total_times', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'total_times');
            }
            if($this->db->field_exists('used_times', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'used_times');
            }
            if($this->db->field_exists('player_promo_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'player_promo_id');
            }
        }
    }
}
