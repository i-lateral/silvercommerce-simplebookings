<?php

namespace ilateral\SimpleBookings\Forms;

use SilverStripe\Forms\OptionsetField;

class DateOptionsField extends OptionsetField
{
    /**
     * Extra CSS classes for the FormField container.
     *
     * @var array
     */
    protected $extraClasses = [
        'field',
        'form-group',
        'optionset'
    ];
}