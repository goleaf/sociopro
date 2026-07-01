<?php

namespace App\Http\Controllers;

use App\Actions\Jobs\StreamJobApplicationAttachmentAction;
use App\Enums\MembershipRole;
use App\Enums\UserRole;
use App\Models\Account_active_request;
use App\Models\Badge;
use App\Models\Blog;
use App\Models\Blogcategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Group;
use App\Models\Group_member;
use App\Models\Job;
use App\Models\JobApply;
use App\Models\JobCategory;
use App\Models\JobWishlist;
use App\Models\Marketplace;
use App\Models\Page;
use App\Models\Page_like;
use App\Models\Pagecategory;
use App\Models\Payment_gateway;
use App\Models\Posts;
use App\Models\Setting;
use App\Models\Sponsor;
use App\Models\User;
use App\Queries\Jobs\JobApplicationExportQuery;
use App\Support\Files\FileUploader;
use App\Support\Validation\DateTimeRules;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Image;

class AdminCrudController extends Controller
{
    private const ADMIN_USERS_DATATABLE_COLUMNS = [
        0 => 'id',
        1 => 'id',
        2 => 'name',
        3 => 'email',
        4 => 'status',
        5 => 'id',
    ];

    private const DATATABLE_DEFAULT_LENGTH = 10;

    private const DATATABLE_MAX_LENGTH = 100;

    public function __construct()
    {
        // Don't remove it
        session(['admin_login' => 1]);
    }

    private function uniqueRule(string $table, string $column, ?Model $ignore = null): Unique
    {
        $rule = Rule::unique($table, $column);

        if ($ignore) {
            return $rule->ignore($ignore);
        }

        return $rule;
    }

    /**
     * @return array{draw: int, start: int, length: int, sort: string, direction: string, search: string}
     */
    private function adminUsersDataTableParameters(Request $request): array
    {
        $columnIndex = $this->integerWithin(
            data_get($request->input('order'), '0.column'),
            0,
            0,
            count(self::ADMIN_USERS_DATATABLE_COLUMNS) - 1
        );
        $requestedDirection = data_get($request->input('order'), '0.dir', 'desc');
        $direction = is_string($requestedDirection) ? strtolower($requestedDirection) : 'desc';
        $search = data_get($request->input('search'), 'value', '');
        $search = is_string($search) ? trim(mb_substr($search, 0, 255)) : '';

        return [
            'draw' => $this->integerWithin($request->input('draw'), 0, 0),
            'start' => $this->integerWithin($request->input('start'), 0, 0),
            'length' => $this->integerWithin($request->input('length'), self::DATATABLE_DEFAULT_LENGTH, 1, self::DATATABLE_MAX_LENGTH),
            'sort' => self::ADMIN_USERS_DATATABLE_COLUMNS[$columnIndex] ?? 'id',
            'direction' => in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc',
            'search' => $search,
        ];
    }

    private function integerWithin(mixed $value, int $default, int $minimum, ?int $maximum = null): int
    {
        if (is_array($value) || is_object($value)) {
            return $default;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);

        if ($integer === false || $integer < $minimum) {
            return $default;
        }

        if ($maximum !== null && $integer > $maximum) {
            return $maximum;
        }

        return (int) $integer;
    }

    // admin change pass

    public function admin_change_password()
    {
        $page_data['view_path'] = 'profile_view.password';

        return view('backend.index', $page_data);
    }

    // admin profile

    public function admin_profile()
    {
        $page_data['view_path'] = 'profile_view.profile';

        return view('backend.index', $page_data);
    }

    public function admin_profile_update(Request $request)
    {
        $validated = $request->validate([
            'profile_photo' => 'mimes:jpeg,jpg,png,gif|nullable',
            'dateofbirth' => DateTimeRules::nullableBirthDate(),
        ]);

        $user = auth()->user();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->date_of_birth = $request->filled('dateofbirth')
            ? DateTimeRules::birthDateTimestamp($request->dateofbirth)
            : null;
        $user->profession = $request->profession;
        $user->gender = $request->gender;
        $user->phone = $request->phone;
        $user->address = $request->address;
        if ($request->profile_photo && ! empty($request->profile_photo)) {
            $file_name = FileUploader::upload($request->profile_photo, 'public/storage/userimage', 800, null, 200, 200);
            // Update to database
            $user->photo = $file_name;
        }

        $user->save();
        flash()->addSuccess('Profile updated successfully!');

        return redirect()->back();
    }

    // dashboard

    public function admin_dashboard(Request $request)
    {
        $year = (int) $request->query('year', date('Y'));

        $page_data['all_category'] = Pagecategory::all();
        $page_data['year'] = $year;
        $page_data['dashboardCounts'] = [
            'users' => User::query()->nonAdmins()->count(),
            'posts' => Posts::query()->count(),
            'pages' => Page::query()->count(),
            'blogs' => Blog::query()->count(),
            'sponsors' => Sponsor::query()->count(),
            'marketplaces' => Marketplace::query()->count(),
        ];
        $page_data['monthlyUserCounts'] = collect(range(1, 12))->map(function (int $month) use ($year): int {
            $start = now()->setDate($year, $month, 1)->startOfDay();
            $end = $start->copy()->endOfMonth();

            return User::query()
                ->whereDate('created_at', '>=', $start->toDateString())
                ->whereDate('created_at', '<=', $end->toDateString())
                ->count();
        })->all();
        $page_data['view_path'] = 'dashboard.index';

        return view('backend.index', $page_data);
    }

    // page category
    public function view_category()
    {
        $page_data['all_category'] = Pagecategory::all();
        $page_data['view_path'] = 'page_category.index';

        return view('backend.index', $page_data);
    }

    public function create_category()
    {
        $page_data['view_path'] = 'page_category.create';

        return view('backend.index', $page_data);
    }

    public function save_category(Request $request)
    {
        $validated = $request->validate([
            'pagecategory' => ['required', 'max:255', 'string', $this->uniqueRule('pagecategories', 'name')],
        ]);
        $pagecategory = new Pagecategory;
        $pagecategory->name = $request->pagecategory;
        $done = $pagecategory->save();
        if ($done) {
            flash()->addSuccess('Page Category has been added successfully!');
        }

        return redirect()->back();
    }

    public function edit_category($id)
    {
        $page_data['pagecategory'] = Pagecategory::find($id);
        $page_data['view_path'] = 'page_category.edit';

        return view('backend.index', $page_data);
    }

    public function update_category(Request $request, $id)
    {
        $pagecategory = Pagecategory::findOrFail($id);
        $validated = $request->validate([
            'pagecategory' => ['required', 'max:255', 'string', $this->uniqueRule('pagecategories', 'name', $pagecategory)],
        ]);
        $pagecategory->name = $request->pagecategory;
        $done = $pagecategory->save();
        if ($done) {
            flash()->addSuccess('Page Category has been updated successfully!');
        }

        return redirect()->route('admin.view.category');
    }

    public function delete_category($id)
    {
        $category = Pagecategory::find($id);
        $category->delete();
        flash()->addSuccess('Page Category has been Deleted successfully!');

        return redirect()->back();
    }

    // product category
    public function view_product_category()
    {
        $page_data['all_category'] = Category::all();
        $page_data['view_path'] = 'product_category.index';

        return view('backend.index', $page_data);
    }

    public function create_product_category()
    {
        $page_data['view_path'] = 'product_category.create';

        return view('backend.index', $page_data);
    }

    public function save_product_category(Request $request)
    {
        $validated = $request->validate([
            'productcategory' => ['required', 'max:255', 'string', $this->uniqueRule('categories', 'name')],
        ]);
        $productcategory = new Category;
        $productcategory->name = $request->productcategory;
        $done = $productcategory->save();
        if ($done) {
            flash()->addSuccess('Product Category has been added successfully!');
        }

        return redirect()->back();
    }

    public function edit_product_category($id)
    {
        $page_data['productcategory'] = Category::find($id);
        $page_data['view_path'] = 'product_category.edit';

        return view('backend.index', $page_data);
    }

    public function update_product_category(Request $request, $id)
    {
        $productcategory = Category::findOrFail($id);
        $validated = $request->validate([
            'productcategory' => ['required', 'max:255', 'string', $this->uniqueRule('categories', 'name', $productcategory)],
        ]);
        $productcategory->name = $request->productcategory;
        $done = $productcategory->save();
        if ($done) {
            flash()->addSuccess('Product Category has been updated successfully!');
        }

        return redirect()->route('admin.view.product.category');
    }

    public function delete_product_category($id)
    {
        $category = Category::find($id);
        $category->delete();
        flash()->addSuccess('Product Category has been Deleted successfully!');

        return redirect()->back();
    }

    // product brand
    public function view_brand_category()
    {
        $page_data['brand'] = Brand::all();
        $page_data['view_path'] = 'brand.index';

        return view('backend.index', $page_data);
    }

    public function create_brand_category()
    {
        $page_data['view_path'] = 'brand.create';

        return view('backend.index', $page_data);
    }

    public function save_brand_category(Request $request)
    {
        $validated = $request->validate([
            'brand' => ['required', 'max:255', 'string', $this->uniqueRule('brands', 'name')],
        ]);
        $brand = new Brand;
        $brand->name = $request->brand;
        $done = $brand->save();
        if ($done) {
            flash()->addSuccess('Product Brand has been added successfully!');
        }

        return redirect()->back();
    }

    public function edit_brand_category($id)
    {
        $page_data['brand'] = Brand::find($id);
        $page_data['view_path'] = 'brand.edit';

        return view('backend.index', $page_data);
    }

    public function update_brand_category(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);
        $validated = $request->validate([
            'brand' => ['required', 'max:255', 'string', $this->uniqueRule('brands', 'name', $brand)],
        ]);
        $brand->name = $request->brand;
        $done = $brand->save();
        if ($done) {
            flash()->addSuccess('Product Brand has been updated successfully!');
        }

        return redirect()->route('admin.view.product.brand');
    }

    public function delete_brand_category($id)
    {
        $brand = Brand::find($id);
        $brand->delete();
        flash()->addSuccess('Product Brand has been Deleted successfully!');

        return redirect()->back();
    }

    // blog category
    public function view_blog_category()
    {
        $page_data['all_category'] = Blogcategory::all();
        $page_data['view_path'] = 'blog_category.index';

        return view('backend.index', $page_data);
    }

    public function create_blog_category()
    {
        $page_data['view_path'] = 'blog_category.create';

        return view('backend.index', $page_data);
    }

    public function save_blog_category(Request $request)
    {
        $validated = $request->validate([
            'blogcategory' => ['required', 'max:255', 'string', $this->uniqueRule('blogcategories', 'name')],
        ]);
        $blogcategories = new Blogcategory;
        $blogcategories->name = $request->blogcategory;
        $done = $blogcategories->save();
        if ($done) {
            flash()->addSuccess('Blog Category has been added successfully!');
        }

        return redirect()->back();
    }

    public function edit_blog_category($id)
    {
        $page_data['blogcategories'] = Blogcategory::find($id);
        $page_data['view_path'] = 'blog_category.edit';

        return view('backend.index', $page_data);
    }

    public function update_blog_category(Request $request, $id)
    {
        $blogcategories = Blogcategory::findOrFail($id);
        $validated = $request->validate([
            'blogcategory' => ['required', 'max:255', 'string', $this->uniqueRule('blogcategories', 'name', $blogcategories)],
        ]);
        $blogcategories->name = $request->blogcategory;
        $done = $blogcategories->save();
        if ($done) {
            flash()->addSuccess('Blog Category has been updated successfully!');
        }

        return redirect()->route('admin.view.blog.category');
    }

    public function delete_blog_category($id)
    {
        $blogcategories = Blogcategory::find($id);
        $blogcategories->delete();
        flash()->addSuccess('Blog Category Brand has been Deleted successfully!');

        return redirect()->back();
    }

    public function about()
    {
        $purchase_code = get_settings('purchase_code');
        $returnable_array = [
            'purchase_code_status' => 'valid',
            'support_expiry_date' => '10.10.2040',
            'customer_name' => 'valid',
            'product_license' => 'valid',
            'license_type' => 'Regular',
        ];

        $page_data['application_details'] = $returnable_array;
        $page_data['curlEnabled'] = function_exists('curl_version');
        $page_data['softwareVersion'] = Setting::where('type', 'version')->value('description');
        $page_data['purchaseCode'] = Setting::where('type', 'purchase_code')->value('description');
        $page_data['view_path'] = 'setting.system_about';

        return view('backend.index', $page_data);
    }

    // Don't remove this code for security reasons
    public function save_valid_purchase_code($action_type, Request $request)
    {
        if ($action_type == 'update') {
            $data['description'] = $request->purchase_code;

            Setting::where('type', 'purchase_code')->update($data);
            session()->flash('success', get_phrase('Purchase code has been updated'));
            echo 1;
        } else {
            return view('admin.setting.save_purchase_code');
        }
    }

    // Groups
    public function groups()
    {
        $page_data['view_path'] = 'group.list';
        $page_data['groups'] = Group::all();

        return view('backend.index', $page_data);
    }

    public function deleteGroup($id, Request $request)
    {
        $group = Group::find($id);
        if (! $group) {
            flash()->addError('Group not found');

            return redirect()->back();
        }
        $group->delete();
        Group_member::where('group_id', $id)->delete();
        flash()->addSuccess('Group deleted successfully');

        return redirect()->back();
    }

    public function group_create()
    {
        $page_data['view_path'] = 'group.create';

        return view('backend.index', $page_data);
    }

    public function group_created(Request $request)
    {
        $request->validate([
            'logo' => 'mimes:jpeg,jpg,png,gif|nullable',
            'title' => 'required|max:255',
            'subtitle' => 'required',
            'status' => 'required',
        ]);

        if ($request->logo && ! empty($request->logo)) {
            $logo_file_name = FileUploader::upload($request->logo, 'public/storage/groups/logo', 250);
        } else {
            $logo_file_name = null;
        }
        $group = new Group;
        $group->user_id = auth()->user()->id;
        $group->title = $request->title;
        $group->subtitle = $request->subtitle;
        $group->status = $request->status;
        $group->about = $request->about;
        $group->privacy = $request->privacy;
        if ($request->logo && ! empty($request->logo)) {
            $group->logo = $logo_file_name;
        }
        $done = $group->save();
        if ($done) {
            $group_member = new Group_member;
            $group_member->group_id = $group->id;
            $group_member->user_id = auth()->user()->id;
            $group_member->role = MembershipRole::Admin->value;
            $group_member->is_accepted = '1';
            $done = $group_member->save();
            if ($done) {
                flash()->addSuccess('Group created successfully');
            }
        }

        return redirect()->route('admin.group');
    }

    public function group_edit($id)
    {
        $page_data['group_details'] = Group::find($id);
        $page_data['view_path'] = 'group.edit';

        return view('backend.index', $page_data);
    }

    public function group_updated($id, Request $request)
    {
        $request->validate([
            'logo' => 'mimes:jpeg,jpg,png,gif|nullable',
            'title' => 'required|max:255',
        ]);

        if ($request->logo && ! empty($request->logo)) {
            $logo_file_name = FileUploader::upload($request->logo, 'public/storage/groups/logo', 250);
        } else {
            $logo_file_name = null;
        }

        $group = Group::find($id);
        $group->title = $request->title;
        $group->subtitle = $request->subtitle;
        $group->status = $request->status;
        $group->privacy = $request->privacy;
        $group->about = $request->about;
        if ($request->logo && ! empty($request->logo)) {
            $group->logo = $logo_file_name;
        }
        $done = $group->save();

        if ($done) {
            flash()->addSuccess('Group updated successfully');
        }

        return redirect()->route('admin.group');
    }

    public function pages()
    {
        if (isset($_GET['delete']) && $_GET['delete'] == 'yes' && isset($_GET['id'])) {
            Page::find($_GET['id'])->delete();
            flash()->addSuccess('Page deleted successfully');

            return redirect()->back();
        }

        $page_data['view_path'] = 'page.list';
        $page_data['pages'] = Page::get();

        return view('backend.index', $page_data);
    }

    public function page_create()
    {
        $page_data['view_path'] = 'page.create';

        return view('backend.index', $page_data);
    }

    public function page_edit($id = '')
    {
        $page_data['page_details'] = Page::find($id);
        $page_data['view_path'] = 'page.edit';

        return view('backend.index', $page_data);
    }

    public function page_created(Request $request)
    {
        if ($request->category == 'Select a category') {
            flash()->addError('Please select a category');

            return redirect()->back()->withInput();
        }

        $request->validate([
            'logo' => 'mimes:jpeg,jpg,png,gif|nullable',
            'coverphoto' => 'mimes:jpeg,jpg,png,gif|nullable',
            'title' => 'required|max:255',
            'category' => 'required',
        ]);

        if ($request->logo && ! empty($request->logo)) {
            $logo_file_name = FileUploader::upload($request->logo, 'public/storage/pages/logo', 250);
        } else {
            $logo_file_name = null;
        }

        if ($request->coverphoto && ! empty($request->coverphoto)) {
            $coverphoto_file_name = FileUploader::upload($request->coverphoto, 'public/storage/pages/coverphoto', 250);
        } else {
            $coverphoto_file_name = null;
        }

        $page = new Page;
        $page->user_id = auth()->user()->id;
        $page->title = $request->title;
        $page->category_id = $request->category;
        $page->description = $request->description;
        if ($request->logo && ! empty($request->logo)) {
            $page->logo = $logo_file_name;
        }
        if ($request->coverphoto && ! empty($request->coverphoto)) {
            $page->coverphoto = $coverphoto_file_name;
        }
        $done = $page->save();
        if ($done) {
            // $pagelike = new Page_like();
            // $pagelike->page_id = $page->id;
            // $pagelike->user_id = auth()->user()->id;
            // $pagelike->role = 'admin';
            // $done = $pagelike->save();
            if ($done) {
                flash()->addSuccess('Page created successfully');
            }
        }

        return redirect()->route('admin.page');
    }

    public function page_updated($id, Request $request)
    {
        if ($request->category == 'Select a category') {
            flash()->addError('Please select a category');

            return redirect()->back()->withInput();
        }

        $request->validate([
            'logo' => 'mimes:jpeg,jpg,png,gif|nullable',
            'coverphoto' => 'mimes:jpeg,jpg,png,gif|nullable',
            'title' => 'required|max:255',
            'category' => 'required',
        ]);

        if ($request->logo && ! empty($request->logo)) {
            $logo_file_name = FileUploader::upload($request->logo, 'public/storage/pages/coverphoto', 250);
        } else {
            $logo_file_name = null;
        }

        if ($request->coverphoto && ! empty($request->coverphoto)) {
            $coverphoto_file_name = FileUploader::upload($request->coverphoto, 'public/storage/pages/coverphoto', 250);
        } else {
            $coverphoto_file_name = null;
        }

        $page = Page::find($id);
        $page->user_id = auth()->user()->id;
        $page->title = $request->title;
        $page->category_id = $request->category;
        $page->description = $request->description;
        if ($request->logo && ! empty($request->logo)) {
            $page->logo = $logo_file_name;
        }
        if ($request->coverphoto && ! empty($request->coverphoto)) {
            $page->coverphoto = $coverphoto_file_name;
        }
        $done = $page->save();

        if ($done) {
            flash()->addSuccess('Page updated successfully');
        }

        return redirect()->route('admin.page');
    }

    public function blogs()
    {
        if (isset($_GET['delete']) && $_GET['delete'] == 'yes' && isset($_GET['id'])) {
            Blog::find($_GET['id'])->delete();
            flash()->addSuccess('Blog deleted successfully');

            return redirect()->back();
        }

        $page_data['view_path'] = 'blog.list';
        $page_data['blogs'] = Blog::get();

        return view('backend.index', $page_data);
    }

    public function blog_create()
    {
        $page_data['view_path'] = 'blog.create';

        return view('backend.index', $page_data);
    }

    public function blog_edit($id = '')
    {
        $page_data['blog_details'] = Blog::find($id)->first();
        $page_data['view_path'] = 'blog.edit';

        return view('backend.index', $page_data);
    }

    public function blog_created(Request $request)
    {
        if ($request->category == 'Select a category') {
            flash()->addError('Please select a category');

            return redirect()->back()->withInput();
        }

        $request->validate([
            'title' => 'required|max:255',
            'category' => 'required',
            'start_date' => DateTimeRules::requiredBrowserDate(),
            'end_date' => DateTimeRules::requiredDateRangeEnd('start_date'),
        ]);

        $file_name = null;
        if ($request->image && ! empty($request->image)) {
            $file_name = FileUploader::upload($request->image, 'public/storage/blog/thumbnail', 370);
            FileUploader::upload($request->image, 'public/storage/blog/coverphoto/'.$file_name, 900);
        }

        $data['user_id'] = Auth()->user()->id;
        $data['title'] = $request->title;
        $data['category_id'] = $request->category;
        $data['created_at'] = date('Y-m-d H:i:s', time());
        $data['updated_at'] = date('Y-m-d H:i:s', time());
        $tags = json_decode($request->tag, true);
        $tag_array = [];
        if (is_array($tags)) {
            foreach ($tags as $key => $tag) {
                $tag_array[$key] = $tag['value'];
            }
        }
        $data['tag'] = json_encode($tag_array);
        $data['description'] = $request->description;
        if ($file_name !== null) {
            $data['thumbnail'] = $file_name;
        }
        $data['view'] = json_encode([]);

        DB::Table('blogs')->insert($data);
        flash()->addSuccess('Blog created successfully');

        return redirect()->route('admin.blog');
    }

    public function blog_updated(Request $request, $id)
    {
        if ($request->category == 'Select a category') {
            flash()->addError('Please select a category');

            return redirect()->back()->withInput();
        }

        $request->validate([
            'title' => 'required|max:255',
            'category' => 'required',
        ]);

        $file_name = null;
        if ($request->image && ! empty($request->image)) {
            $file_name = FileUploader::upload($request->image, 'public/storage/blog/thumbnail', 370);
            FileUploader::upload($request->image, 'public/storage/blog/coverphoto/'.$file_name, 900);
        }

        $blog = Blog::find($id);

        // $blog->user_id = Auth()->user()->id;
        // store image name for delete file operation
        $imagename = $blog->thumbnail;

        $blog->user_id = Auth()->user()->id;
        $blog->title = $request->title;
        $blog->category_id = $request->category;
        $tags = json_decode($request->tag, true);
        $tag_array = [];

        if (is_array($tags)) {
            foreach ($tags as $key => $tag) {
                $tag_array[$key] = $tag['value'];
            }
        }
        $blog->tag = json_encode($tag_array);
        $blog->description = $request->description;
        if ($file_name !== null) {
            $blog->thumbnail = $file_name;
        }
        $done = $blog->save();
        if ($done) {
            // just put the file name and folder name nothing more :)
            if (! empty($request->image)) {
                removeFile('blog', $imagename);
            }
            flash()->addSuccess('Blog updated successfully');

            return redirect()->route('admin.blog');
        }
    }

    // Badge Start
    public function Badge()
    {
        $page_data['badges'] = Badge::get();
        $page_data['badge_price'] = Setting::where('type', 'badge_price')->value('description');
        $page_data['view_path'] = 'badge.badge-history';

        return view('backend.index', $page_data);
    }

    public function delete_badge_history($id)
    {
        $badge = Badge::find($id);
        $badge->delete();
        flash()->addSuccess('Badge  has been Deleted successfully!');

        return redirect()->back();
    }

    public function badge_settings_save(Request $request)
    {
        Setting::where('type', 'badge_price')->update(['description' => $request->badge_price]);
        flash()->addSuccess('Badge Price Updated Successfully');

        return redirect()->back();
    }

    // =========== job Addon  Start Here  ============//

    //  Category View
    public function view_job_category()
    {
        $page_data['all_category'] = JobCategory::all();
        $page_data['view_path'] = 'jobs.job_category';

        return view('backend.index', $page_data);
    }

    public function create_job_category()
    {
        $page_data['view_path'] = 'jobs.create_category';

        return view('backend.index', $page_data);
    }

    public function save_job_category(Request $request)
    {
        $validated = $request->validate([
            'jobcategory' => ['required', 'max:255', 'string', $this->uniqueRule('job_categories', 'name')],
        ]);
        $jobcategories = new JobCategory;
        $jobcategories->name = $request->jobcategory;
        $done = $jobcategories->save();
        if ($done) {
            flash()->addSuccess('Job Category has been added successfully!');
        }

        return redirect()->back();
    }

    public function edit_job_category($id)
    {
        $page_data['jobcategories'] = JobCategory::find($id);
        $page_data['view_path'] = 'jobs.edit_category';

        return view('backend.index', $page_data);
    }

    public function update_job_category(Request $request, $id)
    {
        $jobcategories = JobCategory::findOrFail($id);
        $validated = $request->validate([
            'jobcategory' => ['required', 'max:255', 'string', $this->uniqueRule('job_categories', 'name', $jobcategories)],
        ]);
        $jobcategories->name = $request->jobcategory;
        $done = $jobcategories->save();
        if ($done) {
            flash()->addSuccess('Job Category has been updated successfully!');
        }

        return redirect()->route('admin.view.job.category');
    }

    public function delete_job_category($id)
    {
        $jobcategories = JobCategory::find($id);
        $jobcategories->delete();
        flash()->addSuccess('Job Category  has been Deleted successfully!');

        return redirect()->back();
    }

    // Job Create
    public function jobs()
    {
        $page_data['jobs'] = Job::where('status', '1')->orderBy('id', 'DESC')->get();
        $page_data['view_path'] = 'jobs.job_list';

        return view('backend.index', $page_data);
    }

    public function job_create()
    {
        $page_data['view_path'] = 'jobs.job_create';

        return view('backend.index', $page_data);
    }

    public function job_created(Request $request)
    {
        if ($request->category == 'Select a category') {
            flash()->addError('Please select a category');

            return redirect()->back()->withInput();
        }

        $request->validate([
            'title' => 'required|max:255',
            'category' => 'required',
        ]);

        $file_name = null;
        if ($request->image && ! empty($request->image)) {
            $file_name = FileUploader::upload($request->image, 'public/storage/job/thumbnail', 370);
            // FileUploader::upload($request->image, 'public/storage/job/coverphoto/' . $file_name, 900);
        }

        $data['user_id'] = Auth()->user()->id;
        $data['title'] = $request->title;
        $data['category_id'] = $request->category;
        $data['starting_salary_range'] = $request->starting_salary_range;
        $data['ending_salary_range'] = $request->ending_salary_range;
        $data['company'] = $request->company;
        $data['type'] = $request->type;
        $data['location'] = $request->location;
        $data['created_at'] = date('Y-m-d H:i:s', time());
        $data['updated_at'] = date('Y-m-d H:i:s', time());
        $data['description'] = $request->description;
        $data['status'] = $request->status;
        $data['start_date'] = DateTimeRules::browserDateAtCurrentTime($request->start_date);
        $data['end_date'] = DateTimeRules::browserDateAtCurrentTime($request->end_date);
        $data['is_published'] = $request->is_published ?? 0;
        if ($file_name !== null) {
            $data['thumbnail'] = $file_name;
        }
        DB::Table('jobs')->insert($data);
        flash()->addSuccess('Jobs created successfully');

        return redirect()->route('admin.job');
    }

    public function job_edit($id = '')
    {
        $page_data['job_details'] = Job::find($id);
        $page_data['view_path'] = 'jobs.job_edit';

        return view('backend.index', $page_data);
    }

    public function job_updated(Request $request, $id)
    {
        if ($request->category == 'Select a category') {
            flash()->addError('Please select a category');

            return redirect()->back()->withInput();
        }

        $request->validate([
            'title' => 'required|max:255',
            'category' => 'required',
            'start_date' => DateTimeRules::requiredBrowserDate(),
            'end_date' => DateTimeRules::requiredDateRangeEnd('start_date'),
        ]);

        if ($request->image) {
            $random_name = rand();
            $new_thumbnail = $random_name.'.'.$request->image->getClientOriginalExtension();
            $request->image->move(public_path('storage/job/thumbnail/'), $new_thumbnail);

            if (! empty($request->old_image) && file_exists(public_path('storage/job/thumbnail/'.$request->old_image))) {
                unlink(public_path('storage/job/thumbnail/'.$request->old_image));
            }
        } else {
            $new_thumbnail = $request->old_image;
        }

        $job = Job::find($id);

        // store image name for delete file operation
        $job->thumbnail = $new_thumbnail;
        $job->title = $request->title;
        $job->category_id = $request->category;
        $job->company = $request->company;
        $job->starting_salary_range = $request->starting_salary_range;
        $job->ending_salary_range = $request->ending_salary_range;
        $job->type = $request->type;
        $job->location = $request->location;

        $job->status = $request->status;
        $job->start_date = DateTimeRules::browserDateAtCurrentTime($request->start_date);
        $job->end_date = DateTimeRules::browserDateAtCurrentTime($request->end_date);
        $job->is_published = $request->has('is_published') ? 1 : 0;

        $job->description = $request->description;
        $done = $job->save();
        if ($done) {
            flash()->addSuccess('Job updated successfully');

            return redirect()->route('admin.job');
        }
    }

    public function delete_job($id)
    {
        $job = Job::find($id);
        $job_history = DB::table('payment_histories')->where('item_id', $job->id)->delete();
        $job_wishlist = JobWishlist::where('job_id', $job->id)->delete();
        $job_apply = JobApply::where('job_id', $job->id)->delete();
        $thumbnailPathName = 'public/storage/job/thumbnail/'.$job->thumbnail;
        if (file_exists($thumbnailPathName)) {
            unlink($thumbnailPathName);
        }
        $job->delete();
        flash()->addSuccess('Job has been Deleted successfully!');

        return redirect()->back();
    }

    public function pending_job()
    {
        $page_data['pending_job'] = Job::where('status', '0')->orderBy('id', 'DESC')->get();
        $page_data['view_path'] = 'jobs.pending_job';

        return view('backend.index', $page_data);
    }

    public function AllApplyList(?JobApplicationExportQuery $applications = null)
    {
        $applications ??= app(JobApplicationExportQuery::class);

        $page_data['all_list'] = $applications->forOwner(auth()->user()->id);
        $page_data['view_path'] = 'jobs.apply-list';

        return view('backend.index', $page_data);
    }

    public function applyListDelete($id)
    {
        $job = JobApply::find($id);
        $thumbnailPathName = 'public/storage/job/cv/'.$job->attachment;
        if (file_exists($thumbnailPathName)) {
            unlink($thumbnailPathName);
        }
        $job->delete();
        flash()->addSuccess('Apply List has been Deleted successfully!');

        return redirect()->back();
    }

    public function downloadPdf($id, ?StreamJobApplicationAttachmentAction $streamAttachment = null)
    {
        $streamAttachment ??= app(StreamJobApplicationAttachmentAction::class);

        $job = JobApply::find($id);
        $filePath = $job !== null && $job->attachment !== null
            ? public_path('storage/job/cv/'.$job->attachment)
            : null;

        if ($filePath === null || ! is_file($filePath)) {
            return back();
        }

        return $streamAttachment->handle($job);
    }

    public function jobPaymentHistory()
    {
        $page_data['job_history'] = DB::table('payment_histories')->where('item_type', 'job')->get();
        $page_data['view_path'] = 'jobs.job_payment_history';

        return view('backend.index', $page_data);
    }

    public function jobDeleteHistory($id)
    {
        $paymentHistory = DB::table('payment_histories')->where('id', $id)->delete();
        // $paymentHistory->delete();
        flash()->addSuccess('Job Payment History has been Deleted successfully!');

        return redirect()->back();
    }

    public function settings_view()
    {
        $page_data['job_price'] = Setting::where('type', 'job_price')->value('description');
        $page_data['day'] = Setting::where('type', 'day')->value('description');
        $page_data['view_path'] = 'jobs.job_package';

        return view('backend.index', $page_data);
    }

    public function settings_save(Request $request)
    {
        Setting::where('type', 'job_price')->update(['description' => $request->job_price]);
        Setting::where('type', 'day')->update(['description' => $request->day]);
        flash()->addSuccess('Job Price Updated Successfully');

        return redirect()->back();
    }

    // ============ job Addon  End Here ===============//

    public function users()
    {
        $users = User::query()->nonAdmins()->get();

        $page_data['users'] = $users;
        $page_data['view_path'] = 'users.list';

        return view('backend.index', $page_data);
    }

    public function user_add()
    {
        $page_data['view_path'] = 'users.add';

        return view('backend.index', $page_data);
    }

    public function user_store(Request $request)
    {
        // password validation
        //  $request->validate([
        //     'current_password' => ['required', new MatchOldPassword],
        //     'new_password' => ['required'],
        //     'new_confirm_password' => ['same:new_password'],
        // ]);

        $validated = $this->validate($request, [
            'email' => ['required', 'email', $this->uniqueRule('users', 'email')],
            'name' => 'required', 'max:255',
            'password' => ['required', 'string'],
            'gender' => 'required',
            'date_of_birth' => DateTimeRules::requiredBirthDate(),
        ]);

        $data = [];
        if ($request->photo && ! empty($request->photo)) {
            // $file_name = FileUploader::upload($request->photo, 'public/storage/userimage', 800);
            $file_name = FileUploader::upload($request->file('photo'), 'public/storage/userimage', 800);
            // Update to database
            $data['photo'] = $file_name;
        }

        $data['name'] = $validated['name'];
        $data['email'] = $validated['email'];
        $data['phone'] = $request->phone;
        $data['address'] = $request->address;
        $data['date_of_birth'] = DateTimeRules::birthDateTimestamp($validated['date_of_birth']);
        $data['about'] = $request->bio;
        $data['gender'] = $validated['gender'];

        $user_insert = new User($data);
        $user_insert->forceFill([
            'user_role' => UserRole::General->value,
            'password' => Hash::make($validated['password']),
            'friends' => '[]',
            'followers' => '[]',
            'status' => 1,
        ])->save();
        $user_insert->markEmailAsVerified();
        flash()->addSuccess('User added successfully');

        return redirect()->route('admin.users');
    }

    public function user_edit($id = '')
    {
        $page_data['user_data'] = User::find($id);
        $page_data['view_path'] = 'users.edit';

        return view('backend.index', $page_data);
    }

    public function user_update($id, Request $request)
    {
        $user = User::findOrFail($id);

        // password validation
        //  $request->validate([
        //     'current_password' => ['required', new MatchOldPassword],
        //     'new_password' => ['required'],
        //     'new_confirm_password' => ['same:new_password'],
        // ]);

        $validated = $this->validate($request, [
            'email' => ['required', 'email', $this->uniqueRule('users', 'email', $user)],
            'name' => 'required', 'max:255',
            'gender' => 'required',
            'date_of_birth' => DateTimeRules::requiredBirthDate(),
        ]);

        if ($request->photo && ! empty($request->photo)) {
            $file_name = FileUploader::upload($request->photo, 'public/storage/userimage', 800);

            $previous_image = public_path().'/storage/userimage/optimized/'.User::where('id', $id)->value('photo');
            $previous_image2 = public_path().'/storage/userimage/'.User::where('id', $id)->value('photo');
            if (file_exists($previous_image) && is_file($previous_image)) {
                unlink($previous_image);
                unlink($previous_image2);
            }

            // Update to database
            $data['photo'] = $file_name;
        }

        $data['name'] = $validated['name'];
        $data['email'] = $validated['email'];
        $data['phone'] = $request->phone;
        $data['address'] = $request->address;
        $data['date_of_birth'] = DateTimeRules::birthDateTimestamp($validated['date_of_birth']);
        $data['about'] = $request->bio;
        $data['gender'] = $validated['gender'];
        $date['updated_at'] = now();

        User::where('id', $id)->update($data);
        flash()->addSuccess('User updated successfully');

        return redirect()->route('admin.users');
    }

    public function user_delete($user_id = '')
    {
        User::find($user_id)->delete();
        flash()->addSuccess('User deleted successfully');

        return redirect()->route('admin.users');
    }

    public function user_status($user_id = '')
    {
        $query = User::where('id', $user_id);
        if ($query->value('status') == 1) {
            $query->update(['status' => 0]);
        } else {
            $query->update(['status' => 1]);
        }
        flash()->addSuccess('User Updated successfully');

        return redirect()->route('admin.users');
    }

    public function server_side_users_data(Request $request)
    {
        $parameters = $this->adminUsersDataTableParameters($request);
        $data = [];
        $total_number_of_row = User::query()->nonAdmins()->count();

        $usersQuery = User::query()
            ->select(['name', 'id', 'email', 'photo', 'status', 'email_verified_at'])
            ->nonAdmins();

        if ($parameters['search'] !== '') {
            $usersQuery->where(function ($query) use ($parameters) {
                $search = $parameters['search'];
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        $filtered_number_of_row = $parameters['search'] === ''
            ? $total_number_of_row
            : (clone $usersQuery)->count();
        $usersQuery->orderBy($parameters['sort'], $parameters['direction']);
        if ($parameters['sort'] !== 'id') {
            $usersQuery->orderByDesc('id');
        }

        $users = $usersQuery
            ->skip($parameters['start'])
            ->take($parameters['length'])
            ->get();

        foreach ($users as $key => $user) {
            // photo
            $photo = '<img src="'.User::get_user_image($user['photo']).'" alt="" height="50" width="50" class="img-fluid rounded-circle img-thumbnail">';

            // user name
            if ($user['email_verified_at'] == null) {
                $status = '<small><br><span class="badge bg-danger">'.get_phrase('Unverified').'</span></small>';
            } else {
                $status = '';
            }
            $name = $user['name'].$status;

            // user email
            $email = $user['email'];

            // Status
            if ($user['status'] != 1) {
                $status = '<span class="badge bg-danger">'.get_phrase('Disabled').'</span>';
            } else {
                $status = '<span class="badge bg-success">'.get_phrase('Active').'</span>';
            }

            if ($user['status'] != 1) {
                $status_btn = '<a class="dropdown-item" onclick="return confirm(&#39;'.get_phrase('Are You Sure').' ?&#39;)" href="'.route('admin.user.status', $user['id']).'">'.get_phrase('Active').'</a>';
            } else {
                $status_btn = '<a class="dropdown-item" onclick="return confirm(&#39;'.get_phrase('Are You Sure').' ?&#39;)" href="'.route('admin.user.status', $user['id']).'">'.get_phrase('Deactive').'</a>';
            }

            $action = '<div class="adminTable-action me-auto">
		                        <button type="button" class="eBtn eBtn-black dropdown-toggle table-action-btn-2" data-bs-toggle="dropdown" aria-expanded="false">
		                          '.get_phrase('Actions').'
		                        </button>
		                        <ul class="dropdown-menu dropdown-menu-end eDropdown-menu-2 eDropdown-table-action">
		                          <li><a class="dropdown-item" href="'.route('admin.user.edit', $user['id']).'">'.get_phrase('Edit').'</a>
		                          </li>
		                          <li>'.$status_btn.'</li>
		                          <li>
		                            <a class="dropdown-item" onclick="return confirm(&#39;'.get_phrase('Are You Sure Want To Delete').' ?&#39;)" href="'.route('admin.user.delete', $user['id']).'">'.get_phrase('Delete').'</a>
		                          </li>
		                        </ul>
		                    </div>';

            $nestedData['key'] = ++$key;
            $nestedData['photo'] = $photo;
            $nestedData['name'] = $name;
            $nestedData['email'] = $email;
            $nestedData['status'] = $status;
            $nestedData['action'] = $action.'<script>$("a, i").tooltip();</script>';
            $data[] = $nestedData;
        }

        $json_data = [
            'draw' => $parameters['draw'],
            'recordsTotal' => intval($total_number_of_row),
            'recordsFiltered' => intval($filtered_number_of_row),
            'data' => $data,
        ];

        return response()->json($json_data);
    }

    public function payment_settings()
    {
        $page_data['payment_gateways'] = Payment_gateway::get();
        $page_data['view_path'] = 'payment.payment_gateways';

        return view('backend.index', $page_data);
    }

    public function payment_gateway_edit($id)
    {
        $page_data['currencies'] = Currency::query()->select(['code'])->orderBy('code')->get();
        $page_data['payment_gateway'] = Payment_gateway::where('id', $id)->first();
        $page_data['paymentGatewayKeys'] = $page_data['payment_gateway']->decodedKeys();
        $page_data['view_path'] = 'payment.payment_gateway_edit';

        return view('backend.index', $page_data);
    }

    public function payment_gateway_update($id, Request $request)
    {
        $paymentGateway = Payment_gateway::findOrFail($id);
        $allowedKeyNames = array_keys($paymentGateway->decodedKeys());
        $paymentGateway->forceFill([
            'currency' => $request->input('currency'),
            'keys' => $request->only($allowedKeyNames),
        ])->save();
        flash()->addSuccess('Payment gateway has been updated');

        return redirect()->route('admin.settings.payment');
    }

    public function payment_gateway_status($id)
    {
        $query = Payment_gateway::where('id', $id);
        if ($query->value('status') == 1) {
            $query->update(['status' => 0]);
        } else {
            $query->update(['status' => 1]);
        }
        flash()->addSuccess('Payment gateway status changed successfully');

        return redirect()->route('admin.settings.payment');
    }

    public function payment_gateway_environment($id)
    {
        $query = Payment_gateway::where('id', $id);
        if ($query->value('test_mode') == 1) {
            $query->update(['test_mode' => 0]);
        } else {
            $query->update(['test_mode' => 1]);
        }
        flash()->addSuccess('Payment gateway environment changed successfully');

        return redirect()->back();
    }

    public function accountActiveReq()
    {
        $request_users = Account_active_request::with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        $page_data['view_path'] = 'users.accountActiveReq';
        $page_data['request_users'] = $request_users;

        return view('backend.index', $page_data);
    }

    public function acActiveReqApp($id, $user_id)
    {
        // Retrieve both the account request and user in one go
        $accountRequest = Account_active_request::find($id);
        $user = User::find($user_id);

        if ($accountRequest && $user) {
            // Update user's status and approve the account request in one line each
            $user->update(['status' => 1]);
            $accountRequest->delete(); // Remove the request after approval
            flash()->addSuccess('Account enable request successfully approved');
        } else {
            flash()->addError('Account enable request or user not found');
        }

        return redirect()->route('admin.users');
    }

    public function acActiveReDlt($id)
    {
        $accountRequest = Account_active_request::find($id);
        $accountRequest->delete();
        flash()->addSuccess('Account enable request delete successfully');

        return redirect()->back();
    }
}
