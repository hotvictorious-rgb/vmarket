<?php

namespace App\Repositories;

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Models\AdminWallet;
use App\Models\Order;
use App\Models\OrderExpectedDeliveryHistory;
use App\Models\OrderTransaction;
use App\Models\Product;
use App\Models\SellerWallet;
use App\Models\Shop;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryInterface
{

    public function __construct(
        private readonly Order                        $order,
        private readonly OrderExpectedDeliveryHistory $orderExpectedDeliveryHistory,
        private readonly Product                      $product,
        private readonly OrderDetail                  $orderDetail,
        private readonly AdminWallet                  $adminWallet,
        private readonly SellerWallet                 $sellerWallet,
        private readonly Transaction                  $transaction,
        private readonly OrderTransaction             $orderTransaction,
        private readonly Shop                         $shop,
    )
    {
    }

    public function add(array $data): string|object
    {
        return $this->order->create($data);
    }

    public function getFirstWhere(array $params, array $relations = []): ?Model
    {
        return $this->order->with($relations)->where($params)->first();
    }

    public function getList(array $orderBy = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, ?int $offset = null): Collection|LengthAwarePaginator
    {
        return $this->order
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                $query->orderBy(key($orderBy), current($orderBy));
            })->get();
    }

    public function getListWhere(array $orderBy = [], ?string $searchValue = null, array $filters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, ?int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->order->with($relations)
            ->when(isset($filters['seller_is']) && $filters['seller_is'] != 'all', function ($query) use ($filters) {
                return $query->where('seller_is', $filters['seller_is']);
            })
            ->when(isset($filters['seller_id']) && $filters['seller_id'] != 'all', function ($query) use ($filters) {
                return $query->where('seller_id', $filters['seller_id']);
            })
            ->when(isset($filters['order_type']) && $filters['order_type'] != 'all', function ($query) use ($filters) {
                return $query->where('order_type', $filters['order_type']);
            })
            ->when(isset($filters['order_status']) && $filters['order_status'] != 'all', function ($query) use ($filters) {
                return $query->where('order_status', $filters['order_status']);
            })
            ->when(isset($filters['customer_id']) && $filters['customer_id'] != 'all', function ($query) use ($filters) {
                return $query->where('customer_id', $filters['customer_id']);
            })
            ->when(isset($filters['is_guest']), function ($query) use ($filters) {
                return $query->where('is_guest', $filters['is_guest']);
            })
            ->when(isset($filters['customer_type']), function ($query) use ($filters) {
                return $query->where('is_guest', $filters['customer_type']);
            })
            ->when(isset($filters['coupon_code']), function ($query) use ($filters) {
                return $query->where('coupon_code', $filters['coupon_code']);
            })
            ->when(isset($filters['checked']), function ($query) use ($filters) {
                return $query->where('checked', $filters['checked']);
            })
            ->when(isset($filters['filter']), function ($query) use ($filters) {
                $query->when($filters['filter'] == 'all', function ($query) {
                    return $query;
                })
                    ->when($filters['filter'] == 'POS', function ($query) {
                        return $query->where('order_type', 'POS');
                    })
                    ->when($filters['filter'] == 'default_type', function ($query) {
                        return $query->where('order_type', 'default_type');
                    })
                    ->when($filters['filter'] == 'admin' || $filters['filter'] == 'seller', function ($query) use ($filters) {
                        return $query->whereHas('details', function ($query) use ($filters) {
                            return $query->whereHas('product', function ($query) use ($filters) {
                                return $query->where('added_by', $filters['filter']);
                            });
                        });
                    });
            })
            ->when(isset($filters['date_type']) && $filters['date_type'] == "today", function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when(isset($filters['date_type']) && $filters['date_type'] == "this_year", function ($query) {
                $current_start_year = date('Y-01-01');
                $current_end_year = date('Y-12-31');
                return $query->whereDate('created_at', '>=', $current_start_year)
                    ->whereDate('created_at', '<=', $current_end_year);
            })
            ->when(isset($filters['date_type']) && $filters['date_type'] == "this_month", function ($query) {
                $current_month_start = date('Y-m-01');
                $current_month_end = date('Y-m-t');
                return $query->whereDate('created_at', '>=', $current_month_start)
                    ->whereDate('created_at', '<=', $current_month_end);
            })
            ->when(isset($filters['date_type']) && $filters['date_type'] == "this_week", function ($query) {
                $start_week = Carbon::now()->subDays(7)->startOfWeek()->format('Y-m-d');
                $end_week = Carbon::now()->startOfWeek()->format('Y-m-d');
                return $query->whereDate('created_at', '>=', $start_week)
                    ->whereDate('created_at', '<=', $end_week);
            })
            ->when(isset($filters['date_type']) && $filters['date_type'] == "custom_date" && isset($filters['from']) && isset($filters['to']), function ($query) use ($filters) {
                return $query->whereDate('created_at', '>=', $filters['from'])
                    ->whereDate('created_at', '<=', $filters['to']);
            })
            ->when(isset($filters['delivery_man_id']), function ($query) use ($filters) {
                return $query->where(['delivery_man_id' => $filters['delivery_man_id']]);
            })
            ->when($searchValue, function ($query) use ($searchValue) {
                return $query->where(function ($query) use ($searchValue) {
                    return $query->where('id', 'like', "%{$searchValue}%")
                        ->orWhere('order_status', 'like', "%{$searchValue}%")
                        ->orWhere('transaction_ref', 'like', "%{$searchValue}%");
                });
            })
            ->when(isset($filters['whereHas_deliveryMan']), function ($query) use ($filters) {
                return $query->whereHas('deliveryMan', function ($query) use ($filters) {
                    $query->where('seller_id', $filters['whereHas_deliveryMan']);
                });
            })
            ->when(isset($filters['whereIn_order_status']) && $filters['whereIn_order_status'] != 'all', function ($query) use ($filters) {
                $query->whereIn('order_status', $filters['whereIn_order_status']);
            })
            ->when(isset($filters['whereIn_payment_status']) && $filters['whereIn_payment_status'] != 'all', function ($query) use ($filters) {
                $query->whereIn('payment_status', $filters['whereIn_payment_status']);
            })
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                $query->orderBy(array_key_first($orderBy), array_values($orderBy)[0]);
            });

        $filters += ['searchValue' => $searchValue];
        return $dataLimit == 'all' ? $query->get() : $query->paginate($dataLimit)->appends($filters);
    }

    public function getListWhereIn(array $orderBy = [], ?string $searchValue = null, array $filters = [], array $whereIn = [], array $relations = [], array $nullFields = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, ?int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->order->with($relations)
            ->when(isset($filters['seller_is']) && $filters['seller_is'] != 'all', function ($query) use ($filters) {
                return $query->where('seller_is', $filters['seller_is']);
            })
            ->when(isset($filters['seller_id']) && $filters['seller_id'] != 'all', function ($query) use ($filters) {
                return $query->where('seller_id', $filters['seller_id']);
            })
            ->when(isset($filters['order_type']) && $filters['order_type'] != 'all', function ($query) use ($filters) {
                return $query->where('order_type', $filters['order_type']);
            })
            ->when(isset($filters['order_status']) && $filters['order_status'] != 'all' && !is_array($filters['order_status']), function ($query) use ($filters) {
                return $query->where('order_status', $filters['order_status']);
            })
            ->when(isset($filters['customer_id']) && $filters['customer_id'] != 'all' && $filters['customer_id'] != '0', function ($query) use ($filters) {
                return $query->where('customer_id', $filters['customer_id']);
            })
            ->when(isset($filters['is_guest']), function ($query) use ($filters) {
                return $query->where('is_guest', $filters['is_guest']);
            })
            ->when(isset($filters['customer_type']), function ($query) use ($filters) {
                return $query->where('is_guest', $filters['customer_type']);
            })
            ->when(isset($filters['coupon_code']), function ($query) use ($filters) {
                return $query->where('coupon_code', $filters['coupon_code']);
            })
            ->when(isset($filters['checked']), function ($query) use ($filters) {
                return $query->where('checked', $filters['checked']);
            })
            ->when(isset($filters['filter']), function ($query) use ($filters) {
                $query->when($filters['filter'] == 'all', function ($query) {
                    return $query;
                })
                    ->when($filters['filter'] == 'POS', function ($query) {
                        return $query->where('order_type', 'POS');
                    })
                    ->when($filters['filter'] == 'default_type', function ($query) {
                        return $query->where('order_type', 'default_type');
                    })
                    ->when($filters['filter'] == 'admin' || $filters['filter'] == 'seller', function ($query) use ($filters) {
                        return $query->whereHas('details', function ($query) use ($filters) {
                            return $query->whereHas('product', function ($query) use ($filters) {
                                return $query->where('added_by', $filters['filter']);
                            });
                        });
                    });
            })
            ->when(isset($filters['date_type']) && $filters['date_type'] == "today", function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when(isset($filters['date_type']) && $filters['date_type'] == "this_week", function ($query) {
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            })
            ->when(isset($filters['date_type']) && $filters['date_type'] == "this_month", function ($query) {
                $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
            })
            ->when(isset($filters['date_type']) && $filters['date_type'] == "this_year", function ($query) {
                $query->whereYear('created_at', Carbon::now()->year);
            })
            ->when(isset($filters['date_type']) && $filters['date_type'] == "custom_date" && isset($filters['from']) && isset($filters['to']), function ($query) use ($filters) {
                return $query->whereDate('created_at', '>=', $filters['from'])
                    ->whereDate('created_at', '<=', $filters['to']);
            })
            ->when(isset($filters['delivery_man_id']), function ($query) use ($filters) {
                return $query->where(['delivery_man_id' => $filters['delivery_man_id']]);
            })
            ->when($searchValue, function ($query) use ($searchValue) {
                return $query->where(function ($query) use ($searchValue) {
                    return $query->where('id', 'like', "%{$searchValue}%")
                        ->orWhere('order_status', 'like', "%{$searchValue}%")
                        ->orWhere('transaction_ref', 'like', "%{$searchValue}%");
                });
            })
            ->when(isset($filters['whereHas_deliveryMan']), function ($query) use ($filters) {
                return $query->whereHas('deliveryMan', function ($query) use ($filters) {
                    $query->where('seller_id', $filters['whereHas_deliveryMan']);
                });
            })
            ->when(isset($filters['has_order_edit_settlement']), function ($query) use ($filters) {
                $settlements = $filters['has_order_edit_settlement'];
                $query->where('edited_status', 1)
                    ->when(count($settlements) == 1 && in_array('due', $settlements), function ($query) use ($filters) {
                        $query->where('edit_due_amount', '>', 0);
                    })
                    ->when(count($settlements) == 1 && in_array('return', $settlements), function ($query) use ($filters) {
                        $query->where('edit_return_amount', '>', 0);
                    })
                    ->when(count($settlements) > 1 && in_array('due', $settlements) && in_array('return', $settlements), function ($query) use ($filters) {
                        $query->where('edit_due_amount', '>', 0)->orWhere('edit_return_amount', '>', 0);
                    });
            })
            ->when(isset($filters['whereIn_order_status']) && $filters['whereIn_order_status'] != 'all', function ($query) use ($filters) {
                $query->whereIn('order_status', $filters['whereIn_order_status']);
            })
            ->when(isset($filters['whereIn_payment_status']) && $filters['whereIn_payment_status'] != 'all', function ($query) use ($filters) {
                $query->whereIn('payment_status', $filters['whereIn_payment_status']);
            })
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                $query->orderBy(array_key_first($orderBy), array_values($orderBy)[0]);
            })
            ->when(!empty($whereIn), function ($query) use ($whereIn) {
                foreach ($whereIn as $key => $filterIndex) {
                    if (is_array($filterIndex) && !empty($filterIndex)) {
                        $query->whereIn($key, $filterIndex);
                    }
                }
            })
            ->when(!empty($nullFields), function ($query) use ($nullFields) {
                return $query->whereNull($nullFields);
            })
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                return $query->orderBy(array_key_first($orderBy), array_values($orderBy)[0]);
            });

        $filters += ['searchValue' => $searchValue];
        return $dataLimit == 'all' ? $query->get() : $query->paginate($dataLimit)->appends($filters);
    }

    public function getListWhereDate(array $filters = [], ?string $dateType = null, array $filterDate = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, ?int $offset = null): Collection|LengthAwarePaginator
    {
        return $this->order->with($relations)
            ->when($filters['order_status'], function ($query) use ($filters) {
                $query->where('order_status', $filters['order_status']);
            })
            ->when(isset($filters['seller_is']) && $filters['seller_is'] == 'seller', function ($query) use ($filters) {
                $query->where(['seller_is' => 'seller', 'seller_id' => $filters['seller_id']]);
            })
            ->when($dateType == 'today', function ($query) {
                $query->whereDate('created_at', Carbon::today());
            })
            ->when($dateType == 'this_week', function ($query) {
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            })
            ->when($dateType == 'this_month', function ($query) {
                $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
            })
            ->when($dateType == 'this_year', function ($query) {
                $query->whereYear('created_at', Carbon::now()->year);
            })
            ->get();
    }

    public function getListWhereCount(?string $searchValue = null, array $filters = [], array $relations = []): int
    {
        return $this->order->with($relations)
            ->when(isset($filters['customer_id']), function ($query) use ($filters) {
                return $query->where(['customer_id' => $filters['customer_id']]);
            })->count();
    }

    public function getDeliveryManOrderListWhere(string $addedBy, ?string $searchValue = null, array $filters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, ?int $offset = null): Collection|LengthAwarePaginator
    {
        return $this->order->select('id', 'deliveryman_charge', 'order_status', 'delivery_man_id')->where($filters)
            ->whereHas('deliveryMan', function ($query) use ($addedBy) {
                $query->when($addedBy === 'seller', function ($subQuery) {
                    $subQuery->where(['seller_id' => auth('seller')->id()]);
                });
            })
            ->when($searchValue, function ($query) use ($searchValue) {
                $query->where('id', 'like', "%$searchValue%");
            })
            ->latest()
            ->paginate($dataLimit)
            ->appends(['searchValue' => $searchValue]);
    }

    public function update(string $id, array $data): bool
    {
        return $this->order->where('id', $id)->update($data);
    }

    public function updateWhere(array $params, array $data): bool
    {
        return $this->order->where($params)->update($data);
    }

    public function delete(array $params): bool
    {
        $this->order->where($params)->delete();
        return true;
    }

    public function getListWhereNotIn(array $orderBy = [], ?string $searchValue = null, array $filters = [], array $relations = [], array $nullFields = [], array $whereNotIn = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, ?int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->order->where($filters)
            ->when(!empty($searchValue), function ($query) use ($searchValue) {
                $query->whereHas('order', function ($query) use ($searchValue) {
                    $query->where('id', 'like', "%{$searchValue}%");
                });
            })
            ->when(!empty($whereNotIn), function ($query) use ($whereNotIn) {
                foreach ($whereNotIn as $key => $whereNotInIndex) {
                    $query->whereNotIn($key, $whereNotInIndex);
                }
            })
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                $query->orderBy(array_key_first($orderBy), array_values($orderBy)[0]);
            });

        $filters += ['searchValue' => $searchValue];
        return $dataLimit == 'all' ? $query->get() : $query->paginate($dataLimit)->appends($filters);
    }

    public function updateAmountDate(object $request, string|int $userId, string $userType): bool
    {
        $fieldName = $request['field_name'];
        $fieldValues = $request['field_val'];
        $cause = $request['cause'] ?? null;

        if ($fieldName == 'deliveryman_charge') {
            $fieldValues = currencyConverter(amount: $fieldValues);
        }

        try {
            DB::beginTransaction();

            if ($fieldName == 'expected_delivery_date') {
                $this->orderExpectedDeliveryHistory->create([
                    'order_id' => $request['order_id'],
                    'user_id' => $userId,
                    'user_type' => $userType,
                    'expected_delivery_date' => $fieldValues,
                    'cause' => $cause
                ]);
            }

            $this->order->where(['id' => $request['order_id']])->update([$fieldName => $fieldValues]);

            DB::commit();
        } catch (Exception $ex) {
            DB::rollback();
            return false;
        }
        return true;
    }

    public function updateStockOnOrderStatusChange(string|int $orderId, string $status): bool
    {
        $order = $this->order->with('details.product')->find($orderId);
        if ($status == 'returned' || $status == 'failed' || $status == 'canceled') {
            foreach ($order['details'] as $detail) {
                if ($detail['is_stock_decreased'] == 1) {
                    $product = $detail?->product;
                    $type = $detail['variant'];
                    $variations = [];
                    if ($product && $product['variation']) {
                        foreach (json_decode($product['variation'], true) as $variation) {
                            if ($type == $variation['type']) {
                                $variation['qty'] += $detail['qty'];
                            }
                            $variations[] = $variation;
                        }
                    }
                    if ($product) {
                        $this->product->where(['id' => $product['id']])->update([
                            'variation' => json_encode($variations),
                            'current_stock' => $product['current_stock'] + $detail['qty'],
                        ]);
                        $this->orderDetail->where(['id' => $detail['id']])->update([
                            'is_stock_decreased' => 0,
                            'delivery_status' => $status
                        ]);
                    }
                }
            }
        } else {
            foreach ($order['details'] as $detail) {
                if ($detail['is_stock_decreased'] == 0) {
                    $product = $detail?->product;
                    if ($product) {
                        $type = $detail['variant'];
                        $variations = [];
                        foreach (json_decode($product['variation'], true) as $variation) {
                            if ($type == $variation['type']) {
                                $variation['qty'] -= $detail['qty'];
                            }
                            $variations[] = $variation;
                        }
                        $this->product->where(['id' => $product['id']])->update([
                            'variation' => json_encode($variations),
                            'current_stock' => $product['current_stock'] - $detail['qty'],
                        ]);
                    }
                    $this->orderDetail->where(['id' => $detail['id']])->update([
                        'is_stock_decreased' => 1,
                        'delivery_status' => $status
                    ]);
                }
            }
        }

        return true;
    }

    public function manageWalletOnOrderStatusChange(object $order, string $receivedBy): bool
    {
        $order = $this->order->find($order['id']);
        if (!$order) {
            return false;
        }

        // [AI] Financial Idempotency Guard: Prevent duplicate vendor earnings or commission crediting
        if (\App\Models\OrderTransaction::where(['order_id' => $order->id, 'status' => 'disburse'])->exists()) {
            return true;
        }

        // [AI] Strict Third-Party Marketplace Settlement Boundary:
        // Automatic vendor earnings disbursement on order status change is COMPLETELY BLOCKED for third-party marketplace orders.
        // Third-party vendor disbursement must strictly execute via VendorSettlementService::executeManualSettlement().
        if (\App\Utils\OrderManager::isThirdPartyMarketplaceOrder($order)) {
            return true; // [AI] Block: do NOT disburse vendor earnings automatically
        }

        // [AI] V1 Pure Digital Accounting: Only admin in-house orders are processed here
        $orderSummary = getOrderSummary(order: $order);
        $orderAmount = $orderSummary['subtotal'] - $orderSummary['total_discount_on_product'] - $order['discount_amount'];
        $commission = $order['admin_commission'];
        $shippingModel = $order->shipping_responsibility;

        $adminWallet = $this->adminWallet->where('admin_id', 1)->first();
        if (!$adminWallet) {
            $adminWalletData = [
                'admin_id' => 1,
                'withdrawn' => 0,
                'commission_earned' => 0,
                'inhouse_earning' => 0,
                'delivery_charge_earned' => 0,
                'pending_amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $adminWallet = $this->adminWallet->create($adminWalletData);
        }

        // Coupon expense transaction
        if ($order['coupon_code'] && $order['coupon_code'] != '0' && $order['discount_type'] == 'coupon_discount') {
            $transaction = [
                'order_id' => $order->id,
                'payment_for' => 'coupon_discount',
                'payer_id' => 1,
                'payment_receiver_id' => 1,
                'paid_by' => 'admin',
                'paid_to' => 'admin',
                'payment_status' => 'disburse',
                'amount' => $order['discount_amount'],
                'transaction_type' => 'expense',
            ];
            $this->transaction->create($transaction);
        }

        // Referral discount expense transaction
        if ($order['refer_and_earn_discount'] > 0) {
            $transaction = [
                'order_id' => $order->id,
                'payment_for' => 'referral_discount',
                'payer_id' => 1,
                'payment_receiver_id' => 1,
                'paid_by' => 'admin',
                'paid_to' => 'admin',
                'payment_status' => 'disburse',
                'amount' => $order['refer_and_earn_discount'],
                'transaction_type' => 'expense',
            ];
            $this->transaction->create($transaction);
        }

        // Update OrderTransaction status to disburse
        $transaction = $this->orderTransaction->where(['order_id' => $order['id']])->first();
        if ($transaction) {
            $transaction->status = 'disburse';
            $transaction->save();
        }

        // Update Admin Wallet for in-house orders
        $adminWallet->commission_earned += $commission;
        $adminWallet->inhouse_earning += $orderAmount;
        $adminWallet->total_tax_collected += $orderSummary['total_tax'];
        $adminWallet->pending_amount = max(0, $adminWallet->pending_amount - $order['order_amount']);

        if (!$order['is_shipping_free'] && $order['shipping_cost'] > 0) {
            $adminWallet->delivery_charge_earned += $order['shipping_cost'];
        }
        $adminWallet->save();

        return true;
    }

    public function getListWhereBetween(array $filters = [], ?string $selectColumn = null, ?string $whereBetween = null, array $whereBetweenFilters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, ?int $offset = null): Collection|LengthAwarePaginator
    {
        return $this->order->with($relations)->where($filters)
            ->when($selectColumn == 'order_amount', function ($query) {
                $query->select(
                    DB::raw('IFNULL(sum(order_amount),0) as sums'),
                    DB::raw('YEAR(created_at) year, MONTH(created_at) month,DAY(created_at) day,DAYNAME(created_at) day_of_week')
                );
            })
            ->whereBetween($whereBetween, $whereBetweenFilters)
            ->groupby('year', 'month', 'day_of_week')
            ->get();
    }

    public function getTopCustomerList(array $filters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, ?int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->order
            ->with($relations)
            ->where($filters)
            ->select('customer_id', DB::raw('COUNT(customer_id) as count'))
            ->whereHas('customer', function ($query) {
                $query->where('id', '!=', 0)->whereNot('email', 'walking@customer.com');
            })
            ->groupBy('customer_id')
            ->orderBy("count", 'desc');

        return $dataLimit == 'all' ? $query->get() : $query->paginate($dataLimit)->appends($filters);
    }

    public function getTopVendorListByOrderReceived(array $filters = [], array $relations = [], int|string $dataLimit = DEFAULT_DATA_LIMIT, ?int $offset = null): Collection|LengthAwarePaginator
    {
        $query = $this->order
            ->with($relations)
            ->whereHas('seller', function ($query) {
                return $query;
            })
            ->where('seller_is', 'seller')
            ->select('seller_id', DB::raw('COUNT(id) as count'))
            ->groupBy('seller_id')
            ->orderBy("count", 'desc');

        return $dataLimit == 'all' ? $query->get() : $query->paginate($dataLimit)->appends($filters);
    }

    public function getPreviousFirstOrderWhere(int $id, array $params = [], array $relations = []): ?Model
    {
        return $this->order->with($relations)->where($params)->where('id', '<', $id)
            ->orderBy('id', 'desc')->first();
    }

    public function getNextFirstOrderWhere(int $id, array $params = [], array $relations = []): ?Model
    {
        return $this->order->with($relations)->where($params)->where('id', '>', $id)
            ->orderBy('id', 'asc')->first();
    }
}
