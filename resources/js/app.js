import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    // Pegawai filter (search-based multi-select) — dipakai di absensi & perjalanan-dinas
    Alpine.data('pegawaiFilter', (initialSelected, allPegawai) => ({
        selected: Array.isArray(initialSelected) ? initialSelected.map(Number) : [],
        allPegawai: allPegawai || [],
        search: '',
        open: false,
        filtered() {
            const q = this.search.trim().toLowerCase();
            if (!q) return this.allPegawai;
            return this.allPegawai.filter(p => (p.name || '').toLowerCase().includes(q));
        },
        isSelected(id) {
            return this.selected.includes(Number(id));
        },
        toggle(id) {
            const n = Number(id);
            const idx = this.selected.indexOf(n);
            if (idx === -1) this.selected.push(n);
            else this.selected.splice(idx, 1);
        },
        remove(id) {
            const n = Number(id);
            const idx = this.selected.indexOf(n);
            if (idx !== -1) this.selected.splice(idx, 1);
        },
        reset() {
            this.selected = [];
        },
        nameOf(id) {
            const p = this.allPegawai.find(x => Number(x.id) === Number(id));
            return p ? p.name : '#' + id;
        }
    }));
});

Alpine.start();
