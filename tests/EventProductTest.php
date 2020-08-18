<?php

namespace ilateral\SimpleBookings\Tests;

use ilateral\SimpleBookings\Products\EventProduct;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * Unit tests for the simple bookings functionality
 *
 * @author Mo <morven@ilateral.co.uk>
 * @package simplebookings
 * @subpackage tests
 */
class EventProductTest extends SapphireTest
{

    /**
     * Defines the fixture file to use for this test class
     *
     */
    protected static $fixture_file = 'Events.yml';

    /**
     * Test if the booked spaces algorythm returns the
     * correct results when we have a booking that starts
     * before but ends inside an existing booking
     *
     * @return void
     */
    public function testGetCurrentDates()
    {
        /** @var EventProduct */
        $workshop_one = $this->objFromFixture(EventProduct::class, 'workshop_one');
        /** @var EventProduct */
        $workshop_two = $this->objFromFixture(EventProduct::class, 'workshop_two');

        // Change the default date (auto cleared in tear down)
        DBDatetime::set_mock_now('2020-06-01 10:00:00');

        $this->assertEquals(2, $workshop_one->getCurrentDates()->count());
        $this->assertEquals(3, $workshop_two->getCurrentDates()->count());

        // Change the default date (auto cleared in tear down)
        DBDatetime::set_mock_now('2020-07-01 10:00:00');

        $this->assertEquals(1, $workshop_one->getCurrentDates()->count());
        $this->assertEquals(2, $workshop_two->getCurrentDates()->count());
    }

    public function testGetPossibleSpaces()
    {
        /** @var EventProduct */
        $workshop_one = $this->objFromFixture(EventProduct::class, 'workshop_one');

        $this->assertEquals(10, $workshop_one->getPossibleSpaces('2020-06-16 10:00:00', '2020-06-16 17:00:00'));
        $this->assertEquals(15, $workshop_one->getPossibleSpaces('2020-07-16 10:00:00', '2020-07-16 17:00:00'));
        $this->assertEquals(20, $workshop_one->getPossibleSpaces('2020-05-16 10:00:00', '2020-05-16 17:00:00'));
        $this->assertEquals(0, $workshop_one->getPossibleSpaces('2020-04-16 10:00:00', '2020-04-16 17:00:00'));
        $this->assertEquals(0, $workshop_one->getPossibleSpaces('2020-08-16 10:00:00', '2020-08-16 17:00:00'));

        /** @var EventProduct */
        $workshop_two = $this->objFromFixture(EventProduct::class, 'workshop_two');

        $this->assertEquals(22, $workshop_two->getPossibleSpaces('2020-06-16 10:00:00', '2020-06-16 17:00:00'));
        $this->assertEquals(18, $workshop_two->getPossibleSpaces('2020-07-16 10:00:00', '2020-07-16 17:00:00'));
        $this->assertEquals(12, $workshop_two->getPossibleSpaces('2020-08-16 10:00:00', '2020-08-16 17:00:00'));
        $this->assertEquals(0, $workshop_two->getPossibleSpaces('2020-04-16 10:00:00', '2020-04-16 17:00:00'));
        $this->assertEquals(0, $workshop_two->getPossibleSpaces('2020-09-16 10:00:00', '2020-09-16 17:00:00'));
    }

    public function testGetBookedSpaces()
    {
        /** @var EventProduct */
        $workshop_one = $this->objFromFixture(EventProduct::class, 'workshop_one');

        $this->assertEquals(9, $workshop_one->getBookedSpaces('2020-06-16 10:00:00', '2020-06-16 17:00:00'));
        $this->assertEquals(6, $workshop_one->getBookedSpaces('2020-07-16 10:00:00', '2020-07-16 17:00:00'));
        $this->assertEquals(6, $workshop_one->getBookedSpaces('2020-05-16 10:00:00', '2020-05-16 17:00:00'));
        $this->assertEquals(0, $workshop_one->getBookedSpaces('2020-04-16 10:00:00', '2020-04-16 17:00:00'));
        $this->assertEquals(0, $workshop_one->getBookedSpaces('2020-08-16 10:00:00', '2020-08-16 17:00:00'));

        /** @var EventProduct */
        $workshop_two = $this->objFromFixture(EventProduct::class, 'workshop_two');

        $this->assertEquals(4, $workshop_two->getBookedSpaces('2020-06-16 10:00:00', '2020-06-16 17:00:00'));
        $this->assertEquals(0, $workshop_two->getBookedSpaces('2020-07-16 10:00:00', '2020-07-16 17:00:00'));
        $this->assertEquals(0, $workshop_two->getBookedSpaces('2020-08-16 10:00:00', '2020-08-16 17:00:00'));
        $this->assertEquals(0, $workshop_two->getBookedSpaces('2020-04-16 10:00:00', '2020-04-16 17:00:00'));
        $this->assertEquals(0, $workshop_two->getBookedSpaces('2020-09-16 10:00:00', '2020-09-16 17:00:00'));
    }

    public function testGetRemainingSpaces()
    {
        /** @var EventProduct */
        $workshop_one = $this->objFromFixture(EventProduct::class, 'workshop_one');

        $this->assertEquals(1, $workshop_one->getRemainingSpaces('2020-06-16 10:00:00', '2020-06-16 17:00:00'));
        $this->assertEquals(9, $workshop_one->getRemainingSpaces('2020-07-16 10:00:00', '2020-07-16 17:00:00'));
        $this->assertEquals(14, $workshop_one->getRemainingSpaces('2020-05-16 10:00:00', '2020-05-16 17:00:00'));
        $this->assertEquals(0, $workshop_one->getRemainingSpaces('2020-04-16 10:00:00', '2020-04-16 17:00:00'));
        $this->assertEquals(0, $workshop_one->getRemainingSpaces('2020-08-16 10:00:00', '2020-08-16 17:00:00'));

        /** @var EventProduct */
        $workshop_two = $this->objFromFixture(EventProduct::class, 'workshop_two');

        $this->assertEquals(18, $workshop_two->getRemainingSpaces('2020-06-16 10:00:00', '2020-06-16 17:00:00'));
        $this->assertEquals(18, $workshop_two->getRemainingSpaces('2020-07-16 10:00:00', '2020-07-16 17:00:00'));
        $this->assertEquals(12, $workshop_two->getRemainingSpaces('2020-08-16 10:00:00', '2020-08-16 17:00:00'));
        $this->assertEquals(0, $workshop_two->getRemainingSpaces('2020-04-16 10:00:00', '2020-04-16 17:00:00'));
        $this->assertEquals(0, $workshop_two->getRemainingSpaces('2020-09-16 10:00:00', '2020-09-16 17:00:00'));
    }

    public function testCanBookSpaces()
    {
        /** @var EventProduct */
        $workshop_one = $this->objFromFixture(EventProduct::class, 'workshop_one');

        $this->assertTrue($workshop_one->canBookSpaces(1, '2020-06-16 10:00:00', '2020-06-16 17:00:00'));
        $this->assertFalse($workshop_one->canBookSpaces(2, '2020-06-16 10:00:00', '2020-06-16 17:00:00'));
        $this->assertFalse($workshop_one->canBookSpaces(5, '2020-06-16 10:00:00', '2020-06-16 17:00:00'));

        $this->assertTrue($workshop_one->canBookSpaces(1, '2020-07-16 10:00:00', '2020-07-16 17:00:00'));
        $this->assertTrue($workshop_one->canBookSpaces(5, '2020-07-16 10:00:00', '2020-07-16 17:00:00'));
        $this->assertTrue($workshop_one->canBookSpaces(9, '2020-07-16 10:00:00', '2020-07-16 17:00:00'));
        $this->assertFalse($workshop_one->canBookSpaces(10, '2020-07-16 10:00:00', '2020-07-16 17:00:00'));
        $this->assertFalse($workshop_one->canBookSpaces(15, '2020-07-16 10:00:00', '2020-07-16 17:00:00'));

        $this->assertTrue($workshop_one->canBookSpaces(1, '2020-05-16 10:00:00', '2020-05-16 17:00:00'));
        $this->assertTrue($workshop_one->canBookSpaces(7, '2020-05-16 10:00:00', '2020-05-16 17:00:00'));
        $this->assertTrue($workshop_one->canBookSpaces(10, '2020-05-16 10:00:00', '2020-05-16 17:00:00'));
        $this->assertTrue($workshop_one->canBookSpaces(14, '2020-05-16 10:00:00', '2020-05-16 17:00:00'));
        $this->assertFalse($workshop_one->canBookSpaces(15, '2020-05-16 10:00:00', '2020-05-16 17:00:00'));
        $this->assertFalse($workshop_one->canBookSpaces(20, '2020-05-16 10:00:00', '2020-05-16 17:00:00'));

        $this->assertFalse($workshop_one->canBookSpaces(1, '2020-04-16 10:00:00', '2020-04-16 17:00:00'));
        $this->assertFalse($workshop_one->canBookSpaces(5, '2020-04-16 10:00:00', '2020-04-16 17:00:00'));
        $this->assertFalse($workshop_one->canBookSpaces(10, '2020-04-16 10:00:00', '2020-04-16 17:00:00'));
    }
}
