@if ($warehouses->isEmpty())

    {{-- Setup ditangani bubble chat di atas; di sini cukup penunjuk arah. --}}
    <div class="glass-md flex flex-col items-center gap-3 p-10 text-center">
        <span class="chat-avatar">AI</span>
        <h2 class="t-h3">Belum ada gudang terdaftar</h2>
        <p class="t-body max-w-[52ch]">
            Jawab tiga pertanyaan asisten AI di panel atas — jumlah gudang, jumlah rak per gudang,
            dan kategori barang. Struktur rak beserta zona dan kapasitasnya langsung disusun dan disimpan.
        </p>
    </div>

@else

    {{-- Ringkasan kapasitas --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="metric">
            <span class="metric__label">Gudang</span>
            <span class="metric__value">{{ $warehouses->count() }}</span>
            <span class="metric__delta" style="color: var(--c-mist)">{{ $rackCount }} rak terdaftar</span>
        </div>
        <div class="metric">
            <span class="metric__label">Barang tertempatkan</span>
            <span class="metric__value">{{ $placedCount }}</span>
            <span class="metric__delta {{ $unplacedCount > 0 ? 'metric__delta--down' : 'metric__delta--up' }}">
                {{ $unplacedCount > 0 ? $unplacedCount.' menunggu spotting' : 'semua sudah ditempatkan' }}
            </span>
        </div>
        <div class="metric">
            <span class="metric__label">Kapasitas terpakai</span>
            <span class="metric__value">{{ $totalCapacity > 0 ? round($usedCapacity / $totalCapacity * 100) : 0 }}%</span>
            <span class="metric__delta" style="color: var(--c-mist)">{{ number_format($usedCapacity, 0, ',', '.') }} dari {{ number_format($totalCapacity, 0, ',', '.') }}</span>
        </div>
        <div class="metric">
            <span class="metric__label">Total SKU</span>
            <span class="metric__value">{{ $items->count() }}</span>
            <span class="metric__delta" style="color: var(--c-mist)">{{ $items->pluck('category')->unique()->count() }} kategori</span>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1.6fr_1fr] lg:items-start">

        {{-- Daftar barang --}}
        <div class="glass-md flex flex-col">
            <div class="flex items-center justify-between p-5">
                <span class="eyebrow">Daftar barang</span>
                <span class="t-body" style="font-size: var(--fs-body-s)">{{ $items->count() }} SKU</span>
            </div>

            @if ($items->isEmpty())
                <p class="t-body px-5 pb-5" style="font-size: var(--fs-body-s)">
                    Belum ada barang. Tambahkan lewat formulir di samping, lalu jalankan spotting AI.
                </p>
            @else
                <div class="hidden gap-3 px-5 pb-2 md:grid" style="grid-template-columns: 110px 1fr 80px 150px 40px; font-family: var(--font-mono); font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--c-slate)">
                    <div>SKU</div><div>Nama / kategori</div><div>Qty</div><div>Penempatan</div><div></div>
                </div>

                @foreach ($items as $item)
                    <div class="glass-sm mx-3 mb-2.5 grid items-center gap-3 rounded-xl p-3 md:grid-cols-[110px_1fr_80px_150px_40px]">
                        <div style="font-family: var(--font-mono); font-size: 12px; color: var(--c-frost)">{{ $item->sku }}</div>
                        <div>
                            <div class="text-[14px]" style="color: var(--c-frost)">{{ $item->name }}</div>
                            <div class="text-[12px]" style="color: var(--c-mist)">
                                {{ $item->category }} · {{ $item->movement }} moving
                                @if ($item->expires_at)
                                    · kedaluwarsa {{ $item->expires_at->format('d/m/y') }}
                                @endif
                            </div>
                        </div>
                        <div style="font-family: var(--font-mono); font-size: 12px; color: var(--c-mist); font-variant-numeric: tabular-nums">{{ number_format($item->quantity, 0, ',', '.') }}</div>
                        <div>
                            @if ($item->rack)
                                <span class="badge badge--ok">{{ $item->rack->warehouse->code }} · {{ $item->rack->code }}</span>
                                @if ($item->placement_reason)
                                    <div class="mt-1 text-[11px]" style="color: var(--c-slate); line-height: 1.45">{{ $item->placement_reason }}</div>
                                @endif
                            @else
                                <span class="badge badge--warn">Belum ditempatkan</span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('items.destroy', $item) }}" class="justify-self-end">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn--tertiary btn--sm" title="Hapus {{ $item->sku }}">×</button>
                        </form>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Tambah barang + struktur gudang --}}
        <div class="flex flex-col gap-4">
            <div class="glass-md flex flex-col gap-3.5 p-6">
                <span class="eyebrow">Tambah barang</span>
                <form method="POST" action="{{ route('items.store') }}" class="flex flex-col gap-3">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="field">
                            <label for="sku" class="field__label">SKU</label>
                            <input id="sku" name="sku" type="text" required maxlength="50" value="{{ old('sku') }}" placeholder="SKU-10482" class="field__input">
                        </div>
                        <div class="field">
                            <label for="quantity" class="field__label">Jumlah</label>
                            <input id="quantity" name="quantity" type="number" min="1" required value="{{ old('quantity', 100) }}" class="field__input">
                        </div>
                    </div>
                    <div class="field">
                        <label for="name" class="field__label">Nama barang</label>
                        <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name') }}" placeholder="Kopi Susu 220ml" class="field__input">
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="field">
                            <label for="category" class="field__label">Kategori</label>
                            <input id="category" name="category" type="text" required maxlength="100" value="{{ old('category') }}" placeholder="Minuman" class="field__input">
                        </div>
                        <div class="field">
                            <label for="movement" class="field__label">Frekuensi gerak</label>
                            <select id="movement" name="movement" class="field__select">
                                <option value="fast" @selected(old('movement') === 'fast')>Fast moving</option>
                                <option value="medium" @selected(old('movement', 'medium') === 'medium')>Medium moving</option>
                                <option value="slow" @selected(old('movement') === 'slow')>Slow moving</option>
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <label for="expires_at" class="field__label">Tanggal kedaluwarsa <span style="color: var(--c-slate); font-weight: 400">(opsional)</span></label>
                        <input id="expires_at" name="expires_at" type="date" value="{{ old('expires_at') }}" class="field__input">
                    </div>
                    <button type="submit" class="btn btn--secondary btn--full">Tambah barang</button>
                </form>
            </div>

            <div class="glass-md flex flex-col gap-3.5 p-6">
                <div class="flex items-center justify-between gap-3">
                    <span class="eyebrow">Struktur gudang</span>
                    <form method="POST" action="{{ route('warehouses.destroy') }}"
                          onsubmit="return confirm('Hapus seluruh gudang beserta raknya? Penempatan barang akan ikut hilang.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn--tertiary btn--sm">Susun ulang</button>
                    </form>
                </div>

                @foreach ($warehouses as $warehouse)
                    <div class="flex flex-col gap-2.5">
                        <div>
                            <div class="text-[13px] font-semibold" style="color: var(--c-frost)">{{ $warehouse->code }} · {{ $warehouse->name }}</div>
                            @if ($warehouse->focus)
                                <div class="text-[12px]" style="color: var(--c-mist)">{{ $warehouse->focus }}</div>
                            @endif
                        </div>

                        @foreach ($warehouse->racks as $rack)
                            @php $pct = $rack->loadPercent(); @endphp
                            <div>
                                <div class="mb-1.5 flex items-center justify-between text-[13px]">
                                    <span style="color: var(--c-frost)">
                                        {{ $rack->code }}
                                        <span class="text-[12px]" style="color: var(--c-mist)">{{ $rack->category ?? '—' }}{{ $rack->zone ? ' · '.$rack->zone : '' }}</span>
                                    </span>
                                    <span style="font-family: var(--font-mono); color: var(--c-mist)">{{ $pct }}%</span>
                                </div>
                                <div class="meter">
                                    <div class="meter__fill @if ($pct >= 90) meter__fill--risk @elseif ($pct >= 70) meter__fill--warn @endif" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>

@endif
