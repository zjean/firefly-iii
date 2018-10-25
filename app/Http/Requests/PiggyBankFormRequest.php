<?php
/**
 * PiggyBankFormRequest.php
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

namespace FireflyIII\Http\Requests;

use FireflyIII\Models\PiggyBank;

/**
 * Class PiggyBankFormRequest.
 */
class PiggyBankFormRequest extends Request
{
    /**
     * Verify the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * Returns the data required by the controller.
     *
     * @return array
     */
    public function getPiggyBankData(): array
    {
        return [
            'name'         => $this->string('name'),
            'startdate'    => $this->date('startdate'),
            'account_id'   => $this->integer('account_id'),
            'targetamount' => $this->string('targetamount'),
            'targetdate'   => $this->date('targetdate'),
            'notes'        => $this->string('notes'),
        ];
    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        $nameRule = 'required|between:1,255|uniquePiggyBankForUser';

        /** @var PiggyBank $piggy */
        $piggy = $this->route()->parameter('piggyBank');

        if (null !== $piggy) {
            $nameRule = 'required|between:1,255|uniquePiggyBankForUser:' . $piggy->id;
        }

        $rules = [
            'name'         => $nameRule,
            'account_id'   => 'required|belongsToUser:accounts',
            'targetamount' => 'required|numeric|more:0',
            'startdate'    => 'date',
            'targetdate'   => 'date|nullable',
            'order'        => 'integer|min:1',
        ];

        return $rules;
    }
}
