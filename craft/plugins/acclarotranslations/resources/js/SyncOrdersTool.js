(function($) {

if (typeof Craft.AcclaroTranslations === 'undefined') {
    Craft.AcclaroTranslations = {};
}

/**
 * Order index class
 */
Craft.AcclaroTranslations.SyncOrdersTool = Craft.Tool.extend(
{
    onComplete: function()
    {
        if (!this.$allDone)
        {
            //@TODO
            this.$allDone = $('<div class="alldone" data-icon="done" />').appendTo(this.hud.$main);
        }

        this.base();

        window.location.reload();
    }
});

})(jQuery);