<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\JobVacancy;

class MasterQuestionSeeder extends Seeder
{
  public function run(): void
  {
    $masterQuestions = [
      // Backend question
      [
        'section' => 'technical',
        'text' => 'Apabila a=7, b=12, jika diberikan instruksi a=b; b=a; maka akan mengakibatkan nilai...',
        'options' => ['A' => 'a=7, b=12', 'B' => 'a=12, b=7', 'C' => 'a=12, b=12', 'D' => 'a=7, b=7'],
        'correct' => 'C',
        'tags' => ['backend',                                                                                                                                                                                'general'],
        'difficulty' => 'easy'
      ],
      [
        'section' => 'technical',
        'text' => 'Diberikan algoritma P=12; P=P+5; Q=P. Berapakah nilai P dan Q masing-masing?',
        'options' => ['A' => '12 dan 17', 'B' => '17 dan 12', 'C' => '17 dan 17', 'D' => '12 dan 12'],
        'correct' => 'C',
        'tags' => ['backend', 'general'],
        'difficulty' => 'easy'
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
        'correct' => 'B',
        'tags' => ['backend', 'general', 'SQL'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'technical',
        'text' => 'Dalam IF Bersarang, jika $x=15: if($x>10){ if($x<20){ echo "A"; } } else { echo "B"; }. Apa outputnya?',
        'options' => ['A' => 'A', 'B' => 'B', 'C' => 'AB', 'D' => 'Tidak ada output'],
        'correct' => 'A',
        'tags' => ['backend', 'general'],
        'difficulty' => 'easy'
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
        'correct' => 'B',
        'tags' => ['backend', 'general'],
        'difficulty' => 'medium'
      ],

      // PHP specific
      [
        'section' => 'knowledge',
        'text' => 'Manakah penulisan Array 2 Dimensi yang benar di PHP?',
        'options' => [
          'A' => '$data = [1, 2, 3];',
          'B' => '$data = [ ["A", "B"], ["C", "D"] ];',
          'C' => '$data = "A", "B";',
          'D' => '$data = array("A", "B");'
        ],
        'correct' => 'B',
        'tags' => ['PHP'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Manakah contoh Looping Dinamis yang benar menggunakan foreach di PHP?',
        'options' => [
          'A' => 'for($i=0; $i<10; $i++)',
          'B' => 'foreach($items as $item) { ... }',
          'C' => 'while($a < 10)',
          'D' => 'do { ... } while($a)'
        ],
        'correct' => 'B',
        'tags' => ['PHP'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'technical',
        'text' => 'Jika Laravel menampilkan error 500, file log mana yang harus dicek pertama kali?',
        'options' => ['A' => 'storage/logs/laravel.log', 'B' => '.env', 'C' => 'public/index.php', 'D' => 'config/app.php'],
        'correct' => 'A',
        'tags' => ['PHP', 'Laravel'],
        'difficulty' => 'hard'
      ],

      // New questions from Google Form conversion
      [
        'section' => 'knowledge',
        'text' => 'Apa yang dimaksud dengan konsep Inheritance (Pewarisan) dalam OOP?',
        'options' => [
          'A' => 'Kemampuan sebuah class untuk menyalin data dari database',
          'B' => 'Kemampuan sebuah class untuk menurunkan property dan method ke class lain (child)',
          'C' => 'Proses menghapus variabel yang tidak terpakai',
          'D' => 'Teknik menyembunyikan logika program'
        ],
        'correct' => 'B',
        'tags' => ['backend', 'OOP'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Keyword apa yang digunakan di PHP untuk membuat sebuah Class mewarisi (inherit) dari Class lain?',
        'options' => ['A' => 'implements', 'B' => 'extends', 'C' => 'requires', 'D' => 'includes'],
        'correct' => 'B',
        'tags' => ['backend', 'PHP', 'OOP'],
        'difficulty' => 'easy'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Dalam SQL, apa kegunaan dari perintah "DELETE" tanpa menggunakan klausa "WHERE"?',
        'options' => [
          'A' => 'Menghapus struktur tabel',
          'B' => 'Menghapus satu baris data paling atas',
          'C' => 'Menghapus seluruh baris data dalam tabel tersebut',
          'D' => 'Hanya menghapus primary key saja'
        ],
        'correct' => 'C',
        'tags' => ['backend', 'SQL'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Apa tujuan utama dari proses "Debugging" dalam pengembangan perangkat lunak?',
        'options' => [
          'A' => 'Mempercepat koneksi internet',
          'B' => 'Menemukan dan memperbaiki kesalahan (bug) pada kode program',
          'C' => 'Mengunggah kode ke server produksi',
          'D' => 'Membuat tampilan website menjadi lebih bagus'
        ],
        'correct' => 'B',
        'tags' => ['backend', 'general'],
        'difficulty' => 'easy'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Manakah query SQL yang benar untuk mengubah (update) data nama pada tabel "users" yang memiliki id=1?',
        'options' => [
          'A' => 'CHANGE users SET name="Budi" WHERE id=1',
          'B' => 'UPDATE users SET name="Budi" WHERE id=1',
          'C' => 'MODIFY users name="Budi" WHERE id=1',
          'D' => 'UPDATE users VALUES("Budi") WHERE id=1'
        ],
        'correct' => 'B',
        'tags' => ['backend', 'SQL'],
        'difficulty' => 'medium'
      ],

      // Frontend questions
      [
        'section' => 'knowledge',
        'text' => 'Tag manakah yang digunakan untuk mendeklarasikan bahwa sebuah file HTML adalah versi HTML5?',
        'options' => ['A' => '<html>', 'B' => '<!DOCTYPE html>', 'C' => '<head>', 'D' => '<meta html5>'],
        'correct' => 'B',
        'tags' => ['frontend', 'general', 'HTML'],
        'difficulty' => 'easy'
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
        'correct' => 'A',
        'tags' => ['frontend', 'general', 'HTML'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Manakah properti CSS yang digunakan untuk menyembunyikan elemen namun tetap memakan ruang (space) di layout?',
        'options' => ['A' => 'display: none;', 'B' => 'visibility: hidden;', 'C' => 'opacity: 1;', 'D' => 'margin-top: -999px;'],
        'correct' => 'B',
        'tags' => ['frontend', 'general', 'CSS'],
        'difficulty' => 'medium'
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
        'correct' => 'A',
        'tags' => ['frontend', 'general'],
        'difficulty' => 'easy'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Properti Flexbox untuk mengatur perataan elemen secara horizontal (main-axis) adalah?',
        'options' => ['A' => 'align-items', 'B' => 'justify-content', 'C' => 'flex-direction', 'D' => 'display-flex'],
        'correct' => 'B',
        'tags' => ['frontend', 'general', 'CSS'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'technical',
        'text' => 'Diberikan kode JS: var purchase_total = 235000; Berapa total yang harus dibayarkan jika mendapatkan diskon 20% (belanja min 200rb)?',
        'options' => ['A' => '235.000', 'B' => '188.000', 'C' => '47.000', 'D' => '210.000'],
        'correct' => 'B',
        'tags' => ['frontend', 'general', 'JavaScript'],
        'difficulty' => 'hard'
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
        'correct' => 'A',
        'tags' => ['frontend', 'general', 'JavaScript'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'technical',
        'text' => 'Bagaimana cara memilih elemen dengan class "features__item" yang memiliki data-id="7" dan menambahkan class "highlighted" menggunakan jQuery?',
        'options' => [
          'A' => "$('.features__item[data-id=\"7\"]').addClass('highlighted');",
          'B' => "$('#features__item').attr('id', '7').addClass('highlighted');",
          'C' => "$('.features__item').data('id', 7).class('highlighted');",
          'D' => "getElementByClass('.features__item').setData('id', 7).add('highlighted');"
        ],
        'correct' => 'A',
        'tags' => ['frontend', 'jQuery', 'JavaScript'],
        'difficulty' => 'hard'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Berapakah nilai breakpoint (lebar layar) yang paling umum digunakan dalam framework CSS (seperti Bootstrap) untuk memisahkan tampilan Tablet dan Desktop (Large devices)?',
        'options' => [
          'A' => '320px',
          'B' => '576px',
          'C' => '768px',
          'D' => '992px'
        ],
        'correct' => 'D',
        'tags' => ['frontend', 'CSS', 'Responsive'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Manakah di bawah ini yang merupakan kumpulan framework atau library JavaScript yang populer untuk membangun User Interface (UI)?',
        'options' => [
          'A' => 'React, Vue, Angular',
          'B' => 'Laravel, Django, Ruby on Rails',
          'C' => 'MySQL, PostgreSQL, MongoDB',
          'D' => 'Apache, Nginx, Docker'
        ],
        'correct' => 'A',
        'tags' => ['frontend', 'JavaScript'],
        'difficulty' => 'easy'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Properti Flexbox yang digunakan untuk mengatur perataan elemen secara vertikal (cross-axis) di dalam container adalah?',
        'options' => [
          'A' => 'justify-content',
          'B' => 'align-items',
          'C' => 'flex-direction',
          'D' => 'float'
        ],
        'correct' => 'B',
        'tags' => ['frontend', 'CSS'],
        'difficulty' => 'medium'
      ]
    ];

    $vacancies = JobVacancy::all();

    foreach ($vacancies as $vacancy) {
      $title = strtolower($vacancy->title);
      $requiredSkills = array_map('strtolower', $vacancy->required_skills ?? []);
      
      // Determine basic role
      $role = '';
      if (str_contains($title, 'backend')) $role = 'backend';
      elseif (str_contains($title, 'frontend')) $role = 'frontend';

      foreach ($masterQuestions as $q) {
        $qTags = array_map('strtolower', $q['tags']);
        
        // Logic Filter:
        // 1. Is this a question for the matching role (backend/frontend)?
        $isRoleMatch = in_array($role, $qTags);
        
        // 2. Does any question tag match the job's required_skills?
        $isSkillMatch = !empty(array_intersect($qTags, $requiredSkills));

        if ($isRoleMatch || $isSkillMatch) {
          Question::updateOrCreate(
            ['job_vacancy_id' => $vacancy->id, 'question_text' => $q['text']],
            [
              'options' => $q['options'],
              'correct_answer' => $q['correct'],
              'section' => $q['section'],
              'difficulty' => $q['difficulty'] ?? 'medium',
              'is_active' => true
            ]
          );
        }
      }
    }

    $this->command->info('Skill-Aware Question Bank successfully synchronized!');
  }
}
