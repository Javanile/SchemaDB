<?php

/*
 * 
 * 
 * 
 * 
\*/
namespace Javanile\SchemaDB\Model;

/**
 *
 */
use Javanile\SchemaDB\Database;

/**
 * static part of sdbClass
 *
 *
 */
class Table extends Fields
{   	
    /**
	 * Retrieve table name
	 * 
	 * @return string
	 */
    public static function getTable()
    {        
		// config attribute that contain table name
		$attribute = 'Table';

		// retrieve value from class setting definition
		if (static::hasConfig($attribute)) {
			return static::getConfig($attribute);
		}
		
		// 
		else if (isset(static::$table)) {
            $name = static::$table;
        } 
		
		//
		elseif (isset(static::$class)) {
            $name = static::$class;
        } 
		
		//
		else {
            $name = static::getClassName();
        }

		// get prefix
        $table = static::getDatabase()->getPrefix() . $name;

		// store as setting for future request
		static::setConfig($attribute, $table);
								
        // return complete table name
        return $table;
    }
   	
    /**
	 * 
	 * @return type
	 */ 
    public static function applyTable()
    {        
		// if model is not connectect to any db return
		if (!static::getDatabase()) {
			return;			
		}	

		$attribute = 'TableUpdated';
		
		// avoid re-update by check the cache
        if (static::hasConfig($attribute)) {	
			return;
		}

        // get table name
        $table = static::getTable();

		// 
		$schema = static::getSchema();
						
        // have a valid schema update db table
        if ($schema) {
            static::getDatabase()->applyTable($table, $schema, false);
        }

        // cache last update avoid multiple call
        static::setConfig($attribute, time());
    }
	
	/**
	 * Retriece linked database or default
	 * 
	 * @return type
	 */
    protected static function getDatabase()
    {		
		//
		$attribute = 'Database';

        //
        $database = static::hasConfig($attribute) ? static::getConfig($attribute) : Database::getDefault();

        //
        // TODO: check if no have database connected $database == null

        //
        return $database;
    }

	/**
	 * Link specific database to this table
	 * 
	 * @return type
	 */
    protected static function setDatabase($database)
    {        
		//
		$attribute = 'Database';
		
		//
		static::setConfig($attribute, $database);
    }

    /**
     *
     *
     * @param type $array
     */
    protected static function fetch(
        $sql,
        $values=null,
        $array=false,
        $value=false,
        $cast=true
    ) {
		// requested a single record
		if (!$array && !$value && $cast) {

            //
            $record = static::getDatabase()->getRow($sql, $values);

            //
            return $record ? static::make($record): null;
		}

        // requested a single record
		else if ($array && !$value && $cast) {

            //
            $records = static::getDatabase()->getResults($sql, $values);

            //
            if ($records) {
                foreach($records as &$record) {
                    $record = static::make($record);
                }
            }
            
            //
            return $records;
		}


    }
	
	
}