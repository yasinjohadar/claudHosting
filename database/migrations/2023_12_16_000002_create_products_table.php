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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whmcs_id')->unique();
            $table->string('type');
            $table->unsignedInteger('gid')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('paytype')->default('recurring');
            $table->json('pricing')->nullable();
            $table->unsignedInteger('currency')->default(1);
            $table->boolean('showdomainoptions')->default(false);
            $table->boolean('stockcontrol')->default(false);
            $table->unsignedInteger('qty')->default(0);
            $table->boolean('prorata')->default(false);
            $table->date('proratadate')->nullable();
            $table->boolean('proratachargenextmonth')->default(false);
            $table->boolean('hidden')->default(false);
            $table->boolean('tax')->default(true);
            $table->boolean('allowqty')->default(false);
            $table->boolean('recurring')->default(true);
            $table->boolean('autoterminate')->default(true);
            $table->boolean('autorenew')->default(true);
            $table->string('servertype')->nullable();
            $table->unsignedInteger('servergroup')->nullable();
            $table->text('configoption1')->nullable();
            $table->text('configoption2')->nullable();
            $table->text('configoption3')->nullable();
            $table->text('configoption4')->nullable();
            $table->text('configoption5')->nullable();
            $table->text('configoption6')->nullable();
            $table->text('configoption7')->nullable();
            $table->text('configoption8')->nullable();
            $table->text('configoption9')->nullable();
            $table->text('configoption10')->nullable();
            $table->text('configoption11')->nullable();
            $table->text('configoption12')->nullable();
            $table->text('configoption13')->nullable();
            $table->text('configoption14')->nullable();
            $table->text('configoption15')->nullable();
            $table->text('configoption16')->nullable();
            $table->text('configoption17')->nullable();
            $table->text('configoption18')->nullable();
            $table->text('configoption19')->nullable();
            $table->text('configoption20')->nullable();
            $table->text('configoption21')->nullable();
            $table->text('configoption22')->nullable();
            $table->text('configoption23')->nullable();
            $table->text('configoption24')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('whmcs_id');
            $table->index('type');
            $table->index('gid');
            $table->index('hidden');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};