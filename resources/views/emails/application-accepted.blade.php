<x-mail::message>

# Selamat, {{ $application->full_name }}!

Kami dengan senang hati menginformasikan bahwa Anda telah **LOLOS** tahap seleksi CV untuk posisi **{{ $application->jobVacancy->title }}**.

## Tahap Selanjutnya
Anda diundang untuk mengikuti **Tes Online** sebagai bagian dari proses rekrutmen selanjutnya.

## Informasi Tes:
* **Posisi**: {{ $application->jobVacancy->title }}
* **Durasi Tes**: 60 menit
* **Batas Waktu Pengerjaan**: 7 hari sejak email ini diterima

<x-mail::button :url="url('/test/access/' . $application->test_token)">
Mulai Tes
</x-mail::button>

## Petunjuk:
1. Klik tombol di atas untuk memulai tes
2. Pastikan koneksi internet Anda stabil
3. Siapkan waktu yang cukup untuk menyelesaikan tes
4. Tes hanya dapat dikerjakan satu kali

Terima kasih atas minat Anda untuk bergabung bersama {{ config('app.name') }}.
Jika Anda memiliki pertanyaan, silakan hubungi kami melalui email ini.

Salam hangat,<br>
**Tim HR**
</x-mail::message>
