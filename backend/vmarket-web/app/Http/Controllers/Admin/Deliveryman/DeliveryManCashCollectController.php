<?php

namespace App\Http\Controllers\Admin\Deliveryman;

use App\Contracts\Repositories\DeliveryManRepositoryInterface;
use App\Contracts\Repositories\DeliveryManTransactionRepositoryInterface;
use App\Contracts\Repositories\DeliveryManWalletRepositoryInterface;
use App\Enums\WebConfigKey;
use App\Events\CashCollectEvent;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\DeliveryManCashCollectRequest;
use App\Services\DeliveryManCashCollectService;
use App\Traits\PushNotificationTrait;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliveryManCashCollectController extends BaseController
{
    use PushNotificationTrait;

    /**
     * @param DeliveryManRepositoryInterface $deliveryManRepo
     * @param DeliveryManWalletRepositoryInterface $deliveryManWalletRepo
     * @param DeliveryManTransactionRepositoryInterface $deliveryManTransactionRepo
     */
    public function __construct(
        private readonly DeliveryManRepositoryInterface       $deliveryManRepo,
        private readonly DeliveryManWalletRepositoryInterface $deliveryManWalletRepo,
        private readonly DeliveryManTransactionRepositoryInterface $deliveryManTransactionRepo,
    )
    {
    }

    /**
     * @param Request|null $request
     * @param string|null $type
     * @return View Index function is the starting point of a controller
     * Index function is the starting point of a controller
     */
    public function index(Request|null $request, ?string $type = null): View
    {
        $deliveryMan = $this->deliveryManRepo->getFirstWhere(params: ['id'=>$request['id']], relations: ['wallet']);
        $transactions = $this->deliveryManTransactionRepo->getListWhere(
            orderBy: ['id'=>'desc'],
            filters: ['delivery_man_id'=>$request['id']],
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
        );

        return view('admin-views.delivery-man.earning-statement.collect-cash', compact('deliveryMan', 'transactions'));
    }


    public function getCashReceive(DeliveryManCashCollectRequest $request, $id, DeliveryManCashCollectService $deliveryManCashCollectService): RedirectResponse
    {
        $deliveryMan = $this->deliveryManRepo->getFirstWhere(params: ['id' => $id]);
        if (!$deliveryMan) {
            ToastMagic::error(translate('deliveryman_not_found'));
            return back();
        }

        try {
            $convertedAmount = currencyConverter(amount: $request['amount']);
            $status = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $id, $deliveryManCashCollectService, $deliveryMan, $convertedAmount) {
                // [AI] Pessimistic lock on delivery man wallet to prevent concurrent over-collection
                $wallet = \App\Models\DeliveryManWallet::where('delivery_man_id', $id)->lockForUpdate()->first();
                if (!$wallet || $convertedAmount > $wallet->cash_in_hand) {
                    return false;
                }

                $dataArray = $deliveryManCashCollectService->getIdentityImages(request: $request, deliveryMan: $deliveryMan);
                $this->deliveryManTransactionRepo->add(data: $dataArray);
                $amount = $wallet->cash_in_hand - $convertedAmount;
                $this->deliveryManWalletRepo->update(id: $wallet->id, data: ['cash_in_hand' => $amount]);
                return true;
            });

            if (!$status) {
                ToastMagic::warning(translate('receive_amount_can_not_be_more_than_cash_in_hand'));
                return back();
            }

            if (!empty($deliveryMan['fcm_token'])) {
                CashCollectEvent::dispatch('cash_collect_by_admin_message', 'delivery_man', $deliveryMan['app_language'] ?? getDefaultLanguage(), $convertedAmount, $deliveryMan['fcm_token']);
            }
            ToastMagic::success(translate('amount_receive_successfully'));
        } catch (\Exception $e) {
            ToastMagic::error(translate('something_went_wrong_please_try_again'));
        }

        return back();
    }

}
