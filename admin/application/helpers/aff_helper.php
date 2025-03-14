<?php

function aff_newly_player_tagged_list($affiliate_id){
    $CI= &get_instance();

    $tag_list = $CI->affiliate_newly_registered_player_tags->getTagsByAffiliateId($affiliate_id);
    // $tag_list = $CI->affiliatemodel->getAffiliateTag($affiliate_id);

    if(FALSE === $tag_list || !is_array($tag_list)){
        return lang('player.tp12');
    }
    $tag_list_result = [];

    foreach($tag_list as $tag_entry){
        $tag_list_result[] = $tag_entry['tagName'];
    }

    return implode(', ', $tag_list_result);
}

function aff_tagged_list($affiliate_id, $is_export = FALSE){
    $CI= &get_instance();

    $tag_list = $CI->affiliatemodel->getAffiliateTag($affiliate_id);

    if(FALSE === $tag_list || !is_array($tag_list)){
        return lang('player.tp12');
    }

    $tag_list_result = [];

    foreach($tag_list as $tag_entry){
        $tag_list_result[] = aff_tag_formatted($tag_entry['tagId'], $is_export);
    }

    return ($is_export) ? implode(',', $tag_list_result) : implode('', $tag_list_result);
}

function aff_tag_formatted($tagId, $is_export){
    $CI= &get_instance();
    static $tag_list;

    if (empty($tag_list)) {
        $tag_list = $CI->affiliatemodel->getActiveTagsKV();
    }

    if(isset($tag_list[$tagId])){
        if($is_export){
            return $tag_list[$tagId]['tagName'];
        }
        return aff_tagged_formate($tagId, $tag_list[$tagId]);
    }

    return $tagId;
}

function aff_tagged_formate($tagId = null, $tagList){
    $CI= &get_instance();

    if(isset($tagList['tagColor']) && !empty($tagList['tagColor'])){
        return '<a href="javascript: void(0);" class="tag tag-component"><span class="tag tag-text label" style="background-color:'. $tagList['tagColor'].'"> '. $tagList['tagName'] . '</span></a>';
    }else{
        return '<a href="javascript: void(0);" class="tag tag-component"><span class="tag tag-text label label-info">' . $tagList['tagName'] . '</span></a>';
    }
}