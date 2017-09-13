(function($) {

if (typeof Craft.AcclaroTranslations === 'undefined') {
    Craft.AcclaroTranslations = {};
}

/**
 * Order index class
 */
Craft.AcclaroTranslations.OrderIndex = Craft.BaseElementIndex.extend(
{
    afterInit: function()
    {
        $(document).on("click", ".acclarotranslations-delete-order", function() {
            var $button = $(this);

            if (confirm(Craft.t('Are you sure you want to delete this order?'))) {
                var data = {
                    action: 'acclaroTranslations/deleteOrder',
                    orderId: $button.data('order-id')
                };

                data[Craft.csrfTokenName] = Craft.csrfTokenValue;

                $.post(
                    location.href,
                    data,
                    function (data) {
                        if (!data.success) {
                            alert(data.error);
                        } else {
                            $button.closest('tr').remove();
                        }
                    },
                    'json'
                );
            }
        });

        this.base();
    }
});

Craft.registerElementIndexClass('AcclaroTranslations_Order', Craft.AcclaroTranslations.OrderIndex);

})(jQuery);