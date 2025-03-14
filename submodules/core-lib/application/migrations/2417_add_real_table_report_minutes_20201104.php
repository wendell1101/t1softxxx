<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_real_table_report_minutes_20201104 extends CI_Migration {

    private $tableName = 'real_table_report_minutes';

    public function up()
    {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'table_identifier' => [
                'type' => 'VARCHAR',
                'constraint' => '30',
                'null' => true
            ],
            'player_ids_count' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => false
            ],
            'round_ids_count' => [
                'type' => 'INT',
                'constraint' => '12',
                'null' => false
            ],
            'game_platform_id' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => false
            ],
            'betting_amount' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'real_betting_amount' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'result_amount' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'win_amount' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'loss_amount' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'minute' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => false
            ],
            'hour' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => false
            ],
            'date_within' => [
                'type' => 'DATE',
                'null' => false
            ],
            'date_minute' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => false
            ],
            'currency_key' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => false
            ],
            'md5_sum' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true
            ],
            'uniqueid' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => false
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ]
        ];

        $this->load->model('player_model');

        if(! $this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id',TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->player_model->addIndex($this->tableName,'idx_realtablereportminutes_tableidentifier','table_identifier');
            $this->player_model->addIndex($this->tableName,'idx_realtablereportminutes_datewithin','date_minute');
            $this->player_model->addIndex($this->tableName,'idx_realtablereportminutes_datewithin','date_within');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_realtablereportminutes_uniqueid', 'uniqueid');
        }

    }

    public function down()
    {
        if($this->utils->table_really_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}