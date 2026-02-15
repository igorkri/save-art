<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('art_category_id')->nullable()->after('tags')->constrained('art_categories')->nullOnDelete();
        });

        $roots = DB::table('art_categories')->whereNull('parent_id')->pluck('id', 'slug');
        $children = DB::table('art_categories')->whereNotNull('parent_id')->get()->groupBy('parent_id');

        foreach (DB::table('projects')->whereNotNull('art_category')->get() as $project) {
            $catSlug = $project->art_category;
            $subSlug = $project->art_subcategory;
            $targetId = null;
            $parentId = $roots[$catSlug] ?? null;
            if ($parentId && $subSlug) {
                $subs = $children->get($parentId);
                $sub = $subs ? $subs->firstWhere('slug', $subSlug) : null;
                $targetId = $sub?->id;
            }
            if (! $targetId) {
                $targetId = $parentId;
            }
            if ($targetId) {
                DB::table('projects')->where('id', $project->id)->update(['art_category_id' => $targetId]);
            }
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['art_category', 'art_subcategory']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('art_category')->nullable()->after('tags');
            $table->string('art_subcategory')->nullable()->after('art_category');
        });

        $rows = DB::table('art_categories')->get()->keyBy('id');
        foreach (DB::table('projects')->whereNotNull('art_category_id')->get() as $project) {
            $row = $rows->get($project->art_category_id);
            $catSlug = $row?->parent_id ? ($rows->get($row->parent_id)?->slug) : $row?->slug;
            $subSlug = $row?->parent_id ? $row->slug : null;
            DB::table('projects')->where('id', $project->id)->update([
                'art_category' => $catSlug,
                'art_subcategory' => $subSlug,
            ]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['art_category_id']);
        });
    }
};
