<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Пропускаємо якщо FAQ вже є
        if (Faq::exists()) {
            return;
        }

        $categories = FaqCategory::all();

        if ($categories->isEmpty()) {
            return;
        }

        $faqs = [
            // Загальні питання
            'general' => [
                [
                    'question' => ['uk' => 'Що таке Save Art?', 'en' => 'What is Save Art?'],
                    'answer' => [
                        'uk' => 'Save Art — це платформа для підтримки українських митців, де ви можете знаходити проєкти та підтримувати їх фінансово.',
                        'en' => 'Save Art is a platform to support Ukrainian artists, where you can find projects and support them financially.',
                    ],
                ],
                [
                    'question' => ['uk' => 'Як зареєструватися на платформі?', 'en' => 'How to register on the platform?'],
                    'answer' => [
                        'uk' => 'Натисніть кнопку "Реєстрація" у верхньому правому куті сайту та заповніть необхідні поля.',
                        'en' => 'Click the "Register" button in the top right corner of the site and fill in the required fields.',
                    ],
                ],
            ],
            // Донати та оплата
            'donations' => [
                [
                    'question' => ['uk' => 'Які способи оплати доступні?', 'en' => 'What payment methods are available?'],
                    'answer' => [
                        'uk' => 'Ми приймаємо банківські картки Visa та MasterCard, а також оплату через Apple Pay та Google Pay.',
                        'en' => 'We accept Visa and MasterCard bank cards, as well as Apple Pay and Google Pay payments.',
                    ],
                ],
                [
                    'question' => ['uk' => 'Чи можу я повернути свій донат?', 'en' => 'Can I get a refund for my donation?'],
                    'answer' => [
                        'uk' => 'Донати не підлягають поверненню, оскільки вони відразу передаються на реалізацію проєкту.',
                        'en' => 'Donations are non-refundable as they are immediately used for project implementation.',
                    ],
                ],
            ],
            // Для митців
            'artists' => [
                [
                    'question' => ['uk' => 'Як створити проєкт?', 'en' => 'How to create a project?'],
                    'answer' => [
                        'uk' => 'Зареєструйтесь як митець, заповніть профіль та натисніть "Створити проєкт" у вашому кабінеті.',
                        'en' => 'Register as an artist, complete your profile and click "Create Project" in your dashboard.',
                    ],
                ],
                [
                    'question' => ['uk' => 'Яка комісія платформи?', 'en' => 'What is the platform commission?'],
                    'answer' => [
                        'uk' => 'Комісія платформи становить 5% від зібраних коштів плюс комісія платіжної системи.',
                        'en' => 'The platform commission is 5% of the collected funds plus payment system fees.',
                    ],
                ],
            ],
            // Для меценатів
            'patrons' => [
                [
                    'question' => ['uk' => 'Як стати меценатом?', 'en' => 'How to become a patron?'],
                    'answer' => [
                        'uk' => 'Зареєструйтесь на платформі та оберіть проєкт, який хочете підтримати.',
                        'en' => 'Register on the platform and choose a project you want to support.',
                    ],
                ],
                [
                    'question' => ['uk' => 'Чи отримаю я винагороду за донат?', 'en' => 'Will I receive a reward for my donation?'],
                    'answer' => [
                        'uk' => 'Так, кожен проєкт має різні рівні винагород залежно від суми донату.',
                        'en' => 'Yes, each project has different reward levels depending on the donation amount.',
                    ],
                ],
            ],
            // Технічна підтримка
            'support' => [
                [
                    'question' => ['uk' => 'Як звʼязатися з підтримкою?', 'en' => 'How to contact support?'],
                    'answer' => [
                        'uk' => 'Напишіть нам на email support@saveart.ua або скористайтесь формою зворотного звʼязку.',
                        'en' => 'Email us at support@saveart.ua or use the feedback form.',
                    ],
                ],
                [
                    'question' => ['uk' => 'Як змінити пароль?', 'en' => 'How to change password?'],
                    'answer' => [
                        'uk' => 'Перейдіть в налаштування профілю та оберіть "Змінити пароль".',
                        'en' => 'Go to profile settings and select "Change Password".',
                    ],
                ],
            ],
        ];

        $order = 1;
        foreach ($categories as $category) {
            $categoryFaqs = $faqs[$category->slug] ?? [];

            foreach ($categoryFaqs as $faqData) {
                Faq::create([
                    'faq_category_id' => $category->id,
                    'question' => $faqData['question'],
                    'answer' => $faqData['answer'],
                    'order' => $order++,
                    'is_active' => true,
                ]);
            }
        }
    }
}
