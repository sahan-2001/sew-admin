<x-filament::page>
    {{-- Employee Details Section --}}
    <x-filament::card class="mb-6">
        <h2 class="text-lg font-bold mb-3">Employee Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4"><br>
            <p><strong>Employee Code:</strong> {{ $record->employee_code }}</p>
            <p><strong>Full Name:</strong> {{ $record->full_name }}</p>
            <p><strong>Department:</strong> {{ $record->department ?? '-' }}</p>
            <p><strong>Designation:</strong> {{ $record->designation ?? '-' }}</p>
            <p><strong>Position Type:</strong> {{ $record->employment_type ?? '-' }}</p>
            <p><strong>Status:</strong> {{ $record->is_active ? 'Active' : 'Inactive' }}</p>
            <p><strong>Site:</strong> {{ $record->site?->name ?? '-' }}</p>
        </div>
    </x-filament::card>

    {{-- Personal Info Section --}}
    <x-filament::card class="mb-6">
        <h2 class="text-lg font-bold mb-3">Personal Information</h2><br>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <p><strong>First Name:</strong> {{ $record->first_name }}</p>
            <p><strong>Last Name:</strong> {{ $record->last_name }}</p>
            <p><strong>Date of Birth:</strong> {{ $record->date_of_birth ? $record->date_of_birth->format('F j, Y') : '-' }}</p>
            <p><strong>Gender:</strong> {{ $record->gender ?? '-' }}</p>
            <p><strong>Phone:</strong> {{ $record->phone ?? '-' }}</p>
            <p><strong>Email:</strong> {{ $record->email ?? '-' }}</p>
            <p class="col-span-2"><strong>Address:</strong> {{ $record->address ?? '-' }}</p>
        </div>
    </x-filament::card>

    {{-- Employment Info Section --}}
    <x-filament::card class="mb-6">
        <h2 class="text-lg font-bold mb-3">Employment Info</h2><br>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <p><strong>Joined Date:</strong> {{ $record->joined_date ? $record->joined_date->format('F j, Y') : '-' }}</p>
            <p><strong>Basic Salary:</strong> {{ $record->basic_salary ? number_format($record->basic_salary, 2) : '-' }}</p>
            <p><strong>Created At:</strong> {{ $record->created_at ? $record->created_at->format('F j, Y, g:i A') : '-' }}</p>
            <p><strong>Updated At:</strong> {{ $record->updated_at ? $record->updated_at->format('F j, Y, g:i A') : '-' }}</p
        </div>
    </x-filament::card>
</x-filament::page>
