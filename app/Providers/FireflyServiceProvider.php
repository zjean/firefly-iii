<?php
/**
 * FireflyServiceProvider.php
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

namespace FireflyIII\Providers;

use FireflyIII\Export\ExpandedProcessor;
use FireflyIII\Export\ProcessorInterface;
use FireflyIII\Generator\Chart\Basic\ChartJsGenerator;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Attachments\AttachmentHelper;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Helpers\Chart\MetaPieChart;
use FireflyIII\Helpers\Chart\MetaPieChartInterface;
use FireflyIII\Helpers\FiscalHelper;
use FireflyIII\Helpers\FiscalHelperInterface;
use FireflyIII\Helpers\Help\Help;
use FireflyIII\Helpers\Help\HelpInterface;
use FireflyIII\Helpers\Report\BalanceReportHelper;
use FireflyIII\Helpers\Report\BalanceReportHelperInterface;
use FireflyIII\Helpers\Report\BudgetReportHelper;
use FireflyIII\Helpers\Report\BudgetReportHelperInterface;
use FireflyIII\Helpers\Report\PopupReport;
use FireflyIII\Helpers\Report\PopupReportInterface;
use FireflyIII\Helpers\Report\ReportHelper;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Repositories\User\UserRepository;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Services\Currency\ExchangeRateInterface;
use FireflyIII\Services\Currency\FixerIOv2;
use FireflyIII\Services\IP\IpifyOrg;
use FireflyIII\Services\IP\IPRetrievalInterface;
use FireflyIII\Services\Password\PwndVerifierV2;
use FireflyIII\Services\Password\Verifier;
use FireflyIII\Support\Amount;
use FireflyIII\Support\ExpandedForm;
use FireflyIII\Support\FireflyConfig;
use FireflyIII\Support\Navigation;
use FireflyIII\Support\Preferences;
use FireflyIII\Support\Steam;
use FireflyIII\Support\Twig\AmountFormat;
use FireflyIII\Support\Twig\General;
use FireflyIII\Support\Twig\Journal;
use FireflyIII\Support\Twig\Loader\AccountLoader;
use FireflyIII\Support\Twig\Loader\TransactionJournalLoader;
use FireflyIII\Support\Twig\Loader\TransactionLoader;
use FireflyIII\Support\Twig\Rule;
use FireflyIII\Support\Twig\Transaction;
use FireflyIII\Support\Twig\Translation;
use FireflyIII\Validation\FireflyValidator;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Twig;
use TwigBridge\Extension\Loader\Functions;
use Validator;

/**
 *
 * Class FireflyServiceProvider.
 *
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FireflyServiceProvider extends ServiceProvider
{
    /**
     * Start provider.
     */
    public function boot(): void
    {
        Validator::resolver(
        /** @noinspection MoreThanThreeArgumentsInspection */
            function ($translator, $data, $rules, $messages) {
                return new FireflyValidator($translator, $data, $rules, $messages);
            }
        );
        $config = app('config');
        Twig::addExtension(new Functions($config));
        Twig::addRuntimeLoader(new TransactionLoader);
        Twig::addRuntimeLoader(new AccountLoader);
        Twig::addRuntimeLoader(new TransactionJournalLoader);
        Twig::addExtension(new General);
        Twig::addExtension(new Journal);
        Twig::addExtension(new Translation);
        Twig::addExtension(new Transaction);
        Twig::addExtension(new Rule);
        Twig::addExtension(new AmountFormat);
    }

    /**
     * Register stuff.
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function register(): void
    {
        $this->app->bind(
            'preferences',
            function () {
                return new Preferences;
            }
        );

        $this->app->bind(
            'fireflyconfig',
            function () {
                return new FireflyConfig;
            }
        );
        $this->app->bind(
            'navigation',
            function () {
                return new Navigation;
            }
        );
        $this->app->bind(
            'amount',
            function () {
                return new Amount;
            }
        );

        $this->app->bind(
            'steam',
            function () {
                return new Steam;
            }
        );
        $this->app->bind(
            'expandedform',
            function () {
                return new ExpandedForm;
            }
        );

        // chart generator:
        $this->app->bind(GeneratorInterface::class, ChartJsGenerator::class);

        // chart builder
        $this->app->bind(
            MetaPieChartInterface::class,
            function (Application $app) {
                /** @var MetaPieChart $chart */
                $chart = app(MetaPieChart::class);
                if ($app->auth->check()) {
                    $chart->setUser(auth()->user());
                }

                return $chart;
            }
        );

        // other generators
        // export:
        $this->app->bind(ProcessorInterface::class, ExpandedProcessor::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AttachmentHelperInterface::class, AttachmentHelper::class);

        // more generators:
        $this->app->bind(PopupReportInterface::class, PopupReport::class);
        $this->app->bind(HelpInterface::class, Help::class);
        $this->app->bind(ReportHelperInterface::class, ReportHelper::class);
        $this->app->bind(FiscalHelperInterface::class, FiscalHelper::class);
        $this->app->bind(BalanceReportHelperInterface::class, BalanceReportHelper::class);
        $this->app->bind(BudgetReportHelperInterface::class, BudgetReportHelper::class);
        $this->app->bind(ExchangeRateInterface::class, FixerIOv2::class);

        // password verifier thing
        $this->app->bind(Verifier::class, PwndVerifierV2::class);

        // IP thing:
        $this->app->bind(IPRetrievalInterface::class, IpifyOrg::class);
    }
}
