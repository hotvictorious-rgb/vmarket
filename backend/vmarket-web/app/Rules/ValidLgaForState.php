<?php

namespace App\Rules;

use App\Models\Lga;
use Illuminate\Contracts\Validation\Rule;

/**
 * [AI] Validates that an LGA belongs to the specified state.
 *
 * Part of: VMarket Geography & Fulfillment Architecture
 * Phase: 2 - Canonical Geography Schema
 *
 * Prevents invalid combinations like:
 * - State: Akwa Ibom, LGA: Enugu North (belongs to Enugu)
 *
 * Usage:
 * 'lga_id' => [
 *     'required',
 *     'exists:lgas,id',
 *     new ValidLgaForState($request->state_id),
 * ]
 */
class ValidLgaForState implements Rule
{
    protected $stateId;

    public function __construct($stateId)
    {
        $this->stateId = $stateId;
    }

    public function passes($attribute, $value)
    {
        if (!$this->stateId || !$value) {
            return false;
        }

        return Lga::where('id', $value)
            ->where('state_id', $this->stateId)
            ->where('is_active', true)
            ->exists();
    }

    public function message()
    {
        return 'The selected :attribute does not belong to the specified state.';
    }
}
