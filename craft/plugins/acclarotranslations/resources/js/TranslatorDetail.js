(function($) {

if (typeof Craft.AcclaroTranslations === 'undefined') {
    Craft.AcclaroTranslations = {};
}

Craft.AcclaroTranslations.TranslatorDetail = {
    updateService: function() {
        var service = $('#service').val();

        if (service === '') {
            $('.acclarotranslations-translator-settings').hide();
            $('.acclarotranslations-translator-settings-header').hide();
        } else {
            var $settings = $('#settings-'+service);

            $settings.show();
            $('.acclarotranslations-translator-settings').not($settings).hide();
            $('.acclarotranslations-translator-settings-header').show();
        }
    },

    validate: function() {
        var $service = $('#service');
        var service = $service.val();
        var serviceValid = !!service;
        var $languages = $(':checkbox[name="languages[]"]');
        var languagesValid = $languages.filter(':checked').length > 0;
        var valid = serviceValid && languagesValid;

        this.toggleInputState($service, serviceValid, Craft.t('Please choose a translation service.'));
        this.toggleInputState($languages, languagesValid, Craft.t('Please choose one or more languages.'));

        switch (service) {
            case 'acclaro':
                var $apiToken = $('input[name="settings[acclaro][apiToken]"]');
                var apiTokenValid = $apiToken.val() !== '';
                this.toggleInputState($apiToken, apiTokenValid, Craft.t('Please enter your Acclaro API token.'));
                valid = valid && apiTokenValid
                break;
        }

        return valid;
    },

    toggleInputState: function(el, valid, message) {
        var $el = $(el);
        var $input = $el.closest('.input');
        var $errors = $input.find('ul.errors');

        $el.toggleClass('error', !valid);
        $input.toggleClass('errors', !valid);

        if (valid) {
            $errors.remove();
        } else if ($errors.length === 0) {
            $('<ul>', {'class': 'errors'})
                .appendTo($input)
                .append($('<li>', {'text': message}));
        }
    },

    serializeSettings: function(service) {
        var data = {};

        var $fields = $(':input[name^="settings['+service+']"]').not(':checkbox:not(:checked), :radio:not(:checked)');

        $fields.each(function() {
            var $el = $(this);
            var regExp = new RegExp('^settings\\['+service+'\\]\\[(.*?)\\]$');
            var name = $el.attr('name').replace(regExp, '$1');
            var value = $el.val();
            var multiple = /\]\[$/.test(name);

            if (multiple) {
                name = name.substr(0, name.length - 2);

                if (typeof data[name] === 'undefined') {
                    data[name] = [];
                }

                if ($.isArray(value)) {
                    $.each(value, function(i, v) {
                        data[name].push(v);
                    });
                } else {
                    data[name].push(value);
                }
            } else {
                data[name] = value;
            }
        });

        return data;
    },

    authenticateTranslationService: function() {
        var service = $('#service').val();
        var settings = this.serializeSettings(service);

        $.post(
            location.href,
            {
                CRAFT_CSRF_TOKEN: Craft.csrfTokenValue,
                action: 'acclaroTranslations/authenticateTranslationService',
                service: service,
                settings: settings
            },
            function(data) {
                if (data.success) {
                    $('#status').val('active');
                    alert(Craft.t('You are now authenticated!'));
                } else {
                    $('#status').val('inactive');
                    alert(Craft.t('Invalid API token.'));
                }
            },
            'json'
        );
    },

    init: function() {
        var self = this;

        $('#service').on('change', $.proxy(this.updateService, this));

        this.updateService();

        $('.acclarotranslations-authenticate-translation-service').on('click', function(e) {
            e.preventDefault();

            self.authenticateTranslationService();
        });

        $('.acclarotranslations-translator-form').on('submit', function(e) {
            if (!self.validate()) {
                e.preventDefault();
            }
        });
    }
};

$(function() {
    Craft.AcclaroTranslations.TranslatorDetail.init();
});

})(jQuery);