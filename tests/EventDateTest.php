<?php

namespace ilateral\SimpleBookings\Tests;

use ilateral\SimpleBookings\Model\EventDate;
use ilateral\SimpleBookings\Products\EventProduct;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;

class EventDateTest extends SapphireTest
{
    /**
     * Defines the fixture file to use for this test class
     *
     */
    protected static $fixture_file = 'Events.yml';

    public function testIsPast()
    {
        /** @var EventDate */
        $one = $this->objFromFixture(EventDate::class, 'workshop_one_one');

        DBDatetime::set_mock_now('2020-06-16 10:00:00');
        $this->assertEquals(true, $one->isPast());

        DBDatetime::set_mock_now('2020-06-18 10:00:00');
        $this->assertEquals(true, $one->isPast());

        DBDatetime::set_mock_now('2020-06-14 10:00:00');
        $this->assertEquals(false, $one->isPast());
    }

    public function testGetBookedSpaces()
    {
        $date = $this->objFromFixture(EventDate::class, 'workshop_one_one');
        $this->assertEquals(9, $date->getBookedSpaces());

        $date = $this->objFromFixture(EventDate::class, 'workshop_one_two');
        $this->assertEquals(6, $date->getBookedSpaces());

        $date = $this->objFromFixture(EventDate::class, 'workshop_one_three');
        $this->assertEquals(6, $date->getBookedSpaces());

        $date = $this->objFromFixture(EventDate::class, 'workshop_two_one');
        $this->assertEquals(0, $date->getBookedSpaces());

        $date = $this->objFromFixture(EventDate::class, 'workshop_two_two');
        $this->assertEquals(0, $date->getBookedSpaces());

        $date = $this->objFromFixture(EventDate::class, 'workshop_two_three');
        $this->assertEquals(4, $date->getBookedSpaces());
    }
}
