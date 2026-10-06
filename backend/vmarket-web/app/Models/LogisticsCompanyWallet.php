<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class LogisticsCompanyWallet
 *
 * @property int $id
 * @property int $logistics_company_id
 * @property float $total_earned
 * @property float $withdrawn
 * @property float $pending_withdraw
 * @property float $current_balance
 */
class LogisticsCompanyWallet extends Model
{
    protected $table = 'logistics_company_wallets';

    protected $fillable = [
        'logistics_company_id',
        'total_earned',
        'withdrawn',
        'pending_withdraw',
        'current_balance',
    ];

    protected $casts = [
        'total_earned' => 'float',
        'withdrawn' => 'float',
        'pending_withdraw' => 'float',
        'current_balance' => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(LogisticsCompany::class, 'logistics_company_id');
    }
}
