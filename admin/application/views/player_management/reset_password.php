<form method="POST" name="player_reset_password" action="<?=site_url('player_management/playerResetPassword/' . $player['playerId'])?>" autocomplete="off">
    <div class="panel panel-primary">
        <div class="panel panel-body" id="player_panel_body">
            <div class="row">
                <div class="col-md-12">
                    <center>
                        <h4><?=lang('member.enter.password.message')?></h4>
                        <div class="input-group">
                            <input class="span_value form-control" type="text" id="pwd" name="password" placeHolder="<?=lang('member.enter.password')?>"  required/>
                            <span class="input-group-btn">
                                <button class="btn btn-default btn-md" type="button" id="reset_from"
                                    data-toggle="tooltip" data-placement="top" onclick="getGeneratePassword();" title="<?=lang('member.generate.random.password');?>">
                                    <i class="glyphicon glyphicon-refresh"></i>
                                </button>
                            </span>
                        </div>
                    </center>
                    <div class="col-md-11">
                        <center><span style="color:red;"><?=form_error('hiddenPassword');?></span></center>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <center>
        <input type="hidden" name="hiddenPassword"  class="form-control">
        <input type="submit" class="btn btn-primary submit_btn btn-sm" data-loading-text="<?=lang('Processing...');?>" value="<?=lang('lang.reset');?>">
        &nbsp;
        <a href="<?=site_url('player_management/userInformation/' . $player['playerId'])?>" class="btn btn-sm btn-warning btn-md" id="reset_password"><?=lang('lang.cancel');?></a>
    </center>
</form>
<script type="text/javascript">

    function getGeneratePassword() {
        html  = '';
        html += '<p>';
        html += 'Loading Data...';
        html += '</p>';

        $.ajax({
            'url' : base_url +'player_management/getGeneratePassword',
            'type' : 'GET',
            'dataType' : "html",
            'success' : function(data){
                $('.span_value').val('***********');
                $('#pwd').val(data);
            }
        });
    }
</script>

<script type="text/template" id="tpl-notify4changePassword">
    <div class="text-center notify-text notify4changingPassword">
        <?=lang('notify.132')?>
    </div>
    <div class="progress hide progress4changingPassword">
        <div data-percentage="0%" style="width: 100%;" class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <div class="text-center notify-text hide notify4changePassOk"><?=lang('notify.27')?></div>
    <div class="text-center notify-text hide notify4kickingOutGames"><?=lang('notify.133')?></div>
    <div class="progress hide progress4kickingOutGames">
        <div data-percentage="0%" style="width: 100%;" class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <div class="text-center notify-text hide notify4kickOutGames"><?=lang('notify.131')?></div>
    <div class="text-center notify-button hide">
            <button name="closeNotify" data-dismiss="modal" ><?=lang('Close')?></button>
    </div>
</script>