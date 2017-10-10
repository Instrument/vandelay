(function($) {

if (typeof Craft.AcclaroTranslations === 'undefined') {
    Craft.AcclaroTranslations = {};
}

Craft.AcclaroTranslations.GlobalSetEdit = {
    init: function(orders, globalSetId, drafts) {
        this.initAddToTranslationOrderButton(orders, globalSetId);
        this.initDraftsDropdown(drafts);
        this.initSaveDraftButton();
    },

    initDraftsDropdown: function(drafts) {
        var $container = $('<div>', {'class': 'select'}).css('margin-left', '0.75em');

        var $select = $('<select>');

        $select.appendTo($container);

        $select.on('change', function () {
            var url = $(this).val();

            if (url != '') {
                window.location.href = url;
            }
        });

        var $option = $('<option>', {'value': '', 'text': Craft.t('Current')});

        $option.appendTo($select);

        $.each(drafts, function(i, draft) {
            var $option = $('<option>', {'value': draft.url, 'text': draft.name});

            $option.appendTo($select);
        });

        $container.appendTo('#page-title');
    },

    initSaveDraftButton: function() {
        var $form = $('input[name=action][value="globals/saveContent"]').closest('form');

        var $buttons = $form.find('> .buttons');

        var $btngroup = $('<div>', {'class': 'btngroup'})
            .appendTo($buttons);

        var $submit = $buttons.find('.submit:first')
            .appendTo($btngroup);

        var $btn = $('<div>', {'class': 'btn submit menubtn'})
            .appendTo($btngroup);

        var $menu = $('<div>', {'class': 'menu'})
            .appendTo($btngroup);

        var $list = $('<ul>').appendTo($menu);

        var $item = $('<li>').appendTo($list);

        var $formsubmit = $('<a>', {
            'class': 'formsubmit',
            'text': Craft.t('Save as a draft'),
            'data-action': 'acclaroTranslations/saveGlobalSetDraft'
        }).appendTo($item);

        $btn.menubtn();

        $formsubmit.formsubmit();
    },

    initAddToTranslationOrderButton: function(orders, globalSetId) {
        var self = this;

        var sourceLanguage = $('#page-title select:first').val();

        var $btngroup = $('<div>', {'class': 'btngroup submit right acclarotranslations-dropdown'});

        $btngroup.appendTo('#page-header');

        var $btn = $('<a>', {
            'class': 'btn submit add icon menubtn',
            'href': '#',
            'text': Craft.t('Add to Translation Order')
        });

        $btn.appendTo($btngroup);

        $btn.on('click', function(e) {
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
                    'value': sourceLanguage
                });

                $hiddenSourceLanguage.appendTo($form);

                var $hiddenGlobalSetId = $('<input>', {
                    'type': 'hidden',
                    'name': 'elements[]',
                    'value': globalSetId
                });

                $hiddenGlobalSetId.appendTo($form);

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
            'href': Craft.getUrl('acclarotranslations/orders/new', {'elements[]': globalSetId, 'sourceLanguage': sourceLanguage}),
            'text': Craft.t('Create New Order')+'...'
        });

        $link.appendTo($item);

        $btn.menubtn();
    }
};

$(function() {
});

})(jQuery);