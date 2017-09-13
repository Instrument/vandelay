(function($) {

function unique(array) {
    return $.grep(array, function(el, index) {
        return index === $.inArray(el, array);
    });
}

if (typeof Craft.AcclaroTranslations === 'undefined') {
    Craft.AcclaroTranslations = {};
}

Craft.AcclaroTranslations.AddEntriesToTranslationOrder = {
    entries: [],

    $btn: null,
    $createNewLink: null,

    isEditEntryScreen: function() {
        return $('form#container input[type=hidden][name=action][value="entries/saveEntry"]').length > 0;
    },

    getEditEntryId: function() {
        return $('form#container input[type=hidden][name=entryId]').val();
    },

    updateSelectedEntries: function() {
        var entries = [];

        $('.elementindex table.data tbody tr.sel[data-id]').each(function() {
            entries.push($(this).data('id'));
        });

        this.entries = unique(entries);

        this.$btn.toggleClass('disabled', this.entries.length === 0);

        this.updateCreateNewLink();
    },

    updateCreateNewLink: function() {
        var href = this.$createNewLink.attr('href').split('?')[0];

        href += '?sourceLanguage='+this.getSourceLanguage();

        for (var i = 0; i < this.entries.length; i++) {
            href += '&elements[]=' + this.entries[i];
        }

        this.$createNewLink.attr('href', href);
    },

    getSourceLanguage: function() {
        if (this.isEditEntryScreen()) {
            return $('[name=locale]').val();
        }

        var localeMenu = $('.localemenubtn').data('menubtn').menu;

        // Figure out the initial locale
        var $option = localeMenu.$options.filter('.sel:first');

        if ($option.length === 0) {
            $option = localeMenu.$options.first();
        }

        return $option.data('locale');
    },

    init: function(orders) {
        if (this.isEditEntryScreen() && !this.getEditEntryId()) {
            return;
        }

        var self = this;

        var $btngroup = $('<div>', {'class': 'btngroup submit acclarotranslations-dropdown'});

        if (this.isEditEntryScreen()) {
            $btngroup.prependTo('#extra-headers');
        } else {
            $btngroup.prependTo('#extra-headers .buttons');
        }

        this.$btn = $('<a>', {
            'class': 'btn submit add icon menubtn',
            'href': '#',
            'text': Craft.t('Add to Translation Order')
        });

        if (!this.isEditEntryScreen()) {
            this.$btn.addClass('disabled');
        }

        this.$btn.appendTo($btngroup);

        this.$btn.on('click', function(e) {
            e.preventDefault();
        });

        var $menu = $('<div>', {'class': 'menu'});

        $menu.appendTo($btngroup);

        var $dropdown = $('<ul>', {'class': 'padded'});

        $dropdown.appendTo($menu);

        for (var i = 0; i < orders.length; i++) {
            var order = orders[i];

            var $item = $('<li>');

            $item.appendTo($dropdown);

            var $link = $('<a>', {
                'href': '#',
                'text': order.title
            });

            $link.appendTo($item);

            $link.data('order', order);

            $link.on('click', function(e) {
                e.preventDefault();

                var order = $(this).data('order');

                var $form = $('<form>', {
                    'method': 'POST'
                });

                $form.hide();

                $form.appendTo('body');

                $form.append(Craft.getCsrfInput());

                var $hiddenAction = $('<input>', {
                    'type': 'hidden',
                    'name': 'action',
                    'value': 'acclaroTranslations/addElementsToOrder'
                });

                $hiddenAction.appendTo($form);

                var $hiddenOrderId = $('<input>', {
                    'type': 'hidden',
                    'name': 'id',
                    'value': order.id
                });

                $hiddenOrderId.appendTo($form);

                var $hiddenSourceLanguage = $('<input>', {
                    'type': 'hidden',
                    'name': 'sourceLanguage',
                    'value': self.getSourceLanguage()
                });

                $hiddenSourceLanguage.appendTo($form);

                for (var j = 0; j < self.entries.length; j++) {
                    $('<input>', {
                        'type': 'hidden',
                        'name': 'elements[]',
                        'value': self.entries[j]
                    }).appendTo($form);
                }

                var $submit = $('<input>', {
                    'type': 'submit'
                });

                $submit.appendTo($form);

                $form.submit();
            });
        }

        var $item = $('<li>');

        $item.appendTo($dropdown);

        var $link = $('<a>', {
            'class': 'acclarotranslations-dropdown-create-new',
            'style': 'font-style: italic; font-weight: bold;',
            'href': Craft.getUrl('acclarotranslations/orders/new'),
            'text': Craft.t('Create New Order')+'...'
        });

        $link.appendTo($item);

        this.$createNewLink = $link;

        this.$btn.menubtn();

        var self = this;

        $(document).on('click', '.elementindex .checkbox, .elementindex .selectallcontainer .btn', function() {
            setTimeout($.proxy(self.updateSelectedEntries, self), 100);
        });

        // on edit entry screen
        if (this.isEditEntryScreen()) {
            this.entries.push(this.getEditEntryId());
            this.updateCreateNewLink();
        }
    }
};

$(function() {
});

})(jQuery);