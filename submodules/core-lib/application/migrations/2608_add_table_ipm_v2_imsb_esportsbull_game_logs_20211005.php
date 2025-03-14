<?php

defined("BASEPATH") OR exit("No direct script access allowed");

class Migration_add_table_ipm_v2_imsb_esportsbull_game_logs_20211005 extends CI_Migration
{

    private $tableName = "ipm_v2_imsb_esportsbull_game_logs";

    public function up()
    {
        $fields = array(
			"id" => array(
                "type" => "BIGINT",
                "null" => false,
                "auto_increment" => true
			),
			"Provider" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
			"GameId" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
			"BetId" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
			"WagerCreationDateTime" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
            "LastUpdatedDate" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
			"PlayerId" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
            "ProviderPlayerId" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
            "OperatorName" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
            "ProviderOperatorId" => array(
                "type" => "VARCHAR",
                "constraint" => "50",
                "null" => true
            ),
			"Currency" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
			"StakeAmount" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
            "TurnOver" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
            "MemberExposure" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
            "PayoutAmount" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
            "WinLoss" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
            "ResultStatus" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
			"OddsType" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
            "TotalOdds" => array(
                "type" => "DOUBLE",
                "null" => true
            ),
			"WagerType" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
			"Platform" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
			"isSettled" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
            "IsResettled" => array(
                "type" => "TINYINT",
                "null" => true,
                "default" => 0
            ),
			"isConfirmed" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
			"isCancelled" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
			"BetTradeStatus" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
			"BetTradeCommission" => array(
				"type" => "TEXT",
				"null" => true,
			),
			"BetTradeBuybackAmount" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
			"ComboType" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
			"LastUpdatedDate" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
            "betTradeSuccessDateTime" => array(
				"type" => "VARCHAR",
				"constraint" => "50",
				"null" => true,
			),
            "Confirmed" => array(
                "type" => "VARCHAR",
                "constraint" => 100,
                "null" => true
            ),
            "SettlementDateTime" => array(
                "type" => "datetime",
                "null" => true,
            ),
            "Tolerance" => array(
                "type" => "VARCHAR",
                "constraint" => 100,
                "null" => true
            ),
			"DetailItems" => array(
				"type" => "TEXT",
				"null" => true,
			),
            "SportsName" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true,
            ),
            "last_sync_time" => array(
                "type" => "DATETIME",
                "null" => true
            ),

            # SBE additional info
            "response_result_id" => array(
                "type" => "INT",
                "null" => true
            ),
            "external_uniqueid" => array(
                "type" => "VARCHAR",
                "constraint" => "100",
                "null" => true
            ),
            "created_at DATETIME DEFAULT CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" => array(
                "null" => false
            ),
            "md5_sum" => array(
                "type" => "VARCHAR",
                "constraint" => "32",
                "null" => true,
            )

		);

        if(!$this->utils->table_really_exists($this->tableName)){
            $this->dbforge->add_field($fields);
            $this->dbforge->add_key("id", true);
            $this->dbforge->add_key("last_sync_time");
            $this->dbforge->create_table($this->tableName);

            # add Index
            $this->load->model("player_model");
            $this->player_model->addIndex($this->tableName,"idx_GameId", "GameId");
            $this->player_model->addIndex($this->tableName,"idx_PlayerId", "PlayerId");
            $this->player_model->addUniqueIndex($this->tableName,"idx_BetId", "BetId");
            $this->player_model->addUniqueIndex($this->tableName,"idx_external_uniqueid", "external_uniqueid");
            $this->player_model->addUniqueIndex($this->tableName, "idx_WagerCreationDateTime", "WagerCreationDateTime");
        }
    }

    public function down()
    {
        if($this->db->table_exist($this->tableName)){
            $this->dbforge->drop_table($this->tableName);
        }
    }
}