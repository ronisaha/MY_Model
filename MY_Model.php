<?php


/**
 * MY_Model
 *
 * An extension to CodeIgniter's general Model class to assist
 * in writing very small amount of code to do general tasks that
 * are needed in an application.
 *
 * @author  Md Emran Hasan <phpfour@gmail.com>
 * @author  Roni Kumar Saha <roni.cse@gmail.com>
 * @version 1.5
 */

class MY_Model extends CI_Model
{
    /**
     * @var string name of table
     */
    protected $table = NULL;
    /**
     * @var string the primary key
     */
    protected $primaryKey = NULL;

    /**
     * @var array list of fields
     */
    protected $fields = array();
    /**
     * @var int returned number of rows of a query
     */
    protected $numRows = NULL;
    /**
     * @var int|string the id of last inserted data
     */
    protected $insertId = NULL;
    /**
     * @var null
     */
    protected $affectedRows = NULL;
    /**
     * @var bool set the return type of query, return as array if true or return object if false
     */
    protected $returnArray = TRUE;

    /**
     * @var \CI_Controller the db object
     */
    protected $CI = NULL;

    /**
     * Initialize the model and bind with a table if the value of table found!
     */
    public function __construct()
    {
        parent::__construct();
        $this->CI = &get_instance();

        ($this->table != NULL) AND $this->loadTable($this->table, $this->primaryKey);
    }

    /**
     * @param string $table
     * @param string $primaryKey
     */
    public function loadTable($table, $primaryKey = 'id')
    {
        $this->table      = $table;
        $this->primaryKey = $primaryKey;
        $this->fields     = $this->db->list_fields($table);
    }

    /**
     * @param null|string|array $conditions
     * @param string            $fields
     * @param null|string|array $order
     * @param int               $start
     * @param null|int          $limit
     *
     * @return mixed
     */
    public function findAll($conditions = NULL, $fields = '*', $order = NULL, $start = 0, $limit = NULL)
    {
        if ($conditions != NULL) {
            if (is_array($conditions)) {
                $this->db->where($conditions);
            } else {
                $this->db->where($conditions, NULL, FALSE);
            }
        }

        if ($fields != NULL) {
            $this->db->select($fields);
        }

        if ($order != NULL) {
            $this->order_by($order);
        }

        if ($limit != NULL) {
            $this->db->limit($limit, $start);
        }

        return $this->getResult();
    }


    /**
     * Get the query result built
     * Helpful if you built your own query then just call this function to get the result
     *
     * @return mixed(array|object) based on the returnArray value
     */
    public function getResult()
    {
        $query         = $this->db->get($this->table);
        $this->numRows = $query->num_rows();
        return ($this->returnArray) ? $query->result_array() : $query->result();
    }

    /**
     * @param null|string|array $conditions
     * @param string            $fields
     * @param null|string|array $order
     *
     * @return bool
     */
    public function find($conditions = NULL, $fields = '*', $order = NULL)
    {
        $data = $this->findAll($conditions, $fields, $order, 0, 1);

        if ($data) {
            return $data[0];
        } else {
            return FALSE;
        }
    }

    /**
     * @param null   $conditions
     * @param        $name
     * @param string $fields
     * @param null   $order
     *
     * @return bool
     */
    public function field($conditions = NULL, $name, $fields = '*', $order = NULL)
    {
        $data = $this->findAll($conditions, $fields, $order, 0, 1);

        if ($data) {
            $row = $data[0];
            if (isset($row[$name])) {
                return $row[$name];
            }
        }

        return FALSE;
    }

    /**
     * @param null $conditions
     *
     * @return bool
     */
    public function findCount($conditions = NULL)
    {
        $data = $this->findAll($conditions, 'COUNT(*) AS count', NULL, 0, 1);

        if ($data) {
            return $data[0]['count'];
        } else {
            return FALSE;
        }
    }

    /**
     * @param null $data
     *
     * @return bool|null
     */
    public function insert($data = NULL)
    {
        if ($data == NULL) {
            return FALSE;
        }

        foreach ($data as $key => $value) {
            if (array_search($key, $this->fields) === FALSE) {
                unset($data[$key]);
            }
        }

        $this->db->insert($this->table, $data);
        $this->insertId = $this->db->insert_id();

        return $this->insertId;
    }

    /**
     * @param null $data
     * @param null $id
     * @param null $conditions
     *
     * @return bool|null
     */
    public function update($data = NULL, $id = NULL, $conditions = NULL)
    {
        $this->affectedRows = NULL;

        if ($id == NULL && $conditions == NULL && $data != NULL) { //THis is an insert operation
            return $this->insert($data);
        }

        if ($data == NULL) {
            return FALSE;
        }

        foreach ($data as $key => $value) {
            if (array_search($key, $this->fields) === FALSE) {
                unset($data[$key]);
            }
        }

        if ($id !== NULL) {
            $this->db->where($this->primaryKey, $id);
            $this->db->update($this->table, $data);
            $this->affectedRows = $this->db->affected_rows();
        } elseif ($conditions != NULL) {
            $this->db->where($conditions);
            $this->db->update($this->table, $data);
            $this->affectedRows = $this->db->affected_rows();
        }

        return $id;
    }

    /**
     * @param null $data
     * @param null $update
     *
     * @return bool
     */
    public function onDuplicateUpdate($data = NULL, $update = NULL)
    {
        if (is_null($data)) {
            return FALSE;
        }

        $sql = $this->_duplicate_insert_sql($this->db->_protect_identifiers($this->table), $data, $update);
        return $this->db->query($sql);
    }

    /**
     * @param      $table
     * @param      $values
     * @param null $update
     *
     * @return string
     */
    protected function _duplicate_insert_sql($table, $values, $update = NULL)
    {
        $updateStr = array();
        $keyStr    = array();
        $valStr    = array();

        foreach ($values as $key => $val) {
            $keyStr[] = $key;
            $valStr[] = $this->db->escape($val);
        }

        if (is_null($update)) {
            $update = $values;
        }

        foreach ($update as $key => $val) {
            $updateStr[] = $key . " = '{$val}'";
        }

        $sql = "INSERT INTO " . $table . " (" . implode(', ', $keyStr) . ") ";
        $sql .= "VALUES (" . implode(', ', $valStr) . ") ";
        $sql .= "ON DUPLICATE KEY UPDATE " . implode(", ", $updateStr);

        return $sql;
    }

    /**
     * @param null $id
     * @param null $conditions
     *
     * @return bool
     */
    public function remove($id = NULL, $conditions = NULL)
    {
        if ($id != NULL) {
            $this->db->where($this->primaryKey, $id);
        } elseif ($conditions != NULL) {
            $this->db->where($conditions);
        } else {
            return FALSE;
        }
        return $this->db->delete($this->table);
    }


    /**
     * @param null $values
     * @param null $field
     *
     * @return bool
     */
    public function remove_in($values = NULL, $field = NULL)
    {
        if ($values === NULL) {
            return FALSE;
        }

        if ($field === NULL) {
            $field = $this->primaryKey;
        }

        $this->db->where_in($field, $values);

        return $this->db->delete($this->table);
    }


    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        $watch = array('findBy', 'findAllBy', 'findFieldBy');

        foreach ($watch as $found) {
            if (stristr($method, $found)) {
                $field = strtolower(str_replace($found, '', $method));
                return $this->$found($field, $args);
            }
        }
    }

    /**
     * Returns a property value based on its name.
     * Do not call this method. This is a PHP magic method that we override
     * to allow using the following syntax to read a property or obtain event handlers:
     * <pre>
     * $value=$model->propertyName;
     * </pre>
     *
     * @param string $name the property name
     *
     * @return mixed the property value
     * @see __set
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter))
            return $this->$getter();
        elseif (isset($this->$name))
            return $this->$name;
        elseif(isset($this->CI->$name)){
            return $this->CI->$name;
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property : ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return NULL;
    }


    /**
     * @param        $field
     * @param        $value
     * @param string $fields
     * @param null   $order
     *
     * @return bool
     */
    public function findBy($field, $value, $fields = '*', $order = NULL)
    {
        $arg_list = array();
        if (is_array($value)) {
            $arg_list = $value;
            $value    = $arg_list[0];
        }
        $fields = isset($arg_list[1]) ? $arg_list[1] : $fields;
        $order  = isset($arg_list[2]) ? $arg_list[2] : $order;

        $where = array($field => $value);
        return $this->find($where, $fields, $order);
    }

    /**
     * @param        $field
     * @param        $value
     * @param string $fields
     * @param null   $order
     * @param int    $start
     * @param null   $limit
     *
     * @return mixed
     */
    public function findAllBy($field, $value, $fields = '*', $order = NULL, $start = 0, $limit = NULL)
    {
        $arg_list = array();
        if (is_array($value)) {
            $arg_list = $value;
            $value    = $arg_list[0];
        }
        $fields = isset($arg_list[1]) ? $arg_list[1] : $fields;
        $order  = isset($arg_list[2]) ? $arg_list[2] : $order;
        $start  = isset($arg_list[3]) ? $arg_list[3] : $start;
        $limit  = isset($arg_list[4]) ? $arg_list[4] : $limit;

        $where = array($field => $value);
        return $this->findAll($where, $fields, $order, $start, $limit);
    }

    /**
     *
     * @param        $field
     * @param        $value
     * @param string $fields
     * @param null   $order
     *
     * @return mixed
     */
    public function findFieldBy($field, $value, $fields = '*', $order = NULL)
    {
        $arg_list = array();
        if (is_array($value)) {
            $arg_list = $value;
            $value    = $arg_list[0];
        }
        $fields = isset($arg_list[1]) ? $arg_list[1] : $fields;
        $order  = isset($arg_list[2]) ? $arg_list[2] : $order;
        $where  = array($field => $value);
        return $this->field($where, $fields, $fields, $order);
    }

    /**
     * call the ci->db->order_by method as per provided param
     * The param can be string just like default order_by function expect
     * or can be array with set of param!!
     * <pre>
     * $model->orderby('fieldname DESC');
     * or
     * $model->orderby(array('fieldname','DESC'));
     * or
     * $model->orderby(array(array('fieldname','DESC'),'fieldname DESC'));
     * </pre>
     *
     * @param mixed(string|array) $orders
     *
     * @return bool
     */
    public function order_by($orders = NULL)
    {
        if ($orders == NULL) {
            return FALSE;
        }

        if (is_array($orders)) { //Multiple order by provided!
            //check if we got single order by passed as array!!
            if (isset($orders[1]) && (strtolower($orders[1]) == 'asc' || strtolower($orders[1]) == 'desc' || strtolower($orders[1]) == 'random')) {
                $this->db->order_by($orders[0], $orders[1]);
                return;
            }
            foreach ($orders as $order) {
                $this->order_by($order);
            }
            return;
        }

        $this->db->order_by($orders); //its a string just call db order_by

        return TRUE;
    }


    /**
     * @param $sql
     *
     * @return mixed
     */
    public function executeQuery($sql)
    {
        return $this->db->query($sql);
    }

    /**
     * @return mixed
     */
    public function getLastQuery()
    {
        return $this->db->last_query();
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function getInsertString($data)
    {
        return $this->db->insert_string($this->table, $data);
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return int
     */
    public function getNumRows()
    {
        return $this->numRows;
    }

    /**
     * @return null|int
     */
    public function getInsertId()
    {
        return $this->insertId;
    }

    /**
     * @return null|mix
     */
    public function getAffectedRows()
    {
        return $this->affectedRows;
    }

    /**
     * @param $primaryKey
     */
    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * @param $returnArray
     */
    public function setReturnArray($returnArray)
    {
        $this->returnArray = $returnArray;
    }
}
