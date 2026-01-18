<?php

use App\Library\Domain\BookRental\ValueObjects\Status;
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
        Schema::create('book_rents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->timestamp('rented_at');
            $table->timestamp('due_date');
            $table->timestamp('returned_at')->nullable();
            $table->enum('status', Status::ALL)->default(Status::ACTIVE);
            $table->integer('reading_progress')->default(0);
            $table->integer('extension_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['book_id', 'status']);
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_rents');
    }
};
