<?php
/**
 * LinkTypeFormRequest.php
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

/**
 * Class BillFormRequest.
 */
class LinkTypeFormRequest extends Request
{
    /**
     * Verify the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow logged and admins
        return auth()->check();
    }

    /**
     * Rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        // fixed
        $nameRule = 'required|min:1|unique:link_types,name';
        $idRule   = '';

        // get parameter link:
        $link = $this->route()->parameter('linkType');

        if (null !== $link) {
            $idRule   = 'exists:link_types,id';
            $nameRule = 'required|min:1';
        }

        $rules = [
            'id'      => $idRule,
            'name'    => $nameRule,
            'inward'  => 'required|min:1|different:outward',
            'outward' => 'required|min:1|different:inward',
        ];

        return $rules;
    }
}
