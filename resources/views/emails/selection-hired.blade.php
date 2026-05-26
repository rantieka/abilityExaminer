<x-mail::message>
# Dear {{ $application->full_name }},

We are absolutely thrilled to extend our official **Job Offer** for the position of **{{ $application->jobVacancy->title }}** at {{ config('app.name') }}!

Following your outstanding performance in both the CV screening and the Technical Online Exam, our HRD and Technical Management have unanimously approved your selection for this role.

### Position Details:
- **Role**: {{ $application->jobVacancy->title }}
- **Department**: {{ $application->jobVacancy->department ?? 'Engineering' }}
- **Status**: Full-time / Contract (Based on Position Details)

@if($application->supervisor_notes)
### Feedback from the Selection Committee:
> "{{ $application->supervisor_notes }}"
@endif

## Next Steps:
Our HR representative will be in touch with you shortly via phone or email to discuss:
1. Compensation, benefits, and standard employment contract details.
2. Necessary documents for onboarding.
3. Your official starting date.

If you have any urgent questions, please feel free to reply directly to this email.

Once again, congratulations on this spectacular achievement! We are extremely excited to have you join our team and start a rewarding journey with us.

Best regards,  
**The Recruitment Team**  
{{ config('app.name') }}

<x-mail::subcopy>
This email was officially processed and released by the Human Resource Department on {{ now()->format('d F Y, H:i') }} (WIB).
</x-mail::subcopy>
</x-mail::message>
