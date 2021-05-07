<?php

declare(strict_types=1);

namespace AGTHARN\uhc\libs\muqsit\chunkgenerator;

use Closure;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\level\ChunkLoader;
use pocketmine\level\format\Chunk;

final class ChunkGenerator implements ChunkLoader{

	/** @var int */
	private $chunkX;

	/** @var int */
	private $chunkZ;

	/** @var Closure */
	private $populate;

	/** @var Closure */
	private $on_populate;
	/** @var Level */
	private $level;
	/** @var int */
	private $loaderId = 0;

	public function __construct(Level $level, int $chunkX, int $chunkZ, Closure $populate, Closure $on_populate){
		$this->level = $level;
		$this->chunkX = $chunkX;
		$this->chunkZ = $chunkZ;
		$this->populate = $populate;
		$this->on_populate = $on_populate;
		$this->loaderId = Level::generateChunkLoaderId($this);
	}

	public function getX() : int{ /** @phpstan-ignore-line */
		return (int)$this->chunkX;
	}

	public function getZ() : int{ /** @phpstan-ignore-line */
		return (int)$this->chunkZ;
	}

	public function onChunkLoaded(Chunk $chunk) : void{
		if($chunk->isPopulated()){
			($this->on_populate)($this);
		}else{
			($this->populate)($this);
		}
	}

	public function onChunkPopulated(Chunk $chunk) : void{
		($this->on_populate)($this);
	}

	public function onChunkChanged(Chunk $chunk) : void{
	}

	public function onChunkUnloaded(Chunk $chunk) : void{
	}

	public function onBlockChanged(Vector3 $block) : void{
	}

	/**
	 * Returns the ChunkLoader id.
	 * Call Level::generateChunkLoaderId($this) to generate and save it
	 */
	public function getLoaderId(): int
	{
		return $this->loaderId;
	}

	/**
	 * Returns if the chunk loader is currently active
	 */
	public function isLoaderActive(): mixed /** @phpstan-ignore-line */
	{
		// TODO: Implement isLoaderActive() method. /** @phpstan-ignore-line */
	}

	/**
	 * @return mixed
	 */
	public function getPosition() /** @phpstan-ignore-line */
	{
		// TODO: Implement getPosition() method. /** @phpstan-ignore-line */
	}

	/**
	 * @return Level
	 */
	public function getLevel()
	{
		return $this->level;
	}
}