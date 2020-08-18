<?php

namespace ilateral\SimpleBookings\Forms\GridField;

use ilateral\SimpleBookings\Model\Booking;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\View\HTML;

class BookingDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest
{

    private static $allowed_actions = [
        'edit',
        'view',
        'ItemEditForm',
    ];

    public function ItemEditForm()
    {
        $form = parent::ItemEditForm();
        $form->addExtraClass("cms-booking-form");
        $record = $this->record;

        if ($form && is_a($record, Booking::class) && $record->ID !== 0 && $record->canEdit()) {
            $actions = $form->Actions();

            // Add right aligned total field
            $total = $record->obj("TotalCost");
            $total_html = HTML::createTag(
                'a',
                [
                    'class' => 'cms-booking-total btn btn-outline-info justify-content-end ml-auto',
                    'href' => $record->CMSInvoiceLink()
                ],
                _t(
                    __CLASS__ . ".BookingValue",
                    "Booking Value: {total}",
                    ['total' => $total->Nice()]
                )
            );

            $actions->push(
                LiteralField::create(
                    "TotalCost",
                    $total_html
                )
            );
        }
        
        $this->extend("updateItemEditForm", $form);
        
        return $form;
    }
}
