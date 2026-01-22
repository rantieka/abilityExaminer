<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Application;
use Illuminate\Support\Facades\Session;

class TestAuthController extends Controller
{
  public function index()
  {
    return view('test.login');
  }

  public function authenticate(Request $request)
  {
    $request->validate([
      'email' => 'required|email',
      'application_id' => 'required|numeric',
    ]);

    $application = Application::where('id', $request->application_id)
      ->where('email', $request->email)
      ->first();

    if ($application) {
      // Set session untuk login pelamar
      Session::put('applicant_id', $application->id);
      return redirect()->route('test.show', $application->id);
    }

    return back()->with('error', 'Email or Application ID is incorrect.');
  }
}
