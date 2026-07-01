 @foreach ($groups as $key => $group)
        <div class="col-md-4 col-lg-4 col-sm-6 single-item-countable" id="group-{{ $group->id }}">
            <div class="card p-2 rounded">
                <div class="mb-2"> <img class="img-fluid img-radisu" src="{{ get_group_logo($group->logo,'logo') }}" ></div>
                <a href="{{ route('single.group',$group->id) }}"><h4>{{ ellipsis($group->title,20) }}</h4></a>
                <span class="small text-muted">{{ get_phrase('____ Members', $viewData->groupAcceptedMemberCount($group))}}</span>
                @if ($viewData->userJoinedGroup($group, auth()->user()))
                <a href="javascript:void(0)" onclick="ajaxAction('{{ route('group.rjoin',$group->id) }}')" class="btn common_btn_2">{{ get_phrase('Joined')}}</a>
                @else
                    <a href="javascript:void(0)" onclick="ajaxAction('{{ route('group.join',$group->id) }}')" class="btn common_btn">{{ get_phrase('Join')}}</a>
                @endif
            </div>
        </div>
        @if (isset($search)&&!empty($search))
            @if ($key==2)
                @break
            @endif
        @endif
@endforeach     
