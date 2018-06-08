<?php

declare(strict_types=1);

namespace Tests\ThreadParser;

use phpClub\ThreadParser\DateConverter;
use phpClub\ThreadParser\Exception\DateParseException;
use PHPUnit\Framework\TestCase;

class DateConverterTest extends TestCase
{
    /**
     * @var DateConverter
     */
    private $dateConverter;

    public function setUp()
    {
        $this->dateConverter = new DateConverter(new \DateTimeZone('UTC'));
    }

    public function testGetDateTime()
    {
        $dateTime = $this->dateConverter->toDateTime('Срд 30 Янв 2013 13:05:52');
        $this->assertEquals('30 01 2013 13:05:52', $dateTime->format('d m Y H:i:s'));

        $dateTime = $this->dateConverter->toDateTime('19/04/17 Срд 15:21:43');
        $this->assertEquals('19 04 2017 15:21:43', $dateTime->format('d m Y H:i:s'));
    }

    /**
     * @dataProvider provideInvalidDates
     */
    public function testInvalidArgument($invalidDate)
    {
        $this->expectException(DateParseException::class);
        $this->dateConverter->toDateTime($invalidDate);
    }

    public function provideInvalidDates()
    {
        return [
            ['Срд 2013 18:05:52'],
            [' '],
            [''],
        ];
    }

    public function testMDvachDateParser()
    {
        $dateTime = $this->dateConverter->parseMDvachDate('02 Май, 19:34', 2013);
        $this->assertEquals('2013-05-02 19:34', $dateTime->format('Y-m-d H:i'));
    }

    public function testMDvachInvalidDate()
    {
        $this->expectException(DateParseException::class);
        $dateTime = $this->dateConverter->parseMDvachDate('absolutely invalid date', 2013);
    }
}
