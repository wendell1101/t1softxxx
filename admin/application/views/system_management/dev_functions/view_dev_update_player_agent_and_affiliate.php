<style>
    /* Floating alert styling */
    .floating-alert {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1050;
        width: auto;
        max-width: 300px;
        display: none; /* Initially hidden */
    }
</style>

<div id="alert-container"></div>

<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title">
            <i class="icon-pie-chart"></i>
            <?php echo lang('Update Player Affiliate/Agent'); ?>
        </h4>
    </div>
    <div class="panel-body">
        <div class="alert alert-info">
            <strong>Note:</strong> This is for updates only. In case the player doesn't have a dedicated agent or affiliate, please go to the Player User Information section and then the Signup Info tab.
        </div>
        <form id="form-filter">
            <div class="row form-group">
                <div class="col-md-2">
                    <label for="player_name" class="form-label"><?php echo lang('Playername'); ?></label>
                    <input type="text" id="player_name" name="player_name" class="form-control">
                    <span class="text-danger error-search-message" style="display: none;">This field is required.</span>
                </div>
                <div class="col-md-2">
                    <br>
                    <input type="button" value="<?php echo lang('Search player'); ?>" id="loadData" class="btn btn-portage btn-sm">
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="myTable">
                <thead>
                    <tr>
                        <th><?php echo lang('Playername'); ?></th>
                        <th><?php echo lang('Affiliate'); ?></th>
                        <th><?php echo lang('Agent'); ?></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal for agent -->
<div class="modal fade" id="modal-agent" tabindex="-1" role="dialog" aria-labelledby="modalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="modal-agent-form">
                <div class="modal-header">
                    <h4 class="modal-title" id="modalLabel"><?php echo lang('Agent form'); ?></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="agent-player-id" class="form-control">
                    <label for="select-agent" class="control-label"><?php echo lang('Agent list'); ?>:</label>
                    <select id="select-agent" class="form-control" style="width: 100%;">
                        <option></option>
                        <?php foreach ($agents as $key => $agent) { ?>
                            <option value="<?php echo $key; ?>"><?php echo $agent; ?></option>
                        <?php } ?>
                    </select>
                    <span class="text-danger error-message" style="display: none;">This field is required.</span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo lang('Close'); ?></button>
                    <button type="submit" class="btn btn-primary" id="update-agent"><?php echo lang('Update agent'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal for aff -->
<div class="modal fade" id="modal-aff" tabindex="-1" role="dialog" aria-labelledby="modalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="modal-aff-form">
                <div class="modal-header">
                    <h4 class="modal-title" id="modalLabel"><?php echo lang('Affiliate form'); ?></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="aff-player-id" class="form-control">
                    <label for="select-aff" class="control-label"><?php echo lang('Affiliate list'); ?>:</label>
                    <select id="select-aff" class="form-control" style="width: 100%;">
                        <option></option>
                        <?php foreach ($affiliates as $key => $affiliate) { ?>
                            <option value="<?php echo $key; ?>"><?php echo $affiliate; ?></option>
                        <?php } ?>
                    </select>
                    <span class="text-danger error-message" style="display: none;">This field is required.</span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo lang('Close'); ?></button>
                    <button type="submit" class="btn btn-primary" id="update-aff"><?php echo lang('Update affiliate'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
    // Define base URL for API calls
    var base_url = '<?php echo base_url(); ?>';

    // Initialize DataTable
    var dataTable = $('#myTable').DataTable({
        dom: "<'panel-body'<'pull-right'B><'pull-right progress-container hidden-xs'>l><'dt-information-summary1 text-info pull-left' i>t<'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
        autoWidth: false,
        searching: false,
        stateSave: false,
        buttons: [],
        processing: true,
        serverSide: true,
        ajax: function (data, callback) {
            var formData = $('#form-filter').serializeArray();
            data.extra_search = formData;

            $.post(base_url + 'api/playerAffiliateAgent', data, function (response) {
                callback(response);
            }, 'json');
        }
    });

    // Handle the search button click
    $('#loadData').on('click', function () {
        const player = $('#player_name').val();
        if (!player) {
            $('.error-search-message').show(); // Show error message
            return;
        }
        $('.error-search-message').hide(); // Hide error message
        dataTable.ajax.reload(); // Reload DataTable with new data
    });

    // Initialize Select2 in modals
    $('#modal-agent').on('shown.bs.modal', function () {
        $('#select-agent').select2({
            placeholder: "Please select an agent",
            allowClear: true,
            dropdownParent: $('#modal-agent'),
            templateResult: function (data) {
                if (!data.id) return data.text;
                return $('<span>').text(`${data.id} - ${data.text}`);
            },
            templateSelection: function (data) {
                if (!data.id) return data.text;
                return `${data.id} - ${data.text}`;
            }
        });
    });

    $('#modal-aff').on('shown.bs.modal', function () {
        $('#select-aff').select2({
            placeholder: "Please select an affiliate",
            allowClear: true,
            dropdownParent: $('#modal-aff'),
            templateResult: function (data) {
                if (!data.id) return data.text;
                return $('<span>').text(`${data.id} - ${data.text}`);
            },
            templateSelection: function (data) {
                if (!data.id) return data.text;
                return `${data.id} - ${data.text}`;
            }
        });
    });

    // Submit Agent Form
    $('#modal-agent-form').on('submit', function (e) {
        e.preventDefault();
        const agentId = $('#select-agent').val();
        const playerId = $('#agent-player-id').val();

        if (!agentId) {
            $('.error-message').show(); // Show error message
            return;
        }

        $('.error-message').hide();

        const data = { playerId, agentId };

        const confirmMessage = '<?php echo lang('Are you sure you want to perform this action?'); ?>';
        if (window.confirm(confirmMessage)) {
            $.ajax({
                url: '/player_management/assignPlayerAgentThruAjax',
                type: 'POST',
                data,
                dataType: 'json'
            }).done(function (response) {
                if (response.status === 'success') {
                    showAlert('success', '<strong>Success!</strong> ' + '<?php echo lang('Your action was completed.'); ?>');
                    $('#modal-agent').modal('hide');
                    $('#agent-player-id').val('');
                    $('#select-agent').val(null).trigger('change');
                    dataTable.ajax.reload();
                } else {
                    showAlert('danger', '<strong>Error!</strong> ' + response.message);
                }
            }).fail(function () {
                showAlert('danger', '<strong>Error!</strong> An unexpected error occurred.');
            });
        }
    });

    // Submit Affiliate Form
    $('#modal-aff-form').on('submit', function (e) {
        e.preventDefault();
        const affiliateId = $('#select-aff').val();
        const playerId = $('#aff-player-id').val();

        if (!affiliateId) {
            $('.error-message').show(); // Show error message
            return;
        }

        $('.error-message').hide();

        const data = { playerId, affiliateId };

        const confirmMessage = '<?php echo lang('Are you sure you want to perform this action?'); ?>';
        if (window.confirm(confirmMessage)) {
            $.ajax({
                url: '/player_management/assignPlayerAffiliateThruAjax',
                type: 'POST',
                data,
                dataType: 'json'
            }).done(function (response) {
                if (response.status === 'success') {
                    showAlert('success', '<strong>Success!</strong> ' + '<?php echo lang('Your action was completed.'); ?>');
                    $('#modal-aff').modal('hide');
                    $('#aff-player-id').val('');
                    $('#select-aff').val(null).trigger('change');
                    dataTable.ajax.reload();
                } else {
                    showAlert('danger', '<strong>Error!</strong> ' + response.message);
                }
            }).fail(function () {
                showAlert('danger', '<strong>Error!</strong> An unexpected error occurred.');
            });
        }
    });

    // Prevent form submission in the filter form
    $('#form-filter').on('submit', function (e) {
        e.preventDefault();
    });

    // Show Agent Modal
    window.showAgentList = function (playerId) {
        $('#agent-player-id').val(playerId);
        $('#modal-agent').modal('show');
    };

    // Show Affiliate Modal
    window.showAffList = function (playerId) {
        $('#aff-player-id').val(playerId);
        $('#modal-aff').modal('show');
    };

    // Function to show a floating alert
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible floating-alert" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                ${message}
            </div>
        `;

        const $alert = $(alertHtml).appendTo('#alert-container');
        $alert.fadeIn(300).delay(3000).fadeOut(500, function () {
            $(this).remove();
        });
    }

    // Reset Select2 in Agent Modal when it closes
    $('#modal-agent').on('hide.bs.modal', function () {
        $('#select-agent').val(null).trigger('change'); // Reset the dropdown
    });

    // Reset Select2 in Affiliate Modal when it closes
    $('#modal-aff').on('hide.bs.modal', function () {
        $('#select-aff').val(null).trigger('change'); // Reset the dropdown
    });
});

</script>

