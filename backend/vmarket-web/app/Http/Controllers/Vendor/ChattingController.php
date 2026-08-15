<?php

namespace App\Http\Controllers\Vendor;

use App\Contracts\Repositories\ChattingRepositoryInterface;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\DeliveryManRepositoryInterface;
use App\Contracts\Repositories\ShopRepositoryInterface;
use App\Contracts\Repositories\VendorRepositoryInterface;
use App\Enums\ViewPaths\Vendor\Chatting;
use App\Events\ChattingEvent;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\ChattingRequest;
use App\Models\Order;
use App\Services\ChattingService;
use App\Traits\PushNotificationTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ChattingController extends BaseController
{
    use PushNotificationTrait;

    /**
     * @param ChattingRepositoryInterface $chattingRepo
     * @param ShopRepositoryInterface $shopRepo
     * @param ChattingService $chattingService
     * @param VendorRepositoryInterface $vendorRepo
     * @param DeliveryManRepositoryInterface $deliveryManRepo
     * @param CustomerRepositoryInterface $customerRepo
     */
    public function __construct(
        private readonly ChattingRepositoryInterface    $chattingRepo,
        private readonly ShopRepositoryInterface        $shopRepo,
        private readonly ChattingService                $chattingService,
        private readonly VendorRepositoryInterface      $vendorRepo,
        private readonly DeliveryManRepositoryInterface $deliveryManRepo,
        private readonly CustomerRepositoryInterface    $customerRepo,
    )
    {
    }


    /**
     * @param Request|null $request
     * @param string|array|null $type
     * @return View|Collection|LengthAwarePaginator|callable|RedirectResponse|null
     */
    public function index(?Request $request, string|array $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        return $this->getListView(type: $type);
    }

    /**
     * @param string|array $type
     * @return View
     */
    public function getListView(string|array $type): View
    {
        $shop = $this->shopRepo->getFirstWhere(params: ['seller_id' => auth('seller')->id()]);
        $vendorId = auth('seller')->id();
        if ($type == 'delivery-man') {
            $allChattingUsers = $this->chattingRepo->getListWhereNotNull(
                orderBy: ['id' => 'DESC'],
                filters: ['seller_id' => $vendorId],
                whereNotNull: ['delivery_man_id', 'seller_id'],
                relations: ['deliveryMan'],
                dataLimit: 'all'
            )->unique('delivery_man_id')->filter(fn ($chatting) => $this->hasActiveOrderWithDeliveryMan(
                vendorId: $vendorId,
                deliveryManId: $chatting->delivery_man_id
            ))->values();

            if (count($allChattingUsers) > 0) {
                $lastChatUser = $allChattingUsers[0]->deliveryMan;
                $this->chattingRepo->updateAllWhere(
                    params: ['seller_id' => $vendorId, 'delivery_man_id' => $lastChatUser['id']],
                    data: ['seen_by_seller' => 1]
                );

                $deliveryMenUnreadMessagesQueryParams = [
                    'seller_id' => $vendorId,
                    'usersColumn' => 'delivery_man_id',
                    'filteredByColumn' => 'seen_by_seller',
                    'notificationReceiver' => 'seller',
                ];

                $countUnreadMessages = $this->chattingRepo->countUnreadMessages(data: $deliveryMenUnreadMessagesQueryParams);

                $chattingMessages = $this->chattingRepo->getListWhereNotNull(
                    orderBy: ['id' => 'DESC'],
                    filters: ['seller_id' => $vendorId, 'delivery_man_id' => $lastChatUser->id],
                    whereNotNull: ['delivery_man_id', 'seller_id'],
                    relations: ['deliveryMan'],
                    dataLimit: 'all'
                );

                return view(Chatting::INDEX[VIEW], [
                    'userType' => $type,
                    'allChattingUsers' => $allChattingUsers,
                    'lastChatUser' => $lastChatUser,
                    'chattingMessages' => $chattingMessages,
                    'countUnreadMessages' => $countUnreadMessages
                ]);
            }
        } elseif ($type == 'admin') {
            $allChattingUsers = $this->chattingRepo->getListWhereNotNull(
                orderBy: ['id' => 'DESC'],
                filters: ['seller_id' => $vendorId],
                whereNotNull: ['admin_id', 'seller_id'],
                relations: ['admin'],
                dataLimit: 'all'
            )->unique('admin_id');

            if (count($allChattingUsers) > 0) {
                $lastChatUser = $allChattingUsers[0]->admin;
                $this->chattingRepo->updateAllWhere(
                    params: ['seller_id' => $vendorId, 'admin_id' => $lastChatUser['id'] ?? 0],
                    data: ['seen_by_seller' => 1]
                );

                $adminUnreadMessagesQueryParams = [
                    'seller_id' => $vendorId,
                    'usersColumn' => 'admin_id',
                    'filteredByColumn' => 'seen_by_seller',
                    'notificationReceiver' => 'seller',
                ];

                $countUnreadMessages = $this->chattingRepo->countUnreadMessages(data: $adminUnreadMessagesQueryParams);

                $chattingMessages = $this->chattingRepo->getListWhereNotNull(
                    orderBy: ['id' => 'DESC'],
                    filters: ['seller_id' => $vendorId, 'admin_id' => $lastChatUser->id ?? 0],
                    whereNotNull: ['admin_id', 'seller_id'],
                    relations: ['admin'],
                    dataLimit: 'all'
                );
                return view(Chatting::INDEX[VIEW], [
                    'userType' => $type,
                    'allChattingUsers' => $allChattingUsers,
                    'lastChatUser' => $lastChatUser,
                    'chattingMessages' => $chattingMessages,
                    'countUnreadMessages' => $countUnreadMessages
                ]);
            } else {
                // Return view with a fake admin user if no chats exist yet.
                return view(Chatting::INDEX[VIEW], [
                    'userType' => $type,
                    'allChattingUsers' => collect([ (object)['admin_id' => 0, 'admin' => (object)['id' => 0, 'f_name' => 'Admin', 'l_name' => '', 'image_full_url' => [] ]] ]),
                    'lastChatUser' => (object)['id' => 0, 'f_name' => 'Admin', 'l_name' => '', 'image_full_url' => [] ],
                    'chattingMessages' => collect([]),
                    'countUnreadMessages' => 0
                ]);
            }
        } elseif ($type == 'customer') {
            return redirect()->route('vendor.messages.index', ['type' => 'admin']);
        }
        return view(Chatting::INDEX[VIEW], compact('shop'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getMessageByUser(Request $request): JsonResponse
    {
        $vendorId = auth('seller')->id();
        $data = [];
        if ($request->has(key: 'delivery_man_id')) {
            if (!$this->hasActiveOrderWithDeliveryMan(vendorId: $vendorId, deliveryManId: $request['delivery_man_id'])) {
                return response()->json(['message' => 'Chat closed: No active order is assigned to this delivery man.'], 403);
            }
            $getUser = $this->deliveryManRepo->getFirstWhere(params: ['id' => $request['delivery_man_id']]);
            $this->chattingRepo->updateAllWhere(
                params: ['seller_id' => $vendorId, 'delivery_man_id' => $request['delivery_man_id']],
                data: ['seen_by_seller' => 1]);

            $chattingMessages = $this->chattingRepo->getListWhereNotNull(
                orderBy: ['id' => 'DESC'],
                filters: ['seller_id' => $vendorId, 'delivery_man_id' => $request['delivery_man_id']],
                whereNotNull: ['delivery_man_id', 'seller_id'],
                dataLimit: 'all'
            );
            $data = self::getRenderMessagesView(user: $getUser, message: $chattingMessages, type: 'delivery_man');
        } elseif ($request->has(key: 'admin_id') || $request->has(key: 'user_id')) {
            // Block customer chat, route to admin logic
            if ($request->has(key: 'user_id')) {
                return response()->json(['message' => 'Messaging customers is disabled.'], 403);
            }
            $getUser = (object)['id' => 0, 'f_name' => 'Admin', 'l_name' => '', 'image_full_url' => [] ];
            $this->chattingRepo->updateAllWhere(
                params: ['seller_id' => $vendorId, 'admin_id' => 0],
                data: ['seen_by_seller' => 1]
            );
            $chattingMessages = $this->chattingRepo->getListWhereNotNull(
                orderBy: ['id' => 'DESC'],
                filters: ['seller_id' => $vendorId, 'admin_id' => 0],
                whereNotNull: ['admin_id', 'seller_id'],
                dataLimit: 'all'
            );
            $data = self::getRenderMessagesView(user: $getUser, message: $chattingMessages, type: 'admin');
        }
        return response()->json($data);
    }

    /**
     * @param ChattingRequest $request
     * @return JsonResponse
     */
    public function addVendorMessage(ChattingRequest $request): JsonResponse
    {
        if($request->hasFile('file')){
            foreach ($request->file('file') as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                if (in_array($extension, getDisallowedExtensionsListArray())) {
                    if (env('APP_MODE', 'dev') == 'demo') {
                        return response()->json([
                            'status' => 'error',
                            'message' => translate('Uploading_ZIP_files_is_currently_unavailable_in_demo_mode')
                        ]);
                    }

                    return response()->json([
                        'status' => 'error',
                        'message' => translate('Files_with_extensions_like') .
                            ' (' . implode(', ', array_map(fn($ext) => '.' . $ext, getDisallowedExtensionsListArray())) . ') ' .
                            translate('are_not_supported') . '!'
                    ]);
                }
            }
        }

        $data = [];
        $vendor = $this->vendorRepo->getFirstWhere(params: ['id' => auth('seller')->id()]);
        $shop = $this->shopRepo->getFirstWhere(params: ['seller_id' => auth('seller')->id()]);
        $attachment = $this->chattingService->getAttachment($request);
        if ($request->has(key: 'delivery_man_id')) {
            if (!$this->hasActiveOrderWithDeliveryMan(vendorId: $vendor['id'], deliveryManId: $request['delivery_man_id'])) {
                return response()->json(['message' => 'Chat closed: No active order is assigned to this delivery man.'], 403);
            }
            $this->chattingRepo->add(
                data: $this->chattingService->getDeliveryManChattingData(
                    request: $request,
                    shopId: $shop['id'],
                    vendorId: $vendor['id']
                )
            );
            $deliveryMan = $this->deliveryManRepo->getFirstWhere(params: ['id' => $request['delivery_man_id']]);
            event(new ChattingEvent(key: 'message_from_seller', type: 'delivery_man', userData: $deliveryMan, messageForm: $vendor));

            $chattingMessages = $this->chattingRepo->getListWhereNotNull(
                orderBy: ['id' => 'DESC'],
                filters: ['seller_id' => $vendor['id'], 'delivery_man_id' => $request['delivery_man_id']],
                whereNotNull: ['delivery_man_id', 'seller_id'],
                dataLimit: 'all'
            );
            $data = self::getRenderMessagesView(user: $deliveryMan, message: $chattingMessages, type: 'delivery_man');
        } elseif ($request->has(key: 'admin_id') || $request->has(key: 'user_id')) {
            if ($request->has(key: 'user_id')) {
                return response()->json(['message' => 'Messaging customers is disabled.'], 403);
            }
            $this->chattingRepo->add(
                data: $this->chattingService->getAdminChattingData(
                    request: $request,
                    shopId: $shop['id'],
                    vendorId: $vendor['id'])
            );
            $customer = (object)['id' => 0, 'f_name' => 'Admin', 'l_name' => '', 'image_full_url' => [] ];
            // Admin events can be added here if needed

            $chattingMessages = $this->chattingRepo->getListWhereNotNull(
                orderBy: ['id' => 'DESC'],
                filters: ['seller_id' => $vendor['id'], 'admin_id' => 0],
                whereNotNull: ['admin_id', 'seller_id'],
                dataLimit: 'all'
            );
            $data = self::getRenderMessagesView(user: $customer, message: $chattingMessages, type: 'admin');
        }
        return response()->json($data);
    }

    /**
     * @param string $tableName
     * @param string $orderBy
     * @param string|int|null $id
     * @return Collection
     */
    protected function getChatList(string $tableName, string $orderBy, string|int $id = null): Collection
    {
        $vendorId = auth('seller')->id();
        $columnName = $tableName == 'users' ? 'user_id' : 'delivery_man_id';
        $filters = isset($id) ? ['chattings.seller_id' => $vendorId, $columnName => $id] : ['chattings.seller_id' => $vendorId];
        return $this->chattingRepo->getListBySelectWhere(
            joinColumn: [$tableName, $tableName . '.id', '=', 'chattings.' . $columnName],
            select: ['chattings.*', $tableName . '.f_name', $tableName . '.l_name', $tableName . '.image'],
            filters: $filters,
            orderBy: ['chattings.id' => $orderBy],
        );
    }

    private function hasActiveOrderWithDeliveryMan(int|string $vendorId, int|string $deliveryManId): bool
    {
        return Order::where('seller_id', $vendorId)
            ->where('delivery_man_id', $deliveryManId)
            ->whereIn('order_status', ['processing', 'out_for_delivery'])
            ->exists();
    }

    /**
     * @param object $user
     * @param object $message
     * @param string $type
     * @return array
     */
    protected function getRenderMessagesView(object $user, object $message, string $type): array
    {
        $userData = [
            'name' => $user['f_name'] . ' ' . $user['l_name'],
            'phone' => $user['country_code'] . $user['phone'],
            'detailsRoute' => $type == 'customer' ? route('vendor.orders.list', ['status' => 'all', 'filter' => 'all', 'customer_id' => $user['id']]) : '#'
        ];

        if ($type == 'customer') {
            $userData['image'] = getStorageImages(path: $user->image_full_url, type: 'backend-profile');
        } else {
            $userData['image'] = getStorageImages(path: $user->image_full_url, type: 'backend-profile');
        }

        return [
            'userData' => $userData,
            'chattingMessages' => view('vendor-views.chatting.messages', [
                'lastChatUser' => $user,
                'userType' => $type,
                'chattingMessages' => $message
            ])->render(),
        ];
    }

    public function getNewNotification(): JsonResponse
    {
        $vendorId = auth('seller')->id();
        $chatting = $this->chattingRepo->getListWhereNotNull(
            filters: ['seller_id' => $vendorId, 'seen_by_seller' => 0, 'notification_receiver' => 'seller', 'seen_notification' => 0],
            whereNotNull: ['seller_id'],
        )->count();

        $this->chattingRepo->updateListWhereNotNull(
            filters: ['seller_id' => $vendorId, 'seen_by_seller' => 0, 'notification_receiver' => 'seller', 'seen_notification' => 0],
            whereNotNull: ['seller_id'],
            data: ['seen_notification' => 1]
        );

        return response()->json([
            'newMessagesExist' => $chatting,
            'message' => $chatting > 1 ? $chatting . ' ' . translate('New_Message') : translate('New_Message'),
        ]);
    }
}
