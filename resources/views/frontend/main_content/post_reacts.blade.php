@if(isset($post_react) && $post_react == true)
<div class="post-react d-flex align-items-center">
    <?php $unique_values = array_unique($user_reacts); ?>
    <ul class="react-icons">
        @foreach($unique_values as $user_react)
            @if($user_react == 'like')
                {{-- <li><img class="w-17px" src="{{asset('storage/images/like.svg')}}" alt=""></li> --}}
                <li class="like-color h-22"><svg width="20" class="me-0" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.30176 16.8208L9.52422 19.0208C9.94003 19.3874 10.8756 19.5708 11.4993 19.5708H15.4494C16.6968 19.5708 18.0482 18.7458 18.36 17.6458L20.8548 10.9541C21.3746 9.67076 20.439 8.57076 18.8798 8.57076H14.7217C14.098 8.57076 13.5783 8.11243 13.6822 7.47076L14.202 4.53743C14.4099 3.71243 13.7862 2.79576 12.8506 2.52076C12.019 2.24576 10.9795 2.61243 10.5637 3.16243L6.30176 8.7541" fill="#5431EF"/>
                    <path d="M1 17.1838V7.81618C1 6.47794 1.48 6 2.6 6H3.4C4.52 6 5 6.47794 5 7.81618V17.1838C5 18.5221 4.52 19 3.4 19H2.6C1.48 19 1 18.5221 1 17.1838Z" fill="#5431EF"/>
                    </svg></li>
            @endif

            @if($user_react == 'love')
                <li><img class="w-22px" src="{{asset('storage/images/love.svg')}}" alt=""></li>
            @endif

            @if($user_react == 'sad')
                <li><img class="w-17px" src="{{asset('storage/images/sad.svg')}}" alt=""></li>
            @endif

            @if($user_react == 'angry')
                <li><img class="w-17px" src="{{asset('storage/images/angry.svg')}}" alt=""></li>
            @endif

            @if($user_react == 'haha')
                <li><img class="w-17px" src="{{asset('storage/images/haha.svg')}}" alt=""></li>
            @endif
        @endforeach
    </ul>

    @if(count($user_reacts) > 0)
        <span class="react-count">{{count($user_reacts)}}</span>
    @else
        <span class="react-count">0 {{get_phrase('Like')}}</span>
    @endif
</div>
@endif

@if(isset($ajax_call) && $ajax_call)
    <!--hr tag will be split by js to show different sections-->
    <hr>
@endif

@if(isset($my_react) && $my_react == true)
    @if(array_key_exists($user_info->id, $user_reacts))
        @if($user_reacts[$user_info->id] == 'like')
            <div class="like-color">
                {{-- <img class="w-17px mt--6px" src="{{asset('storage/images/liked.svg')}}" alt=""> --}}
                <svg width="20" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.30176 16.8208L9.52422 19.0208C9.94003 19.3874 10.8756 19.5708 11.4993 19.5708H15.4494C16.6968 19.5708 18.0482 18.7458 18.36 17.6458L20.8548 10.9541C21.3746 9.67076 20.439 8.57076 18.8798 8.57076H14.7217C14.098 8.57076 13.5783 8.11243 13.6822 7.47076L14.202 4.53743C14.4099 3.71243 13.7862 2.79576 12.8506 2.52076C12.019 2.24576 10.9795 2.61243 10.5637 3.16243L6.30176 8.7541" fill="#5431EF"/>
                    <path d="M1 17.1838V7.81618C1 6.47794 1.48 6 2.6 6H3.4C4.52 6 5 6.47794 5 7.81618V17.1838C5 18.5221 4.52 19 3.4 19H2.6C1.48 19 1 18.5221 1 17.1838Z" fill="#5431EF"/>
                    </svg>
                    
                    
                {{get_phrase('Liked')}}
            </div>
        @endif

        @if($user_reacts[$user_info->id] == 'love')
            <div class="love-color">
                <img class="w-22px mt--4px" src="{{asset('storage/images/love.svg')}}" alt="">
                {{get_phrase('Loved')}}
            </div>
        @endif

        @if($user_reacts[$user_info->id] == 'haha')
            <div class="sad-color">
                <img class="w-17px mt--4px" src="{{asset('storage/images/haha.svg')}}" alt="">
                {{get_phrase('Haha')}}
            </div>
        @endif

        @if($user_reacts[$user_info->id] == 'angry')
            <div class="angry-color">
                <img class="w-17px mt--4px" src="{{asset('storage/images/angry.svg')}}" alt="">
                {{get_phrase('Angry')}}
            </div>
        @endif

        @if($user_reacts[$user_info->id] == 'sad')
            <div class="sad-color">
                <img class="w-17px mt--4px" src="{{asset('storage/images/sad.svg')}}" alt="">
                Sad
            </div>
        @endif
    @else
        @if (isset($type)&&$type=="shorts")
            <div><i class="fa fa-thumbs-up @if (isset($type)&&$type=="shorts") shorts-icon-size @endif"></i></div>
        @else
            <div>
                <img class="w-17px mt--6px" src="{{asset('storage/images/like2.svg')}}" alt="">
             @if (isset($type)&&$type=="shorts")  @else {{get_phrase('Like')}} @endif </div>
        @endif
    @endif
@endif

