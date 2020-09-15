<?php

namespace ilateral\SimpleBookings\Forms;

use SilverStripe\View\ArrayData;
use SilverStripe\Forms\OptionsetField;

class DateOptionsField extends OptionsetField
{
    /**
     * Build a field option for template rendering
     *
     * @param mixed $value Value of the option
     * @param string $title Title of the option
     * @param boolean $odd True if this should be striped odd. Otherwise it should be striped even
     * @return ArrayData Field option
     */
    protected function getFieldOption($value, $title, $odd)
    {
        if ($this->isDisabledValue($value)) {
            $title = _t("Bookings.FullyBooked", "{$title} - Fully Booked", ['title' => $title]);
        }

        return new ArrayData([
            'ID' => $this->getOptionID($value),
            'Class' => $this->getOptionClass($value, $odd),
            'Role' => 'option',
            'Name' => $this->getOptionName(),
            'Value' => $value,
            'Title' => $title,
            'isChecked' => $this->isSelectedValue($value, $this->Value()),
            'isDisabled' => $this->isDisabledValue($value)
        ]);
    }
}