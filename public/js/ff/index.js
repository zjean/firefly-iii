/*
 * index.js
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

/** global: accountFrontpageUri, today, piggyInfoUri, token, billCount, accountExpenseUri, accountRevenueUri */

$(function () {
    "use strict";
    // do chart JS stuff.
    drawChart();

});

function drawChart() {
    "use strict";
    lineChart(accountFrontpageUri, 'accounts-chart');

    if (billCount > 0) {
        multiCurrencyPieChart('chart/bill/frontpage', 'bills-chart');
    }
    stackedColumnChart('chart/budget/frontpage', 'budgets-chart');
    columnChart('chart/category/frontpage', 'categories-chart');
    columnChart(accountExpenseUri, 'expense-accounts-chart');
    columnChart(accountRevenueUri, 'revenue-accounts-chart');

    // get balance box:
    getBalanceBox();
    getBillsBox();
    getAvailableBox();
    getNetWorthBox();
    getPiggyBanks();

    //getBoxAmounts();
}

/**
 *
 */
function getPiggyBanks() {
    $.getJSON(piggyInfoUri).done(function (data) {
        if (data.html.length > 0) {
            $('#piggy_bank_overview').html(data.html);
        }
    });
}

function getNetWorthBox() {
    // box-net-worth
    $.getJSON('json/box/net-worth').done(function (data) {
        $('#box-net-worth').html(data.net_worths.join(', '));
    });
}

/**
 *
 */
function getAvailableBox() {
    // box-left-to-spend
    // box-left-per-day
    $.getJSON('json/box/available').done(function (data) {
        $('#box-left-to-spend').html(data.left);
        $('#box-left-per-day').html(data.perDay);
        $('#box-left-to-spend-text').text(data.text);
        if(data.overspent === true) {
            $('#box-left-to-spend-box').removeClass('bg-green-gradient').addClass('bg-red-gradient');
        }
    });
}

/**
 *
 */
function getBillsBox() {
    // box-bills-unpaid
    // box-bills-paid
    $.getJSON('json/box/bills').done(function (data) {
        $('#box-bills-paid').html(data.paid);
        $('#box-bills-unpaid').html(data.unpaid);
    });
}

/**
 *
 */
function getBalanceBox() {
    // box-balance-sums
    // box-balance-list
    $.getJSON('json/box/balance').done(function (data) {
        if (data.size === 1) {
            // show balance in "sums", show single entry in list.
            for (x in data.sums) {
                $('#box-balance-sums').html(data.sums[x]);
                $('#box-balance-list').html(data.incomes[x] + ' / ' + data.expenses[x]);
            }
            return;
        }
        // do not use "sums", only use list.
        $('#box-balance-progress').remove();
        var expense, string, sum, income, current;
        var count = 0;
        for (x in data.sums) {
            if (count > 1) {
                return;
            }
            current = $('#box-balance-list').html();
            sum = data.sums[x];
            expense = data.expenses[x];
            income = data.incomes[x];
            string = income + ' / ' + expense + ': ' + sum;

            $('#box-balance-list').html(current + '<span title="' + string + '">' + string + '</span>' + '<br>');
            count++;
        }
    });
}