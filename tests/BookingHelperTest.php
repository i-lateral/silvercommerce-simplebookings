<?php

namespace ilateral\SimpleBookings\Tests\;

use ilateral\SimpleBookings\Helpers\BookingHelper;
use ilateral\SimpleBookings\Products\EventProduct;
use SilverStripe\Dev\SapphireTest;

class BookingHelperTest extends SapphireTest
{

    /**
     * Defines the fixture file to use for this test class
     *
     */
    protected static $fixture_file = 'BookingHelperTest.yml';

    public function testGetBookings()
    {
        $product = $this->objFromFixture(EventProduct::class, 'workshop_one');
        $helper = BookingHelper::create('2020-05-15 00:00:00', '2020-07-17 00:00:00', $product);

        // All dates that should apply
        $this->assertEquals(6, $helper->getBookings()->count());

        // Dates in the past (no bookings)
        $helper->setStartDate('2020-05-01 00:00:00');
        $helper->setEndDate('2020-05-15 00:00:00');
        $this->assertEquals(0, $helper->getBookings()->count());

        // Dates in the future (no bookings)
        $helper->setStartDate('2020-07-18 00:00:00');
        $helper->setEndDate('2020-10-15 00:00:00');
        $this->assertEquals(0, $helper->getBookings()->count());

        // Bookings in the 6th month
        $helper->setStartDate('2020-06-15 00:00:00');
        $helper->setEndDate('2020-06-31 00:00:00');
        $this->assertEquals(3, $helper->getBookings()->count());

        // Bookings in the 7th month
        $helper->setStartDate('2020-07-01 00:00:00');
        $helper->setEndDate('2020-07-31 00:00:00');
        $this->assertEquals(2, $helper->getBookings()->count());
    }

    public function getTotalBookedSpaces()
    {
        $one = $this->objFromFixture(EventProduct::class, 'workshop_one');
        $helper = BookingHelper::create('2020-05-15 00:00:00', '2020-07-17 00:00:00', $one);
        $this->assertEquals(21, $helper->getTotalBookedSpaces());

        $helper->setStartDate('2020-05-01 00:00:00');
        $helper->setEndDate('2020-05-15 00:00:00');
        $this->assertEquals(6, $helper->getTotalBookedSpaces());

        $helper->setStartDate('2020-07-18 00:00:00');
        $helper->setEndDate('2020-10-15 00:00:00');
        $this->assertEquals(0, $helper->getTotalBookedSpaces());

        $helper->setStartDate('2020-06-16 00:00:01');
        $helper->setEndDate('2020-06-16 00:00:00');
        $this->assertEquals(9, $helper->getTotalBookedSpaces());

        $helper->setStartDate('2020-07-01 00:00:00');
        $helper->setEndDate('2020-07-31 00:00:00');
        $this->assertEquals(6, $helper->getTotalBookedSpaces());

        $two = $this->objFromFixture(EventProduct::class, 'workshop_two');
        $helper = BookingHelper::create('2020-05-15 00:00:00', '2020-07-17 00:00:00', $two);
        $this->assertEquals(4, $helper->getTotalBookedSpaces());

        $helper->setStartDate('2020-05-01 00:00:00');
        $helper->setEndDate('2020-05-15 00:00:00');
        $this->assertEquals(0, $helper->getTotalBookedSpaces());

        $helper->setStartDate('2020-07-18 00:00:00');
        $helper->setEndDate('2020-10-15 00:00:00');
        $this->assertEquals(0, $helper->getTotalBookedSpaces());
    }
}
