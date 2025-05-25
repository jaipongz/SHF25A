<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['file', 'folder']);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->text('path')->nullable(); // ใช้เก็บ path ของไฟล์ถ้าเป็น type = file
            $table->unsignedBigInteger('size')->nullable(); // ใช้เฉพาะกับไฟล์
            $table->boolean('is_deleted')->default(false); // สำหรับถังขยะ
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
}
