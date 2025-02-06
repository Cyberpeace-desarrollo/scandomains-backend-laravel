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
        Schema::create('found_suspicious_domains', function (Blueprint $table) {
            $table->id();
            $table->string('suspicious_domain');
            $table->date('found_date')->default(now());
            $table->string('photo_url');
            $table->boolean('flag')->nullable();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('found_suspicious_domains');
    }
};
