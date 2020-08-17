<?php

namespace ilateral\SimpleBookings\Products;

use Exception;
use ProductController;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\ORM\ValidationResult;
use ilateral\SimpleBookings\Products\EventProduct;
use SilverCommerce\ShoppingCart\Forms\AddToCartForm;
use SilverCommerce\ShoppingCart\ShoppingCartFactory;
use SilverCommerce\OrdersAdmin\Factory\LineItemFactory;

class EventProductController extends ProductController
{
    private static $allowed_actions = [
        'AddToCartForm'
    ];

    /**
     * Customised AddToCartForm that allows selecting the date of the event
     * as a radio set
     *
     * @return AddToCartForm
     */
    public function AddToCartForm()
    {
        $form = AddToCartForm::create(
            $this,
            "AddToCartForm"
        );
        /** @var EventProduct */
        $object = $this->dataRecord;

        $form
            ->setProductClass(EventProduct::class)
            ->setProductID($object->ID);
        
        $fields = $form->Fields();
        $fields->insertBefore(
            'Quantity',
            OptionsetField::create(
                'Date',
                _t(__CLASS__ . '.SelectDate', 'Select a date')
            )->setSource($object->getCurrentDates()->map())
            ->setDisabledItems($this->getDisabledDateIDs())
            ->setForm($form)
        );

        /** @var \SilverStripe\Forms\RequiredFields */
        $validator = $form->getValidator();
        $validator->addRequiredField('Date');

        return $form;
    }

    public function doAddItemToCart($data, $form)
    {
        $classname = $data["ClassName"];
        $id = $data["ID"];
        /** var EventProduct */
        $object = $classname::get()->byID($id);
        $cart = ShoppingCartFactory::create();
        $customisations = [];
        $date = $object->getCurrentDates()->byID($data['Date']);

        if (!empty($object) && !empty($date)) {
            // Manually create a line item and add to cart, return any exceptions raised as a message
            try {
                $customisations[] = [
                    "Title" => _t(EventProduct::class . ".Date", 'Date'),
                    "Value" => $date->Title,
                    "BasePrice" => 0
                ];

                $factory = LineItemFactory::create()
                    ->setProduct($object)
                    ->setQuantity($data['Quantity'])
                    ->setCustomisations($customisations)
                    ->makeItem()
                    ->write();

                // Now get and update the assotiated booking data
                $item = $factory->getItem();

                /** @var \ilateral\SimpleBookings\Model\Booking */
                $booking = $item->Booking();
                $booking->Start = $date->Start;
                $booking->End = $date->End;
                $booking->write();

                $cart->addFromLineItemFactory($factory);
                $cart->save();

                $message = _t(
                    'ShoppingCart.AddedItemToCart',
                    'Added "{item}" to your shopping cart',
                    ["item" => $object->Title]
                );

                $form->sessionMessage(
                    $message,
                    ValidationResult::TYPE_GOOD
                );
            } catch (Exception $e) {
                $form->sessionMessage(
                    $e->getMessage()
                );
            }
        } else {
            $form->sessionMessage(
                _t("ShoppingCart.ErrorAddingToCart", "Error adding item to cart")
            );
        }

        return $this->redirectBack();
    }
}
