/*
 * edit.js
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */


/** global: originalSum, accounting, what, Modernizr, currencySymbol */

var destAccounts = {};
var srcAccounts = {};
var categories = {};
var descriptions = {};

$(document).ready(function () {
    "use strict";
    $('.btn-do-split').click(cloneDivRow);
    $('.remove-current-split').click(removeDivRow);

    $.getJSON('json/expense-accounts').done(function (data) {
        destAccounts = data;
        $('input[name$="destination_account_name]"]').typeahead({source: destAccounts, autoSelect: false});
    });

    $.getJSON('json/revenue-accounts').done(function (data) {
        srcAccounts = data;
        $('input[name$="source_account_name]"]').typeahead({source: srcAccounts, autoSelect: false});
    });

    $.getJSON('json/categories').done(function (data) {
        categories = data;
        $('input[name$="category_name]"]').typeahead({source: categories, autoSelect: false});
    });

    $.getJSON('json/transaction-journals/' + what).done(function (data) {
        descriptions = data;
        $('input[name="journal_description"]').typeahead({source: descriptions, autoSelect: false});
        $('input[name$="transaction_description]"]').typeahead({source: descriptions, autoSelect: false});
    });

    $.getJSON('json/tags').done(function (data) {

        var opt = {
            typeahead: {
                source: data,
                afterSelect: function () {
                    this.$element.val("");
                },
                autoSelect: false
            }
        };
        $('input[name="tags"]').tagsinput(
            opt
        );
    });


    $('input[name$="][amount]"]').on('input', calculateSum);

    if (!Modernizr.inputtypes.date) {
        $('input[type="date"]').datepicker(
            {
                dateFormat: 'yy-mm-dd'
            }
        );
    }
});

/**
 * New and cool
 * @param e
 * @returns {boolean}
 */
function removeDivRow(e) {
    "use strict";
    var rows = $('div.split_row');
    if (rows.length === 1) {
        return false;
    }
    var row = $(e.target);
    var index = row.data('split');
    $('div.split_row[data-split="' + index + '"]').remove();


    resetDivSplits();

    return false;

}

/**
 * New and cool
 * @returns {boolean}
 */
function cloneDivRow() {
    "use strict";
    var source = $('div.split_row').last().clone();
    var count = $('div.split_row').length + 1;
    source.removeClass('initial-row');
    source.find('.count').text('#' + count);

    source.find('input[name$="][amount]"]').val("").on('input', calculateSum);
    source.find('input[name$="][foreign_amount]"]').val("").on('input', calculateSum);
    if (destAccounts.length > 0) {
        source.find('input[name$="destination_account_name]"]').typeahead({source: destAccounts, autoSelect: false});
    }

    if (srcAccounts.length > 0) {
        source.find('input[name$="source_account_name]"]').typeahead({source: srcAccounts, autoSelect: false});
    }
    if (categories.length > 0) {
        source.find('input[name$="category_name]"]').typeahead({source: categories, autoSelect: false});
    }
    if (descriptions.length > 0) {
        source.find('input[name$="transaction_description]"]').typeahead({source: descriptions, autoSelect: false});
    }

    $('div.split_row_holder').append(source);

    // remove original click things, add them again:
    $('.remove-current-split').unbind('click').click(removeDivRow);

    calculateSum();
    resetDivSplits();

    return false;
}

/**
 * New and hip
 */
function resetDivSplits() {
    "use strict";
    // loop rows, reset numbers:

    // update the row split number:
    $.each($('div.split_row'), function (i, v) {
        var row = $(v);
        row.attr('data-split', i);

        // add or remove class with bg thing
        if (i % 2 === 0) {
            row.removeClass('bg-gray-light');
        }
        if (i % 2 === 1) {
            row.addClass('bg-gray-light');
        }

    });

    // loop each remove button, update the index
    $.each($('.remove-current-split'), function (i, v) {
        var button = $(v);
        button.attr('data-split', i);
        button.find('i').attr('data-split', i);

    });

    // loop each indicator (#) and update it:
    $.each($('td.count'), function (i, v) {
        var cell = $(v);
        var index = i + 1;
        cell.text('#' + index);
    });

    // loop each possible field.

    // ends with ][description]
    $.each($('input[name$="][transaction_description]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][transaction_description]');
    });
    // ends with ][destination_account_name]
    $.each($('input[name$="][destination_account_name]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][destination_account_name]');
    });
    // ends with ][source_account_name]
    $.each($('input[name$="][source_account_name]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][source_account_name]');
    });
    // ends with ][amount]
    $.each($('input[name$="][amount]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][amount]');
    });

    // ends with ][foreign_amount]
    $.each($('input[name$="][foreign_amount]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][foreign_amount]');
    });

    // ends with ][transaction_currency_id]
    $.each($('input[name$="][transaction_currency_id]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][transaction_currency_id]');
    });

    // ends with ][foreign_currency_id]
    $.each($('input[name$="][foreign_currency_id]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][foreign_currency_id]');
    });

    // ends with ][budget_id]
    $.each($('select[name$="][budget_id]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][budget_id]');
    });

    // ends with ][category]
    $.each($('input[name$="][category_name]"]'), function (i, v) {
        var input = $(v);
        input.attr('name', 'transactions[' + i + '][category_name]');
    });
}


function calculateSum() {
    "use strict";
    var left = originalSum * -1;
    var sum = 0;
    var set = $('input[name$="][amount]"]');
    for (var i = 0; i < set.length; i++) {
        var current = $(set[i]);
        sum += (current.val() === "" ? 0 : parseFloat(current.val()));
        left += (current.val() === "" ? 0 : parseFloat(current.val()));
    }
    sum = Math.round(sum * 100) / 100;
    left = Math.round(left * 100) / 100;


    $('.amount-warning').remove();
    if (sum !== originalSum) {
        var holder = $('#journal_amount_holder');
        var par = holder.find('p.form-control-static');
        $('<span>').text(' (' + accounting.formatMoney(sum, currencySymbol) + ')').addClass('text-danger amount-warning').appendTo(par);
        // also add what's left to divide (or vice versa)
        $('<span>').text(' (' + accounting.formatMoney(left, currencySymbol) + ')').addClass('text-danger amount-warning').appendTo(par);
    }

}