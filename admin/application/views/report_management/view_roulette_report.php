<?php include APPPATH . "/views/includes/popup_promorules_info.php";?>
<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#viewRouletteReport" class="btn btn-xs btn-primary <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>
    <div id="viewRouletteReport" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="search-form" action="<?= site_url('/report_management/viewRouletteReport'); ?>" method="get">
                <div class="row">
                    <!-- Date -->
                    <div class="form-group col-md-3 col-lg-3">
                        <label class="control-label">
                            <?= lang('roulette_report.datetime'); ?>:
                        </label>
                        <input id="search_payment_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="true" autocomplete="off" />
                        <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>" />
                        <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>" />
                    </div>
                    <!-- username -->
                    <div class="form-group col-md-3 col-lg-3">
                        <label class="control-label">
                            <?=lang('Username'); ?>
                        </label>
                        <input type="text" name="by_username" id="by_username" value="<?= $conditions['by_username']; ?>" class="form-control input-sm group-reset" />
                    </div>

                    <!-- Roulette name -->
                    <div class="form-group col-md-3 col-lg-3">
                        <label for="by_roulette_name" class="control-label"><?=lang('roulette_report.roulette_name');?> </label>
                        <select name="by_roulette_name" id="by_roulette_name" class="form-control input-sm group-reset">
                            <option value=""><?=lang('All')?></option>
                            <?php foreach ($r_name as $ro_name => $ro_type) :?>
                                <?php if($conditions['by_roulette_name'] == $ro_type): ?>
                                    <option selected value="<?=$ro_type?>"><?=lang('roulette_name_'.$ro_type)?></option>
                                <?php else:?>
                                    <option value="<?=$ro_type?>"><?=lang('roulette_name_'.$ro_type)?></option>
                                <?php endif; ?>
                            <?php endforeach;?>
                        </select>
                    </div>

                    <!--Prize (amount/item) -->
                    <div class="form-group col-md-3 col-lg-3">
                        <label for="by_product_id" class="control-label"><?=lang('roulette_report.prize');?> </label>
                        <select name="by_product_id" id="by_product_id" class="form-control input-sm">
                            <option value=""><?=lang('All')?></option>
                            <?php foreach ($all_prize as $p_id => $p_name) :?>
                                <?php if($conditions['by_product_id'] == $p_id): ?>
                                    <option selected value="<?=$p_id?>"><?=$p_name?></option>
                                <?php else:?>
                                    <option value="<?=$p_id?>"><?=$p_name?></option>
                                <?php endif; ?>
                            <?php endforeach;?>
                        </select>
                    </div>

                    <!-- Promo Rule -->
                    <div class="form-group col-md-3 col-lg-3">
                        <label for="promoCmsSettingId" class="control-label"><?=lang('cms.promotitle')?></label>
                        <select name="promoCmsSettingId" id="promoCmsSettingId" class="form-control input-sm group-reset">
                            <option value=""><?=lang('All')?></option>
                            <?php foreach ($promoList as $promo) :?>
                                <?php if($conditions['promoCmsSettingId'] == $promo['promoCmsSettingId']): ?>
                                    <option selected value="<?=$promo['promoCmsSettingId']?>"><?=$promo['promoName']?></option>
                                <?php else:?>
                                    <option value="<?=$promo['promoCmsSettingId']?>"><?=$promo['promoName']?></option>
                                <?php endif; ?>
                            <?php endforeach;?>
                        </select>
                    </div>
                    
                    <!-- Under Affiliate -->
                    <?php if ($this->utils->getConfig('enable_show_and_search_affiliate_field')) { ?>
                        <div class="form-group col-md-3 col-lg-3">
                        <label class="control-label">
                            <?= lang('Under Affiliate')?>
                        </label>
                        <input type="text" name="by_affiliate" id="by_affiliate" value="<?= $conditions['by_affiliate']; ?>" class="form-control input-sm group-reset" />
                        </div>
                    <?php } ?>
                 </div>
                <div class="row">
                    <div class="form-group col-md-2 col-md-offset-10">
                        <div class="pull-right">
                            <input type="button" id="btnResetFields" value="<?=lang('lang.clear'); ?>" class="btn btn-sm btn-linkwater">
                            <button type="submit" class="btn btn-sm btn-portage"><?=lang("lang.search")?></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="icon-bullhorn"></i>
            <?=lang("Player Roulette Report")?>
        </h4>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-condensed" id="result_table">
                <thead>
                    <tr>
                        <th style="min-width:110px;"><?=lang("roulette_report.datetime")?></th>
                        <th><?=lang("Username")?></th>
                        <?php if ($this->utils->getConfig('enable_show_and_search_affiliate_field')) { ?>
                            <th><?=lang("Affiliate")?></th>
                        <?php } ?>    
                        <!-- <th><?=lang("roulette_report.deposit_amount")?></th> -->
                        <th><?=lang("cms.promotitle")?></th>
                        <th><?=lang('roulette_report.roulette_name')?></th>
                        <th style="min-width:110px;"><?=lang("roulette_report.prize_release_time")?></th>
                        <th><?=lang("roulette_report.prize")?></th>
                        <th><?=lang("roulette_report.spin_limit")?></th>
                        <th><?=lang("roulette_report.spin_count")?></th>
                        <th><?=lang('Withdraw Condition');?></th>
                        <th style="min-width:300px;"><?=lang("roulette_report.note")?></th>
                        <th><?=lang("Amount")?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr></tr>
                    <tr>
                        <?php if($this->utils->getConfig('enable_show_and_search_affiliate_field')){
                            $value = 11;
                        } else {
                            $value = 10;
                        }?>

                        <th colspan=<?=$value?> style="text-align:right"><?=lang('Subtotal')?>:</th>
                        <th><span id="sub_amount" class="text-right">0.00</span><br></th>
                    </tr>
                    <tr>
                        <th colspan=<?=$value?> style="text-align:right"><?=lang('summary_report.Total')?>:</th>
                        <th><span id="total_amount" class="text-right">0.00</span><br></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
    <form id="_export_csv_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' id = "json_csv_search" type="hidden">
    </form>
<?php }?>

<script type="text/javascript">

    $(document).ready(function(){

        $('#by_roulette_name').change(function(){
            var r_name = $('#by_roulette_name').val();
            var r_settings = <?=json_encode($r_settings)?>;
            $('#by_product_id').val('');
            $("#by_product_id").empty();
            $("#by_product_id").append($('<option>').text("<?=lang('All');?>").val('allStatus'));

            $.each(r_settings ,function (k,v) {
                if(k == r_name){
                    // if(v.used){
                    $.each(v ,function (key,val) {
                        $("#by_product_id").append($('<option>').val(val.product_id).text(val.prize));
                    });
                    // }
                    // else {
                    //     $("#by_product_id").append($('<option>').val(v.product_id).text(v.prize));
                    // }
                }
                //  else {
                //     $("#by_product_id option[value="+v.product_id+"]").remove();
                // }
            });
        }).trigger('submit');

        var dataTable = $('#result_table').DataTable({
            <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,
            searching: false,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: 'btn-linkwater',
                }
                <?php if($export_report_permission){ ?>
                ,{
                    text: "<?= lang('CSV Export'); ?>",
                    className:'btn btn-sm btn-portage',
                    action: function ( e, dt, node, config ) {
                        var form_params=$('#search-form').serializeArray();
                       var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': 'queue',
                            'draw':1, 'length':-1, 'start':0};
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/playerRouletteReport'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                    }
                }
                <?php } ?>
            ],
            columnDefs: [
                // { className: 'text-right', targets: [3,4] },
                { visible: false, targets: [9] },
            ],
            order: [ 0, 'desc' ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/playerRouletteReport", data, function(data) {
                    var subTotal =0;
                    var sub = 0;
                    var amountKey = 10;
                    <?php if($this->utils->getConfig('enable_show_and_search_affiliate_field')){ ?>
                        amountKey = 11;
                    <?php }?>

                    console.log('data',settings);
                    $.each(data.data, function(i, v){
                        sub = v[amountKey].replace(/<(?:.|\n)*?>/gm, '');
                        sub = sub.replace(/,(?=\d{3})/g, ''); //remove thousands_sep
                        if(Number.parseFloat(sub)){
                            subTotal+= Number.parseFloat(sub);
                        }
                    });
                    $('#sub_amount').text(parseFloat(subTotal).toFixed(2));
                    callback(data);
                    $('#total_amount').text(data.summary[0].total_amount);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                    }
                    else {
                        dataTable.buttons().enable();
                    }
                }, 'json');
            }
        });

        $('#btnResetFields').click(function() {
            $('.group-reset').val('');
            $("#by_product_id").empty();
            $("#by_product_id").append($('<option>').val('allStatus').text("<?=lang('All');?>"));
            $('.dateInput').data('daterangepicker').setStartDate(moment().startOf('day').format('Y-MM-DD HH:mm:ss'));
            $('.dateInput').data('daterangepicker').setEndDate(moment().endOf('day').format('Y-MM-DD HH:mm:ss'));
            dateInputAssignToStartAndEnd($('#search_withdrawal_date'));
        });
    });
</script>