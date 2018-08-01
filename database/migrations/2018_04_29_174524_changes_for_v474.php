<?php

/**
 * 2018_04_29_174524_changes_for_v474.php
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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesForV474
 */
class ChangesForV474 extends Migration
{
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(
            'import_jobs',
            function (Blueprint $table) {
                $table->string('provider', 50)->after('file_type')->default('');
                $table->string('stage', 50)->after('status')->default('');
                $table->longText('transactions')->after('extended_status')->nullable();
                $table->longText('errors')->after('transactions')->nullable();

                $table->integer('tag_id', false, true)->nullable()->after('user_id');
                $table->foreign('tag_id')->references('id')->on('tags')->onDelete('set null');
            }
        );
    }
}
