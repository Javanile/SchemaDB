<?php
/**
 *
 */

namespace Javanile\SchemaDB\Database;

trait SchemaApi
{

    /**
     * Describe database each tables
     * with the specific prefix and her fields
     *
     * @return array return an array with database description schema
     */
    public function desc($only=null)
    {
        //
        $prefix = strlen($this->getPrefix());

        //
        $tables = $this->getTables();

        //
        if (!$tables) {
            return [];
        }

        //
        $desc = [];

        //
        if (is_string($only)) { $only = [$only]; }

        //
        foreach ($tables as $table) {

            //
            $model = substr($table, $prefix);
            
            //
            if (!$only || in_array($model, $only)) {
                $desc[$model] = $this->descTable($table);
            }
        }

        //
        return $desc;
    }

    /**
     * describe table
     *
     * @param type $table
     * @return type
     */
    public function descTable($table)
    {
        //
        $sql = "DESC `{$table}`";

        //
        $fields = $this->getResults($sql);

        //
        $desc = array();

        //
        $count = 0;

        //
        $before = false;

        //
        foreach ($fields as $field) {

            //
            $field['First']  = $count === 0;
            $field['Before'] = $before;

            //
            $desc[$field['Field']] = $field;

            //
            $before = $field['Field'];
            $count++;
        }

        //
        return $desc;
    }

    /**
     * Apply schema on the database
     *
     * @param  type $schema
     * @return type
     */
    public function apply($schema, $columns = null, $notation = null)
    {
        // 
        if (is_string($schema)) {
            $schema = array(
                $schema => is_string($columns) ? array($columns => $notation) : $columns,
            );
        }

        // retrive queries
        $queries = $this->diff($schema);

        // execute queries
        if (!$queries) {
            return;
        }

        // send all queries to align database
        foreach ($queries as $sql) {
            $this->execute($sql);
        }

        // return queries
        return $queries;
    }

    /**
     * Update database table via schema
     *
     * @param  string $table  real table name to update
     * @param  type   $schema
     * @param  type   $parse
     * @return type
     */
    public function applyTable($table, $schema, $parse = true)
    {
        // retrive queries
        $queries = $this->diffTable($table, $schema, $parse);

        // execute queries
        if ($queries && count($queries) > 0) {

            // loop throu all queries calculated and execute it
            foreach ($queries as $sql) {

                // execute each queries
                $this->execute($sql);
            }
        }

        // return queries
        return $queries;
    }

    /**
     * Generate SQL query to align database
     * compare real database and passed schema
     *
     * @param  type $schema
     * @param  type $parse
     * @return type
     */
    public function diff($schema, $parse = true)
    {
        // prepare
        if ($parse) {
            $this->getParser()->parse($schema);
        }
        
        // get prefix string
        $prefix = $this->getPrefix();

        // output container for rescued SQL query
        $queries = array();

        // loop throu the schema
        foreach ($schema as $table => &$attributes) {

            //
            $table = $parse ? $prefix.$table : $table;

            // 
            $sql = $this->diffTable($table, $attributes, false);

            //
            if (count($sql) > 0) {
                $queries = array_merge($queries, $sql);
            }
        }

        // return estimated sql query
        return $queries;
    }

    /**
     * generate query to align table
     *
     * @param  type $table
     * @param  type $schema
     * @param  type $parse
     * @return type
     */
    public function diffTable($table, $schema, $parse = true)
    {
        // parse input schema if required
        if ($parse) {

            //
            $this->getParser()->parseSchemaTable($schema);

            //
            $table = $this->getPrefix().$table;
        }
        
        // if table no exists return sql statament for creating this
        if (!$this->tableExists($table, false)) {

            //
            $sql = $this
                ->getWriter()
                ->createTable($table, $schema);

            //
            return [$sql];
        }

        //
        $queries = $this->diffTableQueries($table, $schema);

        //
        return $queries;
    }

    /**
     * generate query to align table
     *
     * @param  type $table
     * @param  type $schema
     * @param  type $parse
     * @return type
     */
    private function diffTableQueries($table, &$schema)
    {
        // first order queries used as output array
        $foQueries = array();

        // second order queries used as output array
        $soQueries = array();

        // describe table get current table description
        $fields = $this->descTable($table);

        // test field definition
        foreach ($schema as $field => &$attributes) {

            //
            $this->diffTableField(
                $table, $field, $attributes, $fields, $foQueries, $soQueries
            );
        }

        //
        return $this->diffTableMergeQueries(
                $table, $fields, $foQueries, $soQueries
        );
    }

    /**
     *
     *
     *
     * @return type
     */
    private function
    diffTableMergeQueries($table, &$fields, &$foQueries, &$soQueries)
    {
        //
        $key = $this->diffTableFieldPrimaryKey($fields);

        //
        if ($key && count($foQueries) > 0) {

            //
            $foQueries[] = $this
                ->getWriter()
                ->alterTableDropPrimaryKey($table);

            //
            $fields[$key]['Key'] = '';

            //
            $fields[$key]['Extra'] = '';

            //
            $foQueries[] = $this
                ->getWriter()
                ->alterTableChange($table, $key, $fields[$key]);
        }

        //
        return array_merge(array_reverse($foQueries), $soQueries);
    }

    /**
     *
     * @param type $table
     * @param type $field
     * @param type $attributes
     * @param type $fields
     * @param type $foQueries
     * @param type $soQueries
     */
    private function diffTableField(
    $table, $field, &$attributes, &$fields, &$foQueries, &$soQueries
    )
    {
        // check if column exists in current db
        if (!isset($fields[$field])) {

            //
            $sql = $this
                ->getWriter()
                ->alterTableAdd($table, $field, $attributes);

            // add primary key column
            if ($attributes['Key'] == 'PRI') {
                $foQueries[] = $sql;
            }

            // add normal column
            else {
                $soQueries[] = $sql;
            }
        }

        // check if column need to be updated
        else if ($this->diffTableFieldAttributes($field, $attributes, $fields)) {

            // compose alter table query with attributes
            $sql = $this
                ->getWriter()
                ->alterTableChange($table, $field, $attributes);

            // alter column that lose primary key
            if ($fields[$field]['Key'] == 'PRI' || $attributes['Key'] == 'PRI') {
                $foQueries[] = $sql;
            }

            // alter colum than not interact with primary key
            else {
                $soQueries[] = $sql;
            }
        }
    }

    /**
     * Evaluate diff between a field and their attributes
     * vs fields set definitions releaved direct from db
     *
     * @param  type $field
     * @param  type $attributes
     * @param  type $fields
     * @return boolean
     */
    private function diffTableFieldAttributes($field, &$attributes, &$fields)
    {
        // loop throd current column property
        foreach ($fields[$field] as $key => $value) {

            // if have a difference
            if ($attributes[$key] == $value) {
                continue;
            }

            //
            if ($this->getDebug()) {
                echo '<pre style="background:#E66;color:#000;margin:0 0 1px 0;padding:2px 6px 3px 6px;border:1px solid #000;">';
                echo '  difference: "'.$attributes[$key].'" != "'.$value.'" in '.$field.'['.$key.']</pre>';
            }

            //
            return true;
        }

        //
        return false;
    }

    /**
     * Return primary field name if have one
     *
     * @param type $fields
     * @return boolean
     */
    private function diffTableFieldPrimaryKey(&$fields)
    {
        // loop throd current column property
        foreach ($fields as $field => &$attributes) {

            // lookitup by equal
            if ($attributes['Key'] == 'PRI') {
                return $field;
            }
        }

        //
        return false;
    }

    /**
     *
     * @param type $schema
     */
    public function alter($schema, $columns = null, $notation = null)
    {
        //
        if (is_string($schema)) {
            $schema = array(
                $schema => is_string($columns) 
                         ? array($columns => $notation)
                         : $columns,
            );
        }

        //
        $desc = $this->desc();

        //
        foreach ($schema as $table => $fields) {

            //
            $desc[$table] = isset($desc[$table])
                         && is_array($desc[$table])
                          ? array_merge($desc[$table], $fields)
                          : $fields;
        }

        //
        $this->apply($desc);
    }

    /**
     *
     * @param type $schema
     */
    public function alterTable($schema, $column = null, $notation = null)
    {
        // TODO: da fare
    }

    /**
     *
     * @param type $schema
     */
    public function adapt($schema, $columns = null, $notation = null)
    {
        //
        if (is_string($schema)) {
            $schema = array(
                $schema => is_string($columns) ? array($columns => $notation) : $columns,
            );
        }

        //
        $desc = $this->desc(array_keys($schema));
        
        //
        foreach ($schema as $table => $fields) {

            if (!isset($desc[$table])) {
                $desc[$table] = $fields;
                continue;
            }

            foreach ($fields as $field => $attribute) {
                if (!isset($desc[$table][$field])) {
                    $desc[$table][$field] = $attribute;
                }
            }
        }

        //
        $this->apply($desc);
    }

    /**
     *
     *
     */
    public function profile($values)
    {
        //
        $profile = [];

        //
        foreach (array_keys($values) as $field) {
            $profile[$field] = '';
        }

        //
        return $profile;
    }

    /**
     * printout database status and info
     *
     */
    public function info($model=null)
    {
        //
        $debug = $this->getDebug();
        
        //
        $this->setDebug(false);

        //
        $style = 'text-align:center;margin:10px 0;width:800px;';
        
        //
        if (is_null($model)) {
            $this->info($this->getModels());
        }

        //
        else if (is_array($model) && count($model) > 0) {
            foreach ($model as $m) {
                $this->info($m);
            }            
        }

        //
        else if (is_array($model) && count($model) == 0) {
            echo '<pre><table border="1" style="'.$style.'">'
               . '<tr><th>No database tables</th></tr></table></pre>'
               . '</table></pre>';
        }

        //
        else {

            //
            $desc = $this->desc($model);

            //
            echo '<pre><table border="1" style="'.$style.'">'
               . '<tr><th colspan="9">'.$model.'</th></tr>';

            //
            if (isset($desc[$model])) {

                //
                $first = key($desc[$model]);

                //
                echo '<tr><td>&nbsp;</td>';

                //
                foreach (array_keys($desc[$model][$first]) as $attributeName) {
                    echo '<th>'.$attributeName.'</th>';
                }

                //
                echo '</tr>';

                foreach ($desc[$model] as $field => $attributes) {

                    //
                    echo '<tr><th>'.$field.'</th>';

                    //
                    foreach ($attributes as $value) {
                        echo '<td>'.$value.'</td>';
                    }

                    //
                    echo '</tr>';
                }
            } else {
                echo '<tr><td>model table not exists</td></tr>';
            }

            //
            echo '</table></pre>';
            




        }

        //
        $this->setDebug($debug);
    }

    /**
     * printout database status and info
     *
     */
    public function dumpSchema()
    {
        //
        $debug = $this->getDebug();

        //
        $this->setDebug(false);

        // describe databse
        $schema = $this->desc();

        //
        $style = 'text-align:center;margin:10px 0;width:800px;';

        //
        echo '<pre>';

        //
        if (!$schema) {
            echo '<table border="1" style="'.$style.'">'
               . '<tr><th>No database tables</th></tr></table></pre>'
               . '</table>';
        }

        //
        else {

            //
            foreach ($schema as $table => $fields) {

                //
                echo '<table border="1" style="'.$style.'">'
                   . '<tr><th colspan="9">'.$table.'</th></tr><tr><td>&nbsp;</td>';

                //
                $first = key($fields);

                //
                foreach (array_keys($fields[$first]) as $attributeName) {
                    echo '<th>'.$attributeName.'</th>';
                }

                //
                echo '</tr>';

                //
                foreach ($fields as $field => $attributes) {

                    //
                    echo '<tr><th>'.$field.'</th>';

                    //
                    foreach ($attributes as $value) {
                        echo '<td>'.$value.'</td>';
                    }

                    //
                    echo '</tr>';
                }

                //
                echo '</table>';
            }

            //
            echo '</pre>';
        }

        //
        $this->setDebug($debug);
    }
}