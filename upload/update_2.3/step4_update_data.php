use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

if(DB::table('settings')->where('type', 'theme_color')->count() == 0){
DB::table('settings')->insert(['type' => 'theme_color', 'description' => 'default' ,'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')]);
}


if(DB::table('settings')->where('type', 'zitsi_configuration')->count() == 0){
    $zitsi['account_email'] = 'demo@gmail.com';
    $zitsi['jitsi_app_id'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    $zitsi['jitsi_jwt'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    $zitsi_key = json_encode($zitsi);

DB::table('settings')->insert(['type' => 'zitsi_configuration', 'description' => $zitsi_key ,'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')]);

}

if (!Schema::hasColumn('media_files', 'album_image_id')) {
    Schema::table('media_files', function (Blueprint $table) {
        $table->string('album_image_id')->nullable();
    });
}

if (!Schema::hasColumn('posts', 'album_image_id')) {
    Schema::table('posts', function (Blueprint $table) {
        $table->string('album_image_id')->nullable();
    });
}