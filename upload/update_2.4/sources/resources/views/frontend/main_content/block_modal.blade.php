<style>
    .eBlock{}
   
    .eBlock h3{
        font-size: 15px;
    }
    .eBlock ul{}
    .eBlock ul li{
        font-size: 14px;
        margin-bottom: 7px;
    }
    .eBlock ul li:last-child{
        margin-bottom: 0;
    }
</style>

<div class="eBlock mt-3">
    <h3>{{get_phrase("Block")}} {{$post->getUser->name}}</h3>
    <p>{{$post->getUser->name}} {{get_phrase('will no longer be able to:')}}</p>
    <form action="{{route('block_user_post', $post->post_id)}}" method="post">
        @csrf
        <input type="hidden" value="1" name="block">
        <ul>
            <li>{{get_phrase("See your posts on your timeline")}}</li>
        </ul>
        <p>{{get_phrase('If you just want to limit what you share with')}} {{$post->getUser->name}} {{get_phrase('or see less of him on Sociopro, you can take a break from him instead.')}}</p>
       <div class="modal-footer mt-2">
          <button type="submit" class="btn common_btn ">{{get_phrase('Confirm')}}</button>
       </div>
    </form>
</div>