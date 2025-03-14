<div class="t1t-widget widget-sidebar right-side hidden-xs">
    <ul class="right-menu">
        <?php if ($this->utils->isEnabledFeature('player_center_sidebar_deposit') && ($playerStatus!=5)) :?>
        <li>
            <a id="fund_management" class="link" href="<?=site_url('/player_center2/deposit')?>">
                <img src="<?=base_url() . $this->utils->getPlayerCenterTemplate()?>/img/side-icon2.png">
                <span class=""><?php echo lang("Fund Management") ?></span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($this->utils->isEnabledFeature('player_center_sidebar_message')) :?>
        <li>
            <a id="message" class="link" href="<?=site_url('player_center2/messages')?>">
                <img src="<?=base_url() . $this->utils->getPlayerCenterTemplate()?>/img/side-icon3.png">
                <span class=""><?php echo lang("lang.message") ?></span>
            </a>
        </li>
        <?php endif; ?>
        <?php if ($this->utils->isEnabledFeature('enable_player_center_live_chat')) :?>
        <li>
            <a id="live_chat" class="link sidebar_live_chat" href="javascript:void(0)" onclick="<?=$this->utils->getLiveChatOnClick();?>" target="livechat">
                <i class="fa fa-comments-o" aria-hidden="true" style="font-size: 22px; margin-top: 3px; margin-left: 5px;"></i>
                <span class=""><?php echo lang("Live Chat") ?></span>
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="right-content tab-content"></div>
</div>
<script type="text/javascript">
    $(function(){
        // Right side menu (show right side content)
        $(".right-menu li a").click(function(e) {
            if ($(this).hasClass("guide-tour")) {
                tour.restart();
            } else if($(this).hasClass("link")) {
                if($(this).hasClass("sidebar_live_chat")){
                    return e.preventDefault();
                }

                <?php if ($this->utils->isEnabledFeature('enabled_player_center_preloader')) : ?>
                    $(".preloader").removeClass("preloader-out");
                <?php endif; ?>
            } else {
                $(".right-side").addClass("active");
                e.preventDefault();
            }
        });
        // Right side menu (hide right side content)
        $(document).mouseup(function(e){
            var _con = $('.right-side');
            if(!_con.is(e.target) && _con.has(e.target).length === 0){
                $(".right-side").removeClass("active");
                $(".right-menu li").removeClass("active");
            }
        });
    });
</script>
<script type="text/javascript">
    function createstars(n) {
    return new Array(n+1).join("*")
    }

    $(document).ready(function(){
        $("#dashboard").addClass("active");

        <?php if($this->utils->getConfig('enable_hide_show_username_player_center')) : ?>
        $( "#uname_hidden" ).click(function() 
        {
            var player_uname_len = $("#player_uname").html().length;
            var player_uname = $("#player_uname").html();
            
            $("#player_uname").html(player_uname.substring(1,0) + createstars(player_uname_len-2) + player_uname.substring(player_uname_len-1,player_uname_len));
            $('#uname_hidden').hide();
            $('#uname_show').show();
            
        });

        $( "#uname_show" ).click(function() 
        {
            var player_uname_len = $("#player_uname").html().length;
            var player_uname = $("#hidden_uname").html();
            
            
            $("#player_uname").html(player_uname);
            $('#uname_show').hide();
            $('#uname_hidden').show();
            
        });
        <?php endif; ?>
    });
</script>