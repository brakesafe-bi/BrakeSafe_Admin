<div data-admin-avatar class="{{ $sizeClass ?? 'h-10 w-10' }} flex shrink-0 items-center justify-center overflow-hidden rounded-full border border-cyan-400/20 bg-cyan-500/20 font-semibold text-cyan-300">
    @if ($admin->profile_photo_path)
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($admin->profile_photo_path) }}" alt="{{ $admin->full_name ?: $admin->name }} profile picture" class="h-full w-full object-cover">
    @else
        <span data-admin-avatar-fallback>{{ strtoupper(substr($admin->full_name ?: $admin->name ?: 'A', 0, 1)) }}</span>
    @endif
</div>
