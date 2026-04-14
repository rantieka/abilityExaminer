<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\JobVacancy;

class MasterQuestionSeeder extends Seeder
{
  public function run(): void
  {
    // Data Backend
    $backendQuestions = [
      [
        'section' => 'knowledge',
        'text' => 'Apabila a=7, b=12, jika diberikan instruksi a=b; b=a; maka akan mengakibatkan nilai...',
        'options' => ['A' => 'a=7, b=12', 'B' => 'a=12, b=7', 'C' => 'a=12, b=12', 'D' => 'a=7, b=7'],
        'correct' => 'C'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Diberikan algoritma P=12; P=P+5; Q=P. Berapakah nilai P dan Q masing-masing?',
        'options' => ['A' => '12 dan 17', 'B' => '17 dan 12', 'C' => '17 dan 17', 'D' => '12 dan 12'],
        'correct' => 'C'
      ],
      [
        'section' => 'technical',
        'text' => 'Manakah penulisan Array 2 Dimensi yang benar di PHP?',
        'options' => [
          'A' => '$data = [1, 2, 3];',
          'B' => '$data = [ ["A", "B"], ["C", "D"] ];',
          'C' => '$data = "A", "B";',
          'D' => '$data = array("A", "B");'
        ],
        'correct' => 'B'
      ],
      [
        'section' => 'technical',
        'text' => 'Manakah contoh Looping Dinamis yang benar menggunakan foreach di PHP?',
        'options' => [
          'A' => 'for($i=0; $i<10; $i++)',
          'B' => 'foreach($items as $item) { ... }',
          'C' => 'while($a < 10)',
          'D' => 'do { ... } while($a)'
        ],
        'correct' => 'B'
      ],
      [
        'section' => 'technical',
        'text' => 'Dalam IF Bersarang, jika $x=15: if($x>10){ if($x<20){ echo "A"; } } else { echo "B"; }. Apa outputnya?',
        'options' => ['A' => 'A', 'B' => 'B', 'C' => 'AB', 'D' => 'Tidak ada output'],
        'correct' => 'A'
      ],
      [
        'section' => 'technical',
        'text' => 'Query mana yang benar untuk mengambil data dari 3 tabel (JOIN)?',
        'options' => [
          'A' => 'SELECT * FROM t1, t2, t3',
          'B' => 'SELECT * FROM t1 JOIN t2 ON t1.id=t2.id JOIN t3 ON t2.id=t3.id',
          'C' => 'GET DATA FROM t1 AND t2 AND t3',
          'D' => 'MERGE t1, t2, t3'
        ],
        'correct' => 'B'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Apa kepanjangan dan fungsi utama dari konsep MVC dalam pengembangan web?',
        'options' => [
          'A' => 'Model View Control - Untuk desain grafis',
          'B' => 'Model View Controller - Memisahkan logika bisnis, data, dan tampilan',
          'C' => 'Multi View Code - Untuk keamanan data',
          'D' => 'Main Video Core - Untuk multimedia'
        ],
        'correct' => 'B'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Apa itu Class turunan (Child Class) dalam OOP?',
        'options' => [
          'A' => 'Class yang tidak bisa dipakai',
          'B' => 'Class yang mewarisi sifat dari Class induk (Parent)',
          'C' => 'Class yang berisi database',
          'D' => 'Class yang hanya berisi satu variabel'
        ],
        'correct' => 'B'
      ],
      [
        'section' => 'technical',
        'text' => 'Jika Laravel menampilkan error 500, file log mana yang harus dicek pertama kali?',
        'options' => ['A' => 'storage/logs/laravel.log', 'B' => '.env', 'C' => 'public/index.php', 'D' => 'config/app.php'],
        'correct' => 'A'
      ],
    ];

    // Data Frontend
    $frontendQuestions = [
      [
        'section' => 'knowledge',
        'text' => 'Tag manakah yang digunakan untuk mendeklarasikan bahwa sebuah file HTML adalah versi HTML5?',
        'options' => ['A' => '<html>', 'B' => '<!DOCTYPE html>', 'C' => '<head>', 'D' => '<meta html5>'],
        'correct' => 'B'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Manakah yang merupakan elemen semantik baru di HTML5?',
        'options' => [
          'A' => '<header>, <nav>, <section>, <article>, <aside>, <footer>',
          'B' => '<div>, <span>, <table>, <form>',
          'C' => '<b>, <i>, <u>, <s>',
          'D' => '<font>, <center>, <big>, <strike>'
        ],
        'correct' => 'A'
      ],
      [
        'section' => 'technical',
        'text' => 'Manakah properti CSS yang digunakan untuk menyembunyikan elemen namun tetap memakan ruang (space) di layout?',
        'options' => ['A' => 'display: none;', 'B' => 'visibility: hidden;', 'C' => 'opacity: 1;', 'D' => 'margin-top: -999px;'],
        'correct' => 'B'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Manakah kumpulan framework CSS yang umum digunakan?',
        'options' => [
          'A' => 'Tailwind, Bootstrap, Bulma',
          'B' => 'Laravel, Symfony, CodeIgniter',
          'C' => 'MySQL, MongoDB, Oracle',
          'D' => 'React, Vue, Angular'
        ],
        'correct' => 'A'
      ],
      [
        'section' => 'technical',
        'text' => 'Berapakah nilai responsive breakpoint yang umum digunakan untuk memisahkan Mobile dan Desktop?',
        'options' => ['A' => '320px', 'B' => '768px', 'C' => '1024px', 'D' => '1920px'],
        'correct' => 'B'
      ],
      [
        'section' => 'technical',
        'text' => 'Properti Flexbox untuk mengatur perataan elemen secara horizontal (main-axis) adalah?',
        'options' => ['A' => 'align-items', 'B' => 'justify-content', 'C' => 'flex-direction', 'D' => 'display-flex'],
        'correct' => 'B'
      ],
      [
        'section' => 'technical',
        'text' => 'Gunakan jQuery selector untuk memilih elemen dengan class "features__item" yang memiliki data-id="7":',
        'options' => [
          'A' => '$(".features__item[data-id=\'7\']")',
          'B' => '$("#features-7")',
          'C' => '$(".features__item-7")',
          'D' => '$("item").data(7)'
        ],
        'correct' => 'A'
      ],
      [
        'section' => 'technical',
        'text' => 'Diberikan kode JS: var purchase_total = 235000; Berapa total yang harus dibayarkan jika mendapatkan diskon 20% (belanja min 200rb)?',
        'options' => ['A' => '235.000', 'B' => '188.000', 'C' => '47.000', 'D' => '210.000'],
        'correct' => 'B'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Manakah yang merupakan kumpulan Javascript Framework?',
        'options' => [
          'A' => 'React JS, Vue JS, Angular',
          'B' => 'Phaser, Three.js, Babylon',
          'C' => 'Express, Nest, Hapi',
          'D' => 'Electron, Ionic, React Native'
        ],
        'correct' => 'A'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Apa perbedaan utama antara var, let, dan const dalam scope variabel?',
        'options' => [
          'A' => 'var adalah function scope, let & const adalah block scope',
          'B' => 'Semuanya sama saja',
          'C' => 'let hanya untuk string',
          'D' => 'const bisa diubah-ubah nilainya'
        ],
        'correct' => 'A'
      ],
    ];


    // Proses Input ke Database
    $vacancies = JobVacancy::all();

    foreach ($vacancies as $vacancy) {
      $title = strtolower($vacancy->title);
      $targetQuestions = [];

      if (str_contains($title, 'backend')) {
        $targetQuestions = $backendQuestions;
      } elseif (str_contains($title, 'frontend')) {
        $targetQuestions = $frontendQuestions;
      }

      foreach ($targetQuestions as $q) {
        Question::updateOrCreate(
          ['job_vacancy_id' => $vacancy->id, 'question_text' => $q['text']],
          [
            'options' => $q['options'],
            'correct_answer' => $q['correct'],
            'section' => $q['section'],
            'is_active' => true
          ]
        );
      }
    }

    $this->command->info('Master Question Bank (Backend & Frontend) successfully synchronized!');
  }
}
