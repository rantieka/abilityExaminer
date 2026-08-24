@extends('layouts.landing')

@section('title', 'Home - Ability Examiner')

@section('content')
<style>
  /* Custom designs for portfolio elements */
  .hero-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    padding: 30px;
    position: relative;
    overflow: hidden;
  }
  
  .hero-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    background-color: #ffb800;
  }

  .status-badge {
    background-color: #fef3c7;
    color: #ffb800;
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .pulse-dot {
    width: 8px;
    height: 8px;
    background-color: #ffb800;
    border-radius: 50%;
    display: inline-block;
    animation: pulse 1.5s infinite;
  }

  @keyframes pulse {
    0% {
      transform: scale(0.9);
      opacity: 0.6;
    }
    50% {
      transform: scale(1.2);
      opacity: 1;
    }
    100% {
      transform: scale(0.9);
      opacity: 0.6;
    }
  }

  .progress-glow {
    height: 8px;
    border-radius: 4px;
    background-color: #e5e7eb;
    overflow: hidden;
  }

  .progress-glow-bar {
    height: 100%;
    border-radius: 4px;
    background: linear-gradient(90deg, #ffe17d 0%, #ffb800 100%);
    box-shadow: 0 0 8px rgba(255, 184, 0, 0.5);
  }

  /* Tree Diagram Styles */
  .tree-container {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 24px;
  }

  .tree-node {
    background: white;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 12px 18px;
    display: inline-block;
    font-weight: 500;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    z-index: 2;
    position: relative;
  }

  .tree-node.root {
    border-color: #ffb800;
    background: #fffdf5;
  }

  .tree-leaf {
    border-radius: 8px;
    padding: 8px 16px;
    font-weight: 600;
    display: inline-block;
  }

  .tree-leaf.accept {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
  }

  .tree-leaf.reject {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
  }

  .tree-line-v {
    width: 2px;
    height: 25px;
    background-color: #cbd5e1;
    margin: 0 auto;
  }

  .tree-line-h-wrapper {
    position: relative;
    width: 100%;
    height: 20px;
  }

  .tree-line-h {
    position: absolute;
    top: 0;
    left: 25%;
    width: 50%;
    height: 2px;
    background-color: #cbd5e1;
  }

  .tree-line-branch-left {
    position: absolute;
    top: 0;
    left: 25%;
    width: 2px;
    height: 20px;
    background-color: #cbd5e1;
  }

  .tree-line-branch-right {
    position: absolute;
    top: 0;
    right: 25%;
    width: 2px;
    height: 20px;
    background-color: #cbd5e1;
  }

  .tree-label {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 4px;
  }

  /* Metric cards */
  .metric-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    transition: transform 0.2s ease;
  }

  .metric-card:hover {
    transform: translateY(-4px);
  }

  .metric-value {
    font-size: 2.2rem;
    font-weight: 700;
    color: #3f3f3f;
  }

  .metric-value.highlight {
    color: #ffb800;
  }
</style>

<div class="list-group position-fixed left-0 d-none d-sm-block" style="z-index: 1000;">
  <ul class="pagination-section">
    <li class="page-item"><a href="#home" class="rounded-circle text-decoration-none"></a></li>
    <li class="page-item"><a href="#about" class="rounded-circle text-decoration-none"></a></li>
    <li class="page-item"><a href="#services" class="rounded-circle text-decoration-none"></a></li>
    <li class="page-item"><a href="#metrics" class="rounded-circle text-decoration-none"></a></li>
    <li class="page-item"><a href="#contact" class="rounded-circle text-decoration-none"></a></li>
  </ul>
</div>

<!-- HERO SECTION -->
<section id="home" class="banner active-scroll my-5 mb-lg-0 py-4">
  <div class="container">
    <div class="banner-content row align-items-center">
      <div class="col-lg-6 mb-5">
        <div class="banner-content-text text-center text-lg-start" data-aos="fade-right" data-aos-duration="2000">
          <h1 class="banner-content-headline fw-bold mb-4" style="color: #3f3f3f; line-height: 1.2;">
            Sistem Seleksi Rekrutmen Cerdas Berbasis Data Mining & AI
          </h1>
          <p class="banner-content-desc mb-4" style="color: #64748b; font-size: 0.95rem; line-height: 1.6;">
            Mengintegrasikan <strong>Large Language Model (Groq Llama 3.3)</strong> untuk penyaringan CV otomatis dan <strong>Algoritma Pohon Keputusan C4.5</strong> untuk penentuan kelulusan pelamar secara objektif dan akurat berdasarkan evaluasi terpadu.
          </p>
          <div class="d-flex flex-column flex-sm-row justify-content-center justify-content-lg-start gap-3">
            <a href="{{ route('career.index') }}" class="btn d-inline-flex justify-content-center align-items-center text-decoration-none rounded shadow-sm px-4 py-3" style="background-color: #3f3f3f; color: white; border: 2px solid #3f3f3f; transition: all 0.3s; white-space: nowrap;" onmouseover="this.style.backgroundColor='#ffb800'; this.style.borderColor='#ffb800'; this.style.color='#3f3f3f';" onmouseout="this.style.backgroundColor='#3f3f3f'; this.style.borderColor='#3f3f3f'; this.style.color='white';">
              <span class="fw-semibold">Cari Lowongan Kerja</span>
            </a>
            <a href="{{ url('/portal') }}" class="btn d-inline-flex justify-content-center align-items-center text-decoration-none rounded shadow-sm px-4 py-3" style="background-color: transparent; border: 2px solid #3f3f3f; color: #3f3f3f; transition: all 0.3s; white-space: nowrap;" onmouseover="this.style.backgroundColor='#ffb800'; this.style.borderColor='#ffb800'; this.style.color='#3f3f3f';" onmouseout="this.style.backgroundColor='transparent'; this.style.borderColor='#3f3f3f'; this.style.color='#3f3f3f';">
              <span class="fw-semibold">Akses Portal HRD</span>
            </a>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="banner-content-img position-relative" data-aos="fade-left" data-aos-duration="2000">
          <!-- Beautiful Interactive Mockup Instead of Nineod Image -->
          <div class="hero-card shadow">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
              <div class="d-flex align-items-center gap-2">
                <i class="fas fa-file-pdf text-danger fs-4"></i>
                <div>
                  <h6 class="mb-0 fw-semibold text-dark" style="font-size: 0.9rem;">CV_Kandidat_Software_Engineer.pdf</h6>
                  <span class="text-muted" style="font-size: 0.75rem;">Status: Teranalisis oleh AI</span>
                </div>
              </div>
              <div class="status-badge">
                <span class="pulse-dot"></span>
                <span>Proses Seleksi</span>
              </div>
            </div>
            
            <div class="mb-3">
              <div class="d-flex justify-content-between text-dark mb-1" style="font-size: 0.85rem;">
                <span>AI Screening Score (Kesesuaian CV)</span>
                <span class="fw-bold">84%</span>
              </div>
              <div class="progress-glow">
                <div class="progress-glow-bar" style="width: 84%"></div>
              </div>
            </div>

            <div class="mb-4">
              <div class="d-flex justify-content-between text-dark mb-1" style="font-size: 0.85rem;">
                <span>Online CBT Score (Hasil Ujian)</span>
                <span class="fw-bold">78%</span>
              </div>
              <div class="progress-glow">
                <div class="progress-glow-bar" style="width: 78%"></div>
              </div>
            </div>

            <div class="p-3 rounded mb-2" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
              <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                  <i class="fas fa-project-diagram text-primary"></i>
                  <span class="fw-semibold text-dark" style="font-size: 0.9rem;">Keputusan C4.5 (Decision)</span>
                </div>
                <span class="tree-leaf accept">ACCEPTED</span>
              </div>
              <div class="mt-2 text-muted" style="font-size: 0.8rem; line-height: 1.4;">
                <i class="fas fa-quote-left me-1"></i> AI Score > 57.0 dan Test Score > 63.0. Tingkat kepercayaan klasifikasi model sebesar 90.6%.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ABOUT SECTION -->
<section id="about" class="about active-scroll py-5" style="background-color: #f9fafb;">
  <div class="container">
    <div class="about-content row align-items-center">
      <div class="col-lg-6 order-2 order-lg-1">
        <div class="about-content-img" data-aos="fade-right" data-aos-duration="2000">
          <!-- CSS Visualizing C4.5 Decision Tree Structure -->
          <div class="tree-container text-center">
            <h5 class="fw-semibold text-dark mb-4 text-center">Struktur Aturan Pohon Keputusan C4.5</h5>
            
            <!-- Root Node -->
            <div class="tree-node root">
              <div class="tree-label">Atribut Utama</div>
              <span>AI Score (CV)</span>
            </div>
            
            <div class="tree-line-v"></div>
            <div class="tree-line-h-wrapper">
              <div class="tree-line-h"></div>
              <div class="tree-line-branch-left"></div>
              <div class="tree-line-branch-right"></div>
            </div>
            
            <div class="row text-center mb-3">
              <div class="col-6">
                <div class="tree-label">&le; 57.0</div>
                <div class="tree-leaf reject">REJECTED</div>
              </div>
              <div class="col-6">
                <div class="tree-label">&gt; 57.0</div>
                <!-- Node 2 -->
                <div class="tree-node">
                  <div class="tree-label">Atribut Kedua</div>
                  <span>Test Score (Exam)</span>
                </div>
              </div>
            </div>
            
            <div class="row text-center">
              <div class="col-6 offset-6">
                <div class="tree-line-v" style="height: 15px;"></div>
                <div class="tree-line-h-wrapper" style="height: 15px;">
                  <div class="tree-line-h" style="left: 0; width: 100%;"></div>
                  <div class="tree-line-branch-left" style="left: 0; height: 15px;"></div>
                  <div class="tree-line-branch-right" style="right: 0; height: 15px;"></div>
                </div>
                <div class="row">
                  <div class="col-6">
                    <div class="tree-label">&le; 63.0</div>
                    <div class="tree-leaf reject">REJECTED</div>
                  </div>
                  <div class="col-6">
                    <div class="tree-label">&gt; 63.0</div>
                    <div class="tree-leaf accept">ACCEPTED</div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="mt-4 pt-3 border-top text-muted text-start" style="font-size: 0.8rem; line-height: 1.5;">
              <i class="fas fa-info-circle me-1" style="color: #ffb800;"></i>
              <strong>Catatan:</strong> Batas penilaian keputusan (AI Score &amp; Test Score) bersifat dinamis. Nilai batas ini dapat disesuaikan manual melalui panel admin atau beradaptasi secara otomatis sesuai dengan data latih baru apabila model pohon keputusan C4.5 dilatih ulang (<i>retrained</i>).
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 order-1 order-lg-2 mb-5">
        <div class="about-content-text" data-aos="fade-left" data-aos-duration="2000">
          <h2 class="fw-bold about-content-title h3" style="color: #3f3f3f;">Bagaimana Sistem Membantu Proses Rekrutmen?</h2>
          <p class="about-content-desc text-muted">Sistem ini menggabungkan kecerdasan buatan untuk menguraikan profil kandidat yang tidak terstruktur secara efisien, serta algoritma data mining klasik untuk pengambilan keputusan yang transparan.</p>
          <div class="d-flex align-items-center checklist-item mb-3">
            <i class="fas fa-check-circle fs-5 me-3" style="color: #ffb800;"></i>
            <span class="text-dark">Penyaringan Dokumen Terotomatisasi (PDF Parsing)</span>
          </div>
          <div class="d-flex align-items-center checklist-item mb-3">
            <i class="fas fa-check-circle fs-5 me-3" style="color: #ffb800;"></i>
            <span class="text-dark">Anonimisasi Data Sensitif Pelamar (PII Masking)</span>
          </div>
          <div class="d-flex align-items-center checklist-item mb-3">
            <i class="fas fa-check-circle fs-5 me-3" style="color: #ffb800;"></i>
            <span class="text-dark">Keputusan Obyektif & Bebas dari Bias Evaluator</span>
          </div>
          <div class="d-flex align-items-center checklist-item mb-3">
            <i class="fas fa-check-circle fs-5 me-3" style="color: #ffb800;"></i>
            <span class="text-dark">Penjelasan Logis Dinamis berbasis Natural Language (NLP)</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SERVICES SECTION (Fitur Utama) -->
<section id="services" class="service active-scroll py-5">
  <div class="container">
    <div class="service-header text-center mb-5">
      <h2 class="service-header-title fw-bold text-dark h3" data-aos="fade-in" data-aos-duration="2000">
        Fitur Unggulan Aplikasi <span class="d-block text-warning" style="color: #ffb800 !important;">Seleksi Terintegrasi</span>
      </h2>
      <p class="service-header-desc text-muted" data-aos="fade-in" data-aos-duration="2500">
        Tiga modul utama yang bersinergi dalam menentukan kelayakan kandidat secara objektif.
      </p>
    </div>
    <div class="row gy-4 service-list">
      <div class="col-md-4 service-item">
        <div class="service-item-content p-4 rounded shadow-sm h-100 bg-white border" data-aos="fade-right" data-aos-duration="2000">
          <div class="service-item-img text-center mb-3">
            <i class="fas fa-robot fs-1 p-3 rounded-circle text-warning bg-light" style="width: 80px; height: 80px; display: inline-flex; align-items: center; justify-content: center; color: #ffb800 !important;"></i>
          </div>
          <div class="service-item-text text-center">
            <h4 class="fw-semibold text-dark mb-3">AI CV Screening</h4>
            <p class="text-muted">
              Pengekstrakan data pengalaman, keterampilan, dan kualifikasi dari dokumen PDF CV secara otomatis menggunakan LLM Groq (Llama 3.3).
            </p>
          </div>
        </div>
      </div>
      <div class="col-md-4 service-item">
        <div class="service-item-content p-4 rounded shadow-sm h-100 bg-white border" data-aos="fade-up" data-aos-duration="2000">
          <div class="service-item-img text-center mb-3">
            <i class="fas fa-laptop-code fs-1 p-3 rounded-circle text-warning bg-light" style="width: 80px; height: 80px; display: inline-flex; align-items: center; justify-content: center; color: #ffb800 !important;"></i>
          </div>
          <div class="service-item-text text-center">
            <h4 class="fw-semibold text-dark mb-3">Ujian Kompetensi Online</h4>
            <p class="text-muted">
              Pelaksanaan ujian teknis online secara real-time langsung di sistem melalui token ujian terenkripsi yang dikirim via email.
            </p>
          </div>
        </div>
      </div>
      <div class="col-md-4 service-item">
        <div class="service-item-content p-4 rounded shadow-sm h-100 bg-white border" data-aos="fade-left" data-aos-duration="2000">
          <div class="service-item-img text-center mb-3">
            <i class="fas fa-project-diagram fs-1 p-3 rounded-circle text-warning bg-light" style="width: 80px; height: 80px; display: inline-flex; align-items: center; justify-content: center; color: #ffb800 !important;"></i>
          </div>
          <div class="service-item-text text-center">
            <h4 class="fw-semibold text-dark mb-3">C4.5 Decision Tree</h4>
            <p class="text-muted">
              Klasifikasi otomatis status penerimaan (Accepted/Rejected) menggunakan representasi pohon keputusan dari hasil pelatihan dataset historis.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- METRICS SECTION (Replacing History/Corporate stats) -->
<section id="metrics" class="history active-scroll py-5" style="background-color: #f9fafb;">
  <div class="container">
    <div class="history-header mb-5 text-center">
      <h2 class="history-header-title fw-bold text-dark h3" data-aos="fade-in" data-aos-duration="2000">
        Performa Evaluasi Model C4.5
      </h2>
      <p class="history-header-desc text-muted" data-aos="fade-in" data-aos-duration="3000">
        Model diuji menggunakan teknik <strong>10-Fold Cross-Validation</strong> menggunakan dataset latih historis dengan hasil metriks evaluasi sebagai berikut.
      </p>
    </div>
    <div class="history-body">
      <div class="row align-items-center">
        <div class="col-md-7 mb-4 mb-md-0">
          <div class="row gy-4 px-2" data-aos="fade-right" data-aos-duration="2000">
            <div class="col-6">
              <div class="metric-card">
                <span class="metric-value highlight">86%</span>
                <span class="d-block fw-semibold text-muted" style="font-size: 0.85rem; text-transform: uppercase;">Akurasi Klasifikasi</span>
              </div>
            </div>
            <div class="col-6">
              <div class="metric-card">
                <span class="metric-value">0.7009</span>
                <span class="d-block fw-semibold text-muted" style="font-size: 0.85rem; text-transform: uppercase;">Kappa Statistic</span>
              </div>
            </div>
            <div class="col-6">
              <div class="metric-card">
                <span class="metric-value">75.0%</span>
                <span class="d-block fw-semibold text-muted" style="font-size: 0.85rem; text-transform: uppercase;">Recall (ACCEPTED)</span>
              </div>
            </div>
            <div class="col-6">
              <div class="metric-card">
                <span class="metric-value">93.3%</span>
                <span class="d-block fw-semibold text-muted" style="font-size: 0.85rem; text-transform: uppercase;">Recall (REJECTED)</span>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-5">
          <div class="bg-white p-4 rounded border shadow-sm" data-aos="fade-left" data-aos-duration="2000">
            <h5 class="fw-bold mb-3 text-dark">Informasi Tambahan</h5>
            <ul class="text-muted ps-3" style="font-size: 0.95rem; line-height: 1.7;">
              <li class="mb-2"><strong>Akurasi Tinggi:</strong> Klasifikasi memiliki tingkat akurasi 86% yang membuktikan bahwa aturan pohon keputusan sangat representatif terhadap keputusan rekruter historis.</li>
              <li class="mb-2"><strong>Substantial Agreement:</strong> Nilai Kappa > 0.7 menunjukkan kesesuaian kuat antara model prediksi dan keputusan aktual.</li>
              <li class="mb-2"><strong>Proteksi Keputusan:</strong> Nilai Recall REJECTED mencapai 93.3%, meminimalisir kemungkinan meloloskan kandidat yang tidak kompeten secara keliru.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CONTACT SECTION -->
<section id="contact" class="contact py-5 active-scroll">
  <div class="container">
    <div class="contact-header text-center mb-4">
      <h2 class="fw-bold text-dark h3">Tertarik Mencoba Sistem Ini?</h2>
    </div>
    <div class="contact-body text-center max-width-600 mx-auto">
      <div class="bg-light p-4 rounded border mb-4">
        <p class="mb-0 text-muted" style="font-size: 0.9rem; line-height: 1.6;">
          Anda dapat mensimulasikan peran sebagai <strong>Kandidat / Pelamar</strong> dengan mendaftar pada halaman Karir, mengunggah berkas CV, mengikuti tautan ujian yang dikirim ke email simulasi Anda, dan melihat hasil seleksi secara transparan.
        </p>
      </div>
      <div>
        <a href="{{ route('career.index') }}" class="btn d-inline-flex justify-content-center align-items-center text-decoration-none rounded shadow-sm px-4 py-3" style="background-color: #3f3f3f; color: white; border: 2px solid #3f3f3f; transition: all 0.3s; white-space: nowrap;" onmouseover="this.style.backgroundColor='#ffb800'; this.style.borderColor='#ffb800'; this.style.color='#3f3f3f';" onmouseout="this.style.backgroundColor='#3f3f3f'; this.style.borderColor='#3f3f3f'; this.style.color='white';">
          <i class="fas fa-briefcase me-2"></i> <span class="fw-semibold">Lamar Sekarang</span>
        </a>
      </div>
    </div>
  </div>
</section>
@endsection