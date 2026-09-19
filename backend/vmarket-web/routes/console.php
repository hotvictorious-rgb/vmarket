<?php

use Illuminate\Support\Facades\Schedule;

// [AI] Automated Cashback Reward Maturation (Hourly check for orders passing the 24-hour return window)
Schedule::command('cashback:mature')->hourly();

// [AI] Marketplace Listing Freshness Audit
Schedule::command('products:check-marketplace-freshness')->hourly();
