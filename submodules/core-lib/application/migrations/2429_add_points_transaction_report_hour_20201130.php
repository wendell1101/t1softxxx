<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_points_transaction_report_hour_20201130 extends CI_Migration {

    private $tableName = 'points_transaction_report_hour';

    public function up()
    {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'game_description_id' => [
				'type' => 'INT',
				'null' => false,
			],
			'game_platform_id' => [
				'type' => 'INT',
				'null' => false,
			],
			'game_type_id' => [
				'type' => 'INT',
				'null' => false,
			],
            'source_uniqueid' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true
            ],
            'uniqueid' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => false
            ],
            'player_id' => [
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
            'md5_sum' => [
                'type' => 'VARCHAR',
                'constraint' => '64',
                'null' => true
            ],
            'currency_key' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null'=> true
            ],
            'date_hour' => [
				'type' => 'VARCHAR',
				'constraint' => '20',
				'null' => false,
            ],
            'hour' => [
				'type' => 'INT',
				'null' => false,
			],
			'date' => [
				'type' => 'DATE',
				'null' => false,
			],
            'vip_id' => [
                'type' => 'INT',
                'constraint' => '11',
                'null' => false
            ],
            'bet_points_rate' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'bet_points' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'win_points_rate' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'win_points' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'lose_points_rate' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'lose_points' => [
                'type' => 'DOUBLE',
                'null' => false
            ],
            'is_deleted' => [
                'type' => 'TINYINT',
                'constraint' => '1',
                'null' => true,
                'default' => 0
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
            $this->player_model->addIndex($this->tableName,'idx_pointstransactionreporthourly_date','date');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_realtablereporthourly_sourceuniqueid', 'source_uniqueid');
            $this->player_model->addUniqueIndex($this->tableName,'idx_pointstransactionreporthourly_uniqueid','uniqueid');
        }
    }

    public function down()
    {
        if($this->utils->table_really_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}