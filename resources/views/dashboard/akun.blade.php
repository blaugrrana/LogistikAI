{{-- ── Tab Pengaturan Akun ──────────────────────────────────────────── --}}

<div class="grid gap-4 lg:grid-cols-2 lg:items-start">

    <div class="glass-md flex flex-col gap-3.5 p-6">
        <div class="flex flex-col gap-1">
            <span class="eyebrow">Profil</span>
            <p class="t-body" style="font-size: var(--fs-body-s)">Nama dan email yang dipakai untuk masuk ke panel.</p>
        </div>

        <form method="POST" action="{{ route('account.profile') }}" class="flex flex-col gap-3">
            @csrf
            @method('PUT')
            <div class="field">
                <label for="acc_name" class="field__label">Nama lengkap</label>
                <input id="acc_name" name="name" type="text" required maxlength="255"
                       value="{{ old('name', auth()->user()->name) }}" class="field__input">
            </div>
            <div class="field">
                <label for="acc_email" class="field__label">Alamat email</label>
                <input id="acc_email" name="email" type="email" required maxlength="255"
                       value="{{ old('email', auth()->user()->email) }}" class="field__input">
            </div>
            <button type="submit" class="btn btn--secondary btn--full">Simpan profil</button>
        </form>
    </div>

    <div class="glass-md flex flex-col gap-3.5 p-6">
        <div class="flex flex-col gap-1">
            <span class="eyebrow">Kata sandi</span>
            <p class="t-body" style="font-size: var(--fs-body-s)">Minimal 8 karakter. Sesi lain tetap berjalan setelah diganti.</p>
        </div>

        <form method="POST" action="{{ route('account.password') }}" class="flex flex-col gap-3">
            @csrf
            @method('PUT')
            <div class="field">
                <label for="current_password" class="field__label">Kata sandi saat ini</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="field__input">
            </div>
            <div class="field">
                <label for="new_password" class="field__label">Kata sandi baru</label>
                <input id="new_password" name="password" type="password" required minlength="8" autocomplete="new-password" class="field__input">
            </div>
            <div class="field">
                <label for="new_password_confirmation" class="field__label">Konfirmasi kata sandi baru</label>
                <input id="new_password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="field__input">
            </div>
            <button type="submit" class="btn btn--secondary btn--full">Perbarui kata sandi</button>
        </form>
    </div>

    <div class="glass-md flex flex-col gap-3.5 p-6">
        <div class="flex flex-col gap-1">
            <span class="eyebrow">Informasi akun</span>
        </div>
        <dl class="grid grid-cols-2 gap-4 text-[13px]">
            <div>
                <dt style="color: var(--c-slate)">Terdaftar sejak</dt>
                <dd class="mt-0.5 font-semibold" style="color: var(--c-frost)">{{ auth()->user()->created_at?->format('d M Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt style="color: var(--c-slate)">Status email</dt>
                <dd class="mt-0.5">
                    @if (auth()->user()->email_verified_at)
                        <span class="badge badge--ok">Terverifikasi</span>
                    @else
                        <span class="badge badge--warn">Belum diverifikasi</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt style="color: var(--c-slate)">Perusahaan</dt>
                <dd class="mt-0.5 font-semibold" style="color: var(--c-frost)">{{ auth()->user()->company->name }}</dd>
            </div>
            <div>
                <dt style="color: var(--c-slate)">Akun terhubung</dt>
                <dd class="mt-0.5 font-semibold" style="color: var(--c-frost)">{{ auth()->user()->company->users()->count() }}</dd>
            </div>
            <div>
                <dt style="color: var(--c-slate)">Gudang terdaftar</dt>
                <dd class="mt-0.5 font-semibold" style="color: var(--c-frost)">{{ $warehouses->count() }}</dd>
            </div>
            <div>
                <dt style="color: var(--c-slate)">Total SKU</dt>
                <dd class="mt-0.5 font-semibold" style="color: var(--c-frost)">{{ $items->count() }}</dd>
            </div>
        </dl>
    </div>

    <div class="glass-md flex flex-col gap-3.5 p-6">
        <div class="flex flex-col gap-1">
            <span class="eyebrow">Hapus akun</span>
            <p class="t-body" style="font-size: var(--fs-body-s)">
                Akun Anda dihapus permanen dan tidak dapat dipulihkan. Data gudang, rak, dan barang milik
                perusahaan tetap tersimpan untuk akun lain yang terhubung.
            </p>
        </div>

        <form method="POST" action="{{ route('account.destroy') }}" class="flex flex-col gap-3"
              onsubmit="return confirm('Hapus akun ini secara permanen? Data perusahaan tetap tersimpan.')">
            @csrf
            @method('DELETE')
            <div class="field">
                <label for="delete_password" class="field__label">Konfirmasi dengan kata sandi</label>
                <input id="delete_password" name="password" type="password" required autocomplete="current-password" class="field__input">
            </div>
            <button type="submit" class="btn btn--destructive btn--full">Hapus akun saya</button>
        </form>
    </div>
</div>
