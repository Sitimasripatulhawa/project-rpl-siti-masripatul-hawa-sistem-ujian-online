/**
 * Dual Camera Proctoring Manager
 * AI Online Exam & Proctoring System (Tahap 9)
 * Built with HTML5 WebRTC getUserMedia API
 */

class ProctoringCameraManager {
    constructor(config = {}) {
        this.config = Object.assign({
            camera1VideoId: 'camera1Video',
            camera2VideoId: 'camera2Video',
            camera1SelectId: 'camera1Select',
            camera2SelectId: 'camera2Select',
            camera1StatusId: 'camera1Status',
            camera2StatusId: 'camera2Status',
            micStatusId: 'micStatus',
            cameraErrorBoxId: 'cameraErrorBox',
            camera2ContainerId: 'camera2Container',
            camera2FallbackId: 'camera2Fallback',
            btnContinueId: 'btnEnterExam',
            enableProctoring: true,
            onStatusChange: null
        }, config);

        this.stream1 = null;
        this.stream2 = null;
        this.videoDevices = [];
        this.isCamera1Ready = false;
        this.isCamera2Ready = false;
        this.isDualCameraSupported = false;

        this.init();
    }

    /**
     * Check WebRTC support and bind device change listeners
     */
    init() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            this.showError('Browser atau protokol HTTP Anda tidak mendukung WebRTC getUserMedia API. Gunakan browser modern (Google Chrome, Firefox, Edge).');
            return;
        }

        // Listen for hardware plug/unplug changes
        navigator.mediaDevices.addEventListener('devicechange', () => {
            console.log('[Proctoring] Hardware device change detected.');
            this.refreshDevices();
        });

        // Clean up streams on page unload
        window.addEventListener('beforeunload', () => {
            this.stopAllStreams();
        });
    }

    /**
     * Request initial permissions and setup cameras
     */
    async requestPermissions() {
        this.clearError();
        this.updateCamera1Status('checking', 'Meminta Izin Kamera...');
        this.updateCamera2Status('checking', 'Memeriksa Perangkat...');

        try {
            // 1. Initial getUserMedia to trigger browser permission dialog
            const tempStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            });

            // Check microphone
            const audioTracks = tempStream.getAudioTracks();
            if (audioTracks.length > 0) {
                this.updateMicStatus(true, 'Mikrofon Aktif');
            } else {
                this.updateMicStatus(false, 'Mikrofon Tidak Terdeteksi');
            }

            // Stop temporary stream before starting dedicated device streams
            tempStream.getTracks().forEach(t => t.stop());

            // 2. Enumerate video devices
            await this.refreshDevices();

        } catch (err) {
            this.handlePermissionError(err);
        }
    }

    /**
     * Enumerate available video inputs and start streams
     */
    async refreshDevices() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            this.videoDevices = devices.filter(d => d.kind === 'videoinput');

            console.log(`[Proctoring] Detected ${this.videoDevices.length} camera device(s).`);

            // Populate selector dropdowns
            this.populateDeviceSelectors();

            if (this.videoDevices.length === 0) {
                this.showError('Tidak ditemukan perangkat kamera (webcam) pada komputer atau laptop Anda.');
                this.updateCamera1Status('offline', 'Kamera Tidak Ditemukan');
                this.updateContinueButton();
                return;
            }

            // Start Camera 1 (Primary Front/Webcam)
            const cam1DeviceId = this.videoDevices[0].deviceId;
            await this.startCamera1(cam1DeviceId);

            // Handle Camera 2 (Dual Camera vs Single Camera Fallback)
            if (this.videoDevices.length >= 2) {
                this.isDualCameraSupported = true;
                this.showCamera2Container(true);
                const cam2DeviceId = this.videoDevices[1].deviceId;
                await this.startCamera2(cam2DeviceId);
            } else {
                this.isDualCameraSupported = false;
                this.showCamera2Container(false);
                this.updateCamera2Status('warning', 'Mode Kamera Tunggal');
            }

            this.updateContinueButton();

        } catch (err) {
            console.error('[Proctoring] Error enumerating devices:', err);
            this.showError('Gagal mendeteksi perangkat media: ' + (err.message || err));
        }
    }

    /**
     * Start Primary Camera stream
     */
    async startCamera1(deviceId = null) {
        try {
            if (this.stream1) {
                this.stream1.getTracks().forEach(t => t.stop());
            }

            const constraints = {
                video: deviceId ? { deviceId: { exact: deviceId } } : { facingMode: 'user' },
                audio: false
            };

            this.stream1 = await navigator.mediaDevices.getUserMedia(constraints);
            const videoEl = document.getElementById(this.config.camera1VideoId);
            if (videoEl) {
                videoEl.srcObject = this.stream1;
                videoEl.play().catch(e => console.warn('Video 1 auto-play:', e));
            }

            // Track disconnect listener
            const track = this.stream1.getVideoTracks()[0];
            if (track) {
                track.onended = () => {
                    console.warn('[Proctoring] Camera 1 track ended/disconnected!');
                    this.handleCameraDisconnect(1);
                };
            }

            this.isCamera1Ready = true;
            this.updateCamera1Status('online', 'Terhubung (Kamera Utama)');
            this.updateContinueButton();

        } catch (err) {
            console.error('[Proctoring] Failed to start Camera 1:', err);
            this.isCamera1Ready = false;
            this.updateCamera1Status('offline', 'Gagal Terhubung');
            this.showError('Kamera 1 gagal diakses: ' + (err.message || 'Perangkat mungkin sedang digunakan aplikasi lain.'));
            this.updateContinueButton();
        }
    }

    /**
     * Start Secondary Camera stream
     */
    async startCamera2(deviceId = null) {
        try {
            if (this.stream2) {
                this.stream2.getTracks().forEach(t => t.stop());
            }

            const constraints = {
                video: deviceId ? { deviceId: { exact: deviceId } } : { facingMode: 'environment' },
                audio: false
            };

            this.stream2 = await navigator.mediaDevices.getUserMedia(constraints);
            const videoEl = document.getElementById(this.config.camera2VideoId);
            if (videoEl) {
                videoEl.srcObject = this.stream2;
                videoEl.play().catch(e => console.warn('Video 2 auto-play:', e));
            }

            // Track disconnect listener
            const track = this.stream2.getVideoTracks()[0];
            if (track) {
                track.onended = () => {
                    console.warn('[Proctoring] Camera 2 track ended/disconnected!');
                    this.handleCameraDisconnect(2);
                };
            }

            this.isCamera2Ready = true;
            this.updateCamera2Status('online', 'Terhubung (Kamera Sekunder)');

        } catch (err) {
            console.error('[Proctoring] Failed to start Camera 2:', err);
            this.isCamera2Ready = false;
            this.updateCamera2Status('warning', 'Kamera 2 Tidak Aktif');
        }
    }

    /**
     * Handle hardware disconnection
     */
    handleCameraDisconnect(camIndex) {
        if (camIndex === 1) {
            this.isCamera1Ready = false;
            this.updateCamera1Status('offline', 'Terputus (Disconnected)');
            this.showError('Peringatan: Kamera Utama (Camera 1) Anda terputus! Harap hubungkan kembali dan klik tombol Uji Ulang Kamera.');
        } else {
            this.isCamera2Ready = false;
            this.updateCamera2Status('offline', 'Terputus (Disconnected)');
        }

        this.updateContinueButton();

        if (typeof this.config.onStatusChange === 'function') {
            this.config.onStatusChange({
                camera1: this.isCamera1Ready,
                camera2: this.isCamera2Ready,
                disconnected: camIndex
            });
        }
    }

    /**
     * Populate <select> dropdowns with detected cameras
     */
    populateDeviceSelectors() {
        const sel1 = document.getElementById(this.config.camera1SelectId);
        const sel2 = document.getElementById(this.config.camera2SelectId);

        if (sel1) {
            sel1.innerHTML = '';
            this.videoDevices.forEach((dev, idx) => {
                const opt = document.createElement('option');
                opt.value = dev.deviceId;
                opt.textContent = dev.label || `Kamera ${idx + 1} (Video Input)`;
                sel1.appendChild(opt);
            });

            sel1.onchange = (e) => this.startCamera1(e.target.value);
        }

        if (sel2) {
            sel2.innerHTML = '';
            this.videoDevices.forEach((dev, idx) => {
                const opt = document.createElement('option');
                opt.value = dev.deviceId;
                opt.textContent = dev.label || `Kamera ${idx + 1} (Video Input)`;
                if (idx === 1) opt.selected = true;
                sel2.appendChild(opt);
            });

            sel2.onchange = (e) => this.startCamera2(e.target.value);
        }
    }

    /**
     * Toggle display between dual camera view and single camera fallback card
     */
    showCamera2Container(showDual) {
        const c2Container = document.getElementById(this.config.camera2ContainerId);
        const c2Fallback = document.getElementById(this.config.camera2FallbackId);

        if (showDual) {
            if (c2Container) c2Container.classList.remove('d-none');
            if (c2Fallback) c2Fallback.classList.add('d-none');
        } else {
            if (c2Container) c2Container.classList.add('d-none');
            if (c2Fallback) c2Fallback.classList.remove('d-none');
        }
    }

    /**
     * Handle Permission Errors with helpful browser instructions
     */
    handlePermissionError(err) {
        console.error('[Proctoring] Permission error:', err);
        this.isCamera1Ready = false;
        this.updateCamera1Status('offline', 'Izin Ditolak');
        this.updateContinueButton();

        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            this.showError(`
                <strong>Akses Kamera Diblokir / Ditolak oleh Browser:</strong>
                <p class="mb-1 mt-1 text-xs">Untuk mengikuti ujian dengan sistem pengawasan, Anda wajib mengizinkan akses kamera:</p>
                <ol class="mb-0 ps-3 text-xs">
                    <li>Klik ikon <strong>Kamera / Gembok</strong> di sisi kiri bilah alamat (URL bar) browser Anda.</li>
                    <li>Ubah izin Kamera & Mikrofon menjadi <strong>"Allow" (Izinkan)</strong>.</li>
                    <li>Muat ulang (Refresh) halaman ini atau klik tombol <em>Uji Ulang Kamera</em> di bawah.</li>
                </ol>
            `);
        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
            this.showError('Perangkat kamera (webcam) tidak ditemukan. Pastikan webcam terpasang dengan baik pada komputer Anda.');
        } else {
            this.showError('Terjadi kesalahan saat meminta akses kamera: ' + (err.message || err.name));
        }
    }

    /**
     * Update Camera 1 Status Pill UI
     */
    updateCamera1Status(type, label) {
        const el = document.getElementById(this.config.camera1StatusId);
        if (!el) return;

        let dotClass = 'dot-online';
        if (type === 'checking' || type === 'warning') dotClass = 'dot-warning';
        if (type === 'offline') dotClass = 'dot-offline';

        el.innerHTML = `<span class="camera-status-dot ${dotClass}"></span> <span>${label}</span>`;
    }

    /**
     * Update Camera 2 Status Pill UI
     */
    updateCamera2Status(type, label) {
        const el = document.getElementById(this.config.camera2StatusId);
        if (!el) return;

        let dotClass = 'dot-online';
        if (type === 'checking' || type === 'warning') dotClass = 'dot-warning';
        if (type === 'offline') dotClass = 'dot-offline';

        el.innerHTML = `<span class="camera-status-dot ${dotClass}"></span> <span>${label}</span>`;
    }

    /**
     * Update Microphone Status UI
     */
    updateMicStatus(isOnline, label) {
        const el = document.getElementById(this.config.micStatusId);
        if (!el) return;

        if (isOnline) {
            el.className = 'badge bg-success-subtle text-success text-xs';
            el.innerHTML = '<i class="bi bi-mic-fill me-1"></i> ' + label;
        } else {
            el.className = 'badge bg-secondary-subtle text-muted text-xs';
            el.innerHTML = '<i class="bi bi-mic-mute-fill me-1"></i> ' + label;
        }
    }

    /**
     * Update Continue Button disabled state based on camera readiness and integrity pledge
     */
    updateContinueButton() {
        const btn = document.getElementById(this.config.btnContinueId);
        const pledge = document.getElementById('pledgeCheck');
        if (!btn) return;

        if (this.config.enableProctoring) {
            const canProceed = this.isCamera1Ready && (!pledge || pledge.checked);
            btn.disabled = !canProceed;

            if (!this.isCamera1Ready) {
                btn.title = 'Kamera utama wajib aktif dan terhubung untuk memulai ujian.';
            } else if (pledge && !pledge.checked) {
                btn.title = 'Harap centang Pakta Integritas.';
            } else {
                btn.title = 'Klik untuk memasuki ruang ujian';
            }
        } else {
            btn.disabled = pledge ? !pledge.checked : false;
        }
    }

    /**
     * Display alert error box
     */
    showError(htmlMessage) {
        const box = document.getElementById(this.config.cameraErrorBoxId);
        if (box) {
            box.innerHTML = htmlMessage;
            box.classList.remove('d-none');
        }
    }

    /**
     * Clear error box
     */
    clearError() {
        const box = document.getElementById(this.config.cameraErrorBoxId);
        if (box) {
            box.innerHTML = '';
            box.classList.add('d-none');
        }
    }

    /**
     * Stop all camera tracks
     */
    stopAllStreams() {
        if (this.stream1) {
            this.stream1.getTracks().forEach(t => t.stop());
            this.stream1 = null;
        }
        if (this.stream2) {
            this.stream2.getTracks().forEach(t => t.stop());
            this.stream2 = null;
        }
        this.isCamera1Ready = false;
        this.isCamera2Ready = false;
    }
}
