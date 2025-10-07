<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CmsV2MediaDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('cms_v2_media')) {
            return;
        }

        // Insert a 1x1 transparent PNG if not present
        $filename = 'placeholder-1x1.png';
        $exists = DB::table('cms_v2_media')->where('filename', $filename)->exists();
        if ($exists) {
            return;
        }

        $b64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8Xw8AAoMBgH5+CTkAAAAASUVORK5CYII=';
        $data = base64_decode($b64);
        // cms_v2_media.id は ulid/uuid 型だが、マイグレーションは ulid 優先。
        // MySQL 互換のため ULID を使用。
        $uuid = (string) Str::ulid();
        $checksum = hash('sha256', $data);

        DB::table('cms_v2_media')->insert([
            'id' => $uuid,
            'filename' => $filename,
            'mime' => 'image/png',
            'size' => strlen($data),
            'checksum' => $checksum,
            'data' => $data,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
