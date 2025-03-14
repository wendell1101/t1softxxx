<?php
$default_open_upload_panel = false; // default_open_search_panel

// echo '<pre>';
// print_r($sheetData);
// echo '</pre>';
?>

<?php include __DIR__ . '/../includes/something_wrong_modal.php';?>

<!-- Import Selected Modal Start -->
<div class="modal fade" id="importedResultModal" tabindex="-1" role="dialog" aria-labelledby="importedResultModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="importedResultModalLabel"><?=lang('Result Summary')?></h4>
            </div>
            <div class="modal-body importedResultModalBody">
                <ul class="list-group">
                    <li class="list-group-item list-group-item-info"><?=lang('Imported Total count')?> : <span class="total-count">99</span></li>
                    <li class="list-group-item list-group-item-success"><?=lang('New insert count')?> : <span class="new-insert-count">99</span></li>
                    <li class="list-group-item list-group-item-dark"><?=lang('Ignore count')?> : <span class="ignore-count">99</span></li>
                </ul>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-go-list-imported-result" data-dismiss="modal"><?=lang('Go To List')?></button>
                <button type="button" class="btn btn-primary btn-close-imported-result" data-dismiss="modal"><?=lang('Close')?></button>
            </div>
        </div>
    </div>
</div>
<!-- Import Selected Modal End -->

<!-- Import Selected Modal Start -->
<div class="modal fade" id="importAllModal" tabindex="-1" role="dialog" aria-labelledby="importedResultModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="importAllModalLabel"><?=lang('Are You Sure Import All?')?></h4>
            </div>
            <div class="modal-body importAllModalBody">
                <!-- @todo "span.selected_counter" Need replace to real selected count. -->
                <?=lang('<span class="selected_counter">99</span> data has been selected, ready to import.')?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-confirm-import-all"><?=lang('Confirm')?></button>
                <button type="button" class="btn btn-primary btn-cancel-import-all"><?=lang('Cancel')?></button>
            </div>
        </div>
    </div>
</div>
<!-- Import Selected Modal End -->

<!-- Import All Modal Start -->
<div class="modal fade" id="importSelectedModal" tabindex="-1" role="dialog" aria-labelledby="importSelectedModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="importSelectedModalLabel"><?=lang('Are You Sure Import The Selected?')?></h4>
            </div>
            <div class="modal-body importSelectedModalBody">
                <!-- @todo "span.selected_counter" Need replace to real selected count. -->
                <?=lang('<span class="selected_counter">99</span> data has been selected, ready to import.')?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-confirm-import-selected"><?=lang('Confirm')?></button>
                <button type="button" class="btn btn-primary btn-cancel-import-selected"><?=lang('Cancel')?></button>
            </div>
        </div>
    </div>
</div>
<!-- Import All Modal End -->

<div class="panel panel-primary">
    <div class="panel-heading">
        <h4 class="panel-title">
            <i class="icon-newspaper"></i>
            <?=lang("report.view_hedge_in_ag_preview")?>

            <div class="btn btn-default btn-xs <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?> pull-right btn-back_to_upload"><?=lang("Back to Upload")?></div>
        </h4>
    </div>
    <form id="import-form" action="<?= site_url('/report_management/importHedgeInAG4HedgingDetailInfoXls'); ?>" method="post">
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-condensed" id="result_table">
                    <thead>
                        <tr>
                            <th><?=lang("Action")?> <!-- // # 0 --> </th>
                            <th><?=lang("view_hedge_in_ag_preview.table_id")?> <!-- // # 1 --> </th>
                            <th><?=lang("view_hedge_in_ag_preview.contnet_id")?> <!-- // # 2 --> </th>

                            <th><?=lang("view_hedge_in_ag_preview.members")?> <!-- // # 3 --> </th>
                            <th><?=lang("view_hedge_in_ag_preview.banker")?> <!-- // # 4 --> </th>
                            <th><?=lang("view_hedge_in_ag_preview.player")?> <!-- // # 5 --> </th>
                            <th><?=lang("view_hedge_in_ag_preview.dragon")?> <!-- // # 6 --> </th>

                            <th><?=lang("view_hedge_in_ag_preview.tiger")?> <!-- // # 7 --> </th>
                            <th><?=lang("view_hedge_in_ag_preview.big")?> <!-- // # 8 --> </th>
                            <th><?=lang("view_hedge_in_ag_preview.small")?> <!-- // # 9 --> </th>
                            <th><?=lang("view_hedge_in_ag_preview.sic_bo_odd")?> <!-- // # 10 --> </th>

                            <th><?=lang("view_hedge_in_ag_preview.sic_bo_even")?> <!-- // # 11 --> </th>
                            <th><?=lang("view_hedge_in_ag_preview.red")?> <!-- // # 12 --> </th>
                            <th><?=lang("view_hedge_in_ag_preview.black")?> <!-- // # 13 --> </th>
                            <th><?=lang("view_hedge_in_ag_preview.roulette_odd")?> <!-- // # 14 --> </th>

                            <th><?=lang("view_hedge_in_ag_preview.roulette_even")?> <!-- // # 15 --> </th>
                            <th><?=lang("view_hedge_in_ag_preview.hedge_difference")?> <!-- // # 16 --> </th>
                            <th><?=lang("view_hedge_in_ag_preview.hedge_index")?> <!-- // # 17 --> </th>
                            <th><?=lang("view_hedge_in_ag_preview.hedge_spicious")?> <!-- // # 18 --> </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sheetData as $indexNumber => $row): ?>
                        <tr id="<?=$indexNumber?>">
                            <td class="text-center">
                                <input type="checkbox" name="index_<?=$indexNumber?>" value="<?=$indexNumber?>">
                            </td>
                            <td class="input-sm">
                                <pre><?= $row['A'] ?></pre> <!-- // # 1, table_id -->
                                <textarea name="table_id[<?=$indexNumber?>]" class="hide"><?= $row['A'] ?></textarea>
                            </td>
                            <td class="input-sm"><pre><?= $row['B'] ?></pre> <!-- // # 2, contnet_id -->
                                <textarea name="contnet_id[<?=$indexNumber?>]" class="hide"><?= $row['B'] ?></textarea>
                            </td>
                            <td class="input-sm"><pre><?= $row['C'] ?></pre> <!-- // # 3, members -->
                                <textarea name="members[<?=$indexNumber?>]" class="hide"><?= $row['C'] ?></textarea>
                            </td>
                            <td class="input-sm"><pre><?= $row['D'] ?></pre> <!-- // # 4, banker -->
                                <textarea name="banker[<?=$indexNumber?>]" class="hide"><?= $row['D'] ?></textarea>
                            </td>

                            <td class="input-sm"><pre><?= $row['E'] ?></pre> <!-- // # 5, player -->
                                <textarea name="player[<?=$indexNumber?>]" class="hide"><?= $row['E'] ?></textarea>
                            </td>
                            <td class="input-sm"><pre><?= $row['F'] ?></pre> <!-- // # 6, dragon -->
                                <textarea name="dragon[<?=$indexNumber?>]" class="hide"><?= $row['F'] ?></textarea>
                            </td>
                            <td class="input-sm"><pre><?= $row['G'] ?></pre> <!-- // # 7, tiger -->
                                <textarea name="tiger[<?=$indexNumber?>]" class="hide"><?= $row['G'] ?></textarea>
                            </td>
                            <td class="input-sm"><pre><?= $row['H'] ?></pre> <!-- // # 8, big -->
                                <textarea name="big[<?=$indexNumber?>]" class="hide"><?= $row['H'] ?></textarea>
                            </td>

                            <td class="input-sm"><pre><?= $row['I'] ?></pre> <!-- // # 9, small -->
                                <textarea name="small[<?=$indexNumber?>]" class="hide"><?= $row['I'] ?></textarea>
                            </td>
                            <td class="input-sm"><pre><?= $row['J'] ?></pre> <!-- // # 10, sic_bo_odd -->
                                <textarea name="sic_bo_odd[<?=$indexNumber?>]" class="hide"><?= $row['J'] ?></textarea>
                            </td>
                            <td class="input-sm"><pre><?= $row['K'] ?></pre> <!-- // # 11, sic_bo_even -->
                                <textarea name="sic_bo_even[<?=$indexNumber?>]" class="hide"><?= $row['K'] ?></textarea>
                            </td>
                            <td class="input-sm"><pre><?= $row['L'] ?></pre> <!-- // # 12, red -->
                                <textarea name="red[<?=$indexNumber?>]" class="hide"><?= $row['L'] ?></textarea>
                            </td>

                            <td class="input-sm"><pre><?= $row['M'] ?></pre> <!-- // # 13, black -->
                                <textarea name="black[<?=$indexNumber?>]" class="hide"><?= $row['M'] ?></textarea>
                            </td>
                            <td class="input-sm"><pre><?= $row['N'] ?></pre> <!-- // # 14, roulette_odd -->
                                <textarea name="roulette_odd[<?=$indexNumber?>]" class="hide"><?= $row['N'] ?></textarea>
                            </td>
                            <td class="input-sm"><pre><?= $row['O'] ?></pre> <!-- // # 15, roulette_even -->
                                <textarea name="roulette_even[<?=$indexNumber?>]" class="hide"><?= $row['O'] ?></textarea>
                            </td>
                            <td class="input-sm"><pre><?= $row['P'] ?></pre> <!-- // # 16, hedge_difference -->
                                <textarea name="hedge_difference[<?=$indexNumber?>]" class="hide"><?= $row['P'] ?></textarea>
                            </td>

                            <td class="input-sm"><pre><?= $row['Q'] ?></pre> <!-- // # 17, hedge_index -->
                                <textarea name="hedge_index[<?=$indexNumber?>]" class="hide"><?= $row['Q'] ?></textarea>
                            </td>
                            <td class="input-sm"><pre><?= $row['R'] ?></pre> <!-- // # 18, hedge_spicious -->
                            <textarea name="hedge_spicious[<?=$indexNumber?>]" class="hide"><?= $row['R'] ?></textarea>
                            </td>
                        </tr>
                        <?php endforeach; // EOF foreach ($sheetData as $indexNumber => $row): ?>
                    </tbody>
                    <tfoot>
                        <tr></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </form>
    <div class="panel-footer"></div>
</div>

<div id="conf-modal"  class="modal fade bs-example-modal-md"  data-backdrop="static"
data-keyboard="false"  tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header panel-heading">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 id="myModalLabel" ><?=lang('sys.ga.conf.title');?></h3>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="help-block" id="conf-msg">

                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" id="cancel-action" data-dismiss="modal"><?=lang('pay.bt.cancel');?></button>
                <button type="button" id="confirm-action" class="btn btn-primary"><?=lang('pay.bt.yes');?></button>
            </div>
        </div>
    </div>
</div>



<style type="text/css">
.table-responsive pre {
    background: transparent;
    border: none;
}
</style>
<script type="text/javascript">

    var tokenFileName = '<?=$tokenFileName?>';
    function notify(type, msg) {
        $.notify({
            message: msg
        }, {
            type: type
        });
    }


    var hedge_in_ag_preview = hedge_in_ag_preview||{};

    hedge_in_ag_preview.tokenFileName = '<?=$tokenFileName?>';
    hedge_in_ag_preview.baseUrl = '<?php echo base_url(); ?>';

    hedge_in_ag_preview.onReady = function () {
        var _this = this;

        _this._registerEvents();

        _this.dataTable = $('#result_table').DataTable({
            // stateSave: true,
            dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            autoWidth: false,
            searching: true,
            lengthChange: true,
            pageLength: <?=$this->utils->getDefaultItemsPerPage()?>,

            rowId: function(a) { // Set rows IDs
                var indexName = $(a[0]).attr('name');
                var _rowId = indexName.replaceAll('index_', '');
                return _rowId;
            },
            buttons: [
                {
                    text: "<?= lang('Select/Deselect All current page'); ?>",
                    className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                    action: function ( e, dt, node, config ) {
                        var total_amount = $('#import-form input:checkbox').length;
                        if(total_amount == $('#import-form input:checkbox:checked').length){
                            $('#import-form input:checkbox').prop('checked', false);
                        }else{
                            $('#import-form input:checkbox')
                                .prop('checked', 'checked')
                                .attr('checked', 'checked');
                        }
                    } // EOF action: function ( e, dt, node, config ) { ...
                }
                ,{
                    // extend: 'selected',
                    text: "<?= lang('Import selected'); ?>",
                    className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                    action: function ( e, dt, node, config ) {

                        $('#importSelectedModal').modal('show');

                        // hedge_in_ag_preview.clicked_import_selected(e, dt, node, config);
                    } // EOF action: function ( e, dt, node, config ) { ...
                }
                ,{
                    text: "<?= lang('Import All'); ?>",
                    className:'btn btn-sm <?=$this->utils->getConfig('use_new_sbe_color') ? 'btn-portage' : 'btn-primary'?>',
                    action: function ( e, dt, node, config ) {
                        // hedge_in_ag_preview.clicked_import_all(e, dt, node, config);
                        $('#importAllModal').modal('show');
                    } // EOF action: function ( e, dt, node, config ) { ...
                }

            ],
            columnDefs: [
                { className: 'text-right', targets: [] },
                { className: 'text-center', targets: [1] }
            ],
            order: [ 0, 'desc' ]
            // serverSide: false,
            // // SERVER-SIDE PROCESSING
            // processing: true,
            // serverSide: true,
            // ajax: function (data, callback, settings) {
            //     data.extra_search = $('#search-form').serializeArray();
            //     $.post(base_url + "api/iovationReport", data, function(data) {
            //         $.each(data.data, function(i, v){
            //             /*sub = v[10].replace(/<(?:.|\n|)*?>/gm, '');
            //             convertedSub = sub.replace(',', '');
            //             if(Number.parseFloat(convertedSub)){
            //                 subTotal+= Number.parseFloat(convertedSub);
            //             }*/
            //         });
            //         callback(data);
            //         if ( dataTable.rows( { selected: true } ).indexes().length === 0 ) {
            //             dataTable.buttons().disable();
            //         }
            //         else {
            //             dataTable.buttons().enable();
            //         }
            //     }, 'json');
            // }
        });
        _this.dataTable.page.len( <?=$this->utils->getDefaultItemsPerPage()?> ).draw(); // for lengthChange

    } // EOF onReady()

    hedge_in_ag_preview._registerEvents = function () {
        var _this = this;

        // delegates handle
        $('body').on('click', '.btn-go-list-imported-result', function(e){
            _this.clicked_btn_go_list_imported_result(e);
        });
        $('body').on('click', '.btn-back_to_upload', function(e){
            _this.clicked_back_to_upload(e);
        });
        $('body').on('click', '#import-form tr', function(e){
            _this.clicked_dataTable_tr(e);
        });

        $('body').on('click', '.btn-confirm-import-selected', function(e){
            _this.clicked_btn_confirm_import_selected(e);
        });
        $('body').on('click', '.btn-cancel-import-selected', function(e){
            _this.clicked_btn_cancel_import_selected(e);
        });

        $('body').on('click', '.btn-confirm-import-all', function(e){
            _this.clicked_btn_confirm_import_all(e);
        });
        $('body').on('click', '.btn-cancel-import-all', function(e){
            _this.clicked_btn_cancel_import_all(e);
        });


        $('#importSelectedModal')
            .on('show.bs.modal', function (e) {
                _this.show_importSelected(e);
            })
            .on('shown.bs.modal', function (e) {
                _this.shown_importSelected(e);
            })
            .on('hide.bs.modal', function (e) {
                _this.hide_importSelected(e);
            })
            .on('hidden.bs.modal', function (e) {
                _this.hidden_importSelected(e);
            });

        $('#importAllModal')
            .on('show.bs.modal', function (e) {
                _this.show_importAll(e);
            })
            .on('shown.bs.modal', function (e) {
                _this.shown_importAll(e);
            })
            .on('hide.bs.modal', function (e) {
                _this.hide_importAll(e);
            })
            .on('hidden.bs.modal', function (e) {
                _this.hidden_importAll(e);
            });

        $('#somethingWrongModal')
            .on('show.bs.modal', function (e) {
                _this.show_somethingWrongModal(e);
            })
            .on('shown.bs.modal', function (e) {
                _this.shown_somethingWrongModal(e);
            })
            .on('hide.bs.modal', function (e) {
                _this.hide_somethingWrongModal(e);
            })
            .on('hidden.bs.modal', function (e) {
                _this.hidden_somethingWrongModal(e);
            });

    } // EOF _registerEvents()

    hedge_in_ag_preview.show_somethingWrongModal = function (e) {

    };
    hedge_in_ag_preview.shown_somethingWrongModal = function (e) {

    };
    hedge_in_ag_preview.hide_somethingWrongModal = function (e) {
        location.reload();
    };
    hedge_in_ag_preview.hidden_somethingWrongModal = function (e) {

    };

    hedge_in_ag_preview.getTarget$El = function (e) {
        var _this = this;
        return $(e.target);
    };


    hedge_in_ag_preview.clicked_back_to_upload = function(e){
        var _this = this;
        var theUri = '<?=site_url('report_management/viewHedgeInAG4upload')?>';
        location.href = theUri;
    }

    // show_importAllModal
    hedge_in_ag_preview.show_importAll = function(e){
        var _this = this;
        // var targetBtn$El = _this.getTarget$El(e);
        var dt = _this.dataTable;
        var rows = dt.rows(':has(:checkbox)').count();
        $('.importAllModalBody .selected_counter').html(rows);
    };
    hedge_in_ag_preview.shown_importAll = function(e){
    };
    hedge_in_ag_preview.hide_importAll = function(e){
    };
    hedge_in_ag_preview.hidden_importAll = function(e){
    };

    hedge_in_ag_preview.show_importSelected = function(e){
        var _this = this;
        // var targetBtn$El = _this.getTarget$El(e);
        var dt = _this.dataTable;
        var rows = dt.rows(':has(:checkbox:checked)').count();
        $('.importSelectedModalBody .selected_counter').html(rows);

        // var selectedIds = dt.rows(':has(:checkbox:checked)').ids();
        // var theFilename = _this.tokenFileName;

        // var theDeferred = _this.importIdsAndFilename(theIds, theFilename
        //     , function(jqXHR, settings){ // beforeSendCB
        //         targetBtn$El.button('loading');
        //     }, function(jqXHR, textStatus){ // completeCB
        //         targetBtn$El.button('reset');
        // });
    };
    hedge_in_ag_preview.shown_importSelected = function(e){
    };
    hedge_in_ag_preview.hide_importSelected = function(e){
    };
    hedge_in_ag_preview.hidden_importSelected = function(e){
    };

    hedge_in_ag_preview.clicked_dataTable_tr = function(e){
        var _this = this;
        var target$El = _this.getTarget$El(e);

        var isToggle = true;
        if(target$El.closest('tr').length > 0 ){
            var targetTr$El = target$El.closest('tr');
        }
        if(target$El.prop('type') == 'checkbox'){
            isToggle = false;
        }

        if(isToggle){
            var indexCheckbox$El = targetTr$El.find('input:checkbox[name^="index_"]');
            if(indexCheckbox$El.is(":checked")){
                // to de-checked
                indexCheckbox$El.prop('checked', false);
            }else{
                // to checked
                indexCheckbox$El.prop('checked', true);
            }
        }
    }


    // btn-close-imported-result
    hedge_in_ag_preview.clicked_btn_close_imported_result = function(e){
        $('#importedResultModal').modal('hide');
    };

    // btn-go-list-imported-result
    hedge_in_ag_preview.clicked_btn_go_list_imported_result = function(e){
        var _this = this;
        var theUri = '<?=site_url('report_management/viewHedgeInAG4playerList')?>';
        location.href = theUri;
    };

    // .btn-confirm-import-selected
    hedge_in_ag_preview.clicked_btn_confirm_import_selected = function(e){
        var _this = this;
        var targetBtn$El = _this.getTarget$El(e);
        var dt = _this.dataTable;
        var selectedIds = dt.rows(':has(:checkbox:checked)').ids();
        var theIds = selectedIds.toArray();
        var theFilename = _this.tokenFileName;

        var referred = _this.script_importIdsAndFilename(theIds, theFilename, function(){ // beforeSendCB
            targetBtn$El.button('loading');
        }, function(){ // completeCB
            targetBtn$El.button('reset');
        });

        referred.done(function (data, textStatus, jqXHR) {
            $('#importSelectedModal').modal('hide');
        });
    }
    hedge_in_ag_preview.OLDclicked_btn_confirm_import_selected = function(e){
        var _this = this;
        var targetBtn$El = _this.getTarget$El(e);
        var dt = _this.dataTable;
        var selectedIds = dt.rows(':has(:checkbox:checked)').ids();
        var theIds = selectedIds.toArray();
        var theFilename = _this.tokenFileName;
        var theDeferred = _this.importIdsAndFilename(theIds, theFilename
            , function(jqXHR, settings){ // beforeSendCB
                targetBtn$El.button('loading');
            }, function(jqXHR, textStatus){ // completeCB
                targetBtn$El.button('reset');

                $('#importSelectedModal').modal('hide');
        });
        theDeferred.done(function (data, textStatus, jqXHR) {

            var newInserCounter = 0;
            var ignore4ExistCounter = 0;
            var totalCounter = 0;
            if( typeof(data.row_list) !== 'undefined'
                && data.row_list.length > 0
            ){
                data.row_list.forEach(function(elementOfArray, indexNumber, srcArray){

                    if(elementOfArray.result === null ){
                        ignore4ExistCounter++; // empty row Or other format inactive.
                    }else if( ! $.isEmptyObject(elementOfArray.result.result)  ){
                        newInserCounter++;
                    }else{
                        ignore4ExistCounter++;
                    }
                });
                totalCounter = data.row_list.length;
            }
            $('.importedResultModalBody .total-count').html(totalCounter);
            $('.importedResultModalBody .new-insert-count').html(newInserCounter);
            $('.importedResultModalBody .ignore-count').html(ignore4ExistCounter);

// console.log('newInserCounter:', newInserCounter, 'ignore4ExistCounter:', ignore4ExistCounter)
            $('#importedResultModal').modal('show');
        }); // EOF theDeferred.done(function (data, textStatus, jqXHR) { ...

    }; // EOF clicked_btn_confirm_import_selected

    // .btn-cancel-import-selected
    hedge_in_ag_preview.clicked_btn_cancel_import_selected = function(e){
        var _this = this;
        $('#importSelectedModal').modal('hide');
    };

    hedge_in_ag_preview.script_importIdsAndFilename=function(theIds, theFilename, beforeSendCB, completeCB){
        var _this = this;
        var theDeferred = _this.importIdsAndFilename(theIds, theFilename
            , function(jqXHR, settings){ // beforeSendCB
                // targetBtn$El.button('loading');
                beforeSendCB.apply(_this, arguments);
            }, function(jqXHR, textStatus){ // completeCB
                completeCB.apply(_this, arguments);
        });
        theDeferred.done(function (data, textStatus, jqXHR) {

            var newInserCounter = 0;
            var ignore4ExistCounter = 0;
            var totalCounter = 0;
            if( typeof(data.row_list) !== 'undefined'
                && data.row_list.length > 0
            ){
                data.row_list.forEach(function(elementOfArray, indexNumber, srcArray){
                    if(elementOfArray.result === null ){
                        ignore4ExistCounter++; // empty row Or other format inactive.
                    }else if( Number(elementOfArray.result.result) > 0
                    ){
                        newInserCounter++;
                    }else{
                        ignore4ExistCounter++;
                    }
                });
                totalCounter = data.row_list.length;
            }

            $('.importedResultModalBody .total-count').html(totalCounter);
            $('.importedResultModalBody .new-insert-count').html(newInserCounter);
            $('.importedResultModalBody .ignore-count').html(ignore4ExistCounter);

            $('#importedResultModal').modal('show');
        }); // EOF theDeferred.done(function (data, textStatus, jqXHR) { ...

        return theDeferred;
    } // EOF script_importIdsAndFilename


    // clicked_btn_confirm_import_all
    // .btn-confirm-import-all
    hedge_in_ag_preview.clicked_btn_confirm_import_all = function(e){
        var _this = this;
        var targetBtn$El = _this.getTarget$El(e);
        var dt = _this.dataTable;
        var selectedIds = dt.rows(':has(:checkbox)').ids();
        var theIds = selectedIds.toArray();
        var theFilename = _this.tokenFileName;

        var referred = _this.script_importIdsAndFilename(theIds, theFilename, function(){ // beforeSendCB
            targetBtn$El.button('loading');
        }, function(){ // completeCB
            targetBtn$El.button('reset');
        });

        referred.done(function (data, textStatus, jqXHR) {
            $('#importAllModal').modal('hide');
        });
    };
    // .btn-cancel-import-all
    hedge_in_ag_preview.clicked_btn_cancel_import_all = function(e){
        var _this = this;
        $('#importAllModal').modal('hide');
    };

    hedge_in_ag_preview.clicked_import_all = function(e, dt, node, config){
        var _this = this;
        var targetBtn$El = _this.getTarget$El(e);

        // console.log('Import All', arguments);

        var rows = dt.rows(':has(:checkbox)').count();
        var selectedIds = dt.rows(':has(:checkbox)').ids();
        // console.log( 'There are '+rows+'(s) selected in the table', selectedIds );
        // 將所有選擇到的 ids 跟 檔案名稱，送去匯入 importHedgeInAG4HedgingDetailInfoXls()
        var theIds = selectedIds.toArray();
        var theFilename = tokenFileName;
        var theDeferred = hedge_in_ag_preview.importIdsAndFilename(theIds, theFilename
            , function(jqXHR, settings){ // beforeSendCB
                targetBtn$El.button('loading');
            }, function(jqXHR, textStatus){ // completeCB
                targetBtn$El.button('reset');
        });
    }  // EOF clicked_import_all

    hedge_in_ag_preview.importIdsAndFilename = function (theIds, theFilename, beforeSendCB, completeCB){
        var _this = this;

        var theUri = _this.baseUrl+ 'report_management/importHedgeInAG4HedgingDetailInfoXls';

        var theData = {};
        theData['filename'] = theFilename;
        theData['ids'] = theIds;
// console.log('importIdsAndFilename.theData', theData);

        if( typeof(beforeSendCB) === 'undefined'){
            beforeSendCB = function (){
            };
        }
        if( typeof(completeCB) === 'undefined'){
            completeCB = function (){
            };
        }

        var jqXHR = $.ajax({
            type: 'POST',
            url: theUri,
            data: $.param(theData),
            beforeSend: function (jqXHR, settings) {
                // targetBtn$El.button('loading');
                beforeSendCB.apply(_this, arguments);
            },
            complete: function (jqXHR, textStatus) {
                // targetBtn$El.button('reset');
                completeCB.apply(_this, arguments);
            }
        });
        jqXHR.done(function (data, textStatus, jqXHR) {
            // _this.dataTable.ajax.reload(null, false); // user paging is not reset on reload
            // $('#deleteWithdrawalConditionModal').modal('hide');
            // console.log('importIdsAndFilename.done().data', data);
        });
        jqXHR.fail(function (jqXHR, textStatus, errorThrown) {
            // console.log('importIdsAndFilename.fail().jqXHR',jqXHR);
            // console.log('importIdsAndFilename.fail().textStatus',textStatus);
            // console.log('importIdsAndFilename.fail().errorThrown',errorThrown)
            if ( errorThrown == 'Forbidden'
                || errorThrown == 'Unauthorized'
            ) {
                $('#somethingWrongModal').modal('show');
            }
        });
        return jqXHR;
    } // EOF importIdsAndFilename




    $(document).ready(function(){

        hedge_in_ag_preview.onReady();

    });
</script>