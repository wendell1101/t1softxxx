var PlayerAttachDocument = {
	current_kyc_status: '',
	allowed_withdrawal_status: '',
	confirmed_delete: '',
    confirmed_visible: '',
    confirmed_not_visible: '',
    txt_comment:'',
    dfd: $.Deferred(),

	init: function() {
		var self = this;
		$('#kyc_status').text(self.current_kyc_status);
	    $('#allowed_withdrawal_status').text(self.allowed_withdrawal_status);
	    self.initTextarea();
        self.registerEvents();
	},

	openNav: function() {
		var self = this;
        document.getElementById("overlay").style.width = "100%";
    },

    closeNav: function() {
		var self = this;
        document.getElementById("overlay").style.width = "0%";
    },

    getFileData: function(myFile) {
		var self = this;
		var file = myFile.files[0];
		var filename = file.name;
		$(myFile).parent().find("span").text(filename);
    },

    removeImage: function( picID , playerId , tag, comments ) {
		var self = this;
        if (confirm(self.confirmed_delete)) {
        	$.post('/player_management/delKYCPlayerImage/', {'picId' : picID , 'playerId' : playerId, 'tag' : tag, 'comments' : comments} ,function(data){
                alert(data.msg);
                modal('/player_management/player_attach_document/'+playerId);
	        });
        } else {
            event.preventDefault();
        }
    },

    submit_form: function() {
		var self = this;
    },

    InitRemarks: function(fields) {
		var self = this;
		$(fields).parent().find('.hidden-remarks').val($(fields).val());
    },

    InitComments: function(fields) {
		var self = this;
		$(fields).parent().find('.hidden-comments').val($(fields).val());
    },

    initTextarea: function() {
	    var self = this;
        var one_row_imgs = 4;
        var default_rows = 2;
        $.each(self.txt_comment, function (i, val){
            var total_imgs = $('#form_' + val + ' .image-container .multi-img').size();
            var need_generate = false;
            var total_rows = default_rows;

            if(total_imgs > 0){
                total_rows = Math.ceil(total_imgs / one_row_imgs);
                need_generate = total_rows > default_rows;
            }

            if(need_generate){
                var height = (total_rows*50-30) + 'px'; //50 id for img height, 30 is for non textarea
                $('#txt_comments_'+val).css('height', height);
            }
        });
    },

    updateVisibilitystatus: function(action, picId, playerId, tag) {
        var self = this;
        if(action === "visible"){
            var confirmation = self.confirmed_visible;
        } else {
            var confirmation = self.confirmed_not_visible;
        }
        if (confirm(confirmation)) {
            $.post('/player_management/updateVisibilitystatus/', {'action' : action , 'playerId' : playerId, 'picId' : picId, 'tag' : tag} ,function(data){
                alert(data.msg);
                modal('/player_management/player_attach_document/'+playerId);
            });
        }
    },
    registerEvents: function(){
        var self = this;

        /// for hook only once, patch to repeat Twice select image popup event
        var mark$El = $('.modal-attach-proof').parent();
        if( mark$El.data('had_register_events') == 1 ){
            return; // cancel for the events had registed.
        }
        // mark the events will be regist.
        mark$El
            .attr('data-had_register_events', 1)
            .prop('data-had_register_events',1);


        // Register Events
        $('body').on("click",".add-image-btn",function(e) {
            self.clicked_add_image_btn(e);
        });
        $('body').on("click",".img_thumbnail",function(e) {
            self.clicked_img_thumbnail(e);
        });
        $('body').on("change",'input[name="txtImage[]"]',function(e) {
            self.changed_input_txtImage(e);
        });


    }, // EOF registerEvents

    init_dfd_preview: function(){
        var self = this;

        self.dfd= $.Deferred();
        self.dfd.always( function(){
            var cloned_arguments = Array.prototype.slice.call(arguments);
            self.callback_from_dfd_preview.apply(self, cloned_arguments)
        });
    },
    callback_from_dfd_preview:function(preview_src, e){
        var self = this;

        var cloned_arguments = Array.prototype.slice.call(arguments);

        $(e.target).closest(".attach-docu").find('.add-image-btn')
                                            .css({ 'background-size': 'cover'
                                                , 'background-repeat': 'no-repeat'
                                                , 'background-image': 'url('+ preview_src+ ')'
                                            });

    },
    /// The delegate methods for registerEvents
    clicked_add_image_btn: function(e){
        var self = this;
        self.init_dfd_preview();
        $(e.target).closest("form").find('.custom-file-upload').trigger('click'); // will call changed_input_txtImage()
    },
    clicked_img_thumbnail: function(e){
        var self = this;

        $src = $(e.currentTarget).find('img').attr("src");
        $(".overlay").find(".img_container").find("img").attr("src",$src);
        $uploadedBy = $(e.currentTarget).parent().find(".uploaded-by").val();
        $(".overlay").find(".img-info").find(".overlay-uploaded-by").html($uploadedBy);
        $dateUploaded = $(e.currentTarget).parent().find(".timestamp").val();
        $(".overlay").find(".img-info").find(".overlay-timestamp").html($dateUploaded);

        $visibleToPlayer = $(e.currentTarget).parent().find(".visible_to_player").val();

        $picid = $(e.currentTarget).data('picid');
        $tag = $(e.currentTarget).data('tag');
        $playerid = $(e.currentTarget).data('playerid');
        $comments = $(e.currentTarget).data('comments');
        $(".overlay").find(".image-on-hover").find(".delete-btn").attr('onclick',"return PlayerAttachDocument.removeImage("+$picid+","+$playerid+","+"'"+$tag+"'"+","+"'"+$comments+"'"+");");

        if($visibleToPlayer === '1'){
            $('.not-visible-to-player').hide();
            $('.visible-to-player').show();
            $(".overlay").find(".image-on-hover").find(".visible-to-player").attr('onclick',"return PlayerAttachDocument.updateVisibilitystatus('not_visible',"+$picid+","+$playerid+","+"'"+$tag+"'"+");");
        } else {
            $('.visible-to-player').hide();
            $('.not-visible-to-player').show();
            $(".overlay").find(".image-on-hover").find(".not-visible-to-player").attr('onclick',"return PlayerAttachDocument.updateVisibilitystatus('visible',"+$picid+","+$playerid+","+"'"+$tag+"'"+");");

        }

        self.openNav();
    }, // EOF clicked_img_thumbnail
    changed_input_txtImage: function(e){
        var self = this;

        self.getFileData(e.target);

        var preview_src = URL.createObjectURL(e.target.files[0]);
        $(e.target).attr('data-preview_src', preview_src)
            .data('preview_src', preview_src);

        self.dfd.resolve.apply(self, [preview_src, e]); // will call callback_from_dfd_preview()

    }, // EOF changed_input_txtImage

}
