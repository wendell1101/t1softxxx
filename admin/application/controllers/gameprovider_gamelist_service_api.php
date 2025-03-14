<?php

if (! defined("BASEPATH")) {
    exit("No direct script access allowed");
}

require_once dirname(__FILE__) . "/BaseController.php";

class Gameprovider_gamelist_service_api extends BaseController
{
    public $output;

    public function getGamePlatformList($gamePlatformId)
    {
        $extra = [];
        $api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
        $data = $api->queryGameListFromGameProvider($extra);
        $jsonData = json_encode($data);

        if ($api->enable_export_game_list_json) {
            $downloadLink = base_url("download_game_list/{$gamePlatformId}");
            // return button link
            $buttonHtml = '<button onclick="window.open(\'' . $downloadLink . '\', \'_blank\')">Download Game List</button>';
            return $this->output
                ->set_content_type("text/html")
                ->set_output($buttonHtml);
        }

        // If export is not enabled, return the JSON data
        return $this->output
            ->set_content_type("application/json")
            ->set_output($jsonData);
    }

    public function downloadGameList($gamePlatformId)
    {
        $api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
        $data = $api->queryGameListFromGameProvider([]);
        $jsonData = json_encode($data);

        $date = date('Ymd'); // Current date in YYYYMMDD format
        $fileName = "{$date}_game_list_{$gamePlatformId}.json";

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . strlen($jsonData));

        echo $jsonData;
        exit;
    }
}
