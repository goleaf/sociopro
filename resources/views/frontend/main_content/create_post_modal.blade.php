@php
    $hugging_face_auth_key =  DB::table('settings')->where('type', 'hugging_face_auth_key')->value('description');

@endphp
<!-- Modal -->
<form class="ajaxForm" id="createPostForm" action="{{ route('create_post') }}" method="post" enctype="multipart/form-data">
    @csrf
    @if(auth()->user()->profile_status == 'lock')
    <input type="hidden" id="post_privacy" name="privacy" value="friends">
    @else
    <input type="hidden" id="post_privacy" name="privacy" value="public">
    @endif
    <input type="hidden" id="post_type" name="post_type" value="general">
    @isset($event_id)
        <input type="hidden" id="event_id" name="event_id" value="{{ $event_id }}">
        <input type="hidden" id="publisher" name="publisher" value="event">
    @endisset
    @isset($page_id)
        <input type="hidden" id="page_id" name="page_id" value="{{ $page_id }}">
        <input type="hidden" id="publisher" name="publisher" value="page">
    @endisset

    @isset($group_id)
        <input type="hidden" id="group_id" name="group_id" value="{{ $group_id }}">
        <input type="hidden" id="publisher" name="publisher" value="group">
    @endisset

    @isset($paid_content_id)
        <input type="hidden" id="paid_content_id" name="paid_content_id" value="{{ $paid_content_id }}">
        <input type="hidden" id="publisher" name="publisher" value="paid_content">
    @endisset

    <div class="modal fade post_creates" id="createPost" tabindex="-1" aria-labelledby="createPostLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-center">
                    <h5 class="modal-title" id="exampleModalLabel">{{ get_phrase('Create Post') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i
                            class="fa fa-close"></i></button>
                </div>
                <div class="modal-body">
                    <div class="entry-header">
                        @if (isset($page_id) && !empty($page_id))
                            @php
                                $page = \App\Models\Page::find($page_id);
                            @endphp
                            <a href="{{ route('single.page', $page_id) }}"
                                class="author-thumb d-flex">
                                <img src="{{ get_page_logo($page->logo, 'logo') }}" width="40px"
                                    class="rounded-circle" alt="">
                                <h6 class="ms-2">{{ $page->title }}</h6>
                            </a>
                        @else
                            <a href="{{ route('profile') }}" class="author-thumb d-flex">
                                <img src="{{ get_user_image($user_info->photo, 'optimized') }}" width="40px"
                                    class="rounded-circle" alt="">
                                <h6 class="ms-2">{{ $user_info->name }}</h6>
                            </a>
                        @endif
                        <div class="entry-status ct_status">
                            <div class="dropdown">
                                
                                @if(auth()->user()->profile_status == 'lock')
                                <button class="btn btn-gray dropdown-toggle" type="button" id="postPrivacyDroupdownBtn"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-earth-americas"></i> {{ get_phrase('Friends') }}
                                </button>
                                @else
                                <button class="btn btn-gray dropdown-toggle" type="button" id="postPrivacyDroupdownBtn"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-earth-americas"></i> {{ get_phrase('Public') }}
                                </button>
                                @endif
                                <ul class="dropdown-menu" aria-labelledby="postPrivacyDroupdownBtn">
                                    <li><a class="dropdown-item" href="javascript:void(0)"
                                            onclick="post_privacy('private', this, 'postPrivacyDroupdownBtn', 'post_privacy')"><i
                                                class="fa-solid fa-user"></i> {{ get_phrase('Only Me') }}</a>
                                    </li>
                                    <li><a class="dropdown-item" href="javascript:void(0)"
                                            onclick="post_privacy('friends', this, 'postPrivacyDroupdownBtn', 'post_privacy')"><i
                                                class="fa-solid fa-users"></i>
                                            @if (isset($paid_content))
                                                {{ get_phrase('Premium') }}
                                            @else
                                                {{ get_phrase('Friends') }}
                                            @endisset
                                    </a>
                                </li>
                                @if(auth()->user()->profile_status == 'lock')
                                @else
                                <li><a class="dropdown-item" href="javascript:void(0)"
                                        onclick="post_privacy('public', this, 'postPrivacyDroupdownBtn', 'post_privacy')"><i
                                            class="fa-solid fa-user-group"></i> {{ get_phrase('Public') }}</a></li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>

                <textarea name="description" class="n_textarea" id="post_article"
                    placeholder="{{ get_phrase("What's on your mind ____", [auth()->user()->name]) }}?"></textarea>

                <div id="tab-file" class="post-inner file-tab cursor-pointer p-0 mt-2">
                    <span class="close-btn z-index-2000"><i class="fa fa-close"></i></span>

                    <!--Uploader start-->
                    <div class="file-uploader">
                        <label for="multiFileUploader">
                            <i class="fa-solid fa-cloud-arrow-up text-secondary"></i>
                            <p>{{ get_phrase('Click to browse') }}</p>
                        </label>
                        <input type="file" class="fileUploader position-absolute visibility-hidden"
                            name="multiple_files[]" id="multiFileUploader"
                            accept=".jpg,.jpeg,.png,.gif,.mp4,.mov,.wmv,.avi,.mkv,.webm" multiple />
                        <div class="preview-files">
                            <div class="row justify-content-start px-3"></div>
                        </div>
                       
                    </div>
                    <!--Uplodaer end-->
                    <div class="mt-4 form-group eg_control">
                         <input type="file" class="form-control" name="mobile_app_image" placeholder="upload a file">
                        <label class="form-label" for="">{{get_phrase("Upload a preview(for mobile application )")}}</label>
                    </div>
                </div>

                {{-- Text to Image --}}
                <div id="tab-ai" class="post-inner file-tab cursor-pointer p-0 mt-2">
                    <span class="close-btn z-index-2000"><i class="fa fa-close"></i></span>

                    <div class="widget friend-widget">
                        <div class="n_pro_con d-flex align-items-start">
                            <div class="demo-badge">
                                <h4>{{get_phrase('Text-to-Image Generator')}}</h4>
                            </div>
                        </div>
                    
                        <form id="text-form">
                            <label for="input-text" class="widget-title">{{get_phrase('Enter your text:')}}</label>
                            <input type="text" id="input-text" placeholder="Type something...">
                            <input type="hidden" id="base64-image" name="ai_image">
                            <button type="button" id="generate-button" class="btn common mt-3 rounded w-100 btn-lg active">
                                {{get_phrase('Generate Image')}}
                            </button>
                        </form>
                        <div class="output ai_image_generate_img">
                            <img id="generated-image" src="" alt="Generated Image" class="hidden">
                            <a id="download-button" class="hidden btn common mt-3 rounded w-100 btn-lg active" download="generated-image.png"><i class="fa-solid fa-download"></i> {{get_phrase('Download Image')}} </a>
                        </div>
                    </div>
                </div>

                <div class="post-inner py-3" id="tab-tag">
                    <h4 class="h5"> <a href="javascript:void(0)"
                            onclick="$('#tab-tag').removeClass('current')" class="prev-btn"><i
                                class="fa fa-long-arrow-left"></i></a>{{ get_phrase('Tag People') }}
                    </h4>
                    <div class="tag-wrap">

                        <div class="post-tagged">
                            <h4>{{ get_phrase('Tagged') }}</h4>
                            <div class="tag-card" id="taggedUsers"></div>
                            <div class="suggesions">
                                <input class="mt-3"
                                    onkeyup="searchFriendsForTagging(this, '#friendsForTagging')" type="search"
                                    placeholder="{{ get_phrase('Search more peoples') }}">
                                <h4>{{ get_phrase('Suggestions') }}</h4>

                                <div class="progress suggestions-loaging-bar d-none">
                                    <div class="indeterminate"></div>
                                </div>

                                <div class="tag-peoples" id="friendsForTagging">
                                    @php
                                        $friends = DB::table('users')
                                            ->whereJsonContains('friends', [Auth()->user()->id])
                                            ->take(5)
                                            ->get();
                                    @endphp
                                    @include('frontend.main_content.friend_list_for_tagging', [
                                        'friends' => $friends,
                                    ])
                                </div>
                            </div>
                        </div>
                        
                    </div><!-- Tag People End -->
                </div>

                @include('frontend.main_content.create_post_felling_and_activity')

                @include('frontend.main_content.create_post_location')

                <!-- Location Tab End -->
                  
                    <div class="modal-footer text-center justify-content-center p-3 border-none">
                         <div class="add_post_modal">
                             <p class="mb-0">{{get_phrase('Add to your post')}}</p>
                                <div>
                                    <button type="button" data-tab="tab-file" class="btn btn-secondary m_btn"><img
                                        src="{{ asset('storage/images/image.svg') }}"
                                        alt="photo"></button>
                                    <button type="button" data-tab="tab-ai" class="btn btn-secondary m_btn"><i class="fa-solid fa-robot" style="color:#0950a9e6"></i></button>
                                <button type="button" data-tab="tab-tag" class="btn btn-secondary m_btn"><img
                                        src="{{ asset('storage/images/peoples.png') }}"
                                        alt="photo"></button>
                                <button type="button" data-tab="tab-feeling" class="btn btn-secondary m_btn"><img
                                        src="{{ asset('storage/images/forum.svg') }}"
                                        alt="photo"></button>
                                <button type="button" onclick="loadMaps('map')" data-tab="tab-location"
                                    class="btn btn-secondary m_btn"><img src="{{ asset('storage/images/location.png') }}"
                                        alt="photo"></button>
                                <button type="button" class="btn btn-secondary m_btn" onclick="confirmLiveStreaming()"><img
                                        src="{{ asset('storage/images/camera.svg') }}"
                                        alt="photo"></button>
                                </div>
                         </div>
                        <button type="submit"
                            class="btn common mt-3 rounded w-100 btn-lg">{{ get_phrase('Publish Now') }}</button>
                    </div>
            </div>
        </div>
    </div>
</div> <!-- Create Post Modal End -->
</form>
  
<script>
$(document).ready(function() {
    function checkInputs() {
        var textareaValue = $('#post_article').val();
        var fileInputValue = $('#multiFileUploader').val();
        var submitButton = $('.common');
        if (textareaValue.trim() !== '' || fileInputValue !== '') {
            submitButton.prop('disabled', false);
            submitButton.removeClass('disabled').addClass('active');
        } else {
            submitButton.prop('disabled', true);
            submitButton.removeClass('active').addClass('disabled');
        }
    }
    checkInputs();
    $('#post_article').on('input', function() {
        checkInputs();
    });
    $('#multiFileUploader').on('change', function() {
        checkInputs();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const token = "{{ $hugging_face_auth_key }}";
    const form = document.getElementById('text-form');
    const inputText = document.getElementById('input-text');
    const base64Input = document.getElementById('base64-image');
    const outputImage = document.getElementById('generated-image');
    const generateButton = document.getElementById('generate-button');
    const submitButton = document.getElementById('submit-button');

    // Function to fetch image and convert to Base64
    async function fetchImageWithRetry(text, retries = 3, delay = 5000) {
        for (let i = 0; i < retries; i++) {
            try {
                outputImage.src = "{{asset('assets/frontend/images/loader.gif')}}";
                outputImage.classList.remove('hidden');

                const response = await fetch(
                    'https://api-inference.huggingface.co/models/stabilityai/stable-diffusion-2',
                    {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ inputs: text }),
                    }
                );

                if (response.ok) {
                    return await response.blob();
                } else {
                    const errorDetails = await response.json();
                    console.error("Error details:", errorDetails);
                    if (errorDetails.error && errorDetails.error.includes("currently loading")) {
                        console.log(`Retrying... (${i + 1}/${retries})`);
                    } else {
                        throw new Error(errorDetails.error || response.statusText);
                    }
                }
            } catch (error) {
                if (i === retries - 1) throw error;
                console.log(`Retrying in ${delay / 1000} seconds...`);
                await new Promise(resolve => setTimeout(resolve, delay));
            }
        }
    }

    generateButton.addEventListener('click', async () => {
        const text = inputText.value.trim();
        if (!text) {
            alert("Please enter valid text.");
            return;
        }

        try {
            const imageBlob = await fetchImageWithRetry(text);
            const reader = new FileReader();
            reader.readAsDataURL(imageBlob);
            reader.onloadend = () => {
                const base64Image = reader.result; // Base64 string

                // Set image src and hidden input value
                outputImage.src = base64Image;
                base64Input.value = base64Image.split(',')[1]; // Only the Base64 part
                outputImage.classList.remove('hidden');

                // Show the submit button
                submitButton.classList.remove('hidden');
            };
        } catch (error) {
            console.error("Error occurred:", error);
            alert(`An error occurred: ${error.message}`);
        }
    });
});

</script>

