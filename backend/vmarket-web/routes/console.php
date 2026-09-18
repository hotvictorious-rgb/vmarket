<?php

use Illuminate\Support\Facades\Schedule;

// [AI] Automated Cashback Reward Maturation (Promotes pending rewards after 7-day return period)
Schedule::command('cashback:mature')->daily();

// [AI] Marketplace Listing Freshness Audit
Schedule::command('products:check-marketplace-freshness')->hourly();
