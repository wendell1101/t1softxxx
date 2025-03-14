<!-- Start questName Modal -->
<div class="modal fade" id="questNameModal" tabindex="-1" role="dialog" aria-labelledby="mainModalLabel">
    <div class="modal-dialog" role="document">
        <input type="hidden" id="questNameModalTarget">
        <div class="modal-content">
            <form role="form" id="form_quest_name">
                <div class="modal-header">
                    <h4 class="modal-title" id="questNameLabel"><?=lang('cms.questCategoryName');?></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="quest_name_english"><?=lang("lang.english.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="quest_name_english" name="quest_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.english.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="quest_name_chinese"><?=lang("lang.chinese.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="quest_name_chinese" name="quest_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.chinese.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="quest_name_indonesian"><?=lang("lang.indonesian.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="quest_name_indonesian" name="quest_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.indonesian.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="quest_name_vietnamese"><?=lang("lang.vietnamese.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="quest_name_vietnamese" name="quest_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.vietnamese.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="quest_name_korean"><?=lang("lang.korean.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="quest_name_korean" name="quest_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.korean.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="quest_name_thai"><?=lang("lang.thai.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="quest_name_thai" name="quest_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.thai.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="quest_name_india"><?=lang("lang.india.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="quest_name_india" name="quest_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.india.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="quest_name_portuguese"><?=lang("lang.portuguese.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="quest_name_portuguese" name="quest_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.portuguese.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="quest_name_spanish"><?=lang("lang.spanish.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="quest_name_spanish" name="quest_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.spanish.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="quest_name_kazakh"><?=lang("lang.kazakh.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="quest_name_kazakh" name="quest_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.kazakh.name"))?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" >
                    <div style="height:70px;position:relative;">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=lang('lang.close');?></button>
                        <button type="button" class="btn btn-primary"  onclick="return validateQuestName();"><?=lang('Done')?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End questName Modal -->

<script type="text/javascript">
    var quest_name_language_fields_length = 10;
    function validateQuestName(){
        var form = $("#form_quest_name");
        var inputNames = form.find('input[name^="quest_name"]');
        var questNames = {1:"",2:"",3:"",4:"",5:"",6:"",7:"",8:"",9:"",10:""};
        form.find('.hidden').length
        form.find('span').addClass("hidden");
        $("#form_quest_name input[type=text]").each(function(){
            if ( $(this).val().length == 0 ) {
                $(this).next().removeClass("hidden")
            }
        });

        if( form.find('.hidden').length == quest_name_language_fields_length ) {
            inputNames.each(function(index) {
                switch($(this).attr('id')) {
                    case "quest_name_english":
                        questNames[1] = $(this).val();
                        break;
                    case "quest_name_chinese":
                        questNames[2] = $(this).val();
                        break;
                    case "quest_name_indonesian":
                        questNames[3] = $(this).val();
                        break;
                    case "quest_name_vietnamese":
                        questNames[4] = $(this).val();
                        break;
                    case "quest_name_korean":
                        questNames[5] = $(this).val();
                        break;
                    case "quest_name_thai":
                        questNames[6] = $(this).val();
                        break;
                    case "quest_name_india":
                        questNames[7] = $(this).val();
                        break;
                    case "quest_name_portuguese":
                        questNames[8] = $(this).val();
                        break;
                    case "quest_name_spanish":
                        questNames[9] = $(this).val();
                        break;
                    case "quest_name_kazakh":
                        questNames[10] = $(this).val();
                        break;
                    default:
                        //console.log('unknown name!')
                }
            });

            $.each( questNames, function( key, value ) {
                if(value == ""){
                    questNames[key] = questNames[1];
                }
            });

            var jsonPretty = '_json:'+JSON.stringify(questNames);

            var currentLang = "<?= $this->language_function->getCurrentLanguage(); ?>";
            var target =  $("#questNameModalTarget").val();

            if( target === "questCatecoryTitleView" ){
                $("#questCatecoryTitleView").val(questNames[currentLang]);
                $("#questCatecoryTitle").val(jsonPretty);
            } else if( target === "editquestCategoryTitleView" ) {
                $("#editquestCategoryTitleView").val(questNames[currentLang]);
                $("#editquestCategoryTitle").val(jsonPretty);
            }

            $('#questNameModal').modal('hide');
        }
    }

    $(document).on("click","#editquestCategoryTitleView",function(){
        $("#form_quest_name").find('span')
            .addClass("hidden")
            .find('span').text('');

        var inputNames = $('#editquestCategoryTitle').val();
        console.log(inputNames)
        if( inputNames.indexOf("_json:") >= 0 ) {
            var langConvert = jQuery.parseJSON(inputNames.substring(6));
            $("#form_quest_name input[type=text]").each(function(index){
                $(this).val(langConvert[index+1]);
            });
        } else {
            $("#form_quest_name input[type=text]").val(inputNames);
        }
        $("#questNameModalTarget").val('editquestCategoryTitleView');
        $('#questNameModal').modal('show');
    });

    $(document).on("click","#questCatecoryTitleView",function(){
        $("#form_quest_name").find('span').addClass("hidden");
        $("#questNameModalTarget").val('questCatecoryTitleView');
        $('#questNameModal').modal('show');
    });
</script>