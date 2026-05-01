<?php

namespace Database\Seeders;

use App\Models\JobVacancy;
use Illuminate\Database\Seeder;

class UpdateJobQualificationsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $newQualifications = "<ul>
<li>Fresh Graduate (SMK/D3/D4/S1).</li>
<li>Strong learning ability and eagerness to grow.</li>
<li>Good teamwork and collaboration skills.</li>
</ul>";

    // Update Frontend vacancies
    JobVacancy::where('title', 'like', '%Frontend%')->update([
      'qualifications' => $newQualifications
    ]);

    // Update Backend vacancies
    JobVacancy::where('title', 'like', '%Backend%')->update([
      'qualifications' => $newQualifications
    ]);
    
    echo "Successfully updated Frontend and Backend job qualifications.\n";
  }
}
