<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="renderer" content="webkit" />
    <title>Match Details</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <script type="text/javascript" src="<?=$this->utils->jsUrl('jquery-2.1.4.min.js')?>"></script>
    <script type="text/javascript" src="<?=$this->utils->jsUrl('bootstrap.min.js')?>"></script>
    <?php
    $user_theme = !empty($this->session->userdata('admin_theme')) ? $this->session->userdata('admin_theme') : $this->config->item('sbe_default_theme');
    ?>
    <link href="<?=$this->utils->cssUrl('themes/bootstrap.' . $user_theme . '.css')?>" rel="stylesheet">
    <link href="<?=$this->utils->cssUrl('font-awesome.min.css')?>" rel="stylesheet">
</head>
<body data-theme="<?=$user_theme?>">
<div class="panel panel-primary">
    <div class="panel-body">
        <div class="table-responsive">
        <table id="myTable" class="table table-bordered">
            <thead>
                <tr>
                    <?php
                    // Display table headers dynamically based on the keys of the first row in the data
                    if (!empty($data)) {
                        $firstRow = reset($data);
                        foreach ($firstRow as $key => $value) {
                            // Format the header: capitalize first letter and replace '_' with space
                            $formattedHeader = str_replace('_', ' ', ucwords($key));
                            echo '<th>' . htmlspecialchars($formattedHeader) . '</th>';
                        }
                    }
                    ?>
                </tr>
            </thead>
            <?php if ($data): ?>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <?php foreach ($row as $value): ?>
                            <td><?= htmlspecialchars($value) ?></td>
                        <?php endforeach ?>
                    </tr>
                <?php endforeach ?>
            </tbody>
            <?php endif ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>