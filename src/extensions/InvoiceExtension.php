<?php

namespace ilateral\SimpleBookings\Extensions;

use ilateral\SimpleBookings\Model\Booking;
use ilateral\SimpleBookings\Products\BookableProduct;
use ilateral\SimpleBookings\Products\EventProduct;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;

class InvoiceExtension extends DataExtension
{
    /**
     * Perform booking functions when the invoice is saved
     */
    public function onBeforeWrite()
    {
        /**
         * If this invoice needs to link to a booking, create one now and link
         * @var \SilverCommerce\OrdersAdmin\Model\Invoice
         */
        $owner = $this->getOwner();
    
        // If this invoice has a booking and is paid, mark all linked bookings as confirmed
        if ($owner->isChanged('Status') && $owner->isPaid()) {
            $bookings = Booking::get()->filter(
                'ItemID',
                $owner->Items()->column('ID')
            );
            foreach ($bookings as $booking) {
                $booking->markConfirmed();
                $booking->write();
            }
        }
    }
}
