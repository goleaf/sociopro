use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;



//Table structure for table `block_users`
if(!Schema::hasTable('block_users'))
{
    Schema::create('block_users', function (Blueprint $table) {
        $table->id();
        $table->integer('user_id')->nullable();
        $table->integer('block_user')->nullable();
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
}
