<?php
declare(strict_types=1);

/*
 * 2018_01_01_000001_create_oauth_auth_codes_table.php
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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateOauthAuthCodesTable
 */
class CreateOauthAuthCodesTable extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::drop('oauth_auth_codes');
    }

    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create(
            'oauth_auth_codes', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->integer('user_id');
            $table->integer('client_id');
            $table->text('scopes')->nullable();
            $table->boolean('revoked');
            $table->dateTime('expires_at')->nullable();
        }
        );
    }
}
