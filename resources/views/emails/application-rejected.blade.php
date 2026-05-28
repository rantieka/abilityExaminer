<x-mail::message>

# Halo {{ $application->full_name }},

Terima kasih atas minat Anda untuk melamar posisi **{{ $application->jobVacancy->title }}** di {{ config('app.name') }}.

Setelah meninjau lamaran Anda dengan saksama, saat ini kami belum dapat melanjutkan lamaran Anda ke tahap berikutnya.

Kami sangat menghargai minat dan waktu yang telah Anda luangkan untuk melamar di {{ config('app.name') }}. Kami akan menyimpan data lamaran Anda sebagai referensi untuk peluang karir yang sesuai di kemudian hari.

Semoga Anda sukses dalam perjalanan karir berikutnya.

Salam hangat,<br>
**Tim HR**

<x-mail::subcopy>
Jika Anda memiliki pertanyaan terkait lamaran ini, silakan hubungi kami melalui email ini.
</x-mail::subcopy>
</x-mail::message>
