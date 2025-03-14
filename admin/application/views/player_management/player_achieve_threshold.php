<style>
    .achieve_threshold_hint{
        font-size:12px; 
        color:#ff0000; 
        font-style: italic;
    }
</style>
<form method="POST" id="form-root" action="/player_management/set_dw_achieve_threshold">
    <?=$double_submit_hidden_field?>
    <div class="row" id="achieve_threshold_form">
        <div class="col-md-12 add_achieve_threshold_form" id="toggleView">
            <div class="panel-primary">
                <div class="panel-body" id="achieve_threshold_panel_body">
                    <input type="hidden" name="player_id" value="<?=$playerId;?>"/>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="deposit_achieve_threshold"><?=lang('sys.achieve.threshold.deposit');?>:</label>
                            <input type="number" id='deposit_achieve_threshold' class="form-control number_only" name="deposit_achieve_threshold" min="0" value="<?= empty($deposit_achieve_threshold) ? '' : $deposit_achieve_threshold?>"/>
                            <span id="d_achieve_threshold" class="achieve_threshold_hint"></span>
                            <span class="require_style"><?=form_error('deposit_achieve_threshold');?></span>
                        </div>
                        <div class="form-group">
                            <label for="withdrawal_achieve_threshold"><?=lang('sys.achieve.threshold.withdrawal');?>:</label>
                            <input type="number" id='withdrawal_achieve_threshold' class="form-control number_only" name="withdrawal_achieve_threshold" min="0" value="<?=empty($withdrawal_achieve_threshold) ? '' : $withdrawal_achieve_threshold?>"/>
                            <span id="w_achieve_threshold" class="achieve_threshold_hint"></span>
                            <span class="require_style"><?=form_error('withdrawal_achieve_threshold');?></span>
                        </div>
                    </div>
                </div>
            </div>
            <center>
                <input type="button" id='btn_add_achieve_threshold' class="btn btn-success" value="<?=lang('lang.save');?>"/>
                <button data-dismiss="modal" aria-label="Close" class="btn btn-warning"><?=lang('lang.cancel');?></button>
            </center>
        </div>
    </div>
</form>

<script type="text/javascript">

function refresh_achieve_threshold_info() {
    window.location.reload();
    $('#simpleModal').modal('hide');
}

function checkBankAccount() {
    var dataCorrect = true;
    var deposit_achieve_threshold = $("#deposit_achieve_threshold").val();
    var withdrawal_achieve_threshold = $("#withdrawal_achieve_threshold").val();
    var errText = "";
    //錯誤:空字串
    if((deposit_achieve_threshold === undefined && withdrawal_achieve_threshold === undefined) || (deposit_achieve_threshold === '' && withdrawal_achieve_threshold === '')) {
        dataCorrect= false;
        errText ="<?=lang('sys.achieve.threshold.err')?>";
    }
    
    $(".achieve_threshold_hint").text(errText);

    return dataCorrect;
}

$(document).ready(function () {
    $("#btn_add_achieve_threshold").click(function(e) {
        var errors = 0;

        if( checkBankAccount() ) {
            $(".achieve_threshold_hint").text("");
        } else {
            errors += 1;
        }

        if(errors == 0) {
            //disable first, wait return
            $("#btn_add_achieve_threshold").attr("disabled", true);
            //send ajax
            $.ajax({
                'url' : base_url +'player_management/set_dw_achieve_threshold/',
                'type' : 'POST',
                'data': $("#form-root").serialize(),
                'dataType' : "json",
                'success' : function(data){
                    if (data.success === false) {
                        $("#d_achieve_threshold").text(data.message);
                        $("#w_achieve_threshold").text(data.message);
                        //release btn lock
                        $("#btn_add_achieve_threshold").attr("disabled", false);
                    } else {
                        //success, refresh and show model
                        $('#mainModal').modal('hide');

                        var title = "<?=lang('sys.achieve.threshold.title');?>";
                        var content = "<?=lang('sys.achieve.threshold.success')?>";
                        var button = '<button class="btn btn-sm btn-scooter" onclick="refresh_achieve_threshold_info()"><?=lang('OK')?></button>';

                        success_modal_custom_button(title, content, button);
                    }
                },
                error : function(data) {
                    //release btn lock
                    $("#btn_add_achieve_threshold").attr("disabled", false);
                    console.log("error data:" +data);
                    console.log("jsondata = " + JSON.stringify(data));
                }
            },'json');
        }
    });
});


</script>