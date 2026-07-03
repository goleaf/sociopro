<?php

use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Support\Facades\File;

if (! function_exists('api_helper_user_columns')) {
    /**
     * @return list<string>
     */
    function api_helper_user_columns(): array
    {
        return [
            'id',
            'user_role',
            'username',
            'email',
            'name',
            'nickname',
            'friends',
            'followers',
            'gender',
            'studied_at',
            'address',
            'profession',
            'job',
            'marital_status',
            'phone',
            'date_of_birth',
            'about',
            'save_post',
            'photo',
            'cover_photo',
            'status',
            'lastActive',
            'timezone',
            'email_verified_at',
            'created_at',
            'updated_at',
            'profile_status',
        ];
    }
}

if (! function_exists('api_helper_remote_url')) {
    function api_helper_remote_url(mixed $fileName): ?string
    {
        $fileName = (string) $fileName;

        return str_contains($fileName, 'https://') ? $fileName : null;
    }
}

if (! function_exists('api_helper_folder')) {
    function api_helper_folder(mixed $folderName): string
    {
        $folderName = trim((string) $folderName, '/');

        return $folderName === '' ? '' : $folderName.'/';
    }
}

if (! function_exists('api_helper_existing_file_url')) {
    function api_helper_existing_file_url(string $relativePath, string $defaultRelativePath, bool $requireFile = true): string
    {
        $path = base_path($relativePath);
        $exists = File::exists($path) && (! $requireFile || File::isFile($path));

        return url($exists ? $relativePath : $defaultRelativePath);
    }
}

if (! function_exists('api_helper_friends_list')) {
    /**
     * @return list<mixed>
     */
    function api_helper_friends_list(mixed $friends): array
    {
        $decoded = json_decode((string) $friends, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }
}

if (! function_exists('get_user_info')) {
    function get_user_info($user_id = ''): ?User
    {
        return User::query()
            ->select(api_helper_user_columns())
            ->whereKey($user_id)
            ->first();
    }
}

if (! function_exists('get_user_images')) {
    function get_user_images($file_name_or_user_id = '', $optimized = ''): string
    {
        $fileName = $file_name_or_user_id === '' ? 'default.png' : (string) $file_name_or_user_id;

        if (is_numeric($file_name_or_user_id)) {
            $fileName = (string) User::query()
                ->whereKey($file_name_or_user_id)
                ->value('photo');
        }

        $remoteUrl = api_helper_remote_url($fileName);
        if ($remoteUrl !== null) {
            return $remoteUrl;
        }

        return api_helper_existing_file_url(
            'public/storage/userimage/'.api_helper_folder($optimized).$fileName,
            'public/storage/userimage/default.png'
        );
    }
}

if (! function_exists('get_cover_photos')) {
    function get_cover_photos($file_name_or_user_id = '', $optimized = ''): string
    {
        if ($file_name_or_user_id === '') {
            $file_name_or_user_id = auth()->user()?->cover_photo ?? '';
        }

        $fileName = (string) $file_name_or_user_id;

        if (is_numeric($file_name_or_user_id)) {
            $fileName = (string) User::query()
                ->whereKey($file_name_or_user_id)
                ->value('cover_photo');
        }

        $remoteUrl = api_helper_remote_url($fileName);
        if ($remoteUrl !== null) {
            return $remoteUrl;
        }

        return api_helper_existing_file_url(
            'public/storage/cover_photo/'.api_helper_folder($optimized).$fileName,
            'public/storage/cover_photo/default.jpg'
        );
    }
}

if (! function_exists('get_post_images')) {
    function get_post_images($file_name = '', $optimized = ''): string
    {
        $remoteUrl = api_helper_remote_url($file_name);
        if ($remoteUrl !== null) {
            return $remoteUrl;
        }

        return api_helper_existing_file_url(
            'public/storage/post/images/'.api_helper_folder($optimized).(string) $file_name,
            'public/storage/post/images/default.png'
        );
    }
}

if (! function_exists('get_post_videos')) {
    function get_post_videos($file_name = '', $optimized = ''): string
    {
        $remoteUrl = api_helper_remote_url($file_name);
        if ($remoteUrl !== null) {
            return $remoteUrl;
        }

        return api_helper_existing_file_url(
            'public/storage/post/videos/'.api_helper_folder($optimized).(string) $file_name,
            'public/storage/post/videos/default.png',
            false
        );
    }
}

if (! function_exists('get_story_images')) {
    function get_story_images($file_name = '', $optimized = ''): string
    {
        $remoteUrl = api_helper_remote_url($file_name);
        if ($remoteUrl !== null) {
            return $remoteUrl;
        }

        return api_helper_existing_file_url(
            'public/storage/story/images/'.api_helper_folder($optimized).(string) $file_name,
            'public/storage/story/images/default.jpg'
        );
    }
}

if (! function_exists('get_story_videos')) {
    function get_story_videos($file_name = '', $optimized = ''): string
    {
        $remoteUrl = api_helper_remote_url($file_name);
        if ($remoteUrl !== null) {
            return $remoteUrl;
        }

        return api_helper_existing_file_url(
            'public/storage/story/videos/'.api_helper_folder($optimized).(string) $file_name,
            'public/storage/story/videos/default.jpg',
            false
        );
    }
}

if (! function_exists('get_group_logos')) {
    function get_group_logos($file_name = '', $foldername = ''): string
    {
        $remoteUrl = api_helper_remote_url($file_name);
        if ($remoteUrl !== null) {
            return $remoteUrl;
        }

        $folder = api_helper_folder($foldername);

        if ($file_name !== '') {
            return url('public/storage/groups/'.$folder.(string) $file_name);
        }

        return url('public/storage/groups/'.$folder.'default/default.jpg');
    }
}

if (! function_exists('get_group_cover_photos')) {
    function get_group_cover_photos($file_name = '', $foldername = ''): string
    {
        $remoteUrl = api_helper_remote_url($file_name);
        if ($remoteUrl !== null) {
            return $remoteUrl;
        }

        $folder = api_helper_folder($foldername);

        if ($file_name !== '') {
            return url('public/storage/groups/'.$folder.(string) $file_name);
        }

        return url('public/storage/groups/'.$folder.'default/default.jpg');
    }
}

if (! function_exists('get_all_assets_photos')) {
    function get_all_assets_photos($file_name = '', $foldername = '', $main_foldername = ''): string
    {
        $remoteUrl = api_helper_remote_url($file_name);
        if ($remoteUrl !== null) {
            return $remoteUrl;
        }

        $folder = api_helper_folder($foldername);
        $mainFolder = api_helper_folder($main_foldername);

        if ($file_name !== '') {
            return url('public/assets/frontend/'.$mainFolder.$folder.(string) $file_name);
        }

        return url('public/assets/frontend/'.$mainFolder.$folder.'default/default.jpg');
    }
}

if (! function_exists('get_group_event_photos')) {
    function get_group_event_photos($file_name = '', $foldername = '', $main_foldername = ''): string
    {
        $remoteUrl = api_helper_remote_url($file_name);
        if ($remoteUrl !== null) {
            return $remoteUrl;
        }

        $folder = api_helper_folder($foldername);
        $mainFolder = api_helper_folder($main_foldername);

        if ($file_name !== '') {
            return url('public/storage/'.$mainFolder.$folder.(string) $file_name);
        }

        return url('public/storage/'.$mainFolder.$folder.'default/default.jpg');
    }
}

if (! function_exists('get_one_folder_files')) {
    function get_one_folder_files($file_name = '', $foldername = ''): string
    {
        $remoteUrl = api_helper_remote_url($file_name);
        if ($remoteUrl !== null) {
            return $remoteUrl;
        }

        $folder = api_helper_folder($foldername);

        if ($file_name !== '') {
            return url('public/storage/'.$folder.(string) $file_name);
        }

        return url('public/storage/'.$folder.'default/default.jpg');
    }
}

if (! function_exists('members_by_group_id')) {
    /**
     * @return list<array{id: mixed, user_id: mixed, group_id: mixed, name: string|null, photo: string, countfriends: int, matching_friends_count: int}>
     */
    function members_by_group_id($group_id = ''): array
    {
        return GroupMember::query()
            ->select(['id', 'user_id', 'group_id'])
            ->with(['user' => fn ($query) => $query->select(['id', 'name', 'photo', 'friends'])])
            ->where('group_id', $group_id)
            ->get()
            ->filter(fn (GroupMember $member): bool => $member->user !== null)
            ->map(function (GroupMember $member): array {
                $user = $member->user;
                $friendsList = api_helper_friends_list($user->friends);
                $matchingFriendsCount = collect($friendsList)
                    ->filter(fn ($friendId): bool => (string) $friendId === (string) $member->user_id)
                    ->count();

                return [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'group_id' => $member->group_id,
                    'name' => $user->name,
                    'photo' => get_user_images($user->photo),
                    'countfriends' => count($friendsList),
                    'matching_friends_count' => $matchingFriendsCount,
                ];
            })
            ->values()
            ->all();
    }
}
