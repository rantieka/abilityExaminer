<?php

namespace App\Http\Controllers;

class CareerController extends Controller
{
  public function index() {
    $jobs = [
      [
        'id' => 1,
        'title' => 'Senior Full Stack Developer',
        'department' => 'Engineering',
        'location' => 'Jakarta, Indonesia',
        'job_type' => 'Full-time',
        'salary_min' => 20000000,
        'salary_max' => 30000000,
        'description' => 'Kami mencari Senior Full Stack Developer yang berpengalaman dengan PHP/Laravel dan Vue.js.',
        'slug' => 'senior-full-stack-developer',
      ],
      [
        'id' => 2,
        'title' => 'UI/UX Designer',
        'department' => 'Design',
        'location' => 'Bandung, Indonesia',
        'job_type' => 'Full-time',
        'salary_min' => 12000000,
        'salary_max' => 18000000,
        'description' => 'Bergabunglah dengan tim design kami untuk menciptakan pengalaman pengguna yang luar biasa.',
        'slug' => 'ui-ux-designer',
      ],
      [
        'id' => 3,
        'title' => 'Data Analyst',
        'department' => 'Analytics',
        'location' => 'Jakarta, Indonesia',
        'job_type' => 'Full-time',
        'salary_min' => 10000000,
        'salary_max' => 15000000,
        'description' => 'Kami mencari Data Analyst yang passionate untuk mengubah data menjadi actionable insights.',
        'slug' => 'data-analyst',
      ],
    ];

    $jobsByDepartment = collect($jobs)->groupBy('department');

    return view('career.index', compact('jobsByDepartment'));
  }

  public function show($slug) {
    $jobs = [
      [
        'id' => 1,
        'title' => 'Senior Full Stack Developer',
        'department' => 'Engineering',
        'location' => 'Jakarta, Indonesia',
        'job_type' => 'Full-time',
        'salary_min' => 20000000,
        'salary_max' => 30000000,
        'description' => 'Kami mencari Senior Full Stack Developer yang berpengalaman dengan PHP/Laravel dan Vue.js. Anda akan memimpin tim development dan mengembangkan fitur-fitur inovatif.',
        'responsibilities' => "• Memimpin dan mengembangkan tim development
- Mengembangkan fitur-fitur baru menggunakan Laravel dan Vue.js
- Code review dan mentoring junior developers
- Berkolaborasi dengan Product dan Design team
- Optimize performa aplikasi dan database",
        'requirements' => "• Minimal 5 tahun pengalaman Full Stack Development
- Expert di Laravel dan Vue.js
- Familiar dengan Docker dan Git
- Kemampuan komunikasi yang baik
- Problem solving yang excellent",
        'slug' => 'senior-full-stack-developer',
          ],
          [
        'id' => 2,
        'title' => 'UI/UX Designer',
        'department' => 'Design',
        'location' => 'Bandung, Indonesia',
        'job_type' => 'Full-time',
        'salary_min' => 12000000,
        'salary_max' => 18000000,
        'description' => 'Bergabunglah dengan tim design kami untuk menciptakan pengalaman pengguna yang luar biasa. Anda akan bekerja dengan product dan engineering teams untuk menghasilkan design yang user-centric.',
        'responsibilities' => "• Membuat wireframe dan mockup untuk aplikasi web dan mobile
- Melakukan user research dan usability testing
- Collaborate dengan Engineering team dalam implementasi
- Maintain design system dan component library
- Iterasi design berdasarkan user feedback",
        'requirements' => "• Minimal 3 tahun pengalaman UI/UX Design
- Portfolio yang kuat menunjukkan design thinking
- Expert di Figma atau design tools lainnya
- Pengetahuan tentang responsive design
- Attention to detail yang tinggi",
        'slug' => 'ui-ux-designer',
          ],
          [
        'id' => 3,
        'title' => 'Data Analyst',
        'department' => 'Analytics',
        'location' => 'Jakarta, Indonesia',
        'job_type' => 'Full-time',
        'salary_min' => 10000000,
        'salary_max' => 15000000,
        'description' => 'Kami mencari Data Analyst yang passionate untuk mengubah data menjadi actionable insights. Anda akan menganalisis data dari berbagai sumber dan memberikan rekomendasi strategis.',
        'responsibilities' => "• Mengumpulkan dan menganalisis data dari berbagai sumber
- Membuat dashboard dan report yang meaningful
- Identifikasi trend dan pattern dari data
- Memberikan rekomendasi strategis berdasarkan insights
- Collaborate dengan stakeholder lain",
        'requirements' => "• Minimal 2 tahun pengalaman sebagai Data Analyst
- Proficient di SQL dan Python
- Familiar dengan tools analytics
- Kemampuan membuat visualisasi data
- Detail-oriented dan analytical thinking",
        'slug' => 'data-analyst',
          ],
      ];

      $job = collect($jobs)->firstWhere('slug', $slug);

      if (!$job) {
          abort(404);
      }

      return view('career.show', compact('job'));
  }
}