MY_Model
===========

An extension to CodeIgniter's general Model class to assist in writing
very small amount of code to do general tasks that are needed in regular
application.

Methods
=========

* findBy($field, $value, $fields, $order)
* findAllBy($field, $value, $fields, $order, $start, $limit)
* findAll($conditions, $fields, $order, $start, $limit)
* find($conditions, $fields, $order)
* field($conditions, $name, $fields, $order)
* findCount($conditions)
* insert($data)
* update($data, $id, $conditions)
* onDuplicateUpdate($data, $update)
* remove($id, $conditions)
* remove_in($values, $field)
* order_by($orders)
* getResult()