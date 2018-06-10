<?php


namespace Application\Repricer;

use Application\Repricer;

return [
    'repriceRuleList' => [
        Repricer::RR_NO_CHANGE => 'No Change',
        Repricer::RR_BEAT_TROLL_CHEAP => 'Beat Troll, fixed 10 cents',
        Repricer::RR_BEAT_TROLL_EXPENSIVE => 'Beat Troll one percent',
        Repricer::RR_MATCH_CHEAPEST_EVO => 'Match Cheapest EVO',
        Repricer::RR_SET_TO_SELLERY_PRICE => 'Repriced to match Sellery',
        Repricer::RR_TROLL_BUY_PRICE_LOWER_BOUND => 'Raise price to be more than Troll Buy price plus percentage',
        Repricer::RR_SELL_PRICE_FLOOR => 'Item price set to minimum price',
        Repricer::RR_EXCEPTION_SELL_PRICE_FLOOR => 'Existing Item Price below minimum price. No Change',
        Repricer::RR_HOLD_ALL => 'Hold all inventory. No Price Change',
        Repricer::RR_RELEASE_FROM_HOLD_PRICE_UP => 'Quantity on site sold out, release some, price up',
        Repricer::RR_OOS_TRY_PRICE_DOWN_NO_CHANGE => 'Tried to Price down while out of stock.  No Change made',
    ],

];