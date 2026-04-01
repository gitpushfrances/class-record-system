<x-sidebar-layout>

<div class="mb-6">
    <a href="{{ route('admin.deans.index') }}" class="text-sm text-indigo-600 hover:underline">← Back to Faculty</a>
    <h1 class="mt-1 text-2xl font-bold" style="color:#f0dfc0;">Create Program Head Account</h1>
</div>

@if($errors->any())
    <div class="px-4 py-3 mb-4 rounded-lg text-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;">
        @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
@endif

<div class="max-w-lg rounded-xl p-6" style="background:#211a12;border:1px solid rgba(200,169,126,0.15);">
    <form method="POST" action="{{ route('admin.users.program-heads.store') }}">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Password</label>
                <input type="password" name="password"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Confirm Password</label>
                <input type="password" name="password_confirmation"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
            </div>
            <button type="submit" class="w-full py-2.5 rounded-lg text-sm font-semibold"
                    style="background:linear-gradient(135deg,#9a7a50,#c8a97e);color:#1c1814;">
                Create Program Head Account
            </button>
        </div>
    </form>
</div>

</x-sidebar-layout>
