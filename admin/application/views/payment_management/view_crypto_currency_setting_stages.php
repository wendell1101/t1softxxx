<div class="panel panel-primary cryptocurrency">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title custom-pt pull-left">
            <i class="fa fa-cogs"></i> <?=lang('pay.crypto_currency_setting');?>
        </h4>
        <div class="clearfix"></div>
    </div>
    <div class="panel panel-body">
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <div class="well collapse" id="edit_panel_body">
                        <div class="panel-body">
                            <form class="form-horizontal" action="/payment_management/saveEditCryptoCurrencySetting" method="post" role="form">
                                <div class="form-group">
                                    <div class="col-md-4">
                                        <label for="editCryptoCurrency" class="control-label disabled"><?=lang('Cryptocurrency');?></label>
                                        <input type="text" name="editCryptoCurrency" id="editCryptoCurrency" class="form-control input-sm user-success" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="editTransation" class="control-label"><?=lang('Transaction Type');?></label>
                                        <input type="text" name="editTransation" id="editTransation" class="form-control input-sm user-success" readonly>
                                    </div>
                                    <div class="input-group col-md-4">
                                        <label for="editExchangeRateMultiplier" class="control-label"><?=lang('Exchange Rate Multiplier');?>
                                            <span style="color: #ea2f10;">* </span>
                                        </label>
                                        <input id="editExchangeRateMultiplier" min="0.0001" class="form-control input-sm user-success" type="number" step="0.0001" name="editExchangeRateMultiplier"
                                        required="required">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col text-center" style="margin-top: 15px;">
                                        <input type="submit" value="<?=lang('lang.save');?>" class="btn btn-linkwater">
                                        <input id="cancel" type="reset" value="<?=lang('lang.cancel');?>" class="btn btn-scooter">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <table class="table table-striped table-hover table-condensed table-bordered dataTable no-footer dtr-column collapsed crypto" id="my_table">
                        <thead>
                            <tr>
                                <th><?=lang('Cryptocurrency');?></th>
                                <th><?=lang('Transaction Type');?></th>
                                <th><?=lang('Exchange Rate Multiplier');?></th>
                                <th><?=lang('Last Updated On');?></th>
                                <th><?=lang('sys.gm.header.lastupdatedby');?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($cryptoCurrencySetting) && is_array($cryptoCurrencySetting)) { ?>
                                <?php foreach ($cryptoCurrencySetting as $row) { ?>
                                    <tr>
                                        <?php if($row['transaction'] == 'deposit'){ ?>
                                            <th><?= $row['crypto_currency'] ?></th>
                                            <td><span class="text-success"><?= $row['transaction'] ?></span></td>
                                        <?php }else { ?>
                                            <td></td>
                                            <td><span class="text-danger"><?= $row['transaction'] ?></span></td>
                                        <?php }?>
                                        <td><?= $row['exchange_rate_multiplier'] ?><a title="Edit exchange rate multiplier" class="blue editCryptoCurrencySetting" onclick="showEditModal('<?= $row['crypto_currency'] ?>','<?= $row['transaction'] ?>')">
                                            <span class="glyphicon glyphicon-pencil"></span></a></td>
                                        <td><?= $row['update_at'] ?></td>
                                        <td><?= $row['update_by'] ?></td>
                                    </tr>
                                <?php } ?>
                            <?php }?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>

<script type="text/javascript">
    function showEditModal(crypto_currency ,transaction){
        $('#edit_panel_body').show();
        $.ajax({
            'url' : base_url + 'payment_management/getCryptoCurrencySetting/' + crypto_currency + '/' + transaction,
            'type' : 'GET',
            'dataType' : "json",
            'success' : function (data) {
                $('#editCryptoCurrency').val(data[0].crypto_currency);
                $('#editTransation').val(data[0].transaction);
                $('#editExchangeRateMultiplier').val(data[0].exchange_rate_multiplier);
            }
        }, 'json');
    }

    $('#cancel').click(function() {
        $('#edit_panel_body').hide();
    });

    $(document).ready(function() {
        $('#edit_panel_body').hide();
    });
</script>