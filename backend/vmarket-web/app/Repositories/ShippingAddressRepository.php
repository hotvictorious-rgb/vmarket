<?php

namespace App\Repositories;

use App\Contracts\Repositories\ShippingAddressRepositoryInterface;
use App\Models\ShippingAddress;
use App\Traits\ProductTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class ShippingAddressRepository implements ShippingAddressRepositoryInterface
{
    use ProductTrait;

    public function __construct(
        private readonly ShippingAddress $shippingAddress,
    )
    {
    }

    public function add(array $data): string|object
    {
        return $this->shippingAddress->create($data);
    }

    public function getFirstWhere(array $params, array $relations = []): ?Model
    {
        return $this->shippingAddress->with($relations)->where($params)->first();
    }

    public function getList(array $orderBy = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, ?int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->shippingAddress->with($relations)
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                return $query->orderBy(array_key_first($orderBy), array_values($orderBy)[0]);
            });

        return $dataLimit == 'all' ? $query->get() : $query->paginate($dataLimit);
    }

    public function getListWhere(array $orderBy = [], ?string $searchValue = null, array $filters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, ?int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->shippingAddress
            ->when($searchValue, function ($query) use ($searchValue) {
                return $query->where('id', 'like', "%$searchValue%");
            })
            ->when(isset($filters['id']), function ($query) use ($filters) {
                $query->where('id', $filters['id']);
            })
            // [AI] SECURITY FIX VULN-004: Added customer_id filter support to enforce tenant isolation at the repository layer
            ->when(isset($filters['customer_id']), function ($query) use ($filters) {
                $query->where('customer_id', $filters['customer_id']);
            })
            ->when(isset($filters['is_guest']), function ($query) use ($filters) {
                $query->where('is_guest', $filters['is_guest']);
            })
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                $query->orderBy(array_key_first($orderBy), array_values($orderBy)[0]);
            });

        $filters += ['searchValue' => $searchValue];
        return $dataLimit == 'all' ? $query->get() : $query->paginate($dataLimit)->appends($filters);
    }

    /**
     * [AI] SECURITY FIX VULN-010: Added optional $ownerParams to enforce ownership before update.
     * Callers should always pass ['customer_id' => $userId] (or guest equivalent) to prevent
     * cross-customer address mutations. Existing callers with no $ownerParams are unaffected.
     */
    public function update(string $id, array $data, array $ownerParams = []): bool
    {
        $query = $this->shippingAddress->where('id', $id);
        if (!empty($ownerParams)) {
            $query = $query->where($ownerParams);
        }
        return (bool) $query->update($data);
    }

    public function delete(array $params): bool
    {
        $this->shippingAddress->where($params)->delete();
        return true;
    }
}
