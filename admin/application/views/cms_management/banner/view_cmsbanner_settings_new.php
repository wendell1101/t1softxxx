<div class="panel panel-primary">
    <div class="panel-heading custom-ph">
        <h4 class="panel-title custom-pt"><i class="glyphicon glyphicon-picture"></i> <?= ucwords(lang('CMS Banner Settings')) ?> 
            <!-- <a href="#" class="btn  pull-right btn-xs btn-info" id="add_cmsbanner_sec">
                <span id="addBannerCmsGlyhicon"> <i class="fa fa-plus-circle"></i> Add New Banner </span>
            </a> -->
        </h4>
    </div>

    <div class="panel-body">

        <div id="bannerList" class="">
            <form action="<?=BASEURL . 'cmsbanner_management/addBannerCms'?>" method="post" role="" accept-charset="utf-8" enctype="multipart/form-data">
                <input type="hidden" name="bannercmsId" class="form-control input-sm" required>
                <div class="dataTables_wrapper form-inline dt-bootstrap no-footer">
                    <div class="category-banner">
                        <p>Category:</p>
                        <div class="catergory">
                            <label for="homeBanner" class="control-label col col-md-3 pl-0">
                                <input class="r_button" type="radio" name="category" id="homeBanner" data-width="1920" data-height="470" value="home" checked>  <?= ucwords(lang('Home Banner')) ?>
                            </label>
                            <label for="promoBanner" class="control-label col col-md-3">
                                <input class="r_button" type="radio" name="category" id="promoBanner" data-width="588" data-height="250" value="promo">  <?= ucwords(lang('Promo Banner')) ?>
                            </label>
                            <p class="note"> <?= lang('Note: this banner you are about to add is for home page or promo') ?></p>
                        </div>
                    </div>

                    <!-- Home Banner -->
                    <div class="bannerTab homeBanner">
                        <div class="banner-uploader">
                            <div class="file-upload-box">
                                <input type="file" id="userfile" name="userfile">
                                <label for="userfile"> <?= ucwords(lang('Choose file to upload')) ?></label>
                                <p><?= lang('Drag & Drop to Upload File') ?></p>
                                <p class="note">only image file are allowed to upload 
                                    <span>.jpg .png .gif.webp</span>
                                 </p>
                                 <?php if($this->utils->getConfig('enabled_cms_banner_dimension_validation')){ ?>
                                 <p class="note dimension_note">w/ dimension of <?= $this->utils->getConfig('default_cms_banner_dimension')['home_banner']['width']."x".$this->utils->getConfig('default_cms_banner_dimension')['home_banner']['height']. " pixel"; ?></p>
                                <?php } ?>
                                <div class="image_upload">
                                    <img src="" id="editBannerCmsImg" alt>
                                </div>
                            </div>
                        </div>

                        <div class="banner-fields">
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="bannerOrder" class="control-label"><?= ucwords(lang('Banner Order Number')) ?></label>
                                        <input id="bannerOrder" type="text"
                                            class="form-control input-sm user-success" value="1" name="order">
                                    </div>
                                    <div class="col-md-8">
                                        <p>Language: </p>
                                        <?php 
                                            if(!empty($languages)){
                                                foreach ($languages as $key => $value) {
                                                    $checked = $value['key'] == 1 ? "checked": "";
                                        ?>
                                                    <label class="col-md-2 col-sm-12">
                                                        <input type="checkbox" class="chb" name="language" value="<?= $value['key'] ?>" <?= $checked ?>> <?= ucwords($value['word']) ?>
                                                    </label>
                                        <?php 
                                                }
                                                
                                            }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="title" class="control-label"><?= ucwords(lang('Title')) ?></label>
                                        <input id="title" type="text" class="form-control input-sm user-success"
                                            value="" name="title" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="summary" class="control-label"><?= ucwords(lang('Summary')) ?></label>
                                        <input id="summary" type="text" class="form-control input-sm user-success"
                                            value="" name="summary" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="sdate" class="control-label"><?= ucwords(lang('Start Date')) ?></label>
                                        <input id="sdate" type="text" class="form-control input-sm user-success dateInput"
                                            value="<?=$dateFrom;?>" data-time="true" data-future="true" name="start_at" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="edate" class="control-label"><?= ucwords(lang('End Date')) ?></label>
                                        <input id="edate" type="text" class="form-control input-sm user-success dateInput"
                                            value="<?=$dateTo;?>" data-time="true" data-future="true" name="end_at" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group link">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="link" class="control-label"><?= ucwords(lang('Link')) ?></label>
                                        <input id="link" type="text" class="form-control input-sm user-success"
                                            value="" name="link">
                                    </div>
                                    <div class="col-md-8">
                                        <p><?= ucwords(lang('Target Link')) ?> </p>
                                        <label class="col-md-2 col-sm-12 pl-0">
                                            <input type="radio" value="_self" name="link_target" checked> <?= ucwords(lang('Current Window')) ?>
                                        </label>
                                        <label class="col-md-2 col-sm-12">
                                            <input type="radio" value="_blank" name="link_target"> <?= ucwords(lang('Open New Tab')) ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="gstring" class="control-label"> <?= ucwords(lang('Game Type String')) ?></label>
                                        <input id="gstring" type="text" class="form-control input-sm user-success"
                                            value="" name="game_gametype">
                                            <span class='uploadNote cms-sub-mesg'><?=lang('cms.notes_game_type_string');?></span>
                                    </div>
                                    <div class="col-md-8">
                                        <p><?= ucwords(lang('Game Platform')) ?></p>
                                        <?php 
                                            if(!empty($game_apis)){
                                                foreach ($game_apis as $key => $value) {
                                        ?>
                                                    <label class="col-md-4 col-sm-12">
                                                        <input name="game_platform_id" type="radio" value="<?= $value['id'] ?>"> <?= $value['system_code'] ?>
                                                    </label>
                                        <?php 
                                                }
                                                
                                            }
                                        ?>
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <input type="button" class="btn btn-linkwater" id="form_button_cancel" value="<?= ucwords(lang('Cancel')) ?>" style="display: none;">
                                <input type="submit" class="btn btn-scooter" id="form_submit_button" value="<?= ucwords(lang('Save')) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <table class="table table-striped table-bordered table-hover dataTable no-footer dtr-column collapsed" id="banner_table" style="width: 100%;" role="grid" aria-describedby="my_table_info">
                <thead>
                    <tr role="row">
                        <th><?= ucwords(lang('Action')) ?></th>
                        <th ><?= ucwords(lang('Banner')) ?></th>
                        <th><?= ucwords(lang('Category')) ?></th>
                        <th><?= ucwords(lang('Title')) ?></th>
                        <th><?= ucwords(lang('Link')) ?></th>
                        <th><?= ucwords(lang('Game Platform')) ?></th>
                        <th><?= ucwords(lang('Game Type String')) ?></th>
                        <th><?= ucwords(lang('Start Date')) ?></th>
                        <th><?= ucwords(lang('End Date')) ?></th>
                        <th><?= ucwords(lang('Order')) ?></th>
                        <th><?= ucwords(lang('Status')) ?></th>
                    </tr>
                </thead>

                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        bannerJson = '<?php echo $bannerJson; ?>';
        var jsonData = JSON.parse(bannerJson);
        var table = $('#banner_table').DataTable({
            data: jsonData,
            dom: "<'panel-body' <'pull-right'B><'pull-right'f><'pull-right progress-container'>l><'dt-information-summary1 text-info pull-left' i>t<'text-center'r><'panel-body'<'pull-right'p><'dt-information-summary2 text-info pull-left' i>>",
            buttons: [
                {
                    text: "<?php echo lang('Expand all'); ?>",
                    action: function ( e, dt, node, config ) {
                        table.rows().every(function(){
                            // If row has details collapsed
                            if(!this.child.isShown()){
                                // Open this row
                                this.child(format(this.data())).show();
                                $(this.node()).addClass('shown');
                                $(this.node()).find('td a.more-info').toggleClass('show');
                            }
                        });
                    }
                },
                {
                    text: "<?php echo lang('Collapse all'); ?>",
                    action: function ( e, dt, node, config ) {
                        table.rows().every(function(){
                            // If row has details expanded
                            if(this.child.isShown()){
                                // Collapse row details
                                this.child.hide();
                                $(this.node()).removeClass('shown');
                                $(this.node()).find('td a.more-info').toggleClass('show');
                            }
                        });
                    }
                }
            ],
            columns: [
                        
                        { 
                            data: 'bannerId',
                            "orderable":      false,
                            "data":           null,
                            "defaultContent": '',
                            render: function(data, type, row, meta) {
                                return '<a href="javascript:void(0)" class="more-info" data-id="'+row['bannerId']+'"></a>';
                            }
                        },
                        { 
                            data: 'banner_img_url',
                            render: function(data) {
                                if(isNullAndUndef(data)){
                                     return '<i class="text-muted">' + "<?php echo  lang("lang.norecord") ; ?>" +'<i/>';
                                }
                                return '<img class="banner-thumb" src="'+data+'" alt="">';
                            }
                        },
                        { 
                            data: 'category',
                            render: function(data) {
                                if(isNullAndUndef(data)){
                                     return '<i class="text-muted">' + "<?php echo  lang("lang.norecord") ; ?>" +'<i/>';
                                }
                                return data;
                            }
                        },
                        { 
                            data: 'title',
                            render: function(data) {
                                if(isNullAndUndef(data)){
                                     return '<i class="text-muted">' + "<?php echo  lang("lang.norecord") ; ?>" +'<i/>';
                                }
                                return data;
                            }
                        },
                        { 
                            data: 'link',
                            render: function(data) {
                                if(isNullAndUndef(data)){
                                     return '<i class="text-muted">' + "<?php echo  lang("lang.norecord") ; ?>" +'<i/>';
                                }
                                return data;
                            }
                        },
                        { 
                            data: 'game_platform_system_code',
                            render: function(data) {
                                // console.log(data);
                                if(isNullAndUndef(data)){
                                     return '<i class="text-muted">' + "<?php echo  lang("lang.norecord") ; ?>" +'<i/>';
                                }
                                return data;
                            }
                        },
                        { 
                            data: 'game_gametype',
                            render: function(data) {
                                if(isNullAndUndef(data)){
                                     return '<i class="text-muted">' + "<?php echo  lang("lang.norecord") ; ?>" +'<i/>';
                                }
                                return data;
                            }
                        },
                        { 
                            data: 'start_at',
                            render: function(data) {
                                if(isNullAndUndef(data)){
                                     return '<i class="text-muted">' + "<?php echo  lang("lang.norecord") ; ?>" +'<i/>';
                                }
                                return data;
                            }
                        },
                        { 
                            data: 'end_at',
                            render: function(data) {
                                if(isNullAndUndef(data)){
                                     return '<i class="text-muted">' + "<?php echo  lang("lang.norecord") ; ?>" +'<i/>';
                                }
                                return data;
                            }
                        },
                        { 
                            data: 'sort_order',
                            render: function(data) {
                                if(isNullAndUndef(data)){
                                     return '<i class="text-muted">' + "<?php echo  lang("lang.norecord") ; ?>" +'<i/>';
                                }
                                return data;
                            }
                        },
                        { 
                            data: 'status',
                            className: 'status',
                            render: function(data, type, row, meta) {
                                if(data === "active"){
                                    // console.log(row);
                                    return '<label class="wa-switch">'+
                                            '<input type="checkbox"  checked="true" class="checkbox_status" data-id="'+row['bannerId']+'">'+
                                            '<span class="slider round"></span>'+
                                        '</label>'; 
                                } else {
                                    return '<label class="wa-switch">'+
                                            '<input type="checkbox" class="checkbox_status" data-id="'+row['bannerId']+'">'+
                                            '<span class="slider round"></span>'+
                                        '</label>'; 
                                }
                                
                            }
                        },            
            ],
            "order": [[9, 'asc']],
            "fnCreatedRow": function( nRow ) {
                $(nRow).addClass('odd parent');
            }
        });

        $(document).on("click","#banner_table td a.more-info",function() {
            $(this).toggleClass('show');
            var tr = $(this).closest('tr');
            var row = table.row( tr );
            id = $(this).data("id");
            
            if ( row.child.isShown() ) {
                // This row is already open - close it
                row.child.hide();
                tr.removeClass('shown');
                tr.removeClass(id+'_shown');
            }
            else {
                table.rows().every(function(){
                    if(this.child.isShown()){
                        // Collapse row details
                        this.child.hide();
                        $(this.node()).removeClass('shown');
                        $(this.node()).find('td a.more-info').toggleClass('show');
                    }
                });
                // Open this row
                row.child( format(row.data()) ).show();
                tr.addClass('shown');
                tr.addClass(id+'_shown');

                
            }
        });


        $(document).on("change",".chb",function() {
            $(".chb").prop('checked',false);
            $(this).prop('checked',true);
        });

        $(document).on("click",".checkbox_status",function() {
            id = $(this).data("id");
            isChecked = $(this).is(":checked");
            status = isChecked ? "active": "inactive";
            url = "<?= BASEURL . 'cmsbanner_management/activateBannerCms/'?>"+id+"/"+status;
            $.get(url);
        });

        $(document).on("click",".button_delete",function() {
            id = $(this).data("id");
            var url = base_url + 'cmsbanner_management/deleteBannerCmsItem/' + id;
            confirm_message = "<?= lang('Are you sure you want to delete') ?>";
            if (confirm(confirm_message)) {
                //delete
                selected_class = id+"_shown";
                // $.get(url);
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(){ 
                        // console.log();
                        table.row('.'+selected_class).remove().draw( true );
                        alert("<?= lang('Success') ?>");
                    },
                    error: function() {
                        alert("<?= lang('Something happen. Please try again.') ?>"); 
                    }
                });
            }
        });

        $(document).on("click",".button_edit",function() {
            id = $(this).data("id");
            $.ajax({
                'url': base_url + 'cmsbanner_management/getBannerCmsDetails/' + id,
                'type': 'GET',
                'dataType': "json",
                'success': function(response){
                    // console.log(response);
                    $(".chb").prop('checked',false); //uncheck all language checkbox
                    $('input[name="bannercmsId"]').val(response.data.bannerId);
                    $('input[name="title"]').val(response.data.title);
                    $('input[name="summary"]').val(response.data.summary);
                    $('input[name="order"]').val(response.data.sort_order);
                    $('input[name="link"]').val(response.data.link);
                    $('input[name="game_gametype"]').val(response.data.game_gametype);
                    // $('input[name="start_at"]').val(response.data.start_at);
                    // $('input[name="end_at"]').val(response.data.end_at);
                    $("input[name=category][value=" + response.data.category + "]").prop('checked', true);
                    $("input[name=language][value=" + response.data.language + "]").prop('checked', true);
                    $("input[name=link_target][value=" + response.data.link_target + "]").prop('checked', true);
                    $("input[name=game_platform_id][value=" + response.data.game_platform_id + "]").prop('checked', true);
                    if(!isNullAndUndef(response.data.banner_img_url)){
                        $('#editBannerCmsImg').attr('src', response.data.banner_img_url);
                        $('#editBannerCmsImg').show();
                        $('.image_upload').show();
                    } else {
                        $('#editBannerCmsImg').attr('src', "");
                        $('#editBannerCmsImg').hide();
                        $('.image_upload').hide();
                    }

                    if(!isNullAndUndef(response.data.start_at)){
                        $('input[name="start_at"]').val(response.data.start_at);
                        $('#sdate').daterangepicker({
                            timePicker: true,
                            timePicker24Hour: true,
                            singleDatePicker: true,
                            timePickerSeconds: true,
                            startDate: response.data.start_at,
                            minDate: response.data.start_at,
                            locale: {
                                format: 'YYYY-MM-DD HH:mm:ss'
                            }
                        });
                    }

                    if(!isNullAndUndef(response.data.end_at)){
                        $('input[name="end_at"]').val(response.data.end_at);
                        $('#edate').daterangepicker({
                            timePicker: true,
                            timePicker24Hour: true,
                            singleDatePicker: true,
                            timePickerSeconds: true,
                            startDate: response.data.end_at,
                            minDate: "<?= $this->utils->getNowForMysql(); ?>",
                            locale: {
                                format: 'YYYY-MM-DD HH:mm:ss'
                            }
                        });
                    }
                    
                    
                }
            }, 'json');
            $("#form_button_cancel").show();
            $("#form_submit_button").val("<?= lang("Update")  ?>");
            $('html, body').animate({
                scrollTop: $('#bannerList').offset().top - 20 
            }, 'slow');
            return false;
        });

        $(document).on("click","#form_button_cancel",function() {
            location.reload();
        });

        $(document).on("click",'label input[name="category"]',function() {
            $('#editBannerCmsImg').attr('src', "");
            $('#editBannerCmsImg').hide();
            $('.image_upload').hide();
            $('#userfile').val('');

            if ($(this).attr('value') === 'home') {
                width = "<?= $this->utils->getConfig('default_cms_banner_dimension')['home_banner']['width']; ?>";
                height = "<?= $this->utils->getConfig('default_cms_banner_dimension')['home_banner']['height']; ?>";
                $('.dimension_note').text("w/ dimension of "+width+"x"+height+ " pixel");
            } else if ($(this).attr('value') === 'promo') {
                width = "<?= $this->utils->getConfig('default_cms_banner_dimension')['promo_banner']['width']; ?>";
                height = "<?= $this->utils->getConfig('default_cms_banner_dimension')['promo_banner']['height']; ?>";
                $('.dimension_note').text("w/ dimension of "+width+"x"+height+ " pixel");
            }
        });
    });

    function format ( d ) {
        iso_lang = JSON.parse('<?= json_encode(language_function::ISO2_LANG) ?>');
        link_target = d.link_target == '_self' ? "<?= lang("Current Window")  ?>" : "<?= lang("Open New Tab")  ?>";
        return '<div class="child"><div class="more-information">'+
                    '<div class="info-group">'+
                        '<label><b>Summary</b></label>'+
                        '<p>'+d.summary+'</p>'+
                    '</div>'+
                    '<div class="info-group">'+
                        '<label><b>Link Target</b></label>'+
                        '<p>'+link_target+'</p>'+
                    '</div>'+
                    '<div class="info-group">'+
                        '<label><b>Language</b></label>'+
                        '<p>'+iso_lang[d.language]+'</p>'+
                    '</div>'+
                '</div>'+
                '<div class="actionVipGroup">'+
                    '<button class="btn btn-sm btn-scooter button_edit" data-id="'+d.bannerId+'">'+"<?= lang("Edit")?>"+'</button>'+
                    '<button class="btn btn-sm btn-danger button_delete" data-id="'+d.bannerId+'">'+"<?= lang("Delete")?>"+'</button>'+
                '</div></div>';
    }

    function isNullAndUndef(variable) {
        return (variable == null || variable == undefined || variable == "");
    }
    

    const boxElement = document.querySelector(".file-upload-box"),
    dragText = boxElement.querySelector("p"),
    inputElement = boxElement.querySelector("#userfile");

    // boxElement.addEventListener("click", (e) => {
    //     inputElement.click();
    // });

    inputElement.addEventListener("change", (e) => {
        if (inputElement.files.length) {
            appendImage(inputElement.files[0]);
        }
    });

    boxElement.addEventListener("dragover", (e) => {
        e.preventDefault();
        boxElement.classList.add("upload-box-hover");
        dragText.textContent = "<?php echo lang('Release to Upload File'); ?>";
    });

    ["dragleave", "dragend"].forEach((type) => {
        boxElement.addEventListener(type, (e) => {
            boxElement.classList.remove("upload-box-hover");
            dragText.textContent = "<?php echo lang('Drag & Drop to Upload File'); ?>";
        });
    });

    boxElement.addEventListener("drop", (e) => {
        e.preventDefault();

        if (e.dataTransfer.files.length) {
            inputElement.files = e.dataTransfer.files;
            appendImage(e.dataTransfer.files[0]);
        }

        boxElement.classList.remove("upload-box-hover");
    });

    function appendImage(file){
        let fileType = file.type; 
        let validExtensions = ["image/jpeg", "image/jpg", "image/png", "image/webp"]; 
        if(validExtensions.includes(fileType)){ 
            let fileReader = new FileReader(); //creating new FileReader object
            fileReader.onload = ()=>{
                let fileURL = fileReader.result; 
                let image = new Image();
                image.src = fileURL;
                image.onload = function() {
                    let rHeight = $('input[name="category"]:checked').data('height');
                    let rWidth = $('input[name="category"]:checked').data('width');
                    let category = $('input[name="category"]:checked').val();
                    if (category === 'home') {
                        rWidth = "<?= $this->utils->getConfig('default_cms_banner_dimension')['home_banner']['width']; ?>";
                        rHeight = "<?= $this->utils->getConfig('default_cms_banner_dimension')['home_banner']['height']; ?>";
                    } else if (category === 'promo') {
                        rWidth = "<?= $this->utils->getConfig('default_cms_banner_dimension')['promo_banner']['width']; ?>";
                        rHeight = "<?= $this->utils->getConfig('default_cms_banner_dimension')['promo_banner']['height']; ?>";
                    }

                    enabled_validation = "<?= $this->utils->getConfig('enabled_cms_banner_dimension_validation'); ?>";
                    if(enabled_validation){
                        if(this.width == rWidth && this.height == rHeight){
                            $('#editBannerCmsImg').attr('src', this.src);
                            $('#editBannerCmsImg').show();
                            $('.image_upload').show();
                        } else {
                            $('#userfile').val('');
                            msg = "<?= lang('Wrong image dimension!'); ?> \n<?= lang('Width'); ?>: " + this.width + "\n<?= lang('Height'); ?>: " + this.height;
                            alert(msg);
                        }
                    } else{
                        $('#editBannerCmsImg').attr('src', this.src);
                        $('#editBannerCmsImg').show();
                        $('.image_upload').show();
                    }
                };
            }
            fileReader.readAsDataURL(file);
        }else{
            errorMsg = "<?php echo lang('This is not an Image File!'); ?>";
            alert(errorMsg);
            dragText.textContent = "<?php echo lang('Drag & Drop to Upload File'); ?>";
        }
    }

    $("#bannerOrder").bind("keypress", function (e) {
        var keyCode = e.which ? e.which : e.keyCode
           
        if (!(keyCode >= 48 && keyCode <= 57)) {
            return false;
        }
    });
</script>
