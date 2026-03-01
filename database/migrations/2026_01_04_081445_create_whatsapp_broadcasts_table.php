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
        if (!Schema::hasTable('whatsapp_broadcasts')) {
            Schema::create('whatsapp_broadcasts', function (Blueprint $table) {
                $table->id();
                $table->text('message_template');
                $table->enum('send_type', ['text', 'template'])->default('text');
                // Course and Group references for filtering students
                $table->unsignedBigInteger('course_id')->nullable();
                $table->unsignedBigInteger('group_id')->nullable();
                $table->integer('total_recipients')->default(0);
                $table->integer('sent_count')->default(0);
                $table->integer('failed_count')->default(0);
                $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();

                $table->index('status');
                $table->index('created_by');
                $table->index(['course_id', 'group_id']);
            });
        } else {
            // Modify existing table - remove old columns and add new ones
            Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
                // Drop old index first if it exists
                try {
                    $table->dropIndex(['class_id', 'subject_id']);
                } catch (\Exception $e) {
                    // Index might not exist, ignore
                }
                
                // Drop old columns if they exist
                if (Schema::hasColumn('whatsapp_broadcasts', 'class_id')) {
                    $table->dropColumn('class_id');
                }
                if (Schema::hasColumn('whatsapp_broadcasts', 'subject_id')) {
                    $table->dropColumn('subject_id');
                }
            });
            
            // Add new columns in separate operation
            Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
                if (!Schema::hasColumn('whatsapp_broadcasts', 'course_id')) {
                    $table->unsignedBigInteger('course_id')->nullable()->after('send_type');
                }
                if (!Schema::hasColumn('whatsapp_broadcasts', 'group_id')) {
                    $table->unsignedBigInteger('group_id')->nullable()->after('course_id');
                }
            });
            
            // Add new index in separate operation
            Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
                $table->index(['course_id', 'group_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_broadcasts');
    }
};

