<div data-file-info="ajax_ui_grade_report.php" data-datatable-selector="#grade-table">
    <form class="form-inline" id="search-form">
        <input type="text" id="reportrange" class="form-control input-sm dateInput inline" data-start="#date_from" data-end="#date_to" data-time="true" />
        <input type="hidden" id="date_from" name="date_from"/>
        <input type="hidden" id="date_to" name="date_to"/>
        <input type="button" class="btn btn-portage btn-sm" id="btn-submit" value="<?=lang('lang.search');?>"/>

        <input type="hidden" name="search_by" value="2">
        <input type="hidden" name="username" value="<?= $player_username ?>">
        <input type="hidden" name="request_type" value="">
        <input type="hidden" name="request_grade" value="">
        <input type="hidden" name="level_from" value="">
        <input type="hidden" name="level_to" value="">
    </form>
    <hr />
    <div class="clearfix">
        <table id="grade-table" class="table table-bordered">
            <thead>
                <tr>
                    <th>NO.</th>
                    <th><?=lang('Player Username')?></th>
                    <th><?=lang('Player Tag')?></th>
                    <th><?=lang('Affiliate')?></th>
                    <th><?=lang('report.gr02')?></th>
                    <th><?=lang('report.gr03')?></th>
                    <th><?=lang('report.gr04')?></th>
                    <th><?=lang('report.gr05')?></th>
                    <th><?=lang('report.gr06')?></th>
                    <th><?=lang('report.gr07')?></th>
                    <th><?=lang('report.gr08')?></th>
                    <th><?=lang('report.gr09')?></th>
                    <th><?=lang('report.gr10')?></th>
                    <th><?=lang('report.gr11')?></th>
                    <th><?=lang('report.gr12')?></th>
                    <th><?=lang('report.gr13')?></th>
                    <th><?=lang('report.gr14')?></th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<script type="text/javascript">
    function gradeHistory() {
        var dataTable = $('#grade-table').DataTable({
            autoWidth: false,
            searching: false,
            dom: "<'panel-body' <'pull-right'B><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            columnDefs: [
                { className: 'text-right', targets: [] },
                // { visible: false, targets: [ 9, 10, 11 ] },
                { visible: false, targets: [ 0, 1, 2, 9, 10, 11 ] },
            ],
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>',
                }
            ],
            order: [[ 0, "desc" ]],
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                data.extra_search = $('#search-form').serializeArray();
                $.post(base_url + "api/playerGradeReports", data, function(data) {
                    callback(data);
                }, 'json');
            },
        });

        $('#search-form #btn-submit').click( function(e) {
            e.preventDefault();
            dataTable.ajax.reload();
        });

        // $('.export_excel').click(function(){
        //     var d = {'extra_search':$('#form-filter').serializeArray(), 'draw':1, 'length':-1, 'start':0};
        //     $.post(site_url('/export_data/player_reports'), d, function(data){
        //         if(data && data.success){
        //             $('body').append('<iframe src="'+data.link+'" frameborder="0" scrolling="no" style="border:0px;width:0px;height:0px"></iframe>');
        //         }else{
        //             alert('export failed');
        //         }
        //     });
        // });

        ATTACH_DATATABLE_BAR_LOADER.init('grade-table');
    }
</script>
