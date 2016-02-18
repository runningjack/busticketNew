<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 1/20/2016
 * Time: 8:36 PM
 */

namespace models;


use system\library\Database\Model;

class Driver extends Model
{
    protected  static $db_fields=array('id','app_id','hashed','key_salt','ip','firstname','lastname','licence_code','issue_date','expiry_date',
        'addresss','city','state','country','phone','email','username','password','verified','disabled','is_logged_in','route_id','bus_id','created_at','updated_at');
    protected static $table ="drivers";

    public $id;
    public $app_id;
    public $hased;
    public $key_salt;
    public $ip;
    public $firstname;
    public $lastname;
    public $licence_code;
    public $issue_date;
    public $expiry_date;
    public $address;
    public $city;
    public $state;
    public $country;
    public $phone;
    public $email;
    public $usename;
    public $password;
    public $verified;
    public $disabled;
    public $is_logged_in;
    public $route_id;
    public $bus_id;
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

    public function validateUniqueEmailFailed(array $data){
        $v = new Validator($data, array(
            new Unique("email","field must be unique","customer")
        ));

        if (! $v->execute()) {
            print_r($v->getErrors());
        }else{
            return true;
        }
    }

}