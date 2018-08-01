<?php
/**
 * PiggyBankRequest.php
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

use FireflyIII\Models\PiggyBank;
use FireflyIII\Rules\IsAssetAccountId;

/**
 *
 * Class PiggyBankRequest
 */
class PiggyBankRequest extends Request
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
            'name'           => $this->string('name'),
            'account_id'     => $this->integer('account_id'),
            'targetamount'   => $this->string('target_amount'),
            'current_amount' => $this->string('current_amount'),
            'start_date'     => $this->date('start_date'),
            'target_date'    => $this->date('target_date'),
            'note'           => $this->string('notes'),
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
            'name'           => 'required|between:1,255|uniquePiggyBankForUser',
            'account_id'     => ['required', 'belongsToUser:accounts', new IsAssetAccountId],
            'target_amount'  => 'required|numeric|more:0',
            'current_amount' => 'numeric|more:0|lte:target_amount',
            'start_date'     => 'date|nullable',
            'target_date'    => 'date|nullable',
            'notes'          => 'max:65000',
        ];

        switch ($this->method()) {
            default:
                break;
            case 'PUT':
            case 'PATCH':
                /** @var PiggyBank $piggyBank */
                $piggyBank     = $this->route()->parameter('piggyBank');
                $rules['name'] = 'required|between:1,255|uniquePiggyBankForUser:' . $piggyBank->id;
                break;
        }


        return $rules;
    }

}
