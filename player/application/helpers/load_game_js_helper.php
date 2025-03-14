<?php
function load_game_js($links = []){
    $html = "";
    $version = PRODUCTION_VERSION;
    if(!empty($links)){
        foreach ($links as $link) {
            $file_url = base_url($link);
            $html .= "<script src=\"{$file_url}?v={$version}\"></script>";
        }
    }
    
    return $html;
}