

<h4 class="widget-title mb-8">{{get_phrase('Info')}}</h4>
<ul>
    <li class="d-flex">
        <i class="fa fa-briefcase"></i> <span>
        {{$user_info->job}}</span>
    </li>
    <li class="d-flex">
        <i class="fa-solid fa-graduation-cap"></i>
        <span>{{get_phrase('Studied at')}} {{$user_info->studied_at}}</span>
    </li>
    <li class="d-flex">
        <i class="fa-solid fa-location-dot"></i>
        <span>{{get_phrase('From')}} {{$user_info->address}}</span>
    </li>
    <li class="d-flex">
        <i class="fa-solid fa-user"></i>
        <span class="text-capitalize">{{get_phrase($user_info->gender)}}</span>
    </li>
    <li class="d-flex">
        <i class="fa-solid fa-clock"></i>
        <span>{{get_phrase('Joined')}} {{date_formatter($user_info->created_at, 1)}}</span>
    </li>
</ul>
<button onclick="showCustomModal('<?php echo route('profile.my_info', ['action_type' => 'edit']); ?>', '{{get_phrase('Edit info')}}')" class="btn common_btn w-100 mt-8">{{get_phrase('Edit Info')}}</button>
