<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class CategorySpecification
 *
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $input_type
 * @property array|null $options
 * @property bool $is_required
 * @property string|null $unit
 * @property string|null $placeholder
 * @property int $sort_order
 * @property bool $is_active
 * @property Category|null $category
 */
class CategorySpecification extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'input_type',
        'options',
        'is_required',
        'unit',
        'placeholder',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'options' => 'array',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
