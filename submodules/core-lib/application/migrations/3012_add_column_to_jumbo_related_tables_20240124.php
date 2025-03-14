<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_column_to_jumbo_related_tables_20240124 extends CI_Migration {
	private $transferTableName = 'jumb_game_logs';
    private $seamlessTableName = 'jumbo_seamless_wallet_transactions';

    public function up() {
        $this->load->model('player_model');
        $field = array(
            'historyId' => array(
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ),
        );

        if($this->utils->table_really_exists($this->transferTableName)){
            if(!$this->db->field_exists('historyId', $this->transferTableName)){
                $this->dbforge->add_column($this->transferTableName, $field);
                $this->player_model->addIndex($this->transferTableName,'idx_historyId','historyId');
            }
        }

        if($this->utils->table_really_exists($this->seamlessTableName)){
            if(!$this->db->field_exists('historyId', $this->seamlessTableName)){
                $this->dbforge->add_column($this->seamlessTableName, $field);
                $this->player_model->addIndex($this->seamlessTableName,'idx_historyId','historyId');
            }
        }
    }

    public function down() {
        if($this->utils->table_really_exists($this->transferTableName)){
            if($this->db->field_exists('historyId', $this->transferTableName)){
                $this->dbforge->drop_column($this->transferTableName, 'historyId');
            }
        }

        if($this->utils->table_really_exists($this->seamlessTableName)){
            if($this->db->field_exists('historyId', $this->seamlessTableName)){
                $this->dbforge->drop_column($this->seamlessTableName, 'historyId');
            }
        }
    }
}
///END OF FILE/////