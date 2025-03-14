<div class="panel panel-primary" >
    <div class="panel-heading">
        <h4 class="panel-title"><i class="icon-dice"></i> <?=lang('Gamelogs Export Hourly')?> </h4>
    </div>
    <div class="panel-body" >
        <form id='form-filter'>
            <div class="row form-group">
                <div class="col-md-2">
                    <label for="date" class="form-label">Choose a date:</label>
                    <input type="date" id="export_date" name="date" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <br>
                    <input type="button" value="<?=lang('lang.search')?>" id="loadData" class="btn btn-portage btn-sm">
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-hover " id="game_logs_export_hourly_table">
                <thead>
                    <tr>
                        <th><?=lang('Date hour')?></th>
                        <th><?=lang('Download link')?></th>
                        <th><?=lang('Generated at')?></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <div class="panel-footer"></div>
</div>

<script>
    var dateInput = document.getElementById('export_date');
    var today = new Date();
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0');
    var yyyy = today.getFullYear();
    today = yyyy + '-' + mm + '-' + dd;
    document.getElementById('export_date').setAttribute('max', today);
    dateInput.value = today;

    $(document).ready(function(){

        var dataTable = $('#game_logs_export_hourly_table').DataTable({
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,
            searching: false,

            <?php if ($this->utils->isEnabledFeature('column_visibility_report')){ ?>
                stateSave: true,
            <?php } else { ?>
                stateSave: false,
            <?php } ?>
            buttons: [
                {
                    extend: 'colvis',
                    postfixButtons: [ 'colvisRestore' ],
                    className: '<?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-linkwater' : ''?>',
                }
            ],
            "order": [ 0, 'asc' ],

            // SERVER-SIDE PROCESSING
            processing: true,
            serverSide: true,
            ajax: function (data, callback, settings) {
                formData = $('#form-filter').serializeArray();
                data.extra_search = formData;
                console.log('ajax post here ....................');
                console.log('extra_search', data.extra_search);
                $.post(base_url + "api/gamelogsExportHourly", data, function(data) {
                    console.log(data);

                    callback(data);
                    if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
                        dataTable.buttons().disable();
                    }
                    else {
                        dataTable.buttons().enable();
                    }
                }, 'json');
           },

        });

        $('#loadData').on('click', function () {
            dataTable.ajax.reload(); // Reload the DataTable with new data from the AJAX call
        });

    });
</script>
