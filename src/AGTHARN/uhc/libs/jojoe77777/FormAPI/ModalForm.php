<?php

declare(strict_types = 1);

namespace AGTHARN\uhc\libs\jojoe77777\FormAPI;

class ModalForm extends Form
{

    /** @var string */
    private $content = '';

    /**
     * __construct
     * 
     * @param callable|null $callable
     */
    public function __construct(?callable $callable)
    {
        parent::__construct($callable);
        $this->data['type'] = 'modal';
        $this->data['title'] = '';
        $this->data['content'] = $this->content;
        $this->data['button1'] = '';
        $this->data['button2'] = '';
    }

    /**
     * setTitle
     * 
     * @param string $title
     * @return void
     */
    public function setTitle(string $title): void
    {
        $this->data['title'] = $title;
    }

    /**
     * getTitle
     * 
     * @return string
     */
    public function getTitle(): string
    {
        return $this->data['title'];
    }

    /**
     * getContent
     * 
     * @return string
     */
    public function getContent(): string
    {
        return $this->data['content'];
    }

    /**
     * setContent
     * 
     * @param string $content
     * @return void
     */
    public function setContent(string $content): void
    {
        $this->data['content'] = $content;
    }

    /**
     * setButton1
     * 
     * @param string $text
     * @return void
     */
    public function setButton1(string $text): void
    {
        $this->data['button1'] = $text;
    }

    /**
     * getButton1
     * 
     * @return string
     */
    public function getButton1(): string
    {
        return $this->data['button1'];
    }

    /**
     * setButton2
     * 
     * @param string $text
     * @return void
     */
    public function setButton2(string $text): void
    {
        $this->data['button2'] = $text;
    }

    /**
     * getButton2
     * 
     * @return string
     */
    public function getButton2(): string
    {
        return $this->data['button2'];
    }
}
