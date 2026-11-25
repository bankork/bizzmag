<?php

/**
 * Reference styles seen on Nigerian transfer alerts and POS slips. Only the
 * format is realistic; every generated reference is invented.
 */
return [
    'transfer_sources' => [
        ['bank' => 'GTBank', 'format' => 'TRF/{NAME}/GTB'],
        ['bank' => 'Access Bank', 'format' => 'NIP/ACCESS/{NAME}'],
        ['bank' => 'Zenith Bank', 'format' => 'ZENITH TRF FRM {NAME}'],
        ['bank' => 'First Bank', 'format' => 'FBN/NIP/{NAME}'],
        ['bank' => 'UBA', 'format' => 'UBA/TRF/{NAME}'],
        ['bank' => 'OPay', 'format' => 'OPay Transfer from {NAME}'],
        ['bank' => 'Moniepoint', 'format' => 'Moniepoint/{NAME}'],
        ['bank' => 'Kuda', 'format' => 'Kuda/NIP/{NAME}'],
        ['bank' => 'PalmPay', 'format' => 'PalmPay from {NAME}'],
    ],
    'pos_terminals' => [
        ['provider' => 'Moniepoint POS', 'format' => 'MONIEPOINT RRN {DIGITS12}'],
        ['provider' => 'OPay POS', 'format' => 'OPAY POS RRN {DIGITS12}'],
    ],
];
