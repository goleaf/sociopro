
{{-- <link rel="stylesheet" href="{{ asset('assets/frontend/css/nice-select.css') }}"> --}}
<style>
    .form-select {
	padding: 10px 20px;
	font-size: 14px;
	color: #444444;
	background-color: #f3f3f3 !important;
	border-color:  #f3f3f3 !important;
	border-radius: 5px;
}
.form-select:focus{
    box-shadow: none;
}
</style>
<div class="page-wrap">
    <div class="d-flex pagetab-head  align-items-center justify-content-between mb-3 p-20 radius-8 bg-white">
        <h3 class="h5 mb-0">{{ get_phrase('Create New Blog') }}</h3>
       <a href="{{ url()->previous() }}" class="btn common_btn"><i class="fa-solid fa-left-long"></i>{{get_phrase('Back')}}</a>
    </div>
    <div class="card border-none n_blog mt-3 px-3 py-4">
        <div class="create-article">
            @if ($errors->any())
                <div class="text-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{ route('blog.store') }}" class="form_sel" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="#">{{ get_phrase('Title') }}</label>
                    <input type="text" name="title" placeholder="Enter your title">
                </div>
                <div class="form-group">
                    <label for="#">{{ get_phrase('Category') }}</label>
                    <select name="category" id="category" required class="form-select  bg-secondary">
                        <option value="" selected disabled>{{ get_phrase('Select Category') }}</option>
                        @foreach ( $blog_category as $category )
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="#" class="mt-12">{{ get_phrase('Tags') }}</label>
                    <input type="text" name="tag" class="form-control bg-secondary" placeholder="Enter your tags">
                </div>
                <div class="form-group">
                    <label for="#">{{ get_phrase('Description') }}</label>
                    <textarea name="description" id="description" class="content"  placeholder="Description"></textarea>
                </div>
                <div class="form-group">
                    <label for="#">{{ get_phrase('Image') }}</label>
                    <input type="file" name="image" id="image">
                </div>
                
                <div class="inline-btn mt-3">
                    <button type="submit" class="btn common_btn w-100">{{ get_phrase('Create Post') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- <script src="{{ asset('assets/frontend/js/jquery.nice-select.min.js') }}"></script>
<script>
    $('document').ready(function(){
        $(".select").niceSelect();
    });
</script> --}}