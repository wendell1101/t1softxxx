<div id="CustomizedPromoEditorModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-fs">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button btn-cancel" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo lang('promo.customized_promo_helper'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col col-md-3">
                        <label for="CustomizedPromoList">Customized Promo</label>
                    </div>
                    <div class="col col-md-9">
                        <select id="CustomizedPromoList"></select>
                    </div>
                </div>
                <div class="row">
                    <div class="col col-md-3">
                    </div>
                    <div class="col col-md-9">
                        <div id="CustomizedPromoDetail"></div>
                    </div>
                </div>

                <!-- Nav tabs -->
                <ul class="nav nav-tabs" role="tablist">
                    <li role="presentation" class="active">
                        <a href="#CustomizedPromoConditionEditor" role="tab" data-toggle="tab"><?= lang('cms.applyCondition'); ?></a>
                    </li>
                    <li role="presentation">
                        <a href="#CustomizedPromoReleaseEditor" role="tab" data-toggle="tab"><?= lang('Release Bonus'); ?></a>
                    </li>
                </ul>

                <!-- Tab panes -->
                <div class="tab-content">
                    <div id="CustomizedPromoConditionEditor" class="tab-pane active" role="tabpanel">
                        <form class="form-horizontal"><div class="json-editor"></div></form>
                    </div>
                    <div id="CustomizedPromoReleaseEditor" class="tab-pane" role="tabpanel">
                        <form class="form-horizontal"><div class="json-editor"></div></form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-confirm"><?= lang('lang.save'); ?></button>
                <button type="button" class="btn btn-default btn-cancel" data-dismiss="modal"><?= lang('lang.close'); ?></button>
            </div>
        </div>
    </div>
</div>

<?=$this->load->view('includes/json_editor', [
    'current_language' => $this->language_function->getCurrentLanguageName()
], TRUE)?>

<script type="text/javascript" src="<?= site_url() . 'resources/js/marketing_management/customized_promo_editor.js' ?>"></script>
<style type="text/css">
    #CustomizedPromoEditorModal .modal-body form {
        margin: -15px 0;
    }

    #CustomizedPromoEditorModal .row .col {
        padding: 15px;
    }

    #CustomizedPromoEditorModal .btn-group {
        width: unset;
    }

    #CustomizedPromoEditorModal .form-horizontal .form-group {
        margin-left: unset;
        margin-right: unset;
    }
</style>

<script type="text/javascript">
    $(document).ready(function() {
        customized_promo_editor.init(() => {
            let modal = $('#CustomizedPromoEditorModal').modal({
                show: false
            });
            modal.find('.btn-confirm').on('click', function() {
                try {
                    customized_promo_editor.save();
                } catch (error) {
                    MessageBox.danger(error.message);
                }
            });
            return modal;
        });
    });
</script>