<div data-file-info="ajax_ui_quest_report.php" data-datatable-selector="#questreport-table">
    <form class="form-inline" id="search-form">
        <input type="text" id="reportrange" class="form-control input-sm dateInput inline" data-start="#date_from" data-end="#date_to" data-time="true" />
        <input type="hidden" id="date_from" name="date_from"/>
        <input type="hidden" id="date_to" name="date_to"/>
        <?php
            $default_timezone = $this->utils->getTimezoneOffset(new DateTime());
            $timezone_offsets = $this->utils->getConfig('timezone_offsets');
            $timezone_location = $this->utils->getConfig('current_php_timezone');
            $force_default_timezone = $this->utils->getConfig('force_default_timezone_option');
        ?>
        <select id="timezone" name="timezone"  class="form-control input-sm">
        <?php if(!$force_default_timezone): ?>
             <?php for($i = 12;  $i >= -12; $i--): ?>
                <option value="<?php echo $i > 0 ? "+{$i}" : $i ;?>" <?php echo ($i==$default_timezone) ? 'selected' : ''?>> <?php echo $i >= 0 ? "+{$i}" : $i ;?></option>
            <?php endfor;?>
        <?php else: ?>
            <option value="<?=$force_default_timezone;?>" selected> <?= $force_default_timezone;?></option>
        <?php endif;?>
        </select>
        <input type="button" class="btn btn-portage btn-sm" id="btn-submit" value="<?=lang('lang.search');?>"/>

        <input type="hidden" name="search_by" value="2">
        <input type="hidden" name="player_id" value="<?= $player_id ?>">
        <input type="hidden" name="request_type" value="">
        <input type="hidden" name="request_grade" value="">
        <input type="hidden" name="level_from" value="">
        <input type="hidden" name="level_to" value="">
        <input type="hidden" name="search_type" value="releaseTime">
    </form>
    <hr />
    <div class="clearfix">
        <table id="questreport-table" class="table table-bordered">
            <thead>
                <tr>
                    <th><?=lang('report.qr01')?></th>
                    <th><?=lang('report.pr01')?></th>
                    <th><?=lang('report.qr02')?></th>
                    <th><?=lang('report.qr03')?></th>
                    <th><?=lang('report.qr09')?></th>
                    <th><?=lang('report.qr04')?></th>
                    <th><?=lang('report.qr05')?></th>
                    <th><?=lang('report.qr06')?></th>
                    <th><?=lang('report.qr07')?></th>
                    <th><?=lang('report.qr08')?></th>
                </tr>
            </thead>

            <!-- <tfoot>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </tfoot> -->
        </table>
    </div>
</div>

<script type="text/javascript">

    var message = {
        createTime      : '<?=lang('report.qr01')?>',
        username        : '<?=lang('report.pr01')?>',
        categoryTitle   : '<?=lang('report.qr02')?>',
        managerTitle    : '<?=lang('report.qr03')?>',
        type            : '<?=lang('report.qr09')?>',
        statue          : '<?=lang('report.qr04')?>',
        WC              : '<?=lang('report.qr05')?>',
        amount          : '<?=lang('report.qr06')?>',
        playerRequestIp : '<?=lang('report.qr07')?>',
        releaseTime     : '<?=lang('report.qr08')?>'        
    };

    function addCommas(nStr){
        nStr += '';
        var x = nStr.split('.');
        var x1 = x[0];
        var x2 = x.length > 1 ? '.' + x[1] : '';
        var rgx = /(\d+)(\d{3})/;
        while (rgx.test(x1)) {
            x1 = x1.replace(rgx, '$1' + ',' + '$2');
        }
        return x1 + x2;
    }
    function questReport() {
        let player_id = <?= $player_id ?>;
        console.log('player_id', player_id);
        var dataTable = $('#questreport-table').DataTable({
            scrollX: true,
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>',
                }
            ],
            columnDefs: [
                { className: 'text-right', targets: [2] },
            ],
            "order": [ 0, 'asc' ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {

                var formData = $('#search-form').serializeArray();
            
                data.extra_search = formData;
                $.post(base_url + "api/playerQuestReport/" + player_id , data, function(data) {
                    console.log(data)
                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                    }
                    else {
                        dataTable.buttons().enable();
                    }
                }, 'json');
           },
           drawCallback : function( settings ) {
               console.log('drawCallback.arguments', arguments);
               <?php if( ! empty($enable_freeze_top_in_list) ): ?>
                var _scrollBodyHeight = window.innerHeight;
                _scrollBodyHeight -= $('.navbar-fixed-top').height();
                _scrollBodyHeight -= $('.dataTables_scrollHead').height();
                _scrollBodyHeight -= $('.dataTables_scrollFoot').height();
                _scrollBodyHeight -= $('#myTable_paginate').closest('.panel-body').height();
                _scrollBodyHeight -= 44;// buffer
                $('.dataTables_scrollBody').css({'max-height': _scrollBodyHeight+ 'px'});
            <?php endif; // EOF if( ! empty($enable_freeze_top_in_list) ):... ?>
           },
           "rowCallback": function( row, data, index ) {
           },
        });

        $('#search-form #btn-submit').click( function(e) {
            e.preventDefault();
            dataTable.ajax.reload();
        });

        ATTACH_DATATABLE_BAR_LOADER.init('questreport-table');
    }
</script>
