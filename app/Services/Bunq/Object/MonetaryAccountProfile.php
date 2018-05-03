<?php
/**
 * MonetaryAccountProfile.php
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

namespace FireflyIII\Services\Bunq\Object;

/**
 * Class MonetaryAccountProfile.
 */
class MonetaryAccountProfile extends BunqObject
{
    /** @var string */
    private $profileActionRequired;
    /** @var Amount */
    private $profileAmountRequired;
    /**
     * @var null
     */
    private $profileDrain;
    /**
     * @var null
     */
    private $profileFill;

    /**
     * MonetaryAccountProfile constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->profileDrain          = null;
        $this->profileFill           = null;
        $this->profileActionRequired = $data['profile_action_required'];
        $this->profileAmountRequired = new Amount($data['profile_amount_required']);

        return;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'profile_action_required' => $this->profileActionRequired,
            'profile_amount_required' => $this->profileAmountRequired->toArray(),
        ];
    }
}
