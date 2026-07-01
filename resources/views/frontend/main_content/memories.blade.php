@php $user_info = Auth()->user() @endphp

<div class="memories single-item-countable single-entry p-0">
    <div class="entry-inner p-0">
        <img src="{{ asset('assets/frontend/images/memories_background.png') }}" class="w-100" alt="memories-banner-area">
        <p class="text-center mb-0 pb-3 mt-2 px-5">
            @if ($posts->count() > 0)
                {{ get_phrase('We hope you enjoy revisiting and sharing your memories on Sociopro from the most recent moments to those from days gone by.') }}
            @else
                <span class="d-block">{{ get_phrase('No memories to view or share today.') }}</span>
                <span class="d-block">{{ get_phrase("We'll notify you when there are some to reminisce about") }}</span>
            @endif
        </p>
    </div>
</div>

<div id="memories_content">
    @include('frontend.main_content.posts')
</div>

@include('frontend.main_content.scripts')
