$ = jQuery;

jQuery(document).ready(function($) {
    const popup = $('#form-data-popup');
    const popupContent = $('#form-data-content');
    const closeBtn = $('.form-data-popup-close');

    $('.view-form-data').on('click', function(event) {
        event.preventDefault();
        const formData = JSON.parse($(this).attr('data-form_data'));
        popupContent.empty();

        $.each(formData, function(key, value) {
            const displayValue = Array.isArray(value) ? value.join(', ') : value;
            popupContent.append(`<p><strong>${key}:</strong> ${displayValue}</p>`);
        });

        popup.show();
    });

    closeBtn.on('click', function() {
        popup.hide();
    });

    $(window).on('click', function(event) {
        if ($(event.target).is(popup)) {
            popup.hide();
        }
    });
});