<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="fa"></i> <?=lang('daily currency list')?>
        </h4>
    </div>
    <div class="panel-body">
        <div class="table-responsive"  >
            <table class="table table-bordered table-hover" id="deposit-table">
                <div class="btn-action" style="margin-top:14px;">
                </div>
                <div class="clearfix"></div>
                <thead>
                    <tr>
                        <th>current rate date</th>
                        <th>base currency</th>
                        <th>target currency</th>
                        <th>rate</th>
                    </tr>
                    <?php foreach ($daily_currency as $count => $result): ?>
                        <tr>
                            <?php foreach ($result as $key => $value): ?>
                                <?php if($key != 'id'): ?>
                                    <td><?= $value ?></td>
                                <?php endif; ?>
                            <?php endforeach;?>
                        </tr>
                    <?php endforeach;?>
                </thead>
            </table>
        </div>
    </div>
</div>

