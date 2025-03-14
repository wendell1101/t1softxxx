<?php
  if(is_array($this->config->item('cryptocurrencies')) && in_array("USDT", $this->config->item('cryptocurrencies'))){
        $enabled_usdt = true;
    }else{
        $enabled_usdt = false;
    }
?>
<form id="search-form">
    <input type="hidden" name="locked_user_id" value="1">
    <input type="hidden" name="dwStatus" value="requestAll">
</form>
<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa fa-lock"></i> <?=lang('Locked Deposit List')?>
        </h4>
    </div>
    <div class="panel-body">
        <div class="table-responsive"  >
            <table class="table table-bordered table-hover" id="deposit-table">
                <div class="btn-action" style="margin-top:14px;">
                    <button type="button" class="btn btn-emerald btn-sm" id="unlockTransBtn">
                        <i class="fa fa-unlock" style="color:white;" title="Add"></i> <?= lang('Unlock Deposit'); ?>
                    </button>
                </div>
                <div class="clearfix"></div>
                <thead>
                    <tr>
                        <th><?=lang('lang.action'); // #1 ?></th>
                        <th><?=lang('lang.status'); // #2 ?></th>
                        <th><?=lang('deposit_list.order_id'); // #3 ?></th>
                        <th><?=lang('system.word38'); // #4 ?></th>
                        <th><?=lang('player.38');// OGP-28145?></th>
                        <?php if ($this->utils->getConfig('enable_split_player_username_and_affiliate')) { ?>
                            <th><?=lang("Affiliate") // #5 ?></th> <!-- 4 -->
                        <?php } ?>
                        <th><?=lang('pay.payment_account_flag'); // #6 ?></th>
                        <th><?=lang('pay.reqtime'); // #7 ?></th>
                        <th><?=lang('Deposit Datetime'); // #8 ?></th>
                        <th><?=lang('pay.spenttime'); // #9 ?></th>
                        <th><?=lang('sys.vu40'); // #10 ?></th>
                        <th class="hidden_aff_th"><?=lang("Affiliate") // #11 ?></th>
                        <th><?=lang('pay.playerlev'); // #12 ?></th>
                        <?php if($this->utils->getConfig('enabled_player_tag_in_deposit')) :?>
                            <th><?=lang("Tag") // #13 ?></th>
                        <?php endif; ?>
                        <?php if($enabled_usdt) :?>
                            <th><?=lang('Received crypto'); // #14 ?></th>
                        <?php endif; ?>
                        <th><?=lang('Deposit Amount'); // #15 ?></th>
                        <?php if($this->utils->getConfig('enable_cpf_number')) :?>
                            <th><?=lang('financial_account.CPF_number'); // #16 ?></th>
                        <?php endif; ?>
                        <th><?=lang('transaction.transaction.type.3'); // #17 ?></th>
                        <th><?=lang('pay.collection_name'); // #18 ?></th>
                        <th><?=lang('deposit_list.ip'); // #19 ?></th>
                        <th><?=lang('pay.updatedon'); // #20 ?></th>
                        <th><?=lang('cms.timeoutAt'); // #21 ?></th>
                        <th><?=lang('pay.procsson'); // #22 ?></th>
                        <th><?=lang('pay.collection_account_name'); // #23 ?></th>
                        <th><?=lang('con.bnk20'); // #24 ?></th>
                        <th><?=lang('pay.deposit_payment_name'); // #25 ?></th>
                        <th><?=lang('pay.deposit_payment_account_name'); // #26 ?></th>
                        <th><?=lang('pay.deposit_payment_account_number'); // #27 ?></th>
                        <!-- <th><?=lang('pay.deposit_transaction_code'); // #28 by OGP-26797 ?></th> -->
                        <th><?=lang('cms.promotitle'); // #29 by OGP-26798 ?></th>
                        <th><?=lang('Promo Request ID'); // #30 ?></th>
                        <th><?=lang('pay.promobonus'); // #31 ?></th>
                        <th><?=lang('External ID'); // #32 ?></th>
                        <th><?=lang('Bank Order ID'); // #33 ?></th>
                        <?php if ($this->utils->isEnabledFeature('enable_deposit_datetime')) { ?>
                            <th><?=lang('Deposit Datetime From Player'); // #34 ?></th>
                        <?php } ?>
                        <th><?=lang('Mode of Deposit'); // #35 ?></th>
                        <th style="min-width:200px;"><?=lang('Player Deposit Note'); // #36 ?></th>
                        <th style="min-width:400px;"><?=lang('pay.procssby'); // #37 ?></th>
                        <th style="min-width:400px;"><?=lang('External Note'); // #38 ?></th>
                        <th style="min-width:400px;"><?=lang('Internal Note'); // #39 ?></th>
                        <th style="min-width:600px;"><?=lang('Action Log'); // #40 ?></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" style="margin-top:50px !important;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title"><?= lang('Unlock Deposit') ?></h4>
            </div>
            <input type="hidden" id="hiddenId">
            <div class="modal-body">
                <?= lang('Are you sure you want to unlock selected deposit transactions?'); ?>
            </div>
            <div class="modal-footer">
                <a data-dismiss="modal" class="btn btn-default"><?= lang('lang.no'); ?></a>
                <a class="btn btn-primary" id="deleteBtn"><i class="fa"></i> <?= lang('lang.yes'); ?></a>
            </div>
        </div>
    </div>
</div>

<?php if($this->utils->isEnabledFeature('export_excel_on_queue')){?>
    <form id="_export_excel_queue_form" class="hidden" method="POST" target="_blank">
        <input name='json_search' type="hidden">
    </form>
<?php }?>

<script type="text/javascript">
    var enable_split_player_username_and_affiliate = '<?=$this->utils->getConfig('enable_split_player_username_and_affiliate')?>';
    var not_visible_target = '';
    var text_right = '';
    <?php if(!empty($this->utils->getConfig('deposit_list_columnDefs'))) : ?>
		<?php if(!empty($this->utils->getConfig('deposit_list_columnDefs')['not_visible_payment_management'])) : ?>
			not_visible_target = JSON.parse("<?=json_encode($this->utils->getConfig('deposit_list_columnDefs')['not_visible_payment_management']) ?>" ) ;
		<?php endif; ?>
		<?php if(!empty($this->utils->getConfig('deposit_list_columnDefs')['className_text-right_payment_management'])) : ?>
			text_right = JSON.parse("<?= json_encode($this->utils->getConfig('deposit_list_columnDefs')['className_text-right_payment_management']) ?>" ) ;
		<?php endif; ?>
	<?php endif; ?>

    var checkedRows = [];
    var export_type = "<?=$this->utils->isEnabledFeature('export_excel_on_queue') ? 'queue' : 'direct';?>";
    var $excelForm  = $('#_export_excel_queue_form');

    $(document).ready( function() {

        if (enable_split_player_username_and_affiliate) {
            rowNum = $(".hidden_aff_th").index();
            $("#deposit-table thead th:eq("+rowNum+")").remove();
        }

        var params = {
            'extra_search'  : $('#search-form').serializeArray(),
            'draw'          : 1,
            'length'        : -1,
            'start'         : 0
        };
        var hidden_colvis = '';
        <?php if (!empty($this->utils->getConfig('hidden_colvis_for_deposit_list_locked_deposit'))) : ?>
            var hidden_colvis_arr = JSON.parse("<?= json_encode($this->utils->getConfig('hidden_colvis_for_deposit_list_locked_deposit')) ?>");
                hidden_colvis = formatHiddenColvisStr(hidden_colvis_arr);
        <?php endif; ?>

        $('#deposit-table').DataTable({
            autoWidth: false,
            searching: false,
            <?php if($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            pageLength: <?=$this->utils->getDefaultItemsPerPage()?>,
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l>" +
                 "<'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p>" +
                 "<'dt-information-summary2 text-info pull-left' i>>",
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: ['colvisRestore'],
                    className: 'btn-linkwater',
                    columns: hidden_colvis
                },
                <?php if($this->permissions->checkPermissions('export_deposit_lists')): ?>
                    {
                        text: "<?=lang('CSV Export'); ?>",
                        className: 'btn btn-sm btn-portage',
                        action: function (e, dt, node, config) {
                            <?php if($this->utils->isEnabledFeature('export_excel_on_queue')): ?>
                                var form_params=$('#search-form').serializeArray();
                                var d = {'extra_search': form_params, 'export_format': 'csv', 'export_type': export_type,
                                    'draw':1, 'length':-1, 'start':0};

                                $("#_export_excel_queue_form").attr('action', site_url('/export_data/depositList/null/true'));
                                $("#_export_excel_queue_form [name=json_search]").val(JSON.stringify(d));
                                $("#_export_excel_queue_form").submit();
                            <?php else: ?>
                                $.post(site_url('/export_data/depositList/null/true'), params, function (data) {
                                    if (data && data.success) {
                                        $('body').append('<iframe src="' + data.link + '" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
                                    } else {
                                        alert('export failed');
                                    }
                                });
                            <?php endif; ?>
                        }
                    }
                <?php endif; ?>
            ],
            columnDefs: [
				{ sortable: false, targets: [ 0 ] },
				{ visible: false, targets: not_visible_target },
				{ className: 'text-right', targets: text_right },
				<?php if($this->utils->isEnabledFeature('close_aff_and_agent')): ?>
					{ targets: [ 8 ], className: "noVis hidden" },
				<?php endif?>
			],
			order: [[ <?=$defaultSortColumn;?>, 'desc']],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post("/api/lockedDepositList/", data, function (data) {
                    callback(data);
                    if( $('#deposit-table').DataTable().rows( { selected: true } ).indexes().length === 0 ) {
                        $('#deposit-table').DataTable().buttons().disable();
                    }
                    else {
                        $('#deposit-table').DataTable().buttons().enable();
                    }
                }, 'json');
            },
            drawCallback : function() {
                $("input[type='checkbox']").on('click', function(){
                    var id = $(this).val();
                    if( $(this).is(":checked") ) {
                        checkedRows.push(id);
                    } else {
                        var index = checkedRows.indexOf(id);
                        if (index > -1) {
                            checkedRows.splice(index, 1);
                        }
                    }
                    console.log(checkedRows);
                });
            }
        });
    });

    $('#unlockTransBtn').on('click', function() {
        if(checkedRows.length > 0) {
            $('#deleteModal').modal('show');
        } else {
            clearNotify();
            $.notify('<?= lang('No locked transaction selected!'); ?>',{type: 'danger'});
        }
    });

    $('#deleteBtn').on('click', function(){
        clearNotify();
        $(this).find('i').addClass('fa-refresh fa-spin');
        $.post('/payment_management/batchUnlockTransaction', {saleOrdersId : checkedRows}, function(){
            successUnlocked();
        });
    });
    function formatHiddenColvisStr(hidden_colvis_arr){
        var format_hidden_colvis_str = "";
        var hidden_array= [];

        $.each(hidden_colvis_arr ,function (k,v) {
            v = v+1;
            format_hidden_colvis_str = ':not(:nth-child('+ v +'))';
            hidden_array.push(format_hidden_colvis_str);
        });

        return hidden_array.join("");;
    }

    function clearNotify() {
        $.notifyClose('all');
    }

    function successUnlocked() {
        $('#deleteBtn').find('i').removeClass('fa-refresh fa-spin');
        $('#deleteModal').modal('hide');
        $.notify('<?= lang('Deposit transaction successfully unlocked'); ?>',{type: 'success'});
        $('#deposit-table').DataTable().ajax.reload(null,false);
        checkedRows = [];
    }
</script>

