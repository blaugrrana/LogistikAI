{{-- ── Bubble chat asisten AI (khusus tab Manajemen Barang) ─────────── --}}
<div class="glass-lg glass--rim sticky top-[62px] z-19 flex flex-col gap-2.5 rounded-xl p-3" data-chat>

    <div class="flex items-center gap-2.5">
        <span class="chat-avatar">AI</span>
        <span class="flex-1 text-[13px]" style="color: var(--c-frost); line-height: 1.4">Asisten Penataan Gudang</span>
        <button type="button" data-chat-toggle
                class="rounded-lg px-2.5 py-1"
                style="background: transparent; border: 1px solid var(--glass-edge); font-family: var(--font-mono); font-size: 10px; letter-spacing: 0.08em; text-transform: uppercase; color: var(--c-mist); cursor: pointer">Tutup</button>
    </div>

    <div class="flex flex-col gap-2.5 border-t pt-2.5" style="border-color: var(--glass-edge)" data-chat-body>

        <div class="chat-progress" data-chat-progress hidden><div class="chat-progress__bar"></div></div>

        <div class="chat-log" data-chat-log role="log" aria-live="polite"></div>

        {{-- Ringkasan isian yang sudah ditangkap AI --}}
        <div class="flex flex-wrap items-center gap-1.5" data-chat-slots hidden>
            <span class="footnote">Tercatat</span>
            <span class="tag" data-slot="warehouse_count" hidden></span>
            <span class="tag" data-slot="rack_counts" hidden></span>
            <span class="tag" data-slot="categories" hidden></span>
        </div>

        <form class="flex gap-2" data-chat-form>
            @csrf
            <label for="chat-input" class="sr-only">Pesan untuk asisten</label>
            <input id="chat-input" type="text" autocomplete="off" data-chat-input maxlength="500"
                   placeholder="{{ $warehouses->isNotEmpty() ? 'mis. tambah 2 rak di GD-A, atau tambah gudang baru 5 rak' : 'mis. 2 gudang, yang pertama 5 rak yang kedua 3, isinya minuman dan snack' }}"
                   class="flex-1 rounded-[10px] px-3 py-2.5 text-[13px]"
                   style="background: var(--c-basalt); border: 1px solid var(--glass-edge); color: var(--c-frost); outline: none">
            <button type="submit" class="btn btn--primary" data-chat-send @disabled(! $aiReady)>Kirim</button>
        </form>

        <div class="footnote">Jawaban disusun model — periksa hasilnya sebelum dipakai</div>
    </div>
</div>

@once
    @push('scripts')
    <script>
    // Bubble chat: pesan bebas dikirim apa adanya, AI yang menafsirkan menjadi
    // isian setup (jumlah gudang, rak per gudang, kategori). Server membalas
    // 'reply' + 'state'; saat lengkap, struktur langsung disusun (done: true).
    (() => {
        const root = document.querySelector('[data-chat]');
        if (!root) return;

        const log = root.querySelector('[data-chat-log]');
        const form = root.querySelector('[data-chat-form]');
        const input = root.querySelector('[data-chat-input]');
        const send = root.querySelector('[data-chat-send]');
        const body = root.querySelector('[data-chat-body]');
        const toggle = root.querySelector('[data-chat-toggle]');
        const progress = root.querySelector('[data-chat-progress]');
        const slotBar = root.querySelector('[data-chat-slots]');
        const token = root.querySelector('input[name="_token"]').value;
        const endpoint = @json(route('warehouses.chat'));
        const aiReady = !send.disabled;
        const hasStructure = @json($warehouses->isNotEmpty());

        const labels = {
            warehouse_count: n => n + ' gudang',
            rack_counts: list => 'rak: ' + list.join(' + '),
            categories: c => c,
        };

        const stages = hasStructure ? [
            'Sedang berpikir',
            'Memahami maksud Anda',
            'Memeriksa struktur saat ini',
            'Menyiapkan perubahan',
            'Menyimpan perubahan',
        ] : [
            'Sedang berpikir',
            'Memahami maksud Anda',
            'Menyusun denah gudang',
            'Menentukan zona tiap rak',
            'Menghitung kapasitas',
            'Menyimpan struktur',
        ];

        let state = { warehouse_count: null, rack_counts: null, categories: null };
        let busy = false;

        const scroll = () => { log.scrollTop = log.scrollHeight; };

        const bubble = (text, kind) => {
            const el = document.createElement('div');
            el.className = 'bubble bubble--' + kind;
            el.textContent = text;
            log.appendChild(el);
            scroll();
        };

        const renderSlots = () => {
            let any = false;
            for (const [key, format] of Object.entries(labels)) {
                const tag = slotBar.querySelector(`[data-slot="${key}"]`);
                const value = state[key];
                const filled = Array.isArray(value) ? value.length > 0 : (value !== null && value !== '');
                tag.hidden = !filled;
                if (filled) { tag.textContent = format(value); any = true; }
            }
            slotBar.hidden = !any;
        };

        const thinking = () => {
            const el = document.createElement('div');
            el.className = 'bubble bubble--ai thinking';
            el.innerHTML = '<span class="thinking__dots"><span></span><span></span><span></span></span>'
                         + '<span class="thinking__label"></span>';
            const label = el.querySelector('.thinking__label');
            let i = 0;
            label.textContent = stages[0] + '…';
            const timer = setInterval(() => {
                i = Math.min(i + 1, stages.length - 1);
                label.textContent = stages[i] + '…';
            }, 1400);
            log.appendChild(el);
            scroll();
            return { stop: () => { clearInterval(timer); el.remove(); } };
        };

        const setEnabled = on => {
            input.disabled = !on;
            send.disabled = !on;
            if (on) input.focus();
        };

        const talk = async message => {
            busy = true;
            setEnabled(false);
            progress.hidden = false;
            const think = thinking();

            try {
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ message, state }),
                });
                const data = await res.json().catch(() => ({}));
                think.stop();

                if (!res.ok) {
                    // 422 membawa daftar error validasi, 502 membawa pesan tunggal.
                    const detail = data.errors ? Object.values(data.errors).flat()[0] : data.message;
                    throw new Error(detail || 'Permintaan gagal (status ' + res.status + ').');
                }

                state = data.state ?? state;
                renderSlots();
                bubble(data.reply, 'ai');

                if (data.done) {
                    bubble('Memuat ulang panel…', 'ai');
                    setTimeout(() => window.location.reload(), 900);
                    return;
                }
            } catch (e) {
                think.stop();
                bubble(e.message, 'error');
                bubble('Silakan coba kirim lagi.', 'ai');
            }

            busy = false;
            progress.hidden = true;
            setEnabled(true);
        };

        form.addEventListener('submit', e => {
            e.preventDefault();
            const message = input.value.trim();
            if (!message || busy) return;

            bubble(message, 'user');
            input.value = '';
            talk(message);
        });

        toggle.addEventListener('click', () => {
            const hidden = body.hasAttribute('hidden');
            body.toggleAttribute('hidden', !hidden);
            toggle.textContent = hidden ? 'Tutup' : 'Buka';
        });

        if (!aiReady) {
            bubble('GEMINI_API_KEY belum diisi, jadi saya belum bisa menyusun gudang. Tambahkan kuncinya di file .env lalu muat ulang halaman ini.', 'error');
            setEnabled(false);
        } else if (hasStructure) {
            bubble('Struktur gudang Anda sudah tersusun. Butuh perubahan? Sebutkan saja, misalnya "tambah 2 rak di GD-A", "tambah gudang baru dengan 5 rak", atau "hapus rak B3".', 'ai');
        } else {
            bubble('Halo! Ceritakan saja gudang Anda — berapa gudangnya, berapa rak per gudang, dan barang kategori apa yang disimpan. Boleh sekaligus atau bertahap.', 'ai');
        }
    })();
    </script>
    @endpush
@endonce
