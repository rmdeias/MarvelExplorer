<?php

namespace App\Command\ElasticSearchCommands;

use App\Repository\CharacterRepository;
use App\Service\ElasticSearchServices\ElasticIndexService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * IndexCharactersCommand
 *
 * This command indexes all characters from the database into Elasticsearch.
 *
 * Steps performed:
 * 1. Creates the characters index in Elasticsearch if it does not exist.
 * 2. Fetches characters from MySQL in batches to avoid memory issues.
 * 3. Indexes each character into Elasticsearch.
 *
 * Usage:
 *  php -d memory_limit=512M bin/console app:index-characters
 *
 * Notes:
 * - Batch size is 50 by default to manage memory.
 * - Doctrine's toIterable() is used to efficiently stream results.
 */
#[AsCommand(
    name: 'app:index-characters',
    description: 'Creates the characters index in Elasticsearch and indexes all characters from MySQL.'
)]
class IndexCharactersCommand extends Command
{
    public function __construct(
        private readonly CharacterRepository     $characterRepository,
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
        $output->writeln('<info>Creating characters index if necessary...</info>');
        $this->elasticIndexService->createCharactersIndex();

        $output->writeln('<info>Indexing characters...</info>');

        $client = $this->elasticIndexService->getClient();
        $batchSize = 50;
        $i = 0;

        $em = $this->characterRepository->getEntityManager();

        $qb = $this->characterRepository->createQueryBuilder('c')
            ->select(
                'c.marvelId AS marvelId',
                'c.name AS name',
                'c.thumbnail AS thumbnail'
            );

        foreach ($qb->getQuery()->toIterable([], \Doctrine\ORM\Query::HYDRATE_SCALAR) as $characterData) {
            $client->index([
                'index' => 'characters',
                'id'    => $characterData['marvelId'],
                'body'  => [
                    'marvelId' => $characterData['marvelId'],
                    'name'    => $characterData['name'],
                    'thumbnail'=> $characterData['thumbnail'],
                ],
            ]);

            $i++;

            if ($i % $batchSize === 0) {
                $em->clear();
            }
        }

        $output->writeln("<info>Indexing completed. Total characters indexed: $i.</info>");

        return Command::SUCCESS;
    }
}
