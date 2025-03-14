<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_field_round_id_in_common_seamless_wallet_transactions_20201006 extends CI_Migration
{
	private $tableName = 'common_seamless_wallet_transactions';

    public function up() {

        $fields = array(
            'round_id' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            )
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('round_id', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);

                # add Index
                $this->load->model('player_model');
                $this->player_model->addIndex($this->tableName,'idx_commonseamlesswallettransaction_round_id','round_id');
            }
        }
    }

    public function down() {
        if( $this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('round_id', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'round_id');
            }
        }
    }
}