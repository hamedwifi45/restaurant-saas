<?php

namespace App\View\Components;

use chillerlan\QRCode\QRCode;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OfflineQrCode extends Component
{
    public string $dataUri;

    public function __construct(
        string $value,
        public string $alt = 'QR code',
        public int $size = 96,
    ) {
        $this->dataUri = (new QRCode)->render($value);
    }

    public function render(): View
    {
        return view('components.offline-qr-code');
    }
}
