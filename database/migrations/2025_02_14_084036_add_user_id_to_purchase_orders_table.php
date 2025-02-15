<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class AddUserIdToPurchaseOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('special_note');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Set default user_id for existing records where it is null
        $defaultUser = User::first();
        if ($defaultUser) {
            $defaultUserId = $defaultUser->id;
            DB::table('purchase_orders')->whereNull('user_id')->update(['user_id' => $defaultUserId]);
        }
    }

    public function down()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
}