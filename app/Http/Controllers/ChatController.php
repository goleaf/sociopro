<?php

namespace App\Http\Controllers;

use App\Actions\Chat\FindOrCreateMessageThreadAction;
use App\Actions\Chat\StoreChatMessageAction;
use App\Http\Requests\Chat\StoreChatMessageRequest;
use App\Models\Chat;
use App\Models\Marketplace;
use App\Models\MediaFile;
use App\Models\MessageThread;
use App\Models\User;
use App\Support\Files\FileUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    private const CHAT_VIDEO_EXTENSIONS = ['avi', 'mp4', 'webm', 'mov', 'wmv', 'mkv'];

    private const CHAT_UPLOAD_MIME_RULE = 'mimes:jpeg,jpg,png,gif,jfif,mp4,mov,wmv,mkv,webm,avi';

    public function chat(Request $request, $receiver = null, $product = null)
    {
        if ($product !== null) {
            $this->authorizeMarketplaceProductChat((int) $receiver, (int) $product);
        }

        $user = $request->user();
        $userId = (int) $user->id;
        $receiverId = (int) $receiver;
        $messageThread = MessageThread::betweenUsers($receiverId, $userId)->first();

        $receiverData = User::find($receiverId);
        if (! empty($messageThread)) {
            Chat::forThread($messageThread->id)
                ->unreadForReceiver($receiverId)
                ->update(['read_status' => '1']);
            $message = Chat::forThread($messageThread->id)->latest('id')->limit(20)->get();
        } else {
            $message = [];
        }
        if (isset($product) && $product != null) {
            $product_url = route('single.product', $product);
        } else {
            $product_url = null;
        }
        $previousChatList = MessageThread::forParticipant($userId)->orderBy('id', 'DESC')->get();

        return view('frontend.chat.index', [
            'reciver_data' => $receiverData,
            'message' => $message,
            'previousChatList' => $previousChatList,
            'product_url' => $product_url,
            'product' => $product,
        ]);
    }

    public function chat_save(
        StoreChatMessageRequest $request,
        FindOrCreateMessageThreadAction $findOrCreateMessageThread,
        StoreChatMessageAction $storeChatMessage
    ) {
        $receiverId = $this->receiverIdFromRequest($request);
        $user = $request->user();

        if ($request->filled('product_id')) {
            $this->authorizeMarketplaceProductChat($receiverId, $request->integer('product_id'));
        }

        $receiver = User::findOrFail($receiverId);
        $messageThread = $findOrCreateMessageThread->execute($user, $receiver, $request->input('messagecenter'));
        $chat = $storeChatMessage->execute(
            sender: $user,
            receiver: $receiver,
            messageThread: $messageThread,
            message: $request->input('message'),
            chatCenter: $request->input('messagecenter'),
            thumbsup: $request->input('thumbsup'),
            file: '1',
        );

        $validationError = $this->storeChatAttachments($request, $chat, $user);
        if ($validationError !== null) {
            return json_encode($validationError);
        }

        return $this->chatSaveResponse($messageThread, $receiverId, $request->filled('product_id'));
    }

    public function remove_chat(Request $request, $id)
    {
        $chatMessage = Chat::findOrFail($id);

        abort_unless($chatMessage->isParticipant((int) $request->user()->id), 403);

        $chatMessage->delete();

        return redirect()->back();
    }

    public function react_chat(Request $request)
    {
        $formData = $request->only([
            'requestType',
            'messageId',
            'react',
        ]);
        if ($formData['requestType'] == 'update') {
            $chat = Chat::findOrFail($formData['messageId']);

            abort_unless($chat->isParticipant((int) $request->user()->id), 403);

            $chat->react = $formData['react'];
            $chat->save();

            $page_data['message'] = $chat;
            $message = view('frontend.chat.chat_react', $page_data)->render();
            $response = ['elemSelector' => '#ShowReactId_'.$formData['messageId'], 'content' => $message];

            return json_encode($response);
        }
    }

    public function search_chat(Request $request)
    {
        $messageThreadsUserId = [];
        $search = (string) $request->query('search', '');
        $currentUser = $request->user();
        $currentUserId = (int) $currentUser->id;
        $view_btn_text = 'View Profile';
        $output = '';

        $myMessageThreads = MessageThread::forParticipant($currentUserId)->get();
        foreach ($myMessageThreads as $myMessageThread) {
            if ($myMessageThread->sender_id == $currentUserId) {
                array_push($messageThreadsUserId, $myMessageThread->receiver_id);
            } elseif ($myMessageThread->receiver_id == $currentUserId) {
                array_push($messageThreadsUserId, $myMessageThread->sender_id);
            }
        }

        $users = User::whereIn('id', $messageThreadsUserId)->where('name', 'like', '%'.$search.'%')->get();

        foreach ($users as $key => $user) {
            $lastMsg = Chat::betweenUsers($currentUserId, $user->id)->limit(1)->orderBy('id', 'desc')->first();

            $output .= view('frontend.chat.search-contact', [
                'chatUrl' => route('chat', $user->id),
                'imageUrl' => get_user_image($user->photo, 'optimized'),
                'isThumbsup' => (int) $lastMsg->thumbsup === 1,
                'lastMessage' => (string) $lastMsg->message,
                'userName' => (string) $user->name,
                'viewProfileText' => get_phrase($view_btn_text),
            ])->render();
        }

        return $output;
    }

    public function chat_load(Request $request)
    {
        $id = $request->integer('id');
        $user = $request->user();
        $messageThread = MessageThread::betweenUsers((int) $user->id, (int) $id)->first();
        $page_data['message'] = Chat::forThread($messageThread->id)
            ->unreadForReceiver((int) $user->id)
            ->get();
        $message = view('frontend.chat.single-message', $page_data)->render();
        $this->markUnreadMessagesReadForUser($user, (int) $id);
        $response = ['appendElement' => '#message_body', 'content' => $message];

        return json_encode($response);
    }

    public function chat_read_option(Request $request)
    {
        $receiverId = $request->integer('id');

        if ($receiverId > 0) {
            $this->markUnreadMessagesReadForUser($request->user(), $receiverId);
        }

        return response('');
    }

    private function receiverIdFromRequest(Request $request): int
    {
        return $request->integer('receiver_id') ?: $request->integer('reciver_id');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function storeChatAttachments(Request $request, Chat $chat, User $user): ?array
    {
        $files = $request->multiple_files;

        if (! is_array($files) || ($files[0] ?? null) === null) {
            return null;
        }

        $validator = Validator::make($files, ['multiple_files' => self::CHAT_UPLOAD_MIME_RULE]);
        if ($validator->fails()) {
            return ['validationError' => $validator->getMessageBag()->toArray()];
        }

        foreach ($files as $mediaFile) {
            $fileName = random(40);
            $fileExtension = strtolower($mediaFile->getClientOriginalExtension());
            if (in_array($fileExtension, self::CHAT_VIDEO_EXTENSIONS, true)) {
                FileUploader::upload($mediaFile, 'public/storage/chat/videos/'.$fileName.'.'.$fileExtension);
                $fileType = 'video';
            } else {
                FileUploader::upload($mediaFile, 'public/storage/chat/images/'.$fileName, 1000, null, 300);
                $fileType = 'image';
            }

            $mediaFileData = [
                'user_id' => $user->id,
                'chat_id' => $chat->id,
                'file_name' => $fileName,
                'file_type' => $fileType,
                'privacy' => 'public',
            ];
            $mediaFileData['created_at'] = time();
            $mediaFileData['updated_at'] = $mediaFileData['created_at'];
            MediaFile::create($mediaFileData);
        }

        return null;
    }

    private function chatSaveResponse(MessageThread $messageThread, int $receiverId, bool $hasProduct)
    {
        $page_data['message'] = Chat::forThread($messageThread->id)->latest('id')->limit(1)->get();
        $message = view('frontend.chat.single-message', $page_data)->render();
        $url = route('chat', $receiverId);
        if ($hasProduct) {
            $response = ['appendElement' => '#message_body', 'content' => $message, 'clickTo' => '#messageResetBox', 'replaceUrl' => '#message_body', 'url' => $url];
        } else {
            $response = ['appendElement' => '#message_body', 'content' => $message, 'clickTo' => '#messageResetBox'];
        }

        return json_encode($response);
    }

    private function markUnreadMessagesReadForUser(User $user, int $receiverId): int
    {
        $userId = (int) $user->id;
        $messageThread = MessageThread::betweenUsers($userId, $receiverId)
            ->select(['id'])
            ->first();

        if (! $messageThread) {
            return 0;
        }

        return Chat::forThread($messageThread->id)
            ->unreadForReceiver($userId)
            ->update(['read_status' => '1']);
    }

    private function authorizeMarketplaceProductChat(int $receiverId, int $productId): void
    {
        $marketplace = Marketplace::findOrFail($productId);

        abort_unless($receiverId === (int) $marketplace->user_id, 403);

        Gate::authorize('messageSeller', $marketplace);
    }
}
