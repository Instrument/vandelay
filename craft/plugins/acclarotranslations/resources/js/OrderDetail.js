(function($) {

if (typeof Craft.AcclaroTranslations === 'undefined') {
    Craft.AcclaroTranslations = {};
}

Craft.AcclaroTranslations.OrderDetail = {
    goToStep: function(el) {
        var href = $(el).attr('href');
        var stepSelector = href.replace(/^.*(#step\d+)$/, '$1');
        var $btn = $('.acclarotranslations-order-step-btngroup .btn[href="'+href+'"]');
        var $step = $(stepSelector);
        var $steps = $('.acclarotranslations-order-step');

        $btn.closest('li').nextAll('li').find('.btn').removeClass('active').addClass('disabled').removeClass('prev');
        $btn.closest('li').prevAll('li').find('.btn').removeClass('active').removeClass('disabled').addClass('prev');
        $btn.removeClass('disabled').removeClass('prev');
        $steps.not($step).removeClass('active');
        $step.addClass('active');
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

    validateStepElement: function(el) {
        var $step = $(el).closest('.acclarotranslations-order-step');
        var stepId = $step.attr('id').replace(/^step(\d+)$/, '$1');

        return this.validateStep(stepId);
    },

    validateStep: function(stepId) {
        var valid = true;

        switch (stepId) {
            case '1':
                break;
            case '2':
                var $checkboxes = $('input[type=checkbox][name="targetLanguages[]"]');

                valid = $checkboxes.filter(':checked').length > 0;

                this.toggleInputState($checkboxes, valid, Craft.t('Please choose one or more target languages.'));

                break;
            case '3':
                var $requestedDueDate = $('#requestedDueDate-date');

                valid = $requestedDueDate.val() === '' || /^\d{1,2}\/\d{1,2}\/\d{4}$/.test($requestedDueDate.val());

                this.toggleInputState($requestedDueDate, valid, Craft.t('Please enter a valid date.'));

                break;
            case '4':
                var $translatorId = $('#translatorId');

                valid = !!$translatorId.val();

                this.toggleInputState($translatorId, valid, Craft.t('Please choose a translation service.'));

                break;
        }

        return valid;
    },

    init: function() {
        var self = this;

        $('input, select').on('keypress', function(e) {
            if (e.which === 13) {
                event.preventDefault();
            }
        });

        $('#title, #requestedDueDate-date, #comments').on('change', function() {
            var $el = $(this);
            var name = $el.attr('name');
            var val = $el.val();
            var $bound = $('[data-order-attribute="'+name+'"]');

            if (val !== '') {
                $bound.text(val);
            } else {
                $bound.html('<i>'+Craft.t('None')+'</i>');
            }
        });

        $(':checkbox[name="targetLanguages[]"], :checkbox[name="targetLanguages"]').on('change', function() {
            var $all = $(':checkbox[name="targetLanguages"]');
            var $checkboxes = $all.is(':checked') ? $(':checkbox[name="targetLanguages[]"]') : $(':checkbox[name="targetLanguages[]"]:checked');
            var targetLanguages = [];
            var targetLanguagesLabels = [];

            $checkboxes.each(function() {
                var $el = $(this);
                var val = $el.attr('value');
                var label = $.trim($el.next('label').text());
                targetLanguages.push(val);
                targetLanguagesLabels.push(label);
            });

            $('[data-order-attribute=targetLanguages]').text(targetLanguagesLabels.join(', '));
        });

        $('.acclarotranslations-order-step-next').on('click', function(e) {
            e.preventDefault();

            if (self.validateStepElement(this)) {
                self.goToStep(this);
            }
        });

        $('.acclarotranslations-order-step-prev').on('click', function(e) {
            e.preventDefault();

            self.goToStep(this);
        });

        $('.acclarotranslations-order-delete-entry').on('click', function(e) {
            var $button = $(this);
            var $table = $button.closest('table');
            var $row = $button.closest('tr');

            e.preventDefault();

            if (confirm(Craft.t('Are you sure you want to remove this entry from the order?'))) {
                $row.remove();

                if ($table.find('tbody tr').length === 0) {
                    $table.remove();
                }

                var entriesCount = $('input[name="elements[]"]').length;

                if (entriesCount === 0) {
                    $('.acclarotranslations-order-submit-btn').addClass('disabled').prop('disabled', true);
                }

                var wordCount = 0;

                $('[data-word-count]').each(function() {
                    wordCount += Number($(this).data('word-count'));
                });

                $('[data-order-attribute=entriesCount]').text(entriesCount);

                $('[data-order-attribute=wordCount]').text(wordCount);
            }
        });

        $('.acclarotranslations-order-submit-btn').on('click', function(e) {
            $(this).closest('.acclarotranslations-order-form')
                .find('.acclarotranslations-loader-msg')
                .removeClass('hidden');
        });

        $('.acclarotranslations-order-form').on('submit', function(e) {
            if (!self.validateStep('4')) {
                e.preventDefault();
            }

            var $form = $(this);

            $form.find('.acclarotranslations-loader').removeClass('hidden');
            $form.find('.btn[type=submit]').addClass('disabled').css('pointer-events', 'none');
        });

        $('#requestedDueDate-date').datepicker('option', 'minDate', +1)

        $(document).on('click', '.acclarotranslations-order-step-btngroup .btn.prev', function(e) {
            e.preventDefault();

            self.goToStep(this);
        });
    }
};

$(function() {
    Craft.AcclaroTranslations.OrderDetail.init();
});

})(jQuery);