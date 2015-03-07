$(function () {

    $('.currencySelect').click(currencySelect);

    ranges = {};
    ranges[currentMonthName] = [moment().startOf('month'), moment().endOf('month')];
    ranges[previousMonthName] = [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')];
    ranges[nextMonthName] = [moment().add(1, 'month').startOf('month'), moment().add(1, 'month').endOf('month')];
    ranges['Everything'] = [firstDate, moment()];

    $('#daterange').daterangepicker(
        {
            //View::share('currentMonthName', $current);
            //View::share('previousMonthName', $prev);
            //View::share('nextMonthName', $next);


            ranges: ranges
            ,
            opens: 'left',

            format: 'DD-MM-YYYY',
            startDate: start,
            endDate: end
        },
        function (start, end, label) {

            // send post.
            $.post(dateRangeURL, {
                start: start.format('YYYY-MM-DD'),
                end: end.format('YYYY-MM-DD'),
                label: label,
                _token: token
            }).success(function () {
                window.location.reload(true);
            }).fail(function () {
                alert('Could not change date range');

            });

            //alert('A date range was chosen: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
        }
    );

});

function currencySelect(e) {
    var target = $(e.target);
    var symbol = target.data('symbol');
    var code = target.data('code');
    var id = target.data('id');
    var fieldType = target.data('field');
    var menu = $('.' + fieldType + 'CurrencyDropdown');

    var symbolHolder = $('#' + fieldType + 'CurrentSymbol');
    symbolHolder.text(symbol);
    $('input[name="' + fieldType + '_currency_id"]').val(id);

    // close dropdown (hack hack)
    menu.click();


    return false;
}
