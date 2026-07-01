

<div class="col-lg-6 col-sm-12 order-3 order-lg-2">
<div class="newsfeed-form single-entry mt-5">
    <div class="entry-inner current-entry">
        <a href="javascript:void(0)" onclick="confirmAction('{{ route('user.status', ['id' => auth()->user()->id]) }}', true)" class="dropdown-item">{{ get_phrase('Account Deactivate') }}</a>
    </div> 
</div> 
</div>