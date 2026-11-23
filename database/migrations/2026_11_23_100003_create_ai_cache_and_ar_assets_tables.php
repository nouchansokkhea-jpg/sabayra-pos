<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_ai_insights', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('insight_type', 50); // weekly_summary, menu_audit, sentiment_radar, upsell_pairings
            $table->json('payload');
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();

            $table->index(['business_id', 'insight_type']);
        });

        Schema::create('ar_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUuid('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->string('title');
            $table->text('asset_url'); // .glb, .usdz
            $table->text('preview_image_url')->nullable();
            $table->bigInteger('file_size_bytes')->nullable();
            $table->string('format', 20)->default('glb');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_assets');
        Schema::dropIfExists('business_ai_insights');
    }
};
