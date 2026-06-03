<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ManageSettings extends Page implements HasForms
{
  use InteractsWithForms;

  protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

  protected static string | \UnitEnum | null $navigationGroup = 'Pengaturan';

  protected string $view = 'filament.pages.manage-settings';

  protected static ?string $navigationLabel = 'Aturan Keputusan';

  protected static ?string $title = 'Aturan Keputusan';

  public static function canAccess(): bool
  {
    return auth()->check() && auth()->user()->hasRole(['hr', 'super_admin']);
  }

  public ?array $data = [];

  public function mount(): void
  {
    $this->form->fill([
      'c45_ai_threshold' => Setting::get('c45_ai_threshold', '57'),
      'c45_test_threshold' => Setting::get('c45_test_threshold', '63'),
      'c45_leaf1_confidence' => Setting::get('c45_leaf1_confidence', '88.2'),
      'c45_leaf2_confidence' => Setting::get('c45_leaf2_confidence', '79.4'),
      'c45_leaf3_confidence' => Setting::get('c45_leaf3_confidence', '90.6'),
      'c45_confidence_threshold' => Setting::get('c45_confidence_threshold', '79.4'),
      'weka_file' => Setting::get('weka_file'),
    ]);
  }

  public function form(Schema $schema): Schema
  {
    return $schema
      ->schema([
        Section::make('Unggah Model Keputusan')
          
          ->schema([
            Grid::make(1)
              ->schema([
                \Filament\Forms\Components\FileUpload::make('weka_file')
                  ->label('File Output')
                  ->acceptedFileTypes(['text/plain'])
                  ->disk('public')
                  ->directory('weka-models')
                  ->preserveFilenames()
                  ->maxSize(1024),
                \Filament\Forms\Components\Placeholder::make('active_model_info')
                  ->hiddenLabel()
                  ->content(function () {
                    $accuracy = Setting::get('c45_weka_accuracy');
                    $kappa = Setting::get('c45_weka_kappa');
                    $updatedAt = Setting::get('c45_weka_updated_at');
                    $fileName = Setting::get('c45_weka_file_name');

                    if (!$accuracy && !$kappa) {
                      return new \Illuminate\Support\HtmlString('<div style="color: #6b7280; font-style: italic;">Belum ada file model Weka aktif yang diunggah. Menggunakan parameter default.</div>');
                    }

                    return new \Illuminate\Support\HtmlString("
                      <div style='background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem;'>
                        <div style='display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;'>
                          <div>
                            <strong style='font-size: 0.75rem; color: #6b7280; text-transform: uppercase;'>Akurasi</strong>
                            <div style='font-size: 1.5rem; font-weight: 700; color: #10b981;'>{$accuracy}%</div>
                          </div>
                          <div>
                            <strong style='font-size: 0.75rem; color: #6b7280; text-transform: uppercase;'>Statistik Kappa</strong>
                            <div style='font-size: 1.5rem; font-weight: 700; color: #3b82f6;'>{$kappa}</div>
                          </div>
                        </div>
                        <div style='margin-top: 1rem; border-top: 1px solid #f3f4f6; padding-top: 0.5rem; font-size: 0.75rem; color: #6b7280;'>
                          <div><strong>File Aktif:</strong> {$fileName}</div>
                          <div><strong>Diunggah Pada:</strong> {$updatedAt}</div>
                        </div>
                      </div>
                    ");
                  })
              ])
          ]),

        Section::make('Threshold Keputusan')
          
          ->schema([
            Grid::make(2)
              ->schema([
                TextInput::make('c45_ai_threshold')
                  ->label('Threshold Skor AI (CV Screening)')
                  ->numeric()
                  ->required()
                  ->readOnly()
                  ->dehydrated(),
                TextInput::make('c45_test_threshold')
                  ->label('Threshold Skor Ujian')
                  ->numeric()
                  ->required()
                  ->readOnly()
                  ->dehydrated(),
              ]),
          ]),

        Section::make('Confidence Level Model & Alert Threshold (%)')
          ->schema([
            Grid::make(4)
              ->schema([
                TextInput::make('c45_leaf1_confidence')
                  ->label('Confidence Rule 1 (%)')
                  ->numeric()
                  ->required()
                  ->readOnly()
                  ->dehydrated()
                  ->suffix('%'),
                TextInput::make('c45_leaf2_confidence')
                  ->label('Confidence Rule 2 (%)')
                  ->numeric()
                  ->required()
                  ->readOnly()
                  ->dehydrated()
                  ->suffix('%'),
                TextInput::make('c45_leaf3_confidence')
                  ->label('Confidence Rule 3 (%)')
                  ->numeric()
                  ->required()
                  ->readOnly()
                  ->dehydrated()
                  ->suffix('%'),
                TextInput::make('c45_confidence_threshold')
                  ->label('Alert Threshold Confidence (%)')
                  ->numeric()
                  ->required()
                  ->readOnly()
                  ->dehydrated()
                  ->suffix('%')
                  ->helperText(new \Illuminate\Support\HtmlString('<small>Otomatis dari confidence terendah model.</small>')),
              ]),
          ]),
      ])
      ->statePath('data');
  }

  public function save(): void
  {
    $state = $this->form->getState();

    if (!empty($state['weka_file'])) {
      $path = $state['weka_file'];
      Log::info("Weka file upload debugging:", [
        'path' => $path,
        'is_array' => is_array($path),
        'disk_public_exists' => \Illuminate\Support\Facades\Storage::disk('public')->exists(is_array($path) ? reset($path) : $path),
      ]);
      
      try {
        $actualPath = is_array($path) ? reset($path) : $path;
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($actualPath)) {
          $content = \Illuminate\Support\Facades\Storage::disk('public')->get($actualPath);
          
          $aiMatch = [];
          $testMatch = [];
          $leaf1Match = [];
          $leaf2Match = [];
          $leaf3Match = [];
          $accuracyMatch = [];
          $kappaMatch = [];
          
          preg_match('/AI Score\s*\(CV\)\s*<=\s*([\d\.]+)/i', $content, $aiMatch);
          preg_match('/Test Score\s*\(Exam\)\s*<=\s*([\d\.]+)/i', $content, $testMatch);
          
          preg_match('/AI Score\s*\(CV\)\s*<=\s*[\d\.]+\s*:\s*REJECTED\s*\((\d+(?:\.\d+)?)(?:\/(\d+(?:\.\d+)?))?\)/i', $content, $leaf1Match);
          preg_match('/Test Score\s*\(Exam\)\s*<=\s*[\d\.]+\s*:\s*REJECTED\s*\((\d+(?:\.\d+)?)(?:\/(\d+(?:\.\d+)?))?\)/i', $content, $leaf2Match);
          preg_match('/Test Score\s*\(Exam\)\s*>\s*[\d\.]+\s*:\s*ACCEPTED\s*\((\d+(?:\.\d+)?)(?:\/(\d+(?:\.\d+)?))?\)/i', $content, $leaf3Match);
          
          preg_match('/Correctly Classified Instances\s+\d+\s+(\d+(?:\.\d+)?)\s*%/i', $content, $accuracyMatch);
          preg_match('/Kappa statistic\s+([\d\.-]+)/i', $content, $kappaMatch);
          
          if (empty($aiMatch) || empty($testMatch)) {
            Notification::make()
              ->title('Gagal Membaca Aturan')
              ->body('Gagal mem-parsing aturan dari file. Pastikan file yang diunggah merupakan buffer hasil valid J48.')
              ->danger()
              ->send();
            return;
          }
          
          $state['c45_ai_threshold'] = (string) $aiMatch[1];
          $state['c45_test_threshold'] = (string) $testMatch[1];
          
          $calcConfidence = function ($match, $default) {
            if (empty($match)) return $default;
            $total = (float) $match[1];
            $errors = isset($match[2]) ? (float) $match[2] : 0.0;
            if ($total <= 0) return $default;
            return (string) round((1 - ($errors / $total)) * 100, 1);
          };
          
          $state['c45_leaf1_confidence'] = $calcConfidence($leaf1Match, '88.2');
          $state['c45_leaf2_confidence'] = $calcConfidence($leaf2Match, '79.4');
          $state['c45_leaf3_confidence'] = $calcConfidence($leaf3Match, '90.6');

          // Auto-set confidence alert threshold = nilai terendah dari ketiga leaf node
          $state['c45_confidence_threshold'] = (string) min(
            (float) $state['c45_leaf1_confidence'],
            (float) $state['c45_leaf2_confidence'],
            (float) $state['c45_leaf3_confidence']
          );

          Setting::set('c45_weka_accuracy', isset($accuracyMatch[1]) ? $accuracyMatch[1] : '86');
          Setting::set('c45_weka_kappa', isset($kappaMatch[1]) ? $kappaMatch[1] : '0.7009');
          Setting::set('c45_weka_file_name', basename($path));
          Setting::set('c45_weka_updated_at', now()->format('Y-m-d H:i:s'));

          $this->data['c45_ai_threshold'] = $state['c45_ai_threshold'];
          $this->data['c45_test_threshold'] = $state['c45_test_threshold'];
          $this->data['c45_leaf1_confidence'] = $state['c45_leaf1_confidence'];
          $this->data['c45_leaf2_confidence'] = $state['c45_leaf2_confidence'];
          $this->data['c45_leaf3_confidence'] = $state['c45_leaf3_confidence'];
          $this->data['c45_confidence_threshold'] = $state['c45_confidence_threshold'];
        }
      } catch (\Throwable $e) {
        Notification::make()
          ->title('Error Processing File')
          ->body('An error occurred: ' . $e->getMessage())
          ->danger()
          ->send();
        return;
      }
    }

    foreach ($state as $key => $value) {
      Setting::set($key, $value);
    }

    $this->form->fill($state);

    Notification::make()
      ->title('Berhasil!')
      ->body('Pengaturan berhasil disimpan.')
      ->success()
      ->send();
  }
}
