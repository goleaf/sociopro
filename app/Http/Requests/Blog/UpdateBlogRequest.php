<?php

namespace App\Http\Requests\Blog;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateBlogRequest extends BlogRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user instanceof User) {
            return false;
        }

        $blog = Blog::query()->find($this->route('id'));

        return ! $blog instanceof Blog || Gate::forUser($user)->allows('update', $blog);
    }
}
