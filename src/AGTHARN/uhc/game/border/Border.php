<?php
declare(strict_types=1);

/**
 * ███╗░░░███╗██╗███╗░░██╗███████╗██╗░░░██╗██╗░░██╗░█████╗░
 * ████╗░████║██║████╗░██║██╔════╝██║░░░██║██║░░██║██╔══██╗
 * ██╔████╔██║██║██╔██╗██║█████╗░░██║░░░██║███████║██║░░╚═╝
 * ██║╚██╔╝██║██║██║╚████║██╔══╝░░██║░░░██║██╔══██║██║░░██╗
 * ██║░╚═╝░██║██║██║░╚███║███████╗╚██████╔╝██║░░██║╚█████╔╝
 * ╚═╝░░░░░╚═╝╚═╝╚═╝░░╚══╝╚══════╝░╚═════╝░╚═╝░░╚═╝░╚════╝░
 * 
 * Copyright (C) 2020-2021 AGTHARN
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace AGTHARN\uhc\game\border;

use pocketmine\world\World;

class Border
{   
    /** @var World */
    private World $world;

    /** @var int */
    private int $size = 500;
    /** @var int */
    public int $reductionSize = 0;
    
    /**
     * __construct
     *
     * @param  World $world
     * @return void
     */
    public function __construct(World $world)
    {
        $this->world = $world;
    }
    
    /**
     * setSize
     *
     * @param  int $size
     * @return void
     */
    public function setSize(int $size): void
    {
        $this->size = $size;
    }
    
    /**
     * setReduction
     *
     * @return void
     */
    public function setReduction(int $size): void
    {
        $this->reductionSize = $size;
    }
    
    /**
     * getSize
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }   
}
