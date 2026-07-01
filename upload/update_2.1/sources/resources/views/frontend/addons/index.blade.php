@php
 if (addon_status('fundraiser') == 1){
    $fundraiser = DB::table('fundraisers')
        ->where('user_id', auth()->user()->id)
        ->exists();
    
    $donate = DB::table('fundraiser_donations')
        ->where('doner_id', auth()->user()->id)
        ->exists();
    }
@endphp
<style>
    .ac-con{
        margin-left: 10px !important; 
    }
    .res_funrai .btn_1 {  
	margin-right: 20px;
  }
  .over{
    overflow: hidden;
  }
</style>

<div class="main-section over mt-0">
    {{-- <div class="container"> --}}
        <div class="row">
            {{-- <div class="col-lg-4 col-xl-3">
                @include('frontend.addons.left_sidebar')
            </div> --}}

            <div class="col-lg-12 col-xl-12">

                <div class=" row">
                    <div
                        class=" @if ($section_title == '') col-6 d-flex align-items-center @else col-12 @endif">
                        <div class="toggle-menu">
                            <i class="fa-solid fa-bars"></i>
                        </div>
                    </div>
                    {{-- @if ($section_title != '')
                        <div class="col-6 d-flex mt-12 align-items-center">
                            <h4 class="fz-20-b-22-black">{{ $section_title }}</h4>
                        </div>
                    @endif --}}

                    @if (Route::currentRouteName() !== 'creator.payout')
                        @if (Route::currentRouteName() !== 'user.settings')
                            @if (addon_status('fundraiser') == 1)
                            <div class="bg-white radius-8 p-20 mb-12  blog-type res_funrai ac-con">
                                <div class="d-flex align-items-center justify-content-between">
                                <h3 class="text_16 mb-0">{{get_phrase('Fundraiser')}}</h3>
                                <a href="{{ route('fundraiser.create') }}" class="btn_1">{{get_phrase('Create Fundraise')}}</a>
                                </div>
                                <div class="gr-search mt-3">
                                    <form action="{{ route('fundraiser.search') }}" class="ag_form" method="GET">
                                        <input type="text" id="search" name="search" class="bg-secondary rounded"
                                        placeholder="Search" value="{{ request('search') }}" placeholder="search here...">
                                        <span class="i fa fa-search"></span>
                                    </form>
                            </div>
                                <ul class="Etab d-flex">
                                    <li><a href="{{route('fundraiser.index')}}" class="@if(Route::currentRouteName() == 'fundraiser.index') actives @else  @endif">{{get_phrase('Campaign')}}</a></li>
                                    @if ($fundraiser || $donate)
                                    <li>  
                                        <a href="@if ($fundraiser) {{ route('fundraiser.myactivity') }} @else
                                            {{ route('fundraiser.donor') }} @endif" class="@if (Route::currentRouteName() == 'fundraiser.myactivity') actives @endif">{{get_phrase(' My Activity')}} </a>
                                    </li>
                                    @endif
                                    <li><a href="{{ route('fundraiser.payment') }}" class="@if (Route::currentRouteName() == 'fundraiser.payment' || Route::currentRouteName() == 'campaign.history') actives @endif">{{get_phrase('Payment')}}</a></li>
                                    <li><a href="{{ route('fundraiser.category', ['type' => 'explore']) }}" class="@if(Route::currentRouteName() == 'fundraiser.category') actives @else  @endif" >{{get_phrase('Category')}}</a></li>
                                </ul>
                            </div>   
                            @endif
                        @endif
                    @endif


                    {{-- @if (Route::currentRouteName() == 'fundraiser.myactivity' || Route::currentRouteName() == 'campaign.type')
                        <div class="d-flex justify-content-start mt-12">
                            <a href="{{ $head_link }}" class="previousBtn p_btn"><i
                                    class="fa-solid fa-arrow-left-long"></i>{{ $link_name }}
                            </a>
                        </div>
                    @else
                        <div class="col-6 d-flex justify-content-end mt-12 align-items-center">
                            <a href="{{ $head_link }}" class="previousBtn p_btn">{{ $link_name }}
                                <i class="fa-solid fa-arrow-right-long"></i></a>
                        </div>
                    @endif --}}
                </div>

                {{-- all addon view --}}
                @include($content_view)
            </div>
        </div>
    {{-- </div> --}}
</div>
