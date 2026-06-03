<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\JobVacancy;

class MasterQuestionSeeder extends Seeder
{
  public static function getQuestions(): array
  {
    return [
      // Backend question
      [
        'section' => 'technical',
        'text' => 'Apabila a=7, b=12, jika diberikan instruksi ```a=b; b=a;``` maka akan mengakibatkan nilai...',
        'options' => ['A' => 'a=7, b=12', 'B' => 'a=12, b=7', 'C' => 'a=12, b=12', 'D' => 'a=7, b=7'],
        'correct' => 'C',
        'tags' => ['backend'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'technical',
        'text' => 'Diberikan algoritma ```P=12; P=P+5; Q=P;``` Berapakah nilai P dan Q masing-masing?',
        'options' => ['A' => '12 dan 17', 'B' => '17 dan 12', 'C' => '17 dan 17', 'D' => '12 dan 12'],
        'correct' => 'C',
        'tags' => ['backend'],
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
        'tags' => ['backend', 'MySQL'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'technical',
        'text' => 'Dalam IF Bersarang, jika $x=15: ```if($x>10){ if($x<20){ echo "A"; } } else { echo "B"; }``` Apa outputnya?',
        'options' => ['A' => 'A', 'B' => 'B', 'C' => 'AB', 'D' => 'Tidak ada output'],
        'correct' => 'A',
        'tags' => ['backend'],
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
        'tags' => ['backend'],
        'difficulty' => 'easy'
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
        'difficulty' => 'easy'
      ],
      [
        'section' => 'technical',
        'text' => 'Perintah PHP manakah yang paling tepat digunakan untuk menampilkan semua jenis error (termasuk notice dan warning) saat proses development?',
        'options' => [
          'A' => "error_reporting(E_ALL); ini_set('display_errors', 1);",
          'B' => "display_errors = On;",
          'C' => "show_errors(true);",
          'D' => "debug_mode(true);"
        ],
        'correct' => 'A',
        'tags' => ['PHP'],
        'difficulty' => 'easy'
      ],
      [
        'section' => 'technical',
        'text' => "Diberikan kode PHP berikut: \n```php\n\$arr = [1, 2, 3];\nforeach (\$arr as &\$val) {}\nforeach (\$arr as \$val) {}\necho implode(',', \$arr);\n``` \nApa output dari kode tersebut?",
        'options' => [
          'A' => '1,2,3',
          'B' => '1,2,2',
          'C' => '1,1,1',
          'D' => '3,3,3'
        ],
        'correct' => 'B',
        'tags' => ['PHP'],
        'difficulty' => 'hard'
      ],
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
        'tags' => ['backend'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Keyword apa yang digunakan di PHP untuk membuat sebuah Class mewarisi (inherit) dari Class lain?',
        'options' => ['A' => 'implements', 'B' => 'extends', 'C' => 'requires', 'D' => 'includes'],
        'correct' => 'B',
        'tags' => ['PHP'],
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
        'tags' => ['backend', 'MySQL'],
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
        'tags' => ['backend'],
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
        'tags' => ['backend', 'MySQL'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'technical',
        'text' => 'Diberikan sebuah tabel `orders` yang memiliki **Composite Index** pada kolom `(user_id, order_date, status)`. Manakah query di bawah ini yang **TIDAK** akan menggunakan indeks tersebut secara optimal (akan melakukan Full Table Scan)?',
        'options' => [
          'A' => 'SELECT * FROM orders WHERE user_id = 5 AND order_date = "2023-01-01"',
          'B' => 'SELECT * FROM orders WHERE user_id = 5',
          'C' => 'SELECT * FROM orders WHERE order_date = "2023-01-01" AND status = "shipped"',
          'D' => 'SELECT * FROM orders WHERE user_id = 5 AND status = "pending"'
        ],
        'correct' => 'C',
        'tags' => ['backend', 'MySQL'],
        'difficulty' => 'hard'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Manakah HTTP Method yang paling tepat digunakan dalam REST API untuk memperbarui data yang sudah ada secara keseluruhan?',
        'options' => ['A' => 'GET', 'B' => 'POST', 'C' => 'PUT', 'D' => 'DELETE'],
        'correct' => 'C',
        'tags' => ['backend', 'REST API'],
        'difficulty' => 'medium'
      ],

      // Frontend questions
      [
        'section' => 'knowledge',
        'text' => 'Tag manakah yang digunakan untuk mendeklarasikan bahwa sebuah file HTML adalah versi HTML5?',
        'options' => ['A' => '<html>', 'B' => '<!DOCTYPE html>', 'C' => '<head>', 'D' => '<meta html5>'],
        'correct' => 'B',
        'tags' => ['frontend'],
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
        'tags' => ['frontend'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Manakah properti CSS yang digunakan untuk menyembunyikan elemen secara visual, namun elemen tersebut tetap mempertahankan ruang (space) yang ditempatinya pada tata letak halaman?',
        'options' => [
          'A' => 'display: none;',
          'B' => 'visibility: hidden;',
          'C' => 'display: hidden;',
          'D' => 'visibility: none;'
        ],
        'correct' => 'B',
        'tags' => ['frontend'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Manakah kumpulan framework CSS yang umum digunakan?',
        'options' => [
          'A' => 'Tailwind, Bootstrap, Bulma',
          'B' => 'Yii, Symfony, CodeIgniter',
          'C' => 'MySQL, MongoDB, Oracle',
          'D' => 'React, Vue, Angular'
        ],
        'correct' => 'A',
        'tags' => ['frontend'],
        'difficulty' => 'easy'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Properti Flexbox untuk mengatur perataan elemen secara horizontal (main-axis) adalah?',
        'options' => ['A' => 'align-items', 'B' => 'justify-content', 'C' => 'flex-direction', 'D' => 'display: flex'],
        'correct' => 'B',
        'tags' => ['frontend'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'technical',
        'text' => 'Diberikan variabel JavaScript `var purchase_total = 235000;`. Jika terdapat ketentuan diskon sebesar 20% untuk minimal pembelian Rp 200.000, berapakah total akhir yang harus dibayarkan?',
        'options' => ['A' => '235.000', 'B' => '188.000', 'C' => '47.000', 'D' => '210.000'],
        'correct' => 'B',
        'tags' => ['JavaScript'],
        'difficulty' => 'easy'
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
        'tags' => ['frontend', 'JavaScript'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'technical',
        'text' => 'Bagaimana cara memilih elemen dengan class "features__item" yang memiliki data-id="7" dan menambahkan class "highlighted" menggunakan Vanilla JavaScript?',
        'options' => [
          'A' => "document.querySelector('.features__item[data-id=\"7\"]').classList.add('highlighted');",
          'B' => "document.getElementById('features__item').setAttribute('id', '7').classList.add('highlighted');",
          'C' => "document.getElementsByClassName('features__item').dataset.id = 7; document.classList.add('highlighted');",
          'D' => "document.getElementByClass('.features__item').setData('id', 7).add('highlighted');"
        ],
        'correct' => 'A',
        'tags' => ['frontend', 'JavaScript'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'technical',
        'text' => "Apa output dari kode JavaScript berikut?\n```javascript\nconsole.log('A');\nsetTimeout(() => console.log('B'), 0);\nPromise.resolve().then(() => console.log('C'));\nconsole.log('D');\n```",
        'options' => [
          'A' => 'A, B, C, D',
          'B' => 'A, D, B, C',
          'C' => 'A, D, C, B',
          'D' => 'A, C, D, B'
        ],
        'correct' => 'C',
        'tags' => ['frontend', 'JavaScript'],
        'difficulty' => 'hard'
      ],
      [
        'section' => 'technical',
        'text' => "Diberikan kode berikut:\n```javascript\nfor (var i = 0; i < 3; i++) {\n  setTimeout(() => console.log(i), 100);\n}\n```\nApa yang akan muncul di console setelah 100ms?",
        'options' => [
          'A' => '0, 1, 2',
          'B' => '3, 3, 3',
          'C' => '2, 1, 0',
          'D' => 'Error: i is not defined'
        ],
        'correct' => 'B',
        'tags' => ['frontend', 'JavaScript'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Dalam desain web responsif, manakah nilai breakpoint (media query) yang secara umum digunakan sebagai batas awal untuk ukuran layar Desktop (Large Devices)?',
        'options' => [
          'A' => '320px',
          'B' => '576px',
          'C' => '768px',
          'D' => '992px'
        ],
        'correct' => 'D',
        'tags' => ['frontend', 'Responsive'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'knowledge',
        'text' => 'Manakah di bawah ini yang merupakan kumpulan framework atau library JavaScript yang populer untuk membangun User Interface (UI)?',
        'options' => [
          'A' => 'React, Vue, Angular',
          'B' => 'Yii, Django, Ruby on Rails',
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
        'tags' => ['frontend'],
        'difficulty' => 'medium'
      ],
      [
        'section' => 'technical',
        'text' => 'Mengapa sebuah elemen dengan properti `z-index: 9999;` terkadang tetap muncul di belakang elemen lain yang hanya memiliki `z-index: 10;`?',
        'options' => [
          'A' => 'Karena elemen dengan z-index 10 memiliki position: fixed.',
          'B' => 'Karena elemen dengan z-index 9999 berada di dalam elemen induk (parent) yang membentuk Stacking Context baru dengan nilai z-index yang lebih rendah.',
          'C' => 'Karena browser membatasi nilai maksimal z-index hanya sampai 1000.',
          'D' => 'Karena properti z-index hanya berfungsi jika elemen tersebut memiliki display: flex.'
        ],
        'correct' => 'B',
        'tags' => ['frontend', 'CSS'],
        'difficulty' => 'hard'
      ]
    ];
  }

  public function run(): void
  {
    $masterQuestions = self::getQuestions();

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
              'skill_category' => 'required', // All master questions are fundamental/required
              'is_active' => true
            ]
          );
        }
      }
    }

    $this->command->info('Skill-Aware Question Bank successfully synchronized!');
  }
}
