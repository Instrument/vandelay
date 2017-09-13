(function($) {

if (typeof Craft.AcclaroTranslations === 'undefined') {
    Craft.AcclaroTranslations = {};
}

/**
 * Order entries class
 */
Craft.AcclaroTranslations.OrderEntries = {
    $checkboxes: null,
    $selectAllCheckbox: null,
    $publishSelectedBtn: null,

    hasSelections: function() {
        return this.$checkboxes.filter(':checked').length > 0;
    },
    toggleSelected: function(toggle) {
        this.$checkboxes.prop('checked', toggle);

        this.togglePublishButton();
    },
    toggleSelectAllCheckbox() {
        this.$selectAllCheckbox.prop(
            'checked',
            this.$checkboxes.filter(':checked').length === this.$checkboxes.length
        );
    },
    togglePublishButton: function() {
        if (this.hasSelections()) {
            this.$publishSelectedBtn.prop('disabled', false).removeClass('disabled');
        } else {
            this.$publishSelectedBtn.prop('disabled', true).addClass('disabled');
        }
    },
    init: function() {
        this.$publishSelectedBtn = $('.acclarotranslations-publish-selected-btn');
        this.$selectAllCheckbox = $('thead .acclarotranslations-checkbox-cell :checkbox');
        this.$checkboxes = $('tbody .acclarotranslations-checkbox-cell :checkbox').not('[disabled]');

        this.$selectAllCheckbox.on('change', function() {
            Craft.AcclaroTranslations.OrderEntries.toggleSelected($(this).is(':checked'));
        });

        this.$checkboxes.on('change', function() {
            Craft.AcclaroTranslations.OrderEntries.togglePublishButton();
            Craft.AcclaroTranslations.OrderEntries.toggleSelectAllCheckbox();
        });
    }
};

$(function() {
    Craft.AcclaroTranslations.OrderEntries.init();
});

})(jQuery);