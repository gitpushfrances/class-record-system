@extends('layouts.app')

@section('title', 'Edit Subject Request')

@section('content')
<div class="max-w-xl mx-auto">

    <div class="mb-6">
        <a href="{{ route('program-head.subjects.index') }}" class="text-sm" style="color:#c8a97e;">
            <i class="mr-1 text-xs fas fa-arrow-left"></i> Back to Subjects
        </a>
        <h1 class="mt-2 text-xl font-bold" style="font-family:'Fraunces',serif; color:#1c1814;">Edit Subject Request</h1>
        <p class="text-sm mt-0.5 text-gray-500">Only pending requests can be edited.</p>
    </div>

    <div class="p-6 bg-white border rounded-2xl" style="border-color:#e5e7eb;">
        <form method="POST" action="{{ route('program-head.subjects.update', $subject) }}">
            @csrf @method('PUT')

            <div class="space-y-4">
                <div>
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Subject Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $subject->code) }}"
                           class="w-full px-3 py-2 text-sm border rounded-xl focus:outline-none"
                           style="border-color:#d1d5db;">
                    @error('code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Subject Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $subject->name) }}"
                           class="w-full px-3 py-2 text-sm border rounded-xl focus:outline-none"
                           style="border-color:#d1d5db;">
                    @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Units <span class="text-red-500">*</span></label>
                    <input type="number" name="units" value="{{ old('units', $subject->units) }}"
                           min="1" max="10"
                           class="w-full px-3 py-2 text-sm border rounded-xl focus:outline-none"
                           style="border-color:#d1d5db;">
                    @error('units')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2 text-sm border rounded-xl focus:outline-none"
                              style="border-color:#d1d5db;">{{ old('description', $subject->description) }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('program-head.subjects.index') }}"
                   class="px-4 py-2 text-sm font-medium border rounded-xl"
                   style="border-color:#d1d5db; color:#374151;">Cancel</a>
                <button type="submit"
                        class="px-5 py-2 text-sm font-semibold transition rounded-xl"
                        style="background:#1c1814; color:#f0dfc0;">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
