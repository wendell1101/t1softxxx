<?php if(isset($deposit_method) && $deposit_method == 'auto_static_html') :?>
    <?php include $static_html;?>
<?php else: ?>
	<?php include VIEWPATH . '/stable_center2/cashier/deposit/auto.php' ?>
<?php endif ?>