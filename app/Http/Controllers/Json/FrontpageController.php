<?php
/**
 * FrontpageController.php
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

namespace FireflyIII\Http\Controllers\Json;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;

/**
 * Class FrontpageController.
 */
class FrontpageController extends Controller
{
    /**
     * @param PiggyBankRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     *

     */
    public function piggyBanks(PiggyBankRepositoryInterface $repository)
    {
        $set  = $repository->getPiggyBanks();
        $info = [];
        /** @var PiggyBank $piggyBank */
        foreach ($set as $piggyBank) {
            $amount = $repository->getCurrentAmount($piggyBank);
            if (1 === bccomp($amount, '0')) {
                // percentage!
                $pct = round(($amount / $piggyBank->targetamount) * 100);

                $entry = [
                    'id'         => $piggyBank->id,
                    'name'       => $piggyBank->name,
                    'amount'     => $amount,
                    'target'     => $piggyBank->targetamount,
                    'percentage' => $pct,
                ];

                $info[] = $entry;
            }
        }
        $html = '';
        if (count($info) > 0) {
            $html = view('json.piggy-banks', compact('info'))->render();
        }

        return response()->json(['html' => $html]);
    }
}
