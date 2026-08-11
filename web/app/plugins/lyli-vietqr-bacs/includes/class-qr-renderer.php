<?php

namespace Lyli\VietQRBACS;

use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

final class QR_Renderer
{
    public function data_uri(string $payload): string
    {
        if ('' === $payload) {
            throw new \InvalidArgumentException('QR payload is required.');
        }
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => true,
            'addQuietzone' => true,
            'scale' => 6,
        ]);
        $output = (new QRCode($options))->render($payload);
        if (! is_string($output) || ! str_starts_with($output, 'data:image/svg+xml;base64,')) {
            throw new \RuntimeException('The QR renderer returned an unexpected output type.');
        }
        return $output;
    }
}
