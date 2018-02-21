<?php

class BookingCalendarField extends CalendarField 
{
    protected $product;

    protected $options = [
        'day_format' => 'D',
        'month_format' => 'M',
        'year_format' => 'Y',
        'allow_past_dates' => false,
        'future_limit' => 10,
        'past_limit' => 0,
        'days_count' => 0,
        'StartName' => 'StartDate',
        'EndName' => 'EndDate',
        'useEndField' => true
    ];

    public function getProduct()
    {
        return $this->product;
    }

    public function setProduct($product) 
    {
        $this->product = $product;

        return $this;
    }

    /**
	 * Create a new file field.
	 *
	 * @param string $name The internal field name, passed to forms.
	 * @param string $title The field label.
	 * @param int $value The value of the field.
	 */
	public function __construct($name, $title = null, $value = null,$product) {
        $this->product = $product;
        
		parent::__construct($name, $title, $value);
    }

    public function getCalendarDays($month,$year)
    {
        $today = new Date();
        $today->setValue(date("Y-m-d H:i:s"));
        /* days in month */
        $days = parent::getCalendarDays($month,$year);

        $product = $this->getProduct();
        
        if ($product) {
            foreach ($days as $day) {
                $spaces = $product->AvailablePlaces - $product->getBookedPlaces($day->Date->format("Y-m-d 00:00:00"), $day->Date->format("Y-m-d 23:59:59"));
                if (
                    ($spaces > 0 && $day->Date->format("Y-m-d H:i:s") > $today->format("Y-m-d H:i:s")) 
                    && !in_array($day->Date->format("Y-m-d"),$this->disabled_dates)
                ) {
                    $day->Availability = 'available';
                    $day->Spaces = $spaces;
                } else {
                    $day->Availability = 'not-available'; 
                    $day->Spaces = 0;                   
                }
            }
        }

        return $days;
    }
}