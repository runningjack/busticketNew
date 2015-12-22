<?php
/**
 * Created by PhpStorm.
 * User: Amedora
 * Date: 7/16/15
 * Time: 2:39 PM
 */

namespace models;
use system\library\Database\Model;
use system\library\Verify;


class Account extends Model {
    protected  static $db_fields=array('id','merchant_id','app_id','route_id','route_name','station_id','station_name','status',
        'view','balance','created_at','updated_at');
    protected static $table ="accounts";
    public $id;
    public $merchant_id;
    public $app_id;
    public $route_id;
    public $route_name;
    public $station_id;
    public $station_name;
    public $status;
    public $view;
    public $balance;
    public $created_at;
    public $updated_at;

    protected   function attributes(){
        //return get_object_vars($this);
        $instance = new static;
        $attributes = array();
        foreach(self::$db_fields as $field){
            if(property_exists($this,$field)){
                $attributes[$field] =$this->$field;
            }
        }
        return $attributes;
    }
} 