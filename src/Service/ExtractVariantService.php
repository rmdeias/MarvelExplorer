<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class ExtractVariantService
{
    public function __construct(private HttpClientInterface $client) {}

    /**
     * Enriches the variants of a comic by fetching full comic details from the API.
     *
     * @param array $comicDetailsData The comic data array containing 'variants' (array of Marvel IDs)
     * @param string $baseUrl The base URL of the API
     * @return array The enriched comic data with full variants
     */
    public function enrichVariants(array $comicDetailsData, string $baseUrl): array
    {
        $variantsData = [];

        foreach ($comicDetailsData['variants'] ?? [] as $variantId) {
            if (!empty($variantId)) {
                try {
                    $variantData = $this->client->request(
                        'GET',
                        $baseUrl . '/api/comics/' . $variantId
                    )->toArray();

                    if (!empty($variantData['thumbnail'])) {
                        $variantsData[] = [
                            'marvelId' => $variantData['marvelId'] ?? null,
                            'title' => $variantData['title'] ?? null,
                            'description' => $variantData['description'],
                            'slug' => $variantData['slug'] ?? null,
                            'thumbnail' => $variantData['thumbnail'] ?? null,
                        ];
                    }
                } catch (\Exception $e) {
                    // Optional: log or skip the variant if API call fails
                }
            }
        }

        $comicDetailsData['variants'] = $variantsData;

        return $comicDetailsData;
    }
}
