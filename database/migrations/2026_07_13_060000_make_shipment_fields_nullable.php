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
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('status')->nullable()->change();
            $table->string('branch')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->date('shipping_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('status')->nullable(false)->change();
            $table->string('branch')->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->date('shipping_date')->nullable(false)->change();
        });
    }
};