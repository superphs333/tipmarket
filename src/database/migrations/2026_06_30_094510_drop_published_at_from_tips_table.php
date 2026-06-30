<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('tips', 'published_at')) {
            return;
        }

        if (! $this->indexExists('tips_status_index')) {
            Schema::table('tips', function (Blueprint $table) {
                $table->index('status');
            });
        }

        if (! $this->indexExists('tips_category_id_status_index')) {
            Schema::table('tips', function (Blueprint $table) {
                $table->index(['category_id', 'status']);
            });
        }

        if (! $this->indexExists('tips_status_audience_index')) {
            Schema::table('tips', function (Blueprint $table) {
                $table->index(['status', 'audience']);
            });
        }

        Schema::table('tips', function (Blueprint $table) {
            if ($this->indexExists('tips_status_published_at_index')) {
                $table->dropIndex('tips_status_published_at_index');
            }

            if ($this->indexExists('tips_category_id_status_published_at_index')) {
                $table->dropIndex('tips_category_id_status_published_at_index');
            }

            if ($this->indexExists('tips_status_audience_published_at_index')) {
                $table->dropIndex('tips_status_audience_published_at_index');
            }
        });

        Schema::table('tips', function (Blueprint $table) {
            $table->dropColumn('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tips', 'published_at')) {
            return;
        }

        Schema::table('tips', function (Blueprint $table) {
            $table->timestamp('published_at')
                ->nullable()
                ->after('status')
                ->comment('팁 공개 발행 시각');

            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status', 'published_at']);
            $table->index(['status', 'audience', 'published_at']);
        });

        Schema::table('tips', function (Blueprint $table) {
            if ($this->indexExists('tips_status_index')) {
                $table->dropIndex('tips_status_index');
            }

            if ($this->indexExists('tips_category_id_status_index')) {
                $table->dropIndex('tips_category_id_status_index');
            }

            if ($this->indexExists('tips_status_audience_index')) {
                $table->dropIndex('tips_status_audience_index');
            }
        });
    }

    private function indexExists(string $index): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return collect(DB::select('SHOW INDEX FROM tips'))
            ->contains(fn (object $row): bool => $row->Key_name === $index);
    }
};
