<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add verification columns if they don't exist
            if (! Schema::hasColumn('users', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('email_verified_at');
            }

            if (! Schema::hasColumn('users', 'slack_id')) {
                $table->string('slack_id')->nullable()->unique()->after('email');
            }

            if (! Schema::hasColumn('users', 'status')) {
                $table->tinyInteger('status')->default(2)->after('slack_id')
                    ->comment('0=deactivated, 1=active, 2=for_verification');
            }

            if (! Schema::hasColumn('users', 'default_approvers')) {
                $table->json('default_approvers')->nullable()->after('status')
                    ->comment('JSON: {hr_id, tl_id, pm_id}');
            }

            // Remove legacy role columns if they exist
            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropColumn('role_id');
            }

            if (Schema::hasColumn('users', 'secondary_role_id')) {
                $table->dropColumn('secondary_role_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['verified_at', 'slack_id', 'status', 'default_approvers']);
        });
    }
};
