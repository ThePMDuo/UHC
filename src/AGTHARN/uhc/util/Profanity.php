<?php

namespace AGTHARN\uhc\util;

use pocketmine\utils\Config;

use AGTHARN\uhc\Main;

class Profanity
{
    /** @var Main */
    public $plugin;

    /** @var string */
    private const SEPARATOR_PLACEHOLDER = '{!!}';
    
    /**
     * escapedSeparatorCharacters
     *
     * @var array
     */
    protected $escapedSeparatorCharacters = [
        '\s',
    ];
    
    /**
     * separatorCharacters
     *
     * @var array
     */
    protected $separatorCharacters = [
        '@',
        '#',
        '%',
        '&',
        '_',
        ';',
        "'",
        '"',
        ',',
        '~',
        '`',
        '|',
        '!',
        '$',
        '^',
        '*',
        '(',
        ')',
        '-',
        '+',
        '=',
        '{',
        '}',
        '[',
        ']',
        ':',
        '<',
        '>',
        '?',
        '.',
        '/',
        ];
    
    /**
     * characterSubstitutions
     *
     * @var array
     */
    protected $characterSubstitutions = [
        '/a/' => [
            'a',
            '4',
            '@',
            'Á',
            'á',
            'À',
            'Â',
            'à',
            'Â',
            'â',
            'Ä',
            'ä',
            'Ã',
            'ã',
            'Å',
            'å',
            'æ',
            'Æ',
            'α',
            'Δ',
            'Λ',
            'λ',
        ],
        '/b/' => ['b', '8', '\\', '3', 'ß', 'Β', 'β'],
        '/c/' => ['c', 'Ç', 'ç', 'ć', 'Ć', 'č', 'Č', '¢', '€', '<', '(', '{', '©'],
        '/d/' => ['d', '\\', ')', 'Þ', 'þ', 'Ð', 'ð'],
        '/e/' => ['e', '3', '€', 'È', 'è', 'É', 'é', 'Ê', 'ê', 'ë', 'Ë', 'ē', 'Ē', 'ė', 'Ė', 'ę', 'Ę', '∑'],
        '/f/' => ['f', 'ƒ'],
        '/g/' => ['g', '6', '9'],
        '/h/' => ['h', 'Η'],
        '/i/' => ['i', '!', '|', ']', '[', '1', '∫', 'Ì', 'Í', 'Î', 'Ï', 'ì', 'í', 'î', 'ï', 'ī', 'Ī', 'į', 'Į'],
        '/j/' => ['j'],
        '/k/' => ['k', 'Κ', 'κ'],
        '/l/' => ['l', '!', '|', ']', '[', '£', '∫', 'Ì', 'Í', 'Î', 'Ï', 'ł', 'Ł'],
        '/m/' => ['m'],
        '/n/' => ['n', 'η', 'Ν', 'Π', 'ñ', 'Ñ', 'ń', 'Ń'],
        '/o/' => [
            'o',
            '0',
            'Ο',
            'ο',
            'Φ',
            '¤',
            '°',
            'ø',
            'ô',
            'Ô',
            'ö',
            'Ö',
            'ò',
            'Ò',
            'ó',
            'Ó',
            'œ',
            'Œ',
            'ø',
            'Ø',
            'ō',
            'Ō',
            'õ',
            'Õ',
        ],
        '/p/' => ['p', 'ρ', 'Ρ', '¶', 'þ'],
        '/q/' => ['q'],
        '/r/' => ['r', '®'],
        '/s/' => ['s', '5', '$', '§', 'ß', 'Ś', 'ś', 'Š', 'š'],
        '/t/' => ['t', 'Τ', 'τ'],
        '/u/' => ['u', 'υ', 'µ', 'û', 'ü', 'ù', 'ú', 'ū', 'Û', 'Ü', 'Ù', 'Ú', 'Ū'],
        '/v/' => ['v', 'υ', 'ν'],
        '/w/' => ['w', 'ω', 'ψ', 'Ψ'],
        '/x/' => ['x', 'Χ', 'χ'],
        '/y/' => ['y', '¥', 'γ', 'ÿ', 'ý', 'Ÿ', 'Ý'],
        '/z/' => ['z', 'Ζ', 'ž', 'Ž', 'ź', 'Ź', 'ż', 'Ż'],
    ];
    
    /** @var array */
    protected $profanities = [];
    /** @var mixed */
    protected $separatorExpression;
    /** @var mixed */
    protected $characterExpressions;
    
    /**
     * __construct
     *
     * @param  Main $plugin
     * @return void
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        $this->profanities = (new Config($this->plugin->getDataFolder() . "swearwords.yml"))->getAll()["swearwords"];

        $this->separatorExpression = $this->generateSeparatorExpression();
        $this->characterExpressions = $this->generateCharacterExpressions();
    }
    
    /**
     * hasProfanity
     *
     * @param  string $string
     * @return bool
     */
    public function hasProfanity(string $string): bool
    {
        if (empty($string)) {
            return false;
        }

        $profanities = [];
        $profanityCount = count($this->profanities);

        for ($i = 0; $i < $profanityCount; $i++) {
            $profanities[$i] = $this->generateProfanityExpression($this->profanities[$i], $this->characterExpressions, $this->separatorExpression);
        }

        foreach ($profanities as $profanity) {
            if ($this->stringHasProfanity($string, $profanity)) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * obfuscateIfProfane
     *
     * @param  string $string
     * @return string
     */
    public function obfuscateIfProfane($string): string
    {
        if ($this->hasProfanity($string)) {
            $string = str_repeat("*", strlen($string));
        }

        return $string;
    }
    
    /**
     * generateProfanityExpression
     *
     * @param  string $word
     * @param  mixed $characterExpressions
     * @param  mixed $separatorExpression
     * @return mixed
     */
    protected function generateProfanityExpression(string $word, $characterExpressions, $separatorExpression): mixed
    {
        $expression = '/' . preg_replace(array_keys($characterExpressions), array_values($characterExpressions), $word) . '/i';

        return str_replace(self::SEPARATOR_PLACEHOLDER, $separatorExpression, $expression);
    }
    
    /**
     * stringHasProfanity
     *
     * @param  string $string
     * @param  mixed $profanity
     * @return mixed
     */
    private function stringHasProfanity(string $string, $profanity): mixed
    {
        return preg_match($profanity, $string) === 1;
    }
    
    /**
     * generateEscapedExpression
     *
     * @param  array $characters
     * @param  array $escapedCharacters
     * @param  string $quantifier
     * @return string
     */
    private function generateEscapedExpression(array $characters = [], array $escapedCharacters = [], string $quantifier = '*?'): string
    {
        $regex = $escapedCharacters;
        foreach ($characters as $character) {
            $regex[] = preg_quote($character, '/');
        }

        return '[' . implode('', $regex) . ']' . $quantifier;
    }
    
    /**
     * generateSeparatorExpression
     *
     * @return string
     */
    private function generateSeparatorExpression(): string
    {
        return $this->generateEscapedExpression($this->separatorCharacters, $this->escapedSeparatorCharacters);
    }
    
    /**
     * generateCharacterExpressions
     *
     * @return array
     */
    protected function generateCharacterExpressions(): array
    {
        $characterExpressions = [];
        foreach ($this->characterSubstitutions as $character => $substitutions) {
            $characterExpressions[$character] = $this->generateEscapedExpression($substitutions, [], '+?') . self::SEPARATOR_PLACEHOLDER;
        }

        return $characterExpressions;
    }
}