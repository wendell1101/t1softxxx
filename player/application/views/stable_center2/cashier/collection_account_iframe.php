<div class=".container">
    <div class="row">
        <div class="col pull-right">
            <button type="button" class="close cif">×</button>
        </div>
    </div>
    <div class="row">
        <iframe id="deposit-response-iframe" src="<?= $target_href ?>" class="col-xs-12" style="height:100vh">
        </iframe>
    </div>
</div>
<script type="text/javascript">
    document.querySelector('.cif')?.addEventListener('click', function(){
        window.location.href = `<?=$back_btn_href?>`;
    });
</script>