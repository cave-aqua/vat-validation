<?php

declare(strict_types=1);

namespace App\VatServices;

use Symfony\Component\HttpFoundation\Request;

interface VatServiceInterface
{
    public function getResult(Request $request): array;
}
