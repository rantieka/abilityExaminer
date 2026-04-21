
$user = \App\Models\User::first();
echo "User ID: " . $user->id . "\n";
echo "Pre-count: " . \Illuminate\Support\Facades\DB::table('notifications')->count() . "\n";

try {
  \Filament\Notifications\Notification::make()
    ->title('Script Test')
    ->body('Testing filament notification specifically')
    ->sendToDatabase($user);
  
  echo "Filament Notify call completed.\n";
} catch (\Exception $e) {
  echo "Exception: " . $e->getMessage() . "\n";
  echo "Stack: " . $e->getTraceAsString() . "\n";
}

echo "Post-count: " . \Illuminate\Support\Facades\DB::table('notifications')->count() . "\n";


