<style>
    /* .player_note_list-group {
        height: 50%;
        overflow: scroll;
        overflow-x: hidden;
    } */
    .player-remarks-modal .vertical-alignment-helper {
        display:table;
        height: 100%;
        width: 100%;
        pointer-events:none; /* This makes sure that we can still click outside of the modal to close it */
    }
    .player-remarks-modal .vertical-align-center {
        /* To center vertically */
        display: table-cell;
        vertical-align: middle;
        pointer-events:none;
    }
    .player-remarks-modal .modal-content {
        /* Bootstrap sets the size of the modal in the modal-dialog class, we need to inherit it */
        max-width:inherit; /* For Bootstrap 4 - to avoid the modal window stretching full width */
        height:inherit;
        pointer-events: all;
    }
    .dot-btn{
        margin-left: -15px;
        background-color: transparent;
    }
    .player-remarks-modal{
        position: absolute;
        top: -66px;
        right: -21px;
        width: 600px;
    }
    #edit-player-remarks-modal{
        z-index: 1090;
    }
    #delete-player-remarks-modal{
        z-index: 1090;
    }
    .note_list-item .btn-group{
        display: none;
        height:12px;
    }
    .note_list-item:hover .btn-group{
        display: block;
    }
    .note_list-item:hover{
        background-color: #f0f0f0;
    }
    .remarks-error-text p{
        font-size: 8px;
    }
    .note_list-item{
        box-sizing:border-box;
        padding-bottom:90px;
        border-style:none;
    }
    .old_note{
        border-style: none;
    }
    .note-content-css{
        box-sizing:border-box;
        padding-top:8px;
        height:100px;
        max-height:160px;
        max-width:505px;
        word-break: break-all;
    }
</style>
<div>
    <?php if($this->utils->getConfig('add_tag_remarks')):?>
<div class="row">
        <div class="col-md-6">
            <label style="padding-bottom: 10px"><?=lang('filter');?>:</label>
                <select onchange="filterNotes(this.value,<?= $player_id?>)">
                <option value="all" name="tag_remark_id"><?=lang('All')?></option>
                    <?php
                        if(!empty($tagRemarks)):
                            foreach ($tagRemarks as $tag): ?>
                                <option value="<?= $tag['remarkId'];?>" name="tag_remark_id" 
                                <?=!empty($tag_remark_id)&&$tag_remark_id==$tag['remarkId']?"selected":"";?>><?= $tag['tagRemarks'];?></option>
                    <?php
                            endforeach; 
                        endif
                    ?>
                </select>
            </div>
        </div>
<?php endif;?>
    <ul class="player_note_list-group">
        <?php
        if(!empty($notes)):
            foreach ($notes as $note): ?>
            <li class="btn-control list-group-item note_list-item">
                <input class="old_note" type="hidden" value="<?=$note['notes']?>"/>
                <div class="row">
                    <div class="col-md-2">
                        <span class="text-muted pull-right"><?=$note['username']?></span>
                    </div>
                    <div class="col-md-9">
                        <span class="text-muted">
                            <?php
                                if(strtotime($note['updatedOn']) > strtotime($note['createdOn'])) {
                                    echo $note['updatedOn'] . ' (Edited)';
                                }else{
                                    echo $note['createdOn'];
                                }
                            ?>
                        </span>
                    </div>
                    <?php if ($this->permissions->checkPermissions('control_edit_delete_note')){  ?>
                    <div class="btn-group col-md-1">
                        <button class="dot-btn btn glyphicon glyphicon-option-horizontal dropdown-toggle drop_notes" type="button" data-toggle="dropdown">
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <!-- Edit Remarks 小視窗 -->
                            <li><a href="javascript:void(0)" onclick="return get_noteid_Info(this, <?=$note['noteId']?>, `<?=$note['notes']?>`, <?=$player_id?>, 'edit-remark'); return false;"><span><?=lang('lang.edit')?></span></a></li>
                            <!-- Delete Remarks 小視窗 -->
                            <li><a href="javascript:void(0)" onclick="return get_noteid_Info(this, <?=$note['noteId']?>, `<?=$note['notes']?>`, <?=$player_id?>, 'delete-remark'); return false;"><span><?=lang('lang.delete')?></span></a></li>
                        </ul>
                    </div>
                    <?php } ?>
                </div>
                <div class="col-md-12 note-content-<?=$note['noteId']?> note-content-css">
                    <?=$note['notes']?>
                </div>
            </li>
        <?php
            endforeach;
        endif; ?>
    </ul>

    <form action="/player_management/add_player_notes/<?=$player_id?>" method="post" onsubmit="return add_player_notes(this, <?=$player_id?>)">
        <div class="form-group col-md-12">
            <textarea name="notes" class="form-control input-sm" rows="5" placeholder="<?=lang('Add remarks')?>..." style="resize: none; max-height: 180px;" onkeyup="autogrow(this); limitTextArea(this,240,'#add-remarks-error-text');" onkeypress="limitTextArea(this,240,'#add-remarks-error-text');" onpaste="limitTextArea(this,240,'#add-remarks-error-text');" required></textarea>
        </div>
        <?php if($this->utils->getConfig('add_tag_remarks')):?>
        <div class="row">
            <div class="col-md-6">
            <label><?=lang("Remarks")?>:</label>
                <select  name="tag_remark_id">
                    <?php
                        if(!empty($tagRemarks)):
                            foreach ($tagRemarks as $tag): ?>
                                <option value="<?= $tag['remarkId'];?>"><?= $tag['tagRemarks'];?></option>
                    <?php
                        endforeach; 
                    endif
                    ?>
                </select>
            </div>
        </div>
        <?php endif;?>
        <div class="row">
            <div class="col-lg-6 text-left remarks-error-text" id="add-remarks-error-text" style="margin: -5px 18px 0 16px;">
                <p style="color:white;"><?=lang('Maximum characters')?></p>
            </div>
            <div class="col-lg-5 text-right">
                <button id="preview-send-btn" class="btn btn-primary pull-right btn-sm" style="width:90px; padding:8px; margin-top:-3px;"><?=lang('Add Remarks')?></button>
            </div>
        </div>
    </form>
</div>

<script>
    function limitTextArea(field,lenMax,idName){
        if (field.value.length > lenMax){
            if(idName == "#edit-remarks-error-text"){
                $('#edit-remarks-error-text p').attr("style","color:red");
            }else{
                $('#add-remarks-error-text p').attr("style","color:red");
            }
            field.value = field.value.substring(0, lenMax);
        }else{
            if(idName == "#edit-remarks-error-text"){
                $('#edit-remarks-error-text p').attr("style","color:white");
            }else{
                $('#add-remarks-error-text p').attr("style","color:white");
            }
        }
    }

    function edit_player_note(self, note_id, player_id, action = 'save') {
        var note_list    = $(self).closest('.note_list-item');
        var old_note     = $(note_list).find('.old_note').val();
        var display_note = $(note_list).find('.display_note');
        var edit_remark_modal = $(note_list).find('#edit-player-remarks-modal');

        if (action == 'save'){ //Save
            var url = '/player_management/edit_player_notes/' + note_id + '/' + player_id;
            var params = $(self).serializeArray();
            $.post(url, params, function(data) {
                if (data.success) {
                    refresh_modal('player_notes', "/player_management/player_notes/" + player_id, 'Player Notes', true);
                }else{
                    location.reload();
                }
            });
        }

        if (action == 'cancel'){ //Cancel
            $(display_note).empty().append('<textarea name="new_notes" class="form-control input-sm m-b-10" style="max-width: 100%;min-width: 100%;height: 155px; resize: none;" onkeyup="limitTextArea(this,240,'+"'"+'#edit-remarks-error-text'+"'"+');" onkeypress="limitTextArea(this,240,'+"'"+'#edit-remarks-error-text'+"'"+');" onpaste="limitTextArea(this,240,'+"'"+'#edit-remarks-error-text'+"'"+');">' + old_note + '</textarea>');
            $(edit_remark_modal).addClass('hide');
            $('#backdrop-edit-' + note_id).removeClass("modal-backdrop in");
        }

        return false;
    }

    function remove_player_note(note_id, player_id) {
        var url = '/player_management/remove_player_note/' + note_id + '/' + player_id;
        $.getJSON(url, function(data) {
            if (data.success) {
                refresh_modal('player_notes', "/player_management/player_notes/" + player_id, 'Player Notes', true);
            }else{
                location.reload();
            }
        });

        return false;
    }

    function filterNotes(tag_remark_id, player_id) {
        refresh_modal('player_notes', "/player_management/filter_player_notes/"+tag_remark_id+"/"+ player_id, 'Player Notes', true);
        return false;
    }

    function closebtn_isclicked(self, note_id, type) {
        var note_list   = $(self).closest('.note_list-item');
        var error_modal = $(".remark-modal");

        $(error_modal).addClass('hide');
        $('#backdrop-' + type + '-' + note_id).removeClass("modal-backdrop in");
    }


    function get_noteid_Info(self, note_id, notes, playerId ,active = 'edit-remark'){
        var url = '/player_management/get_noteid_Info/' + note_id;
        var note_list = $(self).closest('.note_list-item');
        var remove_edit_modal = $(note_list).find('#remove_edit_modal_' + note_id);
        var remove_delete_modal = $(note_list).find('.remove_delete_modal_' + note_id);
        var backdrop_delete = $(note_list).find('#backdrop-delete-' + note_id);

        backdrop_delete.remove();
        remove_edit_modal.remove();
        remove_delete_modal.remove();

        // <!-- Edit Remarks 小視窗 -->
        var edit_remarks_modal =
        '<form action="" method="post" id="remove_edit_modal_' + note_id + '" onsubmit="return edit_player_note(this,' + note_id + ',' + playerId + ",'" + 'save' + "'" +')">' +
            '<div class="player-remarks-modal hide remark-modal" id="edit-player-remarks-modal" tabindex="-1" role="dialog">' +
                '<div class="vertical-alignment-helper">' +
                    '<div class="modal-content">' +
                        '<div class="modal-header">' +
                            '<button type="button" class="close">' +
                                '<span class="close-btn" onclick="closebtn_isclicked(this,' + note_id + ",'" + 'edit' + "'" + '); return edit_player_note(this,' + note_id + ',' + playerId + ",'" + 'cancel' + "'" + ');">×</span>' +
                            '</button>' +
                            '<h4 class="modal-title"><?=lang('Edit Remark')?></h4>' +
                        '</div>' +
                        '<div class="modal-body">' +
                            '<span class="display_note">' +
                                '<textarea name="new_notes" class="form-control input-sm m-b-10" style="max-width: 100%;min-width: 100%;height: 155px; resize: none;" onkeyup="limitTextArea(this,240,' + "'" + '#edit-remarks-error-text' + "'" +');" onkeypress="limitTextArea(this,240,' + "'" + '#edit-remarks-error-text' + "'" +');" onpaste="limitTextArea(this,240,' + "'" + '#edit-remarks-error-text' + "'" +');">' +
                                    notes +
                                '</textarea>'+
                            '</span>'+
                            //add remarks
                            <?php if($this->utils->getConfig('add_tag_remarks')):?>
                            '<div class="row">' +
                                '<div class="col-md-6">'+
                                    '<label><?=lang("Remarks")?>:</label>'+
                                        '<select  id="edit_tag_remark" name="edit_tag_remark" class="edit_tag_remark">'+
                                            '<?php
                                                if(!empty($tagRemarks)):
                                                    foreach ($tagRemarks as $tag): ?>
                                                        <option value="<?= $tag['remarkId'] ;?>"><?= $tag['tagRemarks'];?></option>'+
                                            '<?php
                                                    endforeach; 
                                                endif
                                            ?>'+
                                    '</select>'+
                                '</div>'+
                            '</div>' +
                            <?php endif;?>
                            '<div class="row">' +
                                '<div class="col-lg-7 text-left remarks-error-text" id="edit-remarks-error-text">' +
                                    '<p style="color:white;"><?=lang('Maximum characters')?></p>' +
                                '</div>' +
                                '<div class="col-lg-5 text-right">' +
                                    '<button type="button" class="btn btn-default btn-sm cancel_notes" onclick="return edit_player_note(this,' + note_id + ',' + playerId + ",'" + 'cancel' + "'" + ')">' +
                                        '<span><?=lang('lang.cancel')?></span>' +
                                    '</button>' +
                                    '<button type="submit" class="btn btn-primary btn-sm save_notes">' +
                                        '<span><?=lang('lang.save')?></span>' +
                                    '</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="" id="backdrop-edit-' + note_id + '"></div>' +
        '</form>';

        // <!-- Delete Remarks 小視窗 -->
        var delete_remarks_modal =
        '<div class="player-remarks-modal hide remark-modal remove_delete_modal_' + note_id + '" id="delete-player-remarks-modal" tabindex="-1" role="dialog">' +
            '<div class="vertical-alignment-helper">' +
                '<div class="modal-content">' +
                    '<div class="modal-header">' +
                        '<button type="button" class="close">' +
                            '<span class="close-btn" onclick="closebtn_isclicked(this,' + note_id + ",'" + 'delete' + "'" +')">×</span>' +
                        '</button>' +
                        '<h4 class="modal-title"><?=lang('Delete Remark')?></h4>' +
                    '</div>' +
                    '<div class="modal-body">' +
                        '<div class="m-b-25 m-t-15">' +
                            '<p style="color: #6f6f6f; font-size: 14px;"><?=lang('Are you sure you want to delete this remark?')?></p>' +
                        '</div>' +
                        '<div class="text-right">' +
                            '<button type="button" class="btn btn-default btn-sm" onclick="return get_noteid_Info(this,' + note_id + ",'" + notes + "'," + playerId + ",'" + 'cancel-delete' + "'" + ');"><?=lang('lang.cancel')?></button>' +
                            '<button type="button" class="btn btn-danger btn-sm" onclick="return remove_player_note('+ note_id + ',' + playerId + ');"><?=lang('lang.delete')?></button>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>' +
        '<div class="" id="backdrop-delete-' + note_id + '"></div>';

        $('.note-content-' + note_id).after(delete_remarks_modal).after(edit_remarks_modal);

        var edit_remarks_modal = $(note_list).find('#edit-player-remarks-modal');
        var delete_remarks_modal = $(note_list).find('#delete-player-remarks-modal');

        if(active == 'edit-remark'){
            var modal = edit_remarks_modal.removeClass("hide");
            var modal_backdrop = $('#backdrop-edit-' + note_id).addClass("modal-backdrop in");

        }

        if(active == 'delete-remark'){
            var modal = delete_remarks_modal.removeClass("hide");
            var modal_backdrop = $('#backdrop-delete-' + note_id).addClass("modal-backdrop in");
        }

        if(active == 'cancel-delete'){
            var modal = delete_remarks_modal.addClass("hide");
            var modal_backdrop = $('#backdrop-delete-' + note_id).removeClass("modal-backdrop in");
        }

        $.getJSON(url, function(data) {
            if (data.success) {

                var tag_remark_id = data.note.tag_remark_id;
                var remark_id=document.getElementById("edit_tag_remark");
                var option = remark_id.getElementsByTagName('option');

                var tag_remark_option=$("#edit_tag_remark option");
                tag_remark_option.each(function(){
                    if($(this).val() == data.note.tag_remark_id){
                        $(this).prop('selected', true);
                    }
                });

                modal;
                modal_backdrop;
            }
        });
        return false;
    }


</script>