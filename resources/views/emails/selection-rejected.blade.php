<x-mail::message>
# Dear {{ $application->full_name }},

Thank you very much for your interest in the **{{ $application->jobVacancy->title }}** position at {{ config('app.name') }} and for taking the time to complete our online assessment stage.

We want to express our appreciation for the effort you put into the Technical Assessment Exam. Our selection committee has carefully reviewed your profile, exam breakdown, and performance metrics. 

Unfortunately, we regret to inform you that we will not be proceeding with your candidacy for this specific position at this time.

Although you were not selected for this particular role, our team was impressed by your technical efforts. We will securely retain your resume in our talent database and may contact you should a future position align with your skills and qualifications.

We encourage you to continue building your portfolio and apply for future vacancies that interest you.

We wish you the very best of luck with your job search and all your future professional endeavors.

Warmest regards,  
**The Recruitment Team**  
{{ config('app.name') }}

<x-mail::subcopy>
This email was officially processed and released by the Human Resource Department on {{ now()->format('d F Y, H:i') }} (WIB).
</x-mail::subcopy>
</x-mail::message>
