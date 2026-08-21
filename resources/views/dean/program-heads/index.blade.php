<x-sidebar-layout>
    @if (session('success'))
        <div class="px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6">
        <h1 class="text-2xl font-bold" style="font-family:'Fraunces',serif; color:#1c1814;">Program Head Assignments</h1>
        <p class="mt-1 text-sm text-gray-500">Assign or reassign the program each Program Head in your department manages.</p>
    </div>

    <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
        @if($programHeads->isEmpty())
            <p class="p-6 text-sm text-gray-500">No Program Head accounts in your department yet.</p>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead style="background:#f9fafb;">
                    <tr>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Current Program</th>
                        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Assign / Reassign</th>
                        <th class="px-6 py-3 text-xs font-semibold text-center text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($programHeads as $programHead)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800">{{ $programHead->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $programHead->email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $programHead->program->code ?? 'Unassigned' }}
                            </td>
                            <td class="px-6 py-4">
                                <form action="{{ route('dean.program-heads.assign', $programHead) }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    <select name="program_id" class="px-2 py-1 text-sm border rounded" required>
                                        <option value="">Select program</option>
                                        @foreach($programs as $program)
                                            <option value="{{ $program->id }}" {{ $programHead->program_id == $program->id ? 'selected' : '' }}>
                                                {{ $program->code }} — {{ $program->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="px-3 py-1 text-xs font-medium text-white rounded hover:opacity-90" style="background-color:#c8a97e;">
                                        Save
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($programHead->program_id)
                                    <form action="{{ route('dean.program-heads.remove', $programHead) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Remove {{ $programHead->name }} from {{ $programHead->program->code ?? 'their program' }}? They will lose access to their dashboard until reassigned.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1 text-xs font-medium text-white bg-red-500 rounded hover:bg-red-700">
                                            Remove
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</x-sidebar-layout>
