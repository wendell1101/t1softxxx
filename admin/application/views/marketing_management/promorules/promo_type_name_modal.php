<!-- Start promoName Modal -->
<div class="modal fade" id="promoNameModal" tabindex="-1" role="dialog" aria-labelledby="mainModalLabel">
    <div class="modal-dialog" role="document">
        <input type="hidden" id="promoNameModalTarget">
        <div class="modal-content">
            <form role="form" id="form_promo_name">
                <div class="modal-header">
                    <h4 class="modal-title" id="promoNameLabel"><?=lang('cms.promoTypeName');?></h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="promo_name_english"><?=lang("lang.english.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="promo_name_english" name="promo_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.english.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="promo_name_chinese"><?=lang("lang.chinese.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="promo_name_chinese" name="promo_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.chinese.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="promo_name_indonesian"><?=lang("lang.indonesian.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="promo_name_indonesian" name="promo_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.indonesian.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="promo_name_vietnamese"><?=lang("lang.vietnamese.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="promo_name_vietnamese" name="promo_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.vietnamese.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="promo_name_korean"><?=lang("lang.korean.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="promo_name_korean" name="promo_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.korean.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="promo_name_thai"><?=lang("lang.thai.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="promo_name_thai" name="promo_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.thai.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="promo_name_india"><?=lang("lang.india.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="promo_name_india" name="promo_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.india.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="promo_name_portuguese"><?=lang("lang.portuguese.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="promo_name_portuguese" name="promo_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.portuguese.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="promo_name_spanish"><?=lang("lang.spanish.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="promo_name_spanish" name="promo_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.spanish.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="promo_name_kazakh"><?=lang("lang.kazakh.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="promo_name_kazakh" name="promo_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.kazakh.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="promo_name_brazil"><?=lang("lang.brazil.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="promo_name_brazil" name="promo_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.brazil.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="promo_name_japanese"><?=lang("lang.japanese.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="promo_name_japanese" name="promo_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.japanese.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="promo_name_mexican"><?=lang("lang.mexican.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="promo_name_mexican" name="promo_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.mexican.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="promo_name_chinese_traditional"><?=lang("lang.chinese.traditional.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="promo_name_chinese_traditional" name="promo_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.chinese.traditional.name"))?></span>
                            </div>
                            <div class="form-group">
                                <label for="promo_name_filipino"><?=lang("lang.filipino.name")?> </label>
                                <input type="text" class="form-control clear-fields" id="promo_name_filipino" name="promo_name[]">
                                <span class="help-block hidden" style="color:#F04124"><?= sprintf(lang('formvalidation.required'),lang("lang.filipino.name"))?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" >
                    <div style="height:70px;position:relative;">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?=lang('lang.close');?></button>
                        <button type="button" class="btn btn-primary"  onclick="return validatePromoName();"><?=lang('Done')?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End promoName Modal -->

<script type="text/javascript">
    var promo_name_language_fields_length = 15;
    function validatePromoName(){
        var form = $("#form_promo_name");
        var inputNames = form.find('input[name^="promo_name"]');
        var promoNames = {1:"",2:"",3:"",4:"",5:"",6:"",7:"",8:"",9:"",10:"",11:"",12:"",13:"",14:"",15:""};
        form.find('.hidden').length
        form.find('span').addClass("hidden");
        $("#form_promo_name input[type=text]").each(function(){
            if ( $(this).val().length == 0 ) {
                $(this).next().removeClass("hidden")
            }
        });

        if( form.find('.hidden').length == promo_name_language_fields_length ) {
            inputNames.each(function(index) {
                switch($(this).attr('id')) {
                    case "promo_name_english":
                        promoNames[1] = $(this).val();
                        break;
                    case "promo_name_chinese":
                        promoNames[2] = $(this).val();
                        break;
                    case "promo_name_indonesian":
                        promoNames[3] = $(this).val();
                        break;
                    case "promo_name_vietnamese":
                        promoNames[4] = $(this).val();
                        break;
                    case "promo_name_korean":
                        promoNames[5] = $(this).val();
                        break;
                    case "promo_name_thai":
                        promoNames[6] = $(this).val();
                        break;
                    case "promo_name_india":
                        promoNames[7] = $(this).val();
                        break;
                    case "promo_name_portuguese":
                        promoNames[8] = $(this).val();
                        break;
                    case "promo_name_spanish":
                        promoNames[9] = $(this).val();
                        break;
                    case "promo_name_kazakh":
                        promoNames[10] = $(this).val();
                        break;
                    case "promo_name_brazil":
                        promoNames[11] = $(this).val();
                        break;
                    case "promo_name_japanese":
                        promoNames[12] = $(this).val();
                        break;
                    case "promo_name_mexican":
                        promoNames[13] = $(this).val();
                        break;
                    case "promo_name_chinese_traditional":
                        promoNames[14] = $(this).val();
                        break;
                    case "promo_name_filipino":
                        promoNames[15] = $(this).val();
                        break;
                    default:
                        //console.log('unknown name!')
                }
            });

            $.each( promoNames, function( key, value ) {
                if(value == ""){
                    promoNames[key] = promoNames[1];
                }
            });

            var jsonPretty = '_json:'+JSON.stringify(promoNames);

            var currentLang = "<?= $this->language_function->getCurrentLanguage(); ?>";
            var target =  $("#promoNameModalTarget").val();

            if( target === "promoTypeNameView" ){
                $("#promoTypeNameView").val(promoNames[currentLang]);
                $("#promoTypeName").val(jsonPretty);
            } else if( target === "editpromoTypeNameView" ) {
                $("#editpromoTypeNameView").val(promoNames[currentLang]);
                $("#editpromoTypeName").val(jsonPretty);
            }

            $('#promoNameModal').modal('hide');
        }
    }

    $(document).on("click","#editpromoTypeNameView",function(){
        $("#form_promo_name").find('span')
            .addClass("hidden")
            .find('span').text('');

        var inputNames = $('#editpromoTypeName').val();
        if( inputNames.indexOf("_json:") >= 0 ) {
            var langConvert = jQuery.parseJSON(inputNames.substring(6));
            $("#form_promo_name input[type=text]").each(function(index){
                $(this).val(langConvert[index+1]);
            });
        } else {
            $("#form_promo_name input[type=text]").val(inputNames);
        }
        $("#promoNameModalTarget").val('editpromoTypeNameView');
        $('#promoNameModal').modal('show');
    });

    $(document).on("click","#promoTypeNameView",function(){
        $("#form_promo_name").find('span').addClass("hidden");
        $("#promoNameModalTarget").val('promoTypeNameView');
        $('#promoNameModal').modal('show');
    });
</script>