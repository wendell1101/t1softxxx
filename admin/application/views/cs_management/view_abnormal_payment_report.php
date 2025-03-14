<div class="panel panel-primary hidden">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-search"></i> <?=lang("lang.search")?>
            <span class="pull-right">
                <a data-toggle="collapse" href="#collapseAbnormalPayment" class="btn btn-xs btn-primary <?=$this->config->item('default_open_search_panel') ? '' : 'collapsed'?>"></a>
            </span>
        </h4>
    </div>
    <div id="collapseAbnormalPayment" class="panel-collapse <?=$this->config->item('default_open_search_panel') ? '' : 'collapse in'?>">
        <div class="panel-body">
            <form id="search-form" action="<?= site_url('/cs_management/view_abnormal_payment_report'); ?>" method="get">
                <div class="row">
                    <!-- Date -->
                    <div class="form-group col-md-3 col-lg-3">
                        <label class="control-label">
                            <?= lang('cs.abnormal.payment.datetime'); ?>
                        </label>
                        <input id="search_payment_date" class="form-control input-sm dateInput user-success" data-start="#by_date_from" data-end="#by_date_to" data-time="true" autocomplete="off" />
                        <input type="hidden" id="by_date_from" name="by_date_from" value="<?=$conditions['by_date_from'];?>" />
                        <input type="hidden" id="by_date_to" name="by_date_to" value="<?=$conditions['by_date_to'];?>" />
                    </div>

                    <!-- status -->
                   <div class="form-group col-md-3 col-lg-3">
                       <label for="by_status" class="control-label"><?=lang('cs.abnormal.payment.status');?> </label>
                       <?=form_dropdown('by_status', $status_list, $conditions['by_status'], 'class="form-control input-sm iovation_report_status by_status"'); ?>                        
                   </div>

                   <!-- type -->
                   <div class="form-group col-md-3 col-lg-3">
                       <label for="by_type" class="control-label"><?=lang('cs.abnormal.payment.type');?> </label>
                       <?=form_dropdown('by_type', $type_list, $conditions['by_type'], 'class="form-control input-sm iovation_report_status by_type"'); ?>                        
                   </div>

                    <!-- operator -->
                    <div class="form-group col-md-3 col-lg-3">
                        <label  class="control-label"><?=lang('cs.abnormal.payment.operator');?></label>
                        <select name="update_by" id="update_by" class="form-control input-sm">
                            <option value=""><?=lang('sys.vu05');?></option>
                            <?php if (!empty($user_group)) { ?>
                                <?php foreach ($user_group as $user) {?>
                                    <option value="<?=$user['userId']?>" <?=(($conditions['update_by'] == $user['userId']) ? ' selected="selected"' : '')?>><?=$user['username']?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </div>
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
            <i class="glyphicon glyphicon-exclamation-sign"></i>
            <?=lang("cs.abnormal.payment.report")?>
        </h4>
    </div>
    <form action="<?=site_url('cs_management/setAbnormalStatusToRead')?>" method="post" role="form">
        <div class="panel-body" id="chat_panel_body">
            <div class="">
                <?php if($this->CI->permissions->checkPermissions('adjust_abnormal_payment_report_status')) {?>
                    <button type="submit" id="set_read" class="btn btn-danger btn-sm hide" data-toggle="tooltip" data-placement="top" title="<?=lang('cs.abnormal.payment.set.read');?>" onclick="return confirm('<?=lang('sys.sure');?>')">
                        <i class="glyphicon glyphicon-cog" style="color:white;"></i>
                        <?php echo lang('cs.abnormal.payment.set.read'); ?>
                    </button>
                <?php } ?>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-condensed table-bordered" id="result_table">
                    <thead>
                        <tr>
                            <th style="padding: 8px" class="th_chk_multiple"><input type="checkbox" id="checkWhite" onclick="checkAll(this.id)"/></th>
                            <th><?=lang("cs.abnormal.payment.status")?></th>
                            <th><?=lang("cs.abnormal.payment.type")?></th>
                            <th><?=lang("cs.abnormal.payment.operator")?></th>
                            <th><?=lang("cs.abnormal.payment.player")?></th>
                            <th><?=lang("cs.abnormal.payment.abnormal_payment_name")?></th>
                            <th><?=lang("cs.abnormal.payment.created_at")?></th>
                            <th><?=lang("cs.abnormal.payment.update_at")?></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </form>
    <div class="panel-footer"></div>
</div>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php }?>

<script type="text/javascript">
    $(document).ready(function(){
        var dataTable = $('#result_table').DataTable({
            stateSave: true,
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,
            searching: false,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: 'btn-linkwater',
                }
                <?php if( $export_report_permission ){ ?>
                ,{
                    text: "<?= lang('CSV Export'); ?>",
                    className:'btn btn-sm btn-portage',
                    action: function ( e, dt, node, config ) {
                        var form_params=$('#search-form').serializeArray();
                       var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': 'queue',
                            'draw':1, 'length':-1, 'start':0};
                            $("#_export_excel_queue_form").attr('action', site_url('/export_data/abnormalPaymentReport'));
                            $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                            $("#_export_excel_queue_form").submit();
                    }
                }
                <?php } ?>
            ],
            columnDefs: [
                {
                    sortable: false,
                    targets: [0]
                },
            ],
            order: [[6, 'desc']],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/abnormalPaymentReport", data, function(data) {
                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                        $('#set_read').addClass('hide');

                    }
                    else {
                        dataTable.buttons().enable();
                        $('#set_read').removeClass('hide');

                    }
                }, 'json');
            }
        });

        $('#btnResetFields').click(function() {
            $(".by_status").val("2");
            $(".by_type").val("");
            $("#update_by").val("");
            $('.dateInput').data('daterangepicker').setStartDate(moment().startOf('day').format('Y-MM-DD HH:mm:ss'));
            $('.dateInput').data('daterangepicker').setEndDate(moment().endOf('day').format('Y-MM-DD HH:mm:ss'));
            dateInputAssignToStartAndEnd($('#search_withdrawal_date'));
        });
    });

    function checkAll(id) {
        var list = document.getElementsByClassName(id);
        var all = document.getElementById(id);

        if (all.checked) {
            for (i = 0; i < list.length; i++) {
                list[i].checked = 1;
            }
            $("#delete_form").attr("onsubmit","return confirmDelete();");

        } else {
            for (i = 0; i < list.length; i++) {
                list[i].checked = 0;
            }

            $("#delete_form").attr("onsubmit","");
        }
    }

    function uncheckAll(id) {
        var list = document.getElementById(id).dataset.checkedAllFor;
        var all = document.getElementById(list);

        var item = document.getElementById(id);
        var allitems = document.getElementsByClassName(list);
        var cnt = 0;

        if (item.checked) {
            for (i = 0; i < allitems.length; i++) {
                if (allitems[i].checked) {
                    cnt++;
                }
            }

            if (cnt == allitems.length) {
                all.checked = 1;
            }
        } else {
            all.checked = 0;
        }
    }
</script>