<?php
/**
 * RabobankDebitCreditTest.php
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

namespace Tests\Unit\Import\Converter;

use FireflyIII\Import\Converter\RabobankDebitCredit;
use Log;
use Tests\TestCase;

/**
 * Class RabobankDebitCredit
 */
class RabobankDebitCreditTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }


    /**
     * @covers \FireflyIII\Import\Converter\RabobankDebitCredit
     */
    public function testConvertAnything(): void
    {
        $converter = new RabobankDebitCredit;
        $result    = $converter->convert('9083jkdkj');
        $this->assertEquals(1, $result);
    }

    /**
     * @covers \FireflyIII\Import\Converter\RabobankDebitCredit
     */
    public function testConvertCredit(): void
    {
        $converter = new RabobankDebitCredit;
        $result    = $converter->convert('C');
        $this->assertEquals(1, $result);
    }

    /**
     * @covers \FireflyIII\Import\Converter\RabobankDebitCredit
     */
    public function testConvertCreditOld(): void
    {
        $converter = new RabobankDebitCredit;
        $result    = $converter->convert('B');
        $this->assertEquals(1, $result);
    }

    /**
     * @covers \FireflyIII\Import\Converter\RabobankDebitCredit
     */
    public function testConvertDebit(): void
    {
        $converter = new RabobankDebitCredit;
        $result    = $converter->convert('D');
        $this->assertEquals(-1, $result);
    }

    /**
     * @covers \FireflyIII\Import\Converter\RabobankDebitCredit
     */
    public function testConvertDebitOld(): void
    {
        $converter = new RabobankDebitCredit;
        $result    = $converter->convert('A');
        $this->assertEquals(-1, $result);
    }
}
