<?php

return [
    'name' => 'Pos',

    /*
    |--------------------------------------------------------------------------
    | POS Module Configuration
    |--------------------------------------------------------------------------
    | marketplace_status values that are allowed to access the POS:
    |   - 'pos_only'         → Free POS (unverified vendors)
    |   - 'pending_approval' → POS active while awaiting marketplace KYC
    |   - 'approved'         → Full access (POS + Marketplace)
    |
    | marketplace_status values that are BLOCKED from POS:
    |   - 'suspended'        → Vendor suspended by admin
    */
    'allowed_marketplace_statuses' => ['pos_only', 'pending_approval', 'approved'],

    'receipt_prefix' => 'RCP',

    'default_reorder_level' => 5,

    'debt_repayment_methods' => ['cash', 'pos_card', 'bank_transfer'],
];
