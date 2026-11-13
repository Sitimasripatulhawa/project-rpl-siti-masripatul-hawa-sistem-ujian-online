/**
 * AI Proctoring Client Engine (Tahap 10 MVP)
 * AI Online Exam & Proctoring System
 * 
 * Features:
 * - Browser-based realistic face & anomaly detection
 * - Minimal resource consumption (interval-based downscaled canvas processing)
 * - Cooldown & debounce manager (prevents event flooding)
 * - Configurable thresholds & evidence snapshotting
 * - Privacy-by-design: No full video recording, local detection only
 */

class AiProctoringEngine {
    constructor(options = {}) {
        this.examId = options.examId || 0;
        this.participantId = options.participantId || 0;
        this.videoElement = options.videoElement || null;
        this.statusElement = options.statusElement || null;
        this.apiEndpoint = options.apiEndpoint || (window.BASE_URL ? window.BASE_URL + '/student/logProctoringEvent' : '/student/logProctoringEvent');

        // Configurable thresholds
        this.config = Object.assign({
            checkIntervalMs: 1800,           // Analyze 1 frame every 1.8 seconds (lightweight)
            faceMissingThresholdMs: 3000,    // Missing face must persist for >= 3s before event
            multipleFaceThresholdMs: 2000,   // Multiple faces must persist for >= 2s
            cameraBlockedThresholdMs: 3000,  // Blackout/blocked must persist for >= 3s
            suspiciousGazeThresholdMs: 4000, // Head/gaze averted for >= 4s
            eventCooldownMs: 15000,          // Cooldown between identical events
            criticalCooldownMs: 20000,       // Cooldown for critical events
            enableEvidenceSnapshot: true,    // Send low-res thumbnail evidence if available
            snapshotQuality: 0.5,            // JPEG compression quality
            canvasWidth: 320,                // Downscaled width for efficiency
            canvasHeight: 240                // Downscaled height for efficiency
        }, options.config || {});

        // Internal State
        this.isRunning = false;
        this.timerId = null;
        this.canvas = document.createElement('canvas');
        this.canvas.width = this.config.canvasWidth;
        this.canvas.height = this.config.canvasHeight;
        this.ctx = this.canvas.getContext('2d', { willReadFrequently: true });

        // Consecutive anomaly duration tracking
        this.anomalyDurations = {
            FACE_NOT_DETECTED: 0,
            MULTIPLE_FACE: 0,
            CAMERA_BLOCKED: 0,
            SUSPICIOUS_GAZE: 0
        };

        // Last logged timestamp per event type for debouncing
        this.lastLoggedTimes = {};

        // Feature detection: Browser Native FaceDetector API if available
        this.nativeFaceDetector = null;
        if (typeof window !== 'undefined' && 'FaceDetector' in window) {
            try {
                this.nativeFaceDetector = new window.FaceDetector({ fastMode: true, maxDetectedFaces: 4 });
                console.log('[AiProctoring] Using native Shape Detection API (FaceDetector).');
            } catch (e) {
                this.nativeFaceDetector = null;
            }
        }
    }

    /**
     * Start the AI proctoring monitor
     */
    start() {
        if (this.isRunning) return;
        if (!this.videoElement) {
            console.warn('[AiProctoring] Video element not assigned.');
            return;
        }

        this.isRunning = true;
        console.log('[AiProctoring] Engine started. Check interval:', this.config.checkIntervalMs, 'ms');

        this.timerId = setInterval(() => {
            this.analyzeFrame();
        }, this.config.checkIntervalMs);
    }

    /**
     * Stop the AI proctoring monitor and clear resources
     */
    stop() {
        this.isRunning = false;
        if (this.timerId) {
            clearInterval(this.timerId);
            this.timerId = null;
        }
        console.log('[AiProctoring] Engine stopped.');
    }

    /**
     * Process a single video frame on off-screen canvas
     */
    async analyzeFrame() {
        if (!this.isRunning || !this.videoElement) return;

        // Check if video is playing and ready
        if (this.videoElement.readyState < 2 || this.videoElement.paused || this.videoElement.ended) {
            return;
        }

        const videoW = this.videoElement.videoWidth || this.config.canvasWidth;
        const videoH = this.videoElement.videoHeight || this.config.canvasHeight;

        if (videoW === 0 || videoH === 0) return;

        // Draw downscaled frame to canvas
        this.ctx.drawImage(this.videoElement, 0, 0, this.config.canvasWidth, this.config.canvasHeight);

        // 1. Check for CAMERA_BLOCKED (dark/covered camera)
        const luminanceData = this.checkLuminanceAndBlocked();
        if (luminanceData.isBlocked) {
            this.handleAnomaly('CAMERA_BLOCKED', 'WARNING', {
                avg_luminance: luminanceData.avgLuminance,
                variance: luminanceData.variance
            });
            this.updateBadge('Kamera Tertutup / Gelap', 'warning');
            return;
        } else {
            this.resetAnomaly('CAMERA_BLOCKED');
        }

        // 2. Detect Faces
        let faces = [];
        try {
            if (this.nativeFaceDetector) {
                faces = await this.nativeFaceDetector.detect(this.canvas);
            } else {
                // Heuristic skin-tone and facial contour centroid clustering
                faces = this.detectFacesHeuristic();
            }
        } catch (err) {
            faces = this.detectFacesHeuristic();
        }

        const faceCount = faces.length;

        // 3. Evaluate Detected Face Counts
        if (faceCount === 0) {
            this.handleAnomaly('FACE_NOT_DETECTED', 'WARNING', {
                detail: 'No face detected in video frame'
            });
            this.resetAnomaly('MULTIPLE_FACE');
            this.resetAnomaly('SUSPICIOUS_GAZE');
            this.updateBadge('Wajah Tidak Terlihat', 'warning');
        } else if (faceCount > 1) {
            this.handleAnomaly('MULTIPLE_FACE', 'CRITICAL', {
                face_count: faceCount,
                detail: 'More than one face detected'
            });
            this.resetAnomaly('FACE_NOT_DETECTED');
            this.resetAnomaly('SUSPICIOUS_GAZE');
            this.updateBadge('Terdeteksi > 1 Orang', 'danger');
        } else {
            // Exactly 1 face: Normal condition
            this.resetAnomaly('FACE_NOT_DETECTED');
            this.resetAnomaly('MULTIPLE_FACE');

            // 4. Optional Check: Suspicious Gaze / Off-center looking
            const primaryFace = faces[0];
            const gazeAnomaly = this.checkGazeDeviation(primaryFace);
            if (gazeAnomaly.isSuspicious) {
                this.handleAnomaly('SUSPICIOUS_GAZE', 'WARNING', gazeAnomaly);
                this.updateBadge('Pandangan Berpaling', 'info');
            } else {
                this.resetAnomaly('SUSPICIOUS_GAZE');
                this.updateBadge('Wajah Terdeteksi', 'success');
            }
        }
    }

    /**
     * Compute average luminance and pixel variance to detect covered/blocked lens
     */
    checkLuminanceAndBlocked() {
        try {
            const imgData = this.ctx.getImageData(0, 0, this.config.canvasWidth, this.config.canvasHeight);
            const data = imgData.data;
            const totalPixels = data.length / 4;
            let sumLuminance = 0;

            // Sample every 4th pixel for high speed
            let sampled = 0;
            for (let i = 0; i < data.length; i += 16) {
                const r = data[i];
                const g = data[i + 1];
                const b = data[i + 2];
                // ITU-R BT.601 formula for relative luminance
                const lum = 0.299 * r + 0.587 * g + 0.114 * b;
                sumLuminance += lum;
                sampled++;
            }

            const avgLuminance = sumLuminance / (sampled || 1);
            // If completely black or near-zero (< 10 out of 255)
            const isBlocked = (avgLuminance < 10);

            return {
                isBlocked: isBlocked,
                avgLuminance: Math.round(avgLuminance * 10) / 10
            };
        } catch (e) {
            return { isBlocked: false, avgLuminance: 128 };
        }
    }

    /**
     * Lightweight heuristic face detector using skin-tone chrominance clustering
     * Runs 100% locally and offline without external ML dependencies
     */
    detectFacesHeuristic() {
        try {
            const w = this.config.canvasWidth;
            const h = this.config.canvasHeight;
            const imgData = this.ctx.getImageData(0, 0, w, h);
            const data = imgData.data;

            let skinPixelCount = 0;
            let skinXSum = 0;
            let skinYSum = 0;
            let leftHalfCount = 0;
            let rightHalfCount = 0;

            // Sample pixels with step 4 (16 bytes)
            for (let y = 0; y < h; y += 4) {
                for (let x = 0; x < w; x += 4) {
                    const idx = (y * w + x) * 4;
                    const r = data[idx];
                    const g = data[idx + 1];
                    const b = data[idx + 2];

                    // Standard YCbCr or RGB skin-color chromatic filter
                    // R > 95, G > 40, B > 20, max-min > 15, |R-G| > 15, R > G, R > B
                    const isSkin = (r > 70 && g > 35 && b > 20 &&
                        (Math.max(r, g, b) - Math.min(r, g, b) > 12) &&
                        Math.abs(r - g) > 8 && r > g && r > b);

                    if (isSkin) {
                        skinPixelCount++;
                        skinXSum += x;
                        skinYSum += y;
                        if (x < w * 0.4) leftHalfCount++;
                        if (x > w * 0.6) rightHalfCount++;
                    }
                }
            }

            const totalSampled = (w / 4) * (h / 4);
            const skinRatio = skinPixelCount / totalSampled;

            // Thresholds for face detection
            if (skinRatio < 0.04) {
                // Not enough skin tone to form a face
                return [];
            }

            // Check if two distinct clusters exist (e.g. two heads on left and right)
            if (leftHalfCount > totalSampled * 0.08 && rightHalfCount > totalSampled * 0.08) {
                return [
                    { boundingBox: { x: w * 0.2, y: h * 0.3, width: 80, height: 100 } },
                    { boundingBox: { x: w * 0.7, y: h * 0.3, width: 80, height: 100 } }
                ];
            }

            // Normal single face center
            const centerX = skinXSum / (skinPixelCount || 1);
            const centerY = skinYSum / (skinPixelCount || 1);

            return [
                {
                    boundingBox: {
                        x: Math.max(0, centerX - 50),
                        y: Math.max(0, centerY - 60),
                        width: 100,
                        height: 120
                    },
                    centerX: centerX,
                    centerY: centerY
                }
            ];
        } catch (e) {
            // Default to 1 face fallback on error to avoid false positives
            return [{ boundingBox: { x: 100, y: 80, width: 120, height: 120 } }];
        }
    }

    /**
     * Check if user's face is averted / gaze offset excessively
     */
    checkGazeDeviation(face) {
        const w = this.config.canvasWidth;
        const box = face.boundingBox || {};
        const centerX = face.centerX || (box.x + (box.width || 0) / 2);

        // Normalized horizontal center (-1.0 to 1.0)
        const normX = (centerX - (w / 2)) / (w / 2);

        // If face is shifted > 65% to either extreme border
        const isSuspicious = Math.abs(normX) > 0.65;

        return {
            isSuspicious: isSuspicious,
            horizontal_deviation: Math.round(normX * 100) / 100
        };
    }

    /**
     * Handle detected anomaly with debounce & cooldown manager
     */
    handleAnomaly(eventType, severity, metadata = {}) {
        // Accumulate anomaly duration
        this.anomalyDurations[eventType] = (this.anomalyDurations[eventType] || 0) + this.config.checkIntervalMs;
        const duration = this.anomalyDurations[eventType];

        // Determine threshold for this anomaly type
        let thresholdMs = this.config.faceMissingThresholdMs;
        if (eventType === 'MULTIPLE_FACE') thresholdMs = this.config.multipleFaceThresholdMs;
        if (eventType === 'CAMERA_BLOCKED') thresholdMs = this.config.cameraBlockedThresholdMs;
        if (eventType === 'SUSPICIOUS_GAZE') thresholdMs = this.config.suspiciousGazeThresholdMs;

        // Anomaly must persist for >= threshold duration
        if (duration < thresholdMs) {
            return;
        }

        // Check cooldown window to prevent event spam
        const now = Date.now();
        const lastLog = this.lastLoggedTimes[eventType] || 0;
        const cooldown = (severity === 'CRITICAL') ? this.config.criticalCooldownMs : this.config.eventCooldownMs;

        if (now - lastLog < cooldown) {
            return;
        }

        // Trigger and send event
        this.lastLoggedTimes[eventType] = now;
        metadata.duration_ms = duration;

        this.sendEvent(eventType, severity, metadata);
    }

    /**
     * Reset consecutive duration when condition normalizes
     */
    resetAnomaly(eventType) {
        if (this.anomalyDurations[eventType]) {
            this.anomalyDurations[eventType] = 0;
        }
    }

    /**
     * Dispatch event to backend API endpoint with optional evidence snapshot
     */
    async sendEvent(eventType, severity, metadata = {}) {
        console.warn(`[AiProctoring] LOGGING EVENT: ${eventType} (${severity})`, metadata);

        let evidenceBase64 = null;
        if (this.config.enableEvidenceSnapshot && this.canvas) {
            try {
                evidenceBase64 = this.canvas.toDataURL('image/jpeg', this.config.snapshotQuality);
            } catch (e) {
                evidenceBase64 = null;
            }
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const payload = {
            exam_id: this.examId,
            event_type: eventType,
            severity: severity,
            timestamp: new Date().toISOString(),
            metadata: metadata,
            evidence_base64: evidenceBase64,
            camera_source: 'primary_camera',
            csrf_token: csrfToken
        };

        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            if (result && result.success) {
                console.log(`[AiProctoring] Event ${eventType} saved to server. ID:`, result.event_id);
            }
        } catch (err) {
            console.error('[AiProctoring] Failed to transmit event to server:', err);
        }
    }

    /**
     * Update UI status badge in PIP proctoring widget
     */
    updateBadge(text, colorClass) {
        if (!this.statusElement) return;

        const badgeClassMap = {
            success: 'bg-success text-white',
            warning: 'bg-warning text-dark',
            danger: 'bg-danger text-white',
            info: 'bg-info text-dark'
        };

        this.statusElement.className = 'badge px-2 py-1 ' + (badgeClassMap[colorClass] || 'bg-secondary text-white');
        this.statusElement.textContent = text;
    }
}

// Export to window
if (typeof window !== 'undefined') {
    window.AiProctoringEngine = AiProctoringEngine;
}
