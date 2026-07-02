<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('job_categories')) {
            Schema::create('job_categories', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();

                $table->index('name', 'job_categories_name_idx');
            });
        }

        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('title')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->string('starting_salary_range')->nullable();
                $table->string('ending_salary_range')->nullable();
                $table->string('company')->nullable();
                $table->string('type')->nullable();
                $table->text('location')->nullable();
                $table->longText('description')->nullable();
                $table->string('status')->nullable()->default('0');
                $table->dateTime('start_date')->nullable();
                $table->dateTime('end_date')->nullable();
                $table->boolean('is_published')->default(false);
                $table->string('thumbnail')->nullable();
                $table->timestamps();

                $table->index(['status', 'id'], 'jobs_status_id_idx');
                $table->index(['user_id', 'id'], 'jobs_user_id_id_idx');
                $table->index(['category_id', 'status', 'id'], 'jobs_category_status_id_idx');
                $table->index(['end_date', 'id'], 'jobs_end_date_id_idx');
            });
        }

        if (! Schema::hasTable('job_wishlists')) {
            Schema::create('job_wishlists', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('job_id')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'job_id'], 'job_wishlists_user_job_unique');
                $table->index(['job_id', 'user_id'], 'job_wishlists_job_user_idx');
            });
        }

        if (! Schema::hasTable('job_applies')) {
            Schema::create('job_applies', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('job_id')->nullable();
                $table->unsignedBigInteger('owner_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('attachment')->nullable();
                $table->timestamps();

                $table->index(['owner_id', 'id'], 'job_applies_owner_id_id_idx');
                $table->index(['user_id', 'id'], 'job_applies_user_id_id_idx');
                $table->index(['job_id', 'id'], 'job_applies_job_id_id_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applies');
        Schema::dropIfExists('job_wishlists');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_categories');
    }
};
