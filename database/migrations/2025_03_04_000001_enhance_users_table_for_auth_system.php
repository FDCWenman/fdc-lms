<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Slack integration
            $table->string('slack_id')->nullable()->unique()->after('email');
            
            // Account status: 0=deactivated, 1=active, 2=for_verification
            $table->tinyInteger('status')->default(2)->after('email_verified_at')->index();
            
            // Role management (will be referenced after Spatie migrations run)
            $table->unsignedBigInteger('primary_role_id')->nullable()->after('status');
            $table->unsignedBigInteger('secondary_role_id')->nullable()->after('primary_role_id');
            
            // Default approvers stored as JSON
            // Structure: {"hr_approver_id": 1, "lead_approver_id": 2, "pm_approver_id": 3}
            $table->json('default_approvers')->nullable()->after('secondary_role_id');
            
            // Employment details
            $table->date('hired_date')->nullable()->after('default_approvers');
            
            // Track verification timestamp separately from email_verified_at
            $table->timestamp('verified_at')->nullable()->after('email_verified_at');
            
            // Add indexes for performance
            $table->index('slack_id');
            $table->index(['primary_role_id', 'secondary_role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['users_slack_id_index']);
            $table->dropIndex(['users_primary_role_id_secondary_role_id_index']);
            $table->dropIndex(['users_status_index']);
            
            $table->dropColumn([
                'slack_id',
                'status',
                'primary_role_id',
                'secondary_role_id',
                'default_approvers',
                'hired_date',
                'verified_at',
            ]);
        });
    }
};
