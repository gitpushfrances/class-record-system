<x-sidebar-layout>
    <div class="p-6 space-y-6">

        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-bold" style="color: #f0dfc0;">Backup & Restore</h1>
            <p class="text-sm mt-1" style="color: rgba(200,169,126,0.6);">Manage database backups and restore points.</p>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="px-4 py-3 rounded-lg text-sm" style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #86efac;">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="px-4 py-3 rounded-lg text-sm" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5;">
                {{ session('error') }}
            </div>
        @endif

        {{-- Section 1: Create Backup --}}
        <div class="rounded-xl p-5" style="background: #211a12; border: 1px solid rgba(200,169,126,0.15);">
            <h2 class="text-base font-semibold mb-3" style="color: #c8a97e;">Create Backup</h2>
            <p class="text-sm mb-4" style="color: rgba(200,169,126,0.6);">Creates a full backup of the database and application files.</p>
            <form method="POST" action="{{ route('admin.backup.run') }}">
                @csrf
                <button type="submit"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
                        style="background: linear-gradient(135deg, #9a7a50, #c8a97e); color: #1c1814;"
                        onmouseover="this.style.opacity='0.85'"
                        onmouseout="this.style.opacity='1'">
                    <i class="fas fa-database mr-2"></i> Run Backup Now
                </button>
            </form>
        </div>

        {{-- Section 2: Backup History --}}
        <div class="rounded-xl p-5" style="background: #211a12; border: 1px solid rgba(200,169,126,0.15);">
            <h2 class="text-base font-semibold mb-3" style="color: #c8a97e;">Backup History</h2>
            @if($files->isEmpty())
                <p class="text-sm" style="color: rgba(200,169,126,0.5);">No backups found.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(200,169,126,0.15);">
                                <th class="text-left py-2 px-3" style="color: rgba(200,169,126,0.6);">Filename</th>
                                <th class="text-left py-2 px-3" style="color: rgba(200,169,126,0.6);">Size</th>
                                <th class="text-left py-2 px-3" style="color: rgba(200,169,126,0.6);">Created</th>
                                <th class="text-left py-2 px-3" style="color: rgba(200,169,126,0.6);">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($files as $file)
                            <tr style="border-bottom: 1px solid rgba(200,169,126,0.07);">
                                <td class="py-2 px-3" style="color: #f0dfc0;">{{ $file['name'] }}</td>
                                <td class="py-2 px-3" style="color: rgba(200,169,126,0.7);">{{ $file['size_mb'] }} MB</td>
                                <td class="py-2 px-3" style="color: rgba(200,169,126,0.7);">{{ $file['created_at'] }}</td>
                                <td class="py-2 px-3 flex gap-2">
                                    <a href="{{ route('admin.backup.download', $file['name']) }}"
                                       class="px-3 py-1 rounded text-xs font-medium"
                                       style="background: rgba(200,169,126,0.1); color: #c8a97e; border: 1px solid rgba(200,169,126,0.2);">
                                        <i class="fas fa-download mr-1"></i> Download
                                    </a>
                                    <button onclick="confirmDelete('{{ $file['name'] }}')"
                                            class="px-3 py-1 rounded text-xs font-medium"
                                            style="background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.2);">
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
        <div class="rounded-xl p-5" style="background: #211a12; border: 1px solid rgba(239,68,68,0.2);">
            <h2 class="text-base font-semibold mb-1" style="color: #f87171;">Restore Database</h2>
            <p class="text-sm mb-4 font-medium" style="color: #fca5a5;">
                <i class="fas fa-triangle-exclamation mr-1"></i>
                This will overwrite the entire database. This cannot be undone.
            </p>
            <form method="POST" action="{{ route('admin.backup.restore') }}" enctype="multipart/form-data">
                @csrf
                <div class="flex items-center gap-3">
                    <input type="file" name="sql_file" accept=".sql,.txt" required
                           class="text-sm" style="color: rgba(200,169,126,0.8);">
                    <button type="submit"
                            class="px-4 py-2 rounded-lg text-sm font-medium"
                            style="background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3);"
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
        <div class="rounded-xl p-6 w-full max-w-sm" style="background: #211a12; border: 1px solid rgba(200,169,126,0.2);">
            <h3 class="text-base font-semibold mb-2" style="color: #f0dfc0;">Delete Backup</h3>
            <p class="text-sm mb-5" style="color: rgba(200,169,126,0.7);">Are you sure you want to delete this backup? This cannot be undone.</p>
            <div class="flex justify-end gap-3">
                <button onclick="closeDeleteModal()"
                        class="px-4 py-2 rounded-lg text-sm"
                        style="background: rgba(200,169,126,0.1); color: #c8a97e;">
                    Cancel
                </button>
                <form id="delete-form" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-4 py-2 rounded-lg text-sm font-medium"
                            style="background: rgba(239,68,68,0.2); color: #f87171;">
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
