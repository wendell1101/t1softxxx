<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=lang('cashier.withdrawal.withdraw_condition');?></title>
    <link rel="stylesheet" type="text/css" href="<?= $this->utils->getSystemUrl('player','/resources/third_party/bootstrap/3.3.7/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script type="text/javascript" src="<?= $this->utils->getSystemUrl('player','/resources/third_party/jquery/jquery-3.1.1.min.js') ?>"></script>
    <script type="text/javascript" src="<?= $this->utils->getSystemUrl('player','/resources/third_party/bootstrap/3.3.7/bootstrap.js') ?>"></script>
</head>
    <body>
        <main class="withdrawal-condition">
            <h1 class="withdrawal-condition-title"><?=lang('cashier.withdrawal.withdraw_condition.title')?></h1>
            <div class="remaining-amount">
                <p class="remaining-bet"><?=lang('cashier.withdrawal.withdraw_condition.remain_betting') . ' ' . (!empty($wc['unfinished_bet'])?$wc['unfinished_bet']:'0');?></p>
                <p class="remaining-deposit"><?=lang('cashier.withdrawal.withdraw_condition.remain_deposit') . ' ' . (!empty($wc['unfinished_deposit'])?$wc['unfinished_deposit']:'0');?></p>
            </div>
            <div class="hint">
                <p class="hint_text"><?=lang('cashier.withdrawal.withdraw_condition.info')?></p>
            </div>
            <div class="withdrawal-condition-table table-responsive">
                <table id="withdrawalConditionResultTable" width="100%" class="table table-striped table-hover dt-responsive display nowrap"></table>
            </div>
        </main>

        <script src="<?=$this->utils->thirdpartyUrl('jquery/jquery-3.1.1.min.js') ?>"></script>
        <?php include VIEWPATH . '/resources/third_party/DataTable_history.php'; ?>

        <script type="text/javascript">
        var withdrawalcondistionTB;

        function withdrawalConditionHistory() {
            var table_container = $('#withdrawalConditionResultTable');

            if(withdrawalcondistionTB !== undefined){
                withdrawalcondistionTB.page.len($('#pageLength').val());
                withdrawalcondistionTB.ajax.reload();
                return false;
            }

            var columns = [];
            var i = 0;

            columns.push({
                "name": "transactionType",
                "title": "<?=lang('pay.transactionType')?>",
                "data": i++,
                "visible": true,
                "orderable": true
            });
            columns.push({
                "name": "promoName",
                "title": "<?=lang('pay.promoName')?>",
                "data": i++,
                "visible": true,
                "orderable": true
            });
            columns.push({
                "name": "depositAmount",
                "title": "<?=lang('cashier.53')?>",
                "data": i++,
                "visible": true,
                "orderable": true
            });
            columns.push({
                "name": "bonusAmount",
                "title": "<?=lang('Bonus')?>",
                "data": i++,
                "visible": true,
                "orderable": true
            });
            columns.push({
                "name": "startedAt",
                "title": "<?=lang('pay.startedAt')?>",
                "data": i++,
                "visible": true,
                "orderable": true
            });
            columns.push({
                "name": "withdrawalAmountCondition",
                "title": "<?=lang('pay.withdrawalAmountCondition')?>",
                "data": i++,
                "visible": true,
                "orderable": true
            });
            columns.push({
                "name": "bettingAmount",
                "title": "<?=lang('Betting Amount')?>",
                "data": i++,
                "visible": true,
                "orderable": true
            });
            columns.push({
                "name": "status",
                "title": "<?=lang('lang.status')?>",
                "data": i++,
                "visible": true,
                "orderable": true
            });

            withdrawalcondistionTB = table_container.DataTable($.extend({}, dataTable_options, {
                "responsive": false,
                "pageLength": $('#pageLength').val(),
                "columns": columns,
                columnDefs: [ {  // for the responsive extention to display control row button
                    className: 'control',
                    orderable: false,
                    targets:   -1
                }],
                order: [[4, 'desc']],
                ajax: {
                    url: '/api/playerWithdrawConditionDetails',
                    type: 'post',
                    data: function ( d ) {
                        if($('.withdrawal-condition-table table tr th').hasClass('sorting')){
                            $('.withdrawal-condition-table table tr th').addClass('hasSorting');
                        }
                    },
                }, fnRowCallback: function(nRow) {
                    $('td', nRow).css('color', '#fff').css('background-color', '#232323');
                }
                <?php if ($this->utils->getConfig('hide_player_center_history_list_controls_when_no_data')) : ?>
                // OGP-21311: drawCallback not working, use fnDrawCallback instead
                , fnDrawCallback: function() {
                    var wrapper = $(this).parents('.dataTables_wrapper');
                    var status = $(wrapper).find('.dt-row:last');
                    if ($(this).find('tbody td.dataTables_empty').length > 0) {
                        $(status).hide();
                    }
                    else {
                        $(status).show();
                    }
                }
                <?php endif; ?>

            }));
        }

        $(document).ready(function(){
            withdrawalConditionHistory();
        });

        </script>

    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin:0;
            padding: 0;
            background-color: #130e24;
            color: #fff;
            font-family: sans-serif;
        }
        .withdrawal-condition {
            padding: 40px;
        }
        .withdrawal-condition h1{
            font-size: 40px;
            color: #f2ff02;
            text-align: center;
        }
        .withdrawal-condition .remaining-amount {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .withdrawal-condition .remaining-amount p {
            border: 1px solid #322a4f;
            font-size: 20px;
            padding: 10px;
            margin: 5px;
            min-width: 400px;
            color: #f2ff02;
        }
        .withdrawal-condition .hint .hint_text{
            font-size: 25px;
            color: #ff0000;
            margin: auto;
            padding: 10px;
            text-align: center;
        }
        .withdrawal-condition-table {
            background: #232323;
            padding: 40px 15px;
            max-width: 95%;
            margin: 60px auto;
        }
        .withdrawal-condition-table table{
            width: 100%;
            text-align: left;
            border-collapse: collapse;
        }
        .withdrawal-condition-table table tr th,
        .withdrawal-condition-table table tr td {
            border: 1px solid #959595;
            padding: 10px;
        }
        .withdrawal-condition-table table tr th.hasSorting {
            background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAAQCAYAAAAS7Y8mAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyNpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDYuMC1jMDAyIDc5LjE2NDQ2MCwgMjAyMC8wNS8xMi0xNjowNDoxNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIDIxLjIgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkYxNEQ5MTZGN0RBNjExRUM5Qjk3Q0U2OEY2MEZBOUQyIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkYxNEQ5MTcwN0RBNjExRUM5Qjk3Q0U2OEY2MEZBOUQyIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6RjE0RDkxNkQ3REE2MTFFQzlCOTdDRTY4RjYwRkE5RDIiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6RjE0RDkxNkU3REE2MTFFQzlCOTdDRTY4RjYwRkE5RDIiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz63n8BhAAAA3UlEQVR42mL8//8/Q1xcHAMU9ACxLxA7AfFTBkywBohXQTHDokWLGHABJiR2FxAXA7EaEN8CYkUs6vWAWJyBCMACpVcAsQsQN0AN/A7E94DYEohPIKl/D5Uj2uAPUNeoQ4PBHohfALEUA5kAZnAGlPYDYm4ou5GBAsCExv8DxH8ZqACYGGgERg0efAYzIyVRig0OguZIEPgKxDehGWcGMRkEH3gGxGuhhj0AYhsgXg3Ee0kxmBOIBdHETkDLjytIubIXiEtIMfglEF/Cog7kUhUgPgjE2wgZCgIAAQYACYgn7523MDwAAAAASUVORK5CYII=');
            background-repeat: no-repeat;
            background-position: center right;
            padding-right: 25px;
        }
        .withdrawal-condition-table table.dataTable tbody tr{
            background-color: #232323;
        }
        .withdrawal-condition-table .dt-row div{
            color: #fff;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            box-sizing: border-box;
            display: inline-block;
            min-width: 1.5em;
            padding: 0.5em 1em;
            margin-left: 2px;
            text-align: center;
            text-decoration: none !important;
            cursor: pointer;
            color: #fff !important;
            border: 1px solid transparent;
            border-radius: 2px;
        }

        @media (max-width:768px) {
            main.withdrawal-condition {
                padding: 40px 20px;
            }
            main.withdrawal-condition .remaining-amount p {
                min-width: 100%
            }
            main.withdrawal-condition h1 {
                font-size: 25px;
            }
            main.withdrawal-condition .hint .hint_text{
                max-width: 100%;
                text-align: left;
                font-size: 16px;
            }
            main.withdrawal-condition .remaining-amount {
                flex-wrap: wrap;
            }
            main.withdrawal-condition .withdrawal-condition-table {
                max-width: 100%;
                padding: 20px 15px;
            }
        }
    </style>
    </body>
</html>
