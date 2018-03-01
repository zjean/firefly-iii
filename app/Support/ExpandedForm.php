<?php
/**
 * ExpandedForm.php
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

namespace FireflyIII\Support;

use Amount as Amt;
use Carbon\Carbon;
use Eloquent;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use RuntimeException;
use Session;

/**
 * Class ExpandedForm.
 */
class ExpandedForm
{
    /**
     * @param string $name
     * @param null   $value
     * @param array  $options
     *
     * @return string
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function amount(string $name, $value = null, array $options = []): string
    {
        return $this->currencyField($name, 'amount', $value, $options);
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function amountSmall(string $name, $value = null, array $options = []): string
    {
        return $this->currencyField($name, 'amount-small', $value, $options);
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     * @throws \FireflyIII\Exceptions\FireflyException
     *
     */
    public function balance(string $name, $value = null, array $options = []): string
    {
        return $this->currencyField($name, 'balance', $value, $options);
    }

    /**
     * @param       $name
     * @param int   $value
     * @param null  $checked
     * @param array $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function checkbox(string $name, $value = 1, $checked = null, $options = []): string
    {
        $options['checked'] = true === $checked ? true : null;
        $label              = $this->label($name, $options);
        $options            = $this->expandOptionArray($name, $label, $options);
        $classes            = $this->getHolderClasses($name);
        $value              = $this->fillFieldValue($name, $value);

        unset($options['placeholder'], $options['autocomplete'], $options['class']);

        $html = view('form.checkbox', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function date(string $name, $value = null, array $options = []): string
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);
        unset($options['placeholder']);
        $html = view('form.date', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param array $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function file(string $name, array $options = []): string
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $html    = view('form.file', compact('classes', 'name', 'label', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function integer(string $name, $value = null, array $options = []): string
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = '1';
        $html            = view('form.integer', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function location(string $name, $value = null, array $options = []): string
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);
        $html    = view('form.location', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * Takes any collection and tries to make a sensible select list compatible array of it.
     *
     * @param \Illuminate\Support\Collection $set
     *
     * @return array
     */
    public function makeSelectList(Collection $set): array
    {
        $selectList = [];
        $fields     = ['title', 'name', 'description'];
        /** @var Eloquent $entry */
        foreach ($set as $entry) {
            $entryId = intval($entry->id);
            $title   = null;

            foreach ($fields as $field) {
                if (isset($entry->$field) && null === $title) {
                    $title = $entry->$field;
                }
            }
            $selectList[$entryId] = $title;
        }

        return $selectList;
    }

    /**
     * @param \Illuminate\Support\Collection $set
     *
     * @return array
     */
    public function makeSelectListWithEmpty(Collection $set): array
    {
        $selectList    = [];
        $selectList[0] = '(none)';
        $fields        = ['title', 'name', 'description'];
        /** @var Eloquent $entry */
        foreach ($set as $entry) {
            $entryId = intval($entry->id);
            $title   = null;

            foreach ($fields as $field) {
                if (isset($entry->$field) && null === $title) {
                    $title = $entry->$field;
                }
            }
            $selectList[$entryId] = $title;
        }

        return $selectList;
    }

    /**
     * @param       $name
     * @param array $list
     * @param null  $selected
     * @param array $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function multiCheckbox(string $name, array $list = [], $selected = null, array $options = []): string
    {
        $label    = $this->label($name, $options);
        $options  = $this->expandOptionArray($name, $label, $options);
        $classes  = $this->getHolderClasses($name);
        $selected = $this->fillFieldValue($name, $selected);

        unset($options['class']);
        $html = view('form.multiCheckbox', compact('classes', 'name', 'label', 'selected', 'options', 'list'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param array $list
     * @param null  $selected
     * @param array $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function multiRadio(string $name, array $list = [], $selected = null, array $options = []): string
    {
        $label    = $this->label($name, $options);
        $options  = $this->expandOptionArray($name, $label, $options);
        $classes  = $this->getHolderClasses($name);
        $selected = $this->fillFieldValue($name, $selected);

        unset($options['class']);
        $html = view('form.multiRadio', compact('classes', 'name', 'label', 'selected', 'options', 'list'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param null   $value
     * @param array  $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function nonSelectableAmount(string $name, $value = null, array $options = []): string
    {
        $label            = $this->label($name, $options);
        $options          = $this->expandOptionArray($name, $label, $options);
        $classes          = $this->getHolderClasses($name);
        $value            = $this->fillFieldValue($name, $value);
        $options['step']  = 'any';
        $selectedCurrency = isset($options['currency']) ? $options['currency'] : Amt::getDefaultCurrency();
        unset($options['currency']);
        unset($options['placeholder']);

        // make sure value is formatted nicely:
        if (null !== $value && '' !== $value) {
            $value = round($value, $selectedCurrency->decimal_places);
        }

        $html = view('form.non-selectable-amount', compact('selectedCurrency', 'classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param null   $value
     * @param array  $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function nonSelectableBalance(string $name, $value = null, array $options = []): string
    {
        $label            = $this->label($name, $options);
        $options          = $this->expandOptionArray($name, $label, $options);
        $classes          = $this->getHolderClasses($name);
        $value            = $this->fillFieldValue($name, $value);
        $options['step']  = 'any';
        $selectedCurrency = isset($options['currency']) ? $options['currency'] : Amt::getDefaultCurrency();
        unset($options['currency']);
        unset($options['placeholder']);

        // make sure value is formatted nicely:
        if (null !== $value && '' !== $value) {
            $decimals = $selectedCurrency->decimal_places ?? 2;
            $value    = round($value, $decimals);
        }

        $html = view('form.non-selectable-amount', compact('selectedCurrency', 'classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param string $name
     * @param null   $value
     * @param array  $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function number(string $name, $value = null, array $options = []): string
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = 'any';
        unset($options['placeholder']);

        $html = view('form.number', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param $type
     * @param $name
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function optionsList(string $type, string $name): string
    {
        $previousValue = null;

        try {
            $previousValue = request()->old('post_submit_action');
        } catch (RuntimeException $e) {
            // don't care
        }

        $previousValue = null === $previousValue ? 'store' : $previousValue;
        $html          = view('form.options', compact('type', 'name', 'previousValue'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param array $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function password(string $name, array $options = []): string
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $html    = view('form.password', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param array $list
     * @param null  $selected
     * @param array $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function select(string $name, array $list = [], $selected = null, array $options = []): string
    {
        $label    = $this->label($name, $options);
        $options  = $this->expandOptionArray($name, $label, $options);
        $classes  = $this->getHolderClasses($name);
        $selected = $this->fillFieldValue($name, $selected);
        unset($options['autocomplete']);
        unset($options['placeholder']);
        $html = view('form.select', compact('classes', 'name', 'label', 'selected', 'options', 'list'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function staticText(string $name, $value, array $options = []): string
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $html    = view('form.static', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function tags(string $name, $value = null, array $options = []): string
    {
        $label                = $this->label($name, $options);
        $options              = $this->expandOptionArray($name, $label, $options);
        $classes              = $this->getHolderClasses($name);
        $value                = $this->fillFieldValue($name, $value);
        $options['data-role'] = 'tagsinput';
        $html                 = view('form.tags', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function text(string $name, $value = null, array $options = []): string
    {
        $label   = $this->label($name, $options);
        $options = $this->expandOptionArray($name, $label, $options);
        $classes = $this->getHolderClasses($name);
        $value   = $this->fillFieldValue($name, $value);
        $html    = view('form.text', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param null  $value
     * @param array $options
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function textarea(string $name, $value = null, array $options = []): string
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['rows'] = 4;
        $html            = view('form.textarea', compact('classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }

    /**
     * @param       $name
     * @param       $label
     * @param array $options
     *
     * @return array
     */
    protected function expandOptionArray(string $name, $label, array $options): array
    {
        $name                    = str_replace('[]', '', $name);
        $options['class']        = 'form-control';
        $options['id']           = 'ffInput_' . $name;
        $options['autocomplete'] = 'off';
        $options['placeholder']  = ucfirst($label);

        return $options;
    }

    /**
     * @param $name
     * @param $value
     *
     * @return mixed
     */
    protected function fillFieldValue(string $name, $value)
    {
        if (Session::has('preFilled')) {
            $preFilled = session('preFilled');
            $value     = isset($preFilled[$name]) && null === $value ? $preFilled[$name] : $value;
        }
        try {
            if (null !== request()->old($name)) {
                $value = request()->old($name);
            }
        } catch (RuntimeException $e) {
            // don't care about session errors.
        }
        if ($value instanceof Carbon) {
            $value = $value->format('Y-m-d');
        }

        return $value;
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function getHolderClasses(string $name): string
    {
        // Get errors from session:
        /** @var MessageBag $errors */
        $errors  = session('errors');
        $classes = 'form-group';

        if (null !== $errors && $errors->has($name)) {
            $classes = 'form-group has-error has-feedback';
        }

        return $classes;
    }

    /**
     * @param $name
     * @param $options
     *
     * @return mixed
     */
    protected function label(string $name, array $options): string
    {
        if (isset($options['label'])) {
            return $options['label'];
        }
        $name = str_replace('[]', '', $name);

        return strval(trans('form.' . $name));
    }

    /**
     * @param string $name
     * @param string $view
     * @param null   $value
     * @param array  $options
     *
     * @return string
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    private function currencyField(string $name, string $view, $value = null, array $options = []): string
    {
        $label           = $this->label($name, $options);
        $options         = $this->expandOptionArray($name, $label, $options);
        $classes         = $this->getHolderClasses($name);
        $value           = $this->fillFieldValue($name, $value);
        $options['step'] = 'any';
        $defaultCurrency = isset($options['currency']) ? $options['currency'] : Amt::getDefaultCurrency();
        $currencies      = app('amount')->getAllCurrencies();
        unset($options['currency']);
        unset($options['placeholder']);

        // perhaps the currency has been sent to us in the field $amount_currency_id_$name (amount_currency_id_amount)
        $preFilled      = session('preFilled');
        $key            = 'amount_currency_id_' . $name;
        $sentCurrencyId = isset($preFilled[$key]) ? intval($preFilled[$key]) : $defaultCurrency->id;

        // find this currency in set of currencies:
        foreach ($currencies as $currency) {
            if ($currency->id === $sentCurrencyId) {
                $defaultCurrency = $currency;
                break;
            }
        }

        // make sure value is formatted nicely:
        if (null !== $value && '' !== $value) {
            $value = round($value, $defaultCurrency->decimal_places);
        }

        $html = view('form.' . $view, compact('defaultCurrency', 'currencies', 'classes', 'name', 'label', 'value', 'options'))->render();

        return $html;
    }
}
