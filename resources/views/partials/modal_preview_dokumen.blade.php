{{-- Helper Global: Buka Dokumen Langsung di Tab Baru Browser (Tanpa Modal / Tanpa Spinner Macet) --}}
<script>
function previewDokumen(previewUrl) {
    if (!previewUrl) return;
    window.open(previewUrl, '_blank', 'noopener,noreferrer');
}
</script>
