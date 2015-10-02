<?php

/*\
 * 
 * 
\*/
namespace SourceForge\SchemaDB;

/**
 * self methods of sdbClass
 *
 *
 */
class Record extends ModelAPI
{
    ## constructor
    public function __construct()
    {
        ## update database schema
        static::updateTable();

        ## prepare field values strip schema definitions
        foreach ($this->getFields() as $f) {

            ##
            $this->{$f} = Parser::getNotaionValue($this->{$f});
        }
    }
	
    /**
     * Assign value and store object
     *
     * @param type $query
     */
    public function assign($query)
    {
        ##
        foreach ($query as $k => $v) {

            ##
            $this->{$k} = $v;
        }

        ##
        $this->store();
    }

    /**
     * Auto-store element method
     *
     * @return type
     */
    public function store()
    {
        ## retrieve primary key
        $k = static::getPrimaryKey();

        ## based on primary key store action
        if ($k && $this->{$k}>0) {

            ##

            return $this->store_update();
        } else {

            ##

            return $this->store_insert();
        }
    }

    /**
     * Return fields names
     *
     * @return type
     */
    public function getFields()
    {
        ##
        $class = get_class($this);
        
		##
		$fields = array_keys(get_class_vars($class));
        
		##
		return array_diff($fields, static::getModelSetting('exclude'));		
    }
}
