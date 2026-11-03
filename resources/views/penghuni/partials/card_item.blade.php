<div class="card-input-item shadow-xs mb-2" id="card_wrap_{{ $key }}">
    <div class="card-input-header" data-bs-toggle="collapse" data-bs-target="#card_body_{{ $key }}" aria-expanded="true">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-bold small {{ $isLainnya ? 'text-warning' : 'text-primary' }}">{{ $k }}</span>
            <span class="badge bg-success bg-opacity-10 text-success fw-bold d-none" id="card_active_badge_{{ $key }}" style="font-size: 10px;">
                <i class="fas fa-check me-1"></i>Ada Isi
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="small fw-bold {{ $isLainnya ? 'text-warning' : 'text-primary' }}">Total: <span id="card_total_badge_{{ $key }}">0</span></span>
            <i class="fas fa-chevron-down text-muted small transition-transform" id="card_icon_{{ $key }}"></i>
        </div>
    </div>
    <div class="collapse show p-2.5" id="card_body_{{ $key }}">
        <div class="row g-2">
            <!-- Sub Panel 1: TAHANAN -->
            <div class="col-12 col-md-6">
                <div class="card-input-subbox border-danger border-opacity-25 bg-danger bg-opacity-10 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                        <span class="small fw-bold text-danger"><i class="fas fa-user-lock me-1"></i>TAHANAN</span>
                        <span class="badge bg-danger text-white px-2 py-0.5" id="card_sub_tahanan_{{ $key }}">0</span>
                    </div>
                    <div class="row g-1.5">
                        <div class="col-6">
                            <label class="d-block text-muted mb-0.5" style="font-size: 11px;">Dewasa (L)</label>
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('tahanan_dl_{{ $key }}', -1)">-</button>
                                <input type="number" id="card_tahanan_dl_{{ $key }}" class="form-control stepper-input" value="0" min="0" oninput="syncCardToTable('tahanan_dl_{{ $key }}', this.value)">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('tahanan_dl_{{ $key }}', 1)">+</button>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="d-block text-muted mb-0.5" style="font-size: 11px;">Dewasa (P)</label>
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('tahanan_dp_{{ $key }}', -1)">-</button>
                                <input type="number" id="card_tahanan_dp_{{ $key }}" class="form-control stepper-input" value="0" min="0" oninput="syncCardToTable('tahanan_dp_{{ $key }}', this.value)">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('tahanan_dp_{{ $key }}', 1)">+</button>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="d-block text-muted mb-0.5" style="font-size: 11px;">Anak (L)</label>
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('tahanan_al_{{ $key }}', -1)">-</button>
                                <input type="number" id="card_tahanan_al_{{ $key }}" class="form-control stepper-input" value="0" min="0" oninput="syncCardToTable('tahanan_al_{{ $key }}', this.value)">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('tahanan_al_{{ $key }}', 1)">+</button>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="d-block text-muted mb-0.5" style="font-size: 11px;">Anak (P)</label>
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('tahanan_ap_{{ $key }}', -1)">-</button>
                                <input type="number" id="card_tahanan_ap_{{ $key }}" class="form-control stepper-input" value="0" min="0" oninput="syncCardToTable('tahanan_ap_{{ $key }}', this.value)">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('tahanan_ap_{{ $key }}', 1)">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sub Panel 2: NARAPIDANA -->
            <div class="col-12 col-md-6">
                <div class="card-input-subbox border-info border-opacity-25 bg-info bg-opacity-10 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                        <span class="small fw-bold text-info"><i class="fas fa-users me-1"></i>NARAPIDANA</span>
                        <span class="badge bg-info text-white px-2 py-0.5" id="card_sub_napi_{{ $key }}">0</span>
                    </div>
                    <div class="row g-1.5">
                        <div class="col-6">
                            <label class="d-block text-muted mb-0.5" style="font-size: 11px;">Dewasa (L)</label>
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('narapidana_dl_{{ $key }}', -1)">-</button>
                                <input type="number" id="card_narapidana_dl_{{ $key }}" class="form-control stepper-input" value="0" min="0" oninput="syncCardToTable('narapidana_dl_{{ $key }}', this.value)">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('narapidana_dl_{{ $key }}', 1)">+</button>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="d-block text-muted mb-0.5" style="font-size: 11px;">Dewasa (P)</label>
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('narapidana_dp_{{ $key }}', -1)">-</button>
                                <input type="number" id="card_narapidana_dp_{{ $key }}" class="form-control stepper-input" value="0" min="0" oninput="syncCardToTable('narapidana_dp_{{ $key }}', this.value)">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('narapidana_dp_{{ $key }}', 1)">+</button>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="d-block text-muted mb-0.5" style="font-size: 11px;">Anak (L)</label>
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('narapidana_al_{{ $key }}', -1)">-</button>
                                <input type="number" id="card_narapidana_al_{{ $key }}" class="form-control stepper-input" value="0" min="0" oninput="syncCardToTable('narapidana_al_{{ $key }}', this.value)">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('narapidana_al_{{ $key }}', 1)">+</button>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="d-block text-muted mb-0.5" style="font-size: 11px;">Anak (P)</label>
                            <div class="input-group input-group-sm">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('narapidana_ap_{{ $key }}', -1)">-</button>
                                <input type="number" id="card_narapidana_ap_{{ $key }}" class="form-control stepper-input" value="0" min="0" oninput="syncCardToTable('narapidana_ap_{{ $key }}', this.value)">
                                <button type="button" class="btn btn-outline-secondary stepper-btn" onclick="stepCardInput('narapidana_ap_{{ $key }}', 1)">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

