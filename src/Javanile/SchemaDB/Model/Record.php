<?php

/*
 * 
 * 
\*/
namespace Javanile\SchemaDB\Model;

/**
 *
 */
use Javanile\SchemaDB\SchemaParser;

/**
 * self methods of sdbClass
 *
 *
 */
class Record extends Table
{
    /**
	 *  constructor
	 */
    public function __construct()
    {
        // update database schema
        static::applyTable();

        // prepare field values strip schema definitions
        foreach (static::getSchemaFields() as $field) {

            //
            $this->{$field} = SchemaParser::getNotationValue($this->{$field});
        }
    }
	
    /**
	 * 
	 * 
	 * @param type $values
	 */
    public function fill($values)
    {
		//
        foreach (static::getSchemaFields() as $field) {
            
			//
			if (isset($values[$field])) {
                $this->{$field} = $values[$field];
            }
        }

		//
        $key = $this->getPrimaryKey();

		//
        if ($key) {
            $this->{$key} = isset($values[$key]) ? (int) $values[$key] : (int) $this->{$key};
        }
    }
    
}