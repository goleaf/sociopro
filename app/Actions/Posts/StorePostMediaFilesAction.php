<?php

namespace App\Actions\Posts;

use App\Models\MediaFile;
use App\Models\User;
use App\Support\Files\FileUploader;
use Illuminate\Http\Request;

class StorePostMediaFilesAction
{
    public function handle(int|string $postId, User $user, Request $request): void
    {
        if (! is_array($request->multiple_files) || ($request->multiple_files[0] ?? null) === null) {
            return;
        }

        foreach ($request->multiple_files as $mediaFile) {
            $fileName = random(40);
            $extension = strtolower($mediaFile->getClientOriginalExtension());
            if (in_array($extension, ['avi', 'mp4', 'webm', 'mov', 'wmv', 'mkv'], true)) {
                FileUploader::upload($mediaFile, 'public/storage/post/videos/'.$fileName.'.'.$extension);
                $fileType = 'video';
            } else {
                FileUploader::upload($mediaFile, 'public/storage/post/images/'.$fileName.'.'.$extension, 1000, null, 300);
                $fileType = 'image';
            }

            $createdAt = now()->toDateTimeString();
            $mediaFileData = [
                'user_id' => $user->id,
                'post_id' => $postId,
                'file_name' => $fileName.'.'.$extension,
                'file_type' => $fileType,
                'privacy' => $request->privacy,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];

            if (isset($request->page_id) && ! empty($request->page_id)) {
                $mediaFileData['page_id'] = $request->page_id;
            } elseif (isset($request->group_id) && ! empty($request->group_id)) {
                $mediaFileData['group_id'] = $request->group_id;
            }

            MediaFile::create($mediaFileData);
        }
    }
}
