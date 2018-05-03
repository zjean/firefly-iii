/*
 * index.js
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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


$(function () {
    "use strict";
    //var status = $('#status-box');
    // set HTML to "migrating...":
    startMigration();
});

function startMigration() {
    $('#status-box').html('<i class="fa fa-spin fa-spinner"></i> Setting up DB...');
    $.post(migrateUri, {_token: token}).done(function (data) {
        if(data.error === false) {
            startPassport();
        } else {
            displaySoftFail(data.message);
        }

    }).fail(function () {
        $('#status-box').html('<i class="fa fa-warning"></i> Migration failed! See log files :(');
    });
}

/**
 *
 */
function startPassport() {
    $('#status-box').html('<i class="fa fa-spin fa-spinner"></i> Setting up OAuth2...');
    $.post(keysUri, {_token: token}).done(function (data) {
        if(data.error === false) {
            startUpgrade();
        } else {
            displaySoftFail(data.message);
        }

    }).fail(function () {
        $('#status-box').html('<i class="fa fa-warning"></i> OAuth2 failed! See log files :(');
    });
}

/**
 *
 */
function startUpgrade() {
    $('#status-box').html('<i class="fa fa-spin fa-spinner"></i> Upgrading database...');
    $.post(upgradeUri, {_token: token}).done(function (data) {
        if(data.error === false) {
            startVerify();
        } else {
            displaySoftFail(data.message);
        }
    }).fail(function () {
        $('#status-box').html('<i class="fa fa-warning"></i> Upgrade failed! See log files :(');
    });
}

/**
 *
 */
function startVerify() {
    $('#status-box').html('<i class="fa fa-spin fa-spinner"></i> Verify database integrity...');
    $.post(verifyUri, {_token: token}).done(function (data) {
        if(data.error === false) {
            completeDone();
        } else {
            displaySoftFail(data.message);
        }
    }).fail(function () {
        $('#status-box').html('<i class="fa fa-warning"></i> Verification failed! See log files :(');
    });
}

/**
 *
 */
function completeDone() {
    $('#status-box').html('<i class="fa fa-thumbs-up"></i> Installation + upgrade complete! Wait to be redirected...');
    setTimeout(function () {
        window.location = homeUri;
    }, 3000);
}

function displaySoftFail(message) {
    $('#status-box').html('<i class="fa fa-warning"></i> ' + message + '<br /><br />Please read the ' +
                          '<a href="http://firefly-iii.readthedocs.io/en/latest/support/faq.html#i-get-an-error-during-the-automatic-installation-and-upgrade">' +
                          'official documentation</a> about this, and upgrade by hand.');
}