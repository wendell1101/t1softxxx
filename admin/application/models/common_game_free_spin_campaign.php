<?php
if(! defined("BASEPATH")){
    exit("No direct script access allowed");
}

require_once dirname(__FILE__) . "/base_model.php";

class Common_game_free_spin_campaign extends BaseModel
{

    protected $tableName = "common_game_free_spin_campaign";

    public function getAvailableCampaignForNewPlayer($gamePlatformId){
        $today = $this->utils->getNowForMysql();
        $this->db->select('campaign_id')
            ->distinct()
                ->from($this->tableName)
                    ->where("start_time <=",$today)
                        ->where("end_time >=",$today)
                            ->where("is_for_new_player",true)
                                ->where("game_platform_id",$gamePlatformId);

        return $this->runMultipleRowOneFieldArray('campaign_id');
    }

    public function getAvailableCampaign($gamePlatformId){
        $today = $this->utils->getNowForMysql();
        $this->db->select('campaign_id')
            ->distinct()
                ->from($this->tableName)
                    ->where("start_time <=",$today)
                        ->where("end_time >=",$today)
                            ->where("is_for_new_player",false)
                                ->where("game_platform_id",$gamePlatformId);

        return $this->runMultipleRowOneFieldArray('campaign_id');
    }

    public function checkPlayerIfExistOnCampaign($campaignId, $playerId){
        $this->db->where(['campaign_id'=>$campaignId,'player_id'=>$playerId])->from('free_spin_campaign_players');
        return $this->runExistsResult();
    }

    public function getPlayerAvailableCampaign($playerId, $gamePlatformId){
        $this->load->model(array('player_model'));
        $playerLevel = $this->player_model->getPlayerCurrentLevel($playerId);
        $vipLevel = isset($playerLevel[0]['vipSettingId']) ? $playerLevel[0]['vipSettingId'] : null;
        $today = $this->utils->getNowForMysql();
        $sql = <<<EOD
SELECT
DISTINCT cgfsc.campaign_id
FROM {$this->tableName} as cgfsc
LEFT JOIN free_spin_campaign_players as fscp ON fscp.campaign_id = cgfsc.campaign_id 
LEFT JOIN free_spin_campaign_viplevels as fscv ON fscv.campaign_id = cgfsc.campaign_id
WHERE
cgfsc.game_platform_id = ? and cgfsc.start_time <= ? and cgfsc.end_time >= ? and (fscp.player_id = ? or fscv.vip_setting_id = ? )
EOD;

        $params=[
            $gamePlatformId,
            $today,
            $today,
            $playerId,
            $vipLevel

        ];

        $rows = $this->runRawSelectSQLArray($sql, $params);
        $campaignIds =  array_column($rows, 'campaign_id');
        return $campaignIds;
    }

    public function getGameCampaignList($gamePlatformId){
        $sql = <<<EOD
SELECT
campaign_id,
name,
num_of_games,
status,
currency,
start_time,
end_time,
is_for_new_player,
created_at,
updated_at
FROM {$this->tableName} as cgfsc
WHERE
cgfsc.game_platform_id = ? 
AND DATE(cgfsc.end_time) >= DATE(NOW())
order by campaign_id desc
EOD;

        $params=[
            $gamePlatformId
        ];

        $campaigns = $this->runRawSelectSQLArray($sql, $params);
        if(!empty($campaigns)){
            foreach ($campaigns as $key => $value) {
                $campaigns[$key]['games'] = $this->getCampaignGames($value['campaign_id']);
                $campaigns[$key]['vip_levels'] = $this->getCampaignVips($value['campaign_id']);
                $campaigns[$key]['players'] = $this->getCampaignPlayers($value['campaign_id']);
            }
        }
        return $campaigns;
    }

    public function getCampaignGames($campaignId){
        $sql = <<<EOD
SELECT
DISTINCT(SUBSTRING_INDEX(gd.game_code, "-", -1)) as id, CONCAT(REPLACE ( gd.english_name, " Mobile", "" )," (",UPPER(gd.sub_game_provider),")") AS name
FROM free_spin_campaign_games as fscg
LEFT JOIN game_description as gd on gd.external_game_id LIKE CONCAT('%', fscg.external_game_id, '%')
WHERE
fscg.campaign_id = ? 
EOD;

        $params=[
            $campaignId
        ];

        $rows = $this->runRawSelectSQLArray($sql, $params);
        return $rows;
    }

    public function getCampaignVips($campaignId){
        $sql = <<<EOD
SELECT
vip_setting_id
FROM free_spin_campaign_viplevels
WHERE
campaign_id = ? 
EOD;

        $params=[
            $campaignId
        ];

        $rows = $this->runRawSelectSQLArray($sql, $params);
        $vip_levels =  array_column($rows, 'vip_setting_id');
        return $vip_levels;
    }

    public function getCampaignPlayers($campaignId){
        $sql = <<<EOD
SELECT
DISTINCT(p.playerId) as id,
p.username as player_username
FROM free_spin_campaign_players as fscp
JOIN `player` AS p ON p.playerId = fscp.player_id 
WHERE
fscp.campaign_id = ? 
EOD;

        $params=[
            $campaignId
        ];

        $rows = $this->runRawSelectSQLArray($sql, $params);
        return $rows;
    }

    public function getCampaignDetails($gamePlatformId, $campaignId){
        $sql = <<<EOD
SELECT
campaign_id,
name,
num_of_games,
status,
currency,
start_time,
end_time,
is_for_new_player,
created_at,
updated_at,
version
FROM {$this->tableName} as cgfsc
WHERE
cgfsc.game_platform_id = ? and cgfsc.campaign_id = ?
EOD;

        $params=[
            $gamePlatformId,
            $campaignId
        ];

        $campaign_details = $this->runOneRawSelectSQLArray($sql, $params);
        if(!empty($campaign_details)){
            $campaign_details['games'] = $this->getCampaignGames($campaignId);
            $campaign_details['vip_levels'] = $this->getCampaignVips($campaignId);
            $campaign_details['players'] = $this->getCampaignPlayers($campaignId);

            $start = strtotime($campaign_details['start_time']);
            $end = strtotime($campaign_details['end_time']);
            $today = $this->utils->getTimestampNow();
            $campaign_details['running'] = ($today >= $start) ? true : false;
            $campaign_details['ended'] = ($today >= $end) ? true : false;
        }
        
        return $campaign_details;
    }

    public function runCampaignGameDelete($campaignId){
        $this->db->where('campaign_id', $campaignId);
        return $this->runRealDelete('free_spin_campaign_games', $this->db);
    }

    public function runCampaignPlayerDelete($campaignId){
        $this->db->where('campaign_id', $campaignId);
        return $this->runRealDelete('free_spin_campaign_players', $this->db);
    }

    public function runCampaignVipDelete($campaignId){
        $this->db->where('campaign_id', $campaignId);
        return $this->runRealDelete('free_spin_campaign_viplevels', $this->db);
    }

    public function getFGPlayerDataAjaxRemote($perPage, $page, $search, $type){
        $this->db->select('p.username as text, p.playerId as id');
        $this->db->from('game_provider_auth as g');
        $this->db->join('player as p', 'p.playerId=g.player_id');
        $this->db->like('p.username', $search);
        $this->db->where('g.game_provider_id', FLOW_GAMING_SEAMLESS_THB1_API);
        $this->db->limit($perPage, $page);
        if($type == 'data'){
            $result =  $this->db->get()->result_array();
        } else {
            $result =  $this->db->count_all_results();
        }
        return $result;
    }

    public function getFGGameDataAjaxRemote($perPage, $page, $search, $type, $subProvider = null){
        $this->db->select('DISTINCT(SUBSTRING_INDEX(game_code, "-", -1)) as id, CONCAT(REPLACE ( english_name, " Mobile", "" )," (",UPPER(sub_game_provider),")") AS text', FALSE);
        $this->db->from('game_description');
        $this->db->like('english_name', $search);
        $this->db->where('game_platform_id', FLOW_GAMING_SEAMLESS_THB1_API);
        $this->db->where('game_code !=', "unknown");
        if(!empty($subProvider)){
            $this->db->where('sub_game_provider', $subProvider);
        } else {
            $this->db->limit($perPage, $page);
        }
        
        if($type == 'data'){
            $result =  $this->db->get()->result_array();
        } else {
            $result =  $this->db->count_all_results();
        }
        return $result;
    }

    public function getFGSubProviders(){
        $this->db->select('distinct(sub_game_provider) as provider');
        $this->db->from('game_description');
        $this->db->where('game_platform_id', FLOW_GAMING_SEAMLESS_THB1_API);
        $this->db->where('external_game_id !=', "unknown");
        $this->db->where('sub_game_provider is not null');
        $result =  $this->db->get()->result_array();
        return $result;
    }

    public function getCampaigns($gamePlatformId){
        $sql = <<<EOD
SELECT
*
FROM {$this->tableName} as cgfsc
WHERE
cgfsc.game_platform_id = ? 
AND DATE(cgfsc.end_time) >= DATE(NOW())
order by campaign_id desc
EOD;

        $params=[
            $gamePlatformId
        ];

        $campaigns = $this->runRawSelectSQLArray($sql, $params);
        
        return $campaigns;
    }

    public function getCampaignDetailsById($id, $game_platform_id)
    {
        $this->db->from($this->tableName)
            ->where("game_platform_id",$game_platform_id)
                ->where('id',$id);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function isFreeBetBonusExist($externalUniqueId)
    {
        $this->db->from('seamless_free_bet_record')
            ->where('unique_id',$externalUniqueId);

        return $this->runExistsResult();
    }
}
