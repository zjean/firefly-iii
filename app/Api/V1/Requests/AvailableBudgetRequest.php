<?php
/**
 * AvailableBudgetRequest.php
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

namespace FireflyIII\Api\V1\Requests;

/**
 * Class AvailableBudgetRequest
 */
class AvailableBudgetRequest extends Request
{
    /**
     * Authorize logged in users.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return auth()->check();
    }

    /**
     * Get all data from the request.
     *
     * @return array
     */
    public function getAll(): array
    {
        return [
            'currency_id'   => $this->integer('currency_id'),
            'currency_code' => $this->string('currency_code'),
            'amount'        => $this->string('amount'),
            'start_date'    => $this->date('start_date'),
            'end_date'      => $this->date('end_date'),
        ];
    }

    /**
     * The rules that the incoming request must be matched against.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'currency_id'   => 'numeric|exists:transaction_currencies,id|required_without:currency_code',
            'currency_code' => 'min:3|max:3|exists:transaction_currencies,code|required_without:currency_id',
            'amount'        => 'required|numeric|more:0',
            'start_date'    => 'required|date|before:end_date',
            'end_date'      => 'required|date|after:start_date',
        ];

        return $rules;
    }


}
