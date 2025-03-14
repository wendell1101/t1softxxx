<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_table_flash_tech_game_logs_20200818 extends CI_Migration {

    private $tableName = 'flash_tech_game_logs';

    public function up()
    {
        $fields = [
            'id' => [
                'type' => 'BIGINT',
                'null' => false,
                'auto_increment' => true
            ],
            'versionId' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'SourceName' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true
            ],
            'ReferenceNo' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'SocTransId' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'IsFirstHalf' => [
                'type' => 'TINYINT',
                'null' => false,
                'default' => 0
            ],
            'TransDate' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'IsHomeGive' => [
                'type' => 'TINYINT',
                'null' => false,
                'default' => 0
            ],
            'IsBetHome' => [
                'type' => 'TINYINT',
                'null' => false,
                'default' => 0
            ],
            'BetAmount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'Outstanding' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'Hdp' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'Odds' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'Currency' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true
            ],
            'WinAmount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'ExchangeRate' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'WinLoseStatus' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true
            ],
            'TransType' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true
            ],
            'DangerStatus' => [
                'type' => 'VARCHAR',
                'constraint' => '2',
                'null' => true
            ],
            'MemCommission' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'BetIp' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'HomeScore' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'AwayScore' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'RunHomeScore' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'RunAwayScore' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'IsRunning' => [
                'type' => 'TINYINT',
                'null' => false,
                'default' => 0
            ],
            'RejectReason' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ],
            'SportType' => [
                'type' => 'VARCHAR',
                'constraint' => '5',
                'null' => true
            ],
            'Choice' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'WorkingDate' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'OddsType' => [
                'type' => 'VARCHAR',
                'constraint' => '2',
                'null' => true
            ],
            'MatchDate' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'HomeTeamId' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'AwayTeamId' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'LeagueId' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'SpecialId' => [
                'type' => 'VARCHAR',
                'constraint' => '10',
                'null' => true
            ],
            'StatusChange' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'StateUpdateTs' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'MemCommissionSet' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'IsCashOut' => [
                'type' => 'TINYINT',
                'null' => false,
                'default' => 0
            ],
            'CashOutTotal' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'CashOutTakeBack' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'CashOutWinLoseAmount' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'BetSource' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'AOSExcluding' => [
                'type' => 'VARCHAR',
                'constraint' => '512',
                'null' => true
            ],
            'MMRPercent' => [
                'type' => 'DOUBLE',
                'null' => true
            ],
            'MatchID' => [
                'type' => 'INT',
                'constraint' => '15',
                'null' => true
            ],
            'MatchGroupID' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'BetRemarks' => [
                'type' => 'VARCHAR',
                'constraint' => '128',
                'null' => true
            ],
            'IsSpecial' => [
                'type' => 'TINYINT',
                'null' => false,
                'default' => 0
            ],
            'extra_info' => [
                'type' => 'JSON',
                'null' => true
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                'null' => false,
            ],
            'md5_sum' => [
                'type' => 'VARCHAR',
                'constraint' => '32',
                'null' => true
            ]
        ];

        if(! $this->db->table_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id',TRUE);
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model('player_model');
            $this->player_model->addIndex($this->tableName,'idx_flashtech_versionId','versionId');
            $this->player_model->addIndex($this->tableName,'idx_flashtech_ReferenceNo','ReferenceNo');
            $this->player_model->addUniqueIndex($this->tableName, 'idx_flashtech_MatchID', 'MatchID');
        }
    }

    public function down()
    {
        if($this->db->table_exists($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}