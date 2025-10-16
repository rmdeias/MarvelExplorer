<?php

namespace App\Command\ElasticSearchCommands;

use App\Repository\SerieRepository;
use App\Service\ElasticSearchServices\ElasticIndexService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * IndexSeriesCommand
 *
 * This command indexes all series from the database into Elasticsearch.
 *
 * Steps performed:
 * 1. Creates the series index in Elasticsearch if it does not exist.
 * 2. Fetches series from MySQL in batches to avoid memory issues.
 * 3. Indexes each serie into Elasticsearch.
 *
 * Usage:
 *  php -d memory_limit=512M bin/console app:index-series
 *
 * Notes:
 * - Batch size is 50 by default to manage memory.
 * - Doctrine's toIterable() is used to efficiently stream results.
 */
#[AsCommand(
    name: 'app:index-series',
    description: 'Creates the series index in Elasticsearch and indexes all series from MySQL.'
)]
class IndexSeriesCommand extends Command
{
    public function __construct(
        private readonly SerieRepository     $serieRepository,
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
        $output->writeln('<info>Creating series index if necessary...</info>');
        $this->elasticIndexService->createSeriesIndex();

        $output->writeln('<info>Indexing series...</info>');

        $client = $this->elasticIndexService->getClient();
        $batchSize = 50;
        $i = 0;

        $em = $this->serieRepository->getEntityManager();

        $qb = $this->serieRepository->createQueryBuilder('c')
            ->select(
                'c.marvelId AS marvelId',
                'c.title AS title',
                'c.thumbnail AS thumbnail'
            );

        foreach ($qb->getQuery()->toIterable([], \Doctrine\ORM\Query::HYDRATE_SCALAR) as $serieData) {
            $client->index([
                'index' => 'series',
                'id'    => $serieData['marvelId'],
                'body'  => [
                    'marvelId' => $serieData['marvelId'],
                    'title'    => $serieData['title'],
                    'thumbnail'=> $serieData['thumbnail'],
                ],
            ]);

            $i++;

            if ($i % $batchSize === 0) {
                $em->clear();
            }
        }

        $output->writeln("<info>Indexing completed. Total series indexed: $i.</info>");

        return Command::SUCCESS;
    }
}
