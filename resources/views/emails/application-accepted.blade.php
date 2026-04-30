<x-mail::message>
# Congratulations, {{ $application->full_name }}!

We are pleased to inform you that you have **PASSED** the CV screening stage for the position of **{{ $application->jobVacancy->title }}**.

## Next Steps

You are invited to take an **Online Test** as the next stage of our recruitment process.

### Important Information:
- **Position**: {{ $application->jobVacancy->title }}
- **Test Duration**: 60 minutes
- **Deadline**: 7 days from the receipt of this email

<x-mail::button :url="url('/test/access/' . $application->test_token)">
Start Test Now
</x-mail::button>

## Instructions:
1. Click the button above to start the test
2. Ensure you have a stable internet connection
3. Prepare sufficient time to complete the test
4. The test can only be taken once

If you have any questions, please do not hesitate to contact us.

Thank you for your interest in joining our team!

Best regards,<br>
**HR Team**
</x-mail::message>
