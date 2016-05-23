(function ($) {
    "use strict";

    $('.datetime-inputs .time').timepicker({
        'showDuration': true,
        'timeFormat': 'g:ia'
    });

    $('.datetime-inputs .date').datepicker({
        'format': 'd/m/yyyy',
        'autoclose': true
    });

    var datepair = new Datepair($('.datetime-inputs')[0]);
})(jQuery);
