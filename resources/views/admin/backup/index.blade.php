<x-sidebar-layout>
    <div class="p-6 space-y-6">

        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-bold" style="color:#1f2937;">Backup & Restore</h1>
            <p class="text-sm mt-1" style="color:#6b7280;">Manage database backups and restore points.</p>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="px-4 py-3 rounded-lg text-sm" style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #15803d;">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="px-4 py-3 rounded-lg text-sm" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #b91c1c;">
                {{ session('error') }}
            </div>
        @endif

        {{-- Section 1: Create Backup --}}
        <div class="rounded-xl p-5" style="background:#ffffff; border: 1px solid #e5e7eb;">
            <h2 class="text-base font-semibold mb-3" style="color:#0b3d0b;">Create Backup</h2>
            <p class="text-sm mb-4" style="color:#6b7280;">Creates a full backup of the database and application files.</p>
            <form method="POST" action="{{ route('admin.backup.run') }}">
                @csrf
                <button type="submit"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
                        style="background: linear-gradient(135deg, #a8861a, #d4af37); color: #0b3d0b;"
                        onmouseover="this.style.opacity='0.85'"
                        onmouseout="this.style.opacity='1'">
                    <i class="fas fa-database mr-2"></i> Run Backup Now
                </button>
            </form>
        </div>

        {{-- Section 2: Backup History --}}
        <div class="rounded-xl p-5" style="background:#ffffff; border: 1px solid #e5e7eb;">
            <h2 class="text-base font-semibold mb-3" style="color:#0b3d0b;">Backup History</h2>
            @if($files->isEmpty())
                <p class="text-sm" style="color:#6b7280;">No backups found.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <th class="text-left py-2 px-3" style="color:#6b7280;">Filename</th>
                                <th class="text-left py-2 px-3" style="color:#6b7280;">Size</th>
                                <th class="text-left py-2 px-3" style="color:#6b7280;">Created</th>
                                <th class="text-left py-2 px-3" style="color:#6b7280;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($files as $file)
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td class="py-2 px-3" style="color:#1f2937;">{{ $file['name'] }}</td>
                                <td class="py-2 px-3" style="color:#6b7280;">{{ $file['size_mb'] }} MB</td>
                                <td class="py-2 px-3" style="color:#6b7280;">{{ $file['created_at'] }}</td>
                                <td class="py-2 px-3 flex gap-2">
                                    <a href="{{ route('admin.backup.download', $file['name']) }}"
                                       class="px-3 py-1 rounded text-xs font-medium"
                                       style="background: rgba(212,175,55,0.1); color:#0b3d0b; border: 1px solid #e5e7eb;">
                                        <i class="fas fa-download mr-1"></i> Download
                                    </a>
                                    <button onclick="confirmDelete('{{ $file['name'] }}')"
                                            class="px-3 py-1 rounded text-xs font-medium"
                                            style="background: rgba(239,68,68,0.1); color: #dc2626; border: 1px solid rgba(239,68,68,0.2);">
                                        <i class="fas fa-trash mr-1"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Section 3: Restore --}}
        <div class="rounded-xl p-5" style="background:#ffffff; border: 1px solid rgba(239,68,68,0.2);">
            <h2 class="text-base font-semibold mb-1" style="color: #dc2626;">Restore Database</h2>
            <p class="text-sm mb-4 font-medium" style="color: #b91c1c;">
                <i class="fas fa-triangle-exclamation mr-1"></i>
                This will overwrite the entire database. This cannot be undone.
            </p>
            <form method="POST" action="{{ route('admin.backup.restore') }}" enctype="multipart/form-data">
                @csrf
                <div class="flex items-center gap-3">
                    <input type="file" name="sql_file" accept=".sql,.txt" required
                           class="text-sm" style="color:#6b7280;">
                    <button type="submit"
                            class="px-4 py-2 rounded-lg text-sm font-medium"
                            style="background: rgba(239,68,68,0.15); color: #dc2626; border: 1px solid rgba(239,68,68,0.3);"
                            onmouseover="this.style.background='rgba(239,68,68,0.25)'"
                            onmouseout="this.style.background='rgba(239,68,68,0.15)'"
                            onclick="return confirm('Are you sure? This will overwrite the entire database.')">
                        <i class="fas fa-upload mr-2"></i> Restore
                    </button>
                </div>
            </form>
        </div>

    </div>

    {{-- Delete Confirmation Modal --}}
    <div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center"
         style="background: rgba(0,0,0,0.6);">
        <div class="rounded-xl p-6 w-full max-w-sm" style="background:#ffffff; border: 1px solid #e5e7eb;">
            <h3 class="text-base font-semibold mb-2" style="color:#1f2937;">Delete Backup</h3>
            <p class="text-sm mb-5" style="color:#6b7280;">Are you sure you want to delete this backup? This cannot be undone.</p>
            <div class="flex justify-end gap-3">
                <button onclick="closeDeleteModal()"
                        class="px-4 py-2 rounded-lg text-sm"
                        style="background: rgba(212,175,55,0.1); color:#0b3d0b;">
                    Cancel
                </button>
                <form id="delete-form" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-4 py-2 rounded-lg text-sm font-medium"
                            style="background: rgba(239,68,68,0.2); color: #dc2626;">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(filename) {
            document.getElementById('delete-form').action = '/admin/backup/' + filename;
            const modal = document.getElementById('delete-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closeDeleteModal() {
            const modal = document.getElementById('delete-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
</x-sidebar-layout>
