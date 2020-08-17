<?php

namespace ilateral\SimpleBookings\Model;

use DateTime;
use ilateral\SimpleBookings\Helpers\BookingHelper;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use ilateral\SimpleBookings\Products\EventProduct;
use SilverStripe\Forms\ReadonlyField;

class EventDate extends DataObject
{
    private static $table_name = "SimpleBookings_EventDate";

    private static $db = [
        'Start' => 'Datetime',
        'End'   => 'Datetime',
        'Spaces' => 'Int'
    ];

    private static $has_one = [
        'Event' => EventProduct::class
    ];

    private static $casting = [
        'Title' => 'Varchar',
        'BookedSpaces' => 'Int',
        'RemainingSpaces' => 'Int'
    ];

    private static $summary_fields = [
        'Start.Nice',
        'End.Nice',
        'Spaces',
        'BookedSpaces'
    ];

    private static $field_labels = [
        'Time' => 'Time of event',
        'Start.Nice' => 'Start',
        'End.Nice' => 'End',
        'Spaces' => 'Number of available spaces'
    ];

    private static $default_sort = 'Start ASC';

    /**
     * @return string
     */
    public function getTitle()
    {
        $date = $this->dbObject('Start')->Nice();

        $this->extend('updateTitle', $date);

        return $date;
    }

    /**
     * Is this EventDate past (at the moment only checks if the start date
     * has passed current date?
     *
     * @return bool
     */
    public function isPast()
    {
        // Use DBDatetime for unit testing support
        $now = new DateTime(DBDatetime::now()->format(DBDatetime::ISO_DATETIME));
        $date = new DateTime($this->Start);

        return ($date <= $now);
    }

    /**
     * Find the total number of booked spaces for this event/date
     *
     * @return int
     */
    public function getBookedSpaces()
    {
        $helper = BookingHelper::create($this->Start, $this->End, $this->Event());
        return $helper->getTotalBookedSpaces();
    }

    /**
     * How many places have are remaining (to be booked)?
     * (A negative number would mean overbooked)
     *
     * @return int
     */
    public function getRemainingSpaces()
    {
        return $this->Spaces - $this->BookedSpaces;
    }

    /**
     * Update CMS fields prior to any extensions running
     *
     * @return \SilverStripe\Forms\FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function ($fields) {
            $fields
                ->dataFieldByName('Time')
                ->setDescription(_t(__CLASS__ . ".TimeDescription", 'Used for display purposes only'));

            $fields->addFieldToTab(
                'Root.Main',
                ReadonlyField::create('BookedSpaces')
            );
        });

        return parent::getCMSFields();
    }
}
