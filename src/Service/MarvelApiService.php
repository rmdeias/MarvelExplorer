<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class MarvelApiService
{
    private HttpClientInterface $client;
    private string $baseUrl;
    private string $publicKey;
    private string $privateKey;

    public function __construct(HttpClientInterface $client, string $publicKey, string $privateKey)
    {
        $this->client = $client;
        $this->baseUrl = 'https://gateway.marvel.com/v1/public';
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }


    /**
     * @param int $limit
     * @param int $offset
     * @return array
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getCharacters(int $limit = 10, int $offset = 0): array
    {
        $data = [];
        $timestamp = time();
        $hash = md5($timestamp . $this->privateKey . $this->publicKey);
        try {
            $response = $this->client->request('GET', $this->baseUrl . '/characters', [
                'query' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'ts' => $timestamp,
                    'apikey' => $this->publicKey,
                    'hash' => $hash,
                ],
            ]);

            foreach ($response->toArray()['data']['results'] as $character) {

                $data[] = [$character['id'],
                    $character['name'],
                    $character['description'],
                    $character['thumbnail'],
                    $character['comics']['items']];
            }

            return $data;

        } catch (TransportExceptionInterface $e) {
            // Erreur réseau
            throw new \RuntimeException('Connection error:' . $e->getMessage());
        } catch (ClientExceptionInterface $e) {
            // Erreur client (4xx)
            throw new \RuntimeException('Client error : ' . $e->getMessage());
        } catch (ServerExceptionInterface $e) {
            // Erreur serveur (5xx)
            throw new \RuntimeException('Server error : ' . $e->getMessage());
        } catch (RedirectionExceptionInterface $e) {
            throw new \RuntimeException('Redirection error : ' . $e->getMessage());
        } catch (DecodingExceptionInterface $e) {
            // Invalid JSON
            throw new \RuntimeException('Unable to decode response : ' . $e->getMessage());
        }

    }

}
