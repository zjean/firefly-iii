<?php
/**
 * web.php
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

declare(strict_types=1);


/**
 * These routes only work when the user is NOT logged in.
 */
Route::group(
    ['middleware' => 'user-not-logged-in', 'namespace' => 'FireflyIII\Http\Controllers'], function () {

    // Authentication Routes...
    Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
    Route::post('login', 'Auth\LoginController@login');

    // Registration Routes...
    Route::get('register', ['uses' => 'Auth\RegisterController@showRegistrationForm', 'as' => 'register']);
    Route::post('register', 'Auth\RegisterController@register');

    // Password Reset Routes...
    Route::get('password/reset/{token}', ['uses' => 'Auth\ResetPasswordController@showResetForm', 'as' => 'password.reset']);
    Route::post('password/email', ['uses' => 'Auth\ForgotPasswordController@sendResetLinkEmail', 'as' => 'password.email']);
    Route::post('password/reset', 'Auth\ResetPasswordController@reset');
    Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm');

    // Change email routes:
    Route::get('profile/confirm-email-change/{token}', ['uses' => 'ProfileController@confirmEmailChange', 'as' => 'profile.confirm-email-change']);
    Route::get('profile/undo-email-change/{token}/{oldAddressHash}', ['uses' => 'ProfileController@undoEmailChange', 'as' => 'profile.undo-email-change']);

}
);

/**
 * For some other routes, it is only relevant that the user is authenticated.
 */
Route::group(
    ['middleware' => 'user-simple-auth', 'namespace' => 'FireflyIII\Http\Controllers'], function () {
    Route::get('error', ['uses' => 'HomeController@displayError', 'as' => 'error']);
    Route::any('logout', ['uses' => 'Auth\LoginController@logout', 'as' => 'logout']);
    Route::get('flush', ['uses' => 'HomeController@flush', 'as' => 'flush']);
    Route::get('routes', ['uses' => 'HomeController@routes', 'as' => 'routes']);
    Route::get('debug', 'DebugController@index')->name('debug');
}
);

/**
 * For the two factor routes, the user must be logged in, but NOT 2FA. Account confirmation does not matter here.
 *
 */
Route::group(
    ['middleware' => 'user-logged-in-no-2fa', 'prefix' => 'two-factor', 'as' => 'two-factor.', 'namespace' => 'FireflyIII\Http\Controllers\Auth'], function () {
    Route::get('', ['uses' => 'TwoFactorController@index', 'as' => 'index']);
    Route::get('lost', ['uses' => 'TwoFactorController@lostTwoFactor', 'as' => 'lost']);
    Route::post('', ['uses' => 'TwoFactorController@postIndex', 'as' => 'post']);

}
);

/**
 * For all other routes, the user must be fully authenticated and have an activated account.
 */

/**
 * Home Controller
 */
Route::group(
    ['middleware' => ['user-full-auth'], 'namespace' => 'FireflyIII\Http\Controllers'], function () {
    Route::get('/', ['uses' => 'HomeController@index', 'as' => 'index']);
    Route::get('/flash', ['uses' => 'HomeController@testFlash', 'as' => 'test-flash']);
    Route::get('/home', ['uses' => 'HomeController@index', 'as' => 'home']);
    Route::post('/daterange', ['uses' => 'HomeController@dateRange', 'as' => 'daterange']);
}
);


/**
 * Account Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'accounts', 'as' => 'accounts.'], function () {
    Route::get('{what}', ['uses' => 'AccountController@index', 'as' => 'index'])->where('what', 'revenue|asset|expense');
    Route::get('create/{what}', ['uses' => 'AccountController@create', 'as' => 'create'])->where('what', 'revenue|asset|expense');
    Route::get('edit/{account}', ['uses' => 'AccountController@edit', 'as' => 'edit']);
    Route::get('delete/{account}', ['uses' => 'AccountController@delete', 'as' => 'delete']);
    Route::get('show/{account}/{start_date?}/{end_date?}', ['uses' => 'AccountController@show', 'as' => 'show']);

    // reconcile routes:
    Route::get('reconcile/{account}/index/{start_date?}/{end_date?}', ['uses' => 'Account\ReconcileController@reconcile', 'as' => 'reconcile']);
    Route::get(
        'reconcile/{account}/transactions/{start_date?}/{end_date?}', ['uses' => 'Account\ReconcileController@transactions', 'as' => 'reconcile.transactions']
    );
    Route::get('reconcile/{account}/overview/{start_date?}/{end_date?}', ['uses' => 'Account\ReconcileController@overview', 'as' => 'reconcile.overview']);
    Route::post('reconcile/{account}/submit/{start_date?}/{end_date?}', ['uses' => 'Account\ReconcileController@submit', 'as' => 'reconcile.submit']);

    // show reconciliation
    Route::get('reconcile/show/{tj}', ['uses' => 'Account\ReconcileController@show', 'as' => 'reconcile.show']);
    Route::get('reconcile/edit/{tj}', ['uses' => 'Account\ReconcileController@edit', 'as' => 'reconcile.edit']);
    Route::post('reconcile/update/{tj}', ['uses' => 'Account\ReconcileController@update', 'as' => 'reconcile.update']);

    Route::post('store', ['uses' => 'AccountController@store', 'as' => 'store']);
    Route::post('update/{account}', ['uses' => 'AccountController@update', 'as' => 'update']);
    Route::post('destroy/{account}', ['uses' => 'AccountController@destroy', 'as' => 'destroy']);

}
);

/**
 * Attachment Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'attachments', 'as' => 'attachments.'], function () {
    Route::get('edit/{attachment}', ['uses' => 'AttachmentController@edit', 'as' => 'edit']);
    Route::get('delete/{attachment}', ['uses' => 'AttachmentController@delete', 'as' => 'delete']);
    Route::get('download/{attachment}', ['uses' => 'AttachmentController@download', 'as' => 'download']);
    Route::get('view/{attachment}', ['uses' => 'AttachmentController@view', 'as' => 'view']);

    Route::post('update/{attachment}', ['uses' => 'AttachmentController@update', 'as' => 'update']);
    Route::post('destroy/{attachment}', ['uses' => 'AttachmentController@destroy', 'as' => 'destroy']);

}
);

/**
 * Bills Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'bills', 'as' => 'bills.'], function () {
    Route::get('', ['uses' => 'BillController@index', 'as' => 'index']);
    Route::get('rescan/{bill}', ['uses' => 'BillController@rescan', 'as' => 'rescan']);
    Route::get('create', ['uses' => 'BillController@create', 'as' => 'create']);
    Route::get('edit/{bill}', ['uses' => 'BillController@edit', 'as' => 'edit']);
    Route::get('delete/{bill}', ['uses' => 'BillController@delete', 'as' => 'delete']);
    Route::get('show/{bill}', ['uses' => 'BillController@show', 'as' => 'show']);

    Route::post('store', ['uses' => 'BillController@store', 'as' => 'store']);
    Route::post('update/{bill}', ['uses' => 'BillController@update', 'as' => 'update']);
    Route::post('destroy/{bill}', ['uses' => 'BillController@destroy', 'as' => 'destroy']);
}
);


/**
 * Budget Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'budgets', 'as' => 'budgets.'], function () {

    Route::get('income/{start_date}/{end_date}', ['uses' => 'BudgetController@updateIncome', 'as' => 'income']);
    Route::get('info/{start_date}/{end_date}', ['uses' => 'BudgetController@infoIncome', 'as' => 'income.info']);
    Route::get('create', ['uses' => 'BudgetController@create', 'as' => 'create']);
    Route::get('edit/{budget}', ['uses' => 'BudgetController@edit', 'as' => 'edit']);
    Route::get('delete/{budget}', ['uses' => 'BudgetController@delete', 'as' => 'delete']);
    Route::get('show/{budget}', ['uses' => 'BudgetController@show', 'as' => 'show']);
    Route::get('show/{budget}/{budgetLimit}', ['uses' => 'BudgetController@showByBudgetLimit', 'as' => 'show.limit']);
    Route::get('list/no-budget/{moment?}', ['uses' => 'BudgetController@noBudget', 'as' => 'no-budget']);
    Route::get('{moment?}', ['uses' => 'BudgetController@index', 'as' => 'index']);


    Route::post('income', ['uses' => 'BudgetController@postUpdateIncome', 'as' => 'income.post']);
    Route::post('store', ['uses' => 'BudgetController@store', 'as' => 'store']);
    Route::post('update/{budget}', ['uses' => 'BudgetController@update', 'as' => 'update']);
    Route::post('destroy/{budget}', ['uses' => 'BudgetController@destroy', 'as' => 'destroy']);
    Route::post('amount/{budget}', ['uses' => 'BudgetController@amount', 'as' => 'amount']);
}
);

/**
 * Category Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'categories', 'as' => 'categories.'], function () {
    Route::get('', ['uses' => 'CategoryController@index', 'as' => 'index']);
    Route::get('create', ['uses' => 'CategoryController@create', 'as' => 'create']);
    Route::get('edit/{category}', ['uses' => 'CategoryController@edit', 'as' => 'edit']);
    Route::get('delete/{category}', ['uses' => 'CategoryController@delete', 'as' => 'delete']);

    Route::get('show/{category}/{moment?}', ['uses' => 'CategoryController@show', 'as' => 'show']);
    Route::get('list/no-category/{moment?}', ['uses' => 'CategoryController@noCategory', 'as' => 'no-category']);

    Route::post('store', ['uses' => 'CategoryController@store', 'as' => 'store']);
    Route::post('update/{category}', ['uses' => 'CategoryController@update', 'as' => 'update']);
    Route::post('destroy/{category}', ['uses' => 'CategoryController@destroy', 'as' => 'destroy']);
}
);


/**
 * Currency Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'currencies', 'as' => 'currencies.'], function () {
    Route::get('', ['uses' => 'CurrencyController@index', 'as' => 'index']);
    Route::get('create', ['uses' => 'CurrencyController@create', 'as' => 'create']);
    Route::get('edit/{currency}', ['uses' => 'CurrencyController@edit', 'as' => 'edit']);
    Route::get('delete/{currency}', ['uses' => 'CurrencyController@delete', 'as' => 'delete']);
    Route::get('default/{currency}', ['uses' => 'CurrencyController@defaultCurrency', 'as' => 'default']);

    Route::post('store', ['uses' => 'CurrencyController@store', 'as' => 'store']);
    Route::post('update/{currency}', ['uses' => 'CurrencyController@update', 'as' => 'update']);
    Route::post('destroy/{currency}', ['uses' => 'CurrencyController@destroy', 'as' => 'destroy']);

}
);

/**
 * Export Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'export', 'as' => 'export.'], function () {
    Route::get('', ['uses' => 'ExportController@index', 'as' => 'index']);
    Route::get('status/{exportJob}', ['uses' => 'ExportController@getStatus', 'as' => 'status']);
    Route::get('download/{exportJob}', ['uses' => 'ExportController@download', 'as' => 'download']);

    Route::post('submit', ['uses' => 'ExportController@postIndex', 'as' => 'submit']);

}
);

/**
 * Chart\Account Controller (default report)
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/account', 'as' => 'chart.account.'], function () {
    Route::get('frontpage', ['uses' => 'AccountController@frontpage', 'as' => 'frontpage']);
    Route::get('expense', ['uses' => 'AccountController@expenseAccounts', 'as' => 'expense']);
    Route::get('revenue', ['uses' => 'AccountController@revenueAccounts', 'as' => 'revenue']);
    Route::get('report/{accountList}/{start_date}/{end_date}', ['uses' => 'AccountController@report', 'as' => 'report']);
    Route::get('period/{account}/{start_date}/{end_date}', ['uses' => 'AccountController@period', 'as' => 'period']);

    Route::get('income-category/{account}/all/all', ['uses' => 'AccountController@incomeCategoryAll', 'as' => 'income-category-all']);
    Route::get('expense-category/{account}/all/all', ['uses' => 'AccountController@expenseCategoryAll', 'as' => 'expense-category-all']);
    Route::get('expense-budget/{account}/all/all', ['uses' => 'AccountController@expenseBudgetAll', 'as' => 'expense-budget-all']);

    Route::get('income-category/{account}/{start_date}/{end_date}', ['uses' => 'AccountController@incomeCategory', 'as' => 'income-category']);
    Route::get('expense-category/{account}/{start_date}/{end_date}', ['uses' => 'AccountController@expenseCategory', 'as' => 'expense-category']);
    Route::get('expense-budget/{account}/{start_date}/{end_date}', ['uses' => 'AccountController@expenseBudget', 'as' => 'expense-budget']);
}
);


/**
 * Chart\Bill Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/bill', 'as' => 'chart.bill.'], function () {
    Route::get('frontpage', ['uses' => 'BillController@frontpage', 'as' => 'frontpage']);
    Route::get('single/{bill}', ['uses' => 'BillController@single', 'as' => 'single']);

}
);

/**
 * Chart\Budget Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/budget', 'as' => 'chart.budget.'], function () {

    Route::get('frontpage', ['uses' => 'BudgetController@frontpage', 'as' => 'frontpage']);
    Route::get('period/0/{accountList}/{start_date}/{end_date}', ['uses' => 'BudgetController@periodNoBudget', 'as' => 'period.no-budget']);
    Route::get('period/{budget}/{accountList}/{start_date}/{end_date}', ['uses' => 'BudgetController@period', 'as' => 'period']);
    Route::get('budget/{budget}/{budgetLimit}', ['uses' => 'BudgetController@budgetLimit', 'as' => 'budget-limit']);
    Route::get('budget/{budget}', ['uses' => 'BudgetController@budget', 'as' => 'budget']);

    // these charts are used in budget/show:
    Route::get('expense-category/{budget}/{budgetLimit?}', ['uses' => 'BudgetController@expenseCategory', 'as' => 'expense-category']);
    Route::get('expense-asset/{budget}/{budgetLimit?}', ['uses' => 'BudgetController@expenseAsset', 'as' => 'expense-asset']);
    Route::get('expense-expense/{budget}/{budgetLimit?}', ['uses' => 'BudgetController@expenseExpense', 'as' => 'expense-expense']);

    // these charts are used in reports (category reports):
    Route::get(
        'budget/expense/{accountList}/{budgetList}/{start_date}/{end_date}/{others}',
        ['uses' => 'BudgetReportController@budgetExpense', 'as' => 'budget-expense']
    );
    Route::get(
        'account/expense/{accountList}/{budgetList}/{start_date}/{end_date}/{others}',
        ['uses' => 'BudgetReportController@accountExpense', 'as' => 'account-expense']
    );

    Route::get(
        'operations/{accountList}/{budgetList}/{start_date}/{end_date}',
        ['uses' => 'BudgetReportController@mainChart', 'as' => 'main']
    );
}
);

/**
 * Chart\Category Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/category', 'as' => 'chart.category.'],
    function () {

        Route::get('frontpage', ['uses' => 'CategoryController@frontpage', 'as' => 'frontpage']);
        Route::get('period/{category}', ['uses' => 'CategoryController@currentPeriod', 'as' => 'current']);
        Route::get('period/{category}/{date}', ['uses' => 'CategoryController@specificPeriod', 'as' => 'specific']);
        Route::get('all/{category}', ['uses' => 'CategoryController@all', 'as' => 'all']);
        Route::get(
            'report-period/0/{accountList}/{start_date}/{end_date}', ['uses' => 'CategoryController@reportPeriodNoCategory', 'as' => 'period.no-category']
        );
        Route::get('report-period/{category}/{accountList}/{start_date}/{end_date}', ['uses' => 'CategoryController@reportPeriod', 'as' => 'period']);

        // these charts are used in reports (category reports):
        Route::get(
            'category/income/{accountList}/{categoryList}/{start_date}/{end_date}/{others}',
            ['uses' => 'CategoryReportController@categoryIncome', 'as' => 'category-income']
        );
        Route::get(
            'category/expense/{accountList}/{categoryList}/{start_date}/{end_date}/{others}',
            ['uses' => 'CategoryReportController@categoryExpense', 'as' => 'category-expense']
        );
        Route::get(
            'account/income/{accountList}/{categoryList}/{start_date}/{end_date}/{others}',
            ['uses' => 'CategoryReportController@accountIncome', 'as' => 'account-income']
        );
        Route::get(
            'account/expense/{accountList}/{categoryList}/{start_date}/{end_date}/{others}',
            ['uses' => 'CategoryReportController@accountExpense', 'as' => 'account-expense']
        );

        Route::get(
            'operations/{accountList}/{categoryList}/{start_date}/{end_date}',
            ['uses' => 'CategoryReportController@mainChart', 'as' => 'main']
        );

    }
);

/**
 * Chart\Tag Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/tag', 'as' => 'chart.tag.'], function () {

    // these charts are used in reports (tag reports):
    Route::get(
        'tag/income/{accountList}/{tagList}/{start_date}/{end_date}/{others}',
        ['uses' => 'TagReportController@tagIncome', 'as' => 'tag-income']
    );
    Route::get(
        'tag/expense/{accountList}/{tagList}/{start_date}/{end_date}/{others}',
        ['uses' => 'TagReportController@tagExpense', 'as' => 'tag-expense']
    );
    Route::get(
        'account/income/{accountList}/{tagList}/{start_date}/{end_date}/{others}',
        ['uses' => 'TagReportController@accountIncome', 'as' => 'account-income']
    );
    Route::get(
        'account/expense/{accountList}/{tagList}/{start_date}/{end_date}/{others}',
        ['uses' => 'TagReportController@accountExpense', 'as' => 'account-expense']
    );

    // new routes
    Route::get(
        'budget/expense/{accountList}/{tagList}/{start_date}/{end_date}',
        ['uses' => 'TagReportController@budgetExpense', 'as' => 'budget-expense']
    );
    Route::get(
        'category/expense/{accountList}/{tagList}/{start_date}/{end_date}',
        ['uses' => 'TagReportController@categoryExpense', 'as' => 'category-expense']

    );


    Route::get(
        'operations/{accountList}/{tagList}/{start_date}/{end_date}',
        ['uses' => 'TagReportController@mainChart', 'as' => 'main']
    );

}
);

/**
 * Chart\Expense Controller (for expense/revenue report).
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/expense', 'as' => 'chart.expense.'], function () {
    Route::get(
        'operations/{accountList}/{expenseList}/{start_date}/{end_date}',
        ['uses' => 'ExpenseReportController@mainChart', 'as' => 'main']
    );
}
);


/**
 * Chart\PiggyBank Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/piggy-bank', 'as' => 'chart.piggy-bank.'],
    function () {
        Route::get('{piggyBank}', ['uses' => 'PiggyBankController@history', 'as' => 'history']);
    }
);

/**
 * Chart\Report Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/report', 'as' => 'chart.report.'], function () {
    Route::get('operations/{accountList}/{start_date}/{end_date}', ['uses' => 'ReportController@operations', 'as' => 'operations']);
    Route::get('operations-sum/{accountList}/{start_date}/{end_date}/', ['uses' => 'ReportController@sum', 'as' => 'sum']);
    Route::get('net-worth/{accountList}/{start_date}/{end_date}/', ['uses' => 'ReportController@netWorth', 'as' => 'net-worth']);

}
);

/**
 * Import Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'import', 'as' => 'import.'], function () {

    Route::get('', ['uses' => 'Import\IndexController@index', 'as' => 'index']);

    // import method prerequisites:
    Route::get('prerequisites/{bank}', ['uses' => 'Import\PrerequisitesController@index', 'as' => 'prerequisites']);
    Route::post('prerequisites/{bank}', ['uses' => 'Import\PrerequisitesController@post', 'as' => 'prerequisites.post']);

    // create the job:
    Route::get('create/{bank}', ['uses' => 'Import\IndexController@create', 'as' => 'create-job']);

    // configure the job:
    Route::get('configure/{importJob}', ['uses' => 'Import\ConfigurationController@index', 'as' => 'configure']);
    Route::post('configure/{importJob}', ['uses' => 'Import\ConfigurationController@post', 'as' => 'configure.post']);

    // get status of any job:
    Route::get('status/{importJob}', ['uses' => 'Import\StatusController@index', 'as' => 'status']);
    Route::get('json/{importJob}', ['uses' => 'Import\StatusController@json', 'as' => 'status.json']);

    // start a job
    Route::any('start/{importJob}', ['uses' => 'Import\IndexController@start', 'as' => 'start']);

    // download config
    Route::get('download/{importJob}', ['uses' => 'Import\IndexController@download', 'as' => 'download']);
}
);

/**
 * Help Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'help', 'as' => 'help.'], function () {
    Route::get('{route}', ['uses' => 'HelpController@show', 'as' => 'show']);

}
);

/**
 * Budget Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'jscript', 'as' => 'javascript.'], function () {
    Route::get('variables', ['uses' => 'JavascriptController@variables', 'as' => 'variables']);
    Route::get('accounts', ['uses' => 'JavascriptController@accounts', 'as' => 'accounts']);
    Route::get('currencies', ['uses' => 'JavascriptController@currencies', 'as' => 'currencies']);
}
);

/**
 * JSON Controller(s)
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'json', 'as' => 'json.'], function () {
    Route::get('expense-accounts', ['uses' => 'Json\AutoCompleteController@expenseAccounts', 'as' => 'expense-accounts']);
    Route::get('all-accounts', ['uses' => 'Json\AutoCompleteController@allAccounts', 'as' => 'all-accounts']);
    Route::get('revenue-accounts', ['uses' => 'Json\AutoCompleteController@revenueAccounts', 'as' => 'revenue-accounts']);
    Route::get('categories', ['uses' => 'JsonController@categories', 'as' => 'categories']);
    Route::get('budgets', ['uses' => 'JsonController@budgets', 'as' => 'budgets']);
    Route::get('tags', ['uses' => 'JsonController@tags', 'as' => 'tags']);

    Route::get('box/balance', ['uses' => 'Json\BoxController@balance', 'as' => 'box.balance']);
    Route::get('box/bills', ['uses' => 'Json\BoxController@bills', 'as' => 'box.bills']);
    Route::get('box/available', ['uses' => 'Json\BoxController@available', 'as' => 'box.available']);
    Route::get('box/net-worth', ['uses' => 'Json\BoxController@netWorth', 'as' => 'box.net-worth']);

    Route::get('transaction-journals/all', ['uses' => 'Json\AutoCompleteController@allTransactionJournals', 'as' => 'all-transaction-journals']);
    Route::get('transaction-journals/with-id/{tj}', ['uses' => 'Json\AutoCompleteController@journalsWithId', 'as' => 'journals-with-id']);
    Route::get('transaction-journals/{what}', ['uses' => 'Json\AutoCompleteController@transactionJournals', 'as' => 'transaction-journals']);
    Route::get('transaction-types', ['uses' => 'JsonController@transactionTypes', 'as' => 'transaction-types']);
    Route::get('trigger', ['uses' => 'JsonController@trigger', 'as' => 'trigger']);
    Route::get('action', ['uses' => 'JsonController@action', 'as' => 'action']);

    // frontpage
    Route::get('frontpage/piggy-banks', ['uses' => 'Json\FrontpageController@piggyBanks', 'as' => 'fp.piggy-banks']);

    // currency conversion:
    Route::get('rate/{fromCurrencyCode}/{toCurrencyCode}/{date}', ['uses' => 'Json\ExchangeController@getRate', 'as' => 'rate']);

    // intro things:
    Route::post('intro/finished/{route}/{specificPage?}', ['uses' => 'Json\IntroController@postFinished', 'as' => 'intro.finished']);
    Route::post('intro/enable/{route}/{specificPage?}', ['uses' => 'Json\IntroController@postEnable', 'as' => 'intro.enable']);
    Route::get('intro/{route}/{specificPage?}', ['uses' => 'Json\IntroController@getIntroSteps', 'as' => 'intro']);


}
);


/**
 * NewUser Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'new-user', 'as' => 'new-user.'], function () {
    Route::get('', ['uses' => 'NewUserController@index', 'as' => 'index']);
    Route::post('submit', ['uses' => 'NewUserController@submit', 'as' => 'submit']);
}
);

/**
 * Piggy Bank Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'piggy-banks', 'as' => 'piggy-banks.'], function () {
    Route::get('', ['uses' => 'PiggyBankController@index', 'as' => 'index']);
    Route::get('add/{piggyBank}', ['uses' => 'PiggyBankController@add', 'as' => 'add-money']);
    Route::get('remove/{piggyBank}', ['uses' => 'PiggyBankController@remove', 'as' => 'remove-money']);
    Route::get('add-money/{piggyBank}', ['uses' => 'PiggyBankController@addMobile', 'as' => 'add-money-mobile']);
    Route::get('remove-money/{piggyBank}', ['uses' => 'PiggyBankController@removeMobile', 'as' => 'remove-money-mobile']);
    Route::get('create', ['uses' => 'PiggyBankController@create', 'as' => 'create']);
    Route::get('edit/{piggyBank}', ['uses' => 'PiggyBankController@edit', 'as' => 'edit']);
    Route::get('delete/{piggyBank}', ['uses' => 'PiggyBankController@delete', 'as' => 'delete']);
    Route::get('show/{piggyBank}', ['uses' => 'PiggyBankController@show', 'as' => 'show']);
    Route::post('store', ['uses' => 'PiggyBankController@store', 'as' => 'store']);
    Route::post('update/{piggyBank}', ['uses' => 'PiggyBankController@update', 'as' => 'update']);
    Route::post('destroy/{piggyBank}', ['uses' => 'PiggyBankController@destroy', 'as' => 'destroy']);
    Route::post('add/{piggyBank}', ['uses' => 'PiggyBankController@postAdd', 'as' => 'add']);
    Route::post('remove/{piggyBank}', ['uses' => 'PiggyBankController@postRemove', 'as' => 'remove']);
    Route::post('sort', ['uses' => 'PiggyBankController@order', 'as' => 'order']);


}
);


/**
 * Preferences Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'preferences', 'as' => 'preferences.'], function () {
    Route::get('', ['uses' => 'PreferencesController@index', 'as' => 'index']);
    Route::get('/code', ['uses' => 'PreferencesController@code', 'as' => 'code']);
    Route::get('/delete-code', ['uses' => 'PreferencesController@deleteCode', 'as' => 'delete-code']);
    Route::post('', ['uses' => 'PreferencesController@postIndex', 'as' => 'update']);
    Route::post('/code', ['uses' => 'PreferencesController@postCode', 'as' => 'code.store']);

}
);

/**
 * Profile Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'profile', 'as' => 'profile.'], function () {

    Route::get('', ['uses' => 'ProfileController@index', 'as' => 'index']);
    Route::get('change-email', ['uses' => 'ProfileController@changeEmail', 'as' => 'change-email']);
    Route::get('change-password', ['uses' => 'ProfileController@changePassword', 'as' => 'change-password']);
    Route::get('delete-account', ['uses' => 'ProfileController@deleteAccount', 'as' => 'delete-account']);

    Route::post('delete-account', ['uses' => 'ProfileController@postDeleteAccount', 'as' => 'delete-account.post']);
    Route::post('change-password', ['uses' => 'ProfileController@postChangePassword', 'as' => 'change-password.post']);
    Route::post('change-email', ['uses' => 'ProfileController@postChangeEmail', 'as' => 'change-email.post']);
    Route::post('regenerate', ['uses' => 'ProfileController@regenerate', 'as' => 'regenerate']);
}
);

/**
 * Report Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'reports', 'as' => 'reports.'], function () {

    Route::get('', ['uses' => 'ReportController@index', 'as' => 'index']);
    Route::get('options/{reportType}', ['uses' => 'ReportController@options', 'as' => 'options']);
    Route::get('default/{accountList}/{start_date}/{end_date}', ['uses' => 'ReportController@defaultReport', 'as' => 'report.default']);
    Route::get('audit/{accountList}/{start_date}/{end_date}', ['uses' => 'ReportController@auditReport', 'as' => 'report.audit']);
    Route::get('category/{accountList}/{categoryList}/{start_date}/{end_date}', ['uses' => 'ReportController@categoryReport', 'as' => 'report.category']);
    Route::get('budget/{accountList}/{budgetList}/{start_date}/{end_date}', ['uses' => 'ReportController@budgetReport', 'as' => 'report.budget']);
    Route::get('tag/{accountList}/{tagList}/{start_date}/{end_date}', ['uses' => 'ReportController@tagReport', 'as' => 'report.tag']);
    Route::get('account/{accountList}/{expenseList}/{start_date}/{end_date}', ['uses' => 'ReportController@accountReport', 'as' => 'report.account']);

    Route::post('', ['uses' => 'ReportController@postIndex', 'as' => 'index.post']);
}
);

/**
 * Report Data AccountController
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Report', 'prefix' => 'report-data/account', 'as' => 'report-data.account.'],
    function () {
        Route::get('general/{accountList}/{start_date}/{end_date}', ['uses' => 'AccountController@general', 'as' => 'general']);
    }
);

/**
 * Report Data Expense / Revenue Account Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Report', 'prefix' => 'report-data/expense', 'as' => 'report-data.expense.'],
    function () {

        // spent per period
        Route::get('spent/{accountList}/{expenseList}/{start_date}/{end_date}', ['uses' => 'ExpenseController@spent', 'as' => 'spent']);

        // per category && per budget
        Route::get('category/{accountList}/{expenseList}/{start_date}/{end_date}', ['uses' => 'ExpenseController@category', 'as' => 'category']);
        Route::get('budget/{accountList}/{expenseList}/{start_date}/{end_date}', ['uses' => 'ExpenseController@budget', 'as' => 'budget']);

        //expense earned top X
        Route::get('expenses/{accountList}/{expenseList}/{start_date}/{end_date}', ['uses' => 'ExpenseController@topExpense', 'as' => 'expenses']);
        Route::get('income/{accountList}/{expenseList}/{start_date}/{end_date}', ['uses' => 'ExpenseController@topIncome', 'as' => 'income']);

    }
);

/**
 * Report Data Income/Expenses Controller (called financial operations)
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Report', 'prefix' => 'report-data/operations',
     'as'         => 'report-data.operations.'], function () {
    Route::get('operations/{accountList}/{start_date}/{end_date}', ['uses' => 'OperationsController@operations', 'as' => 'operations']);
    Route::get('income/{accountList}/{start_date}/{end_date}', ['uses' => 'OperationsController@income', 'as' => 'income']);
    Route::get('expenses/{accountList}/{start_date}/{end_date}', ['uses' => 'OperationsController@expenses', 'as' => 'expenses']);

}
);

/**
 * Report Data Category Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Report', 'prefix' => 'report-data/category',
     'as'         => 'report-data.category.'], function () {
    Route::get('operations/{accountList}/{start_date}/{end_date}', ['uses' => 'CategoryController@operations', 'as' => 'operations']);
    Route::get('income/{accountList}/{start_date}/{end_date}', ['uses' => 'CategoryController@income', 'as' => 'income']);
    Route::get('expenses/{accountList}/{start_date}/{end_date}', ['uses' => 'CategoryController@expenses', 'as' => 'expenses']);

}
);

/**
 * Report Data Balance Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Report', 'prefix' => 'report-data/balance', 'as' => 'report-data.balance.'],
    function () {

        Route::get('general/{accountList}/{start_date}/{end_date}', ['uses' => 'BalanceController@general', 'as' => 'general']);
    }
);

/**
 * Report Data Budget Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Report', 'prefix' => 'report-data/budget', 'as' => 'report-data.budget.'],
    function () {

        Route::get('general/{accountList}/{start_date}/{end_date}/', ['uses' => 'BudgetController@general', 'as' => 'general']);
        Route::get('period/{accountList}/{start_date}/{end_date}', ['uses' => 'BudgetController@period', 'as' => 'period']);

    }
);

/**
 * Rules Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'rules', 'as' => 'rules.'], function () {

    Route::get('', ['uses' => 'RuleController@index', 'as' => 'index']);
    Route::get('create/{ruleGroup}', ['uses' => 'RuleController@create', 'as' => 'create']);
    Route::get('up/{rule}', ['uses' => 'RuleController@up', 'as' => 'up']);
    Route::get('down/{rule}', ['uses' => 'RuleController@down', 'as' => 'down']);
    Route::get('edit/{rule}', ['uses' => 'RuleController@edit', 'as' => 'edit']);
    Route::get('delete/{rule}', ['uses' => 'RuleController@delete', 'as' => 'delete']);
    Route::get('test', ['uses' => 'RuleController@testTriggers', 'as' => 'test-triggers']);
    Route::get('test-rule/{rule}', ['uses' => 'RuleController@testTriggersByRule', 'as' => 'test-triggers-rule']);
    Route::get('select/{rule}', ['uses' => 'RuleController@selectTransactions', 'as' => 'select-transactions']);

    Route::post('trigger/order/{rule}', ['uses' => 'RuleController@reorderRuleTriggers', 'as' => 'reorder-triggers']);
    Route::post('action/order/{rule}', ['uses' => 'RuleController@reorderRuleActions', 'as' => 'reorder-actions']);
    Route::post('store/{ruleGroup}', ['uses' => 'RuleController@store', 'as' => 'store']);
    Route::post('update/{rule}', ['uses' => 'RuleController@update', 'as' => 'update']);
    Route::post('destroy/{rule}', ['uses' => 'RuleController@destroy', 'as' => 'destroy']);
    Route::post('execute/{rule}', ['uses' => 'RuleController@execute', 'as' => 'execute']);

}
);

/**
 * Rule Groups Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'rule-groups', 'as' => 'rule-groups.'], function () {
    Route::get('create', ['uses' => 'RuleGroupController@create', 'as' => 'create']);
    Route::get('edit/{ruleGroup}', ['uses' => 'RuleGroupController@edit', 'as' => 'edit']);
    Route::get('delete/{ruleGroup}', ['uses' => 'RuleGroupController@delete', 'as' => 'delete']);
    Route::get('up/{ruleGroup}', ['uses' => 'RuleGroupController@up', 'as' => 'up']);
    Route::get('down/{ruleGroup}', ['uses' => 'RuleGroupController@down', 'as' => 'down']);
    Route::get('select/{ruleGroup}', ['uses' => 'RuleGroupController@selectTransactions', 'as' => 'select-transactions']);

    Route::post('store', ['uses' => 'RuleGroupController@store', 'as' => 'store']);
    Route::post('update/{ruleGroup}', ['uses' => 'RuleGroupController@update', 'as' => 'update']);
    Route::post('destroy/{ruleGroup}', ['uses' => 'RuleGroupController@destroy', 'as' => 'destroy']);
    Route::post('execute/{ruleGroup}', ['uses' => 'RuleGroupController@execute', 'as' => 'execute']);
}
);

/**
 * Search Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'search', 'as' => 'search.'], function () {
    Route::get('', ['uses' => 'SearchController@index', 'as' => 'index']);
    Route::any('search', ['uses' => 'SearchController@search', 'as' => 'search']);
}
);


/**
 * Tag Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'tags', 'as' => 'tags.'], function () {

    Route::get('', ['uses' => 'TagController@index', 'as' => 'index']);
    Route::get('create', ['uses' => 'TagController@create', 'as' => 'create']);

    Route::get('show/{tag}/{moment?}', ['uses' => 'TagController@show', 'as' => 'show']);

    Route::get('edit/{tag}', ['uses' => 'TagController@edit', 'as' => 'edit']);
    Route::get('delete/{tag}', ['uses' => 'TagController@delete', 'as' => 'delete']);

    Route::post('store', ['uses' => 'TagController@store', 'as' => 'store']);
    Route::post('update/{tag}', ['uses' => 'TagController@update', 'as' => 'update']);
    Route::post('destroy/{tag}', ['uses' => 'TagController@destroy', 'as' => 'destroy']);
}
);

/**
 * Transaction Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'transactions', 'as' => 'transactions.'], function () {
    Route::get('{what}/{moment?}', ['uses' => 'TransactionController@index', 'as' => 'index'])->where(['what' => 'withdrawal|deposit|transfers|transfer']);
    Route::get('show/{tj}', ['uses' => 'TransactionController@show', 'as' => 'show']);
    Route::post('reorder', ['uses' => 'TransactionController@reorder', 'as' => 'reorder']);
    Route::post('reconcile', ['uses' => 'TransactionController@reconcile', 'as' => 'reconcile']);
}
);

/**
 * Transaction Single Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Transaction', 'prefix' => 'transactions', 'as' => 'transactions.'],
    function () {
        Route::get('create/{what}', ['uses' => 'SingleController@create', 'as' => 'create'])->where(['what' => 'withdrawal|deposit|transfer']);
        Route::get('edit/{tj}', ['uses' => 'SingleController@edit', 'as' => 'edit']);
        Route::get('delete/{tj}', ['uses' => 'SingleController@delete', 'as' => 'delete']);
        Route::post('store/{what}', ['uses' => 'SingleController@store', 'as' => 'store'])->where(['what' => 'withdrawal|deposit|transfer']);
        Route::post('update/{tj}', ['uses' => 'SingleController@update', 'as' => 'update']);
        Route::post('destroy/{tj}', ['uses' => 'SingleController@destroy', 'as' => 'destroy']);
        Route::get('clone/{tj}', ['uses' => 'SingleController@cloneTransaction', 'as' => 'clone']);
    }
);

/**
 * Transaction Mass Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Transaction', 'prefix' => 'transactions/mass', 'as' => 'transactions.mass.'],
    function () {
        Route::get('edit/{journalList}', ['uses' => 'MassController@edit', 'as' => 'edit']);
        Route::get('delete/{journalList}', ['uses' => 'MassController@delete', 'as' => 'delete']);
        Route::post('update', ['uses' => 'MassController@update', 'as' => 'update']);
        Route::post('destroy', ['uses' => 'MassController@destroy', 'as' => 'destroy']);
    }
);

/**
 * Transaction Bulk Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Transaction', 'prefix' => 'transactions/bulk', 'as' => 'transactions.bulk.'],
    function () {
        Route::get('edit/{journalList}', ['uses' => 'BulkController@edit', 'as' => 'edit']);
        Route::post('update', ['uses' => 'BulkController@update', 'as' => 'update']);
    }
);

/**
 * Transaction Split Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Transaction', 'prefix' => 'transactions/split',
     'as'         => 'transactions.split.'], function () {
    Route::get('edit/{tj}', ['uses' => 'SplitController@edit', 'as' => 'edit']);
    Route::post('update/{tj}', ['uses' => 'SplitController@update', 'as' => 'update']);

}
);

/**
 * Transaction Convert Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Transaction', 'prefix' => 'transactions/convert',
     'as'         => 'transactions.convert.'], function () {
    Route::get('{transactionType}/{tj}', ['uses' => 'ConvertController@index', 'as' => 'index']);
    Route::post('{transactionType}/{tj}', ['uses' => 'ConvertController@postIndex', 'as' => 'index.post']);
}
);

/**
 * Transaction Link Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Transaction', 'prefix' => 'transactions/link', 'as' => 'transactions.link.'],
    function () {
        Route::post('store/{tj}', ['uses' => 'LinkController@store', 'as' => 'store']);

        Route::get('delete/{journalLink}', ['uses' => 'LinkController@delete', 'as' => 'delete']);
        Route::get('switch/{journalLink}', ['uses' => 'LinkController@switchLink', 'as' => 'switch']);

        Route::post('destroy/{journalLink}', ['uses' => 'LinkController@destroy', 'as' => 'destroy']);
    }
);

/**
 * Report Popup Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Popup', 'prefix' => 'popup', 'as' => 'popup.'], function () {
    Route::get('general', ['uses' => 'ReportController@general', 'as' => 'general']);

}
);

/**
 * For the admin routes, the user must be logged in and have the role of 'owner'
 */
Route::group(
    ['middleware' => 'admin', 'namespace' => 'FireflyIII\Http\Controllers\Admin', 'prefix' => 'admin', 'as' => 'admin.'], function () {

    // admin home
    Route::get('', ['uses' => 'HomeController@index', 'as' => 'index']);
    Route::post('test-message', ['uses' => 'HomeController@testMessage', 'as' => 'test-message']);

    // check for updates?
    Route::get('update-check', ['uses' => 'UpdateController@index', 'as' => 'update-check']);
    Route::post('update-check/manual', ['uses' => 'UpdateController@updateCheck', 'as' => 'update-check.manual']);
    Route::post('update-check', ['uses' => 'UpdateController@post', 'as' => 'update-check.post']);

    // user manager
    Route::get('users', ['uses' => 'UserController@index', 'as' => 'users']);
    Route::get('users/edit/{user}', ['uses' => 'UserController@edit', 'as' => 'users.edit']);
    Route::get('users/delete/{user}', ['uses' => 'UserController@delete', 'as' => 'users.delete']);
    Route::get('users/show/{user}', ['uses' => 'UserController@show', 'as' => 'users.show']);

    Route::post('users/update/{user}', ['uses' => 'UserController@update', 'as' => 'users.update']);
    Route::post('users/destroy/{user}', ['uses' => 'UserController@destroy', 'as' => 'users.destroy']);

    // journal links manager
    Route::get('links', ['uses' => 'LinkController@index', 'as' => 'links.index']);
    Route::get('links/create', ['uses' => 'LinkController@create', 'as' => 'links.create']);
    Route::get('links/show/{linkType}', ['uses' => 'LinkController@show', 'as' => 'links.show']);
    Route::get('links/edit/{linkType}', ['uses' => 'LinkController@edit', 'as' => 'links.edit']);
    Route::get('links/delete/{linkType}', ['uses' => 'LinkController@delete', 'as' => 'links.delete']);


    Route::post('links/store', ['uses' => 'LinkController@store', 'as' => 'links.store']);
    Route::post('links/update/{linkType}', ['uses' => 'LinkController@update', 'as' => 'links.update']);
    Route::post('links/destroy/{linkType}', ['uses' => 'LinkController@destroy', 'as' => 'links.destroy']);

    // FF configuration:
    Route::get('configuration', ['uses' => 'ConfigurationController@index', 'as' => 'configuration.index']);
    Route::post('configuration', ['uses' => 'ConfigurationController@postIndex', 'as' => 'configuration.index.post']);

}
);
