<?php
/**
 * Class that handle a connection with database.
 *
 * PHP version 5.6
 *
 * @author Francesco Bianco
 */

namespace Javanile\Moldable\Parser\Pgsql;

trait NumberTrait
{
    protected function getNotationAspectsBoolean(
        $notation,
        $aspects
    ) {
        $aspects['Type'] = 'tinyint(1)';
        $aspects['Default'] = (int) $notation;
        $aspects['Null'] = 'NO';

        return $aspects;
    }

    protected function getNotationAspectsInteger(
        $notation,
        $aspects
    ) {
        $aspects['Type'] = 'integer';
        $aspects['Default'] = (int) $notation;
        $aspects['Null'] = 'NO';

        return $aspects;
    }

    protected function getNotationAspectsFloat(
        $notation,
        $aspects
    ) {
        $aspects['Null'] = 'NO';
        $aspects['Type'] = 'float(12,2)';
        $aspects['Default'] = (float) $notation;

        return $aspects;
    }

    protected function getNotationAspectsDouble(
        $notation,
        $aspects
    ) {
        $aspects['Null'] = 'NO';
        $aspects['Type'] = 'double(10,4)';
        $aspects['Default'] = (float) $notation;

        return $aspects;
    }
}