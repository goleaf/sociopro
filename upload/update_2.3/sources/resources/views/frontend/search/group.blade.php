
    <div class="page-wrap">
        <div class="card search-card border-none bg-white radius-8  p-20 ">
            <h3 class="sub-title mb-3">{{ get_phrase('Search Results') }}</h3>
            @include('frontend.search.header')
        </div> <!-- Search Card End -->
        
        
        
        <div class="card border-none bg-white radius-8  p-20 mt-4">
            <h3 class="sub-title mb-3">{{get_phrase('Groups')}}</h3>
                <div class="suggest-wrap sust_entery d-flex gap-3 flex-wrap">
                    @foreach ($groups as $key => $group )
                        <div class="col-lg-4 col-md-4 col-sm-6">
                            <div class="card sugg-card p-2 rounded">
                                <a href="{{ route('single.group',$group->id) }}" class="mb-2 thumbnail-133" style="background-image: url('{{ get_group_logo($group->logo,'logo') }}');"></a>
                               <div class="pl_con">
                                    <a href="{{ route('single.group',$group->id) }}"><h4>{{ ellipsis($group->title,20) }}</h4></a>
                                    @php $joined = \App\Models\Group_member::where('group_id',$group->id)->where('is_accepted','1')->count(); @endphp
                                    <span class="small text-muted">{{ $joined }} {{ get_phrase('Member') }} @if($joined>1) s @endif</span>
                                    @php $join = \App\Models\Group_member::where('group_id',$group->id)->where('user_id',auth()->user()->id)->count(); @endphp
                                    @if ($join>0)
                                    <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('group.rjoin',$group->id); ?>')" class="btn common_btn_2">{{ get_phrase('Joined') }}</a>
                                    @else
                                        <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('group.join',$group->id); ?>')" class="btn common_btn">{{ get_phrase('Join') }}</a>
                                    @endif
                               </div>
                            </div>
                        </div>
                    @endforeach
                </div> 
        </div><!--  Group Card End -->
    </div>



    @include('frontend.main_content.scripts')
    @include('frontend.initialize')
    @include('frontend.common_scripts')
