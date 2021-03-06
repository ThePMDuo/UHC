<?php

/**
 * Copyright (C) 2021  CzechPMDevs
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace czechpmdevs\multiworld\libs\czechpmdevs\libpmform\response;

class FormResponse {

    /** @var mixed */
    private $data;

    /**
     * @param mixed $data
     */
    public function __construct($data) {
        $this->data = $data;
    }

    public function isValid(): bool {
        return $this->data !== null;
    }

    /**
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }
}