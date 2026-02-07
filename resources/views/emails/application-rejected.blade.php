<x-mail::message>
# Dear {{ $application->full_name }},

Thank you for your interest in applying for the **{{ $application->jobVacancy->title }}** position at {{ config('app.name') }}.

After carefully reviewing your application, we regret to inform you that we will not be proceeding with your candidacy at this time.

@if($application->rejection_reason)
## Notes from HR Team

{{ $application->rejection_reason }}
@endif

## Message for You

This decision does not reflect on your qualifications or experience. We encourage you to continue developing your skills and apply for other suitable positions in the future.

We will keep your application on file for future opportunities.

---

We wish you all the best in your career endeavors.

Best regards,<br>
**HR Team {{ config('app.name') }}**

<x-mail::subcopy>
If you have any questions regarding this application, please contact us via this email.
</x-mail::subcopy>
</x-mail::message>
