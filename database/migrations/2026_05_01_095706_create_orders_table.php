<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\User;
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
        Schema::create('orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignIdFor(User::class, 'created_by')->constrained()->cascadeOnDelete();

            $table->string('status')->default(OrderStatus::PENDING->value);
            $table->string('customer_name');
            $table->string('customer_ico')->nullable();
            $table->string('customer_dic')->nullable();
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('address_city');
            $table->string('address_country');
            $table->string('address_zip');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
