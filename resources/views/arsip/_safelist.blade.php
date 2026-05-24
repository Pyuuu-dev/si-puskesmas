{{--
    Safelist file untuk Tailwind v4 scanner.
    File ini TIDAK pernah di-include ke output HTML.
    Tujuan: pastikan semua class dinamic yang dipakai modul Arsip
    (folder color, link preset bg) ke-detect oleh Tailwind scanner.

    Jangan dihapus. Jangan di-render. Hanya untuk @source detection.
--}}
@php exit; @endphp

{{-- Folder color palette (18 warna) ──────────────────────────── --}}
{{-- bg variants --}}
<div class="bg-red-100 bg-red-500 bg-red-600"></div>
<div class="bg-orange-100 bg-orange-500 bg-orange-600"></div>
<div class="bg-amber-100 bg-amber-500 bg-amber-600"></div>
<div class="bg-yellow-100 bg-yellow-500 bg-yellow-600"></div>
<div class="bg-lime-100 bg-lime-500 bg-lime-600"></div>
<div class="bg-green-100 bg-green-500 bg-green-600"></div>
<div class="bg-emerald-100 bg-emerald-500 bg-emerald-600"></div>
<div class="bg-teal-100 bg-teal-500 bg-teal-600"></div>
<div class="bg-cyan-100 bg-cyan-500 bg-cyan-600"></div>
<div class="bg-sky-100 bg-sky-500 bg-sky-600"></div>
<div class="bg-blue-100 bg-blue-500 bg-blue-600 bg-blue-700"></div>
<div class="bg-indigo-100 bg-indigo-500 bg-indigo-600"></div>
<div class="bg-violet-100 bg-violet-500 bg-violet-600"></div>
<div class="bg-purple-100 bg-purple-500 bg-purple-600"></div>
<div class="bg-fuchsia-100 bg-fuchsia-500 bg-fuchsia-600"></div>
<div class="bg-pink-100 bg-pink-500 bg-pink-600"></div>
<div class="bg-rose-100 bg-rose-500 bg-rose-600"></div>
<div class="bg-gray-100 bg-gray-500 bg-gray-600 bg-gray-800 bg-gray-900"></div>
<div class="bg-slate-700"></div>

{{-- text variants --}}
<div class="text-red-500 text-red-600 text-red-700"></div>
<div class="text-orange-500 text-orange-600 text-orange-700"></div>
<div class="text-amber-500 text-amber-600 text-amber-700"></div>
<div class="text-yellow-500 text-yellow-600 text-yellow-700"></div>
<div class="text-lime-500 text-lime-600 text-lime-700"></div>
<div class="text-green-500 text-green-600 text-green-700"></div>
<div class="text-emerald-500 text-emerald-600 text-emerald-700"></div>
<div class="text-teal-500 text-teal-600 text-teal-700"></div>
<div class="text-cyan-500 text-cyan-600 text-cyan-700"></div>
<div class="text-sky-500 text-sky-600 text-sky-700"></div>
<div class="text-blue-500 text-blue-600 text-blue-700"></div>
<div class="text-indigo-500 text-indigo-600 text-indigo-700"></div>
<div class="text-violet-500 text-violet-600 text-violet-700"></div>
<div class="text-purple-500 text-purple-600 text-purple-700"></div>
<div class="text-fuchsia-500 text-fuchsia-600 text-fuchsia-700"></div>
<div class="text-pink-500 text-pink-600 text-pink-700"></div>
<div class="text-rose-500 text-rose-600 text-rose-700"></div>
<div class="text-gray-500 text-gray-600 text-gray-700"></div>

{{-- ring variants (untuk hover/active state color picker) --}}
<div class="ring-red-600 ring-orange-600 ring-amber-600 ring-yellow-600"></div>
<div class="ring-lime-600 ring-green-600 ring-emerald-600 ring-teal-600"></div>
<div class="ring-cyan-600 ring-sky-600 ring-blue-600 ring-indigo-600"></div>
<div class="ring-violet-600 ring-purple-600 ring-fuchsia-600 ring-pink-600"></div>
<div class="ring-rose-600 ring-gray-600"></div>

{{-- border variants (untuk folder card hover state) --}}
<div class="border-red-300 border-orange-300 border-amber-300 border-yellow-300"></div>
<div class="border-lime-300 border-green-300 border-emerald-300 border-teal-300"></div>
<div class="border-cyan-300 border-sky-300 border-blue-300 border-indigo-300"></div>
<div class="border-violet-300 border-purple-300 border-fuchsia-300 border-pink-300"></div>
<div class="border-rose-300 border-gray-300"></div>
<div class="hover:border-red-300 hover:border-orange-300 hover:border-amber-300 hover:border-yellow-300"></div>
<div class="hover:border-lime-300 hover:border-green-300 hover:border-emerald-300 hover:border-teal-300"></div>
<div class="hover:border-cyan-300 hover:border-sky-300 hover:border-blue-300 hover:border-indigo-300"></div>
<div class="hover:border-violet-300 hover:border-purple-300 hover:border-fuchsia-300 hover:border-pink-300"></div>
<div class="hover:border-rose-300 hover:border-gray-300"></div>
