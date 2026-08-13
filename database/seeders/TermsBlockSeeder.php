<?php

namespace Database\Seeders;

use App\Models\TermsBlock;
use App\Models\TermsSection;
use Illuminate\Database\Seeder;

class TermsBlockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Пропускаємо якщо блоки вже є
        if (TermsBlock::exists()) {
            return;
        }

        $sections = TermsSection::orderBy('order')->get();

        if ($sections->isEmpty()) {
            return;
        }

        $paragraphs = implode("\n", [
            'Культурна спадщина України в контексті нових історичних подій набула особливої актуальності та нових змістів.',
            'Сьогодні образотворче мистецтво у фарбах на холсті відображає не просто сюжети чи метафори, а небувалий у сучасній історії злам епох. Художники фіксують не тільки події, а ще й глибину емоційно-почуттєвого фону, який неможливо передати на словах та в стрічці новин. Це - новітнє мистецтво, сучасне, переосмислене, глибинне, на віки.',
            'Саме зараз настає його час - аби уберегти наступні покоління від руїн, транслюючи біль крізь художні образи.',
        ]);

        $orderedItems = implode("\n", ['Текст.', 'Текст.', 'Текст.', 'Текст.']);

        $unorderedItems = $orderedItems;

        $blockTemplates = [
            [
                'heading' => 'Загловок розділу Н5',
                'paragraphs' => $paragraphs,
                'list_type' => null,
                'list_items' => null,
            ],
            [
                'heading' => 'Загловок розділу Н6',
                'paragraphs' => $paragraphs,
                'list_type' => null,
                'list_items' => null,
            ],
            [
                'heading' => 'Список нумерований',
                'paragraphs' => null,
                'list_type' => 'ordered',
                'list_items' => $orderedItems,
            ],
            [
                'heading' => 'Список',
                'paragraphs' => null,
                'list_type' => 'unordered',
                'list_items' => $unorderedItems,
            ],
            [
                'heading' => 'Загловок розділу Н6',
                'paragraphs' => $paragraphs,
                'list_type' => null,
                'list_items' => null,
            ],
        ];

        foreach ($sections as $section) {
            $order = 1;

            foreach ($blockTemplates as $template) {
                TermsBlock::create([
                    'terms_section_id' => $section->id,
                    'heading' => $template['heading'],
                    'paragraphs' => $template['paragraphs'],
                    'list_type' => $template['list_type'],
                    'list_items' => $template['list_items'],
                    'order' => $order++,
                ]);
            }
        }
    }
}
