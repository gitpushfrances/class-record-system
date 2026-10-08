@php $selectedGender = old('gender', $student->gender ?? null); @endphp
<div class="mb-4">
    <label class="block mb-2 text-sm font-bold text-gray-700">Gender</label>
    <select name="gender" class="w-full px-3 py-2 border rounded" required>
        <option value="">Select gender</option>
        <option value="male" {{ $selectedGender === 'male' ? 'selected' : '' }}>Male</option>
        <option value="female" {{ $selectedGender === 'female' ? 'selected' : '' }}>Female</option>
    </select>
    @error('gender')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
</div>
