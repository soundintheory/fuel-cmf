<?php

namespace CMF\Auth;

/**
 * Validator
 *
 * @package    CMF
 * @subpackage Auth
 */
class PasswordValidator
{

    private $password;

    private $minLength;
    private $maxLength;
    private $allowedSymbols;

    private $regex;

    public function __construct($password)
    {
        $this->password = $password;

        $config = \Config::get('cmf.auth.requirements');

        $this->minLength = $config['min_length'];
        $this->maxLength = $config['max_length'];

        if ($config['force_symbols']) {
            $this->allowedSymbols = $config['allowed_symbols'];
        }

        $this->buildRegex();
    }

    public function isValid()
    {
        return preg_match($this->getRegex(), $this->getPassword());
    }

    private function buildRegex()
    {
        $range = '.{' . $this->getMinLength() . ',' . $this->getMaxLength() . '}';

        if($this->getAllowedSymbols()){
            $this->regex = '/^(?=.*[A-Za-z])' . '(?=.*\d)' . '(?=.*[' . $this->getAllowedSymbols() . '])' . '[A-Za-z\d' . $this->getAllowedSymbols() .']' . $range . '$/';
        } else {
            $this->regex = '/^(?=.*[A-Za-z])' . '(?=.*\d)' . '[A-Za-z\d]' . $range . '$/';
        }
    }

    // Getters

    /**
     * @return string
     */
    protected function getRegex()
    {
        return $this->regex;
    }

    /**
     * @return mixed
     */
    protected function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    protected function getMinLength()
    {
        return $this->minLength;
    }

    /**
     * @return mixed
     */
    protected function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @return mixed
     */
    protected function getAllowedSymbols()
    {
        return $this->allowedSymbols;
    }

}