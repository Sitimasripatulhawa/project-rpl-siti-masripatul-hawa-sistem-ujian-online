<?php
/**
 * WhatsApp Gateway Service using Fonnte API (https://fonnte.com)
 * AI Online Exam & Proctoring System
 */

class FonnteService
{
    private string $token;
    private string $apiUrl;

    public function __construct()
    {
        $this->token = defined('FONNTE_TOKEN') ? trim(FONNTE_TOKEN) : '';
        $this->apiUrl = defined('FONNTE_API_URL') ? trim(FONNTE_API_URL) : 'https://api.fonnte.com/send';
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
     * @return array ['success' => bool, 'message' => string, 'simulated' => bool, 'response' => ?array]
     */
    public function sendMessage(string $targetPhone, string $message): array
    {
        // 1. Check if we should run in local development / simulation mode
        if (!$this->isConfigured()) {
            return [
                'success' => true,
                'simulated' => true,
                'message' => 'Mode Simulasi Aktif: Token Fonnte belum diisi di .env. Pesan disimulasikan.',
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
                'message' => 'cURL Error saat menghubungi gateway Fonnte: ' . $err,
                'response' => null
            ];
        }

        $data = json_decode($response, true);
        if ($httpCode === 200 && isset($data['status']) && $data['status'] === true) {
            return [
                'success' => true,
                'simulated' => false,
                'message' => 'Pesan WhatsApp berhasil dikirim via Fonnte.',
                'response' => $data
            ];
        }

        $errorReason = $data['reason'] ?? ($data['message'] ?? 'Gateway Fonnte menolak pengiriman pesan (HTTP ' . $httpCode . ')');
        return [
            'success' => false,
            'simulated' => false,
            'message' => $errorReason,
            'response' => $data
        ];
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
        
        // Store OTP in session if in simulation mode so the UI can helpfully inform the developer
        if ($result['simulated']) {
            $_SESSION['dev_simulated_otp'] = [
                'phone' => $targetPhone,
                'otp' => $otpCode,
                'time' => date('H:i:s')
            ];
        }

        return $result;
    }
}
