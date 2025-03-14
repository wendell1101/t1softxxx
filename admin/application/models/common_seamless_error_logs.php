<?php
if(! defined("BASEPATH")){
    exit("No direct script access allowed");
}

require_once dirname(__FILE__) . "/base_game_logs_model.php";

class Common_seamless_error_logs extends Base_game_logs_model
{

    protected $tableName = "common_seamless_error_logs";
    const ERROR_ID_UNKNOWN=1;
    const ERROR_ID_INVALID_TOKEN=2;
    const ERROR_ID_INVALID_PARAMETER=3;
    const ERROR_ID_INVALID_IP=4;

    public function __construct()
    {
        parent::__construct();
    }

    public function getGameLogStatistics($dateFrom, $dateTo)
    {
        return false;
    }

    /** 
     * Overview: check transaction if exist based in unique id
     * 
     * @param int $gamePlatformId
     * @param int $externalUniqueId
     * 
     * @return boolean
    */
    public function isTransactionExist($gamePlatformId, $externalUniqueId)
    {
        $this->db->from($this->tableName)
            ->where("game_platform_id",$gamePlatformId)
            ->where('external_unique_id',$externalUniqueId);

        return $this->runExistsResult();
    }

    public function getTransactionRowArray($gamePlatformId, $externalUniqueId)
    {
        $qry = $this->db->from($this->tableName)
        ->where("game_platform_id",$gamePlatformId)
        ->where('external_unique_id',$externalUniqueId)
        ->row_array();
        return $qry;
    }

    /** 
     * Insert Transaction to Table
     * 
     * @param array $data
     * @return int last insert ID
    */
    public function insertTransaction($data)
    {
        return $this->insertData($this->tableName,$data);
    }

    public function get_seamless_error_logs($from , $to, $db = null, $db_name = null){
        $error_ids = $this->utils->getConfig('seamless_error_logs_error_id_cron_settings');
        if(empty($error_ids) || !is_array($error_ids)){
            $error_ids = [self::ERROR_ID_INVALID_TOKEN];
        }

        $str_error_ids=implode(',',$error_ids);

        $sql=<<<EOD
select *
from {$this->tableName} as csel
where
csel.created_at >= ? and csel.created_at <= ?  and csel.error_id in ($str_error_ids)
order by game_platform_id
EOD;
        $this->utils->debug_log('get_seamless_error_logs sql', $sql, $from, $to);
        $rows =$this->runRawSelectSQLArray($sql, [$from, $to, self::DB_FALSE], $db);
        return $rows;
    }

    public function generate_seamless_error_log_notification($rows, $from, $to, $db_name){
        $wallet_map = $this->utils->getGameSystemMap();
        if(!empty($rows)){
            $user = "# Seamless common error logs";
            $config = $this->utils->getConfig('seamless_error_logs_cron_settings');

            $export_link = null;
            $body = "**Date Time**:{$from} to {$to}  ";
            $body .= "**Database**:{$db_name}  ";

            if($config){
                if(isset($config['host'])){
                    $user .= " ({$config['host']})";
                }
                
            }

            $groupedByErrorIdAndPlatform = array_reduce($rows, function (array $group, array $element) {
                $group[$element['error_id']][$element['game_platform_id']][] = $element;

                return $group;
            }, []);


            foreach ($groupedByErrorIdAndPlatform as $index => $errorData) {
                $error_des = $this->getErrorIdDescription($index);
                $body .= "\n **{$error_des}** \n ";
                if(!empty($errorData)){
                    foreach ($errorData as $platformId => $data) {
                        $platform = isset($wallet_map[$platformId]) ? $wallet_map[$platformId] : "Unkown";
                        $body .= " *{$platform}* \n ";

                        if(!empty($data)){
                            $body .= "``` json \n ";
                            foreach ($data as $key => $value) {
                                $array = array(
                                    "request_id" => $value['request_id'],
                                    "response_result_id" => $value['response_result_id'],
                                );
                                $body .= json_encode($array). " \n ";
                            }
                            $body .= " \n```";
                        }

                        $body .= "  \n ";
                    }
                }
            }

            $message = [
                null,
                $body,
            ];

            $channel = $this->utils->getConfig('generate_seamless_error_log_notification_channel');
            $this->load->helper('mattermost_notification_helper');

            sendNotificationToMattermost($user, $channel, [], $message);
        }
    }

    function getErrorIdDescription($id){
        switch ($id) {
            case 2:
                return "Error invalid token.";
                break;
            case 3:
                return "Error invalid parameter.";
                break;
            case 4:
                return "Invalid IP Address.";
                break;

            default:
                return "Error unknown.";
                break;
        }
    }
}