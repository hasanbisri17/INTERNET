{{-- CSS sudah dimuat melalui Vite, tidak perlu @push('styles') --}}

<div
    x-data="{
        logoUrl: {{ \App\Models\Setting::get("invoice_logo") ? '"'.\Illuminate\Support\Facades\Storage::disk("public")->url(\App\Models\Setting::get("invoice_logo")).'"' : 'null' }},
        isDragging: false,
        handleDrop(e) {
            e.preventDefault();
            this.isDragging = false;
            
            if (!e.dataTransfer.files.length) return;
            
            const file = e.dataTransfer.files[0];
            if (!file.type.match('image.*')) {
                alert('Hanya file gambar yang diperbolehkan');
                return;
            }
            
            this.uploadFile(file);
        },
        handleDragOver(e) {
            e.preventDefault();
            this.isDragging = true;
        },
        handleDragLeave() {
            this.isDragging = false;
        },
        handleFileSelect(e) {
            if (!e.target.files.length) return;
            
            const file = e.target.files[0];
            if (!file.type.match('image.*')) {
                alert('Hanya file gambar yang diperbolehkan');
                return;
            }
            
            this.uploadFile(file);
        },
        uploadFile(file) {
            const formData = new FormData();
            formData.append('logo', file);
            formData.append('_token', '{{ csrf_token() }}');
            
            // Tampilkan preview sementara
            const reader = new FileReader();
            reader.onload = (e) => {
                this.logoUrl = e.target.result;
            };
            reader.readAsDataURL(file);
            
            // Upload file ke server
            fetch('{{ route("upload.logo") }}', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update hidden input untuk form Filament
                    document.getElementById('data.invoice_logo').value = data.path;
                    Livewire.dispatch('logo-uploaded', { path: data.path });
                } else {
                    alert('Gagal mengupload logo: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengupload logo');
            });
        },
        removeLogo() {
            if (confirm('Apakah Anda yakin ingin menghapus logo?')) {
                this.logoUrl = '';
                document.getElementById('data.invoice_logo').value = '';
                Livewire.dispatch('logo-removed');
            }
        }
    }
    @dragover="handleDragOver"
    @dragleave="handleDragLeave"
    @drop="handleDrop"
    class="logo-drop-area p-6 border-2 border-dashed rounded-lg text-center cursor-pointer transition-all duration-200"
    :class="{ 'border-primary-500 bg-primary-50': isDragging, 'border-gray-300 hover:border-primary-400': !isDragging }"
>
    <input type="hidden" id="data.invoice_logo" name="data[invoice_logo]" />
    
    <template x-if="logoUrl">
        <div class="logo-preview mb-4">
            <img :src="logoUrl" alt="Logo Preview" class="max-h-32 max-w-full mx-auto object-contain" />
            <button 
                type="button" 
                @click.prevent="removeLogo"
                class="mt-2 px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600 transition-colors"
            >
                Hapus Logo
            </button>
        </div>
    </template>
    
    <template x-if="!logoUrl">
        <div>
            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-600">Tarik dan lepas logo di sini atau</p>
        </div>
    </template>
    
    <label class="mt-2 inline-block px-4 py-2 bg-primary-500 text-white rounded cursor-pointer hover:bg-primary-600 transition-colors">
        Pilih File
        <input type="file" class="hidden" accept="image/*" @change="handleFileSelect" />
    </label>
    
    <p class="mt-2 text-xs text-gray-500">Format: JPG, PNG. Ukuran maksimal: 1MB</p>
</div>