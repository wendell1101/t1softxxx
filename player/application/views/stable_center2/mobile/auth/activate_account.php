<div class="row">
    <div class="col-md-12">
        <div class="panel panel-og">
            <div class="panel-heading" id="add_player_panel_heading">
                <h4 class="panel-title pull-left"><?=lang('reg.68');?></h4>
                <div class="clearfix"></div>
            </div>

            <div class="panel panel-body" id="add_player_panel_body">

                <ol class="breadcrumb">
                    <li class="active"><?=lang('reg.49');?></li>
                    <li class="active"><b><?=lang('reg.63');?></b></li>
                    <li class="active"><?=lang('reg.50');?></li>
                    <li class="active"><?=lang('reg.51');?></li>
                </ol>

                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <center> <?=lang('reg.52');?> </center>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <blockquote style="padding-bottom:0;">
                                    <h2><b><?=lang('reg.54');?></b></h2>
                                </blockquote>

                                <blockquote>
                                    <?=lang('reg.64');?>
                                    <?php if($this->utils->getConfig('removed_link_and_cursor_on_email')) : ?>
                                        <b class="pl-email"><?=$player['email']?></b>.</br>
                                    <?php else: ?>
                                        <a href="#"><?=$player['email']?></a>.</br>
                                    <?php endif; ?>
                                    <?=lang('reg.65');?> <?=lang('reg.70');?>
                                </blockquote>

                                <blockquote>
                                    <form method="post" action="<?=BASEURL . 'iframe_module/resendEmail';?>">
                                        <?=lang('reg.66');?>:
                                        <input type="submit" class="btn btn-hotel btn-sm" value="<?=lang('reg.67');?>"/>

                                        <?php if($this->utils->getConfig('activate_home_butoon_redirect_menu_page')) : ?>
                                            <a href="<?php echo site_url('player_center/menu')?>" class="btn btn-hotel btn-sm"><?=lang('button.go_home')?></a>
                                        <?php else: ?>
                                           <a href="<?php echo site_url('iframe_module/iframe_viewCashier')?>" class="btn btn-hotel btn-sm"><?=lang('button.go_home')?></a>
                                        <?php endif; ?>

                                        <input type="hidden" value="<?=$player['playerId'];?>" name="playerId">
                                    </form>
                                </blockquote>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $("body").addClass("reg_activate");
    });
</script>