<?php
/**
 * WhatsApp Gateway Service using Fonnte API (https://fonnte.com)
 * AI Online Exam & Proctoring System
 */

class FonnteService
{
    private string $token;
    private string $apiUrl;

    public function __construct(?string $token = null, ?string $apiUrl = null)
    {
        $this->token = $token !== null ? trim($token) : (defined('FONNTE_TOKEN') ? trim(FONNTE_TOKEN) : '');
        $this->apiUrl = $apiUrl !== null ? trim($apiUrl) : (defined('FONNTE_API_URL') ? trim(FONNTE_API_URL) : 'https://api.fonnte.com/send');
    }

    /**
     * Check if a real Fonnte token has been configured
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->token) && $this->token !== 'your_fonnte_token_here';
    }

    /**
     * Send a WhatsApp message via Fonnte
     *
     * @param string $targetPhone Target phone number
     * @param string $message Text content
     * @return array ['success' => bool, 'message' => string, 'simulated' => bool, 'raw_reason' => string, 'response' => ?array]
     */
    public function sendMessage(string $targetPhone, string $message): array
    {
        // 1. Check if we should run in local development / simulation mode
        if (!$this->isConfigured()) {
            return [
                'success' => true,
                'simulated' => true,
                'message' => 'Mode Simulasi Aktif: Token Fonnte belum diisi di .env. Pesan disimulasikan.',
                'raw_reason' => 'simulation_mode',
                'response' => ['target' => $targetPhone, 'message' => $message]
            ];
        }

        // 2. Normalize Indonesian phone format (0812... -> 62812...)
        $cleanPhone = preg_replace('/[^0-9]/', '', $targetPhone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }

        // 3. Send HTTP POST to Fonnte API using cURL
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'target' => $cleanPhone,
                'message' => $message,
                'countryCode' => '62',
            ],
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->token
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err) {
            return [
                'success' => false,
                'simulated' => false,
                'message' => 'Gagal menghubungi gateway Fonnte (koneksi/jaringan bermasalah): ' . $err,
                'raw_reason' => 'curl_error: ' . $err,
                'response' => null
            ];
        }

        $data = json_decode($response, true);
        if ($httpCode === 200 && isset($data['status']) && $data['status'] === true) {
            return [
                'success' => true,
                'simulated' => false,
                'message' => 'Pesan WhatsApp berhasil dikirim via Fonnte.',
                'raw_reason' => 'ok',
                'response' => $data
            ];
        }

        $rawReason = $data['reason'] ?? ($data['message'] ?? 'Gateway Fonnte menolak pengiriman pesan (HTTP ' . $httpCode . ')');
        $friendlyMessage = $this->formatErrorMessage($rawReason);

        return [
            'success' => false,
            'simulated' => false,
            'message' => $friendlyMessage,
            'raw_reason' => $rawReason,
            'response' => $data
        ];
    }

    /**
     * Map raw Fonnte error reasons into clear, helpful Indonesian messages
     *
     * @param string $reason
     * @return string
     */
    private function formatErrorMessage(string $reason): string
    {
        $lower = strtolower(trim($reason));

        if (str_contains($lower, 'unknown token')) {
            return 'Token Fonnte tidak dikenali atau salah (unknown token). Silakan periksa Device Token di dashboard fonnte.com pada menu Device dan pastikan status perangkat Connected.';
        }

        if (str_contains($lower, 'device disconnected') || str_contains($lower, 'disconnected')) {
            return 'Perangkat WhatsApp di Fonnte sedang terputus (device disconnected). Silakan buka dashboard fonnte.com dan scan ulang QR code perangkat Anda.';
        }

        if (str_contains($lower, 'invalid country') || str_contains($lower, 'invalid target')) {
            return 'Format nomor tujuan WhatsApp tidak valid (' . htmlspecialchars($reason) . '). Pastikan nomor diawali dengan 08... atau 628...';
        }

        if (str_contains($lower, 'not registered') || str_contains($lower, 'unregistered')) {
            return 'Nomor tujuan belum terdaftar di WhatsApp. Pastikan nomor tujuan memiliki akun WhatsApp aktif.';
        }

        if (str_contains($lower, 'quota') || str_contains($lower, 'insufficient') || str_contains($lower, 'package')) {
            return 'Kuota pengiriman pesan Fonnte Anda telah habis atau paket kedaluwarsa. Silakan periksa kuota akun di fonnte.com.';
        }

        return 'Gateway Fonnte merespon: ' . $reason;
    }

    /**
     * Send OTP message specifically formatted for password reset
     *
     * @param string $targetPhone
     * @param string $recipientName
     * @param string $otpCode
     * @return array
     */
    public function sendOtpPasswordReset(string $targetPhone, string $recipientName, string $otpCode): array
    {
        $appName = defined('APP_NAME') ? APP_NAME : 'AI Online Exam & Proctoring System';

        $msg  = "🔐 *KODE VERIFIKASI OTP - {$appName}*\n\n";
        $msg .= "Halo *{$recipientName}*,\n";
        $msg .= "Kami menerima permintaan pemulihan kata sandi untuk akun Anda.\n\n";
        $msg .= "Berikut adalah 6 digit kode OTP Anda:\n";
        $msg .= "👉 *{$otpCode}*\n\n";
        $msg .= "⏰ *Masa Berlaku:* 10 Menit\n";
        $msg .= "⚠️ *PENTING:* Jangan berikan kode OTP ini kepada siapa pun, termasuk pihak sekolah/administrator demi keamanan akun Anda.\n\n";
        $msg .= "_Pesan otomatis oleh Sistem Ujian Online._";

        $result = $this->sendMessage($targetPhone, $msg);
        
        // If simulation mode OR sending failed, save OTP in session so user is never blocked
        if ($result['simulated'] || !$result['success']) {
            $_SESSION['dev_simulated_otp'] = [
                'phone' => $targetPhone,
                'otp' => $otpCode,
                'is_error' => !$result['success'],
                'error_message' => !$result['success'] ? $result['message'] : null,
                'time' => date('H:i:s')
            ];
        } else {
            // Real WhatsApp message delivered to gateway successfully
            unset($_SESSION['dev_simulated_otp']);
        }

        return $result;
    }
}

