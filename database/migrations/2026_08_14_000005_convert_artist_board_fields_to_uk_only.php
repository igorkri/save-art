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
        // Pattern A: `descriptions` is a top-level {uk,en} JSON column.
        Schema::table('artist_boards', function (Blueprint $table) {
            $table->text('descriptions_uk')->nullable()->after('descriptions');
        });

        DB::statement("UPDATE artist_boards SET descriptions_uk = JSON_UNQUOTE(JSON_EXTRACT(descriptions, '$.uk')) WHERE descriptions IS NOT NULL");

        Schema::table('artist_boards', function (Blueprint $table) {
            $table->dropColumn('descriptions');
        });

        Schema::table('artist_boards', function (Blueprint $table) {
            $table->renameColumn('descriptions_uk', 'descriptions');
        });

        // Pattern B: `titles` and `data` contain nested {uk,en} values inside JSON structures.
        DB::table('artist_boards')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $titles = $row->titles ? json_decode($row->titles, true) : null;
                if (is_array($titles)) {
                    foreach (['title1', 'title2', 'description'] as $key) {
                        if (isset($titles[$key]) && is_array($titles[$key])) {
                            $titles[$key] = $titles[$key]['uk'] ?? null;
                        }
                    }
                }

                $data = $row->data ? json_decode($row->data, true) : null;
                if (is_array($data)) {
                    foreach ($data as &$artist) {
                        if (isset($artist['name']) && is_array($artist['name'])) {
                            $artist['name'] = $artist['name']['uk'] ?? null;
                        }

                        if (isset($artist['museums']) && is_array($artist['museums'])) {
                            foreach ($artist['museums'] as &$museum) {
                                if (isset($museum['name']) && is_array($museum['name'])) {
                                    $museum['name'] = $museum['name']['uk'] ?? null;
                                }
                                if (isset($museum['exhibition_name']) && is_array($museum['exhibition_name'])) {
                                    $museum['exhibition_name'] = $museum['exhibition_name']['uk'] ?? null;
                                }
                            }
                            unset($museum);
                        }

                        if (isset($artist['works']) && is_array($artist['works'])) {
                            foreach ($artist['works'] as &$work) {
                                if (isset($work['title']) && is_array($work['title'])) {
                                    $work['title'] = $work['title']['uk'] ?? null;
                                }
                                if (isset($work['description']) && is_array($work['description'])) {
                                    $work['description'] = $work['description']['uk'] ?? null;
                                }
                            }
                            unset($work);
                        }
                    }
                    unset($artist);
                }

                DB::table('artist_boards')
                    ->where('id', $row->id)
                    ->update([
                        'titles' => $titles !== null ? json_encode($titles) : null,
                        'data' => $data !== null ? json_encode($data) : null,
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artist_boards', function (Blueprint $table) {
            $table->json('descriptions_json')->nullable()->after('descriptions');
        });

        DB::statement("UPDATE artist_boards SET descriptions_json = JSON_OBJECT('uk', descriptions, 'en', descriptions) WHERE descriptions IS NOT NULL");

        Schema::table('artist_boards', function (Blueprint $table) {
            $table->dropColumn('descriptions');
        });

        Schema::table('artist_boards', function (Blueprint $table) {
            $table->renameColumn('descriptions_json', 'descriptions');
        });

        DB::table('artist_boards')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $titles = $row->titles ? json_decode($row->titles, true) : null;
                if (is_array($titles)) {
                    foreach (['title1', 'title2', 'description'] as $key) {
                        if (isset($titles[$key]) && ! is_array($titles[$key])) {
                            $titles[$key] = ['uk' => $titles[$key], 'en' => $titles[$key]];
                        }
                    }
                }

                $data = $row->data ? json_decode($row->data, true) : null;
                if (is_array($data)) {
                    foreach ($data as &$artist) {
                        if (isset($artist['name']) && ! is_array($artist['name'])) {
                            $artist['name'] = ['uk' => $artist['name'], 'en' => $artist['name']];
                        }

                        if (isset($artist['museums']) && is_array($artist['museums'])) {
                            foreach ($artist['museums'] as &$museum) {
                                if (isset($museum['name']) && ! is_array($museum['name'])) {
                                    $museum['name'] = ['uk' => $museum['name'], 'en' => $museum['name']];
                                }
                                if (isset($museum['exhibition_name']) && ! is_array($museum['exhibition_name'])) {
                                    $museum['exhibition_name'] = ['uk' => $museum['exhibition_name'], 'en' => $museum['exhibition_name']];
                                }
                            }
                            unset($museum);
                        }

                        if (isset($artist['works']) && is_array($artist['works'])) {
                            foreach ($artist['works'] as &$work) {
                                if (isset($work['title']) && ! is_array($work['title'])) {
                                    $work['title'] = ['uk' => $work['title'], 'en' => $work['title']];
                                }
                                if (isset($work['description']) && ! is_array($work['description'])) {
                                    $work['description'] = ['uk' => $work['description'], 'en' => $work['description']];
                                }
                            }
                            unset($work);
                        }
                    }
                    unset($artist);
                }

                DB::table('artist_boards')
                    ->where('id', $row->id)
                    ->update([
                        'titles' => $titles !== null ? json_encode($titles) : null,
                        'data' => $data !== null ? json_encode($data) : null,
                    ]);
            }
        });
    }
};
