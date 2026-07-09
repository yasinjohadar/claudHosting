<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('companyname')->nullable()->after('country_code');
            $table->string('address1')->nullable()->after('companyname');
            $table->string('address2')->nullable()->after('address1');
            $table->string('city')->nullable()->after('address2');
            $table->string('state')->nullable()->after('city');
            $table->string('postcode', 32)->nullable()->after('state');
            $table->string('country', 2)->nullable()->after('postcode');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'companyname',
                'address1',
                'address2',
                'city',
                'state',
                'postcode',
                'country',
            ]);
        });
    }
};
