<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserListController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->query('per_page', 5);

        if (!in_array($perPage, [5, 10, 25, 50], true)) {
            $perPage = 5;
        }

        $users = $this->usersQuery($request)
            ->paginate($perPage)
            ->withQueryString();

        $counts = [
            'all' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
        ];

        $search = (string) $request->query('search', '');
        $filterRole = (string) $request->query('role', '');
        $status = (string) $request->query('status', '');
        $sort = (string) $request->query('sort', 'created_at');
        $direction = (string) $request->query('direction', 'desc');

        return view('admin.users.index', compact(
            'users',
            'counts',
            'search',
            'filterRole',
            'status',
            'sort',
            'direction'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->usersQuery($request);

        return response()->streamDownload(function () use ($query) {
            $file = fopen('php://output', 'w');

            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'Name', 'Email', 'Phone', 'Company',
                'City', 'Role', 'Status', 'Joined'
            ]);

            foreach ($query->lazy(200) as $user) {
                fputcsv($file, [
                    $this->csvSafe($user->name),
                    $this->csvSafe($user->email),
                    $this->csvSafe($user->phone),
                    $this->csvSafe($user->providerProfile?->company_name),
                    $this->csvSafe($user->city),
                    $this->csvSafe($user->role),
                    $this->csvSafe($user->status),
                    $user->created_at?->format('Y-m-d'),
                ]);
            }

            fclose($file);
        }, 'space-users-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = fopen($request->file('file')->getRealPath(), 'r');

        if ($file === false) {
            return back()->withErrors(['file' => 'Unable to read CSV file.']);
        }

        $rows = [];
        $seenEmails = [];

        try {
            $header = fgetcsv($file, 0, ',', '"', '');

            if (!$header) {
                return back()->withErrors(['file' => 'CSV file is empty.']);
            }

            $header = array_map(function ($column) {
                return strtolower(trim(
                    preg_replace('/^\xEF\xBB\xBF/', '', (string) $column)
                ));
            }, $header);

            $indexes = array_flip($header);

            if (!isset($indexes['name'], $indexes['email'])) {
                return back()->withErrors([
                    'file' => 'CSV must contain name and email columns.',
                ]);
            }

            $rowNumber = 1;

            while (($line = fgetcsv($file, 0, ',', '"', '')) !== false) {
                $rowNumber++;

                $get = function (string $field) use ($line, $indexes): string {
                    $column = $indexes[$field] ?? null;

                    return $column === null
                        ? ''
                        : trim((string) ($line[$column] ?? ''));
                };

                $data = [
                    'name' => $get('name'),
                    'email' => $get('email'),
                    'phone' => $get('phone'),
                    'city' => $get('city'),
                ];

                if ($data['name'] === '' && $data['email'] === '') {
                    continue;
                }

                $validator = Validator::make($data, [
                    'name' => ['required', 'string', 'max:255'],
                    'email' => [
                        'required',
                        'email',
                        'max:255',
                        Rule::unique('users', 'email'),
                    ],
                    'phone' => ['nullable', 'string', 'max:25'],
                    'city' => ['nullable', 'string', 'max:100'],
                ]);

                if ($validator->fails()) {
                    return back()->withErrors([
                        'file' => "Row {$rowNumber}: " . $validator->errors()->first(),
                    ]);
                }

                $email = mb_strtolower($data['email']);

                if (isset($seenEmails[$email])) {
                    return back()->withErrors([
                        'file' => "Duplicate email on row {$rowNumber}.",
                    ]);
                }

                $seenEmails[$email] = true;
                $rows[] = $data;

                if (count($rows) > 200) {
                    return back()->withErrors([
                        'file' => 'Maximum 200 users per import.',
                    ]);
                }
            }
        } finally {
            fclose($file);
        }

        if (count($rows) === 0) {
            return back()->withErrors(['file' => 'No users found in CSV.']);
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                User::create([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'phone' => $row['phone'] ?: null,
                    'city' => $row['city'] ?: null,
                    'role' => 'customer',
                    'status' => 'active',
                    'password' => Str::random(64),
                ]);
            }
        });

        return redirect()->route('admin.users.index')
            ->with('success', count($rows) . ' customer accounts imported. Password resets are required before login.');
    }

    private function usersQuery(Request $request): Builder
    {
        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', '');
        $status = (string) $request->query('status', '');
        $sort = (string) $request->query('sort', 'created_at');
        $direction = (string) $request->query('direction', 'desc');

        if (!in_array($sort, [
            'name', 'phone', 'company', 'city',
            'role', 'status', 'created_at'
        ], true)) {
            $sort = 'created_at';
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query = User::query()->with('providerProfile');

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('users.phone', 'like', "%{$search}%")
                    ->orWhereHas('providerProfile', function ($profile) use ($search) {
                        $profile->where('company_name', 'like', "%{$search}%");
                    });
            });
        }

        if (in_array($role, ['admin', 'provider', 'customer'], true)) {
            $query->where('users.role', $role);
        }

        if (in_array($status, ['active', 'suspended'], true)) {
            $query->where('users.status', $status);
        }

        if ($sort === 'company') {
            $query->orderBy(
                ProviderProfile::query()
                    ->select('company_name')
                    ->whereColumn('provider_profiles.user_id', 'users.id')
                    ->limit(1),
                $direction
            );
        } else {
            $query->orderBy("users.$sort", $direction);
        }

        return $query->orderBy('users.id', 'desc');
    }

    private function csvSafe(?string $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^\s*[=+\-@]/u', $value)
            ? "'" . $value
            : $value;
    }
}
