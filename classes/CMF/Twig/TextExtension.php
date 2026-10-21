<?php

namespace CMF\Twig;

use Twig\Environment,
    Twig\Extension\AbstractExtension,
    Twig\TwigFilter;

/**
 * The 'truncate' and 'wordwrap' filters from the abandoned twig/extensions package
 */
class TextExtension extends AbstractExtension
{
    public function getFilters()
    {
        return array(
            new TwigFilter('truncate', array($this, 'truncate'), array('needs_environment' => true)),
            new TwigFilter('wordwrap', array($this, 'wordwrap'), array('needs_environment' => true)),
        );
    }

    public function truncate(Environment $env, $value, $length = 30, $preserve = false, $separator = '...')
    {
        $value = (string) $value;

        if (mb_strlen($value, $env->getCharset()) > $length) {
            if ($preserve) {
                if (false !== ($breakpoint = mb_strpos($value, ' ', $length, $env->getCharset()))) {
                    $length = $breakpoint;
                }
            }

            return rtrim(mb_substr($value, 0, $length, $env->getCharset())) . $separator;
        }

        return $value;
    }

    public function wordwrap(Environment $env, $value, $length = 80, $separator = "\n", $preserve = false)
    {
        $sentences = array();

        $previous = mb_regex_encoding();
        mb_regex_encoding($env->getCharset());

        $pieces = mb_split($separator, (string) $value);
        mb_regex_encoding($previous);

        foreach ($pieces as $piece) {
            while (!$preserve && mb_strlen($piece, $env->getCharset()) > $length) {
                $sentences[] = mb_substr($piece, 0, $length, $env->getCharset());
                $piece = mb_substr($piece, $length, 2048, $env->getCharset());
            }

            $sentences[] = $piece;
        }

        return implode($separator, $sentences);
    }
}
