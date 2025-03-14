<?php

if (! defined("BASEPATH")) {
    exit("No direct script access allowed");
}

require_once dirname(__FILE__) . "/BaseController.php";

class Gameprovider_service_api extends BaseController
{
    public $output;

    /**
     * Get logs from the game platform.
     *
     * @param  mixed $gamePlatformId  The ID of the game platform.
     * @return \CI_Output  The JSON response with game platform logs.
     */
    public function getGamePlatformLogs($gamePlatformId)
    {
        $extra = [];
        $api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
        $data = $api->queryGameLogsFromProvider($extra);
        $jsonData = json_encode($data);

        return $this->output
            ->set_content_type("application/json")
            ->set_output($jsonData);
    }
    /**
     * Get the list of game platforms.
     *
     * @param  mixed $gamePlatformId  The ID of the game platform.
     * @return \CI_Output  The JSON response with the game platform list.
     */
    public function getGamePlatformList($gamePlatformId)
    {
        $extra = [];
        $api = $this->utils->loadExternalSystemLibObject($gamePlatformId);
        $data = $api->queryGamePlatformList($extra);
        $jsonData = json_encode($data);

        return $this->output
            ->set_content_type("application/json")
            ->set_output($jsonData);
    }
}
