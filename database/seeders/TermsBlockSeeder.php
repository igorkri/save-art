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

        $paragraphs = [
            'uk' => implode("\n", [
                'Культурна спадщина України в контексті нових історичних подій набула особливої актуальності та нових змістів.',
                'Сьогодні образотворче мистецтво у фарбах на холсті відображає не просто сюжети чи метафори, а небувалий у сучасній історії злам епох. Художники фіксують не тільки події, а ще й глибину емоційно-почуттєвого фону, який неможливо передати на словах та в стрічці новин. Це - новітнє мистецтво, сучасне, переосмислене, глибинне, на віки.',
                'Саме зараз настає його час - аби уберегти наступні покоління від руїн, транслюючи біль крізь художні образи.',
            ]),
            'en' => implode("\n", [
                'The cultural heritage of Ukraine in the context of new historical events has acquired special relevance and new meanings.',
                'Today, visual art in paints on canvas reflects not just plots or metaphors, but an unprecedented break of epochs in modern history. Artists fix not only events, but also the depth of emotional and psychological background, which cannot be conveyed in words and in news feeds. This is modern art, contemporary, rethought, profound, for ages.',
                'Now is its time - to protect future generations from ruins, transmitting pain through artistic images.',
            ]),
        ];

        $orderedItems = [
            'uk' => implode("\n", ['Текст.', 'Текст.', 'Текст.', 'Текст.']),
            'en' => implode("\n", ['Text.', 'Text.', 'Text.', 'Text.']),
        ];

        $unorderedItems = $orderedItems;

        $blockTemplates = [
            [
                'heading' => ['uk' => 'Загловок розділу Н5', 'en' => 'Section Heading H5'],
                'paragraphs' => $paragraphs,
                'list_type' => null,
                'list_items' => null,
            ],
            [
                'heading' => ['uk' => 'Загловок розділу Н6', 'en' => 'Section Heading H6'],
                'paragraphs' => $paragraphs,
                'list_type' => null,
                'list_items' => null,
            ],
            [
                'heading' => ['uk' => 'Список нумерований', 'en' => 'Numbered list'],
                'paragraphs' => null,
                'list_type' => 'ordered',
                'list_items' => $orderedItems,
            ],
            [
                'heading' => ['uk' => 'Список', 'en' => 'List'],
                'paragraphs' => null,
                'list_type' => 'unordered',
                'list_items' => $unorderedItems,
            ],
            [
                'heading' => ['uk' => 'Загловок розділу Н6', 'en' => 'Section Heading H6'],
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
