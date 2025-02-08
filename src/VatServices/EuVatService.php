<?php

declare(strict_types=1);

namespace App\VatServices;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class EuVatService implements VatServiceInterface
{
    public function __construct(private TagAwareCacheInterface $cachePool) {}

    private array $vatRegex = [
        'AT' => '/^ATU\d{8}$/', // Austria
        'BE' => '/^BE0\d{9}$/', // Belgium
        'BG' => '/^BG\d{9,10}$/', // Bulgaria
        'HR' => '/^HR\d{11}$/', // Croatia
        'CY' => '/^CY\d{8}[A-Z]$/', // Cyprus
        'CZ' => '/^CZ\d{8,10}$/', // Czech Republic
        'DK' => '/^DK\d{8}$/', // Denmark
        'EE' => '/^EE\d{9}$/', // Estonia
        'FI' => '/^FI\d{8}$/', // Finland
        'FR' => '/^FR[A-Z0-9]{2}\d{9}$/', // France
        'DE' => '/^DE\d{9}$/', // Germany
        'GR' => '/^GR\d{9}$/', // Greece
        'HU' => '/^HU\d{8}$/', // Hungary
        'IE' => '/^IE\d{7}[A-Z]{1,2}$/', // Ireland
        'IT' => '/^IT\d{11}$/', // Italy
        'LV' => '/^LV\d{11}$/', // Latvia
        'LT' => '/^LT\d{9,12}$/', // Lithuania
        'LU' => '/^LU\d{8}$/', // Luxembourg
        'MT' => '/^MT\d{8}$/', // Malta
        'NL' => '/^NL\d{9}B\d{2}$/', // Netherlands
        'PL' => '/^PL\d{10}$/', // Poland
        'PT' => '/^PT\d{9}$/', // Portugal
        'RO' => '/^RO\d{2,10}$/', // Romania
        'SK' => '/^SK\d{10}$/', // Slovakia
        'SI' => '/^SI\d{8}$/', // Slovenia
        'ES' => '/^ES[A-Z0-9]\d{7}[A-Z0-9]$/', // Spain
        'SE' => '/^SE\d{12}$/', // Sweden
    ];

    public function getResult(Request $request): array
    {
        if ($request->getPayload()->get('vat-id') === null || $request->getPayload()->get('country-code') === null) {
            return
                [
                    'message' => 'No input given.'
                ];
        }

        $vatId = $request->getPayload()->get('vat-id');
        $countryCode = $request->getPayload()->get('country-code');

        if (!$this->isValidVatId($countryCode, $vatId)) {
            return
                [
                    'message' => 'Not a valid VAT id given.'
                ];
        }

        //Adds caching to prevent unneccesary API calls
        $addressResult = $this->cachePool->get("$countryCode" . "$vatId", function (ItemInterface $item) use ($vatId, $countryCode) {
            $item->expiresAfter(600);

            $res = $this->getAddress($countryCode, $vatId);

            return $res;
        });


        if (array_key_exists('valid', $addressResult) && !$addressResult['valid']) {
            return
                [
                    'message' => 'Not a valid VAT id given.'
                ];
        }

        return $addressResult;
    }

    private function isValidVatId(string $countryCode, string $vatId): bool
    {
        return (bool)preg_match($this->vatRegex[$countryCode], $countryCode . $vatId);
    }

    private function getAddress(string $countryCode, string $vatId): array
    {
        $result = $this->getAddressApiResult($countryCode, $vatId);

        return !$result ? $result : $this->formatAddress($result);
    }

    private function getAddressApiResult(string $countryCode, string $vatId): array
    {
        $client = new Client();

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $body = json_encode(
            [
                "countryCode" => $countryCode,
                "vatNumber" => $vatId
            ]
        );

        $request = new Psr7Request('POST', 'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number', $headers, $body);
        $res = $client->send($request);

        return json_decode($res->getBody()->getContents(), true);
    }

    private function formatAddress(array $addressResult)
    {
        $formattedResult = [];

        foreach ($addressResult as $key => $value) {
            if ($key === 'name') {
                $formattedResult['Company name'] = $value;
            }

            if ($key === 'address') {
                $splittedOutAddress = explode("\n", $value);
                $splittedOutAddress = array_filter($splittedOutAddress, function ($el) {
                    if (!empty($el)) {
                        return trim($el);
                    }
                });

                //Make sure clean logical keys
                $splittedOutAddress = array_values($splittedOutAddress);

                $formattedResult['Street'] = $splittedOutAddress[0];
                $formattedResult['City'] = $splittedOutAddress[1];
            }
        }

        return ['addressParts' => $formattedResult];
    }
}
