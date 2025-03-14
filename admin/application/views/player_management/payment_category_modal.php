<div class="row" id="payment_category_modal" tabindex="-1" role="dialog">
    <div class="modal-body">
        <?php foreach ($categorys as $key => $value): ?>
            <div class="checkbox">
                <label>
                    <input name="payment_category" value="<?=lang($key)?>" type="checkbox"/>
                    <?=lang($value)?>
                </label>
            </div>
        <?php endforeach?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?=lang('Cancel') ?></button>
        <button type="submit" id="saveBtn" class="btn btn-primary"><?=lang('lang.save')?></button>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $(document).on('click', '#saveBtn', function(){
            var selectedValues = [];
            $('input[name="payment_category"]:checked').each(function() {
                selectedValues.push($(this).val());
            });

            if (selectedValues.length > 0) {
                return filter_payment_category(selectedValues);
            } else {
                alert("<?=lang('Please Choose Bank/Payment Gateway Type')?>");
            }
        });
    });
</script>

