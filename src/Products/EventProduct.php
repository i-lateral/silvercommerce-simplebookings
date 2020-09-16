<?php

namespace ilateral\SimpleBookings\Products;

use ilateral\SimpleBookings\Helpers\BookingHelper;
use ilateral\SimpleBookings\Interfaces\Bookable;
use Product;
use ilateral\SimpleBookings\Model\EventDate;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * Events are simple bookable products that will usually only be run over a single day, or maybe several days but
 * have a fairly simple "start" date.
 *
 * By default, when adding an event product to the cart, it will add an item with a range of midnight the current day
 * to midnight the following day.
 */
class EventProduct extends Product implements Bookable
{
    private static $table_name = 'SimpleBookings_EventProduct';

    private static $singular_name = 'Event';

    private static $plural_name = 'Events';

    private static $description = "A one off event that can be bookeed across multiple dates";

    private static $db = [
        'DeliverTicket' => 'Boolean'
    ];

    private static $has_many = [
        'Dates' => EventDate::class
    ];

    private static $defaults = [
        'DeliverTicket' => false
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab(
            'Root.Settings',
            CheckboxField::create(
                'DeliverTicket',
                "Does a 'ticket' or similar item need to be delivered?"
            )
        );

        return $fields;
    }

    /**
     * Overwrite default stock checking so we can overwrite with custom checks
     * in @link \ilateral\SimpleBookings\Extensions\LineItemExtension
     *
     * @return int
     */
    public function getStockLevel()
    {
        return 9999;
    }

    /**
     * Return dated, filtering out any that have passed
     *
     * @return \SilverStripe\ORM\HasManyList
     */
    public function getCurrentDates()
    {
        $now = DBDatetime::now();
        return $this->Dates()->exclude('Start:LessThanOrEqual', $now->Format(DBDatetime::ISO_DATETIME));
    }

    /**
     * Get the total number of spaces allowed within a date range
     *
     * @param string $start
     * @param string $end
     *
     * @return int
     */
    public function getPossibleSpaces(string $start, string $end)
    {
        $helper = BookingHelper::create($start, $end, $this);
        $dates = $this->Dates()->where($helper->getWhereFilter());
        $date = $dates->first();

        if (!empty($date)) {
            return $date->Spaces;
        }

        return 0;
    }

    /**
     * Get number of booked spaces within a date range
     *
     * NOTE: This method uses BookingHelper direct (rather than first getting
     * a relevent date, in an attempt to run more effitiently)
     *
     * @param string $start
     * @param string $end
     *
     * @return int
     */
    public function getBookedSpaces(string $start, string $end)
    {
        $helper = BookingHelper::create($start, $end, $this);
        return $helper->getTotalBookedSpaces();
    }

    /**
     * Get the total number of spaces allowed within a date range
     *
     * @param string $start
     * @param string $end
     *
     * @return int
     */
    public function getRemainingSpaces(string $start, string $end)
    {
        $helper = BookingHelper::create($start, $end, $this);
        $dates = $this->Dates()->where($helper->getWhereFilter());
        /** @var EventDate */
        $date = $dates->first();

        if (!empty($date)) {
            return $date->getRemainingSpaces();
        }

        return 0;
    }

    /**
     * Can the provided number of spaces be booked within this date range
     *
     * @param int    $quantity
     * @param string $start
     * @param string $end
     *
     * @return bool
     */
    public function canBookSpaces(int $quantity, string $start, string $end)
    {
        $possible = $this->getRemainingSpaces($start, $end);
        $remaining = $possible - $quantity;

        return !($remaining < 0);
    }

    /**
     * Get a list of disabled dates for this
     */
    public function getDisabledDateIDs()
    {
        $disabled = [];

        foreach ($this->Dates() as $date) {
            // If in past, disable and move to next
            if ($date->isPast() || $date->RemainingSpaces <= 0) {
                $disabled[] = $date->ID;
            }
        }

        return $disabled;
    }
}
