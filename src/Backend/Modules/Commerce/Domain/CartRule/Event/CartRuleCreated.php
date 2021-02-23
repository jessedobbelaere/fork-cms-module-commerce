<?php

namespace Backend\Modules\Commerce\Domain\CartRule\Event;

final class CartRuleCreated extends Event
{
    /**
     * @var string The name the listener needs to listen to to catch this event.
     */
    const EVENT_NAME = 'commerce.event.cart_rule.created';
}