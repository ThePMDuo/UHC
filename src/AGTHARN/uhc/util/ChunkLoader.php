<?php
declare(strict_types=1);

namespace AGTHARN\uhc\util;

use pocketmine\scheduler\ClosureTask;
use pocketmine\level\Level;

use AGTHARN\uhc\Main;

use AGTHARN\uhc\libs\muqsit\chunkgenerator\ChunkGenerator;

use Generator;

class ChunkLoader
{        
	/** @var Main */
    private $plugin;

    /**
     * __construct
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * generateChunks
     *
     * @param  Level $world
     * @param  int $minChunkX
     * @param  int $minChunkZ
     * @param  int $maxChunkX
     * @param  int $maxChunkZ
     * @param  int $population_queue_size
     * @return void
     */
    public function generateChunks(Level $world, int $minChunkX, int $minChunkZ, int $maxChunkX, int $maxChunkZ, int $population_queue_size): void
    {
		$loaded_chunks = 0;
		$iterated = 0;
		$population_queue = [];
		$logger = $this->plugin->getLogger();

		$generator = (static function() use($world, $minChunkX, $minChunkZ, $maxChunkX, $maxChunkZ, &$loaded_chunks, &$iterated, &$population_queue) : Generator{
			for ($chunkX = $minChunkX; $chunkX <= $maxChunkX; ++$chunkX) {
				for ($chunkZ = $minChunkZ; $chunkZ <= $maxChunkZ; ++$chunkZ) {
					++$iterated;

					$chunk = $world->isChunkLoaded($chunkX, $chunkZ) ? $world->getChunk($chunkX, $chunkZ) : null;
					if ($chunk !== null) {
						if ($chunk->isPopulated()) {
							yield true;
							continue;
						}
						$population_queue[Level::chunkHash($chunkX, $chunkZ)] = $chunk;
					}

					$generator = new ChunkGenerator($world, $chunkX, $chunkZ, static function(ChunkGenerator $generator) use(&$population_queue) : void{
						$population_queue[Level::chunkHash($generator->getX(), $generator->getZ())] = $generator;
					}, static function(ChunkGenerator $generator) use($world, &$loaded_chunks) : void{
						$world->unregisterChunkLoader($generator, $generator->getX(), $generator->getZ());
						--$loaded_chunks;
					});

					$world->registerChunkLoader($generator, $chunkX, $chunkZ);
					++$loaded_chunks;
					yield true;
				}
			}
		})();

		$iterations = (1 + ($maxChunkX - $minChunkX)) * (1 + ($maxChunkZ - $minChunkZ));
		$this->plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(static function() use(&$loaded_chunks, &$iterated, &$population_queue, $world, $iterations, $generator, $logger, $population_queue_size): void
        {
			foreach ($population_queue as $index => $gen) {
				if ($world->populateChunk($gen->getX(), $gen->getZ(), true)) {
					unset($population_queue[$index]);
					/** @var $generator Generator */
					if (count($population_queue) === 0 && !$generator->valid()) {
						return;
					}
				}
			}

			while ($iterated !== $iterations && $loaded_chunks < $population_queue_size) {
				$generator->send(true);
				if (!$generator->valid() && count($population_queue) === 0) {
					return;
				}

				$logger->info("Completed {$iterated} / {$iterations} chunks (" . sprintf("%0.2f", ($iterated / $iterations) * 100) . "%, {$loaded_chunks} chunks are currently being populated)");
			}
			return;
		}), 1);
	}
}
