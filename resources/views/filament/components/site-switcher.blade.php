<form method="POST" action="{{ route('site.change') }}" class="ml-4 flex items-center">
    @csrf
    <select
        name="site_id"
        onchange="this.form.submit()"
        class="
            rounded-md text-sm px-2 py-1 transition
            border
            bg-white text-gray-900 border-gray-300
            dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700
            focus:outline-none focus:ring-2 focus:ring-primary-500
        "
    >
        @foreach(\App\Models\Site::where('is_active', true)->get() as $site)
            <option value="{{ $site->id }}" @selected(session('site_id') == $site->id)>
                {{ $site->name }}
            </option>
        @endforeach
    </select>
</form>
