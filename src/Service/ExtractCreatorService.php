<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service responsible for extracting and enriching creators of a comic.
 *
 * This service fetches complete creator information via the API
 * and adds the creator's role from the comic data.
 */
readonly class ExtractCreatorService
{
    public function __construct(private HttpClientInterface $client)
    {
    }

    /**
     * Enriches the creators data of a comic by fetching full creator details
     * from the API and adding the role from the comic.
     *
     * @param array $comicDetailsData The comic data array containing creators
     * @param string $baseUrl The base URL of the API
     * @return array The enriched comic data array with full creator information
     */
    public function enrichCreators(array $comicDetailsData, string $baseUrl): array
    {
        $creatorsDatas = [];

        foreach ($comicDetailsData['creators'] ?? [] as $creatorInfo) {
            if (!empty($creatorInfo['marvelCreatorId'])) {
                try {
                    $creatorData = $this->client->request(
                        'GET',
                        $baseUrl . '/api/creators/' . $creatorInfo['marvelCreatorId']
                    )->toArray();

                    // Add the comic role to the creator data
                    $creatorData['role'] = $creatorInfo['role'];

                    $creatorsDatas[] = $creatorData;
                } catch (\Exception $e) {
                    // Optional: log or skip the creator if API call fails
                }
            }
        }

        // Replace original creators with enriched data
        $comicDetailsData['creators'] = $creatorsDatas;

        return $comicDetailsData;
    }
}
