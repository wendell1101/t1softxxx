<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title pull-left"><i class="icon-settings"></i> <?=lang('sys.gm.transactionsdailysummaryreportsettings');?></h4>
        <div class="clearfix"></div>
    </div>

    <div class="panel panel-body" id="details_panel_body">
        <div class="col-md-5 nopadding">
            <h3>
                <?=lang('Current Settings:')?>
            </h3>
            <span>
                <?="<b>".lang('cb_settings2')."</b> :".$conditions['day_starttime'].":00:00"?><br/>
                <?php //"<b>".lang('cb_settings3')."</b> :".$conditions['day_endtime'].":59:59"?><br/>
            </span>
            <br/><br/>
            <form>
            <table class="table table-bordered table-hover">
                <tbody>
                <tr><!--From Hour:-->
                    <th class="active"><?=lang('cb_settings2')?>:</th>
                    <td>
                        <select class="form-control" id="day_starttime" name="day_starttime">
                            <option value="00" <?= $conditions['day_starttime'] == "00"? "selected": ""?> >00</option>
                            <option value="01" <?= $conditions['day_starttime'] == "01"? "selected": ""?>>01</option>
                            <option value="02" <?= $conditions['day_starttime'] == "02"? "selected": ""?>>02</option>
                            <option value="03" <?= $conditions['day_starttime'] == "03"? "selected": ""?>>03</option>
                            <option value="04" <?= $conditions['day_starttime'] == "04"? "selected": ""?>>04</option>
                            <option value="05" <?= $conditions['day_starttime'] == "05"? "selected": ""?>>05</option>
                            <option value="06" <?= $conditions['day_starttime'] == "06"? "selected": ""?>>06</option>
                            <option value="07" <?= $conditions['day_starttime'] == "07"? "selected": ""?>>07</option>
                            <option value="08" <?= $conditions['day_starttime'] == "08"? "selected": ""?>>08</option>
                            <option value="09" <?= $conditions['day_starttime'] == "09"? "selected": ""?>>09</option>
                            <option value="10" <?= $conditions['day_starttime'] == "10"? "selected": ""?>>10</option>
                            <option value="11" <?= $conditions['day_starttime'] == "11"? "selected": ""?>>11</option>
                            <option value="12" <?= $conditions['day_starttime'] == "12"? "selected": ""?>>12</option>
                            <option value="13" <?= $conditions['day_starttime'] == "13"? "selected": ""?>>13</option>
                            <option value="14" <?= $conditions['day_starttime'] == "14"? "selected": ""?>>14</option>
                            <option value="15" <?= $conditions['day_starttime'] == "15"? "selected": ""?>>15</option>
                            <option value="16" <?= $conditions['day_starttime'] == "16"? "selected": ""?>>16</option>
                            <option value="17" <?= $conditions['day_starttime'] == "17"? "selected": ""?>>17</option>
                            <option value="18" <?= $conditions['day_starttime'] == "18"? "selected": ""?>>18</option>
                            <option value="19" <?= $conditions['day_starttime'] == "19"? "selected": ""?>>19</option>
                            <option value="20" <?= $conditions['day_starttime'] == "20"? "selected": ""?>>20</option>
                            <option value="21" <?= $conditions['day_starttime'] == "21"? "selected": ""?>>21</option>
                            <option value="22" <?= $conditions['day_starttime'] == "22"? "selected": ""?>>22</option>
                            <option value="23" <?= $conditions['day_starttime'] == "23"? "selected": ""?>>23</option>
                        </select>
                    </td>
                    <td>
                        <?=lang(':00:00')?>
                    </td>
                </tr>
                </tbody>
            </table>
            </form>
            <button id="saveTransactionDailySummaryReportSetting" class="btn btn-sm pull-right <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-info' ?>">
                            <?=lang('player.saveset');?>
                        </button>
            
        </div>
        <div class="clearfix"></div>
    </div>
</div>

<script>
$(document).ready(function () {
    var day_starttime = $("#day_starttime"),
        day_endtime = $("#day_endtime"),
        saveSetting = $("#saveTransactionDailySummaryReportSetting"),
        UPDATE_TRANSACTION_REPORT_SETTINGS_URL='<?php echo site_url('system_management/ajax_set_transaction_summary_setting') ?>';
    
    saveSetting.click(function(){
        saveSettings();
        return false;
    });

    function saveSettings() {
        console.log("savesettings");
        var ds = day_starttime.val();
            // de = day_endtime.val();

        var dataSettings = {
            "day_starttime" : ds,
            // "day_endtime" : de
        };
        // e.preventDefault();
        $.ajax({
                url : UPDATE_TRANSACTION_REPORT_SETTINGS_URL,
                type : 'POST',
                data : dataSettings,
                dataType : "json",
        }).done(function (obj) {
            console.log("result: "+obj);
            if(obj.status == "success"){
                BootstrapDialog.show({
                    "message": '<?php echo lang('Successfully Update Setting');?>',
                    "onhide": function(){
                        window.location.reload(true);
                    }
                });
            }
        }).fail(function (jqXHR, textStatus) {
            /*Note: this is for session timeout,if the session is out because this is ajax, eventually it will go to log in page*/
            if(jqXHR.status>=300 && jqXHR.status<500){
                location.reload();
            }else{
                alert(textStatus);
            }
        });
    }

    
});
</script>