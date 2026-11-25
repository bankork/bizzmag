<?php

/**
 * Demo catalogue with plausible Lagos mid-range prices (late 2026).
 * Prices are naira strings, editable by the owner once the catalogue
 * screens exist (Phase 2). Allocation defaults follow PRD-7.5-3.
 *
 * Allocation: 'equal' or ['primary_assistant', primary share in basis points].
 */
return [
    'services' => [
        'Braids' => [
            ['name' => 'Knotless braids', 'allocation' => ['primary_assistant', 6000], 'variants' => ['Small' => '55,000', 'Medium' => '40,000', 'Large' => '30,000']],
            ['name' => 'Box braids', 'allocation' => ['primary_assistant', 6000], 'variants' => ['Small' => '45,000', 'Medium' => '35,000', 'Large' => '25,000']],
            ['name' => 'Boho knotless braids', 'allocation' => ['primary_assistant', 5000], 'price' => '65,000'],
            ['name' => 'Ghana weaving', 'allocation' => ['primary_assistant', 6000], 'variants' => ['Small' => '20,000', 'Big' => '12,000']],
            ['name' => 'Cornrows (all back)', 'allocation' => 'equal', 'price' => '8,000'],
            ['name' => 'Cornrows with extensions', 'allocation' => 'equal', 'price' => '15,000'],
            ['name' => 'Kids braids', 'allocation' => 'equal', 'price' => '15,000'],
        ],
        'Wigs and installs' => [
            ['name' => 'Frontal wig installation', 'allocation' => ['primary_assistant', 7000], 'price' => '25,000'],
            ['name' => 'Closure wig installation', 'allocation' => ['primary_assistant', 7000], 'price' => '15,000'],
            ['name' => 'Wig revamp (wash and style)', 'allocation' => 'equal', 'price' => '12,000'],
            ['name' => 'Wig customisation', 'allocation' => 'equal', 'price' => '20,000'],
            ['name' => 'Sew-in weave', 'allocation' => ['primary_assistant', 6000], 'price' => '18,000'],
        ],
        'Hair care' => [
            ['name' => 'Wash and blow-dry', 'allocation' => 'equal', 'price' => '6,000'],
            ['name' => 'Silk press', 'allocation' => 'equal', 'price' => '18,000'],
            ['name' => 'Relaxer retouch', 'allocation' => 'equal', 'price' => '12,000'],
            ['name' => 'Deep conditioning treatment', 'allocation' => 'equal', 'price' => '10,000'],
            ['name' => 'Twist-out (natural hair)', 'allocation' => 'equal', 'price' => '10,000'],
            ['name' => 'Loc retwist', 'allocation' => 'equal', 'price' => '15,000'],
        ],
        'Nails' => [
            ['name' => 'Classic manicure', 'allocation' => 'equal', 'price' => '7,000'],
            ['name' => 'Classic pedicure', 'allocation' => 'equal', 'price' => '9,000'],
            ['name' => 'Spa pedicure', 'allocation' => 'equal', 'price' => '15,000'],
            ['name' => 'Gel polish (hands)', 'allocation' => 'equal', 'price' => '10,000'],
            ['name' => 'Acrylic full set', 'allocation' => 'equal', 'variants' => ['Short' => '18,000', 'Long' => '25,000']],
            ['name' => 'Acrylic refill', 'allocation' => 'equal', 'price' => '12,000'],
            ['name' => 'Nail art (per set)', 'allocation' => 'equal', 'price' => '5,000', 'quantity_allowed' => true],
        ],
        'Lashes and makeup' => [
            ['name' => 'Lash extensions (classic)', 'allocation' => 'equal', 'price' => '15,000'],
            ['name' => 'Lash extensions (volume)', 'allocation' => 'equal', 'price' => '22,000'],
            ['name' => 'Strip lashes application', 'allocation' => 'equal', 'price' => '5,000'],
            ['name' => 'Brow shaping and tint', 'allocation' => 'equal', 'price' => '6,000'],
            ['name' => 'Soft glam makeup', 'allocation' => 'equal', 'price' => '25,000'],
            ['name' => 'Bridal makeup', 'allocation' => ['primary_assistant', 7000], 'price' => '120,000'],
            ['name' => 'Gele tying', 'allocation' => 'equal', 'price' => '8,000'],
        ],
    ],

    'products' => [
        'Hair products' => [
            ['name' => 'X-pression braiding hair (pack)', 'price' => '3,500'],
            ['name' => 'Pre-stretched braiding hair (pack)', 'price' => '4,500'],
            ['name' => 'Edge control', 'price' => '5,000'],
            ['name' => 'Leave-in conditioner', 'price' => '6,500'],
            ['name' => 'Lace glue', 'price' => '8,000'],
            ['name' => 'Wig cap', 'price' => '1,500'],
        ],
        'Beauty products' => [
            ['name' => 'Lash glue', 'price' => '4,000'],
            ['name' => 'Cuticle oil', 'price' => '3,000'],
        ],
    ],
];
