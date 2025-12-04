<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Person\Person;

class HomeController extends Controller
{
    /**
     * Priority map for user redirection based on eHealth scopes.
     * The order of keys matters: higher priority roles (Owner, HR) should be checked first.
     *
     * Format: 'scope_name' => ['route' => 'route.name', 'role' => 'DEBUG_LABEL']
     */
    protected const array SCOPE_REDIRECT_MAP = [
        // --- LEVEL 1: OWNERS & TOP MANAGEMENT ---
        // Owners need to see the Legal Entity details, licenses, and contracts first.
        'legal_entity:update' => [
            'route' => 'legal-entity.details',
            'role'  => 'OWNER'
        ],
        // Pharmacy owners specifically might need a different dashboard, strictly speaking
        // they also have legal_entity:update, but if you have specific scopes for pharmacy:
//        'pharmacy:update' => [
//            'route' => 'legal-entity.details', // Or 'pharmacy.dashboard'
//            'role'  => 'PHARMACY_OWNER'
//        ],

        // --- LEVEL 2: HR & ADMINISTRATIVE STAFF ---
        // HR's main job is managing employees and verification.
        'employee_request:write' => [
            'route' => 'employee.index',
            'role'  => 'HR'
        ],
        // Med Admins manage divisions and services.
        'division:write' => [
            'route' => 'division.index',
            'role'  => 'MED_ADMIN'
        ],
        'healthcare_service:write' => [
            'route' => 'healthcare-service.index',
            'role'  => 'MED_ADMIN' // or MED_COORDINATOR
        ],

        // --- LEVEL 3: RECEPTION & REGISTRATION ---
        // Receptionists work with appointments and patient registration.
        // If you have a Calendar module, point them there.
//        'appointment:write' => [
//            'route' => 'appointment.index', // Or 'calendar.index'
//            'role'  => 'RECEPTIONIST'
//        ],
        // If only patient registration rights exist:
        'person:full:write' => [
            'route' => 'patient.index',
            'role'  => 'REGISTRAR'
        ],

        // --- LEVEL 4: CLINICAL ROLES (DOCTORS, SPECIALISTS) ---
        // Doctors spend most time creating encounters or prescriptions.
        'medical_events:write' => [
            'route' => 'patient.index', // Or 'my-patients.index'
            'role'  => 'DOCTOR/SPECIALIST'
        ],
//        'medication_request:write' => [
//            'route' => 'patient.index',
//            'role'  => 'DOCTOR_PCP'
//        ],

        // --- LEVEL 5: DIAGNOSTICS & LABORATORY ---
        // Laborants work with diagnostic reports queue.
//        'diagnostic_report:write' => [
//            'route' => 'diagnostic-report.index', // Pending reports list
//            'role'  => 'LABORANT'
//        ],

        // --- LEVEL 6: PHARMACISTS ---
        // Pharmacists dispense medications.
//        'medication_dispense:write' => [
//            'route' => 'medication-dispense.index',
//            'role'  => 'PHARMACIST'
//        ],

        // --- LEVEL 7: ASSISTANTS & READ-ONLY ---
        // Assistants usually prepare data for doctors.
        'medical_events:read' => [
            'route' => 'patient.index',
            'role'  => 'ASSISTANT'
        ],
    ];

    /**
     * Show the application landing page.
     *
     * @return View|RedirectResponse
     */
    public function index()
    {
        if (Auth::check()) {
            return $this->dashboard();
        }

        $email = config('app.email');
        $phone = config('app.phone');

        return view('home', compact('email', 'phone'));
    }

    /**
     * Dispatch the user to the appropriate page based on their scopes (permissions).
     *
     * @return RedirectResponse
     */
    public function dashboard(): RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // 1. Resolve Legal Entity Context
        $le = legalEntity();

        if (!$le) {
            return redirect()->route('legalEntity.select');
        }

        // 2. Iterate through the Priority Map
        // The first matching scope determines the redirect destination.
        foreach (self::SCOPE_REDIRECT_MAP as $scope => $config) {
            // Check if user has the specific scope (permission)
            if ($user->can($scope)) {
                // Optional: Log the role detection for debugging
                // \Log::info("User redirected as {$config['role']}");

                return redirect()->route($config['route'], ['legalEntity' => $le->id]);
            }
        }

        // 3. Fallback for generic users (e.g., View Only)
        if ($user->can('viewAny', Person::class)) {
            return redirect()->route('patient.index', ['legalEntity' => $le->id]);
        }

        // 4. Ultimate Fallback
        return redirect()->away('https://openhealths.com/dashboard');
    }
}
