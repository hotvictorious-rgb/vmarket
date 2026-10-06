<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class LogisticsCompanyWithdrawRequest
 *
 * @property int $id
 * @property int $logistics_company_id
 * @property float $amount
 * @property string|null $bank_name
 * @property string|null $account_number
 * @property string|null $account_name
 * @property string $status
 * @property string|null $transaction_note
 * @property string|null $admin_note
 * @property \Illuminate\Support\Carbon|null $approved_at
 */
class LogisticsCompanyWithdrawRequest extends Model
{
    protected $table = 'logistics_company_withdraw_requests';

    protected $fillable = [
        'logistics_company_id',
        'amount',
        'bank_name',
        'account_number',
        'account_name',
        'status',
        'transaction_note',
        'admin_note',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'approved_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(LogisticsCompany::class, 'logistics_company_id');
    }
}
