<?php

namespace App\Command\ElasticSearchCommands;

use App\Repository\ComicRepository;
use App\Service\ElasticSearchServices\ElasticIndexService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * IndexComicsCommand
 *
 * This command indexes all comics from the database into Elasticsearch.
 *
 * Steps performed:
 * 1. Creates the comics index in Elasticsearch if it does not exist.
 * 2. Fetches comics from MySQL in batches to avoid memory issues.
 * 3. Indexes each comic into Elasticsearch.
 *
 * Usage:
 *  php -d memory_limit=512M bin/console app:index-comics
 *
 * Notes:
 * - Batch size is 50 by default to manage memory.
 * - Doctrine's toIterable() is used to efficiently stream results.
 */
#[AsCommand(
    name: 'app:index-comics',
    description: 'Creates the comics index in Elasticsearch and indexes all comics from MySQL.'
)]
class IndexComicsCommand extends Command
{
    public function __construct(
        private readonly ComicRepository     $comicRepository,
        private readonly ElasticIndexService $elasticIndexService
    ) {
        parent::__construct();
    }

    /**
     * Executes the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int Command exit status
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Creating comics index if necessary...</info>');
        $this->elasticIndexService->createComicsIndex();

        $output->writeln('<info>Indexing comics...</info>');

        $client = $this->elasticIndexService->getClient();
        $batchSize = 50;
        $i = 0;

        $em = $this->comicRepository->getEntityManager();

        $qb = $this->comicRepository->createQueryBuilder('c')
            ->select(
                'c.marvelId AS marvelId',
                'c.title AS title',
                'c.date AS date',
                'c.thumbnail AS thumbnail'
            );

        foreach ($qb->getQuery()->toIterable([], \Doctrine\ORM\Query::HYDRATE_SCALAR) as $comicData) {
            $client->index([
                'index' => 'comics',
                'id'    => $comicData['marvelId'],
                'body'  => [
                    'marvelId' => $comicData['marvelId'],
                    'title'    => $comicData['title'],
                    'date'     => $comicData['date'] ? (new \DateTimeImmutable($comicData['date']))->format('Y-m-d') : null,
                    'thumbnail'=> $comicData['thumbnail'],
                ],
            ]);

            $i++;

            if ($i % $batchSize === 0) {
                $em->clear();
            }
        }

        $output->writeln("<info>Indexing completed. Total comics indexed: $i.</info>");

        return Command::SUCCESS;
    }
}
