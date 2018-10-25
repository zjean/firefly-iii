<?php
/**
 * RecurrenceController.php
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

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Json;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class RecurrenceController
 */
class RecurrenceController extends Controller
{
    /** @var RecurringRepositoryInterface The recurring repository. */
    private $recurring;

    /**
     * RecurrenceController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                $this->recurring = app(RecurringRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Shows all events for a repetition. Used in calendar.
     *
     * @param Request $request
     *
     * @throws FireflyException
     * @return JsonResponse
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function events(Request $request): JsonResponse
    {
        $return           = [];
        $start            = Carbon::createFromFormat('Y-m-d', $request->get('start'));
        $end              = Carbon::createFromFormat('Y-m-d', $request->get('end'));
        $firstDate        = Carbon::createFromFormat('Y-m-d', $request->get('first_date'));
        $endDate          = '' !== (string)$request->get('end_date') ? Carbon::createFromFormat('Y-m-d', $request->get('end_date')) : null;
        $endsAt           = (string)$request->get('ends');
        $repetitionType   = explode(',', $request->get('type'))[0];
        $repetitions      = (int)$request->get('reps');
        $repetitionMoment = '';
        $start->startOfDay();

        // if $firstDate is beyond $end, simply return an empty array.
        if ($firstDate->gt($end)) {
            return response()->json([]);
        }
        // if $firstDate is beyond start, use that one:
        $actualStart = clone $firstDate;

        if ('weekly' === $repetitionType || 'monthly' === $repetitionType) {
            $repetitionMoment = explode(',', $request->get('type'))[1] ?? '1';
        }
        if ('ndom' === $repetitionType) {
            $repetitionMoment = str_ireplace('ndom,', '', $request->get('type'));
        }
        if ('yearly' === $repetitionType) {
            $repetitionMoment = explode(',', $request->get('type'))[1] ?? '2018-01-01';
        }
        $repetition                    = new RecurrenceRepetition;
        $repetition->repetition_type   = $repetitionType;
        $repetition->repetition_moment = $repetitionMoment;
        $repetition->repetition_skip   = (int)$request->get('skip');
        $repetition->weekend           = (int)$request->get('weekend');
        $actualEnd                     = clone $end;
        $occurrences                   = [];
        switch ($endsAt) {
            case 'forever':
                // simply generate up until $end. No change from default behavior.
                $occurrences = $this->recurring->getOccurrencesInRange($repetition, $actualStart, $actualEnd);
                break;
            case 'until_date':
                $actualEnd   = $endDate ?? clone $end;
                $occurrences = $this->recurring->getOccurrencesInRange($repetition, $actualStart, $actualEnd);
                break;
            case 'times':
                $occurrences = $this->recurring->getXOccurrences($repetition, $actualStart, $repetitions);
                break;
        }


        /** @var Carbon $current */
        foreach ($occurrences as $current) {
            if ($current->gte($start)) {
                $event    = [
                    'id'        => $repetitionType . $firstDate->format('Ymd'),
                    'title'     => 'X',
                    'allDay'    => true,
                    'start'     => $current->format('Y-m-d'),
                    'end'       => $current->format('Y-m-d'),
                    'editable'  => false,
                    'rendering' => 'background',
                ];
                $return[] = $event;
            }
        }

        return response()->json($return);
    }

    /**
     * Suggests repetition moments.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function suggest(Request $request): JsonResponse
    {
        $today       = new Carbon;
        $date        = Carbon::createFromFormat('Y-m-d', $request->get('date'));
        $preSelected = (string)$request->get('pre_select');
        $result      = [];
        if ($date > $today || 'true' === (string)$request->get('past')) {
            $weekly     = sprintf('weekly,%s', $date->dayOfWeekIso);
            $monthly    = sprintf('monthly,%s', $date->day);
            $dayOfWeek  = (string)trans(sprintf('config.dow_%s', $date->dayOfWeekIso));
            $ndom       = sprintf('ndom,%s,%s', $date->weekOfMonth, $date->dayOfWeekIso);
            $yearly     = sprintf('yearly,%s', $date->format('Y-m-d'));
            $yearlyDate = $date->formatLocalized((string)trans('config.month_and_day_no_year'));
            $result     = [
                'daily'  => ['label' => (string)trans('firefly.recurring_daily'), 'selected' => 0 === strpos($preSelected, 'daily')],
                $weekly  => ['label'    => (string)trans('firefly.recurring_weekly', ['weekday' => $dayOfWeek]),
                             'selected' => 0 === strpos($preSelected, 'weekly')],
                $monthly => ['label'    => (string)trans('firefly.recurring_monthly', ['dayOfMonth' => $date->day]),
                             'selected' => 0 === strpos($preSelected, 'monthly')],
                $ndom    => ['label'    => (string)trans('firefly.recurring_ndom', ['weekday' => $dayOfWeek, 'dayOfMonth' => $date->weekOfMonth]),
                             'selected' => 0 === strpos($preSelected, 'ndom')],
                $yearly  => ['label' => (string)trans('firefly.recurring_yearly', ['date' => $yearlyDate]), 'selected' => 0 === strpos($preSelected, 'yearly')],
            ];
        }


        return response()->json($result);
    }

}
