<?php

namespace App\Http\Controllers;

use App\Http\Controllers\PeopleController;
use App\Models\User;
use App\Models\Quotation;
use App\Models\Service;
use App\Models\Subservice;
use App\Models\Options;
use App\Models\Items;
use App\Models\Invoice;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use App\Services\SubServicesService;
use App\Helpers\getNewClientsHelper;
use App\Models\Gallery;
use App\Models\Photos;
use App\Models\GroupPhotos;
use App\Models\Occupations;
use App\Models\Specializations;
use App\Models\CareerSteps;
use App\Models\Carousel;
use App\Models\Blog;
use App\Models\Application;
use App\Models\InternshipApplication;
use App\Models\InternshipProgram;
use App\Support\ProgramFormFields;
use App\Models\InternsLearner;
use App\Models\MictBeneficiary;
use App\Models\Partner;
use App\Models\SiteSetting;
use App\Models\Message;
use App\Mail\StaffAccountActivation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Mail\ApplicationAccepted;
use App\Mail\ApplicationRejected;
use App\Helpers\StaffEmailHelper;
use App\Helpers\StaffFolderHelper;
use App\Models\StaffSale;
use App\Models\Client;
use App\Services\ClientLeadService;
use App\Models\JobTitle;
use App\Services\ProgramAnnouncementService;
use App\Services\StaffPermissionSyncService;
use App\Services\StaffSalesService;
use App\Services\Lmis\LmisClient;
use App\Services\Lmis\LmisProgramSyncService;
use App\Services\Lmis\LmisRequestException;




class AdminController extends Controller
{
    private $subServicesService;

    public function __construct(SubServicesService $subServicesService)
    {
        $this->subServicesService = $subServicesService;
    }
    public function index(StaffSalesService $staffSales)
    {
        $user = auth()->user();
        if ($user && $user->hasRole('student')) {
            return redirect()->route('student.portal');
        }

        $isAdmin = $user && $user->isDashboardAdmin();
        $employee = $user ? $user->employee : null;
        $isStaffView = ! $isAdmin && $employee;

        $fy = $staffSales->financialYearBounds();
        $financialYearLabel = $fy['label'];

        // Staff see their own attributed sales; admins keep company-wide invoice totals.
        if ($isStaffView) {
            $salesByMonth = $staffSales->salesByMonth((int) $employee->id);
            $mySalesThisMonth = $staffSales->employeeMonthTotal((int) $employee->id);
            $mySalesFyTotal = $staffSales->employeeFyTotal((int) $employee->id);
            $myCommissionFy = $staffSales->employeeCommissionFyTotal((int) $employee->id);
            $leaderboard = $staffSales->leaderboard();
            $myRank = optional($leaderboard->firstWhere('employee_id', (int) $employee->id))->rank;
            $staffTotal = null;
            $staffNewThisMonth = null;
        } else {
            $today = now();
            $financialYearStart = $fy['start'];
            $financialYearEnd = $fy['end'];

            $salesPerMonth = Invoice::query()
                ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(COALESCE(total_price, 0)) as total')
                ->whereBetween('created_at', [$financialYearStart, $financialYearEnd])
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->year, $r->month));

            $monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $salesByMonth = [];
            $prevTotal = null;
            for ($i = 0; $i < 12; $i++) {
                $date = $financialYearStart->copy()->addMonths($i);
                $key = $date->format('Y-m');
                $row = $salesPerMonth->get($key);
                $total = (float) ($row->total ?? 0);
                $trend = null;
                if ($prevTotal !== null) {
                    $trend = $total >= $prevTotal ? 'up' : 'down';
                }
                $prevTotal = $total;
                $salesByMonth[] = [
                    'label' => $monthNames[(int) $date->format('n')] . ' ' . $date->format('Y'),
                    'total' => $total,
                    'trend' => $trend,
                ];
            }

            $mySalesThisMonth = null;
            $mySalesFyTotal = null;
            $myCommissionFy = null;
            $myRank = null;
            $staffTotal = Employee::count();
            $staffNewThisMonth = Employee::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
        }

        $canClients = $user && method_exists($user, 'canAccessClients') && $user->canAccessClients();
        $inquiries = $canClients
            ? Client::leads()->latest('updated_at')->take(15)->get()
            : collect();

        $pageTitle = 'Dashboard';

        return view('admin.dashboard.overview', compact(
            'salesByMonth',
            'financialYearLabel',
            'inquiries',
            'canClients',
            'staffTotal',
            'staffNewThisMonth',
            'pageTitle',
            'isAdmin',
            'isStaffView',
            'employee',
            'mySalesThisMonth',
            'mySalesFyTotal',
            'myCommissionFy',
            'myRank'
        ));
    }

    public function remove($id)
    {
        $quotation = Quotation::find($id);
        $quotation->delete();
        return redirect()->back()->with('success', 'User has been deleted!');
    }

    public function removeinvoice($id)
    {
        $invoice = Invoice::find($id);
        $invoice->delete();
        return redirect()->back()->with('success', 'invoice has been deleted!');
    }

    public function quotations()
    {
        $quotations = Quotation::paginate(10);
        return view('admin.dashboard.quotations.index', compact('quotations'));
    }

    public function invoices()
    {
        $invoices = Invoice::paginate(10);
        return view('admin.dashboard.invoices.index', compact('invoices'));
    }

    public function view_all_blogs()
    {
        return view('admin.blogs.view_all_blogs');
    }

    public function clients()
    {
        $clients = User::select('users.*', 'roles.display_name')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('roles.id', '!=', 1)
            ->get();

        return view('admin.dashboard.clients.index', compact('clients'));
    }

    public function users()
    {
        $users = User::all();
        return view('admin.users', compact('users'));
    }



    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect()->back()->with('error', 'User not found!');
        }

        $user->delete();
        return redirect()->back()->with('success', 'User has been deleted!');
    }

    public function viewapplications($id)
    {
        $person = PeopleController::resolveApplication($id);

        return redirect()->route('dashboard.people.view', $person);
    }


    public function viewInternship($id)
    {
        $internship = InternshipApplication::findOrFail($id);
        
        // Return JSON for modal
        return response()->json([
            'success' => true,
            'internship' => $internship
        ]);
    }

    public function downloadinternshipDocs($id, $type)
    {
        $person = PeopleController::resolveApplication($id);
        $filePath = match ($type) {
            'cv' => $person->cv_path,
            'id_copy' => $person->id_copy_path,
            'qualification_copy' => $person->qualification_copy_path,
            'proof_of_payment' => $person->proof_of_payment_path,
            default => null,
        };

        abort_unless($filePath, 404);

        return response()->download(public_path($filePath));
    }

    public function viewquotations($id)
    {
        $quotation = DB::table('quotations')->find($id);
        $items = Items::where('QI_id', $quotation->quotation_no)->get();
        return view('admin/viewquotations', compact('quotation', 'items'),);
    }

    public function viewinvoice($id)
    {
        $invoice = DB::table('invoices')->find($id);
        $items = Items::where('QI_id', $invoice->invoice_no)->get();
        return view('admin/viewinvoice', compact('invoice', 'items'));
    }

    public function viewuser($id)
    {
        $user = User::find($id);
        return view('admin/viewuser', compact('user'));
    }

    public function services()
    {
        $services = Service::with('tiers')->orderBy('name')->get();

        return view('admin.dashboard.services.index', compact('services'));
    }

    /**
     * View a specific service and its subservices
     *
     * @param  string|int $slug
     * @return \Illuminate\View\View
     */
    public function viewservice($slug)
    {
        // Try to find by slug first, then by id
        $service = Service::where('slug', $slug)->first();
        
        if (!$service && is_numeric($slug)) {
            $service = Service::find($slug);
        }

        if (!$service) {
            return redirect()->back()->withErrors(['error' => 'Service not found.']);
        }
        $service->load(['tiers.page', 'tiers.tierPrice', 'tiers.packages.features', 'tiers.addons']);
        $tiersByKey = $service->tiers->keyBy('tier_key');
        $subservices = Subservice::where('service_id', $service->service_id)->get();
        $extras = $subservices->isNotEmpty();

        return view('admin.services.viewservice', compact('service', 'subservices', 'extras', 'tiersByKey'));
    }

    public function viewsubservice($id)
    {
        $subservice = SubService::where('id', $id)->first();
        $options = Options::where('subservice_id', $subservice->subserv_id)->get();
        $serviceName = $this->subServicesService->findService($subservice->service_id)->name;
        $serviceDesc = $this->subServicesService->findService($subservice->service_id)->description;
        $serviceID = $this->subServicesService->findService($subservice->service_id)->id;

        return view('admin/subservices/viewsubservice', compact('id', 'subservice', 'options', 'serviceName', 'serviceID', 'serviceDesc'));
    }
    public function view_employee($id)
    {
        // Try to find by ID first, then by first_name
        $employee = Employee::with(['user', 'assignedTitle.permissionGroups'])->where('id', $id)->orWhere('first_name', $id)->first();
        
        if (!$employee) {
            return redirect()->route('admin.dashboard.staff')->with('error', 'Staff member not found.');
        }

        $jobTitles = JobTitle::with('permissionGroups')->active()->orderBy('name')->get();
        
        return view('admin.dashboard.staff.view', compact('employee', 'jobTitles'));
    }

    public function all_employees(StaffSalesService $staffSales)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->isDashboardAdmin();
        $currentEmployee = $user ? $user->employee : null;

        $employees = Employee::with(['user', 'assignedTitle'])->orderBy('first_name')->orderBy('last_name')->paginate(10);
        $jobTitles = JobTitle::with('permissionGroups')->active()->orderBy('name')->get();

        $organogramEmployees = Employee::query()
            ->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'job_title',
                'profile_picture',
                'manager_id',
                'sort_order',
            ])
            ->orderBy('sort_order')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function (Employee $employee) {
                return [
                    'id' => $employee->id,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'email' => $employee->email,
                    'job_title' => $employee->job_title,
                    'manager_id' => $employee->manager_id,
                    'sort_order' => (int) $employee->sort_order,
                    'profile_picture_url' => $employee->photo_url,
                    'initials' => strtoupper(
                        substr((string) $employee->first_name, 0, 1)
                        . substr((string) $employee->last_name, 0, 1)
                    ),
                    'view_url' => route('dashboard.staff.view', $employee->id),
                ];
            })
            ->values();

        $fy = $staffSales->financialYearBounds();
        $salesLeaderboard = $staffSales->leaderboard();
        $salesEmployees = Employee::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'job_title']);

        $salePeople = Client::query()
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [Client::STATUS_LEAD])
            ->orderBy('name')
            ->get();

        return view('admin.dashboard.staff.index', compact(
            'employees',
            'organogramEmployees',
            'salesLeaderboard',
            'salesEmployees',
            'salePeople',
            'isAdmin',
            'currentEmployee',
            'fy',
            'jobTitles'
        ));
    }

    public function store_staff_sale(Request $request, ClientLeadService $leads)
    {
        $user = auth()->user();
        if (! $user || ! $user->isDashboardAdmin()) {
            abort(403, 'Only admins can record staff sales.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'amount' => 'required|numeric|min:0',
            'commission' => 'nullable|numeric|min:0',
            'sale_date' => 'required|date',
            'client_id' => 'nullable|integer|exists:clients,id',
            'client_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
        ]);

        $client = $leads->convertFromSale(
            isset($validated['client_id']) ? (int) $validated['client_id'] : null,
            $validated['client_name'] ?? null
        );

        StaffSale::create([
            'employee_id' => $validated['employee_id'],
            'client_id' => $client?->id,
            'amount' => $validated['amount'],
            'commission' => $validated['commission'] ?? null,
            'sale_date' => $validated['sale_date'],
            'client_name' => $validated['client_name'] ?: $client?->displayName(),
            'notes' => $validated['notes'] ?? null,
            'recorded_by' => $user->id,
        ]);

        return redirect()
            ->route('admin.dashboard.staff', ['tab' => 'leaderboard'])
            ->with('success', 'Sale recorded. Commission is private and will not appear on the leaderboard.');
    }

    public function all_JSON_employees()
    {
        // Retrieve all employees using your desired logic
        $employees = DB::table('employees')->get();

        return $employees;
    }

    public function new_employee(Request $request)
    {
        if (! auth()->user() || ! auth()->user()->isDashboardAdmin()) {
            abort(403);
        }

        try {
            if ($request->filled('first_name')) {
                $request->merge([
                    'email' => StaffEmailHelper::fromFirstName($request->input('first_name')),
                ]);
            }

            $validatedData = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'job_title' => 'nullable|string|max:255',
                'job_title_id' => 'required|exists:job_titles,id',
                'email' => 'required|email|unique:employees,email',
                'personal_email' => 'nullable|email|max:255',
                'phone' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'province' => 'required|string|max:255',
                'ID_number' => 'required|string|min:13|max:13|unique:employees,ID_number',
                'profile_picture' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
                'id_copy' => StaffFolderHelper::DOCUMENT_RULE,
                'bank_confirmation' => StaffFolderHelper::DOCUMENT_RULE,
                'cv' => StaffFolderHelper::DOCUMENT_RULE,
                'sars_income_tax' => StaffFolderHelper::DOCUMENT_RULE,
                'date_of_birth' => 'nullable|date',
            ]);

            $this->applyJobTitleFields($validatedData);

            StaffFolderHelper::ensureDirectory($validatedData['first_name'], $validatedData['last_name']);

            $profilePicturePath = null;
            if ($request->file('profile_picture')) {
                $profilePicture = $request->file('profile_picture');
                if (! $profilePicture->isValid()) {
                    throw ValidationException::withMessages([
                        'profile_picture' => StaffFolderHelper::uploadErrorMessage($profilePicture),
                    ]);
                }

                $profilePicturePath = StaffFolderHelper::storeUpload(
                    $profilePicture,
                    $validatedData['first_name'],
                    $validatedData['last_name']
                );
            }

            $user = new User;
            $user->name = $validatedData['first_name'] . ' ' . $validatedData['last_name'];
            $user->email = $validatedData['email'];
            $user->password = bcrypt('K@y1s31T'); // Set the temporary password
            $user->save();
            try {
                $user->attachRole('staff');
            } catch (\Throwable $e) {
                \Log::warning('Could not attach staff role on create: '.$e->getMessage());
            }

            // Create a new employee record
            $employee = new Employee;
            $employee->user_id = $user->id;
            $employee->first_name = $validatedData['first_name'];
            $employee->last_name = $validatedData['last_name'];
            $employee->job_title = $validatedData['job_title'] ?? null;
            $employee->job_title_id = $validatedData['job_title_id'] ?? null;
            $employee->email = $validatedData['email'];
            $employee->personal_email = $validatedData['personal_email'] ?? null;
            $employee->phone = $validatedData['phone'];
            $employee->address = $validatedData['address'];
            $employee->province = $validatedData['province'];
            $employee->ID_number = $validatedData['ID_number'];
            $employee->profile_picture = $profilePicturePath;
            $this->storeStaffDocuments($request, $employee, $validatedData['first_name'], $validatedData['last_name']);
            $employee->id_verifi_doc = ($validatedData['id_verifi_doc'] ?? false) || (bool) $employee->id_verifi_doc;
            $employee->proof_address_verifi_doc = $validatedData['proof_address_verifi_doc'] ?? false;
            $employee->bank_confi_verifi = ($validatedData['bank_confi_verifi'] ?? false) || (bool) $employee->bank_confi_verifi;
            $employee->date_of_birth = $validatedData['date_of_birth'];
            $employee->save();
            StaffEmailHelper::assignWorkEmail($employee->fresh());
            app(StaffPermissionSyncService::class)->syncEmployee($employee->fresh(['user', 'assignedTitle']));

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Staff member created successfully.', 'employee' => $employee]);
            }
            
            return redirect()->route('admin.dashboard.staff')->with('success', 'Staff member created successfully!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }
    public function delete_employee(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $this->destroyEmployeeRecord($employee);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Staff member and associated user have been deleted.']);
            }

            return redirect()->route('admin.dashboard.staff')->with('success', 'Staff member and associated user have been deleted.');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to delete staff member: ' . $e->getMessage()], 500);
            }

            return redirect()->route('admin.dashboard.staff')->with('error', 'Failed to delete staff member: ' . $e->getMessage());
        }
    }

    public function bulk_employees(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:activate,reset,delete',
            'delivery' => 'nullable|in:work,personal',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:employees,id',
        ]);

        $employees = Employee::with('user')->whereIn('id', $validated['ids'])->get();

        if ($validated['action'] === 'delete') {
            $deleted = 0;
            $skipped = 0;

            foreach ($employees as $employee) {
                if ($employee->user_id && (int) $employee->user_id === (int) auth()->id()) {
                    $skipped++;
                    continue;
                }

                $this->destroyEmployeeRecord($employee);
                $deleted++;
            }

            $message = "Deleted {$deleted} staff member(s).";
            if ($skipped > 0) {
                $message .= " Skipped {$skipped} (your own account).";
            }

            return redirect()->route('admin.dashboard.staff')->with('success', $message);
        }

        $delivery = $validated['delivery'] ?? 'work';
        $sent = 0;
        $failed = [];

        foreach ($employees as $employee) {
            try {
                $this->sendStaffActivationEmail($employee, $delivery);
                $sent++;
            } catch (\Throwable $e) {
                $failed[] = ($employee->email ?: $employee->full_name).' ('.$e->getMessage().')';
                \Log::error('Staff activation email failed: '.$e->getMessage(), [
                    'employee_id' => $employee->id,
                ]);
            }
        }

        $label = $validated['action'] === 'reset' ? 'password reset' : 'activation';
        $message = "Sent {$label} emails to {$sent} staff member(s).";
        if ($failed !== []) {
            $message .= ' Could not send to: '.implode(', ', $failed).'.';
        }

        return redirect()->route('admin.dashboard.staff')->with(
            $failed === [] ? 'success' : 'error',
            $message
        );
    }

    protected function destroyEmployeeRecord(Employee $employee): void
    {
        if ($employee->user_id) {
            $user = User::find($employee->user_id);
            if ($user) {
                $user->delete();
            }
        }

        StaffFolderHelper::deleteStored($employee->profile_picture);
        StaffFolderHelper::deleteDirectory($employee->first_name, $employee->last_name);

        $employee->delete();
    }

    /**
     * Resolve job_title_id → denormalized job_title string for display.
     *
     * @param  array<string, mixed>  $validatedData
     */
    protected function applyJobTitleFields(array &$validatedData): void
    {
        $titleId = $validatedData['job_title_id'] ?? null;
        if ($titleId) {
            $title = JobTitle::find($titleId);
            if ($title) {
                $validatedData['job_title_id'] = $title->id;
                $validatedData['job_title'] = $title->name;

                return;
            }
        }

        $validatedData['job_title_id'] = null;
    }

    protected function storeStaffDocuments(Request $request, Employee $employee, string $firstName, string $lastName): void
    {
        foreach (StaffFolderHelper::DOCUMENT_TYPES as $field => $meta) {
            if (! $request->file($field)) {
                continue;
            }

            $file = $request->file($field);
            if (! $file->isValid()) {
                throw ValidationException::withMessages([
                    $field => StaffFolderHelper::uploadErrorMessage($file),
                ]);
            }

            StaffFolderHelper::deleteStored($employee->{$meta['column']});
            $employee->{$meta['column']} = StaffFolderHelper::storeDocument($file, $firstName, $lastName, $field);

            if ($field === 'id_copy') {
                $employee->id_verifi_doc = true;
            }
            if ($field === 'bank_confirmation') {
                $employee->bank_confi_verifi = true;
            }
        }
    }

    public function send_employee_activation(Request $request, $id)
    {
        $employee = Employee::with('user')->findOrFail($id);

        if ($employee->user && $employee->user->email_verified_at) {
            return redirect()
                ->route('dashboard.staff.view', $employee->id)
                ->with('success', 'This staff account is already activated.');
        }

        $validated = $request->validate([
            'delivery' => 'required|in:work,personal',
        ]);

        try {
            $sentTo = $this->sendStaffActivationEmail($employee, $validated['delivery']);

            return redirect()
                ->route('dashboard.staff.view', $employee->id)
                ->with('success', 'Activation email sent to '.$sentTo.'. Login remains '.$employee->fresh()->email.'.');
        } catch (\Throwable $e) {
            \Log::error('Staff activation email failed: '.$e->getMessage(), [
                'employee_id' => $employee->id,
            ]);

            return redirect()
                ->route('dashboard.staff.view', $employee->id)
                ->with('error', 'Could not send activation email: '.$e->getMessage());
        }
    }

    protected function sendStaffActivationEmail(Employee $employee, string $channel = 'work'): string
    {
        StaffEmailHelper::assignWorkEmail($employee);
        $employee->refresh();

        $delivery = StaffEmailHelper::deliveryAddress($employee, $channel);
        $user = $this->ensureStaffUser($employee);
        $token = Password::broker()->createToken($user);
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        $mailer = StaffEmailHelper::outboundMailer();
        Mail::mailer($mailer)->to($delivery)->send(new StaffAccountActivation($employee, $user, $resetUrl, $delivery));

        \Log::info('Staff activation email sent', [
            'employee_id' => $employee->id,
            'to' => $delivery,
            'mailer' => $mailer,
        ]);

        return $delivery;
    }

    protected function ensureStaffUser(Employee $employee): User
    {
        $user = $employee->user;

        if (! $user && $employee->user_id) {
            $user = User::find($employee->user_id);
        }

        if (! $user) {
            $user = User::where('email', $employee->email)->first();
        }

        if (! $user) {
            $user = User::create([
                'name' => $employee->full_name,
                'email' => $employee->email,
                'password' => Hash::make(Str::random(32)),
            ]);
        }

        if ((int) $employee->user_id !== (int) $user->id) {
            $employee->user_id = $user->id;
            $employee->save();
        }

        if (method_exists($user, 'hasRole') && ! $user->hasRole('staff') && ! $user->hasRole('admin')) {
            try {
                $user->attachRole('staff');
            } catch (\Throwable $e) {
                \Log::warning('Could not attach staff role: '.$e->getMessage(), [
                    'user_id' => $user->id,
                ]);
            }
        }

        return $user;
    }
    public function update_employee(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            if ($request->filled('first_name')) {
                $request->merge([
                    'email' => StaffEmailHelper::fromFirstName($request->input('first_name'), $employee->email),
                ]);
            }

        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
                'job_title' => 'nullable|string|max:255',
                'job_title_id' => 'required|exists:job_titles,id',
                'email' => 'required|email|unique:employees,email,' . $employee->id,
                'personal_email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'province' => 'required|string|max:255',
                'ID_number' => 'required|string|min:13|max:13|unique:employees,ID_number,' . $employee->id,
                'date_of_birth' => 'nullable|date',
                'profile_picture' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
                'id_copy' => StaffFolderHelper::DOCUMENT_RULE,
                'bank_confirmation' => StaffFolderHelper::DOCUMENT_RULE,
                'cv' => StaffFolderHelper::DOCUMENT_RULE,
                'sars_income_tax' => StaffFolderHelper::DOCUMENT_RULE,
                'id_verifi_doc' => 'nullable|boolean',
                'proof_address_verifi_doc' => 'nullable|boolean',
                'bank_confi_verifi' => 'nullable|boolean',
            ]);

            $this->applyJobTitleFields($validatedData);

            $nameChanged = StaffFolderHelper::slug($employee->first_name, $employee->last_name)
                !== StaffFolderHelper::slug($validatedData['first_name'], $validatedData['last_name']);

            if ($nameChanged) {
                StaffFolderHelper::renameDirectory(
                    $employee->first_name,
                    $employee->last_name,
                    $validatedData['first_name'],
                    $validatedData['last_name']
                );
                $relocated = StaffFolderHelper::relocateStaffPath(
                    $employee->profile_picture,
                    $validatedData['first_name'],
                    $validatedData['last_name']
                );
                if ($relocated) {
                    $validatedData['profile_picture'] = $relocated;
                }
                foreach (StaffFolderHelper::DOCUMENT_TYPES as $meta) {
                    $relocatedDoc = StaffFolderHelper::relocateStaffPath(
                        $employee->{$meta['column']},
                        $validatedData['first_name'],
                        $validatedData['last_name']
                    );
                    if ($relocatedDoc) {
                        $validatedData[$meta['column']] = $relocatedDoc;
                    }
                }
            } else {
                StaffFolderHelper::ensureDirectory($validatedData['first_name'], $validatedData['last_name']);
            }

            if ($request->file('profile_picture')) {
                $profilePicture = $request->file('profile_picture');
                if (! $profilePicture->isValid()) {
                    throw ValidationException::withMessages([
                        'profile_picture' => StaffFolderHelper::uploadErrorMessage($profilePicture),
                    ]);
                }

                StaffFolderHelper::deleteStored($employee->profile_picture);
                $validatedData['profile_picture'] = StaffFolderHelper::storeUpload(
                    $profilePicture,
                    $validatedData['first_name'],
                    $validatedData['last_name']
                );
            }

            $this->storeStaffDocuments($request, $employee, $validatedData['first_name'], $validatedData['last_name']);
            foreach (StaffFolderHelper::DOCUMENT_TYPES as $meta) {
                if ($employee->{$meta['column']}) {
                    $validatedData[$meta['column']] = $employee->{$meta['column']};
                }
            }
            if ($employee->id_verifi_doc) {
                $validatedData['id_verifi_doc'] = true;
            }
            if ($employee->bank_confi_verifi) {
                $validatedData['bank_confi_verifi'] = true;
            }

            $employee->update($validatedData);
            $employee->refresh();
            $employee->first_name = $validatedData['first_name'];
            $employee->last_name = $validatedData['last_name'];
            StaffEmailHelper::assignWorkEmail($employee);

            $employee->refresh();
            if ($employee->user) {
                $employee->user->name = $validatedData['first_name'].' '.$validatedData['last_name'];
                $employee->user->save();
            }

            app(StaffPermissionSyncService::class)->syncEmployee($employee->fresh(['user', 'assignedTitle']));

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Staff member updated successfully.', 'employee' => $employee]);
            }

            return redirect()->route('admin.dashboard.staff')->with('success', 'Staff member updated successfully.');
        } catch (ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to update staff member: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to update staff member: ' . $e->getMessage())->withInput();
        }
    }

    // Applications CRUD methods
    public function applications()
    {
        $applications = InternshipApplication::with('internshipProgram')->orderBy('created_at', 'desc')->get();
        return view('admin.dashboard.applications.index', compact('applications'));
    }

    public function createApplication()
    {
        return view('admin.applications.create');
    }

    public function storeApplication(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'app_type' => 'nullable|string|max:255',
            'field' => 'nullable|string|max:255',
            'program_partner' => 'nullable|string|max:255',
            'status' => 'required|in:pending,accepted,rejected'
        ]);

        InternshipApplication::create($validatedData);
        return redirect()->route('dashboard.applications')->with('success', 'Application created successfully.');
    }

    public function editApplication($id)
    {
        $application = InternshipApplication::findOrFail($id);
        return view('admin.applications.edit', compact('application'));
    }

    public function updateApplication(Request $request, $id)
    {
        $application = InternshipApplication::findOrFail($id);
        
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'app_type' => 'nullable|string|max:255',
            'field' => 'nullable|string|max:255',
            'program_partner' => 'nullable|string|max:255',
            'status' => 'required|in:pending,accepted,rejected'
        ]);

        $application->update($validatedData);
        return redirect()->route('dashboard.applications')->with('success', 'Application updated successfully.');
    }

    public function deleteApplication($id)
    {
        $application = InternshipApplication::findOrFail($id);
        $application->delete();
        return redirect()->back()->with('success', 'Application deleted successfully.');
    }

    public function deleteSelectedApplications(Request $request)
    {
        $selectedIds = json_decode($request->input('selected_ids'), true) ?? [];

        foreach ($selectedIds as $id) {
            PeopleController::resolveApplication($id)->delete();
        }

        return redirect()->route('dashboard.people', ['type' => 'application'])->with('success', 'Selected applications deleted successfully.');
    }

    // Internships CRUD methods
    public function internships()
    {
        $internships = InternshipApplication::orderBy('created_at', 'desc')->get();
        return view('admin.dashboard.internships.index', compact('internships'));
    }

    public function createInternship()
    {
        return view('admin.internships.create');
    }

    public function storeInternship(Request $request)
    {
        try {
            // Generate a unique app_id
            $app_id = 'APP_' . mt_rand(100000, 999999);
            
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'id_no' => 'required|string|max:20',
                'age' => 'required|string|max:3',
                'address' => 'required|string|max:500',
                'high_school' => 'required|string|max:255',
                'year_of_completion' => 'required|string|max:4',
                'qualification' => 'required|string|max:255',
                'year_obtained' => 'required|string|max:4',
                'institution' => 'required|string|max:255',
                'app_type' => 'required|string|max:50',
                'field' => 'required|string|max:100',
                'status' => 'required|string|in:pending,approved,rejected',
                'cv_file' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
                'id_copy_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'qualification_copy_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            // Handle file uploads
            $documentPaths = [
                'cv_path' => null,
                'id_copy_path' => null,
                'qualification_copy_path' => null
            ];

            // Upload CV
            if ($request->hasFile('cv_file')) {
                $cvPath = $request->file('cv_file')->store('internships/documents', 'public');
                $documentPaths['cv_path'] = $cvPath;
            }

            // Upload ID Copy
            if ($request->hasFile('id_copy_file')) {
                $idCopyPath = $request->file('id_copy_file')->store('internships/documents', 'public');
                $documentPaths['id_copy_path'] = $idCopyPath;
            }

            // Upload Qualification Copy
            if ($request->hasFile('qualification_copy_file')) {
                $qualificationCopyPath = $request->file('qualification_copy_file')->store('internships/documents', 'public');
                $documentPaths['qualification_copy_path'] = $qualificationCopyPath;
            }

            // Prepare data for insertion
            $internshipData = array_merge([
                'app_id' => $app_id,
                'user_id' => auth()->id(),
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'id_no' => $validatedData['id_no'],
                'age' => $validatedData['age'],
                'address' => $validatedData['address'],
                'high_school' => $validatedData['high_school'],
                'year_of_completion' => $validatedData['year_of_completion'],
                'qualification' => $validatedData['qualification'],
                'year_obtained' => $validatedData['year_obtained'],
                'institution' => $validatedData['institution'],
                'app_type' => $validatedData['app_type'],
                'field' => $validatedData['field'],
                'status' => $validatedData['status'],
            ], $documentPaths);

            InternshipApplication::create($internshipData);
            
            return response()->json([
                'success' => true,
                'message' => 'Internship application created successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating internship: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manually add internship to the database - for direct database insertion
     * Usage: Call this method or use the examples given below
     */
    public function addInternshipManually()
    {
        // Example 1: Create a new internship application
        $internship = new InternshipApplication();
        $internship->app_id = 'APP_' . mt_rand(100000, 999999);
        $internship->user_id = 1; // Replace with actual user ID
        $internship->id_no = '9901234567890';
        $internship->age = '25';
        $internship->address = '123 Main Street, Cape Town';
        $internship->high_school = 'Springfield High School';
        $internship->year_of_completion = '2017';
        $internship->qualification = 'Bachelor Computer Science';
        $internship->year_obtained = '2021';
        $internship->institution = 'University of Cape Town';
        $internship->app_type = 'Full Time';
        $internship->field = 'Software Development';
        $internship->cv_path = 'documents/cv_sample.pdf';
        $internship->id_copy_path = 'documents/id_copy_sample.pdf';
        $internship->qualification_copy_path = 'documents/certificate_sample.pdf';
        $internship->save();

        return "Internship application created successfully!";
    }

    public function editInternship($id)
    {
        $internship = InternshipApplication::findOrFail($id);
        return view('admin.internships.edit', compact('internship'));
    }

    public function updateInternship(Request $request, $id)
    {
        try {
            $internship = InternshipApplication::findOrFail($id);
            
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'id_no' => 'required|string|max:20',
                'age' => 'required|string|max:3',
                'address' => 'required|string|max:500',
                'high_school' => 'required|string|max:255',
                'year_of_completion' => 'required|string|max:4',
                'qualification' => 'required|string|max:255',
                'year_obtained' => 'required|string|max:4',
                'institution' => 'required|string|max:255',
                'app_type' => 'required|string|max:50',
                'field' => 'required|string|max:100',
                'status' => 'required|string|in:pending,approved,rejected',
                'cv_file' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
                'id_copy_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'qualification_copy_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            // Handle file uploads (only update if new files are uploaded)
            $documentPaths = [];

            // Upload CV if provided
            if ($request->hasFile('cv_file')) {
                $cvPath = $request->file('cv_file')->store('internships/documents', 'public');
                $documentPaths['cv_path'] = $cvPath;
            }

            // Upload ID Copy if provided
            if ($request->hasFile('id_copy_file')) {
                $idCopyPath = $request->file('id_copy_file')->store('internships/documents', 'public');
                $documentPaths['id_copy_path'] = $idCopyPath;
            }

            // Upload Qualification Copy if provided
            if ($request->hasFile('qualification_copy_file')) {
                $qualificationCopyPath = $request->file('qualification_copy_file')->store('internships/documents', 'public');
                $documentPaths['qualification_copy_path'] = $qualificationCopyPath;
            }

            // Prepare data for update
            $updateData = array_merge([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'id_no' => $validatedData['id_no'],
                'age' => $validatedData['age'],
                'address' => $validatedData['address'],
                'high_school' => $validatedData['high_school'],
                'year_of_completion' => $validatedData['year_of_completion'],
                'qualification' => $validatedData['qualification'],
                'year_obtained' => $validatedData['year_obtained'],
                'institution' => $validatedData['institution'],
                'app_type' => $validatedData['app_type'],
                'field' => $validatedData['field'],
                'status' => $validatedData['status'],
            ], $documentPaths);

            $internship->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Internship application updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating internship: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteInternship($id)
    {
        $internship = InternshipApplication::findOrFail($id);
        $internship->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Internship application deleted successfully.'
        ]);
    }

    public function deleteSelectedInternships(Request $request)
    {
        try {
            $selectedIds = $request->input('selected_ids');
            if (is_string($selectedIds)) {
                $selectedIds = json_decode($selectedIds);
            }
            
            InternshipApplication::whereIn('id', $selectedIds)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Selected internship applications deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting internships: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteSelectedServices(Request $request)
    {
        try {
            $selectedIds = json_decode($request->input('selected_ids'));
            
            if (empty($selectedIds)) {
                return redirect()->back()->with('warning', 'No services selected for deletion.');
            }
            
            $count = Service::whereIn('id', $selectedIds)->count();
            Service::whereIn('id', $selectedIds)->delete();
            
            return redirect()->route('dashboard.services')
                ->with('success', $count . ' service(s) deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('dashboard.services')
                ->with('error', 'Failed to delete services: ' . $e->getMessage());
        }
    }

    // ==================== INTERNS & LEARNERS CRUD ====================

    public function internsLearners()
    {
        try {
            $internsLearners = InternsLearner::with(['program', 'person'])->orderBy('created_at', 'desc')->paginate(10);
        } catch (\Exception $e) {
            // If table doesn't exist, create empty collection
            $internsLearners = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        }
        $programs = InternshipProgram::all();
        return view('admin.dashboard.interns-learners.index', compact('internsLearners', 'programs'));
    }

    public function createInternLearner()
    {
        $programs = InternshipProgram::all();
        try {
            $applications = InternshipApplication::whereDoesntHave('internLearner')->get();
        } catch (\Exception $e) {
            // If table doesn't exist yet, just get all applications
            $applications = InternshipApplication::all();
        }
        return view('admin.dashboard.interns-learners.create', compact('programs', 'applications'));
    }

    public function storeInternLearner(Request $request)
    {
        $validatedData = $request->validate([
            'person_id' => 'nullable|exists:people,id',
            'program_id' => 'nullable|exists:internship_programs,id',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'id_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'required|in:active,completed,terminated',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        InternsLearner::create($validatedData);

        return redirect()->route('dashboard.interns-learners')->with('success', 'Intern/Learner created successfully.');
    }

    public function viewInternLearner($id)
    {
        // mict_beneficiary is a computed attribute, so we only eager load real relationships
        $internLearner = InternsLearner::with(['program', 'person'])->findOrFail($id);
        return view('admin.dashboard.interns-learners.view', compact('internLearner'));
    }

    public function editInternLearner($id)
    {
        $internLearner = InternsLearner::findOrFail($id);
        $programs = InternshipProgram::all();
        try {
            $applications = InternshipApplication::whereDoesntHave('internLearner')->orWhere('id', $internLearner->person_id)->get();
        } catch (\Exception $e) {
            // If table doesn't exist yet, just get all applications
            $applications = InternshipApplication::all();
        }
        
        // Load MICT beneficiary if exists
        $mictBeneficiary = null;
        if ($internLearner->program_id && $internLearner->email) {
            $mictBeneficiary = MictBeneficiary::where('email_address', $internLearner->email)
                                              ->where('program_id', $internLearner->program_id)
                                              ->first();
        }
        
        return view('admin.dashboard.interns-learners.edit', compact('internLearner', 'programs', 'applications', 'mictBeneficiary'));
    }

    public function updateInternLearner(Request $request, $id)
    {
        $internLearner = InternsLearner::findOrFail($id);

        $validatedData = $request->validate([
            'person_id' => 'nullable|exists:people,id',
            'program_id' => 'nullable|exists:internship_programs,id',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'id_number' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'required|in:active,completed,terminated',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        $internLearner->update($validatedData);

        // Update MICT beneficiary if exists and MICT data is provided
        if ($request->has('mict_beneficiary_id') && $request->mict_beneficiary_id) {
            $mictBeneficiary = MictBeneficiary::findOrFail($request->mict_beneficiary_id);
            
            // Validate and update MICT beneficiary fields
            $mictData = $request->validate([
                // Learner Details
                'learner_title' => 'nullable|string|max:20',
                'maiden_name' => 'nullable|string|max:255',
                'type_of_id' => 'nullable|string|max:50',
                'residence_status' => 'nullable|string|max:50',
                'marital_status' => 'nullable|string|max:50',
                'race' => 'nullable|string|max:50',
                'disabled' => 'nullable|boolean',
                'type_of_disability' => 'nullable|string|max:255',
                'age' => 'nullable|integer',
                'sa_citizen' => 'nullable|boolean',
                'nationality' => 'nullable|string|max:100',
                'first_language' => 'nullable|string|max:100',
                'employed' => 'nullable|boolean',
                'length_of_unemployment_years' => 'nullable|integer',
                'employment_start_date' => 'nullable|date',
                'learner_agreement_start_date' => 'nullable|date',
                'learner_agreement_end_date' => 'nullable|date',
                'program_start_date' => 'nullable|date',
                'amount_allocated' => 'nullable|numeric',
                'previous_internship' => 'nullable|boolean',
                'year_of_study' => 'nullable|string|max:50',
                // Address
                'physical_address_1' => 'nullable|string|max:255',
                'physical_address_2' => 'nullable|string|max:255',
                'physical_address_3' => 'nullable|string|max:255',
                'physical_postal_code' => 'nullable|string|max:20',
                'postal_address_1' => 'nullable|string|max:255',
                'postal_address_2' => 'nullable|string|max:255',
                'postal_address_3' => 'nullable|string|max:255',
                'postal_address_postal_code' => 'nullable|string|max:20',
                'type_of_area' => 'nullable|string|max:50',
                'cellphone' => 'nullable|string|max:20',
                'telephone' => 'nullable|string|max:20',
                'fax' => 'nullable|string|max:20',
                // Qualification
                'highest_nqf_qualification' => 'nullable|string|max:50',
                'other_qualification' => 'nullable|string|max:255',
                'title_of_highest_qualification' => 'nullable|string|max:255',
                'has_matriculated' => 'nullable|boolean',
                'matriculated_in_sa' => 'nullable|boolean',
                'province_of_high_school' => 'nullable|string|max:100',
                'year_of_national_senior_certificate' => 'nullable|string|max:10',
                // Guardian
                'guardian_first_name' => 'nullable|string|max:255',
                'guardian_last_name' => 'nullable|string|max:255',
                'guardian_type_of_id' => 'nullable|string|max:50',
                'guardian_id_number' => 'nullable|string|max:20',
                'guardian_telephone' => 'nullable|string|max:20',
                'guardian_cellphone' => 'nullable|string|max:20',
                'guardian_home_address' => 'nullable|string',
                'guardian_postal_address' => 'nullable|string',
                'guardian_email_address' => 'nullable|email|max:255',
            ]);
            
            $mictBeneficiary->update($mictData);
        }

        return redirect()->route('dashboard.interns-learners')->with('success', 'Intern/Learner updated successfully.');
    }

    public function deleteInternLearner($id)
    {
        $internLearner = InternsLearner::findOrFail($id);
        $internLearner->delete();
        
        return redirect()->route('dashboard.interns-learners')->with('success', 'Intern/Learner deleted successfully.');
    }

    public function deleteSelectedInternsLearners(Request $request)
    {
        $selectedIds = json_decode($request->input('selected_ids'));
        InternsLearner::whereIn('id', $selectedIds)->delete();
        
        return redirect()->back()->with('success', 'Selected interns/learners deleted successfully.');
    }

    // ==================== PROGRAMS CRUD ====================

    public function programs()
    {
        $user = auth()->user();
        if (! $user || ! $user->hasAnyStaffPermission(['programs.read', 'programs.create', 'programs.update', 'programs.approve'])) {
            abort(403);
        }

        $programs = InternshipProgram::with('partner')->orderBy('created_at', 'desc')->paginate(10);
        return view('admin.dashboard.programs.index', compact('programs'));
    }

    public function createProgram()
    {
        $user = auth()->user();
        if (! $user || ! $user->hasStaffPermission('programs.create')) {
            abort(403);
        }

        $partners = Partner::forPrograms()->get();
        return view('admin.dashboard.programs.create', compact('partners'));
    }

    public function storeProgram(Request $request, ProgramAnnouncementService $announcementService)
    {
        $user = auth()->user();
        if (! $user || ! $user->hasStaffPermission('programs.create')) {
            abort(403);
        }

        $canApprove = $user->hasStaffPermission('programs.approve');

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'program_type' => 'required|string|in:Internship,TVET Placement,Short Program',
            'partner_id' => 'nullable|exists:partners,id',
            'description' => 'required|string',
            'duration' => 'required|string|max:100',
            'recruitment_start_date' => 'required|date',
            'recruitment_end_date' => 'required|date|after:recruitment_start_date',
            'number_needed' => 'required|integer|min:1',
            'has_stipend' => 'boolean',
            'stipend_amount' => 'nullable|numeric|min:0',
            'stipend_currency' => 'required_with:stipend_amount|string|max:3',
            'has_accreditation' => 'boolean',
            'accreditation_details' => 'nullable|string',
            'youth_beneficiaries' => 'boolean',
            'requirements' => 'required|string',
            'is_active' => 'boolean',
            'allows_enquiry' => 'boolean',
            'create_announcement' => 'boolean',
            'announcement_title' => 'exclude_unless:create_announcement,1|required_if:create_announcement,1|string|max:255',
            'announcement_description' => 'exclude_unless:create_announcement,1|required_if:create_announcement,1|string',
            'announcement_link' => 'exclude_unless:create_announcement,1|nullable|url|max:255',
            'announcement_badge' => 'exclude_unless:create_announcement,1|nullable|string|max:50',
            'announcement_expires_at' => 'exclude_unless:create_announcement,1|nullable|date',
            'form_fields' => 'nullable|array',
            'form_fields.*' => 'string',
        ]);

        $programData = [
            'name' => $validatedData['name'],
            'program_type' => $validatedData['program_type'],
            'partner_id' => $validatedData['partner_id'] ?? null,
            'description' => $validatedData['description'],
            'duration' => $validatedData['duration'],
            'recruitment_start_date' => $validatedData['recruitment_start_date'],
            'recruitment_end_date' => $validatedData['recruitment_end_date'],
            'number_needed' => $validatedData['number_needed'],
            'has_stipend' => $validatedData['has_stipend'] ?? false,
            'stipend_amount' => $validatedData['stipend_amount'],
            'stipend_currency' => $validatedData['stipend_currency'] ?? 'ZAR',
            'has_accreditation' => $validatedData['has_accreditation'] ?? false,
            'accreditation_details' => $validatedData['accreditation_details'],
            'youth_beneficiaries' => $validatedData['youth_beneficiaries'] ?? false,
            'requirements' => $validatedData['requirements'],
            'is_active' => $canApprove ? ($validatedData['is_active'] ?? false) : false,
            'allows_enquiry' => $validatedData['allows_enquiry'] ?? false,
            'application_form_schema' => ProgramFormFields::normalizeSelected(
                $request->input('form_fields'),
                $validatedData['program_type'],
                $request->boolean('allows_enquiry')
            ),
        ];

        $program = InternshipProgram::create($programData);

        $message = $canApprove && ($programData['is_active'] ?? false)
            ? 'Program created successfully.'
            : 'Program saved as draft. A superadmin must approve it before it is shown publicly.';

        if (SiteSetting::current()->lmis_enabled) {
            try {
                $lmisStatus = app(LmisProgramSyncService::class)->queueProgram($program);
                if ($lmisStatus === LmisProgramSyncService::RESULT_SYNCED) {
                    $message .= ' Pushed to LMIS.';
                } elseif ($lmisStatus === LmisProgramSyncService::RESULT_PENDING) {
                    $message .= ' LMIS is offline; the programme will be pushed when it is reachable.';
                }
            } catch (\Throwable $e) {
                $message .= ' LMIS sync could not be queued; it can be retried later.';
            }
        }

        if ($request->boolean('create_announcement') && $canApprove) {
            $announcement = $announcementService->createForProgram($program, [
                'title' => $validatedData['announcement_title'],
                'description' => $validatedData['announcement_description'],
                'link' => $validatedData['announcement_link'] ?? route('programs'),
                'badge' => $validatedData['announcement_badge'] ?? 'OPPORTUNITY',
                'expires_at' => $validatedData['announcement_expires_at'] ?? null,
            ]);
            $message .= ' Announcement published — <a href="' . route('admin.dashboard.announcements.edit', $announcement->id) . '" class="underline">edit announcement</a>.';
        }

        return redirect()->route('dashboard.programs')->with('success', $message);
    }

    public function viewProgram($id)
    {
        $program = InternshipProgram::with('partner')->findOrFail($id);
        return view('admin.dashboard.programs.view', compact('program'));
    }

    public function editProgram($id)
    {
        $user = auth()->user();
        if (! $user || ! $user->hasStaffPermission('programs.update')) {
            abort(403);
        }

        $program = InternshipProgram::with('announcement')->findOrFail($id);
        $partners = Partner::forPrograms()->get();
        return view('admin.dashboard.programs.edit', compact('program', 'partners'));
    }

    public function updateProgram(Request $request, $id, ProgramAnnouncementService $announcementService)
    {
        $user = auth()->user();
        if (! $user || ! $user->hasStaffPermission('programs.update')) {
            abort(403);
        }

        $canApprove = $user->hasStaffPermission('programs.approve');
        $program = InternshipProgram::with('announcement')->findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'program_type' => 'required|string|in:Internship,TVET Placement,Short Program',
            'partner_id' => 'nullable|exists:partners,id',
            'description' => 'required|string',
            'duration' => 'required|string|max:100',
            'recruitment_start_date' => 'required|date',
            'recruitment_end_date' => 'required|date|after:recruitment_start_date',
            'number_needed' => 'required|integer|min:1',
            'has_stipend' => 'boolean',
            'stipend_amount' => 'nullable|numeric|min:0',
            'stipend_currency' => 'required_with:stipend_amount|string|max:3',
            'has_accreditation' => 'boolean',
            'accreditation_details' => 'nullable|string',
            'youth_beneficiaries' => 'boolean',
            'requirements' => 'required|string',
            'is_active' => 'boolean',
            'allows_enquiry' => 'boolean',
            'create_announcement' => 'boolean',
            'announcement_title' => 'exclude_unless:create_announcement,1|required_if:create_announcement,1|string|max:255',
            'announcement_description' => 'exclude_unless:create_announcement,1|required_if:create_announcement,1|string',
            'announcement_link' => 'exclude_unless:create_announcement,1|nullable|url|max:255',
            'announcement_badge' => 'exclude_unless:create_announcement,1|nullable|string|max:50',
            'announcement_expires_at' => 'exclude_unless:create_announcement,1|nullable|date',
            'form_fields' => 'nullable|array',
            'form_fields.*' => 'string',
        ]);

        $publishAnnouncement = $request->boolean('create_announcement');
        $announcementPayload = [
            'title' => $validatedData['announcement_title'] ?? null,
            'description' => $validatedData['announcement_description'] ?? null,
            'link' => $validatedData['announcement_link'] ?? route('programs'),
            'badge' => $validatedData['announcement_badge'] ?? 'OPPORTUNITY',
            'expires_at' => $validatedData['announcement_expires_at'] ?? null,
        ];

        unset(
            $validatedData['form_fields'],
            $validatedData['create_announcement'],
            $validatedData['announcement_title'],
            $validatedData['announcement_description'],
            $validatedData['announcement_link'],
            $validatedData['announcement_badge'],
            $validatedData['announcement_expires_at']
        );

        $program->update([
            ...$validatedData,
            'has_stipend' => $validatedData['has_stipend'] ?? false,
            'has_accreditation' => $validatedData['has_accreditation'] ?? false,
            'youth_beneficiaries' => $validatedData['youth_beneficiaries'] ?? false,
            'is_active' => $canApprove
                ? ($validatedData['is_active'] ?? false)
                : (bool) $program->is_active,
            'allows_enquiry' => $validatedData['allows_enquiry'] ?? false,
            'application_form_schema' => ProgramFormFields::normalizeSelected(
                $request->input('form_fields'),
                $validatedData['program_type'],
                $request->boolean('allows_enquiry')
            ),
        ]);

        $message = 'Program updated successfully.';
        if (! $canApprove) {
            $message .= ' Public visibility was not changed (approval required).';
        }

        if ($publishAnnouncement && $canApprove) {
            $announcement = $announcementService->updateForProgram($program, $announcementPayload);
            $message .= ' Announcement updated — <a href="' . route('admin.dashboard.announcements.edit', $announcement->id) . '" class="underline">edit announcement</a>.';
        } elseif ($canApprove) {
            $announcementService->unpublishForProgram($program);
            if ($program->announcement_id) {
                $message .= ' Announcement unpublished from the homepage and announcements page.';
            }
        }

        return redirect()->route('dashboard.programs')->with('success', $message);
    }

    public function toggleProgramEnquiry($id)
    {
        $program = InternshipProgram::findOrFail($id);
        $program->allows_enquiry = ! $program->allows_enquiry;
        $program->save();

        return response()->json([
            'success' => true,
            'allows_enquiry' => $program->allows_enquiry,
            'message' => $program->allows_enquiry
                ? 'Program is now collecting interest on the public Programmes page.'
                : 'Program is marked as actively running on the public Programmes page.',
        ]);
    }

    public function deleteProgram($id)
    {
        $user = auth()->user();
        if (! $user || ! $user->hasStaffPermission('programs.delete')) {
            abort(403);
        }

        $program = InternshipProgram::findOrFail($id);
        $program->delete();
        
        return redirect()->route('dashboard.programs')->with('success', 'Program deleted successfully.');
    }

    public function deleteSelectedPrograms(Request $request)
    {
        $request->merge(['action' => 'delete']);

        return $this->bulkPrograms($request);
    }

    public function bulkPrograms(Request $request, ?ProgramAnnouncementService $announcementService = null)
    {
        $announcementService ??= app(ProgramAnnouncementService::class);

        $ids = $request->input('selected_ids', $request->input('ids', []));
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            $ids = is_array($decoded) ? $decoded : [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $ids))));

        $request->merge(['ids' => $ids]);
        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:internship_programs,id',
        ]);

        $count = count($validated['ids']);

        if ($validated['action'] === 'activate') {
            $user = auth()->user();
            if (! $user || ! $user->hasStaffPermission('programs.approve')) {
                abort(403, 'Only users with program approval permission can activate programs.');
            }

            InternshipProgram::whereIn('id', $validated['ids'])->update(['is_active' => true]);

            return redirect()->route('dashboard.programs')
                ->with('success', $count === 1 ? '1 program activated.' : $count.' programs activated.');
        }

        if ($validated['action'] === 'deactivate') {
            $programs = InternshipProgram::with('announcement')->whereIn('id', $validated['ids'])->get();
            InternshipProgram::whereIn('id', $validated['ids'])->update(['is_active' => false]);
            foreach ($programs as $program) {
                $announcementService->unpublishForProgram($program);
            }

            return redirect()->route('dashboard.programs')
                ->with('success', $count === 1 ? '1 program set to inactive.' : $count.' programs set to inactive.');
        }

        InternshipProgram::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('dashboard.programs')
            ->with('success', $count === 1 ? '1 program deleted.' : $count.' programs deleted.');
    }

    public function settings()
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        // Personal settings shortcut for staff; site settings only for site-settings.manage / admin.
        if (! $user->hasStaffPermission('site-settings.manage') && $user->hasStaffPermission('settings.own')) {
            return redirect()->route('dashboard.profile');
        }

        if (! $user->hasStaffPermission('site-settings.manage')) {
            abort(403);
        }

        $pageTitle = 'Settings';
        $siteSettings = SiteSetting::current();

        return view('admin.dashboard.settings.index', compact('pageTitle', 'user', 'siteSettings'));
    }

    public function updateSettings(Request $request)
    {
        $user = auth()->user();
        if (! $user || ! $user->hasStaffPermission('site-settings.manage')) {
            abort(403);
        }

        $settings = SiteSetting::current();
        $section = $request->input('section', 'floating');

        if ($section === 'lmis') {
            $validated = $request->validate([
                'lmis_enabled' => 'nullable|boolean',
                'lmis_base_url' => 'nullable|url|max:255',
                'lmis_api_token' => 'nullable|string|max:2000',
            ]);

            $enabled = $request->boolean('lmis_enabled');
            $baseUrl = rtrim((string) ($validated['lmis_base_url'] ?? ''), '/');

            if ($enabled && $baseUrl === '') {
                throw ValidationException::withMessages([
                    'lmis_base_url' => 'A base URL is required when LMIS sync is enabled.',
                ]);
            }

            $settings->lmis_enabled = $enabled;
            $settings->lmis_base_url = $baseUrl !== '' ? $baseUrl : 'http://localhost:3010';

            $token = $validated['lmis_api_token'] ?? '';
            if ($token !== '') {
                $settings->lmis_api_token = $token;
            }

            $settings->save();

            return redirect()->route('dashboard.settings')->with('success', 'LMIS connection saved.');
        }

        $digits = preg_replace('/\D+/', '', (string) $request->input('whatsapp_e164', '')) ?? '';
        if ($digits !== '' && (strlen($digits) < 10 || strlen($digits) > 15)) {
            throw ValidationException::withMessages([
                'whatsapp_e164' => 'Enter a WhatsApp number with 10 to 15 digits, or leave it blank.',
            ]);
        }

        $settings->show_whatsapp_floating = $request->boolean('show_whatsapp_floating');
        $settings->show_chatbot_floating = $request->boolean('show_chatbot_floating');
        $settings->whatsapp_e164 = $digits !== '' ? $digits : null;
        $settings->save();

        return redirect()->route('dashboard.settings')->with('success', 'Website contact settings updated.');
    }

    public function testLmisConnection(LmisClient $client)
    {
        try {
            $client->health();
        } catch (LmisRequestException $e) {
            return redirect()->route('dashboard.settings')->with('error', $e->getMessage());
        }

        return redirect()->route('dashboard.settings')->with('success', 'LMIS connection succeeded.');
    }

    // ==================== PARTNERS CRUD ====================

    public function partners()
    {
        $partners = Partner::ordered()->paginate(10);
        return view('admin.dashboard.partners.index', compact('partners'));
    }

    public function createPartner()
    {
        return view('admin.dashboard.partners.create');
    }

    public function storePartner(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'partner_type' => 'nullable|string|in:Skills Development,JV,Sponsor,Other',
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'website_url' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'mou_signed' => 'boolean',
            'mou_date' => 'nullable|date',
            'mou_document' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $partnerData = [
            'name' => $validatedData['name'],
            'partner_type' => $validatedData['partner_type'] ?? null,
            'website_url' => $validatedData['website_url'] ?? null,
            'description' => $validatedData['description'] ?? null,
            'display_order' => $validatedData['display_order'] ?? 0,
            'is_active' => $validatedData['is_active'] ?? true,
            'mou_signed' => $request->boolean('mou_signed'),
            'mou_date' => $validatedData['mou_date'] ?? null,
        ];

        // Handle logo upload - store directly in public/images/partners
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');

            $extension = $file->getClientOriginalExtension();
            $safeName = \Illuminate\Support\Str::slug($validatedData['name'] ?: 'partner');
            $filename = $safeName . '.' . $extension;

            $destination = public_path('images/partners');
            if (!is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $file->move($destination, $filename);

            $partnerData['logo_path'] = 'images/partners/' . $filename;
        }

        // Handle MOU document upload
        if ($request->hasFile('mou_document')) {
            $mouFile = $request->file('mou_document');
            $safeName = \Illuminate\Support\Str::slug($validatedData['name'] ?: 'partner');
            $mouFilename = 'MOU_' . $safeName . '_' . date('Ymd') . '.pdf';
            $mouDest = public_path('documents/mou');
            if (!is_dir($mouDest)) {
                mkdir($mouDest, 0755, true);
            }
            $mouFile->move($mouDest, $mouFilename);
            $partnerData['mou_document'] = 'documents/mou/' . $mouFilename;
        }

        Partner::create($partnerData);

        return redirect()->route('dashboard.partners')->with('success', 'Partner created successfully.');
    }

    public function viewPartner($id)
    {
        $partner = Partner::findOrFail($id);
        return view('admin.dashboard.partners.view', compact('partner'));
    }

    public function editPartner($id)
    {
        $partner = Partner::findOrFail($id);
        return view('admin.dashboard.partners.edit', compact('partner'));
    }

    public function updatePartner(Request $request, $id)
    {
        $partner = Partner::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'partner_type' => 'nullable|string|in:Skills Development,JV,Sponsor,Other',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'website_url' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'mou_signed' => 'boolean',
            'mou_date' => 'nullable|date',
            'mou_document' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $partnerData = [
            'name' => $validatedData['name'],
            'partner_type' => $validatedData['partner_type'] ?? null,
            'website_url' => $validatedData['website_url'] ?? null,
            'description' => $validatedData['description'] ?? null,
            'display_order' => $validatedData['display_order'] ?? 0,
            'is_active' => $validatedData['is_active'] ?? true,
            'mou_signed' => $request->boolean('mou_signed'),
            'mou_date' => $validatedData['mou_date'] ?? null,
        ];

        // Handle logo upload - store directly in public/images/partners
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');

            // Delete old logo if exists
            if ($partner->logo_path) {
                $oldPath = public_path($partner->logo_path);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $extension = $file->getClientOriginalExtension();
            $safeName = \Illuminate\Support\Str::slug($validatedData['name'] ?: $partner->name ?: 'partner');
            $filename = $safeName . '.' . $extension;

            $destination = public_path('images/partners');
            if (!is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $file->move($destination, $filename);

            $partnerData['logo_path'] = 'images/partners/' . $filename;
        }

        // Handle MOU document upload
        if ($request->hasFile('mou_document')) {
            if ($partner->mou_document) {
                @unlink(public_path($partner->mou_document));
            }
            $mouFile = $request->file('mou_document');
            $safeName = \Illuminate\Support\Str::slug($validatedData['name'] ?: $partner->name ?: 'partner');
            $mouFilename = 'MOU_' . $safeName . '_' . date('Ymd') . '.pdf';
            $mouDest = public_path('documents/mou');
            if (!is_dir($mouDest)) {
                mkdir($mouDest, 0755, true);
            }
            $mouFile->move($mouDest, $mouFilename);
            $partnerData['mou_document'] = 'documents/mou/' . $mouFilename;
        }

        $partner->update($partnerData);

        return redirect()->route('dashboard.partners')->with('success', 'Partner updated successfully.');
    }

    public function deletePartner($id)
    {
        $partner = Partner::findOrFail($id);
        
        // Delete logo if exists
        if ($partner->logo_path) {
            Storage::disk('public')->delete($partner->logo_path);
        }
        
        $partner->delete();
        
        return redirect()->route('dashboard.partners')->with('success', 'Partner deleted successfully.');
    }

    public function deleteSelectedPartners(Request $request)
    {
        $selectedIds = json_decode($request->input('selected_ids'));
        
        foreach ($selectedIds as $id) {
            $partner = Partner::find($id);
            if ($partner) {
                // Delete logo if exists
                if ($partner->logo_path) {
                    Storage::disk('public')->delete($partner->logo_path);
                }
                $partner->delete();
            }
        }
        
        return redirect()->back()->with('success', 'Selected partners deleted successfully.');
    }

    // ==================== APPLICATION ACCEPTANCE/REJECTION ====================

    /**
     * Accept an application and send email to applicant
     */
    public function acceptApplication(Request $request, $id)
    {
        try {
            $request->validate([
                'admin_message' => 'required|string|max:1000',
            ]);

            $application = InternshipApplication::findOrFail($id);
            $user = auth()->user();

            // Update application
            $application->status = 'accepted';
            $application->admin_message = $request->admin_message;
            $application->responded_by = $user->id;
            $application->responded_at = now();
            $application->save();

            // Send acceptance email
            Mail::to($application->email)->send(new ApplicationAccepted($application, $request->admin_message));

            return redirect()->back()->with('success', 'Application accepted successfully and email has been sent to the applicant.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error accepting application: ' . $e->getMessage());
        }
    }

    /**
     * Reject an application and send email to applicant
     */
    public function rejectApplication(Request $request, $id)
    {
        try {
            $request->validate([
                'admin_message' => 'required|string|max:1000',
            ]);

            $application = InternshipApplication::findOrFail($id);
            $user = auth()->user();

            // Update application
            $application->status = 'rejected';
            $application->admin_message = $request->admin_message;
            $application->responded_by = $user->id;
            $application->responded_at = now();
            $application->save();

            // Send rejection email
            Mail::to($application->email)->send(new ApplicationRejected($application, $request->admin_message));

            return redirect()->back()->with('success', 'Application rejected and email has been sent to the applicant.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error rejecting application: ' . $e->getMessage());
        }
    }
}
