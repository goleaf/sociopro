@foreach ($posts as $loopIndex => $post)
    @continue(empty($post->location))
    @continue($viewData->isBlockedPost($post, $user_info ?? auth()->user()))
    @continue($post->post_type === 'fundraiser')

    @include('frontend.main_content.single-post', [
        'post' => $post,
        'type' => $type ?? null,
        'subscription' => $subscription ?? null,
        'has_memories' => $has_memories ?? null,
        'loopIndex' => $loopIndex,
        'embeddedPostCard' => true,
    ])
@endforeach
