<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Enums\GlobalConstant;
use App\Events\ChattingEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\CustomerSendMessageRequest;
use App\Models\Chatting;
use App\Models\DeliveryMan;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\User;
use App\Utils\FileManagerLogic;
use App\Utils\Helpers;
use App\Utils\ImageManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function list(Request $request, $type):JsonResponse
    {
        $admin_size = 0;
        $admin_chat_id = [];
        if ($type == 'delivery-man') {
            $id_param = 'delivery_man_id';
            $with = 'deliveryMan';
        } elseif ($type == 'seller') {
            return response()->json(['message' => 'Customer-to-Vendor chat is disabled.'], 403);
        } elseif ($type == 'admin') {
            $id_param = 'admin_id';
            $with = 'admin';
        } else {
            return response()->json(['message' => 'Invalid Chatting Type!'], 403);
        }

        $total_size = Chatting::where(['user_id' => $request->user()->id])
                ->whereNotNull($id_param)
                ->select($id_param)
                ->distinct()
                ->get()
                ->count();

        $all_chat_ids = Chatting::where(['user_id' => $request->user()->id])
            ->whereNotNull($id_param)
            ->select($id_param)
            ->latest()
            ->get()
            ->unique($id_param)
            ->toArray();

        $unique_chat_ids = array_slice(array_values($all_chat_ids), $request->offset - 1, $request->limit);

        $chats = array();
        // removed mixed admin chat logic from seller

        if ($unique_chat_ids) {
            foreach ($unique_chat_ids as $unique_chat_id) {
                $user_chatting = Chatting::with([$with])
                    ->where(['user_id' => $request->user()->id, $id_param => $unique_chat_id[$id_param]])
                    ->whereNotNull($id_param)
                    ->latest()
                    ->first();

                $user_chatting->unseen_message_count = Chatting::where(['user_id' => $user_chatting->user_id, $id_param => $user_chatting->$id_param, 'seen_by_customer' => '0'])->count();
                $chats[] = $user_chatting;
            }
        }

        $data = array();
        $data['total_size'] = $total_size;
        $data['limit'] = $request->limit;
        $data['offset'] = $request->offset;
        $data['chat'] = $chats;

        return response()->json($data, 200);
    }

    private function getAdminChatList($request): array
    {
        $admin_size = Chatting::where(['user_id' => $request->user()->id])
            ->whereNotNull(['admin_id', 'user_id'])
            ->select(['admin_id', 'seller_id'])
            ->distinct()
            ->get()
            ->count();

        return [
            'admin_size' => $admin_size,
            'admin_chat_id' => $admin_size > 0 ? [['admin_id' => 0]] : [],
        ];
    }

    public function search(Request $request, $type):JsonResponse
    {
        $terms = explode(" ", $request->input('search'));
        if ($type == 'seller') {
            return response()->json(['message' => 'Customer-to-Vendor chat is disabled.'], 403);        } elseif ($type == 'delivery-man') {
            $with_param = 'deliveryMan';
            $id_param = 'delivery_man_id';
            $users = DeliveryMan::when($request->search, function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->where('f_name', 'like', '%' . $term . '%')
                        ->orWhere('l_name', 'like', '%' . $term . '%');
                }
            })->pluck('id')->toArray();
        } elseif ($type == 'admin') {
            $with_param = 'admin';
            $id_param = 'admin_id';
            $users = [0]; // Admin is 0
        } else {
            return response()->json(['message' => translate('Invalid Chatting Type!')], 403);
        }
        $unique_chat_ids = Chatting::where(['user_id' => $request->user()->id])
            ->whereIn($id_param, $users)
            ->select($id_param)
            ->distinct()
            ->get()
            ->toArray();
        $unique_chat_ids = call_user_func_array('array_merge', $unique_chat_ids);
        $chats = array();
        if ($unique_chat_ids) {
            foreach ($unique_chat_ids as $unique_chat_id) {
                if (!is_array($unique_chat_id)) {
                    $user_chatting = Chatting::with([$with_param])
                        ->where(['user_id' => $request->user()->id, $id_param => $unique_chat_id])
                        ->whereNotNull($id_param)
                        ->latest()
                        ->first();

                    if ($user_chatting) {
                        $user_chatting->unseen_message_count = Chatting::where(['user_id' => $user_chatting->user_id, $id_param => $user_chatting->$id_param, 'seen_by_customer' => '0'])->count();
                    }
                    $chats[] = $user_chatting;
                }
            }
        }
        return response()->json($chats, 200);
    }

    public function get_message(Request $request, $type, $id):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'offset' => 'required',
            'limit' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        if ($type == 'delivery-man') {
            $id_param = 'delivery_man_id';
            $sent_by = 'sent_by_delivery_man';
            $with = 'deliveryMan';
        } elseif ($type == 'seller') {
            return response()->json(['message' => 'Customer-to-Vendor chat is disabled.'], 403);
        } elseif ($type == 'admin') {
            $id_param = 'admin_id';
            $sent_by = 'sent_by_admin';
            $with = 'admin';
        } else {
            return response()->json(['message' => translate('Invalid Chatting Type!')], 403);
        }

        $query = Chatting::with($with)->where(['user_id' => $request->user()->id, $id_param => $id]);
        if ($request->filled('order_id')) {
            $query->where('order_id', $request->order_id);
        }
        $query = $query->latest();

        if (!empty($query->get())) {
            $message = $query->paginate($request->limit, ['*'], 'page', $request->offset);
            $message?->map(function ($conversation) {
                if (!is_null($conversation->attachment_full_url) && count($conversation->attachment_full_url) > 0) {
                    $attachmentData = [];
                    foreach ($conversation->attachment_full_url as $key => $attachment) {
                        $attachmentData[] = (object)$this->getAttachmentData($attachment);
                    }
                    $conversation->attachment = $attachmentData;
                } else {
                    $conversation->attachment = [];
                }
            });
            $query->where($sent_by, 1)->update(['seen_by_customer' => 1]);

            $order = $request->filled('order_id') ? \App\Models\Order::find($request->order_id) : null;
            $is_active = $order ? !in_array($order->order_status, ['delivered', 'canceled', 'returned', 'failed']) : true;

            $data = [];
            $data['total_size'] = $message->total();
            $data['limit'] = $request->limit;
            $data['offset'] = $request->offset;
            $data['is_active'] = $is_active;
            $data['order_id'] = $request->order_id ? (int)$request->order_id : null;
            $data['message'] = $message->items();
            return response()->json($data, 200);
        }
        return response()->json(['message' => translate('no messages found!')], 200);

    }

    public function send_message(CustomerSendMessageRequest $request, $type):JsonResponse
    {
        $uploadMaxFileSize = ini_get('upload_max_filesize');
        if (strpos($uploadMaxFileSize, 'G') !== false) {
            $uploadMaxFileSize = str_replace('G', '', $uploadMaxFileSize);
            $uploadMaxFileSize = (int)$uploadMaxFileSize * 1024 * 1024;
        } elseif (strpos($uploadMaxFileSize, 'M') !== false) {
            $uploadMaxFileSize = str_replace('M', '', $uploadMaxFileSize);
            $uploadMaxFileSize = (int)$uploadMaxFileSize * 1024 * 1024;
        }

        $attachment = [];
        if ($request->file('media')) {
            foreach ($request['media'] as $image) {
                if (in_array('.'.$image->getClientOriginalExtension(), GlobalConstant::VIDEO_EXTENSION)) {
                    $attachment[] = [
                        'file_name' => ImageManager::file_upload(dir: 'chatting/', format: $image->getClientOriginalExtension(), file: $image),
                        'storage' => getWebConfig(name: 'storage_connection_type') ?? 'public',
                    ];
                } else {
                    $attachment[] = [
                        'file_name' => ImageManager::upload('chatting/', 'webp', $image),
                        'storage' => getWebConfig(name: 'storage_connection_type') ?? 'public',
                    ];
                }
            }
        }
        if ($request->file('file')) {
            foreach ($request['file'] as $file) {
                $attachment[] = [
                    'file_name' => ImageManager::file_upload(dir: 'chatting/', format: $file->getClientOriginalExtension(), file: $file),
                    'storage' => getWebConfig(name: 'storage_connection_type') ?? 'public',
                ];
            }
        }
        $chatting = new Chatting();
        $chatting->user_id = $request->user()->id;
        $chatting->message = $request['message'];
        $chatting->attachment = json_encode($attachment);
        $chatting->sent_by_customer = 1;
        $chatting->seen_by_customer = 1;
        $messageForm = User::find($request->user()->id);

        if ($request->filled('order_id')) {
            $order = \App\Models\Order::where('id', $request->order_id)
                ->where('customer_id', $request->user()->id)
                ->first();
            if (!$order) {
                return response()->json(['message' => translate('Invalid order')], 403);
            }
            if (in_array($order->order_status, ['delivered', 'canceled', 'returned', 'failed'])) {
                return response()->json(['message' => translate('This order is delivered. Chat is closed.')], 403);
            }
            $chatting->order_id = $order->id;
            $chatting->is_active = 1;
        }

        if ($type == 'seller') {
            return response()->json(['message' => 'Customer-to-Vendor chat is disabled.'], 403);
        } elseif ($type == 'admin') {
            $chatting->admin_id = 0;
            $chatting->seller_id = null;
            $chatting->shop_id = null;
            $chatting->seen_by_admin = 0;
            $chatting->notification_receiver = 'admin';
            $chatting->chat_type = 'customer_to_admin';
        } elseif ($type == 'delivery-man') {
            // Gating: Only allow if an active assigned order exists
            $orderQuery = \App\Models\Order::where('customer_id', $request->user()->id)
                                            ->where('delivery_man_id', $request->id)
                                            ->whereNotIn('order_status', ['delivered', 'canceled', 'returned', 'failed']);
            if ($request->filled('order_id')) {
                $orderQuery->where('id', $request->order_id);
            }
            $orderExists = $orderQuery->exists();

            if (!$orderExists) {
                return response()->json(['message' => translate('You can only chat with delivery riders for active assigned orders.')], 403);
            }

            $chatting->delivery_man_id = $request->id;
            $chatting->seen_by_delivery_man = 0;
            $chatting->notification_receiver = 'deliveryman';
            $chatting->chat_type = 'customer_to_delivery';
            $deliveryMan = DeliveryMan::find($request->id);
            event(new ChattingEvent(key: 'message_from_customer', type: 'delivery_man', userData: $deliveryMan, messageForm: $messageForm));
        } else {
            return response()->json(translate('Invalid_Chatting_Type'), 403);
        }
        if ($chatting->save()) {
            return response()->json(['message' => $request['message'], 'time' => now(), 'attachment' => $attachment], 200);
        } else {
            return response()->json(['message' => translate('Message_sending_failed')], 403);
        }
    }

    public function seen_message(Request $request, $type):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        if ($type == 'delivery-man') {
            $id_param = 'delivery_man_id';
        } elseif ($type == 'seller') {
            return response()->json(['message' => 'Customer-to-Vendor chat is disabled.'], 403);
        } else {
            return response()->json(['message' => 'Invalid Chatting Type'], 403);
        }

        $chatting = Chatting::where(['user_id' => $request->user()->id, $id_param => $request->id])->update(['seen_by_customer' => 1]);

        if ($chatting) {
            return response()->json(['message' => 'Successfully seen'], 200);
        } else {
            return response()->json(['message' => 'Fail'], 403);
        }
    }

    private function getAttachmentData($attachment): array
    {
        $extension = strtolower((string)strrchr($attachment['path'], '.'));
        $audioExtensions = ['.m4a', '.mp3', '.wav', '.aac', '.ogg', '.opus', '.wma', '.amr'];
        if (in_array($extension, $audioExtensions)) {
            $type = 'audio';
        } elseif (in_array($extension, GlobalConstant::DOCUMENT_EXTENSION)) {
            $type = 'file';
        } else {
            $type = 'media';
        }
        $path = $attachment['status'] == 200 ? $attachment['path'] : null;
        $size = $attachment['status'] == 200 ? FileManagerLogic::getFileSize(path: $path) : null;
        return [
            'type' => $type,
            'key' => $attachment['key'],
            'path' => $path,
            'size' => $size
        ];
    }
}
