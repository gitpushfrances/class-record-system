<x-sidebar-layout>
    <div class="p-6 max-w-lg">
        <div class="mb-6">
            <h1 class="text-2xl font-bold" style="color: #f0dfc0;">Create Teacher Account</h1>
            <p class="text-sm mt-1" style="color: rgba(200,169,126,0.6);">New teacher will be active immediately.</p>
        </div>

        @if($errors->any())
            <div class="px-4 py-3 rounded-lg text-sm mb-4" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5;">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-xl p-6" style="background: #211a12; border: 1px solid rgba(200,169,126,0.15);">
            <form method="POST" action="{{ route('admin.users.teachers.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1" style="color: #c8a97e;">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-3 py-2 rounded-lg text-sm"
                           style="background: rgba(200,169,126,0.07); border: 1px solid rgba(200,169,126,0.2); color: #f0dfc0;">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" style="color: #c8a97e;">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-3 py-2 rounded-lg text-sm"
                           style="background: rgba(200,169,126,0.07); border: 1px solid rgba(200,169,126,0.2); color: #f0dfc0;">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" style="color: #c8a97e;">Password</label>
                    <input type="password" name="password" required
                           class="w-full px-3 py-2 rounded-lg text-sm"
                           style="background: rgba(200,169,126,0.07); border: 1px solid rgba(200,169,126,0.2); color: #f0dfc0;">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1" style="color: #c8a97e;">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full px-3 py-2 rounded-lg text-sm"
                           style="background: rgba(200,169,126,0.07); border: 1px solid rgba(200,169,126,0.2); color: #f0dfc0;">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="px-4 py-2 rounded-lg text-sm font-medium"
                            style="background: linear-gradient(135deg, #9a7a50, #c8a97e); color: #1c1814;">
                        Create Teacher
                    </button>
                    <a href="{{ route('admin.deans.index') }}"
                       class="px-4 py-2 rounded-lg text-sm"
                       style="background: rgba(200,169,126,0.1); color: #c8a97e;">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-sidebar-layout>
