<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 태그명 공백을 제거하고 tags.name에 unique index를 추가한다.
     *
     * 기존 데이터에 "전세 계약"과 "전세계약"처럼 정규화 후 같은 이름이 되는 태그가 있으면
     * 가장 먼저 생성된 태그를 대표 태그로 남기고, tag_tip 연결을 대표 태그로 옮긴 뒤 중복 태그를 삭제한다.
     */
    public function up(): void
    {
        $keepers = [];

        DB::table('tags')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function (object $tag) use (&$keepers): void {
                $normalizedName = $this->normalizeName((string) $tag->name, (int) $tag->id);

                if (! isset($keepers[$normalizedName])) {
                    $keepers[$normalizedName] = (int) $tag->id;

                    DB::table('tags')
                        ->where('id', $tag->id)
                        ->update(['name' => $normalizedName]);

                    return;
                }

                $keeperId = $keepers[$normalizedName];
                $duplicateId = (int) $tag->id;

                DB::table('tag_tip')
                    ->where('tag_id', $duplicateId)
                    ->pluck('tip_id')
                    ->each(function (int $tipId) use ($keeperId): void {
                        DB::table('tag_tip')->insertOrIgnore([
                            'tag_id' => $keeperId,
                            'tip_id' => $tipId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    });

                DB::table('tag_tip')
                    ->where('tag_id', $duplicateId)
                    ->delete();

                DB::table('tags')
                    ->where('id', $duplicateId)
                    ->delete();
            });

        Schema::table('tags', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    /**
     * name unique index만 제거한다.
     *
     * up()에서 수행한 태그명 정규화와 중복 태그 병합은 데이터 정리 작업이므로 되돌리지 않는다.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }

    /**
     * 태그명에서 # 기호와 모든 공백을 제거한다.
     */
    private function normalizeName(string $name, int $fallbackId): string
    {
        $name = trim($name);
        $name = ltrim($name, '#');
        $name = preg_replace('/\s+/u', '', $name) ?? $name;

        return $name !== '' ? $name : "tag{$fallbackId}";
    }
};
