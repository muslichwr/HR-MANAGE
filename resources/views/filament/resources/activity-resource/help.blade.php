<div class="p-4">
    <div class="space-y-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Menggunakan Log Aktivitas</h2>
            <p class="text-sm text-gray-500">Log aktivitas mencatat semua perubahan data dalam sistem. Berikut adalah cara menggunakan fitur-fiturnya:</p>
        </div>
        
        <div class="space-y-3">
            <div class=" p-3 rounded-lg">
                <h3 class="text-md font-semibold text-gray-800">Filter</h3>
                <ul class="list-disc pl-5 text-sm">
                    <li>Filter <strong>Jenis Log</strong> untuk melihat aktivitas berdasarkan modul (departemen, karyawan, dll).</li>
                    <li>Filter <strong>Event</strong> untuk melihat aktivitas pembuatan, perubahan, atau penghapusan data.</li>
                    <li>Filter <strong>User</strong> untuk melihat aktivitas pengguna tertentu.</li>
                    <li>Filter <strong>Tanggal</strong> untuk melihat aktivitas dalam rentang waktu tertentu.</li>
                </ul>
            </div>
            
            <div class=" p-3 rounded-lg">
                <h3 class="text-md font-semibold text-gray-800">Aksi</h3>
                <ul class="list-disc pl-5 text-sm">
                    <li><strong>Refresh</strong> untuk memperbarui daftar log aktivitas.</li>
                    <li><strong>Bersihkan Log Lama</strong> (hanya admin) untuk menghapus log aktivitas yang lebih dari 30 hari.</li>
                    <li><strong>Ekspor ke CSV</strong> untuk mengunduh log aktivitas dalam format CSV.</li>
                </ul>
            </div>
            
            <div class=" p-3 rounded-lg">
                <h3 class="text-md font-semibold text-gray-800">Detail</h3>
                <ul class="list-disc pl-5 text-sm">
                    <li>Klik <strong>Lihat</strong> pada aktivitas untuk melihat detail perubahan data.</li>
                    <li>Pada halaman detail, Anda dapat melihat perbandingan data sebelum dan sesudah perubahan.</li>
                </ul>
            </div>
        </div>
        
        <div class="text-xs text-gray-500 italic">
            Sistem log aktivitas menggunakan package Spatie ActivityLog untuk mencatat semua perubahan data.
        </div>
    </div>
</div> 