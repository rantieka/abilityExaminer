<x-mail::message>
# Dear {{ $application->full_name }},

Dengan senang hati kami menyampaikan bahwa Anda telah berhasil lolos seluruh tahapan seleksi dan resmi menerima Penawaran Kerja (Job Offer) untuk posisi {{ $application->jobVacancy->title }} di {{ config('app.name') }}.

Berdasarkan hasil seleksi CV dan Technical Online Exam yang telah Anda ikuti, tim HR dan manajemen terkait memutuskan untuk melanjutkan Anda sebagai bagian dari tim kami.

### Detail Posisi:
* **Posisi**: {{ $application->jobVacancy->title }}
* **Departemen**: {{ $application->jobVacancy->department ?? 'Engineering' }}
* **Status**: Penuh Waktu / Kontrak (sesuai ketentuan posisi)

@if($application->supervisor_notes)
### Feedback from the Selection Committee:
> "{{ $application->supervisor_notes }}"
@endif

## Tahap Selanjutnya:
Tim HR kami akan segera menghubungi Anda melalui telepon atau email untuk membahas:
1. Detail kompensasi, benefit, dan kontrak kerja.
2. Dokumen yang diperlukan untuk proses onboarding.
3. Tanggal mulai bekerja.

Jika Anda memiliki pertanyaan, silakan hubungi kami melalui email ini.

Selamat atas pencapaian ini, dan terima kasih atas minat Anda untuk bergabung bersama {{ config('app.name') }}. Kami berharap dapat segera bekerja sama dengan Anda.

Salam hangat,<br>
**Tim HR**
</x-mail::message>
