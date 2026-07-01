<?php

use App\Http\Controllers\AdminCrudController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CustomUserController;
use App\Http\Controllers\Event\EventController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaidContent;
use App\Http\Controllers\PaymentHistory;
use App\Http\Controllers\Report\SearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SponsorController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\JobController;
use Illuminate\Support\Facades\Route;

// events route

Route::controller(EventController::class)->middleware('auth', 'verified', 'activity', 'prevent-back-history')->group(function () {
    Route::get('/events', 'allevents')->name('event');
    Route::get('user/event', 'userevent')->name('userevent');
    Route::post('/event/store', 'store')->name('event.store');
    Route::post('/event/update/{id}', 'update')->name('event.update');
    Route::get('event/delete', 'event_delete')->name('event.delete');
    Route::get('event/view/{id}', 'single_event')->name('single.event');
    // event going
    Route::get('event/going/{id}', 'event_going')->name('event.going');
    Route::get('event/notgoing/{id}', 'event_notgoing')->name('event.notgoing');
    // event interested
    Route::get('event/interested/{id}', 'event_interested')->name('event.interested');
    Route::get('event/notinterested/{id}', 'event_notinterested')->name('event.notinterested');

    Route::get('event/cancel/{id}', 'event_cancel')->name('event.cancel');

    // load event
    Route::get('/load_event_by_scrolling', 'load_event_by_scrolling')->name('load_event_by_scrolling');

    // invite route to friend
    Route::get('/event/invite/{invited_friend_id}/{requester_id}/{event_id}', 'event_invite')->name('event.invite');
    // view all route
    Route::get('/share/event/', 'shareevent')->name('event.share');

    Route::post('event/invites/sent', 'sent_invition')->name('event.invition');
    Route::get('/search_user_for_event_inviting', 'search_user_for_event_inviting')->name('search_user_for_event_inviting');
});

// marketplace route

Route::controller(MarketplaceController::class)->middleware('auth', 'verified', 'activity', 'prevent-back-history')->group(function () {
    Route::get('/products', 'allproducts')->name('allproducts');
    Route::get('user/product', 'userproduct')->name('userproduct');
    Route::POST('/product/store', 'store')->name('product.store');
    Route::post('/update/product/{id}', 'update')->name('product.update');
    Route::get('product/delete', 'product_delete')->name('product.delete');
    Route::get('/load_product_by_scrolling', 'load_product_by_scrolling')->name('load_product_by_scrolling');
    Route::get('product/view/{id}', 'single_product')->name('single.product');
    Route::get('/product/filter/{category?}/{max?}/{min?}/{brand?}/{location?}', 'filter')->name('filter.product');

    Route::get('/product/saved/', 'saved_product')->name('product.saved');

    Route::get('save/product/{id}', 'save_for_later')->name('save.product.later');
    Route::get('unsave/product/{id}', 'unsave_for_later')->name('unsave.product.later');
    Route::get('product/iframe/view/{id}', 'single_product_ifrane')->name('single.product.iframe');
});

//  blog
Route::controller(BlogController::class)->middleware('auth', 'verified', 'activity', 'prevent-back-history')->group(function () {
    Route::get('/blogs', 'blogs')->name('blogs');
    Route::get('/create/blog', 'create')->name('create.blog');
    Route::get('my/blog', 'myblog')->name('myblog');
    Route::POST('/blog/store', 'store')->name('blog.store');
    Route::get('/edit/blog/{id}', 'edit')->name('blog.edit');
    Route::post('/update/blog/{id}', 'update')->name('blog.update');
    Route::get('blog/delete', 'delete')->name('blog.delete');
    Route::get('/load_blog_by_scrolling', 'load_blog_by_scrolling')->name('load_blog_by_scrolling');
    Route::get('blog/view/{id}', 'single_blog')->name('single.blog');
    Route::get('/blog/category/{category}', 'category_blog')->name('category.blog');
    Route::get('/blog/search/', 'search')->name('search.blog');
});

//  FrontEnd Jobs Route
Route::controller(JobController::class)->middleware('auth', 'verified', 'activity')->group(function () {
    Route::get('/jobs', 'jobs')->name('jobs');
    Route::get('/create/job', 'job_create')->name('create.job');
    Route::POST('/job/store', 'job_store')->name('job.store');
    Route::get('my/job', 'myjob')->name('job.myjob');
    Route::get('/edit/job/{id}', 'job_edit')->name('job.edit');
    Route::post('/update/job/{id}', 'job_update')->name('job.update');
    Route::get('job/delete', 'job_delete')->name('job.delete');

    Route::get('job/follow/{id}/{user_id}', 'job_follow')->name('job.follow');

    Route::get('job/save/view', 'job_save')->name('job.save.view');
    Route::get('job/search/view', 'search_job')->name('search.job');
    Route::get('/job/filter/{category?}/{max?}/{location?}', 'job_filter')->name('filter.job');

    Route::get('job/single/details/{id}', 'single_job_details')->name('job.single.details');

    Route::get('job/apply/form/{id}', 'ApplyForm')->name('job.apply.form');

    Route::post('job/applyed/{id}', 'JobApply')->name('job.apply');

    Route::get('job/my/apply/list', 'MyApply')->name('job.my.apply.list');
    Route::get('job/apply/all-list', 'AllApplyList')->name('job.apply.all.list');
    Route::get('job/apply/list-delete/{id}', 'applyListDelete')->name('job.apply.list-delete');
    Route::get('job/pdf/download/{id}', 'DownloadPdf')->name('job.pdf.download');


    Route::get('job/pay/form/{jobId}', 'job_pay_form')->name('job.pay.form');

    Route::post('job/payment_configuration/{id}', 'payment_configuration')->name('job.payment_configuration');

    

});


//  page
Route::controller(PageController::class)->middleware('auth', 'verified', 'activity', 'prevent-back-history')->group(function () {
    Route::get('/pages', 'pages')->name('pages');
    Route::POST('/page/store', 'store')->name('page.store');
    Route::post('/update/page/{id}', 'update')->name('page.update');
    Route::post('/update/coverphoto/page/{id}', 'updatecoverphoto')->name('page.coverphoto');
    Route::post('/update/info/page/{id}', 'updateinfo')->name('page.update.info');
    Route::get('/load_page_by_scrolling', 'load_page_by_scrolling')->name('load_page_by_scrolling');
    Route::get('page/view/{id}', 'single_page')->name('single.page');
    Route::get('page/photo/view/{id}', 'page_photos')->name('single.page.photos');
    Route::get('/page/videos/{id}', 'videos')->name('page.videos');
    Route::get('/page/load_videos', 'load_videos')->name('page.load_videos');

    Route::get('page/like/{id}', 'like')->name('page.like');
    Route::get('page/dislike/{id}', 'dislike')->name('page.dislike');
});

//  group
Route::controller(GroupController::class)->middleware('auth', 'verified', 'activity', 'prevent-back-history')->group(function () {
    Route::get('/groups', 'groups')->name('groups');
    Route::POST('/group/store', 'store')->name('group.store');
    Route::post('/update/group/{id}', 'update')->name('group.update');
    Route::post('/update/coverphoto/group/{id}', 'updatecoverphoto')->name('group.coverphoto');
    Route::get('/group/peopel/info/{id}', 'peopelinfo')->name('group.people.info');
    Route::get('group/view/details/{id}', 'single_group')->name('single.group');
    Route::get('group/photo/view/{id}', 'group_photos')->name('single.group.photos');
    Route::get('all/peopel/group/view/{id}', 'all_people_group')->name('all.people.group.view');
    Route::get('/group/event/view/{id}', 'group_event')->name('group.event.view');
    Route::get('group/join/{id}', 'join')->name('group.join');
    Route::get('group/rjoin/{id}', 'rjoin')->name('group.rjoin');
    Route::get('group/search/view', 'search_group')->name('search.group');
    Route::get('group/all/view', 'group_all_view')->name('all.group.view');
    Route::get('group/user/create', 'group_user_create')->name('group.user.created');
    Route::get('group/user/joined', 'group_user_joined')->name('group.user.joined');
    Route::post('album/add/image', 'add_album_image')->name('add.image.album');
    Route::post('group/invites/sent', 'sent_invition')->name('group.invition');
    Route::get('/search_friends_for_inviting', 'search_friends_for_inviting')->name('search_friends_for_inviting');
    Route::get('/load_groups_by_scrolling', 'load_groups_by_scrolling')->name('load_groups_by_scrolling');
});

//  video
Route::controller(VideoController::class)->middleware('auth', 'verified', 'activity', 'prevent-back-history')->group(function () {
    Route::get('/videos', 'videos')->name('videos');
    Route::POST('/videos/sorts/store', 'store')->name('videos.store');
    Route::get('/video/details/info/{id}', 'videoinfo')->name('video.detail.info');
    Route::get('/shorts', 'shorts')->name('shorts');
    Route::get('save/video/short/{id}', 'save_for_later')->name('save.video.later');
    Route::get('/load_videos_by_scrolling', 'load_videos_by_scrolling')->name('load_videos_by_scrolling');
    Route::get('/load_shorts_by_scrolling', 'load_shorts_by_scrolling')->name('load_shorts_by_scrolling');

    Route::get('save/video/short/{id}', 'save_for_later')->name('save.video.later');
    Route::get('unsave/video/short/{id}', 'unsave_for_later')->name('unsave.video.later');

    Route::get('saved/video/view', 'save_all')->name('save.all.view');

    Route::get('video/delete', 'video_delete')->name('video.delete');
});

//  video
Route::controller(ChatController::class)->middleware('auth', 'verified', 'activity', 'prevent-back-history')->group(function () {
    Route::get('/chat/inbox/{reciver}/{product?}/', 'chat')->name('chat');
    Route::POST('/chat/save', 'chat_save')->name('chat.save');
    Route::get('chat/own/remove/{id}', 'remove_chat')->name('remove.chat');
    Route::POST('/my_message_react', 'react_chat')->name('react.chat');
    Route::get('/chat/profile/search/', 'search_chat')->name('search.chat');

    Route::get('/chat/inbox/load/data/ajax/', 'chat_load')->name('chat.load');
    Route::get('/chat/inbox/read/message/ajax/', 'chat_read_option')->name('chat.read');
    
});

//  follow
Route::controller(FollowController::class)->middleware('auth', 'verified', 'activity', 'prevent-back-history')->group(function () {
    Route::get('user/account/follow/{id}', 'follow')->name('user.follow');
    Route::get('user/account/unfollow/{id}', 'unfollow')->name('user.unfollow');
});

//  whole website ssearch
Route::controller(SearchController::class)->middleware('auth', 'verified', 'activity', 'prevent-back-history')->group(function () {
    Route::get('/search', 'search')->name('search');
    Route::get('/search/people/', 'search_people')->name('search.people');
    Route::get('/search/post/', 'search_post')->name('search.post');
    Route::get('/search/video/', 'search_video')->name('search.video');
    Route::get('/search/product/', 'search_product')->name('search.product');
    Route::get('/search/page/', 'search_page')->name('search.page');
    Route::get('/search/group/', 'search_group')->name('search.group.specific');
    Route::get('/search/event/', 'search_event')->name('search.event');
});

Route::controller(CustomUserController::class)->middleware('auth', 'verified', 'activity', 'prevent-back-history')->group(function () {
    Route::get('user/view/profile/{id}', 'view_profile_data')->name('user.profile.view');
    Route::get('/user/load_post_by_scrolling', 'load_post_by_scrolling')->name('user.load_post_by_scrolling');
    Route::get('user/password/change', 'changepass')->name('user.password.change');
    Route::POST('user/password/update', 'updatepass')->name('user.password.update');
    Route::get('user/friend/{id}', 'friend')->name('user.friend');
    Route::get('user/unfriend/{id}', 'unfriend')->name('user.unfriend');

    Route::get('/user/friends/{id}', 'friends')->name('user.friends');
    Route::get('/user/photos/{id}', 'photos')->name('user.photos');
    Route::get('/user/videos/{id}', 'videos')->name('user.videos');

    Route::get('video/delete/{id}', 'delete_mediafile')->name('delete.mediafile');
    Route::get('download/media/file/{id}', 'download_mediafile')->name('download.mediafile');
    Route::get('download/media/file/image/{id}', 'download_mediafile_image')->name('download.mediafile.image');
});

//  setting frontend
Route::controller(SettingController::class)->group(function () {
    Route::get('about/page/view/', 'about_view')->name('about.view')->middleware('auth', 'verified', 'prevent-back-history');
    Route::get('policy/page/view/', 'policy_view')->name('policy.view')->middleware('auth', 'verified', 'prevent-back-history');
    Route::get('contact/us/view/', 'contact_view')->name('contact.view');
    Route::POST('contact/us/send/', 'contact_send')->name('contact.send');

    Route::get('term/condition/view/', 'term_view')->name('term.view');

    Route::get('admin/about/page/data/', 'update_about_page_data')->name('admin.about.page.data.view')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/about/page/data/update/{id}', 'update_about_page_data_update')->name('admin.about.page.data.update')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    Route::POST('admin/privacy/page/data/update/{id}', 'update_privacy_page_data_update')->name('admin.privacy.page.data.update')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    Route::POST('admin/term/page/data/update/{id}', 'update_term_page_data_update')->name('admin.term.page.data.update')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    Route::get('admin/reported/post/', 'reported_post_to_admin')->name('admin.reported.post.view')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/reported/post/delete/{id}', 'reported_post_remove_by_admin')->name('admin.reported.post.delete.by.admin')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    Route::get('admin/live-video/setting/view', 'live_video_edit_form')->name('admin.live-video.view')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::post('admin/live-video/setting/update', 'live_video_update')->name('admin.live-video.update')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    Route::get('admin/smtp/setting/view/', 'smtp_settings_view')->name('admin.smtp.settings.view')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/smtp/setting/save/{id}', 'smtp_settings_save')->name('admin.smtp.settings.view.save')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    // system settings
    Route::get('admin/system/setting/view/', 'system_settings_view')->name('admin.system.settings.view')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/system/setting/save/', 'system_settings_save')->name('admin.system.settings.view.save')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/system/setting/logo/save/', 'system_settings_logo_save')->name('admin.system.settings.logo.view.save')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    Route::get('admin/settings/amazon_s3', 'amazon_s3')->name('admin.settings.amazon_s3');
    Route::post('admin/settings/amazon_s3/update', 'amazon_s3_update')->name('admin.settings.amazon_s3.update');
});

//  admin all crud
Route::controller(AdminCrudController::class)->group(function () {

    Route::get('admin/dashboard/', 'admin_dashboard')->name('admin.dashboard')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    Route::get('admin/users/', 'users')->name('admin.users')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/user/add', 'user_add')->name('admin.user.add')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::post('admin/user/store', 'user_store')->name('admin.user.store')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/user/edit/{id}', 'user_edit')->name('admin.user.edit')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::post('admin/user/update/{id}', 'user_update')->name('admin.user.update')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/user/delete/{id}', 'user_delete')->name('admin.user.delete')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/user/status/{id}', 'user_status')->name('admin.user.status')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    Route::any('admin/server_side_users_data', 'server_side_users_data')->name('admin.server_side_users_data');

    // Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => ['auth']], function() {
    //     Route::get('/users', 'AdminController@user_index')->name('admin.users.index');
    //     Route::get('/users/create', 'AdminController@user_create')->name('admin.users.create');
    //     Route::post('/users', 'AdminController@user_store')->name('admin.users.store');
    //     Route::get('/users/{user}/edit', 'AdminController@user_edit')->name('admin.users.edit');
    //     Route::put('/users/{user}', 'AdminController@user_update')->name('admin.users.update');
    //     Route::delete('/users/{user}', 'AdminController@user_destroy')->name('admin.users.destroy');
    // });

    Route::get('admin/change/password', 'admin_change_password')->name('admin.change.password')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/profile/', 'admin_profile')->name('admin.profile')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/profile/update/', 'admin_profile_update')->name('admin.profile.update')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    Route::get('admin/page', 'pages')->name('admin.page')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/page/create', 'page_create')->name('admin.page.create')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/page/edit/{id}', 'page_edit')->name('admin.page.edit')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::post('admin/page/created/', 'page_created')->name('admin.page.created')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::post('admin/page/updated/{id}', 'page_updated')->name('admin.page.updated')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    Route::get('admin/blog', 'blogs')->name('admin.blog')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/blog/create', 'blog_create')->name('admin.blog.create')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/blog/edit/{id}', 'blog_edit')->name('admin.blog.edit')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::post('admin/blog/created/', 'blog_created')->name('admin.blog.created')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::post('admin/blog/updated/{id}', 'blog_updated')->name('admin.blog.updated')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

   // Badge Raoute Here
   Route::get('admin/badge', 'badge')->name('admin.badge')->middleware('auth', 'verified', 'admin', 'prevent-back-history');  
   Route::get('admin/badge/delete/{id}', 'delete_badge_history')->name('admin.badge.delete')->middleware('auth', 'verified', 'admin', 'prevent-back-history');  

   Route::POST('admin/badge/price/save/', 'badge_settings_save')->name('admin.badge.price.save')->middleware('auth', 'verified', 'admin', 'prevent-back-history');



   // Badge Raoute Here

   //Job Route Start Here

   // Admin Control Route for job   
 
   Route::get('admin/job/category/view/', 'view_job_category')->name('admin.view.job.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/job/category/create/', 'create_job_category')->name('admin.create.job.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/job/category/save/', 'save_job_category')->name('admin.save.job.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/job/category/edit/{id}', 'edit_job_category')->name('admin.edit.job.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/job/category/update/{id}', 'update_job_category')->name('admin.update.job.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/job/category/delete/{id}', 'delete_job_category')->name('admin.delete.job.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    //
    Route::get('admin/job', 'jobs')->name('admin.job')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/job/create', 'job_create')->name('admin.job.create')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    Route::get('admin/job/edit/{id}', 'job_edit')->name('admin.job.edit')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::post('admin/job/created/', 'job_created')->name('admin.job.created')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::post('admin/job/updated/{id}', 'job_updated')->name('admin.job.updated')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/job/delete/{id}', 'delete_job')->name('admin.delete.job')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/job/pending/', 'pending_job')->name('admin.pending.job')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/job/apply/all/list', 'AllApplyList')->name('admin.job.apply.all.list')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/job/apply/list-delete/{id}', 'applyListDelete')->name('admin.job.apply.list-delete')->middleware('auth', 'verified', 'admin', 'prevent-back-history');


    Route::get('admin/job/pdf/download/{id}', 'DownloadPdf')->name('admin.job.pdf.download')->middleware('auth', 'verified', 'admin');

    Route::get('admin/job/payment/history', 'jobPaymentHistory')->name('admin.job.payment.history')->middleware('auth', 'verified', 'admin');
    Route::get('admin/delete/job/payment/history/{id}', 'jobDeleteHistory')->name('admin.delete.job.payment.history')->middleware('auth', 'verified', 'admin');


    Route::get('admin/job/price/view/', 'settings_view')->name('admin.job.price.view')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/job/price/save/', 'settings_save')->name('admin.job.price.view.save')->middleware('auth', 'verified', 'admin', 'prevent-back-history');



   //Job Route End Here




    Route::get('admin/page/category/view/', 'view_category')->name('admin.view.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/page/category/create/', 'create_category')->name('admin.create.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/page/category/save/', 'save_category')->name('admin.save.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/page/category/edit/{id}', 'edit_category')->name('admin.edit.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/page/category/update/{id}', 'update_category')->name('admin.update.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/page/category/delete/{id}', 'delete_category')->name('admin.delete.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    // product category
    Route::get('admin/product/category/view/', 'view_product_category')->name('admin.view.product.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/product/category/create/', 'create_product_category')->name('admin.create.product.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/product/category/save/', 'save_product_category')->name('admin.save.product.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/product/category/edit/{id}', 'edit_product_category')->name('admin.edit.product.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/product/category/update/{id}', 'update_product_category')->name('admin.update.product.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/product/category/delete/{id}', 'delete_product_category')->name('admin.delete.product.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    // product brand
    Route::get('admin/product/brand/view/', 'view_brand_category')->name('admin.view.product.brand')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/product/brand/create/', 'create_brand_category')->name('admin.create.product.brand')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/product/brand/save/', 'save_brand_category')->name('admin.save.product.brand')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/product/brand/edit/{id}', 'edit_brand_category')->name('admin.edit.product.brand')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/product/brand/update/{id}', 'update_brand_category')->name('admin.update.product.brand')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/product/brand/delete/{id}', 'delete_brand_category')->name('admin.delete.product.brand')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    // blog  category
    Route::get('admin/blog/category/view/', 'view_blog_category')->name('admin.view.blog.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/blog/category/create/', 'create_blog_category')->name('admin.create.blog.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/blog/category/save/', 'save_blog_category')->name('admin.save.blog.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/blog/category/edit/{id}', 'edit_blog_category')->name('admin.edit.blog.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/blog/category/update/{id}', 'update_blog_category')->name('admin.update.blog.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/blog/category/delete/{id}', 'delete_blog_category')->name('admin.delete.blog.category')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    Route::get('admin/settings/payment', 'payment_settings')->name('admin.settings.payment')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/payment_gateway/edit/{id}', 'payment_gateway_edit')->name('admin.payment_gateway.edit')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::post('admin/payment_gateway/update/{id}', 'payment_gateway_update')->name('admin.payment_gateway.update')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/payment_gateway/status/{id}', 'payment_gateway_status')->name('admin.payment_gateway.status')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/payment_gateway/environment/{id}', 'payment_gateway_environment')->name('admin.payment_gateway.environment')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

    //System About routes
    Route::get('admin/settings/about', 'about')->name('admin.about');
    Route::any('admin/save_valid_purchase_code/{action_type?}', 'save_valid_purchase_code')->name('admin.save_valid_purchase_code');
});

//  setting frontend
Route::controller(SponsorController::class)->group(function () {
    // Add  category
    Route::get('admin/sponsor/view/', 'view_sponsor')->name('admin.view.sponsor')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/sponsor/create/', 'create_sponsor')->name('admin.create.sponsor')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/sponsor/save/', 'save_sponsor')->name('admin.save.sponsor')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/sponsor/edit/{id}', 'edit_sponsor')->name('admin.edit.sponsor')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::POST('admin/sponsor/update/{id}', 'update_sponsor')->name('admin.update.sponsor')->middleware('auth', 'verified', 'admin', 'prevent-back-history');
    Route::get('admin/sponsor/delete/{id}', 'delete_sponsor')->name('admin.delete.sponsor')->middleware('auth', 'verified', 'admin', 'prevent-back-history');

});


Route::controller(NotificationController::class)->middleware('auth', 'verified', 'activity')->group(function () {
    Route::get('/all/notification', 'notifications')->name('notifications');
    Route::get('/accept/friend/request/notification/{id}', 'accept_friend_notification')->name('accept.friend.request.from.notification');
    Route::get('/decline/friend/request/notification/{id}', 'decline_friend_notification')->name('decline.friend.request.from.notification');

    Route::get('/accept/group/request/notification/{id}/{group_id}', 'accept_group_notification')->name('accept.group.request.from.notification');
    Route::get('/decline/group/request/notification/{id}/{group_id}', 'decline_group_notification')->name('decline.group.request.from.notification');

    Route::get('/accept/event/request/notification/{id}/{event_id}', 'accept_event_notification')->name('accept.event.request.from.notification');
    Route::get('/decline/event/request/notification/{id}/{event_id}', 'decline_event_notification')->name('decline.event.request.from.notification');

    Route::get('/mark/as/read/notification/{id}', 'mark_as_read')->name('mark.as.read.notification');
    //fundraiser
    Route::get('/accept/fundraiser/request/notification/{id}/{fundraiser_id}', 'accept_fundraiser_notification')->name('accept.fundraiser.request.from.notification');
    Route::get('/decline/fundraiser/request/notification/{id}/{fundraiser_id}', 'decline_fundraiser_notification')->name('decline.fundraiser.request.from.notification');
});

Route::controller(LanguageController::class)->middleware('auth', 'verified', 'activity', 'admin')->group(function () {
    Route::get('admin/all/language/settings', 'language')->name('admin.language.settings');
    Route::POST('admin/create/language/', 'language_add')->name('admin.language.create');
    Route::POST('admin/languages/update/{language}', 'language_update')->name('admin.languages.update');
    Route::get('admin/languages/edit/phrase/{language}', 'edit_phrase')->name('admin.languages.edit.phrase');
    Route::post('admin/languages/update/phrase/{id}', 'update_phrase')->name('admin.languages.update.phrase');
});

Route::controller(PaymentHistory::class)->middleware('auth', 'verified', 'activity', 'admin', 'prevent-back-history')->group(function () {
    Route::get('admin/payment-histories', 'index')->name('admin.payment_histories');
});


//  paid content
Route::controller(PaidContent::class)->middleware('auth', 'verified', 'activity', 'prevent-back-history')->group(function () {
    Route::get('/paid/content', 'paid_content')->name('paid.content');
    Route::get('/paid/content/general/timeline', 'general_timeline')->name('general.timeline');

    // payout and subscriber
    Route::get('/creator/payout', 'creator_payout')->name('creator.payout');
    Route::post('/creator/payout/request', 'creator_payout_request')->name('creator.payout.request');
    Route::get('/creator/payout/cancel/{id}', 'creator_payout_cancel')->name('creator.payout.cancel');
    Route::get('/paid/content/subscriber/', 'subscriber_list')->name('subscriber.list');

    Route::get('/user/subscription', 'user_subscription')->name('user.subscription');
    Route::get('/subscription/payment', 'subscription_payment')->name('subscription.payment');

    // creator profile view
    Route::get('/paid/content/view/{page}/{id}', 'creator_page_view')->name('page.view');
    Route::post('/paid/content/request/author/{id}', 'request_author')->name('request.author');

    // subscription
    Route::post('/paid/content/subscription/payment/{id}', 'subscription')->name('subscription.payment_configuration');

    // search type
    Route::post('/paid/content/search/{type}', 'search_type')->name('search.type');
    Route::get('/paid/content/search/{type}', 'search_type')->name('search.type');
    Route::get('/load/searched/list/item', 'load_search_list_item')->name('load.search.list.item');

    Route::get('/paid/content/creator/{type}', 'creator_timeline')->name('creator.timeline');
    Route::get('/creator/post/type/{type}', 'creator_timeline')->name('post.type');
    Route::get('/creator/subscribers/', 'subscribers')->name('creator.subscribers');
    Route::get('/creator/packages/', 'packages')->name('creator.package');
    Route::post('/paid/content/create/package', 'create_package')->name('create.package');
    Route::get('/paid/content/package/edit/{id}', 'edit_package')->name('edit.package');
    Route::post('/paid/content/package/update/{id}', 'update_package')->name('update.package');
    Route::get('/paid/content/package/delete/{id}', 'delete_package')->name('delete.package');

    Route::get('/paid/content/settings/', 'settings')->name('settings');
    Route::post('/paid/content/settings/update/{id}', 'update_settings')->name('update.settings');
    Route::get('/paid/content/settings/remove/{type}', 'remove_photo')->name('remove.photo');

    Route::post('/paid/content/my_page/post', 'post')->name('paid.content.post');
    Route::get('/load/paid/content/post/', 'load_paid_content_post')->name('load.paid.content.post');
    Route::get('/load/timeline/post/', 'load_timeline_post')->name('load.timeline.post');

    // admin
    Route::get('/admin/author/list', 'author_list')->name('author.list');
    Route::get('/admin/author/status/{id}', 'author_status')->name('author.status');
    Route::get('/admin/author/delete/{id}', 'author_delete')->name('author.delete');
    Route::get('/admin/author/review/request/{id}', 'review_request')->name('author.review.request');
    Route::get('/admin/author/payout/', 'payout_report')->name('payout.report');
    Route::get('/admin/author/pending/report', 'pending_report')->name('pending.report');
    Route::get('/admin/make/payment/{id}', 'author_payout')->name('author.payout');
    Route::get('/admin/payout/delete/{id}', 'delete_payout')->name('admin.delete.payout');
});
