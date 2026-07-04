<?php

namespace App\Actions\Posts;

use App\Models\MediaFile;

class DeletePostMediaFileAction
{
    /**
     * @return array{alertMessage: string, fadeOutElem: string}
     */
    public function handle(MediaFile $mediaFile): array
    {
        $mediaFileId = $mediaFile->id;

        remove_file('public/storage/post/images/'.$mediaFile->file_name);
        $mediaFile->delete();

        return [
            'alertMessage' => get_phrase('Image deleted successfully'),
            'fadeOutElem' => '#previous-uploaded-img-'.$mediaFileId,
        ];
    }
}
