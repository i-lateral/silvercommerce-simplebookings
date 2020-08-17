<?php

namespace ilateral\SimpleBookings\Forms\GridField;

use ilateral\SimpleBookings\Model\Booking;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;

class BookingDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest
{

    private static $allowed_actions = array(
        'edit',
        'view',
        'ItemEditForm',
    );

    public function ItemEditForm()
    {
        $form = parent::ItemEditForm();
        $form->addExtraClass("cms-booking-form");
        $record = $this->record;

        if ($form && is_a($record, Booking::class) && $record->ID !== 0 && $record->canEdit()) {
            $actions = $form->Actions();

            // Add right aligned total field
            $total = $record->obj("TotalCost");
            $total_html = '<span class="cms-booking-total ui-corner-all ui-button-text-only">';
            $total_html .= "<strong>Total:</strong> {$total->Nice()}";
            $total_html .= '</span>';

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