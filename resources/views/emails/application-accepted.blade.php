<x-mail::message>
# Selamat, {{ $application->full_name }}!

Kami dengan senang hati memberitahukan bahwa Anda **LOLOS** tahap screening CV untuk posisi **{{ $application->jobVacancy->title }}**.

## Tahap Selanjutnya

Anda diundang untuk mengikuti **Tes Online** sebagai tahap berikutnya dalam proses rekrutmen kami.

### Informasi Penting:
- **Posisi**: {{ $application->jobVacancy->title }}
- **Durasi Tes**: Sekitar 30-45 menit
- **Batas Waktu**: 7 hari sejak email ini diterima

<x-mail::button :url="url('/applications/' . $application->id . '/test')">
Mulai Tes Sekarang
</x-mail::button>

## Petunjuk:
1. Klik tombol di atas untuk memulai tes
2. Pastikan koneksi internet Anda stabil
3. Siapkan waktu yang cukup untuk menyelesaikan tes
4. Tes hanya dapat dikerjakan satu kali

Jika Anda memiliki pertanyaan, jangan ragu untuk menghubungi kami.

Terima kasih atas minat Anda untuk bergabung dengan tim kami!

Salam,<br>
**Tim HR {{ config('app.name') }}**
</x-mail::message>
