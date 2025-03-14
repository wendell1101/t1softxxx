<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$title?></title>
    <link rel="stylesheet" type="text/css" href="<?=$this->utils->thirdpartyUrl('bootstrap/4.4.1/bootstrap.min.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?=$this->utils->processAnyUrl('/includes/css/index.css', '/resources/player_lobby')?>">
    <link rel="stylesheet" type="text/css" href="<?=$this->utils->processAnyUrl('/includes/css/aos.css', '/resources/player_lobby')?>" />
</head>
<body>
    <div id="app-platform">
        <!-- Header -->
        <header>
            <div class="header">
                <div class="left">
                    <a class="logo" href="javascript:void(0)">
                        <?php if (empty($logo_link)) { ?>
                            <img src="<?=$this->utils->processAnyUrl('/includes/image/logos/tripleone.png', '/resources/player_lobby')?>" alt="">
                        <?php } else { ?>
                            <img src="<?=$logo_link?>" alt="">
                        <?php }  ?>
                    </a>
                </div>
            </div>
        </header>
        <!-- End -->

        <!-- Body -->
        <main>
            <div class="main_cont">
                <div class="search_field">
                    <input type="text" name="" id="search" placeholder="<?= lang('Search Game List', $int_lang)?>">
                    <button class="search">
                        <img src="  data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAQAAABKfvVzAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAAmJLR0QAAKqNIzIAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAAHdElNRQfkBQQHFQrUrPKcAAABhElEQVQ4y42TwUsUYRyGn9nRltQ9CpHbeW8iQcJCkEH4D3QIIb14U/8AD0HSqVN6EOoSitcIgtToUpDgJQRJ6GCJ4EF3y4smLKH0eNDZmVmdsff2vfM+833fy++Dpgwc9KUb7ttw2w+O2U22vOtXW3XoE6+lc8F5fIJp2oBdlvlBgxvco0oB+MLDYL/172Oq7jliIeFWfK/qmh3peNVj9ZvlC8cMfKrqQtpcVWveyrjbK/Wf/bExoOpoZhkla+rb2JhRf9mW09+UeuT1s1WBKvAxOMlpfAnopC8CeoCf5GkLgJsRUAT+5gINADojoBbTGeoBoB4B34GBXOA+IOtRB49VvZPT0qr6KV52WVdXDDPiQ6qOJK1xVWcNLonf9o+qixZjs+A7Vd+k59/AYY+ao55CSn5W9cBpH1i2214nLryPFNLuC4+9Soup52TF1/5ufjxx2UcutCBTLRc1pEKZEnU2ggMwZI7hRCB/iM4rmU/ssHklAIaJgz37DwAMnXTHPZ9bPAVTAmLdegNIbwAAACV0RVh0ZGF0ZTpjcmVhdGUAMjAyMC0wNS0wNFQwNzoyMToxMCswMDowMOBQc/YAAAAldEVYdGRhdGU6bW9kaWZ5ADIwMjAtMDUtMDRUMDc6MjE6MTArMDA6MDCRDctKAAAAGXRFWHRTb2Z0d2FyZQB3d3cuaW5rc2NhcGUub3Jnm+48GgAAAABJRU5ErkJggg==" alt="">
                    </button>
                </div>
                <div class="top_part">
                    <div class="game_cat">
                        <ul>
                            <?php
                            if (!empty($types)) {
                                foreach ($types as $key => $type) {?>
                                    <li class="<?= ($type['is_active']) ? "active" : ""?>">
                                        <a class="<?=$type['game_type_unique_code']?> gametype" href="#" data-id="<?=$type['game_type_unique_code']?>">
                                            <img src="<?=$type['game_type_icon']?>" alt="">
                                            <?=$type['game_type_name']?>
                                        </a>
                                    </li>
                                <?php
                                }
                            }?>
                        </ul>
                    </div>
                </div>
                <div class="game_container">
                </div>
            </div>
        </main>
    </div>
    <script src="<?=$this->utils->thirdpartyUrl('jquery/jquery-3.1.1.min.js') ?>"></script>
    <script src="<?=$this->utils->processAnyUrl('/includes/js/aos.js', '/resources/player_lobby')?>"></script>
    <script type="text/javascript">
        var game_platform_id = <?= $game_platform_id ?>;
        var home_link = <?= json_encode($home_link); ?>;
        var language = <?= json_encode($language); ?>;
        var static_details = <?= json_encode($static_details); ?>;
        var append_target_db= "<?=$append_target_db ? 'true' : 'false';?>";

        $(document).ready(function(){

            $(document).on("click",".gametype",function() {
                $("li[class*='active']").removeClass('active');
                $(this).parents().addClass('active');
                var game_type = $(this).attr("data-id") ;
                var search_string = $("#search").val();
                var params = {
                    search_string    : search_string,
                    home_link : home_link,
                    game_type : game_type,
                    language : language,
                    append_target_db: append_target_db
                };
                getGameList(params);
            });

            $(document).on('keypress',function(e) {
                if(e.which == 13) {
                    $( ".search" ).click();
                }
            });

            $(document).on("click",".search",function() {
                var game_type = $('.game_cat').find("li.active").children("a").attr("data-id");
                var search_string = $("#search").val();
                var params = {
                    search_string    : search_string,
                    home_link : home_link,
                    game_type : game_type,
                    language : language,
                    append_target_db: append_target_db
                };
                getGameList(params);
            });

            function showLoader(){
                $(".game_container").empty();
                $(".game_container").append( ' <div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>' );
            }

            function showGameRows(results){
                $(".game_container").empty();
                $("#search").val('');
                var init = false;
                $.each( results, function( key, value ) {
                    $( ".game_container" ).append(  '<div data-aos-once="true" data-aos="zoom-in" data-aos-offset="200" data-aos-delay="100" class="game_item col-xl-2 col-lg-3 col-md-3 col-sm-6 col-xs-12">'+
                                                    '<div class="game_thumb">'+
                                                        '<div class="game_img">'+
                                                            '<img src="'+value.game_image_url+'" alt="" >'+
                                                        '</div>'+
                                                        '<p class="game_name">'+value.game_name+'</p>'+
                                                        '<div class="game_hover">'+
                                                            '<a class="trial '+value.display_trial+'" href="'+value.game_trial_url+'">'+static_details.demo+'</a>'+
                                                        '</div>'+
                                                    '</div>'+
                                                '</div>'
                    );
                    if(results.length - 1 == key){
                        init = true;
                    }
                });

                if(init){
                    setTimeout(function(){
                        AOS.init();
                    }, 3000);
                }

            }

            function showNoResults(){
                $(".game_container").empty();
                $(".game_container").append( ' <div class="no-reult"><p>'+static_details.no_result+'</p></div>' );
            }

            function getGameList(params){
                showLoader();
                $.ajax({
                    url : '/async/get_gameList_by_platform/' + game_platform_id + '?' + $.param(params),
                    type : 'GET',
                    dataType : "json"
                }).done(function (obj) {
                    if(obj.length > 0){
                        showGameRows(obj);
                    } else {
                        showNoResults();
                    }

                }).fail(function (jqXHR, textStatus) {
                    if(jqXHR.status<300 || jqXHR.status>500){
                        alert(textStatus);
                    }
                });
            }

            var GAME_LIST = (function() {
                function loadGameList(){
                    var game_type = $('.game_cat').find("li.active").children("a").attr("data-id");
                    var onload_params = {
                        home_link : home_link,
                        language : language,
                        game_type: game_type,
                        append_target_db: append_target_db
                    };
                    getGameList(onload_params);
                }

                return {
                    get:function() {
                        loadGameList();
                    }
                }
            }());

            GAME_LIST.get();
        });
    </script>
</body>
</html>