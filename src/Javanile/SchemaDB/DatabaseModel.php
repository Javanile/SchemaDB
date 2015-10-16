<?php

namespace Javanile\SchemaDB;


class DatabaseModel extends DatabaseSchema
{
    
    /**
     * 
     */
    private function getTable($model) {

        //
        return $this->getPrefix($model);
    }


    /**
     *
     * @param type $model
     */
    public function getPrimaryKey($model) {

        $table = $this->getTable($model);

        $desc = $this->descTable($table);
        
        foreach($desc as $field => $attributes) {
            
            foreach($attributes as $name => $value) {
                if ($name == 'Key' && $value == 'PRI') {
                    return $field;
                }
            }
        }

        return false;
    }


    /**
     *
     * @param type $model
     */
    public function getMainField($model) {

        $key = $this->getPrimaryKey($model);

        $table = $this->getTable($model);

        $desc = $this->descTable($table);
        
        foreach($desc as $field => $attributes) {
            
            if ($field == $key) {
                continue;
            }
            
            return $field;            
        }
    }

}