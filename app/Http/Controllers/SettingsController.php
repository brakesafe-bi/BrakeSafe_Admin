<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    protected const SETTINGS_FILE = 'settings/thresholds.json';

    public function index()
    {
        $thresholds = $this->loadThresholds();

        return view('settings.index', compact('thresholds'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'soft_brake' => 'required|numeric|min:0|max:30',
            'medium_brake' => 'required|numeric|min:0|max:30',
            'hard_brake' => 'required|numeric|min:0|max:30',
            'emergency_brake' => 'required|numeric|min:0|max:30',
        ]);

        $this->saveThresholds([
            'soft_brake' => round($validated['soft_brake'], 1),
            'medium_brake' => round($validated['medium_brake'], 1),
            'hard_brake' => round($validated['hard_brake'], 1),
            'emergency_brake' => round($validated['emergency_brake'], 1),
        ]);

        return redirect()->route('settings')->with('status', 'Brake detection thresholds updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('settings')->with('status', 'Password changed successfully.');
    }

    protected function loadThresholds(): array
    {
        if (Storage::disk('local')->exists(self::SETTINGS_FILE)) {
            $content = Storage::disk('local')->get(self::SETTINGS_FILE);
            $data = json_decode($content, true);
            if (is_array($data)) {
                return array_merge($this->defaultThresholds(), $data);
            }
        }

        return $this->defaultThresholds();
    }

    protected function saveThresholds(array $thresholds): void
    {
        Storage::disk('local')->put(self::SETTINGS_FILE, json_encode($thresholds, JSON_PRETTY_PRINT));
    }

    protected function defaultThresholds(): array
    {
        return [
            'soft_brake' => 2.0,
            'medium_brake' => 4.5,
            'hard_brake' => 7.0,
            'emergency_brake' => 9.5,
        ];
    }
}
