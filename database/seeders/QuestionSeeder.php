<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vacancies = \App\Models\JobVacancy::all();

        if ($vacancies->isEmpty()) {
            $this->command->info('No Job Vacancies found. Please create one first.');
            return;
        }

        $baseQuestions = [
            [
                'question_text' => 'Apa kepanjangan dari HTML?',
                'options' => [
                    'A' => 'Hyper Text Markup Language',
                    'B' => 'High Text Make Language',
                    'C' => 'Hyper Transfer Mark Language',
                    'D' => 'High Terminal Machine Learning'
                ],
                'correct_answer' => 'A'
            ],
            [
                'question_text' => 'Manakah yang merupakan framework PHP?',
                'options' => [
                    'A' => 'React',
                    'B' => 'Laravel',
                    'C' => 'Vue',
                    'D' => 'Angular'
                ],
                'correct_answer' => 'B'
            ],
            [
                'question_text' => 'Fungsi "echo" di PHP digunakan untuk?',
                'options' => [
                    'A' => 'Menghapus data',
                    'B' => 'Mengulang loop',
                    'C' => 'Menampilkan output',
                    'D' => 'Koneksi database'
                ],
                'correct_answer' => 'C'
            ],
        ];

        foreach ($vacancies as $vacancy) {
            foreach ($baseQuestions as $q) {
                // Hindari duplikasi jika dijalankan berulang
                \App\Models\Question::firstOrCreate([
                    'job_vacancy_id' => $vacancy->id,
                    'question_text' => $q['question_text'],
                ], [
                    'options' => $q['options'],
                    'correct_answer' => $q['correct_answer'],
                ]);
            }
        }
    }
}
