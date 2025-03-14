<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_jumb_gamelogs_20201014 extends CI_Migration {

    private $tableidr1 = 'jumb_game_logs_idr1';
    private $tablecny1 = 'jumb_game_logs_cny1';
    private $tablethb1 = 'jumb_game_logs_thb1';
    private $tableusd1 = 'jumb_game_logs_usd1';
    private $tablevnd1 = 'jumb_game_logs_vnd1';
    private $tablemyr1 = 'jumb_game_logs_myr1';

    // private $tableidr2 = 'jumb_game_logs_idr2';
    // private $tablecny2 = 'jumb_game_logs_cny2';
    // private $tablethb2 = 'jumb_game_logs_thb2';
    // private $tableusd2 = 'jumb_game_logs_usd2';
    // private $tablevnd2 = 'jumb_game_logs_vnd2';
    // private $tablemyr2 = 'jumb_game_logs_myr2';



    public function up() {
        $fields = array(
            'id' => array(
                'type' => 'INT',
                'null' => false,
                'auto_increment' => TRUE,
            ),
            'PlayerId' => array(
                'type' => 'INT',
                'constraint' => '11',
                'null' => false,
            ),
            'Username' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ),
            'seqNo' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'mtype' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'gameDate' => array(
                'type' => 'VARCHAR',
                'constraint' => '19',
                'null' => false,
            ),
            'bet' => array(
                'type' => 'double',
                'null' => false,
            ),
            'win' => array(
                'type' => 'double',
                'null' => false,
            ),
            'total' => array(
                'type' => 'double',
                'null' => false,
            ),
            'currency' => array(
                'type' => 'VARCHAR',
                'constraint' => '2',
                'null' => false,
            ),
            'jackpot' => array(
                'type' => 'double',
                'null' => false,
            ),
            'jackpotContribute' => array(
                'type' => 'double',
                'null' => false,
            ),
            'denom' => array(
                'type' => 'double',
                'null' => false,
            ),
            'lastModifyTime' => array(
                'type' => 'VARCHAR',
                'constraint' => '19',
                'null' => false,
            ),
            'gameName' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => false,
            ),
            'playerIp' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => false,
            ),
            'clientType' => array(
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => false,
            ),
            'hasFreegame' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'hasGamble' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'systemTakeWin' => array(
                'type' => 'INT',
                'null' => false,
            ),
            'err_text' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ),
            'external_uniqueid' => array(
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ),
            'response_result_id' => array(
                'type' => 'INT',
                'null' => true,
            ),
            'roomType' => array(
                'type' => 'int',
                'constraint' => '11',
                'null' => true,
            ),
            'beforeBalance' => array(
                'type' => 'double',
                'null' => true,
            ),
            'afterBalance' => array(
                'type' => 'double',
                'null' => true,
            ),
            'gambleBet' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ),
            'gType' => array(
                'type' => 'SMALLINT',
                'null' => true,
            )
        );

        if(!$this->db->table_exists($this->tableidr1)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableidr1);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addUniqueIndex($this->tableidr1, 'idx_pretty_external_unique_id_idr1', 'external_uniqueid');
        }

        if(!$this->db->table_exists($this->tablecny1)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tablecny1);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addUniqueIndex($this->tablecny1, 'idx_pretty_external_unique_id_cny1', 'external_uniqueid');
        }

        if(!$this->db->table_exists($this->tablethb1)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tablethb1);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addUniqueIndex($this->tablethb1, 'idx_pretty_external_unique_id_thb1', 'external_uniqueid');
        }

        if(!$this->db->table_exists($this->tableusd1)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tableusd1);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addUniqueIndex($this->tableusd1, 'idx_pretty_external_unique_id_usd1', 'external_uniqueid');
        }

        if(!$this->db->table_exists($this->tablevnd1)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tablevnd1);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addUniqueIndex($this->tablevnd1, 'idx_pretty_external_unique_id_vnd1', 'external_uniqueid');
        }

        if(!$this->db->table_exists($this->tablemyr1)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table($this->tablemyr1);
            # Add Index
            $this->load->model('player_model');
            $this->player_model->addUniqueIndex($this->tablemyr1, 'idx_pretty_external_unique_id_myr1', 'external_uniqueid');
        }
    }

    public function down() {
        if($this->db->table_exists($this->tableidr1)){
            $this->dbforge->drop_table($this->tableidr1);
        }
        
        if($this->db->table_exists($this->tablecny1)){
            $this->dbforge->drop_table($this->tablecny1);
        }
        
        if($this->db->table_exists($this->tablethb1)){
            $this->dbforge->drop_table($this->tablethb1);
        }
        
        if($this->db->table_exists($this->tableusd1)){
            $this->dbforge->drop_table($this->tableusd1);
        }
        
        if($this->db->table_exists($this->tablevnd1)){
            $this->dbforge->drop_table($this->tablevnd1);
        }
        
        if($this->db->table_exists($this->tablemyr1)){
            $this->dbforge->drop_table($this->tablemyr1);
        }
    }
}
