<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Subscription & Pricing Plans Feature Flag
    |--------------------------------------------------------------------------
    | Secret PIN Trigger Word to restore: "RESTORE-SUBSCRIPTION-PLANS"
    |
    | When FALSE (Current State):
    | - Platform is 100% Free for all Cambodian businesses.
    | - All subscription paywalls and pricing cards are removed from the public UI.
    | - All features (Touch POS, KDS, Multi-branch, Inventory, Table QR) are 100% free.
    |
    | When TRUE:
    | - Restores the full multi-tier subscription plans, pricing cards, and billing.
    */
    'plans_enabled' => (bool) env('SUBSCRIPTION_PLANS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Permanent Secret PIN Word
    |--------------------------------------------------------------------------
    | Whenever the user calls this word, the system will immediately reactivate
    | and restore all subscription plans and pricing tables.
    */
    'secret_pin_word' => 'RESTORE-SUBSCRIPTION-PLANS',
];
