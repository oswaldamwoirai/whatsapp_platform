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
        Schema::create('chatbot_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('chatbot_flows')->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['message', 'condition', 'delay', 'action', 'input']);
            $table->json('config');
            $table->foreignId('parent_id')->nullable()->constrained('chatbot_nodes')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['flow_id', 'parent_id']);
            $table->index(['flow_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_nodes');
    }
};
