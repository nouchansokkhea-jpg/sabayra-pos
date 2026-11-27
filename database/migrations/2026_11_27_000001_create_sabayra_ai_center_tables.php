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
        Schema::table('plans', function (Blueprint $table): void {
            if (! Schema::hasColumn('plans', 'has_ai_assistant')) {
                $table->boolean('has_ai_assistant')->default(true);
            }
            if (! Schema::hasColumn('plans', 'has_ai_menu_creator')) {
                $table->boolean('has_ai_menu_creator')->default(true);
            }
            if (! Schema::hasColumn('plans', 'has_ai_marketing')) {
                $table->boolean('has_ai_marketing')->default(false);
            }
            if (! Schema::hasColumn('plans', 'has_ai_forecasting')) {
                $table->boolean('has_ai_forecasting')->default(false);
            }
            if (! Schema::hasColumn('plans', 'ai_monthly_credits')) {
                $table->integer('ai_monthly_credits')->default(100);
            }
            if (! Schema::hasColumn('plans', 'ai_max_tokens_per_request')) {
                $table->integer('ai_max_tokens_per_request')->default(2048);
            }
        });

        Schema::table('business_ai_insights', function (Blueprint $table): void {
            if (! Schema::hasColumn('business_ai_insights', 'title')) {
                $table->string('title', 160)->nullable();
            }
            if (! Schema::hasColumn('business_ai_insights', 'summary')) {
                $table->text('summary')->nullable();
            }
            if (! Schema::hasColumn('business_ai_insights', 'recommended_action')) {
                $table->string('recommended_action', 255)->nullable();
            }
            if (! Schema::hasColumn('business_ai_insights', 'metrics_json')) {
                $table->json('metrics_json')->nullable();
            }
            if (! Schema::hasColumn('business_ai_insights', 'priority')) {
                $table->string('priority', 20)->default('medium');
            }
            if (! Schema::hasColumn('business_ai_insights', 'period_start')) {
                $table->date('period_start')->nullable();
            }
            if (! Schema::hasColumn('business_ai_insights', 'period_end')) {
                $table->date('period_end')->nullable();
            }
            if (! Schema::hasColumn('business_ai_insights', 'status')) {
                $table->string('status', 20)->default('new');
            }
            if (! Schema::hasColumn('business_ai_insights', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('business_ai_insights', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable();
            }
        });

        if (! Schema::hasTable('ai_feature_settings')) {
            Schema::create('ai_feature_settings', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete()->unique();
                $table->string('preferred_language', 20)->default('auto');
                $table->string('preferred_tone', 30)->default('friendly');
                $table->string('provider_override', 40)->nullable();
                $table->string('model_override', 80)->nullable();
                $table->decimal('temperature', 3, 2)->default(0.40);
                $table->boolean('assistant_enabled')->default(true);
                $table->boolean('menu_creator_enabled')->default(true);
                $table->boolean('marketing_studio_enabled')->default(true);
                $table->boolean('forecasting_enabled')->default(true);
                $table->boolean('insights_enabled')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_credit_ledgers')) {
            Schema::create('ai_credit_ledgers', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->string('period_ym', 7); // YYYY-MM
                $table->integer('allocated_credits')->default(100);
                $table->integer('bonus_credits')->default(0);
                $table->integer('used_credits')->default(0);
                $table->integer('reserved_credits')->default(0);
                $table->timestamps();

                $table->unique(['business_id', 'period_ym'], 'ai_credit_ledgers_biz_period_unique');
            });
        }

        if (! Schema::hasTable('ai_usage_logs')) {
            Schema::create('ai_usage_logs', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('request_uuid', 64)->unique();
                $table->string('feature', 50); // assistant, menu_creator, marketing_studio, inventory_forecast, sales_prediction, insights
                $table->string('provider', 40); // gemini, openai
                $table->string('model', 80);
                $table->integer('prompt_tokens')->default(0);
                $table->integer('completion_tokens')->default(0);
                $table->integer('total_tokens')->default(0);
                $table->integer('credits_charged')->default(1);
                $table->decimal('estimated_cost_usd', 10, 6)->default(0.000000);
                $table->integer('latency_ms')->nullable();
                $table->string('status', 25)->default('pending'); // pending, completed, failed, refunded, rejected
                $table->string('error_code', 60)->nullable();
                $table->string('error_message', 500)->nullable();
                $table->timestamps();

                $table->index(['business_id', 'created_at'], 'ai_usage_logs_biz_created_idx');
                $table->index(['business_id', 'feature', 'status'], 'ai_usage_logs_biz_feature_status_idx');
            });
        }

        if (! Schema::hasTable('ai_conversations')) {
            Schema::create('ai_conversations', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title', 160);
                $table->string('preferred_language', 20)->default('auto');
                $table->integer('message_count')->default(0);
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'user_id', 'last_message_at'], 'ai_conversations_biz_user_last_idx');
            });
        }

        if (! Schema::hasTable('ai_messages')) {
            Schema::create('ai_messages', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignUuid('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('role', 20); // user, assistant, tool
                $table->text('content');
                $table->string('language_detected', 20)->nullable();
                $table->json('tool_calls_json')->nullable();
                $table->json('tool_results_json')->nullable();
                $table->json('data_sources_json')->nullable();
                $table->string('provider', 40)->nullable();
                $table->string('model', 80)->nullable();
                $table->foreignUuid('usage_log_id')->nullable()->constrained('ai_usage_logs')->nullOnDelete();
                $table->timestamps();

                $table->index(['conversation_id', 'created_at'], 'ai_messages_conv_created_idx');
                $table->index(['business_id', 'created_at'], 'ai_messages_biz_created_idx');
            });
        }

        if (! Schema::hasTable('ai_generated_contents')) {
            Schema::create('ai_generated_contents', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('feature', 40); // menu_creator, marketing_studio
                $table->string('content_type', 50); // description, improve, translate_km, translate_en, facebook_caption, tiktok_script, etc.
                $table->string('platform', 30)->default('menu'); // menu, facebook, tiktok, telegram, general
                $table->foreignUuid('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('language', 20)->default('km');
                $table->string('tone', 30)->nullable();
                $table->string('title', 160)->nullable();
                $table->json('input_payload')->nullable();
                $table->text('output_text');
                $table->json('structured_output')->nullable();
                $table->string('status', 20)->default('draft'); // draft, applied, archived
                $table->foreignUuid('usage_log_id')->nullable()->constrained('ai_usage_logs')->nullOnDelete();
                $table->timestamps();

                $table->index(['business_id', 'feature', 'created_at'], 'ai_generated_contents_biz_feat_created_idx');
            });
        }

        if (! Schema::hasTable('ai_forecasts')) {
            Schema::create('ai_forecasts', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('forecast_type', 30); // inventory, sales
                $table->string('horizon', 20); // 1d, 7d, 30d, stock_runway
                $table->string('status', 30)->default('ready'); // ready, insufficient_data, failed
                $table->integer('historical_days_available')->default(0);
                $table->integer('data_points_count')->default(0);
                $table->string('confidence_level', 20)->default('insufficient'); // high, medium, low, insufficient
                $table->json('statistical_payload');
                $table->text('ai_explanation')->nullable();
                $table->text('limitations')->nullable();
                $table->json('assumptions_json')->nullable();
                $table->foreignUuid('usage_log_id')->nullable()->constrained('ai_usage_logs')->nullOnDelete();
                $table->timestamp('generated_at')->useCurrent();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'forecast_type', 'horizon', 'generated_at'], 'ai_forecasts_lookup_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_forecasts');
        Schema::dropIfExists('ai_generated_contents');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('ai_credit_ledgers');
        Schema::dropIfExists('ai_feature_settings');

        Schema::table('business_ai_insights', function (Blueprint $table): void {
            if (Schema::hasColumn('business_ai_insights', 'reviewed_by')) {
                $table->dropConstrainedForeignId('reviewed_by');
            }
            $dropCols = array_values(array_filter([
                Schema::hasColumn('business_ai_insights', 'title') ? 'title' : null,
                Schema::hasColumn('business_ai_insights', 'summary') ? 'summary' : null,
                Schema::hasColumn('business_ai_insights', 'recommended_action') ? 'recommended_action' : null,
                Schema::hasColumn('business_ai_insights', 'metrics_json') ? 'metrics_json' : null,
                Schema::hasColumn('business_ai_insights', 'priority') ? 'priority' : null,
                Schema::hasColumn('business_ai_insights', 'period_start') ? 'period_start' : null,
                Schema::hasColumn('business_ai_insights', 'period_end') ? 'period_end' : null,
                Schema::hasColumn('business_ai_insights', 'status') ? 'status' : null,
                Schema::hasColumn('business_ai_insights', 'reviewed_at') ? 'reviewed_at' : null,
            ]));
            if ($dropCols !== []) {
                $table->dropColumn($dropCols);
            }
        });

        Schema::table('plans', function (Blueprint $table): void {
            $dropCols = array_values(array_filter([
                Schema::hasColumn('plans', 'has_ai_assistant') ? 'has_ai_assistant' : null,
                Schema::hasColumn('plans', 'has_ai_menu_creator') ? 'has_ai_menu_creator' : null,
                Schema::hasColumn('plans', 'has_ai_marketing') ? 'has_ai_marketing' : null,
                Schema::hasColumn('plans', 'has_ai_forecasting') ? 'has_ai_forecasting' : null,
                Schema::hasColumn('plans', 'ai_monthly_credits') ? 'ai_monthly_credits' : null,
                Schema::hasColumn('plans', 'ai_max_tokens_per_request') ? 'ai_max_tokens_per_request' : null,
            ]));
            if ($dropCols !== []) {
                $table->dropColumn($dropCols);
            }
        });
    }
};
