<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('original_url');
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->string('code', 8)->collation('utf8mb4_bin')->unique()->nullable();
            } else {
                $table->string('code', 8)->unique()->nullable();
            }
            $table->unsignedBigInteger('clicks')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('short_links');
    }
};
