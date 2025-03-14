<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_column_to_friendreferralsettings_20220902 extends CI_Migration
{
	private $tableName = 'friendreferralsettings';

    private $tableName4playerfriendreferral = 'playerfriendreferral';

    public function up() {
        $this->load->model('player_model');

        /// friendreferralsettings
        $fields = array(
            'bonusAmountInReferred' => array(
                'type' => 'INT',
                'null' => true,
                'default' => 0
            ),
        );

        if($this->utils->table_really_exists($this->tableName)){
            if(!$this->db->field_exists('bonusAmountInReferred', $this->tableName)){
                $this->dbforge->add_column($this->tableName, $fields);
            }
        }

        /// playerfriendreferral
        $fields4playerfriendreferral = array(
            'transactionId4invited' => array(
                'type' => 'INT',
				'null' => true,
            ),
            'status4invited' => array(
                'type' => 'VARCHAR',
                'constraint' => '32',
                'default' => 0
            ),
        );

        if($this->utils->table_really_exists($this->tableName4playerfriendreferral)){
            if(!$this->db->field_exists('transactionId4invited', $this->tableName4playerfriendreferral)){
                $this->dbforge->add_column($this->tableName4playerfriendreferral, $fields4playerfriendreferral);

                $this->player_model->addIndex($this->tableName4playerfriendreferral, 'idx_transactionId4invited', 'transactionId4invited');
                // $this->player_model->addUniqueIndex()
                $this->player_model->addIndex($this->tableName4playerfriendreferral, 'idx_status4invited', 'status4invited');
            }
        }



    }

    public function down() {
        if($this->utils->table_really_exists($this->tableName)){
            if($this->db->field_exists('bonusAmountInReferred', $this->tableName)){
                $this->dbforge->drop_column($this->tableName, 'bonusAmountInReferred');
            }
        }

        if($this->utils->table_really_exists($this->tableName4playerfriendreferral)){
            if($this->db->field_exists('transactionId4invited', $this->tableName4playerfriendreferral)){
                $this->dbforge->drop_column($this->tableName4playerfriendreferral, 'transactionId4invited');
            }
            if($this->db->field_exists('status4invited', $this->tableName4playerfriendreferral)){
                $this->dbforge->drop_column($this->tableName4playerfriendreferral, 'status4invited');
            }
        }

    }
}