<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cyberpanel_websites')) {
            Schema::create('cyberpanel_websites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('domain')->unique();
                $table->string('owner')->nullable();
                $table->string('email')->nullable();
                $table->string('package')->nullable();
                $table->string('php_version')->nullable();
                $table->string('status')->default('active');
                $table->timestamp('joined_at')->nullable();
                $table->timestamp('subscription_ends_at')->nullable();
                $table->timestamp('last_renewed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index('status');
                $table->index('package');
                $table->index('user_id');
            });
        }

        if (! Schema::hasTable('cyberpanel_wordpress_sites')) {
            Schema::create('cyberpanel_wordpress_sites', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('cyberpanel_website_id')->constrained('cyberpanel_websites')->cascadeOnDelete();
                $table->string('domain');
                $table->string('wp_admin_url')->nullable();
                $table->string('wp_user')->nullable();
                $table->string('status')->default('provisioning');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index('domain');
                $table->index('status');
            });
        }

        if (! Schema::hasColumn('products', 'cyberpanel_provision')) {
            Schema::table('products', function (Blueprint $table) {
                $after = Schema::hasColumn('products', 'whm_provision') ? 'whm_provision' : 'coolify_provision';
                $table->json('cyberpanel_provision')->nullable()->after($after);
            });
        }

        if (! Schema::hasColumn('package_order_requests', 'cyberpanel_website_id')) {
            Schema::table('package_order_requests', function (Blueprint $table) {
                $after = Schema::hasColumn('package_order_requests', 'whm_account_id') ? 'whm_account_id' : 'status';
                $table->foreignId('cyberpanel_website_id')->nullable()->after($after)
                    ->constrained('cyberpanel_websites')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('invoices', 'cyberpanel_website_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $after = Schema::hasColumn('invoices', 'whm_account_id') ? 'whm_account_id' : 'customer_id';
                $table->foreignId('cyberpanel_website_id')->nullable()->after($after)
                    ->constrained('cyberpanel_websites')->nullOnDelete();
                $table->index('cyberpanel_website_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'cyberpanel_website_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropForeign(['cyberpanel_website_id']);
                $table->dropColumn('cyberpanel_website_id');
            });
        }

        if (Schema::hasColumn('package_order_requests', 'cyberpanel_website_id')) {
            Schema::table('package_order_requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('cyberpanel_website_id');
            });
        }

        if (Schema::hasColumn('products', 'cyberpanel_provision')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('cyberpanel_provision');
            });
        }

        Schema::dropIfExists('cyberpanel_wordpress_sites');
        Schema::dropIfExists('cyberpanel_websites');
    }
};
