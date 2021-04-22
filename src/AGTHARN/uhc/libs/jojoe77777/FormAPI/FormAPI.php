<?php

declare(strict_types = 1);

namespace AGTHARN\uhc\libs\jojoe77777\FormAPI;

use pocketmine\plugin\PluginBase;

class FormAPI extends PluginBase
{

    /**
     * createCustomForm
     * 
     * @deprecated
     *
     * @param callable|null $function
     * @return CustomForm
     */
    public function createCustomForm(?callable $function = null): CustomForm
    {
        return new CustomForm($function);
    }

    /**
     * createSimpleForm
     * 
     * @deprecated
     *
     * @param callable|null $function
     * @return SimpleForm
     */
    public function createSimpleForm(?callable $function = null): SimpleForm
    {
        return new SimpleForm($function);
    }

    /**
     * createModalForm
     * 
     * @deprecated
     *
     * @param callable|null $function
     * @return ModalForm
     */
    public function createModalForm(?callable $function = null): ModalForm
    {
        return new ModalForm($function);
    }
}
