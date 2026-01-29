<x-mail::message>
# Terima Kasih, {{ $application->full_name }}

Terima kasih atas minat Anda untuk melamar posisi **{{ $application->jobVacancy->title }}** di {{ config('app.name') }}.

Setelah melakukan review yang cermat terhadap CV Anda, dengan berat hati kami informasikan bahwa saat ini kami tidak dapat melanjutkan lamaran Anda ke tahap berikutnya.

@if($application->rejection_reason)
## Catatan dari Tim HR

{{ $application->rejection_reason }}
@endif

## Pesan untuk Anda

Keputusan ini tidak mengurangi apresiasi kami terhadap kualifikasi dan pengalaman yang Anda miliki. Kami mendorong Anda untuk terus mengembangkan diri dan melamar kembali untuk posisi lain yang sesuai dengan keahlian Anda di masa mendatang.

Kami akan menyimpan data lamaran Anda untuk referensi peluang kerja di masa yang akan datang.

---

Kami berharap yang terbaik untuk karir Anda ke depannya.

Salam hangat,<br>
**Tim HR {{ config('app.name') }}**

<x-mail::subcopy>
Jika Anda memiliki pertanyaan terkait lamaran ini, silakan hubungi kami melalui email ini.
</x-mail::subcopy>
</x-mail::message>
