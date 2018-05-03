<?php
declare(strict_types=1);
/**
 * User.php
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

namespace FireflyIII;

use FireflyIII\Events\RequestedNewPassword;
use FireflyIII\Models\CurrencyExchangeRate;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Log;
use Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\Role;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\ExportJob;
use FireflyIII\Models\Category;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Attachment;

/**
 * Class User.
 */
class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'blocked'    => 'boolean',
        ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['email', 'password', 'blocked', 'blocked_code'];
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * @param string $value
     *
     * @return User
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): User
    {
        if (auth()->check()) {
            $userId = (int)$value;
            $user   = self::find($userId);
            if (null !== $user) {
                return $user;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * Link to accounts.
     *
     * @return HasMany
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * Full credit goes to: https://github.com/Zizaco/entrust
     *
     * @param mixed $role
     */
    public function attachRole($role)
    {
        if (\is_object($role)) {
            $role = $role->getKey();
        }

        if (\is_array($role)) {
            $role = $role['id'];
        }
        try {
            $this->roles()->attach($role);
        } catch (QueryException $e) {
            // don't care
            Log::info(sprintf('Query exception when giving user a role: %s', $e->getMessage()));
        }
    }

    /**
     * @codeCoverageIgnore
     * Link to attachments
     *
     * @return HasMany
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to available budgets
     *
     * @return HasMany
     */
    public function availableBudgets(): HasMany
    {
        return $this->hasMany(AvailableBudget::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to bills.
     *
     * @return HasMany
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to budgets.
     *
     * @return HasMany
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to categories
     *
     * @return HasMany
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to currency exchange rates
     *
     * @return HasMany
     */
    public function currencyExchangeRates(): HasMany
    {
        return $this->hasMany(CurrencyExchangeRate::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to export jobs
     *
     * @return HasMany
     */
    public function exportJobs(): HasMany
    {
        return $this->hasMany(ExportJob::class);
    }

    /**
     * @codeCoverageIgnore
     * Generates access token.
     *
     * @return string
     */
    public function generateAccessToken(): string
    {
        $bytes = random_bytes(16);

        return (string)bin2hex($bytes);
    }

    /**
     * @codeCoverageIgnore
     * Checks if the user has a role by its name.
     *
     * Full credit goes to: https://github.com/Zizaco/entrust
     *
     * @param string $name
     *
     * @deprecated
     * @return bool
     */
    public function hasRole(string $name): bool
    {
        foreach ($this->roles as $role) {
            if ($role->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @codeCoverageIgnore
     * Link to import jobs.
     *
     * @return HasMany
     */
    public function importJobs(): HasMany
    {
        return $this->hasMany(ImportJob::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to piggy banks.
     *
     * @return HasManyThrough
     */
    public function piggyBanks(): HasManyThrough
    {
        return $this->hasManyThrough(PiggyBank::class, Account::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to preferences.
     *
     * @return HasMany
     */
    public function preferences(): HasMany
    {
        return $this->hasMany(Preference::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to rule groups.
     *
     * @return HasMany
     */
    public function ruleGroups(): HasMany
    {
        return $this->hasMany(RuleGroup::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to rules.
     *
     * @return HasMany
     */
    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    /**
     * @codeCoverageIgnore
     * Send the password reset notification.
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token)
    {
        $ipAddress = Request::ip();

        event(new RequestedNewPassword($this, $token, $ipAddress));
    }

    /**
     * @codeCoverageIgnore
     * Link to tags.
     *
     * @return HasMany
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to transaction journals.
     *
     * @return HasMany
     */
    public function transactionJournals(): HasMany
    {
        return $this->hasMany(TransactionJournal::class);
    }

    /**
     * @codeCoverageIgnore
     * Link to transactions.
     *
     * @return HasManyThrough
     */
    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, TransactionJournal::class);
    }
}
