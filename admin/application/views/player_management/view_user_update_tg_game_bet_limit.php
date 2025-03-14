<div class="panel panel-primary" id="game_form" style="width: 40%;margin: 0 auto;">
    <div class="panel-heading">
        <h4 class="panel-title"> <a href="#game_info_update" id="hide_game_info" class="btn btn-primary btn-sm">
                <i class="glyphicon glyphicon-chevron-up" id="hide_game_up"></i></a> &nbsp;
            <strong><?= $system_code ?> <?= lang('Bet Setting') ?></strong>
        </h4>
    </div>
    <div class="panel-body" id="game_panel_body">
        <div class="row">
            <div class="col-md-4" style="padding:20px; left: 200px;">
                <div class="form-group">
                    <ul class="list-group">
                        <li class="list-group-item"><?php echo lang('Username'); ?> : <b><?= $username ?></b> </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12" style="padding:20px">
                <form class="form-horizontal" id="append_form">
                    <div class="form-group">
                        <div class="form-group">
                            <label class="control-label col-md-3" style="left: 50px" for="min_bet"><?php echo lang('Minimum Bet'); ?> :</label>
                            <div class="col-sm-6">
                            <input type="text" class="form-control" id="min_bet" placeholder="<?php echo lang('Enter minimum bet'); ?>" required>  
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-3" style="left: 50px" for="max_bet"><?php echo lang('Maximum Bet'); ?> :</label>
                            <div class="col-sm-6">
                            <input type="text" class="form-control" id="max_bet" placeholder="<?php echo lang('Enter maximum bet'); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3" style="left: 50px" for="min_tie"><?php echo lang('Minimum Tie'); ?> :</label>
                            <div class="col-sm-6">
                            <input type="text" class="form-control" id="min_tie" placeholder="<?php echo lang('Enter minimum tie'); ?>" required>  
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-3" style="left: 50px" for="max_tie"><?php echo lang('Maximum Tie'); ?> :</label>
                            <div class="col-sm-6">
                            <input type="text" class="form-control" id="max_tie" placeholder="<?php echo lang('Enter maximum tie'); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-md-3" style="left: 50px" for="min_pair"><?php echo lang('Minimum Pair'); ?> :</label>
                            <div class="col-sm-6">
                            <input type="text" class="form-control" id="min_pair" placeholder="<?php echo lang('Enter minimum pair'); ?>" required>  
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-3" style="left: 50px" for="max_pair"><?php echo lang('Maximum Pair'); ?> :</label>
                            <div class="col-sm-6">
                            <input type="text" class="form-control" id="max_pair" placeholder="<?php echo lang('Enter maximum pair'); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col" style="text-align: center">
                            <button type="button" class="btn btn-primary btn_update_casino_bet"><?= lang('Update Bet Limit') ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>

<script type="text/javascript">

    $(document).on("click", ".btn_update_casino_bet",function(){
        event.preventDefault();
        var gameId = "<?php echo $game_platform_id; ?>";
        var playerId = "<?php echo $player_id; ?>";
        var min_bet = $("#min_bet").val();
        var max_bet = $("#max_bet").val();
        var min_tie = $("#min_tie").val();
        var max_tie = $("#max_tie").val();
        var min_pair = $("#min_pair").val();
        var max_pair = $("#max_pair").val();
        var data = JSON.stringify({
            min_bet:min_bet,
            max_bet:max_bet,
            min_tie:min_tie,
            max_tie:max_tie,
            min_pair:min_pair,
            max_pair:max_pair
        });
        console.log(data)
        var url = "/async/update_player_tg_bet_setting";
        $.ajax({
            type: "POST",
            url: url,
            dataType: 'json',
            data: {
                data:data,
                playerId:playerId,
                gameId:gameId
            },
            success: function(data){
                console.log(data)
                if(data.success == true){
                    alert("<?php echo lang('Update success!'); ?>");
                    location.reload();
                } else {
                    alert("<?php echo lang('Try again!'); ?>");
                }
            }
        });
    });

    $(document).on("keypress","#min_bet,#max_bet",function(e){
        //if the letter is not digit then display error and don't type anything
        if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
            alert('Must input Integer');
            return false;
        }
    });



</script>