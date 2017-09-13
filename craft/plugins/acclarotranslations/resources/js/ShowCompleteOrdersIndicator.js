(function($) {

if (typeof Craft.AcclaroTranslations === 'undefined') {
    Craft.AcclaroTranslations = {};
}

Craft.AcclaroTranslations.ShowCompleteOrdersIndicator = {
    numberOfCompleteOrders: 0,
    init: function(numberOfCompleteOrders) {
        this.numberOfCompleteOrders = numberOfCompleteOrders;

        if (this.numberOfCompleteOrders > 0) {
            this.showIndicator();
        }
    },
    showIndicator: function() {
        var $link = $('<a>', {'class': 'acclarotranslations-complete-orders-indicator', 'href': Craft.getCpUrl('acclarotranslations')});
        var $stamp = $('<span>', {'data-icon': 'newstamp'});
        var $indicator = $('<span>', {'text': this.numberOfCompleteOrders});

        $indicator.appendTo($stamp);

        $stamp.appendTo($link);

        $link.appendTo('#nav-acclarotranslations');
    }
};

})(jQuery);