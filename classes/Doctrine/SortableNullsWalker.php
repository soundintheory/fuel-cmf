<?php

namespace CMF\Doctrine;

use Doctrine\ORM\Query\SqlWalker;

class SortableNullsWalker extends SqlWalker
{
    const NULLS_FIRST = 'NULLS FIRST';
    const NULLS_LAST = 'NULLS LAST';

    public function walkOrderByClause($orderByClause)
    {
        $sql = parent::walkOrderByClause($orderByClause);

        if ($nullFields = $this->getQuery()->getHint('SortableNullsWalker.fields'))
        {
            if (is_array($nullFields))
            {
                $platform = $this->getConnection()->getDatabasePlatform()->getName();
                switch ($platform)
                {
                case 'mysql':
                    // for mysql the nulls last is represented with - before the field name
                    foreach ($nullFields as $field => $sorting)
                    {
                        /**
                         * NULLs are considered lower than any non-NULL value,
                         * except if a – (minus) character is added before
                         * the column name and ASC is changed to DESC, or DESC to ASC;
                         * this minus-before-column-name feature seems undocumented.
                         */
                        if ('NULLS LAST' === $sorting)
                        {
                            $sql = preg_replace_callback('/ORDER BY (.+)'.'('.$field.') (ASC|DESC)/i', function($matches) {
                                if ($matches[3] === 'ASC') {
                                    $order = 'DESC';
                                } elseif ($matches[3] === 'DESC') {
                                    $order = 'ASC';
                                }
                                return ('ORDER BY -'.$matches[1].$matches[2].' '.$order);
                            }, $sql);
                        }
                    }
                        break;
                case 'oracle':
                case 'postgresql':
                    foreach ($nullFields as $field => $sorting)
                    {
                        $sql = preg_replace('/(\.' . $field . ') (ASC|DESC)?\s*/i', "$1 $2 " . $sorting, $sql);
                    }
                    break;
                default:
                    // I don't know for other supported platforms.
                    break;
                    }
                }
            }

            return $sql;
    }
}