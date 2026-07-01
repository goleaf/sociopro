use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;



if (!Schema::hasColumn('posts', 'mobile_app_image')) {
    Schema::table('posts', function (Blueprint $table) {
        $table->string('mobile_app_image')->nullable();
    });
}

if (!Schema::hasColumn('videos', 'mobile_app_image')) {
    Schema::table('videos', function (Blueprint $table) {
        $table->string('mobile_app_image')->nullable();
    });
}
if (!Schema::hasColumn('personal_access_tokens', 'expires_at')) {
    Schema::table('personal_access_tokens', function (Blueprint $table) {
        $table->string('expires_at')->nullable();
    });
}

