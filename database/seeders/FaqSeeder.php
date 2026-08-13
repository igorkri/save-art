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
                    'question' => 'Що таке Save Art?',
                    'answer' => 'Save Art — це платформа для підтримки українських митців, де ви можете знаходити проєкти та підтримувати їх фінансово.',
                ],
                [
                    'question' => 'Як зареєструватися на платформі?',
                    'answer' => 'Натисніть кнопку "Реєстрація" у верхньому правому куті сайту та заповніть необхідні поля.',
                ],
            ],
            // Донати та оплата
            'donations' => [
                [
                    'question' => 'Які способи оплати доступні?',
                    'answer' => 'Ми приймаємо банківські картки Visa та MasterCard, а також оплату через Apple Pay та Google Pay.',
                ],
                [
                    'question' => 'Чи можу я повернути свій донат?',
                    'answer' => 'Донати не підлягають поверненню, оскільки вони відразу передаються на реалізацію проєкту.',
                ],
            ],
            // Для митців
            'artists' => [
                [
                    'question' => 'Як створити проєкт?',
                    'answer' => 'Зареєструйтесь як митець, заповніть профіль та натисніть "Створити проєкт" у вашому кабінеті.',
                ],
                [
                    'question' => 'Яка комісія платформи?',
                    'answer' => 'Комісія платформи становить 5% від зібраних коштів плюс комісія платіжної системи.',
                ],
            ],
            // Для меценатів
            'patrons' => [
                [
                    'question' => 'Як стати меценатом?',
                    'answer' => 'Зареєструйтесь на платформі та оберіть проєкт, який хочете підтримати.',
                ],
                [
                    'question' => 'Чи отримаю я винагороду за донат?',
                    'answer' => 'Так, кожен проєкт має різні рівні винагород залежно від суми донату.',
                ],
            ],
            // Технічна підтримка
            'support' => [
                [
                    'question' => 'Як звʼязатися з підтримкою?',
                    'answer' => 'Напишіть нам на email support@saveart.ua або скористайтесь формою зворотного звʼязку.',
                ],
                [
                    'question' => 'Як змінити пароль?',
                    'answer' => 'Перейдіть в налаштування профілю та оберіть "Змінити пароль".',
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
