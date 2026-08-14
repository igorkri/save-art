<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use Tests\TestCase;

class ProjectTagsTest extends TestCase
{
    public function test_tags_are_stored_as_a_json_array(): void
    {
        $project = new Project;
        $project->tags = ['живопис', 'сучасне мистецтво'];

        $this->assertSame(
            ['живопис', 'сучасне мистецтво'],
            json_decode($project->getAttributes()['tags'], true),
        );
        $this->assertSame(['живопис', 'сучасне мистецтво'], $project->tags);
    }

    public function test_comma_separated_tags_are_normalized_for_backward_compatibility(): void
    {
        $project = new Project;
        $project->tags = 'живопис, арт';

        $this->assertSame(['живопис', 'арт'], $project->tags);
    }
}
