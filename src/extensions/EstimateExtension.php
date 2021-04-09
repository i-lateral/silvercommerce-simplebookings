<?php

namespace ilateral\SimpleBookings\Extensions;

use ilateral\SimpleBookings\Model\Booking;
use SilverCommerce\OrdersAdmin\Model\Invoice;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;

class EstimateExtension extends DataExtension
{
    /**
     * Get a list of bookings from the current estimate (based on the attached products)
     *
     * @return DataList
     */
    public function getBookings()
    {
        /**
         * @var \SilverCommerce\OrdersAdmin\Model\Estimate
         */
        $owner = $this->getOwner();
        $item_ids = $owner->Items()->column('ID');

        if (count($item_ids) == 0) {
            return ArrayList::create();
        }

        return Booking::get()->filter(
            'ItemID',
            $item_ids
        );
    }

    /**
     * Perform booking functions when the invoice is saved
     */
    public function onBeforeWrite()
    {
        /**
         * @var \SilverCommerce\OrdersAdmin\Model\Estimate
         */
        $owner = $this->getOwner();
        $bookings = $owner->getBookings();

        foreach ($bookings as $booking) {
            $write = false;

            // If the current booking is not assigned to this customer, update it
            if ($owner->Customer()->exists() && $owner->CustomerID != $booking->CustomerID) {
                $booking->CustomerID = $owner->CustomerID;
                $write = true;
            }

            // If this invoice has a booking and is paid, mark all linked bookings as confirmed
            if (is_a($owner, Invoice::class) && $owner->isChanged('Status') && $owner->isPaid()) {
                $booking->markConfirmed();
                $write = true;
            }

            if ($write) {
                $booking->write();
            }
        }
    }
}
