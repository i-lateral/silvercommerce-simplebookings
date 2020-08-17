# SilverCommerce Simple Bookings Module

Adds a simple booking system to the SilverCommerce system, allowing users
to add the following products to the shopping cart:

"Bookable Products" - A product that can be booked for a flexible period or time and is charged baded on duration

"Event Products" - A product that can be booked for pre-defined date ranges for a fixed price

Upon completion of the transaction, a booking is automatically created in the admin.

The module also checks to ensure that more items are not booked than is allowed.

## Author

This module is created and maintained by [ilateral](http://ilateralweb.co.uk)

## Dependancies

* SilverStripe Framework 4.x
* SilverCommerce 1.x

## Installation

Install via Composer:

`i-lateral/silvercommerce-simplebookings`

## Usage

Once installed, visit the Catalogue in the site admin.

Add new "Bookable Product" and fill in it's details, as you would a
regular product.

Or, add an "Event Product" and pre-select it's dates.