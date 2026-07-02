<?php

namespace App\Http\Controllers;

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
    public function chat($receiver = null, $product = null)
    {
        if ($product !== null) {
            $this->authorizeMarketplaceProductChat((int) $receiver, (int) $product);
        }

        $userId = auth()->user()->id;
        $receiverId = (int) $receiver;
        $messageThread = MessageThread::betweenParticipants($receiverId, $userId)->first();

        $receiverData = User::find($receiver);
        if (! empty($messageThread)) {
            Chat::forMessageThread($messageThread->id)
                ->unreadForReceiver($receiverId)
                ->update(['read_status' => '1']);
            $message = Chat::forMessageThread($messageThread->id)->orderBy('id', 'DESC')->limit('20')->get();
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

    public function chat_save(Request $request)
    {
        $receiverId = $this->receiverIdFromRequest($request);
        $userId = auth()->user()->id;

        if ($request->filled('product_id')) {
            $this->authorizeMarketplaceProductChat($receiverId, $request->integer('product_id'));
        }

        $firstMessageThread = MessageThread::betweenParticipants($receiverId, $userId)->first();
        $messageThreadCount = MessageThread::betweenParticipants($receiverId, $userId)->count();

        if ($messageThreadCount <= 0) {
            $messageThread = new MessageThread;
            $messageThread->sender_id = $userId;
            $messageThread->receiver_id = $receiverId;
            $messageThread->chat_center = $request->messagecenter;
            $done = $messageThread->save();
            if ($done) {
                $chat = new Chat;
                $chat->receiver_id = $receiverId;
                $chat->sender_id = $userId;
                $chat->chat_center = $request->messagecenter;
                $chat->message = $request->message;
                $chat->message_thread_id = $messageThread->id;
                $chat->thumbsup = $request->thumbsup;
                $chat->file = '1';
                $chat->save();
                $last_chat_id = $chat->id;

                if (is_array($request->multiple_files) && $request->multiple_files[0] != null) {
                    // Data validation
                    $rules = ['multiple_files' => 'mimes:jpeg,jpg,png,gif,jfif,mp4,mov,wmv,mkv,webm,avi'];
                    $validator = Validator::make($request->multiple_files, $rules);
                    if ($validator->fails()) {
                        return json_encode(['validationError' => $validator->getMessageBag()->toArray()]);
                    }

                    foreach ($request->multiple_files as $key => $media_file) {
                        $file_name = random(40);
                        $file_extention = strtolower($media_file->getClientOriginalExtension());
                        if ($file_extention == 'avi' || $file_extention == 'mp4' || $file_extention == 'webm' || $file_extention == 'mov' || $file_extention == 'wmv' || $file_extention == 'mkv') {
                            FileUploader::upload($media_file, 'public/storage/chat/videos/'.$file_name.'.'.$file_extention);
                            $file_type = 'video';
                        } else {
                            FileUploader::upload($media_file, 'public/storage/chat/images/'.$file_name, 1000, null, 300);
                            $file_type = 'image';
                        }
                        // $file_name = $file_name.'.'.$file_extention;

                        $media_file_data = ['user_id' => auth()->user()->id, 'chat_id' => $last_chat_id, 'file_name' => $file_name, 'file_type' => $file_type, 'privacy' => 'public'];
                        $media_file_data['created_at'] = time();
                        $media_file_data['updated_at'] = $media_file_data['created_at'];
                        MediaFile::create($media_file_data);
                    }
                }
                $page_data['message'] = Chat::forMessageThread($messageThread->id)->orderBy('id', 'DESC')->limit('1')->get();
                $message = view('frontend.chat.single-message', $page_data)->render();
                $url = route('chat', $receiverId);
                if (isset($request->product_id) && ! empty($request->product_id)) {
                    $response = ['appendElement' => '#message_body', 'content' => $message, 'clickTo' => '#messageResetBox', 'replaceUrl' => '#message_body', 'url' => $url];
                } else {
                    $response = ['appendElement' => '#message_body', 'content' => $message, 'clickTo' => '#messageResetBox'];
                }

                return json_encode($response);
            }
        } else {
            $chat = new Chat;
            $chat->receiver_id = $receiverId;
            $chat->sender_id = $userId;
            $chat->chat_center = $request->messagecenter;
            $chat->message = $request->message;
            $chat->message_thread_id = $firstMessageThread->id;
            $chat->thumbsup = $request->thumbsup;
            $chat->file = '1';
            $chat->save();
            $last_chat_id = $chat->id;

            if (is_array($request->multiple_files) && $request->multiple_files[0] != null) {
                // Data validation
                $rules = ['multiple_files' => 'mimes:jpeg,jpg,png,gif,jfif,mp4,mov,wmv,mkv,webm,avi'];
                $validator = Validator::make($request->multiple_files, $rules);
                if ($validator->fails()) {
                    return json_encode(['validationError' => $validator->getMessageBag()->toArray()]);
                }

                foreach ($request->multiple_files as $key => $media_file) {
                    $file_name = random(40);
                    $file_extention = strtolower($media_file->getClientOriginalExtension());
                    if ($file_extention == 'avi' || $file_extention == 'mp4' || $file_extention == 'webm' || $file_extention == 'mov' || $file_extention == 'wmv' || $file_extention == 'mkv') {
                        FileUploader::upload($media_file, 'public/storage/chat/videos/'.$file_name.'.'.$file_extention);
                        $file_type = 'video';
                    } else {
                        FileUploader::upload($media_file, 'public/storage/chat/images/'.$file_name, 1000, null, 300);
                        $file_type = 'image';
                    }
                    // $file_name = $file_name.'.'.$file_extention;

                    $media_file_data = ['user_id' => auth()->user()->id, 'chat_id' => $last_chat_id, 'file_name' => $file_name, 'file_type' => $file_type, 'privacy' => 'public'];

                    $media_file_data['chat_id'] = $chat->id;
                    $media_file_data['created_at'] = time();
                    $media_file_data['updated_at'] = $media_file_data['created_at'];
                    MediaFile::create($media_file_data);
                }
            }
            $page_data['message'] = Chat::forMessageThread($firstMessageThread->id)->orderBy('id', 'DESC')->limit('1')->get();
            $message = view('frontend.chat.single-message', $page_data)->render();
            $url = route('chat', $receiverId);
            if (isset($request->product_id) && ! empty($request->product_id)) {
                $response = ['appendElement' => '#message_body', 'content' => $message, 'clickTo' => '#messageResetBox', 'replaceUrl' => '#message_body', 'url' => $url];
            } else {
                $response = ['appendElement' => '#message_body', 'content' => $message, 'clickTo' => '#messageResetBox'];
            }

            return json_encode($response);
        }
    }

    public function remove_chat($id)
    {
        $chat = Chat::find($id);
        $chat->delete();

        return redirect()->back();
    }

    public function react_chat(Request $request)
    {
        $form_data = $request->only([
            'requestType',
            'messageId',
            'react',
        ]);
        if ($form_data['requestType'] == 'update') {
            $chat = Chat::find($form_data['messageId']);
            $chat->react = $form_data['react'];
            $chat->save();

            $page_data['message'] = Chat::find($form_data['messageId']);
            $message = view('frontend.chat.chat_react', $page_data)->render();
            $response = ['elemSelector' => '#ShowReactId_'.$form_data['messageId'], 'content' => $message];

            return json_encode($response);
        }
    }

    public function search_chat()
    {
        $messageThreadsUserId = [];
        $search = $_GET['search'];
        $view_btn_text = 'View Profile';
        $output = '';

        $myMessageThreads = MessageThread::forParticipant(auth()->user()->id)->get();
        foreach ($myMessageThreads as $myMessageThread) {
            if ($myMessageThread->sender_id == auth()->user()->id) {
                array_push($messageThreadsUserId, $myMessageThread->receiver_id);
            } elseif ($myMessageThread->receiver_id == auth()->user()->id) {
                array_push($messageThreadsUserId, $myMessageThread->sender_id);
            }
        }

        $users = User::whereIn('id', $messageThreadsUserId)->where('name', 'like', '%'.$search.'%')->get();

        foreach ($users as $key => $user) {
            $lastMsg = Chat::betweenParticipants(auth()->user()->id, $user->id)->limit(1)->orderBy('id', 'desc')->first();

            $lastText = $lastMsg->thumbsup == '1' ? "<i class='fa-solid fa-thumbs-up'></i>" : $lastMsg->message;
            $output .= '<div class="single-contact d-flex align-items-center justify-content-between">
                        <div class="avatar d-flex align-items-center">
                            <a href="'.route('chat', $user->id).' " class="d-flex align-items-center">
                                <div class="avatar me-3">
                                    <img src=" '.get_user_image($user->photo, 'optimized').' " class="img-fluid rounded-circle h-39" alt="">
                                    
                                </div>
                            </a>
                            <div class="avatar-info">
                                <a href=" '.route('chat', $user->id).' "><h3 class="h6 mb-0"> '.$user->name.'</h3></a>
                                <span>'.$lastText.'</span>
                            </div>
                        </div>
                        <div class="m-user-action">
                            <div class="post-controls dropdown dotted">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown"
                                    role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="#"><i class="fa fa-user"></i>'.get_phrase($view_btn_text).'</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>';
        }

        return $output;
    }

    public function chat_load()
    {
        $id = $_GET['id'];
        $messageThread = MessageThread::betweenParticipants(auth()->user()->id, (int) $id)->first();
        $page_data['message'] = Chat::forMessageThread($messageThread->id)
            ->unreadForReceiver(auth()->user()->id)
            ->get();
        $message = view('frontend.chat.single-message', $page_data)->render();
        $this->chat_read_option($id);
        $response = ['appendElement' => '#message_body', 'content' => $message];

        return json_encode($response);
    }

    public function chat_read_option($id)
    {
        $messageThread = MessageThread::betweenParticipants(auth()->user()->id, (int) $id)->first();
        if (! empty($messageThread)) {
            $done = Chat::forMessageThread($messageThread->id)
                ->unreadForReceiver(auth()->user()->id)
                ->update(['read_status' => '1']);
        }
    }

    // not using right now if need then we can use this
    public function chat_read_optionN()
    {
        $id = $_GET['id'];
        $messageThread = MessageThread::betweenParticipants(auth()->user()->id, (int) $id)->first();
        if (! empty($messageThread)) {
            return $done = Chat::forMessageThread($messageThread->id)
                ->unreadForReceiver(auth()->user()->id)
                ->update(['read_status' => '1']);

            return $done;
        }
    }

    private function receiverIdFromRequest(Request $request): int
    {
        return $request->integer('receiver_id') ?: $request->integer('reciver_id');
    }

    private function authorizeMarketplaceProductChat(int $receiverId, int $productId): void
    {
        $marketplace = Marketplace::findOrFail($productId);

        abort_unless($receiverId === (int) $marketplace->user_id, 403);

        Gate::authorize('messageSeller', $marketplace);
    }
}
