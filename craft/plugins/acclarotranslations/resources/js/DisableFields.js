(function($) {

if (typeof Craft.AcclaroTranslations === 'undefined') {
    Craft.AcclaroTranslations = {};
}

Craft.AcclaroTranslations.DisableFields = {
    init: function() {
        var $form = $('form');
        $form.find(':input').addClass('disabled').prop('disabled', true);
        $form.find(':input,a,button').attr('tabindex', -1);
        $form.find('.input').addClass('disabled');
        $form.find('.btn').addClass('disabled');
        $form.find('.redactor-box').addClass('disabled');
        $form.append('<span class="icon acclarotranslations-lock"></span>');
    }
};

$(function() {
    Craft.AcclaroTranslations.DisableFields.init();
});

})(jQuery);